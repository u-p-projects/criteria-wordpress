<?php
// Data for => /edition/shows/

add_action('wp_ajax_editions', 'editions');
add_action('wp_ajax_nopriv_editions', 'editions');

function editions() {
  $data = new Editions($_POST);
  $data->init();
}

class Editions {
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
      'title' => 'Edition',
      'data' => array(
        'editions' => $this->get_editions()
      )
    );

    echo json_encode($this->response);
    die();
  }

  private function get_editions() {
    $editions_list = array();
    $query = new WP_Query(array(
      'post_status' => 'publish',
      'post_type' => 'edition',
      'orderby'   => 'menu_order',
      'no_found_rows' => true,
      'fields' => 'ids',
      'posts_per_page' => -1
    ));

    if ($query) {
      while ($query->have_posts()) {
        $query->the_post();
        global $post;

        array_push($editions_list, array(
          'title' => get_the_title($post),
          'id' => $post,
          'slug' => explode($this->siteUrl, get_permalink($post))[1],
          'featured' => get_field('featured_image', $post),
          'date' => get_field('date', $post),
          'year' => get_field('year', $post),
          'current' => get_field('current', $post),
          'byline' => get_field('byline', $post),
          'excerpt' => get_field('excerpt', $post)
        ));
      }
    }

    return $editions_list;
  }
}
?>
