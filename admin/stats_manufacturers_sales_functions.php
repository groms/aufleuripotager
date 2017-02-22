<?
/*
	Contribution Name: Manufacturer Sales Report
	Contribution Version: 2.3

	Creation of this file 
  	Author Name: Cyril Jacquenot
  	Author E-Mail Address: cyril.jacquenot@laposte.net
  	Author Website: http://groms.dyndns.org
  	Donations: www.paypal.com
  	Donations Email: cyril.jacquenot@laposte.net
  
  WARNING:
    This file must be attached with the parent file "stats_manufacturers_sales.php"
*/

  function getGroupArray() {
    global $groups_array;
    $groups_array = array();
    $groups_query = tep_db_query("select group_id,group_name from manufacturers_groups order by group_name");
    while ($groups = tep_db_fetch_array($groups_query)) {
      if (tep_not_null($groups['group_name'])) {
        $n = $groups['group_name'];
      } else {
        $n = 'Vente directe';
      } 
      $groups_array[] = array('id' => $groups['group_id'], 'text' => $n);
    }
    return true;
  }

  function getEmailNotifyHTML($adminMode) {
    
    if ($adminMode) {
      $txt = tep_draw_checkbox_field('email_notify', '*', true, '', ' id="email_notify"');
    } else {
      $txt = tep_draw_checkbox_field('email_notify', '*', true, '', 'email_notify');
    }
    
    $txt .= '
      <script>
        function toggleEN() {
          document.getElementById(\'email_notify\').checked=!document.getElementById(\'email_notify\').checked;
        }
      </script>';
    
    return $txt .
      "<a href='javascript:toggleEN();'>Informer l'adhérent par email</a>";
  }

  function propagate_product_name_changes($p_id) {
		//TABLE op and ops
		$sql = "SELECT pd.products_id, pd.products_name, p.products_reference, p.group_id FROM products_description AS pd
      LEFT JOIN products AS p ON p.products_id=pd.products_id";
    if ($p_id>0) $sql .= " WHERE p.products_id = $p_id";  
    
		$query = tep_db_query($sql, 'db_link');
    while ($record = tep_db_fetch_array($query)) {
			tep_db_query("UPDATE orders_products SET products_name = '".addslashes_once(trim($record['products_name']))."' 
                      WHERE products_id = ".$record['products_id'].";", 'db_link');

      if ($record['group_id'] > 0) {
        $products_name = $record['products_name'];
        if ($record['products_reference'] != "") {
          $products_name .= " <i>(réf. : ".$record['products_reference'].")</i>";
        }
  
  			tep_db_query("UPDATE orders_products_suppliers SET 
                          products_name = '".addslashes_once($record['products_name'])."' 
                          WHERE products_id = ".$record['products_id'].";", 'db_link');
      }
		}                                                  

		//TABLE opm
		$sql = "SELECT op.orders_products_id, op.products_id, op.products_name, opa.products_options, opa.products_options_values FROM orders_products AS op
      LEFT JOIN orders_products_attributes AS opa ON op.orders_products_id=opa.orders_products_id";
    if ($p_id>0) $sql .= " WHERE op.products_id = $p_id";  
		$query = tep_db_query($sql, 'db_link');
    while ($record = tep_db_fetch_array($query)) {
			tep_db_query("UPDATE orders_products_modifications SET 
        products_name = '".addslashes_once($record['products_name'])."',
        products_options = '".addslashes_once(trim($record['products_options']))."',
        products_options_values = '".addslashes_once(trim($record['products_options_values']))."'
          WHERE orders_products_id = ".$record['orders_products_id'].";", 'db_link');
		}
  }

  function propagate_shipping_modifications($manufacturers_id, $default_shipping_day, $default_shipping_frequency, $table, $products_id = -1) {
    // les commandes récurrentes impactées doivent être prises en compte
    // ceci ne s'applique que pour la vente directe (commandes récurrentes et group_id = 0)

    if ($products_id > -1) {// on désactive l'utilisation de cette fonction dangereuse si $products_id!=-1
    
//    	echo "pid=".$products_id."!dsd=".$default_shipping_day."!dsf=".$default_shipping_frequency;exit;
    
    	if ($products_id > -1) $p_id_where = " AND op.products_id = ".$products_id;
    	else $p_id_where = ""; 

	    // si $default_shipping_day == t|f => on n'a rien à faire car on les enreg op des commandes en cours sont bons à tous les coups
	    if (($default_shipping_day == 'tuesday|thursday') || ($default_shipping_frequency < 1)) return 0; 
	
	    $sql = "SELECT DISTINCT o.customers_id, m.group_id, op.products_id, op.shipping_frequency, op.shipping_day, o.customers_name, o.customers_email_address, o.orders_status, op.orders_id, op.next_date_shipped FROM ".TABLE_ORDERS_PRODUCTS." AS op 
	              LEFT JOIN ".TABLE_MANUFACTURERS." AS m ON m.manufacturers_id = op.manufacturers_id 
	              LEFT JOIN ".TABLE_ORDERS." AS o ON op.orders_id = o.orders_id 
	      WHERE m.manufacturers_id = " . (int)$manufacturers_id . " AND op.shipping_day <> '$default_shipping_day'  
	          AND op.is_recurrence_order = 1 AND o.orders_status = 4 AND m.group_id = 0 $p_id_where GROUP BY op.orders_id ORDER BY o.customers_name;";  
	
	    $query = tep_db_query($sql);
	    $i = 0;
	    $txt = ""; 
	    $o_id_list = "";
	    $p_id_list = ""; 
	    $email_list = ""; 
	    while ($record = tep_db_fetch_array($query)) {
	      // on modifie l'enreg si shipping_day <> $default_shipping_day ou que $default_shipping_frequency > $shipping_frequency
	      // par contre, dans l'UPDATE d'op, on ne modifie shipping_frequency que si $default_shipping_frequency < shipping_frequency  
	      $add = (($record['shipping_day'] <> $default_shipping_day) || ($default_shipping_frequency < $record['default_shipping_frequency']));
	      
	      if ($add) {
	        $o_id_list .= $record['orders_id'].","; 
		    $p_id_list .= $record['products_id'].",";
	        $email_list .= $record['customers_name']." &lt;".$record['customers_email_address']."&gt;, "; 
	        $txt .= "&nbsp;&nbsp;&nbsp;Commande n° ".$record['orders_id']." <b>récurrente</b> concernée (Adhérent n° ".$record['customers_id']." - ".$record['customers_name'].", email : ".$record['customers_email_address'].")<br>";
	        $i = $i + 1;
	      }
	    }
	
	    if ($i>0) {
	      if (strlen($o_id_list) > 1) $o_id_list = substr($o_id_list, 0, -1);
	      if (strlen($p_id_list) > 1) $p_id_list = substr($p_id_list, 0, -1);
	      if (strlen($email_list) > 2) $email_list = substr($email_list, 0, -2);
	      
	      // 1er cas : shipping_day <> $default_shipping_day UNIQUEMENT
	      //   => on ne modifie pas la fréquence, mais on modifie nds et sd
	      $order_date = get_order_date($manufacturers_id, "", $default_shipping_day, 1);
	      $sql = "UPDATE " . TABLE_ORDERS_PRODUCTS . " SET next_date_shipped = '$order_date', shipping_day = '$default_shipping_day' WHERE manufacturers_id = " . (int)$manufacturers_id . " AND orders_id IN ($o_id_list) AND shipping_day <> '$default_shipping_day' AND shipping_frequency = $default_shipping_frequency AND products_id IN ($p_id_list);";
	 			tep_db_query($sql);
	
	      // 2ème cas : on ne modifie pas que la fréq et le nds
	      //      la fréquence est plus grande (ex : 1 fois / sem   =>   2 fois / sem)
	      //      le jour est identique
	      $sql = "UPDATE " . TABLE_ORDERS_PRODUCTS . " SET next_date_shipped = '$order_date', shipping_frequency = $default_shipping_frequency WHERE manufacturers_id = " . (int)$manufacturers_id . " AND orders_id IN ($o_id_list) AND shipping_frequency > $default_shipping_frequency  AND shipping_day = '$default_shipping_day' AND products_id IN ($p_id_list);";
	 			tep_db_query($sql);
	
	      // 3ème cas : le jour est différent et la fréquence aussi => il faut modifier nds, ds et sf  UNIQUEMENT SI  
	      //      la fréquence est plus grande (ex : 1 fois / sem   =>   2 fois / sem)
	      //      le jour est différent 
	      $sql = "UPDATE " . TABLE_ORDERS_PRODUCTS . " SET next_date_shipped = '$order_date', shipping_day = '$default_shipping_day', shipping_frequency = $default_shipping_frequency WHERE manufacturers_id = " . (int)$manufacturers_id . " AND orders_id IN ($o_id_list) AND shipping_frequency > $default_shipping_frequency  AND shipping_day <> '$default_shipping_day' AND products_id IN ($p_id_list);";
	 			tep_db_query($sql);
	       
	      $bilan = "Il y a $i commandes/produits impactés par la modification du mode de livraison pour le producteur ".$manufacturers_name." ($table) et les produits n° $p_id_list."; 
	      	
	      $emails = $bilan."<br><br>".$txt."<br><br>Liste des adhérents concernés :<br>".$email_list;
	      tep_mail('groms', 'groms@free.fr', 'MODIFICATION default_shipping_day OU default_shipping_frequency - AUFLEURIPOTAGER', $emails, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
	    }
	    return $i;

    } else return 0;
    

  }

  function putShippingJS($formName) {
    return "
      function updateSD(id) {
        var shippingDay;
        var shippingFrequency;
        
        if (id == null) {
          shippingDay = document.forms['$formName'].shipping_day;
          shippingFrequency = document.forms['$formName'].shipping_frequency;
        } else {
          shippingDay = document.getElementById('shipping_day'+id);
          shippingFrequency = document.getElementById('shipping_frequency'+id);
        }
        
        if (shippingDay.value == 'tuesday|thursday') {
          shippingFrequency.selectedIndex = 0; //0.5
        } else if (shippingDay.value == 'saturday') {
          shippingFrequency.selectedIndex = 3; //4.0
        } else {
          if (shippingFrequency.selectedIndex == 0) {
            shippingFrequency.selectedIndex = 1; //1.0
          }
        }
      }
      
      function updateSF(id) {
        var shippingDay;
        var shippingFrequency;
        var shippingDates = null;
        
        if (id == null) {
          shippingDay = document.forms['$formName'].shipping_day;
          shippingFrequency = document.forms['$formName'].shipping_frequency;
        } else {
          shippingDay = document.getElementById('shipping_day'+id);
          shippingFrequency = document.getElementById('shipping_frequency'+id);
          shippingDates = document.getElementById('next_date_shipped'+id);
        }
        
        if (shippingFrequency.value == '0.5') {
          if (shippingDates) {
            shippingDay.value = 'tuesday|thursday';
          } else {
            shippingDay.selectedIndex = 2; //tuesday|thursday
          }
        } else {
          if (shippingDates) {
            var myDate = shippingDates.value;
            annee = myDate.substr(0,4); 
            mois = myDate.substr(5,2); 
            jour = myDate.substr(8,2); 
            var newDate = new Date(annee, mois-1, jour); //quand vous transmettez un mois en valeur numérique, il vous faut commencer à compter par 0
            var myDay = newDate.getDay(); // day:0 => dimanche

            if (myDay == 2) {
              shippingDay.value = 'tuesday';
            } else if (myDay == 4) {
              shippingDay.value = 'thursday';
            }
          } else {
            if ((shippingDay.selectedIndex == 2)||((shippingDay.selectedIndex == 3)&&(shippingFrequency.value < '4.0'))) {
              // si tuesday|thursday est sélectionné
              shippingDay.selectedIndex = 0; //tuesday
            }
          } 
        }
      }
      ";
  }
  
  function putShipping($id, $sd, $sf, $ds = "", $from = "", $is_rec = false, $new_product = false, 
        $sf_selected_ahi = "", $ds_sel = "", $nb_weeks_max = 3, $op_id = "") {
    // $from = "m" : on vient de admin/manufacturers.php
    // $from = "p" : on vient de admin/categories.php
    // $from = "" : on vient de admin/stats_manufacturers_sales_functions.php/getShippingInfo et donc de checkout_confirmation.php
    global $gaID;
    
    if ($ds != "") $ds = date("Y-m-d", getStrtotime($ds));

    if ($op_id != '') {
      $js = 'document.getElementById(\'modified'.$op_id.'\').value=\'yes\';';
    } else {
      $js = '';
    }
//    echo $js;exit;

    list($id, $attr) = explode("§", $id);  
    $id = tep_get_prid($id);
    $result = "";
    $sd_man = "";
    $sf_man = "";
    $m_id = "";
    $p_id = "";
		if ($from != "m") {
		  // on ne vient pas de admin/manufacturers.php
      if (is_numeric($id)) {
        if ($from == "p") {
    		  // on vient de admin/categories.php
    		  // ==> on recherche le parent (manufacturer))
          $m_id = $id;
          $query_raw = "SELECT default_shipping_day as shipping_day, default_shipping_frequency as shipping_frequency FROM " . TABLE_MANUFACTURERS . " WHERE manufacturers_id = " . $id;
        } else {
    		  // on vient de admin/stats_manufacturers_sales_functions.php
    		  // ==> on recherche le produit
          $p_id = $id;
          $query_raw = "SELECT shipping_day, shipping_frequency FROM " . TABLE_PRODUCTS . " WHERE products_id = " . $id;
        }
    		$query = tep_db_query($query_raw);
    		if ($res = tep_db_fetch_array($query)) {
          $sd_parent = $res["shipping_day"];
          $sf_parent = $res["shipping_frequency"];
    		}
      } else {
        $sd_parent = "thursday";
        $sf_parent = "1.0";
        if ($new_product && ($from == "p")) {
          $sd = $sd_parent;
          $sf = $sf_parent;
        }
        
      }
    }
    if ($from != "") $id = "";
    
    if (($from != "")&&(($sf_parent < 1.0)||($from == "m")||(($from == "p")&&(!is_numeric($id))))) {
      // le producteur est OK pour livrer le mardi et le vendredi => on autorise l'adhérent à choisir
      $shipping_day_array = array(
        array('id' => 'tuesday', 'text' => 'mardi'),
        array('id' => 'thursday', 'text' => 'jeudi'),
        array('id' => 'tuesday|thursday', 'text' => 'mardi et jeudi'),
        array('id' => 'saturday', 'text' => 'samedi'));

      $result .= tep_draw_pull_down_menu('shipping_day'.$id.$attr, $shipping_day_array, $sd, 'onchange="'.$js.'updateSD(\''.$id.$attr.'\');"');
    } else {
      // sinon, pas le choix
      $result .= tep_draw_hidden_field('shipping_day'.$id.$attr, $sd);
      if ($ds != "") {
        $ds_cur = strtotime($ds);
        if ($gaID>0) {
          // c'est un groupement d'achat
          // on récupère nnds
          $nnds = getGA_order_date_next($gaID);
          
          if (($ds == $nnds)||($nnds == "")||($nnds == "0000-00-00")) {
            // les commandes sont déjà figées pour $ds => pas le choix
            $result .= tep_draw_hidden_field('next_date_shipped'.$id.$attr, $ds);
            $result .= "<b><u>".getFormattedLongDate($ds, true)."</u></b>";
          } else {
            // les commandes ne sont pas déjà figées pour $ds => 2 choix possibles 
            $shipping_dates_array[] = array('id' => $ds,
                                   'text' => getFormattedLongDate($ds, true));
            $shipping_dates_array[] = array('id' => $nnds,
                                   'text' => getFormattedLongDate($nnds, true));
          }
        } else {
          // c'est de la vente directe
          if (orders_are_frozen($p_id, $m_id, $ds) == "") {  //$ds is in Y-m-d format
            $shipping_dates_array[] = array('id' => $ds,
                                   'text' => getFormattedLongDate($ds, true));
          } 
  
          for ($i=0; $i<($nb_weeks_max-1); $i++) {
            if (($sf < 1)||($sd == "tuesday|thursday")) {
              if (strtolower(date("l", $ds_cur)) == "tuesday") {
                $ds_cur = getNextThursday($ds_cur);
              } else if (strtolower(date("l", $ds_cur)) == "thursday") {
                $ds_cur = getNextTuesday($ds_cur);
              } 
              if (orders_are_frozen($p_id, $m_id, $ds_cur) == "") {   //$ds_cur is in Y-m-d format
                $shipping_dates_array[] = array('id' => $ds_cur,
                                       'text' => getFormattedLongDate($ds_cur, true));
              }
            } else {
              $ds_cur = getDay($ds_cur, $sd);
              if (orders_are_frozen($p_id, $m_id, $ds_cur) == "") {   //$ds_cur is in Y-m-d format
                $shipping_dates_array[] = array('id' => $ds_cur,
                                       'text' => getFormattedLongDate($ds_cur, true));
              }
            }
            $ds_cur = getStrtotime($ds_cur); //force interg format for $ds_cur
          }
        }
        if (count($shipping_dates_array)>0) {
          if ($ds_sel == "") {
            $ds_sel = $ds;  
          }
          $result .= tep_draw_pull_down_menu('next_date_shipped'.$id.$attr, $shipping_dates_array, $ds_sel, 'onchange="'.$js.'updateSF(\''.$id.$attr.'\');"');
        }
      } else {
        $result .=  '<b>' . convertEnglishDateNames_fr($sd) .'</b>';
      }
    }
    
    // pour la fréquence de livraison, on ne peut être que moins restrictif
    //  - si shipping_frequency = 2.0 par ex => on ne peut pas être livrer 2 fois par semaine, ni toutes les semaines
    if ((($is_rec)||($from != ""))&&(($sf_parent < 4.0)||($from == "m"))) {
//    if ((($is_rec))&&(($sf_parent < 4.0)||($from != ""))) {
      $shipping_frequency_array = array(
        array('id' => '0.5', 'text' => convertShippingFrequencyToText_fr(0.5)),
        array('id' => '1.0', 'text' => convertShippingFrequencyToText_fr(1.0)),
        array('id' => '2.0', 'text' => convertShippingFrequencyToText_fr(2.0)),
        array('id' => '4.0', 'text' => convertShippingFrequencyToText_fr(4.0)));
  
     if ($from != "m") {
        //  si on vient de categories.php et que nouveau produit => on ne vire rien
        if (!$new_product || ($from != "p")) {
          if ($sf_parent >= 1.0) {
            // on vire le 0.5
            $shipping_frequency_array = array_splice($shipping_frequency_array, 1);  
          }
          if ($sf_parent >= 2.0) {
            // on vire le 0.5 et 1.0
            $shipping_frequency_array = array_splice($shipping_frequency_array, 1);  
          }
        }
      }
      
      if ($sf_selected_ahi == "") {
        if ($from == "") {
          $sf_sel = $shipping_frequency_array[0]['id'];
          if ($sf_sel < 1) $sf_sel = 1.0;
        } else {
          $sf_sel = $sf;
        }
      } else {
        $sf_sel = $sf_selected_ahi;
      }
      
      $result .=  ' '.tep_draw_pull_down_menu('shipping_frequency'.$id.$attr, $shipping_frequency_array, $sf_sel, 'onchange="'.$js.'updateSF(\''.$id.$attr.'\');"');
    } else {
      // on a pas le choix (on vire en fait le 0.5, 1.0 et 2.0) => toutes les 4 semaines
      $result .=  tep_draw_hidden_field('shipping_frequency'.$id.$attr, $sf);
      if ($is_rec) $result .= ' ('.convertShippingFrequencyToText_fr($sf).')';
    }
    
    $result .= '
      <script>
        updateSF('.$id.$attr.');
      </script>';
    
    return $result; 
  }

  function convertShippingFrequencyToText_fr($sf) {

    switch ($sf) {
      case 1.0: return "1 fois par semaine";
      case 0.5: return "2 fois par semaine";
      case 2.0: return "toutes les 2 semaines";
      case 4.0: return "toutes les 4 semaines";
    }
    
  }


  function convertEnglishDateNames_fr($date, $toLower = true)
  {
    $date = strtolower($date);
    $date=str_replace ("monday","Lundi",$date);
    $date=str_replace ("tuesday","Mardi",$date);
    $date=str_replace ("wednesday","Mercredi",$date);
    $date=str_replace ("thursday","Jeudi",$date);
    $date=str_replace ("thursday","Vendredi",$date);
    $date=str_replace ("saturday","Samedi",$date);
    $date=str_replace ("sunday","Dimanche",$date);
    $date=str_replace ("|"," et ",$date);
    
    
    $date=str_replace("january","Janvier",$date);
    $date=str_replace("february","Février",$date);
    $date=str_replace("march","Mars",$date);
    $date=str_replace("april","Avril",$date);
    $date=str_replace("may","Mai",$date);
    $date=str_replace("june","Juin",$date);
    $date=str_replace("july","Juillet",$date);
    $date=str_replace("august","Août",$date);
    $date=str_replace("september","Septembre",$date);
    $date=str_replace("october","Octobre",$date);
    $date=str_replace("november","Novembre",$date);
    $date=str_replace("december","Décembre",$date);

    $date=str_replace(" 0"," ",$date);
    $date=str_replace(", "," ",$date);
    
    if (!$toLower) {
      $date = ucwords($date); 
    } else {
      $date = strtolower($date); 
    }

    return ($date);
  }


  function getUSLanguageDateFormat($strDate) {
    // transform if necessary dd/mm/yyyy to yyyy-mm-dd 
    if ((strlen($strDate) <> 10) || (DEFAULT_LANGUAGE != "fr")) {
      return $strDate;
    }
    else {
      // replacing / by -
      $strDate = str_replace("/", "-", $strDate);
      
      if ($strDate{2} == "-") {
//        dd-mm-yyyy
//        0123456789
        return substr($strDate, 6, 4)."-".substr($strDate, 3, 2)."-".substr($strDate, 0, 2); 
      }
      else {
//        yyyy-mm-dd
//        0123456789
        return $strDate;
      }
    }
  }

  function getDefaultLanguageDateFormat($strDate) {
    // transform if necessary yyyy-mm-dd to dd/mm/yyyy 
    if ((strlen($strDate) <> 10) || (DEFAULT_LANGUAGE != "fr")) {
      return $strDate;
    }
    else {
      // replacing / by -
      $strDate = str_replace("/", "-", $strDate);
      
      if ($strDate{2} == "-") {
//        dd-mm-yyyy
//        0123456789
        return str_replace("-", "/", $strDate);
      }
      else {
//        yyyy-mm-dd
//        0123456789
        return substr($strDate, 8, 2)."/".substr($strDate, 5, 2)."/".substr($strDate, 0, 4); 
      }
    }
  }

  function getFormattedLongDate($dateShipped = "", $withNamedDay = false) {
    if ($dateShipped == "") {
      $dateShipped = date("Y-m-d");  
    }
    if ($withNamedDay) {
      $aux = "l d F Y";
    } else {
      $aux = "d F Y";
    }
    return convertEnglishDateNames_fr(date($aux, getStrtotime($dateShipped)));
  }
  
  function getMySQLraw($whichSQL, $pn = "", $po = "", $pov = "", $is_rec_search = false, $adminMode = "yes", $get_arrays = false) {
    global $manufacturer_id, $customer_id, $gaID, $bulk_only,
      $forceValidation, $order_date_from, $order_date_to, $print,
      $MySQL_query_numrows, $MySQL_query_split, $HTTP_GET_VARS,
      $customersIdArray, $manufacturersIdArray, $productsIdArray,
      $m_id_from_array, $c_id_from_array, $p_id_from_array, $products_id;
      
    $sum = "sum(opm.products_quantity) as sum_pq, sum(opm.final_price*opm.products_quantity) as sum_fp ";
    $select = $sum;
    $join = "";
    $max_date = "p.manufacturers_id, max(opm.orders_products_modifications_datetime) as dp, o.orders_status as os, o.orders_id, opm.is_recurrence_order, ";

    if ($manufacturer_id>0) {
      $and = " AND p.manufacturers_id = " . $manufacturer_id;
    }
    else {
      $and = "";
    }
    
    if (($whichSQL == "p")||($whichSQL == "cp")) {
      $selectPorCP = " o.orders_status, p.products_min_manufacturer_quantity, p.is_bulk, p.measure_unit, p.products_reference, p.products_quantity as products_stock, opm.products_id, opm.orders_products_id, opm.final_price, opm.products_quantity, opm.date_shipped, opm.orders_products_modifications_datetime, opm.orders_products_modifications_id, ";
    }
    
    $split_field_count = "";
    switch ($whichSQL) {
      case "m":
          $group_by = "opm.group_id, m.manufacturers_name, opm.manufacturers_id";
          $by = "mg.group_name, " . $group_by;
          $select = $by . ", " . $select;
          break;
      case "c": // groupement par adhérent
          $group_by = "c.customers_lastname, c.customers_firstname, opm.customers_id";
          $by = $group_by . ", opm.orders_products_modifications_datetime, opm.orders_id";
          $select = $by . ", " . $max_date . $select; 
          $split_field_count = "opm.customers_id";
          break;
      case "f": // groupement par fournisseur
          $group_by = "m.manufacturers_name, opm.manufacturers_id";
          $by = $group_by . ", opm.orders_products_modifications_datetime, opm.orders_id";
          $select = $by . ", " . $max_date . $select; 
          $split_field_count = "opm.manufacturers_id";
          break;
      case "p": // liste des produits par fournisseur ou client
          $group_by = "m.manufacturers_name, pd.products_name, opm.products_options, opm.products_options_values";
          $by = $group_by . ", opm.orders_products_id, opm.manufacturers_id";
          $select = "opm.manufacturers_id, " . $selectPorCP . $by . ", " . $max_date . $select; 
          break;
      case "cp": // customer list by products
          $by = "pd.products_name, opm.products_options, opm.products_options_values, op.orders_products_id, opm.orders_products_modifications_id, o.customers_name";
          $select = "m.manufacturers_name, opm.customers_id, ". $selectPorCP . $by . ", " . $max_date . $select; 
    		  if ($pn != "") {
            $group_by = "m.manufacturers_name, c.customers_lastname, c.customers_firstname";
            $and .= " AND pd.products_name = '" . addslashes_once($pn) . "'";
            if (($po != "") && ($pov != ""))  {
              $and .= " AND opm.products_options = '" . addslashes_once($po) . "' AND opm.products_options_values = '" . addslashes_once($pov) . "'";
            } 
          }
          else {
            $group_by = "m.manufacturers_name, pd.products_name, opm.products_options, opm.products_options_values";
          }
          break;
    }
    if ($whichSQL!="m") {
	    if (($whichSQL=="p")||($whichSQL=="cp")) {
				$join .= " LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " AS pd ON pd.products_id = opm.products_id"; 
			}
			$join .= " LEFT JOIN " . TABLE_CUSTOMERS . " AS c ON c.customers_id = opm.customers_id"; 
		}

    $gb = "
              GROUP BY " . $group_by . "
              ORDER BY " . $group_by;

    if (!$is_rec_search) {
  		// CAREFUL : in $sql, LEAVE SPACE BEFORE EACH LINE (for split_page_results.php)

      $sql = "SELECT " . $select . " 
  							 FROM orders_products_modifications AS opm
  							 LEFT JOIN " . TABLE_ORDERS_PRODUCTS . " AS op ON opm.orders_products_id = op.orders_products_id
  							 LEFT JOIN " . TABLE_PRODUCTS . " AS p ON opm.products_id = p.products_id
  							 LEFT JOIN " . TABLE_ORDERS . " AS o ON opm.orders_id = o.orders_id
                 LEFT JOIN " . TABLE_MANUFACTURERS . " AS m on opm.manufacturers_id = m.manufacturers_id ";

      $where_clause = "opm.facturate = 'Y' AND opm.date_shipped BETWEEN '" . getUSLanguageDateFormat($order_date_from) . "' AND '" . $order_date_to . "'";
//((opm.group_id = 0) OR ((opm.group_id > 0) AND (o.orders_status <> 5))) AND 
      if ($whichSQL == "m"){
        $sql .=" LEFT JOIN manufacturers_groups as mg ON opm.group_id = mg.group_id ";  
		    $where_clause = " WHERE (((opm.is_recurrence_order = 1) OR (opm.is_recurrence_order = 0 AND (". $where_clause . "))) AND opm.manufacturers_id IS NOT NULL AND opm.manufacturers_id <> '') ";
      } else {
		    $where_clause = " WHERE (". $where_clause . ") ";
      }
      $sql .= $join;
			if ($manufacturer_id < 1) {
			  if ($gaID <= 0) {
  		    $where_clause .= " AND opm.group_id = 0"; 
        } else {
  		    $where_clause .= " AND opm.group_id = 1 AND opm.customers_ga_id = $gaID"; 
        }
      }

		  if (is_numeric($customer_id)) {
		    $where_clause .= " AND opm.customers_id = " . $customer_id;
      } else {
		    $where_clause .= " AND opm.customers_id > 0 ";
      }
      if (!empty($bulk_only) && ($bulk_only == true)) {
		    $where_clause .= " AND p.is_bulk = 1 ";
      }
      $where_clause .= $and;
      
      if ($get_arrays) {
        $customersIdArray = array();
        $manufacturersIdArray = array();
        $productsIdArray = array();
        $customersIdArray[] = array('id' => '', 'text' => '');
        $manufacturersIdArray[] = array('id' => '', 'text' => '');
        $productsIdArray[] = array('id' => '', 'text' => '');
        $sql_array = "SELECT 
                        opm.products_id, opm.products_options, opm.products_options_values, m.manufacturers_name, pd.products_name,
                        opm.customers_id, CONCAT(c.customers_firstname, ' ', c.customers_lastname) AS c_name, 
                        opm.manufacturers_id, m.manufacturers_name 
                      FROM orders_products_modifications AS opm
                        LEFT JOIN products_description AS pd ON opm.products_id = pd.products_id
         							  LEFT JOIN " . TABLE_PRODUCTS . " AS p ON opm.products_id = p.products_id
                        LEFT JOIN customers AS c ON opm.customers_id = c.customers_id
                        LEFT JOIN manufacturers AS m ON opm.manufacturers_id = m.manufacturers_id";
        if ($whichSQL == "f") { // permet de ne pas afficher les produits non commandés au fournisseur en mode "fournisseur"
          $sql_array .= " LEFT JOIN manufacturers_validations AS mv ON mv.manufacturers_id = opm.manufacturers_id AND mv.date_shipped = opm.date_shipped                    
                          LEFT JOIN orders_products_suppliers AS ops ON ops.manufacturers_id = opm.manufacturers_id AND ops.date_shipped = opm.date_shipped AND ops.products_id = opm.products_id ";                    
        } 
        $sql_array .= $where_clause;

        /* on est obligé d'adapter 3 fois le calcul du $where_array 
            cela permet de garder toujours la liste max de choix possible pour un man, cust ou prod sélectionné 
            NE PAS REGROUPER CES 3 FOIS EN UNE SEULE
        */
        $where_array = "";
        if ($m_id_from_array != "") {
          $where_array .= " AND opm.manufacturers_id = ". $m_id_from_array;
        }
        if ($p_id_from_array != "") {
          $where_array .= " AND opm.products_id = ". $p_id_from_array;
        }
        $query_array = tep_db_query($sql_array . $where_array . " GROUP BY opm.customers_id ORDER BY c_name");
        while ($res_array = tep_db_fetch_array($query_array)) {
          $customersIdArray[] = array('id' => $res_array['customers_id'], 'text' => $res_array['c_name'].' ('.$res_array['customers_id'].')');
        }

        $where_array = "";
        if (!$supplier_mode && ($c_id_from_array != "")) {
          $where_array .= " AND opm.customers_id = ". $c_id_from_array;
        }
        if ($p_id_from_array != "") {
          $where_array .= " AND opm.products_id = ". $p_id_from_array;
        }
        $query_array = tep_db_query($sql_array . $where_array . " GROUP BY opm.manufacturers_id ORDER BY m.manufacturers_name");
        while ($res_array = tep_db_fetch_array($query_array)) {
          $manufacturersIdArray[] = array('id' => $res_array['manufacturers_id'], 'text' => $res_array['manufacturers_name'].' ('.$res_array['manufacturers_id'].')');
        }

        $where_array = "";
        if ($m_id_from_array != "") {
          $where_array .= " AND opm.manufacturers_id = ". $m_id_from_array;
        }
        if (!$supplier_mode && ($c_id_from_array != "")) {
          $where_array .= " AND opm.customers_id = ". $c_id_from_array;
        }
        $new_sql_array = $sql_array.$where_array;
        if ($whichSQL == "f") { // permet de ne pas afficher les produits non commandés au fournisseur en mode "fournisseur"
          $new_sql_array .= " AND ((mv.validation_datetime IS NULL) OR (mv.validation_datetime IS NOT NULL AND ops.new_products_quantity > 0)) ";  
        }
        $new_sql_array .= " GROUP BY m.manufacturers_name, pd.products_name, opm.products_options, opm.products_options_values ORDER BY m.manufacturers_name, pd.products_name;";
        $query_array = tep_db_query($new_sql_array);
        while ($res_array = tep_db_fetch_array($query_array)) {
          $options = '';
          if (/*$res_array['products_options'] &&*/ $res_array['products_options_values']) {
            $options = ' - '. /*$res_array['products_options'].' : '.*/$res_array['products_options_values'];
          }
          $p_name = '';
          if ($manufacturer_id <= 0) {
            $p_name .= $res_array['manufacturers_name'].'/';
          }
          $p_name .= tep_truncate_string($res_array['products_name'], MAX_PRODUCTS_NAMES_LENGTH /2);
          $productsIdArray[] = array('id' => $res_array['products_id'], 'text' => $p_name.$options.' ('.$res_array['products_id'].')');
        }
      }
      
      $sql .= $where_clause.$gb;
      $sql = addArrayFilters($sql);
  
      if (($adminMode == "yes")&&(!$print)&&($gaID>0)&&($manufacturer_id < 1)&&($split_field_count != "")) {
        $MySQL_query_split = new splitPageResults($HTTP_GET_VARS['page'], MAX_DISPLAY_SEARCH_RESULTS_ADMIN, $sql, $MySQL_query_numrows, $split_field_count);
      }
    } else {
      // is_rec_search==true
      $sql = "";
      if ($order_date_from == $order_date_to) {
        // on ne valide les récurrences que sur une semaine
        $day = strtolower(date("l", strtotime($order_date_to)));
    		$sql = "SELECT DISTINCT o.customers_id, o.customers_name, op.products_quantity, pd.products_name, op.orders_id, op.orders_products_id, op.next_date_shipped, op.shipping_day, op.shipping_frequency 
                  FROM " . TABLE_ORDERS_PRODUCTS . " AS op 
                    LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " AS pd ON pd.products_id = op.products_id
                    LEFT JOIN " . TABLE_ORDERS . " AS o ON op.orders_id = o.orders_id
                    LEFT JOIN " . TABLE_CUSTOMERS . " AS c ON o.customers_id = c.customers_id";
    		$sql .= " WHERE op.manufacturers_id = " . $manufacturer_id . " AND o.orders_status = 4 AND op.products_quantity > 0.0 AND 
                    op.is_recurrence_order > 0 AND op.shipping_day LIKE '%$day%' AND 
                    op.date_shipped <= '" . $order_date_to . "' AND 
                    op.orders_products_id NOT IN (
                        SELECT DISTINCT orders_products_id FROM orders_products_modifications 
                        WHERE facturate = 'Y' AND is_recurrence_order > 0 AND date_shipped = '" . $order_date_to . "' AND manufacturers_id = " . $manufacturer_id . ")
                  ORDER BY c.customers_lastname, c.customers_firstname, pd.products_name";                  // enlevé du 1er WHERE : AND op.next_date_shipped <= '$order_date_to' 
      }              // ajout le 9 aout 2010 : op.date_shipped <= '" . $order_date_to . "' AND
                     // op.date_shipped est la date de départ de prise en compte des récurrences, c'est la référence  
                     // il ne faut pas mettre de condition sur op.next_date_shipped (car elle peut être dépassée par rapport à order_date suite à une non-livraison fournisseur)  
    } 
//    echo $sql."<br>";
    return $sql;
  }
               
  function querystring_small($mID = "", $mName = "", $with_dates = true, $cID = "", $with_page = true, $with_arrays = true) {
    global $order_date_from, $order_date_to, $manufacturer_name, $manufacturer_id, 
      $customer_id, $customer_name, $gaID, $gaName, $HTTP_GET_VARS,
      $m_id_from_array, $c_id_from_array, $p_id_from_array;
                   
    getDates();                   
                     
    $aux = '';
    if ($with_dates) {
      $aux .= '&order_date_from='.urlencode($order_date_from).'&order_date_to='.urlencode($order_date_to);
    }

    if ($with_arrays) {
      if ($m_id_from_array != "") {
        $aux .= "&m_id_from_array=".$m_id_from_array;
      }
      if ($c_id_from_array != "") {
        $aux .= "&c_id_from_array=".$c_id_from_array;
      }
      if ($p_id_from_array != "") {
        $aux .= "&p_id_from_array=".$p_id_from_array;
      }
    }
    
    if ($cID != "") {
      $aux .= "&cID=".$cID;
    } else {
      if (trim($customer_id) == "") {
        $customer_id = "all_by_once"; 
      }
      $aux .= "&cID=$customer_id";
    }
    
    if ($mID != "") {
      $aux .= "&mID=".$mID;
    } else {
      if (trim($manufacturer_id) != "") { $aux .= "&mID=".$manufacturer_id; }
    }
    if (trim($gaID) != "") { $aux .= "&gaID=".$gaID; }
    if (trim($gaName) != "") { $aux .= "&gaName=".$gaName; }
    if ($mName != "") {
      $aux .= "&mName=".urlencode(addslashes_once($mName));
    } else {
      if (trim($manufacturer_name) != "") { $aux .= "&mName=".urlencode(addslashes_once($manufacturer_name)); }
    }
    if (trim($customer_name) != "") { $aux .= "&cName=".urlencode(addslashes_once($customer_name)); }
    if (($with_page)&&($HTTP_GET_VARS['page']>0)) {
      $aux .= "&page=".$HTTP_GET_VARS['page']; 
    }
    
    $qs = querystring_remove(array("page", "action", "m_id_from_array", "c_id_from_array", "p_id_from_array", "cID", "cName", "mID","mName","order_date_from","selected_box","order_date_to","period_name","gaID","gaName")).$aux;
    if (substr($qs, 0, 1) == "&") {
      $qs = substr($qs, 1);
    }
    $qs .= '&selected_box=reports';

    return $qs;
  }

  function querystring_remove($remove_vars) {
  /*
    * Usage:
    * Suppose $_SERVER['QUERY_STRING'] looks like this: ?a=1&b=2&c=3:
    *
    * echo querystring_remove("a"); -> ?b=2&c=3 (a is removed)
  */
    if ($remove_vars == '') {
      $remove_vars = array();
    }
    if ( !is_array($remove_vars) ) {
      $remove_vars = array($remove_vars);
    }
//    print_r($remove_vars);echo "<br>";
    array_push($remove_vars, 'osCAdminID','force_validation','disable_current_week','fill_modifications',
      'orders_total','remove_validation','remove_manufacturer_validation','rmv_m_id','convert_data','change_names','force_validation','selected_box',
      'update_opm','md_del_mode','md_c_id','md_p_id','md_ds','md_opm_id','md_qty','md_price','md_stock','freeze_orders',
      'checkbox','radio_url','md_supplier_mode','shipping_dates','facturation_months','bulk_only');

//    print_r($remove_vars);echo "<br>";

    $sep = ini_get('arg_separator.output');

    $qs = "";
    foreach ( $_GET as $k => $v ) {
      if ( !in_array($k, $remove_vars) ) {
        $qs .= $k . "=" . urlencode($v) . $sep;
      }
    }
    $qs = substr($qs, 0, -1); /* trim off trailing $sep */
/*
    if (str_len($qs) > 4) {
      $qs .= "&selected_box=reports";
    } else {
      $qs = "?selected_box=reports";
    }
  */
//    echo "qs=".$qs."<br>";
    return $qs; 
  }
  
  function getBlockExter($c_id, $get_dates = true) {
    global $print,  
      $total_quantity, $total_sales, $customer_id, $currencies, 
      $man_cust_list_products, $man_customers_list_query, $products_id,
      $curTitleName, $curTotalQuantity, $curTotalPrice, $curLastOrderDate, 
      /*$start_date, */$has_frozen_orders, $is_week_mode,
      $manufacturer_id;
      
    if ($get_dates) getDates();

    $print = false;
    $total_quantity = 0;
    $total_sales = 0.0;
//    $currencies = 
    $man_cust_list_products = "";
    $man_customers_list_query = "";
    $products_id = "";
    $curTitleName = "";
    $curTotalQuantity = 0;
    $curTotalPrice = 0.0;
    $curLastOrderDate = "";
    $manufacturer_id = "";
    $has_frozen_orders = false;
    $is_week_mode = false;
    
    define("ALL_CUSTOMERS", "");
    define("TEXT_BUTTON_REPORT_PRINT_THIS_CUSTOMER", "");
    define("ENTRY_TOTAL", "Total");
      	
    $customer_id = (int)$c_id;
    $table = "";
    
    if ($customer_id > 0) {
      $table = getBlock(false, false);
      $table = str_replace("</tr><--ADD_HR--></table>", "</table>", $table);
      $table = str_replace("<--ADD_HR-->", "", $table);
    } else {
      $table = "<big>ERROR : CUSTOMER_ID CANNOT BE NULL</big>";
    }
    return $table;

  }

  function boolNumber($bValue = false) {                      // returns integer
    return ($bValue ? 1 : 0);
  }
  
  function boolString($bValue = false) {                      // returns string
    return ($bValue ? 'true' : 'false');
  }  

  function getFirstDayOfMonthFromDT($dt) {
    return getFirstDayOfMonth(date("m", $dt), date("Y", $dt));
  }

  function getLastDayOfMonthFromDT($dt) {
    return getLastDayOfMonth(date("m", $dt), date("Y", $dt));
  }

  function getFirstDayOfMonth($month = "", $year = "") {
    if (!$month) $month = date("m");
    if (!$year) $year = date("Y");
    $day = mktime(0,0,0,$month,1,$year);
    return date("Y-m-d", $day);
  }

  function getLastDayOfMonth($month = "", $year = "") {
    if (!$month) $month = date("m");
    if (!$year) $year = date("Y");
    $day = strtotime("-1 day", mktime(0,0,0,$month+1,1,$year));
    return date("Y-m-d", $day);
  }

  function getPreviousMonth($month = "", $year = "", $nbMonths = 1) {
    if (!$month) $month = date("m");
    if (!$year) $year = date("Y");
    $firstDay = mktime(0,0,0,$month-$nbMonths,1,$year);
    return date("Y-m-d", $firstDay);
  }

  function getNextMonth($month = "", $year = "", $nbMonths = 1) {
    if (!$month) $month = date("m");
    if (!$year) $year = date("Y");
    $firstDay = mktime(0,0,0,$month+$nbMonths,1,$year);
    return date("Y-m-d", $firstDay);
  }

  function getFirstTuesdayOfMonth($month = "", $year = "") {
    if (!$month) $month = date("m");
    if (!$year) $year = date("Y");
    $firstDay = mktime(0,0,0,$month,1,$year);
    while (strtolower(date("l", $firstDay))!='tuesday') {
      $firstDay = strtotime("+1 day", $firstDay);
    }
    return date("Y-m-d", $firstDay);
  }

  function getLastTuesdayOfMonth($month = "", $year = "") {
    if (!$month) $month = date("m");
    if (!$year) $year = date("Y");
    $firstDay = mktime(0,0,0,$month+1,1,$year);
    while (strtolower(date("l", $firstDay))!='tuesday') {
      $firstDay = strtotime("-1 day", $firstDay);
    }
    return date("Y-m-d", $firstDay);
  }

  function getStrtotime($date) {
    if (!is_numeric($date) && ($date != ""))  {
      $date = strtotime($date);  
    }
    return $date;
  }

  function getDay($cur_day = "", $day_name = "tuesday", $dir = "+") {
    if ($cur_day != "") {
      $cur_day = getStrtotime($cur_day);
    } else {
      $cur_day = strtotime(date("Y-m-d"));
    }
    $i = 0;
    $cur_day = strtotime($dir."1 day", $cur_day);
    while ((strtolower(date("l", $cur_day)) != $day_name)&&($i<10)) {
      $cur_day = strtotime($dir."1 day", $cur_day);
      $i += 1;
    }
    return date("Y-m-d", $cur_day);
  }

  function getNextTuesday($cur_day = "") {
    return getDay($cur_day, "tuesday");
  }

  function getNextThursday($cur_day = "") {
    return getDay($cur_day, "thursday");
  }

  function getNextSaturday($cur_day = "") {
    return getDay($cur_day, "saturday");
  }

	function orders_are_frozen($p_id, $m_ga_id = "", $ds = "", $ga = false) {
    // dans la table validation_datetime, on stocke avec des chiffres négatifs les ga_id du groupement d'achat
    // les chiffres positifs (cas standard) sont pour les producteurs de la vente directe

/*
	  echo $ds;
    $ds = getStrtotime($ds);
	  echo $ds;
*/
    $validation_datetime = "";
	  $p_id = tep_get_prid($p_id);
    
    if ($p_id>0) {
      $sql = "SELECT p.manufacturers_id, m.group_id, p.shipping_day, p.shipping_frequency FROM products AS p 
          LEFT JOIN manufacturers AS m ON m.manufacturers_id = p.manufacturers_id 
          WHERE p.products_id = $p_id;";
      $sql_query = tep_db_query($sql);
      if ($sql_result = tep_db_fetch_array($sql_query)) {
        $m_ga_id = $sql_result['manufacturers_id'];
        $ga = ($sql_result['group_id']>0); 
        $sd = $sql_result['shipping_day'];
        $sf = $sql_result['shipping_frequency'];
      }
    }

    if ($ga) {
      if ($m_ga_id == "") $m_ga_id = getGA_ID($sql_result['group_id']);
      if ($ds == "") $ds = getGA_order_date($m_ga_id);
      $m_ga_id = -$m_ga_id;
    } else {
      if ($ds == "") {
        $ds = get_order_date($m_ga_id, $p_id, $sd, $sf);
      }
    }
    
    if ((is_numeric($m_ga_id))&&($ds != "")) {
      $sql = "SELECT validation_datetime FROM manufacturers_validations WHERE manufacturers_id = $m_ga_id AND date_shipped = '$ds' AND validation_datetime <> '0000-00-00' AND validation_datetime IS NOT NULL AND validation_datetime <> '';";
      $sql_query = tep_db_query($sql);
      if ($sql_result = tep_db_fetch_array($sql_query)) {
        $validation_datetime = $sql_result['validation_datetime'];  
      }
    }
    return $validation_datetime; // si on retourne une chaine vide => pas de commandes figées
  }

	function ordersGA_are_frozen($ga_id, $nds) {
	  return orders_are_frozen("", $ga_id, $nds, true); // si on retourne une chaine vide => pas de commandes figées
  }

  function orders_are_frozen_global($group_id, $date_shipped, $p_id = "", $m_id = "", $gaID = "") {
    if (!is_numeric($group_id)) {
      if (is_numeric($p_id)) {
        $sql_query = tep_db_query("SELECT group_id FROM products WHERE products_id = $p_id;");
        if ($sql_result = tep_db_fetch_array($sql_query)) {
          $group_id = $sql_result['group_id']; 
        }
      } else if (is_numeric($m_id)) {
        $sql_query = tep_db_query("SELECT group_id FROM manufacturers WHERE manufacturers_id = $m_id;");
        if ($sql_result = tep_db_fetch_array($sql_query)) {
          $group_id = $sql_result['group_id']; 
        }
      }
    }
//    echo "l".$group_id."l".$date_shipped."l".$p_id."l<br>";
    if ($group_id>0) {
      if ($gaID=="") $gaID = getGA_ID($group_id); 
      return ordersGA_are_frozen($gaID, $date_shipped);        
    } else {
      return orders_are_frozen($p_id, $m_id, $date_shipped);        
    }
  }

	function ordersS_are_frozen($m_id, $ds) {
	  // check if suppliers_orders are validated
    $so_datetime = "";
    if ($m_id>0) {
      $sql = "SELECT supplier_order_datetime FROM manufacturers_validations 
        WHERE manufacturers_id = $m_id AND date_shipped = '$ds' AND supplier_order_datetime IS NOT NULL AND supplier_order_datetime <> '' AND supplier_order_datetime <> '0000-00-00';";
//      echo $sql;
      $sql_query = tep_db_query($sql);
      if ($sql_result = tep_db_fetch_array($sql_query)) {
        $so_datetime = $sql_result['supplier_order_datetime'];  
      }
    }
    return $so_datetime;
  }

  function ops_are_received($m_id, $ds) {
    $res = false;
    $sql_query = tep_db_query("SELECT supplier_order_received FROM orders_products_suppliers
      WHERE manufacturers_id = $m_id AND date_shipped = '$ds';");
    if ($sql_result = tep_db_fetch_array($sql_query)) {
      $res = ($sql_result['supplier_order_received'] == 1);  
    }
    return $res;
  }

  function valid_orders($id, $ga, $dt) {
    global $order_date_to;
    if (orders_are_frozen("", $id, $order_date_to, $ga) == "") {
      if ($dt == "") $dt = date("Y-m-d H:i:s");
      if ($ga) $id = -$id;
			tep_db_query("INSERT INTO manufacturers_validations (manufacturers_id, date_shipped, validation_datetime) 
        VALUES (".$id.",'".$order_date_to."','".$dt."');", 'db_link');
    }
  }

  function can_validate($ds, $ga = false) {
    global $is_week_mode, $forceValidation, $preprod;
    
    if (($forceValidation)||($preprod)) return true;
    
    $res = false;
    if ($is_week_mode) {
      // on peut valider les commandes si sd < ds < ed
      $dt_today = strtotime(date("Y-m-d H:i:s"));
      $dt_order_date = strtotime($ds);
      if (!$ga) {
        $nb = RVD_VALIDATION_LIMIT;
      } else {
        $nb = GA_VALIDATION_LIMIT;
      }

  		$dt_start_date = strtotime("-$nb days 00:00:00", $dt_order_date);
//  		$dt_end_date = strtotime("-1 days 12:00:00", $dt_order_date);
  		if (($dt_today >= $dt_start_date)/*&&($dt_today <= $dt_end_date)*/) {
        $res = true;
  		}
    }
    return $res;
  }

  function getGA_ID($group_id, $newGA_ID = -1, $forceDefaultGA = false) {

    if ($group_id <= 0) {
      global $gaID;
      $gaID = 0;
    } else {
      if ($newGA_ID == "*") $newGA_ID = 1;
      
      if ($newGA_ID>0) {
        $gaID = $newGA_ID;
      } else {
        global $gaID, $adminMode, $customer_id;
  
        if (($gaID<=0)||($adminMode=="")) {
          if (is_numeric($customer_id) && clientCanBuyGA() && ($customer_id>0)) {
            $sql = "SELECT customers_ga_id FROM customers WHERE customers_id = ".$customer_id;
            $c_query = tep_db_query($sql);
            if ($c = tep_db_fetch_array($c_query)) {
              $gaID = $c['customers_ga_id'];
            }
          }
        }
      }
      if (($gaID == "*")||(($forceDefaultGA)&&($gaID < 1))) $gaID = DEFAULT_GA_ID;  // CJ 2010-06-10
    }
    
    return $gaID;
  }

  function getGA_order_date($ga_id = "", $next_next = false) {
    global $shipping_day, $shipping_frequency, $customer_id;

    $cj_order_date = "";
    if ($ga_id != "") {
      // on est en mode admin
      $c_query = tep_db_query("SELECT cga.next_date_shipped, cga.next_next_date_shipped FROM customers_ga AS cga 
                WHERE cga.customers_ga_id = ".$ga_id);
    } else {
      if (is_numeric($customer_id) && clientCanBuyGA() && ($customer_id>0)) {
        $c_query = tep_db_query("SELECT cga.next_date_shipped, cga.next_next_date_shipped FROM customers AS c 
                LEFT JOIN customers_ga AS cga ON c.customers_ga_id = cga.customers_ga_id 
                  WHERE customers_id = ".$customer_id);
      } else if ($ga_id == "") {
        $ga_id = getGA_ID(1, -1, true);
        // on est en mode admin
        $c_query = tep_db_query("SELECT cga.next_date_shipped, cga.next_next_date_shipped FROM customers_ga AS cga 
                  WHERE cga.customers_ga_id = ".$ga_id);
      }
    }
    if ($c = tep_db_fetch_array($c_query)) {
      // on a la date de la prochaine livraison du grpt d'achat
      if ($next_next) {
        $cj_order_date = $c['next_next_date_shipped'];
      } else {
        $cj_order_date = $c['next_date_shipped'];
      }    
    }
    $shipping_day = "saturday";
    $shipping_frequency = 4.0;

    return $cj_order_date;
  }

  function getGA_order_date_next($ga_id = "") {
    return getGA_order_date($ga_id, true);
  }
  
  function get_order_date_arg($myDate, $m_id = "", $p_id = "", $sd = "", $sf = "", $dir = "+") {
    global $shipping_day, $shipping_frequency;
    
    $recalc_sf = "";
    
    $p_id = tep_get_prid($p_id);

    if (($sd == "")||($sf == "")) {
      if ($m_id<=0) {
        $shipping_day = "tuesday|thursday";
        $shipping_frequency = 0.5;
      } else {
        if ((is_numeric($m_id))||(is_numeric($p_id))) {
          // on recherche les données pour le manufacturer (default_shipping_day et default_shipping_frequency)
      		if (is_numeric($m_id)) {
            $query_raw = "SELECT default_shipping_day as shipping_day, default_shipping_frequency as shipping_frequency FROM " . TABLE_MANUFACTURERS . " WHERE manufacturers_id = " . $m_id;
          } else {
            $query_raw = "SELECT shipping_day, shipping_frequency FROM " . TABLE_PRODUCTS . " WHERE products_id = " . $p_id;
          } 
      		$query = tep_db_query($query_raw);
      		$bFound = false;
          if ($result = tep_db_fetch_array($query)) {
        		$bFound = true;
            $shipping_day = $result["shipping_day"];
            $shipping_frequency = $result["shipping_frequency"];
      		}

          if (($shipping_day == "tuesday|thursday") && ($shipping_frequency > 0.5)) {
            $shipping_frequency = 0.5;
            $recalc_sf .= "<br>sf recalculated with m_id=$m_id and p_id=$p_id";  
          }
      		if (!$bFound) {
      		  $recalc_sf .= "<br>record not found with SQL_RAW : $query_raw";
          } 
      	} else {
          $shipping_day = "thursday";
          $shipping_frequency = 1.0;
        }
      }
    } else {
      $shipping_day = $sd;
      $shipping_frequency = $sf;
      if (($shipping_day == "tuesday|thursday") && ($shipping_frequency > 0.5)) {
        $shipping_frequency = 0.5;
        $recalc_sf .= "<br>sf recalculated with m_id=$m_id and p_id=$p_id (sd=$sd and sf=$sf)";
      }  
    }

    if (($shipping_day == "tuesday|thursday") && ($shipping_frequency > 0.5)) {
      $shipping_frequency = 0.5;
      $recalc_sf .= "<br>sf recalculated with m_id=$m_id and p_id=$p_id (sd=$sd and sf=$sf) GLOBAL";
    }  
    
    $dt = strtotime($dir."2 days", strtotime($myDate));
    $day = strtolower(date("l", $dt));

    // les livraisons peuvent être le mardi ou le vendredi
    // si la fréquence de livraison est égale à 0.5, le produit/producteur le livre le mardi et le vendredi
    // sinon, le producteur livre le jour $shipping_day uniquement (toutes les semaines, 2 semaines...)) 
    $i=0;
    while (((($shipping_frequency == 0.5)&&($day != "tuesday")&&($day != "thursday"))|| 
          (($shipping_frequency >= 1)&&($day != $shipping_day)))&&($i<30)) {   // 30 itérations max

      $add_weeks = "";
      if (($i==0)&&($shipping_frequency >= 2)) {
        // on ajoute x semaines pour gérer la fréquence de livraison
        // mais on ne le fait qu'une fois ! 
        $to_add = (int)$shipping_frequency - 1;
        if ($to_add > 0) {
          $add_weeks = " $dir".$to_add." weeks";
        }
      }
      $dt = strtotime($dir."1 day".$add_weeks, $dt);
      $day = strtolower(date("l", $dt));
      $i++;
    }

    if ($i >= 30) {
      // oulah : il y a une erreur dans le calcul !
      // => envoi d'un mail pour débuggage
      $emails = "shipping_day = $shipping_day<br>shipping_frequency = $shipping_frequency<br>product_id = $p_id<br>manufacturer_id = $m_id $recalc_sf"; 
      tep_mail('groms', 'groms@free.fr', 'ERROR WHILE CALCULATING next_order_date - AUFLEURIPOTAGER', $emails, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);

      return "";
    } else {
      return date("Y-m-d", $dt);
    }
  }		

	function get_order_date($m_id = "", $p_id = "", $sd = "", $sf = "") {
		return get_order_date_arg(date("Y-m-d"), $m_id, $p_id, $sd, $sf);
	}

  function setOrderDates() {
    global $order_date_from, $order_date_to, $manufacturer_id, $gaID;
    
    if ($gaID <= 0) {
      $order_date_to = get_order_date($manufacturer_id);
    } else {
      $order_date_to = getGA_order_date($gaID);
    }
    $order_date_from = $order_date_to;
  }

  function getDates() {
    global $HTTP_GET_VARS, $HTTP_POST_VARS, $order_date_from, $order_date_to, 
      $periodName, $periodNames, $is_week_mode, $is_month_mode;

    $setOrderDates = false;
    $periodName = "";
    $is_week_mode = false;
    $is_month_mode = false;

    if (isset($HTTP_POST_VARS['order_date_to'])) {
      $order_date_to = $HTTP_POST_VARS['order_date_to'];
    }
    else if (isset($HTTP_GET_VARS['order_date_to'])) {
      $order_date_to = $HTTP_GET_VARS['order_date_to'];
    }
    else {
      $setOrderDates = true;
    }

    if (isset($HTTP_POST_VARS['order_date_from'])) {
      $order_date_from = $HTTP_POST_VARS['order_date_from'];
    } 
    else if (isset($HTTP_GET_VARS['order_date_from'])) {
      $order_date_from = $HTTP_GET_VARS['order_date_from'];
    } 
    else {
      $setOrderDates = true;
  	}
  	
  	if ($setOrderDates) { setOrderDates(); }
  	
    if ($order_date_from != $order_date_to) {
//      echo $order_date_from.$order_date_to;
      $m1 = date("F", strtotime($order_date_from)); 
      $m2 = date("F", strtotime($order_date_to)); 
      
      if ($m1 == $m2) {
        $periodName = $periodNames[1] . " de " . convertEnglishDateNames_fr($m1);
        $is_month_mode = true;
      } else {
        $periodName = $periodNames[2];
      }
    } else {
      $is_week_mode = true;
      $periodName = $periodNames[0];
    }
  }

  function getBlock($cbp, $adminMode = "yes", $pbm = false) {
    global $print, $total_quantity, $total_sales, $customer_id, $currencies, 
      $man_cust_list_products, $man_customers_list_query, $products_id,
      $curTitleName, $curTotalQuantity, $curTotalPrice, $curLastOrderDate, 
      $is_week_mode, $manufacturer_id, $manufacturer_id_sav, $gaID, 
      $customer_only, $show_stock, $has_frozen_orders, $order_date_to, 
      $supplier_validated, $supplier_order_received,
      $m_id_from_array, $c_id_from_array, $p_id_from_array;
    
    $curTitleName = "";
    $show_print_link = false;
		$total_quantity = 0;
		$total_sales = 0;
    $table = '';
    $has_record = false;
    $supplier_validated = false;
    $supplier_mode = false;
    $supplier_order_received = false;
    if ($adminMode == "yes") {
      $table .= '<tr>
          <td class="main" colspan="2">
          <span class="pageHeadingSmall">';
      if ($cbp) {
        //Customer by product
        $table .= "&nbsp;";
        $products_name = $man_cust_list_products['products_name'];
    		if (($man_cust_list_products['products_options'] != "") && ($man_cust_list_products['products_options_values'] != "")) {
          $products_name .= " (" . $man_cust_list_products['products_options'] . " : " . $man_cust_list_products['products_options_values'] . ")"; }
    		
        $table .= TABLE_PRODUCT_NAME." (n° ". $man_cust_list_products['products_id'] . ")";
        if ($man_cust_list_products['products_reference'] != "") {
          $table .= " <i>(réf. : ".$man_cust_list_products['products_reference'].")</i>";
        }
        $table .= " : </span> ";

        $supplier_validated = (($has_frozen_orders) && (ordersS_are_frozen($man_cust_list_products['manufacturers_id'], $order_date_to) != ""));

        $m_p_name = "";
        if ((!$customer_only)&&($manufacturer_id<=0)&&($m_id_from_array == "")) {
          $m_p_name .= getManufacturerNameLinkForProduct($man_cust_list_products);
        }
        $table .= $m_p_name;
        $curTitleName = $m_p_name.$products_name;
        
        $table .= getProductNameLink($man_cust_list_products['products_id'], $products_name);
        
      } else if ($pbm) {
        //Product by manufacturer
        $supplier_validated = (($has_frozen_orders) && (ordersS_are_frozen($manufacturer_id, $order_date_to)!= ""));
        if ($supplier_validated) {
          $supplier_validated = true;
          $supplier_mode = true;
        }

        if (!$customer_only && ($manufacturer_id_sav <= 0)) {
          $table .= "&nbsp;";
          if (is_numeric($manufacturer_id)) {
            $man_query = tep_db_query("SELECT manufacturers_name, manufacturers_address, manufacturers_email FROM manufacturers WHERE manufacturers_id = $manufacturer_id;");
        		if ($man = tep_db_fetch_array($man_query)) {
        		  $curTitleName = $man['manufacturers_name'];
              $table .= "Producteur (n° $manufacturer_id) : ".$curTitleName."</span>"; 
  
              if (!empty($man['manufacturers_address'])) {
                $table .= " - ".$man['manufacturers_address'];
              }
              if (!empty($man['manufacturers_email'])) {
                if (!$print) {
                  $table .= " (<a href='mailto:".$man['manufacturers_email']."'>".$man['manufacturers_email']."</a>)";
                } else {
                  $table .= " (<b>".$man['manufacturers_email']."</b>)";
                }
              }
        		} else {
              $table .= MANUFACTURER_NOT_FOUND;
            }
          } else {
              $table .= ALL_MANUFACTURERS;
          }
        }

        $table .= '</td>';
        
        ($pbm) ? $cs=6 : $cs=7; 
        $table .= '<td class="smallTextNoBorder" align="right" colspan="'.$cs.'" nowrap><!PRINT_LINK!>';
        $print_link = "";

        if (!$print) {
          $print_link .= '<a href="'.$_SERVER['PHP_SELF'].'?'.querystring_small().'&print=yes&customer_only=yes" target="print">'; 
          $print_link .= 'Imprimer ce producteur</a>';

          if (($has_frozen_orders) && (!$supplier_validated)) {
            $print_link = tep_draw_form('supplier_validation_form', FILENAME_STATS_MANUFACTURERS, querystring_small($manufacturer_id_sav), 'post').'
            <input type="submit" id="submit_btn" value="Etape 2 : Valider la commande fournisseur" style="height: 18px; font-weight: bold; font-size : 12px; "><br>'.$print_link.'
              <input type="hidden" name="action" value="validate_supplier_order">
              <input type="hidden" name="sv_m_id" value="'.$manufacturer_id.'">';
          }
        }
      } else {
        //Product by customer
        //recherche du client dans la base
        if (is_numeric($customer_id)) {
//          $curTitleName = ;
          $curTitleName = tep_address_label($customer_id, 1, true, ' ', ' - ', 6, true);
          $table .= TABLE_CUSTOMER_NAME . " (n° ". $customer_id .") :</span> ";
        }
        else {
          $curTitleName = ALL_CUSTOMERS;
        }
    
        $table .= getCustomerNameLink($customer_id, $curTitleName). '</td>';
        
        $table .= '<td class="smallTextNoBorder" align="right" colspan="6" nowrap><!PRINT_LINK!>';
        if (!$print) {
          $print_link .= '<a href="'.$_SERVER['PHP_SELF'].'?'.querystring_small().'&print=yes&customer_only=yes" target="print">'; 
          $print_link .= TEXT_BUTTON_REPORT_PRINT_THIS_CUSTOMER."</a>";
        }
      }
      $table .= '
            </td>
          </tr>';
    }
    
		if ($cbp) {
      // =======================================================================================================================================================================    
      // list of all products bought for manufucter_id and product_id
      // =======================================================================================================================================================================
      $sql_query_raw = getMySQLraw("cp", 
            $man_cust_list_products['products_name'], 
            $man_cust_list_products['products_options'], 
            $man_cust_list_products['products_options_values']);
    }
    else if ((!$supplier_mode)&&(!$cbp)) {
      // =======================================================================================================================================================================    
      // list of all products bought for manufucter_id and customer_id
      // =======================================================================================================================================================================    
  		$sql_query_raw = getMySQLraw("p", "", "", "", false, $adminMode);
    } else if ($supplier_mode) { 
      // =======================================================================================================================================================================    
      // list of all products ordered to suppliers (after order reception)
      // use of new table : orders_products_suppliers
      // =======================================================================================================================================================================    
      $sql_query_raw = addArrayFilters("SELECT ops.supplier_order_received, p.is_bulk, p.measure_unit, p.manufacturers_id, p.products_id, ops.products_name, ops.new_products_quantity AS products_quantity, ops.new_final_price AS final_price, p.products_quantity AS products_stock FROM orders_products_suppliers AS ops
        LEFT JOIN products AS p ON p.products_id = ops.products_id
        WHERE ops.manufacturers_id = $manufacturer_id AND ops.date_shipped = '$order_date_to' ORDER BY ops.products_name;");
    } 
//    echo $order_date_to;
    
    $man_cust_products_query = tep_db_query($sql_query_raw);
    while ($man_cust_products = tep_db_fetch_array($man_cust_products_query)) {
      $has_record = true;

  		if ($cbp) {
  			$customer_name = $man_cust_products['customers_name'];
      }
      else {
  			if ($supplier_mode) {
  			  $products_name = $man_cust_products['products_name'];
  			  $supplier_validated = true;
  			  $supplier_order_received = ($man_cust_products['supplier_order_received'] == 1); 
        } else {
//          $supplier_validated = (($has_frozen_orders) && (ordersS_are_frozen($man_cust_products['manufacturers_id'], $order_date_to)!= ""));

          if ($adminMode != "yes") {
            // en mode non admin, on n'affiche les produits uniquement s'ils ont été validés
            if (orders_are_frozen_global("", $man_cust_products['date_shipped'], $man_cust_products['products_id']) == "") {
              continue;
            }            
            
          }

          $products_model = $man_cust_products['products_model'];	
    			$products_name = "";

          if ($m_id_from_array == "") {
            $man_name = getManufacturerNameLinkForProduct($man_cust_products);   // "manufacturer_name // "
          }  
    			
          $products_name .= $man_cust_products['products_name'];
          if ($man_cust_products['products_reference'] != "") {
            $products_name .= " <i>(réf. : ".$man_cust_products['products_reference'].")</i>";
          }
    			if ($man_cust_products['products_options_values'] != "") {$products_name .= " (" . $man_cust_products['products_options'] . " : " . $man_cust_products['products_options_values'] . ")"; }
    			
        }
      }	

      $is_recurrent_order = (($man_cust_products['os'] == 4)||($man_cust_products['os'] == -1));
      $products_stock = tep_format_qty_for_db($man_cust_products['products_stock']);
			if ($supplier_mode) {
			  $qty_to_order = $man_cust_products['products_quantity'];
			  $opm_id = "sm_".$man_cust_products['products_id'];
      } else {        
        $opm_id = $man_cust_products['orders_products_modifications_id'];
        $min_qty = tep_format_qty_for_db($man_cust_products['products_min_manufacturer_quantity']);
        // récupération de laquantitéà commander, suivant le stock et l'achat mini fournisseur
        if ($products_stock >= 0) {
          // le produit est encore en stock, il n'a pas besoin d'être commandé
          $qty_to_order = 0;
        } else if ($min_qty>0) {
          $int = (int)($products_stock/$min_qty);
          if ($int * $min_qty == $products_stock) {
            // le stock est un multiple du conditionnement => on commande laquantitéexacte
            $qty_to_order = -$int * $min_qty;
          } else {
            // le stock est un multiple du conditionnement => on commande laquantité+1
            $qty_to_order = (-$int + 1) * $min_qty;
          }
        } else {
          $qty_to_order = -$products_stock;
        }
      }
      
      if ((!$print)||(($print)&&((($qty_to_order > 0)&&($customer_id == "all_by_man"))||($customer_id != "all_by_man")))) {
        $single_price = $man_cust_products['final_price'];
  			if ($supplier_mode) {
          $products_quantity = $man_cust_products['products_quantity'];
  			} else {
          $products_quantity = $man_cust_products['sum_pq'];
//     			$final_price = $man_cust_products['sum_fp'];
    		}
   			$final_price = $products_quantity * $single_price;
  			
        $tr_class = "dataTableRow";
        if ($man_cust_products['orders_status'] == 4) {
          $tr_class .= "Rec";
        }
        if ((($customer_id != 'all_by_man')&&($products_quantity == 0)) || 
            (($customer_id == 'all_by_man')&&($qty_to_order == 0)&&($has_frozen_orders)) ) {
          $tr_class .= "Strike";
        }
  
        $table .= '
            <tr class="'.$tr_class.'">';

        $dp = getShortDate($man_cust_products['dp'], $man_cust_products['recurrence_accepted_datetime']);
        $curLastOrderDate = $dp;
  
        // récupération de laquantitéde mesure, le cas échéant (is_bulk=1)
        $unit = "";
        $unit_to_order = "";
        if (($man_cust_products['is_bulk']>0)&&($man_cust_products['measure_unit']!="")) {
          $unit = " ".$man_cust_products['measure_unit'].(tep_format_qty_for_db($products_quantity)>1 ? "s" : "");
        }
        if (($man_cust_products['is_bulk']>0)&&($man_cust_products['measure_unit']!="")) {
          $unit_to_order = " ".$man_cust_products['measure_unit'].(tep_format_qty_for_db($qty_to_order)>1 ? "s" : "");
        }
  

        if (($customer_id != 'all_by_man')||(!$has_frozen_orders)) {
          // 1ère colonne : quantité
          $table .= '
              <td class="dataTableContent" align="right" nowrap><big><b>'.tep_format_qty_for_html($products_quantity).$unit.'</b></big>&nbsp;</td>';
        } else {
          $t_txt = "";
          if ($qty_to_order>0) {
            $show_print_link = true;
            if (!$supplier_validated) {
              $t_txt = "Quantité à commander à ".$man_cust_products['manufacturers_name'];
            } else if ($supplier_validated && !$supplier_order_received) {
              $t_txt = "Quantité commandée à ".$man_cust_list_products['manufacturers_name'];
            } else if ($supplier_order_received) {
              $t_txt = "Quantité reçue de ".$man_cust_list_products['manufacturers_name'];
            }
          }
          $table .= '
              <td class="dataTableContent" align="right" title="'.$t_txt.'" nowrap><big><b>'.tep_format_qty_for_html($qty_to_order).$unit_to_order.'</b></big>&nbsp;</td>';
        }

        $table .= '
              <td class="dataTableContent" width="100%" align="left" nowrap>';

    		if ($cbp) {
          $table .= getCustomerNameLink($man_cust_products['customers_id'], $customer_name);
        }
        else {
          $table .= "&nbsp;&nbsp;&nbsp;";
          if (($customer_id != 'all_by_man')&&($manufacturer_id<=0)) {
            $table .= $man_name;
          }
          
          if ($man_cust_products['products_id']>0) {
            $table .= getProductNameLink($man_cust_products['products_id'], $products_name);
          } else {
            $table .= '<span class"messageStackError">ERROR: product_id is NULL (opm_id='.$man_cust_products['orders_products_modifications_id'].', op_id='.$man_cust_products['orders_products_id'].')</span>';
          }
        }                               
  
        if (($customer_id != 'all_by_man')&&($adminMode == "yes")) {
          // 2ème colonne : date d'achat
          $table .= '
                </td>
                <td class="dataTableContent" align="center">&nbsp;'.$dp.'&nbsp;</td>';
        }


        if ($show_stock==1) {
          // 3ème colonne : stock restant
//          if (($supplier_order_received)||($has_frozen_orders && !$supplier_validated)) {
            if ($supplier_order_received) {
              $t = "3 : Stock APRÈS réception commande fournisseur (modifiable si réception fournisseur différente de la commande)";
            } else if (!$has_frozen_orders) {
              $t = "0 : Stock AVANT prise en compte des commandes adhérents (avant validation de la commande)";
            } else if (!$supplier_validated) {
              $t = "1 : Stock AVANT envoi commande fournisseur (après prise en compte des commandes adhérents)";
            } else  {
              $t = "2 : Stock PREVISIONNEL après livraison aux adhérents (en supposant que le fournisseur nous envoie bien ".tep_format_qty_for_html($qty_to_order)." produits)";
            }
            
            if ($products_stock < 0) {
              $c_txt = "messageStackErrorBig";  
            } else if ($products_stock == 0) {
              $c_txt = "messageStackWarningBig";  
            } else {
              $c_txt = "messageStackSuccessBig";  
            }    

            $stock_unit = "";
            if (($man_cust_products['is_bulk']>0)&&($man_cust_products['measure_unit']!="")) {
              $stock_unit = " ".$man_cust_products['measure_unit'].(tep_format_qty_for_db($products_stock)>1 ? "s" : "");
            }

            $table .= '
                <td class="dataTableContent" align="right" nowrap><span class="'.$c_txt.'" title="'.$t.'">&nbsp;'.tep_format_qty_for_html($products_stock).$stock_unit.'&nbsp;</span>'.$edit.'</td>';
/*
          } else {
            $table .= '
                <td class="dataTableContent" align="right" nowrap>&nbsp;</td>';
          }
*/
        }
        if ($customer_id == 'all_by_man') {
          // 4ème colonne : prix unitaire
          $table .= '
              <td class="dataTableContent" align="center" nowrap>&nbsp;'.$currencies->format($single_price).'&nbsp;</td>';
        }

        if ($adminMode == "yes") {
          // 5ème colonne : quantité client ou quantité fournisseur
          if (($customer_id != 'all_by_man')||(!$has_frozen_orders)) {
            //quantitéclient
            if ($products_quantity>0) $show_print_link = true;
            $table .= '
                <td class="dataTableContent" align="right" nowrap><span title="Quantité commandée par l\'adhérent">&nbsp;'.tep_format_qty_for_html($products_quantity).$unit.'&nbsp;</span></td>';
          } else {
            //quantitéfournisseur
            $table .= tep_draw_hidden_field('qty_to_order'.$man_cust_products['manufacturers_id']."_".$man_cust_products['products_id'], $qty_to_order);
            $table .= '
                <td class="dataTableContent" align="right" nowrap title="'.$t_txt.'">&nbsp;<b>'.tep_format_qty_for_html($qty_to_order).$unit_to_order.'</b>&nbsp;</td>';
          }
        }
      
        // 5ème colonne : coût total du produit
        if (($customer_id != 'all_by_man')||(!$has_frozen_orders)) {
          $table .= '
              <td class="dataTableContent" align="right">&nbsp;'.$currencies->format($final_price).'&nbsp;</td>';
        } else {
          $table .= '
              <td class="dataTableContent" align="right">&nbsp;'.$currencies->format($single_price*$qty_to_order).'&nbsp;</td>';
        }
  
        if ($adminMode == "yes") {
          // 6ème colonne : M/S
          if((!$print)/*&&($customer_id != "all_by_man")*/&&($customer_id != "all")&&(!$cbp)&&($is_week_mode)){
            $check = (($gaID <= 0) || (($gaID > 0) && !($supplier_validated && !$supplier_order_received)));
            
            $table .= '  <td class="dataTableContent" align="right">';
            if ($check) {         
              $table .=      setLinkShowHide($opm_id, tep_image(DIR_WS_IMAGES . 'icons/b_edit.png', 'Editer', '16', '16'));
            } else {
              $table .= tep_draw_separator('pixel_trans.gif', 16, 16);
            }
            $table .= '  </td>';        
            if (($products_quantity > 0) && $check && ($customer_id != "all_by_man")) {
              $table .= '<td class="dataTableContent" align="right">'.setLinkDeleteProduct($opm_id, tep_image(DIR_WS_IMAGES . 'icons/cross.gif', 'Supprimer', '16', '16')).'</td>';
            } else {
              $table .= '<td class="dataTableContent" align="right">'.tep_draw_separator('pixel_trans.gif', 16, 16).'</td>';
            }
          }
        }
        $table .= '</tr>';
  
        if ((!$print)&&($customer_id != "all")&&($adminMode == "yes")) {
          $table .= getProductModDiv($opm_id, $customer_id, 
              $man_cust_products['products_id'], $order_date_to, 
              $products_quantity, $single_price,  
              $products_stock, $supplier_validated, $supplier_order_received);
        }
  
  			if (($customer_id != 'all_by_man')||(!$has_frozen_orders)) {
          $total_quantity += $products_quantity;
    			$total_sales += $final_price;
        } else {
          $total_quantity += $qty_to_order;
    			$total_sales += $single_price*$qty_to_order;
        }
      }      


		} // fin du while

    $curTotalQuantity = $total_quantity;
    $curTotalPrice = $total_sales;

    if ($has_record) {
      if ((!$print)&&($has_frozen_orders)) $table .= '</form>';
      $table .= '
            <tr>';
/*
      if ($adminMode == "yes") {
      }
*/
      if ($customer_id != 'all_by_man') {
        $table .= '
              <td class="dataTableTotalRow" colspan="2" align="right">'.ENTRY_TOTAL.'&nbsp;</td>';
      } else {
        $table .= '
              <td class="dataTableTotalRow" align="right">&nbsp;</td>';
      }
      if (($customer_id != 'all_by_man')&&($adminMode == "yes")) { 
        // date de commande
        $table .= '
          <td class="dataTableTotalRow" align="center" nowrap>&nbsp;</td>';
      }

      if ($show_stock==1) {
        // stock restant
        $table .= '
              <td class="dataTableTotalRow" align="right">&nbsp;</td>';
      }
      if ($customer_id == 'all_by_man') {
        // prix unitaire
        $table .= '
              <td class="dataTableTotalRow" align="right">&nbsp;</td>';
      }
      if ($adminMode == "yes") {
        if ($customer_id != 'all_by_man') {
          //quantité totale 'uniquement pour le mode adhérent'
          $table .= '
                <td class="dataTableTotalRow" align="right">&nbsp;'.tep_format_qty_for_html($curTotalQuantity).'&nbsp;</td>';
        } else {
          $table .= '
                <td class="dataTableTotalRow" align="right">&nbsp;</td>';
        }
      }
    
      $table .= '
            <td class="dataTableTotalRow" align="right">&nbsp;'.$currencies->format($curTotalPrice).'</td>';

      if(($adminMode == "yes")&&(!$print)/*&&($customer_id != "all_by_man")*/&&(!$cbp)&&($customer_id != "all")&&($is_week_mode)){
        // M/S
        $table .= '
            <td class="dataTableTotalRow" align="right" colspan="2">&nbsp;</td>';
      }          

      $table .= '</tr><--ADD_HR-->';
      if (($supplier_validated)&&(!$print)&&($customer_id=="all_by_man")) {
      
        $step_nb = "Etape 2 : Commande fournisseur validée";
        $cancel_supplier_validation = '<a href="'.tep_href_link(FILENAME_STATS_MANUFACTURERS, querystring_small($manufacturer_id_sav).'&remove_manufacturer_validation=true&rmv_m_id='.$manufacturer_id, 'NONSSL').'">'.
                tep_image(DIR_WS_ICONS . 'cross.gif', '').'</a><br>';
        $sor_button = "";
        if (($supplier_validated) && (!$supplier_order_received)) {
          $sor_button = tep_draw_form('supplier_reception_form', FILENAME_STATS_MANUFACTURERS, querystring_small($manufacturer_id_sav), 'post').'
            <input type="submit" id="submit_btn" value="Etape 3 : Valider la réception" style="height: 18px; font-weight: bold; font-size : 12px; ">
            <input type="hidden" name="action" value="validate_supplier_order_received">
            <input type="hidden" name="sv_m_id" value="'.$manufacturer_id.'"></form><br>';
        } else if (($supplier_validated) && ($supplier_order_received)) {
          $cancel_supplier_validation = "<br>";
          $step_nb = "Etape 3 : Commande fournisseur reçue";
        }
      
        $print_link = '<span class="messageStackSuccess">'.$step_nb.'</span>'.$cancel_supplier_validation.$sor_button.$print_link;

      } else if (!$show_print_link) { 
        $print_link = '<span class="messageStackWarning">Rien à commander</span>'; }
      $table = str_replace('<!PRINT_LINK!>', $print_link, $table);
    } else {
      $table = "";
    }

    return $table;
  }

  function getProductsByCustomerBlock() {
    return getBlock(false);
  }

  function getCustomersByProductBlock() {
    return getBlock(true);
  }

  function getProductsByManufacturerBlock() {
    return getBlock(false, "yes", true);
  }
    
  function setLinkShowHide($i, $toEcho) {
    return '<a href="javascript:showhideMod(\''.$i.'\');">'.$toEcho.'</a>';
  }

  function setLinkDeleteProduct($id, $toEcho) {
    return '<a href="javascript:delProduct(\''.$id.'\');">'.$toEcho.'</a>';
  }
  function setLinkValid($id, $toEcho) {
    return '<a href="javascript:validMod(\''.$id.'\');">'.$toEcho.'</a>';
  }
  
  function getProductModDiv($opm_id, $c_id, $p_id, $ds, $qty, $price, 
              $stock = "", $supplier_validated = false, $supplier_order_received = false) {

    global $customer_id, $show_stock, $has_frozen_orders;
    
    $check_stock = (($show_stock)&&(!($supplier_validated && !$supplier_order_received))); 
    $check_qty = ((is_numeric($customer_id) && ($customer_id!='all_by_man'))||(($customer_id=='all_by_man')&&($supplier_order_received)));
    $check_PU = ((is_numeric($customer_id) && ($customer_id!='all_by_man'))||(($customer_id=='all_by_man')&&(!$has_frozen_orders || ($has_frozen_orders && ($supplier_order_received || !$supplier_validated)))));

    $div = '
      <tr>
        <td colspan="7">'.
          tep_draw_hidden_field('c_id'.$opm_id, $c_id, 'id="c_id'.$opm_id.'"').
          tep_draw_hidden_field('p_id'.$opm_id, $p_id, 'id="p_id'.$opm_id.'"').
          tep_draw_hidden_field('ds'.$opm_id, $ds, 'id="ds'.$opm_id.'"').'
      <div id="mod'.$opm_id.'" style="display:none;">
            <table width="100%" border="0" cellpadding="0" cellspacing="0">
              <tr>
                <td>&nbsp;</td>
                <td class="dataTableContent" align="center">';

                  if ($check_stock) $div .= '<b>Stock&nbsp;restant</b>';

    $div .= '   </td>
                <td class="dataTableContent" align="center">';

                  if ($check_qty) $div .= '<b>Qté</b>';

    $div .= '   </td>
                <td class="dataTableContent" align="center">';

                  if ($check_PU) $div .= '<b>P.U.</b>';
    
    $div .=     '</td>
                <td>&nbsp;</td>
              </tr>
              <tr><td class="dataTableContent" width="100%" align="center">';

    if (($customer_id=='all_by_man')&&((!$supplier_validated)||($supplier_order_received))) {
      $div .= '<span class="messageStackWarning"><b>ATTENTION :</b> si vous modifiez le <b>P.U.</b>, toutes les commandes de cette livraison seront affectées.<br>Le prix du produit sera modifié également.</span>';
    } /*else if (($supplier_validated)&&($customer_id!='all_by_man')) {
      $div .= '<span class="messageStackWarning"><b>ATTENTION :</b> la commande fournisseur est validée pour ce produit, le <b>stock</b> ne sera donc <b>pas mis à jour</b>.</span>';
    }
*/
    
    $div .= '   </td><td class="dataTableContent" align="right">';

    if ($check_stock) {
      // stock
      ($stock==0) ? $stock_html = "0,0" : $stock_html = tep_format_qty_for_html($stock); 
      $div .= tep_draw_input_field('product_stock'.$opm_id, $stock_html, 'size="5" align="right" id="product_stock'.$opm_id.'" onchange="disableEdit(\'product_quantity'.$opm_id.'\');"');
    } else {
      $div .= tep_draw_hidden_field('product_stock'.$opm_id, $stock, 'id="product_stock'.$opm_id.'"');
    }

    $div .= '   </td>
                <td class="dataTableContent" align="right">';

    if ($check_qty) {
      // qty
      ($qty==0) ? $qty_html = "0,0" : $qty_html = tep_format_qty_for_html($qty); 
      $div .= tep_draw_input_field('product_quantity'.$opm_id, $qty_html, 'size="5" align="right" id="product_quantity'.$opm_id.'" onchange="disableEdit(\'product_stock'.$opm_id.'\');"');
    } else {
      $div .= tep_draw_hidden_field('product_quantity'.$opm_id, $qty, 'id="product_quantity'.$opm_id.'"');
    }
    
    $div .= '   </td>
                <td class="dataTableContent" align="right">';

    if ($check_PU) {
      ($price==0) ? $price_html = "0,0" : $price_html = tep_format_qty_for_html($price); 
      $div .= tep_draw_input_field('product_price'.$opm_id, $price_html, 'size="5" align="right" id="product_price'.$opm_id.'"');
    } else {
      $div .= tep_draw_hidden_field('product_price'.$opm_id, $price, 'id="product_price'.$opm_id.'"');
    }

    $div .= '   </td>
                <td class="dataTableContent" align="right">'.setLinkValid($opm_id, tep_image(DIR_WS_IMAGES . 'icons/tick.gif', 'Valider', '16', '16')).'</td>
              </tr>
            </table>
          </div>
        </td>
      </tr>';
      
    return $div;
  }
  
  function delOrderProduct($md_opm_id, $md_c_id, $md_p_id, $md_ds, $price) {
    return modOrderProduct($md_opm_id, $md_c_id, $md_p_id, $md_ds, 0, $price);
  }

  function modOrderProduct($md_opm_id, $md_c_id, $md_p_id, $md_ds, $qty, $price, $stock = "", $md_supplier_mode = false, $adminMode = true) {
    global $customer_id;
    $qty = tep_format_qty_for_db($qty);
    $qty_sav = $qty;
    $price = tep_format_qty_for_db($price);
    if ($stock != "") $stock = tep_format_qty_for_db($stock); 

    if ($adminMode) {
      $t_who = "producteur";
    } else {
      $t_who = "vous-même";
    }

    $orders_added = array();
    
    $md_p_id = tep_get_prid($md_p_id);
    $m_id = "";

/*
    if ($customer_id == 'all_by_man') {
      // on est en mode admin, avec lal iste des produits par fournisseur
      // on peut modifier le stock et le P.U.

      // mise à jour du stock
      tep_update_stock($md_p_id, $qty, "", true); // 4th param : override = true
      return true;
    } else {
*/      

      if ($md_supplier_mode) {
        // on vient du mode supplier (all_by_man) => on recherche tous les enreg où il y a le produit $md_p_id
        // 3 valeurs sont modifiables : products_quantity, products_stock et final_price
        // suivant l'état de la commande fournisseur, on ne procède pas à la même modif de table
        $sql = "SELECT p.products_quantity AS products_stock, opm.orders_products_id, opm.manufacturers_id, opm.orders_id, pd.products_name, 
                    opm.products_options, opm.products_options_values, 
                    opm.is_recurrence_order, opm.orders_products_modifications_id, opm.final_price, opm.customers_ga_id, opm.products_quantity, 
                    opm.customers_id, o.orders_status, opm.group_id 
                FROM orders_products_modifications AS opm 
                LEFT JOIN products AS p ON p.products_id = opm.products_id
                LEFT JOIN products_description AS pd ON p.products_id = pd.products_id
                LEFT JOIN orders AS o ON opm.orders_id = o.orders_id
                WHERE opm.facturate='Y' AND opm.products_id='".$md_p_id."' AND opm.date_shipped='".$md_ds."';";
        //opm.products_quantity>0 AND 
    	} else {
    		// on récupère UN SEUL enreg dans opm, celui provenant du champ en modif 
        // il peut y avoir plusieurs commandes impactées mais bon le fait d'en modifier qu'une seule est suffisant
        // les autres commandes sont mises à qty=0 
    	  $sql = "SELECT opm.orders_products_id, opm.manufacturers_id, opm.orders_id, pd.products_name, opm.products_options, opm.products_options_values, 
                    opm.is_recurrence_order, opm.final_price, opm.customers_ga_id, opm.customers_id, opm.products_quantity, o.orders_status, opm.group_id  
                FROM orders_products_modifications AS opm 
                LEFT JOIN products_description AS pd ON opm.products_id = pd.products_id
                LEFT JOIN orders AS o ON opm.orders_id = o.orders_id
                WHERE opm.orders_products_modifications_id = ".(int)$md_opm_id.";";
      }
      $mydatetime = date("Y-m-d H:i:s");
      $query = tep_db_query($sql);
  		while ($record = tep_db_fetch_array($query)) {   // en mode non cbp, le while équivaut à un if (1 seul enreg)
        $old_final_price = $record['final_price'];
     		$old_stock = $record['products_stock'];
     		$m_id = $record['manufacturers_id'];

        if (!$md_supplier_mode) {
          // mode non admin ou mode admin (all_by_once) : on mode les quantités ou produits d'un client et d'un produit donnée
        	if ($adminMode) {
          	// on est en mode admin
            // on récupère tous les enreg pour avoir la somme des quantités pour les commandes qui vont être modifiées
            $old_qty = 0; // début de l'itération pour le calcul des quantités 
            $sql_mod = "SELECT opm.orders_products_modifications_id, opm.products_quantity, opm.orders_products_id, opm.orders_id, opm.is_recurrence_order,  
                pd.products_name, opm.products_options, opm.products_options_values, opm.final_price, opm.customers_ga_id, opm.manufacturers_id, o.orders_status, opm.group_id 
                FROM orders_products_modifications AS opm 
                LEFT JOIN products_description AS pd ON opm.products_id = pd.products_id
                LEFT JOIN orders AS o ON opm.orders_id = o.orders_id
                WHERE opm.facturate='Y' AND opm.customers_id='".$md_c_id."' AND opm.products_id='".$md_p_id."' AND opm.date_shipped='".$md_ds."';";
            // j'ai viré le 11/09/2010 : opm.products_quantity>0 AND 
            
//            echo $sql_mod;exit;
            $query_mod = tep_db_query($sql_mod);
          	while ($record_mod = tep_db_fetch_array($query_mod)) {
              $old_qty += $record_mod['products_quantity'];  // récupération de la somme des quantités
    
          		// on met à 'facturate=N' l'opm pour laquelle on va changer le facturate
          		$sql2 = "UPDATE orders_products_modifications SET facturate='N' WHERE orders_products_modifications_id='".$record_mod['orders_products_modifications_id']."';";
              tep_db_query($sql2);
    
           	  // historique de commande
              if (!in_array($record_mod['orders_id'], $orders_added)) {
            		// on ajoute un enreg dans opm avec la quantité qui vaut 0.0 !
            		// sauf pour la commande pour laquelle on va rajouter le nouvel enreg opm avec les nvelles valeurs $qty et $price
            		if ($record['orders_products_id']!=$record_mod['orders_products_id']) {
                  $sql_insert = "
                    INSERT INTO orders_products_modifications (orders_products_modifications_datetime, date_shipped, orders_products_id, orders_id, 
                      customers_id, is_recurrence_order, manufacturers_id, products_id, products_name, products_options, products_options_values,
                      final_price, products_quantity, customers_ga_id, group_id) 
                    VALUES ('".$mydatetime."','".$md_ds."','".$record_mod['orders_products_id']."','".$record_mod['orders_id']."','".$md_c_id."','".$record_mod['is_recurrence_order']."','".$record_mod['manufacturers_id']."',
                        '".$md_p_id."','".tep_db_input($record_mod['products_name'])."','".tep_db_input($record_mod['products_options'])."','".tep_db_input($record_mod['products_options_values'])."',
                        '".$record_mod['final_price']."','0.0','".$record_mod['customers_ga_id']."','".$record_mod['group_id']."');";
                  tep_db_query($sql_insert);
                }
  
                if (($record_mod['final_price'] != $price)||($record_mod['products_quantity'] != $qty)) {
                  $qty_txt = "";
                  $price_txt = "";
                  if ($record_mod['products_quantity'] != $qty) {
                    $qty_txt = ", quantité avant = ".tep_format_qty_for_html($record_mod['products_quantity']).", quantité après = $qty";
                  } 
                  if ($record_mod['final_price'] != $price) {
                    $price_txt = ", prix avant = ".tep_format_qty_for_html($record_mod['final_price']).", prix après = $price";
                  }
                  
                  // ajout effectif de l'enreg dans la table d'historiques
                  $sql_add = "INSERT INTO " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments) VALUES ('".$record_mod['orders_id']."','".$record_mod['orders_status']."','$mydatetime','0',
                    'modification quantités et/ou prix par $t_who dans opm (opm=".$record_mod['orders_products_modifications_id'].", produit=\'".tep_db_input(tep_get_products_name($md_p_id))."\'".$qty_txt.$price_txt.")');";
                  tep_db_query($sql_add, 'db_link');
                }
  
                $orders_added[] = array('id' => $record_mod['orders_id'], 'is_rec' => $record_mod['is_recurrence_order']);
              }
  
          	}
          } else {
            // en mode non admin, un seul enreg à modifier : celui correspondant à $md_opm_id
            $old_qty = $record['products_quantity'];
  
            if (($old_final_price == $price)&&($old_qty == $qty)) return true; // rien à faire, tout est pareil ! 
  
            // ajout de l'historique
            $qty_txt = "";
            $price_txt = "";
            if ($old_qty != $qty) {
              $qty_txt = ", quantité avant = ".tep_format_qty_for_html($old_qty).", quantité après = $qty";
            } 
            if ($old_final_price != $price) {
              $price_txt = ", prix avant = ".tep_format_qty_for_html($old_final_price).", prix après = $price";
            }
            
            // ajout effectif de l'enreg dans la table d'historiques
            $sql_add = "INSERT INTO " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments) VALUES ('".$record['orders_id']."','".$record['orders_status']."','$mydatetime','0',
              'modification quantités et/ou prix par $t_who dans opm (opm = ".$md_opm_id.", produit = \'".tep_db_input(tep_get_products_name($md_p_id))."\'".$qty_txt.$price_txt.")');";
            tep_db_query($sql_add, 'db_link');
          }
  
        } // fin de else, pour le if ($md_supplier_mode)
  
        if ((($md_supplier_mode)&&($old_final_price != $price)&&($m_id>0))||        // tester m_id permet de s'assurer qu'on est bien allé dans la boucle while
            ((!$md_supplier_mode)&&(($old_final_price != $price)||(($old_qty != $qty))))) {
       
          // on ajoute l'enreg avec les nouvelles valeurs $qty et $price pour l'op_id
      		// avant, on met à 'facturate=N' l'opm pour laquelle on va rajouter l'enreg
      		$sql2 = "UPDATE orders_products_modifications SET facturate='N' WHERE date_shipped = '$md_ds' AND orders_products_id='".$record['orders_products_id']."';";
          tep_db_query($sql2);
          
          if ($md_supplier_mode) {
            // correction d'un gros bug : on mode fournisseur, la quantité ne doit pas être changée (car la quantité qui est dans $qty correspond à un enreg de la table ops)
            $qty = $record['products_quantity']; 
          }
          
          $sql3 = "
            INSERT INTO orders_products_modifications (orders_products_modifications_datetime, date_shipped, orders_products_id, orders_id, customers_id, is_recurrence_order, 
              manufacturers_id, products_id, products_name, products_options, products_options_values,
              final_price, products_quantity, customers_ga_id, group_id) 
            VALUES ('".$mydatetime."','".$md_ds."','".$record['orders_products_id']."','".$record['orders_id']."','".$record['customers_id']."','".$record['is_recurrence_order']."','".$record['manufacturers_id']."',
                '".$md_p_id."','".addslashes_once($record['products_name'])."','".addslashes_once($record['products_options'])."','".addslashes_once($record['products_options_values'])."',
                '".tep_format_qty_for_db($price)."','".tep_format_qty_for_db($qty)."','".$record['customers_ga_id']."','".$record['group_id']."');";
          $query3 = tep_db_query($sql3);

          if ($md_supplier_mode) {
        	  // order_total
            calcul_ot_one($record['orders_id'], $record['is_recurrence_order']==1);

        	  // historique de commande
            if (!in_array($record['orders_id'], $orders_added)) {
              $sql_add = "INSERT INTO " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments) VALUES ('".$record['orders_id']."','".$record['orders_status']."','$mydatetime','0',
                  'modification prix par $t_who dans opm (opm=".$record['orders_products_modifications_id'].", produit=\'".tep_db_input(tep_get_products_name($md_p_id))."\', prix avant = ".tep_format_qty_for_html($old_final_price).", prix après = ".tep_format_qty_for_html($price).")');";
              tep_db_query($sql_add, 'db_link');
              $orders_added[] = $record['orders_id'];
            }
          } 
        }
  
        if (!$md_supplier_mode) {
    			// pour chacune des commandes modifiées, on recalcule l'order_total
    			$orders_added[] = array('id' => $record['orders_id'], 'is_rec' => $record['is_recurrence_order']);
    			
          for ($i=0;$i<count($orders_added);$i++) {
         	  // order_total
            calcul_ot_one($orders_added[$i]['id'], $orders_added[$i]['is_rec']==1); 
          }
          //mise à jour du stock, uniquement si la commande est validée
          // il faut d'abord vérifier l'état de validation de la commande
          if (orders_are_frozen_global($record['group_id'], $md_ds, $md_p_id, $record['manufacturers_id'], $record['customers_ga_id']) != "") {
            // les commandes sont validées => on met à jour
            tep_update_stock($md_p_id, $qty-$old_qty);
          }
        } 
      } // end while

      if ($m_id>0) {    // on verrouille : tester m_id permet de s'assurer qu'on est bien allé dans la boucle while
        if ($md_supplier_mode) {
          //mise à jour du stock
          $qty = $qty_sav; // hé oui, encore un bug de corrigé : 15/09/2010

          if ($old_stock != $stock) {
            $new_stock = $stock; 
          } else {
            // il faut récupérer l'ancienneté quantité dans la table ops
            $old_qty = "";
            $sql_ops = "SELECT new_products_quantity FROM orders_products_suppliers WHERE manufacturers_id = $m_id AND products_id = $md_p_id AND date_shipped = '$md_ds'"; 
            $query_ops = tep_db_query($sql_ops);
          	if ($record_ops = tep_db_fetch_array($query_ops)) {
              $old_qty = $record_ops['new_products_quantity'];
          	}
            if ($old_qty != "") {
          	  // on doit utiliser laquantitéde la table ops pour modifier le stock
          	  $new_stock = $old_stock + $qty - $old_qty;
            } else {
          	  // erreur, car on est en mode supplier !
          	  echo tep_error_message(false, 'Cannot find qty in ops table for p_id = '.$md_p_id);
          	  exit;
            }
          }
          // en mode supplier, les commandes sont forcément validées => mise à jour du stock à chaque fois
          tep_update_stock($md_p_id, $new_stock, "", true);
          
          // dans quel mode est-on : avant validation ou après ?
          // pour différencier, il faut vérifier un enreg dans ops
          
          // on modifie le prix du produit pour le futures commandes dans la table products 
       		$sql = "UPDATE products SET products_price = '".$price."' WHERE products_id='".$md_p_id."';";
      		$query = tep_db_query($sql);
      		
      		if ($old_final_price != $price) {
            // ajoute une notification de modif dans la nouvelle table products_prices_modifications
            $sql_add = "INSERT INTO products_prices_modifications (products_prices_modifications_datetime, products_id, old_price, new_price, comments) 
                  VALUES ('".$mydatetime."','$md_p_id','$old_final_price','$price', 'modification prix par producteur - modOrderProduct');";
            tep_db_query($sql_add, 'db_link');
          }
  
          // on modifie les enreg éventuels dans la table ops
       		$sql = "UPDATE orders_products_suppliers SET new_products_quantity = '".$qty."', new_final_price = '".$price."' WHERE products_id=".$md_p_id." AND date_shipped='".$md_ds."' AND manufacturers_id=".$m_id.";";
      		$query = tep_db_query($sql);
      	}
      } else {
        echo "Cannot find record in supplier_mode.<br>SQL QUERY : ".$sql;
        exit;
      }


   return true; 
  }

/*
  function freeze_orders($f_datetime = "") {
  	global $manufacturer_id;
  	
    // on modifie le champ last_freezing de la table manufacturers concernée
    if ($f_datetime == "") {
	    $freezing_date = date("Y-m-d H:i:s");
	  } else {
	    $freezing_date = $f_datetime;
		}	  
    $sql_add = "UPDATE manufacturers SET last_freezing = '$freezing_date' WHERE manufacturers_id = $manufacturer_id;";
    tep_db_query($sql_add, 'db_link');
    return $freezing_date;
  }
*/

	function calcul_ot() {
		// recalcule tous les orders_total
		$o_query = tep_db_query("SELECT orders_id,orders_status FROM orders;");
		while ($o = tep_db_fetch_array($o_query)) {
      calcul_ot_one((int)$o['orders_id'], ($o[orders_status]==4)||($o[orders_status]<0));
		}
	}
	
	function calcul_ot_one($o_id, $is_rec = false) {
	  if ($is_rec) {
	    calcul_ot_one_old($o_id);
    } else {
	    calcul_ot_one_old($o_id, "_modifications");
    }
  }
	
	function calcul_ot_one_old($o_id, $opm = "") {
		global $currencies;

    $fact = "";
    if ($opm != "") {
      $fact = "facturate = 'Y' AND";
    }
		$sql = "SELECT SUM(products_quantity*final_price) AS sum FROM orders_products$opm WHERE $fact orders_id='" . $o_id . "';";
		$ot_query = tep_db_query($sql);
		if ($ot = tep_db_fetch_array($ot_query)) {
     $ot_query = tep_db_query("UPDATE orders_total SET text='<b>".$currencies->format($ot['sum'])."</b>',value='".$ot['sum']."' WHERE orders_id='" . $o_id . "';");
		}
  }

  // gestion des groupements d'achats - CJ 29/12/2008
  function authorizedGA() {
    $ga_name = "";
    if (tep_session_is_registered('customer_id')) {
      $ga_sql = "select customers_ga_name from customers_ga where customers_ga_id = -1";
      $ga_query = tep_db_query($ga_sql);
      if ($ga = tep_db_fetch_array($ga_query)) {
        $ga_name = $ga['customers_ga_name']; 
      }
    }

    return $ga_name;
  }

  function gaCanBuy() {
    $ga_id = authorizedGA();
    return (($ga_id == "*")||($ga_id > 0)); 
  }

// gestion des groupements d'achats - CJ 29/12/2008
  function clientCanBuyGA() {
    global $customer_id;
    $canbuy = gaCanBuy();
    if (($canbuy) && is_numeric($customer_id) && ($customer_id>0)) {
      $ga_sql = "select customers_ga_id from customers where customers_id = '" . (int)$customer_id . "'";
      $ga_query = tep_db_query($ga_sql);
      if ($ga = tep_db_fetch_array($ga_query)) {
        $ga_id = $ga['customers_ga_id'];
        $auth_ga = authorizedGA();
        $canbuy = (($auth_ga=="*")&&(($ga_id>0)||($ga_id=="*")))||(($ga_id>0)&&($ga_id==$auth_ga));
      } else {
        $canbuy = false;
      }
    } else {
      $canbuy = false;
    } 
    return $canbuy;
  }

  function addProduct($mID, $mGID, $oID = -1, $not_p_id_list = "", $is_rec = false, $adminMode = false, $orderAdminMode = false) {
    global $order_date_to, $HTTP_GET_VARS, $gaID;
    
    $p_id_all = "";
    $attr = "";
    $disabled = "";

    $sql = "SELECT p.products_id, m.manufacturers_name, pd.products_name, p.shipping_day, p.shipping_frequency FROM products AS p 
        LEFT JOIN manufacturers as m ON p.manufacturers_id = m.manufacturers_id
        LEFT JOIN products_description as pd ON p.products_id = pd.products_id ";
    if ((!$adminMode)||($orderAdminMode)) {
      // on n'affiche que les produits de la vente directe ou du groupement d'achat (c'est mGID qui détermine)
      if (!empty($HTTP_GET_VARS['product_id'])) {
        $p_id_all = $HTTP_GET_VARS['product_id'];
        list($p_id, $option_id, $value_id, $sd, $sf) = explode("§", $p_id_all);
        if ($value_id>0) $attr = "§".$value_id;
        $p_id = tep_get_prid($p_id);
      } else {
        $p_id = "";
      }
      
      $gaID = getGA_ID($mGID);

      if (!$orderAdminMode) {
        $phpFile = FILENAME_ACCOUNT_HISTORY_INFO;
      } else {
        $phpFile = FILENAME_ORDERS;
      }
      $addTxt = "";
      $oidText = '
        <input type="hidden" name="cur_order_id" value="'.$oID.'">
        <input type="hidden" name="product_id" value="'.$p_id_all.'">
        ';
      $hr = "";
      $addBtn = "Ajouter le produit";
    } else if ($adminMode) {
      $phpFile = FILENAME_STATS_MANUFACTURERS;
      $oidText = '';
      $addTxt = "Ajouter :";
      $disabled = " disabled";
      $hr = "<hr>";
      $addBtn = "Ajouter à la livraison du ".getFormattedLongDate($order_date_to);
    }
    if ($mID>0) {
      $sql .= "
        WHERE p.manufacturers_id = $mID ";
    } else if ($mGID>=0) {
      $sql .= "
        WHERE m.group_id = $mGID ";
    }
    if ((!$adminMode || ($adminMode && $orderAdminMode) )&&($mGID>=0)&&($not_p_id_list!="")){
      $sql .= "AND p.products_id NOT IN (".$not_p_id_list.") ";
    }
    if (!$adminMode && !$orderAdminMode) {
      $sql .= "AND p.products_status = '1' ";
    }
    
    $sql .= " ORDER BY ";
    if (($mGID>0)&&($mID<1)) {
      $sql .= "m.manufacturers_name, ";
    }
    $sql .= "pd.products_name;";

    $products_array = array();
    if (!$adminMode) {
      $products_array[] = array('id' => '-1§-1§-1§-1§-1', 'text' => '');
    }
    
    $products_query = tep_db_query($sql);
    while ($products = tep_db_fetch_array($products_query)) {
      if (($mGID>0)&&($mID<1)) {
        // si grpt d'achat, on affiche le nom du producteur
        $p_name = tep_truncate_string($products['manufacturers_name']."/".$products['products_name'], MAX_PRODUCTS_NAMES_LENGTH);
      } else {
        $p_name = tep_truncate_string($products['products_name'], MAX_PRODUCTS_NAMES_LENGTH - 20);
      }
      $products_attr_query = tep_db_query("
        SELECT pa.options_id, pa.options_values_id, po.products_options_name, pov.products_options_values_name FROM products_attributes as pa
          LEFT JOIN products_options as po ON pa.options_id = po.products_options_id 
          LEFT JOIN products_options_values as pov ON pa.options_values_id = pov.products_options_values_id 
          WHERE pa.products_id = ".$products['products_id']." ORDER BY pov.products_options_values_name;");
      $has_attr = false; 
      while ($products_attr = tep_db_fetch_array($products_attr_query)) {
        $has_attr = true; 
        $products_array[] = array('id' => $products['products_id'].'§'.$products_attr['options_id'].'§'.$products_attr['options_values_id'].
                                            '§'.$products['shipping_day'].'§'.$products['shipping_frequency'],
                                 'text' => $p_name . ' (' .$products_attr['products_options_values_name'].')');
      }
      if (!$has_attr) {
        $products_array[] = array('id' => $products['products_id'].'§-1§-1§'.$products['shipping_day'].'§'.$products['shipping_frequency'],
                                 'text' => $p_name);
      }
    }

    $clientList = "";
    if (($adminMode)&&(!$orderAdminMode)) {
      $clientList = '
            <tr>
              <td class="pageHeadingSmall" nowrap>&nbsp;</td>
              <td class="main" nowrap align="right">à l\'adhérent : '.addClientListBlock($gaID).'</td>
            </tr>
            <tr>
              <td class="pageHeadingSmall" nowrap>&nbsp;</td>
              <td class="main" nowrap align="right">'.getEmailNotifyHTML(true).'</td>
            </tr>';
    }

    
    $addProduct = '
        <tr>
          <td>'.
            tep_draw_form('add_product_form', $phpFile, querystring_small(), 'post').'
            <input type="hidden" name="action" value="addproduct">'.$oidText.'

            <table border="0" width="100%" cellspacing="2" cellpadding="2">
              <tr rowspan="2">
                <td class="pageHeadingSmall" nowrap>'.$addTxt.'&nbsp;</td>
                <td class="main" align="right" nowrap>
                  <input type="text" id="quantity_addproduct" name="quantity_addproduct" value="1" align="right" size="2">&nbsp;x&nbsp;'.
                  tep_draw_pull_down_menu('products_addproduct', $products_array, $p_id_all, 'onchange="javascript:changeProduct(this, '.$oID.');"');
                  
    if ((!$adminMode)&&($p_id>0)) {
      if ($gaID>0) {
        $ds = getGA_order_date($gaID);
        if (ordersGA_are_frozen($gaID, $ds)!="") {
          $ds = getGA_order_date_next($gaID);
        }
      } else {
        $ds = get_order_date("", $p_id, $sd, $sf);
        if (orders_are_frozen($p_id, "", $ds)!="") {
          $ds = get_order_date_arg($ds, "", $p_id, $sd, $sf);
        }
      }
      $addProduct .= '<div id="shippingDiv">livraison le '.putShipping($p_id.$attr, $sd, $sf, $ds, "", $is_rec).'</div>';
    }

    $addProduct .= '</td>
                <td align="left" width="100%"><input type="submit" id="submit_addproduct" style="height: 25px; font-weight: bold; font-size : 12px;" value="'.$addBtn.'"'.$disabled.'></td>
              </tr>'.$clientList.'
            </table></form>'.$hr.'
          </td>
        </tr>';

    return $addProduct;
  
  }
  
  function addClientListBlock($gaID, $cID = '', $onchange = ' onchange="javascript:changeClient();"') {
    // l'adhérent ne peut être choisi qu'en mode admin
    // dans le cas d'un groupement d'achat, on filtre les adhérents par groupement
    // from = checkout_confirmation ou addProduct function in adminmode
    $customers_array = array();
    $customers_array[] = array('id' => '',
                               'text' => '');
    $cust_sql = "
      SELECT customers_id, customers_firstname, customers_lastname FROM customers 
        WHERE (customers_id > 0 AND customers_firstname <> 'Password') ";

/* CJ 2010-06-10 : on affiche tout
    if (($gaID>0)&&($cID=="")) {  // si $cID est vide, on vient du mode admin
      $cust_sql .= " AND (customers_ga_id = '*' OR customers_ga_id = '$gaID')";
    }     
*/

    $cust_sql .= " ORDER BY customers_lastname"; 
    $customers_query = tep_db_query($cust_sql);
    while ($customers = tep_db_fetch_array($customers_query)) {
        $customers_array[] = array('id' => $customers['customers_id'],
                                   'text' => $customers['customers_firstname'].' '.$customers['customers_lastname'] . ' ('.$customers['customers_id'].')');
    }
    return tep_draw_pull_down_menu('customers_addproduct', $customers_array, $cID, 'id="customers_addproduct" '.$onchange); 
  }
  
  function addProductAttributes($p_name, $op_id) {
    $p_name = tep_truncate_string($p_name, MAX_PRODUCTS_NAMES_LENGTH); //$products['products_name'], 30);

    $attributes_query = tep_db_query("select products_options, products_options_values, options_values_price, price_prefix from " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . " where orders_products_id = '" . $op_id . "'");
    if (tep_db_num_rows($attributes_query)) {
      if ($attributes = tep_db_fetch_array($attributes_query)) {
        $p_name .= $attributes['products_options'].$attributes['products_options_values'];
      }
    }
    return $p_name;
  }
 
  function reloadSMS($m_id = -1, $page = FILENAME_STATS_MANUFACTURERS) {
    global $HTTP_GET_VARS;
    $qs = "";
    if (($page == FILENAME_STATS_MANUFACTURERS)&&($m_id>0)) {
      $qs = "&mID=".$m_id;
    } else if ($page == FILENAME_ORDERS) {
      $qs = "&action=edit";
    } 
/*
    if ($HTTP_GET_VARS['page']>0) {
      $qs = "&page=".$HTTP_GET_VARS['page'];
    }
*/
    tep_redirect(tep_href_link($page, querystring_small().$qs));
  }
  
  function getBulk($p_id, $qty_selected = 1.0, $name = "product_qty", $check_qty_in_cart = false, $param = "", $add_zero_qty = false) {
    global $cart;
    
    $qty_selected = tep_format_qty_for_db($qty_selected);
    
    $p_id = tep_get_prid($p_id);
    
    $check_qty_in_cart = (tep_session_is_registered('cart') && is_object($cart) && $check_qty_in_cart);
    if ($check_qty_in_cart) {
      $cart_qty = $cart->get_quantity($p_id);
    }
    
    $bulk_query = tep_db_query("SELECT is_bulk, measure_unit, products_min_manufacturer_quantity, authorized_weights FROM " . TABLE_PRODUCTS . " WHERE products_id='" . $p_id . "';");
    if ($bulk_result = tep_db_fetch_array($bulk_query)) {
      $ib = $bulk_result['is_bulk']; 
      $mu = $bulk_result['measure_unit']; 
      $cond = $bulk_result['products_min_manufacturer_quantity'];  // conditionnemnt fournisseur
      $weights_array = explode("|", $bulk_result['authorized_weights']);
      
      if ($ib > 0) {
        // c'est un produit en vrac : on peut donc choisir la quantité
        $product_qty = array();
        if ($add_zero_qty) $product_qty[] = array('id' => 0, 'text' => tep_format_qty_for_html(0, false, $mu, " "));
        foreach ($weights_array as $f) {
          $f = tep_format_qty_for_db($f);
          if (($check_qty_in_cart && in_array($f+$cart_qty, $weights_array)||(!$check_qty_in_cart))) {
            $product_qty[] = array('id' => $f, 'text' => tep_format_qty_for_html($f, false, $mu, " "));
          } 
        }
        if (($qty_selected>=0)&&(!in_array_recursive($qty_selected, $product_qty))) {
          $product_qty[] = array('id' => $qty_selected, 'text' => tep_format_qty_for_html($qty_selected, false, $mu, " "));
          sort($product_qty);
        }
        if (count($product_qty) > 0) {
          return tep_draw_pull_down_menu($name, $product_qty, $qty_selected, $param);
        } else {
          return "";
        }
      }
    }
  }
  
  function update_ga_nds($gaID, $nds, $nnds) {
    $sql_mod = "UPDATE customers_ga SET next_date_shipped = '" .$nds. "', next_next_date_shipped = '" .$nnds. "' WHERE customers_ga_id = ".$gaID.";";
    tep_db_query($sql_mod, 'db_link');
    
    // on modifie toutes les next_date_shipped des orders_products des adhérents concernés
    $sql_mod_nds = "SELECT orders_products_id FROM orders_products AS op 
        LEFT JOIN orders AS o ON op.orders_id = o.orders_id
        LEFT JOIN customers AS c ON c.customers_id = o.customers_id
        LEFT JOIN manufacturers AS m ON op.manufacturers_id = m.manufacturers_id
      WHERE (c.customers_ga_id = '".$gaID."' OR c.customers_ga_id = '*') AND (m.group_id = 1) AND (o.orders_status >-1);";
    $mod_nds_query = tep_db_query($sql_mod_nds);
    while ($mod_nds = tep_db_fetch_array($mod_nds_query)) {
      $sql_mod_op = "UPDATE orders_products SET next_date_shipped = '" .$nds. "' WHERE orders_products_id = ".$mod_nds['orders_products_id'].";";
      tep_db_query($sql_mod_op, 'db_link');
      $i += 1;
    }
  }
  
  function generateNDSarray($gaID = "", $nds = "", $nnds = "") {
    global $next_date_shipped_array;
    $next_saturday = get_order_date("", "", "saturday", 1.0);
    $next_saturday = date("Y-m-d", strtotime("-6 weeks", strtotime($next_saturday)));
    (tep_db_prepare_input($gaID>0)) ? ((tep_db_prepare_input(ordersGA_are_frozen($gaID, $next_saturday) != "")) ? $frozen = " (validée)" : $frozen = "") : $frozen = "";   
    $next_date_shipped_array[] = array('id' => $next_saturday, 'text' => getFormattedLongDate($next_saturday, true).$frozen);
    for ($i=0; $i<25; $i++) {
      $next_saturday = get_order_date_arg($next_saturday, "", "", "saturday", 1.0);
      (tep_db_prepare_input($gaID>0)) ? ((tep_db_prepare_input(ordersGA_are_frozen($gaID, $next_saturday) != "")) ? $frozen = " (validée)" : $frozen = "") : $frozen = "";   
      $next_date_shipped_array[] = array('id' => $next_saturday, 'text' => getFormattedLongDate($next_saturday, true).$frozen);
    }
//    echo "a".$nds."a".$nnds."a";
    if (($nnds != "")&&($nnds != "0000-00-00")&&(!in_array_recursive($nnds, $next_date_shipped_array))) {
      (tep_db_prepare_input($gaID>0)) ? ((tep_db_prepare_input(ordersGA_are_frozen($gaID, $nnds) != "")) ? $frozen = " (validée)" : $frozen = "") : $frozen = "";   
      array_unshift($next_date_shipped_array, array('id' => $nnds, 'text' => getFormattedLongDate($nnds, true).$frozen));  
    }
    if (($nds != "")&&($nds != "0000-00-00")&&(!in_array_recursive($nds, $next_date_shipped_array))) {
      (tep_db_prepare_input($gaID>0)) ? ((tep_db_prepare_input(ordersGA_are_frozen($gaID, $nds) != "")) ? $frozen = " (validée)" : $frozen = "") : $frozen = "";   
      array_unshift($next_date_shipped_array, array('id' => $nds, 'text' => getFormattedLongDate($nds, true).$frozen));  
    }
    array_unshift($next_date_shipped_array, array('id' => '', 'text' => ''));  
  }
  
  function getShippingInfo($p_array, $from, $is_rec = false) {
/*
    $from = SC : from shopping_cart.php
    $from = CC : from checkout_confirmation.php
    $from = AHI : from account_history_info_table.php
    
*/

    $toEcho = "";
    
    if (is_array($p_array)) {
      if ($from != "AHI") {
        $fromtxt = "le";
        $ga = ($p_array['group_id']>0);
        if ($ga) {
      		$cj_order_date = getGA_order_date(getGA_ID($p_array['group_id']));
        } else {
          if ($is_rec) {
            $fromtxt = "à partir du";
          }
          $sd = $p_array['shipping_day'];
          $sf = $p_array['shipping_frequency'];
  /*
          en commentaire car sinon, il manque des jours dans les drop_down_list
          on est obligé de verrouiller checkout_process pour être sûr de pas avoir :
              shipping_day = tuesday|thursday et frequence = 1.0
          } else {
            $sd = strtolower(date("l", strtotime($p_array['date_shipped'])));
            $sf = 1.0;
          }
  */
      		$cj_order_date = get_order_date("", tep_get_prid($p_array['id']), $sd, $sf);
        }
        if ($cj_order_date != $p_array['date_shipped']) {
          $toEcho .= '<br><span class="messageStackErrorBig"><i>ATTENTION : ';
        } else {
          $toEcho .= '<br><span class="messageStackSuccess"><i>';
        }
        $toEcho .= 'livraison '.$fromtxt.' ';
      } else {
        $toEcho .= '';
      }
      
      if (($from == "CC")||($from == "AHI")) {
/*      
        en commentaire car sinon, il manque des jours dans les drop_down_list
        on est obligé de verrouiller checkout_process pour être sûr de pas avoir :
            shipping_day = tuesday|thursday et frequence = 1.0
        if ($sf == 0.5) {
          // par défaut, même si le produit est commandable 2 fois par semaine, 
          //    on ne fait pas une commande toutes les 2 semaines
          $sd = strtolower(date("l", strtotime($p_array['date_shipped'])));
          $sf = 1.0;
        }
*/
        $toEcho .= putShipping(
          tep_get_prid($p_array['id']).getAttrId($p_array['attributes'], true), 
          $sd, 
          $sf,
          $p_array['date_shipped'],
          "",
          $is_rec);
      } else {
        $toEcho .=  '<b>' . getFormattedLongDate($p_array['date_shipped'], true) . "</b>";
      }
      if ($from != "AHI") {
        $toEcho .= '</span></i></td>' . "\n";
      }
    }
    return $toEcho;
  }
  
  function getManufacturerNameLinkForProduct($query_result) {
    global $order_date_to, $has_frozen_orders, $print, $customer_only, $adminMode,
      $supplier_order_received, $supplier_validated, $manufacturer_id_sav;

    if ($customer_only || ($manufacturer_id_sav > 0)) return "";

    $m_id = $query_result['manufacturers_id'];

    $supplier_validated = (($has_frozen_orders) && (ordersS_are_frozen($m_id, $order_date_to) != ""));
    $supplier_order_received = ops_are_received($m_id, $order_date_to); 
    $c_txt = "main";
    if (!$has_frozen_orders) {
      $t_txt = "Veuillez valider la commande du groupement d&#39;achat";
    } else {
      $m_name_without_quote = str_replace("'", "&#39;", $query_result['manufacturers_name']);
      if (($supplier_validated)&&(!$supplier_order_received)) {
        $c_txt = "messageStackWarningBig";
        $t_txt = "La commande a été envoyée à ".$m_name_without_quote; 
      } else if (($supplier_validated)&&($supplier_order_received)) {
        $c_txt = "messageStackSuccessBig"; 
        $t_txt = "La commande de ".$m_name_without_quote." a été reçue"; 
      } else {
        $t_txt = "Veuillez valider la commande de ".$m_name_without_quote;
      }
    }
    $m_name = "<span class='$c_txt' title='$t_txt'>".$query_result['manufacturers_name']."</span>";
    
    if (!$print) {
      if ($adminMode == "yes") {
        $link = tep_href_link(FILENAME_STATS_MANUFACTURERS, querystring_small(getMIDvalue(), "", true, "all_by_man", false, false) . "&m_id_from_array=".$m_id, 'NONSSL');
      } else {
        $link = tep_href_link(FILENAME_MANUFACTURERS_INFO, 'manufacturers_id='.$m_id);
      }
      return '<a href="' . $link . '"><b>'.$m_name.'</b></a> // ';
    } else {
      if (trim($query_result['manufacturers_name']) != "") {
        return '<b>'.$m_name.'</b> // ';
      } else {
        return "";
      }
    }
  }
  
  function getProductNameLink($p_id, $p_name) {
    global $print, $adminMode; 
    if (!$print) {
      if ($adminMode == "yes") {
        return '<a href="' . tep_href_link(FILENAME_STATS_MANUFACTURERS, querystring_small(getMIDvalue(), "", true, "all_cust_by_prod", false, false) . "&p_id_from_array=".$p_id, 'NONSSL') . '">' . $p_name . '</i></a>';
      } else {
        return '<a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $p_id) . '">' . $p_name . '</a>';
      }
    }
    else {
      return $p_name;
    }
  }
  
  function getCustomerNameLink($c_id, $c_name) {
    global $print; 
    if (!$print) {
      return '<a href="' . tep_href_link(FILENAME_STATS_MANUFACTURERS, querystring_small(getMIDvalue(), "", true, "all_by_once", false, false) . "&c_id_from_array=".$c_id, 'NONSSL') . '">' . $c_name . '</a>';
    } else {
      return $c_name;
    }
  }
  
  function getMIDvalue() {
    global $manufacturer_id; 
    ($manufacturer_id<=0) ? $m_id_aux = -1 : $m_id_aux = $manufacturer_id;
    return $m_id_aux;
  }
  
  function addArrayFilters($sql) {
    global $m_id_from_array, $c_id_from_array, $p_id_from_array, $supplier_mode, $adminMode;

    if ($adminMode == "yes") {
      if ($m_id_from_array != "") {
        $sql = str_replace("WHERE ", "WHERE p.manufacturers_id = ". $m_id_from_array." AND ", $sql);
      }
      if (!$supplier_mode && ($c_id_from_array != "")) {
        $sql = str_replace("WHERE ", "WHERE opm.customers_id = ". $c_id_from_array." AND ", $sql);
      }
      if ($p_id_from_array != "") {
        $sql = str_replace("WHERE ", "WHERE p.products_id = ". $p_id_from_array." AND ", $sql);
      }
    }

    return $sql;
  }
  
  function getNextValidNDS($nds, $m_id, $p_id, $sd, $sf) {
    global $order_date_to;
    // la date de livraison prévue (nds) est dépassée
    // il faut recalculer la nouvelle nds valide
    // on boucle à partir de nds jusqu'à ce que l'on tombe sur order_date_to
    // on limite néanmoins la recharche à 12 itérations (3 mois)
    $i = 0;
    $bAdd = false;
    $new_nds = $nds;
    while ((!$bAdd)&&($i<12)) {
      $new_nds = date("Y-m-d", strtotime(get_order_date_arg($new_nds, $m_id, $p_id, $sd, $sf)));
      $bAdd = ($new_nds == $order_date_to);
      $i++;
    }
    
/*
    // on recherche la date de début
    while (strtotime($nds) >= strtotime($shipping_dates_array[0]['id'])) {
      $nds = date("Y-m-d", strtotime(get_order_date_arg($nds, $manufacturer_id, "", $sd, $sf, "-")));
    }
    
    // on boucle à partir de cette date de début jusqu'à la date de fin (dernier élément de $shipping_dates_array)
    while ((!$bAdd)&&(strtotime($nds) <= strtotime($shipping_dates_array[count($shipping_dates_array)-1]['id']))) {
      $nds = date("Y-m-d", strtotime(get_order_date_arg($nds, $manufacturer_id, "", $sd, $sf)));
      $bAdd = ($nds == $order_date_to);
    }
*/

    if ($bAdd) {
      return $new_nds;
    } else {
      return "";
    }
  }
?>