<?
/*
  $Id: countries.php,v 1.28 2003/06/29 22:50:51 hpdl Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 osCommerce

  Released under the GNU General Public License
*/

  require('includes/application_top.php');

  if ($HTTP_GET_VARS['action'] == "modify_auth") {
      $sql_mod = "UPDATE customers_ga SET customers_ga_name = '" .tep_db_input($HTTP_POST_VARS['auth_ga']). "' WHERE customers_ga_id = -1;";
      tep_db_query($sql_mod, 'db_link');
      $reload = true;
  }

  $submit_btn = '<tr class="dataTableRow">
      <td class="dataTableTotalRow" align="center" nowrap colspan="3">
        <input type="submit" value="Modifier">  
      </td></tr>';


  $c_id_list = "";
  $current_ga = -1;
  $ga_sql = "SELECT customers_ga_id, customers_ga_name, next_date_shipped, next_next_date_shipped FROM customers_ga ORDER BY customers_ga_id";
  $ga_query = tep_db_query($ga_sql);
  $ga_array = array();
  $ga_array_all = array();
  $ga_array[] = array('id' => '0', 'text' => '- AUCUN -');
  $ga_array_all[] = array('id' => '-1', 'text' => '');
  $ga_array_all[] = array('id' => '0', 'text' => '- AUCUN -');

  $next_date_shipped_table = '
    <table border="0" width="100%" cellspacing="0" cellpadding="2" style="BORDER: 2px solid;">
      <tr class="dataTableHeadingRow"><td class="dataTableHeadingContent" align="center" nowrap>Groupement d\'achat</td><td class="dataTableHeadingContent" align="center" nowrap>Livraison</td><td class="dataTableHeadingContent" align="center" nowrap>Livraison suivante</td></tr>';

  generateNDSarray();

  $i = 0;
  $reload = false;
  while ($ga = tep_db_fetch_array($ga_query)) {
    if ($ga['customers_ga_id'] > 0) {
      if ($HTTP_GET_VARS['action'] == "modify_next_date") {
        $nds = $HTTP_POST_VARS['next_date_shipped'.$ga['customers_ga_id']];
        $nnds = $HTTP_POST_VARS['next_next_date_shipped'.$ga['customers_ga_id']];
//        echo $nds.$ga_query['next_date_shipped'];
        if (($ga['next_date_shipped']!=$nds)||($ga['next_next_date_shipped']!=$nnds)) {
          update_ga_nds($ga['customers_ga_id'], $nds, $nnds);
        }
        $reload = true;
      } else {
        $ga_array[] = array('id' => $ga['customers_ga_id'], 'text' => $ga['customers_ga_name']);
        $ga_array_all[] = array('id' => $ga['customers_ga_id'], 'text' => $ga['customers_ga_name']);
  
        $ga_name = $ga['customers_ga_name'];
        if ($ga_name == "*") {$ga_name = "<b>Tous</b>";} 
  
//        echo $ga['next_date_shipped'];
  
        $next_date_shipped_table .= '
          <tr class="dataTableRow">
            <td class="dataTableContent">'.$ga_name.'</td>
            <td class="dataTableContent" align="center">'.
              tep_draw_pull_down_menu('next_date_shipped'.$ga['customers_ga_id'], $next_date_shipped_array, $ga['next_date_shipped'], '').'</td>
            <td class="dataTableContent" align="center">'.
              tep_draw_pull_down_menu('next_next_date_shipped'.$ga['customers_ga_id'], $next_date_shipped_array, $ga['next_next_date_shipped'], '').
            '</td>
          </tr>';
        $i += 1;
      }

    } else {
      // l'id == -1 => quel est le groupement d'achat "courant" ?
      $current_auth_ga = $ga['customers_ga_name'];
    }
  }
  
  if (!$reload) {
    if ($i>1) {
      $ga_array[] = array('id' => '*', 'text' => '- TOUS -');
      $ga_array_all[] = array('id' => '*', 'text' => '- TOUS -');
    }
    $next_date_shipped_table .= "</table>".$submit_btn;
  } else {
//    echo $i;
  }

  if (isset($HTTP_GET_VARS['action']))    {
    if (($HTTP_GET_VARS['action'] == "modify")&&(isset($HTTP_POST_VARS['c_id_list']))) {
      if ((isset($HTTP_POST_VARS['ga_id_all']))&&(($HTTP_POST_VARS['ga_id_all']>-1)||($HTTP_POST_VARS['ga_id_all']=="*"))) {
        $sql_mod = "UPDATE customers SET customers_ga_id = '" .$HTTP_POST_VARS['ga_id_all']. "';";
        tep_db_query($sql_mod, 'db_link');
      } else {
        $c_id_array = split(",", $HTTP_POST_VARS['c_id_list']);
        for ($i=0;$i<count($c_id_array);$i++) {
          $c_ga_id = $HTTP_POST_VARS['ga_id'.$c_id_array[$i]];
  //        echo $c_ga_id;
  //        if ( array_key_exists($c_ga_id, $ga_array) ) {
            $sql_mod = "UPDATE customers SET customers_ga_id = '" .$c_ga_id. "' WHERE customers_id = '".$c_id_array[$i]."';";
            tep_db_query($sql_mod, 'db_link');
  //          echo "=updated";
  //        }
  //        echo "<br>";
        }
      }
      $reload = true;
    } 
//exit;
  }

  if ($reload) tep_redirect(tep_href_link('ga.php', ''));


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
<!-- header //-->
<? require($admin_FS_path . DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<!-- body //-->
<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
    <td width="<? echo BOX_WIDTH; ?>" valign="top">
        <table border="0" width="<? echo BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
  <!-- left_navigation //-->
  <? require($admin_FS_path . DIR_WS_INCLUDES . 'column_left.php'); ?>
  <!-- left_navigation_eof //-->
      </table>
    </td>
  <!-- body_text //-->
    <td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading" colspan="2" align="center">Gestion du groupement d'achat</td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading" colspan="2">&nbsp;</td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td>
        <table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr class="pageHeadingSmall" height="25px" valign="top">
            <td align="center" nowrap>
              Affectation des adhérents à un groupement d'achat
            </td>
            <td>&nbsp;</td>
            <td align="center" nowrap>
              Options
            </td>
          </tr>
          <tr>
            <td valign="top">
             <?
             echo tep_draw_form('customers_ga', 'ga.php', 'action=modify', 'post');

             ?> 
              <table border="0" width="100%" cellspacing="0" cellpadding="2" style="BORDER: 2px solid;">
              <? echo $submit_btn; ?>
                <tr class="dataTableHeadingRow">
                  <td class="dataTableHeadingContent" align="center" nowrap>Adhérent</td>
                  <td class="dataTableHeadingContent" align="center" nowrap>Groupement d'achat</td>
                  <td class="dataTableContent" align="center" width="100%">&nbsp;</td>
                </tr>
                <tr class="dataTableRow">
                  <td class="dataTableContent" nowrap>&nbsp;&nbsp;<b>Tous les adhérents</b>&nbsp;&nbsp;</td>
                  <td class="dataTableContent" align="center">&nbsp;&nbsp;<?
                    echo tep_draw_pull_down_menu('ga_id_all', $ga_array_all, -1);
                  ?>
                  &nbsp;&nbsp;</td>
                  <td class="dataTableHeadingContent" align="center" width="100%">&nbsp;</td>
                </tr>
                <tr class="dataTableRow">
                  <td class="dataTableContent" nowrap colspan="3" height="10px"></td>
                </tr>

  <?

    $ga_sql = "select customers_id, customers_firstname, customers_lastname, customers_ga_id from " . TABLE_CUSTOMERS . " order by customers_lastname, customers_firstname";
    $ga_query = tep_db_query($ga_sql);
    while ($ga = tep_db_fetch_array($ga_query)) {
                if ($ga['customers_firstname'] != 'Password') {
                  $c_name = $ga['customers_firstname']." ".strtoupper($ga['customers_lastname']);
                  $c_id_list .= $ga['customers_id'].",";
    ?>
                  <tr class="dataTableRow">
                    <td class="dataTableContent" nowrap>&nbsp;&nbsp;<? echo $c_name; ?>&nbsp;&nbsp;</td>
                    <td class="dataTableContent" align="center">&nbsp;&nbsp;<?
                      echo tep_draw_pull_down_menu('ga_id'.$ga['customers_id'], $ga_array, $ga['customers_ga_id']);
                    ?>
                    &nbsp;&nbsp;</td>
                    <td class="dataTableHeadingContent" align="center" width="100%">&nbsp;</td>
                  </tr>
    <?
                }
    }
              $c_id_list = substr($c_id_list, 0, -1); // on supprime la virgule !
              echo tep_draw_hidden_field('c_id_list', $c_id_list);

              echo $submit_btn; ?>
              
              </table>
              </form>
              </td>
              <td>&nbsp;</td>
              <td width="100%" valign="top">
                <table border="0" width="100%" cellspacing="0" cellpadding="2" style="BORDER: 2px solid;">
                  <tr class="dataTableRow" valign="top">
                    <td class="dataTableContent" nowrap>
               <?
               echo tep_draw_form('form_auth_ga', 'ga.php', 'action=modify_auth', 'post');
                ?>
                    Groupement d'achat pouvant commander : 
                    <?
                    echo tep_draw_pull_down_menu('auth_ga', $ga_array, $current_auth_ga, 'onchange="submit();"');
//                    echo $submit_btn;
                    ?>
              </form>
                    </td>
                  </tr>
                </table><br>
              <table border="0" width="100%" cellspacing="0" cellpadding="2" style="BORDER: 2px solid;">

                  <tr class="dataTableRow" valign="top">
                    <td class="dataTableContent" nowrap align="center">
               <?
               echo tep_draw_form('form_auth_ga', 'ga.php', 'action=modify_next_date', 'post');
                ?>
                    <big><b>Date de la prochaine livraison :</b></big><br><br>
                    <?
                    echo $next_date_shipped_table;
//                    echo getFormattedDate()
                    ?>
              </form>
                    </td>
                  </tr>
                </table>

              </td>
          </tr>
        </table></td></tr>
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
