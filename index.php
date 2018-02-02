<?php
/*==================================================================================================
Описание: Стартовый скрипт приложения
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


#------------------------------------------------------------
#Локализация
setLocale(LC_ALL, 'ru_RU.UTF-8');
setLocale(LC_NUMERIC, 'C');
mb_internal_encoding("UTF-8");

#------------------------------------------------------------
#Временная зона
if(function_exists('date_default_timezone_set')) date_default_timezone_set('Europe/Moscow');

/*
 * Константы приложения
 */
define('APP_INSIDE',	true);		#Признак, указывающий на корректный запуск приложения
define('APP_DEBUG',		true);		#Признак включения режима отладки

/*
 * Пути к папкам
 */
define('DIR_ROOT',		realpath(dirname(__FILE__)));
define('DIR_SERVER', 	DIR_ROOT.'/server');				#Путь к корневой папке серверной части
define('DIR_TEMP',		DIR_ROOT.'/server/tmp');			#Путь к папке для хранения временных файлов
define('DIR_CLASSES',	DIR_ROOT.'/server/classes');		#Путь к папке с файлами классов
define('DIR_FUNCTIONS', DIR_ROOT.'/server/functions');		#Путь к папке с файлами функций
define('DIR_LOGS',		DIR_ROOT.'/server/logs');			#Путь к папке с LOG файлами
define('DIR_MODULES',	DIR_ROOT.'/server/modules');		#Путь к папке с модулями
define('DIR_CONFIG',	DIR_ROOT.'/server/config');			#Путь к папке с файлами настроек
define('DIR_LANGUAGES',	DIR_ROOT.'/server/languages');		#Путь к папке с файлами языковых локализаций

define('DIR_CLIENT',	DIR_ROOT.'/client');				#Путь к папке с клиентскими файлами
define('DIR_IROLE_SCREENSHOTS',	DIR_ROOT.'/server/irole_screenshots');	#Путь к папке со скриншотами объектов доступа


/*
 * Загрузка основных функций
 */
require_once(DIR_FUNCTIONS.'/utils.functions.php');
require_once(DIR_FUNCTIONS.'/loader.functions.php');
loader_init();

Page::_build();
/*
$v = new Validator(array(
	array(
		'name'=>'test',
		'value'=>250,
		'type'=>'uint',
		'min'=>200,
		'required'=>true,
		'exclude'=>array(250)
	)
));
if(!$v->validate()){
	echo '<pre>';
	print_r($v->getErrors());
	echo '</pre>';
}
*/
/*
echo '<pre>';
print_r(Request::_get('ip_addr'));

//print_r(get_defined_constants());
echo '</pre>';
echo "WORK TIME = ".sprintf("%01.6f",microtime(true)-BEGIN_WORK_TIME);
*/
?>