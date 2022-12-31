<?php
 require_once('./settings.php');
 require_once('./functions.php');
 require_once('./libvirt.php');
 if (session_status() == PHP_SESSION_NONE) session_start();
 if ($_SESSION['admin-login']) {
  $lv = new Libvirt();
  if (@$lv->connect($GLOBALS['address'])) {
   $image_template = file_get_contents($GLOBALS['template-path'] . '/vm-new-add-existing-disk-image.html');
   $images_html = '';
   $images = @$lv->storagepool_get_volume_information(@$lv->get_storagepool_res($_GET['storage']));
   foreach ($images as $k => $v) {
    $images_html .= str_replace('[[image]]', $k, $image_template) . "\r\n";
   }
   $image_array = array(
    '[[image]]' => $images_html
   );
   echo $images_html;
  } else echo 'Cannot connect to libvirt service.';
 }
?>