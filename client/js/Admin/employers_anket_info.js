;(function(){
var PAGE_NAME = 'employers_anket_info';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		//таблицы jsTable
		'tables': ['table_employers'],
		'validators': ['form_anket'],
		'table_employers': null,
		'form_anket': null,
		//Вкладки
		'tabs': null,
		//anket_info
		'anket_info': null,
		'posts': {},
		'companies':null,
		'orgchart':null,
		'post_splitter':null,
		'employer': null
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
		if(self.objects['orgchart'])self.objects['orgchart'].empty();
		for(var i in self.objects) self.objects[i] = null;
		self.objects = {};
	},//end function


	//Инициализация страницы
	start: function(data){

		//Создание календарей
		new Picker.Date($$('.calendar_input'), {
			timePicker: false,
			positionOffset: {x: 0, y: 2},
			pickerClass: 'calendar',
			useFadeInOut: false
		});

		//Вкладки
		this.objects['tabs'] = new jsTabPanel('tabs_area',{
			'onchange': null
		});

		this.setData(data);

		if(typeOf(this.objects['anket_info'])!='object'){
			$('tabs_area').hide();
			$('tabs_none').show();
		}

		//Проверка формы
		this.objects['form_anket'] = new jsValidator('anket_form_area');
		this.objects['form_anket']
		.required('info_first_name').alpha('info_first_name')
		.required('info_last_name').alpha('info_last_name')
		.required('info_middle_name').alpha('info_middle_name')
		.required('info_birth_date').date('info_birth_date')
		.required('info_phone').phone('info_phone')
		.required('info_post_from').date('info_post_from')
		.email('info_email');

		$('change_post_button').addEvent('click',this.selectorOpen.bind(this));
		$('change_post_cancel_button').addEvent('click',this.selectorCancel.bind(this));
		$('post_selector_cancel_button').addEvent('click',this.selectorClose.bind(this));
		$('post_selector_complete_button').addEvent('click',this.selectorComplete.bind(this));
		$('anket_save_button').addEvent('click',this.anketSave.bind(this));
		$('anket_decline_button').addEvent('click',this.anketDecline.bind(this));
		$('anket_approve_button').addEvent('click',this.anketApprove.bind(this));
		$('employer_profile_button').addEvent('click',this.employerProfile.bind(this));
		$('employer_profile_button2').addEvent('click',this.employerProfile.bind(this));


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

	},//end function




	/*******************************************************************
	 * Обработка данных
	 ******************************************************************/

	//Обработка данных
	setData: function(data){
		var text;
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


		//Анкета
		if(typeOf(data['anket_info'])=='object'){
			var is_checked = String(data['anket_info']['anket_type'])!='1';
			this.objects['anket_info'] = data['anket_info'];
			for(var key in data['anket_info']){
				switch(key){
					case 'anket_type':
						switch(data['anket_info'][key]){
							case '1': text = 'Новая анкета'; break;
							case '2': text = '<font color="red">Рассмотрена и отклонена</font>'; break;
							case '3': text = '<font color="green">Рассмотрена и согласована</font>'; break;
							default: text = '-[?????]-';
						}
						$('info_anket_type').set('html',text);
					break;
					default:
						if($('info_'+key)){
							$('info_'+key).setValue(data['anket_info'][key]);
							if(is_checked){
								$('info_'+key).addClass('disabled').set('disabled',true).set('readonly',true);
							}else{
								$('info_'+key).removeClass('disabled').set('disabled',false).set('readonly',false);
							}
						}
				}
			}
			select_set('post_selector_companies_select', data['anket_info']['company_id']);
			$('posts_filter').value = data['anket_info']['post_name'];
			if(is_checked){
				$('approved_time_label').show();
				$('change_post_button').hide();
				$('anket_checked_form').hide();
				if(String(data['anket_info']['anket_type'])=='3'&& String(data['anket_info']['employer_id'])!='0'){
					$('employer_profile_button2').show();
					this.objects['employer'] = {
						'employer_id': data['anket_info']['employer_id']
					};
				}else{
					$('employer_profile_button2').hide();
				}
			}else{
				$('approved_time_label').hide();
			}
			this.selectorCancel();
		}//Анкета


		//Список сотрудников
		if(typeOf(data['employers_search'])=='array'){
			this.employersDataSet(data['employers_search']);
		}//Список сотрудников


		//Информация о созданном сотруднике
		if(typeOf(data['employer'])=='object'){
			this.objects['employer'] = data['employer'];
			var id;
			for(var key in data['employer']){
				id = 'info_'+key+'_complete';
				if(!$(id))continue;
				$(id).set('text',data['employer'][key]);
			}
			$('anket_form_area').hide();
			$('anket_complete_area').show();
		}//Информация о созданном сотруднике


	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/


	employersDataSet: function(data){
		if(!data.length){
			$('employers_table').hide();
			$('employers_none').show();
			$('tab_1').hide();
			return;
		}else{
			$('employers_none').hide();
			$('employers_table').show();
			$('tab_1').show();
		}

		$('tab_1').set('text','Похожие сотрудники ('+data.length+')')

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

	//Открытие окна выбора должности
	selectorOpen: function(){
		$('employer_anket_info_wrapper').hide();
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
		$('employer_anket_info_wrapper').show();
	},//end function


	//Выбрана должность
	selectorComplete: function(){
		if(typeOf(this.objects['post_selected'])=='object'){
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
			this.objects['orgchart'].select('post_uid',(typeOf(this.objects['post_selected'])=='object' ? this.objects['post_selected']['post_uid'] : this.objects['anket_info']['post_uid'] ));
		}
		$('post_selector_complete_button').hide();
	},//end function




	//Сохранение изменений в анкете
	anketSave: function(){
		if(!this.objects['form_anket'].validate()) return;
		this.anketAction(1);
	},//end function



	//Сохранение изменений в анкете и ее согласование
	anketDecline: function(){
		App.message(
			'Подтвердите действие',
			'Вы действительно хотите <font color="red"><b>отклонить</b></font> анкету нового сотрудника?',
			'CONFIRM',
			function(){
				App.pages[PAGE_NAME].anketAction(2);
			}
		);
	},//end function



	//Сохранение изменений в анкете и ее согласование
	anketApprove: function(){
		if(!this.objects['form_anket'].validate()) return;
		App.message(
			'Подтвердите действие',
			'Вы действительно хотите <font color="green"><b>согласовать</b></font> анкету нового сотрудника?',
			'CONFIRM',
			function(){
				App.pages[PAGE_NAME].anketAction(3);
			}
		);
	},//end function



	//Сохранение изменений в анкете
	anketAction: function(anket_type){
		if(typeOf(this.objects['anket_info'])!='object') return;
		if(String(this.objects['anket_info']['anket_type'])!='1') return;
		var anket = {
				'action':'employers.anket.save',
				'anket_id': this.objects['anket_info']['anket_id'],
				'anket_type': anket_type,
				'company_id': (typeOf(this.objects['post_selected'])=='object' ? this.objects['post_selected']['company_id'] : this.objects['anket_info']['company_id']),
				'post_uid': (typeOf(this.objects['post_selected'])=='object' ? this.objects['post_selected']['post_uid'] : this.objects['anket_info']['post_uid']),
				'first_name': $('info_first_name').getValue(),
				'last_name': $('info_last_name').getValue(),
				'middle_name': $('info_middle_name').getValue(),
				'order_no': $('info_order_no').getValue(),
				'post_from': $('info_post_from').getValue(),
				'birth_date': $('info_birth_date').getValue(),
				'phone': $('info_phone').getValue(),
				'email': $('info_email').getValue(),
				'work_computer': $('info_work_computer').getValue(),
				'need_accesscard': $('info_need_accesscard').getValue(),
				'comment': $('info_comment').getValue(),
				'template_post': $('template_post').getValue(),
				'template_new': $('template_new').getValue()
		};
		new axRequest({
			url : '/admin/ajax/employers',
			data: anket,
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
				}
			}
		}).request();
	},//end function


	//Переход в карточку сотрудника
	employerProfile: function(){
		if(typeOf(this.objects['employer'])!='object') return;
		App.Location.doPage({
			'href': '/admin/employers/info?employer_id='+this.objects['employer']['employer_id'],
			'url': '/admin/employers/info',
			'data': {
				'employer_id': this.objects['employer']['employer_id']
			},
			'method':'get'
		});
	},//end function

	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();