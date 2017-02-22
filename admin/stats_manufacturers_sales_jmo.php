<?
/*
	Contribution Name: Manufacturer Sales Report
	Contribution Version: 2.3

	Creation of this file 
  	Author Name: Robert Heath
  	Author E-Mail Address: robert@rhgraphicdesign.com
  	Author Website: http://www.rhgraphicdesign.com
  	Donations: www.paypal.com
  	Donations Email: robert@rhgraphicdesign

	Modifications on PHP file 
    Date: 28/05/07
    Name: Cyril Jacquenot
    What's modified?
      * fixed localizations:
        * use of currency class, to use global currency settings (€, £, $)
        * use of PHP server date/time format (fr_FR, en-EN, ...)         
      * fixed: code merging
        * before: copy/paste had been used
        * now: 
          * same code appears once
          * sql requests have completely been rewritten
          * sql requests have been speeded up
          * html code renewed (html tags are now OK) 
          * better display of pages                                       
      * added: PHP file including functions only : stats_manufacturers_sales_functions.php
      * added: some styles in "printer.css" and "stylesheet.css"
      * added: possibility to see sold products :
        * for all manufacturers                  
        * by one manufacturer                  
        * by all the customers of one manufacturer                  
        * by every customers of one manufacturer              
        * by one customer of one manufacturer
        * for each request, there is the possibility to print a simple page                          
        * when listing products, there is now a link to the product detail  
    What's need to be fixed?
      * french date format gestion with SpiffyCal
                                           
  	Donations: www.paypal.com with email: cyril.jacquenot@laposte.net

	Released under the GNU General Public License
*/

  $adminMode = "yes";

  require('includes/application_top.php');
//  require('../includes/functions/general.php');

  require($admin_FS_path . DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();
  
  // global variables
  $MySQL_query_numrows = -1;
  $MySQL_query_split = "";
  $show_page_changer = "";
  $supplier_validated = false;
  $supplier_order_received = false;
  $manufacturer_id = "";
  $manufacturer_id_sav = "";
  $customer_id = "";
  $manufacturer_name = "";
  $customer_name = "";
  $md_opm_id = "";
  $last_freezing_date = "";
  $forceValidation = false;
  $has_recurrences_to_valid = false;
  $freezeOrders = false;
  $has_frozen_orders = false;
  $do_not_redeclare_order = false;
  $order_date_from = "";
  $order_date_to = "";
  $show_stock = 0;

  $periodNames = array("Semaine", "Mois", "Personnalisé");

  if ((!empty($m_id_filter))&&($m_id_filter>0)) {
    $manufacturer_id = $m_id_filter; 
    $HTTP_GET_VARS['mID'] = $m_id_filter; 
  } else {
    if (!empty($HTTP_GET_VARS['mID']))    {$manufacturer_id = $HTTP_GET_VARS['mID'];}
    if (!empty($HTTP_GET_VARS['mName']))  {$manufacturer_name = remove_slashes(trim($HTTP_GET_VARS['mName']));}
  } 
  if (!empty($HTTP_GET_VARS['cID']))    {$customer_id = $HTTP_GET_VARS['cID'];}
  
  if (isset($HTTP_POST_VARS['m_id_from_array']))    {$m_id_from_array = $HTTP_POST_VARS['m_id_from_array'];}
  if (isset($HTTP_POST_VARS['p_id_from_array']))    {$p_id_from_array = $HTTP_POST_VARS['p_id_from_array'];}
  if ($customer_id != "all_by_man") {
    if (isset($HTTP_POST_VARS['c_id_from_array']))    {$c_id_from_array = $HTTP_POST_VARS['c_id_from_array'];}
  } else {
    $c_id_from_array = "";
  }
  


  $gaName = remove_slashes(trim($gaName));
  
  if (($manufacturer_id > 0)&&($manufacturer_name == "")) {
    // on récupère le nom du manufacturer s'il est vide
    $manufacturer_name = tep_manufacturers_name($manufacturer_id);  
  }

  if (($gaID > 0)&&($gaName == "")) {
    // on récupère le nom du groupement d'achat s'il est vide  
    $gaName = tep_ga_name($gaID);  
  }
  
  if (!empty($HTTP_GET_VARS['cName']))  {$customer_name = $HTTP_GET_VARS['cName'];}

//  if (!empty($HTTP_GET_VARS['page']))  {$MySQL_query_current_page = $HTTP_GET_VARS['page'];}

/*
  // fill manufacturers_validations table
  if ((!empty($HTTP_GET_VARS['fill_validations']))&&($HTTP_GET_VARS['fill_validations'] == "true")) {
		tep_db_query("DELETE FROM manufacturers_validations;");
		$query = tep_db_query("SELECT manufacturers_id, date_shipped, orders_products_modifications_datetime 
        FROM orders_products_modifications WHERE facturate = 'Y' AND is_recurrence_order = 1 GROUP BY manufacturers_id, date_shipped ORDER BY date_shipped ASC, manufacturers_id ASC, orders_products_modifications_datetime DESC;", 'db_link');
    $today = date('Y-m-d H:i:s');
    $i = 0;
    while ($record = tep_db_fetch_array($query)) {
			tep_db_query("INSERT INTO manufacturers_validations (manufacturers_id, date_shipped, validation_datetime) 
        VALUES (".$record['manufacturers_id'].",'".$record['date_shipped']."','".$record['orders_products_modifications_datetime']."');", 'db_link');
      $i += 1;
		}
		if ($i>0) {
		  echo "$i records added in MANUFACTURERS_VALIDATIONS";
    }
  }

  // renseigner les next_date_shipped
  if (!empty($HTTP_GET_VARS['calculate_nds'])) {
    if (($HTTP_GET_VARS['calculate_nds'] == "true")||($HTTP_GET_VARS['calculate_nds'] == "force")){
      set_time_limit(0);
      
      $sql = "SELECT manufacturers_id, date_shipped, shipping_day, shipping_frequency, 
                products_id, orders_products_id, is_recurrence_order FROM orders_products;";
      $query = tep_db_query($sql, 'db_link');
    	$i = 0;
      while ($record = tep_db_fetch_array($query)) {
        $op_id = $record['orders_products_id'];
        $is_rec = ($record['is_recurrence_order'] == 1);
        $sf = $record['shipping_frequency'];
        if ($sf < 0.5) { $sf = 1.0; }
        $nds = "";
        $mds = "";
        
        if (!$is_rec) {
          // ce n'est pas une commande récurrente => nds = ds
          $nds = $record['date_shipped'];
        } else {
          $sql = "SELECT MAX(date_shipped) AS mds FROM orders_products_modifications AS opm
                    LEFT JOIN orders AS o ON opm.orders_id = o.orders_id 
                    WHERE o.orders_status = 4 AND opm.is_recurrence_order = 1 AND opm.orders_products_id = $op_id AND opm.facturate= 'Y' ORDER BY opm.date_shipped DESC, opm.orders_products_modifications_datetime DESC;";
          $query1 = tep_db_query($sql, 'db_link');
          $record1 = tep_db_fetch_array($query1);
          $mds = $record1['mds'];
          if (($mds != "")&&($mds != "0000-00-00")) {
            // c'est une commande récurrente qui a déjà eu des livraisons
            // on est dans le cas où l'on a un enreg dans opm => la commande est donc forcément validée
            // nds doit absolument pointer vers la livraison suivante à mds
            $nds = get_order_date_arg($mds, "", $record['products_id'], $record['shipping_day'], $sf);
          } else {
            // cette commande n'a jamais été livrée, si on est dans ce cas, c'est forcément une commande récurrente
            // ce cas est simple : la date de la prochaine livraison est 'date_shipped' de la table op
            $nds = $record['date_shipped'];
          }
        }
        
        if ($nds != "") {
          tep_db_query("UPDATE orders_products SET next_date_shipped = '$nds', shipping_frequency = '$sf' WHERE orders_products_id = '$op_id';", 'db_link');
            
        	$i += 1;
        }
      }
      set_time_limit(30);
      
      if ($i>0) {
        echo "$i records modified.<br><br>";
      }
    }
  }
*/

	//RECALCUL DES ORDERS_TOTAL
  if ((!empty($HTTP_GET_VARS['change_ot']))&&($HTTP_GET_VARS['change_ot'] == "true")) {
    set_time_limit(0);

//		calcul_ot_one_old(2872, "_modifications");
    calcul_ot();

    set_time_limit(30);
	}

  // CALCUL DES NOMS DES PRODUITS
  if ((!empty($HTTP_GET_VARS['change_pn']))&&($HTTP_GET_VARS['change_pn'] == "true")) {
    set_time_limit(0);

    propagate_product_name_changes("");

    set_time_limit(30);
  }                                                  

  // CHANGER LA CASSE
  if ((!empty($HTTP_GET_VARS['change_names']))&&($HTTP_GET_VARS['change_names'] == "true")) {
    set_time_limit(0);

		//TABLE customers
		$query = tep_db_query("SELECT customers_firstname,customers_lastname,customers_id,customers_email_address,customers_telephone FROM customers;", 'db_link');
    while ($record = tep_db_fetch_array($query)) {
			tep_db_query("UPDATE customers SET 
				customers_firstname = '".addslashes_once(ucwords(strtolower($record['customers_firstname'])))."', 
				customers_lastname = '".addslashes_once(strtoupper($record['customers_lastname']))."',
				customers_email_address = '".addslashes_once(strtolower($record['customers_email_address']))."', 
				customers_telephone = '".tep_format_tel($record['customers_telephone'])."' 
			WHERE customers_id = ".$record['customers_id'].";", 'db_link');
		}

		//TABLE address_book
    $query = tep_db_query("SELECT ab.entry_street_address,ab.entry_city,ab.entry_postcode,ab.address_book_id,c.customers_firstname,c.customers_lastname FROM address_book AS ab 
      LEFT JOIN customers AS c ON c.customers_id = ab.customers_id;", 'db_link');
    while ($record = tep_db_fetch_array($query)) {
			if (($record['customers_firstname'] == '') || ($record['customers_lastname'] == '')) {
        tep_db_query("DELETE FROM address_book WHERE address_book_id = ".$record['address_book_id'].";", 'db_link');
      } else {
        tep_db_query("UPDATE address_book SET 
          entry_firstname = '".addslashes_once($record['customers_firstname'])."',
          entry_lastname = '".addslashes_once($record['customers_lastname'])."',
    			entry_street_address = '".addslashes_once(strtolower($record['entry_street_address']))."',
    			entry_postcode = '".tep_format_cp($record['entry_postcode'])."',
          entry_city = '".addslashes_once(strtoupper($record['entry_city']))."',
    			entry_state = NULL,
    			entry_suburb = IF(entry_suburb IS NULL OR entry_suburb = '', NULL, entry_suburb),
    			entry_country_id = 73
    		WHERE address_book_id = ".$record['address_book_id'].";", 'db_link');
      }
		}

		//TABLE orders
		$query = tep_db_query("SELECT o.orders_id,o.customers_name,o.billing_name,o.customers_email_address,o.customers_company,
  			o.customers_city,o.customers_street_address,o.customers_country,
  			o.delivery_city,o.delivery_street_address,o.delivery_country,
  			o.billing_city,o.billing_street_address,o.billing_country,c.customers_firstname,c.customers_email_address AS email,c.customers_lastname,
        ab.entry_street_address,ab.entry_city,co.countries_name
			FROM orders AS o
			LEFT JOIN customers AS c ON o.customers_id = c.customers_id
			LEFT JOIN address_book AS ab ON c.customers_default_address_id = ab.address_book_id
      LEFT JOIN countries AS co ON co.countries_id = ab.entry_country_id;", 'db_link');
    while ($record = tep_db_fetch_array($query)) {
			tep_db_query("UPDATE orders SET 
				customers_name = '".addslashes_once($record['customers_firstname'])." ".addslashes_once($record['customers_lastname'])."',
				billing_name = '".addslashes_once($record['customers_firstname'])." ".addslashes_once($record['customers_lastname'])."',
				customers_email_address = '".addslashes_once($record['email'])."', 
				customers_street_address = '".addslashes_once(strtolower($record['entry_street_address']))."',
				customers_postcode = '".$record['entry_postcode']."',
				customers_city = '".addslashes_once($record['entry_city'])."',
				customers_country = '".addslashes_once($record['countries_name'])."',
				delivery_street_address = '".addslashes_once(strtolower($record['entry_street_address']))."',
				delivery_postcode = '".$record['entry_postcode']."',
				delivery_city = '".addslashes_once(strtoupper($record['entry_city']))."',
				delivery_country = '".addslashes_once($record['countries_name'])."',
				billing_street_address = '".addslashes_once(strtolower($record['entry_street_address']))."',
				billing_postcode = '".$record['entry_postcode']."',
				billing_city = '".addslashes_once(strtoupper($record['entry_city']))."',
				billing_country = '".addslashes_once($record['countries_name'])."'
			WHERE orders_id = ".$record['orders_id'].";", 'db_link');
//				customers_company = '', // removed
		}

		//TABLE reviews
		$query = tep_db_query("SELECT c.customers_firstname,c.customers_lastname,r.reviews_id 
			FROM reviews AS r
			LEFT JOIN customers AS c ON r.customers_id = c.customers_id;", 'db_link');
    while ($record = tep_db_fetch_array($query)) {
			tep_db_query("UPDATE reviews SET 
				customers_name = '".addslashes_once($record['customers_firstname'])." ".addslashes_once($record['customers_lastname'])."'
		  WHERE reviews_id = ".$record['reviews_id'].";", 'db_link');
		}
    set_time_limit(30);
  }

  getDates();

  require($doc_root . $subpath . DIR_WS_FUNCTIONS . 'shared.php');  
  $reload = false;

  // set printer-friendly toggle
  (tep_db_prepare_input($HTTP_GET_VARS['print']=='yes')) ? $print=true : $print=false;
  (tep_db_prepare_input($HTTP_GET_VARS['bulk_only']=='yes')) ? $bulk_only=true : $bulk_only=false;
  (tep_db_prepare_input($HTTP_GET_VARS['customer_only']=='yes')) ? $customer_only=true : $customer_only=false;
  // set inversion toggle
  
  if (!empty($HTTP_POST_VARS['checkbox']))    {$has_recurrences_to_valid = true;}
  if (!empty($HTTP_GET_VARS['force_validation']))    { $forceValidation = $HTTP_GET_VARS['force_validation'] == "true"; }
  if ((!$forceValidation)&&(!empty($HTTP_POST_VARS['force_validation'])))    { $forceValidation = $HTTP_POST_VARS['force_validation'] == "true"; }
  if (!empty($HTTP_POST_VARS['freeze_orders']))    {
  	$freezeOrders = (($HTTP_POST_VARS['freeze_orders'] == "true")||($HTTP_POST_VARS['freeze_orders'] == "on")||($HTTP_POST_VARS['freeze_orders'] == "*"));
  }

  // gestion des modif qty or price dans la table opm ==========>
  if (!empty($HTTP_GET_VARS['md_del_mode']))    {$md_del_mode = $HTTP_GET_VARS['md_del_mode'];}
  if (!empty($HTTP_GET_VARS['md_opm_id']))    {$md_opm_id = $HTTP_GET_VARS['md_opm_id'];}
  if (!empty($HTTP_GET_VARS['md_c_id']))    {$md_c_id = $HTTP_GET_VARS['md_c_id'];}
  if (!empty($HTTP_GET_VARS['md_p_id']))    {$md_p_id = $HTTP_GET_VARS['md_p_id'];}
  if (!empty($HTTP_GET_VARS['md_ds']))    {$md_ds = $HTTP_GET_VARS['md_ds'];}
  if (!empty($HTTP_GET_VARS['md_qty']))    {$md_qty = $HTTP_GET_VARS['md_qty'];}
  if (!empty($HTTP_GET_VARS['md_price']))    {$md_price = $HTTP_GET_VARS['md_price'];}
  if (!empty($HTTP_GET_VARS['md_stock']))    {$md_stock = $HTTP_GET_VARS['md_stock'];}
  $md_supplier_mode = false;
  if (!empty($HTTP_GET_VARS['md_supplier_mode']) && $HTTP_GET_VARS['md_supplier_mode']=='true')    {$md_supplier_mode = true;}

  if ($md_opm_id != "") {
    if ($md_del_mode == "yes") {
      $reload = delOrderProduct($md_opm_id, $md_c_id, $md_p_id, $md_ds, $md_price);
    }
    else {
      $reload = modOrderProduct($md_opm_id, $md_c_id, $md_p_id, $md_ds, $md_qty, $md_price, $md_stock, $md_supplier_mode);
    }
  }

  // on vérifie qu'il n'y a pas eu de freezing effectué par le manufacturer
/*
	if ($gaID>0) {
    $last_freezing_date = ordersGA_are_frozen($gaID, $order_date_to);
  } else {
    $last_freezing_date = orders_are_frozen("", $manufacturer_id, $order_date_to);
  }
*/
  ($gaID > 0) ? $group_id_global = 1 : $group_id_global = 0;
  $last_freezing_date = orders_are_frozen_global($group_id_global, $order_date_to, "", $manufacturer_id, $gaID);
	$has_frozen_orders = ($last_freezing_date != "");

  if ((!empty($HTTP_POST_VARS['action']))&&($HTTP_POST_VARS['action'] == "change_ga_ds")) {
    $nds = $HTTP_POST_VARS['next_date_shipped'];
    $nnds = $HTTP_POST_VARS['next_next_date_shipped'];
    $old_nds = $HTTP_POST_VARS['old_nds'];
    $old_nnds = $HTTP_POST_VARS['old_nnds'];
    if (($gaID>0)&&(($nds!=$old_nds)||($nnds!=$old_nnds))) {
      update_ga_nds($gaID, $nds, $nnds);
      $order_date_from = $nds;
      $order_date_to = $nds;
      $HTTP_POST_VARS['order_date_from'] = $nds;
      $HTTP_POST_VARS['order_date_to'] = $nds;
     	$reload = true;
    }
  }


  //on valide la livraison : ajout enreg dans manufactures_validation et modification du stock +
  //   et,en mode vente directe uniquement, toutes les commandes récurrentes qui étaient cochées (validation des commandes récurrentes)
//  if(($manufacturer_id)&&(($has_recurrences_to_valid)||(($freezeOrders)&&($gaID>0)))&&($is_week_mode)&&(!$customer_only)){
  if(($manufacturer_id)&&($has_recurrences_to_valid || $freezeOrders)&&($is_week_mode)&&(!$customer_only)){
    //on rajoute l'enreg dans la table
    $mydate = date("Y-m-d");
    $mydatetime = date("Y-m-d H:i:s");
    $order_date_to = $HTTP_GET_VARS["order_date_to"]; 

    // on freeze les commandes '(validation)'
    $has_frozen_orders = true;
    $orders_id_for_status_history = "";             

	  if (($freezeOrders)&&($gaID>0)) {
      //GROUPEMENT d'ACHAT : ajout de la validation dans mv (chiffre négatif)
			valid_orders($gaID, true, $mydatetime);
    } else {
      //VENTE DIRECTE
      if ($has_recurrences_to_valid) {
        //on récupère les infos nécessaires pour enregistrer la validation dans opm
        $op_id_list = "";
        for ($i = 0; $i < count($HTTP_POST_VARS["checkbox"]); $i++) {
          list($order_id, $op_id) = explode("|", $HTTP_POST_VARS["checkbox"][$i]);
          
          $orders_id_for_status_history .= "('".$order_id."','4','".$mydatetime."','0','validation du produit récurrent op_id = $op_id par le producteur " . addslashes_once($manufacturer_name) . " (id = " . $manufacturer_id . ")'),"; 
          $op_id_list .= "'".$op_id."',"; 
        }
        $op_id_list = substr($op_id_list, 0, -1); // on supprime la virgule !
    		$sql = "
            SELECT op.orders_products_id, op.orders_id, op.final_price, op.products_quantity, op.orders_id, op.next_date_shipped, op.shipping_day, op.shipping_frequency,
              op.products_id, op.products_name, opa.products_options, opa.products_options_values, o.customers_id, op.group_id 
    			  FROM orders_products as op 
    			  LEFT JOIN orders AS o ON op.orders_id = o.orders_id
    			  LEFT JOIN orders_products_attributes AS opa ON op.orders_products_id = opa.orders_products_id
            WHERE op.orders_products_id IN (" . $op_id_list . ") AND (op.manufacturers_id = '".$manufacturer_id."');";
    		$query = tep_db_query($sql);
    		while ($record = tep_db_fetch_array($query)) {
          // on ajoute l'enreg dans opm 
          //     PAS BESOIN DE SPECIFIER ***customers_ga_id*** CAR IL EST FROCEMENT = à 0 car commande récurrente
          //     PAS BESOIN DE SPECIFIER ***group_id*** CAR IL EST FROCEMENT = à 0 car commande récurrente
          $sql_add = "INSERT INTO orders_products_modifications (
            orders_products_modifications_datetime, date_shipped, orders_products_id, orders_id, customers_id, is_recurrence_order, 
            manufacturers_id, products_id, products_name, products_options, products_options_values, final_price, products_quantity, group_id) 
            VALUES
            ('".$mydatetime."','".$order_date_to."',".$record['orders_products_id'].",".$record['orders_id'].",".$record['customers_id'].",1,".$manufacturer_id.",
            ".$record['products_id'].",'".addslashes_once($record['products_name'])."','".addslashes_once($record['products_options'])."','".addslashes_once($record['products_options_values'])."',
            ".$record['final_price'].",".$record['products_quantity'].",".$record['group_id'].");"; //, freezing_datetime     ,'".$myDate."');";
          tep_db_query($sql_add, 'db_link');
    //      echo $op_id;
    
          //mise à jour de la next_date_shipped
          $nds = get_order_date_arg($record['next_date_shipped'], "", $record['products_id'], $record['shipping_day'], $record['shipping_frequency']);
          $sql_update_nds = "UPDATE " . TABLE_ORDERS_PRODUCTS . " SET next_date_shipped = '$nds' WHERE orders_products_id = ".(int)$record['orders_products_id'].";";
          tep_db_query($sql_update_nds, 'db_link'); // enregistrement de la nouvelle valeur dans op
    		}
      }
  		
      //ajout de la validation dans mv
			valid_orders($manufacturer_id, false, $mydatetime);
    }
    
		// mise à jour du stock pour chaque opm
		$sql_o = "SELECT opm.orders_id, opm.customers_ga_id, opm.group_id, opm.products_quantity, opm.products_id, o.orders_status, opm.orders_products_modifications_id FROM orders_products_modifications AS opm 
      LEFT JOIN orders AS o ON opm.orders_id = o.orders_id
      WHERE opm.facturate = 'Y' AND opm.date_shipped = '$order_date_to';";
		$query_o = tep_db_query($sql_o);
		while ($record_o = tep_db_fetch_array($query_o)) {
      if (($freezeOrders)&&($gaID>0)&&($record_o['group_id']>0)&&($record_o['customers_ga_id']==$gaID)) {
        // on ajoute l'info de pre-historique de commande uniquement pour un grpt d'achat 
        // dans ce cas, $orders_id_for_status_history est bien à BLANC 
        $orders_id_for_status_history .= "('".$record_o['orders_id']."','".$record_o['orders_status']."','".$mydatetime."','0','validation du produit \'".tep_db_input(tep_get_products_name($record_o['products_id']))."\' (opm_id = ".$record_o['orders_products_modifications_id'].") par le producteur \'" . tep_db_input($manufacturer_name) . "\' (id = " . $manufacturer_id . ")'),"; 
      }
      
  		// mise à jour effective du stock
      tep_update_stock($record_o['products_id'], $record_o['products_quantity']);
		}

    if ($orders_id_for_status_history != "") {
      // au moins une commande impactée
      $orders_id_for_status_history = substr($orders_id_for_status_history, 0, -1); // on supprime la virgule !
      // enregistrement de l'historique de la validation
      $sql_add = "INSERT INTO " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments) VALUES ".$orders_id_for_status_history.";";
      tep_db_query($sql_add, 'db_link');
    }

    // on reload la page
//    exit;
    $reload = true;          
  } // fin du "if has_recurrences_to_valid"

  // VALIDATION DE LA FACTURATION DES COMMANDES FOURNISSEUR
  if ((!empty($HTTP_POST_VARS['action']))&&($HTTP_POST_VARS['action'] == "validate_supplier_order")&&($gaID>0)&&($is_week_mode)&&(!$customer_only)) {
    $manufacturer_id_sav = $manufacturer_id;
    $manufacturer_id = $HTTP_POST_VARS['sv_m_id'];
    $sql = getMySQLraw("p", "", "", "", false, $adminMode);
    $man_cust_products_query = tep_db_query($sql);
		$ops_list = "";
    while ($man_cust_products = tep_db_fetch_array($man_cust_products_query)) {
      // génération du nom du produit
      $products_name = $man_cust_products['products_name'];
      if ($man_cust_products['products_reference'] != "") {
        $products_name .= " <i>(réf. : ".$man_cust_products['products_reference'].")</i>";
      }
			if ($man_cust_products['products_options_values'] != "") {$products_name .= " (" . $man_cust_products['products_options'] . " : " . $man_cust_products['products_options_values'] . ")"; }
      $products_name = addslashes_once($products_name);

      // récupération de la quantité à commander
      $qty_to_order = $HTTP_POST_VARS['qty_to_order'.$manufacturer_id."_".$man_cust_products['products_id']];      

		  if ($qty_to_order>0) {
        $ops_list .= "($manufacturer_id, '".$order_date_to."', 
          ".$man_cust_products['products_id'].", '$products_name', 
          ".$qty_to_order.", ".$man_cust_products['final_price'].", 
          ".$qty_to_order.", ".$man_cust_products['final_price']."),";    //new
      }
      
      // mise à jour du stock
      tep_update_stock($man_cust_products['products_id'], $man_cust_products['products_stock']+$qty_to_order, "", true);  // forçage de la valeur à $man_cust_products['products_stock']+$qty_to_order
		}

    // enregistrement des produits commandés au supplier
    if (strlen($ops_list) > 1) $ops_list = substr($ops_list, 0, -1);
//    echo $ops_list;exit;

    $sql_add = "INSERT INTO orders_products_suppliers (manufacturers_id, date_shipped, products_id, products_name, products_quantity, final_price, new_products_quantity, new_final_price) VALUES ".$ops_list.";";
    tep_db_query($sql_add, 'db_link');

    // enregistrement de l'info dans manufacturers_validations
    $mydatetime = date("Y-m-d H:i:s");
    $sql_add = "INSERT INTO manufacturers_validations (manufacturers_id, date_shipped, validation_datetime, supplier_order_datetime) VALUES ($manufacturer_id, '".$order_date_to."', '".$mydatetime."', '".$mydatetime."');";
    tep_db_query($sql_add, 'db_link');

    // on reload la page
    $manufacturer_id = $manufacturer_id_sav;
    $reload = true;          
  }

  // VALIDATION DE LA RECEPTION DES COMMANDES FOURNISSEUR
  if ((!empty($HTTP_POST_VARS['action']))&&($HTTP_POST_VARS['action'] == "validate_supplier_order_received")&&($gaID>0)&&($is_week_mode)&&(!$customer_only)) {
    $manufacturer_id_sav = $manufacturer_id;
    $manufacturer_id = $HTTP_POST_VARS['sv_m_id'];

    // mise à jour de la table
    $sql_add = "UPDATE orders_products_suppliers SET supplier_order_received = 1 WHERE manufacturers_id = $manufacturer_id AND date_shipped = '".$order_date_to."';";
    tep_db_query($sql_add, 'db_link');

    // on reload la page
    $manufacturer_id = $manufacturer_id_sav;
    $reload = true;          
  }

  // DESACTIVER LA VALIDATION DE LA RECEPTION FOURNISSEUR
  if ((!empty($HTTP_GET_VARS['remove_manufacturer_validation']))&&($HTTP_GET_VARS['remove_manufacturer_validation'] == "true")&&($gaID>0)&&($is_week_mode)&&(!$customer_only)) {
    $rmv_m_id = $HTTP_GET_VARS['rmv_m_id'];

    $sql_rmv = "SELECT products_id, products_quantity FROM orders_products_suppliers WHERE manufacturers_id = ".$rmv_m_id." AND date_shipped = '".$order_date_to."';";
		$query_rmv = tep_db_query($sql_rmv);
		while ($record_rmv = tep_db_fetch_array($query_rmv)) {
		  // mise à jour du stock  
//      echo $record_rmv['products_id'].$record_rmv['products_quantity']."<br>";
      tep_update_stock($record_rmv['products_id'], $record_rmv['products_quantity']);
		}
    
    $sql_add = "DELETE FROM orders_products_suppliers WHERE manufacturers_id = ".$rmv_m_id." AND date_shipped = '".$order_date_to."';";
    tep_db_query($sql_add, 'db_link');

    // enregistrement de l'info dans manufacturers_validations
    $sql_add = "DELETE FROM manufacturers_validations WHERE manufacturers_id = ".$rmv_m_id." AND date_shipped = '".$order_date_to."' AND supplier_order_datetime <> '' AND supplier_order_datetime IS NOT NULL;";
    tep_db_query($sql_add, 'db_link');

    // on reload la page
    $reload = true;          
  }

  // DESACTIVER LES VALIDATIONS des commandes (et des RECURRENCES en mode RVD)
  if ((!empty($HTTP_GET_VARS['remove_validation']))&&($HTTP_GET_VARS['remove_validation'] == "true")&&(($manufacturer_id>0)||($gaID>0))&&($is_week_mode)&&(!$customer_only)) {
    // GROUPEMENT d'ACHAT :
    //     on remet tous les stocks à leur état "post-étape 1" (stocks négatifs), pour tous les manufacturers
    if ($gaID>0) {
      $sql_rmv = "SELECT products_id, products_quantity FROM orders_products_suppliers WHERE date_shipped = '".$order_date_to."';";
  		$query_rmv = tep_db_query($sql_rmv);
  		while ($record_rmv = tep_db_fetch_array($query_rmv)) {
  		  // mise à jour du stock  
        tep_update_stock($record_rmv['products_id'], $record_rmv['products_quantity']);
  		}
      
      $sql_add = "DELETE FROM orders_products_suppliers WHERE date_shipped = '".$order_date_to."';";
      tep_db_query($sql_add, 'db_link');
    }
    
    // on récupère toutes les commandes récurrentes validées
    // -----------------
    $sql = "SELECT opm.manufacturers_id, opm.is_recurrence_order, opm.customers_ga_id, opm.orders_products_id, o.orders_status, opm.orders_products_modifications_id, opm.orders_id, opm.products_id, opm.products_quantity, m.manufacturers_name FROM orders_products_modifications as opm
      LEFT JOIN manufacturers AS m ON opm.manufacturers_id = m.manufacturers_id  
      LEFT JOIN orders AS o ON opm.orders_id = o.orders_id 
      WHERE opm.facturate = 'Y' AND opm.date_shipped = '$order_date_to'";  
      
    if ($gaID<=0) {
      $sql .= " AND opm.manufacturers_id = $manufacturer_id AND opm.group_id = 0 AND opm.is_recurrence_order > 0";
    } else {
      $sql .= " AND opm.group_id > 0 AND opm.customers_ga_id = $gaID";
    }
  	$query = tep_db_query($sql, 'db_link');
  
  	$values = "";                                                                                                                        
  	$ori_list = "";
  	$op_id_list = "";
  	$m_id_list = "";
    $today = date('Y-m-d H:i:s');
    ($gaID>0) ? $recur = "" : $recur = " récurrente";
    while ($record = tep_db_fetch_array($query)) {

      //mise à jour du stock (on remet le produit en stock), et ce : pour toutes les commandes
      tep_update_stock($record['products_id'], -$record['products_quantity']);

      if ($gaID>0) {
    		$m_id_list .= $record['manufacturers_id'].",";
    	}

  		$ori_list .= $record['orders_products_modifications_id'].",";
  		$op_id_list .= $record['orders_products_id'].",";
      $values .= "(".$record['orders_id'].",".$record['orders_status'].",'".$today."',0,'dé-validation de la commande".$recur." par le producteur ".addslashes_once($record['manufacturers_name']). " (id = ".$manufacturer_id.")'),";
  	}
		$m_id_list = substr($m_id_list, 0, -1); // on supprime la virgule !
  	
    if ($gaID <= 0) $m_id_list = $manufacturer_id;
    
    if ($m_id_list) {
      tep_db_query("DELETE FROM manufacturers_validations WHERE manufacturers_id IN (".$m_id_list.") AND date_shipped = '$order_date_to';", 'db_link');
    }

  	if ($ori_list) {
  		$values = substr($values, 0, -1); // on supprime la virgule !
  		$ori_list = substr($ori_list, 0, -1); // on supprime la virgule !
  		
      // on ajout les historiques !
  		tep_db_query("INSERT INTO orders_status_history (orders_id,orders_status_id,date_added,customer_notified,comments) 
        VALUES ".$values.";", 'db_link');
        
      // suppression effective des validations
      if ($gaID<=0) {
    		tep_db_query("DELETE FROM orders_products_modifications WHERE orders_products_modifications_id IN (".$ori_list.");", 'db_link');
      } 
    }
    
    if ($gaID<=0) {
      // VENTE DIRECTE UNIQUEMENT : on revient en arrière sur le next_date_shipped dans op
    	if ($op_id_list) {
    		$op_id_list = substr($op_id_list, 0, -1); // on supprime la virgule !
	        $sql = "SELECT orders_products_id, next_date_shipped, shipping_day, shipping_frequency FROM orders_products WHERE orders_products_id IN ($op_id_list);";
	      	$query = tep_db_query($sql, 'db_link');
	        while ($record = tep_db_fetch_array($query)) {
	          $nds = $record['next_date_shipped'];
	          $sd = $record['shipping_day'];
	          $sf = $record['shipping_frequency'];
	          $nds = get_order_date_arg($nds, "", "", $sd, $sf, "-"); // on recule temporellement
	  
	          $sql_update_nds = "UPDATE " . TABLE_ORDERS_PRODUCTS . " SET next_date_shipped = '$nds' WHERE orders_products_id = ".(int)$record['orders_products_id'].";";
//	          echo $sql_update_nds;exit;
	          tep_db_query($sql_update_nds, 'db_link'); // enregistrement de la nouvelle valeur dans op
	        }
    	}
    } else {
      // GROUPEMENT D'ACHAT
      // suppression effective des validations
  		tep_db_query("DELETE FROM manufacturers_validations WHERE manufacturers_id = '".-$gaID."' AND date_shipped = '$order_date_to';", 'db_link');
    }
    $reload = true;
  }

  if ((!empty($HTTP_GET_VARS['oups']))&&($HTTP_GET_VARS['oups'] == "true")) {
    set_time_limit(0);

		$query = tep_db_query("SELECT DISTINCT p.products_quantity AS products_stock, p.products_id, sum( opm.products_quantity ) AS sum_pq
FROM orders_products_modifications AS opm
LEFT JOIN orders_products AS op ON opm.orders_products_id = op.orders_products_id
LEFT JOIN products AS p ON opm.products_id = p.products_id
LEFT JOIN orders AS o ON opm.orders_id = o.orders_id
LEFT JOIN manufacturers AS m ON p.manufacturers_id = m.manufacturers_id
LEFT JOIN customers AS c ON c.customers_id = o.customers_id
WHERE (
opm.facturate = 'Y'
AND (
(
opm.group_id =0
)
OR (
(
opm.group_id >0
)
AND (
o.orders_status =1
)
)
)
AND opm.date_shipped
BETWEEN '2010-07-03'
AND '2010-07-03'
)
AND m.group_id =1
AND opm.customers_ga_id =1
AND opm.customers_id >0
AND p.manufacturers_id
IN ( 16, 23 ) 
GROUP BY m.manufacturers_name, opm.products_name, opm.products_options, opm.products_options_values
ORDER BY m.manufacturers_name, opm.products_name, opm.products_options, opm.products_options_values
LIMIT 0 , 100");

    while ($record = tep_db_fetch_array($query)) {
  		tep_db_query("UPDATE products SET products_quantity = ". -$record['sum_pq']." WHERE products_id = ". $record['products_id'].";");
    }

    set_time_limit(30);
  }                                                  

  if ($reload) reloadSMS();

  if ($customer_id == "") {
    $customer_id = "all_by_once";
  }
  
  
  
?>
<!DOCTYPE html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <? echo HTML_PARAMS; ?>>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=<? echo CHARSET; ?>">
    <title><? echo TITLE; ?></title>
    <link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
    <script language="javascript" src="includes/general.js"></script>
    <script language="javascript">
      function changeClient() {
        var objBtn = document.getElementById('submit_addproduct');
        var objClient = document.getElementById('customers_addproduct');
        if ((objBtn)&&(objClient)) {
          objBtn.disabled = (objClient.value == "");
        }
      }
    
      function changeProduct() {
        // nothing
      }
    
      function showhideMod(id) {
        var stock_obj = document.getElementById("product_stock" + id);
        var qty_obj = document.getElementById("product_quantity" + id);
        var price_obj = document.getElementById("product_price" + id);

        showhide('mod'+id);

        if (stock_obj && (stock_obj.type == "text")) {stock_obj.disabled=false;}
        if (qty_obj && (qty_obj.type == "text")) {qty_obj.disabled=false;}
        if (price_obj && (price_obj.type == "text")) {price_obj.disabled=false;}
        
        if (stock_obj && (stock_obj.type == "text")) { stock_obj.focus(); stock_obj.select();}
        else if (qty_obj && (qty_obj.type == "text")) { qty_obj.focus(); qty_obj.select();}
        else if (price_obj && (price_obj.type == "text")) { price_obj.focus(); price_obj.select();}
        
      }

      function disableEdit(obj_name) {
        var obj = document.getElementById(obj_name);
        if (obj && (obj.type == "text")) { obj.disabled=true; }
      }

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

      function getModDelUrl(id) {
        c_id = document.getElementById("c_id" + id).value;
        p_id = document.getElementById("p_id" + id).value;
        ds = document.getElementById("ds" + id).value;
        var price_get_var = "";
        var qty_get_var = "";
        var stock_obj = document.getElementById("product_stock" + id);
        var qty_obj = document.getElementById("product_quantity" + id);
        var price_obj = document.getElementById("product_price" + id);
        if (qty_obj)   { qty_get_var   = '&md_qty=' + qty_obj.value;     }
        if (price_obj) { price_get_var = '&md_price=' + price_obj.value; }
        if (stock_obj) { stock_get_var = '&md_stock=' + stock_obj.value; }
				myUrl = '<?=tep_href_link(FILENAME_STATS_MANUFACTURERS, querystring_small(), 'NONSSL');?>&md_opm_id=' + id + '&md_c_id=' + c_id + '&md_p_id=' + p_id + '&md_ds=' + ds + price_get_var + qty_get_var + stock_get_var;
        return myUrl;
      }

      function delProduct(id) {
        if (confirm("La quantité pour ce produit va être définie à 0, voulez-vous continuer ?") == 1) {
          document.location.replace(getModDelUrl(id) + '&md_del_mode=yes');
        } 
      }
      function validMod(id) {
        document.location.replace(getModDelUrl(id) + "<? if ($customer_id == "all_by_man") { echo '&md_supplier_mode=true'; } ?>");
      }
      function addZero(iNb) {
        if (iNb < 10) {
          return "0"+iNb;
        }
        else { 
          return ""+iNb;
        }
      }
      function DateToString(date) {
        return addZero(date.getDate()) + "/" + addZero(date.getMonth()+1) + "/" + addZero(date.getFullYear());
      }

      function changeWeek(sd) {
        curDate = sd.options[sd.selectedIndex].value;
        document.date_range.order_date_from.value = curDate;  
        document.date_range.order_date_to.value = curDate;
        document.date_range.submit();  
      }
      
      function changeMonth(fm) {
        curDate = fm.options[fm.selectedIndex].value.split("|");
        document.date_range.order_date_from.value = curDate[0];  
        document.date_range.order_date_to.value = curDate[1];
        document.date_range.submit();  
      }
      
      function onLoad() {
        //SetFocus();
      }
      
    </script>
  </head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF" onLoad="onLoad();">
<div id="spiffycalendar" class="text"></div>

<? 
$td_page_heading = "<td class='pageHeading' valign='middle'>";
if ($print) {
  $td_page_heading .= "</td><td>".tep_image('../images/logoAVEC17.gif', HEADING_TITLE, 170, 93)."</td>";
  $td_page_heading .= "<td valign='middle' class='pageHeading' align='left'><big>".STORE_NAME . "</big><br>";
}

if ($gaID <= 0) {
  if ($m_id_from_array == "") {
    if ($manufacturer_id==-1) {
      $td_page_heading .= "Vente directe"; 
  //    $td_page_heading .= HEADING_TITLE; 
    }
    else if ($manufacturer_id=="") {
      $td_page_heading .= "Liste des producteurs"; 
    }
    else {
      $td_page_heading .= "Producteur de la vente directe : " . $manufacturer_name ." <small><i>(n° ".$manufacturer_id . ")</i></small>";
    }
  } else {
    $td_page_heading .= "Producteur de la vente directe : " . tep_manufacturers_name($m_id_from_array) ." <small><i>(n° ".$m_id_from_array . ")</i></small>";
  }
} else {
  if ((!$print)||(!$customer_only)) $td_page_heading .= "Groupement d'achat - " . $gaName; 
  if ($m_id_from_array == "") {
    if ($manufacturer_id>0) {
      if ((!$print)||(!$customer_only))  $td_page_heading .= " : "; 
      $td_page_heading .= $manufacturer_name ." <small><i>(n° ".$manufacturer_id . ")</i></small>"; 
    }
  } else {
    if ((!$print)||(!$customer_only))  $td_page_heading .= " : "; 
    $td_page_heading .= tep_manufacturers_name($m_id_from_array) ." <small><i>(n° ".$m_id_from_array . ")</i></small>"; 
  }
}

$td_page_heading .= "</td>";

if(!$print) {
// =======================================================================================================================================================================    
// TABLES : NOT IN PRINTING MODE
// =======================================================================================================================================================================    

// header //-->
require($admin_FS_path . DIR_WS_INCLUDES . 'header.php');
// header_eof //-->
}?>

<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
    <?if ((!$print)&&((empty($m_id_filter))||($m_id_filter<=0))) {?>
    <td width="<? echo BOX_WIDTH; ?>" valign="top">
      <table border="0" width="<? echo BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
        <!-- left_navigation //-->
        <? require($admin_FS_path . DIR_WS_INCLUDES . 'column_left.php'); ?>
        <!-- left_navigation_eof //-->
      </table>
    </td>
    <?}
    
    
    
    ?>

    <td width="100%" valign="top">
      <table border="0" width="100%" cellspacing="0" cellpadding="0">
        <tr>
          <td>
            <table border="0" width="100%" cellspacing="0" cellpadding="0">
              <tr valign="middle">
                <? echo $td_page_heading;?> 
                <td class="pageHeading" align="right">
                    <? echo tep_draw_separator('pixel_trans.gif', 
                                HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?>
                </td>
                <td class="smallTextNoBorder" align="right">
    <?if ($print) {
                  echo "<big><b>Facture imprimée le : </b><br>".strftime(DATE_TIME_FORMAT)."</big>";
    }?>
                </td>
    
    
              </tr>
            </table>
          </td>
        </tr>

        <tr>
          <td>
            <? 
            if (($manufacturer_id)&&(!$print)) {
              echo tep_draw_form('date_range', FILENAME_STATS_MANUFACTURERS, querystring_small(), 'get'); 
              echo tep_draw_hidden_field('order_date_from', "");
              echo tep_draw_hidden_field('order_date_to', "");
              echo tep_draw_hidden_field('mID', "");
              echo tep_draw_hidden_field('mName', "");
              echo tep_draw_hidden_field('gaID', "");
              echo tep_draw_hidden_field('gaName', "");
              echo tep_draw_hidden_field('cID', "");
              echo tep_draw_hidden_field('cName', "");
            
            ?> 
            <!-- date range table -->
            <table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="main">
                <td align="left">

                    <!-- period selection table -->
                    <table border="0" width="100%" cellspacing="0" cellpadding="2">
                        <?php 

                        echo "<tr class='dataTableRow'>
                              <td colspan='5' height='30' valign='top' class='pageHeadingSmall'>
                                Choix des dates de <u>livraison</u>$t_txt 
                              </td>
                              </tr>
                              <tr class='dataTableRow'>
                              <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
                              <td nowrap align='center'$wClass>
                                Jour (livraison) : ".tep_draw_pull_down_menu('shipping_dates', $shipping_dates_array, $order_date_to, 'id="shipping_dates" onchange="javascript:changeWeek(this);"')."
                              </td>";
                              
                      if ($gaID<=0) {
                        echo "<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<i>ou</i>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
                              <td align='center'$mClass>
                                Mois (facturation) : ".tep_draw_pull_down_menu('facturation_months', $facturation_months_array, date("Y-m-d", $cur_month), 'id="facturation_months" onchange="javascript:changeMonth(this);"');
                      } else {
                        echo "<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
                              <td width='100%'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
                      }  
                        echo "
                              </td>
                              <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
                              
?>
                        <script language="javascript">
                          <? if ($is_month_mode) {?>
                          // on sélectionne le mois
                          var sdObj = document.getElementById("shipping_dates");
                          var fmObj = document.getElementById("facturation_months");
                          var idx = fmObj.length-1;
                          if (<?=boolString($is_week_mode);?>) {
                            fmObj.selectedIndex = idx;
                            sdObj.focus();
                          } else {
                            for (i=0;i<=idx;i++) {
                              if (fmObj.options[i].value == "<?=$order_date_from."|".$order_date_to?>") {
                                idx = i;
                                break;
                              }
                            }
                            fmObj.selectedIndex = idx;
                            fmObj.focus();
                          }
                          <?}?>
                        </script>
<?
/*
                        echo "&nbsp;&nbsp;&nbsp;";
    
    
    						if (($periodName!=$periodNames[0])||($periodName==$periodNames[0])&&(!$disable_current_week)) {
                        echo '<input type="submit" value="'.ENTRY_SUBMIT.'">';
    						} 
    						echo '
                        </center>
                        </div>
                        </td>';
*/
?>
                      </tr>
                    </table>
                    <!-- endperiod selection table -->

                </td>
              </tr>
            </table>
            <!-- end date range table -->
          </form>
<? } // end if not empty_mID && !$print?>
          </td>
        </tr>
<?

if ($manufacturer_id) {

 /* if ((!$print)||(($print)&&($customer_id!='all_by_man'))) {*/
 
  if ((!$print)||(($print)&&(!$customer_only))) {
    ?>
		<tr><td>&nbsp;</td></tr>
		<tr height="30px">
        <td class="messageStackSuccessBig" align="center">
		    <?
          if ($is_week_mode) {
            echo "Date de livraison sélectionnée : <big>".getFormattedLongDate($order_date_to, true)."</big>";
          } else {
            if ($periodName) {echo "" . $periodName . " : </big>";}
            echo "<big>" . getFormattedLongDate($order_date_from, true) .  " - " . getFormattedLongDate($order_date_to, true). "</big>";
          }
        ?>
        </td>
    </tr>
  <?
  }

  if (($is_week_mode)&&(!$print)&&($gaID>0)) {
    // création de l'array : next_date_shipped
    generateNDSarray($gaID, $nds, $nnds);
  
    echo "<tr><td align='center' class='messageBox'>".tep_draw_form('ga_ds', FILENAME_STATS_MANUFACTURERS, querystring_small(), 'post');
    echo '  <input type="hidden" name="action" value="change_ga_ds">
            <input type="hidden" name="old_nds" value="'.$nds.'">
            <input type="hidden" name="old_nnds" value="'.$nnds.'">';
    echo "Date de la livraison programmée : ".tep_draw_pull_down_menu('next_date_shipped', $next_date_shipped_array, $nds, 'onchange="this.form.submit()"');
    echo "<br>Date de la livraison <b>suivante</b> programmée : ".tep_draw_pull_down_menu('next_next_date_shipped', $next_date_shipped_array, $nnds, 'onchange="this.form.submit()"');
    echo "</form></td></tr>";
  }
}
?>
   				<tr><td>&nbsp;</td></tr>
        <tr>
          <td>

  <table border="0" width="100%" cellspacing="0" cellpadding="3">

    
      <? 
      
      if (!$manufacturer_id) { 
      // =======================================================================================================================================================================    
      // mID == "" => list all the ordered products by manufacturers
      // =======================================================================================================================================================================    
      ?>
      <tr>    
      <td valign="top">
      <table border="0" width="100%" cellspacing="0" cellpadding="1">
        <?
        function addTR($params) {
          global $print;
          
          if(!$print) {
              $txt = '<tr class="dataTableRow" onMouseOver="rowOverEffect(this)" onMouseOut="rowOutEffect(this)" onClick="document.location.href=\''.tep_href_link(FILENAME_STATS_MANUFACTURERS, $params, 'NONSSL').'\'">';
          } else { // printing mode
              $txt = '<tr class="dataTableRow">';
          }
          return $txt;
        }

        $sql = "SELECT m.manufacturers_id, m.manufacturers_name, m.group_id, 
                        opm.customers_ga_id, cga.customers_ga_name, mg.group_name 
          FROM orders_products_modifications AS opm 
          LEFT JOIN customers_ga AS cga ON cga.customers_ga_id = opm.customers_ga_id
          LEFT JOIN manufacturers AS m ON m.manufacturers_id = opm.manufacturers_id
          LEFT JOIN manufacturers_groups AS mg ON mg.group_id = m.group_id
          WHERE 
              ((m.group_id = 0) OR 
              (m.group_id = 1)) AND facturate = 'Y' 
          GROUP BY m.group_id, opm.customers_ga_id, m.manufacturers_id
          ORDER BY m.group_id, opm.customers_ga_id, m.manufacturers_name"; 

/* 2010-06-10    by CJ
//              (m.group_id = 1 AND opm.customers_ga_id > 0)) AND facturate = 'Y' 
*/

        $manufacturers_query = tep_db_query($sql);
  			$i = 0;
  			$table = "";
  			$cur_group_id = "";
  			$total_group_qty = 0;
  			$total_group_price = 0;
  			while ($manufacturers = tep_db_fetch_array($manufacturers_query)) {	
   			  $i += 1;
					$url_param = "";
					
/* 2010-06-10    by CJ
					$gaID = $manufacturers['customers_ga_id'];
          if ($gaID == "*") {
            $gaID = DEFAULT_GA_ID; // par défaut, si TOUS => on choisit 1 
          } else if (($gaID <= 0)||($manufacturers['group_id'] <= 0)) {
            $gaID = 0;
          }
*/

          $gaID = getGA_ID($manufacturers['group_id'], $manufacturers['customers_ga_id'], true);
          if ($gaID > 0) {
            $ga_name = " - ";
            if ($manufacturers['customers_ga_name'] == "") {
//              echo "a".$manufacturers['customers_ga_id']."a";
              $ga_name .= tep_ga_name($gaID);
            } else {
              $ga_name .= $manufacturers['customers_ga_name'];
            }
            $ga_name_url = urlencode($manufacturers['customers_ga_name']);
          } else {
            $ga_name = "Vente directe";
            $ga_name_url = "";
          }

					$mgn = $manufacturers['group_id'].$gaID;
          if ($mgn != $cur_group_id) {
            // on ajoute un nouveau groupe : Vente directe, groupement d'achat n°1, grpt n°2...
            $cur_group_id = $mgn;
            
            if(!$print) {
              $url_param = querystring_small("", "", false) . '&mID=-1&gaID=' . $gaID . '&gaName=' . $ga_name_url;
            }
            $table .= addTR($url_param);
            $table .= '<td class="dataTableContentGroup" width="100%" colspan="1">'.$manufacturers['group_name'].$ga_name.'&nbsp;</td></tr>';
          }
					$url_param = "";
          if(!$print) {
            $url_param = 'cID=all_by_once&gaID=' . $gaID . '&gaName=' . $ga_name_url . '&' . querystring_small($manufacturers['manufacturers_id'], $manufacturers['manufacturers_name'], false);
          }
          $table .= addTR($url_param);
          $table .= '<td class="dataTableContentFab" width="100%">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$manufacturers['manufacturers_name'].'&nbsp;</td></tr>';
  			}
        echo $table;
        ?>
      </table>
    <?
	} else {
    // =======================================================================================================================================================================    
    // mID != "" => list all the customers for this manufacturer_id
    // =======================================================================================================================================================================
      if (!$customer_only) {

      if ($customer_id == "all") { 
      ?>

      <tr>    
      <td valign="top">
      <table border="0" width="100%" cellspacing="1" cellpadding="2" style="BORDER: 2px solid;">
        <tr class="dataTableHeadingRow">
          <td class="dataTableHeadingContent" align="center" nowrap width="100%"><? echo TABLE_CUSTOMER_NAME; ?></td>
          <td class="dataTableHeadingContent" align="center" nowrap><? echo TABLE_ORDER_PURCHASED; ?></td>
          <td class="dataTableHeadingContent" align="center" nowrap><? echo TABLE_TOTAL_PRODUCTS; ?></td>
          <td class="dataTableHeadingContent" align="center" nowrap><? echo TABLE_PRODUCT_REVENUE; ?></td>
        </tr>
        <?			  
  			$total_sales = 0;
  			$total_quantity = 0;	
  			$man_customers_query = tep_db_query(getMySQLraw("c"));
  			$i = 0;
  			while ($man_cust_products = tep_db_fetch_array($man_customers_query)) {
          $i += 1;	
          $products_quantity = $man_cust_products['sum_pq'];
					$final_price = $products_quantity * $man_cust_products['final_price'];
          if (!$print) {
            $url_param = querystring_small() . "cName=".$man_cust_products['customers_name']."&cID=".$man_cust_products['customers_id'];
          ?>
          
                <tr class="dataTableRow" onMouseOver="rowOverEffect(this)" onMouseOut="rowOutEffect(this)" onClick="document.location.href='<? echo tep_href_link(FILENAME_STATS_MANUFACTURERS, $url_param, 'NONSSL'); ?>'">
                
          <?
          } else {?>
                <tr class="dataTableRow">
          <?
          }
          ?>
          <td class="dataTableContent" width="100%"><? echo $man_cust_products['customers_name']; ?></td>
          <td class="dataTableContent" width="100%" align="center">
            <?echo getShortDate($man_cust_products['dp'], $man_cust_products['recurrence_accepted_datetime']);?></td>
          <td class="dataTableContent" align="right">&nbsp;<? echo tep_format_qty_for_html($products_quantity); ?>&nbsp;</td>
          <td class="dataTableContent" align="right">&nbsp;<? echo $currencies->format($final_price); ?></td>
        </tr><?
        
  				$total_quantity = $total_quantity + $products_quantity;
  				$total_sales = ($total_sales + $final_price);
  			} // end of while loop   

        ?>

        <tr class="dataTableRow">
          <td class="dataTableTotalRow" align="left">&nbsp;</td>
          <td class="dataTableTotalRow" align="right"><? echo ENTRY_TOTAL; ?>&nbsp;</td>
          <td class="dataTableTotalRow" align="right">&nbsp;<? echo tep_format_qty_for_html($total_quantity); ?>&nbsp;</td>
          <td class="dataTableTotalRow" align="right">&nbsp;<? echo $currencies->format($total_sales); ?></td>
        </tr>
      </table></td></tr>
      
      <?
      }
        // gestion du figeage des commandes
        // on affice une message comme quoi c'est figé le cas échéant
        if (($is_week_mode)&&($has_frozen_orders)) {
          ($gaID > 0) ? $step_nb = "Etape 1 : " : $step_nb = ""; 
          $freezing_table = '
            <tr><td class="pageHeadingSmall" align="center">'.$step_nb.'Commandes validées le <i>'.convertEnglishDateNames_fr(date('l j F Y à H:i:s', strtotime($last_freezing_date))).'</i></td></tr>
            <tr><td class="formAreaTitle" align="center">Les commandes sont maintenant validées : toute commande ultérieure sera prise en compte la livraison suivante.</td></tr>';
            
          if (!$print) {
            $freezing_table .= '
              <tr><td class="pageHeadingSmall" align="center" id="cancel_validation">
                <span class="messageStackErrorBig"><a href="'.tep_href_link(FILENAME_STATS_MANUFACTURERS, querystring_small().'&remove_validation=true', 'NONSSL').'">'.
                tep_image(DIR_WS_ICONS . 'cross.gif', '').'<big><b>Annuler la validation de la commande</b></big></a></span></td></tr>';
          }
              
          $freezing_table .= '
            <tr><td><hr></td></tr>';

          echo $freezing_table;
        }


        $freezing_table = "";
        $can_validate = can_validate($order_date_to, ($gaID>0));

        if ((!$print)&&($gaID<=0)&&($manufacturer_id>0)&&($can_validate)&&($is_week_mode)) {
//        if (((!$print)&&($preprod))||((!$print)&&($is_week_mode)&&(($forceValidation)||(!$forceValidation)&&(!$has_frozen_orders)))) {
          $recurrences_table = '
  	        <tr><td class="pageHeadingSmall" align="left" colspan="2">Liste des commandes récurrentes à valider</td></tr>
  	        <tr><td class="formAreaTitle" colspan="2">La validation des commandes doit se faire au plus tard le jour précédant la livraison.</td></tr>';
	        $recurrences_table .= tep_draw_form('valid_recurrences',FILENAME_STATS_MANUFACTURERS, querystring_small(), 'post', 'onsubmit="javascript:document.getElementById(\'submit_btn\').disabled=true;"').
	          '<table border="0" width="100%" cellspacing="1" cellpadding="2" style="BORDER: 2px solid;">';

          $recurrences_table .= '<tr class="dataTableRow">
	            <td class="dataTableTotalRow" align="center" nowrap>
                &nbsp;&nbsp;<a href="javascript:showhide(\'toggle_rec\');"><b>Afficher les récurrences</b></a>&nbsp&nbsp;<br>\/
		            <input type="hidden" name="freeze_orders" value="true">
    	          <input type="hidden" name="force_validation" value="'; if($forceValidation){$recurrences_table .= "true";} 
	        $recurrences_table .= '"></td>
	            <td class="dataTableTotalRow" align="left" nowrap width="100%">&nbsp;&nbsp;&nbsp;&nbsp;
                <input type="submit" id="submit_btn" value="Valider les récurrences et les commandes" style="height: 35px; font-weight: bold; font-size : 18px; ">  
	            </td>
            </tr>
            <tr>
              <td  width="100%" colspan="2">
                <div id="toggle_rec" style="display:none;">
                  <table border="0" width="100%" cellspacing="0" cellpadding="0">
                    <tr>
                      <td class="dataTableTotalRow"  align="left"  nowrap>
                        <input type="checkbox" id="checkbox_all" onclick="javascript:checkAll();" /><a href="javascript:checkAllLink();"><b>Tout cocher/décocher</b></a>
                      </td>
                      <td class="dataTableTotalRow" width="100%">&nbsp;</td>
                    </tr>
                  </table>
                  <table border="0" width="100%" cellspacing="0" cellpadding="0">';
	        $recurrences_table .= $submit_btn;

	  			$rec_nb = 0;
	        $sql = getMySQLraw("p", "", "", "", true);
	  			$recurrences_query = tep_db_query($sql);
	  			while ($recurrences = tep_db_fetch_array($recurrences_query)) {
            // on vérifie si la commande récurrente doit être validée cette semaine
            $sd = $recurrences['shipping_day']; 
            $sf = $recurrences['shipping_frequency']; 
//            $ds_ref = $recurrences['date_shipped']; // date_shipped est la date de référence : on n'affiche que les récurrences à partir de cette date
            $nds = $recurrences['next_date_shipped'];

            // 2 cas : 
            //    * $order_date_to est < $nds : il ne faut pas choisir la récurrence
            //    * $order_date_to est >= $nds : il faut pas choisir la récurrence
            
            $bAdd = false;

            if (strtotime($order_date_to) >= strtotime($nds)) { // ajouté le 9 aout 2010
              $bAdd = ( ($nds == $order_date_to) ||
                      ( ($nds != $order_date_to) && ($sf <= 1.0) )); // si $sf <= 1.0, la requête est forcément sélectionnable (shipping_day LIKE '%<tuesday_ou_thursday>%')
              if (!$bAdd) { // si on est là, c'est que $order_date_to > $nds (strictement)
                $bAdd = (getNextValidNDS($nds, $manufacturer_id, "", $sd, $sf) != "");
              }
            }

            if ($bAdd) {
	    			  // traitement de cette commande récurrente
	    			  $rec_nb += 1;
	            $recurrences_table .= '
	            	<tr class="dataTableRow">
	              	<td class="dataTableContent" width="100%"><input type="checkbox" id="checkbox'.$rec_nb.'" name="checkbox[]" 
                    value="'. $recurrences['orders_id']."|".$recurrences['orders_products_id'].'" />'.$recurrences['customers_name'].' - '.tep_format_qty_for_html($recurrences['products_quantity']).' x '.$recurrences['products_name'].' (ID adhérent = '.$recurrences['customers_id'].', id cmde = '.$recurrences['orders_id'].', id op = '.$recurrences['orders_products_id'].')</td>
	              </tr>';
            }  
	        }
	        $recurrences_table .= '
                   </table>
                 </div>
               </td>
            </tr></table></form>
	        <tr><td><hr></td></tr><tr><td>&nbsp;</td></tr>';

	        if ($rec_nb>0) {
	          echo $recurrences_table;?>
	          <script>
	            function checkAllLink() {
	              document.getElementById("checkbox_all").checked = !document.getElementById("checkbox_all").checked;  
	              checkAll();
	            }

	            function checkAll() {
	              for (i=1;i<=<?=$rec_nb?>;i++) {
	                document.getElementById("checkbox" + i).checked = !document.getElementById("checkbox" + i).checked;
	              }
	            }
	            checkAllLink();
	          </script><?
	        } else {
  	        $add_validation_btn_only = true;
          }
	      } else { // si pas de récurrences potentielles à afficher pour validation
	        $add_validation_btn_only = $can_validate;
	        if ((!$can_validate)&&($is_week_mode)) { // on n'affiche le message qu'en mode $is_week_mode
	          ($gaID>0) ? $nb = GA_VALIDATION_LIMIT : $nb = RVD_VALIDATION_LIMIT;
	          echo '
		          <tr><td class="messageStackErrorBig" align="left">Vous n\'avez pas le droit de valider les commandes pour l\'instant.<br><big>La validation n\'est possible que <b>'.$nb.'</b> jours maxi avant la date de livraison.</big></td></tr>
		          <tr><td><br></td></tr>';
          }
	      }

        if ($add_validation_btn_only) {
			    if ($gaID>0) {
            $grp_txt = "Etape 1 : Valider la commande du groupement d'achat ".$gaName;
          } else {
            $grp_txt = "Valider/figer la commande";
          }
          if ((!$print)&&($can_validate)&&(!$has_frozen_orders)&&($is_week_mode)&&((($gaID<=0)&&($manufacturer_id>0))||($gaID>0))) {
	          $freezing_table = '';
  			    if ($gaID<=0) {
	          $freezing_table .= '
		          <tr><td class="messageStackWarningBig" align="left">Aucune commande récurrente trouvée pour cette date de livraison.</td></tr>
		          <tr><td><br></td></tr>';
  			    } 
	          $freezing_table .= '
		          <tr><td class="pageHeadingSmall" align="left">Figer les commandes</td></tr>
		          <tr><td class="formAreaTitle">Vous pouvez figer les commandes : toute commande ultérieure sera prise en compte à la prochaine livraison.</td></tr>';
	          $freezing_table .= tep_draw_form('freeze_form',FILENAME_STATS_MANUFACTURERS, querystring_small(), 'post');
	          $freezing_table .= '
	            <input type="hidden" name="freeze_orders" value="true">
	            <tr class="dataTableRow">
	              <td class="dataTableTotalRow" align="center">
                <input type="submit" id="submit_btn" value="'.$grp_txt.'" style="height: 35px; font-weight: bold; font-size : 18px; ">  
	              </td>
	            </tr>
	            </form>
	            <tr><td>&nbsp;</td></tr>';
		        echo $freezing_table;
		      }
	      } //end of (if $add_validation_btn_only)
      }//end of (if !$customer_only)

      if (!$do_not_redeclare_order) {
        require($admin_FS_path . DIR_WS_CLASSES . 'order.php');
      }
      if ((!$print)&&($is_week_mode)) {
        if ($gaID<=0) {
          echo addProduct($manufacturer_id, 0, -1, "", false, true); 
        } else {
          echo addProduct($manufacturer_id, 1, -1, "", false, true); 
        }
      }

      if ($customer_id != "") {

        $has_records = false;
        $select_mode = "";
        $table = "";
        $total_table = "";

        $txt = "Liste des produits";
        $class_all_by_once = "";
        $class_all_cust_by_prod = "";
        $class_all_by_man = "";

        if (($gaID>0)&&($customer_id == 'all_by_man')&&!$customer_only) { //&&($has_frozen_orders)
          $show_stock = 1; // on affiche toujours le stock restant  
        }

        switch ($customer_id) {
          case "all_by_once" : 
          case is_numeric($customer_id): 
            // all products by customers, for every buyer
      			$what = "c";
            $heading_txt = "Adhérents";
            if (!$customer_only) $txt .= " par adhérent";
            $class_all_by_once = ' class="messageStackSuccess"';
            break;
          case "all_cust_by_prod" : 
            // all customers by products, for every ordered product
      			$what = "cp";
            $heading_txt = "Produits";
            $txt = "Liste des adhérents par produit";
            $class_all_cust_by_prod = ' class="messageStackSuccess"';
            if ($bulk_only) {
              $class_all_cust_by_prod_bulk = ' class="messageStackSuccess"';
            }
            break;
          case "all_by_man" : 
            // all manufacturers by products, for every ordered product
      			$what = "f";
            $heading_txt = TABLE_HEADING_MANUFACTURERS_NAME."s";
            if (!$customer_only) $txt .= " par producteur";
            $class_all_by_man = ' class="messageStackSuccess"';
            break;
        }    
        if ($bulk_only) $txt .= ' (uniquement ceux en vrac)';


        if (($customer_id == "all_by_once")||($customer_id == "all_cust_by_prod")||($customer_id == "all_by_man")||((is_numeric($customer_id))&&($customer_only))) { 

          $table = '
            <tr><td class="pageHeadingSmall" align="left"><br>'.$txt.'</td></tr>';
          if ($gaID<=0) {
            // pas de commandes récurrentes avec le groupement d'achat
            $table .= '<tr><td align="left" height="15px">
                <table border="0" width="100%" cellspacing="0" cellpadding="0">
                  <tr><td class="smallTextNoBorder" nowrap><i>(les commandes récurrentes sont affichées sur&nbsp;</i></td><td class="smallTextNoBorderRec" nowrap><i><b>fond bleu</b></i></td><td width="100%" class="smallTextNoBorder">)</td></tr>
                </table></td></tr>';
          }
  
    			// initialisation of the arrays "$manufacturersIdArray" (dans getMySQLraw)...
    			$sql_array_raw = getMySQLraw($what, "", "", "", false, "yes", !$print);      // last param : get_arrays
          $man_customers_list_query = tep_db_query($sql_array_raw);

          if (($manufacturer_id)&&(!$print)) {                                          
            $select_mode = '<tr><td class="pageHeadingBG" align="center" height="30px" valign="middle">';
            $by = "";            
            if ($gaID<=0) {
              $select_mode .= 'Liste des commandes ponctuelles et des commandes récurrentes validées';
            } else {
              $select_mode .= 'Liste des commandes enregistrées pour le groupement d\'achat';
              if ($manufacturer_id < 1) {
                $by = " (maxi ".MAX_DISPLAY_SEARCH_RESULTS_ADMIN." par page)";            
              }
            }
            $select_mode .= "</td></tr><tr><td>";
  
            $select_mode .= "<br>".tep_draw_form('arrays_form', FILENAME_STATS_MANUFACTURERS, querystring_small("", "", true, "", false/* no page */, false/* no arrays*/) . "&action=arrays", 'post', '').
              "<table cellspacing='0' cellpadding='0'><tr><td nowrap colspan='3'>";
  
            if (($gaID > 0) || (($gaID <= 0) && ($manufacturer_id<1))) {
              $href = tep_href_link(FILENAME_STATS_MANUFACTURERS, querystring_small("", "", true, "all_by_man", false), 'NONSSL');
              if ($manufacturer_id > 0) {
                $mID_text = " (".tep_manufacturers_name($manufacturer_id).")";
              } else {
                $mID_text = "$by";
              }
              if ($customer_id != 'all_by_man') {
                $select_mode .= "<a$class_all_by_man href='".$href."'>Mode <b>fournisseur</b> $mID_text</a>";
              } else {
                $select_mode .= "<span$class_all_by_man>Mode <b>fournisseur</b> $mID_text</span>";
              }
              $select_mode .= "</td><td width='5px'></td><td><!MAN_ARRAY!></td><td width='30px'></td><td colspan='2'><a href='".$href."&print=yes' target='_blank'>Imprimer (mode <b>fournisseur</b>)</a></td></tr><tr><td nowrap colspan='3'>";
            }
            $href = tep_href_link(FILENAME_STATS_MANUFACTURERS, querystring_small("", "", true, "all_by_once", false), 'NONSSL');
            if ($customer_id != 'all_by_once') {
              $select_mode .= "<a$class_all_by_once href='".$href."'>Mode adhérent $by</a>";
            } else {
              $select_mode .= "<span$class_all_by_once>Mode adhérent $by</span>";
            }
            $select_mode .= "</td><td width='5px'></td><td><!CUST_ARRAY!></td><td width='30px'></td><td colspan='2'><a class='main' href='".$href."&print=yes' target='_blank'>Imprimer (mode adhérent)</a></td></tr><tr><td nowrap>";
            $href = tep_href_link(FILENAME_STATS_MANUFACTURERS, querystring_small("", "", true, "all_cust_by_prod", false), 'NONSSL');
            if (($customer_id != 'all_cust_by_prod')||($bulk_only)) {
              $select_mode .= "<a$class_all_cust_by_prod href='".$href."'>Mode produit</a>";
            } else {
              $select_mode .= "<span$class_all_cust_by_prod>Mode produit</span>";
            }
            $select_mode .= "</td><td width='5px'></td><td nowrap>";
            if ($gaID>0) {
              if (!$bulk_only) {
                $select_mode .= "<a$class_all_cust_by_prod_bulk href='".$href."&bulk_only=yes'>(le <b>vrac</b> uniquement)</a>";
              } else {
                $select_mode .= "<span$class_all_cust_by_prod_bulk>(le <b>vrac</b> uniquement)</a>";
              }
            }
            $select_mode .= "</td><td width='5px'></td><td><!PROD_ARRAY!></td><td width='30px'>";
            $select_mode .= "</td><td nowrap><a class='main' href='".$href."&print=yes' target='_blank'>Imprimer (mode produit)</a></td><td width='5px'></td><td nowrap>";
            if ($gaID>0) {
              $select_mode .= "<a class='main' href='".$href."&print=yes&bulk_only=yes' target='_blank'>(le <b>vrac</b> uniquement)</a>";
            }
            $select_mode .= "</td></tr></table></form>";
  
            if (($m_id_from_array != "")||(count($manufacturersIdArray)>2))   {    
              $select_mode = str_replace("<!MAN_ARRAY!>", tep_draw_pull_down_menu('m_id_from_array', $manufacturersIdArray, $m_id_from_array, ' onchange="this.form.submit();"'), $select_mode);
            } else { // ce n'est pas la peine d'afficher la liste de choix si on a maxi 2 valeurs : '' + 1 valeur 
              $select_mode = str_replace("<!MAN_ARRAY!>", "&nbsp;", $select_mode);
            }
            if (($customer_id != 'all_by_man')&&(($c_id_from_array != "")||(count($customersIdArray)>2))) {
              $select_mode = str_replace("<!CUST_ARRAY!>", tep_draw_pull_down_menu('c_id_from_array', $customersIdArray, $c_id_from_array, ' onchange="this.form.submit();"'), $select_mode);
            } else {
              $select_mode = str_replace("<!CUST_ARRAY!>", "&nbsp;", $select_mode);
            }
            if (($p_id_from_array != "")||(count($productsIdArray)>2))   {    
              $select_mode = str_replace("<!PROD_ARRAY!>", tep_draw_pull_down_menu('p_id_from_array', $productsIdArray, $p_id_from_array, ' onchange="this.form.submit();"'), $select_mode);
            } else {
              $select_mode = str_replace("<!PROD_ARRAY!>", "&nbsp;", $select_mode);
            }
          }

          $showTotalTable = (
            (!$customer_only)&&
              (($print && (tep_db_num_rows($man_customers_list_query) > 1))|| 
              (!$print && (
                  (($what != "f")||(($what == "f")&&($m_id_from_array == "")&&(count($manufacturersIdArray)>2)))&&
                  (($what != "c")||(($what == "c")&&($c_id_from_array == "")&&(count($customersIdArray)>2)))&&
                  (($what != "cp")||(($what == "cp")&&($p_id_from_array == "")&&(count($productsIdArray)>2))))))); 
  
          if ($showTotalTable) {
            $total_table .= '<tr><td>&nbsp;</td></tr><tr><td class="pageHeadingSmall" align="left">Liste totale des '.strtolower($heading_txt);
            if ($bulk_only) $total_table .= ' (uniquement ceux en vrac)';
            $total_table .= '
              </td></tr>
              <tr><td>
              <table border="0" width="100%" cellspacing="1" cellpadding="2" style="BORDER: 2px solid;">
                <tr class="dataTableHeadingRow">
                  <td class="dataTableHeadingContent" align="center">Qté</td>
                  <td class="dataTableHeadingContent" align="center" nowrap>'.$heading_txt.'</td>
                  <td class="dataTableHeadingContent" align="center" nowrap>'.TABLE_ORDER_PURCHASED.'</td>
                  <td class="dataTableHeadingContent" align="center">Qté</td>
                  <td class="dataTableHeadingContent" align="center">Prix total</td>
                </tr>';
          }
  
          $table .= '
            <tr><td>
            <table border="0" width="100%" cellspacing="1" cellpadding="1" style="BORDER: 2px solid;">
              <tr class="dataTableHeadingRow">';
          $table .= '
              <td class="dataTableHeadingContent" align="center">'.TABLE_PRODUCT_QUANTITY.'</td>';
          $table .= '
                <td class="dataTableHeadingContent" align="center" nowrap width="100%">';
          if ($what != "cp") { 
            $table .= TABLE_PRODUCT_NAME; 
          } else { 
            $table .= TABLE_CUSTOMER_NAME; 
          }
  
          $table .= '</td>';
          if ($what != "f") {
            $table .= '
            <td class="dataTableHeadingContent" align="center" nowrap>'.TABLE_ORDER_PURCHASED.'</td>';
          }
          if ($show_stock==1) {
            $table .= '
                <td class="dataTableHeadingContent" align="center">Stock restant</td>';
          }
          if (($what == "f")) {
            $table .= '
                <td class="dataTableHeadingContent" align="center">Prix unitaire</td>';
          }
          $table .= '
              <td class="dataTableHeadingContent" align="center">'.TABLE_PRODUCT_QUANTITY.'</td>
              <td class="dataTableHeadingContent" align="center">Prix total</td>';
  
          if((!$print)/*&&($customer_id != "all_by_man")*/&&($what != "cp")&&($customer_id != "all")&&($is_week_mode)) {
            $table .= '
                  <td class="dataTableHeadingContent" align="center" colspan="2" width="20px">M/S</td>';
          }          
          $table .= '
              </tr>';
  
     			$curTotalQuantity = 0;
     			$curTotalPrice = 0.0;
     			$curLastOrderDate = "";
     			$TotalQuantity = 0;
     			$TotalPrice = 0.0;
          $curTitleName = "";

          $customer_id_sav = $customer_id;
          $manufacturer_id_sav = $manufacturer_id;
    			while ($man_cust_list_products = tep_db_fetch_array($man_customers_list_query)) {
            $has_records = true;
            
            switch ($what) {
              case "c":
                $customer_id = $man_cust_list_products['customers_id'];
                $table .= getProductsByCustomerBlock();
                break;
              case "cp": 
        			  $products_id = "all";
                $table .= getCustomersByProductBlock();
$jmo=getCustomersByProductBlock();
                break;
              case "f": 
                $manufacturer_id = $man_cust_list_products['manufacturers_id'];
        			  $products_id = "all";
                $table .= getProductsByManufacturerBlock();
                break;
            }
            if ($curTotalQuantity == 0) {
              $tr_class = "dataTableRowStrike";
            } else {
              $tr_class = "dataTableRow";
            }

            if (($showTotalTable)&&
                    ((($what != "f")&&($curTotalQuantity>0))||($what == "f"))) {
              $total_table .= '<tr class="'.$tr_class.'">
                  <td class="dataTableContent" align="right">&nbsp;<big><b>'.tep_format_qty_for_html($curTotalQuantity).'</b></big>&nbsp;</td>
                  <td class="dataTableContent" width="100%"> &nbsp;'.$curTitleName.'</td>
                  <td class="dataTableContent" align="right">&nbsp;'.$curLastOrderDate.'&nbsp;</td>
                  <td class="dataTableContent" align="right">&nbsp;'.tep_format_qty_for_html($curTotalQuantity).'&nbsp;</td>
                  <td class="dataTableContent" align="right">&nbsp;'.$currencies->format($curTotalPrice).'&nbsp;</td></tr>';
         			$TotalQuantity += $curTotalQuantity;
         			$TotalPrice += $curTotalPrice;
            }
    			}
          $customer_id = $customer_id_sav;
          $manufacturer_id = $manufacturer_id_sav;

          $table .= '</table>
            </td></tr>';
  
          $table = str_replace("</tr><--ADD_HR--></table>", "</table>", $table);
          $table = str_replace("<--ADD_HR-->", "<tr><td colspan=7><hr size=2 color=#556655></td></tr>", $table);
  
          if (count($manufacturersIdArray)>1 || count($customersIdArray)>1 || count($productsIdArray)>1) {
            echo $select_mode;
          }
          if ($has_records) {
            if ($showTotalTable) {
              $total_table .= '
                  <tr>
                    <td class="dataTableTotalRow" align="right">&nbsp;&nbsp;</td>
                    <td class="dataTableTotalRow" colspan="2" align="right">'.ENTRY_TOTAL.'&nbsp;</td>
                    <td class="dataTableTotalRow" align="right">&nbsp;'.tep_format_qty_for_html($TotalQuantity).'&nbsp;</td>
                    <td class="dataTableTotalRow" align="right">&nbsp;'.$currencies->format($TotalPrice).'&nbsp;</td>
                  </tr>
                </table>
                </td></tr>';

              echo $total_table;
echo $jmo;
            }
  
            if (($gaID > 0)&&(!$print)&&($manufacturer_id<1)&&(($customer_id=="all_by_once")||($customer_id=="all_by_man"))) {
              $what = "";
              if ($customer_id=="all_by_once") {
                $what = " adhérents";
              } else if ($customer_id=="all_by_man") {
                $what = " fournisseurs";
              }
              if ($MySQL_query_numrows > MAX_DISPLAY_SEARCH_RESULTS_ADMIN) {
                $show_page_changer =  
                  "<table><tr><td class='main' width='100%' nowrap>".
                    $MySQL_query_split->display_count($MySQL_query_numrows, MAX_DISPLAY_SEARCH_RESULTS_ADMIN, $HTTP_GET_VARS['page'], 'Afficher <b>%d</b> &agrave; <b>%d'.$what.'</b> (sur un total de <b>%d</b>)').
                  "</td><td class='main' nowrap>&nbsp;&nbsp;&nbsp;".
                    $MySQL_query_split->display_links($MySQL_query_numrows, MAX_DISPLAY_SEARCH_RESULTS_ADMIN, MAX_DISPLAY_PAGE_LINKS, $HTTP_GET_VARS['page'], querystring_small("", "", true, "", false)).
                  "&nbsp;&nbsp;&nbsp;</td></tr></table>";
              }
            }
  
  /* // le premier lien ne fonctionne pas (le select)
            if ($show_page_changer != "")
              echo "<br>".tep_draw_form('page_results1', FILENAME_STATS_MANUFACTURERS, querystring_small(), 'get').$show_page_changer."</form>";
  */
            echo $table;
            if ($show_page_changer != "")
              echo tep_draw_form('page_results2', FILENAME_STATS_MANUFACTURERS, querystring_small(), 'get').$show_page_changer."</form>";
          } else {
            echo '<tr><td class="pageHeadingSmall" align="left" height="50px" valign="middle">Aucune commande "ponctuelle" n\'a été trouvée pour la livraison du '. getFormattedLongDate($order_date_to, true) . '.';
            if ($rec_nb>0) {
              echo '<br>Veuillez valider les commandes récurrentes en cliquant sur le bouton "<i>Valider les récurrences et les commandes</i>".';
            }
            echo '</td></tr>';
  //          echo '<tr><td class="pageHeadingSmall" align="left"><br><hr></td></tr>';
          }
          
        } // if ...
      } //if ($customer_id)

	}
?>
      </td>
      </tr>
    </table>
    </td>
    <!-- body_text_eof //-->
    </tr>
    </table>
  </td>
  </tr>
</table>

<!-- body_eof //-->
<!-- footer //-->
<? 
if(!$print) {
  require($admin_FS_path . DIR_WS_INCLUDES . 'footer.php');
} else {
  echo "<center><br><span class='menuBoxContent'><hr width='70%'>".getFooter()."</span></center>";
}
?>
<!-- footer_eof //-->
</body>
</html>
<? require($admin_FS_path . DIR_WS_INCLUDES . 'application_bottom.php'); ?>
