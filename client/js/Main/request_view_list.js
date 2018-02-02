var request_view_list_objects = {};


//Вход на страницу
function request_view_list_enter_page(success, status, data){
	request_view_list_start(data);
}//end function



//Выход со страницы
function request_view_list_exit_page(){
	if(request_view_list_objects['request_view_list_table']) request_view_list_objects['request_view_list_table'].terminate();

	for(var i in request_view_list_objects){
		request_view_list_objects[i] = null;
	}
	request_view_list_objects = {};
	App.Location.removeEvent('beforeLoadPage', request_view_list_exit_page);
}//end function



//Инициализация процесса создания заявки
function request_view_list_start(data){

	App.Location.addEvent('beforeLoadPage', request_view_list_exit_page);

	request_view_list_objects['request_view_list_table'] = null;

	var storage_data = App.localStorage.read('rvl_filter', null, true);
	var filter = (storage_data ? String(storage_data).fromQueryString() : null) || data['filter'];
	//var filter = data['filter'];

	if(typeOf(filter)=='object'){
		var value;
		for(var key in filter){
			value = filter[key];
			switch(key){
				case 'is_owner': $('view_owner').set('checked',(value=='1'?true:false)); break;
				case 'is_curator': $('view_curator').set('checked',(value=='1'?true:false)); break;
				case 'is_gatekeeper': $('view_gatekeeper').set('checked',(value=='1'?true:false)); break;
				case 'is_performer': $('view_performer').set('checked',(value=='1'?true:false)); break;
				case 'is_watcher': $('view_watcher').set('checked',(value=='1'?true:false)); break;
				case 'employer': $('view_employer').setValue(decodeURIComponent(value)); break;
				case 'type': select_set('view_type', value); break;
				case 'period': select_set('view_period', value); break;
				case 'watched': select_set('view_watched', value); break;
			}
		}
	}

	//Обработка событий нажатия клафиши ENTER на поле
	['view_employer'].each(function(item){
		$(item).addEvent('keypress',function(event){
			if(event.code ==13){
				request_view_list_filter();
			}
		});
	});

	request_view_list_filter();

}//end function




//Построение таблицы
function request_view_list_build_table(data){

	if(typeOf(data)!='array'||!data.length){
		if(request_view_list_objects['request_view_list_table'] != null){
			request_view_list_objects['request_view_list_table'].terminate();
			request_view_list_objects['request_view_list_table'] = null;
		}
		$('request_view_list_table_area').empty();
		new Element('h2',{'html':'Доступные для просмотра заявки не найдены'}).inject('request_view_list_table_area');
		return;
	}else{
		if(request_view_list_objects['request_view_list_table'] != null){
			request_view_list_objects['request_view_list_table'].setData(data);
			return;
		}
		$('request_view_list_table_area').empty();
	}

	var request_view_list_area = build_blockitem({
		'parent': 'request_view_list_table_area',
		'title'	: 'Найденные заявки'
	});

	request_view_list_area['container'].setStyles({
		'padding': '0px',
		'margin': '0px'
	});

	//Инициализация таблицы выбора объектов доступа
	request_view_list_objects['request_view_list_table'] = new jsTable(request_view_list_area['container'],{
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
								App.Location.doPage('/main/requests/view?request_id='+data['request_id']+'&iresource_id='+data['iresource_id']);
							}
						}
					}).inject(cell);
					return '';
				}
			},
			{
				width:'60px',
				styles:{'min-width':'60px'},
				sortable:true,
				dataStyle:{'text-align':'center'},
				caption: 'Номер заявки',
				dataSource:'request_id',
				dataType:'int'
			},
			{
				width:'80px',
				styles:{'min-width':'60px'},
				sortable:false,
				dataStyle:{'text-align':'center'},
				caption: 'Тип заявки',
				dataSource:'request_type',
				dataFunction:function(table, cell, text, data){
					return (String(text)=='3'? '<font color="red">Блокировка доступа</font>' : '<font color="green">Запрос доступа</font>' );
				}
			},
			{
				width:'100px',
				styles:{'min-width':'80px'},
				sortable:true,
				dataStyle:{'text-align':'center'},
				caption: 'Дата заявки',
				dataSource:'create_date',
				dataType:'date'
			},
			{
				width:'250px',
				styles:{'min-width':'200px'},
				sortable:true,
				dataStyle:{'text-align':'left'},
				caption: 'Информационный ресурс',
				dataSource:'iresource_name'
			},
			{
				width:'120px',
				styles:{'min-width':'120px'},
				sortable:true,
				dataStyle:{'text-align':'left'},
				caption: 'Статус заявки',
				dataSource:'route_status',
				dataFunction:function(table, cell, text, data){
					switch(text){
						case '1': return 'В работе';
						case '2': return 'Приостановлена<br><div class="small">'+data['route_status_desc']+'</div>';
						case '100': return 'Исполнена';
						case '0': return 'Остановлена<br><div class="small">'+data['route_status_desc']+'</div>';
						default: return '---';
					}
					return '';
				}
			},
			{
				width:'30%',
				sortable:true,
				caption: 'Заявитель',
				styles:{'min-width':'100px'},
				dataStyle:{'text-align':'left'},
				dataSource:'employer_name',
				dataFunction:function(table, cell, text, data){
					return data['employer_name']+'<br/>'+'<div class="small">'+data['post_name']+'<br>'+data['company_name']+'</div>';
				}
			},
			{
				width:'30%',
				styles:{'min-width':'150px'},
				sortable:false,
				caption: 'Вы видите эту заявку потому что',
				dataSource:'request_id',
				dataStyle:{'text-align':'left'},
				dataFunction:function(table, cell, text, data){
					var result = '';
					if(data['is_owner']=='1') result+=(result.length>0?', ':'')+'являетесь заявителем';
					if(data['is_curator']=='1'&&data['is_owner']!='1') result+=(result.length>0?', ':'')+'являетесь куратором';
					if(data['is_gatekeeper']=='1') result+=(result.length>0?', ':'')+'согласовывали заявку';
					if(data['is_performer']=='1') result+=(result.length>0?', ':'')+'исполняли заявку';
					if(data['is_watcher']=='1') result+=(result.length>0?', ':'')+'должны быть уведомлены о наличии заявки';
					return result;
				}
			}
		],
		rowFunction: function(table, row, data){
			var color = '#FFFDE6';
			if(data['is_watched']=='1') color = '#FFFFFF';
			return {
				'background': color,
				'bg_recalc': false
			}
		},
		'dataBackground1': '#fff',
		'dataBackground2': '#fff',
		selectType:0
	});
	request_view_list_objects['request_view_list_table'].setData(data);
}//end function




/*Поиск сотрудников*/
function request_view_list_filter(){

	if(request_view_list_objects['process_search']) return;
	request_view_list_objects['process_search'] = true;

	var filter = {
		'type'			: $('view_type').getValue(),
		'period'		: $('view_period').getValue(),
		'watched'		: $('view_watched').getValue(),
		'employer'		: $('view_employer').getValue(),
		'is_owner'		: ($('view_owner').checked ? '1':'0'),
		'is_curator'	: ($('view_curator').checked ? '1':'0'),
		'is_gatekeeper'	: ($('view_gatekeeper').checked ? '1':'0'),
		'is_performer'	: ($('view_performer').checked ? '1':'0'),
		'is_watcher'	: ($('view_watcher').checked ? '1':'0'),
	};

	App.localStorage.write('rvl_filter', Object.toQueryString(filter), true);
	filter['action'] = 'watcher.requestlist';

	new axRequest({
		url : '/main/ajax/employer',
		data: filter,
		silent: true,
		waiter: true,
		callback: function(success, status, data){
			request_view_list_objects['process_search'] = false;
			if(success){
				request_view_list_build_table( (typeOf(data)=='object'?data['requests']:null) );
			}
		}
	}).request();
}//end function


