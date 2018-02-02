var request_view_objects = {};


//Вход на страницу
function request_view_enter_page(success, status, data){
	App.Location.addEvent('beforeLoadPage', request_view_exit_page);
	if(
		typeOf(data)!='object' ||
		typeOf(data['info'])!='object' ||
		typeOf(data['iresource'])!='object'||
		typeOf(data['ir_types'])!='array' ||
		typeOf(data['roles'])!='array' ||
		typeOf(data['steps'])!='object'
	){
		$('request_wrapper').hide();
		$('request_none').show();
		return false;
	}
	request_view_start(data);
}//end function



//Выход со страницы
function request_view_exit_page(){

	if(request_view_objects['IRTABLE']){
		request_view_objects['IRTABLE'].terminate();
	}

	if(request_view_objects['STEPTABLE']){
		request_view_objects['STEPTABLE'].terminate();
	}

	if(request_view_objects['IRCOMMENTS']){
		request_view_objects['IRCOMMENTS'].destroy();
	}

	for(var i in request_view_objects){
		request_view_objects[i] = null;
	}
	request_view_objects = {};
	App.Location.removeEvent('beforeLoadPage', request_view_exit_page);
}//end function



//Инициализация заявки
function request_view_start(data){

	request_view_objects['REQUEST_ID'] = parseInt(data['info']['request_id']);
	request_view_objects['REQUEST_TYPE'] = parseInt(data['info']['request_type']);
	request_view_objects['IRESOURCE_ID'] = parseInt(data['iresource']['iresource_id']);
	request_view_objects['IR_TYPES'] = data['ir_types'];
	request_view_objects['IRTABLE'] = null;
	request_view_objects['STEPTABLE'] = null;
	request_view_objects['IRCOMMENTS'] = null;
/*
	//Создание календарей
	new Picker.Date($$('.calendar_input'), {
		timePicker: false,
		positionOffset: {x: 0, y: 2},
		pickerClass: 'calendar',
		useFadeInOut: false
	});

	//Навигация
	select_add({
		'list':'ir_selector_iresource_list',
		'options':[['info','Общие сведения о заявке']]
	});
	select_add({
		'list'		: 'ir_selector_iresource_list',
		'options'	: data['iresources'],
		'key'		: 'iresource_id',
		'value'		: 'iresource_name'
	});
*/
	//Инфо заявки
	var fields = ['request_id','request_type','route_status','route_status_desc','iresource_name','curator_name','create_date','employer_name','company_name','post_name','phone','email'];
	var text;
	for(var i=0;i<fields.length;i++){
		if($('info_'+fields[i])){
			text = data['info'][fields[i]];
			if(fields[i] == 'request_type'){
				text = (String(text) == '3' ? '<font color="red">Блокировка доступа</font>' : '<font color="green">Запрос доступа</font>' );
			}else
			if(fields[i] == 'route_status'){
				switch(String(text)){
					case '0': text = '<font color="red">Заявка отменена</font>'; break;
					case '1': text = 'В работе'; break;
					case '2': text = '<font color="blue">Заявка приостановлена</font>'; break;
					case '100': text = '<font color="green">Заявка исполнена</font>'; break;
					default: text = '<font color="red">-[Неизвестный статус заявки]-</font>';
				}
			}
			$('info_'+fields[i]).set('html', text);
		}
	}

	if(	String(data['info']['is_curator'])=='1' ||
		String(data['info']['is_gatekeeper'])=='1' ||
		String(data['info']['is_owner'])=='1' ||
		String(data['info']['is_performer'])=='1' ||
		String(data['info']['is_watcher'])=='1'){
		$('gk_export_area').show();
	}else{
		$('gk_export_area').hide();
	}


	var infoarea = build_blockitem({
		'list': 'blocklist',
		'title'	: 'Общие сведения о заявке'
	});
	$('tmpl_request_info').inject(infoarea['container']).show();

	//Комментарии
	request_view_build_comments(data);

	//Запрашиваемый доступ
	request_view_build_roles_table();
	request_view_objects['IRTABLE'].setData(data['roles']);

	request_view_build_steps_table(data['steps'], data['iresource']);

	build_scrolldown($('request_area'), 50);

}//end function





/*Построение списка доступов*/
function request_view_build_roles_table(){

	var iroles = build_blockitem({
		'list'	: 'blocklist',
		'title'	: 'Запрашиваемый доступ'
	});

	iroles['container'].setStyles({
		'padding': '0px',
		'margin': '0px'
	});

	var is_lock_request = (request_view_objects['REQUEST_TYPE']==3);

	//Таблица со списком доступов
	request_view_objects['IRTABLE'] = new jsTable(iroles['container'],{
		'class': 'jsTableLight',
		columns: [
			{
				width:'30%',
				sortable:false,
				caption: 'Функционал',
				dataSource:'full_name'
			},
			{
				width:'40%',
				sortable:false,
				caption: 'Описание',
				dataSource:'description',
				dataFunction:function(table, cell, text, data){

					var span = new Element('div',{
						'styles':{
							'margin-left':'30px'
						}
					}).set('text',text);

					if(!data['screenshot']||data['screenshot']==''){
						new Element('img',{
							'src':INTERFACE_IMAGES+'/preview_none.png',
						}).inject(cell).setStyles({
							'cursor':'default',
							'float':'left'
						});
						span.inject(cell);
						return '';
					}
					new Element('img',{
						'src':INTERFACE_IMAGES+'/preview_active.png',
					}).inject(cell).setStyles({
						'cursor':'pointer',
						'float':'left'
					}).addEvents({
						'click': function(e){
							preview_irole(data['irole_id']);
						}
					});
					span.inject(cell);
					return '';

				}
			},
			{
				width:'80px',
				sortable:false,
				caption: 'Важность',
				dataSource:'weight',
				dataStyle:{'text-align':'center','min-width':'80px'},
				dataFunction:function(table, cell, text, data){
					if(text<3) return 'Низкая';
					if(text<6) return 'Средняя';
					if(text<8) return 'Высокая';
					return 'Критично';
				}
			},
			{
				caption: 'Запрошен заявителем',
				sortable:false,
				width:'120px',
				dataSource:'ir_request',
				dataStyle:{'text-align':'center','min-width':'120px'},
				dataFunction:function(table, cell, text, data){
					if(String(text) == '0') return '--Нет--';
					if(is_lock_request) return '-[Блокировать]-';
					return request_view_objects['IR_TYPES'].filterResult('full_name', 'item_id', text);
				}
			},
			{
				caption: 'Установлен гейткипером',
				sortable:false,
				width:'120px',
				dataSource:'ir_types',
				styles:{'min-width':'120px'},
				dataStyle:{'text-align':'center','min-width':'120px'},
				dataFunction:function(table, cell, text, data){
					if(String(data['ir_selected']) == '0') return '--Нет--';
					if(is_lock_request) return '-[Блокировать]-';
					return request_view_objects['IR_TYPES'].filterResult('full_name', 'item_id', data['ir_selected']);
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
					return action +'<br>'+ data['update_time'] +'<br>'+ (data['gatekeeper_id']=='0'? 'Администратор' : data['gatekeeper_name']);
				}
			}
		],
		rowFunction: function(table, row, data){
			var color = '#FFAFAF';
			if(data['weight']<3) color = '#FFFFFF';
			else if(data['weight']<6) color = '#FFFDE6';
			else if(data['weight']<8) color = '#FFDEDE';
			return {
				'background': color,
				'bg_recalc': false
			}
		},
		'dataBackground1': '#fff',
		'dataBackground2': '#fff',
		selectType:1
	});

}


/*Построение списка комментариев*/
function request_view_build_comment_list(comments){
	var comment;
	for(var i=0; i<comments.length; i++){
		comment = comments[i];
		build_commentitem({
			'list'		: request_view_objects['IRCOMMENTS'],
			'author'	: (String(comment['employer_id']) == '0' ? 'Администратор' : comment['employer_name']),
			'timestamp'	: comment['timestamp'],
			'message'	: comment['comment'],
			'bg_color'	: (i%2==0 ? null : '#FFFFFF')
		});
	}
}


/*Построение списка комментариев*/
function request_view_build_comments(data){

	var request_id = data['info']['request_id'];
	var iresource_id = data['iresource']['iresource_id'];
	var comments = data['iresource']['comments'];
	var icomments = build_blockitem({
		'list'	: 'blocklist',
		'title'	: 'Комментарии к заявке'
	});

	//Комментарии
	var icomments_area = new Element('div').inject(icomments['container']);
	var comment_list = new Element('ul',{'class':'commentlist'}).inject(icomments_area);
	request_view_objects['IRCOMMENTS'] = comment_list;

	if(typeOf(comments)!='array'||!comments.length){
		new Element('h2',{'html':'Комментарии отсутствуют'}).inject(comment_list);
	}
	else{
		request_view_build_comment_list(comments);
	}

};



/*Построение списка этапов согласования*/
function request_view_build_steps_table(data, iresource){

	if(typeOf(data)!='object') return;

	var steps_area = build_blockitem({
		'list': 'blocklist',
		'title'	: 'История процесса согласования'
	});
	steps_area['container'].setStyles({
		'padding':'0px',
		'margin':'0px',
	});

	var steps = [];
	for(var k in data){
		steps.push(data[k]);
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

	request_view_objects['STEPTABLE'] = new jsTable(steps_area['container'], {
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
						if(typeOf(iresource)=='object' && typeOf(iresource['step_info'])=='object' && String(iresource['current_step']) == String(data['rstep_id'])){
							var item = iresource['step_info'];
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
							return out;
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

	request_view_objects['STEPTABLE'].setData(steps);

}//end function


/*Получить заявку в PDF формате*/
function request_view_export(format){
	var link = '/main/customcontent/reports?format='+format+'&report_type=watcher&request_id='+request_view_objects['REQUEST_ID']+'&iresource_id='+request_view_objects['IRESOURCE_ID'];
	App.echo('Download: '+link);
	App.Loader.downloadFile(link);
}
