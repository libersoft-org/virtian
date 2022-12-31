<?php
 if (session_status() == PHP_SESSION_NONE) session_start();
 if ($_SESSION['admin-login']) {
  require_once('./settings.php');
  require_once('./functions.php');
  $net_template = file_get_contents($GLOBALS['template-path'] . '/status-network.html');
  $nets = getNetworkInterfaces();
  foreach ($nets as $net_name) {
   $network = shell_exec('vnstat -i ' . $net_name . ' -tr 2');
   $net = explode("\n", $network);
   $net_html = str_replace('[[name]]', $net_name, $net_template);
   for ($i = 3; $i < count ($net); $i++) {
    $parts = preg_replace('!\s+!', ';', trim($net[$i]));
    $parts = explode(';',trim($parts));
    if ($parts[0] == 'rx') $net_html = str_replace('[[download]]', $parts[1] . ' ' . $parts[2], $net_html);
    if ($parts[0] == 'tx') $net_html = str_replace('[[upload]]', $parts[1] . ' ' . $parts[2], $net_html);
   }
   echo $net_html;
  }
 }
?>