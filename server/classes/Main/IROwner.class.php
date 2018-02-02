<?php
/*==================================================================================================
Описание: Владелец информационного ресурса
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');




class Main_IROwner{


	/*==============================================================================================
	Переменные класса
	==============================================================================================*/

	private $db = null;		#Указатель на экземпляр базы данных





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







	/*==============================================================================================
	Информация по сотруднику
	==============================================================================================*/

	/*
	 * Возвращает список должностей, занимаемых сотрудниками
	 */
	public function getIResourceEmployers($iresource_id=0){

		if(empty($iresource_id)) return false;

		$this->db->prepare('
			SELECT
				DISTINCT CRF.`employer_id` as `employer_id`,
				EMP.`search_name` as `search_name`,
				EMP.`phone` as `phone`,
				EMP.`email` as `email`,
				CRF.`post_uid` as `post_uid`,
				CRF.`company_id` as `company_id`,
				P.`full_name` as `post_name`,
				C.`full_name` as `company_name`
			FROM `complete_roles_full` as CRF
			INNER JOIN `employers` as EMP ON EMP.`employer_id`=CRF.`employer_id` AND EMP.`status`>0
			INNER JOIN `company_posts` as CP ON CP.`post_uid`=CRF.`post_uid`
			INNER JOIN `posts` as P ON P.`post_id`=CP.`post_id`
			INNER JOIN `companies` as C ON C.`company_id`=CRF.`company_id`
			WHERE CRF.`iresource_id`=?
			ORDER BY CRF.`timestamp` DESC
		');
		$this->db->bind($iresource_id);

		return $this->db->select();
	}#end function




	/*
	 * Получение списка объектов, к которым сотрудник имеет доступ в указанном ИР
	 */
	public function employerIResourceRoles($employer_id=0, $iresource_id=0){

		#Получение списка объектов ИР
		$this->db->prepare('
			SELECT
				IROLE.`iresource_id` as `iresource_id`,
				IROLE.`irole_id` as `irole_id`,
				IROLE.`owner_id` as `owner_id`,
				IROLE.`full_name` as `full_name`,
				IROLE.`description` as `description`,
				IROLE.`is_area` as `is_area`,
				IROLE.`weight` as `weight`,
				CRF.`ir_type` as `ir_type`,
				CRF.`request_id` as `request_id`,
				DATE_FORMAT(CRF.`timestamp`, "%d.%m.%Y") as `timestamp`,
				CRF.`timestamp` as `sql_time`
			FROM 
				`iroles` as IROLE
			LEFT JOIN `complete_roles_full` as CRF ON CRF.`employer_id`=? AND CRF.`iresource_id`=IROLE.`iresource_id` AND CRF.`irole_id`=IROLE.`irole_id`
			WHERE 
				IROLE.`iresource_id`=? AND IROLE.`is_lock`=0
			ORDER BY 
				IROLE.`full_name`
		');
		$this->db->bind($employer_id);
		$this->db->bind($iresource_id);

		if( ($objects = $this->db->select()) === false) return false;

		$data = array();
		$items = array();
		$iroles=array();
		$areas = array(array(
			'irole_id' => '0',
			'iresource_id' => $iresource_id,
			'full_name' => '-[Без раздела]-'
		));
		$area_ids = array();
		$area_ids[0] = true;
		$results=array();
		$indx=0;
		
		#Подготовка списка разделов
		foreach($objects as $key=>$item){
			if($objects[$key]['is_area'] == 1){
				$areas[] = array(
					'irole_id' => $objects[$key]['irole_id'],
					'iresource_id' => $iresource_id,
					'full_name' => $objects[$key]['full_name']
				);
				$area_ids[$objects[$key]['irole_id']] = true;
			}else{
				$irole_id = $objects[$key]['irole_id'];
				if(isset($iroles[$irole_id])){
					if($objects[$key]['sql_time'] > $results[$iroles[$irole_id]]['sql_time']){
						$results[$iroles[$irole_id]]['sql_time'] = $objects[$key]['sql_time'];
						$results[$iroles[$irole_id]]['ir_type'] = $objects[$key]['ir_type'];
					}
					continue;
				}else{
					$iroles[$irole_id] = $indx;
				}
			}
			$results[$indx] = $objects[$key];
			$indx++;
		}

		#Обработка записей и перегруппировка по разделам
		foreach($results as $key=>$item){
			if($results[$key]['is_area'] != 1){
				if(empty($results[$key]['ir_type'])) continue;
				$results[$key]['screenshot'] = (irole_screenshot_exists($results[$key]['irole_id']) ? $results[$key]['irole_id'] : '');
				#Если родительский раздел не найден в списке разделов, отправляем в "Без раздела"
				if(!isset($area_ids[$results[$key]['owner_id']])) $results[$key]['owner_id'] = 0;
				if(!isset($data[$results[$key]['owner_id']])||!is_array($data[$results[$key]['owner_id']])) $data[$results[$key]['owner_id']] = array();
				$data[$results[$key]['owner_id']][] = $results[$key];
			}
		}

		#Создание результирующего списка
		foreach($areas as $item){
			if(isset($data[$item['irole_id']])&&is_array($data[$item['irole_id']])){
				$items[] = $item['full_name'];
				foreach($data[$item['irole_id']] as $i){
					$items[] = $i;
				}
			}
		}

		return $items;
	}#end function





	/*
	 * Получение списка объектов доступа информационного ресурса
	 */
	public function getIRoles($iresource_id=0, $fields=null, $raw_data=false, $iroles_filter=null){

		#Получение списка объектов ИР
		$this->db->prepare('
			SELECT * FROM `iroles` WHERE `iresource_id`=? AND `is_lock`=0 ? ORDER BY `full_name`
		');
		$this->db->bind($iresource_id);
		$this->db->bindSql((!empty($iroles_filter)&&is_array($iroles_filter) ? ' AND `irole_id` IN ('.implode(',',array_map('intval',$iroles_filter)).')' : ''));
		if( ($results = $this->db->select()) === false) return false;

		#Возвращаем сырые данные если RAW запрос
		if($raw_data) return $results;

		$data = array();
		$items = array();
		$areas = array(array(
			'irole_id' => '0',
			'iresource_id' => $iresource_id,
			'full_name' => '-[Без раздела]-'
		));
		$area_ids = array();
		$area_ids[0] = true;
		
		#Подготовка списка разделов
		foreach($results as $key=>$item){
			if($results[$key]['is_area'] == 1){
				$areas[] = array(
					'irole_id' => $results[$key]['irole_id'],
					'iresource_id' => $results[$key]['iresource_id'],
					'full_name' => $results[$key]['full_name']
				);
				$area_ids[$results[$key]['irole_id']] = true;
			}else{
				if(!empty($results[$key]['ir_types'])){
					$results[$key]['ir_types'] = explode(',',$results[$key]['ir_types']);
				}else{
					$results[$key]['ir_types']=array();
				}
			}
		}

		#Обработка записей и перегруппировка по разделам
		foreach($results as $key=>$item){
			if($results[$key]['is_area'] != 1){
				$results[$key]['screenshot'] = (irole_screenshot_exists($results[$key]['irole_id']) ? $results[$key]['irole_id'] : '');
				if(empty($results[$key]['weight'])) $results[$key]['weight'] = 0;
				#Если родительский раздел не найден в списке разделов, отправляем в "Без раздела"
				if(!isset($area_ids[$results[$key]['owner_id']])) $results[$key]['owner_id'] = 0;
				if(!isset($data[$results[$key]['owner_id']])||!is_array($data[$results[$key]['owner_id']])) $data[$results[$key]['owner_id']] = array();
				$data[$results[$key]['owner_id']][] = $results[$key];
			}
		}

		#Создание результирующего списка
		foreach($areas as $item){
			if(isset($data[$item['irole_id']])&&is_array($data[$item['irole_id']])){
				$items[] = $item['full_name'];
				foreach($data[$item['irole_id']] as $i){
					$items[] = $i;
				}
			}
		}

		return $items;
	}#end function




}#end class

?>