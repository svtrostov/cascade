<?php
/*==================================================================================================
Описание: Работа с историей заявок
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');



trait Trait_RequestHistory{


	/*==============================================================================================
	Функции
	==============================================================================================*/



	#--------------------------------------------------
	# Проверяет наличие информационного ресурса заявки в активных заявках `request_iresources`
	#--------------------------------------------------
	public function isRIResourceActive($request_id=0, $iresource_id=0){
		return ($this->db->result('SELECT count(*) FROM `request_iresources` WHERE `request_id`='.intval($request_id).' AND `iresource_id`='.intval($iresource_id).' LIMIT 1') > 0);
	}#end function


	#--------------------------------------------------
	# Проверяет наличие информационного ресурса заявки в истории
	#--------------------------------------------------
	public function isRIResourceHistory($request_id=0, $iresource_id=0){
		return ($this->db->result('SELECT count(*) FROM `request_iresources_hist` WHERE `request_id`='.intval($request_id).' AND `iresource_id`='.intval($iresource_id).' LIMIT 1') > 0);
	}#end function


	#--------------------------------------------------
	# Возвращает имя таблицы DB в зависимости от переданных аргументов
	# Если передан 1 агрумент, считается, что это route_status
	# Если передано несколько аргументов, считается что это request_id и iresource_id
	#--------------------------------------------------
	public function getRIResourceDBTableName(){
		$arguments=array_map('intval',func_get_args());
		if(count($arguments) == 1) return ($arguments[0] == 1 || $arguments[0] == 2 ? 'request_iresources' : 'request_iresources_hist');
		return ($this->isRIResourceActive($arguments[0], $arguments[1]) ? 'request_iresources' : 'request_iresources_hist');
	}#end function



	#--------------------------------------------------
	# Перемещает заявку в историю
	#--------------------------------------------------
	public function moveRIResourceToHistory($request_id=0, $iresource_id=0){
		$in_transaction = $this->db->inTransaction();
		if(!$in_transaction) $this->db->transaction();

		#Копирование записи в историю
		$this->db->prepare('INSERT INTO `request_iresources_hist` SELECT * FROM `request_iresources` WHERE `request_id`=? AND `iresource_id`=?');
		$this->db->bind($request_id);
		$this->db->bind($iresource_id);
		if($this->db->simple()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		#Удаление записи из активных заявок
		$this->db->prepare('DELETE FROM `request_iresources` WHERE `request_id`=? AND `iresource_id`=?');
		$this->db->bind($request_id);
		$this->db->bind($iresource_id);
		if($this->db->simple()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		#Копирование шагов заявки в историю
		$this->db->prepare('INSERT INTO `request_steps_hist` SELECT * FROM `request_steps` WHERE `request_id`=? AND `iresource_id`=?');
		$this->db->bind($request_id);
		$this->db->bind($iresource_id);
		if($this->db->simple()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		#Удаление шагов заявки из активных заявок
		$this->db->prepare('DELETE FROM `request_steps` WHERE `request_id`=? AND `iresource_id`=?');
		$this->db->bind($request_id);
		$this->db->bind($iresource_id);
		if($this->db->simple()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		#Выполнено успешно
		if(!$in_transaction) $this->db->commit();
		return true;
	}#end function

}#end class


?>