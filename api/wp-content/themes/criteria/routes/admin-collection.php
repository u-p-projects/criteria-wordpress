<?php

add_action('wp_ajax_admin_collection', 'admin_collection');
add_action('wp_ajax_nopriv_admin_collection', 'admin_collection');

function admin_collection() {
  $data = new AdminCollection($_POST);
  $data->init();
}

class AdminCollection {
  public function __construct($payload) {
    $this->query = $payload['query'];
    $this->response = array();
    $this->current_user = user_information(wp_get_current_user());
    $this->siteUrl = get_bloginfo('url');
    // $this->cached_file = TEMPLATEPATH . '/cache/' . 'collection.json';
  }

  public function init() {
    // Return payload
    if ($this->check_if_has_query()) {
      $this->payload();
    } else {
      if (file_exists($this->cached_file) && $this->check_if_cache_valid($this->cached_file)) {
        $this->payload(); // TODO: change back to cached_payload();
      } else {
        $this->payload();
      }
    }
  }
  
  private function check_if_cache_valid($cached_file) {
    $ttl = 60 * 60; // 1HR
    $stat = stat($cached_file);
    $delta = time() - $stat['mtime'];
    return ($delta < $ttl);
  }

  // Check for if request has query params (/filter?)
  private function check_if_has_query() {
    $check = false;
    $query = $this->query;
    if(isset($query) && !empty($query)) {
      foreach ($query as $q => $value) {
        if ($value !== undefined) {
          $check = true;
          break;
        }
      }
    }
    return $check;
  }

  private function cached_payload() {
    return $this->payload(json_decode(file_get_contents($this->cached_file)));
  }

  private function payload($cached_products = null) {
    $this->response = array(
      'current_user' => $this->current_user['is_auth'] ? $this->current_user : null,
      'title' => 'AdminCollection',
      'data' => array(
        'products' => $cached_products ?: $this->get_products()
      )
    );

    echo json_encode($this->response);
    die();
  }

  private function get_products() {
    $filtered_products = array();
    $args = array(
      'post_status' => 'publish',
      'post_type' => 'collection',
      'orderby' => 'menu_order',
      'no_found_rows' => true,
      'fields' => 'ids',
      'posts_per_page' => -1,
      'tax_query' => array(
        'relation' => 'AND'
      )
    );

    $query = $this->query;

    // If has filter query params => build filtered collection
    /* if(isset($query) && !empty($query)) {
      foreach ($query as $q => $value) {
        if ($value !== undefined) {
          switch ($q) {
            case 'category':
              array_push($args['tax_query'], array(
                'taxonomy' => 'product_category',
                'field' => 'slug',
                'terms' => $value
              ));
              break;
            case 'subcategory':
              array_push($args['tax_query'], array(
                'taxonomy' => 'product_category',
                'field' => 'slug',
                'terms' => $value
              ));
              break;
            case 'edition':
              if ($value === 'all') {
                $value = array_map(function($cat) {
                  return $cat->slug;
                }, get_terms(array(
                  'taxonomy' => 'edition',
                  'hide_empty' => false,
                  'parent' => 0
                )));
              }
              array_push($args['tax_query'], array(
                'taxonomy' => 'edition',
                'field' => 'slug',
                'terms' =>  $value,
              ));
              break;
            default:
              array_push($args['tax_query'], array(
                'taxonomy' => $q,
                'field' => 'slug',
                'terms' => $value
              ));
              break;
          }
        }
      }
    } */

    // Setup arrays
    $products = new WP_Query($args);

    $i = 0;
    $collection_products = array();

    // Setup product data
    if ($products) {
      while ($products->have_posts()) {
        $products->the_post();
        global $post; // contains only the post id

        // Get terms
        // $terms = wp_get_post_terms($post, 'product_category', array('fields' => 'names', 'order' => 'DESC'));

        // Image data
        $image = get_field('featured_image');

        // Get the product options for the product
        $data = new ProductOptions(array('action' => 'product_option', 'product' => get_the_title()), $post);

        // Get the name for the post
        $name = explode('/', get_permalink());

        // Product data
        $product_data = array(
          'title' => get_the_title(),
          'name' => $name[count($name) - 2], // just the name
          'slug' => explode($this->siteUrl, get_permalink())[1],
          'meta' => $data->get_meta($post),
          'categories' => $data->get_categories(),
          'product_options' => array(
            'meta' => $data->get_meta(),
            'options' => $data->get_options(),
            'variations' => $data->get_variations(),
          )
        );

        // Add product data to the collection
        array_push($collection_products, $product_data);
      }
    }

    return $collection_products;
  }

  // Get all categories from taxonomy 'category'
  private function get_categories() {
    $product_sub_categories = array();
    $product_categories = new WP_Term_Query(array(
      'taxonomy' => 'product_category',
      'orderby' => 'order',
      'order' => 'ASC',
      'hide_empty' => false,
      'parent' => 0
    ));

    if ($this->query['category'] !== undefined) {
      foreach ($product_categories->get_terms() as $category) {
        if ($category->slug === $this->query['category']) {
          $children = new WP_Term_Query(array(
            'taxonomy' => 'product_category',
            'orderby' => 'name',
            'order' => 'ASC',
            'hide_empty' => false,
            'parent' => $category->term_id
          ));
          $sub_categories = array_map(function($cat) {
            return array(
              'name' => $cat->name,
              'slug' => $cat->slug
            );
          }, $children->get_terms());
        }
      }
    }

    $categories = array_map(function($cat) {
      return array(
        'name' => $cat->name,
        'slug' => $cat->slug
      );
    }, $product_categories->get_terms());

    return array(
      'primary' => $categories,
      'sub' => $sub_categories
    );
  }

  // Get all designers/brands from taxonomies 'brand' + 'designer'
  private function get_designers() {
    $designers = new WP_Term_Query(array(
      'taxonomy' => array('designer', 'brand'),
      'hide_empty' => false,
      'orderby' => 'slug',
      'order' => 'ASC',
      'parent' => 0
    ));

    return array_map(function($cat) {
      return array(
        'name' => $cat->name,
        'slug' => $cat->slug,
        'type' => $cat->taxonomy
      );
    }, $designers->get_terms());
  }
}
?>
