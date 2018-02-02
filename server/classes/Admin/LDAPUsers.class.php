<?php
/*==================================================================================================
Описание: LDAP Users
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');




class Admin_LDAPUsers{


	/*==============================================================================================
	Переменные класса
	==============================================================================================*/

	private $is_init		= false;	#ПРизнак корректной инициализации класса
	private $db 			= null;		#Указатель на экземпляр базы данных
	private $dbtoday		= '';		#Текущая дата в формате SQL: CCYY-mm-dd
	private $timestamp		= '';

	private $ldap			= '';
	private $ad_domain		= '';
	private $ad_controllers	= '';
	private $ad_username	= '';
	private $ad_password	= '';
	private $ad_base_dn		= '';
	private $ignogeUsersPrefixes = array();

	private $defaultUserInfo = array(
		'username'		=> '',
		'displayname'	=> '',
		'title'			=> '',
		'company'		=> '',
		'department'	=> '',
		'mail'			=> '',
		'telephone'		=> '',
		'active'		=> 0,
		'expires'		=> 0
	);



	/*==============================================================================================
	Инициализация
	==============================================================================================*/


	/*
	 * Конструктор класса
	 */
	public function __construct($options=array()){
		$this->db = Database::getInstance('main');
		$this->dbtoday = date('Y-m-d');
	}#end function



	/*
	 * Соединение с LDAP
	 */
	private function connect(){
		if($this->is_init) return true;
		$this->ad_domain = Config::getOption('ldap','ad_domain', false);
		$this->ad_controllers = Config::getOption('ldap','ad_controllers', false);
		$this->ad_username = Config::getOption('ldap','ad_username', false);
		$this->ad_password = Config::getOption('ldap','ad_password', false);
		$this->ad_base_dn = Config::getOption('ldap','ad_base_dn', false);
		$this->ignogeUsersPrefixes = Config::getOption('ldap','ignogeUsersPrefixes', array());
		try{
			$this->ldap = new adLDAP(array(
				'use_ssl'				=> true,
				'use_tls'				=> false,
				'domain_controllers'	=> $this->ad_controllers,
				'base_dn'				=> $this->ad_base_dn
			));
		}catch (adLDAPException $e){
			return false;
		}

		if (!$this->ldap->authenticate($this->ad_username, $this->ad_password)){
			$this->ldap->close();
			die('connection fail:' . $this->ldap->getLastError());
			return false;
		}
		return true;
	}#end function





	/*==============================================================================================
	ФУНКЦИИ: Работа с пользователячми домена
	==============================================================================================*/

	/*
	 * Добавление информации пользователя из AD в локальную базу ldap_users
	 */
	public function ldapUserNew($fields=array()){

		if(empty($fields)) return false;
		$add_fields = array_intersect(array_keys($this->defaultUserInfo), array_keys($fields));
		$adds = array();
		if(empty($add_fields)) return false;
		foreach($add_fields as $add_field){
			$adds[$add_field] = $fields[$add_field];
		}
		$fields = array_merge($this->defaultUserInfo, $adds);
		$ins_names=array();
		$ins_q=array();
		$binds=array();
		foreach($fields as $field=>$value){
			$ins_names[]='`'.$field.'`';
			$ins_q[]='?';
			$binds[]=$value;
		}
		$this->db->prepare('INSERT INTO `ldap_users` ('.implode(',',$ins_names).')VALUES('.implode(',',$ins_q).')');
		foreach($binds as $bind) $this->db->bind($bind);
		if(($record_id = $this->db->insert())===false) return false;

		return $record_id;
	}#end function




	/*
	 * Поиск пользователей в AD по имени пользователя
	 */
	public function searchUsers($username='*'){
		if(!$this->connect()) return false;
		return $this->ldap->user()->find(false,'samaccountname', $username);
	}#end function




	/*
	 * Получение информации о сотруднике
	 */
	public function userInfo($username=null, $fields=null){
		if(!$this->connect()) return false;
		if(empty($username)) return array();
		if(empty($fields)) $fields = array('samaccountname','displayname','mail','company','department','title','telephone','homephone','mobile','workphone','useraccountcontrol','accountexpires','lastlogontimestamp');
		if(!is_array($username))$username = array($username);
		$info = array();
		foreach($username as $uname){
			$uinfo = $this->ldap->user()->info($uname,$fields,false);
			if(is_array($uinfo)&&count($uinfo)>0){
				$info[$uname] = array_merge($this->defaultUserInfo, array(
					'username' => $uname
				));
				foreach($uinfo[0] as $key=>$value){
					switch(strtolower($key)){
						case 'displayname':
							$info[$uname]['displayname'] = (is_array($value) && !empty($value)) ? $value[0] : $uname;
						break;
						case 'mail':
						case 'title':
						case 'company':
						case 'department':
							if(is_array($value) && !empty($value)) $info[$uname][$key] = $value[0];
						break;
						case 'useraccountcontrol':
							$info[$uname]['active'] = (is_array($value) && !empty($value)) ? (($value[0] & 2)==2 ? 0 : 1) : 0;
						break;
						case 'lastlogontimestamp':
							if(is_array($value) && !empty($value)){
								$unix_timestamp = (floatval($value[0]) / 10000000) - 11644560000;
								$info[$uname]['lastlogon'] = date("Y-m-d H:i:s", $unix_timestamp);
							}
						break;
						case 'accountexpires':
							/*
							if(is_array($value) && !empty($value)){
								$unix_timestamp = (floatval($value[0]) / 10000000) - 11644560000;
								$info[$uname]['expires'] = date("d.m.Y H:i:s", $unix_timestamp);
							}
							*/
							if(is_array($value) && !empty($value)) $info[$uname]['expires'] = $value[0];
						break;
						case 'telephone':
						case 'homephone':
						case 'mobile':
						case 'workphone':
							if(is_array($value) && !empty($value) && empty($info[$uname]['telephone'])) $info[$uname]['telephone'] = $value[0];
						break;
					}
				}
			}
		}

		return $info;
	}#end function




	/*
	 * Функция возвращает список сотрудников, отсутствующих в локальной базе
	 */
	public function undefinedUsers($filterByPrefixes=true){
		/*
		if($this->db->result('SELECT count(*) FROM `ldap_users` LIMIT 1') > 0){
			return $this->db->selectByKey('username','SELECT * FROM `ldap_users`');
		}
		*/
		if(!$this->connect()) return false;
		if(($uinfo = $this->searchUsers('*'))===false) return false;
		if(empty($uinfo)) return array();
		$undefined = array();
		$filter = ($filterByPrefixes && is_array($this->ignogeUsersPrefixes) && !empty($this->ignogeUsersPrefixes)) ? true : false;
		foreach($uinfo as $uname){
			$use_login = true;
			if($filter){
				foreach($this->ignogeUsersPrefixes as $prefix){
					if(strncasecmp($prefix,$uname,strlen($prefix))==0){
						$use_login=false;
						break;
					}
				}
			}
			if(!$use_login) continue;
			$this->db->prepare('SELECT count(*) FROM `employers` WHERE `username` LIKE ? LIMIT 1');
			$this->db->bind($uname);
			if(($result = $this->db->result())===false) return false;
			if($result > 0) continue;
			$undefined[]=$uname;
		}


		$users = $this->userInfo($undefined);

		/*
		foreach($users as $aduser=>$adinfo){
			$this->ldapUserNew($adinfo);
		}
		*/

		return $users;
	}#end function




	/*
	 * Список существующих сотрудников, подходящих под данные LDAP пользователя
	 */
	public function relatedEmployers($anket=0){
		if(empty($anket)) return false;
		$this->db->prepare('
			SELECT `employer_id`,`username`,`status`,`search_name`,`phone`,`email`, DATE_FORMAT(`birth_date`, "%d.%m.%Y") as `birth_date` 
			FROM `employers` WHERE `last_name` LIKE ? OR `email` LIKE ?
		');
		$this->db->bind($anket['last_name']);
		$this->db->bind($anket['email']);
		return $this->db->select();
	}#end function

}#end class

?>
