;(function(){
var PAGE_NAME = 'iresources_irtypes';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': ['table_list'],
		'validators': ['form_add','form_edit'],
		'table_list': null,
		'form_add': null,
		'form_edit': null,
		//
		'ir_types':null,
		'ir_types_assoc':{}
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
		self.objects['validators'].each(function(validator){
			if(self.objects[validator]) self.objects[validator].destroy();
		});
		for(var i in self.objects) self.objects[i] = null;
		self.objects = {};
	},//end function


	//Инициализация страницы
	start: function(data){

		//Организации
		this.objects['irtypes_splitter'] = set_splitter_h({
			'left'		: $('irtypes_area'),
			'right'		: $('irtypes_info'),
			'splitter'	: $('irtypes_splitter'),
			'parent'	: $('irtypes_splitter').getParent('.contentareafull')
		});

		var settings = {
			'dataBackground1':'#efefef',
			'class': 'jsTableLight',
			columns: [
				{
					width:'50px',
					sortable:true,
					caption: 'ID',
					styles:{'min-width':'50px'},
					dataStyle:{'text-align':'center'},
					dataSource:'item_id',
					dataType: 'int'
				},
				{
					width:'auto',
					sortable:true,
					caption: 'Тип доступа',
					styles:{'min-width':'160px'},
					dataStyle:{'text-align':'left'},
					dataSource:'full_name'
				}
			],
			selectType:1
		};
		this.objects['table_list'] = new jsTable('irtypes_table_area', settings);
		this.objects['table_list'].addEvent('click', this.selectItem.bind(this));

		this.objects['oinfo'] = build_blockitem({
			'parent': 'irtypes_info_area',
			'title'	: 'Свойства типа доступа'
		});
		$('tmpl_irtype_info').show().inject(this.objects['oinfo']['container']);
		this.objects['oinfo']['li'].hide();
		$('button_delete_irtype').hide();
		$('button_delete_irtype').addEvent('click', this.deleteIRType.bind(this));
		this.objects['form_edit'] = new jsValidator('tmpl_irtype_info');
		this.objects['form_edit'].required('oinfo_item_id').numeric('oinfo_item_id').required('oinfo_full_name').required('oinfo_short_name');
		$('button_change_irtype').addEvent('click', this.changeIRType.bind(this));

		this.objects['onew'] = build_blockitem({
			'list': this.objects['oinfo']['list'],
			'title'	: 'Добавить тип доступа'
		});
		this.objects['onew']['li'].hide();
		$('tmpl_irtype_new').show().inject(this.objects['onew']['container']);
		this.objects['form_add'] = new jsValidator('tmpl_irtype_new');
		this.objects['form_add'].required('onew_full_name').required('onew_short_name');
		$('button_add_irtype').addEvent('click', this.addIRType.bind(this));
		$('button_new_irtype').addEvent('click', this.newIRType.bind(this));


		//Данные
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
			data['ir_types'].sort(function(a,b){if(a['full_name']>b['full_name'])return 1;return -1;});
			this.objects['ir_types_assoc'] = {};
			this.objects['ir_types'] = data['ir_types'];
			for(var i=0; i<data['ir_types'].length;i++){
				this.objects['ir_types_assoc'][data['ir_types'][i]['item_id']] = data['ir_types'][i]['full_name'];
			}
			this.objects['table_list'].setData(data['ir_types']);
		}//Типы доступа


		if(data['item_id']){
			this.objects['table_list'].selectOf([String(data['item_id'])],1);
		}


		this.selectItem();

	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/








	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/


	//Выбран элемент
	selectItem: function(){
		$('button_delete_irtype').hide();
		this.objects['oinfo']['li'].hide();
		this.objects['onew']['li'].hide();
		this.objects['sobject']=null;
		if(!this.objects['table_list'].selectedRows.length) return;
		var tr = this.objects['table_list'].selectedRows[0];
		if(typeOf(tr)!='element') return;
		var data = tr.retrieve('data');
		if(typeOf(data)!='object') return;
		$('oinfo_item_id').value = data['item_id'];
		$('oinfo_full_name').value = data['full_name'];
		$('oinfo_short_name').value = data['short_name'];
		this.objects['sobject']=data;
		this.objects['oinfo']['li'].show();
		$('button_delete_irtype').show();
	},//end function



	//Добавление - показ формы
	newIRType: function(){
		if(!this.objects['table_list']) return;
		this.objects['table_list'].clearSelected();
		this.objects['sobject'] = null;
		this.objects['oinfo']['li'].hide();
		this.objects['onew']['li'].show();
		$('button_delete_irtype').hide();
	},//end function



	//Добавление - процесс
	addIRType: function(){
		if(!this.objects['form_add'].validate()) return;
		new axRequest({
			url : '/admin/ajax/iresources',
			data:{
				'action':'irtype.new',
				'full_name': $('onew_full_name').value,
				'short_name': $('onew_short_name').value
			},
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
				}
			}
		}).request();
	},//end function



	//Изменение - процесс
	changeIRType: function(){
		if(typeOf(this.objects['sobject'])!='object') return;
		if(!this.objects['form_edit'].validate()) return;
		new axRequest({
			url : '/admin/ajax/iresources',
			data:{
				'action':'irtype.edit',
				'item_id': $('oinfo_item_id').value,
				'full_name': $('oinfo_full_name').value,
				'short_name': $('oinfo_short_name').value
			},
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
				}
			}
		}).request();
	},//end function



	//Удаление - процесс
	deleteIRType: function(){
		if(typeOf(this.objects['sobject'])!='object' || String(this.objects['sobject']['item_id']) != String($('oinfo_item_id').value)) return;
		var item_id = String($('oinfo_item_id').value);
		App.message(
			'Подтвердите действие',
			'Вы действительно хотите удалить выбранный тип доступа?',
			'CONFIRM',
			function(){
				new axRequest({
					url : '/admin/ajax/iresources',
					data:{
						'action':'irtype.delete',
						'item_id': item_id
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