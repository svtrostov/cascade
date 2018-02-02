;(function(){
var PAGE_NAME = 'iresources_info';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': ['table_companies','table_all_companies','table_areas','table_items'],
		'validators': ['form_anket','form_add','form_edit','form_section_add','form_section_edit'],
		'form_anket': null,
		'form_add': null,
		'form_section_add':null,
		'form_section_edit':null,
		'table_companies':null,
		'table_all_companies':null,
		'table_areas':null,
		'table_items':null,
		//
		'groups': null,
		'igroups': null,
		'companies': null,
		'post_selected': null,
		'posts': {},
		'companies':null,
		'companies_assoc':{},
		'orgchart':null,
		'iresource':null,
		'iresources':null,
		'post_splitter':null,
		'post_selected':null,
		'anket': null,
		'ir_types':null,
		'ir_types_assoc':{},
		'selected_irole':null,
		'selected_section':null,
		'ir_objects': {}
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
		for(var i in self.objects) self.objects[i] = null;
		self.objects = {};
	},//end function


	//Инициализация страницы
	start: function(data){

		$('bigblock_expander').addEvent('click',this.fullscreen.bind(this));

		this.objects['tabs'] = new jsTabPanel('tabs_area',{
			'onchange': this.changeTab.bind(this)
		});

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
		$('change_post_button').addEvent('click',this.selectorOpen.bind(this));
		$('change_post_cancel_button').addEvent('click',this.selectorCancel.bind(this));
		$('post_selector_cancel_button').addEvent('click',this.selectorClose.bind(this));
		$('post_selector_complete_button').addEvent('click',this.selectorComplete.bind(this));
		$('iresource_info_save_button').addEvent('click',this.anketSave.bind(this));

		//Проверка формы
		this.objects['form_anket'] = new jsValidator($('anket_form'));
		this.objects['form_anket'].required('info_full_name').required('info_short_name');


		//Организации
		this.objects['companie_splitter'] = set_splitter_h({
			'left'		: $('companies_area'),
			'right'		: $('all_companies_area'),
			'splitter'	: $('companies_splitter_handle'),
			'handle'	: $('companies_splitter'),
			'parent'	: $('tabs_area')
		});

		var settings = {
			'dataBackground1':'#efefef',
			'class': 'jsTableLight',
			columns: [
				{
					width:'50px',
					sortable:true,
					caption: 'ID',
					styles:{'min-width':'50px'},
					dataStyle:{'text-align':'center'},
					dataSource:'company_id',
					dataType: 'int'
				},
				{
					width:'auto',
					sortable:true,
					caption: 'Организация',
					styles:{'min-width':'160px'},
					dataStyle:{'text-align':'left'},
					dataSource:'full_name'
				}
			],
			selectType:2
		};

		this.objects['table_companies'] = new jsTable('companies_area_table', settings);
		this.objects['table_all_companies'] = new jsTable('all_companies_area_table', settings);
		$('button_company_include').addEvent('click',this.companyInclude.bind(this));
		$('button_company_exclude').addEvent('click',this.companyExclude.bind(this));



		//Объекты доступа
		this.objects['objects_splitter'] = set_splitter_h({
			'left'		: $('sections_area'),
			'right'		: $('objects_area'),
			'splitter'	: $('objects_splitter'),
			'parent'	: $('tabs_area')
		});

		this.objects['table_areas'] = new jsTable('sections_table_area', {
			'dataBackground1':'#efefef',
			'class': 'jsTableLight',
			columns: [
				{
					width:'50px',
					sortable:true,
					caption: 'ID',
					styles:{'min-width':'50px'},
					dataStyle:{'text-align':'center'},
					dataSource:'irole_id',
					dataType: 'int'
				},
				{
					width:'auto',
					sortable:true,
					caption: 'Раздел ресурса',
					styles:{'min-width':'130px'},
					dataStyle:{'text-align':'left'},
					dataSource:'full_name'
				}
			],
			selectType:1
		});
		this.objects['table_areas'].addEvent('click', this.selectSection.bind(this));


		this.objects['table_items'] = new jsTable('objects_table_area', {
			'dataBackground1':'#efefef',
			'class': 'jsTableLight',
			sectionCollapsible:true,
			columns: [
				{
					width:'50px',
					caption: 'ID',
					styles:{'min-width':'50px'},
					dataStyle:{'text-align':'center'},
					dataSource:'irole_id',
					dataType: 'int'
				},
				{
					caption: 'Статус',
					dataSource:'is_lock',
					width:90,
					sortable: true,
					dataStyle:{'text-align':'center'},
					dataFunction:function(table, cell, text, data){
						switch(text){
							case '1': return '<font color="red">Блокирован</font>';
							case '0': return '<font color="green">Активен</font>';
						}
						return '-[?????]-';
					}
				},
				{
					width:'auto',
					caption: 'Объект доступа',
					dataSource:'full_name'
				},
				{
					width:'auto',
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
					styles:{'min-width':'80px'},
					dataStyle:{'text-align':'center'},
					dataFunction:function(table, cell, text, data){
						text = parseInt(text);
						if(text<3) return text+' (Низкая)';
						if(text<6) return text+' (Средняя)';
						if(text<8) return text+' (Высокая)';
						return text+' (Критично)';
					}
				},
				{
					caption: 'Типы доступа',
					sortable:false,
					width:'140px',
					dataSource:'ir_types',
					styles:{'min-width':'140px'},
					dataFunction:function(table, cell, text, data){
						if(typeOf(text)!='array'||!text.length) return '----';
						var result=[];
						for(var i=0; i<text.length;i++){
							if(App.pages[PAGE_NAME].objects['ir_types_assoc'][text[i]]){
								result.push(App.pages[PAGE_NAME].objects['ir_types_assoc'][text[i]]);
							}
						}
						return (result.length>0 ? result.join(', ') : '----');
					}
				}
			],
			selectType:1
		});
		this.objects['table_items'].addEvent('click', this.selectIRole.bind(this));
		$('objects_filter').addEvent('keydown',function(e){if(e.code==13) App.pages[PAGE_NAME].filterIRoles();});

		$('sections_tool_edit').hide();
		$('objects_tool_edit').hide();

		$('object_reload_button').addEvent('click', this.reloadIRoles.bind(this));
		$('object_del_button').addEvent('click', this.deleteIRole.bind(this));

		$('object_add_button').addEvent('click', this.addIRoleOpen.bind(this));
		$('object_add_cancel_button').addEvent('click', this.addIRoleClose.bind(this));
		$('object_add_complete_button').addEvent('click', this.addIRoleComplete.bind(this));
		this.objects['form_add'] = new jsValidator($('object_add_wrapper'));
		this.objects['form_add'].required('add_full_name').required('add_weight').numeric('add_weight');

		$('object_edit_button').addEvent('click', this.editIRoleOpen.bind(this));
		$('object_edit_cancel_button').addEvent('click', this.editIRoleClose.bind(this));
		$('object_edit_complete_button').addEvent('click', this.editIRoleComplete.bind(this));
		this.objects['form_edit'] = new jsValidator($('object_edit_wrapper'));
		this.objects['form_edit'].required('edit_full_name').required('edit_weight').numeric('edit_weight');

		$('screenshot_preview_link').addEvent('click', this.screenshotPreview.bind(this));
		$('screenshot_delete_link').addEvent('click', this.screenshotDelete.bind(this));
		$('screenshot_upload_form').getElement('input[type=file]').addEvent('change',function(){
			$('screenshot_upload_button').show();
		});
		$('screenshot_upload_button').addEvent('click', this.screenshotUpload.bind(this));


		//Секции
		$('section_add_button').addEvent('click', this.addSectionOpen.bind(this));
		$('section_add_cancel_button').addEvent('click', this.addSectionClose.bind(this));
		$('section_add_complete_button').addEvent('click', this.addSectionComplete.bind(this));
		this.objects['form_section_add'] = new jsValidator($('section_add_wrapper'));
		this.objects['form_section_add'].required('section_add_full_name');

		$('section_edit_button').addEvent('click', this.editSectionOpen.bind(this));
		$('section_edit_cancel_button').addEvent('click', this.editSectionClose.bind(this));
		$('section_edit_complete_button').addEvent('click', this.editSectionComplete.bind(this));
		this.objects['form_section_edit'] = new jsValidator($('section_edit_wrapper'));
		this.objects['form_section_edit'].required('section_edit_full_name');

		$('section_del_button').addEvent('click', this.deleteSection.bind(this));

		$('object_import_button').addEvent('click', this.importIRolesOpen.bind(this));
		$('import_iresource_id').addEvent('change', this.importIRolesSelectType.bind(this));
		$('import_type').addEvent('change', this.importIRolesSelectType.bind(this));
		$('import_cancel_button').addEvent('click', this.importIRolesClose.bind(this));
		$('import_complete_button').addEvent('click', this.importIRolesComplete.bind(this));

		//Данные
		this.setData(data);

		if(typeOf(this.objects['iresource'])!='object'){
			$('tabs_area').hide();
			$('tabs_none').show();
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


		//Группы ресурсов
		if(typeOf(data['iresource_groups'])=='array'){
			data['iresource_groups'].sort(function(a,b){if(a['full_name']>b['full_name'])return 1;return -1;});
			data['iresource_groups'].unshift({'igroup_id':'0','full_name':'-[Без группы]-'});
			this.objects['igroups'] = $unlink(data['iresource_groups']);
			select_add({
				'list': 'igroup_id',
				'key': 'igroup_id',
				'value': 'full_name',
				'options': data['iresource_groups'],
				'default': 0,
				'clear': true
			});
		}


		//Группы
		if(typeOf(data['groups'])=='array'){
			this.objects['groups'] = $unlink(data['groups']);
			data['groups'].unshift({'group_id':'0','full_name':'-[Нет исполнителей]-'});
			select_add({
				'list': 'info_worker_group',
				'key': 'group_id',
				'value': 'full_name',
				'options': data['groups'],
				'default': 0,
				'clear': true
			});
		}//Группы



		//Информационный ресурс
		if(typeOf(data['iresource'])=='object'){
			this.objects['iresource'] = data['iresource'];
			var id;
			for(var key in data['iresource']){
				id = 'info_'+key;
				if(!$(id))continue;
				$(id).setValue(data['iresource'][key]);
			}
			$('igroup_id').setValue(data['iresource']['iresource_group']);
			$('bigblock_title').set('text','Карточка информационного ресурса ID:'+data['iresource']['iresource_id']+' - '+data['iresource']['full_name']);
			if(parseInt(data['iresource']['company_id'])>0 && String(data['iresource']['post_uid'])!='0'){
				this.objects['post_selected'] = {
				'company_id': data['iresource']['company_id'],
				'company_name': data['iresource']['company_name'],
				'post_uid': data['iresource']['post_uid'],
				'post_name':data['iresource']['post_name']
				};
				this.selectorComplete();
			}else{
				this.objects['post_selected'] = null;
				this.selectorCancel();
			}
		}//Информационный ресурс


		//Организации в ресурсе
		if(typeOf(data['iresource_companies'])=='array'){
			var row;
			this.objects['iresource_companies']=[];
			for(var i=0;i<data['iresource_companies'].length;i++){
				if(this.objects['companies_assoc'][data['iresource_companies'][i]]){
					this.objects['iresource_companies'].push({
						'company_id': data['iresource_companies'][i],
						'full_name': this.objects['companies_assoc'][data['iresource_companies'][i]]
					});
				}
			}
			this.objects['table_companies'].setData(this.objects['iresource_companies']);
			this.objects['table_all_companies'].setData(this.objects['companies'].filterSelect({
				'company_id':{
					'value': data['iresource_companies'],
					'condition': 'NOTIN'
				}
			}));
		}//Организации в ресурсе


		//Типы доступа
		if(typeOf(data['ir_types'])=='array'){
			this.objects['ir_types_assoc'] = {};
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


		//Объекты доступа
		if(typeOf(data['iroles'])=='object'){
			this.objects['areas'] = data['iroles']['areas'];
			this.objects['items'] = data['iroles']['items'];
			this.objects['table_areas'].setData(data['iroles']['areas']);
			this.objects['table_items'].setData(data['iroles']['items']);
			this.selectIRole();
			this.selectSection();
			select_add({
				'list': ['add_section','edit_owner_id','import_section'],
				'key': 'irole_id',
				'value': 'full_name',
				'options':  data['iroles']['areas'],
				'default': 0,
				'clear': true
			});
		}//Объекты доступа


		//Объект доступа
		if(typeOf(data['irole'])=='object'){
			this.objects['selected_irole'] = data['irole'];
			var id;
			for(var key in data['irole']){
				id = 'edit_'+key;
				if(!$(id))continue;
				$(id).setValue(data['irole'][key]);
			}
			buildChecklist({
				'parent': 'edit_ir_type_area',
				'options': this.objects['ir_types'],
				'key': 'item_id',
				'value': 'full_name',
				'selected': data['irole']['ir_types'],
				'clear': true
			});
		}//Объект доступа


		//Раздел
		if(typeOf(data['section'])=='object'){
			this.objects['selected_section'] = data['section'];
			var id;
			for(var key in data['section']){
				id = 'section_edit_'+key;
				if(!$(id))continue;
				$(id).setValue(data['section'][key]);
			}
		}//Раздел


		//Список информационных ресурсов
		if(typeOf(data['iresources'])=='array'){
			data['iresources'].sort(function(a,b){if(a['full_name']>b['full_name'])return 1;return -1;});
			this.objects['iresources'] = data['iresources'];
			select_add({
				'list': 'import_iresource_id',
				'key': 'iresource_id',
				'value': 'full_name',
				'iterator': function(item){
					return {
						'key': item['iresource_id'],
						'value': item['full_name']+' (ID:'+item['iresource_id']+')'
					};
				},
				'options': data['iresources'].filterSelect({
					'iresource_id':{
						'value': this.objects['iresource']['iresource_id'],
						'condition': '!='
					}
				}),
				'default': 0,
				'clear': true
			});
		}//Список информационных ресурсов

	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/








	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/

	//Развертывание окна на весь экран
	fullscreen: function(normal_screen){
		var panel = $('page_iresources_info');
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
	changeTab: function(){
		
		
		
	},


	//Открытие окна выбора должности
	selectorOpen: function(){
		$('bigblock_wrapper').hide();
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
		$('bigblock_wrapper').show();
	},//end function


	//Выбрана должность
	selectorComplete: function(){
		if(typeOf(this.objects['post_selected'])=='object'){
			$('selected_post_none').hide();
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
		$('selected_post_none').show();
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
			this.objects['orgchart'].select('post_uid',(typeOf(this.objects['post_selected'])=='object' ? this.objects['post_selected']['post_uid'] : null ));
		}
		$('post_selector_complete_button').hide();
	},//end function


	//Сохранение
	anketSave: function(){
		if(!this.objects['form_anket'].validate()) return;
		var anket = {
			'action': 'iresource.edit',
			'iresource_id': this.objects['iresource']['iresource_id'],
			'full_name': $('info_full_name').getValue(),
			'short_name': $('info_short_name').getValue(),
			'description': $('info_description').getValue(),
			'location': $('info_location').getValue(),
			'techinfo': $('info_techinfo').getValue(),
			'is_lock': $('info_is_lock').getValue(),
			'igroup_id': $('igroup_id').getValue(),
			'company_id': (typeOf(this.objects['post_selected'])=='object' ? this.objects['post_selected']['company_id'] : 0),
			'post_uid': (typeOf(this.objects['post_selected'])=='object' ? this.objects['post_selected']['post_uid'] : 0),
			'worker_group': $('info_worker_group').getValue()
		};
		new axRequest({
			url : '/admin/ajax/iresources',
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


	//Добавление организации
	companyInclude: function(){
		if(!this.objects['table_all_companies'].selectedRows.length) return;
		var companies = [];
		for(var i=0; i<this.objects['table_all_companies'].selectedRows.length;i++){
			tr = this.objects['table_all_companies'].selectedRows[i];
			if(typeOf(tr)!='element') continue;
			data = tr.retrieve('data');
			if(typeOf(data)!='object') continue;
			companies.push(data['company_id']);
		}
		this.companyAction(companies, 'include');
	},//end function



	//Удаление организации
	companyExclude: function(){
		var tr, data;
		if(!this.objects['table_companies'].selectedRows.length) return;
		var companies = [];
		for(var i=0; i<this.objects['table_companies'].selectedRows.length;i++){
			tr = this.objects['table_companies'].selectedRows[i];
			if(typeOf(tr)!='element') continue;
			data = tr.retrieve('data');
			if(typeOf(data)!='object') continue;
			companies.push(data['company_id']);
		}
		this.companyAction(companies, 'exclude');
	},//end function



	//Операция с организациями
	companyAction: function(companies, action){
		if(typeOf(companies)!='array'||!companies.length) return;
		new axRequest({
			url : '/admin/ajax/iresources',
			data:{
				'action':'iresource.company.'+action,
				'iresource_id': this.objects['iresource']['iresource_id'],
				'companies': companies
			},
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
				}
			}
		}).request();
	},//end function



	//Выбран раздел
	selectSection: function(){
		$('sections_tool_edit').hide();
		if(!this.objects['table_areas'].selectedRows.length) return;
		$('sections_tool_edit').show();
	},//end function



	//Выбран объект доступа
	selectIRole: function(){
		if(!this.objects['table_items'].selectedRows.length){
			$('objects_tool_edit').hide();
			return;
		}
		$('objects_tool_edit').show();
	},//end function



	//Фильтр таблицы объектов доступа
	filterIRoles: function(){
		this.objects['table_items'].filter($('objects_filter').getValue());
	},//end function



	//Открытие окна добавления объекта доступа
	addIRoleOpen: function(){
		$('bigblock_wrapper').hide();
		$('add_full_name').value='';
		$('add_short_name').value='';
		$('add_description').value='';
		$('add_weight').value='0';
		select_set('add_section','0');
		$('object_add').show();
	},//end function



	//Закрытие окна добавления объекта доступа
	addIRoleClose: function(){
		$('object_add').hide();
		$('bigblock_wrapper').show();
	},//end function


	//Добавление объектов доступа
	addIRoleComplete: function(){
		if(!this.objects['form_add'].validate()) return;
		var ir_types=[];
		$('add_ir_type_area').getElements('input[type=checkbox]').each(function(el){if(el.checked==true){ir_types.push(el.value);}});
		new axRequest({
			url : '/admin/ajax/iresources',
			data:{
				'action':'irole.add',
				'iresource_id': this.objects['iresource']['iresource_id'],
				'full_name': $('add_full_name').getValue(),
				'short_name': $('add_short_name').getValue(),
				'description': $('add_description').getValue(),
				'weight': $('add_weight').getValue(),
				'section': $('add_section').getValue(),
				'is_lock': $('add_is_lock').getValue(),
				'ir_types': ir_types
			},
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
					App.pages[PAGE_NAME].addIRoleClose();
				}
			}
		}).request();
	},//end function


	//Удаление выбранного объекта доступа
	deleteIRole: function(){
		if(!this.objects['table_items'].selectedRows.length) return;
		var tr = this.objects['table_items'].selectedRows[0];
		if(typeOf(tr)!='element') return;
		var data = tr.retrieve('data');
		if(typeOf(data)!='object') return;
		var irole_id = data['irole_id'];
		var iresource_id = this.objects['iresource']['iresource_id'];
		App.message(
			'Подтвердите действие',
			'Вы действительно хотите удалить выбранный объект доступа?',
			'CONFIRM',
			function(){
				new axRequest({
					url : '/admin/ajax/iresources',
					data:{
						'action':'irole.delete',
						'iresource_id': iresource_id,
						'irole_id': irole_id
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



	//Редактирвоание выбранного объекта доступа
	editIRoleOpen: function(open){
		if(open === true){
			if(typeOf(this.objects['selected_irole'])!='object') return;
			$('bigblock_wrapper').hide();
			if(this.objects['selected_irole']['screenshot']!=''){
				$('file_exists_area').show();
			}else{
				$('file_exists_area').hide();
			}
			$('screenshot_upload_button').hide();
			$('screenshot_upload_form').getElement('input[name=iresource_id]').set('value',this.objects['selected_irole']['iresource_id']);
			$('screenshot_upload_form').getElement('input[name=irole_id]').set('value',this.objects['selected_irole']['irole_id']);
			$('object_edit').show();
			return;
		}
		if(!this.objects['table_items'].selectedRows.length) return;
		var tr = this.objects['table_items'].selectedRows[0];
		if(typeOf(tr)!='element') return;
		var data = tr.retrieve('data');
		if(typeOf(data)!='object') return;
		var irole_id = data['irole_id'];
		var iresource_id = this.objects['iresource']['iresource_id'];
		this.objects['selected_irole'] = null;
		new axRequest({
			url : '/admin/ajax/iresources',
			data:{
				'action':'irole.info',
				'iresource_id': iresource_id,
				'irole_id': irole_id
			},
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
					App.pages[PAGE_NAME].editIRoleOpen(true);
				}
			}
		}).request();
	},//end function



	//Закрытие окна редактирования объекта доступа
	editIRoleClose: function(){
		this.objects['selected_irole']=null;
		$('object_edit').hide();
		$('bigblock_wrapper').show();
	},//end function



	//Сохранение изменений
	editIRoleComplete: function(){
		if(typeOf(this.objects['selected_irole'])!='object') return this.editIRoleClose();
		if(!this.objects['form_edit'].validate()) return;
		var ir_types=[];
		$('edit_ir_type_area').getElements('input[type=checkbox]').each(function(el){if(el.checked==true){ir_types.push(el.value);}});
		new axRequest({
			url : '/admin/ajax/iresources',
			data:{
				'action':'irole.edit',
				'iresource_id': this.objects['selected_irole']['iresource_id'],
				'irole_id': this.objects['selected_irole']['irole_id'],
				'full_name': $('edit_full_name').getValue(),
				'short_name': $('edit_short_name').getValue(),
				'description': $('edit_description').getValue(),
				'weight': $('edit_weight').getValue(),
				'section': $('edit_owner_id').getValue(),
				'is_lock': $('edit_is_lock').getValue(),
				'ir_types': ir_types
			},
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
					App.pages[PAGE_NAME].editIRoleClose();
				}
			}
		}).request();
	},



	//Перегрузка объектов доступа
	reloadIRoles: function(open){
		new axRequest({
			url : '/admin/ajax/iresources',
			data:{
				'action':'iresource.roles',
				'iresource_id': this.objects['iresource']['iresource_id']
			},
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
				}
			}
		}).request();
	},//end function



	//Просмотр скриншота
	screenshotPreview: function(){
		if(typeOf(this.objects['selected_irole'])!='object') return;
		preview_irole(this.objects['selected_irole']['irole_id']);
	},//end function


	//Отправка сертификата на сервер
	screenshotUpload: function(){
		if(typeOf(this.objects['selected_irole'])!='object') return;
		var irole_id = this.objects['selected_irole']['irole_id'];
		var iresource_id = this.objects['selected_irole']['iresource_id'];
		new axRequest({
			uploaderForm: $('screenshot_upload_form'),
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
					App.pages[PAGE_NAME].objects['selected_irole']['screenshot']=String(irole_id);
					App.pages[PAGE_NAME].editIRoleOpen(true);
				}
			}
		}).upload();
	},//end function


	//Удаление скриншота с сервера
	screenshotDelete: function(){
		if(typeOf(this.objects['selected_irole'])!='object') return;
		var irole_id = this.objects['selected_irole']['irole_id'];
		var iresource_id = this.objects['selected_irole']['iresource_id'];
		App.message(
			'Подтвердите действие',
			'Вы действительно хотите удалить скриншот?',
			'CONFIRM',
			function(){
				new axRequest({
					url : '/admin/ajax/iresources',
					data:{
						'action':'irole.screenshot.delete',
						'iresource_id': iresource_id,
						'irole_id': irole_id
					},
					silent: false,
					waiter: true,
					callback: function(success, status, data){
						if(success){
							App.pages[PAGE_NAME].setData(data);
							App.pages[PAGE_NAME].objects['selected_irole']['screenshot']='';
							App.pages[PAGE_NAME].editIRoleOpen(true);
						}
					}
				}).request();
			}
		);
	},//end function



	//Открытие окна добавления секции
	addSectionOpen: function(){
		$('bigblock_wrapper').hide();
		$('section_add_full_name').value='';
		$('section_add_short_name').value='';
		$('section_add_description').value='';
		$('section_add').show();
	},//end function



	//Закрытие окна добавления раздела
	addSectionClose: function(){
		$('section_add').hide();
		$('bigblock_wrapper').show();
	},//end function




	//Добавление секций
	addSectionComplete: function(){
		if(!this.objects['form_section_add'].validate()) return;
		new axRequest({
			url : '/admin/ajax/iresources',
			data:{
				'action':'section.add',
				'iresource_id': this.objects['iresource']['iresource_id'],
				'full_name': $('section_add_full_name').getValue(),
				'short_name': $('section_add_short_name').getValue(),
				'description': $('section_add_description').getValue()
			},
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
					App.pages[PAGE_NAME].addSectionClose();
				}
			}
		}).request();
	},//end function



	//Открытие окна редактирования раздела
	editSectionOpen: function(open){
		if(open === true){
			if(typeOf(this.objects['selected_section'])!='object') return;
			$('bigblock_wrapper').hide();
			$('section_edit').show();
			return;
		}
		if(!this.objects['table_areas'].selectedRows.length) return;
		var tr = this.objects['table_areas'].selectedRows[0];
		if(typeOf(tr)!='element') return;
		var data = tr.retrieve('data');
		if(typeOf(data)!='object') return;
		var irole_id = data['irole_id'];
		var iresource_id = this.objects['iresource']['iresource_id'];
		this.objects['selected_section'] = null;
		new axRequest({
			url : '/admin/ajax/iresources',
			data:{
				'action':'section.info',
				'iresource_id': iresource_id,
				'irole_id': irole_id
			},
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
					App.pages[PAGE_NAME].editSectionOpen(true);
				}
			}
		}).request();
	},//end function



	//Закрытие окна редактирования раздела
	editSectionClose: function(){
		this.objects['selected_section']=null;
		$('section_edit').hide();
		$('bigblock_wrapper').show();
	},//end function



	//Сохранение изменений в разделе
	editSectionComplete: function(){
		if(typeOf(this.objects['selected_section'])!='object') return this.editSectionClose();
		if(!this.objects['form_section_edit'].validate()) return;
		new axRequest({
			url : '/admin/ajax/iresources',
			data:{
				'action':'section.edit',
				'iresource_id': this.objects['selected_section']['iresource_id'],
				'irole_id': this.objects['selected_section']['irole_id'],
				'full_name': $('section_edit_full_name').getValue(),
				'short_name': $('section_edit_short_name').getValue(),
				'description': $('section_edit_description').getValue()
			},
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
					App.pages[PAGE_NAME].editSectionClose();
				}
			}
		}).request();
	},//end function


	//Удаление раздела
	deleteSection: function(){
		if(!this.objects['table_areas'].selectedRows.length) return;
		var tr = this.objects['table_areas'].selectedRows[0];
		if(typeOf(tr)!='element') return;
		var data = tr.retrieve('data');
		if(typeOf(data)!='object') return;
		var irole_id = data['irole_id'];
		var iresource_id = this.objects['iresource']['iresource_id'];
		App.message(
			'Подтвердите действие',
			'Вы действительно хотите удалить выбранный раздел?<br>Все объекты доступа, включенные в удаляемый раздел, будут из него исключены',
			'CONFIRM',
			function(){
				new axRequest({
					url : '/admin/ajax/iresources',
					data:{
						'action':'irole.delete',
						'iresource_id': iresource_id,
						'irole_id': irole_id
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


	//Открытие окна импорта объектов доступа из другого информационного ресурса
	importIRolesOpen: function(open){
		if(open===true){
			if(typeOf(this.objects['iresources'])!='array') return;
			$('bigblock_wrapper').hide();
			select_set('import_type','copy');
			this.importIRolesSelectType();
			$('import_panel').show();
			return;
		}
		if(typeOf(this.objects['iresources'])=='array') return this.importIRolesOpen(true);
		new axRequest({
			url : '/admin/ajax/iresources',
			data:{
				'action':'iresources.list',
				'fields':['iresource_id','full_name']
			},
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
					App.pages[PAGE_NAME].importIRolesOpen(true);
				}
			}
		}).request();
	},//end function


	//Выбор метода копирования
	importIRolesSelectType: function(){
		var type = $('import_type').getValue();
		['copy','clone','custom'].each(function(item){if(type==item){$('import_type_'+item).show();}else{$('import_type_'+item).hide();}});
		$('import_objects_list').empty();
		if(type=='custom'){
			this.importIRolesSourceObjectsInit();
		}
	},//end function



	//Список объектов информационного ресурса
	importIRolesSourceObjectsBuild: function(data){
		if(typeOf(data)!='object'||typeOf(data['items'])!='array') return;
		if(!data['items'].length){
			$('import_objects_area').hide();
			$('import_objects_none').show();
			return;
		}
		buildChecklist({
			'parent': 'import_objects_list',
			'options': data['items'],
			'key': 'irole_id',
			'value': 'full_name',
			'sections': true,
			'clear': true
		});
		$('import_objects_none').hide();
		$('import_objects_area').show();
	},//end function


	//Список объектов информационного ресурса
	importIRolesSourceObjectsInit: function(){
		var import_iresource_id = $('import_iresource_id').getValue();
		if(typeOf(this.objects['ir_objects'][import_iresource_id])=='object'){
			return this.importIRolesSourceObjectsBuild(this.objects['ir_objects'][import_iresource_id]);
		}
		new axRequest({
			url : '/admin/ajax/iresources',
			data:{
				'action':'iresource.roles',
				'iresource_id': import_iresource_id
			},
			silent: true,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].objects['ir_objects'][data['iresource_id']] = data['iroles'];
					App.pages[PAGE_NAME].importIRolesSourceObjectsBuild(data['iroles']);
				}
			}
		}).request();
	},//end function



	//Закрытие окна импорта
	importIRolesClose: function(){
		$('import_panel').hide();
		$('bigblock_wrapper').show();
	},//end function



	//Импорт
	importIRolesComplete: function(){
		var iroles=[];
		$('import_objects_list').getElements('input[type=checkbox]').each(function(el){if(el.checked==true){iroles.push(el.value);}});
		var iresource_id = this.objects['iresource']['iresource_id'];
		new axRequest({
			url : '/admin/ajax/iresources',
			data:{
				'action':'iresource.import',
				'iresource_id': iresource_id,
				'import_from': $('import_iresource_id').getValue(),
				'import_type': $('import_type').getValue(),
				'import_section': $('import_section').getValue(),
				'iroles': iroles,
				'import_screenshots': $('import_screenshots').getValue()
			},
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
					App.pages[PAGE_NAME].importIRolesClose();
				}
			}
		}).request();
	},//end function


	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();