<?php
 require_once('./settings.php');
 require_once('./functions.php');
 if (session_status() == PHP_SESSION_NONE) session_start();
 if ($_SESSION['admin-login']) {
  $disk_template = file_get_contents($GLOBALS['template-path'] . '/vm-new-add-disk-device.html');
  $disk_array = array(
   '[[template-path]]' => $GLOBALS['template-path'],
   '[[id]]'            => $_GET['id']
  );
  echo html_replace($disk_array, $disk_template);
 }
?>