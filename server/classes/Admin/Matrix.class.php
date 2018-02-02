<?php
/*==================================================================================================
Описание: Матрица доступа
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');




class Admin_Matrix{


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
	public function __construct(){
		$this->db = Database::getInstance('main');
	}#end function






	/*==============================================================================================
	ФУНКЦИИ: Работа с заявками
	==============================================================================================*/




	/*
	 * Проверка наличия доступа к объекту в результирующей таблице доступов сотрудников
	 */
	public function employerIResourceRoleExists($employer_id=0, $post_uid=0, $iresource_id=0, $irole_id=0){

		#Получение списка объектов ИР
		$this->db->prepare('SELECT count(*) FROM `complete_roles_full` WHERE `employer_id`=? '.(!empty($post_uid)?' AND `post_uid`=? ':'').' AND `iresource_id`=? AND `irole_id`=? AND `ir_type`>0');
		$this->db->bind($employer_id);
		if(!empty($post_uid)) $this->db->bind($post_uid);
		$this->db->bind($iresource_id);
		$this->db->bind($irole_id);
		if( ($result = $this->db->result()) === false) return false;
		return ($result > 0);
	}#end function




	/*
	 * Получение списка объектов доступа в заявке
	 */
	public function employerIResourceRoles($employer_id=0, $post_uid=0, $iresource_id=0){

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
			LEFT JOIN `complete_roles_full` as CRF ON CRF.`employer_id`=? '.(!empty($post_uid)?' AND CRF.`post_uid`=? ':'').' AND CRF.`iresource_id`=IROLE.`iresource_id` AND CRF.`irole_id`=IROLE.`irole_id`
			WHERE 
				IROLE.`iresource_id`=? AND IROLE.`is_lock`=0
			ORDER BY 
				IROLE.`full_name`
		');
		$this->db->bind($employer_id);
		if(!empty($post_uid)) $this->db->bind($post_uid);
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
	 * Получение общего списка объектов ИР сотрудника
	 */
	public function employerCompleteIResources($employer_id=0){

		#Получение списка объектов ИР
		$this->db->prepare('
			SELECT
				DISTINCT CR.`iresource_id` as `iresource_id`,
				IR.`full_name` as `iresource_name`
			FROM 
				`complete_roles` as CR
			INNER JOIN `iresources` as IR ON IR.`iresource_id`=CR.`iresource_id`
			WHERE 
				CR.`employer_id`=?
		');
		$this->db->bind($employer_id);

		if(($iresources = $this->db->select()) === false) return false;
		if(empty($iresources)) return false;

		#Создание результирующего списка
		foreach($iresources as $indx=>$iresource){
			$iresource_id = $iresource['iresource_id'];
			$iresources[$indx]['roles'] = $this->employerIResourceRoles($employer_id,null,$iresource_id);
			$iresources[$indx]['performers'] = $this->getIResourcePerformers($iresource_id);
		}

		return $iresources;
	}#end function




	/*
	 * Получение списка сотрудников, включенных в группу исполнителей по ИР
	 */
	public function getIResourcePerformers($iresource_id=0){

		$this->db->prepare('SELECT * FROM `iresources` WHERE `iresource_id`=? LIMIT 1');
		$this->db->bind($iresource_id);
		$iresource_info = $this->db->selectRecord();
		if(empty($iresource_info)||empty($iresource_info['worker_group'])) return array();

		$this->db->prepare('
			SELECT 
				EMP.`employer_id` as `employer_id`,
				EMP.`search_name` as `search_name`,
				EMP.`phone` as `phone`,
				EMP.`email` as `email`
			FROM `employer_groups` as EG 
				INNER JOIN `employers` as EMP ON EMP.`employer_id`=EG.`employer_id` AND EMP.`status`>0
			WHERE `group_id`=?
			');
			$this->db->bind($iresource_info['worker_group']);
		return $this->db->select();
	}#end function




	/*
	 * Получение списка ИР сотрудника на определенной должности
	 */
	public function employerIResourcesOnPost($employer_id=0, $post_uid=0){
		$this->db->prepare('SELECT DISTINCT `iresource_id` FROM `complete_roles_full` WHERE `employer_id`=? AND `post_uid`=?');
		$this->db->bind($employer_id);
		$this->db->bind($post_uid);
		if(($iresources = $this->db->selectFromField('iresource_id')) === false) return false;
		return $iresources;
	}#end function




	/*
	 * Получение списка ролей ИР сотрудника на определенной должности
	 */
	public function employerIResourceRolesOnPost($employer_id=0, $post_uid=0, $iresource_id=0){
		$this->db->prepare('SELECT DISTINCT `irole_id` FROM `complete_roles_full` WHERE `employer_id`=? AND `post_uid`=? AND `iresource_id`=?');
		$this->db->bind($employer_id);
		$this->db->bind($post_uid);
		$this->db->bind($iresource_id);
		if(($iroles = $this->db->selectFromField('irole_id')) === false) return false;
		return $iroles;
	}#end function


}#end class

?>