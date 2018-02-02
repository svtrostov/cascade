<?php
/*==================================================================================================
Описание: Заявки
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');




class Admin_Request{
	use Trait_RequestRoles, Trait_RequestHistory;

	/*==============================================================================================
	Переменные класса
	==============================================================================================*/

	private $db 			= null;		#Указатель на экземпляр базы данных


	private $defaultRequestRecord = array(
		'request_type'	=> 2,
		'curator_id'	=> 0,
		'employer_id'	=> 0,
		'company_id'	=> 0,
		'post_uid'		=> 0,
		'timestamp'		=> '0000-00-00 00:00:00',
		'phone'			=> '',
		'email'			=> ''
	);

	private $defaultRequestIResourceRecord = array(
		'request_id'		=> 0,
		'iresource_id'		=> 0,
		'route_id'			=> 0,
		'route_status'		=> 0,
		'route_status_desc'	=> '',
		'current_step'		=> 0
	);



	private $defaultRequestCommentRecord = array(
		'request_id'	=> 0,
		'iresource_id'	=> 0,
		'employer_id'	=> 0,
		'comment'		=> '',
		'timestamp'		=> '0000-00-00 00:00:00'
	);



	private $defaultRequestIRoleRecord = array(
		'request_id'	=> 0,
		'iresource_id'	=> 0,
		'irole_id'		=> 0,
		'ir_type'		=> 0,
		'ir_selected'	=> 0,
		'gatekeeper_id'	=> 0,
		'update_type'	=> 0,
		'timestamp'		=> '0000-00-00 00:00:00'
	);


	private $requests = array();
	private $routes = array();
	private $employer_groups = array();

	private $dbtoday		= '';		#Сегодняшняя дата
	private $dbtimestamp	= '';		#Сегодняшняя дата и время


	/*==============================================================================================
	Инициализация
	==============================================================================================*/


	/*
	 * Конструктор класса
	 */
	public function __construct(){
		$this->db = Database::getInstance('main');
		$this->dbtoday		= date('Y-m-d');
		$this->dbtimestamp	= date('Y-m-d H:i:s');
	}#end function






	/*==============================================================================================
	ФУНКЦИИ: Работа с заявками
	==============================================================================================*/


	/*
	 * Проверка cуществования заявки
	 */
	public function requestExists($request_id=0){
		if(empty($request_id)) return false;
		$this->db->prepare('SELECT count(*) FROM `requests` WHERE `request_id`=? LIMIT 1');
		$this->db->bind($request_id);
		return ($this->db->result() > 0);
	}#end function




	/*
	 * Проверка cуществования информационного ресурса в заявке
	 */
	public function requestIResourceExists($request_id=0, $iresource_id=0){
		if(empty($request_id)||empty($iresource_id)) return false;
		if($this->isRIResourceActive($request_id, $iresource_id)) return true;
		if($this->isRIResourceHistory($request_id, $iresource_id)) return true;
		return false;
	}#end function




	/*
	 * Добавление Заявки
	 */
	public function requestNew($fields=array()){

		if(empty($fields)) return false;
		$add_fields = array_intersect(array_keys($this->defaultRequestRecord), array_keys($fields));
		$adds = array();
		if(empty($add_fields)) return false;
		foreach($add_fields as $add_field){
			$adds[$add_field] = $fields[$add_field];
		}
		$fields = array_merge($this->defaultRequestRecord, $adds);
		$ins_names=array();
		$ins_q=array();
		$binds=array();
		foreach($fields as $field=>$value){
			$ins_names[]='`'.$field.'`';
			$ins_q[]='?';
			$binds[]=$value;
		}
		$this->db->prepare('INSERT INTO `requests` ('.implode(',',$ins_names).')VALUES('.implode(',',$ins_q).')');
		foreach($binds as $bind) $this->db->bind($bind);
		if(($request_id = $this->db->insert())===false) return false;
		if(!$this->createRIRoleDBTable($request_id)) return false;

		return $request_id;
	}#end function






	/*
	 * Добавление Заявки
	 */
	public function requestIResourceNew($fields=array()){

		if(empty($fields)) return false;
		$add_fields = array_intersect(array_keys($this->defaultRequestIResourceRecord), array_keys($fields));
		$adds = array();
		if(empty($add_fields)) return false;
		foreach($add_fields as $add_field){
			$adds[$add_field] = $fields[$add_field];
		}
		$fields = array_merge($this->defaultRequestIResourceRecord, $adds);
		$request_iresources_table = 'request_iresources';//$this->getRIResourceDBTableName($fields['route_status']);
		$ins_names=array();
		$ins_q=array();
		$binds=array();
		foreach($fields as $field=>$value){
			$ins_names[]='`'.$field.'`';
			$ins_q[]='?';
			$binds[]=$value;
		}
		$this->db->prepare('INSERT INTO `'.$request_iresources_table.'` ('.implode(',',$ins_names).')VALUES('.implode(',',$ins_q).')');
		foreach($binds as $bind) $this->db->bind($bind);
		if(($request_id = $this->db->insert())===false) return false;

		return $request_id;
	}#end function







	/*
	 * Получение информации по заявке
	 */
	public function requestInfo($request_id=0){
		if(empty($request_id)) return false;
		if(!empty($this->requests[$request_id])) return $this->requests[$request_id];
		$this->db->prepare('SELECT * FROM `requests` WHERE `request_id`=? LIMIT 1');
		$this->db->bind($request_id);
		$info = $this->db->selectRecord();
		if(empty($info)) return false;
		$this->requests[$request_id] = $info;
		return $info;
	}




	/*
	 * Получение списка заявок
	 */
	public function getRequestsListEx($requests=0, $fields=null, $single=false, $extended=true, $last_requests=0){

		$select = '';
		$select_from_field = false;
		$last_requests = intval($last_requests);

		if(empty($fields)){
			$select = 'SELECT REQ.*';
		}
		elseif(!is_array($fields) && (array_key_exists($fields, $this->defaultRequestRecord) || $fields=='request_id')){
			$select = 'SELECT REQ.`'.$fields.'` as `'.$fields.'`';
			$select_from_field = true;
		}
		elseif(is_array($fields)){
			foreach($fields as $field){
				if(!array_key_exists($field, $this->defaultRequestRecord) && $field!='request_id') continue;
				$select.= (empty($select) ? 'SELECT ' : ', ');
				$select.='REQ.`'.$field.'` as `'.$field.'`';
			}
		}

		if(empty($select)) return false;

		
		if($extended){
			$select.=',
			DATE_FORMAT(REQ.`timestamp`,"%d.%m.%Y") as `create_date`,
			EE.`search_name` as `employer_name`,
			EC.`search_name` as `curator_name`,
			IFNULL(C.`full_name`,"") as `company_name`,
			IFNULL(P.`full_name`,"") as `post_name`
			FROM `requests` as REQ
				INNER JOIN `employers` as EE ON EE.`employer_id` = REQ.`employer_id`
				INNER JOIN `employers` as EC ON EC.`employer_id` = REQ.`curator_id`
				INNER JOIN `companies` as C ON C.`company_id`=REQ.`company_id`
				INNER JOIN `company_posts` as CP ON CP.`post_uid`=REQ.`post_uid`
				INNER JOIN `posts` as P ON P.`post_id`=CP.`post_id`
			';
		}else{
			$select.=' FROM `requests` as REQ';
		}

		if(!is_array($requests)){
			if(empty($requests)){
				$result = $this->db->prepare($select.($single?' LIMIT 1':($last_requests>0?' ORDER BY REQ.`request_id` DESC LIMIT '.$last_requests:'')));
			}else{
				$this->db->prepare($select.' WHERE REQ.`request_id`=? '.($single?' LIMIT 1':($last_requests>0?' ORDER BY REQ.`request_id` DESC LIMIT '.$last_requests:'')));
				$this->db->bind(intval($requests));
			}
		}else{
			$conditions = $this->db->buildSqlConditions($requests,'REQ');
			$this->db->prepare($select.' WHERE '.$conditions.($single?' LIMIT 1':($last_requests>0?' ORDER BY REQ.`request_id` DESC LIMIT '.$last_requests:'')));
		}

		$result = ($select_from_field ? $this->db->selectFromField($fields)  : (empty($single) ? $this->db->select() : $this->db->selectRecord()));

		if(!$extended || $select_from_field) return $result;

		return $result;
	}#end function





	/*
	 * Получение списка информационных ресурсов заявки
	 */
	public function requestIResourcesEx($request_id=0, $iresource_id=0, $extended=true){

		$request_id = intval($request_id);
		$iresource_id = intval($iresource_id);

		if($iresource_id > 0){
			$request_iresources_table = $this->getRIResourceDBTableName($request_id, $iresource_id);
			$this->db->prepare('
				SELECT
					RIR.*,
					IR.`full_name` as `iresource_name`,
					ROUT.`full_name` as `route_name`
				FROM `'.$request_iresources_table.'` as RIR
					INNER JOIN `iresources` as IR ON IR.`iresource_id`=RIR.`iresource_id`
					INNER JOIN `routes` as ROUT ON ROUT.`route_id`=RIR.`route_id`
				WHERE RIR.`request_id`='.$request_id.' AND RIR.`iresource_id`='.$iresource_id.' LIMIT 1'
			);
		}else{
			$this->db->prepare('
				SELECT RST.* FROM (
					(SELECT
						RIR.*,
						IR.`full_name` as `iresource_name`,
						ROUT.`full_name` as `route_name`
					FROM `request_iresources` as RIR
						INNER JOIN `iresources` as IR ON IR.`iresource_id`=RIR.`iresource_id`
						INNER JOIN `routes` as ROUT ON ROUT.`route_id`=RIR.`route_id`
					WHERE RIR.`request_id`='.$request_id.') 
					UNION ALL
					(SELECT
						RIR.*,
						IR.`full_name` as `iresource_name`,
						ROUT.`full_name` as `route_name`
					FROM `request_iresources_hist` as RIR
						INNER JOIN `iresources` as IR ON IR.`iresource_id`=RIR.`iresource_id`
						INNER JOIN `routes` as ROUT ON ROUT.`route_id`=RIR.`route_id`
					WHERE RIR.`request_id`='.$request_id.')
				) as RST
			');
		}
		if(!$extended) return ($iresource_id>0 ? $this->db->selectRecord() : $this->db->select());
		if(($iresources = $this->db->select())===false) return false;
		$count = count($iresources);
		if(!$count) return ($iresource_id>0 ? null : array());

		for($i=0;$i<$count;$i++){
			$iresources[$i]['comments'] = $this->requestIResourceComments($request_id, $iresources[$i]['iresource_id']);
			$iresources[$i]['roles'] = $this->requestIResourceRoles($request_id, $iresources[$i]['iresource_id'],false,null,$iresources[$i]['route_status']);
			$iresources[$i]['steps'] = $this->requestIResourceStepsHistory($request_id, $iresources[$i]['iresource_id']);
			$iresources[$i]['route'] = $this->requestIResourceRouteSteps($request_id, $iresources[$i]['iresource_id'], $iresources[$i]['route_id']);
			$iresources[$i]['current_step_uid'] = 0;
		}

		return ($iresource_id>0 ? $iresources[0] :$iresources);
	}#end function




	/*
	 * Получение списка объектов доступа в заявке
	 */
	public function requestIResourceRoles($request_id=0, $iresource_id=0, $raw_data=false, $only_changed_roles=null,$route_status=1){

		$request_id = intval($request_id);
		$iresource_id = intval($iresource_id);
		if(empty($request_id)) return false;
		$request_info = $this->requestInfo($request_id);
		if(empty($request_info)) return false;
		$request_roles_table = $this->getRIRoleDBTableName($request_id);

		#Получение списка объектов ИР
		$this->db->prepare('
			SELECT
				IROLE.`irole_id` as `irole_id`,
				"'.$iresource_id.'" as `iresource_id`,
				IROLE.`owner_id` as `owner_id`,
				IROLE.`short_name` as `short_name`,
				IROLE.`full_name` as `full_name`,
				IROLE.`description` as `description`,
				IROLE.`is_area` as `is_area`,
				IROLE.`ir_types` as `ir_types`,
				IROLE.`weight` as `weight`,
				CROLE.`ir_type` as `ir_current`,
				RROLE.`request_id` as `request_id`,
				RROLE.`ir_type` as `ir_request`,
				RROLE.`gatekeeper_id` as `gatekeeper_id`,
				IF(RROLE.`update_type`=0, null, EMP.`search_name`)  as `gatekeeper_name`,
				RROLE.`update_type` as `update_type`,
				RROLE.`ir_selected` as `ir_selected`,
				DATE_FORMAT(RROLE.`timestamp`, "%d.%m.%Y %h:%i:%s") as `update_time`
			FROM 
				`iroles` as IROLE
			LEFT JOIN `complete_roles` as CROLE ON CROLE.`iresource_id`=? AND CROLE.`irole_id`=IROLE.`irole_id` AND CROLE.`employer_id`=?
			LEFT JOIN `'.$request_roles_table.'` as RROLE ON RROLE.`request_id`=? AND RROLE.`iresource_id`=? AND RROLE.`irole_id`=IROLE.`irole_id`
			LEFT JOIN `employers` as EMP ON EMP.`employer_id`=RROLE.`gatekeeper_id`
			WHERE 
				IROLE.`iresource_id`=? AND IROLE.`is_lock`=0
			ORDER BY 
				IROLE.`full_name`
		');
		$this->db->bind($request_info['employer_id']);
		$this->db->bind($iresource_id);
		$this->db->bind($request_id);
		$this->db->bind($iresource_id);
		$this->db->bind($iresource_id);
		if( ($results = $this->db->select()) === false) return false;

		#Возвращаем сырые данные если RAW запрос
		if($raw_data) return $results;

		//Если заявка на удаление доступа - выводим только запрошенные в заявке роли
		if(is_null($only_changed_roles)) $only_changed_roles = (($request_info['request_type']==3 || $route_status==0 || $route_status==100) ? true : false);

		$data = array();
		$items = array();
		$areas = array(array(
			'irole_id' => '0',
			'iresource_id' => $iresource_id,
			'full_name' => '-[Без раздела]-'
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
				if(empty($results[$key]['ir_current'])) $results[$key]['ir_current']=0;
				if(empty($results[$key]['request_id'])) $results[$key]['request_id']=$request_id;
				if(empty($results[$key]['ir_request'])) $results[$key]['ir_request']=0;
				if(empty($results[$key]['gatekeeper_id'])) $results[$key]['gatekeeper_id']=0;
				if(empty($results[$key]['update_type'])) $results[$key]['update_type']=0;
				if(empty($results[$key]['update_time'])) $results[$key]['update_time']='';
				if(!empty($results[$key]['update_type'])){
					if(empty($results[$key]['gatekeeper_id'])){
						$results[$key]['gatekeeper_name'] = 'Администратор';
					}else{
						if(empty($results[$key]['gatekeeper_name']))$results[$key]['gatekeeper_name'] = '-не определен, ID=['.intval($results[$key]['gatekeeper_id']).']-';
					}
				}else{
					$results[$key]['gatekeeper_name'] = '';
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
				if($only_changed_roles && $results[$key]['ir_selected']==0 && $results[$key]['ir_request']==0) continue;
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




	/*
	 * Получение списка этапов согласования в заявке
	 */
	public function requestIResourceStepsHistory($request_id=0, $iresource_id=0){
		$is_history = ($this->isRIResourceActive($request_id, $iresource_id) ? false : true);
		$this->db->prepare('
			SELECT 
				RSTEP.`step_uid` as `step_uid`,
				RSTEP.`rstep_id` as `rstep_id`,
				RSTEP.`route_id` as `route_id`,
				RSTEP.`gatekeeper_id` as `gatekeeper_id`,
				RSTEP.`assistant_id` as `assistant_id`,
				RSTEP.`step_complete` as `step_complete`,
				RSTEP.`is_approved` as `is_approved`,
				IF(RSTEP.`step_complete`=1 AND RSTEP.`gatekeeper_id` > 0, (SELECT `search_name` FROM `employers` WHERE `employer_id`=RSTEP.`gatekeeper_id` LIMIT 1),"") as `gatekeeper_name`,
				IF(RSTEP.`step_complete`=1 AND RSTEP.`assistant_id` > 0, (SELECT `search_name` FROM `employers` WHERE `employer_id`=RSTEP.`assistant_id` LIMIT 1),"") as `assistant_name`,
				DATE_FORMAT(RSTEP.`timestamp`, "%d.%m.%Y %H:%i:%s") as `timestamp`
			FROM `'.($is_history ? 'request_steps_hist' : 'request_steps').'` as RSTEP
			WHERE RSTEP.`request_id`=? AND RSTEP.`iresource_id`=?
			ORDER BY RSTEP.`rstep_id` ASC
		');
		$this->db->bind($request_id);
		$this->db->bind($iresource_id);
		if(($result = $this->db->select())===false)return false;
		return $result;
	}#end function




	/*
	 * Получение списка шагов маршрута в заявке
	 */
	public function requestIResourceRouteSteps($request_id=0, $iresource_id=0, $route_id=0){
		$request_id = intval($request_id);
		$iresource_id = intval($iresource_id);
		$route_id = intval($route_id);
		if(empty($request_id)||empty($iresource_id)||empty($route_id)) return false;
		$request_info = $this->requestInfo($request_id);
		if(empty($request_info)) return false;

		$this->db->prepare('SELECT * FROM `route_steps` WHERE `route_id`=?');
		$this->db->bind($route_id);
		$steps = $this->db->selectByKey('step_uid');
		if(empty($steps)) return false;

		$admin_route = new Admin_Route();

		#Просмотр маршрута по step_yes
		$step_uid = $admin_route->getStepUID($route_id,1,0,0,0);
		$we_be_here=array();
		$result = array();
		while(true){
			if(empty($steps[$step_uid])) return false;
			if(in_array($step_uid,$we_be_here, true)) return false;
			$we_be_here[]=$step_uid;
			$result[] = $steps[$step_uid];
			if($steps[$step_uid]['step_type']==3) break;
			$step_uid = $steps[$step_uid]['step_yes'];
		}


		for($i=0;$i<count($result);$i++){
			$gatekeepers = false;
			$data = array(
				'request_id'		=> $request_id,
				'iresource_id'		=> $iresource_id,
				'step_type'			=> $result[$i]['step_type'],
				'gatekeeper_type'	=> $result[$i]['gatekeeper_type'],
				'gatekeeper_role'	=> $result[$i]['gatekeeper_role'],
				'gatekeeper_id'		=> $result[$i]['gatekeeper_id'],
				'employer_id'		=> $request_info['employer_id'],
				'post_uid'			=> $request_info['post_uid'],
				'company_id'		=> $request_info['company_id']
			);
			$result[$i]['info'] = $this->requestIResourceStepInfo($data);
			if($result[$i]['step_type'] == 2){
				$gatekeepers = $this->requestIResourceStepGatekeepers($data);
			}
			if(empty($gatekeepers)||!is_array($gatekeepers)){
				$result[$i]['gatekeepers'] = null;
			}else{
				$result[$i]['gatekeepers'] = $this->db->select('SELECT `employer_id`,`search_name` FROM `employers` WHERE `employer_id` IN ('.implode(',',$gatekeepers).') AND `status`>0 ORDER BY `search_name`');
			}
		}

		return $result;
	}#end function





	/*
	 * Получение получение сведений об указанном шаге согласования
	 */
	public function requestIResourceStepInfo($data=null){

		$step_type			= (empty($data['step_type']) ? 0 : intval($data['step_type']));
		$gatekeeper_type	= (empty($data['gatekeeper_type']) ? 0 : intval($data['gatekeeper_type']));
		$gatekeeper_role	= (empty($data['gatekeeper_role']) ? 0 : intval($data['gatekeeper_role']));
		$gatekeeper_id		= (empty($data['gatekeeper_id']) ? 0 : (is_numeric($data['gatekeeper_id'])?$data['gatekeeper_id']:0));
		$post_uid			= (empty($data['post_uid']) ? 0 : (is_numeric($data['post_uid']) ? $data['post_uid'] : 0));
		if(empty($step_type)) return false;

		$block_info = '-[?????]-';
		$gk_info = '---';

		switch($step_type){
			case 1: $block_info = 'Начало маршрута'; break;
			case 3: $block_info = 'Заявка исполнена'; break;
			case 4: $block_info = 'Заявка отклонена'; break;
			case 2:

				switch($gatekeeper_role){
					case 1: $block_info = 'Согласование'; break;
					case 2: $block_info = 'Утверждение'; break;
					case 3: $block_info = 'Исполнение'; break;
					case 4: $block_info = 'Уведомление'; break;
				}

				switch($gatekeeper_type){
					case 1: $gk_info = 'Сотрудник'; break;
					case 2: $gk_info = 'Руководитель заявителя'; break;
					case 3: $gk_info = 'Руководитель организации заявителя'; break;
					case 4: $gk_info = 'Владелец информационного ресурса'; break;
					case 5: $gk_info = 'Группа сотрудников: '.$this->db->result('SELECT `full_name` FROM `groups` WHERE `group_id`='.$gatekeeper_id.' LIMIT 1'); break;
					case 6:
						$this->db->prepare('
						SELECT
							P.`full_name` as `post_name`,
							C.`full_name` as `company_name`
						FROM `company_posts` as CP 
						INNER JOIN `posts` as P ON P.`post_id` = CP.`post_id` 
						INNER JOIN `companies` as C ON C.`company_id` = CP.`company_id` 
						WHERE CP.`post_uid`=? LIMIT 1');
						$this->db->bind($gatekeeper_id);
						$nfo = $this->db->selectRecord();
						if(!empty($nfo)){
							$gk_info = 'Сотрудник, занимающий должность: '.$nfo['post_name'].' в '.$nfo['company_name'];
						}else{
							$gk_info = 'Сотрудник, занимающий должность: ['.$post_uid.']';
						}
					break;
					case 7: $gk_info = 'Группа исполнителей, назначенных информационному ресурсу'; break;
				}

			break;
		}

		return array(
			'action' => $block_info,
			'gatekeeper' => $gk_info
		);

	}#end function





	/*
	 * Получение списка гейткиперов на указанном шаге согласования
	 */
	public function requestIResourceStepGatekeepers($data=null){

		$gatekeeper_type	= (empty($data['gatekeeper_type']) ? 0 : intval($data['gatekeeper_type']));
		$gatekeeper_id		= (empty($data['gatekeeper_id']) ? 0 : (is_numeric($data['gatekeeper_id'])?$data['gatekeeper_id']:0));
		$iresource_id		= (empty($data['iresource_id']) ? 0 : intval($data['iresource_id']));
		$employer_id		= (empty($data['employer_id']) ? 0 : intval($data['employer_id']));
		$company_id			= (empty($data['company_id']) ? 0 : intval($data['company_id']));
		$post_uid			= (empty($data['post_uid']) ? 0 : (is_numeric($data['post_uid']) ? $data['post_uid'] : 0));
		if(empty($gatekeeper_type) || empty($iresource_id) || empty($employer_id) || empty($post_uid) || empty($company_id)) return false;
		if(!isset($this->employer_groups[$employer_id])){
			$this->employer_groups[$employer_id] = $this->db->selectFromField('group_id', 'SELECT `group_id` FROM `employer_groups` WHERE `employer_id` ='.$employer_id);
		}
		$employer_groups = $this->employer_groups[$employer_id];

		switch($gatekeeper_type){

			#Конкретный сотрудник
			case '1':
				$this->db->prepare('SELECT `employer_id` FROM `employers` WHERE `employer_id`=? AND `status`>0');
				$this->db->bind($gatekeeper_id);
				return $this->db->selectFromField('employer_id');
			break;

			#Руководитель заявителя
			case '2':
				$this->db->prepare('
					SELECT DISTINCT EP.`employer_id` as `employer_id`
					FROM `company_posts` as CP
					INNER JOIN `employer_posts` as EP ON ((CP.`boss_uid`>0 AND EP.`post_uid`=CP.`boss_uid`)OR(CP.`boss_uid`=0 AND EP.`post_uid`=CP.`post_uid`)) AND EP.`post_from`<=? AND EP.`post_to`>=?
					INNER JOIN `employers` as EMP ON EMP.`employer_id`=EP.`employer_id` AND EMP.`status`>0
					WHERE CP.`post_uid`=?
				');
				$this->db->bind($this->dbtoday);
				$this->db->bind($this->dbtoday);
				$this->db->bind($post_uid);
				return $this->db->selectFromField('employer_id');
			break;

			#Руководитель организации
			case '3':
				$this->db->prepare('
					SELECT DISTINCT EP.`employer_id` as `employer_id`
					FROM `company_posts` as CP
					INNER JOIN `employer_posts` as EP ON EP.`post_uid`=CP.`post_uid` AND EP.`post_from`<=? AND EP.`post_to`>=?
					INNER JOIN `employers` as EMP ON EMP.`employer_id`=EP.`employer_id` AND EMP.`status`>0
					WHERE CP.`company_id`=? AND CP.`boss_uid`=0
				');
				$this->db->bind($this->dbtoday);
				$this->db->bind($this->dbtoday);
				$this->db->bind($company_id);
				return $this->db->selectFromField('employer_id');
			break;

			#Владелец ресурса
			case '4':
				$this->db->prepare('
					SELECT DISTINCT EP.`employer_id` as `employer_id`
					FROM `iresources` as IR
					INNER JOIN `employer_posts` as EP ON EP.`post_uid`=IR.`post_uid` AND EP.`post_from`<=? AND EP.`post_to`>=?
					INNER JOIN `employers` as EMP ON EMP.`employer_id`=EP.`employer_id` AND EMP.`status`>0
					WHERE IR.`iresource_id`=?
				');
				$this->db->bind($this->dbtoday);
				$this->db->bind($this->dbtoday);
				$this->db->bind($iresource_id);
				return $this->db->selectFromField('employer_id');
			break;

			#Группа пользователей
			case '5':
				$this->db->prepare('
					SELECT DISTINCT EG.`employer_id` as `employer_id`
					FROM `employer_groups` as EG
					INNER JOIN `employers` as EMP ON EMP.`employer_id`=EG.`employer_id` AND EMP.`status`>0
					WHERE EG.`group_id`=?
				');
				$this->db->bind($gatekeeper_id);
				return $this->db->selectFromField('employer_id');
			break;

			#Сотрудник, занимающий должность
			case '6':
				$this->db->prepare('
					SELECT DISTINCT EP.`employer_id` as `employer_id`
					FROM `employer_posts` as EP
					INNER JOIN `employers` as EMP ON EMP.`employer_id`=EP.`employer_id` AND EMP.`status`>0
					WHERE EP.`post_uid`=? AND EP.`post_from`<=? AND EP.`post_to`>=?
				');
				$this->db->bind($gatekeeper_id);
				$this->db->bind($this->dbtoday);
				$this->db->bind($this->dbtoday);
				return $this->db->selectFromField('employer_id');
			break;

			#Группа исполнителей
			case '7':
				$this->db->prepare('
					SELECT DISTINCT EG.`employer_id` as `employer_id`
					FROM `iresources` as IR
					INNER JOIN `employer_groups` as EG ON EG.`group_id`=IR.`worker_group`
					INNER JOIN `employers` as EMP ON EMP.`employer_id`=EG.`employer_id` AND EMP.`status`>0
					WHERE IR.`iresource_id`=?
				');
				$this->db->bind($iresource_id);
				return $this->db->selectFromField('employer_id');
			break;

		}

		return false;
	}#end function







	/*
	 * Комментарии к заявке
	 */
	public function requestIResourceComments($request_id=0, $iresource_id=0){
		$request_id = intval($request_id);
		$iresource_id = (!empty($iresource_id) ? intval($iresource_id) : 0);
		$this->db->prepare('
			SELECT
				RC.`comment_id` as `comment_id`,
				RC.`employer_id` as `employer_id`,
				EMP.`search_name` as `employer_name`,
				RC.`comment` as `comment`,
				DATE_FORMAT(RC.`timestamp`, "%d.%m.%Y %H:%i:%s") as `timestamp`
			FROM 
				`request_comments` as RC
			INNER JOIN `iresources` as IR ON IR.`iresource_id`=RC.`iresource_id`
			LEFT JOIN `employers` as EMP ON EMP.`employer_id`=RC.`employer_id`
			WHERE
				RC.`request_id`=?
				'.($iresource_id > 0 ? 'AND RC.`iresource_id`=?' : '').'
			ORDER BY RC.`comment_id` ASC
		');
		$this->db->bind($request_id);
		if($iresource_id>0) $this->db->bind($iresource_id);
		return $this->db->select();
	}#end function





	/*
	 * Добавление комментария к заявке
	 */
	public function commentAdd($fields=array()){

		if(empty($fields)) return false;
		$add_fields = array_intersect(array_keys($this->defaultRequestCommentRecord), array_keys($fields));
		$adds = array();
		if(empty($add_fields)) return false;
		foreach($add_fields as $add_field){
			$adds[$add_field] = $fields[$add_field];
		}
		$fields = array_merge($this->defaultRequestCommentRecord, $adds);
		$ins_names=array();
		$ins_q=array();
		$binds=array();
		foreach($fields as $field=>$value){
			$ins_names[]='`'.$field.'`';
			$ins_q[]='?';
			$binds[]=$value;
		}
		$this->db->prepare('INSERT INTO `request_comments` ('.implode(',',$ins_names).')VALUES('.implode(',',$ins_q).')');
		foreach($binds as $bind) $this->db->bind($bind);
		if(($comment_id = $this->db->insert())===false) return false;

		return $comment_id;
	}#end function





	/*
	 * Добавление или изменение в заявке объекта доступа для информационного ресурса
	 */
	public function setIRole($fields=array()){

		if(empty($fields)) return false;
		$request_id		= (empty($fields['request_id']) ? 0 : intval($fields['request_id']));
		$iresource_id 	= (empty($fields['iresource_id']) ? 0 : intval($fields['iresource_id']));
		$irole_id 		= (empty($fields['irole_id']) ? 0 : intval($fields['irole_id']));
		$ir_type 		= (empty($fields['ir_type']) ? 0 : intval($fields['ir_type']));
		$gatekeeper_id 	= (empty($fields['gatekeeper_id']) ? User::_getEmployerID() : intval($fields['gatekeeper_id']));
		$update_type 	= (!isset($fields['update_type']) ? false : intval($fields['update_type']));
		$ir_selected 	= (empty($fields['ir_selected']) ? $ir_type : intval($fields['ir_selected']));

		if(empty($request_id) || empty($iresource_id) || empty($irole_id)) return false;

		$request_roles_table = $this->getRIRoleDBTableName($request_id);

		$rrole = $this->db->selectRecord('SELECT * FROM `'.$request_roles_table.'` WHERE `request_id`='.$request_id.' AND `iresource_id`='.$iresource_id.' AND `irole_id`='.$irole_id.' LIMIT 1');
		$rrole_id = (isset($rrole['id']) ? $rrole['id'] : null);

		if(empty($rrole_id)){
			if($update_type===false) $update_type = 1;
			$this->db->prepare('INSERT INTO `'.$request_roles_table.'` (`request_id`,`iresource_id`,`irole_id`,`ir_type`,`ir_selected`,`gatekeeper_id`,`update_type`,`timestamp`) VALUES (?,?,?,?,?,?,?,?)');
			$this->db->bind($request_id);
			$this->db->bind($iresource_id);
			$this->db->bind($irole_id);
			$this->db->bind($ir_type);
			$this->db->bind($ir_selected);
			$this->db->bind($gatekeeper_id);
			$this->db->bind($update_type);
			$this->db->bind(date('Y-m-d H:i:s'));
			if(($rrole_id = $this->db->insert())===false)return false;
		}else{
			if($update_type===false){
				$update_type = ($ir_selected == 0 ? 3 : 2);
			}
			$this->db->prepare('UPDATE `'.$request_roles_table.'` SET `ir_selected`=?,`gatekeeper_id`=?,`update_type`=?,`timestamp`=? WHERE `id`=?');
			$this->db->bind($ir_selected);
			$this->db->bind($gatekeeper_id);
			$this->db->bind($update_type);
			$this->db->bind(date('Y-m-d H:i:s'));
			$this->db->bind($rrole_id);
			if($this->db->update()===false) return false;
		}

		return $rrole_id;
	}#end function






}#end class

?>
