;(function(){
var PAGE_NAME = 'employers_list';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': ['table_employers'],
		'table_employers': null
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

		$('bigblock_expander').addEvent('click',this.fullscreen);
		$('filter_button').addEvent('click',this.filter);
		$('filter_search_name').addEvent('keydown',function(e){if(e.code==13) this.filter();}.bind(this));
		$('filter_status').addEvent('change',this.filter);

		this.setData(data);
	},//end function




	/*******************************************************************
	 * Обработка данных
	 ******************************************************************/

	//Обработка данных
	setData: function(data){
		var type;
		if(typeOf(data)!='object') return;

		//Установки фильтра
		if(typeOf(data['filter'])=='object'){
			for(var key in data['filter']){
				if($('filter_'+key)){
					$('filter_'+key).setValue(data['filter'][key]);
				}
			}
		}


		//Список сотрудников
		if(typeOf(data['employers_search'])=='array'){
			this.employersDataSet(data['employers_search']);
		}



	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/


	employersDataSet: function(data){
		if(!data.length){
			$('employers_table').hide();
			$('employers_none').show();
			return;
		}else{
			$('employers_none').hide();
			$('employers_table').show();
		}

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
						caption: 'Анкета',
						dataSource:'anket_id',
						width:100,
						sortable: false,
						dataStyle:{'text-align':'left'},
						dataFunction:function(table, cell, text, data){
							if(parseInt(text)>0) return '<a target="_blank" class="mailto" href="/admin/employers/anketinfo?anket_id='+data['anket_id']+'">Показать</a>';
							return 'Нет анкеты';
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






	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/

	//Развертывание окна на весь экран
	fullscreen: function(){
		var panel = $('page_employers_list');
		var title = $('bigblock_title').getParent('.titlebar');
		if(title.hasClass('expanded')){
			title.removeClass('expanded').addClass('normal');
			panel.inject($('adminarea')||$('mainarea'));
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
				'action':'employers.search',
				'search_name': $('filter_search_name').getValue(),
				'status': $('filter_status').getValue(),
				'extended': 'employers_list'
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