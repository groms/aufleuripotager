<?php
/*
  $Id: rvd_orders.php,v 0.1 //groms78 2009/03/21

*/

  require('includes/application_top.php');
  $printMode = (isset($HTTP_GET_VARS['print'])&&($HTTP_GET_VARS['print'] == "yes"));  

  if (!tep_session_is_registered('customer_id')) {
    $navigation->set_snapshot();
    tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
  }

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_ACCOUNT);

  $currencies = new currencies();
//  $orders = new orders();

  if (!empty($HTTP_GET_VARS['action'])) {
    if ($HTTP_GET_VARS['action']=="add_other_products") {
      if ($HTTP_POST_VARS['has_milk']=="*") {
        $has_milk = "Y";
      } else {
        $has_milk = "N";
      }
      if ($HTTP_POST_VARS['has_vegetables']=="") {
        $has_vegetables = "NONE";  
      } else {
        $has_vegetables = strtoupper($HTTP_POST_VARS['has_vegetables']);
      }
      if ($HTTP_POST_VARS['has_meat']=="") {
        $has_meat = "NONE";  
      } else {
        $has_meat = strtoupper($HTTP_POST_VARS['has_meat']);
      }
			$sql_add = "UPDATE customers SET 
          has_milk = '".$has_milk."', 
          has_vegetables = '".$has_vegetables."', 
          has_meat = '".$has_meat."'
        WHERE customers_id = ". (int)$customer_id . ";";
      tep_db_query($sql_add, 'db_link');
    }
  }
  
?>

<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
  <title><?php echo TITLE; ?></title>
  <base href="<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
  <link rel="stylesheet" type="text/css" href="stylesheet.css">
  <script>
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
  </script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0">
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<!-- body //-->
<table border="0" width="100%" cellspacing="3" cellpadding="3">
  <tr>
<?
if (!$printMode) {
?>
    <td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">
<!-- left_navigation //-->
<?  require(DIR_WS_INCLUDES . 'column_left.php'); ?>
<!-- left_navigation_eof //-->
    </table></td>
<?
}
?>
<!-- body_text //-->
    <td valign="top">
      <? 
      getDates();
      require($doc_root . $subpath . DIR_WS_FUNCTIONS . 'shared.php');  
      $reload = false;
      
      if (isset($HTTP_POST_VARS['shipping_dates'])&&($HTTP_POST_VARS['shipping_dates']!="")) {
        $order_date_to = $HTTP_POST_VARS['shipping_dates'];
      } else {
        if (isset($HTTP_GET_VARS['shipping_dates'])&&($HTTP_GET_VARS['shipping_dates']!="")) $order_date_to = $HTTP_GET_VARS['shipping_dates'];
      }
      $order_date_from = $order_date_to;
      
      $aux = getBlockExter($customer_id, false); 
if (!$printMode) {
      ?>
      <div>
        <table width="100%">
          <tr>
            <td width="100%" class="pageHeading">Vente directe</td>
            <td align="right" class="smallText"><? echo '<a target="_blank" href="'.tep_href_link(basename($PHP_SELF), tep_get_all_get_params(array('print','order_date_from','order_date_to')).tep_get_all_post_params(array('order_date_from','order_date_to','print'))) . '&print=yes'.'">Imprimer</a>';?></td>
          </tr>
        </table><hr></div>
<?}?>
      <table  width="100%">
        <tr>
          <td class="main">
            <br>
            <?
        		$query = tep_db_query("
                SELECT has_milk, has_vegetables, has_meat FROM customers 
                WHERE customers_id = '".(int)$customer_id."';");
        		$has_milk = "";
        		$has_vegetables = "";
        		$has_meat = "";
            if ($record = tep_db_fetch_array($query)) {
        		  $has_milk = $record['has_milk'];                                                                                                    
        		  $has_vegetables = $record['has_vegetables'];  
        		  $has_meat = $record['has_meat'];  
        		}
            if ($has_milk=="Y") {
              $table_milk = '
                  <tr class="dataTableRow">
                    <td class="dataTableContent" align="center">&nbsp;X&nbsp;</td>
                    <td class="dataTableContent" width="100%" colspan="3">&nbsp;&nbsp;&nbsp;Lait</td>
                  </tr>';
            }
            if (($has_vegetables=="EL")||($has_vegetables=="OTHER")) {
              $table_vegetables = '
                  <tr class="dataTableRow">
                    <td class="dataTableContent" align="center">&nbsp;X&nbsp;</td>
                    <td class="dataTableContent" width="100%" colspan="4">&nbsp;&nbsp;&nbsp;Panier de l&eacute;gumes ('.$has_vegetables.')</td>
                  </tr>';
            }
            if (($has_meat=="LG")||($has_vegetables=="EG")) {
              $table_meat = '
                  <tr class="dataTableRow">
                    <td class="dataTableContent" align="center">&nbsp;X&nbsp;</td>
                    <td class="dataTableContent" width="100%" colspan="4">&nbsp;&nbsp;&nbsp;Viande ('.$has_meat.')</td>
                  </tr>';
            }

            $table = '<tr><td colspan="3" class="pageHeadingSmall" width="100%" >';
            if (!$printMode) {
              $table .= tep_draw_form('date_range', tep_href_link('./rvd_orders.php', '', 'SSL'), 'post'); 
              $table .= 'Jour de livraison s&eacute;lectionn&eacute; : ';
              $table .= tep_draw_pull_down_menu('shipping_dates', $shipping_dates_array, $order_date_to, 'onchange="this.form.submit()"');
              $table .= '</td></tr></form>';
              $table .= '<tr><td colspan="3">&nbsp;</td></tr>';
            }
            $table .= '<tr><td class="pageHeading" width="100%" colspan="3">Liste des produits pour le '.getFormattedLongDate($order_date_to, true)."<br><small><small><i>(&agrave; aller chercher Aufleuripotager)</i></small></small></td></tr>";
            $table_head = '<tr class="dataTableHeadingRow">
                  <td class="dataTableContent" class="dataTableHeadingContent" align="center">Qt&eacute;</td>
                  <td class="dataTableContent" class="dataTableHeadingContent" align="center">Nom du produit</td>
                  <td class="dataTableContent" class="dataTableHeadingContent" align="center">Prix</td>
                </tr>';
            if ($aux!="") {
              $table .= $table_head . $aux;
              $table .= '<tr><td class="main" colspan="5"><i><b>ATTENTION :</b> seules les commandes ponctuelles et les commandes r&eacute;currentes <b>valid&eacute;es</b> (c\'est-&agrave;-dire, prises en compte par le producteur) sont affich&eacute;es dans le tableau ci-dessus.</i></td></tr>';
              $table .= $table_milk.$table_vegetables.$table_meat;
            } else {
              $table .= '<tr><td class="pageHeadingSmall" colspan="5"><small>Aucun produit command&eacute;</small></td></tr>';
            }
            
            echo $table;
            ?>
          </td>
        </tr>
      </table><hr>
      <table width="100%"">
<?            if (!$printMode) {?>
        <tr>
          <td class="main" colspan="5" align="center">
            <b>Produits non consultables sur le site auxquels je suis abonn&eacute; : <i><a href='javascript:showhide("changeOP");'>Afficher</a></i></b> 
            <br><br>
            <div id="changeOP" style="display:none;">
            <?
            echo tep_draw_form('form_other_products', tep_href_link('rvd_orders.php', 'action=add_other_products', 'SSL'), 'post'); 

            ?>
            <center>
            <input type="checkbox" name="has_milk" value="*"<?if ($has_milk=="Y") {echo "checked";}?>><b>Lait</b>
            <br>
            <b>L&eacute;gumes :</b> <input type="radio" name="has_vegetables" value="EL"<?if ($has_vegetables=="EL") {echo "checked";}?>>Eric & Laure
            &nbsp;&nbsp;&nbsp;<input type="radio" name="has_vegetables" value="OTHER" <?if ($has_vegetables=="OTHER") {echo "checked";}?>>Autres 
            &nbsp;&nbsp;&nbsp;<input type="radio" name="has_vegetables" value="NONE" <?if (($has_vegetables=="")||($has_vegetables=="NONE")) {echo "checked";}?>>Aucun
            <br>
            <b>Viande :</b> <input type="radio" name="has_meat" value="LG"<?if ($has_meat=="LG") {echo "checked";}?>>Loic Gu&eacute;rillon
            &nbsp;&nbsp;&nbsp;<input type="radio" name="has_meat" value="EG" <?if ($has_meat=="EG") {echo "checked";}?>>Etienne Gouffault (Lacaprarius)
            &nbsp;&nbsp;&nbsp;<input type="radio" name="has_meat" value="NONE" <?if (($has_meat=="")||($has_meat=="NONE")) {echo "checked";}?>>Aucun
            <br><br>
            <input type="submit" value="Valider">
            </center>
            </form>
            </div>

          </td>
        </tr>
<?}?>
      </table>
    </td>

<?
if (!$printMode) {
?>
    <td width="<?php echo BOX_WIDTH; ?>" valign="top">
      <table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">
        <!-- right_navigation //-->
        <?php require(DIR_WS_INCLUDES . 'column_right.php'); ?>
        <!-- right_navigation_eof //-->
      </table></td>
<?
}
?>

</tr>
</table>
<!-- body_eof //-->

<!-- footer //-->
<?php  require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<br>
</body>
</html>
<?php 

if (!$printMode) require(DIR_WS_INCLUDES . 'application_bottom.php'); 
