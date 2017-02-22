<?
/*
$Id: orders.php,v 1.112 2003/06/29 22:50:52 hpdl Exp $

osCommerce, Open Source E-Commerce Solutions
http://www.oscommerce.com

Copyright (c) 2003 osCommerce

Released under the GNU General Public License
*/



function tep_get_params_order($action, $oID, $reset_page = false, $add_status = true) {
  global $cID, $group_id, $status, $order_date_to_ga, $customers_addproduct;

  $array = array('action','status','group_id','order_date_to_ga','customers_addproduct','page','oID','opID');

  $query = '';
  if ($action != '') {
    $query .= '&action=' . $action;
  } 
  if (($oID > -1)&&($oID != "")) {
    $query .= '&oID='. $oID;
  }
  if (($group_id > -1)&&($group_id != "")) {
    $query .= '&group_id=' . $group_id;
  } 
  if ($add_status && ($status > -2) && ($status != "")) {
    $query .= '&status=' . $status;
  } 
  if (($group_id > 0)&&($order_date_to_ga != "")) {
    $query .= '&order_date_to_ga=' . $order_date_to_ga;
  } 
  if (($customers_addproduct > 0)&&($customers_addproduct != "")) {
    $query .= '&customers_addproduct=' . $customers_addproduct;
  } 

  $query = substr($query, 1);
  return tep_get_all_get_params($array) . $query;
}

$adminMode = "yes";
$orderAdminMode = "yes";
require('includes/application_top.php');

$oID = '';
if (isset($HTTP_POST_VARS['oID_field'])) {
  $oID = tep_db_prepare_input($HTTP_POST_VARS['oID_field']);
} else if (isset($HTTP_POST_VARS['oID'])) {
  $oID = tep_db_prepare_input($HTTP_POST_VARS['oID']);
} else if (isset($HTTP_GET_VARS['oID'])) {
  $oID = tep_db_prepare_input($HTTP_GET_VARS['oID']);
}
if (($oID == '')||($oID < 0)) {
  $oID = -1;
}

$group_id = '';
$order_date_to_ga = '';
if (isset($HTTP_POST_VARS['group_id'])) {
  // la variable POST est prioritaire sur touttes les autres
  $group_id = tep_db_prepare_input($HTTP_POST_VARS['group_id']);
} else if (isset($HTTP_GET_VARS['group_id'])) {
  $group_id = tep_db_prepare_input($HTTP_GET_VARS['group_id']);
}
if (($group_id == '')||($group_id < 0)) {
  $group_id = -1;
  $order_date_to_ga = '';
} else if ($group_id>0) {
  if (isset($HTTP_POST_VARS['order_date_to_ga'])) {
    // la variable POST est prioritaire sur touttes les autres
    $order_date_to_ga = tep_db_prepare_input($HTTP_POST_VARS['order_date_to_ga']);
  } else if (isset($HTTP_GET_VARS['order_date_to_ga'])) {
    $order_date_to_ga = tep_db_prepare_input($HTTP_GET_VARS['order_date_to_ga']);
  }
}

$customers_addproduct = '';
$cID = '';
if (isset($HTTP_POST_VARS['customers_addproduct'])) {
  // la variable POST est prioritaire sur touttes les autres
  $cID = tep_db_prepare_input($HTTP_POST_VARS['customers_addproduct']);
} else if (isset($HTTP_GET_VARS['customers_addproduct'])) {
  $cID = tep_db_prepare_input($HTTP_GET_VARS['customers_addproduct']);
} else if (isset($HTTP_POST_VARS['cID'])) {
  $cID = tep_db_prepare_input($HTTP_POST_VARS['cID']);
} else if (isset($HTTP_GET_VARS['cID'])) {
  $cID = tep_db_prepare_input($HTTP_GET_VARS['cID']);
}
if ($cID == '') {
  $cID = -1;
}
$customers_addproduct = $cID;

if (isset($HTTP_POST_VARS['status'])) {
  // la variable POST est prioritaire sur touttes les autres
  $status = tep_db_prepare_input($HTTP_POST_VARS['status']);
} else if (isset($HTTP_GET_VARS['status'])) {
  $status = tep_db_prepare_input($HTTP_GET_VARS['status']);
}
if ($status == '') {
  $status = -2;
}

require($admin_FS_path . DIR_WS_CLASSES . 'currencies.php');
$currencies = new currencies();

$orders_statuses = array();
$orders_statuses_norec = array();
$orders_statuses_rec = array();
$orders_status_array = array();
$orders_status_query = tep_db_query("select orders_status_id, orders_status_name from " . TABLE_ORDERS_STATUS . " where language_id = '" . (int)$languages_id . "'");
while ($orders_status = tep_db_fetch_array($orders_status_query)) {
  $orders_statuses[] = array('id' => $orders_status['orders_status_id'],
                          'text' => $orders_status['orders_status_name']);
  if (($orders_status['orders_status_id']>0)&&($orders_status['orders_status_id']!=4)) {
    // commande non récurrente
    $orders_statuses_norec[] = array('id' => $orders_status['orders_status_id'],
                            'text' => $orders_status['orders_status_name']);
  } else {
    $orders_statuses_rec[] = array('id' => $orders_status['orders_status_id'],
                            'text' => $orders_status['orders_status_name']);
  }
  $orders_status_array[$orders_status['orders_status_id']] = $orders_status['orders_status_name'];
}

$action = (isset($HTTP_GET_VARS['action']) ? $HTTP_GET_VARS['action'] : '');

//echo $action;exit;

if (tep_not_null($action)) {
  switch ($action) {
    case 'deleteproduct':
      $oID = tep_db_prepare_input($HTTP_GET_VARS['oID']);
      $opID = tep_db_prepare_input($HTTP_GET_VARS['opID']);
//      echo $oID.'-'.$opID;exit;
      tep_db_query("DELETE FROM " . TABLE_ORDERS_PRODUCTS . " WHERE orders_id = '$oID' AND orders_products_id = '$opID'");
      tep_redirect(tep_href_link(FILENAME_ORDERS, tep_get_params_order('edit', $oID, false, false)));
      break;  
    case 'update_status':
    case 'update_order':
      if (isset($HTTP_POST_VARS['oID'])) {
        $oID = tep_db_prepare_input($HTTP_POST_VARS['oID']);
      } else {
        $oID = tep_db_prepare_input($HTTP_GET_VARS['oID']);
      }
      $status = tep_db_prepare_input($HTTP_POST_VARS['new_status']);
      $comments = tep_db_prepare_input($HTTP_POST_VARS['comments']);
      
      $order_updated = false;
      $check_status_query = tep_db_query("select customers_name, customers_email_address, orders_status, date_purchased from " . TABLE_ORDERS . " where orders_id = '" . (int)$oID . "'");
      $check_status = tep_db_fetch_array($check_status_query);
      
      if ( ($check_status['orders_status'] != $status) || tep_not_null($comments)) {
        tep_db_query("update " . TABLE_ORDERS . " set orders_status = '" . tep_db_input($status) . "', last_modified = now() where orders_id = '" . (int)$oID . "'");
        
        $customer_notified = '0';
        if (isset($HTTP_POST_VARS['notify']) && ($HTTP_POST_VARS['notify'] == 'on')) {
          $notify_comments = '';
          if (isset($HTTP_POST_VARS['notify_comments']) && ($HTTP_POST_VARS['notify_comments'] == 'on')) {
            $notify_comments = sprintf(EMAIL_TEXT_COMMENTS_UPDATE, $comments) . "\n\n";
          }
          
          if (!$preprod) {
            $email = STORE_NAME . "\n" . EMAIL_SEPARATOR . "\n" . EMAIL_TEXT_ORDER_NUMBER . ' ' . $oID . "\n" . EMAIL_TEXT_INVOICE_URL . ' ' . tep_catalog_href_link(FILENAME_CATALOG_ACCOUNT_HISTORY_INFO, 'order_id=' . $oID, 'SSL') . "\n" . EMAIL_TEXT_DATE_ORDERED . ' ' . tep_date_long($check_status['date_purchased']) . "\n\n" . $notify_comments . sprintf(EMAIL_TEXT_STATUS_UPDATE, $orders_status_array[$status]);
            tep_mail($check_status['customers_name'], $check_status['customers_email_address'], EMAIL_TEXT_SUBJECT, $email, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
            $customer_notified = '1';
          } else {
            $customer_notified = '0';
          }
          
        }
        
        tep_db_query("insert into " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments) values ('" . (int)$oID . "', '" . tep_db_input($status) . "', now(), '" . tep_db_input($customer_notified) . "', '" . tep_db_input($comments)  . "')");
        
        $order_updated = true;
      }
      
      if ($order_updated == true) {
        $messageStack->add_session(SUCCESS_ORDER_UPDATED, 'success');
      } else {
        $messageStack->add_session(WARNING_ORDER_NOT_UPDATED, 'warning');
      }
      if ($action == 'update_order') {
        tep_redirect(tep_href_link(FILENAME_ORDERS, tep_get_params_order('edit', $oID, false, false)));
      } else {
        tep_redirect(tep_href_link(FILENAME_ORDERS, tep_get_params_order('', $oID, false, false)));
      }
      break;
    case 'deleteallconfirm':
      $order_query = tep_db_query("select orders_id from " . TABLE_ORDERS);
      while ($order = tep_db_fetch_array($order_query)) {
        tep_remove_order($order['orders_id'], '');
      }
      
      tep_redirect(tep_href_link(FILENAME_ORDERS, tep_get_params_order('', '')));
      break;
    case 'new_order':
      
      $customer_id = tep_db_prepare_input($HTTP_GET_VARS['cID']);
      $group_id = tep_db_prepare_input($HTTP_POST_VARS['group_id']);
      if ($group_id == 0) {
        $order_date_to = tep_db_prepare_input($HTTP_POST_VARS['order_date_to_vd']);
      } else {
        $order_date_to = tep_db_prepare_input($HTTP_POST_VARS['order_date_to_ga']);
      }
      
//      echo $group_id;exit;

      // on crée la nouvelle commande
      tep_session_register('cart');
      $cart = new shoppingCart;
      tep_session_register('customer_id');
      $recurrent_order = false;
      $adminMode = "yes";
//      $cart->add_cart($p_id, $qty, $attr, false, true);
      // intégration checkout_process.php
      $new_order_admin_mode = "yes";
      require($doc_root . $subpath . 'checkout_process.php');
//      $cart->cleanup();
      $sql_data_array = array('orders_id' => -$insert_id,
                              'date_shipped' => $order_date_to,
                              'next_date_shipped' => $order_date_to,
                              'manufacturers_id' => 0,
                              'is_recurrence_order' => (int)$recurrent_order,
                              'products_id' => 0, 
                              'group_id' => $group_id, 
                              'shipping_day' => 0, 
                              'shipping_frequency' => 0, 
                              'products_model' => '', 
                              'products_name' => 'fake product', 
                              'products_price' => 0, 
                              'final_price' => 0, 
                              'products_tax' => 0, 
                              'products_quantity' => 0);
      tep_db_perform(TABLE_ORDERS_PRODUCTS, $sql_data_array);
//      $order_products_id = tep_db_insert_id();

      // purge des variables
      tep_session_unregister('cart');
      tep_session_unregister('customer_id');
      
      tep_redirect(tep_href_link(FILENAME_ORDERS, tep_get_params_order('edit', $insert_id)));
      break;
    case 'deleteconfirm':
      $oID = tep_db_prepare_input($HTTP_GET_VARS['oID']);
      
      tep_remove_order($oID, $HTTP_POST_VARS['restock']);
      
      tep_redirect(tep_href_link(FILENAME_ORDERS, tep_get_params_order('', '')));
      break;
    case 'fusion':
      // fusion de toutes les comandes du GA ayant le même customers_id, orders_status et date_shipped
      
      $oID = tep_db_prepare_input($HTTP_GET_VARS['oID']);
      $order_query = tep_db_query("SELECT op.date_shipped, o.customers_id, o.orders_status, o.group_id FROM " . TABLE_ORDERS . " AS o LEFT JOIN " . TABLE_ORDERS_PRODUCTS . " AS op ON op.orders_id = o.orders_id WHERE o.orders_id = $oID LIMIT 1;");
      if ($order = tep_db_fetch_array($order_query)) {
        $oID_list = "";
        $oq = tep_db_query("SELECT DISTINCT opm.orders_id FROM orders_products_modifications AS opm LEFT JOIN orders AS o ON o.orders_id = opm.orders_id WHERE 
                  opm.date_shipped = '".$order['date_shipped']."' AND 
                  o.orders_status = ". $order['orders_status']." AND
                  o.customers_id = ". $order['customers_id']." AND
                  o.group_id = ". $order['group_id']." AND
                  o.orders_id <> $oID;");
        while ($or = tep_db_fetch_array($oq)) {
          $oID_list .= $or['orders_id'].",";
        }
        $oID_list = substr($oID_list, 0, -1);
        
//        echo $oID_list;exit;
        
        if ($oID_list) {
          tep_db_query("UPDATE orders_products SET orders_id = $oID WHERE orders_id IN ($oID_list);"); 
          tep_db_query("UPDATE orders_products_modifications SET orders_id = $oID WHERE orders_id IN ($oID_list);"); 
          tep_db_query("UPDATE orders_products_attributes SET orders_id = $oID WHERE orders_id IN ($oID_list);"); 
          tep_db_query("UPDATE orders_products_download SET orders_id = $oID WHERE orders_id IN ($oID_list);"); 
          tep_db_query("DELETE FROM orders_total WHERE orders_id IN ($oID_list);");
          tep_db_query("DELETE FROM orders WHERE orders_id IN ($oID_list);");
          // ajout d'un historique
          $mydatetime = date("Y-m-d H:i:s");
          $sql_add = "INSERT INTO " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments) VALUES ('".$oID."','".$order['orders_status']."','$mydatetime','0','fusion des commandes identiques par le producteur => IDs ($oID_list)');";
          tep_db_query($sql_add, 'db_link');
          calcul_ot_one($oID, false); 
        }          
      }
      
      tep_redirect(tep_href_link(FILENAME_ORDERS, tep_get_params_order('', $oID)));
      break;
  }
}

if (($action == 'edit') && isset($HTTP_GET_VARS['oID'])) {
  $oID = tep_db_prepare_input($HTTP_GET_VARS['oID']);
  
  $orders_query = tep_db_query("select orders_id from " . TABLE_ORDERS . " where orders_id = '" . (int)$oID . "'");
  $order_exists = true;
  if (!tep_db_num_rows($orders_query)) {
    $order_exists = false;
    $messageStack->add(sprintf(ERROR_ORDER_DOES_NOT_EXIST, $oID), 'error');
  }
}

include($admin_FS_path . DIR_WS_CLASSES . 'order.php');
$is_week_mode = true;
$fromCustomers = "yes";
$gaID = 1;
$order_date_to = get_order_date("", "", "tuesday|thursday", 0.5);
require($doc_root . $subpath . DIR_WS_FUNCTIONS . 'shared.php');  
if ($reload) {
  reloadSMS(-1, FILENAME_ORDERS);
}

?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <? echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<? echo CHARSET; ?>">
<title><? echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="includes/general.js"></script>

<script language="javascript">
  function deleteProductFromOrder(opID) {
    if (confirm("Voulez-vous vraiment supprimer ce produit ?")) { // Clic sur OK
  		myUrl = '<?=tep_href_link(FILENAME_ORDERS, tep_get_params_order('deleteproduct', $oID, false, true));?>'+'&opID='+opID;
      document.location.replace(myUrl); 
    }
  }
</script>

</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
<!-- header //-->
<?
require($admin_FS_path . DIR_WS_INCLUDES . 'header.php');
?>
<!-- header_eof //-->

<!-- body //-->
<table border="0" width="100%" cellspacing="2" cellpadding="2">
<tr>
<td width="<? echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<? echo BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
<!-- left_navigation //-->
<? require($admin_FS_path . DIR_WS_INCLUDES . 'column_left.php'); ?>
<!-- left_navigation_eof //-->
</table></td>
<!-- body_text //-->
<td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
<?



if (($action == 'edit') && ($order_exists == true)) {
  $order = new order($oID);
  require('../account_history_info_table.php');

?>
<tr>
<td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
<tr>
<td class="pageHeading"><? echo HEADING_TITLE; ?></td>
<td class="pageHeading" align="right"><? echo tep_draw_separator('pixel_trans.gif', 1, HEADING_IMAGE_HEIGHT); ?></td>
<td class="pageHeading" align="right"><? echo '<a href="' . tep_href_link(FILENAME_ORDERS, tep_get_params_order('', $oID)) . '">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a>'; ?></td>
</tr>
<tr>
<td class="pageHeadingSmall" colspan="3">Num&eacute;ro de commande: <? echo tep_db_input($oID);?></td>
</tr>
<tr>
</table></td>
</tr>
<tr>
<td><table width="100%" border="0" cellspacing="0" cellpadding="2">
<tr>
<td colspan="3"><? echo tep_draw_separator(); ?></td>
</tr>
<tr>
<td valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="2">
<tr>
<td class="main" nowrap><b><? echo "Date commande:"; ?></b></td>
<td class="main" nowrap><? echo getFormattedLongDate($order->info['date_purchased'], true).' - '. date("H:i:s", strtotime($order->info['date_purchased'])); ?></td>
</tr>
<tr>
<td colspan="2"><? echo tep_draw_separator('pixel_trans.gif', '1', '5'); ?></td>
</tr>
<tr>
<td class="main"  nowrap valign="top"><b><? echo "Adhérent (n° ". $order->customer['id'] ."):"; ?></b></td>
<td class="main" nowrap><? echo tep_address_format(6 /*$order->customer['format_id']*/, $order->customer, 1, '', '<br>'); ?></td>
</tr>
<tr>
<td colspan="2"><? echo tep_draw_separator('pixel_trans.gif', '1', '5'); ?></td>
</tr>
<td class="main" nowrap><b><? echo ENTRY_TELEPHONE_NUMBER; ?></b></td>
<td class="main" nowrap><? echo $order->customer['telephone']; ?></td>
</tr>
<tr>
<td class="main" nowrap><b><? echo ENTRY_EMAIL_ADDRESS; ?></b></td>
<td class="main" nowrap><? echo '<a href="mailto:' . $order->customer['email_address'] . '"><u>' . $order->customer['email_address'] . '</u></a>'; ?></td>
</tr>
<tr>
<td colspan="2"><? echo tep_draw_separator('pixel_trans.gif', '1', '5'); ?></td>
</tr>
<tr>
<td class="main"><b><? echo ENTRY_PAYMENT_METHOD; ?></b></td>
<td class="main"><? echo $order->info['payment_method']; ?></td>
</tr>
<?
if (tep_not_null($order->info['cc_type']) || tep_not_null($order->info['cc_owner']) || tep_not_null($order->info['cc_number'])) {
?>
<tr>
<td colspan="2"><? echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
</tr>
<tr>
<td class="main"><? echo ENTRY_CREDIT_CARD_TYPE; ?></td>
<td class="main"><? echo $order->info['cc_type']; ?></td>
</tr>
<tr>
<td class="main"><? echo ENTRY_CREDIT_CARD_OWNER; ?></td>
<td class="main"><? echo $order->info['cc_owner']; ?></td>
</tr>
<tr>
<td class="main"><? echo ENTRY_CREDIT_CARD_NUMBER; ?></td>
<td class="main"><? echo $order->info['cc_number']; ?></td>
</tr>
<tr>
<td class="main"><? echo ENTRY_CREDIT_CARD_EXPIRES; ?></td>
<td class="main"><? echo $order->info['cc_expires']; ?></td>
</tr>
<?
}
?>
</table></td>
<td width="100%">&nbsp;</td>
</tr>
</table></td>
</tr>
<tr>
<td><? echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
</tr>
<tr>
<td><hr></td>
</tr>
<tr>
<td><? echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
</tr>
<tr>
<td>
<? echo $table;?>
</td>
</tr>
<tr>
<td><? echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
</tr>
<tr>
<td>
<? echo $validated_orders_table;?>
</td>
</tr>
<tr>
<td><? echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
</tr>
<tr>
<td class="main">
<a href="javascript:showhide('history')"><span class="main"><b>Historique</b> <i>(cliquer pour afficher)</i></span></a>
<div id='history' style='display:none;'>
<table border="1" cellspacing="0" cellpadding="5">
<tr>
<td class="smallText" align="center"><b><? echo TABLE_HEADING_DATE_ADDED; ?></b></td>
<td class="smallText" align="center"><b><? echo TABLE_HEADING_CUSTOMER_NOTIFIED; ?></b></td>
<td class="smallText" align="center"><b><? echo TABLE_HEADING_STATUS; ?></b></td>
<td class="smallText" align="center"><b><? echo TABLE_HEADING_COMMENTS; ?></b></td>
</tr>
<?
$orders_history_query = tep_db_query("select orders_status_id, date_added, customer_notified, comments from " . TABLE_ORDERS_STATUS_HISTORY . " where orders_id = '" . tep_db_input($oID) . "' order by date_added");
if (tep_db_num_rows($orders_history_query)) {
while ($orders_history = tep_db_fetch_array($orders_history_query)) {
echo '          <tr>' . "\n" .
'            <td class="smallText" align="center">' . tep_datetime_short($orders_history['date_added']) . '</td>' . "\n" .
'            <td class="smallText" align="center">';
if ($orders_history['customer_notified'] == '1') {
echo tep_image(DIR_WS_ICONS . 'tick.gif', ICON_TICK) . "</td>\n";
} else {
echo tep_image(DIR_WS_ICONS . 'cross.gif', ICON_CROSS) . "</td>\n";
}
echo '            <td class="smallText">' . $orders_status_array[$orders_history['orders_status_id']] . '</td>' . "\n" .
'            <td class="smallText">' . nl2br(tep_db_output($orders_history['comments'])) . '&nbsp;</td>' . "\n" .
'          </tr>' . "\n";
}
} else {
echo '          <tr>' . "\n" .
'            <td class="smallText" colspan="5">' . TEXT_NO_ORDER_HISTORY . '</td>' . "\n" .
'          </tr>' . "\n";
}
?>
</table></div></td>
</tr>
<tr>
<td><? echo tep_draw_separator('pixel_trans.gif', '1', '5'); ?></td>
</tr>
<tr>
<td class="main"><b><? echo TABLE_HEADING_COMMENTS; ?></b></td>
</tr>
<tr><? echo tep_draw_form('status', FILENAME_ORDERS, tep_get_params_order('update_order', $oID)); ?>
<td class="main"><? echo tep_draw_textarea_field('comments', 'soft', '60', '5'); ?></td>
</tr>
<tr>
<td><? echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
</tr>
<tr>
<td><table border="0" cellspacing="0" cellpadding="2">
<tr>
<td><table border="0" cellspacing="0" cellpadding="2">
<tr>
<td class="main"><b><? echo ENTRY_STATUS; ?></b> <? 
    if (($order->info['orders_status_id']!=-1)&&($order->info['orders_status_id']!=4)) {
      echo tep_draw_pull_down_menu('new_status', $orders_statuses_norec, $order->info['orders_status_id']); 
    } else {
      echo tep_draw_pull_down_menu('new_status', $orders_statuses_rec, $order->info['orders_status_id']); 
    }
    
?></td>
</tr>
<tr>
<td class="main"><b><? echo ENTRY_NOTIFY_CUSTOMER; ?></b> <? echo tep_draw_checkbox_field('notify', '', true); ?></td>
<td class="main"><b><? echo ENTRY_NOTIFY_COMMENTS; ?></b> <? echo tep_draw_checkbox_field('notify_comments', '', true); ?></td>
</tr>
</table></td>
<td valign="top"><? echo tep_image_submit('button_update.gif', IMAGE_UPDATE); ?></td>
</tr>
</table></td>
</form></tr>
<tr>
<td colspan="2" align="right">
<?
// echo '<a href="' . tep_href_link(FILENAME_ORDERS_INVOICE, 'oID=' . $HTTP_GET_VARS['oID']) . '" TARGET="_blank">' . tep_image_button('button_invoice.gif', IMAGE_ORDERS_INVOICE) . '</a>';
// echo '<a href="' . tep_href_link(FILENAME_ORDERS_PACKINGSLIP, 'oID=' . $HTTP_GET_VARS['oID']) . '" TARGET="_blank">' . tep_image_button('button_packingslip.gif', IMAGE_ORDERS_PACKINGSLIP) . '</a>';
echo '<a href="' . tep_href_link(FILENAME_ORDERS, tep_get_params_order('', $oID)) . '">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a>';
?>
</td>
</tr>
<?
} else {



?>
<tr>
<td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
<tr>
<td class="pageHeading"><? echo HEADING_TITLE; ?></td>
<td class="pageHeading" align="right"><? echo tep_draw_separator('pixel_trans.gif', 1, HEADING_IMAGE_HEIGHT); ?></td>
<td align="right">
<? echo tep_draw_form('orders', FILENAME_ORDERS, tep_get_params_order('', '')); ?>
<table border="0" width="100%" cellspacing="0" cellpadding="0">
<tr>
<td class="smallTextNoBorder" align="right" width="100%" nowrap>
  <? echo '<b>'.HEADING_TITLE_STATUS . '</b> ' . tep_draw_pull_down_menu('status', array_merge(array(array('id' => '', 'text' => TEXT_ALL_ORDERS)), $orders_statuses), '', 'onChange="this.form.submit();"'); ?>
</td><td class="smallTextNoBorder" align="right" nowrap>
&nbsp;&nbsp;
</td><td class="smallTextNoBorder" align="right" nowrap>
<?
echo '&nbsp;&nbsp;<b>Adhérent :</b> ' . addClientListBlock($group_id, $customers_addproduct, ' onchange="this.form.submit();"');
 ?>
</td>
</tr>
<tr>
<td class="smallTextNoBorder" align="right" nowrap>
  <? echo '<b>Type:</b> ' . tep_draw_pull_down_menu('group_id', array(array('id' => '-1', 'text' => '-- TOUS --'), array('id' => '0', 'text' => 'Vente directe'), array('id' => '1', 'text' => 'Groupement d\'achat')), $group_id, 'onChange="this.form.submit();"'); ?>
</td><td class="smallTextNoBorder" align="right" nowrap>
&nbsp;&nbsp;
</td><td class="smallTextNoBorder" align="right" nowrap>
<? 
//if ($group_id > 0) {
    
  echo '&nbsp;&nbsp;<b>Date de livraison:</b> ';
  if ($group_id > 0) {
    array_unshift($shipping_dates_array, array('id' => '', 'text' => '-- DATE DE LIVRAISON (GA) -- '));
    echo tep_draw_pull_down_menu('order_date_to_ga', $shipping_dates_array, $order_date_to_ga, 'onChange="this.form.submit();"');
  } else {
    echo tep_draw_pull_down_menu('order_date_to_ga', array(array('id' => '', 'text' => '-- DATE DE LIVRAISON (GA) -- ')), '', 'disabled');
  }
//}
?>
</td>
</tr>
<tr>
<td class="smallTextNoBorder" align="right" colspan="3" nowrap>
  <? echo '<b>'.HEADING_TITLE_SEARCH . '</b>&nbsp;&nbsp;' . tep_draw_input_field('oID_field', '', 'size="12"'); ?>
</td>
</tr>
</table>
<?
tep_draw_hidden_field('action', $action);
/*
tep_draw_hidden_field('oID', $oID);
tep_draw_hidden_field('group_id', $group_id);
tep_draw_hidden_field('cID', $cID);
tep_draw_hidden_field('status', $status);
*/
?>
</form>
</td>
</tr>
</table></td>
</tr>
<tr>
<td>
<table border="0" width="100%" cellspacing="0" cellpadding="0">
<tr>
<td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
<?
$orders_query_raw = "select ot.value, o.group_id, o.orders_id, o.orders_status, o.customers_name, o.payment_method, o.date_purchased, o.last_modified, o.currency, o.currency_value, s.orders_status_name, ot.text as order_total, o.customers_id from " . TABLE_ORDERS . " o 
    left join (select DISTINCT orders_id, date_shipped from orders_products) as op on o.orders_id = op.orders_id 
    left join " . TABLE_ORDERS_TOTAL . " ot on (o.orders_id = ot.orders_id), " . TABLE_ORDERS_STATUS . " s 
    where o.orders_status = s.orders_status_id and s.language_id = '" . (int)$languages_id . "' and ot.class = 'ot_total'";


if (($group_id >= 0)&&($group_id != "")) {
  $orders_query_raw .= " and o.group_id = $group_id";
  if ($order_date_to_ga != '') {
    $orders_query_raw .= " and op.date_shipped = '".$order_date_to_ga."'";
  }
}
if (($cID > 0)&&($cID != "")) {
  $orders_query_raw .= " and o.customers_id = $cID";
}
if (($status > -2)&&($status != "")) {
  $orders_query_raw .= " and s.orders_status_id = '" . $status . "'";
}

$orders_query_raw .= " group by o.orders_id order by o.orders_id DESC";
//echo  $orders_query_raw;
$orders_split = new splitPageResults($HTTP_GET_VARS['page'], MAX_DISPLAY_SEARCH_RESULTS, $orders_query_raw, $orders_query_numrows);
$orders_query = tep_db_query($orders_query_raw);
$nbOrders = 0;
$total = 0;
if (tep_db_num_rows($orders_query) > 0) {
?>
<tr class="dataTableHeadingRow">
  <td class="dataTableHeadingContent" align="center"><? echo TABLE_HEADING_CUSTOMERS; ?></td>
  <td class="dataTableHeadingContent" align="center"><? echo TABLE_HEADING_ORDER_TOTAL; ?></td>
  <td class="dataTableHeadingContent" align="center"><? echo TABLE_HEADING_DATE_PURCHASED; ?></td>
  <td class="dataTableHeadingContent" align="center">Date livraison</td>
  <td class="dataTableHeadingContent" align="center"><? echo TABLE_HEADING_STATUS; ?></td>
  <td class="dataTableHeadingContent" align="center"><? echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
</tr>
<?
} else {?>

<br>
<span class="pageHeadingSmall">Aucune commande n'a été trouvée avec les critères sélectionnés.</span>

<?}
while ($orders = tep_db_fetch_array($orders_query)) {
  if (!isset($HTTP_GET_VARS['oID']) || !is_numeric($HTTP_GET_VARS['oID']) ) {
    $oID = $orders['orders_id'];
  } else {
    $oID = $HTTP_GET_VARS['oID'];
  }
  if (($oID == $orders['orders_id']) && !isset($oInfo)) {
    $oInfo = new objectInfo($orders);
  }

  // colore les lignes suivant le statut de la commande
  $cls = getBgColorClass($orders['orders_status']);
  if (isset($oInfo) && is_object($oInfo) && ($orders['orders_id'] == $oInfo->orders_id)) {
//      $i = 1;
      $td_mouse = ' onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_ORDERS, tep_get_params_order('edit', $oInfo->orders_id)) . '\'"';
      echo '              <tr id="defaultSelected" class="dataTableRowSelected">' . "\n";
  } else {
      $td_mouse = ' onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_ORDERS, tep_get_params_order('', $orders['orders_id'])) . '\'"';
      echo '              <tr class="dataTableRow" onmouseover="rowOverEffect(this)">' . "\n";
  }
  
  $ds = "";
  if ($orders['group_id']>0) {
    $g_name = "<b><big><big>GA</big></big></b>";
    // on récupère la date de livraison
    if ($orders['orders_id'] > 0) {
      $sql = "select date_shipped from orders_products_modifications where orders_id = ".$orders['orders_id']." LIMIT 1;";
      $query = tep_db_query($sql);
      if ($res = tep_db_fetch_array($query)) {
        $ds = $res['date_shipped'];
      }
    }
  } else {
    $g_name = "VD";
  }
  
  
  ?>
  <td class="<?=$cls?>"<?=$td_mouse?> nowrap><? echo $g_name . ' - ' . $orders['customers_name'] . "&nbsp;" . "(ID cmde : " . $orders['orders_id'] . ")"; ?></td>
  <td class="<?=$cls?>"<?=$td_mouse?> align="right"><? echo strip_tags($orders['order_total']); ?></td>
  <td class="<?=$cls?>"<?=$td_mouse?> align="center">&nbsp;<? echo tep_datetime_short($orders['date_purchased']); ?>&nbsp;</td>
  <td class="<?=$cls?>"<?=$td_mouse?> align="center">&nbsp;<? if ($ds!="") echo date("d/m/Y",strtotime($ds)); ?>&nbsp;</td>
  <td class="<?=$cls?>" align="center">
    <? 
      echo tep_draw_form('status_form', FILENAME_ORDERS, tep_get_params_order('update_status', $orders['orders_id']));
//      echo tep_draw_hidden_field('oID', $orders['orders_id']);
      $onchange = " onchange='this.form.submit();'";
      if (($orders['orders_status']!=-1)&&($orders['orders_status']!=4)) {
        echo tep_draw_pull_down_menu('new_status', $orders_statuses_norec, $orders['orders_status'], $onchange); 
      } else {
        echo tep_draw_pull_down_menu('new_status', $orders_statuses_rec, $orders['orders_status'], $onchange); 
      }
   ?>
  </form></td>
  <td class="<?=$cls?>"<?=$td_mouse?> align="right"><? 
    echo '<a href="' . tep_href_link(FILENAME_ORDERS, tep_get_params_order('edit', $orders['orders_id'])) . '">' . tep_image(DIR_WS_ICONS . 'b_edit.png', 'Editer cette commande') . '</a>&nbsp;';
    if (isset($oInfo) && is_object($oInfo) && ($orders['orders_id'] == $oInfo->orders_id)) { echo tep_image(DIR_WS_IMAGES . 'icon_arrow_right.gif', ''); } else { echo '<a href="' . tep_href_link(FILENAME_ORDERS, tep_get_params_order('', $orders['orders_id'])) . '">' . tep_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp;</td>
  </tr>
  <?
  $total += $orders['value'];

  $nbOrders++;
}
if ($nbOrders>0) {
($nbOrders>1) ? $s = "s" : $s = "";
?>
<tr>
<td class="dataTableTotalRow" valign="top" nowrap>TOTAL (sur <?=$nbOrders?> commande<?=$s?>) :</td>
<td class="dataTableTotalRow" align="right" nowrap><? echo $currencies->format($total); ?></td>
<td class="dataTableTotalRow" align="right" nowrap>&nbsp;</td>
<td class="dataTableTotalRow" align="right" nowrap><? if ($order_date_to_ga!="") echo date("d/m/Y",strtotime($order_date_to_ga));?></td>
<td class="dataTableTotalRow" align="right" nowrap colspan="2">&nbsp;</td>
</tr>
<?
if ($orders_query_numrows > MAX_DISPLAY_SEARCH_RESULTS) {
?>
<tr>
<td colspan="6">
  <table border="0" width="100%" cellspacing="0" cellpadding="2">
    <tr>
      <td class="smallText" valign="top"><? echo $orders_split->display_count($orders_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $HTTP_GET_VARS['page'], TEXT_DISPLAY_NUMBER_OF_ORDERS); ?></td>
      <td class="smallText" align="right"><? echo $orders_split->display_links($orders_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $HTTP_GET_VARS['page'], tep_get_params_order('', $orders['orders_id'], true)); ?></td>
    </tr>
  </table>
</td>
</tr>
<?}// if (MAX_DISPLAY_SEARCH_RESULTS)
}?>
</table></td>
<?
$heading = array();
$contents = array();

switch ($action) {
case 'deleteall':
$heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_DELETE_ORDER . '</b>');

$contents = array('form' => tep_draw_form('orders', FILENAME_ORDERS, tep_get_params_order('deleteallconfirm', $oInfo->orders_id)));
$contents[] = array('align' => 'center', 'text' => 'ETES-VOUS SUR DE VOULOIR SUPPRIMER TOUTES LES COMMANDES ?<br>' . tep_image_submit('button_delete.gif', IMAGE_DELETE) . ' <a href="' . tep_href_link(FILENAME_ORDERS, tep_get_params_order('', $oInfo->orders_id)) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
break;
case 'delete':
$heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_DELETE_ORDER . '</b>');

$contents = array('form' => tep_draw_form('orders', FILENAME_ORDERS, tep_get_params_order('deleteconfirm', $oInfo->orders_id)));
$contents[] = array('text' => TEXT_INFO_DELETE_INTRO . '<br><br><b>' . $cInfo->customers_firstname . ' ' . $cInfo->customers_lastname . '</b>');
$contents[] = array('text' => '<br>' . tep_draw_checkbox_field('restock') . ' ' . TEXT_INFO_RESTOCK_PRODUCT_QUANTITY);
$contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_delete.gif', IMAGE_DELETE) . ' <a href="' . tep_href_link(FILENAME_ORDERS, tep_get_params_order('', $oInfo->orders_id)) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
break;
default:
  if (isset($oInfo) && is_object($oInfo)) {
  $heading[] = array('text' => '<b>[' . $oInfo->orders_id . ']&nbsp;&nbsp;' . tep_datetime_short($oInfo->date_purchased) . '</b>');
  
  
  $contents[] = array('align' => 'center', 'text' => '
    <a href="' . tep_href_link(FILENAME_ORDERS, tep_get_params_order('edit', $oInfo->orders_id)) . '">' . tep_image_button('button_edit.gif', 'Editer cette commande') . '</a> 
    <a href="' . tep_href_link(FILENAME_ORDERS, tep_get_params_order('delete', $oInfo->orders_id)) . '">' . tep_image_button('button_delete.gif', 'Effacer cette commande') . '</a>');

  if ($oInfo->group_id>0) {
    $contents[] = array('align' => 'center', 'text' => '
      <a href="' . tep_href_link(FILENAME_ORDERS, tep_get_params_order('fusion', $oInfo->orders_id)) . '">' . tep_image_button('button_fusion.gif', 'Fusionner toutes les commandes de '.$oInfo->customers_name.' avec la même date de livraison et le même statut') . '</a>'); 
  }

  /*
  $contents[] = array('align' => 'center',
  'text' =>
  '<a href="' . tep_href_link(FILENAME_ORDERS_INVOICE, 'oID=' . $oInfo->orders_id) . '" TARGET="_blank">' . tep_image_button('button_invoice.gif', IMAGE_ORDERS_INVOICE) . '</a>
  <a href="' . tep_href_link(FILENAME_ORDERS_PACKINGSLIP, 'oID=' . $oInfo->orders_id) . '" TARGET="_blank">' . tep_image_button('button_packingslip.gif', IMAGE_ORDERS_PACKINGSLIP) . '</a><br>';
  '<a href="' . tep_href_link(FILENAME_ORDERS, tep_get_all_get_params(array('oID', 'action')) . 'oID=' . $oInfo->orders_id . '&action=deleteall') . '"><big><big><b>TOUT EFFACER</b></big></big></a>');
  */

  $contents[] = array('text' => '<br>' . TEXT_DATE_ORDER_CREATED . ' ' . tep_date_short($oInfo->date_purchased));
  if (tep_not_null($oInfo->last_modified)) $contents[] = array('text' => TEXT_DATE_ORDER_LAST_MODIFIED . ' ' . tep_date_short($oInfo->last_modified));
    $contents[] = array('text' => '<br>' . TEXT_INFO_PAYMENT_METHOD . ' '  . $oInfo->payment_method);
  }
  break;
}

if ( (tep_not_null($heading)) && (tep_not_null($contents)) ) {
echo '            <td width="25%" valign="top">' . "\n";

$box = new box;
echo $box->infoBox($heading, $contents);

echo '            </td>' . "\n";
}
?>
</tr>
</table></td>
</tr>
<?
}
?>
</table></td>
<!-- body_text_eof //-->
</tr>
</table>
<!-- body_eof //-->

<!-- footer //-->
<? require($admin_FS_path . DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<br>
</body>
</html>
<? require($admin_FS_path . DIR_WS_INCLUDES . 'application_bottom.php'); ?>