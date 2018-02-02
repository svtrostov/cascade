<?php
/*==================================================================================================
Описание: Класс протоколирования действий пользователей
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');


class Protocol{

	use Trait_SingletonUnique;

	/*==============================================================================================
	Переменные класса
	==============================================================================================*/

	#db
	private $db = null;
	private $user = null;
	private $employer_id = 0;
	private $session_uid = 0;
	private $object_types = null;


	/*
	CREATE TABLE IF NOT EXISTS `protocol_actions` (
	  `action_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	  `action_name` char(128) NOT NULL COMMENT "Имя действия",
	  `employer_id` int(10) unsigned NOT NULL COMMENT "Идентификатор пользователя",
	  `session_uid` int(10) unsigned NOT NULL COMMENT "Идентификатор сессии пользователя, в рамках которой было выполнено действие",
	  `timestamp` datetime NOT NULL COMMENT "Дата и время действия",
	  `company_id` int(10) unsigned NOT NULL COMMENT "ID организации в рамках которой выполняется действие",
	  `acl_id` int(10) unsigned NOT NULL COMMENT "Идентификатор ACL объекта, в рамках которого выполняется действие",
	  `acl_name` char(128) NOT NULL COMMENT "Имя ACL объекта, в рамках которого выполняется действие",
	  `primary_type` char(32) NOT NULL COMMENT "Тип основного объекта, над которым выполняется действие",
	  `primary_id` bigint(20) unsigned NOT NULL COMMENT "Идентификатор основного объекта, над которым выполняется действие",
	  `secondary_type` char(32) NOT NULL COMMENT "Тип дополнительного объекта, над которым выполняется действие",
	  `secondary_id` bigint(20) unsigned NOT NULL COMMENT "Идентификатор дополнительного объекта, над которым выполняется действие",
	  `description` char(255) NOT NULL COMMENT "Описание действия",
	  PRIMARY KEY (`action_id`),
	  KEY `employer_id` (`employer_id`),
	  KEY `session_uid` (`session_uid`),
	  KEY `timestamp` (`timestamp`),
	  KEY `company_id` (`company_id`),
	  KEY `acl_id` (`acl_id`),
	  KEY `primary_type` (`primary_type`),
	  KEY `primary_id` (`primary_id`),
	  KEY `secondary_type` (`secondary_type`),
	  KEY `secondary_id` (`secondary_id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT="Таблица протоколирования действий пользователей" AUTO_INCREMENT=1;

	CREATE TABLE IF NOT EXISTS `protocol_values` (
	  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	  `action_id` int(10) unsigned NOT NULL COMMENT "Идентификатор действия",
	  `value` varchar(32768) NOT NULL COMMENT "Изменяемые значения",
	  PRIMARY KEY (`id`),
	  KEY `action_id` (`action_id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT="Таблица значений действий пользователей" AUTO_INCREMENT=1;


	 */

	private $defaultProtocolActionRecord = array(
		'action_name'		=> '',
		'employer_id'		=> 0,
		'session_uid'		=> 0,
		'timestamp'			=> '0000-00-00 00:00:00',
		'company_id'		=> 0,
		'acl_id'			=> 0,
		'acl_name'			=> '',
		'primary_type'		=> '',
		'primary_id'		=> 0,
		'secondary_type'	=> '',
		'secondary_id'		=> 0,
		'description'		=> ''
	);


	private $defaultProtocolValueRecord = array(
		'action_id'	=> 0,
		'value'		=> ''
	);


	/*==============================================================================================
	Инициализация
	==============================================================================================*/


	/*
	 * Конструктор класса
	 */
	protected function init(){

		$this->db			= Database::getInstance('main');
		$this->user			= User::getInstance();
		$this->employer_info= $this->user->getEmployerInfo();
		$this->employer_id	= $this->user->getEmployerID();
		$this->session_uid	= intval(Session::_get('session_uid'));
		$this->object_types	= Config::getOption('protocol','object_types',array());

	}#end function






	/*==============================================================================================
	ФУНКЦИИ: Вспомогательные функции
	==============================================================================================*/

	/*
	 * Возвращает идентификатор типа объекта по его имени
	 */
	public function getObjectTypeID($otype_name=''){
		if(empty($otype_name)||empty($this->object_types)||empty($this->object_types[$otype_name])) return false;
		if(empty($this->object_types[$otype_name]['id'])) return false;
		return $this->object_types[$otype_name]['id'];
	}#end function




	/*
	 * Возвращает список типов объектов
	 */
	public function getObjectTypes(){
		return $this->object_types;
	}#end function





	/*==============================================================================================
	ФУНКЦИИ: Добавление записи в протокол
	==============================================================================================*/



	/*
	 * Добавление записи в протокол
	 */
	public function add($fields=array()){
		if(empty($fields)) return false;
		$fields = array_merge($this->defaultProtocolActionRecord, $fields);
		foreach($fields as $key=>$value){
			switch($key){
				case 'primary_type':
					if($this->getObjectTypeID($fields[$key])===false) return false;
				break;
				case 'secondary_type':
					if(!empty($fields[$key])){
						if($this->getObjectTypeID($fields[$key])===false) return false;
					}else{
						$fields['secondary_type'] = '';
						$fields['secondary_id'] = 0;
					}
				break;
				case 'employer_id':
					$fields[$key] = $this->employer_id;
				break;
				case 'session_uid':
					$fields[$key] = $this->session_uid;
				break;
				case 'timestamp':
					$fields[$key] = date('Y-m-d H:i:s');
				break;
				case 'value':
					if(empty($value)){
						$fields[$key] = '';
					}else{
						$fields[$key] = json_encode($value);
						if(mb_strlen($fields[$key],'UTF-8')>32760) $fields[$key] = '';
					}
				break;
			}
		}
		return $this->addDBAction($fields);
	}#end function





	/*
	 * Добавление записи в протокол базы данных
	 */
	private function addDBAction($fields=array()){

		if(empty($fields)) return false;
		$add_fields = array_intersect(array_keys($this->defaultProtocolActionRecord), array_keys($fields));
		$adds = array();
		if(empty($add_fields)) return false;
		foreach($add_fields as $add_field){
			$adds[$add_field] = $fields[$add_field];
		}
		$a_fields = array_merge($this->defaultProtocolActionRecord, $adds);
		$ins_names=array();
		$ins_q=array();
		$binds=array();
		foreach($a_fields as $field=>$value){
			$ins_names[]='`'.$field.'`';
			$ins_q[]='?';
			$binds[]=$value;
		}

		//Проверяем наличие транзакции
		$in_transaction = $this->db->inTransaction();
		if(!$in_transaction) $this->db->transaction();

		//Добавление записи в протокол
		$this->db->prepare('INSERT INTO `protocol_actions` ('.implode(',',$ins_names).')VALUES('.implode(',',$ins_q).')');
		foreach($binds as $bind) $this->db->bind($bind);
		if(($action_id = $this->db->insert())===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		//Добавление значений в протокол, если значения заданы
		if(!empty($fields['value'])){
			$fields['action_id'] = $action_id;
			if(!$this->addDBValue($fields)){
				if(!$in_transaction) $this->db->rollback();
				return false;
			}
		}

		if(!$in_transaction) $this->db->commit();
		return $action_id;
	}#end function




	/*
	 * Добавление значений в протокол базы данных
	 */
	private function addDBValue($fields=array()){

		if(empty($fields)) return false;
		$add_fields = array_intersect(array_keys($this->defaultProtocolValueRecord), array_keys($fields));
		$adds = array();
		if(empty($add_fields)) return false;
		foreach($add_fields as $add_field){
			$adds[$add_field] = $fields[$add_field];
		}
		$fields = array_merge($this->defaultProtocolValueRecord, $adds);
		$ins_names=array();
		$ins_q=array();
		$binds=array();
		foreach($fields as $field=>$value){
			$ins_names[]='`'.$field.'`';
			$ins_q[]='?';
			$binds[]=$value;
		}

		//Добавление значений в протокол
		$this->db->prepare('INSERT INTO `protocol_values` ('.implode(',',$ins_names).')VALUES('.implode(',',$ins_q).')');
		foreach($binds as $bind) $this->db->bind($bind);
		if(($value_id = $this->db->insert())===false) return false;

		return $value_id;
	}#end function




	/*==============================================================================================
	ФУНКЦИИ: Получение данных протокола
	==============================================================================================*/


	/*
	 * Получение списка событий протокола
	 */
	public function getProtocolEvents(){
		
		
		
	}#end function



}#end class

?>