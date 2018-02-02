<?php
/*==================================================================================================
Описание: Обработка AJAX запросов
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');

#Обработка AJAX запроса, в зависимости от запрошенного действия
switch(Request::_get('action')){





	/*******************************************************************
	 * Одобрение заявки на текущем шаге согласования
	 ******************************************************************/
	case 'request.approve':

		//Идентификатор запроса
		if( ($request_id = Request::_getId('request_id', 0)) == 0){
			return Ajax::_responseError('Ошибка выполнения','Номер заявки указан неверно');
		}

		//Идентификатор ИР
		if( ($iresource_id = Request::_getId('iresource_id', 0)) == 0){
			return Ajax::_responseError('Ошибка выполнения','Информационный ресурс указан неверно');
		}

		//Комментарий
		$comment = Request::_getStr('comment', '');

		//Массив измененных объектов доступа
		$ir_list = Request::_getArray('a', false);
		if(!is_array($ir_list)) $ir_list=array();

		$db = Database::getInstance('main');

		$db->transaction();

		$main_employer = new Main_Employer();
		$main_request = new Main_Request();

		$gk_request = $main_employer->getActiveRequests(array(
			'request_id'	=> array($request_id),
			'iresource_id'	=> array($iresource_id),
			'single'		=> true
		));

		if(empty($gk_request) || !$main_employer->canApprove($request_id, $iresource_id)){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','В настоящий момент Вы не можете выполнять какие-либо действия с выбранной заявкой');
		}

		$main_request->open(array(
			'request_id' =>$request_id, 
			'fullinfo' => false,
			'iresource_id'=>$iresource_id,
			'alliroles'=>false,
			'iresourceforupdate'=>true
		));

		if($main_request->toStep($iresource_id, 'approve', User::_getEmployerID()) === false){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','В настоящий момент Вы не можете выполнять какие-либо действия с выбранной заявкой');
		}


		$db->commit();

		#Выполнено успешно
		return Ajax::_setData($main_request->cache);

	break; #Список объектов доступа определенного информационного ресурса








	/*******************************************************************
	 * Сохранение заявки и начало процесса согласования
	 ******************************************************************/
	case 'request.save':

		//Идентификатор должности
		if( ($post_uid = Request::_getId('post_uid', 0)) == 0){
			return Ajax::_responseError('Ошибка выполнения','Должность указана неверно');
		}

		//Массив информационных ресурсов
		if( ($ir_list = Request::_getArray('ir', false)) === false){
			return Ajax::_responseError('Ошибка выполнения','Не задан список информационных ресурсов');
		}

		//Массив объектов доступа
		if( ($a_list = Request::_getArray('a', false)) === false){
			return Ajax::_responseError('Ошибка выполнения','Не выбрано ни одного объекта доступа.');
		}

		//Информация о должности сотрудника
		if(($post_info = employer_post_info(array('post_uid' => $post_uid))) === false){
			return Ajax::_responseError('Ошибка выполнения','Вы не занимаете выбранную должность или должность указана неверно');
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
		}//Обработка массива объектов доступа

		$request = new Main_Request();

		//Создание заявки
		$db->transaction();

			$request_id = $request->create(array(
				'employer_id'	=> null,
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
				//Добавление информационного ресурса
				$rrole_id = $request->setIRole(array(
					'iresource_id'		=> $irole[0],
					'irole_id'			=> $irole[1],
					'ir_type'			=> $irole[2],
					'ir_selected'		=> $irole[2]
				));
				if(!$rrole_id){
					$db->rollback();
					return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/request/request.save /setIRole /iresource_id='.$irole[0].'/irole_id='.$irole[1].'/false');
				}
			}//Добавление в заявку объектов доступа

			//На первый шаг согласования
			$request->toFirstStep();

		#Заявка согласована успешно
		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(true);

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






	default:
	Ajax::_responseError('/main/ajax/request','Не найден обработчик для: '.Request::_get('action'));

}


?>