var employers_groups_objects = {};

//Вход на страницу
function employers_groups_enter_page(success, status, data){
	employers_groups_start(data);
}//end function


//Выход со страницы
function employers_groups_exit_page(){
	['otable','grouptable','alltable'].each(function(table,index){
		if(employers_groups_objects[table]) employers_groups_objects[table].terminate();
	});
	if(employers_groups_objects['oform_info']) employers_groups_objects['oform_info'].destroy();
	if(employers_groups_objects['oform_new']) employers_groups_objects['oform_new'].destroy();
	for(var i in employers_groups_objects){
		employers_groups_objects[i] = null;
	}
	employers_groups_objects = {};
	App.Location.removeEvent('beforeLoadPage', employers_groups_exit_page);
}//end function



//Инициализация процесса
function employers_groups_start(data){
	App.Location.addEvent('beforeLoadPage', employers_groups_exit_page);

	employers_groups_objects['employers'] = null;
	employers_groups_objects['groups'] = [];
	employers_groups_objects['sobject'] = null;
	employers_groups_objects['groups_area_scroll'] = new Fx.Scroll($('groups_table_area_wrapper'));

	employers_groups_objects['tabs'] = new jsTabPanel('tabs_area',{
		'onchange': employers_groups_change_tab
	});

	employers_groups_objects['splitter'] = set_splitter_h({
		'left'		: $('groups_area'),
		'right'		: $('groups_info'),
		'splitter'	: $('groups_splitter'),
		'parent'	: $('tabs_area')
	});

	employers_groups_objects['splitter2'] = set_splitter_h({
		'left'		: $('selected_group_area'),
		'right'		: $('all_employers_area'),
		'splitter'	: $('group_employers_splitter_handle'),
		'handle'	: $('group_employers_splitter'),
		'parent'	: $('tabs_area')
	});

	var settings = {
		'dataBackground1':'#efefef',
		'class': 'jsTableLight',
		columns: [
			{
				width:'50px',
				sortable:true,
				caption: 'ID',
				styles:{'min-width':'50px'},
				dataStyle:{'text-align':'center'},
				dataSource:'group_id',
				dataType: 'num'
			},
			{
				width:'200px',
				sortable:true,
				caption: 'Группа',
				styles:{'min-width':'160px'},
				dataStyle:{'text-align':'left'},
				dataSource:'full_name'
			}
		],
		selectType:1
	};

	employers_groups_objects['otable'] = new jsTable('groups_table_area', settings);
	employers_groups_objects['otable'].addEvent('click', employers_groups_select);

	settings = {
		'dataBackground1':'#efefef',
		'class': 'jsTableLight',
		columns: [
			{
				width:'50px',
				sortable:true,
				caption: 'ID',
				styles:{'min-width':'50px'},
				dataStyle:{'text-align':'center'},
				dataSource:'employer_id',
				dataType: 'num'
			},
			{
				width:'100px',
				sortable:true,
				caption: 'Логин',
				styles:{'min-width':'80px'},
				dataStyle:{'text-align':'left'},
				dataSource:'username'
			},
			{
				width:'200px',
				sortable:true,
				caption: 'ФИО сотрудника',
				styles:{'min-width':'160px'},
				dataStyle:{'text-align':'left'},
				dataSource:'search_name',
				dataFunction:function(table, cell, text, data){
					return '<a target="_blank" class="mailto" href="/admin/employers/info?employer_id='+data['employer_id']+'">'+text+'</a>';
				}
			}
		],
		selectType:2
	};
	employers_groups_objects['grouptable'] = new jsTable('included_employers_area_table', settings);
	settings['columns'].push({
		width:'100px',
		sortable:true,
		caption: 'Дата рождения',
		styles:{'min-width':'80px'},
		dataStyle:{'text-align':'center'},
		dataSource:'birth_date'
	});
	employers_groups_objects['alltable'] = new jsTable('all_employers_area_table', settings);




	employers_groups_objects['oinfo'] = build_blockitem({
		'parent': 'groups_info_area',
		'title'	: 'Свойства группы'
	});
	$('tmpl_group_info').show().inject(employers_groups_objects['oinfo']['container']);
	employers_groups_objects['oinfo']['li'].hide();
	$('button_delete_group').hide();

	employers_groups_objects['oform_info'] = new jsValidator('tmpl_group_info');
	employers_groups_objects['oform_info']
	.required('oinfo_group_id').numeric('oinfo_group_id')
	.required('oinfo_full_name')
	.required('oinfo_short_name');


	employers_groups_objects['onew'] = build_blockitem({
		'list': employers_groups_objects['oinfo']['list'],
		'title'	: 'Добавить группу'
	});
	employers_groups_objects['onew']['li'].hide();
	$('tmpl_group_new').show().inject(employers_groups_objects['onew']['container']);

	employers_groups_objects['oform_new'] = new jsValidator('tmpl_group_new');
	employers_groups_objects['oform_new'].required('onew_full_name').required('onew_short_name');

	$('employers_groups_filter').addEvent('keydown',function(e){if(e.code==13) employers_groups_otable_filter();});
	$('employers_groups_filter_button').addEvent('click',employers_groups_otable_filter);

	$('employers_groups_all_filter').addEvent('keydown',function(e){if(e.code==13) employers_groups_alltable_filter();});
	$('employers_groups_all_filter_button').addEvent('click',employers_groups_alltable_filter);

	$('button_employers_include').addEvent('click',employers_groups_include_to_group);
	$('button_employers_exclude').addEvent('click',employers_groups_exclude_from_group);

	employers_groups_dataset(data);
}//end function




//Смена вкладки
function employers_groups_change_tab(index){

	switch(index){

		//Список групп
		case 0:
		
		break;

		//Группировка сотрудников
		case 1:
			employers_groups_set_list();
			employers_groups_select_group();
		break;

	}

}//end function





//Фильтр данных таблицы списка
function employers_groups_otable_filter(no_clear_selected){
	employers_groups_objects['otable'].filter($('employers_groups_filter').value);
	if(!no_clear_selected){
		employers_groups_objects['otable'].clearSelected();
		employers_groups_objects['sobject'] = null;
		employers_groups_objects['oinfo']['li'].hide();
	}
}//end function




//Применение данных
function employers_groups_dataset(data){

	if(typeOf(data)!='object') return;

	//Массив групп
	if(typeOf(data['groups'])=='array'){
		$('button_delete_group').hide();
		employers_groups_objects['groups'] = data['groups'];
		employers_groups_objects['sobject']=null;
		employers_groups_objects['oinfo']['li'].hide();
		employers_groups_objects['otable'].setData(data['groups']);
	}//Массив групп


	//Массив сотрудников группы
	if(typeOf(data['employers'])=='array'){
		//employers_groups_objects['employers'] = data['employers'];
		employers_groups_objects['grouptable'].setData(data['employers']);
	}//Массив сотрудников группы



	//Массив сотрудников поиск
	if(typeOf(data['employers_search'])=='array'){
		employers_groups_objects['alltable'].setData(data['employers_search']);
	}//Массив сотрудников поиск


	/*
	//Выбранная группа
	if(data['sobject']){
		employers_groups_objects['sobject'] = null;
		employers_groups_objects['otable'].selectOf([String(data['sobject'])],1);
		employers_groups_select();
	}//Выбранная группа
	*/


	//Контейнеры ролей
	if(typeOf(employers_groups_objects['groups'])!='array' || !employers_groups_objects['groups'].length){
		$('containers_groups_area').hide();
		$('containers_groups_none').show();
	}else{
		$('containers_groups_area').show();
		$('containers_groups_none').hide();
	}


}//end function



//Выбран элемент
function employers_groups_select(){
	$('button_delete_group').hide();
	employers_groups_objects['oinfo']['li'].hide();
	employers_groups_objects['onew']['li'].hide();
	employers_groups_objects['sobject']=null;
	if(!employers_groups_objects['otable'].selectedRows.length) return;
	var tr = employers_groups_objects['otable'].selectedRows[0];
	if(typeOf(tr)!='element') return;
	var data = tr.retrieve('data');
	if(typeOf(data)!='object') return;
	$('oinfo_group_id').value = data['group_id'];
	$('oinfo_full_name').value = data['full_name'];
	$('oinfo_short_name').value = data['short_name'];
	employers_groups_objects['sobject']=data;
	employers_groups_objects['oinfo']['li'].show();
	$('button_delete_group').show();
	employers_groups_objects['groups_area_scroll'].toElement(tr);
}//end function




//Изменение - процесс
function employers_groups_change_save(){

	if(typeOf(employers_groups_objects['sobject'])!='object') return;
	if(!employers_groups_objects['oform_info'].validate()) return;

	new axRequest({
		url : '/admin/ajax/employers',
		data:{
			'action':'group.edit',
			'group_id': $('oinfo_group_id').value,
			'full_name': $('oinfo_full_name').value,
			'short_name': $('oinfo_short_name').value
		},
		silent: false,
		waiter: true,
		callback: function(success, status, data){
			if(success){
				employers_groups_dataset(data);
				employers_groups_otable_filter(true);
			}
		}
	}).request();

}//end function



//Добавление - показ формы
function employers_groups_new(){
	if(!employers_groups_objects['otable']) return;
	employers_groups_objects['otable'].clearSelected();
	employers_groups_objects['sobject'] = null;
	employers_groups_objects['oinfo']['li'].hide();
	employers_groups_objects['onew']['li'].show();
	$('button_delete_group').hide();
}//end function




//Добавление - процесс
function employers_groups_new_save(){

	if(!employers_groups_objects['oform_new'].validate()) return;

	new axRequest({
		url : '/admin/ajax/employers',
		data:{
			'action':'group.new',
			'full_name': $('onew_full_name').value,
			'short_name': $('onew_short_name').value
		},
		silent: false,
		waiter: true,
		callback: function(success, status, data){
			if(success){
				employers_groups_dataset(data);
			}
		}
	}).request();

}//end function





//Удаление - процесс
function employers_groups_delete(){

	if(typeOf(employers_groups_objects['sobject'])!='object' || String(employers_groups_objects['sobject']['group_id']) != String($('oinfo_group_id').value)) return;
	var group_id = String($('oinfo_group_id').value);

	App.message(
		'Подтвердите действие',
		'Вы действительно хотите удалить выбранную группу?',
		'CONFIRM',
		function(){
			new axRequest({
				url : '/admin/ajax/employers',
				data:{
					'action':'group.delete',
					'group_id': group_id
				},
				silent: false,
				waiter: true,
				callback: function(success, status, data){
					if(success){
						employers_groups_dataset(data);
					}
				}
			}).request();
		}
	);

}//end function



//Построение списка групп
function employers_groups_set_list(){
	select_add({
		'list': 'employers_groups_select',
		'key': 'group_id',
		'value': 'full_name',
		'options': employers_groups_objects['groups'],
		'default': select_getValue('employers_groups_select'),
		'clear': true
	});
}//end function




//Выбор группы
function employers_groups_select_group(){

	var group_id = select_getValue('employers_groups_select');
	var group_info = employers_groups_objects['groups'].filterRow('group_id',group_id);
	if(typeOf(group_info)!='object') return false;

	$('employers_groups_select').disable();
	new axRequest({
		url : '/admin/ajax/employers',
		data:{
			'action':'group.employers',
			'group_id': group_id,
		},
		silent: false,
		waiter: true,
		callback: function(success, status, data){
			$('employers_groups_select').enable();
			if(success){
				employers_groups_dataset(data);
			}
		}
	}).request();

	return true;
}//end function





//Фильтр данных таблицы доступных сотрудников
function employers_groups_alltable_filter(){
	//Фильтрация данных
	new axRequest({
		url : '/admin/ajax/employers',
		data:{
			'action':'employers.search',
			'search_name': $('employers_groups_all_filter').getValue(),
			'status': '1',
			'extended': 'employers_list'
		},
		silent: true,
		waiter: true,
		callback: function(success, status, data){
			if(success){
				employers_groups_dataset(data);
			}
		}
	}).request();
}//end function



//Исключение сотрудников из группы
function employers_groups_exclude_from_group(){
	var tr,data;
	var group_id = select_getValue('employers_groups_select');
	if(!employers_groups_objects['grouptable'].selectedRows.length) return;
	var employers = [];
	for(var i=0; i<employers_groups_objects['grouptable'].selectedRows.length;i++){
		tr = employers_groups_objects['grouptable'].selectedRows[i];
		if(typeOf(tr)!='element') continue;
		data = tr.retrieve('data');
		if(typeOf(data)!='object') continue;
		employers.push(data['employer_id']);
	}
	employers_groups_group_operation(group_id, employers, 'exclude');
}//end function




//Включение сотрудников в группу
function employers_groups_include_to_group(){
	var group_id = select_getValue('employers_groups_select');
	if(!employers_groups_objects['alltable'].selectedRows.length) return;
	var employers = [];
	for(var i=0; i<employers_groups_objects['alltable'].selectedRows.length;i++){
		tr = employers_groups_objects['alltable'].selectedRows[i];
		if(typeOf(tr)!='element') continue;
		data = tr.retrieve('data');
		if(typeOf(data)!='object') continue;
		employers.push(data['employer_id']);
	}
	employers_groups_group_operation(group_id, employers, 'include');
}//end function




//Операция включение, исключение объектов из контейнера роли
function employers_groups_group_operation(group_id, employers, action){
	new axRequest({
		url : '/admin/ajax/employers',
		data:{
			'action':'group.'+action,
			'group_id': group_id,
			'employers': employers
		},
		silent: false,
		waiter: true,
		callback: function(success, status, data){
			if(success){
				employers_groups_dataset(data);
			}
		}
	}).request();
}
