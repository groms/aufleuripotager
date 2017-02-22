<?php
/*
  $Id: product_info.php,v 1.97 2003/07/01 14:34:54 hpdl Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 osCommerce

  Released under the GNU General Public License
*/

  require('includes/application_top.php');

//  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_PRODUCT_INFO);

  $sql = "SELECT m.manufacturers_id, 
      mi.manufacturers_url, 
      mi.infotext, 
      mi.main_product, 
      mi.quality_label,
      mi.organic_product, 
      m.manufacturers_name,
      m.manufacturers_people_name,
      m.manufacturers_address,
      m.manufacturers_tel,
      m.manufacturers_email,
      m.manufacturers_image,
      m.date_added,
      m.last_modified,
      m.group_id,
      mg.group_name
      FROM " . TABLE_MANUFACTURERS . " AS m " .
     "LEFT JOIN " . TABLE_MANUFACTURERS_GROUPS . " AS mg ON mg.group_id = m.group_id " .  
     "LEFT JOIN " . TABLE_MANUFACTURERS_INFO . " AS mi ON mi.manufacturers_id = m.manufacturers_id ";  
  
  if(isset($HTTP_GET_VARS['manufacturers_id'])){
    $sql1 = 
     "LEFT JOIN " . TABLE_PRODUCTS . " AS p ON p.manufacturers_id = m.manufacturers_id 
      WHERE m.manufacturers_id = " . $HTTP_GET_VARS['manufacturers_id'];
  }
  else {
    $sql1 = " GROUP BY m.group_id, m.manufacturers_name ORDER BY m.group_id, m.manufacturers_name";
  }
  $sql = $sql . $sql1 . ";";
  $manufacturer_query = tep_db_query($sql);

?>

<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<base href="<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
<link rel="stylesheet" type="text/css" href="stylesheet.css">
<script language="javascript"><!--
function popupWindow(url) {
  window.open(url,'popupWindow','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=yes,copyhistory=no,width=100,height=100,screenX=150,screenY=150,top=150,left=150')
}
//--></script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0">
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<!-- body //-->
<table border="0" width="100%" cellspacing="3" cellpadding="3">
  <tr>
    <td width="<?php echo BOX_WIDTH; ?>" valign="top">
    <table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">
<!-- left_navigation //-->
<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>
<!-- left_navigation_eof //-->
    </table></td>
<!-- body_text //-->
    <td width="100%" valign="top">
      <table border="0" width="100%" cellspacing="0" cellpadding="0">
<?php
  if(isset($HTTP_GET_VARS['manufacturers_id'])){
      $manufacturer = tep_db_fetch_array($manufacturer_query);
?>
      <tr>
        <td colspan="2" class="pageHeading">Informations sur le producteur : <i><?=$manufacturer['manufacturers_name']?></i></td>
      </tr>
      <tr>
        <td valign="top">
           <table border="0" width="100%" cellspacing="20" cellpadding="0">
              <tr><td class="main">
                 <big>
                    <?
                    echo str_replace("\n", "<BR><BR>", $manufacturer['infotext']);
                    ?>
                 </big>
              </td></tr>
          </table>
        </td>
        <td valign="top">
          <table border="0" width="100%" cellspacing="20" cellpadding="0"><tr><td colspan="2">
          <?           
          echo tep_image(DIR_WS_CATALOG_IMAGES . $manufacturer['manufacturers_image'], addslashes($manufacturer['manufacturers_name']), 320, 240, 'hspace="5" vspace="5"', 320);
          ?>
          </td></tr>
          <tr><td class="main" colspan="2">
          <? echo 
            "<big><b>".$manufacturer['manufacturers_name']."</b></big>";
            if ($manufacturer['manufacturers_people_name'] != "") {
              echo " (".$manufacturer['manufacturers_people_name'].")";
            }
            echo "<br>";
            if ($manufacturer['main_product'] != "") {
              echo "<b><u>Produit :</u> ".$manufacturer['main_product']."</b><br>";
            }
          ?>
          </td></tr>
          <tr><td class="main">
          <?
            echo str_replace("\n", "<BR>", $manufacturer['manufacturers_address'])."<br>".
            $manufacturer['manufacturers_tel']."<br>".
            "<b><a href='mailto:".$manufacturer['manufacturers_email']."'>".$manufacturer['manufacturers_email']."</a></b>";

            if ($manufacturer['manufacturers_url'] != "") {
              echo "<br><br><u><a href='".$manufacturer['manufacturers_url']."'>".$manufacturer['manufacturers_url']."</a></u>";
            }
          ?>
          </td>
          <td class="main" align="center" valign="middle">
          <?
          if ($manufacturer['organic_product'] == 1) {
            echo tep_image(DIR_WS_IMAGES . "logo-ab.jpg", addslashes($manufacturer['quality_label']), 44, 54, 'hspace="5" vspace="5"', 54);
            if ($manufacturer['quality_label'] != "") {
              echo "<br><small><b><u>Label :</u> ".$manufacturer['quality_label']."</b></small>";
            }
          }
          ?>
            
          </td>
          <?
          ?>
          </td></tr>
          </table>
        </td>
      </tr>
      <tr>
        <td colspan="2" ><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td colspan="2" ><hr></td>
      </tr>
      <tr>
        <td colspan="2" class="main"><big>&nbsp;&nbsp;Pour avoir la liste des produits de ce producteur, veuillez cliquer <?php echo '<a href="' . tep_href_link(FILENAME_DEFAULT, 'manufacturers_id='.$manufacturer['manufacturers_id']) . '"><b>ici</b></a>.';?></big></td>
      </tr>
<?php
  } else {?>
      <tr>
        <td class="pageHeading" colspan="2">Liste des producteurs partenaires du LOCAL</td>
      </tr>
      <tr>
        <td colspan="2" ><br><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <?
      $done0 = false;
      $done1 = false;
    	while ($manufacturer = tep_db_fetch_array($manufacturer_query)) {
        if (($manufacturer['group_id'] == '0') && (!$done1)){
          $done1 = true;?>
          <tr>
            <td colspan="2" class="main"><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
          </tr>
          <tr>
            <td colspan="2" class="main">&nbsp;&nbsp;<big><b>Producteurs de la <a href='<?=tep_href_link("message.php", "msgtype=rvd_ga_def")?>'>Vente Directe</a></b></big></td>
          </tr><?
        }
        if (($manufacturer['group_id'] == '1') && (!$done2)){
          $done2 = true;?>
          <tr>
            <td colspan="2" class="main"><hr>&nbsp;&nbsp;<big><b><a href='<?=tep_href_link("message.php", "msgtype=rvd_ga_def")?>'><?=$manufacturer['group_name']?></a></b></big></td>
          </tr><?
        }?>
        <tr>
          <td>&nbsp;&nbsp;&nbsp;<?
            if (tep_not_null($manufacturer['manufacturers_image'])) {
              echo tep_image(DIR_WS_CATALOG_IMAGES . $manufacturer['manufacturers_image'], '', 32, 32, '', 32);
            } else {
              echo tep_image(DIR_WS_ICONS . 'preview.gif', '', 32, 32);
            }
          
          ?>&nbsp;&nbsp;&nbsp;</td>
          <td class="main" width="100%"><?php 
            $m_name = $manufacturer['manufacturers_name'];

            if ($manufacturer['manufacturers_people_name'] != "") {
              $m_name = $tb . $m_name . " (" . $manufacturer['manufacturers_people_name'] . ")";
            }
            echo '<a href="' . tep_href_link(FILENAME_MANUFACTURERS_INFO, 'manufacturers_id='.$manufacturer['manufacturers_id']) . '">' . $m_name . '</a>';?></td>
        </tr><?
      }
  }?>
      <tr>
        <td colspan="2" ><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td colspan="2" ><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td colspan="2" ><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td align="right"><?php echo '<a href="' . tep_href_link(FILENAME_DEFAULT) . '">' . tep_image_button('button_continue.gif', IMAGE_BUTTON_CONTINUE) . '</a>'; ?></td>
                <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
    </table></td>
<!-- body_text_eof //-->
    <td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">
<!-- right_navigation //-->
<?php require(DIR_WS_INCLUDES . 'column_right.php'); ?>
<!-- right_navigation_eof //-->
    </table></td>
  </tr>
</table>
<!-- body_eof //-->

<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<br>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
