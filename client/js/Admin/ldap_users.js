;(function(){
var PAGE_NAME = 'ldap_users';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': ['table_users','table_employers'],
		'table_users': null,
		'table_employers': null,
		'validators': ['form_anket'],
		'form_anket': null,
		//
		'groups': null,
		'companies': null,
		'posts': {},
		'orgchart':null
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
		if(self.objects['orgchart'])self.objects['orgchart'].empty();
		for(var i in self.objects) self.objects[i] = null;
		self.objects = {};
	},//end function


	//Инициализация страницы
	start: function(data){

		$('user_tool_area').hide();

		//Вкладки
		this.objects['tabs'] = new jsTabPanel('tabs_area',{
			'onchange': null
		});

		//Создание календарей
		new Picker.Date($$('.calendar_input'), {
			timePicker: false,
			positionOffset: {x: 0, y: 2},
			pickerClass: 'calendar',
			useFadeInOut: false
		});

		//Проверка формы
		this.objects['form_anket'] = new jsValidator('import_form_area');
		this.objects['form_anket']
		.required('info_first_name').alpha('info_first_name')
		.required('info_last_name').alpha('info_last_name')
		.required('info_middle_name').alpha('info_middle_name')
		.date('info_birth_date').phone('info_phone').email('info_email');

		$('bigblock_expander').addEvent('click',this.fullscreen.bind(this));
		$('filter_button').addEvent('click',this.filter.bind(this));
		$('filter_search_name').addEvent('keydown',function(e){if(e.code==13) this.filter();}.bind(this));
		$('reload_button').addEvent('click',this.reloadUsers.bind(this));
		$('import_button').addEvent('click',this.importOpen.bind(this));
		$('import_cancel_button').addEvent('click',this.importCancel.bind(this));
		$('import_complete_button').addEvent('click',this.importComplete.bind(this));

		$('change_post_button').addEvent('click',this.selectorOpen.bind(this));
		$('change_post_cancel_button').addEvent('click',this.selectorCancel.bind(this));
		$('post_selector_cancel_button').addEvent('click',this.selectorClose.bind(this));
		$('post_selector_complete_button').addEvent('click',this.selectorComplete.bind(this));

		$('import_complete_cancel_button').addEvent('click',this.importCompleteCancel.bind(this));

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

		this.setData(data);
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
			this.objects['companies'] = data['companies'];
			select_add({
				'list': 'post_selector_companies_select',
				'key': 'company_id',
				'value': 'full_name',
				'options': data['companies'],
				'default': 0,
				'clear': true
			});
		}//Организации


		//Информационные ресурсы
		if(typeOf(data['users'])=='object'){
			this.usersDataSet(data['users']);
		}


		//Список сотрудников
		if(typeOf(data['employers_search'])=='array'){
			this.employersDataSet(data['employers_search']);
		}//Список сотрудников


		//Информация о созданном сотруднике
		if(typeOf(data['employer'])=='object'){
			this.objects['employer'] = data['employer'];
			var id;
			for(var key in data['employer']){
				id = 'info_'+key+'_complete';
				if(!$(id))continue;
				$(id).set('text',data['employer'][key]);
			}
			$('employer_profile_button').set('href','/admin/employers/info?employer_id='+this.objects['employer']['employer_id']);
			$('import_info_wrapper').hide();
			$('import_complete_wrapper').show();
		}//Информация о созданном сотруднике


	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/

	employersDataSet: function(data){
		if(!data.length){
			$('employers_table').hide();
			$('employers_none').show();
			$('tab_1').hide();
			return;
		}else{
			$('employers_none').hide();
			$('employers_table').show();
			$('tab_1').show();
		}

		$('tab_1').set('text','Похожие сотрудники ('+data.length+')')

		if(!this.objects['table_employers']){
			this.objects['table_employers'] = new jsTable('employers_table',{
				'dataBackground1':'#efefef',
				columns:[
					{
						width:'40px',
						sortable:false,
						caption: '-',
						styles:{'min-width':'40px'},
						dataStyle:{'text-align':'center'},
						dataSource:'employer_id',
						dataFunction:function(table, cell, text, data){
							new Element('img',{
								'src': INTERFACE_IMAGES+'/document_go.png',
								'styles':{
									'cursor':'pointer',
									'margin-left':'4px'
								},
								'events':{
									'click': function(e){
										App.Location.doPage('/admin/employers/info?employer_id='+text);
									}
								}
							}).inject(cell);
							return '';
						}
					},
					{
						caption: 'ID сотрудника',
						dataSource:'employer_id',
						width:60,
						dataStyle:{'text-align':'center'}
					},
					{
						caption: 'Имя пользователя',
						dataSource:'username',
						width:150,
						dataStyle:{'text-align':'left'}
					},
					{
						caption: 'Статус',
						dataSource:'status',
						width:90,
						sortable: false,
						dataStyle:{'text-align':'center'},
						dataFunction:function(table, cell, text, data){
							switch(text){
								case '0': return '<font color="red">Заблокирован</font>';
								case '1': return '<font color="green">Активен</font>';
							}
							return '-[?????]-';
						}
					},
					{
						caption: 'ФИО сотрудника',
						dataSource:'search_name',
						width:220,
						sortable: false,
						dataStyle:{'text-align':'left'},
						dataFunction:function(table, cell, text, data){
							var employer_name = text;
							var result =  
							'<a target="_blank" class="mailto" href="/admin/employers/info?employer_id='+data['employer_id']+'">'+employer_name+'</a>' + 
							'<br/><span class="small">Tel: '+ data['phone']+(data['email']!=''?'<br/>E-Mail: <a class="mailto" href="mailto:'+data['email']+'">'+data['email']+'</a></span>':'');
							return result;
						}
					},
					{
						caption: 'Дата рождения',
						dataSource:'birth_date',
						width:100,
						dataStyle:{'text-align':'center'}
					},
				]
			});
		}

		this.objects['table_employers'].setData(data);
	},


	usersDataSet: function(data){
		var data_array = [];
		for(var key in data){
			if(String(data[key]['active']) == '1') data_array.push(data[key]);
		}
		if(!data_array.length){
			$('users_table_wrapper').hide();
			$('filter_area').hide();
			$('users_none').show();
			$('bigblock_title').set('html','Импорт пользователей из ActiveDirectory: нет новых пользователей');
			return;
		}else{
			$('users_none').hide();
			$('filter_area').show();
			$('users_table_wrapper').show();
			$('bigblock_title').set('html','Импорт пользователей из ActiveDirectory: '+data_array.length+' новых');
		}

		if(!this.objects['table_users']){
			this.objects['table_users'] = new jsTable('users_table',{
				'dataBackground1':'#efefef',
				columns:[
					{
						width:150,
						caption: 'Имя пользователя',
						sortable:true,
						dataSource:'username',
						dataStyle:{'text-align':'left'}
					},
					{
						width:200,
						caption: 'ФИО пользователя',
						sortable:true,
						dataSource:'displayname',
						dataStyle:{'text-align':'left'}
					},
					{
						width:200,
						caption: 'Должность',
						sortable:true,
						dataSource:'title',
						dataStyle:{'text-align':'left'}
					},
					{
						width:150,
						caption: 'Организация',
						sortable:true,
						dataSource:'company',
						dataStyle:{'text-align':'left'}
					},
					{
						width:150,
						caption: 'Подразделение',
						sortable:true,
						dataSource:'department',
						dataStyle:{'text-align':'left'}
					},
					{
						width:160,
						caption: 'E-mail',
						sortable:true,
						dataSource:'mail',
						dataStyle:{'text-align':'left'}
					},
					{
						width:160,
						caption: 'Телефон',
						sortable:true,
						dataSource:'telephone',
						dataStyle:{'text-align':'left'}
					},
					{
						width:160,
						caption: 'Последний вход',
						sortable:true,
						dataSource:'lastlogon',
						dataStyle:{'text-align':'left'}
					}
				]
			});
			this.objects['table_users'].addEvent('click', this.selectUser.bind(this));
		}

		this.objects['table_users'].setData(data_array);
	},






	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/

	//Развертывание окна на весь экран
	fullscreen: function(normal_screen){
		var panel = $('page_ldap_users');
		var title = $('bigblock_title').getParent('.titlebar');
		if(title.hasClass('expanded')||normal_screen==true){
			title.removeClass('expanded').addClass('normal');
			panel.inject($('adminarea'));
		}else{
			title.removeClass('normal').addClass('expanded');
			panel.inject(document.body);
		}
	},//end function



	//Перезагрузка списка сотрудников
	reloadUsers: function(){
		new axRequest({
			url : '/admin/ajax/ldap',
			data:{
				'action':'ldap.users'
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



	//Фильтр таблицы сотрудников
	filter: function(){
		if(this.objects['table_users']){
			this.objects['table_users'].filter($('filter_search_name').getValue());
			this.objects['table_users'].clearSelected();
		}
		this.selectUser();
	},//end function


	//Выбор пользователя
	selectUser: function(){
		$('user_tool_area').hide();
		if(!this.objects['table_users'].selectedRows.length) return;
		$('user_tool_area').show();
	},//end function



	//Импорт сотрудника - открытие
	importOpen: function(){
		if(!this.objects['table_users'].selectedRows.length) return;
		var tr = this.objects['table_users'].selectedRows[0];
		if(typeOf(tr)!='element') return;
		var data = tr.retrieve('data');
		if(typeOf(data)!='object') return;
		$('info_birth_date').setValue(_TODAY);
		var id, value;
		for(var key in data){
			id = 'adinfo_'+key;
			if(!$(id))continue;
			$(id).setValue(data[key]);
			switch(key){
				case 'displayname':
					value = String(data[key]).split(' ');
					if(typeOf(value)=='array' && value.length){
						if(typeOf(value[0])=='string') $('info_last_name').setValue(value[0]);
						if(typeOf(value[1])=='string') $('info_first_name').setValue(value[1]);
						if(typeOf(value[2])=='string') $('info_middle_name').setValue(value[2]);
					}
				break;
				case 'telephone':
					$('info_phone').setValue(data[key]);
				break;
				case 'mail':
					$('info_email').setValue(data[key]);
				break;
			}
		}

		new axRequest({
			url : '/admin/ajax/ldap',
			data:{
				'action':'ldap.related',
				'first_name': $('info_first_name').getValue(),
				'last_name': $('info_last_name').getValue(),
				'middle_name': $('info_middle_name').getValue(),
				'email': $('info_email').getValue()
			},
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].importOpenComplete(data);
				}
			}
		}).request();

	},//end function



	//Открытие окна импорта сотрудника
	importOpenComplete: function(data){
		this.setData(data);
		this.selectorCancel();
		$('bigblock_wrapper').hide();
		$('import_info_wrapper').show();
	},//end function




	//Импорт сотрудника - закрытие
	importCancel: function(){
		$('import_info_wrapper').hide();
		$('bigblock_wrapper').show();
	},//end function



	//Открытие окна выбора должности
	selectorOpen: function(){
		$('import_info_wrapper').hide();
		$('post_selector_complete_button').hide();
		$('post_selector').show();
		if(typeOf(this.objects['post_selected'])=='object'){
			select_set('post_selector_companies_select', this.objects['post_selected']['company_id']);
		}
		this.postChangeCompany();
	},//end function


	//Закрытие окна выбора должности
	selectorClose: function(){
		$('post_selector').hide();
		$('import_info_wrapper').show();
	},//end function


	//Выбрана должность
	selectorComplete: function(){
		if(typeOf(this.objects['post_selected'])=='object'){
			$('selected_post_area').show();
			$('change_post_cancel_button').show();
			$('selected_company_name').setValue(this.objects['post_selected']['company_name']);
			$('selected_post_name').setValue(this.objects['post_selected']['post_name']);
		}
		this.selectorClose();
	},//end function


	//Отмена выбора должности
	selectorCancel: function(){
		$('selected_post_area').hide();
		$('change_post_cancel_button').hide();
		this.objects['post_selected'] = null;
	},//end function



	//Выбор должности
	postSelect: function(el){
		if(!el || typeOf(el)!='element' || el.hasClass('noselect')){
			$('post_selector_complete_button').hide();
			this.objects['post_selected'] = null;
		}else{
			this.objects['post_selected'] = {
				'company_id': el.retrieve('company_id'),
				'company_name': select_getText('post_selector_companies_select'),
				'post_uid': el.retrieve('post_uid'),
				'post_name': el.retrieve('full_name')
			};
			$('post_selector_complete_button').show();
		}
	},//end function


	//Фильтр списка должностей
	postFilter: function(){
		this.objects['orgchart'].filter($('posts_filter').value);
	},//end function


	//Изменить организацию
	postChangeCompany: function(){
		var company_id = select_getValue('post_selector_companies_select');
		if(parseInt(company_id)<1) return;
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
			this.objects['orgchart'].select('post_uid',(typeOf(this.objects['post_selected'])=='object' ? this.objects['post_selected']['post_uid'] : 0 ));
		}
		$('post_selector_complete_button').hide();
	},//end function



	//Импорт сотрудника
	importComplete: function(){
		if(!this.objects['form_anket'].validate()) return;
		this.importAction();
	},//end function



	//Импорт сотрудника - процесс
	importAction: function(anket_type){
		var anket = {
				'action':'ldap.import',
				'username':  $('adinfo_username').getValue(),
				'company_id': (typeOf(this.objects['post_selected'])=='object' ? this.objects['post_selected']['company_id'] : 0),
				'post_uid': (typeOf(this.objects['post_selected'])=='object' ? this.objects['post_selected']['post_uid'] : 0),
				'first_name': $('info_first_name').getValue(),
				'last_name': $('info_last_name').getValue(),
				'middle_name': $('info_middle_name').getValue(),
				'birth_date': $('info_birth_date').getValue(),
				'phone': $('info_phone').getValue(),
				'email': $('info_email').getValue(),
				'template_post': $('template_post').getValue()
		};
		new axRequest({
			url : '/admin/ajax/ldap',
			data: anket,
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
				}
			}
		}).request();
	},//end function


	//Закрытие окна успешного добавления сотрудника
	importCompleteCancel: function(){
		$('import_complete_wrapper').hide();
		$('import_info_wrapper').hide();
		$('bigblock_wrapper').show();
	},//end function


	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();