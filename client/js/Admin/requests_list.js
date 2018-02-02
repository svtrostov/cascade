;(function(){
var PAGE_NAME = 'requests_list';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': ['table_requests'],
		'table_requests': null,
		//
		'requests': null,
		'companies': null,
		'routes': null,
		'routes_assoc': {}
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

		$('bigblock_expander').addEvent('click',this.fullscreen.bind(this));
		$('filter_button').addEvent('click',this.filter.bind(this));
		$('filter_clear_button').addEvent('click',this.filterClear.bind(this));
		$('filter_search_term').addEvent('keydown',function(e){if(e.code==13) this.filter();}.bind(this));
		$('filter_request_status').addEvent('change',this.filter.bind(this));
		$('filter_request_type').addEvent('change',this.filter.bind(this));
		$('filter_company_id').addEvent('change',this.filter.bind(this));
		$('filter_iresource_id').addEvent('change',this.filter.bind(this));
		$('filter_route_id').addEvent('change',this.filter.bind(this));
		$('filter_period').addEvent('change',this.filter.bind(this));

		this.setData(data);

		var storage_data = App.localStorage.read('arl_filter', null, true);
		if(storage_data){
			var filter = String(storage_data).fromQueryString();
			if(typeOf(filter)=='object'){
				var value;
				for(var key in filter){
					value = filter[key];
					switch(key){
						case 'type': $('filter_request_type').setValue(value); break;
						case 'status': $('filter_request_status').setValue(value); break;
						case 'company_id': $('filter_company_id').setValue(value); break;
						case 'iresource_id': $('filter_iresource_id').setValue(value); break;
						case 'route_id': $('filter_route_id').setValue(value); break;
						case 'period': $('filter_period').setValue(value); break;
						case 'search_term': $('filter_search_term').setValue(decodeURIComponent(value)); break;
						case 'term_type': $('filter_search_term_type').setValue(value); break;
					}
				}
			}
			this.filter();
		}
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
			data['companies'].sort(function(a,b){if(a['full_name']>b['full_name'])return 1;return -1;});
			this.objects['companies'] = $unlink(data['companies']);
			data['companies'].unshift({'company_id':'all','full_name':'-[Все организации]-'});
			select_add({
				'list': 'filter_company_id',
				'key': 'company_id',
				'value': 'full_name',
				'options': data['companies'],
				'default': 'all',
				'clear': true
			});
		}//Организации


		//Маршруты
		if(typeOf(data['routes'])=='array'){
			data['routes'].sort(function(a,b){if(a['full_name']>b['full_name'])return 1;return -1;});
			this.objects['routes'] = $unlink(data['routes']);
			for(var i=0;i<data['routes'].length;i++){
				this.objects['routes_assoc'][data['routes'][i]['route_id']] = data['routes'][i]['full_name'];
			}
			data['routes'].unshift({'route_id':'all','full_name':'-[Все маршруты]-'});
			select_add({
				'list': 'filter_route_id',
				'key': 'route_id',
				'value': 'full_name',
				'options': data['routes'],
				'default': 'all',
				'clear': true
			});
		}//Маршруты


		//Информационные ресурсы
		if(typeOf(data['iresources'])=='array'){
			data['iresources'].sort(function(a,b){if(a['full_name']>b['full_name'])return 1;return -1;});
			this.objects['iresources'] = $unlink(data['iresources']);
			data['iresources'].unshift({'iresource_id':'all','full_name':'-[Все информационные ресурсы]-'});
			select_add({
				'list': 'filter_iresource_id',
				'key': 'iresource_id',
				'value': 'full_name',
				'options': data['iresources'],
				'default': 'all',
				'clear': true
			});
		}//Информационные ресурсы



		//Заявки
		if(typeOf(data['requests'])=='array'){
			this.requestsDataSet(data['requests']);
		}//Заявки


	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/


	requestsDataSet: function(data){
		if(!data.length){
			$('requests_table_wrapper').hide();
			$('requests_none').show();
			return;
		}else{
			$('requests_none').hide();
			$('requests_table_wrapper').show();
		}

		if(!this.objects['table_requests']){
			this.objects['table_requests'] = new jsTable('requests_table',{
				'dataBackground1':'#efefef',
				columns:[
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
										App.Location.doPage('/admin/requests/info?request_id='+data['request_id']+'&iresource_id='+data['iresource_id']);
									}
								}
							}).inject(cell);
							return '';
						}
					},
					{
						caption: 'ID заявки',
						sortable:true,
						dataSource:'request_id',
						width:50,
						dataStyle:{'text-align':'center'},
						dataType: 'int'
					},
					{
						caption: 'Информационный ресурс',
						sortable:true,
						dataSource:'iresource_id',
						width:50,
						dataStyle:{'text-align':'left'},
						dataFunction:function(table, cell, text, data){
							return '<a target="_blank" class="mailto" href="/admin/iresources/info?iresource_id='+data['iresource_id']+'">'+data['iresource_name']+'</a>';
						}
					},
					{
						width:80,
						sortable:true,
						caption: 'Дата создания',
						styles:{'min-width':'80px'},
						dataStyle:{'text-align':'center'},
						dataSource:'create_date',
						dataType: 'date'
					},
					{
						caption: 'Тип заявки',
						dataSource:'request_type',
						width:90,
						sortable: true,
						dataStyle:{'text-align':'center'},
						dataFunction:function(table, cell, text, data){
							switch(String(text)){
								case '3': return '<font color="red">Запрос блокировки</font>';
								case '2': return '<font color="green">Запрос доступа</font>';
							}
							return '-[?????]-';
						}
					},
					{
						caption: 'Маршрут согласования',
						dataSource:'route_id',
						width:120,
						sortable: true,
						dataStyle:{'text-align':'left'},
						dataFunction:function(table, cell, text, data){
							if(App.pages[PAGE_NAME].objects['routes_assoc'][text]){
								return '<a target="_blank" class="mailto" href="/admin/routes/info?route_id='+data['route_id']+'">'+App.pages[PAGE_NAME].objects['routes_assoc'][text]+'</a>';
							}
							return '-[? ID='+text+']-';
						}
					},
					{
						caption: 'Статус заявки',
						dataSource:'route_status',
						width:120,
						sortable: true,
						dataStyle:{'text-align':'left'},
						dataFunction:function(table, cell, text, data){
							var out='';
							switch(String(text)){
								case '0': out= '<font color="red"><b>Отменена</b></font>'; break;
								case '1': out= '<font color="black"><b>На согласовании</b></font>'; break;
								case '2': out= '<font color="blue"><b>Приостановлена</b></font>'; break;
								case '100': out= '<font color="green"><b>Исполнена</b></font>'; break;
								default: out='<font color="red"><b>-[??? ID:'+text+']-</b></font>'; break;
							}
							return out+'<br>'+data['route_status_desc'];
						}
					},
					{
						caption: 'Куратор',
						dataSource:'curator_name',
						width:120,
						sortable: true,
						dataStyle:{'text-align':'left'},
						dataFunction:function(table, cell, text, data){
							return '<a target="_blank" class="mailto" href="/admin/employers/info?employer_id='+data['curator_id']+'">'+data['curator_name']+'</a>';
						}
					},
					{
						caption: 'Заявитель',
						dataSource:'employer_name',
						width:120,
						sortable: true,
						dataStyle:{'text-align':'left'},
						dataFunction:function(table, cell, text, data){
							var result =  
							'<a target="_blank" class="mailto" href="/admin/employers/info?employer_id='+data['employer_id']+'">'+data['employer_name']+'</a>' + 
							'<br/><span class="small">Tel: '+ data['phone']+'<br/>E-Mail: '+data['email']+'</span>';
							return result;
						}
					},
					{
						caption: 'Должность заявителя',
						dataSource:'post_name',
						width:200,
						dataStyle:{'text-align':'left'},
						dataFunction:function(table, cell, text, data){
							return '<b>'+data['company_name']+'</b><br>'+data['post_name'];
						}
					}
				]
			});
		}

		this.objects['table_requests'].setData(data);
	},






	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/

	//Развертывание окна на весь экран
	fullscreen: function(normal_screen){
		var panel = $('page_requests_list');
		var title = $('bigblock_title').getParent('.titlebar');
		if(title.hasClass('expanded')||normal_screen==true){
			title.removeClass('expanded').addClass('normal');
			panel.inject($('adminarea'));
		}else{
			title.removeClass('normal').addClass('expanded');
			panel.inject(document.body);
		}
	},//end function



	//Фильтрация данных
	filter: function(){
		var filter = {
			'search_term': $('filter_search_term').getValue(),
			'term_type': $('filter_search_term_type').getValue(),
			'status': $('filter_request_status').getValue(),
			'type': $('filter_request_type').getValue(),
			'company_id': $('filter_company_id').getValue(),
			'iresource_id': $('filter_iresource_id').getValue(),
			'route_id': $('filter_route_id').getValue(),
			'period': $('filter_period').getValue()
		};
		App.localStorage.write('arl_filter', Object.toQueryString(filter), true);
		filter['action'] = 'requests.search';
		new axRequest({
			url : '/admin/ajax/requests',
			data: filter,
			silent: true,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
				}
			}
		}).request();
	},//end function



	//Сброс фильтра
	filterClear: function(){
		$('filter_search_term').setValue('');
		$('filter_search_term_type').setValue('employer');
		$('filter_request_status').setValue('1');
		$('filter_request_type').setValue('all');
		$('filter_company_id').setValue('all');
		$('filter_iresource_id').setValue('all');
		$('filter_route_id').setValue('all');
		$('filter_period').setValue('1');
		this.filter();
	},//end function


	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();