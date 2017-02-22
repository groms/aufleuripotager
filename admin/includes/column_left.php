<?php
/*
  $Id: column_left.php,v 1.15 2002/01/11 05:03:25 hpdl Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2002 osCommerce

  Released under the GNU General Public License
*/

  if ((empty($m_id_filter))||($m_id_filter<=0)) {
    require($admin_FS_path . DIR_WS_BOXES . 'catalog.php');
    require($admin_FS_path . DIR_WS_BOXES . 'customers.php');
    require($admin_FS_path . DIR_WS_BOXES . 'reports.php');
  //  require($admin_FS_path . DIR_WS_BOXES . 'modules.php');
  //  require($admin_FS_path . DIR_WS_BOXES . 'taxes.php');
  //  require($admin_FS_path . DIR_WS_BOXES . 'localization.php');
    require($admin_FS_path . DIR_WS_BOXES . 'configuration.php');
    require($admin_FS_path . DIR_WS_BOXES . 'tools.php');
  }

?>
