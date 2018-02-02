<?php
/*==================================================================================================
Описание: Класс контроля доступа пользователей
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');

if(!defined('ACL_OBJECT_PAGE')) define('ACL_OBJECT_PAGE', 1);
if(!defined('ACL_OBJECT_FUNCTION')) define('ACL_OBJECT_FUNCTION', 2);
if(!defined('ACL_OBJECT_ROLE')) define('ACL_OBJECT_ROLE', 3);


class UserAccess{

	use Trait_SingletonUnique;

	/*==============================================================================================
	Переменные класса
	==============================================================================================*/

	#db
	private $db = null;
	private $user = null;
	private $xcache = null;
	private $employer_id = 0;
	private $access_level = 0;
	private $is_super = false;

	private $objects			= array();	#Массив доступных объектов по ID объекта
	private $object_names		= array();	#Массив сопоставления имен объектов с ID объекта
	private $companies			= array();	#Массив организаций
	private $objects_loaded		= false;
	private $companies_loaded	= false;


	private $defaultObjectRecord = array(
		'type'				=> 0,
		'name'				=> '',
		'desc'				=> '',
		'is_lock'			=> 0,
		'min_access_level'	=> 0,
		'for_all_companies'	=> 0
	);



	/*==============================================================================================
	Инициализация
	==============================================================================================*/


	/*
	 * Конструктор класса
	 */
	protected function init(){

		$this->db			= Database::getInstance('main');
		$this->super_users	= Config::getOption('general', 'super_users', array());
		$this->user			= User::getInstance();
		$this->xcache		= XCache::getInstance();
		$this->employer_id	= $this->user->getEmployerID();
		$this->access_level	= $this->user->getAccessLevel();
		$this->session_name	= $this->user->session_name;
		$this->is_super		= $this->isSuper();

	}#end function



	/*==============================================================================================
	ФУНКЦИИ: Типы ACL объектов
	==============================================================================================*/


	/*
	 * Возвращает список типов ACL объектов
	 */
	public function getObjectTypes(){

		return array(
			array(ACL_OBJECT_PAGE, 'Страницы'),
			array(ACL_OBJECT_FUNCTION, 'Функции'),
			array(ACL_OBJECT_ROLE, 'Контейнеры ролей')
		);

	}#end function





	/*==============================================================================================
	ФУНКЦИИ: Добавление редактирование объектов
	==============================================================================================*/

	/*
	 * Добавление ACL объекта
	 */
	public function newObject($data=array()){
		$allowed=array('type','name','desc','is_lock','min_access_level','for_all_companies');
		$fields='';
		$values='';
		$binds=array();
		$change_name = false;
		foreach($data as $field=>$value){
			if(!in_array($field, $allowed)) continue;
			if($field=='name') $change_name = true;
			$fields.=(!empty($fields)?',':'').'`'.$field.'`';
			$values.=(!empty($values)?',':'').'?';
			$binds[]=$value;
		}
		if(empty($fields)) return array('status' => true);

		#Проверка уникальности имени
		if($change_name && !empty($data['name'])){
			$this->db->prepare('SELECT count(*) FROM `access_objects` WHERE `name` LIKE ? LIMIT 1');
			$this->db->bind($data['name']);
			if($this->db->result()==1) return array(
				'status' => false,
				'desc' => 'Заданное внутреннее имя уже используется другим объектом'
			);
		}else{
			return array(
				'status' => false,
				'desc' => 'Не задано имя объекта'
			);
		}

		$this->db->prepare('INSERT INTO `access_objects` ('.$fields.') VALUES ('.$values.')');
		foreach($binds as $bind) $this->db->bind($bind);
		if(($object_id = $this->db->insert())===false) return array(
			'status' => false,
			'desc' => 'Внутренняя ошибка сервера: не удалось добавить объект'
		);

		$this->setAccessObjectsActual(false);
		return array(
			'status' => true,
			'object_id' => $object_id
		);

	}#end function





	/*
	 * Редактирование ACL объекта
	 */
	public function changeObject($object_id=0, $data=array()){
		$object = $this->getObject($object_id);
		if(!is_array($object)) return array(
			'status' => false,
			'desc' => 'Объект ID='.$object_id.' не существует'
		);
		$allowed=array('name','desc','is_lock','min_access_level','for_all_companies');
		$prepare='';
		$binds=array();
		$change_name = false;
		foreach($data as $field=>$value){
			if(!in_array($field, $allowed)) continue;
			if($field=='name') $change_name = true;
			$prepare.=(!empty($prepare)?',':'').'`'.$field.'`=?';
			$binds[]=$value;
		}
		if(empty($prepare)) return array('status' => true);

		#Проверка уникальности имени
		if($change_name){
			$this->db->prepare('SELECT count(*) FROM `access_objects` WHERE `name` LIKE ? AND `object_id`!=? LIMIT 1');
			$this->db->bind($data['name']);
			$this->db->bind($object['object_id']);
			if($this->db->result()==1) return array(
				'status' => false,
				'desc' => 'Заданное внутреннее имя уже используется другим объектом'
			);
		}

		$this->db->prepare('UPDATE `access_objects` SET '.$prepare.' WHERE `object_id`=?');
		foreach($binds as $bind) $this->db->bind($bind);
		$this->db->bind($object['object_id']);
		if($this->db->update()===false) return array(
			'status' => false,
			'desc' => 'Внутренняя ошибка сервера: не удалось обновить информацию об объекте в базе данных'
		);

		$this->setAccessObjectsActual(false);
		return array('status' => true);

	}#end function





	/*
	 * Удаление ACL объекта
	 */
	public function deleteObject($object_id=0){
		$object = $this->getObject($object_id);
		if(!is_array($object)) return array(
			'status' => false,
			'desc' => 'Объект ID='.$object_id.' не существует'
		);

		$prepares = array(
			'DELETE FROM `access_objects` WHERE `object_id`=?',
			'DELETE FROM `access_roles` WHERE `parent_id`=?',
			'DELETE FROM `access_roles` WHERE `object_id`=?',
			'DELETE FROM `employer_access` WHERE `object_id`=?',
			'UPDATE `menu_map` SET `access_object_id`=0 WHERE `access_object_id`=?'
		);

		foreach($prepares as $prepare){
			$this->db->prepare($prepare);
			$this->db->bind($object['object_id']);
			if($this->db->simple()===false) return array(
				'status' => false,
				'desc' => 'Ошибка удаления объекта из базы данных'
			);
		}

		$this->setAccessObjectsActual(false);
		return array('status' => true);
	}#end function




	/*
	 * Включение объектов в контейнер роли
	 */
	public function containerInclude($role_id=0, $objects){

		$parent = $this->getObject($role_id);
		if(!is_array($parent)) return array(
			'status' => false,
			'desc' => 'Контейнер роли ID='.$role_id.' не существует'
		);

		if(empty($objects)||!is_array($objects)) return array(
			'status' => false,
			'desc' => 'Не заданы объекты доступа'
		);

		$objects = array_map('intval', $objects);
		foreach($objects as $child_id){

			if(!$child_id) return array(
				'status' => false,
				'desc' => 'Заданы не корректные идентификаторы объектов'
			);

			$child = $this->getObject($child_id);
			if(!is_array($child)) return array(
				'status' => false,
				'desc' => 'Объект ID='.$child_id.' не существует'
			);

			if($this->haveCollision($parent['object_id'], $child['object_id'])) continue;

			$this->db->prepare('REPLACE INTO `access_roles` (`parent_id`,`object_id`) VALUES (?,?)');
			$this->db->bind($parent['object_id']);
			$this->db->bind($child['object_id']);
			if($this->db->simple()===false) return array(
				'status' => false,
				'desc' => 'Ошибка добавления объектов в контейнер роли'
			);

		}//foreach

		$this->setAccessObjectsActual(false);
		return array('status' => true);
	}#end function






	/*
	 * Исключение объектов из контейнера роли
	 */
	public function containerExclude($role_id=0, $objects){

		$parent = $this->getObject($role_id);
		if(!is_array($parent)) return array(
			'status' => false,
			'desc' => 'Контейнер роли ID='.$role_id.' не существует'
		);

		if(empty($objects)||!is_array($objects)) return array(
			'status' => false,
			'desc' => 'Не заданы объекты доступа'
		);

		$objects = array_map('intval', $objects);

		$this->db->prepare('DELETE FROM `access_roles` WHERE `parent_id`=? AND `object_id` IN ('.implode(',',$objects).')');
		$this->db->bind($parent['object_id']);
		if($this->db->simple()===false) return array(
			'status' => false,
			'desc' => 'Ошибка удаления объектов из контейнера роли'
		);

		$this->setAccessObjectsActual(false);
		return array('status' => true);
	}#end function






	/*==============================================================================================
	ФУНКЦИИ: Работа с базой данных
	==============================================================================================*/


	/*
	 * Текущие привелегии доступа не актуальны
	 */
	public function setAccessObjectsActual($status=false){
		$this->xcache->set($this->session_name.'/acl/actual', $status);
		if(!$status){
			$this->objects_loaded = false;
			$this->companies_loaded = false;
			$this->db->update('UPDATE `employers` SET `acl_update`=1');
		}
	}#end function



	/*
	 * Загрузка массивов объектов и организаций из базы данных
	 */
	public function dbLoadAll(){

		#Чтение в массив доступных объектов
		if(!$this->objects_loaded){
			if(!$this->dbLoadObjects()) return false;
		}

		#Чтение в массив доступных организаций
		if(!$this->companies_loaded){
			if(!$this->dbLoadCompanies()) return false;
		}

		if($this->xcache->isEnabled()){
			$this->xcache->set($this->session_name.'/acl/actual', true);
		}

		return true;
	}#end function



	/*
	 * Получение массива объектов из базы данных
	 */
	public function dbLoadObjects(){

		if(
			!$this->xcache->isEnabled()||
			!$this->xcache->exists($this->session_name.'/acl/objects')||
			!$this->xcache->exists($this->session_name.'/acl/object_names')||
			!$this->xcache->get($this->session_name.'/acl/actual')
		){
			$this->object_names = array();
			if(($this->objects = $this->db->selectByKey('object_id', 'SELECT * FROM `access_objects`'))===false) return false;

			#Массив сопоставления имен объектов доступа с ID
			foreach($this->objects as $key=>$value){
				$this->object_names[$value['name']] = $key;
				$this->objects[$key]['namedesc'] = $value['name'].' ('.$value['object_id'].', '.$value['desc'].')';
				$this->objects[$key]['childs'] = array();	#Массив дочерних элементов объекта
				$this->objects[$key]['groups'] = array();	#Массив групп доступа, в которые включен объект
			}

			#Получение данных по дочерним элементам контейнеров (ролей)
			if(($list = $this->db->select('SELECT * FROM `access_roles`'))===false) return false;
			foreach($list as $item){
				#Если объект существует
				if(isset($this->objects[$item['parent_id']]) && isset($this->objects[$item['object_id']])){
					if($this->objects[$item['parent_id']]['type']==ACL_OBJECT_ROLE){
						$this->objects[$item['parent_id']]['childs'][] = $item['object_id'];
					}
				}
			}

			if($this->xcache->isEnabled()){
				$this->xcache->set($this->session_name.'/acl/objects', $this->objects);
				$this->xcache->set($this->session_name.'/acl/object_names', $this->object_names);
			}

		}else{
			$this->objects = $this->xcache->get($this->session_name.'/acl/objects');
			$this->object_names = $this->xcache->get($this->session_name.'/acl/object_names');
		}


		$this->objects_loaded = true;

		return true;
	}#end function



	/*
	 * Получение массива организаций
	 */
	public function dbLoadCompanies(){

		if(
			!$this->xcache->isEnabled() ||
			!$this->xcache->exists($this->session_name.'/acl/companies')||
			!$this->xcache->get($this->session_name.'/acl/actual')
		){

			if( ($this->companies = $this->db->selectByKey('company_id', 'SELECT * FROM `companies`')) === false) return false;
			if(!is_array($this->companies)) return false;

			if($this->xcache->isEnabled()){
				$this->xcache->set($this->session_name.'/acl/companies', $this->companies);
			}

		}else{
			$this->companies = XCache::_get($this->session_name.'/acl/companies');
		}

		#Данные по организациям загружены
		$this->companies_loaded = true;

		return true;
	}#end function



	/*
	 * Получение списка доступов пользователя
	 */
	public function dbLoadUserAccess($employer_id=0, $assoc=false){

		if(empty($employer_id))$employer_id=$this->employer_id;

		$this->db->prepare('SELECT `object_id`,`company_id`,`is_restrict` FROM `employer_access` WHERE `employer_id`=?');
		$this->db->bind($employer_id);

		return $this->db->select(null, ($assoc?MYSQL_ASSOC:MYSQL_NUM));
	}#end function


	/*
	 * Получение расширенного списка доступов пользователя: включая типы объектов
	 */
	public function dbLoadUserAccessEx($employer_id=0){

		if(empty($employer_id))$employer_id=$this->employer_id;
		$this->db->prepare('
			SELECT 
				EA.`id` as `id`,
				EA.`object_id` as `object_id`,
				AO.`type` as `type`,
				EA.`company_id` as `company_id`,
				EA.`is_restrict` 
			FROM `employer_access` as EA 
				INNER JOIN `access_objects` as AO ON AO.`object_id`=EA.`object_id`
			WHERE `employer_id`=?
		');
		$this->db->bind($employer_id);

		return $this->db->select();
	}#end function




	/*
	 * Проверяет существование пользователя с указанным идентификатором
	 */
	public function dbUserExists($employer_id=0){
		if(empty($employer_id)) return false;
		$this->db->prepare('SELECT count(*) FROM `employers` WHERE `employer_id`=? LIMIT 1');
		$this->db->bind($employer_id);
		return ($this->db->result() == 1);
	}#end function



	/*
	 * Возвращает Access Level пользователя
	 */
	public function dbUserAccessLevel($employer_id=0){
		if(empty($employer_id)) return false;
		$this->db->prepare('SELECT `access_level` FROM `employers` WHERE `employer_id`=? LIMIT 1');
		$this->db->bind($employer_id);
		$al = $this->db->result();
		return (empty($al) ? 0: $al);
	}#end function





	/*==============================================================================================
	ФУНКЦИИ: Работа с массивом организаций
	==============================================================================================*/


	/*
	 * Проверяет существование организации с указанным идентификатором
	 */
	public function companyExists($company_id=0){

		#Получение массива организаций
		if($this->companies_loaded==false){
			if(!$this->dbLoadCompanies()) return false;
		}

		if(!isset($this->companies[$company_id])||!is_array($this->companies[$company_id])) return false;

		return $company_id;
	}#end function



	/*
	 * Проверяет статус активности организации с указанным идентификатором
	 * Если организация существует и активна, возвращает TRUE, иначе - FALSE
	 */
	public function companyActive($company_id=0){

		#Проверка существования организации с указанным идентификатором
		if(!$this->companyExists($company_id)) return false;

		return ($this->companies[$company_id]['lock'] == 0 ? true : false);
	}#end function



	/*
	 * Возвращает объект организации
	 */
	public function getCompany($company_id=0){
		#Проверка существования организации с указанным идентификатором
		if(!$this->companyExists($company_id)) return false;
		return  $this->companies[$company_id];
	}#end function



	/*
	 * Возвращает массив Организаций
	 */
	public function getCompanies($companies_ids=null){

		#Чтение в массив доступных организаций
		if(!$this->companies_loaded){
			if(!$this->dbLoadCompanies()) return false;
		}
		if(empty($companies_ids)||!is_array($companies_ids)) return array();
		$result = array();
		foreach($companies_ids as $company_id){
			if($this->companyExists($company_id))$result[]=$this->companies[$company_id];
			
		}

		return $result;
	}#end function






	/*==============================================================================================
	ФУНКЦИИ: Работа с объектами доступа
	==============================================================================================*/



	/*
	 * Возвращает идентификатор объекта по его имени
	 */
	public function getObjectIdFormName($name=''){

		#Чтение в массив доступных объектов
		if(!$this->objects_loaded){
			if(!$this->dbLoadObjects()) return false;
		}

		if(!isset($this->object_names[$name])) return false;
		$object_id = $this->object_names[$name];
		if(!isset($this->objects[$object_id])||!is_array($this->objects[$object_id])) return false;

		return $object_id;
	}#end function



	/*
	 * Возвращает имя объекта по его идентификатору
	 */
	public function getObjectNameFromId($object_id=0){

		#Чтение в массив доступных объектов
		if(!$this->objects_loaded){
			if(!$this->dbLoadObjects()) return false;
		}

		if(!isset($this->objects[$object_id])||!is_array($this->objects[$object_id])) return false;

		return $this->objects[$object_id]['name'];
	}#end function



	/*
	 * Возвращает объект ACL
	 * Функция берет идентификатор объекта ACL $object_id,
	 * представляющий собой именованный или числовой идентификатор.
	 * Проверяет существование объекта ACL и возвращает объект ACL
	 */
	public function getObject($object_id=0){

		#Чтение в массив доступных объектов
		if(!$this->objects_loaded){
			if(!$this->dbLoadObjects()) return false;
		}

		if(!is_numeric($object_id)){
			$object_id = $this->getObjectIdFormName($object_id);
		}
		if(!$object_id) return false;

		if(!isset($this->objects[$object_id])||!is_array($this->objects[$object_id])) return false;

		return $this->objects[$object_id];
	}#end function




	/*
	 * Возвращает массив объектов, удовлетворяющий условиям заданного фильтра
	 */
	public function searchObjects($filter=array(), $fields=array()){

		#Чтение в массив доступных объектов
		if(!$this->objects_loaded){
			if(!$this->dbLoadObjects()) return false;
		}

		#Массив результатов
		$result=array();

		#Просмотр объектов
		foreach($this->objects as $object){

			$success = true;
			#Проверка объекта на соответствие заданным критериям фильтра
			foreach($filter as $key=>$value){
				if($value != $object[$key]) $success = false;
			}

			#Добавление в результаты
			if( $success ) array_push($result, (empty($fields) ? $object:arrayCustomFields($object,$fields)));

		}#Просмотр объектов

		#Возврат результатов
		return $result;
	}#end function





	/*=====================================================================================================================================
	Функции проверки коллизий
	======================================================================================================================================*/


	/*
	 * Проверка коллизий: дочерний объект родителя является его родителем
	 * 
	 * Принимает аргументы:
	 * $owner_id (*) - Идентификатор родительского элемента объекта
	 * $object_id (*) - Идентификатор объекта
	 * 
	 * Результат:
	 * Возвращает TRUE, если в дочерних объектах контейнера $object_id присутствует $owner_id
	 */
	public function haveCollision($owner_id, $object_id){

		if($owner_id == $object_id) return true;

		#Чтение в массив доступных объектов, прочитать не удалось - может быть коллизия
		if(!$this->dbLoadAll()) return true;

		if(!is_numeric($object_id)){
			if( ($object_id = $this->getObjectIdFormName($object_id)) === false) return false; #Объект не существуе, коллизии быть не может
		}

		if(!is_numeric($owner_id)){
			if( ($owner_id = $this->getObjectIdFormName($owner_id)) === false) return false; #Объект не существуе, коллизии быть не может
		}

		$tree = array($owner_id);

		return $this->haveCollisionCalculate($owner_id, $object_id, $tree);
	}#end function



	/*
	 * Проверка коллизий: дочерний объект родителя является его родителем
	 * 
	 * Функция проверяет, является ли объект $object_id родителем объекта $owner_id
	 * Возвращает TRUE, если коллизия найдена, функция рекурсивна
	 */
	private function haveCollisionCalculate($owner_id, $object_id, &$tree){

		if(!isset($this->objects[$object_id]['childs'])) return false;

		#Просмотр дочерних объектов роли
		foreach($this->objects[$object_id]['childs'] as $child_id){

			#Если текущий дочерний объект $object_id, является родителем для $owner_id, коллизия найдена
			if($child_id == $owner_id) return true;

			#Если текущий дочерний объект в свою очередь также является ролью - делаем рекурсивный запрос
			#Перед рекурсивным запросом проверяем, в истории запросов отсутствие коллизий для исключения 
			#замкнутого цикла: родитель имеет в дочерних объектах своего родителя
			if($this->objects[$child_id]['type'] == ACL_OBJECT_ROLE){
				if(array_search($child_id, $tree)===false){
					array_push($tree, $child_id);
					if( $this->haveCollisionCalculate($owner_id, $child_id, $tree) == true )return true;
				}
			}

		}#foreach

		#Коллизии не найдены
		return false;
	}#end function








	/*==============================================================================================
	ФУНКЦИИ: Работа с правами доступа пользователя
	==============================================================================================*/



	/*
	 * Текущие привелегии доступа пользователя не актуальны
	 */
	public function setUserAccessActual($employer_id=0,$status=false){
		$employer_id=intval($employer_id);
		if(empty($employer_id)) $employer_id=$this->employer_id;
		$this->xcache->set($this->session_name.'/acl/'.$employer_id.'/actual', $status);
	}



	/*
	 * Построение массива прав доступа пользователя
	 * 
	 * $employer_id - идентификатор пользователя
	 */
	public function getUserPrivs($employer_id=0, $from_db=false){

		#Загрузка массива объектов из базы данных
		if(!$this->dbLoadAll()) return false;

		if(empty($employer_id)){
			$employer_id=$this->employer_id;
		}

		if(
			$from_db ||
			!$this->xcache->isEnabled()||
			!$this->xcache->exists($this->session_name.'/acl/'.$employer_id.'/access')||
			!$this->xcache->get($this->session_name.'/acl/'.$employer_id.'/actual')
		){
			$explain 		= array(); #Массив, объясняющий права доступа пользователя
			$user_objects	= array(); #Масисв объектов ACL, к которым пользователь имеет доступ
			$access			= array(); #Результирующий массив объектов ACL к которым пользователь имеет доступ, сгрупированный по организациям

			$access_level = $this->dbUserAccessLevel($employer_id);

			#BEGIN
			array_push($explain, 'i ACL BEGIN FOR employer_id=['.$employer_id.'],  access_level=['.$access_level.']');

			#Назначение пользователю прав доступа к объектам из базы данных
			array_push($explain, 'i STEP 1: Назначение пользователю прав доступа к объектам из базы данных');
			if(($dbobjects = $this->dbLoadUserAccess($employer_id)) !== false){
				$this->addObjectsToList($access_level, $user_objects, $dbobjects, $explain, '');
			}

			#Фильтрация и группировка по организациям объектов доступа, получение результирующего массива
			array_push($explain, 'i STEP 2: Фильтрация и группировка по организациям объектов доступа, получение результирующего массива');
			$access = $this->getFinalUserAccess($access_level, $user_objects, $explain, '');

			#END
			array_push($explain, 'i ACL END');

			#Сохранение в XCache
			if($this->xcache->isEnabled()){
				$this->xcache->set($this->session_name.'/acl/'.$employer_id.'/access', $access);
				$this->xcache->set($this->session_name.'/acl/'.$employer_id.'/actual', true);
			}
		}else{
			$access = $this->xcache->get($this->session_name.'/acl/'.$employer_id.'/access');
			$explain = null;
		}

		#Возвращаем массив прав доступа пользователя
		return array(
			'employer_id'	=> $employer_id,
			'explain'		=> $explain,
			'access'		=> $access,
		);

	}#end function



	/*
	 * Фильтрация и группировка по организациям объектов доступа, получение результирующего массива доступов пользователя
	 * 
	 * $user_objects - Масисв объектов ACL, к которым пользователь имеет доступ или установлен запрет на доступ
	 * $explain - ссылка на массив объяснения доступа
	 * $comment - комментарий для EXPLAIN ACCESS
	 */
	private function getFinalUserAccess($access_level, $user_objects=array(), &$explain, $comment=''){

		$result = array(
			'c0' => array()
		);

		#Просмотриваем все организации
		foreach($this->companies as $company){
			$result['c'.$company['company_id']] = array();
		}#Просмотриваем все организации

		#Если не задан массив объектов доступа 
		#Считаем, что пользователь не наделен никакими правами
		if(empty($user_objects)) return $result;


		#Просмотр всех объектов, указанных в массиве
		foreach($user_objects as $item){

			#Фильтр запретов - установлен restrict на объект
			if($item[2] != 0) continue;

			#Фильтр организации - неизвестная организация
			if(!isset($result['c'.$item[1]])) continue;


			#Получение объекта ACL
			$object = $this->getObject($item[0]);
			if(!is_array($object)) continue;
			$object_id = $object['object_id'];

			#Добавляем объект в список доступов пользователя
			$result['c'.$item[1]][] = $object_id;

		}#Просмотр всех объектов, указанных в массиве

		return $result;
	}#end function






	/* 
	 * Добавляет объекты доступа в основной список из массива 
	 * 
	 * Функция берет исходный линейный массив $objects,
	 * представляющий собой смешанный набор идентификаторов и имен объектов доступа,
	 * проверяет существование объекта доступа, 
	 * конвертирует именованный идентификатор объекта доступа в числовой идентификатор
	 * и добавляет запись в массив $list, если таковая там отсутствует
	 * 
	 * $list - основной список объектов доступа
	 * $objects - список идентификаторов объектов, которые требуется включить в основной список
	 * $explain - ссылка на массив объяснения доступа
	 * $comment - комментарий для EXPLAIN ACCESS
	 * 
	 * Структура записи основного списка групп доступа
	 * 'uID'=>array(
	 * 	1,		//id объекта
	 * 	0,		//id организации
	 * 	1		//признак запрета
	 * )
	 */
	private function addObjectsToList($access_level, &$list, $objects=array(), &$explain, $comment=''){

		#Загрузка массива объектов из базы данных
		if(!$this->dbLoadAll()) return false;

		#Просмотр добавляемых объектов
		foreach($objects as $item){

			#Если объект доступа задан не массивом, преобразуем в массив, считая что restrict = false и объект доступен для любой организации
			if(!is_array($item)) $item = array($item, 0, 0);

			if(!isset($item[0])){
				array_push($explain,'? Объект доступа задан пустым массивом'.$comment);
				continue;
			}
			$ident		= $item[0];
			$company_id	= (isset($item[1]) ? $item[1] : 0);
			$restrict	= (isset($item[2]) ? $item[2] : 0);
			$object = $this->getObject($ident); #Получение объекта

			#Объект не найден
			if(empty($object) || !is_array($object)){
				array_push($explain,'? Не найден объект доступа по идентификатору ['.$ident.']'.$comment);
				continue;
			}

			if(empty($restrict) && !empty($object['is_lock'])){
				array_push($explain,'- к объекту ID:'.$ident.' ['.$object['name'].'], объект заблокирован'.$comment);
				continue;
			}

			if($object['min_access_level'] > $access_level){
				array_push($explain, '- к объекту ID:'.$object['object_id'].' ['.$object['name'].'], низкий AL, требуется AL=['.$object['min_access_level'].']'.$comment);
				continue;
			}

			$ident = $object['object_id'];

			#Если текущий объект является страницей
			if($object['type'] == ACL_OBJECT_PAGE){
				#Добавляем объект в список доступа
				$this->addObjectToList($access_level, $list, $ident, 0, $restrict, $explain, $comment);
			}
			#Если текущий объект является контейнером (ролью)
			else if($object['type'] == ACL_OBJECT_ROLE){

				array_push($explain,'i Добавляется объект типа роль ID:'.$ident.' ['.$object['name'].'] для организации ID:'.$company_id.$comment);

				#Если текущая роль доступна во всех организациях
				if($company_id == 0 || $object['for_all_companies']==1){

					#Добавляем объект - роль в список доступа
					if($this->addObjectToList($access_level, $list, $ident, 0, $restrict, $explain, $comment) === false){
						$tree = array($ident);
						$this->calculateRoleTree($access_level, $list, $tree, $ident, 0, $restrict, $explain, $comment.' -> ');
					}

					/*
					#Просмотриваем все организации
					foreach($this->companies as $company){

						$cid = $company['company_id'];

						#Добавляем объект - роль в список доступа
						if($this->addObjectToList($access_level, $list, $ident, $cid, $restrict, $explain, $comment) === false){
							$tree = array($ident);
							$this->calculateRoleTree($access_level, $list, $tree, $ident, $cid, $restrict, $explain, $comment.' -> ');
						}

					}#Просмотриваем все организации
					*/
				}
				#Роль доступна только в определенной организации
				else{
					#Добавляем объект - роль в список доступа
					if($this->addObjectToList($access_level, $list, $ident, $company_id, $restrict, $explain, $comment) === false){
						$tree = array($ident);
						$this->calculateRoleTree($access_level, $list, $tree, $ident, $company_id, $restrict, $explain, $comment.' -> ');
					}
				}

			}
			#Если объект не является контейнером роли
			else{

				#Если текущая роль доступна во всех организациях
				if($company_id == 0 || $object['for_all_companies']==1){

					#Добавляем объект в список доступа - все организации
					$this->addObjectToList($access_level, $list, $ident, 0, $restrict, $explain, $comment);

					/*
					#Просмотриваем все организации
					foreach($this->companies as $company){

						$cid = $company['company_id'];
						#Добавляем объект в список доступа
						$this->addObjectToList($access_level, $list, $ident, $cid, $restrict, $explain, $comment);

					}#Просмотриваем все организации
					*/
				}
				#Роль доступна только в определенной организации
				else{
					#Добавляем объект в список доступа
					$this->addObjectToList($access_level, $list, $ident, $company_id, $restrict, $explain, $comment);
				}

			}#Если объект не является контейнером роли

		}#Просмотр добавляемых объектов

		return $list;
	}#end function




	/* 
	 * Добавляет один ACL объект в основной список
	 * 
	 * $list - основной список объектов доступа
	 * $ident - идентификатор объекта, который требуется включить в основной список
	 * $company_id - организация, для которой назначается доступ
	 * $restrict - запрет доступа
	 * $explain - ссылка на массив объяснения доступа
	 * $comment - комментарий для EXPLAIN ACCESS
	 * 
	 * Структура записи основного списка объектов доступа
	 * 'uID'=>array(
	 * 	1,		//id объекта
	 * 	0,		//id организации
	 * 	1		//признак запрета
	 * )
	 * Функция возвращает TRUE, если производится повторное добавление объекта, 
	 * во всех остальных случаях функция возвращает FALSE
	 */
	private function addObjectToList($access_level, &$list, $ident, $company_id, $restrict, &$explain, $comment=''){

		$object = $this->getObject($ident); #Получение объекта
		if(!is_array($object)){
			array_push($explain, '? Объект ID:'.$ident.' не найден'.$comment);
			return false;
		}

		#Объект может использоваться только для всех организаций
		if($object['for_all_companies']==1){
			if($company_id > 0){
				array_push($explain, 'i Объект ID:'.$ident.' используется только для всех организаций, организация ID:'.$company_id.' изменена на все организации '.$comment);
			}
			$company_id = 0;
		}

		if(empty($company_id)){
			$company = array(
				'company_id'	=> 0,
				'full_name'		=> 'Все организации',
				'is_lock'		=> 0
			);
		}else{
			if(empty($company_id) || !$this->companyExists($company_id)){
				array_push($explain, '- к объекту ID:'.$ident.' ['.$object['name'].'] не назначен в организации ID:'.$company_id.', т.к. организация не найдена'.$comment);
				return false;
			}
			$company = $this->companies[$company_id];
		}

		if(!empty($object['is_lock'])){
			array_push($explain, '- к объекту ID:'.$ident.' ['.$object['name'].'] в организации ID:'.$company_id.' ['.$company['full_name'].'], объект заблокирован'.$comment);
			return false;
		}

		if($object['min_access_level'] > $access_level){
			array_push($explain, '- к объекту ID:'.$object['object_id'].' ['.$object['name'].'], низкий AL, требуется AL=['.$object['min_access_level'].']'.$comment);
			return false;
		}

		$indx = 'u'.$ident.'c'.$company_id;

		#Объект не задан в основном массиве
		if(!isset($list[$indx])){
			$list[$indx] = array(
				$ident,			#ID Объекта
				$company_id,	#ID Организации
				$restrict		#Признак запрета
			);
			if(!$restrict)
				array_push($explain, '+ к объекту ID:'.$ident.' ['.$this->getObjectNameFromId($ident).'] в организации ID:'.$company_id.' ['.$company['full_name'].']'.$comment);
			else
				array_push($explain, '- запрет к объекту ID:'.$ident.' ['.$this->getObjectNameFromId($ident).'] в организации ID:'.$company_id.' ['.$company['full_name'].']'.$comment);
		}
		#Объект уже задан в основном массиве
		else{
			#Доступ к объекту еще не запрещен, но задан запрет
			if(!$list[$indx][2] && $restrict){
				$list[$indx][2] = $restrict;
				array_push($explain, '- запрет к объекту ID:'.$ident.' ['.$this->getObjectNameFromId($ident).'] в организации ID:'.$company_id.' ['.$company['full_name'].']'.$comment);
			}else{
				//array_push($explain, '! повторно к объекту ID:'.$ident.' ['.$this->getObjectNameFromId($ident).'] в организации ID:'.$company_id.' ['.$company['full_name'].']'.$comment);
				return true;
			}
		}

		return false;
	}#end function




	/*
	 * Добавление элемента в массив доступа на основании дерева доступа
	 * 
	 * $list - основной список объектов доступа
	 * $tree - ссылка на массив дерева
	 * $object_id - родительский объект
	 * $company_id - организация, для которой назначается доступ
	 * $restrict - запрет доступа
	 * $explain - ссылка на массив объяснения доступа
	 * $comment - комментарий для EXPLAIN ACCESS
	 * 
	 * Функция работает рекурсивно, возврат значений не предусмотрен
	 * результаты работы функции записываются непосредственно в массивы $access и $tree
	 */
	private function calculateRoleTree($access_level, &$list, &$tree, $ident, $company_id, $restrict, &$explain, $comment){

		$object = $this->getObject($ident);
		if(!is_array($object)){
			array_push($explain, '? Объект ID:'.$ident.' не найден'.$comment);
			return;
		}

		#Просмотр дочерних объектов роли
		foreach($object['childs'] as $child_id){

			$child_object = $this->getObject($child_id);
			if(!is_array($object)){
				array_push($explain, '? Объект ID:'.$child_id.' не найден'.$comment.'/'.$object['name']);
				return;
			}

			#Если текущий дочерний объект в свою очередь также является ролью - делаем рекурсивный запрос
			#Перед рекурсивным запросом проверяем, в истории запросов отсутствие коллизий для исключения 
			#замкнутого цикла: родитель имеет в дочерних объектах своего родителя
			if($child_object['type'] == ACL_OBJECT_ROLE){
				if(!in_array($child_id, $tree)){
					#Если в массиве доступов к объектам еще нет текущего объекта - добавляем его
					$this->addObjectToList($access_level, $list, $child_id, $company_id, $restrict, $explain, $comment.'/'.$object['name']);
					array_push($tree, $child_id);
					$this->calculateRoleTree($access_level, $list, $tree, $child_id,  $company_id, $restrict, $explain, $comment.'/'.$ident);
				}
			}else{
				$this->addObjectToList($access_level, $list, $child_id, $company_id, $restrict, $explain, $comment.'/'.$object['name']);
			}

		}#Просмотр дочерних объектов роли

		return;
	}#end function












	/*==============================================================================================
	ФУНКЦИИ: Проверка прав пользователя
	==============================================================================================*/



	/*
	 * Проверка, является ли пользователь - суперадминистратором
	 */
	public function isSuper($employer_id=0){
		$employer_id = intval($employer_id);
		if(empty($employer_id)) $employer_id=$this->employer_id;
		if(empty($this->super_users)) return false;
		if(!is_array($this->super_users)) return ($employer_id == $this->super_users);
		return in_array($employer_id, $this->super_users);
	}#end function



	/*
	 * Проверяет доступ текущего пользователя к ACL объекту в указанной орагнизации
	 * 
	 * $object_id - идентификатор объекта ACL
	 * $company_id - идентификатор организации, если не задан, используется текущая активная организация
	 * $ignore_company_lock - признак игнорирования признака блокировки организации
	 * $ignore_object_lock - признак игнорирования признака блокировки объекта
	 * 
	 * Возвращает TRUE, если пользователю разрешен доступ к указанному объекту
	*/
	public function checkAccess($object_id=0, $company_id=0, $ignore_company_lock=true, $ignore_object_lock=false, $ignore_super=false){

		#Загрузка массива объектов из базы данных
		if(!$this->dbLoadAll()) return false;

		#Идентификатор объекта
		if(empty($object_id)) return false;

		#суперадминистратор
		if(!$ignore_super && $this->is_super) return true;

		#Идентификатор текущей организации
		$company_id = intval($company_id);
		if(!empty($company_id)){
			if(!$this->companyExists($company_id)) return false;
			if(!empty($this->companies[$company_id]['is_lock']) && !$ignore_company_lock) return false;
		}

		$object = $this->getObject($object_id);
		if(!is_array($object)) return false;
		if(!$ignore_object_lock && $object['is_lock']!=0) return false; #Доступ к данному объекту был ограничен
		if($this->access_level < $object['min_access_level']) return false; #Уровень доступа пользователя ниже минимально допустимого для данного объекта

		if(($userPrivs = $this->getUserPrivs())===false) return false;
		$access = $userPrivs['access'];

		if(is_array($access['c0'])){
			if(in_array($object['object_id'], $access['c0'])) return true;
		}
		if(empty($access['c'.$company_id]) || !is_array($access['c'.$company_id])) return false; #Недостаточно прав

		#Проверка наличия доступа пользователя к объекту в рамках выбранной организации
		if(!in_array($object['object_id'], $access['c'.$company_id])) return false; #Недостаточно прав

		return true;
	}#end function





	/*
	 * Возвращает список идентификаторов организаций, в которых пользователь имеет доступ к указанному объекту
	 * 
	 * $object_id - идентификатор объекта ACL
	 * $ignore_company_lock - признак игнорирования признака блокировки организации
	 * 
	 * Возвращает TRUE, если пользователю разрешен доступ к указанному объекту
	*/
	public function getAllowedCompaniesForObject($object_id=0, $ignore_company_lock=true, $ignore_super=false){

		#Загрузка массива объектов из базы данных
		if(!$this->dbLoadAll()) return false;

		#Идентификатор объекта
		if(empty($object_id)) return false;

		#суперадминистратор
		if(!$ignore_super && $this->is_super) return array_keys($this->companies);

		$object = $this->getObject($object_id);
		if(!is_array($object)) return false;
		if($object['is_lock']!=0) return array(); #Доступ к данному объекту был ограничен
		if($this->access_level < $object['min_access_level']) return array(); #Уровень доступа пользователя ниже минимально допустимого для данного объекта

		if(($userPrivs = $this->getUserPrivs())===false) return false;
		$access = $userPrivs['access'];
		$result = array();

		#Все организации
		if(is_array($access['c0'])){
			if(in_array($object['object_id'], $access['c0'])){
				return array_keys($this->companies);
			}
		}


		#Просмотр организаций
		foreach($this->companies as $company_id=>$company){
			if(!empty($company['is_lock']) && !$ignore_company_lock) continue;
			if(empty($access['c'.$company_id]) || !is_array($access['c'.$company_id])) continue;
			if(!in_array($object['object_id'], $access['c'.$company_id])) continue;
			$result[] = $company_id;
		}

		return $result;
	}#end function






	/*==============================================================================================
	ФУНКЦИИ: Работа с меню
	==============================================================================================*/


	/*
	 * Построение меню для пользователя
	 */
	public function getUserMenu($menu_id=0, $ignore_super=false){

		$this->db->prepare('SELECT * FROM `menu_map` WHERE `menu_id`=?');
		$this->db->bind(intval($menu_id));
		if(($list = $this->db->select())===false) return false;

		$menu = array();
		$filter = array();
		$item_ids = array();
		$childs = array();

		foreach($list as $i=>$item){
			$childs[$item['parent_id']] = true;
			if(!empty($item['is_lock'])) continue;
			if($ignore_super || !$this->is_super){
				if(!empty($item['access_object_id']) && !$this->checkAccess($item['access_object_id'],0,true,false,$ignore_super)) continue;
			}
			$filter[]=$item;
			$item_ids[$item['item_id']] = $item['item_id'];
		}

		foreach($filter as $item){
			if(!empty($item['parent_id']) && !isset($item_ids[$item['parent_id']])) continue;
			if($item['is_folder'] && !isset($childs[$item['item_id']])) continue;
			$item['is_folder'] = ($item['is_folder'] ? true : false);
			$menu[]=$item;
		}

		return $menu;
	}#end function










}#end class

?>