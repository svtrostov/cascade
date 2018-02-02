var request_info_objects = {};


//Вход на страницу
function request_info_enter_page(success, status, data){
	App.Location.addEvent('beforeLoadPage', request_info_exit_page);
	if(
		typeOf(data)!='object' ||
		typeOf(data['info'])!='object' ||
		typeOf(data['iresources'])!='object'||
		typeOf(data['ir_types'])!='array'
	){
		$('request_wrapper').hide();
		$('request_none').show();
		return false;
	}
	request_info_start(data);
}//end function



//Выход со страницы
function request_info_exit_page(){

	for(var i in request_info_objects['IRTABLES']){
		request_info_objects['IRTABLES'][i].terminate();
	}
	for(var i in request_info_objects['IRCOMMENTS']){
		request_info_objects['IRCOMMENTS'][i].destroy();
	}

	for(var i in request_info_objects){
		request_info_objects[i] = null;
	}
	request_info_objects = {};
	App.Location.removeEvent('beforeLoadPage', request_info_exit_page);
}//end function



//Инициализация заявки
function request_info_start(data){

	request_info_objects['REQUEST_TYPE'] = parseInt(data['info']['request_type']);
	request_info_objects['IR_TYPES'] = data['ir_types'];
	request_info_objects['IRTABLES'] = {};
	request_info_objects['IRCOMMENTS'] = {};
	request_info_objects['request_iresources']={};


	//Навигация
	select_add({
		'list':'ir_selector_iresource_list',
		'options':[['info','Общие сведения о заявке']]
	});


	//Инфо заявки
	var fields = ['request_id','request_type','curator_name','create_date','employer_name','company_name','post_name','phone','email'];
	var text;
	for(var i=0;i<fields.length;i++){
		if($('info_'+fields[i])){
			text = data['info'][fields[i]];
			if(fields[i] == 'request_type'){
				text = (String(text) == '3' ? '<font color="red">Блокировка доступа</font>' : '<font color="green">Запрос доступа</font>' );
			}
			$('info_'+fields[i]).set('html', text);
		}
	}

	var infoarea = build_blockitem({
		'parent': 'step_info',
		'title'	: 'Общие сведения о заявке'
	});
	$('tmpl_iresource_info').inject(infoarea['container']).show();
	var routearea = build_blockitem({
		'list': 	infoarea['list'],
		'title'	: 'Статус согласования'
	});
	routearea['container'].setStyles({
		'padding': '0px',
		'margin': '0px'
	});



	//Таблица со списком доступов
	request_info_objects['IRTABLES']['iresources'] = new jsTable(routearea['container'],{
		'class': 'jsTableLight',
		columns: [
			{
				width:'40%',
				sortable:true,
				caption: 'Информационный ресурс',
				dataSource:'iresource_name',
				styles:{
					'height':'30px'
				}
			},
			{
				caption: 'Статус согласования',
				sortable:true,
				width:'10%',
				dataSource:'route_status',
				dataStyle:{'text-align':'center','min-width':'140px'},
				dataFunction:function(table, cell, text, data){
					switch(String(text)){
						case '0': return 'Отменена';
						case '1': return 'В работе';
						case '2': return 'Приостановлена';
						case '100': return 'Исполнена';
						default: return 'Неизвестно';
					}
				}
			},
			{
				caption: 'Комментарий',
				sortable:true,
				width:'20%',
				dataSource:'route_status_desc',
				dataStyle:{'text-align':'left','min-width':'120px'}
			},
			{
				caption: 'Текущее действие',
				sortable:false,
				width:'30%',
				dataSource:'step_info',
				dataStyle:{'text-align':'left','min-width':'220px'},
				dataFunction:function(table, cell, item, data){
					if(typeOf(item)!='object') return '-[Нет]-';
					var out = '<font class="dark"><b>'+item['role']+'</b></font><br/>';
					var phone, email, name;
					if(typeOf(item['gatekeepers'])!='array'||!item['gatekeepers'].length) return out+'<font class="error">Гейткипер не найден</font>';
					for(var i=0; i<item['gatekeepers'].length;i++){
						name = String(item['gatekeepers'][i]['employer_name']).length > 0 ? item['gatekeepers'][i]['employer_name'] : '-[Неизвестное ФИО, ID='+item['gatekeepers'][i]['employer_id']+']-';
						phone = String(item['gatekeepers'][i]['phone']).length > 0 ? item['gatekeepers'][i]['phone'] : null;
						email = String(item['gatekeepers'][i]['email']).length > 0 ? item['gatekeepers'][i]['email'] : null;
						out += name+(phone || email ? '<div class="small">'+( phone ? '<i>Телефон:</i> '+phone : '')+(email ? ' <i>E-mail:</i> <a class="mailto" href="mailto:'+email+'">'+email+'</a>':'')+'</div>' : '')+'<br/>';
					}
					if(typeOf(item['assistants'])!='array'||!item['assistants'].length) return out;
					out+='<b>Заместители:</b><br/>';
					for(var i=0; i<item['assistants'].length;i++){
						name = String(item['assistants'][i]['employer_name']).length > 0 ? item['assistants'][i]['employer_name'] : '-[Неизвестное ФИО, ID='+item['assistants'][i]['employer_id']+']-';
						phone = String(item['assistants'][i]['phone']).length > 0 ? item['assistants'][i]['phone'] : null;
						email = String(item['assistants'][i]['email']).length > 0 ? item['assistants'][i]['email'] : null;
						out += name+(phone || email ? '<div class="small">'+( phone ? '<i>Телефон:</i> '+phone : '')+(email ? ' <i>E-mail:</i> <a class="mailto" href="mailto:'+email+'">'+email+'</a>':'')+'</div>' : '')+'<br/>';
					}
					request_info_objects['table_steps_'+data['iresource_id']+'_current'] = {
						'current_step': data['current_step'],
						'info': out
					};
					return out;
				}
			}
		],
		'dataBackground1': '#fff',
		'dataBackground2': '#fff',
		rowHoverType:1,
		selectType:1
	});

	request_info_objects['IRTABLES']['iresources'].setData(data['iresources']);

	request_info_irs_build(data);

	request_info_objects['slideshow'] = new jsSlideShow('step_container');


	if(typeOf(REQUEST_LAST_DATA_GET)=='object'){
		if(REQUEST_LAST_DATA_GET['iresource_id']){
			select_set('ir_selector_iresource_list', REQUEST_LAST_DATA_GET['iresource_id']);
		}
	}
	request_info_ir_selector_iresource_list_change();

}//end function




//Выбор ИР
function request_info_ir_selector_iresource_list_change(){
	request_info_do_page(select_getValue('ir_selector_iresource_list'), select_getText('ir_selector_iresource_list'));
	
}//end function







//Навигация по страницам мастера
function request_info_do_page(action, step_title){

	switch(action){
		case 'empty': return;
	}

	$('step_title').set('html',step_title);

	//Слайд
	if($('step_'+action)){
		request_info_objects['slideshow'].show($('step_'+action), {
			transition: 'fadeThroughBackground'
		});
	}


}//end function






function request_info_irs_build(data){
	var iresource, iresource_id;
	for(iresource_id in data['iresources']){
		if(typeOf(data['iresources'][iresource_id])!='object') continue;
		if(typeOf(data['roles'][iresource_id])!='object') continue;
		request_info_irs_add(iresource_id, data);
		select_add({
			'list'		: 'ir_selector_iresource_list',
			'options'	: [{'iresource_id':iresource_id,'iresource_name':data['iresources'][iresource_id]['iresource_name']}],
			'key'		: 'iresource_id',
			'value'		: 'iresource_name'
		});
	}
};



/*Построение списка комментариев*/
function request_info_build_comment_list(comment_list, comments){
	var comment;
	for(var i=0; i<comments.length; i++){
		comment = comments[i];
		build_commentitem({
			'list'		: comment_list,
			'author'	: (String(comment['employer_id']) == '0' ? 'Администратор' : comment['employer_name']),
			'timestamp'	: comment['timestamp'],
			'message'	: comment['comment'],
			'bg_color'	: (i%2==0 ? null : '#FFFFFF')
		});
	}
};





/*Создание элемента ИР в слайдере*/
function request_info_irs_add(iresource_id, data){

	var iresource =  data['iresources'][iresource_id];
	var request_id =  data['iresources'][iresource_id]['request_id'];
	var roles =  data['roles'][iresource_id];


	var iresource_div = new Element('div',{'class':'steparea','id':'step_'+iresource_id}).inject('step_container').hide();
	//new Element('h1').inject(iresource_div).set('text',iresource['iresource_name']);
	var ir_list = new Element('ul',{'class':'blocklist'}).inject(iresource_div);


	var iroles = build_blockitem({
		'list'	: ir_list,
		'title'	: 'Запрошенный доступ'
	});
	iroles['container'].setStyles({
		'padding': '0px',
		'margin': '0px'
	});

	if(iresource['route_status']=='1'||iresource['route_status']=='2'){
		//Удаление ИР
		new Element('span',{
			'title':'Отменить запрашиваемый доступ к информационному ресурсу',
			'class':'ui-icon-white ui-icon-trash'
		}).inject(iroles['toolbar']).setStyles({
			'cursor':'pointer'
		}).addEvents({
			click: function(e){
				App.message(
					'Подтвердите действие',
					'Отменить запрос доступа к информационному ресурсу: '+iresource['iresource_name']+'?',
					'CONFIRM',
					function(){
						//request_new_ir_remove(iresource_id);
					}
				);
				e.stop();
				return false;
			}
		});
	}



	var icomments = build_blockitem({
		'list'	: ir_list,
		'title'	: 'Комментарии к заявке'
	});

	//Комментарии
	var icomments_area = new Element('div').inject(icomments['container']);
	var comment_list = new Element('ul',{'class':'commentlist'}).inject(icomments_area);
	request_info_objects['IRCOMMENTS'][iresource_id] = comment_list;

	if(typeOf(iresource['comments'])!='array'||!iresource['comments'].length){
		new Element('h2',{'html':'Комментарии отсутствуют'}).inject(comment_list);
	}else{
		request_info_build_comment_list(comment_list, iresource['comments']);
	}

	var add_comment_funct = function(e){
		App.comment('Добавить комментарий','',function(comment){
			comment = String(comment).trim();
			if(!comment.length) return;
			new axRequest({
				url : '/main/ajax/request',
				data:{
					'action':'comment.add',
					'irlist': 1,
					'request_id': request_id,
					'iresource_id': iresource_id,
					'comment': comment,
					'returncomments': 1
				},
				silent: true,
				waiter: true,
				callback: function(success, status, data){
					if(success){
						if(typeOf(request_info_objects['IRCOMMENTS'][iresource_id])!='element') return;
						request_info_objects['IRCOMMENTS'][iresource_id].empty();
						request_info_build_comment_list(request_info_objects['IRCOMMENTS'][iresource_id], data);
					}
				}
			}).request();
		});
		e.stop();
	};


	if(iresource['route_status']=='1'||iresource['route_status']=='2'){
		//Добавление комментария
		new Element('span',{
			'title':'Добавить комментарий к заявке',
			'class':'ui-icon-white ui-icon-comment',
			'styles':{
				'cursor':'pointer'
			},
			'events':{
				'click': add_comment_funct
			}
		}).inject(icomments['toolbar']);

		var comment_button = new Element('div',{
			'class':'ui-button',
			'events':{
				'click': add_comment_funct
			}
		}).inject(icomments_area);
		new Element('span',{'text':'Добавить комментарий'}).inject(comment_button);
	}



	//Подготовка массива объектов - разделы
	var oareas = {0:'-[Без раздела]-'};
	var areas = [[0,'-[Без раздела]-']];
	var aroles = {};
	var owner_id;
	for(var i in roles){
		owner_id = parseInt(roles[i]['owner_id']);
		if(typeOf(oareas[owner_id])!='string'){
			oareas[owner_id] = roles[i]['owner_name'];
			areas.push([owner_id, roles[i]['owner_name']]);
		}
		if(typeOf(aroles[owner_id])!='array')aroles[owner_id] = [];
		aroles[owner_id].push(roles[i]);
	}
	var sortareas = areas.sort(function(a,b){if(a[1]>b[1]) return 1; else if(a[1]<b[1]) return -1; else return 0;});
	var result = [];

	for(var i=0; i<sortareas.length;i++){
		owner_id = sortareas[i][0];
		if(typeOf(aroles[owner_id])!='array') continue;
		result.push(sortareas[i][1]);
		for(var j=0; j < aroles[owner_id].length; j++){
			result.push(aroles[owner_id][j]);
		}
	}

	var is_lock_request = (request_info_objects['REQUEST_TYPE']==3);

	//Таблица со списком доступов
	request_info_objects['IRTABLES'][iresource_id] = new jsTable(iroles['container'],{
		'class': 'jsTableLight',
		columns: [
			{
				width:'60%',
				sortable:false,
				caption: 'Функционал',
				dataSource:'irole_name'
			},
			{
				caption: 'Изначально запрошен',
				sortable:false,
				width:'120px',
				dataSource:'ir_request',
				dataStyle:{'text-align':'center','min-width':'120px'},
				dataFunction:function(table, cell, text, data){
					if(String(text) == '0') return '---';
					if(is_lock_request) return '-[Блокировать]-';
					return request_info_objects['IR_TYPES'].filterResult('full_name', 'item_id', text);
				}
			},
			{
				caption: 'Установлен в процессе согласования',
				sortable:false,
				width:'120px',
				dataSource:'ir_selected',
				dataStyle:{'text-align':'center','min-width':'120px'},
				dataFunction:function(table, cell, text, data){
					if(String(text) == '0') return '--Нет--';
					if(is_lock_request) return '-[Блокировать]-';
					return request_info_objects['IR_TYPES'].filterResult('full_name', 'item_id', text);
				}
			},
			{
				caption: 'Изменения',
				sortable:false,
				width:'120px',
				dataSource:'ir_selected',
				dataStyle:{'text-align':'center','min-width':'120px','font-size':'10px'},
				dataFunction:function(table, cell, text, data){
					var action = '';
					switch(String(data['update_type'])){
						case '0': return '-не менялся-';
						case '1': action = 'Добавлено'; break;
						case '2': action = 'Изменено'; break;
						case '3': action = 'Удалено'; break;
					}
					return action +'<br>'+ data['timestamp'] +'<br>'+ (data['gatekeeper_id']=='0'? 'Администратор' : data['gatekeeper_name']);
				}
			}
		],
		rowFunction: function(table, row, data){
			var color = '#FFFFFF';
			return {
				'background': color,
				'bg_recalc': false
			}
		},
		'dataBackground1': '#fff',
		'dataBackground2': '#fff',
		selectType:1
	});

	request_info_objects['IRTABLES'][iresource_id].setData(result);


	var steps_area = 'area_process_steps_'+iresource_id;
	if(typeOf(request_info_objects[steps_area])!='object'){
		request_info_objects[steps_area] = build_blockitem({
			'list': ir_list,
			'title'	: 'История процесса согласования'
		});
		request_info_objects[steps_area]['container'].setStyles({
			'padding':'0px',
			'margin':'0px',
		});
		request_info_buildSteps(request_info_objects[steps_area]['container'], data, iresource_id);
	}


}//end function









//Построение истории процесса согласования
function request_info_buildSteps(parent, data, iresource_id){

	var table_steps = 'table_steps_'+iresource_id;
	if(typeOf(data['steps'][iresource_id])!='object') return;
	var steps = [];
	for(var k in data['steps'][iresource_id]){
		steps.push(data['steps'][iresource_id][k]);
	}
	steps.sort(function(a,b){
		var data_a = String(a['rstep_id']).toInt();
		var data_b = String(b['rstep_id']).toInt();
		if(data_a > data_b) return 1;
		if(data_a < data_b) return -1;
		return 0;
	});

	if(!steps.length){
		parent.set('html','<h2>История согласования пуста</h2>');
		return;
	}

	request_info_objects[table_steps] = steps;

	if(!request_info_objects['IRTABLES'][table_steps]){
		request_info_objects['IRTABLES'][table_steps] = new jsTable(parent, {
			'class': 'jsTableLight',
			columns: [
				{
					width:'120px',
					sortable:false,
					caption: 'Этап согласования',
					dataSource:'step_type',
					dataStyle:{'text-align':'center','min-width':'120px'},
					dataFunction:function(table, cell, text, data){
						switch(String(data['step_type'])){
							case '1': return 'Начало согласования';
							case '2':
								switch(String(data['gatekeeper_role'])){
									case '1': return 'Согласование заявки';
									case '2': return 'Утверждение заявки';
									case '3': return 'Исполнение заявки';
									case '4': return 'Уведомление';
								}
								return '-[????]-';
							break;
							case '3': return 'Заявка исполнена';
							case '4': return 'Заявка отменена';
						}
						return '-[????]-';
					}
				},
				{
					caption: 'Статус согласования',
					sortable:false,
					width:'180px',
					dataSource:'gatekeeper_id',
					styles:{'min-width':'180px'},
					dataFunction:function(table, cell, text, data){
						if(String(data['step_complete'])=='0'){
							var sc = request_info_objects['table_steps_'+iresource_id+'_current'];
							if(typeOf(sc) == 'object' && String(sc['current_step']) == String(data['rstep_id'])){
								return '<font class="dark"><b>Текущее действие</b>:</font> '+sc['info'];
							}
							return '---';
						}
						var approved_info = (String(data['is_approved'])=='1' ? '<font color="green">Одобрил</font>' : '<font color="red">Отклонил</font>');
						if(String(data['gatekeeper_id'])!='0'){
							return 'Гейткипер:<br>'+data['gatekeeper_name']+'<br><b>'+approved_info+'</b>';
						}else
						if(String(data['assistant_id'])!='0'){
							return 'Заместитель:<br>'+data['assistant_name']+'<br><b>'+approved_info+'</b>';
						}else{
							return '<font color="red">-[?????]-</font>';
						}
					}
				},
				{
					caption: 'Дата и время',
					sortable:false,
					width:'140px',
					dataSource:'timestamp',
					dataStyle:{'text-align':'center','min-width':'140px'},
					dataFunction:function(table, cell, text, data){
						if(String(data['step_complete'])=='0') return '---';
						return data['timestamp'];
					}
				}
			],
			'dataBackground1': '#fafafa',
			'dataBackground2': '#fff',
			selectType:1
		});

	}

	request_info_objects['IRTABLES'][table_steps].setData(request_info_objects[table_steps]);
}//end function

