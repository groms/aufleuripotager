<?php
/*
  $Id: shopping_cart.php,v 1.35 2003/06/25 21:14:33 hpdl Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 osCommerce

  Released under the GNU General Public License
*/

  class shoppingCart {
    var $contents, $total, $weight, $cartID, $content_type;

    function shoppingCart() {
      $this->reset();
    }

    function restore_contents() {
      global $customer_id;
      if (!tep_session_is_registered('customer_id')) return false;

// insert current cart contents in database
      if (is_array($this->contents)) {
        reset($this->contents);

        while (list($products_id, ) = each($this->contents)) {
          $qty = $this->contents[$products_id]['qty'];
          $product_query = tep_db_query("select products_id from " . TABLE_CUSTOMERS_BASKET . " where customers_id = '" . (int)$customer_id . "' and products_id = '" . tep_db_input($products_id) . "'");
          if (!tep_db_num_rows($product_query)) {
            tep_db_query("insert into " . TABLE_CUSTOMERS_BASKET . " (customers_id, products_id, customers_basket_quantity, customers_basket_date_added) values ('" . (int)$customer_id . "', '" . tep_db_input($products_id) . "', '" . $qty . "', '" . date('Ymd') . "')");
            if (isset($this->contents[$products_id]['attributes'])) {
              reset($this->contents[$products_id]['attributes']);
              while (list($option, $value) = each($this->contents[$products_id]['attributes'])) {
                tep_db_query("insert into " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . " (customers_id, products_id, products_options_id, products_options_value_id) values ('" . (int)$customer_id . "', '" . tep_db_input($products_id) . "', '" . (int)$option . "', '" . (int)$value . "')");
              }
            }
          } else {
            tep_db_query("update " . TABLE_CUSTOMERS_BASKET . " set customers_basket_quantity = '" . $qty . "' where customers_id = '" . (int)$customer_id . "' and products_id = '" . tep_db_input($products_id) . "'");
          }
        }
      }

// reset per-session cart contents, but not the database contents
      $this->reset(false);

      $products_query = tep_db_query("select products_id, customers_basket_quantity from " . TABLE_CUSTOMERS_BASKET . " where customers_id = '" . (int)$customer_id . "'");
      while ($products = tep_db_fetch_array($products_query)) {
        $this->contents[$products['products_id']] = array('qty' => $products['customers_basket_quantity']);
// attributes
        $attributes_query = tep_db_query("select products_options_id, products_options_value_id from " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . " where customers_id = '" . (int)$customer_id . "' and products_id = '" . tep_db_input($products['products_id']) . "'");
        while ($attributes = tep_db_fetch_array($attributes_query)) {
          $this->contents[$products['products_id']]['attributes'][$attributes['products_options_id']] = $attributes['products_options_value_id'];
        }
      }

      $this->cleanup();
    }

    function reset($reset_database = false) {
      global $customer_id;

      $this->contents = array();
      $this->total = 0;
      $this->weight = 0;
      $this->content_type = false;

      if (tep_session_is_registered('customer_id') && ($reset_database == true)) {
        tep_db_query("delete from " . TABLE_CUSTOMERS_BASKET . " where customers_id = '" . (int)$customer_id . "'");
        tep_db_query("delete from " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . " where customers_id = '" . (int)$customer_id . "'");
      }

      unset($this->cartID);
      if (tep_session_is_registered('cartID')) tep_session_unregister('cartID');
    }

    function add_cart($products_id, $qty = '1', $attributes = '', $notify = true, $adminMode = false) {
      global $new_products_id_in_cart, $customer_id;

      $qty = tep_format_qty_for_db($qty);

  		$stop = false;
      if (!tep_session_is_registered('customer_id')) {
        $stop = true;
        tep_redirect(tep_href_link("message.php", "msgtype=please_login"));
      }
      
      if (!$stop) {
        $products_id_string = tep_get_uprid($products_id, $attributes);
        $products_id = tep_get_prid($products_id_string);
  
        $attributes_pass_check = true;
  
        $updating = false;
  
        if (is_array($attributes)) {
          reset($attributes);
          while (list($option, $value) = each($attributes)) {
            if (!is_numeric($option) || !is_numeric($value)) {
              $attributes_pass_check = false;
              break;
            }
          }
        }
  
        if (is_numeric($products_id) && is_qty($qty) && ($attributes_pass_check == true)) {
  
          if ($adminMode != "yes") {
        		// le groupe du produit à ajouter est-il différent de ceux qui sont dans le panier ?
    				$orders_are_frozen = false;
    				$cj_order_date_fr = "";
        
            $check_product_query = tep_db_query("SELECT pd.products_name, is_bulk, m.group_id, shipping_day, shipping_frequency, products_status, m.manufacturers_id, m.manufacturers_name FROM " . TABLE_PRODUCTS . " AS p LEFT JOIN " . TABLE_MANUFACTURERS . " AS m ON m.manufacturers_id = p.manufacturers_id LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " AS pd ON pd.products_id = p.products_id WHERE p.products_id = '" . (int)$products_id . "';");
            $check_product = tep_db_fetch_array($check_product_query);
            if ($check_product) {
              $m_id = (int)$check_product['manufacturers_id'];
            	$m_name = $check_product['manufacturers_name'];
            	$p_name = $check_product['products_name'];
  //  					$last_freezing_date = $check_product['last_freezing'];
    
    	        // le produit est-il commandable ?
    					if ($check_product['products_status'] != '1') {
                $stop = true;
                tep_redirect(tep_href_link("message.php", "msgtype=not_allowed"));
    					} else {
    			      // le produit est-il du type "groupement d'achat" ?
    		        $can_buy_ga = ($check_product['group_id'] != 1); // ce n'est pas un produit du groupement d'achat !
    		        if (!$can_buy_ga) {
    		          // c'est un produit du grpt d'achat
    		          $can_buy_ga = clientCanBuyGA();
    		          if ($can_buy_ga) {
        	        	// les commandes sont-elles figées ?
        	        	$gaID = getGA_ID($check_product['group_id']);
        						$cj_order_date = getGA_order_date($gaID);
        
                    // on vérifie que l'on n'a pas de commandes figées à la date $cj_order_date
        						$orders_are_frozen = (ordersGA_are_frozen($gaID, $cj_order_date) != "");
        						$nnds = getGA_order_date_next($gaID);
                  }
    		        } else {
    		          // c'est un produit de la vente directe
      	        	// les commandes sont-elles figées ?
      	        	$gaID = 0;
      						$cj_order_date = get_order_date("", $products_id, $check_product['shipping_day'], $check_product['shipping_frequency']);
      
                  // on vérifie que l'on n'a pas de commandes figées à la date $cj_order_date
      						$orders_are_frozen = (orders_are_frozen($products_id, $m_id, $cj_order_date) != "");
       						$nnds = get_order_date_arg($cj_order_date, "", $products_id, $check_product['shipping_day'], $check_product['shipping_frequency']);
                }
    
    		        if (!$can_buy_ga) {
    		          $stop = true;
    		          tep_redirect(tep_href_link("message.php", "msgtype=not_allowed_ga"));
    		        }
    					}
            } else {
              $stop = true;
    	        tep_redirect(tep_href_link("message.php", "msgtype=product_not_found"));
            }
    
    				if ((!$stop)&&((!$this->in_cart($products_id_string)))) {
    					// on vérifie que l'on est pas en train de mélanger les 2 réseaux
    		  		$p_list = $this->get_product_id_list(true);
    		  		if ($p_list != "") {
    				    $check_product_query_count = tep_db_query("SELECT count(*) as cf FROM " . TABLE_PRODUCTS . " AS p LEFT JOIN " . TABLE_MANUFACTURERS . " AS m ON m.manufacturers_id = p.manufacturers_id WHERE (m.group_id != '" . (int)$check_product['group_id'] . "') AND (p.products_id IN (" . $p_list . "));");
    			      $check_product_count = tep_db_fetch_array($check_product_query_count);
    			      
    						if ((!$check_product_count)||(($check_product_count)&&((int)$check_product_count['cf'] > 0))) {
    		          $stop = true;
    		          if ($check_product['group_id'] == 0) {
    		          	// c'est un produit de la vente directe
    			          tep_redirect(tep_href_link("message.php", "msgtype=not_allowed_ga_already_in_basket"));
    		          } else {
    		          	// c'est un produit du groupement d'achat
    			          tep_redirect(tep_href_link("message.php", "msgtype=not_allowed_rvd_already_in_basket"));
    		          }
    						}
    		  		}
    				}
    				
            if (!$stop) {
              // on vérifie que l'on n'a pas dépassé la limite max pour cette semaine
              $nb_dispo = $this->get_product_limitation($products_id, $qty, $cj_order_date);
              if ($nb_dispo > -1) {
    	          $stop = true;
    	      		tep_redirect(tep_href_link("message.php", "msgtype=product_limit_exceeded&m_id=".$m_id."&p_name=".urlencode($p_name)."&nb_dispo=".$nb_dispo));
              }
            }
            
          }
          if (!$stop) {
            if ($notify == true) {
              $new_products_id_in_cart = $products_id;
              tep_session_register('new_products_id_in_cart');
            }
            
  
            if ($this->in_cart($products_id_string)) {
              $this->update_quantity($products_id_string, $qty, $attributes);
  			      $updating = true;
            } else {
//            echo $qty;exit;
              $this->contents[$products_id_string] = array('qty' => $qty);
              
  // adding in basket ==============>            
  // insert into database
              if (tep_session_is_registered('customer_id')) {
                tep_db_query("insert into " . TABLE_CUSTOMERS_BASKET . " (customers_id, products_id, customers_basket_quantity, customers_basket_date_added) values ('" . (int)$customer_id . "', '" . tep_db_input($products_id_string) . "', '" . $qty . "', '" . date('Ymd') . "')");
              }
  
              if (is_array($attributes)) {
                reset($attributes);
                while (list($option, $value) = each($attributes)) {
                  $this->contents[$products_id_string]['attributes'][$option] = $value;
  // insert into database
                  if (tep_session_is_registered('customer_id')) tep_db_query("insert into " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . " (customers_id, products_id, products_options_id, products_options_value_id) values ('" . (int)$customer_id . "', '" . tep_db_input($products_id_string) . "', '" . (int)$option . "', '" . (int)$value . "')");
                }
              }
  // adding in basket <==============            
            }
  
            $this->cleanup();
  
  // assign a temporary unique ID to the order contents to prevent hack attempts during the checkout procedure
            $this->cartID = $this->generate_cart_id();
  
  					if ((!$updating)&&($orders_are_frozen)) {
  					  // les commandes sont figées
  						$cj_order_date_backup_fr = $cj_order_date; // livraison S
              $cj_order_date_fr = $nnds; // livraison S+1
  						
  	      		tep_redirect(tep_href_link("message.php", "msgtype=orders_are_frozen".$r_only."&date_backup=".urlencode($cj_order_date_backup_fr)."&date_next=".urlencode($cj_order_date_fr)."&m_name=".urlencode($m_name)));
  					}
          }
        }
      }
    }

    function get_product_limitation($products_id, $qty, $order_date) {
      $return = -1;
      $products_id = tep_get_prid($products_id);

      $check_product_query = tep_db_query("
        SELECT sum(op.products_quantity) as sum_pq, p.products_limitation as pl FROM orders_products AS op 
          LEFT JOIN orders AS o ON op.orders_id = o.orders_id 
          LEFT JOIN products AS p ON p.products_id = op.products_id
          WHERE (o.orders_status = 4) OR 
                (o.orders_status <> 4 AND o.orders_status <> -1 AND op.date_shipped = '".$order_date."' AND op.products_id = " . $products_id . ");");
      $check_product = tep_db_fetch_array($check_product_query);
      if ($check_product) {
        $pl = (int)$check_product['pl'];
        $sum_pq = (int)$check_product['sum_pq'];
        if (!(($pl <= 0) || (($pl > 0) && (($sum_pq + $qty) <= $pl)))) {
          // on ne peut pas commander car $qty > ($pl - $sum_pq)
          if ($sum_pq < $pl) {
            $return = ($pl - $sum_pq);
          } else {
            $return = 0;
          }
          
        }
      }
      return $return;
    }

    function update_quantity($products_id, $quantity = '', $attributes = '') {
      global $customer_id;
      
      $quantity = tep_format_qty_for_db($quantity);

      $products_id_string = tep_get_uprid($products_id, $attributes);
      $products_id = tep_get_prid($products_id_string);

      $attributes_pass_check = true;

      if (is_array($attributes)) {
        reset($attributes);
        while (list($option, $value) = each($attributes)) {
          if (!is_numeric($option) || !is_numeric($value)) {
            $attributes_pass_check = false;
            break;
          }
        }
      }

      if (is_numeric($products_id) && isset($this->contents[$products_id_string]) && is_qty($quantity) && ($attributes_pass_check == true)) {
        $this->contents[$products_id_string] = array('qty' => $quantity);
// update database
        if (tep_session_is_registered('customer_id')) tep_db_query("update " . TABLE_CUSTOMERS_BASKET . " set customers_basket_quantity = '" . $quantity . "' where customers_id = '" . (int)$customer_id . "' and products_id = '" . tep_db_input($products_id_string) . "'");

        if (is_array($attributes)) {
          reset($attributes);
          while (list($option, $value) = each($attributes)) {
            $this->contents[$products_id_string]['attributes'][$option] = $value;
// update database
            if (tep_session_is_registered('customer_id')) tep_db_query("update " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . " set products_options_value_id = '" . (int)$value . "' where customers_id = '" . (int)$customer_id . "' and products_id = '" . tep_db_input($products_id_string) . "' and products_options_id = '" . (int)$option . "'");
          }
        }
      }
    }

    function cleanup() {
      global $customer_id;

      reset($this->contents);
      while (list($key,) = each($this->contents)) {
        if (tep_format_qty_for_db($this->contents[$key]['qty']) == 0) {
          unset($this->contents[$key]);
// remove from database
          if (tep_session_is_registered('customer_id')) {
            tep_db_query("delete from " . TABLE_CUSTOMERS_BASKET . " where customers_id = '" . (int)$customer_id . "' and products_id = '" . tep_db_input($key) . "'");
            tep_db_query("delete from " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . " where customers_id = '" . (int)$customer_id . "' and products_id = '" . tep_db_input($key) . "'");
          }
        }
      }
    }

    function count_contents() {  // get total number of items in cart 
      $total_items = 0;
      if (is_array($this->contents)) {
        reset($this->contents);
        while (list($products_id, ) = each($this->contents)) {
          $total_items += $this->get_quantity($products_id);
        }
      }

      return $total_items;
    }

    function get_quantity($products_id) {
      if (isset($this->contents[$products_id])) {
        return $this->contents[$products_id]['qty'];
      } else {
        return 0;
      }
    }

    function in_cart($products_id) {
      if (isset($this->contents[$products_id])) {
        return true;
      } else {
        return false;
      }
    }

    function remove($products_id) {
      global $customer_id;

      unset($this->contents[$products_id]);
// remove from database
      if (tep_session_is_registered('customer_id')) {
        tep_db_query("delete from " . TABLE_CUSTOMERS_BASKET . " where customers_id = '" . (int)$customer_id . "' and products_id = '" . tep_db_input($products_id) . "'");
        tep_db_query("delete from " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . " where customers_id = '" . (int)$customer_id . "' and products_id = '" . tep_db_input($products_id) . "'");
      }

// assign a temporary unique ID to the order contents to prevent hack attempts during the checkout procedure
      $this->cartID = $this->generate_cart_id();
    }

    function remove_all() {
      $this->reset();
    }

    function get_product_id_list($real_id = false) {
      $product_id_list = '';
      if (is_array($this->contents)) {
        reset($this->contents);
        while (list($products_id, ) = each($this->contents)) {
          if ($real_id) {
	          $product_id_list .= ', ' . tep_get_prid($products_id);
	        } else {
	          $product_id_list .= ', ' . $products_id;
	        }
        }
      }

      return substr($product_id_list, 2);
    }

    function calculate() {
      $this->total = 0;
      $this->weight = 0;
      if (!is_array($this->contents)) return 0;

      reset($this->contents);
      while (list($products_id, ) = each($this->contents)) {
        $qty = $this->contents[$products_id]['qty'];

// products price
        $product_query = tep_db_query("select products_id, products_price, products_tax_class_id, products_weight from " . TABLE_PRODUCTS . " where products_id = '" . (int)$products_id . "'");

        if ($product = tep_db_fetch_array($product_query)) {
          $prid = $product['products_id'];
          $products_tax = tep_get_tax_rate($product['products_tax_class_id']);
          $products_price = $product['products_price'];
          $products_weight = $product['products_weight'];

          $specials_query = tep_db_query("select specials_new_products_price from " . TABLE_SPECIALS . " where products_id = '" . (int)$prid . "' and status = '1'");
          if (tep_db_num_rows ($specials_query)) {
            $specials = tep_db_fetch_array($specials_query);
            $products_price = $specials['specials_new_products_price'];
          }

          $this->total += tep_add_tax($products_price, $products_tax) * $qty;
          $this->weight += ($qty * $products_weight);
        }

// attributes price
        if (isset($this->contents[$products_id]['attributes'])) {
          reset($this->contents[$products_id]['attributes']);
          while (list($option, $value) = each($this->contents[$products_id]['attributes'])) {
            $attribute_price_query = tep_db_query("select options_values_price, price_prefix from " . TABLE_PRODUCTS_ATTRIBUTES . " where products_id = '" . (int)$prid . "' and options_id = '" . (int)$option . "' and options_values_id = '" . (int)$value . "'");
            $attribute_price = tep_db_fetch_array($attribute_price_query);
            if ($attribute_price['price_prefix'] == '+') {
              $this->total += $qty * tep_add_tax($attribute_price['options_values_price'], $products_tax);
            } else {
              $this->total -= $qty * tep_add_tax($attribute_price['options_values_price'], $products_tax);
            }
          }
        }
      }
    }

    function attributes_price($products_id) {
      $attributes_price = 0;

      if (isset($this->contents[$products_id]['attributes'])) {
        reset($this->contents[$products_id]['attributes']);
        while (list($option, $value) = each($this->contents[$products_id]['attributes'])) {
          $attribute_price_query = tep_db_query("select options_values_price, price_prefix from " . TABLE_PRODUCTS_ATTRIBUTES . " where products_id = '" . (int)$products_id . "' and options_id = '" . (int)$option . "' and options_values_id = '" . (int)$value . "'");
          $attribute_price = tep_db_fetch_array($attribute_price_query);
          if ($attribute_price['price_prefix'] == '+') {
            $attributes_price += $attribute_price['options_values_price'];
          } else {
            $attributes_price -= $attribute_price['options_values_price'];
          }
        }
      }

      return $attributes_price;
    }

    function get_products() {
      global $languages_id, $customer_id;

      if (!is_array($this->contents)) return false;

      $products_array = array();
      reset($this->contents);

      while (list($products_id, ) = each($this->contents)) {
//        $products_id = tep_get_prid($products_id);
        $sql = "select p.group_id, is_bulk, measure_unit, products_min_manufacturer_quantity, products_status, 
            m.manufacturers_id, m.manufacturers_name, m.last_freezing, p.products_id, pd.products_name, 
            p.shipping_day, p.shipping_frequency, p.products_model, p.products_image, p.products_price, 
            p.products_weight, p.products_tax_class_id FROM " . TABLE_PRODUCTS . " AS p 
                LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " as pd ON pd.products_id=p.products_id 
                LEFT JOIN " . TABLE_MANUFACTURERS . " AS m ON m.manufacturers_id = p.manufacturers_id 
                    WHERE p.products_id = '" . (int)$products_id . "' and pd.products_id = p.products_id and pd.language_id = '" . (int)$languages_id . "'";
        $products_query = tep_db_query($sql);
        if ($products = tep_db_fetch_array($products_query)) {

          $prid = $products['products_id'];
          $products_price = $products['products_price'];

          $specials_query = tep_db_query("select specials_new_products_price from " . TABLE_SPECIALS . " where products_id = '" . (int)$prid . "' and status = '1'");
          if (tep_db_num_rows($specials_query)) {
            $specials = tep_db_fetch_array($specials_query);
            $products_price = $specials['specials_new_products_price'];
          }

  				if ($products['group_id']==1) {
    				// c'est un produit du grpt d'achat
            // => on récupère l'order_date du groupement d'achat lié à l\'adhérent actuel (grâce à la variable d'env. $customer_id)
            $gaID = getGA_ID($products['group_id']);
            $cj_order_date = getGA_order_date($gaID);
  					$orders_are_frozen = (ordersGA_are_frozen($gaID, $cj_order_date) != "");
  					if ($orders_are_frozen) {
  						$cj_order_date = getGA_order_date_next($gaID);
  					}
          } else {
            // on ne vient pas du groupement d'achat
            $cj_order_date = get_order_date("", $prid, $products['shipping_day'], $products['shipping_frequency']);
  					$orders_are_frozen = (orders_are_frozen($prid, $products['manufacturers_id'], $cj_order_date) != "");
  
  					if ($orders_are_frozen) {
  						$cj_order_date = get_order_date_arg($cj_order_date, "", $prid, $products['shipping_day'], $products['shipping_frequency']);
  					}
          }
          
          $products_array[] = array('id' => $products_id,
                                    'name' => $products['products_name'],
                                    'model' => $products['products_model'],
                                    'group_id' => $products['group_id'],
                                    'image' => $products['products_image'],
                                    'price' => $products_price,
//                                    'is_recurrent_order' => $cj_order_date,
                                    'date_shipped' => $cj_order_date,
                                    'next_date_shipped' => $cj_order_date, //$products['next_date_shipped'],
                                    'shipping_day' => $products['shipping_day'],
                                    'shipping_frequency' => $products['shipping_frequency'],
                                    'quantity' => $this->contents[$products_id]['qty'],
                                    'is_bulk' => $products['is_bulk'],
                                    'measure_unit' => $products['measure_unit'],
                                    'products_min_manufacturer_quantity' => $products['products_min_manufacturer_quantity'],
                                    'weight' => $products['products_weight'],
                                    'final_price' => ($products_price + $this->attributes_price($products_id)),
                                    'tax_class_id' => $products['products_tax_class_id'],
                                    'attributes' => (isset($this->contents[$products_id]['attributes']) ? $this->contents[$products_id]['attributes'] : ''));
        }
      }

      return $products_array;
    }

    function show_total() {
      $this->calculate();

      return $this->total;
    }

    function show_weight() {
      $this->calculate();

      return $this->weight;
    }

    function generate_cart_id($length = 5) {
      return tep_create_random_value($length, 'digits');
    }

    function get_content_type() {
      $this->content_type = false;

      if ( (DOWNLOAD_ENABLED == 'true') && ($this->count_contents() > 0) ) {
        reset($this->contents);
        while (list($products_id, ) = each($this->contents)) {
          if (isset($this->contents[$products_id]['attributes'])) {
            reset($this->contents[$products_id]['attributes']);
            while (list(, $value) = each($this->contents[$products_id]['attributes'])) {
              $virtual_check_query = tep_db_query("select count(*) as total from " . TABLE_PRODUCTS_ATTRIBUTES . " pa, " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad where pa.products_id = '" . (int)$products_id . "' and pa.options_values_id = '" . (int)$value . "' and pa.products_attributes_id = pad.products_attributes_id");
              $virtual_check = tep_db_fetch_array($virtual_check_query);

              if ($virtual_check['total'] > 0) {
                switch ($this->content_type) {
                  case 'physical':
                    $this->content_type = 'mixed';

                    return $this->content_type;
                    break;
                  default:
                    $this->content_type = 'virtual';
                    break;
                }
              } else {
                switch ($this->content_type) {
                  case 'virtual':
                    $this->content_type = 'mixed';

                    return $this->content_type;
                    break;
                  default:
                    $this->content_type = 'physical';
                    break;
                }
              }
            }
          } else {
            switch ($this->content_type) {
              case 'virtual':
                $this->content_type = 'mixed';

                return $this->content_type;
                break;
              default:
                $this->content_type = 'physical';
                break;
            }
          }
        }
      } else {
        $this->content_type = 'physical';
      }

      return $this->content_type;
    }

    function unserialize($broken) {
      for(reset($broken);$kv=each($broken);) {
        $key=$kv['key'];
        if (gettype($this->$key)!="user function")
        $this->$key=$kv['value'];
      }
    }

  }
?>
