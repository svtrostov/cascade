<?php
/*==================================================================================================
Описание: Cron - скрипт рассылки сообщений сотрудникам 
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
define('BEGIN_WORK_TIME',microtime(true));
/*
 * Проверка текущей версии PHP
 * Данное программное обеспечение требует версии PHP не ниже 5.4.0
 * Это связано с использованием технологии Trait, введенной в PHP с версии 5.4.0
 */
if(version_compare(PHP_VERSION, '5.4.0', '<')){
	die('FATAL ERROR: you using PHP less than 5.4.0. This software is incompatible with this version. Please upgrade your PHP.');
}


/***********************************************************************
 * ИНИЦИАЛИЗАЦИЯ
 ***********************************************************************/

#------------------------------------------------------------
#Локализация
setLocale(LC_ALL, 'ru_RU.UTF-8');
setLocale(LC_NUMERIC, 'C');

#------------------------------------------------------------
#Временная зона
if(function_exists('date_default_timezone_set')) date_default_timezone_set('Europe/Moscow');


/*
 * Константы приложения
 */
define('APP_INSIDE',	true);		#Признак, указывающий на корректный запуск приложения
define('APP_DEBUG',		true);		#Признак включения режима отладки
define('APP_CRON',		true);		#Признак работы через CRON


/*
 * Пути к папкам
 */
define('DIR_ROOT',		realpath(dirname(__FILE__).'/../../'));
define('DIR_SERVER', 	DIR_ROOT.'/server');				#Путь к корневой папке серверной части
define('DIR_CRON',		DIR_ROOT.'/server/cron');			#Путь к папке для хранения временных файлов
define('DIR_TEMP',		DIR_ROOT.'/server/tmp');			#Путь к папке для хранения временных файлов
define('DIR_CLASSES',	DIR_ROOT.'/server/classes');		#Путь к папке с файлами классов
define('DIR_FUNCTIONS', DIR_ROOT.'/server/functions');		#Путь к папке с файлами функций
define('DIR_LOGS',		DIR_ROOT.'/server/logs');			#Путь к папке с LOG файлами
define('DIR_MODULES',	DIR_ROOT.'/server/modules');		#Путь к папке с модулями
define('DIR_CONFIG',	DIR_ROOT.'/server/config');			#Путь к папке с файлами настроек
define('DIR_LANGUAGES',	DIR_ROOT.'/server/languages');		#Путь к папке с файлами языковых локализаций
define('DIR_CLIENT',	DIR_ROOT.'/client');				#Путь к папке с клиентскими файлами


/*
 * Загрузка функций cron
 */
require_once(DIR_FUNCTIONS.'/cron.functions.php');
/*
#Проверка дублирования запуска скрипта
$script_pid = cron_proc_exists(basename(realpath(__FILE__)),1);
if(!empty($script_pid))  die('script already running');
*/

/*
 * Загрузка основных функций
 */
require_once(DIR_FUNCTIONS.'/utils.functions.php');
require_once(DIR_FUNCTIONS.'/loader.functions.php');

if(!loader_init()) die('loader_init() false');




/***********************************************************************
 * ФУНКЦИИ
 ***********************************************************************/



/***********************************************************************
 * ОСНОВНАЯ ЧАСТЬ
 ***********************************************************************/

$db = Database::getInstance('main');


$smtp_secure		= Config::getOption('general','smtp_secure','ssl');
$smtp_host			= Config::getOption('general','smtp_host',false);
$smtp_port			= Config::getOption('general','smtp_port',0);
$smtp_username		= Config::getOption('general','smtp_username','');
$smtp_password		= Config::getOption('general','smtp_password','');
$smtp_from_email	= Config::getOption('general','smtp_from_email','');
$smtp_from_name		= Config::getOption('general','smtp_from_name','');
$smtp_reply_email	= Config::getOption('general','smtp_reply_email','');
$smtp_reply_name	= Config::getOption('general','smtp_reply_name','');

if(empty($smtp_host)||empty($smtp_port)||empty($smtp_username)||empty($smtp_from_email)){
	die('CONFIG ERROR: SMTP SETTINGS NOT FOUND');
}

if(empty($smtp_reply_email)) $smtp_reply_email = $smtp_from_email;
if(empty($smtp_reply_name)) $smtp_reply_name = 'Mail service';
if(empty($smtp_from_name)) $smtp_from_name = 'Mail service';


$mail = new PHPMailer();


$mail->CharSet = "utf-8";
$mail->IsSMTP();							// telling the class to use SMTP
$mail->IsHTML();							// HTML
$mail->SMTPAuth			= true;				// enable SMTP authentication
$mail->SMTPSecure		= $smtp_secure;		// sets the prefix to the servier
$mail->SMTPKeepAlive	= true;				// SMTP connection will not close after each email sent
$mail->Host				= $smtp_host;		// sets the SMTP server
$mail->Port				= $smtp_port;		// set the SMTP port for the GMAIL server
$mail->Username			= $smtp_username;	// SMTP account username
$mail->Password			= $smtp_password;	// SMTP account password
$mail->SetFrom($smtp_from_email, $smtp_from_name);
$mail->AddReplyTo($smtp_reply_email, $smtp_reply_name);


LABEL_START:

#Получение сообщения
$db->prepare('SELECT * FROM `mail` LIMIT 1');
if( ($message = $db->selectRecord()) === false){
	die('DB ERROR: SELECT FROM `mail`');
}
if(empty($message)){
	goto LABEL_COMPLETE;
}


$mail->Subject	= $message['subject'];
$mail->AltBody	= "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test
$mail->MsgHTML($message['content']);
$mail->AddAddress($message["mail_to"], $message["mail_to"]);

if(!$mail->Send()) {
	die("Mailer Error (" . str_replace("@", "&#64;", $message["mail_to"]) . ') ' . $mail->ErrorInfo);
}

$mail->ClearAddresses();
$mail->ClearAttachments();


$db->prepare('DELETE FROM `mail` WHERE `id`=?');
$db->bind($message['id']);
if($db->delete() === false){
	die('DB ERROR: DELETE FROM `mail`');
}


goto LABEL_START;

#Успех
LABEL_COMPLETE:
die('complete');
?>