<?php
/*==================================================================================================
Описание: Обработка многоуровневых массивов
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');



trait Trait_Array{


	/*==============================================================================================
	Функции
	==============================================================================================*/



	#--------------------------------------------------
	# Получение значения элемента массива по заданному пути
	#--------------------------------------------------
	static public function arrayGetValue(&$iterator, $path, $default=null){

		if((array)$path !== $path) $path=array($path);
		foreach($path as $v){
			if(isset($iterator[$v])) $iterator = &$iterator[$v];
			else return $default;
		}

		return $iterator;
	}#end function



	#--------------------------------------------------
	# Запись значения в массив по пути
	#--------------------------------------------------
	static public function arraySetValue(&$iterator, $path, $value=null){

		if((array)$path !== $path) $path = array($path);
		$count = count($path)-1;
		foreach($path as $v){
			//if(!isset($iterator[$v])) $iterator[$v] array();
			$iterator = &$iterator[$v];
		}
		$iterator = $value;

		return true;
	}#end function




	#--------------------------------------------------
	# Удаление значения из массива по пути
	#--------------------------------------------------
	static public function arrayDelValue(&$array, $path){

		if((array)$path !== $path) $path = array($path);
		$iterator = $array;
		$count = count($path)-1;
		for($i=0; $i<=$count; $i++){
			$v = $path[$i];
			if(!isset($iterator[$v])) return true;
			if($i == $count) unset($iterator[$v]);
			else $iterator = &$iterator[$v];
		}

		return true;
	}#end function






}#end class


?>