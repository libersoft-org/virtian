<?php
 require_once('./settings.php');
 require_once('./functions.php');
 require_once('./libvirt.php');
 if (session_status() == PHP_SESSION_NONE) session_start();
 if ($_SESSION['admin-login']) {
  $lv = new Libvirt();
  if (@$lv->connect($GLOBALS['address'])) {
   $xml_template = file_get_contents('xml/storage.xml');
   $xml_array = array(
    '{{name}}' => $_POST['name'],
    '{{path}}' => $_POST['path']
   );
   $xml = html_replace($xml_array, $xml_template);
   if (@$res = $lv->storagepool_define_xml($xml)) {
    if (@$lv->storagepool_create($res)) echo json_encode(array('error' => 0, 'message' => 'OK'));
    else echo json_encode(array('error' => 4, 'message' => 'The storage has been defined, but it cannot be activated.'));
   } else echo json_encode(array('error' => 3, 'message' => 'Cannot define new XML for this storage pool.'));
  } else echo json_encode(array('error' => 2, 'message' => 'Cannot connect to libvirt service.'));
 } else echo json_encode(array('error' => 1, 'message' => 'Admin not logged in.'));
?>