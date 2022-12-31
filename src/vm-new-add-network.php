<?php
 require_once('./settings.php');
 require_once('./functions.php');
 if (session_status() == PHP_SESSION_NONE) session_start();
 if ($_SESSION['admin-login']) {
  $net_template = file_get_contents($GLOBALS['template-path'] . '/vm-new-add-network.html');
  $interface_template = file_get_contents($GLOBALS['template-path'] . '/vm-new-add-network-row.html');
  $interfaces = getNetworkInterfaces();
  $interfaces_html = '';
  foreach ($interfaces as $i) {
   $interfaces_html .= str_replace('[[interface]]', $i, $interface_template);
  }
  $net_array = array(
   '[[template-path]]' => $GLOBALS['template-path'],
   '[[id]]'            => $_GET['id'],
   '[[interfaces]]'    => $interfaces_html
  );
  echo html_replace($net_array, $net_template);
 }
?>