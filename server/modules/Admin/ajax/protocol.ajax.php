<?php
/*==================================================================================================
Описание: Обработка AJAX запросов
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');

if(!$uaccess->checkAccess('admin.protocol.moderate', 0)){
	return Ajax::_responseError('Ошибка выполнения','Недостаточно прав для работы с протоколом действий пользователей');
}

$request_action = Request::_get('action');

#Обработка AJAX запроса, в зависимости от запрошенного действия
switch($request_action){


	/*******************************************************************
	 * Поиск записей протокола
	 ******************************************************************/
	case 'protocol.search':

		$company_id = $request->getId('company_id',0);
		$employer_id = $request->getId('employer_id',0);
		$action_name = $request->getStr('action_name','all');
		$acl_name = $request->getStr('acl_name','all');
		$object_type = $request->getStr('object_type','all');
		$object_id = $request->getId('object_id',0);
		$date_from = $request->getDate('date_from',date('d.m.Y'));
		$date_to = $request->getDate('date_to',date('d.m.Y'));
		$limit = $request->getId('limit',100);

		$conds = array();

		if(!empty($company_id)&&$company_id!='all') $conds['company_id'] = $company_id;
		if(!empty($employer_id)&&$employer_id!='all') $conds['employer_id'] = $employer_id;
		if(!empty($action_name)&&$action_name!='all') $conds['action_name'] = $action_name;
		if(!empty($acl_name)&&$acl_name!='all') $conds['acl_name'] = $acl_name;
		if(!empty($object_type)&&$object_type!='all'){
			$object_type_id = is_numeric($object_type)? $object_type : Protocol::_getObjectTypeID($object_type);
			if(!empty($object_type_id)) $conds['object_type'] = $object_type;
		}
		if(!empty($object_id)&&$object_id!='all') $conds['object_id'] = $object_id;


		$db = Database::getInstance('main');

		$sql_select = '
			SELECT
				PE.`action_id` as `action_id`,
				PE.`action_name` as `action_name`,
				PE.`employer_id` as `employer_id`,
				PE.`session_uid` as `session_uid`,
				PE.`timestamp` as `timestamp`,
				PE.`company_id` as `company_id`,
				PE.`acl_name` as `acl_name`,
				PE.`primary_type` as `primary_type`,
				PE.`primary_id` as `primary_id`,
				PE.`secondary_type` as `secondary_type`,
				PE.`secondary_id` as `secondary_id`,
				PE.`description` as `description`,
				EMP.`search_name` as `employer_name`,
				IFNULL(C.`full_name`,"---") as `company_name`
			FROM `protocol_actions` as PE 
			INNER JOIN `employers` as EMP ON EMP.`employer_id` = PE.`employer_id`
			LEFT JOIN `companies` as C ON C.`company_id` = PE.`company_id`
		';

		$sql_where = 'WHERE PE.`timestamp` BETWEEN "'.date2sql($date_from).' 00:00:00" AND "'.date2sql($date_to).' 23:59:59"';


		foreach($conds as $key=>$value){
			if(empty($value)) continue;
			switch($key){

				case 'company_id':
					$sql_where.=' AND PE.`company_id`='.$value;
				break;

				case 'employer_id':
					$sql_where.='  AND  PE.`employer_id`='.$value;
				break;

				case 'action_name':
					$sql_where.='  AND PE.`action_name` LIKE "'.$db->getQuotedValue($value, false).'%"';
				break;

				case 'acl_name':
					$sql_where.=' AND PE.`acl_name` LIKE "'.$db->getQuotedValue($value, false).'%"';
				break;

				case 'object_type':
					$sql_where.=' AND PE.`primary_type` LIKE "'.$db->getQuotedValue($value, false).'%" OR PE.`secondary_type` LIKE "'.$db->getQuotedValue($value, false).'%"';
				break;

				case 'object_id':
					$sql_where.=' AND PE.`primary_id`='.$value.' OR PE.`secondary_id`='.$value;
				break;

			}//switch
		}//foreach

		$sql =
			$sql_select."\n\t".
			$sql_where."\n\t".
			' ORDER BY PE.`timestamp` DESC '.
			' LIMIT '.$limit;


		#Выполнено успешно
		return Ajax::_setData(array(
			'protocol' => $db->select($sql)
		));

	break; #Поиск записей протокола






	/*******************************************************************
	 * Получение информации о сессии
	 ******************************************************************/
	case 'session.info':

		$session_id = $request->getId('session_id',0);
		if(empty($session_id)) return Ajax::_responseError('Ошибка выполнения','Не задан идентификатор сессии');

		$db = Database::getInstance('main');

		$db->prepare('SELECT ALOG.*,DATE_FORMAT(ALOG.`login_time`,"%d.%m.%Y %H:%i:%s") as `login_time` FROM `employer_authlog` as ALOG WHERE ALOG.`session_uid`=? LIMIT 1');
		$db->bind($session_id);
		$info = $db->selectRecord();
		if(empty($info)) return Ajax::_responseError('Ошибка выполнения','Не найдена информация по сессии ID:'.$session_id);

		#Выполнено успешно
		return Ajax::_setData(array(
			'session' => $info
		));

	break; #Получение информации о сессии





	default:
	Ajax::_responseError('/main/ajax/protocol','Не найден обработчик для: '.$request_action);


}

?>