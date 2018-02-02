<?php
/*==================================================================================================
Описание: Сотрудник
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');




class Main_Employer{


	/*==============================================================================================
	Переменные класса
	==============================================================================================*/

	public $options = array(
		'db' => null,
		'employer_id' => 0
	);
	private $is_init		= false;	#ПРизнак корректной инициализации класса
	private $db 			= null;		#Указатель на экземпляр базы данных
	private $employer_id 	= 0;		#Идентификатор сотрудника
	private $dbtoday		= '';		#Сегодняшняя дата

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Пояснения:
	 * 
	 * Данные массивов $myself и $delegates используются в процессе определения
	 * вовлеченности конкретного сотрудника в процесс согласования заявок
	 * 
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	#Информация непосредственно о сотруднике
	public $myself = array(
		'info'			=> null,		#Информация о сотруднике (запись из таблицы `employers`)
		'posts'			=> null,		#Список должностей, занимаемых сотрудником
		'groups'		=> null,		#Список групп, в которые включен сотрудник
		'irowner'		=> null,		#Список информационных ресурсов, для которых сотрудник является владельцем
		'companyhead'	=> null,		#Список организаций, в которых сотрудник является руководителем
		'assistants'	=> null,		#Список заместителей сотрудника
		'delegates'		=> null,		#Список делегировавших сотруднику свои полномочия
		'workers'		=> null			#Список должностей, которые подчиняются данному сотруднику
	);

	#Информация о делегировавших полномочия, общие списки по всем делегировавшим
	#см $this->dbDelegatesInfo()
	public $delegates = null;

	#Объединенные списки сотрудника + делегировавших полномочия
	public $gatekeeperUnionLists = null;

	#Список активных заявок (заявок в процессе согласования), в которых сотрудник участвует как гейткипер (согласование, утверждение)
	public $gatekeeperRequests = null;

	#Список активных заявок (заявок в процессе согласования), в которых сотрудник участвует исполнитель
	public $performerRequests = null;

	#Список активных заявок (заявок в процессе согласования), в которых сотрудник участвует как гейткипер (согласование, утверждение) или как исполнитель
	public $allActiveRequests = null;

	#Внутренние списки запросов, с которыми идет работа
	#Здесь кешируется информация о вызванных заявках, чтобы не делать запросы к БД по нескольку раз
	private $internalCache = array(
		'requests' => array()
	);


	/*==============================================================================================
	Инициализация
	==============================================================================================*/


	/*
	 * Конструктор класса
	 */
	public function __construct($options=array()){
		$this->setOptions($options);
		$this->dbtoday = date('Y-m-d');
	}#end function



	/*
	 * Применить опции к классу
	 */
	public function setOptions($options){
		$this->options = array_merge($this->options, $options);
		foreach($this->options as $key=>$value){
			switch($key){
				case 'db': 
					$this->db = empty($value) ? Database::getInstance('main') : $value;
				break;
				case 'employer_id':
					$this->employer_id = empty($value) ? User::_getEmployerID() : intval($value);
				break;
			}
		}
		$this->is_init = (!empty($this->db) && !empty($this->employer_id)) ? true : false;
	}#end function




	/*==============================================================================================
	Информация по сотруднику, участвующая в процессе согласования
	==============================================================================================*/



	/*
	 * Определение ролей сотрудника в отношении ИР в заявке
	 */
	public function dbWatcherRoles($employer_id=0, $request_id=0, $iresource_id=0){

		$default = array(
			'is_owner'		=> false,
			'is_curator'	=> false,
			'is_gatekeeper'	=> false,
			'is_performer'	=> false,
			'is_watcher'	=> false
		);
		if(!$this->is_init) return $default;
		$employer_id = intval($employer_id);
		$request_id = intval($request_id);
		$iresource_id = intval($iresource_id);
		if(empty($employer_id)) $employer_id = $this->employer_id;
		$this->db->prepare('SELECT `is_owner`,`is_curator`,`is_gatekeeper`,`is_performer`,`is_watcher` FROM `request_watch` WHERE `request_id`=? AND `iresource_id` IN (0,?) AND `employer_id`=? LIMIT 1');
		$this->db->bind($request_id);
		$this->db->bind($iresource_id);
		$this->db->bind($employer_id);
		$record = $this->db->selectRecord();
		return (empty($record)||!is_array($record)) ? $default : $record;
	}#end function



	/*
	 * Проверяет существование сотрудника в БД
	 */
	public function dbEmployerExists($employer_id=0, $ignore_lock=false){
		if(!$this->is_init) return false;
		if(empty($employer_id)) $employer_id = $this->employer_id;
		$this->db->prepare('SELECT count(*) FROM `employers` WHERE `employer_id`=? ? LIMIT 1');
		$this->db->bind($employer_id);
		$this->db->bindSql((!$ignore_lock?' AND `status`>0 ':''));
		return ($this->db->result() == 1);
	}#end function




	/*
	 * Возвращает информацию о сотруднике / сотрудниках
	 */
	public function dbEmployerInfo($employer_id=0, $single=false, $fields=null){
		if(!$this->is_init) return false;
		if(empty($employer_id)) $employer_id = $this->employer_id;
		if(is_array($employer_id)) $employer_id = implode(',',$employer_id);

		$this->db->prepare('
			SELECT EMP.*, 
			DATE_FORMAT(EMP.`birth_date`,"%d.%m.%Y") as `birth_date`,
			DATE_FORMAT(EMP.`create_date`,"%d.%m.%Y") as `create_date` 
			FROM `employers` as EMP WHERE EMP.`employer_id` IN (?) ?
		');
		$this->db->bindSql($employer_id);
		$this->db->bindSql((empty($single)?'':' LIMIT 1'));

		$result = (empty($single) ? $this->db->select() : $this->db->selectRecord());

		if(!is_array($fields)) return $result;
		if(!empty($single)) return $this->getRecordWithCustomFields($result, $fields);

		$return = array();
		foreach($result as $record){
			$return[] = $this->getRecordWithCustomFields($record, $fields);
		}
		return $return;
	}#end function





	/*
	 * Возвращает список групп, в которые включен сотрудник
	 */
	public function dbEmployerGroups($employer_id=0){
		if(!$this->is_init) return false;
		if(empty($employer_id)) $employer_id = $this->employer_id;
		if(is_array($employer_id)) $employer_id = implode(',',$employer_id);
		return $this->db->selectFromField('group_id', 'SELECT `group_id` FROM `employer_groups` WHERE `employer_id` IN ('.$employer_id.')');
	}#end function




	/*
	 * Возвращает список групп, в которые включен сотрудник
	 */
	public function getEmployerGroups(){
		if(!$this->is_init) return false;
		if(is_array($this->myself['groups'])) return $this->myself['groups'];
		$this->myself['groups'] = $this->dbEmployerGroups($this->employer_id);
		return $this->myself['groups'];
	}#end function




	/*
	 * Возвращает cписок должностей, занимаемых сотрудником
	 */
	public function dbEmployerPosts($employer_id=0, $fullinfo=false, $fields=null){
		if(!$this->is_init) return false;
		if(empty($employer_id)) $employer_id = $this->employer_id;
		if(is_array($employer_id)) $employer_id = implode(',',$employer_id);

		if(!$fullinfo){
			$this->db->prepare('SELECT `post_uid` FROM `employer_posts` WHERE `employer_id` IN(?) AND `post_from` <= ? AND `post_to` >= ?');
			$this->db->bindSql($employer_id);
			$this->db->bind($this->dbtoday);
			$this->db->bind($this->dbtoday);
			return $this->db->selectFromField('post_uid');
		}

		$this->db->prepare('
			SELECT 
				EP.`employer_id` AS `employer_id`,
				EMP.`search_name` AS `employer_name`,
				EP.`post_uid` AS `post_uid`,
				CP.`boss_uid` AS `boss_post_uid`,
				C.`company_id` AS `company_id`,
				C.`full_name` AS `company_name`,
				P.`post_id` AS `post_id`,
				P.`full_name` AS `post_name`,
				DATE_FORMAT(EP.`post_from`,"%d.%m.%Y") as `post_from`,
				DATE_FORMAT(EP.`post_to`,"%d.%m.%Y") as `post_to`
			FROM `employer_posts` as EP
				INNER JOIN `employers` as EMP ON EMP.`employer_id` = EP.`employer_id`
				INNER JOIN `company_posts` as CP ON EP.`post_uid` = CP.`post_uid`
				INNER JOIN `companies` as C ON C.`company_id` = CP.`company_id`
				INNER JOIN `posts` as P ON P.`post_id` = CP.`post_id`
			WHERE EP.`employer_id` IN(?) AND EP.`post_from` <= ? AND EP.`post_to` >= ?
		');
		$this->db->bindSql($employer_id);
		$this->db->bind($this->dbtoday);
		$this->db->bind($this->dbtoday);

		$result = $this->db->select();
		if(!is_array($fields)) return $result;
		$return = array();
		foreach($result as $record){
			$return[] = $this->getRecordWithCustomFields($record, $fields);
		}
		return $return;
	}#end function




	/*
	 * Возвращает cписок должностей, занимаемых сотрудником
	 */
	public function getEmployerPosts(){
		if(!$this->is_init) return false;
		if(is_array($this->myself['posts'])) return $this->myself['posts'];
		$this->myself['posts'] = $this->dbEmployerPosts($this->employer_id);
		return $this->myself['posts'];
	}#end function




	/*
	 * Возвращает cписок должностей, занимаемых сотрудником, с полной детализацией
	 */
	public function dbEmployerPostsDetails($employer_id=0, $company_id=0, $contain_search=false){
		if(!$this->is_init) return false;
		if(empty($employer_id)) $employer_id = $this->employer_id;
		$search_like = false;
		if(is_array($employer_id)){
			$employer_id = implode(',',$employer_id);
		}else
		if(!is_numeric($employer_id)){
			$search_like = true;
			$employer_id = "'".($contain_search?'%':'')."".$this->db->getQuotedValue($employer_id,false)."%'";
		}
		$company_id = intval($company_id);
		$this->db->prepare('
			SELECT 
				EP.`employer_id` AS `employer_id`,
				EMP.`search_name` AS `employer_name`,
				EP.`post_uid` AS `post_uid`,
				DATE_FORMAT(EP.`post_from`,"%d.%m.%Y") as `post_from`,
				DATE_FORMAT(EP.`post_to`,"%d.%m.%Y") as `post_to`,
				C.`company_id` AS `company_id`,
				C.`full_name` AS `company_name`,
				P.`post_id` AS `post_id`,
				P.`full_name` AS `post_name`,
				CP.`boss_uid` AS `boss_post_uid`,
				CPBOSS.`post_id` AS `boss_post_id`,
				PBOSS.`full_name` AS `boss_post_name`
			FROM `employers` as EMP
				INNER JOIN `employer_posts` as EP ON EP.`employer_id`=EMP.`employer_id` AND EP.`post_from` <= ? AND EP.`post_to` >= ?
				INNER JOIN `company_posts` as CP ON EP.`post_uid` = CP.`post_uid` '.($company_id > 0 ? ' AND CP.`company_id`='.$company_id : '').'
				INNER JOIN `companies` as C ON C.`company_id` = CP.`company_id`
				INNER JOIN `posts` as P ON P.`post_id` = CP.`post_id`
				LEFT JOIN `company_posts` as CPBOSS ON CPBOSS.`post_uid` = CP.`boss_uid`
				LEFT JOIN `posts` as PBOSS ON PBOSS.`post_id` = CPBOSS.`post_id`
			WHERE '.($search_like?'EMP.`search_name` LIKE ?':'EMP.`employer_id` IN (?)').'
		');
		$this->db->bind($this->dbtoday);
		$this->db->bind($this->dbtoday);
		$this->db->bindSql($employer_id);
		if(($results = $this->db->select()) === false)return false;

		//Просмотр должностей, поиск руководителей
		for($i=0; $i<count($results);$i++){
			$results[$i]['bosses'] = array();
			if($results[$i]['boss_post_uid'] == 0) continue;
			if( ($bosses = $this->dbEmployersOnPost($results[$i]['boss_post_uid'])) !== false) $results[$i]['bosses'] = $bosses;
		}//Просмотр должностей, поиск руководителей

		return $results;
	}#end function






	/*
	 * Возвращает список сотрудников, занимающих указанную должность
	 */
	public function dbEmployersOnPost($post_uid=0){
		if(!$this->is_init || empty($post_uid)) return false;
		if(is_array($post_uid)) $post_uid = implode(',',$post_uid);
		$this->db->prepare('
			SELECT 
				EP.`employer_id` AS `employer_id`,
				DATE_FORMAT(EP.`post_from`,"%d.%m.%Y") as `post_from`,
				DATE_FORMAT(EP.`post_to`,"%d.%m.%Y") as `post_to`,
				EMP.`search_name` as `employer_name`
			FROM `employer_posts` as EP
				INNER JOIN `employers` as EMP ON EMP.`employer_id`= EP.`employer_id` AND EMP.`status` > 0
			WHERE EP.`post_uid` IN (?) AND EP.`post_from`<=? AND EP.`post_to`>=?
		');
		$this->db->bindSql($post_uid);
		$this->db->bind($this->dbtoday);
		$this->db->bind($this->dbtoday);
		return $this->db->select();
	}#end function






	/*
	 * Возвращает cписок сотрудников, занимающих определенную должность, с полной детализацией
	 */
	public function dbSearchPostEmployers($post_name='', $company_id=0, $contain_search=false){
		if(!$this->is_init) return false;
		$search_like = true;
		$post_name = "'".($contain_search?'%':'')."".$this->db->getQuotedValue($post_name,false)."%'";
		$company_id = intval($company_id);
		$this->db->prepare('
			SELECT 
				EP.`employer_id` AS `employer_id`,
				EMP.`search_name` AS `employer_name`,
				EP.`post_uid` AS `post_uid`,
				DATE_FORMAT(EP.`post_from`,"%d.%m.%Y") as `post_from`,
				DATE_FORMAT(EP.`post_to`,"%d.%m.%Y") as `post_to`,
				C.`company_id` AS `company_id`,
				C.`full_name` AS `company_name`,
				P.`post_id` AS `post_id`,
				P.`full_name` AS `post_name`,
				CP.`boss_uid` AS `boss_post_uid`,
				CPBOSS.`post_id` AS `boss_post_id`,
				PBOSS.`full_name` AS `boss_post_name`
			FROM `posts` as P
				INNER JOIN `company_posts` as CP ON CP.`post_id` = P.`post_id` '.($company_id > 0 ? ' AND CP.`company_id`='.$company_id : '').'
				INNER JOIN `employer_posts` as EP ON EP.`post_uid`=CP.`post_uid` AND EP.`post_from` <= ? AND EP.`post_to` >= ?
				INNER JOIN `employers` as EMP ON EMP.`employer_id`=EP.`employer_id`
				INNER JOIN `companies` as C ON C.`company_id` = CP.`company_id`
				LEFT JOIN `company_posts` as CPBOSS ON CPBOSS.`post_uid` = CP.`boss_uid`
				LEFT JOIN `posts` as PBOSS ON PBOSS.`post_id` = CPBOSS.`post_id`
			WHERE P.`full_name` LIKE ?
		');
		$this->db->bind($this->dbtoday);
		$this->db->bind($this->dbtoday);
		$this->db->bindSql($post_name);
		if(($results = $this->db->select()) === false)return false;

		//Просмотр должностей, поиск руководителей
		for($i=0; $i<count($results);$i++){
			$results[$i]['bosses'] = array();
			if($results[$i]['boss_post_uid'] == 0) continue;
			if( ($bosses = $this->dbEmployersOnPost($results[$i]['boss_post_uid'])) !== false) $results[$i]['bosses'] = $bosses;
		}//Просмотр должностей, поиск руководителей

		return $results;
	}#end function





	/*
	 * Возвращает список организаций, в которых сотрудник является руководителем
	 */
	public function dbEmployerCompanyHead($employer_id=0, $postlist=null){
		if(!$this->is_init) return false;
		if(empty($employer_id)) $employer_id = $this->employer_id;
		if(!is_array($postlist)) $postlist = $this->dbEmployerPosts($employer_id);
		if(empty($postlist)) return array();
		$postlist = implode(',',$postlist);
		return $this->db->selectFromField('company_id','SELECT DISTINCT `company_id` FROM `company_posts` WHERE `post_uid` IN ('.$postlist.') AND `boss_uid`=0 AND `boss_id`=0');
	}#end function



	/*
	 * Возвращает список организаций, в которых сотрудник является руководителем
	 */
	public function getEmployerCompanyHead(){
		if(!$this->is_init) return false;
		if(is_array($this->myself['companyhead'])) return $this->myself['companyhead'];
		$this->myself['companyhead'] = $this->dbEmployerCompanyHead($this->employer_id, (!is_array($this->myself['posts']) ? $this->getEmployerPosts() : $this->myself['posts']));
		return $this->myself['companyhead'];
	}#end function



	/*
	 * Возвращает список информационных ресурсов, для которых сотрудник назначен их владельцем
	 */
	public function dbEmployerIROwner($employer_id=0, $postlist=null){
		if(!$this->is_init) return false;
		if(empty($employer_id)) $employer_id = $this->employer_id;
		if(!is_array($postlist)) $postlist = $this->dbEmployerPosts($employer_id);
		if(empty($postlist)) return array();
		$postlist = implode(',',$postlist);
		return $this->db->selectFromField('iresource_id','SELECT DISTINCT `iresource_id` FROM `iresources` WHERE `post_uid` IN ('.$postlist.')');
	}#end function



	/*
	 * Возвращает список информационных ресурсов, для которых сотрудник назначен их владельцем
	 */
	public function getEmployerIROwner(){
		if(!$this->is_init) return false;
		if(is_array($this->myself['irowner'])) return $this->myself['irowner'];
		$this->myself['irowner'] = $this->dbEmployerIROwner($this->employer_id, (!is_array($this->myself['posts']) ? $this->getEmployerPosts() : $this->myself['posts']));
		return $this->myself['irowner'];
	}#end function




	/*
	 * Возвращает заместителей сотрудника
	 */
	public function dbEmployerAssistants($employer_id=0, $fullinfo=false, $single=false, $fields=null, $only_actual=true){
		if(!$this->is_init) return false;
		if(empty($employer_id)) $employer_id = $this->employer_id;
		if(is_array($employer_id)) $employer_id = implode(',',$employer_id);

		if(!$fullinfo){
			$this->db->prepare('
				SELECT ASSIST.`assistant_id` as `assistant_id`
				FROM `assistants` as ASSIST
				INNER JOIN `employers` as EMP ON EMP.`employer_id`=ASSIST.`assistant_id` AND EMP.`status` > 0
				WHERE ASSIST.`employer_id` IN (?) ?
			');
			$this->db->bindSql($employer_id);
			$this->db->bindSql(($only_actual ? ' AND ASSIST.`from_date` <= "'.$this->dbtoday.'" AND ASSIST.`to_date` >= "'.$this->dbtoday.'" ' : ''));
			return $this->db->selectFromField('assistant_id');
		}

		$this->db->prepare('
			SELECT 
				EMP.`employer_id` as `employer_id`,
				EMP.`search_name` as `employer_name`,
				DATE_FORMAT(EMP.`birth_date`,"%d.%m.%Y") as `birth_date`,
				EMP.`phone` as `phone`,
				EMP.`email` as `email`,
				DATE_FORMAT(ASSIST.`from_date`,"%d.%m.%Y") as `from_date`,
				DATE_FORMAT(ASSIST.`to_date`,"%d.%m.%Y") as `to_date`
			FROM `assistants` as ASSIST
			INNER JOIN `employers` as EMP ON EMP.`employer_id`=ASSIST.`assistant_id` AND EMP.`status` > 0
			WHERE ASSIST.`employer_id` IN (?) ? 
			ORDER BY EMP.`search_name` ASC 
			?
		');
		$this->db->bindSql($employer_id);
		$this->db->bindSql(($only_actual ? ' AND ASSIST.`from_date` <= "'.$this->dbtoday.'" AND ASSIST.`to_date` >= "'.$this->dbtoday.'" ' : ''));
		$this->db->bindSql( (empty($single) ? '':' LIMIT 1') );

		$result = (empty($single) ? $this->db->select() : $this->db->selectRecord());
		if(!is_array($fields)) return $result;
		if(!empty($single)) return $this->getRecordWithCustomFields($result, $fields);
		$return = array();
		foreach($result as $record){
			$return[] = $this->getRecordWithCustomFields($record, $fields);
		}
		return $return;
	}#end function




	/*
	 * Возвращает заместителей сотрудника
	 */
	public function getEmployerAssistants(){
		if(!$this->is_init) return false;
		if(is_array($this->myself['assistants'])) return $this->myself['assistants'];
		$this->myself['assistants'] = $this->dbEmployerAssistants($this->employer_id);
		return $this->myself['assistants'];
	}#end function




	/*
	 * Возвращает должностей, которые подчиняются данному сотруднику
	 */
	public function dbEmployerWorkers($employer_id=0, $postlist=null){
		if(!$this->is_init) return false;
		if(empty($employer_id)) $employer_id = $this->employer_id;
		if(!is_array($postlist)) $postlist = $this->dbEmployerPosts($employer_id);
		if(empty($postlist)) return array();
		$this->db->prepare('SELECT `post_uid` FROM `company_posts` WHERE `boss_uid` IN (?)');
		return $this->db->selectFromField('post_uid', 'SELECT `post_uid` FROM `company_posts` WHERE `boss_uid` IN ('.implode(',',$postlist).')');
	}#end function




	/*
	 * Возвращает должностей, которые подчиняются данному сотруднику
	 */
	public function getEmployerWorkers(){
		if(!$this->is_init) return false;
		if(is_array($this->myself['workers'])) return $this->myself['workers'];
		$this->myself['workers'] = $this->dbEmployerWorkers($this->employer_id, (!is_array($this->myself['posts']) ? $this->getEmployerPosts() : $this->myself['posts']));
		return $this->myself['workers'];
	}#end function




	/*
	 * Возвращает список делегировавших сотруднику свои полномочия
	 */
	public function dbEmployerDelegates($employer_id=0, $fullinfo=false, $single=false, $fields=null, $only_actual=true){
		if(!$this->is_init) return false;
		if(empty($employer_id)) $employer_id = $this->employer_id;
		if(is_array($employer_id)) $employer_id = implode(',',$employer_id);

		if(!$fullinfo){
			$this->db->prepare('
				SELECT ASSIST.`employer_id` as `employer_id`
				FROM `assistants` as ASSIST
				INNER JOIN `employers` as EMP ON EMP.`employer_id`=ASSIST.`employer_id` AND EMP.`status` > 0
				WHERE ASSIST.`assistant_id` IN(?) ?
			');
			$this->db->bindSql($employer_id);
			$this->db->bindSql(($only_actual ? ' AND ASSIST.`from_date` <= "'.$this->dbtoday.'" AND ASSIST.`to_date` >= "'.$this->dbtoday.'" ' : ''));
			return $this->db->selectFromField('employer_id');
		}

		$this->db->prepare('
			SELECT
				EMP.`employer_id` as `employer_id`,
				EMP.`search_name` as `employer_name`,
				DATE_FORMAT(EMP.`birth_date`,"%d.%m.%Y") as `birth_date`,
				EMP.`phone` as `phone`,
				EMP.`email` as `email`,
				DATE_FORMAT(ASSIST.`from_date`,"%d.%m.%Y") as `from_date`,
				DATE_FORMAT(ASSIST.`to_date`,"%d.%m.%Y") as `to_date`
			FROM `assistants` as ASSIST
			INNER JOIN `employers` as EMP ON EMP.`employer_id`=ASSIST.`employer_id` AND EMP.`status` > 0
			WHERE ASSIST.`assistant_id` IN(?) ?
			ORDER BY EMP.`search_name` ASC
			?
		');
		$this->db->bindSql($employer_id);
		$this->db->bindSql(($only_actual ? ' AND ASSIST.`from_date` <= "'.$this->dbtoday.'" AND ASSIST.`to_date` >= "'.$this->dbtoday.'" ' : ''));
		$this->db->bindSql( (empty($single) ? '':' LIMIT 1') );

		$result = (empty($single) ? $this->db->select() : $this->db->selectRecord());
		if(!is_array($fields)) return $result;
		if(!empty($single)) return $this->getRecordWithCustomFields($result, $fields);
		$return = array();
		foreach($result as $record){
			$return[] = $this->getRecordWithCustomFields($record, $fields);
		}
		return $return;
	}#end function




	/*
	 * Возвращает список делегировавших сотруднику свои полномочия
	 */
	public function getEmployerDelegates(){
		if(!$this->is_init) return false;
		if(is_array($this->myself['delegates'])) return $this->myself['delegates'];
		$this->myself['delegates'] = $this->dbEmployerDelegates($this->employer_id);
		return $this->myself['delegates'];
	}#end function




	/*
	 * Возвращает массив сведений по делегировавшим свои полномочия:
	 * - руководителями каких организаций являются
	 * - владельцами каких ИР являются
	 * - кто им подчинятеся
	 * Возвращаются общие объединенные списки по всем делегировавшим
	 */
	public function dbDelegatesInfo($employer_id=0, $delegateslist=null){
		if(!$this->is_init) return false;
		if(empty($employer_id)) $employer_id = $this->employer_id;
		if(!is_array($delegateslist)) $delegateslist = $this->dbEmployerDelegates($employer_id);
		if(is_array($employer_id)) $employer_id = implode(',',$employer_id);
		if(empty($delegateslist)) return array(
			'posts'			=> array(),	#Список должностей, занимаемых сотрудником
			'irowner'		=> array(),	#Список информационных ресурсов, для которых сотрудники являются владельцами
			'companyhead'	=> array(),	#Список организаций, в которых сотрудники является руководителями
			'workers'		=> array(),	#Список должностей, которые подчиняются данным сотрудникам
			'groups'		=> array()	#Список групп, в которые включены сотрудники
		);

		#Список должностей, занимаемых сотрудником
		$posts = $this->dbEmployerPosts($delegateslist);

		#Список информационных ресурсов, для которых сотрудники являются владельцами
		$irowner = $this->dbEmployerIROwner($delegateslist, $posts);

		#Список организаций, в которых сотрудники является руководителями
		$companyhead = $this->dbEmployerCompanyHead($delegateslist, $posts);

		#Список должностей, которые подчиняются данным сотрудникам
		$workers = $this->dbEmployerWorkers($delegateslist, $posts);

		#Список групп, в которые включены сотрудники
		$groups = $this->dbEmployerGroups($delegateslist);

		return array(
			'posts'			=> $posts,			#Список должностей, занимаемых сотрудником
			'irowner'		=> $irowner,		#Список информационных ресурсов, для которых сотрудники являются владельцами
			'companyhead'	=> $companyhead,	#Список организаций, в которых сотрудники является руководителями
			'workers'		=> $workers,		#Список должностей, которые подчиняются данным сотрудникам
			'groups'		=> $groups			#Список групп, в которые включены сотрудники
		);
	}#end function




	/*
	 * Возвращает список информацию по делегировавшим свои полномочия
	 */
	public function getDelegatesInfo(){
		if(!$this->is_init) return false;
		if(is_array($this->delegates)) return $this->delegates;
		$this->delegates = $this->dbDelegatesInfo($this->employer_id, (!is_array($this->myself['delegates']) ? $this->getEmployerDelegates() : $this->myself['delegates']));
		return $this->delegates;
	}#end function




	/*
	 * Получает все списки 
	 */
	public function loadEmployerFullInfo(){
		if(!$this->is_init) return false;

		$this->getEmployerGroups();
		$this->getEmployerPosts();
		$this->getEmployerCompanyHead();
		$this->getEmployerIROwner();
		$this->getEmployerWorkers();
		$this->getEmployerAssistants();
		$this->getEmployerDelegates();
		$this->getDelegatesInfo();

		return true;
	}#end function





	/*
	 * Возвращает cписок заявок сотрудника
	 */
	public function dbEmployerRequests($employer_id=0, $route_status=1, $fields=null){
		if(!$this->is_init) return false;
		$route_status = intval($route_status);
		if(empty($employer_id)) $employer_id = $this->employer_id;
		if(is_array($employer_id)) $employer_id = implode(',',array_map('intval',$employer_id));
		$is_history = ($route_status!=1 && $route_status !=2);
		////$filter_sql = (is_array($filter) ? $this->db->buildSqlConditions($filter,'RI') : '');

		$this->db->prepare('
			SELECT 
				REQ.`request_id` AS `request_id`,
				REQ.`request_type` AS `request_type`,
				REQ.`employer_id` AS `employer_id`,
				EMP.`search_name` AS `employer_name`,
				REQ.`curator_id` AS `curator_id`,
				IF(REQ.`curator_id`>0, CUR.`search_name`, "Administrator") AS `curator_name`,
				RI.`iresource_id` AS `iresource_id`,
				IR.`full_name` AS `iresource_name`,
				RI.`route_status` AS `route_status`,
				RI.`route_status_desc` AS `route_status_desc`,
				DATE_FORMAT(REQ.`timestamp`,"%d.%m.%Y") as `create_date`,
				RI.`current_step` as `rstep_id`,
				ROUTS.`step_type` as `step_type`,
				ROUTS.`gatekeeper_type` as `gatekeeper_type`,
				ROUTS.`gatekeeper_id` as `gatekeeper_id`,
				ROUTS.`gatekeeper_role` as `gatekeeper_role`
			FROM `requests` as REQ
				INNER JOIN `'.($is_history?'request_iresources_hist':'request_iresources').'` as RI ON RI.`request_id` = REQ.`request_id` AND RI.`route_status`='.$route_status.'
				INNER JOIN `iresources` as IR ON IR.`iresource_id` = RI.`iresource_id`
				LEFT JOIN `'.($is_history?'request_steps_hist':'request_steps').'` as RS ON RS.`rstep_id` = RI.`current_step`
				LEFT JOIN `route_steps` as ROUTS ON ROUTS.`route_id` = RS.`route_id` AND ROUTS.`step_uid` = RS.`step_uid`
				INNER JOIN `employers` as EMP ON EMP.`employer_id`=REQ.`employer_id`
				LEFT JOIN `employers` as CUR ON CUR.`employer_id`=REQ.`curator_id`
			WHERE REQ.`employer_id` IN (?)
			ORDER BY REQ.`request_id` ASC
		');
		$this->db->bindSql($employer_id);

		//print_r($this->db->parseTemplate());

		$result = $this->db->select();

		if(!is_array($fields)) return $result;
		$return = array();
		foreach($result as $record){
			$return[] = $this->getRecordWithCustomFields($record, $fields);
		}
		return $return;
	}#end function






	/*
	 * Возвращает журнал входа сотрудника в админку
	 */
	public function dbEmployerAccessLog($employer_id=0, $filter=null, $fields=null, $limit=100){
		if(!$this->is_init) return false;
		if(empty($employer_id)) $employer_id = $this->employer_id;
		if(is_array($employer_id)) $employer_id = implode(',',$employer_id);
		$limit = intval($limit);
		$filter_sql = (is_array($filter) ? $this->db->buildSqlConditions($filter,'EAUTH') : '');

		$this->db->prepare('
			SELECT EAUTH.*, DATE_FORMAT(EAUTH.`login_time`,"%d.%m.%Y %H:%i:%s") as `login_time`
			FROM `employer_authlog` as EAUTH WHERE EAUTH.`employer_id` IN (?) '.(empty($filter_sql) ? '' : ' AND '.$filter_sql).'
			ORDER BY EAUTH.`login_time` DESC
			'.($limit > 0 ? 'LIMIT '.$limit:'').'
		');
		$this->db->bindSql($employer_id);

		$result = $this->db->select();

		if(!is_array($fields)) return $result;
		$return = array();
		foreach($result as $record){
			$return[] = $this->getRecordWithCustomFields($record, $fields);
		}

		return $return;
	}#end function




	/*
	 * Отмечат заявку как просмотренную данным сотрудником
	 */
	public function dbEmployerRequestSetAsWatched($employer_id=0, $request_id=0, $iresource_id=0){

		if(!$this->is_init) return false;
		if(empty($employer_id)) $employer_id = $this->employer_id;
		if(is_array($employer_id)) $employer_id = implode(',',$employer_id);

		$this->db->prepare('UPDATE `request_watch` SET `is_watched`=1 WHERE `employer_id` IN (?) AND `request_id`=? AND `iresource_id` IN(0,?)');
		$this->db->bindSql($employer_id);
		$this->db->bind($request_id);
		$this->db->bind($iresource_id);
		return $this->db->update();

	}#end function




	/*
	 * Возвращает количество непрочитанных сотрудником заявок
	 */
	public function dbEmployerWatchedRequestsCount($employer_id=0){

		if(!$this->is_init) return false;
		if(empty($employer_id)) $employer_id = $this->employer_id;
		if(is_array($employer_id)) $employer_id = implode(',',$employer_id);

		$this->db->prepare('SELECT count(*) FROM `request_watch` WHERE `employer_id` IN (?) AND `is_watched`=0');
		$this->db->bindSql($employer_id);
		return $this->db->result();

	}#end function



	/*
	 * Проверка, является ли указанный сотрудник заявителем или куратором по заявке
	 */
	public function dbCanWatchRequest($employer_id=0, $request_id=0, $iresource_id=0){

		if(!$this->is_init) return false;
		if(empty($request_id)||empty($iresource_id)) return false;
		if(empty($employer_id)) $employer_id = $this->employer_id;

		$request_id = intval($request_id);
		$this->db->prepare('SELECT sum(`is_owner`+`is_curator`+`is_gatekeeper`+`is_performer`+`is_watcher`) FROM `request_watch` WHERE `request_id`=? AND `iresource_id`IN(0,?)  AND `employer_id`=? LIMIT 1');
		$this->db->bind($request_id);
		$this->db->bind($iresource_id);
		$this->db->bind($employer_id);

		return ($this->db->result() > 0);
	}#end function



	/*
	 * Возвращает cписок заявок, которые может просматривать сотрудник
	 */
	public function dbEmployerWatchedRequests($employer_id=0, $watcher_filter=null, $request_filter=null, $employer_filter=null, $fields=null, $limit=0){

		if(!$this->is_init) return false;
		if(empty($employer_id)) $employer_id = $this->employer_id;
		if(is_array($employer_id)) $employer_id = implode(',',$employer_id);
		$watcher_filter = (is_array($watcher_filter)&&!empty($watcher_filter) ? $this->db->buildSqlConditions($watcher_filter,'RW') : '');
		$request_filter = (is_array($request_filter)&&!empty($request_filter) ? $this->db->buildSqlConditions($request_filter,'REQ') : '');
		$employer_filter = (is_array($employer_filter)&&!empty($employer_filter) ? $this->db->buildSqlConditions($employer_filter,'EMP') : '');



		$this->db->prepare('
			SELECT RST.* FROM(
				(SELECT 
					RW.`request_id` as `request_id`,
					REQ.`request_type` as `request_type`,
					RI.`iresource_id` as `iresource_id`,
					IR.`full_name` as `iresource_name`,
					RW.`employer_id` as `employer_id`,
					EMP.`search_name` as `employer_name`,
					C.`full_name` as `company_name`,
					P.`full_name` as `post_name`,
					RW.`is_watched` as `is_watched`,
					RW.`is_owner` as `is_owner`,
					RW.`is_curator` as `is_curator`,
					RW.`is_gatekeeper` as `is_gatekeeper`,
					RW.`is_performer` as `is_performer`,
					RW.`is_watcher` as `is_watcher`,
					RI.`route_status` AS `route_status`,
					RI.`route_status_desc` AS `route_status_desc`,
					DATE_FORMAT(REQ.`timestamp`,"%d.%m.%Y") as `create_date`
				FROM `request_watch` as RW
					INNER JOIN `requests` as REQ ON REQ.`request_id`=RW.`request_id` '.(empty($request_filter) ? '' : ' AND '.$request_filter).'
					INNER JOIN `employers` as EMP ON EMP.`employer_id`=REQ.`employer_id` '.(empty($employer_filter) ? '' : ' AND '.$employer_filter).' AND EMP.`status`>0
					INNER JOIN `request_iresources` as RI ON RI.`request_id` = REQ.`request_id` AND (RI.`iresource_id` = RW.`iresource_id` OR RW.`iresource_id`=0)
					INNER JOIN `companies` as C ON C.`company_id`=REQ.`company_id`
					INNER JOIN `company_posts` as CP ON CP.`post_uid`=REQ.`post_uid`
					INNER JOIN `posts` as P ON P.`post_id`=CP.`post_id`
					LEFT JOIN `iresources` as IR ON IR.`iresource_id` = RI.`iresource_id`
				WHERE RW.`employer_id` IN (?) '.(empty($watcher_filter) ? '' : ' AND '.$watcher_filter).'
				LIMIT 100)
				UNION ALL
				(SELECT 
					RW.`request_id` as `request_id`,
					REQ.`request_type` as `request_type`,
					RI.`iresource_id` as `iresource_id`,
					IR.`full_name` as `iresource_name`,
					RW.`employer_id` as `employer_id`,
					EMP.`search_name` as `employer_name`,
					C.`full_name` as `company_name`,
					P.`full_name` as `post_name`,
					RW.`is_watched` as `is_watched`,
					RW.`is_owner` as `is_owner`,
					RW.`is_curator` as `is_curator`,
					RW.`is_gatekeeper` as `is_gatekeeper`,
					RW.`is_performer` as `is_performer`,
					RW.`is_watcher` as `is_watcher`,
					RI.`route_status` AS `route_status`,
					RI.`route_status_desc` AS `route_status_desc`,
					DATE_FORMAT(REQ.`timestamp`,"%d.%m.%Y") as `create_date`
				FROM `request_watch` as RW
					INNER JOIN `requests` as REQ ON REQ.`request_id`=RW.`request_id` '.(empty($request_filter) ? '' : ' AND '.$request_filter).'
					INNER JOIN `employers` as EMP ON EMP.`employer_id`=REQ.`employer_id` '.(empty($employer_filter) ? '' : ' AND '.$employer_filter).' AND EMP.`status`>0
					INNER JOIN `request_iresources_hist` as RI ON RI.`request_id` = REQ.`request_id` AND (RI.`iresource_id` = RW.`iresource_id` OR RW.`iresource_id`=0)
					INNER JOIN `companies` as C ON C.`company_id`=REQ.`company_id`
					INNER JOIN `company_posts` as CP ON CP.`post_uid`=REQ.`post_uid`
					INNER JOIN `posts` as P ON P.`post_id`=CP.`post_id`
					LEFT JOIN `iresources` as IR ON IR.`iresource_id` = RI.`iresource_id`
				WHERE RW.`employer_id` IN (?) '.(empty($watcher_filter) ? '' : ' AND '.$watcher_filter).'
				LIMIT 100)
			) as RST
			ORDER BY RST.`request_id` DESC
			'.($limit > 0 ? 'LIMIT '.$limit:' LIMIT 100').'
		');
		$this->db->bindSql($employer_id);
		$this->db->bindSql($employer_id);
		//print_r($this->db->select('explain '.$this->db->parseTemplate()));

		$result = ($limit == 1 ? $this->db->selectRecord() : $this->db->select());

		if(!is_array($fields)) return $result;
		if($limit == 1) return $this->getRecordWithCustomFields($result, $fields);

		$return = array();
		foreach($result as $record){
			$return[] = $this->getRecordWithCustomFields($record, $fields);
		}

		return $return;
	}#end function





















	/*==============================================================================================
	Работа с заявками: получение списков, проверки и т.д.
	==============================================================================================*/


	/*
	 * Формирует объединенный список гейткиперов (сотрудник + делегировавшие полномочия)
	 */
	private function getGatekeeprUnionLists($split=false){
		if(is_array($this->gatekeeperUnionLists) && !$split) return $this->gatekeeperUnionLists;
		if(!$this->loadEmployerFullInfo()) return false;
		if(empty($this->myself) || empty($this->delegates)) return false;

		if(!$split){

			#Формирование общих списков по типам гейткиперов для поиска заявок
			$this->gatekeeperUnionLists = array(

				#Список идентификаторов сотрудников
				'gatekeepers' => array_unique(array_merge(array($this->employer_id), $this->myself['delegates'])),

				#Список должностей
				'posts' => array_unique(array_merge($this->myself['posts'], $this->delegates['posts'])),

				#Список руководителей организации
				'companyheads' => array_unique(array_merge($this->myself['companyhead'], $this->delegates['companyhead'])),

				#Список владельцев ресурса
				'irowners' => array_unique(array_merge($this->myself['irowner'], $this->delegates['irowner'])),

				#Список групп сотрудника
				'groups' => $this->myself['groups'],

				#Список подчиненных
				'workers' => array_unique(array_merge($this->myself['workers'], $this->delegates['workers']))

			);

			return $this->gatekeeperUnionLists;
		}

		return array(
			#Как гейткипер
			'gatekeeper' => array(
				'gatekeepers'	=> array($this->employer_id), #Список идентификаторов сотрудников
				'posts'			=> $this->myself['posts'], #Список должностей
				'companyheads'	=> $this->myself['companyhead'], #Список руководителей организации
				'irowners'		=> $this->myself['irowner'], #Список владельцев ресурса
				'groups'		=> $this->myself['groups'], #Список групп сотрудника
				'workers'		=> $this->myself['workers'] #Список подчиненных
			),
			#Как ассистент гейткипера
			'assistant' => array(
				'gatekeepers'	=> array_diff(array_merge(array($this->employer_id), $this->myself['delegates']), array($this->employer_id)), #Список идентификаторов сотрудников
				'posts'			=> array_diff(array_merge($this->myself['posts'], $this->delegates['posts']), $this->myself['posts']), #Список должностей
				'companyheads'	=> array_diff(array_merge($this->myself['companyhead'], $this->delegates['companyhead']), $this->myself['companyhead']), #Список руководителей организации
				'irowners'		=> array_diff(array_merge($this->myself['irowner'], $this->delegates['irowner']), $this->myself['irowner']), #Список владельцев ресурса
				'groups'		=> array(), #Список групп сотрудника
				'workers'		=> array_diff(array_merge($this->myself['workers'], $this->delegates['workers']), $this->myself['workers']) #Список подчиненных
			)
			/*
			'assistant' => array(
				'gatekeepers'	=> $this->myself['delegates'], #Список идентификаторов сотрудников
				'posts'			=> array_unique($this->delegates['posts']), #Список должностей
				'companyheads'	=> array_unique($this->delegates['companyhead']), #Список руководителей организации
				'irowners'		=> array_unique($this->delegates['irowner']), #Список владельцев ресурса
				'groups'		=> array_unique($this->delegates['groups']), #Список групп сотрудника
				'workers'		=> array_unique($this->delegates['workers']) #Список подчиненных
			)
			*/
		);

	}#end function




	/*
	 * Выбирает из массива $record часть полей, указанных в массиве $fields и возвращает их
	 */
	private function getRecordWithCustomFields($record, $fields){
		if(!is_array($record)) return false;
		$result = array();
		foreach($fields as $field){
			$result[$field] = (!isset($record[$field]) ? null : $record[$field]);
		}
		return $result;
	}#end function




	/*
	 * Формирует запрос к базе данных для работы со списком активных запросов, 
	 * принимает параметры:
	 * $data = array(
	 * 	'roles' => array(1,2,3,4)		#Массив ролей гейткипера, учавствующих в формировании списка заявок
	 * 	'fullinfo' => [true,false]		#Признак, указывающий что надо вернуть также дополнительную информацию по заявке (ФИО, название должности, организации и т.п.)
	 * 	'request_id' => array([ID])		#ID заявки
	 * 	'iresource_id' => array([ID])	#ID информационного ресурса
	 * 	'single' => [true,false]		#Признак, указывающий о необходимости вернуть только одну запись (single=false -> db->select(), single=true -> db->selectRecord()),
	 * 	'fields' => [array() or NULL]	#Если задан как NULL - возвращает все записи, если задан как массив - возвращает записи только с полями, указанными в массиве
	 *  'count' => [true,false]		#Признак, указывающий о необходимости вернуть только количество найденных записей, при TRUE параметр 'single' не учитывается
	 * )
	 */
	public function getActiveRequests($params=array()){

		$params = array_merge(array(
			'roles' => array(1,2),
			'fullinfo'=> true,
			'request_id' => null,
			'iresource_id' => null,
			'single' => false,
			'fields' => null,
			'count' => false
		),$params);

		#Получение объединенных списков
		if(($lists = $this->getGatekeeprUnionLists(true)) === false) return false;

		$sql = array(
			'gatekeeper' => '',
			'assistant' => ''
		);
		$sql_part = array();

		#Подготовка SQL
		foreach($lists as $ltype=>$larray){

			$found=0;
			$sql[$ltype] = '';
			$sql_part[$ltype] = '';

			#Формирование SQL запроса - WHERE
			foreach($larray as $key=>$value){
				if(empty($value)||!is_array($value)) continue;
				switch($key){

					#1 - конкретный пользователь (user_id)
					case 'gatekeepers':
						$sql[$ltype].=($found > 0 ? ' OR ' : '') . '(ROUTS.`gatekeeper_type`=1 AND ROUTS.`gatekeeper_id` IN ('.implode(',', $value).'))';
					break;

					#2 - руководитель сотрудника (boss_id)
					case 'workers':
						$sql[$ltype].=($found > 0 ? ' OR ' : '') . '(ROUTS.`gatekeeper_type`=2 AND REQ.`post_uid` IN ('.implode(',', $value).'))';
					break;

					#3 - руководитель организации (company_id)
					case 'companyheads':
						$sql[$ltype].=($found > 0 ? ' OR ' : '') . '(ROUTS.`gatekeeper_type`=3 AND REQ.`company_id` IN ('.implode(',', $value).'))';
					break;

					#4 - владелец ресурса (resource_id)
					case 'irowners':
						$sql[$ltype].=($found > 0 ? ' OR ' : '') . '(ROUTS.`gatekeeper_type`=4 AND RIR.`iresource_id` IN ('.implode(',', $value).'))';
					break;

					#5 - группа пользователей (group_id)
					#7 - группа исполнителей (group_id)
					case 'groups':
						$sql[$ltype].=($found > 0 ? ' OR ' : '') . 
						'(ROUTS.`gatekeeper_type`=5 AND ROUTS.`gatekeeper_id` IN ('.implode(',', $value).')) OR ' .
						'(ROUTS.`gatekeeper_type`=7 AND IR.`worker_group` IN ('.implode(',', $value).'))';
					break;

					#6 - должность в организации(cp_uid)
					case 'posts':
						$sql[$ltype].=($found > 0 ? ' OR ' : '') . '(ROUTS.`gatekeeper_type`=6 AND ROUTS.`gatekeeper_id` IN ('.implode(',', $value).'))';
					break;

				}
				$found++;
			}#Формирование SQL запроса - WHERE

			$sql_part[$ltype] = '
				SELECT 
				'.(!empty($params['count']) ? '
					count(*) as `count`
				':'
					REQ.`request_id` as `request_id`,
					REQ.`request_type` as `request_type`,
					IR.`iresource_id` as `iresource_id`,
					'.(!empty($params['fullinfo']) ? '
					IR.`full_name` as `iresource_name`,
					EMP.`search_name` as `employer_name`,
					C.`full_name` as `company_name`,
					P.`full_name` as `post_name`,
					REQ.`phone` as `phone`,
					REQ.`email` as `email`,
					':'').'
					REQ.`curator_id` as `curator_id`,
					REQ.`employer_id` as `employer_id`,
					REQ.`company_id` as `company_id`,
					REQ.`post_uid` as `post_uid`,
					DATE_FORMAT(REQ.`timestamp`, "%d.%m.%Y") as `create_date`,
					RIR.`rires_id` as `rires_id`,
					RIR.`route_id` as `route_id`,
					RS.`rstep_id` as `rstep_id`,
					ROUTS.`step_uid` as `step_uid`,
					ROUTS.`gatekeeper_role` as `gatekeeper_role`,
					ROUTS.`gatekeeper_type` as `gatekeeper_type`,
					ROUTS.`gatekeeper_id` as `gatekeeper_id`,
					ROUTS.`step_yes` as `step_approve`,
					ROUTS.`step_no` as `step_decline`,
					'.($ltype == 'gatekeeper' ? 1 : 0).' as `is_gatekeeper`,
					'.($ltype == 'gatekeeper' ? 0 : 1).' as `is_assistant`,
					IF(ROUTS.`gatekeeper_role` IN (1,2,3), 1, 0) as `can_approve`,
					IF(ROUTS.`gatekeeper_role` IN (1,2), 1, 0) as `can_decline`,
					IF(ROUTS.`gatekeeper_role` != 4, 1, 0) as `can_comment`
				').'
				FROM `request_iresources` as RIR
				INNER JOIN `request_steps` as RS ON RS.`rstep_id`=RIR.`current_step` AND RS.`step_complete`=0
				INNER JOIN `requests` as REQ ON REQ.`request_id`=RIR.`request_id`
				INNER JOIN `iresources` as IR ON IR.`iresource_id`=RIR.`iresource_id`
				'.(!empty($params['fullinfo']) && empty($params['count']) ? '
				INNER JOIN `employers` as EMP ON EMP.`employer_id`=REQ.`employer_id` AND EMP.`status`>0
				INNER JOIN `companies` as C ON C.`company_id`=REQ.`company_id` AND C.`is_lock`=0
				INNER JOIN `company_posts` as CP ON CP.`post_uid`=REQ.`post_uid`
				INNER JOIN `posts` as P ON P.`post_id`=CP.`post_id`
				':'').'
				INNER JOIN `route_steps` as ROUTS ON 
					ROUTS.`route_id`=RIR.`route_id` AND 
					ROUTS.`step_uid`=RS.`step_uid` AND 
					ROUTS.`step_type`=2 AND ROUTS.`gatekeeper_role` IN ('.implode(',',$params['roles']).') AND ('.$sql[$ltype].')
				WHERE 
					RIR.`route_status`=1
					'.(!empty($params['request_id']) ? ('AND RIR.`request_id` IN ('.implode(',',$params['request_id']).')') : '').'
					'.(!empty($params['iresource_id']) ? ('AND RIR.`iresource_id` IN ('.implode(',',$params['iresource_id']).')') : '').'
				LIMIT 30
			';

		}#Подготовка SQL

		if(strlen($sql['assistant'])>0){
			$completeSql = 'SELECT RSLT.* FROM (('.implode(')UNION ALL(',array_values($sql_part)).')) as RSLT '.($params['single']==true && !$params['count'] ? 'LIMIT 1' : 'LIMIT 30');
		}else{
			$completeSql = 'SELECT RSLT.* FROM ('.$sql_part['gatekeeper'].') as RSLT '.($params['single']==true && !$params['count'] ? ' LIMIT 1' : 'LIMIT 30');
		}


		if(!empty($params['count'])){
			$result = $this->db->selectFromField('count',$completeSql);
			return array_sum($result);
		}

		$result = (empty($params['single']) ? $this->db->select($completeSql) : $this->db->selectRecord($completeSql));

		if(!is_array($params['fields'])) return $result;
		if(!empty($params['single'])) return $this->getRecordWithCustomFields($result, $params['fields']);

		$return = array();
		foreach($result as $record){
			$return[] = $this->getRecordWithCustomFields($record, $params['fields']);
		}
		return $return;
	}#end function




	/*
	 * Возвращает список всех активных заявок (заявок в процессе согласования), в которых сотрудник участвует как гейткипер (согласование, утверждение) или как исполнитель
	 */
	public function getAllActiveRequests($only_count=false){
		if(!$this->is_init) return false;
		if(is_array($this->allActiveRequests)) return $this->allActiveRequests;
		$this->gatekeeperRequests = $this->getActiveRequests(array(
			'roles'=>array(1,2,3),
			'fullinfo'=> true,
			'fields'=>array('gatekeeper_role','is_assistant','request_id','request_type','iresource_id','iresource_name','company_name','employer_name','post_name','phone','email'),
			'count' => $only_count
		));
		return $this->gatekeeperRequests;
	}#end function




	/*
	 * Возвращает список активных заявок (заявок в процессе согласования), в которых сотрудник участвует как гейткипер (согласование, утверждение)
	 */
	public function getGatekeeperRequests(){
		if(!$this->is_init) return false;
		if(is_array($this->gatekeeperRequests)) return $this->gatekeeperRequests;
		$this->gatekeeperRequests = $this->getActiveRequests(array(
			'roles'=>array(1,2),
			'fullinfo'=> true,
			'fields'=>array('gatekeeper_role','is_assistant','request_id','request_type','iresource_id','iresource_name','company_name','employer_name','post_name','phone','email')
		));
		return $this->gatekeeperRequests;
	}#end function





	/*
	 * Возвращает список активных заявок (заявок в процессе согласования), в которых сотрудник участвует как исполнитель
	 */
	public function getPerformerRequests(){
		if(!$this->is_init) return false;
		if(is_array($this->performerRequests)) return $this->performerRequests;
		$this->performerRequests = $this->getActiveRequests(array(
			'roles'=>array(3),
			'fullinfo'=> true,
			'fields'=>array('gatekeeper_role','is_assistant','request_id','request_type','iresource_id','iresource_name','company_name','employer_name','post_name','phone','email')
		));
		return $this->performerRequests;
	}#end function




	/*
	 * Возвращает запрошенный параметр заявки на текущем этапе ее согласования
	 * Если параметр не задан, возвращается запись целиком
	 * Если параметр задан как массив, возвращается ассоциированный массив полей, заданных элементами массива $param
	 */
	public function getRequestParam($request_id=0, $iresource_id=0, $param=null){
		if(!$this->is_init) return false;
		$request_id = intval($request_id);
		$iresource_id = intval($iresource_id);
		if(empty($request_id)||empty($iresource_id)||empty($param)) return false;
		$key = $request_id.'-'.$iresource_id;
		if(!isset($this->internalCache['requests'][$key])){
			$this->internalCache['requests'][$key] = $this->getActiveRequests(array(
				'roles'			=> array(1,2,3,4),
				'single'		=> true,
				'fullinfo'		=> false,
				'request_id'	=> array($request_id),
				'iresource_id'	=> array($iresource_id)
			));
		}

		if(is_array($this->internalCache['requests'][$key])){
			if(empty($param)) return $this->internalCache['requests'][$key];
			if(!is_array($param)) return (empty($this->internalCache['requests'][$key][$param]) ? null : $this->internalCache['requests'][$key][$param]);
			return $this->getRecordWithCustomFields($this->internalCache['requests'][$key], $param);
		}

		return null;
	}#end function






	/*
	 * Проверяет, может ли сотрудник на текущем этапе согласования заявки ее одобрять
	 */
	public function canApprove($request_id=0, $iresource_id=0){
		$result = $this->getRequestParam($request_id, $iresource_id, 'can_approve');
		return (empty($result) ? false : true);
	}#end function





	/*
	 * Проверяет, может ли сотрудник на текущем этапе согласования заявки ее отклонять
	 */
	public function canDecline($request_id=0, $iresource_id=0){
		$result = $this->getRequestParam($request_id, $iresource_id, 'can_decline');
		return (empty($result) ? false : true);
	}#end function





	/*
	 * Проверяет, может ли сотрудник на текущем этапе согласования заявки ее просматривать
	 */
	public function canWatch($request_id=0, $iresource_id=0){

		#Сначала проверяем роли сотрудника в отношении данной заявки, если явно указано, что может просматривать - возвращаем true
		$roles = $this->dbWatcherRoles($this->employer_id, $request_id, $iresource_id);
		foreach($roles as $role){
			if($role == true) return true;
		}

		$result = $this->getRequestParam($request_id, $iresource_id);
		return (empty($result) ? false : true);
	}#end function



	/*
	 * Проверяет, может ли сотрудник на текущем этапе согласования заявки оставлять в ней комментарии
	 */
	public function canComment($request_id=0, $iresource_id=0){

		#Сначала проверяем роли сотрудника в тоношении данной заявки, если явно указано, что сотрудник заявитель, куратор, исполнитель или гейткипер - возвращаем true
		$roles = $this->dbWatcherRoles($this->employer_id, $request_id, $iresource_id);
		if($roles['is_owner'] == true || $roles['is_curator'] == true || $roles['is_gatekeeper'] == true || $roles['is_performer'] == true) return true;

		$result = $this->getRequestParam($request_id, $iresource_id, 'can_comment');
		return (empty($result) ? false : true);
	}#end function








	/*==============================================================================================
	Профайл
	==============================================================================================*/



	/*
	 * Получение информации сотрудника из БД
	 */
	public function getInfo($employer_id=0, $fields=array(), $read_from_db=false){

		if(!$this->is_init) return false;
		$employer_id = intval($employer_id);
		if(empty($employer_id)) $employer_id = $this->employer_id;
		if(is_array($this->myself['info']) && !$read_from_db) return (empty($fields)) ? $this->myself['info'] : (is_array($fields) ? $this->getRecordWithCustomFields($this->myself['info'], $fields) : $this->myself['info'][$fields] );

		$this->db->prepare('SELECT * FROM `employers` WHERE `employer_id`=? LIMIT 1');
		$this->db->bind($employer_id);
		if( ($this->myself['info'] = $this->db->selectRecord())===false) return false;

		return (empty($fields)) ? $this->myself['info'] : (is_array($fields) ? $this->getRecordWithCustomFields($this->myself['info'], $fields) : $this->myself['info'][$fields] );
	}#end function




	/*
	 * Изменяет информацию сотрудника в БД
	 */
	public function changeInfo($employer_id=0, $fields=array()){

		if(!$this->is_init) return false;
		$employer_id = intval($employer_id);
		if(empty($employer_id)) $employer_id = $this->employer_id;

		$allow_fields = array('status','username','password','language','theme','search_name','first_name','last_name','middle_name','birth_date','phone','email','work_name','work_address','work_post','work_phone','notice_me_requests','notice_curator_requests','notice_gkemail_1','notice_gkemail_2','notice_gkemail_3','notice_gkemail_4');
		$change_fields = array();
		foreach($fields as $field=>$value){
			if(in_array($field, $allow_fields)){
				if($field == 'password'){
					$change_fields[$field] = sha1($value);
					$change_fields['change_password'] = 1;
				}else{
					$change_fields[$field] = $value;
				}
			}
		}

		if(empty($change_fields)) return true;

		$update_sql = $this->db->buildSqlConditions($change_fields,'',',');

		$this->db->prepare('UPDATE `employers` SET ? WHERE `employer_id`=?');
		$this->db->bindSql($update_sql);
		$this->db->bind($employer_id);

		return ($this->db->update() === false ? false : true);
	}#end function





	/*
	 * Получение перечня организаций, в которых сотрудник может создавать новых сотрудников
	 */
	public function canAddEmployerCompanies($employer_id=0, $fullinfo=false){

		if(!$this->is_init) return false;
		$employer_id = intval($employer_id);
		if(empty($employer_id)) $employer_id = $this->employer_id;

		if(!$fullinfo){
			$this->db->prepare('
				SELECT 
					DISTINCT C.`company_id` as `company_id`
				FROM `companies` as C
				INNER JOIN `employer_rights` as ER ON ER.`employer_id`=? AND ER.`company_id` IN (0, C.`company_id`) AND ER.`can_add_employer`=1 
				WHERE C.`is_lock`=0
			');
			$this->db->bind($employer_id);
			return $this->db->selectFromField('company_id');
		}

		$this->db->prepare('
			SELECT 
				DISTINCT C.`company_id` as `company_id`,
				C.`full_name` as `company_name`
			FROM `companies` as C
			INNER JOIN `employer_rights` as ER ON ER.`employer_id`=? AND ER.`company_id` IN (0, C.`company_id`) AND ER.`can_add_employer`=1 
			WHERE C.`is_lock`=0
		');
		$this->db->bind($employer_id);
		return $this->db->select();

	}#end function




	/*
	 * Получение перечня организаций, в которых сотрудник может писать заявки от имени других сотрудников
	 */
	public function canCuratorCompanies($employer_id=0, $fullinfo=false){

		if(!$this->is_init) return false;
		$employer_id = intval($employer_id);
		if(empty($employer_id)) $employer_id = $this->employer_id;

		if(!$fullinfo){
			$this->db->prepare('
				SELECT 
					DISTINCT C.`company_id` as `company_id`
				FROM `companies` as C
				INNER JOIN `employer_rights` as ER ON ER.`employer_id`=? AND ER.`company_id` IN (0, C.`company_id`) AND ER.`can_curator`=1 
				WHERE C.`is_lock`=0
			');
			$this->db->bind($employer_id);
			return $this->db->selectFromField('company_id');
		}

		$this->db->prepare('
			SELECT 
				DISTINCT C.`company_id` as `company_id`,
				C.`full_name` as `company_name`
			FROM `companies` as C
			INNER JOIN `employer_rights` as ER ON ER.`employer_id`=? AND ER.`company_id` IN (0, C.`company_id`) AND ER.`can_curator`=1 
			WHERE C.`is_lock`=0
		');
		$this->db->bind($employer_id);
		return $this->db->select();
	}#end function





	/*
	 * Формирование ФИО по формату: И.О. Фамилия
	 */
	public function getEmployerName($employer_id=0, $format='{f}.{m}. {last}'){

		if(!$this->is_init) return false;
		$employer_id = intval($employer_id);
		if(empty($employer_id)) $employer_id = $this->employer_id;
		$info = $this->getInfo($employer_id, null, true);
		if(empty($info)) return false;
		$first	= mb_convert_case($info['first_name'], MB_CASE_TITLE, 'UTF-8');
		$last	= mb_convert_case($info['last_name'], MB_CASE_TITLE, 'UTF-8');
		$middle	= mb_convert_case($info['middle_name'], MB_CASE_TITLE, 'UTF-8');
		$f		= mb_substr($first,0,1,'UTF-8');
		$l		= mb_substr($last,0,1,'UTF-8');
		$m		= mb_substr($middle,0,1,'UTF-8');

		return str_replace(
			array('{f}','{m}','{l}','{first}','{middle}','{last}'),
			array($f,$m,$l,$first,$middle,$last),
			$format
		);
	}#end function





}#end class

?>
