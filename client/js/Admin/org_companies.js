var org_companies_objects = {};

//Вход на страницу
function org_companies_enter_page(success, status, data){
	org_companies_start(data);
}//end function


//Выход со страницы
function org_companies_exit_page(){
	['otable'].each(function(table,index){
		if(org_companies_objects[table]) org_companies_objects[table].terminate();
	});
	if(org_companies_objects['oform_info']) org_companies_objects['oform_info'].destroy();
	if(org_companies_objects['oform_new']) org_companies_objects['oform_new'].destroy();
	for(var i in org_companies_objects){
		org_companies_objects[i] = null;
	}
	org_companies_objects = {};
	App.Location.removeEvent('beforeLoadPage', org_companies_exit_page);
}//end function



//Инициализация процесса создания заявки
function org_companies_start(data){
	App.Location.addEvent('beforeLoadPage', org_companies_exit_page);

	org_companies_objects['sobject'] = null;
	org_companies_objects['companies_area_scroll'] = new Fx.Scroll($('companies_table_area_wrapper'));

	org_companies_objects['splitter'] = set_splitter_h({
		'left'		: $('companies_area'),
		'right'		: $('companies_info'),
		'splitter'	: $('companies_splitter'),
		'parent'	: $('companies_splitter').getParent('.contentareafull')
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
				dataSource:'company_id',
				dataType: 'num'
			},
			{
				width:'200px',
				sortable:true,
				caption: 'Организация',
				styles:{'min-width':'160px'},
				dataStyle:{'text-align':'left'},
				dataSource:'full_name'
			}
		],
		selectType:1
	};

	org_companies_objects['otable'] = new jsTable('companies_table_area', settings);
	org_companies_objects['otable'].addEvent('click', org_companies_select);


	org_companies_objects['oinfo'] = build_blockitem({
		'parent': 'companies_info_area',
		'title'	: 'Свойства организации'
	});
	$('tmpl_company_info').show().inject(org_companies_objects['oinfo']['container']);
	org_companies_objects['oinfo']['li'].hide();
	$('button_delete_company').hide();

	org_companies_objects['oform_info'] = new jsValidator('tmpl_company_info');
	org_companies_objects['oform_info']
	.required('oinfo_company_id').numeric('oinfo_company_id')
	.required('oinfo_full_name')
	.required('oinfo_short_name');


	org_companies_objects['onew'] = build_blockitem({
		'list': org_companies_objects['oinfo']['list'],
		'title'	: 'Добавить организацию'
	});
	org_companies_objects['onew']['li'].hide();
	$('tmpl_company_new').show().inject(org_companies_objects['onew']['container']);

	org_companies_objects['oform_new'] = new jsValidator('tmpl_company_new');
	org_companies_objects['oform_new'].required('onew_full_name').required('onew_short_name');

	$('org_companies_filter').addEvent('keydown',function(e){if(e.code==13) org_companies_otable_filter();});
	$('org_companies_filter_button').addEvent('click',org_companies_otable_filter);

	org_companies_dataset(data);
}//end function





//Фильтр данных таблицы списка
function org_companies_otable_filter(no_clear_selected){
	org_companies_objects['otable'].filter($('org_companies_filter').value);
	if(!no_clear_selected){
		org_companies_objects['otable'].clearSelected();
		org_companies_objects['sobject'] = null;
		org_companies_objects['oinfo']['li'].hide();
	}
}//end function




//Применение данных
function org_companies_dataset(data){


	//Массив объектов
	if(typeOf(data['companies'])=='array'){
		$('button_delete_company').hide();
		org_companies_objects['sobject']=null;
		org_companies_objects['oinfo']['li'].hide();
		org_companies_objects['otable'].setData(data['companies']);
	}//Типы объектов


	//Выбранный объект
	if(data['sobject']){
		org_companies_objects['sobject'] = null;
		org_companies_objects['otable'].selectOf([String(data['sobject'])],1);
		org_companies_select();
	}//Выбранный объект


}//end function



//Выбран элемент
function org_companies_select(){
	$('button_delete_company').hide();
	org_companies_objects['oinfo']['li'].hide();
	org_companies_objects['onew']['li'].hide();
	org_companies_objects['sobject']=null;
	if(!org_companies_objects['otable'].selectedRows.length) return;
	var tr = org_companies_objects['otable'].selectedRows[0];
	if(typeOf(tr)!='element') return;
	var data = tr.retrieve('data');
	if(typeOf(data)!='object') return;
	$('oinfo_company_id').value = data['company_id'];
	$('oinfo_full_name').value = data['full_name'];
	$('oinfo_short_name').value = data['short_name'];
	$('oinfo_is_lock').checked = String(data['is_lock'])=='1'?true:false;
	org_companies_objects['sobject']=data;
	org_companies_objects['oinfo']['li'].show();
	$('button_delete_company').show();
	org_companies_objects['companies_area_scroll'].toElement(tr);
}//end function




//Изменение - процесс
function org_companies_change_save(){

	if(typeOf(org_companies_objects['sobject'])!='object') return;
	if(!org_companies_objects['oform_info'].validate()) return;

	new axRequest({
		url : '/admin/ajax/org',
		data:{
			'action':'org.company.edit',
			'company_id': $('oinfo_company_id').value,
			'is_lock': ($('oinfo_is_lock').checked?'1':'0'),
			'full_name': $('oinfo_full_name').value,
			'short_name': $('oinfo_short_name').value
		},
		silent: false,
		waiter: true,
		callback: function(success, status, data){
			if(success){
				org_companies_dataset(data);
				org_companies_otable_filter(true);
			}
		}
	}).request();

}//end function



//Добавление - показ формы
function org_companies_new(){
	if(!org_companies_objects['otable']) return;
	org_companies_objects['otable'].clearSelected();
	org_companies_objects['sobject'] = null;
	org_companies_objects['oinfo']['li'].hide();
	org_companies_objects['onew']['li'].show();
	$('button_delete_company').hide();
}//end function




//Добавление - процесс
function org_companies_new_save(){

	if(!org_companies_objects['oform_new'].validate()) return;

	new axRequest({
		url : '/admin/ajax/org',
		data:{
			'action':'org.company.new',
			'is_lock': ($('onew_is_lock').checked?'1':'0'),
			'full_name': $('onew_full_name').value,
			'short_name': $('onew_short_name').value
		},
		silent: false,
		waiter: true,
		callback: function(success, status, data){
			if(success){
				org_companies_dataset(data);
			}
		}
	}).request();

}//end function





//Удаление - процесс
function org_companies_delete(){

	if(typeOf(org_companies_objects['sobject'])!='object' || String(org_companies_objects['sobject']['company_id']) != String($('oinfo_company_id').value)) return;
	var company_id = String($('oinfo_company_id').value);

	App.message(
		'Подтвердите действие',
		'Вы действительно хотите удалить выбранную организацию?',
		'CONFIRM',
		function(){
			new axRequest({
				url : '/admin/ajax/org',
				data:{
					'action':'org.company.delete',
					'company_id': company_id
				},
				silent: false,
				waiter: true,
				callback: function(success, status, data){
					if(success){
						org_companies_dataset(data);
					}
				}
			}).request();
		}
	);

}//end function

