<?php
 if (session_status() == PHP_SESSION_NONE) session_start();
 require_once('./settings.php');
 require_once('./libvirt.php');
 if ($_SESSION['admin-login']) {
  $lv = new Libvirt();
  if (@$lv->connect($GLOBALS['address'])) {
   $domName = @$lv->domain_get_name_by_uuid($_POST['id']);
   if ($domName) {
    if (@$lv->domain_change_xml($domName, $_POST['xml'])) echo json_encode(array('error' => 0, 'message' => 'OK'));
    else echo json_encode(array('error' => 4, 'message' => 'This XML is not valid.'));
   } else echo json_encode(array('error' => 3, 'message' => 'Wrong UUID.'));
  } else echo json_encode(array('error' => 2, 'message' => 'Cannot connect to libvirt service.'));
 } else echo json_encode(array('error' => 1, 'message' => 'Admin not logged in.'));
?>