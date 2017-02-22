<?php
/*
  $Id: checkout_process.php,v 1.128 2003/05/28 18:00:29 hpdl Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 osCommerce

  Released under the GNU General Public License
*/
  
  // adminMode = yes          <=>   stats_manufacturers_sales 
  // adminMode = addproduct   <=>   account_history_info 
  // adminMode not set        <=>   normal order 

  $super_user_mode = false;
  if (!isset($adminMode)) $adminMode = "";
  if (!isset($new_order_admin_mode)) $new_order_admin_mode = "";
  
  if ($adminMode == "") {
    include('includes/application_top.php');
    // if the customer is not logged on, redirect them to the login page
    if (!tep_session_is_registered('customer_id')) {
      $navigation->set_snapshot(array('mode' => 'SSL', 'page' => FILENAME_CHECKOUT_PAYMENT));
      tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
    }
    
    if (!tep_session_is_registered('sendto')) {
      tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
    }
  
    if ( (tep_not_null(MODULE_PAYMENT_INSTALLED)) && (!tep_session_is_registered('payment')) ) {
      tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
   }
  
    // avoid hack attempts during the checkout procedure by checking the internal cartID
    if (isset($cart->cartID) && tep_session_is_registered('cartID')) {
      if ($cart->cartID != $cartID) {
        tep_redirect(tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
      }
    }

    $customer_id_sav = $customer_id;
    
    if (isset($HTTP_POST_VARS['customers_addproduct']) && (is_numeric($HTTP_POST_VARS['customers_addproduct']))
       && ($HTTP_POST_VARS['customers_addproduct']!='') && ($HTTP_POST_VARS['customers_addproduct']!=$customer_id)) {
      // on est en mode super_user => on a le droit d'attribuer la commande ‡ qq'un d'autre
      $customer_id = $HTTP_POST_VARS['customers_addproduct'];
      $super_user_mode = true;
    }
  
    include($doc_root . $subpath . DIR_WS_LANGUAGES . $language . '/' . FILENAME_CHECKOUT_PROCESS);

    // load the selected shipping module
    require($doc_root . $subpath . DIR_WS_CLASSES . 'shipping.php');
    $shipping_modules = new shipping($shipping);
  }


  if ($adminMode != "addproduct") {
    // load selected payment module
    require($doc_root . $subpath . DIR_WS_CLASSES . 'payment.php');
    $payment_modules = new payment($payment);
  
    require(DIR_WS_CLASSES . 'order.php');
  }

  $order = new order;

  if ($adminMode != "addproduct") {
    // load the before_process function from the payment modules
    if (is_object($payment_modules)) {
      $payment_modules->before_process();
    }
  
    require($doc_root . $subpath . DIR_WS_CLASSES . 'order_total.php');
    $order_total_modules = new order_total;
    if (is_object($order_total_modules)) {
      $order_totals = $order_total_modules->process();
    }
  }

  $date_purchased = 'now()';
  if ($recurrent_order) {    //   $recurrent_order == "on"
    $orders_status = 4; //commande r&eacute;currente
    $recurrent_order = true;
  }
  else {
    $recurrent_order = false;
    if ($new_order_admin_mode != "yes") {
      $orders_status = $order->info['order_status'];
      $group_id = $order->info['group_id'];
    } else {
      $orders_status = 1;
//      $date_purchased = $order_date_to;
//      $group_id = $order->info['group_id'];
    }
  }
  
  if ($adminMode != "addproduct") {
/*
    if ($adminMode == "yes") {
      $sql_data_array = array('customers_id' => $customer_id,
                              'customers_name' => $order->customer['firstname'] . ' ' . $order->customer['lastname'],
                              'customers_company' => $order->customer['company'],
                              'customers_street_address' => $order->customer['street_address'],
                              'customers_suburb' => $order->customer['suburb'],
                              'customers_city' => $order->customer['city'],
                              'customers_postcode' => $order->customer['postcode'], 
                              'customers_state' => $order->customer['state'], 
                              'customers_country' => $order->customer['country']['title'], 
                              'customers_telephone' => $order->customer['telephone'], 
                              'customers_email_address' => $order->customer['email_address'],
                              'customers_address_format_id' => $order->customer['format_id'], 
                              'cc_type' => $order->info['cc_type'], 
                              'cc_owner' => $order->info['cc_owner'], 
                              'cc_number' => $order->info['cc_number'], 
                              'cc_expires' => $order->info['cc_expires'], 
                              'date_purchased' => $date_purchased, 
    //                          'postponed_date_purchased' => $date_purchased, 
                              'orders_status' => $orders_status, 
                              'group_id' => $order->info['group_id'], 
                              'currency' => $order->info['currency'], 
                              'currency_value' => $order->info['currency_value'],
                              'delivery_name' => $order->customer['firstname'] . ' ' . $order->customer['lastname'], 
                              'delivery_company' => $order->customer['company'],
                              'delivery_street_address' => $order->customer['street_address'],
                              'delivery_suburb' => $order->customer['suburb'],
                              'delivery_city' => $order->customer['city'],
                              'delivery_postcode' => $order->customer['postcode'], 
                              'delivery_state' => $order->customer['state'], 
                              'delivery_country' => $order->customer['country']['title'], 
                              'delivery_address_format_id' => $order->customer['format_id'], 
                              'billing_name' => $order->customer['firstname'] . ' ' . $order->customer['lastname'], 
                              'billing_company' => $order->customer['company'],
                              'billing_street_address' => $order->customer['street_address'],
                              'billing_suburb' => $order->customer['suburb'],
                              'billing_city' => $order->customer['city'],
                              'billing_postcode' => $order->customer['postcode'], 
                              'billing_state' => $order->customer['state'], 
                              'billing_country' => $order->customer['country']['title'], 
                              'billing_address_format_id' => $order->customer['format_id'], 
                              'payment_method' => $order->info['payment_method']); 
    } else {
      $sql_data_array = array('customers_id' => $customer_id,
                              'customers_name' => $order->customer['firstname'] . ' ' . $order->customer['lastname'],
                              'customers_company' => $order->customer['company'],
                              'customers_street_address' => $order->customer['street_address'],
                              'customers_suburb' => $order->customer['suburb'],
                              'customers_city' => $order->customer['city'],
                              'customers_postcode' => $order->customer['postcode'], 
                              'customers_state' => $order->customer['state'], 
                              'customers_country' => $order->customer['country']['title'], 
                              'customers_telephone' => $order->customer['telephone'], 
                              'customers_email_address' => $order->customer['email_address'],
                              'customers_address_format_id' => $order->customer['format_id'], 
                              'group_id' => $order->info['group_id'], 
                              'cc_type' => $order->info['cc_type'], 
                              'cc_owner' => $order->info['cc_owner'], 
                              'cc_number' => $order->info['cc_number'], 
                              'cc_expires' => $order->info['cc_expires'], 
                              'date_purchased' => $date_purchased, 
    //                          'postponed_date_purchased' => $date_purchased, 
                              'orders_status' => $orders_status, 
                              'currency' => $order->info['currency'], 
                              'currency_value' => $order->info['currency_value'],
                              'delivery_name' => $order->delivery['firstname'] . ' ' . $order->delivery['lastname'], 
                              'delivery_company' => $order->delivery['company'],
                              'delivery_street_address' => $order->delivery['street_address'], 
                              'delivery_suburb' => $order->delivery['suburb'], 
                              'delivery_city' => $order->delivery['city'], 
                              'delivery_postcode' => $order->delivery['postcode'], 
                              'delivery_state' => $order->delivery['state'], 
                              'delivery_country' => $order->delivery['country']['title'], 
                              'delivery_address_format_id' => $order->delivery['format_id'], 
                              'billing_name' => $order->billing['firstname'] . ' ' . $order->billing['lastname'], 
                              'billing_company' => $order->billing['company'],
                              'billing_street_address' => $order->billing['street_address'], 
                              'billing_suburb' => $order->billing['suburb'], 
                              'billing_city' => $order->billing['city'], 
                              'billing_postcode' => $order->billing['postcode'], 
                              'billing_state' => $order->billing['state'], 
                              'billing_country' => $order->billing['country']['title'], 
                              'billing_address_format_id' => $order->billing['format_id'], 
                              'payment_method' => $order->info['payment_method']); 
    } 
*/

    // maintenant, par d&eacute;faut, billing address = delivery_address = customer_address
    $sql_data_array = array('customers_id' => $customer_id,
                            'customers_name' => $order->customer['firstname'] . ' ' . $order->customer['lastname'],
                            'customers_company' => $order->customer['company'],
                            'customers_street_address' => $order->customer['street_address'],
                            'customers_suburb' => $order->customer['suburb'],
                            'customers_city' => $order->customer['city'],
                            'customers_postcode' => $order->customer['postcode'], 
                            'customers_state' => $order->customer['state'], 
                            'customers_country' => $order->customer['country']['title'], 
                            'customers_telephone' => $order->customer['telephone'], 
                            'customers_email_address' => $order->customer['email_address'],
                            'customers_address_format_id' => $order->customer['format_id'], 
                            'cc_type' => $order->info['cc_type'], 
                            'cc_owner' => $order->info['cc_owner'], 
                            'cc_number' => $order->info['cc_number'], 
                            'cc_expires' => $order->info['cc_expires'], 
                            'date_purchased' => $date_purchased, 
                            'orders_status' => $orders_status, 
                            'group_id' => $group_id, 
                            'currency' => $order->info['currency'], 
                            'currency_value' => $order->info['currency_value'],
                            'delivery_name' => $order->customer['firstname'] . ' ' . $order->customer['lastname'], 
                            'delivery_company' => $order->customer['company'],
                            'delivery_street_address' => $order->customer['street_address'],
                            'delivery_suburb' => $order->customer['suburb'],
                            'delivery_city' => $order->customer['city'],
                            'delivery_postcode' => $order->customer['postcode'], 
                            'delivery_state' => $order->customer['state'], 
                            'delivery_country' => $order->customer['country']['title'], 
                            'delivery_address_format_id' => $order->customer['format_id'], 
                            'billing_name' => $order->customer['firstname'] . ' ' . $order->customer['lastname'], 
                            'billing_company' => $order->customer['company'],
                            'billing_street_address' => $order->customer['street_address'],
                            'billing_suburb' => $order->customer['suburb'],
                            'billing_city' => $order->customer['city'],
                            'billing_postcode' => $order->customer['postcode'], 
                            'billing_state' => $order->customer['state'], 
                            'billing_country' => $order->customer['country']['title'], 
                            'billing_address_format_id' => $order->customer['format_id'], 
                            'payment_method' => $order->info['payment_method']); 
    
    tep_db_perform(TABLE_ORDERS, $sql_data_array);
    $insert_id = tep_db_insert_id();

    for ($i=0, $n=sizeof($order_totals); $i<$n; $i++) {
      $sql_data_array = array('orders_id' => $insert_id,
                              'title' => $order_totals[$i]['title'],
                              'text' => $order_totals[$i]['text'],
                              'value' => $order_totals[$i]['value'], 
                              'class' => $order_totals[$i]['code'], 
                              'sort_order' => $order_totals[$i]['sort_order']);
      tep_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);
    }

    $customer_notification = ((SEND_EMAILS == 'true')&&($new_order_admin_mode != "yes")) ? '1' : '0';
    $orderComment = "";
    if (($new_order_admin_mode == "yes") || ($adminMode == "yes")) {
      $orderComment .= "Commande cr&eacute;&eacute;e par le producteur. ";
    }
    $orderComment .= $order->info['comments'];
    
    $sql_data_array = array('orders_id' => $insert_id, 
                            'orders_status_id' => $orders_status, 
                            'date_added' => 'now()', 
                            'customer_notified' => $customer_notification,
                            'comments' => $orderComment);
    tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
  } else {
    // if adminMode=addproduct  (from account_history_info.php)
    $insert_id = (int)$HTTP_POST_VARS['cur_order_id'];
  }
  
// initialized for the email confirmation
  $products_ordered = '';
  $subtotal = 0;
  $total_tax = 0;
  $manufacturers_emails = array();
  $manufacturers_names = array();
  $manufacturers_ordered_products = array();
//	$cj_order_date = get_order_date();
//	$postponed_date_purchased = "";
//echo sizeof($order->products);

  if ($new_order_admin_mode != "yes") {
    for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
  // r&eacute;cup&eacute;ration du manufacturer
      $date_shipped = "";
      $p_id = tep_get_prid($order->products[$i]['id']);
      $attr = getAttrId($order->products[$i]['attributes'], false);
  
      if (($adminMode != "yes")&&($orderAdminMode != "yes")) {
        if (isset($HTTP_POST_VARS['next_date_shipped'.$p_id.$attr])) $date_shipped = $HTTP_POST_VARS['next_date_shipped'.$p_id.$attr];
      } else {
        // from admin/stats_manufacturers_sales admin/orders.php (si $orderAdminMode == yes)
        $date_shipped = $order_date_to;
      }
  
  //    echo "a".$attr."|".$date_shipped."b";exit;
  
      if ($date_shipped == "") {
        echo tep_error_message(false, "Impossible de r&eacute;cup&eacute;rer la date de livraison pour le produit n∞ $p_id");
        exit;
        //tep_redirect(tep_href_link(FILENAME_CHECKOUT_CONFIRMATION, '', 'SSL'));
      }
      
      $manufacturers_got = false;
  
      $manufacturers_query = tep_db_query("select m.group_id, m.manufacturers_id, m.manufacturers_name, m.last_freezing from " . TABLE_PRODUCTS . " as p LEFT JOIN " . TABLE_MANUFACTURERS . " as m ON m.manufacturers_id = p.manufacturers_id WHERE p.products_id = '" . $order->products[$i]['id'] . "'");
      if ($manufacturers = tep_db_fetch_array($manufacturers_query)) {
        $manufacturers_got = true;
      }
  
      if ($adminMode != "yes") {
        if ($recurrent_order) {
          $shipping_day = $HTTP_POST_VARS['shipping_day'.$p_id.$attr];
          $shipping_frequency = $HTTP_POST_VARS['shipping_frequency'.$p_id.$attr];
          if ($shipping_frequency < 1.0) {
            $shipping_day = 'tuesday|thursday';
          } else {
            $shipping_day = strtolower(date("l", strtotime($date_shipped)));
          }
        } else {
          $shipping_day = strtolower(date("l", strtotime($date_shipped)));
          $shipping_frequency = 1.0;
        }
  
      } else {
        // on ajoute un produit en mode admin
        $shipping_day = strtolower(date("l", strtotime($date_shipped)));
        if ($order->products[$i]['group_id']>0) {
          $shipping_frequency = 4.0;
        } else {
          $shipping_frequency = 1.0;
        } 
      }
  
//      echo $order->products[$i]['qty'];exit;
      if (!isset($recurrent_order_int)) $recurrent_order_int = (int)$recurrent_order; // en mode admin, on g&eacute;nËre '$recurrent_order_int' dans 'shared.php' 
      $sql_data_array = array('orders_id' => $insert_id,
                              'date_shipped' => $date_shipped,
                              'next_date_shipped' => $date_shipped,
                              'manufacturers_id' => $manufacturers['manufacturers_id'],
                              'is_recurrence_order' => $recurrent_order_int,
                              'products_id' => $p_id, 
                              'group_id' => $order->products[$i]['group_id'], 
                              'shipping_day' => $shipping_day, 
                              'shipping_frequency' => $shipping_frequency, 
                              'products_model' => $order->products[$i]['model'], 
                              'products_name' => $order->products[$i]['name'], 
                              'products_price' => $order->products[$i]['price'], 
                              'final_price' => $order->products[$i]['final_price'], 
                              'products_tax' => $order->products[$i]['tax'], 
                              'products_quantity' => $order->products[$i]['qty']);
  
      tep_db_perform(TABLE_ORDERS_PRODUCTS, $sql_data_array);
      $order_products_id = tep_db_insert_id();
  
  //------insert customer choosen option to order--------
      $attributes_exist = '0';
      $products_ordered_attributes = '';
      if (isset($order->products[$i]['attributes'])) {
        $attributes_exist = '1';
        for ($j=0, $n2=sizeof($order->products[$i]['attributes']); $j<$n2; $j++) {
          if (DOWNLOAD_ENABLED == 'true') {
            $attributes_query = "select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix, pad.products_attributes_maxdays, pad.products_attributes_maxcount , pad.products_attributes_filename 
                                 from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa 
                                 left join " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                  on pa.products_attributes_id=pad.products_attributes_id
                                 where pa.products_id = '" . $order->products[$i]['id'] . "' 
                                  and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "' 
                                  and pa.options_id = popt.products_options_id 
                                  and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "' 
                                  and pa.options_values_id = poval.products_options_values_id 
                                  and popt.language_id = '" . $languages_id . "' 
                                  and poval.language_id = '" . $languages_id . "'";
            $attributes = tep_db_query($attributes_query);
          } else {
            $attributes = tep_db_query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa where pa.products_id = '" . $order->products[$i]['id'] . "' and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "' and pa.options_id = popt.products_options_id and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "' and pa.options_values_id = poval.products_options_values_id and popt.language_id = '" . $languages_id . "' and poval.language_id = '" . $languages_id . "'");
          }
          $attributes_values = tep_db_fetch_array($attributes);
  
          $sql_data_array = array('orders_id' => $insert_id, 
                                  'orders_products_id' => $order_products_id, 
                                  'products_options' => $attributes_values['products_options_name'],
                                  'products_options_values' => $attributes_values['products_options_values_name'], 
                                  'options_values_price' => $attributes_values['options_values_price'], 
                                  'price_prefix' => $attributes_values['price_prefix']);
          tep_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array);
  
          if ((DOWNLOAD_ENABLED == 'true') && isset($attributes_values['products_attributes_filename']) && tep_not_null($attributes_values['products_attributes_filename'])) {
            $sql_data_array = array('orders_id' => $insert_id, 
                                    'orders_products_id' => $order_products_id, 
                                    'orders_products_filename' => $attributes_values['products_attributes_filename'], 
                                    'download_maxdays' => $attributes_values['products_attributes_maxdays'], 
                                    'download_count' => $attributes_values['products_attributes_maxcount']);
            tep_db_perform(TABLE_ORDERS_PRODUCTS_DOWNLOAD, $sql_data_array);
          }
          $products_ordered_attributes .= "\n\t" . $attributes_values['products_options_name'] . ' ' . $attributes_values['products_options_values_name'];
        }
      }
  //------insert customer choosen option eof ----
      $total_weight += ($order->products[$i]['qty'] * $order->products[$i]['weight']);
      $total_tax += tep_calculate_tax($total_products_price, $products_tax) * $order->products[$i]['qty'];
      $total_cost += $total_products_price;
  
      $products_ordered .= 
        "<b>".tep_format_qty_for_html($order->products[$i]['qty']) . '</b> x ' . 
        $order->products[$i]['name'] . ' (' . $order->products[$i]['model'] . ') = <b>' . $currencies->display_price($order->products[$i]['final_price'], $order->products[$i]['tax'], tep_format_qty_for_db($order->products[$i]['qty'])) . "</b>" . $products_ordered_attributes . "\n";
  
      $products_ordered .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;=> <i>';
      $products_ordered .= 'date de livraison du produit ';
      if ($recurrent_order) {
        $products_ordered .= '‡ partir du';
      } else {
        $products_ordered .= 'le';
      }
      $products_ordered .= ' <b>' . getFormattedLongDate($date_shipped, true) . '</b>';
      if ($recurrent_order) {
        $products_ordered .= ', ' . convertShippingFrequencyToText_fr($shipping_frequency);
      }
      $products_ordered .= '</i>\n\n';
  
      if (($manufacturers_got) && ($manufacturers['group_id'] == "0")) {
        if ($attributes_values != "") {
          $attributes_values_string = " (" . $attributes_values['products_options_name'] . ": " . $attributes_values['products_options_values_name'] . ")";
        }
        else {
          $attributes_values_string = "";
        }
        
        $manufacturers_ordered_products[$manufacturers['manufacturers_id']] = $manufacturers_ordered_products[$manufacturers['manufacturers_id']]. $order->products[$i]['qty'] . " x " . $order->products[$i]['name'] . $attributes_values_string . "<br>";
  
        $manufacturers_emails[$manufacturers['manufacturers_id']] = $manufacturers['manufacturers_email'];
        $manufacturers_names[$manufacturers['manufacturers_id']] = $manufacturers['manufacturers_name'];
      }
  
  //adding modifications data by CJ - 20081224
      if (!$recurrent_order) {      
        // on ne renseigne la bonne valeur de customers_ga_id uniquement en cas d'un produit du grpt d'achat
        $gaID = getGA_ID($order->products[$i]['group_id'], -1, true);
        $sql_data_array = array('orders_products_modifications_datetime' => $date_purchased,
                                'date_shipped' => $date_shipped,
                                'orders_products_id' => $order_products_id, 
                                'orders_id' => $insert_id, 
                                'customers_id' => $customer_id, 
                                'customers_ga_id' => $gaID, 
                                // is_recurrence_order => 0
                                'manufacturers_id' => $manufacturers['manufacturers_id'],
                                'group_id' => $order->products[$i]['group_id'], 
                                'products_id' => $order->products[$i]['id'], 
                                'products_name' => $order->products[$i]['name'],
                                'products_options' => $attributes_values['products_options_name'],
                                'products_options_values' => $attributes_values['products_options_values_name'],
                                'final_price' => $order->products[$i]['final_price'],
                                'products_quantity' => $order->products[$i]['qty']
  //                              , 'freezing_datetime' => $date_shipped
                                ); 
        tep_db_perform('orders_products_modifications', $sql_data_array);
  
  //mise ‡ jour du stock uniquement si commande non r&eacute;currente !
  //non, finalement, on ne met ‡ jour que lors de la validation des commandes...
  //      tep_update_stock($order->products[$i]['id'], $order->products[$i]['qty'], $order->products[$i]['attributes']);
      }
  
      $attributes_values ="";    
    }
    
    $products_ordered = substr($products_ordered, 0, -2); // on vire le dernier "\n"
    $to_erase   = array("\r\n", "\n", "\r", "\\n", "\n\n");
    $to_replace = "<br>";
  
    if ($adminMode != "addproduct") {
      $products_ordered = str_replace(" ()", "", $products_ordered);
    	$email_order = "<big><b>Commande "; 
      if ($adminMode != "yes") {
        $email_subject = EMAIL_TEXT_SUBJECT;
        if (!$recurrent_order) {
          $email_subject .= " (commande ponctuelle)";
          $email_order .= "ponctuelle";
      	} else {
          $email_subject .= " (commande recurrente)";
          $email_order .= "<u>r&eacute;currente</u>";
      	}
        $email_order .= "</b> <small>(n∞ " . $insert_id . ")</small>\n<b>";
    
        $email_order .= STORE_NAME . "</b></big>\n";
        $email_order .= EMAIL_TEXT_INVOICE_URL . ' ' . tep_href_link(FILENAME_ACCOUNT_HISTORY_INFO, 'order_id=' . $insert_id, 'SSL', false) . "\n";
        
        if ($customer_id != $customer_id_sav) {
          $email_order .= EMAIL_SEPARATOR."\n";
          $email_order .= "Cette commande a &eacute;t&eacute; effectu&eacute;e par <b>".tep_customers_name($customer_id_sav, true). "</b>, suite aux achats que vous avez demand&eacute;s ou effectu&eacute;s au local.\n";
        }
        $email_order .= EMAIL_SEPARATOR . "\n";
        
        if ($order->info['comments']) {
          $email_order .= tep_db_output($order->info['comments']) . "\n\n";
        }
        $email_order .= "\n<b>".EMAIL_TEXT_PRODUCTS . "</b>\n" . 
                        "<i>Veuillez noter les dates de livraison produit par produit indiqu&eacute;es ci-dessous.</i>\n" .
                        EMAIL_SEPARATOR . "\n" . 
                        $products_ordered . 
                        EMAIL_SEPARATOR . "\n";
      
        for ($i=0, $n=sizeof($order_totals); $i<$n; $i++) {
          $email_order .= "<b>".strip_tags($order_totals[$i]['title']) . '</b> ' . strip_tags($order_totals[$i]['text']) . "\n";
        }
      
        $email_order .= "\n<b>" . EMAIL_TEXT_BILLING_ADDRESS . "</b>\n" .
                        EMAIL_SEPARATOR . "\n" .
                        tep_address_label($customer_id, $billto) . "\n\n";
      
        if (is_object($$payment)) {
      
          $email_order .= "<b>".EMAIL_TEXT_PAYMENT_METHOD . "</b>\n" . 
                          EMAIL_SEPARATOR . "\n";
          $payment_class = $$payment;
          $email_order .= $payment_class->title . "\n";
          $email_order .= "Le paiement de chaque producteur s'effectuera au local de l'association, en fin de mois.\n\n";
      /*
          if ($payment_class->email_footer) { 
            $email_order .= $payment_class->email_footer . "\n\n";
          }
      */
        }
    
      } else {
    
        $email_subject = "Commande effectu&eacute;e par le producteur";
        $email_order .= " (n∞ " . $insert_id . ") effectu&eacute;e par le producteur</b></big><br><br>";
        $email_order .= "Vous avez demand&eacute; au producteur de vous rajouter un produit &agrave; la livraison du ". getFormattedLongDate($order_date_to, true) . " :<br><br>";
        $email_order .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$products_ordered;
        $email_order .= "<br><br>Cet email vous informe que ce produit vient d'être pris en compte par le producteur.<br>";
        $email_order .= "Cliquez <a href='".tep_href_link('../account_history_info.php', 'order_id=' . $insert_id, 'SSL', false)."'><b>ici</b></a> pour avoir le d&eacute;tail de cette 'commande'.\n<br><br>";
        $email_order .= "N'h&eacute;sitez pas &agrave; contacter le <a href=\"mailto:groms@free.fr\"><b>webmaster</b></a> pour plus d'informations.\n<br>Cordialement,\n<br>V&eacute;ronique AILLET du fleuripotager";
      }
  
      $email_subject = str_replace($to_erase, "", $email_subject);
      $email_order = str_replace($to_erase, $to_replace, $email_order);
      $send_to_name = $order->customer['firstname'] . ' ' . $order->customer['lastname'];
  
      if (!$preprod) {
        $send_to_email = $order->customer['email_address'];
      } else {
        if ($customer_id_sav != $customer_id) {
          $send_to_name = tep_customers_name($customer_id_sav)." (".$customer_id_sav. ") to " . $send_to_name . " (".$customer_id.")";
        } else {
          $send_to_name .= " (".$customer_id.")";
        }
        $send_to_email = "groms@free.fr";
      } 
      
      if (!isset($HTTP_POST_VARS['email_notify'])||(isset($HTTP_POST_VARS['email_notify']) && ($HTTP_POST_VARS['email_notify'] == '*'))) {
        $email_notify_str = "&email_notify=1";
        tep_mail($send_to_name, $send_to_email, $email_subject, $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
      } else {
        $email_notify_str = "";
      }
    
  /*
        $msg = "<BEGIN><BR><b>Destinataire : </b>".$order->customer['firstname'] . ' ' . $order->customer['lastname']." (".
            $order->customer['email_address'].")<hr><b>Objet : </b>".$email_subject."<hr><b>Corps du msg :</b><br>".$email_order."<hr>".
            STORE_OWNER." (".STORE_OWNER_EMAIL_ADDRESS.")<BR><END><hr>";
            
        echo $msg;
  */
  
    /*
      // send emails to other people
      if (SEND_EXTRA_ORDER_EMAILS_TO != '') {
        tep_mail('', SEND_EXTRA_ORDER_EMAILS_TO, EMAIL_TEXT_SUBJECT, strip_tags($email_order), STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
      }
    */
    
      // load the after_process function from the payment modules
      if (is_object($payment_modules)) {
        $payment_modules->after_process();
      }
  
    } // fin if adminMode!=addproduct (pas de else)
  }

  if ($adminMode == "") {
/*
    // send emails to manufacturers
    foreach ($manufacturers_emails as $key => $value) {
      $email_subject = $order->customer['firstname'] . ' ' . $order->customer['lastname'] . ' a effectu&eacute; une commande sur le catalogue en ligne de l\'AVEC :<br><br>';
      $email_subject .= $manufacturers_ordered_products[$key];
      $email_subject .= '<br>Vous pouvez avoir la liste de tout ce qui a &eacute;t&eacute; command&eacute; &agrave; cette adresse : <a href="http://avec35catalog.free.fr/admin/stats_manufacturers_sales.php?mID='.$key.'&mName='.$manufacturers_names[$key].'" target="_blank">Ventes producteurs</a><br>N\'oubliez pas de cliquer sur la p&eacute;riode appropri&eacute;e (Semaine derni&egrave;re, Mois dernier, ...)<br><br>AVEC - Vente en ligne';
  
      tep_mail($manufacturers_names[$key], $manufacturers_emails[$key], 'Commande en ligne sur le catalogue en ligne AVEC35', $email_subject, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
    }
*/

    $aux = 'orders_id='.$insert_id;
    if ($recurrent_order) {
      $aux .= '&recurrent_order=1';
    }
  
    if ($customer_id != $customer_id_sav) {
      $aux .= '&order_to_other='.$customer_id;
      $customer_id = $customer_id_sav;
    }
    
  }

// unregister session variables used during checkout
  tep_session_unregister('sendto');
  tep_session_unregister('billto');
  tep_session_unregister('shipping');
  tep_session_unregister('payment');
  tep_session_unregister('comments');

  $cart->reset(true);   // true : for database

  if ($adminMode == "") {
    tep_redirect(tep_href_link(FILENAME_CHECKOUT_SUCCESS, $aux.$email_notify_str, 'SSL'));
    require(DIR_WS_INCLUDES . 'application_bottom.php');
  }

?>
