<?php
/*==================================================================================================
Описание: Контроллер страниц модуля Main
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');



/*
 * Контроллер модуля MAIN
 */
function mainController($data){

	$user = $data['user'];
	$template = $data['template'];
	$request = $data['request'];
	$ajax = Ajax::getInstance();
	$is_ajax = $request->get('ajax', false);
	$is_custom = $request->getBool('custom', false);
	$is_post = ($request->get('method', 'GET') == 'POST' ? true : false);
	$is_auth_user = $user->checkAuthStatus();
	$user_language = $user->getLanguage();
	$route_way = $request->get('way', false);
	$page = $request->get('page', false);
	$action = $request->get('action', false);
	$module = $request->get('module', false);
	$nocert = ($request->getStr('logintype', '') == 'login' ? true : false);
	$is_login_page = ($page == 'login' ? true : false);
	$is_auth_from_cert = ($request->hasValidClientCert() && Config::getOption('general','user_cert_login',false) && !$nocert);
	$ignore_auth_error = false;
	$client_success_auth_redirect = false;

	#Если пользователь не аутентифицирован
	if(!$is_auth_user){

		//error_log('session: '.print_r($_SESSION,true)."\n",3,DIR_ROOT.'/log.txt');

		$result = array();

		#Запрошена аутентификация через LOGIN/CERT
		if($is_auth_from_cert || $is_post && ($action == 'login' || $is_login_page)){

			#Аутентификация без сертификата
			if(!$is_auth_from_cert){
				if($is_post && ($action == 'login' || $is_login_page)){
					$username = trim($request->getStr('username', false,'p'));
					$password = $request->getStr('password',false,'p');
					$remember = $request->getInt('remember', 0,'p');
					$result = $user->auth($username, $password, $remember);
				}
			}
			#Аутентификация через сертификат
			else{
				$pin = trim($request->getStr('pin', '','p'));
				$result = $user->x509auth($pin);
				if(!$is_post){
					if(empty($result['result'])){
						$result = array();
						$ignore_auth_error=true;
					}else{
						$_POST['page'] = ($is_login_page ? '/main/index' : $_SERVER['REQUEST_URI']);
						$client_success_auth_redirect = true;
					}
				}
			}

			#Аутентификация прошла успешно
			if(!empty($result['result'])){
				$to_page = trim(rawurldecode($request->getStr('page', '','p')));
				if(empty($to_page) || strlen($to_page)<3 || !preg_match('/^[a-zA-Z0-9\=\?\%\/\-\_\@\!\&\.\,\:\\\;\{\}\[\]\#]+$/',$to_page)) $to_page = '/main/index';
				if($client_success_auth_redirect){
					if($is_ajax){
						$ajax->setLocation($to_page);
					}else{
						echo
							'<html><head><script type="text/javascript">function ffgo(){document.location.href="'.addslashes($to_page).'";return true;}</script>'.
							'</head><body Onload="ffgo();"><form name="one" id="one" method="get" action="'.$to_page.'"></form></body></html>';
					}
					return true;
				}
				return Page::_doLocation($to_page);
			}else{
				#Через AJAX
				if($is_ajax && !$ignore_auth_error){
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-login.css');
					$ajax->responseError(Language::get('Main/user','auth/errors/auth_error'),(isset($result['desc']) ? $result['desc'] : Language::get('general','errors/service_unavailable')));
					return true;
				}
			}

		}#Запрошена аутентификация через LOGIN/CERT

		#Страница LOGIN
		if($is_login_page){
			$template->assign(array(
				'error'	=> isset($result['desc']) ? '<div class="login_error"><h3>'.$result['desc'].'</h3></div>' : ''
			));
			if($is_auth_from_cert){
				$template->assign(array(
					'SSL_CLIENT_S_DN_L' => $_SERVER['SSL_CLIENT_S_DN_L'],
					'SSL_CLIENT_S_DN_O' => $_SERVER['SSL_CLIENT_S_DN_O'],
					'SSL_CLIENT_S_DN_OU' => $_SERVER['SSL_CLIENT_S_DN_OU'],
					'SSL_CLIENT_S_DN_CN' => $_SERVER['SSL_CLIENT_S_DN_CN'],
				));
				$template->setTemplate('Main/templates/login_cert.tpl');
			}else{
				$template->setTemplate('Main/templates/login.tpl');
			}
			$template->display();
			return true;
		}
		
		/*
		#Обработка запросов на просмотр страниц не аутентифицированным пользователям
		#может быть размещена здесь
		switch($page){
			
		}
		*/


		#Прочие страницы
		return Page::_doLocation('/login'.(strlen($_SERVER['REQUEST_URI']) <3?'':'?page='.rawurlencode($_SERVER['REQUEST_URI'])));
	}#Если пользователь не аутентифицирован

	
	#Неизвестно что запрошено - 404
	if(empty($route_way)) return Page::_httpError(404);


	#Запрошен произвольный контент
	if($is_custom || $route_way[0]=='customcontent'){

		switch($route_way[0]){
			#Прочий произвольный контент
			case 'customcontent':
				if(empty($route_way[1])) return true;

				switch($route_way[1]){

					#Просмотр скриншотов объектов доступа
					case 'irolescreenshot':
						require_once(DIR_MODULES.'/Main/custom/screenshot_preview_irole.php');
					break;

					#Отчеты
					case 'reports':
						require_once(DIR_MODULES.'/Main/custom/reports.php');
					break;

				}

			break;

		}

		return true;
	}#Запрошен произвольный контент



	#Если запрос не по AJAX - возвращаем контент index.tpl
	#и далее через AJAX делается запрос интересуемой страницы
	if(!$is_ajax){

		if($route_way[0] == 'logout') return Page::_doLogout('/main/login');
		$request->request['get']['init']=1;
		$template->setTemplate('Main/templates/index.tpl');
		$template->display();
		return true;
	}

	$template_name = null;

	$main_employer = new Main_Employer();

	#Если происходит инициализация приложения
	if($request->getInt('init',0) == 1){

		#Построение основного меню для пользователя
		mc_getUserMainMenu($user, $main_employer);

		$emp_info = $main_employer->getInfo(null, array('change_password','email','phone'), true);

		#Проверка смены пароля
		if($emp_info['change_password'] != 1 && !$is_auth_from_cert){
			$ajax->setStack('password','change');
		}

		#Проверка введенных данных
		if(empty($emp_info['email'])||empty($emp_info['phone'])){
			$ajax->setStack('contacts','change');
		}

	}#происходит инициализация приложения


	#Обработка AJAX запроса
	switch($route_way[0]){

		#Main страница
		case 'index':
		case 'main':
		case 'login':
			$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-main.css');
			$ajax->addRequired('/client/js/Main/main.js','main_enter_page');
			$template_name = 'main.php';
			$can_add_copanies = $main_employer->canAddEmployerCompanies(null);
			$can_curator_copanies = $main_employer->canCuratorCompanies(null);
			$ir_owner = $main_employer->getEmployerIROwner();
			$template->assign('can_add_employers', (!empty($can_add_copanies) ? true : false));
			$template->assign('can_curator', (!empty($can_curator_copanies) ? true : false));
			$template->assign('is_ir_owner', (!empty($ir_owner) ? true : false));
			$ajax->setData(array(
				'requests'=>$main_employer->getAllActiveRequests(true)
			));
		break;

		#Logout страница
		case 'logout':
			return Page::_doLogout('/main/login');
		break;

		#AJAX операции
		case 'ajax':
			if(empty($route_way[1])) return Page::_httpError(404);
			
			switch($route_way[1]){

				case 'employer':
					require_once(DIR_MODULES.'/Main/ajax/employer.ajax.php');
				break;

				case 'request':
					require_once(DIR_MODULES.'/Main/ajax/request.ajax.php');
				break;

				case 'gatekeeper':
					require_once(DIR_MODULES.'/Main/ajax/gatekeeper.ajax.php');
				break;

				case 'irowner':
					require_once(DIR_MODULES.'/Main/ajax/irowner.ajax.php');
				break;

				default: return Page::_httpError(404);
			}

		break;



		#История входов
		case 'accesslog':
			$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
			$ajax->addRequired('/client/js/Main/accesslog.js','accesslog_enter_page');
			$template_name = 'accesslog.php';
			$ajax->setData($main_employer->dbEmployerAccessLog());
		break;




		#Профайл
		case 'profile':
			$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
			$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-profile.css');
			$ajax->addRequired('/client/js/Main/profile.js','profile_enter_page');
			$template_name = 'profile.php';
			$ajax->setData(array(
				'info'	=> $main_employer->dbEmployerInfo(null, true, array('employer_id','username','first_name','middle_name','last_name','birth_date','phone','email','notice_me_requests','notice_curator_requests','notice_gkemail_1','notice_gkemail_2','notice_gkemail_3','notice_gkemail_4')),
				'posts'	=> $main_employer->dbEmployerPostsDetails(),
			));
		break;



		#Раздел для владельцев информационных ресурсов
		case 'iresources':
			if(empty($route_way[1])) return Page::_httpError(404);

			switch($route_way[1]){

				case 'owner':
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-iresources-owner.css');
					$ajax->addRequired('/client/js/Main/iresources_owner.js','iresources_owner');
					$template_name = 'iresources_owner.php';
					$ir_owner = $main_employer->getEmployerIROwner();
					if(empty($ir_owner)) break;
					$admin_iresources = new Admin_IResource();
					$ajax->setData(array(
						'ir_types'	=> Database::getInstance('main')->select('SELECT * FROM `ir_types`'),
						'iresources'=> $admin_iresources->getIResourcesList(array('iresource_id'=>$ir_owner),array('iresource_id','full_name'))
					));
				break;

				default: return Page::_httpError(404);
			}
		break;



		#Страницы заявок
		case 'requests':
			if(empty($route_way[1])) return Page::_httpError(404);

			switch($route_way[1]){

				//Новая заявка
				case 'new':
					if(($results = $main_employer->dbEmployerPostsDetails()) === false){
						$ajax->responseError('Ошибка выполнения','Ошибка во время получения информации о должностях'); break;
					}
					$ajax->setData($results);
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-request-new.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
					$ajax->addRequired('/client/js/Main/request_new.js','request_new_enter_page');
					$template_name = 'request_new.php';
				break;

				//Инфо заявки
				case 'info':
					$request_id = $request->getId('request_id', 0);
					if(!$request_id){$ajax->responseError('Ошибка выполнения','Некорректно задан номер заявки'); break;}

					$main_request = new Main_Request();

					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-request-info.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
					$ajax->addRequired('/client/js/Main/request_info.js','request_info_enter_page');
					$template_name = 'request_info.php';

					if(!$main_request->dbIsEmployerRequest($user->getEmployerID(), $request_id)) break;
					$main_request->open(array(
						'request_id' =>$request_id, 
						'fullinfo' => true
					));
					$ajax->setData(array(
						'ir_types'	=> Database::getInstance('main')->select('SELECT * FROM `ir_types`'),
						'iresources'=> $main_request->cache['iresources'],
						'roles'		=> $main_request->cache['roles'],
						'info'		=> $main_request->cache['info'],
						'steps'		=> $main_request->cache['steps']
					));
				break;

				//История заявок
				case 'history':
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-request-history.css');
					$ajax->addRequired('/client/js/Main/request_history.js','request_history_enter_page');
					$template_name = 'request_history.php';
					$ajax->setData(array(
						'active' => $main_employer->dbEmployerRequests(null, 1),
						'complete' => $main_employer->dbEmployerRequests(null, 100),
						'cancel' => $main_employer->dbEmployerRequests(null, 0),
						'hold' => $main_employer->dbEmployerRequests(null, 2)
					));
				break;

				//Просмотр заявок
				case 'view':
					$request_id = $request->getId('request_id', 0);
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
					//Общий список заявок
					if(!$request_id){
						$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-request-view-list.css');
						$ajax->addRequired('/client/js/Main/request_view_list.js','request_view_list_enter_page');
						$template_name = 'request_view_list.php';
						$ajax->setData(array(
							//Запрос списка идет с клиента
							'filter' => array(
								'date_from'		=> date('d.m.Y',time()-604800),
								'date_to'		=> date('d.m.Y',time()),
								'is_watched'	=> 0,
								'is_owner'		=> 1,
								'is_curator'	=> 1,
								'is_gatekeeper'	=> 1,
								'is_performer'	=> 1,
								'is_watcher'	=> 1
							)
						));
						break;
					}

					$template_name = 'request_view.php';
					$ajax->addRequired('/client/js/Main/request_view.js','request_view_enter_page');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-request-view.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
					$iresource_id = $request->getInt('iresource_id', 0);
					if(empty($iresource_id)){$ajax->responseError('Ошибка выполнения','Некорректно задан идентификатор информационного ресурса'); break;}

					$watcher_request = $main_employer->dbEmployerWatchedRequests(
						null, 
						array(
							'request_id'=>$request_id,
							'iresource_id'=>array(0,$iresource_id)
						),
						null,
						null,
						null,
						1
					);
					if(empty($watcher_request)){/*$ajax->responseError('Ошибка выполнения','Вы не можете просматривать выбранную заявку');*/ break;}
					$main_employer->dbEmployerRequestSetAsWatched(null, $request_id, $iresource_id);
					$main_request = new Main_Request();
					$main_request->open(array(
						'request_id' =>$request_id, 
						'fullinfo' => true,
						'iresource_id'=>$iresource_id,
						'alliroles'=>true,
						'onlychangedroles'=>true
					));

					$ajax->setData(array(
						'ir_types'	=> Database::getInstance('main')->select('SELECT * FROM `ir_types`'),
						'iresource'	=> !empty($main_request->cache['iresources'][$iresource_id]) ? $main_request->cache['iresources'][$iresource_id] : null,
						'roles'		=> !empty($main_request->cache['roles'][$iresource_id]) ? $main_request->cache['roles'][$iresource_id] : null,
						'steps'		=> !empty($main_request->cache['steps'][$iresource_id]) ? $main_request->cache['steps'][$iresource_id] : null,
						'info'		=> array_merge($watcher_request, $main_request->cache['info'])
					));

				break;


				#Текущие права доступа сотрудника
				case 'complete':
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-request-complete.css');
					$ajax->addRequired('/client/js/Main/request_complete.js','request_complete');
					$template_name = 'request_complete.php';
					$admin_matrix = new Admin_Matrix();
					$ajax->setData(array(
						'iresources'=> $admin_matrix->employerCompleteIResources(User::_getEmployerId()),
						'ir_types'	=> Database::getInstance('main')->select('SELECT * FROM `ir_types`')
					));
				break;


				default: return Page::_httpError(404);
			}

		break;#Страницы заявок


		#Страницы гейткипера
		case 'gatekeeper':
			if(empty($route_way[1])) return Page::_httpError(404);

			switch($route_way[1]){
				//Список заявок
				case 'requestlist':
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-gatekeeper-requestlist.css');
					$ajax->addRequired('/client/js/Main/gatekeeper_requestlist.js','gatekeeper_requestlist_enter_page');
					$ajax->setData($main_employer->getAllActiveRequests());
					$template_name = 'gatekeeper_requestlist.php';
				break;

				//Согласование/утверждение/исполнение заявки
				case 'requestinfo':
					$template_name = 'gatekeeper_requestinfo.php';
					$ajax->addRequired('/client/js/Main/gatekeeper_requestinfo.js','gk_requestinfo_enter_page');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-gatekeeper-requestinfo.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');

					$request_id = $request->getInt('request_id', 0);
					if(!$request_id){$ajax->responseError('Ошибка выполнения','Некорректно задан номер заявки'); break;}
					$iresource_id = $request->getInt('iresource_id', 0);
					if(!$iresource_id){$ajax->responseError('Ошибка выполнения','Некорректно задан идентификатор информационного ресурса'); break;}

					$gk_request = $main_employer->getActiveRequests(array(
						'roles'			=> array(1,2,3),
						'request_id'	=> array($request_id),
						'iresource_id'	=> array($iresource_id),
						'single'		=> true
					));

					if(empty($gk_request)){/*$ajax->responseError('Ошибка выполнения','Вы не можете согласовывать или утверждать выбранную заявку');*/ break;}

					$main_request = new Main_Request();
					$main_request->open(array(
						'request_id' =>$request_id, 
						'fullinfo' => true,
						'iresource_id'=>$iresource_id,
						'alliroles'=>true,
						'onlychangedroles'=> (($gk_request['request_type']==3 || in_array($gk_request['gatekeeper_role'],array(3,4))) ? true : false)
					));
					$gk_request['gatekeeper_role_name'] = $main_request->getGatekeeperRoleString($gk_request['gatekeeper_role']);
					$ajax->setData(array(
						'ir_types'	=> Database::getInstance('main')->select('SELECT * FROM `ir_types`'),
						'iresource'	=> $main_request->cache['iresources'][$iresource_id],
						'roles'		=> $main_request->cache['roles'][$iresource_id],
						'info'		=> array_merge($gk_request, $main_request->cache['info'])
					));

				break;


				default: return Page::_httpError(404);
			}

		break;#Страницы гейткипера


		#Страница выбора заместителей
		case 'assistants':
			$template_name = 'assistants.php';
			$ajax->addRequired('/client/js/Main/assistants.js','assistants_enter_page');
			$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-assistants.css');
			$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
			$delegates	= $main_employer->dbEmployerDelegates(null, true, false, null, false);
			$assistants	= $main_employer->dbEmployerAssistants(null, true, false, null, false); 
			foreach($delegates as $id=>$record) $delegates[$id]['posts'] = $main_employer->dbEmployerPosts($record['employer_id'], true, array('post_name','company_name'));
			foreach($assistants as $id=>$record) $assistants[$id]['posts'] = $main_employer->dbEmployerPosts($record['employer_id'], true, array('post_name','company_name'));

			$ajax->setData(array(
				'delegates'	=> $delegates, 
				'assistants'=> $assistants
			));
		break;



		#Куратор
		case 'curator':

			if(empty($route_way[1])) return Page::_httpError(404);

			switch($route_way[1]){

				//Анкета нового сотрудника
				case 'employer':
					$template_name = 'curator_employer.php';
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-curator-employer.css');
					$ajax->addRequired('/client/js/Main/curator_employer.js','curator_employer_enter_page');
					$companies = $main_employer->canAddEmployerCompanies(null, true);
					if(empty($companies)) break;
					$ajax->setData(array(
						'companies' => $companies
					));
				break;


				//ЗАпросить доступ для сотрудника
				case 'request':
					$template_name = 'curator_request.php';
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-curator-request.css');
					$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/ui-jstable.css');
					$ajax->addRequired('/client/js/Main/curator_request.js','curator_request');
					$companies = $main_employer->canCuratorCompanies(null, true);
					if(empty($companies)) break;
					$ajax->setData(array(
						'companies' => $companies
					));
				break;


				default: return Page::_httpError(404);
			}

		break;




		#test
		case 'test':
			$template_name = 'index.tpl';
			$ajax->setData(array(1,2,3,4,5));
			$ajax->setCallback('test_callback_local');
			$ajax->addRequired('/client/themes/'.$user->getTheme().'/css/main.css');
			$ajax->setTitle('Title from AJAX');
			$ajax->addContent('body','<h1>THIS HTML CONTENT FROM AJAX RESPONSE :)</h1><br><div id="id_test"></div>','set');
			$ajax->addContent('#id_test','This in DIV flom ajax request ID: '.Request::_getStr('RuId',0));
			$ajax->addContent('#id_test','<br><input type="button" value="Click me again!" onclick="load_test_data();">');
			$ajax->commit();
		break;


		default:
			return Page::_httpError(404);
	}


	#Количество непросмотренных заявок
	$ajax->setStack('unreadrequests', Database::getInstance('main')->result('SELECT count(*) FROM `request_watch` as RW INNER JOIN `request_iresources` as RI ON RI.`request_id` = RW.`request_id` AND (RI.`iresource_id` = RW.`iresource_id` OR RW.`iresource_id`=0)  WHERE RW.`employer_id`='.$user->getEmployerID().' AND RW.`is_watched`=0'));

	#Если задан шаблон - возвращаем его
	if(!empty($template_name)){
		$template->setTemplate('Main/templates/'.$template_name);
		$ajax->addContent('#mainarea',$template->display(true),'set');
	}
	
	$ajax->commit();
	return true;
}#end function







/*
 * Построение основного меню для пользователя
 */
function mc_getUserMainMenu($user, $main_employer){

	$can_add_copanies = $main_employer->canAddEmployerCompanies(null);
	$can_curator_copanies = $main_employer->canCuratorCompanies(null);
	$ir_owner = $main_employer->getEmployerIROwner();
	$is_ir_owner =!empty($ir_owner);

	$menu=array();
	$menu[] = array('id'=> 1, 'name'=> 'Главная', 'link'=>'/main/index', 'class'=> 'icon_home', 'section' => 0);
	if(!empty($can_curator_copanies)){
		$menu[] = array('id'=> 2, 'name'=> 'Новая заявка', 'link'=>'', 'class'=> 'icon_plus', 'section' => 0);
		$menu[] = array('id'=> 201, 'name'=> 'Моя новая заявка', 'link'=>'/main/requests/new', 'class'=> 'empty', 'section' => 2);
		$menu[] = array('id'=> 202, 'name'=> 'Заявка от имени сотрудника', 'link'=>'/main/curator/request', 'class'=> 'empty', 'section' => 2);
	}else{
		$menu[] = array('id'=> 2, 'name'=> 'Новая заявка', 'link'=>'/main/requests/new', 'class'=> 'icon_plus', 'section' => 0);
	}

	if(!empty($can_add_copanies) || !empty($is_ir_owner)){
		$menu[] = array('id'=> 3, 'name'=> 'Действия', 'link'=>'', 'class'=> 'icon_check', 'section' => 0);
		$menu[] = array('id'=> 301, 'name'=> 'Заявки на рассмотрении', 'link'=>'/main/gatekeeper/requestlist', 'class'=> 'empty', 'section' => 3);
		if(!empty($can_add_copanies)){
			$menu[] = array('id'=> 302, 'name'=> 'Заполнить анкету нового сотрудника', 'link'=>'/main/curator/employer', 'class'=> 'empty', 'section' => 3);
		}
		if(!empty($is_ir_owner)){
			$menu[] = array('id'=> 303, 'name'=> 'Мои информационные ресурсы', 'link'=>'/main/iresources/owner', 'class'=> 'empty', 'section' => 3);
		}
	}else{
		$menu[] = array('id'=> 3, 'name'=> 'Действия', 'link'=>'/main/gatekeeper/requestlist', 'class'=> 'icon_check', 'section' => 0);
	}

	$menu[] = array('id'=> 4, 'name'=> 'История', 'link'=>'', 'class'=> 'icon_request', 'section' => 0);
	$menu[] = array('id'=> 5, 'name'=> 'Замещение', 'link'=>'/main/assistants', 'class'=> 'icon_users', 'section' => 0);
	if($user->isAdmin()){
		$menu[] = array('id'=> 7, 'name'=> 'Админ', 'link'=>Request::_get('scheme').'://'.$_SERVER['HTTP_HOST'].'/admin/index', 'class'=> 'icon_settings', 'section' => 0);
	}

	$menu[] = array('id'=> 9, 'name'=> 'Выход', 'link'=>'/logout', 'class'=> 'icon_logout', 'section' => 0);

	$menu[] = array('id'=> 422, 'name'=> 'История моих заявок', 'link'=>'/main/requests/history', 'class'=> 'empty', 'section' => 4);
	$menu[] = array('id'=> 423, 'name'=> 'Заявки с моим участием', 'link'=>'/main/requests/view', 'class'=> 'empty', 'section' => 4);
	$menu[] = array('id'=> 424, 'name'=> 'Мой доступ', 'link'=>'/main/requests/complete', 'class'=> 'empty', 'section' => 4);

	Ajax::_setStack('menu',$menu);


}#end function










?>
