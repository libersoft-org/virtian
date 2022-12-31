<?php
 if (session_status() == PHP_SESSION_NONE) session_start();
 require_once('./settings.php');
 require_once('./functions.php');
 require_once('./libvirt.php');
 if ($_SESSION['admin-login']) {
  $lv = new Libvirt();
  if (@$lv->connect($GLOBALS['address'])) {
   $vm_template = file_get_contents('xml/vm.xml');
   $vm_cd_template = file_get_contents('xml/vm-cd.xml');
   $vm_disk_image_template = file_get_contents('xml/vm-disk-image.xml');
   $vm_disk_device_template = file_get_contents('xml/vm-disk-device.xml');
   $vm_net_template = file_get_contents('xml/vm-net.xml');
   if (isset($_POST['iso']) && $_POST['iso'] != '') {
    $vm_cd_array = array(
     '{{iso}}' => $GLOBALS['iso-path'] . '/' . $_POST['iso']
    );
    $vm_cd_xml = html_replace($vm_cd_array, $vm_cd_template);
   }
   if (isset($_POST['disk'])) {
    foreach ($_POST['disk'] as $k => $d) {
     switch ($d['add_type']) {
      case '0':
       // new image
       $image_path = @$lv->get_storagepool_info($d['storage'])['path'] . '/' . $d['name'] . '.img';
       if (!file_exists($image_path)) {
        if (!@$lv->storagevolume_create($d['storage'], $d['name'] . '.img', getImageFormat($d['image_type']), $d['size'], getSizeUnit($d['size_unit']), $d['size'], getSizeUnit($d['size_unit']))) die (json_encode(array('error' => 6, 'message' => 'Error while creating a new storage volume (disk image).')));
       } else die (json_encode(array('error' => 5, 'message' => 'Cannot create a new storage volume (virtual disk image file), because it already exists.')));
       $vm_disk_array = array(
        '{{type}}' => getImageFormat($d['image_type']),
        '{{dev}}'  => getDevice('vd', $k),
        '{{img}}'  => $image_path
       );
       $vm_disks_xml .= html_replace($vm_disk_array, $vm_disk_image_template) . "\r\n";
       break;
      case '1':
       // existing image
       $image_path = @$lv->get_storagepool_info($d['storage'])['path'] . '/' . $d['image'];
       if ($image_path == null) die (json_encode(array('error' => 4, 'message' => 'Cannot get storage pool informations.')));
       $xml = @$lv->storagevolume_get_xml_desc($d['storage'], $d['image']);
       $xml = substr($xml, strpos($xml, '<format type=') + 14);
       $type = substr($xml, 0, strpos($xml, '\''));
       if (substr($image_type, -1) == "\n") $image_type = substr($image_type, 0, -1);
       $vm_disk_array = array(
        '{{type}}' => $type,
        '{{dev}}' => getDevice('vd', $k),
        '{{img}}' => $image_path
       );
       $vm_disks_xml .= html_replace($vm_disk_array, $vm_disk_image_template) . "\r\n";
       break;
      case '2':
       // device
       $vm_disk_array = array(
        '{{path}}' => $d['path'],
        '{{dev}}'  => getDevice('vd', $k),
       );
       $vm_disks_xml .= html_replace($vm_disk_array, $vm_disk_device_template) . "\r\n";
       break;
     }
    }
   }
   if (isset($_POST['network'])) {
    foreach ($_POST['network'] as $n) {
     $vm_network_template = file_get_contents('xml/vm-net.xml');
     $vm_networks_array = array(
      '{{dev}}' => $n,
     );
     $vm_networks_xml .= html_replace($vm_networks_array, $vm_network_template) . "\r\n";
    }
   }
   $ram_unit = getSizeUnit($_POST['ram_unit']);
   $cdrom = $_POST['iso'] != '' ? $GLOBALS['iso-path'] . '/' . $_POST['iso'] : '';
   $vm_new_uuid = @$lv->generate_uuid();
   $vm_array = array(
    '{{name}}'     => $_POST['name'],
    '{{cpu}}'      => $_POST['cpu'],
    '{{uuid}}'     => $vm_new_uuid,
    '{{ram-unit}}' => $ram_unit,
    '{{ram}}'      => $_POST['ram'],
    '{{password}}' => $_POST['password'],
    '{{cd}}'       => $_POST['iso'] != '' ? $vm_cd_xml : '',
    '{{disks}}'    => $vm_disks_xml,
    '{{nets}}'     => $vm_networks_xml
   );
   $vm_xml = html_replace($vm_array, $vm_template);
   $vm_xml_array = explode("\r\n", $vm_xml);
   foreach ($vm_xml_array as $k => $v) if ($v == '') unset($vm_xml_array[$k]);
   $vm_xml = implode("\r\n", $vm_xml_array);
   if (@$lv->domain_define($vm_xml)) echo json_encode(array('error' => 0, 'message' => 'OK'));
   else echo json_encode(array('error' => 3, 'message' => 'Cannot create a new virtual server, because there is an error in its XML definition.'));
  } else echo json_encode(array('error' => 2, 'message' => 'Cannot connect to libvirt service.'));
 } else echo json_encode(array('error' => 1, 'message' => 'Admin not logged in.'));
?>