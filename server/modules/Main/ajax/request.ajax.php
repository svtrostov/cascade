<?php
/*==================================================================================================
Описание: Обработка AJAX запросов
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');

require_once(DIR_FUNCTIONS.'/employer.functions.php');

#Обработка AJAX запроса, в зависимости от запрошенного действия
switch(Request::_get('action')){




	/*******************************************************************
	 * Получение списка данных
	 ******************************************************************/
	case 'get.data':

		$datalist = array('irlist','irtypes','igroups');
		$result = array();

		//Просмотр списка типов данных
		foreach($datalist as $datatype){

			$result[$datatype] = null;

			if(Request::_getInt($datatype, 0) != 1) continue;

			//Обработка запрошенного типа данных
			switch($datatype){
				
				//Запрошен список информационных ресурсов, доступных сотруднику
				case 'irlist':

					require_once(DIR_FUNCTIONS.'/company.functions.php');

					//Сотрудник
					$employer_id = User::_getEmployerId();

					//Организация
					$company_id = Request::_getId('company_id', 0);

					$for_employer = Request::_getId('employer_id', 0);
					if(!$for_employer) $for_employer=$employer_id;

					//Идентификатор должности
					if( ($post_uid = Request::_getId('post_uid', 0)) == 0){
						return Ajax::_responseError('Ошибка выполнения','Должность указана неверно');
					}

					//Информация о должности сотрудника
					$post_info = employer_post_info(array('employer_id'=>$for_employer, 'post_uid' => $post_uid));
					if(empty($post_info)){
						return Ajax::_responseError('Ошибка выполнения','Должность указана неверно');
					}

					if($for_employer != $employer_id){
						$main_employer = new Main_Employer();
						$allowed_companies = $main_employer->canCuratorCompanies(null);
						if(!in_array($post_info['company_id'], $allowed_companies)) return Ajax::_responseError('Ошибка выполнения','Вы не можете оформлять заявки для сотрудников выбранной организации');
					}

					if( ($irlist = company_iresources(array('company_id'=>$post_info['company_id']))) === false){
						return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/request/get.data/company_id:'.$post_info['company_id'].'/false');
					}

					$result[$datatype] = $irlist;
				break;


				//Запрошен список возможных типов доступа
				case 'irtypes':

					require_once(DIR_FUNCTIONS.'/iresource.functions.php');
					if( ($irtypes = iresource_irtypes()) === false){
						return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/request/get.data/irtypes/false');
					}

					$result[$datatype] = $irtypes;

				break;

				//Запрошен список групп информационных ресурсов
				case 'igroups':

					//Информация о должности сотрудника
					if(empty($post_info)){
						$employer_id = User::_getEmployerId();
						$for_employer = Request::_getId('employer_id', 0);
						if(!$for_employer) $for_employer=$employer_id;
						//Идентификатор должности
						if( ($post_uid = Request::_getId('post_uid', 0)) == 0){
							return Ajax::_responseError('Ошибка выполнения','Должность указана неверно');
						}
						$post_info = employer_post_info(array('employer_id'=>$for_employer, 'post_uid' => $post_uid));
						if(empty($post_info)){
							return Ajax::_responseError('Ошибка выполнения','Должность указана неверно');
						}
					}
					require_once(DIR_FUNCTIONS.'/company.functions.php');
					if( ($igroups = company_iresources_groups($post_info['company_id'])) === false){
						return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/request/get.data/igroups/false '.$company_id);
					}

					$result[$datatype] = $igroups;

				break;

			}//Обработка запрошенного типа данных

		}//Просмотр списка типов данных

		#Выполнено успешно
		return Ajax::_setData($result);

	break; #Получение списка данных





	/*******************************************************************
	 * Получение списка данных для заявки от имени других сотрудников
	 ******************************************************************/
	case 'get.irdata':

		$datalist = array('irlist','irtypes','igroups');
		$result = array();
		//Сотрудник
		$employer_id = User::_getEmployerId();
		//Организация
		$company_id = Request::_getId('company_id', 0);

		if(empty($company_id)){
			return Ajax::_responseError('Ошибка выполнения','Организация указана неверно');
		}

		//Просмотр списка типов данных
		foreach($datalist as $datatype){

			$result[$datatype] = null;

			//Обработка запрошенного типа данных
			switch($datatype){

				//Запрошен список информационных ресурсов, доступных сотруднику
				case 'irlist':

					require_once(DIR_FUNCTIONS.'/company.functions.php');
					$main_employer = new Main_Employer();
					$allowed_companies = $main_employer->canCuratorCompanies(null);
					if(!in_array($company_id, $allowed_companies)) return Ajax::_responseError('Ошибка выполнения','Вы не можете оформлять заявки для сотрудников выбранной организации');

					if( ($irlist = company_iresources(array('company_id'=>$company_id))) === false){
						return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/request/get.irdata/company_id:'.$post_info['company_id'].'/false');
					}
					$result[$datatype] = $irlist;
				break;


				//Запрошен список возможных типов доступа
				case 'irtypes':

					require_once(DIR_FUNCTIONS.'/iresource.functions.php');
					if( ($irtypes = iresource_irtypes()) === false){
						return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/request/get.irdata/irtypes/false');
					}

					$result[$datatype] = $irtypes;

				break;

				//Запрошен список групп информационных ресурсов
				case 'igroups':
					require_once(DIR_FUNCTIONS.'/company.functions.php');
					if( ($igroups = company_iresources_groups($company_id)) === false){
						return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/request/get.irdata/igroups/false '.$company_id);
					}

					$result[$datatype] = $igroups;

				break;

			}//Обработка запрошенного типа данных

		}//Просмотр списка типов данных

		#Выполнено успешно
		return Ajax::_setData($result);

	break; #Получение списка данных









	/*******************************************************************
	 * Список объектов доступа определенного информационного ресурса
	 ******************************************************************/
	case 'get.roles':

		require_once(DIR_FUNCTIONS.'/company.functions.php');
		require_once(DIR_FUNCTIONS.'/iresource.functions.php');

		//Сотрудник
		$employer_id = User::_getEmployerId();

		//Организация
		$company_id = Request::_getId('company_id', 0);

		$for_employer = Request::_getId('employer_id', 0);
		if(!$for_employer) $for_employer=$employer_id;

		//Идентификатор ИР
		if( ($iresource_id = Request::_getId('iresource_id', 0)) == 0){
			return Ajax::_responseError('Ошибка выполнения','Информационный ресурс указан неверно');
		}

		//Идентификатор должности
		if( ($post_uid = Request::_getId('post_uid', 0)) == 0){
			return Ajax::_responseError('Ошибка выполнения','Должность указана неверно');
		}

		//Информация о должности сотрудника
		$post_info = employer_post_info(array('employer_id'=>$for_employer, 'post_uid' => $post_uid));
		if(empty($post_info)){
			return Ajax::_responseError('Ошибка выполнения','Должность указана неверно');
		}

		if($for_employer != $employer_id){
			$main_employer = new Main_Employer();
			$allowed_companies = $main_employer->canCuratorCompanies(null);
			if(!in_array($post_info['company_id'], $allowed_companies)) return Ajax::_responseError('Ошибка выполнения','Вы не можете оформлять заявки для сотрудников выбранной организации');
		}


		//Информация об информационном ресурсе
		if(($iresource_info = iresource_info(array('iresource_id' => $iresource_id))) === false){
			return Ajax::_responseError('Ошибка выполнения','Информационный ресурс указан неверно');
		}


		$company_info = company_info(array('company_id'=>$post_info['company_id']));
		if(empty($company_info)){
			return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/request/get.roles/company_id:'.$post_info['company_id'].'/false');
		}


		//Доступ организации к ИР
		if(!company_allowed(array('company_id'=>$post_info['company_id'], 'iresource_id'=>$iresource_id))){
			return Ajax::_responseError('Ошибка выполнения','Сотрудники организации '.$company_info['full_name'].' не могут запрашивать доступ к указанному информационному ресурсу');
		}


		$iresource_roles = iresource_roles(array(
			'iresource_id'	=> $iresource_id,
			'employer_id' 	=> User::_getEmployerId(),
			'only_active' 	=> true,
			'raw_data'		=> false,
			'request_id'	=> 0,
			'iresource_info'=> $iresource_info
		));

		if($iresource_roles === false){
			return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/request/get.roles/iresource_roles/false');
		}

		#Выполнено успешно
		return Ajax::_setData($iresource_roles);

	break; #Список объектов доступа определенного информационного ресурса




	/*******************************************************************
	 * Список объектов доступа определенного информационного ресурса
	 ******************************************************************/
	case 'get.irroles':

		require_once(DIR_FUNCTIONS.'/company.functions.php');
		require_once(DIR_FUNCTIONS.'/iresource.functions.php');

		//Сотрудник
		$employer_id = User::_getEmployerId();

		//Организация
		$company_id = Request::_getId('company_id', 0);

		$for_employer = Request::_getId('employer_id', 0);
		if(!$for_employer) $for_employer=$employer_id;

		//Идентификатор ИР
		if( ($iresource_id = Request::_getId('iresource_id', 0)) == 0){
			return Ajax::_responseError('Ошибка выполнения','Информационный ресурс указан неверно');
		}

		$main_employer = new Main_Employer();
		$allowed_companies = $main_employer->canCuratorCompanies(null);
		if(!in_array($company_id, $allowed_companies)) return Ajax::_responseError('Ошибка выполнения','Вы не можете оформлять заявки для сотрудников выбранной организации');


		//Информация об информационном ресурсе
		if(($iresource_info = iresource_info(array('iresource_id' => $iresource_id))) === false){
			return Ajax::_responseError('Ошибка выполнения','Информационный ресурс указан неверно');
		}


		$company_info = company_info(array('company_id'=>$company_id));
		if(empty($company_info)){
			return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/request/get.roles/company_id:'.$post_info['company_id'].'/false');
		}


		//Доступ организации к ИР
		if(!company_allowed(array('company_id'=>$company_id, 'iresource_id'=>$iresource_id))){
			return Ajax::_responseError('Ошибка выполнения','Сотрудники организации '.$company_info['full_name'].' не могут запрашивать доступ к указанному информационному ресурсу');
		}

		$iresource_roles = iresource_roles(array(
			'iresource_id'	=> $iresource_id,
			'employer_id' 	=> 0,
			'only_active' 	=> true,
			'raw_data'		=> false,
			'request_id'	=> 0,
			'iresource_info'=> $iresource_info
		));

		if($iresource_roles === false){
			return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/request/get.roles/iresource_roles/false');
		}

		#Выполнено успешно
		return Ajax::_setData($iresource_roles);

	break; #Список объектов доступа определенного информационного ресурса






	/*******************************************************************
	 * Сохранение заявки и начало процесса согласования для нескольких сотрудников
	 ******************************************************************/
	case 'request.multisave':

		//Сотрудник
		$curator_id = User::_getEmployerId();

		//Организация
		$company_id = Request::_getId('company_id', 0);
		if(empty($company_id)) return Ajax::_responseError('Ошибка выполнения','Организация указана неверно');
		$main_employer = new Main_Employer();
		$allowed_companies = $main_employer->canCuratorCompanies(null);
		if(!in_array($company_id, $allowed_companies)) return Ajax::_responseError('Ошибка выполнения','Вы не можете оформлять заявки для сотрудников выбранной организации');


		//Список сотрудников
		$for_employers = Request::_getArray('e', false);
		if(empty($for_employers)){
			return Ajax::_responseError('Ошибка выполнения','Не заданы заявители');
		}
		$employers=array();
		foreach($for_employers as $row){
			$row = explode('|',$row);
			if(count($row)!=2) Ajax::_responseError('Ошибка выполнения','Некорректный запрос: данные сотрудников');
			$employer_id = intval($row[0]);
			$post_uid = $row[1];
			//Информация о должности сотрудника
			$post_info = employer_post_info(array('employer_id'=>$employer_id, 'post_uid' => $post_uid));
			if(empty($post_info)) return Ajax::_responseError('Ошибка выполнения','Сотрудники и/или должность сотрудника указаны неверно');
			if($company_id != $post_info['company_id']) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос: выбранная организация');
			$employers[]=array(
				'employer_id'	=> $row[0],
				'post_uid'		=> $row[1],
				'request_id'	=> 0
			);
		}

		//Массив информационных ресурсов
		$ir_list = Request::_getArray('ir', false);
		if(empty($ir_list)){
			return Ajax::_responseError('Ошибка выполнения','Не задан список информационных ресурсов');
		}

		//Массив объектов доступа
		$a_list = Request::_getArray('a', false);
		if(empty($a_list)){
			return Ajax::_responseError('Ошибка выполнения','Не выбрано ни одного объекта доступа.');
		}

		$db = Database::getInstance('main');

		require_once(DIR_FUNCTIONS.'/company.functions.php');
		require_once(DIR_FUNCTIONS.'/iresource.functions.php');

		$ir_info = array();

		//Обработка массива информационных ресурсов
		foreach($ir_list as $key=>$iresource_id){
			$iresource_id = intval($iresource_id);
			$ir_list[$key] = $iresource_id;
			if(empty($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Ошибка входных данных.');
			$ir_info[$iresource_id] = iresource_info(array('iresource_id'=>$iresource_id), $db);
			//Доступ организации к ИР
			if(!is_array($ir_info[$iresource_id]) || !company_allowed(array('company_id'=>$post_info['company_id'], 'iresource_id'=>$iresource_id), $db)){
				return Ajax::_responseError('Ошибка выполнения','Запрошен доступ к недопустимому информационному ресурсу');
			}
		}//Обработка массива информационных ресурсов

		$access_list = array();
		$iresources_access = array();

		//Обработка массива объектов доступа
		foreach($a_list as $item){
			$item = explode('|',$item);
			$iresource_id 	= intval($item[0]);
			$irole_id 		= intval($item[1]);
			$ir_selected	= intval($item[2]);
			if(!$iresource_id || !$irole_id || !$ir_selected || !in_array($iresource_id, $ir_list, true)) return Ajax::_responseError('Ошибка выполнения','Ошибка входных данных.');

			//Информация об объекте доступа
			$db->prepare('SELECT `irole_id`, `is_lock`, `is_area`, `ir_types` FROM `iroles` WHERE `irole_id`=? AND `iresource_id`=? LIMIT 1');
			$db->bind($irole_id);
			$db->bind($iresource_id);

			if(($irole_info = $db->selectRecord())===false){
				return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/request/request.save /irole_info/false');
			}

			if(!is_array($irole_info)) return Ajax::_responseError('Ошибка выполнения','Некорректная заявка: объект доступа ID='.$irole_id.' не существует в информационном ресурсе ID='.$iresource_id);
			if($irole_info['is_lock']==1)return Ajax::_responseError('Ошибка выполнения','Некорректная заявка: Попытка запроса доступа к заблокированному функционалу ID='.$irole_id.' ['.$irole_info['full_name'].'] в информационном ресурсе ID='.$iresource_id.' ['.$ir_info[$iresource_id]['full_name'].']');
			if($irole_info['is_area']==1) return Ajax::_responseError('Ошибка выполнения','Некорректная заявка: Попытка запроса доступа к объекту являющемуся разделом, ID='.$irole_id.' ['.$irole_info['full_name'].'] в информационном ресурсе ID='.$iresource_id.' ['.$ir_info[$iresource_id]['full_name'].']');

			#Проверка привелегии доступа в объекте доступа
			if(strlen($irole_info['ir_types'])>0){
				$ir_types = explode(',',$irole_info['ir_types']);
				if(!in_array($ir_selected, $ir_types)) return Ajax::_responseError('Ошибка выполнения','Некорректная заявка: Объект доступа ID='.$irole_id.' ['.$irole_info['full_name'].'] не имеет указанной привелегии доступа [ir_type='.$ir_selected.'] в информационном ресурсе ID='.$iresource_id.' ['.$ir_info[$iresource_id]['full_name'].']');
			}else{
				return Ajax::_responseError('Ошибка выполнения','Некорректная заявка: Запрошены привелегии доступа к объекту доступа, который не имеет привелегий, ID='.$irole_id.' ['.$irole_info['full_name'].'] не имеет указанной привелегии доступа [ir_type='.$ir_selected.'] в информационном ресурсе ID='.$iresource_id.' ['.$ir_info[$iresource_id]['full_name'].']');
			}

			$access_list[] = array($iresource_id, $irole_id, $ir_selected);
			if(!isset($iresources_access[$iresource_id])) $iresources_access[$iresource_id] = 0;
			$iresources_access[$iresource_id]=$iresources_access[$iresource_id]+1;
		}//Обработка массива объектов доступа

		$ir_diff = array_diff($ir_list, array_keys($iresources_access));

		if(!empty($ir_diff)) return Ajax::_responseError('Ошибка выполнения','Ошибка входных данных: IR_DIFF.');

		/*
		print_r($a_list);
		print_r($ir_list); 
		print_r($iresources_access); 
		print_r($ir_diff); 
		return Ajax::_responseError('Ошибка выполнения','XXX.');
		*/


		//Создание заявки
		$db->transaction();

		//Создание заявок для сотрудников
		foreach($employers as $id=>$employer){

			$request = new Main_Request();

			$request_id = $request->create(array(
				'employer_id'	=> $employer['employer_id'],
				'curator_id'	=> $curator_id,
				'post_uid'		=> $employer['post_uid'],
				'company_id'	=> $company_id,
				'phone'			=> Request::_getStr('phone',null),
				'email'			=> Request::_getStr('email',null)
			));

			if(!$request_id){
				$db->rollback();
				return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/request/request.save /request->create/false');
			}

			//Добавление в заявку информационных ресурсов
			foreach($ir_info as $iresource_id=>$iresource){
				if($iresources_access[$iresource_id] == 0) continue;
				$rires_id = $request->setIResource(array(
					'iresource_id' => $iresource_id
				));
				if(!$rires_id){
					$db->rollback();
					return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/request/request.save /setIResource/iresource_id='.$iresource_id.'/false');
				}
			}//Добавление в заявку информационных ресурсов

			//Добавление в заявку объектов доступа
			foreach($access_list as $irole){
				if($iresources_access[$irole[0]] == 0){
					$db->rollback();
					return Ajax::_responseError('Ошибка выполнения','Ошибка входных данных: IR_EMPTY.');
				}
				//Добавление информационного ресурса
				$rrole_id = $request->setIRole(array(
					'iresource_id'		=> $irole[0],
					'irole_id'			=> $irole[1],
					'ir_type'			=> $irole[2],
					'ir_selected'		=> $irole[2],
					'update_type'		=> 0
				));
				if(!$rrole_id){
					$db->rollback();
					return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/request/request.save /setIRole /iresource_id='.$irole[0].'/irole_id='.$irole[1].'/false');
				}
			}//Добавление в заявку объектов доступа

			//На первый шаг согласования
			$request->toFirstStep();

			$employers[$id]['request_id'] = $request_id;

		}//Создание заявок для сотрудников


		#Заявка добавлена успешно
		$db->commit();

		#Выполнено успешно
		return Ajax::_setData($employers);


	break; #Сохранение заявки и начало процесса согласования для нескольких сотрудников





	/*******************************************************************
	 * Сохранение заявки и начало процесса согласования
	 ******************************************************************/
	case 'request.save':

		//Сотрудник
		$employer_id = User::_getEmployerId();

		//Организация
		$company_id = Request::_getId('company_id', 0);

		$for_employer = Request::_getId('employer_id', 0);
		if(!$for_employer) $for_employer=$employer_id;

		//Идентификатор должности
		if( ($post_uid = Request::_getId('post_uid', 0)) == 0){
			return Ajax::_responseError('Ошибка выполнения','Должность указана неверно');
		}

		//Информация о должности сотрудника
		$post_info = employer_post_info(array('employer_id'=>$for_employer, 'post_uid' => $post_uid));
		if(empty($post_info)){
			return Ajax::_responseError('Ошибка выполнения','Должность указана неверно');
		}

		if($for_employer != $employer_id){
			$main_employer = new Main_Employer();
			$allowed_companies = $main_employer->canCuratorCompanies(null);
			if(!in_array($post_info['company_id'], $allowed_companies)) return Ajax::_responseError('Ошибка выполнения','Вы не можете оформлять заявки для сотрудников выбранной организации');
		}

		//Массив информационных ресурсов
		$ir_list = Request::_getArray('ir', false);
		if(empty($ir_list)){
			return Ajax::_responseError('Ошибка выполнения','Не задан список информационных ресурсов');
		}

		//Массив объектов доступа
		$a_list = Request::_getArray('a', false);
		if(empty($a_list)){
			return Ajax::_responseError('Ошибка выполнения','Не выбрано ни одного объекта доступа.');
		}

		$db = Database::getInstance('main');

		require_once(DIR_FUNCTIONS.'/company.functions.php');
		require_once(DIR_FUNCTIONS.'/iresource.functions.php');

		$ir_info = array();

		//Обработка массива информационных ресурсов
		foreach($ir_list as $key=>$iresource_id){
			$iresource_id = intval($iresource_id);
			$ir_list[$key] = $iresource_id;
			if(empty($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Ошибка входных данных.');
			$ir_info[$iresource_id] = iresource_info(array('iresource_id'=>$iresource_id), $db);
			//Доступ организации к ИР
			if(!is_array($ir_info[$iresource_id]) || !company_allowed(array('company_id'=>$post_info['company_id'], 'iresource_id'=>$iresource_id), $db)){
				return Ajax::_responseError('Ошибка выполнения','Запрошен доступ к недопустимому информационному ресурсу');
			}
		}//Обработка массива информационных ресурсов

		$access_list = array();
		$iresources_access = array();

		//Обработка массива объектов доступа
		foreach($a_list as $item){
			$item = explode('|',$item);
			$iresource_id 	= intval($item[0]);
			$irole_id 		= intval($item[1]);
			$ir_selected	= intval($item[2]);
			if(!$iresource_id || !$irole_id || !$ir_selected || !in_array($iresource_id, $ir_list, true)) return Ajax::_responseError('Ошибка выполнения','Ошибка входных данных.');

			//Информация об объекте доступа
			$db->prepare('SELECT `irole_id`, `is_lock`, `is_area`, `ir_types` FROM `iroles` WHERE `irole_id`=? AND `iresource_id`=? LIMIT 1');
			$db->bind($irole_id);
			$db->bind($iresource_id);

			if(($irole_info = $db->selectRecord())===false){
				return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/request/request.save /irole_info/false');
			}

			if(!is_array($irole_info)) return Ajax::_responseError('Ошибка выполнения','Некорректная заявка: объект доступа ID='.$irole_id.' не существует в информационном ресурсе ID='.$iresource_id);
			if($irole_info['is_lock']==1)return Ajax::_responseError('Ошибка выполнения','Некорректная заявка: Попытка запроса доступа к заблокированному функционалу ID='.$irole_id.' ['.$irole_info['full_name'].'] в информационном ресурсе ID='.$iresource_id.' ['.$ir_info[$iresource_id]['full_name'].']');
			if($irole_info['is_area']==1) return Ajax::_responseError('Ошибка выполнения','Некорректная заявка: Попытка запроса доступа к объекту являющемуся разделом, ID='.$irole_id.' ['.$irole_info['full_name'].'] в информационном ресурсе ID='.$iresource_id.' ['.$ir_info[$iresource_id]['full_name'].']');

			#Проверка привелегии доступа в объекте доступа
			if(strlen($irole_info['ir_types'])>0){
				$ir_types = explode(',',$irole_info['ir_types']);
				if(!in_array($ir_selected, $ir_types)) return Ajax::_responseError('Ошибка выполнения','Некорректная заявка: Объект доступа ID='.$irole_id.' ['.$irole_info['full_name'].'] не имеет указанной привелегии доступа [ir_type='.$ir_selected.'] в информационном ресурсе ID='.$iresource_id.' ['.$ir_info[$iresource_id]['full_name'].']');
			}else{
				return Ajax::_responseError('Ошибка выполнения','Некорректная заявка: Запрошены привелегии доступа к объекту доступа, который не имеет привелегий, ID='.$irole_id.' ['.$irole_info['full_name'].'] не имеет указанной привелегии доступа [ir_type='.$ir_selected.'] в информационном ресурсе ID='.$iresource_id.' ['.$ir_info[$iresource_id]['full_name'].']');
			}

			$access_list[] = array($iresource_id, $irole_id, $ir_selected);
			if(!isset($iresources_access[$iresource_id])) $iresources_access[$iresource_id] = 0;
			$iresources_access[$iresource_id]=$iresources_access[$iresource_id]+1;
		}//Обработка массива объектов доступа

		$ir_diff = array_diff($ir_list, array_keys($iresources_access));

		if(!empty($ir_diff)) return Ajax::_responseError('Ошибка выполнения','Ошибка входных данных: IR_DIFF.');

		/*
		print_r($a_list);
		print_r($ir_list); 
		print_r($iresources_access); 
		print_r($ir_diff); 
		return Ajax::_responseError('Ошибка выполнения','XXX.');
		*/

		$request = new Main_Request();

		//Создание заявки
		$db->transaction();

			$request_id = $request->create(array(
				'employer_id'	=> $for_employer,
				'curator_id'	=> $employer_id,
				'post_uid'		=> $post_uid,
				'company_id'	=> $post_info['company_id'],
				'phone'			=> Request::_getStr('phone',null),
				'email'			=> Request::_getStr('email',null)
			));

			if(!$request_id){
				$db->rollback();
				return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/request/request.save /request->create/false');
			}

			//Добавление в заявку информационных ресурсов
			foreach($ir_info as $iresource_id=>$iresource){
				if($iresources_access[$iresource_id] == 0) continue;
				$rires_id = $request->setIResource(array(
					'iresource_id' => $iresource_id
				));
				if(!$rires_id){
					$db->rollback();
					return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/request/request.save /setIResource/iresource_id='.$iresource_id.'/false');
				}
			}//Добавление в заявку информационных ресурсов

			//Добавление в заявку объектов доступа
			foreach($access_list as $irole){
				if($iresources_access[$irole[0]] == 0){
					$db->rollback();
					return Ajax::_responseError('Ошибка выполнения','Ошибка входных данных: IR_EMPTY.');
				}
				//Добавление информационного ресурса
				$rrole_id = $request->setIRole(array(
					'iresource_id'		=> $irole[0],
					'irole_id'			=> $irole[1],
					'ir_type'			=> $irole[2],
					'ir_selected'		=> $irole[2],
					'update_type'		=> 0
				));
				if(!$rrole_id){
					$db->rollback();
					return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/request/request.save /setIRole /iresource_id='.$irole[0].'/irole_id='.$irole[1].'/false');
				}
			}//Добавление в заявку объектов доступа

			//На первый шаг согласования
			$request->toFirstStep();

		#Заявка добавлена успешно
		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			'request_id' => $request_id//,
			//'response' => $response
		));

	break; #Сохранение заявки и начало процесса согласования







	/*******************************************************************
	 * Добавление комментария к заявке
	 ******************************************************************/
	case 'comment.add':

		//Идентификатор заявки
		if( ($request_id = Request::_getId('request_id', 0)) == 0){
			return Ajax::_responseError('Ошибка выполнения','Номер заявки указан некорректно');
		}

		//Идентификатор информационного ресурса
		if( ($iresource_id = Request::_getId('iresource_id', 0)) == 0){
			return Ajax::_responseError('Ошибка выполнения','Идентификатор информационного ресурса указан некорректно');
		}

		//Комментарий
		$comment = htmlspecialchars(trim(Request::_getStr('comment', '')));
		if(empty($comment)){
			return Ajax::_responseError('Ошибка выполнения','Не задан комментарий');
		}


		$main_employer = new Main_Employer();

		if(!$main_employer->canComment($request_id, $iresource_id)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете оставлять комментарии в данной заявке');
		}

		$main_request = new Main_Request();
		$db = Database::getInstance('main');

		//Начало транзакции
		$db->transaction();

			$db->prepare('INSERT INTO `request_comments` (`request_id`,`iresource_id`,`employer_id`,`comment`,`timestamp`) VALUES (?,?,?,?,?)');
			$db->bind($request_id);
			$db->bind($iresource_id);
			$db->bind(User::_getEmployerID());
			$db->bind($comment);
			$db->bind(date('Y-m-d H:i:s'));

			if($db->insert() === false){
				$db->rollback();
				return Ajax::_responseError('Ошибка выполнения','Ошибка во время добавления комментария');
			}

		#Успешно
		$db->commit();

		#Выполнено успешно
		return Ajax::_setData( (Request::_getBool('returncomments') == true ? $main_request->dbGetComments($request_id, $iresource_id) : true ) );

	break; #Сохранение заявки и начало процесса согласования







	/*******************************************************************
	 * Одобрение заявки на текущем шаге согласования
	 ******************************************************************/
	case 'request.approve':

		$employer_id = User::_getEmployerID();

		//Идентификатор запроса
		if( ($request_id = Request::_getId('request_id', 0)) == 0){
			return Ajax::_responseError('Ошибка выполнения','Номер заявки указан неверно');
		}

		//Идентификатор ИР
		if( ($iresource_id = Request::_getId('iresource_id', 0)) == 0){
			return Ajax::_responseError('Ошибка выполнения','Информационный ресурс указан неверно');
		}

		//Комментарий
		$comment = htmlspecialchars(strip_tags(trim(Request::_getStr('comment', ''))));

		//Массив измененных объектов доступа
		$ir_list = Request::_getArray('a', false);
		if(!is_array($ir_list)) $ir_list=array();

		$db = Database::getInstance('main');

		$db->transaction();

		$main_employer = new Main_Employer();
		$main_request = new Main_Request();

		$gk_request = $main_employer->getActiveRequests(array(
			'roles'			=> array(1,2,3),
			'request_id'	=> array($request_id),
			'iresource_id'	=> array($iresource_id),
			'single'		=> true
		));

		if(empty($gk_request) || !$main_employer->canApprove($request_id, $iresource_id)){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','В настоящий момент Вы не можете выполнять какие-либо действия с выбранной заявкой.');
		}

		if($gk_request['gatekeeper_role']==4){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Заявка доступна Вам только для просмотра, Вы не можете выполнять какие-либо действия');
		}

		if(strlen($comment)==0 && $gk_request['gatekeeper_role']==3){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Исполнитель должен оставить комментарий о статусе выпонения заявки');
		}

		//Открытие заявки
		if($main_request->open(array(
			'request_id'			=> $request_id, 
			'fullinfo'				=> false,
			'iresource_id'			=> $iresource_id,
			'alliroles'				=> false,
			'iresourceforupdate'	=> true
		))===false) return Ajax::_responseError('Ошибка выполнения','Не удалось открыть заявку');

		$request_type = $main_request->cache['info']['request_type'];

		//Обновляем роли
		if(!empty($ir_list)){foreach($ir_list as $row){
			$irole = array_map('intval',explode('|',$row));
			if(count($irole)!=4) continue;
			if($irole[0]!=$gk_request['request_id'] || $irole[1]!=$gk_request['iresource_id'] || empty($irole[2])) continue;

			//Проверка существования объекта доступа в ИР и типа доступа
			$db->prepare('SELECT * FROM `iroles` WHERE `irole_id`=? AND `iresource_id`=? LIMIT 1');
			$db->bind($irole[2]);
			$db->bind($iresource_id);
			if(($irole_info = $db->selectRecord()) === false){
				$db->rollback();
				return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/request/request.approve /sql:iroles/false');
			}
			if(!is_array($irole_info)) continue;
			if($irole_info['is_lock']==1 && $request_type != 3) continue;
			if($irole_info['is_area']==1) continue;

			//Проверка привелегии доступа в объекте доступа
			if($irole[3] != 0){
				if($request_type == 3){
					$irole[3] = ($irole[3] == 0 ? 0 : 1);
				}else{
					if(strlen($irole_info['ir_types'])>0){
						$ir_types = explode(',',$irole_info['ir_types']);
						if(!in_array($irole[3],$ir_types)) continue;
					}else{
						continue;
					}
				}
			}

			//Добавление/обновление объекта доступа в заявке
			$main_request->setIRole(array(
				'request_id'	=> $request_id,
				'iresource_id'	=> $irole[1],
				'irole_id'		=> $irole[2],
				'ir_selected'	=> $irole[3],
				'gatekeeper_id'	=> $employer_id
			));
		}}



		//На следующий шаг
		if($main_request->toStep($iresource_id, 'approve',  $employer_id) === false){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/request/request.approve /toStep/false');
		}


		//Отметка об успешном выполнении текушего шага
		if($main_request->setRouteStep(array(
			'request_id'	=> $gk_request['request_id'],
			'iresource_id'	=> $gk_request['iresource_id'],
			'gatekeeper_id'	=> ($gk_request['is_gatekeeper']==1 ? $employer_id : 0),
			'assistant_id'	=> ($gk_request['is_assistant']==1 ? $employer_id : 0),
			'step_complete'	=> 1,
			'is_approved'	=> 1,
			'rstep_id'		=> $gk_request['rstep_id'],
			'step_uid'		=> $gk_request['step_uid']
		)) === false){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/request/request.approve /setRouteStep/false');
		}


		//Комментарий доступен только если исполнение заявки
		if(strlen($comment)>0 && $gk_request['gatekeeper_role']==3){
			$db->prepare('INSERT INTO `request_comments` (`request_id`,`iresource_id`,`employer_id`,`comment`,`timestamp`) VALUES (?,?,?,?,?)');
			$db->bind($gk_request['request_id']);
			$db->bind($gk_request['iresource_id']);
			$db->bind($employer_id);
			$db->bind($comment);
			$db->bind(date('Y-m-d H:i:s'));

			if($db->insert() === false){
				$db->rollback();
				return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера во время добавления комментария');
			}
		}


		//Даем права сотруднику на просмотр заявки, поскольку он ее согласовал и/или исполнил
		if($main_request->addWatcher(array(
			'employer_id'	=> $employer_id,
			'iresource_id'	=> $gk_request['iresource_id'],
			($gk_request['gatekeeper_role']==3 ? 'is_performer' : 'is_gatekeeper')	=> true
		)) === false){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/request/request.approve /addWatcher/false');
		}


		$db->commit();
		//$db->rollback();

		switch($gk_request['gatekeeper_role']){
			case '1': $status_desc = 'Заявка согласована'; break;
			case '2': $status_desc = 'Заявка утверждена'; break;
			case '3': $status_desc = 'Заявка исполнена'; break;
			default:  $status_desc = 'А что Вы сделали?'; break;
		}

		#Выполнено успешно
		return Ajax::_setData(array(
			'status'	=> 'complete',
			'desc'		=> $status_desc
		));

	break; #Одобрение заявки на текущем шаге согласования









	/*******************************************************************
	 * Отклонение заявки на текущем шаге согласования
	 ******************************************************************/
	case 'request.decline':

		$employer_id = User::_getEmployerID();

		//Идентификатор запроса
		if( ($request_id = Request::_getId('request_id', 0)) == 0){
			return Ajax::_responseError('Ошибка выполнения','Номер заявки указан неверно');
		}

		//Идентификатор ИР
		if( ($iresource_id = Request::_getId('iresource_id', 0)) == 0){
			return Ajax::_responseError('Ошибка выполнения','Информационный ресурс указан неверно');
		}

		//Комментарий
		$comment = htmlspecialchars(strip_tags(trim(Request::_getStr('comment', ''))));

		if(strlen($comment)==0){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Вы не указали причину отклонения заявки');
		}

		$db = Database::getInstance('main');

		$db->transaction();

		$main_employer = new Main_Employer();
		$main_request = new Main_Request();

		$gk_request = $main_employer->getActiveRequests(array(
			'roles'			=> array(1,2),
			'request_id'	=> array($request_id),
			'iresource_id'	=> array($iresource_id),
			'single'		=> true
		));

		if(empty($gk_request) || !$main_employer->canApprove($request_id, $iresource_id)){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','В настоящий момент Вы не можете выполнять какие-либо действия с выбранной заявкой.');
		}


		//Открытие заявки
		$main_request->open(array(
			'request_id'			=> $request_id, 
			'fullinfo'				=> false,
			'iresource_id'			=> $iresource_id,
			'alliroles'				=> false,
			'iresourceforupdate'	=> true
		));



		//На следующий шаг
		if($main_request->toStep($iresource_id, 'decline',  $employer_id) === false){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/request/request.decline /toStep/false');
		}


		//Отметка об успешном выполнении текушего шага
		if($main_request->setRouteStep(array(
			'request_id'	=> $gk_request['request_id'],
			'iresource_id'	=> $gk_request['iresource_id'],
			'gatekeeper_id'	=> ($gk_request['is_gatekeeper']==1 ? $employer_id : 0),
			'assistant_id'	=> ($gk_request['is_assistant']==1 ? $employer_id : 0),
			'step_complete'	=> 1,
			'is_approved'	=> 0,
			'rstep_id'		=> $gk_request['rstep_id'],
			'step_uid'		=> $gk_request['step_uid']
		)) === false){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/request/request.decline /setRouteStep/false');
		}


		//Комментарий доступен только если исполнение заявки
		$db->prepare('INSERT INTO `request_comments` (`request_id`,`iresource_id`,`employer_id`,`comment`,`timestamp`) VALUES (?,?,?,?,?)');
		$db->bind($gk_request['request_id']);
		$db->bind($gk_request['iresource_id']);
		$db->bind($employer_id);
		$db->bind($comment);
		$db->bind(date('Y-m-d H:i:s'));

		if($db->insert() === false){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера во время добавления комментария');
		}


		//Даем права сотруднику на просмотр заявки, поскольку он ее согласовал и/или исполнил
		if($main_request->addWatcher(array(
			'employer_id'	=> $employer_id,
			'iresource_id'	=> $gk_request['iresource_id'],
			($gk_request['gatekeeper_role']==3 ? 'is_performer' : 'is_gatekeeper')	=> true
		)) === false){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/request/request.decline /addWatcher/false');
		}


		$db->commit();


		#Выполнено успешно
		return Ajax::_setData(array(
			'status'	=> 'complete',
			'desc'		=> 'Заявка отклонена'
		));

	break; #Отклонение заявки на текущем шаге согласования












	/*******************************************************************
	 * Отмена заявки заявителем или куратором заявителя
	 ******************************************************************/
	case 'request.cancel':

		$employer_id = User::_getEmployerID();

		//Идентификатор запроса
		if( ($request_id = Request::_getId('request_id', 0)) == 0){
			return Ajax::_responseError('Ошибка выполнения','Номер заявки указан неверно');
		}

		//Идентификатор ИР
		if( ($iresource_id = Request::_getId('iresource_id', 0)) == 0){
			return Ajax::_responseError('Ошибка выполнения','Информационный ресурс указан неверно');
		}

		$db = Database::getInstance('main');

		$db->transaction();

		$main_request = new Main_Request();

		if(!$main_request->dbIsEmployerRequest($employer_id, $request_id)){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Вы не можете отменить согласование этой заявки.');
		}

		$iresource = $main_request->dbGetIResource($request_id, $iresource_id, true, false);

		if(empty($iresource)||!is_array($iresource)){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Указанный информационный ресурс отсутствует в заявке.');
		}


		if($iresource['route_status'] == 100){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Нельзя отменить исполненную заявку.');
		}


		if($iresource['route_status'] == 0){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Эта заявка уже отменена.');
		}

		$db->prepare('UPDATE `request_iresources` SET `route_status`=0,`route_status_desc`=?,`current_step`=0 WHERE `rires_id`=?');
		$db->bind('Заявку отменил '.Session::_get('employer_name'));
		$db->bind($iresource['rires_id']);
		if($db->update()===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/request/request.cancel /UPDATE/false');
		}

		//Перемещение в историю
		if($main_request->moveRIResourceToHistory($request_id, $iresource_id)===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/request/request.cancel /History/false');
		}


		#Выполнено успешно
		$db->commit();

		$main_employer = new Main_Employer();
		return Ajax::_setData(array(
			'active' => $main_employer->dbEmployerRequests(null, 1),
			'complete' => $main_employer->dbEmployerRequests(null, 100),
			'cancel' => $main_employer->dbEmployerRequests(null, 0),
			'hold' => $main_employer->dbEmployerRequests(null, 2)
		));


	break; #Отклонение заявки на текущем шаге согласования








	default:
	Ajax::_responseError('/main/ajax/request','Не найден обработчик для: '.Request::_get('action'));

}


?>