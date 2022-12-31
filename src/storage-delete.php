<?php
 if (session_status() == PHP_SESSION_NONE) session_start();
 require_once('./settings.php');
 require_once('./libvirt.php');
 if ($_SESSION['admin-login']) {
  $lv = new Libvirt();
  if (@$lv->connect($GLOBALS['address'])) {
   if (@$lv->storagepool_undefine($_GET['pool'])) echo json_encode(array('error' => 0, 'message' => 'OK'));
   else echo json_encode(array('error' => 3, 'message' => 'Cannot delete this pool.'));
  } else echo json_encode(array('error' => 2, 'message' => 'Cannot connect to libvirt service.'));
 } else echo json_encode(array('error' => 1, 'message' => 'Admin not logged in.'));
?>