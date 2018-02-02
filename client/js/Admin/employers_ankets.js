;(function(){
var PAGE_NAME = 'employers_ankets';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': ['table_ankets'],
		'table_ankets': null
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
		$('filter_anket_type').addEvent('change',this.filter);
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
			data['companies'].unshift({'company_id':'0','full_name':'-[Все организации]-'});
			select_add({
				'list': 'filter_company_id',
				'key': 'company_id',
				'value': 'full_name',
				'options': data['companies'],
				'default': 0,
				'clear': true
			});
		}

		//Установки фильтра
		if(typeOf(data['filter'])=='object'){
			for(var key in data['filter']){
				if($('filter_'+key)){
					$('filter_'+key).setValue(data['filter'][key]);
				}
			}
		}


		//Анкеты
		if(typeOf(data['ankets'])=='array'){
			this.anketsDataSet(data['ankets']);
		}



	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/


	anketsDataSet: function(data){
		if(!data.length){
			$('ankets_table').hide();
			$('ankets_none').show();
			return;
		}else{
			$('ankets_none').hide();
			$('ankets_table').show();
		}

		if(!this.objects['table_ankets']){
			this.objects['table_ankets'] = new jsTable('ankets_table',{
				'dataBackground1':'#efefef',
				columns:[
					{
						width:'40px',
						sortable:false,
						caption: '-',
						styles:{'min-width':'40px'},
						dataStyle:{'text-align':'center'},
						dataSource:'anket_id',
						dataFunction:function(table, cell, text, data){
							new Element('img',{
								'src': INTERFACE_IMAGES+'/document_go.png',
								'styles':{
									'cursor':'pointer',
									'margin-left':'4px'
								},
								'events':{
									'click': function(e){
										App.Location.doPage('/admin/employers/anketinfo?anket_id='+text);
									}
								}
							}).inject(cell);
							return '';
						}
					},
					{
						caption: 'ID анкеты',
						dataSource:'anket_id',
						width:60,
						dataStyle:{'text-align':'center'}
					},
					{
						caption: 'Дата создания',
						dataSource:'create_time',
						width:90,
						dataStyle:{'text-align':'center'}
					},
					{
						caption: 'Статус',
						dataSource:'anket_type',
						width:90,
						sortable: false,
						dataStyle:{'text-align':'center'},
						dataFunction:function(table, cell, text, data){
							switch(text){
								case '1': return '<font color="blue">Новая</font>';
								case '2': return '<font color="red">Отклонена</font>';
								case '3': return '<font color="green">Согласована</font>';
							}
							return '-[?????]-';
						}
					},
					{
						caption: 'ФИО сотрудника',
						dataSource:'employer_id',
						width:220,
						sortable: false,
						dataStyle:{'text-align':'left'},
						dataFunction:function(table, cell, text, data){
							var employer_name = data['last_name']+' '+data['first_name']+' '+data['middle_name'];
							var result =  
							(parseInt(text)> 0 ? '<a target="_blank" class="mailto" href="/admin/employers/info?employer_id='+data['employer_id']+'">'+employer_name+'</a>' : employer_name) + 
							'<br/><span class="small">Дата рождения: '+ data['birth_date']+'<br/>Tel: '+ data['phone']+'<br/>E-Mail: '+data['email']+'</span>';
							return result;
						}
					},
					{
						caption: 'Должность сотрудника',
						dataSource:'post_name',
						width:300,
						dataStyle:{'text-align':'left'},
						dataFunction:function(table, cell, text, data){
							return '<b>'+data['company_name']+'</b><br>'+data['post_name'];
						}
					},
					{
						caption: 'Компьютер',
						dataSource:'work_computer',
						sortable: false,
						width:80,
						dataStyle:{'text-align':'center'},
						dataFunction:function(table, cell, text, data){
							if(text=='1') return 'Да';
							return 'Нет';
						}
					},
					{
						caption: 'Пропуск',
						dataSource:'need_accesscard',
						sortable: false,
						width:80,
						dataStyle:{'text-align':'center'},
						dataFunction:function(table, cell, text, data){
							if(text=='1') return 'Да';
							return 'Нет';
						}
					},
					{
						caption: 'Коментарий',
						dataSource:'comment',
						width:350,
						dataStyle:{'text-align':'left'}
					},
					{
						caption: 'Инициатор',
						dataSource:'curator_name',
						width:180,
						sortable: false,
						dataStyle:{'text-align':'left'},
						dataFunction:function(table, cell, text, data){
							return '<a target="_blank" class="mailto" href="/admin/employers/info?employer_id='+data['curator_id']+'">'+text+'</a>';
						}
					}
				]
			});
		}

		this.objects['table_ankets'].setData(data);
	},






	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/

	//Развертывание окна на весь экран
	fullscreen: function(normal_screen){
		var panel = $('page_employers_ankets');
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
			url : '/admin/ajax/employers',
			data:{
				'action':'employers.ankets.list',
				'search_name': $('filter_search_name').getValue(),
				'anket_type': $('filter_anket_type').getValue(),
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





	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();