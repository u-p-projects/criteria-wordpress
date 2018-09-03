<?php
// Data for single edition /edition/:edition/

add_action('wp_ajax_edition', 'edition');
add_action('wp_ajax_nopriv_edition', 'edition');

function edition() {
  $data = new Edition($_POST);
  $data->init();
}

class Edition {
  public function __construct($payload) {
    $this->edition = $payload['edition'];
    $this->response = array();
    $this->current_user = user_information(wp_get_current_user());
    $this->siteUrl = get_bloginfo('url');
  }

  public function init() {
    // Return payload
    $this->payload();
  }

  private function payload() {
    $this->response = array(
      'current_user' => $this->current_user['is_auth'] ? $this->current_user : null,
      'data' => array(
        'edition' => $this->get_edition()
      )
    );

    echo json_encode($this->response);
    die();
  }

  private function get_edition() {
    $edition = array_shift(get_posts(
      array(
        'name' => $this->edition,
        'post_status' => 'any',
        'post_type' => 'edition',
        'fields' => 'ids',
        'posts_per_page' => 1
      )
    ));

    if ($edition && !empty($this->edition)) {
      $edition_category = get_field('category_link', $edition);
      $products = get_posts(array(
        'post_status' => 'publish',
        'post_type' => 'collection',
        'orderby' => 'menu_order',
        'no_found_rows' => true,
        'fields' => 'ids',
        'posts_per_page' => -1,
        'tax_query' => array(
          'relation' => 'AND',
          array(
            'taxonomy' => 'edition',
            'field' => 'slug',
            'terms' =>  $edition_category->slug
          )
        )
      ));
      $products_list = array();

      foreach ($products as $product) {
        $image = get_field('featured_image', $product);

        array_push($products_list, array(
          'title' => get_the_title($product),
          'slug' => explode($this->siteUrl, get_permalink($product))[1],
          'image' => array(
            'url' => explode($this->siteUrl, $image['sizes']['medium_large'])[1],
            'top' => get_field('image_to_top', $image['ID'])
          ),
          'meta' => get_meta($product)
        ));
      }

      return array(
        'title' => get_the_title($edition),
        'id' => $edition,
        'slug' => explode($this->siteUrl, get_permalink($edition))[1],
        'date' => get_field('date', $edition),
        'description' => get_field('description', $edition),
        'gallery' => get_field('gallery', $edition),
        'products' => $products_list
      );
    }
  }
}
?>
