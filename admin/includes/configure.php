<?
/*
  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 osCommerce

  Released under the GNU General Public License
*/

  if (file_exists('../includes/configure_avec.php')) {
    require "../includes/configure_avec.php";
  } else {
    require "../../includes/configure_avec.php";
  }

// Define the webserver and path parameters
// * DIR_FS_* = Filesystem directories (local/physical)
// * DIR_WS_* = Webserver directories (virtual/URL)

  define('HTTP_CATALOG_SERVER', $host_url);
  define('HTTPS_CATALOG_SERVER', '');
  define('ENABLE_SSL_CATALOG', 'false'); // secure webserver for catalog module

  define('DIR_WS_ADMIN', DIR_WS_CATALOG . $admin_subpath); // relative path required

  // rabsolute path required (server dir paths)
  define('DIR_FS_ADMIN', DIR_FS_CATALOG . $admin_subpath);
  define('DIR_FS_CATALOG_LANGUAGES', DIR_FS_CATALOG . 'includes/languages/');
  define('DIR_FS_CATALOG_MODULES', DIR_FS_CATALOG . 'includes/modules/');
  define('DIR_FS_BACKUP', DIR_FS_ADMIN . 'backups/');
?>
