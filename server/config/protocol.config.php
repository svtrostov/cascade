<?php
/*==================================================================================================
Описание: Настройки протоколирования действий сотрудников
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');




/*--------------------------------------------------------------------------------------
Основные настройки протоколирования
--------------------------------------------------------------------------------------*/
return array(

	#---------------------
	#Типы объектов системы протоколирования
	'object_types'	=> array(
		'menu' =>  array(
			'id'		=> 10,
			'name'		=> 'Меню',
			'db_table'	=> 'menu',
			'db_field'	=> 'menu_id'
		),
		'menuitem' =>  array(
			'id'		=> 20,
			'name'		=> 'Пункт меню',
			'db_table'	=> 'menu_map',
			'db_field'	=> 'item_id'
		),
		'aclobject' =>  array(
			'id'		=> 30,
			'name'		=> 'ACL',
			'db_table'	=> 'access_objects',
			'db_field'	=> 'object_id'
		),
		'employer' => array(
			'id'		=> 100,
			'name'		=> 'Сотрудник',
			'db_table'	=> 'employers',
			'db_field'	=> 'employer_id'
		),
		'employercert' => array(
			'id'		=> 101,
			'name'		=> 'Сертификат сотрудника',
			'db_table'	=> 'employer_certs',
			'db_field'	=> 'id'
		),
		'template' => array(
			'id'		=> 110,
			'name'		=> 'Шаблон доступа',
			'db_table'	=> 'templates',
			'db_field'	=> 'template_id'
		),
		'route' => array(
			'id'		=> 120,
			'name'		=> 'Маршрут согласования',
			'db_table'	=> 'routes',
			'db_field'	=> 'route_id'
		),
		'routeparam'	=> array(
			'id'		=> 121,
			'name'		=> 'Параметр маршрута',
			'db_table'	=> 'route_params',
			'db_field'	=> 'param_id'
		),
		'request' => array(
			'id'		=> 140,
			'name'		=> 'Заявка',
			'db_table'	=> 'requests',
			'db_field'	=> 'request_id'
		),
		'group' => array(
			'id'		=> 160,
			'name'		=> 'Группа',
			'db_table'	=> 'groups',
			'db_field'	=> 'group_id'
		),
		'company' => array(
			'id'		=> 170,
			'name'		=> 'Организация',
			'db_table'	=> 'company',
			'db_field'	=> 'company_id'
		),
		'post' => array(
			'id'		=> 180,
			'name'		=> 'Должность',
			'db_table'	=> 'post',
			'db_field'	=> 'post_id'
		),
		'orgstructure' => array(
			'id'		=> 190,
			'name'		=> 'Орг структура',
			'db_table'	=> 'company_posts',
			'db_field'	=> 'post_uid'
		),
		'iresource' => array(
			'id'		=> 200,
			'name'		=> 'Информационный ресурс',
			'db_table'	=> 'iresources',
			'db_field'	=> 'iresource_id'
		),
		'irole' => array(
			'id'		=> 210,
			'name'		=> 'Объект доступа',
			'db_table'	=> 'iroles',
			'db_field'	=> 'irole_id'
		),
		'irtype' => array(
			'id'		=> 220,
			'name'		=> 'Тип доступа',
			'db_table'	=> 'ir_types',
			'db_field'	=> 'item_id'
		),
		'igroup' => array(
			'id'		=> 225,
			'name'		=> 'Группа ресурсов',
			'db_table'	=> 'iresource_groups',
			'db_field'	=> 'igroup_id'
		),
		'anket' => array(
			'id'		=> 230,
			'name'		=> 'Анкета нового сотрудника',
			'db_table'	=> 'employer_ankets',
			'db_field'	=> 'anket_id'
		)
	),


	#Тип данных: 
	#vars - переменные, 
	#defines - константы, декларируются через define(), все переменные массива должны быть скалярными
	'__type__' => 'vars'

);#end $OPTIONS


?>