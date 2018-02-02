<?php
/*==================================================================================================
Описание: Организационная структура
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');




class Admin_Organization{


	/*==============================================================================================
	Переменные класса
	==============================================================================================*/

	public $options = array(
		'db' => null
	);
	private $is_init		= false;	#ПРизнак корректной инициализации класса
	private $db 			= null;		#Указатель на экземпляр базы данных


	private $defaultCompanyRecord = array(
		'is_lock'		=> 0,
		'full_name'		=> '',
		'short_name'	=> ''
	);

	private $defaultPostRecord = array(
		'full_name'		=> '',
		'short_name'	=> ''
	);




	/*==============================================================================================
	Инициализация
	==============================================================================================*/


	/*
	 * Конструктор класса
	 */
	public function __construct($options=array()){
		$this->db = Database::getInstance('main');
	}#end function






	/*==============================================================================================
	ФУНКЦИИ: Работа с организациями
	==============================================================================================*/


	/*
	 * Получение списка организаций
	 */
	public function getCompaniesList($fields=null){
		$result = $this->db->select('SELECT * FROM `companies`');
		if(empty($fields)) return $result;
		$return = array();
		foreach($result as $record){
			$return[] = arrayCustomFields($record, $fields);
		}
		return $return;
	}#end function



	/*
	 * Проверка cуществования организации
	 */
	public function companyExists($company=0){
		if(empty($company)) return false;
		if(is_numeric($company)) return UserAccess::_companyExists($company);
		$this->db->prepare('SELECT count(*) FROM `companies` WHERE `full_name` LIKE ? LIMIT 1');
		$this->db->bind($company);
		return ($this->db->result() > 0);
	}#end function




	/*
	 * Возвращает название организации по ее ID
	 */
	public function getCompanyName($company_id=0){
		$this->db->prepare('SELECT `full_name` FROM `companies` WHERE `company_id`=? LIMIT 1');
		$this->db->bind($company_id);
		return $this->db->result();
	}#end function



	/*
	 * Добавление организации
	 */
	public function companyNew($fields=array()){

		if(empty($fields)) return false;
		$add_fields = array_intersect(array_keys($this->defaultCompanyRecord), array_keys($fields));
		$adds = array();
		if(empty($add_fields)) return false;
		foreach($add_fields as $add_field){
			$adds[$add_field] = $fields[$add_field];
		}
		$fields = array_merge($this->defaultCompanyRecord, $adds);
		$ins_names=array();
		$ins_q=array();
		$binds=array();
		foreach($fields as $field=>$value){
			$ins_names[]='`'.$field.'`';
			$ins_q[]='?';
			$binds[]=$value;
		}
		$this->db->prepare('INSERT INTO `companies` ('.implode(',',$ins_names).')VALUES('.implode(',',$ins_q).')');
		foreach($binds as $bind) $this->db->bind($bind);
		if(($company_id = $this->db->insert())===false) return false;

		#Устанавливаем, что данные ACL не актуальны
		UserAccess::_setAccessObjectsActual(false);

		return $company_id;
	}#end function





	/*
	 * Обновление информации об организации
	 */
	public function companyUpdate($company_id=0, $fields=array()){

		if(empty($company_id)||empty($fields)) return false;
		$update_fields = array_intersect(array_keys($this->defaultCompanyRecord), array_keys($fields));
		if(empty($update_fields)) return false;
		$binds=array();
		$updates='';
		foreach($update_fields as $update_field){
			$updates.=(empty($updates)?'':',').'`'.$update_field.'`=?';
			$binds[]=$fields[$update_field];
		}
		$this->db->prepare('UPDATE `companies` SET '.$updates.' WHERE `company_id`=?');
		foreach($binds as $bind) $this->db->bind($bind);
		$this->db->bind($company_id);

		if($this->db->update()===false) return false;

		#Устанавливаем, что данные ACL не актуальны
		UserAccess::_setAccessObjectsActual(false);

		return true;
	}#end function





	/*
	 * Удаление организации
	 */
	public function companyDelete($company_id=0, $check_can_delete=true){

		if(empty($company_id)) return false;
		if($check_can_delete && !$this->companyCanDelete($company_id)) return false;

		//Проверяем наличие транзакции
		$in_transaction = $this->db->inTransaction();
		if(!$in_transaction) $this->db->transaction();

		//Удаление из companies
		$this->db->prepare('DELETE FROM `companies` WHERE `company_id`=?');
		$this->db->bind($company_id);
		if($this->db->delete()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		//Удаление из company_posts
		$this->db->prepare('DELETE FROM `company_posts` WHERE `company_id`=?');
		$this->db->bind($company_id);
		if($this->db->delete()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		//Удаление из employer_rights
		$this->db->prepare('DELETE FROM `employer_rights` WHERE `company_id`=?');
		$this->db->bind($company_id);
		if($this->db->delete()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		//Обновление iresources
		$this->db->prepare('UPDATE `iresources` SET `company_id`=0,`post_uid`=0 WHERE `company_id`=?');
		$this->db->bind($company_id);
		if($this->db->update()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		//Удаление из iresource_companies
		$this->db->prepare('DELETE FROM `iresource_companies` WHERE `company_id`=?');
		$this->db->bind($company_id);
		if($this->db->delete()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		//Обновление templates
		$this->db->prepare('UPDATE `templates` SET `company_id`=0,`post_uid`=0 WHERE `company_id`=?');
		$this->db->bind($company_id);
		if($this->db->update()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		//Обновление route_params
		$this->db->prepare('UPDATE `route_params` SET `for_company`=0 WHERE `for_company`=?');
		$this->db->bind($company_id);
		if($this->db->update()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}


		//Выполнено успешно
		if(!$in_transaction) $this->db->commit();

		#Устанавливаем, что данные ACL не актуальны
		UserAccess::_setAccessObjectsActual(false);

		return true;
	}#end function





	/*
	 * Проверка допустимости удаления организации
	 */
	public function companyCanDelete($company_id=0){

		#Проверка использования объекта доступа в шаблонах и заявках
		$this->db->prepare('
			(SELECT count(*) as `count`FROM `company_posts` WHERE `company_id`=? LIMIT 1) UNION
			(SELECT count(*)  as `count` FROM `iresources` WHERE `company_id`=? LIMIT 1) UNION
			(SELECT count(*)  as `count` FROM `templates` WHERE `company_id`=? LIMIT 1) UNION
			(SELECT count(*)  as `count` FROM `requests` WHERE `company_id`=? LIMIT 1)
		');
		$this->db->bind($company_id);
		$this->db->bind($company_id);
		$this->db->bind($company_id);
		$this->db->bind($company_id);

		if(($counts = $this->db->selectFromField('count')) === false )return false;

		return (array_sum($counts) > 0 ? false : true);
	}#end function








	/*==============================================================================================
	ФУНКЦИИ: Работа с должностями
	==============================================================================================*/


	/*
	 * Получение списка должностей
	 */
	public function getPostList($assoc=false){
		#Получение массива должностей
		if($assoc) return $this->db->selectByKey('post_id','SELECT `post_id`, `full_name` FROM `posts`');
		return $this->db->select('SELECT `post_id`, `full_name` FROM `posts`');
	}#end function



	/*
	 * Возвращает UID должности
	 */
	public function getPostUID($company_id, $post_id){
		return '1'.str_pad($company_id, 5, '0', STR_PAD_LEFT).str_pad($post_id, 7, '0', STR_PAD_LEFT).str_pad(0, 7, '0', STR_PAD_LEFT);
	}#end function



	/*
	 * Получение списка должностей
	 */
	public function getPostsList(){
		return $this->db->select('SELECT * FROM `posts`');
	}#end function



	/*
	 * Проверка cуществования должности
	 */
	public function postExists($post=0){
		if(empty($post)) return false;
		if(is_numeric($post)) 
			$this->db->prepare('SELECT count(*) FROM `posts` WHERE `post_id`=? LIMIT 1');
		else
			$this->db->prepare('SELECT count(*) FROM `posts` WHERE `full_name` LIKE ? LIMIT 1');
		$this->db->bind($post);
		return ($this->db->result() > 0);
	}#end function




	/*
	 * Добавление должности
	 */
	public function postNew($fields=array()){

		if(empty($fields)) return false;
		$add_fields = array_intersect(array_keys($this->defaultPostRecord), array_keys($fields));
		$adds = array();
		if(empty($add_fields)) return false;
		foreach($add_fields as $add_field){
			$adds[$add_field] = $fields[$add_field];
		}
		$fields = array_merge($this->defaultPostRecord, $adds);
		$ins_names=array();
		$ins_q=array();
		$binds=array();
		foreach($fields as $field=>$value){
			$ins_names[]='`'.$field.'`';
			$ins_q[]='?';
			$binds[]=$value;
		}
		$this->db->prepare('INSERT INTO `posts` ('.implode(',',$ins_names).')VALUES('.implode(',',$ins_q).')');
		foreach($binds as $bind) $this->db->bind($bind);
		if(($post_id = $this->db->insert())===false) return false;

		return $post_id;
	}#end function




	/*
	 * Обновление информации о должности
	 */
	public function postUpdate($post_id=0, $fields=array()){

		if(empty($post_id)||empty($fields)) return false;
		$update_fields = array_intersect(array_keys($this->defaultPostRecord), array_keys($fields));
		if(empty($update_fields)) return false;
		$binds=array();
		$updates='';
		foreach($update_fields as $update_field){
			$updates.=(empty($updates)?'':',').'`'.$update_field.'`=?';
			$binds[]=$fields[$update_field];
		}
		$this->db->prepare('UPDATE `posts` SET '.$updates.' WHERE `post_id`=?');
		foreach($binds as $bind) $this->db->bind($bind);
		$this->db->bind($post_id);

		if($this->db->update()===false) return false;

		return true;
	}#end function




	/*
	 * Удаление должности
	 */
	public function postDelete($post_id=0, $check_can_delete=true){

		if(empty($post_id)) return false;
		if($check_can_delete && !$this->postCanDelete($post_id)) return false;

		//Проверяем наличие транзакции
		$in_transaction = $this->db->inTransaction();
		if(!$in_transaction) $this->db->transaction();

		//Удаление из posts
		$this->db->prepare('DELETE FROM `posts` WHERE `post_id`=?');
		$this->db->bind($post_id);
		if($this->db->delete()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		//Удаление из company_posts
		$this->db->prepare('DELETE FROM `company_posts` WHERE `post_id`=?');
		$this->db->bind($post_id);
		if($this->db->delete()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		//Выполнено успешно
		if(!$in_transaction) $this->db->commit();

		return true;
	}#end function




	/*
	 * Проверка допустимости удаления должности
	 */
	public function postCanDelete($post_id=0){

		#Проверка использования объекта доступа в шаблонах и заявках
		$this->db->prepare('
			(SELECT count(*) as `count` FROM `company_posts` WHERE `post_id`=? LIMIT 1)
		');
		$this->db->bind($post_id);

		if(($counts = $this->db->selectFromField('count')) === false )return false;

		return (array_sum($counts) > 0 ? false : true);
	}#end function









	/*==============================================================================================
	ФУНКЦИИ: Работа с организационной структурой
	==============================================================================================*/


	/*
	 * Получение организационной структуры
	 */
	public function getOrgChart($company_id=0){

		$company_id = intval($company_id);
		if(empty($company_id)) return false;
		if(!UserAccess::_companyExists($company_id)) return false;

		#Получение массива орг структуры
		$this->db->prepare('
			SELECT
				CP.`id` as `id`,
				CP.`post_uid` as `post_uid`,
				CP.`boss_uid` as `boss_uid`,
				CP.`company_id` as `company_id`,
				CP.`post_id` as `post_id`,
				CP.`boss_id` as `boss_id`,
				POST.`short_name` as `short_name`,
				POST.`full_name` as `full_name`
			FROM `company_posts` as CP
				INNER JOIN `posts` as POST ON CP.`post_id` = POST.`post_id`
			WHERE `company_id`=?
		');
		$this->db->bind($company_id);
		return $this->db->select();
	}#end function




	/*
	 * Получение организационной структуры
	 */
	public function getCompanyPosts($company_id=0){

		$company_id = intval($company_id);
		if(empty($company_id)) return false;
		if(!UserAccess::_companyExists($company_id)) return false;

		#Получение массива орг структуры
		$this->db->prepare('
			SELECT
				CP.`company_id` as `company_id`,
				CP.`post_uid` as `post_uid`,
				CP.`boss_uid` as `boss_uid`,
				CP.`post_id` as `post_id`,
				CP.`boss_id` as `boss_id`,
				POST.`full_name` as `post_name`,
				PBOSS.`full_name` as `boss_post_name`
			FROM `company_posts` as CP
				INNER JOIN `posts` as POST ON POST.`post_id` = CP.`post_id`
				LEFT JOIN `posts` as PBOSS ON  PBOSS.`post_id` = CP.`boss_id`
			WHERE CP.`company_id`=?
		');
		$this->db->bind($company_id);
		return $this->db->select();
	}#end function



	/*
	 * Возвращает название должности по UID
	 */
	public function getPostUIDName($post_uid=0){
		$this->db->prepare('SELECT P.`full_name` FROM `company_posts` as CP INNER JOIN `posts` as P ON P.`post_id`=CP.`post_id` WHERE `post_uid`=? LIMIT 1');
		$this->db->bind($post_uid);
		return $this->db->result();
	}#end function




	/*
	 * Проверка cуществования должности в организации
	 */
	public function postUIDExists($post_uid=0, $company_id=0){
		if(empty($post_uid)) return false;
		if(empty($company_id)){
			$this->db->prepare('SELECT count(*) FROM `company_posts` WHERE `post_uid`=? LIMIT 1');
			$this->db->bind($post_uid);
		}else{
			$this->db->prepare('SELECT count(*) FROM `company_posts` WHERE `post_uid`=? AND `company_id`=? LIMIT 1');
			$this->db->bind($post_uid);
			$this->db->bind($company_id);
		}
		return ($this->db->result() > 0);
	}#end function



	/*
	 * Получение списка идентификаторов должностей
	 */
	public function getPostUIDs($company_id=0){

		$company_id = intval($company_id);
		if(empty($company_id)) return false;
		if(!UserAccess::_companyExists($company_id)) return false;
		$this->db->prepare('SELECT `post_uid` FROM `company_posts` WHERE `company_id`=?');
		$this->db->bind($company_id);
		return $this->db->selectFromField('post_uid');
	}#end function



	/*
	 * Проверка допустимости удаления шага маршрута
	 */
	public function postUIDsCanDelete($company_id=0, $post_uid=null){
		$wsql = '';
		if(empty($post_uid)) return false;

		if(is_array($post_uid)){
			$wsql = ' AND CP.`post_uid` IN ('.implode(',',array_map(array($this->db,'getQuotedValue'),$post_uid)).')';
		}else{
			$wsql = ' AND CP.`post_uid` = '.$this->db->getQuotedValue($post_uid);
		}
		$this->db->prepare('
			(SELECT count(*) as `count` FROM `company_posts` as CP
				INNER JOIN `employer_posts` as EP ON EP.`post_uid`=CP.`post_uid`
			WHERE CP.`company_id`=? '.$wsql.' LIMIT 1)
			UNION
			(SELECT count(*) as `count` FROM `complete_roles_full` as CP WHERE CP.`company_id`=?  '.$wsql.' LIMIT 1)
			UNION
			(SELECT count(*) as `count` FROM `requests` as CP WHERE CP.`company_id`=? '.$wsql.' LIMIT 1)
		');
		$this->db->bind($company_id);
		$this->db->bind($company_id);
		$this->db->bind($company_id);
		if(($counts = $this->db->selectFromField('count')) === false )return false;
		return (array_sum($counts) > 0 ? false : true);
	}#end function



}#end class

?>