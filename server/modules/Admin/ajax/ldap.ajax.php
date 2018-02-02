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
	 * Список пользователей AD, отсутствующих в локальной базе
	 ******************************************************************/
	case 'ldap.users':

		$admin_ldap = new Admin_LDAPUsers();
		return Ajax::_setData(array(
			'users' => $admin_ldap->undefinedUsers()
		));

	break; #Список пользователей AD, отсутствующих в локальной базе




	/*******************************************************************
	 * Список пользователей локальной базы, похожих на выбранного пользователя из AD
	 ******************************************************************/
	case 'ldap.related':
		$email		= trim(Request::_getEmail('email', ''));
		$last_name	= mb_convert_case(trim(Request::_getStr('last_name', '')), MB_CASE_TITLE, 'UTF-8');

		$admin_ldap = new Admin_LDAPUsers();
		return Ajax::_setData(array(
			'employers_search'	=> $admin_ldap->relatedEmployers(array(
				'last_name'	=> $last_name,
				'email'		=> $email
			))
		));

	break; #Список пользователей локальной базы, похожих на выбранного пользователя из AD




	/*******************************************************************
	 * Импорт сотрудника
	 ******************************************************************/
	case 'ldap.import':

		if(!$uaccess->checkAccess('ldap.import', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете импортировать пользователей из ActiveDirectory');
		}

		$organization = new Admin_Organization();
		$admin_employers = new Admin_Employers();

		$template_post = $request->getBool('template_post', false);

		$allowed_fields = array('username','company_id','post_uid','first_name','last_name','middle_name','birth_date','phone','email');
		$update_fields=array();

		foreach($allowed_fields as $field){
			switch($field){

				case 'username':
					$value = $request->getStr($field, '');
					if(empty($value)) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');
					if($admin_employers->employerExists($value)) return Ajax::_responseError('Ошибка выполнения','Сотрудник с указанным именем пользователя уже существует');
					$update_fields[$field] = $value;
				break;

				case 'company_id':
					$value = $request->getId($field, 0);
					if(!empty($value)){
						if(!$organization->companyExists($value)) return Ajax::_responseError('Ошибка выполнения','Указанная организация не существует');
						$update_fields[$field] = $value;
					}
				break;

				case 'post_uid':
					$value = $request->getStr($field, '');
					if(!empty($value)){
						if(!is_numeric($value)) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');
						if(!$organization->postUIDExists($value)) return Ajax::_responseError('Ошибка выполнения','Указанная должность не существует');
						$update_fields[$field] = $value;
					}
				break;

				case 'first_name':
				case 'last_name':
				case 'middle_name':
					$value = $request->getStr($field, '');
					if(empty($value)) return Ajax::_responseError('Ошибка выполнения','ФИО не задано');
					if(!preg_match('/^[a-zА-Яа-яЁё]+$/u', $value)) return Ajax::_responseError('Ошибка выполнения','ФИО указано некорректно: '.$field.'=['.$value.']');
					$update_fields[$field] = mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
				break;

				case 'birth_date':
					$value = $request->getDate($field, '');
					if(!empty($value)){
						$update_fields[$field] = date('Y-m-d', strtotime($value));
					}else{
						$update_fields[$field] = date('Y-m-d');
					}
				break;

				case 'email':
					$value = $request->getEmail($field, false);
					if(!empty($value)) $update_fields[$field] = $value;
				break;

				case 'phone':
					$value = $request->getStr($field, '');
					if(!empty($value)){
						if(!preg_match('/^(([0-9]{1})*[- .(]*([0-9]{3})[- .)]*[0-9]{3}[- .]*[0-9]{2}[- .]*[0-9]{2})+$/', $value)) return Ajax::_responseError('Ошибка выполнения','Телефон указан некорректно');
						$update_fields[$field] = $value;
					}
				break;

			}//switch field
		}//foreach allowed_fields



		$db = Database::getInstance('main');
		$db->transaction();

		$admin_template = new Admin_Template();

		$username		= (isset($update_fields['username']) ? $update_fields['username'] : '');
		$first_name		= (isset($update_fields['first_name']) ? $update_fields['first_name'] : '');
		$last_name		= (isset($update_fields['last_name']) ? $update_fields['last_name'] : '');
		$middle_name	= (isset($update_fields['middle_name']) ? $update_fields['middle_name'] : '');
		$birth_date		= (isset($update_fields['birth_date']) ? $update_fields['birth_date'] : '');
		$email			= (isset($update_fields['email']) ? $update_fields['email'] : '');
		$phone			= (isset($update_fields['phone']) ? $update_fields['phone'] : '');
		$company_id		= (isset($update_fields['company_id']) ? $update_fields['company_id'] : 0);
		$post_uid		= (isset($update_fields['post_uid']) ? $update_fields['post_uid'] : 0);
		$post_from		= (isset($update_fields['post_from']) ? $update_fields['post_from'] : date('Y-m-d'));

		#1. Создаем учетную запись сотрудника
		if(($employer = $admin_employers->employerNew(array(
			'anket_id'		=> 0,
			'username'		=> $username,
			'first_name'	=> $first_name,
			'last_name'		=> $last_name,
			'middle_name'	=> $middle_name,
			'birth_date'	=> $birth_date,
			'email'			=> $email,
			'phone'			=> $phone,
			'company_name'	=> $organization->getCompanyName($company_id),
			'post_name'		=> $organization->getPostUIDName($post_uid)
		),false))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Ошибка создания нового сотрудника');
		}

		#2. Добавление должности сотруднику
		if(!empty($company_id)&&!empty($post_uid)){
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
		}

		#3. Оформление заявки для должности отрудника
		if($template_post && !empty($company_id)&&!empty($post_uid)){
			$template_id = $admin_template->templateForPost($post_uid, $company_id);
			if($template_id){
				$main_request = new Main_Request();
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
			}
		}


		//Успешно
		$db->commit();

		$admin_ldap = new Admin_LDAPUsers();

		#Выполнено успешно
		return Ajax::_setData(array(
			'users'				=> $admin_ldap->undefinedUsers(),
			'employer'			=> $employer
		));

	break; #Импорт сотрудника




	default:
	Ajax::_responseError('/admin/ajax/ldap','Не найден обработчик для: '.Request::_get('action'));
}
?>