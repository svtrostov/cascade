<?php
/*==================================================================================================
Описание: Шаблоны доступа
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');




class Admin_IResource{
	use Trait_RequestRoles;

	/*==============================================================================================
	Переменные класса
	==============================================================================================*/

	private $db = null;		#Указатель на экземпляр базы данных


	private $defaultIResourceRecord = array(
		'company_id'		=> 0,
		'post_uid'			=> 0,
		'is_lock'			=> 0,
		'short_name'		=> '',
		'full_name'			=> '',
		'description'		=> '',
		'location'			=> '',
		'worker_group'		=> 0,
		'iresource_group'	=> 0,
		'techinfo'			=> ''
	);

	private $defaultIRoleRecord = array(
		'iresource_id'	=> 0,
		'owner_id'		=> 0,
		'is_lock'		=> 0,
		'is_area'		=> 0,
		'short_name'	=> '',
		'full_name'		=> '',
		'description'	=> '',
		'ir_types'		=> '',
		'screenshot'	=> '',
		'weight'		=> 0
	);


	private $defaultIRTypeRecord = array(
		'short_name'	=> '',
		'full_name'		=> ''
	);


	private $defaultIGroupRecord = array(
		'short_name'	=> '',
		'full_name'		=> ''
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
	ФУНКЦИИ: Работа с информационными ресурсами
	==============================================================================================*/


	/*
	 * Получение списка информационных ресурсов
	 */
	public function getIResourcesList($iresources=0, $fields=null, $single=false){

		if(!is_array($iresources)){
			if(empty($iresources)){
				$this->db->prepare('SELECT * FROM `iresources`'.($single?' LIMIT 1':''));
			}else{
				$this->db->prepare('SELECT * FROM `iresources` WHERE `iresource_id`=? '.($single?' LIMIT 1':''));
				$this->db->bind(intval($iresources));
			}
		}else{
			$conditions = $this->db->buildSqlConditions($iresources);
			$this->db->prepare('SELECT * FROM `iresources` WHERE '.$conditions.($single?' LIMIT 1':''));
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
	 * Получение списка информационных ресурсов
	 */
	public function getIResourcesListEx($iresources=0, $fields=null, $single=false, $extended=true, $limit=50){

		$select = '';
		$select_from_field = false;

		if(empty($fields)){
			$select = 'SELECT IRES.*';
		}
		elseif(!is_array($fields) && (array_key_exists($fields, $this->defaultEmployerRecord) || $fields=='iresource_id')){
			$select = 'SELECT IRES.`'.$fields.'` as `'.$fields.'`';
			$select_from_field = true;
		}
		elseif(is_array($fields)){
			foreach($fields as $field){
				if(!array_key_exists($field, $this->defaultEmployerRecord) && $field!='iresource_id') continue;
				$select.= (empty($select) ? 'SELECT ' : ', ');
				$select.='IRES.`'.$field.'` as `'.$field.'`';
			}
		}

		if(empty($select)) return false;

		
		if($extended){
			$select.=',
			IFNULL(C.`full_name`,"") as `company_name`,
			IFNULL(P.`full_name`,"") as `post_name`,
			IFNULL(IG.`full_name`,"") as `igroup_name`
			FROM `iresources` as IRES
				LEFT JOIN `companies` as C ON C.`company_id`=IRES.`company_id`
				LEFT JOIN `company_posts` as CP ON CP.`post_uid`=IRES.`post_uid`
				LEFT JOIN `posts` as P ON P.`post_id`=CP.`post_id`
				LEFT JOIN `iresource_groups` as IG ON IG.`igroup_id`=IRES.`iresource_group`
			';
		}else{
			$select.=' FROM `iresources` as IRES';
		}

		if(!is_array($iresources)){
			if(empty($iresources)){
				$result = $this->db->prepare($select.($single?' LIMIT 1':' LIMIT '.$limit));
			}else{
				$this->db->prepare($select.' WHERE IRES.`iresource_id`=? '.($single?' LIMIT 1':' LIMIT '.$limit));
				$this->db->bind(intval($iresources));
			}
		}else{
			$conditions = $this->db->buildSqlConditions($iresources,'IRES');
			$this->db->prepare($select.' WHERE '.$conditions.($single?' LIMIT 1':' LIMIT '.$limit));
		}

		$result = ($select_from_field ? $this->db->selectFromField($fields)  : (empty($single) ? $this->db->select() : $this->db->selectRecord()));

		if(!$extended || $select_from_field) return $result;

		$employers = new Admin_Employers();

		if(!empty($single)){
			$result['employers'] = ($result['post_uid']!=0 ?$employers->getEmployersOnPostEx($result['post_uid']) : array());
			return $result;
		}

		for($i=0; $i<count($result);$i++){
			$result[$i]['employers'] = ($result[$i]['post_uid']!=0 ? $employers->getEmployersOnPostEx($result[$i]['post_uid']) : array());
		}

		return $result;
	}#end function




	/*
	 * Проверка cуществования информационного ресурса
	 */
	public function iresourceExists($iresource_id=0){
		if(empty($iresource_id)) return false;
		$this->db->prepare('SELECT count(*) FROM `iresources` WHERE `iresource_id`=? LIMIT 1');
		$this->db->bind($iresource_id);
		return ($this->db->result() > 0);
	}#end function



	/*
	 * Получение списка объектов ресурса
	 */
	public function iresourceIRoles($iresource_id=0){
		return $this->db->selectFromField('irole_id','SELECT `irole_id` FROM `iroles` WHERE `iresource_id`='.intval($iresource_id));
	}#end function




	/*
	 * Добавление информационного ресурса
	 */
	public function iresourceNew($fields=array()){

		if(empty($fields)) return false;
		$add_fields = array_intersect(array_keys($this->defaultIResourceRecord), array_keys($fields));
		$adds = array();
		if(empty($add_fields)) return false;
		foreach($add_fields as $add_field){
			$adds[$add_field] = $fields[$add_field];
		}
		$fields = array_merge($this->defaultIResourceRecord, $adds);
		$ins_names=array();
		$ins_q=array();
		$binds=array();
		foreach($fields as $field=>$value){
			$ins_names[]='`'.$field.'`';
			$ins_q[]='?';
			$binds[]=$value;
		}
		$this->db->prepare('INSERT INTO `iresources` ('.implode(',',$ins_names).')VALUES('.implode(',',$ins_q).')');
		foreach($binds as $bind) $this->db->bind($bind);
		if(($iresource_id = $this->db->insert())===false) return false;

		return $iresource_id;
	}#end function





	/*
	 * Обновление информации об информационном ресурсе
	 */
	public function iresourceUpdate($iresource_id=0, $fields=array()){

		if(empty($iresource_id)||empty($fields)) return false;
		$update_fields = array_intersect(array_keys($this->defaultIResourceRecord), array_keys($fields));
		if(empty($update_fields)) return false;
		$binds=array();
		$updates='';
		foreach($update_fields as $update_field){
			$updates.=(empty($updates)?'':',').'`'.$update_field.'`=?';
			$binds[]=$fields[$update_field];
		}
		$this->db->prepare('UPDATE `iresources` SET '.$updates.' WHERE `iresource_id`=?');
		foreach($binds as $bind) $this->db->bind($bind);
		$this->db->bind($iresource_id);

		if($this->db->update()===false) return false;
		return true;
	}#end function





	/*
	 * Удаление информационного ресурса
	 */
	public function iresourceDelete($iresource_id=0, $check_can_delete=true){

		if(empty($iresource_id)) return false;
		if($check_can_delete && !$this->iresourceCanDelete($iresource_id)) return false;

		//Проверяем наличие транзакции
		$in_transaction = $this->db->inTransaction();
		if(!$in_transaction) $this->db->transaction();

		//Удаление из iresources
		$this->db->prepare('DELETE FROM `iresources` WHERE `iresource_id`=?');
		$this->db->bind($iresource_id);
		if($this->db->delete()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		//Обновление route_params
		$this->db->prepare('UPDATE `route_params` SET `for_resource`=0 WHERE `for_resource`=?');
		$this->db->bind($iresource_id);
		if($this->db->update()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		//Удаление из iresource_companies
		$this->db->prepare('DELETE FROM `iresource_companies` WHERE `iresource_id`=?');
		$this->db->bind($iresource_id);
		if($this->db->delete()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		//Удаление из iroles
		$this->db->prepare('DELETE FROM `iroles` WHERE `iresource_id`=?');
		$this->db->bind($iresource_id);
		if($this->db->delete()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		//Удаление из tmpl_roles
		$this->db->prepare('DELETE FROM `tmpl_roles` WHERE `iresource_id`=?');
		$this->db->bind($iresource_id);
		if($this->db->delete()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		//Выполнено успешно
		if(!$in_transaction) $this->db->commit();

		return true;
	}#end function





	/*
	 * Проверка допустимости удаления информационного ресурса
	 */
	public function iresourceCanDelete($iresource_id=0){

		#Проверка использования информационного ресурса
		$this->db->prepare('
			(SELECT count(*) as `count`FROM `tmpl_roles` WHERE `iresource_id`=? LIMIT 1) UNION ALL
			(SELECT count(*)  as `count` FROM `request_iresources` WHERE `iresource_id`=? LIMIT 1) UNION ALL
			(SELECT count(*)  as `count` FROM `request_iresources_hist` WHERE `iresource_id`=? LIMIT 1) UNION ALL
			(SELECT count(*)  as `count` FROM `route_params` WHERE `for_resource`=? LIMIT 1)
		');
		$this->db->bind($iresource_id);
		$this->db->bind($iresource_id);
		$this->db->bind($iresource_id);
		$this->db->bind($iresource_id);

		if(($counts = $this->db->selectFromField('count')) === false )return false;

		return (array_sum($counts) > 0 ? false : true);

	}#end function



	/*==============================================================================================
	ФУНКЦИИ: Работа с правами доступа к информационному ресурсу из организаций
	==============================================================================================*/


	/*
	 * Проверка cуществования организации в ресурсе
	 */
	public function iresourceCompanyExists($iresource_id=0, $company_id=0){
		if(empty($iresource_id)||empty($company_id)) return false;
		$this->db->prepare('SELECT count(*) FROM `iresource_companies` WHERE `iresource_id`=? AND `company_id`=? LIMIT 1');
		$this->db->bind($iresource_id);
		$this->db->bind($company_id);
		return ($this->db->result() > 0);
	}#end function




	/*
	 * Получение списка организаций, включенных в ресурс
	 */
	public function getIResourceCompanies($iresource_id=0){
		$this->db->prepare('SELECT `company_id` FROM `iresource_companies` WHERE `iresource_id`=?');
		$this->db->bind($iresource_id);
		if(($companies = $this->db->selectFromField('company_id'))===false) return false;
		if(empty($companies)) return array();
		return $companies;
	}#end function



	/*
	 * Добавление организации в ресурс
	 */
	public function iresourceIncludeCompany($iresource_id=0, $company_id=0){
		if(empty($iresource_id)) return false;
		$this->db->prepare('REPLACE INTO `iresource_companies` (`company_id`,`iresource_id`)VALUES(?,?)');
		$this->db->bind(intval($company_id));
		$this->db->bind($iresource_id);
		if($this->db->simple()===false) return false;
		return true;
	}#end function




	/*
	 * Исключение организации из ресурса
	 */
	public function iresourceExcludeCompany($iresource_id=0, $company_id=0){
		if(empty($iresource_id)) return false;
		$this->db->prepare('DELETE FROM `iresource_companies` WHERE `company_id`=? AND `iresource_id`=?');
		$this->db->bind(intval($company_id));
		$this->db->bind($iresource_id);
		if($this->db->simple()===false) return false;
		return true;
	}#end function





	/*==============================================================================================
	ФУНКЦИИ: Работа с типами доступов
	==============================================================================================*/


	/*
	 * Проверка cуществования типа доступа
	 */
	public function irtypeExists($item_id=0){
		if(empty($item_id)) return false;
		$this->db->prepare('SELECT count(*) FROM `ir_types` WHERE `item_id`=? LIMIT 1');
		$this->db->bind($item_id);
		return ($this->db->result() > 0);
	}#end function



	/*
	 * Получение списка типов доступов
	 */
	public function getIRTypesList($fields=null){
		$result = $this->db->select('SELECT * FROM `ir_types`');
		if(empty($fields)) return $result;
		$return = array();
		foreach($result as $record){
			$return[] = arrayCustomFields($record, $fields);
		}
		return $return;
	}#end function




	/*
	 * Добавление типа доступа
	 */
	public function irtypeNew($fields=array()){

		if(empty($fields)) return false;
		$add_fields = array_intersect(array_keys($this->defaultIRTypeRecord), array_keys($fields));
		$adds = array();
		if(empty($add_fields)) return false;
		foreach($add_fields as $add_field){
			$adds[$add_field] = $fields[$add_field];
		}
		$fields = array_merge($this->defaultIRTypeRecord, $adds);
		$ins_names=array();
		$ins_q=array();
		$binds=array();
		foreach($fields as $field=>$value){
			$ins_names[]='`'.$field.'`';
			$ins_q[]='?';
			$binds[]=$value;
		}
		$this->db->prepare('INSERT INTO `ir_types` ('.implode(',',$ins_names).')VALUES('.implode(',',$ins_q).')');
		foreach($binds as $bind) $this->db->bind($bind);
		if(($item_id = $this->db->insert())===false) return false;

		return $item_id;
	}#end function




	/*
	 * Обновление типа доступа
	 */
	public function irtypeUpdate($item_id=0, $fields=array()){

		if(empty($item_id)||empty($fields)) return false;
		$update_fields = array_intersect(array_keys($this->defaultIRTypeRecord), array_keys($fields));
		if(empty($update_fields)) return false;
		$binds=array();
		$updates='';
		foreach($update_fields as $update_field){
			$updates.=(empty($updates)?'':',').'`'.$update_field.'`=?';
			$binds[]=$fields[$update_field];
		}
		$this->db->prepare('UPDATE `ir_types` SET '.$updates.' WHERE `item_id`=?');
		foreach($binds as $bind) $this->db->bind($bind);
		$this->db->bind($item_id);

		if($this->db->update()===false) return false;
		return true;
	}#end function




	/*
	 * Удаление типа доступа
	 */
	public function irtypeDelete($item_id=0, $check_can_delete=true){

		if(empty($item_id)) return false;
		if($check_can_delete && !$this->irtypeCanDelete($item_id)) return false;

		//Проверяем наличие транзакции
		$in_transaction = $this->db->inTransaction();
		if(!$in_transaction) $this->db->transaction();

		//Удаление из iresources
		$this->db->prepare('DELETE FROM `ir_types` WHERE `item_id`=?');
		$this->db->bind($item_id);
		if($this->db->delete()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		//Выполнено успешно
		if(!$in_transaction) $this->db->commit();

		return true;
	}#end function





	/*
	 * Проверка допустимости удаления типа доступа
	 */
	public function irtypeCanDelete($item_id=0){
		$item_id = intval($item_id);
		#Проверка использования информационного ресурса
		$this->db->prepare('
			(SELECT count(*) as `count` FROM `tmpl_roles` WHERE `ir_type`= ? LIMIT 1) UNION
			(SELECT count(*)  as `count` FROM `complete_roles` WHERE `ir_type`=? LIMIT 1) UNION 
			(SELECT count(*) as `count` FROM `iroles` WHERE `ir_types` LIKE ? OR `ir_types` LIKE "%,?" OR `ir_types` LIKE "?,%" OR `ir_types` LIKE "%,?,%" LIMIT 1)
		');
		$this->db->bind($item_id);
		$this->db->bind($item_id);
		$this->db->bind($item_id);
		$this->db->bind($item_id);
		$this->db->bind($item_id);
		$this->db->bindNum($item_id);
		$this->db->bindNum($item_id);
		$this->db->bindNum($item_id);
		if(($counts = $this->db->selectFromField('count')) === false )return false;
		if (array_sum($counts) > 0) return false;
		return ($this->checkUseRIRoleIRType($item_id) ? false : true);
	}#end function







	/*==============================================================================================
	ФУНКЦИИ: Работа с объектами доступа в информационном ресурсе
	==============================================================================================*/



	/*
	 * Проверка cуществования объекта доступа в информационном ресурсе
	 */
	public function iroleExists($iresource_id=0, $irole_id=0){
		if(empty($iresource_id)) return false;
		$this->db->prepare('SELECT count(*) FROM `iroles` WHERE `irole_id`=? AND `iresource_id`=? LIMIT 1');
		$this->db->bind($irole_id);
		$this->db->bind($iresource_id);
		return ($this->db->result() > 0);
	}#end function



	/*
	 * Получение списка объектов доступа информационного ресурса
	 */
	public function getIRoles($iresource_id=0, $fields=null, $raw_data=false, $iroles_filter=null){

		#Получение списка объектов ИР
		$this->db->prepare('SELECT IROLE.*,"0" as `ir_selected` FROM `iroles` as IROLE WHERE IROLE.`iresource_id`=? ? ORDER BY IROLE.`full_name`');
		$this->db->bind($iresource_id);
		$this->db->bindSql((!empty($iroles_filter)&&is_array($iroles_filter) ? ' AND IROLE.`irole_id` IN ('.implode(',',array_map('intval',$iroles_filter)).')' : ''));
		if( ($results = $this->db->select()) === false) return false;

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
				if(!empty($results[$key]['ir_types'])){
					$results[$key]['ir_types'] = explode(',',$results[$key]['ir_types']);
				}else{
					$results[$key]['ir_types']=array();
				}
			}
		}

		#Обработка записей и перегруппировка по разделам
		foreach($results as $key=>$item){
			if($results[$key]['is_area'] != 1){
				$results[$key]['screenshot'] = (irole_screenshot_exists($results[$key]['irole_id']) ? $results[$key]['irole_id'] : '');
				if(empty($results[$key]['weight'])) $results[$key]['weight'] = 0;
				#Если родительский раздел не найден в списке разделов, отправляем в "Без раздела"
				if(!isset($area_ids[$results[$key]['owner_id']])) $results[$key]['owner_id'] = 0;
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

		return array(
			'areas'	=> $areas,
			'items' => $items
		);
	}#end function





	/*
	 * Добавление объекта доступа
	 */
	public function iroleNew($fields=array()){

		if(empty($fields)) return false;
		$add_fields = array_intersect(array_keys($this->defaultIRoleRecord), array_keys($fields));
		$adds = array();
		if(empty($add_fields)) return false;
		foreach($add_fields as $add_field){
			$adds[$add_field] = $fields[$add_field];
		}
		$fields = array_merge($this->defaultIRoleRecord, $adds);
		$ins_names=array();
		$ins_q=array();
		$binds=array();
		foreach($fields as $field=>$value){
			$ins_names[]='`'.$field.'`';
			$ins_q[]='?';
			$binds[]=$value;
		}

		$in_transaction = $this->db->inTransaction();
		if(!$in_transaction) $this->db->transaction();

		$this->db->prepare('INSERT INTO `iroles` ('.implode(',',$ins_names).')VALUES('.implode(',',$ins_q).')');
		foreach($binds as $bind) $this->db->bind($bind);
		if(($irole_id = $this->db->insert())===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}


		if(!$in_transaction) $this->db->commit();
		return $irole_id;
	}#end function




	/*
	 * Обновление объекта доступа
	 */
	public function iroleUpdate($iresource_id=0, $irole_id=0, $fields=array()){

		if(empty($irole_id)||empty($iresource_id)||empty($fields)) return false;
		$update_fields = array_intersect(array_keys($this->defaultIRoleRecord), array_keys($fields));
		if(empty($update_fields)) return true;
		$binds=array();
		$updates='';
		foreach($update_fields as $update_field){
			$updates.=(empty($updates)?'':',').'`'.$update_field.'`=?';
			$binds[]=$fields[$update_field];
		}

		$this->db->prepare('UPDATE `iroles` SET '.$updates.' WHERE `irole_id`=? AND `iresource_id`=?');
		foreach($binds as $bind) $this->db->bind($bind);
		$this->db->bind($irole_id);
		$this->db->bind($iresource_id);

		$in_transaction = $this->db->inTransaction();
		if(!$in_transaction) $this->db->transaction();

		if($this->db->update()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		if(!$in_transaction) $this->db->commit();
		return true;
	}#end function






	/*
	 * Удаление объекта доступа
	 */
	public function iroleDelete($iresource_id=0, $irole_id=0, $check_can_delete=true, $delete_screenshot=true){

		if(empty($iresource_id)) return false;
		if($check_can_delete && !$this->iroleCanDelete($iresource_id, $irole_id)) return false;

		//Проверяем наличие транзакции
		$in_transaction = $this->db->inTransaction();
		if(!$in_transaction) $this->db->transaction();

		//Удаление из iroles
		$this->db->prepare('DELETE FROM `iroles` WHERE `iresource_id`=? AND `irole_id`=?');
		$this->db->bind($iresource_id);
		$this->db->bind($irole_id);
		if($this->db->delete()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		//Обновление iroles
		$this->db->prepare('UPDATE `iroles` SET `owner_id`=0 WHERE `owner_id`=?');
		$this->db->bind($irole_id);
		if($this->db->update()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		//Удаление из tmpl_roles
		$this->db->prepare('DELETE FROM `tmpl_roles` WHERE `iresource_id`=? AND `irole_id`=?');
		$this->db->bind($iresource_id);
		$this->db->bind($irole_id);
		if($this->db->delete()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}


		if($delete_screenshot) irole_screenshot_delete($irole_id);

		//Выполнено успешно
		if(!$in_transaction) $this->db->commit();

		return true;
	}#end function





	/*
	 * Проверка допустимости удаления объекта доступа
	 */
	public function iroleCanDelete($iresource_id=0, $irole_id=0){
		#Проверка использования объекта доступа
		$this->db->prepare('
			(SELECT count(*) as `count`FROM `tmpl_roles` WHERE `iresource_id`=? AND `irole_id`=? LIMIT 1) UNION
			(SELECT count(*)  as `count` FROM `complete_roles` WHERE `iresource_id`=?  AND `irole_id`=? LIMIT 1)
		');
		$this->db->bind($iresource_id);
		$this->db->bind($irole_id);
		$this->db->bind($iresource_id);
		$this->db->bind($irole_id);
		if(($counts = $this->db->selectFromField('count')) === false )return false;
		if (array_sum($counts) > 0) return false;
		return ($this->checkUseRIRoleIResourceRole($irole_id) ? false : true);
	}#end function




	/*
	 * Получение информации об объекте доступа
	 */
	public function getIRole($iresource_id=0, $irole_id=0){
		$this->db->prepare('SELECT IROLE.* FROM `iroles` as IROLE WHERE IROLE.`irole_id`=? AND IROLE.`iresource_id`=? LIMIT 1');
		$this->db->bind($irole_id);
		$this->db->bind($iresource_id);
		if(($record=$this->db->selectRecord())===false) return false;
		if(empty($record['weight'])) $record['weight'] = 0;
		$record['ir_types'] = (empty($record['ir_types']) ? array() : explode(',',$record['ir_types']));
		$record['screenshot'] = (irole_screenshot_exists($record['irole_id']) ? $record['irole_id'] : '');
		return $record;
	}#end function










	/*==============================================================================================
	ФУНКЦИИ: Работа с группами информационных ресурсов
	==============================================================================================*/



	/*
	 * Проверка cуществования группы ИР
	 */
	public function igroupExists($igroup_id=0){
		if(empty($item_id)) return false;
		$this->db->prepare('SELECT count(*) FROM `iresource_groups` WHERE `igroup_id`=? LIMIT 1');
		$this->db->bind($item_id);
		return ($this->db->result() > 0);
	}#end function




	/*
	 * Получение списка типов доступов
	 */
	public function getIGroupsList($fields=null){
		$result = $this->db->select('SELECT * FROM `iresource_groups`');
		if(empty($fields)) return $result;
		$return = array();
		foreach($result as $record){
			$return[] = arrayCustomFields($record, $fields);
		}
		return $return;
	}#end function




	/*
	 * Добавление типа доступа
	 */
	public function igroupNew($fields=array()){

		if(empty($fields)) return false;
		$add_fields = array_intersect(array_keys($this->defaultIGroupRecord), array_keys($fields));
		$adds = array();
		if(empty($add_fields)) return false;
		foreach($add_fields as $add_field){
			$adds[$add_field] = $fields[$add_field];
		}
		$fields = array_merge($this->defaultIGroupRecord, $adds);
		$ins_names=array();
		$ins_q=array();
		$binds=array();
		foreach($fields as $field=>$value){
			$ins_names[]='`'.$field.'`';
			$ins_q[]='?';
			$binds[]=$value;
		}
		$this->db->prepare('INSERT INTO `iresource_groups` ('.implode(',',$ins_names).')VALUES('.implode(',',$ins_q).')');
		foreach($binds as $bind) $this->db->bind($bind);
		if(($igroup_id = $this->db->insert())===false) return false;

		return $igroup_id;
	}#end function




	/*
	 * Обновление типа доступа
	 */
	public function igroupUpdate($igroup_id=0, $fields=array()){

		if(empty($igroup_id)||empty($fields)) return false;
		$update_fields = array_intersect(array_keys($this->defaultIGroupRecord), array_keys($fields));
		if(empty($update_fields)) return false;
		$binds=array();
		$updates='';
		foreach($update_fields as $update_field){
			$updates.=(empty($updates)?'':',').'`'.$update_field.'`=?';
			$binds[]=$fields[$update_field];
		}
		$this->db->prepare('UPDATE `iresource_groups` SET '.$updates.' WHERE `igroup_id`=?');
		foreach($binds as $bind) $this->db->bind($bind);
		$this->db->bind($igroup_id);

		if($this->db->update()===false) return false;
		return true;
	}#end function




	/*
	 * Удаление типа доступа
	 */
	public function igroupDelete($igroup_id=0, $check_can_delete=true){

		if(empty($igroup_id)) return false;
		if($check_can_delete && !$this->igroupCanDelete($igroup_id)) return false;

		//Проверяем наличие транзакции
		$in_transaction = $this->db->inTransaction();
		if(!$in_transaction) $this->db->transaction();

		//Обновление iresources
		$this->db->prepare('UPDATE `iresources` SET `iresource_group`=0 WHERE `iresource_group`=?');
		$this->db->bind($igroup_id);
		if($this->db->delete()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		//Удаление из iresource_groups
		$this->db->prepare('DELETE FROM `iresource_groups` WHERE `igroup_id`=? LIMIT 1');
		$this->db->bind($igroup_id);
		if($this->db->delete()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}


		//Выполнено успешно
		if(!$in_transaction) $this->db->commit();

		return true;
	}#end function




	/*
	 * Проверка допустимости удаления типа доступа
	 */
	public function igroupCanDelete($igroup_id=0){
		return true;
	}#end function




}#end class

?>