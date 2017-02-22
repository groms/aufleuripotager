<?php
/*
  $Id: orders.php,v 1.25 2003/06/20 00:28:44 hpdl Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2002 osCommerce

  Released under the GNU General Public License
  Translated by Gunt - Contact : webmaster@webdesigner.com.fr
*/

define('HEADING_TITLE', 'Commandes');
define('HEADING_TITLE_SEARCH', 'ID commande:');
define('HEADING_TITLE_STATUS', 'Etat:');

define('TABLE_HEADING_COMMENTS', 'Commentaires');
define('TABLE_HEADING_CUSTOMERS', 'Adhérents');
define('TABLE_HEADING_ORDER_TOTAL', 'Total commande');
define('TABLE_HEADING_DATE_PURCHASED', 'Date d\'achat');
define('TABLE_HEADING_STATUS', 'Etat');
define('TABLE_HEADING_ACTION', 'Action');
define('TABLE_HEADING_QUANTITY', 'Qt&eacute;.');
define('TABLE_HEADING_PRODUCTS_MODEL', 'Mod&egrave;le');
define('TABLE_HEADING_PRODUCTS', 'Produits');
define('TABLE_HEADING_TAX', 'Taxe');
define('TABLE_HEADING_TOTAL', 'Total');
define('TABLE_HEADING_PRICE_EXCLUDING_TAX', 'Prix (ht)');
define('TABLE_HEADING_PRICE_INCLUDING_TAX', 'Prix (ttc)');
define('TABLE_HEADING_TOTAL_EXCLUDING_TAX', 'Total (ht)');
define('TABLE_HEADING_TOTAL_INCLUDING_TAX', 'Total (ttc)');

define('TABLE_HEADING_CUSTOMER_NOTIFIED', 'Adhérent notifi&eacute;');
define('TABLE_HEADING_DATE_ADDED', 'Date d\'ajout');

define('ENTRY_CUSTOMER', 'Adhérent :');
define('ENTRY_SOLD_TO', 'VENDU A:');
define('ENTRY_DELIVERY_TO', 'Livraison &agrave;:');
define('ENTRY_SHIP_TO', 'LIVRE A:');
define('ENTRY_SHIPPING_ADDRESS', 'Adresse d\'exp&eacute;dition:');
define('ENTRY_BILLING_ADDRESS', 'Adresse facturation:');
define('ENTRY_PAYMENT_METHOD', 'M&eacute;thode de paiement:');
define('ENTRY_CREDIT_CARD_TYPE', 'Type de carte de cr&eacute;dit:');
define('ENTRY_CREDIT_CARD_OWNER', 'Propri&eacute;taire de la carte de cr&eacute;dit:');
define('ENTRY_CREDIT_CARD_NUMBER', 'Num&eacute;ro de la carte de cr&eacute;dit:');
define('ENTRY_CREDIT_CARD_EXPIRES', 'Date d\'expiration de la carte de cr&eacute;dit:');
define('ENTRY_SUB_TOTAL', 'Sous-Total:');
define('ENTRY_TAX', 'Taxe:');
define('ENTRY_SHIPPING', 'Exp&eacute;dition:');
define('ENTRY_TOTAL', 'Total:');
define('ENTRY_DATE_PURCHASED', 'Date d\'achat:');
define('ENTRY_STATUS', 'Etat:');
define('ENTRY_DATE_LAST_UPDATED', 'Derni&egrave;re date de mise &agrave; jour:');
define('ENTRY_NOTIFY_CUSTOMER', 'Informer adhérent :');
define('ENTRY_NOTIFY_COMMENTS', 'Ajouter un commentaire :');
define('ENTRY_PRINTABLE', 'Imprimer la facture');

define('TEXT_INFO_HEADING_DELETE_ORDER', 'Supprimer la commande');
define('TEXT_INFO_DELETE_INTRO', 'Etes vous sur de vouloir supprimer cette commande?');
define('TEXT_INFO_RESTOCK_PRODUCT_QUANTITY', 'Restaurer la valeur de stock');
define('TEXT_DATE_ORDER_CREATED', 'Date de cr&eacute;ation:');
define('TEXT_DATE_ORDER_LAST_MODIFIED', 'Derni&egrave;re modification:');
define('TEXT_INFO_PAYMENT_METHOD', 'M&eacute;thode de paiement:');

define('TEXT_ALL_ORDERS', '-- TOUS --');
define('TEXT_NO_ORDER_HISTORY', 'Aucun historique de commande disponible');

define('EMAIL_SEPARATOR', '------------------------------------------------------');
define('EMAIL_TEXT_SUBJECT', 'Mise &agrave; jour de la commande');
define('EMAIL_TEXT_ORDER_NUMBER', 'Num&eacute;ro de commande:');
define('EMAIL_TEXT_INVOICE_URL', 'Facture d&eacute;taill&eacute;e:');
define('EMAIL_TEXT_DATE_ORDERED', 'Date de commande:');
define('EMAIL_TEXT_STATUS_UPDATE', 'L\'état de votre commande a &eacute;t&eacute; mis &agrave; jour par le suivant.' . "\n\n" . 'Nouvel état: %s' . "\n\n" . 'Merci de r&eacute;pondre &agrave; ce courrier &eacute;lectronique si vous avez des questions.' . "\n");
define('EMAIL_TEXT_COMMENTS_UPDATE', 'Les commentaires de votre commande sontS' . "\n\n%s\n\n");

define('ERROR_ORDER_DOES_NOT_EXIST', 'Erreur: La commande n\'existe pas.');
define('SUCCESS_ORDER_UPDATED', 'Succ&egrave;s: Commande mis &agrave; jour avec succ&egrave;s.');
define('WARNING_ORDER_NOT_UPDATED', 'Attention: Aucune modification n\'a &eacute;t&eacute; effectu&eacute;. La commande n\'a pas &eacute;t&eacute; mis &agrave; jour.');
?>