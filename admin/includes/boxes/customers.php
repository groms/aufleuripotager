<?
/*
  $Id: customers.php,v 1.16 2007/04/17 01:18:53 hpdl Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2002 osCommerce

  Released under the GNU General Public License
  Changed for Orderlist 5.1b
*/
?>
<!-- customers //-->
          <tr>
            <td>
<?
  $heading = array();
  $contents = array();

  $heading[] = array('text'  => BOX_HEADING_CUSTOMERS,
                     'link'  => tep_href_link(FILENAME_CUSTOMERS, 'selected_box=customers'));

  if ($selected_box == 'customers') {
    $contents[] = array('text'  => 
//          '<a href="' . tep_href_link(FILENAME_ORDERLIST, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_CUSTOMERS_ORDERLIST . '</a><br>' .
    			'<a href="' . tep_href_link(FILENAME_CUSTOMERS, '', 'NONSSL') . '" class="menuBoxContentLink"><b>' . BOX_CUSTOMERS_CUSTOMERS . '</b></a><br>' .
          '<a href="' . tep_href_link(FILENAME_ORDERS, '', 'NONSSL') . '" class="menuBoxContentLink">' . BOX_CUSTOMERS_ORDERS . '</a><br>' .
    			'<a href="' . tep_href_link('ga.php', '', 'NONSSL') . '" class="menuBoxContentLink">Groupmt d\'achat</a>');

  }

  $box = new box;
  echo $box->menuBox($heading, $contents);
?>
            </td>
          </tr>
<!-- customers_eof //-->
