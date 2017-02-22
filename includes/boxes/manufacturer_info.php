<?php
/*
  $Id: manufacturer_info.php,v 1.11 2003/06/09 22:12:05 hpdl Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 osCommerce

  Released under the GNU General Public License
*/

$sql = "";
if (isset($HTTP_GET_VARS['products_id'])) {
  $sql = "select m.manufacturers_id, m.manufacturers_name, m.manufacturers_image, mi.manufacturers_url from " . TABLE_MANUFACTURERS . " m left join " . TABLE_MANUFACTURERS_INFO . " mi on (m.manufacturers_id = mi.manufacturers_id and mi.languages_id = '" . (int)$languages_id . "'), " . TABLE_PRODUCTS . " p  where p.products_id = '" . (int)$HTTP_GET_VARS['products_id'] . "' and p.manufacturers_id = m.manufacturers_id";
}
else if (isset($HTTP_GET_VARS['manufacturers_id'])) {
  $sql = "select m.manufacturers_id, m.manufacturers_name, m.manufacturers_image, mi.manufacturers_url from " . TABLE_MANUFACTURERS . " m left join " . TABLE_MANUFACTURERS_INFO . " mi on (m.manufacturers_id = mi.manufacturers_id and mi.languages_id = '" . (int)$languages_id . "'), " . TABLE_PRODUCTS . " p  where m.manufacturers_id = '" . (int)$HTTP_GET_VARS['manufacturers_id'] . "' and p.manufacturers_id = m.manufacturers_id";
}

if (!clientCanBuyGA()) {
  // si l'adhérent n'est pas autorisé au grpt d'achat => et ben, pas de possibilité de le trouver !
  $sql .= " and p.group_id = 0 ";  
}

if ($sql != "") {
  $manufacturer_query = tep_db_query($sql);
  if (tep_db_num_rows($manufacturer_query)) {
      $manufacturer = tep_db_fetch_array($manufacturer_query);
?>
<!-- manufacturer_info //-->
          <tr>
            <td>
<?php
      $info_box_contents = array();
      $info_box_contents[] = array('text' => BOX_HEADING_MANUFACTURER_INFO);

      new infoBoxHeading($info_box_contents, false, false);

/*
      if ($manufacturer['manufacturers_url'] == 'catalog') {
        $url = FILENAME_MANUFACTURERS_INFO;
      }
      else {
        $url = FILENAME_REDIRECT;
      }
*/
      $url = FILENAME_MANUFACTURERS_INFO;


      $manufacturer_info_string = '<table border="0" width="100%" cellspacing="0" cellpadding="0">';
      if (tep_not_null($manufacturer['manufacturers_image'])) {
        $manufacturer_info_string .= '<tr><td align="center" class="infoBoxContents" colspan="2"><a href="' . 
          tep_href_link($url, 'action=manufacturer&manufacturers_id=' . $manufacturer['manufacturers_id']) . '" target="_blank">' .
          tep_image(DIR_WS_CATALOG_IMAGES . $manufacturer['manufacturers_image'], $manufacturer['manufacturers_name'], 80, 60, '', RIGHT_INFOBOX_WIDTH_IN_PX) . '</a></td></tr>';
      }
//      if (tep_not_null($manufacturer['manufacturers_url'])) $manufacturer_info_string .= '<tr><td valign="top" align="center" class="infoBoxContents">&nbsp;</td><td valign="top" align="center" class="infoBoxContents">';
      $manufacturer_info_string .= '<center><a href="' . tep_href_link($url, 'action=manufacturer&manufacturers_id=' . $manufacturer['manufacturers_id']) . '" target="_blank">';
      $manufacturer_info_string .= sprintf(BOX_MANUFACTURER_INFO_HOMEPAGE, $manufacturer['manufacturers_name']) . '</center></a></td></tr>';
      $manufacturer_info_string .= '<tr><td valign="top" class="infoBoxContents" colspan="2" align="center"><br><a href="' . tep_href_link(FILENAME_DEFAULT, 'manufacturers_id=' . $manufacturer['manufacturers_id']) . '">' . BOX_MANUFACTURER_INFO_OTHER_PRODUCTS . '</a></td></tr>' .
                                   '</table>';

      $info_box_contents = array();
      $info_box_contents[] = array('text' => $manufacturer_info_string);

      new infoBox($info_box_contents);
?>
            </td>
          </tr>
<!-- manufacturer_info_eof //-->
<?php
    }
  }
?>
