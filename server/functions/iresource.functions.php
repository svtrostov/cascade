<?php
/*==================================================================================================
Описание: iresource.functions.php
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');




#------------------------------------------------------------------
#Список возможных типов доступа
function iresource_irtypes($db=null){
	if(!$db) $db = Database::getInstance('main');
	#Запрос списка возможных типов доступа
	return $db->select('SELECT * FROM `ir_types`');
}#end function




#------------------------------------------------------------------
#Список групп информационных ресурсов
function iresource_groups($db=null){
	if(!$db) $db = Database::getInstance('main');
	#Запрос списка групп информационных ресурсов
	return $db->select('SELECT * FROM `iresource_groups`');
}#end function




#------------------------------------------------------------------
#Информация об ИР
function iresource_info($data=array(), $db=null){
	if(!$db) $db = Database::getInstance('main');
	$iresource_id = intval($data['iresource_id']);
	return $db->selectRecord('SELECT * FROM `iresources` WHERE `iresource_id`='.$iresource_id.' LIMIT 1');
}#end function



#------------------------------------------------------------------
#Проверка существования ИР
function iresource_exists($iresource_id=0, $db=null){
	if(!$db) $db = Database::getInstance('main');
	return ($db->result('SELECT count(*) FROM `iresources` WHERE `iresource_id`='.intval($iresource_id).' LIMIT 1') == 1);
}#end function




#------------------------------------------------------------------
#Информация о владельце ИР
function iresource_owner($data=array(), $db=null){

	if(!$db) $db = Database::getInstance('main');
	$iresource_id = intval($data['iresource_id']);
	if(empty($iresource_id)) return false;
	$as_list = empty($data['as_list']) ? false : true;

	$db->prepare('
		SELECT
			IR.`iresource_id` as `iresource_id`,
			IR.`full_name` as `iresource_name`,
			C.`company_id` as `company_id`,
			C.`full_name` as `company_name`,
			EMP.`employer_id` as `employer_id`,
			EMP.`search_name` as `employer_name`,
			EP.`post_from` as `post_from`,
			EP.`post_to` as `post_to`,
			CP.`post_uid` as `post_uid`,
			CP.`post_id` as `post_id`,
			P.`full_name` as `post_name`
		FROM 
			`iresources` as IR
		INNER JOIN `companies` as C ON C.`company_id` = IR.`company_id`
		INNER JOIN `company_posts` as CP ON CP.`post_uid`=IR.`post_uid`
		INNER JOIN `employer_posts` as EP ON EP.`post_uid`=CP.`post_uid`
		INNER JOIN `posts` as P ON P.`post_id` = CP.`post_id`
		INNER JOIN `employers` as EMP ON EMP.`employer_id` = EP.`employer_id` AND EMP.`status`>0
		WHERE 
			IR.`iresource_id`=?
	');
	$db->bind($iresource_id);

	return ($as_list ? $db->selectFromField('employer_id') : $db->select());
}#end function




#------------------------------------------------------------------
#Информация об объектах доступа информационного ресурса
function iresource_roles($data=array(), $db=null){

	if(!$db) $db = Database::getInstance('main');
	$iresource_id 	= intval($data['iresource_id']);	#Идентификатор ИР
	$employer_id 	= intval($data['employer_id']);		#Идентификатор сотрудника, если задан, то проверяется и возвращается уже имеющийся доступ
	$only_active 	= (empty($data['only_active']) ? true : false);	#Признак возврата только активных объектов доступа
	$raw_data 		= (empty($data['raw_data']) ? false : true);	#Признак возврата данных по объектам доступа без обработки и форматирования
	$request_id 	= intval($data['request_id']);	#Идентификатор запроса, если указан, то возвращаются также указанные в запросе типы доступов
	if(empty($iresource_id)) return false;
	$iresource_info = (!empty($data['iresource_info'])&&is_array($data['iresource_info']) ? $data['iresource_info'] : iresource_info($db,array('iresource_id'=>$iresource_id)));
	if(empty($iresource_info)) return false;
	if($request_id > 0) $request_roles_table = $this->getRIRoleDBTableName($request_id);

	#Получение списка объектов ИР
	$db->prepare('
		SELECT
			IR.`irole_id` as `irole_id`,
			"'.$iresource_id.'" as `iresource_id`,
			IR.`owner_id` as `owner_id`,
			IR.`short_name` as `short_name`,
			IR.`full_name` as `full_name`,
			IR.`description` as `description`,
			IR.`is_area` as `is_area`,
			IR.`ir_types` as `ir_types`,
			IR.`weight` as `weight`,
			IR.`screenshot` as `screenshot`,
			'.($employer_id > 0 ? 'CR.`ir_type` as `ir_current`':'"0" as `ir_current`').',
			'.($request_id > 0 ? '
				RR.`request_id` as `request_id`,
				RR.`ir_type` as `ir_request`,
				RR.`gatekeeper_id` as `gatekeeper_id`,
				RR.`update_type` as `update_type`,
				RR.`ir_selected` as `ir_selected`,
				DATE_FORMAT(RR.`timestamp`, "%d.%m.%Y %h:%i:%s") as `update_time`
				':'
				"0" as `ir_selected`
			').'
		FROM 
			`iroles` as IR
			'.($employer_id > 0 ? 'LEFT JOIN `complete_roles` as CR ON CR.`iresource_id`=? AND CR.`irole_id`=IR.`irole_id` AND CR.`employer_id`=?':'').'
			'.($request_id > 0 ? 'LEFT JOIN `'.$request_roles_table.'` as RR ON RR.`request_id`=? AND RR.`iresource_id`=? AND RR.`irole_id`=IR.`irole_id`':'').'
		WHERE 
			IR.`iresource_id`=?
			'.($only_active ? 'AND IR.`is_lock`=0':'').'
		ORDER BY 
			IR.`full_name`
	');
	if($employer_id > 0){
		$db->bind($iresource_id);
		$db->bind($employer_id);
	}
	if($request_id > 0){
		$db->bind($request_id);
		$db->bind($iresource_id);
		
	}
	$db->bind($iresource_id);

	if( ($results = $db->select(null, MYSQL_ASSOC)) === false) return false;
	
	#Возвращаем сырые данные если RAW запрос
	if($raw_data) return $results;
	
	$data = array();
	$items = array();
	$areas = array(array(
		'irole_id' => '0',
		'iresource_id' => $iresource_id,
		'full_name' => '--Без раздела--'
	));
	$area_ids = array();
	$area_ids[0] = true;
	
	#Подготовка списка разделов
	foreach($results as $key=>$item){
		if($results[$key]['is_area'] == 1){
			$areas[] = array(
				'irole_id' => $results[$key]['irole_id'],
				'iresource_id' => $results[$key]['iresource_id'],
				'full_name' => $results[$key]['full_name']
			);
			$area_ids[$results[$key]['irole_id']] = true;
		}else{
			if(empty($results[$key]['ir_selected']))$results[$key]['ir_selected']=0;
			
			if($employer_id > 0){
				if(empty($results[$key]['ir_current'])) $results[$key]['ir_current']=0;
			}
			if($request_id > 0){
				if(empty($results[$key]['request_id'])) $results[$key]['request_id']=$request_id;
				if(empty($results[$key]['ir_request'])) $results[$key]['ir_request']=0;
				if(empty($results[$key]['gatekeeper_id'])) $results[$key]['gatekeeper_id']=0;
				if(empty($results[$key]['update_type'])) $results[$key]['update_type']=0;
				if(empty($results[$key]['update_time'])) $results[$key]['update_time']='';
				if(!empty($results[$key]['update_type'])){
					if(empty($results[$key]['gatekeeper_id'])){
						$results[$key]['gatekeeper_name'] = 'Администратор';
					}else{
						$results[$key]['gatekeeper_name'] = $db->result('SELECT `search_name` FROM `employers` WHERE `employer_id`='.intval($results[$key]['gatekeeper_id']).' LIMIT 1');
						if(empty($results[$key]['gatekeeper_name']))$results[$key]['gatekeeper_name'] = '-не определен, ID=['.intval($results[$key]['gatekeeper_id']).']-';
					}
				}else{
					$results[$key]['gatekeeper_name'] = '';
				}
			}
			if(!empty($results[$key]['ir_types'])){
				$results[$key]['ir_types'] = explode(',',$results[$key]['ir_types']);
			}else{
				$results[$key]['ir_types']=0;
			}
		}
	}

	#Обработка записей и перегруппировка по разделам
	foreach($results as $key=>$item){
		if($results[$key]['is_area'] != 1){
			$results[$key]['screenshot'] = (irole_screenshot_exists($results[$key]['irole_id']) ? $results[$key]['irole_id'] : '');
			#Если родительский раздел не найден в списке разделов, отправляем в "Без раздела"
			if(!isset($area_ids[$results[$key]['owner_id']])) $results[$key]['owner_id'] = 0;
			$results[$key]['ir_types'] = empty($results[$key]['ir_types']) ? '' : $results[$key]['ir_types'];
			if(!isset($data[$results[$key]['owner_id']])||!is_array($data[$results[$key]['owner_id']])) $data[$results[$key]['owner_id']] = array();
			$data[$results[$key]['owner_id']][] = $results[$key];
		}
	}

	#Создание результирующего списка
	foreach($areas as $item){
		if(isset($data[$item['irole_id']])&&is_array($data[$item['irole_id']])){
			$items[] = $item['full_name'];
			foreach($data[$item['irole_id']] as $i){
				$items[] = $i;
			}
		}
	}

	return $items;
}#end function




?>