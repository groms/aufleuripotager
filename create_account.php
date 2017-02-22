<?
/*
  $Id: login.php,v 1.80 2003/06/05 23:28:24 hpdl Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 osCommerce

  Released under the GNU General Public License
*/

  require('includes/application_top.php');

// redirect the customer to a friendly cookie-must-be-enabled page if cookies are disabled (or the session has not started)
  if ($session_started == false) {
    tep_redirect(tep_href_link(FILENAME_COOKIE_USAGE));
  }

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_LOGIN);
  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CREATE_ACCOUNT);

  $breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_LOGIN, '', 'SSL'));
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <? echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<? echo CHARSET; ?>">
<title><? echo TITLE; ?></title>
<base href="<? echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
<link rel="stylesheet" type="text/css" href="stylesheet.css">
<script language="javascript"><!--
function session_win() {
  window.open("<? echo tep_href_link(FILENAME_INFO_SHOPPING_CART); ?>","info_shopping_cart","height=460,width=430,toolbar=no,statusbar=no,scrollbars=yes").focus();
}
function window_onload()
{
	document.login.password_access.focus();
}
//--></script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" onload="window_onload()">
<!-- header //-->
<? require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<!-- body //-->
<table border="0" width="100%" cellspacing="3" cellpadding="3">
  <tr>
    <td width="<? echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<? echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">
<!-- left_navigation //-->
<? require(DIR_WS_INCLUDES . 'column_left.php'); ?>
<!-- left_navigation_eof //-->
    </table></td>
<!-- body_text //-->
    <td width="100%" valign="top"><? echo tep_draw_form('login', tep_href_link(FILENAME_CREATE_ACCOUNT_AVEC, 'password_given=yes', 'SSL')); ?><table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><? echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><? echo tep_image(DIR_WS_IMAGES . 'table_background_login.gif', HEADING_TITLE, HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><? echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
<?
  if ($messageStack->size('login') > 0) {
?>
      <tr>
        <td><? echo $messageStack->output('login'); ?></td>
      </tr>
      <tr>
        <td><? echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
<?
  }

  if ($cart->count_contents() > 0) {
?>
      <tr>
        <td class="smallText"><? echo TEXT_PASSWORD_PROTECTED; ?></td>
      </tr>
      <tr>
        <td><? echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
<?
  }
?>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
            <tr>
              <td class="main"><b><? echo ENTRY_PASSWORD; ?></b></td>
              <td class="main"><? echo tep_draw_password_field('password_access'); ?></td>
              <td align="right"><? echo tep_image_submit('button_login.gif', IMAGE_BUTTON_LOGIN); ?></td>
            </tr>
        </table></td>
      </tr>
      <tr>
        <td class="smallText"><br><br><? echo sprintf(TEXT_ORIGIN_LOGIN, tep_href_link(FILENAME_LOGIN, tep_get_all_get_params(), 'SSL')); ?></td>
      </tr>
    </table></form></td>
<!-- body_text_eof //-->
    <td width="<? echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<? echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">
<!-- right_navigation //-->
<? require(DIR_WS_INCLUDES . 'column_right.php'); ?>
<!-- right_navigation_eof //-->
    </table></td>
  </tr>
</table>
<!-- body_eof //-->

<!-- footer //-->
<? require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<br>
</body>
</html>
<? require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
