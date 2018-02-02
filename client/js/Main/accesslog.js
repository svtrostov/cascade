var accesslog_objects = {};


//Вход на страницу
function accesslog_enter_page(success, status, data){
	accesslog_start(data);
}//end function



//Выход со страницы
function accesslog_exit_page(){
	if(accesslog_objects['accesslog_table']) accesslog_objects['accesslog_table'].terminate();
	for(var i in accesslog_objects){
		accesslog_objects[i] = null;
	}
	accesslog_objects = {};
	App.Location.removeEvent('beforeLoadPage', accesslog_exit_page);
}//end function



//Инициализация процесса создания заявки
function accesslog_start(data){
	App.Location.addEvent('beforeLoadPage', accesslog_exit_page);
	accesslog_objects['accesslog_table'] = null;
	accesslog_build_log(data);
}//end function



//Построение таблицы ассистентов
function accesslog_build_log(data){

	if(typeOf(data)!='array'||!data.length){
		new Element('h2',{'html':'В настоящий момент журнал входов пуст'}).inject('accesslog_table_area');
		return;
	}else{
		if(accesslog_objects['accesslog_table'] != null){
			accesslog_objects['accesslog_table'].setData(data);
			return;
		}
		$('accesslog_table_area').empty();
	}

	var accesslog_area = build_blockitem({
		'parent': 'accesslog_table_area',
		'title'	: 'Журнал аутентификации пользователя'
	});

	accesslog_area['container'].setStyles({
		'padding': '0px',
		'margin': '0px'
	});

	//Инициализация таблицы выбора объектов доступа
	accesslog_objects['accesslog_table'] = new jsTable(accesslog_area['container'],{
		'class': 'jsTableLight',
		columns: [
			{
				width:'30%',
				sortable:false,
				caption: 'Дата и время',
				styles:{'min-width':'150px'},
				dataStyle:{'text-align':'center'},
				dataSource:'login_time'
			},
			{
				width:'20%',
				sortable:false,
				caption: 'IP адрес прокси',
				styles:{'min-width':'150px'},
				dataStyle:{'text-align':'center'},
				dataSource:'ip_addr'
			},
			{
				width:'20%',
				sortable:false,
				caption: 'IP адрес клиента',
				styles:{'min-width':'150px'},
				dataStyle:{'text-align':'center'},
				dataSource:'ip_real'
			},
			{
				width:'20%',
				styles:{'min-width':'120px'},
				sortable:false,
				caption: 'Тип входа',
				dataSource:'login_type',
				dataStyle:{'text-align':'left'},
				dataFunction:function(table, cell, text, data){
					if(text.contains('login')) return 'Аутентификация через LOGIN форму';
					else if(text.contains('cert')) return 'Аутентификация через SSL сертификат';
					return 'Аутентификация посредством Cookie';
				}
			}
		],
		selectType:1
	});
	accesslog_objects['accesslog_table'].setData(data);
}//end function
