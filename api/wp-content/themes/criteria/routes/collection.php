<?php
// Data for collection page (and filtered collection page) => /collection/ & /collection/filter?/

add_action('wp_ajax_collection', 'collection');
add_action('wp_ajax_nopriv_collection', 'collection');

function collection() {
  $data = new Collection($_POST);
  $data->init();
}

class Collection {
  public function __construct($payload) {
    $this->query = $payload['query'];
    $this->response = array();
    $this->current_user = user_information(wp_get_current_user());
    $this->siteUrl = get_bloginfo('url');
    $this->cached_file = TEMPLATEPATH . '/cache/' . 'collection.json';
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
      'title' => 'Collection',
      'data' => array(
        'products' => $cached_products ?: $this->get_products(),
        'categories' => $this->get_categories(),
        'designers' => $this->get_designers(),
        'colours' => $this->get_colours(),
        'materials' => $this->get_materials(),
        'editions' => $this->get_editions()
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
    if(isset($query) && !empty($query)) {
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
    }

    // Setup arrays
    $products = new WP_Query($args);

    // TODO featured Products 
    $featured_products = array();

    // TODO now products
    $now_products = array();
    $non_featured_products = array();

    $i = 0;
    if ($products) {
      while ($products->have_posts()) {
        $products->the_post();
        global $post;

        // Check if is 'Now' category
        $is_available = false;
        $terms = wp_get_post_terms($post, 'product_category', array('fields' => 'names', 'order' => 'DESC'));
        foreach ($terms as $category) {
          if ($category === 'Now') {
            $is_available = true;
            break;
          }
        }

        // Is featured
        $is_featured = get_field('featured');

        // Image data
        $image = get_field('featured_image');

        // error_log(print_r($image, true), 0);

        // Product data
        $product_data = array(
          'title' => get_the_title(),
          'slug' => explode($this->siteUrl, get_permalink())[1],
          'image' => array(
            'url' => '', //explode($this->siteUrl, $image['sizes']['medium_large'])[1],
            'top' => '', //get_field('image_to_top', $image['ID'])
          ),
          'meta' => get_meta($post->ID),
          'is_available' => $is_available, 
          'is_featured' => $is_featured
        );

        if (isset($image['ID'])) {
          $product_data['image'] = array(
            'url' => explode($this->siteUrl, $image['sizes']['medium_large'])[1],
            'top' => get_field('image_to_top', $image['ID'])
          );

          // error_log(get_field('image_to_top', $image['ID']), 0);
        }

        // Add to appropriate array (featured/now/default)
        if ($is_featured) {
          $product_data['featured_order'] = get_field('featured_order', $post->ID) ?: 4;
          array_push($featured_products, $product_data);
        } else if ($is_available) {
          array_push($now_products, $product_data);
        } else {
          array_push($non_featured_products, $product_data);
        }
      }
    }

    usort($featured_products, function($a, $b) {
      return $a['featured_order'] - $b['featured_order'];
    });
    
    // Merge Featured, Now & non featured products together
    $filtered_products = array_merge($featured_products, $now_products, $non_featured_products);

    // If no query params => it is full collection => cache file
    if (!$this->check_if_has_query()) {
      file_put_contents($this->cached_file, json_encode($filtered_products));
    }

    // Resets Querys & Posts
    // wp_reset_postdata();
    // wp_reset_query();

    return $filtered_products;
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

  // Get all edition terms from taxonomy 'edition'
  private function get_editions() {
    $editions = get_terms(array(
      'taxonomy' => 'edition',
      'hide_empty' => false,
      'parent' => 0
    ));

    return array_map(function($cat) {
      return array(
        'name' => $cat->name,
        'slug' => $cat->slug,
        'type' => $cat->taxonomy
      );
    }, $editions);
  }

  // Get all colours from taxonomy 'colour'
  private function get_colours() {
    $designers = get_terms(array(
      'taxonomy' => 'colour',
      'hide_empty' => false,
      'parent' => 0
    ));

    return array_map(function($cat) {
      return array(
        'name' => $cat->name,
        'slug' => $cat->slug
      );
    }, $designers);
  }

  // Get all materials from taxonomy 'material'
  private function get_materials() {
    $designers = get_terms(array(
      'taxonomy' => 'material',
      'hide_empty' => false,
      'parent' => 0
    ));

    return array_map(function($cat) {
      return array(
        'name' => $cat->name,
        'slug' => $cat->slug
      );
    }, $designers);
  }
}
?>
