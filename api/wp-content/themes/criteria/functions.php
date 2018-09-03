<?php
if (isset($_SERVER['HTTP_ORIGIN'])) {
  header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
  header('Access-Control-Allow-Credentials: true');
}

require_once(TEMPLATEPATH . '/lib/utils.php');
require_once(TEMPLATEPATH . '/lib/theme.php');
require_once(TEMPLATEPATH . '/lib/admin.php');
require_once(TEMPLATEPATH . '/routes/about.php');
require_once(TEMPLATEPATH . '/routes/catalogue.php');
require_once(TEMPLATEPATH . '/routes/collection.php');
require_once(TEMPLATEPATH . '/routes/admin-collection.php');
require_once(TEMPLATEPATH . '/routes/admin-product.php');
require_once(TEMPLATEPATH . '/routes/collection-dump.php');
require_once(TEMPLATEPATH . '/routes/designer.php');
require_once(TEMPLATEPATH . '/routes/designers.php');
require_once(TEMPLATEPATH . '/routes/edition.php');
require_once(TEMPLATEPATH . '/routes/editions.php');
require_once(TEMPLATEPATH . '/routes/get-enquiry-list.php');
require_once(TEMPLATEPATH . '/routes/home.php');
require_once(TEMPLATEPATH . '/routes/product.php');
require_once(TEMPLATEPATH . '/routes/product-options.php');
require_once(TEMPLATEPATH . '/routes/send-enquiry.php');
require_once(TEMPLATEPATH . '/routes/search.php');
require_once(TEMPLATEPATH . '/routes/update-product-options.php');
?>
