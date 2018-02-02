;(function(){
var PAGE_NAME = 'routes_list';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': ['table_routes'],
		'table_routes': null,
		//
		'routes': null
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
		$('filter_route_status').addEvent('change',this.filter);
		$('filter_route_type').addEvent('change',this.filter);


		this.setData(data);
	},//end function




	/*******************************************************************
	 * Обработка данных
	 ******************************************************************/

	//Обработка данных
	setData: function(data){
		var type;
		if(typeOf(data)!='object') return;

		//Информационные ресурсы
		if(typeOf(data['routes'])=='array'){
			this.routesDataSet(data['routes']);
		}


	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/


	routesDataSet: function(data){
		if(!data.length){
			$('routes_table_wrapper').hide();
			$('routes_none').show();
			return;
		}else{
			$('routes_none').hide();
			$('routes_table_wrapper').show();
		}

		if(!this.objects['table_routes']){
			this.objects['table_routes'] = new jsTable('routes_table',{
				'dataBackground1':'#efefef',
				columns:[
					{
						width:'40px',
						sortable:false,
						caption: '-',
						styles:{'min-width':'40px'},
						dataStyle:{'text-align':'center'},
						dataSource:'route_id',
						dataFunction:function(table, cell, text, data){
							new Element('img',{
								'src': INTERFACE_IMAGES+'/document_go.png',
								'styles':{
									'cursor':'pointer',
									'margin-left':'4px'
								},
								'events':{
									'click': function(e){
										App.Location.doPage('/admin/routes/info?route_id='+text);
									}
								}
							}).inject(cell);
							return '';
						}
					},
					{
						caption: 'ID маршрута',
						sortable:true,
						dataSource:'route_id',
						width:60,
						dataStyle:{'text-align':'center'}
					},
					{
						width:220,
						sortable:true,
						caption: 'Наименование маршрута',
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
						width:220,
						sortable:true,
						caption: 'Описание',
						styles:{'min-width':'100px'},
						dataStyle:{'text-align':'left'},
						dataSource:'description'
					},
					{
						caption: 'Тип маршрута (применимость)',
						dataSource:'route_type',
						width:200,
						styles:{'min-width':'200px'},
						dataStyle:{'text-align':'left'},
						dataFunction:function(table, cell, text, data){
							switch(String(text)){
								case '1': return 'Для заявок сотрудников';
								case '2': return 'Для шаблонов должностей';
								case '3': return 'Для шаблонов на новых сотрудников';
								case '4': return 'Для заявок блокировки доступа';
								default: return '-[?????]-';
							}
						}
					},
					{
						width:100,
						sortable:true,
						caption: 'Приоритет',
						styles:{'min-width':'100px'},
						dataStyle:{'text-align':'center'},
						dataSource:'priority'
					},
					{
						width:100,
						sortable:true,
						caption: 'По-умолчанию',
						styles:{'min-width':'100px'},
						dataStyle:{'text-align':'center'},
						dataSource:'is_default',
						dataFunction:function(table, cell, text, data){
							return (text!='0' ? 'Да' : 'Нет');
						}
					},
					{
						width:'40px',
						sortable:false,
						caption: '-',
						styles:{'min-width':'40px'},
						dataStyle:{'text-align':'center'},
						dataSource:'route_id',
						dataFunction:function(table, cell, text, data){
							new Element('img',{
								'src': INTERFACE_IMAGES+'/delete.png',
								'styles':{
									'cursor':'pointer',
									'margin-left':'4px'
								},
								'events':{
									'click': function(e){
										App.pages[PAGE_NAME].routeDelete(text);
									}
								}
							}).inject(cell);
							return '';
						}
					}
				]
			});
		}

		this.objects['table_routes'].setData(data);
	},






	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/

	//Развертывание окна на весь экран
	fullscreen: function(normal_screen){
		var panel = $('page_routes_list');
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
			url : '/admin/ajax/routes',
			data:{
				'action':'routes.search',
				'search_name': $('filter_search_name').getValue(),
				'status': $('filter_route_status').getValue(),
				'route_type': $('filter_route_type').getValue()
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



	//Удаление выбранного маршрута
	routeDelete: function(route_id){
		App.message(
			'Подтвердите действие',
			'Вы действительно хотите удалить выбранный маршрут?',
			'CONFIRM',
			function(){
				new axRequest({
					url : '/admin/ajax/routes',
					data:{
						'action':'route.delete',
						'route_id': route_id,
						'search_name': $('filter_search_name').getValue(),
						'status': $('filter_route_status').getValue(),
						'route_type': $('filter_route_type').getValue()
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