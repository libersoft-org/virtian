<?php
 if (session_status() == PHP_SESSION_NONE) session_start();
 require_once('./settings.php');
 if ($_POST['pass'] == $GLOBALS['password']) {
  $_SESSION['admin-login'] = true;
  echo json_encode(array('error' => 0, 'message' => 'OK'));
 } else {
  echo json_encode(array('error' => 1, 'message' => 'Wrong password.'));
 }
?>