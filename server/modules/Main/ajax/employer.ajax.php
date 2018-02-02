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
	 * Поиск сотрудников для замещения
	 ******************************************************************/
	case 'assistants.search':

		//Поисковый запрос
		$term = strtr(trim(Request::_getStr('term', '')), '%',' ');
		$term = preg_replace('/\s\s+/', ' ', $term);
		$term_nospaces = str_replace(' ','',$term);
		$term_is_numeric = (is_numeric($term_nospaces)) ? true : false;
		$term_len = mb_strlen($term_nospaces,'utf-8');

		if(empty($term_nospaces)){
			return Ajax::_responseError('Ошибка выполнения','Не задан поисковый запрос');
		}

		if($term_is_numeric){
			return Ajax::_responseError('Ошибка выполнения','Поисковый запрос не может быть числом');
		}

		if($term_len<2){
			return Ajax::_responseError('Ошибка выполнения','Поисковый запрос должен быть не менее 2-х символов');
		}

		$db = Database::getInstance('main');
		$db->transaction();

		$main_employer = new Main_Employer();

		if(($list = $db->select('SELECT *, DATE_FORMAT(`birth_date`,"%d.%m.%Y") as `birth_date` FROM `employers` WHERE `search_name` LIKE "'.$db->getQuotedValue($term,false).'%" AND `employer_id`!='.User::_getEmployerID().' AND `status`>0 ORDER BY `search_name` ASC'))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/employer/assistants.search /SQL/false/term=['.$term.']');
		}

		$result = array();

		for($i=0;$i<count($list);$i++){
			$result[] = array(
				'employer_id'	=> $list[$i]['employer_id'],
				'employer_name'	=> $list[$i]['search_name'],
				'birth_date'	=> $list[$i]['birth_date'],
				'phone'			=> $list[$i]['phone'],
				'email'			=> $list[$i]['email'],
				'posts'			=> $main_employer->dbEmployerPosts($list[$i]['employer_id'], true, array('post_name','company_name'))
			);
		}

		//Не предусмотрены какие-либо изменения в данной транзакии
		$db->rollback();

		#Выполнено успешно
		return Ajax::_setData($result);
		
	break; #Список должностей, занимаемых сотрудником







	/*******************************************************************
	 * Добавление ассистента
	 ******************************************************************/
	case 'assistants.add':

		//Идентификатор сотрудника
		if( ($assistant_id = Request::_getId('employer_id', 0)) == 0){
			return Ajax::_responseError('Ошибка выполнения','Не выбран сотрудник');
		}

		//Дата начала делегирования
		if( ($date_from = Request::_getDate('date_from', false)) === false){
			return Ajax::_responseError('Ошибка выполнения','Даты заданы некорректно');
		}

		//Дата окончания делегирования
		if( ($date_to = Request::_getDate('date_to', false)) === false){
			return Ajax::_responseError('Ошибка выполнения','Даты заданы некорректно');
		}

		$employer_id = User::_getEmployerID();

		if($employer_id == $assistant_id){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете делегировать сами себе что-либо');
		}

		$main_employer = new Main_Employer();
		if(!$main_employer->dbEmployerExists($assistant_id)){
			return Ajax::_responseError('Ошибка выполнения','Выбранный сотрудник не существует или заблокирован, выберите другого сотрудника');
		}

		$db = Database::getInstance('main');
		$db->transaction();


		//Удаляем предыдущую запись об ассистенте
		$db->prepare('DELETE FROM `assistants` WHERE `employer_id`=? AND `assistant_id`=?');
		$db->bind($employer_id); //ID сотрудника
		$db->bind($assistant_id); //ID выбранного ассистента
		if($db->insert() === false){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/employer/assistants.add /SQL:DELETE/false');
		}

		//Добавляем ассистента
		$db->prepare('INSERT INTO `assistants` (`employer_id`,`assistant_id`,`from_date`,`to_date`,`timestamp`) VALUES (?,?,?,?,?)');
		$db->bind($employer_id); //ID сотрудника
		$db->bind($assistant_id); //ID выбранного ассистента
		$db->bind(date('Y-m-d', strtotime($date_from)));
		$db->bind(date('Y-m-d', strtotime($date_to)));
		$db->bind(date('Y-m-d H:i:s'));

		if($db->insert() === false){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/employer/assistants.add /SQL:INSERT/false');
		}

		//Список ассисентов
		$assistants	= $main_employer->dbEmployerAssistants(null, true, false, null, false); 
		foreach($assistants as $id=>$record) $assistants[$id]['posts'] = $main_employer->dbEmployerPosts($record['employer_id'], true, array('post_name','company_name'));

		#Выполнено успешно
		$db->commit();
		return Ajax::_setData($assistants);
		
	break; #Добавление ассистента






	/*******************************************************************
	 * Удаление ассистента
	 ******************************************************************/
	case 'assistants.delete':

		//Идентификатор сотрудника
		if( ($assistant_id = Request::_getId('employer_id', 0)) == 0){
			return Ajax::_responseError('Ошибка выполнения','Не выбран сотрудник');
		}

		$employer_id = User::_getEmployerID();

		if($employer_id == $assistant_id){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете отобрать сами у себя что-либо');
		}

		$db = Database::getInstance('main');
		$db->transaction();


		//Удаляем предыдущую запись об ассистенте
		$db->prepare('DELETE FROM `assistants` WHERE `employer_id`=? AND `assistant_id`=?');
		$db->bind($employer_id); //ID сотрудника
		$db->bind($assistant_id); //ID выбранного ассистента
		if($db->insert() === false){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/employer/assistants.delete /SQL:DELETE/false');
		}

		//Список ассисентов
		$main_employer = new Main_Employer();
		$assistants	= $main_employer->dbEmployerAssistants(null, true, false, null, false); 
		foreach($assistants as $id=>$record) $assistants[$id]['posts'] = $main_employer->dbEmployerPosts($record['employer_id'], true, array('post_name','company_name'));

		#Выполнено успешно
		$db->commit();
		return Ajax::_setData($assistants);

	break; #Удаление ассистента







	/*******************************************************************
	 * Получение списка заявок доступных для проспмотра
	 ******************************************************************/
	case 'watcher.requestlist':

		$employer_id = User::_getEmployerID();
		$type = $request->getEnum('type',array('all','2','3'),'all');
		$period = $request->getEnum('period',array('all','1','7','30','90','365'),'7');
		$watched = $request->getEnum('watched',array('all','0','1'),'all');
		$employer = $request->getStr('employer',100);
		$db = Database::getInstance('main');

		$watcher_sub_filter = array();
		$watcher_filter = array();
		$request_filter = array();
		$employer_filter = array();

		if($period != 'all') $request_filter[]= array('timestamp',date('Y-m-d H:i:s', (time()-$period*86400)),null,'>');
		if($type != 'all') $request_filter[]= array('request_type',$type,null,'=');
		if($watched!= 'all') $watcher_filter['is_watched'] = $watched;
		if(!empty($employer)) $employer_filter[] = array('search_name',$employer,null,'LIKE%');

		if(Request::_getBool('is_owner', false)) $watcher_sub_filter['is_owner'] = 1;
		if(Request::_getBool('is_curator', false)) $watcher_sub_filter['is_curator'] = 1;
		if(Request::_getBool('is_gatekeeper', false)) $watcher_sub_filter['is_gatekeeper'] = 1;
		if(Request::_getBool('is_performer', false)) $watcher_sub_filter['is_performer'] = 1;
		if(Request::_getBool('is_watcher', false)) $watcher_sub_filter['is_watcher'] = 1;

		if(!empty($watcher_sub_filter)) $watcher_filter[] = array(null, $db->buildSqlConditions($watcher_sub_filter,'RW','OR'),BIND_SQL);

		$main_employer = new Main_Employer();
		Ajax::_setData(array(
			'requests' => $main_employer->dbEmployerWatchedRequests(null, $watcher_filter, $request_filter, $employer_filter),
		));

		//print_r(Database::getInstance('main')->parseTemplate());

		return true;

	break; #Получение списка заявок доступных для проспмотра






	/*******************************************************************
	 * Изменение контактной информации сотрудника
	 ******************************************************************/
	case 'profile.info.change':

		$employer_id	= User::_getEmployerID();
		$email			= Request::_getEmail('email', '');
		$phone			= Request::_getStr('phone', '');

		if(empty($email)) return Ajax::_responseError('Ошибка выполнения','Адрес электронной почты указан некорректно');
		if(empty($phone)) return Ajax::_responseError('Ошибка выполнения','Не указан контактный телефон');

		$main_employer = new Main_Employer();
		if($main_employer->changeInfo(null,array(
			'email' => $email,
			'phone' => $phone
		)) === false){
			return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/employer/profile.info.change /changeInfo/false');
		}

		Ajax::_responseSuccess('Успешно','Контактная информация успешно обновлена');
		Ajax::_setData(true);
		return true;

	break; #Изменение контактной информации сотрудника







	/*******************************************************************
	 * Изменение уведомлений сотрудника
	 ******************************************************************/
	case 'profile.notice.change':

		$employer_id	= User::_getEmployerID();
		$notice_me_requests			= (Request::_getBool('notice_me_requests', false) ? '1':'0');
		$notice_curator_requests	= (Request::_getBool('notice_curator_requests', false) ? '1':'0');
		$notice_gkemail_1			= (Request::_getBool('notice_gkemail_1', false) ? '1':'0');
		$notice_gkemail_2			= (Request::_getBool('notice_gkemail_2', false) ? '1':'0');
		$notice_gkemail_3			= (Request::_getBool('notice_gkemail_3', false) ? '1':'0');
		$notice_gkemail_4			= (Request::_getBool('notice_gkemail_4', false) ? '1':'0');

		$main_employer = new Main_Employer();
		if($main_employer->changeInfo(null,array(
			'notice_me_requests' => $notice_me_requests,
			'notice_curator_requests' => $notice_curator_requests,
			'notice_gkemail_1' => $notice_gkemail_1,
			'notice_gkemail_2' => $notice_gkemail_2,
			'notice_gkemail_3' => $notice_gkemail_3,
			'notice_gkemail_4' => $notice_gkemail_4
		)) === false){
			return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/employer/profile.info.change /changeInfo/false');
		}

		Ajax::_responseSuccess('Успешно','Типы уведомлений обновлены');
		Ajax::_setData(true);
		return true;

	break; #Изменение уведомлений сотрудника





	/*******************************************************************
	 * Изменение пароля сотрудника
	 ******************************************************************/
	case 'profile.password.change':

		$employer_id		= User::_getEmployerID();
		$password_prev		= trim(Request::_getStr('pwdprev', ''));
		$password_new		= trim(Request::_getStr('pwdnew', ''));
		$password_confirm	= trim(Request::_getStr('pwdconfirm', ''));

		if(empty($password_prev)) return Ajax::_responseError('Ошибка выполнения','Не задан старый пароль');
		if(strlen($password_new) < 8) return Ajax::_responseError('Ошибка выполнения','Длина нового пароля должна быть не менее 8 символов');
		if(strcmp($password_new, $password_confirm)!=0) return Ajax::_responseError('Ошибка выполнения', 'Новый пароль и подтверждение не совпадают');

		$main_employer = new Main_Employer();
		$db_password = $main_employer->dbEmployerInfo(null, true, array('password'));
		if(strcmp($password_prev, $db_password['password'])!==0 && strcmp(sha1($password_prev), $db_password['password'])!==0){
			return Ajax::_responseError('Ошибка выполнения','Старый пароль задан некорректно');
		}

		if($main_employer->changeInfo(null,array(
			'password' => $password_new
		)) === false){
			return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/employer/profile.password.change /changeInfo/false');
		}

		Ajax::_responseSuccess('Успешно','Пароль успешно изменен');
		Ajax::_setData(true);
		return true;

	break; #Изменение пароля сотрудника








	/*******************************************************************
	 * Получение списка должностей в организации для куратора
	 ******************************************************************/
	case 'curator.company.posts':

		$employer_id		= User::_getEmployerID();
		$company_id			= Request::_getId('company_id', 0);
		if(empty($company_id)) return Ajax::_responseError('Ошибка выполнения','Идентификатор организации задан неверно');

		$main_employer = new Main_Employer();
		$allowed_companies = $main_employer->canAddEmployerCompanies(null);
		if(!in_array($company_id, $allowed_companies)) return Ajax::_responseError('Ошибка выполнения','Вы не можете просматривать должности указанной организации');

		$db = Database::getInstance('main');
		$db->prepare('SELECT * FROM
			(SELECT 
				CP.`post_uid` as `post_uid`,
				IF(CP.`boss_id` = 0, POST.`full_name` , CONCAT_WS(" / ", POST.`full_name`,PBOSS.`full_name`)) as `post_name`
			FROM `company_posts` as CP
				INNER JOIN `posts` as POST ON POST.`post_id` = CP.`post_id`
				LEFT JOIN `posts` as PBOSS ON PBOSS.`post_id` = CP.`boss_id`
			WHERE CP.`company_id`=?) as RST
			ORDER BY RST.`post_name` ASC
		');
		$db->bind($company_id);

		if( ($posts = $db->select()) === false){
			return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/employer/curator.company.posts /SQL:SELECT/false');
		}

		Ajax::_setData(array(
			'company_id'	=> $company_id,
			'posts'			=> $posts
		));
		return true;

	break; #Изменение пароля сотрудника





	/*******************************************************************
	 * Добавление анкеты сотрудника
	 ******************************************************************/
	case 'curator.add.employer':

		$employer_id		= User::_getEmployerID();
		$company_id			= Request::_getId('company_id', 0);
		$post_uid			= Request::_getId('post_uid', '');
		$order_no			= Request::_getStr('order_no', '');
		$email				= Request::_getEmail('email', '');
		$phone				= Request::_getStr('phone', '');
		$post_from			= Request::_getDate('post_from', false);
		$birth_date			= Request::_getDate('birth_date', false);
		$work_computer		= (!Request::_getBool('work_computer', false) ? '0':'1');
		$need_accesscard	= (!Request::_getBool('need_accesscard', false) ? '0':'1');
		$comment			= Request::_getStr('comment', '');
		$first_name			= mb_convert_case(Request::_getStr('first_name', ''), MB_CASE_TITLE, 'UTF-8');
		$last_name			= mb_convert_case(Request::_getStr('last_name', ''), MB_CASE_TITLE, 'UTF-8');
		$middle_name		= mb_convert_case(Request::_getStr('middle_name', ''), MB_CASE_TITLE, 'UTF-8');
		$employer_name		= $last_name.' '.$first_name.' '.$middle_name;

		if(empty($first_name)||empty($last_name)||empty($middle_name)||empty($phone)||empty($company_id)||empty($post_uid)||empty($post_from)||empty($birth_date)){
			return Ajax::_responseError('Ошибка выполнения','Анкета заполнена не полностью');
		}

		$db = Database::getInstance('main');

		//Проверка сущестсвования организации
		$db->prepare('SELECT * FROM `companies` WHERE `company_id`=? LIMIT 1');
		$db->bind($company_id);
		if( ($company_info = $db->selectRecord()) === false){
			return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/employer/curator.add.employer /SQL:SELECT:COMPANY/false');
		}
		if(empty($company_info)) return Ajax::_responseError('Ошибка выполнения', 'Указанная организация не существует');
		if(!empty($company_info['is_lock'])) return Ajax::_responseError('Ошибка выполнения', 'Указанная организация заблокирована администратором системы');

		$main_employer = new Main_Employer();
		$allowed_companies = $main_employer->canAddEmployerCompanies(null);
		if(!in_array($company_id, $allowed_companies)) return Ajax::_responseError('Ошибка выполнения','Вы не можете добавлять сотрудников в указанной организации');

		//Проверка существования должности
		$db->prepare('
			SELECT
				CP.`post_uid` as `post_uid`,
				POST.`full_name` as `post_name`
			FROM `company_posts` as CP
				INNER JOIN `posts` as POST ON POST.`post_id` = CP.`post_id`
			WHERE CP.`company_id`=? AND CP.`post_uid`=?
			LIMIT 1
		');
		$db->bind($company_id);
		$db->bind($post_uid);
		if( ($post_info = $db->selectRecord()) === false){
			return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/employer/curator.add.employer /SQL:SELECT:POST/false');
		}
		if(empty($post_info)) return Ajax::_responseError('Ошибка выполнения', 'Указанная должность не существует в выбранной организации');

		//Проверка существования сотрудника на должности
		$db->prepare('
			SELECT count(*) FROM `employers` as EMP
			INNER JOIN `employer_posts` as EP ON EP.`employer_id`=EMP.`employer_id` AND EP.`post_uid`=? AND EP.`post_to`>?
			WHERE EMP.`birth_date`=? AND EMP.`last_name` LIKE ? AND (EMP.`first_name` LIKE ? OR EMP.`middle_name` LIKE ?)
			LIMIT 1
		');
		$db->bind($post_uid);
		$db->bind(date('Y-m-d', strtotime($post_from)));
		$db->bind(date('Y-m-d', strtotime($birth_date)));
		$db->bind($last_name);
		$db->bind($first_name);
		$db->bind($middle_name);

		if( ($found = $db->result()) === false){
			return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/employer/curator.add.employer /SQL:SELECT:EMP/false !!!');
		}
		if($found) return Ajax::_responseError('Ошибка выполнения', $employer_name.' уже занимает должность '.$post_info['post_name'].' в '.$company_info['full_name']);


		//Проверка существования анкеты сотрудника
		$db->prepare('
			SELECT 
				EA.`curator_id` as `curator_id`,
				EMP.`search_name` as `curator_name`,
				DATE_FORMAT(EA.`create_time`,"%d.%m.%Y %H:%i:%s") as `create_time`
			FROM `employer_ankets` as EA
			INNER JOIN `employers` as EMP ON EMP.`employer_id`=EA.`curator_id`
			WHERE EA.`birth_date`=? AND EA.`last_name`=? AND (EA.`first_name`=? OR EA.`middle_name`=?) AND EA.`company_id`=? AND EA.`post_uid`=?
			LIMIT 1
		');
		$db->bind(date('Y-m-d', strtotime($birth_date)));
		$db->bind($last_name);
		$db->bind($first_name);
		$db->bind($middle_name);
		$db->bind($company_id);
		$db->bind($post_uid);

		if( ($found = $db->selectRecord()) === false){
			return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/employer/curator.add.employer /SQL:SELECT:ANKET/false');
		}
		if(!empty($found)){
			return Ajax::_responseError(
				'Ошибка выполнения', 
				'Для сотрудника '.$employer_name.' уже была оформлена анкета на должность '.$post_info['post_name'].' в '.$company_info['full_name'].'. '.
				'Анкету оформил: '.$found['curator_name'].' ('.$found['create_time'].')'
			);
		}


		//Добавление анкеты
		$db->prepare('
			INSERT INTO `employer_ankets` (
				`anket_type`,`approved_time`,`employer_id`,`curator_id`,
				`company_id`,`post_uid`,`order_no`,`post_from`,`first_name`,
				`last_name`,`middle_name`,`birth_date`,`phone`,`email`,
				`work_computer`,`need_accesscard`,`comment`,`create_time`
			) VALUES (?,?,?,?,?, ?,?,?,?,?, ?,?,?,?,?, ?,?,?)');
		$db->bind(1);							//anket_type
		$db->bind('0000-00-00 00:00:00');		//approved_time
		$db->bind(0);							//employer_id
		$db->bind($employer_id);				//curator_id
		$db->bind($company_id);								//company_id

		$db->bind($post_uid);								//post_uid
		$db->bind($order_no);								//order_no
		$db->bind(date('Y-m-d', strtotime($post_from)));	//post_from
		$db->bind($first_name);								//first_name
		$db->bind($last_name);								//last_name

		$db->bind($middle_name);							//middle_name
		$db->bind(date('Y-m-d', strtotime($birth_date)));	//birth_date
		$db->bind($phone);									//phone
		$db->bind($email);									//email
		$db->bind($work_computer);				//work_computer

		$db->bind($need_accesscard);			//need_accesscard
		$db->bind($comment);					//comment
		$db->bind(date('Y-m-d H:i:s'));			//create_time

		if( $db->insert() === false){
			return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/employer/curator.add.employer /SQL:INSERT:ANKET/false');
		}

		Ajax::_setData(true);
		return true;

	break; #Добавление анкеты сотрудника







	/*******************************************************************
	 * Поиск сотрудника для формирования заявки от его имени
	 ******************************************************************/
	case 'curator.employer.search':

		$employer_id		= User::_getEmployerID();
		$company_id			= Request::_getId('company_id', 0);
		$search_type		= Request::_getEnum('search_type',array('employer','post'),'employer');
		$term_type			= Request::_getEnum('term_type',array('begin','contain'),'begin');

		//Поисковый запрос
		$term = strtr(trim(Request::_getStr('employer_name', '')), '%',' ');
		$term = preg_replace('/\s\s+/', ' ', $term);
		$term_nospaces = str_replace(' ','',$term);
		$term_is_numeric = (is_numeric($term_nospaces)) ? true : false;
		$term_len = mb_strlen($term_nospaces,'utf-8');

		if(empty($term_nospaces)) return Ajax::_responseError('Ошибка выполнения','Не задан поисковый запрос');
		if($term_is_numeric) return Ajax::_responseError('Ошибка выполнения','Поисковый запрос не может быть числом');
		if($term_len<2) return Ajax::_responseError('Ошибка выполнения','Поисковый запрос должен быть не менее 2-х символов');
		if(empty($company_id)) return Ajax::_responseError('Ошибка выполнения','Не указана организация');


		$db = Database::getInstance('main');
		$main_employer = new Main_Employer();
		$allowed_companies = $main_employer->canCuratorCompanies(null);
		if(!in_array($company_id, $allowed_companies)) return Ajax::_responseError('Ошибка выполнения','Вы не можете оформлять заявки для сотрудников выбранной организации');

		//Просерка сущестсвования организации
		$db->prepare('SELECT * FROM `companies` WHERE `company_id`=? LIMIT 1');
		$db->bind($company_id);
		if( ($company_info = $db->selectRecord()) === false){
			return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/employer/curator.add.employer /SQL:SELECT:COMPANY/false');
		}
		if(empty($company_info)) return Ajax::_responseError('Ошибка выполнения', 'Указанная организация не существует');
		if(!empty($company_info['is_lock'])) return Ajax::_responseError('Ошибка выполнения', 'Указанная организация заблокирована администратором системы');


		if($search_type == 'employer'){
			if(($results = $main_employer->dbEmployerPostsDetails($term, $company_id, ($term_type=='contain'?true:false))) === false){
				Ajax::_responseError('Ошибка выполнения','Ошибка во время получения информации о сотрудниках и должностях'); break;
			}
		}else{
			if(($results = $main_employer->dbSearchPostEmployers($term, $company_id, ($term_type=='contain'?true:false))) === false){
				Ajax::_responseError('Ошибка выполнения','Ошибка во время получения информации о сотрудниках и должностях'); break;
			}
		}

		Ajax::_setData($results);
		return true;

	break; #Поиск сотрудника для формирования заявки от его имени





	default:
	Ajax::_responseError('/main/ajax/employer','Не найден обработчик для: '.Request::_get('action'));


}




?>