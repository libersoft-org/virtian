<?php
 function getHumanSize($bytes) {
  $type = array('', 'k', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y');
  $i = 0;
  while ($bytes >= 1024) {
   $bytes /= 1024;
   $i++;
  }
  return round($bytes, 2) . ' ' . $type[$i] .'B';
 }

 function getRAMUsage() {
  $data = explode("\n", shell_exec('cat /proc/meminfo'));
  $meminfo = array();
  foreach ($data as $line) {
   list($key, $val) = explode(":", $line);
   $meminfo[$key] = str_replace(' kB', '', trim($val) * 1024);
  }
  $result = array(
   'used' => $meminfo['MemTotal'] - $meminfo['MemAvailable'],
   'free' => $meminfo['MemAvailable'],
   'total' => $meminfo['MemTotal'],
   'used-percent' => round((($meminfo['MemTotal'] - $meminfo['MemAvailable']) / $meminfo['MemAvailable']) * 100, 2)
  );
  return $result;
 }

 function getStorageUsage() {
  $storage = explode("\n", shell_exec('df -B1 -T -x tmpfs -x devtmpfs | tail -n +2 | sed -e \'s/\s\+/|/g\''));
  unset($storage[count($storage) - 1]);
  $storage_array = array();
  for ($i = 0; $i < count($storage); $i++) {
   $data = explode('|', $storage[$i]);
   $data[3] = $data[2] - $data[4];
   array_push($storage_array, $data);
  }
  return $storage_array;
 }

 function html_replace($array, $html) {
  return str_replace(array_keys($array), $array, $html);
 }

 function getClientIP() {
  $ip = '';
  if (!empty($_SERVER['HTTP_CLIENT_IP'])) $ip = $_SERVER['HTTP_CLIENT_IP'];
  elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
  else $ip = $_SERVER['REMOTE_ADDR'];
  return $ip;
 }

 function getVMStates() {
  return array(
   array('state' => 'No state', 'color' => 'white'),
   array('state' => 'Running', 'color' => 'green'),
   array('state' => 'Blocked on resource', 'color' => 'gray'),
   array('state' => 'Suspended', 'color' => 'orange'),
   array('state' => 'Shutting down ...', 'color' => 'yellow'),
   array('state' => 'Shut off', 'color' => 'red'),
   array('state' => 'Crashed', 'color' => 'black'),
   array('state' => 'Suspended by guest power management', 'color' => 'blue')
  );
 }

 function getNetworkInterfaces() {
  $nets = explode("\n", shell_exec('cat /proc/net/dev | awk \'{print $1}\' | grep : | sed \'s/.$//\''));
  foreach ($nets as $k => $v) if ($v == 'lo' || $v == '' || strpos($v, 'macvtap') === 0) unset($nets[$k]);    
  return $nets;
 }

 function getNetworkDetails($interface) {
  $s = explode(' ', shell_exec('ifconfig ' . $interface . ' | grep \'inet \' | awk \'{print $2, $4, $6}\''));
  $nets['ip'] = $s[0];
  $nets['mask'] = $s[1];
  $nets['broadcast'] = $s[2];
  return $nets;
 }

 function getRandomPassword($min, $max) {
  $alphabet = 'abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789';
  $pass = '';
  for ($i = 1; $i <= rand($min, $max); $i++) {
   $n = rand(0, strlen($alphabet) - 1);
   $pass .= substr($alphabet, $n, 1);
  }
  return $pass;
 }

 function getDevice($prefix, $id) {
  return $prefix . getDeviceLetter($id);
 }

 function getImageFormat($id) {
  $type = array('0' => 'raw', '1' => 'qcow', '2' => 'qcow2');
  return $type[$id];
 }

 function getDeviceLetter($id) {
  $devs = str_split('abcdefghijklmnopqrstuvwxyz');
  $numeric = $id % count($devs);
  $letter = $devs[$numeric];
  $num = intval($id / count($devs));
  if ($num > 0) return getDevice($num - 1) . $letter;
  else return $letter;
 }

 function getSizeUnit($id) {
  $units = array('0' => 'B', '1' => 'KiB', '2' => 'MiB', '3' => 'GiB', '4' => 'TiB', '5' => 'PiB', '6' => 'EiB');
  return $units[$id];
 }
?>