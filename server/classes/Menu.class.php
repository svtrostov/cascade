<?php
/*==================================================================================================
Описание: Класс работы с меню
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');



class Menu{

	use Trait_SingletonUnique;

	/*==============================================================================================
	Переменные класса
	==============================================================================================*/

	#db
	private $db = null;



	/*==============================================================================================
	Инициализация
	==============================================================================================*/


	/*
	 * Конструктор класса
	 */
	protected function init(){

		$this->db	= Database::getInstance('main');

	}#end function




	/*==============================================================================================
	ФУНКЦИИ: Работа с меню
	==============================================================================================*/



	/*
	 * Построение меню для пользователя
	 */
	public function getMenu($menu_id=0){

		$this->db->prepare('SELECT * FROM `menu_map` WHERE `menu_id`=?');
		$this->db->bind(intval($menu_id));
		if(($list = $this->db->select())===false) return false;

		$menu = array();
		$filter = array();
		$item_ids = array();
		$childs = array();

		foreach($list as $i=>$item){
			$childs[$item['parent_id']] = true;
			$filter[]=$item;
			$item_ids[$item['item_id']] = $item['item_id'];
		}

		foreach($filter as $item){
			if(!empty($item['parent_id']) && !isset($item_ids[$item['parent_id']])) continue;
			$menu[]=$item;
		}

		return $menu;
	}#end function






}#end class

?>