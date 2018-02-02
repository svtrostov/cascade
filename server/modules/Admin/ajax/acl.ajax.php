<?php
/*==================================================================================================
Описание: Обработка AJAX запросов
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');

if(!$uaccess->checkAccess('admin.acl.moderate', 0)){
	return Ajax::_responseError('Ошибка выполнения','Недостаточно прав для работы с ACL');
}

$request_action = Request::_get('action');

#Обработка AJAX запроса, в зависимости от запрошенного действия
switch($request_action){


	/*******************************************************************
	 * Изменение объекта ACL
	 ******************************************************************/
	case 'object.new':

		$type				= $request->getEnum('type',array('1','2','3'), 0);
		$min_access_level	= $request->getId('min_access_level', 0);
		$is_lock			= $request->getBool('is_lock', false);
		$name				= $request->getStr('name', '');
		$desc				= $request->getStr('desc', '');
		$for_all_companies	= $request->getBool('for_all_companies', false);

		if(empty($name)) return Ajax::_responseError('Ошибка выполнения','Не задано имя объекта');
		if(empty($desc)) return Ajax::_responseError('Ошибка выполнения','Не задано описание объекта');
		if(empty($type)) return Ajax::_responseError('Ошибка выполнения','Не задан тип объекта');

		$db = Database::getInstance('main');
		$db->transaction();

		$result = $uaccess->newObject(array(
			'name' => $name,
			'desc' => $desc,
			'type' => $type,
			'is_lock' => ($is_lock ? '1':'0'),
			'min_access_level' => $min_access_level,
			'for_all_companies'=> ($for_all_companies ? '1':'0')
		));

		if(!$result['status']){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения',$result['desc']);
		}

		Protocol::_add(array(
			'action_name'	=> $request_action,
			'acl_id'		=> $uaccess->getObjectIdFormName('admin.acl.moderate'),
			'acl_name'		=> 'admin.acl.moderate',
			'primary_type'	=> 'aclobject',
			'primary_id'	=> $result['object_id'],
			'description'	=> 'Создан ACL объект ID:'.$result['object_id'],
			'value'			=> array(
				'name' => $name,
				'desc' => $desc,
				'type' => $type,
				'is_lock' => ($is_lock ? '1':'0'),
				'min_access_level' => $min_access_level,
				'for_all_companies'=> ($for_all_companies ? '1':'0')
			)
		));

		//Успешно
		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			'aobjects' => $uaccess->searchObjects(),
			'sobject' => $result['object_id']
		));

	break; #Изменение объекта ACL





	/*******************************************************************
	 * Изменение объекта ACL
	 ******************************************************************/
	case 'object.edit':

		$object_id			= $request->getId('object_id', 0);
		$type				= $request->getId('type', 0);
		$min_access_level	= $request->getId('min_access_level', 0);
		$is_lock			= $request->getBool('is_lock', false);
		$name				= $request->getStr('name', '');
		$desc				= $request->getStr('desc', '');
		$for_all_companies	= $request->getBool('for_all_companies', false);


		$db = Database::getInstance('main');
		$db->transaction();

		$result = $uaccess->changeObject(
			$object_id,
			array(
				'name' => $name,
				'desc' => $desc,
				'is_lock' => ($is_lock ? '1':'0'),
				'min_access_level' => $min_access_level,
				'for_all_companies'=> ($for_all_companies ? '1':'0')
		));

		if(!$result['status']){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения',$result['desc']);
		}

		Protocol::_add(array(
			'action_name'	=> $request_action,
			'acl_id'		=> $uaccess->getObjectIdFormName('admin.acl.moderate'),
			'acl_name'		=> 'admin.acl.moderate',
			'primary_type'	=> 'aclobject',
			'primary_id'	=> $object_id,
			'description'	=> 'Изменен ACL объект ID:'.$object_id,
			'value'			=> array(
				'name' => $name,
				'desc' => $desc,
				'type' => $type,
				'is_lock' => ($is_lock ? '1':'0'),
				'min_access_level' => $min_access_level,
				'for_all_companies'=> ($for_all_companies ? '1':'0')
			)
		));

		//Успешно
		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			'aobjects' => $uaccess->searchObjects(),
			'sobject' => $object_id
		));

	break; #Изменение объекта ACL






	/*******************************************************************
	 * Удаление объекта ACL
	 ******************************************************************/
	case 'object.delete':

		$object_id = $request->getId('object_id', 0);

		$db = Database::getInstance('main');
		$db->transaction();

		$result = $uaccess->deleteObject($object_id);

		if(!$result['status']){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения',$result['desc']);
		}

		Protocol::_add(array(
			'action_name'	=> $request_action,
			'acl_id'		=> $uaccess->getObjectIdFormName('admin.acl.moderate'),
			'acl_name'		=> 'admin.acl.moderate',
			'primary_type'	=> 'aclobject',
			'primary_id'	=> $result['object_id'],
			'description'	=> 'Удален ACL объект ID:'.$object_id,
			'value'			=> array()
		));

		//Успешно
		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			'aobjects' => $uaccess->searchObjects()
		));

	break; #Удаление объекта ACL





	/*******************************************************************
	 * Добавление объектов доступа в контейнер роли
	 ******************************************************************/
	case 'role.include':

		$role_id = $request->getId('role_id', 0);
		$objects = $request->getArray('objects', 0);
		$objects = array_map('intval', $objects);

		$db = Database::getInstance('main');
		$db->transaction();

		$result = $uaccess->containerInclude($role_id, $objects);

		if(!$result['status']){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения',$result['desc']);
		}

		Protocol::_add(array(
			'action_name'	=> $request_action,
			'acl_id'		=> $uaccess->getObjectIdFormName('admin.acl.moderate'),
			'acl_name'		=> 'admin.acl.moderate',
			'primary_type'	=> 'aclobject',
			'primary_id'	=> $role_id,
			'description'	=> 'В роль добавлены объекты',
			'value'			=> $objects
		));

		//Успешно
		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			'aobjects' => $uaccess->searchObjects()
		));

	break; #Удаление объекта ACL





	/*******************************************************************
	 * Удаление объектов доступа из контейнера роли
	 ******************************************************************/
	case 'role.exclude':

		$role_id = $request->getId('role_id', 0);
		$objects = $request->getArray('objects', 0);
		$objects = array_map('intval', $objects);

		$db = Database::getInstance('main');
		$db->transaction();

		$result = $uaccess->containerExclude($role_id, $objects);

		if(!$result['status']){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения',$result['desc']);
		}

		Protocol::_add(array(
			'action_name'	=> $request_action,
			'acl_id'		=> $uaccess->getObjectIdFormName('admin.acl.moderate'),
			'acl_name'		=> 'admin.acl.moderate',
			'primary_type'	=> 'aclobject',
			'primary_id'	=> $role_id,
			'description'	=> 'Из роли исключены объекты',
			'value'			=> $objects
		));

		//Успешно
		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			'aobjects' => $uaccess->searchObjects()
		));

	break; #Удаление объекта ACL






	/*******************************************************************
	 * Получение списка объектов доступа сотрудника
	 ******************************************************************/
	case 'employer.access.get':

		$employer_id = $request->getId('employer_id', 0);
		if(empty($employer_id)) return Ajax::_responseError('Ошибка выполнения','Идентификатор сотрудника задан неверно');

		$db = Database::getInstance('main');
		$db->transaction();

		if(($result = $uaccess->dbLoadUserAccessEx($employer_id))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Ошибка получения списка объектов доступа сотрудника');
		}

		//Успешно
		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			'employer_id' => $employer_id,
			'eaccess' => $result
		));

	break; #Получение списка объектов доступа сотрудника





	/*******************************************************************
	 * Получение итогового доступа сотрудника
	 ******************************************************************/
	case 'employer.privs.get':

		$employer_id = $request->getId('employer_id', 0);
		if(empty($employer_id)) return Ajax::_responseError('Ошибка выполнения','Идентификатор сотрудника задан неверно');
		if(!$uaccess->dbUserExists($employer_id)) return Ajax::_responseError('Ошибка выполнения','Сотрудник с указанным идентификатором не существует');

		$db = Database::getInstance('main');
		$db->transaction();

		if(($result = $uaccess->getUserPrivs($employer_id, true))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Ошибка получения списка результирующего доступа сотрудника');
		}

		//Успешно
		$db->commit();

		#Выполнено успешно
		return Ajax::_setData($result);

	break; #Получение списка объектов доступа сотрудника







	/*******************************************************************
	 * Действия с набором объектов доступа сотрудника
	 ******************************************************************/
	case 'employer.access.action':

		$type = $request->getStr('type', '');
		$value = $request->getId('value', 0);
		$employer_id = $request->getId('employer_id', 0);
		$acl_objects = $request->getArray('objects', array());
		if(empty($employer_id)) return Ajax::_responseError('Ошибка выполнения','Не задан идентификатор сотрудника');
		if(!$uaccess->dbUserExists($employer_id)) return Ajax::_responseError('Ошибка выполнения','Сотрудник с указанным идентификатором не существует');
		$acl_objects = array_map('intval', $acl_objects);
		if(empty($acl_objects)) return Ajax::_responseError('Ошибка выполнения','Не заданы объекты, над которыми выполняется действие');
		$objects = implode(',',$acl_objects);

		$prepare = '';
		$bind = null;
		$action_description = '';

		switch($type){
			case 'restrict':
				$sql = 'UPDATE `employer_access` SET `is_restrict`='.($value?'1':'0').' WHERE `id` IN ('.$objects.') AND `employer_id`='.$employer_id;
				$action_description = 'Сотруднику '.($value?'блокирован доступ':'снята блокировка доступа').' к объектам ACL';
			break;
			case 'company':
				$sql = 'UPDATE `employer_access` SET `company_id`='.$value.' WHERE `id` IN ('.$objects.') AND `employer_id`='.$employer_id;
				$action_description = 'Сотруднику установлен доступ к объектам ACL в рамках организации ID:'.$value;
			break;
			case 'delete':
				$action_description = 'Сотруднику удален доступ к объектам ACL';
				$sql = 'DELETE FROM `employer_access` WHERE `id` IN ('.$objects.') AND `employer_id`='.$employer_id;
			break;
			default:
				return Ajax::_responseError('Ошибка выполнения','Указано неизвестное действие');
		}

		$db = Database::getInstance('main');
		$db->transaction();

		if($db->simple($sql)===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Ошибка во время обновления объектов доступа сотрудника');
		}

		Protocol::_add(array(
			'action_name'	=> $request_action,
			'acl_id'		=> $uaccess->getObjectIdFormName('admin.acl.moderate'),
			'acl_name'		=> 'admin.acl.moderate',
			'primary_type'	=> 'employer',
			'primary_id'	=> $employer_id,
			'description'	=> $action_description,
			'value'			=> $acl_objects
		));

		//Успешно
		$db->commit();

		$uaccess->setUserAccessActual($employer_id, false);

		if(($result = $uaccess->dbLoadUserAccessEx($employer_id))===false){
			return Ajax::_responseError('Ошибка выполнения','Ошибка получения списка объектов доступа сотрудника');
		}

		#Выполнено успешно
		return Ajax::_setData(array(
			'employer_id' => $employer_id,
			'eaccess' => $result
		));

	break; #Действия с набором объектов доступа сотрудника






	/*******************************************************************
	 * Добавление сотруднику объектов доступа
	 ******************************************************************/
	case 'employer.access.add':

		$employer_id = $request->getId('employer_id', 0);
		$object_ids = $request->getArray('o', array());
		$company_ids = $request->getArray('c', array());
		$restricted = $request->getArray('r', array());

		if(empty($employer_id)) return Ajax::_responseError('Ошибка выполнения','Не задан идентификатор сотрудника');
		if(!$uaccess->dbUserExists($employer_id)) return Ajax::_responseError('Ошибка выполнения','Сотрудник с указанным идентификатором не существует');

		$object_ids = array_map('intval', $object_ids);
		$company_ids = array_map('intval', $company_ids);
		$restricted = array_map('intval', $restricted);

		if(
			empty($object_ids) ||
			count($object_ids) != count($company_ids) ||
			count($object_ids) != count($restricted)
		){
			return Ajax::_responseError('Ошибка выполнения','Некорректно заданы входные данные');
		}

		for($i=0;$i<count($object_ids);$i++){
			if(
				!$uaccess->getObject($object_ids[$i])||
				($company_ids[$i]!=0 && !$uaccess->companyExists($company_ids[$i]))
			){
				return Ajax::_responseError('Ошибка выполнения','Некорректно заданы входные данные: ('.$object_ids[$i].','.$company_ids[$i].')');
			}
			$restricted[$i]=($restricted[$i]==0?0:1);
		}

		$db = Database::getInstance('main');
		$db->transaction();

		for($i=0;$i<count($object_ids);$i++){
			$db->prepare('INSERT INTO `employer_access` (`employer_id`,`company_id`,`object_id`,`is_restrict`) VALUES (?,?,?,?)');
			$db->bind($employer_id);
			$db->bind($company_ids[$i]);
			$db->bind($object_ids[$i]);
			$db->bind($restricted[$i]);
			if($db->simple()===false){
				$db->rollback();
				return Ajax::_responseError('Ошибка выполнения','Ошибка во время добавления сотруднику объектов доступа');
			}
			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('admin.acl.moderate'),
				'acl_name'		=> 'admin.acl.moderate',
				'primary_type'	=> 'employer',
				'primary_id'	=> $employer_id,
				'secondary_type'=> 'aclobject',
				'secondary_id'	=> $object_ids[$i],
				'description'	=> 'Сотруднику добавлен доступа к ACL объекту ID:'.$object_ids[$i].' в организации ID:'.$company_ids[$i].', запрет='.$restricted[$i],
				'value'			=> array()
			));
		}


		//Успешно
		$db->commit();

		$uaccess->setUserAccessActual($employer_id, false);

		if(($result = $uaccess->dbLoadUserAccessEx($employer_id))===false){
			return Ajax::_responseError('Ошибка выполнения','Ошибка получения списка объектов доступа сотрудника');
		}

		#Выполнено успешно
		return Ajax::_setData(array(
			'employer_id' => $employer_id,
			'eaccess' => $result
		));

	break; #Добавление сотруднику объектов доступа





	default:
	Ajax::_responseError('/main/ajax/acl','Не найден обработчик для: '.Request::_get('action'));


}

?>