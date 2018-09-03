<?php
// Data required for a product page => /product/:product/

add_action('wp_ajax_product', 'product');
add_action('wp_ajax_nopriv_product', 'product');

function product() {
  $data = new Product($_POST);
  $data->init();
}

class Product {
  public function __construct($payload) {
    $this->payload = $payload;
    $this->product = get_posts(
      array(
        'name' => $payload['product'],
        'post_status' => 'any',
        'post_type' => 'collection',
        'posts_per_page' => 1
      )
    );
    $this->siteUrl = get_bloginfo('url');
    $this->product_id = get_page_by_path($payload['product'], OBJECT, 'collection')->ID;
    $this->response = array();
    $this->current_user = user_information(wp_get_current_user());
  }

  public function init() {
    // Return payload
    $this->payload();
  }

  private function payload() {
    if ($this->product) {

      $data = new ProductOptions($this->payload, $this->product_id); // From routes/product-options.php
      $this->response = array(
        'current_user' => $this->current_user['is_auth'] ? $this->current_user : null,
        'title' => get_the_title($this->product_id),
        'id' => $this->product_id,
        'data' => array(
          'valid' => true,
          'is_lighting' => $data->is_lighting(),
          'is_available' => $this->check_if_available_now(),
          'options_link' => $this->current_user['is_auth'] ? get_permalink($this->product_id) . 'product-options/' : null,
          'edit_link' => $this->current_user['is_auth'] ? get_edit_post_link($this->product_id, '&') : null,
          'meta' => $data->get_meta(),
          'images' => $this->get_images(),
          'information' => $data->get_information(),
          'options' => $data->get_options(),
          'variations' => $data->get_variations(),
          'related' => $this->get_related_products($data->get_meta())
        )
      );
    } else {
      $this->response = array(
        'current_user' => $this->current_user['is_auth'] ? $this->current_user : null,
        'title' => get_the_title($this->product_id),
        'data' => array(
          'valid' => false,
          'error' => 'Product not found'
        )
      );
    }

    echo json_encode($this->response);
    die();
  }

  // Product gallery images
  private function get_images() {
    $gallery = get_field('gallery', $this->product_id);
    $i = 0;
    if (isset($gallery) && !empty($gallery)) {
      foreach ($gallery as $image) {
        $gallery[$i]['align_to_top'] = get_field('image_to_top', $image['ID']);
        $i++;
      }
    }
    return array(
      'gallery' => $gallery,
      'additional_images' => get_field('gallery_insitu', $this->product_id)
    );
  }

  // Check if product is in category 'Now'
  private function check_if_available_now() {
    $is_available = false;
    $terms = wp_get_post_terms($this->product_id, 'product_category', array('fields' => 'names', 'order' => 'DESC'));
    foreach ($terms as $category) {
      if ($category === 'Now') {
        return array(
          'status' => true,
          'available_now_text' => get_field('available_now_text', $this->product_id)
        );
        break;
      }
    }
    return null;
  }

  private function get_related_products($meta) {
    $related_products = array();
    $args = array(
      'post_status' => 'publish',
      'post_type' => 'collection',
      'no_found_rows' => true,
      'fields' => 'ids',
      'post__not_in' => array($this->product_id),
      'posts_per_page' => 5,
      'orderby' => 'rand',
      'tax_query' => array(
        'relation' => 'AND'
      )
    );

    if ($meta['brand']) {
      array_push($args['tax_query'], array(
        'taxonomy' => 'brand',
        'field' => 'slug',
        'terms' => $meta['brand']['slug']
      ));
    }

    if ($meta['designer']) {
      array_push($args['tax_query'], array(
        'taxonomy' => 'designer',
        'field' => 'slug',
        'terms' => $meta['designer']['slug']
      ));
    }

    $products = get_posts($args);

    foreach ($products as $product) {
      $image = get_field('featured_image', $product);

      array_push($related_products, array(
        'title' => get_the_title($product),
        'slug' => explode($this->siteUrl, get_permalink($product))[1],
        'image' => array(
          'url' => explode($this->siteUrl, $image['sizes']['medium_large'])[1],
          'top' => get_field('image_to_top', $image['ID'])
        ),
        'meta' => get_meta($product)
      ));
    }
    return $related_products;
  }
}
?>
