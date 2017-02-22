<?php
/*
  $Id: special_orders.php, v1.1 groms

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2009 osCommerce

  Released under the GNU General Public License
*/

  if (tep_session_is_registered('customer_id')) {
// retreive the last x products purchased

    $info_box_contents = array();
    switch ($so_type) {
      case "norec":
        $info_box_contents[] = array('text' => 'Cmdes&nbsp;ponctuelles');
        break;
      case "rec":
        $info_box_contents[] = array('text' => 'Cmdes&nbsp;r&eacute;currentes');
        break;
      case "validated":
        $info_box_contents[] = array('text' => 'Cmdes&nbsp;valid&eacute;es');
        break;
    }
    new infoBoxHeading($info_box_contents, false, false);

    $customer_orders_string = '<table border="0" width="100%" cellspacing="0" cellpadding="1">';

    $info_box_contents = array();
      
    if ($so_type != "validated") {
      if ($so_type == 'rec') {
        // commandes récurrentes
        $and = " and orders_status = 4 ORDER BY date_purchased DESC"; 
      } else {
        // commandes ponctuelles 
        $and = " and orders_status <> -1 and orders_status <> 4 ORDER BY date_purchased DESC LIMIT 5"; 
      }
      $orders_query = tep_db_query("select orders_id, orders_status, date_purchased, group_id from " . TABLE_ORDERS . " where customers_id = '" . (int)$customer_id . "'".$and.";");
      $i = 0;
      while ($orders = tep_db_fetch_array($orders_query)) {
        $date = date("d/m/y", strtotime($orders['date_purchased']));
        if ($orders['group_id']>0) {
          $so_gid = "<b>GA</b>";
        } else {
          $so_gid = "RVD";
        }
        $customer_orders_string .= '  <tr>
                                        <td class="'.getBgColorClass($orders['orders_status']).'"><a href="' . tep_href_link(FILENAME_ACCOUNT_HISTORY_INFO, 'order_id=' . $orders['orders_id']) . '">Cde '.$so_gid.' '.$date.'</a></td>
                                      </tr>';
        $i+=1;
      }
    } else {
      $customer_orders_string .= '  <tr>
                                      <td class="main"><a href="' . tep_href_link('rvd_orders.php', '', 'SSL') . '"><b>Cdes RVD à<br>retirer aufleuripotager</b></a></td>
                                    </tr>';
    }

    $customer_orders_string .= '</table>';
    $info_box_contents[] = array('text' => $customer_orders_string);

    new infoBox($info_box_contents);
  }
?>
