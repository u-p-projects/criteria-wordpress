<?php
function user_information($user) {
  return array(
    'is_auth' => is_user_logged_in($user),
    'is_admin' => is_role_admin($user),
    'role' => $user->roles
  );
}

function is_role_admin($user) {
  return in_array('administrator', $user->roles);
}

function return_asset_url($asset, $size = 'full') {
  if (empty($asset)) {
    return NULL;
  } else {
    $asset = ($size === 'full') ? $asset['url'] : $asset['sizes'][$size];
    return explode('/wp-content/uploads/', $asset)[1];
  }
}

function get_meta($product) {
  $brand = get_field('brand', $product);
  $designer = get_field('designer', $product);

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
    ) : null
  );
}
?>
