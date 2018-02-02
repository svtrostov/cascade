<?php
/*==================================================================================================
Описание: Обработка AJAX запросов
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');


$request_action = Request::_get('action');

LABEL_REQUESTS_AXCONTROLLER_START:

#Обработка AJAX запроса, в зависимости от запрошенного действия
switch($request_action){



	/*******************************************************************
	 * Поиск заявок
	 ******************************************************************/
	case 'requests.search':

		$company_id = $request->getStr('company_id','all');
		$iresource_id = $request->getStr('iresource_id','all');
		$route_id = $request->getStr('route_id','all');
		$status = $request->getEnum('status',array('0','1','2','100'),'1');
		$type = $request->getEnum('type',array('all','2','3'),'all');
		$period = $request->getEnum('period',array('all','1','7','30','90','365'),'7');
		$limit = $request->getId('limit',100);


		$search_term = trim($request->getStr('search_term',''));
		$term_type = $request->getEnum('term_type',array('request','curator','employer','gatekeeper'),'curator');
		if(!empty($search_term)&&is_numeric($search_term)) $term_type = 'request';

		$conds = array();

		if($company_id != 'all') $conds['company_id'] = intval($company_id);
		if($iresource_id != 'all') $conds['iresource_id'] = intval($iresource_id);
		if($route_id != 'all') $conds['route_id'] = intval($route_id);
		if($period != 'all') $conds['period'] = intval($period);
		$conds['status'] = intval($status);
		if($type != 'all') $conds['type'] = intval($type);
		if(!empty($search_term)){
			switch($term_type){
				case 'request': $conds = array('request_id'=>intval($search_term)); break;
				case 'curator': $conds['curator_name']=$search_term; break;
				case 'employer': $conds['employer_name']=$search_term; break;
				case 'gatekeeper': $conds['gatekeeper_name']=$search_term; break;
			}
		}
		$is_history = empty($conds['status']) ? true : ($conds['status']==1||$conds['status']==2 ? false : true);

		$db = Database::getInstance('main');

		$sql_select = '
			SELECT 
				RIR.`iresource_id` as `iresource_id`,
				RIR.`route_id` as `route_id`,
				RIR.`route_status` as `route_status`,
				RIR.`route_status_desc` as `route_status_desc`,
				REQ.`request_id` as `request_id`,
				REQ.`request_type` as `request_type`,
				REQ.`company_id` as `company_id`,
				REQ.`curator_id` as `curator_id`,
				REQ.`employer_id` as `employer_id`,
				REQ.`phone` as `phone`,
				REQ.`email` as `email`,
				DATE_FORMAT(REQ.`timestamp`,"%d.%m.%Y") as `create_date`,
				C.`full_name` as `company_name`,
				EE.`search_name` as `employer_name`,
				EC.`search_name` as `curator_name`,
				CP.`post_uid` as `post_uid`,
				P.`full_name` as `post_name`,
				IR.`full_name` as `iresource_name`
		';

		$inner_requests = 'INNER JOIN `requests` as REQ ON REQ.`request_id`=RIR.`request_id`';
		$inner_request_steps = '';
		$inner_companies = 'INNER JOIN `companies` as C ON C.`company_id` = REQ.`company_id`';
		$inner_iresources = 'INNER JOIN `iresources` as IR ON IR.`iresource_id` = RIR.`iresource_id`';
		$inner_employer = 'INNER JOIN `employers` as EE ON EE.`employer_id` = REQ.`employer_id`';
		$inner_curator = 'INNER JOIN `employers` as EC ON EC.`employer_id` = REQ.`curator_id`';
		$inner_company_posts = 'INNER JOIN `company_posts` as CP ON CP.`post_uid`=REQ.`post_uid`';
		$inner_posts = 'INNER JOIN `posts` as P ON P.`post_id`=CP.`post_id`';
		$inner_gatekeeper_name = '';
		$sql_from = 'FROM `'.($is_history ? 'request_iresources_hist' : 'request_iresources').'` as RIR';
		$sql_where = '';


		foreach($conds as $key=>$value){
			switch($key){

				case 'request_id':
					$inner_requests.=' AND REQ.`request_id`='.$value;
				break;

				case 'company_id':
					$inner_requests.=' AND REQ.`company_id`='.$value;
				break;

				case 'type':
					$inner_requests.=' AND REQ.`request_type`='.$value;
				break;

				case 'route_id':
					$sql_where.=(empty($sql_where)?'WHERE ':' AND ').'RIR.`route_id`='.$value;
				break;

				case 'iresource_id':
					$sql_where.=(empty($sql_where)?'WHERE ':' AND ').'RIR.`iresource_id`='.$value;
				break;

				case 'status':
					$sql_where.=(empty($sql_where)?'WHERE ':' AND ').'RIR.`route_status`='.$value;
				break;

				case 'period':
					$inner_requests.=' AND REQ.`timestamp`>"'.date('Y-m-d H:i:s',(time()-$value*86400)).'"';
				break;

				case 'curator_name':
					$inner_curator.=' AND EC.`search_name` LIKE "'.$db->getQuotedValue($value,false).'%"';
				break;

				case 'employer_name':
					$inner_employer.=' AND EE.`search_name` LIKE "'.$db->getQuotedValue($value,false).'%"';
				break;

				case 'gatekeeper_name':
					$inject_request_steps = true;
					$inner_request_steps ='INNER JOIN `'.($is_history ? 'request_steps_hist' : 'request_steps').'` as RSTEP ON RSTEP.`request_id`=RIR.`request_id` AND RSTEP.`iresource_id`=RIR.`iresource_id` AND RSTEP.`step_complete`=1';
					$inner_gatekeeper_name = 'INNER JOIN `employers` as EG ON EG.`employer_id` IN (RSTEP.`gatekeeper_id`,RSTEP.`assistant_id`) AND EG.`search_name` LIKE "'.$db->getQuotedValue($value,false).'%"';
				break;

			}//switch
		}//foreach

		$sql =
			$sql_select."\n\t".
			$sql_from."\n\t".
			$inner_requests."\n\t".
			$inner_request_steps."\n\t".
			$inner_companies."\n\t".
			$inner_iresources."\n\t".
			$inner_employer."\n\t".
			$inner_curator."\n\t".
			$inner_company_posts."\n\t".
			$inner_posts."\n\t".
			$inner_gatekeeper_name."\n\t".
			$sql_where.' LIMIT '.$limit;


		#Выполнено успешно
		return Ajax::_setData(array(
			'requests' => $db->select($sql)
		));

	break; #Поиск шаблонов






	/*******************************************************************
	 * Добавление комментария к заявке
	 ******************************************************************/
	case 'comment.add':

		if(!$uaccess->checkAccess('requests.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять заявки');
		}

		//Идентификатор заявки
		if( ($request_id = Request::_getId('request_id', 0)) == 0){
			return Ajax::_responseError('Ошибка выполнения','Номер заявки указан некорректно');
		}

		//Идентификатор информационного ресурса
		if( ($iresource_id = Request::_getId('iresource_id', 0)) == 0){
			return Ajax::_responseError('Ошибка выполнения','Идентификатор информационного ресурса указан некорректно');
		}

		$admin_request = new Admin_Request();
		$iresource = $admin_request->requestIResourcesEx($request_id,$iresource_id,false);
		if(empty($iresource)) return Ajax::_responseError('Ошибка выполнения','Информационный ресурс ID:'.$iresource_id.' не присутствует в заявке ID:'.$request_id);
		if($iresource['route_status'] == 0 || $iresource['route_status'] == 100) return Ajax::_responseError('Ошибка выполнения','Нельзя оставлять комментарии в исполненной или отклоненной заявке');

		//Комментарий
		$comment = htmlspecialchars(trim(Request::_getStr('comment', '')));
		if(empty($comment)){
			return Ajax::_responseError('Ошибка выполнения','Не задан комментарий');
		}

		$admin_request = new Admin_Request();
		if(($comment_id = $admin_request->commentAdd(array(
			'request_id'	=> $request_id,
			'iresource_id'	=> $iresource_id,
			'comment' 		=> $comment,
			'employer_id'	=> User::_getEmployerID(),
			'timestamp'		=> date("Y-m-d H:i:s")
		)))===false){
			return Ajax::_responseError('Ошибка выполнения','Ошибка добавления комментария');
		}

		#Выполнено успешно
		return Ajax::_setData(array(
			'request_id'	=> $request_id,
			'iresource_id'	=> $iresource_id,
			'comments'		=> $admin_request->requestIResourceComments($request_id, $iresource_id)
		));

	break; #Добавление комментария к заявке





	/*******************************************************************
	 * Сохранение объектов ИР заявки
	 ******************************************************************/
	case 'roles.save':

		if(!$uaccess->checkAccess('requests.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять заявки');
		}

		$request_id		= $request->getId('request_id', 0);
		$iresource_id	= $request->getId('iresource_id', 0);
		$roles			= $request->getArray('a', array());
		$gatekeeper_id	= User::_getEmployerID();

		if(empty($request_id)||empty($iresource_id)||empty($roles)) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');

		$admin_request = new Admin_Request();
		$admin_iresource = new Admin_IResource();

		$request_info = $admin_request->requestInfo($request_id);
		if(empty($request_info)) return Ajax::_responseError('Ошибка выполнения','Заявка ID:'.$request_id.' не существует');
		if(!$admin_iresource->iresourceExists($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Информационный ресурс ID:'.$iresource_id.' не существует');
		if(!$admin_request->requestIResourceExists($request_id,$iresource_id)) return Ajax::_responseError('Ошибка выполнения','Информационный ресурс ID:'.$iresource_id.' не присутствует в заявке ID:'.$request_id);
		$iresource = $admin_request->requestIResourcesEx($request_id,$iresource_id,false);
		if(empty($iresource)) return Ajax::_responseError('Ошибка выполнения','Информационный ресурс ID:'.$iresource_id.' не присутствует в заявке ID:'.$request_id);
		if($iresource['route_status'] == 0 || $iresource['route_status'] == 100) return Ajax::_responseError('Ошибка выполнения','Нельзя изменить запрашиваемй функционал в исполненной или отклоненной заявке');

		$request_type = $request_info['request_type'];
		$iroles=array();
		$irtypes=array(0=>true);
		$result=array();

		//foreach
		foreach($roles as $irole){
			if(!is_array($irole)||count($irole)!=2) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');
			$irole_id = intval($irole[0]);
			$ir_type = intval($irole[1]);
			if(empty($irole_id)) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');

			if(!isset($iroles[$irole_id])){
				$iroles[$irole_id] = $admin_iresource->getIRole($iresource_id, $irole_id);
				if(empty($iroles[$irole_id])) return Ajax::_responseError('Ошибка выполнения','Объект доступа ID:'.$irole_id.' не существует в информационном ресурсе ID:'.$iresource_id);
			}

			if($request_type == 3){
				$ir_type = ($ir_type == 0 ? 0 : 1);
			}else{
				if(!isset($irtypes[$ir_type])){
					$irtypes[$ir_type] = $admin_iresource->irtypeExists($ir_type);
					if(!$irtypes[$ir_type]) return Ajax::_responseError('Ошибка выполнения','Тип доступа ID:'.$ir_type.' не существует');
				}
				if($ir_type > 0 && !in_array($ir_type,$iroles[$irole_id]['ir_types'])) return Ajax::_responseError('Ошибка выполнения','Тип доступа ID:'.$ir_type.' недопустим для объекта доступа ID:'.$irole_id.' в информационном ресурсе ID:'.$iresource_id);
			}

			$result[] = array(
				$irole_id,
				$ir_type
			);
		}//foreach


		$db = Database::getInstance('main');
		$db->transaction();

		$protocol_roles = array();

			//foreach
			foreach($result as $irole){
				$protocol_roles[]=array(
					'request_id'	=> $request_id,
					'iresource_id'	=> $iresource_id,
					'irole_id'		=> $irole[0],
					'ir_selected'	=> $irole[1],
					'gatekeeper_id'	=> $gatekeeper_id
				);
				if($admin_request->setIRole(array(
					'request_id'	=> $request_id,
					'iresource_id'	=> $iresource_id,
					'irole_id'		=> $irole[0],
					'ir_selected'	=> $irole[1],
					'gatekeeper_id'	=> $gatekeeper_id
				))===false){
					$db->rollback();
					return Ajax::_responseError('Ошибка выполнения','Ошибка сохранения заявки');
				}
			}//foreach

			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('requests.edit'),
				'acl_name'		=> 'requests.edit',
				'primary_type'	=> 'request',
				'primary_id'	=> $request_id,
				'description'	=> 'Изменены запрашиваемые в заявке объекты доступа',
				'value'			=> $protocol_roles
			));

		#Выполнено успешно
		$db->commit();

		#Выполнено успешно
		Ajax::_responseSuccess('Сохранение заявки','Операция выполнена успешно');
		return Ajax::_setData(array(
			'request_id'	=> $request_id,
			'iresource_id'	=> $iresource_id,
			'roles'			=> $admin_request->requestIResourceRoles($request_id, $iresource_id)
		));

	break; #Сохранение объектов ИР заявки








	/*******************************************************************
	 * Остановка/возобновление/отмена завки
	 ******************************************************************/
	case 'request.step.stop':
	case 'request.step.pause':
	case 'request.step.continue':

		if(!$uaccess->checkAccess('requests.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять заявки');
		}

		$request_id		= $request->getId('request_id', 0);
		$iresource_id	= $request->getId('iresource_id', 0);
		$route_id		= $request->getId('route_id', 0);
		$step_uid		= $request->getId('step_uid', 0);

		if(empty($request_id)||empty($iresource_id)||empty($route_id)||empty($step_uid)) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');

		$employer_id	= User::_getEmployerID();
		$employer_name	= User::_get('search_name');

		$need_status= ($request_action == 'request.step.pause' ? 2 : ($request_action == 'request.step.stop' ? 0 : 1));
		$need_status_desc = '';
		$step_action='';
		switch($need_status){
			case 0: $step_action='stop'; $need_status_desc='Обработка заявки отменена администратором: '.$employer_name; break;
			case 1: $step_action='continue'; $need_status_desc='В работе (статус установлен администратором: '.$employer_name.')'; break;
			case 2: $step_action='pause'; $need_status_desc='Приостановлена (статус установлен администратором: '.$employer_name.')'; break;
		}

		$main_request = new Main_Request();
		$admin_request = new Admin_Request();
		$admin_iresource = new Admin_IResource();

		if(!$admin_request->requestExists($request_id)) return Ajax::_responseError('Ошибка выполнения','Заявка ID:'.$request_id.' не существует');
		if(!$admin_iresource->iresourceExists($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Информационный ресурс ID:'.$iresource_id.' не существует');
		if(!$admin_request->requestIResourceExists($request_id,$iresource_id)) return Ajax::_responseError('Ошибка выполнения','Информационный ресурс ID:'.$iresource_id.' не присутствует в заявке ID:'.$request_id);
		$iresource = $admin_request->requestIResourcesEx($request_id,$iresource_id,false);
		if(empty($iresource)) return Ajax::_responseError('Ошибка выполнения','Информационный ресурс ID:'.$iresource_id.' не присутствует в заявке ID:'.$request_id);
		if($iresource['route_status'] == 0 || $iresource['route_status'] == 100) return Ajax::_responseError('Ошибка выполнения','Нельзя изменить статус исполненной или отклоненной заявки');

		$db = Database::getInstance('main');
		$db->transaction();

		$main_request->open(array(
			'request_id' => $request_id,
			'iresource_id' => $iresource_id,
			'fullinfo'=>false,
			'iresourceforupdate' => true
		));

		if($main_request->requestProcessStatus($iresource_id, $step_action, $need_status_desc, $employer_id) === false){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/request/'.$request_action.' /toStep/false');
		}

		Protocol::_add(array(
			'action_name'	=> $request_action,
			'acl_id'		=> $uaccess->getObjectIdFormName('requests.edit'),
			'acl_name'		=> 'requests.edit',
			'primary_type'	=> 'request',
			'primary_id'	=> $request_id,
			'description'	=> 'Изменен статус заявки на '.$step_action,
			'value'			=> array(
				'request_id'	=> $request_id,
				'iresource_id'	=> $iresource_id
			)
		));

		$db->commit();

		$iresource = $admin_request->requestIResourcesEx($request_id,$iresource_id,false);
		$iresource['steps'] = $admin_request->requestIResourceStepsHistory($request_id, $iresource_id);
		$iresource['route'] = $admin_request->requestIResourceRouteSteps($request_id, $iresource_id, $route_id);

		#Выполнено успешно
		Ajax::_responseSuccess('Изменение статуса процесса согласования','Операция выполнена успешно');
		return Ajax::_setData($iresource);
		return Ajax::_setData(array(
			'request_id'	=> $request_id,
			'iresource_id'	=> $iresource_id,
			'route_status'	=> $need_status,
			'route_status_desc'	=> $need_status_desc,
			'steps'			=> $admin_request->requestIResourceStepsHistory($request_id, $iresource_id),
			'route'			=> $admin_request->requestIResourceRouteSteps($request_id, $iresource_id, $route_id)
		));

	break; #Остановка/возобновление/отмена завки






	/*******************************************************************
	 * Одобрение/отклонение завки
	 ******************************************************************/
	case 'request.step.approve':
	case 'request.step.decline':

		if(!$uaccess->checkAccess('requests.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять заявки');
		}

		$request_id		= $request->getId('request_id', 0);
		$iresource_id	= $request->getId('iresource_id', 0);
		$route_id		= $request->getId('route_id', 0);
		$rstep_id		= $request->getId('rstep_id', 0);
		$step_uid		= $request->getId('step_uid', 0);

		if(empty($request_id)||empty($iresource_id)||empty($route_id)||empty($step_uid)||empty($rstep_id)) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос: empty');

		$employer_id	= User::_getEmployerID();
		$employer_name	= User::_get('search_name');

		$step_action = ($request_action == 'request.step.approve' ? 'approve' : 'decline');

		$main_request = new Main_Request();
		$admin_request = new Admin_Request();
		$admin_iresource = new Admin_IResource();

		if(!$admin_request->requestExists($request_id)) return Ajax::_responseError('Ошибка выполнения','Заявка ID:'.$request_id.' не существует');
		if(!$admin_iresource->iresourceExists($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Информационный ресурс ID:'.$iresource_id.' не существует');
		if(!$admin_request->requestIResourceExists($request_id,$iresource_id)) return Ajax::_responseError('Ошибка выполнения','Информационный ресурс ID:'.$iresource_id.' не присутствует в заявке ID:'.$request_id);
		$iresource = $admin_request->requestIResourcesEx($request_id,$iresource_id,false);
		if(empty($iresource)) return Ajax::_responseError('Ошибка выполнения','Информационный ресурс ID:'.$iresource_id.' не присутствует в заявке ID:'.$request_id);
		if($iresource['current_step']!=$rstep_id) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос: rstep_id');

		$db = Database::getInstance('main');
		$db->transaction();

		$main_request->open(array(
			'request_id' => $request_id,
			'iresource_id' => $iresource_id,
			'fullinfo'=>false,
			'iresourceforupdate' => true
		));

		//На следующий шаг
		if($main_request->toStep($iresource_id, $step_action,  $employer_id) === false){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/request/request.'.$step_action.' /toStep/false');
		}

		//Отметка об успешном выполнении текушего шага
		if($main_request->setRouteStep(array(
			'request_id'	=> $request_id,
			'iresource_id'	=> $iresource_id,
			'gatekeeper_id'	=> $employer_id,
			'assistant_id'	=> 0,
			'step_complete'	=> 1,
			'is_approved'	=> ($request_action == 'request.step.approve' ? 1 : 0),
			'rstep_id'		=> $iresource['current_step'],
			'step_uid'		=> $step_uid
		)) === false){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/request/request.'.$step_action.' /setRouteStep/false');
		}

		Protocol::_add(array(
			'action_name'	=> $request_action,
			'acl_id'		=> $uaccess->getObjectIdFormName('requests.edit'),
			'acl_name'		=> 'requests.edit',
			'primary_type'	=> 'request',
			'primary_id'	=> $request_id,
			'description'	=> 'Изменены статус процесса согласования заявки, заявка '.($request_action == 'request.step.approve' ? 'согласована' : 'отклонена'),
			'value'			=> array(
				'request_id'	=> $request_id,
				'iresource_id'	=> $iresource_id
			)
		));

		$db->commit();

		$iresource = $admin_request->requestIResourcesEx($request_id,$iresource_id,false);
		$iresource['steps'] = $admin_request->requestIResourceStepsHistory($request_id, $iresource_id);
		$iresource['route'] = $admin_request->requestIResourceRouteSteps($request_id, $iresource_id, $route_id);

		#Выполнено успешно
		Ajax::_responseSuccess('Изменение статуса процесса согласования','Операция выполнена успешно');
		return Ajax::_setData($iresource);

	break; #Одобрение/отклонение завки






	/*******************************************************************
	 * Создание заявки на блокировку доступа
	 ******************************************************************/
	case 'request.lock':

		if(!$uaccess->checkAccess('requests.lock', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете создавать заявки на блокировку доступа');
		}

		$employer_id	= $request->getId('employer_id', 0);
		$post_uid		= $request->getId('post_uid', 0);
		$a				= $request->getArray('a', array());
		if(empty($employer_id)||empty($post_uid)||empty($a)) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос: empty');

		$admin_employers = new Admin_Employers();
		if(!$admin_employers->employerExists($employer_id)) return Ajax::_responseError('Ошибка выполнения','Сотрудник ID:'.$employer_id.' не существует');
		$post_info = $admin_employers->employerPostInfo($employer_id, $post_uid);
		if(empty($post_info)) return Ajax::_responseError('Ошибка выполнения','Сотрудник ID:'.$employer_id.' не занимает выбранную должность');

		$iresources=array();
		$iroles=array();
		$result=array();

		$admin_iresource = new Admin_IResource();
		$admin_matrix = new Admin_Matrix();

		//foreach
		foreach($a as $irole){
			if(!is_array($irole)||count($irole)!=3) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');
			$iresource_id = intval($irole[0]);
			$irole_id = intval($irole[1]);
			$ir_type = intval($irole[2]);
			if(empty($iresource_id)||empty($irole_id)||empty($ir_type)) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');
			if(!isset($iresources[$iresource_id])){
				if(!$admin_iresource->iresourceExists($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Информационный ресурс ID:'.$iresource_id.' не существует');
				$iresources[$iresource_id] = array();
			}
			if(!isset($iroles[$irole_id])){
				$iroles[$irole_id] = $admin_iresource->getIRole($iresource_id, $irole_id);
				if(empty($iroles[$irole_id])) return Ajax::_responseError('Ошибка выполнения','Объект доступа ID:'.$irole_id.' не существует в информационном ресурсе ID:'.$iresource_id);
				if(!$admin_matrix->employerIResourceRoleExists($employer_id, $post_uid, $iresource_id, $irole_id)) return Ajax::_responseError('Ошибка выполнения','Сотрудник на указанной должности не имеет доступа к объекту доступа ID:'.$irole_id.' из информационного ресурса ID:'.$iresource_id);
			}

			$iresources[$iresource_id][] = $irole_id;

		}//foreach


		$main_request = new Main_Request();

		$db = Database::getInstance('main');
		$db->transaction();

			//Создание заявки
			$request_id = $main_request->create(array(
				'request_type'	=> 3,
				'employer_id'	=> $employer_id,
				'curator_id'	=> User::_getEmployerID(),
				'post_uid'		=> $post_uid,
				'company_id'	=> $post_info['company_id'],
				'phone'			=> User::_get('phone'),
				'email'			=> User::_get('email')
			));

			//Добавление в заявку информационных ресурсов
			foreach($iresources as $iresource_id=>$access_list){

				$rires_id = $main_request->setIResource(array(
					'iresource_id'	=> $iresource_id,
					'route_type'	=> 4
				));
				if(!$rires_id){
					$db->rollback();
					return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/request/request.lock /setIResource/iresource_id='.$iresource_id.'/false');
				}

				//Добавление в заявку объектов доступа
				foreach($access_list as $irole_id){

					$rrole_id = $main_request->setIRole(array(
						'iresource_id'		=> $iresource_id,
						'irole_id'			=> $irole_id,
						'ir_type'			=> 1,
						'ir_selected'		=> 1,
						'update_type'		=> 0
					));
					if(!$rrole_id){
						$db->rollback();
						return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/request/request.lock /setIRole /iresource_id='.$iresource_id.'/irole_id='.$irole_id.'/false');
					}
				}//Добавление в заявку объектов доступа

			}//Добавление в заявку информационных ресурсов


			//На первый шаг согласования
			$main_request->toFirstStep();

		Protocol::_add(array(
			'action_name'	=> $request_action,
			'acl_id'		=> $uaccess->getObjectIdFormName('requests.lock'),
			'acl_name'		=> 'requests.lock',
			'primary_type'	=> 'request',
			'primary_id'	=> $request_id,
			'description'	=> 'Создана заявка на блокировку доступа',
			'value'			=> array(
				'request_type'	=> 3,
				'employer_id'	=> $employer_id,
				'curator_id'	=> User::_getEmployerID(),
				'post_uid'		=> $post_uid,
				'company_id'	=> $post_info['company_id'],
				'roles'			=> $iresources
			)
		));

		#Заявка добавлена успешно
		$db->commit();

		#Выполнено успешно
		Ajax::_responseSuccess('Заявка на блокировку доступа','Операция выполнена успешно');
		return Ajax::_setData(array('request_id'=>$request_id));

	break; #Создание заявки на блокировку доступа




	default:
	Ajax::_responseError('/admin/ajax/requests','Не найден обработчик для: '.Request::_get('action'));
}
?>