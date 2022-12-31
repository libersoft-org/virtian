<?php
 error_reporting(E_ERROR | E_PARSE);
 if (session_status() == PHP_SESSION_NONE) session_start();
 require_once('./settings.php');
 require_once('./libvirt.php');
 if ($_SESSION['admin-login']) {
  $lv = new Libvirt();
  if (@$lv->connect($GLOBALS['address'])) {
   $domName = @$lv->domain_get_name_by_uuid($_GET['id']);
   if ($domName) {
    switch ($_GET['action']) {
     case 'start':
      @$lv->domain_start($domName);
      break;
     case 'shutdown':
      @$lv->domain_shutdown($domName);
      break;
     case 'force-shutdown':
      @$lv->domain_destroy($domName);
      break;
     case 'restart':
      @$lv->domain_reboot($domName);
      break;
     case 'force-restart':
      @$lv->domain_destroy($domName);
      @$lv->domain_start($domName);
      break;
     case 'suspend':
      @$lv->domain_suspend($domName);
      break;
     case 'resume':
      @$lv->domain_resume($domName);
      break;
     case 'delete':
      @$lv->domain_undefine($domName);
      break;
    }
    echo json_encode(array('error' => 0, 'message' => 'OK'));
   } else echo json_encode(array('error' => 3, 'message' => 'Wrong UUID.'));
  } else echo json_encode(array('error' => 2, 'message' => 'Cannot connect to libvirt service.'));
 } else echo json_encode(array('error' => 1, 'message' => 'Admin not logged in.'));
?>