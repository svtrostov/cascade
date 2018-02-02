;(function(){
var PAGE_NAME = 'matrix_employers';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': ['table_employers'],
		'table_iroles': null,
		'table_employers':null,
		//
		'process_search':false,
		'employer_selected':null,
		'iresources':null,
		'iresources_assoc':{},
		'ir_types':null,
		'ir_types_assoc':{},
		'ir_list':{},
		'selected_items':null,
		'ir_results_count': 0,
		'ir_results': {},
		'posts': null,
		'selected_post': 0
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
		self.resultRemoveAllProcess();
		self.objects['tables'].each(function(table){
			if(self.objects[table]) self.objects[table].terminate();
		});
		for(var i in self.objects) self.objects[i] = null;
		self.objects = {};
	},//end function



	//Инициализация страницы
	start: function(data){

		$('bigblock_expander').addEvent('click',this.fullscreen.bind(this));

		$('employer_term').addEvent('keydown',function(e){if(e.code==13) App.pages[PAGE_NAME].employerSearch();});
		$('employer_term_button').addEvent('click',this.employerSearch.bind(this));
		this.objects['splitter'] = set_splitter_h({
			'left'		: $('employers_area'),
			'right'		: $('iresources_area'),
			'splitter'	: $('employers_splitter'),
			'parent'	: $('centralarea_container')
		});


		this.objects['table_employers'] = new jsTable('employers_table',{
			'dataBackground1':'#efefef',
			'class': 'jsTableLight',
			columns:[
				{
					caption: 'ID',
					sortable:false,
					width:'50px',
					dataSource:'employer_id',
					dataStyle:{'text-align':'center','min-width':'50px'}
				},
				{
					caption: 'Логин',
					dataSource:'username',
					width:100,
					sortable: false,
					dataStyle:{'text-align':'left','min-width':'100px'},
				},
				{
					caption: 'ФИО сотрудника',
					dataSource:'search_name',
					width:220,
					sortable: false,
					dataStyle:{'text-align':'left'},
					dataFunction:function(table, cell, text, data){
						return '<a target="_blank" class="mailto" href="/admin/employers/info?employer_id='+data['employer_id']+'">'+text+'</a>';
					}
				}
			]
		});
		this.objects['table_employers'].addEvent('click', this.selectEmployer.bind(this));

		$('objects_filter').addEvent('keydown',function(e){if(e.code==13) App.pages[PAGE_NAME].resultFilter();});
		$('post_selector').addEvent('change',function(){
			App.pages[PAGE_NAME].objects['selected_post'] = $('post_selector').getValue();
			App.pages[PAGE_NAME].selectEmployer(true);
		});

		$('object_lock_button').addEvent('click', this.lockAccess.bind(this));

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
			this.objects['iresources'] = data['iresources'];
			for(var i=0; i<data['iresources'].length;i++){
				this.objects['iresources_assoc'][data['iresources'][i]['iresource_id']] = data['iresources'][i]['full_name'];
			}
		}//Информационные ресурсы


		//Должности сотрудника
		if(typeOf(data['posts'])=='array'){
			data['posts'].sort(function(a,b){if(a['post_name']>b['post_name'])return 1;return -1;});
			data['posts'].unshift({
				'post_uid':'all',
				'post_name':'-[Все занимаемые должности]-',
				'company_name':'Во всех организациях'
			});

			select_add({
				'list': 'post_selector',
				'key': 'post_uid',
				'value': 'post_name',
				'options': data['posts'],
				'iterator': function(item){
					return {
						'key': item['post_uid'],
						'value': item['post_name']+' ('+item['company_name']+')'
					};
				},
				'default': this.objects['selected_post'],
				'clear': true
			});
		}//Должности сотрудника


		//Объекты доступа сотрудника
		if(typeOf(data['employer_iresources'])=='object'){
			for(var iresource_id in data['employer_iresources']){
				if(typeOf(data['employer_iresources'][iresource_id])=='array'&&data['employer_iresources'][iresource_id].length>0){
					this.resultAdd(iresource_id, data['employer_iresources'][iresource_id]);
				}
			}
			this.resultUpdateInterface();
		}//Объекты доступа сотрудника



	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/






	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/


	//Развертывание окна на весь экран
	fullscreen: function(normal_screen){
		var panel = $('page_matrix_employers');
		var title = $('bigblock_title').getParent('.titlebar');
		if(title.hasClass('expanded')||normal_screen==true){
			title.removeClass('expanded').addClass('normal');
			panel.inject($('adminarea'));
		}else{
			title.removeClass('normal').addClass('expanded');
			panel.inject(document.body);
		}
	},//end function



	//Поиск сотрудников
	employerSearch: function(){
		var tobj = $('employer_term');
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
				if(success){
					self['employer_selected'] = null;
					self.employersBuild(data['employers_search']);
				}
			}
		}).request();
	},//end function



	//Построение списка сотрудников, имеющих доступ к объекту ИР
	employersBuild: function(employers){
		if(typeOf(employers)!='array') return;
		if(employers.length > 0){
			$('employers_none').hide();
			employers.sort(function(a,b){if(a['search_name']>b['search_name'])return 1;return -1;});
			this.objects['table_employers'].setData(employers);
			$('employers_table').show();
		}else{
			$('employers_table').hide();
			$('employers_none').show();
		}
		this.objects['selected_post']=0;
		this.resultRemoveAllProcess();
	},//end function





	//Выбор сотрудника
	selectEmployer: function(no_get_posts){
		if(!this.objects['table_employers'].selectedRows.length){
			this.objects['selected_post']=0;
			return this.resultRemoveAllProcess();
		}
		var tr =this.objects['table_employers'].selectedRows[0];
		if(typeOf(tr)!='element'){
			this.objects['selected_post']=0;
			return this.resultRemoveAllProcess();
		}
		var data = tr.retrieve('data');
		if(typeOf(data)!='object'){
			this.objects['selected_post']=0;
			return this.resultRemoveAllProcess();
		}
		this.objects['employer_selected'] = data;
		var employer_id = data['employer_id'];

		new axRequest({
			url : '/admin/ajax/matrix',
			data:{
				'action':'employer.iresources',
				'employer_id': employer_id,
				'post_uid': (no_get_posts==true ? this.objects['selected_post'] : 0),
				'get_posts': (no_get_posts==true ? '0' : '1')
			},
			silent: true,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].resultRemoveAllProcess();
					if(no_get_posts!=true) App.pages[PAGE_NAME].objects['selected_post']=0;
					App.pages[PAGE_NAME].setData(data);
				}
			}
		}).request();
	},//end function




	//Построение результата
	employerIResourcesBuild: function(){
		this.resultRemoveAllProcess();
	},//end function




	//Обновление интерфейса: отображение/сокрытие элементов
	resultUpdateInterface: function(){
		if(this.objects['ir_results_count'] > 0){
			$('ir_none').hide();
			$('ir_list_none').hide();
			$('ir_area').show();
			$('ir_list').show();
			$('objects_filter_area').show();
		}else{
			if(this.objects['selected_post']==0){
				$('ir_area').hide();
				$('ir_none').show();
			}else{
				$('ir_list').hide();
				$('ir_list_none').show();
				$('objects_filter_area').hide();
			}
			$('objects_tool_lock').hide();
		}
	},//end function



	//Добавление ресурса в результат
	resultAdd: function(iresource_id, data){
		var title = (!this.objects['iresources_assoc'][iresource_id]? '-[??? ID:'+iresource_id+']-' : this.objects['iresources_assoc'][iresource_id]);
		var li = build_blockitem({
			'list': 'ir_list',
			'title'	: title
		});

		if(typeOf(this.objects['ir_results'][iresource_id])!='object') this.objects['ir_results'][iresource_id]={};

		this.objects['ir_results'][iresource_id]['item'] = li;
		li['container'].setStyles({
			'padding': '0px',
			'margin': '0px'
		});

		var selected_post = (this.objects['selected_post']!='all'&&this.objects['selected_post']!=0);

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
				}
			],
			'dataBackground1':'#efefef',
			selectType: (selected_post ? 2 : 1)
		});

		if(selected_post) this.objects['ir_results'][iresource_id]['table'].addEvent('click', this.selectIRole.bind(this));
		this.objects['ir_results'][iresource_id]['table'].setData(data);
		this.objects['ir_results_count']++;

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

		this.objects['ir_results'] = {};
		this.objects['ir_results_count']=0;
		$('ir_list').empty();
		this.resultUpdateInterface();
	},//end function



	//Фильтрация данных
	resultFilter: function(){

		var term = $('objects_filter').getValue();

		if(typeOf(this.objects['ir_results'])=='object'){
			for(var i in this.objects['ir_results']){
				if(typeOf(this.objects['ir_results'])!='object') continue;
				if(this.objects['ir_results'][i]['table']){
					this.objects['ir_results'][i]['table'].clearSelected();
					this.objects['ir_results'][i]['table'].filter(term);
				}
			}
		}

		$('objects_tool_lock').hide();

	},//end function



	//Выбор объекта доступа
	selectIRole: function(){

		var count = 0;
		if(this.objects['selected_post']=='all'||this.objects['selected_post']==0){
			$('objects_tool_lock').hide();
			return;
		}

		if(typeOf(this.objects['ir_results'])=='object'){
			for(var i in this.objects['ir_results']){
				if(typeOf(this.objects['ir_results'])!='object') continue;
				if(this.objects['ir_results'][i]['table']){
					if(!this.objects['ir_results'][i]['table'].selectedRows.length) continue;
					count++;
					break;
				}
			}
		}

		if(!count){
			$('objects_tool_lock').hide();
		}else{
			$('objects_tool_lock').show();
		}

	},//end function




	//Блокировка доступа: запрос
	lockAccess: function(){
		if(this.objects['selected_post']=='all'||this.objects['selected_post']==0||typeOf(this.objects['employer_selected'])!='object'){
			$('objects_tool_lock').hide();
			return;
		}

		App.message(
			'Подтвердите действие',
			'Вы действительно хотите оформить заявку на блокировку выбранных объектов доступа для текущего сотрудника?',
			'CONFIRM',
			function(){
				App.pages[PAGE_NAME].lockAccessProcess();
			}
		);

	},//end function




	//Блокировка доступа: процесс
	lockAccessProcess: function(){
		if(typeOf(this.objects['employer_selected'])!='object') return;
		if(this.objects['selected_post']=='all'||this.objects['selected_post']==0) return;

		var irole_id, ir_type, a=[], j, table, tr, data;

		if(typeOf(this.objects['ir_results'])=='object'){
			for(var i in this.objects['ir_results']){
				if(typeOf(this.objects['ir_results'])!='object') continue;
				table = this.objects['ir_results'][i]['table'];
				if(table){
					if(!table.selectedRows.length) continue;
					for(j=0; j<table.selectedRows.length; j++){
						tr = table.selectedRows[j];
						if(typeOf(tr)!='element') continue;
						data = tr.retrieve('data');
						if(typeOf(data)!='object') continue;
						a.push([data['iresource_id'],data['irole_id'],1]);
					}
				}
			}
		}

		if(a.length==0) return;

		new axRequest({
			url : '/admin/ajax/requests',
			data:{
				'action':'request.lock',
				'employer_id': this.objects['employer_selected']['employer_id'],
				'post_uid': this.objects['selected_post'],
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


	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();