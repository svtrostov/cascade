;(function(){
var PAGE_NAME = 'iresources_groups';
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
		'igroups':null,
		'igroups_assoc':{}
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
		this.objects['igroups_splitter'] = set_splitter_h({
			'left'		: $('igroups_area'),
			'right'		: $('igroups_info'),
			'splitter'	: $('igroups_splitter'),
			'parent'	: $('igroups_splitter').getParent('.contentareafull')
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
					dataSource:'igroup_id',
					dataType: 'int'
				},
				{
					width:'auto',
					sortable:true,
					caption: 'Наименование группы',
					styles:{'min-width':'160px'},
					dataStyle:{'text-align':'left'},
					dataSource:'full_name'
				}
			],
			selectType:1
		};
		this.objects['table_list'] = new jsTable('igroups_table_area', settings);
		this.objects['table_list'].addEvent('click', this.selectItem.bind(this));

		this.objects['oinfo'] = build_blockitem({
			'parent': 'igroups_info_area',
			'title'	: 'Свойства группы ресурсов'
		});
		$('tmpl_igroup_info').show().inject(this.objects['oinfo']['container']);
		this.objects['oinfo']['li'].hide();
		$('button_delete_igroup').hide();
		$('button_delete_igroup').addEvent('click', this.deleteIGroup.bind(this));
		this.objects['form_edit'] = new jsValidator('tmpl_igroup_info');
		this.objects['form_edit'].required('oinfo_igroup_id').numeric('oinfo_igroup_id').required('oinfo_full_name').required('oinfo_short_name');
		$('button_change_igroup').addEvent('click', this.changeIGroup.bind(this));

		this.objects['onew'] = build_blockitem({
			'list': this.objects['oinfo']['list'],
			'title'	: 'Добавить группу ресурсов'
		});
		this.objects['onew']['li'].hide();
		$('tmpl_igroup_new').show().inject(this.objects['onew']['container']);
		this.objects['form_add'] = new jsValidator('tmpl_igroup_new');
		this.objects['form_add'].required('onew_full_name').required('onew_short_name');
		$('button_add_igroup').addEvent('click', this.addIGroup.bind(this));
		$('button_new_igroup').addEvent('click', this.newIGroup.bind(this));


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
		if(typeOf(data['iresource_groups'])=='array'){
			data['iresource_groups'].sort(function(a,b){if(a['full_name']>b['full_name'])return 1;return -1;});
			this.objects['igroups_assoc'] = {};
			this.objects['igroups'] = data['igroups'];
			for(var i=0; i<data['iresource_groups'].length;i++){
				this.objects['igroups_assoc'][data['iresource_groups'][i]['igroup_id']] = data['iresource_groups'][i]['full_name'];
			}
			this.objects['table_list'].setData(data['iresource_groups']);
		}//Типы доступа


		if(data['igroup_id']){
			this.objects['table_list'].selectOf([String(data['igroup_id'])],1);
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
		$('button_delete_igroup').hide();
		this.objects['oinfo']['li'].hide();
		this.objects['onew']['li'].hide();
		this.objects['sobject']=null;
		if(!this.objects['table_list'].selectedRows.length) return;
		var tr = this.objects['table_list'].selectedRows[0];
		if(typeOf(tr)!='element') return;
		var data = tr.retrieve('data');
		if(typeOf(data)!='object') return;
		$('oinfo_igroup_id').value = data['igroup_id'];
		$('oinfo_full_name').value = data['full_name'];
		$('oinfo_short_name').value = data['short_name'];
		this.objects['sobject']=data;
		this.objects['oinfo']['li'].show();
		$('button_delete_igroup').show();
	},//end function



	//Добавление - показ формы
	newIGroup: function(){
		if(!this.objects['table_list']) return;
		this.objects['table_list'].clearSelected();
		this.objects['sobject'] = null;
		this.objects['oinfo']['li'].hide();
		this.objects['onew']['li'].show();
		$('button_delete_igroup').hide();
	},//end function



	//Добавление - процесс
	addIGroup: function(){
		if(!this.objects['form_add'].validate()) return;
		new axRequest({
			url : '/admin/ajax/iresources',
			data:{
				'action':'igroup.new',
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
	changeIGroup: function(){
		if(typeOf(this.objects['sobject'])!='object') return;
		if(!this.objects['form_edit'].validate()) return;
		new axRequest({
			url : '/admin/ajax/iresources',
			data:{
				'action':'igroup.edit',
				'igroup_id': $('oinfo_igroup_id').value,
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
	deleteIGroup: function(){
		if(typeOf(this.objects['sobject'])!='object' || String(this.objects['sobject']['igroup_id']) != String($('oinfo_igroup_id').value)) return;
		var igroup_id = String($('oinfo_igroup_id').value);
		App.message(
			'Подтвердите действие',
			'Вы действительно хотите удалить выбранную группу информационных ресурсов?',
			'CONFIRM',
			function(){
				new axRequest({
					url : '/admin/ajax/iresources',
					data:{
						'action':'igroup.delete',
						'igroup_id': igroup_id
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
