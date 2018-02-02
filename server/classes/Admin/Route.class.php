<?php
/*==================================================================================================
Описание: Маршруты
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');




class Admin_Route{


	/*==============================================================================================
	Переменные класса
	==============================================================================================*/

	private $db 			= null;		#Указатель на экземпляр базы данных


	private $defaultRouteRecord = array(
		'route_type'	=> 1,
		'full_name'		=> '',
		'description'	=> '',
		'is_lock'		=> 0,
		'is_default'	=> 0,
		'priority'		=> 0
	);

	private $defaultRouteParamRecord = array(
		'route_id'		=> 0,
		'for_employer'	=> 0,
		'for_resource'	=> 0,
		'for_company'	=> 0,
		'for_post'		=> 0,
		'for_group'		=> 0
	);


	private $defaultRouteStepRecord = array(
		'route_id'			=> 0,
		'step_uid'			=> '0',
		'step_type'			=> 0,
		'gatekeeper_type'	=> 0,
		'gatekeeper_role'	=> 0,
		'gatekeeper_id'		=> 0,
		'step_yes'			=> '0',
		'step_no'			=> '0',
		'pos_x'				=> 0,
		'pos_y'				=> 0
	);


	/*==============================================================================================
	Инициализация
	==============================================================================================*/


	/*
	 * Конструктор класса
	 */
	public function __construct(){
		$this->db = Database::getInstance('main');
	}#end function






	/*==============================================================================================
	ФУНКЦИИ: Выбор оптимального маршрута согласования
	==============================================================================================*/

	/*
	 * Функция выбора маршрутов, удовлетворяющих условию
	 */
	public function routeSelect($data=null){

		if(!is_array($data)) return false;

		$employer_id		= (empty($data['employer_id']) ? 0 : intval($data['employer_id']));
		$company_id			= (empty($data['company_id']) ? 0 : intval($data['company_id']));
		$post_uid			= (empty($data['post_uid']) ? 0 : intval($data['post_uid']));
		$route_type			= (empty($data['route_type']) ? 1 : intval($data['route_type']));
		$iresource_id		= (empty($data['iresource_id']) ? 0 : intval($data['iresource_id']));
		$single				= (isset($data['single']) ? (!empty($data['single']) ? true : false) : true);
		if(empty($employer_id)||empty($company_id)||empty($post_uid)||empty($iresource_id)) return false;

		$employer_groups	= $this->db->selectFromField('group_id', 'SELECT `group_id` FROM `employer_groups` WHERE `employer_id` ='.$employer_id);
		$sql_groups 		= empty($employer_groups) ? '0' : implode(',',$employer_groups);
		$sql_iresources 	= $iresource_id;

		$this->db->prepare('
			SELECT * FROM(
				SELECT 
					ROUT.`route_id`,
					( IF(PARAM.`for_employer`>0 AND PARAM.`for_employer`=?, 20 ,0) + 
						IF(PARAM.`for_post`>0 AND PARAM.`for_post`=?, 10 ,0) + 
						IF(PARAM.`for_group`>0 AND PARAM.`for_group` IN ('.$sql_groups.'), 5 ,0) + 
						IF(PARAM.`for_resource`>0 AND PARAM.`for_resource` IN ('.$sql_iresources.'), 3 ,0) + 
						IF(PARAM.`for_company`>0 AND PARAM.`for_company`=?, 1 ,0)
					) as `weight`,
					PARAM.`for_employer` as `for_employer`,
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
				INNER JOIN `routes` as ROUT ON ROUT.`route_id`=PARAM.`route_id` AND ROUT.`is_lock`=0 AND ROUT.`route_type`=?
				WHERE
					(PARAM.`for_employer`=0 OR (PARAM.`for_employer`>0 AND PARAM.`for_employer`=?)) AND
					(PARAM.`for_post`=0 OR (PARAM.`for_post`>0 AND PARAM.`for_post`=?)) AND
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
		$this->db->bind($route_type);
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








	/*==============================================================================================
	ФУНКЦИИ: Работа с маршрутами
	==============================================================================================*/


	/*
	 * Получение списка маршрутов
	 */
	public function getRoutesListEx($routes=0, $fields=null, $single=false, $extended=true){

		$select = '';
		$select_from_field = false;

		if(empty($fields)){
			$select = 'SELECT ROUT.*';
		}
		elseif(!is_array($fields) && (array_key_exists($fields, $this->defaultRouteRecord) || $fields=='route_id')){
			$select = 'SELECT ROUT.`'.$fields.'` as `'.$fields.'`';
			$select_from_field = true;
		}
		elseif(is_array($fields)){
			foreach($fields as $field){
				if(!array_key_exists($field, $this->defaultRouteRecord) && $field!='route_id') continue;
				$select.= (empty($select) ? 'SELECT ' : ', ');
				$select.='ROUT.`'.$field.'` as `'.$field.'`';
			}
		}
		if(empty($select)) return false;

		$select.=' FROM `routes` as ROUT';

		if(!is_array($routes)){
			if(empty($routes)){
				$result = $this->db->prepare($select.($single?' LIMIT 1':''));
			}else{
				$this->db->prepare($select.' WHERE ROUT.`route_id`=? '.($single?' LIMIT 1':''));
				$this->db->bind(intval($routes));
			}
		}else{
			$conditions = $this->db->buildSqlConditions($routes,'ROUT');
			$this->db->prepare($select.' WHERE '.$conditions.($single?' LIMIT 1':''));
		}

		$result = ($select_from_field ? $this->db->selectFromField($fields)  : (empty($single) ? $this->db->select() : $this->db->selectRecord()));

		if(!$extended || $select_from_field) return $result;

		return $result;
	}#end function





	/*
	 * Проверка cуществования шаблона
	 */
	public function routeExists($route_id=0){
		if(empty($route_id)) return false;
		$this->db->prepare('SELECT count(*) FROM `routes` WHERE `route_id`=? LIMIT 1');
		$this->db->bind($route_id);
		return ($this->db->result() > 0);
	}#end function




	/*
	 * Добавление маршрута
	 */
	public function routeNew($fields=array()){

		if(empty($fields)) return false;
		$add_fields = array_intersect(array_keys($this->defaultRouteRecord), array_keys($fields));
		$adds = array();
		if(empty($add_fields)) return false;
		foreach($add_fields as $add_field){
			$adds[$add_field] = $fields[$add_field];
		}
		$fields = array_merge($this->defaultRouteRecord, $adds);
		$ins_names=array();
		$ins_q=array();
		$binds=array();
		foreach($fields as $field=>$value){
			$ins_names[]='`'.$field.'`';
			$ins_q[]='?';
			$binds[]=$value;
		}

		//Проверяем наличие транзакции
		$in_transaction = $this->db->inTransaction();
		if(!$in_transaction) $this->db->transaction();

		if($fields['is_default']==1){
			$this->db->prepare('UPDATE `routes` SET `is_default`=0 WHERE `route_type`=?');
			$this->db->bind($fields['route_type']);
			if($this->db->update()===false){
				if(!$in_transaction) $this->db->rollback();
				return false;
			}
		}

		$this->db->prepare('INSERT INTO `routes` ('.implode(',',$ins_names).')VALUES('.implode(',',$ins_q).')');
		foreach($binds as $bind) $this->db->bind($bind);
		if(($route_id = $this->db->insert())===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		if(($param_id = $this->routeParamNew(array(
			'route_id' => $route_id
		)))===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		//Выполнено успешно
		if(!$in_transaction) $this->db->commit();

		return $route_id;
	}#end function




	/*
	 * Обновление информации о маршруте
	 */
	public function routeUpdate($route_id=0, $fields=array()){

		if(empty($route_id)||empty($fields)) return false;
		$update_fields = array_intersect(array_keys($this->defaultRouteRecord), array_keys($fields));
		if(empty($update_fields)) return false;
		$binds=array();
		$updates='';
		foreach($update_fields as $update_field){
			$updates.=(empty($updates)?'':',').'`'.$update_field.'`=?';
			$binds[]=$fields[$update_field];
		}
		$this->db->prepare('UPDATE `routes` SET '.$updates.' WHERE `route_id`=?');
		foreach($binds as $bind) $this->db->bind($bind);
		$this->db->bind($route_id);

		if($this->db->update()===false) return false;
		return true;
	}#end function





	/*
	 * Удаление маршрута
	 */
	public function routeDelete($route_id=0, $check_can_delete=true){

		if(empty($route_id)) return false;
		if($check_can_delete && !$this->routeCanDelete($route_id)) return false;

		//Проверяем наличие транзакции
		$in_transaction = $this->db->inTransaction();
		if(!$in_transaction) $this->db->transaction();

		//Удаление из routes
		$this->db->prepare('DELETE FROM `routes` WHERE `route_id`=?');
		$this->db->bind($route_id);
		if($this->db->delete()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		//Удаление из route_params
		$this->db->prepare('DELETE FROM `route_params` WHERE `route_id`=?');
		$this->db->bind($route_id);
		if($this->db->delete()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		//Удаление из route_steps
		$this->db->prepare('DELETE FROM `route_steps` WHERE `route_id`=?');
		$this->db->bind($route_id);
		if($this->db->delete()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		//Выполнено успешно
		if(!$in_transaction) $this->db->commit();

		return true;
	}#end function





	/*
	 * Проверка допустимости удаления маршрута
	 */
	public function routeCanDelete($route_id=0){
		$this->db->prepare('
			(SELECT count(*) as `count`FROM `request_iresources` WHERE `route_id`=? LIMIT 1) UNION ALL
			(SELECT count(*) as `count`FROM `request_iresources_hist` WHERE `route_id`=? LIMIT 1)
		');
		$this->db->bind($route_id);
		$this->db->bind($route_id);
		if(($counts = $this->db->selectFromField('count')) === false )return false;
		return (array_sum($counts) > 0 ? false : true);
	}#end function




	/*==============================================================================================
	ФУНКЦИИ: Работа с параметрами маршрута
	==============================================================================================*/


	/*
	 * Получение списка параметров маршрута
	 */
	public function routeParams($routes=0, $fields=array(), $single=false){
		if(!is_array($routes)){
			if(empty($routes)){
				$result = $this->db->prepare('SELECT * FROM `route_params`'.($single?' LIMIT 1':''));
			}else{
				$this->db->prepare('SELECT * FROM `route_params` WHERE `route_id`=? '.($single?' LIMIT 1':''));
				$this->db->bind(intval($routes));
			}
		}else{
			$conditions = $this->db->buildSqlConditions($routes);
			$this->db->prepare('SELECT * FROM `route_params` WHERE '.$conditions.($single?' LIMIT 1':''));
		}

		$result = (empty($single) ? $this->db->select() : $this->db->selectRecord());
		if(empty($fields)) return $result;
		if(!empty($fields) && !is_array($fields)) return arrayFromField($fields,$result);
		if(!empty($single)) return arrayCustomFields($result, $fields);

		$return = array();
		foreach($result as $record){
			$return[] = arrayCustomFields($record, $fields);
		}

		return $return;
	}#end function




	/*
	 * Получение списка параметров маршрута
	 */
	public function routeParamsEx($route_id=0){
		$this->db->prepare('
			SELECT 
				RP.`param_id` as `param_id`,
				RP.`route_id` as `route_id`,
				RP.`for_employer` as `for_employer`,
				RP.`for_resource` as `for_resource`,
				RP.`for_company` as `for_company`,
				RP.`for_post` as `for_post`,
				RP.`for_group` as `for_group`,
				C.`full_name` as `for_company_name`,
				IR.`full_name` as `for_resource_name`,
				CONCAT_WS(" / ", EMP.`search_name`, DATE_FORMAT(EMP.`birth_date`,"%d.%m.%Y")) as `for_employer_name`,
				G.`full_name` as `for_group_name`,
				(SELECT `full_name` FROM `posts` WHERE `post_id`=CP.`post_id` LIMIT 1) as `for_post_name`
			FROM 
				`route_params` as RP 
			LEFT JOIN `companies` as C ON C.`company_id`=RP.`for_company`
			LEFT JOIN `iresources` as IR ON IR.`iresource_id`=RP.`for_resource`
			LEFT JOIN `employers` as EMP ON EMP.`employer_id`=RP.`for_employer`
			LEFT JOIN `groups` as G ON G.`group_id`=RP.`for_group`
			LEFT JOIN `company_posts` as CP ON CP.`post_uid`=RP.`for_post`
			WHERE 
				`route_id`=?
		');
		$this->db->bind(intval($route_id));
		return $this->db->select();
	}#end function




	/*
	 * Добавление параметра маршрута
	 */
	public function routeParamNew($fields=array()){

		if(empty($fields)) return false;
		$add_fields = array_intersect(array_keys($this->defaultRouteParamRecord), array_keys($fields));
		$adds = array();
		if(empty($add_fields)) return false;
		foreach($add_fields as $add_field){
			$adds[$add_field] = $fields[$add_field];
		}
		$fields = array_merge($this->defaultRouteParamRecord, $adds);
		$ins_names=array();
		$ins_q=array();
		$binds=array();
		foreach($fields as $field=>$value){
			$ins_names[]='`'.$field.'`';
			$ins_q[]='?';
			$binds[]=$value;
		}
		$this->db->prepare('INSERT INTO `route_params` ('.implode(',',$ins_names).')VALUES('.implode(',',$ins_q).')');
		foreach($binds as $bind) $this->db->bind($bind);
		if(($id = $this->db->insert())===false) return false;

		return $id;
	}#end function




	/*
	 * Обновление параметра маршрута
	 */
	public function routeParamUpdate($route_id=0, $param_id=0, $fields=array()){

		if(empty($route_id)||empty($param_id)||empty($fields)) return false;
		$update_fields = array_intersect(array_keys($this->defaultRouteParamRecord), array_keys($fields));
		if(empty($update_fields)) return false;
		$binds=array();
		$updates='';
		foreach($update_fields as $update_field){
			$updates.=(empty($updates)?'':',').'`'.$update_field.'`=?';
			$binds[]=$fields[$update_field];
		}
		$this->db->prepare('UPDATE `route_params` SET '.$updates.' WHERE `param_id`=? AND `route_id`=?');
		foreach($binds as $bind) $this->db->bind($bind);
		$this->db->bind($param_id);
		$this->db->bind($route_id);

		if($this->db->update()===false) return false;
		return true;
	}#end function




	/*
	 * Удаление параметра маршрута
	 */
	public function routeParamDelete($route_id=0, $param_id=0, $check_can_delete=true){

		if(empty($route_id)) return false;
		if($check_can_delete && !$this->routeParamCanDelete($route_id, $param_id)) return false;

		//Проверяем наличие транзакции
		$in_transaction = $this->db->inTransaction();
		if(!$in_transaction) $this->db->transaction();

		//Удаление из route_params
		$this->db->prepare('DELETE FROM `route_params` WHERE `param_id`=? AND `route_id`=?');
		$this->db->bind($param_id);
		$this->db->bind($route_id);
		if($this->db->delete()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		//Выполнено успешно
		if(!$in_transaction) $this->db->commit();

		return true;
	}#end function



	/*
	 * Проверка допустимости удаления параметра маршрута
	 */
	public function routeParamCanDelete($route_id=0, $param_id=0){
		return true;
	}#end function





	/*==============================================================================================
	ФУНКЦИИ: Работа с шагами маршрута
	==============================================================================================*/


	/*
	 * Генерация UID шага маршрута
	 */
	public function getStepUID($route_id=0, $step_type=0, $gatekeeper_role=0, $gatekeeper_type=0, $gatekeeper_id=0){
		return 
			'1'. //1
			str_pad($route_id, 9, '0', STR_PAD_LEFT). //9
			str_pad($step_type, 2, '0', STR_PAD_LEFT). 	//2
			str_pad($gatekeeper_type, 2, '0', STR_PAD_LEFT).  //2
			str_pad($gatekeeper_role, 2, '0', STR_PAD_LEFT). //2
			'0000'. // 4 reserved
			str_pad($gatekeeper_id, 20, '0', STR_PAD_LEFT); //20
	}#end function




	/*
	 * Получение списка шагов маршрута
	 */
	public function routeSteps($route_id=0, $fields=null){
		if(empty($route_id)) return false;
		$this->db->prepare('SELECT * FROM `route_steps` WHERE `route_id`=?');
		$this->db->bind($route_id);
		if(($results = $this->db->select())===false) return false;
		if(empty($fields)) return $results;
		if(!is_array($fields)) return arrayFromField($fields, $results, true);
		$records = array();
		foreach($results as $result){
			$records[]=arrayCustomFields($result,$fields);
		}
		return $records;
	}#end function



	/*
	 * Удаление существующей схемы маршрута
	 */
	public function routeStepsClear($route_id=0, $check_can_delete=true){
		if(empty($route_id)) return false;
		if($check_can_delete && !$this->routeStepsCanDelete($route_id)) return false;
		$this->db->prepare('DELETE FROM `route_steps` WHERE `route_id`=?');
		$this->db->bind($route_id);
		if($this->db->delete()===false) return false;
		return true;
	}#end function




	/*
	 * Проверка допустимости удаления шага маршрута
	 */
	public function routeStepsCanDelete($route_id=0, $step_uid=null){
		$wsql = '';
		if(!empty($step_uid)){
			if(is_array($step_uid)){
				$wsql = ' AND RS.`step_uid` IN ('.implode(',',array_map(array($this->db,'getQuotedValue'),$step_uid)).')';
				
			}else{
				$wsql = ' AND RS.`step_uid` = '.$this->db->getQuotedValue($step_uid);
			}
		}
		$this->db->prepare('
			SELECT count(*) as `count` FROM `route_steps` as RS
				INNER JOIN `request_steps` as REQSTEP ON REQSTEP.`route_id`=? AND REQSTEP.`step_uid`=RS.`step_uid` AND REQSTEP.`step_complete`=0
				INNER JOIN `request_iresources` as RIR ON RIR.`request_id`=REQSTEP.`request_id` AND RIR.`iresource_id`=REQSTEP.`iresource_id` AND RIR.`route_status` IN (1,2)
			WHERE RS.`route_id`=?
			'.$wsql.'
			LIMIT 1
		');
		$this->db->bind($route_id);
		$this->db->bind($route_id);
		if(($counts = $this->db->selectFromField('count')) === false )return false;
		return (array_sum($counts) > 0 ? false : true);
	}#end function









	/*
	 * Добавление элемента в схему маршрута
	 */
	public function routeStepNew($fields=array()){

		if(empty($fields)) return false;
		$add_fields = array_intersect(array_keys($this->defaultRouteStepRecord), array_keys($fields));
		$adds = array();
		if(empty($add_fields)) return false;
		foreach($add_fields as $add_field){
			$adds[$add_field] = $fields[$add_field];
		}
		$fields = array_merge($this->defaultRouteStepRecord, $adds);
		$ins_names=array();
		$ins_q=array();
		$binds=array();
		foreach($fields as $field=>$value){
			$ins_names[]='`'.$field.'`';
			$ins_q[]='?';
			$binds[]=$value;
		}
		$this->db->prepare('INSERT INTO `route_steps` ('.implode(',',$ins_names).')VALUES('.implode(',',$ins_q).')');
		foreach($binds as $bind) $this->db->bind($bind);
		if(($id = $this->db->insert())===false) return false;

		return $id;
	}#end function



}#end class

?>