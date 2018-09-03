<?php
// Add brand/designer to collection table in back-end
function modify_collection_table($columns) {
  unset(
  		$columns['author'],
  		$columns['comments'],
      $columns['date']
  	);

  $new_columns = array(
    'brand_designer' => 'Brand/Designer',
    'featured' => 'Featured'
  );
  return array_merge($columns, $new_columns);
}
add_filter('manage_edit-collection_columns', 'modify_collection_table');

function manage_collection_custom_columns($column) {
  global $post;
  switch ($column) {
    case "brand_designer":
      $brand = get_field('brand');
      $designer = get_field('designer');
      $arr = array();
      if ($brand) $arr['brand'] = $brand->name;
      if ($designer) $arr['designer'] = $designer->name;
      echo '<div>' . (join(", ", $arr)) . '</div>';
      break;
    case "featured":
      $featured = get_field('featured');
      echo '<div>' . isset($featured) && $featured ? 'Yes' : '' . '</div>';
      break;
  }
}
add_action('manage_collection_posts_custom_column', 'manage_collection_custom_columns');


// Disable fields for back-end only
function product_ordering_field_control($field) {
  $screen = get_current_screen();
  if ($screen->parent_base === 'edit' && $screen->post_type !== 'acf-field-group') return false;
  else return $field;
}
add_filter('acf/load_field/name=product_ordering', 'product_ordering_field_control');

function product_pricing_field_control($field) {
  $screen = get_current_screen();
  if ($screen->parent_base === 'edit' && $screen->post_type !== 'acf-field-group') return false;
  else return $field;
}
add_filter('acf/load_field/name=product_pricing', 'product_pricing_field_control');

function product_options_field_control($field) {
  $screen = get_current_screen();
  if ($screen->parent_base === 'edit' && $screen->post_type !== 'acf-field-group') return false;
  else return $field;
}
add_filter('acf/load_field/name=product_options', 'product_options_field_control');

function product_variations_field_control($field) {
  $screen = get_current_screen();
  if ($screen->parent_base === 'edit' && $screen->post_type !== 'acf-field-group') return false;
  else return $field;
}
add_filter('acf/load_field/name=product_variations', 'product_variations_field_control');


// Dashboard
function add_dashboard_widgets() {
  wp_add_dashboard_widget(
    'criteria',
    'Criteria Welcome',
    'criteria_function'
);
}
add_action('wp_dashboard_setup', 'add_dashboard_widgets');

function criteria_function() {
  echo "";
}

function remove_dashboard_meta() {
  remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal');
  remove_meta_box('dashboard_plugins', 'dashboard', 'normal');
  remove_meta_box('dashboard_primary', 'dashboard', 'side');
  remove_meta_box('dashboard_secondary', 'dashboard', 'normal');
  remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
  remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');
  remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
  remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
  remove_meta_box('dashboard_activity', 'dashboard', 'normal');
}
add_action('admin_init', 'remove_dashboard_meta');

// Remove tabs from admin
function remove_tabs() {
  if (!current_user_can('update_core')) {
    remove_menu_page('index.php');
    remove_menu_page('users.php');
    remove_menu_page('profile.php');
    remove_menu_page('tools.php');
  }
  remove_menu_page('edit.php');
  remove_menu_page('edit.php?post_type=page');
  remove_menu_page('edit-comments.php');
}
add_action('admin_menu', 'remove_tabs');

// Add Options Pages
if (function_exists('acf_add_options_page')) {
	acf_add_options_page(
		array(
			'page_title' => 'Shipping',
      'position' => 25
		)
	);
  acf_add_options_page(
    array(
      'page_title' => 'About',
      'position' => 26
    )
  );
}

// Hide admin bar
add_filter('show_admin_bar', '__return_false');

// Custom CSS on Login Page
function my_login_stylesheet() {
  wp_enqueue_style('custom-login', get_template_directory_uri() . '/admin.css');
}
add_action('login_enqueue_scripts', 'my_login_stylesheet');
add_action('admin_head', 'my_login_stylesheet');

// Hide admin notice for non admins
function hide_update_notice_to_all_but_admin_users() {
  if (!current_user_can('update_core')) {
    remove_action('admin_notices', 'update_nag', 3);
  }
}
add_action('admin_notices', 'hide_update_notice_to_all_but_admin_users', 1);

// Simplify ACF content editor
function my_toolbars($toolbars) {
  $toolbars['Very Simple'] = array();
  $toolbars['Very Simple'][1] = array('italic,link,unlink,spellchecker,bullist');

  if (($key = array_search('code', $toolbars['Full'][2])) !== false) {
    unset($toolbars['Full'][2][$key]);
  }

  unset($toolbars['Basic']);

  return $toolbars;
}
add_filter('acf/fields/wysiwyg/toolbars' , 'my_toolbars');

// Simplify content editor
function my_format_TinyMCE($in) {
  $in['keep_styles'] = false;
  $in['paste_remove_styles'] = true;
  $in['paste_remove_spans'] = true;
  $in['paste_as_text'] = true;
  $in['toolbar1'] = 'bold,italic,strikethrough,link,unlink,spellchecker ';
  return $in;
}
add_filter('tiny_mce_before_init', 'my_format_TinyMCE');

// Remove media buttons
// function RemoveAddMediaButtonsForNonAdmins(){
//   remove_action('media_buttons', 'media_buttons');
// }
// add_action('admin_head', 'RemoveAddMediaButtonsForNonAdmins');
?>
