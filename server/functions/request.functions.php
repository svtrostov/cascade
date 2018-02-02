<?php
/*==================================================================================================
Описание: request.functions.php
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');


require_once(DIR_FUNCTIONS.'/employer.functions.php');
require_once(DIR_FUNCTIONS.'/iresource.functions.php');
require_once(DIR_FUNCTIONS.'/route.functions.php');


#------------------------------------------------------------------
#Выбор оптимального маршрута согласования заявки
function request_route_select($data=array(), $db=null){

	if(!$db) $db = Database::getInstance('main');
	$employer_id = (empty($data['employer_id']) ? User::_getEmployerID() : intval($data['employer_id']));
	$ir_list = (empty($data['ir_list'])||!is_array($data['ir_list']) ? false : $data['ir_list']);
	$company_id = (empty($data['company_id']) ? 0 : intval($data['company_id']));
	$post_uid = (empty($data['post_uid']) ? false : $data['post_uid']);
	if(empty($employer_id) || empty($ir_list) || empty($company_id) || empty($post_uid)) return false;
	$employer_groups = (empty($data['employer_groups'])||!is_array($data['employer_groups']) ? employer_groups(array('employer_id'=>$employer_id)) : $data['employer_groups']);
	$is_template = (empty($data['is_template']) ? 0 : ($data['is_template'] == 1 ? 1 : 0));
	$is_single = (empty($data['is_single']) ? false : true);
	if(empty($employer_groups)) $employer_groups = array(0);

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






#------------------------------------------------------------------
#Создание заявки
function request_new($data=array(), $db=null){

	if(!$db) $db = Database::getInstance('main');
	$request_type = (empty($data['request_type']) ? 2 : intval($data['request_type']));
	$employer_id = (empty($data['employer_id']) ? User::_getEmployerID() : intval($data['employer_id']));
	$curator_id = (empty($data['curator_id']) ? $employer_id : intval($data['curator_id']));
	$company_id = (empty($data['company_id']) ? 0 : intval($data['company_id']));
	$post_uid = (empty($data['post_uid']) ? false : $data['post_uid']);
	$template_id = (empty($data['template_id']) ? 0 : intval($data['template_id']));
	$phone = (empty($data['phone']) ? User::_get('phone') : $data['phone']);
	$email = (empty($data['email']) ? User::_get('email') : $data['email']);
	if(empty($employer_id) || empty($company_id) || empty($post_uid)) return false;

	#Создание заявки
	$db->prepare('INSERT INTO `requests` (`request_type`,`curator_id`,`employer_id`,`company_id`,`post_uid`,`template_id`,`timestamp`,`phone`,`email`) VALUES (?,?,?,?,?,?,?,?,?)');
	$db->bind(2);
	$db->bind(0);
	$db->bind($employer_id);
	$db->bind($company_id);
	$db->bind($post_uid);
	$db->bind($template_id);
	$db->bind(date("Y-m-d H:i:s"));
	$db->bind($phone);
	$db->bind($email);

	return intval($db->insert());
}#end function




#------------------------------------------------------------------
#Добавление в заявку информационного ресурса
function request_add_iresource($data=array(), $db=null){

	if(!$db) $db = Database::getInstance('main');
	$request_id = (empty($data['request_id']) ? 0 : intval($data['request_id']));
	$iresource_id = (empty($data['iresource_id']) ? 0 : intval($data['iresource_id']));
	$route_id = (empty($data['route_id']) ? 0 : intval($data['route_id']));
	$route_status = (empty($data['route_status']) ? 1 : intval($data['route_status']));
	$route_status_desc = (empty($data['route_status_desc']) ? ($route_status==1?'В процессе согласования':'') : $data['route_status_desc']);
	$current_step = (empty($data['current_step']) ? 0 : intval($data['current_step']));
	if(empty($request_id) || empty($iresource_id) || empty($route_id)) return false;
	$update_if_exists = (!empty($data['update_if_exists']) ? true : false);

	//Предварительно проверить существование ИР в заявке и обновить в случае нахождения
	if($update_if_exists){
		$rires_id = $db->result('SELECT `rires_id` FROM `request_iresources` WHERE `request_id`='.$request_id.' AND `iresource_id`='.$iresource_id.' LIMIT 1');
		if(!empty($rires_id)){
			$db->prepare('UPDATE `request_iresources` SET `route_status`=?,`route_status_desc`=?,`route_id`=?,`current_step`=? WHERE `rires_id`=?');
			$db->bind($route_status);
			$db->bind($route_status_desc);
			$db->bind($route_id);
			$db->bind($current_step);
			$db->bind($rires_id);
			if($db->update()===false) return false;
			return $rires_id;
		}
	}

	$db->prepare('INSERT INTO `request_iresources` (`request_id`,`iresource_id`,`route_id`,`route_status`,`route_status_desc`,`current_step`) VALUES (?,?,?,?,?,?)');
	$db->bind($request_id);
	$db->bind($iresource_id);
	$db->bind($route_id);
	$db->bind($route_status);
	$db->bind($route_status_desc);
	$db->bind($current_step);

	return intval($db->insert());
}#end function




#------------------------------------------------------------------
#Добавление в заявку объекта доступа для информационного ресурса
function request_add_iresource_role($data=array(), $db=null){

	if(!$db) $db = Database::getInstance('main');
	$request_id = (empty($data['request_id']) ? 0 : intval($data['request_id']));
	$iresource_id = (empty($data['iresource_id']) ? 0 : intval($data['iresource_id']));
	$irole_id = (empty($data['irole_id']) ? 0 : intval($data['irole_id']));
	$ir_type = (empty($data['ir_type']) ? 0 : intval($data['ir_type']));
	$gatekeeper_id = (empty($data['gatekeeper_id']) ? User::_getEmployerID() : intval($data['gatekeeper_id']));
	$update_type = (empty($data['update_type']) ? 0 : intval($data['update_type']));
	$ir_selected = (empty($data['ir_selected']) ? $ir_type : intval($data['ir_selected']));
	if(empty($request_id) || empty($iresource_id) || empty($irole_id)) return false;
	$update_if_exists = (!empty($data['update_if_exists']) ? true : false);

	//Предварительно проверить существование объекта ИР в заявке и обновить в случае нахождения
	if($update_if_exists){
		$rrole_id = $db->result('SELECT `id` FROM `request_roles` WHERE `request_id`='.$request_id.' AND `iresource_id`='.$iresource_id.' AND `irole_id`='.$irole_id.' LIMIT 1');
		if(!empty($rrole_id)){
			$db->prepare('UPDATE `request_roles` SET `ir_type`=?,`ir_selected`=?,`gatekeeper_id`=?,`update_type`=?,`timestamp`=? WHERE `id`=?');
			$db->bind($ir_type);
			$db->bind($ir_selected);
			$db->bind($gatekeeper_id);
			$db->bind($update_type);
			$db->bind(date("Y-m-d H:i:s"));
			$db->bind($rrole_id);
			if($db->update()===false) return false;
			return $rrole_id;
		}
	}

	$db->prepare('INSERT INTO `request_roles` (`request_id`,`iresource_id`,`irole_id`,`ir_type`,`ir_selected`,`gatekeeper_id`,`update_type`,`timestamp`) VALUES (?,?,?,?,?,?,?,?)');
	$db->bind($request_id);
	$db->bind($iresource_id);
	$db->bind($irole_id);
	$db->bind($ir_type);
	$db->bind($ir_selected);
	$db->bind($gatekeeper_id);
	$db->bind($update_type);
	$db->bind(date("Y-m-d H:i:s"));

	return intval($db->insert());
}#end function





#------------------------------------------------------------------
#Перевод маршрута согласования по заявке на следующий шаг
function request_route_next($data=array(), $db=null){

	if(!$db) $db = Database::getInstance('main');
	$request_id 	= (empty($data['request_id']) ? 0 : intval($data['request_id']));
	$iresource_id 	= (empty($data['iresource_id']) ? 0 : intval($data['iresource_id']));
	$is_admin 		= isset($data['is_admin']) ? ($data['is_admin'] == true ? true : false) : false;
	$gatekeeper_id 	= empty($data['gatekeeper_id']) ? 0 : intval($data['gatekeeper_id']);
	$is_approved 	= isset($data['is_approved']) ? ($data['is_approved'] == true ? true : false) : false;
	$is_declined 	= isset($data['is_declined']) ? ($data['is_declined'] == true ? true : false) : false;
	if(!$request_id) return array('result'=>false,'desc'=>'Неизвестная заявка');

	//Информация о заявке
	if( ($request_info = $db->selectRecord('SELECT * FROM `requests` WHERE `request_id`='.$request_id.' LIMIT 1')) === false) return array('result'=>false,'desc'=>'Ошибка при получении информации о заявке');
	if(empty($request_info)) return array('result'=>false,'desc'=>'Заявка не найдена');

	#Если требуется вычислить следующий шаг маршрута для конкретного информационного ресурса
	if($iresource_id > 0){
		$db->prepare('SELECT `iresource_id` FROM `request_iresources` WHERE `request_id`=? AND `iresource_id`=?');
		$db->bind($request_id);
		$db->bind($iresource_id);
		$iresources_ids = $db->selectFromField('iresource_id');
		if(empty($iresources_ids)) return array('result'=>false,'desc'=>'В заявке отсутствует указанный информационный ресурс');
	}else{
		$db->prepare('SELECT `iresource_id` FROM `request_iresources` WHERE `request_id`=?');
		$db->bind($request_id);
		$iresources_ids = $db->selectFromField('iresource_id');
		if(empty($iresources_ids)) return array('result'=>false,'desc'=>'В заявке отсутствуют информационные ресурсы');
	}

	$iresources_responce = array();

	//Перевод на следующий шаг указанных ИР
	foreach($iresources_ids as $iresource_id){

		#Сведения об информационном ресурсе
		if(($iresource_info = iresource_info(array('iresource_id'=>$iresource_id),$db)) === false) return array('result'=>false,'desc'=>'Информационный ресурс ID='.$iresource_id.' не существует');

		routeNextStepStart:

		$result = request_route_next_for_iresource(array(
			'request_id' 	=> $request_id,
			'iresource_id' 	=> $iresource_id,
			'is_admin' 		=> $is_admin,
			'gatekeeper_id' => $gatekeeper_id,
			'is_approved'	=> $is_approved,
			'is_declined'	=> $is_declined,
			'request_info'	=> $request_info,
			'iresource_info'=> $iresource_info
		), $db);

		#Обработка системных действий, полученных при переводе заявки на следующий шаг
		if(!empty($result['action'])){

			//Переход на шаг вперед
			if($result['action'] == 'next') goto routeNextStepStart;

		}#Обработка системных действий

		$result['iresource_id'] = $iresource_id;
		$result['iresource_name'] = $iresource_info['full_name'];
		$iresources_responce[$iresource_id] = $result;

	}//Перевод на следующий шаг указанных ИР

	return array('result'=>true,'desc'=>'Обработка заявки выполнена успешно','details'=>$iresources_responce);
}#end function





#------------------------------------------------------------------
#Перевод маршрута согласования по заявке на следующий шаг по конкретному ИР
function request_route_next_for_iresource($data=array(), $db=null){

	if(!$db) $db = Database::getInstance('main');

	
	
	
	
	
	
	
	
	
	


	return array('result'=>true,'desc'=>'Выполнено успешно');
}#end function






#------------------------------------------------------------------
#Получение идентификаторов гейткиперов для текущего шага маршрута
function request_current_gatekeepers($data=array(), $db=null){

	if(!$db) $db = Database::getInstance('main');
	$request_id 	= (empty($data['request_id']) ? 0 : intval($data['request_id']));
	$iresource_id 	= (empty($data['iresource_id']) ? 0 : intval($data['iresource_id']));
	$current_step	= (empty($data['current_step']) ? 0 : intval($data['current_step']));

	if(empty($request_id) || empty($iresource_id)) return false;

	//Текущий шаг
	if(empty($current_step)){
		$current_step = $db->result('SELECT `current_step` FROM `request_iresources` WHERE `request_id`='.$request_id.' AND `iresource_id`='.$iresource_id.' LIMIT 1');
		if($current_step === false) return false;
		if(empty($current_step)) return array();
		$current_step = intval($current_step);
	}

	//Информация о текущем шаге согласования
	$current_step_info = $db->selectRecord('SELECT * FROM `request_steps` WHERE `rstep_id`='.$current_step.' AND `request_id`='.$request_id.' AND `iresource_id`='.$iresource_id.' LIMIT 1');
	if(!is_array($current_step_info)) return false;


	
	
	$gatekeepers = array();

	
	
	
	
	
	
	

	return $gatekeepers;
}#end function












?>