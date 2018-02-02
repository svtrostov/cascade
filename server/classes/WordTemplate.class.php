<?php
/*==================================================================================================
Описание: Обработка темплейтов MS Word
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');


class WordTemplate{

	public $_tempFileName	= null;		#Имя временного файла
	private $_objZip		= null;		#ZIP объект
	private $_zipIsClosed	= true;		#Признак, указывающий что ZIP азхив закрыт
	private $_documentXML	= null;		#XML содержимое DOCX документа


	/*
	 * Конструктор класса
	 */
	public function __construct ($fileName=''){ 

		#Загрузка темплейта
		$this->template_load($fileName);

	}#end function




	/*
	 * Деструктор класса
	 */
	public function __destruct(){
		if($this->_objZip) $this->_objZip->close();
		if($this->_tempFileName) @unlink($this->_tempFileName);
	}#end function




	/*
	 * Загрузка темплейта
	 */
	public function template_load($fileName=''){

		#Если файл темплейта не существует - выход
		if(!file_exists($fileName)) return false;

		#Создание временного файла
		$this->_tempFileName = tempnam(DIR_SERVER.'/tmp','zip');

		#Копирование содержимого темплейта во временный файл
		unlink($this->_tempFileName);
		copy($fileName, $this->_tempFileName);

		#Создание ZIP объекта и открытие временного файла как архива
		$this->_objZip = new ZipArchive();
		if( $this->_objZip->open($this->_tempFileName) === false) return false;

		$this->_documentXML = $this->_objZip->getFromName('word/document.xml');

		$this->_zipIsClosed = false;

		return true;

	}#end function




	/*
	 * Замена макроподстановки вида ${TERM} на соответствующее значение
	 */
	public function set($search, $replace){

		if($this->_zipIsClosed) return false;

		if(substr($search, 0, 2) !== '${' && substr($search, -1) !== '}') {
			$search = '${'.$search.'}';
		}

		$this->_documentXML = str_replace($search, $replace, $this->_documentXML);

		return true;

	}#end function




	/*
	 * Созранение внесенных изменений в архив документа DOCX
	 */
	public function save(){

		$this->_objZip->addFromString('word/document.xml', $this->_documentXML);
		$this->_objZip->close();
		$this->_zipIsClosed = true;

		return true;

	}#end function



	/*
	 * Чтение временного файла и возврат содержимого
	 */
	public function read(){

		if(!$this->_zipIsClosed) $this->save();

		return file_get_contents($this->_tempFileName);

	}#end function



	/*
	 * Замена подстрок в мультибайтовой строке
	 */
	public function mb_str_replace($needle, $replacement, $haystack){
		$needle_len = mb_strlen($needle);
		$replacement_len = mb_strlen($replacement);
		$pos = mb_strpos($haystack, $needle);
		while ($pos !== false){
			$haystack = mb_substr($haystack, 0, $pos) . $replacement . mb_substr($haystack, $pos + $needle_len);
			$pos = mb_strpos($haystack, $needle, $pos + $replacement_len);
		}
		return $haystack;
	}


}#END CLASS

?>