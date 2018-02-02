;(function(){
var PAGE_NAME = 'routes_add';
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

		$('route_add_preview_button').addEvent('click',this.anketPreview.bind(this));
		$('route_add_preview_back_button').addEvent('click',this.anketPreviewClose.bind(this));
		$('route_add_send_button').addEvent('click',this.anketSave.bind(this));
		$('route_add_profile_button').addEvent('click',this.routeProfile.bind(this));

		this.setData(data);

		//Построение анкеты
		this.objects['anketarea'] = build_blockitem({
			'parent': 'anket_area',
			'title'	: 'Введите сведения о маршруте'
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
			'title'	: 'Маршрут успешно добавлен'
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

		//Маршрут
		if(typeOf(data['route'])=='object'){
			this.objects['route'] = data['route'];
			var id;
			for(var key in data['route']){
				id = 'info_'+key+'_complete';
				if(!$(id))continue;
				$(id).set('text',data['route'][key]);
			}
		}//Маршрут

	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/








	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/


	//Предварительный просмотр
	anketPreview: function(){
		if(!this.objects['form_anket'].validate()) return;
		this.objects['anket'] = {
			'action': 'route.new',
			'full_name': $('info_full_name').getValue(),
			'description': $('info_description').getValue(),
			'route_type': $('info_route_type').getValue(),
			'is_lock': $('info_is_lock').getValue(),
			'is_default': $('info_is_default').getValue(),
			'priority': $('info_priority').getValue()
		};
		this.objects['preview'] = {
			'full_name': $('info_full_name').getValue(),
			'description': $('info_description').getValue(),
			'is_lock': ($('info_is_lock').checked ? 'Да' : 'Нет'),
			'is_default': ($('info_is_default').checked ? 'Да' : 'Нет'),
			'route_type': select_getText('info_route_type'),
			'priority': $('info_priority').getValue()
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
			url : '/admin/ajax/routes',
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



	//Переход в карточку маршрута
	routeProfile: function(){
		if(typeOf(this.objects['route'])!='object')return;
		App.Location.doPage({
			'href': '/admin/routes/info?route_id='+this.objects['route']['route_id'],
			'url': '/admin/routes/info',
			'data': {
				'route_id': this.objects['route']['route_id']
			},
			'method':'get'
		});
	},//end function


	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();