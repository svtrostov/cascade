<?php
/*==================================================================================================
Описание: Предопределенные константы
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');


return array(

	#---------------------
	#Менеджмент системы

	
	#Адрес электронной почты администратора,
	#Используется для отправки предупреждений, аудита и прочей отладочной информации
	'ADMIN_EMAIL'		=> 'localhost@localdomain',


	#---------------------
	#Разное
	'SQ'			=> '\'',		#Single Quote (Database)
	'DQ'			=> '"',			#Double Quote (Database)
	'BQ'			=> '`',			#Back quote
	'LQ'			=> 0x01,		#Left Field Quote
	'RQ'			=> 0x02,		#Right Field Quote
	'RN'			=> "\r\n",		#Перенос строки (Log)


	#---------------------
	#Database константы: Константы формата возвращаемого результата
	'DB_ERROR'		=> -1,				#Ошибка
	'DB_NONE'		=> 0,				#Ноль
	'DB_BOTH'		=> MYSQL_BOTH,		#Вернуть каждую запись результата как оба типа массива (нумерованный + ассоциированный)
	'DB_ASSOC'		=> MYSQL_ASSOC,		#Вернуть каждую запись результата как ассоциативный массив
	'DB_NUM'		=> MYSQL_NUM,		#Вернуть каждую запись результата как обычный нумерованный массив
	'BIND_NULL'		=> 0,
	'BIND_TEXT'		=> 1,
	'BIND_NUM'		=> 2,
	'BIND_FIELD'	=> 3,
	'BIND_SQL'		=> 4,



	#Тип данных: 
	#vars - переменные, 
	#defines - константы, декларируются через define(), все переменные массива должны быть скалярными
	'__type__' => 'defines'
);

?>
