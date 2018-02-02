<?php
/*==================================================================================================
Описание: company.functions.php
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');




#------------------------------------------------------------------
#Информация об организации
function company_info($data=array(), $db=null){
	if(!$db) $db = Database::getInstance('main');
	$company_id = (empty($data['company_id']) ? 0 : intval($data['company_id']));
	return $db->selectRecord('SELECT * FROM `companies` WHERE `company_id`='.$company_id.' LIMIT 1');	
}#end function




#------------------------------------------------------------------
#Список информационных ресурсов, доступных в указанной организации
function company_iresources($data=array(), $db=null){

	if(!$db) $db = Database::getInstance('main');
	$company_id = (empty($data['company_id']) ? 0 : intval($data['company_id']));
	if(!$company_id) return false;
	
	#Запрос списка информационных ресурсов, доступных сотруднику данной организации
	$db->prepare('
		SELECT 
			IR.`iresource_id` as `iresource_id`,
			IR.`full_name` as `full_name`,
			IR.`description` as `description`,
			IR.`iresource_group` as `igroup_id`
		FROM `iresource_companies` as CIR
		INNER JOIN `iresources` as IR ON IR.`iresource_id`= CIR.`iresource_id` AND IR.`is_lock`=0
		WHERE CIR.`company_id` IN(0,?)
	');
	$db->bind($company_id);

	return $db->select(null, MYSQL_ASSOC);
}#end function



#------------------------------------------------------------------
#Список групп информационных ресурсов, доступных в указанной организации
function company_iresources_groups($company_id=0, $db=null){

	if(!$db) $db = Database::getInstance('main');
	$company_id = intval($company_id);
	if(!$company_id) return false;
	
	#Запрос списка групп информационных ресурсов, доступных сотруднику данной организации
	$db->prepare('
		SELECT 
			DISTINCT IG.`igroup_id` as `igroup_id`,
			IG.`full_name` as `full_name`
		FROM `iresources` as IR
			INNER JOIN `iresource_companies` as CIR ON IR.`iresource_id`= CIR.`iresource_id` AND CIR.`company_id` IN(0,?)
			INNER JOIN `iresource_groups` as IG ON IG.`igroup_id`= IR.`iresource_group`
		WHERE IR.`is_lock`=0
	');
	$db->bind($company_id);

	return $db->select(null, MYSQL_ASSOC);
}#end function




#------------------------------------------------------------------
#Проверяет, разрешен ли сотрудникам компании $company_id доступ к информационному ресурсу $iresource_id
function company_allowed($data=array(), $db=null){

	if(!$db) $db = Database::getInstance('main');
	$company_id = (empty($data['company_id']) ? 0 : intval($data['company_id']));
	$iresource_id  = (empty($data['iresource_id']) ? 0 : intval($data['iresource_id']));
	if(!$company_id || !$iresource_id) return false;


	#Запрос списка информационных ресурсов, доступных сотруднику данной организации
	$db->prepare('
		SELECT count(*)
		FROM `iresource_companies` as CIR
		INNER JOIN `iresources` as IR ON IR.`iresource_id`= CIR.`iresource_id` AND IR.`is_lock`=0
		WHERE CIR.`company_id`IN(0,?) AND CIR.`iresource_id`=?
	');
	$db->bind($company_id);
	$db->bind($iresource_id);

	return ($db->result() == 1 ? true : false);
}#end function




#------------------------------------------------------------------
#Информация о руководителях организации
function company_director($data=array(), $db=null){

	if(!$db) $db = Database::getInstance('main');
	$company_id = (empty($data['company_id']) ? 0 : intval($data['company_id']));
	if(empty($company_id)) return false;
	$as_list = empty($data['as_list']) ? false : true;

	$db->prepare('
		SELECT
			EMP.`employer_id` as `employer_id`,
			EMP.`search_name` as `employer_name`,
			EP.`post_from` as `post_from`,
			EP.`post_to` as `post_to`,
			CP.`post_uid` as `post_uid`,
			CP.`post_id` as `post_id`,
			P.`full_name` as `post_name`,
			CP.`company_id` as `company_id`,
			C.`full_name` as `company_name`
		FROM 
			`company_posts` as CP
		INNER JOIN `companies` as C ON C.`company_id` = CP.`company_id`
		INNER JOIN `employer_posts` as EP ON EP.`post_uid`=CP.`post_uid` AND EP.`post_from`<=? AND EP.`post_to`>=?
		INNER JOIN `posts` as P ON P.`post_id` = CP.`post_id`
		INNER JOIN `employers` as EMP ON EMP.`employer_id` = EP.`employer_id` AND EMP.`status`>0
		WHERE 
			CP.`company_id`=? AND
			CP.`boss_uid` = 0
	');
	$db->bind(date('Y-m-d'));
	$db->bind(date('Y-m-d'));
	$db->bind($company_id);

	return ($as_list ? $db->selectFromField('employer_id') : $db->select());
}#end function



?>