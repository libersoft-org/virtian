<?php
 if (session_status() == PHP_SESSION_NONE) session_start();
 require_once('./settings.php');
 require_once('./libvirt.php');
 if ($_SESSION['admin-login']) {
  $lv = new Libvirt();
  if (@$lv->connect($GLOBALS['address'])) {
   if (@$lv->storagevolume_delete($_GET['pool'], $_GET['volume'])) echo json_encode(array('error' => 0, 'message' => 'OK'));
   else echo json_encode(array('error' => 3, 'message' => 'Cannot delete this volume.'));
  } else echo json_encode(array('error' => 2, 'message' => 'Cannot connect to libvirt service.'));
 } else echo json_encode(array('error' => 1, 'message' => 'Admin not logged in.'));
?>