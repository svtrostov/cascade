<?php
/*==================================================================================================
Описание: Заявка
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');




class Main_MsgPool{
	use Trait_RequestRoles;

	/*==============================================================================================
	Переменные класса
	==============================================================================================*/

	public $options = array(
		'db' => null,
		'request_id' => 0
	);

	private $db 			= null;		#Указатель на экземпляр базы данных
	private $dbtoday		= '';		#Сегодняшняя дата
	private $dbtimestamp	= '';		#Сегодняшняя дата и время
	private $request_id		= 0;		#Текущий идентификатор заявки
	private $fullinfo		= false;	#Признак запроса полной информации по заявке


	#Кеш заявки
	public $cache = array(
		'info'		=> null,			#Информация по заявке из requests
		'iresources'=> null,			#ИР в заявке из request_iresources
		'roles'		=> null,			#Объекты доступа из request _ roles
		'steps'		=> null,			#Этапы согласования по заявке из request _ steps
		'routes'	=> array(),			#Маршруты согласования из routes
		'routesteps'=> array(),			#Шаги согласования для маршрутов из route _ steps
		'employer_groups'=>array()		#Группы сотрудника
	);



	/*==============================================================================================
	Инициализация
	==============================================================================================*/


	/*
	 * Конструктор класса
	 */
	public function __construct($request_id=0, $db=null){
		$this->dbtoday		= date('Y-m-d');
		$this->dbtimestamp	= date('Y-m-d H:i:s');
		$this->db = empty($db) ? Database::getInstance('main') : $db;
		$request_id = intval($request_id);
		if($request_id > 0) $this->open($request_id);

	}#end function



	/*==============================================================================================
	Работа с базой данных
	==============================================================================================*/



	/*
	 * Проверка, является ли указанный сотрудник заявителем или куратором по заявке
	 */
	public function dbIsEmployerRequest($employer_id=0, $request_id=0){
		$employer_id = intval($employer_id);
		$request_id = intval($request_id);
		$this->db->prepare('SELECT count(*) FROM `requests` WHERE `request_id`=? AND (`employer_id`=? OR `curator_id`=?) LIMIT 1');
		$this->db->bind($request_id);
		$this->db->bind($employer_id);
		$this->db->bind($employer_id);
		return ($this->db->result() == 1);
	}#end function





	/*
	 * Получение информации о заявке из базы данных
	 */
	public function dbGetInfo($request_id=0, $fullinfo=false){
		$this->db->prepare('
			SELECT 
				REQ.`request_id` as `request_id`,
				REQ.`request_type` as `request_type`,
				REQ.`curator_id` as `curator_id`,
				REQ.`employer_id` as `employer_id`,
				REQ.`company_id` as `company_id`,
				REQ.`cp_id` as `post_uid`,
				REQ.`template_id` as `template_id`,
				DATE_FORMAT(REQ.`timestamp`, "%d.%m.%Y") as `create_date`,
				REQ.`phone` as `phone`,
				REQ.`email` as `email`
				?
			FROM `requests` as REQ
				?
			WHERE REQ.`request_id`=?
			LIMIT 1
		');
		if($fullinfo){
			$this->db->bindSql(',
				EMP.`search_name` as `employer_name`,
				IFNULL(CEMP.`search_name`, "Administrator") as `curator_name`,
				C.`full_name` as `company_name`,
				P.`full_name` as `post_name`
			');
			$this->db->bindSql('
				INNER JOIN `employers` as EMP ON EMP.`employer_id`=REQ.`employer_id`
				INNER JOIN `companies` as C ON C.`company_id`=REQ.`company_id`
				INNER JOIN `company_posts` as CP ON CP.`post_uid`=REQ.`cp_id`
				INNER JOIN `posts` as P ON P.`post_id` = CP.`post_id`
				LEFT JOIN `employers` as CEMP ON CEMP.`employer_id`=REQ.`curator_id`
			');
		}else{
			$this->db->bindSql('');
			$this->db->bindSql('');
		}
		$this->db->bind($request_id);
		return $this->db->selectRecord();
	}#end function



	/*
	 * Получение информации об информационных ресурсах заявки
	 */
	public function dbGetIResource($request_id=0, $iresource_id=0, $single=false, $forupdate=false){
		$this->db->prepare('
			SELECT
				RIR.*,
				IR.`full_name` as `iresource_name`
			FROM `request_iresources` as RIR
				INNER JOIN `iresources` as IR ON IR.`iresource_id`=RIR.`iresource_id`
			WHERE RIR.`request_id`=? ?
		');
		$this->db->bind($request_id);
		if($iresource_id>0 && $single){
			$this->db->bindSql('AND RIR.`iresource_id`='.intval($iresource_id).' LIMIT 1'.($forupdate ? ' FOR UPDATE' :''));
			return $this->db->selectRecord();
		}
		$this->db->bindSql(($forupdate ? ' FOR UPDATE' :''));
		return $this->db->selectByKey('iresource_id');
	}#end function






	/*
	 * Получение списка объектов доступа в заявке
	 */
	public function dbGetIRoles($request_id=0, $iresource_id=0, $fullinfo=false){
		$request_roles_table = $this->getRIRoleDBTableName($request_id);
		$this->db->prepare('
			SELECT 
				REQROLES.`id` as `rrole_id`,
				REQROLES.`request_id` as `request_id`,
				REQROLES.`iresource_id` as `iresource_id`,
				REQROLES.`irole_id` as `irole_id`,
				REQROLES.`ir_type` as `ir_request`,
				REQROLES.`ir_selected` as `ir_selected`,
				REQROLES.`gatekeeper_id` as `gatekeeper_id`,
				REQROLES.`update_type` as `update_type`,
				DATE_FORMAT(REQROLES.`timestamp`, "%d.%m.%Y %H:%i:%s") as `timestamp`
				?
			FROM `'.$request_roles_table.'` as REQROLES
			?
			WHERE REQROLES.`request_id`=? AND REQROLES.`iresource_id`=?
		');
		if($fullinfo){
			$this->db->bindSql(', 
				IROLE.`full_name` as `irole_name`,
				IROLE.`description` as `irole_desc`,
				IROLE.`owner_id` as `owner_id`,
				IF(IROLE.`owner_id`=0, null, IROLEOWN.`full_name`)  as `owner_name`,
				IF(REQROLES.`update_type`=0, null, EMP.`search_name`)  as `gatekeeper_name`,
				IROLE.`weight` as `weight`
			');
			$this->db->bindSql('
				INNER JOIN `iroles` as IROLE ON IROLE.`irole_id`=REQROLES.`irole_id` 
				LEFT JOIN `iroles` as IROLEOWN ON IROLEOWN.`irole_id`=IROLE.`owner_id`
				LEFT JOIN `employers` as EMP ON EMP.`employer_id`=REQROLES.`gatekeeper_id`
			');
		}else{
			$this->db->bindSql('');
			$this->db->bindSql('');
		}
		$this->db->bind($request_id);
		$this->db->bind($iresource_id);
		return $this->db->selectByKey('irole_id');
	}#end function





	/*
	 * Получение списка объектов доступа информационного ресурса
	 */
	public function dbGetAllIRoles($request_id=0, $iresource_id=0, $employer_id=0, $raw_data=false, $only_changed_roles=false){

		$iresource_info = isset($this->cache['iresources'][$iresource_id]) ? $this->cache['iresources'][$iresource_id] : array();
		$request_roles_table = $this->getRIRoleDBTableName($request_id);

		#Получение списка объектов ИР
		$this->db->prepare('
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
				CR.`ir_type` as `ir_current`,
				RR.`request_id` as `request_id`,
				RR.`ir_type` as `ir_request`,
				RR.`gatekeeper_id` as `gatekeeper_id`,
				IF(RR.`update_type`=0, null, EMP.`search_name`)  as `gatekeeper_name`,
				RR.`update_type` as `update_type`,
				RR.`ir_selected` as `ir_selected`,
				DATE_FORMAT(RR.`timestamp`, "%d.%m.%Y %h:%i:%s") as `update_time`
			FROM 
				`iroles` as IR
				LEFT JOIN `complete_roles` as CR ON CR.`iresource_id`=? AND CR.`irole_id`=IR.`irole_id` AND CR.`employer_id`=?
				LEFT JOIN `'.$request_roles_table.'` as RR ON RR.`request_id`=? AND RR.`iresource_id`=? AND RR.`irole_id`=IR.`irole_id`
				LEFT JOIN `employers` as EMP ON EMP.`employer_id`=RR.`gatekeeper_id`
			WHERE 
				IR.`iresource_id`=? AND IR.`is_lock`=0
			ORDER BY 
				IR.`full_name`
		');
		$this->db->bind($iresource_id);
		$this->db->bind($iresource_id);
		$this->db->bind($employer_id);
		$this->db->bind($request_id);
		$this->db->bind($iresource_id);
		$this->db->bind($iresource_id);
		if( ($results = $this->db->select(null, MYSQL_ASSOC)) === false) return false;

		#Возвращаем сырые данные если RAW запрос
		if($raw_data) return $results;
		
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
	 * Получение сипска этапов согласования в заявке
	 */
	public function dbGetSteps($request_id=0, $iresource_id=0){
		$this->db->prepare('SELECT * FROM `request_steps` WHERE `request_id`=? AND `iresource_id`=?');
		$this->db->bind($request_id);
		$this->db->bind($iresource_id);
		return $this->db->selectByKey('rstep_id');
	}#end function



	/*
	 * Получение информации о маршруте
	 */
	public function dbGetRoute($route_id=0){
		$this->db->prepare('SELECT * FROM `routes` WHERE `route_id`=? LIMIT 1');
		$this->db->bind($route_id);
		return $this->db->selectRecord();
	}#end function



	/*
	 * Получение шагов маршрута
	 */
	public function dbGetRouteSteps($route_id=0){
		$this->db->prepare('SELECT * FROM `route_steps` WHERE `route_id`=?');
		$this->db->bind($route_id);
		return $this->db->selectByKey('step_uid');
	}#end function





	/*
	 * Комментарии к заявке
	 */
	public function dbGetComments($request_id=0, $iresource_id=0){
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







	/*==============================================================================================
	РАБОТА С МАРШРУТАМИ
	==============================================================================================*/

	/*
	 * Функция выбора маршрутов, удовлетворяющих условию
	 */
	public function routeSelect($data=null){

		if(!$this->request_id || empty($this->db) || empty($data) || !is_array($data)) return false;

		$employer_id		= $this->cache['info']['employer_id'];
		$company_id			= $this->cache['info']['company_id'];
		$post_uid			= $this->cache['info']['post_uid'];
		$is_template		= ($this->cache['info']['template_id'] > 0 ? 1 : 0);
		$iresource_id		= (empty($data['iresource_id']) ? 0 : intval($data['iresource_id']));
		$single				= (isset($data['single']) ? (!empty($data['single']) ? true : false) : true);
		$iresources 		= ($iresource_id > 0 ? array($iresource_id) : array_keys($this->cache['iresources']));
		if(empty($iresources)) return false;
		if(!isset($this->cache['info']['employer_groups'])){
			$employer_groups= $this->db->selectFromField('group_id', 'SELECT `group_id` FROM `employer_groups` WHERE `employer_id` ='.$employer_id);
			$this->cache['info']['employer_groups'] = $employer_groups;
		}else{
			$employer_groups= $this->cache['info']['employer_groups'];
		}

		$sql_groups 	= empty($employer_groups) ? '0' : implode(',',$employer_groups);
		$sql_iresources = implode(',',$iresources);

		$this->db->prepare('
			SELECT * FROM(
				SELECT 
					ROUT.`route_id`,
					( IF(PARAM.`for_user`>0 AND PARAM.`for_user`=?, 20 ,0) + 
						IF(PARAM.`for_cp`>0 AND PARAM.`for_cp`=?, 10 ,0) + 
						IF(PARAM.`for_group`>0 AND PARAM.`for_group` IN ('.$sql_groups.'), 5 ,0) + 
						IF(PARAM.`for_resource`>0 AND PARAM.`for_resource` IN ('.$sql_iresources.'), 3 ,0) + 
						IF(PARAM.`for_company`>0 AND PARAM.`for_company`=?, 1 ,0)
					) as `weight`,
					PARAM.`for_user` as `for_user`,
					PARAM.`for_cp` as `for_cp`,
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
					(PARAM.`for_cp`=0 OR (PARAM.`for_cp`>0 AND PARAM.`for_cp`=?)) AND
					(PARAM.`for_group`=0 OR (PARAM.`for_group`>0 AND PARAM.`for_group` IN ('.$sql_groups.'))) AND
					(PARAM.`for_resource`=0 OR (PARAM.`for_resource`>0 AND PARAM.`for_resource` IN ('.$sql_iresources.'))) AND
					(PARAM.`for_company`=0 OR (PARAM.`for_company`>0 AND PARAM.`for_company`=?))
			) as `RT`
			WHERE RT.`weight` > 0 || RT.`is_default`=1
			ORDER BY RT.`priority` DESC, RT.`weight` DESC
			'.($single==true ? 'LIMIT 1':'').'
		');
		$this->db->bind($employer_id);
		$this->db->bind($post_uid);
		$this->db->bind($company_id);
		$this->db->bind($is_template);
		$this->db->bind($employer_id);
		$this->db->bind($post_uid);
		$this->db->bind($company_id);
		if($single){
			if(($all_routes = $this->db->selectRecord()) === false) return false;
		}else{
			if(($all_routes = $this->db->select()) === false) return false;
		}

		if(!is_array($all_routes))	return false;

		return $all_routes;
	}#end function





	/*
	 * Получить сведения о маршруте
	 */
	public function getRoute($route_id=0){
		$route_id = intval($route_id);
		if(!isset($this->cache['routes'][$route_id])){
			$this->cache['routes'][$route_id] = $this->dbGetRoute($route_id);
			$this->cache['routesteps'][$route_id] = $this->dbGetRouteSteps($route_id);
		}
		return $this->cache['routes'][$route_id];
	}#end function




	/*
	 * Получить сведения о шагах маршрута
	 */
	public function getRouteSteps($route_id=0){
		$route_id = intval($route_id);
		if(!isset($this->cache['routesteps'][$route_id])){
			$this->cache['routes'][$route_id] = $this->dbGetRoute($route_id);
			$this->cache['routesteps'][$route_id] = $this->dbGetRouteSteps($route_id);
		}
		return $this->cache['routesteps'][$route_id];
	}#end function







	/*==============================================================================================
	ДОСТУП К ЗАЯВКЕ НА ПРОСМОТР ВНЕ ЭТАПОВ СОГЛАСОВАНИЯ
	==============================================================================================*/


	/*
	 * Добавление сотруднику разрешения просматривать заявку вне этапа согласования
	 */
	public function addWatcher($data=null){

		if(!$this->request_id || empty($this->db) || empty($data) || !is_array($data)) return false;
		$employer_id	= (empty($data['employer_id']) ? User::_getEmployerID() : intval($data['employer_id']));
		$request_id		= (empty($data['request_id']) ? $this->request_id : intval($data['request_id']));
		$iresource_id	= (empty($data['iresource_id']) ? 0 : intval($data['iresource_id']));
		$watch_types	= array();
		$is_new_watcher	= false;
		$fields 		= array('is_owner','is_curator','is_gatekeeper','is_performer','is_watcher');
		foreach($fields as $field){
			if(!empty($data[$field])) $watch_types[]=$field;
		}
		
		if(empty($employer_id)||empty($watch_types)) return false;

		#Проверка существования записи
		$this->db->prepare('SELECT `id` FROM `request_watch` WHERE `request_id`=? AND `iresource_id`=? AND `employer_id`=? LIMIT 1');
		$this->db->bind($request_id);
		$this->db->bind($iresource_id);
		$this->db->bind($employer_id);
		$id = $this->db->result();

		#Новая запись
		if(empty($id)){
			$this->db->prepare('INSERT INTO `request_watch` (`request_id`,`iresource_id`,`employer_id`,`is_watched`,`is_owner`,`is_curator`,`is_gatekeeper`,`is_performer`,`is_watcher`) VALUES (?,?,?,?,?,?,?,?,?)');
			$this->db->bind($request_id);
			$this->db->bind($iresource_id);
			$this->db->bind($employer_id);
			$this->db->bind(0);
			$this->db->bind((in_array('is_owner',$watch_types)?1:0));
			$this->db->bind((in_array('is_curator',$watch_types)?1:0));
			$this->db->bind((in_array('is_gatekeeper',$watch_types)?1:0));
			$this->db->bind((in_array('is_performer',$watch_types)?1:0));
			$this->db->bind((in_array('is_watcher',$watch_types)?1:0));
			if(($id = $this->db->insert())===false) return false;
			$is_new_watcher = true;
		}else{
			$sql = '';
			foreach($watch_types as $key){
				$sql.=(empty($sql)?'':',').'`'.$key.'`=1';
			}
			$this->db->prepare('UPDATE `request_watch` SET ? WHERE `id`=?');
			$this->db->bindSql($sql);
			$this->db->bind($id);
			if($this->db->update()===false) return false;
		}

		return true;
	}#end function














	/*==============================================================================================
	ФУНКЦИИ РАБОТЫ С ЗАЯВКАМИ
	==============================================================================================*/



	/*
	 * Открытие существующей заявки
	 */
	public function open($data=0, $fullinfo=false, $iresource_id=0, $alliroles=false, $iresourceforupdate=false, $onlychangedroles=false){

		if(!is_array($data)){
			$data = array(
				'request_id'=>$data,
				'fullinfo'=>$fullinfo,
				'iresource_id'=>$iresource_id,
				'alliroles'=>$alliroles,
				'onlychangedroles'=>$onlychangedroles,
				'iresourceforupdate'=>$iresourceforupdate
			);
		}

		$data = array_merge(array(
				'request_id'=>0,
				'fullinfo'=>false,
				'iresource_id'=>0,
				'alliroles'=>false,
				'onlychangedroles'=>false,
				'iresourceforupdate'=>false
		), $data);

		$request_id = intval($data['request_id']);
		if(empty($this->db) || empty($request_id)) return false;
		$iresource_id = intval($data['iresource_id']);
		$fullinfo = empty($data['fullinfo'])?false:true;
		$alliroles = empty($data['alliroles'])?false:true;
		$iresourceforupdate = empty($data['iresourceforupdate'])?false:true;
		$onlychangedroles = empty($data['onlychangedroles'])?false:true;

		$this->fullinfo = $fullinfo;

		$this->cache['info'] = $this->dbGetInfo($request_id, $fullinfo);
		if(!is_array($this->cache['info'])) return false;

		$this->cache['iresources'] = $this->dbGetIResource($request_id, $iresource_id, false, $iresourceforupdate);
		if(!is_array($this->cache['iresources'])) return false;

		$this->cache['roles'] = array();
		$this->cache['steps'] = array();

		$this->request_id = $request_id;

		foreach($this->cache['iresources'] as $iresource_id => $item){
			$route_id = $item['route_id'];
			$this->cache['roles'][$iresource_id] = ($alliroles  ? $this->dbGetAllIRoles($request_id, $iresource_id,$this->cache['info']['employer_id'], false, $onlychangedroles) : $this->dbGetIRoles($request_id, $iresource_id, $fullinfo));
			$this->cache['steps'][$iresource_id] = $this->dbGetSteps($request_id, $iresource_id);
			$this->getRoute($route_id);

			//fullinfo
			if($fullinfo){

				$this->cache['iresources'][$iresource_id]['comments'] = $this->dbGetComments($request_id, $iresource_id);

				$this->cache['iresources'][$iresource_id]['step_info'] = null;

				if(empty($item['current_step'])||($item['route_status']!=1&&$item['route_status']!=2)) continue;
				$current_step = isset($this->cache['steps'][$iresource_id][$item['current_step']]) ? $this->cache['steps'][$iresource_id][$item['current_step']] : null;
				if(empty($current_step)) continue;

				$step_uid = $current_step['step_uid'];

				$route_step = isset($this->cache['routesteps'][$route_id][$step_uid])&&is_array($this->cache['routesteps'][$route_id][$step_uid]) ? $this->cache['routesteps'][$route_id][$step_uid] : null;
				if(empty($route_step)) continue;

				if($route_step['step_type']!=2) continue;

				$gatekeepers_ids = $this->getRouteStepGatekeepers(array(
					'gatekeeper_type'	=> $route_step['gatekeeper_type'],
					'gatekeeper_id'		=> $route_step['gatekeeper_id'],
					'iresource_id'		=> $iresource_id
				));

				$gatekeepers = null;
				$assistants = null;

				if(!empty($gatekeepers_ids)){
					$gatekeepers = $this->db->select('SELECT `employer_id`,`search_name` as `employer_name`,`phone`,`email` FROM `employers` WHERE `employer_id` IN ('.implode(',',$gatekeepers_ids).')');
					$assistants = $this->getGatekeeperAssistants($gatekeepers_ids, true);
				}
				$this->cache['iresources'][$iresource_id]['step_info'] = array(
					'type'=> $this->getGatekeeperTypeString($route_step['gatekeeper_type']),
					'role'=> $this->getGatekeeperRoleString($route_step['gatekeeper_role']),
					'gatekeepers'=> $gatekeepers,
					'assistants'=> $assistants
				);

			}//fullinfo

		}//foreach

		return true;
	}#end function







	/*
	 * Создание заявки
	 */
	public function create($data=null){
		if(empty($this->db) || empty($data) || !is_array($data)) return false;

		$request_type	= (empty($data['request_type']) ? 2 : intval($data['request_type']));
		$employer_id	= (empty($data['employer_id']) ? User::_getEmployerID() : intval($data['employer_id']));
		$curator_id		= (empty($data['curator_id']) ? $employer_id : intval($data['curator_id']));
		$company_id		= (empty($data['company_id']) ? 0 : intval($data['company_id']));
		$post_uid		= (empty($data['post_uid']) ? 0 : (is_numeric($data['post_uid'])?$data['post_uid']:0));
		$template_id	= (empty($data['template_id']) ? 0 : intval($data['template_id']));
		$phone			= (empty($data['phone']) ? User::_get('phone') : $data['phone']);
		$email			= (empty($data['email']) ? User::_get('email') : $data['email']);
		if(empty($employer_id) || empty($company_id) || empty($post_uid)) return false;

		#Создание заявки
		$this->db->prepare('INSERT INTO `requests` (`request_type`,`curator_id`,`employer_id`,`company_id`,`cp_id`,`template_id`,`timestamp`,`phone`,`email`) VALUES (?,?,?,?,?,?,?,?,?)');
		$this->db->bind($request_type);
		$this->db->bind($curator_id);
		$this->db->bind($employer_id);
		$this->db->bind($company_id);
		$this->db->bind($post_uid);
		$this->db->bind($template_id);
		$this->db->bind($this->dbtimestamp);
		$this->db->bind($phone);
		$this->db->bind($email);

		if(($request_id = $this->db->insert())===false) return false;

		$this->request_id = $request_id;
		$this->cache['info'] = array(
			'request_id'	=> $request_id,
			'request_type'	=> $request_type,
			'curator_id'	=> $curator_id,
			'employer_id'	=> $employer_id,
			'company_id'	=> $company_id,
			'post_uid'		=> $post_uid,
			'template_id'	=> $template_id,
			'phone'			=> $phone,
			'email'			=> $email,
			'create_date'	=> date('d.m.Y')
		);
		$this->cache['iresources'] = array();
		$this->cache['roles'] = array();
		$this->cache['steps'] = array();

		#Добавление разрешений на просмотр заявки вне этапов согласования
		if($employer_id!=$curator_id){
			$this->addWatcher(array(
				'employer_id'	=> $employer_id,
				'is_owner'		=> true
			));
			$this->addWatcher(array(
				'employer_id'	=> $curator_id,
				'is_curator'	=> true
			));
		}else{
			$this->addWatcher(array(
				'employer_id'	=> $curator_id,
				'is_owner'		=> true,
				'is_curator'	=> true
			));
		}

		return $request_id;
	}#end function





	/*
	 * Добавление в заявку информационного ресурса или изменение информации
	 */
	public function setIResource($data=null){

		if(!$this->request_id || empty($this->db) || empty($data) || !is_array($data)) return false;

		$iresource_id		= (empty($data['iresource_id']) ? 0 : intval($data['iresource_id']));
		$route_id			= (empty($data['route_id']) ? 0 : intval($data['route_id']));
		$route_status		= (!isset($data['route_status']) ? 1 : intval($data['route_status']));
		$route_status_desc	= (empty($data['route_status_desc']) ? ($route_status==1?'В процессе согласования':'') : $data['route_status_desc']);
		$current_step		= (empty($data['current_step']) ? 0 : intval($data['current_step']));
		$is_new_iresource	= false;
		$iroles				= array();
		$steps				= array();

		if(empty($iresource_id)) return false;

		$rires_id = $this->db->result('SELECT `rires_id` FROM `request_iresources` WHERE `request_id`='.$this->request_id.' AND `iresource_id`='.$iresource_id.' LIMIT 1');

		#Записи нет - новый ИР
		if(empty($rires_id)){

			#Если не задан маршрут согласования, автоматический выбор
			if(empty($route_id)){
				$route_info = $this->routeSelect(array(
					'iresource_id'	=> $iresource_id,
					'single'		=> true
				));
				//Маршрут не найден
				if(empty($route_info)||!is_array($route_info)) return false;
				$route_id = $route_info['route_id'];
				if(empty($route_id))  return false;
			}

			#Идентификатор маршрута задан некорректно
			if(!is_array($this->getRoute($route_id))) return false;

			$this->db->prepare('INSERT INTO `request_iresources` (`request_id`,`iresource_id`,`route_id`,`route_status`,`route_status_desc`,`current_step`) VALUES (?,?,?,?,?,?)');
			$this->db->bind($this->request_id);
			$this->db->bind($iresource_id);
			$this->db->bind($route_id);
			$this->db->bind($route_status);
			$this->db->bind($route_status_desc);
			$this->db->bind($current_step);
			if(($rires_id = $this->db->insert())===false) return false;
			$is_new_iresource = true;

		}else{

			$this->db->prepare('UPDATE `request_iresources` SET `route_status`=?,`route_status_desc`=?,`current_step`=? WHERE `rires_id`=?');
			$this->db->bind($route_status);
			$this->db->bind($route_status_desc);
			$this->db->bind($current_step);
			$this->db->bind($rires_id);
			if($this->db->update()===false) return false;

		}

		if(!$is_new_iresource && !isset($this->cache['iresources'][$iresource_id])){
			$is_new_iresource = true;
			$iroles	= $this->dbGetIRoles($this->request_id, $iresource_id);
			$steps	= $this->dbGetSteps($this->request_id, $iresource_id);
		}

		if($is_new_iresource){
			$this->cache['iresources'][$iresource_id] = array(
				'rires_id'			=> $rires_id,
				'request_id'		=> $this->request_id,
				'iresource_id'		=> $iresource_id,
				'route_id'			=> $route_id,
				'route_status'		=> $route_status,
				'route_status_desc'	=> $route_status_desc,
				'current_step'		=> $current_step
			);
			$this->cache['roles'][$iresource_id] = $iroles;
			$this->cache['steps'][$iresource_id] = $steps;
		}else{
			$this->cache['iresources'][$iresource_id]['route_status'] = $route_status;
			$this->cache['iresources'][$iresource_id]['route_status_desc'] = $route_status_desc;
			$this->cache['iresources'][$iresource_id]['current_step'] = $current_step;
		}


		return $rires_id;
	}#end function




	/*
	 * Добавление в заявку объекта доступа для информационного ресурса или его изменение
	 */
	public function setIRole($data=null){

		if(!$this->request_id || empty($this->db) || empty($data) || !is_array($data)) return false;

		$iresource_id 	= (empty($data['iresource_id']) ? 0 : intval($data['iresource_id']));
		$irole_id 		= (empty($data['irole_id']) ? 0 : intval($data['irole_id']));
		$ir_type 		= (empty($data['ir_type']) ? 0 : intval($data['ir_type']));
		$gatekeeper_id 	= (empty($data['gatekeeper_id']) ? User::_getEmployerID() : intval($data['gatekeeper_id']));
		$update_type 	= (!isset($data['update_type']) ? false : intval($data['update_type']));
		$ir_selected 	= (empty($data['ir_selected']) ? $ir_type : intval($data['ir_selected']));
		$is_new_irole	= false;

		if(empty($iresource_id) || empty($irole_id)) return false;
		if(!isset($this->cache['iresources'][$iresource_id])) return false;
		$request_roles_table = $this->getRIRoleDBTableName($request_id);

		$rrole = $this->db->selectRecord('SELECT * FROM `'.$request_roles_table.'` WHERE `request_id`='.$this->request_id.' AND `iresource_id`='.$iresource_id.' AND `irole_id`='.$irole_id.' LIMIT 1');
		$rrole_id = (isset($rrole['id']) ? $rrole['id'] : null);

		if(empty($rrole_id)){
			if($update_type===false) $update_type = 0;
			$this->db->prepare('INSERT INTO `'.$request_roles_table.'` (`request_id`,`iresource_id`,`irole_id`,`ir_type`,`ir_selected`,`gatekeeper_id`,`update_type`,`timestamp`) VALUES (?,?,?,?,?,?,?,?)');
			$this->db->bind($this->request_id);
			$this->db->bind($iresource_id);
			$this->db->bind($irole_id);
			$this->db->bind($ir_type);
			$this->db->bind($ir_selected);
			$this->db->bind($gatekeeper_id);
			$this->db->bind($update_type);
			$this->db->bind($this->dbtimestamp);
			if(($rrole_id = $this->db->insert())===false)return false;
			$is_new_irole = true;
		}else{
			if($update_type===false){
				$update_type = ($ir_selected == 0 ? 3 : 2);
			}
			$this->db->prepare('UPDATE `'.$request_roles_table.'` SET `ir_selected`=?,`gatekeeper_id`=?,`update_type`=?,`timestamp`=? WHERE `id`=?');
			$this->db->bind($ir_selected);
			$this->db->bind($gatekeeper_id);
			$this->db->bind($update_type);
			$this->db->bind($this->dbtimestamp);
			$this->db->bind($rrole_id);
			if($this->db->update()===false) return false;
		}

		//print_r($this->db->parseTemplate());

		if(!$is_new_irole && !isset($this->cache['roles'][$iresource_id][$irole_id])) $is_new_irole = true;

		if($is_new_irole){
			$this->cache['roles'][$iresource_id][$irole_id] = array(
				'rrole_id'			=> $rrole_id,
				'request_id'		=> $this->request_id,
				'iresource_id'		=> $iresource_id,
				'irole_id'			=> $irole_id,
				'ir_type'			=> $ir_type,
				'ir_selected'		=> $ir_selected,
				'gatekeeper_id'		=> $gatekeeper_id,
				'update_type'		=> $update_type,
				'timestamp'			=> date('d.m.Y H:i:s')
			);
		}else{
			$this->cache['roles'][$iresource_id][$irole_id]['ir_selected']	= $ir_selected;
			$this->cache['roles'][$iresource_id][$irole_id]['gatekeeper_id']= $gatekeeper_id;
			$this->cache['roles'][$iresource_id][$irole_id]['update_type']	= $update_type;
			$this->cache['roles'][$iresource_id][$irole_id]['timestamp']	= date('d.m.Y H:i:s');
		}

		return $rrole_id;
	}#end function









	/*==============================================================================================
	ЭТАПЫ СОГЛАСОВАНИЯ ЗАЯВКИ
	==============================================================================================*/


	/*
	 * Начало согласования, перевод заявки на первый этап согласования,
	 * по сути является инициализацией процесса согласования заявки
	 */
	public function toFirstStep($iresource_id=0){

		if(!$this->request_id || empty($this->db)) return false;
		$iresource_id 	= (empty($iresource_id) ? 0 : intval($iresource_id));

		if($iresource_id > 0){
			if(!isset($this->cache['iresources'][$iresource_id])) return false;
			$iresources = array($iresource_id);
		}else{
			$iresources = array_keys($this->cache['iresources']);
		}

		#Перевод каждого информационного ресурса в заявке на первый этап согласования
		foreach($iresources as $iresource_id){

			//Заявка не в работе
			if($this->cache['iresources'][$iresource_id]['route_status'] != 1) continue;

			//Не задан маршрут
			if(empty($this->cache['iresources'][$iresource_id]['route_id'])){
				$this->setIResource(array(
					'iresource_id'		=> $iresource_id,
					'route_status'		=> 0,
					'route_status_desc'	=> '[ERROR]: Заявка отменена, не задан маршрут согласования',
					'current_step'		=> 0
				));
				continue;
			}

			$route_id = $this->cache['iresources'][$iresource_id]['route_id'];

			//Маршрут не найден
			if(!is_array($this->cache['routes'][$route_id])){
				$this->setIResource(array(
					'iresource_id'		=> $iresource_id,
					'route_status'		=> 0,
					'route_status_desc'	=> '[ERROR]: Заявка отменена, выбранный маршрут согласования не найден',
					'current_step'		=> 0
				));
				continue;
			}

			//Маршрут не содержит этапов согласования
			if(!is_array($this->cache['routesteps'][$route_id])){
				$this->setIResource(array(
					'iresource_id'		=> $iresource_id,
					'route_status'		=> 0,
					'route_status_desc'	=> '[ERROR]: Заявка отменена, выбранный маршрут не содержит этапов согласования',
					'current_step'		=> 0
				));
				continue;
			}

			//Вычисление UID начального блока маршрута
			$step_uid = $this->uidBeginBlock($route_id);

			//Не найден блок начала маршрута
			if(!isset($this->cache['routesteps'][$route_id][$step_uid])||!is_array($this->cache['routesteps'][$route_id][$step_uid])){
				$this->setIResource(array(
					'iresource_id'		=> $iresource_id,
					'route_status'		=> 0,
					'route_status_desc'	=> '[ERROR]: Заявка отменена, не найдено начало маршрута',
					'current_step'		=> 0
				));
				continue;
			}

			//UID Первого этапа согласования
			$step_uid = $this->cache['routesteps'][$route_id][$step_uid]['step_yes'];

			//Перевод на первый этап согласования
			$processResult = $this->stepProcessing($iresource_id, $route_id, $step_uid);

		}#Перевод каждого информационного ресурса в заявке на первый этап согласования

		return true;
	}#end function





	/*
	 * Перевод заявки на следующий этап согласования
	 * заявка одобрена на текущем шаге, дальше по маршруту
	 */
	public function toStep($iresource_id=0, $step_type='none', $gatekeeper_id=0){

		$step_uid = 0;

		if(!$this->request_id || empty($this->db)) return false;
		$iresource_id 	= (empty($iresource_id) ? 0 : intval($iresource_id));

		if($iresource_id > 0){
			if(!isset($this->cache['iresources'][$iresource_id])) return false;
			$iresources = array($iresource_id);
		}else{
			$iresources = array_keys($this->cache['iresources']);
		}

		#Перевод каждого информационного ресурса в заявке на новый этап согласования
		foreach($iresources as $iresource_id){

			//Заявка не в работе
			if($this->cache['iresources'][$iresource_id]['route_status'] != 1) continue;

			//Не задан маршрут
			if(empty($this->cache['iresources'][$iresource_id]['route_id'])){
				$this->setIResource(array(
					'iresource_id'		=> $iresource_id,
					'route_status'		=> 0,
					'route_status_desc'	=> '[ERROR]: Заявка отменена, не задан маршрут согласования',
					'current_step'		=> 0
				));
				continue;
			}

			$route_id = $this->cache['iresources'][$iresource_id]['route_id'];

			//Маршрут не найден
			if(!is_array($this->cache['routes'][$route_id])){
				$this->setIResource(array(
					'iresource_id'		=> $iresource_id,
					'route_status'		=> 0,
					'route_status_desc'	=> '[ERROR]: Заявка отменена, выбранный маршрут согласования не найден',
					'current_step'		=> 0
				));
				continue;
			}

			//Маршрут не содержит этапов согласования
			if(!is_array($this->cache['routesteps'][$route_id])){
				$this->setIResource(array(
					'iresource_id'		=> $iresource_id,
					'route_status'		=> 0,
					'route_status_desc'	=> '[ERROR]: Заявка отменена, выбранный маршрут не содержит этапов согласования',
					'current_step'		=> 0
				));
				continue;
			}

			switch($step_type){
				case 'empty':
				case 'none': return true;
				case 'first':
				case 'start':
					$step_uid = $this->uidBeginBlock($route_id);
				break;
				case 'next':
				case 'prev':
				case 'approve':
				case 'decline':
					$rstep_id = $this->cache['iresources'][$iresource_id]['current_step'];
					if(!$rstep_id || !isset($this->cache['steps'][$iresource_id][$rstep_id])){
						$step_uid = -1;
						break;
					}
					$step_info = $this->cache['steps'][$iresource_id][$rstep_id];
					$step_uid = $step_info['step_uid'];
					if(!isset($this->cache['routesteps'][$route_id][$step_uid])||!is_array($this->cache['routesteps'][$route_id][$step_uid])){
						$step_uid = -1;
						break;
					}

					$current_step = $this->cache['routesteps'][$route_id][$step_uid];

					//Добавление в пул сообщений уведомлений для гейткиперов, заявка аппрувлена или отклонена
					$this->messagePoolAdd(array(
						'action'			=> ($step_type == 'next' || $step_type == 'approve' ? 'approve' : 'decline'),
						'employers'			=> $this->getRouteStepGatekeepers(array(
													'gatekeeper_type'	=> $current_step['gatekeeper_type'],
													'gatekeeper_id'		=> $current_step['gatekeeper_id'],
													'iresource_id'		=> $iresource_id
						)),
						'gatekeeper_id'		=> $gatekeeper_id,
						'request_id'		=> $this->request_id,
						'iresource_id'		=> $iresource_id,
						'route_id'			=> $route_id,
						'step_uid'			=> $step_uid,
						'gatekeeper_type'	=> $current_step['gatekeeper_type'],
						'gatekeeper_role'	=> $current_step['gatekeeper_role']
					));

					$step_info = $this->cache['routesteps'][$route_id][$step_uid];
					if($step_type == 'next' || $step_type == 'approve'){
						$step_uid = $step_info['step_yes'];
						break;
					}
					$step_uid = $step_info['step_no'];
				break;
				default:
					$step_uid = $step_type;
				break;
			}

			//Не найден блок
			if(!isset($this->cache['routesteps'][$route_id][$step_uid])||!is_array($this->cache['routesteps'][$route_id][$step_uid])){
				$this->setIResource(array(
					'iresource_id'		=> $iresource_id,
					'route_status'		=> 0,
					'route_status_desc'	=> '[ERROR]: Заявка отменена, не найден шаг маршрута',
					'current_step'		=> 0
				));
				continue;
			}

			//Перевод на первый этап согласования
			$processResult = $this->stepProcessing($iresource_id, $route_id, $step_uid);

		}#Перевод каждого информационного ресурса в заявке на первый этап согласования

		return true;
	}#end function






	/*
	 * Генерация UID начального блока маршрута
	 */
	private function uidBeginBlock($route_id=0){
		return	'1'. 										//1 - prefix
				str_pad($route_id, 9, '0', STR_PAD_LEFT).	//9 - route_id: Идентификатор маршрута
				str_pad(1, 2, '0', STR_PAD_LEFT). 			//2 - step_type: Тип шага (1 - начало маршрута, 2 - гейткипер, 3 - конец маршрута ИСПОЛНЕНО, 4 - конец маршрута ОТКЛОНЕНО.)
				str_pad(0, 2, '0', STR_PAD_LEFT).			//2 - gatekeeper_type: Тип гейткипера (1 - конкретный пользователь (user_id), 2 - руководитель сотрудника (boss_id), 3 - руководитель организации (company_id), 4 - владелец ресурса (resource_id), 5 - группа пользователей (group_id), 6 - должность в организации(cp_uid), 7 - группа исполнителей (group_id))
				str_pad(0, 2, '0', STR_PAD_LEFT).			//2 - gatekeeper_role: Роль гейткипера в маршруте (1 - согласование, 2 - утверждение, 3 - исполнение, 4 - уведомление)
				'0000'.										//4 - reserved
				str_pad(0, 20, '0', STR_PAD_LEFT);			//20- gatekeeper_id: Идентификатор гейткипера
	}#end function





	/*
	 * Процедура перевода заявки по маршруту на новый этап согласования
	 */
	private function stepProcessing($iresource_id=0, $route_id=0, $step_uid=null){$iteration=0;while(1){$iteration++;

		#Генерация UID блока начала маршрута, если UID не задан и это первая итерация
		if(empty($step_uid) && $iteration==1){
			$step_uid = $this->uidBeginBlock($route_id);
		}

		//Этап согласования не существует
		if(!isset($this->cache['routesteps'][$route_id][$step_uid])||!is_array($this->cache['routesteps'][$route_id][$step_uid])){
			$this->setIResource(array(
				'iresource_id'		=> $iresource_id,
				'route_status'		=> 0,
				'route_status_desc'	=> '[ERROR]: Заявка отменена, нарушение целостности маршрута',
				'current_step'		=> 0
			));
			return true;
		}


		//Текущий шаг
		$current_step = $this->cache['routesteps'][$route_id][$step_uid];


		//Текущий этап маршрута - блок начала маршрута
		if($current_step['step_type'] == 1){
			$step_uid = $current_step['step_yes'];
			continue;
		}


		//Текущий этап маршрута - блок окончание маршрута, ИСПОЛНЕНО
		if($current_step['step_type'] == 3){
			$this->setIResource(array(
				'iresource_id'		=> $iresource_id,
				'route_status'		=> 100,
				'route_status_desc'	=> 'Заявка исполнена',
				'current_step'		=> 0
			));
			return true;
		}


		//Текущий этап маршрута - блок окончание маршрута, ОТКЛОНЕНО
		if($current_step['step_type'] == 4){
			$this->setIResource(array(
				'iresource_id'		=> $iresource_id,
				'route_status'		=> 0,
				'route_status_desc'	=> 'Заявка отклонена',
				'current_step'		=> 0
			));
			return true;
		}


		//Текущий этап маршрута - неизвестный блок
		if($current_step['step_type'] != 2){
			$this->setIResource(array(
				'iresource_id'		=> $iresource_id,
				'route_status'		=> 0,
				'route_status_desc'	=> '[ERROR]: Заявка отменена, нарушение целостности маршрута',
				'current_step'		=> 0
			));
			return true;
		}

		#Шаг с блоком уведомления о заявке не отображается в маршрутном листе согласования
		if($current_step['gatekeeper_role'] != 4){
			$rstep_id = $this->addRouteStep($iresource_id, $route_id, $step_uid);
		}

		//Получение гейткиперов текущего шага
		$gatekeepers = $this->getRouteStepGatekeepers(array(
			'gatekeeper_type'	=> $current_step['gatekeeper_type'],
			'gatekeeper_id'		=> $current_step['gatekeeper_id'],
			'iresource_id'		=> $iresource_id
		));

		//Если не найден ни один гейткипер
		if(empty($gatekeepers)||!is_array($gatekeepers)){
			//Если на данном шаге нужно только уведомить гейткипера о заявке - переходим на следующий шаг без приостановки заявки
			if($current_step['gatekeeper_role'] == 4){
				$step_uid = $current_step['step_yes'];
				continue;
			}
			$this->setIResource(array(
				'iresource_id'		=> $iresource_id,
				'route_status'		=> 2,
				'route_status_desc'	=> '[PAUSE]: Заявка приостановлена, отсутствует гейткипер: '.$this->getGatekeeperTypeString($current_step['gatekeeper_type']),
				'current_step'		=> $rstep_id
			));
			return true;
		}


		//Если на текущем шаге требуется уведомить гейткипера о заявке
		if($current_step['gatekeeper_role'] == 4){

			//Добавление в пул сообщений уведомлений для гейткиперов
			$this->messagePoolAdd(array(
				'action'			=> 'notice',
				'employers'			=> $gatekeepers,
				'request_id'		=> $this->request_id,
				'iresource_id'		=> $iresource_id,
				'route_id'			=> $route_id,
				'step_uid'			=> $step_uid,
				'gatekeeper_type'	=> $current_step['gatekeeper_type'],
				'gatekeeper_role'	=> $current_step['gatekeeper_role']
			));

			foreach($gatekeepers as $gatekeeper_id){
				$this->addWatcher(array(
					'employer_id'	=> $gatekeeper_id,
					'is_watcher'	=> true
				));
			}
			$step_uid = $current_step['step_yes'];
			continue;
		}


		//Добавление нового шага согласования в историю заявки
		$this->setIResource(array(
			'iresource_id'		=> $iresource_id,
			'current_step'		=> $rstep_id
		));


		//Помимо списка гейткиперов, также получаем список ассистентов гейткиперов
		$assistants = $this->getGatekeeperAssistants($gatekeepers);

		//Добавление в пул сообщений уведомлений для гейткиперов
		//Добавление в пул сообщений уведомлений для ассистентов гейткиперов
		$this->messagePoolAdd(array(
			'action'			=> 'notice',
			'employers'			=> array_merge($gatekeepers, $assistants),
			'request_id'		=> $this->request_id,
			'iresource_id'		=> $iresource_id,
			'route_id'			=> $route_id,
			'step_uid'			=> $step_uid,
			'gatekeeper_type'	=> $current_step['gatekeeper_type'],
			'gatekeeper_role'	=> $current_step['gatekeeper_role']
			
		));


		return true;
	}}#end function





	/*
	 * Добавление нового шага согласования в маршрутный лист заявки
	 */
	private function addRouteStep($iresource_id=0, $route_id=0, $step_uid=null){

		$this->db->prepare('INSERT INTO `request_steps` (`request_id`,`iresource_id`,`route_id`,`step_uid`,`gatekeeper_id`,`assistant_id`,`step_complete`,`is_approved`,`timestamp`) VALUES (?,?,?,?,?,?,?,?,?)');
		$this->db->bind($this->request_id);
		$this->db->bind($iresource_id);
		$this->db->bind($route_id);
		$this->db->bind($step_uid);
		$this->db->bind(0);
		$this->db->bind(0);
		$this->db->bind(0);
		$this->db->bind(0);
		$this->db->bind($this->dbtimestamp);

		return $this->db->insert();
	}#end function





	/*
	 * Добавление в заявку объекта доступа для информационного ресурса или его изменение
	 */
	public function setRouteStep($data=null){

		if(empty($this->db) || empty($data) || !is_array($data)) return false;

		$data['request_id']		= (!isset($data['request_id']) ? $this->request_id : intval($data['request_id']));
		$data['iresource_id']	= (!isset($data['iresource_id']) ? 0 : intval($data['iresource_id']));
		$data['gatekeeper_id']	= (!isset($data['gatekeeper_id']) ? false : intval($data['gatekeeper_id']));
		$data['assistant_id']	= (!isset($data['assistant_id']) ? false : intval($data['assistant_id']));
		$data['step_complete']	= (!isset($data['step_complete']) ? false : intval($data['step_complete']));
		$data['is_approved']	= (!isset($data['is_approved']) ? false : intval($data['is_approved']));
		$data['rstep_id']		= (!isset($data['rstep_id']) ? false : intval($data['rstep_id']));
		$data['step_uid']		= (!isset($data['step_uid']) ? false : $this->db->getQuotedValue($data['step_uid']));

		if(empty($data['request_id']) || empty($data['iresource_id']) || (empty($data['step_uid'])&&empty($data['rstep_id']))) return false;

		$this->db->prepare('UPDATE `request_steps` SET `timestamp`=? ? ? ? ? WHERE ? `request_id`=? AND `iresource_id`=? ?');
		$this->db->bind($this->dbtimestamp);
		$this->db->bindSql(($data['gatekeeper_id']!==false ? ',`gatekeeper_id`='.$data['gatekeeper_id'].' ' : ''));
		$this->db->bindSql(($data['assistant_id']!==false ? ',`assistant_id`='.$data['assistant_id'].' ' : ''));
		$this->db->bindSql(($data['step_complete']!==false ? ',`step_complete`='.$data['step_complete'].' ' : ''));
		$this->db->bindSql(($data['is_approved']!==false ? ',`is_approved`='.$data['is_approved'].' ' : ''));

		$this->db->bindSql(($data['rstep_id']!==false ? '`rstep_id`='.$data['rstep_id'].' AND ' : ''));
		$this->db->bind($data['request_id']);
		$this->db->bind($data['iresource_id']);
		$this->db->bindSql(($data['step_uid']!==false ? ' AND `step_uid`='.$data['step_uid'] : ''));

		return $this->db->update();
	}#end function






	/*
	 * Получение списка гейткиперов на текущем шаге согласования
	 */
	public function getRouteStepGatekeepers($data=null){

		if(!$this->request_id || empty($this->db) || empty($data) || !is_array($data)) return false;

		$gatekeeper_type	= (empty($data['gatekeeper_type']) ? 0 : intval($data['gatekeeper_type']));
		$gatekeeper_id		= (empty($data['gatekeeper_id']) ? 0 : intval($data['gatekeeper_id']));
		$iresource_id		= (empty($data['iresource_id']) ? 0 : intval($data['iresource_id']));
		$employer_id		= (empty($data['employer_id']) ? $this->cache['info']['employer_id'] : intval($data['employer_id']));
		$company_id			= (empty($data['company_id']) ? $this->cache['info']['company_id'] : intval($data['company_id']));
		$post_uid			= (empty($data['post_uid']) ? $this->cache['info']['post_uid'] : (is_numeric($data['post_uid']) ? $data['post_uid'] : 0));
		if(empty($gatekeeper_type) || empty($iresource_id) || empty($employer_id) || empty($post_uid) || empty($company_id)) return false;
		if(!isset($this->cache['employer_groups'][$employer_id])){
			$this->cache['employer_groups'][$employer_id] = $this->db->selectFromField('group_id', 'SELECT `group_id` FROM `employer_groups` WHERE `employer_id` ='.$employer_id);
		}
		$employer_groups = $this->cache['employer_groups'][$employer_id];

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
					INNER JOIN `employer_posts` as EP ON ((CP.`boss_uid`>0 AND EP.`cp_id`=CP.`boss_uid`)OR(CP.`boss_uid`=0 AND EP.`cp_id`=CP.`post_uid`)) AND EP.`post_from`<=? AND EP.`post_to`>=?
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
					INNER JOIN `employer_posts` as EP ON EP.`cp_id`=CP.`post_uid` AND EP.`post_from`<=? AND EP.`post_to`>=?
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
					INNER JOIN `employer_posts` as EP ON EP.`cp_id`=IR.`owner_id` AND EP.`post_from`<=? AND EP.`post_to`>=?
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
					WHERE EP.`cp_id`=? AND EP.`post_from`<=? AND EP.`post_to`>=?
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
	 * Возвращает идентификаторы сотрудников, замещающие гейткипера
	 */
	public function getGatekeeperAssistants($gatekeepers=0, $fullinfo=false){

		if(empty($this->db)) return false;

		$gatekeepers = (is_array($gatekeepers) ? $gatekeepers : array($gatekeepers));
		$gatekeepers = array_unique(array_map('intval',$gatekeepers));
		if(empty($gatekeepers)) return array();

		$this->db->prepare('
			SELECT 
				ASSIST.`assistant_id` as `employer_id` ?
			FROM `assistants` as ASSIST
			INNER JOIN `employers` as EMP ON EMP.`employer_id`=ASSIST.`assistant_id` AND EMP.`status`>0
			WHERE ASSIST.`employer_id` IN (?) AND ASSIST.`from_date`<=? AND ASSIST.`to_date`>=?
		');
		$this->db->bindSql(($fullinfo ? ', EMP.`search_name` as `employer_name`, EMP.`phone` as `phone`, EMP.`email` as `email` ' : ''));
		$this->db->bindSql(implode(',',$gatekeepers));
		$this->db->bind($this->dbtoday);
		$this->db->bind($this->dbtoday);

		return ($fullinfo ? $this->db->select() : $this->db->selectFromField('employer_id'));
	}#end function








	/*==============================================================================================
	ИНФОРМАЦИОННЫЕ ФУНКЦИИ
	==============================================================================================*/

	public function getGatekeeperTypeString($gatekeeper_type){
		switch($gatekeeper_type){
			case '1': return 'Определенный сотрудник';
			case '2': return 'Непосредственный руководитель заявителя';
			case '3': return 'Руководитель организации заявителя';
			case '4': return 'Владелец информационного ресурса';
			case '5': return 'Группа пользователей';
			case '6': return 'Сотрудник, занимающий определенную должность';
			case '7': return 'Группа исполнителей';
			default:  return 'Неизвестный тип гейткипера';
		}
	}#end function


	public function getGatekeeperRoleString($gatekeeper_role){
		switch($gatekeeper_role){
			case '1': return 'Согласование заявки';
			case '2': return 'Утверждение заявки';
			case '3': return 'Исполнение заявки';
			case '4': return 'Ознакомление с заявкой';
			default:  return 'Неизвестная роль гейткипера';
		}
	}#end function






	/*==============================================================================================
	ПУЛ СООБЩЕНИЙ
	==============================================================================================*/


	/*
	 * Добавление в пул сообщений нового уведомления для гейткиперов
	 * 
	 * Структура массива:
	 * $data(
	 * 		'action'			=> 'notice', //Тип действия: notice - уведомить сотрудников, approve, decline - заявка согласована, отклонена
	 * 		'employers'			=> $gatekeepers, //массив сотрудников для уведомления, или идентификатор сотрудника
	 * 		'request_id'		=> $this->request_id, //идентификатор заявки
	 * 		'iresource_id'		=> $iresource_id, //идентификатор информационного ресурса
	 * 		'route_id'			=> $route_id, //идентификатор маршрута
	 * 		'step_uid'			=> $step_uid, //идентификатор шага в маршруте
	 * 		'gatekeeper_type'	=> $current_step['gatekeeper_type'], //текущий тип гейткипера
	 * 		'gatekeeper_role'	=> $current_step['gatekeeper_role'] //текущая роль гейткипера
	 * )
	 */
	public function messagePoolAdd($data=array()){

		if(empty($this->db) || empty($data) || !is_array($data)) return false;
		$action				= (empty($data['action']) ? false : $data['action']);
		$employers			= (empty($data['employers']) ? 0 : $data['employers']);
		$request_id			= (empty($data['request_id']) ? 0 : intval($data['request_id']));
		$iresource_id		= (empty($data['iresource_id']) ? 0 : intval($data['iresource_id']));
		$route_id			= (empty($data['route_id']) ? 0 : intval($data['route_id']));
		$step_uid			= (empty($data['step_uid']) ? 0 : $data['step_uid']);
		$gatekeeper_type	= (empty($data['gatekeeper_type']) ? 0 : intval($data['gatekeeper_type']));
		$gatekeeper_role	= (empty($data['gatekeeper_role']) ? 0 : intval($data['gatekeeper_role']));
		if(empty($action)||empty($request_id)||empty($iresource_id)||empty($route_id)||empty($step_uid)) return false;

		$employers = (!is_array($employers) ? array(intval($employers)) : array_unique(array_map('intval',$employers)));

		switch($action){
			case 'notice': $action_type = 1; break;
			case 'approve': $action_type = 2; break;
			case 'decline': $action_type = 3; break;
			default: $action_type = 0;
		}

		$timestamp = date('Y-m-d H:i:s');




		foreach($employers as $employer_id){

			$this->db->prepare('INSERT INTO `messages_pool` (`type`,`timestamp`,`employer_id`,`request_id`,`iresource_id`,`route_id`,`step_uid`,`gatekeeper_type`,`gatekeeper_role`) VALUES(?,?,?,?,?,?,?,?,?)');
			$this->db->bind($action_type);
			$this->db->bind($timestamp);
			$this->db->bind($employer_id);
			$this->db->bind($request_id);
			$this->db->bind($iresource_id);
			$this->db->bind($route_id);
			$this->db->bind($step_uid);
			$this->db->bind($gatekeeper_type);
			$this->db->bind($gatekeeper_role);
			if($this->db->insert() === false) return false;
		}

		return true;
	}#end function











}#end class

?>