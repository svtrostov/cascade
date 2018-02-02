<?php
/*==================================================================================================
Описание: Работа с объектами доступа заявок
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');



trait Trait_RequestRoles{


	/*==============================================================================================
	Функции
	==============================================================================================*/



	#--------------------------------------------------
	# Создание таблицы объектов доступа для заявок
	#--------------------------------------------------
	public function createRIRoleDBTable($request_id=0, $check_exists=true){
		$table_name = $this->getRIRoleDBTableName($request_id);
		if($check_exists && $this->existsRIRoleDBTable($table_name)) return true;
		if($this->db->simple('
			CREATE TABLE IF NOT EXISTS `'.$table_name.'` (
			`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`request_id` int(10) unsigned NOT NULL,
			`iresource_id` int(10) unsigned NOT NULL,
			`irole_id` int(10) unsigned NOT NULL,
			`ir_type` int(10) unsigned NOT NULL,
			`ir_selected` int(10) unsigned NOT NULL,
			`gatekeeper_id` int(10) unsigned NOT NULL,
			`update_type` int(10) unsigned NOT NULL,
			`timestamp` datetime NOT NULL,
			PRIMARY KEY (`id`),
			KEY `iresource_id` (`iresource_id`),
			KEY `request_id` (`request_id`),
			KEY `irole_id` (`irole_id`),
			KEY `ir_selected` (`ir_selected`),
			KEY `ir_type` (`ir_type`)
			) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT="Request roles table" AUTO_INCREMENT=1;
		')===false) return false;
		return true;
	}#end function




	#--------------------------------------------------
	# Возвращает название таблицы для объектов доступа заявки, по идентификатору заявки
	#--------------------------------------------------
	public function getRIRoleDBTableName($request_id=0){
		return 'request_roles_'.floor(intval($request_id)/1000);
	}#end function




	#--------------------------------------------------
	# Проверяет существование таблицы объектов доступа  заявки
	#--------------------------------------------------
	public function existsRIRoleDBTable($request_id=0){
		$table_name = (is_numeric($request_id) ? $this->getRIRoleDBTableName($request_id) : $request_id);
		return $this->db->tableExists($table_name);
	}#end function




	#--------------------------------------------------
	# Возвращает список всех таблиц объектов доступа заявки
	#--------------------------------------------------
	public function getRIRoleDBTables(){
		return $this->db->tableList('request_roles_');
	}#end function




	#--------------------------------------------------
	# Проверка использования информационного ресурса в заявках
	#--------------------------------------------------
	public function checkUseRIRoleIResource($iresource_id=0){
		$table_names = $this->getRIRoleDBTables();
		$iresource_id = intval($iresource_id);
		foreach($table_names as $table_name){
			if($this->db->result('SELECT count(*) FROM `'.$table_name.'` WHERE `iresource_id`='.$iresource_id.' LIMIT 1') > 0) return true;
		}
		return false;
	}#end function




	#--------------------------------------------------
	# Проверка использования объекта доступа информационного ресурса в заявках
	#--------------------------------------------------
	public function checkUseRIRoleIResourceRole($irole_id=0){
		$table_names = $this->getRIRoleDBTables();
		$irole_id = intval($irole_id);
		foreach($table_names as $table_name){
			if($this->db->result('SELECT count(*) FROM `'.$table_name.'` WHERE `irole_id`='.$irole_id.' LIMIT 1') > 0) return true;
		}
		return false;
	}#end function




	#--------------------------------------------------
	# Проверка использования типа доступа в заявках
	#--------------------------------------------------
	public function checkUseRIRoleIRType($ir_type=0){
		$table_names = $this->getRIRoleDBTables();
		$ir_type = intval($ir_type);
		foreach($table_names as $table_name){
			if($this->db->result('SELECT count(*) FROM `'.$table_name.'` WHERE `ir_type`='.$ir_type.' OR `ir_selected`='.$ir_type.' LIMIT 1') > 0) return true;
		}
		return false;
	}#end function









}#end class


?>