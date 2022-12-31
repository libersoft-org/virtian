<?php
 if (session_status() == PHP_SESSION_NONE) session_start();
 require_once('./settings.php');
 require_once('./functions.php');
 require_once('./libvirt.php');
 if ($_SESSION['admin-login']) {
  $lv = new Libvirt();
  if (@$lv->connect($GLOBALS['address'])) {
   $image_path = @$lv->get_storagepool_info($_POST['pool'])['path'] . '/' . $_POST['name'] . '.img';
   if (!file_exists($image_path)) {
    if(@$lv->storagevolume_create($_POST['pool'], $_POST['name'] . '.img', getImageFormat($_POST['type']), $_POST['size'], getSizeUnit($_POST['size_unit']), $_POST['size'], getSizeUnit($_POST['size_unit']))) echo json_encode(array('error' => 0, 'message' => 'OK'));
    else echo json_encode(array('error' => 4, 'message' => 'Cannot create this storage volume.'));
   } else echo json_encode(array('error' => 3, 'message' => 'Image with this name already exists. Please try another name.'));
  } else echo json_encode(array('error' => 2, 'message' => 'Cannot connect to libvirt service.'));
 } else echo json_encode(array('error' => 1, 'message' => 'Admin not logged in.'));
?>