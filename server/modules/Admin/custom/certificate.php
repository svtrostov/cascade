<?php
/*==================================================================================================
Описание: Отдача контента скриншота
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');

$request_status = true;
$content = '';

$employer_id = Request::_getId('employer_id',0);
$sha1 = Request::_getStr('sha1','');

if(empty($employer_id)||empty($sha1)){
	$request_status = false;
	$sha1 = 'error';
	$content = 'Incorrect employer_id and/or certificate SHA1';
}


if($request_status){
	if(!$uaccess->checkAccess('employers.certificate.moderate', 0)){
		$request_status = false;
		$sha1 = 'error';
		$content = 'Access denided';
	}
}


if($request_status){
	$db = Database::getInstance('main');
	//Проверка существования сертификата
	$db->prepare('SELECT * FROM `employer_certs` WHERE `employer_id`=? AND `SSL_CERT_HASH` LIKE ? LIMIT 1');
	$db->bind($employer_id);
	$db->bind($sha1);
	if(($cert_user = $db->selectRecord())===false){
		$request_status = false;
		$sha1 = 'error';
		$content = '500: Internal Server error';
	}
}

if($request_status){
	if(empty($cert_user)){
		$request_status = false;
		$content = 'Sertificate SHA1=['.$sha1.'] not found';
		$sha1 = 'error';
	}
}

if($request_status){
	$content = $cert_user['SSL_CLIENT_CERT'];
}



header('Content-type: text/html',true);
header('Content-Length: '.strlen($content),true);
header('Content-Disposition: attachment; filename="'.$sha1.'.cer"');
header('Content-Transfer-Encoding: bytes');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');
header('Connection: close');
echo $content;
exit;
?>