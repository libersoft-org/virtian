<?php
 error_reporting(E_ERROR | E_PARSE);
 if (session_status() == PHP_SESSION_NONE) session_start();
 require_once('./functions.php');
 require_once('./settings.php');
 require_once('./libvirt.php');
 if ($_SESSION['admin-login']) {
  $lv = new Libvirt();
  if (@$lv->connect($GLOBALS['address'])) {
   $domName = @$lv->domain_get_name_by_uuid($_GET['id']);
   if ($domName) {
    $res = @$lv->get_domain_object($domName);
    $screen = @$lv->domain_get_screenshot($res);
    if ($screen != null) {
     header('Content-Type: ' . $screen['mime']);
     echo $screen['data'];
    } else emptyPic();
   } else emptyPic();
  } else emptyPic();
 }
 
 function emptyPic() {
  $iw = 640;
  $ih = 480;
  $font = $GLOBALS['template-path'] . '/font/Ubuntu-B.ttf';
  $fontsize = 50;
  $angle = 0;
  $text = 'Not available';
  $im = imagecreate($iw, $ih);
  $bgcolor = imagecolorallocate($im, 0, 0, 0);
  $fontcolor = imagecolorallocate($im, 239, 101, 4);
  $textbox = imagettfbbox($fontsize, $angle, $font, $text);
  $tw = $textbox[2] - $textbox[0];
  $th = $textbox[7] - $textbox[1];
  $x = ($iw / 2) - ($tw / 2);
  $y = ($ih / 2) - ($th / 2);
  imagettftext($im, $fontsize, $angle, $x, $y, $fontcolor, $font, $text);
  header('Content-type: image/png');
  imagepng($im);
  imagedestroy($im);
 }
?>