<?php
/*==================================================================================================
Описание: Контроллер страниц модуля Admin
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');



/*
 * Контроллер модуля ADMIN
 */
function adminController($data){

	$user		= $data['user'];
	$template	= $data['template'];
	$request	= $data['request'];
	$ajax		= Ajax::getInstance();
	$uaccess	= UserAccess::getInstance();
	$is_ajax	= $request->get('ajax', false);
	$is_custom	= $request->getBool('custom', false);
	$is_post	= ($request->get('method', 'GET') == 'POST' ? true : false);
	$route_way = $request->get('way', false);
	$page = $request->get('page', false);
	$action = $request->get('action', false);
	$module = $request->get('module', false);
	$is_auth_user = $user->checkAuthStatus();

	#Если пользователь не аутентифицирован или не имеет доступа к административному интерфейсу - редирект на /main/index
	if(!$is_auth_user || !$user->isAdmin()){
		return Page::_doLocation((!$is_auth_user?'/main/login':'/main/index'));
	}#Если пользователь не аутентифицирован

	#Неизвестно что запрошено - 404
	if(empty($route_way)) return Page::_httpError(404,'#adminarea');


	#Запрошен произвольный контент
	if($is_custom || $route_way[0]=='customcontent'){

		switch($route_way[0]){
			#Прочий произвольный контент
			case 'customcontent':
				if(empty($route_way[1])) return true;

				switch($route_way[1]){
					#Сертификаты
					case 'certificate':
						require_once(DIR_MODULES.'/Admin/custom/certificate.php');
					break;
					#Печать учетных данных
					case 'accountprint':
						require_once(DIR_MODULES.'/Admin/custom/accountprint.php');
					break;
				}

				/*
				case 'latin':
					$db = Database::getInstance('main');
					$data = $db->select('SELECT `username`,`search_name` from `employers`');
					foreach($data as $item){
						echo $item['username']."\t".$item['search_name']."\n";
					}
				break;
				*/

			break;
		}
		return true;
	}#Запрошен произвольный контент



	#Если запрос не по AJAX - возвращаем контент index.tpl
	#и далее через AJAX делается запрос интересуемой страницы
	if(!$is_ajax){
		if($route_way[0] == 'logout') return Page::_doLogout('/main/login');
		$request->request['get']['init']=1;
		$template->setTemplate('Admin/templates/index.php');
		$template->display();
		return true;
	}

	$template_name = null;

	#ДЛЯ ТЕСТА: НЕ ЗАБУДЬ УДАЛИТЬ!!!  
	#А ТО БУДЕТ ВЕЧНАОЕ ДЕРГАНЬЕ БД ВМЕСТО КЕША
	#//$uaccess->setAccessObjectsActual();
	#ДЛЯ ТЕСТА: НЕ ЗАБУДЬ УДАЛИТЬ!!!  

	#Если происходит инициализация приложения
	if($request->getInt('init',0) == 1){

		ac_getAdminTopMenu();
		$ajax->setStack('adminmenu',$uaccess->getUserMenu(2));

	}#происходит инициализация приложения


	#Обработка AJAX запроса
	switch($route_way[0]){


		#Main страница
		case 'index':
		case 'main':
			$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-admin-main.css');
			$ajax->addRequired('/client/js/Admin/admin_main.js','admin_main');
			$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
			$template_name = 'main.php';
			$admin_request = new Admin_Request();
			Ajax::_setData(array(
				'requests' => ($uaccess->checkAccess('page.requests.list',0) ? $admin_request->getRequestsListEx(null,null,false,true,9) : null),
				'stats' => ac_statistics()
			));
		break;



		#Logout страница
		case 'logout':
			return Page::_doLogout('/main/login');
		break;#Logout страница



		#AJAX операции
		case 'ajax':
			if(empty($route_way[1])) return Page::_httpError(404,'#adminarea');
			switch($route_way[1]){
				case 'manager_menu':
					require_once(DIR_MODULES.'/Admin/ajax/manager_menu.ajax.php');
				break;
				case 'acl':
					require_once(DIR_MODULES.'/Admin/ajax/acl.ajax.php');
				break;
				case 'protocol':
					require_once(DIR_MODULES.'/Admin/ajax/protocol.ajax.php');
				break;
				case 'org':
					require_once(DIR_MODULES.'/Admin/ajax/org.ajax.php');
				break;
				case 'employers':
					require_once(DIR_MODULES.'/Admin/ajax/employers.ajax.php');
				break;
				case 'iresources':
					require_once(DIR_MODULES.'/Admin/ajax/iresources.ajax.php');
				break;
				case 'templates':
					require_once(DIR_MODULES.'/Admin/ajax/templates.ajax.php');
				break;
				case 'routes':
					require_once(DIR_MODULES.'/Admin/ajax/routes.ajax.php');
				break;
				case 'requests':
					require_once(DIR_MODULES.'/Admin/ajax/requests.ajax.php');
				break;
				case 'matrix':
					require_once(DIR_MODULES.'/Admin/ajax/matrix.ajax.php');
				break;
				case 'ldap':
					require_once(DIR_MODULES.'/Admin/ajax/ldap.ajax.php');
				break;
				default: return Page::_httpError(404,'#adminarea');
			}
		break;#AJAX операции



		#Менеджер
		case 'manager':
			if(empty($route_way[1])) return Page::_httpError(404,'#adminarea');
			switch($route_way[1]){

				#Менеджер Меню
				case 'menu':
					if(!ac_checkPageAccess('page.manager.menu', $template_name)) break;
					$ajax->addRequired('/client/js/Admin/manager_menu.js','manager_menu_enter_page');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-manager-menu.css');
					$template_name = 'manager_menu.php';
					$ajax->setData(array(
						'menu_id' => 2,
						'menu' => Menu::_getMenu(2),
						'aobjects' => $uaccess->searchObjects(array('type' => ACL_OBJECT_PAGE),array('object_id','namedesc'))
					));
				break;

				#Менеджер ACL объектов
				case 'acl':
					if(!ac_checkPageAccess('page.manager.acl', $template_name)) break;
					$ajax->addRequired('/client/js/Admin/manager_acl.js','manager_acl_enter_page');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-manager-acl.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
					$template_name = 'manager_acl.php';
					Ajax::_setData(array(
						'aobjects' => $uaccess->searchObjects(),
						'otypes' => $uaccess->getObjectTypes()
					));
				break;

				#Менеджер прав пользователей
				case 'access':
					if(!ac_checkPageAccess('page.manager.access', $template_name)) break;
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-manager-access.css');
					$ajax->addRequired('/client/js/Admin/manager_access.js','manager_access_enter_page');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
					$template_name = 'manager_access.php';
					Ajax::_setData(array(
						'aobjects' => $uaccess->searchObjects(),
						'otypes' => $uaccess->getObjectTypes(),
						//'employers' => Database::getInstance('main')->select('SELECT `employer_id`,`search_name`,DATE_FORMAT(`birth_date`, "%d.%m.%Y") as `birth_date` FROM `employers` ORDER BY `search_name`'),
						'companies' => Database::getInstance('main')->select('SELECT `company_id`,`full_name` as `company_name` FROM `companies`')
					));
				break;

				#Протокол действий пользователей
				case 'protocol':
					if(!ac_checkPageAccess('page.manager.protocol', $template_name)) break;
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-manager-protocol.css');
					$ajax->addRequired('/client/js/Admin/manager_protocol.js','manager_protocol');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
					$template_name = 'manager_protocol.php';
					$protocol = Protocol::getInstance();
					Ajax::_setData(array(
						'types'		=> $protocol->getObjectTypes(),
						'aobjects' => $uaccess->searchObjects(),
						'companies' => Database::getInstance('main')->select('SELECT `company_id`,`full_name` FROM `companies`')
					));
				break;

				default: return Page::_httpError(404,'#adminarea');
			}
		break;#Менеджер


		#Матрица доступа
		case 'matrix':

			if(empty($route_way[1])) return Page::_httpError(404,'#adminarea');
			switch($route_way[1]){

				#Матрица доступа по информационным ресурсам
				case 'iresources':
					if(!ac_checkPageAccess('page.matrix.iresources', $template_name)) break;
					$template_name = 'matrix_iresources.php';
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-matrix-iresources.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
					$ajax->addRequired('/client/js/Admin/matrix_iresources.js','matrix_iresources');
					$admin_iresource = new Admin_IResource();
					Ajax::_setData(array(
						'iresources'		=> $admin_iresource->getIResourcesList(null,array('iresource_id','full_name')),
						'ir_types'			=> $admin_iresource->getIRTypesList()
					));
				break;


				#Матрица доступа по сотрудникам
				case 'employers':
					if(!ac_checkPageAccess('page.matrix.employers', $template_name)) break;
					$template_name = 'matrix_employers.php';
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-matrix-employers.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
					$ajax->addRequired('/client/js/Admin/matrix_employers.js','matrix_employers');
					$admin_iresource = new Admin_IResource();
					Ajax::_setData(array(
						'iresources'	=> $admin_iresource->getIResourcesList(null,array('iresource_id','full_name')),
						'ir_types'		=> $admin_iresource->getIRTypesList()
					));
				break;

				default: return Page::_httpError(404,'#adminarea');
			}

		break; #Матрица доступа




		#Информационные ресурсы
		case 'iresources':

			if(empty($route_way[1])) return Page::_httpError(404,'#adminarea');
			switch($route_way[1]){

				#Список информационных ресурсов
				case 'list':
					if(!ac_checkPageAccess('page.iresources.list', $template_name)) break;
					$template_name = 'iresources_list.php';
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-iresources-list.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
					$ajax->addRequired('/client/js/Admin/iresources_list.js','iresources_list');
					$admin_iresource = new Admin_IResource();
					$organization = new Admin_Organization();
					$admin_employers = new Admin_Employers();
					Ajax::_setData(array(
						'iresources' => $admin_iresource->getIResourcesListEx(),
						'groups'	=> $admin_employers->getGroupsList(array('group_id','full_name')),
						'companies' => $organization->getCompaniesList(array('company_id','full_name')),
						'iresource_groups'	=> $admin_iresource->getIGroupsList()
					));
				break;


				#Новый ресурс
				case 'add':
					$template_name = 'iresources_add.php';
					if(!ac_checkPageAccess('page.iresources.add', $template_name)) break;
					$ajax->addRequired('/client/js/Admin/iresources_add.js','iresources_add');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-org-chart.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-iresources-add.css');
					$organization = new Admin_Organization();
					$admin_employers = new Admin_Employers();
					$admin_iresource = new Admin_IResource();
					$ajax->setData(array(
						'groups'			=> $admin_employers->getGroupsList(array('group_id','full_name')),
						'companies'			=> $organization->getCompaniesList(array('company_id','full_name')),
						'iresource_groups'	=> $admin_iresource->getIGroupsList()
					));
				break;


				#Карточка ресурса
				case 'info':
					$template_name = 'iresources_info.php';
					if(!ac_checkPageAccess('page.iresources.info', $template_name)) break;
					$ajax->addRequired('/client/js/Admin/iresources_info.js','iresources_info');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-org-chart.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-iresources-info.css');
					$organization = new Admin_Organization();
					$admin_iresource = new Admin_IResource();
					$admin_employers = new Admin_Employers();
					$iresource_id = $request->getId('iresource_id',0);
					if(empty($iresource_id)) break;
					$iresource = $admin_iresource->getIResourcesListEx($iresource_id,null,true,true);
					if(empty($iresource)||!is_array($iresource)||empty($iresource['iresource_id'])) break;
					$ajax->setData(array(
						'iresource'				=> $iresource,
						'groups'				=> $admin_employers->getGroupsList(array('group_id','full_name')),
						'companies'				=> $organization->getCompaniesList(array('company_id','full_name')),
						'iresource_companies'	=> $admin_iresource->getIResourceCompanies($iresource['iresource_id']),
						'ir_types'				=> $admin_iresource->getIRTypesList(),
						'iroles'				=> $admin_iresource->getIRoles($iresource['iresource_id']),
						'iresource_groups'		=> $admin_iresource->getIGroupsList()
					));
				break;


				#Типы доступов
				case 'irtypes':
					$template_name = 'iresources_irtypes.php';
					if(!ac_checkPageAccess('page.access.types', $template_name)) break;
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-iresources-irtypes.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
					$ajax->addRequired('/client/js/Admin/iresources_irtypes.js','iresources_irtypes');
					$admin_iresource = new Admin_IResource();
					$ajax->setData(array(
						'ir_types' => $admin_iresource->getIRTypesList()
					));
				break;


				#Группы информационных ресурсов
				case 'groups':
					$template_name = 'iresources_groups.php';
					if(!ac_checkPageAccess('page.iresources.groups', $template_name)) break;
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-iresources-groups.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
					$ajax->addRequired('/client/js/Admin/iresources_groups.js','iresources_groups');
					$admin_iresource = new Admin_IResource();
					$ajax->setData(array(
						'iresource_groups' => $admin_iresource->getIGroupsList()
					));
				break;


				default: return Page::_httpError(404,'#adminarea');
			}

		break; #Информационные ресурсы




		#Маршруты согласования
		case 'routes':

			if(empty($route_way[1])) return Page::_httpError(404,'#adminarea');
			switch($route_way[1]){

				#Новый маршрут
				case 'add':
					$template_name = 'routes_add.php';
					if(!ac_checkPageAccess('page.routes.add', $template_name)) break;
					$ajax->addRequired('/client/js/Admin/routes_add.js','routes_add');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-org-chart.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-routes-add.css');
					$ajax->setData(array(
					));
				break;

				#Список маршрутов
				case 'list':
					if(!ac_checkPageAccess('page.routes.list', $template_name)) break;
					$template_name = 'routes_list.php';
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-routes-list.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
					$ajax->addRequired('/client/js/Admin/routes_list.js','routes_list');
					$admin_route = new Admin_Route();
					Ajax::_setData(array(
						'routes' => $admin_route->getRoutesListEx()
					));
				break;


				#Карточка маршрута
				case 'info':
					$template_name = 'routes_info.php';
					if(!ac_checkPageAccess('page.routes.info', $route_name)) break;
					$ajax->addRequired(array(
						array('/client/js/lib/plumb-1.4.0/jsBezier-0.6.js'),
						array('/client/js/lib/plumb-1.4.0/jsPlumb-util-1.4.0-RC1.js'),
						array('/client/js/lib/plumb-1.4.0/jsPlumb-dom-adapter-1.4.0-RC1.js'),
						array('/client/js/lib/plumb-1.4.0/jsPlumb-1.4.0-RC1.js'),
						array('/client/js/lib/plumb-1.4.0/jsPlumb-anchors-1.4.0-RC1.js'),
						array('/client/js/lib/plumb-1.4.0/jsPlumb-endpoint-1.4.0-RC1.js'),
						array('/client/js/lib/plumb-1.4.0/jsPlumb-connection-1.4.0-RC1.js'),
						array('/client/js/lib/plumb-1.4.0/jsPlumb-defaults-1.4.0-RC1.js'),
						array('/client/js/lib/plumb-1.4.0/jsPlumb-connectors-flowchart-1.4.0-RC1.js'),
						array('/client/js/lib/plumb-1.4.0/jsPlumb-renderers-svg-1.4.0-RC1.js'),
						array('/client/js/lib/plumb-1.4.0/jsPlumb-renderers-canvas-1.4.0-RC1.js'),
						array('/client/js/lib/plumb-1.4.0/jsPlumb-renderers-vml-1.4.0-RC1.js'),
						array('/client/js/lib/plumb-1.4.0/mootools.jsPlumb-1.4.0-RC1.js'),
						array('/client/js/Admin/routes_info.js','routes_info')
					));
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-org-chart.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-routes-info.css');
					$organization = new Admin_Organization();
					$admin_route = new Admin_Route();
					$admin_iresource = new Admin_IResource();
					$admin_employers = new Admin_Employers();
					$route_id = $request->getId('route_id', 0);
					if(empty($route_id)) break;
					$routeinfo = $admin_route->getRoutesListEx($route_id,null,true,true);
					if(empty($routeinfo)||!is_array($routeinfo)||empty($routeinfo['route_id'])) break;
					$ajax->setData(array(
						'routes'		=> $admin_route->getRoutesListEx(null,array('route_id','full_name')),
						'route'			=> $routeinfo,
						'params'		=> $admin_route->routeParamsEx($routeinfo['route_id']),
						'companies'		=> $organization->getCompaniesList(array('company_id','full_name')),
						'groups'		=> $admin_employers->getGroupsList(array('group_id','full_name')),
						'iresources'	=> $admin_iresource->getIResourcesList(null,array('iresource_id','full_name'))
					));
				break;


			}

		break; #Маршруты согласования




		#Шаблоны заявок
		case 'templates':

			if(empty($route_way[1])) return Page::_httpError(404,'#adminarea');
			switch($route_way[1]){

				#Список шаблонов
				case 'list':
					if(!ac_checkPageAccess('page.templates.list', $template_name)) break;
					$template_name = 'templates_list.php';
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-templates-list.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
					$ajax->addRequired('/client/js/Admin/templates_list.js','templates_list');
					$admin_template = new Admin_Template();
					$organization = new Admin_Organization();
					Ajax::_setData(array(
						'templates' => $admin_template->getTemplatesListEx(),
						'companies' => $organization->getCompaniesList(array('company_id','full_name'))
					));
				break;


				#Новый шаблон
				case 'add':
					$template_name = 'templates_add.php';
					if(!ac_checkPageAccess('page.templates.add', $template_name)) break;
					$ajax->addRequired('/client/js/Admin/templates_add.js','templates_add');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-org-chart.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-templates-add.css');
					$organization = new Admin_Organization();
					$ajax->setData(array(
						'companies'		=> $organization->getCompaniesList(array('company_id','full_name'))
					));
				break;


				#Карточка шаблона
				case 'info':
					$template_name = 'templates_info.php';
					if(!ac_checkPageAccess('page.templates.info', $template_name)) break;
					$ajax->addRequired('/client/js/Admin/templates_info.js','templates_info');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-org-chart.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-templates-info.css');
					$organization = new Admin_Organization();
					$admin_template = new Admin_Template();
					$admin_iresource = new Admin_IResource();
					$template_id = $request->getId('template_id', 0);
					if(empty($template_id)) break;
					$templateinfo = $admin_template->getTemplatesListEx($template_id,null,true,true);
					if(empty($templateinfo)||!is_array($templateinfo)||empty($templateinfo['template_id'])) break;
					$ajax->setData(array(
						'templates'				=> $admin_template->getTemplatesList(null,array('template_id','full_name')),
						'template'				=> $templateinfo,
						'companies'				=> $organization->getCompaniesList(array('company_id','full_name')),
						'iresources'			=> $admin_iresource->getIResourcesList(null,array('iresource_id','full_name')),
						'ir_types'				=> $admin_iresource->getIRTypesList(),
						'tmpl_roles'			=> $admin_template->templateRoles($templateinfo['template_id'])
					));
				break;


				default: return Page::_httpError(404,'#adminarea');
			}

		break; #Шаблоны заявок



		#Сотрудники
		case 'employers':
			if(empty($route_way[1])) return Page::_httpError(404,'#adminarea');
			switch($route_way[1]){

				#Группы сотрудников
				case 'groups':
					$template_name = 'employers_groups.php';
					if(!ac_checkPageAccess('page.employers.groups', $template_name)) break;
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-employers-groups.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
					$ajax->addRequired('/client/js/Admin/employers_groups.js','employers_groups_enter_page');
					$admin_employers = new Admin_Employers();
					$ajax->setData(array(
						'groups' => $admin_employers->getGroupsListEx()//,
						//'employers' => $admin_employers->getEmployersList(null,array('employer_id','search_name','username','birth_date'),false)
					));
				break;


				#Список сотрудников
				case 'list':
					$template_name = 'employers_list.php';
					if(!ac_checkPageAccess('page.employers.list', $template_name)) break;
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-employers-list.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
					$ajax->addRequired('/client/js/Admin/employers_list.js','employers_list');
					$ajax->setData(array(
						'filter'	=> array(
							'status' => '1',
							'search_name'=> ''
						)
					));
				break;


				#Анкеты новых сотрудников
				case 'ankets':
					$template_name = 'employers_ankets.php';
					if(!ac_checkPageAccess('page.employers.ankets', $template_name)) break;
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-employers-ankets.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
					$ajax->addRequired('/client/js/Admin/employers_ankets.js','employers_ankets');
					$organization = new Admin_Organization();
					$admin_employers = new Admin_Employers();
					$ajax->setData(array(
						'companies' => $organization->getCompaniesList(array('company_id','full_name')),
						'ankets'	=> $admin_employers->getAnketsListEx(array('anket_type'=>1),null,false),
						'filter'	=> array(
							'company_id'	=> '0',
							'search_name'	=> '',
							'anket_type'	=> '1'
						)
					));
				break;


				#Новый сотрудник
				case 'add':
					$template_name = 'employers_add.php';
					if(!ac_checkPageAccess('page.employers.add', $template_name)) break;
					$ajax->addRequired('/client/js/Admin/employers_add.js','employers_add_enter_page');
					$ajax->setData(array());
				break;


				#Карточка сотрудника
				case 'info':
					$template_name = 'employers_info.php';
					if(!ac_checkPageAccess('page.employers.info', $template_name)) break;
					$employer_id = $request->getId('employer_id',0);
					$organization = new Admin_Organization();
					$admin_employers = new Admin_Employers();
					$ajax->addRequired('/client/js/Admin/employers_info.js','employers_info_enter_page');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-employers-info.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-org-chart.css');
					$ajax->setData(array(
						'employer_info'			=>	$admin_employers->getEmployersList($employer_id, array(
													'employer_id','status','access_level','username','search_name','first_name','last_name',
													'middle_name','birth_date','phone','email','work_name','work_address',
													'work_post','work_phone','create_date','anket_id','never_assistant',
													'notice_me_requests','notice_curator_requests',
													'notice_gkemail_1','notice_gkemail_2','notice_gkemail_3',
													'notice_gkemail_4','ignore_pin'
												),true),
						'companies'				=> $organization->getCompaniesList(array('company_id','full_name')),
						'groups'				=> $admin_employers->getGroupsList(array('group_id','full_name')),
						'employer_groups'		=> $admin_employers->getEmployersGroups($employer_id),
						'employer_posts'		=> $admin_employers->getEmployersPostsEx($employer_id, true),
						'employer_assistants'	=> $admin_employers->getEmployersAssistantsEx($employer_id, true),
						'employer_delegates'	=> $admin_employers->getEmployersDelegatesEx($employer_id, true),
						'employer_rights'		=> $admin_employers->getEmployersRights($employer_id),
						'employer_certs'		=> $admin_employers->getEmployersCertificates($employer_id, array('employer_id','SSL_CERT_HASH','SSL_CLIENT_M_SERIAL','SSL_CLIENT_S_DN_L','SSL_CLIENT_S_DN_O','SSL_CLIENT_S_DN_OU','SSL_CLIENT_S_DN_CN'))
					));
				break;


				#Просмотр анкеты сотрудника
				case 'anketinfo':
					$template_name = 'employers_anket_info.php';
					if(!ac_checkPageAccess('page.employers.anketinfo', $template_name)) break;
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-employers-anket-info.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-org-chart.css');
					$ajax->addRequired('/client/js/Admin/employers_anket_info.js','employers_anket_info');
					$organization = new Admin_Organization();
					$admin_employers = new Admin_Employers();
					$anket_info = $admin_employers->getAnketInfo(array('anket_id'=>$request->getId('anket_id',0)));
					if(!empty($anket_info)){
						$employers = $admin_employers->anketRelatedEmployers($anket_info);
					}else{
						$employers = null;
					}
					$ajax->setData(array(
						'anket_info'		=> $anket_info,
						'companies' 		=> $organization->getCompaniesList(array('company_id','full_name')),
						'employers_search'	=> $employers
					));
				break;


				default: return Page::_httpError(404,'#adminarea');
			}
		break;#Сотрудники





		#Организационная структура
		case 'org':
			if(empty($route_way[1])) return Page::_httpError(404,'#adminarea');
			switch($route_way[1]){

				#Организации
				case 'companies':
					$template_name = 'org_companies.php';
					if(!ac_checkPageAccess('page.org.companies', $template_name)) break;
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-org-companies.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
					$ajax->addRequired('/client/js/Admin/org_companies.js','org_companies_enter_page');
					$organization = new Admin_Organization();
					$ajax->setData(array(
						'companies' => $organization->getCompaniesList()
					));
				break;


				#Должности
				case 'posts':
					$template_name = 'org_posts.php';
					if(!ac_checkPageAccess('page.org.posts', $template_name)) break;
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-org-posts.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
					$ajax->addRequired('/client/js/Admin/org_posts.js','org_posts_enter_page');
					$organization = new Admin_Organization();
					$ajax->setData(array(
						'posts' => $organization->getPostsList()
					));
				break;


				#Организационная диаграмма
				case 'structure':
					$template_name = 'org_structure.php';
					if(!ac_checkPageAccess('page.org.structure', $template_name)) break;
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-org-structure.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-org-chart.css');
					$ajax->addRequired('/client/js/Admin/org_structure.js','org_structure_enter_page');
					$companies = $uaccess->getCompanies($uaccess->getAllowedCompaniesForObject('org.structure.load'));
					if(empty($companies)) break;
					$organization = new Admin_Organization();
					$ajax->setData(array(
						'companies' => $companies,
						'company_id'=> $companies[0]['company_id'],
						'org_data' => $organization->getOrgChart($companies[0]['company_id']),
						'posts' => $organization->getPostList()
					));
				break;



				default: return Page::_httpError(404,'#adminarea');
			}
		break;#Организационная структура




		#Заявки
		case 'requests':

			if(empty($route_way[1])) return Page::_httpError(404,'#adminarea');
			switch($route_way[1]){

				#Список заявок
				case 'list':
					if(!ac_checkPageAccess('page.requests.list', $template_name)) break;
					$template_name = 'requests_list.php';
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-requests-list.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
					$ajax->addRequired('/client/js/Admin/requests_list.js','requests_list');
					$organization = new Admin_Organization();
					$admin_iresource = new Admin_IResource();
					$admin_route = new Admin_Route();
					Ajax::_setData(array(
						'iresources'=> $admin_iresource->getIResourcesList(null,array('iresource_id','full_name')),
						'companies' => $organization->getCompaniesList(array('company_id','full_name')),
						'routes' => $admin_route->getRoutesListEx(null,array('route_id','full_name'))
					));
				break;

				#Карточка заяки
				case 'info':
					if(!ac_checkPageAccess('page.requests.info', $template_name)) break;
					$template_name = 'requests_info.php';
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-requests-info.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
					$ajax->addRequired('/client/js/Admin/requests_info.js','requests_info');
					$admin_request = new Admin_Request();
					$request_info = $admin_request->getRequestsListEx($request->getId('request_id',0),null,true,true);
					if(!empty($request_info)){
						$request_iresources = $admin_request->requestIResourcesEx($request_info['request_id']);
					}else{
						$request_iresources = null;
					}
					$admin_iresource = new Admin_IResource();
					Ajax::_setData(array(
						'request' => $request_info,
						'request_iresources' => $request_iresources,
						'ir_types' => $admin_iresource->getIRTypesList()
					));
				break;


				default: return Page::_httpError(404,'#adminarea');
			}

		break; #Заявки


		#LDAP
		case 'ldap':
			if(empty($route_way[1])) return Page::_httpError(404,'#adminarea');
			switch($route_way[1]){
				case 'users':
					if(!ac_checkPageAccess('page.ldap.users', $template_name)) break;
					$template_name = 'ldap_users.php';
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-ldap-users.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-org-chart.css');
					$ajax->addRequired('/client/js/Admin/ldap_users.js','ldap_users');
					$admin_ldap = new Admin_LDAPUsers();
					$organization = new Admin_Organization();
					Ajax::_setData(array(
						'companies'	=> $organization->getCompaniesList(array('company_id','full_name')),
						'users'		=> $admin_ldap->undefinedUsers()
					));
				break;

				default: return Page::_httpError(404,'#adminarea');
			}
		break;



		#О программе
		case 'about':
			$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-admin-about.css');
			$ajax->addRequired('/client/js/Admin/admin_about.js','admin_about');
			$template_name = 'about.php';
		break; #О программе


		default:
			return Page::_httpError(404,'#adminarea');
	}


	#Если задан шаблон - возвращаем его
	if(!empty($template_name)){
		$template->setTemplate('Admin/templates/'.$template_name);
		$ajax->addContent('#adminarea',$template->display(true),'set');
	}

	Ajax::_commit();
	return true;
}#end function




/*
 * Построение основного меню для пользователя
 */
function ac_getAdminTopMenu(){

	$menu=array();
	$menu[] = array('id'=> 1, 'name'=> 'Вернуться', 'link'=>Request::_get('scheme').'://'.$_SERVER['HTTP_HOST'].'/main/index', 'class'=> 'icon_home', 'section' => 0);
	$menu[] = array('id'=> 9, 'name'=> 'Выход', 'link'=>'/logout', 'class'=> 'icon_logout', 'section' => 0);

	Ajax::_setStack('menu',$menu);
}#end function




/*
 * Ошибка доступа
 */
function ac_checkPageAccess($object_name, &$template_name){
	if(!UserAccess::_checkAccess($object_name)){
		Ajax::_addMessage('Ошибка доступа','Недостаточно прав для просмотра этой страницы: '.$object_name, 'error');
		Ajax::_addRequired('/client/themes/'.User::_getTheme().'/css/ui-admin-main.css');
		Ajax::_addRequired('/client/js/Admin/admin_main.js','admin_main');
		Ajax::_addRequired('/client/themes/'.User::_getTheme().'/css/ui-jstable.css');
		$template_name = 'main.php';
		$admin_request = new Admin_Request();
		Ajax::_setData(array(
			'requests' => $admin_request->getRequestsListEx(null,null,false,true,9),
			'stats' => ac_statistics()
		));
		return false;
	}
	return true;
}#end function



/*
 * Статистика системы заявок
 */
function ac_statistics(){

	$db =  Database::getInstance('main');
	return array(
		'employers_total'			=> $db->result('SELECT count(*) FROM `employers`'),
		'iresources_total'			=> $db->result('SELECT count(*) FROM `iresources`'),
		'groups_total'				=> $db->result('SELECT count(*) FROM `groups`'),
		'routes_total'				=> $db->result('SELECT count(*) FROM `routes`'),
		'templates_total'			=> $db->result('SELECT count(*) FROM `templates`'),
		'companies_total'			=> $db->result('SELECT count(*) FROM `companies`'),
		'requests_total'			=> $db->result('SELECT count(*) FROM `requests`'),
		'request_iresources_total'	=> $db->result('SELECT count(*) FROM `request_iresources`'),
		'request_iresources_hist_total'	=> $db->result('SELECT count(*) FROM `request_iresources_hist`')
	);

}#end function

?>
