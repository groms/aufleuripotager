<?
/*
  $Id: orderlist.php, v5.1 2007/02/06 15:52:31 insomniac2 Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  New Improved OrderList by insomniac2 www.terracomp.ca

  Copyright (c) 2002 osCommerce

  Released under the GNU General Public License
*/
// Buffering
  ob_start();

  require('includes/application_top.php');

  // lets find out todays date so that if you ever wanted to print this thing,
  // the date will display on the output
 $today=date("Y/m/d");

  require($admin_FS_path . DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();

  include($admin_FS_path . DIR_WS_CLASSES . 'order.php');

  $orders_statuses = array();
  $orders_status_array = array();
  $orders_status_query = tep_db_query("select os.orders_status_id, os.orders_status_name from " . TABLE_ORDERS_STATUS . " os where os.language_id = '" . (int)$languages_id . "' order by os.orders_status_id");
  while ($orders_status = tep_db_fetch_array($orders_status_query)) {
    $orders_statuses[] = array('id' => $orders_status['orders_status_id'],
                               'text' => $orders_status['orders_status_name']);
    $orders_status_array[$orders_status['orders_status_id']] = $orders_status['orders_status_name'];
  }

  $status = (int)$_GET['status'];

  if ($status == 0)  {
    $os = '';
  } else {
    $os = " and o.orders_status = '" . $status . "' ";
  }

  if ($_GET['month'] == '') {
    $day1 = date("d");
  	$month = date("m");
    $year = '20' . date("y")-1; // added -1 to set base year

    $date1 = $year . "-" . $month . "-" . "$day1";

    $day22 = date("d");
    $month22 = date("m");
    $year22 = '20'. date("y");

    $date2 = $year22 . "-" . $month22 . "-" . "$day22";

  } else {
    $day1 = $_GET['day'];
    $month = $_GET['month'];
    $year = $_GET['year'];

    $date1 = $year . "-" . $month . "-" . "$day1";

    $day22 = $_GET['day2'];
    $month22 = $_GET['month2'];
    $year22 = $_GET['year2'];

    $date2 = $year22 . "-" . $month22 . "-" . "$day22";
  }

   $day = array();
   $day[] = array('id' => 1, 'text' => '1');
   $day[] = array('id' => 2, 'text' => '2');
   $day[] = array('id' => 3, 'text' => '3');
   $day[] = array('id' => 4, 'text' => '4');
   $day[] = array('id' => 5, 'text' => '5');
   $day[] = array('id' => 6, 'text' => '6');
   $day[] = array('id' => 7, 'text' => '7');
   $day[] = array('id' => 8, 'text' => '8');
   $day[] = array('id' => 9, 'text' => '9');
   $day[] = array('id' => 10, 'text' => '10');
   $day[] = array('id' => 11, 'text' => '11');
   $day[] = array('id' => 12, 'text' => '12');
   $day[] = array('id' => 13, 'text' => '13');
   $day[] = array('id' => 14, 'text' => '14');
   $day[] = array('id' => 15, 'text' => '15');
   $day[] = array('id' => 16, 'text' => '16');
   $day[] = array('id' => 17, 'text' => '17');
   $day[] = array('id' => 18, 'text' => '18');
   $day[] = array('id' => 19, 'text' => '19');
   $day[] = array('id' => 20, 'text' => '20');
   $day[] = array('id' => 21, 'text' => '21');
   $day[] = array('id' => 22, 'text' => '22');
   $day[] = array('id' => 23, 'text' => '23');
   $day[] = array('id' => 24, 'text' => '24');
   $day[] = array('id' => 25, 'text' => '25');
   $day[] = array('id' => 26, 'text' => '26');
   $day[] = array('id' => 27, 'text' => '27');
   $day[] = array('id' => 28, 'text' => '28');
   $day[] = array('id' => 29, 'text' => '29');
   $day[] = array('id' => 30, 'text' => '30');
   $day[] = array('id' => 31, 'text' => '31');

  $months = array();
  $months[] = array('id' => 1, 'text' => MONTH_JANUARY);
  $months[] = array('id' => 2, 'text' => MONTH_FEBRUARY);
  $months[] = array('id' => 3, 'text' => MONTH_MARCH);
  $months[] = array('id' => 4, 'text' => MONTH_APRIL);
  $months[] = array('id' => 5, 'text' => MONTH_MAY);
  $months[] = array('id' => 6, 'text' => MONTH_JUNE);
  $months[] = array('id' => 7, 'text' => MONTH_JULY);
  $months[] = array('id' => 8, 'text' => MONTH_AUGUST);
  $months[] = array('id' => 9, 'text' => MONTH_SEPTEMBER);
  $months[] = array('id' => 10, 'text' => MONTH_OCTOBER);
  $months[] = array('id' => 11, 'text' => MONTH_NOVEMBER);
  $months[] = array('id' => 12, 'text' => MONTH_DECEMBER);

  $years = array();
  $years[] = array('id' => 2006, 'text' => '2006');
  $years[] = array('id' => 2007, 'text' => '2007');
  $years[] = array('id' => 2008, 'text' => '2008');
  $years[] = array('id' => 2009, 'text' => '2009');
  $years[] = array('id' => 2010, 'text' => '2010');
  $years[] = array('id' => 2011, 'text' => '2011');
  $years[] = array('id' => 2012, 'text' => '2012');
  $years[] = array('id' => 2013, 'text' => '2013');

  $day2 = array();
   $day2[] = array('id' => 1, 'text' => '1');
   $day2[] = array('id' => 2, 'text' => '2');
   $day2[] = array('id' => 3, 'text' => '3');
   $day2[] = array('id' => 4, 'text' => '4');
   $day2[] = array('id' => 5, 'text' => '5');
   $day2[] = array('id' => 6, 'text' => '6');
   $day2[] = array('id' => 7, 'text' => '7');
   $day2[] = array('id' => 8, 'text' => '8');
   $day2[] = array('id' => 9, 'text' => '9');
   $day2[] = array('id' => 10, 'text' => '10');
   $day2[] = array('id' => 11, 'text' => '11');
   $day2[] = array('id' => 12, 'text' => '12');
   $day2[] = array('id' => 13, 'text' => '13');
   $day2[] = array('id' => 14, 'text' => '14');
   $day2[] = array('id' => 15, 'text' => '15');
   $day2[] = array('id' => 16, 'text' => '16');
   $day2[] = array('id' => 17, 'text' => '17');
   $day2[] = array('id' => 18, 'text' => '18');
   $day2[] = array('id' => 19, 'text' => '19');
   $day2[] = array('id' => 20, 'text' => '20');
   $day2[] = array('id' => 21, 'text' => '21');
   $day2[] = array('id' => 22, 'text' => '22');
   $day2[] = array('id' => 23, 'text' => '23');
   $day2[] = array('id' => 24, 'text' => '24');
   $day2[] = array('id' => 25, 'text' => '25');
   $day2[] = array('id' => 26, 'text' => '26');
   $day2[] = array('id' => 27, 'text' => '27');
   $day2[] = array('id' => 28, 'text' => '28');
   $day2[] = array('id' => 29, 'text' => '29');
   $day2[] = array('id' => 30, 'text' => '30');
   $day2[] = array('id' => 31, 'text' => '31');

  $months2 = array();
  $months2[] = array('id' => 1, 'text' => MONTH_JANUARY);
  $months2[] = array('id' => 2, 'text' => MONTH_FEBRUARY);
  $months2[] = array('id' => 3, 'text' => MONTH_MARCH);
  $months2[] = array('id' => 4, 'text' => MONTH_APRIL);
  $months2[] = array('id' => 5, 'text' => MONTH_MAY);
  $months2[] = array('id' => 6, 'text' => MONTH_JUNE);
  $months2[] = array('id' => 7, 'text' => MONTH_JULY);
  $months2[] = array('id' => 8, 'text' => MONTH_AUGUST);
  $months2[] = array('id' => 9, 'text' => MONTH_SEPTEMBER);
  $months2[] = array('id' => 10, 'text' => MONTH_OCTOBER);
  $months2[] = array('id' => 11, 'text' => MONTH_NOVEMBER);
  $months2[] = array('id' => 12, 'text' => MONTH_DECEMBER);

  $years2 = array();
  $years2[] = array('id' => 2004, 'text' => '2006');
  $years2[] = array('id' => 2005, 'text' => '2007');
  $years2[] = array('id' => 2006, 'text' => '2008');
  $years2[] = array('id' => 2007, 'text' => '2009');
  $years2[] = array('id' => 2008, 'text' => '2010');
  $years2[] = array('id' => 2009, 'text' => '2011');
  $years2[] = array('id' => 2010, 'text' => '2012');
  $years2[] = array('id' => 2011, 'text' => '2013');

  require($admin_FS_path . DIR_WS_LANGUAGES . $language . '/' . FILENAME_ORDERLIST);
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <? echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<? echo CHARSET; ?>">
<title>&nbsp;<? echo BROWSER_TITLE . ' - ' . TITLE ?></title>
<br>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css" media="all" >
<script language="javascript" src="includes/general.js"></script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
<!-- header //-->
<?
  if ($printable != 'on') {
  require($admin_FS_path . DIR_WS_INCLUDES . 'header.php');
  }; ?>
<!-- header_eof //-->

<!-- body //-->
<table border="0" width="100%" cellspacing="2" cellpadding="2">
 <br>
<!-- body_text //-->
    <td width="100%" valign="top"><table border="0" align="center" width="95%" cellspacing="0" cellpadding="2">
      <tr>
        <td><? echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
        <td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading" width="50%"><?

			/// BEGIN CRAZY!!! LOGIC FOR 3 TITLES
			if ($orderlist_days > 0 && $orderlist_days != NULL && !isset($_GET['month']) ) {

			  if ($orderlist_days < 2 && !isset($_GET['month']) )
				echo TEXT_ORDERS_TODAY;
			  else
				echo TEXT_ORDERS_FOR_PAST . ' ' . $orderlist_days . ' ' . TEXT_DAYS;

			  } else {

			  if (isset($status)) {
			  // SHOW CURRENT SELECTED ORDER STATUS TO DISPLAY
			  $query_status = "select * from orders_status where orders_status_id = '" . $status . "' and language_id = '" . (int)$languages_id . "'";
			  $status_result = tep_db_query($query_status);

			    while ($order_status = tep_db_fetch_array($status_result)) {
				  echo TEXT_ORDERS_WITH_STATUS . ' ' . $order_status['orders_status_name'] . '</font>';
			    }
			  }

			  if (!isset($_GET['month']) && $status =='' && !$orderlist_days) { echo HEADING_TITLE; }
			  if (isset($_GET['month']) ) { echo HEADING_TITLE . '<font color="0000FF"> : ' . $date1 . ' to ' . $date2 . '</font>'; }
			}
			/// END CRAZY!!! LOGIC FOR 3 TITLES
			?></td>
			<td class="main" align="right" width="25%"><? echo tep_date_long($today); ?></td>
			<td class="main" align="right" width="25%"><a href="javascript:history.back(-1);"><? echo tep_image_button('button_back.gif', IMAGE_BACK, 'align="absmiddle"') . '</a>&nbsp;&nbsp;<a href="' . tep_href_link(FILENAME_ORDERS, tep_get_all_get_params(array('action'))) . '">' . tep_image_button('button_orders.gif', IMAGE_ORDERS) . '</a>'; ?>&nbsp;</td>
			</tr>

		  </table></td>
        </tr>

		<tr>
          <td><? echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
        </tr>

<?
    $search = '';
    if (isset($_GET['search']) && tep_not_null($_GET['search'])) {
      $keywords = tep_db_input(tep_db_prepare_input($_GET['search']));
	  //$keywords = trim($_GET['keywords']); // not sure what trim does here

	  $keywords = "o.orders_id like '%" . $keywords . "%' or o.customers_name like '%" . $keywords . "%' or o.customers_id like '%" . $keywords . "%' or o.customers_company like '%" . $keywords . "%' or o.customers_street_address like '%" . $keywords . "%' or o.customers_suburb like '%" . $keywords . "%' or o.customers_city like '%" . $keywords . "%' or o.customers_postcode like '%" . $keywords . "%' or o.customers_state like '%" . $keywords . "%' or o.customers_country like '%" . $keywords . "%' or o.customers_telephone like '%" . $keywords . "%' or o.customers_email_address like '%" . $keywords . "%' or o.delivery_name like '%" . $keywords . "%' or o.delivery_company like '%" . $keywords . "%' or o.delivery_street_address like '%" . $keywords . "%' or o.delivery_suburb like '%" . $keywords . "%' or o.delivery_city like '%" . $keywords . "%' or o.delivery_postcode like '%" . $keywords . "%' or o.delivery_state like '%" . $keywords . "%' or o.delivery_country like '%" . $keywords . "%' or o.billing_name like '%" . $keywords . "%' or o.billing_company like '%" . $keywords . "%' or o.billing_street_address like '%" . $keywords . "%' or o.billing_suburb like '%" . $keywords . "%' or o.billing_city like '%" . $keywords . "%' or o.billing_postcode like '%" . $keywords . "%' or o.billing_state like '%" . $keywords . "%' or o.billing_country like '%" . $keywords . "%' or o.payment_method like '%" . $keywords . "%' or o.cc_number like '%" . $keywords . "%' ";
    }

    if (isset($_GET['cID'])) {
      $cID = tep_db_prepare_input($_GET['cID']);

      $orders_query_raw = "select o.orders_id, o.customers_id, o.customers_name, o.customers_email_address, o.payment_method, o.date_purchased, o.last_modified, o.currency, o.currency_value, s.orders_status_name, ot.text as order_total from " . TABLE_ORDERS . " o left join " . TABLE_ORDERS_TOTAL . " ot on (o.orders_id = ot.orders_id), " . TABLE_ORDERS_STATUS . " s where o.customers_id = '" . $_GET['cID'] . "' and o.orders_status = s.orders_status_id and s.language_id = '" . (int)$languages_id . "' and ot.class = 'ot_total' order by o.orders_id DESC";

	} elseif (isset($_GET['search']) && tep_not_null($_GET['search'])) {
	  //$oID = tep_db_prepare_input($_GET['oID']);

	  $orders_query_raw = "select distinct * from " . TABLE_ORDERS . " o where " . $keywords . " order by o.orders_id DESC";

	  //$orders_query_raw = "select distinct o.orders_id, u.orders_id, u.customers_name, u.customers_id, o.customers_acct, o.customers_name, o.customers_email_address, o.customers_id, u.orders_shipping_tracking_no, u.orders_shipping_date_shipped, u.cc_number, u.payment_type, u.payment_ref, o.customers_company, o.customers_street_address, o.customers_suburb, o.customers_city, o.customers_postcode, o.customers_state, o.customers_country, o.customers_telephone, o.billing_telephone, o.customers_email_address, o.delivery_name, o.delivery_company, o.delivery_street_address, o.delivery_suburb, o.billing_city, o.billing_postcode, o.billing_state, o.billing_country, o.billing_telephone, o.delivery_telephone, o.payment_method, o.date_purchased, o.cc_number, o.purchase_order_number, o.po_requested_by, o.po_contact_person, o.ip from " . TABLE_SPG_ORDERS_SHIPPING . " u left join " . TABLE_ORDERS . " o on u.orders_id = o.orders_id where " . $keywords . " order by o.orders_id DESC";

	} elseif (isset($_GET['status']) && is_numeric($_GET['status']) && ($_GET['status'] > 0)) { // osc update
      //$status = tep_db_prepare_input($_GET['status']);

      $orders_query_raw = "select *, ot.text as order_total from " . TABLE_ORDERS . " o left join " . TABLE_ORDERS_TOTAL . " ot on (o.orders_id = ot.orders_id), " . TABLE_ORDERS_STATUS . " s where o.orders_status = s.orders_status_id and s.language_id = '" . (int)$languages_id . "' and s.orders_status_id = '" . (int)$status . "' and ot.class = 'ot_total' order by o.orders_id DESC";

	} elseif (isset($orderlist_days) && !empty($orderlist_days) ) {

	  if (!isset($orderlist_days)) $orderlist_days = ORDERLIST_DAYS;

	  $orders_query_raw = "select *, ot.text as order_total from " . TABLE_ORDERS . " o left join " . TABLE_ORDERS_TOTAL . " ot on (o.orders_id = ot.orders_id), " . TABLE_ORDERS_STATUS . " s where o.orders_status = s.orders_status_id and TO_DAYS(NOW()) - TO_DAYS(o.date_purchased) < '" . $orderlist_days . "' and s.language_id = '" . (int)$languages_id . "' and ot.class = 'ot_total' order by o.orders_id DESC";

	} elseif (isset($_GET['month']) ) {

	  $orders_query_raw = "select *, ot.text as order_total from " . TABLE_ORDERS . " o left join " . TABLE_ORDERS_TOTAL . " ot on (o.orders_id = ot.orders_id), " . TABLE_ORDERS_STATUS . " s where o.orders_status = s.orders_status_id " . $os . " and o.date_purchased between '" . $date1 . "' and '" . $date2 . "' and s.language_id = '" . (int)$languages_id . "' and ot.class = 'ot_total' order by o.orders_id DESC";

	} else {
      $orders_query_raw = "select *, ot.text as order_total from " . TABLE_ORDERS . " o left join " . TABLE_ORDERS_TOTAL . " ot on (o.orders_id = ot.orders_id), " . TABLE_ORDERS_STATUS . " s where o.orders_status = s.orders_status_id and s.language_id = '" . (int)$languages_id . "' and o.orders_status = '' and ot.class = 'ot_total' order by o.orders_id DESC";
    }

	$result = tep_db_query($orders_query_raw);
	$records = mysql_num_rows($result);
?>

	    <tr>
         <td><table border="0" width="100%" cellspacing="0" cellpadding="0"><? echo tep_draw_form('status', FILENAME_ORDERLIST, tep_get_all_get_params(array('action')), 'post', ''); ?>
           <tr>
		     <!--// FORM THAT LETS YOU SELECT ORDERS WITHIN A TIME FRAME -->
			 <? echo tep_draw_form('date_range', FILENAME_ORDERLIST, '', 'get'); ?>
				 <td class="smallText" align="left">
			     <? echo TEXT_DATE_RANGE_FILTER; ?>&nbsp;&nbsp;&nbsp;<? echo TEXT_FROM . '&nbsp;' . tep_draw_pull_down_menu('month', $months, $month, 'onchange=\'this.form.submit();\'') . tep_draw_pull_down_menu('day', $day, $day1, 'onchange=\'this.form.submit();\'') . tep_draw_pull_down_menu('year', $years, $year, 'onchange=\'this.form.submit();\''); ?>&nbsp;&nbsp;&nbsp;
				 <? echo TEXT_TO . '&nbsp;' . tep_draw_pull_down_menu('month2', $months2, $month22, 'onchange=\'this.form.submit();\'') . tep_draw_pull_down_menu('day2', $day2, $day22, 'onchange=\'this.form.submit();\'') . tep_draw_pull_down_menu('year2', $years, $year22, 'onchange=\'this.form.submit();\''); ?></td>
			  </form>
			     <!--// FORM THAT LETS YOU SEARCH ALL FIELDS IN ORDERS -->
				 <td class="smallText" align="right">
				 <? echo tep_draw_form('filter', FILENAME_ORDERLIST, '', 'get'); ?>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<? echo HEADING_TITLE_SEARCH_FILTER . '&nbsp;&nbsp;' . tep_draw_input_field('search', $keywords, 'size="20" maxlength="60"'); ?>&nbsp;

<?
    if (!isset($_GET['search']) || $_GET['search'] == NULL) echo tep_image_submit('button_submit.gif', IMAGE_SUBMIT, 'align="absmiddle"'); else echo '<a href="' . tep_href_link(FILENAME_ORDERLIST) . '">' . tep_image_button('button_reset.gif', IMAGE_RESET, 'align="absmiddle"') . '</a>&nbsp;';
?></td>
              </form>
			 </tr>
		   </table>
			 <tr>
			   <td><? echo tep_draw_separator('pixel_trans.gif', '1', '5'); ?></td>
			 </tr>
			 <tr>
             <td valign="top"><table border="0" align="center" width="100%" cellspacing="0" cellpadding="5">
               <tr>
			     <td class="smallText" align="left" valign="bottom"><? if (isset($_GET['search'])) { echo 'Search Mode : '; } if ($records == 1) echo $records . ' ' . TEXT_RECORD_FOUND; if ($records >= 2) echo $records . ' ' . TEXT_RECORDS_FOUND; if ($records == 0) echo TEXT_NO_RECORDS; ?></td>

			   <!--// FORM THAT LETS YOU SELECT ORDERS IN PAST HOW MANY DAYS -->
			   <? echo tep_draw_form('orderlist_days', FILENAME_ORDERLIST, '', 'get'); ?>
			     <td class="smallText" align="right"><? echo HEADING_TITLE_DAYS . '&nbsp;&nbsp;' . tep_draw_input_field('orderlist_days', '', 'size="3" maxlength="4"'); ?>&nbsp;&nbsp;<?
				 if (!isset($_GET['orderlist_days']) || $_GET['orderlist_days'] == NULL) echo tep_image_submit('button_submit.gif', IMAGE_SUBMIT, 'align="absmiddle"'); else echo '<a href="' . tep_href_link(FILENAME_ORDERLIST) . '">' . tep_image_button('button_reset.gif', IMAGE_RESET, 'align="absmiddle"') . '</a>';
			   ?>&nbsp;&nbsp;&nbsp;or&nbsp;&nbsp;&nbsp;
			 </form>

			   <!--// FORM THAT LETS YOU SELECT WHICH ORDER STATUS TO DISPLAY -->
			   <? echo tep_draw_form('status', FILENAME_ORDERLIST, '', 'get'); ?>
                <? echo HEADING_TITLE_STATUS . '&nbsp;&nbsp;' . tep_draw_pull_down_menu('status', array_merge(array(array('id' => '', 'text' => TEXT_SELECT_STATUS)), $orders_statuses), '', 'onChange="this.form.submit();"'); ?>&nbsp;&nbsp;<?
				if (isset($_GET['status'])) { echo '<a href="' . tep_href_link(FILENAME_ORDERLIST) . '">' . tep_image_button('button_reset.gif', IMAGE_RESET, 'align="absmiddle"') . '</a>'; } ?>
			 </form>

<!--// BEGIN customers drop down selection list //-->
<?
  $customers_query = tep_db_query("select customers_id, customers_firstname, customers_lastname from " . TABLE_CUSTOMERS . " order by customers_firstname ASC");

        while ($customers = tep_db_fetch_array($customers_query)) {
        	   $customers_firstname = $customers['customers_firstname'];
			   $customers_lastname = $customers['customers_lastname'];
        	   $customers_array[] = array('id' => $customers['customers_id'],
										  'text' => $customers_firstname . ' ' . $customers_lastname);
		}
?>
                &nbsp;or&nbsp;&nbsp;&nbsp;<? echo tep_draw_form('customers', FILENAME_ORDERLIST, '', 'get') . HEADING_TITLE_CUSTOMER_SELECT . ' ' . tep_draw_pull_down_menu('cID', array_merge(array(array('id' => '', 'text' => TEXT_SELECT_CLIENT)), $customers_array), (isset($_GET['customers_id']) ? $_GET['cID'] : ''), 'onChange="this.form.submit();"'); ?>
			 </form>
<?
    if (isset($_GET['cID']) && tep_not_null($_GET['cID'])) {
?>
            <? echo '<a href="' . tep_href_link(FILENAME_ORDERLIST) . '">' . tep_image_button('button_reset.gif', IMAGE_RESET, 'align="absmiddle"') . '</a>'; ?>
<?
    }
?>
<!--// END customers drop down selection list //-->


			   <? if ($records > 0) echo '&nbsp;&nbsp;&nbsp;' . tep_image_submit('button_print_this.gif', IMAGE_BUTTON_PRINTABLE, 'align="absmiddle" onclick="window.print()"') . '&nbsp;' . ENTRY_PRINTABLE . ' ' . tep_draw_checkbox_field('printable', $print, 'checked');
			   ?></td>

           </tr>
        </table></td>
      </tr>

      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0" >
          <tr>
            <td valign="top"><table border="1" align="center" width="100%" cellspacing="0" cellpadding="5">
              <tr class="dataTableRow">
                <td class="main" width="10%"><b><? echo TABLE_HEADING_INVOICE_NUMBER; ?></b></td>
				<td class="main" width="11%"><? echo TABLE_HEADING_DATE; ?></td>
				<td class="main" width="12%"><? echo TABLE_HEADING_ORDER_STATUS; ?></td>
				<td class="main" width="30%"><? echo TABLE_HEADING_CLIENT_DETAILS; ?></td>
                <td class="main" width="47%"><? echo TABLE_HEADING_ORDERS_DETAILS; ?></td>
              </tr>

<?
  $rows = 0;
  $orders_split = new splitPageResults($_GET['page'], MAX_DISPLAY_SEARCH_RESULTS, $orders_query_raw, $orders_query_numrows);
  $orders_query = tep_db_query($orders_query_raw);

  while ($orders = tep_db_fetch_array($orders_query)) {

  $rows ++;

	if ((!isset($_GET['oID']) || (isset($_GET['oID']) && ($_GET['oID'] == $orders['orders_id']))) && !isset($oInfo)) {
        $oInfo = new objectInfo($orders);
    }

  if (strlen($rows) < 2) {
    $rows = '0' . $rows;
  }

  $order = new order($orders['orders_id']);
?>
           <tr>
             <td class="smallText" align="left" valign="top">
             <? echo '<br> <a href="' . tep_href_link(FILENAME_ORDERS, tep_get_all_get_params(array('oID', 'action')) . 'oID=' . (int)$orders['orders_id'] . '&action=edit') . '" target="_NEW">' . tep_image(DIR_WS_ICONS . 'show_order.gif', ICON_PREVIEW); ?></a>&nbsp;<? echo '<a href="' . tep_href_link(FILENAME_ORDERS, tep_get_all_get_params(array('oID', 'action')) . 'oID=' . (int)$orders['orders_id'] . '&action=edit') . '" target="_NEW"><b>' . '&nbsp;' . (int)$orders['orders_id']; ?></b></a>
		   </td>

             <td class="smallText" align="center" valign="top"><br>
               <? echo tep_datetime_short($orders['date_purchased']); ?></td>

			 <td class="smallText" align="left" valign="top"><br>
               <? echo $orders['orders_status_name']; ?></td>

             <td class="smallText" align="left" valign="top"><br>
			   <? echo OL_ACCT . '&nbsp;' . $orders['customers_acct'] . '<br>';
					 echo OL_COMPANY . '&nbsp;' . $orders['customers_company'] . '<br><br>';

					 echo OL_CUSTOMERNAME . '&nbsp;' . $orders['customers_name'] . '<br>';
					 echo OL_ADDRESS . '&nbsp;' . $orders['customers_street_address'];
					 echo OL_ADDRESS2 . '&nbsp;' . $orders['customers_street_address2'] . '<br>';
					 echo OL_SUBURB . '&nbsp;' . $orders['customers_suburb'] . '<br>';
					 echo OL_LOCATION; ?>&nbsp;<? echo $orders['customers_city'] . ', ' . $orders['customers_state'] . ', ' . $orders['customers_country'] . '<br>';
					 echo OL_POSTCODE . '&nbsp;' . $orders['customers_postcode'] . '<br>';
					 echo OL_TEL . '&nbsp;' . $orders['customers_telephone'] . '<br><br>';

					 echo OL_BILLING_NAME . '&nbsp;' . $orders['billing_name'] . '<br>';
					 echo OL_BILLING_ADDRESS . '&nbsp;' . $orders['billing_street_address'];
					 echo OL_BILLING_ADDRESS2 . '&nbsp;' . $orders['billing_street_address2'] . '<br>';
					 echo OL_BILLING_SUBURB . '&nbsp;' . $orders['billing_suburb'] . '<br>';
					 echo OL_LOCATION; ?>&nbsp;<? echo $orders['billing_city'] . ', ' . $orders['billing_state'] . ', ' . $orders['billing_country'] . '<br>';
					 echo OL_BILLING_POSTCODE . '&nbsp;' . $orders['billing_postcode'] . '<br>';
					 //echo OL_BILLING_TEL . '&nbsp;' . $orders['billing_telephone'] . '<br><br>';

					 echo OL_DELIVERY_NAME . '&nbsp;' . $orders['delivery_name'] . '<br>';
					 echo OL_DELIVERY_ADDRESS . '&nbsp;' . $orders['delivery_street_address'];
					 echo OL_DELIVERY_ADDRESS2 . '&nbsp;' . $orders['delivery_street_address2'] . '<br>';
					 echo OL_DELIVERY_SUBURB . '&nbsp;' . $orders['delivery_suburb'] . '<br>';
					 echo OL_LOCATION; ?>&nbsp;<? echo$orders['delivery_city'] . ', ' . $orders['delivery_state'] . ', ' . $orders['delivery_country'] . '<br>';
					 echo OL_DELIVERY_POSTCODE . '&nbsp;' . $orders['delivery_postcode'] . '<br>';
					 //echo OL_DELIVERY_TEL . '&nbsp;' . $orders['delivery_telephone'] . '<br><br>';

					 echo OL_FAX . '&nbsp;' . $orders['customers_fax'] . '<br>';
					 echo OL_EMAIL . '&nbsp;' . $orders['customers_email_address']; ?>
             </td>

			 <td class="smallText" valign="top">

<?
   for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {

	  // BEGIN associate Manufacturer To Product
	    $v_query = tep_db_query("select manufacturers_id from " . TABLE_PRODUCTS . " where products_id = '" . $order->products[$i]['id'] . "'");
		$v = tep_db_fetch_array($v_query);
	  // Select appropriate Manufacturers Name
		$mfg_query = tep_db_query("select manufacturers_id, manufacturers_name from " . TABLE_MANUFACTURERS . " where manufacturers_id = '" . $v['manufacturers_id'] . "'");
		$mfg = tep_db_fetch_array($mfg_query);
	  // END associate Manufacturer To Product
?>
            <b><a href="<? echo tep_href_link(FILENAME_CATEGORIES, 'action=new_product_preview&read=only&pID=' . $order->products[$i]['id'] . '&origin=' . FILENAME_ORDERLIST . '&oID=' . (int)$orders['orders_id']); ?>"><? echo '<br><font color="0000FF">' . $order->products[$i]['qty'] . '&nbsp;x ' . $order->products[$i]['name']; ?></font></a></b><br>
<?
			if (tep_not_null($mfg['manufacturers_id'])) {
				echo TEXT_MANUFACTURER . ' ' . $mfg['manufacturers_name'] . '<br>';
			} else {
				echo TEXT_MANUFACTURER_UNAVAILABLE . '<br>';
			}

				echo TEXT_MODEL . '&nbsp;' . $order->products[$i]['model'] . '<br>';
				//if (tep_not_null($order->products[$i]['upc'])) echo TEXT_PRODUCT_UPC_SKU . '&nbsp;' . $order->products[$i]['upc'] . '<br>';
				//echo TEXT_PRODUCT_SERIAL_NUMBER . '&nbsp;' . $order->products[$i]['serial_number'] . '<br>';
				//echo TEXT_PRODUCT_SOFTWARE_KEY . '&nbsp;' . $order->products[$i]['software_key'] . '<br>';


			// End Show Manufacturer In Listing
			  //$j=0;
			  //while ($products_options = $order->products[$i]['attributes'][$j]['option']) {
				  //$products_options_values = $order->products[$i]['attributes'][$j]['value'];
				  //echo "&nbsp;&nbsp;<font color=\"0000FF\">$products_options</font> = $products_options_values<br>";
				//$j++;
			  //}
			// END MOD: Display Attribs
		   if (isset($order->products[$i]['attributes']) && (sizeof($order->products[$i]['attributes']) > 0)) {
			  for ($j=0, $k=sizeof($order->products[$i]['attributes']); $j<$k; $j++) {
			  $j=0;
				while ($products_options = $order->products[$i]['attributes'][$j]['option']) {
				  echo '&nbsp;&nbsp;<font color="0000FF"><small>' . $products_options . '</font>' . ' = ' . $order->products[$i]['attributes'][$j]['value'];
				  if ($order->products[$i]['attributes'][$j]['price'] != '0') echo ' (' . $order->products[$i]['attributes'][$j]['prefix'] . $currencies->format($order->products[$i]['attributes'][$j]['price'] * $order->products[$i]['qty'], true, $order->info['currency'], $order->info['currency_value']) . ')';
				  echo '</small><br>';
			  $j++;
			}
		  }
		} else if($order->info['purchase_order_number']) {
	  }

   }
					 echo '<br>';
					 echo ENTRY_PAYMENT_METHOD; ?>&nbsp;&nbsp;<? if (tep_not_null($order->info['payment_method'])) echo $order->info['payment_method']; else echo ERROR_NO_METHOD_SET;

			if (tep_not_null($order->info['cc_type']) || tep_not_null($order->info['cc_owner']) || tep_not_null($order->info['cc_number'])) {

					 echo '<br><br>' . ENTRY_CREDIT_CARD_TYPE . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $order->info['cc_type'] . '<br>';
					 echo ENTRY_CREDIT_CARD_OWNER . '&nbsp;&nbsp;&nbsp;' . $order->info['cc_owner'] . '<br>';
					 echo ENTRY_CREDIT_CARD_NUMBER . ' ' . $order->info['cc_number'] . '<br>';
					 echo ENTRY_CREDIT_CARD_CVV_NUMBER . ' ' . $order->info['cc_cvv'] . '<br>';
					 if (tep_not_null($order->info['cc_issue'])) echo ENTRY_CREDIT_CARD_ISSUE_NUMBER . ' ' . $order->info['cc_issue'] . '<br>';
					 if (tep_not_null($order->info['cc_start'])) echo ENTRY_CREDIT_CARD_START . ' ' . $order->info['cc_start'] . '&nbsp;[ MM : YY ]' . '<br>';
					 echo ENTRY_CREDIT_CARD_EXPIRES . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $order->info['cc_expires'] . '&nbsp;[ MM : YY ]';
			}

			//if (tep_not_null($order->info['purchase_order_number']) || tep_not_null($order->info['po_requested_by']) || tep_not_null($order->info['po_contact_person'])) {

					 //echo '<br>' . ENTRY_PURCHASE_ORDER_NUMBER . ' ' . $order->info['purchase_order_number'] . '<br>';
					 //echo ENTRY_PURCHASE_REQUESTED_BY . ' ' . $order->info['po_requested_by'] . '<br>';
					 //echo ENTRY_PURCHASE_CONTACT_PERSON . ' ' . $order->info['po_contact_person'];
			//}

				   for ($i=0, $n=sizeof($order->totals); $i<$n; $i++) {
					 echo '<br><br><align="right">' . $order->totals[$i]['title'] . '&nbsp;' . $order->totals[$i]['text'];
				   }
?>

		<br><br></td>
	  </tr>

	  <tr>
	    <td colspan="5"><table align="center" border="0" width="100%" cellspacing="0" cellpadding="2">
		  <tr>
		  <!--// BEGIN Item Count //-->
		    <td class="smallText" align="right" width="10%"></td>
		    <td class="smallText" align="right" width="11%"></td>
		    <td class="smallText" align="right" width="12%"></td>
		    <td class="smallText" align="right" width="30%"></td>
		    <td class="smallText" align="left" width="47%">&nbsp;<? echo TEXT_TOTAL_ITEMS; ?>
				&nbsp;<? $total_units = 0;
				for($i=0, $j=count($order->products); $i<$j; $i++) {
				$total_units += $order->products[$i]['qty'];
			  }
			  echo $total_units; ?></td>
		  <!--// END Item Count //-->
		  </tr>
	    </table></td>
      </tr>
<?
   }
?>

</table>

	  <tr>
		<td><? echo tep_draw_separator('pixel_trans.gif', '1', '5'); ?></td>
	  </tr>
      <tr>
        <td colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="5">
          <tr>
            <td class="smallText" valign="top"><? echo $orders_split->display_count($orders_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, (int)$_GET['page'], TEXT_DISPLAY_NUMBER_OF_RECORDS); ?></td>

			<!--// navigation -->
            <td class="smallText" align="right" valign="top"><a href="javascript:history.back(-1);"><? echo tep_image_button('button_back.gif', IMAGE_BACK, 'align="absmiddle"') . '</a>&nbsp;&nbsp;<a href="' . tep_href_link(FILENAME_ORDERS, tep_get_all_get_params(array('action'))) . '">' . tep_image_button('button_orders.gif', IMAGE_ORDERS) . '</a>'; ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<? echo $orders_split->display_links($orders_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, (int)$_GET['page'], tep_get_all_get_params(array('page', 'info', 'x', 'y', 'oID'))); ?></td>
          </tr>
        </table></td>
      </tr>

<!-- body_text_eof //-->
  </tr>
</table><br><br>
<!-- body_eof //-->

<!-- footer //-->
<?
  if ($printable != 'on') {
   //require($admin_FS_path . DIR_WS_INCLUDES . 'footer.php');
  }
?>
<!-- footer_eof //-->
</body>
</html>
<?
ob_end_flush();
?>