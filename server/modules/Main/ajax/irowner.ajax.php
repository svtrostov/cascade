<?php
/*==================================================================================================
Описание: Обработка AJAX запросов
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');


$request_action = Request::_get('action');

LABEL_IROWNER_AXCONTROLLER_START:

#Обработка AJAX запроса, в зависимости от запрошенного действия
switch($request_action){



	/*******************************************************************
	 * Сведения об информационном ресурсе для владельца ИР
	 ******************************************************************/
	case 'iresource.data':

		$iresource_id = $request->getId('iresource_id', 0);
		$get_iroles = $request->getBool('iroles', true);
		$get_employers = $request->getBool('employers', true);

		if(empty($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');

		$ir_owner = $main_employer->getEmployerIROwner();
		if(empty($ir_owner)||!in_array($iresource_id,$ir_owner,true)) return Ajax::_responseError('Ошибка выполнения','Вы не являетесь владельцем выбранного информационного ресурса');

		$result=array();
		$main_irowner = new Main_IROwner();

		//Требуется вернуть список ролей ИР
		if($get_iroles){
			$result['iroles'] = $main_irowner->getIRoles($iresource_id);
		}//Требуется вернуть список ролей ИР


		//Требуется вернуть список сотрудников, имеющих доступ к ИР
		if($get_employers){
			$result['employers'] = $main_irowner->getIResourceEmployers($iresource_id);
		}//Требуется вернуть список сотрудников, имеющих доступ к ИР


		return Ajax::_setData($result);

	break; #Сведения об информационном ресурсе для владельца ИР








	/*******************************************************************
	 * Список сотрудников, которые имеют права доступа к указанному объекту доступа
	 ******************************************************************/
	case 'irole.employers':

		$iresource_id = $request->getId('iresource_id', 0);
		$irole_id = $request->getId('irole_id', 0);

		if(empty($iresource_id)||empty($irole_id)) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');

		$ir_owner = $main_employer->getEmployerIROwner();
		if(empty($ir_owner)||!in_array($iresource_id,$ir_owner,true)) return Ajax::_responseError('Ошибка выполнения','Вы не являетесь владельцем выбранного информационного ресурса');

		$admin_iresource = new Admin_IResource();
		if(!$admin_iresource->iresourceExists($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Информационный ресурс ID:'.$iresource_id.' не существует');
		if(!$admin_iresource->iroleExists($iresource_id, $irole_id)) return Ajax::_responseError('Ошибка выполнения','Объект доступа ID:'.$irole_id.' не найден в информационном ресурсе ID:'.$iresource_id);


		$db = Database::getInstance('main');

		$db->prepare('
			SELECT
				CRF.`iresource_id` as `iresource_id`,
				CRF.`irole_id` as `irole_id`,
				CRF.`request_id` as `request_id`,
				EMP.`employer_id` as `employer_id`,
				EMP.`search_name` as `search_name`,
				EMP.`phone` as `phone`,
				EMP.`email` as `email`,
				CRF.`ir_type` as `ir_type`,
				CRF.`post_uid` as `post_uid`,
				P.`full_name` as `post_name`,
				C.`full_name` as `company_name`,
				DATE_FORMAT(CRF.`timestamp`, "%d.%m.%Y") as `timestamp`
			FROM `complete_roles_full` as CRF
			INNER JOIN `employers` as EMP ON EMP.`employer_id`=CRF.`employer_id`
			INNER JOIN `company_posts` as CP ON CP.`post_uid`=CRF.`post_uid`
			INNER JOIN `posts` as P ON P.`post_id`=CP.`post_id`
			INNER JOIN `companies` as C ON C.`company_id`=CRF.`company_id`
			WHERE 
				CRF.`iresource_id`=? AND CRF.`irole_id`=?
		');
		$db->bind($iresource_id);
		$db->bind($irole_id);


		return Ajax::_setData(array(
			'irole_employers' => $db->select()
		));

	break; #Список сотрудников, которые имеют права доступа к указанному объекту доступа






	/*******************************************************************
	 * Список объектов доступа, к которым имеет доступ выбранный сотрудник в указанном информационном ресурсе
	 ******************************************************************/
	case 'employer.iroles':

		$iresource_id = $request->getId('iresource_id', 0);
		$employer_id = $request->getId('employer_id', 0);

		if(empty($iresource_id)||empty($employer_id)) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');

		$ir_owner = $main_employer->getEmployerIROwner();
		if(empty($ir_owner)||!in_array($iresource_id,$ir_owner,true)) return Ajax::_responseError('Ошибка выполнения','Вы не являетесь владельцем выбранного информационного ресурса');

		$admin_employers = new Admin_Employers();
		$admin_iresource = new Admin_IResource();
		if(!$admin_iresource->iresourceExists($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Информационный ресурс ID:'.$iresource_id.' не существует');
		if(!$admin_employers->employerExists($employer_id)) return Ajax::_responseError('Ошибка выполнения','Сотрудник ID:'.$employer_id.' не существует');

		$main_irowner = new Main_IROwner();

		//Получение списка объектов ИР
		return Ajax::_setData(array(
			'employer_iroles' => $main_irowner->employerIResourceRoles($employer_id, $iresource_id)
		));

	break; # Список объектов доступа, к которым имеет доступ выбранный сотрудник в указанном информационном ресурсе







	/*******************************************************************
	 * Блокировка доступа к объектам информационного ресурса
	 ******************************************************************/
	case 'request.lock':

		$iresource_id = $request->getId('iresource_id', 0);
		if(empty($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');

		$ir_owner = $main_employer->getEmployerIROwner();
		if(empty($ir_owner)||!in_array($iresource_id,$ir_owner,true)) return Ajax::_responseError('Ошибка выполнения','Вы не являетесь владельцем выбранного информационного ресурса');

		$employer_id	= $request->getId('employer_id', 0);
		$post_uid		= $request->getId('post_uid', 0);
		$a				= $request->getArray('iroles', array());
		if(empty($employer_id)||empty($post_uid)||empty($a)||!is_array($a)) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос: empty');

		$admin_iresource = new Admin_IResource();
		$admin_employers = new Admin_Employers();
		if(!$admin_iresource->iresourceExists($iresource_id)) return Ajax::_responseError('Ошибка выполнения','Информационный ресурс ID:'.$iresource_id.' не существует');
		if(!$admin_employers->employerExists($employer_id)) return Ajax::_responseError('Ошибка выполнения','Сотрудник ID:'.$employer_id.' не существует');
		$post_info = $admin_employers->employerPostInfo($employer_id, $post_uid);
		if(empty($post_info)) return Ajax::_responseError('Ошибка выполнения','Сотрудник ID:'.$employer_id.' не занимает указанную должность');

		$iroles=array();
		$result=array();
		$a = array_map('intval',$a);

		$admin_matrix = new Admin_Matrix();

		//foreach
		foreach($a as $irole_id){
			if(!isset($iroles[$irole_id])){
				$iroles[$irole_id] = $admin_iresource->getIRole($iresource_id, $irole_id);
				if(empty($iroles[$irole_id])) return Ajax::_responseError('Ошибка выполнения','Объект доступа ID:'.$irole_id.' не существует в информационном ресурсе ID:'.$iresource_id);
				if(!$admin_matrix->employerIResourceRoleExists($employer_id, $post_uid, $iresource_id, $irole_id)) return Ajax::_responseError('Ошибка выполнения','Сотрудник на указанной должности не имеет доступа к объекту доступа ID:'.$irole_id.' из информационного ресурса ID:'.$iresource_id);
			}
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

			$rires_id = $main_request->setIResource(array(
				'iresource_id'	=> $iresource_id,
				'route_type'	=> 4
			));
			if(!$rires_id){
				$db->rollback();
				return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/irowner/request.lock /setIResource/iresource_id='.$iresource_id.'/false');
			}

			//Добавление в заявку объектов доступа
			foreach($a as $irole_id){

				$rrole_id = $main_request->setIRole(array(
					'iresource_id'		=> $iresource_id,
					'irole_id'			=> $irole_id,
					'ir_type'			=> 1,
					'ir_selected'		=> 1,
					'update_type'		=> 0
				));
				if(!$rrole_id){
					$db->rollback();
					return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/irowner/request.lock /setIRole /iresource_id='.$iresource_id.'/irole_id='.$irole_id.'/false');
				}
			}//Добавление в заявку объектов доступа


			//На первый шаг согласования
			$main_request->toFirstStep();

		#Заявка добавлена успешно
		$db->commit();

		#Выполнено успешно
		Ajax::_responseSuccess('Заявка на блокировку доступа','Операция выполнена успешно');
		return Ajax::_setData(array(
			'request_id'=>$request_id
		));

	break; #Блокировка доступа к объектам информационного ресурса





	default:
	Ajax::_responseError('/main/ajax/irowner','Не найден обработчик для: '.Request::_get('action'));
}
?>