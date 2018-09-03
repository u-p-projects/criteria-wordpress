<?php
// Get enquiry list data on /enquiry?

add_action('wp_ajax_get_enquiry_list', 'get_enquiry_list');
add_action('wp_ajax_nopriv_get_enquiry_list', 'get_enquiry_list');

function get_enquiry_list() {
  $data = new GetEnquiryList($_POST);
  $data->init();
}

class GetEnquiryList {
  public function __construct($payload) {
    $this->payload = json_decode(file_get_contents('php://input'), true);
    $this->list = $this->payload['list'];
    $this->siteUrl = get_bloginfo('url');
  }

  public function init() {
    // Return payload
    $this->payload();
  }

  private function payload() {
    $this->response = array(
      'title' => 'Enquiry',
      'list' => $this->get_list()
    );

    echo json_encode($this->response);
    die();
  }

  private function get_list() {
    $items = $this->list ? array() : null;
    if ($this->list) {
      foreach ($this->list as $item) {
        $ID = (int) $item['id'];
        $product_data = new ProductOptions(null, $ID);
        $variations = get_field('product_variations', $ID);
        $variation = array(
          'title' => get_the_title($ID),
          'slug' => explode($this->siteUrl, get_permalink($ID))[1],
          'image' => explode($this->siteUrl, get_field('featured_image', $ID)['url'])[1],
          'meta' => $product_data->get_meta()
        );

        foreach($variations as $var) {
          if ($var['id'] == $item['var']) {
            $variation['options'] = $var['product_options'];
            break;
          }
        }
        array_push($items, $variation);
      }
    }
    return $items;
  }
}
?>
