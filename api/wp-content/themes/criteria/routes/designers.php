<?php
// Data for designers page => /designers/

add_action('wp_ajax_designers', 'designers');
add_action('wp_ajax_nopriv_designers', 'designers');

function designers() {
  $data = new Designers($_POST);
  $data->init();
}

class Designers {
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
      'title' => 'Designers',
      'data' => $this->get_brands_and_designers()
    );

    echo json_encode($this->response);
    die();
  }

  private function get_brands_and_designers() {
    $designers = get_terms(array(
      'taxonomy' => array('designer'),
      'hide_empty' => false,
      'parent' => 0
    ));

    $brands = get_terms(array(
      'taxonomy' => array('brand'),
      'hide_empty' => false,
      'parent' => 0
    ));

    return array(
      'designers' => array_map(function($cat) {
          return array(
            'name' => $cat->name,
            'slug' => $cat->slug,
            'type' => $cat->taxonomy
          );
        }, $designers),
      'brands' => array_map(function($cat) {
          return array(
            'name' => $cat->name,
            'slug' => $cat->slug,
            'type' => $cat->taxonomy
          );
        }, $brands)
    );
  }
}
?>
