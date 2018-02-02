<?php
/*==================================================================================================
Описание: Обработка AJAX запросов
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');


$request_action = Request::_get('action');

LABEL_IRESOURCES_AXCONTROLLER_START:

#Обработка AJAX запроса, в зависимости от запрошенного действия
switch($request_action){



	/*******************************************************************
	 * Список информационных ресурсов
	 ******************************************************************/
	case 'iresources.list':

		$fields = $request->getArray('fields', null);
		$admin_iresource = new Admin_IResource();
		return Ajax::_setData(array(
			'iresources' => $admin_iresource->getIResourcesList(0, $fields)
		));

	break; #Список информационных ресурсов





	/*******************************************************************
	 * Добавление информационного ресурса
	 ******************************************************************/
	case 'iresource.new':

		if(!$uaccess->checkAccess('iresources.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять информационные ресурсы');
		}

		$company_id		= $request->getId('company_id', 0);
		$post_uid		= $request->getId('post_uid', 0);
		$is_lock		= $request->getBool('is_lock', false);
		$short_name		= trim($request->getStr('short_name', ''));
		$full_name		= trim($request->getStr('full_name', ''));
		$description	= trim($request->getStr('description', ''));
		$location		= trim($request->getStr('location', ''));
		$worker_group	= $request->getId('worker_group', 0);
		$techinfo		= trim($request->getStr('techinfo', ''));
		$igroup_id		= $request->getId('igroup_id', 0);


		if(empty($full_name)||empty($short_name)) return Ajax::_responseError('Ошибка выполнения','Не задано название информационного ресурса');

		$organization = new Admin_Organization();
		$admin_iresource = new Admin_IResource();
		$admin_employers = new Admin_Employers();
		if($company_id>0 && !$organization->companyExists($company_id)) return Ajax::_responseError('Ошибка выполнения','Выбранная организация не существует');
		if($post_uid>0 && !$organization->postUIDExists($post_uid, $company_id)) return Ajax::_responseError('Ошибка выполнения','Выбранная должность не существует');
		if($worker_group>0 && !$admin_employers->groupExists($worker_group)) return Ajax::_responseError('Ошибка выполнения','Группа исполнителей не существует');

		if(($iresource_id = $admin_iresource->iresourceNew(array(
			'company_id'		=> $company_id,
			'post_uid'			=> $post_uid,
			'is_lock' 			=> (!$is_lock ? 0 : 1),
			'short_name'		=> $short_name,
			'full_name'			=> $full_name,
			'description'		=> $description,
			'location'			=> $location,
			'techinfo'			=> $techinfo,
			'iresource_group'	=> $igroup_id,
			'worker_group'		=> $worker_group
		)))===false){
			return Ajax::_responseError('Ошибка выполнения','Ошибка добавления информационного ресурса');
		}

		Protocol::_add(array(
			'action_name'	=> $request_action,
			'acl_id'		=> $uaccess->getObjectIdFormName('iresources.edit'),
			'acl_name'		=> 'iresources.edit',
			'primary_type'	=> 'iresource',
			'primary_id'	=> $iresource_id,
			'description'	=> 'Создан информационный ресурс',
			'value'	=> array(
				'company_id'		=> $company_id,
				'post_uid'			=> $post_uid,
				'is_lock' 			=> (!$is_lock ? 0 : 1),
				'short_name'		=> $short_name,
				'full_name'			=> $full_name,
				'description'		=> $description,
				'location'			=> $location,
				'techinfo'			=> $techinfo,
				'iresource_group'	=> $igroup_id,
				'worker_group'		=> $worker_group
			)
		));

		#Выполнено успешно
		return Ajax::_setData(array(
			'iresource_id'	=> $iresource_id,
			'iresource'		=> $admin_iresource->getIResourcesListEx($iresource_id,null,true)
		));

	break; #Добавление информационного ресурса





	/*******************************************************************
	 * Редактирование информационного ресурса
	 ******************************************************************/
	case 'iresource.edit':

		if(!$uaccess->checkAccess('iresources.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять информационные ресурсы');
		}

		$iresource_id	= $request->getId('iresource_id', 0);
		$company_id		= $request->getId('company_id', 0);
		$post_uid		= $request->getId('post_uid', 0);
		$is_lock		= $request->getBool('is_lock', false);
		$igroup_id		= $request->getId('igroup_id', 0);
		$short_name		= trim($request->getStr('short_name', ''));
		$full_name		= trim($request->getStr('full_name', ''));
		$description	= trim($request->getStr('description', ''));
		$location		= trim($request->getStr('location', ''));
		$techinfo		= trim($request->getStr('techinfo', ''));
		$worker_group	= $request->getId('worker_group', 0);

		if(empty($full_name)||empty($short_name)) return Ajax::_responseError('Ошибка выполнения','Не задано название информационного ресурса');

		$organization = new Admin_Organization();
		$admin_iresource = new Admin_IResource();
		$admin_employers = new Admin_Employers();
		if(!$iresource_id || !$admin_iresource->iresourceExists($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Информационный ресурс не существует');
		if($company_id>0 && !$organization->companyExists($company_id)) return Ajax::_responseError('Ошибка выполнения','Выбранная организация не существует');
		if($post_uid>0 && !$organization->postUIDExists($post_uid, $company_id)) return Ajax::_responseError('Ошибка выполнения','Выбранная должность не существует');
		if($worker_group>0 && !$admin_employers->groupExists($worker_group)) return Ajax::_responseError('Ошибка выполнения','Группа исполнителей не существует');

		if($admin_iresource->iresourceUpdate($iresource_id, array(
			'company_id'		=> $company_id,
			'post_uid'			=> $post_uid,
			'is_lock' 			=> (!$is_lock ? 0 : 1),
			'short_name'		=> $short_name,
			'full_name'			=> $full_name,
			'description'		=> $description,
			'location'			=> $location,
			'techinfo'			=> $techinfo,
			'iresource_group'	=> $igroup_id,
			'worker_group'		=> $worker_group
		))===false){
			return Ajax::_responseError('Ошибка выполнения','Ошибка изменения информационного ресурса');
		}

		Protocol::_add(array(
			'action_name'	=> $request_action,
			'acl_id'		=> $uaccess->getObjectIdFormName('iresources.edit'),
			'acl_name'		=> 'iresources.edit',
			'primary_type'	=> 'iresource',
			'primary_id'	=> $iresource_id,
			'description'	=> 'Внесены изменения в информационный ресурс',
			'value'	=> array(
				'company_id'		=> $company_id,
				'post_uid'			=> $post_uid,
				'is_lock' 			=> (!$is_lock ? 0 : 1),
				'short_name'		=> $short_name,
				'full_name'			=> $full_name,
				'description'		=> $description,
				'location'			=> $location,
				'techinfo'			=> $techinfo,
				'iresource_group'	=> $igroup_id,
				'worker_group'		=> $worker_group
			)
		));

		#Выполнено успешно
		Ajax::_responseSuccess('Сохранение настроек ресурса','Операция выполнена успешно');
		return Ajax::_setData(array(
			'iresource_id'	=> $iresource_id,
			'iresource'		=> $admin_iresource->getIResourcesListEx($iresource_id,null,true,true)
		));

	break; #Редактирование информационного ресурса





	/*******************************************************************
	 * Удаление информационного ресурса
	 ******************************************************************/
	case 'iresource.delete':

		$iresource_id = $request->getId('iresource_id', 0);

		if(!$uaccess->checkAccess('iresources.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять информационные ресурсы');
		}

		$admin_iresource = new Admin_IResource();
		if(!$admin_iresource->iresourceExists($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Информационного ресурса не существует');

		//Проверка допустимости удаления организации, для обеспечения целостности
		if(!$admin_iresource->iresourceCanDelete($iresource_id)){
			return Ajax::_responseError('Ошибка выполнения','Нельзя удалить информационный ресурс, поскольку он используется в шаблонах доступа или заявках сотрудников');
		}

		if(!$admin_iresource->iresourceDelete($iresource_id, false)){
			return Ajax::_responseError('Ошибка выполнения','Ошибка удаления информационного ресурса');
		}

		Protocol::_add(array(
			'action_name'	=> $request_action,
			'acl_id'		=> $uaccess->getObjectIdFormName('iresources.edit'),
			'acl_name'		=> 'iresources.edit',
			'primary_type'	=> 'iresource',
			'primary_id'	=> $iresource_id,
			'description'	=> 'Удален информационный ресурс',
			'value'			=> array()
		));

		$request_action = 'iresources.search';
		goto LABEL_IRESOURCES_AXCONTROLLER_START;


	break; #Удаление информационного ресурса






	/*******************************************************************
	 * Поиск информационных ресурсов
	 ******************************************************************/
	case 'iresources.search':

		$company_id = $request->getStr('company_id','all');
		$iresource_status = $request->getStr('iresource_status','1');
		$search_name = $request->getStr('search_name','');
		$igroup_id = $request->getStr('igroup_id','all');
		$conditions = array();

		if($company_id != 'all') $conditions['company_id'] = intval($company_id);
		if($igroup_id != 'all') $conditions['iresource_group'] = intval($igroup_id);
		if($iresource_status != 'all') $conditions['is_lock'] = ($iresource_status=='1'?0:1);
		if(!empty($search_name)) $conditions[] = array(
			'field'=>array('full_name','description','location'),
			'value'=>$search_name,
			'glue' => '%LIKE%',
			'bridge'=>',',
			'field_bridge' => 'OR'
		);

		if(empty($conditions)) $conditions = null;
		$admin_iresource = new Admin_IResource();

		#Выполнено успешно
		return Ajax::_setData(array(
			'iresources' => $admin_iresource->getIResourcesListEx($conditions, null, false)
		));

	break; #Поиск информационных ресурсов




	/*******************************************************************
	 * Добавление организации в ресурс
	 ******************************************************************/
	case 'iresource.company.include':

		if(!$uaccess->checkAccess('iresources.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять информационные ресурсы');
		}

		$iresource_id = $request->getId('iresource_id', 0);
		$companies = $request->getArray('companies', null);
		if(empty($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Не задан информационный ресурс');
		if(empty($companies)||!is_array($companies)) return Ajax::_responseError('Ошибка выполнения','Не заданы организации');

		$admin_iresource = new Admin_IResource();
		$organization = new Admin_Organization();
		if(!$admin_iresource->iresourceExists($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Информационный ресурс существует');
		$companies = array_map('intval',$companies);

		$db = Database::getInstance('main');
		$db->transaction();

		foreach($companies as $company_id){
			if($company_id>0 && !$organization->companyExists($company_id)){
				$db->rollback();
				return Ajax::_responseError('Ошибка выполнения','Организация [ID:'.$company_id.'] не существует');
			}
			if(!$admin_iresource->iresourceIncludeCompany($iresource_id, $company_id)){
				$db->rollback();
				return Ajax::_responseError('Ошибка выполнения','Ошибка добавления организации [ID:'.$company_id.'] в ресурс [ID:'.$iresource_id.']');
			}
			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('iresources.edit'),
				'acl_name'		=> 'iresources.edit',
				'primary_type'	=> 'iresource',
				'primary_id'	=> $iresource_id,
				'secondary_type'=> 'company',
				'secondary_id'	=> $company_id,
				'description'	=> 'Открыт доступ к информационному ресурсу для организации ID:'.$company_id,
				'value'			=> array()
			));
		}

		#Выполнено успешно
		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			'iresource_id' => $iresource_id,
			'iresource_companies' => $admin_iresource->getIResourceCompanies($iresource_id)
		));

	break; #Добавление организации в ресурс






	/*******************************************************************
	 * Удаление организации из ресурса
	 ******************************************************************/
	case 'iresource.company.exclude':

		if(!$uaccess->checkAccess('iresources.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять информационные ресурсы');
		}

		$iresource_id = $request->getId('iresource_id', 0);
		$companies = $request->getArray('companies', null);
		if(empty($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Не задан информационный ресурс');
		if(empty($companies)||!is_array($companies)) return Ajax::_responseError('Ошибка выполнения','Не заданы организации');

		$admin_iresource = new Admin_IResource();
		if(!$admin_iresource->iresourceExists($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Информационный ресурс существует');
		$companies = array_map('intval',$companies);

		$db = Database::getInstance('main');
		$db->transaction();

		foreach($companies as $company_id){
			if(!$admin_iresource->iresourceExcludeCompany($iresource_id, $company_id)){
				$db->rollback();
				return Ajax::_responseError('Ошибка выполнения','Ошибка удаления организации [ID:'.$company_id.'] из ресурса [ID:'.$iresource_id.']');
			}
			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('iresources.edit'),
				'acl_name'		=> 'iresources.edit',
				'primary_type'	=> 'iresource',
				'primary_id'	=> $iresource_id,
				'secondary_type'=> 'company',
				'secondary_id'	=> $company_id,
				'description'	=> 'Закрыт доступ к информационному ресурсу для организации ID:'.$company_id,
				'value'			=> array()
			));
		}

		#Выполнено успешно
		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			'iresource_id' => $iresource_id,
			'iresource_companies' => $admin_iresource->getIResourceCompanies($iresource_id)
		));

	break; #Удаление организации из ресурса






	/*******************************************************************
	 * Добавление объектов доступа в информационный ресурс
	 ******************************************************************/
	case 'irole.add':

		$iresource_id	= $request->getId('iresource_id', 0);

		if(empty($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Не задан информационный ресурс');

		$admin_iresource = new Admin_IResource();
		if(!$admin_iresource->iresourceExists($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Информационный ресурс не существует');

		$iresource = $admin_iresource->getIResourcesList($iresource_id,null,true);
		if(!$uaccess->checkAccess('iresource.roles.edit', $iresource['company_id'])){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять объекты доступа этого информационного ресурса');
		}

		$owner_id		= $request->getId('section', 0);
		$weight			= $request->getId('weight', 0);
		$short_name		= $request->getStr('short_name', '');
		$full_names		= array_map('trim',explode("\n",$request->getStr('full_name', '')));
		$description	= $request->getStr('description', '');
		$is_lock		= $request->getBool('is_lock', false);
		$ir_types		= implode(',',array_map('intval',$request->getArray('ir_types', array())));


		$db = Database::getInstance('main');
		$db->transaction();

		foreach($full_names as $full_name){
			if(empty($full_name)) continue;
			if(($irole_id = $admin_iresource->iroleNew(array(
				'iresource_id'	=> $iresource_id,
				'owner_id'		=> $owner_id,
				'short_name'	=> (empty($short_name) ? $full_name : $short_name),
				'full_name'		=> $full_name,
				'description'	=> $description,
				'is_lock'		=> ($is_lock?'1':'0'),
				'is_area'		=> 0,
				'ir_types'		=> $ir_types,
				'weight'		=> $weight
			)))===false){
				$db->rollback();
				return Ajax::_responseError('Ошибка выполнения','Ошибка добавления объекта доступа [NAME:'.$full_name.'] в ресурс [ID:'.$iresource_id.']');
			}
			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('iresource.roles.edit'),
				'acl_name'		=> 'iresource.roles.edit',
				'primary_type'	=> 'irole',
				'primary_id'	=> $irole_id,
				'secondary_type'=> 'iresource',
				'secondary_id'	=> $iresource_id,
				'description'	=> 'Создан объект доступа ID:'.$irole_id.' в информационном ресурсе ID:'.$iresource_id,
				'value'			=> array(
					'iresource_id'	=> $iresource_id,
					'owner_id'		=> $owner_id,
					'short_name'	=> (empty($short_name) ? $full_name : $short_name),
					'full_name'		=> $full_name,
					'description'	=> $description,
					'is_lock'		=> ($is_lock?'1':'0'),
					'is_area'		=> 0,
					'ir_types'		=> $ir_types,
					'weight'		=> $weight
				)
			));
		}

		#Выполнено успешно
		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			'iroles' => $admin_iresource->getIRoles($iresource_id)
		));

	break; #Добавление объектов доступа в информационный ресурс





	/*******************************************************************
	 * Изменение свойств объекта доступа
	 ******************************************************************/
	case 'irole.edit':

		$iresource_id	= $request->getId('iresource_id', 0);

		if(empty($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Не задан информационный ресурс');

		$admin_iresource = new Admin_IResource();
		if(!$admin_iresource->iresourceExists($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Информационный ресурс не существует');

		$iresource = $admin_iresource->getIResourcesList($iresource_id,null,true);
		if(!$uaccess->checkAccess('iresource.roles.edit', $iresource['company_id'])){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять объекты доступа этого информационного ресурса');
		}

		$irole_id		= $request->getId('irole_id', 0);
		$owner_id		= $request->getId('section', 0);
		$weight			= $request->getId('weight', 0);
		$short_name		= trim($request->getStr('short_name', ''));
		$full_name		= trim($request->getStr('full_name', ''));
		$description	= trim($request->getStr('description', ''));
		$is_lock		= $request->getBool('is_lock', false);
		$is_area		= $request->getBool('is_area', false);
		$ir_types		= implode(',',array_map('intval',$request->getArray('ir_types', array())));

		if(empty($irole_id)||empty($full_name)) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');

		$db = Database::getInstance('main');
		$db->transaction();

		if($admin_iresource->iroleUpdate($iresource_id, $irole_id, array(
			'owner_id'		=> $owner_id,
			'short_name'	=> (empty($short_name) ? $full_name : $short_name),
			'full_name'		=> $full_name,
			'description'	=> $description,
			'is_lock'		=> ($is_lock?'1':'0'),
			'is_area'		=> 0,
			'ir_types'		=> $ir_types,
			'weight'		=> $weight
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Ошибка обновления объекта доступа [ID:'.$irole_id.', NAME:'.$full_name.'] в ресурсе [ID:'.$iresource_id.']');
		}

		Protocol::_add(array(
			'action_name'	=> $request_action,
			'acl_id'		=> $uaccess->getObjectIdFormName('iresource.roles.edit'),
			'acl_name'		=> 'iresource.roles.edit',
			'primary_type'	=> 'irole',
			'primary_id'	=> $irole_id,
			'secondary_type'=> 'iresource',
			'secondary_id'	=> $iresource_id,
			'description'	=> 'Изменен объект доступа ID:'.$irole_id.' в информационном ресурсе ID:'.$iresource_id,
			'value'			=> array(
				'owner_id'		=> $owner_id,
				'short_name'	=> (empty($short_name) ? $full_name : $short_name),
				'full_name'		=> $full_name,
				'description'	=> $description,
				'is_lock'		=> ($is_lock?'1':'0'),
				'is_area'		=> 0,
				'ir_types'		=> $ir_types,
				'weight'		=> $weight
			)
		));

		#Выполнено успешно
		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			'iroles' => $admin_iresource->getIRoles($iresource_id)
		));

	break; #Изменение свойств объекта доступа





	/*******************************************************************
	 * Удаление бъектов доступа из информационного ресурса
	 ******************************************************************/
	case 'irole.delete':

		$iresource_id	= $request->getId('iresource_id', 0);

		if(empty($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Не задан информационный ресурс');

		$admin_iresource = new Admin_IResource();
		if(!$admin_iresource->iresourceExists($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Информационный ресурс не существует');

		$iresource = $admin_iresource->getIResourcesList($iresource_id,null,true);
		if(!$uaccess->checkAccess('iresource.roles.edit', $iresource['company_id'])){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять объекты доступа этого информационного ресурса');
		}

		$irole_id = $request->getId('irole_id', 0);

		if(!$admin_iresource->iroleCanDelete($iresource_id, $irole_id)){
			return Ajax::_responseError('Ошибка выполнения','Нельзя удалить выбранный объект доступа, поскольку он используется в шаблонах доступа или заявках сотрудников');
		}

		if(!$admin_iresource->iroleDelete($iresource_id, $irole_id, false)){
			return Ajax::_responseError('Ошибка выполнения','Ошибка удаления объекта из информационного ресурса');
		}

		Protocol::_add(array(
			'action_name'	=> $request_action,
			'acl_id'		=> $uaccess->getObjectIdFormName('iresource.roles.edit'),
			'acl_name'		=> 'iresource.roles.edit',
			'primary_type'	=> 'irole',
			'primary_id'	=> $irole_id,
			'secondary_type'=> 'iresource',
			'secondary_id'	=> $iresource_id,
			'description'	=> 'Удален объект доступа ID:'.$irole_id.' из информационного ресурса ID:'.$iresource_id,
			'value'			=> array()
		));

		#Выполнено успешно
		return Ajax::_setData(array(
			'iroles' => $admin_iresource->getIRoles($iresource_id)
		));

	break; #Удаление бъектов доступа из информационного ресурса





	/*******************************************************************
	 * Получение информации об объекте доступа
	 ******************************************************************/
	case 'irole.info':

		$iresource_id = $request->getId('iresource_id', 0);
		$irole_id = $request->getId('irole_id', 0);

		$admin_iresource = new Admin_IResource();
		if(!$admin_iresource->iresourceExists($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Информационного ресурса не существует');

		#Выполнено успешно
		return Ajax::_setData(array(
			'irole' => $admin_iresource->getIRole($iresource_id, $irole_id)
		));

	break; #Получение информации об объекте доступа




	/*******************************************************************
	 * Получение информации об объекте доступа типа раздела
	 ******************************************************************/
	case 'section.info':

		$iresource_id = $request->getId('iresource_id', 0);
		$irole_id = $request->getId('irole_id', 0);

		$admin_iresource = new Admin_IResource();
		if(!$admin_iresource->iresourceExists($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Информационного ресурса не существует');

		#Выполнено успешно
		return Ajax::_setData(array(
			'section' => $admin_iresource->getIRole($iresource_id, $irole_id)
		));

	break; #Получение информации об объекте доступа





	/*******************************************************************
	 * Получение списка объектов доступа информационного ресурса
	 ******************************************************************/
	case 'iresource.roles':

		$iresource_id = $request->getId('iresource_id', 0);

		$admin_iresource = new Admin_IResource();
		if(!$admin_iresource->iresourceExists($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Информационного ресурса не существует');

		#Выполнено успешно
		return Ajax::_setData(array(
			'iresource_id' => $iresource_id,
			'iroles' => $admin_iresource->getIRoles($iresource_id)
		));

	break; #Получение списка объектов доступа информационного ресурса





	/*******************************************************************
	 * Отправка файла скриншота на сервер
	 ******************************************************************/
	case 'irole.screenshot.upload':

		$iresource_id	= $request->getId('iresource_id', 0);

		if(empty($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Не задан информационный ресурс');

		$admin_iresource = new Admin_IResource();
		if(!$admin_iresource->iresourceExists($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Информационный ресурс не существует');

		$iresource = $admin_iresource->getIResourcesList($iresource_id,null,true);
		if(!$uaccess->checkAccess('iresource.roles.edit', $iresource['company_id'])){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять объекты доступа этого информационного ресурса');
		}

		header('Content-Type: text/html; charset=utf-8', true);

		$irole_id = $request->getId('irole_id', 0);

		#Файл не задан
		if(!$_FILES['screenshot']['size']){
			return Ajax::_responseError('Ошибка выполнения','Не задан файл скриншота');
		}

		#Размер файла
		if($_FILES['screenshot']['size'] > 2097152){
			return Ajax::_responseError('Ошибка выполнения','Слишком большой размер файла');
		}

		#Ошибка загрузки файла
		if($_FILES['screenshot']['error']){
			return Ajax::_responseError('Ошибка выполнения','Ошибка загрузки файла: '.$_FILES['screenshot']['error']);
		}

		$filename = DIR_IROLE_SCREENSHOTS.'/irole_'.$irole_id.'.jpg';

		try{

			#Проверка прав
			if(file_exists($filename) && !unlink($filename)){
				return Ajax::_responseError('Ошибка выполнения','Недостаточно прав для удаления имеющегося файла скриншота');
			}

			#Открытие файла картинки
			if(!($image = imagecreatefromstring(file_get_contents($_FILES['screenshot']['tmp_name'])))){
				return Ajax::_responseError('Ошибка выполнения','Ошибка открытия изображения из загруженного файла');
			}

			#Сохранение файла
			if(!imagejpeg($image, $filename, 70)){
				return Ajax::_responseError('Ошибка выполнения','Ошибка сохранения изображения на сервере');
			}

			imagedestroy($image);

		}catch (Exception $e){
			return Ajax::_responseError('Ошибка выполнения','Ошибка обработки файла скриншота');
		}


		#Выполнено успешно
		return Ajax::_setData(array(
			'iroles' => $admin_iresource->getIRoles($iresource_id)
		));

	break; #Отправка файла скриншота на сервер




	/*******************************************************************
	 * Удаление файла скриншота с сервера
	 ******************************************************************/
	case 'irole.screenshot.delete':

		$iresource_id	= $request->getId('iresource_id', 0);

		if(empty($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Не задан информационный ресурс');

		$admin_iresource = new Admin_IResource();
		if(!$admin_iresource->iresourceExists($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Информационный ресурс не существует');

		$iresource = $admin_iresource->getIResourcesList($iresource_id,null,true);
		if(!$uaccess->checkAccess('iresource.roles.edit', $iresource['company_id'])){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять объекты доступа этого информационного ресурса');
		}

		$irole_id = $request->getId('irole_id', 0);

		irole_screenshot_delete($irole_id);

		#Выполнено успешно
		return Ajax::_setData(array(
			'iroles' => $admin_iresource->getIRoles($iresource_id)
		));

	break; #Удаление файла скриншота с сервера




	/*******************************************************************
	 * Добавление секций в информационный ресурс
	 ******************************************************************/
	case 'section.add':

		$iresource_id	= $request->getId('iresource_id', 0);

		if(empty($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Не задан информационный ресурс');

		$admin_iresource = new Admin_IResource();
		if(!$admin_iresource->iresourceExists($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Информационный ресурс не существует');

		$iresource = $admin_iresource->getIResourcesList($iresource_id,null,true);
		if(!$uaccess->checkAccess('iresource.roles.edit', $iresource['company_id'])){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять объекты доступа этого информационного ресурса');
		}

		$short_name		= $request->getStr('short_name', '');
		$full_names		= array_map('trim',explode("\n",$request->getStr('full_name', '')));
		$description	= $request->getStr('description', '');

		$db = Database::getInstance('main');
		$db->transaction();

		foreach($full_names as $full_name){
			if(empty($full_name)) continue;
			if(($irole_id = $admin_iresource->iroleNew(array(
				'iresource_id'	=> $iresource_id,
				'owner_id'		=> 0,
				'short_name'	=> (empty($short_name) ? $full_name : $short_name),
				'full_name'		=> $full_name,
				'description'	=> $description,
				'is_lock'		=> 0,
				'is_area'		=> 1
			)))===false){
				$db->rollback();
				return Ajax::_responseError('Ошибка выполнения','Ошибка добавления секции [NAME:'.$full_name.'] в ресурс [ID:'.$iresource_id.']');
			}
		}

		Protocol::_add(array(
			'action_name'	=> $request_action,
			'acl_id'		=> $uaccess->getObjectIdFormName('iresource.roles.edit'),
			'acl_name'		=> 'iresource.roles.edit',
			'primary_type'	=> 'irole',
			'primary_id'	=> $irole_id,
			'secondary_type'=> 'iresource',
			'secondary_id'	=> $iresource_id,
			'description'	=> 'Создан раздел ID:'.$irole_id.' в информационном ресурсе ID:'.$iresource_id,
			'value'			=> array(
				'iresource_id'	=> $iresource_id,
				'owner_id'		=> 0,
				'short_name'	=> (empty($short_name) ? $full_name : $short_name),
				'full_name'		=> $full_name,
				'description'	=> $description,
				'is_lock'		=> 0,
				'is_area'		=> 1
			)
		));

		#Выполнено успешно
		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			'iroles' => $admin_iresource->getIRoles($iresource_id)
		));

	break; #Добавление объектов доступа в информационный ресурс






	/*******************************************************************
	 * Изменение свойств раздела
	 ******************************************************************/
	case 'section.edit':

		$iresource_id	= $request->getId('iresource_id', 0);

		if(empty($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Не задан информационный ресурс');

		$admin_iresource = new Admin_IResource();
		if(!$admin_iresource->iresourceExists($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Информационный ресурс не существует');

		$iresource = $admin_iresource->getIResourcesList($iresource_id,null,true);
		if(!$uaccess->checkAccess('iresource.roles.edit', $iresource['company_id'])){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять объекты доступа этого информационного ресурса');
		}

		$irole_id		= $request->getId('irole_id', 0);
		$short_name		= trim($request->getStr('short_name', ''));
		$full_name		= trim($request->getStr('full_name', ''));
		$description	= trim($request->getStr('description', ''));

		if(empty($irole_id)||empty($full_name)) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');

		$db = Database::getInstance('main');
		$db->transaction();

		if($admin_iresource->iroleUpdate($iresource_id, $irole_id, array(
			'short_name'	=> (empty($short_name) ? $full_name : $short_name),
			'full_name'		=> $full_name,
			'description'	=> $description
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Ошибка обновления раздела [ID:'.$irole_id.', NAME:'.$full_name.'] в ресурсе [ID:'.$iresource_id.']');
		}

		Protocol::_add(array(
			'action_name'	=> $request_action,
			'acl_id'		=> $uaccess->getObjectIdFormName('iresource.roles.edit'),
			'acl_name'		=> 'iresource.roles.edit',
			'primary_type'	=> 'irole',
			'primary_id'	=> $irole_id,
			'secondary_type'=> 'iresource',
			'secondary_id'	=> $iresource_id,
			'description'	=> 'Изменен раздел ID:'.$irole_id.' в информационном ресурсе ID:'.$iresource_id,
			'value'			=> array(
				'short_name'	=> (empty($short_name) ? $full_name : $short_name),
				'full_name'		=> $full_name,
				'description'	=> $description
			)
		));

		#Выполнено успешно
		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			'iroles' => $admin_iresource->getIRoles($iresource_id)
		));

	break; #Изменение свойств раздела





	/*******************************************************************
	 * Импорт объектов доступа из информационного ресурса
	 ******************************************************************/
	case 'iresource.import':

		$iresource_id	= $request->getId('iresource_id', 0);

		if(empty($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Не задан информационный ресурс');

		$admin_iresource = new Admin_IResource();
		if(!$admin_iresource->iresourceExists($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Информационный ресурс не существует');

		$iresource = $admin_iresource->getIResourcesList($iresource_id,null,true);
		if(!$uaccess->checkAccess('iresource.roles.edit', $iresource['company_id'])){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять объекты доступа этого информационного ресурса');
		}

		$import_from = $request->getId('import_from', 0);
		$import_type = $request->getEnum('import_type',array('copy','clone','custom'),'');
		$import_screenshots = $request->getBool('import_screenshots', false);
		$import_section = $request->getId('import_section', 0);
		$import_iroles = $request->getArray('iroles', null);

		if(empty($import_from)||empty($import_type)) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');

		if(!$admin_iresource->iresourceExists($import_from)) return Ajax::_responseError('Ошибка выполнения','Информационного ресурса ID:'.$import_from.' не существует');

		if($import_type == 'custom'){
			if(empty($import_iroles)) return Ajax::_responseError('Ошибка выполнения','Не выбраны объекты доступа для копирования');
			if($import_section > 0 && !$admin_iresource->iroleExists($iresource_id, $import_section)) return Ajax::_responseError('Ошибка выполнения','Раздел ID:'.$import_section.' не существует');
		}

		$db = Database::getInstance('main');
		$db->transaction();


		if(($import_iroles = $admin_iresource->getIRoles($import_from, null, true,($import_type == 'custom' ? $import_iroles : null)))===false){
			$db->rollback();
			 return Ajax::_responseError('Ошибка выполнения','Ошибка получения списка объектов информационного ресурса ID:'.$import_from);
		}

		//Операция клонирования
		if($import_type == 'clone'){
			if(($current_iroles = $admin_iresource->iresourceIRoles($iresource_id))===false){
				$db->rollback();
				 return Ajax::_responseError('Ошибка выполнения','Ошибка получения списка объектов информационного ресурса ID:'.$iresource_id);
			}
			foreach($current_iroles as $irole_id){
				if(!$admin_iresource->iroleCanDelete($iresource_id, $irole_id)){
					$db->rollback();
					return Ajax::_responseError('Ошибка выполнения','Нельзя удалить выбранный объект доступа ID:'.$irole_id.', поскольку он используется в шаблонах доступа или заявках сотрудников');
				}
				if(!$admin_iresource->iroleDelete($iresource_id, $irole_id, false, false)){
					$db->rollback();
					return Ajax::_responseError('Ошибка выполнения','Ошибка удаления объекта ID:'.$irole_id.' из информационного ресурса ID:'.$iresource_id);
				}
			}
		}//Операция клонирования

		$sections=array();
		$items=array();

		//Копирование объектов доступа: создание разделов
		foreach($import_iroles as $irole){
			if($irole['is_area']!=1) continue;
			if(($irole_id = $admin_iresource->iroleNew(array(
				'iresource_id'	=> $iresource_id,
				'owner_id'		=> 0,
				'short_name'	=> $irole['short_name'],
				'full_name'		=> $irole['full_name'],
				'description'	=> $irole['description'],
				'is_lock'		=> 0,
				'is_area'		=> 1
			)))===false){
				$db->rollback();
				return Ajax::_responseError('Ошибка выполнения','Ошибка добавления раздела [NAME:'.$irole['full_name'].'] в ресурс [ID:'.$iresource_id.']');
			}
			$sections[$irole['irole_id']] = $irole_id;
			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('iresource.roles.edit'),
				'acl_name'		=> 'iresource.roles.edit',
				'primary_type'	=> 'irole',
				'primary_id'	=> $irole_id,
				'secondary_type'=> 'iresource',
				'secondary_id'	=> $iresource_id,
				'description'	=> 'Создан раздел ID:'.$irole_id.' в информационном ресурсе ID:'.$iresource_id,
				'value'			=> array(
					'iresource_id'	=> $iresource_id,
					'owner_id'		=> 0,
					'short_name'	=> $irole['short_name'],
					'full_name'		=> $irole['full_name'],
					'description'	=> $irole['description'],
					'is_lock'		=> 0,
					'is_area'		=> 1
				)
			));
		}//Копирование объектов доступа: создание разделов


		//Копирование объектов доступа: создание объектов
		foreach($import_iroles as $irole){
			if($irole['is_area']==1) continue;
			$section_id = ($irole['owner_id'] > 0 ? (isset($sections[$irole['owner_id']]) ? $sections[$irole['owner_id']] : 0) : 0);
			if(($irole_id = $admin_iresource->iroleNew(array(
				'iresource_id'	=> $iresource_id,
				'owner_id'		=> ($import_type == 'custom' ? $import_section : $section_id),
				'short_name'	=> $irole['short_name'],
				'full_name'		=> $irole['full_name'],
				'description'	=> $irole['description'],
				'is_lock'		=> $irole['is_lock'],
				'is_area'		=> 0,
				'ir_types'		=> $irole['ir_types'],
				'weight'		=> $irole['weight']
			)))===false){
				$db->rollback();
				return Ajax::_responseError('Ошибка выполнения','Ошибка добавления объекта доступа [NAME:'.$irole['full_name'].'] в ресурс [ID:'.$iresource_id.']');
			}
			$items[$irole['irole_id']] = $irole_id;
			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('iresource.roles.edit'),
				'acl_name'		=> 'iresource.roles.edit',
				'primary_type'	=> 'irole',
				'primary_id'	=> $irole_id,
				'secondary_type'=> 'iresource',
				'secondary_id'	=> $iresource_id,
				'description'	=> 'Создан объект доступа ID:'.$irole_id.' в информационном ресурсе ID:'.$iresource_id,
				'value'			=> array(
					'iresource_id'	=> $iresource_id,
					'owner_id'		=> ($import_type == 'custom' ? $import_section : $section_id),
					'short_name'	=> $irole['short_name'],
					'full_name'		=> $irole['full_name'],
					'description'	=> $irole['description'],
					'is_lock'		=> $irole['is_lock'],
					'is_area'		=> 0,
					'ir_types'		=> $irole['ir_types'],
					'weight'		=> $irole['weight']
				)
			));
		}//Копирование объектов доступа: создание объектов


		#Выполнено успешно
		$db->commit();

		//Удаление скриншотов
		if($import_type == 'clone'){
			foreach($current_iroles as $irole_id){
				irole_screenshot_delete($irole_id);
			}
		}

		//Копирование скриншотов
		if($import_screenshots){
			foreach($items as $from=>$to){
				irole_screenshot_copy($from, $to);
			}
		}

		#Выполнено успешно
		return Ajax::_setData(array(
			'iroles' => $admin_iresource->getIRoles($iresource_id)
		));

	break; #Импорт объектов доступа из информационного ресурса
















	/*******************************************************************
	 * Добавление типа доступа
	 ******************************************************************/
	case 'irtype.new':

		if(!$uaccess->checkAccess('irtype.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете редактировать список типов доступа');
		}

		$short_name		= trim($request->getStr('short_name', ''));
		$full_name		= trim($request->getStr('full_name', ''));

		if(empty($full_name)||empty($short_name)) return Ajax::_responseError('Ошибка выполнения','Не задано название типа доступа');

		$admin_iresource = new Admin_IResource();

		if(($item_id = $admin_iresource->irtypeNew(array(
			'short_name'	=> $short_name,
			'full_name'		=> $full_name
		)))===false){
			return Ajax::_responseError('Ошибка выполнения','Ошибка добавления типа доступа');
		}

			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('irtype.edit'),
				'acl_name'		=> 'irtype.edit',
				'primary_type'	=> 'irtype',
				'primary_id'	=> $item_id,
				'description'	=> 'Создан тип доступа',
				'value'			=> array(
					'short_name'	=> $short_name,
					'full_name'		=> $full_name
				)
			));

		#Выполнено успешно
		return Ajax::_setData(array(
			'item_id'	=> $item_id,
			'ir_types'	=> $admin_iresource->getIRTypesList()
		));

	break; #Добавление типа доступа





	/*******************************************************************
	 * Редактирование типа доступа
	 ******************************************************************/
	case 'irtype.edit':

		if(!$uaccess->checkAccess('irtype.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете редактировать список типов доступа');
		}

		$item_id	= $request->getId('item_id', 0);
		$short_name	= trim($request->getStr('short_name', ''));
		$full_name	= trim($request->getStr('full_name', ''));

		if(empty($item_id)) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');
		if(empty($full_name)||empty($short_name)) return Ajax::_responseError('Ошибка выполнения','Не задано название типа доступа');

		$admin_iresource = new Admin_IResource();

		if($admin_iresource->irtypeUpdate($item_id, array(
			'short_name'	=> $short_name,
			'full_name'		=> $full_name
		))===false){
			return Ajax::_responseError('Ошибка выполнения','Ошибка изменения типа доступа');
		}

			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('irtype.edit'),
				'acl_name'		=> 'irtype.edit',
				'primary_type'	=> 'irtype',
				'primary_id'	=> $item_id,
				'description'	=> 'Изменен тип доступа',
				'value'			=> array(
					'short_name'	=> $short_name,
					'full_name'		=> $full_name
				)
			));

		#Выполнено успешно
		Ajax::_responseSuccess('Редактирование типа доступа','Операция выполнена успешно');
		return Ajax::_setData(array(
			'item_id'	=> $item_id,
			'ir_types' => $admin_iresource->getIRTypesList()
		));

	break; #Редактирование типа доступа





	/*******************************************************************
	 * Удаление типа доступа
	 ******************************************************************/
	case 'irtype.delete':

		if(!$uaccess->checkAccess('irtype.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете редактировать список типов доступа');
		}

		$item_id = $request->getId('item_id', 0);

		$admin_iresource = new Admin_IResource();

		//Проверка допустимости удаления, для обеспечения целостности
		if(!$admin_iresource->irtypeCanDelete($item_id)){
			return Ajax::_responseError('Ошибка выполнения','Нельзя удалить тип доступа, поскольку он используется в настройках информационных ресурсов, шаблонах доступа или заявках сотрудников');
		}

		if(!$admin_iresource->irtypeDelete($item_id, false)){
			return Ajax::_responseError('Ошибка выполнения','Ошибка удаления типа доступа');
		}

			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('irtype.edit'),
				'acl_name'		=> 'irtype.edit',
				'primary_type'	=> 'irtype',
				'primary_id'	=> $item_id,
				'description'	=> 'Удален тип доступа ID:'.$item_id,
				'value'			=> array()
			));

		#Выполнено успешно
		return Ajax::_setData(array(
			'ir_types' => $admin_iresource->getIRTypesList()
		));

	break; #Удаление типа доступа








	/*******************************************************************
	 * Добавление группы ресурсов
	 ******************************************************************/
	case 'igroup.new':

		if(!$uaccess->checkAccess('igroup.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете редактировать группы информационных ресурсов');
		}

		$short_name		= trim($request->getStr('short_name', ''));
		$full_name		= trim($request->getStr('full_name', ''));

		if(empty($full_name)||empty($short_name)) return Ajax::_responseError('Ошибка выполнения','Не задано название группы информационных ресурсов');

		$admin_iresource = new Admin_IResource();

		if(($igroup_id = $admin_iresource->igroupNew(array(
			'short_name'	=> $short_name,
			'full_name'		=> $full_name
		)))===false){
			return Ajax::_responseError('Ошибка выполнения','Ошибка добавления группы информационных ресурсов');
		}

			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('igroup.edit'),
				'acl_name'		=> 'igroup.edit',
				'primary_type'	=> 'igroup',
				'primary_id'	=> $igroup_id,
				'description'	=> 'Создана группа информационных ресурсов',
				'value'			=> array(
					'short_name'	=> $short_name,
					'full_name'		=> $full_name
				)
			));

		#Выполнено успешно
		return Ajax::_setData(array(
			'igroup_id'	=> $igroup_id,
			'iresource_groups' => $admin_iresource->getIGroupsList()
		));

	break; #Добавление группы ресурсов





	/*******************************************************************
	 * Редактирование группы ресурсов
	 ******************************************************************/
	case 'igroup.edit':

		if(!$uaccess->checkAccess('igroup.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете редактировать группы информационных ресурсов');
		}

		$igroup_id	= $request->getId('igroup_id', 0);
		$short_name	= trim($request->getStr('short_name', ''));
		$full_name	= trim($request->getStr('full_name', ''));

		if(empty($igroup_id)) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');
		if(empty($full_name)||empty($short_name)) return Ajax::_responseError('Ошибка выполнения','Не задано название группы информационных ресурсов');

		$admin_iresource = new Admin_IResource();

		if($admin_iresource->igroupUpdate($igroup_id, array(
			'short_name'	=> $short_name,
			'full_name'		=> $full_name
		))===false){
			return Ajax::_responseError('Ошибка выполнения','Ошибка изменения группы информационных ресурсов');
		}

			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('igroup.edit'),
				'acl_name'		=> 'igroup.edit',
				'primary_type'	=> 'igroup',
				'primary_id'	=> $igroup_id,
				'description'	=> 'Изменена группа информационных ресурсов',
				'value'			=> array(
					'short_name'	=> $short_name,
					'full_name'		=> $full_name
				)
			));

		#Выполнено успешно
		Ajax::_responseSuccess('Редактирование группы информационных ресурсов','Операция выполнена успешно');
		return Ajax::_setData(array(
			'igroup_id'	=> $igroup_id,
			'iresource_groups' => $admin_iresource->getIGroupsList()
		));

	break; #Редактирование группы ресурсов





	/*******************************************************************
	 * Удаление группы ресурсов
	 ******************************************************************/
	case 'igroup.delete':

		if(!$uaccess->checkAccess('igroup.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете редактировать группы информационных ресурсов');
		}

		$igroup_id = $request->getId('igroup_id', 0);

		$admin_iresource = new Admin_IResource();

		//Проверка допустимости удаления, для обеспечения целостности
		if(!$admin_iresource->igroupCanDelete($igroup_id)){
			return Ajax::_responseError('Ошибка выполнения','Нельзя удалить группу информационных ресурсов, поскольку она используется в настройках информационных ресурсов');
		}

		if(!$admin_iresource->igroupDelete($igroup_id, false)){
			return Ajax::_responseError('Ошибка выполнения','Ошибка удаления группы информационных ресурсов');
		}

			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('igroup.edit'),
				'acl_name'		=> 'igroup.edit',
				'primary_type'	=> 'igroup',
				'primary_id'	=> $igroup_id,
				'description'	=> 'Удалена группа информационных ресурсов ID:'.$igroup_id,
				'value'			=> array()
			));

		#Выполнено успешно
		return Ajax::_setData(array(
			'iresource_groups' => $admin_iresource->getIGroupsList()
		));

	break; #Удаление группы ресурсов






	default:
	Ajax::_responseError('/main/ajax/iresources','Не найден обработчик для: '.Request::_get('action'));
}
?>