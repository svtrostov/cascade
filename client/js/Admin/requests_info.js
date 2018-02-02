;(function(){
var PAGE_NAME = 'requests_info';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': [],
		//
		'ir_types':null,
		'ir_types_assoc':{},
		'request':null,
		'request_iresources':null,
		'selected_items':{}
	},


	/*******************************************************************
	 * Инициализация
	 ******************************************************************/

	//Вход на страницу
	enter: function(success, status, data){
		App.Location.addEvent('beforeLoadPage', App.pages[PAGE_NAME].exit);
		this.objects = $unlink(this.defaults);
		this.start(data);
	},//end function



	//Выход со страницы
	exit: function(){
		App.Location.removeEvent('beforeLoadPage', App.pages[PAGE_NAME].exit);
		var self = App.pages[PAGE_NAME];
		self.fullscreen(true);
		self.objects['tables'].each(function(table){
			if(self.objects[table]) self.objects[table].terminate();
		});
		for(var i in self.objects) self.objects[i] = null;
		self.objects = {};
	},//end function



	//Инициализация страницы
	start: function(data){

		$('bigblock_expander').addEvent('click',this.fullscreen);

		//Применение данных
		this.setData(data);


		if(typeOf(this.objects['request'])!='object'){
			$('request_area').hide();
			$('request_none').show();
			$('error_desc').set('text','Заявка с указанным идентификатором не найдена');
			return;
		}

		if(typeOf(this.objects['request_iresources'])!='object'){
			$('request_area').hide();
			$('request_none').show();
			$('error_desc').set('text','Не найдены информационные ресурсы заявки');
			return;
		}

		this.objects['area_request_info'] = build_blockitem({
			'parent': 'area_info',
			'title'	: 'Общие сведения о заявке'
		});
		$('tmpl_request_info').inject(this.objects['area_request_info']['container']).show();

		this.objects['slideshow'] = new jsSlideShow('areas_container');
		$('area_selector').addEvent('change',this.changeAreaSelector.bind(this));

		if(typeOf(REQUEST_LAST_DATA_GET)=='object'){
			if(REQUEST_LAST_DATA_GET['iresource_id']){
				select_set('area_selector', REQUEST_LAST_DATA_GET['iresource_id']);
				this.changeAreaSelector();
			}
		}

	},//end function




	/*******************************************************************
	 * Обработка данных
	 ******************************************************************/

	//Обработка данных
	setData: function(data){
		var type;
		if(typeOf(data)!='object') return;


		//Типы доступа
		if(typeOf(data['ir_types'])=='array'){
			this.objects['ir_types_assoc'] = {'0':'---'};
			this.objects['ir_types'] = data['ir_types'];
			for(var i=0; i<data['ir_types'].length;i++){
				this.objects['ir_types_assoc'][data['ir_types'][i]['item_id']] = data['ir_types'][i]['full_name'];
			}
			buildChecklist({
				'parent': 'add_ir_type_area',
				'options': data['ir_types'],
				'key': 'item_id',
				'value': 'full_name',
				'clear': true
			});
		}//Типы доступа



		//Заявка
		if(typeOf(data['request'])=='object'){
			this.objects['request'] = data['request'];
			var id;
			for(var key in data['request']){
				id = 'info_'+key;
				if(!$(id))continue;
				switch(key){
					case 'request_type':
						$(id).setValue(String(data['request'][key])=='3'?'<font color="red">Блокировка доступа</font>' : '<font color="green">Запрос доступа</font>');
					break;
					default:
					$(id).setValue(data['request'][key]);
				}
			}
			$('bigblock_title').set('text','Карточка заявки ID:'+data['request']['request_id']+' - '+data['request']['employer_name']);
		}//Заявка


		//Информационные ресурсы заявки
		if(typeOf(data['request_iresources'])=='array' && data['request_iresources'].length>0){
			data['request_iresources'].sort(function(a,b){if(a['iresource_name']>b['iresource_name'])return 1;return -1;});
			this.objects['request_iresources'] = {};
			for(var i=0;i<data['request_iresources'].length;i++){
				var iresource_id = data['request_iresources'][i]['iresource_id'];
				this.objects['request_iresources'][iresource_id] = data['request_iresources'][i];
				this.buildIResourceArea(iresource_id);
			}
			data['request_iresources'].unshift({'iresource_id':'info','iresource_name':'-[Общие сведения о заявке]-'});
			select_add({
				'list': 'area_selector',
				'key': 'iresource_id',
				'value': 'iresource_name',
				'options': data['request_iresources'],
				'default': 'info',
				'clear': true
			});
		}//Информационные ресурсы заявки


	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/


	//Создание ИР в слайдере
	buildIResourceArea: function(iresource_id){

		if(typeOf(this.objects['request_iresources'])!='object') return;
		if(typeOf(this.objects['request_iresources'][iresource_id])!='object') return;

		var iresource_div = new Element('div',{
			'class':'steparea',
			'id':'area_'+iresource_id
		}).inject('areas_container').hide();
		var iresource_tabs = $('tmpl_request_iresource').clone().inject(iresource_div).show();
		this.objects['request_iresources'][iresource_id]['tabs'] = new jsTabPanel(iresource_tabs,{});
		this.objects['request_iresources'][iresource_id]['tab_process'] = iresource_tabs.getFirst('.process');
		this.objects['request_iresources'][iresource_id]['tab_objects'] = iresource_tabs.getFirst('.objects');
		this.objects['request_iresources'][iresource_id]['tab_comments'] = iresource_tabs.getFirst('.comments');
		var iresource = this.objects['request_iresources'][iresource_id];

		//Комментарии
		this.setComments(iresource_id);


		//Объекты доступа
		if(String(iresource['route_status'])!='0'&&String(iresource['route_status'])!='100'){
			var save_roles_button = new Element('div',{
				'class':'ui-button-light',
				'events':{
					'click': function(){App.pages[PAGE_NAME].saveRoles(iresource_id);}
				}
			}).inject(iresource['tab_objects']);
			new Element('span',{'text':'Сохранить внесенные изменения'}).inject(save_roles_button);
		}
		this.setRoles(iresource_id);


		//Процесс согласования
		this.setSteps(iresource_id);

	},//end function




	//Применение данных к ИР
	setIResourceData: function(iresource_id, data){

		if(typeOf(this.objects['request_iresources'])!='object') return null;
		if(typeOf(this.objects['request_iresources'][iresource_id])!='object') return null;
		var update_route_assoc = false;
		if(typeOf(data)=='object'){
			for(var key in data){
				switch(key){

					case 'route':
						update_route_assoc = true;
						if(typeOf(data[key])=='array') this.objects['request_iresources'][iresource_id][key] = data[key];
					break;

					case 'steps':
						if(typeOf(data[key])=='array') this.objects['request_iresources'][iresource_id][key] = data[key];
					break;

					default: 
						this.objects['request_iresources'][iresource_id][key] = data[key];
					break;
				}
			}
		}

		if(update_route_assoc || typeOf(this.objects['request_iresources'][iresource_id]['route_assoc'])!='object'){
			var route_assoc = {};
			route = this.objects['request_iresources'][iresource_id]['route'];
			for(var i=0; i<route.length;i++){
				route_assoc[route[i]['step_uid']] = route[i];
			}
			this.objects['request_iresources'][iresource_id]['route_assoc'] = route_assoc;
		}

		var current_step_uid = 0;
		for(var i=0;i<this.objects['request_iresources'][iresource_id]['steps'].length;i++){
			if(String(this.objects['request_iresources'][iresource_id]['current_step']) == String(this.objects['request_iresources'][iresource_id]['steps'][i]['rstep_id'])){
				current_step_uid = this.objects['request_iresources'][iresource_id]['steps'][i]['step_uid'];
				break;
			}
		}
		this.objects['request_iresources'][iresource_id]['current_step_uid'] = current_step_uid;

		return this.objects['request_iresources'][iresource_id];
	},//end function





	//Шаги согласования заявки
	setSteps: function(iresource_id, data){

		var iresource = this.setIResourceData(iresource_id, data);
		if(typeOf(iresource)!='object') return false;

		var tab_process = $(iresource['tab_process']);
		tab_process.getElement('.request_iresource_name').set('html','ID:'+iresource['route_id']+' - '+'<a target="_blank" class="mailto" href="/admin/iresources/info?iresource_id='+iresource['iresource_id']+'">'+iresource['iresource_name']+'</a>');
		tab_process.getElement('.request_iresource_route').set('html','ID:'+iresource['route_id']+' - '+'<a target="_blank" class="mailto" href="/admin/routes/info?route_id='+iresource['route_id']+'">'+iresource['route_name']+'</a>');
		var route_status = '-[???]-';
		switch(String(iresource['route_status'])){
			case '0': route_status = '<font color="red">Заявка отменена</font>'; break;
			case '1': route_status = '<font color="black">В процессе согласования</font>'; break;
			case '2': route_status = '<font color="blue">Заявка приостановлена</font>'; break;
			case '100': route_status = '<font color="green">Заявка исполнена</font>'; break;
		}
		tab_process.getElement('.request_iresource_status').set('html',route_status+' - '+iresource['route_status_desc']);

		var route_area = 'area_process_route_'+iresource_id;
		var steps_area = 'area_process_steps_'+iresource_id;
		if(typeOf(this.objects[route_area])!='object'){
			this.objects[route_area] = build_blockitem({
				'parent': iresource['tab_process'],
				'title'	: 'Маршрут согласования заявки'
			});
			this.objects[route_area]['container'].setStyles({
				'padding':'0px',
				'margin':'0px',
			});
		}
		if(typeOf(this.objects[steps_area])!='object'){
			this.objects[steps_area] = build_blockitem({
				'parent': iresource['tab_process'],
				'title'	: 'История процесса согласования'
			});
			this.objects[steps_area]['container'].setStyles({
				'padding':'0px',
				'margin':'0px',
			});
		}

		this.buildRoute(this.objects[route_area]['container'], iresource);
		this.buildSteps(this.objects[steps_area]['container'], iresource);
	},//end function




	//Построение маршрута согласования
	buildRoute: function(parent, iresource){

		var request_id = iresource['request_id'];
		var iresource_id = iresource['iresource_id'];
		var table_route = 'table_route_'+iresource_id;
		if(typeOf(iresource['route'])!='array'||!iresource['route'].length) return;
		var route_status = iresource['route_status'];
		var current_step_uid = iresource['current_step_uid'];

		if(!this.objects[table_route]){

			this.objects['tables'].push(table_route);
			this.objects[table_route] = new jsTable(parent, {
				'class': 'jsTableLight',
				columns: [
					{
						width:'120px',
						sortable:false,
						caption: 'Действие',
						dataSource:'info',
						dataStyle:{'text-align':'center','min-width':'120px'},
						dataFunction:function(table, cell, text, data){
							return data['info']['action'];
						}
					},
					{
						caption: 'Гейткипер',
						sortable:false,
						width:'160px',
						dataSource:'info',
						dataStyle:{'text-align':'center','min-width':'160px'},
						dataFunction:function(table, cell, text, data){
							return data['info']['gatekeeper'];
						}
					},
					{
						caption: 'Сотрудники',
						sortable:false,
						width:'180px',
						dataSource:'gatekeepers',
						styles:{'min-width':'180px'},
						dataFunction:function(table, cell, text, data){
							if(String(data['step_type'])!='2') return '---';
							if(typeOf(data['gatekeepers'])!='array'||!data['gatekeepers'].length) return '<font color="red">Нет сотрудников</font>';
							var result='';
							for(var i=0; i<data['gatekeepers'].length;i++){
								result += (result==''?'':'<br>')+'<a target="_blank" class="mailto" href="/admin/employers/info?employer_id='+data['gatekeepers'][i]['employer_id']+'">'+data['gatekeepers'][i]['search_name']+'</a>';
							}
							return result;
						}
					},
					{
						caption: 'Информация',
						sortable:false,
						width:'140px',
						dataSource:'step_uid',
						dataStyle:{'text-align':'center','min-width':'140px'},
						dataFunction:function(table, cell, text, data){
							var iresource = App.pages[PAGE_NAME].objects['request_iresources'][iresource_id];
							if(iresource['current_step_uid'] != data['step_uid']){
								if(String(data['step_type'])!='2') return '---';
								var steps = iresource['steps'].filterSelect({
									'step_uid': data['step_uid']
								});
								if(typeOf(steps)!='array'||!steps.length)return '---';
								var current_step = steps[steps.length-1];
								if(String(current_step['step_complete'])!='1') return '---';
								var approved_info = (String(current_step['is_approved'])=='1' ? '<font color="green">Одобрил</font>' : '<font color="red">Отклонил</font>');
								if(String(current_step['gatekeeper_id'])!='0'){
									return 'Гейткипер:<br><a target="_blank" class="mailto" href="/admin/employers/info?employer_id='+current_step['gatekeeper_id']+'">'+current_step['gatekeeper_name']+'</a><br>'+current_step['timestamp']+'<br><b>'+approved_info+'</b>';
								}else
								if(String(current_step['assistant_id'])!='0'){
									return 'Заместитель:<br><a target="_blank" class="mailto" href="/admin/employers/info?employer_id='+current_step['assistant_id']+'">'+current_step['assistant_name']+'</a><br>'+current_step['timestamp']+'<br><b>'+approved_info+'</b>';
								}else{
									return '<font color="red">-[?????]-</font>';
								}
							}
							return '<b>'+(String(data['step_type'])=='2'&&String(iresource['route_status'])=='1' ? 'Текущий шаг, ожидает обработки' : 'Текущий шаг')+'</b>';
						}
					},
					{
						caption: 'Действия',
						sortable:false,
						width:'80px',
						dataSource:'step_uid',
						dataStyle:{'text-align':'center','min-width':'80px'},
						dataFunction:function(table, cell, text, data){
							var iresource = App.pages[PAGE_NAME].objects['request_iresources'][iresource_id];
							var step_uid = data['step_uid'];
							if(String(iresource['current_step_uid']) != String(step_uid) || String(data['step_type'])!='2') return '---';
							if(String(iresource['route_status']) == '0' || String(iresource['route_status'])=='100') return 'Недоступно';
							if(String(iresource['route_status']) == '2'){
								new Element('img',{
									'src': INTERFACE_IMAGES+'/icons/play_16.png',
									'title':'Возобновить процесс согласования',
									'styles':{
										'cursor':'pointer',
										'margin-left':'5px'
									},
									'events':{
										'click': function(){App.pages[PAGE_NAME].stepAction('continue', iresource_id, step_uid);}
									}
								}).inject(cell);
							}else{
								new Element('img',{
									'src': INTERFACE_IMAGES+'/icons/pause_16.png',
									'title':'Приостановить процесс согласования',
									'styles':{
										'cursor':'pointer',
										'margin-left':'5px'
									},
									'events':{
										'click': function(){App.pages[PAGE_NAME].stepAction('pause', iresource_id, step_uid);}
									}
								}).inject(cell);
								new Element('img',{
									'src': INTERFACE_IMAGES+'/icons/accept_16.png',
									'title':'Одобрить заявку на текущем шаге',
									'styles':{
										'cursor':'pointer',
										'margin-left':'5px'
									},
									'events':{
										'click': function(){App.pages[PAGE_NAME].stepAction('approve', iresource_id, step_uid);}
									}
								}).inject(cell);
								if(String(data['gatekeeper_role'])=='1'||String(data['gatekeeper_role'])=='2'){
									new Element('img',{
										'src': INTERFACE_IMAGES+'/icons/decline_16.png',
										'title':'Отклонить заявку на текущем шаге',
										'styles':{
											'cursor':'pointer',
											'margin-left':'5px'
										},
										'events':{
											'click': function(){App.pages[PAGE_NAME].stepAction('decline', iresource_id, step_uid);}
										}
									}).inject(cell);
								}
							}
							new Element('img',{
								'src': INTERFACE_IMAGES+'/icons/stop_16.png',
								'title':'Полностью отменить заявку',
								'styles':{
									'cursor':'pointer',
									'margin-left':'5px'
								},
								'events':{
									'click': function(){App.pages[PAGE_NAME].stepAction('stop', iresource_id, step_uid);}
								}
							}).inject(cell);
							return '';
						}
					}
				],
				'dataBackground1': '#fafafa',
				'dataBackground2': '#fff',
				selectType:1
			});

		}

		this.objects[table_route].setData(iresource['route']);
	},//end function





	//Построение истории процесса согласования
	buildSteps: function(parent, iresource){

		var request_id = iresource['request_id'];
		var iresource_id = iresource['iresource_id'];
		var table_steps = 'table_steps_'+iresource_id;
		if(typeOf(iresource['steps'])!='array'||!iresource['steps'].length) return;

		if(!this.objects[table_steps]){

			this.objects['tables'].push(table_steps);
			this.objects[table_steps] = new jsTable(parent, {
				'class': 'jsTableLight',
				columns: [
					{
						width:'120px',
						sortable:false,
						caption: 'Действие',
						dataSource:'step_uid',
						dataStyle:{'text-align':'center','min-width':'120px'},
						dataFunction:function(table, cell, text, data){
							var step = App.pages[PAGE_NAME].objects['request_iresources'][iresource_id]['route_assoc'][data['step_uid']];
							if(typeOf(step)!='object') return '-[?????]-';
							return step['info']['action'];
						}
					},
					{
						caption: 'Гейткипер',
						sortable:false,
						width:'160px',
						dataSource:'step_uid',
						dataStyle:{'text-align':'center','min-width':'160px'},
						dataFunction:function(table, cell, text, data){
							var step = App.pages[PAGE_NAME].objects['request_iresources'][iresource_id]['route_assoc'][data['step_uid']];
							if(typeOf(step)!='object') return '-[?????]-';
							return step['info']['gatekeeper'];
						}
					},
					{
						caption: 'ФИО сорудника',
						sortable:false,
						width:'180px',
						dataSource:'gatekeeper_id',
						styles:{'min-width':'180px'},
						dataFunction:function(table, cell, text, data){
							if(String(data['step_complete'])=='0') return '---';
							var approved_info = (String(data['is_approved'])=='1' ? '<font color="green">Одобрил</font>' : '<font color="red">Отклонил</font>');
							if(String(data['gatekeeper_id'])!='0'){
								return 'Гейткипер:<br><a target="_blank" class="mailto" href="/admin/employers/info?employer_id='+data['gatekeeper_id']+'">'+data['gatekeeper_name']+'</a><br><b>'+approved_info+'</b>';
							}else
							if(String(data['assistant_id'])!='0'){
								return 'Заместитель:<br><a target="_blank" class="mailto" href="/admin/employers/info?employer_id='+data['assistant_id']+'">'+data['assistant_name']+'</a><br><b>'+approved_info+'</b>';
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

		this.objects[table_steps].setData(iresource['steps']);
	},//end function










	//Сохранение изменений в объектах доступа заявки
	saveRoles: function(iresource_id){
		if(typeOf(this.objects['request_iresources'])!='object') return;
		if(typeOf(this.objects['request_iresources'][iresource_id])!='object') return;
		if(typeOf(this.objects['request_iresources'][iresource_id]['roles'])!='array') return;
		if(typeOf(this.objects['selected_items'][iresource_id])!='object') return;
		var route_status = parseInt(this.objects['request_iresources'][iresource_id]['route_status']);
		if(route_status==0 || route_status==100){
			return App.message('Ошибка', 'Нельзя изменить запрашиваемй функционал в исполненной или отклоненной заявке', 'ERROR');
		}

		var a=[], ir_type, irole_id;

		for(irole_id in this.objects['selected_items'][iresource_id]){
			ir_type = String(this.objects['selected_items'][iresource_id][irole_id]).toInt();
			irole_id= String(irole_id).toInt();
			if(irole_id>0){
				a.push([irole_id, ir_type]);
			}
		}
		if(!a.length) return;
		new axRequest({
			url : '/admin/ajax/requests',
			data:{
				'action':'roles.save',
				'request_id': this.objects['request']['request_id'],
				'iresource_id': iresource_id,
				'a': a
			},
			silent: false,
			display: 'hint',
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setRoles(iresource_id, data['roles']);
				}
			}
		}).request();
	},//end function




	//Роли к заявке
	setRoles: function(iresource_id, roles){
		if(typeOf(this.objects['request_iresources'])!='object') return;
		if(typeOf(this.objects['request_iresources'][iresource_id])!='object') return;
		if(typeOf(roles)=='array') this.objects['request_iresources'][iresource_id]['roles'] = roles;
		var iresource = this.objects['request_iresources'][iresource_id];
		this.buildRoles(iresource['tab_objects'], iresource);
	},//end function




	buildRoles: function(parent, iresource){

		var request_id = iresource['request_id'];
		var iresource_id = iresource['iresource_id'];
		var table_roles = 'table_roles_'+iresource_id;
		this.objects['selected_items'][iresource_id]={};
		if(typeOf(iresource['roles'])!='array'||!iresource['roles'].length) return;
		var is_complete_request = (String(iresource['route_status'])=='0'||String(iresource['route_status'])=='100');
		var is_lock_request = (String(App.pages[PAGE_NAME].objects['request']['request_type'])=='3');

		if(!this.objects[table_roles]){

			this.objects['tables'].push(table_roles);
			this.objects[table_roles] = new jsTable(parent,{
				'class': 'jsTable',
				sectionCollapsible:true,
				columns: [
					{
						width:'20%',
						sortable:false,
						caption: 'Функционал',
						dataSource:'full_name'
					},
					{
						width:'20%',
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
							if(String(text) == '0') return '---';
							if(is_lock_request) return '-[Блокировать]-';
							if(App.pages[PAGE_NAME].objects['ir_types_assoc'][text]){
								return App.pages[PAGE_NAME].objects['ir_types_assoc'][text];
							}
							return '-[? ID='+text+']-';
						}
					},
					{
						caption: 'Установлен гейткипером',
						sortable:false,
						width:'80px',
						dataSource:'ir_types',
						dataStyle:{'text-align':'center','min-width':'80px'},
						dataFunction:function(table, cell, text, data){
							if(is_complete_request){
								if(String(data['ir_selected'])=='0') return '---';
								if(is_lock_request) return '-[Блокировать]-';
								return (App.pages[PAGE_NAME].objects['ir_types_assoc'][data['ir_selected']] ? App.pages[PAGE_NAME].objects['ir_types_assoc'][data['ir_selected']] : '-[?????]-');
							}
							var result=[['0','-- Нет --']];
							if(is_lock_request){
								result.push(['1','-[Блокировать]-']);
							}else{
								if(typeOf(text)=='array'&&text.length>0){
									for(var i=0; i<text.length;i++){
										if(App.pages[PAGE_NAME].objects['ir_types_assoc'][text[i]]){
											result.push([text[i],App.pages[PAGE_NAME].objects['ir_types_assoc'][text[i]]]);
										}
									}
								}
							}
							var irt = select_add({
								'parent': cell,
								'options': result,
								'default': data['ir_selected']
							});
							irt.store('iresource_id',data['iresource_id']);
							irt.store('irole_id',data['irole_id']);
							irt.addEvent('change',function(){
								var iresource_id = this.retrieve('iresource_id');
								var irole_id = this.retrieve('irole_id');
								if(!iresource_id || !irole_id) return false;
								App.pages[PAGE_NAME].objects['selected_items'][iresource_id][irole_id] = select_getValue(this);
							});
							return '';
						}
					},
					{
						caption: 'Изменения',
						sortable:false,
						width:'140px',
						dataSource:'update_type',
						dataStyle:{'text-align':'center','min-width':'140px','font-size':'10px'},
						dataFunction:function(table, cell, text, data){
							var action = '';
							switch(String(data['update_type'])){
								case '0': return '-не менялся-';
								case '1': action = 'Добавлено'; break;
								case '2': action = 'Изменено'; break;
								case '3': action = 'Удалено'; break;
							}
							return action +'<br>'+ data['update_time'] +'<br>'+ (String(data['gatekeeper_id'])=='0'? 'Администратор' : '<a target="_blank" class="mailto" href="/admin/employers/info?employer_id='+data['gatekeeper_id']+'">'+data['gatekeeper_name']+'</a>');
						}
					}
				],
				'dataBackground1': '#fafafa',
				'dataBackground2': '#fff',
				selectType:1
			});

		}

		this.objects[table_roles].setData(iresource['roles']);
	},//end function






	//Комментарии к заявке
	setComments: function(iresource_id, comments){
		if(typeOf(this.objects['request_iresources'])!='object') return;
		if(typeOf(this.objects['request_iresources'][iresource_id])!='object') return;
		if(typeOf(comments)=='array') this.objects['request_iresources'][iresource_id]['comments'] = comments;
		var iresource = this.objects['request_iresources'][iresource_id];
		this.buildComments(iresource['tab_comments'], iresource);
	},//end function



	//Построение комментариев
	buildComments: function(parent, iresource){

		parent.empty();

		//Комментарии
		var icomments_area = new Element('div').inject(parent);
		var comment_list = new Element('ul',{'class':'commentlist'}).inject(icomments_area);
		var request_id = iresource['request_id'];
		var iresource_id = iresource['iresource_id'];

		if(typeOf(iresource['comments'])!='array'||!iresource['comments'].length){
			new Element('h2',{'html':'Комментарии отсутствуют'}).inject(comment_list);
		}else{
			this.buildCommentItems(comment_list, iresource['comments']);
		}

		if(String(iresource['route_status'])!='0'&&String(iresource['route_status'])!='100'){
			var add_comment_funct = function(e){
				App.comment('Добавить комментарий','',function(comment){
					comment = String(comment).trim();
					if(!comment.length) return;
					new axRequest({
						url : '/admin/ajax/requests',
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
								App.pages[PAGE_NAME].setComments(iresource_id, data['comments']);
							}
						}
					}).request();
				});
				e.stop();
			};

			var comment_button = new Element('div',{
				'class':'ui-button-light',
				'events':{
					'click': add_comment_funct
				}
			}).inject(icomments_area);
			new Element('span',{'text':'Добавить комментарий'}).inject(comment_button);
		}
	},//end function



	//Построение списка комментариев
	buildCommentItems: function(comment_list, comments){
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
	},//end function







	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/

	//Развертывание окна на весь экран
	fullscreen: function(normal_screen){
		var panel = $('page_requests_info');
		var title = $('bigblock_title').getParent('.titlebar');
		if(title.hasClass('expanded')||normal_screen==true){
			title.removeClass('expanded').addClass('normal');
			panel.inject($('adminarea'));
		}else{
			title.removeClass('normal').addClass('expanded');
			panel.inject(document.body);
		}
	},//end function



	//Выбор ИР
	changeAreaSelector: function(){
		this.showArea($('area_selector').getValue());
	},//end function



	//Навигация по страницам
	showArea: function(action){
		switch(action){
			case 'empty': return;
		}

		//Слайд
		if($('area_'+action)){
			this.objects['slideshow'].show($('area_'+action), {
				transition: 'fadeThroughBackground'
			});
		}

	},//end function



	//Действия над текущим шагом маршрута
	stepAction: function(action, iresource_id, step_uid){
		if(typeOf(this.objects['request_iresources'])!='object') return;
		if(typeOf(this.objects['request_iresources'][iresource_id])!='object') return;
		if(typeOf(this.objects['request_iresources'][iresource_id]['roles'])!='array') return;
		if(typeOf(this.objects['selected_items'][iresource_id])!='object') return;
		var route_id = this.objects['request_iresources'][iresource_id]['route_id'];
		var rstep_id = this.objects['request_iresources'][iresource_id]['current_step'];
		var step_action = false;
		var step_description = '';

		switch(action){
			case 'stop': step_action = 'request.step.stop';step_description = 'полностью отменить'; break;
			case 'pause': step_action = 'request.step.pause';step_description = 'приостановить'; break;
			case 'continue': step_action = 'request.step.continue';step_description = 'возобновить'; break;
			case 'decline': step_action = 'request.step.decline';step_description = 'отклонить на данном шаге и продолжить'; break;
			case 'approve': step_action = 'request.step.approve'; step_description = 'одобрить на данном шаге и продолжить'; break;
		}

		if(!step_action) return;

		App.message(
			'Подтвердите действие',
			"Вы действительно хотите <font color='white'><b>"+step_description+"</b></font> обработку заявки по данному маршруту?",
			'CONFIRM',
			function(){

				new axRequest({
					url : '/admin/ajax/requests',
					data:{
						'action': step_action,
						'request_id': App.pages[PAGE_NAME].objects['request']['request_id'],
						'iresource_id': iresource_id,
						'route_id': route_id,
						'rstep_id': rstep_id,
						'step_uid': step_uid
					},
					silent: false,
					display: 'hint',
					waiter: true,
					callback: function(success, status, data){
						if(success){
							App.pages[PAGE_NAME].setSteps(iresource_id, data);
						}
					}
				}).request();

			}
		);

	},//end function



	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();