<?php
 if (session_status() == PHP_SESSION_NONE) session_start();
 require_once('./settings.php');
 require_once('./functions.php');
 require_once('./libvirt.php');
 getPage();

 function getPage() {
  if (!$_SESSION['admin-login']) {
   $GLOBALS['body'] = file_get_contents($GLOBALS['template-path'] . '/login.html');
   $body_array = array(
    '[[template-path]]' => $GLOBALS['template-path'],
    '[[product]]'       => $GLOBALS['product'],
    '[[version]]'       => $GLOBALS['version'],
    '[[company]]'       => $GLOBALS['company'],
    '[[web]]'           => $GLOBALS['web'],
    '[[year-from]]'     => $GLOBALS['year-from'],
    '[[year-to]]'       => date('Y'),
   );
  } else {
   $GLOBALS['body']           = file_get_contents($GLOBALS['template-path'] . '/index.html');
   $GLOBALS['error_template'] = file_get_contents($GLOBALS['template-path'] . '/error.html');
   switch($_GET['page']) {
    case 'vms':
     getVMs();
     break;
    case 'vm':
     getVM();
     break;
    case 'vm-new':
     getVMNew();
     break;
    case 'vm-edit':
     getVMEdit();
     break;
    case 'storage':
     getStorage();
     break;
    case 'storage-new':
     getStorageNew();
     break;
    case 'storage-volumes':
     getStorageVolumes();
     break;
    case 'storage-volume-new':
     getStorageVolumeNew();
     break;
    case 'storage-volume':
     getStorageVolume();
     break;
    case 'network':
     getNetwork();
     break;
    case 'settings':
     getSettings();
     break;
    case 'log':
     getLog();
     break;
    default:
     getStatus();
     break;
   }
   $body_array = array(
    '[[template-path]]'   => $GLOBALS['template-path'],
    '[[product]]'         => $GLOBALS['product'],
    '[[version]]'         => $GLOBALS['version'],
    '[[company]]'         => $GLOBALS['company'],
    '[[web]]'             => $GLOBALS['web'],
    '[[year-from]]'       => $GLOBALS['year-from'],
    '[[year-to]]'         => date('Y'),
    '[[active-status]]'   => !isset($_GET['page']) ? ' active' : '',
    '[[active-vms]]'      => $_GET['page'] == 'vms' || $_GET['page'] == 'vm' || $_GET['page'] == 'vm-new' || $_GET['page'] == 'vm-edit' ? ' active' : '',
    '[[active-storage]]'  => $_GET['page'] == 'storage' ? ' active' : '',
    '[[active-network]]'  => $_GET['page'] == 'network' ? ' active' : '',
    '[[active-settings]]' => $_GET['page'] == 'settings' ? ' active' : '',
    '[[active-log]]'      => $_GET['page'] == 'log' ? ' active' : ''
   );
  }
  $GLOBALS['body'] = html_replace($body_array, $GLOBALS['body']);
  echo $GLOBALS['body'];
 }

 function getStatus() {
  $ram = getRAMUsage();
  $storage = getStorageUsage();
  $storage_template = file_get_contents($GLOBALS['template-path'] . '/status-storage.html');
  $storage_html = '';
  $lv = new Libvirt();
  $lvver = libvirt_version();
  foreach ($storage as $s) {
   $storage_array = array(
    '[[path]]'         => $s[6],
    '[[device]]'       => $s[0],
    '[[fs]]'           => $s[1],
    '[[used]]'         => getHumanSize($s[3]),
    '[[free]]'         => getHumanSize($s[4]),
    '[[total]]'        => getHumanSize($s[2]),
    '[[used-percent]]' => round(($s[3] / $s[2]) * 100, 2),
   );
   $storage_html .= html_replace($storage_array, $storage_template) . "\r\n";
  }
  $body_array = array(
   '[[title]]'               => 'Server status',
   '[[content]]'             => file_get_contents($GLOBALS['template-path'] . '/status.html'),
   '[[version-product]]'     => $GLOBALS['version'],
   '[[version-linux]]'       => php_uname(),
   '[[version-php]]'         => phpversion(),
   '[[version-libvirt]]'     => $lvver['libvirt.major'] . '.' . $lvver['libvirt.minor'] . '.' . $lvver['libvirt.release'],
   '[[version-libvirt-php]]' => $lvver['connector.major'] . '.' . $lvver['connector.minor'] . '.' . $lvver['connector.release'],
   '[[cpu-usage]]'           => sys_getloadavg()[0] . '%',
   '[[ram-used]]'            => getHumanSize($ram['used']),
   '[[ram-free]]'            => getHumanSize($ram['free']),
   '[[ram-total]]'           => getHumanSize($ram['total']),
   '[[ram-used-percent]]'    => $ram['used-percent'],
   '[[storage]]'             => $storage_html,
  );
  $GLOBALS['body'] = html_replace($body_array, $GLOBALS['body']);
 }

 function getVMs() {
  $vms_template = file_get_contents($GLOBALS['template-path'] . '/vms.html');
  $lv = new Libvirt();
  if (!@$lv->connect($GLOBALS['address'])) {
   $body_array = array(
    '[[title]]'   => 'Error',
    '[[content]]' => str_replace('[[error]]', 'Cannot connect to libvirt service.', $GLOBALS['error_template']),
   );
  } else {
   $vms_row_template = file_get_contents($GLOBALS['template-path'] . '/vms-row.html');
   $states = getVMStates();
   $domName = '';
   if (!empty($_GET['id'])) $domName = $lv->domain_get_name_by_uuid($_GET['id']);
   $vms = $lv->get_domains();
   $rows = '';
   if ($vms != null) {
    foreach ($vms as $v) {
     $domObject = $lv->get_domain_object($v);
     $domInfo   = $lv->domain_get_info($domObject);
     $vnc       = $lv->domain_get_vnc_port($domObject);
     $row_array = array(
      '[[color]]'  => $states[$domInfo['state']]['color'],
      '[[status]]' => $states[$domInfo['state']]['state'],
      '[[name]]'   => $v,
      '[[id]]'     => libvirt_domain_get_uuid_string($domObject),
      '[[cpu]]'    => $domInfo['nrVirtCpu'],
      '[[ram]]'    => getHumanSize($domInfo['maxMem'] * 1024),
      '[[vnc]]'    => $vnc == 0 || $vnc == -1 ? 'None' : $vnc
     );
     $r = html_replace($row_array, $vms_row_template);
     $rows .= $r . "\r\n";
    }
   }
   $body_array = array(
    '[[title]]'   => 'Virtual servers',
    '[[content]]' => $vms_template,
    '[[rows]]'    => $rows
   );
  }
  $GLOBALS['body'] = html_replace($body_array, $GLOBALS['body']);
 }

 function getVM() {
  $vm_template = file_get_contents($GLOBALS['template-path'] . '/vm.html');
  $vm_net_template = file_get_contents($GLOBALS['template-path'] . '/vm-net.html');
  $vm_disk_template = file_get_contents($GLOBALS['template-path'] . '/vm-disk.html');
  $lv = new Libvirt();
  if (!@$lv->connect($GLOBALS['address'])) {
   $body_array = array(
    '[[title]]'   => 'Error',
    '[[content]]' => str_replace('[[error]]', 'Cannot connect to libvirt service.', $GLOBALS['error_template'])
   );
  } else {
   $domName   = $lv->domain_get_name_by_uuid($_GET['id']);
   $domObject = $lv->get_domain_object($domName);
   $domInfo   = $lv->domain_get_info($domObject);
   $vnc       = $lv->domain_get_vnc_port($domObject);
   $networks  = $lv->domain_get_interface_devices($domObject);
   $disks     = $lv->domain_get_disk_devices($domObject);
   foreach ($networks as $k => $v) {
    if ($k !== 'num') {
     $vm_net_array = array('[[network]]' => $v);
     $network_html .= html_replace($vm_net_array, $vm_net_template);
    }
   }
   foreach ($disks as $k => $v) {
    if ($k !== 'num') {
     $vm_disks_array = array('[[disk]]' => $v);
     $disks_html .= html_replace($vm_disks_array, $vm_disk_template);
    }
   }
   $body_array = array(
    '[[title]]'   => 'Virtual servers - ' . $domName,
    '[[content]]' => $vm_template,
    '[[id]]'      => libvirt_domain_get_uuid_string($domObject),
    '[[name]]'    => $domName,
    '[[cpu]]'     => $domInfo['nrVirtCpu'],
    '[[ram]]'     => getHumanSize($domInfo['maxMem'] * 1024),
    '[[state]]'   => getVMStates()[$domInfo['state']]['state'],
    '[[net]]'     => $network_html,
    '[[disks]]'   => $disks_html,
    '[[vnc]]'     => $vnc == 0 || $vnc == -1 ? 'None' : $vnc
   );
  }
  $GLOBALS['body'] = html_replace($body_array, $GLOBALS['body']);
 }

 function getVMNew() {
  $vm_new_template     = file_get_contents($GLOBALS['template-path'] . '/vm-new.html');
  $vm_new_cpu_template = file_get_contents($GLOBALS['template-path'] . '/vm-new-cpu.html');
  $vm_new_iso_template = file_get_contents($GLOBALS['template-path'] . '/vm-new-iso.html');
  $disk_template_path  = $GLOBALS['template-path'] . '/vm-new-disk.html';
  $net_template_path   = $GLOBALS['template-path'] . '/vm-new-net.html';
  $lv = new Libvirt();
  if (!@$lv->connect($GLOBALS['address'])) {
   $body_array = array(
    '[[title]]'   => 'Error',
    '[[content]]' => str_replace('[[error]]', 'Cannot connect to libvirt service.', $GLOBALS['error_template'])
   );
  } else {
   $domName = $lv->domain_get_name_by_uuid($_GET['id']);
   for ($i = substr(shell_exec('lscpu | awk \'$1 == "CPU(s):" {print $2}\''), 0, -1); $i >= 1;  $i--) $cpus .= str_replace('[[cpu]]', $i, $vm_new_cpu_template) . "\r\n";
   foreach (glob($GLOBALS['iso-path'] . '/*.iso') as $f) $isos .= str_replace('[[iso]]', basename($f), $vm_new_iso_template) . "\r\n";
   $body_array = array(
    '[[title]]'             => 'Virtual servers - New virtual server',
    '[[content]]'           => $vm_new_template,
    '[[error]]'             => $_GET['error'] != '' ? str_replace('[[error]]', $_GET['error'], $error_template) : '',
    '[[name]]'              => $domName,
    '[[cpu-cores]]'         => $cpus,
    '[[password]]'          => getRandomPassword(8, 16),
    '[[isos]]'              => $isos,
    '[[add-disk-template]]' => $disk_template_path,
    '[[add-net-template]]'  => $net_template_path
   );
  }
  $GLOBALS['body'] = html_replace($body_array, $GLOBALS['body']);
 }
 
 function getVMEdit() {
  $vm_edit_template = file_get_contents($GLOBALS['template-path'] . '/vm-edit.html');
  $lv = new Libvirt();
  if (!@$lv->connect($GLOBALS['address'])) {
   $body_array = array(
    '[[title]]'   => 'Error',
    '[[content]]' => str_replace('[[error]]', 'Cannot connect to libvirt service.', $GLOBALS['error_template'])
   );
  } else {
   $domName = $lv->domain_get_name_by_uuid($_GET['id']);
   $xml = $lv->domain_get_xml($domName);
   $body_array = array(
    '[[title]]'   => 'virtual servers - Edit virtual server',
    '[[content]]' => $vm_edit_template,
    '[[error]]'   => $_GET['error'] != '' ? str_replace('[[error]]', $_GET['error'], $error_template) : '',
    '[[id]]'      => $_GET['id'],
    '[[name]]'    => $domName,
    '[[xml]]'     => $xml
   );
  }
  $GLOBALS['body'] = html_replace($body_array, $GLOBALS['body']);
 }

 function getStorage() {
  $storage_template = file_get_contents($GLOBALS['template-path'] . '/storage.html');
  $pool_template = file_get_contents($GLOBALS['template-path'] . '/storage-row.html');
  $lv = new Libvirt();
  if (!@$lv->connect($GLOBALS['address'])) {
   $body_array = array(
    '[[title]]'   => 'Error',
    '[[content]]' => str_replace('[[error]]', 'Cannot connect to libvirt service.', $GLOBALS['error_template'])
   );
  } else {
   $pools = $lv->get_storagepools();
   foreach ($pools as $p) {
    $pi = $lv->get_storagepool_info($p);
    $pools_array = array(
     '[[name]]'       => $p,
     '[[id]]'         => $p,
     '[[state]]'      => $pi['state'],
     '[[capacity]]'   => getHumanSize($pi['capacity']),
     '[[allocation]]' => getHumanSize($pi['allocation']),
     '[[available]]'  => getHumanSize($pi['available']),
     '[[active]]'     => $pi['active'] == 1 ? 'yes' : 'no',
     '[[active-alt]]' => $pi['active'] == 1 ? 'Yes' : 'No',
     '[[path]]'       => $pi['path'],
     '[[volumes]]'    => $pi['volume_count']
    );
    $pools_html .= html_replace($pools_array, $pool_template) . "\r\n";
   }
   $body_array = array(
    '[[title]]'   => 'Storage',
    '[[content]]' => $storage_template,
    '[[rows]]'    => $pools_html
   );
  }
  $GLOBALS['body'] = html_replace($body_array, $GLOBALS['body']);
 }

 function getStorageNew() {
  $storage_new_template = file_get_contents($GLOBALS['template-path'] . '/storage-new.html');
  $body_array = array(
   '[[title]]'   => 'Storage - New storage pool',
   '[[content]]' => $storage_new_template,
   '[[error]]'   => $_GET['error'] != '' ? str_replace('[[error]]', $_GET['error'], $error_template) : '',
  );
  $GLOBALS['body'] = html_replace($body_array, $GLOBALS['body']);
 }

 function getStorageVolumes() {
  $storage_volumes_template = file_get_contents($GLOBALS['template-path'] . '/storage-volumes.html');
  $volume_template = file_get_contents($GLOBALS['template-path'] . '/storage-volumes-row.html');
  $lv = new Libvirt();
  if (!@$lv->connect($GLOBALS['address'])) {
   $body_array = array(
    '[[title]]'   => 'Error',
    '[[content]]' => str_replace('[[error]]', 'Cannot connect to libvirt service.', $GLOBALS['error_template'])
   );
  } else {
   $volumes = $lv->storagepool_get_volume_information($_GET['id']);
   foreach ($volumes as $k => $v) {
    $volumes_array = array(
     '[[name]]'       => $k,
     '[[pool]]'       => $_GET['id'],
     '[[capacity]]'   => getHumanSize($v['capacity']),
     '[[allocation]]' => getHumanSize($v['allocation']),
     '[[path]]'       => $v['path']
    );
    $volumes_html .= html_replace($volumes_array, $volume_template) . "\r\n";
   }
   $body_array = array(
    '[[title]]'   => 'Storage - Storage volumes',
    '[[content]]' => $storage_volumes_template,
    '[[rows]]'    => $volumes_html,
    '[[name]]'    => $_GET['id']
   );
  }
  $GLOBALS['body'] = html_replace($body_array, $GLOBALS['body']);
 }
 
 function getStorageVolume() {
  $storage_volume_template = file_get_contents($GLOBALS['template-path'] . '/storage-volume.html');
  $lv = new Libvirt();
  if (!@$lv->connect($GLOBALS['address'])) {
   $body_array = array(
    '[[title]]'   => 'Error',
    '[[content]]' => str_replace('[[error]]', 'Cannot connect to libvirt service.', $GLOBALS['error_template'])
   );
  } else {
   $volume = $lv->storagepool_get_volume_information($_GET['pool'], $_GET['id']);
   $storage_volume_array = array(
    '[[pool]]'       => $_GET['pool'],
    '[[id]]'         => $_GET['id'],
    '[[path]]'       => $volume[$_GET['id']]['path'],
    '[[capacity]]'   => getHumanSize($volume[$_GET['id']]['capacity']),
    '[[allocation]]' => getHumanSize($volume[$_GET['id']]['allocation']),
   );
   $storage_volume_html = html_replace($storage_volume_array, $storage_volume_template);
   $body_array = array(
    '[[title]]'   => 'Storage - Storage volume',
    '[[content]]' => $storage_volume_html,
   );
  }
  $GLOBALS['body'] = html_replace($body_array, $GLOBALS['body']);
 }

 function getStorageVolumeNew() {
  $storage_volume_new_template = file_get_contents($GLOBALS['template-path'] . '/storage-volume-new.html');
  $body_array = array(
   '[[title]]'   => 'Storage - New storage volume',
   '[[content]]' => $storage_volume_new_template,
   '[[error]]'   => $_GET['error'] != '' ? str_replace('[[error]]', $_GET['error'], $error_template) : '',
   '[[pool]]'    => $_GET['pool']
  );
  $GLOBALS['body'] = html_replace($body_array, $GLOBALS['body']);
 }

 function getNetwork() {
  $network_template = file_get_contents($GLOBALS['template-path'] . '/network.html');
  $row_template = file_get_contents($GLOBALS['template-path'] . '/network-row.html');
  $network_html = '';
  $networks = getNetworkInterfaces();
  foreach ($networks as $n) {
   $net = getNetworkDetails($n);
   $network_array = array(
    '[[name]]'      => $n,
    '[[ip]]'        => $net['ip'],
    '[[mask]]'      => $net['mask'],
    '[[broadcast]]' => $net['broadcast']
   );
   $network_html .= html_replace($network_array, $row_template) . "\r\n";
  }
  $body_array = array(
   '[[title]]'   => 'Network',
   '[[content]]' => $network_template,
   '[[rows]]'    => $network_html
  );
  $GLOBALS['body'] = html_replace($body_array, $GLOBALS['body']);
 }

 function getSettings() {
  $settings_template = file_get_contents($GLOBALS['template-path'] . '/settings.html');
  $body_array = array(
   '[[title]]'   => 'Settings',
   '[[content]]' => $settings_template
  );
  $GLOBALS['body'] = html_replace($body_array, $GLOBALS['body']);
 }
 
 function getLog() {
  $log_template = file_get_contents($GLOBALS['template-path'] . '/log.html');
  $body_array = array(
   '[[title]]'   => 'Log',
   '[[content]]' => $log_template
  );
  $GLOBALS['body'] = html_replace($body_array, $GLOBALS['body']);
 }
?>