<?php
/*==================================================================================================
Описание: Организационная структура
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');




class Admin_Employers{


	/*==============================================================================================
	Переменные класса
	==============================================================================================*/

	private $is_init		= false;	#ПРизнак корректной инициализации класса
	private $db 			= null;		#Указатель на экземпляр базы данных
	private $dbtoday		= '';		#Текущая дата в формате SQL: CCYY-mm-dd
	private $timestamp		= '';

	private $defaultEmployerRecord = array(
		'status'			=> 1,
		'access_level'		=> 0,
		'username'			=> '',
		'password'			=> '0000',
		'change_password'	=> 0,
		'last_ip_addr'		=> '0.0.0.0',
		'last_ip_real'		=> '0.0.0.0',
		'last_login_time'	=> '0000-00-00 00:00:00',
		'last_login_type'	=> '',
		'search_name'		=> '',
		'first_name'		=> '',
		'last_name'			=> '',
		'middle_name'		=> '',
		'birth_date'		=> '0000-00-00',
		'phone'				=> '',
		'email'				=> '',
		'work_name'			=> '',
		'work_address'		=> '',
		'work_post'			=> '',
		'work_phone'		=> '',
		'create_date'		=> '0000-00-00',
		'language'			=> 'ru',
		'theme'				=> 'default',
		'anket_id'			=> 0,
		'never_assistant'			=> 0,
		'notice_me_requests'		=> 1,
		'notice_curator_requests'	=> 1,
		'notice_gkemail_1'			=> 1,
		'notice_gkemail_2'			=> 1,
		'notice_gkemail_3'			=> 0,
		'notice_gkemail_4'			=> 1,
		'ignore_pin'		=> 0,
		'pin_code'			=> '0000',
		'pin_fails_count'	=> 0,
		'acl_update'		=> 1
	);

	private $defaultGroupRecord = array(
		'full_name'		=> '',
		'short_name'	=> ''
	);



	private $defaultEmployerPostRecord = array(
		'employer_id'	=> 0,
		'post_uid'		=> 0,
		'post_from'		=> '0000-00-00',
		'post_to'		=> '2099-12-31'
	);


	private $defaultAssistantRecord = array(
		'employer_id'	=> 0,
		'assistant_id'	=> 0,
		'submitter_id'	=> 0,
		'from_date'		=> '0000-00-00',
		'to_date'		=> '0000-00-00',
		'timestamp'		=> '0000-00-00 00:00:00'
	);


	private $defaultRightRecord = array(
		'employer_id'		=> 0,
		'company_id'		=> 0,
		'can_add_employer'	=> 0,
		'can_curator'		=> 0
	);


	private $defaultAnketRecord = array(
		'anket_type'		=> 1,
		'approved_time'		=> '0000-00-00 00:00:00',
		'employer_id'		=> 0,
		'curator_id'		=> 0,
		'company_id'		=> 0,
		'post_uid'			=> 0,
		'order_no'			=> '',
		'post_from'			=> '0000-00-00',
		'first_name'		=> '',
		'last_name'			=> '',
		'middle_name'		=> '',
		'birth_date'		=> '0000-00-00',
		'phone'				=> '',
		'email'				=> '',
		'work_computer'		=> 0,
		'need_accesscard'	=> 0,
		'comment'			=> '',
		'create_time'		=> '0000-00-00 00:00:00'
	);





	/*==============================================================================================
	Инициализация
	==============================================================================================*/


	/*
	 * Конструктор класса
	 */
	public function __construct($options=array()){
		$this->db = Database::getInstance('main');
		$this->dbtoday = date('Y-m-d');
	}#end function






	/*==============================================================================================
	ФУНКЦИИ: Работа с группами сотрудников
	==============================================================================================*/


	/*
	 * Получение списка групп
	 */
	public function getGroupsList($fields=null){
		$result = $this->db->select('SELECT * FROM `groups`');
		if(empty($fields)) return $result;
		$return = array();
		foreach($result as $record){
			$return[] = arrayCustomFields($record, $fields);
		}
		return $return;
	}#end function



	/*
	 * Получение списка групп cо списком включенных в группу сотрудников
	 */
	public function getGroupsListEx(){
		if(($groups = $this->db->select('SELECT * FROM `groups`'))===false) return false;
		for($i=0;$i<count($groups);$i++){
			$groups[$i]['employers'] = $this->getGroupEmployers($groups[$i]['group_id'], true);
		}
		return $groups;
	}#end function



	/*
	 * Проверка cуществования группы
	 */
	public function groupExists($group=0){
		if(empty($group)) return false;
		if(is_numeric($group)) 
			$this->db->prepare('SELECT count(*) FROM `groups` WHERE `group_id`=? LIMIT 1');
		else
			$this->db->prepare('SELECT count(*) FROM `groups` WHERE `full_name` LIKE ? LIMIT 1');
		$this->db->bind($group);
		return ($this->db->result() > 0);
	}#end function




	/*
	 * Добавление группы
	 */
	public function groupNew($fields=array()){

		if(empty($fields)) return false;
		$add_fields = array_intersect(array_keys($this->defaultGroupRecord), array_keys($fields));
		$adds = array();
		if(empty($add_fields)) return false;
		foreach($add_fields as $add_field){
			$adds[$add_field] = $fields[$add_field];
		}
		$fields = array_merge($this->defaultGroupRecord, $adds);
		$ins_names=array();
		$ins_q=array();
		$binds=array();
		foreach($fields as $field=>$value){
			$ins_names[]='`'.$field.'`';
			$ins_q[]='?';
			$binds[]=$value;
		}
		$this->db->prepare('INSERT INTO `groups` ('.implode(',',$ins_names).')VALUES('.implode(',',$ins_q).')');
		foreach($binds as $bind) $this->db->bind($bind);
		if(($group_id = $this->db->insert())===false) return false;

		return $group_id;
	}#end function




	/*
	 * Обновление информации о группе
	 */
	public function groupUpdate($group_id=0, $fields=array()){

		if(empty($group_id)||empty($fields)) return false;
		$update_fields = array_intersect(array_keys($this->defaultGroupRecord), array_keys($fields));
		if(empty($update_fields)) return false;
		$binds=array();
		$updates='';
		foreach($update_fields as $update_field){
			$updates.=(empty($updates)?'':',').'`'.$update_field.'`=?';
			$binds[]=$fields[$update_field];
		}
		$this->db->prepare('UPDATE `groups` SET '.$updates.' WHERE `group_id`=?');
		foreach($binds as $bind) $this->db->bind($bind);
		$this->db->bind($group_id);

		if($this->db->update()===false) return false;

		return true;
	}#end function




	/*
	 * Удаление группы
	 */
	public function groupDelete($group_id=0, $check_can_delete=true){

		if(empty($group_id)) return false;
		if($check_can_delete && !$this->groupCanDelete($group_id)) return false;

		//Проверяем наличие транзакции
		$in_transaction = $this->db->inTransaction();
		if(!$in_transaction) $this->db->transaction();

		//Удаление из groups
		$this->db->prepare('DELETE FROM `groups` WHERE `group_id`=?');
		$this->db->bind($group_id);
		if($this->db->delete()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		//Удаление из employer_groups
		$this->db->prepare('DELETE FROM `employer_groups` WHERE `group_id`=?');
		$this->db->bind($group_id);
		if($this->db->delete()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		//Обновление iresources
		$this->db->prepare('UPDATE `iresources` SET `worker_group`=0 WHERE `worker_group`=?');
		$this->db->bind($group_id);
		if($this->db->update()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}


		//Обновление route_params
		$this->db->prepare('UPDATE `route_params` SET `for_group`=0 WHERE `for_group`=?');
		$this->db->bind($group_id);
		if($this->db->update()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}


		//Выполнено успешно
		if(!$in_transaction) $this->db->commit();

		return true;
	}#end function




	/*
	 * Проверка допустимости удаления группы
	 */
	public function groupCanDelete($group_id=0){

		#Проверка использования объекта доступа в шаблонах и заявках
		$this->db->prepare('
			(SELECT count(*) as `count` FROM `route_steps` WHERE `gatekeeper_type`=5 AND `gatekeeper_id`=? LIMIT 1)UNION
			(SELECT count(*) as `count` FROM `route_params` WHERE `for_group`=? LIMIT 1) UNION
			(SELECT count(*) as `count` FROM `iresources` WHERE `worker_group`=? LIMIT 1)
		');
		$this->db->bind($group_id);
		$this->db->bind($group_id);
		$this->db->bind($group_id);

		if(($counts = $this->db->selectFromField('count')) === false) return false;

		return (array_sum($counts) > 0 ? false : true);
	}#end function




	/*
	 * Проверка cуществования сотрудника в группе
	 */
	public function groupEmployerExists($group_id=0, $employer_id=0){
		if(empty($group_id)||empty($employer_id)) return false;
		$this->db->prepare('SELECT count(*) FROM `groups` WHERE `group_id`=? AND `employer_id`=? LIMIT 1');
		$this->db->bind($group_id);
		$this->db->bind($employer_id);
		return ($this->db->result() > 0);
	}#end function




	/*
	 * Получение списка сотрудников, включенных в группу
	 */
	public function getGroupEmployers($group_id=0, $only_ids=false){
		$this->db->prepare('SELECT `employer_id` FROM `employer_groups` WHERE `group_id`=?');
		$this->db->bind($group_id);
		if(($employers = $this->db->selectFromField('employer_id'))===false) return false;
		if(empty($employers)) return array();
		if($only_ids) return $employers;
		return $this->getEmployersList(array('employer_id'=>$employers), array('employer_id','search_name','username','birth_date'), false);
	}#end function





	/*
	 * Добавление сотрудника в группу
	 */
	public function groupIncludeEmployer($group_id=0, $employer_id=0){
		if(empty($group_id)||empty($employer_id)) return false;
		if(!$this->groupExists($group_id)||!$this->employerExists($employer_id)) return false;
		$this->db->prepare('REPLACE INTO `employer_groups` (`group_id`,`employer_id`)VALUES(?,?)');
		$this->db->bind($group_id);
		$this->db->bind($employer_id);
		if($this->db->simple()===false) return false;
		return true;
	}#end function




	/*
	 * Исключение сотрудника из группы
	 */
	public function groupExcludeEmployer($group_id=0, $employer_id=0){
		if(empty($group_id)||empty($employer_id)) return false;
		$this->db->prepare('DELETE FROM `employer_groups` WHERE `group_id`=? AND `employer_id`=?');
		$this->db->bind($group_id);
		$this->db->bind($employer_id);
		if($this->db->simple()===false) return false;
		return true;
	}#end function






	/*==============================================================================================
	ФУНКЦИИ: Работа с анкетами сотрудников
	==============================================================================================*/


	/*
	 * Получение списка анкет
	 */
	public function getAnketsList($ankets=0, $fields=null, $single=false, $extended=false){

		$select = '';
		$select_from_field = false;

		if(empty($fields)){
			$select = 'SELECT ANKET.*, 
				DATE_FORMAT(ANKET.`birth_date`,"%d.%m.%Y") as `birth_date`, 
				DATE_FORMAT(ANKET.`post_from`,"%d.%m.%Y") as `post_from`,
				DATE_FORMAT(ANKET.`approved_time`,"%d.%m.%Y %H:%i:%s") as `approved_time`,
				DATE_FORMAT(ANKET.`create_time`,"%d.%m.%Y %H:%i:%s") as `create_time`';
		}
		elseif(!is_array($fields) && (array_key_exists($fields, $this->defaultAnketRecord) || $fields=='anket_id')){
			$select = 'SELECT ';
			switch($fields){
				case 'birth_date': $select.='DATE_FORMAT(ANKET.`birth_date`,"%d.%m.%Y") as `birth_date`'; break;
				case 'post_from': $select.='DATE_FORMAT(ANKET.`post_from`,"%d.%m.%Y") as `post_from`'; break;
				case 'approved_time': $select.='DATE_FORMAT(ANKET.`approved_time`,"%d.%m.%Y %H:%i:%s") as `approved_time`'; break;
				case 'create_time': $select.='DATE_FORMAT(ANKET.`create_time`,"%d.%m.%Y %H:%i:%s") as `create_time`'; break;
				default: $select.='ANKET.`'.$fields.'` as `'.$fields.'`';
			}
			$select_from_field = true;
		}
		elseif(is_array($fields)){
			foreach($fields as $field){
				if(!array_key_exists($field, $this->defaultAnketRecord) && $field!='anket_id') continue;
				$select.= (empty($select) ? 'SELECT ' : ', ');
				switch($field){
					case 'birth_date': $select.='DATE_FORMAT(ANKET.`birth_date`,"%d.%m.%Y") as `birth_date`'; break;
					case 'post_from': $select.='DATE_FORMAT(ANKET.`post_from`,"%d.%m.%Y") as `post_from`'; break;
					case 'approved_time': $select.='DATE_FORMAT(ANKET.`approved_time`,"%d.%m.%Y %H:%i:%s") as `approved_time`'; break;
					case 'create_time': $select.='DATE_FORMAT(ANKET.`create_time`,"%d.%m.%Y %H:%i:%s") as `create_time`'; break;
					default: $select.='ANKET.`'.$field.'` as `'.$field.'`';
				}
			}
		}

		if(empty($select)) return false;

		if($extended){
			$select.=',
			C.`full_name` as `company_name`,
			P.`full_name` as `post_name`,
			CURATOR.`search_name` as `curator_name`
			FROM `employer_ankets` as ANKET
				INNER JOIN `company_posts` as CP ON CP.`post_uid` = ANKET.`post_uid`
				INNER JOIN `companies` as C ON C.`company_id` = ANKET.`company_id`
				INNER JOIN `posts` as P ON P.`post_id` = CP.`post_id`
				INNER JOIN `employers` as CURATOR ON CURATOR.`employer_id`=ANKET.`curator_id`
			';
		}else{
			$select.=' FROM `employer_ankets` as ANKET';
		}

		if(!is_array($ankets)){
			if(empty($ankets)){
				$result = $this->db->prepare($select.($single?' LIMIT 1':''));
			}else{
				$this->db->prepare($select.' WHERE ANKET.`employer_id`=? '.($single?' LIMIT 1':''));
				$this->db->bind(intval($ankets));
			}
		}else{
			$conditions = $this->db->buildSqlConditions($ankets,'ANKET');
			$this->db->prepare($select.' WHERE '.$conditions.($single?' LIMIT 1':''));
		}

		return ($select_from_field ? $this->db->selectFromField($fields)  : (empty($single) ? $this->db->select() : $this->db->selectRecord()));
	}#end function



	/*
	 * Получение расширенного списка анкет
	 */
	public function getAnketsListEx($ankets=0, $fields=null, $single=false){
		return $this->getAnketsList($ankets, $fields, $single, true);
	}#end function


	/*
	 * Получение информации по анкете
	 */
	public function getAnketInfo($anket_id=0){
		if(empty($anket_id)) return false;
		return $this->getAnketsList($anket_id, null, true, true);
	}#end function



	/*
	 * Обновление анкеты
	 */
	public function anketUpdate($anket_id=0, $fields=array()){

		if(empty($anket_id)||empty($fields)) return false;
		$update_fields = array_intersect(array_keys($this->defaultAnketRecord), array_keys($fields));
		if(empty($update_fields)) return false;
		$binds=array();
		$updates='';
		foreach($update_fields as $update_field){
			$updates.=(empty($updates)?'':',').'`'.$update_field.'`=?';
			$binds[]=$fields[$update_field];
		}
		$this->db->prepare('UPDATE `employer_ankets` SET '.$updates.' WHERE `anket_id`=?');
		foreach($binds as $bind) $this->db->bind($bind);
		$this->db->bind($anket_id);

		if($this->db->update()===false) return false;

		return true;
	}#end function




	/*
	 * Список существующих сотрудников, подходящих под данные анкеты
	 */
	public function anketRelatedEmployers($anket=0){
		if(!is_array($anket)) $anket = $this->getAnketInfo($anket);
		if(empty($anket)) return false;
		$this->db->prepare('
			SELECT `employer_id`,`username`,`status`,`search_name`,`phone`,`email`, DATE_FORMAT(`birth_date`, "%d.%m.%Y") as `birth_date` 
			FROM `employers` WHERE (`birth_date`=? AND `last_name` LIKE ?) OR (`last_name` LIKE ? AND `first_name` LIKE ? AND `middle_name` LIKE ?)
		');
		$this->db->bind(date('Y-m-d', strtotime($anket['birth_date'])));
		$this->db->bind($anket['last_name']);
		$this->db->bind($anket['last_name']);
		$this->db->bind($anket['first_name']);
		$this->db->bind($anket['middle_name']);
		return $this->db->select();
	}#end function





	/*==============================================================================================
	ФУНКЦИИ: Работа с сотрудниками
	==============================================================================================*/



	/*
	 * Проверяет существование сотрудника в БД
	 */
	public function employerExists($employer=0, $ignore_lock=true){
		if(empty($employer)) return false;
		if(!is_array($employer)){
			if(is_numeric($employer))
				$this->db->prepare('SELECT count(*) FROM `employers` WHERE `employer_id`=? ? LIMIT 1');
			else
				$this->db->prepare('SELECT count(*) FROM `employers` WHERE `username` LIKE ? ? LIMIT 1');
			$this->db->bind($employer);
			$this->db->bindSql((!$ignore_lock?' AND `status`>0 ':''));
			return ($this->db->result() > 0);
		}

		$conditions = $this->db->buildSqlConditions($employer);
		return ($this->db->result('SELECT count(*) FROM `employers` WHERE '.$conditions.' LIMIT 1') > 0);
	}#end function





	/*
	 * Получение списка сотрудников
	 */
	public function getEmployersList($employers=0, $fields=null, $single=false, $extended=false){

		$select = '';
		$select_from_field = false;

		if(empty($fields)){
			$select = 'SELECT EMPL.*, 
				DATE_FORMAT(EMPL.`birth_date`,"%d.%m.%Y") as `birth_date`, 
				DATE_FORMAT(EMPL.`create_date`,"%d.%m.%Y %H:%i:%s") as `create_date`';
		}
		elseif(!is_array($fields) && (array_key_exists($fields, $this->defaultEmployerRecord) || $fields=='employer_id')){
			$select = 'SELECT ';
			switch($fields){
				case 'birth_date': $select.='DATE_FORMAT(EMPL.`birth_date`,"%d.%m.%Y") as `birth_date`'; break;
				case 'create_time': $select.='DATE_FORMAT(EMPL.`create_date`,"%d.%m.%Y") as `create_date`'; break;
				default: $select.='EMPL.`'.$fields.'` as `'.$fields.'`';
			}
			$select_from_field = true;
		}
		elseif(is_array($fields)){
			foreach($fields as $field){
				if(!array_key_exists($field, $this->defaultEmployerRecord) && $field!='employer_id') continue;
				$select.= (empty($select) ? 'SELECT ' : ', ');
				switch($field){
					case 'birth_date': $select.='DATE_FORMAT(EMPL.`birth_date`,"%d.%m.%Y") as `birth_date`'; break;
					case 'create_date': $select.='DATE_FORMAT(EMPL.`create_date`,"%d.%m.%Y") as `create_date`'; break;
					default: $select.='EMPL.`'.$field.'` as `'.$field.'`';
				}
			}
		}

		if(empty($select)) return false;

		$select.=' FROM `employers` as EMPL';

		if(!is_array($employers)){
			if(empty($employers)){
				$result = $this->db->prepare($select.($single?' LIMIT 1':' LIMIT 100'));
			}else{
				$this->db->prepare($select.' WHERE EMPL.`employer_id`=? '.($single?' LIMIT 1':' LIMIT 100'));
				$this->db->bind(intval($employers));
			}
		}else{
			$conditions = $this->db->buildSqlConditions($employers,'EMPL');
			$this->db->prepare($select.' WHERE '.$conditions.($single?' LIMIT 1':' LIMIT 100'));
		}

		$result = ($select_from_field ? $this->db->selectFromField($fields)  : (empty($single) ? $this->db->select() : $this->db->selectRecord()));

		if(!$extended || $select_from_field) return $result;

		if(!empty($single)){
			$result['posts'] = $this->getEmployersPostsEx($result['employer_id'],false,array('company_name','post_name','boss_post_name'));
			return $result;
		}

		for($i=0; $i<count($result);$i++){
			$result[$i]['posts'] = $this->getEmployersPostsEx($result[$i]['employer_id'],false,array('company_name','post_name','boss_post_name'));
		}

		return $result;
	}#end function



	/*
	 * Получение списка сотрудников
	 */
	public function getEmployersListEx($employers=0, $fields=null, $single=false){
		$this->getEmployersList($employers, $fields, $single, true);
	}#end function





	/*
	 * Возвращает список групп, в которые включены сотрудники
	 */
	public function getEmployersGroups($employers=0){
		$employers = (is_array($employers) ? implode(',', array_map('intval',$employers)) : intval($employers));
		return $this->db->selectFromField('group_id', 'SELECT DISTINCT `group_id` FROM `employer_groups` WHERE `employer_id` IN ('.$employers.')');
	}#end function



	/*
	 * Проверяет, занимает ли сотрудник указанную должность
	 */
	public function employerPostExists($employer_id=0, $post_uid=0){
		$this->db->prepare('SELECT DISTINCT count(*) FROM `employer_posts` WHERE `employer_id`=? AND `post_uid`=? LIMIT 1');
		$this->db->bind($employer_id);
		$this->db->bind($post_uid);
		return ($this->db->result() > 0);
	}#end function




	/*
	 * Возвращает информацию о должности сотрудника
	 */
	public function employerPostInfo($employer_id=0, $post_uid=0){
		$this->db->prepare('
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
			FROM `employer_posts` as EP 
				INNER JOIN `company_posts` as CP ON CP.`post_uid` = EP.`post_uid`
				INNER JOIN `companies` as C ON C.`company_id` = CP.`company_id`
				INNER JOIN `posts` as P ON P.`post_id` = CP.`post_id`
			WHERE EP.`employer_id`=? AND EP.`post_uid`=? 
			LIMIT 1
		');
		$this->db->bind($employer_id);
		$this->db->bind($post_uid);
		return $this->db->selectRecord();
	}#end function




	/*
	 * Возвращает список должностей, занимаемых сотрудниками
	 */
	public function getEmployersPosts($employers=0){
		$employers = (is_array($employers) ? implode(',', array_map('intval',$employers)) : intval($employers));
		return $this->db->selectFromField('post_uid', 'SELECT DISTINCT `post_uid` FROM `employer_posts` WHERE `employer_id` IN ('.$employers.')');
	}#end function




	/*
	 * Возвращает список должностей, занимаемых сотрудниками
	 */
	public function getEmployersPostsEx($employers=0, $bosslist=false, $fields=null){

		if(empty($employers)) return false;
		if(!is_array($employers)) $employers = array($employers);
		$employers = implode(',', array_map('intval',$employers));

		$this->db->prepare('
			SELECT 
				EP.`employer_id` AS `employer_id`,
				DATE_FORMAT(EP.`post_from`,"%d.%m.%Y") as `post_from`,
				DATE_FORMAT(EP.`post_to`,"%d.%m.%Y") as `post_to`,
				CP.`post_uid` AS `post_uid`,
				CP.`post_id` AS `post_id`,
				CP.`boss_uid` AS `boss_uid`,
				CP.`boss_id` AS `boss_id`,
				P.`full_name` AS `post_name`,
				PBOSS.`full_name` AS `boss_post_name`,
				C.`company_id` AS `company_id`,
				C.`full_name` AS `company_name`
			FROM `employer_posts` as EP
				INNER JOIN `company_posts` as CP ON CP.`post_uid` = EP.`post_uid`
				INNER JOIN `companies` as C ON C.`company_id` = CP.`company_id`
				INNER JOIN `posts` as P ON P.`post_id` = CP.`post_id`
				LEFT JOIN `posts` as PBOSS ON PBOSS.`post_id` = CP.`boss_id`
			WHERE EP.`employer_id` IN ('.$employers.')
		');
		$result = $this->db->select();

		if(empty($fields)&&empty($bosslist)) return $result;
		$return = array();
		foreach($result as $record){
			$data = empty($fields) ? $record : arrayCustomFields($record, $fields);
			if(!empty($bosslist)){
				if(!empty($record['boss_uid'])){
					$record['bosslist'] = $this->getEmployersOnPostEx($record['boss_uid']);
				}else{
					$record['bosslist'] = array();
				}
			}
			$return[] = $record;
		}

		return $return;
	}#end function




	/*
	 * Возвращает список сотрудников, занимаемых определенную должность
	 */
	public function getEmployersOnPost($post_uid=0, $from=null, $to=null){
		if(empty($post_uid)) return false;
		$from = !empty($from) ? $from : $this->dbtoday;
		$to = !empty($to) ? $to : $this->dbtoday;
		$this->db->prepare('SELECT DISTINCT `employer_id` FROM `employer_posts` WHERE EP.`post_uid`=? AND EP.`post_from`<=? AND EP.`post_to`>=?');
		$this->db->bind($post_uid);
		$this->db->bind($from);
		$this->db->bind($to);
		return $this->db->selectFromField('post_uid');
	}#end function




	/*
	 * Возвращает список сотрудников, занимаемых определенную должность
	 */
	public function getEmployersOnPostEx($post_uid=0, $from=null, $to=null){

		if(empty($post_uid)) return false;
		$from = !empty($from) ? $from : $this->dbtoday;
		$to = !empty($to) ? $to : $this->dbtoday;
		$this->db->prepare('
			SELECT 
				EP.`employer_id` AS `employer_id`,
				DATE_FORMAT(EP.`post_from`,"%d.%m.%Y") as `post_from`,
				DATE_FORMAT(EP.`post_to`,"%d.%m.%Y") as `post_to`,
				EMP.`search_name` as `employer_name`,
				EMP.`username` as `username`
			FROM `employer_posts` as EP
				INNER JOIN `employers` as EMP ON EMP.`employer_id`= EP.`employer_id` AND EMP.`status` > 0
			WHERE EP.`post_uid`=? AND EP.`post_from`<=? AND EP.`post_to`>=?
		');
		$this->db->bind($post_uid);
		$this->db->bind($from);
		$this->db->bind($to);

		return $this->db->select();
	}#end function




	/*
	 * Удаляет у сотрудника должность
	 */
	public function employerPostDelete($employer_id=0, $post_uid=0){
		$this->db->prepare('DELETE FROM `employer_posts` WHERE `employer_id`=? and `post_uid`=?');
		$this->db->bind($employer_id);
		$this->db->bind($post_uid);
		if($this->db->delete()===false) return false;
		return true;
	}#end function




	/*
	 * Возвращает список заместителей сотрудника
	 */
	public function getEmployersAssistants($employers=0, $all=false){
		$employers = (is_array($employers) ? implode(',', array_map('intval',$employers)) : intval($employers));
		$this->db->prepare('SELECT DISTINCT ASSIST.`assistant_id` as `assistant_id` FROM `assistants` as ASSIST
			INNER JOIN `employers` as EMP ON EMP.`employer_id`=ASSIST.`assistant_id` AND EMP.`status` > 0
			WHERE ASSIST.`employer_id` IN ('.$employers.') '.(empty($all) ? ' AND ASSIST.`from_date` <= "'.$this->dbtoday.'" AND ASSIST.`to_date` >= "'.$this->dbtoday.'" ' : '').'
		');
		return $this->db->selectFromField('assistant_id');
	}#end function




	/*
	 * Возвращает список заместителей сотрудника
	 */
	public function getEmployersAssistantsEx($employers=0, $all=false){
		$employers = (is_array($employers) ? implode(',', array_map('intval',$employers)) : intval($employers));
		$this->db->prepare('
			SELECT 
				ASSIST.`assistant_id` as `employer_id`,
				EMP.`search_name` as `employer_name`,
				EMP.`username` as `username`,
				DATE_FORMAT(EMP.`birth_date`,"%d.%m.%Y") as `birth_date`,
				EMP.`phone` as `phone`,
				EMP.`email` as `email`,
				DATE_FORMAT(ASSIST.`from_date`,"%d.%m.%Y") as `from_date`,
				DATE_FORMAT(ASSIST.`to_date`,"%d.%m.%Y") as `to_date`
			FROM `assistants` as ASSIST
				INNER JOIN `employers` as EMP ON EMP.`employer_id`=ASSIST.`assistant_id` AND EMP.`status` > 0
			WHERE ASSIST.`employer_id` IN ('.$employers.') '.(empty($all) ? ' AND ASSIST.`from_date` <= "'.$this->dbtoday.'" AND ASSIST.`to_date` >= "'.$this->dbtoday.'" ' : '').'
		');

		return $this->db->select();
	}#end function




	/*
	 * Возвращает список делегировавших сотруднику свои полномочия
	 */
	public function getEmployersDelegates($employers=0, $all=false){
		$employers = (is_array($employers) ? implode(',', array_map('intval',$employers)) : intval($employers));
		$this->db->prepare('SELECT DISTINCT ASSIST.`employer_id` as `employer_id` FROM `assistants` as ASSIST
			INNER JOIN `employers` as EMP ON EMP.`employer_id`=ASSIST.`assistant_id` AND EMP.`status` > 0
			WHERE ASSIST.`assistant_id` IN ('.$employers.') '.(empty($all) ? ' AND ASSIST.`from_date` <= "'.$this->dbtoday.'" AND ASSIST.`to_date` >= "'.$this->dbtoday.'" ' : '').'
		');
		return $this->db->selectFromField('employer_id');
	}#end function




	/*
	 * Возвращает список делегировавших сотруднику свои полномочия
	 */
	public function getEmployersDelegatesEx($employers=0, $all=false){
		$employers = (is_array($employers) ? implode(',', array_map('intval',$employers)) : intval($employers));
		$this->db->prepare('
			SELECT 
				ASSIST.`employer_id` as `employer_id`,
				EMP.`search_name` as `employer_name`,
				EMP.`username` as `username`,
				DATE_FORMAT(EMP.`birth_date`,"%d.%m.%Y") as `birth_date`,
				EMP.`phone` as `phone`,
				EMP.`email` as `email`,
				DATE_FORMAT(ASSIST.`from_date`,"%d.%m.%Y") as `from_date`,
				DATE_FORMAT(ASSIST.`to_date`,"%d.%m.%Y") as `to_date`
			FROM `assistants` as ASSIST
				INNER JOIN `employers` as EMP ON EMP.`employer_id`=ASSIST.`employer_id` AND EMP.`status` > 0
			WHERE ASSIST.`assistant_id` IN ('.$employers.') '.(empty($all) ? ' AND ASSIST.`from_date` <= "'.$this->dbtoday.'" AND ASSIST.`to_date` >= "'.$this->dbtoday.'" ' : '').'
		');

		return $this->db->select();
	}#end function




	/*
	 * Возвращает историю замещений сотрудника
	 */
	public function getEmployersAssistantsHistory($employers=0){
		$employers = (is_array($employers) ? implode(',', array_map('intval',$employers)) : intval($employers));
		$this->db->prepare('
			SELECT 
				ASSIST.`employer_id` as `employer_id`,
				ASSIST.`assistant_id` as `assistant_id`,
				ASSIST.`submitter_id` as `submitter_id`,
				EMP.`search_name` as `employer_name`,
				EMPA.`search_name` as `assistant_name`,
				EMPS.`search_name` as `submitter_name`,
				DATE_FORMAT(ASSIST.`from_date`,"%d.%m.%Y") as `from_date`,
				DATE_FORMAT(ASSIST.`to_date`,"%d.%m.%Y") as `to_date`,
				DATE_FORMAT(ASSIST.`timestamp`,"%d.%m.%Y %H:%i:%s") as `timestamp`
			FROM `assistants_hist` as ASSIST
				INNER JOIN `employers` as EMP ON EMP.`employer_id`=ASSIST.`employer_id`
				INNER JOIN `employers` as EMPA ON EMPA.`employer_id`=ASSIST.`assistant_id`
				INNER JOIN `employers` as EMPS ON EMPS.`employer_id`=ASSIST.`submitter_id`
			WHERE ASSIST.`employer_id` IN ('.$employers.') OR ASSIST.`assistant_id` IN ('.$employers.')
			ORDER BY ASSIST.`timestamp`
		');

		return $this->db->select();
	}#end function




	/*
	 * Возвращает список должностей, которые подчиняются сотруднику
	 */
	public function getEmployersWorkers($employers=0){
		$postlist = $this->getEmployersPosts($employers);
		if(empty($postlist)) return array();
		return $this->db->selectFromField('post_uid', 'SELECT DISTINCT `post_uid` FROM `company_posts` WHERE `boss_uid` IN ('.implode(',',$postlist).')');
	}#end function




	/*
	 * Возвращает список организаций, в которых сотрудник имеет какие-либо права на оформление анкет или заявок
	 */
	public function getEmployersRights($employers=0){
		$employers = (is_array($employers) ? implode(',', array_map('intval',$employers)) : intval($employers));
		return $this->db->select('SELECT * FROM `employer_rights` WHERE `employer_id` IN ('.$employers.')');
	}#end function




	/*
	 * Возвращает список сертификатов сотрудников
	 */
	public function getEmployersCertificates($employers=0, $fields=null){
		$employers = (is_array($employers) ? implode(',', array_map('intval',$employers)) : intval($employers));
		$result = $this->db->select('SELECT * FROM `employer_certs` WHERE `employer_id` IN ('.$employers.')');
		if(empty($fields)) return $result;
		if(!is_array($fields)) $fields=array($fields);
		$return = array();
		foreach($result as $record){
			$return[] = arrayCustomFields($record, $fields);
		}
		return $return;
	}#end function





	/*
	 * Добавление сотрудника
	 */
	public function employerNew($fields=array(), $create_aduser=true){

		if(empty($fields)) return false;
		$add_fields = array_intersect(array_keys($this->defaultEmployerRecord), array_keys($fields));
		$adds = array();
		if(empty($add_fields)) return false;
		foreach($add_fields as $add_field){
			$adds[$add_field] = $fields[$add_field];
		}

		if(empty($adds['first_name'])||empty($adds['last_name'])||empty($adds['middle_name'])) return false;
		$adds['first_name']	= mb_convert_case($adds['first_name'], MB_CASE_TITLE, 'UTF-8');
		$adds['last_name']	= mb_convert_case($adds['last_name'], MB_CASE_TITLE, 'UTF-8');
		$adds['middle_name']= mb_convert_case($adds['middle_name'], MB_CASE_TITLE, 'UTF-8');
		$adds['search_name']= $adds['last_name'].' '.$adds['first_name'].' '.$adds['middle_name'];

		$company_name = isset($fields['company_name']) ? trim($fields['company_name']) : '';
		$post_name = isset($fields['post_name']) ? trim($fields['post_name']) : '';

		$create_ad = ($create_aduser && Config::getOption('general','new_user_create_ad',false));

		if($create_ad){
			$ad_domain = Config::getOption('ldap','ad_domain', false);
			$ad_controllers = Config::getOption('ldap','ad_controllers', false);
			$ad_username = Config::getOption('ldap','ad_username', false);
			$ad_password = Config::getOption('ldap','ad_password', false);
			$ad_base_dn = Config::getOption('ldap','ad_base_dn', false);
			$ad_container = Config::getOption('ldap','ad_container', false);
			$ad_change_pwd = Config::getOption('ldap','ad_change_pwd', true);
			if(empty($ad_domain)||empty($ad_controllers)||empty($ad_username)||empty($ad_password)||empty($ad_base_dn)||empty($ad_container)) return false;
			try {
				$adldap = new adLDAP(array(
					'use_ssl' => true,
					'use_tls' => false,
					'domain_controllers' => $ad_controllers,
					'base_dn'=>$ad_base_dn
				));
			}
			catch (adLDAPException $e){
				return false;
			}

			if (!$adldap->authenticate($ad_username, $ad_password)){
				$adldap->close();
				return false;
			}
		}

		//Генерация имени пользователя
		if(empty($adds['username'])){
			$employer_login_count = 0;
			$employer_login = $this->employerLoginGen($adds['search_name'], Config::getOption('general','new_login_format','{Last}-{F}{M}'));
			$employer_login_for_check = $employer_login;

			do{
				$employer_login_count++;
				$employer_login_for_check = ($employer_login_count > 1 ? $employer_login.$employer_login_count : $employer_login);

				$employer_info = $this->employerExists($employer_login_for_check);
				if(empty($employer_info)){
					if($create_ad) $employer_info = $adldap->user()->find(false,'samaccountname', $employer_login_for_check);
				}
			}while(!empty($employer_info));
			$adds['username'] = $employer_login_for_check;
		}

		//Генерация пароля
		if(empty($adds['password'])){
			$adds['password'] = $this->employerPasswordGen(Config::getOption('general','new_password_length',8), Config::getOption('general','new_password_strength',3));
		}

		//Генерация PIN-кода
		if(empty($adds['pin_code'])){
			$adds['pin_code'] = mt_rand(1000,9999);
		}

		//Генерация email
		if(empty($adds['email'])){
			$new_email_format = Config::getOption('general','new_email_format',false);
			if(!empty($new_email_format)){
				$adds['email'] = strtolower($adds['username']).'@'.ltrim(trim($new_email_format),'@');
			}
		}

		if(!empty($adds['birth_date'])){
			$adds['birth_date'] = date('Y-m-d', strtotime($adds['birth_date']));
		}

		$adds['create_date'] = $this->dbtoday;

		$fields = array_merge($this->defaultEmployerRecord, $adds);
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

		$this->db->prepare('INSERT INTO `employers` ('.implode(',',$ins_names).')VALUES('.implode(',',$ins_q).')');
		foreach($binds as $bind) $this->db->bind($bind);
		if(($fields['employer_id'] = $this->db->insert())===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		//AD Create
		if($create_ad){

			//print_r($fields);exit;
			if(empty($adds['phone']))$adds['phone']='n/a';

			$attributes=array(
				"username"			=> $adds['username'],
				"logon_name"		=> $adds['username'].'@'.$ad_domain,
				"firstname"			=> $adds['first_name'],
				"surname"			=> $adds['last_name'],
				"display_name"		=> $adds['search_name'],
				"canonical_name"	=> $this->employerLoginGen($adds['search_name'], '{Last} {First} {M}.').($employer_login_count > 1 ? " (".$employer_login_count.")" : ""),
				"title"				=> (empty($post_name)?'undefined':$post_name),
				"company"			=> (empty($company_name)?'undefined':$company_name),
				"email"				=> $adds['email'],
				"homephone"			=> $adds['phone'],
				"telephone"			=> $adds['phone'],
				"container"			=> $ad_container,
				"change_password"	=> ($ad_change_pwd ? '1' : '0'),
				"enabled"			=> 1,
				"password"			=> $adds['password'],
				"description"		=> 'Created from Cascade employeeId #'.$fields['employer_id'].' anketId #'.$fields['anket_id'].''
			);


			//print_r($attributes);exit;

			try {
				$result = $adldap->user()->create($attributes);
			}
			catch (adLDAPException $e){
				echo $e;
				if(!$in_transaction) $this->db->rollback();
				return false;
			}

			if(!$result){
				if(!$in_transaction) $this->db->rollback();
				return false;
			}

		}////AD Create


		if(!$in_transaction) $this->db->commit();
		return $fields;
	}#end function





	/*
	 * Генерация логина сотрудника на английском языке исходя из его ФИО по формату
	 */
	public function employerLoginGen($search_name='', $format=null){

		if(empty($search_name)) return false;
		if(empty($format)) $format = Config::getOption('general','new_login_format','{Last}-{F1}{M1}');

		$search_name = explode(' ',trim($search_name));
		if(count($search_name)<3) return false;
		$last		= strtolower(rus2eng($search_name[0]));
		$first		= strtolower(rus2eng($search_name[1]));
		$middle		= strtolower(rus2eng($search_name[2]));
		$l1			= substr($last,0,1);
		$f1			= substr($first,0,1);
		$m1			= substr($middle,0,1);
		$l	= strtolower(rus2eng(mb_substr($search_name[0],0,1,'UTF-8')));
		$f	= strtolower(rus2eng(mb_substr($search_name[1],0,1,'UTF-8')));
		$m	= strtolower(rus2eng(mb_substr($search_name[2],0,1,'UTF-8')));
		return str_replace(
			array(
				'{F}','{M}','{L}',
				'{f}','{m}','{l}',
				'{First}','{Middle}','{Last}',
				'{first}','{middle}','{last}',
				'{F1}','{M1}','{L1}',
				'{f1}','{m1}','{l1}',
			),
			array(
				strtoupper($f), strtoupper($m), strtoupper($l), 
				$f, $m, $l, 
				ucfirst($first), ucfirst($middle), ucfirst($last),
				$first, $middle, $last,
				strtoupper($f1), strtoupper($m1), strtoupper($l1), 
				$f1, $m1, $l1 
			),
			$format
		);
	}#end function




	/*
	 * Генерация пароля
	 */
	public function employerPasswordGen($length=8, $strength=3){
		$chunks = array(
			0 => 'abcdefghijklmnopqrstuvwxyz',
			1 => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
			2 => '1234567890',
			3 => '_-@#$%'
		);
		$strength = max(0, min(3,$strength));
		$length = max($length, $strength+2);
		$password_array = array_fill(0, $length-1, false);
		for($i=0;$i<=$strength;$i++){
			$password_array[$i+1] = $chunks[$i][(mt_rand() % strlen($chunks[$i]))];
		}
		shuffle($password_array);
		array_unshift($password_array, false);

		for($j=0;$j<count($password_array);$j++){
			if(!empty($password_array[$j])) continue;
			$i = mt_rand(0,($j>0?$strength:min(1,$strength)));
			$password_array[$j]= $chunks[$i][(mt_rand() % strlen($chunks[$i]))];
		}

		return implode('',$password_array);
	}#end function





	/*
	 * Обновление информации о группе
	 */
	public function employerUpdate($employer_id=0, $fields=array()){

		if(empty($employer_id)||empty($fields)) return false;
		$update_fields = array_intersect(array_keys($this->defaultEmployerRecord), array_keys($fields));
		if(empty($update_fields)) return false;
		$binds=array();
		$updates='';
		foreach($update_fields as $update_field){
			$updates.=(empty($updates)?'':',').'`'.$update_field.'`=?';
			$binds[]=$fields[$update_field];
		}
		$this->db->prepare('UPDATE `employers` SET '.$updates.' WHERE `employer_id`=?');
		foreach($binds as $bind) $this->db->bind($bind);
		$this->db->bind($employer_id);

		if($this->db->update()===false) return false;

		return true;
	}#end function






	/*==============================================================================================
	ФУНКЦИИ: Работа с должностями сотрудников
	==============================================================================================*/


	/*
	 * Добавление должности сотруднику
	 */
	public function employerPostAdd($fields=array()){

		if(empty($fields)) return false;
		$add_fields = array_intersect(array_keys($this->defaultEmployerPostRecord), array_keys($fields));
		$adds = array();
		if(empty($add_fields)) return false;
		foreach($add_fields as $add_field){
			$adds[$add_field] = $fields[$add_field];
		}
		$fields = array_merge($this->defaultEmployerPostRecord, $adds);
		$ins_names=array();
		$ins_q=array();
		$binds=array();
		foreach($fields as $field=>$value){
			$ins_names[]='`'.$field.'`';
			$ins_q[]='?';
			$binds[]=$value;
		}
		$this->db->prepare('INSERT INTO `employer_posts` ('.implode(',',$ins_names).')VALUES('.implode(',',$ins_q).')');
		foreach($binds as $bind) $this->db->bind($bind);
		if(($id = $this->db->insert())===false) return false;

		return $id;
	}#end function



	/*
	 * Проверяет, занимает ли сотрудник указанную должность
	 */
	public function employerOnPost($employer_id=0,$post_uid=0){
		if(empty($employer_id)||empty($post_uid)) return false;
		$this->db->prepare('SELECT count(*) FROM `employer_posts` WHERE `employer_id`=? AND `post_uid`=? LIMIT 1');
		$this->db->bind($employer_id);
		$this->db->bind($post_uid);
		return ($this->db->result() > 0);
	}#end function





	/*
	 * Добавляет сотруднику права в указанной организации
	 */
	public function employerSetRight($employer_id=0, $company_id=0, $right_type='', $allowed=false){

		if(empty($employer_id)) return false;
		if(!isset($this->defaultRightRecord[$right_type])) return false;

		#Проверка существования записи о правах у сотрудника
		if(($exists = $this->db->result('SELECT count(*) FROM `employer_rights` WHERE `company_id`='.$company_id.' AND `employer_id`='.$employer_id.' LIMIT 1')) === false ){
			return false;
		}

		#Если записи нет - добавляем
		if(!$exists){
			$add_fields = $this->defaultRightRecord;
			$fields = array();
			foreach($this->defaultRightRecord as $add_field=>$def_value){
				$fields[$add_field] = $def_value;
			}
			$fields['employer_id'] = $employer_id;
			$fields['company_id'] = $company_id;
			$fields[$right_type] = ($allowed ? '1':'0');
			$ins_names=array();
			$ins_q=array();
			$binds=array();
			foreach($fields as $field=>$value){
				$ins_names[]='`'.$field.'`';
				$ins_q[]='?';
				$binds[]=$value;
			}
			$this->db->prepare('INSERT INTO `employer_rights` ('.implode(',',$ins_names).')VALUES('.implode(',',$ins_q).')');
			foreach($binds as $bind) $this->db->bind($bind);
			if($this->db->insert()===false) return false;
		}else{
			$this->db->prepare('UPDATE `employer_rights` SET `'.$right_type.'`=? WHERE `company_id`=? AND `employer_id`=?');
			$this->db->bind($allowed ? '1':'0');
			$this->db->bind($company_id);
			$this->db->bind($employer_id);
			if($this->db->update() === false) return false;
		}

		return true;
	}#end function






	/*==============================================================================================
	ФУНКЦИИ: Работа с делегированием
	==============================================================================================*/


	/*
	 * Добавление ассистента сотруднику
	 */
	public function assistantAdd($fields=array()){

		if(empty($fields)) return false;
		$add_fields = array_intersect(array_keys($this->defaultAssistantRecord), array_keys($fields));
		$adds = array();
		if(empty($add_fields)) return false;
		foreach($add_fields as $add_field){
			$adds[$add_field] = $fields[$add_field];
		}
		$fields = array_merge($this->defaultAssistantRecord, $adds);
		$fields['timestamp'] = date('Y-m-d H:i:s');
		$fields['submitter_id'] = User::_getEmployerID();
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

		$this->db->prepare('INSERT INTO `assistants` ('.implode(',',$ins_names).')VALUES('.implode(',',$ins_q).')');
		foreach($binds as $bind) $this->db->bind($bind);
		if($this->db->insert()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		$this->db->prepare('INSERT INTO `assistants_hist` ('.implode(',',$ins_names).')VALUES('.implode(',',$ins_q).')');
		foreach($binds as $bind) $this->db->bind($bind);
		if($this->db->insert()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		if(!$in_transaction) $this->db->commit();

		return true;
	}#end function




	/*
	 * Удаление ассистента у сотрудника
	 */
	public function assistantDelete($employer_id=0, $assistant_id=0){
		$this->db->prepare('DELETE FROM `assistants` WHERE `employer_id`=? AND `assistant_id`=?');
		$this->db->bind(intval($employer_id));
		$this->db->bind(intval($assistant_id));
		if($this->db->delete()===false) return false;
		return true;
	}#end function







}#end class

?>
