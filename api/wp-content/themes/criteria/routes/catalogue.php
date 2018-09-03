<?php
// Data for catalogue page => /catalogue/

add_action('wp_ajax_catalogue', 'catalogue');
add_action('wp_ajax_nopriv_catalogue', 'catalogue');

function catalogue() {
  $data = new Catalogue($_POST);
  $data->init();
}

class Catalogue {
  public function __construct($payload) {
    $this->query = $payload['query'];
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
      'title' => 'Catalogue',
      'data' => array(
        'catalogues' => $this->get_catalogues()
      )
    );

    echo json_encode($this->response);
    die();
  }

  private function get_catalogues() {
    $catalogues_list = array();
    $query = new WP_Query(array(
      'post_status' => 'publish',
      'post_type' => 'catalogue',
      'orderby'   => 'menu_order',
      'no_found_rows' => true,
      'fields' => 'ids',
      'posts_per_page' => -1
    ));

    if ($query) {
      while ($query->have_posts()) {
        $query->the_post();
        global $post;

        array_push($catalogues_list, array(
          'title' => get_the_title($post),
          'id' => $post,
          'slug' => explode($this->siteUrl, get_permalink($post))[1],
          'gallery' => get_field('gallery', $post),
          'overview' => get_field('overview', $post),
          'meta' => get_field('meta', $post),
        ));
      }
    }

    return $catalogues_list;
  }
}
?>
