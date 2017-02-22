<?php
/*
  $Id: checkout_alternative.php,v 2.00 2004/01/05 23:28:24 hpdl Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 osCommerce

  Released under the GNU General Public License
*/


  require('includes/application_top.php');

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CHECKOUT_ALTERNATIVE);

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_LOGIN);

//added by CJ ===>
// if there is nothing in the customers cart, redirect them to the shopping cart page
  if ($cart->count_contents() < 1) {
    tep_redirect(tep_href_link(FILENAME_SHOPPING_CART));
  }
//added by CJ <===

  if (isset($HTTP_GET_VARS['error'])||isset($HTTP_POST_VARS['error'])) {
  $messageStack->add('create_account', ENTRY_EMAIL_ADDRESS_ERROR);
  $messageStack->add('create_account', ENTRY_STREET_ADDRESS_ERROR);
          echo  "<tr><td>".$messageStack->output('create_account')."</td></tr>";
          echo  "<tr><td>".tep_draw_separator('pixel_trans.gif', '100%', '10')."</td></tr>";

  }
  class productListBox extends tableBox {
    function productListBox($contents) {
      $this->table_parameters = '';
      $this->tableBox($contents, true);
    }
  }

  require(DIR_WS_CLASSES . 'order_total.php');
  $order_total_modules = new order_total;

  require(DIR_WS_CLASSES . 'payment.php');
  $payment_modules = new payment($payment);

  require(DIR_WS_CLASSES . 'order.php');
  $order = new order;

  require(DIR_WS_CLASSES . 'shipping.php');
  $shipping_modules = new shipping;

// redirect the customer to a friendly cookie-must-be-enabled page if cookies are disabled (or the session has not started)
  if ($session_started == false) {
    tep_redirect(tep_href_link(FILENAME_COOKIE_USAGE));
  }
  $error = false;
  if (isset($HTTP_POST_VARS['action']) && ($HTTP_POST_VARS['action'] == 'process')) {

   //START REGISTRATION CODE
    $firstname = tep_db_prepare_input($HTTP_POST_VARS['firstname']);
    $lastname = tep_db_prepare_input($HTTP_POST_VARS['lastname']);
    if (ACCOUNT_DOB == 'true') $dob = tep_db_prepare_input($HTTP_POST_VARS['dob']);
    $email_address = tep_db_prepare_input($HTTP_POST_VARS['email_address']);
    if (ACCOUNT_COMPANY == 'true') $company = tep_db_prepare_input($HTTP_POST_VARS['company']);
    $street_address = tep_db_prepare_input($HTTP_POST_VARS['street_address']);
    if (ACCOUNT_SUBURB == 'true') $suburb = tep_db_prepare_input($HTTP_POST_VARS['suburb']);
    $postcode = tep_db_prepare_input($HTTP_POST_VARS['postcode']);
    $city = tep_db_prepare_input($HTTP_POST_VARS['city']);
    $state = tep_db_prepare_input($HTTP_POST_VARS['state']);
      if (isset($HTTP_POST_VARS['zone_id']))
        $zone_id = tep_db_prepare_input($HTTP_POST_VARS['zone_id']);

    $country = tep_db_prepare_input($HTTP_POST_VARS['country']);
    $telephone = tep_db_prepare_input($HTTP_POST_VARS['telephone']);
    $fax = tep_db_prepare_input($HTTP_POST_VARS['fax']);
    if (isset($HTTP_POST_VARS['newsletter'])) {
      $newsletter = tep_db_prepare_input($HTTP_POST_VARS['newsletter']);
    } else {
      $newsletter = false;
    }
    $password = tep_db_prepare_input($HTTP_POST_VARS['password']);
    $confirmation = tep_db_prepare_input($HTTP_POST_VARS['confirmation']);

    $error = false;

    if (strlen($email_address) < ENTRY_EMAIL_ADDRESS_MIN_LENGTH) {
      $error = true;

      $messageStack->add('create_account', ENTRY_EMAIL_ADDRESS_ERROR);
    } elseif (tep_validate_email($email_address) == false) {
      $error = true;

      $messageStack->add('create_account', ENTRY_EMAIL_ADDRESS_CHECK_ERROR);
    } else {
      $check_email_query = tep_db_query("select count(*) as total from " . TABLE_CUSTOMERS . " where customers_email_address = '" . tep_db_input($email_address) . "'");
      $check_email = tep_db_fetch_array($check_email_query);
      if ($check_email['total'] > 0) {
        $error = true;
        $messageStack->add('create_account', ENTRY_EMAIL_ADDRESS_ERROR_EXISTS);
      }
    }

    if ($error == false) {
      $sql_data_array = array('customers_firstname' => $firstname,
                              'customers_lastname' => $lastname,
                              'customers_email_address' => $email_address,
                              'customers_telephone' => $telephone,
                              'customers_fax' => $fax,
                              'customers_newsletter' => $newsletter,
                              'customers_password' => tep_encrypt_password($password));

      if (ACCOUNT_GENDER == 'true') $sql_data_array['customers_gender'] = $gender;
      if (ACCOUNT_DOB == 'true') $sql_data_array['customers_dob'] = tep_date_raw($dob);

      tep_db_perform(TABLE_CUSTOMERS, $sql_data_array);

      $customer_id = tep_db_insert_id();

      $sql_data_array = array('customers_id' => $customer_id,
                              'entry_firstname' => $firstname,
                              'entry_lastname' => $lastname,
                              'entry_street_address' => $street_address,
                              'entry_postcode' => $postcode,
                              'entry_city' => $city,
                              'entry_country_id' => $country);

      if (ACCOUNT_GENDER == 'true') $sql_data_array['entry_gender'] = $gender;
      if (ACCOUNT_COMPANY == 'true') $sql_data_array['entry_company'] = $company;
      if (ACCOUNT_SUBURB == 'true') $sql_data_array['entry_suburb'] = $suburb;
      if (ACCOUNT_STATE == 'true') {
        if ($zone_id > 0) {
          $sql_data_array['entry_zone_id'] = $zone_id;
          $sql_data_array['entry_state'] = '';
        } else {
          $sql_data_array['entry_zone_id'] = '0';
          $sql_data_array['entry_state'] = $state;
        }
      }

      tep_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array);

      $address_id = tep_db_insert_id();

      tep_db_query("update " . TABLE_CUSTOMERS . " set customers_default_address_id = '" . (int)$address_id . "' where customers_id = '" . (int)$customer_id . "'");

      tep_db_query("insert into " . TABLE_CUSTOMERS_INFO . " (customers_info_id, customers_info_number_of_logons, customers_info_date_account_created) values ('" . (int)$customer_id . "', '0', now())");

      if (SESSION_RECREATE == 'True') {
        tep_session_recreate();
      }

      $customer_first_name = $firstname;
      $customer_default_address_id = $address_id;
      $customer_country_id = $country;
      $customer_zone_id = $zone_id;
      tep_session_register('customer_id');
      tep_session_register('customer_first_name');
      tep_session_register('customer_default_address_id');
      tep_session_register('customer_country_id');
      tep_session_register('customer_zone_id');

    $shipping_address_query = tep_db_query("select address_book_id from " . TABLE_ADDRESS_BOOK . " where customers_id = '" . (int)$customer_id . "'");
    $shipping_address = tep_db_fetch_array($shipping_address_query);
    //print_r($shipping_address);
    //extract($_POST);

    foreach ($_POST as $key => $val) {
              //echo $key.'<br>';
              if (!tep_session_is_registered($key)) tep_session_register($key);
              $$key =$val;
                }
    tep_session_register('billto');
    tep_session_register('sendto');
     $billto = $shipping_address['address_book_id'];
     $sendto = $shipping_address['address_book_id'];
      // restore cart contents
      $cart->restore_contents();

   //END REGISTRATION CODE

   //START DIFFERENT SHIPPING CODE

   if (tep_not_null($HTTP_POST_VARS['ShipFirstName']) && tep_not_null($HTTP_POST_VARS['ShipLastName']) && tep_not_null($HTTP_POST_VARS['ShipAddress'])) {
      $process = true;

      $firstname = tep_db_prepare_input($HTTP_POST_VARS['ShipFirstName']);
      $lastname = tep_db_prepare_input($HTTP_POST_VARS['ShipLastName']);
      $street_address = tep_db_prepare_input($HTTP_POST_VARS['ShipAddress']);
      //if (ACCOUNT_SUBURB == 'true') $suburb = tep_db_prepare_input($HTTP_POST_VARS['shipstate']);
      $postcode = tep_db_prepare_input($HTTP_POST_VARS['shippostcode']);
      $city = tep_db_prepare_input($HTTP_POST_VARS['ShipCity']);
      $country = tep_db_prepare_input($HTTP_POST_VARS['shipcountry']);
      if (ACCOUNT_STATE == 'true') {
        if (isset($HTTP_POST_VARS['zone_id'])) {
          $zone_id = tep_db_prepare_input($HTTP_POST_VARS['zone_id']);
        } else {
          $zone_id = false;
        }
        $state = tep_db_prepare_input($HTTP_POST_VARS['shipstate']);
      }

      if (strlen($firstname) < ENTRY_FIRST_NAME_MIN_LENGTH) {
        $error = true;

        $messageStack->add('checkout_address', ENTRY_FIRST_NAME_ERROR);
      }

      if (strlen($lastname) < ENTRY_LAST_NAME_MIN_LENGTH) {
        $error = true;

        $messageStack->add('checkout_address', ENTRY_LAST_NAME_ERROR);
      }

      if (strlen($street_address) < ENTRY_STREET_ADDRESS_MIN_LENGTH) {
        $error = true;

        $messageStack->add('checkout_address', ENTRY_STREET_ADDRESS_ERROR);
      }

      if (strlen($postcode) < ENTRY_POSTCODE_MIN_LENGTH) {
        $error = true;

        $messageStack->add('checkout_address', ENTRY_POST_CODE_ERROR);
      }

      if (strlen($city) < ENTRY_CITY_MIN_LENGTH) {
        $error = true;

        $messageStack->add('checkout_address', ENTRY_CITY_ERROR);
      }

      if (ACCOUNT_STATE == 'true') {
        $zone_id = 0;
        $check_query = tep_db_query("select count(*) as total from " . TABLE_ZONES . " where zone_country_id = '" . (int)$country . "'");
        $check = tep_db_fetch_array($check_query);
        $entry_state_has_zones = ($check['total'] > 0);
        if ($entry_state_has_zones == true) {
          $zone_query = tep_db_query("select distinct zone_id from " . TABLE_ZONES . " where zone_country_id = '" . (int)$country . "' and (zone_name like '" . tep_db_input($state) . "%' or zone_code like '%" . tep_db_input($state) . "%')");
          if (tep_db_num_rows($zone_query) == 1) {
            $zone = tep_db_fetch_array($zone_query);
            $zone_id = $zone['zone_id'];
          } else {
            $error = true;

            $messageStack->add('checkout_address', ENTRY_STATE_ERROR_SELECT);
          }
        } else {
          if (strlen($state) < ENTRY_STATE_MIN_LENGTH) {
            $error = true;

            $messageStack->add('checkout_address', ENTRY_STATE_ERROR);
          }
        }
      }

      if ( (is_numeric($country) == false) || ($country < 1) ) {
        $error = true;

        $messageStack->add('checkout_address', ENTRY_COUNTRY_ERROR);
      }

      if ($error == false) {
        $sql_data_array = array('customers_id' => $customer_id,
                                'entry_firstname' => $firstname,
                                'entry_lastname' => $lastname,
                                'entry_street_address' => $street_address,
                                'entry_postcode' => $postcode,
                                'entry_city' => $city,
                                'entry_country_id' => $country);

        if (ACCOUNT_GENDER == 'true') $sql_data_array['entry_gender'] = $gender;
        if (ACCOUNT_COMPANY == 'true') $sql_data_array['entry_company'] = $company;
        if (ACCOUNT_SUBURB == 'true') $sql_data_array['entry_suburb'] = $suburb;
        if (ACCOUNT_STATE == 'true') {
          if ($zone_id > 0) {
            $sql_data_array['entry_zone_id'] = $zone_id;
            $sql_data_array['entry_state'] = '';
          } else {
            $sql_data_array['entry_zone_id'] = '0';
            $sql_data_array['entry_state'] = $state;
          }
        }

        if (!tep_session_is_registered('sendto')) tep_session_register('sendto');

        tep_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array);
          tep_session_unregister('sendto');
          tep_session_unregister('billto');
          tep_session_register('sendto');
          tep_session_register('billto');
        $sendto = tep_db_insert_id();
        $billto = $sendto -1;
    }
  }
   //END DIFFERENT SHIPPING CODE

   //START PAYMENT CODE

   $payment_modules->update_status();
    if ( ( is_array($payment_modules->modules) && (sizeof($payment_modules->modules) > 1) && !is_object($$payment) ) || (is_object($$payment) && ($$payment->enabled == false)) ) {
    tep_redirect(tep_href_link(FILENAME_CHECKOUT_ALTERNATIVE, 'error_message=' . urlencode(ERROR_NO_PAYMENT_MODULE_SELECTED), 'SSL'));
   }



   while (list($key, $value) = each($_POST))
   {
          tep_session_register($key);
   }

     if (MODULE_ORDER_TOTAL_INSTALLED) {
         $order_total_modules->process();
      }
   //END PAYMENT CODE

   //START Shiping CODE

         if (!tep_session_is_registered('sendto')) {
           tep_session_register('sendto');
         }

   if (!tep_session_is_registered('shipping')) tep_session_register('shipping');
    $total_weight = $cart->show_weight();
    $total_count = $cart->count_contents();

      if ( (isset($HTTP_POST_VARS['shipping'])) && (strpos($HTTP_POST_VARS['shipping'], '_')) ) {
        $shipping = $HTTP_POST_VARS['shipping'];
        list($module, $method) = explode('_', $shipping);
        if ( is_object($$module) || ($shipping == 'free_free') ) {
          if ($shipping == 'free_free') {
            $quote[0]['methods'][0]['title'] = FREE_SHIPPING_TITLE;
            $quote[0]['methods'][0]['cost'] = '0';
          } else {
            $quote = $shipping_modules->quote($method, $module);
          }
          if (isset($quote['error'])) {
            tep_session_unregister('shipping');
          } else {
            if ( (isset($quote[0]['methods'][0]['title'])) && (isset($quote[0]['methods'][0]['cost'])) ) {
              $shipping = array('id' => $shipping,
                                'title' => (($free_shipping == true) ?  $quote[0]['methods'][0]['title'] : $quote[0]['module'] . ' (' . $quote[0]['methods'][0]['title'] . ')'),
                                'cost' => $quote[0]['methods'][0]['cost']);
            }
          }
        } else {
          tep_session_unregister('shipping');
        }
      }
   //END SHIPING CODE

   //START CONFORMITION CODE
   if (!tep_session_is_registered('payment')) tep_session_register('payment');
   if (isset($HTTP_POST_VARS['payment'])) $payment = $HTTP_POST_VARS['payment'];

   if (!tep_session_is_registered('comments')) tep_session_register('comments');
   if (tep_not_null($HTTP_POST_VARS['comments'])) {
    $comments = tep_db_prepare_input($HTTP_POST_VARS['comments']);
   }
    if (!tep_session_is_registered('customer_country_id')) tep_session_register('customer_country_id');
    if (!tep_session_is_registered('customer_zone_id')) tep_session_register('customer_zone_id');
    $customer_country_id = 0;
    $customer_zone_id = 0;
   //END CONFORMITION CODE
    $order->cart();
    $confirmation = $payment_modules->confirmation();
   //print_r($order);
   //$payment_modules = new payment($payment);

   tep_redirect(tep_href_link(FILENAME_CHECKOUT_CONFIRMATION));
  }
}
  if ($error == true) {
    $messageStack->add('login', TEXT_LOGIN_ERROR);
  }

  $breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_CHECKOUT_ALTERNATIVE, '', 'SSL'));

?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<base href="<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
<link rel="stylesheet" type="text/css" href="stylesheet.css">
<SCRIPT src="checkout_alternative_global.js" type="text/javascript"></SCRIPT>

<script language="javascript"><!--
var selected;
function selectRowEffect(object, buttonSelect) {
  if (!selected) {
    if (document.getElementById) {
      selected = document.getElementById('defaultSelected');
    } else {
      selected = document.all['defaultSelected'];
    }
  }

  if (selected) selected.className = 'moduleRow';
  object.className = 'moduleRowSelected';
  selected = object;

// one button is not an array
  if (document.forms['checkout'].payment[0]) {
    document.forms['checkout'].payment[buttonSelect].checked=true;
  } else {
    document.forms['checkout'].payment.checked=true;
  }
}

function rowOverEffect(object) {
  if (object.className == 'moduleRow') object.className = 'moduleRowOver';
}

function rowOutEffect(object) {
  if (object.className == 'moduleRowOver') object.className = 'moduleRow';
}
//--></script>
<script language="javascript"><!--
var selected;

function selectRowEffect1(object, buttonSelect) {
  if (!selected) {
    if (document.getElementById) {
      selected = document.getElementById('defaultSelected');
    } else {
      selected = document.all['defaultSelected'];
    }
  }

  if (selected) selected.className = 'moduleRow';
  object.className = 'moduleRowSelected';
  selected = object;

// one button is not an array
  if (document.forms['checkout'].shipping[0]) {
    document.forms['checkout'].shipping[buttonSelect].checked=true;
  } else {
    document.forms['checkout'].shipping.checked=true;
  }
}

function rowOverEffect1(object) {
  if (object.className == 'moduleRow') object.className = 'moduleRowOver';
}

function rowOutEffect1(object) {
  if (object.className == 'moduleRowOver') object.className = 'moduleRow';
}

//--></script>
<script LANGUAGE="javascript" SRC="calendar.js"></script>
<?php require('includes/form_check.js.php'); ?>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" onload="javascripst:document.getElementById('test').innerHTML = unescape(fetch_data());">
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<!-- body //-->
<table border="0" width="780" cellspacing="0" cellpadding="0" align="center">
  <tr>
    <td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">
<!-- left_navigation //-->
<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>
<!-- left_navigation_eof //-->
    </table></td>
<!-- body_text //-->
     <td width="100%" valign="top"><?php echo tep_draw_form('checkout', tep_href_link(FILENAME_CHECKOUT_ALTERNATIVE, '', 'SSL'), 'post','onSubmit="return check_form(checkout);"') . tep_draw_hidden_field('action', 'process'); ?><table border="0" width="100%" cellspacing="0" cellpadding="0">
        <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLES; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_image(DIR_WS_IMAGES . 'table_background_login.gif', HEADING_TITLES, HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>

<?php
  if ($messageStack->size('login') > 0) {

?>
      <tr>
        <td><?php echo $messageStack->output('login'); ?></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
<?php
  }
  if ($cart->count_contents() > 0) {
?>


<?php
  }
?>

      <tr>
            <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>

<tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><b><?php echo TITLE_FORM; ?></b></td>
          </tr>
        </table></td>
      </tr>
           <tr>
                <td width="100%" class="main"><?php echo PRIMARY_ADDRESS_DESCRIPTION; ?></td>
           </tr>
           <tr>


 <?php
  ///*********************************** PRODUCTS MODULE START**************************//////
?>
       <tr>

        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">

          <tr class="infoBoxContents">
            <td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="0">

              <tr>
                <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr>
                    <td class="main" colspan="3"><?php echo '<b>' . HEADING_PRODUCTS . '</b> <a href="' . tep_href_link(FILENAME_SHOPPING_CART) . '"><span class="orderEdit">(' . TEXT_EDIT . ')</span></a>'; ?></td>
                  </tr>

      <tr>
        <td>
<?php
require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_SHOPPING_CART);

    $info_box_contents = array();
    $info_box_contents[0][] = array('params' => 'class="productListing-heading"',
                                    'text' => TABLE_HEADING_PRODUCTS);

    $info_box_contents[0][] = array('align' => 'center',
                                    'params' => 'class="productListing-heading"',
                                    'text' => TABLE_HEADING_QUANTITY);

    $info_box_contents[0][] = array('align' => 'right',
                                    'params' => 'class="productListing-heading"',
                                    'text' => TABLE_HEADING_TOTAL);

    $any_out_of_stock = 0;
    $products = $cart->get_products();
    for ($i=0, $n=sizeof($products); $i<$n; $i++) {
// Push all attributes information in an array
      if (isset($products[$i]['attributes']) && is_array($products[$i]['attributes'])) {
        while (list($option, $value) = each($products[$i]['attributes'])) {
          echo tep_draw_hidden_field('id[' . $products[$i]['id'] . '][' . $option . ']', $value);
          $attributes = tep_db_query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix
                                      from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                      where pa.products_id = '" . $products[$i]['id'] . "'
                                       and pa.options_id = '" . $option . "'
                                       and pa.options_id = popt.products_options_id
                                       and pa.options_values_id = '" . $value . "'
                                       and pa.options_values_id = poval.products_options_values_id
                                       and popt.language_id = '" . $languages_id . "'
                                       and poval.language_id = '" . $languages_id . "'");
          $attributes_values = tep_db_fetch_array($attributes);

          $products[$i][$option]['products_options_name'] = $attributes_values['products_options_name'];
          $products[$i][$option]['options_values_id'] = $value;
          $products[$i][$option]['products_options_values_name'] = $attributes_values['products_options_values_name'];
          $products[$i][$option]['options_values_price'] = $attributes_values['options_values_price'];
          $products[$i][$option]['price_prefix'] = $attributes_values['price_prefix'];
        }
      }
    }

    for ($i=0, $n=sizeof($products); $i<$n; $i++) {
      if (($i/2) == floor($i/2)) {
        $info_box_contents[] = array('params' => 'class="productListing-even"');
      } else {
        $info_box_contents[] = array('params' => 'class="productListing-odd"');
      }

      $cur_row = sizeof($info_box_contents) - 1;

     $products_name = '<table border="0" cellspacing="2" cellpadding="2">' .
                       '  <tr>' .
                       '    <td class="productListing-data" align="center"><a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $products[$i]['id']) . '">' . tep_image(DIR_WS_CATALOG_IMAGES . $products[$i]['image'], $products[$i]['name'], SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT) . '</a></td>' .
                       '    <td class="productListing-data" valign="center"><a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $products[$i]['id']) . '"><b>' . $products[$i]['name'] . '</b></a>';

      if (STOCK_CHECK == 'true') {
        $stock_check = tep_check_stock($products[$i]['id'], $products[$i]['quantity']);
        if (tep_not_null($stock_check)) {
          $any_out_of_stock = 1;

          $products_name .= $stock_check;
        }
      }

      if (isset($products[$i]['attributes']) && is_array($products[$i]['attributes'])) {
        reset($products[$i]['attributes']);
        while (list($option, $value) = each($products[$i]['attributes'])) {
          $products_name .= '<br><small><i> - ' . $products[$i][$option]['products_options_name'] . ' ' . $products[$i][$option]['products_options_values_name'] . '</i></small>';
        }
      }

      $products_name .= '    </td>' .
                        '  </tr>' .
                        '</table>';

      $info_box_contents[$cur_row][] = array('params' => 'class="productListing-data" valign="center"',
                                             'text' => $products_name);

      $info_box_contents[$cur_row][] = array('align' => 'center',
                                             'params' => 'class="productListing-data" valign="center"',
                                             'text' => tep_draw_input_field('cart_quantity[]', $products[$i]['quantity'], 'size="4" disabled="true"') . tep_draw_hidden_field('products_id[]', $products[$i]['id']));

      $info_box_contents[$cur_row][] = array('align' => 'right',
                                             'params' => 'class="productListing-data" valign="center"',
                                             'text' => '<b>' . $currencies->display_price($products[$i]['final_price'], tep_get_tax_rate($products[$i]['tax_class_id']), $products[$i]['quantity']) . '</b>');
    }

    new productListBox($info_box_contents);
?>

        <tr>
                 <td width="100%"  class="infoBoxContents" align="right"><b><font class="infoBoxContents"><?php echo TITLE_TOTAL; ?></font> <? echo  $currencies->format($cart->show_total());?></b></td>

            </table></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>


 <?php
  ///*********************************** PRODUCTS MODULE END**************************//////
?>

<?
// deleted by CJ : SHIPPING MODULE & PAYMENT MODULE !
// see checkout_alternative.sav.php for previous code
?>

<?php
  ///*********************************** CUSTOMERS MODULE START**************************//////
?>
       <table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><br><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><b><?php echo TITLE_PAYMENT_ADDRESS; ?></b></td>
           <td class="inputRequirement" align="right"><?php echo FORM_REQUIRED_INFORMATION; ?></td>
          </tr>

        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" cellspacing="2" cellpadding="2" align="right">
        <tr>
               <td class = "infoBoxContents"><?php echo PAYMENT_SHIPMENT; ?></td>
               <td class = "infoBoxContents"><input type="image"  src="images/collapse_tcat.gif" name="row" value="1" onclick="return collapse();"></td>
                    </tr>
                    </table></td>
          </tr>
        </table></td>
      </tr>

      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" cellspacing="2" cellpadding="2">
        <tr>
              <td class="infoBoxContents"><?php echo ENTRY_FIRST_NAME; ?></td>
              <td class="infoBoxContents"><?php echo tep_draw_input_field('firstname','','') . '&nbsp;' . (tep_not_null(ENTRY_FIRST_NAME_TEXT) ? '<span class="inputRequirement">' . ENTRY_FIRST_NAME_TEXT . '</span>': ''); ?></td>
        <tr>
              <td class="infoBoxContents"><?php echo ENTRY_LAST_NAME; ?></td>
              <td class="infoBoxContents"><?php echo tep_draw_input_field('lastname','','') . '&nbsp;' . (tep_not_null(ENTRY_LAST_NAME_TEXT) ? '<span class="inputRequirement">' . ENTRY_LAST_NAME_TEXT . '</span>': ''); ?></td>
        <tr>
              <td class="infoBoxContents"><?php echo ENTRY_STREET_ADDRESS; ?></td>
              <td class="infoBoxContents"><?php echo tep_draw_input_field('street_address','','') . '&nbsp;' . (tep_not_null(ENTRY_STREET_ADDRESS_TEXT) ? '<span class="inputRequirement">' . ENTRY_STREET_ADDRESS_TEXT . '</span>': ''); ?></td>
        <tr>
                <td class="infoBoxContents"><?php echo ENTRY_POST_CODE; ?></td>
                <td class="infoBoxContents"><?php echo tep_draw_input_field('postcode') . '&nbsp;' . (tep_not_null(ENTRY_POST_CODE_TEXT) ? '<span class="inputRequirement">' . ENTRY_POST_CODE_TEXT . '</span>': ''); ?></td>
              </tr>
        <tr>
              <td class="infoBoxContents"><?php echo ENTRY_CITY; ?></td>
              <td class="infoBoxContents"><?php echo tep_draw_input_field('city','','') . '&nbsp;' . (tep_not_null(ENTRY_CITY_TEXT) ? '<span class="inputRequirement">' . ENTRY_CITY_TEXT . '</span>': ''); ?></td>

        <tr>
                <td class="infoBoxContents"><?php echo ENTRY_COUNTRY; ?></td>
                <td class="infoBoxContents"><?php echo tep_get_country_list('country') . '&nbsp;' . (tep_not_null(ENTRY_COUNTRY_TEXT) ? '<span class="inputRequirement">' . ENTRY_COUNTRY_TEXT . '</span>': ''); ?></td>
        <tr>
              <td class="infoBoxContents"><?php echo ENTRY_TELEPHONE_NUMBER; ?></td>
              <td class="infoBoxContents"><?php echo tep_draw_input_field('telephone','','') . '&nbsp;' . (tep_not_null(ENTRY_TELEPHONE_NUMBER_TEXT) ? '<span class="inputRequirement">' . ENTRY_TELEPHONE_NUMBER_TEXT . '</span>': ''); ?></td>
        <tr>
              <td class="infoBoxContents"><?php echo ENTRY_EMAIL_ADDRESS; ?></td>
              <td class="infoBoxContents"><?php echo tep_draw_input_field('email_address') . '&nbsp;' . (tep_not_null(ENTRY_EMAIL_ADDRESS_TEXT) ? '<span class="inputRequirement">' . ENTRY_EMAIL_ADDRESS_TEXT . '</span>': ''); ?></td>
       </tr>
       <tr>
              <td class="main"><?php echo ENTRY_PASSWORD; ?></td>
              <td class="main"><?php echo tep_draw_password_field('password') . '&nbsp;' . (tep_not_null(ENTRY_PASSWORD_TEXT) ? '<span class="inputRequirement">' . ENTRY_PASSWORD_TEXT . '</span>': ''); ?></td>
       </tr>
       <tr>
               <td class="main"><?php echo ENTRY_PASSWORD_CONFIRMATION; ?></td>
               <td class="main"><?php echo tep_draw_password_field('confirmation') . '&nbsp;' . (tep_not_null(ENTRY_PASSWORD_CONFIRMATION_TEXT) ? '<span class="inputRequirement">' . ENTRY_PASSWORD_CONFIRMATION_TEXT . '</span>': ''); ?></td>
       </tr>
              </table></td>
              </tr>
            </table></td>
          </tr>



                 <tr>
        <td><table border="0" width="100%" cellspacing="2" cellpadding="2">
                    <TBODY id=collapseobj_forumbit_1>
                      <TD class=alt2 noWrap>
                      <table border="0" width="100%" cellspacing="1" cellpadding="2">
                          <TBODY>
                               <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><b><?php echo TITLE_SHIPPING_ADDRESS; ?></b></td>
          </tr>
        </table></td>
      </tr>

       <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" width="100%" cellspacing="0" cellpadding="2">


       <tr>
               <td class="infoBoxContents"><?php echo ENTRY_FIRST_NAME; ?></td>
               <td class = "infoBoxContents"><input type="text" name="ShipFirstName" value="<? echo $FirstName; ?>" size="20"></td>
       <tr>
               <td class="infoBoxContents"><?php echo ENTRY_LAST_NAME; ?></td>
               <td class = "infoBoxContents"><input name="ShipLastName" value="<? echo $LastName; ?>" size="20"></td>
       <tr>
               <td class="infoBoxContents"><?php echo ENTRY_STREET_ADDRESS; ?></td>
               <td class = "infoBoxContents"><tt><font size="2"><input name="ShipAddress" value="<? echo $ShipAddress; ?>" size="20"></font></tt></td>
       <tr>
                <td class="infoBoxContents"><?php echo ENTRY_POST_CODE; ?></td>
                <td class="infoBoxContents"><?php echo tep_draw_input_field('shippostcode') ; ?></td>
              </tr>
       <tr>
               <td class="infoBoxContents"><?php echo ENTRY_CITY; ?></td>
               <td class = "infoBoxContents"><INPUT TYPE="text" NAME="ShipCity" VALUE="<? echo $City; ?>"></td>
       <tr>
                <td class="infoBoxContents"><?php echo ENTRY_COUNTRY; ?></td>
                <td class="infoBoxContents"><?php echo tep_get_country_list('shipcountry') . '&nbsp;' . (tep_not_null(ENTRY_COUNTRY_TEXT) ? '<span class="inputRequirement">' . ENTRY_COUNTRY_TEXT . '</span>': ''); ?></td>
       <tr>
               <td class="infoBoxContents"><?php echo ENTRY_TELEPHONE_NUMBER; ?></td>
               <td class="infoBoxContents"><?php echo tep_draw_input_field('shiptelephone') . '&nbsp;' . (tep_not_null(ENTRY_TELEPHONE_NUMBER_TEXT) ? '<span class="inputRequirement">' . ENTRY_TELEPHONE_NUMBER_TEXT . '</span>': ''); ?></td>
       </tr>
                </table></td>
              </tr>
            </table></td>
          </tr>
      </td></tr>
  </TBODY> </table></td>
              </tr>
            </table></td>
          </tr>

 <table border="0" width="100%" cellspacing="0" cellpadding="2" valign="top">
<?php
          if (basename($HTTP_REFERER)) {
?>
           <td width="20%" valign="top"><center>   <?php
           echo '<a href="' . basename($HTTP_REFERER) . '">' . tep_image_button('button_continue_shopping.gif', IMAGE_BUTTON_CONTINUE_SHOPPING) . '</a>'; ?>
<?php
            }
?>
       </td><td width="100%" border="1">&nbsp;</td><td width="100%" border="1" valign="top"><center>
<?php

  if (isset($$payment->form_action_url)) {
    $form_action_url = $$payment->form_action_url;
  } else {
    $form_action_url = tep_href_link(FILENAME_CHECKOUT_PROCESS_ALTERNATIVE, '', 'SSL');
  }




  echo tep_image_submit('button_confirm_order.gif', IMAGE_BUTTON_CONFIRM_ORDER) . '</form></td>';
?>
</tr>
</table>

<br> <!-- added by CJ -->
<!-- body_text_eof //-->

<!-- body_eof //-->

<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->

</body>
<? // script added by CJ ?>
<script>
  collapse(); // on load
  function collapse() {
    return toggle_collapse('forumbit_1');    
  }
</script>
</html>

<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
