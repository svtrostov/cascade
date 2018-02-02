<?php
/*==================================================================================================
Описание: employer.functions.php
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');




#------------------------------------------------------------------
#Информация о сотрудникие
function employer_info($data=array(), $db=null){

	if(!$db) $db = Database::getInstance('main');
	$employer_id = (empty($data['employer_id']) ? User::_getEmployerID() : intval($data['employer_id']));
	if(!$employer_id) return false;
	
	$employer_id = intval($data['employer_id']);
	
	if( ($employer_info = $db->select_one('SELECT * FROM `employers` WHERE `employer_id`='.$employer_id.' LIMIT 1',MYSQL_ASSOC)) === false) return false;
	
	return $employer_info;
	
}#end function





#------------------------------------------------------------------
#Список должностей сотрудника
function employer_post_list($data=array(), $db=null){
	
	if(!$db) $db = Database::getInstance('main');
	$employer_id = (empty($data['employer_id']) ? User::_getEmployerID() : intval($data['employer_id']));
	if(!$employer_id) return false;
	
	$db->prepare('
		SELECT 
			EP.`id` AS `id`,
			EP.`employer_id` AS `employer_id`,
			EP.`post_uid` AS `post_uid`,
			DATE_FORMAT(EP.`post_from`,"%d.%m.%Y") as `post_from`,
			DATE_FORMAT(EP.`post_to`,"%d.%m.%Y") as `post_to`,
			C.`company_id` AS `company_id`,
			C.`full_name` AS `company_name`,
			P.`post_id` AS `post_id`,
			P.`full_name` AS `post_name`,
			CP.`boss_uid` AS `boss_uid`,
			CPBOSS.`post_id` AS `boss_post_id`,
			PBOSS.`full_name` AS `boss_post_name`
		FROM `employer_posts` as EP
			INNER JOIN `company_posts` as CP ON EP.`post_uid` = CP.`post_uid`
			INNER JOIN `companies` as C ON C.`company_id` = CP.`company_id`
			INNER JOIN `posts` as P ON P.`post_id` = CP.`post_id`
			LEFT JOIN `company_posts` as CPBOSS ON CPBOSS.`post_uid` = CP.`boss_uid`
			LEFT JOIN `posts` as PBOSS ON PBOSS.`post_id` = CPBOSS.`post_id`
		WHERE EP.`employer_id`=? AND EP.`post_from` <= ? AND EP.`post_to` >= ?
	');
	$db->bind($employer_id);
	$db->bind(date('Y-m-d'));
	$db->bind(date('Y-m-d'));
	
	if(($results = $db->select()) === false)return false;
	

	//Просмотр должностей, поиск руководителей
	for($i=0; $i<count($results);$i++){
		if($results[$i]['boss_uid'] == 0){
			$results[$i]['bosses'] = array();
			continue;
		}
		$db->prepare('
			SELECT 
				EP.`employer_id` AS `employer_id`,
				DATE_FORMAT(EP.`post_from`,"%d.%m.%Y") as `post_from`,
				DATE_FORMAT(EP.`post_to`,"%d.%m.%Y") as `post_to`,
				EMP.`search_name` as `search_name`
			FROM `employer_posts` as EP
				INNER JOIN `employers` as EMP ON EMP.`employer_id`= EP.`employer_id` AND EMP.`status` > 0
			WHERE EP.`post_uid`=? AND EP.`post_from` <= ? AND EP.`post_to` >= ?
		');
		$db->bind($results[$i]['boss_uid']);
		$db->bind(date('Y-m-d'));
		$db->bind(date('Y-m-d'));
		if( ($bosses = $db->select()) === false) return false;
		$results[$i]['bosses'] = $bosses;
		
	}//Просмотр должностей, поиск руководителей

	#Выполнено успешно
	return $results;

}#end function






#------------------------------------------------------------------
#Информация о должности сотрудника
function employer_post_info($data=array(), $db=null){

	if(!$db) $db = Database::getInstance('main');
	$employer_id = (empty($data['employer_id']) ? User::_getEmployerID() : ($data['employer_id'] == 'all' ? 0 :intval($data['employer_id'])));
	$as_list = empty($data['as_list']) ? false : true;
	$post_uid = $data['post_uid'];

	#Проверка существования указанного сотрудника на выбранной должности
	$db->prepare('
		SELECT
			EP.`employer_id` as `employer_id`,
			EP.`post_from` as `post_from`,
			EP.`post_to` as `post_to`,
			CP.`post_uid` as `post_uid`,
			CP.`boss_uid` as `boss_uid`,
			CP.`post_id` as `post_id`,
			P.`full_name` as `post_name`,
			CP.`boss_id` as `boss_id`,
			CP.`company_id` as `company_id`,
			C.`full_name` as `company_name`
		FROM 
			`employer_posts` as EP
		INNER JOIN `company_posts` as CP ON CP.`post_uid` = EP.`post_uid`
		INNER JOIN `companies` as C ON C.`company_id` = CP.`company_id`
		INNER JOIN `posts` as P ON P.`post_id` = CP.`post_id`
		'.($employer_id > 0 ? '' : 'INNER JOIN `employers` as EMP ON EMP.`employer_id` = EP.`employer_id` AND EMP.`status`>0').'
		WHERE 
			'.($employer_id>0 ? 'EP.`employer_id`=? AND ' : '').'
			EP.`post_uid`=? AND 
			EP.`post_from`<=? AND 
			EP.`post_to`>=? 
			'.($employer_id>0 ? 'LIMIT 1 ' : '').'
	');
	if($employer_id>0) $db->bind($employer_id);
	$db->bind($post_uid);
	$db->bind(date('Y-m-d'));
	$db->bind(date('Y-m-d'));
	
	if($employer_id > 0) return ($as_list ? $db->selectFromField('employer_id') : $db->selectRecord());

	return ($as_list ? $db->selectFromField('employer_id') : $db->select());
}#end function




#------------------------------------------------------------------
#Группы в которые включен сотрудник
function employer_groups($data=array(), $db=null){

	if(!$db) $db = Database::getInstance('main');
	$employer_id = (empty($data['employer_id']) ? User::_getEmployerID() : intval($data['employer_id']));
	if(!$employer_id) return false;

	$employer_id = intval($data['employer_id']);
	$full_info = !empty($data['full_info']) ? true : false;

	if(!$full_info){
		if( ($groups = $db->selectFromField('group_id','SELECT `group_id` FROM `employer_groups` WHERE `employer_id`='.$employer_id,MYSQL_ASSOC)) === false) return false;
		return $groups;
	}
	
	$db->prepare('
		SELECT 
			GRP.`group_id` as `group_id`,
			GRP.`full_name` as `full_name`
		FROM `employer_groups` as EG
		INNER JOIN `groups` as GRP ON GRP.`group_id`=EG.`group_id`
		WHERE
			EG.`employer_id`=? 
	');
	$db->bind($employer_id);
	if( ($groups = $db->select()) === false) return false;
	
	return $employer_info;
	
}#end function




#------------------------------------------------------------------
#Информация об ассистентах сотрудника
function employer_assistants($data=array(), $db=null){

	if(!$db) $db = Database::getInstance('main');
	$employer_id = (empty($data['employer_id']) ? User::_getEmployerID() : intval($data['employer_id']));
	if(!$employer_id) return false;
	
	$employer_id = intval($data['employer_id']);
	$from_date = (empty($data['from_date']) ? date('Y-m-d') : $data['from_date']);
	$to_date = (empty($data['to_date']) ? date('Y-m-d') : $data['to_date']);
	$as_list = empty($data['as_list']) ? false : true;

	if(!$as_list){

		$db->prepare('
			SELECT 
				ASSIST.`assistant_id` as `employer_id`,
				EMP.`search_name` as `employer_name`
			FROM 
				`assistants` as ASSIST
			INNER JOIN `employers` as EMP ON EMP.`employer_id`=ASSIST.`assistant_id` AND EMP.`status` > 0
			WHERE 
				ASSIST.`employer_id` = ? AND
				ASSIST.`from_date` <= ? AND
				ASSIST.`to_date` >= ?
		');
		$db->bind($employer_id);
		$db->bind($from_date);
		$db->bind($to_date);
		if( ($assistants = $db->select()) === false) return false;
	
	}else{
		$db->prepare('
			SELECT 
				ASSIST.`assistant_id` as `assistant_id`,
			FROM 
				`assistants` as ASSIST
			INNER JOIN `employers` as EMP ON EMP.`employer_id`=ASSIST.`assistant_id` AND EMP.`status` > 0
			WHERE 
				ASSIST.`employer_id` = ? AND
				ASSIST.`from_date` <= ? AND
				ASSIST.`to_date` >= ?
		');
		$db->bind($employer_id);
		$db->bind($from_date);
		$db->bind($to_date);
		if( ($assistants = $db->selectFromField('assistant_id')) === false) return array();
	}

	return $assistants;
}#end function




#------------------------------------------------------------------
#Информация чьим ассистентом является сотрудник
function employer_delegated($data=array(), $db=null){

	if(!$db) $db = Database::getInstance('main');
	$employer_id = (empty($data['employer_id']) ? User::_getEmployerID() : intval($data['employer_id']));
	if(!$employer_id) return false;
	$from_date = (empty($data['from_date']) ? date('Y-m-d') : $data['from_date']);
	$to_date = (empty($data['to_date']) ? date('Y-m-d') : $data['to_date']);
	$as_list = empty($data['as_list']) ? false : true;

	$db->prepare('
		SELECT 
			ASSIST.`employer_id` as `employer_id`,
			EMP.`search_name` as `employer_name`
		FROM 
			`assistants` as ASSIST
		INNER JOIN `employers` as EMP ON EMP.`employer_id`=ASSIST.`employer_id` AND EMP.`status` > 0
		WHERE 
			ASSIST.`assistant_id` = ? AND
			ASSIST.`from_date` <= ? AND
			ASSIST.`to_date` >= ?
	');
	$db->bind($employer_id);
	$db->bind($from_date);
	$db->bind($to_date);

	return ($as_list ? $db->selectFromField('employer_id') : $db->select());
}#end function





#------------------------------------------------------------------
#Информация о должностях сотрудника, возвращает список post_uid
function employer_posts($data=array(), $db=null){

	if(!$db) $db = Database::getInstance('main');
	$employer_id = (empty($data['employer_id']) ? User::_getEmployerID() : intval($data['employer_id']));
	if(!$employer_id) return false;
	$from_date = (empty($data['from_date']) ? date('Y-m-d') : $data['from_date']);
	$to_date = (empty($data['to_date']) ? date('Y-m-d') : $data['to_date']);
	$as_list = empty($data['as_list']) ? false : true;

	$db->prepare('SELECT `post_uid` FROM `employer_posts` WHERE `employer_id` = ? AND `post_from` <= ? AND `post_to` >= ?');
	$db->bind($employer_id);
	$db->bind($from_date);
	$db->bind($to_date);

	return $db->selectFromField('post_uid');
}#end function






#------------------------------------------------------------------
#Информация об организациях, где сотрудник занимает должность руководителя организации
function employer_directors($data=array(), $db=null){

	if(!$db) $db = Database::getInstance('main');
	$employer_id = (empty($data['employer_id']) ? User::_getEmployerID() : intval($data['employer_id']));
	if(!$employer_id) return false;
	$from_date = (empty($data['from_date']) ? date('Y-m-d') : $data['from_date']);
	$to_date = (empty($data['to_date']) ? date('Y-m-d') : $data['to_date']);
	$as_list = empty($data['as_list']) ? false : true;

	$db->prepare('SELECT `post_uid` FROM `employer_posts` WHERE `employer_id` = ? AND `post_from` <= ? AND `post_to` >= ?');
	$db->bind($employer_id);
	$db->bind($from_date);
	$db->bind($to_date);

	return $db->selectFromField('post_uid');
}#end function



?>