<?
/*
$Id: categories.php,v 1.146 2003/07/11 14:40:27 hpdl Exp $

osCommerce, Open Source E-Commerce Solutions
http://www.oscommerce.com

Copyright (c) 2003 osCommerce

Released under the GNU General Public License
*/

$local_cPath = "";
if (isset($HTTP_POST_VARS['cPathCombo'])) {
  if ($HTTP_POST_VARS['cPathCombo']!="") {
    $HTTP_GET_VARS['cPath'] = $HTTP_POST_VARS['cPathCombo'];
    $local_cPath = $HTTP_POST_VARS['cPathCombo'];
  }
} else if (isset($HTTP_GET_VARS['cPath'])) {
  if ($HTTP_GET_VARS['cPath']!="") {
    $local_cPath = $HTTP_GET_VARS['cPath'];
  }
} 

require('includes/application_top.php');

require($admin_FS_path . DIR_WS_CLASSES . 'currencies.php');
$currencies = new currencies();

function calcul_ot_op($orders_id) {
  global $currencies;
  
  //on recalcule les orders_totals
  $sql = "select sum(op.products_quantity*op.final_price) as sum from orders_products as op where op.orders_id='" . $orders_id . "';";
  $ot_query = tep_db_query($sql);
  if ($ot = tep_db_fetch_array($ot_query)) {
    $ot_query = tep_db_query("UPDATE orders_total SET text='<b>".$currencies->format($ot['sum'])."</b>',value='".$ot['sum']."' WHERE orders_id='" . $orders_id . "';");
  }
}

$weights_array = array();

function getAuthorizedArray() {
  global $pInfo, $weights_array, $weights_checkbox, $defaultAuthorizedWeights_array;
  // $defaultAuthorizedWeights_array is at the beginning of general.php

  $weights_checkbox = "<table cellspacing='10'><tr>";

  $weights_array = array();
  $weights_array_tmp = explode("|", $pInfo->authorized_weights);
  $i = 0;$j=1;
  foreach ($defaultAuthorizedWeights_array as $value) {
    if (in_array($value, $weights_array_tmp)) {
      $weights_array[] = array('id' => tep_format_qty_for_db($value), 'text' => tep_format_qty_for_html($value)." ".$pInfo->measure_unit);
    }
    if ($i == 5) {
      $weights_checkbox .= "</tr><tr>";
      $i = 0;
    }
    $weights_checkbox .= "<td class='main'>".tep_draw_checkbox_field('authorized_weights[]', tep_format_qty_for_db($value), in_array($value, $weights_array_tmp), '', 'id="authorized_weights'.$j.'"').tep_format_qty_for_html($value)."</td>";
    $i += 1;   
    $j += 1;   
  }
  $weights_checkbox .= "</tr></table>";
}

$action = (isset($HTTP_GET_VARS['action']) ? $HTTP_GET_VARS['action'] : '');

$local_mID = "";
$local_mNAME = "";
if (isset($HTTP_GET_VARS['mID'])) {$local_mID = $HTTP_GET_VARS['mID'];}
if (isset($HTTP_GET_VARS['mNAME'])) {$local_mNAME = $HTTP_GET_VARS['mNAME'];}

$groups_array = array();
getGroupArray();

if (tep_not_null($action)) {
switch ($action) {
  case 'setflag':
    if ( ($HTTP_GET_VARS['flag'] == '0') || ($HTTP_GET_VARS['flag'] == '1') ) {
      if (isset($HTTP_GET_VARS['pID'])) {
        tep_set_product_status($HTTP_GET_VARS['pID'], $HTTP_GET_VARS['flag']);
      }
      
      if (USE_CACHE == 'true') {
        tep_reset_cache_block('categories');
        tep_reset_cache_block('also_purchased');
      }
    }
    
    tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $local_cPath . '&pID=' . $HTTP_GET_VARS['pID'] . '&mID=' . $local_mID . '&mNAME=' . $local_mNAME));
    break;
  case 'insert_category':
  case 'update_category':
    if (isset($HTTP_POST_VARS['categories_id'])) $categories_id = tep_db_prepare_input($HTTP_POST_VARS['categories_id']);
    $sort_order = tep_db_prepare_input($HTTP_POST_VARS['sort_order']);
    
    $sql_data_array = array('sort_order' => $sort_order);
    
    if ($categories_image = new upload('categories_image', DIR_FS_CATALOG_IMAGES)) {
      $categories_image_name = $categories_image->filename;
    } 
    if (($categories_image_name == "")&&(isset($HTTP_POST_VARS['categories_previous_image']))&&($HTTP_POST_VARS['categories_previous_image']!="")) {
      $categories_image_name = $HTTP_POST_VARS['categories_previous_image'];
    }

    if ($categories_image_name != "") {
      $sql_data_array = array_merge($sql_data_array, array('categories_image' => $categories_image_name));
//      tep_db_query("update " . TABLE_CATEGORIES . " set categories_image = '" . tep_db_input($categories_image_name) . "' where categories_id = '" . (int)$categories_id . "'");
    }
    
    if ($action == 'insert_category') {
      $insert_sql_data = array('parent_id' => $current_category_id, 'date_added' => 'now()');
      
      $sql_data_array = array_merge($sql_data_array, $insert_sql_data);
      
      tep_db_perform(TABLE_CATEGORIES, $sql_data_array);
      
      $categories_id = tep_db_insert_id();
    } elseif ($action == 'update_category') {
      $update_sql_data = array('last_modified' => 'now()');
      
      $sql_data_array = array_merge($sql_data_array, $update_sql_data);
      
      tep_db_perform(TABLE_CATEGORIES, $sql_data_array, 'update', "categories_id = '" . (int)$categories_id . "'");
    }
    
    $languages = tep_get_languages();
    for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
      $categories_name_array = $HTTP_POST_VARS['categories_name'];
      
      $language_id = $languages[$i]['id'];
      
      $sql_data_array = array('categories_name' => tep_db_prepare_input($categories_name_array[$language_id]));
      $sql_data_array = array_merge($sql_data_array, array('group_id' => tep_db_prepare_input($HTTP_POST_VARS['group_id'])));
      
      if ($action == 'insert_category') {
        $insert_sql_data = array('categories_id' => $categories_id,
        'language_id' => $languages[$i]['id']);
        
        $sql_data_array = array_merge($sql_data_array, $insert_sql_data);
        
        tep_db_perform(TABLE_CATEGORIES_DESCRIPTION, $sql_data_array);
      } elseif ($action == 'update_category') {
        tep_db_perform(TABLE_CATEGORIES_DESCRIPTION, $sql_data_array, 'update', "categories_id = '" . (int)$categories_id . "' and language_id = '" . (int)$languages[$i]['id'] . "'");
      }
    }
    
    if (USE_CACHE == 'true') {
      tep_reset_cache_block('categories');
      tep_reset_cache_block('also_purchased');
    }
    
    tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $local_cPath . '&cID=' . $categories_id));
    break;
  case 'delete_category_confirm':
    if (isset($HTTP_POST_VARS['categories_id'])) {
    $categories_id = tep_db_prepare_input($HTTP_POST_VARS['categories_id']);
    
    $categories = tep_get_category_tree($categories_id, '', '0', '', true);
    $products = array();
    $products_delete = array();
    
    for ($i=0, $n=sizeof($categories); $i<$n; $i++) {
    $product_ids_query = tep_db_query("select products_id from " . TABLE_PRODUCTS_TO_CATEGORIES . " where categories_id = '" . (int)$categories[$i]['id'] . "'");
    
    while ($product_ids = tep_db_fetch_array($product_ids_query)) {
    $products[$product_ids['products_id']]['categories'][] = $categories[$i]['id'];
    }
    }
    
    reset($products);
    while (list($key, $value) = each($products)) {
    $category_ids = '';
    
    for ($i=0, $n=sizeof($value['categories']); $i<$n; $i++) {
    $category_ids .= "'" . (int)$value['categories'][$i] . "', ";
    }
    $category_ids = substr($category_ids, 0, -2);
    
    $check_query = tep_db_query("select count(*) as total from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '" . (int)$key . "' and categories_id not in (" . $category_ids . ")");
    $check = tep_db_fetch_array($check_query);
    if ($check['total'] < '1') {
    $products_delete[$key] = $key;
    }
    }
    
    // removing categories can be a lengthy process
    tep_set_time_limit(0);
    for ($i=0, $n=sizeof($categories); $i<$n; $i++) {
    tep_remove_category($categories[$i]['id']);
    }
    
    reset($products_delete);
    while (list($key) = each($products_delete)) {
    tep_remove_product($key);
    }
    }
    
    if (USE_CACHE == 'true') {
    tep_reset_cache_block('categories');
    tep_reset_cache_block('also_purchased');
    }
    
    tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $local_cPath));
    break;
  case 'delete_product_confirm':
    if (isset($HTTP_POST_VARS['products_id']) && isset($HTTP_POST_VARS['product_categories']) && is_array($HTTP_POST_VARS['product_categories'])) {
    $product_id = tep_db_prepare_input($HTTP_POST_VARS['products_id']);
    $product_categories = $HTTP_POST_VARS['product_categories'];
    
    // on efface les orders_products des commandes récurrentes associées à ce produit
    $sql = "select op.products_name,o.customers_name,op.orders_products_id,o.orders_id,o.customers_email_address from orders_products as op left join orders as o on op.orders_id=o.orders_id where o.orders_status=4 and op.products_id='" . (int)$product_id . "';";
    $recurrences_query = tep_db_query($sql);
    $emails = "";
    while ($recurrences = tep_db_fetch_array($recurrences_query)) {
    $n = $recurrences['products_name'];
    $emails .= $recurrences['customers_name']." (op_id=".$recurrences['orders_products_id'].") &lt;".$recurrences['customers_email_address']."&gt;, ";
    
    // on efface (on rend orders_id négatif pour la traçabilité !)
    tep_db_query("UPDATE orders_products SET orders_id = -".(int)$recurrences['orders_id']." WHERE orders_products_id = ".$recurrences['orders_products_id'].";");
    
    //mise à jour des orders_total
    calcul_ot_op((int)$recurrences['orders_id']);
    }
    if ($emails) {
    $emails = "<b>Produit supprimé :</b> ".$n." (".(int)$product_id.")<br><br><b>Commandes récurrentes pour :</b><br>".substr($emails, 0, -2);
    tep_mail('groms', 'groms@free.fr', 'SUPPRESSION PRODUIT $n - AUFLEURIPOTAGER', $emails, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
    }
    
    for ($i=0, $n=sizeof($product_categories); $i<$n; $i++) {
    tep_db_query("delete from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '" . (int)$product_id . "' and categories_id = '" . (int)$product_categories[$i] . "'");
    }
    
    $product_categories_query = tep_db_query("select count(*) as total from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '" . (int)$product_id . "'");
    $product_categories = tep_db_fetch_array($product_categories_query);
    
    if ($product_categories['total'] == '0') {
    tep_remove_product($product_id);
    }
    
    }
    
    if (USE_CACHE == 'true') {
    tep_reset_cache_block('categories');
    tep_reset_cache_block('also_purchased');
    }
    
    tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $local_cPath));
    break;
  case 'move_category_confirm':
    if (isset($HTTP_POST_VARS['categories_id']) && ($HTTP_POST_VARS['categories_id'] != $HTTP_POST_VARS['move_to_category_id'])) {
      $categories_id = tep_db_prepare_input($HTTP_POST_VARS['categories_id']);
      $new_parent_id = tep_db_prepare_input($HTTP_POST_VARS['move_to_category_id']);
      
      $path = explode('_', tep_get_generated_category_path_ids($new_parent_id));
      
      if (in_array($categories_id, $path)) {
        $messageStack->add_session(ERROR_CANNOT_MOVE_CATEGORY_TO_PARENT, 'error');
        
        tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $local_cPath . '&cID=' . $categories_id));
      } else {
        tep_db_query("update " . TABLE_CATEGORIES . " set parent_id = '" . (int)$new_parent_id . "', last_modified = now() where categories_id = '" . (int)$categories_id . "'");
        
        if (USE_CACHE == 'true') {
          tep_reset_cache_block('categories');
          tep_reset_cache_block('also_purchased');
        }
      
        tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $new_parent_id . '&cID=' . $categories_id));
      }
    }
    
    break;
  case 'move_product_confirm':
    $products_id = tep_db_prepare_input($HTTP_POST_VARS['products_id']);
    $new_parent_id = tep_db_prepare_input($HTTP_POST_VARS['move_to_category_id']);
    
    $duplicate_check_query = tep_db_query("select count(*) as total from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '" . (int)$products_id . "' and categories_id = '" . (int)$new_parent_id . "'");
    $duplicate_check = tep_db_fetch_array($duplicate_check_query);
    if ($duplicate_check['total'] < 1) tep_db_query("update " . TABLE_PRODUCTS_TO_CATEGORIES . " set categories_id = '" . (int)$new_parent_id . "' where products_id = '" . (int)$products_id . "' and categories_id = '" . (int)$current_category_id . "'");
    
    if (USE_CACHE == 'true') {
    tep_reset_cache_block('categories');
    tep_reset_cache_block('also_purchased');
    }
    
    tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $new_parent_id . '&pID=' . $products_id));
    break;
  case 'insert_product':
  case 'update_product':
    if (isset($HTTP_POST_VARS['edit_x']) || isset($HTTP_POST_VARS['edit_y'])) {
      $action = 'new_product';
    } else {
      if (isset($HTTP_GET_VARS['pID'])) $products_id = tep_db_prepare_input($HTTP_GET_VARS['pID']);

      $products_date_available = tep_db_prepare_input($HTTP_POST_VARS['products_date_available']);
      $products_date_available = (date('Y-m-d') < $products_date_available) ? $products_date_available : 'null';
      
      $sql = "select group_id from manufacturers where manufacturers_id = '" . tep_db_prepare_input($HTTP_POST_VARS['manufacturers_id']) . "';";
      $query = tep_db_query($sql);
      $group_id = 0;
      if ($res = tep_db_fetch_array($query)) {
        $group_id = $res['group_id'];
      }
      
      $cond = tep_db_prepare_input($HTTP_POST_VARS['products_min_manufacturer_quantity']);
      if ($cond <= 0) $cond = 1;
      
      $sql_data_array = array(
        'products_quantity' => tep_format_qty_for_db(tep_db_prepare_input($HTTP_POST_VARS['products_quantity'])),
        'products_model' => tep_db_prepare_input($HTTP_POST_VARS['products_model']),
        'products_price' => tep_format_qty_for_db(tep_db_prepare_input($HTTP_POST_VARS['products_price'])),
        'products_min_manufacturer_quantity' => $cond,
        'products_limitation' => tep_db_prepare_input($HTTP_POST_VARS['products_limitation']),
        'products_reference' => tep_db_prepare_input($HTTP_POST_VARS['products_reference']),
        'products_date_available' => $products_date_available,
        'products_weight' => tep_db_prepare_input($HTTP_POST_VARS['products_weight']),
        'products_status' => tep_db_prepare_input($HTTP_POST_VARS['products_status']),
        'products_tax_class_id' => tep_db_prepare_input($HTTP_POST_VARS['products_tax_class_id']),
        'is_bulk' => tep_db_prepare_input($HTTP_POST_VARS['is_bulk']),
        'measure_unit' => tep_db_prepare_input($HTTP_POST_VARS['measure_unit']),
        'shipping_day' => tep_db_prepare_input($HTTP_POST_VARS['shipping_day']),
        'shipping_frequency' => tep_db_prepare_input($HTTP_POST_VARS['shipping_frequency']),
        'authorized_weights' => tep_db_prepare_input($HTTP_POST_VARS['authorized_weights']),                  
        'group_id' => $group_id,
        'manufacturers_id' => tep_db_prepare_input($HTTP_POST_VARS['manufacturers_id']));
      
      if (isset($HTTP_POST_VARS['products_image']) && 
            tep_not_null($HTTP_POST_VARS['products_image']) && ($HTTP_POST_VARS['products_image'] != 'none')) {
        $sql_data_array['products_image'] = tep_db_prepare_input($HTTP_POST_VARS['products_image']);
      }
        
      $reload = true;
      
      if ($action == 'insert_product') {
        $insert_sql_data = array('products_date_added' => 'now()');
        
        $sql_data_array = array_merge($sql_data_array, $insert_sql_data);
        
        tep_db_perform(TABLE_PRODUCTS, $sql_data_array);
        $products_id = tep_db_insert_id();
        
        tep_db_query("insert into " . TABLE_PRODUCTS_TO_CATEGORIES . " (products_id, categories_id) values ('" . (int)$products_id . "', '" . (int)$current_category_id . "')");
      } elseif ($action == 'update_product') {
        $update_sql_data = array('products_last_modified' => 'now()');
        
        $sql_data_array = array_merge($sql_data_array, $update_sql_data);
        
        tep_db_perform(TABLE_PRODUCTS, $sql_data_array, 'update', "products_id = '" . (int)$products_id . "'");
        
      	// ajoute une notification de modif dans la nouvelle table products_prices_modifications
        $old_price = tep_format_qty_for_db($HTTP_POST_VARS['old_products_price']);
        $new_price = tep_format_qty_for_db($HTTP_POST_VARS['products_price']);
        $sql_add = "INSERT INTO products_prices_modifications (products_prices_modifications_datetime, products_id, old_price, new_price, comments) 
              VALUES ('".date("Y-m-d H:i:s")."','$products_id','".$old_price."','".$new_price."', 
              'modification du produit - categories.php');";
        tep_db_query($sql_add, 'db_link');
        
        propagate_shipping_modifications($HTTP_POST_VARS['manufacturers_id'], $HTTP_POST_VARS['shipping_day'], $HTTP_POST_VARS['shipping_frequency'], "TABLE_PRODUCTS", (int)$products_id);
        
      }
      
      $languages = tep_get_languages();
      for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
        $language_id = $languages[$i]['id'];
        
        $sql_data_array = array('products_name' => tep_db_prepare_input($HTTP_POST_VARS['products_name'][$language_id]),
            'products_description' => tep_db_prepare_input($HTTP_POST_VARS['products_description'][$language_id]),
            'products_hidden_info' => tep_db_prepare_input($HTTP_POST_VARS['products_hidden_info']),                  
            'products_url' => tep_db_prepare_input($HTTP_POST_VARS['products_url'][$language_id]));
        
        if ($action == 'insert_product') {
          $insert_sql_data = array('products_id' => $products_id,
          'language_id' => $language_id);
          
          $sql_data_array = array_merge($sql_data_array, $insert_sql_data);
          
          tep_db_perform(TABLE_PRODUCTS_DESCRIPTION, $sql_data_array);
        } elseif ($action == 'update_product') {
          tep_db_perform(TABLE_PRODUCTS_DESCRIPTION, $sql_data_array, 'update', "products_id = '" . (int)$products_id . "' and language_id = '" . (int)$language_id . "'");
          
          // on modifie tous les enreg de order_products
//              products_name = '".addslashes_once(tep_db_prepare_input($HTTP_POST_VARS['products_name'][$language_id]))."',
          $sql_add = "UPDATE orders_products SET
              products_price = '".tep_format_qty_for_db(tep_db_prepare_input($HTTP_POST_VARS['products_price']))."',
              final_price = '".tep_format_qty_for_db(tep_db_prepare_input($HTTP_POST_VARS['products_price']))."'
              WHERE products_id = $products_id;";
          tep_db_query($sql_add, 'db_link');

          // on modifie tous les enreg de order_products_modifications dont la date_shipped >= date d'aujourd'hui
          $sql_add = "UPDATE orders_products_modifications SET
              final_price = '".tep_format_qty_for_db(tep_db_prepare_input($HTTP_POST_VARS['products_price']))."'
              WHERE products_id = $products_id AND date_shipped >= '".date("Y-m-d")."';";
          tep_db_query($sql_add, 'db_link');
          
          //on met à jour les product_names dans ops, op, opm
          propagate_product_name_changes($products_id);          
          
          // on met à jour les orders_total
          $sql = "select o.orders_id from orders_products as op left join orders as o on op.orders_id=o.orders_id where op.products_id='" . (int)$products_id . "';";
          $ot_op_query = tep_db_query($sql);
          while ($ot_op = tep_db_fetch_array($ot_op_query)) {
            calcul_ot_op((int)$ot_op['orders_id']);
          }
        }
      }
      
      if (USE_CACHE == 'true') {
        tep_reset_cache_block('categories');
        tep_reset_cache_block('also_purchased');
      }
      
      if ($reload) tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $local_cPath . '&pID=' . $products_id . '&mID=' . $local_mID . '&mNAME=' . $local_mNAME));
    }
    break;
  case 'copy_to_confirm':
    if (isset($HTTP_POST_VARS['products_id']) && isset($HTTP_POST_VARS['categories_id'])) {
    $products_id = tep_db_prepare_input($HTTP_POST_VARS['products_id']);
    $categories_id = tep_db_prepare_input($HTTP_POST_VARS['categories_id']);
    
    if ($HTTP_POST_VARS['copy_as'] == 'link') {
    if ($categories_id != $current_category_id) {
    $check_query = tep_db_query("select count(*) as total from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '" . (int)$products_id . "' and categories_id = '" . (int)$categories_id . "'");
    $check = tep_db_fetch_array($check_query);
    if ($check['total'] < '1') {
    tep_db_query("insert into " . TABLE_PRODUCTS_TO_CATEGORIES . " (products_id, categories_id) values ('" . (int)$products_id . "', '" . (int)$categories_id . "')");
    }
    } else {
    $messageStack->add_session(ERROR_CANNOT_LINK_TO_SAME_CATEGORY, 'error');
    }
    } elseif ($HTTP_POST_VARS['copy_as'] == 'duplicate') {
    $product_query = tep_db_query("select products_quantity, products_model, products_image, products_price, products_date_available, products_weight, products_tax_class_id, products_min_manufacturer_quantity, is_bulk, products_reference, measure_unit, shipping_day, shipping_frequency, authorized_weights, products_limitation, manufacturers_id, group_id from " . TABLE_PRODUCTS . " where products_id = '" . (int)$products_id . "'");
    $product = tep_db_fetch_array($product_query);
    
    tep_db_query("insert into " . TABLE_PRODUCTS . " (products_quantity, products_model, products_image, products_price, products_date_added, products_date_available, products_weight, products_status, products_tax_class_id, products_min_manufacturer_quantity, is_bulk, measure_unit, shipping_day, shipping_frequency, authorized_weights, products_reference, products_limitation, manufacturers_id, group_id) values ('" . tep_db_input($product['products_quantity']) . "', '" . tep_db_input($product['products_model']) . "', '" . tep_db_input($product['products_image']) . "', '" . tep_db_input($product['products_price']) . "',  now(), " . (empty($product['products_date_available']) ? "null" : "'" . tep_db_input($product['products_date_available']) . "'") . ", '" . tep_db_input($product['products_weight']) . "', '0', '" . (int)$product['products_tax_class_id'] . "', '" . $product['products_min_manufacturer_quantity'] . "', '" . (int)$product['is_bulk'] . "', '" . $product['measure_unit'] . "', '" . $product['shipping_day'] . "', '" . $product['shipping_frequency'] . "', '" . $product['authorized_weights'] . "', '" . $product['products_reference'] . "', '" . (int)$product['products_limitation'] . "', '" . (int)$product['manufacturers_id'] . "', '" . (int)$product['group_id'] . "')");
    $dup_products_id = tep_db_insert_id();
    
    $description_query = tep_db_query("select language_id, products_name, products_description, products_url from " . TABLE_PRODUCTS_DESCRIPTION . " where products_id = '" . (int)$products_id . "'");
    while ($description = tep_db_fetch_array($description_query)) {
    tep_db_query("insert into " . TABLE_PRODUCTS_DESCRIPTION . " (products_id, language_id, products_name, products_description, products_url, products_viewed) values ('" . (int)$dup_products_id . "', '" . (int)$description['language_id'] . "', '" . tep_db_input($description['products_name']) . "', '" . tep_db_input($description['products_description']) . "', '" . tep_db_input($description['products_url']) . "', '0')");
    }
    
    tep_db_query("insert into " . TABLE_PRODUCTS_TO_CATEGORIES . " (products_id, categories_id) values ('" . (int)$dup_products_id . "', '" . (int)$categories_id . "')");
    $products_id = $dup_products_id;
    }
    
    if (USE_CACHE == 'true') {
    tep_reset_cache_block('categories');
    tep_reset_cache_block('also_purchased');
    }
    }
    
    tep_redirect(tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $categories_id . '&pID=' . $products_id));
  break;
  case 'new_product_preview':
    // copy image only if modified
    $authorized_weights_str = "";
    $products_image_name = "";
    if ($products_image = new upload('products_image', DIR_FS_CATALOG_IMAGES)) {
      $products_image_name = $products_image->filename;
    } 
    if (($products_image_name == "")&&(isset($HTTP_POST_VARS['products_previous_image']))&&($HTTP_POST_VARS['products_previous_image']!="")) {
      $products_image_name = $HTTP_POST_VARS['products_previous_image'];
    }
/*
    $products_image->set_destination(DIR_FS_CATALOG_IMAGES);
    if ($products_image->parse()&& $products_image->save()) {
      $products_image_name = $products_image->filename;
    } else {
      $products_image_name = (isset($HTTP_POST_VARS['products_previous_image']) ? $HTTP_POST_VARS['products_previous_image'] : '');
    }
*/
  
    if (isset($HTTP_POST_VARS['authorized_weights'])) {
      for ($i = 0; $i < count($HTTP_POST_VARS["authorized_weights"]); $i++) {
        $authorized_weights_str .= tep_format_qty_for_db($HTTP_POST_VARS["authorized_weights"][$i]) . "|";  
      }
      $authorized_weights_str = substr($authorized_weights_str, 0, -1);
    }

    break;
  }
}

// check if the catalog image directory exists
if (is_dir(DIR_FS_CATALOG_IMAGES)) {
if (!is_writeable(DIR_FS_CATALOG_IMAGES)) $messageStack->add(ERROR_CATALOG_IMAGE_DIRECTORY_NOT_WRITEABLE, 'error');
} else {
$messageStack->add(ERROR_CATALOG_IMAGE_DIRECTORY_DOES_NOT_EXIST, 'error');
}
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <? echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<? echo CHARSET; ?>">
<title><? echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="includes/general.js"></script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF" onload="SetFocus();">
<div id="spiffycalendar" class="text"></div>
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
<?
if ($action == 'new_product') {
$parameters = array('products_name' => '',
'products_description' => '',
'products_url' => '',
'products_id' => '',
'products_quantity' => 0,
'products_model' => '',
'products_image' => '',
'products_price' => '',
'products_weight' => 0,
'products_date_added' => '',
'products_last_modified' => '',
'products_date_available' => '',
'products_status' => '',
'is_bulk' => '',
'measure_unit' => '',
'shipping_day' => '',
'shipping_frequency' => '',
'authorized_weights' => '',
'products_hidden_info' => '',                  
'products_min_manufacturer_quantity' => '',
'products_reference' => '',
'products_limitation' => '',
'products_tax_class_id' => '',
'manufacturers_id' => '');

$pInfo = new objectInfo($parameters);

$sql_mID = "";
if ($local_mID) {
$sql_mID = " and p.manufacturers_id = '" . $local_mID . "'";
}

if (isset($HTTP_GET_VARS['pID']) && empty($HTTP_POST_VARS)) {
  $product_query = tep_db_query("select pd.products_hidden_info, pd.products_name, p.group_id, pd.products_description, pd.products_url, p.products_id, p.products_min_manufacturer_quantity, p.products_reference, p.products_limitation, p.products_quantity, p.products_model, p.products_image, p.products_price, p.products_weight, p.products_date_added, p.products_last_modified, date_format(p.products_date_available, '%Y-%m-%d') as products_date_available, p.products_status, p.products_tax_class_id, p.manufacturers_id, p.is_bulk, p.measure_unit, p.shipping_day, p.shipping_frequency, p.authorized_weights from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = '" . (int)$HTTP_GET_VARS['pID'] . "' and p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "'".$sql_mID);
  $product = tep_db_fetch_array($product_query);
  
  $pInfo->objectInfo($product);
} elseif (tep_not_null($HTTP_POST_VARS)) {
  $pInfo->objectInfo($HTTP_POST_VARS);
  $products_name = $HTTP_POST_VARS['products_name'];
  $products_description = $HTTP_POST_VARS['products_description'];
  $products_url = $HTTP_POST_VARS['products_url'];
}

getAuthorizedArray();

$manufacturers_array = array(array('id' => '', 'text' => TEXT_NONE));
$manufacturers_query = tep_db_query("select manufacturers_id, manufacturers_name, group_id from " . TABLE_MANUFACTURERS . " order by group_id, manufacturers_name");
while ($manufacturers = tep_db_fetch_array($manufacturers_query)) {
  if ($manufacturers['group_id'] == "0") {
    $m_name = "RVD-";
  } else {
    $m_name = "GA-";
  }
  $m_name .= $manufacturers['manufacturers_name'];
  $manufacturers_array[] = array('id' => $manufacturers['manufacturers_id'],
                            'text' => $m_name);
}

$tax_class_array = array(array('id' => '0', 'text' => TEXT_NONE));
$tax_class_query = tep_db_query("select tax_class_id, tax_class_title from " . TABLE_TAX_CLASS . " order by tax_class_title");
while ($tax_class = tep_db_fetch_array($tax_class_query)) {
  $tax_class_array[] = array('id' => $tax_class['tax_class_id'],
  'text' => $tax_class['tax_class_title']);
}

$bulk_array = array(array('id' => '0', 'text' => ''),array('id' => '1', 'text' => 'Oui'));
$measure_array = array(array('id' => '', 'text' => ''),array('id' => 'kilo', 'text' => 'Kilo'),array('id' => 'litre', 'text' => 'Litre'));

$languages = tep_get_languages();

if (!isset($pInfo->products_status)) $pInfo->products_status = '1';
switch ($pInfo->products_status) {
  case '0': $in_status = false; $out_status = true; break;
  case '1':
  default: $in_status = true; $out_status = false;
}
?>
<link rel="stylesheet" type="text/css" href="includes/javascript/spiffyCal/spiffyCal_v2_1.css">
<script language="JavaScript" src="includes/javascript/spiffyCal/spiffyCal_v2_1.js"></script>
<script language="javascript"><!--
var dateAvailable = new ctlSpiffyCalendarBox("dateAvailable", "new_product", "products_date_available","btnDate1","<? echo $pInfo->products_date_available; ?>",scBTNMODE_CUSTOMBLUE);
//--></script>
<script language="javascript"><!--
var tax_rates = new Array();
<?
for ($i=0, $n=sizeof($tax_class_array); $i<$n; $i++) {
  if ($tax_class_array[$i]['id'] > 0) {
    echo 'tax_rates["' . $tax_class_array[$i]['id'] . '"] = ' . tep_get_tax_rate_value($tax_class_array[$i]['id']) . ';' . "\n";
  }
}
?>

function doRound(x, places) {
  return Math.round(x * Math.pow(10, places)) / Math.pow(10, places);
}

function getTaxRate() {
  var selected_value = document.forms["new_product"].products_tax_class_id.selectedIndex;
  var parameterVal = document.forms["new_product"].products_tax_class_id[selected_value].value;
  
  if ( (parameterVal > 0) && (tax_rates[parameterVal] > 0) ) {
    return tax_rates[parameterVal];
  } else {
    return 0;
  }
}

function updateBulk(bFromMU) {
  var sTxt = "<? echo TEXT_PRODUCTS_PRICE_NET; ?>";
  var sTxtStock = "Stock restant";
  var sTxtMC = "Conditionnement fournisseur";
  var isBulk = document.forms["new_product"].is_bulk;
  var mU = document.forms["new_product"].measure_unit;
  var div = document.getElementById("div_price_net");
  var divStock = document.getElementById("stockLeft");
  var divMC = document.getElementById("minConditioning");
  var divAW1 = document.getElementById("authorized_weights_div1");
  var divAW2 = document.getElementById("authorized_weights_div2");

  mU.disabled = (isBulk.value != "1");
  div.innerHTML = sTxt;
  divStock.innerHTML = sTxtStock + " :";
  divMC.innerHTML = sTxtMC + " :";
  if (mU.disabled) {
    mU.value = "";
    divAW1.style.display = "none";
  } else {
    divAW1.style.display = "block";
    if (!bFromMU) { mU.selectedIndex = 1; }
    if (mU.value != "") {
      sMU = mU.options[mU.selectedIndex].text;
      div.innerHTML = "Prix du produit (au " + sMU + ") TTC :";
      sAux = " (en " + sMU + ") :";
      divStock.innerHTML = sTxtStock + sAux;
      divMC.innerHTML = sTxtMC + sAux;
    }
  }
  divAW2.style.display = divAW1.style.display;
}

<?=putShippingJS("new_product");?>

function updateM() {
  var shippingDay;
  var shippingFrequency;
  var man;
  
  shippingDay = document.forms['new_product'].shipping_day;
  shippingFrequency = document.forms['new_product'].shipping_frequency;
  man = document.forms['new_product'].manufacturers_id;
  
  man_name = man.options[man.selectedIndex].text;
  
  if (man_name.substr(0,2) == "GA") {
    shippingDay.selectedIndex = 3; //saturday
    shippingFrequency.selectedIndex = shippingFrequency.length - 1; //4.0
  } else {
    shippingDay.selectedIndex = 1; //thursday
    shippingFrequency.selectedIndex = 1; //1.0
  }
}

function updateGross() {
  var taxRate = getTaxRate();
  var grossValue = document.forms["new_product"].products_price.value;
  
  if (taxRate > 0) {
    grossValue = grossValue * ((taxRate / 100) + 1);
  }
  
  document.forms["new_product"].products_price_gross.value = doRound(grossValue, 4);
}

function updateNet() {
  var taxRate = getTaxRate();
  var netValue = document.forms["new_product"].products_price_gross.value;
  
  if (taxRate > 0) {
    netValue = netValue / ((taxRate / 100) + 1);
  }
  
  document.forms["new_product"].products_price.value = doRound(netValue, 4);
}
//--></script>
<? 
echo tep_draw_form('new_product', FILENAME_CATEGORIES, 'cPath=' . $local_cPath . (isset($HTTP_GET_VARS['pID']) ? '&pID=' . $HTTP_GET_VARS['pID'] : '') . '&action=new_product_preview&mID=' . $local_mID . '&mNAME=' . $local_mNAME, 'post', 'enctype="multipart/form-data"'); ?>
<table border="0" width="100%" cellspacing="0" cellpadding="2">
<tr>
  <td>
    <table border="0" width="100%" cellspacing="0" cellpadding="0">
    <tr>
    <td class="pageHeading"><? echo sprintf(TEXT_NEW_PRODUCT, tep_output_generated_category_path($current_category_id)); ?></td>
    <td class="pageHeading" align="right"><? echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
    </tr>
    </table>
  </td>
</tr>
<tr>
<td><? echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
</tr>
<tr>
<td colspan="2"><table border="0" cellspacing="0" cellpadding="2">
<tr>
<td colspan="2"><? echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
</tr>
<tr>
<td class="main" align="center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
<td class="main" align="left" width="100%"><? echo tep_image_submit('button_preview.gif', IMAGE_PREVIEW) . '&nbsp;&nbsp;<a href="' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $local_cPath . (isset($HTTP_GET_VARS['pID']) ? '&pID=' . $HTTP_GET_VARS['pID'] : '') . '&mID=' . $local_mID . '&mNAME=' . $local_mNAME) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>'; ?></td>
</tr>
<tr>
<td class="main"><? echo TEXT_PRODUCTS_STATUS; ?></td>
<td class="main"><? echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_radio_field('products_status', '1', $in_status) . '&nbsp;' . TEXT_PRODUCT_AVAILABLE . '&nbsp;' . tep_draw_radio_field('products_status', '0', $out_status) . '&nbsp;' . TEXT_PRODUCT_NOT_AVAILABLE; ?></td>
</tr>
<tr>
<td colspan="2"><? echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
</tr>
<tr>
<td class="main"><? echo TEXT_PRODUCTS_DATE_AVAILABLE; ?><br><small>(YYYY-MM-DD)</small></td>
<td class="main"><? echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;'; ?><script language="javascript">dateAvailable.writeControl(); dateAvailable.dateFormat="yyyy-MM-dd";</script></td>
</tr>
<tr>
<td class="main" nowrap align="left">
  Mode de livraison du produit : 
</td>
<td><? 
  echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;';
  echo putShipping($pInfo->manufacturers_id, $pInfo->shipping_day, $pInfo->shipping_frequency, "", "p", false, $HTTP_GET_VARS['action'] == 'new_product');

?></td>
</tr>
<tr>
<td colspan="2"><? echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
</tr>
<tr>
<td class="main"><? echo TEXT_PRODUCTS_MANUFACTURER; ?></td>
<td class="main"><? echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_pull_down_menu('manufacturers_id', $manufacturers_array, $pInfo->manufacturers_id, ' onchange=updateM(); '); ?></td>
</tr>
<tr>
<td colspan="2"><? echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
</tr>
<?
for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
?>
<tr>
<td class="main"><b><? if ($i == 0) echo TEXT_PRODUCTS_NAME; ?></b></td>
<td class="main"><? echo tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '<br>' . tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('products_name[' . $languages[$i]['id'] . ']', (isset($products_name[$languages[$i]['id']]) ? stripslashes($products_name[$languages[$i]['id']]) : tep_get_products_name($pInfo->products_id, $languages[$i]['id'])),'size="80"'); ?></td>
</tr>
<?
}


?>
<tr>
<td class="main"><b>Référence produit : </b></td>
<td class="main"><? echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;'.tep_draw_input_field('products_reference', $pInfo->products_reference); ?></td>
</tr>

<!--
<tr>
<td colspan="2"><? echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
</tr>
<tr bgcolor="#ebebff">
<td class="main"><? echo TEXT_PRODUCTS_TAX_CLASS; ?></td>
<td class="main"><? echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_pull_down_menu('products_tax_class_id', $tax_class_array, $pInfo->products_tax_class_id, 'onchange="updateGross()"'); ?></td>
</tr>
-->
<tr bgcolor="#ebebff">
<td class="main"><div id="div_price_net"></div></td>
<td class="main"><? echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . 
  tep_draw_input_field('products_price', tep_format_qty_for_html($pInfo->products_price), 'onKeyUp="updateGross()"').
  tep_draw_hidden_field('old_products_price', tep_format_qty_for_db($pInfo->products_price), '');
  
   ?></td>
</tr>
<tr>
<td colspan="2"><? echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
</tr>
<tr bgcolor="orange">
<td class="main" nowrap><div id="stockLeft"></div></td>
<td class="main"><? echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('products_quantity', tep_format_qty_for_html($pInfo->products_quantity)); ?></td>
</tr>

<tr><td colspan="2"></td></tr>

<tr bgcolor="#ffebeb">
<td class="main">
<? echo "Vente en vrac : "; ?>
<? echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_pull_down_menu('is_bulk', $bulk_array, $pInfo->is_bulk, 'onchange="updateBulk(false)"'); ?>
</td>
<td class="main">
<? echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;'."Unit&eacute; de mesure"; ?>
<? echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_pull_down_menu('measure_unit', $measure_array, $pInfo->measure_unit, 'onchange="updateBulk(true)"'); ?>
</td>
</tr>

<?   ($pInfo->is_bulk == 1) ? $style = "block" : $style = "none"; ?>


<tr bgcolor="#ffebeb">
<td class="main" valign="middle">
<div id="authorized_weights_div1" style="display:<?=$style?>;">
   <b>Quantités disponibles à la commande :</b>
</div>
</td>
<td class="main"  valign="middle" align="center">
<div id="authorized_weights_div2" style="position:relative;display:<?=$style?>;">
  <table border="1px">
    <tr><td class="main" align="center"><input type="checkbox" id="checkbox_all" onclick="javascript:checkAll();" /><a href="javascript:checkAllLink();"><b>Tout cocher/décocher</b></a></tr>
    <tr><td class="main" align="center"><? echo $weights_checkbox; ?></td></tr>
  </table>
  
  <script>
    function checkAllLink() {
      document.getElementById("checkbox_all").checked = !document.getElementById("checkbox_all").checked;  
      checkAll();
    }

    function checkAll() {
      for (i=1;i<=<?=count($defaultAuthorizedWeights_array);?>;i++) {
        document.getElementById("authorized_weights" + i).checked = document.getElementById("checkbox_all").checked;
      }
    }
    document.getElementById("checkbox_all").checked = false;
//    checkAllLink();
  </script>
  
   </td></tr> 
</div>
</td>
</tr>
<tr bgcolor="#ffebeb">
<td class="main"><? echo TEXT_PRODUCTS_WEIGHT; ?></td>
<td class="main"><? echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('products_weight', tep_format_qty_for_html($pInfo->products_weight)); ?></td>
</tr>
<tr bgcolor="#ffebeb">
<td class="main"><div id="minConditioning"></div></td>
<td class="main"><? echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('products_min_manufacturer_quantity', tep_format_qty_for_html($pInfo->products_min_manufacturer_quantity)); ?></td>
</tr>


<!--
<tr bgcolor="#ebebff">
<td class="main"><? echo TEXT_PRODUCTS_PRICE_GROSS; ?></td>
<td class="main"><? echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('products_price_gross', $pInfo->products_price, 'OnKeyUp="updateNet()"'); ?></td>
</tr>
-->
<tr>
<td colspan="2"><? echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
</tr>
<script language="javascript"><!--
updateGross();
//--></script>
<?
for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
?>
<tr>
  <td class="main" valign="top"><? if ($i == 0) echo TEXT_PRODUCTS_DESCRIPTION; ?></td>
  <td>
    <table border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td class="main" valign="top"><? echo tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']); ?><br>
        <? echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_textarea_field('products_description[' . $languages[$i]['id'] . ']', 'soft', '80', '15', (isset($products_description[$languages[$i]['id']]) ? stripslashes($products_description[$languages[$i]['id']]) : tep_get_products_description($pInfo->products_id, $languages[$i]['id']))); ?></td>
      </tr>
    </table>
  </td>
</tr>
<?
}
?>
<!--
<tr>
<td class="main"><? echo "Capacité maximale (par semaine): "; ?></td>
<td class="main"><? echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('products_limitation', $pInfo->products_limitation); ?></td>
</tr>
<tr>
<td colspan="2"><? echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
</tr>
<tr>
<td class="main"><? echo TEXT_PRODUCTS_MODEL; ?></td>
<td class="main"><? echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('products_model', $pInfo->products_model); ?></td>
</tr>
-->
<tr>
<td colspan="2"><? echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
</tr>
<tr>
<td class="main"><? echo TEXT_PRODUCTS_IMAGE; ?></td>
<td class="main"><? echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_file_field('products_image') . '<br>' . tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . $pInfo->products_image . tep_draw_hidden_field('products_previous_image', $pInfo->products_image); ?></td>
</tr>
<!--
<tr>
<td colspan="2"><? echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
</tr>

<?
for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
?>
<tr>
<td class="main"><? if ($i == 0) echo TEXT_PRODUCTS_URL . '<br><small>' . TEXT_PRODUCTS_URL_WITHOUT_HTTP . '</small>'; ?></td>
<td class="main"><? echo tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('products_url[' . $languages[$i]['id'] . ']', (isset($products_url[$languages[$i]['id']]) ? stripslashes($products_url[$languages[$i]['id']]) : tep_get_products_url($pInfo->products_id, $languages[$i]['id'])), 'size="50"'); ?></td>
</tr>
<?
}
?>
<tr>
<td colspan="2"><? echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
</tr>
-->
<tr>
  <td class="main" valign="top">Info produit (<b>cachée</b>, visible uniquement <u>en admin</u>) :</td>
  <td>
<? echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_textarea_field('products_hidden_info', 'soft', '80', '5', $pInfo->products_hidden_info); ?>  </td>
</tr>
<tr>
<td colspan="2"><? echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
</tr>

<tr>
<td class="main"><? echo "Type: "; ?></td>
<td class="main"><? echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_pull_down_menu('group_id', $groups_array, $pInfo->group_id, 'width="50px" disabled'); ?></td>
</tr>
<tr>
<td colspan="2"><? echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
</tr>
<tr>
<td class="main"></td>
<td class="main"><? echo tep_draw_separator('pixel_trans.gif', '24', '15').tep_draw_hidden_field('products_date_added', (tep_not_null($pInfo->products_date_added) ? $pInfo->products_date_added : date('Y-m-d'))) . tep_image_submit('button_preview.gif', IMAGE_PREVIEW) . '&nbsp;&nbsp;<a href="' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $local_cPath . (isset($HTTP_GET_VARS['pID']) ? '&pID=' . $HTTP_GET_VARS['pID'] : '') . '&mID=' . $local_mID . '&mNAME=' . $local_mNAME) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>'; ?></td>
</tr>
</table>
</td>
</tr>
</table>
</form>
<?
} elseif ($action == 'new_product_preview') {
  if (tep_not_null($HTTP_POST_VARS)) {
    $pInfo = new objectInfo($HTTP_POST_VARS);
    $products_name = $HTTP_POST_VARS['products_name'];
    $products_description = $HTTP_POST_VARS['products_description'];
    $products_url = $HTTP_POST_VARS['products_url'];
  } else {
    $product_query = tep_db_query("select p.products_id, pd.language_id, pd.products_name, pd.products_description, pd.products_url, p.products_quantity, p.products_model, p.products_image, p.products_price, p.products_weight, p.products_date_added, p.products_last_modified, p.products_date_available, p.products_status, p.manufacturers_id, p.products_min_manufacturer_quantity, p.is_bulk, p.measure_unit, p.shipping_day, p.shipping_frequency, p.authorized_weights, p.products_reference, p.products_limitation from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = pd.products_id and p.products_id = '" . (int)$HTTP_GET_VARS['pID'] . "'");
    $product = tep_db_fetch_array($product_query);
    
    $pInfo = new objectInfo($product);
    $products_image_name = $pInfo->products_image;
  }

  $form_action = (isset($HTTP_GET_VARS['pID'])) ? 'update_product' : 'insert_product';
  
  echo tep_draw_form($form_action, FILENAME_CATEGORIES, 'cPath=' . $local_cPath . (isset($HTTP_GET_VARS['pID']) ? '&pID=' . $HTTP_GET_VARS['pID'] : '') . '&action=' . $form_action . '&mID=' . $local_mID . '&mNAME=' . $local_mNAME, 'post', 'enctype="multipart/form-data"');
  
  $languages = tep_get_languages();
  for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
    if (isset($HTTP_GET_VARS['read']) && ($HTTP_GET_VARS['read'] == 'only')) {
    $pInfo->products_name = tep_get_products_name($pInfo->products_id, $languages[$i]['id']);
    $pInfo->products_description = tep_get_products_description($pInfo->products_id, $languages[$i]['id']);
    $pInfo->products_url = tep_get_products_url($pInfo->products_id, $languages[$i]['id']);
    } else {
      $pInfo->products_name = tep_db_prepare_input($products_name[$languages[$i]['id']]);
      $pInfo->products_description = tep_db_prepare_input($products_description[$languages[$i]['id']]);
      $pInfo->products_url = tep_db_prepare_input($products_url[$languages[$i]['id']]);
    }
    ?>
    <table border="0" width="100%" cellspacing="0" cellpadding="2">
    <tr>
    <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
    <tr>
    <td class="pageHeading"><? echo tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . $pInfo->products_name; ?></td>
    <td class="pageHeading" align="right"><? echo $currencies->format($pInfo->products_price); ?></td>
    </tr>
    </table></td>
    </tr>
    <tr>
    <td><? echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
    </tr>
    <tr>
    <td class="main"><? echo tep_image(DIR_WS_CATALOG_IMAGES . $products_image_name, $pInfo->products_name, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT, 'align="right" hspace="5" vspace="5"', CATEGORY_ADMIN_MAX_IMAGE_SIZE) . $pInfo->products_description; ?></td>
    </tr>
    <?
    if ($pInfo->products_url) {
      ?>
      <tr>
      <td><? echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
      <td class="main"><? echo sprintf(TEXT_PRODUCT_MORE_INFORMATION, $pInfo->products_url); ?></td>
      </tr>
      <?
    }
    ?>
    <tr>
    <td><? echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
    </tr>
    <?
    if ($pInfo->products_date_available > date('Y-m-d')) {
      ?>
      <tr>
      <td align="center" class="smallText"><? echo sprintf(TEXT_PRODUCT_DATE_AVAILABLE, tep_date_long($pInfo->products_date_available)); ?></td>
      </tr>
      <?
    } else {
      ?>
      <tr>
      <td align="center" class="smallText"><? echo sprintf(TEXT_PRODUCT_DATE_ADDED, tep_date_long($pInfo->products_date_added)); ?></td>
      </tr>
      <?
    }
    
    
    ?>
    <tr>
    <td><? echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
    </tr>
    <?
  }

if (isset($HTTP_GET_VARS['read']) && ($HTTP_GET_VARS['read'] == 'only')) {
if (isset($HTTP_GET_VARS['origin'])) {
  $pos_params = strpos($HTTP_GET_VARS['origin'], '?', 0);
if ($pos_params != false) {
  $back_url = substr($HTTP_GET_VARS['origin'], 0, $pos_params);
  $back_url_params = substr($HTTP_GET_VARS['origin'], $pos_params + 1);
} else {
  $back_url = $HTTP_GET_VARS['origin'];
  $back_url_params = '';
}
} else {
  $back_url = FILENAME_CATEGORIES;
  $back_url_params = 'cPath=' . $local_cPath . '&pID=' . $pInfo->products_id;
}
?>
<tr>
<td align="right"><? echo '<a href="' . tep_href_link($back_url, $back_url_params . '&mID=' . $local_mID . '&mNAME=' . $local_mNAME, 'NONSSL') . '">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a>'; ?></td>
</tr>
<?
} else {
?>
<tr>
<td align="right" class="smallText">
<?
/* Re-Post all POST'ed variables */
reset($HTTP_POST_VARS);
while (list($key, $value) = each($HTTP_POST_VARS)) {
if (!is_array($HTTP_POST_VARS[$key])) {
echo tep_draw_hidden_field($key, htmlspecialchars(stripslashes($value)));
}
}
$languages = tep_get_languages();
for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
echo tep_draw_hidden_field('products_name[' . $languages[$i]['id'] . ']', htmlspecialchars(stripslashes($products_name[$languages[$i]['id']])));
echo tep_draw_hidden_field('products_description[' . $languages[$i]['id'] . ']', htmlspecialchars(stripslashes($products_description[$languages[$i]['id']])));
echo tep_draw_hidden_field('products_url[' . $languages[$i]['id'] . ']', htmlspecialchars(stripslashes($products_url[$languages[$i]['id']])));
}
echo tep_draw_hidden_field('products_image', stripslashes($products_image_name));
echo tep_draw_hidden_field('authorized_weights', $authorized_weights_str);

echo tep_image_submit('button_back.gif', IMAGE_BACK, 'name="edit"') . '&nbsp;&nbsp;';
if (isset($HTTP_GET_VARS['pID'])) {
  echo tep_image_submit('button_update.gif', IMAGE_UPDATE);
} else {
  echo tep_image_submit('button_insert.gif', IMAGE_INSERT);
}
echo '&nbsp;&nbsp;<a href="' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $local_cPath . (isset($HTTP_GET_VARS['pID']) ? '&pID=' . $HTTP_GET_VARS['pID'] : '') . '&mID=' . $local_mID . '&mNAME=' . $local_mNAME) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>';
?></td>
</tr>
</table></form>
<?
}
} else {
?>
<table border="0" width="100%" cellspacing="0" cellpadding="2">
<tr>
<td><table border="0" width="100%" cellspacing="0" cellpadding="0">
<tr>
<td class="pageHeading"><?
echo HEADING_TITLE;
if (isset($HTTP_GET_VARS['mNAME'])) {
echo " - ".$HTTP_GET_VARS['mNAME'];
}?>
</td>
<td class="pageHeading" align="right"><? echo tep_draw_separator('pixel_trans.gif', 1, HEADING_IMAGE_HEIGHT); ?></td>
<td align="right"><table border="0" width="100%" cellspacing="0" cellpadding="0">
<tr>
<td class="smallText" align="right">
<?
//echo tep_draw_form($form_action, FILENAME_CATEGORIES,  . (isset($HTTP_GET_VARS['pID']) ? '&pID=' . $HTTP_GET_VARS['pID'] : '') . '&action=' . $form_action . '&mID=' . $local_mID . '&mNAME=' . $local_mNAME, 'post', 'enctype="multipart/form-data"');

echo tep_draw_form('search', FILENAME_CATEGORIES, 'cPath=' . $local_cPath . '&selected_box=catalog', 'post');
echo HEADING_TITLE_SEARCH . ' ' . tep_draw_input_field('search');
echo '</form>';
?>
</td>
</tr>
<tr>
<td class="smallText" align="right">
<?
echo tep_draw_form('goto', FILENAME_CATEGORIES, '&selected_box=catalog', 'post');
echo HEADING_TITLE_GOTO . ' ' . tep_draw_pull_down_menu('cPathCombo', tep_get_category_tree(), $current_category_id, 'onChange="this.form.submit();"');
echo '</form>';
?>
</td>
</tr>
</table></td>
</tr>
</table></td>
</tr>
<tr>
<td><table border="0" width="100%" cellspacing="0" cellpadding="0">
<tr>
<td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
<tr class="dataTableHeadingRow">
<td class="dataTableHeadingContent" colspan="3" nowrap><? echo TABLE_HEADING_CATEGORIES_PRODUCTS; ?></td>
<td class="dataTableHeadingContent" align="center"><? echo TABLE_HEADING_STATUS; ?></td>
<td class="dataTableHeadingContent" align="right"><? echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
</tr>
<?
$categories_count = 0;
$rows = 0;
$sql = "select cd.group_id, c.categories_id, cd.categories_name, c.categories_image, c.parent_id, c.sort_order, c.date_added, c.last_modified from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd ";
if (isset($HTTP_POST_VARS['search'])) {
  $search = tep_db_prepare_input($HTTP_POST_VARS['search']);

  $sql .= "where c.categories_id = cd.categories_id and cd.language_id = '" . (int)$languages_id . "' and cd.categories_name like '%" . tep_db_input($search) . "%' order by c.sort_order, cd.categories_name"; 
} else {
  $sql .= "where c.parent_id = '" . (int)$current_category_id . "' and c.categories_id = cd.categories_id and cd.language_id = '" . (int)$languages_id . "' order by c.sort_order, cd.categories_name";
}
$categories_query = tep_db_query($sql);

if (!isset($HTTP_GET_VARS['mID'])) {
while ($categories = tep_db_fetch_array($categories_query)) {
$categories_count++;
$rows++;

// Get parent_id for subcategories if search
if (isset($HTTP_POST_VARS['search'])) $local_cPath= $categories['parent_id'];

if ((!isset($HTTP_GET_VARS['cID']) && !isset($HTTP_GET_VARS['pID']) || (isset($HTTP_GET_VARS['cID']) && ($HTTP_GET_VARS['cID'] == $categories['categories_id']))) && !isset($cInfo) && (substr($action, 0, 3) != 'new')) {
  $category_childs = array('childs_count' => tep_childs_in_category_count($categories['categories_id']));
  $category_products = array('products_count' => tep_products_in_category_count($categories['categories_id']));
  
  $cInfo_array = array_merge($categories, $category_childs, $category_products);
  $cInfo = new objectInfo($cInfo_array);

}

if (isset($cInfo) && is_object($cInfo) && ($categories['categories_id'] == $cInfo->categories_id) ) {
  echo '              <tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_CATEGORIES, tep_get_path($categories['categories_id'])) . '\'">' . "\n";
} else {
  echo '              <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $local_cPath . '&cID=' . $categories['categories_id']) . '\'">' . "\n";
}

if (tep_not_null($categories['categories_image'])) {
  $tb = tep_image(DIR_WS_CATALOG_IMAGES . $categories['categories_image'], '', 16, 16, '', 16);
} else {
  $tb = tep_image(DIR_WS_ICONS . 'preview.gif', ICON_PREVIEW);
}
                                               

?>
<td class="dataTableContent" width="18px" align="center"><? echo '<a href="' . tep_href_link(FILENAME_CATEGORIES, tep_get_path($categories['categories_id'])) . '">' . tep_image(DIR_WS_ICONS . 'folder.gif', ICON_FOLDER) . '</a>'; ?></td>
<td class="dataTableContent" width="18px" align="center"><? echo '<a href="' . tep_href_link(FILENAME_CATEGORIES, tep_get_path($categories['categories_id'])) . '">' . $tb  . '</a>'; ?></td>
<td class="dataTableContent" width="100%"><? echo '<b>' . $categories['categories_name'] . '</b>';?></td>
<td class="dataTableContent">&nbsp;</td>
<td class="dataTableContent" align="right"><? if (isset($cInfo) && is_object($cInfo) && ($categories['categories_id'] == $cInfo->categories_id) ) { echo tep_image(DIR_WS_IMAGES . 'icon_arrow_right.gif', ''); } else { echo '<a href="' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $local_cPath . '&cID=' . $categories['categories_id']) . '">' . tep_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp;</td>
</tr>
<?
}
}

$local_mID = "";
if (isset($HTTP_GET_VARS['mID'])) {
  $local_mID = (int)$HTTP_GET_VARS['mID'];
}

$products_count = 0;
$products_query_sql = "select pd.products_hidden_info, p.products_image, p.group_id, pd.products_description, p.products_id, pd.products_name, p.products_min_manufacturer_quantity, p.is_bulk, p.measure_unit, p.shipping_day, p.shipping_frequency, p.authorized_weights, p.products_reference, p.products_limitation, p.products_quantity, p.products_image, p.products_price, p.products_date_added, p.products_last_modified, p.products_date_available, p.products_status";

if (isset($HTTP_POST_VARS['search'])) {
  $products_query_sql .= ", p2c.categories_id from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c where p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "' and p.products_id = p2c.products_id and pd.products_name like '%" . tep_db_input($search) . "%'";
} else {
  $products_query_sql .= " from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c where p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "' and p.products_id = p2c.products_id";
}
if ($local_mID) {
  $products_query_sql .= " and manufacturers_id = '" . $local_mID . "'";
}
else {
  // condition if rajoutée le 2010-05-16 pour pouvoir rechercher tous les produits, depuis n'importe où
  if (!isset($HTTP_POST_VARS['search'])&&($HTTP_POST_VARS['search']=="")) {
    $products_query_sql .= " and p2c.categories_id = '" . (int)$current_category_id . "'";
  }
}
$products_query_sql .= " order by pd.products_name";
//echo $products_query_sql;
$products_query = tep_db_query($products_query_sql);
while ($products = tep_db_fetch_array($products_query)) {
$products_count++;
$rows++;

// Get categories_id for product if search
if (isset($HTTP_POST_VARS['search'])) $local_cPath = $products['categories_id'];

if ( (!isset($HTTP_GET_VARS['pID']) && !isset($HTTP_GET_VARS['cID']) || (isset($HTTP_GET_VARS['pID']) && ($HTTP_GET_VARS['pID'] == $products['products_id']))) && !isset($pInfo) && !isset($cInfo) && (substr($action, 0, 3) != 'new')) {
  // find out the rating average from customer reviews
  $reviews_query = tep_db_query("select (avg(reviews_rating) / 5 * 100) as average_rating from " . TABLE_REVIEWS . " where products_id = '" . (int)$products['products_id'] . "'");
  $reviews = tep_db_fetch_array($reviews_query);
  $pInfo_array = array_merge($products, $reviews);
  $pInfo = new objectInfo($pInfo_array);
}


if (isset($pInfo) && is_object($pInfo) && ($products['products_id'] == $pInfo->products_id) ) {
  getAuthorizedArray();

  echo '              <tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $local_cPath . '&pID=' . $products['products_id'] . '&action=new_product_preview&read=only') . '\'">' . "\n";
} else {
  echo '              <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $local_cPath . '&pID=' . $products['products_id']) . '&mID=' .$local_mID. '&mNAME=' . $HTTP_GET_VARS['mNAME'] . '\'">' . "\n";
}

if (tep_not_null($products['products_image'])) {
  $tb = tep_image(DIR_WS_CATALOG_IMAGES . $products['products_image'], '', 16, 16, '', 16);
} else {
  $tb = tep_image(DIR_WS_ICONS . 'preview.gif', ICON_PREVIEW);
}

?>
<td class="dataTableContent" width="18px" align="center" colspan="2"><? echo '<a href="' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $local_cPath . '&pID=' . $products['products_id'] . '&action=new_product') . '">' . $tb . '</a>'; ?></td>
<td class="dataTableContent" width="100%"><? echo $products['products_name'];?></td>
<td class="dataTableContent" align="center">
<?
if ($products['products_status'] == '1') {
echo tep_image(DIR_WS_IMAGES . 'icon_status_green.gif', IMAGE_ICON_STATUS_GREEN, 10, 10) . '&nbsp;&nbsp;<a href="' . tep_href_link(FILENAME_CATEGORIES, 'action=setflag&flag=0&pID=' . $products['products_id'] . '&cPath=' . $local_cPath) . '">' . tep_image(DIR_WS_IMAGES . 'icon_status_red_light.gif', IMAGE_ICON_STATUS_RED_LIGHT, 10, 10) . '</a>';
} else {
echo '<a href="' . tep_href_link(FILENAME_CATEGORIES, 'action=setflag&flag=1&pID=' . $products['products_id'] . '&cPath=' . $local_cPath) . '">' . tep_image(DIR_WS_IMAGES . 'icon_status_green_light.gif', IMAGE_ICON_STATUS_GREEN_LIGHT, 10, 10) . '</a>&nbsp;&nbsp;' . tep_image(DIR_WS_IMAGES . 'icon_status_red.gif', IMAGE_ICON_STATUS_RED, 10, 10);
}
?></td>
<td class="dataTableContent" align="right"><? if (isset($pInfo) && is_object($pInfo) && ($products['products_id'] == $pInfo->products_id)) { echo tep_image(DIR_WS_IMAGES . 'icon_arrow_right.gif', ''); } else { echo '<a href="' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $local_cPath . '&pID=' . $products['products_id']) . '">' . tep_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp;</td>
</tr>
<?
}

$local_cPath_back = '';
if (sizeof($local_cPath_array) > 0) {
for ($i=0, $n=sizeof($local_cPath_array)-1; $i<$n; $i++) {
if (empty($local_cPath_back)) {
$local_cPath_back .= $local_cPath_array[$i];
} else {
$local_cPath_back .= '_' . $local_cPath_array[$i];
}
}
}

$local_cPath_back = (tep_not_null($local_cPath_back)) ? 'cPath=' . $local_cPath_back . '&' : '';
?>
<tr>
<td colspan="5"><table border="0" width="100%" cellspacing="0" cellpadding="2">
<tr>
<? if (!$local_mID) {
$text = TEXT_CATEGORIES . '&nbsp;' . $categories_count . '<br>';
} else {
$text = "";
}?>
<td class="smallText"><? echo $text . TEXT_PRODUCTS . '&nbsp;' . $products_count; ?></td>
<? if (!$local_mID) {?>
<td align="right" class="smallText"><? if (sizeof($local_cPath_array) > 0) echo '<a href="' . tep_href_link(FILENAME_CATEGORIES, $local_cPath_back . 'cID=' . $current_category_id) . '">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a>&nbsp;'; if (!isset($HTTP_POST_VARS['search'])) echo '<a href="' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $local_cPath . '&action=new_category') . '">' . tep_image_button('button_new_category.gif', IMAGE_NEW_CATEGORY) . '</a>&nbsp;<a href="' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $local_cPath . '&action=new_product') . '">' . tep_image_button('button_new_product.gif', IMAGE_NEW_PRODUCT) . '</a>'; ?>&nbsp;</td><?
}?>
</tr>
</table></td>
</tr>
</table></td>
<?

            
$heading = array();
$contents = array();
switch ($action) {
case 'new_category':
$heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_NEW_CATEGORY . '</b>');

$contents = array('form' => tep_draw_form('newcategory', FILENAME_CATEGORIES, 'action=insert_category&cPath=' . $local_cPath, 'post', 'enctype="multipart/form-data"'));
$contents[] = array('text' => TEXT_NEW_CATEGORY_INTRO);

$category_inputs_string = '';
$languages = tep_get_languages();
for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
$category_inputs_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_name[' . $languages[$i]['id'] . ']');
}

$contents[] = array('text' => '<br>' . TEXT_CATEGORIES_NAME . $category_inputs_string);
$contents[] = array('text' => '<br>' . TEXT_CATEGORIES_IMAGE . '<br>' . tep_draw_file_field('categories_image'));
$contents[] = array('text' => "<br><b>Type:</b> " . tep_draw_pull_down_menu('group_id', $groups_array, $cInfo->group_id, 'width="50px"'));
$contents[] = array('text' => '<br>' . TEXT_SORT_ORDER . '<br>' . tep_draw_input_field('sort_order', '', 'size="2"'));
$contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_save.gif', IMAGE_SAVE) . ' <a href="' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $local_cPath) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
break;
case 'edit_category':
$heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_EDIT_CATEGORY . '</b>');

$contents = array('form' => tep_draw_form('categories', FILENAME_CATEGORIES, 'action=update_category&cPath=' . $local_cPath, 'post', 'enctype="multipart/form-data"') . tep_draw_hidden_field('categories_id', $cInfo->categories_id));
$contents[] = array('text' => TEXT_EDIT_INTRO);

$category_inputs_string = '';
$languages = tep_get_languages();
for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
$category_inputs_string .= '<br>' . tep_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . tep_draw_input_field('categories_name[' . $languages[$i]['id'] . ']', tep_get_category_name($cInfo->categories_id, $languages[$i]['id']));
}

$contents[] = array('text' => '<br>' . TEXT_EDIT_CATEGORIES_NAME . $category_inputs_string);
$contents[] = array('text' => '<br>' . tep_image(DIR_WS_CATALOG_IMAGES . $cInfo->categories_image, $cInfo->categories_name, '', '', '', 100) . '<br>'.$cInfo->categories_image);
$contents[] = array('text' => '<br>' . TEXT_EDIT_CATEGORIES_IMAGE . '<br>' . tep_draw_file_field('categories_image') . tep_draw_hidden_field('categories_previous_image', $cInfo->categories_image));
$contents[] = array('text' => '<br>' . TEXT_EDIT_SORT_ORDER . '<br>' . tep_draw_input_field('sort_order', $cInfo->sort_order, 'size="2"'));
$contents[] = array('text' => "<br><b>Type:</b> " . tep_draw_pull_down_menu('group_id', $groups_array, $cInfo->group_id, 'width="50px"'));
$contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_save.gif', IMAGE_SAVE) . ' <a href="' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $local_cPath . '&cID=' . $cInfo->categories_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
break;
case 'delete_category':
$heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_DELETE_CATEGORY . '</b>');

$contents = array('form' => tep_draw_form('categories', FILENAME_CATEGORIES, 'action=delete_category_confirm&cPath=' . $local_cPath) . tep_draw_hidden_field('categories_id', $cInfo->categories_id));
$contents[] = array('text' => TEXT_DELETE_CATEGORY_INTRO);
$contents[] = array('text' => '<br><b>' . $cInfo->categories_name . '</b>');
if ($cInfo->childs_count > 0) $contents[] = array('text' => '<br>' . sprintf(TEXT_DELETE_WARNING_CHILDS, $cInfo->childs_count));
if ($cInfo->products_count > 0) $contents[] = array('text' => '<br>' . sprintf(TEXT_DELETE_WARNING_PRODUCTS, $cInfo->products_count));
$contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_delete.gif', IMAGE_DELETE) . ' <a href="' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $local_cPath . '&cID=' . $cInfo->categories_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
break;
case 'move_category':
$heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_MOVE_CATEGORY . '</b>');

$contents = array('form' => tep_draw_form('categories', FILENAME_CATEGORIES, 'action=move_category_confirm&cPath=' . $local_cPath) . tep_draw_hidden_field('categories_id', $cInfo->categories_id));
$contents[] = array('text' => sprintf(TEXT_MOVE_CATEGORIES_INTRO, $cInfo->categories_name));
$contents[] = array('text' => '<br>' . sprintf(TEXT_MOVE, $cInfo->categories_name) . '<br>' . tep_draw_pull_down_menu('move_to_category_id', tep_get_category_tree(), $current_category_id));
$contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_move.gif', IMAGE_MOVE) . ' <a href="' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $local_cPath . '&cID=' . $cInfo->categories_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
break;
case 'delete_product':
$heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_DELETE_PRODUCT . '</b>');

$contents = array('form' => tep_draw_form('products', FILENAME_CATEGORIES, 'action=delete_product_confirm&cPath=' . $local_cPath) . tep_draw_hidden_field('products_id', $pInfo->products_id));
$contents[] = array('text' => TEXT_DELETE_PRODUCT_INTRO);
$contents[] = array('text' => '<br><b>' . $pInfo->products_name . '</b>');

$product_categories_string = '';
$product_categories = tep_generate_category_path($pInfo->products_id, 'product');

for ($i = 0, $n = sizeof($product_categories); $i < $n; $i++) {
$category_path = '';
for ($j = 0, $k = sizeof($product_categories[$i]); $j < $k; $j++) {
$category_path .= $product_categories[$i][$j]['text'] . '&nbsp;&gt;&nbsp;';
}
$category_path = substr($category_path, 0, -16);

$product_categories_string .= tep_draw_checkbox_field('product_categories[]', $product_categories[$i][sizeof($product_categories[$i])-1]['id'], true) . '&nbsp;' . $category_path . '<br>';


}
$product_categories_string = substr($product_categories_string, 0, -4);

$contents[] = array('text' => $product_categories_string . '<br>');


$sql = "select count(*) as rec_count from orders_products as op left join orders as o on op.orders_id=o.orders_id where o.orders_status=4 and op.products_id='" . (int)$pInfo->products_id . "';";
//				echo $sql;
$recurrences_query = tep_db_query($sql);
$recurrences = tep_db_fetch_array($recurrences_query);
$rec_count = 0;
if (($recurrences) && (($rec_count = (int)$recurrences['rec_count']) > 0)) {
$contents[] = array('text' => '<br><blink><b>ATTENTION :</b></blink> ce produit est utilisé dans ' . $rec_count . ' récurrence(s). Si vous le supprimer, les adhérents ayant une commande récurrente recevront un email pour les en informer.<br>');
}

$contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_delete.gif', IMAGE_DELETE) . ' <a href="' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $local_cPath . '&pID=' . $pInfo->products_id . '&mID=' . $local_mID . '&mNAME=' . $local_mNAME) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
break;
case 'move_product':
$heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_MOVE_PRODUCT . '</b>');

$contents = array('form' => tep_draw_form('products', FILENAME_CATEGORIES, 'action=move_product_confirm&cPath=' . $local_cPath) . tep_draw_hidden_field('products_id', $pInfo->products_id));
$contents[] = array('text' => sprintf(TEXT_MOVE_PRODUCTS_INTRO, $pInfo->products_name));
$contents[] = array('text' => '<br>' . TEXT_INFO_CURRENT_CATEGORIES . '<br><b>' . tep_output_generated_category_path($pInfo->products_id, 'product') . '</b>');
$contents[] = array('text' => '<br>' . sprintf(TEXT_MOVE, $pInfo->products_name) . '<br>' . tep_draw_pull_down_menu('move_to_category_id', tep_get_category_tree(), $current_category_id));
$contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_move.gif', IMAGE_MOVE) . ' <a href="' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $local_cPath . '&pID=' . $pInfo->products_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
break;
case 'copy_to':
$heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_COPY_TO . '</b>');

$contents = array('form' => tep_draw_form('copy_to', FILENAME_CATEGORIES, 'action=copy_to_confirm&cPath=' . $local_cPath) . tep_draw_hidden_field('products_id', $pInfo->products_id));
$contents[] = array('text' => TEXT_INFO_COPY_TO_INTRO);
$contents[] = array('text' => '<br>' . TEXT_INFO_CURRENT_CATEGORIES . '<br><b>' . tep_output_generated_category_path($pInfo->products_id, 'product') . '</b>');
$contents[] = array('text' => '<br>' . TEXT_CATEGORIES . '<br>' . tep_draw_pull_down_menu('categories_id', tep_get_category_tree(), $current_category_id));
$contents[] = array('text' => '<br>' . TEXT_HOW_TO_COPY . '<br>' . tep_draw_radio_field('copy_as', 'link', true) . ' ' . TEXT_COPY_AS_LINK . '<br>' . tep_draw_radio_field('copy_as', 'duplicate') . ' ' . TEXT_COPY_AS_DUPLICATE);
$contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_copy.gif', IMAGE_COPY) . ' <a href="' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $local_cPath . '&pID=' . $pInfo->products_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
break;
default:
if ($rows > 0) {

if (isset($cInfo) && is_object($cInfo)) { // category info box contents

  $heading[] = array('text' => '<b>' . $cInfo->categories_name . '</b>');
  
  $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $local_cPath . '&cID=' . $cInfo->categories_id . '&action=edit_category') . '">' . tep_image_button('button_edit.gif', IMAGE_EDIT) . '</a> <a href="' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $local_cPath . '&cID=' . $cInfo->categories_id . '&action=delete_category') . '">' . tep_image_button('button_delete.gif', IMAGE_DELETE) . '</a> <a href="' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $local_cPath . '&cID=' . $cInfo->categories_id . '&action=move_category') . '">' . tep_image_button('button_move.gif', IMAGE_MOVE) . '</a>');
  $contents[] = array('text' => '<br><b>' . TEXT_DATE_ADDED . '</b> ' . tep_date_short($cInfo->date_added));
  if (tep_not_null($cInfo->last_modified)) $contents[] = array('text' => '<b>'.TEXT_LAST_MODIFIED . '</b> ' . tep_date_short($cInfo->last_modified));

  $contents[] = array('text' => '<br><center>' . tep_image(DIR_WS_CATALOG_IMAGES . $cInfo->categories_image, $cInfo->categories_name, HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT, '', CATEGORY_ADMIN_MAX_IMAGE_SIZE) . '</center>' . $cInfo->categories_image);

  $contents[] = array('text' => "<br><b>Type:</b> " . tep_draw_pull_down_menu('group_id', $groups_array, $cInfo->group_id, 'width="50px" disabled'));

  $contents[] = array('text' => '<br><b>' . TEXT_SUBCATEGORIES . '</b> ' . $cInfo->childs_count . '<br><b>' . TEXT_PRODUCTS . '</b> ' . $cInfo->products_count);

} elseif (isset($pInfo) && is_object($pInfo)) { // product info box contents
  $heading[] = array('text' => '<b>' . tep_get_products_name($pInfo->products_id, $languages_id) . '</b>');
  
  $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $local_cPath . '&pID=' . $pInfo->products_id . '&action=new_product&mID=' . $local_mID . '&mNAME=' . $local_mNAME) . '">' . tep_image_button('button_edit.gif', IMAGE_EDIT) . '</a> <a href="' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $local_cPath . '&pID=' . $pInfo->products_id . '&action=delete_product&mID=' . $local_mID . '&mNAME=' . $local_mNAME) . '">' . tep_image_button('button_delete.gif', IMAGE_DELETE) . '</a>');
  if (!$local_mID) {
    $contents[] = array('text' => ' <a href="' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $local_cPath . '&pID=' . $pInfo->products_id . '&action=move_product') . '">' . tep_image_button('button_move.gif', IMAGE_MOVE) . '</a> <a href="' . tep_href_link(FILENAME_CATEGORIES, 'cPath=' . $local_cPath . '&pID=' . $pInfo->products_id . '&action=copy_to') . '">' . tep_image_button('button_copy_to.gif', IMAGE_COPY_TO) . '</a>');
  }
  if ($pInfo->products_reference!=""){
    $contents[] = array('text' => '<br><b>Référence produit :</b> ' . $pInfo->products_reference);
  }
  $contents[] = array('text' => '<br><b>' . TEXT_DATE_ADDED . '</b> ' . getFormattedLongDate($pInfo->products_date_added));
  if (tep_not_null($pInfo->products_last_modified)) $contents[] = array('text' => '<b>Dernière modif :</b> ' . getFormattedLongDate($pInfo->products_last_modified));
  if (date('Y-m-d') < $pInfo->products_date_available) $contents[] = array('text' => '<b>'.TEXT_DATE_AVAILABLE . '</b> ' . getFormattedLongDate($pInfo->products_date_available));
  $contents[] = array('text' => '<br><center>' . tep_image(DIR_WS_CATALOG_IMAGES . $pInfo->products_image, $pInfo->products_name, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT, '', CATEGORY_ADMIN_MAX_IMAGE_SIZE) . '</center>' . $pInfo->products_image);

  if (($pInfo->is_bulk)||($pInfo->measure_unit!="")) { 
    $txt = "Prix au ".$pInfo->measure_unit.":";
    $txt1 = " ".$pInfo->measure_unit;
    $txt1 .= '<br><b>Quantités disponibles :</b> ' . tep_draw_pull_down_menu('authorized_weights', $weights_array, 1, '');
  } else {
    $txt = TEXT_PRODUCTS_PRICE_INFO;
    $txt1 = "";
  }
  
  $contents[] = array('text' => '<br><b>' . $txt . '</b> ' . $currencies->format($pInfo->products_price) . '<br><b>Stock restant :</b> ' . tep_format_qty_for_html($pInfo->products_quantity) . '<br><b>Conditionnement :</b> par ' . tep_format_qty_for_html($pInfo->products_min_manufacturer_quantity).$txt1);

  if (tep_not_null($pInfo->products_description)) {
    $contents[] = array('text' => '<br><b>Description :</b><br>'.$pInfo->products_description);
  }

  $contents[] = array('text' => 
    '<br><b>Jour de livraison :</b><br>&nbsp;&nbsp;&nbsp;' . convertEnglishDateNames_fr($pInfo->shipping_day) . 
    '<br><b>Fréquence de livraison :</b><br>&nbsp;&nbsp;&nbsp;' . convertShippingFrequencyToText_fr($pInfo->shipping_frequency));
  
  //$contents[] = array('text' => '<br>' . TEXT_PRODUCTS_AVERAGE_RATING . ' ' . number_format($pInfo->average_rating, 2) . '%');

  $contents[] = array('text' => "<br><b>Type:</b> " . tep_draw_pull_down_menu('group_id', $groups_array, $pInfo->group_id, 'width="50px" disabled'));

  if (tep_not_null($pInfo->products_hidden_info)) {
    $contents[] = array('text' => '<br><b>Info produit (cachée) :</b><br>' . $pInfo->products_hidden_info);
  }

}
} else { // create category/product info
$heading[] = array('text' => '<b>' . EMPTY_CATEGORY . '</b>');

$contents[] = array('text' => TEXT_NO_CHILD_CATEGORIES_OR_PRODUCTS);
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
</table>
<?
}
?>
</td>
<!-- body_text_eof //-->
</tr>
</table>

<script>
updateBulk();
</script>
<!-- body_eof //-->

<!-- footer //-->
<? require($admin_FS_path . DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<br>
</body>
</html>
<? require($admin_FS_path . DIR_WS_INCLUDES . 'application_bottom.php'); ?>