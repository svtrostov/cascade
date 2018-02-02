var manager_acl_objects = {};

//Вход на страницу
function manager_acl_enter_page(success, status, data){
	manager_acl_start(data);
}//end function


//Выход со страницы
function manager_acl_exit_page(){
	['roletable','allowedtable','otable'].each(function(table,index){
		if(manager_acl_objects[table]) manager_acl_objects[table].terminate();
	});
	if(manager_acl_objects['oform_info']) manager_acl_objects['oform_info'].destroy();
	if(manager_acl_objects['oform_new']) manager_acl_objects['oform_new'].destroy();
	for(var i in manager_acl_objects){
		manager_acl_objects[i] = null;
	}
	manager_acl_objects = {};
	App.Location.removeEvent('beforeLoadPage', manager_acl_exit_page);
}//end function



//Инициализация процесса создания заявки
function manager_acl_start(data){
	App.Location.addEvent('beforeLoadPage', manager_acl_exit_page);

	manager_acl_objects['aobjects'] = [];
	manager_acl_objects['sobject']=null;
	manager_acl_objects['otypes'] = {};
	manager_acl_objects['objects_area_scroll'] = new Fx.Scroll($('objects_table_area_wrapper'));

	manager_acl_objects['tabs'] = new jsTabPanel('tabs_area',{
		'onchange': manager_acl_change_tab
	});

	manager_acl_objects['splitter'] = set_splitter_h({
		'left'		: $('objects_area'),
		'right'		: $('objects_info'),
		'splitter'	: $('objects_splitter'),
		'parent'	: $('tabs_area')
	});

	manager_acl_objects['splitter2'] = set_splitter_h({
		'left'		: $('roles_area'),
		'right'		: $('allowed_area'),
		'splitter'	: $('roles_splitter_handle'),
		'handle'	: $('roles_splitter'),
		'parent'	: $('tabs_area'),
		'min'		: 200,
		'max'		: 900
	});

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
				dataSource:'name'
			},
			{
				width:'150px',
				styles:{'min-width':'120px'},
				sortable:false,
				dataStyle:{'text-align':'left'},
				caption: 'Описание',
				dataSource:'desc'
			}
		],
		selectType:2
	};

	manager_acl_objects['roletable'] = new jsTable('roles_area_table',settings);
	manager_acl_objects['allowedtable'] = new jsTable('allowed_area_table',settings);
	settings['selectType'] = 1;
	manager_acl_objects['otable'] = new jsTable('objects_table_area', settings);
	manager_acl_objects['otable'].addEvent('click', manager_acl_select_object);


	manager_acl_objects['oinfo'] = build_blockitem({
		'parent': 'objects_info_area',
		'title'	: 'Свойства объекта ACL'
	});
	$('tmpl_object_info').show().inject(manager_acl_objects['oinfo']['container']);
	manager_acl_objects['oinfo']['li'].hide();
	$('button_delete_object').hide();

	manager_acl_objects['oform_info'] = new jsValidator('tmpl_object_info');
	manager_acl_objects['oform_info']
	.required('oinfo_object_id').numeric('oinfo_object_id')
	.required('oinfo_min_access_level').numeric('oinfo_min_access_level')
	.required('oinfo_name')
	.required('oinfo_desc');


	manager_acl_objects['onew'] = build_blockitem({
		'list': manager_acl_objects['oinfo']['list'],
		'title'	: 'Добавить объект ACL'
	});
	manager_acl_objects['onew']['li'].hide();
	$('tmpl_object_new').show().inject(manager_acl_objects['onew']['container']);

	manager_acl_objects['oform_new'] = new jsValidator('tmpl_object_new');
	manager_acl_objects['oform_new']
	.required('onew_min_access_level').numeric('onew_min_access_level')
	.required('onew_name')
	.required('onew_desc');

	$('manager_acl_objects_filter').addEvent('keydown',function(e){if(e.code==13) manager_acl_otable_filter();});
	$('manager_acl_objects_filter_button').addEvent('click',manager_acl_otable_filter);

	$('manager_acl_allowed_filter').addEvent('keydown',function(e){if(e.code==13) manager_acl_allowedtable_filter();});
	$('manager_acl_allowed_filter_button').addEvent('click',manager_acl_allowedtable_filter);

	$('button_object_exclude').addEvent('click',manager_acl_objects_exclude_from_role);
	$('button_object_include').addEvent('click',manager_acl_objects_include_to_role);

	manager_acl_dataset(data);
}//end function




//Смена вкладки
function manager_acl_change_tab(index){

	switch(index){

		//Объекты ACL
		case 0:
		
		break;

		//Контейнеры ролей
		case 1:
			manager_acl_set_role_list();
			manager_acl_select_role();
		break;

	}

}//end function




//Исключение объектов из контейнера роли
function manager_acl_objects_exclude_from_role(){
	var tr,data;
	var role_id = select_getValue('manager_acl_objects_roles_select');
	if(!manager_acl_objects['roletable'].selectedRows.length) return;
	var objects = [];
	for(var i=0; i<manager_acl_objects['roletable'].selectedRows.length;i++){
		tr = manager_acl_objects['roletable'].selectedRows[i];
		if(typeOf(tr)!='element') continue;
		data = tr.retrieve('data');
		if(typeOf(data)!='object') continue;
		objects.push(data['object_id']);
	}
	manager_acl_objects_role_container(role_id, objects, 'exclude');
}//end function




//Включение объектов в контейнер роли
function manager_acl_objects_include_to_role(){
	var role_id = select_getValue('manager_acl_objects_roles_select');
	if(!manager_acl_objects['allowedtable'].selectedRows.length) return;
	var objects = [];
	for(var i=0; i<manager_acl_objects['allowedtable'].selectedRows.length;i++){
		tr = manager_acl_objects['allowedtable'].selectedRows[i];
		if(typeOf(tr)!='element') continue;
		data = tr.retrieve('data');
		if(typeOf(data)!='object') continue;
		objects.push(data['object_id']);
	}
	manager_acl_objects_role_container(role_id, objects, 'include');
}//end function



//Операция включение, исключение объектов из контейнера роли
function manager_acl_objects_role_container(role_id, objects, action){
	new axRequest({
		url : '/admin/ajax/acl',
		data:{
			'action':'role.'+action,
			'role_id': role_id,
			'objects': objects
		},
		silent: false,
		waiter: true,
		callback: function(success, status, data){
			if(success){
				manager_acl_dataset(data);
				manager_acl_otable_filter(true);
				manager_acl_select_role();
			}
		}
	}).request();
}




//Фильтр данных таблицы объектов доступа
function manager_acl_otable_filter(no_clear_selected){
	manager_acl_objects['otable'].filter($('manager_acl_objects_filter').value);
	if(!no_clear_selected){
		manager_acl_objects['otable'].clearSelected();
		manager_acl_objects['sobject'] = null;
		manager_acl_objects['oinfo']['li'].hide();
	}
}//end function



//Фильтр данных таблицы доступных объектов для роли
function manager_acl_allowedtable_filter(no_clear_selected){
	manager_acl_objects['allowedtable'].filter($('manager_acl_allowed_filter').value);
	manager_acl_objects['allowedtable'].clearSelected();
}//end function




//Построение списка контейнеров ролей
function manager_acl_set_role_list(){
	select_add({
		'list': 'manager_acl_objects_roles_select',
		'key': 'object_id',
		'value': 'namedesc',
		'options': manager_acl_objects['aobjects'].filterSelect('type', 3, 0),
		'default': select_getValue('manager_acl_objects_roles_select'),
		'clear': true
	});
}//end function




//Выбор контейнера роли
function manager_acl_select_role(){

	if(typeOf(manager_acl_objects['otypes'])!='object' || typeOf(manager_acl_objects['aobjects'])!='array') return false;
	var role_id = select_getValue('manager_acl_objects_roles_select');
	var role_info = manager_acl_objects['aobjects'].filterRow('object_id',role_id);
	if(typeOf(role_info)!='object') return false;

	//Построение списка объектов, включенных в контейнер роли
	var objects = [];
	var childs = role_info['childs'].clone();
	for(var i in manager_acl_objects['otypes']){
		objects.push(manager_acl_objects['otypes'][i]['name']);
		objects.append(manager_acl_objects['aobjects'].filterSelect({
			'type': i,
			'object_id':{
				'value': childs,
				'condition': 'IN'
			}
		}));
	}
	manager_acl_objects['roletable'].setData(objects);

	//Построение списка объектов, не включенных в контейнер роли
	objects = [];
	childs.push(role_id);
	for(var i in manager_acl_objects['otypes']){
		objects.push(manager_acl_objects['otypes'][i]['name']);
		objects.append(manager_acl_objects['aobjects'].filterSelect({
			'type': i,
			'object_id':{
				'value': childs,
				'condition': 'NOTIN'
			}
		}));
	}
	manager_acl_objects['allowedtable'].setData(objects);
	manager_acl_allowedtable_filter();


	return true;
}//end function






//Применение данных
function manager_acl_dataset(data){

	if(typeOf(data)!='object') return;

	//Типы объектов
	if(typeOf(data['otypes'])=='array'){
		for(var i=0; i<data['otypes'].length;i++){
			manager_acl_objects['otypes'][data['otypes'][i][0]]={
				'type' : data['otypes'][i][0],
				'name' : data['otypes'][i][1],
				'count': 0
			};
		}
		select_add({
			'list': ['oinfo_type','onew_type'],
			'key': 0,
			'value': 1,
			'options': data['otypes'],
			'clear': true
		});
	}//Типы объектов

	//Массив объектов
	if(typeOf(data['aobjects'])=='array'){
		$('button_delete_object').hide();
		manager_acl_objects['sobject']=null;
		manager_acl_objects['oinfo']['li'].hide();
		manager_acl_objects['aobjects'] = data['aobjects'];
		var objects, a;

		if(typeOf(manager_acl_objects['otypes'])=='object'){
			objects = [];
			for(var i in manager_acl_objects['otypes']){
				objects.push(manager_acl_objects['otypes'][i]['name']);
				a = data['aobjects'].filterSelect('type', i, 0);
				manager_acl_objects['otypes'][i]['count'] = a.length;
				objects.append(a);
			}
		}else{
			objects = data['aobjects'];
		}

		manager_acl_objects['otable'].setData(objects);
	}//Типы объектов

	//Выбранный объект
	if(data['sobject']){
		manager_acl_objects['sobject'] = null;
		manager_acl_objects['otable'].selectOf([String(data['sobject'])],1);
		manager_acl_select_object();
	}


	//Контейнеры ролей
	if(typeOf(manager_acl_objects['otypes'][3])!='object' || !manager_acl_objects['otypes'][3]['count']){
		$('containers_roles_area').hide();
		$('containers_roles_none').show();
	}else{
		$('containers_roles_none').hide();
		$('containers_roles_area').show();
	}

}//end function





//Выбран объект ACL
function manager_acl_select_object(){
	$('button_delete_object').hide();
	manager_acl_objects['oinfo']['li'].hide();
	manager_acl_objects['onew']['li'].hide();
	manager_acl_objects['sobject']=null;
	if(!manager_acl_objects['otable'].selectedRows.length) return;
	var tr = manager_acl_objects['otable'].selectedRows[0];
	if(typeOf(tr)!='element') return;
	var data = tr.retrieve('data');
	if(typeOf(data)!='object') return;
	$('oinfo_object_id').value = data['object_id'];
	$('oinfo_name').value = data['name'];
	$('oinfo_desc').value = data['desc'];
	$('oinfo_min_access_level').value = data['min_access_level'];
	select_set('oinfo_type',data['type']);
	$('oinfo_is_lock').checked = String(data['is_lock'])=='1'?true:false;
	$('oinfo_for_all_companies').checked = String(data['for_all_companies'])=='1'?true:false;
	manager_acl_objects['sobject']=data;
	manager_acl_objects['oinfo']['li'].show();
	$('button_delete_object').show();
	manager_acl_objects['objects_area_scroll'].toElement(tr);
}//end function




//Изменение объекта ACL
function manager_acl_object_change_save(){

	if(typeOf(manager_acl_objects['sobject'])!='object') return;
	if(!manager_acl_objects['oform_info'].validate()) return;

	new axRequest({
		url : '/admin/ajax/acl',
		data:{
			'action':'object.edit',
			'object_id': $('oinfo_object_id').value,
			'type': select_getValue('oinfo_type'),
			'is_lock': ($('oinfo_is_lock').checked?'1':'0'),
			'name': $('oinfo_name').value,
			'desc': $('oinfo_desc').value,
			'min_access_level': $('oinfo_min_access_level').value,
			'for_all_companies': ($('oinfo_for_all_companies').checked?'1':'0')
		},
		silent: false,
		waiter: true,
		callback: function(success, status, data){
			if(success){
				manager_acl_dataset(data);
				manager_acl_otable_filter(true);
			}
		}
	}).request();

}//end function



//Добавление объекта ACL
function manager_acl_object_new(){
	if(!manager_acl_objects['otable']) return;
	manager_acl_objects['otable'].clearSelected();
	manager_acl_objects['sobject'] = null;
	manager_acl_objects['oinfo']['li'].hide();
	manager_acl_objects['onew']['li'].show();
	$('button_delete_object').hide();
}//end function




//Изменение объекта ACL
function manager_acl_object_new_save(){

	if(!manager_acl_objects['oform_new'].validate()) return;

	new axRequest({
		url : '/admin/ajax/acl',
		data:{
			'action':'object.new',
			'type': select_getValue('onew_type'),
			'is_lock': ($('onew_is_lock').checked?'1':'0'),
			'name': $('onew_name').value,
			'desc': $('onew_desc').value,
			'min_access_level': $('onew_min_access_level').value,
			'for_all_companies': ($('onew_for_all_companies').checked?'1':'0')
		},
		silent: false,
		waiter: true,
		callback: function(success, status, data){
			if(success){
				manager_acl_dataset(data);
			}
		}
	}).request();

}//end function





//Удаление объекта ACL
function manager_acl_object_delete(){

	if(typeOf(manager_acl_objects['sobject'])!='object' || String(manager_acl_objects['sobject']['object_id']) != String($('oinfo_object_id').value)) return;
	var object_id = String($('oinfo_object_id').value);

	App.message(
		'Подтвердите действие',
		'Вы действительно хотите удалить выбранный объект?<br/>Объект также будет укдален из прав доступа пользователей, контейнеров ролей и иных связных мест',
		'CONFIRM',
		function(){
			new axRequest({
				url : '/admin/ajax/acl',
				data:{
					'action':'object.delete',
					'object_id': object_id
				},
				silent: false,
				waiter: true,
				callback: function(success, status, data){
					if(success){
						manager_acl_dataset(data);
					}
				}
			}).request();
		}
	);

}//end function

