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
      
      function showhideMod(op_id) {
        // à faire
        showhide('div_date'+op_id);
        showhide('div_sel_dates_mod'+op_id);
      }
      
      function changeClient() {
        // nothing
      }
      function changeProduct(combo, o_id) {
        <? 
          if ($adminMode != "yes") {?>
            var div = document.getElementById("shippingDiv");
            var p_id = combo.value;//.split('|')[0];
            
            document.location = "<? 
              echo tep_href_link(FILENAME_ACCOUNT_HISTORY_INFO, '', 'SSL').'&order_id='.$oID;
            ?>" +'&product_id='+p_id;
          <?
          }
        ?>

      }
      <?=putShippingJS("order_quantity");?>
     </script>

<?

  function getQtyStriked($qty, $txt) {
    if (tep_format_qty_for_db($qty) <= 0) { return "<strike>".$txt."</strike>"; } else { return $txt; } 
  }

  function canModQty($p_id, $date_shipped, $group_id = 0) {
    global $recurrent_order, $adminMode;

    $can_mod = false;

    if ($adminMode != "yes") {
      if (($recurrent_order)&&($group_id<=0)) {
        $can_mod = true;
      } else {
        $dt_now = strtotime(date("Y-m-d H:i:s"));
        $dt_ds = strtotime($date_shipped);
        if ($dt_now < $dt_ds) {
          // la livraison a bien lieu à une date ultérieure à la date d'aujourd'hui
          $can_mod = (orders_are_frozen_global($group_id, $date_shipped, $p_id) == "");
        }
      }
    } else {
      $can_mod = true;
    }
    return $can_mod;
  }

  function getQtyBlock($op_id, $opm_id, $qty, $price, $p_id, $date_shipped, $is_bulk = false, $group_id = 0, $mu = '') {
    global $recurrent_order, $adminMode, $can_mod_at_least_one;
    
    $can_mod = canModQty($p_id, $date_shipped, $group_id);
    
    $unit = "";
    if (($is_bulk)&&($mu!="")) {
      $unit = " ".$mu.($qty>1 ? "s" : "");
    }
    
    if ($can_mod) {
      $can_mod_at_least_one = true;
      
      if (($is_bulk<=0)||($adminMode)) {
        $qty_modif = "<input type='text' id='product_quantity$op_id' name='product_qty[]' value='".tep_format_qty_for_html($qty)."' size='2' align='right' onkeydown='removeComma(this);' onchange='document.getElementById(\"modified$op_id\").value=\"yes\";'>".$unit;
      } else {
        $qty_modif = getBulk($p_id, tep_format_qty_for_db($qty), "product_qty[]", false, "onchange='document.getElementById(\"modified$op_id\").value=\"yes\";'", true);
      }
      
      return $qty_modif." 
        <input type='hidden' id='p_id$op_id' name='p_id[]' value='$p_id'>
        <input type='hidden' id='ds$op_id' name='ds[]' value='$date_shipped'>
        <input type='hidden' id='modified$op_id' name='modified[]' value='no'>
        <input type='hidden' id='op_id$op_id' name='op_id[]' value='$op_id'>
        <input type='hidden' id='opm_id$op_id' name='opm_id[]' value='$opm_id'>
        <input type='hidden' id='product_qty_save$op_id' name='product_qty_save[]' value='".tep_format_qty_for_html($qty)."'>
        <input type='hidden' id='product_price$op_id' name='product_price[]' value='$price'>
        <input type='hidden' id='attr$op_id' name='attr[]' value='op_id$op_id'>";
    } else {
      return getQtyStriked(tep_format_qty_for_db($qty), tep_format_qty_for_html($qty).$unit);
    }
  }
  
  // =========================== DEBUT DE $table ==========================
  
  $table = "";
  
  if ($adminMode != "yes") {
    $table .= tep_draw_form('order_quantity', tep_href_link(FILENAME_ACCOUNT_HISTORY_INFO, 'order_id='.$oID), 'post');
    $form_add_product = tep_draw_form('order_addproduct', $page, 'post'); 
  } else {
    $table .= tep_draw_form('order_quantity', FILENAME_ORDERS, tep_get_all_get_params());
    $form_add_product = tep_draw_form('order_addproduct', FILENAME_ORDERS, tep_get_all_get_params()); 
  }
  
  $table .= '<input type="hidden" name="action" value="update_qty">
      <table border="0" width="100%" cellspacing="0" cellpadding="2">';
    
  $table_btn_recal = tep_image_submit('button_update_cart.gif', 'Mise à jour quantités');
  $table_btn_add = '</form>'.$form_add_product.'&nbsp;<a href="javascript:showhide(\'addProductDiv\');">'. tep_image_button('button_in_cart.gif') . '</a>';
  
  $change_of_price = false;
  $total_price = 0.0;
  $table_products = "";
  $p_id_list = "";
  if (sizeof($order->products)<=0) {
    // c'est une commande où on a supprimé des produits
    $sql = "SELECT products_id, date_shipped, group_id FROM orders_products 
        WHERE orders_id = -$oID;";
    $query = tep_db_query($sql, 'db_link');
    while ($record = tep_db_fetch_array($query)) {
      if (canModQty($record['products_id'], $record['date_shipped'], $record['group_id'])) {
        $can_mod_at_least_one = true;
      }
    }
    
    $table .= '<tr><td class="messageStackWarning" colspan="7">Vous n\'avez plus aucun produit dans votre commande.</td><tr>';
    $table .= '<tr><td class="smallTextNoBorder" colspan="7"><br><hr></td><tr>';
    $table .= '<tr><td class="smallTextNoBorder" colspan="2">&nbsp;</td><td class="smallTextNoBorder" witdh="100%" colspan="3">';
    if ($can_mod_at_least_one) {
      $table .= $table_btn_add;
    } else {
      $table .= '&nbsp;';
    }
    $table .= '</td></tr>';
  } else {
    $table .= '<!DEL_CB!>'; // le checkbox de suppression
    
/*
    ($recurrent_order) ? 
      $delCB = '<tr><td class="smallTextNoBorder" colspan="1" align="center"><b>Supprimer<br>le produit</b></td><td class="smallTextNoBorder" colspan="6"></td></tr>' : 
      $delCB = ''; 
*/ // on désactive la suppression des produits, je le sens pas bien (opm record without op record...)


    function ahiShippingInfo($txt, $p_array, $ds_sel = "" /* date à sélectionner */) {
      global $recurrent_order;

      $sql = "SELECT shipping_day, shipping_frequency FROM products WHERE products_id = ".$p_array['id'].";";
      $sql_query = tep_db_query($sql);
      if ($sql_result = tep_db_fetch_array($sql_query)) {
        $p_sd = $sql_result['shipping_day'];
        $p_sf = $sql_result['shipping_frequency'];
        $attr_id = "op_id".$p_array['op_id']; // trick for success : replacing attr by op_id to get single name in form !
        $table =  
            "<table><tr>
              <td nowrap class='smallTextNoBorder'>$txt</td>";
        if ($p_array['group_id'] == 0) { // for drop_list
          $od_start_for_drop_list = get_order_date_arg(date("Y-m-d"), "", $p_array['id'], $p_sd, $p_sf);
        } else {
          $od_start_for_drop_list = getGA_order_date(DEFAULT_GA_ID);
        }
        if ($ds_sel == "") $ds_sel = $od_start_for_drop_list;
          
        if ($p_array['group_id'] == 0) {
          $nb_max_weeks = 3 * (2 + $p_sf);
          $table .= "  
              <td nowrap class='smallTextNoBorder'><a href='javascript:showhideMod(".$p_array['op_id'].");'>".tep_image(DIR_WS_ICONS . 'b_edit.png', 'Editer', '16', '16')."</a>
              </td><td nowrap class='smallTextNoBorder'><div id='div_date".$p_array['op_id']."' style='display:block;'><b>".getFormattedLongDate($ds_sel, true)."</b></div>
              </td><td nowrap class='smallTextNoBorder'><div id='div_sel_dates_mod".$p_array['op_id']."' style='display:none;'>".
                putShipping(
                  tep_get_prid($p_array['id'])."§".$attr_id."§".$next_txt,
                  $p_sd, $p_sf, $od_start_for_drop_list, "", $recurrent_order, false, $p_array['shipping_frequency'], 
                  $ds_sel, $nb_max_weeks, $p_array['op_id'])."
                 </div>
              </td>";
        } else {
          $table .= "  
              <td nowrap class='smallTextNoBorder'><b>".getFormattedLongDate($ds_sel, true)."</b></td>";
        }
        $table .=   
            "</tr></table>";
        return $table;
      }
    }
      
    for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {    
      // ajout de la notion de la date de livraison                                          
  
      $p_id = $order->products[$i]['id'];
      $p_id_list .= $p_id.",";
      $p_attributes = ""; 
      $op_id = $order->products[$i]['op_id']; 
      $op_qty = tep_format_qty_for_db($order->products[$i]['qty']);
      $op_price = $order->products[$i]['final_price']; 
      $op_shipping_day = $order->products[$i]['shipping_day']; 
      $op_shipping_frequency = $order->products[$i]['shipping_frequency']; 
      $op_next_date_shipped = $order->products[$i]['next_date_shipped']; 
      $next_date_shipped_formatted = getFormattedLongDate($op_next_date_shipped, true);
      $date_shipped = $order->products[$i]['date_shipped'];
      $group_id = $order->products[$i]['group_id'];
      $ds_formatted = getFormattedLongDate($date_shipped, true);
      $today = date('Y-m-d');
      $today_date = date("Y-m-d H:i:s");
      $prev_od = get_order_date_arg($today, "", $p_id, $op_shipping_day, $op_shipping_frequency, "-");
      $prev_od_formatted = getFormattedLongDate($prev_od, true); 
      $next_od = get_order_date_arg($today, "", $p_id, $op_shipping_day, $op_shipping_frequency, "+");
      $next_od_formatted = getFormattedLongDate($next_od, true); 
      
      if ($recurrent_order) {
        $t = "à partir du";
      } else {
        $t = "le";
      } 
  
      if ( (isset($order->products[$i]['attributes'])) && (sizeof($order->products[$i]['attributes']) > 0) ) {
        for ($j=0, $n2=sizeof($order->products[$i]['attributes']); $j<$n2; $j++) {
          if ((trim($order->products[$i]['attributes'][$j]['option'])!="")&&(trim($order->products[$i]['attributes'][$j]['value'])!="")) {
            $p_attributes .= '<small><i> - ' . $order->products[$i]['attributes'][$j]['option'] . ': ' . $order->products[$i]['attributes'][$j]['value'] . '</i></small>';
          }
        }
      }
         
      $shipping_msg = "";
      // on récupère l'opm correspondant
      $sql = "SELECT date_shipped, orders_products_modifications_id, products_quantity, products_name, 
              products_options, products_options_values, final_price FROM orders_products_modifications 
          WHERE orders_products_id = $op_id AND facturate= 'Y' AND ((is_recurrence_order > 0 AND date_shipped <= '$today') OR (is_recurrence_order <= 0)) ORDER BY date_shipped DESC, orders_products_modifications_datetime DESC;";
//      echo "<br>".$sql;
      $query = tep_db_query($sql, 'db_link');
      $record = tep_db_fetch_array($query);
      $opm_exist = false;
      if ($record['orders_products_modifications_id']>0) {
        // on a trouvé des opm (commande ponctuelle ou récurrence) => le produit a été validé au moins une fois
        $qty_db = tep_format_qty_for_db($record['products_quantity']);
        $opm_exist = true;
  
        $ds_max = date("Y-m-d 20:00:00", strtotime($record['date_shipped']));
        $max_date_shipped_formatted = getFormattedLongDate($ds_max, true);

        if (!$recurrent_order) {
          // cas simple : commande non récurrente
          if (orders_are_frozen_global($group_id, $date_shipped, $p_id) != "") {
            // les commandes sont figées (validées) par le producteur => c'est vert
            $shipping_msg .= "<tr><td class='messageStackSuccess'>produit pris en compte par le producteur, ";
            if (strtotime($today_date) > strtotime($ds_max)) {
              $shipping_msg .= "a été ";
            } else {
              $shipping_msg .= "sera ";
            }
            $shipping_msg .= "livré le <b>$max_date_shipped_formatted</b></td></tr>";
            
          } else {
            if (strtotime($today_date) < strtotime($ds_max)) { 
              // c'est orange : on attend la validation
              $shipping_msg .= "<td class='messageStackWarning'>".ahiShippingInfo("livraison prévue le ", $order->products[$i], $op_next_date_shipped)."</td></tr>";
            } else {
              // c'est rouge : la date est dépassée !
              $shipping_msg .= "<td class='messageStackError'>le produit n'a pas été livré le <b>$max_date_shipped_formatted</b></td></tr>";
            }
          }
        } else {
          // commande récurrente
          if (strtotime($ds_max) < strtotime($prev_od)) {
            // le produit n'a pas été livré à la dernière livraison => c'est rouge
            $shipping_msg .= "<tr><td class='messageStackError'>le produit n'a pas été livré le <b>$prev_od_formatted</b></td></tr>";
          } else if ((strtotime($ds_max) >= strtotime($prev_od))&&(strtotime($ds_max) < strtotime($next_od))) {
            // le produit a été livré à la dernière livraison
            $shipping_msg .= "<tr><td class='messageStackSuccess'>produit livré le <b>$max_date_shipped_formatted</b></td></tr>";
          } else if (strtotime($ds_max) == strtotime($next_od)) {
            // le produit va été livré à la prochaine livraison ($next_od > $today_date)
            $shipping_msg .= "<tr><td class='messageStackSuccess'>produit pris en compte par le producteur, sera livré le <b>$next_od_formatted</b></td></tr>";
          } else {  // (strtotime($ds_max) > strtotime($next_od))
            // le produit a été validé à une date ultérieure de nds => impossible
            $shipping_msg .= "<tr>".tep_error_message(true, "Ce produit n'a pas été enregistré correctement : la date de livraison validée par le producteur est incohérente.")."</tr>";
          }

          if (strtotime($ds_max) < strtotime($next_od)) {
            if (orders_are_frozen_global($group_id, $next_od, $p_id) != "") {
              // les commandes sont figées => la prochaine date de livraison est $next_od_formatted
              $shipping_msg .= "<tr><td class='messageStackSuccess'> ... prochaine livraison : le <b>$next_od_formatted</b> (déjà prise en compte par le producteur)";   /*$max_date_shipped_formatted*/
            } else {
              $ds_sel = $op_next_date_shipped;
              if (strtotime($today_date) > strtotime($ds_sel)) {
                // la date paramétrée dans op n'est plus valide => on prend $next_od
                $ds_sel = $next_od;
              } 
              $shipping_msg .= "<tr><td class='messageStackWarning'>".ahiShippingInfo(" ... prochaine livraison : le ", $order->products[$i], $ds_sel);
            }
            if ($op_qty != $qty_db) {
              $shipping_msg .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(avec prise en compte de la nouvelle quantité (<b>".tep_format_qty_for_html($op_qty)."</b> au lieu de ".tep_format_qty_for_html($qty_db).")";
            }
            $shipping_msg .= "</td></tr>";
          }
        }
        $ds_max = $record['date_shipped']; // on supprime l'heure ("20:00:00")
      } else { // no record found in opm
        if ($recurrent_order) {
          // c'est bien une commande récurrente : le produit n'a jamais été validé par le producteur
          if (strtotime($today_date) > strtotime($op_next_date_shipped)) {
            // le produit n'a pas été livré (le producteur ne livrait pas cette semaine là) => c'est rouge
            // il faut recalculer le nds prochain
            $new_nds = getNextValidNDS($op_next_date_shipped, "", $p_id, $op_shipping_day, $op_shipping_frequency);
            $shipping_msg .= "<tr><td class='messageStackError'>le produit n'a pas été livré le <b>$next_date_shipped_formatted</b><br>".ahiShippingInfo(" ... prochaine livraison : le ", $order->products[$i], getFormattedLongDate($new_nds, true))."</td></tr>";
          } else {
            // le produit peut encore êre livré mais on attend la validation producteur
            $shipping_msg .= "<tr><td class='messageStackWarning'>".ahiShippingInfo("livraison prévue le ", $order->products[$i], $op_next_date_shipped)."</b></td></tr>";
          }
        } else {
          // c'est une erreur (car même quand on met la quantité à 0, on rajoute un enreg qty=0 dans opm!
          $shipping_msg .= "<tr>".tep_error_message(true, "Ce produit (id = $p_id, op_id = $op_id) n'a pas été enregistré correctement : il ne sera pas pris en compte par le producteur.")."</tr>"; 
        }
      }
//      $shipping_msg .= "</table>";
  
      $curPrice = $op_qty*$op_price;  
      $price_text = $currencies->format($op_price * $op_qty, true, $order->info['currency'], $order->info['currency_value']);
      $qty = $op_qty;
  
      if ($opm_exist) {
        $opm_id = $record['orders_products_modifications_id']; 
        $opm_qty = $qty_db; 
  
        $opm_price = $record['final_price']; 
        if (($record['products_options'] != '')&&($record['products_options_values'] != '')&&
            ($record['products_options'] != $order->products[$i]['attributes'][0]['option'])&&($record['products_options_values'] != $order->products[$i]['attributes'][0]['value'])) {
          if ((trim($record['products_options'])!="")&&(trim($record['products_options_values'])!="")) {
            $p_attributes = '<small><i> - ' . $record['products_options'] . ': ' . $record['products_options_values'] . '</i></small>';
          }
        }
//          echo $curPrice." ".$opm_qty*$opm_price."<br>";
//        if (((!$recurrent_order)&&($curPrice != $opm_qty*$opm_price)) || (($recurrent_order)&& ($curPrice != $op_qty*$op_price) )) {
          // prix total différent
          if (!$recurrent_order) {
//            $curPrice = $op_qty*$op_price;  
//          } else {
            $curPrice = $opm_qty*$opm_price;  
          }
          $price_text = $currencies->format($curPrice, true, $order->info['currency'], $order->info['currency_value']);
//          $change_of_price = true;
//        }

        if (($op_qty!=$opm_qty)&&($recurrent_order)) {
          // quantité différente pour commande récurrente
          $qty_block = getQtyBlock($op_id, $opm_id, $op_qty, $opm_price, $p_id, $ds_max, $order->products[$i]['is_bulk'] == 1, $group_id, $order->products[$i]['measure_unit']);
        } else {
          $qty = $opm_qty;
          if ($recurrent_order) {
            $qty_block = getQtyBlock($op_id, $opm_id, $opm_qty, $opm_price, $p_id, $ds_max, $order->products[$i]['is_bulk'] == 1, $group_id, $order->products[$i]['measure_unit']);
          } else {
            $qty_block = getQtyBlock($op_id, $opm_id, $opm_qty, $opm_price, $p_id, $date_shipped, $order->products[$i]['is_bulk'] == 1, $group_id, $order->products[$i]['measure_unit']);
          }
        }
      } else {
        // pas d'enreg dans opm (commande récurrente)
        $qty_block = getQtyBlock($op_id, "", $op_qty, $op_price, $p_id, "", $order->products[$i]['is_bulk'] == 1, $group_id, $order->products[$i]['measure_unit']);
      }
      $table_products .= '
            <tr>
              <td class="smallTextNoBorder" align="center" nowrap>';
  
/*
      if (($recurrent_order)&&(canModQty($p_id, $date_shipped, $group_id))) {
        $table_products .= "&nbsp;&nbsp;".tep_draw_checkbox_field('del_product[]', $op_id, false, '', 'del_product'.$op_id)."&nbsp;&nbsp;";
      }
*/
              
      if (tep_not_null($order->products[$i]['image'])) {
        $image = tep_image(DIR_WS_CATALOG_IMAGES . $order->products[$i]['image'], $order->products[$i]['image'], 20, 20, '', 20).' ';
      } else {
        $image = '';
      }
      
      if ($adminMode == "yes") {
        $delLink = '<a href="javascript:deleteProductFromOrder(\''.$op_id.'\');">'.tep_image(DIR_WS_ICONS . 'cross.gif', ICON_CROSS).'</a>&nbsp;&nbsp;&nbsp;';
      }
      
      $table_products .= '
              </td><td class="smallTextNoBorder" align="center" nowrap valign="middle">'.$delLink.$qty_block.'
              <td class="smallTextNoBorder" align="center" valign="middle">&nbsp;x&nbsp;</td>
              <td class="smallTextNoBorder" align="center" valign="middle">'.$image.'
              <td class="smallTextNoBorder" align="left" nowrap width="100%" valign="middle">'.getQtyStriked($qty, tep_truncate_string($order->products[$i]['name'], MAX_PRODUCTS_NAMES_LENGTH).$p_attributes).'</td>
              <td class="smallTextNoBorder" align="right" width="100%" colspan="2" valign="middle">'.getQtyStriked($qty, $price_text).'</td></tr>';
  
      if (($qty>0)) {
        $table_products .= '
            <tr>
              <td colspan="3" nowrap>';
        if ($recurrent_order) {
          $table_products .= "<span class='messageStackRec'>&nbsp;<u>récurrence:</u> <b>".convertEnglishDateNames_fr($op_shipping_day)."</b>&nbsp;<br>&nbsp;".convertShippingFrequencyToText_fr($op_shipping_frequency)."</b>&nbsp;</span>";
        } else {
          $table_products .= "&nbsp;";
        }
        $table_products .= '
              </td><td colspan="4"><table width="100%">'.$shipping_msg.'</table></td>
            </tr>';
      }   
         
      $total_price += $curPrice;
    } // end for loop
    $p_id_list = trim($p_id_list, ",");
  
    $table .= $table_products.'<tr><td colspan="7"><hr></td></tr>';
    $table .= '<tr><td class="smallTextNoBorder" colspan="2">&nbsp;</td><td class="smallTextNoBorder" witdh="100%" colspan="3">';
  
    if ($can_mod_at_least_one) {
      $table .= $table_btn_recal.$table_btn_add;
    } else {
      $delCB = '';
      $table .= '&nbsp;';
    }
    $table .= '</td>';

    $table = str_replace('<!DEL_CB!>', $delCB, $table);

    $p_aux = $currencies->format($total_price, true, $order->info['currency'], $order->info['currency_value']);

//    if (!$change_of_price) {
    $recalc = "";
    if (!strpos($order->totals[0]['text'], $p_aux)) {
//      echo $order->totals[0]['text'].$p_aux;exit;
      calcul_ot_one($oID, $recurrent_order);
      $recalc = " <i>(recalculé)</i>";
    }
    
//      for ($i=0, $n=sizeof($order->totals); $i<$n; $i++) {
        $table .= '
          <td class="smallTextNoBorder" align="right" nowrap>' . $order->totals[0]['title'] . '</td>
          <td class="smallTextNoBorder" align="right">' . $order->totals[0]['text'] .$recalc. '</td>';
//      }

/*
    } else {
      $table .= '
          <td class="smallTextNoBorder" align="right" nowrap>Total <small><i>(recalculé)</i></small> :</td>
          <td class="smallTextNoBorder" align="right"><b>' . $p_aux . '</b></td>';
    }  
*/
  }

  if ($can_mod_at_least_one) {
    if (isset($HTTP_GET_VARS['product_id'])) {
      $disp = "block";
    } else {
      $disp = "none";
    }
  
    $table .= '</tr></table>
      <div id="addProductDiv" style="display:'.$disp.';">
        <table width="500px">'.
          addProduct(-1, $order->info['group_id'], $oID, $p_id_list, $recurrent_order, $adminMode == "yes", $orderAdminMode == "yes").'
        </table></div></form>';
  } else {
    $table .= '</table>';
  }
  $table .= '</form>';
  
  // =========================== FIN DE $table ==========================

  $validated_orders_table = "";
  if ($recurrent_order) {
    $validated_orders_table = '<tr><td>';
    $validations_list = ''; 
    
    //date_shipped < '".$cj_order_date."' AND 
    $sql = "SELECT products_name, products_quantity, final_price, date_shipped, orders_products_id FROM orders_products_modifications WHERE orders_id = ".$oID." AND facturate= 'Y' AND products_quantity > 0 ORDER BY date_shipped DESC, products_name ASC;";
    $query = tep_db_query($sql, 'db_link');
    $cur_ds = "";
    while ($record = tep_db_fetch_array($query)) {
      $ds = $record['date_shipped'];
      if ($cur_ds != $ds) {
        if ($cur_ds != '') {
          $validations_list .= ' 
            <tr class="infoBoxContents">
              <td colspan="5" align="right">Total : <b><!PRICE!></b></td>
            </tr>';
        }

        if (strtotime($ds)>strtotime(date("Y-m-d"))) {
          $txt = " (livraison non réalisée)";
          $txt .= "<br><span class='messageStackWarning'>ATTENTION, n'apparaissent ci-dessous que les produits qui ont été validés par les producteurs</span>";
        } else {
          $txt = "";
        }
        
        $validations_list = str_replace("<!PRICE!>", $currencies->format($total_price_validations, true, $order->info['currency'], $order->info['currency_value']), $validations_list);
        $validations_list .= ' 
            <tr class="infoBoxContents">             
              <td class="smallTextNoBorder" colspan="5" nowrap width="100%"><span class="messageStackSuccess"><u>Livraison du <b>'.getFormattedLongDate($ds, true).'</b>'.$txt.'</u></span></td>
            </tr>';
        $total_price_validations = 0.0;
      }
          
      $validations_list .= ' 
          <tr class="infoBoxContents">
            <td>&nbsp;</td>
            <td align="right">'.tep_format_qty_for_html($record['products_quantity']).'&nbsp;x&nbsp;</td>
            <td nowrap width="100%">'.$record['products_name'].'</td>
            <td align="right">'.$currencies->format($record['products_quantity'] * $record['final_price'], true, $order->info['currency'], $order->info['currency_value']).'</td>
          </tr>';
      $total_price_validations += $record['products_quantity']*$record['final_price'];
      
      $cur_ds = $ds;
    }
    if ($validations_list != "") {
      $validations_list .= ' 
        <tr class="infoBoxContents">
          <td colspan="5" align="right">Total : <b><!PRICE!></b></td>
        </tr>';
      $validations_list = str_replace("<!PRICE!>", $currencies->format($total_price_validations, true, $order->info['currency'], $order->info['currency_value']), $validations_list);
      $validated_orders_table .= '<tr><td colspan="5"></td></tr>';
      $validated_orders_table .= '<tr><td colspan="5" class="smallTextNoBorder"><a href="javascript:showhide(\'all_rec_div\');"><b>Récapitulatif des livraisons pour cette commande récurrente :</b> <small><i>(cliquer pour cacher)</i></small></a></td></tr>';
      $validated_orders_table .= "<tr><td colspan='5'><div id='all_rec_div' style='display:block;'><table border='0' width='100%'' cellspacing='1' cellpadding='2' class='infoBox'>".$validations_list."</table></td></tr>";
      $validated_orders_table .= '</td></tr>';
    } else {
      $validated_orders_table = '';
    }
  }

  $history_table = '
        <tr>
        <td class="smallTextNoBorder"><a href="javascript:showhide(\'history_div\');"><b>'.HEADING_ORDER_HISTORY.' :</b> <small><i>(cliquer pour afficher)</i></small></a></td>
      </tr>
      <tr>
        <td>'.tep_draw_separator('pixel_trans.gif', '100%', '10').'</td>
      </tr>
      <tr>
        <td>
        <div id="history_div" style="display:none;">
        <table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">';
            
  $statuses_query = tep_db_query("select os.orders_status_id, os.orders_status_name, osh.date_added, osh.comments from " . TABLE_ORDERS_STATUS . " os, " . TABLE_ORDERS_STATUS_HISTORY . " osh where osh.orders_id = '" . (int)$HTTP_GET_VARS['order_id'] . "' and osh.orders_status_id = os.orders_status_id and os.language_id = '" . (int)$languages_id . "' order by osh.date_added");
  while ($statuses = tep_db_fetch_array($statuses_query)) {
    $history_table .= '              <tr>
         <td class="smallTextNoBorder" valign="top" width="70">' . tep_date_short($statuses['date_added']) . '</td>
         <td class="smallTextNoBorder" valign="top" width="70">' . $statuses['orders_status_name'] . '</td>
         <td class="smallTextNoBorder" valign="top">' . (empty($statuses['comments']) ? '&nbsp;' : nl2br(tep_output_string_protected($statuses['comments']))) . '</td>
        </tr>';
  }

  $history_table .= '
            </table></td>
          </tr>
        </table>
        </div>
      </td>
      </tr>';