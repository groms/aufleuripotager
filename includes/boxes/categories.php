<?php
/*
  $Id: categories.php,v 1.25 2003/07/09 01:13:58 hpdl Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 osCommerce

  Released under the GNU General Public License
*/

  if (!clientCanBuyGA()) {
    $vente_directe_only = "cd.group_id = 0 and ";
  }


  function tep_show_category($counter) {
    global $tree, $categories_string, $cPath_array;
    
    $bAdded = false;

    $products_in_category = tep_count_products_in_category($counter);
    $has_subcat = tep_has_category_subcategories($counter);

    if (($tree[$counter]['path'] != -1)&&($products_in_category > 0)) {
      $bAdded = true;
      
      for ($i=0; $i<$tree[$counter]['level']; $i++) {
        $categories_string .= "&nbsp;&nbsp;";
      }

      if ($products_in_category > 0) {
        $categories_string .= '<a href="';

        if ($tree[$counter]['parent'] == 0) {
          $cPath_new = 'cPath=' . $counter;
        } else {
          $cPath_new = 'cPath=' . $tree[$counter]['path'];
        }
    
        $categories_string .= tep_href_link(FILENAME_DEFAULT, $cPath_new) . '">';
        if (isset($cPath_array) && in_array($counter, $cPath_array)) {
          $categories_string .= '<b>';
        }
      }

      $categories_string .= $tree[$counter]['name'];
      if ($has_subcat) {
        $categories_string .= '&nbsp;-&gt;';
      }

      if ($products_in_category > 0) {
        if (isset($cPath_array) && in_array($counter, $cPath_array)) {
          $categories_string .= '</b>';
        }
      }
      if ($products_in_category > 0) {
        $categories_string .= '</a>';
      }
    } else if ($tree[$counter]['path'] == -1) {
      // RVD or GA
      $bAdded = true;
      $categories_string .= '<b><i>';
      $categories_string .= $tree[$counter]['name'];
      $categories_string .= '</i></b>';
    }

    if ($bAdded) {
      if (($products_in_category>0)&&(SHOW_COUNTS == 'true')&&($tree[$counter]['path'] != -1)) {    // 'path' == -1  =>  RVD or GA node
        $categories_string .= '&nbsp;(' . $products_in_category . ')';
      }
      $categories_string .= '<br>';
    }

    if ($tree[$counter]['next_id'] != false) {
      tep_show_category($tree[$counter]['next_id']);
    }
  }
?>
<!-- categories //-->
          <tr>
            <td>
<?php
  $info_box_contents = array();
  $info_box_contents[] = array('text' => BOX_HEADING_CATEGORIES);

  new infoBoxHeading($info_box_contents, true, false);

  $categories_string = '';
  $tree = array();
  
                                           
  $top_id = 900;
  $first_element = $top_id;
  $tree[$top_id] = array( 
    'name' => "<a href='".tep_href_link("message.php", "msgtype=rvd_ga_def")."'>RVD : vente directe</a>",
    'parent' => 0,
    'level' => 0,
    'path' => -1,
    'next_id' => 0);
  if (!$vente_directe_only) {
    $tree[$top_id+1] = array( 
      'name' => "<br><a href='".tep_href_link("message.php", "msgtype=rvd_ga_def")."'>GA : groupt d'achat</a>",
      'parent' => 0,
      'level' => 0,
      'path' => -1,
      'next_id' => 0);
  }
    
  $categories_query = tep_db_query("select c.categories_id, cd.group_id, cd.categories_name, c.parent_id from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where " . $vente_directe_only . "c.parent_id = '0' and c.categories_id = cd.categories_id and cd.language_id='" . (int)$languages_id ."' order by cd.group_id, sort_order, cd.categories_name");
  while ($categories = tep_db_fetch_array($categories_query))  {
    if ($categories['group_id'] != $gid) {
      $gid = $categories['group_id'];      
      $gid_changed = true;    
      $p_id = $top_id + $gid;
      $tree[$parent_id]['next_id'] = $p_id;
      $tree[$p_id]['next_id'] = $categories['categories_id'];
    } else {
      $p_id = $categories['parent_id'];  
      if (isset($parent_id)) {
        $tree[$parent_id]['next_id'] = $categories['categories_id'];
      }
    }

    $tree[$categories['categories_id']] = array('name' => $categories['categories_name'],
                                                'parent' => $p_id,
                                                'level' => 1,
                                                'path' => $categories['categories_id'],
                                                'next_id' => false);

    $parent_id = $categories['categories_id'];

    $gid_changed = false;
  }

  //------------------------
  if (tep_not_null($cPath)) {
    $new_path = '';
    reset($cPath_array);
    while (list($key, $value) = each($cPath_array)) {
      $key+=1;
      unset($parent_id);
      unset($first_id);
      $categories_query = tep_db_query("select c.categories_id, cd.group_id, cd.categories_name, c.parent_id from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where " . $vente_directe_only . "c.parent_id = '" . (int)$value . "' and c.categories_id = cd.categories_id and cd.language_id='" . (int)$languages_id ."' order by sort_order, cd.categories_name");
      if (tep_db_num_rows($categories_query)) {
        $new_path .= $value;
        while ($row = tep_db_fetch_array($categories_query)) {
          $c_name = $row['categories_name'];

          $tree[$row['categories_id']] = array('name' => $c_name,
                                               'parent' => $row['parent_id'],
                                               'level' => $key+1,
                                               'path' => $new_path . '_' . $row['categories_id'],
                                               'next_id' => false);

          if (isset($parent_id)) {
            $tree[$parent_id]['next_id'] = $row['categories_id'];
          }

          $parent_id = $row['categories_id'];

          if (!isset($first_id)) {
            $first_id = $row['categories_id'];
          }

          $last_id = $row['categories_id'];
        }
        $tree[$last_id]['next_id'] = $tree[$value]['next_id'];
        $tree[$value]['next_id'] = $first_id;
        $new_path .= '_';
      } else {
        break;
      }
    }
  }
  
  tep_show_category($first_element); 

  $info_box_contents = array();
  $info_box_contents[] = array('text' => $categories_string);

  new infoBox($info_box_contents, 'nowrap');
?>
            </td>
          </tr>
<!-- categories_eof //-->
