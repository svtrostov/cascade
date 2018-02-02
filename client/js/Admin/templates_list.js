;(function(){
var PAGE_NAME = 'templates_list';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': ['table_templates'],
		'table_templates': null,
		//
		'templates': null,
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

		$('bigblock_expander').addEvent('click',this.fullscreen);
		$('filter_button').addEvent('click',this.filter);
		$('filter_search_name').addEvent('keydown',function(e){if(e.code==13) this.filter();}.bind(this));
		$('filter_template_status').addEvent('change',this.filter);
		$('filter_template_type').addEvent('change',this.filter);
		$('filter_company_id').addEvent('change',this.filter);

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
			data['companies'].unshift({'company_id':'0','full_name':'-[Нет организации]-'});
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
		}


		//Шаблоны
		if(typeOf(data['templates'])=='array'){
			this.templatesDataSet(data['templates']);
		}


	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/


	templatesDataSet: function(data){
		if(!data.length){
			$('templates_table_wrapper').hide();
			$('templates_none').show();
			return;
		}else{
			$('templates_none').hide();
			$('templates_table_wrapper').show();
		}

		if(!this.objects['table_templates']){
			this.objects['table_templates'] = new jsTable('templates_table',{
				'dataBackground1':'#efefef',
				columns:[
					{
						width:'40px',
						sortable:false,
						caption: '-',
						styles:{'min-width':'40px'},
						dataStyle:{'text-align':'center'},
						dataSource:'template_id',
						dataFunction:function(table, cell, text, data){
							new Element('img',{
								'src': INTERFACE_IMAGES+'/document_go.png',
								'styles':{
									'cursor':'pointer',
									'margin-left':'4px'
								},
								'events':{
									'click': function(e){
										App.Location.doPage('/admin/templates/info?template_id='+text);
									}
								}
							}).inject(cell);
							return '';
						}
					},
					{
						caption: 'ID шаблона',
						sortable:true,
						dataSource:'template_id',
						width:60,
						dataStyle:{'text-align':'center'}
					},
					{
						width:220,
						sortable:true,
						caption: 'Наименование шаблона',
						styles:{'min-width':'150px'},
						dataStyle:{'text-align':'left'},
						dataSource:'full_name'
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
						caption: 'Применим для должности',
						dataSource:'post_name',
						width:300,
						styles:{'min-width':'200px'},
						dataStyle:{'text-align':'left'},
						dataFunction:function(table, cell, text, data){
							var result;
							if(data['company_id']=='0'){
								result = '-[Любая организация]-';
							}else
							if(data['post_uid']=='0'){
								result = '<b>'+data['company_name']+'</b><br>'+'-[Любая должность]-';
							}else{
								result = '<b>'+data['company_name']+'</b><br>'+data['post_name'];
							}
							return result;
						}
					},
					{
						width:220,
						sortable:true,
						caption: 'Описание',
						styles:{'min-width':'100px'},
						dataStyle:{'text-align':'left'},
						dataSource:'description'
					},
					{
						width:220,
						sortable:true,
						caption: 'Применимость',
						styles:{'min-width':'100px'},
						dataStyle:{'text-align':'left'},
						dataSource:'is_for_new_employer',
						dataFunction:function(table, cell, text, data){
							return (text!='0' ? 'Для новых сотрудников' : 'Для существующих сотрудников');
						}
					},
					{
						width:'40px',
						sortable:false,
						caption: '-',
						styles:{'min-width':'40px'},
						dataStyle:{'text-align':'center'},
						dataSource:'template_id',
						dataFunction:function(table, cell, text, data){
							new Element('img',{
								'src': INTERFACE_IMAGES+'/delete.png',
								'styles':{
									'cursor':'pointer',
									'margin-left':'4px'
								},
								'events':{
									'click': function(e){
										App.pages[PAGE_NAME].templateDelete(text);
									}
								}
							}).inject(cell);
							return '';
						}
					}
				]
			});
		}

		this.objects['table_templates'].setData(data);
	},






	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/

	//Развертывание окна на весь экран
	fullscreen: function(normal_screen){
		var panel = $('page_templates_list');
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
		new axRequest({
			url : '/admin/ajax/templates',
			data:{
				'action':'templates.search',
				'search_name': $('filter_search_name').getValue(),
				'status': $('filter_template_status').getValue(),
				'type': $('filter_template_type').getValue(),
				'company_id': $('filter_company_id').getValue(),
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




	//Удаление шаблона
	templateDelete: function(template_id){
		App.message(
			'Подтвердите действие',
			'Вы действительно хотите удалить выбранный шаблон?',
			'CONFIRM',
			function(){
				new axRequest({
					url : '/admin/ajax/templates',
					data:{
						'action':'template.delete',
						'template_id': template_id,
						'search_name': $('filter_search_name').getValue(),
						'status': $('filter_template_status').getValue(),
						'type': $('filter_template_type').getValue(),
						'company_id': $('filter_company_id').getValue()
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



	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();