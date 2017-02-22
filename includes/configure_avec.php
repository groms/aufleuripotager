<?php

  require_once('configure_paths.php');

// define our database connection
  define('DB_SERVER', 'localhost'); // eg, localhost - should not be empty for productive servers

if ($host == "aufleuripotager.free.fr/") {
  define('DB_SERVER_USERNAME', 'aufleuripotager');
  define('DB_SERVER_PASSWORD', 'tLcVyDAh');
  define('DB_DATABASE', 'aufleuripotager');
}
else {
  // host = localhost
  define('DB_SERVER_USERNAME', 'aufleuripotager');
  define('DB_SERVER_PASSWORD', 'aufleuripotager');
  define('DB_DATABASE', 'aufleuripotager');
}

  define('USE_PCONNECT', 'false'); // use persistent connections?
  define('STORE_SESSIONS', 'mysql'); // leave empty '' for default handler or set to 'mysql'

// Define the webserver and path parameters
  define('HTTP_SERVER', $host_url); // eg, http://localhost - should not be empty for productive servers
  define('DIR_WS_IMAGES', 'images/');

  // relative path required (HTTP paths)
  define('DIR_WS_CATALOG', $subpath); 
  define('DIR_WS_CATALOG_IMAGES', HTTP_SERVER . DIR_WS_CATALOG . 'images/');// absolute path required
  define('DIR_WS_CATALOG_LANGUAGES', HTTP_SERVER . DIR_WS_CATALOG . 'includes/languages/');

  define('DIR_WS_ICONS', DIR_WS_IMAGES . 'icons/');
  define('DIR_WS_INCLUDES', 'includes/');
  define('DIR_WS_BOXES', DIR_WS_INCLUDES . 'boxes/');
  define('DIR_WS_FUNCTIONS', DIR_WS_INCLUDES . 'functions/');
  define('DIR_WS_CLASSES', DIR_WS_INCLUDES . 'classes/');
  define('DIR_WS_MODULES', DIR_WS_INCLUDES . 'modules/');
  define('DIR_WS_LANGUAGES', DIR_WS_INCLUDES . 'languages/');

  // rabsolute path required (server dir paths)
  define('DIR_FS_DOCUMENT_ROOT', $doc_root);
  define('DIR_FS_CATALOG', $doc_root . $subpath);
  define('DIR_FS_CATALOG_IMAGES', DIR_FS_CATALOG . 'images/');   
  
?>
