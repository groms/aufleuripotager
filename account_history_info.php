<?php
/*
  $Id: account_history_info.php,v 1.100 2003/06/09 23:03:52 hpdl Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 osCommerce

  Released under the GNU General Public License
*/

  require('includes/application_top.php');


  if (!tep_session_is_registered('customer_id')) {
    $navigation->set_snapshot();
    tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
  }
  if (!isset($HTTP_GET_VARS['order_id']) || (isset($HTTP_GET_VARS['order_id']) && !is_numeric($HTTP_GET_VARS['order_id']))) {
    tep_redirect(tep_href_link(FILENAME_ACCOUNT_HISTORY, '', 'SSL'));
  }

  $customer_info_query = tep_db_query("select customers_id from " . TABLE_ORDERS . " where orders_id = '". (int)$HTTP_GET_VARS['order_id'] . "'");
  $customer_info = tep_db_fetch_array($customer_info_query);
  if ($customer_info['customers_id'] != $customer_id) {
    tep_redirect(tep_href_link(FILENAME_ACCOUNT_HISTORY, '', 'SSL'));
  }
  
  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_ACCOUNT_HISTORY_INFO);

  $breadcrumb->add(NAVBAR_TITLE_1, tep_href_link(FILENAME_ACCOUNT, '', 'SSL'));
  $breadcrumb->add(NAVBAR_TITLE_2, tep_href_link(FILENAME_ACCOUNT_HISTORY, '', 'SSL'));
  $breadcrumb->add(sprintf(NAVBAR_TITLE_3, $HTTP_GET_VARS['order_id']), tep_href_link(FILENAME_ACCOUNT_HISTORY_INFO, 'order_id=' . $HTTP_GET_VARS['order_id'], 'SSL'));

  // on doit crÈer l'objet order aprËs les modifications pour Ítre s˚r que l'affichage soit OK aprËs la modifs
  require(DIR_WS_CLASSES . 'order.php');
  $order = new order($HTTP_GET_VARS['order_id']);
  $recurrent_order = ($order->info['orders_status_id'] == '4')||($order->info['orders_status_id'] < '0');

  $adminMode = "";
  require($doc_root . $subpath . DIR_WS_FUNCTIONS . 'shared.php');  

  if ($reload) {
    reloadSMS(-1, FILENAME_ACCOUNT_HISTORY_INFO);
  }

  require('./account_history_info_table.php');
  
 
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<base href="<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
<link rel="stylesheet" type="text/css" href="stylesheet.css">
<script>
  function removeComma(field) {
    field.value = field.value.replace(/,/g,'');
  }
</script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0">
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<!-- body //-->
<table border="0" width="100%" cellspacing="3" cellpadding="3">
  <tr>
    <td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">
<!-- left_navigation //-->
<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>
<!-- left_navigation_eof //-->
    </table></td>
<!-- body_text //-->
    <td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_image(DIR_WS_IMAGES . 'table_background_history.gif', HEADING_TITLE, HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main" colspan="2"><?php 
            
              if ($recurrent_order) { 
                $order_type = 'Commande permanente (rÈcurrente)'; 
              } else {
                $order_type = 'Commande ponctuelle'; 
              }

              if ($order->info['group_id']>0) {
                $order_gid = " <b>G</b>roupement d'<b>A</b>chat"; 
              } else {
                $order_gid = " <b>V</b>ente <b>D</b>irecte"; 
              }
            
              echo '<span class="messageBoxBig"><big><big>'.sprintf(HEADING_ORDER_NUMBER, $HTTP_GET_VARS['order_id']) . '</big></big></span>
              <br><big><b>RÈseau :</b> ' . $order_gid . '</big>
              <br><b>Date/heure :</b> ' . ucwords(getFormattedLongDate($order->info['date_purchased'], true)).' - '. date("H:i:s", strtotime($order->info['date_purchased'])). '
              <br><b>Etat :</b> <span class="'.getBgColorClass($order->info['orders_status_id'], true).'">' . ucwords($order->info['orders_status']).'</span>
              <br><b>Type :</b> ' . ucwords($order_type) . '
              <br><br>';
              
             ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
<?php
  if ($order->delivery != false) {
?>
            <td width="30%" valign="top">
            <table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td class="main"><b><?php echo HEADING_BILLING_ADDRESS; ?></b></td>
              </tr>
              <tr>
                <td class="main"><?php echo tep_address_format(6 /*$order->billing['format_id']*/, $order->billing, 1, ' ', '<br>'); ?></td>
              </tr>
              <tr>
                <td class="main"><b><?php echo HEADING_DELIVERY_ADDRESS; ?></td>
              </tr>
              <tr>
                <td class="main"><?php echo "Aufleuripotager"; ?></td>
              </tr>
              <tr>
                <td class="main"><b><?php echo HEADING_PAYMENT_METHOD; ?></b></td>
              </tr>
              <tr>
                <td class="main"><?php echo $order->info['payment_method']." (à chaque producteur)"; ?></td>
              </tr>
            </table>

<?
/* ancien code
    echo '
            <table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td class="main"><b>'.HEADING_DELIVERY_ADDRESS.'</b><br><i><small>(si diffÈrente de l\'adresse de facturation)</small></i></td>
              </tr>
              <tr>
                <td class="main">'.tep_address_format(6, $order->delivery, 1, ' ', '<br>').'</td>
              </tr>';
    if (tep_not_null($order->info['shipping_method'])) {
      echo '
    
              <tr>
                <td class="main"><b>'.HEADING_SHIPPING_METHOD.'</b></td>
              </tr>
              <tr>
                <td class="main">'.$order->info['shipping_method'].'</td>
              </tr>';
    }
      echo '
            </table>';
*/
?>


            </td>
<?php
  }
?>
            <td width="<?php echo (($order->delivery != false) ? '70%' : '100%'); ?>" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="0">
              <tr>
                <td>
<?
   
  echo $table;
  
?>
                </td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
<? 
  echo $validated_orders_table;
  ?>
      
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td><hr></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>

<?php
  echo $history_table;
  if (DOWNLOAD_ENABLED == 'true') include(DIR_WS_MODULES . 'downloads.php');
?>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td><?php 
                if (($recurrent_order)&&($order->info['orders_status_id']>=0)) {
echo '<a href="' . tep_href_link(FILENAME_RECURRENT_ORDER, 'order_id='.$HTTP_GET_VARS['order_id'].'&action=delete', 'SSL') . '">' . tep_image_button('button_delete.gif', IMAGE_BUTTON_DELETE_RECURRENT_ORDER) . '</a>';
                }?>
                </td>
                <td align="left" width="100%"><?php 
                echo '<a href="' . tep_href_link(FILENAME_ACCOUNT, tep_get_all_get_params(array('order_id')), 'SSL') . '">' . tep_image_button('button_back.gif', IMAGE_BUTTON_BACK) . '</a>'; ?>
                <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
    </table></td>
<!-- body_text_eof //-->
    <td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">
<!-- right_navigation //-->
<?php require(DIR_WS_INCLUDES . 'column_right.php'); ?>
<!-- right_navigation_eof //-->
    </table></td>
  </tr>
</table>
<!-- body_eof //-->

<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<br>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
