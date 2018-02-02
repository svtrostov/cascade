<?php
/*==================================================================================================
Описание: Обработка AJAX запросов
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');


$request_action = Request::_get('action');

LABEL_MATRIX_AXCONTROLLER_START:

#Обработка AJAX запроса, в зависимости от запрошенного действия
switch($request_action){



	/*******************************************************************
	 * Список сотрудников, которые имеют права доступа к указанному объекту доступа
	 ******************************************************************/
	case 'irole.employers':

		$iresource_id = $request->getId('iresource_id', 0);
		$irole_id = $request->getId('irole_id', 0);

		if(empty($iresource_id)||empty($irole_id)) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');

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
				EMP.`search_name` as `employer_name`,
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
			'employers' => $db->select()
		));

	break; #Список сотрудников, которые имеют права доступа к указанному объекту доступа






	/*******************************************************************
	 * Список информационных ресурсов и объектов доступа, к которым имеет доступ сотрудник
	 ******************************************************************/
	case 'employer.iresources':

		$employer_id = $request->getId('employer_id', 0);
		$post_uid = $request->getId('post_uid', 0);
		$get_posts = $request->getBool('get_posts', true);

		if(empty($employer_id)) return Ajax::_responseError('Ошибка выполнения','Некорректный запрос');

		$admin_employers = new Admin_Employers();
		if(!$admin_employers->employerExists($employer_id)) return Ajax::_responseError('Ошибка выполнения','Сотрудник ID:'.$employer_id.' не существует');
		if(!empty($post_uid) && !$admin_employers->employerPostExists($employer_id, $post_uid)) return Ajax::_responseError('Ошибка выполнения','Сотрудник ID:'.$employer_id.' не занимает выбранную должность');

		$db = Database::getInstance('main');
		$result = array();
		if($get_posts){
			$result['posts'] = $admin_employers->getEmployersPostsEx($employer_id, false, array('post_uid','post_name'));
		}

		//Должность не указана
		if(empty($post_uid)){
			$db->prepare('SELECT DISTINCT `iresource_id` FROM `complete_roles_full` WHERE `employer_id`=?');
			$db->bind($employer_id);
		}else{
			$db->prepare('SELECT DISTINCT `iresource_id` FROM `complete_roles_full` WHERE `employer_id`=? AND `post_uid`=?');
			$db->bind($employer_id);
			$db->bind($post_uid);
		}
		if(($iresources = $db->selectFromField('iresource_id')) === false) return Ajax::_responseError('Ошибка выполнения','Ошибка получение списка объектов доступа сотрудника');

		$ir_list = array();
		$admin_matrix = new Admin_Matrix();

		//Получение списка объектов ИР
		foreach($iresources as $iresource_id){
			$ir_list[$iresource_id] = $admin_matrix->employerIResourceRoles($employer_id, $post_uid, $iresource_id);
		}//Получение списка объектов ИР





		$result['employer_iresources'] = $ir_list;

		return Ajax::_setData($result);

	break; # Список информационных ресурсов и объектов доступа, к которым имеет доступ сотрудник








	default:
	Ajax::_responseError('/admin/ajax/matrix','Не найден обработчик для: '.Request::_get('action'));
}
?>