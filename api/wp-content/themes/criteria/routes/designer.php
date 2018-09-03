<?php
// Data for selected designer/brand => /designers/:designer/

add_action('wp_ajax_designer', 'designer');
add_action('wp_ajax_nopriv_designer', 'designer');

function designer() {
  $data = new Designer($_POST);
  $data->init();
}

class Designer {
  public function __construct($payload) {
    $this->taxonomy = $payload['taxonomy'];
    $this->response = array();
    $this->current_user = user_information(wp_get_current_user());
    $this->siteUrl = get_bloginfo('url');
    $this->designer = $this->get_designer($payload['designer']);
  }

  public function init() {
    // Return payload
    $this->payload();
  }

  private function payload() {
    $this->response = array(
      'current_user' => $this->current_user['is_auth'] ? $this->current_user : null,
      'data' => array(
        'designer' => $this->get_designer_content(),
        'lead_times' => $this->get_lead_times(),
        'closure_periods' => $this->get_closure_periods()
      )
    );

    echo json_encode($this->response);
    die();
  }

  private function get_lead_times() {
    if ($this->current_user['is_auth']) {
      return array(
        'data' => get_field('lead_times', $this->taxonomy . '_' . $this->designer->term_id),
        'edit_link' => $this->siteUrl . '/api/wp-admin/term.php?taxonomy=' . $this->taxonomy . '&tag_ID=' . $this->designer->term_id . '&post_type=collection'
      );
    }
  }

  private function get_closure_periods() {
    if ($this->current_user['is_auth']) {
      return array(
        'data' => get_field('close_periods', $this->taxonomy . '_' . $this->designer->term_id),
        'edit_link' => $this->siteUrl . '/api/wp-admin/term.php?taxonomy=' . $this->taxonomy . '&tag_ID=' . $this->designer->term_id . '&post_type=collection'
      );
    }
  }

  private function get_designer($designer_slug) {
    if (empty($this->taxonomy)) {
      foreach (array('designer', 'brand') as $taxonomy) {
        $designer = get_term_by('slug', $designer_slug, $taxonomy);
        if ($designer) {
          $this->taxonomy = $taxonomy;
          break;
        }
      }
    } else {
      $designer = get_term_by('slug', $designer_slug, $this->taxonomy);
    }
    return $designer;
  }

  private function get_designer_content() {
    if ($this->designer) {
      $designers = $this->taxonomy === 'brand' ? array() : null;
      $products = get_posts(array(
        'post_status' => 'publish',
        'post_type' => 'collection',
        'no_found_rows' => true,
        'fields' => 'ids',
        'posts_per_page' => -1,
        // 'posts_per_page' => 3,

        'tax_query' => array(
          'relation' => 'AND',
          array(
            'taxonomy' => $this->taxonomy,
            'field' => 'id',
            'terms' => $this->designer->term_id
          )
        )
      ));

      $result = array(
        'title' => $this->designer->name,
        'type' => $this->taxonomy,
        'description' => nl2br($this->designer->description),
        'products' => array()
      );

      // Products
      foreach ($products as $product) {
        // Build brand designers
        $meta = get_meta($product);
        if ($this->taxonomy === 'brand') {
          if ($meta['designer']) {
            array_push($designers, $meta['designer']);
          }
        }

        // Build materials
        $materials = array_map(function($material) {
          return $material->name;
        }, wp_get_post_terms($product, 'material'));

        $image = get_field('featured_image', $product);

        array_push($result['products'], array(
          'title' => get_the_title($product),
          'slug' => explode($this->siteUrl, get_permalink($product))[1],
          'image' => array(
            'url' => explode($this->siteUrl, $image['sizes']['medium_large'])[1],
            'top' => get_field('image_to_top', $image['ID'])
          ),
          'meta' => $meta,
          'materials' => $materials,
        ));
      }

      // Meta
      if ($this->taxonomy === 'designer') {
        $result['meta'] = array(
          'year_of_birth' => get_field('year_of_birth', 'designer_' . $this->designer->term_id),
          'origin' => get_field('origin', 'designer_' . $this->designer->term_id)
        );
      } else {
        $result['meta'] = array(
          'currency' => get_field('currency', 'brand_' . $this->designer->term_id)
        );
      }

      // Add designers to result
      $result['designers'] = array_values(array_unique($designers));

      return $result;
    } else {
      return array(
        'error' => true,
        'message' => 'Designer not found.'
      );
    }
  }
}
?>
