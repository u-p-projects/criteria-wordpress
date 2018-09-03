<?php
// Used for Adam from criteria

add_action('wp_ajax_collection_dump', 'collection_dump');
add_action('wp_ajax_nopriv_collection_dump', 'collection_dump');

function collection_dump() {
  $data = new CollectionDump($_POST);
  $data->init();
}

class CollectionDump {
  public function __construct($payload) {
    $this->payload = $payload;
    $this->products = get_posts(
      array(
        'post_status' => 'any',
        'post_type' => 'collection',
        'posts_per_page' => -1
      )
    );
    $this->siteUrl = get_bloginfo('url');
    $this->response = array();
    $this->current_user = user_information(wp_get_current_user());
  }

  public function init() {
    // Return payload
    $this->payload();
  }

  private function payload() {
    if ($this->current_user && $this->current_user['is_auth']) {
      $this->response = array(
        'data' => $this->get_products()
      );
    } else {
      $this->response = array(
        'data' => $this->current_user
      );
    }

    echo json_encode($this->response);
    die();
  }

  private function get_products() {
    $products = array();
    foreach ($this->products as $product) {
      $data = new ProductOptions(null, $product);
      array_push($products, array(
        'title' => $product->post_title,
        'slug' => $product->post_name,
        'ID' => $product->ID,
        'meta' => $data->get_meta(),
        'order' => $data->get_order_specific(),
        'variations' => $data->get_variations()
      ));
    }
    return $products;
  }

}
?>
