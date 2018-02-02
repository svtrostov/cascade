<?php
/*==================================================================================================
Описание: Работа с клиентом
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');




class User{

	use Trait_SingletonUnique;

	/*==============================================================================================
	Переменные класса
	==============================================================================================*/

	#db
	public $db = null;

	#Идентификатор клиента
	public $employer_id = 0;

	#Признак того, что статус клиента был проверен
	public $auth_status_checked = false;

	#Информация о клиента
	public $info = null;

	#Названия COOKIEs
	public $session_name = null;
	public $cookie_auth = null;
	public $cookie_lang = null;
	public $cookie_theme = null;
	
	#Язык интерфейса пользователя
	public $employer_language=false;

	#Тема интерфейса пользователя
	public $employer_theme=false;


	/*==============================================================================================
	Инициализация
	==============================================================================================*/


	/*
	 * Конструктор класса
	 */
	protected function init(){
		$this->db = Database::getInstance('main');
		$this->session_name	= strtoupper(Config::getOption('general','session_name','CASCAD'));
		$this->cookie_auth	= $this->session_name.'_'.strtoupper(Config::getOption('general','user_cookie_auth_info','UAUTH'));
		$this->cookie_lang	= $this->session_name.'_'.strtoupper(Config::getOption('general','user_cookie_language','LANG'));
		$this->cookie_theme	= $this->session_name.'_'.strtoupper(Config::getOption('general','user_cookie_theme','THEME'));
	}#end function



	/*
	 * Вызов недоступных методов
	 */
	public function __call($name, $args){
		return false;
	}#end function


	public function __destruct(){
		if($this->auth_status_checked && !$this->employer_id) Session::_stop();
	}#end function


	/*==============================================================================================
	Информация
	==============================================================================================*/


	/*
	 * Получение записи о клиенте из базы данных
	 */
	public function getEmployerInfo($read_from_db=false){
		if(empty($this->employer_id)){
			if(!$this->checkAuthStatus()) return false;
		}
		if(!$read_from_db&&!empty($this->info))return $this->info;
		$this->db->prepare('SELECT * FROM `employers` WHERE `employer_id`=? LIMIT 1');
		$this->db->bind($this->employer_id);
		if(($this->info = $this->db->selectRecord()) === false) return false;
		return $this->info;
	}#end function



	/*
	 * Обновление записи о клиенте в базе данных
	 */
	public function dbUpdate($key=null,$value=null){
		if(empty($key))return false;
		if(!is_array($key)){
			$this->db->prepare('UPDATE `employers` SET `?`=? WHERE `employer_id`=?');
			$this->db->bindField($key);
			$this->db->bindText($value);
			$this->db->bindNum($this->employer_id);
			if($this->db->update() === false) return false;
		}
		return true;
	}#end function




	/*
	 * Получение информации о клиенте
	 */
	public function get($key, $default=null){
		if(empty($this->info)) $this->getEmployerInfo();
		if(!isset($this->info[$key])) return $default;
		return $this->info[$key];
	}#end function



	/*
	 * Возвращает ID клиента
	 */
	public function getEmployerID(){
		if(empty($this->employer_id)) $this->checkAuthStatus();
		return $this->employer_id;
	}#end function



	/*
	 * Возвращает уровень доступа клиента как администратора
	 */
	public function getAccessLevel(){
		return $this->get('access_level',0);
	}#end function



	/*
	 * Возвращает TRUE если клиент имеет какие-либо привелегии администратора
	 */
	public function isAdmin(){
		return (intval($this->get('access_level',0)) > 0);
	}#end function









	/*==============================================================================================
	Аутентификация
	==============================================================================================*/



	/*
	 * Проверяет статус аутентификации клиента
	 */
	public function checkAuthStatus(){

		if($this->auth_status_checked) return (!empty($this->employer_id) ? true : false);
		$this->auth_status_checked = true;

		$session_id = Request::_getGPC($this->session_name, false, 'c');

		#Проверка существования COOKIE с идентификатором сессии
		if($session_id !== false){

			#Если сессии хранятся в XCACHE
			if(Session::_xcacheSession()){
				#проверяем существование сессии в XCACHE
				if(Session::_xexists($session_id)){
					#Проверка сессии
					if(Session::_badSession(array('session_name'=>$this->session_name,'employer_id'=>null,'session_uid'=>null))===false){
						return $this->startUserSession(null);
					}
				}
			}else{
				#Проверка сессии
				if(Session::_badSession(array('session_name'=>$this->session_name,'employer_id'=>null,'session_uid'=>null))===false){
					return $this->startUserSession(null);
				}
			}
		}

		#попытка проведения автологина - cookie
		if(Config::getOption('general','user_cookie_login',false)){
			return $this->authFromCookie();
		}

		return false;
	}#end function




	/*
	 * Аутентификация через COOKIE
	 */
	public function authFromCookie(){

		$cookie = Request::_getCookie($this->cookie_auth, false);
		if(empty($cookie)) return false;

		list($employer_id, $employer_name, $employer_pwd_hash, $employer_remember) = explode('/', $cookie);
		$employer_id = abs(intval($employer_id));
		$employer_name = strval($employer_name);
		$employer_pwd_hash = strval($employer_pwd_hash);
		$employer_remember = ($employer_remember == 1 ? 1 : 0);
		
		if(empty($employer_id)||empty($employer_name)||empty($employer_pwd_hash))return false;
		
		$this->db->prepare('SELECT * FROM `employers` WHERE `employer_id`=? AND `username` LIKE ? LIMIT 1');
		$this->db->bind($employer_id);
		$this->db->bind($employer_name);

		if(($info = $this->db->selectRecord()) === false) return false;
		if(empty($info)) return false;

		if(strcasecmp($info['password'], $employer_pwd_hash) != 0){
			if(strcasecmp($this->getHash($info['password']), $employer_pwd_hash) !== 0) return false;
		}
		$info['remember_me'] = $employer_remember;
		$info['password']	 = $employer_pwd_hash;

		#Логирование входа
		if(($info['session_uid'] = $this->authLog('cookie',$employer_id))===false) return false;

		return $this->startUserSession($info);
	}#end function




	/*
	 * Запись в COOKIE информации для автологина
	 */
	public function setAuthCookie($employer_remember=0){

		if(!empty($employer_remember)){
			$expire_time = time() + 31536000;
			$employer_remember = 1;
		}else{
			$expire_time = 1;
			$employer_remember = 0;
		}
		$employer_pwd_hash = $this->getHash($this->info['password']);
		$cookie = $this->info['employer_id'].'/'.$this->info['username'].'/'.$employer_pwd_hash.'/'.$employer_remember;
		Response::_addCookie($this->cookie_auth, $cookie, $expire_time);
		Response::_addCookie($this->cookie_lang, $this->getLanguage(), time() + 31536000);
		Response::_addCookie($this->cookie_theme, $this->getTheme(), time() + 31536000);

	}#end function



	/*
	 * Удаление COOKIE информации для автологина
	 */
	public function deleteAuthCookie(){
		$this->setAuthCookie(0);
		Response::_addCookie($this->session_name,'',1);
	}#end function



	/*
	 * Начало сессии для аутентифицированного клиента,
	 * если передан массив $user, то производится запись в сессию, в противном случае - чтение
	 */
	private function startUserSession($user=null){
		
		#Передан массив для записи в сессию - новая сессия
		if(!empty($user)&&is_array($user)){

			$this->info 		= $user;
			$this->employer_id 	= intval($user['employer_id']);

			#Запись в сессию информации о клиенте
			#Пишется целиком все поля таблицы users
			Session::_set(array(
				'session_name'	=> $this->session_name,
				'session_uid'	=> $user['session_uid'],
				'employer_id'	=> $user['employer_id'],
				'employer_name'	=> $user['search_name'],
				'language'		=> $user['language'],
				'theme'			=> $user['theme']
			));

			#Если сессии хранятся в XCACHE
			#Удаляем предыдущую сессию, если она есть
			if(Session::_xcacheSession()){
				$session_id = Session::_getSessionID();
				$var_uid = 'sess/'.$this->session_name.'/'.$this->employer_id.'/last_uid';
				if(xcache_isset($var_uid)){
					$last_uid = xcache_get($var_uid);
					if(strcmp($session_id,$last_uid)!=0){
						#Если записи о предыдущих сессиях найдены - удаляем их
						if(xcache_isset('sess/'.$this->session_name.'/'.$last_uid)){
							
							xcache_unset('sess/'.$this->session_name.'/'.$last_uid);
						}
					}
				}
				xcache_set($var_uid, Session::_getSessionID());
			}
			
		}else{
			$this->employer_id 	= intval(Session::_get('employer_id'));
			$this->getEmployerInfo();
		}

		if(!isset($user['acl_update'])) return false;
		UserAccess::_setUserAccessActual($this->employer_id, false);
		if(!empty($user['acl_update'])){
			$this->dbUpdate('acl_update', 0);
		}

		return true;
	}#end function



	/*
	 * Аутентификация клиента
	 */
	public function auth($username='', $password='', $employer_remember=0){

		if(empty($username)||empty($password))return array('result'=>false,'desc'=>Language::get('Main/user','auth/errors/no_username'));

		$pwd_hash = sha1($password);

		$this->db->prepare('SELECT * FROM `employers` WHERE `username`=? AND `password` IN (?,?) LIMIT 1');
		$this->db->bind($username);
		$this->db->bind($password);
		$this->db->bind($pwd_hash);
		
		#Ошибка получения данных из БД
		if(($info = $this->db->selectRecord()) === false) return array('result'=>false,'desc'=>Language::get('general','errors/service_unavailable'));
		
		#Неправильный логин/пароль
		if(empty($info)) return array('result'=>false,'desc'=>Language::get('Main/user','auth/errors/incorrect_login'));
		
		#Заблокирован
		if(empty($info['status'])) return array('result'=>false,'desc'=>Language::get('Main/user','auth/errors/account_locked'));

		#Логирование входа
		if(($info['session_uid'] = $this->authLog('login',$info['employer_id']))===false) return array('result'=>false,'desc'=>Language::get('general','errors/service_unavailable'));

		$info['password']	= $pwd_hash;
		$info['remember_me'] = $employer_remember;

		$this->startUserSession($info);
		$this->setAuthCookie($employer_remember);

		return array('result'=>true,'desc'=>null);
	}#end function




	/*
	 * Аутентификация клиента через Сертификат
	 */
	public function x509auth($pin=''){

		if(empty($pin)) $pin = trim(Request::_getStr('pin',''));
		if(!Request::_hasValidClientCert()) return array('result'=>false,'desc'=>Language::get('Main/user','auth/errors/no_validcert'));
		$ssl_serial_num		= hexdec(trim($_SERVER['SSL_CLIENT_M_SERIAL']));
		$pin_code_hash		= sha1($pin);
		$client_cert_hash	= sha1(trim($_SERVER['SSL_CLIENT_CERT']));
		$require_pin		= Config::getOption('general','user_cert_require_pin', true);

		//error_log(date('Y-m-d H:i:s')."\tx509auth:\n".print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),true)."\n----------------------------------------------------\n\n",3,DIR_ROOT.'/log.txt');

		/*
		$this->db->prepare('INSERT INTO `employer_certs` (`employer_id`,`is_lock`,`SSL_CERT_HASH`,`SSL_CLIENT_M_SERIAL`,`SSL_CLIENT_S_DN_L`,`SSL_CLIENT_S_DN_O`,`SSL_CLIENT_S_DN_OU`,`SSL_CLIENT_S_DN_CN`,`SSL_CLIENT_CERT`) VALUES(?,?,?,?,?,?,?,?,?)');
		$this->db->bind(1);
		$this->db->bind(0);
		$this->db->bind($client_cert_hash);
		$this->db->bind($ssl_serial_num);
		$this->db->bind($_SERVER['SSL_CLIENT_S_DN_L']);
		$this->db->bind($_SERVER['SSL_CLIENT_S_DN_O']);
		$this->db->bind($_SERVER['SSL_CLIENT_S_DN_OU']);
		$this->db->bind($_SERVER['SSL_CLIENT_S_DN_CN']);
		$this->db->bind($_SERVER['SSL_CLIENT_CERT']);
		$this->db->insert();
		*/
		/*
		$this->db->prepare('SELECT * FROM `employer_certs` WHERE `SSL_CLIENT_M_SERIAL`=? AND `SSL_CERT_HASH`=? LIMIT 1');
		$this->db->bind($ssl_serial_num);
		$this->db->bind($client_cert_hash);
		*/

		$this->db->prepare('SELECT * FROM `employer_certs` WHERE `SSL_CLIENT_M_SERIAL`=? LIMIT 1');
		$this->db->bind($ssl_serial_num);
		//$this->db->bind($client_cert_hash);

		if(($cert_info = $this->db->selectRecord()) === false) return array('result'=>false,'desc'=>Language::get('general','errors/service_unavailable'));
		if(empty($cert_info)) return array('result'=>false,'desc'=>Language::get('Main/user','auth/errors/no_cert'));

		#Сертификат заблокирован
		if(!empty($cert_info['is_lock'])) return array('result'=>false,'desc'=>Language::get('Main/user','auth/errors/cert_locked'));

		$this->db->prepare('SELECT * FROM `employers` WHERE `employer_id`=? LIMIT 1');
		$this->db->bind($cert_info['employer_id']);
		if(($info = $this->db->selectRecord()) === false) return array('result'=>false,'desc'=>Language::get('general','errors/service_unavailable'));

		#Заблокирован
		if(empty($info['status'])) return array('result'=>false,'desc'=>Language::get('Main/user','auth/errors/account_locked'));

		$pin_fails_count = $info['pin_fails_count'];
		$fake_pin = false;

		if($require_pin && empty($info['ignore_pin']) && empty($pin))return array('result'=>false,'desc'=>Language::get('Main/user','auth/errors/no_pincode'));

		#Проверка PIN кода
		if($require_pin && empty($info['ignore_pin']) && strcmp($pin_code_hash, $info['pin_code'])!=0 && strcmp($pin, $info['pin_code'])!=0){
			$pin_fails_count++;
			$fake_pin = true;
		}else{
			$pin_fails_count = 0;
		}

		if($pin_fails_count != $info['pin_fails_count']){
			$this->db->prepare('UPDATE `employers` SET `pin_fails_count`=? WHERE `employer_id`=? LIMIT 1');
			$this->db->bind($pin_fails_count);
			$this->db->bind($info['employer_id']);
			if($this->db->update() === false) return array('result'=>false,'desc'=>Language::get('general','errors/service_unavailable'));
		}

		if($pin_fails_count >= Config::getOption('general','user_cert_pin_fails', 5)){
			$this->db->prepare('UPDATE `employers` SET `status`=0,`pin_fails_count`=0 WHERE `employer_id`=? LIMIT 1');
			$this->db->bind($info['employer_id']);
			if($this->db->update() === false) return array('result'=>false,'desc'=>Language::get('general','errors/service_unavailable'));
			return array('result'=>false,'desc'=>Language::get('Main/user','auth/errors/account_locked'));
		}

		if($fake_pin) return array('result'=>false,'desc'=>Language::get('Main/user','auth/errors/fake_pin'));

		#Логирование входа
		if(($info['session_uid'] = $this->authLog('cert', $info['employer_id']))===false) return array('result'=>false,'desc'=>Language::get('general','errors/service_unavailable'));

		$info['remember_me'] = 0;

		$this->startUserSession($info);
		$this->setAuthCookie(0);

		return array('result'=>true,'desc'=>null);
	}#end function






	/*
	 * Логирование аутентификации клиента
	 */
	private function authLog($auth_type='login', $employer_id=0){

		$this->db->transaction();

		#Логирование в employers
		$this->db->prepare('UPDATE `employers` SET `last_ip_addr`=?,`last_ip_real`=?,`last_login_time`=?,`last_login_type`=? WHERE `employer_id`=?');
		$this->db->bind(Request::_get('ip_addr'));
		$this->db->bind(Request::_get('ip_real'));
		$this->db->bind(date("Y-m-d H:i:s"));
		$this->db->bind($auth_type);
		$this->db->bind($employer_id);
		if($this->db->update() === false){
			$this->db->rollback();
			return false;
		}

		#Логирование в историю
		$this->db->prepare('INSERT INTO `employer_authlog` (`employer_id`,`login_time`,`ip_addr`,`ip_real`,`login_type`) VALUES (?,?,?,?,?)');
		$this->db->bind($employer_id);
		$this->db->bind(date("Y-m-d H:i:s"));
		$this->db->bind(Request::_get('ip_addr'));
		$this->db->bind(Request::_get('ip_real'));
		$this->db->bind($auth_type);
		if(($session_uid = $this->db->insert()) === false){
			$this->db->rollback();
			return false;
		}

		$this->db->commit();

		return $session_uid;
	}#end function





	/*==============================================================================================
	Язык интерфейса
	==============================================================================================*/



	/*
	 * Получение выбранного клиентом языка интерфейса
	 */
	public function getLanguage($employer_language=null){

		if(!empty($this->employer_language)) return $this->employer_language;

		#Поддерживаемые языки
		$languages = Config::getOption('general','languages',array());
		
		if(empty($employer_language)){
			#Если клиент не аутентифицирован, пытаемся получить выбранный язык из COOKIE
			if(!$this->checkAuthStatus()){
				$employer_language = Request::_getCookie($this->cookie_lang, APP_LANGUAGE);
			}
			#Если клиент аутентицирован, берем значение выбранного языка из сессии
			else{
				if( ($employer_language = Session::_get('language')) === false) $employer_language = APP_LANGUAGE;
			}
		}
		
		if(!in_array($employer_language, $languages, true)) return APP_LANGUAGE;
		
		$this->employer_language = $employer_language;
		
		return $employer_language;
	}#end function




	/*
	 * Изменение языка интерфейса
	 */
	public function setLanguage($employer_language=null){

		if(empty($employer_language)) return false;
		
		#Поддерживаемые языки
		$languages = Config::getOption('general','languages',array());
		if(!in_array($employer_language, $languages, true)) return false;
		
		Response::_addCookie($this->cookie_lang, $employer_language, time() + 31536000);
		$this->employer_language = $employer_language;
		
		#Если клиент не аутентифицирован - выход
		if(!$this->checkAuthStatus() || empty($this->employer_id)) return true;
		
		Session::_set('language', $employer_language);
		$this->db->prepare('UPDATE `employers` SET `language`=? WHERE `employer_id`=?');
		$this->db->bind($employer_language);
		$this->db->bind($this->employer_id);
		if($this->db->update() === false) return false;
		$this->info['language']=$employer_language;
		return true;
	}#end function






	/*==============================================================================================
	Тема интерфейса
	==============================================================================================*/



	/*
	 * Получение выбранной клиентом темы интерфейса
	 */
	public function getTheme($employer_theme=null){

		if(!empty($this->employer_theme)) return $this->employer_theme;

		#Поддерживаемые языки
		$themes = Config::getOption('general','themes',array());
		
		if(empty($employer_theme)){
			#Если клиент не аутентифицирован, пытаемся получить выбранный язык из COOKIE
			if(!$this->checkAuthStatus()){
				$employer_theme = Request::_getCookie($this->cookie_theme, APP_THEME);
			}
			#Если клиент аутентицирован, берем значение выбранного языка из сессии
			else{
				if( ($employer_theme = Session::_get('theme')) === false) $employer_theme = APP_THEME;
			}
		}
		
		if(!in_array($employer_theme, $themes, true)) return APP_THEME;
		
		$this->employer_theme = $employer_theme;
		
		return $employer_theme;
	}#end function




	/*
	 * Изменение темы интерфейса
	 */
	public function setTheme($employer_theme=null){

		if(empty($employer_theme)) return false;
		
		#Поддерживаемые языки
		$themes = Config::getOption('general','themes',array());
		if(!in_array($employer_theme, $themes, true)) return false;
		
		Response::_addCookie($this->cookie_theme, $employer_theme, time() + 31536000);
		$this->employer_theme = $employer_theme;
		
		#Если клиент не аутентифицирован - выход
		if(!$this->checkAuthStatus() || empty($this->employer_id)) return true;
		
		Session::_set('theme', $employer_theme);
		$this->db->prepare('UPDATE `employers` SET `theme`=? WHERE `employer_id`=?');
		$this->db->bind($employer_theme);
		$this->db->bind($this->employer_id);
		if($this->db->update() === false) return false;
		$this->info['theme']=$employer_language;
		return true;
	}#end function




	/*==============================================================================================
	COOKIE HASH
	==============================================================================================*/

	/*
	 * Возвращает Хеш строки в указанном формате с солью
	 */
	public function getHash($string='',$type='sha1'){
		$client_salt = Request::_getIP(false).Request::_getIP(true).$_SERVER['HTTP_USER_AGENT'];
		 switch($type){
			case 'md5': return md5($client_salt.$string.'@->-'.Config::getOption('general','salt','#!$DefaulT@SalT$!#'));
			default: return sha1($client_salt.$string.'@->-'.Config::getOption('general','salt','#!$DefaulT@SalT$!#'));
		 }
	}#end function




}#end class

?>
