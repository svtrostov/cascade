var assistants_objects = {};


//Вход на страницу
function assistants_enter_page(success, status, data){
	assistants_start(data);
}//end function



//Выход со страницы
function assistants_exit_page(){
	if(assistants_objects['delegates_table']) assistants_objects['delegates_table'].terminate();
	if(assistants_objects['assistants_table']) assistants_objects['assistants_table'].terminate();

	for(var i in assistants_objects){
		assistants_objects[i] = null;
	}
	assistants_objects = {};
	App.Location.removeEvent('beforeLoadPage', assistants_exit_page);
}//end function



//Инициализация процесса создания заявки
function assistants_start(data){
	App.Location.addEvent('beforeLoadPage', assistants_exit_page);

	//Построение слайдов
	assistants_objects['slideshow'] = new jsSlideShow('step_container');
	assistants_do_page('assistants');

	assistants_objects['delegates_table'] = null;
	assistants_objects['assistants_table'] = null;
	assistants_build_assistants(data['assistants']);
	assistants_build_delegates(data['delegates']);
	assistants_objects['selected_selector_object'] = null;
	assistants_objects['selected_selector_id'] = 0;
	assistants_objects['process_search'] = false;

	//Обработка событий
	$('button_delegates').addEvent('click',assistants_event_button);
	$('button_assistants').addEvent('click',assistants_event_button);
	$('button_about').addEvent('click',assistants_event_button);

	//Обработка событий нажатия клафиши ENTER на поле
	['assistants_selector_term'].each(function(item){
		$(item).addEvent('keypress',function(event){
			if(event.code ==13){ 
				assistants_selector_search();
			}
		});
	});

	//Создание календарей
	new Picker.Date($$('.calendar_input'), {
		timePicker: false,
		positionOffset: {x: 0, y: 2},
		pickerClass: 'calendar',
		useFadeInOut: false
	});

}//end function



//Обработка нажатия на кнопки управления
function assistants_event_button(event){
	if (!event || (event && typeOf(event.target) != 'element')) return;
	if (event.event.which && event.event.which != 1) return;
	var div = event.target.get('tag') == 'div' ? event.target : event.target.getParent('div');
	var action = 'empty';
	switch(div.id){
		case 'button_delegates': action = 'delegates'; break;
		case 'button_assistants': action = 'assistants'; break;
		case 'button_about': action = 'about'; break;
	}
	return assistants_do_page(action);
}//end function





//Навигация по страницам мастера
function assistants_do_page(action){

	if(action == 'empty') return;
	if(!$('step_'+action)) return;

	//Слайд
	assistants_objects['slideshow'].show($('step_'+action), {
		transition: 'fadeThroughBackground'
	});


}//end function




//Построение таблицы ассистентов
function assistants_build_assistants(data){

	if(typeOf(data)!='array'||!data.length){
		if(assistants_objects['assistants_table'] != null){
			assistants_objects['assistants_table'].terminate();
			assistants_objects['assistants_table'] = null;
		}
		$('assistants_table_area').empty();
		new Element('h2',{'html':'В настоящий момент у Вас нет заместителей'}).inject('assistants_table_area');
		return;
	}else{
		if(assistants_objects['assistants_table'] != null){
			assistants_objects['assistants_table'].setData(data);
			return;
		}
		$('assistants_table_area').empty();
	}

	var assistants_area = build_blockitem({
		'parent': 'assistants_table_area',
		'title'	: 'Вас замещают следующие сотрудники'
	});

	assistants_area['container'].setStyles({
		'padding': '0px',
		'margin': '0px'
	});

	//Инициализация таблицы выбора объектов доступа
	assistants_objects['assistants_table'] = new jsTable(assistants_area['container'],{
		'class': 'jsTableLight',
		columns: [
			{
				width:'30%',
				sortable:false,
				caption: 'ФИО сотрудника',
				styles:{'min-width':'150px'},
				dataStyle:{'text-align':'left'},
				dataSource:'employer_name'
			},
			{
				width:'30%',
				styles:{'min-width':'150px'},
				sortable:false,
				caption: 'Занимает должность',
				dataSource:'posts',
				dataStyle:{'text-align':'left'},
				dataFunction:function(table, cell, text, data){
					if(typeOf(text)!='array'||!text.length) return '-[нет должностей]-';
					var result='';
					for(var i=0; i<text.length; i++){
						result+= text[i]['post_name']+'<br/><div class="small" style="'+(i==text.length-1 ?'':'border-bottom:1px dotted #ddd;margin-bottom:5px;')+'padding-bottom:5px;">'+text[i]['company_name']+'</div>';
					}
					return result;
				}
			},
			{
				width:'150px',
				styles:{'min-width':'120px'},
				sortable:false,
				dataStyle:{'text-align':'left'},
				caption: 'Контактный телефон',
				dataSource:'phone'
			},
			{
				width:'150px',
				styles:{'min-width':'120px'},
				sortable:false,
				caption: 'Электронная почта',
				dataSource:'email',
				dataStyle:{'text-align':'left'},
				dataFunction:function(table, cell, text, data){
					if(!text.contains('@'))return '---';
					return '<a href="mailto:'+text+'">'+text+'</a>';
				}
			},
			{
				width:'100px',
				styles:{'min-width':'80px'},
				sortable:false,
				dataStyle:{'text-align':'center'},
				caption: 'Дата начала',
				dataSource:'from_date'
			},
			{
				width:'100px',
				styles:{'min-width':'80px'},
				sortable:false,
				dataStyle:{'text-align':'center'},
				caption: 'Дата окончания',
				dataSource:'to_date'
			},
			{
				width:'60px',
				styles:{'min-width':'60px'},
				sortable:false,
				caption: 'Отозвать',
				dataSource:'employer_id',
				styles:{'min-width':'140px'},
				dataStyle:{'text-align':'center'},
				dataFunction:function(table, cell, text, data){
					new Element('a',{
						'href': '#',
						'styles':{
							'cursor':'pointer'
						},
						'events':{
							'click': function(e){
								App.message(
									'Подтвердите действие',
									'Вы действительно хотите чтобы сотрудник: '+this['employer_name']+' перестал Вас замещать?',
									'CONFIRM',
									function(){
										assistants_cancel(this['employer_id']);
									}.bind(this)
								);
							}.bind(data)
						}
					}).inject(cell).set('text','Отозвать');
					return '';
				}
			}
		],
		selectType:0
	});
	assistants_objects['assistants_table'].setData(data);
}//end function





//Убрать привелегии у ассистента
function assistants_cancel(assistant_id){

	//Отправка заявки
	new axRequest({
		url : '/main/ajax/employer',
		data:
			'action=assistants.delete'+
			'&employer_id='+encodeURIComponent(assistant_id),
		silent: true,
		waiter: true,
		callback: function(success, status, data){
			if(success && typeOf(data)=='array'){
				assistants_build_assistants(data);
			}
		}
	}).request();

}







//Построение таблицы ассистентов
function assistants_build_delegates(data){

	if(typeOf(data)!='array'||!data.length){
		new Element('h2',{'html':'В настоящий момент Вы никого не замещаете'}).inject('delegates_table_area');
		return;
	}

	var delegates_area = build_blockitem({
		'parent': 'delegates_table_area',
		'title'	: 'Вы замещеате следующих сотрудников'
	});


	delegates_area['container'].setStyles({
		'padding': '0px',
		'margin': '0px'
	});

	//Инициализация таблицы выбора объектов доступа
	assistants_objects['delegates_table'] = new jsTable(delegates_area['container'],{
		'class': 'jsTableLight',
		columns: [
			{
				width:'30%',
				sortable:false,
				caption: 'ФИО сотрудника',
				styles:{'min-width':'150px'},
				dataStyle:{'text-align':'left'},
				dataSource:'employer_name'
			},
			{
				width:'30%',
				styles:{'min-width':'150px'},
				sortable:false,
				caption: 'Занимает должность',
				dataSource:'posts',
				dataStyle:{'text-align':'left'},
				dataFunction:function(table, cell, text, data){
					if(typeOf(text)!='array'||!text.length) return '-[нет должностей]-';
					var result='';
					for(var i=0; i<text.length; i++){
						result+= text[i]['post_name']+'<br/><div class="small" style="'+(i==text.length-1 ?'':'border-bottom:1px dotted #ddd;margin-bottom:5px;')+'padding-bottom:5px;">'+text[i]['company_name']+'</div>';
					}
					return result;
				}
			},
			{
				width:'150px',
				styles:{'min-width':'150px'},
				sortable:false,
				dataStyle:{'text-align':'left'},
				caption: 'Контактный телефон',
				dataSource:'phone'
			},
			{
				width:'150px',
				styles:{'min-width':'150px'},
				sortable:false,
				caption: 'Электронная почта',
				dataSource:'email',
				dataStyle:{'text-align':'left'},
				dataFunction:function(table, cell, text, data){
					if(!text.contains('@'))return '---';
					return '<a href="mailto:'+text+'">'+text+'</a>';
				}
			},
			{
				width:'120px',
				styles:{'min-width':'120px'},
				sortable:false,
				dataStyle:{'text-align':'center'},
				caption: 'Дата начала',
				dataSource:'from_date'
			},
			{
				width:'120px',
				styles:{'min-width':'120px'},
				sortable:false,
				dataStyle:{'text-align':'center'},
				caption: 'Дата окончания',
				dataSource:'to_date'
			}
		],
		selectType:0
	});
	assistants_objects['delegates_table'].setData(data);
}//end function




/*Открытие окна выбора заместителя*/
function assistants_selector_open(){
	$('assistants_selector').show();
	$('assistants_selector_wrapper').scrollTo(0, 0);
	if(!assistants_objects['selected_selector_object']) $('assistants_selector_complete_button').hide();
}//end function




/*Закрытие окна выбора*/
function assistants_selector_cancel(){
	$('assistants_selector').hide();
	$('assistants_selector_period').hide();
}//end function




/*Поиск сотрудников*/
function assistants_selector_search(){

	var tobj = $('assistants_selector_term');
	var term = String(tobj.value).trim();
	if(!term.length) return;

	if(assistants_objects['process_search']) return;
	assistants_objects['process_search'] = true;

	new axRequest({
		url : '/main/ajax/employer',
		data:{
			'action':'assistants.search',
			'term': term
		},
		silent: true,
		waiter: true,
		callback: function(success, status, data){
			assistants_objects['process_search'] = false;
			if(success){
				$('assistants_selector_complete_button').hide();
				assistants_objects['selected_selector_object'] = null;
				assistants_objects['selected_selector_id'] = 0;
				if(typeOf(data)!='array'||!data.length){
					$('assistants_selector_table').hide();
					$('assistants_selector_none').show();
					return;
				}
				assistants_selector_build_list(data);
				$('assistants_selector_table').show();
				$('assistants_selector_none').hide();
			}
		}
	}).request();
}//end function




/*Построение списка найденных сотрудников*/
function assistants_selector_build_list(data){

	var list = $('selectorlist');
	var li, html, employer_name, phone, email, posts;

	list.empty();

	//Построение списка заявок
	for(var index=0; index<data.length; index++){
		employer_name = data[index]['employer_name'];
		phone = String(data[index]['phone']).length > 0 ? data[index]['phone'] : null;
		email = String(data[index]['email']).length > 0 ? data[index]['email'] : null;
		posts = data[index]['posts'];

		html = '<div class="line">'+employer_name +'<br/>'+(phone || email ? '<p class="small">'+( phone ? 'Телефон: '+data[index]['phone'] : '')+(phone && email ? '<br/>':'')+(email ? 'Email: <a class="mailto" href="mailto:'+data[index]['email']+'">'+data[index]['email']+'</a>':'')+'</p>' : '')+'</div>';
		if(typeOf(posts)=='array' && posts.length>0){
			for(var i=0; i<posts.length; i++){
				html+= '<div class="line" style="border-top:1px dotted #ddd;">' + posts[i]['post_name'] + '<br><p class="small">'+posts[i]['company_name']+'</p></div>';
			}
		}else{
			html+= '<div class="line">-[Не занимает никаких должностей]-</div>';
		}
		
		li = new Element('li').inject(list).set('html',html).addEvent('click',function(){
			if($(assistants_objects['selected_selector_object'])) $(assistants_objects['selected_selector_object']).removeClass('selected');
			this.addClass('selected');
			assistants_objects['selected_selector_id'] = this.retrieve('employer_id');
			assistants_objects['selected_selector_object'] = this;
			$('assistants_selector_complete_button').show();
		}).store('employer_id',data[index]['employer_id']).store('employer_data',data[index]);

	}//Построение списка заявок

}//end function


/*Выбор сотрудника*/
function assistants_selector_complete(){
	if(!$(assistants_objects['selected_selector_object'])) return;
	var employer = $(assistants_objects['selected_selector_object']).retrieve('employer_data');
	var phone = String(employer['phone']).length > 0 ? employer['phone'] : null;
	var email = String(employer['email']).length > 0 ? employer['email'] : null;
	$('assistants_selector').hide();
	$('assistants_selector_done_button').hide();
	$('assistants_selector_confirm').checked = false;
	$('assistants_selector_period').show();
	$('assistants_selector_selected_name').set('html',employer['employer_name']+'<br><p class="small">'+
	(phone || email ? ( phone ? 'Телефон: '+phone : '')+(phone && email ? '<br/>':'')+(email ? 'Email: <a class="mailto" href="mailto:'+email+'">'+email+'</a>':'') : '')+'</p>');
}


/*Установка/снятие галочки подтверждения*/
function assistants_selector_confirm_change(){
	if($('assistants_selector_confirm').checked) $('assistants_selector_done_button').show();
	else $('assistants_selector_done_button').hide();
}




/*Делегирование полномочий выбранному сотруднику*/
function assistants_selector_done(){
	if(!$(assistants_objects['selected_selector_object'])) return;
	if(!$('assistants_selector_confirm').checked) return;
	var employer_id = $(assistants_objects['selected_selector_object']).retrieve('employer_id');
	var date_from = String($('assistants_selector_date_from').get('value')).trim();
	var date_to = String($('assistants_selector_date_to').get('value')).trim();

	new axRequest({
		url : '/main/ajax/employer',
		data:{
			'action':'assistants.add',
			'employer_id': employer_id,
			'date_from': date_from,
			'date_to': date_to
		},
		silent: true,
		waiter: true,
		callback: function(success, status, data){
			if(success){
				assistants_selector_cancel();
				$('assistants_selector_complete_button').hide();
				assistants_objects['selected_selector_object'] = null;
				assistants_objects['selected_selector_id'] = 0;
				$('selectorlist').empty();
				$('assistants_selector_term').value='';
				assistants_build_assistants(data);
			}
		}
	}).request();

}

