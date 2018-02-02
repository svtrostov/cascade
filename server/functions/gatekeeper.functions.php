<?php
/*==================================================================================================
Описание: gatekeeper.functions.php
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');




#------------------------------------------------------------------
#Функция возвращает список заявок, в которых сотрудник играет роль гейткипера
function gatekeeper_requests($data=array(), $db=null){

	if(!$db) $db = Database::getInstance('main');
	$gatekeeper_id = (empty($data['gatekeeper_id']) ? User::_getEmployerID() : intval($data['gatekeeper_id']));
	$gatekeeper_groups = (empty($data['employer_groups'])||!is_array($data['employer_groups']) ? employer_groups(array('employer_id'=>$employer_id)) : $data['employer_groups']);
	if(empty($gatekeeper_id)) return false;

	$db->prepare('
		SELECT 
			*
		FROM(
			SELECT 
				ROUT.`route_id` as `route_id`,
				( IF(PARAM.`for_user`>0 AND PARAM.`for_user`=?, 20 ,0) + 
					IF(PARAM.`for_post`>0 AND PARAM.`for_post`=?, 10 ,0) + 
					IF(PARAM.`for_group`>0 AND PARAM.`for_group` IN ('.implode(',',$employer_groups).'), 5 ,0) + 
					IF(PARAM.`for_resource`>0 AND PARAM.`for_resource` IN ('.implode(',',$ir_list).'), 3 ,0) + 
					IF(PARAM.`for_company`>0 AND PARAM.`for_company`=?, 1 ,0)
				) as `weight`,
				PARAM.`for_user` as `for_user`,
				PARAM.`for_post` as `for_post`,
				PARAM.`for_group` as `for_group`,
				PARAM.`for_resource` as `for_resource`,
				PARAM.`for_company` as `for_company`,
				ROUT.`priority` as `priority`,
				ROUT.`full_name` as `full_name`,
				ROUT.`description` as `description`,
				ROUT.`is_default` as `is_default`
			FROM 
				`route_params` as PARAM
			INNER JOIN `routes` as ROUT ON ROUT.`route_id`=PARAM.`route_id` AND ROUT.`is_lock`=0 AND ROUT.`no_autostart`=0 AND ROUT.`is_template`=?
			WHERE
				(PARAM.`for_user`=0 OR (PARAM.`for_user`>0 AND PARAM.`for_user`=?)) AND
				(PARAM.`for_post`=0 OR (PARAM.`for_post`>0 AND PARAM.`for_post`=?)) AND
				(PARAM.`for_group`=0 OR (PARAM.`for_group`>0 AND PARAM.`for_group` IN ('.implode(',',$employer_groups).'))) AND
				(PARAM.`for_resource`=0 OR (PARAM.`for_resource`>0 AND PARAM.`for_resource` IN ('.implode(',',$ir_list).'))) AND
				(PARAM.`for_company`=0 OR (PARAM.`for_company`>0 AND PARAM.`for_company`=?))
		) as `RT`
		WHERE RT.`weight` > 0 || RT.`is_default`=1
		ORDER BY RT.`priority` DESC, RT.`weight` DESC
		'.($is_single==true ? 'LIMIT 1':'').'
	');
	$db->bind($employer_id);
	$db->bind($post_uid);
	$db->bind($company_id);
	$db->bind($is_template);
	$db->bind($employer_id);
	$db->bind($post_uid);
	$db->bind($company_id);
	if($is_single == true){
		if(($all_routes = $db->selectRecord()) === false) return false;
	}else{
		if(($all_routes = $db->select()) === false) return false;
	}

	return $all_routes;
}#end function




?>