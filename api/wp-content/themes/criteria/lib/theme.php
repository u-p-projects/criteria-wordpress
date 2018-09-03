<?php
// Hide all routes from non-logged in users
add_filter('rest_authentication_errors', function( $result ) {
	if (!is_user_logged_in()) {
		return new WP_Error('restx_logged_out', 'Sorry, you must be logged in to make a request.', array('status' => 401));
	}
	return $result;
});

// Add theme support
add_theme_support('post-thumbnails');


// Mange Options button on Collection
function product_options_meta_box() {
	function add_url() {
		global $post;
		$post_status = get_post_status($post->ID);
		if ($post_status !== 'auto-draft') {
			echo '<a onclick="return confirm(\'Leave page? All unsaved changes will be lost.\')"' . 'class="acf-button button button-primary" href="' . str_replace('/api', '', get_bloginfo('url')) . '/collection/' . $post->post_name . '/product-options' . '">Manage Product Options</a>';
		} else {
			echo 'Product must be saved first to add options.';
		}
	}

	add_meta_box(
		'global-notice',
		'Options',
		'add_url',
		'collection',
		'side',
		'high'
	);
}
add_action('add_meta_boxes', 'product_options_meta_box');

?>
