;(function(){
var PAGE_NAME = 'manager_protocol';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': ['table_protocol'],
		'table_protocol': null,
		//
		'types': {},
		'companies': null
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
		$('filter_employer_id').addEvent('keydown',function(e){if(e.code==13) this.filter();}.bind(this));
		$('filter_company_id').addEvent('change',this.filter.bind(this));
		$('filter_object_type').addEvent('change',this.filter.bind(this));
		$('filter_object_id').addEvent('keydown',function(e){if(e.code==13) this.filter();}.bind(this));
		$('filter_acl_name').addEvent('change',this.filter.bind(this));

		//Создание календарей
		new Picker.Date($$('.calendar_input'), {
			timePicker: false,
			positionOffset: {x: 0, y: 2},
			pickerClass: 'calendar',
			useFadeInOut: false
		});

		this.setData(data);

		var storage_data = App.localStorage.read('log_filter', null, true);
		if(storage_data){
			var filter = String(storage_data).fromQueryString();
			if(typeOf(filter)=='object'){
				var value;
				for(var key in filter){
					value = filter[key];
					switch(key){
						case 'company_id': $('filter_company_id').setValue(value); break;
						case 'object_type': $('filter_object_type').setValue(value); break;
						case 'object_id': $('filter_object_id').setValue(value); break;
						case 'acl_name': $('filter_acl_name').setValue(value); break;
						case 'date_from': $('filter_date_from').setValue(value); break;
						case 'date_to': $('filter_date_to').setValue(value); break;
						case 'employer_id': $('filter_employer_id').setValue(value); break;
					}
				}
			}
			this.filter();
		}else{
			$('filter_date_from').setValue(_TODAY);
			$('filter_date_to').setValue(_TODAY);
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


		//Типы объектов
		if(typeOf(data['aobjects'])=='array'){
			this.objects['aobjects'] = $unlink(data['aobjects']);
			data['aobjects'].unshift({'name':'all','namedesc':'-[Все ACL объекты]-'});
			data['aobjects'].sort(function(a,b){if(a['namedesc']>b['namedesc'])return 1;return -1;});
			select_add({
				'list': 'filter_acl_name',
				'key': 'name',
				'value': 'namedesc',
				'options': data['aobjects'],
				'default': 'all',
				'clear': true
			});
		}//Типы объектов


		//Типы объектов
		if(typeOf(data['types'])=='object'){
			this.objects['types'] = $unlink(data['types']);
			var t = [{'uid':'0','name':'-[Все типы объектов]-'}];
			for(var key in data['types']){
				t.push({
					'uid': key,
					'name':data['types'][key]['name']
				});
			}
			t.sort(function(a,b){if(a['name']>b['name'])return 1;return -1;});
			select_add({
				'list': 'filter_object_type',
				'key': 'uid',
				'value': 'name',
				'options': t,
				'default': '0',
				'clear': true
			});
		}//Типы объектов


		//Информация о сессии
		if(typeOf(data['session'])=='object'){
			var out = 
			'UID сессии: '+data['session']['session_uid']+'<br>'+
			'ID сотрудника: '+data['session']['employer_id']+'<br>'+
			'IP адрес: '+data['session']['ip_addr']+'<br>'+
			'IP реальный: '+data['session']['ip_real']+'<br>'+
			'Время входа: '+data['session']['login_time']+'<br>'+
			'Тип входа: '+data['session']['login_type']+'<br>';
			
			App.message('Информация о сессии',out,'INFO');
		}//Информация о сессии


		//Заявки
		if(typeOf(data['protocol'])=='array'){
			this.requestsDataSet(data['protocol']);
		}//Заявки


	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/


	requestsDataSet: function(data){
		if(!data.length){
			$('protocol_table_wrapper').hide();
			$('protocol_none').show();
			return;
		}else{
			$('protocol_none').hide();
			$('protocol_table_wrapper').show();
		}

		if(!this.objects['table_protocol']){
			this.objects['table_protocol'] = new jsTable('protocol_table',{
				'dataBackground1':'#efefef',
				columns:[
					{
						width:'40px',
						sortable:false,
						caption: 'ID',
						styles:{'min-width':'40px'},
						dataStyle:{'text-align':'center'},
						dataSource:'action_id',
						dataType: 'int'
					},
					{
						caption: 'Действие',
						sortable:true,
						dataSource:'action_name',
						width:150,
						dataStyle:{'text-align':'left'}
					},
					{
						width:'40px',
						sortable:false,
						caption: 'ID сессии',
						styles:{'min-width':'40px'},
						dataStyle:{'text-align':'center'},
						dataSource:'session_uid',
						dataType: 'int',
						dataFunction:function(table, cell, text, data){
							new Element('a',{
								'href':'#',
								'text':text,
								'events':{
									'click':function(){
										App.pages[PAGE_NAME].sessionInfo(text);
									}
								}
							}).inject(cell);
							return '';
						}
					},
					{
						width:120,
						sortable:true,
						caption: 'Время события',
						styles:{'min-width':'80px'},
						dataStyle:{'text-align':'center'},
						dataSource:'timestamp',
						dataType: 'date'
					},
					{
						caption: 'Организация',
						sortable:true,
						dataSource:'company_name',
						width:100,
						dataStyle:{'text-align':'left'},
					},
					{
						caption: 'ACL объект',
						dataSource:'acl_name',
						width:120,
						sortable: true,
						dataStyle:{'text-align':'left'}
					},
					{
						caption: 'Сотрудник',
						dataSource:'employer_name',
						width:120,
						sortable: true,
						dataStyle:{'text-align':'left'},
						dataFunction:function(table, cell, text, data){
							return '<a target="_blank" class="mailto" href="/admin/employers/info?employer_id='+data['employer_id']+'">'+data['employer_name']+'</a>';
						}
					},
					{
						caption: 'Тип объекта',
						dataSource:'primary_type',
						width:120,
						sortable: true,
						dataStyle:{'text-align':'left'},
						dataFunction:function(table, cell, text, data){
							return (text == '' ? '---' : typeOf(App.pages[PAGE_NAME].objects['types'][text])=='object' ? App.pages[PAGE_NAME].objects['types'][text]['name'] : text);
						}
					},
					{
						caption: 'ID объекта',
						dataSource:'primary_id',
						width:120,
						sortable: true,
						dataStyle:{'text-align':'left'}
					},
					{
						caption: 'Тип второго',
						dataSource:'secondary_type',
						width:120,
						sortable: true,
						dataStyle:{'text-align':'left'},
						dataFunction:function(table, cell, text, data){
							return (text == '' ? '---' : typeOf(App.pages[PAGE_NAME].objects['types'][text])=='object' ? App.pages[PAGE_NAME].objects['types'][text]['name'] : text);
						}
					},
					{
						caption: 'ID второго',
						dataSource:'secondary_id',
						width:120,
						sortable: true,
						dataStyle:{'text-align':'left'}
					},
					{
						caption: 'Описание',
						dataSource:'description',
						width:200,
						sortable: true,
						dataStyle:{'text-align':'left'}
					}
				]
			});
		}

		this.objects['table_protocol'].setData(data);
	},






	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/

	//Развертывание окна на весь экран
	fullscreen: function(normal_screen){
		var panel = $('page_manager_protocol');
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
			'employer_id': $('filter_employer_id').getValue(),
			'company_id': $('filter_company_id').getValue(),
			'object_type': $('filter_object_type').getValue(),
			'object_id': $('filter_object_id').getValue(),
			'acl_name': $('filter_acl_name').getValue(),
			'date_from': $('filter_date_from').getValue(),
			'date_to': $('filter_date_to').getValue()
		};
		App.localStorage.write('log_filter', Object.toQueryString(filter), true);
		filter['action'] = 'protocol.search';
		new axRequest({
			url : '/admin/ajax/protocol',
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



	//Информация о сессии
	sessionInfo: function(session_id){
		new axRequest({
			url : '/admin/ajax/protocol',
			data:  {
				'action': 'session.info',
				'session_id': session_id
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




	//Сброс фильтра
	filterClear: function(){
		$('filter_employer_id').setValue('');
		$('filter_company_id').setValue('all');
		$('filter_object_type').setValue('0');
		$('filter_object_id').setValue('');
		$('filter_acl_name').setValue('all');
		$('filter_date_from').setValue(_TODAY);
		$('filter_date_to').setValue(_TODAY);
		this.filter();
	},//end function


	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();