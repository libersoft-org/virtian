<?php
 require_once('./settings.php');
 require_once('./functions.php');
 require_once('./libvirt.php');
 if (session_status() == PHP_SESSION_NONE) session_start();
 if ($_SESSION['admin-login']) {
  $lv = new Libvirt();
  if (@$lv->connect($GLOBALS['address'])) {
   $disk_template = file_get_contents($GLOBALS['template-path'] . '/vm-new-add-existing-disk.html');
   $storage_template = file_get_contents($GLOBALS['template-path'] . '/vm-new-add-existing-disk-row.html');
   $storages_html = '';
   $storages = @$lv->get_storagepools();
   foreach ($storages as $s) {
    $storages_html .= str_replace('[[storage]]', $s, $storage_template) . "\r\n";
   }
   $disk_array = array(
    '[[template-path]]' => $GLOBALS['template-path'],
    '[[id]]'            => $_GET['id'],
    '[[storage]]'       => $storages_html
   );
   echo html_replace($disk_array, $disk_template);
  } else echo 'Cannot connect to libvirt service.';
 }
?>