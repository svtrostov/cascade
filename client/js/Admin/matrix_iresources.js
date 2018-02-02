;(function(){
var PAGE_NAME = 'matrix_iresources';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': ['table_iroles','table_employers'],
		'table_iroles': null,
		'table_employers':null,
		//
		'iresources':null,
		'ir_types':null,
		'ir_types_assoc':{},
		'ir_list':{},
		'employers':{}
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

		$('iresource_selector').addEvent('change',this.changeIResource.bind(this));
		this.objects['splitter'] = set_splitter_h({
			'left'		: $('iroles_area'),
			'right'		: $('employers_area'),
			'splitter'	: $('iroles_splitter'),
			'parent'	: $('centralarea_container')
		});


		this.objects['table_iroles'] = new jsTable('iroles_table', {
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
					width:'150px',
					caption: 'Объект доступа',
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



		this.objects['table_employers'] = new jsTable('employers_table',{
			'dataBackground1':'#efefef',
			'class': 'jsTableLight',
			columns:[
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
					caption: 'ФИО сотрудника',
					dataSource:'employer_name',
					width:220,
					sortable: false,
					dataStyle:{'text-align':'left'},
					dataFunction:function(table, cell, text, data){
						return '<a target="_blank" class="mailto" href="/admin/employers/info?employer_id='+data['employer_id']+'">'+text+'</a>';
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
					caption: '№ заявки',
					dataSource:'request_id',
					width:80,
					sortable: false,
					dataStyle:{'text-align':'center','min-width':'80px'},
					dataFunction:function(table, cell, text, data){
						return '<a target="_blank" class="mailto" href="/admin/requests/info?request_id='+data['request_id']+'&iresource_id='+data['iresource_id']+'">'+text+'</a>';
					}
				},
				{
					caption: 'Доступ с даты',
					dataSource:'timestamp',
					width:100,
					sortable: false,
					dataStyle:{'text-align':'center','min-width':'100px'},
				}
			]
		});


		$('iroles_filter').addEvent('keydown',function(e){if(e.code==13) App.pages[PAGE_NAME].irolesFilter();});
		$('iroles_filter_button').addEvent('click',this.irolesFilter.bind(this));

		$('employers_filter').addEvent('keydown',function(e){if(e.code==13) App.pages[PAGE_NAME].employersFilter();});
		$('employers_filter_button').addEvent('click',this.employersFilter.bind(this));

		//Применение данных
		this.setData(data);

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


		//Выбран информационный ресурс
		if(data['selected_iresource']){
			select_set('iresource_selector',data['selected_iresource']);
			this.objects['iresources'] = $unlink(data['iresources']);
			selected_iresource = true;
		}//Выбран информационный ресурс


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


	//Развертывание окна на весь экран
	fullscreen: function(normal_screen){
		var panel = $('page_matrix_iresources');
		var title = $('bigblock_title').getParent('.titlebar');
		if(title.hasClass('expanded')||normal_screen==true){
			title.removeClass('expanded').addClass('normal');
			panel.inject($('adminarea'));
		}else{
			title.removeClass('normal').addClass('expanded');
			panel.inject(document.body);
		}
	},//end function




	//Выбор информационного ресурса
	changeIResource: function(iresource_id){
		iresource_id = parseInt(iresource_id);
		if(iresource_id){
			select_set('iresource_selector', iresource_id);
		}else{
			iresource_id = $('iresource_selector').getValue();
		}
		if(!iresource_id) return false;
		this.objects['selected_resource_id'] = iresource_id;
		this.objects['selected_items'] = {};
		//Объекты выбранного ИР еще не закешированы
		if(typeOf(this.objects['ir_list'][iresource_id])!='object'){
			$('iresource_selector').disable();
			new axRequest({
				url : '/admin/ajax/iresources',
				data:{
					'action':'iresource.roles',
					'iresource_id': iresource_id
				},
				silent: true,
				waiter: true,
				callback: function(success, status, data){
					$('iresource_selector').enable();
					if(success){
						if(typeOf(data)!='object'||typeOf(data['iroles'])!='object'||typeOf(data['iroles']['items'])!='array') return;
						App.pages[PAGE_NAME].objects['ir_list'][iresource_id] = data['iroles'];
						App.pages[PAGE_NAME].iRolesBuild(iresource_id);
					}
				}
			}).request();
		}
		//Объекты выбранного ИР находятся в кеше
		else{
			this.iRolesBuild(iresource_id);
		}

	},//end function




	//Построение списка объектов доступа для выбранного ИР
	iRolesBuild: function(iresource_id){
		if(typeOf(this.objects['ir_list'][iresource_id])!='object'||typeOf(this.objects['ir_list'][iresource_id]['items'])!='array'){
			return App.message(
				'Ошибка JavaScript',
				'Массив объектов доступа для информационного ресурса задан некорректно.<br/><br/>'+
				'Свяжитесь с администратором для разрешения данной ситуации.',
				'error'
			);
		}
		if(this.objects['ir_list'][iresource_id]['items'].length > 0){
			this.objects['table_iroles'].setData(this.objects['ir_list'][iresource_id]['items']);
			$('employers_table_wrapper').hide();
			$('employers_none').show();
		}
	},//end function




	//Выбор объекта доступа
	selectIRole: function(){
		$('employers_table_wrapper').hide();
		$('employers_none').show();
		if(!this.objects['table_iroles'].selectedRows.length) return;
		var tr =this.objects['table_iroles'].selectedRows[0];
		if(typeOf(tr)!='element') return;
		var data = tr.retrieve('data');
		if(typeOf(data)!='object') return;

		var iresource_id = data['iresource_id'];
		var irole_id = data['irole_id'];
		if(typeOf(this.objects['ir_list'][iresource_id])!='object') return;
		var employers = iresource_id+'-'+irole_id;

		//Объекты выбранного ИР еще не закешированы
		if(typeOf(this.objects['employers'][employers])!='array'){
			$('iresource_selector').disable();
			new axRequest({
				url : '/admin/ajax/matrix',
				data:{
					'action':'irole.employers',
					'iresource_id': iresource_id,
					'irole_id': irole_id
				},
				silent: true,
				waiter: true,
				callback: function(success, status, data){
					$('iresource_selector').enable();
					if(success){
						if(typeOf(data)!='object'||typeOf(data['employers'])!='array') return;
						data['employers'].sort(function(a,b){if(a['employer_name']>b['employer_name'])return 1;return -1;});
						App.pages[PAGE_NAME].objects['employers'][employers] = data['employers'];
						App.pages[PAGE_NAME].employersBuild(employers);
					}
				}
			}).request();
		}
		//Объекты выбранного ИР находятся в кеше
		else{
			this.employersBuild(employers);
		}
	},//end function




	//Построение списка сотрудников, имеющих доступ к объекту ИР
	employersBuild: function(employers){
		if(typeOf(this.objects['employers'][employers])!='array') return;
		if(this.objects['employers'][employers].length > 0){
			$('employers_none').hide();
			this.objects['table_employers'].setData(this.objects['employers'][employers]);
			$('employers_table_wrapper').show();
		}else{
			$('employers_table_wrapper').hide();
			$('employers_none').show();
		}
	},//end function




	//Фильтр данных таблицы объектов доступа
	irolesFilter: function(){
		this.objects['table_iroles'].filter($('iroles_filter').value);
	},//end function



	//Фильтр данных таблицы сотрудников
	employersFilter: function(){
		this.objects['table_employers'].filter($('employers_filter').value);
	},//end function



	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();