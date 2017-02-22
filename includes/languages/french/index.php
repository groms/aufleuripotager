<?php
/*
  $Id: index.php,v 1.1 2003/06/11 17:38:00 hpdl Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 osCommerce

  Released under the GNU General Public License
  Translated by Gunt - Contact : webmaster@webdesigner.com.fr
*/

/*
define('TEXT_MAIN', 'C\'est une installation par d&eacute;faut du projet osCommerce, les produits affich&eacute;s ont un but d&eacute;monstratif, <b>n\'importe quels produits achet&eacute;s seront ni livr&eacute;s ni factur&eacute; à l\'adhérent</b>. N\'importe quelle information vue sur ces produits doit &ecirc;tre trait&eacute;e comme fictive.<br><br><table border="0" width="100%" cellspacing="5" cellpadding="2"><tr><td class="main" valign="top">' . tep_image(DIR_WS_IMAGES . 'default/1.gif') . '</td><td class="main" valign="top"><b>Messages d\'erreur </b><br><br>S\'il y a une erreur ou des messages d\'avertissement affich&eacute;s ci-dessus, corrigez-les d\'abord avant de poursuivre. <br><br>Les messages d\'erreur sont affich&eacute;s en haut de la page avec un <span class="messageStackError">fond</span> de cette couleur.<br><br>Plusieurs contr&ocirc;les sont ex&eacute;cut&eacute;s pour assurer une installation saine de votre magasin en ligne - Ces contr&ocirc;les peuvent &ecirc;tre mis hors-service en &eacute;ditant les param&egrave;tres appropri&eacute;s dans le fichier includes/application_top.php file. </td></tr><td class="main" valign="top">' . tep_image(DIR_WS_IMAGES . 'default/2.gif') . '</td><td class="main" valign="top"><b>R&eacute;daction du contenu de la page </b><br><br>Le texte affich&eacute; peut &ecirc;tre modifi&eacute; dans le fichier suivant, sur chaque base de langue :<br><br><nobr class="messageStackSuccess">[chemin du r&eacute;pertoire catalog]/includes/languages/' . $language . '/' . FILENAME_DEFAULT . '</nobr><br><br>Ce fichier peut &ecirc;tre &eacute;dit&eacute; manuellement, ou via l\'outil d\'administration avec les modules  <nobr class="messageStackSuccess">Languages->' . ucfirst($language) . '->Define</nobr> ou <nobr class="messageStackSuccess">Tools->File Manager</nobr>.<br><br>Le texte est affich&eacute; de la façon suivante:<br><br><nobr>define(\'TEXT_MAIN\', \'<span class="messageStackSuccess">C\'est une installation par d&eacute;faut du projet osCommerce...</span>\');</nobr><br><br>Le texte mis en &eacute;vidence en vert peut &ecirc;tre modifi&eacute;  - Il est important dans define() de d&eacute;finir le mot-cl&eacute; TEXT_MAIN . Pour supprimer compl&egrave;tement le texte de TEXT_MAIN, L\'exemple suivant a employ&eacute; seulement deux caract&egrave;res de citation simples (Quotes) existant :<br><br><nobr>define(\'TEXT_MAIN\', \'\');</nobr><br><br>Plus d\'information concernant la fonction  PHP define() peut &ecirc;tre lue <a href="http://www.php.net/define" target="_blank"><u>ici</u></a>.</td></tr><tr><td class="main" valign="top">' . tep_image(DIR_WS_IMAGES . 'default/3.gif') . '</td><td class="main" valign="top"><b>S&eacute;curisation de l\'outil d\'administration</b><br><br>Il est important de s&eacute;curiser l\'outil d\'administration comme il n\'y a actuellement aucune mise en oeuvre de s&eacute;curisation disponible .</td></tr><tr><td class="main" valign="top">' . tep_image(DIR_WS_IMAGES . 'default/4.gif') . '</td><td class="main" valign="top"><b>Documentation en ligne</b><br><br>La documentation en ligne peut être lue sur le site <a href="http://wiki.oscommerce.com" target="_blank"><u>osCommerce Wiki Documentation Effort</u></a>.<br><br>Le forum support de la communaut&eacute; est disponible sur <a href="http://forums.oscommerce.com" target="_blank"><u>osCommerce Community Support Forums</u></a>.</td></tr></table><br>Si vous voulez t&eacute;l&eacute;charger la solution qu\'utilise ce magasin, ou si vous voulez contribuer au projet osCommerce, visitez s\'il vous pla&icirc;t le site <a href="http://www.oscommerce.com" target="_blank"><u>osCommerce</u></a>. Ce magasin utilise osCommerce version <font color="#f0000"><b>' . PROJECT_VERSION . '</b></font>.');
define('TABLE_HEADING_NEW_PRODUCTS', 'Nouveaux produits pour %s');
define('TABLE_HEADING_UPCOMING_PRODUCTS', 'Prochains produits');
define('TABLE_HEADING_DATE_EXPECTED', 'Date pr&eacute;vu');
*/
define('TEXT_MAIN', '');
define('TABLE_HEADING_NEW_PRODUCTS', '');
define('TABLE_HEADING_UPCOMING_PRODUCTS', '');
define('TABLE_HEADING_DATE_EXPECTED', '');


if ( ($category_depth == 'products') || (isset($HTTP_GET_VARS['manufacturers_id'])) ) {
  define('HEADING_TITLE', 'Voyons ce que nous avons ici');
  define('TABLE_HEADING_IMAGE', '');
  define('TABLE_HEADING_MODEL', 'Mod&egrave;le');
  define('TABLE_HEADING_PRODUCTS', 'Nom du produit ');
  define('TABLE_HEADING_MANUFACTURER', 'Producteur');
  define('TABLE_HEADING_QUANTITY', 'Quantit&eacute;');
  define('TABLE_HEADING_PRICE', 'Prix');
  define('TABLE_HEADING_WEIGHT', 'Poids');
  define('TABLE_HEADING_BUY_NOW', 'Acheter maintenant');
  define('TEXT_NO_PRODUCTS', 'Il n\'y a aucun produit list&eacute; dans cette cat&eacute;gorie.');
  define('TEXT_NO_PRODUCTS2', 'Il n\'y a aucun produit disponible de ce producteur.');
  define('TEXT_NUMBER_OF_PRODUCTS', 'Nombre de produits:');
  define('TEXT_SHOW', '<b>Afficher :</b>');
  define('TEXT_BUY', 'Acheter 1 \'');
  define('TEXT_NOW', '\' maintenant');
  define('TEXT_ALL_CATEGORIES', 'Toutes cat&eacute;gories');
  define('TEXT_ALL_MANUFACTURERS', 'Tous producteurs');
} elseif ($category_depth == 'top') {
  define('HEADING_TITLE', 'Nouveaut&eacute;s ?');
} elseif ($category_depth == 'nested') {
  define('HEADING_TITLE', 'Cat&eacute;gories');
}
?>
