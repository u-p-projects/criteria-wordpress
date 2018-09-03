<?php
// Data for about page => /about/

add_action('wp_ajax_about', 'about');
add_action('wp_ajax_nopriv_about', 'about');

function about() {
  $data = new About($_POST);
  $data->init();
}

class About {
  public function __construct($payload) {
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
      'title' => 'About',
      'data' => $this->get_about_information(),
    );

    echo json_encode($this->response);
    die();
  }

  private function get_about_information() {
    return array(
      'about' => get_field('about', 'option'),
      'contact' => get_field('contact', 'option')
    );
  }
}
?>
