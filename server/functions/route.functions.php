<?php
/*==================================================================================================
Описание: route.functions.php
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');


require_once(DIR_FUNCTIONS.'/employer.functions.php');
require_once(DIR_FUNCTIONS.'/iresource.functions.php');
require_once(DIR_FUNCTIONS.'/company.functions.php');

#------------------------------------------------------------------
#Информация о маршруте
function route_info($data=array(), $db=null){
	if(!$db) $db = Database::getInstance('main');
	$route_id = empty($data['route_id']) ? 0 : intval($data['route_id']);
	if(empty($route_id)) return false;
	return $db->selectRecord('SELECT * FROM `routes` WHERE `route_id`='.$route_id.' LIMIT 1');
}#end function



#------------------------------------------------------------------
#Информация ою определенном шаге маршрута
function route_step_info($data=array(), $db=null){

	if(!$db) $db = Database::getInstance('main');
	$route_id = empty($data['route_id']) ? 0 : intval($data['route_id']);
	$step_uid = empty($data['step_uid']) ? 0 : $data['step_uid'];
	if(empty($route_id) || empty($step_uid)) return false;

	$db->prepare('SELECT * FROM `route_steps` WHERE `route_id`=? AND `step_uid` LIKE ? LIMIT 1');
	$db->bind($route_id);
	$db->bind($step_uid);

	return $db->selectRecord();
}#end function





#------------------------------------------------------------------
#Возвращает перечень привелегий определенного гейткипера на на указанном шаге маршрута
function route_step_gatekeeper_privs($data=array(), $db=null){

	if(!$db) $db	= Database::getInstance('main');
	$gatekeeper_id	= (empty($data['gatekeeper_id']) ? User::_getEmployerID() : intval($data['gatekeeper_id']));
	$route_id		= empty($data['route_id']) ? 0 : intval($data['route_id']);
	$step_uid		= empty($data['step_uid']) ? 0 : $data['step_uid'];
	$employer_id	= (empty($data['employer_id']) ? User::_getEmployerID() : intval($data['employer_id']));
	$post_uid		= (empty($data['post_uid']) ? 0 : $data['post_uid']);
	$company_id		= (empty($data['company_id']) ? 0 : intval($data['company_id']));
	$iresource_id	= (empty($data['iresource_id']) ? 0 : intval($data['iresource_id']));
	if(empty($gatekeeper_id) || empty($route_id) || empty($step_uid) || empty($employer_id) || empty($post_uid) || empty($company_id) || empty($iresource_id)) return false;

	$result = array(
		'watch'		=> false,	//Может видеть заявку
		'approve'	=> false,	//Может одобрять на текущем шаге
		'decline'	=> false,	//Может отклонять на текущем шаге
		'cancel'	=> false,	//Может отменять
		'comment'	=> false	//Может оставлять комментарии
	);

	//Получение информации о текущем шаге маршрута
	$route_step_info = (!empty($data['step_info']) && is_array($data['step_info'])) ? 
		$data['step_info'] : 
		route_step_info(array(
			'route_id' => $route_id,
			'step_uid' => $step_uid,
		), $db);

	$gatekeepers = (!empty($data['step_gatekeepers']) && is_array($data['step_gatekeepers'])) ? 
		$data['step_gatekeepers'] : 
		route_step_gatekeepers(array(
			'route_id'			=> $route_id,
			'step_uid'			=> $step_uid,
			'employer_id'		=> $employer_id,
			'post_uid'			=> $post_uid,
			'company_id'		=> $company_id,
			'iresource_id'		=> $iresource_id,
			'route_step_info'	=> $route_step_info,
			'as_list'			=> true
		),$db);

	//Если гейткипер и заявитель - одно лицо
	if($gatekeeper_id == $employer_id){
		$result['watch']	= true;
		$result['cancel']	= true;
		$result['comment']	= true;
	}

	if($gatekeepers === false || $gatekeepers == null) return $result;

	//Указанный гейткипер принимает участие в процессе согласования на данном шаге
	if(in_array($gatekeeper_id, $gatekeepers, true)){
		$result['watch']	= true;
		$result['approve']	= true;
		$result['comment']	= true;

		//Если роль гейткипера на данном шаге - согласование или утверждение, он может отклонить заявку
		if($route_step_info['gatekeeper_role'] == 1 || $route_step_info['gatekeeper_role'] == 2) $result['decline'] = true;
	}


}#end function





#------------------------------------------------------------------
#Информация о гейткиперах на указанном шаге маршрута
function route_step_gatekeepers($data=array(), $db=null){

	if(!$db) $db	= Database::getInstance('main');
	$route_id		= empty($data['route_id']) ? 0 : intval($data['route_id']);
	$step_uid		= empty($data['step_uid']) ? 0 : $data['step_uid'];
	$employer_id	= (empty($data['employer_id']) ? User::_getEmployerID() : intval($data['employer_id']));
	$post_uid		= (empty($data['post_uid']) ? 0 : $data['post_uid']);
	$company_id		= (empty($data['company_id']) ? 0 : intval($data['company_id']));
	$iresource_id	= (empty($data['iresource_id']) ? 0 : intval($data['iresource_id']));
	$as_list		= isset($data['as_list']) ? ($data['as_list'] == true ? true : false) : false;
	if(empty($route_id) || empty($step_uid) || empty($employer_id) || empty($post_uid) || empty($company_id) || empty($iresource_id)) return false;

	//Получение информации о текущем шаге маршрута
	$route_step_info = (!empty($data['step_info']) && is_array($data['step_info'])) ? 
		$data['step_info'] : 
		route_step_info(array(
			'route_id' => $route_id,
			'step_uid' => $step_uid,
		), $db);

	//Шаг маршрута с указанным идентификатором не найден
	if(!is_array($route_step_info)) return false;

	//Если указанный шаг маршрута не проходит через гейткиперов - возвращаем пустой массив
	if($route_step_info['step_type'] != 2) return null;

	$gatekeepers = array();
	$gatekeeper_type	= $route_step_info['gatekeeper_type'];
	$gatekeeper_id		= $route_step_info['gatekeeper_id'];

	//Определение гейткиперов, исходя из указанного типа гейткипера
	switch($gatekeeper_type){

		#Конкретный сотрудник
		case '1':
			if(!$gatekeeper_id) return array(); //Если идентификатор гейткипера не определен - возвращаем пустой массив, нет гейткиперов
			$assistants = employer_assistants(array('employer_id'=>$gatekeeper_id,'as_list'=>true));
			if(empty($assistants)) $assistants = array();
			if($as_list){
				array_unshift($assistants, $gatekeeper_id);
				return array_unique($assistants);
			}
			$gatekeepers[$gatekeeper_id] = array_unique($assistants);
			return $gatekeepers;
		break;


		#Руководитель заявителя
		case '2':
			$post_info = employer_post_info(array('employer_id'=>$employer_id,'post_uid'=>$post_uid), $db);
			if(!is_array($post_info)) return false;
			if($post_info['boss_uid'] == 0 || $post_info['boss_id'] == 0) return null;
			$bosses = employer_post_info(array('post_uid'=>$post_info['boss_uid'],'employer_id'=>'all','as_list'=>true), $db);
			if(empty($bosses)) return array();
			foreach($bosses as $boss){
				$assistants = employer_assistants(array('employer_id'=>$boss,'as_list'=>true), $db);
				if(empty($assistants)) $assistants = array();
				if($as_list){
					$gatekeepers[] = $boss;
					$gatekeepers = array_merge($gatekeepers, $assistants);
				}else{
					$gatekeepers[$boss] = array_unique($assistants);
				}
			}
			return ($as_list ? array_unique($gatekeepers) : $gatekeepers);
		break;


		#Руководитель организации
		case '3':
			$bosses = company_director(array('company_id'=>$company_id, 'as_list'=>true));
			if(empty($bosses)) return array();
			foreach($bosses as $boss){
				$assistants = employer_assistants(array('employer_id'=>$boss,'as_list'=>true), $db);
				if(empty($assistants)) $assistants = array();
				if($as_list){
					$gatekeepers[] = $boss;
					$gatekeepers = array_merge($gatekeepers, $assistants);
				}else{
					$gatekeepers[$boss] = array_unique($assistants);
				}
			}
			return ($as_list ? array_unique($gatekeepers) : $gatekeepers);
		break;


		#Владелец ресурса
		case '4':
		$owners = iresource_owner(array('iresource_id'=>$iresource_id,'as_list'=>true));
		if(empty($owners)) return array();
			foreach($owners as $owner){
				$assistants = employer_assistants(array('employer_id'=>$owner,'as_list'=>true), $db);
				if(empty($assistants)) $assistants = array();
				if($as_list){
					$gatekeepers[] = $owner;
					$gatekeepers = array_merge($gatekeepers, $assistants);
				}else{
					$gatekeepers[$owner] = array_unique($assistants);
				}
			}
			return ($as_list ? array_unique($gatekeepers) : $gatekeepers);
		break;


		#Группа пользователей
		case '5':
			$db->prepare('
				SELECT EMP.`employer_id` as `employer_id`
				FROM `employer_groups` as EG
				INNER JOIN `employers` as EMP ON EMP.`employer_id`=EG.`employer_id` AND EMP.`status`>0
				WHERE EG.`group_id`=?
			');
			$db->bind($gatekeeper_id);
			$employers = $db->selectFromField('employer_id');
			return (empty($employers) ? array() : array_unique($employers));
		break;


		#Сотрудник, занимающий должность
		case '6':
			$bosses = employer_post_info($db, array('post_uid'=>$gatekeeper_id,'employer_id'=>'all','as_list'=>true));
			if(empty($bosses)) return array();
			foreach($bosses as $boss){
				$assistants = employer_assistants(array('employer_id'=>$boss,'as_list'=>true), $db);
				if(empty($assistants)) $assistants = array();
				if($as_list){
					$gatekeepers[] = $boss;
					$gatekeepers = array_merge($gatekeepers, $assistants);
				}else{
					$gatekeepers[$boss] = array_unique($assistants);
				}
			}
			return ($as_list ? array_unique($gatekeepers) : array_fill_keys(array_unique($gatekeepers),array()));
		break;


		#Группа исполнителей
		case '7': 
			$db->prepare('
				SELECT EMP.`employer_id` as `employer_id` 
				FROM `iresources` as IR
				INNER JOIN `employer_groups` as EG ON EG.`group_id`=IR.`worker_group`
				INNER JOIN `employers` as EMP ON EMP.`employer_id`=EG.`employer_id` AND EMP.`status`>0
				WHERE IR.`iresource_id`=?
			');
			$db->bind($iresource_id);
			$gatekeepers = $db->selectFromField('employer_id');
			if(empty($gatekeepers)) return array();
			return ($as_list ? array_unique($gatekeepers) : array_fill_keys(array_unique($gatekeepers),array()));
		break;


	}//Определение гейткиперов, исходя из указанного типа гейткипера

	return null;
}#end function


?>