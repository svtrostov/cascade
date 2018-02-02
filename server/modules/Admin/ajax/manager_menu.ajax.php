<?php
/*==================================================================================================
Описание: Обработка AJAX запросов
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');

if(!$uaccess->checkAccess('admin.menu.moderate', 0)){
	return Ajax::_responseError('Ошибка выполнения','Недостаточно прав для работы с меню');
}

$request_action = Request::_get('action');

#Обработка AJAX запроса, в зависимости от запрошенного действия
switch($request_action){


	/*******************************************************************
	 * Добавление пункта в меню
	 ******************************************************************/
	case 'menu.item.new':

		//Поисковый запрос
		$menu_id			= $request->getId('menu_id', 0);
		$parent_id			= $request->getId('parent_id', 0);
		$access_object_id	= $request->getId('access_object_id', 0);
		$is_lock			= $request->getBool('is_lock', false);
		$is_folder			= $request->getBool('is_folder', false);
		$href				= trim($request->getStr('href', ''));
		$target				= $request->getEnum('target', array('_self','_blank'), '_self');
		$title				= $request->getStr('title', '');
		$desc				= $request->getStr('desc', '');
		$class				= $request->getStr('class', '');

		$db = Database::getInstance('main');
		$db->transaction();

		if($access_object_id > 0){
			if(!UserAccess::_getObject($access_object_id)) $access_object_id=0;
		}

		if(empty($href)) $href='#';

		//Проверка сущестсвования родителя
		if($parent_id > 0){
			$db->prepare('SELECT count(*) FROM `menu_map` WHERE `item_id`=? LIMIT 1');
			$db->bind($parent_id);
			if($db->result() != 1) $parent_id = 0;
		}


		//Добавление анкеты
		$db->prepare('INSERT INTO `menu_map` (`menu_id`,`parent_id`,`access_object_id`,`is_lock`,`is_folder`,`href`,`target`,`title`,`desc`,`class`) VALUES (?,?,?,?,?, ?,?,?,?,?)');
		$db->bind($menu_id);
		$db->bind($parent_id);
		$db->bind($access_object_id);
		$db->bind(($is_lock?'1':'0'));
		$db->bind(($is_folder?'1':'0'));
		$db->bind($href);
		$db->bind($target);
		$db->bind($title);
		$db->bind($desc);
		$db->bind($class);

		if( ($item_id=$db->insert()) === false){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/manager_menu/menu.item.new /SQL:INSERT/false');
		}

		Protocol::_add(array(
			'action_name'	=> $request_action,
			'acl_id'		=> $uaccess->getObjectIdFormName('admin.menu.moderate'),
			'acl_name'		=> 'admin.menu.moderate',
			'primary_type'	=> 'menuitem',
			'primary_id'	=> $item_id,
			'description'	=> 'Создан пункт меню ID:'.$item_id.' в меню ID:'.$menu_id,
			'value'			=> array(
				'menu_id'			=> $menu_id,
				'item_id'			=> $item_id,
				'parent_id'			=> $parent_id,
				'access_object_id'	=> $access_object_id,
				'is_folder'			=> $is_folder,
				'is_lock'			=> $is_lock,
				'href'				=> $href,
				'target'			=> $target,
				'title'				=> $title,
				'desc'				=> $desc,
				'class'				=> $class
			)
		));

		//Успешно
		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			'menu_id' => $menu_id,
			'menu' => Menu::_getMenu($menu_id),
			'default_id' => $item_id,
			'aobjects' => UserAccess::_searchObjects(array('type' => ACL_OBJECT_PAGE),array('object_id','namedesc'))
		));
		
	break; #Добавление пункта в меню






	/*******************************************************************
	 * Редактирование пункта меню
	 ******************************************************************/
	case 'menu.item.edit':

		//Поисковый запрос
		$menu_id			= $request->getId('menu_id', 0);
		$item_id			= $request->getId('item_id', 0);
		$parent_id			= $request->getId('parent_id', 0);
		$access_object_id	= $request->getId('access_object_id', 0);
		$is_lock			= $request->getBool('is_lock', false);
		$is_folder			= $request->getBool('is_folder', false);
		$href				= trim($request->getStr('href', ''));
		$target				= $request->getEnum('target', array('_self','_blank'), '_self');
		$title				= $request->getStr('title', '');
		$desc				= $request->getStr('desc', '');
		$class				= $request->getStr('class', '');

		if($item_id == $parent_id){
			return Ajax::_responseError('Ошибка выполнения','Элемент меню не может ссылаться сам на себя');
		}

		$db = Database::getInstance('main');
		$db->transaction();

		$db->prepare('SELECT count(*) FROM `menu_map` WHERE `item_id`=? AND `menu_id`=? LIMIT 1');
		$db->bind($item_id);
		$db->bind($menu_id);
		if($db->result() != 1){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Элемент ID:'.$item_id.' в меню ID:'.$menu_id.' не существует');
		}

		if($access_object_id > 0){
			if(!UserAccess::_getObject($access_object_id)) $access_object_id=0;
		}

		if(empty($href)) $href='#';

		//Проверка сущестсвования родителя
		if($parent_id > 0){
			$db->prepare('SELECT count(*) FROM `menu_map` WHERE `item_id`=? AND `menu_id`=? LIMIT 1');
			$db->bind($parent_id);
			$db->bind($menu_id);
			if($db->result() != 1) $parent_id = 0;
		}


		//Добавление анкеты
		$db->prepare('UPDATE `menu_map` SET `parent_id`=?,`access_object_id`=?,`is_lock`=?,`is_folder`=?,`href`=?,`target`=?,`title`=?,`desc`=?,`class`=? WHERE `item_id`=? AND `menu_id`=?');
		$db->bind($parent_id);
		$db->bind($access_object_id);
		$db->bind(($is_lock?'1':'0'));
		$db->bind(($is_folder?'1':'0'));
		$db->bind($href);
		$db->bind($target);
		$db->bind($title);
		$db->bind($desc);
		$db->bind($class);
		$db->bind($item_id);
		$db->bind($menu_id);

		if($db->update() === false){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/manager_menu/menu.item.edit /SQL:UPDATE/false');
		}

		if(!$is_folder){
			$db->prepare('UPDATE `menu_map` SET `parent_id`=0 WHERE `parent_id`=? AND `menu_id`=?');
			$db->bind($item_id);
			$db->bind($menu_id);
			if($db->update() === false){
				$db->rollback();
				return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/manager_menu/menu.item.delete /SQL:UPDATE/false');
			}
		}

		Protocol::_add(array(
			'action_name'	=> $request_action,
			'acl_id'		=> $uaccess->getObjectIdFormName('admin.menu.moderate'),
			'acl_name'		=> 'admin.menu.moderate',
			'primary_type'	=> 'menuitem',
			'primary_id'	=> $item_id,
			'description'	=> 'Изменен пункт меню ID:'.$item_id.' в меню ID:'.$menu_id,
			'value'			=> array(
				'menu_id'			=> $menu_id,
				'item_id'			=> $item_id,
				'parent_id'			=> $parent_id,
				'access_object_id'	=> $access_object_id,
				'is_folder'			=> $is_folder,
				'is_lock'			=> $is_lock,
				'href'				=> $href,
				'target'			=> $target,
				'title'				=> $title,
				'desc'				=> $desc,
				'class'				=> $class
			)
		));

		//Успешно
		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			'menu_id' => $menu_id,
			'menu' => Menu::_getMenu($menu_id),
			'default_id' => $item_id,
			'aobjects' => UserAccess::_searchObjects(array('type' => ACL_OBJECT_PAGE),array('object_id','namedesc'))
		));
		
	break; #Редактирование пункта меню






	/*******************************************************************
	 * Удаление пункта меню
	 ******************************************************************/
	case 'menu.item.delete':

		//Поисковый запрос
		$menu_id			= $request->getId('menu_id', 0);
		$item_id			= $request->getId('item_id', 0);

		$db = Database::getInstance('main');
		$db->transaction();

		$db->prepare('SELECT count(*) FROM `menu_map` WHERE `item_id`=? AND `menu_id`=? LIMIT 1');
		$db->bind($item_id);
		$db->bind($menu_id);
		if($db->result() != 1){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Элемент ID:'.$item_id.' в меню ID:'.$menu_id.' не существует');
		}

		$db->prepare('DELETE FROM `menu_map` WHERE `item_id`=? AND `menu_id`=?');
		$db->bind($item_id);
		$db->bind($menu_id);
		if($db->delete() === false){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/manager_menu/menu.item.delete /SQL:DELETE/false');
		}

		$db->prepare('UPDATE `menu_map` SET `parent_id`=0 WHERE `parent_id`=? AND `menu_id`=?');
		$db->bind($item_id);
		$db->bind($menu_id);
		if($db->update() === false){
			$db->rollback();
			return Ajax::_responseError('Ошибка выполнения','Внутренняя ошибка сервера, сообщите администратору следующую информацию: /ajax/manager_menu/menu.item.delete /SQL:UPDATE/false');
		}

		Protocol::_add(array(
			'action_name'	=> $request_action,
			'acl_id'		=> $uaccess->getObjectIdFormName('admin.menu.moderate'),
			'acl_name'		=> 'admin.menu.moderate',
			'primary_type'	=> 'menuitem',
			'primary_id'	=> $item_id,
			'description'	=> 'Удален пункт меню ID:'.$item_id.' в меню ID:'.$menu_id,
			'value'			=> array()
		));

		//Успешно
		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			'menu_id' => $menu_id,
			'menu' => Menu::_getMenu($menu_id),
			'default_id' => $item_id,
			'aobjects' => UserAccess::_searchObjects(array('type' => ACL_OBJECT_PAGE),array('object_id','namedesc'))
		));
		
	break; #Удаление пункта меню





	default:
	Ajax::_responseError('/main/ajax/manager_menu','Не найден обработчик для: '.Request::_get('action'));


}




?>