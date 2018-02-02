var org_posts_objects = {};

//Вход на страницу
function org_posts_enter_page(success, status, data){
	org_posts_start(data);
}//end function


//Выход со страницы
function org_posts_exit_page(){
	['otable'].each(function(table,index){
		if(org_posts_objects[table]) org_posts_objects[table].terminate();
	});
	if(org_posts_objects['oform_info']) org_posts_objects['oform_info'].destroy();
	if(org_posts_objects['oform_new']) org_posts_objects['oform_new'].destroy();
	for(var i in org_posts_objects){
		org_posts_objects[i] = null;
	}
	org_posts_objects = {};
	App.Location.removeEvent('beforeLoadPage', org_posts_exit_page);
}//end function



//Инициализация процесса
function org_posts_start(data){
	App.Location.addEvent('beforeLoadPage', org_posts_exit_page);

	org_posts_objects['sobject'] = null;
	org_posts_objects['posts_area_scroll'] = new Fx.Scroll($('posts_table_area_wrapper'));

	org_posts_objects['splitter'] = set_splitter_h({
		'left'		: $('posts_area'),
		'right'		: $('posts_info'),
		'splitter'	: $('posts_splitter'),
		'parent'	: $('posts_splitter').getParent('.contentareafull')
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
				dataSource:'post_id',
				dataType: 'num'
			},
			{
				width:'200px',
				sortable:true,
				caption: 'Должность',
				styles:{'min-width':'160px'},
				dataStyle:{'text-align':'left'},
				dataSource:'full_name'
			}
		],
		selectType:1
	};

	org_posts_objects['otable'] = new jsTable('posts_table_area', settings);
	org_posts_objects['otable'].addEvent('click', org_posts_select);


	org_posts_objects['oinfo'] = build_blockitem({
		'parent': 'posts_info_area',
		'title'	: 'Свойства организации'
	});
	$('tmpl_post_info').show().inject(org_posts_objects['oinfo']['container']);
	org_posts_objects['oinfo']['li'].hide();
	$('button_delete_post').hide();

	org_posts_objects['oform_info'] = new jsValidator('tmpl_post_info');
	org_posts_objects['oform_info']
	.required('oinfo_post_id').numeric('oinfo_post_id')
	.required('oinfo_full_name')
	.required('oinfo_short_name');


	org_posts_objects['onew'] = build_blockitem({
		'list': org_posts_objects['oinfo']['list'],
		'title'	: 'Добавить организацию'
	});
	org_posts_objects['onew']['li'].hide();
	$('tmpl_post_new').show().inject(org_posts_objects['onew']['container']);

	org_posts_objects['oform_new'] = new jsValidator('tmpl_post_new');
	org_posts_objects['oform_new'].required('onew_full_name').required('onew_short_name');

	$('org_posts_filter').addEvent('keydown',function(e){if(e.code==13) org_posts_otable_filter();});
	$('org_posts_filter_button').addEvent('click',org_posts_otable_filter);

	org_posts_dataset(data);
}//end function





//Фильтр данных таблицы списка
function org_posts_otable_filter(no_clear_selected){
	org_posts_objects['otable'].filter($('org_posts_filter').value);
	if(!no_clear_selected){
		org_posts_objects['otable'].clearSelected();
		org_posts_objects['sobject'] = null;
		org_posts_objects['oinfo']['li'].hide();
	}
}//end function




//Применение данных
function org_posts_dataset(data){


	//Массив объектов
	if(typeOf(data['posts'])=='array'){
		$('button_delete_post').hide();
		org_posts_objects['sobject']=null;
		org_posts_objects['oinfo']['li'].hide();
		org_posts_objects['otable'].setData(data['posts']);
	}//Типы объектов


	//Выбранный объект
	if(data['sobject']){
		org_posts_objects['sobject'] = null;
		org_posts_objects['otable'].selectOf([String(data['sobject'])],1);
		org_posts_select();
	}//Выбранный объект


}//end function



//Выбран элемент
function org_posts_select(){
	$('button_delete_post').hide();
	org_posts_objects['oinfo']['li'].hide();
	org_posts_objects['onew']['li'].hide();
	org_posts_objects['sobject']=null;
	if(!org_posts_objects['otable'].selectedRows.length) return;
	var tr = org_posts_objects['otable'].selectedRows[0];
	if(typeOf(tr)!='element') return;
	var data = tr.retrieve('data');
	if(typeOf(data)!='object') return;
	$('oinfo_post_id').value = data['post_id'];
	$('oinfo_full_name').value = data['full_name'];
	$('oinfo_short_name').value = data['short_name'];
	org_posts_objects['sobject']=data;
	org_posts_objects['oinfo']['li'].show();
	$('button_delete_post').show();
	org_posts_objects['posts_area_scroll'].toElement(tr);
}//end function




//Изменение - процесс
function org_posts_change_save(){

	if(typeOf(org_posts_objects['sobject'])!='object') return;
	if(!org_posts_objects['oform_info'].validate()) return;

	new axRequest({
		url : '/admin/ajax/org',
		data:{
			'action':'org.post.edit',
			'post_id': $('oinfo_post_id').value,
			'full_name': $('oinfo_full_name').value,
			'short_name': $('oinfo_short_name').value
		},
		silent: false,
		waiter: true,
		callback: function(success, status, data){
			if(success){
				org_posts_dataset(data);
				org_posts_otable_filter(true);
			}
		}
	}).request();

}//end function



//Добавление - показ формы
function org_posts_new(){
	if(!org_posts_objects['otable']) return;
	org_posts_objects['otable'].clearSelected();
	org_posts_objects['sobject'] = null;
	org_posts_objects['oinfo']['li'].hide();
	org_posts_objects['onew']['li'].show();
	$('button_delete_post').hide();
}//end function




//Добавление - процесс
function org_posts_new_save(){

	if(!org_posts_objects['oform_new'].validate()) return;

	new axRequest({
		url : '/admin/ajax/org',
		data:{
			'action':'org.post.new',
			'full_name': $('onew_full_name').value,
			'short_name': $('onew_short_name').value
		},
		silent: false,
		waiter: true,
		callback: function(success, status, data){
			if(success){
				org_posts_dataset(data);
			}
		}
	}).request();

}//end function





//Удаление - процесс
function org_posts_delete(){

	if(typeOf(org_posts_objects['sobject'])!='object' || String(org_posts_objects['sobject']['post_id']) != String($('oinfo_post_id').value)) return;
	var post_id = String($('oinfo_post_id').value);

	App.message(
		'Подтвердите действие',
		'Вы действительно хотите удалить выбранную должность?',
		'CONFIRM',
		function(){
			new axRequest({
				url : '/admin/ajax/org',
				data:{
					'action':'org.post.delete',
					'post_id': post_id
				},
				silent: false,
				waiter: true,
				callback: function(success, status, data){
					if(success){
						org_posts_dataset(data);
					}
				}
			}).request();
		}
	);

}//end function

