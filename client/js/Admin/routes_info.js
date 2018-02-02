;(function(){
var PAGE_NAME = 'routes_info';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': ['table_params'],
		'validators': ['form_anket'],
		'form_anket': null,
		'table_params':null,
		//
		'companies':null,
		'companies_assoc':null,
		'groups': null,
		'posts':{},
		'anket': null,
		'steps': null,
		'iresources':null,
		'post_selected':null,
		'gatekeeper_post': null,
		'employer_selected': null,
		'gatekeeper_employers': null,
		'selected_employer_object': null,
		'scheme_change': false,
		'param_employer':null,
		'param_post':null
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
		self.objects['validators'].each(function(validator){
			if(self.objects[validator]) self.objects[validator].destroy();
		});
		self.workflowClear();
		for(var i in self.objects) self.objects[i] = null;
		self.objects = {};
	},//end function



	//Инициализация страницы
	start: function(data){

		$('bigblock_expander').addEvent('click',this.fullscreen.bind(this));

		this.objects['tabs'] = new jsTabPanel('tabs_area',{
			'onchange': this.changeTab.bind(this)
		});

		//Проверка формы
		$('route_info_save_button').addEvent('click',this.anketSave.bind(this));
		this.objects['form_anket'] = new jsValidator($('anket_form'));
		this.objects['form_anket'].required('info_full_name');


		//Данные
		this.setData(data);

		if(typeOf(this.objects['route'])!='object'){
			$('tabs_area').hide();
			$('tabs_none').show();
			return;
		}


		$('workflow_new_button').addEvent('click',this.workflowNew.bind(this));
		$('workflow_reload_button').addEvent('click',this.workflowReload.bind(this));
		$('workflow_save_button').addEvent('click',this.workflowSave.bind(this));

		$('unit_add_button').addEvent('click',this.uselectorOpen.bind(this));
		$('unit_selector_complete_button').addEvent('click',this.uselectorComplete.bind(this));
		$('unit_selector_cancel_button').addEvent('click',this.uselectorClose.bind(this));
		$('unit_type').addEvent('change',this.uselectorSelectUnitType.bind(this));
		$('gatekeeper_type').addEvent('change',this.uselectorSelectGatekeeperType.bind(this));


		//post selector
		this.objects['orgchart'] = new jsOrgChart('post_selector_org_structure_area', {dragAndDrop: false});
		this.objects['orgchart'].addEvents({
			'selectNode': this.postSelect.bind(this)
		});
		$('posts_filter').addEvent('keydown',function(e){if(e.code==13) App.pages[PAGE_NAME].postFilter();});
		$('posts_filter_button').addEvent('click',this.postFilter.bind(this));
		$('post_selector_companies_select').addEvent('change',this.postChangeCompany.bind(this));
		this.objects['post_splitter'] = set_splitter_h({
			'left'		: $('post_selector_companies_area'),
			'right'		: $('post_selector_org_structure'),
			'splitter'	: $('post_selector_splitter'),
			'parent'	: $('post_selector_wrapper')
		});
		$('post_selector_cancel_button').addEvent('click',this.postClose.bind(this));
		$('post_selector_complete_button').addEvent('click',this.postComplete.bind(this));

		//gatekeeper post
		$('change_gatekeeper_post_button').addEvent('click',this.uselectorGatekeeperPostOpen.bind(this));
		$('change_gatekeeper_post_cancel_button').addEvent('click',this.uselectorGatekeeperPostCancel.bind(this));

		//employer selector
		$('employer_selector_term').addEvent('keydown',function(e){if(e.code==13) App.pages[PAGE_NAME].employerSearch();});
		$('employer_selector_term_button').addEvent('click',this.employerSearch.bind(this));
		$('employer_selector_cancel_button').addEvent('click',this.employerClose.bind(this));
		$('employer_selector_complete_button').addEvent('click',this.employerComplete.bind(this));

		//gatekeeper employer
		$('change_gatekeeper_employer_button').addEvent('click',this.uselectorGatekeeperEmployerOpen.bind(this));
		$('change_gatekeeper_employer_cancel_button').addEvent('click',this.uselectorGatekeeperEmployerCancel.bind(this));


		//params
		$('param_del_button').addEvent('click',this.paramDelete.bind(this));
		$('param_add_button').addEvent('click',this.pselectorOpen.bind(this));
		$('param_selector_cancel_button').addEvent('click',this.pselectorClose.bind(this));
		$('param_selector_complete_button').addEvent('click',this.pselectorComplete.bind(this));

		$('change_param_employer_button').addEvent('click',this.pselectorEmployerOpen.bind(this));
		$('change_param_employer_cancel_button').addEvent('click',this.pselectorEmployerCancel.bind(this));

		$('change_param_post_button').addEvent('click',this.pselectorPostOpen.bind(this));
		$('change_param_post_cancel_button').addEvent('click',this.pselectorPostCancel.bind(this));

	},//end function




	/*******************************************************************
	 * Обработка данных
	 ******************************************************************/

	//Обработка данных
	setData: function(data){
		var type;
		if(typeOf(data)!='object') return;

		//Организации
		if(typeOf(data['companies'])=='array'){
			this.objects['companies_assoc'] = {'0':'<b>-[Все организации]-</b>'};
			data['companies'].sort(function(a,b){if(a['full_name']>b['full_name'])return 1;return -1;});
			this.objects['companies'] = $unlink(data['companies']);
			this.objects['companies'].unshift({'company_id':'0','full_name':'<b>-[Все организации]-</b>'});
			for(var i=0;i<data['companies'].length;i++){
				this.objects['companies_assoc'][data['companies'][i]['company_id']] = data['companies'][i]['full_name'];
			}
			select_add({
				'list': 'post_selector_companies_select',
				'key': 'company_id',
				'value': 'full_name',
				'options': data['companies'],
				'default': 0,
				'clear': true
			});
		}//Организации


		//Группы
		if(typeOf(data['groups'])=='array'){
			data['groups'].sort(function(a,b){if(a['full_name']>b['full_name'])return 1;return -1;});
			this.objects['groups'] = data['groups'];
			select_add({
				'list': 'gatekeeper_group',
				'key': 'group_id',
				'value': 'full_name',
				'options': data['groups'],
				'default': 0,
				'clear': true
			});
			this.objects['groups'].unshift({'group_id':'0','full_name':'-[Любая группа]-'});
			select_add({
				'list': 'param_for_group',
				'key': 'group_id',
				'value': 'full_name',
				'options': this.objects['groups'],
				'default': 0,
				'clear': true
			});
		}//Группы


		//Список информационных ресурсов
		if(typeOf(data['iresources'])=='array'){
			data['iresources'].sort(function(a,b){if(a['full_name']>b['full_name'])return 1;return -1;});
			this.objects['iresources'] = data['iresources'];
			this.objects['iresources'].unshift({'iresource_id':'0','full_name':'-[Любой информационный ресурс]-'});
			select_add({
				'list': 'param_for_resource',
				'key': 'iresource_id',
				'value': 'full_name',
				'options':  data['iresources'],
				'default': 0,
				'clear': true
			});
		}//Список информационных ресурсов


		//Опции маршрута
		if(typeOf(data['route'])=='object'){
			this.objects['route'] = data['route'];
			var id;
			for(var key in data['route']){
				id = 'info_'+key;
				if(!$(id))continue;
				$(id).setValue(data['route'][key]);
			}
			$('bigblock_title').set('text','Карточка маршрута ID:'+data['route']['route_id']+' - '+data['route']['full_name']);
		}//Опции маршрута


		//Маршрут
		if(typeOf(data['steps'])=='array'){

			this.objects['steps'] = data['steps'];
			this.workflowClear();
			var einfo, target, block;

			if(!data['steps'].length){
				this.workflowNewOperation();
			}else{
				for(var i=0;i<data['steps'].length;i++){

					block = data['steps'][i];
					block['unit_type'] = block['step_type'];

					einfo = this.unitAdd({
						'uid': String(block['step_uid']),
						'unit_type': String(block['unit_type']),
						'gatekeeper_type': String(block['gatekeeper_type']),
						'gatekeeper_role': String(block['gatekeeper_role']),
						'gatekeeper_id': String(block['gatekeeper_id']),
						'x': String(block['pos_x']),
						'y': String(block['pos_y']),
						'step_yes': String(block['step_yes']),
						'step_no': String(block['step_no']),
						'text': String(block['text'])
					});
					block['el'] = einfo.d;
					block['el_id'] = einfo.id;
				}

				for(var i=0;i<data['steps'].length;i++){
					block = data['steps'][i];
					block['unit_type'] = block['step_type'];

					if(block['unit_type'] == 3 || block['unit_type'] == 4) continue;
					
					if(block['unit_type'] == 1){
						if(String(block['step_yes'])!='0' && typeOf(block['el'])=='element'){
							target = data['steps'].filterResult('el', 'step_uid', block['step_yes']);
							if(typeOf(target)=='element'){
								jsPlumb.connect({uuids:[block['el'].id+"BottomCenter", target.id+"TopCenter"]});
							}
						}
						continue;
					}//unit_type = 1

					if(String(block['step_yes'])!='0' && typeOf(block['el'])=='element'){
						target = data['steps'].filterResult('el', 'step_uid', block['step_yes']);
						if(typeOf(target)=='element'){
							jsPlumb.connect({uuids:[block['el'].id+"RightMiddle", target.id+"TopCenter"]});
						}
					}

					if( String(block['gatekeeper_role']) != '3' && String(block['gatekeeper_role']) != '4' &&
					String(block['step_no'])!='0' && typeOf(block['el'])=='element'){
						target = data['steps'].filterResult('el', 'step_uid', block['step_no']);
						if(typeOf(target)=='element'){
							jsPlumb.connect({uuids:[block['el'].id+"LeftMiddle", target.id+"TopCenter"]});
						}
					}
				}//for
			}
			this.changeStatus(false);
		}//Маршрут


		//Параметры маршрута
		if(typeOf(data['params'])=='array'){
			this.paramsDataSet(data['params']);
		}//Параметры маршрута


	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/

	//Установка статуса изменения схемы маршрута
	changeStatus: function(is_changed){
		this.objects['scheme_change'] = is_changed;
		if(!is_changed){
			App.Location.setBeforeExitFunction(null);
			return;
		}
		App.Location.setBeforeExitFunction(function(data){
			App.pages[PAGE_NAME].checkChange(function(){
				App.Location.setBeforeExitFunction(null);
				App.Location.doPage(data);
			});
		});
	},//end function


	//Проверка была ли изменена схема маршрута
	checkChange: function(callback){
		if(!this.objects['scheme_change']) return callback();
		App.message(
			'Подтвердите действие',
			'Вы вносили изменения в схему маршрута, но не сохранили ее, продолжить без сохранения?',
			'CONFIRM',
			function(){
				return callback();
			}
		);
	},//end function


	//Построение параметров маршрута
	paramsDataSet: function(data){
		$('param_tool_edit').hide();
		if(!data.length){
			$('table_params_area').hide();
			$('table_params_none').show();
			return;
		}else{
			$('table_params_none').hide();
			$('table_params_area').show();
		}

		if(!this.objects['table_params']){
			this.objects['table_params'] = new jsTable('table_params',{
				'dataBackground1':'#efefef',
				columns:[
					{
						caption: 'ID условия',
						sortable:true,
						dataSource:'param_id',
						width:60,
						dataStyle:{'text-align':'center'}
					},
					{
						caption: 'Для сотрудника',
						styles:{'min-width':'100px'},
						dataStyle:{'text-align':'left'},
						dataSource:'for_employer',
						dataFunction:function(table, cell, text, data){
							return (String(text)!='0' ? '<a target="_blank" class="mailto" href="/admin/employers/info?employer_id='+data['for_employer']+'">'+data['for_employer_name']+'</a>' : 'Для всех сотрудников');
						}
					},
					{
						caption: 'Для ресурса',
						styles:{'min-width':'100px'},
						dataStyle:{'text-align':'left'},
						dataSource:'for_resource',
						dataFunction:function(table, cell, text, data){
							return (String(text)!='0' ? '<a target="_blank" class="mailto" href="/admin/iresources/info?iresource_id='+data['for_resource']+'">'+data['for_resource_name']+'</a>' : 'Для всех ресурсов');
						}
					},
					{
						caption: 'Для организации',
						styles:{'min-width':'100px'},
						dataStyle:{'text-align':'left'},
						dataSource:'for_company',
						dataFunction:function(table, cell, text, data){
							return (text!='0' ? data['for_company_name'] : 'Для всех организаций');
						}
					},
					{
						caption: 'Для должности',
						styles:{'min-width':'100px'},
						dataStyle:{'text-align':'left'},
						dataSource:'for_post',
						dataFunction:function(table, cell, text, data){
							return (text!='0' ? data['for_post_name'] : 'Для всех должностей');
						}
					},
					{
						caption: 'Для группы',
						styles:{'min-width':'100px'},
						dataStyle:{'text-align':'left'},
						dataSource:'for_group',
						dataFunction:function(table, cell, text, data){
							return (text!='0' ? data['for_group_name'] : 'Для всех групп');
						}
					}
				]
			});
			this.objects['table_params'].addEvent('click', this.selectParam.bind(this));
		}//not set table_params

		this.objects['table_params'].setData(data);
		this.selectParam();
	},//end function





	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/

	//Развертывание окна на весь экран
	fullscreen: function(normal_screen){
		var panel = $('page_routes_info');
		var title = $('bigblock_title').getParent('.titlebar');
		if(title.hasClass('expanded')||normal_screen==true){
			title.removeClass('expanded').addClass('normal');
			panel.inject($('adminarea'));
		}else{
			title.removeClass('normal').addClass('expanded');
			panel.inject(document.body);
		}
	},//end function



	//Смена вкладки
	changeTab: function(index){
		
		switch(index){
			
			
			//Маршрут
			case 2:
				if(typeOf(this.objects['steps'])!='array'){
					this.stepsLoad();
				}
			break;
		}
	},


	//Сохранение
	anketSave: function(){
		if(!this.objects['form_anket'].validate()) return;
		var anket = {
			'action': 'route.edit',
			'route_id': this.objects['route']['route_id'],
			'full_name': $('info_full_name').getValue(),
			'description': $('info_description').getValue(),
			'route_type': $('info_route_type').getValue(),
			'is_lock': $('info_is_lock').getValue(),
			'is_default': $('info_is_default').getValue(),
			'priority': $('info_priority').getValue()
		};
		new axRequest({
			url : '/admin/ajax/routes',
			data: anket,
			silent: false,
			waiter: true,
			display: 'hint',
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
				}
			}
		}).request();
	},//end function



	//Загрузка шагов маршрута
	stepsLoad: function(){
		new axRequest({
			url : '/admin/ajax/routes',
			data: {
				'action': 'route.steps.load',
				'route_id': this.objects['route']['route_id']
			},
			silent: true,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
				}
			}
		}).request();
	},//end function



	//Выбор параметра маршрута
	selectParam: function(){
		if(!this.objects['table_params'].selectedRows.length){
			$('param_tool_edit').hide();
			return;
		}
		$('param_tool_edit').show();
	},//end function




	/*******************************************************************
	 * Функции Params
	 ******************************************************************/


	//Добавление параметра: открыть окно
	pselectorOpen: function(){
		$('bigblock_wrapper').hide();
		this.pselectorEmployerCancel();
		this.pselectorPostCancel();
		$('param_selector').show();
	},//end function



	//Добавление параметра: закрыть окно
	pselectorClose: function(){
		$('param_selector').hide();
		$('bigblock_wrapper').show();
	},//end function


	//Добавление параметра
	pselectorComplete: function(){
		this.pselectorClose();
		var param = {
			'action': 'route.param.new',
			'route_id': this.objects['route']['route_id'],
			'for_employer': (typeOf(this.objects['param_employer'])=='object' ? this.objects['param_employer']['employer_id'] : 0),
			'for_resource': $('param_for_resource').getValue(),
			'for_company': (typeOf(this.objects['param_post'])=='object' ? this.objects['param_post']['company_id'] : 0),
			'for_post': (typeOf(this.objects['param_post'])=='object' ? this.objects['param_post']['post_uid'] : 0),
			'for_group': $('param_for_group').getValue()
		};
		new axRequest({
			url : '/admin/ajax/routes',
			data: param,
			silent: false,
			waiter: true,
			display: 'hint',
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
				}
			}
		}).request();
	},//end function


	//Выбор сотрудника
	pselectorEmployerOpen: function(){
		this.objects['param_employer'] = null;
		this.objects['employer_callback'] = this.pselectorEmployerComplete.bind(this);
		this.employerOpen();
	},//end function



	//Отмена выбора сотрудника
	pselectorEmployerCancel: function(){
		$('param_employer_area').hide();
		$('param_employer_none').show();
		$('change_param_employer_cancel_button').hide();
		this.objects['param_employer'] = null;
	},//end function



	//Cотрудник выбран
	pselectorEmployerComplete: function(){
		if(typeOf(this.objects['employer_selected'])=='object'){
			this.objects['param_employer'] = $unlink(this.objects['employer_selected']);
			$('param_employer_none').hide();
			$('param_employer_area').show();
			$('change_param_employer_cancel_button').show();
			$('param_employer_name').setValue(this.objects['param_employer']['search_name']);
			$('param_employer_id').setValue(this.objects['param_employer']['employer_id']);
		}
	},//end function


	//Выбор должности
	pselectorPostOpen: function(){
		this.objects['post_selected'] = $unlink(this.objects['param_post']);
		this.objects['post_callback'] = this.pselectorPostComplete.bind(this);
		this.postOpen();
	},//end function


	//Отмена выбора должности
	pselectorPostCancel: function(){
		$('param_post_area').hide();
		$('param_post_none').show();
		$('change_param_post_cancel_button').hide();
		this.objects['param_post'] = null;
	},//end function



	//Должность выбрана
	pselectorPostComplete: function(){
		if(typeOf(this.objects['post_selected'])=='object' && this.objects['post_selected']['company_id']!=0){
			this.objects['param_post'] = $unlink(this.objects['post_selected']);
			$('param_post_none').hide();
			$('param_post_area').show();
			$('change_param_post_cancel_button').show();
			$('param_company_name').setValue(this.objects['param_post']['company_name']);
			$('param_post_name').setValue((this.objects['param_post']['post_uid']!=0 ? this.objects['param_post']['post_name'] : '-[Любой сотрудник организации]-'));
		}else{
			this.pselectorPostCancel();
		}
	},//end function



	//Удаление выбранного параметра маршрута
	paramDelete: function(){
		if(!this.objects['table_params'].selectedRows.length) return;
		var tr = this.objects['table_params'].selectedRows[0];
		if(typeOf(tr)!='element') return;
		var data = tr.retrieve('data');
		if(typeOf(data)!='object') return;
		var param_id = data['param_id'];
		var route_id = data['route_id'];
		App.message(
			'Подтвердите действие',
			'Вы действительно хотите удалить выбранный параметр маршрута?',
			'CONFIRM',
			function(){
				new axRequest({
					url : '/admin/ajax/routes',
					data:{
						'action':'route.param.delete',
						'route_id': route_id,
						'param_id': param_id
					},
					silent: false,
					waiter: true,
					callback: function(success, status, data){
						if(success){
							App.pages[PAGE_NAME].setData(data);
						}
					}
				}).request();
			}
		);
	},//end function





	/*******************************************************************
	 * Функции WorkFlow
	 ******************************************************************/

	prevSelected: null,

	connectorСolor: "#8E9BAC",
	endpointRadius: 20,

	connectorPaintStyleNo: {
		strokeStyle: "#FF0000"
	},

	connectorPaintStyle: {
		lineWidth:3,
		strokeStyle: this.connectorСolor,
		joinstyle:"round",
		outlineColor:"white",
		outlineWidth:1
	},
	connectorHoverStyle: {
		lineWidth:3,
		strokeStyle:"#ec9f2e"
	},

	sourceEndpointYes: {
		endpoint:"Dot",
		paintStyle:{ fillStyle:"#558822",radius:11},
		isSource:true,
		connector:[ "Flowchart", { } ],
		connectorStyle:this.connectorPaintStyle,
		hoverPaintStyle:this.connectorHoverStyle,
		connectorHoverStyle:this.connectorHoverStyle,
		dragOptions:{},
		uniqueEndpoint:true,
		maxConnections:1,
		scope:'workflow',
		'connectorTooltip': 'sourceEndpointYes'
	},
	sourceEndpointNo: {
		endpoint:"Dot",
		paintStyle:{ fillStyle:"#AA5522",radius:11},
		isSource:true,
		connector:[ "Flowchart", {} ],
		connectorStyle:this.connectorPaintStyleNo,
		hoverPaintStyle:this.connectorHoverStyle,
		connectorHoverStyle:this.connectorHoverStyle,
		dragOptions:{},
		uniqueEndpoint:true,
		maxConnections:1,
		scope:'workflow',
		'connectorTooltip': 'sourceEndpointNo'
	},

	targetEndpoint: {
		endpoint:"Dot",	
		paintStyle:{ fillStyle:"#225588",radius: 11},
		connectorStyle:this.connectorPaintStyle,
		hoverPaintStyle:this.connectorHoverStyle,
		connectorHoverStyle:this.connectorHoverStyle,
		maxConnections:-1,
		uniqueEndpoint:false,
		dropOptions:{ hoverClass:"hover", activeClass:"active" },
		suspendedEndpoint:{},
		isTarget:true,
		scope:'workflow',
		'connectorTooltip': 'targetEndpoint'
	},
	sourceEndpointBegin: {
		endpoint:"Dot",
		paintStyle:{ fillStyle:"#558822",radius:11},
		isSource:true,
		connector:[ "Flowchart", { midpoint: 0.5 } ],
		connectorStyle:this.connectorPaintStyle,
		hoverPaintStyle:this.connectorHoverStyle,
		connectorHoverStyle:this.connectorHoverStyle,
		dragOptions:{},
		uniqueEndpoint:true,
		maxConnections:1,
		scope:'workflow',
		'connectorTooltip': 'sourceEndpointBegin'
	},


	//Инициализация
	workflowInit: function() {

		jsPlumb.setRenderMode(jsPlumb.SVG);

		jsPlumb.importDefaults({
			DragOptions : { cursor: "pointer", zIndex:2000},
			PaintStyle : { strokeStyle:this.connectorСolor, lineWidth:2 },
			EndpointStyle : { radius:this.endpointRadius, fillStyle:this.connectorСolor, zIndex:25 },
			HoverPaintStyle : {strokeStyle:"#ec9f2e" },
			EndpointHoverStyle : {fillStyle:"#ec9f2e" },
			ConnectorZIndex: 5,
			ConnectionOverlays : [
				[ "Arrow", { location:0.75 } ],
				[ "Arrow", { location:0.25 } ]
			]
		});

		//Присоединение
		jsPlumb.bind("jsPlumbConnection", function(connection) {
			var dataSource = connection.source.retrieve('org_data');
			var dataTarget = connection.target.retrieve('org_data');
			var is_Yes = (this._getSourceType(connection) == "RightMiddle" ? true : false);
			if(dataSource['unit_type'] == '1'){
				dataSource['step_yes'] = dataTarget['uid'];
				dataSource['step_no'] = dataTarget['uid'];
			}else{
				if(dataSource['gatekeeper_role'] == '3' || dataSource['gatekeeper_role'] == '4'){
					dataSource['step_yes'] = dataTarget['uid'];
					dataSource['step_no'] = dataTarget['uid'];
				}else{
					dataSource[(is_Yes ? 'step_yes' : 'step_no')] = dataTarget['uid'];
				}
			}
			connection.source.store('org_data', dataSource);
			this.changeStatus(true);
		}.bind(this));

		//Отсоединение
		jsPlumb.bind("click", function(connection, originalEvent) {
			jsPlumb.detach(connection); 
		}.bind(this));

		//Отсоединение
		jsPlumb.bind("jsPlumbConnectionDetached", function(connection) {
			var dataSource = connection.source.retrieve('org_data');
			var dataTarget = connection.target.retrieve('org_data');
			var is_Yes = (String(dataSource['step_yes']) == String(dataTarget['uid']) ? true : false);
			if(String(dataSource['unit_type']) == '1'){
				dataSource['step_yes'] = 0;
				dataSource['step_no'] = 0;
			}else{
				if(String(dataSource['gatekeeper_role']) == '3' || String(dataSource['gatekeeper_role']) == '4'){
					dataSource['step_yes'] = 0;
					dataSource['step_no'] = 0;
				}else{
					dataSource[(is_Yes ? 'step_yes' : 'step_no')] = 0;
				}
			}
			connection.source.store('org_data', dataSource);
			jsPlumb.detach(connection); 
			this.changeStatus(true);
		}.bind(this));

		//Перед окончанием соединения
		jsPlumb.bind("beforeDrop", function(connection) {
			//Если идентификаторы равны, соединение не устанавливаем
			if(connection.sourceId == connection.targetId){
				return false;
			}
			return true;
		}.bind(this));


	},//end function



	//
	_getSourceType: function(connection){
		var anchors = connection.sourceEndpoint.anchor.getAnchors();
		if(anchors.length < 1) return false;
		return anchors[0].type;
	},//end function



	//Подготовка точек входа-выхода коннекторов
	prepareConnectors: function(elId, data){
		switch(String(data['unit_type'])){
			//Блок - начало
			case '1':
				return jsPlumb.addEndpoint(elId, this.sourceEndpointBegin, {anchors: ["BottomCenter"], uuid:elId+"BottomCenter"});
			break;
			
			//Блок - гейткипер
			case '2':
				jsPlumb.addEndpoint(elId, this.sourceEndpointYes, {anchors: ["RightMiddle"], uuid:elId+"RightMiddle"});
				if(data['gatekeeper_role'] != '3' && data['gatekeeper_role'] != '4')
					jsPlumb.addEndpoint(elId, this.sourceEndpointNo, {anchors: ["LeftMiddle"], uuid:elId+"LeftMiddle"});
				jsPlumb.addEndpoint(elId, this.targetEndpoint, {anchors: ["TopCenter"], uuid:elId+"TopCenter"});
			break;
			
			//Блок, конец, исполнено
			case '3':
				return  jsPlumb.addEndpoint(elId, this.targetEndpoint, {anchors: ["TopCenter"], uuid:elId+"TopCenter"});
			break;
			
			//Блок, конец, отклонено
			case '4':
				return jsPlumb.addEndpoint(elId, this.targetEndpoint, {anchors: ["TopCenter"], uuid:elId+"TopCenter"});
			break;
		}
	},//end function



	//Создание блока
	unitCreate: function(data){
		var d = new Element('div',{}).addClass('unit').inject($('workflow'));
		if(String(data['unit_type'])!='2'){
			d.addClass('beblock');
			switch(String(data['unit_type'])){
				case '1': d.addClass('start'); break;
				case '3': d.addClass('success'); break;
				case '4': d.addClass('fails'); break;
			}
		}
		var id = '' + ((new Date().getTime())), _d = jsPlumb.CurrentLibrary.getElementObject(d);
		d.set('id',id).store('org_data',data);
		if(typeof data['text'] == 'string') d.set('html', data['text']);
		d.addEvent('click',function(e){
			if(App.pages[PAGE_NAME].selectUnit(this) === false) return;
			if(typeOf(App.pages[PAGE_NAME].prevSelected)=='element') App.pages[PAGE_NAME].prevSelected.removeClass('selected').addClass('unselected');
			this.removeClass('unselected').addClass('selected');
			App.pages[PAGE_NAME].prevSelected = this;
		}.bind(d));
		var w = 300, h = 300;
		d.style.top= data['y'] + 'px';
		d.style.left= data['x'] + 'px';
		
		var toolarea = new Element('div',{}).addClass('unit_tool').inject(d);

		var bdetach = new Element('img',{
			'src' : INTERFACE_IMAGES+'/unit_detach.png',
			'cursor':'pointer',
			'title':'Удалить связи'
		}).inject(toolarea).addEvent('click',function(e){
			jsPlumb.detachAllConnections(this.id);
		}.bind(d));

		var bdel = new Element('img',{
			'src' : INTERFACE_IMAGES+'/unit_remove.png',
			'cursor':'pointer',
			'title':'Удалить элемент'
		}).inject(toolarea).addEvent('click',function(e){
			jsPlumb.detachAllConnections(this.id);
			jsPlumb.removeAllEndpoints(this.id);
			this.parentNode.removeChild(this);
		}.bind(d));

		return {d:d, id:id};
	},//end function



	//Добавление блока
	unitAdd: function(data){
		var info = this.unitCreate(data);
		var e = this.prepareConnectors(info.id, data);
		jsPlumb.draggable(info.id);
		return info;
	},//end function




	//Проверка на существование в схеме блока определенного типа
	unitExists: function(obj){
		var fields = $$('.unit');
		var data;
		for(var i=0; i<fields.length;i++){
			data = fields[i].retrieve('org_data');
			if(typeOf(data)!='object') continue;
			if(obj['unit_type'] != 2 && obj['unit_type'] == data['unit_type']){
				fields[i].fireEvent('click');
				return true;
			}
			if(
				obj['unit_type'] == 2 &&
				obj['gatekeeper_type'] == data['gatekeeper_type'] &&
				obj['gatekeeper_role'] == data['gatekeeper_role'] &&
				obj['gatekeeper_id'] == data['gatekeeper_id']
			){
				fields[i].fireEvent('click');
				return true;
			}
		}
		return false;
	},//end function





	//Обновление панели инструментов при клике на блок
	selectUnit: function(block){
		return true;
	},//end function




	//Удаление всех блоков
	workflowClear: function(){
		var fields = $$('.unit');
		fields.each(function(obj, i){
			jsPlumb.detachAllConnections(obj.id);
			jsPlumb.removeAllEndpoints(obj.id);
			obj.destroy();
		});
	},//end function




	//Обнуление маршрута
	workflowNew: function(){
		App.message(
			'Подтвердите действие',
			'При создании нового маршрута, текущий маршрут будет удален.<br>Вы действительно хотите продолжить?',
			'CONFIRM',
			function(){
				App.pages[PAGE_NAME].workflowNewOperation();
				App.pages[PAGE_NAME].changeStatus(true);
			}
		);
	},//end function



	//Обнуление маршрута
	workflowNewOperation: function(){
		this.workflowClear();
		var defaults = [
			{
				'unit_type': 1,'uid': '','gatekeeper_type': 0,'gatekeeper_role': 0,'gatekeeper_id': 0,
				'x': 50,'y': 50,'step_yes': 0,'step_no': 0,
				'text': 'НАЧАЛО'
			},
			{
				'unit_type': 3,'uid': '','gatekeeper_type': 0,'gatekeeper_role': 0,'gatekeeper_id': 0,
				'x': 400,'y': 300,'step_yes': 0,'step_no': 0,
				'text': 'ИСПОЛНЕНО'
			},
			{
				'unit_type': 4,'uid': '','gatekeeper_type': 0,'gatekeeper_role': 0,'gatekeeper_id': 0,
				'x': 50,'y': 300,'step_yes': 0,'step_no': 0,
				'text': 'ОТКЛОНЕНО'
			}
		];
		for(var i=0;i<defaults.length;i++){
			defaults[i]['uid'] = this.getUnitUID(defaults[i]);
			this.unitAdd(defaults[i]);
		}
	},//end function



	//Перезагрузка маршрута
	workflowReload: function(){
		return this.checkChange(this.stepsLoad.bind(this));
	},//end function



	//Подсчитывает количество блоков определенного типа
	workflowUnitsCount: function(unit_type){
		var counts = {
			1: 0,
			2: 0,
			3: 0,
			4: 0
		};
		var fields = $$('.unit'), data;
		for(var i=0; i< fields.length; i++){
			data = fields[i].retrieve('org_data');
			if(typeOf(data)!='object') continue;
			counts[data['unit_type']]++;
		}
		if(!unit_type) return counts;
		switch(unit_type){
			case 1: return counts[1];
			case 2: return counts[2];
			case 3: return counts[3];
			case 4: return counts[4];
		}
		return -1;
	},//end function



	//Проверка корректности маршрута
	workflowCheck: function(){

		var check = {
			'result': false,
			'desc':''
		};

		var beginBlockCount = 0;
		var endBlockSuccessCount = 0;
		var endBlockFailsCount = 0;
		var beginBlockUID = '';
		var blocks = {};

		var fields = $$('.unit');
		for(var i=0; i< fields.length; i++){
			data = fields[i].retrieve('org_data');
			if(typeOf(data)!='object') continue;
			switch(parseInt(data['unit_type'])){
				case 1: beginBlockUID = data['uid']; beginBlockCount++; break;
				case 3: endBlockSuccessCount++; break;
				case 4: endBlockFailsCount++; break;
			}
			blocks[data['uid']] = {
				'data': data,
				'unit': fields[i],
				'checked': false
			};
		}

		if(!beginBlockUID || beginBlockCount != 1){
			check['desc'] = (beginBlockCount == 0 ? 'Не найден блок начала маршрута' : 'Найдено несколько блоков начала маршрута');
			return check;
		}
		if(endBlockSuccessCount != 1){
			check['desc'] = (endBlockSuccessCount == 0 ? 'Не найден блок успешного завершения маршрута' : 'Найдено несколько блоков успешного завершения маршрута');
			return check;
		}
		if(endBlockFailsCount != 1){
			check['desc'] = (endBlockFailsCount == 0 ? 'Не найден блок неудачного завершения маршрута' : 'Найдено несколько блоков неудачного завершения маршрута');
			return check;
		}

		console.log(blocks);

		var currentBlock = blocks[beginBlockUID], nextBlockYes, nextBlockNo, unit_type;
		var checkedBlocks = {};
		while(true){
			if(checkedBlocks[currentBlock['data']['uid']]){
				currentBlock['unit'].fireEvent('click');
				check['desc'] = 'Схема маршрута может привести к возникновению замкнутого бесконечного цикла';
				return check;
			}
			unit_type = parseInt(currentBlock['data']['unit_type']);
			gatekeeper_role = parseInt(currentBlock['data']['gatekeeper_role']);
			if(unit_type == 3) break;
			if(unit_type == 4){
				check['desc'] = 'Имеющаяся схема не позволяет достигнуть блока успешного завершения маршрута';
				return check;
			}
			if(unit_type == 2){
				nextBlockNo = currentBlock['data']['step_no'];
				if(gatekeeper_role > 2  && (nextBlockNo == 0 || typeOf(blocks[nextBlockNo])!='object')){
					currentBlock['unit'].fireEvent('click');
					check['desc'] = 'Маршрут не закончен, неопредено дальнейшее движение по маршруту при отклонении заявки на выделенном блоке';
					return check;
				}
				if(gatekeeper_role > 0 && gatekeeper_role < 3  && !checkedBlocks[nextBlockNo] && parseInt(blocks[nextBlockNo]['data']['unit_type'])==2){
					currentBlock['unit'].fireEvent('click');
					check['desc'] = 'При отклонении заявки на выделенном блоке присутствует некорректное движение по маршруту';
					return check;
				}
			}
			nextBlockYes = currentBlock['data']['step_yes'];
			if(nextBlockYes == 0 || typeOf(blocks[nextBlockYes])!='object'){
				currentBlock['unit'].fireEvent('click');
				check['desc'] = 'Маршрут не имеет логического завершения, движение по маршруту прерывается на выделенном блоке';
				return check;
			}
			checkedBlocks[currentBlock['data']['uid']] = true;
			currentBlock = blocks[nextBlockYes];
		}

		check['result']=true;
		return check;
	},//end function




	//Сохранение маршрута
	workflowSave: function(){
		var check = this.workflowCheck();
		if(typeOf(check)!='object') return;
		if(!check['result']){
			App.message(
				'Ошибка маршрута',
				check['desc'],
				'ERROR'
			);
			return;
		}

		var data;
		var units=[];
		var fields = $$('.unit');
		for(var i=0; i< fields.length; i++){
			data = fields[i].retrieve('org_data');
			if(typeOf(data)!='object') continue;
			units.push([
				data['uid'],
				data['unit_type'],
				data['gatekeeper_type'],
				data['gatekeeper_role'],
				data['gatekeeper_id'],
				data['step_yes'],
				data['step_no'],
				String(fields[i].style.left).toInt(),
				String(fields[i].style.top).toInt()
			]);
		}
		new axRequest({
			url : '/admin/ajax/routes',
			data: {
				'action': 'route.steps.save',
				'route_id': this.objects['route']['route_id'],
				'u': units
			},
			silent: false,
			waiter: true,
			display: 'hint',
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
					App.pages[PAGE_NAME].changeStatus(false);
				}
			}
		}).request();
		return true;
	},//end function







	/*******************************************************************
	 * Функции WorkFlow: выбор и добавление элементов
	 ******************************************************************/


	//
	uselectorComplete: function(){
		this.uselectorClose();
		this.uselectorCompleteOperation();
	},//end function



	//Добавление блока
	uselectorCompleteOperation: function(){

		var xy = $('workflow').getScroll();
		var route_id		= this.objects['route']['route_id'];
		var unit_type		= parseInt($('unit_type').getValue());
		var gatekeeper_type	= (unit_type == 2 ? $('gatekeeper_type').getValue() : 0);
		var gatekeeper_role	= (unit_type == 2 ? $('gatekeeper_role').getValue() : 0);
		var gatekeeper_id	= 0;
		var text = '';

		if(unit_type != 2){
			var counts = this.workflowUnitsCount();
			var ut = [1,3,4];
			for(var i=0; i<ut.length;i++){
				if(unit_type == ut[i] && counts[ut[i]] > 0){
					return App.message('Ошибка','Вы не можете добавить еще один элемент выбранного типа','ERROR');
				}
			}
		}

		switch(String(unit_type)){
			case '1': text = 'НАЧАЛО'; break;
			case '3': text = 'ИСПОЛНЕНО'; break;
			case '4': text = 'ОТКЛОНЕНО'; break;
			case '2':
				text = '';
				switch(String(gatekeeper_type)){
					case '1': 
						if(typeOf(this.objects['gatekeeper_employer'])!='object') return App.message('Ошибка','Вы не выбрали сотрудника','ERROR'); 
						text += 'Сотрудник:<br/>' + this.objects['gatekeeper_employer']['search_name'];
						gatekeeper_id = this.objects['gatekeeper_employer']['employer_id']; 
					break;
					case '2': text += 'Руководитель заявителя'; break;
					case '3': text += 'Руководитель организации'; break;
					case '4': text += 'Владелец ресурса'; break;
					case '5': 
						text += 'Группа сотрудников:<br/>' + select_getText('gatekeeper_group');
						gatekeeper_id = select_getValue('gatekeeper_group'); 
					break;
					case '6': 
						if(typeOf(this.objects['gatekeeper_post'])!='object' || this.objects['gatekeeper_post']['post_uid']==0) return App.message('Ошибка','Вы не выбрали должность','ERROR'); 
						text += 'Занимающий должность:<br/>'+ this.objects['gatekeeper_post']['company_name'] + '<br/>' + this.objects['gatekeeper_post']['post_name']; 
						gatekeeper_id = this.objects['gatekeeper_post']['post_uid']; 
					break;
					case '7': text += 'Группа исполнителей'; break;
				}
				text += '<br/><br/><b>';
				switch(gatekeeper_role){
					case '1': text += 'Согласование'; break;
					case '2': text += 'Утверждение'; break;
					case '3': text += 'Исполнение'; break;
					case '4': text += 'Уведомление'; break;
				}
				text += '</b>';
			break;
		}

		if(this.unitExists({
			'unit_type': unit_type,
			'gatekeeper_type': gatekeeper_type,
			'gatekeeper_role': gatekeeper_role,
			'gatekeeper_id' : gatekeeper_id
		})){
			return App.message('Ошибка','Добавляемый элемент уже присутствует в схеме маршрута.<br>Вы не можете добавить несколько идентичных элементов в один маршрут','ERROR'); 
			return;
		}

		var unitData = {
			'uid': '',
			'unit_type': String(unit_type),
			'gatekeeper_type': String(gatekeeper_type),
			'gatekeeper_role': String(gatekeeper_role),
			'gatekeeper_id': String(gatekeeper_id),
			'x': xy.x+100,
			'y': xy.y+100,
			'step_yes': 0,
			'step_no': 0,
			'text': text
		};
		unitData['uid'] = this.getUnitUID(unitData);
		this.unitAdd(unitData);

		jsPlumb.repaintEverything();
		this.changeStatus(true);
	},//end function



	//Генерация UID элемента маршрута
	getUnitUID: function(data){
		var route_id = data['route_id'] || this.objects['route']['route_id'];
		return 	'1'+ //1
				strPad(String(route_id), 9, '0', 'STR_PAD_LEFT') + //9
				strPad(String(data['unit_type']), 2, '0', 'STR_PAD_LEFT') + //2
				strPad(String(data['gatekeeper_type']), 2, '0', 'STR_PAD_LEFT') + //2
				strPad(String(data['gatekeeper_role']), 2, '0', 'STR_PAD_LEFT') + //2
				'0000' + // 4 reserved
				strPad(String(data['gatekeeper_id']), 20, '0', 'STR_PAD_LEFT'); //20
	},//end function



	//Выбор добавляемого блока: открыть окно
	uselectorOpen: function(){
		$('bigblock_wrapper').hide();
		var counts = this.workflowUnitsCount();
		var allowedUnitTypes = [];
		if(!counts[1]) allowedUnitTypes.push(['1','Начало маршрута']);
		allowedUnitTypes.push(['2','Гейткипер']);
		if(!counts[3]) allowedUnitTypes.push(['3','Завершение маршрута: исполнено']);
		if(!counts[4]) allowedUnitTypes.push(['4','Завершение маршрута: отклонено']);
		select_add({
			'list': 'unit_type',
			'options': allowedUnitTypes,
			'default': '2',
			'clear': true
		});
		this.uselectorGatekeeperPostCancel();
		this.uselectorGatekeeperEmployerCancel();
		this.uselectorSelectUnitType(2);
		this.uselectorSelectGatekeeperType();
		$('change_gatekeeper_post_cancel_button').hide();
		$('unit_selector').show();
	},//end function



	//Выбор добавляемого блока: закрыть окно
	uselectorClose: function(){
		$('unit_selector').hide();
		$('bigblock_wrapper').show();
	},//end function



	//Выбор типа блока
	uselectorSelectUnitType: function(unit_type){
		if(!unit_type || typeOf(unit_type)!='number'){
			unit_type = $('unit_type').getValue();
		}else{
			$('unit_type').setValue(unit_type);
		}
		['1','2','3','4'].each(function(item){if(unit_type==item){$('unit_type_'+item).show();}else{$('unit_type_'+item).hide();}});
	},//end function



	//Выбор типа гейткипера
	uselectorSelectGatekeeperType: function(gatekeeper_type){
		if(!gatekeeper_type || typeOf(gatekeeper_type)!='number'){
			gatekeeper_type = $('gatekeeper_type').getValue();
		}else{
			$('gatekeeper_type').setValue(gatekeeper_type);
		}
		['1','2','3','4','5','6','7'].each(function(item){if(gatekeeper_type==item){$('gatekeeper_type_'+item).show();}else{$('gatekeeper_type_'+item).hide();}});
		
	},//end function



	//Выбор должности
	uselectorGatekeeperPostOpen: function(){
		this.objects['post_selected'] = $unlink(this.objects['gatekeeper_post']);
		this.objects['post_callback'] = this.uselectorGatekeeperPostComplete.bind(this);
		this.postOpen();
	},//end function


	//Отмена выбора должности
	uselectorGatekeeperPostCancel: function(){
		$('gatekeeper_post_area').hide();
		$('gatekeeper_post_none').show();
		$('change_gatekeeper_post_cancel_button').hide();
		this.objects['gatekeeper_post'] = null;
	},//end function



	//Должность выбрана
	uselectorGatekeeperPostComplete: function(){
		if(typeOf(this.objects['post_selected'])=='object' && this.objects['post_selected']['post_uid']!=0){
			this.objects['gatekeeper_post'] = $unlink(this.objects['post_selected']);
			$('gatekeeper_post_none').hide();
			$('gatekeeper_post_area').show();
			$('change_gatekeeper_post_cancel_button').show();
			$('gatekeeper_company_name').setValue(this.objects['gatekeeper_post']['company_name']);
			$('gatekeeper_post_name').setValue(this.objects['gatekeeper_post']['post_name']);
		}
	},//end function



	//Выбор сотрудника
	uselectorGatekeeperEmployerOpen: function(){
		this.objects['gatekeeper_employer'] = null;
		this.objects['employer_callback'] = this.uselectorGatekeeperEmployerComplete.bind(this);
		this.employerOpen();
	},//end function



	//Отмена выбора сотрудника
	uselectorGatekeeperEmployerCancel: function(){
		$('gatekeeper_employer_area').hide();
		$('gatekeeper_employer_none').show();
		$('change_gatekeeper_employer_cancel_button').hide();
		this.objects['gatekeeper_employer'] = null;
	},//end function



	//Cотрудник выбран
	uselectorGatekeeperEmployerComplete: function(){
		if(typeOf(this.objects['employer_selected'])=='object'){
			this.objects['gatekeeper_employer'] = $unlink(this.objects['employer_selected']);
			$('gatekeeper_employer_none').hide();
			$('gatekeeper_employer_area').show();
			$('change_gatekeeper_employer_cancel_button').show();
			$('gatekeeper_employer_name').setValue(this.objects['gatekeeper_employer']['search_name']);
			$('gatekeeper_employer_id').setValue(this.objects['gatekeeper_employer']['employer_id']);
		}
	},//end function




	/*******************************************************************
	 * Функции выбор должности и организации
	 ******************************************************************/



	//Открытие окна выбора должности
	postOpen: function(){
		//$('bigblock_wrapper').hide();
		$('post_selector_complete_button').hide();
		$('post_selector').show();
		if(typeOf(this.objects['post_selected'])=='object'){
			select_set('post_selector_companies_select', this.objects['post_selected']['company_id']);
		}
		this.postChangeCompany();
	},//end function



	//Выбрана должность
	postComplete: function(){
		if(typeOf(this.objects['post_selected'])=='object'){
			if(this.objects['post_callback'] && this.objects['post_callback'] instanceof Function) this.objects['post_callback'](this.objects['post_selected']);
		}
		this.postClose();
	},//end function



	//Закрытие окна выбора должности
	postClose: function(){
		$('post_selector').hide();
		//$('bigblock_wrapper').show();
	},//end function



	//Фильтр списка должностей
	postFilter: function(){
		this.objects['orgchart'].filter($('posts_filter').value);
	},//end function



	//Изменить организацию
	postChangeCompany: function(){
		var company_id = select_getValue('post_selector_companies_select');
		if(!parseInt(company_id)) return;
		if(typeOf(this.objects['posts'][company_id])=='array'){
			this.setPosts(company_id, this.objects['posts'][company_id]);
			return;
		}
		new axRequest({
			url : '/admin/ajax/org',
			data:{
				'action':'org.structure.load',
				'company_id': company_id
			},
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].objects['posts'][data['company_id']] = data['org_data'];
					App.pages[PAGE_NAME].setPosts(data['company_id'], data['org_data']);
				}
			}
		}).request();
	},//end function



	//Применение списка должностей
	setPosts: function(company_id, data){
		if(typeOf(this.objects['companies'])=='array'){
			var company_name = this.objects['companies'].filterResult('full_name','company_id',company_id);
			$('post_selector_title').set('text','Выберите должность в организации '+company_name);
			this.objects['orgchart'].setData(company_name, data);
			var tof = typeOf(this.objects['post_selected']);
			var svalue = (tof=='object'&& this.objects['post_selected']['post_uid']!='0' ? this.objects['post_selected']['post_uid'] : null);
			this.objects['orgchart'].select('post_uid',svalue);
		}
		$('post_selector_complete_button').hide();
	},//end function



	//Выбор должности
	postSelect: function(el){
		if(!el || typeOf(el)!='element'){
			$('post_selector_complete_button').hide();
			this.objects['post_selected'] = null;
		}else{
			if(el.hasClass('noselect')){
				this.objects['post_selected'] = {
					'company_id': select_getValue('post_selector_companies_select'),
					'company_name': select_getText('post_selector_companies_select'),
					'post_uid': 0,
					'post_name': '-[Любая должность]-'
				};
			}else{
				this.objects['post_selected'] = {
					'company_id': el.retrieve('company_id'),
					'company_name': select_getText('post_selector_companies_select'),
					'post_uid': el.retrieve('post_uid'),
					'post_name': el.retrieve('full_name')
				};
			}
			$('post_selector_complete_button').show();
		}
	},//end function






	/*******************************************************************
	 * Функции выбор сотрудника
	 ******************************************************************/


	//Открытие окна выбора сотрудника
	employerOpen: function(){
		//$('bigblock_wrapper').hide();
		$('employer_selector_complete_button').hide();
		$('employer_selector').show();
		this.objects['employer_selected'] = null;
		$('employer_selector_none').hide();
		$('employer_selector_table').hide();
	},//end function



	//Выбран сотрудник
	employerComplete: function(){
		if(typeOf(this.objects['employer_selected'])=='object'){
			if(this.objects['employer_callback'] && this.objects['employer_callback'] instanceof Function) this.objects['employer_callback'](this.objects['employer_selected']);
		}
		this.employerClose();
	},//end function



	//Закрытие окна выбора сотрудника
	employerClose: function(){
		$('employer_selector').hide();
		//$('bigblock_wrapper').show();
	},//end function



	//Поиск сотрудников
	employerSearch: function(){
		var tobj = $('employer_selector_term');
		var term = String(tobj.value).trim();
		if(!term.length) return;

		if(this.objects['process_search']) return;
		this.objects['process_search'] = true;

		new axRequest({
			url : '/admin/ajax/employers',
			data:{
				'action':'employers.search',
				'search_name': term
			},
			silent: true,
			waiter: true,
			callback: function(success, status, data){
				var self = App.pages[PAGE_NAME];
				self.objects['process_search'] = false;
				self.objects['selected_employer_object'] = null;
				if(success){
					self['employer_selected'] = null;
					$('employer_selector_complete_button').hide();
					if(typeOf(data)=='object'&&typeOf(data['employers_search'])=='array'&&data['employers_search'].length>0){
						$('employer_selector_none').hide();
						$('employer_selector_table').show();
						self.employerSetData(data['employers_search']);
					}else{
						$('employer_selector_none').show();
						$('employer_selector_table').hide();
					}
				}
			}
		}).request();
	},//end function



	//Построение списка найденных сотрудников
	employerSetData: function(data){

		var list = $('employer_selector_list');
		var li, html, employer_name, phone, email;

		list.empty();

		//Построение списка
		for(var index=0; index<data.length; index++){
			employer_name = data[index]['search_name'];
			phone = String(data[index]['phone']).length > 0 ? data[index]['phone'] : null;
			email = String(data[index]['email']).length > 0 ? data[index]['email'] : null;
			posts = data[index]['posts'];

			html = '<div class="line"><a class="mailto" href="/admin/employers/info?employer_id='+data[index]['employer_id']+'" target="_blank">'+employer_name +'</a><br/>'+(phone || email ? '<p class="small">'+( phone ? 'Телефон: '+data[index]['phone'] : '')+(phone && email ? '<br/>':'')+(email ? 'Email: <a class="mailto" href="mailto:'+data[index]['email']+'">'+data[index]['email']+'</a>':'')+'</p>' : '')+'</div>';
			html+= '<div class="line"><p class="small">Идентификатор: '+data[index]['employer_id']+'</p></div>';
			html+= '<div class="line"><p class="small">Имя пользователя: '+data[index]['username']+'</p></div>';

			li = new Element('li').inject(list).set('html',html).addEvent('click',function(){
				 App.pages[PAGE_NAME].employerSelect(this);
			}).store('employer_data',data[index]);

		}//Построение списка

	},//end function



	//Выбор сотрудника
	employerSelect: function(element){
		if($(this.objects['selected_employer_object'])) $(this.objects['selected_employer_object']).removeClass('selected');
		var employer_data = element.retrieve('employer_data');
		if(!element.hasClass('selected')) element.addClass('selected');
		this.objects['employer_selected'] = employer_data;
		this.objects['selected_employer_object'] = element;
		$('employer_selector_complete_button').show();
	},//end function




	empty: null
}

//Инициализация workflow
App.pages[PAGE_NAME].workflowInit();
/*////////////////////////////////////////////////////////////////////*/
})();
