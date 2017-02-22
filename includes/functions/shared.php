<?

  $can_mod_at_least_one = false;

  if ($adminMode != "yes") {
    $oID = $HTTP_GET_VARS['order_id'];
  } else {
    $oID = $HTTP_GET_VARS['oID'];
  }

  if (is_numeric($oID)) {
    $order = new order($oID);
    $recurrent_order = (($order->info['orders_status_id'] == '4')||($order->info['orders_status_id'] < '0'));
    $recurrent_order_int = (int)$recurrent_order;
    if (!isset($customer_id) || ($customer_id <= 0)) {
      $customer_id = $order->customer['id'];  
    }
  }

  // on récupère la prochaine date de livraison
  $shipping_dates_array_vd = array();   // for admin/customers.php only
  $shipping_dates_array = array();
  $facturation_months_array = array();
  $last_order_date_vd = "";
  $last_order_date = "";
  
  $mClass="";
  $wClass="";


  if ($is_week_mode) {
    $wClass = " class='messageStackSuccessBig'";  
    $facturation_months_array[] = array('id' => '', 'text' => '');
  }
  if ($is_month_mode) {
    $mClass = " class='messageStackSuccessBig'";  
    $shipping_dates_array[] = array('id' => '', 'text' => '');
  } 

  $t_txt = "";
  if (($fromCustomers == "yes")||($gaID<=0)) {
    // vente directe
//                          $next_order_date = get_order_date($manufacturer_id);
    $cur_order_date = strtotime("-6 weeks", strtotime($order_date_to));
//                          echo date("Y-m-d");
    for ($i=0; $i<10; $i++) {
      $add = false;
      if (($shipping_day == "thursday")||($shipping_frequency < 1)||($shipping_day == "tuesday|thursday")){
        $cur_order_date = getNextThursday($cur_order_date);
//                              $cur_order_date_formatted = date("Y-m-d", $cur_order_date);
        (tep_db_prepare_input(orders_are_frozen("", $manufacturer_id, $cur_order_date) != "")) ? $frozen = " (validée)" : $frozen = "";
        $shipping_dates_array[] = array('id' => $cur_order_date,
                               'text' => getFormattedLongDate($cur_order_date, true).$frozen);
        if (strtotime($cur_order_date) < strtotime($order_date_to)) $last_order_date = $cur_order_date;
      }
      if (($shipping_day == "tuesday")||($shipping_frequency < 1)||($shipping_day == "tuesday|thursday")){
        $cur_order_date = getNextTuesday($cur_order_date);                            
//                              $cur_order_date_formatted = date("Y-m-d", $cur_order_date);
        (tep_db_prepare_input(orders_are_frozen("", $manufacturer_id, $cur_order_date) != "")) ? $frozen = " (validée)" : $frozen = "";
        $shipping_dates_array[] = array('id' => $cur_order_date, 
                               'text' => getFormattedLongDate($cur_order_date, true).$frozen);
        if (strtotime($cur_order_date) < strtotime($order_date_to)) $last_order_date = $cur_order_date;
      }
      if ($shipping_day == "saturday"){
        $cur_order_date = getNextSaturday($cur_order_date);                            
//                              $cur_order_date_formatted = date("Y-m-d", $cur_order_date);
        (tep_db_prepare_input(orders_are_frozen("", $manufacturer_id, $cur_order_date) != "")) ? $frozen = " (validée)" : $frozen = "";
        $shipping_dates_array[] = array('id' => $cur_order_date,
                               'text' => getFormattedLongDate($cur_order_date, true).$frozen);
        if (strtotime($cur_order_date) < strtotime($order_date_to)) $last_order_date = $cur_order_date;
      }
    }

    // on génère le mois en cours en les x mois précédents
    for ($i=3;$i>=0;$i--) {
      if ($i>0) {
        $cur_month = strtotime(getPreviousMonth("", "", $i));
      } else {
        $cur_month = strtotime(date("Y-m-d"));
      }
      $id = getFirstDayOfMonthFromDT($cur_month)."|".getLastDayOfMonthFromDT($cur_month);
      $text = convertEnglishDateNames_fr(date('F', $cur_month));
      $facturation_months_array[] = array('id' => $id, 'text' => $text);
    }
    $t_txt = " ou <u>facturation</u>";
    
    if ($fromCustomers == "yes") {
      $shipping_dates_array_vd = array_merge($shipping_dates_array);
      $last_order_date_vd = $last_order_date;
    }
    
  } 

  if (($fromCustomers == "yes")||($gaID>0)) {
    // grpt d'achat

    $shipping_dates_array = array();
    $last_order_date = "";
    
    // on récupère les 15 dernières livraisons du groupement d'achat
    // pour cela, on utilise la nouvelle table  manufacturers_validations             
    $sql = "SELECT date_shipped FROM manufacturers_validations 
        WHERE manufacturers_id = '".-$gaID."'".//" AND date_shipped <= '".$order_date_to."'
        " ORDER BY date_shipped DESC LIMIT 15;"; 
    $query = tep_db_query($sql);
		$frozen = " (validée)"; // les précentes commandes sont validées, puisqu'elles sont dans manufacturers_validations !
    $last_order_date = $order_date_to;
    while ($result = tep_db_fetch_array($query)) { 
      // rajout de la date au début   (array_unshift)
      $last_order_date = $result['date_shipped'];
      array_unshift($shipping_dates_array, array('id' => $result['date_shipped'],
                           'text' => getFormattedLongDate($result['date_shipped'], true).$frozen));
    }	
    
    // prochaine livraison  : remplacement de next_order_date par $order_date_to
    $nds = getGA_order_date($gaID);
    if (($nds != "")&&($nds != '0000-00-00')) {
      if (!in_array_recursive($nds, $shipping_dates_array)) {
        (tep_db_prepare_input(ordersGA_are_frozen($gaID, $nds) != "")) ? $frozen = " (validée)" : $frozen = "";
        $shipping_dates_array[] = array('id' => $nds,
                               'text' => getFormattedLongDate($nds, true).$frozen);
      }
    }

    // livraison suivante
    $nnds = getGA_order_date_next($gaID);
    if (($nnds != "")&&($nnds != '0000-00-00')) {
      if (!in_array_recursive($nnds, $shipping_dates_array)) {
        (tep_db_prepare_input(ordersGA_are_frozen($gaID, $nnds) != "")) ? $frozen = " (validée)" : $frozen = "";
        $shipping_dates_array[] = array('id' => $nnds,
                               'text' => getFormattedLongDate($nnds, true).$frozen);
      }
    }
  }

  if (($HTTP_POST_VARS['order_date_to'] == "") && ($HTTP_GET_VARS['order_date_to'] == "") && (strtotime(date("Y-m-d")) <= strtotime($last_order_date))) {
    $order_date_to = $last_order_date;
    $order_date_from = $order_date_to;  
    $HTTP_POST_VARS['order_date_from'] = $order_date_to;
    $HTTP_POST_VARS['order_date_to'] = $order_date_to;
  } else if (($fromCustomers == "yes")&&(strtotime(date("Y-m-d")) > strtotime($last_order_date))) {
    $last_order_date_vd = $order_date_to; 
    $last_order_date = $nds; 
  } 

  if (isset($HTTP_POST_VARS['action'])) {
    $mydatetime = date("Y-m-d H:i:s");
    $calculate_ot = false;

    if (($adminMode == "yes")||($orderAdminMode == "yes")) {
      $t_who = "producteur";
    } else {
      $t_who = "vous-même";
    }
    

    if (($HTTP_POST_VARS['action']=="update_qty") &&
      !empty($HTTP_POST_VARS['product_qty']) && 
      !empty($HTTP_POST_VARS['op_id']) && 
      !empty($HTTP_POST_VARS['p_id']) && 
      !empty($HTTP_POST_VARS['ds']) && 
      !empty($HTTP_POST_VARS['modified']) && 
      !empty($HTTP_POST_VARS['product_qty_save']) &&
      !empty($HTTP_POST_VARS['product_price'])) {

      $change_one = false;

      //on modifie les quantités
      for ($i = 0; $i < count($HTTP_POST_VARS["product_qty"]); $i++) {
        $modified = ($HTTP_POST_VARS["modified"][$i] == "yes");
        if ($modified) {
          $qty = tep_format_qty_for_db($HTTP_POST_VARS["product_qty"][$i]);
          $qty_save = tep_format_qty_for_db($HTTP_POST_VARS["product_qty_save"][$i]);
          $price = $HTTP_POST_VARS["product_price"][$i];
          $op_id = $HTTP_POST_VARS["op_id"][$i];
          $to_del = false;
  /*      disabled on 2010-09-19
          if (!empty($HTTP_POST_VARS['del_product']) && is_array($HTTP_POST_VARS['del_product'])) {
            $to_del = in_array($op_id, $HTTP_POST_VARS["del_product"]); 
          }
  */
          $p_id = tep_get_prid($HTTP_POST_VARS["p_id"][$i]);
          
          if (!$to_del) {
            $opm_id = $HTTP_POST_VARS["opm_id"][$i];
            $ds = $HTTP_POST_VARS["ds"][$i];
            
            if (((is_qty($qty))&&(is_qty($qty_save))&&($qty!=$qty_save))) {
              $change_one = true;
              $id = -1;
              $m = "";
              
              if ($recurrent_order) {
                // c'est une récurrence => on modifie la table op
                $sql = "UPDATE orders_products SET products_quantity = '$qty' WHERE orders_products_id = '$op_id'"; 
            		$query = tep_db_query($sql);
            		
            		$id = $op_id;
                                                                                                      
                $sql_add = "INSERT INTO " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments) VALUES ('".$oID."','".$order->info['orders_status_id']."','$mydatetime','0','modification quantités par $t_who dans op$m (op$m_id = $id, produit = \'".tep_db_input(tep_get_products_name($p_id))."\', quantité avant modif = $qty_save, quantité après modif = $qty)');";
                tep_db_query($sql_add, 'db_link');
    
                $calculate_ot = true; // commande récurrente : il n'y a pas besoin de mettre à jour le stock dans ce cas
              } else {
                // ce n'est pas une récurrence => on modifie la table opm
  //              echo "c_id".$customer_id."!".$cID.$customers_addproduct.$oID;exit;
                if (modOrderProduct($opm_id, $customer_id, $p_id, $ds, $qty, $price, "", false, ($adminMode == "yes")||($orderAdminMode == "yes"))) {
              		$id = $opm_id;
                  $m = "m";
                }
                $calculate_ot = false; // stock et ot calculés dans modOrderProduct
              }
            }
  
            // gestion des modifications de shipping_day et shipping_frequaency (from account_history_info_table.php)
            $attr_mod = $HTTP_POST_VARS["attr"][$i];  // op_id (ie : "op_id<op_id>")
    
            $nds_mod = $HTTP_POST_VARS["next_date_shipped".$p_id.$attr_mod];
            $sf_mod = $HTTP_POST_VARS["shipping_frequency".$p_id.$attr_mod];
            if (($nds_mod != "")&&($sf_mod>0)) {
              // on a défini une date de livraison
              // on applique cette date de livraison à l'enreg op_id
              $op_id_mod = str_replace("op_id", "", $attr_mod);
              if ($sf_mod == 0.5) {
                $sd_mod = 'tuesday|thursday';
              } else {
                $sd_mod = strtolower(date("l", strtotime($nds_mod)));
              }
    
              $sql_seek = "SELECT date_shipped, next_date_shipped, shipping_day, shipping_frequency FROM " . TABLE_ORDERS_PRODUCTS . " WHERE orders_products_id = $op_id_mod";
  //            echo $sql_seek;
              if ($result_seek = tep_db_fetch_array(tep_db_query($sql_seek))) {
  //              $ds_mod_old = $result_seek['date_shipped']; 
                $nds_mod_old = $result_seek['next_date_shipped']; 
                $sd_mod_old = $result_seek['shipping_day']; 
                $sf_mod_old = $result_seek['shipping_frequency']; 
                
  /*
                echo $nds_mod_old."|".$sd_mod_old."|".$sf_mod_old."|NEW|";
                echo $nds_mod."|".$sd_mod."|".$sf_mod."<br>";
  */
                if (($nds_mod_old!=$nds_mod)||
                    ($sd_mod_old!=$sd_mod)||
                    ($sf_mod_old!=$sf_mod)) {
  
                  $sql_add = "UPDATE " . TABLE_ORDERS_PRODUCTS . " SET 
                      next_date_shipped = '$nds_mod',
                      shipping_day = '$sd_mod',
                      shipping_frequency = $sf_mod 
                    WHERE orders_products_id = $op_id_mod";
  //                    date_shipped = '$ds_mod',    // date_shipped correspond toujours à la première date de livraison de la commande, c'est la date de référence qu'il ne faut jamais changer
                  tep_db_query($sql_add, 'db_link');
                  
                  // ajout d'un historique
                  $sd_txt = "";
                  $sf_txt = "";
                  $ds_txt = "";
                  if ($sd_mod_old!=$sd_mod) $sd_txt = ", jour de livraison (".convertEnglishDateNames_fr($sd_mod_old)." => ".convertEnglishDateNames_fr($sd_mod).")"; 
                  if ($sf_mod_old!=$sf_mod) $sf_txt = ", fréquence ($sf_mod_old => $sf_mod)"; 
                  if ($nds_mod_old!=$nds_mod) $nds_txt = ", prochaine date de livraison (\'".getFormattedLongDate($nds_mod_old, true)."\' => \'".getFormattedLongDate($nds_mod, true)."\')"; 
  
                  $sql_add = "INSERT INTO " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments) VALUES ('".$oID."','".$order->info['orders_status_id']."','$mydatetime','0','modification des infos de livraison par $t_who (op_id = $op_id, p_id = $p_id, produit = \'".tep_db_input(tep_get_products_name($p_id))."\'".$sd_txt.$sf_txt.$nds_txt.")');";
                  tep_db_query($sql_add, 'db_link');
                }
              }
            }
  /*          
          } else if (($recurrent_order)&&($to_del)) {
            // suppression du produit
            $sql_add = "UPDATE " . TABLE_ORDERS_PRODUCTS . " SET orders_id = -orders_id WHERE orders_products_id = $op_id";
            tep_db_query($sql_add, 'db_link');
            
            // ajout d'un historique
            $sql_add = "INSERT INTO " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments) VALUES ('".$oID."','".$order->info['orders_status_id']."','$mydatetime','0','suppression du produit par $t_who (op_id = $op_id, p_id = $p_id, produit = \'".tep_db_input(tep_get_products_name($p_id))."\', quantité = $qty_save)');";
            tep_db_query($sql_add, 'db_link');
  
            $calculate_ot = true; // commande récurrente : il n'y a pas besoin de mettre à jour le stock dans ce cas
  */
          }
          
        }
      }
      $order = new order($oID);
      if ($change_one) $reload = true;
      
//          exit;
    }

    if ($HTTP_POST_VARS['action'] == "addproduct") {
      // on rajoute un produit à un client
      $ids = explode("§", $HTTP_POST_VARS['products_addproduct']);
      $p_id = tep_get_prid($ids[0]);
      $attr = array($ids[1] => $ids[2]);
      $qty = $HTTP_POST_VARS['quantity_addproduct'];
      $adminModeEnd = false;
    
      if ($adminMode == "yes") {
        $adminModeEnd = true;
        // récupération du cID
        $customer_id_sav = $customer_id;
        $customer_id = $HTTP_POST_VARS['customers_addproduct'];
        if ($customer_id<=0) {
          $customer_id = $customer_id_sav;
        } 
        if ($customer_id<=0) {
          echo "cID empty!";exit;
        }

        if ($orderAdminMode == "yes") {
          $adminMode = "addproduct";
          $order_date_to = $order->getMinDateShipped($oID);
        }

        tep_session_register('cart');
        tep_session_register('customer_id');
        $cart = new shoppingCart;
        $recurrent_order = false; // retiré le 25/11/2012, prkoi il est forcé ?
        
      } else {
        $cart->reset();
        $adminMode = "addproduct";
      }


      $cart->add_cart($p_id, $qty, $attr, false, true);
      // intégration checkout_process.php
      require($doc_root . $subpath . 'checkout_process.php');
      $oID = $insert_id;
      $cart->cleanup();

      if ($adminModeEnd) {
        $txt = "";
        
        // mise à jour du stock si la commande est figée
        if (!isset($has_frozen_orders)) {
          // on doit vérifier si les commandes sont figées
          $group_id_global = $order->info['group_id'];
          ($group_id_global > 0) ? $gaID_global = 1 : $gaID_global = 0;
          
          $has_frozen_orders = (orders_are_frozen_global($group_id_global, $order_date_to, $p_id, "", $gaID_global) != ""); 
        }
        
        if ($has_frozen_orders) {
          // mise à jour du stock uniquement dans le cas des commandes validées
          tep_update_stock($p_id, $qty);
          $txt = " Les commandes sont validées => le stock a été mis à jour.";
        }
        
        $do_not_redeclare_order = true;
    
        // purge des variables
        tep_session_unregister('cart');
        tep_session_unregister('customer_id');
    
        $customer_id = $customer_id_sav;
        
        /* marche pas, snif
        $messageStack->reset();
        $messageStack->add_session("Le produit a bien été ajouté à l\'adhérent.".$txt, 'success');
        */
        $reload = true;
      } // ajout le 11/09/2010
/*             // commenté le 11/09/2010 pour ajouter le msg à chaque fois !
      } else { 
*/
        $order = new order($oID);
        // ajout d'un historique
        $sql_add = "INSERT INTO " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments) VALUES ('".$oID/*$HTTP_GET_VARS['order_id']*/."','".$order->info['orders_status_id']."','$mydatetime','0','ajout de produit par $t_who (p_id = $p_id, produit = \'".tep_db_input(tep_get_products_name($p_id))."\', quantité = $qty)');";
        tep_db_query($sql_add, 'db_link');

//      }     // commenté le 11/09/2010 pour ajouter le msg à chaque fois !
      $calculate_ot = true; // le stock a bien été mis à jour dans checkout_process
    }    

    // vu que l'on a rajouté un produit, on peut supprimer les fake products
    if (is_numeric($oID)) {
      tep_db_query("DELETE FROM " . TABLE_ORDERS_PRODUCTS . " WHERE orders_id = -$oID");
      if ($calculate_ot) {
        calcul_ot_one($oID, $recurrent_order);
      }
    }

    if ($adminMode != "yes") {
      if ($orderAdminMode != "yes") {
        // on fait une redirection de cette page pour éviter les reload intempestifs
        tep_redirect(tep_href_link(FILENAME_ACCOUNT_HISTORY_INFO, querystring_small()));
      } else {
        tep_redirect(tep_href_link(FILENAME_ORDERS, tep_get_all_get_params(array('action','product_id')) . 'action=edit'));
      }
    }
  }
?>