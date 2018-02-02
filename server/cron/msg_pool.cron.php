<?php
/*==================================================================================================
Описание: Cron - скрипт генерации сообщений для рассылки из пула сообщений
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
if(!empty($script_pid))  die('script already running: '.basename(realpath(__FILE__)));
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

/*
 * Получение информации о заявке и информационном ресурсе из базы данных
 */
function msgpool_isRIResourceActive($request_id=0, $iresource_id=0){
	global $db;
	return ($db->result('SELECT count(*) FROM `request_iresources` WHERE `request_id`='.intval($request_id).' AND `iresource_id`='.intval($iresource_id).' LIMIT 1') > 0);
}#end function


/*
 * Получение информации о заявке и информационном ресурсе из базы данных
 */
function msgpool_request_info($request_id=0, $iresource_id=0){
	global $db;
	$request_iresources_table = (msgpool_isRIResourceActive($request_id, $iresource_id) ? 'request_iresources' : 'request_iresources_hist');
	$db->prepare('
		SELECT 
			REQ.`request_id` as `request_id`,
			REQ.`request_type` as `request_type`,
			RIR.`iresource_id` as `iresource_id`,
			IR.`full_name` as `iresource_name`,
			REQ.`curator_id` as `curator_id`,
			REQ.`employer_id` as `employer_id`,
			REQ.`company_id` as `company_id`,
			C.`full_name` as `company_name`,
			REQ.`post_uid` as `post_uid`,
			P.`full_name` as `post_name`,
			DATE_FORMAT(REQ.`timestamp`, "%d.%m.%Y") as `create_date`,
			REQ.`phone` as `phone`,
			REQ.`email` as `email`
		FROM `'.$request_iresources_table.'` as RIR
			INNER JOIN `requests` as REQ ON REQ.`request_id`=RIR.`request_id`
			INNER JOIN `iresources` as IR ON IR.`iresource_id`=RIR.`iresource_id`
			INNER JOIN `companies` as C ON C.`company_id`=REQ.`company_id`
			INNER JOIN `company_posts` as CP ON CP.`post_uid`=REQ.`post_uid`
			INNER JOIN `posts` as P ON P.`post_id` = CP.`post_id`
		WHERE RIR.`request_id`=? AND RIR.`iresource_id`=? 
		LIMIT 1
	');
	$db->bind($request_id);
	$db->bind($iresource_id);
	return $db->selectRecord();
}#end function




/*
 * Возвращает информацию о сотрудниках
 */
function msgpool_employers_info($employer_ids=array()){
	global $db;
	$db->prepare('
		SELECT EMP.*, 
		DATE_FORMAT(EMP.`birth_date`,"%d.%m.%Y") as `birth_date`
		FROM `employers` as EMP WHERE EMP.`employer_id` IN (?)
	');
	$db->bindSql(implode(',',$employer_ids));
	return $db->selectByKey('employer_id');
}#end function





/*
 * Возвращает контент для письма: список сотрудников
 */
function msgpool_content_employers_list($employer_ids=array()){
	global $employers;
	$result = '';
	foreach($employer_ids as $employer_id){
		if(empty($employers[$employer_id])) continue;
		$result.=
			'<li>'.
			'<table border="0" cellpadding="0" cellspacing="0" width="100%">'.
			'<tr><td class="name">'.$employers[$employer_id]['search_name'].'</td></tr>'.
			(empty($employers[$employer_id]['phone'])?'':'<tr><td class="phone">Телефон: '.$employers[$employer_id]['phone'].'</td></tr>').
			(empty($employers[$employer_id]['email'])?'':'<tr><td class="email">E-mail: <a href="mailto:'.$employers[$employer_id]['email'].'">'.$employers[$employer_id]['email'].'</a></td></tr>').
			'</table>'.
			'</li>';
	}
	return $result;
}#end function



/*
 * Возвращает контент для письма: информация о заявителе
 */
function msgpool_content_request_employer($employer, $request, $contacts=true){
	$contacts=false;
	return
	'<table border="0" cellpadding="0" cellspacing="0" width="100%">'.
		'<tr><td width="120" class="k">Сотрудник:</td><td class="v name">'.$employer['search_name'].'</td></tr>'.
		'<tr><td width="120" class="k">Организация:</td><td class="v">'.$request['company_name'].'</td></tr>'.
		'<tr><td width="120" class="k">Должность:</td><td class="v">'.$request['post_name'].'</td></tr>'.
		(!$contacts
			?''
			:(
				(empty($request['phone'])?'':'<tr><td width="120" class="k">Телефон:</td><td class="v">'.$request['phone'].'</td></tr>').
				(empty($request['email'])?'':'<tr><td width="120" class="k">E-mail:</td><td class="v"><a href="mailto:'.$request['email'].'">'.$request['email'].'</a></td></tr>')
			)
		).
	'</table>';
}#end function





/*
 * Возвращает контент для письма: сформированная строка изменения статуса заявки для заявиетля
 */
function msgpool_content_me_request($message, $employer, $request){
	global $gk_roles;
	$title = '';
	$action = '';
	$wait_action = '';
	$gk_title='';
	$gatekeepers='';
	$gatekeepers='';
	$bg_class='';
	switch($message['type']){
		case 1:
			switch($message['gatekeeper_role']){
				case 1: $title = 'Согласование заявки'; $gk_title='Контакты сотрудников, согласующих заявку:'; break;
				case 2: $title = 'Утверждение заявки'; $gk_title='Контакты сотрудников, утверждающих заявку:'; break;
				case 3: $title = 'Исполнение заявки'; $gk_title='Контакты сотрудников, исполнителей:'; break;
			}
			$action = $title;
			$gatekeepers = (empty($message['gatekeepers']) ? '' : msgpool_content_employers_list($message['gatekeepers']));
			$assistants = (empty($message['assistants']) ? '' : msgpool_content_employers_list($message['assistants']));
			$wait_action = 'Ожидаемое действие';
			$bg_class='normal';
		break;
		case 2:
			switch($message['gatekeeper_role']){
				case 1: $title = 'Заявка согласована'; $gk_title = 'Сотрудник, согласовавший заявку:'; break;
				case 2: $title = 'Заявка утверждена'; $gk_title = 'Сотрудник, утвердивший заявку:'; break;
				case 3: $title = 'Заявка исполнена'; $gk_title = 'Сотрудник, исполнивший заявку:'; break;
			}
			$action = '<font color="green">'.$title.'</font>';
			$gatekeepers = (empty($message['gatekeeper_id']) ? '<li><span class="name">Администратор</span></li>' : msgpool_content_employers_list(array($message['gatekeeper_id'])));
			$wait_action = 'Выполненное действие';
			$bg_class='approve';
		break;
		case 3:
			$title = 'Заявка отклонена';
			$action = '<font color="red">'.$title.'</font>';
			$gk_title = 'Сотрудник, отклонивший заявку:';
			$gatekeepers = (empty($message['gatekeeper_id']) ? '<li><span class="name">Администратор</span></li>' : msgpool_content_employers_list(array($message['gatekeeper_id'])));
			$wait_action = 'Выполненное действие';
			$bg_class='decline';
		break;
		case 4:
			$title = 'Заявка исполнена';
			$action = '<font color="blue">'.$title.'</font>';
			$gk_title = 'Заявка исполнена';
			$wait_action = 'Результат';
			$bg_class='approve';
		break;
	}

	if(empty($title)||empty($gatekeepers)) return '';


	return
	'<tr><td width="100%" colspan="2" cellpadding="0" cellspacing="0" align="left" valign="top" class="'.$bg_class.'">'.
		'<table border="0" cellpadding="0" cellspacing="0" width="100%"><tr>'.
			'<td width="50%" cellpadding="0" cellspacing="0" align="left" valign="top" class="'.$bg_class.'">'.
				'<table border="0" cellpadding="0" cellspacing="0" width="100%">'.
					'<tr><td class="h4">'.$title.'</td></tr>'.
					'<tr><td class="ilist" align="left" valign="top">'.
						'<table border="0" cellpadding="0" cellspacing="0" width="100%">'.
							'<tr><td width="120" class="k">Заявка №:</td><td class="v">'.$request['request_id'].'</td></tr>'.
							'<tr><td width="120" class="k">Тип заявки:</td><td class="v">'.($request['request_type']==3?'Блокировка доступа':'Запрос доступа').'</td></tr>'.
							'<tr><td width="120" class="k">Ресурс:</td><td class="v">'.$request['iresource_name'].'</td></tr>'.
							'<tr><td width="120" class="k">Время:</td><td class="v">'.$message['message_time'].'</td></tr>'.
							(empty($request['phone'])?'':'<tr><td width="120" class="k">Телефон:</td><td class="v">'.$request['phone'].'</td></tr>').
							(empty($request['email'])?'':'<tr><td width="120" class="k">E-mail:</td><td class="v"><a href="mailto:'.$request['email'].'">'.$request['email'].'</a></td></tr>').
							'<tr><td width="120" class="k">&nbsp;</td><td height="40" align="left" valign="middle"><a class="button" href="'.Config::getOption('general','server_address','#').'/main/requests/info?request_id='.$request['request_id'].'">Перейти к заявке</a></td></tr>'.
						'</table>'.
					'</td></tr>'.
				'</table>'.
			'</td>'.
			'<td width="50%" cellpadding="0" cellspacing="0" align="left" valign="top" class="'.$bg_class.'">'.
				'<table border="0" cellpadding="0" cellspacing="0" width="100%">'.
					'<tr><td class="h4">'.$gk_title.'</td></tr>'.
						($message['type'] == 4 
							? ''
							: '<tr><td align="left" valign="top"><ul class="gk">'.$gatekeepers.'</ul></td></tr>'.(!empty($assistants) ? '<tr><td class="h5">Заместители:</td></tr><tr><td><ul class="assist">'.$assistants.'</ul></td></tr>' : '')
						).
				'</table>'.
			'</td>'.
		'</tr></table>'.
	'</td></tr>';

}#end function





/*
 * Возвращает контент для письма: сформированная строка изменения статуса заявки для куратора
 */
function msgpool_content_carator_request($message, $employer, $request){
	global $gk_roles;
	$title = '';
	$action = '';
	$wait_action = '';
	$gk_title='';
	$gatekeepers='';
	$gatekeepers='';
	$bg_class='';
	switch($message['type']){
		case 1:
			switch($message['gatekeeper_role']){
				case 1: $title = 'Согласование заявки'; $gk_title='Контакты сотрудников, согласующих заявку:'; break;
				case 2: $title = 'Утверждение заявки'; $gk_title='Контакты сотрудников, утверждающих заявку:'; break;
				case 3: $title = 'Исполнение заявки'; $gk_title='Контакты сотрудников, исполнителей:'; break;
			}
			$action = $title;
			$gatekeepers = (empty($message['gatekeepers']) ? '' : msgpool_content_employers_list($message['gatekeepers']));
			$assistants = (empty($message['assistants']) ? '' : msgpool_content_employers_list($message['assistants']));
			$wait_action = 'Ожидаемое действие';
			$bg_class='normal';
		break;
		case 2:
			switch($message['gatekeeper_role']){
				case 1: $title = 'Заявка согласована'; $gk_title = 'Сотрудник, согласовавший заявку:'; break;
				case 2: $title = 'Заявка утверждена'; $gk_title = 'Сотрудник, утвердивший заявку:'; break;
				case 3: $title = 'Заявка исполнена'; $gk_title = 'Сотрудник, исполнивший заявку:'; break;
			}
			$action = '<font color="green">'.$title.'</font>';
			$gatekeepers = (empty($message['gatekeeper_id']) ? '<li><span class="name">Администратор</span></li>' : msgpool_content_employers_list(array($message['gatekeeper_id'])));
			$wait_action = 'Выполненное действие';
			$bg_class='approve';
		break;
		case 3:
			$title = 'Заявка отклонена';
			$action = '<font color="red">'.$title.'</font>';
			$gk_title = 'Сотрудник, отклонивший заявку:';
			$gatekeepers = (empty($message['gatekeeper_id']) ? '<li><span class="name">Администратор</span></li>' : msgpool_content_employers_list(array($message['gatekeeper_id'])));
			$wait_action = 'Выполненное действие';
			$bg_class='decline';
		break;
		case 4:
			$title = 'Заявка исполнена';
			$action = '<font color="blue">'.$title.'</font>';
			$gk_title = 'Заявка исполнена';
			$wait_action = 'Результат';
			$bg_class='approve';
		break;
	}


	if(empty($title)||empty($gatekeepers)) return '';


	return
	'<tr><td width="100%" colspan="2" cellpadding="0" cellspacing="0" align="left" valign="top" class="'.$bg_class.'">'.
		'<table border="0" cellpadding="0" cellspacing="0" width="100%"><tr>'.
			'<td width="50%" cellpadding="0" cellspacing="0" align="left" valign="top" class="'.$bg_class.'">'.
				'<table border="0" cellpadding="0" cellspacing="0" width="100%">'.
					'<tr><td class="h4">'.$title.'</td></tr>'.
					'<tr><td class="ilist" align="left" valign="top">'.
						'<table border="0" cellpadding="0" cellspacing="0" width="100%">'.
							'<tr><td width="120" class="k">Заявка №:</td><td class="v">'.$request['request_id'].'</td></tr>'.
							'<tr><td width="120" class="k">Тип заявки:</td><td class="v">'.($request['request_type']==3?'Блокировка доступа':'Запрос доступа').'</td></tr>'.
							'<tr><td width="120" class="k">Ресурс:</td><td class="v">'.$request['iresource_name'].'</td></tr>'.
							'<tr><td width="120" class="k">Время:</td><td class="v">'.$message['message_time'].'</td></tr>'.
							(empty($request['phone'])?'':'<tr><td width="120" class="k">Телефон:</td><td class="v">'.$request['phone'].'</td></tr>').
							(empty($request['email'])?'':'<tr><td width="120" class="k">E-mail:</td><td class="v"><a href="mailto:'.$request['email'].'">'.$request['email'].'</a></td></tr>').
							'<tr><td width="120" class="k">&nbsp;</td><td height="40" align="left" valign="middle"><a class="button" href="'.Config::getOption('general','server_address','#').'/main/requests/info?request_id='.$request['request_id'].'">Перейти к заявке</a></td></tr>'.
						'</table>'.
					'</td></tr>'.
					'<tr><td class="h5">Заявитель (для кого запрошен доступ):</td></tr>'.
					'<tr><td class="ilist" align="left" valign="top">'.
						msgpool_content_request_employer($employer, $request, true).
					'</td></tr>'.
				'</table>'.
			'</td>'.
			'<td width="50%" cellpadding="0" cellspacing="0" align="left" valign="top" class="'.$bg_class.'">'.
				'<table border="0" cellpadding="0" cellspacing="0" width="100%">'.
					'<tr><td class="h4">'.$gk_title.'</td></tr>'.
						($message['type'] == 4 
							? ''
							: '<tr><td align="left" valign="top"><ul class="gk">'.$gatekeepers.'</ul></td></tr>'.(!empty($assistants) ? '<tr><td class="h5">Заместители:</td></tr><tr><td><ul class="assist">'.$assistants.'</ul></td></tr>' : '')
						).
				'</table>'.
			'</td>'.
		'</tr></table>'.
	'</td></tr>';

/*
	return
	'<tr class="'.$bg_class.'"><td class="np"><h4>'.$title.'</h4><div class="block">'.
	'<div class="lw100"><span>Заявка №:</span><div>'.$request['request_id'].'</div></div>'.
	'<div class="lw100"><span>Ресурс:</span><div class="name">'.$request['iresource_name'].'</div></div>'.
	'<div class="lw100"><span>Время:</span><div>'.$message['message_time'].'</div></div>'.
	'<div class="lw100"><span>&nbsp;</span><div class="m50"><a class="button" href="'.Config::getOption('general','server_address','#').'/main/requests/view?request_id='.$request['request_id'].'&iresource_id='.$request['iresource_id'].'">Перейти к заявке</a></div></div>'.
	'<h5>Заявитель (для кого запрошен доступ):</h5><div class="block">'.
	'<div class="lw100 name"><span>Сотрудник:</span><div>'.$employer['search_name'].'</div></div>'.
	'<div class="lw100"><span>Организация:</span><div>'.$request['company_name'].'</div></div>'.
	'<div class="lw100"><span>Должность:</span><div>'.$request['post_name'].'</div></div></div>'.
	'</div><h5>'.$wait_action.':</h5><div class="block"><div class="lw100 name"><div>'.$action.'</div></div></td>'.
	'<td class="np"><h4>'.$gk_title.'</h4>'.
	($message['type'] == 4 
		? ''
		:'<ul class="gk">'.$gatekeepers.'</ul>'.(!empty($assistants) ? '<h5>Заместители:</h5><ul class="assist">'.$assistants.'</ul>' : '')
	).
	'</td></tr>';
*/
}#end function







/*
 * Возвращает контент для письма: сформированная строка для гейткипера
 */
function msgpool_content_gk123_request($message, $employer, $request){
	global $gk_roles;
	$title = '';
	$action = '';
	$gk_title='';
	$gatekeepers='';
	$gatekeepers='';
	$bg_class='';

	switch($message['type']){
		case 1:
			switch($message['gatekeeper_role']){
				case 1: $title = 'Согласование заявки'; $gk_title='Контакты сотрудников, согласующих заявку:'; break;
				case 2: $title = 'Утверждение заявки'; $gk_title='Контакты сотрудников, утверждающих заявку:'; break;
				case 3: $title = 'Исполнение заявки'; $gk_title='Контакты сотрудников, исполнителей:'; break;
				case 4: $title = 'Новая заявка'; $gk_title=''; break;
			}
			$action = $title;
			$gatekeepers = (empty($message['gatekeepers']) ? '' : msgpool_content_employers_list($message['gatekeepers']));
			$assistants = (empty($message['assistants']) ? '' : msgpool_content_employers_list($message['assistants']));
			$bg_class='normal';
		break;
		case 2:
			switch($message['gatekeeper_role']){
				case 1: $title = 'Заявка ранее поступившая Вам была согласована'; $gk_title = 'Сотрудник, согласовавший заявку:'; break;
				case 2: $title = 'Заявка ранее поступившая Вам была утверждена'; $gk_title = 'Сотрудник, утвердивший заявку:'; break;
				case 3: $title = 'Заявка ранее поступившая Вам была исполнена'; $gk_title = 'Сотрудник, исполнивший заявку:'; break;
			}
			$action = '<font color="green">'.$title.'</font>';
			$gatekeepers = (empty($message['gatekeeper_id']) ? '<li><span class="name">Администратор</span></li>' : msgpool_content_employers_list(array($message['gatekeeper_id'])));
			$bg_class='approve';
		break;
		case 3:
			$title = 'Заявка ранее поступившая Вам была отклонена';
			$action = '<font color="red">'.$title.'</font>';
			$gk_title = 'Сотрудник, отклонивший заявку:';
			$gatekeepers = (empty($message['gatekeeper_id']) ? '<li><span class="name">Администратор</span></li>' : msgpool_content_employers_list(array($message['gatekeeper_id'])));
			$bg_class='decline';
		break;
	}


	if(empty($title)||empty($gatekeepers)) return '';


	return
	'<tr><td width="100%" colspan="2" cellpadding="0" cellspacing="0" align="left" valign="top" class="'.$bg_class.'">'.
		'<table border="0" cellpadding="0" cellspacing="0" width="100%"><tr>'.
			'<td cellpadding="0" cellspacing="0" align="left" valign="top" class="'.$bg_class.'">'.
				'<table border="0" cellpadding="0" cellspacing="0" width="100%">'.
					'<tr><td class="h4">'.$title.'</td></tr>'.
					'<tr><td class="ilist" align="left" valign="top">'.
						'<table border="0" cellpadding="0" cellspacing="0" width="100%">'.
							'<tr><td width="120" class="k">Заявка №:</td><td class="v">'.$request['request_id'].'</td></tr>'.
							'<tr><td width="120" class="k">Тип заявки:</td><td class="v">'.($request['request_type']==3?'Блокировка доступа':'Запрос доступа').'</td></tr>'.
							'<tr><td width="120" class="k">Ресурс:</td><td class="v">'.$request['iresource_name'].'</td></tr>'.
							'<tr><td width="120" class="k">Время:</td><td class="v">'.$message['message_time'].'</td></tr>'.
							(empty($request['phone'])?'':'<tr><td width="120" class="k">Телефон:</td><td class="v">'.$request['phone'].'</td></tr>').
							(empty($request['email'])?'':'<tr><td width="120" class="k">E-mail:</td><td class="v"><a href="mailto:'.$request['email'].'">'.$request['email'].'</a></td></tr>').
							($message['type'] == 1 
								?($message['gatekeeper_role']!=4
									?'<tr><td width="120" class="k">&nbsp;</td><td height="40" align="left" valign="middle"><a class="button" href="'.Config::getOption('general','server_address','#').'/main/gatekeeper/requestinfo?request_id='.$request['request_id'].'&iresource_id='.$request['iresource_id'].'">Перейти к заявке</a></td></tr>'
									:'<tr><td width="120" class="k">&nbsp;</td><td height="40" align="left" valign="middle"><a class="button" href="'.Config::getOption('general','server_address','#').'/main/requests/view?request_id='.$request['request_id'].'&iresource_id='.$request['iresource_id'].'">Перейти к заявке</a></td></tr>'
								)
								:''
							).
						'</table>'.
					'</td></tr>'.
					'<tr><td class="h5">Заявитель (для кого запрошен доступ):</td></tr>'.
					'<tr><td class="ilist" align="left" valign="top">'.
						msgpool_content_request_employer($employer, $request, true).
					'</td></tr>'.
				'</table>'.
			'</td>'.
			($message['gatekeeper_role']!=4
				?'<td width="50%" cellpadding="0" cellspacing="0" align="left" valign="top" class="'.$bg_class.'">'.
					'<table border="0" cellpadding="0" cellspacing="0" width="100%">'.
						'<tr><td class="h4">'.$gk_title.'</td></tr>'.
							($message['type'] == 4 
								? ''
								: '<tr><td align="left" valign="top"><ul class="gk">'.$gatekeepers.'</ul></td></tr>'.(!empty($assistants) ? '<tr><td class="h5">Заместители:</td></tr><tr><td><ul class="assist">'.$assistants.'</ul></td></tr>' : '')
							).
					'</table>'.
				'</td>'
				:''
			).
		'</tr></table>'.
	'</td></tr>';

/*
	return
	'<tr class="'.$bg_class.'"><td class="np"><h4>'.$title.'</h4><div class="block">'.
	'<div class="lw100"><span>Заявка №:</span><div>'.$request['request_id'].'</div></div>'.
	'<div class="lw100"><span>Ресурс:</span><div class="name">'.$request['iresource_name'].'</div></div>'.
	'<div class="lw100"><span>Время:</span><div>'.$message['message_time'].'</div></div>'.
	($message['type'] == 1 
		?'<div class="lw100"><span>&nbsp;</span><div class="m50"><a class="button" href="'.Config::getOption('general','server_address','#').'/main/requests/view?request_id='.$request['request_id'].'&iresource_id='.$request['iresource_id'].'">Перейти к заявке</a></div></div>'
		:''
	).'</div>'.
	'<h5>Заявитель (для кого запрошен доступ):</h5>'.
	'<div class="block">'.
	'<div class="lw100 name"><span>Сотрудник:</span><div>'.$employer['search_name'].'</div></div>'.
	'<div class="lw100"><span>Организация:</span><div>'.$request['company_name'].'</div></div>'.
	'<div class="lw100"><span>Должность:</span><div>'.$request['post_name'].'</div></div>'.
	(empty($request['phone'])?'':'<div class="lw100"><span>Телефон:</span><div>'.$request['phone'].'</div></div>').
	(empty($request['email'])?'':'<div class="lw100"><span>E-Mail:</span><div><a href="mailto:'.$request['email'].'">'.$request['email'].'</a></div></div>').
	'</div>'.
	($message['type'] == 1 
		?'</div><h5>От Вас ожидается:</h5><div class="block"><div class="lw100 name"><div>'.$action.'</div></div></td>'
		:''
	).
	'<td class="np"><h4>'.$gk_title.'</h4>'.
	($message['type'] == 4 
		? ''
		:'<ul class="gk">'.$gatekeepers.'</ul>'.(!empty($assistants) ? '<h5>Заместители:</h5><ul class="assist">'.$assistants.'</ul>' : '')
	).
	'</td></tr>';
*/
}#end function










/***********************************************************************
 * ОСНОВНАЯ ЧАСТЬ
 ***********************************************************************/

$db = Database::getInstance('main');

$gk_roles = array(
	'1' => array(
		'type'	=> 'Согласование заявки',
		'gk'	=> 'Согласующий',
		'gks'	=> 'Согласующие сотрудники',
		'act'	=> 'согласована'
	),
	'2' => array(
		'type'	=> 'Утверждение заявки',
		'gk'	=> 'Утверждающий',
		'gks'	=> 'Утверждающие сотрудники',
		'act'	=> 'утверждена'
	),
	'3' => array(
		'type'	=> 'Исполнение заявки',
		'gk'	=> 'Исполнитель',
		'gks'	=> 'Назначенные исполнители',
		'act'	=> 'исполнена'
	),
	'4' => array(
		'type' => 'Ознакомление с заявкой',
		'gk'	=> 'Уведомляемый',
		'gks'	=> 'Уведомляемые',
		'act'	=> 'направлена для рассмотрения'
	),
);




$employers = array();
$employers_list = array();
$requests  = array();
$timestamp = date('Y-m-d H:i:s',time()-1);

#Старт транзакции
$db->transaction();


#Получение сообщений
$db->prepare('SELECT *,DATE_FORMAT(`timestamp`, "%d.%m.%Y %H:%i:%s") as `message_time` FROM `msg_pool` WHERE `timestamp` < ? ORDER BY `id`');
$db->bind($timestamp);
if( ($messages = $db->select()) === false){
	$db->rollback();
	die('DB ERROR: SELECT FROM `msg_pool`');
}
if(empty($messages)){
	$db->rollback();
	die('EMPTY `msg_pool`');
}


#Просмотр сообщений, получение информации о заявках, подготовка списка сотрудников
foreach($messages as $indx=>$message){

	$message['gatekeepers'] = $messages[$indx]['gatekeepers'] = empty($message['gatekeepers']) ? array() : explode(',',$message['gatekeepers']);
	$message['assistants'] = $messages[$indx]['assistants'] = empty($message['assistants']) ? array() : explode(',',$message['assistants']);

	#Получение информации по заявкам
	if(empty($requests[$message['request_id'].'-'.$message['iresource_id']])){
		$requests[$message['request_id'].'-'.$message['iresource_id']] = msgpool_request_info($message['request_id'], $message['iresource_id']);
	}
	$request = $requests[$message['request_id'].'-'.$message['iresource_id']];
	if(!is_array($request)) continue;

	#Подготовка списка сотрудников
	if($request['employer_id']>0) array_push($employers_list, $request['employer_id']);
	if($request['curator_id']>0) array_push($employers_list, $request['curator_id']);
	if(!empty($message['gatekeepers'])) $employers_list = array_merge($employers_list, $message['gatekeepers']);
	if(!empty($message['assistants'])) $employers_list = array_merge($employers_list, $message['assistants']);
	if($message['gatekeeper_id']>0) array_push($employers_list, $message['gatekeeper_id']);

}#Просмотр сообщений, получение информации о заявках, подготовка списка сотрудников



$employers_list = array_unique($employers_list);
if(empty($employers_list)){
	$db->rollback();
	die('employers_list is empty');
}



#Получение списка сотрудников
$employers = msgpool_employers_info($employers_list);
if(empty($employers)){
	$db->rollback();
	die('employers is empty');
}

#Проход по списку сотрудников, создание структуры хранения контента сообщений
foreach($employers as $employer_id=>$employer){

	$employers[$employer_id]['content'] = array(
		'me_req'		=> array(),	//Заявки сотрудника
		'curator_req'	=> array(),	//Заявки куратора
		'gk_type1'		=> array(),	//Заявки для согласования
		'gk_type2'		=> array(),	//Заявки для утверждения
		'gk_type3'		=> array(),	//Заявки для исполнения
		'gk_type4'		=> array()	//Заявки для просмотра
	);

}#Проход по списку сотрудников, создание структуры хранения контента сообщений



#Просмотр сообщений: подготовка контента писем для сотрудников
foreach($messages as $indx=>$message){

	#Заявка
	$request = empty($requests[$message['request_id'].'-'.$message['iresource_id']])?null:$requests[$message['request_id'].'-'.$message['iresource_id']];
	if(empty($request)||!is_array($request)) continue;


	#Типы сообщений:1,2,3,4
	if($message['type']>0&&$message['type']<5 && !empty($employers[$request['employer_id']])){
		#Тип шага - не уведомление: подготавливаем контент для заявителя и куратора
		if($message['gatekeeper_role']!=4){
			//Для заявителя
			if($message['send_employer']&&$message['type']!=2){
				$str = msgpool_content_me_request($message, $employers[$request['employer_id']], $request);
				if(!empty($str)) $employers[$request['employer_id']]['content']['me_req'][] = $str;
			}
			//Для куратора заявителя
			if($message['send_curator']&&$request['curator_id']>0 && $request['curator_id']!=$request['employer_id'] && !empty($employers[$request['curator_id']])){
				$str = msgpool_content_carator_request($message, $employers[$request['employer_id']], $request);
				if(!empty($str)) $employers[$request['curator_id']]['content']['curator_req'][] = $str;
			}
		}#Тип шага - не уведомление: подготавливаем контент для заявителя и куратора
	}#Типы сообщений:1,2,3,4




	#Типы сообщений:1,2,3
	if($message['send_gatekeepers']&&$message['type']>0&&$message['type']<4&&!empty($employers[$request['employer_id']])){

		//Для гейткиперов
		if($message['send_assistants']){
			$gatekeepers_ids = ($message['gatekeeper_role']!=4 ? array_unique(array_merge($message['gatekeepers'], $message['assistants'])) : $message['gatekeepers']);
		}else{
			$gatekeepers_ids = array_unique($message['gatekeepers']);
		}

		foreach($gatekeepers_ids as $gatekeeper_id){
			if(empty($employers[$gatekeeper_id])) continue;
			//if(($message['type']==2||$message['type']==3)&&$gatekeeper_id==$message['gatekeeper_id']) continue;
			$str = msgpool_content_gk123_request($message, $employers[$request['employer_id']], $request);
			if(!empty($str)) $employers[$gatekeeper_id]['content']['gk_type'.$message['gatekeeper_role']][] = $str;
		}

	}#Типы сообщений:1,2,3






}#Просмотр сообщений: подготовка контента писем для сотрудников


$send_content = array();


#Итоговые сообщения для сотрудников
foreach($employers as $employer_id=>$employer){

	#Адрес электронной почты не задан или задан некорректно
	if(empty($employer['email']) || !preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $employer['email'])) continue;

	$content = '';
	$content_count = 0;

	#Просмотр контента
	foreach($employer['content'] as $ctype=>$carray){

		#Нет контента для отправки
		if(empty($carray)) continue;

		#Настройками учетной записи сотрудника запрещено получение контента данного типа
		if($ctype=='me_req' && empty($employer['notice_me_requests'])) continue;
		if($ctype=='curator_req' && empty($employer['notice_curator_requests'])) continue;
		if($ctype=='gk_type1' && empty($employer['notice_gkemail_1'])) continue;
		if($ctype=='gk_type2' && empty($employer['notice_gkemail_2'])) continue;
		if($ctype=='gk_type3' && empty($employer['notice_gkemail_3'])) continue;
		if($ctype=='gk_type4' && empty($employer['notice_gkemail_4'])) continue;

		$table_title = '';
		$table_css_class = $ctype;
		$table_headers = '';

		switch($ctype){
			case 'me_req':
				$table_title = 'Изменения в Ваших заявках';
				$table_headers = '<th width="50%">Информация по заявке</th><th width="50%">Ответственные сотрудники</th>';
			break;

			case 'curator_req':
				$table_title = 'Изменения в заявках, оформленных Вами для других сотрудников';
				$table_headers = '<th width="50%">Информация по заявке</th><th width="50%">Ответственные сотрудники</th>';
			break;

			case 'gk_type1':
				$table_title = 'Заявки, поступившие Вам на согласование';
				$table_headers = '<th width="50%">Информация по заявке</th><th width="450%">Ответственные сотрудники</th>';
			break;

			case 'gk_type2':
				$table_title = 'Заявки, поступившие Вам на утверждение';
				$table_headers = '<th width="50%">Информация по заявке</th><th width="50%">Ответственные сотрудники</th>';
			break;

			case 'gk_type3':
				$table_title = 'Заявки, поступившие Вам на исполнение';
				$table_headers = '<th width="50%">Информация по заявке</th><th width="50%">Ответственные сотрудники</th>';
			break;

			case 'gk_type4':
				$table_title = 'Уведомление о новых заявках сотрудников';
				$table_headers = '<th width="100%">Информация по заявке</th>';
			break;

		}

		$table_records = implode('',$carray);

		$content_count++;
		$content.='<h2>'.$table_title.'</h2><table class="'.$table_css_class.'" cellspacing="1" cellpadding="1" border="1" bordercolor="#B3AE98" width="100%"><thead><tr>'.$table_headers.'</tr></thead><tbody>'.$table_records.'</tbody></table>';

	}#Просмотр контента


	if($content_count > 0){
		$send_content[] = array(
			'email'		=> $employer['email'],
			'name'		=> $employer['search_name'],
			'content'	=> $content
		);
	}


}#Итоговые сообщения для сотрудников


$template = Template::getInstance('mail');
$template->setTemplate('Main/templates/mail/mail.php');

$MAIL_SUBJECT = 'Изменения в заявках (Каскад)';

foreach($send_content as $content){
	$template->assign(array(
		'MAIL_SUBJECT'		=> $MAIL_SUBJECT,
		'MEMBER_LINK'		=> Config::getOption('general','server_address','#'),
		'EMPLOYER_NAME' 	=> $content['name'],
		'EMPLOYER_EMAIL'	=> $content['email'],
		'CONTENT'			=> $content['content']
	));
	$output = str_replace(array("\r","\n","\t"),array('','',''),$template->display(true));
	$db->prepare('INSERT INTO `mail` (`timestamp`,`mail_to`,`subject`,`headers`,`content`) VALUES(?,?,?,?,?)');
	$db->bind($timestamp);
	$db->bind($content['email']);
	$db->bind($MAIL_SUBJECT);
	$db->bind('');
	$db->bind($output);
	if($db->insert() === false){
		$db->rollback();
		die('DB ERROR: INSERT TO `mail`');
	}
}


$db->prepare('DELETE FROM `msg_pool` WHERE `timestamp` < ?');
$db->bind($timestamp);
if($db->delete() === false){
	$db->rollback();
	die('DB ERROR: DELETE FROM `msg_pool`');
}


#Успех
LABEL_COMPLETE:
$db->commit();
die('complete');
?>