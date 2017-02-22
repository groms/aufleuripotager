<?
/*
  $Id: create_account.php,v 1.65 2003/06/09 23:03:54 hpdl Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 osCommerce

  Released under the GNU General Public License
*/

  require('includes/application_top.php');

// needs to be included earlier to set the success message in the messageStack
  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CREATE_ACCOUNT);

  $password_access_ok = false;
  $process = false;
  $password_access = "";
  $password_access_valid = "blamont25";
  
  if (isset($HTTP_POST_VARS['password_access']) && isset($HTTP_GET_VARS['password_given']) && ($HTTP_GET_VARS['password_given'] == 'yes')) {
    $password_access = tep_db_prepare_input($HTTP_POST_VARS['password_access']);
    $password_access_ok = ($password_access == $password_access_valid);
  }

  if (!$password_access_ok) {
    $messageStack->add('create_account', ENTRY_PASSWORD_ERROR);
  }
  else {
    if (isset($HTTP_POST_VARS['action']) && ($HTTP_POST_VARS['action'] == 'process')) {
      $process = true;
  
      if (ACCOUNT_GENDER == 'true') {
        if (isset($HTTP_POST_VARS['gender'])) {
          $gender = tep_db_prepare_input($HTTP_POST_VARS['gender']);
        } else {
          $gender = false;
        }
      }
      $firstname = tep_db_prepare_input($HTTP_POST_VARS['firstname']);
      $lastname = tep_db_prepare_input($HTTP_POST_VARS['lastname']);
      if (ACCOUNT_DOB == 'true') $dob = tep_db_prepare_input($HTTP_POST_VARS['dob']);
      $email_address = tep_db_prepare_input($HTTP_POST_VARS['email_address']);
      if (ACCOUNT_COMPANY == 'true') $company = tep_db_prepare_input($HTTP_POST_VARS['company']);
      $street_address = tep_db_prepare_input($HTTP_POST_VARS['street_address']);
      if (ACCOUNT_SUBURB == 'true') $suburb = tep_db_prepare_input($HTTP_POST_VARS['suburb']);
      $postcode = tep_db_prepare_input($HTTP_POST_VARS['postcode']);
      $city = tep_db_prepare_input($HTTP_POST_VARS['city']);
      if (ACCOUNT_STATE == 'true') {
        $state = tep_db_prepare_input($HTTP_POST_VARS['state']);
        if (isset($HTTP_POST_VARS['zone_id'])) {
          $zone_id = tep_db_prepare_input($HTTP_POST_VARS['zone_id']);
        } else {
          $zone_id = false;
        }
      }
      $country = tep_db_prepare_input($HTTP_POST_VARS['country']);
      $telephone = tep_db_prepare_input($HTTP_POST_VARS['telephone']);
      $fax = tep_db_prepare_input($HTTP_POST_VARS['fax']);
      if (isset($HTTP_POST_VARS['newsletter'])) {
        $newsletter = tep_db_prepare_input($HTTP_POST_VARS['newsletter']);
      } else {
        $newsletter = false;
      }
      $password = tep_db_prepare_input($HTTP_POST_VARS['password']);
      $confirmation = tep_db_prepare_input($HTTP_POST_VARS['confirmation']);
  
      $error = false;
  
      if (ACCOUNT_GENDER == 'true') {
        if ( ($gender != 'm') && ($gender != 'f') ) {
          $error = true;
  
          $messageStack->add('create_account', ENTRY_GENDER_ERROR);
        }
      }
  
      if (strlen($firstname) < ENTRY_FIRST_NAME_MIN_LENGTH) {
        $error = true;
  
        $messageStack->add('create_account', ENTRY_FIRST_NAME_ERROR);
      }
  
      if (strlen($lastname) < ENTRY_LAST_NAME_MIN_LENGTH) {
        $error = true;
  
        $messageStack->add('create_account', ENTRY_LAST_NAME_ERROR);
      }
  
      if (ACCOUNT_DOB == 'true') {
        if (checkdate(substr(tep_date_raw($dob), 4, 2), substr(tep_date_raw($dob), 6, 2), substr(tep_date_raw($dob), 0, 4)) == false) {
          $error = true;
  
          $messageStack->add('create_account', ENTRY_DATE_OF_BIRTH_ERROR);
        }
      }
  
      if (strlen($email_address) < ENTRY_EMAIL_ADDRESS_MIN_LENGTH) {
        $error = true;
  
        $messageStack->add('create_account', ENTRY_EMAIL_ADDRESS_ERROR);
      } elseif (tep_validate_email($email_address) == false) {
        $error = true;
  
        $messageStack->add('create_account', ENTRY_EMAIL_ADDRESS_CHECK_ERROR);
      } else {
        $check_email_query = tep_db_query("select count(*) as total from " . TABLE_CUSTOMERS . " where customers_email_address = '" . tep_db_input($email_address) . "'");
        $check_email = tep_db_fetch_array($check_email_query);
        if ($check_email['total'] > 0) {
          $error = true;
  
          $messageStack->add('create_account', ENTRY_EMAIL_ADDRESS_ERROR_EXISTS);
        }
      }
  
      if (strlen($street_address) < ENTRY_STREET_ADDRESS_MIN_LENGTH) {
        $error = true;
  
        $messageStack->add('create_account', ENTRY_STREET_ADDRESS_ERROR);
      }
  
      if (strlen($postcode) < ENTRY_POSTCODE_MIN_LENGTH) {
        $error = true;
  
        $messageStack->add('create_account', ENTRY_POST_CODE_ERROR);
      }
  
      if (strlen($city) < ENTRY_CITY_MIN_LENGTH) {
        $error = true;
  
        $messageStack->add('create_account', ENTRY_CITY_ERROR);
      }
  
      if (is_numeric($country) == false) {
        $error = true;
  
        $messageStack->add('create_account', ENTRY_COUNTRY_ERROR);
      }
  
      if (ACCOUNT_STATE == 'true') {
        $zone_id = 0;
        $check_query = tep_db_query("select count(*) as total from " . TABLE_ZONES . " where zone_country_id = '" . (int)$country . "'");
        $check = tep_db_fetch_array($check_query);
        $entry_state_has_zones = ($check['total'] > 0);
        if ($entry_state_has_zones == true) {
          $zone_query = tep_db_query("select distinct zone_id from " . TABLE_ZONES . " where zone_country_id = '" . (int)$country . "' and (zone_name like '" . tep_db_input($state) . "%' or zone_code like '%" . tep_db_input($state) . "%')");
          if (tep_db_num_rows($zone_query) == 1) {
            $zone = tep_db_fetch_array($zone_query);
            $zone_id = $zone['zone_id'];
          } else {
            $error = true;
  
            $messageStack->add('create_account', ENTRY_STATE_ERROR_SELECT);
          }
        } else {
          if (strlen($state) < ENTRY_STATE_MIN_LENGTH) {
            $error = true;
  
            $messageStack->add('create_account', ENTRY_STATE_ERROR);
          }
        }
      }
  
      if (strlen($telephone) < ENTRY_TELEPHONE_MIN_LENGTH) {
        $error = true;
  
        $messageStack->add('create_account', ENTRY_TELEPHONE_NUMBER_ERROR);
      }
  
  
      if (strlen($password) < ENTRY_PASSWORD_MIN_LENGTH) {
        $error = true;
  
        $messageStack->add('create_account', ENTRY_PASSWORD_ERROR);
      } elseif ($password != $confirmation) {
        $error = true;
  
        $messageStack->add('create_account', ENTRY_PASSWORD_ERROR_NOT_MATCHING);
      }
    }
  }


  if ((!$error) && ($process)) {
    $sql_data_array = array('customers_firstname' => $firstname,
                            'customers_lastname' => $lastname,
                            'customers_email_address' => $email_address,
                            'customers_telephone' => $telephone,
                            'customers_ga_id' => 1, // CJ 2010-06-21
                            'customers_fax' => $fax,
                            'customers_newsletter' => $newsletter,
                            'customers_password' => tep_encrypt_password($password));

    if (ACCOUNT_GENDER == 'true') $sql_data_array['customers_gender'] = $gender;
    if (ACCOUNT_DOB == 'true') $sql_data_array['customers_dob'] = tep_date_raw($dob);

    tep_db_perform(TABLE_CUSTOMERS, $sql_data_array);

    $customer_id = tep_db_insert_id();

    $sql_data_array = array('customers_id' => $customer_id,
                            'entry_firstname' => $firstname,
                            'entry_lastname' => $lastname,
                            'entry_street_address' => $street_address,
                            'entry_postcode' => $postcode,
                            'entry_city' => $city,
                            'entry_country_id' => $country);

    if (ACCOUNT_GENDER == 'true') $sql_data_array['entry_gender'] = $gender;
    if (ACCOUNT_COMPANY == 'true') $sql_data_array['entry_company'] = $company;
    if (ACCOUNT_SUBURB == 'true') $sql_data_array['entry_suburb'] = $suburb;
    if (ACCOUNT_STATE == 'true') {
      if ($zone_id > 0) {
        $sql_data_array['entry_zone_id'] = $zone_id;
        $sql_data_array['entry_state'] = '';
      } else {
        $sql_data_array['entry_zone_id'] = '0';
        $sql_data_array['entry_state'] = $state;
      }
    }

    tep_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array);

    $address_id = tep_db_insert_id();

    tep_db_query("update " . TABLE_CUSTOMERS . " set customers_default_address_id = '" . (int)$address_id . "' where customers_id = '" . (int)$customer_id . "'");

    tep_db_query("insert into " . TABLE_CUSTOMERS_INFO . " (customers_info_id, customers_info_number_of_logons, customers_info_date_account_created) values ('" . (int)$customer_id . "', '0', now())");

    if (SESSION_RECREATE == 'True') {
      tep_session_recreate();
    }

    $customer_first_name = $firstname;
    $customer_default_address_id = $address_id;
    $customer_country_id = $country;
    $customer_zone_id = $zone_id;
    tep_session_register('customer_id');
    tep_session_register('customer_first_name');
    tep_session_register('customer_default_address_id');
    tep_session_register('customer_country_id');
    tep_session_register('customer_zone_id');

// restore cart contents
    $cart->restore_contents();

// build the message content
    $name = $firstname . ' ' . $lastname;

    if (ACCOUNT_GENDER == 'true') {
       if ($gender == 'm') {
         $email_text = sprintf(EMAIL_GREET_MR, $name); //$lastname
       } else {
         $email_text = sprintf(EMAIL_GREET_MS, $name); //$lastname
       }
    } else {
      $email_text = sprintf(EMAIL_GREET_NONE, $firstname);
    }

    $email_text .= EMAIL_WELCOME . EMAIL_TEXT . EMAIL_CONTACT . EMAIL_WARNING;
    tep_mail($name, $email_address, EMAIL_SUBJECT, $email_text, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);

    tep_redirect(tep_href_link(FILENAME_CREATE_ACCOUNT_SUCCESS, '', 'SSL'));
  }

  $breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_CREATE_ACCOUNT, '', 'SSL'));
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <? echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<? echo CHARSET; ?>">
<title><? echo TITLE; ?></title>
<base href="<? echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
<link rel="stylesheet" type="text/css" href="stylesheet.css">
<? require('includes/form_check.js.php'); ?>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0">
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
    <td width="100%" valign="top"><? echo tep_draw_form('create_account', tep_href_link(FILENAME_CREATE_ACCOUNT_AVEC, 'password_given=yes', 'SSL'), 'post', 'onSubmit="return check_form(create_account);"') . tep_draw_hidden_field('action', 'process'); ?><table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><? echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><? echo tep_image(DIR_WS_IMAGES . 'table_background_account.gif', HEADING_TITLE, HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><? echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td class="smallText"><br><? echo sprintf(TEXT_ORIGIN_LOGIN, tep_href_link(FILENAME_LOGIN, tep_get_all_get_params(), 'SSL')); ?></td>
      </tr>
      <tr>
        <td><? echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
<?
  if ($messageStack->size('create_account') > 0) {
?>
      <tr>
        <td><? echo $messageStack->output('create_account'); ?></td>
      </tr>
      <tr>
        <td><? echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
<?
  }
if ($password_access_ok) {
      echo tep_draw_hidden_field('password_access', $password_access_valid);
?>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><b><? echo CATEGORY_PERSONAL; ?></b></td>
           <td class="inputRequirement" align="right"><? echo FORM_REQUIRED_INFORMATION; ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" cellspacing="2" cellpadding="2">
<?
  if (ACCOUNT_GENDER == 'true') {
?>
              <tr>
                <td class="main"><? echo ENTRY_GENDER; ?></td>
                <td class="main"><? echo tep_draw_radio_field('gender', 'm') . '&nbsp;&nbsp;' . MALE . '&nbsp;&nbsp;' . tep_draw_radio_field('gender', 'f') . '&nbsp;&nbsp;' . FEMALE . '&nbsp;' . (tep_not_null(ENTRY_GENDER_TEXT) ? '<span class="inputRequirement">' . ENTRY_GENDER_TEXT . '</span>': ''); ?></td>
              </tr>
<?
  }
?>
              <tr>
                <td class="main"><? echo ENTRY_FIRST_NAME; ?></td>
                <td class="main"><? echo tep_draw_input_field('firstname') . '&nbsp;' . (tep_not_null(ENTRY_FIRST_NAME_TEXT) ? '<span class="inputRequirement">' . ENTRY_FIRST_NAME_TEXT . '</span>': ''); ?></td>
              </tr>
              <tr>
                <td class="main"><? echo ENTRY_LAST_NAME; ?></td>
                <td class="main"><? echo tep_draw_input_field('lastname') . '&nbsp;' . (tep_not_null(ENTRY_LAST_NAME_TEXT) ? '<span class="inputRequirement">' . ENTRY_LAST_NAME_TEXT . '</span>': ''); ?></td>
              </tr>
<?
  if (ACCOUNT_DOB == 'true') {
?>
              <tr>
                <td class="main"><? echo ENTRY_DATE_OF_BIRTH; ?></td>
                <td class="main"><? echo tep_draw_input_field('dob') . '&nbsp;' . (tep_not_null(ENTRY_DATE_OF_BIRTH_TEXT) ? '<span class="inputRequirement">' . ENTRY_DATE_OF_BIRTH_TEXT . '</span>': ''); ?></td>
              </tr>
<?
  }
?>
              <tr>
                <td class="main"><? echo ENTRY_EMAIL_ADDRESS; ?></td>
                <td class="main"><? echo tep_draw_input_field('email_address') . '&nbsp;' . (tep_not_null(ENTRY_EMAIL_ADDRESS_TEXT) ? '<span class="inputRequirement">' . ENTRY_EMAIL_ADDRESS_TEXT . '</span>': ''); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
<?
  if (ACCOUNT_COMPANY == 'true') {
?>
      <tr>
        <td><? echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td class="main"><b><? echo CATEGORY_COMPANY; ?></b></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" cellspacing="2" cellpadding="2">
              <tr>
                <td class="main"><? echo ENTRY_COMPANY; ?></td>
                <td class="main"><? echo tep_draw_input_field('company') . '&nbsp;' . (tep_not_null(ENTRY_COMPANY_TEXT) ? '<span class="inputRequirement">' . ENTRY_COMPANY_TEXT . '</span>': ''); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
<?
  }
?>
      <tr>
        <td><? echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td class="main"><b><? echo CATEGORY_ADDRESS; ?></b></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" cellspacing="2" cellpadding="2">
              <tr>
                <td class="main"><? echo ENTRY_STREET_ADDRESS; ?></td>
                <td class="main"><? echo tep_draw_input_field('street_address') . '&nbsp;' . (tep_not_null(ENTRY_STREET_ADDRESS_TEXT) ? '<span class="inputRequirement">' . ENTRY_STREET_ADDRESS_TEXT . '</span>': ''); ?></td>
              </tr>
<?
  if (ACCOUNT_SUBURB == 'true') {
?>
              <tr>
                <td class="main"><? echo ENTRY_SUBURB; ?></td>
                <td class="main"><? echo tep_draw_input_field('suburb') . '&nbsp;' . (tep_not_null(ENTRY_SUBURB_TEXT) ? '<span class="inputRequirement">' . ENTRY_SUBURB_TEXT . '</span>': ''); ?></td>
              </tr>
<?
  }
?>
              <tr>
                <td class="main"><? echo ENTRY_POST_CODE; ?></td>
                <td class="main"><? echo tep_draw_input_field('postcode') . '&nbsp;' . (tep_not_null(ENTRY_POST_CODE_TEXT) ? '<span class="inputRequirement">' . ENTRY_POST_CODE_TEXT . '</span>': ''); ?></td>
              </tr>
              <tr>
                <td class="main"><? echo ENTRY_CITY; ?></td>
                <td class="main"><? echo tep_draw_input_field('city') . '&nbsp;' . (tep_not_null(ENTRY_CITY_TEXT) ? '<span class="inputRequirement">' . ENTRY_CITY_TEXT . '</span>': ''); ?></td>
              </tr>
<?
  if (ACCOUNT_STATE == 'true') {
?>
              <tr>
                <td class="main"><? echo ENTRY_STATE; ?></td>
                <td class="main">
<?
    if ($process == true) {
      if ($entry_state_has_zones == true) {
        $zones_array = array();
        $zones_query = tep_db_query("select zone_name from " . TABLE_ZONES . " where zone_country_id = '" . (int)$country . "' order by zone_name");
        while ($zones_values = tep_db_fetch_array($zones_query)) {
          $zones_array[] = array('id' => $zones_values['zone_name'], 'text' => $zones_values['zone_name']);
        }
        echo tep_draw_pull_down_menu('state', $zones_array);
      } else {
        echo tep_draw_input_field('state');
      }
    } else {
      echo tep_draw_input_field('state');
    }

    if (tep_not_null(ENTRY_STATE_TEXT)) echo '&nbsp;<span class="inputRequirement">' . ENTRY_STATE_TEXT;
?>
                </td>
              </tr>
<?
  }
?>
              <tr>
                <td class="main"><? echo ENTRY_COUNTRY; ?></td>
                <td class="main"><? echo tep_get_country_list('country', STORE_COUNTRY) . '&nbsp;' . (tep_not_null(ENTRY_COUNTRY_TEXT) ? '<span class="inputRequirement">' . ENTRY_COUNTRY_TEXT . '</span>': ''); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><? echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td class="main"><b><? echo CATEGORY_CONTACT; ?></b></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" cellspacing="2" cellpadding="2">
              <tr>
                <td class="main"><? echo ENTRY_TELEPHONE_NUMBER; ?></td>
                <td class="main"><? echo tep_draw_input_field('telephone') . '&nbsp;' . (tep_not_null(ENTRY_TELEPHONE_NUMBER_TEXT) ? '<span class="inputRequirement">' . ENTRY_TELEPHONE_NUMBER_TEXT . '</span>': ''); ?></td>
              </tr>
              <tr>
                <td class="main"><? echo ENTRY_FAX_NUMBER; ?></td>
                <td class="main"><? echo tep_draw_input_field('fax') . '&nbsp;' . (tep_not_null(ENTRY_FAX_NUMBER_TEXT) ? '<span class="inputRequirement">' . ENTRY_FAX_NUMBER_TEXT . '</span>': ''); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><? echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td class="main"><b><? echo CATEGORY_OPTIONS; ?></b></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" cellspacing="2" cellpadding="2">
              <tr>
                <td class="main"><? echo ENTRY_NEWSLETTER; ?></td>
                <td class="main"><? echo tep_draw_checkbox_field('newsletter', '1') . '&nbsp;' . (tep_not_null(ENTRY_NEWSLETTER_TEXT) ? '<span class="inputRequirement">' . ENTRY_NEWSLETTER_TEXT . '</span>': ''); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><? echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td class="main"><b><? echo CATEGORY_PASSWORD; ?></b></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" cellspacing="2" cellpadding="2">
              <tr>
                <td class="main"><? echo ENTRY_PASSWORD; ?></td>
                <td class="main"><? echo tep_draw_password_field('password') . '&nbsp;' . (tep_not_null(ENTRY_PASSWORD_TEXT) ? '<span class="inputRequirement">' . ENTRY_PASSWORD_TEXT . '</span>': ''); ?></td>
              </tr>
              <tr>
                <td class="main"><? echo ENTRY_PASSWORD_CONFIRMATION; ?></td>
                <td class="main"><? echo tep_draw_password_field('confirmation') . '&nbsp;' . (tep_not_null(ENTRY_PASSWORD_CONFIRMATION_TEXT) ? '<span class="inputRequirement">' . ENTRY_PASSWORD_CONFIRMATION_TEXT . '</span>': ''); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><? echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td width="10"><? echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td><? echo tep_image_submit('button_continue.gif', IMAGE_BUTTON_CONTINUE); ?></td>
                <td width="10"><? echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
<?} // end of "if ($password_access_ok)" 
else {?>
      <tr>
        <td align="left"><? echo '<a href="' . tep_href_link(FILENAME_CREATE_ACCOUNT, '', 'SSL') . '">' . tep_image_button('button_continue.gif', IMAGE_BUTTON_CONTINUE) . '</a>'; ?></td>
        <td width="10"><? echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
      </tr><?
}
?>    
    </table></form></td>
    
    
    
<!-- body_text_eof //-->
    <td width="<? echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<? echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">
<!-- right_navigation //-->
<? include(DIR_WS_INCLUDES . 'column_right.php'); ?>
<!-- right_navigation_eof //-->
    </table></td>
  </tr>
</table>
<!-- body_eof //-->

<!-- footer //-->
<? include(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<br>
</body>
</html>
<? require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
