<?php
/*==================================================================================================
Описание: Контроллер страниц
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');


class Page{

	use Trait_SingletonUnique;

	/*==============================================================================================
	Переменные класса
	==============================================================================================*/

	private $user 			= null;
	private $request 		= null;
	private $is_ajax 		= false;
	private $is_custom 		= false;
	private $module_name 	= null;
	private $template		= null;
	private $is_auth_user	= false;
	private $language		= APP_LANGUAGE;
	private $theme			= APP_THEME;



	/*==============================================================================================
	Инициализация
	==============================================================================================*/


	/*
	 * Конструктор класса
	*/
	protected function init(){

		ob_start();

		$this->user			= User::getInstance();
		
		#Если это стандартный или AJAX запрос
		$this->template		= Template::getInstance('main');
		$this->request		= Request::getInstance();
		$this->module_name	= $this->request->get('module', null);
		$this->is_ajax		= $this->request->get('ajax', false);
		$this->is_custom	= $this->request->getBool('custom', false);
		$this->is_auth_user	= $this->user->checkAuthStatus();
		
		#Найдено указание на смену языка интерфейса
		$ch_lang = $this->request->getStr('ch_lang', false);
		if(!empty($ch_lang)) $this->user->setLanguage($ch_lang);


		#Найдено указание на смену темы интерфейса
		$ch_theme = $this->request->getStr('ch_theme', false);
		if(!empty($ch_theme)) $this->user->setTheme($ch_theme);

		
		#Текущий язык интерфейса
		$this->language		= $this->user->getLanguage();

		#Текущая тема интерфейса
		$this->language		= $this->user->getTheme();
		
	}#end function



	/*
	 * Завершение работы класса
	 */
	public function __destruct(){

		Response::_sendHeaders();
		$content = ob_get_contents();
		ob_end_clean();

		if($this->is_ajax && !$this->is_custom){
			if(APP_DEBUG && strlen($content)>0) Ajax::_setDebug($content);
			echo Ajax::_getResponseData();
		}else{
			echo $content;
		}
	}#end function








	/*==============================================================================================
	Построение ответа
	==============================================================================================*/




	/*
	 * Конструктор страницы
	 */
	public function build(){

		#Модуль не задан - выход
		if(empty($this->module_name)) return $this->httpError(404);

		#Получение информации о модуле
		$module = Config::getOption('modules', $this->module_name, false);
		if(empty($module)||!is_array($module)||empty($module['active'])||empty($module['controller'])) return $this->httpError(404);
		if(!is_callable($module['controller'])) return $this->httpError(503);

		#Заголовки - общие
		Response::_add('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); 
		Response::_add('Cache-Control: no-store, no-cache, must-revalidate'); 
		Response::_add('Pragma: no-cache');
		if(!$this->is_custom){
			if($this->is_ajax){
				header('Content-Type: application/json; charset=utf-8');
			}else{
				header('Content-Type: text/html; charset=utf-8');
			}
		}

		#Вызов функции контроллера модуля для обработки запроса
		call_user_func($module['controller'], array(
			'module'	=> $this->module_name,
			'request' 	=> $this->request,
			'template'	=> $this->template,
			'user'		=> $this->user
		));

		return true;
	}#end function




	/*
	 * Выход, завершение сеанса
	 */
	public function doLogout($location='/'){

		Session::_stop();
		User::_deleteAuthCookie();
		$this->doLocation($location);

		return true;
	}#end function




	/*
	 * Ошибка
	 */
	public function httpError($errno=404, $jselement='#mainarea'){

		$errstr = Response::_getStatus($errno);
		
		if($this->is_ajax){
			Ajax::_addRequired('/client/themes/'.User::_getTheme().'/css/ui-error.css');
			Ajax::_addContent($jselement,'<h1 class="errorpage_title">'.$errno.': '.$errstr.'</h1>','set');
			Ajax::_commit();
		}else{
			Response::_status($errno);
			$this->template->setTemplate(Config::getOption('general',array('templates','errors',$errno),'Main/templates/_http_error.tpl'));
			$this->template->assign('errno', $errno);
			$this->template->assign('errstr', $errstr);
			$this->template->display();
		}

		return false;
	}#end function



	/*
	 * Редирект
	 */
	public function doLocation($location='/'){

		if($this->is_ajax){
			Ajax::_setLocation($location);
		}else{
			Response::_location($location);
		}

		return true;
	}#end function





	/*==============================================================================================
	Контроллер модуля MAIN, перенечен в функцию mainController файла mainController.functions.php
	==============================================================================================*/


	/*
	 * Контроллер модуля MAIN
	 *//*
	static public function mainController($data){

		$template = $data['template'];
		$request = $data['request'];
		$is_ajax = $request->get('ajax', false);
		$is_post = ($request->get('method', 'GET') == 'POST' ? true : false);
		$is_auth_user = User::_checkAuthStatus();
		$user_language = User::_getLanguage();
		$dir_array = $request->get('dir', false);

		#Для модуля main директории не поддерживаются, если заданы - 404
		if(!empty($dir_array)) return Page::_httpError(404);

		switch($request->get('page', false)){
			
			#Main страница
			case 'main':
				$template_name = 'index.tpl';
				if($is_auth_user) break;

			#Login страница
			case 'index':
			case 'login':
				if($is_post){
					$username = trim($request->getStr('username', false,'p'));
					$password = $request->getStr('password',false,'p');
					$remember = $request->getInt('remember', 0,'p');
					$result = User::_auth($username, $password, $remember);
					if($result['result']!==false){
						return Page::_doLocation('/main/main');
					}
				}
				$template->assign(array(
					'title' => Language::get('Main/user','auth/title'),
					'form_title'=> Language::get('Main/user','auth/form_title'),
					'username' 	=> Language::get('Main/user','auth/username'),
					'password' 	=> Language::get('Main/user','auth/password'),
					'remember' 	=> Language::get('Main/user','auth/remember'),
					'submit' 	=> Language::get('Main/user','auth/submit'),
					'error'		=> isset($result['desc']) ? $result['desc'] : ''
				));
				$template_name = 'login.tpl';
			break;

			#Logout страница
			case 'logout':
				return Page::_doLogout();
			break;

			#timestamp
			case 'time':
				$template_name = 'index.tpl';
			break;

			#test
			case 'test':
				$template_name = 'index.tpl';
				Ajax::_setData(array(1,2,3,4,5));
				Ajax::_setCallback('test_callback_local');
				Ajax::_addRequired('/client/js/test.js','test_callback');
				Ajax::_addRequired('/client/themes/'.User::_getTheme().'/css/main.css');
				Ajax::_setTitle('Title from AJAX');
				Ajax::_addContent('body','<h1>THIS HTML CONTENT FROM AJAX RESPONSE :)</h1><br><div id="id_test"></div>','set');
				Ajax::_addContent('#id_test','This in DIV flom ajax request ID: '.Request::_getStr('RuId',0));
				Ajax::_addContent('#id_test','<br><input type="button" value="Click me again!" onclick="load_test_data();">');
				Ajax::_commit();
			break;


			default:
				return Page::_httpError(404);
		}


		$template->setTemplate('Main/templates/'.$template_name);
		$template->display();

		return true;
	}#end function
*/


}#end class


?>