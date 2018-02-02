var request_history_objects = {};


//Вход на страницу
function request_history_enter_page(success, status, data){
	request_history_start(data);
}//end function



//Выход со страницы
function request_history_exit_page(){
	if(request_history_objects['request_active_table']) request_history_objects['request_active_table'].terminate();
	for(var key in request_history_objects['types']){if(request_history_objects['request_'+key+'_table']) request_history_objects['request_'+key+'_table'].terminate();}
	if(request_history_objects['request_view_table']) request_history_objects['request_view_table'].terminate();

	for(var i in request_history_objects){
		request_history_objects[i] = null;
	}
	request_history_objects = {};
	App.Location.removeEvent('beforeLoadPage', request_history_exit_page);
}//end function



//Инициализация процесса создания заявки
function request_history_start(data){
	App.Location.addEvent('beforeLoadPage', request_history_exit_page);

	//Построение слайдов
	request_history_objects['types'] = {
		'complete':{
			'title':'Исполненные заявки',
			'none':'Нет исполненных заявок'
		},
		'hold':{
			'title':'Временно приостановленные заявки',
			'none':'Нет приостановленных заявок'
		},
		'cancel':{
			'title':'Отмененные заявки',
			'none':'Нет отмененных заявок'
		}
	};
	request_history_objects['slideshow'] = new jsSlideShow('step_container');
	request_history_do_page('active');

	//Обработка событий
	['button_request_active','button_request_complete','button_request_hold','button_request_cancel'].each(function(item){$(item).addEvent('click',request_history_event_button);});

	request_history_build_active(data['active']);
	for(var key in request_history_objects['types']){request_history_build_other(key, data[key]);}
}//end function




//Обработка нажатия на кнопки управления
function request_history_event_button(event){
	if (!event || (event && typeOf(event.target) != 'element')) return;
	if (event.event.which && event.event.which != 1) return;
	var div = event.target.get('tag') == 'div' ? event.target : event.target.getParent('div');
	var action = 'empty';
	switch(div.id){
		case 'button_request_active': action = 'active'; break;
		case 'button_request_complete': action = 'complete'; break;
		case 'button_request_hold': action = 'hold'; break;
		case 'button_request_cancel': action = 'cancel'; break;
		case 'button_request_view': App.Location.doPage('/main/requests/view'); break;
	}
	return request_history_do_page(action);
}//end function






//Построение таблицы
function request_history_build_active(data){

	if(typeOf(data)!='array'||!data.length){
		if(request_history_objects['request_active_table'] != null){
			request_history_objects['request_active_table'].terminate();
			request_history_objects['request_active_table'] = null;
		}
		$('request_active_table').empty();
		new Element('h2',{'html':'В настоящий момент у Вас нет заявок, находящихся в процессе согласования'}).inject('request_active_table');
		return;
	}else{
		if(request_history_objects['request_active_table'] != null){
			request_history_objects['request_active_table'].setData(data);
			return;
		}
		$('request_active_table').empty();
	}

	var requests_area = build_blockitem({
		'parent': 'request_active_table',
		'title'	: 'Заявки в процессе согласования и исполнения'
	});

	requests_area['container'].setStyles({
		'padding': '0px',
		'margin': '0px'
	});

	//Инициализация таблицы выбора объектов доступа
	request_history_objects['request_active_table'] = new jsTable(requests_area['container'],{
		'class': 'jsTableLight',
		columns: [
			{
				width:'40px',
				sortable:false,
				caption: '-',
				styles:{'min-width':'40px'},
				dataStyle:{'text-align':'center'},
				dataSource:'request_id',
				dataFunction:function(table, cell, text, data){
					new Element('img',{
						'src': INTERFACE_IMAGES+'/document_go.png',
						'styles':{
							'cursor':'pointer',
							'margin-left':'4px'
						},
						'events':{
							'click': function(e){
								App.Location.doPage('/main/requests/info?request_id='+data['request_id']+'&iresource_id='+data['iresource_id']);
							}
						}
					}).inject(cell);
					return '';
				}
			},
			{
				width:'80px',
				sortable:false,
				caption: 'Номер заявки',
				styles:{'min-width':'80px'},
				dataStyle:{'text-align':'center'},
				dataSource:'request_id'
			},
			{
				width:'80px',
				sortable:false,
				caption: 'Тип заявки',
				styles:{'min-width':'80px'},
				dataStyle:{'text-align':'center'},
				dataSource:'request_type',
				dataFunction:function(table, cell, text, data){
					return (String(text)=='3'? '<font color="red">Блокировка доступа</font>' : '<font color="green">Запрос доступа</font>' );
				}
			},
			{
				width:'90%',
				sortable:false,
				caption: 'Информационный ресурс',
				styles:{'min-width':'120px'},
				dataStyle:{'text-align':'left'},
				dataSource:'iresource_name'
			},
			{
				width:'150px',
				styles:{'min-width':'120px'},
				sortable:false,
				dataStyle:{'text-align':'center'},
				caption: 'Дата создания заявки',
				dataSource:'create_date'
			},
			{
				width:'140px',
				styles:{'min-width':'140px'},
				sortable:false,
				caption: 'Текущий этап',
				dataSource:'gatekeeper_role',
				dataStyle:{'text-align':'center'},
				dataFunction:function(table, cell, text, data){
					switch(text){
						case '1': return 'Согласование';
						case '2': return 'Утверждение';
						case '3': return 'Исполнение';
						case '4': return 'Просмотр';
						default: return '---';
					}
					return '';
				}
			},
			{
				width:'100px',
				sortable:false,
				caption: 'Отменить заявку',
				styles:{'min-width':'100px'},
				dataStyle:{'text-align':'center'},
				dataSource:'request_id',
				dataFunction:function(table, cell, text, data){
					if(String(data['gatekeeper_role'])=='3' || (String(data['request_type'])=='3' && data['curator_id']!=data['employer_id'])) return 'Нельзя отменить';
					new Element('a',{
						'href': '#',
						'styles':{
							'cursor':'pointer'
						},
						'events':{
							'click': function(e){
								App.message(
									'Подтвердите действие',
									'Вы действительно хотите отменить согласование заявки №'+this['request_id']+' по информационному ресурсу: '+this['iresource_name']+'?',
									'CONFIRM',
									function(){
										request_history_cancel_request(this['request_id'], this['iresource_id']);
									}.bind(this)
								);
							}.bind(data)
						}
					}).inject(cell).set('text','Отменить');
					return '';
				}
			}
		],
		selectType:0
	});
	request_history_objects['request_active_table'].setData(data);
}//end function





//Построение таблицы ассистентов
function request_history_build_other(type, data){

	if(typeOf(request_history_objects['types'][type])!='object') return;
	var rtype = request_history_objects['types'][type];

	if(typeOf(data)!='array'||!data.length){
		if(request_history_objects['request_'+type+'_table'] != null){
			request_history_objects['request_'+type+'_table'].terminate();
			request_history_objects['request_'+type+'_table'] = null;
		}
		$('request_'+type+'_table').empty();
		new Element('h2',{'html':rtype['none']}).inject('request_'+type+'_table');
		return;
	}else{
		if(request_history_objects['request_'+type+'_table'] != null){
			request_history_objects['request_'+type+'_table'].setData(data);
			return;
		}
		$('request_'+type+'_table').empty();
	}

	var requests_area = build_blockitem({
		'parent': 'request_'+type+'_table',
		'title'	: rtype['title']
	});

	requests_area['container'].setStyles({
		'padding': '0px',
		'margin': '0px'
	});

	var table_columns = [
		{
			width:'40px',
			sortable:false,
			caption: '-',
			styles:{'min-width':'40px'},
			dataStyle:{'text-align':'center'},
			dataSource:'request_id',
			dataFunction:function(table, cell, text, data){
				new Element('img',{
					'src': INTERFACE_IMAGES+'/document_go.png',
					'styles':{
						'cursor':'pointer',
						'margin-left':'4px'
					},
					'events':{
						'click': function(e){
							App.Location.doPage('/main/requests/info?request_id='+data['request_id']+'&iresource_id='+data['iresource_id']);
						}
					}
				}).inject(cell);
				return '';
			}
		},
		{
			width:'80px',
			sortable:false,
			caption: 'Номер заявки',
			styles:{'min-width':'80px'},
			dataStyle:{'text-align':'center'},
			dataSource:'request_id'
		},
			{
				width:'80px',
				sortable:false,
				caption: 'Тип заявки',
				styles:{'min-width':'80px'},
				dataStyle:{'text-align':'center'},
				dataSource:'request_type',
				dataFunction:function(table, cell, text, data){
					return (String(text)=='3'? '<font color="red">Блокировка доступа</font>' : '<font color="green">Запрос доступа</font>' );
				}
			},
		{
			width:'40%',
			sortable:false,
			caption: 'Информационный ресурс',
			styles:{'min-width':'120px'},
			dataStyle:{'text-align':'left'},
			dataSource:'iresource_name'
		},
		{
			width:'150px',
			styles:{'min-width':'120px'},
			sortable:false,
			dataStyle:{'text-align':'center'},
			caption: 'Дата создания заявки',
			dataSource:'create_date'
		},
		{
			width:'140px',
			styles:{'min-width':'140px'},
			sortable:false,
			caption: 'Статус заявки',
			dataSource:'route_status',
			dataStyle:{'text-align':'center'},
			dataFunction:function(table, cell, text, data){
				switch(text){
					case '1': return 'В работе';
					case '2': return 'Приостановлена';
					case '100': return 'Исполнена';
					case '0': return 'Остановлена';
					default: return '---';
				}
				return '';
			}
		},
		{
			width:'30%',
			styles:{'min-width':'140px'},
			sortable:false,
			caption: 'Примечание',
			dataSource:'route_status_desc',
			dataStyle:{'text-align':'left'}
		}
	];

	if(type=='complete'){
		table_columns.push({
			width:'70px',
			styles:{'min-width':'70px'},
			sortable:false,
			caption: 'Загрузить',
			dataSource:'request_id',
			dataStyle:{'text-align':'center'},
			dataFunction:function(table, cell, text, data){
				new Element('img',{
					'src': INTERFACE_IMAGES+'/pdf.png',
					'title': 'Скачать заявку в PDF формате',
					'styles':{
						'cursor':'pointer',
						'margin-left':'4px'
					},
					'events':{
						'click': function(e){
							var link = '/main/customcontent/reports?report_type=request&request_id='+data['request_id']+'&iresource_id='+data['iresource_id'];
							//document.location = link;
							App.Loader.downloadFile(link);
							//window.open(link);
						}
					}
				}).inject(cell);
				return '';
			}
		});
	}


	//Инициализация таблицы выбора объектов доступа
	request_history_objects['request_'+type+'_table'] = new jsTable(requests_area['container'],{
		'class': 'jsTableLight',
		columns: table_columns,
		selectType:0
	});
	request_history_objects['request_'+type+'_table'].setData(data);
}//end function








//Навигация по страницам мастера
function request_history_do_page(action){

	if(action == 'empty') return;
	if(!$('step_'+action)) return;

	//Слайд
	request_history_objects['slideshow'].show($('step_'+action), {
		transition: 'fadeThroughBackground'
	});


}//end function




//Навигация по страницам мастера
function request_history_cancel_request(request_id, iresource_id){

	//Отправка заявки
	new axRequest({
		url : '/main/ajax/request',
		data:
			'action=request.cancel'+
			'&request_id='+encodeURIComponent(request_id)+
			'&iresource_id='+encodeURIComponent(iresource_id),
		silent: true,
		waiter: true,
		callback: function(success, status, data){
			if(success && typeOf(data)=='object'){
				request_history_build_active(data['active']);
				for(var key in request_history_objects['types']){request_history_build_other(key, data[key]);}
			}
		}
	}).request();

}