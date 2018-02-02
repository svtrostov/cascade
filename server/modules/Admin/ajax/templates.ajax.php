<?php
/*==================================================================================================
Описание: Обработка AJAX запросов
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');


$request_action = Request::_get('action');

LABEL_TEMPLATES_AXCONTROLLER_START:

#Обработка AJAX запроса, в зависимости от запрошенного действия
switch($request_action){



	/*******************************************************************
	 * Поиск шаблонов
	 ******************************************************************/
	case 'templates.search':

		$company_id = $request->getStr('company_id','all');
		$status = $request->getStr('status','1');
		$type = $request->getStr('type','1');
		$search_name = $request->getStr('search_name','');
		$conditions = array();

		if($company_id != 'all') $conditions['company_id'] = intval($company_id);
		if($status != 'all') $conditions['is_lock'] = ($status=='1'?0:1);
		if($type != 'all') $conditions['is_for_new_employer'] = ($type=='1'?1:0);
		if(!empty($search_name)) $conditions[] = array(
			'field'=>array('full_name','description'),
			'value'=>$search_name,
			'glue' => '%LIKE%',
			'bridge'=>',',
			'field_bridge' => 'OR'
		);

		if(empty($conditions)) $conditions = null;
		$admin_template = new Admin_Template();

		#Выполнено успешно
		return Ajax::_setData(array(
			'templates' => $admin_template->getTemplatesListEx($conditions, null, false)
		));

	break; #Поиск шаблонов





	/*******************************************************************
	 * Добавление шаблона
	 ******************************************************************/
	case 'template.new':

		if(!$uaccess->checkAccess('templates.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять шаблоны заявок для должностей');
		}

		$company_id			= $request->getId('company_id', 0);
		$post_uid			= $request->getId('post_uid', 0);
		$is_lock			= $request->getBool('is_lock', false);
		$is_for_new_employer= $request->getBool('is_for_new_employer', false);
		$full_name			= trim($request->getStr('full_name', ''));
		$description		= trim($request->getStr('description', ''));

		if(empty($full_name)) return Ajax::_responseError('Ошибка выполнения','Не задано название шаблона');

		$organization = new Admin_Organization();
		$admin_template = new Admin_Template();
		if($company_id>0 && !$organization->companyExists($company_id)) return Ajax::_responseError('Ошибка выполнения','Выбранная организация не существует');
		if($post_uid>0 && !$organization->postUIDExists($post_uid, $company_id)) return Ajax::_responseError('Ошибка выполнения','Выбранная должность не существует');

		if(($template_id = $admin_template->templateNew(array(
			'company_id'			=> $company_id,
			'post_uid'				=> $post_uid,
			'is_lock' 				=> (!$is_lock ? 0 : 1),
			'is_for_new_employer'	=> (!$is_for_new_employer ? 0 : 1),
			'full_name'				=> $full_name,
			'description'			=> $description
		)))===false){
			return Ajax::_responseError('Ошибка выполнения','Ошибка добавления шаблона');
		}

			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('templates.edit'),
				'acl_name'		=> 'templates.edit',
				'primary_type'	=> 'template',
				'primary_id'	=> $template_id,
				'description'	=> 'Создан шаблон',
				'value'			=> array(
					'company_id'			=> $company_id,
					'post_uid'				=> $post_uid,
					'is_lock' 				=> (!$is_lock ? 0 : 1),
					'is_for_new_employer'	=> (!$is_for_new_employer ? 0 : 1),
					'full_name'				=> $full_name,
					'description'			=> $description
				)
			));

		#Выполнено успешно
		return Ajax::_setData(array(
			'template_id'	=> $template_id,
			'template'		=> $admin_template->getTemplatesListEx($template_id,null,true,true)
		));

	break; #Добавление информационного ресурса





	/*******************************************************************
	 * Редактирование шаблона
	 ******************************************************************/
	case 'template.edit':

		if(!$uaccess->checkAccess('templates.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять шаблоны заявок для должностей');
		}

		$template_id		= $request->getId('template_id', 0);
		$company_id			= $request->getId('company_id', 0);
		$post_uid			= $request->getId('post_uid', 0);
		$is_lock			= $request->getBool('is_lock', false);
		$is_for_new_employer= $request->getBool('is_for_new_employer', false);
		$full_name			= trim($request->getStr('full_name', ''));
		$description		= trim($request->getStr('description', ''));

		if(empty($full_name)) return Ajax::_responseError('Ошибка выполнения','Не задано название шаблона');

		$organization = new Admin_Organization();
		$admin_template = new Admin_Template();
		if(!$template_id || !$admin_template->templateExists($template_id)) return Ajax::_responseError('Ошибка выполнения','Шаблон не существует');
		if($company_id>0 && !$organization->companyExists($company_id)) return Ajax::_responseError('Ошибка выполнения','Выбранная организация не существует');
		if($post_uid>0 && !$organization->postUIDExists($post_uid, $company_id)) return Ajax::_responseError('Ошибка выполнения','Выбранная должность не существует');

		if($admin_template->templateUpdate($template_id, array(
			'company_id'			=> $company_id,
			'post_uid'				=> $post_uid,
			'is_lock' 				=> (!$is_lock ? 0 : 1),
			'is_for_new_employer'	=> (!$is_for_new_employer ? 0 : 1),
			'full_name'				=> $full_name,
			'description'			=> $description
		))===false){
			return Ajax::_responseError('Ошибка выполнения','Ошибка изменения шаблона');
		}

			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('templates.edit'),
				'acl_name'		=> 'templates.edit',
				'primary_type'	=> 'template',
				'primary_id'	=> $template_id,
				'description'	=> 'Изменен шаблон ID:'.$template_id,
				'value'			=> array(
					'company_id'			=> $company_id,
					'post_uid'				=> $post_uid,
					'is_lock' 				=> (!$is_lock ? 0 : 1),
					'is_for_new_employer'	=> (!$is_for_new_employer ? 0 : 1),
					'full_name'				=> $full_name,
					'description'			=> $description
				)
			));

		#Выполнено успешно
		Ajax::_responseSuccess('Сохранение настроек шаблона','Операция выполнена успешно');
		return Ajax::_setData(array(
			'template_id'	=> $template_id,
			'template'		=> $admin_template->getTemplatesListEx($template_id,null,true,true)
		));

	break; #Редактирование шаблона





	/*******************************************************************
	 * Удаление шаблона
	 ******************************************************************/
	case 'template.delete':

		if(!$uaccess->checkAccess('templates.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять шаблоны заявок для должностей');
		}

		$template_id = $request->getId('template_id', 0);

		$admin_template = new Admin_Template();
		if(!$admin_template->templateExists($template_id)) return Ajax::_responseError('Ошибка выполнения','Шаблона не существует');

		//Проверка допустимости удаления
		if(!$admin_template->templateCanDelete($template_id)){
			return Ajax::_responseError('Ошибка выполнения','Нельзя удалить шаблон, поскольку он используется');
		}

		if(!$admin_template->templateDelete($template_id, false)){
			return Ajax::_responseError('Ошибка выполнения','Ошибка удаления шаблона');
		}

			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('templates.edit'),
				'acl_name'		=> 'templates.edit',
				'primary_type'	=> 'template',
				'primary_id'	=> $template_id,
				'description'	=> 'Удален шаблон ID:'.$template_id,
				'value'			=> array()
			));

		$request_action = 'templates.search';
		goto LABEL_TEMPLATES_AXCONTROLLER_START;


	break; #Удаление шаблона






	/*******************************************************************
	 * Сохранение объектов ИР шаблона
	 ******************************************************************/
	case 'template.save':

		if(!$uaccess->checkAccess('templates.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять шаблоны заявок для должностей');
		}

		$template_id	= $request->getId('template_id', 0);
		$tmpl_roles		= $request->getArray('a', array());

		if(empty($template_id)) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');

		$admin_template = new Admin_Template();
		if(!$admin_template->templateExists($template_id)) return Ajax::_responseError('Ошибка выполнения','Шаблон не существует');

		$iresources=array();
		$iroles=array();
		$irtypes=array();
		$result=array();

		$admin_iresource = new Admin_IResource();

		//foreach
		foreach($tmpl_roles as $irole){
			if(!is_array($irole)||count($irole)!=3) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');
			$iresource_id = intval($irole[0]);
			$irole_id = intval($irole[1]);
			$ir_type = intval($irole[2]);
			if(empty($iresource_id)||empty($irole_id)||empty($ir_type)) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');
			if(!isset($iresources[$iresource_id])){
				$iresources[$iresource_id] = $admin_iresource->iresourceExists($iresource_id);
				if(!$iresources[$iresource_id]) return Ajax::_responseError('Ошибка выполнения','Информационный ресурс ID:'.$iresource_id.' не существует');
			}
			if(!isset($iroles[$irole_id])){
				$iroles[$irole_id] = $admin_iresource->getIRole($iresource_id, $irole_id);
				if(empty($iroles[$irole_id])) return Ajax::_responseError('Ошибка выполнения','Объект доступа ID:'.$irole_id.' не существует в информационном ресурсе ID:'.$iresource_id);
			}
			if(!isset($irtypes[$ir_type])){
				$irtypes[$ir_type] = $admin_iresource->irtypeExists($ir_type);
				if(!$irtypes[$ir_type]) return Ajax::_responseError('Ошибка выполнения','Тип доступа ID:'.$ir_type.' не существует');
			}
			if(!in_array($ir_type,$iroles[$irole_id]['ir_types'])) return Ajax::_responseError('Ошибка выполнения','Тип доступа ID:'.$ir_type.' недопустим для объекта доступа ID:'.$irole_id.' в информационном ресурсе ID:'.$iresource_id);
			$result[] = array(
				$iresource_id,
				$irole_id,
				$ir_type
			);
		}//foreach


		$db = Database::getInstance('main');
		$db->transaction();

		$proles=array();

			if(!$admin_template->templateEmpty($template_id)){
				$db->rollback();
				return Ajax::_responseError('Ошибка выполнения','Ошибка очистки шаблона от предыдущих записей');
			}

			//foreach
			foreach($result as $irole){
				$proles[]=array(
					'iresource_id'	=> $irole[0],
					'irole_id'		=> $irole[1],
					'ir_type'		=> $irole[2]
				);

				if($admin_template->templateRoleNew(array(
					'template_id'	=> $template_id,
					'iresource_id'	=> $irole[0],
					'irole_id'		=> $irole[1],
					'ir_type'		=> $irole[2]
				))===false){
					$db->rollback();
					return Ajax::_responseError('Ошибка выполнения','Ошибка сохранения шаблона');
				}

			}//foreach

			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('templates.edit'),
				'acl_name'		=> 'templates.edit',
				'primary_type'	=> 'template',
				'primary_id'	=> $template_id,
				'description'	=> 'В шаблон ID:'.$template_id.' добавлены объекты доступа',
				'value'			=> $proles
			));

		#Выполнено успешно
		$db->commit();

		#Выполнено успешно
		Ajax::_responseSuccess('Сохранение шаблона','Операция выполнена успешно');
		return Ajax::_setData(array(
			'template_id'	=> $template_id,
			'tmpl_roles'	=> $admin_template->templateRoles($template_id)
		));

	break; #Сохранение объектов ИР шаблона




	/*******************************************************************
	 * Импорт объектов доступа из шаблона
	 ******************************************************************/
	case 'template.import':

		if(!$uaccess->checkAccess('templates.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять шаблоны заявок для должностей');
		}

		$template_id = $request->getId('template_id', 0);
		$import_from = $request->getId('import_from', 0);
		$import_type = $request->getEnum('import_type',array('copy','clone'),'');
		$import_copy_replace = $request->getBool('import_copy_replace', false);
		$is_clone = ($import_type == 'clone');

		if(empty($template_id)||empty($import_from)||empty($import_type)) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');

		$admin_template = new Admin_Template();
		if(!$admin_template->templateExists($template_id)) return Ajax::_responseError('Ошибка выполнения','Шаблона ID:'.$iresource_id.' не существует');
		if(!$admin_template->templateExists($import_from)) return Ajax::_responseError('Ошибка выполнения','Шаблона ID:'.$import_from.' не существует');

		$db = Database::getInstance('main');
		$db->transaction();

		//Операция клонирования
		if($is_clone){
			if(!$admin_template->templateEmpty($template_id)){
				$db->rollback();
				return Ajax::_responseError('Ошибка выполнения','Ошибка очистки шаблона от предыдущих записей');
			}
		}//Операция клонирования

		$roles = $admin_template->templateRoles($import_from, 0, true);

		$proles=array();

		//Копирование объектов доступа
		foreach($roles as $irole){
			if(!$is_clone){
				if($admin_template->templateRoleExists($template_id,$irole['iresource_id'],$irole['irole_id'])){
					if($import_copy_replace){
						if($admin_template->templateRoleUpdate($template_id,$irole['iresource_id'],$irole['irole_id'], array(
							'ir_type' => $irole['ir_type']
						))===false){
							$db->rollback();
							return Ajax::_responseError('Ошибка выполнения','Ошибка импорта объектов доступа в шаблон');
						}
					}
					continue;
				}
			}
			$proles[]=array(
				'iresource_id'	=> $irole['iresource_id'],
				'irole_id'		=> $irole['irole_id'],
				'ir_type'		=> $irole['ir_type']
			);

			if($admin_template->templateRoleNew(array(
				'template_id'	=> $template_id,
				'iresource_id'	=> $irole['iresource_id'],
				'irole_id'		=> $irole['irole_id'],
				'ir_type'		=> $irole['ir_type']
			))===false){
				$db->rollback();
				return Ajax::_responseError('Ошибка выполнения','Ошибка импорта объектов доступа в шаблон');
			}

		}//Копирование объектов доступа

			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('templates.edit'),
				'acl_name'		=> 'templates.edit',
				'primary_type'	=> 'template',
				'primary_id'	=> $template_id,
				'description'	=> 'В шаблон ID:'.$template_id.' добавлены объекты доступа',
				'value'			=> $proles
			));

		#Выполнено успешно
		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			'template_id'	=> $template_id,
			'tmpl_roles'	=> $admin_template->templateRoles($template_id)
		));

	break; #Импорт объектов доступа из шаблона




	default:
	Ajax::_responseError('/main/ajax/templates','Не найден обработчик для: '.Request::_get('action'));
}
?>