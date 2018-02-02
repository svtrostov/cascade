<?php
/*==================================================================================================
Описание: Вспомогательные функции
Автор	: Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');





/*==============================================================================================
Функции дебага
==============================================================================================*/


/*
 * Генератор события об ошибке
 */
function debugError($data=array()){

	$error_return	= !empty($data['return']) ? $data['return'] : false;

	if(!APP_DEBUG) return $error_return;

	$error_uid		= !empty($data['id']) ? $data['id'] : 0;
	$error_desc		= !empty($data['desc']) ? $data['desc'] : 0;
	$error_data		= !empty($data['data']) ? $data['data'] : 0;
	$file = !empty($data['file']) ? $data['file'] :'';
	$line = !empty($data['line']) ? $data['line'] :'';
	$class = !empty($data['class']) ? $data['class'] :'';
	$function = !empty($data['function']) ? $data['function'] :'';

	if(empty($function)){
		$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
		$backtrace = $backtrace[1];
		if(is_array($backtrace)){
			$file = $backtrace['file'];
			$line = $backtrace['line'];
			$class = isset($backtrace['class'])?$backtrace['class']:'';
			$function = isset($backtrace['function'])?$backtrace['function']:'';
		}
	}

	#Вывод ошибки на экран
	echo 
	"<pre>\nDEBUG ERROR:\n".str_repeat('=',40)."\n".
	"Error ID : ".$error_uid."\n".
	"Desc     : ".$error_desc."\n".
	"Class    : ".$class."\n".
	"Function : ".$function."\n".
	"File     : ".$file."\n".
	"Line     : ".$line."\n".
	(empty($error_data) ? '' :
	"Info     : ".print_r($error_data,true)."\n").
	"Backtrace: ".print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),true)."\n".
	str_repeat('=',40),"\n</pre>";

	return $error_return;
}#end function





/*==============================================================================================
Функции обработки строк
==============================================================================================*/

/*
 * Удаление из строки лишних пробелов
 */
function removeWhitespace($string){ 
	$string = preg_replace('/\s+/', ' ', $string); 
	$string = trim($string); 
	return $string;
}#end function




/*
 * Перевод текста с кириллицы в траскрипт
 * ГОСТ Р 52535.1-2006
 * Приказ Федеральной миграционной службы (ФМС России) от 3 февраля 2010 г. N 26 г.
 */
function rus2eng($string=''){
	$string = ' '.trim($string).' ';
	$table = array(
		array(
		'ия ' => 'ia ',
		'ья ' => 'ia ',
		'ый ' => 'y ',
		'ий ' => 'y ',
		'ян ' => 'an ',
		'ай ' => 'ay ',
		'др ' => 'der '
		),
		array(
		'кс' => 'x',
		'ай' => 'ay',
		'ей' => 'ey',
		'ий' => 'iy',
		'ой' => 'oy',
		'уй' => 'uy',
		'ый' => 'yy',
		'эй' => 'ey',
		'юй' => 'yuy',
		'ья' => 'ia',
		'ью' => 'iu'
		),
		array( 
			'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 
			'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I', 
			'Й' => 'I', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 
			'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 
			'У' => 'U', 'Ф' => 'F', 'Х' => 'Kh', 'Ц' => 'Tc', 'Ч' => 'Ch', 
			'Ш' => 'Sh', 'Щ' => 'Shch', 'Ь' => '', 'Ы' => 'Y', 'Ъ' => '', 
			'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya', 

			'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 
			'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 
			'й' => 'I', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 
			'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 
			'у' => 'u', 'ф' => 'f', 'х' => 'kh', 'ц' => 'tc', 'ч' => 'ch', 
			'ш' => 'sh', 'щ' => 'shch', 'ь' => '', 'ы' => 'y', 'ъ' => '', 
			'э' => 'e', 'ю' => 'yu', 'я' => 'ya'
		)
	);

	foreach($table as $item){
		$string = str_replace(
			array_keys($item), 
			array_values($item),
			$string 
		); 
	}

	return trim($string);
}#end function




/*==============================================================================================
Функции работы с датами
==============================================================================================*/



/*
 * Функция конвертации даты из одного формата в другой
 */
function dateformat($date, $to='Y-m-d', $from='d.m.Y'){
	if (strlen($date) == 8 || strlen($date) == 17){
		$date = substr($date, 0, 6) . '20' . substr($date, 6, strlen($date));
	}
	else if($from=='d.m.Y' && $to=='Y-m-d'){
		$d = explode('.',$date);
		return $d[2].'-'.$d[1].'-'.$d[0];
	}
	return date($to, strtotime($date));
}#end function



/*
 * Функция конвертации даты вида d.m.Y в SQL Y-m-d
 */
function date2sql($date){
	return dateformat($date,'Y-m-d','d.m.Y');
}#end function



/*
 * Функция конвертации SQL Y-m-d в дату вида d.m.Y
 */
function sql2date($date){
	return dateformat($date,'d.m.Y','Y-m-d');
}#end function







/*==============================================================================================
Функции работы с массивами
==============================================================================================*/

/*
 * Получение значения элемента массива по заданному пути
 */
function arrayGetValue(&$iterator, $path, $default=null, $delimiter='/'){

	if(!is_array($path)) $path = empty($delimiter) ? $path : explode($delimiter,trim($path,$delimiter));
	foreach($path as $v){
		if(isset($iterator[$v])) $iterator = &$iterator[$v];
		else return $default;
	}

	return $iterator;
}#end function



/*
 * Запись значения в массив по пути
 */
function arraySetValue(&$iterator, $path, $value=null, $delimiter='/'){

	if(!is_array($path)) $path = empty($delimiter) ? $path : explode($delimiter,trim($path,$delimiter));
	$count = count($path)-1;
	foreach($path as $v){
		if(!isset($iterator[$v])) $iterator[$v]=array();
		$iterator = &$iterator[$v];
	}
	$iterator = $value;

	return true;
}#end function




/*
 * Удаление значения из массива по пути
 */
function arrayDelValue(&$array, $path='', $delimiter='/'){

	if(!is_array($path)) $path = empty($delimiter) ? $path : explode($delimiter,trim($path,$delimiter));
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




/*
 * Выбирает из массива $record часть полей, указанных в массиве $fields и возвращает их
 */
function arrayCustomFields($record, $fields){
	if(!is_array($record)) return false;
	$result = array();
	foreach($fields as $field){
		$result[$field] = (!isset($record[$field]) ? null : $record[$field]);
	}
	return $result;
}#end function



/*
 * Выбирает из массива $records значение поля $field и возвращает линейный массив
 */
function arrayFromField($field, $records, $uniques=false){
	if(!is_array($records)) return array();
	$result = array();
	if(!$uniques){
		foreach($records as $record){
			$result[] = (!isset($record[$field]) ? null : $record[$field]);
		}
	}else{
		foreach($records as $record){
			if(!isset($record[$field])) continue;
			if(in_array($record[$field], $result)) continue;
			$result[] = $record[$field];
		}
	}
	return $result;
}#end function





#------------------------------------------------------------------
#Функция проверки существования скриншота для роли
function irole_screenshot_exists($irole_id=''){
	$filename = DIR_IROLE_SCREENSHOTS.'/irole_'.$irole_id.'.jpg';
	return (file_exists($filename)&&is_readable($filename));
}#end function


#------------------------------------------------------------------
#Функция удаления скриншота роли
function irole_screenshot_delete($irole_id=''){
	$filename = DIR_IROLE_SCREENSHOTS.'/irole_'.$irole_id.'.jpg';
	if(file_exists($filename)&&is_writable($filename)){
		@unlink($filename);
	}
}#end function



#------------------------------------------------------------------
#Функция копирования скриншота роли
function irole_screenshot_copy($from_irole='',$to_irole=''){
	$file_from = DIR_IROLE_SCREENSHOTS.'/irole_'.$from_irole.'.jpg';
	$file_to = DIR_IROLE_SCREENSHOTS.'/irole_'.$to_irole.'.jpg';
	if(file_exists($file_from)&&is_readable($file_from)){
		@copy($file_from, $file_to);
	}
}#end function




?>