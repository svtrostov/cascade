;(function(){
var PAGE_NAME = 'request_complete';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': ['table_employers','table_iroles'],
		'table_employers':null,
		'table_iroles':null,
		//
		'iresources':null,
		'iresources_assoc':{},
		'ir_types':null,
		'ir_types_assoc':{},
		'ir_list': {},
		'ir_results':{}
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

		if(typeOf(data)!='object'||typeOf(data['iresources'])!='array'||!data['iresources'].length){
			$('request_wrapper').hide();
			$('request_none').show();
			return;
		}


		this.objects['splitter'] = set_splitter_h({
			'left'		: $('performers_area'),
			'right'		: $('iroles_area'),
			'splitter'	: $('splitter'),
			'parent'	: $('centralarea'),
			'min'		: 300
		});


		this.objects['li_employers'] = build_blockitem({
			'parent': 'performers_area_li',
			'title': 'Ответственные IT специалисты'
		});
		this.objects['li_employers']['container'].setStyles({
			'padding': '0px',
			'margin': '0px'
		});

		//Таблица сотрудников
		this.objects['table_employers'] = new jsTable(this.objects['li_employers']['container'],{
			'dataBackground1':'#efefef',
			'class': 'jsTableLight',
			columns:[
				{
					caption: 'Контакты сотрудников',
					dataSource:'search_name',
					width:'180px',
					sortable: false,
					dataStyle:{'text-align':'left','min-width':'150px'},
					dataFunction:function(table, cell, text, data){
						return '<b>'+data['search_name']+'</b>'+(data['phone'] || data['email'] ? ( data['phone'] ? '<br><i>Телефон:</i> '+data['phone'] : '')+(data['email'] ? '<br><i>E-mail:</i> <a class="mailto" href="mailto:'+data['email']+'">'+data['email']+'</a>':'') : '');
					}
				}
			]
		});


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
				caption: '№ заявки',
				dataSource:'request_id',
				width:80,
				sortable: false,
				dataStyle:{'text-align':'center','min-width':'80px'},
				dataFunction:function(table, cell, text, data){
					return '<a target="_blank" class="mailto" href="/main/requests/info?request_id='+data['request_id']+'&iresource_id='+data['iresource_id']+'">'+text+'</a>';
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



		this.objects['li_iroles'] = build_blockitem({
			'parent': 'iroles_area_li',
			'title'	: 'Ваши права доступа к информационному ресурсу'
		});
		this.objects['li_iroles']['container'].setStyles({
			'padding': '0px',
			'margin': '0px'
		});
		this.objects['table_iroles'] = new jsTable(this.objects['li_iroles']['container'],{
			'class': 'jsTableLight',
			sectionCollapsible:true,
			columns: table_columns,
			'dataBackground1':'#efefef',
			selectType: 2
		});
		//this.objects['table_iroles'].addEvent('click', this.selectEmployerIRole.bind(this));


		//Применение данных
		this.setData(data);
		$('iresource_selector').addEvent('change', this.changeIResource.bind(this));
		this.changeIResource();

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
			data['iresources'].sort(function(a,b){if(a['iresource_name']>b['iresource_name'])return 1;return -1;});
			this.objects['iresources'] = data['iresources'];
			for(var i=0; i<data['iresources'].length;i++){
				data['iresources'][i]['performers'].sort(function(a,b){if(a['search_name']>b['search_name'])return 1;return -1;});
				this.objects['iresources_assoc'][data['iresources'][i]['iresource_id']] = data['iresources'][i];
			}
			select_add({
				'list': 'iresource_selector',
				'key': 'iresource_id',
				'value': 'iresource_name',
				'options': data['iresources'],
				'default': (data['iresources'].length>0 ? data['iresources'][0]['iresource_id'] : '0'),
				'clear': true
			});
		}//Информационные ресурсы


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
		if(typeOf(this.objects['iresources_assoc'][iresource_id])!='object') return;
		this.objects['li_iroles']['title'].setValue('Права доступа в: '+this.objects['iresources_assoc'][iresource_id]['iresource_name']);
		this.objects['table_iroles'].setData(this.objects['iresources_assoc'][iresource_id]['roles']);
		if(!this.objects['iresources_assoc'][iresource_id]['performers'].length){
			$('performers_area_li').hide();
			$('performers_area_none').show();
		}else{
			$('performers_area_none').hide();
			this.objects['table_employers'].setData(this.objects['iresources_assoc'][iresource_id]['performers']);
			$('performers_area_li').show();
		}

	},//end function


	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();