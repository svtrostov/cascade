<?php
/*==================================================================================================
Описание: Обработка AJAX запросов
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');

$request_action = Request::_get('action');

LABEL_ROUTES_AXCONTROLLER_START:


#Обработка AJAX запроса, в зависимости от запрошенного действия
switch($request_action){



	/*******************************************************************
	 * Поиск маршрутов
	 ******************************************************************/
	case 'routes.search':

		$status = $request->getStr('status','1');
		$route_type = $request->getEnum('route_type',array('all','1','2','3','4'), 'all');
		$search_name = $request->getStr('search_name','');
		$conditions = array();

		if($status != 'all') $conditions['is_lock'] = ($status=='1'?0:1);
		if($route_type != 'all') $conditions['route_type'] = $route_type;
		if(!empty($search_name)) $conditions[] = array(
			'field'=>array('full_name','description'),
			'value'=>$search_name,
			'glue' => '%LIKE%',
			'bridge'=>',',
			'field_bridge' => 'OR'
		);

		if(empty($conditions)) $conditions = null;
		$admin_route = new Admin_Route();

		#Выполнено успешно
		return Ajax::_setData(array(
			'routes' => $admin_route->getRoutesListEx($conditions, null, false)
		));

	break; #Поиск маршрутов





	/*******************************************************************
	 * Добавление маршрута
	 ******************************************************************/
	case 'route.new':

		if(!$uaccess->checkAccess('routes.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять маршруты');
		}

		$full_name		= trim($request->getStr('full_name', ''));
		$description	= trim($request->getStr('description', ''));
		$is_lock		= $request->getBool('is_lock', false);
		$route_type		= $request->getEnum('route_type',array('1','2','3','4'), 1);
		$priority		= $request->getId('priority', 0);
		$is_default		= $request->getBool('is_default', false);

		if(empty($full_name)) return Ajax::_responseError('Ошибка выполнения','Не задано название маршрута');

		$admin_route = new Admin_Route();

		if(($route_id = $admin_route->routeNew(array(
			'full_name'		=> $full_name,
			'description'	=> $description,
			'is_lock' 		=> (!$is_lock ? 0 : 1),
			'route_type'	=> $route_type,
			'priority'		=> $priority,
			'is_default'	=> (!$is_default ? 0 : 1)
		)))===false){
			return Ajax::_responseError('Ошибка выполнения','Ошибка добавления маршрута');
		}

			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('routes.edit'),
				'acl_name'		=> 'routes.edit',
				'primary_type'	=> 'route',
				'primary_id'	=> $route_id,
				'description'	=> 'Создан маршрут',
				'value'			=> array(
					'full_name'		=> $full_name,
					'description'	=> $description,
					'is_lock' 		=> (!$is_lock ? 0 : 1),
					'route_type'	=> $route_type,
					'priority'		=> $priority,
					'is_default'	=> (!$is_default ? 0 : 1)
				)
			));

		#Выполнено успешно
		return Ajax::_setData(array(
			'route_id'	=> $route_id,
			'route'		=> $admin_route->getRoutesListEx($route_id,null,true,true)
		));

	break; #Добавление маршрута






	/*******************************************************************
	 * Редактирование маршрута
	 ******************************************************************/
	case 'route.edit':

		if(!$uaccess->checkAccess('routes.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять маршруты');
		}

		$route_id		= $request->getId('route_id', 0);
		$full_name		= trim($request->getStr('full_name', ''));
		$description	= trim($request->getStr('description', ''));
		$is_lock		= $request->getBool('is_lock', false);
		$route_type		= $request->getEnum('route_type',array('1','2','3','4'), 1);
		$priority		= $request->getId('priority', 0);
		$is_default		= $request->getBool('is_default', false);

		if(empty($route_id))  return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');
		if(empty($full_name)) return Ajax::_responseError('Ошибка выполнения','Не задано название маршрута');

		$admin_route = new Admin_Route();
		if(!$route_id || !$admin_route->routeExists($route_id)) return Ajax::_responseError('Ошибка выполнения','Маршрут не существует');

		if($admin_route->routeUpdate($route_id, array(
			'full_name'		=> $full_name,
			'description'	=> $description,
			'is_lock' 		=> (!$is_lock ? 0 : 1),
			'route_type'	=> $route_type,
			'priority'		=> $priority,
			'is_default'	=> (!$is_default ? 0 : 1)
		))===false){
			return Ajax::_responseError('Ошибка выполнения','Ошибка изменения шаблона');
		}

			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('routes.edit'),
				'acl_name'		=> 'routes.edit',
				'primary_type'	=> 'route',
				'primary_id'	=> $route_id,
				'description'	=> 'Изменен маршрут',
				'value'			=> array(
					'full_name'		=> $full_name,
					'description'	=> $description,
					'is_lock' 		=> (!$is_lock ? 0 : 1),
					'route_type'	=> $route_type,
					'priority'		=> $priority,
					'is_default'	=> (!$is_default ? 0 : 1)
				)
			));

		#Выполнено успешно
		Ajax::_responseSuccess('Сохранение настроек маршрута','Операция выполнена успешно');
		return Ajax::_setData(array(
			'route_id'	=> $route_id,
			'route'		=> $admin_route->getRoutesListEx($route_id,null,true,true)
		));

	break; #Редактирование маршрута





	/*******************************************************************
	 * Удаление маршрута
	 ******************************************************************/
	case 'route.delete':

		if(!$uaccess->checkAccess('routes.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять маршруты');
		}

		$route_id = $request->getId('route_id', 0);
		if(empty($route_id))  return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');

		$admin_route = new Admin_Route();
		if(!$route_id || !$admin_route->routeExists($route_id)) return Ajax::_responseError('Ошибка выполнения','Маршрут не существует');

		//Проверка допустимости удаления маршрута
		if(!$admin_route->routeCanDelete($route_id)){
			return Ajax::_responseError('Ошибка выполнения','Нельзя удалить маршрут, поскольку он используется в заявках сотрудников');
		}

		if($admin_route->routeDelete($route_id,false)===false){
			return Ajax::_responseError('Ошибка выполнения','Ошибка удаления маршрута');
		}

			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('routes.edit'),
				'acl_name'		=> 'routes.edit',
				'primary_type'	=> 'route',
				'primary_id'	=> $route_id,
				'description'	=> 'Удален маршрут',
				'value'			=> ''
			));

		$request_action = 'routes.search';
		goto LABEL_ROUTES_AXCONTROLLER_START;

	break; #Удаление маршрута






	/*******************************************************************
	 * Загрузка схемы маршрута
	 ******************************************************************/
	case 'route.steps.load':

		$route_id = $request->getId('route_id', 0);
		if(empty($route_id))  return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');

		$admin_route = new Admin_Route();
		if(!$route_id || !$admin_route->routeExists($route_id)) return Ajax::_responseError('Ошибка выполнения','Маршрут не существует');

		if(($results = $admin_route->routeSteps($route_id))===false){
			return Ajax::_responseError('Ошибка выполнения','Ошибка получения схемы маршрута');
		}

		$db = Database::getInstance('main');

		foreach($results as $key=>$item){

			$step_type = $item['step_type'];
			$gatekeeper_type = ($item['step_type'] == 2 ? $item['gatekeeper_type'] : 0);
			$gatekeeper_role = ($item['step_type'] == 2 ? $item['gatekeeper_role'] : 0);
			$gatekeeper_id   = ($item['step_type'] == 2 ? $item['gatekeeper_id'] : 0);
			$text = '';

			switch($step_type){
				case '1': $text = 'НАЧАЛО'; break;
				case '3': $text = 'ИСПОЛНЕНО'; break;
				case '4': $text = 'ОТКЛОНЕНО'; break;
				case '2':
					switch($gatekeeper_type){
						case '1': 
							$db->prepare('SELECT CONCAT_WS(" / ",`search_name`, DATE_FORMAT(`birth_date`,"%d.%m.%Y")) FROM `employers` WHERE `employer_id`=? LIMIT 1');
							$db->bind($gatekeeper_id);
							$text .= 'Сотрудник:<br/>' . $db->result();
						break;
						case '2': $text .= 'Руководитель заявителя'; break;
						case '3': $text .= 'Руководитель организации'; break;
						case '4': $text .= 'Владелец ресурса'; break;
						case '5': 
							$db->prepare('SELECT `full_name` FROM `groups` WHERE `group_id`=? LIMIT 1');
							$db->bind($gatekeeper_id);
							$text .= 'Группа сотрудников:<br/>' . $db->result();
						break;
						case '6': 
							$db->prepare('
								SELECT 
									C.`full_name` as `company_name`,
									P.`full_name` as `post_name`,
									(SELECT PP.`short_name` FROM `posts` as PP WHERE PP.`post_id`=B.`post_id` LIMIT 1) as `boss_name`
								FROM `company_posts` as CP
									INNER JOIN `companies` as C ON C.`company_id`=CP.`company_id`
									INNER JOIN `posts` as P ON P.`post_id`=CP.`post_id`
									LEFT JOIN `company_posts` as B ON B.`post_uid`=CP.`boss_uid`
								WHERE CP.`post_uid`=? 
								LIMIT 1
							');
							$db->bind($gatekeeper_id);
							$data = $db->selectRecord();

							$text .= 'Занимающий должность:<br/>' .$data['company_name']. '<br/>'.$data['post_name'];
							if(!empty($data['boss_name'])) $text .= ' \ '.$data['boss_name'];
						break;
						case '7': $text .= 'Группа исполнителей'; break;
					}
					$text .= '<br/><br/><b>';
					switch($gatekeeper_role){
						case '1': $text .= 'Согласование'; break;
						case '2': $text .= 'Утверждение'; break;
						case '3': $text .= 'Исполнение'; break;
						case '4': $text .= 'Уведомление'; break;
					}
					$text .= '</b>';
				break;
			}
			$results[$key]['text'] = $text;

		}//foreach



		#Выполнено успешно
		return Ajax::_setData(array(
			'route_id'	=> $route_id,
			'steps'		=> $results
		));

	break; #Загрузка схемы маршрута






	/*******************************************************************
	 * Сохранение схемы маршрута
	 ******************************************************************/
	case 'route.steps.save':

		if(!$uaccess->checkAccess('routes.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять маршруты');
		}

		$route_id	= $request->getId('route_id', 0);
		$units		= $request->getArray('u', null);
		if(empty($route_id)||empty($units)) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос: request-data');

		$admin_route = new Admin_Route();
		if(!$route_id || !$admin_route->routeExists($route_id)) return Ajax::_responseError('Ошибка выполнения','Маршрут не существует');

		if(($results = $admin_route->routeSteps($route_id))===false){
			return Ajax::_responseError('Ошибка выполнения','Ошибка получения шагов маршрута');
		}

		$uids=array();
		$begin_block = 0;
		$end_true_block = 0;
		$end_false_block = 0;
		$validator = new Validator(null, array(
			'unit_uid' => array('required' => true,'type' => 'uint'),
			'unit_type' => array('required' => true,'type' => 'uint','min'=>1,'max'=>4),
			'gatekeeper_role' => array('required' => true,'type' => 'uint','max'=>4),
			'gatekeeper_id' => array('required' => true,'type' => 'uint'),
			'unit_uid' => array('required' => true,'type' => 'uint'),
			'step_yes' => array('required' => true,'type' => 'uint'),
			'step_no' => array('required' => true,'type' => 'uint')
		));

		$admin_employers = new Admin_Employers();
		$organization = new Admin_Organization();
		$db = Database::getInstance('main');

		//Просмотр элементов схемы маршрута
		foreach($units as $unit){
			if(!is_array($unit) || count($unit)!=9){
				return Ajax::_responseError('Ошибка выполнения','Некорректный запрос: unit-data');
			}
			$unit_uid			= $unit[0];
			$unit_type			= $unit[1];
			$gatekeeper_type	= $unit[2];
			$gatekeeper_role	= $unit[3];
			$gatekeeper_id		= $unit[4];
			$step_yes			= $unit[5];
			$step_no			= $unit[6];
			$x					= max(0,intval($unit[7]));
			$y					= max(0,intval($unit[8]));

			$validator->setFields(array(
				array('unit_uid',$unit_uid),
				array('unit_type',$unit_type),
				array('gatekeeper_type',$gatekeeper_type),
				array('gatekeeper_role',$gatekeeper_role),
				array('gatekeeper_id',$gatekeeper_id),
				array('step_yes',$step_yes),
				array('step_no',$step_no)
			));
			if(!$validator->validate()){
				return Ajax::_responseError('Ошибка выполнения','Некорректный запрос: unit-validate');
			}

			$step_uid = $admin_route->getStepUID($route_id, $unit_type, $gatekeeper_role, $gatekeeper_type, $gatekeeper_id);
			if(strcmp($unit_uid,$step_uid)!=0){
				return Ajax::_responseError('Ошибка выполнения','Некорректный запрос: unit-uid');
			}
			if(isset($uids[$step_uid])){
				return Ajax::_responseError('Ошибка выполнения','Некорректный запрос: double-uid');
			}

			switch($unit_type){
				case 1:
					$begin_block = $step_uid;
				break;
				case 2:
					switch($gatekeeper_type){
						case 1:
							if(!$admin_employers->employerExists($gatekeeper_id)){return Ajax::_responseError('Ошибка выполнения','Некорректный запрос: Сотрудник ID:['.$gatekeeper_id.'] не существует');}
						break;
						case 5:
							if(!$admin_employers->groupExists($gatekeeper_id)){return Ajax::_responseError('Ошибка выполнения','Некорректный запрос: Группа ID:['.$gatekeeper_id.'] не существует');}
						break;
						case 6:
							if(!$organization->postUIDExists($gatekeeper_id)){return Ajax::_responseError('Ошибка выполнения','Некорректный запрос: Должности UID:['.$gatekeeper_id.'] не существует');}
						break;
					}//switch gatekeeper_type
				break;
				case 3:
					$end_true_block = $step_uid;
				break;
				case 4:
					$end_false_block = $step_uid;
				break;
			}//switch unit_type

			$uids[$step_uid] = array(
				'route_id'			=> $route_id,
				'step_uid' 			=> $step_uid,
				'step_type'		 	=> $unit_type,
				'gatekeeper_type'	=> $gatekeeper_type,
				'gatekeeper_role'	=> $gatekeeper_role,
				'gatekeeper_id'		=> $gatekeeper_id,
				'pos_x'				=> $x,
				'pos_y'				=> $y,
				'step_yes'			=> $step_yes,
				'step_no'			=> $step_no
			);

		}//foreach units

		if(!$begin_block) return Ajax::_responseError('Ошибка выполнения','Не найден блок начала маршрута');
		if(!$end_true_block) return Ajax::_responseError('Ошибка выполнения','Не найден блок успешного завершения маршрута, когда заявка исполнена');
		if(!$end_false_block) return Ajax::_responseError('Ошибка выполнения','Не найден блок завершения маршрута, когда заявка отклонена');

		#Проверка маршрута по step_yes
		$uid = $begin_block;
		$we_be_here=array($uid);
		while(strcmp($end_true_block,$uid)!=0){
			if(!is_array($uids[$uid])) return Ajax::_responseError('Ошибка выполнения','Не найден блок UID='.$uid);
			$uid = $uids[$uid]['step_yes'];
			if(!$uid){
				return Ajax::_responseError('Ошибка выполнения','Маршрут не закончен, имеющаяся схема не позволяет достигнуть блока успешного завершения маршрута');
			}
			if(in_array($uid,$we_be_here)){
				return Ajax::_responseError('Ошибка выполнения','Маршрут в замкнутом бесконечном цикле');
			}
			$we_be_here[]=$uid;
		}


		//Вычисляем удаляемые блоки из маршрута
		$delete_steps = array_diff($admin_route->routeSteps($route_id,'step_uid'), array_keys($uids));
		//Проверяем допустимость удаления блоков
		if(!empty($delete_steps)){
			if(!$admin_route->routeStepsCanDelete($route_id, $delete_steps)){
				return Ajax::_responseError('Ошибка выполнения','Нельзя сохранить маршрут, поскольку на удаляемых этапах согласования сейчас нахотяся заявки.');
			}
		}


		$db->transaction();

			#Удаление существующей схемы маршрута
			if(!$admin_route->routeStepsClear($route_id, false)){
				$db->rollback();
				return Ajax::_responseError('Ошибка выполнения','Ошибка удаления существующей схемы маршрута');
			}

			#Сохранение маршрута в базе данных
			foreach($uids as $uid=>$unit){
				if(!$admin_route->routeStepNew($unit)){
					$db->rollback();
					return Ajax::_responseError('Ошибка выполнения','Ошибка добавления элемента UID:['.$uid.']');
				}
			}//foreach uids


			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('routes.edit'),
				'acl_name'		=> 'routes.edit',
				'primary_type'	=> 'route',
				'primary_id'	=> $route_id,
				'description'	=> 'Изменена схема маршрута',
				'value'			=> $uids
			));

		#Выполнено успешно
		$db->commit();
		Ajax::_responseSuccess('Сохранение схемы маршрута','Операция выполнена успешно');
		return Ajax::_setData(array(
			'route_id'	=> $route_id
		));

	break; #Сохранение схемы маршрута




	/*******************************************************************
	 * Добавление параметра маршрута
	 ******************************************************************/
	case 'route.param.new':

		if(!$uaccess->checkAccess('routes.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять маршруты');
		}

		$route_id		= $request->getId('route_id', 0);
		$for_employer	= $request->getId('for_employer', 0);
		$for_resource	= $request->getId('for_resource', 0);
		$for_company	= $request->getId('for_company', 0);
		$for_post		= $request->getId('for_post', 0);
		$for_group		= $request->getId('for_group', 0);

		if(empty($route_id))  return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');

		$admin_route = new Admin_Route();
		if(!$route_id || !$admin_route->routeExists($route_id)) return Ajax::_responseError('Ошибка выполнения','Маршрут не существует');

		$organization = new Admin_Organization();
		$admin_iresource = new Admin_IResource();
		$admin_employers = new Admin_Employers();
		if($for_employer>0 && !$admin_employers->employerExists($for_employer)) return Ajax::_responseError('Ошибка выполнения','Выбранный сотрудник не существует');
		if($for_company>0 && !$organization->companyExists($for_company)) return Ajax::_responseError('Ошибка выполнения','Выбранная организация не существует');
		if($for_post>0 && !$organization->postUIDExists($for_post, $for_company)) return Ajax::_responseError('Ошибка выполнения','Выбранная должность не существует');
		if($for_group>0 && !$admin_employers->groupExists($for_group)) return Ajax::_responseError('Ошибка выполнения','Выбранная группа не существует');
		if($for_resource>0 && !$admin_iresource->iresourceExists($for_resource)) return Ajax::_responseError('Ошибка выполнения','Выбранный информационный ресурс не существует');

		if(($param_id = $admin_route->routeParamNew(array(
			'route_id'		=> $route_id,
			'for_employer'	=> $for_employer,
			'for_company' 	=> $for_company,
			'for_post'		=> $for_post,
			'for_group'		=> $for_group,
			'for_resource'	=> $for_resource
		)))===false){
			return Ajax::_responseError('Ошибка выполнения','Ошибка добавления параметра маршрута');
		}

			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('routes.edit'),
				'acl_name'		=> 'routes.edit',
				'primary_type'	=> 'routeparam',
				'primary_id'	=> $param_id,
				'secondary_type'=> 'route',
				'secondary_id'	=> $route_id,
				'description'	=> 'Добавлен параметр маршрута',
				'value'			=> array(
					'param_id'		=> $param_id,
					'for_employer'	=> $for_employer,
					'for_company' 	=> $for_company,
					'for_post'		=> $for_post,
					'for_group'		=> $for_group,
					'for_resource'	=> $for_resource
				)
			));

		#Выполнено успешно
		return Ajax::_setData(array(
			'route_id'	=> $route_id,
			'params'	=> $admin_route->routeParamsEx($route_id)
		));

	break; #Добавление параметра маршрута





	/*******************************************************************
	 * Удаление параметра маршрута
	 ******************************************************************/
	case 'route.param.delete':

		if(!$uaccess->checkAccess('routes.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять маршруты');
		}

		$route_id		= $request->getId('route_id', 0);
		$param_id		= $request->getId('param_id', 0);

		if(empty($param_id)||empty($route_id))  return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');

		$admin_route = new Admin_Route();
		if(!$route_id || !$admin_route->routeExists($route_id)) return Ajax::_responseError('Ошибка выполнения','Маршрут не существует');

		if($admin_route->routeParamDelete($route_id, $param_id)===false){
			return Ajax::_responseError('Ошибка выполнения','Ошибка удаления параметра маршрута');
		}

		Protocol::_add(array(
			'action_name'	=> $request_action,
			'acl_id'		=> $uaccess->getObjectIdFormName('routes.edit'),
			'acl_name'		=> 'routes.edit',
			'primary_type'	=> 'routeparam',
			'primary_id'	=> $param_id,
			'secondary_type'=> 'route',
			'secondary_id'	=> $route_id,
			'description'	=> 'Удален параметр маршрута ID:'.$route_id,
			'value'			=> ''
		));

		#Выполнено успешно
		return Ajax::_setData(array(
			'route_id'	=> $route_id,
			'params'	=> $admin_route->routeParamsEx($route_id)
		));

	break; #Удаление параметра маршрута







	default:
	Ajax::_responseError('/main/ajax/routes','Не найден обработчик для: '.Request::_get('action'));
}
?>