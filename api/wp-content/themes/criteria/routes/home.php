<?php
// Home page data

add_action('wp_ajax_home', 'home');
add_action('wp_ajax_nopriv_home', 'home');

function home() {
  $data = new Home($_POST);
  $data->init();
}

class Home {
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
      'title' => 'Collection',
      'data' => array(
        'categories' => $this->get_categories(),
        'designers' => $this->get_designers()
      )
    );

    echo json_encode($this->response);
    die();
  }

  private function get_designers() {
    $designers = get_terms(array(
      'taxonomy' => array('designer', 'brand'),
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

  private function get_categories() {
    $product_sub_categories = array();
    $product_categories = get_terms(array(
      'taxonomy' => 'product_category',
      'hide_empty' => false,
      'parent' => 0
    ));

    return array_map(function($cat) {
      return array(
        'name' => $cat->name,
        'slug' => $cat->slug
      );
    }, $product_categories);
  }
}
?>
