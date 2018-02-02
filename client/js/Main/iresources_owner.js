;(function(){
var PAGE_NAME = 'iresources_owner';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': ['table_employers','table_iroles','table_employer_iroles','table_irole_employers', 'table_objects_lock'],
		'table_employers':null,
		'table_iroles':null,
		'table_employer_iroles':null,
		'table_irole_employers':null,
		'table_objects_lock':null,
		//
		'process_search':false,
		'employer_selected':null,
		'iresources':null,
		'iresources_assoc':{},
		'ir_types':null,
		'ir_types_assoc':{},
		'selected_iresource_id': 0,
		'selected_items':{},
		'ir_list': {},
		'lock_info': null
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
		self.objects['tables'].each(function(table){
			if(self.objects[table]) self.objects[table].terminate();
		});
		for(var i in self.objects) self.objects[i] = null;
		self.objects = {};
	},//end function



	//Инициализация страницы
	start: function(data){

		if(typeOf(data['iresources'])!='array'||!data['iresources'].length){
			$('bigblock_wrapper').hide();
			$('bigblock_none').show();
			return;
		}

		this.objects['tabs'] = new jsTabPanel('tabs_area',{
			//'onchange': this.changeTab.bind(this)
		});

		//Таблица сотрудников
		this.objects['table_employers'] = new jsTable('employers_table',{
			'dataBackground1':'#efefef',
			'class': 'jsTableLight',
			columns:[
				{
					caption: 'ФИО сотрудника',
					dataSource:'search_name',
					width:'180px',
					sortable: false,
					dataStyle:{'text-align':'left','min-width':'150px'},
					dataFunction:function(table, cell, text, data){
						return data['search_name']+(data['phone'] || data['email'] ? '<div class="small">'+( data['phone'] ? '<i>Телефон:</i> '+data['phone'] : '')+(data['email'] ? '<i>E-mail:</i> <a class="mailto" href="mailto:'+data['email']+'">'+data['email']+'</a>':'')+'</div>' : '');
					}
				},
				{
					caption: 'Занимаемая должность',
					sortable:false,
					width:'180px',
					dataSource:'post_name',
					dataStyle:{'text-align':'left','min-width':'150px'},
					dataFunction:function(table, cell, text, data){
						return '<b>'+data['company_name']+'</b><br>'+data['post_name'];
					}
				},
			]
		});
		this.objects['table_employers'].addEvent('click', this.selectEmployer.bind(this));


		var table_columns = [
			{
				width:'30%',
				sortable:false,
				caption: 'Функционал',
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
				caption: 'Текущий доступ',
				sortable:false,
				width:'120px',
				dataSource:'ir_type',
				dataStyle:{'text-align':'center','min-width':'120px'},
				dataFunction:function(table, cell, text, data){
					if(String(text) == '0') return '-[Нет]-';
					if(App.pages[PAGE_NAME].objects['ir_types_assoc'][text]){
						return App.pages[PAGE_NAME].objects['ir_types_assoc'][text];
					}else{
						return '-[??? ID:'+text+']-';
					}
				}
			},
			{
				caption: 'Начиная с даты',
				dataSource:'timestamp',
				width:100,
				sortable: false,
				dataStyle:{'text-align':'center','min-width':'100px'}
			}
		];



		//Таблица объектов ИР к которым сотрудник имеет доступ
		this.objects['table_employer_iroles'] = new jsTable('iroles_list_table',{
			'class': 'jsTable',
			sectionCollapsible:true,
			columns: table_columns,
			'dataBackground1':'#efefef',
			selectType: 2
		});
		this.objects['table_employer_iroles'].addEvent('click', this.selectEmployerIRole.bind(this));


		//Таблица блокируемых объектов ИР
		this.objects['table_objects_lock'] = new jsTable('objects_lock_table',{
			'class': 'jsTableLight',
			sectionCollapsible:true,
			columns: table_columns,
			'dataBackground1':'#efefef',
			selectType: 1
		});


		//Таблица объектов доступа
		this.objects['table_iroles'] = new jsTable('iroles_table', {
			'dataBackground1':'#efefef',
			'class': 'jsTableLight',
			sectionCollapsible:true,
			columns: [
				{
					width:'150px',
					caption: 'Функционал',
					styles:{'min-width':'150px'},
					dataSource:'full_name'
				},
				{
					width:'170px',
					caption: 'Описание',
					styles:{'min-width':'170px'},
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
				}
			],
			selectType:1
		});
		this.objects['table_iroles'].addEvent('click', this.selectIRole.bind(this));


		//Таблица сотрудников, имеющих доступ к объекту ИР
		this.objects['table_irole_employers'] = new jsTable('employers_list_table',{
			'dataBackground1':'#efefef',
			columns:[
				{
					caption: 'ФИО сотрудника',
					dataSource:'search_name',
					width:220,
					sortable: false,
					dataStyle:{'text-align':'left'},
					dataFunction:function(table, cell, text, data){
						return data['search_name']+(data['phone'] || data['email'] ? '<div class="small">'+( data['phone'] ? '<i>Телефон:</i> '+data['phone'] : '')+(data['email'] ? ' <i>E-mail:</i> <a class="mailto" href="mailto:'+data['email']+'">'+data['email']+'</a>':'')+'</div>' : '');
					}
				},
				{
					caption: 'Занимаемая должность',
					sortable:false,
					width:'180px',
					dataSource:'post_name',
					dataStyle:{'text-align':'left','min-width':'180px'},
					dataFunction:function(table, cell, text, data){
						return '<b>'+data['company_name']+'</b><br>'+data['post_name'];
					}
				},
				{
					caption: 'Текущий доступ',
					sortable:false,
					width:'80px',
					dataSource:'ir_type',
					dataStyle:{'text-align':'center','min-width':'80px'},
					dataFunction:function(table, cell, text, data){
						return (App.pages[PAGE_NAME].objects['ir_types_assoc'][data['ir_type']] ? App.pages[PAGE_NAME].objects['ir_types_assoc'][data['ir_type']] : '-[?????]-');
					}
				},
				{
					caption: 'Начиная с даты',
					dataSource:'timestamp',
					width:100,
					sortable: false,
					dataStyle:{'text-align':'center','min-width':'100px'},
				}
			]
		});


		this.objects['employers_splitter'] = set_splitter_h({
			'left'		: $('employers_area'),
			'right'		: $('iroles_list_area'),
			'splitter'	: $('employers_splitter'),
			'parent'	: $('tabs_area')
		});
		this.objects['iroles_splitter'] = set_splitter_h({
			'left'		: $('iroles_area'),
			'right'		: $('employers_list_area'),
			'splitter'	: $('iroles_splitter'),
			'parent'	: $('tabs_area')
		});

		this.displayIRolesListArea('select');
		this.displayEmployersListArea('select');
		$('iresource_selector').addEvent('change', this.changeIResource.bind(this));
		$('employers_filter').addEvent('keydown',function(e){if(e.code==13) App.pages[PAGE_NAME].employerTableFilter();});
		$('employers_filter_button').addEvent('click', this.employerTableFilter.bind(this));
		$('objects_filter').addEvent('keydown',function(e){if(e.code==13) App.pages[PAGE_NAME].employerIRoleTableFilter();});
		$('employer_list_filter').addEvent('keydown',function(e){if(e.code==13) App.pages[PAGE_NAME].employerListTableFilter();});


		$('object_lock_button').addEvent('click', this.irLockerOpen.bind(this));
		$('button_ir_locker_cancel').addEvent('click', this.irLockerCancel.bind(this));
		$('button_ir_locker_complete').addEvent('click', this.irLockerComplete.bind(this));
		$('button_ir_locker_success_cancel').addEvent('click', this.irLockerSuccessClose.bind(this));

		//Применение данных
		this.setData(data);
		$('centralarea_container').show();
	},//end function




	/*******************************************************************
	 * Обработка данных
	 ******************************************************************/

	//Обработка данных
	setData: function(data){
		var type;
		if(typeOf(data)!='object') return;
		var selected_iresource = false;

		//Типы доступа
		if(typeOf(data['ir_types'])=='array'){
			this.objects['ir_types_assoc'] = {};
			this.objects['ir_types'] = data['ir_types'];
			for(var i=0; i<data['ir_types'].length;i++){
				this.objects['ir_types_assoc'][data['ir_types'][i]['item_id']] = data['ir_types'][i]['full_name'];
			}
		}//Типы доступа


		//Информационные ресурсы
		if(typeOf(data['iresources'])=='array'){
			data['iresources'].sort(function(a,b){if(a['full_name']>b['full_name'])return 1;return -1;});
			this.objects['iresources'] = $unlink(data['iresources']);
			for(var i=0; i<data['iresources'].length;i++){
				this.objects['iresources_assoc'][data['iresources'][i]['iresource_id']] = data['iresources'][i]['full_name'];
			}
			select_add({
				'list': 'iresource_selector',
				'key': 'iresource_id',
				'value': 'full_name',
				'options': data['iresources'],
				'default': (data['iresources'].length>0 ? data['iresources'][0]['iresource_id'] : '0'),
				'clear': true
			});
			selected_iresource = true;
		}//Информационные ресурсы


		//Объекты доступа сотрудника
		if(typeOf(data['employer_iroles'])=='array'){
			if(data['employer_iroles'].length>0){
				this.objects['table_employer_iroles'].setData(data['employer_iroles']);
				this.selectEmployerIRole();
				this.displayIRolesListArea('table_area');
			}else{
				this.displayIRolesListArea('none');
			}
		}//Объекты доступа сотрудника


		//Сотрудники которым доступен объект ИР
		if(typeOf(data['irole_employers'])=='array'){
			if(data['irole_employers'].length>0){
				this.objects['table_irole_employers'].setData(data['irole_employers']);
				this.selectEmployerIRole();
				this.displayEmployersListArea('table_area');
			}else{
				this.displayEmployersListArea('none');
			}
		}//Сотрудники которым доступен объект ИР


		if(selected_iresource){
			$('iresource_selector').fireEvent('change');
		}

	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/






	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/


	//Выбор информационного ресурса
	changeIResource: function(iresource_id){
		iresource_id = parseInt(iresource_id);
		if(iresource_id){
			select_set('iresource_selector', iresource_id);
		}else{
			iresource_id = $('iresource_selector').getValue();
		}
		if(!iresource_id) return false;
		this.objects['selected_iresource_id'] = iresource_id;
		this.objects['selected_items'] = {};
		//Объекты выбранного ИР еще не закешированы
		if(typeOf(this.objects['ir_list'][iresource_id])!='object'){
			$('iresource_selector').disable();
			new axRequest({
				url : '/main/ajax/irowner',
				data:{
					'action':'iresource.data',
					'iresource_id': iresource_id,
					'iroles':'1',
					'employers':'1'
				},
				silent: true,
				waiter: true,
				callback: function(success, status, data){
					$('iresource_selector').enable();
					if(success){
						App.pages[PAGE_NAME].setIResource(iresource_id, data);
					}
				}
			}).request();
		}
		//Объекты выбранного ИР находятся в кеше
		else{
			this.iResourceBuild(iresource_id);
		}

	},//end function




	//Применение данных по информационному ресурсу
	setIResource: function(iresource_id, data){
		if(typeOf(data['iroles'])!='array') return;
		if(typeOf(data['employers'])!='array') return;
		data['employers'].sort(function(a,b){if(a['search_name']>b['search_name'])return 1;return -1;});
		this.objects['ir_list'][iresource_id] = {
			'iroles': data['iroles'],
			'employers': data['employers']
		};
		this.iResourceBuild(iresource_id);
	},//end function




	//Построение данных по информационному ресурсу
	iResourceBuild: function(iresource_id){
		if(typeOf(this.objects['ir_list'][iresource_id])!='object') return;
		var iresource = this.objects['ir_list'][iresource_id];
		if(!iresource['iroles'].length){
			$('iroles_table').hide();
			$('iroles_none').show();
		}else{
			$('iroles_none').hide();
			this.objects['table_iroles'].setData(iresource['iroles']);
			$('iroles_table').show();
		}
		if(!iresource['employers'].length){
			$('employers_table').hide();
			$('employers_none').show();
		}else{
			$('employers_none').hide();
			this.objects['table_employers'].setData(iresource['employers']);
			$('employers_table').show();
		}
		this.displayIRolesListArea('select');
	},//end function



	//Фильтрация данных
	employerTableFilter: function(){
		var term = $('employers_filter').getValue();
		this.objects['table_employers'].clearSelected();
		this.objects['table_employers'].filter(term);
		this.displayIRolesListArea('select');
	},//end function


	//
	displayIRolesListArea: function(area){
		['iroles_list_table_area','iroles_list_none','iroles_list_select'].each(function(item){
			if ('iroles_list_'+area == item) $(item).show(); else $(item).hide();
		});
	},//end function


	//
	displayEmployersListArea: function(area){
		['employers_list_table_area','employers_list_none','employers_list_select'].each(function(item){
			if ('employers_list_'+area == item) $(item).show(); else $(item).hide();
		});
	},//end function




	//Выбор сотрудника из списка сотрудников
	selectEmployer: function(){
		if(!this.objects['table_employers'].selectedRows.length) return this.displayIRolesListArea('select');
		var tr =this.objects['table_employers'].selectedRows[0];
		if(typeOf(tr)!='element') return this.displayIRolesListArea('select');
		var data = tr.retrieve('data');
		if(typeOf(data)!='object')  return this.displayIRolesListArea('select');
		var employer_id = data['employer_id'];
		var iresource_id = $('iresource_selector').getValue();
		new axRequest({
			url : '/main/ajax/irowner',
			data:{
				'action':'employer.iroles',
				'employer_id': employer_id,
				'iresource_id': iresource_id
			},
			silent: true,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
				}else{
					App.pages[PAGE_NAME].displayIRolesListArea('none');
				}
			}
		}).request();
	},//end function




	//Выбор объекта доступа
	selectEmployerIRole: function(){
		if(!this.objects['table_employer_iroles'].selectedRows.length){
			$('objects_tool_lock').hide();
			$('objects_tool_none').show();
		}else{
			$('objects_tool_none').hide();
			$('objects_tool_lock').show();
		}
	},//end function



	//Фильтрация данных
	employerIRoleTableFilter: function(){
		var term = $('objects_filter').getValue();
		this.objects['table_employer_iroles'].clearSelected();
		this.objects['table_employer_iroles'].filter(term);
		this.selectEmployerIRole();
	},//end function




	//Выбор объекта доступа
	selectIRole: function(){
		if(!this.objects['table_iroles'].selectedRows.length) return this.displayEmployersListArea('select');
		var tr =this.objects['table_iroles'].selectedRows[0];
		if(typeOf(tr)!='element') return this.displayEmployersListArea('select');
		var data = tr.retrieve('data');
		if(typeOf(data)!='object') return this.displayEmployersListArea('select');
		var iresource_id = data['iresource_id'];
		var irole_id = data['irole_id'];
		new axRequest({
			url : '/main/ajax/irowner',
			data:{
				'action':'irole.employers',
				'iresource_id': iresource_id,
				'irole_id': irole_id
			},
			silent: true,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
				}else{
					App.pages[PAGE_NAME].displayEmployersListArea('none');
				}
			}
		}).request();
	},//end function



	//Фильтрация данных
	employerListTableFilter: function(){
		var term = $('employer_list_filter').getValue();
		this.objects['table_irole_employers'].clearSelected();
		this.objects['table_irole_employers'].filter(term);
	},//end function




	//Открытие окна блокировки доступа
	irLockerOpen: function(){
		if(!this.objects['table_employers'].selectedRows.length) return;
		if(!this.objects['table_employer_iroles'].selectedRows.length) return;
		var employer_info =this.objects['table_employers'].selectedRows[0].retrieve('data');

		$('bigblock_wrapper').hide();
		$$('.request_iresource_name').each(function(el){el.setValue(select_getText('iresource_selector'))});
		$$('.request_employer_name').each(function(el){el.setValue(employer_info['search_name'])});
		$$('.request_employer_company').each(function(el){el.setValue(employer_info['company_name'])});
		$$('.request_employer_post').each(function(el){el.setValue(employer_info['post_name'])});
		$$('.request_employer_phone').each(function(el){el.setValue(employer_info['phone'])});
		if(employer_info['email']){
			$$('.request_employer_email').each(function(el){el.setValue('<a class="mailto" href="mailto:'+employer_info['email']+'">'+employer_info['email']+'</a>')});
		}else{
			$$('.request_employer_email').each(function(el){el.setValue('-[Не задан]-')});
		}

		var iresource_id = $('iresource_selector').getValue();
		var tr, data, section_name=null, result=[], iroles=[];
		for(var i=0; i<this.objects['table_employer_iroles'].dataCells.length;i++){
			tr = this.objects['table_employer_iroles'].dataCells[i][0];
			data = tr.retrieve('data');
			if(tr.retrieve('is_section')){
				section_name = data;
			}else{
				if(!tr.retrieve('selected')) continue;
				if(section_name){
					result.push(section_name);
					section_name=null;
				}
				result.push(data);
				iroles.push(data['irole_id']);
			}
		}
		if(!iroles.length) return this.irLockerCancel();

		this.objects['table_objects_lock'].setData(result);

		this.objects['lock_info']={
			'action': 'request.lock',
			'iresource_id': iresource_id,
			'iroles': iroles,
			'employer_id': employer_info['employer_id'],
			'company_id': employer_info['company_id'],
			'post_uid': employer_info['post_uid']
		};

		$('ir_locker').show();
	},//end function




	//Закрытие окна блокировки доступа
	irLockerCancel: function(){
		$('ir_locker').hide();
		this.objects['lock_info']=null;
		$('bigblock_wrapper').show();
	},//end function




	//Блокировка доступа
	irLockerComplete: function(){
		if(typeOf(this.objects['lock_info'])!='object') return;
		new axRequest({
			url : '/main/ajax/irowner',
			data: this.objects['lock_info'],
			silent: false,
			display: 'hint',
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].irLockerSuccess(data);
				}
			}
		}).request();
	},//end function




	//Блокировка выполнена успешно
	irLockerSuccess: function(data){
		$$('.success_request_id').each(function(el){el.setValue(data['request_id'])});
		this.irLockerCancel();
		this.irLockerSuccessOpen();
	},//end function




	//Показ окна успешной блокировки
	irLockerSuccessOpen: function(){
		$('bigblock_wrapper').hide();
		$('ir_locker_success').show();
	},//end function




	//Закрытие окна окна успешной блокировки
	irLockerSuccessClose: function(){
		$('ir_locker_success').hide();
		$('bigblock_wrapper').show();
	},//end function



	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();