;(function(){
var PAGE_NAME = 'templates_add';
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
		'companies': null,
		'post_selected': null,
		'posts': {},
		'companies':null,
		'orgchart':null,
		'template':null,
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
		$('template_add_preview_button').addEvent('click',this.anketPreview.bind(this));
		$('template_add_preview_back_button').addEvent('click',this.anketPreviewClose.bind(this));
		$('template_add_send_button').addEvent('click',this.anketSave.bind(this));
		$('template_add_profile_button').addEvent('click',this.templateProfile.bind(this));

		this.setData(data);

		this.selectorCancel();

		//Построение анкеты
		this.objects['anketarea'] = build_blockitem({
			'parent': 'anket_area',
			'title'	: 'Введите сведения о шаблоне'
		});
		$('tmpl_anket').show().inject(this.objects['anketarea']['container']);
		//Проверка формы
		this.objects['form_anket'] = new jsValidator(this.objects['anketarea']['container']);
		this.objects['form_anket'].required('info_full_name');

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
			'title'	: 'Шаблон успешно добавлен'
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


		//Информационные ресурсы
		if(typeOf(data['template'])=='object'){
			this.objects['template'] = data['template'];
			var id;
			for(var key in data['template']){
				id = 'info_'+key+'_complete';
				if(!$(id))continue;
				$(id).set('text',data['template'][key]);
			}
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
		if(!el || typeOf(el)!='element'){
			$('post_selector_complete_button').hide();
			this.objects['post_selected'] = null;
		}else{
			if(el.hasClass('noselect')){
				this.objects['post_selected'] = {
					'company_id': select_getValue('post_selector_companies_select'),
					'company_name': select_getText('post_selector_companies_select'),
					'post_uid': 0,
					'post_name': '-[Любая должность]-'
				};
			}else{
				this.objects['post_selected'] = {
					'company_id': el.retrieve('company_id'),
					'company_name': select_getText('post_selector_companies_select'),
					'post_uid': el.retrieve('post_uid'),
					'post_name': el.retrieve('full_name')
				};
			}
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
			var tof = typeOf(this.objects['post_selected']);
			var svalue = (tof=='object'&& this.objects['post_selected']['post_uid']!='0' ? this.objects['post_selected']['post_uid'] : null);
			this.objects['orgchart'].select('post_uid',svalue);
		}
		$('post_selector_complete_button').hide();
	},//end function



	//Предварительный просмотр
	anketPreview: function(){
		if(!this.objects['form_anket'].validate()) return;
		this.objects['anket'] = {
			'action': 'template.new',
			'full_name': $('info_full_name').getValue(),
			'description': $('info_description').getValue(),
			'is_lock': $('info_is_lock').getValue(),
			'is_for_new_employer': $('info_is_for_new_employer').getValue(),
			'company_id': (typeOf(this.objects['post_selected'])=='object' ? this.objects['post_selected']['company_id'] : 0),
			'post_uid': (typeOf(this.objects['post_selected'])=='object' ? this.objects['post_selected']['post_uid'] : 0)
		};
		this.objects['preview'] = {
			'full_name': $('info_full_name').getValue(),
			'description': $('info_description').getValue(),
			'is_lock': ($('info_is_lock').checked ? 'Да' : 'Нет'),
			'is_for_new_employer': ($('info_is_for_new_employer').checked ? 'Да' : 'Нет'),
			'company_id': (typeOf(this.objects['post_selected'])=='object' ? this.objects['post_selected']['company_name'] : '-[Любая организация]-'),
			'post_uid': (typeOf(this.objects['post_selected'])=='object' ? this.objects['post_selected']['post_name'] : '-[Любая должность]-')
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
			url : '/admin/ajax/templates',
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
	templateProfile: function(){
		if(typeOf(this.objects['template'])!='object')return;
		App.Location.doPage({
			'href': '/admin/templates/info?template_id='+this.objects['template']['template_id'],
			'url': '/admin/templates/info',
			'data': {
				'template_id': this.objects['template']['template_id']
			},
			'method':'get'
		});
	},//end function


	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();