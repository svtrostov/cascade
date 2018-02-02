;(function(){
var PAGE_NAME = 'iresources_add';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': [],
		'validators': ['form_anket'],
		'form_anket': null,
		//
		'groups': null,
		'igroups': null,
		'companies': null,
		'post_selected': null,
		'posts': {},
		'companies':null,
		'orgchart':null,
		'iresource':null,
		'post_splitter':null,
		'post_selected':null,
		'anket': null
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

		//$('company_select').addEvent('change',this.companyChange);
		this.objects['orgchart'] = new jsOrgChart('post_selector_org_structure_area', {dragAndDrop: false});
		this.objects['orgchart'].addEvents({
			'selectNode': this.postSelect.bind(this)
		});
		$('posts_filter').addEvent('keydown',function(e){if(e.code==13) App.pages[PAGE_NAME].postFilter();});
		$('posts_filter_button').addEvent('click',this.postFilter.bind(this));
		$('post_selector_companies_select').addEvent('change',this.postChangeCompany.bind(this));
		this.objects['post_splitter'] = set_splitter_h({
			'left'		: $('post_selector_companies_area'),
			'right'		: $('post_selector_org_structure'),
			'splitter'	: $('post_selector_splitter'),
			'parent'	: $('post_selector_wrapper')
		});
		$('change_post_button').addEvent('click',this.selectorOpen.bind(this));
		$('change_post_cancel_button').addEvent('click',this.selectorCancel.bind(this));
		$('post_selector_cancel_button').addEvent('click',this.selectorClose.bind(this));
		$('post_selector_complete_button').addEvent('click',this.selectorComplete.bind(this));
		$('iresource_add_preview_button').addEvent('click',this.anketPreview.bind(this));
		$('iresource_add_preview_back_button').addEvent('click',this.anketPreviewClose.bind(this));
		$('iresource_add_send_button').addEvent('click',this.anketSave.bind(this));
		$('iresource_add_profile_button').addEvent('click',this.iresourceProfile.bind(this));
		$('iresource_add_new_button').addEvent('click',this.iresourceAddNew.bind(this));

		this.setData(data);

		this.selectorCancel();

		//Построение анкеты
		this.objects['anketarea'] = build_blockitem({
			'parent': 'anket_area',
			'title'	: 'Введите сведения об информационном ресурсе'
		});
		$('tmpl_anket').show().inject(this.objects['anketarea']['container']);
		//Проверка формы
		this.objects['form_anket'] = new jsValidator(this.objects['anketarea']['container']);
		this.objects['form_anket'].required('info_full_name').required('info_short_name');

		//Построение предпросмотра
		this.objects['previewarea'] = build_blockitem({
			'parent': 'anket_area',
			'title'	: 'Предварительный просмотр'
		});
		this.objects['previewarea']['li'].hide();
		$('tmpl_preview').show().inject(this.objects['previewarea']['container']);


		//Построение ресультата
		this.objects['completearea'] = build_blockitem({
			'parent': 'anket_area',
			'title'	: 'Ресурс успешно добавлен'
		});
		this.objects['completearea']['li'].hide();
		$('tmpl_complete').show().inject(this.objects['completearea']['container']);


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
			this.objects['companies'] = data['companies'];
			select_add({
				'list': 'post_selector_companies_select',
				'key': 'company_id',
				'value': 'full_name',
				'options': data['companies'],
				'default': 0,
				'clear': true
			});
		}//Организации


		//Группы
		if(typeOf(data['groups'])=='array'){
			this.objects['groups'] = $unlink(data['groups']);
			data['groups'].unshift({'group_id':'0','full_name':'-[Нет исполнителей]-'});
			select_add({
				'list': 'info_worker_group',
				'key': 'group_id',
				'value': 'full_name',
				'options': data['groups'],
				'default': 0,
				'clear': true
			});
		}//Группы


		//Группы ресурсов
		if(typeOf(data['iresource_groups'])=='array'){
			data['iresource_groups'].sort(function(a,b){if(a['full_name']>b['full_name'])return 1;return -1;});
			data['iresource_groups'].unshift({'igroup_id':'0','full_name':'-[Без группы]-'});
			this.objects['igroups'] = $unlink(data['iresource_groups']);
			select_add({
				'list': 'info_igroup_id',
				'key': 'igroup_id',
				'value': 'full_name',
				'options': data['iresource_groups'],
				'default': 0,
				'clear': true
			});
		}


		//Информационные ресурсы
		if(typeOf(data['iresource'])=='object'){
			this.objects['iresource'] = data['iresource'];
			var id;
			for(var key in data['iresource']){
				id = 'info_'+key+'_complete';
				if(!$(id))continue;
				$(id).set('text',data['iresource'][key]);
			}
			$('info_igroup_id_complete').set('text',this.objects['preview']['igroup_id']);
		}//Информационные ресурсы


	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/








	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/


	//Открытие окна выбора должности
	selectorOpen: function(){
		$('anket_wrapper').hide();
		$('post_selector_complete_button').hide();
		$('post_selector').show();
		if(typeOf(this.objects['post_selected'])=='object'){
			select_set('post_selector_companies_select', this.objects['post_selected']['company_id']);
		}
		this.postChangeCompany();
	},//end function


	//Закрытие окна выбора должности
	selectorClose: function(){
		$('post_selector').hide();
		$('anket_wrapper').show();
	},//end function


	//Выбрана должность
	selectorComplete: function(){
		if(typeOf(this.objects['post_selected'])=='object'){
			$('selected_post_none').hide();
			$('selected_post_area').show();
			$('change_post_cancel_button').show();
			$('selected_company_name').setValue(this.objects['post_selected']['company_name']);
			$('selected_post_name').setValue(this.objects['post_selected']['post_name']);
		}
		this.selectorClose();
	},//end function


	//Отмена выбора должности
	selectorCancel: function(){
		$('selected_post_area').hide();
		$('selected_post_none').show();
		$('change_post_cancel_button').hide();
		this.objects['post_selected'] = null;
	},//end function



	//Выбор должности
	postSelect: function(el){
		if(!el || typeOf(el)!='element' || el.hasClass('noselect')){
			$('post_selector_complete_button').hide();
			this.objects['post_selected'] = null;
		}else{
			this.objects['post_selected'] = {
				'company_id': el.retrieve('company_id'),
				'company_name': select_getText('post_selector_companies_select'),
				'post_uid': el.retrieve('post_uid'),
				'post_name': el.retrieve('full_name')
			};
			$('post_selector_complete_button').show();
		}
	},//end function


	//Фильтр списка должностей
	postFilter: function(){
		this.objects['orgchart'].filter($('posts_filter').value);
	},//end function


	//Изменить организацию
	postChangeCompany: function(){
		var company_id = select_getValue('post_selector_companies_select');
		if(parseInt(company_id)<1) return;
		if(typeOf(this.objects['posts'][company_id])=='array'){
			this.setPosts(company_id, this.objects['posts'][company_id]);
			return;
		}
		new axRequest({
			url : '/admin/ajax/org',
			data:{
				'action':'org.structure.load',
				'company_id': company_id
			},
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].objects['posts'][data['company_id']] = data['org_data'];
					App.pages[PAGE_NAME].setPosts(data['company_id'], data['org_data']);
				}
			}
		}).request();
	},//end function



	//Применение списка должностей
	setPosts: function(company_id, data){
		if(typeOf(this.objects['companies'])=='array'){
			var company_name = this.objects['companies'].filterResult('full_name','company_id',company_id);
			$('post_selector_title').set('text','Выберите должность в организации '+company_name);
			this.objects['orgchart'].setData(company_name, data);
			this.objects['orgchart'].select('post_uid',(typeOf(this.objects['post_selected'])=='object' ? this.objects['post_selected']['post_uid'] : null ));
		}
		$('post_selector_complete_button').hide();
	},//end function



	//Предварительный просмотр
	anketPreview: function(){
		if(!this.objects['form_anket'].validate()) return;
		this.objects['anket'] = {
			'action': 'iresource.new',
			'full_name': $('info_full_name').getValue(),
			'short_name': $('info_short_name').getValue(),
			'description': $('info_description').getValue(),
			'location': $('info_location').getValue(),
			'techinfo': $('info_techinfo').getValue(),
			'igroup_id': $('info_igroup_id').getValue(),
			'is_lock': $('info_is_lock').getValue(),
			'company_id': (typeOf(this.objects['post_selected'])=='object' ? this.objects['post_selected']['company_id'] : 0),
			'post_uid': (typeOf(this.objects['post_selected'])=='object' ? this.objects['post_selected']['post_uid'] : 0),
			'worker_group': $('info_worker_group').getValue()
		};
		this.objects['preview'] = {
			'full_name': $('info_full_name').getValue(),
			'short_name': $('info_short_name').getValue(),
			'description': $('info_description').getValue(),
			'location': $('info_location').getValue(),
			'techinfo': $('info_techinfo').getValue(),
			'igroup_id': select_getText('info_igroup_id'),
			'is_lock': ($('info_is_lock').checked ? 'Да' : 'Нет'),
			'company_id': (typeOf(this.objects['post_selected'])=='object' ? this.objects['post_selected']['company_name'] : '-[Не выбран]-'),
			'post_uid': (typeOf(this.objects['post_selected'])=='object' ? this.objects['post_selected']['post_name'] : '-[Не выбран]-'),
			'worker_group': select_getText('info_worker_group')
		};

		var id;
		for(var key in this.objects['preview']){
			id = 'info_'+key+'_preview';
			if(!$(id))continue;
			$(id).set('text',this.objects['preview'][key]);
		}

		this.objects['anketarea']['li'].hide();
		this.objects['previewarea']['li'].show();
	},//end function



	//Закрыть предварительный просмотр
	anketPreviewClose: function(){
		this.objects['anketarea']['li'].show();
		this.objects['previewarea']['li'].hide();
	},//end function



	//Сохранение
	anketSave: function(){
		if(typeOf(this.objects['anket'])!='object') return;
		new axRequest({
			url : '/admin/ajax/iresources',
			data: this.objects['anket'],
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
					App.pages[PAGE_NAME].anketComplete();
				}
			}
		}).request();
	},//end function



	//Выполнено успешно
	anketComplete: function(){
		this.objects['previewarea']['li'].hide();
		this.objects['completearea']['li'].show();
	},//end function



	//Переход в карточку ресурса
	iresourceProfile: function(){
		if(typeOf(this.objects['iresource'])!='object')return;
		App.Location.doPage({
			'href': '/admin/iresources/info?iresource_id='+this.objects['iresource']['iresource_id'],
			'url': '/admin/iresources/info',
			'data': {
				'iresource_id': this.objects['iresource']['iresource_id']
			},
			'method':'get'
		});
	},//end function


	//Добавление еще одного ресурса
	iresourceAddNew: function(){
		App.Location.doPage({
			'href': '/admin/iresources/add',
			'url': '/admin/iresources/add',
			'data': {
			},
			'method':'get'
		});
	},//end function

	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();