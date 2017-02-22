<?php
/*
  $Id: message.php,v 0.1 //groms78 2008/01/17

*/

  $site_not_available = (isset($HTTP_GET_VARS['msgtype']) && ($HTTP_GET_VARS['msgtype']=="site_not_available"));

  if (!$site_not_available) {
    require('includes/application_top.php'); 
  }

  $txt = "";
  if (isset($HTTP_GET_VARS['msgtype'])) {
    if ($HTTP_GET_VARS['msgtype']=="not_allowed_ga") {
      $class = "messageStackErrorBig";
      $txt = "
        Ce produit est un produit du groupement d'achat et vous n'êtes pas autoris&eacute;(e) &agrave; le commander actuellement.
        <br>Veuillez vous identifier de nouveau sur le site en cliquant <a href='" . tep_href_link(FILENAME_LOGIN, '', 'SSL') . "'>ici</a>.";
    }
    else if ($HTTP_GET_VARS['msgtype']=="please_login") {
      $class = "messageStackWarningBig";
      $txt = "
        Vous n'êtes pas autoris&eacute; &agrave; commander ce produit.
        <br>Veuillez vous <big>identifier</big> de nouveau sur le site en cliquant <a href='" . tep_href_link(FILENAME_LOGIN, '', 'SSL') . "'><big>ici</big></a>.";
    }
    else if ($HTTP_GET_VARS['msgtype']=="site_not_available") {
      $class = "messageStackErrorBig";
      $txt = "
        <big>Le site est en cours de maintenance.<br>
        Veuillez vous connecter ult&eacute;rieurement.</big>";
      $site_not_available = true;
      define('TITLE', 'Site indisponible pour maintenance');
      define('HTML_PARAMS', '');
      define('DIR_WS_INCLUDES', '');
      $_SERVER['HTTP_REFERER'] = './index.php';
    }
    else if ($HTTP_GET_VARS['msgtype']=="product_limit_exceeded") {
      $class = "messageStackErrorBig";
      $txt = "
        Le produit que vous souhaitez ajouter dans votre panier n&quote;est plus disponible dans la quantit&eacute; demand&eacute;e pour cette semaine.<br>";
      $nb_dispo = 0;
      if (isset($HTTP_GET_VARS['nb_dispo'])) {
        $nb_dispo = (int)$HTTP_GET_VARS['nb_dispo'];
      }
      if (isset($HTTP_GET_VARS['p_name'])) {
        $p_name = $HTTP_GET_VARS['p_name'];
      }
      if (isset($HTTP_GET_VARS['m_id'])) {
        $m_id = $HTTP_GET_VARS['m_id'];
      }
      if ($nb_dispo > 0) {
        $txt .= "
          Il ne reste en effet plus que '<i>".$nb_dispo." ".$p_name."</i>' disponibles cette semaine. Veuillez r&eacute;duire la quantit&eacute; &agrave; commander";
      } else {
        $txt .= "
          Il n'est en effet plus possible de commander des '<i>".$p_name."</i>' pour cette semaine. Veuillez r&eacute;essayer la semaine prochaine";
      }
      $txt .= " ou contacter <a href='./manufacturers_info.php?manufacturers_id=".$m_id."'>le producteur</a>.";
    }
    else if ($HTTP_GET_VARS['msgtype']=="not_allowed_ga_already_in_basket") {
      $class = "messageStackErrorBig";
      $txt = "
        Ce produit est un produit du <a href='". tep_href_link("message.php", "msgtype=rvd_ga_def") . "'>R&eacute;seau de Vente Directe</a>.<br>
        Vous avez d&eacute;j&agrave; mis des articles du <a href='". tep_href_link("message.php", "msgtype=rvd_ga_def") . "'>Groupement d&quote;Achat</a> dans votre panier.<br>
        Vous ne pouvez donc pas l&quote;ajouter car il est impossible de m&eacute;langer les commandes de ces deux r&eacute;seaux.";
    }
    else if ($HTTP_GET_VARS['msgtype']=="not_allowed_rvd_already_in_basket") {
      $class = "messageStackErrorBig";
      $txt = "
        Ce produit est un produit du <a href='". tep_href_link("message.php", "msgtype=rvd_ga_def") . "'>Groupement d&quote;Achat</a>.
        <br>
        Vous avez d&eacute;j&agrave; mis des articles du <a href='". tep_href_link("message.php", "msgtype=rvd_ga_def") . "'>R&eacute;seau de Vente Directe</a> dans votre panier.<br>
        Vous ne pouvez donc pas l'ajouter car il est impossible de m&eacute;langer les commandes de ces deux r&eacute;seaux.";
    }
    else if ($HTTP_GET_VARS['msgtype']=="product_not_found") {
      $class = "messageStackErrorBig";
      $txt = "
        ERREUR : ce produit n'a pas &eacute;t&eacute; trouv&eacute; dans la base de donn&eacute;es.";
    }
    else if ($HTTP_GET_VARS['msgtype']=="not_allowed") {
      $class = "messageStackErrorBig";
      $txt = "
        Vous n'êtes pas autoris&eacute;(e) à commander ce produit actuellement.";
    }
    else if ($HTTP_GET_VARS['msgtype']=="orders_are_frozen") {
      $r_only = ((isset($HTTP_GET_VARS['r_only']))&&($HTTP_GET_VARS['r_only'] == "yes"));
      $class = "mainSmall";
      if (!$r_only) {
        $txt = "
          <big>Le producteur <b>".stripslashes($HTTP_GET_VARS['m_name'])."</b> a d&eacute;jà valid&eacute; ses commandes pour le <b> ".getFormattedLongDate($HTTP_GET_VARS['date_backup'], true)."</b>.<br>
          La livraison de ce produit est donc report&eacute;e au <b> ". getFormattedLongDate($HTTP_GET_VARS['date_next'], true) ."</b></big>.";
/*
      } else {
        $txt = "
          <big>Le producteur <b>".stripslashes($HTTP_GET_VARS['m_name'])."</b> a d&eacute;jà valid&eacute; ses commandes <u>r&eacute;currentes</u> pour le <b> ".getFormattedLongDate($HTTP_GET_VARS['date_backup'], true)."</b>.<br>
          Si vous souhaitez effectuer une commande r&eacute;currente, ce produit sera pris en compte pour la <u>livraison suivante</u>, le <b> ".getFormattedLongDate($HTTP_GET_VARS['date_next'], true)."</b></big>.";
*/
      }
    }
    else if ($HTTP_GET_VARS['msgtype']=="rvd_ga_def") {
      $class = "main";
      $txt = "
				<big><big><b>R&eacute;seau de Vente Directe</b></big></big>
				<br><br>
				Dans le cadre du <b>R&eacute;seau de Vente Directe (RVD)</b>, des producteurs locaux bio. proposent des produits locaux, bio ou raisonn&eacyte;
				
				Pour plus d'informations, consultez notre <a href='http://aufleuripotager.weebly.com'>site internet</a>.";
    }
    if ($class == "messageStackErrorBig") {
      $txt .= "<br>
        Pour de plus amples renseignements, n'h&eacute;sitez pas &agrave; nous contacter &agrave; l'adresse suivante : 
            <a href='mailto:aufleuripotager@yahoo.fr'>aufleuripotager@yahoo.fr</a>.";
    }
    if ($site_not_available) { 
      $where = "l'accueil";
    } else {
      $where = "la page pr&eacute;c&eacute;dente";
    }  
    $txt .= "<br><br><a href='".$_SERVER['HTTP_REFERER']."'><b>Revenir à $where</b></a>";
    
  }

?>

<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<?   

if (!$site_not_available) {
  echo '<meta http-equiv="Content-Type" content="text/html; charset='.CHARSET.'">';
  echo '<base href="'.(($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG.'">';
}
?>
<title><?php echo TITLE; ?></title>

<link rel="stylesheet" type="text/css" href="stylesheet.css">
</head>

<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0">


<!-- header //-->
<?php if (!$site_not_available) require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<!-- body //-->
<table border="0" width="100%" cellspacing="3" cellpadding="3">
<? if ($site_not_available) {
  $logo = "<tr>
              <td class='pageHeading' valign='middle' nowrap><img src='images/logo.gif' width='170' height='93'></td>
              <td class='pageHeading'><big>Aufleuripotager</big></td>
            </tr>";
  echo $logo;
}
?>
  <tr>
    <td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">
<!-- left_navigation //-->
<?php if (!$site_not_available) require(DIR_WS_INCLUDES . 'column_left.php'); ?>
<!-- left_navigation_eof //-->
    </table></td>
<!-- body_text //-->
<td class="<?=$class?>" valign="top">
<?=$txt?>
</td>

    <td width="<?php echo BOX_WIDTH; ?>" valign="top">
      <table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">
        <!-- right_navigation //-->
        <?php if (!$site_not_available) require(DIR_WS_INCLUDES . 'column_right.php'); ?>
        <!-- right_navigation_eof //-->
      </table></td>
</tr>
<tr height="100%"><td height="100%">&nbsp;</td></tr>
</table>
<table border="0" width="100%" height="100%" cellspacing="3" cellpadding="3">
  <tr height="100%"><td height="100%"></td></tr>
</table>
<!-- body_eof //-->

<!-- footer //-->
<?php if (!$site_not_available) require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<br>
</body>
</html>
<?php 
if (!$site_not_available) require(DIR_WS_INCLUDES . 'application_bottom.php');
?>  