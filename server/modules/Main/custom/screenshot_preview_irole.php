<?php
/*==================================================================================================
Описание: Отдача контента скриншота
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');

$irole_id = Request::_getId('irole_id',0);
$request_file = DIR_IROLE_SCREENSHOTS.'/irole_'.$irole_id.'.jpg';
$default_file = DIR_IROLE_SCREENSHOTS.'/default.jpg';
$filename = (file_exists($request_file)&&is_readable($request_file) ? $request_file : $default_file);

$content = @file_get_contents($filename);

header('Content-type: image/jpeg',true);
header("Content-Transfer-Encoding: bytes",true);
header('Content-length: '.strlen($content),true);

echo $content;
exit;

?>