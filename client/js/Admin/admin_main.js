;(function(){
var PAGE_NAME = 'admin_main';
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
		self.objects['tables'].each(function(table){
			if(self.objects[table]) self.objects[table].terminate();
		});
		for(var i in self.objects) self.objects[i] = null;
		self.objects = {};
	},//end function


	//Инициализация страницы
	start: function(data){


		//Область вывода заявок
		if(typeOf(data['requests'])=='array' && data['requests'].length > 0){
			this.objects['li_requests'] = build_blockitem({
				'list': 'right_dashboard_list',
				'title': 'Последние заявки'
			});
			this.objects['li_requests']['container'].setStyles({
				'padding': '0px',
				'margin': '0px',
				'background-color':'#b6bfce'
			});
		}



		//Область статистики
		this.objects['li_stats'] = build_blockitem({
			'list': 'right_dashboard_list',
			'title': 'Статистика'
		});
		this.objects['li_stats']['container'].setStyles({
			'padding': '0px',
			'margin': '0px',
			'background-color':'#a8b0bd'
		});
		$('tmpl_stats').inject(this.objects['li_stats']['container']).show();


		this.setData(data);
	},//end function




	/*******************************************************************
	 * Обработка данных
	 ******************************************************************/

	//Обработка данных
	setData: function(data){
		var type;
		if(typeOf(data)!='object') return;

		//Статистика
		if(typeOf(data['stats'])=='object'){
			for(var key in data['stats']){
				if($('stats_'+key)){
					$('stats_'+key).setValue(data['stats'][key]);
				}
			}
		}//Статистика


		//Заявки
		if(typeOf(data['requests'])=='array'){
			if(data['requests'].length > 0) this.requestsDataSet(data['requests']);
		}//Заявки


	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/


	requestsDataSet: function(data){
		if(!data.length) return;

		if(!this.objects['table_requests']){
			this.objects['table_requests'] = new jsTable(this.objects['li_requests']['container'],{
				//'dataBackground1':'#b6bfce',
				'dataBackground2':'#a8b0bd',
				'class': 'jsTableDashboard',
				columns:[
					{
						width:'30px',
						sortable:false,
						caption: '-',
						styles:{'min-width':'30px'},
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
						caption: 'Заявка',
						dataSource:'request_type',
						width:100,
						sortable: true,
						dataStyle:{'text-align':'left'},
						dataFunction:function(table, cell, text, data){
							var out = 'Заявка №'+data['request_id']+'<br>от: '+data['create_date'];
							switch(String(text)){
								case '3': out+='<br><font color="red">Блокировка</font>';break;
								case '2': out+='<br><font color="green">Добавление</font>';break;
							}
							return out;
						}
					},
					{
						caption: 'Заявитель',
						dataSource:'employer_name',
						width:'auto',
						sortable: true,
						dataStyle:{'text-align':'left'},
						dataFunction:function(table, cell, text, data){
							var result =  
							'<a target="_blank" class="mailto" href="/admin/employers/info?employer_id='+data['employer_id']+'">'+data['employer_name']+'</a>';
							return result+'<br>'+data['company_name']+'<br>'+data['post_name'];
						}
					},
					{
						caption: 'Куратор (инициатор)',
						dataSource:'curator_name',
						width:'auto',
						sortable: true,
						dataStyle:{'text-align':'left'},
						dataFunction:function(table, cell, text, data){
							return '<a target="_blank" class="mailto" href="/admin/employers/info?employer_id='+data['curator_id']+'">'+data['curator_name']+'</a>';
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


	//Фильтрация данных
	filter: function(){
		new axRequest({
			url : '/admin/ajax/requests',
			data:{
				'action':'requests.search',
				'search_term': $('filter_search_term').getValue(),
				'term_type': $('filter_search_term_type').getValue(),
				'status': $('filter_request_status').getValue(),
				'type': $('filter_request_type').getValue(),
				'company_id': $('filter_company_id').getValue(),
				'iresource_id': $('filter_iresource_id').getValue(),
				'route_id': $('filter_route_id').getValue(),
				'period': $('filter_period').getValue()
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



	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();