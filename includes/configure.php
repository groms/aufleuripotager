<?php
/*
  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 osCommerce

  Released under the GNU General Public License
*/

  require "configure_avec.php";

// Define the webserver and path parameters
// * DIR_FS_* = Filesystem directories (local/physical)
// * DIR_WS_* = Webserver directories (virtual/URL)
  define('HTTPS_SERVER', ''); // eg, https://localhost - should not be empty for productive servers
  define('ENABLE_SSL', false); // secure webserver for checkout procedure?

  define('HTTP_COOKIE_DOMAIN', $host);
  define('HTTPS_COOKIE_DOMAIN', '');
  define('HTTP_COOKIE_PATH', $subpath);
  define('HTTPS_COOKIE_PATH', '');

  define('DIR_WS_HTTP_CATALOG', $subpath);
  define('DIR_WS_HTTPS_CATALOG', '');
  define('DIR_WS_DOWNLOAD_PUBLIC', 'pub/');

  define('DIR_FS_DOWNLOAD', DIR_FS_CATALOG . 'download/');
  define('DIR_FS_DOWNLOAD_PUBLIC', DIR_FS_CATALOG . 'pub/');

?>
