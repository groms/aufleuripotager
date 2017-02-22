<?php
/*
  $Id: application_top.php,v 1.162 2003/07/12 09:39:03 hpdl Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 osCommerce

  Released under the GNU General Public License
*/
  if (!empty($PHP_AUTH_USER)) {
    switch ($PHP_AUTH_USER) {
      case "trebara":
        $m_id_filter = 10;
        break;
      case "aulne":
        $m_id_filter = 33;
        break;
      case "chesnaie":
        $m_id_filter = 32;
        break;
      case "jolivel":
        $m_id_filter = 38;
        break;
      case "fred":
        $m_id_filter = 55;
        break;
      case "lacaprarius":
        $m_id_filter = 11;
        break;
      case "trocadero":
        $m_id_filter = 36;
        break;
      case "vergermotte":
        $m_id_filter = 50;
        break;
      case "guerillon":
        $m_id_filter = 52;
        break;
      case "sante":
        $m_id_filter = 54;
        break;
      case "lebras":
        $m_id_filter = 49;
        break;
      case "grigonnais":
        $m_id_filter = 53;
        break;
      case "etienneviande":
        $m_id_filter = 51;
        break;
      case "dosdane":
        $m_id_filter = 56;
        break;
      case "berre":
        $m_id_filter = 57;
        break;
      case "lapinais":
        $m_id_filter = 59;
        break;
      case "nevoux-renaud":
        $m_id_filter = 60;
        break;
      case "boute-fraux":
        $m_id_filter = 61;
        break;
      case "maud":
      case "philippe":
      default:
        $m_id_filter = -1;
        break;
    }

  }

  if (file_exists('../includes/configure_paths.php')) {
    require('../includes/configure_paths.php');
  } else {
    require('../../includes/configure_paths.php');
  }

// Set the local configuration parameters - mainly for developers
  if (file_exists($admin_FS_path . 'includes/local/configure.php')) include($admin_FS_path . 'includes/local/configure.php');

// Include application configuration parameters
//  echo $admin_FS_path;
  require($admin_FS_path . 'includes/configure.php');

// define our general functions used application-wide
  require($admin_FS_path . DIR_WS_FUNCTIONS . 'general.php');
  require($admin_FS_path . DIR_WS_FUNCTIONS . 'html_output.php');

// include the list of project filenames
  require($admin_FS_path . DIR_WS_INCLUDES . 'filenames.php');

  $current_page = basename($PHP_SELF);
  if ($current_page != FILENAME_STATS_MANUFACTURERS) {
    if ((!empty($m_id_filter))&&($m_id_filter>0)) {
      reloadSMS($m_id_filter);
    }
  }


// Start the clock for the page parse time log
  define('PAGE_PARSE_START_TIME', microtime());

// Set the level of error reporting
  error_reporting(E_ALL & ~E_NOTICE);

// Check if register_globals is enabled.
// Since this is a temporary measure this message is hardcoded. The requirement will be removed before 2.2 is finalized.
  if (function_exists('ini_get')) {
    ini_get('register_globals') or exit('Server Requirement Error: register_globals is disabled in your PHP configuration. This can be enabled in your php.ini configuration file or in the .htaccess file in your catalog directory.');
  }

// Define the project version
  define('PROJECT_VERSION', 'osCommerce 2.2-MS2');

// set php_self in the local scope
  $PHP_SELF = (isset($HTTP_SERVER_VARS['PHP_SELF']) ? $HTTP_SERVER_VARS['PHP_SELF'] : $HTTP_SERVER_VARS['SCRIPT_NAME']);

// Used in the "Backup Manager" to compress backups
  define('LOCAL_EXE_GZIP', '/usr/bin/gzip');
  define('LOCAL_EXE_GUNZIP', '/usr/bin/gunzip');
  define('LOCAL_EXE_ZIP', '/usr/local/bin/zip');
  define('LOCAL_EXE_UNZIP', '/usr/local/bin/unzip');

// include the list of project database tables
  require($admin_FS_path . DIR_WS_INCLUDES . 'database_tables.php');

// customization for the design layout
  define('BOX_WIDTH', 125); // how wide the boxes should be in pixels (default: 125)

// Define how do we update currency exchange rates
// Possible values are 'oanda' 'xe' or ''
  define('CURRENCY_SERVER_PRIMARY', 'oanda');
  define('CURRENCY_SERVER_BACKUP', 'xe');

// include the database functions
  require($admin_FS_path . DIR_WS_FUNCTIONS . 'database.php');

// make a connection to the database... now
  tep_db_connect() or die('Unable to connect to database server!');

// set application wide parameters
  $configuration_query = tep_db_query('select configuration_key as cfgKey, configuration_value as cfgValue from ' . TABLE_CONFIGURATION);
  while ($configuration = tep_db_fetch_array($configuration_query)) {
    define($configuration['cfgKey'], $configuration['cfgValue']);
  }

  if ((SITE_AVAILABLE != 'true')&&(SITE_AVAILABLE != 'admin_only')) {
    tep_redirect(tep_href_link('../message.php', 'msgtype=site_not_available', 'SSL'));
  }

  $defaultAuthorizedWeights_array = explode("-", DEFAULT_AUTHORIZED_WEIGHTS);
//  $defaultAuthorizedWeights_array = array(0.25, 0.5, 1.0, 1.5, 2.0, 2.5, 3.0, 5.0, 6.0, 7.5, 10.0, 12.0, 15.0, 20.0);

// initialize the logger class
  require($admin_FS_path . DIR_WS_CLASSES . 'logger.php');

// include shopping cart class
  require($admin_FS_path . DIR_WS_CLASSES . 'shopping_cart.php');

// some code to solve compatibility issues
  require($admin_FS_path . DIR_WS_FUNCTIONS . 'compatibility.php');

// check to see if php implemented session management functions - if not, include php3/php4 compatible session class
  if (!function_exists('session_start')) {
    define('PHP_SESSION_NAME', 'osCAdminID');
    define('PHP_SESSION_PATH', '/');
    define('PHP_SESSION_SAVE_PATH', SESSION_WRITE_DIRECTORY);

    include($admin_FS_path . DIR_WS_CLASSES . 'sessions.php');
  }

// define how the session functions will be used
  require($admin_FS_path . DIR_WS_FUNCTIONS . 'sessions.php');

// set the session name and save path
  tep_session_name('osCAdminID');
  tep_session_save_path(SESSION_WRITE_DIRECTORY);

// set the session cookie parameters
   if (function_exists('session_set_cookie_params')) {
    session_set_cookie_params(0, DIR_WS_ADMIN);
  } elseif (function_exists('ini_set')) {
    ini_set('session.cookie_lifetime', '0');
    ini_set('session.cookie_path', DIR_WS_ADMIN);
  }

// lets start our session
  tep_session_start();

// set the language
  if (!tep_session_is_registered('language') || isset($HTTP_GET_VARS['language'])) {
    if (!tep_session_is_registered('language')) {
      tep_session_register('language');
      tep_session_register('languages_id');
    }

    include($admin_FS_path . DIR_WS_CLASSES . 'language.php');
    $lng = new language();

    if (isset($HTTP_GET_VARS['language']) && tep_not_null($HTTP_GET_VARS['language'])) {
      $lng->set_language($HTTP_GET_VARS['language']);
    } else {
      $lng->get_browser_language();
    }

    $language = $lng->language['directory'];
    $languages_id = $lng->language['id'];
  }

// include the language translations
  require($admin_FS_path . DIR_WS_LANGUAGES . $language . '.php');
  if (file_exists($admin_FS_path . DIR_WS_LANGUAGES . $language . '/' . $current_page)) {
    include($admin_FS_path . DIR_WS_LANGUAGES . $language . '/' . $current_page);
  }

// define our localization functions
  require($admin_FS_path . DIR_WS_FUNCTIONS . 'localization.php');

// Include validation functions (right now only email address)
  require($admin_FS_path . DIR_WS_FUNCTIONS . 'validations.php');

// setup our boxes
  require($admin_FS_path . DIR_WS_CLASSES . 'table_block.php');
  require($admin_FS_path . DIR_WS_CLASSES . 'box.php');

// initialize the message stack for output messages
  require($admin_FS_path . DIR_WS_CLASSES . 'message_stack.php');
  $messageStack = new messageStack;

// split-page-results
  require($admin_FS_path . DIR_WS_CLASSES . 'split_page_results.php');

// entry/item info classes
  require($admin_FS_path . DIR_WS_CLASSES . 'object_info.php');

// email classes
  require($admin_FS_path . DIR_WS_CLASSES . 'mime.php');
  require($admin_FS_path . DIR_WS_CLASSES . 'email.php');

// file uploading class
  require($admin_FS_path . DIR_WS_CLASSES . 'upload.php');

// calculate category path
  if (isset($HTTP_GET_VARS['cPath'])) {
    $cPath = $HTTP_GET_VARS['cPath'];
  } else {
    $cPath = '';
  }

  if (tep_not_null($cPath)) {
    $cPath_array = tep_parse_category_path($cPath);
    $cPath = implode('_', $cPath_array);
    $current_category_id = $cPath_array[(sizeof($cPath_array)-1)];
  } else {
    $current_category_id = 0;
  }

// default open navigation box
  if (!tep_session_is_registered('selected_box')) {
    tep_session_register('selected_box');
    $selected_box = 'configuration';
  }

  if (isset($HTTP_GET_VARS['selected_box'])) {
    $selected_box = $HTTP_GET_VARS['selected_box'];
  }

// the following cache blocks are used in the Tools->Cache section
// ('language' in the filename is automatically replaced by available languages)
  $cache_blocks = array(array('title' => TEXT_CACHE_CATEGORIES, 'code' => 'categories', 'file' => 'categories_box-language.cache', 'multiple' => true),
                        array('title' => TEXT_CACHE_MANUFACTURERS, 'code' => 'manufacturers', 'file' => 'manufacturers_box-language.cache', 'multiple' => true),
                        array('title' => TEXT_CACHE_ALSO_PURCHASED, 'code' => 'also_purchased', 'file' => 'also_purchased-language.cache', 'multiple' => true)
                       );

// check if a default currency is set
  if (!defined('DEFAULT_CURRENCY')) {
    $messageStack->add(ERROR_NO_DEFAULT_CURRENCY_DEFINED, 'error');
  }

// check if a default language is set
  if (!defined('DEFAULT_LANGUAGE')) {
    $messageStack->add(ERROR_NO_DEFAULT_LANGUAGE_DEFINED, 'error');
  }

  if (function_exists('ini_get') && ((bool)ini_get('file_uploads') == false) ) {
    $messageStack->add(WARNING_FILE_UPLOADS_DISABLED, 'warning');
  }
  
  if (!empty($PHP_AUTH_USER)) {
    if ($m_id_filter>0) $n_txt = " ($m_id_filter)";
    else $n_txt = "";
    $messageStack->add("Connect&eacute; sous le profil '$PHP_AUTH_USER'".$n_txt, 'warning');
  }
  
  
?>
