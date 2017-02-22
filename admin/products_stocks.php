<?
/*
  $Id: manufacturers.php,v 1.55 2003/06/29 22:50:52 hpdl Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 osCommerce

  Released under the GNU General Public License
*/

  require('includes/application_top.php');

  require($admin_FS_path . DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();

  $action = (isset($HTTP_POST_VARS['action']) ? $HTTP_POST_VARS['action'] : '');
  $get_mID = (isset($HTTP_GET_VARS['mID']) ? $HTTP_GET_VARS['mID'] : '');
  $cs2 = (($get_mID>0) ? 2 : 1);
  $cs4 = (($get_mID>0) ? 4 : 2);
  $sort = (isset($HTTP_GET_VARS['sort']) ? $HTTP_GET_VARS['sort'] : '');

  if (tep_not_null($action)) {
    switch ($action) {
      case 'update_stock':
        for ($i = 0; $i < count($HTTP_POST_VARS["product_stock"]); $i++) {
          //on modifie les stocks
          $p_id_stock = (int)$HTTP_POST_VARS["p_id_stock"][$i];
          $stock_mod = ($HTTP_POST_VARS["stock_modified"][$i] == "yes");
          $stock = tep_format_qty_for_db($HTTP_POST_VARS["product_stock"][$i]);

          if ($stock_mod) {
            $sql = "UPDATE products SET products_quantity = '$stock' WHERE products_id = '$p_id_stock'"; 
        		$query = tep_db_query($sql);
          }

          //on modifie les libellés
          $p_id_name = (int)$HTTP_POST_VARS["p_id_name"][$i];
          $name_mod = ($HTTP_POST_VARS["name_modified"][$i] == "yes");
          $name = addslashes_once(tep_db_prepare_input($HTTP_POST_VARS["product_name"][$i]));
          if ($name_mod) {
            $sql = "UPDATE products_description SET products_name = '".$name."' WHERE products_id = '$p_id_name'"; 
        		$query = tep_db_query($sql);
        		
        		propagate_product_name_changes($p_id_name);
          }

          //on modifie les prix
          $p_id_price = (int)$HTTP_POST_VARS["p_id_price"][$i];
          $price_mod = ($HTTP_POST_VARS["price_modified"][$i] == "yes");
          $price = tep_format_qty_for_db($HTTP_POST_VARS["product_price"][$i]);
          if ($price_mod) {
            $sql = "UPDATE products SET products_price = '".$price."' WHERE products_id = '$p_id_price'"; 
        		$query = tep_db_query($sql);
          }


        }
          
        tep_redirect(tep_href_link('products_stocks.php', tep_get_all_get_params()));
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
    <script language="javascript">
      function showhide(divName) {
        var div = document.getElementById(divName);
        if (div) {
          if (div.style.display == "none") {
            div.style.display = "block";
          }
          else {
            div.style.display = "none";
          }
        }
      }
      
      function showhidemod(id, what) {
        showhide('div_'+what+'_txt'+id);
        showhide('div_'+what+'_edit'+id);
        var obj = document.getElementById('product_'+what+id);
        if (obj) {
          obj.focus();
          obj.select();
        }
      }
      
      function change_mID(id) {
        document.location.href="<?=tep_href_link('products_stocks.php', tep_get_all_get_params(array('mID','page')));?>&mID="+id;
      }
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
    <td width="100%" valign="top">
    <table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td>
<? 

echo tep_draw_form('products_stock_form', 'products_stocks.php', tep_get_all_get_params(), 'post').
        '<input type="hidden" name="action" value="update_stock">'; 

$manufacturers_array[] = array('id' => '', 'text' => 'Choisir un producteur...');
$manufacturers_query = tep_db_query("select manufacturers_id, manufacturers_name, group_id from " . TABLE_MANUFACTURERS . " order by group_id, manufacturers_name");
while ($manufacturers = tep_db_fetch_array($manufacturers_query)) {
  if ($manufacturers['group_id'] == 0) {
    //uniquempent la vente directe
    $m_name = "RVD-";
  } else {
    $m_name = "GA-";
  }
  $m_name .= $manufacturers['manufacturers_name']." (mID=".$manufacturers['manufacturers_id'].")";
  $manufacturers_array[] = array('id' => $manufacturers['manufacturers_id'],
                                 'text' => $m_name);
}
        
?>
            
        
        
        <table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading" nowrap colspan="4">Gestion du stock des produits
                  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?echo tep_draw_pull_down_menu('manufacturers_id', $manufacturers_array, $get_mID, "onChange='change_mID(this.value);'");?></td>
            <td align="center"  width="100%" colspan="2"><? echo tep_image_submit('button_update_cart.gif', 'Mise à jour stock'); ?> </td>
          </tr>
          <tr>
            <td class="pageHeading" nowrap colspan="6">&nbsp;</td>
          </tr>
              <tr class="dataTableHeadingRow">
              <? if ($get_mID <= 0) { ?>
                <td class="dataTableHeadingContent" align="center" colspan="2"><a href="<?=tep_href_link('products_stocks.php', tep_get_all_get_params(array('sort','page')).'sort=m');?>"><big><big><?if(($sort!="price")&&($sort!="p")&&($sort!="s")){echo "<u>";}?><b>Producteur</b><?if(($sort!="price")&&($sort!="p")&&($sort!="s")){echo "</u>";}?></big></big></a></td>
              <? } ?>
                <td class="dataTableHeadingContent" align="center" colspan="<?=$cs4?>" width="100%"><big><a href="<?=tep_href_link('products_stocks.php', tep_get_all_get_params(array('sort','page')).'sort=p');?>"><big><big><?if(($sort=="p")||(($sort!="price")&&($sort!="s")&&($get_mID>0))){echo "<u>";}?><b>Produit</b><?if($sort=="p"){echo "<u>";}?></big></big></a></td>
                <td class="dataTableHeadingContent" align="center"><big><a href="<?=tep_href_link('products_stocks.php', tep_get_all_get_params(array('sort','page')).'sort=price');?>"><big><big><?if($sort=="price"){echo "<u>";}?><b>Prix</b><?if($sort=="price"){echo "</u>";}?></big></big></a></td>
                <td class="dataTableHeadingContent" align="center"><big><a href="<?=tep_href_link('products_stocks.php', tep_get_all_get_params(array('sort','page')).'sort=s');?>"><big><big><?if($sort=="s"){echo "<u>";}?><b>Stock</b><?if($sort=="s"){echo "</u>";}?></big></big></a></td>
              </tr>
<?

  function getField($id, $value, $what, $unit = "", $small_txt = "", $alt = "") {
  
    $value_db = tep_format_qty_for_db($value);
    
    $c_txt = "";
    if ($small_txt == "") {  // si $small_txt == "", c'est que l'on est sur le stock
      $br = "";
      $small_txt = tep_format_qty_for_html($value_db, false, $unit);
      if ($value_db < 0) {
        $c_txt = "messageStackErrorBig";  
      } else if ($value_db == 0) {
        $c_txt = "messageStackWarningBig";  
      } else {
        $c_txt = "messageStackSuccessBig";  
      }
    } else {
      $br = "<br>";
    }

    if (is_double($value_db)||is_numeric($value_db)) {
      // nombre
      $value = tep_format_qty_for_html($value_db);
      $maxlength = 9;
      $size = 7;
      $align = "right";
    } else {
      // text
      $value = tep_output_string($value);
      $maxlength = 255;
      $size = 50;
      $align = "left";
    }
    
    $table =  
        "<table><tr>
          <td nowrap class='smallTextNoBorder'><div id='div_".$what."_text".$id."' style='display:block;'><span class='$c_txt'><b>".$small_txt."</b></span></div></td>
          <td nowrap class='smallTextNoBorder'><div id='div_".$what."_edit".$id."' style='display:none;'>
            <input type='hidden' id='p_id_".$what."$id' name='p_id_".$what."[]' value='$id'>
            <input type='hidden' id='".$what."_modified$id' name='".$what."_modified[]' value='no'>
            <input type='text' title='".$alt."' id='product_".$what."$id' name='product_".$what."[]' value=\"".$value."\" size='".$size."' maxlength='".$maxlength."' style='text-align:".$align."' onchange='document.getElementById(\"".$what."_modified$id\").value=\"yes\";'></td>
          <td nowrap class='smallTextNoBorder'><a href='javascript:showhidemod(".$id.",\"".$what."\");'>".tep_image(DIR_WS_ICONS . 'b_edit.png', 'Editer '.$what, '16', '16')."</a></td>
        </tr></table>";
    return $table;
  }


//if (substr($action, 0, 3) == 'new') {
  $products_stocks_query_raw = "SELECT p.group_id, ptc.categories_id, p.measure_unit, p.is_bulk, mg.group_name, p.products_price, m.manufacturers_image, p.manufacturers_id, m.manufacturers_name, p.products_id, p.products_image, p.products_quantity as products_stock, pd.products_name FROM products AS p 
    LEFT JOIN products_description AS pd ON pd.products_id = p.products_id 
    LEFT JOIN products_to_categories AS ptc ON ptc.products_id = p.products_id 
    LEFT JOIN manufacturers AS m ON m.manufacturers_id = p.manufacturers_id 
    LEFT JOIN manufacturers_groups AS mg ON m.group_id = mg.group_id";

  if ($get_mID > 0) {
    $products_stocks_query_raw .= " WHERE p.manufacturers_id = $get_mID";  
  }

  if (tep_not_null($sort)) {
    switch ($sort) {
      case 'p':
        $products_stocks_query_raw .= " ORDER BY p.group_id, pd.products_name, m.manufacturers_name";
        break;
      case 'price':
        $products_stocks_query_raw .= " ORDER BY p.products_price, p.group_id, m.manufacturers_name, pd.products_name";
        break;
      case 's':
        $products_stocks_query_raw .= " ORDER BY p.products_quantity, p.group_id, m.manufacturers_name, pd.products_name";
        break;
      default:
        $products_stocks_query_raw .= " ORDER BY p.group_id, m.manufacturers_name, pd.products_name";
        break;
    }
  } else {
    $products_stocks_query_raw .= " ORDER BY p.group_id, m.manufacturers_name, pd.products_name";
  }
  $products_stocks_split = new splitPageResults($HTTP_GET_VARS['page'], MAX_DISPLAY_SEARCH_RESULTS, $products_stocks_query_raw, $products_stocks_query_numrows);
  $products_stocks_query = tep_db_query($products_stocks_query_raw);
  
  $cur_gid = -1;
  while ($products_stocks = tep_db_fetch_array($products_stocks_query)) {
    $cur_ID = (int)$products_stocks['products_id'];
    
    if (($get_mID <= 0)&&($sort != "price")&&($sort != "s")&&($products_stocks['group_id'] != $cur_gid)) {
?>
              <tr bgcolor="#AABBEE">
                <td class="dataTableContent" colspan="6"><big><big><big><b><?if ($products_stocks['group_name'] != '') { echo $products_stocks['group_name'];} else { echo "Vente directe";}?></b></big></big></big></td>
              </tr>
<?
      $cur_gid = $products_stocks['group_id'];
    }


    if ($products_stocks['is_bulk'] > 0) {
      $unit = $products_stocks['measure_unit'];
    } else {
      $unit = "";
    }

    if (tep_not_null($products_stocks['manufacturers_image'])) {
      $tb = tep_image(DIR_WS_CATALOG_IMAGES . $products_stocks['manufacturers_image'], '', 16, 16, '', 16);
    } else {
      $tb = tep_image(DIR_WS_ICONS . 'preview.gif', ICON_PREVIEW);
    }

    if (tep_not_null($products_stocks['products_image'])) {
      $tbp = tep_image(DIR_WS_CATALOG_IMAGES . $products_stocks['products_image'], '', 16, 16, '', 16);
    } else {
      $tbp = tep_image(DIR_WS_ICONS . 'preview.gif', ICON_PREVIEW);
    }
    $tbp = '<a href="' . tep_href_link(FILENAME_CATEGORIES, 'pID=' . $cur_ID.'&cPath='.$products_stocks['categories_id']) . '&action=new_product&mID='.$products_stocks['manufacturers_id'].'">' . $tbp . '</a>';
  

?>
              <tr>
              <? if ($get_mID <= 0) { ?>
                <td class="dataTableContent" width="18px" align="center" nowrap><? echo ' <a href="' . tep_href_link(FILENAME_MANUFACTURERS, 'mID=' . $products_stocks['manufacturers_id']) . '&mNAME=' . urlencode($products_stocks['manufacturers_name']) . '&action=edit">'.$tb.'</a>';   ?></td>
                <td class="dataTableContent" align="left" nowrap> <? if ($products_stocks['group_name'] != '') { echo " [".$products_stocks['group_name']."] ";} echo ' <a href="' . tep_href_link(FILENAME_MANUFACTURERS, 'mID=' . $products_stocks['manufacturers_id']) . '&mNAME=' . urlencode($products_stocks['manufacturers_name']) . '">' . $products_stocks['manufacturers_name'] . ' <i>(mID='. $products_stocks['manufacturers_id'] . ')</i></a>';  ?></td>
              <? } ?>
                <td class="dataTableContent" width="18px" align="center" colspan="<?=$cs2?>" nowrap><? echo $tbp ;   ?></td>
                <td class="dataTableContent" align="left" width="100%" nowrap  colspan="<?=$cs2?>"> <? echo getField($cur_ID, $products_stocks['products_name'], 'name', '', '<a href="' . tep_href_link(FILENAME_CATEGORIES, 'pID=' . $cur_ID.'&cPath='.$products_stocks['categories_id']) . '">' . tep_truncate_string($products_stocks['products_name'], MAX_PRODUCTS_NAMES_LENGTH) . ' <i>(pID=' . $cur_ID . ')</i></a>', 'la modification du nom du produit affectera toutes les commandes existantes'); ?></td>
                <td class="dataTableContent" align="right" nowrap><? echo getField($cur_ID, $products_stocks['products_price'], 'price', '', $currencies->format($products_stocks['products_price']), 'seul le prix du produit sera modifié, aucune commande ne sera affectée');?> </td>
                <td class="dataTableContent" align="right" nowrap><? echo getField($cur_ID, $products_stocks['products_stock'], 'stock', $unit, '', 'le stock sera mis à jour');?> </td>
              </tr>
<?
  }
?>
      <tr>
            <td colspan="4">&nbsp;</td>
            <td width="100%" align="center" colspan="2"><? echo tep_image_submit('button_update_cart.gif', 'Mise à jour stock'); ?> </form></td>
      </tr>

      <tr>
        <td class="dataTableTotalRow" valign="top" colspan="4" nowrap><? echo $products_stocks_split->display_count($products_stocks_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $HTTP_GET_VARS['page'], TEXT_DISPLAY_NUMBER_OF_PRODUCTS); ?></td>
        <td class="dataTableTotalRow" align="right" colspan="2" nowrap><? echo $products_stocks_split->display_links($products_stocks_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $HTTP_GET_VARS['page'], 'sort='.$HTTP_GET_VARS['sort']); ?></td>
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
