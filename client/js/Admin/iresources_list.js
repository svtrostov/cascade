;(function(){
var PAGE_NAME = 'iresources_list';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': ['table_iresources'],
		'table_iresources': null,
		//
		'groups': null,
		'companies': null,
		'igroups': null
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
		$('filter_iresource_status').addEvent('change',this.filter);
		$('filter_company_id').addEvent('change',this.filter);
		$('filter_igroup_id').addEvent('change',this.filter);


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


		//Группы ресурсов
		if(typeOf(data['iresource_groups'])=='array'){
			data['iresource_groups'].sort(function(a,b){if(a['full_name']>b['full_name'])return 1;return -1;});
			data['iresource_groups'].unshift({'igroup_id':'0','full_name':'-[Без группы]-'});
			this.objects['igroups'] = $unlink(data['iresource_groups']);
			data['iresource_groups'].unshift({'igroup_id':'all','full_name':'-[Все группы]-'});
			select_add({
				'list': 'filter_igroup_id',
				'key': 'igroup_id',
				'value': 'full_name',
				'options': data['iresource_groups'],
				'default': 'all',
				'clear': true
			});
		}


		//Группы
		if(typeOf(data['groups'])=='array'){
			this.objects['groups'] = data['groups'];
		}


		//Информационные ресурсы
		if(typeOf(data['iresources'])=='array'){
			this.iresourcesDataSet(data['iresources']);
		}


	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/


	iresourcesDataSet: function(data){
		if(!data.length){
			$('iresources_table_wrapper').hide();
			$('iresources_none').show();
			return;
		}else{
			$('iresources_none').hide();
			$('iresources_table_wrapper').show();
		}

		if(!this.objects['table_iresources']){
			this.objects['table_iresources'] = new jsTable('iresources_table',{
				'dataBackground1':'#efefef',
				columns:[
					{
						width:'40px',
						sortable:false,
						caption: '-',
						styles:{'min-width':'40px'},
						dataStyle:{'text-align':'center'},
						dataSource:'iresource_id',
						dataFunction:function(table, cell, text, data){
							new Element('img',{
								'src': INTERFACE_IMAGES+'/document_go.png',
								'styles':{
									'cursor':'pointer',
									'margin-left':'4px'
								},
								'events':{
									'click': function(e){
										App.Location.doPage('/admin/iresources/info?iresource_id='+text);
									}
								}
							}).inject(cell);
							return '';
						}
					},
					{
						caption: 'ID ресурса',
						sortable:true,
						dataSource:'iresource_id',
						width:60,
						dataStyle:{'text-align':'center'}
					},
					{
						width:220,
						sortable:true,
						caption: 'Информационный ресурс',
						styles:{'min-width':'150px'},
						dataStyle:{'text-align':'left'},
						dataSource:'full_name',
						dataFunction:function(table, cell, text, data){
							var result='';
							if(data['iresource_group']!='0'){
								result = App.pages[PAGE_NAME].objects['igroups'].filterResult('full_name', 'igroup_id', data['iresource_group']);
							}else{
								result = '-[Без группы]-';
							}
							if(result==''){
								result = '-[???? ID:'+data['iresource_group']+']-';
							}
							return data['full_name']+'<br><br>'+result;
						}
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
						caption: 'Владелец ресурса',
						dataSource:'post_name',
						width:300,
						styles:{'min-width':'200px'},
						dataStyle:{'text-align':'left'},
						dataFunction:function(table, cell, text, data){
							var result;
							if(data['company_id']=='0'){
								result = '-[Организация не задана]-';
							}else
							if(data['post_uid']=='0'){
								result = '<b>'+data['company_name']+'</b><br>'+'-[Должность не задана]-';
							}else{
								result = '<b>'+data['company_name']+'</b><br>'+data['post_name'];
								if(typeOf(data['employers'])=='array'&&data['employers'].length>0){
									for(var i=0; i<data['employers'].length; i++){
										result+= '<div style="margin-top:10px;"><a target="_blank" class="mailto" href="/admin/employers/info?employer_id='+data['employers'][i]['employer_id']+'">'+data['employers'][i]['employer_name']+'</a> (c '+data['employers'][i]['post_from']+')</div>';
									}
								}else{
									result+= '<div style="margin-top:10px;"><font color="red">-[Нет сотрудников]-</font></div>';
								}
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
						caption: 'Расположение',
						styles:{'min-width':'100px'},
						dataStyle:{'text-align':'left'},
						dataSource:'location'
					},
					{
						width:220,
						sortable:true,
						caption: 'Группа исполнителей',
						styles:{'min-width':'100px'},
						dataStyle:{'text-align':'left'},
						dataSource:'worker_group',
						dataFunction:function(table, cell, text, data){
							var result = '';
							if(text!='0'){
								result = App.pages[PAGE_NAME].objects['groups'].filterResult('full_name', 'group_id', text);
							}else{
								result = '-[Не заданы]-';
							}
							if(result==''){
								result = '-[???? ID:'+text+']-';
							}
							return result;
						}
					},
					{
						width:220,
						sortable:true,
						caption: 'Техническая информация ',
						styles:{'min-width':'100px'},
						dataStyle:{'text-align':'left'},
						dataSource:'techinfo'
					},
					{
						width:'40px',
						sortable:false,
						caption: '-',
						styles:{'min-width':'40px'},
						dataStyle:{'text-align':'center'},
						dataSource:'iresource_id',
						dataFunction:function(table, cell, text, data){
							new Element('img',{
								'src': INTERFACE_IMAGES+'/delete.png',
								'styles':{
									'cursor':'pointer',
									'margin-left':'4px'
								},
								'events':{
									'click': function(e){
										App.pages[PAGE_NAME].iresourceDelete(text);
									}
								}
							}).inject(cell);
							return '';
						}
					}
				]
			});
		}

		this.objects['table_iresources'].setData(data);
	},






	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/

	//Развертывание окна на весь экран
	fullscreen: function(normal_screen){
		var panel = $('page_iresources_list');
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
			url : '/admin/ajax/iresources',
			data:{
				'action':'iresources.search',
				'search_name': $('filter_search_name').getValue(),
				'iresource_status': $('filter_iresource_status').getValue(),
				'company_id': $('filter_company_id').getValue(),
				'igroup_id': $('filter_igroup_id').getValue()
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




	//Удаление выбранного ресурса
	iresourceDelete: function(iresource_id){
		App.message(
			'Подтвердите действие',
			'Вы действительно хотите удалить выбранный информационный ресурс?',
			'CONFIRM',
			function(){
				new axRequest({
					url : '/admin/ajax/iresources',
					data:{
						'action':'iresource.delete',
						'iresource_id': iresource_id,
						'search_name': $('filter_search_name').getValue(),
						'iresource_status': $('filter_iresource_status').getValue(),
						'company_id': $('filter_company_id').getValue(),
						'igroup_id': $('filter_igroup_id').getValue()
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