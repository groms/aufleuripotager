<?
/*
  $Id: define_language.php,v 1.15 2003/07/08 21:51:37 hpdl Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 osCommerce

  Released under the GNU General Public License
*/

  require('includes/application_top.php');

  if (!isset($HTTP_GET_VARS['lngdir'])) $HTTP_GET_VARS['lngdir'] = $language;

  $action = (isset($HTTP_GET_VARS['action']) ? $HTTP_GET_VARS['action'] : '');

  if (tep_not_null($action)) {
    switch ($action) {
      case 'save':
        if (isset($HTTP_GET_VARS['lngdir']) && isset($HTTP_GET_VARS['filename'])) {
          if ($HTTP_GET_VARS['filename'] == $HTTP_GET_VARS['lngdir'] . '.php') {
            $file = DIR_FS_CATALOG_LANGUAGES . $HTTP_GET_VARS['filename'];
          } else {
            $file = DIR_FS_CATALOG_LANGUAGES . $HTTP_GET_VARS['lngdir'] . '/' . $HTTP_GET_VARS['filename'];
          }

          if (file_exists($file)) {
            if (file_exists('bak' . $file)) {
              @unlink('bak' . $file);
            }

            @rename($file, 'bak' . $file);

            $new_file = fopen($file, 'w');
            $file_contents = stripslashes($HTTP_POST_VARS['file_contents']);
            fwrite($new_file, $file_contents, strlen($file_contents));
            fclose($new_file);
          }
          tep_redirect(tep_href_link(FILENAME_DEFINE_LANGUAGE, 'lngdir=' . $HTTP_GET_VARS['lngdir']));
        }
        break;
    }
  }

  $languages_array = array();
  $languages = tep_get_languages();
  $lng_exists = false;
  for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
    if ($languages[$i]['directory'] == $HTTP_GET_VARS['lngdir']) $lng_exists = true;

    $languages_array[] = array('id' => $languages[$i]['directory'],
                               'text' => $languages[$i]['name']);
  }

  if (!$lng_exists) $HTTP_GET_VARS['lngdir'] = $language;
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <? echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<? echo CHARSET; ?>">
<title><? echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
<!-- header //-->
<? require($admin_FS_path . DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<!-- body //-->
<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
    <td width="<? echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<? echo BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
<!-- left_navigation //-->
<? require($admin_FS_path . DIR_WS_INCLUDES . 'column_left.php'); ?>
<!-- left_navigation_eof //-->
    </table></td>
<!-- body_text //-->
    <td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr><? echo tep_draw_form('lng', FILENAME_DEFINE_LANGUAGE, '', 'get'); ?>
            <td class="pageHeading"><? echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><? echo tep_draw_separator('pixel_trans.gif', '1', HEADING_IMAGE_HEIGHT); ?></td>
            <td class="pageHeading" align="right"><? echo tep_draw_pull_down_menu('lngdir', $languages_array, $language, 'onChange="this.form.submit();"'); ?></td>
          </form></tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
<?
  if (isset($HTTP_GET_VARS['lngdir']) && isset($HTTP_GET_VARS['filename'])) {
    if ($HTTP_GET_VARS['filename'] == $HTTP_GET_VARS['lngdir'] . '.php') {
      $file = DIR_FS_CATALOG_LANGUAGES . $HTTP_GET_VARS['filename'];
    } else {
      $file = DIR_FS_CATALOG_LANGUAGES . $HTTP_GET_VARS['lngdir'] . '/' . $HTTP_GET_VARS['filename'];
    }

    if (file_exists($file)) {
      $file_array = file($file);
      $contents = implode('', $file_array);

      $file_writeable = true;
      if (!is_writeable($file)) {
        $file_writeable = false;
        $messageStack->reset();
        $messageStack->add(sprintf(ERROR_FILE_NOT_WRITEABLE, $file), 'error');
        echo $messageStack->output();
      }

?>
          <tr><? echo tep_draw_form('language', FILENAME_DEFINE_LANGUAGE, 'lngdir=' . $HTTP_GET_VARS['lngdir'] . '&filename=' . $HTTP_GET_VARS['filename'] . '&action=save'); ?>
            <td><table border="0" cellspacing="0" cellpadding="2">
              <tr>
                <td class="main"><b><? echo $HTTP_GET_VARS['filename']; ?></b></td>
              </tr>
              <tr>
                <td class="main"><? echo tep_draw_textarea_field('file_contents', 'soft', '80', '20', $contents, (($file_writeable) ? '' : 'readonly')); ?></td>
              </tr>
              <tr>
                <td><? echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
              </tr>
              <tr>
                <td align="right"><? if ($file_writeable == true) { echo tep_image_submit('button_save.gif', IMAGE_SAVE) . '&nbsp;<a href="' . tep_href_link(FILENAME_DEFINE_LANGUAGE, 'lngdir=' . $HTTP_GET_VARS['lngdir']) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>'; } else { echo '<a href="' . tep_href_link(FILENAME_DEFINE_LANGUAGE, 'lngdir=' . $HTTP_GET_VARS['lngdir']) . '">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a>'; } ?></td>
              </tr>
            </table></td>
          </form></tr>
<?
    } else {
?>
          <tr>
            <td class="main"><b><? echo TEXT_FILE_DOES_NOT_EXIST; ?></b></td>
          </tr>
          <tr>
            <td><? echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td><? echo '<a href="' . tep_href_link(FILENAME_DEFINE_LANGUAGE, 'lngdir=' . $HTTP_GET_VARS['lngdir']) . '">' . tep_image_button('button_back.gif', IMAGE_BACK) . '</a>'; ?></td>
          </tr>
<?
    }
  } else {
    $filename = $HTTP_GET_VARS['lngdir'] . '.php';
?>
          <tr>
            <td><table width="100%" border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td class="smallText"><a href="<? echo tep_href_link(FILENAME_DEFINE_LANGUAGE, 'lngdir=' . $HTTP_GET_VARS['lngdir'] . '&filename=' . $filename); ?>"><b><? echo $filename; ?></b></a></td>
<?
    $left = false;
    if ($dir = dir(DIR_FS_CATALOG_LANGUAGES . $HTTP_GET_VARS['lngdir'])) {
      $file_extension = substr($PHP_SELF, strrpos($PHP_SELF, '.'));
      while ($file = $dir->read()) {
        if (substr($file, strrpos($file, '.')) == $file_extension) {
          echo '                <td class="smallText"><a href="' . tep_href_link(FILENAME_DEFINE_LANGUAGE, 'lngdir=' . $HTTP_GET_VARS['lngdir'] . '&filename=' . $file) . '">' . $file . '</a></td>' . "\n";
          if (!$left) {
            echo '              </tr>' . "\n" .
                 '              <tr>' . "\n";
          }
          $left = !$left;
        }
      }
      $dir->close();
    }
?>
              </tr>
            </table></td>
          </tr>
          <tr>
            <td><? echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td align="right"><? echo '<a href="' . tep_href_link(FILENAME_FILE_MANAGER, 'current_path=' . DIR_FS_CATALOG_LANGUAGES . $HTTP_GET_VARS['lngdir']) . '">' . tep_image_button('button_file_manager.gif', IMAGE_FILE_MANAGER) . '</a>'; ?></td>
          </tr>
<?
  }
?>
        </table></td>
      </tr>
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
