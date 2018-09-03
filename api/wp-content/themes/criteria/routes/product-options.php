<?php
// Data on both product and product-options page

add_action('wp_ajax_product_options', 'product_options');
add_action('wp_ajax_nopriv_product_options', 'product_options');

function product_options() {
  $data = new ProductOptions($_POST, $ID);
  $data->init();
}

class ProductOptions {
  public function __construct($payload, $ID) {
    $this->product = get_posts(
      array(
        'name' => $payload['product'],
        'post_status' => 'any',
        'post_type' => 'collection',
        'posts_per_page' => 1
      )
    );
    $this->product_id = $ID ?: get_page_by_path($payload['product'], OBJECT, 'collection')->ID;
    $this->response = array();

    // error_log( print_r($payload, true), 0);
  }

  public function init() {
    // Hide page from non logged in users
    if (!is_user_logged_in()) return;

    // Return payload
    $this->payload();
  }

  private function payload() {
    if ($this->product) {
      $this->response = array(
        'isAuth' => true,
        'test' => $this->product_id,
        'title' => get_the_title($this->product_id),
        'data' => array(
          'product' => array(
            'valid' => true,
            'is_lighting' => $this->is_lighting(),
            'edit_link' => get_edit_post_link($this->product_id, '&'),
            'meta' => $this->get_meta(),
            'order' => $this->get_order_specific(),
            'information' => $this->get_information(),
            'lead_times' => $this->get_lead_times(),
            'delivery_days' => $this->calculate_delivery_days(),
            // 'closure_periods' => $this->get_closure_dates(),
            'options' => $this->get_options(true),
            'variations' => $this->get_variations(true),
            'lighting' => $this->is_lighting() ? $this->lighting_options() : null
          )
        )
      );
    } else {
      $this->response = array(
        'isAuth' => true,
        'title' => get_the_title($this->product_id),
        'data' => array(
          'product' => array(
            'valid' => false,
            'error' => 'Product not found'
          )
        )
      );
    }
    // error_log(print_r($this->response), true, 0);
    echo json_encode($this->response);
    die();
  }

  public function is_lighting() {
    return get_field('is_lighting', $this->product_id);
  }

  private function lighting_options() {
    $lighting_specifications = get_field('lighting_specifications', $this->product_id);
    return $lighting_specifications;
  }

  // Order specfic data
  public function get_order_specific() {
    return array(
      'minimum_order' => get_field('minimum_order', $this->product_id),
    );
  }

  // Get meta from either designer/brand taxonomy page
  public function get_meta() {
    $brand = get_field('brand', $this->product_id);
    $has_brand = $brand !== null;
    $designer = get_field('designer', $this->product_id);

    // If product has a brand
    if ($has_brand) {
      return array(
        'brand' => array(
          'name' => $brand->name,
          'slug' => $brand->slug,
          'id' => $brand->term_id
        ),
        'designer' => $designer ? array(
          'name' => $designer->name,
          'slug' => $designer->slug,
          'id' => $designer->term_id
        ) : null,
        'currency' => get_field('currency', 'brand_' . $brand->term_id),
        'measurement' => get_field('measurement', 'brand_' . $brand->term_id)
      );
    // If product only has a designer
    } else {
      return array(
        'brand' => null,
        'designer' => $designer ? array(
          'name' => $designer->name,
          'slug' => $designer->slug,
          'id' => $designer->term_id
        ) : null,
        'currency' => get_field('currency', 'designer_' . $designer->term_id),
        'measurement' => get_field('measurement', 'designer_' . $designer->term_id)
      );
    }
  }

  // Get product categories
  public function get_categories() {
    $category = get_field('category', $this->product_id);
    return array_map(function($cat) {
      return $cat->name;
    }, $category);
  }

  // Basic product meta
  public function get_information() {
    return array(
      'title' => get_the_title($this->product_id),
      'meta' => array(
        'category' => self::get_categories(),
        'country_of_origin' => get_field('country_of_origin', $this->product_id),
        'year_of_design' => get_field('year_of_design', $this->product_id),
        'edition_of' => get_field('edition_of', $this->product_id)
      ),
      'description' => get_field('description', $this->product_id),
      'notes' => get_field('notes', $this->product_id)
    );
  }


  // Product options (ie. Colour: [Red, Blue, Green])
  public function get_options($is_options_page = false) {
    $options = array();
    $options_field = get_field('global_product_options', $this->product_id);

    if (isset($options_field) && !empty($options_field)) {
      foreach ($options_field as $option) {
        $values = array();
        foreach ($option['option_values'] as $value) {
          $arr = array('value' => $value['value']);
          if ($is_options_page) $arr['removed'] = false;
          array_push($values, $arr);
        }
        $opt_arr = array(
          'option' => $option['option_name'],
          'values' => $values
        );
        if ($is_options_page) $opt_arr['removed'] = false;
        array_push($options, $opt_arr);
      }
    }
    return $options;
  }

  // Product variations list
  public function get_variations($is_options_page = false) {
    $variations = array();
    $variations_field = get_field('product_variations', $this->product_id);

    if (isset($variations_field) && !empty($variations_field)) {
      foreach ($variations_field as $variation) {
        $var = array(
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
          'has_wood' => filter_var($variation['has_wood'], FILTER_VALIDATE_BOOLEAN),
          'options' => array()
        );

        if ($is_options_page) $var['removed'] = false;

        foreach ($variation['product_options'] as $option) {
          if ($is_options_page) {
            array_push($var['options'], array(
              'option' => $option['option_name'],
              'value' => array(
                'name' => $option['option_value'],
                'id' => $option['option_value'] ? null : false
              ),
              'removed' => false
            ));
          } else {
            array_push($var['options'], array(
              'option' => $option['option_name'],
              'value' => $option['option_value']
            ));
          }
        }
        array_push($variations, $var);
      }
    }
    return $variations;
  }

  public function calculate_delivery_days() {
    $lead_times = $this->get_lead_times();
    
    // get relevant dates from $lead_times
    $production_time = intval($lead_times['production_lead_time_max']);
    $delivery_time = intval($lead_times['delivery_to_aus_lead_time_max']);
    $rewiring = !empty($lead_times['rewiring']) ? intval($lead_times['rewiring']) : 0; // check if rewiring is set add that or add 0

    $lead_time_weeks = $production_time + $delivery_time + $rewiring;

    // Get closure periods
    $closure_periods = $this->get_closure_periods();


    $current_date = new DateTime(); // Today's date
    $lead_time_date = new DateTime(); // Today's date
    
    $lead_time_date->add(new DateInterval('P'.($lead_time_weeks * 7).'D')); //'PT'.$timespan.'S'
    
    /* error_log(print_r($closure_periods, true), 0);
    error_log("prod: ". $production_time, 0);
    error_log("del: ". $delivery_time, 0);
    error_log("rewiring: ". $rewiring, 0); */
    // error_log(print_r($current_date, true), 0);
    // error_log(print_r($lead_time_date, true), 0);

    

    if (isset($closure_periods) && !empty($closure_periods) && is_array($closure_periods)) {
      // Need to reduce array if there is overlap in closure periods
      // closure_periods

      $tmp = $closure_periods[0]['start_date'];
      $reduce_closures = array_filter(function($arr){
        if ($tmp > $arr['start_date'] && $tmp <= $arr['start_date']){
          return $arr;
        }
        $tmp = $arr;
      }, (array) $closure_periods);


      // error_log(print_r($closure_periods, true), 0);
      // error_log(print_r($reduce_closures, true), 0);

      foreach($closure_periods as $period) {
        if(isset($period['end_date']) && !empty($period['end_date'])) {
          $period_date = new DateTime($period['end_date']);

          if ($lead_time_date > $period['end_date']){
            // error_log(print_r($period_date, true), 0);
            // error_log('Need to add extra time', 0);
          }
          // $period_date = '';
          // $closure_end_date_timestamp = mktime($period['end_date']);

          // date compare both dates
        }
      }
    } else {
      return $lead_time_weeks * 7;
    }
  }

  public function find_date_overlap($arr1, $arr2) {
    // if($arr1[])
  }

  public function get_closure_periods() {
    // Get Brand
    $brand = get_field('brand', $this->product_id);
    if (isset($brand) && !empty($brand)) {
      $use = ('brand_' . $brand->term_id);
    }

    // Grab closure dates
    $closure_periods = get_field('closure_periods', $use);

    // error_log(print_r($closure_periods, true), 0);
    return $closure_periods;
  }

  public function get_lead_times() {
    $category = get_field('category', $this->product_id)[0]; // get first category (green)
    $term = $category; // $term = ($category->parent !== 0) ? get_term($category->parent, 'product_category') : $category;
  
    // Get the brand
    $brand = get_field('brand', $this->product_id); // WP Term Query

    if (isset($brand) && !empty($brand)) {
      $use = ('brand_' . $brand->term_id);
    } else {
      $designer = get_field('designer', $this->product_id);
      $is_independent = filter_var(get_field('independent_designer', 'designer_' . $designer->term_id), FILTER_VALIDATE_BOOLEAN);
      $use = $is_independent ? ('designer_' . $designer->term_id) : ('brand_' . get_field('brand', 'designer_' . $designer->term_id)->term_id);
    }

    // Grab Lead times
    $lead_times = get_field('lead_times', $use);

    // Filter Lead times - match to the same category
    if(isset($lead_times) && !empty($lead_times)) {
      // traverse lead times
      foreach ($lead_times as $lead_time) {
        // match category and return parent array
        foreach ($lead_time['category'] as $lt_key => $lt_cat) {
          if ($lt_cat->term_id === $term->term_id) {
            // TODO Array reduce categories to only the relevant category.
            // error_log(print_r($lead_time, true), 0);

            // Assign lead time to returned lead time
            $required_lead_time = $lead_time;
            break; // break out of loop on true
          }
        } 
      }
    }
    return $required_lead_time;
  }
}
?>
