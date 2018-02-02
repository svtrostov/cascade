var manager_access_objects = {};

//Вход на страницу
function manager_access_enter_page(success, status, data){
	manager_access_start(data);
}//end function


//Выход со страницы
function manager_access_exit_page(){
	['etable','accesstable','aselected','aobjects'].each(function(table,index){
		if(manager_access_objects[table]) manager_access_objects[table].terminate();
	});
	for(var i in manager_access_objects){
		manager_access_objects[i] = null;
	}
	manager_access_objects = {};
	App.Location.removeEvent('beforeLoadPage', manager_access_exit_page);
}//end function



//Инициализация процесса создания заявки
function manager_access_start(data){
	App.Location.addEvent('beforeLoadPage', manager_access_exit_page);

	manager_access_objects['selected_employer']=null;
	manager_access_objects['companies_array']=null;
	manager_access_objects['companies_assoc']=null;
	manager_access_objects['objects_array']=null;
	manager_access_objects['objects_assoc']=null;
	manager_access_objects['otypes']={};

	if(typeOf(data)!='object'){
		$('employers_list_none').show();
		$('employers_list_area').hide();
		return;
	}
	$('employers_list_none').hide();

	manager_access_objects['splitter'] = set_splitter_h({
		'left'		: $('employers_area'),
		'right'		: $('employers_info'),
		'splitter'	: $('employers_splitter'),
		'parent'	: $('employers_splitter').getParent('.contentareafull')
	});

	manager_access_objects['etable'] = new jsTable('employers_table_area', {
		'dataBackground1':'#efefef',
		'class': 'jsTableLight',
		columns: [
			{
				width:'50px',
				sortable:false,
				caption: 'ID',
				styles:{'min-width':'50px'},
				dataStyle:{'text-align':'center'},
				dataSource:'employer_id'
			},
			{
				width:'auto',
				sortable:false,
				caption: 'ФИО сотрудника',
				styles:{'min-width':'120px'},
				dataStyle:{'text-align':'left'},
				dataSource:'search_name'
			},
			{
				width:'100px',
				styles:{'min-width':'100px'},
				sortable:false,
				dataStyle:{'text-align':'center'},
				caption: 'Дата рождения',
				dataSource:'birth_date'
			}
		],
		selectType:1
	});
	manager_access_objects['etable'].addEvent('click', manager_access_select_employer);
	$('manager_access_employers_filter').addEvent('keydown',function(e){if(e.code==13) manager_access_etable_filter();});
	$('manager_access_employers_filter_button').addEvent('click',manager_access_etable_filter);

	manager_access_objects['einfo'] = build_blockitem({
		'parent': 'employers_info_area',
		'title'	: 'Права доступа сотрудника'
	});
	manager_access_objects['einfo']['container'].setStyles({
		'padding': '0px',
		'margin': '0px'
	});
	manager_access_objects['eaction'] = build_blockitem({
		'list': manager_access_objects['einfo']['list'],
		'title'	: 'Действия с отмеченными объектами'
	});
	$('tmpl_access_actions').show().inject(manager_access_objects['eaction']['container']);
	$('employers_info_area').hide();

	var settings = {
		sectionCollapsible:true,
		'dataBackground1':'#efefef',
		'class': 'jsTableLight',
		columns: [
			{
				width:'50px',
				sortable:false,
				caption: 'ID',
				styles:{'min-width':'50px'},
				dataStyle:{'text-align':'center'},
				dataSource:'object_id'
			},
			{
				width:'120px',
				sortable:false,
				caption: 'Имя',
				styles:{'min-width':'120px'},
				dataStyle:{'text-align':'left'},
				dataSource:'object_id',
				dataFunction:function(table, cell, text, data){
					if(typeOf(manager_access_objects['objects_assoc'])!='object'||typeOf(manager_access_objects['objects_assoc'][data['object_id']])!='object') return '???, ID='+data['object_id'];
					return manager_access_objects['objects_assoc'][data['object_id']]['name'];
				}
			},
			{
				width:'150px',
				styles:{'min-width':'120px'},
				sortable:false,
				dataStyle:{'text-align':'left'},
				caption: 'Описание',
				dataSource:'object_id',
				dataFunction:function(table, cell, text, data){
					if(typeOf(manager_access_objects['objects_assoc'])!='object'||typeOf(manager_access_objects['objects_assoc'][data['object_id']])!='object') return '???, ID='+data['object_id'];
					return manager_access_objects['objects_assoc'][data['object_id']]['desc'];
				}
			},
			{
				width:'150px',
				styles:{'min-width':'120px'},
				sortable:false,
				dataStyle:{'text-align':'left'},
				caption: 'Организация',
				dataSource:'company_id',
				dataFunction:function(table, cell, text, data){
					if(String(data['company_id'])=='0') return 'Все организации';
					if(typeOf(manager_access_objects['companies_assoc'])!='object'||!manager_access_objects['companies_assoc'][data['company_id']]) return '???, ID='+data['company_id'];
					return manager_access_objects['companies_assoc'][data['company_id']];
				}
			},
			{
				width:'50px',
				sortable:false,
				caption: 'Запрет',
				styles:{'min-width':'50px'},
				dataStyle:{'text-align':'center'},
				dataSource:'is_restrict',
				dataFunction:function(table, cell, text, data){
					if(String(data['is_restrict'])=='0') return 'Нет';
					return 'Да';
				}
			}
		],
		selectType:2
	};
	manager_access_objects['accesstable'] = new jsTable(manager_access_objects['einfo']['container'], settings);
	manager_access_objects['accesstable'].addEvent('click', manager_access_select_access);


	manager_access_objects['splitter2'] = set_splitter_h({
		'left'		: $('access_add_selected_area'),
		'right'		: $('access_add_objects_area'),
		'splitter'	: $('access_add_splitter_handle'),
		'handle'	: $('access_add_splitter'),
		'parent'	: $('new_access_area'),
		'min'		: 250,
		'max'		: 700
	});


	manager_access_objects['aselected'] = new jsTable('access_add_selected_table', {
		'dataBackground1':'#efefef',
		'class': 'jsTableLight',
		columns: [
			{
				width:'auto',
				sortable:false,
				caption: 'Объект доступа',
				styles:{'min-width':'120px'},
				dataStyle:{'text-align':'left'},
				dataSource:'object_id',
				dataFunction:function(table, cell, text, data){
					if(typeOf(manager_access_objects['objects_assoc'])!='object'||typeOf(manager_access_objects['objects_assoc'][data['object_id']])!='object') return '???, ID='+data['object_id'];
					return manager_access_objects['objects_assoc'][data['object_id']]['name']+'<br/><span class="small">'+manager_access_objects['objects_assoc'][data['object_id']]['desc']+'</span>';
				}
			},
			{
				width:'150px',
				styles:{'min-width':'120px'},
				sortable:false,
				dataStyle:{'text-align':'left'},
				caption: 'Организация',
				dataSource:'company_id',
				dataFunction:function(table, cell, text, data){
					if(String(data['company_id'])=='0') return 'Все организации';
					if(typeOf(manager_access_objects['companies_assoc'])!='object'||!manager_access_objects['companies_assoc'][data['company_id']]) return '???, ID='+data['company_id'];
					return manager_access_objects['companies_assoc'][data['company_id']];
				}
			},
			{
				width:'50px',
				sortable:false,
				caption: 'Запрет',
				styles:{'min-width':'50px'},
				dataStyle:{'text-align':'center'},
				dataSource:'is_restrict',
				dataFunction:function(table, cell, text, data){
					if(String(data['is_restrict'])=='0') return 'Нет';
					return 'Да';
				}
			}
		],
		selectType:2
	});



	manager_access_objects['aobjects'] = new jsTable('access_add_objects_table', {
		sectionCollapsible:true,
		'dataBackground1':'#efefef',
		'class': 'jsTableLight',
		columns: [
			{
				width:'50px',
				sortable:false,
				caption: 'ID',
				styles:{'min-width':'50px'},
				dataStyle:{'text-align':'center'},
				dataSource:'object_id'
			},
			{
				width:'auto',
				sortable:false,
				caption: 'Объект доступа',
				styles:{'min-width':'120px'},
				dataStyle:{'text-align':'left'},
				dataSource:'object_id',
				dataFunction:function(table, cell, text, data){
					if(typeOf(manager_access_objects['objects_assoc'])!='object'||typeOf(manager_access_objects['objects_assoc'][data['object_id']])!='object') return '???, ID='+data['object_id'];
					return manager_access_objects['objects_assoc'][data['object_id']]['name'];
				}
			},
			{
				width:'auto',
				sortable:false,
				caption: 'Описание',
				styles:{'min-width':'120px'},
				dataStyle:{'text-align':'left'},
				dataSource:'object_id',
				dataFunction:function(table, cell, text, data){
					if(typeOf(manager_access_objects['objects_assoc'])!='object'||typeOf(manager_access_objects['objects_assoc'][data['object_id']])!='object') return '???, ID='+data['object_id'];
					return manager_access_objects['objects_assoc'][data['object_id']]['desc'];
				}
			},
			{
				width:'200px',
				styles:{'min-width':'120px'},
				sortable:false,
				dataStyle:{'text-align':'left'},
				caption: 'Организация',
				dataSource:'company_id',
				dataFunction:function(table, cell, text, data){
					if(String(data['for_all_companies'])=='1'){
						select_add({
							'parent': cell,
							'key': 0,
							'value': 1,
							'options': [['0','-[Только все организации]-']]
						}).addEvent('click',Function.stopEvent).set('name','company_id').setStyle('width','100%');
						return '';
					}
					$('eaction_company_select').clone().setStyle('width','100%').inject(cell).addEvent('click',Function.stopEvent).set('name','company_id');
					return '';
				}
			},
			{
				width:'50px',
				sortable:false,
				caption: 'Запрет',
				styles:{'min-width':'50px'},
				dataStyle:{'text-align':'center'},
				dataSource:'is_restrict',
				dataFunction:function(table, cell, text, data){
					var list = select_add({
						'parent': cell,
						'key': 0,
						'value': 1,
						'options': [['0','Нет'],['1','Да']]
					}).addEvent('click',Function.stopEvent).set('name','is_restrict');
					return '';
				}
			}
		],
		selectType:2
	});

	$('button_object_exclude').addEvent('click',manager_access_add_exclude);
	$('button_object_include').addEvent('click',manager_access_add_include);

	manager_access_objects['tabs'] = new jsTabPanel('tabs_area',{});

	manager_access_dataset(data);
}//end function





//Фильтр данных таблицы сотрудников
function manager_access_etable_filter(no_clear_selected){
	//Фильтрация данных
	new axRequest({
		url : '/admin/ajax/employers',
		data:{
			'action':'employers.search',
			'search_name': $('manager_access_employers_filter').getValue(),
			'status': '1',
			'extended': 'employers_list'
		},
		silent: true,
		waiter: true,
		callback: function(success, status, data){
			if(success){
				manager_access_dataset(data);
			}
		}
	}).request();
	/*
	manager_access_objects['etable'].filter($('manager_access_employers_filter').value);
	if(!no_clear_selected){
		manager_access_objects['etable'].clearSelected();
		$('employers_info_area').hide();
	}
	**/
}//end function



//Применение данных
function manager_access_dataset(data){

	//Типы объектов
	if(typeOf(data['otypes'])=='array'){
		for(var i=0; i<data['otypes'].length;i++){
			manager_access_objects['otypes'][data['otypes'][i][0]]={
				'type' : data['otypes'][i][0],
				'name' : data['otypes'][i][1]
			};
		}
	}//Типы объектов


	//Список сотрудников
	if(typeOf(data['employers'])=='array'){
		manager_access_objects['etable'].setData(data['employers']);
	}//Список сотрудников


	//Массив сотрудников поиск
	if(typeOf(data['employers_search'])=='array'){
		manager_access_objects['etable'].setData(data['employers_search']);
	}//Массив сотрудников поиск


	//Список организаций
	if(typeOf(data['companies'])=='array'){
		manager_access_objects['companies_array']=[{'company_id':'0','company_name':'-[Все организации]-'}];
		manager_access_objects['companies_assoc']={};
		for(var i=0;i<data['companies'].length;i++){
			manager_access_objects['companies_array'].push(data['companies'][i]);
			manager_access_objects['companies_assoc'][data['companies'][i]['company_id']]=data['companies'][i]['company_name'];
		}
		select_add({
			'list': 'eaction_company_select',
			'key': 'company_id',
			'value': 'company_name',
			'options': manager_access_objects['companies_array'],
			'default': 0,
			'clear': true
		});
	}//Список организаций


	//Массив объектов
	if(typeOf(data['aobjects'])=='array'){
		manager_access_objects['objects_array']=[];
		manager_access_objects['objects_assoc']={};
		for(var i=0;i<data['aobjects'].length;i++){
			manager_access_objects['objects_array'].push(data['aobjects'][i]);
			manager_access_objects['objects_assoc'][data['aobjects'][i]['object_id']]=data['aobjects'][i];
		}
	}//Массив объектов


	//Список объектов доступа сотрудника
	if(typeOf(data['eaccess'])=='array'){
		if(data['eaccess'].length > 0){
			var objects, a;

			if(typeOf(manager_access_objects['otypes'])=='object'){
				objects = [];
				for(var i in manager_access_objects['otypes']){
					objects.push(manager_access_objects['otypes'][i]['name']);
					a = data['eaccess'].filterSelect('type', i, 0);
					manager_access_objects['otypes'][i]['count'] = a.length;
					objects.append(a);
				}
			}else{
				objects = data['eaccess'];
			}
			manager_access_objects['accesstable'].setData(objects);
			$('employers_info_area').show();
		}else{
			$('employers_info_area').hide();
		}
	}//Список объектов доступа сотрудника



	//Трассировка доступа
	if(typeOf(data['explain'])=='array'){
		$('acl_explain_area').empty();
		if(data['explain'].length > 0){
			var ul = new Element('ol',{
				'class': 'acl_list'
			});
			for(var i=0;i<data['explain'].length;i++){
				new Element('li',{
					'text': data['explain'][i]
				}).inject(ul);
			}
			ul.inject($('acl_explain_area'));
		}
	}//Трассировка доступа



	//Итоговый доступ
	if(typeOf(data['access'])=='object'){
		$('acl_objects_area').empty();
		if(data['explain'].length > 0){
			var ul = new Element('ol',{
				'class': 'acl_list'
			});
			var cid, company_name, object_name;
			for(var c in data['access']){
				cid = String(c).replace(/[c]/g,'');
				if(cid!='0'){
					if(!manager_access_objects['companies_assoc'][cid]) continue;
					company_name = manager_access_objects['companies_assoc'][cid];
				}else{
					company_name = 'Все организации';
				}
				if(typeOf(data['access'][c])!='array'||!data['access'][c].length) continue;
				new Element('li',{
					'text': company_name,
					'class':'header'
				}).inject(ul);
				/*
				if(typeOf(data['access'][c])!='array'||!data['access'][c].length){
					new Element('li',{
						'text': 'Нет объектов доступа'
					}).inject(ul);
					continue;
				}
				*/
				for(var i=0;i<data['access'][c].length;i++){
					if(typeOf(manager_access_objects['objects_assoc'][data['access'][c][i]])=='object'){
						object_name = manager_access_objects['objects_assoc'][data['access'][c][i]]['namedesc'];
					}else{
						object_name = '???, ID='+data['access'][c][i];
					}
					new Element('li',{
						'text': object_name
					}).inject(ul);
				}
			}
			ul.inject($('acl_objects_area'));
		}
	}//Итоговый доступ


}//end function




//Выбор сотрудника из списка сотрудников
function manager_access_select_employer(){
	$('employers_info_area').hide();
	manager_access_objects['eaction']['li'].hide();
	manager_access_objects['selected_employer']=null;
	if(!manager_access_objects['etable'].selectedRows.length) return;
	var tr = manager_access_objects['etable'].selectedRows[0];
	if(typeOf(tr)!='element') return;
	var data = tr.retrieve('data');
	if(typeOf(data)!='object') return;
	manager_access_objects['selected_employer']=data;
	new axRequest({
		url : '/admin/ajax/acl',
		data:{
			'action':'employer.access.get',
			'employer_id': data['employer_id']
		},
		silent: false,
		waiter: true,
		callback: function(success, status, data){
			if(success){
				manager_access_dataset(data);
			}
		}
	}).request();

}//end function




//Выбор объекта доступа сотрудника
function manager_access_select_access(){
	manager_access_objects['eaction']['li'].hide();
	if(!manager_access_objects['accesstable'].selectedRows.length) return;
	manager_access_objects['eaction']['li'].show();
}//end function





//Снятие/установка запрета на объекты доступа
function manager_access_eaction(action, value){
	if(!manager_access_objects['accesstable'].selectedRows.length) return;
	if(typeOf(manager_access_objects['selected_employer'])!='object') return;
	var employer_id = manager_access_objects['selected_employer']['employer_id'];
	var message = '';
	switch(action){
		case 'restrict':
			if(!value){
				message = 'снять запрет с выбранных объектов доступа';
				value = 0;
			}else{
				message = 'установить запрет на выбранные объекты доступа';
				value = 1;
			}
		break;
		case 'company':
			message = 'изменить организацию в которой доступны выбранные объекты доступа на <br/>'+select_getText('eaction_company_select');
			value = select_getValue('eaction_company_select');
		break;
		case 'delete':
			message = 'удалить выбранные объекты доступа';
			value = '';
		break;
		default: return;
	}
	var objects = [];
	for(var i=0; i<manager_access_objects['accesstable'].selectedRows.length;i++){
		tr = manager_access_objects['accesstable'].selectedRows[i];
		if(typeOf(tr)!='element') continue;
		data = tr.retrieve('data');
		if(typeOf(data)!='object') continue;
		objects.push(data['id']);
	}
	if(objects.length==0) return;

	App.message(
		'Подтвердите действие',
		'Вы действительно хотите '+message,
		'CONFIRM',
		function(){
			new axRequest({
				url : '/admin/ajax/acl',
				data:{
					'action':'employer.access.action',
					'employer_id': employer_id,
					'type': action,
					'value': value,
					'objects': objects
				},
				silent: false,
				waiter: true,
				callback: function(success, status, data){
					if(success){
						manager_access_dataset(data);
					}
				}
			}).request();
		}
	);


}//end function




//Добавление объектов доступа сотруднику - закрытие панели
function manager_access_add_close(){
	$('new_access_wrapper').hide();
	$('employers_wrapper').show();
}//end function




//Добавление объектов доступа сотруднику
function manager_access_add(){
	if(typeOf(manager_access_objects['selected_employer'])!='object') return;
	manager_access_objects['aselected_data']=[];
	var objects, a;

	if(typeOf(manager_access_objects['otypes'])=='object'){
		objects = [];
		for(var i in manager_access_objects['otypes']){
			objects.push(manager_access_objects['otypes'][i]['name']);
			a = manager_access_objects['objects_array'].filterSelect('type', i, 0);
			manager_access_objects['otypes'][i]['count'] = a.length;
			objects.append(a);
		}
	}else{
		objects = manager_access_objects['objects_array'];
	}
	manager_access_objects['aobjects'].setData(objects);

	manager_access_objects['aselected'].setData(manager_access_objects['aselected_data']);
	$('employers_wrapper').hide();
	$('new_access_wrapper').show();
	$('new_access_title').set('text','Добавление прав сотруднику ID:'+manager_access_objects['selected_employer']['employer_id']+' '+manager_access_objects['selected_employer']['search_name']);
}//end function





//Выбор добавляемых объектов
function manager_access_add_include(){
	if(!manager_access_objects['aobjects'].selectedRows.length) return;
	if(typeOf(manager_access_objects['selected_employer'])!='object') return;
	var objects = [], data, tr, company_id, is_restrict;
	for(var i=0; i<manager_access_objects['aobjects'].selectedRows.length;i++){
		tr = manager_access_objects['aobjects'].selectedRows[i];
		if(typeOf(tr)!='element') continue;
		data = tr.retrieve('data');
		if(typeOf(data)!='object') continue;
		company_id = tr.getElement('select[name=company_id]');
		is_restrict = tr.getElement('select[name=is_restrict]');
		if(typeOf(company_id)!='element'||typeOf(is_restrict)!='element') continue;
		manager_access_objects['aselected_data'].push({
			'object_id': data['object_id'],
			'name': data['name'],
			'desc': data['desc'],
			'company_id': select_getValue(company_id),
			'is_restrict': select_getValue(is_restrict)
		});
	}
	manager_access_objects['aobjects'].clearSelected();
	manager_access_objects['aselected'].setData(manager_access_objects['aselected_data']);
}//end function




//Удаление добавляемых объектов
function manager_access_add_exclude(){
	if(!manager_access_objects['aselected'].selectedRows.length) return;
	if(typeOf(manager_access_objects['selected_employer'])!='object') return;
	var objects = [], data, tr, company_id, is_restrict;
	for(var i=0; i<manager_access_objects['aselected'].selectedRows.length;i++){
		tr = manager_access_objects['aselected'].selectedRows[i];
		if(typeOf(tr)!='element') continue;
		data = tr.retrieve('data');
		if(typeOf(data)!='object') continue;
		for(var j=0;j<manager_access_objects['aselected_data'].length;j++){
			if(
				manager_access_objects['aselected_data'][j]['object_id']==data['object_id']&&
				manager_access_objects['aselected_data'][j]['company_id']==data['company_id']&&
				manager_access_objects['aselected_data'][j]['is_restrict']==data['is_restrict']
			){
				manager_access_objects['aselected_data'].splice(j, 1);
				break; 
			}
		}
	}
	manager_access_objects['aselected'].setData(manager_access_objects['aselected_data']);
}//end function




//Непосредственное добавление выбранных объектов доступа сотруднику
function manager_access_add_complete(){
	$('new_access_wrapper').hide();
	$('employers_wrapper').show();
	if(!manager_access_objects['aselected_data'].length) return;
	if(typeOf(manager_access_objects['selected_employer'])!='object') return;
	var object_ids=[], company_ids=[], restricted=[];
	var employer_id = manager_access_objects['selected_employer']['employer_id'];
	for(var i=0; i<manager_access_objects['aselected_data'].length;i++){
		object_ids.push(manager_access_objects['aselected_data'][i]['object_id']);
		company_ids.push(manager_access_objects['aselected_data'][i]['company_id']);
		restricted.push(manager_access_objects['aselected_data'][i]['is_restrict']);
	}
	new axRequest({
		url : '/admin/ajax/acl',
		data:{
			'action':'employer.access.add',
			'employer_id': employer_id,
			'o': object_ids,
			'c': company_ids,
			'r': restricted
		},
		silent: false,
		waiter: true,
		callback: function(success, status, data){
			if(success){
				manager_access_dataset(data);
			}
		}
	}).request();
}//end function




//Получение итогового доступа сотрудника к объектам
function manager_access_get_privs(){
	if(typeOf(manager_access_objects['selected_employer'])!='object') return;
	var employer_id = manager_access_objects['selected_employer']['employer_id'];
	$('acl_employer_access_title').set('text','Итоговый доступ сотрудника ID:'+manager_access_objects['selected_employer']['employer_id']+' '+manager_access_objects['selected_employer']['search_name']);
	new axRequest({
		url : '/admin/ajax/acl',
		data:{
			'action':'employer.privs.get',
			'employer_id': employer_id
		},
		silent: false,
		waiter: true,
		callback: function(success, status, data){
			if(success){
				manager_access_dataset(data);
				$('employers_wrapper').hide();
				$('acl_employer_access_wrapper').show();
			}
		}
	}).request();
}//end function




//Закрытие окна просмотра итогового доступа сотрудника к объектам
function manager_access_privs_close(){
	$('employers_wrapper').show();
	$('acl_employer_access_wrapper').hide();
}//end function

