<?php
/*==================================================================================================
Описание: Шаблоны доступа
Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');




class Admin_Template{


	/*==============================================================================================
	Переменные класса
	==============================================================================================*/

	private $db 			= null;		#Указатель на экземпляр базы данных


	private $defaultTemplateRecord = array(
		'full_name'				=> '',
		'description'			=> '',
		'company_id'			=> 0,
		'post_uid'				=> 0,
		'is_lock'				=> 0,
		'is_for_new_employer'	=> 0
	);

	private $defaultTemplateRoleRecord = array(
		'template_id'	=> 0,
		'iresource_id'	=> 0,
		'irole_id'		=> 0,
		'ir_type'		=> 0
	);




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
	ФУНКЦИИ: Работа с шаблонами
	==============================================================================================*/


	/*
	 * Получение списка шаблонов заявок
	 */
	public function getTemplatesListEx($templates=0, $fields=null, $single=false, $extended=true){

		$select = '';
		$select_from_field = false;

		if(empty($fields)){
			$select = 'SELECT TMPL.*';
		}
		elseif(!is_array($fields) && (array_key_exists($fields, $this->defaultTemplateRecord) || $fields=='template_id')){
			$select = 'SELECT TMPL.`'.$fields.'` as `'.$fields.'`';
			$select_from_field = true;
		}
		elseif(is_array($fields)){
			foreach($fields as $field){
				if(!array_key_exists($field, $this->defaultTemplateRecord) && $field!='template_id') continue;
				$select.= (empty($select) ? 'SELECT ' : ', ');
				$select.='TMPL.`'.$field.'` as `'.$field.'`';
			}
		}

		if(empty($select)) return false;

		
		if($extended){
			$select.=',
			IFNULL(C.`full_name`,"") as `company_name`,
			IFNULL(P.`full_name`,"") as `post_name`
			FROM `templates` as TMPL
				LEFT JOIN `companies` as C ON C.`company_id`=TMPL.`company_id`
				LEFT JOIN `company_posts` as CP ON CP.`post_uid`=TMPL.`post_uid`
				LEFT JOIN `posts` as P ON P.`post_id`=CP.`post_id`
			';
		}else{
			$select.=' FROM `templates` as TMPL';
		}

		if(!is_array($templates)){
			if(empty($templates)){
				$result = $this->db->prepare($select.($single?' LIMIT 1':''));
			}else{
				$this->db->prepare($select.' WHERE TMPL.`template_id`=? '.($single?' LIMIT 1':''));
				$this->db->bind(intval($templates));
			}
		}else{
			$conditions = $this->db->buildSqlConditions($templates,'TMPL');
			$this->db->prepare($select.' WHERE '.$conditions.($single?' LIMIT 1':''));
		}

		$result = ($select_from_field ? $this->db->selectFromField($fields)  : (empty($single) ? $this->db->select() : $this->db->selectRecord()));

		if(!$extended || $select_from_field) return $result;

		return $result;
	}#end function



	/*
	 * Получение списка шаблонов
	 */
	public function getTemplatesList($templates=0, $fields=null, $single=false){

		if(!is_array($templates)){
			if(empty($templates)){
				$result = $this->db->prepare('SELECT * FROM `templates`'.($single?' LIMIT 1':''));
			}else{
				$this->db->prepare('SELECT * FROM `templates` WHERE `template_id`=? '.($single?' LIMIT 1':''));
				$this->db->bind(intval($templates));
			}
		}else{
			$conditions = $this->db->buildSqlConditions($templates);
			$this->db->prepare('SELECT * FROM `templates` WHERE '.$conditions.($single?' LIMIT 1':''));
		}

		$result = (empty($single) ? $this->db->select() : $this->db->selectRecord());
		if(empty($fields)) return $result;
		if(!empty($fields) && !is_array($fields)) return arrayFromField($fields,$result);
		if(!empty($single)) return arrayCustomFields($result, $fields);

		$return = array();
		foreach($result as $record){
			$return[] = arrayCustomFields($record, $fields);
		}

		return $return;
	}#end function



	/*
	 * Функция выбора шаблона для должности
	 */
	public function templateForPost($post_uid=0, $company_id=0){
		$this->db->prepare('
			SELECT `template_id` 
			FROM(
				SELECT 
					TMPL.`template_id`,
					( 0 +
						IF(TMPL.`post_uid`>0 AND TMPL.`post_uid`=?, 2, 0) + 
						IF(TMPL.`company_id`>0 AND TMPL.`company_id`=?, 1, 0)
					) as `weight`
				FROM 
					`templates` as TMPL
				WHERE
					TMPL.`post_uid` IN(0,?) AND
					TMPL.`company_id` IN (0,?) AND
					TMPL.`is_for_new_employer`=0 AND 
					TMPL.`is_lock`=0
			) as `RT`
			ORDER BY RT.`weight` DESC
			LIMIT 1
		');
		$this->db->bind($post_uid);
		$this->db->bind($company_id);
		$this->db->bind($post_uid);
		$this->db->bind($company_id);
		return $this->db->result();
	}#end function





	/*
	 * Функция выбора шаблона для нового сотрудника
	 */
	public function templateForNewEmployer($post_uid=0, $company_id=0){
		$this->db->prepare('
			SELECT `template_id` 
			FROM(
				SELECT 
					TMPL.`template_id`,
					( 0 +
						IF(TMPL.`post_uid`>0 AND TMPL.`post_uid`=?, 2, 0) + 
						IF(TMPL.`company_id`>0 AND TMPL.`company_id`=?, 1, 0)
					) as `weight`
				FROM 
					`templates` as TMPL
				WHERE
					TMPL.`is_for_new_employer`=1 AND TMPL.`is_lock`=0
			) as `RT`
			ORDER BY RT.`weight` DESC
			LIMIT 1
		');
		$this->db->bind($post_uid);
		$this->db->bind($company_id);
		return $this->db->result();
	}#end function





	/*
	 * Получение списка информационных ресурсов в шаблоне
	 */
	public function templateIResources($template_id=0){
		$template_id = intval($template_id);
		if(empty($template_id)) return false;
		$this->db->prepare('SELECT DISTINCT(`iresource_id`) as `iresource_id` FROM `tmpl_roles` WHERE `template_id`='.$template_id.' AND `ir_type`>0');
		return $this->db->selectFromField('iresource_id');
	}#end function





	/*
	 * Проверка cуществования шаблона
	 */
	public function templateExists($template_id=0){
		if(empty($template_id)) return false;
		$this->db->prepare('SELECT count(*) FROM `templates` WHERE `template_id`=? LIMIT 1');
		$this->db->bind($template_id);
		return ($this->db->result() > 0);
	}#end function







	/*
	 * Добавление шаблона
	 */
	public function templateNew($fields=array()){

		if(empty($fields)) return false;
		$add_fields = array_intersect(array_keys($this->defaultTemplateRecord), array_keys($fields));
		$adds = array();
		if(empty($add_fields)) return false;
		foreach($add_fields as $add_field){
			$adds[$add_field] = $fields[$add_field];
		}
		$fields = array_merge($this->defaultTemplateRecord, $adds);
		$ins_names=array();
		$ins_q=array();
		$binds=array();
		foreach($fields as $field=>$value){
			$ins_names[]='`'.$field.'`';
			$ins_q[]='?';
			$binds[]=$value;
		}
		$this->db->prepare('INSERT INTO `templates` ('.implode(',',$ins_names).')VALUES('.implode(',',$ins_q).')');
		foreach($binds as $bind) $this->db->bind($bind);
		if(($template_id = $this->db->insert())===false) return false;

		return $template_id;
	}#end function





	/*
	 * Обновление информации о шаблоне
	 */
	public function templateUpdate($template_id=0, $fields=array()){

		if(empty($template_id)||empty($fields)) return false;
		$update_fields = array_intersect(array_keys($this->defaultTemplateRecord), array_keys($fields));
		if(empty($update_fields)) return false;
		$binds=array();
		$updates='';
		foreach($update_fields as $update_field){
			$updates.=(empty($updates)?'':',').'`'.$update_field.'`=?';
			$binds[]=$fields[$update_field];
		}
		$this->db->prepare('UPDATE `templates` SET '.$updates.' WHERE `template_id`=?');
		foreach($binds as $bind) $this->db->bind($bind);
		$this->db->bind($template_id);

		if($this->db->update()===false) return false;
		return true;
	}#end function





	/*
	 * Удаление шаблона
	 */
	public function templateDelete($template_id=0, $check_can_delete=true){

		if(empty($template_id)) return false;
		if($check_can_delete && !$this->templateCanDelete($template_id)) return false;

		//Проверяем наличие транзакции
		$in_transaction = $this->db->inTransaction();
		if(!$in_transaction) $this->db->transaction();

		//Удаление из templates
		$this->db->prepare('DELETE FROM `templates` WHERE `template_id`=?');
		$this->db->bind($template_id);
		if($this->db->delete()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		//Удаление из tmpl_roles
		$this->db->prepare('DELETE FROM `tmpl_roles` WHERE `template_id`=?');
		$this->db->bind($template_id);
		if($this->db->delete()===false){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		//Выполнено успешно
		if(!$in_transaction) $this->db->commit();

		return true;
	}#end function





	/*
	 * Проверка допустимости удаления шаблона
	 */
	public function templateCanDelete($template_id=0){

		return true;

	}#end function




	/*==============================================================================================
	ФУНКЦИИ: Работа с ролями доступа в шаблоне
	==============================================================================================*/


	/*
	 * Очистка шаблона доступа
	 */
	function templateEmpty($template_id=0, $iresource_id=0){
		if(empty($template_id)) return false;
		if(empty($iresource_id)){
			$this->db->prepare('DELETE FROM `tmpl_roles` WHERE `template_id`=?');
			$this->db->bind($template_id);
		}else{
			$this->db->prepare('DELETE FROM `tmpl_roles` WHERE `template_id`=? AND `iresource_id`=?');
			$this->db->bind($template_id);
			$this->db->bind($iresource_id);
		}
		if($this->db->delete()===false) return false;
		return true;
	}#end function



	/*
	 * Получение списка ролей в шаблоне
	 */
	function templateRoles($template_id=0, $iresource_id=0, $raw_data=false){
		if(empty($template_id)) return false;
		$template_id = intval($template_id);
		$iresource_id = intval($iresource_id);
		if($raw_data){
			if(empty($iresource_id)){
				$this->db->prepare('SELECT * FROM `tmpl_roles` WHERE `template_id`=?');
				$this->db->bind($template_id);
			}else{
				$this->db->prepare('SELECT * FROM `tmpl_roles` WHERE `template_id`=? AND `iresource_id`=?');
				$this->db->bind($template_id);
				$this->db->bind($iresource_id);
			}
			if(($roles = $this->db->select())===false) return false;
			if(empty($roles)) return array();
			return $roles;
		}

		$iresources = ($iresource_id > 0 ? array($iresource_id) : $this->db->selectFromField('iresource_id','SELECT DISTINCT `iresource_id` FROM `tmpl_roles` WHERE `template_id`='.$template_id.(!empty($iroles_text)?' AND `irole_id` IN ('.$iroles_text.')':'')));

		if(empty($iresources)) return array();

		$result = array();

		//foreach: получение ролей
		foreach($iresources as $iresource_id){
			$iroles = $this->getTemplateIRoles($template_id, $iresource_id);
			if(empty($iroles)||!is_array($iroles)) continue;
			$result[] = array(
				'iresource_id'	=> $iresource_id,
				'areas'			=> $iroles['areas'],
				'items'			=> $iroles['items']
			);
		}//foreach: получение ролей


		return $result;
	}#end function




	/*
	 * Получение списка объектов доступа информационного ресурса для шаблона
	 */
	public function getTemplateIRoles($template_id=0, $iresource_id=0){

		#Получение списка объектов ИР
		$this->db->prepare('
			SELECT IROLE.*,
			IFNULL(TMPLROLE.`ir_type`,"0") as `ir_selected`
			FROM `iroles` as IROLE 
				LEFT JOIN `tmpl_roles` as TMPLROLE ON TMPLROLE.`template_id`=? AND TMPLROLE.`irole_id`=IROLE.`irole_id` AND TMPLROLE.`iresource_id`=IROLE.`iresource_id`
			WHERE IROLE.`iresource_id`=?
			ORDER BY IROLE.`full_name`
		');
		$this->db->bind($template_id);
		$this->db->bind($iresource_id);
		if( ($results = $this->db->select()) === false) return false;

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

		return array(
			'areas'	=> $areas,
			'items' => $items
		);
	}#end function







	/*
	 * Добавление шаблона
	 */
	public function templateRoleNew($fields=array()){

		if(empty($fields)) return false;
		$add_fields = array_intersect(array_keys($this->defaultTemplateRoleRecord), array_keys($fields));
		$adds = array();
		if(empty($add_fields)) return false;
		foreach($add_fields as $add_field){
			$adds[$add_field] = $fields[$add_field];
		}
		$fields = array_merge($this->defaultTemplateRoleRecord, $adds);
		$ins_names=array();
		$ins_q=array();
		$binds=array();
		foreach($fields as $field=>$value){
			$ins_names[]='`'.$field.'`';
			$ins_q[]='?';
			$binds[]=$value;
		}
		$this->db->prepare('INSERT INTO `tmpl_roles` ('.implode(',',$ins_names).')VALUES('.implode(',',$ins_q).')');
		foreach($binds as $bind) $this->db->bind($bind);
		if(($tmpl_id = $this->db->insert())===false) return false;

		return $tmpl_id;
	}#end function



	/*
	 * Проверка существования объекта в шаблоне
	 */
	public function templateRoleExists($template_id=0, $iresource_id=0, $irole_id=0){
		if(empty($template_id)||empty($iresource_id)||empty($irole_id)) return false;
		$this->db->prepare('SELECT count(*) FROM `tmpl_roles` WHERE `template_id`=? AND `iresource_id`=? AND `irole_id`=? LIMIT 1');
		$this->db->bind($template_id);
		$this->db->bind($iresource_id);
		$this->db->bind($irole_id);
		return ($this->db->result() > 0);
	}#end function



	/*
	 * Обновление информации объекта в шаблоне
	 */
	public function templateRoleUpdate($template_id=0, $iresource_id=0, $irole_id=0, $fields=array()){

		if(empty($template_id)||empty($fields)) return false;
		$update_fields = array_intersect(array_keys($this->defaultTemplateRoleRecord), array_keys($fields));
		if(empty($update_fields)) return false;
		$binds=array();
		$updates='';
		foreach($update_fields as $update_field){
			$updates.=(empty($updates)?'':',').'`'.$update_field.'`=?';
			$binds[]=$fields[$update_field];
		}
		$this->db->prepare('UPDATE `tmpl_roles` SET '.$updates.' WHERE `template_id`=? AND `iresource_id`=? AND `irole_id`=?');
		foreach($binds as $bind) $this->db->bind($bind);
		$this->db->bind($template_id);
		$this->db->bind($iresource_id);
		$this->db->bind($irole_id);

		if($this->db->update()===false) return false;
		return true;
	}#end function


}#end class

?>