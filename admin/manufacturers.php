<?
/*
  $Id: manufacturers.php,v 1.55 2003/06/29 22:50:52 hpdl Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 osCommerce

  Released under the GNU General Public License
*/

  require('includes/application_top.php');

  $action = (isset($HTTP_GET_VARS['action']) ? $HTTP_GET_VARS['action'] : '');
  $get_mID = (isset($HTTP_GET_VARS['mID']) ? $HTTP_GET_VARS['mID'] : '');

  if (tep_not_null($action)) {
    switch ($action) {
      case 'insert':
      case 'save':
        if ($get_mID) $manufacturers_id = tep_db_prepare_input($get_mID);
        $manufacturers_name = tep_db_prepare_input($HTTP_POST_VARS['manufacturers_name']);
        $manufacturers_email = tep_db_prepare_input($HTTP_POST_VARS['manufacturers_email']);
        $manufacturers_group_id = tep_db_prepare_input($HTTP_POST_VARS['group_id']);
        $manufacturers_previous_image = tep_db_prepare_input($HTTP_POST_VARS['manufacturers_previous_image']);
        $default_shipping_day = tep_db_prepare_input($HTTP_POST_VARS['shipping_day']);
        $default_shipping_frequency = tep_db_prepare_input($HTTP_POST_VARS['shipping_frequency']);
        $quality_label = tep_db_prepare_input($HTTP_POST_VARS['quality_label']);
        $manufacturers_address = tep_db_prepare_input($HTTP_POST_VARS['manufacturers_address']);
        $manufacturers_tel = tep_db_prepare_input($HTTP_POST_VARS['manufacturers_tel']);
        $main_product = tep_db_prepare_input($HTTP_POST_VARS['main_product']);
        $infotext = tep_db_prepare_input($HTTP_POST_VARS['infotext']);
        $manufacturers_people_name = tep_db_prepare_input($HTTP_POST_VARS['manufacturers_people_name']);
        
        (tep_not_null($quality_label)) ? $organic_product = 1 : $organic_product = 0; 

        $sql_data_array = array('manufacturers_name' => $manufacturers_name);
        $sql_data_array = array_merge($sql_data_array, array('manufacturers_email' => $manufacturers_email));
        $sql_data_array = array_merge($sql_data_array, array('group_id' => $manufacturers_group_id));
        $sql_data_array = array_merge($sql_data_array, array('manufacturers_address' => $manufacturers_address));
        $sql_data_array = array_merge($sql_data_array, array('manufacturers_tel' => $manufacturers_tel));
        $sql_data_array = array_merge($sql_data_array, array('default_shipping_day' => $default_shipping_day));
        $sql_data_array = array_merge($sql_data_array, array('default_shipping_frequency' => $default_shipping_frequency));
        $sql_data_array = array_merge($sql_data_array, array('default_shipping_frequency' => $default_shipping_frequency));
        $sql_data_array = array_merge($sql_data_array, array('manufacturers_people_name' => $manufacturers_people_name));

        $manufacturers_image_name = "";
        if ($manufacturers_image = new upload('manufacturers_image', DIR_FS_CATALOG_IMAGES)) {
          $manufacturers_image_name = $manufacturers_image->filename;
        } 
        if (($manufacturers_image_name == "")&&($manufacturers_previous_image!="")) {
          $manufacturers_image_name = $manufacturers_previous_image;
        }
        
        if ($manufacturers_image_name != "") {
          $sql_data_array = array_merge($sql_data_array, array('manufacturers_image' => $manufacturers_image_name));
        }

        if ($action == 'insert') {
          $insert_sql_data = array('date_added' => 'now()');

          $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

          tep_db_perform(TABLE_MANUFACTURERS, $sql_data_array);
          $manufacturers_id = tep_db_insert_id();
        } elseif ($action == 'save') {
          $update_sql_data = array('last_modified' => 'now()');

          $sql_data_array = array_merge($sql_data_array, $update_sql_data);

          tep_db_perform(TABLE_MANUFACTURERS, $sql_data_array, 'update', "manufacturers_id = '" . (int)$manufacturers_id . "'");
        }

        $reload = true;
        $languages = tep_get_languages();
        for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
          $manufacturers_url_array = $HTTP_POST_VARS['manufacturers_url'];
          $language_id = $languages[$i]['id'];
          
          $sql_data_array = array('manufacturers_url' => tep_db_prepare_input($manufacturers_url_array[$language_id]));
          $sql_data_array = array_merge($sql_data_array, array('quality_label' => $quality_label));
          $sql_data_array = array_merge($sql_data_array, array('main_product' => $main_product));
          $sql_data_array = array_merge($sql_data_array, array('infotext' => $infotext));
          $sql_data_array = array_merge($sql_data_array, array('organic_product' => $organic_product));

          // on vérifie si l'enreg existe
          $sql = "select manufacturers_id from " . TABLE_MANUFACTURERS_INFO . " where manufacturers_id = " . (int)$manufacturers_id . " and languages_id = ". (int)$language_id;
          $man_query = tep_db_query($sql);
          $man = tep_db_fetch_array($man_query);
//          echo $sql."<br>".$man['manufacturers_id'];exit;
          
          if ($man['manufacturers_id']<=0) {
            // il n'y a pas d'enreg dans TABLE_MANUFACTURERS_INFO => on crée l'enreg
            $insert_sql_data = array('manufacturers_id' => $manufacturers_id,
                                     'languages_id' => $language_id);

            $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

            tep_db_perform(TABLE_MANUFACTURERS_INFO, $sql_data_array);
          } else {
            tep_db_perform(TABLE_MANUFACTURERS_INFO, $sql_data_array, 'update', "manufacturers_id = '" . (int)$manufacturers_id . "' and languages_id = '" . (int)$language_id . "'");
          }
          
          if (($action == 'save')&&($default_shipping_day != 'tuesday,thursday')) { // le pb se pose uniquement si on est moins restrictif 
            // il faut mettre à jour les shipping_day et shipping_frequency de tous les produits de ce producteurs, 
            $sql = "UPDATE ".TABLE_PRODUCTS." SET shipping_day = '$default_shipping_day', shipping_frequency = $default_shipping_frequency WHERE manufacturers_id = '" . (int)$manufacturers_id . "' AND (shipping_day <> '$default_shipping_day' OR shipping_frequency < $default_shipping_frequency);";
       			tep_db_query($sql);
       			
       			propagate_shipping_modifications($manufacturers_id, $default_shipping_day, $default_shipping_frequency, "TABLE_MANUFACTURERS");
          }
        }

        if (USE_CACHE == 'true') {
          tep_reset_cache_block('manufacturers');
        }
        
        if ($reload) tep_redirect(tep_href_link(FILENAME_MANUFACTURERS, (isset($HTTP_GET_VARS['page']) ? 'page=' . $HTTP_GET_VARS['page'] . '&' : '') . 'mID=' . $manufacturers_id));
        break;
      case 'deleteconfirm':
        $manufacturers_id = tep_db_prepare_input($get_mID);

        if (isset($HTTP_POST_VARS['delete_image']) && ($HTTP_POST_VARS['delete_image'] == 'on')) {
          $manufacturer_query = tep_db_query("select manufacturers_image from " . TABLE_MANUFACTURERS . " where manufacturers_id = '" . (int)$manufacturers_id . "'");
          $manufacturer = tep_db_fetch_array($manufacturer_query);

          $image_location = DIR_FS_DOCUMENT_ROOT . DIR_WS_CATALOG_IMAGES . $manufacturer['manufacturers_image'];

          if (file_exists($image_location)) @unlink($image_location);
        }

        tep_db_query("delete from " . TABLE_MANUFACTURERS . " where manufacturers_id = '" . (int)$manufacturers_id . "'");
        tep_db_query("delete from " . TABLE_MANUFACTURERS_INFO . " where manufacturers_id = '" . (int)$manufacturers_id . "'");

        if (isset($HTTP_POST_VARS['delete_products']) && ($HTTP_POST_VARS['delete_products'] == 'on')) {
          $products_query = tep_db_query("select products_id from " . TABLE_PRODUCTS . " where manufacturers_id = '" . (int)$manufacturers_id . "'");
          while ($products = tep_db_fetch_array($products_query)) {
            tep_remove_product($products['products_id']);
          }
        } else {
          tep_db_query("update " . TABLE_PRODUCTS . " set manufacturers_id = '' where manufacturers_id = '" . (int)$manufacturers_id . "'");
        }

        if (USE_CACHE == 'true') {
          tep_reset_cache_block('manufacturers');
        }

        tep_redirect(tep_href_link(FILENAME_MANUFACTURERS, 'page=' . $HTTP_GET_VARS['page']));
        break;
    }
  }
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <? echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<? echo CHARSET; ?>">
<title><? echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="includes/general.js">
</script>
<script>
<?=putShippingJS("manufacturers");?>
</script>

</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF" onload="SetFocus();">
<!-- header //-->
<? require($admin_FS_path . DIR_WS_INCLUDES . 'header.php'); ?>
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
      <tr>
        <td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><? echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><? echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent" colspan="2"><? echo TABLE_HEADING_MANUFACTURERS; ?></td>
                <td class="dataTableHeadingContent" align="center"><? echo "Nb produits"; ?></td>
                <td class="dataTableHeadingContent" align="center"><? echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
              </tr>
<?

//if (substr($action, 0, 3) == 'new') {
  $manufacturers_query_raw = "select manufacturers_people_name, quality_label, manufacturers_address, manufacturers_url, manufacturers_tel, main_product, infotext, organic_product, m.group_id, manufacturers_email, m.manufacturers_id, manufacturers_name, manufacturers_image, date_added, last_modified, group_name, default_shipping_day, default_shipping_frequency from " . TABLE_MANUFACTURERS . " as m LEFT JOIN manufacturers_groups as mg ON m.group_id = mg.group_id LEFT JOIN manufacturers_info as mi ON m.manufacturers_id = mi.manufacturers_id order by m.group_id, manufacturers_name";
  $manufacturers_split = new splitPageResults($HTTP_GET_VARS['page'], MAX_DISPLAY_SEARCH_RESULTS, $manufacturers_query_raw, $manufacturers_query_numrows);
  $manufacturers_query = tep_db_query($manufacturers_query_raw);
  while ($manufacturers = tep_db_fetch_array($manufacturers_query)) {
    $cur_manID = (int)$manufacturers['manufacturers_id'];
    $manufacturer_products_query = tep_db_query("select count(*) as products_count from " . TABLE_PRODUCTS . " where manufacturers_id = '" . $cur_manID . "'");
    $manufacturer_products = tep_db_fetch_array($manufacturer_products_query);
    $mInfo_array = array_merge($manufacturers, $manufacturer_products);

    if ((!$get_mID || ($get_mID == $cur_manID)) && !isset($mInfo) && (substr($action, 0, 3) != 'new')) {
      // on remplit le $mInfo de l'item sélectionné
      $mInfo = new objectInfo($mInfo_array);
    }

    if (isset($mInfo) && is_object($mInfo) && ($cur_manID == $get_mID)) {
      echo '              <tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_MANUFACTURERS, 'page=' . $HTTP_GET_VARS['page'] . '&mID=' . $cur_manID . '&action=edit') . '\'">' . "\n";
    } else {
      echo '              <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_MANUFACTURERS, 'page=' . $HTTP_GET_VARS['page'] . '&mID=' . $cur_manID) . '\'">' . "\n";
    }

if (tep_not_null($manufacturers['manufacturers_image'])) {
  $tb = tep_image(DIR_WS_CATALOG_IMAGES . $manufacturers['manufacturers_image'], '', 16, 16, '', 16);
} else {
  $tb = tep_image(DIR_WS_ICONS . 'preview.gif', ICON_PREVIEW);
}


?>
                <td class="dataTableContent" width="18px" align="center"><? echo $tb ;   ?></td>
                <td class="dataTableContent" width="100%"><? if ($manufacturers['group_name'] != '') { echo "[".$manufacturers['group_name']."] ";} echo $manufacturers['manufacturers_name'];  ?></td>
                <td class="dataTableContent" align="right" nowrap><? echo '<a href="' . tep_href_link(FILENAME_CATEGORIES, 'mID=' . $cur_manID) . '&mNAME=' . urlencode($manufacturers['manufacturers_name']) . '">' . $manufacturer_products['products_count']." produits" . '</a>'; ?></td>
                <td class="dataTableContent" align="right"><? if ($cur_manID == $get_mID) { echo tep_image(DIR_WS_IMAGES . 'icon_arrow_right.gif'); } else { echo '<a href="' . tep_href_link(FILENAME_MANUFACTURERS, 'page=' . $HTTP_GET_VARS['page'] . '&mID=' . $manufacturers['manufacturers_id']) . '">' . tep_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp;</td>
              </tr>
<?
  }
?>


              <tr>
                <td colspan="4"><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr>
                    <td class="smallText" valign="top"><? echo $manufacturers_split->display_count($manufacturers_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $HTTP_GET_VARS['page'], TEXT_DISPLAY_NUMBER_OF_MANUFACTURERS); ?></td>
                    <td class="smallText" align="right"><? echo $manufacturers_split->display_links($manufacturers_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $HTTP_GET_VARS['page']); ?></td>
                  </tr>
                </table></td>
              </tr>
<?
  if (empty($action)) {
?>
              <tr>
                <td align="right" colspan="4" class="smallText"><? echo '<a href="' . tep_href_link(FILENAME_MANUFACTURERS, 'page=' . $HTTP_GET_VARS['page'] . '&mID=' . $get_mID . '&action=new') . '">' . tep_image_button('button_insert.gif', IMAGE_INSERT) . '</a>'; ?></td>
              </tr>
<?
  }
?>
            </table></td>
<?
  $heading = array();
  $contents = array();

  $groups_array = array();
  getGroupArray();

  $manufacturer_inputs_string = '';
  $languages = tep_get_languages();
  for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
//    echo DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'];
    $manufacturer_inputs_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;';
    if (($action != 'new')&&($action != 'edit')) {
      $manufacturer_inputs_string .= '<a href="'.$mInfo->manufacturers_url.'" target="_blank">'.$mInfo->manufacturers_url.'</a>';
    } else {
      $manufacturer_inputs_string .= tep_draw_input_field('manufacturers_url[' . $languages[$i]['id'] . ']', $mInfo->manufacturers_url);
    }
  }



  switch ($action) {
    case 'new':
    case 'edit':

      if ($action == 'new') {
        $heading[] = array('text' => '<b>' . TEXT_HEADING_NEW_MANUFACTURER . '</b>');
        $contents = array('form' => tep_draw_form('manufacturers', FILENAME_MANUFACTURERS, 'action=insert', 'post', 'enctype="multipart/form-data"'));
        $contents[] = array('text' => TEXT_NEW_INTRO);
        $mID = $get_mID;
      } else {
        $heading[] = array('text' => '<b>' . TEXT_HEADING_EDIT_MANUFACTURER . '</b>');
        $contents = array('form' => tep_draw_form('manufacturers', FILENAME_MANUFACTURERS, 'page=' . $HTTP_GET_VARS['page'] . '&mID=' . $mInfo->manufacturers_id . '&action=save', 'post', 'enctype="multipart/form-data"'));
        $contents[] = array('text' => TEXT_EDIT_INTRO);
        $mID = $mInfo->manufacturers_id;
      }

      $contents[] = array('text' => '<br>' . TEXT_MANUFACTURERS_NAME . '<br>' . tep_draw_input_field('manufacturers_name', $mInfo->manufacturers_name, 'size="30"'));
      $contents[] = array('text' => 'Nom des producteurs:<br>' . tep_draw_input_field('manufacturers_people_name', $mInfo->manufacturers_people_name, 'size="30"'));

//      $contents[] = array('text' => '<br>Email : <br>' . tep_draw_input_field('manufacturers_email', $mInfo->manufacturers_email, 'size="30"'));
      $contents[] = array('text' => '<br>' . TEXT_MANUFACTURERS_GROUP_NAME . '<br>' . tep_draw_pull_down_menu('group_id', $groups_array, $mInfo->group_id, 'width="50px"'));

      if ($mInfo->default_shipping_day == "") $mInfo->default_shipping_day = "thursday";
      if ($mInfo->default_shipping_frequency == "") $mInfo->default_shipping_frequency = "1.0";
      $contents[] = array('text' => 'Mode de livraison :<br>'.putShipping($mInfo->manufacturers_id, $mInfo->default_shipping_day, $mInfo->default_shipping_frequency, "", "m").'<br>'.'<br>');

      if ($mInfo->manufacturers_image) {
        $contents[] = array('text' => tep_image(DIR_WS_CATALOG_IMAGES . $mInfo->manufacturers_image, $mInfo->manufacturers_name, '', '', '', 150).tep_draw_hidden_field('manufacturers_previous_image', $mInfo->manufacturers_image).'<br>'.$mInfo->manufacturers_image);
      }
      $contents[] = array('text' => '<br>' . TEXT_MANUFACTURERS_IMAGE . '<br>' . tep_draw_file_field('manufacturers_image'));

      $contents[] = array('text' => '<br>Produits principaux:' . tep_draw_input_field('main_product', $mInfo->main_product, 'size="30"'));
//      $contents[] = array('text' => '<br>Produit bio:' . $mInfo->organic_product);
      $quality_label_array = array(
          array('id' => '', 'text' => ''),                                              
          array('id' => 'Qualité France', 'text' => 'Qualité France'),
          array('id' => 'ACLAVE', 'text' => 'ACLAVE'),
          array('id' => 'ECOCERT', 'text' => 'ECOCERT'));
      $contents[] = array('text' => 'Certification bio: ' . tep_draw_pull_down_menu('quality_label', $quality_label_array, $mInfo->quality_label, 'width="50px"'));
      $contents[] = array('text' => '<br>Adresse:<br>' . tep_draw_textarea_field('manufacturers_address', 'soft', '30', '5', $mInfo->manufacturers_address));
      $contents[] = array('text' => 'Tél.: ' . tep_draw_input_field('manufacturers_tel', $mInfo->manufacturers_tel, 'size="27"'));
      $contents[] = array('text' => 'Email: ' . tep_draw_input_field('manufacturers_email', $mInfo->manufacturers_email, 'size="25"'));
      $contents[] = array('text' => '<br>Description:' . tep_draw_textarea_field('infotext', 'soft', '30', '5', $mInfo->infotext));

      $contents[] = array('text' => '<br>' . TEXT_MANUFACTURERS_URL . $manufacturer_inputs_string);
      $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_save.gif', IMAGE_SAVE) . ' <a href="' . tep_href_link(FILENAME_MANUFACTURERS, 'page=' . $HTTP_GET_VARS['page'] . '&mID=' . $mID) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
      break;
    case 'delete':
      $heading[] = array('text' => '<b>' . TEXT_HEADING_DELETE_MANUFACTURER . '</b>');

      $contents = array('form' => tep_draw_form('manufacturers', FILENAME_MANUFACTURERS, 'page=' . $HTTP_GET_VARS['page'] . '&mID=' . $mInfo->manufacturers_id . '&action=deleteconfirm'));
      $contents[] = array('text' => TEXT_DELETE_INTRO);
      $contents[] = array('text' => '<br><b>' . $mInfo->manufacturers_name . '</b>');
      $contents[] = array('text' => '<br>' . tep_draw_checkbox_field('delete_image', '', true) . ' ' . TEXT_DELETE_IMAGE);

      if ($mInfo->products_count > 0) {
        $contents[] = array('text' => '<br>' . tep_draw_checkbox_field('delete_products') . ' ' . TEXT_DELETE_PRODUCTS);
        $contents[] = array('text' => '<br>' . sprintf(TEXT_DELETE_WARNING_PRODUCTS, $mInfo->products_count));
      }

      $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_delete.gif', IMAGE_DELETE) . ' <a href="' . tep_href_link(FILENAME_MANUFACTURERS, 'page=' . $HTTP_GET_VARS['page'] . '&mID=' . $mInfo->manufacturers_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
      break;
    default:
      if (isset($mInfo) && is_object($mInfo)) {
        $heading[] = array('text' => '<b>' . $mInfo->manufacturers_name . '</b>');

        $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_MANUFACTURERS, 'page=' . $HTTP_GET_VARS['page'] . '&mID=' . $mInfo->manufacturers_id . '&action=edit') . '">' . tep_image_button('button_edit.gif', IMAGE_EDIT) . '</a> <a href="' . tep_href_link(FILENAME_MANUFACTURERS, 'page=' . $HTTP_GET_VARS['page'] . '&mID=' . $mInfo->manufacturers_id . '&action=delete') . '">' . tep_image_button('button_delete.gif', IMAGE_DELETE) . '</a>');

        $contents[] = array('text' => '<br><b>' . TEXT_DATE_ADDED . '</b> ' . getFormattedLongDate($mInfo->date_added));
        if (tep_not_null($mInfo->last_modified)) $contents[] = array('text' => '<b>'.TEXT_LAST_MODIFIED . '</b> ' . getFormattedLongDate($mInfo->last_modified));

        if ($mInfo->manufacturers_people_name) {
          $contents[] = array('text' => '<br><b>Nom des producteurs:</b><br>' . $mInfo->manufacturers_people_name);
        }
        $contents[] = array('text' => '<br><b>Nb produits :</b> ' . $mInfo->products_count);
        $contents[] = array('text' => 
          '<br><b>Jour de livraison :</b><br>&nbsp;&nbsp;&nbsp;' . convertEnglishDateNames_fr($mInfo->default_shipping_day) . 
          '<br><b>Fréquence de livraison :</b><br>&nbsp;&nbsp;&nbsp;' . convertShippingFrequencyToText_fr($mInfo->default_shipping_frequency));
        if ($mInfo->manufacturers_image) {
          $contents[] = array('text' => '<br><center>' . tep_image(DIR_WS_CATALOG_IMAGES . $mInfo->manufacturers_image, $mInfo->manufacturers_name, '', '', '', 250).'</center>'.$mInfo->manufacturers_image);
        }

        if ($mInfo->main_product) {
          $contents[] = array('text' => '<br><b>Produits principaux:</b><br>' . $mInfo->main_product);
        }
        if ($mInfo->quality_label) {
          $contents[] = array('text' => '<b>Certification bio:</b> ' . $mInfo->quality_label);
        }
        if ($mInfo->manufacturers_address) {
          $contents[] = array('text' => '<br><b>Adresse:</b><br>' . $mInfo->manufacturers_address);
        }
        if ($mInfo->manufacturers_tel) {
          $contents[] = array('text' => '<b>Tél.:</b> ' . $mInfo->manufacturers_tel);
        }
        if ($mInfo->manufacturers_email) {
          $contents[] = array('text' => '<b>Email:</b> ' . $mInfo->manufacturers_email);
        }
        if ($mInfo->infotext) {
          $contents[] = array('text' => '<br><b>Description:</b><br>' . $mInfo->infotext);
        }
        if ($mInfo->manufacturers_url) {
          $contents[] = array('text' => '<br><b>URL:</b><br>' . $manufacturer_inputs_string);
        }
        
        
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
