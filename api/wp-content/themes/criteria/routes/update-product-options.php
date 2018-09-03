<?php
// Request sent from /collection/:product/product-options/
// Used to update ACF fields ([global_product_options, product_variations]) on product page

add_action('wp_ajax_update_product_options', 'update_product_options');
add_action('wp_ajax_nopriv_update_product_options', 'update_product_options');

function update_product_options() {
  $data = new UpdateProductOptions($_POST);
  $data->init();
}

class UpdateProductOptions {
  public function __construct($payload) {
    $this->payload = json_decode(file_get_contents('php://input'), true);
    $this->product_id = get_page_by_path($this->payload['product'], OBJECT, 'collection')->ID;
    $this->product = $this->payload['data'];
    $this->response = array();
  }

  public function init() {
    if (!is_user_logged_in()) return;

    // Update product
    $this->update_options();
    $this->update_variations();

    // Return payload
    $this->payload();
  }

  private function payload() {
    $this->response = array(
      'isAuth' => true,
      'title' => get_the_title($this->product_id),
      'updated' => true
    );

    echo json_encode($this->response);
    die();
  }

  // Update global product options (i.e Colour: [Red, Green, Blue])
  private function update_options() {
    $product_options = $this->product['options'];
    $row_id = 1;
    foreach ($product_options as $option) {
      $row_data = array(
        'option_name' => $option['option']
      );
      if (filter_var($option['removed'], FILTER_VALIDATE_BOOLEAN)) {
        delete_row('global_product_options', $row_id, $this->product_id);
      } else {
        $sub_row_id = 1;
        foreach ($option['values'] as $value) {
          if (filter_var($value['removed'], FILTER_VALIDATE_BOOLEAN)) {
            delete_sub_row(array('global_product_options', $row_id, 'option_values'), $sub_row_id, $this->product_id);
          } else {
            $sub_row_data = array(
              'value' => $value['value']
            );
            update_sub_row(array('global_product_options', $row_id, 'option_values'), $sub_row_id, $sub_row_data, $this->product_id);
            $sub_row_id++;
          }
        }
        update_row('global_product_options', $row_id, $row_data, $this->product_id);
        $row_id++;
      }
    }
  }

  // Update variations field on product
  private function update_variations() {
    $product_variations = $this->product['variations'];
    $row_id = 1;
    foreach ($product_variations as $variation) {
      $row_data = array(
        'id' => $variation['id'],
        'product_length' => (int) $variation['product_length'],
        'product_depth' => (int) $variation['product_depth'],
        'product_height' => (int) $variation['product_height'],
        'product_weight' => (int) $variation['product_weight'],
        'shipping_length' => (int) $variation['shipping_length'],
        'shipping_height' => (int) $variation['shipping_height'],
        'shipping_depth' => (int) $variation['shipping_depth'],
        'shipping_weight' => (int) $variation['shipping_weight'],
        'shipping_volume' => (int) $variation['shipping_volume'],
        'wholesale_price' => (int) $variation['wholesale_price'],
        'crating_price' => (int) $variation['crating_price'],
        'has_wood' => filter_var($variation['has_wood'], FILTER_VALIDATE_BOOLEAN)
      );
      // If variation has been removed on front-end => delete row from variations list
      if (filter_var($variation['removed'], FILTER_VALIDATE_BOOLEAN)) {
        delete_row('product_variations', $row_id,  $this->product_id);
      } else {
        $sub_row_id = 1;
        foreach ($variation['options'] as $option) {
          if (filter_var($option['removed'], FILTER_VALIDATE_BOOLEAN)) {
            delete_sub_row(array('product_variations', $row_id, 'product_options'), $sub_row_id, $this->product_id);
          } else {
            $sub_row_data = array(
              'option_name' => $option['option'],
              'option_value' => $option['value']['name']
            );
            update_sub_row(array('product_variations', $row_id, 'product_options'), $sub_row_id, $sub_row_data, $this->product_id);
            $sub_row_id++;
          }
        }
        update_row('product_variations', $row_id, $row_data, $this->product_id);
        $row_id++;
      }
    }
  }
}
?>
