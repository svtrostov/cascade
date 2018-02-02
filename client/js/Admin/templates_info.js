;(function(){
var PAGE_NAME = 'templates_info';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': ['table_ir_selector'],
		'validators': ['form_anket'],
		'table_ir_selector': null,
		'form_anket': null,
		//
		'companies': null,
		'post_selected': null,
		'companies':null,
		'companies_assoc':{},
		'orgchart':null,
		'template':null,
		'iresources':null,
		'posts':{},
		'post_splitter':null,
		'post_selected':null,
		'anket': null,
		'ir_types':null,
		'ir_types_assoc':{},
		'ir_list':{},
		'selected_resource_id':0,
		'selected_items':null,
		'ir_results':null,
		'ir_results_count':0,
		'change': false
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
		$('template_info_save_button').addEvent('click',this.anketSave.bind(this));

		//Проверка формы
		this.objects['form_anket'] = new jsValidator($('anket_form'));
		this.objects['form_anket'].required('info_full_name');

		$('button_ir_trash').hide();
		$('button_ir_trash').addEvent('click',this.resultRemoveAll.bind(this));
		$('button_ir_add').addEvent('click',this.irSelectorOpen.bind(this));
		$('button_ir_save').addEvent('click',this.resultSave.bind(this));
		$('button_ir_selector_cancel').addEvent('click',this.irSelectorClose.bind(this));
		$('ir_selector_iresource_list').addEvent('change',this.irSelectorChangeIResource.bind(this));
		$('button_ir_selector_sections_collapse').addEvent('click',this.irSelectorSectionsCollapse.bind(this));
		$('button_ir_selector_sections_expand').addEvent('click',this.irSelectorSectionsExpand.bind(this));
		$('button_ir_selector_complete').addEvent('click',this.irSelectorComplete.bind(this));

		$('button_ir_copy').addEvent('click',this.importOpen.bind(this));
		$('import_cancel_button').addEvent('click', this.importClose.bind(this));
		$('import_type').addEvent('change', this.importSelectType.bind(this));
		$('import_complete_button').addEvent('click', this.importComplete.bind(this));


		this.objects['table_ir_selector'] = new jsTable('ir_selector_table', {
			'dataBackground1':'#efefef',
			'class': 'jsTable',
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
					width:'120px',
					dataSource:'ir_types',
					styles:{'min-width':'120px'},
					dataFunction:function(table, cell, text, data){
						var result=[['0','-- Нет --']];
						if(typeOf(text)=='array'&&text.length>0){
							for(var i=0; i<text.length;i++){
								if(App.pages[PAGE_NAME].objects['ir_types_assoc'][text[i]]){
									result.push([text[i],App.pages[PAGE_NAME].objects['ir_types_assoc'][text[i]]]);
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
							if(
								typeOf(App.pages[PAGE_NAME].objects['ir_list'][iresource_id])!='object'||
								typeOf(App.pages[PAGE_NAME].objects['ir_list'][iresource_id]['items'])!='array'
							){
								return false;
							}
							App.pages[PAGE_NAME].objects['selected_items'][irole_id] = select_getValue(this);
							////Читается как UPDATE ARRAY SET [setColumn] = [value] WHERE [termColumn] = [term] LIMIT [limit]
							////App.pages[PAGE_NAME].objects['ir_list'][iresource_id]['items'].filterUpdate('ir_selected', select_getValue(this), 'irole_id', irole_id, 1);
						});
						return '';
					}
				}
			],
			selectType:1
		});



		//Данные
		this.setData(data);

		if(typeOf(this.objects['template'])!='object'){
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


		//Шаблон
		if(typeOf(data['template'])=='object'){
			this.objects['template'] = data['template'];
			var id;
			for(var key in data['template']){
				id = 'info_'+key;
				if(!$(id))continue;
				$(id).setValue(data['template'][key]);
			}
			$('bigblock_title').set('text','Карточка шаблона ID:'+data['template']['template_id']+' - '+data['template']['full_name']);
			if(parseInt(data['template']['company_id'])>0){
				this.objects['post_selected'] = {
				'company_id': data['template']['company_id'],
				'company_name': data['template']['company_name'],
				'post_uid': '0',
				'post_name':'-[Любая должность]-'
				};
				if(data['template']['post_uid']!='0'){
					this.objects['post_selected']['post_uid'] = data['template']['post_uid'];
					this.objects['post_selected']['post_name'] = data['template']['post_name'];
				}
				this.selectorComplete();
			}else{
				this.objects['post_selected'] = null;
				this.selectorCancel();
			}
		}//Шаблон


		//Типы доступа
		if(typeOf(data['ir_types'])=='array'){
			this.objects['ir_types_assoc'] = {};
			this.objects['ir_types'] = data['ir_types'];
			for(var i=0; i<data['ir_types'].length;i++){
				this.objects['ir_types_assoc'][data['ir_types'][i]['item_id']] = data['ir_types'][i]['full_name'];
			}
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
		}//Объект доступа


		//Список информационных ресурсов
		if(typeOf(data['iresources'])=='array'){
			data['iresources'].sort(function(a,b){if(a['full_name']>b['full_name'])return 1;return -1;});
			this.objects['iresources'] = data['iresources'];
			select_add({
				'list': ['ir_selector_iresource_list'],
				'key': 'iresource_id',
				'value': 'full_name',
				'options':  data['iresources'],
				'default': 0,
				'clear': true
			});
		}//Список информационных ресурсов


		//Список шаблонов
		if(typeOf(data['templates'])=='array'){
			data['templates'].sort(function(a,b){if(a['full_name']>b['full_name'])return 1;return -1;});
			this.objects['templates'] = data['templates'];
			select_add({
				'list': ['import_template_id'],
				'key': 'template_id',
				'value': 'full_name',
				'iterator': function(item){
					return {
						'key': item['template_id'],
						'value': item['full_name']+' (ID:'+item['template_id']+')'
					};
				},
				'options': data['templates'].filterSelect({
					'template_id':{
						'value': this.objects['template']['template_id'],
						'condition': '!='
					}
				}),
				'default': 0,
				'clear': true
			});
		}//Список шаблонов


		//Объекты шаблона
		if(typeOf(data['tmpl_roles'])=='array'){
			var iresource_id;
			for(var i=0; i<data['tmpl_roles'].length; i++){
				data['tmpl_roles'][i]['full_name'] = this.objects['iresources'].filterResult('full_name','iresource_id', data['tmpl_roles'][i]['iresource_id']);
			}
			data['tmpl_roles'].sort(function(a,b){if(a['full_name']>b['full_name'])return 1;return -1;});
			for(var i=0; i<data['tmpl_roles'].length; i++){
				iresource_id = data['tmpl_roles'][i]['iresource_id'];
				this.objects['ir_list'][iresource_id] = data['tmpl_roles'][i];
				this.objects['selected_items']={};
				this.objects['selected_resource_id']=iresource_id;
				this.irSelectorComplete();
			}
			this.objects['selected_items']=null;
			this.objects['selected_resource_id']=0;
			this.setChangeStatus(false);
		}//Объекты шаблона



	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/








	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/

	//Развертывание окна на весь экран
	fullscreen: function(normal_screen){
		var panel = $('page_templates_info');
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



	//Сохранение
	anketSave: function(){
		if(!this.objects['form_anket'].validate()) return;
		var anket = {
			'action': 'template.edit',
			'template_id': this.objects['template']['template_id'],
			'full_name': $('info_full_name').getValue(),
			'description': $('info_description').getValue(),
			'is_lock': $('info_is_lock').getValue(),
			'is_for_new_employer': $('info_is_for_new_employer').getValue(),
			'company_id': (typeOf(this.objects['post_selected'])=='object' ? this.objects['post_selected']['company_id'] : 0),
			'post_uid': (typeOf(this.objects['post_selected'])=='object' ? this.objects['post_selected']['post_uid'] : 0)
		};
		new axRequest({
			url : '/admin/ajax/templates',
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



	//Открытие окна выбора ИР и объектов
	irSelectorOpen: function(iresource_id){
		$('bigblock_wrapper').hide();
		this.irSelectorChangeIResource(iresource_id);
		$('ir_selector').show();
	},//end function



	//Закрытие окна выбора ИР и объектов
	irSelectorClose: function(){
		$('ir_selector').hide();
		$('bigblock_wrapper').show();
	},//end function



	//Выбор информационного ресурса
	irSelectorChangeIResource: function(iresource_id){
		iresource_id = parseInt(iresource_id);
		if(iresource_id){
			select_set('ir_selector_iresource_list', iresource_id);
		}else{
			iresource_id = select_getValue('ir_selector_iresource_list');
		}
		if(!iresource_id) return false;
		this.objects['selected_resource_id'] = iresource_id;
		this.objects['selected_items'] = {};
		$('ir_selector_table').hide();
		//Объекты выбранного ИР еще не закешированы
		if(typeOf(this.objects['ir_list'][iresource_id])!='object'){
			$('ir_selector_iresource_list').disable();
			new axRequest({
				url : '/admin/ajax/iresources',
				data:{
					'action':'iresource.roles',
					'iresource_id': iresource_id
				},
				silent: true,
				waiter: true,
				callback: function(success, status, data){
					$('ir_selector_iresource_list').enable();
					if(success){
						if(typeOf(data)!='object'||typeOf(data['iroles'])!='object'||typeOf(data['iroles']['items'])!='array') return;
						App.pages[PAGE_NAME].objects['ir_list'][iresource_id] = data['iroles'];
						App.pages[PAGE_NAME].irSelectorIRolesBuild(iresource_id);
					}
				}
			}).request();
		}
		//Объекты выбранного ИР находятся в кеше
		else{
			this.irSelectorIRolesBuild(iresource_id);
		}
		
	},//end function



	//Построение списка объектов доступа для выбранного ИР
	irSelectorIRolesBuild: function(iresource_id){
		$('ir_selector_table').hide();
		$('ir_selector_none').show();
		if(typeOf(this.objects['ir_list'][iresource_id])!='object'||typeOf(this.objects['ir_list'][iresource_id]['items'])!='array'){
			return App.message(
				'Ошибка JavaScript',
				'Массив объектов доступа для информационного ресурса задан некорректно.<br/><br/>'+
				'Свяжитесь с администратором для разрешения данной ситуации.',
				'error'
			);
		}
		if(this.objects['ir_list'][iresource_id]['items'].length > 0){
			$('ir_selector_none').hide();
			this.objects['table_ir_selector'].setData(this.objects['ir_list'][iresource_id]['items']);
			$('ir_selector_table').show();
		}
	},//end function



	//Свернуть все секции
	irSelectorSectionsCollapse: function(){
		if(this.objects['table_ir_selector']) this.objects['table_ir_selector'].allSectionsDisplay(false);
	},//end function



	//Развернуть все секции
	irSelectorSectionsExpand: function(){
		if(this.objects['table_ir_selector']) this.objects['table_ir_selector'].allSectionsDisplay(true);
	},//end function



	//Выбран тип доступа
	irSelectorChangeIRType: function(){
		var iresource_id = this.retrieve('iresource_id');
		var irole_id = this.retrieve('irole_id');
		if(!iresource_id) return false;
		if(typeOf(request_new_objects['IR_REQUEST'][iresource_id])!='array') return false;
		////Читается как UPDATE ARRAY SET [setColumn] = [value] WHERE [termColumn] = [term] LIMIT [limit]
		request_new_objects['IR_REQUEST'][iresource_id].filterUpdate('ir_selected', select_getValue(this), 'irole_id', irole_id, 1);
	},//end function



	//Закрытие окра выбора объектов доступа с применением выбранных данных
	irSelectorComplete: function(){
		if(typeOf(this.objects['selected_items'])!='object') return this.irSelectorClose();
		var iresource_id = this.objects['selected_resource_id'];
		if(typeOf(this.objects['ir_list'][iresource_id])!='object'||typeOf(this.objects['ir_list'][iresource_id]['items'])!='array') return this.irSelectorClose();
		var ir_type;
		for(var irole_id in this.objects['selected_items']){
			ir_type = this.objects['selected_items'][irole_id];
			this.objects['ir_list'][iresource_id]['items'].filterUpdate('ir_selected', ir_type, 'irole_id', irole_id, 1);
		}

		var items_exists = false;
		var idata = [], row, section=null, is_section;

		//Формирование массива запрашиваемых объектов доступа по информационному ресурсу
		for(var index=0; index < this.objects['ir_list'][iresource_id]['items'].length; index++){
			row = this.objects['ir_list'][iresource_id]['items'][index];
			is_section = (typeOf(row)=='object' ? false : true);
			if(is_section){
				section = row;
			}else{
				if(String(row['ir_selected']).toInt()>0){
					if(section != null){
						idata.push(section);
						section = null;
					}
					idata.push(row);
				}
			}
		}//Формирование массива запрашиваемых объектов доступа по информационному ресурсу


		//Не выбран ни один объект доступа
		if(idata.length == 0){
			//Удаление из результирующего списка 
			this.resultRemove(iresource_id);
		}else{
			//Проверка существования объектов в результирующем списке
			if(typeOf(this.objects['ir_results'])!='object'){
				this.objects['ir_results'] = {};
				this.objects['ir_results_count'] = 0;
			}
			if(typeOf(this.objects['ir_results'][iresource_id])!='object'){
				this.objects['ir_results'][iresource_id] = {
					'item': null,
					'table': null
				};
			}else{
				if(typeOf(this.objects['ir_results'][iresource_id]['item'])=='object') items_exists = true;
			}
			if(!items_exists){
				this.resultAdd(iresource_id);
				this.objects['ir_results_count']++;
			}
			this.objects['ir_results'][iresource_id]['table'].setData(idata);
		}

		this.setChangeStatus(true);
		this.resultUpdateInterface();
		this.irSelectorClose();

	},//end function


	//Обновление интерфейса: отображение/сокрытие элементов
	resultUpdateInterface: function(){
		if(this.objects['ir_results_count'] > 0){
			$('ir_area').show();
			$('ir_none').hide();
			$('button_ir_trash').show();
		}else{
			$('ir_area').hide();
			$('ir_none').show();
			$('button_ir_trash').hide();
		}
	},//end function



	//Добавление ресурса в результат
	resultAdd: function(iresource_id){
		var title = this.objects['iresources'].filterResult('full_name', 'iresource_id', iresource_id);
		var li = build_blockitem({
			'list': 'ir_list',
			'title'	: title
		});
		this.objects['ir_results'][iresource_id]['item'] = li;
		li['container'].setStyles({
			'padding': '0px',
			'margin': '0px'
		});

		//Редактирование ИР
		new Element('span',{
			'title':'Редактировать',
			'class':'ui-icon-white ui-icon-pencil'
		}).inject(li['toolbar']).setStyles({
			'cursor':'pointer'
		}).addEvents({
			click: function(e){
				App.pages[PAGE_NAME].irSelectorOpen(iresource_id);
				e.stop();
				return false;
			}
		});

		//Удаление ИР
		new Element('span',{
			'title':'Удалить из шаблона данный информационный ресурс',
			'class':'ui-icon-white ui-icon-trash'
		}).inject(li['toolbar']).setStyles({
			'cursor':'pointer'
		}).addEvents({
			click: function(e){
				App.message(
					'Подтвердите действие',
					'Вы действиктльно хотите убрать из шаблона информационный ресурс: '+title+'?',
					'CONFIRM',
					function(){
						App.pages[PAGE_NAME].resultRemove(iresource_id);
					}
				);
				e.stop();
				return false;
			}
		});

		this.objects['ir_results'][iresource_id]['table'] = new jsTable(li['container'],{
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
					width:'30%',
					sortable:false,
					caption: 'Объект доступа',
					dataSource:'full_name'
				},
				{
					width:'40%',
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
						text = parseInt(text);
						if(text<3) return text+' (Низкая)';
						if(text<6) return text+' (Средняя)';
						if(text<8) return text+' (Высокая)';
						return text+' (Критично)';
					}
				},
				{
					caption: 'Запросить доступ',
					sortable:false,
					width:'120px',
					dataSource:'ir_selected',
					dataStyle:{'text-align':'center','min-width':'120px'},
					dataFunction:function(table, cell, text, data){
						if(String(text) == '0') return '-[Нет]-';
						if(App.pages[PAGE_NAME].objects['ir_types_assoc'][text]){
							return App.pages[PAGE_NAME].objects['ir_types_assoc'][text];
						}else{
							return '-[?????]-';
						}
					}
				}
			],
			'dataBackground1':'#efefef',
			selectType:1
		});

	},//end function



	//Удаление ИР из результата
	resultRemove: function(iresource_id){
		if(typeOf(this.objects['ir_list'][iresource_id])!='object'||typeOf(this.objects['ir_list'][iresource_id]['items'])!='array') return;

		for(var i=0; i< this.objects['ir_list'][iresource_id]['items'].length; i++){
			if(typeOf(this.objects['ir_list'][iresource_id]['items'][i])=='object')
			this.objects['ir_list'][iresource_id]['items'][i]['ir_selected']=0;
		}

		if(typeOf(this.objects['ir_results'])=='object'){
			if(typeOf(this.objects['ir_results'][iresource_id])=='object'){
				if(this.objects['ir_results'][iresource_id]['table']){
					this.objects['ir_results'][iresource_id]['table'].terminate();
					this.objects['ir_results'][iresource_id]['table'] = null;
				}
				if(this.objects['ir_results'][iresource_id]['item']){
					this.objects['ir_results'][iresource_id]['item']['li'].destroy();
					this.objects['ir_results'][iresource_id]['item'] = null;
				}
				this.objects['ir_results_count']--;
				this.resultUpdateInterface();
			}
			this.objects['ir_results'][iresource_id] = null;
		}
		this.setChangeStatus(true);
	},//end function



	//Удаление всех ИР: запрос
	resultRemoveAll: function(){
		App.message(
			'Подтвердите действие',
			'Вы действиктльно хотите убрать из шаблона все информационный ресурсы?',
			'CONFIRM',
			function(){
				App.pages[PAGE_NAME].resultRemoveAllProcess();
				App.pages[PAGE_NAME].setChangeStatus(true);
			}
		);
	},//end function



	//Удаление всех ИР: процесс
	resultRemoveAllProcess: function(){

		if(typeOf(this.objects['ir_list'])=='object'){
			for(var iresource_id in this.objects['ir_list']){
				if(typeOf(this.objects['ir_list'][iresource_id])=='object'&&typeOf(this.objects['ir_list'][iresource_id]['items'])=='array'){
					for(var i=0; i<this.objects['ir_list'][iresource_id]['items'].length; i++){
						if(typeOf(this.objects['ir_list'][iresource_id]['items'][i])=='object'){
							this.objects['ir_list'][iresource_id]['items'][i]['ir_selected']=0;
						}
					}
				}
			}
		}

		if(typeOf(this.objects['ir_results'])=='object'){
			for(var i in this.objects['ir_results']){
				if(typeOf(this.objects['ir_results'])!='object') continue;
				if(this.objects['ir_results'][i]['table']){
					this.objects['ir_results'][i]['table'].terminate();
					this.objects['ir_results'][i]['table'] = null;
				}
				if(this.objects['ir_results']['item']){
					this.objects['ir_results'][i]['item']['li'].destroy();
					this.objects['ir_results'][i]['item'] = null;
				}
				this.objects['ir_results'][i] = null;
			}
		}

		this.objects['ir_results'] = null;
		this.objects['ir_results_count']=0;
		$('ir_list').empty();
		this.resultUpdateInterface();
	},//end function



	//Сохранение шаблона
	resultSave: function(){
		var irole_id, ir_type, a=[];
		if(typeOf(this.objects['ir_list'])=='object'){
			for(var iresource_id in this.objects['ir_list']){
				if(typeOf(this.objects['ir_list'][iresource_id])=='object'&&typeOf(this.objects['ir_list'][iresource_id]['items'])=='array'){
					for(var i=0; i<this.objects['ir_list'][iresource_id]['items'].length; i++){
						if(typeOf(this.objects['ir_list'][iresource_id]['items'][i])=='object'){
							irole_id= String(this.objects['ir_list'][iresource_id]['items'][i]['irole_id']).toInt();
							ir_type = String(this.objects['ir_list'][iresource_id]['items'][i]['ir_selected']).toInt();
							if(ir_type > 0){
								a.push([iresource_id,irole_id,ir_type]);
								
							}
						}
					}
				}
			}
		}

		new axRequest({
			url : '/admin/ajax/templates',
			data:{
				'action':'template.save',
				'template_id': this.objects['template']['template_id'],
				'a': a
			},
			silent: false,
			display: 'hint',
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
				}
			}
		}).request();

	},//end function



	//Установка статуса изменения
	setChangeStatus: function(is_changed){
		this.objects['change'] = is_changed;
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



	//Проверка был ли сохранен шаблон
	checkChange: function(callback){
		if(!this.objects['change']) return callback();
		App.message(
			'Подтвердите действие',
			'Вы вносили изменения в шаблон, но не сохранили его, продолжить без сохранения?',
			'CONFIRM',
			function(){
				return callback();
			}
		);
	},//end function



	//Импорт: открыть окно
	importOpen: function(){
		$('bigblock_wrapper').hide();
		this.importSelectType();
		$('import_panel').show();
	},//end function



	//Импорт: закрыть окно
	importClose: function(){
		$('import_panel').hide();
		$('bigblock_wrapper').show();
	},//end function



	//Выбор метода копирования
	importSelectType: function(){
		var type = $('import_type').getValue();
		['copy','clone'].each(function(item){if(type==item){$('import_type_'+item).show();}else{$('import_type_'+item).hide();}});
	},//end function



	//Импорт
	importComplete: function(){
		var template_id = this.objects['template']['template_id'];
		new axRequest({
			url : '/admin/ajax/templates',
			data:{
				'action':'template.import',
				'template_id': template_id,
				'import_from': $('import_template_id').getValue(),
				'import_type': $('import_type').getValue(),
				'import_copy_replace': $('import_copy_replace').getValue()
			},
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].resultRemoveAllProcess();
					App.pages[PAGE_NAME].setData(data);
					App.pages[PAGE_NAME].importClose();
				}
			}
		}).request();
	},//end function

	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();