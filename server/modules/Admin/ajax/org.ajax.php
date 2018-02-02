<?php
/*==================================================================================================
Описание: Обработка AJAX запросов
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');


#Обработка AJAX запроса, в зависимости от запрошенного действия
switch(Request::_get('action')){




	/*******************************************************************
	 * Добавление организации
	 ******************************************************************/
	case 'org.company.new':

		$full_name = trim($request->getStr('full_name', ''));
		$short_name = trim($request->getStr('short_name', ''));
		$is_lock = $request->getBool('is_lock', false);

		if(!$uaccess->checkAccess('org.companies.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять список организаций');
		}

		if(empty($full_name)||empty($short_name)) return Ajax::_responseError('Ошибка выполнения','Не задано имя организации');
		$organization = new Admin_Organization();
		if($organization->companyExists($full_name)) return Ajax::_responseError('Ошибка выполнения','Уже существует организация с указанным наименованием');

		if(($company_id = $organization->companyNew(array(
			'full_name'		=> $full_name,
			'short_name'	=> $short_name,
			'is_lock' 		=> (!$is_lock ? 0 : 1)
		)))===false){
			return Ajax::_responseError('Ошибка выполнения','Ошибка добавления организации');
		}


		#Выполнено успешно
		return Ajax::_setData(array(
			'sobject' => $company_id,
			'companies' => $organization->getCompaniesList()
		));

	break; #Добавление организации




	/*******************************************************************
	 * Редактирование организации
	 ******************************************************************/
	case 'org.company.edit':

		$company_id = $request->getId('company_id', 0);
		$full_name = trim($request->getStr('full_name', ''));
		$short_name = trim($request->getStr('short_name', ''));
		$is_lock = $request->getBool('is_lock', false);

		if(!$uaccess->checkAccess('org.companies.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять список организаций');
		}

		$organization = new Admin_Organization();
		if(!$organization->companyExists($company_id)) return Ajax::_responseError('Ошибка выполнения','Редактируемой организации не существует');
		if(empty($full_name)||empty($short_name)) return Ajax::_responseError('Ошибка выполнения','Не задано имя организации');

		if(!$organization->companyUpdate($company_id, array(
			'full_name'		=> $full_name,
			'short_name'	=> $short_name,
			'is_lock' 		=> (!$is_lock ? 0 : 1)
		))){
			return Ajax::_responseError('Ошибка выполнения','Ошибка обновления информации об организации');
		}


		#Выполнено успешно
		return Ajax::_setData(array(
			'sobject' => $company_id,
			'companies' => $organization->getCompaniesList()
		));

	break; #Редактирование организации





	/*******************************************************************
	 * Удаление организации
	 ******************************************************************/
	case 'org.company.delete':

		$company_id = $request->getId('company_id', 0);

		if(!$uaccess->checkAccess('org.companies.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять список организаций');
		}

		$organization = new Admin_Organization();
		if(!$organization->companyExists($company_id)) return Ajax::_responseError('Ошибка выполнения','Редактируемой организации не существует');

		//Проверка допустимости удаления организации, для обеспечения целостности
		if(!$organization->companyCanDelete($company_id)){
			return Ajax::_responseError('Ошибка выполнения','Нельзя удалить организацию, поскольку она используется: в организационной структуре, информационных ресурсах, шаблонах доступа или заявках сотрудников');
		}

		if(!$organization->companyDelete($company_id, false)){
			return Ajax::_responseError('Ошибка выполнения','Ошибка удаления организации');
		}

		#Выполнено успешно
		return Ajax::_setData(array(
			'companies' => $organization->getCompaniesList()
		));

	break; #Удаление организации





	/*******************************************************************
	 * Добавление должности
	 ******************************************************************/
	case 'org.post.new':

		$full_name = trim($request->getStr('full_name', ''));
		$short_name = trim($request->getStr('short_name', ''));

		if(!$uaccess->checkAccess('org.posts.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять список должностей');
		}

		if(empty($full_name)||empty($short_name)) return Ajax::_responseError('Ошибка выполнения','Не задано имя должности');
		$organization = new Admin_Organization();
		if($organization->postExists($full_name)) return Ajax::_responseError('Ошибка выполнения','Уже существует должность с указанным наименованием');

		if(($post_id = $organization->postNew(array(
			'full_name'		=> $full_name,
			'short_name'	=> $short_name
		)))===false){
			return Ajax::_responseError('Ошибка выполнения','Ошибка добавления должности');
		}


		#Выполнено успешно
		return Ajax::_setData(array(
			'sobject' => $post_id,
			'posts' => $organization->getPostsList()
		));

	break; #Добавление должности




	/*******************************************************************
	 * Редактирование должности
	 ******************************************************************/
	case 'org.post.edit':

		$post_id = $request->getId('post_id', 0);
		$full_name = trim($request->getStr('full_name', ''));
		$short_name = trim($request->getStr('short_name', ''));

		if(!$uaccess->checkAccess('org.posts.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять список должностей');
		}

		$organization = new Admin_Organization();
		if(!$organization->postExists($post_id)) return Ajax::_responseError('Ошибка выполнения','Редактируемой должности не существует');
		if(empty($full_name)||empty($short_name)) return Ajax::_responseError('Ошибка выполнения','Не задано имя должности');

		if(!$organization->postUpdate($post_id, array(
			'full_name'		=> $full_name,
			'short_name'	=> $short_name
		))){
			return Ajax::_responseError('Ошибка выполнения','Ошибка обновления информации о должности');
		}


		#Выполнено успешно
		return Ajax::_setData(array(
			'sobject' => $post_id,
			'posts' => $organization->getPostsList()
		));

	break; #Редактирование должности





	/*******************************************************************
	 * Удаление должности
	 ******************************************************************/
	case 'org.post.delete':

		$post_id = $request->getId('post_id', 0);

		if(!$uaccess->checkAccess('org.posts.edit', 0)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять список должностей');
		}

		$organization = new Admin_Organization();
		if(!$organization->postExists($post_id)) return Ajax::_responseError('Ошибка выполнения','Редактируемой должности не существует');

		//Проверка допустимости удаления должности, для обеспечения целостности
		if(!$organization->postCanDelete($post_id)){
			return Ajax::_responseError('Ошибка выполнения','Нельзя удалить должность, поскольку она используется в организационной структуре предприятий');
		}

		if(!$organization->postDelete($post_id, false)){
			return Ajax::_responseError('Ошибка выполнения','Ошибка удаления должности');
		}

		#Выполнено успешно
		return Ajax::_setData(array(
			'posts' => $organization->getPostsList()
		));

	break; #Удаление должности







	/*******************************************************************
	 * Загрузка органзиционной диаграммы
	 ******************************************************************/
	case 'org.structure.load':

		$company_id = $request->getId('company_id', 0);

		if(empty($company_id) || !$uaccess->checkAccess('org.structure.load', $company_id)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете работать с организационной структурой выбранной компании');
		}

		$organization = new Admin_Organization();

		#Выполнено успешно
		return Ajax::_setData(array(
			'company_id'=> $company_id,
			'org_data' => $organization->getOrgChart($company_id)
		));

	break; #Загрузка органзиционной диаграммы






	/*******************************************************************
	 * Сохранение органзиционной диаграммы
	 ******************************************************************/
	case 'org.structure.save':

		$company_id = $request->getId('company_id', 0);
		$posts = $request->getArray('p', array());
		$parents = $request->getArray('b', array());
		if(!is_array($posts))$posts = array();
		if(!is_array($parents))$parents = array();

		if(empty($company_id) || !$uaccess->checkAccess('org.structure.save', $company_id)){
			return Ajax::_responseError('Ошибка выполнения','Вы не можете изменять организационную структуру выбранной компании');
		}

		$posts = array_map('intval', $posts);
		$parents = array_map('intval', $parents);

		if(count($posts) != count($parents)){
			return Ajax::_responseError('Ошибка выполнения','Некорректно заданы входные данные');
		}

		$organization = new Admin_Organization();

		$db = Database::getInstance('main');
		$db->transaction();

		$postlist = $db->selectByKey('post_id','SELECT `post_id` FROM `posts`');
		$structure = array();
		$post_uids = array();

		//Обработка должностей
		for($i=0;$i<count($posts);$i++){
			if($posts[$i] == 0 || empty($postlist[$posts[$i]]) || ($parents[$i]!=0 && empty($postlist[$parents[$i]]))){
				$db->rollback();
				return Ajax::_responseError('Ошибка выполнения','Некорректно заданы входные данные');
			}
			$post_uid = $organization->getPostUID($company_id, $posts[$i]);
			$post_uids[] = $post_uid;
			$structure[] = array(
				'post_id'	=> $posts[$i],
				'boss_id'	=> $parents[$i],
				'post_uid'	=> $post_uid,
				'boss_uid'	=> ($parents[$i] > 0 ? $organization->getPostUID($company_id, $parents[$i]) : 0)
			);
		}


		//Вычисляем удаляемые должности из организационной структуры
		$delete_posts = array_diff($organization->getPostUIDs($company_id), $post_uids);
		//Проверяем допустимость удаления должностей
		if(!empty($delete_posts)){
			if(!$organization->postUIDsCanDelete($company_id, $delete_posts)){
				return Ajax::_responseError('Ошибка выполнения','Нельзя сохранить организационную структуру, поскольку удаляются должности, назначенные сотрудникам и/или используемые в заявках.');
			}
		}

		//Удаление старых объектов
		$db->prepare('DELETE FROM `company_posts` WHERE `company_id`=?');
		$db->bind($company_id);
		if($db->delete() === false){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Ошибка во время удаления текущей организационной структуры');
		}

		//Вставка в базу данных
		foreach($structure as $post){
			$db->prepare('INSERT INTO `company_posts` (`company_id`,`post_uid`,`boss_uid`,`post_id`,`boss_id`) VALUES (?,?,?,?,?)');
			$db->bind($company_id);
			$db->bind($post['post_uid']);
			$db->bind($post['boss_uid']);
			$db->bind($post['post_id']);
			$db->bind($post['boss_id']);
			if($db->simple() === false){
				$db->rollback();
				return Ajax::_responseError('Ошибка выполнения','Ошибка во время создания новой организационной структуры');
			}
		}

		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			/*
			'company_id'=> $company_id,
			'org_data'	=> $organization->getOrgChart($company_id)
			*/
		));

	break; #Загрузка органзиционной диаграммы





	/*******************************************************************
	 * Список должностей организации
	 ******************************************************************/
	case 'org.company.posts':

		$company_id = $request->getId('company_id', 0);
		if(empty($company_id)){
			return Ajax::_responseError('Ошибка выполнения','Не выбрана организация');
		}

		$organization = new Admin_Organization();
		$posts = $organization->getCompanyPosts($company_id);

		#Выполнено успешно
		return Ajax::_setData(array(
			'company_id'=> $company_id,
			'posts' => $posts
		));

	break; #Список должностей организации






	/*******************************************************************
	 * Получение информации по выбранной должности
	 ******************************************************************/
	case 'org.post.info':

		$company_id = $request->getId('company_id', 0);
		if(empty($company_id)){
			return Ajax::_responseError('Ошибка выполнения','Не выбрана организация');
		}
		$post_uid = $request->getId('post_uid', 0);



		$organization = new Admin_Organization();
		if(!$organization->postUIDExists($post_uid)) return Ajax::_responseError('Ошибка выполнения','Выбранной должности не существует');

		$admin_employers = new Admin_Employers();

		#Выполнено успешно
		return Ajax::_setData(array(
			'company_id'	=> $company_id,
			'post_uid'		=> $post_uid,
			'employers'		=> $admin_employers->getEmployersOnPostEx($post_uid)
		));

	break; #Получение информации по выбранной должности



	default:
	Ajax::_responseError('/main/ajax/org','Не найден обработчик для: '.Request::_get('action'));
}

?>