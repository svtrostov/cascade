<?php
/*==================================================================================================
Описание: Обработка AJAX запросов
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');

$request_action = Request::_get('action');

#Обработка AJAX запроса, в зависимости от запрошенного действия
switch($request_action){



	/*******************************************************************
	 * Добавление группы
	 ******************************************************************/
	case 'group.new':

		$full_name = trim($request->getStr('full_name', ''));
		$short_name = trim($request->getStr('short_name', ''));

		if(!$uaccess->checkAccess('employers.groups.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять список групп');
		}

		if(empty($full_name)||empty($short_name)) return Ajax::_responseError('Ошибка выполнения','Не задано имя группы');
		$admin_employers = new Admin_Employers();
		if($admin_employers->groupExists($full_name)) return Ajax::_responseError('Ошибка выполнения','Уже существует группа с указанным наименованием');

		if(($group_id = $admin_employers->groupNew(array(
			'full_name'		=> $full_name,
			'short_name'	=> $short_name
		)))===false){
			return Ajax::_responseError('Ошибка выполнения','Ошибка добавления группы');
		}

		Protocol::_add(array(
			'action_name'	=> $request_action,
			'acl_id'		=> $uaccess->getObjectIdFormName('employers.groups.edit'),
			'acl_name'		=> 'employers.groups.edit',
			'primary_type'	=> 'group',
			'primary_id'	=> $group_id,
			'description'	=> 'Создана новая группа сотрудников',
			'value'	=> array(
				'group_id'		=> $group_id,
				'full_name'		=> $full_name,
				'short_name'	=> $short_name
			)
		));


		#Выполнено успешно
		return Ajax::_setData(array(
			'sobject' => $group_id,
			'groups' => $admin_employers->getGroupsListEx()
		));

	break; #Добавление группы




	/*******************************************************************
	 * Редактирование группы
	 ******************************************************************/
	case 'group.edit':

		$group_id = $request->getId('group_id', 0);
		$full_name = trim($request->getStr('full_name', ''));
		$short_name = trim($request->getStr('short_name', ''));

		if(!$uaccess->checkAccess('employers.groups.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять список групп');
		}

		$admin_employers = new Admin_Employers();
		if(!$admin_employers->groupExists($group_id)) return Ajax::_responseError('Ошибка выполнения','Редактируемой группы не существует');
		if(empty($full_name)||empty($short_name)) return Ajax::_responseError('Ошибка выполнения','Не задано имя группы');

		if(!$admin_employers->groupUpdate($group_id, array(
			'full_name'		=> $full_name,
			'short_name'	=> $short_name
		))){
			return Ajax::_responseError('Ошибка выполнения','Ошибка обновления информации о группе');
		}

		Protocol::_add(array(
			'action_name'	=> $request_action,
			'acl_id'		=> $uaccess->getObjectIdFormName('employers.groups.edit'),
			'acl_name'		=> 'employers.groups.edit',
			'primary_type'	=> 'group',
			'primary_id'	=> $group_id,
			'description'	=> 'Изменена существующая группа сотрудников',
			'value'	=> array(
				'group_id'		=> $group_id,
				'full_name'		=> $full_name,
				'short_name'	=> $short_name
			)
		));


		#Выполнено успешно
		return Ajax::_setData(array(
			'sobject' => $group_id,
			'groups' => $admin_employers->getGroupsListEx()
		));

	break; #Редактирование группы





	/*******************************************************************
	 * Удаление группы
	 ******************************************************************/
	case 'group.delete':

		$group_id = $request->getId('group_id', 0);

		if(!$uaccess->checkAccess('employers.groups.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять список групп');
		}

		$admin_employers = new Admin_Employers();
		if(!$admin_employers->groupExists($group_id)) return Ajax::_responseError('Ошибка выполнения','Редактируемой должности не существует');

		//Проверка допустимости удаления группы, для обеспечения целостности
		if(!$admin_employers->groupCanDelete($group_id)){
			return Ajax::_responseError('Ошибка выполнения','Нельзя удалить группу, поскольку она используется в маршрутах согласований или информационных ресурсах');
		}

		if(!$admin_employers->groupDelete($group_id, false)){
			return Ajax::_responseError('Ошибка выполнения','Ошибка удаления группы');
		}

		Protocol::_add(array(
			'action_name'	=> $request_action,
			'acl_id'		=> $uaccess->getObjectIdFormName('employers.groups.edit'),
			'acl_name'		=> 'employers.groups.edit',
			'primary_type'	=> 'group',
			'primary_id'	=> $group_id,
			'description'	=> 'Удалена существующая группа сотрудников',
			'value'			=> array()
		));

		#Выполнено успешно
		return Ajax::_setData(array(
			'groups' => $admin_employers->getGroupsListEx()
		));

	break; #Удаление группы




	/*******************************************************************
	 * Список сотрудников в группе
	 ******************************************************************/
	case 'group.employers':

		$group_id = $request->getId('group_id', 0);
		if(empty($group_id)) return Ajax::_responseError('Ошибка выполнения','Не задана группа');

		$admin_employers = new Admin_Employers();
		if(!$admin_employers->groupExists($group_id)) return Ajax::_responseError('Ошибка выполнения','Группа не существует');

		#Выполнено успешно
		return Ajax::_setData(array(
			'group_id' => $group_id,
			'employers' => $admin_employers->getGroupEmployers($group_id, false)
		));

	break; #Список сотрудников в группе






	/*******************************************************************
	 * Добавление сотрудника в группу
	 ******************************************************************/
	case 'group.include':

		$group_id = $request->getId('group_id', 0);
		$employers = $request->getArray('employers', null);
		if(empty($group_id)) return Ajax::_responseError('Ошибка выполнения','Не задана группа');
		if(empty($employers)||!is_array($employers)) return Ajax::_responseError('Ошибка выполнения','Не заданы сотрудники');

		if(!$uaccess->checkAccess('employers.groups.moderate', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете добавлять или удалять сотрудников из групп');
		}

		$admin_employers = new Admin_Employers();
		if(!$admin_employers->groupExists($group_id)) return Ajax::_responseError('Ошибка выполнения','Группа не существует');
		$employers = array_map('intval',$employers);

		$db = Database::getInstance('main');
		$db->transaction();

		foreach($employers as $employer_id){
			if(!$admin_employers->groupIncludeEmployer($group_id, $employer_id)){
				$db->rollback();
				return Ajax::_responseError('Ошибка выполнения','Ошибка добавления сотрудника [ID:'.$employer_id.'] в группу [ID:'.$group_id.']');
			}
			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('employers.groups.moderate'),
				'acl_name'		=> 'employers.groups.moderate',
				'primary_type'	=> 'employer',
				'primary_id'	=> $employer_id,
				'secondary_type'=> 'group',
				'secondary_id'	=> $group_id,
				'description'	=> 'Сотрудник добавлен в группу ID:'.$group_id,
				'value'			=> array()
			));
		}

		#Выполнено успешно
		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			//'sobject' => $group_id,
			'group_id' => $group_id,
			'employers' => $admin_employers->getGroupEmployers($group_id, false)
			//'groups' => $admin_employers->getGroupsListEx()
		));

	break; #Добавление сотрудника в группу






	/*******************************************************************
	 * Удаление сотрудника из группы
	 ******************************************************************/
	case 'group.exclude':

		$group_id = $request->getId('group_id', 0);
		$employers = $request->getArray('employers', null);
		if(empty($group_id)) return Ajax::_responseError('Ошибка выполнения','Не задана группа');
		if(empty($employers)||!is_array($employers)) return Ajax::_responseError('Ошибка выполнения','Не заданы сотрудники');

		if(!$uaccess->checkAccess('employers.groups.moderate', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете добавлять или удалять сотрудников из групп');
		}

		$admin_employers = new Admin_Employers();
		if(!$admin_employers->groupExists($group_id)) return Ajax::_responseError('Ошибка выполнения','Группа не существует');
		$employers = array_map('intval',$employers);

		$db = Database::getInstance('main');
		$db->transaction();

		foreach($employers as $employer_id){
			if(!$admin_employers->groupExcludeEmployer($group_id, $employer_id)){
				$db->rollback();
				return Ajax::_responseError('Ошибка выполнения','Ошибка удаления сотрудника [ID:'.$employer_id.'] из группы [ID:'.$group_id.']');
			}
			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('employers.groups.moderate'),
				'acl_name'		=> 'employers.groups.moderate',
				'primary_type'	=> 'employer',
				'primary_id'	=> $employer_id,
				'secondary_type'=> 'group',
				'secondary_id'	=> $group_id,
				'description'	=> 'Сотрудник исключен из группы ID:'.$group_id,
				'value'			=> array()
			));
		}

		#Выполнено успешно
		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			//'sobject' => $group_id,
			//'groups' => $admin_employers->getGroupsListEx()
			'group_id' => $group_id,
			'employers' => $admin_employers->getGroupEmployers($group_id, false)
		));

	break; #Удаление сотрудника из группы






	/*******************************************************************
	 * Добавление сотрудника
	 ******************************************************************/
	case 'employers.add':

		if(!$uaccess->checkAccess('employers.add', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете добавлять новых сотрудников');
		}

		$email				= trim(Request::_getEmail('email', ''));
		$phone				= trim(Request::_getStr('phone', ''));
		$birth_date			= Request::_getDate('birth_date', false);
		$first_name			= mb_convert_case(trim(Request::_getStr('first_name', '')), MB_CASE_TITLE, 'UTF-8');
		$last_name			= mb_convert_case(trim(Request::_getStr('last_name', '')), MB_CASE_TITLE, 'UTF-8');
		$middle_name		= mb_convert_case(trim(Request::_getStr('middle_name', '')), MB_CASE_TITLE, 'UTF-8');

		if(empty($first_name)||empty($last_name)||empty($middle_name)||empty($phone)||empty($birth_date)){
			return Ajax::_responseError('Ошибка выполнения','Анкета заполнена не полностью');
		}

		$admin_employers = new Admin_Employers();

		$db = Database::getInstance('main');
		$db->transaction();

			if(($employer = $admin_employers->employerNew(array(
				'first_name'	=> $first_name,
				'last_name'		=> $last_name,
				'middle_name'	=> $middle_name,
				'birth_date'	=> $birth_date,
				'email'			=> $email,
				'phone'			=> $phone
			)))===false){
				$db->rollback();
				return Ajax::_responseError('Ошибка выполнения','Ошибка добавления нового сотрудника');
			}

			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('employers.add'),
				'acl_name'		=> 'employers.add',
				'primary_type'	=> 'employer',
				'primary_id'	=> $employer['employer_id'],
				'description'	=> 'Создана учетная запись сотрудника',
				'value'			=> $employer
			));

		#Выполнено успешно
		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			'employer' => $employer
		));

	break; #Добавление сотрудника







	/*******************************************************************
	 * Отправка файла сертификата сотрудника на сервер
	 ******************************************************************/
	case 'employers.certificate.upload':

		if(!$uaccess->checkAccess('employers.certificate.moderate', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете добавлять сертификаты для сотрудников');
		}

		header('Content-Type: text/html; charset=utf-8', true);

		$employer_id = Request::_getId('employer_id', 0);

		#Файл не задан
		if(!$_FILES['certificate']['size']){
			return Ajax::_responseError('Ошибка выполнения','Не задан файл сертификата');
		}

		#Размер файла
		if($_FILES['certificate']['size'] > 8192){
			return Ajax::_responseError('Ошибка выполнения','Слишком большой размер файла');
		}

		#Ошибка загрузки файла
		if($_FILES['certificate']['error']){
			return Ajax::_responseError('Ошибка выполнения','Ошибка загрузки файла: '.$_FILES['certificate']['error']);
		}

		try{
			$SSL_CLIENT_CERT=trim(file_get_contents($_FILES['certificate']['tmp_name']));
			if(($cert = @openssl_x509_read($SSL_CLIENT_CERT))===false) return Ajax::_responseError('Ошибка выполнения','Ошибка обработки файла сертификата');

			$cert_info = @openssl_x509_parse($cert);

			$SSL_CERT_HASH			= sha1($SSL_CLIENT_CERT);
			$SSL_CLIENT_M_SERIAL	= $cert_info['serialNumber'];
			$SSL_CLIENT_S_DN_L		= $cert_info['subject']['L'];
			$SSL_CLIENT_S_DN_O		= $cert_info['subject']['O'];
			$SSL_CLIENT_S_DN_OU		= $cert_info['subject']['OU'];
			$SSL_CLIENT_S_DN_CN		= $cert_info['subject']['CN'];

			@openssl_x509_free($cert);

		}catch (Exception $e){
			return Ajax::_responseError('Ошибка выполнения','Ошибка обработки файла сертификата');
		}

		$admin_employers = new Admin_Employers();

		if(empty($employer_id)||!$admin_employers->employerExists($employer_id)){
			return Ajax::_responseError('Ошибка выполнения','Указанный сотрудник не существует');
		}

		$db = Database::getInstance('main');
		$db->transaction();

		//Проверка существования сертификата
		$db->prepare('SELECT * FROM `employer_certs` WHERE `SSL_CERT_HASH` LIKE ? LIMIT 1');
		$db->bind($SSL_CERT_HASH);
		if(($cert_user = $db->selectRecord())===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Ошибка проверки сертификата');
		}
		if(!empty($cert_user)){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Выбранный сертификат уже зарегестрирован за сотрудником ID:'.$cert_user['employer_id']);
		}

		$db->prepare('INSERT INTO `employer_certs` (`employer_id`,`is_lock`,`SSL_CERT_HASH`,`SSL_CLIENT_M_SERIAL`,`SSL_CLIENT_S_DN_L`,`SSL_CLIENT_S_DN_O`,`SSL_CLIENT_S_DN_OU`,`SSL_CLIENT_S_DN_CN`,`SSL_CLIENT_CERT`) VALUES(?,?,?,?,?,?,?,?,?)');
		$db->bind($employer_id);
		$db->bind(0);
		$db->bind($SSL_CERT_HASH);
		$db->bind($SSL_CLIENT_M_SERIAL);
		$db->bind($SSL_CLIENT_S_DN_L);
		$db->bind($SSL_CLIENT_S_DN_O);
		$db->bind($SSL_CLIENT_S_DN_OU);
		$db->bind($SSL_CLIENT_S_DN_CN);
		$db->bind($SSL_CLIENT_CERT);
		if(($cert_id = $db->insert())===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Ошибка записи сертификата в базу данных');
		}

			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('employers.certificate.moderate'),
				'acl_name'		=> 'employers.certificate.moderate',
				'primary_type'	=> 'employer',
				'primary_id'	=> $employer_id,
				'secondary_type'=> 'employercert',
				'secondary_id'	=> $cert_id,
				'description'	=> 'Добавлен сертификат',
				'value'	=> array(
					'SSL_CERT_HASH'			=> $SSL_CERT_HASH,
					'SSL_CLIENT_M_SERIAL'	=> $SSL_CLIENT_M_SERIAL,
					'SSL_CLIENT_S_DN_L'		=> $SSL_CLIENT_S_DN_L,
					'SSL_CLIENT_S_DN_O'		=> $SSL_CLIENT_S_DN_O,
					'SSL_CLIENT_S_DN_OU'	=> $SSL_CLIENT_S_DN_OU,
					'SSL_CLIENT_CERT'		=> $SSL_CLIENT_CERT
				)
			));

		#Выполнено успешно
		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			'employer_certs' => $admin_employers->getEmployersCertificates($employer_id, array('employer_id','SSL_CERT_HASH','SSL_CLIENT_M_SERIAL','SSL_CLIENT_S_DN_L','SSL_CLIENT_S_DN_O','SSL_CLIENT_S_DN_OU','SSL_CLIENT_S_DN_CN'))
		));

	break; #Отправка файла сертификата сотрудника на сервер





	/*******************************************************************
	 * Удаление сертификата сотрудника
	 ******************************************************************/
	case 'employers.certificate.delete':

		if(!$uaccess->checkAccess('employers.certificate.moderate', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете удалять сертификаты сотрудников');
		}

		$employer_id = Request::_getId('employer_id', 0);
		$sha1 = Request::_getStr('sha1', '');

		#Файл не задан
		if(empty($employer_id )||empty($sha1)){
			return Ajax::_responseError('Ошибка выполнения','Не выбран сертификат для удаления');
		}

		$admin_employers = new Admin_Employers();

		if(empty($employer_id)||!$admin_employers->employerExists($employer_id)){
			return Ajax::_responseError('Ошибка выполнения','Указанный сотрудник не существует');
		}

		$db = Database::getInstance('main');
		$db->transaction();

		//Проверка существования сертификата
		$db->prepare('SELECT * FROM `employer_certs` WHERE `employer_id`=? AND `SSL_CERT_HASH` LIKE ? LIMIT 1');
		$db->bind($employer_id);
		$db->bind($sha1);
		if(($cert = $db->selectRecord())===false){
			return Ajax::_responseError('Ошибка выполнения','Ошибка удаления сертификата');
		}
		if(empty($cert)){
			return Ajax::_responseError('Ошибка выполнения','Удаляемый сертификат не найден');
		}

		$db->prepare('DELETE FROM `employer_certs` WHERE `employer_id`=? AND `SSL_CERT_HASH` LIKE ?');
		$db->bind($employer_id);
		$db->bind($sha1);
		if($db->delete()===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Ошибка удаления сертификата');
		}

			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('employers.certificate.moderate'),
				'acl_name'		=> 'employers.certificate.moderate',
				'primary_type'	=> 'employer',
				'primary_id'	=> $employer_id,
				'secondary_type'=> 'employercert',
				'secondary_id'	=> $cert['id'],
				'description'	=> 'Удален сертификат',
				'value'			=> $cert
			));

		#Выполнено успешно
		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			'employer_certs' => $admin_employers->getEmployersCertificates($employer_id, array('employer_id','SSL_CERT_HASH','SSL_CLIENT_M_SERIAL','SSL_CLIENT_S_DN_L','SSL_CLIENT_S_DN_O','SSL_CLIENT_S_DN_OU','SSL_CLIENT_S_DN_CN'))
		));

	break; #Удаление сертификата сотрудника






	/*******************************************************************
	 * Изменение информации о сотруднике
	 ******************************************************************/
	case 'employers.info.change':

		if(!$uaccess->checkAccess('employers.info.change', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете редактировать карточку сотрудника');
		}

		$employer_id = Request::_getId('employer_id', 0);
		$type = Request::_getStr('type','');

		$admin_employers = new Admin_Employers();

		if(empty($employer_id)||!$admin_employers->employerExists($employer_id)){
			return Ajax::_responseError('Ошибка выполнения','Указанный сотрудник не существует');
		}

		$success_title='';
		$allowed_fields = array();
		switch($type){
			case 'info':
				$success_title = 'Изменение данных сотрудника';
				$allowed_fields = array('first_name','last_name','middle_name','birth_date','phone','email');
			break;
			case 'username':
				$success_title = 'Изменение имени пользователя';
				$allowed_fields = array('username');
			break;
			case 'password':
				$success_title = 'Смена пароля';
				$allowed_fields = array('password');
			break;
			case 'pincode':
				$success_title = 'Изменение настроек PIN-кода';
				$allowed_fields = array('pin_code','ignore_pin');
			break;
			case 'access':
				$success_title = 'Изменение аттрибутов доступа';
				$allowed_fields = array('access_level','status');
			break;
			case 'notice':
				$success_title = 'Изменение уведомлений';
				$allowed_fields = array('notice_me_requests','notice_curator_requests','notice_gkemail_1','notice_gkemail_2','notice_gkemail_3','notice_gkemail_4');
			break;
			default:
				return Ajax::_responseError('Ошибка выполнения','Неизвестный тип запроса');
		}

		$update_fields=array();
		foreach($allowed_fields as $field){

			$value = trim($request->getStr($field,''));

			switch($field){
				case 'first_name':
				case 'last_name':
				case 'middle_name':
					if(empty($value)) return Ajax::_responseError('Ошибка выполнения','ФИО не задано');
					if(!preg_match('/^[a-zА-Яа-яЁё]+$/u', $value)) return Ajax::_responseError('Ошибка выполнения','ФИО указано некорректно: '.$field.'=['.$value.']');
					$update_fields[$field] = mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
				break;

				case 'birth_date':
					if(!empty($value)){
						if(!preg_match('/^[0-9]{2}\.[0-9]{2}\.[0-9]{4}$/', $value)) return Ajax::_responseError('Ошибка выполнения','Дата указана некорректно');
						$update_fields[$field] = date('Y-m-d', strtotime($value));
					}
				break;

				case 'email':
					if(!empty($value)){
						if(!preg_match('/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix', $value)) return Ajax::_responseError('Ошибка выполнения','E-Mail указан некорректно');
						$update_fields[$field] = $value;
					}
				break;

				case 'phone':
					if(!empty($value)){
						if(!preg_match('/^(([0-9]{1})*[- .(]*([0-9]{3})[- .)]*[0-9]{3}[- .]*[0-9]{2}[- .]*[0-9]{2})+$/', $value)) return Ajax::_responseError('Ошибка выполнения','Телефон указан некорректно');
						$update_fields[$field] = $value;
					}
				break;

				case 'username':
					if(empty($value)) return Ajax::_responseError('Ошибка выполнения','Не задано новое имя пользователя');
					if(!preg_match('/^[a-zA-Z0-9\_\-]+$/', $value)) return Ajax::_responseError('Ошибка выполнения','Имя пользователя указано некорректно');
					$update_fields[$field] = $value;
				break;

				case 'pin_code':
					if(!empty($value)){
						if(!preg_match('/^[a-zA-Z0-9\_\-]+$/', $value)) return Ajax::_responseError('Ошибка выполнения','PIN-код указан некорректно');
						$update_fields[$field] = $value;
					}
				break;

				case 'status':
				case 'notice_me_requests':
				case 'notice_curator_requests':
				case 'notice_gkemail_1':
				case 'notice_gkemail_2':
				case 'notice_gkemail_3':
				case 'notice_gkemail_4':
				case 'ignore_pin':
					$update_fields[$field] = ($value == '1' ? '1':'0');
				break;

				case 'password':
					if(empty($value)) return Ajax::_responseError('Ошибка выполнения','Не задан пароль');
					if(!preg_match('/(?=^.{8,}$)((?=.*[A-Za-z0-9])(?=.*[A-Z])(?=.*[a-z]))^.*/', $value)) return Ajax::_responseError('Ошибка выполнения','Пароль указан некорректно');
					$update_fields[$field] = $value;
				break;

				case 'access_level':
					$update_fields[$field] = max(0,intval($value));
				break;
			}//switch field
		}//foreach allowed_fields


		if($type == 'info'){
			$update_fields['search_name'] = $update_fields['last_name'].' '.$update_fields['first_name'].' '.$update_fields['middle_name'];
		}

		//Смена имени пользоваателя - проверка, занято ли имяч пользователя или нет
		if($type == 'username'){
			if($admin_employers->employerExists(array(
				array('employer_id',$employer_id,null,'!='),
				'username'=>$update_fields['username']
			))){
				return Ajax::_responseError('Ошибка выполнения','Указанное имя пользователя уже занято');
			}
		}

		if(!$admin_employers->employerUpdate($employer_id, $update_fields)){
			return Ajax::_responseError('Ошибка выполнения','Ошибка обновления информации о сотруднике');
		}

			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('employers.info.change'),
				'acl_name'		=> 'employers.info.change',
				'primary_type'	=> 'employer',
				'primary_id'	=> $employer_id,
				'description'	=> 'Изменена информация сотрудника',
				'value'			=> $update_fields
			));


		#Выполнено успешно
		Ajax::_responseSuccess($success_title,'Операция выполнена успешно');
		return Ajax::_setData(array(
			'employer_info'	=>	$admin_employers->getEmployersList($employer_id, array(
				'employer_id','status','access_level','username','search_name','first_name','last_name',
				'middle_name','birth_date','phone','email','work_name','work_address',
				'work_post','work_phone','create_date','anket_id','never_assistant',
				'notice_me_requests','notice_curator_requests',
				'notice_gkemail_1','notice_gkemail_2','notice_gkemail_3',
				'notice_gkemail_4','ignore_pin'
			),true)
		));

	break; #Изменение информации о сотруднике






	/*******************************************************************
	 * Добавление сотрудника в группу
	 ******************************************************************/
	case 'employers.group.include':

		$employer_id = $request->getId('employer_id', 0);
		$groups = $request->getArray('groups', null);
		if(empty($employer_id)) return Ajax::_responseError('Ошибка выполнения','Не указан сотрудник');
		if(empty($groups)||!is_array($groups)) return Ajax::_responseError('Ошибка выполнения','Не заданы группы');

		if(!$uaccess->checkAccess('employers.groups.moderate', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете добавлять или удалять сотрудников из групп');
		}
		$admin_employers = new Admin_Employers();
		if(!$admin_employers->employerExists($employer_id)) return Ajax::_responseError('Ошибка выполнения','Указанный сотрудник не существует');

		$db = Database::getInstance('main');
		$db->transaction();

		foreach($groups as $group_id){
			if(!$admin_employers->groupIncludeEmployer($group_id, $employer_id)){
				$db->rollback();
				return Ajax::_responseError('Ошибка выполнения','Ошибка добавления сотрудника [ID:'.$employer_id.'] в группу [ID:'.$group_id.']');
			}
			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('employers.groups.moderate'),
				'acl_name'		=> 'employers.groups.moderate',
				'primary_type'	=> 'employer',
				'primary_id'	=> $employer_id,
				'secondary_type'=> 'group',
				'secondary_id'	=> $group_id,
				'description'	=> 'Сотрудник добавлен в группу ID:'.$group_id,
				'value'			=> array()
			));
		}

		#Выполнено успешно
		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			'employer_groups' => $admin_employers->getEmployersGroups($employer_id)
		));

	break; #Добавление сотрудника в группу




	/*******************************************************************
	 * Удаление сотрудника из группы
	 ******************************************************************/
	case 'employers.group.exclude':

		$employer_id = $request->getId('employer_id', 0);
		$groups = $request->getArray('groups', null);
		if(empty($employer_id)) return Ajax::_responseError('Ошибка выполнения','Не указан сотрудник');
		if(empty($groups)||!is_array($groups)) return Ajax::_responseError('Ошибка выполнения','Не заданы группы');

		if(!$uaccess->checkAccess('employers.groups.moderate', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете добавлять или удалять сотрудников из групп');
		}
		$admin_employers = new Admin_Employers();
		if(!$admin_employers->employerExists($employer_id)) return Ajax::_responseError('Ошибка выполнения','Указанный сотрудник не существует');

		$db = Database::getInstance('main');
		$db->transaction();

		foreach($groups as $group_id){
			if(!$admin_employers->groupExcludeEmployer($group_id, $employer_id)){
				$db->rollback();
				return Ajax::_responseError('Ошибка выполнения','Ошибка удаления сотрудника [ID:'.$employer_id.'] из группы [ID:'.$group_id.']');
			}
			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('employers.groups.moderate'),
				'acl_name'		=> 'employers.groups.moderate',
				'primary_type'	=> 'employer',
				'primary_id'	=> $employer_id,
				'secondary_type'=> 'group',
				'secondary_id'	=> $group_id,
				'description'	=> 'Сотрудник исключен из группы ID:'.$group_id,
				'value'			=> array()
			));
		}

		#Выполнено успешно
		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			'employer_groups' => $admin_employers->getEmployersGroups($employer_id)
		));

	break; #Удаление сотрудника из группы







	/*******************************************************************
	 * Добавление должности сотруднику
	 ******************************************************************/
	case 'employers.post.add':

		if(!$uaccess->checkAccess('employers.post.moderate', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете редактировать должности сотрудника');
		}

		$employer_id = $request->getId('employer_id', 0);
		$company_id = $request->getId('company_id', 0);
		$post_uid = $request->getId('post_uid', 0);
		$date_from = $request->getDate('date_from', null);
		$date_to = $request->getDate('date_to', null);
		$template = $request->getBool('template', false);
		$template_id = 0;

		if(empty($employer_id)||empty($company_id)||empty($post_uid)||empty($date_from)||empty($date_to)) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');

		$admin_template = new Admin_Template();
		$organization = new Admin_Organization();
		$admin_employers = new Admin_Employers();
		if(!$admin_employers->employerExists($employer_id)) return Ajax::_responseError('Ошибка выполнения','Указанный сотрудник не существует');
		if(!$organization->companyExists($company_id)) return Ajax::_responseError('Ошибка выполнения','Указанная организация не существует');
		if(!$organization->postUIDExists($post_uid, $company_id)) return Ajax::_responseError('Ошибка выполнения','Указанная должность не существует в выбранной организации');
		if($admin_employers->employerOnPost($employer_id,$post_uid)) return Ajax::_responseError('Ошибка выполнения','Сотрудник уже занимает указанную должность');
		if($template){
			$template_id = $admin_template->templateForPost($post_uid, $company_id);
			if(empty($template_id)) $template = false;
		}

		$db = Database::getInstance('main');
		$db->transaction();

			if(!$admin_employers->employerPostAdd(array(
				'employer_id'	=> $employer_id,
				'company_id'	=> $company_id,
				'post_uid'		=> $post_uid,
				'post_from'		=> date2sql($date_from),
				'post_to'		=> date2sql($date_to)
			))){
				$db->rollback();
				return Ajax::_responseError('Ошибка выполнения','Ошибка добавления должности сотруднику');
			}

			//Шаблон доступа для должности
			if($template){
				$main_request = new Main_Request();
				if(!$main_request->createFromTemplate(array(
					'template_id'	=> $template_id,
					'employer_id'	=> $employer_id,
					'curator_id'	=> $user->getEmployerID(),
					'company_id'	=> $company_id,
					'post_uid'		=> $post_uid
				))){
					$db->rollback();
					return Ajax::_responseError('Ошибка выполнения','Ошибка создания заявки из шаблона');
				}

			}//Шаблон доступа для должности


			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('employers.post.moderate'),
				'acl_name'		=> 'employers.post.moderate',
				'primary_type'	=> 'employer',
				'primary_id'	=> $employer_id,
				'secondary_type'=> 'orgstructure',
				'secondary_id'	=> $post_uid,
				'description'	=> 'Сотруднику добавлена должность UID:'.$post_uid,
				'value'			=> array(
					'template_id'	=> $template_id,
					'employer_id'	=> $employer_id,
					'curator_id'	=> $user->getEmployerID(),
					'company_id'	=> $company_id,
					'post_uid'		=> $post_uid,
					'post_from'		=> $date_from,
					'post_to'		=> $date_to
				)
			));

		#Выполнено успешно
		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			'employer_posts' => $admin_employers->getEmployersPostsEx($employer_id, true)
		));

	break; #Добавление должности сотруднику







	/*******************************************************************
	 * Поиск сотрудников
	 ******************************************************************/
	case 'employers.search':

		$extended = $request->getStr('extended',false);
		$allowed_fields = array('status','employer_id','search_name','username','phone','email');
		$conditions = array();
		switch($extended){

			//Поиск для emloyers_list
			case 'employers_list':
				foreach($allowed_fields as $field){
					switch($field){
						case 'status':
							$value = $request->getId($field,1);
							$conditions[$field] = $value;
						break;
						case 'search_name':
							$value = trim($request->getStr($field,false));
							if(empty($value))break;
							$conditions[] = array(
							'field'=>array('search_name','username'),
							'value'=>$value,
							'glue' => 'LIKE%',
							'bridge'=>',',
							'field_bridge' => 'OR'
							);
						break;
					}
				}
				$return_fields = array('employer_id','status','anket_id','search_name','phone','email','username','birth_date');
			break;

			//Поиск по-умолчанию
			default: 
				foreach($allowed_fields as $field){
					$value = trim($request->getStr($field,''));
					if(empty($value)) continue;
					switch($field){
						case 'employer_id':
							$value = intval($value);
							if($value>0) $conditions[$field] = $value;
						break;
						case 'phone':
						case 'search_name':
						case 'email':
						case 'username':
							$conditions[] = array($field,$value,null,'LIKE%');
						break;
					}
				}
				if(!empty($conditions)){
					$conditions[]=array('status',0,null,'>');
				}
				$return_fields = array('employer_id','search_name','phone','email','username','birth_date');
			break;

		}//extended

		//$db = Database::getInstance('main');
		//print_r($db->buildSqlConditions($conditions,'EMPL'));

		$admin_employers = new Admin_Employers();

		#Выполнено успешно
		return Ajax::_setData(array(
			'employers_search' => empty($conditions) ? array() : $admin_employers->getEmployersList($conditions,$return_fields)
		));

	break; #Поиск сотрудников





	/*******************************************************************
	 * Добавление сотруднику ассистента
	 ******************************************************************/
	case 'employers.assistant.add':

		if(!$uaccess->checkAccess('employers.assistant.moderate', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете делегировать полномочия сотрудникам');
		}

		$for_employer = Request::_getId('for_employer', 0);
		$employer_id = Request::_getId('employer_id', 0);
		$assistant_id = Request::_getId('assistant_id', 0);
		$date_from = $request->getDate('date_from', null);
		$date_to = $request->getDate('date_to', null);


		if(empty($employer_id)||empty($assistant_id)||empty($date_from)||empty($date_to)){
			return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');
		}

		if($employer_id == $assistant_id) return Ajax::_responseError('Ошибка выполнения','Нельзя делегировать полномочия самому себе');

		$admin_employers = new Admin_Employers();

		if(!$admin_employers->employerExists($employer_id) || !$admin_employers->employerExists($assistant_id)){
			return Ajax::_responseError('Ошибка выполнения','Указанный сотрудник не существует');
		}

		$db = Database::getInstance('main');
		$db->transaction();

		if(!$admin_employers->assistantDelete($employer_id,$assistant_id)){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Ошибка делегирования полномочий выбранному сотруднику');
		}

		if($admin_employers->assistantAdd(array(
			'employer_id' => $employer_id,
			'assistant_id' => $assistant_id,
			'from_date' => date('Y-m-d', strtotime($date_from)),
			'to_date' => date('Y-m-d', strtotime($date_to))
		)) === false){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Ошибка делегирования полномочий выбранному сотруднику');
		}

			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('employers.assistant.moderate'),
				'acl_name'		=> 'employers.assistant.moderate',
				'primary_type'	=> 'employer',
				'primary_id'	=> $employer_id,
				'secondary_type'=> 'employer',
				'secondary_id'	=> $assistant_id,
				'description'	=> 'Сотруднику добавлен заместитель ID:'.$assistant_id,
				'value'			=> array(
					'employer_id'	=> $employer_id,
					'assistant_id'	=> $assistant_id,
					'from_date'		=> $date_from,
					'to_date'		=> $date_to
				)
			));

		#Выполнено успешно
		$db->commit();
		if(empty($for_employer)){
			return Ajax::_setData(array());
		}

		return Ajax::_setData(array(
			'employer_assistants'	=> $admin_employers->getEmployersAssistantsEx($for_employer, true),
			'employer_delegates'	=> $admin_employers->getEmployersDelegatesEx($for_employer, true)
		));

	break; #Добавление сотруднику ассистента






	/*******************************************************************
	 * Удаление ассистента у сотрудника
	 ******************************************************************/
	case 'employers.assistant.delete':

		if(!$uaccess->checkAccess('employers.assistant.moderate', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете делегировать полномочия сотрудникам');
		}

		$for_employer = Request::_getId('for_employer', 0);
		$employer_id = Request::_getId('employer_id', 0);
		$assistant_id = Request::_getId('assistant_id', 0);


		if(empty($employer_id)||empty($assistant_id)){
			return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');
		}

		if($employer_id == $assistant_id) return Ajax::_responseError('Ошибка выполнения','Нельзя удалить полномочия у самого себя');

		$admin_employers = new Admin_Employers();

		if(!$admin_employers->employerExists($employer_id) || !$admin_employers->employerExists($assistant_id)){
			return Ajax::_responseError('Ошибка выполнения','Указанный сотрудник не существует');
		}

		$db = Database::getInstance('main');
		$db->transaction();

		if(!$admin_employers->assistantDelete($employer_id, $assistant_id)){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Ошибка делегирования полномочий выбранному сотруднику');
		}

			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('employers.assistant.moderate'),
				'acl_name'		=> 'employers.assistant.moderate',
				'primary_type'	=> 'employer',
				'primary_id'	=> $employer_id,
				'secondary_type'=> 'employer',
				'secondary_id'	=> $assistant_id,
				'description'	=> 'У сотрудника удален заместитель ID:'.$assistant_id,
				'value'			=> array(
					'employer_id'	=> $employer_id,
					'assistant_id'	=> $assistant_id
				)
			));

		#Выполнено успешно
		$db->commit();
		if(empty($for_employer)){
			return Ajax::_setData(array());
		}

		return Ajax::_setData(array(
			'employer_assistants'	=> $admin_employers->getEmployersAssistantsEx($for_employer, true),
			'employer_delegates'	=> $admin_employers->getEmployersDelegatesEx($for_employer, true)
		));

	break; #Удаление ассистента у сотрудника





	/*******************************************************************
	 * История делегирования для сотрудника
	 ******************************************************************/
	case 'employers.assistant.history':

		$employer_id = Request::_getId('employer_id', 0);
		if(empty($employer_id)) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');

		$admin_employers = new Admin_Employers();
		if(!$admin_employers->employerExists($employer_id)) return Ajax::_responseError('Ошибка выполнения','Указанный сотрудник не существует');

		return Ajax::_setData(array(
			'employer_id'			=> $employer_id,
			'assistants_history'	=> $admin_employers->getEmployersAssistantsHistory($employer_id)
		));

	break; #История делегирования для сотрудника






	/*******************************************************************
	 * Добавление сотруднику прав в организациях
	 ******************************************************************/
	case 'employers.right.add':

		$employer_id = $request->getId('employer_id', 0);
		$companies = $request->getArray('companies', null);
		$right_type = trim($request->getStr('right_type', null));
		if(empty($employer_id)) return Ajax::_responseError('Ошибка выполнения','Не указан сотрудник');
		if(empty($companies)||!is_array($companies)) return Ajax::_responseError('Ошибка выполнения','Не заданы организации');
		if(!in_array($right_type,array('can_curator','can_add_employer'),true)) return Ajax::_responseError('Ошибка выполнения','Не задан тип прав');

		if(!$uaccess->checkAccess('employers.right.moderate', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете менять права сотрудников');
		}

		$admin_employers = new Admin_Employers();
		if(!$admin_employers->employerExists($employer_id)) return Ajax::_responseError('Ошибка выполнения','Указанный сотрудник не существует');
		$organization = new Admin_Organization();

		$db = Database::getInstance('main');
		$db->transaction();

		foreach($companies as $company_id){
			if($company_id>0){
				if(!$organization->companyExists($company_id)){
					$db->rollback();
					return Ajax::_responseError('Ошибка выполнения','Указанная организация ID:['.$company_id.'] не существует');
				}
			}
			if(!$admin_employers->employerSetRight($employer_id, $company_id, $right_type, true)){
				$db->rollback();
				return Ajax::_responseError('Ошибка выполнения','Ошибка добавления прав');
			}
			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('employers.right.moderate'),
				'acl_name'		=> 'employers.right.moderate',
				'primary_type'	=> 'employer',
				'primary_id'	=> $employer_id,
				'description'	=> 'Сотруднику добавлены права '.$right_type.' в организации ID:'.$company_id,
				'value'			=> array(
					'employer_id'	=> $employer_id,
					'right_type'	=> $right_type,
					'company_id'	=> $company_id
				)
			));
		}

		#Выполнено успешно
		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			'employer_id' => $employer_id,
			'employer_rights' => $admin_employers->getEmployersRights($employer_id)
		));

	break; #Добавление сотруднику прав в организациях




	/*******************************************************************
	 * Снятие с сотрудника прав в организациях
	 ******************************************************************/
	case 'employers.right.delete':

		$employer_id = $request->getId('employer_id', 0);
		$companies = $request->getArray('companies', null);
		$right_type = trim($request->getStr('right_type', null));
		if(empty($employer_id)) return Ajax::_responseError('Ошибка выполнения','Не указан сотрудник');
		if(empty($companies)||!is_array($companies)) return Ajax::_responseError('Ошибка выполнения','Не заданы организации');
		if(!in_array($right_type,array('can_curator','can_add_employer'),true)) return Ajax::_responseError('Ошибка выполнения','Не задан тип прав');

		if(!$uaccess->checkAccess('employers.right.moderate', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете менять права сотрудников');
		}

		$admin_employers = new Admin_Employers();
		if(!$admin_employers->employerExists($employer_id)) return Ajax::_responseError('Ошибка выполнения','Указанный сотрудник не существует');
		$organization = new Admin_Organization();

		$db = Database::getInstance('main');
		$db->transaction();

		foreach($companies as $company_id){
			if($company_id>0){
				if(!$organization->companyExists($company_id)){
					$db->rollback();
					return Ajax::_responseError('Ошибка выполнения','Указанная организация ID:['.$company_id.'] не существует');
				}
			}
			if(!$admin_employers->employerSetRight($employer_id, $company_id, $right_type, false)){
				$db->rollback();
				return Ajax::_responseError('Ошибка выполнения','Ошибка добавления прав');
			}
			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('employers.right.moderate'),
				'acl_name'		=> 'employers.right.moderate',
				'primary_type'	=> 'employer',
				'primary_id'	=> $employer_id,
				'description'	=> 'У сотрудника сняты права '.$right_type.' в организации ID:'.$company_id,
				'value'			=> array(
					'employer_id'	=> $employer_id,
					'right_type'	=> $right_type,
					'company_id'	=> $company_id
				)
			));
		}

		#Выполнено успешно
		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			'employer_id' => $employer_id,
			'employer_rights' => $admin_employers->getEmployersRights($employer_id)
		));

	break; #Снятие с сотрудника прав в организациях




	/*******************************************************************
	 * Список анкет
	 ******************************************************************/
	case 'employers.ankets.list':

		$company_id = $request->getId('company_id',0);
		$anket_type = $request->getId('anket_type',1);
		$search_name = $request->getStr('search_name','');
		$conditions = array();

		if($company_id > 0) $conditions['company_id'] = $company_id;
		if($anket_type > 0) $conditions['anket_type'] = $anket_type;
		if(!empty($search_name)) $conditions[] = array('last_name',$search_name,null,'LIKE%');

		if(empty($conditions)) $conditions = null;
		$admin_employers = new Admin_Employers();

		#Выполнено успешно
		return Ajax::_setData(array(
			'ankets' => $admin_employers->getAnketsListEx($conditions, null, false),
		));

	break; #Список анкет






	/*******************************************************************
	 * Сохранение анкеты
	 ******************************************************************/
	case 'employers.anket.save':

		$can_anket_moderate = $uaccess->checkAccess('employers.ankets.moderate', 0);
		$can_anket_edit = ($uaccess->checkAccess('employers.ankets.edit', 0) || $can_anket_moderate);

		if(!$can_anket_edit){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять анкеты сотрудников');
		}

		$organization = new Admin_Organization();
		$admin_employers = new Admin_Employers();

		$anket_id = $request->getId('anket_id', 0);
		$template_post = $request->getBool('template_post', false);
		$template_new = $request->getBool('template_new', false);
		if(empty($anket_id)) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');
		$anket_info = $admin_employers->getAnketInfo(array('anket_id'=>$anket_id));
		if(empty($anket_info)) return Ajax::_responseError('Ошибка выполнения','Анкета с указанным идентификатором ID:'.$anket_id.' не существует');
		if($anket_info['anket_type']!=1) return Ajax::_responseError('Ошибка выполнения','Эта анкета уже была обработана администратором, какие-либо изменения не допустимы');

		//Тип действия (заодно и тип анкеты): 1-новая, 2-отклонена, 3-согласована
		$anket_type = intval($request->getEnum('anket_type', array('1','2','3'), 1));

		if($anket_type !=1 && !$can_anket_moderate){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете согласовывать или отклонять анкеты сотрудников');
		}

		$allowed_fields = array('anket_type','company_id','post_uid','order_no','post_from','first_name','last_name','middle_name','birth_date','phone','email','work_computer','need_accesscard','comment');
		$update_fields=array(
			'anket_type' => $anket_type
		);
		if($anket_type !=1){
			$update_fields['approved_time'] = date('Y-m-d H:i:s');
		}
		//Если анкета не отклонена
		if($anket_type!=2){
			foreach($allowed_fields as $field){
				switch($field){

					case 'company_id':
						$value = $request->getId($field, 0);
						if(empty($value)) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');
						if(!$organization->companyExists($value)) return Ajax::_responseError('Ошибка выполнения','Указанная организация не существует');
						$update_fields[$field] = $value;
					break;

					case 'post_uid':
						$value = $request->getStr($field, '');
						if(empty($value)||!is_numeric($value)) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');
						if(!$organization->postUIDExists($value)) return Ajax::_responseError('Ошибка выполнения','Указанная должность не существует');
						$update_fields[$field] = $value;
					break;

					case 'first_name':
					case 'last_name':
					case 'middle_name':
						$value = $request->getStr($field, '');
						if(empty($value)) return Ajax::_responseError('Ошибка выполнения','ФИО не задано');
						if(!preg_match('/^[a-zА-Яа-яЁё]+$/u', $value)) return Ajax::_responseError('Ошибка выполнения','ФИО указано некорректно: '.$field.'=['.$value.']');
						$update_fields[$field] = mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
					break;

					case 'post_from':
					case 'birth_date':
						$value = $request->getDate($field, '');
						if(!empty($value)){
							$update_fields[$field] = date('Y-m-d', strtotime($value));
						}else{
							return Ajax::_responseError('Ошибка выполнения','Не задана дата: '.$field);
						}
					break;

					case 'email':
						$value = $request->getEmail($field, false);
						if(!empty($value)) $update_fields[$field] = $value;
					break;

					case 'order_no':
					case 'comment':
						$update_fields[$field] = $request->getStr($field, '');
					break;

					case 'phone':
						$value = $request->getStr($field, '');
						if(!empty($value)){
							if(!preg_match('/^(([0-9]{1})*[- .(]*([0-9]{3})[- .)]*[0-9]{3}[- .]*[0-9]{2}[- .]*[0-9]{2})+$/', $value)) return Ajax::_responseError('Ошибка выполнения','Телефон указан некорректно');
							$update_fields[$field] = $value;
						}else{
							return Ajax::_responseError('Ошибка выполнения','Не задан номер телефона');
						}
					break;

					case 'work_computer':
					case 'need_accesscard':
						$value = $request->getBool($field, false);
						$update_fields[$field] = (!$value ? '0':'1');
					break;

				}//switch field
			}//foreach allowed_fields
		}////Если анкета не отклонена


		$db = Database::getInstance('main');
		$db->transaction();

		if(!$admin_employers->anketUpdate($anket_id, $update_fields)){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Ошибка обновления анкеты');
		}

			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('employers.ankets.edit'),
				'acl_name'		=> 'employers.ankets.edit',
				'primary_type'	=> 'anket',
				'primary_id'	=> $anket_id,
				'description'	=> 'Изменена анкета ID:'.$anket_id,
				'value'			=> $update_fields
			));

		$create_employer = (isset($update_fields['anket_type'])&&$update_fields['anket_type'] == 3);

		//Если анкета была согласована
		if($create_employer){

			$admin_template = new Admin_Template();

			$first_name		= (isset($update_fields['first_name']) ? $update_fields['first_name'] : $anket_info['first_name']);
			$last_name		= (isset($update_fields['last_name']) ? $update_fields['last_name'] : $anket_info['last_name']);
			$middle_name	= (isset($update_fields['middle_name']) ? $update_fields['middle_name'] : $anket_info['middle_name']);
			$birth_date		= (isset($update_fields['birth_date']) ? $update_fields['birth_date'] : $anket_info['birth_date']);
			$email			= (isset($update_fields['email']) ? $update_fields['email'] : $anket_info['email']);
			$phone			= (isset($update_fields['phone']) ? $update_fields['phone'] : $anket_info['phone']);
			$company_id		= (isset($update_fields['company_id']) ? $update_fields['company_id'] : $anket_info['company_id']);
			$post_uid		= (isset($update_fields['post_uid']) ? $update_fields['post_uid'] : $anket_info['post_uid']);
			$post_from		= (isset($update_fields['post_from']) ? $update_fields['post_from'] : $anket_info['post_from']);

			#1. Создаем учетную запись сотрудника
			if(($employer = $admin_employers->employerNew(array(
				'anket_id'		=> $anket_id,
				'first_name'	=> $first_name,
				'last_name'		=> $last_name,
				'middle_name'	=> $middle_name,
				'birth_date'	=> $birth_date,
				'email'			=> $email,
				'phone'			=> $phone,
				'company_name'	=> $organization->getCompanyName($company_id),
				'post_name'		=> $organization->getPostUIDName($post_uid)
			)))===false){
				$db->rollback();
				return Ajax::_responseError('Ошибка выполнения','Ошибка создания нового сотрудника');
			}

			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('employers.ankets.moderate'),
				'acl_name'		=> 'employers.ankets.moderate',
				'primary_type'	=> 'employer',
				'primary_id'	=> $employer['employer_id'],
				'description'	=> 'Создана учетная запись сотрудника из анкеты ID:'.$anket_id,
				'value'			=> $employer
			));

			#1.a Обновляем в анкете поле идентификатора сотрудника 
			if(!$admin_employers->anketUpdate($anket_id, array('employer_id'=>$employer['employer_id']))){
				$db->rollback();
				return Ajax::_responseError('Ошибка выполнения','Ошибка обновления анкеты');
			}

			#2. Добавление должности сотруднику
			if(!$admin_employers->employerPostAdd(array(
				'employer_id'	=> $employer['employer_id'],
				'company_id'	=> $company_id,
				'post_uid'		=> $post_uid,
				'post_from'		=> date('Y-m-d', strtotime($post_from)),
				'post_to'		=> '2099-12-31'
			))){
				$db->rollback();
				return Ajax::_responseError('Ошибка выполнения','Ошибка добавления должности сотруднику');
			}

			Protocol::_add(array(
				'action_name'	=> $request_action,
				'acl_id'		=> $uaccess->getObjectIdFormName('employers.ankets.moderate'),
				'acl_name'		=> 'employers.ankets.moderate',
				'primary_type'	=> 'employer',
				'primary_id'	=> $employer['employer_id'],
				'secondary_type'=> 'orgstructure',
				'secondary_id'	=> $post_uid,
				'description'	=> 'Сотруднику добавлена должность UID:'.$post_uid.' при создании учетной записи из анкеты ID:'.$anket_id,
				'value'			=> array(
					'employer_id'	=> $employer['employer_id'],
					'company_id'	=> $company_id,
					'post_uid'		=> $post_uid,
					'post_from'		=> $post_from,
					'post_to'		=> '31.12.2099'
				)
			));


			$main_request = new Main_Request();

			#3. Оформление заявки для нового сотрудника
			if($template_new){
				$template_id = $admin_template->templateForNewEmployer($post_uid, $company_id);
				if($template_id){
					if(!$main_request->createFromTemplate(array(
						'template_id'	=> $template_id,
						'employer_id'	=> $employer['employer_id'],
						'curator_id'	=> $user->getEmployerID(),
						'company_id'	=> $company_id,
						'post_uid'		=> $post_uid
					))){
						$db->rollback();
						return Ajax::_responseError('Ошибка выполнения','Ошибка создания заявки из шаблона для нового сотрудника');
					}

					Protocol::_add(array(
						'action_name'	=> $request_action,
						'acl_id'		=> $uaccess->getObjectIdFormName('employers.ankets.moderate'),
						'acl_name'		=> 'employers.ankets.moderate',
						'primary_type'	=> 'request',
						'primary_id'	=> $main_request->request_id,
						'secondary_type'=> 'employer',
						'secondary_id'	=> $employer['employer_id'],
						'description'	=> 'Создана заявка из шаблона для нового сотрудника ID:'.$template_id.' при создании учетной записи из анкеты ID:'.$anket_id,
						'value'			=> array(
							'template_id'	=> $template_id,
							'employer_id'	=> $employer['employer_id'],
							'curator_id'	=> $user->getEmployerID(),
							'company_id'	=> $company_id,
							'post_uid'		=> $post_uid
						)
					));

				}
			}

			#4. Оформление заявки для должности отрудника
			if($template_post){
				$template_id = $admin_template->templateForPost($post_uid, $company_id);
				if($template_id){
					if(!$main_request->createFromTemplate(array(
						'template_id'	=> $template_id,
						'employer_id'	=> $employer['employer_id'],
						'curator_id'	=> $user->getEmployerID(),
						'company_id'	=> $company_id,
						'post_uid'		=> $post_uid
					))){
						$db->rollback();
						return Ajax::_responseError('Ошибка выполнения','Ошибка создания заявки из шаблона для должности');
					}
					Protocol::_add(array(
						'action_name'	=> $request_action,
						'acl_id'		=> $uaccess->getObjectIdFormName('employers.ankets.moderate'),
						'acl_name'		=> 'employers.ankets.moderate',
						'primary_type'	=> 'request',
						'primary_id'	=> $main_request->request_id,
						'secondary_type'=> 'employer',
						'secondary_id'	=> $employer['employer_id'],
						'description'	=> 'Создана заявка из шаблона для должности ID:'.$template_id.' при создании учетной записи из анкеты ID:'.$anket_id,
						'value'			=> array(
							'template_id'	=> $template_id,
							'employer_id'	=> $employer['employer_id'],
							'curator_id'	=> $user->getEmployerID(),
							'company_id'	=> $company_id,
							'post_uid'		=> $post_uid
						)
					));
				}
			}

		}//Если анкета была согласована

		//Успешно
		$db->commit();

		#Выполнено успешно
		$anket_info = $admin_employers->getAnketInfo(array('anket_id'=>$anket_id));
		return Ajax::_setData(array(
			'anket_info'		=> $anket_info,
			'employer'			=> ($create_employer ? $employer : null),
			'employers_search'	=> $admin_employers->anketRelatedEmployers($anket_info)
		));

	break; #Сохранение анкеты




	/*******************************************************************
	 * Удаление должности сотрудника
	 ******************************************************************/
	case 'employers.post.delete':

		if(!$uaccess->checkAccess('employers.post.moderate', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете редактировать должности сотрудника');
		}

		$employer_id = $request->getId('employer_id', 0);
		$company_id = $request->getId('company_id', 0);
		$post_uid = $request->getId('post_uid', 0);
		$type = $request->getEnum('type', array('delete','deletelock'), 'delete');

		if(empty($employer_id)||empty($company_id)||empty($post_uid)) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');

		$admin_employers = new Admin_Employers();
		if(!$admin_employers->employerExists($employer_id)) return Ajax::_responseError('Ошибка выполнения','Указанный сотрудник не существует');
		if(!$admin_employers->employerPostExists($employer_id, $post_uid)) return Ajax::_responseError('Ошибка выполнения','Указанный сотрудник не занимает выбранную должность');

		$db = Database::getInstance('main');
		$db->transaction();

		if(!$admin_employers->employerPostDelete($employer_id, $post_uid)){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Ошибка удаления должности сотрудника');
		}

		Protocol::_add(array(
			'action_name'	=> $request_action,
			'acl_id'		=> $uaccess->getObjectIdFormName('employers.post.moderate'),
			'acl_name'		=> 'employers.post.moderate',
			'primary_type'	=> 'employer',
			'primary_id'	=> $employer_id,
			'secondary_type'=> 'orgstructure',
			'secondary_id'	=> $post_uid,
			'description'	=> 'У сотрудника удалена должность UID:'.$post_uid,
			'value'			=> array(
				'employer_id'	=> $employer_id,
				'company_id'	=> $company_id,
				'post_uid'		=> $post_uid
			)
		));

		//Помимо удаления должности, требуется заблокировать доступ, полученный в рамках занимаемой должности
		if($type == 'deletelock'){

			$admin_matrix = new Admin_Matrix();
			$main_request = new Main_Request();

			#Список ИР сотрудника на должности
			$iresources = $admin_matrix->employerIResourcesOnPost($employer_id, $post_uid);
			if($iresources === false){
				$db->rollback();
				return Ajax::_responseError('Ошибка выполнения','Ошибка получения списка информационных ресурсов');
			}

			#Есть ИР к которым сотрудник имеет доступ в рамках занимаемой должности
			if(!empty($iresources)){

				//Создание заявки
				$request_id = $main_request->create(array(
					'request_type'	=> 3,
					'employer_id'	=> $employer_id,
					'curator_id'	=> User::_getEmployerID(),
					'post_uid'		=> $post_uid,
					'company_id'	=> $company_id,
					'phone'			=> User::_get('phone'),
					'email'			=> User::_get('email')
				));

				//Добавление в заявку информационных ресурсов
				foreach($iresources as $iresource_id){

					$iroles = $admin_matrix->employerIResourceRolesOnPost($employer_id, $post_uid, $iresource_id);
					if($iroles === false){
						$db->rollback();
						return Ajax::_responseError('Ошибка выполнения','Ошибка получения списка объектов доступа для информационного ресурса ID:'.$iresource_id);
					}
					if(empty($iroles)) continue;

					$rires_id = $main_request->setIResource(array(
						'iresource_id'	=> $iresource_id,
						'route_type'	=> 4
					));
					if(!$rires_id){
						$db->rollback();
						return Ajax::_responseError('Ошибка выполнения','Не удалось добавить в заявку на блокировку доступа информационный ресурс ID:'.$iresource_id);
					}

					//Добавление объектов доступа в заявку на блокировку
					foreach($iroles as $irole_id){

						$rrole_id = $main_request->setIRole(array(
							'iresource_id'		=> $iresource_id,
							'irole_id'			=> $irole_id,
							'ir_type'			=> 1,
							'ir_selected'		=> 1,
							'update_type'		=> 0
						));
						if(!$rrole_id){
							$db->rollback();
							return Ajax::_responseError('Ошибка выполнения','Не удалось добавить в заявку для информационного ресурса ID='.$iresource_id.' объект доступа ID:'.$irole_id);
						}

					}//Добавление объектов доступа в заявку на блокировку

				}//Добавление в заявку информационных ресурсов

				//На первый шаг согласования
				$main_request->toFirstStep();

				Ajax::_responseSuccess('Удаление должности','Должность успешно удалена, оформлена заявка ID:'.$request_id.' на блокировку доступа');

				Protocol::_add(array(
					'action_name'	=> $request_action,
					'acl_id'		=> $uaccess->getObjectIdFormName('employers.post.moderate'),
					'acl_name'		=> 'employers.post.moderate',
					'primary_type'	=> 'request',
					'primary_id'	=> $main_request->request_id,
					'secondary_type'=> 'employer',
					'secondary_id'	=> $employer_id,
					'description'	=> 'Создана заявка на блокировку доступа к ИР при удалении должности UID:'.$post_uid,
					'value'			=> array(
						'request_type'	=> 3,
						'employer_id'	=> $employer_id,
						'curator_id'	=> User::_getEmployerID(),
						'post_uid'		=> $post_uid,
						'company_id'	=> $company_id
					)
				));

			}#Есть ИР к которым сотрудник имеет доступ в рамках занимаемой должности
			else{
				Ajax::_responseSuccess('Удаление должности','Должность успешно удалена, заявка на блокировку доступа не создана, так как в рамках удаляемой должности сотруднику не были назначены какие-либо права');
			}

		}//Помимо удаления должности, требуется заблокировать доступ, полученный в рамках занимаемой должности
		else{
			Ajax::_responseSuccess('Удаление должности','Операция выполнена успешно');
		}

		#Выполнено успешно
		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			'employer_posts' => $admin_employers->getEmployersPostsEx($employer_id, true)
		));


	break; #Удаление должности сотрудника



	default:
	Ajax::_responseError('/admin/ajax/employers','Не найден обработчик для: '.Request::_get('action'));
}

?>
