<?php

$host = str_replace("//", "/", trim(getenv("HTTP_HOST"))."/"); // ie : localhost/
$host_url = "http://" . $host; // ie : http://localhost/
$doc_root = str_replace("//", "/", trim(getenv("DOCUMENT_ROOT"))."/"); // ie : /home/www/site/
$preprod = false; 
if ($host == "aufleuripotager.free.fr/") {
  $subpath = "";
}
else {
  $subpath = "aufleuripotager/";
  $preprod = true; 
}
$admin_subpath = "admin/";
$admin_FS_path = $doc_root . $subpath . $admin_subpath;
$admin_url_path = $host_url . $subpath . $admin_subpath;

?>
