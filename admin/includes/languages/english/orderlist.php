<?php
/*
  $Id: orderlist.php, v5.1 2007/02/06 15:52:31 insomniac2 Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  New Improved OrderList by insomniac2 www.terracomp.ca

  Copyright (c) 2002 osCommerce

  Released under the GNU General Public License
*/
define('BROWSER_TITLE', 'Order Listing');

define('HEADING_TITLE', '<font color="0000FF">Order Listing</font>');

define('MONTH_JANUARY', 'January');
define('MONTH_FEBRUARY', 'February');
define('MONTH_MARCH', 'March');
define('MONTH_APRIL', 'April');
define('MONTH_MAY', 'May');
define('MONTH_JUNE', 'June');
define('MONTH_JULY', 'July');
define('MONTH_AUGUST', 'August');
define('MONTH_SEPTEMBER', 'September');
define('MONTH_OCTOBER', 'October');
define('MONTH_NOVEMBER', 'November');
define('MONTH_DECEMBER', 'December');

define('TEXT_TO', '<b>To :</b>');
define('TEXT_FROM', '<b>From :</b>');

define('TEXT_DATE_RANGE_FILTER', '<font color="#0000FF">Date Range Filter</font>');
define('HEADING_TITLE_SEARCH_FILTER', '<font color="0000FF">Search Filter:</font>');
define('HEADING_TITLE_CUSTOMER_SELECT', '<font color="0000FF">Select:</font>');
define('HEADING_TITLE_DAYS', '<font color="0000FF"><b>Past Days:</font></b>');
define('HEADING_TITLE_STATUS', '<font color="0000FF"><b>Status:</font></b>');

define('TABLE_HEADING_INVOICE_NUMBER', 'Invoice #');
define('TABLE_HEADING_DATE', 'Date / Time');
define('TABLE_HEADING_ORDER_STATUS', 'Status');
define('TABLE_HEADING_CLIENT_DETAILS', 'Client Details');
define('TABLE_HEADING_ORDERS_DETAILS', 'Orders Details');

define('IMAGE_BUTTON_PRINTABLE', 'Print This Order List');
define('ENTRY_PRINTABLE', 'Printable:');

define('TEXT_ORDERS_TODAY', '<font color="0000FF">Orders For Today</font>');
define('TEXT_ORDERS_FOR_PAST', '<font color="0000FF"><b>Orders Within Past');
define('TEXT_DAYS', 'Days</font</b>');

define('TEXT_ORDERS_WITH_STATUS', '<font color="0000FF"><b>Orders with Status : ');

define('TEXT_SELECT_STATUS', ' > Select Status <');
define('TEXT_SELECT_CLIENT', ' > Select A Client < ');

define('OL_ACCT', 'Account ID:');
define('OL_COMPANY', 'Company:');

define('OL_LOCATION', 'Location:');

define('OL_CUSTOMERNAME', 'Customer Name:');
define('OL_ADDRESS', 'Default Address:');
define('OL_ADDRESS2', ' ');
define('OL_SUBURB', 'Suburb:');
define('OL_CITY', 'City:');
define('OL_POSTCODE', 'ZIP / Post Code:');
define('OL_STATE', 'State:');
define('OL_COUNTRY', 'Country:');
define('OL_TEL', 'Telephone:');

define('OL_BILLING_NAME', 'Billing Name:');
define('OL_BILLING_ADDRESS', 'Billing Address:');
define('OL_BILLING_ADDRESS2', ' ');
define('OL_BILLING_SUBURB', 'Suburb:');
define('OL_BILLING_CITY', 'City:');
define('OL_BILLING_POSTCODE', 'ZIP / Post Code:');
define('OL_BILLING_STATE', 'State:');
define('OL_BILLING_COUNTRY', 'Country:');
define('OL_BILLING_TEL', 'Telephone:');

define('OL_DELIVERY_NAME', 'Delivery Name:');
define('OL_DELIVERY_ADDRESS', 'Delivery Address:');
define('OL_DELIVERY_ADDRESS2', ' ');
define('OL_DELIVERY_SUBURB', 'Suburb:');
define('OL_DELIVERY_CITY', 'City:');
define('OL_DELIVERY_POSTCODE', 'ZIP / Post Code:');
define('OL_DELIVERY_STATE', 'State:');
define('OL_DELIVERY_COUNTRY', 'Country:');
define('OL_DELIVERY_TEL', 'Telephone:');

define('OL_FAX', 'FAX:');
define('OL_EMAIL', 'E-Mail:');

define('TEXT_MANUFACTURER', 'Manufacturer:');
define('TEXT_MODEL', 'Model / Part #');
define('TEXT_PRODUCT_UPC_SKU', 'UPC / SKU:');
define('TEXT_PRODUCT_SERIAL_NUMBER', 'Serial Number:');
define('TEXT_PRODUCT_SOFTWARE_KEY', 'Software Key:');

define('ENTRY_PAYMENT_METHOD', 'Payment Method : ');
define('ERROR_NO_METHOD_SET', '<font color="FF0000">No Payment Method Set</font>');

define('ENTRY_CREDIT_CARD_TYPE', 'Credit Card Type:');
define('ENTRY_CREDIT_CARD_OWNER', 'Credit Card Owner:');
define('ENTRY_CREDIT_CARD_NUMBER', 'Credit Card Number:');
define('ENTRY_CREDIT_CARD_EXPIRES', 'Credit Card Expires:');
define('ENTRY_CREDIT_CARD_START', 'Credit Card Start Date:');
define('ENTRY_CREDIT_CARD_ISSUE_NUMBER', 'Credit Card Issue Number:');
define('ENTRY_CREDIT_CARD_CVV_NUMBER', 'Credit Card CVV Number:');

define('ENTRY_PURCHASE_ORDER_NUMBER', 'Purchase Order Number:');
define('ENTRY_PURCHASE_REQUESTED_BY', 'Requested by:');
define('ENTRY_PURCHASE_CONTACT_PERSON', 'Authorative Contact:');

define('TEXT_TOTAL_ITEMS', '<b>Total Items:</b>');

define('TEXT_RECORD_FOUND', 'record found');
define('TEXT_RECORDS_FOUND', 'records found');
define('TEXT_NO_RECORDS', '<font color="FF0000">No Available Records</font>');

define('TEXT_DISPLAY_NUMBER_OF_RECORDS', 'Displaying <b>%d</b> to <b>%d</b> (of <b>%d</b> order records)');

define('TEXT_MANUFACTURER_UNAVAILABLE', '<font color="FF0000"><b>NOT IN STOCK!!:</b></font>');
define('TEXT_MANUFACTURER_AVAILABLE', '<font color="FFFF00"><b>IN STOCK:</b></font>');
?>