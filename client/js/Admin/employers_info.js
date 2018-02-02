var employers_info_objects = {};

//Вход на страницу
function employers_info_enter_page(success, status, data){
	employers_info_start(data);
}//end function


//Выход со страницы
function employers_info_exit_page(){
	['certtable','employer_groups_table','all_groups_table','posts_table','assistants_table','delegates_table','assistants_history_table','all_right_table','employer_right_table'].each(function(table,index){
		if(employers_info_objects[table]) employers_info_objects[table].terminate();
	});
	['form_profile_info','form_profile_username','form_profile_pincode','form_profile_access'].each(function(form,index){
		if(employers_info_objects[form]) employers_info_objects[form].destroy();
	});
	if(employers_info_objects['orgchart'])employers_info_objects['orgchart'].empty();
	for(var i in employers_info_objects){
		employers_info_objects[i] = null;
	}
	employers_info_objects = {};
	App.Location.removeEvent('beforeLoadPage', employers_info_exit_page);
}//end function



//Инициализация процесса
function employers_info_start(data){
	App.Location.addEvent('beforeLoadPage', employers_info_exit_page);

	employers_info_objects['employer_info'] = null;
	employers_info_objects['employer_groups'] = null;
	employers_info_objects['employer_posts'] = null;
	employers_info_objects['employer_assistants'] = null;
	employers_info_objects['employer_delegates'] = null;
	employers_info_objects['employer_rights'] = null;
	employers_info_objects['companies_array'] = null;
	employers_info_objects['companies_assoc'] = null;
	employers_info_objects['groups_assoc'] = null;
	employers_info_objects['groups_array'] = null;
	employers_info_objects['selected_assistant'] = null;
	employers_info_objects['org_structure'] = {};
	employers_info_objects['orgchart'] = new jsOrgChart('post_selector_org_structure_area', {
		dragAndDrop: false
	});
	employers_info_objects['orgchart'].addEvents({
		'selectNode': employers_info_post_post_select
	});
	$('posts_filter').addEvent('keydown',function(e){if(e.code==13) employers_info_post_filter();});
	$('posts_filter_button').addEvent('click',employers_info_post_filter);


	//Создание календарей
	new Picker.Date($$('.calendar_input'), {
		timePicker: false,
		positionOffset: {x: 0, y: 2},
		pickerClass: 'calendar',
		useFadeInOut: false
	});

	employers_info_objects['tabs'] = new jsTabPanel('tabs_area',{
		'onchange': employers_info_change_tab
	});


	//Профиль
	employers_info_objects['profile_slideshow'] = new jsSlideShow('profile_step_container');
	['profile_info_button','profile_notice_button','profile_account_button','profile_certificate_button'].each(function(item){
		$(item).addEvent('click',employers_info_profile_step_button_click);
	});

	employers_info_objects['profile_info'] = build_blockitem({
		'parent': 'profile_step_info',
		'title'	: 'Карточка сотрудника'
	});
	$('tmpl_profile_info').show().inject(employers_info_objects['profile_info']['container']);
	employers_info_objects['form_profile_info'] = new jsValidator('tmpl_profile_info');
	employers_info_objects['form_profile_info']
	.required('info_first_name').alpha('info_first_name')
	.required('info_last_name').alpha('info_last_name')
	.required('info_middle_name').alpha('info_middle_name')
	.required('info_birth_date').date('info_birth_date')
	.required('info_phone').phone('info_phone')
	.email('info_email');

	employers_info_objects['form_profile_username'] = new jsValidator('tmpl_profile_account_username');
	employers_info_objects['form_profile_username'].required('info_username').username('info_username');

	employers_info_objects['form_profile_password'] = new jsValidator('tmpl_profile_account_password');
	employers_info_objects['form_profile_password'].required('info_password').password('info_password');

	employers_info_objects['form_profile_pincode'] = new jsValidator('tmpl_profile_account_pincode');
	employers_info_objects['form_profile_pincode'].username('info_pin_code');

	employers_info_objects['form_profile_access'] = new jsValidator('tmpl_profile_account_access');
	employers_info_objects['form_profile_access'].required('info_access_level').numeric('info_access_level').minValue('info_access_level',0);

	employers_info_objects['profile_notice'] = build_blockitem({
		'parent':  'profile_step_notice',
		'title'	: 'Уведомления по электронной почте'
	});
	$('tmpl_notice_info').show().inject(employers_info_objects['profile_notice']['container']);

	employers_info_objects['profile_account_access'] = build_blockitem({
		'parent': 'profile_step_account',
		'title'	: 'Статус учетной записи и уровень доступа'
	});
	$('tmpl_profile_account_access').show().inject(employers_info_objects['profile_account_access']['container']);

	employers_info_objects['profile_account_username'] = build_blockitem({
		'parent': 'profile_step_account',
		'title'	: 'Изменить имя пользователя'
	});
	$('tmpl_profile_account_username').show().inject(employers_info_objects['profile_account_username']['container']);

	employers_info_objects['profile_account_password'] = build_blockitem({
		'parent': 'profile_step_account',
		'title'	: 'Изменить пароль'
	});
	$('tmpl_profile_account_password').show().inject(employers_info_objects['profile_account_password']['container']);

	employers_info_objects['profile_account_pincode'] = build_blockitem({
		'parent': 'profile_step_account',
		'title'	: 'PIN-код'
	});
	$('tmpl_profile_account_pincode').show().inject(employers_info_objects['profile_account_pincode']['container']);

	employers_info_objects['profile_account_print'] = build_blockitem({
		'parent': 'profile_step_account',
		'title'	: 'Печать учетных данных'
	});
	$('tmpl_profile_account_print').show().inject(employers_info_objects['profile_account_print']['container']);


	employers_info_objects['profile_certificate_upload'] = build_blockitem({
		'parent': 'profile_step_certificate',
		'title'	: 'Регистрация нового сертификата'
	});
	$('tmpl_profile_certificate_upload').show().inject(employers_info_objects['profile_certificate_upload']['container']);


	employers_info_objects['profile_certificate'] = build_blockitem({
		'parent': 'profile_step_certificate',
		'title'	: 'Зарегистрированные сертификаты'
	});
	$('tmpl_profile_certificate').show().inject(employers_info_objects['profile_certificate']['container']);


	employers_info_objects['profile_certificate']['container'].setStyles({
		'padding': '0px',
		'margin': '0px'
	});

	employers_info_objects['certtable'] = new jsTable('tmpl_profile_certificate', {
		'dataBackground1':'#efefef',
		'class': 'jsTableLight',
		columns: [
			{
				width:'40px',
				sortable:false,
				caption: '-',
				styles:{'min-width':'40px'},
				dataStyle:{'text-align':'center'},
				dataSource:'SSL_CERT_HASH',
				dataFunction:function(table, cell, text, data){
					new Element('img',{
						'src': INTERFACE_IMAGES+'/save.png',
						'styles':{
							'cursor':'pointer',
							'margin-left':'4px'
						},
						'events':{
							'click': function(e){
								App.Loader.downloadFile('/admin/customcontent/certificate?custom=1&employer_id='+employers_info_objects['employer_info']['employer_id']+'&sha1='+text);
							}
						}
					}).inject(cell);
					new Element('img',{
						'src': INTERFACE_IMAGES+'/delete.png',
						'styles':{
							'cursor':'pointer',
							'margin-left':'4px'
						},
						'events':{
							'click': function(e){
								App.message(
									'Подтвердите действие',
									'Вы действительно хотите удалить сертификат?<br/>SHA1=['+text+']',
									'CONFIRM',
									function(){
										new axRequest({
											url : '/admin/ajax/employers',
											data:{
												'action':'employers.certificate.delete',
												'employer_id': employers_info_objects['employer_info']['employer_id'],
												'sha1': text
											},
											silent: false,
											waiter: true,
											callback: function(success, status, data){
												if(success){
													employers_info_dataset(data);
												}
											}
										}).request();
									}
								);
							}
						}
					}).inject(cell);
					return '';
				}
			},
			{
				width:'200px',
				sortable:true,
				caption: 'SHA1',
				styles:{'min-width':'200px'},
				dataStyle:{'text-align':'left'},
				dataSource:'SSL_CERT_HASH'
			},
			{
				width:'120px',
				sortable:true,
				caption: 'Серийный номер',
				styles:{'min-width':'120px'},
				dataStyle:{'text-align':'center'},
				dataSource:'SSL_CLIENT_M_SERIAL',
				dataType: 'num'
			},
			{
				width:'120px',
				sortable:true,
				caption: 'L',
				styles:{'min-width':'120px'},
				dataStyle:{'text-align':'left'},
				dataSource:'SSL_CLIENT_S_DN_L'
			},
			{
				width:'120px',
				sortable:true,
				caption: 'O',
				styles:{'min-width':'120px'},
				dataStyle:{'text-align':'left'},
				dataSource:'SSL_CLIENT_S_DN_O'
			},
			{
				width:'120px',
				sortable:true,
				caption: 'OU',
				styles:{'min-width':'120px'},
				dataStyle:{'text-align':'left'},
				dataSource:'SSL_CLIENT_S_DN_OU'
			},
			{
				width:'120px',
				sortable:true,
				caption: 'CN',
				styles:{'min-width':'120px'},
				dataStyle:{'text-align':'left'},
				dataSource:'SSL_CLIENT_S_DN_CN'
			}
		],
		selectType:1
	});



	//Группы
	employers_info_objects['splitter_groups'] = set_splitter_h({
		'left'		: $('employer_groups_area'),
		'right'		: $('all_groups_area'),
		'splitter'	: $('groups_splitter_handle'),
		'handle'	: $('groups_splitter'),
		'parent'	: $('tabs_area')
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
				dataSource:'group_id',
				dataType: 'num'
			},
			{
				width:'200px',
				sortable:true,
				caption: 'Группа',
				styles:{'min-width':'160px'},
				dataStyle:{'text-align':'left'},
				dataSource:'full_name'
			}
		],
		selectType:2
	};

	employers_info_objects['employer_groups_table'] = new jsTable('employer_groups_area_table', settings);
	employers_info_objects['all_groups_table'] = new jsTable('all_groups_area_table', settings);
	$('button_group_include').addEvent('click',employers_info_include_to_group);
	$('button_group_exclude').addEvent('click',employers_info_exclude_from_group);





	//Должности сотрудника
	employers_info_objects['postsarea'] = build_blockitem({
		'parent': 'employer_posts_area_table',
		'title'	: 'Занимаемые должности'
	});
	employers_info_objects['postsarea']['container'].setStyles({
		'padding': '0px',
		'margin': '0px'
	});
	$('post_delete_button_area').hide();

	//Таблица должностей
	employers_info_objects['posts_table'] = new jsTable(employers_info_objects['postsarea']['container'],{
		'class': 'jsTableLight',
		columns: [
			{
				width:'20%',
				sortable:false,
				caption: 'Организация',
				dataSource:'company_name'
			},
			{
				width:'30%',
				sortable:false,
				caption: 'Должность',
				dataSource:'post_name'
			},
			{
				caption: 'Руководитель',
				sortable:false,
				width:'30%',
				dataSource:'boss_id',
				dataStyle:{'text-align':'left','min-width':'120px'},
				dataFunction:function(table, cell, text, data){
					if(text=='0') return '<b>-[Нет руководителя]-</b>';
					if(text!='0' && (typeOf(data['bosslist'])!='array'||!data['bosslist'].length)) return '<b>'+data['boss_post_name']+'</b><br/><div class="error">-[Отсутствует руководитель]-</div>';
					var result='<b>'+data['boss_post_name']+'</b>';
					for(var i=0; i<data['bosslist'].length; i++){
						result+= '<br/><a target="_blank" class="mailto" href="/admin/employers/info?employer_id='+data['bosslist'][i]['employer_id']+'">'+data['bosslist'][i]['employer_name']+'</a> (c '+data['bosslist'][i]['post_from']+')';
					}
					return result;
				}
			},
			{
				width:'80px',
				sortable:false,
				caption: 'Начало работы',
				dataSource:'post_from',
				dataStyle:{'text-align':'center','min-width':'80px'}
			},
			{
				width:'80px',
				sortable:false,
				caption: 'Окончание работы',
				dataSource:'post_to',
				dataStyle:{'text-align':'center','min-width':'80px'}
			}
		],
		selectType:1
	});
	employers_info_objects['posts_table'].addEvent('click', employers_info_select_post);

	employers_info_objects['post_splitter'] = set_splitter_h({
		'left'		: $('post_selector_companies_area'),
		'right'		: $('post_selector_org_structure'),
		'splitter'	: $('post_selector_splitter'),
		'parent'	: $('post_selector_wrapper')
	});



	//Делегирование
	employers_info_objects['assistants_slideshow'] = new jsSlideShow('assistants_step_container');
	['assistants_assistants_button','assistants_delegates_button','assistants_history_button'].each(function(item){
		$(item).addEvent('click',employers_info_assistants_step_button_click);
	});
	employers_info_objects['assistants_area'] = build_blockitem({
		'parent': 'assistants_table_area',
		'title'	: 'Сотрудника замещают'
	});
	employers_info_objects['assistants_area']['container'].setStyles({
		'padding': '0px',
		'margin': '0px'
	});

	employers_info_objects['delegates_area'] = build_blockitem({
		'parent': 'delegates_table_area',
		'title'	: 'Сотрудник замещает'
	});
	employers_info_objects['delegates_area']['container'].setStyles({
		'padding': '0px',
		'margin': '0px'
	});

	employers_info_objects['assistants_history_area'] = build_blockitem({
		'parent': 'assistants_step_history',
		'title'	: 'История замещений сотрудника'
	});
	employers_info_objects['assistants_history_area']['container'].setStyles({
		'padding': '0px',
		'margin': '0px'
	});

	settings = {
		'dataBackground1':'#efefef',
		'class': 'jsTableLight',
		columns: [
			{
				width:'40px',
				sortable:false,
				caption: '-',
				styles:{'min-width':'40px'},
				dataStyle:{'text-align':'center'},
				dataSource:'employer_id',
				dataFunction:function(table, cell, text, data){
					new Element('img',{
						'src': INTERFACE_IMAGES+'/delete.png',
						'styles':{
							'cursor':'pointer',
							'margin-left':'4px'
						},
						'events':{
							'click': function(e){
								App.message(
									'Подтвердите действие',
									'Вы действительно хотите прекратить замещение для сотрудника '+data['employer_name']+'?',
									'CONFIRM',
									function(){
										new axRequest({
											url : '/admin/ajax/employers',
											data:{
												'action':'employers.assistant.delete',
												'for_employer': employers_info_objects['employer_info']['employer_id'],
												'assistant_id': (table['as_assistant'] ? employers_info_objects['employer_info']['employer_id'] : data['employer_id']),
												'employer_id': (table['as_assistant'] ? data['employer_id'] : employers_info_objects['employer_info']['employer_id'])
											},
											silent: false,
											waiter: true,
											callback: function(success, status, data){
												if(success){
													employers_info_dataset(data);
												}
											}
										}).request();
									}
								);
							}
						}
					}).inject(cell);
					return '';
				}
			},
			{
				width:'50px',
				sortable:true,
				caption: 'ID',
				styles:{'min-width':'50px'},
				dataStyle:{'text-align':'center'},
				dataSource:'employer_id',
				dataType: 'num'
			},
			{
				width:'100px',
				sortable:true,
				caption: 'Логин',
				styles:{'min-width':'80px'},
				dataStyle:{'text-align':'left'},
				dataSource:'username'
			},
			{
				width:'200px',
				sortable:true,
				caption: 'ФИО сотрудника',
				styles:{'min-width':'160px'},
				dataStyle:{'text-align':'left'},
				dataSource:'employer_name',
				dataFunction:function(table, cell, text, data){
					return '<a target="_blank" class="mailto" href="/admin/employers/info?employer_id='+data['employer_id']+'">'+text+'</a>';
				}
			},
			{
				width:'120px',
				styles:{'min-width':'120px'},
				sortable:true,
				dataStyle:{'text-align':'center'},
				caption: 'Дата начала',
				dataSource:'from_date',
				dataType: 'date'
			},
			{
				width:'120px',
				styles:{'min-width':'120px'},
				sortable:true,
				dataStyle:{'text-align':'center'},
				caption: 'Дата окончания',
				dataSource:'to_date',
				dataType: 'date'
			}
		],
		selectType:1
	};
	employers_info_objects['assistants_table'] = new jsTable(employers_info_objects['assistants_area']['container'], settings);
	employers_info_objects['assistants_table']['as_assistant'] = false;
	employers_info_objects['delegates_table'] = new jsTable(employers_info_objects['delegates_area']['container'], settings);
	employers_info_objects['delegates_table']['as_assistant'] = true;
	$('assistants_selector_term').addEvent('keydown',function(e){if(e.code==13) employers_info_assistants_selector_search();});
	$('assistants_selector_term_button').addEvent('click',employers_info_assistants_selector_search);

	employers_info_objects['assistants_history_table'] =  new jsTable(employers_info_objects['assistants_history_area']['container'], {
		'dataBackground1':'#efefef',
		'class': 'jsTableLight',
		columns: [
			{
				width:'200px',
				sortable:true,
				caption: 'Кого замещают',
				styles:{'min-width':'160px'},
				dataStyle:{'text-align':'left'},
				dataSource:'employer_name',
				dataFunction:function(table, cell, text, data){
					if(employers_info_objects['employer_info']['employer_id'] == data['employer_id']) return '<b>'+text+'</b>';
					return '<a target="_blank" class="mailto" href="/admin/employers/info?employer_id='+data['employer_id']+'">'+text+'</a>';
				}
			},
			{
				width:'200px',
				sortable:true,
				caption: 'Кто заместитель',
				styles:{'min-width':'160px'},
				dataStyle:{'text-align':'left'},
				dataSource:'assistant_name',
				dataFunction:function(table, cell, text, data){
					if(employers_info_objects['employer_info']['employer_id'] == data['assistant_id']) return '<b>'+text+'</b>';
					return '<a target="_blank" class="mailto" href="/admin/employers/info?employer_id='+data['assistant_id']+'">'+text+'</a>';
				}
			},
			{
				width:'120px',
				styles:{'min-width':'120px'},
				sortable:true,
				dataStyle:{'text-align':'center'},
				caption: 'Дата начала',
				dataSource:'from_date',
				dataType: 'date'
			},
			{
				width:'120px',
				styles:{'min-width':'120px'},
				sortable:true,
				dataStyle:{'text-align':'center'},
				caption: 'Дата окончания',
				dataSource:'to_date',
				dataType: 'date'
			},
			{
				width:'160px',
				styles:{'min-width':'160px'},
				sortable:true,
				dataStyle:{'text-align':'center'},
				caption: 'Время операции',
				dataSource:'timestamp',
				dataType: 'date'
			},
			{
				width:'200px',
				sortable:true,
				caption: 'Податель',
				styles:{'min-width':'160px'},
				dataStyle:{'text-align':'left'},
				dataSource:'submitter_name',
				dataFunction:function(table, cell, text, data){
					if(employers_info_objects['employer_info']['employer_id'] == data['submitter_id']) return '<b>'+text+'</b>';
					return '<a target="_blank" class="mailto" href="/admin/employers/info?employer_id='+data['submitter_id']+'">'+text+'</a>';
				}
			}
		],
		selectType:0
	});


	//Права
	var settings = {
		'dataBackground1':'#efefef',
		'class': 'jsTableLight',
		columns: [
			{
				width:'50px',
				sortable:false,
				caption: 'ID',
				styles:{'min-width':'50px'},
				dataStyle:{'text-align':'center'},
				dataSource:'company_id'
			},
			{
				width:'200px',
				sortable:true,
				caption: 'Организация',
				styles:{'min-width':'160px'},
				dataStyle:{'text-align':'left'},
				dataSource:'full_name'
			}
		],
		selectType:2
	};

	employers_info_objects['employer_right_table'] = new jsTable('employer_rights_area_table', settings);
	employers_info_objects['all_right_table'] = new jsTable('all_rights_area_table', settings);
	$('button_right_include').addEvent('click',employers_info_right_for_company_add);
	$('button_right_exclude').addEvent('click',employers_info_right_for_company_delete);
	employers_info_objects['splitter_rights'] = set_splitter_h({
		'left'		: $('employer_rights_area'),
		'right'		: $('all_right_area'),
		'splitter'	: $('right_splitter_handle'),
		'handle'	: $('right_splitter'),
		'parent'	: $('tabs_area')
	});


	//Применение данных
	employers_info_dataset(data);

	if(typeOf(employers_info_objects['employer_info'])!='object'){
		$('tabs_area').hide();
		$('tabs_none').show();
		return;
	}

	$('certificate_upload_form').getElement('input[name=employer_id]').set('value',employers_info_objects['employer_info']['employer_id']);
	$('certificate_upload_form').getElement('input[type=file]').addEvent('change',function(){
		$('upload_certificate_button').show();
	});

}//end function




//Смена вкладки
function employers_info_change_tab(index){

	switch(index){

		//Профиль
		case 0:
		break;

		//Группы
		case 1:
		break;

		//Должности
		case 2:
		break;


		//Делегирование
		case 3:
		break;


		//Права
		case 4:
		break;

	}

}//end function




//Обработка нажатия на кнопки управления
function employers_info_profile_step_button_click(event){
	if (!event || (event && typeOf(event.target) != 'element')) return;
	if (event.event.which && event.event.which != 1) return;
	var div = event.target.get('tag') == 'div' ? event.target : event.target.getParent('div');
	var action = 'empty';
	switch(div.id){
		case 'profile_info_button': action = 'info'; break;
		case 'profile_notice_button': action = 'notice'; break;
		case 'profile_account_button': action = 'account'; break;
		case 'profile_certificate_button': action = 'certificate'; break;
	}
	return employers_info_profile_step_do_page(action);
}//end function




//Навигация по страницам мастера
function employers_info_profile_step_do_page(action){
	if(action == 'empty') return;
	if(!$('profile_step_'+action)) return;
	//Слайд
	employers_info_objects['profile_slideshow'].show($('profile_step_'+action), {
		transition: 'fadeThroughBackground'
	});
}//end function




//Применение данных
function employers_info_dataset(data){

	if(typeOf(data)!='object') return false;

	//Сведения о сотруднике
	if(typeOf(data['employer_info'])=='object'){
		employers_info_objects['employer_info'] = data['employer_info'];
		$('employer_info_title').set('text','Карточка сотрудника ID:'+data['employer_info']['employer_id']+' '+data['employer_info']['search_name']);
		var id;
		for(var key in data['employer_info']){
			id = $('info_'+key);
			if(!id) continue;
			id.setValue(data['employer_info'][key]);
		}
		
		if(String(data['employer_info']['anket_id'])!='0'){
			$('info_anket_id_area').show();
			$('info_anket_id_link').set('href','/admin/employers/anketinfo?anket_id='+data['employer_info']['anket_id']);
		}else{
			$('info_anket_id_area').hide();
		}
	}//Сведения о сотруднике


	//Список организаций
	if(typeOf(data['companies'])=='array'){
		employers_info_objects['companies_array']=[];
		employers_info_objects['companies_assoc']={};
		for(var i=0;i<data['companies'].length;i++){
			employers_info_objects['companies_array'].push(data['companies'][i]);
			employers_info_objects['companies_assoc'][data['companies'][i]['company_id']]=data['companies'][i]['full_name'];
		}
		employers_info_objects['companies_array'].sort(function(a,b){return (a['full_name']>b['full_name'] ? 1 : -1);});
		select_add({
			'list': 'post_selector_companies_select',
			'key': 'company_id',
			'value': 'full_name',
			'options': employers_info_objects['companies_array'],
			'default': 0,
			'clear': true
		});
	}//Список организаций


	//Список сертификатов сотрудника
	if(typeOf(data['employer_certs'])=='array'){
		employers_info_objects['certtable'].setData(data['employer_certs']);
	}//Список сертификатов сотрудника


	//Список всех групп
	if(typeOf(data['groups'])=='array'){
		employers_info_objects['groups_array']=[];
		employers_info_objects['groups_assoc']={};
		for(var i=0;i<data['groups'].length;i++){
			employers_info_objects['groups_array'].push(data['groups'][i]);
			employers_info_objects['groups_assoc'][data['groups'][i]['group_id']]=data['groups'][i]['full_name'];
		}
	}//Список всех групп


	//Список групп сотрудника
	if(typeOf(data['employer_groups'])=='array'){
		employers_info_objects['employer_groups']=[];
		for(var i=0;i<data['employer_groups'].length;i++){
			if(employers_info_objects['groups_assoc'][data['employer_groups'][i]]){
				employers_info_objects['employer_groups'].push({
					'group_id': data['employer_groups'][i],
					'full_name': employers_info_objects['groups_assoc'][data['employer_groups'][i]]
				});
			}
		}
		employers_info_objects['employer_groups_table'].setData(employers_info_objects['employer_groups']);
		employers_info_objects['all_groups_table'].setData(employers_info_objects['groups_array'].filterSelect({
			'group_id':{
				'value': data['employer_groups'],
				'condition': 'NOTIN'
			}
		}));
	}//Список групп сотрудника


	//Список должностей сотрудника
	if(typeOf(data['employer_posts'])=='array'){
		employers_info_objects['posts_table'].setData(data['employer_posts']);
		$('post_delete_button_area').hide();
	}//Список должностей сотрудника


	//Список ассистентов
	if(typeOf(data['employer_assistants'])=='array'){
		employers_info_objects['assistants_table'].setData(data['employer_assistants']);
	}//Список ассистентов


	//Список делегировавших
	if(typeOf(data['employer_delegates'])=='array'){
		employers_info_objects['delegates_table'].setData(data['employer_delegates']);
	}//Список делегировавших


	//История делегирований
	if(typeOf(data['assistants_history'])=='array'){
		employers_info_objects['assistants_history_table'].setData(data['assistants_history']);
	}//История делегирований


	//Права сотрудника
	if(typeOf(data['employer_rights'])=='array'){
		employers_info_objects['employer_rights'] = data['employer_rights'];
		employers_info_rights_select_change();
	}//Права сотрудника

}//end function




//Отправка сертификата на сервер
function employers_info_profile_upload_certificate(){
	new axRequest({
		uploaderForm: $('certificate_upload_form'),
		silent: false,
		waiter: true,
		callback: function(success, status, data){
			if(success){
				$('upload_certificate_button').hide();
				employers_info_dataset(data);
			}
		}
	}).upload();
}//end function



//Изменение информации о сотрудники
function employers_info_profile_change_info(type){
	var anket = {
		'action':'employers.info.change',
		'employer_id': employers_info_objects['employer_info']['employer_id'],
		'type': type
	};

	switch(type){
		case 'info':
			if(!employers_info_objects['form_profile_info'].validate()) return;
			['first_name','last_name','middle_name','birth_date','phone','email'].each(function(key){
				if($('info_'+key)) anket[key]=$('info_'+key).getValue();
			});
		break;
		case 'notice':
			if(!employers_info_objects['form_profile_info'].validate()) return;
			['notice_me_requests','notice_curator_requests','notice_gkemail_1','notice_gkemail_2','notice_gkemail_3','notice_gkemail_4'].each(function(key){
				if($('info_'+key)) anket[key]=$('info_'+key).getValue();
			});
		break;
		case 'username':
			if(!employers_info_objects['form_profile_username'].validate()) return;
			if($('info_username')) anket['username']=$('info_username').getValue();
		break;
		case 'password':
			if(!employers_info_objects['form_profile_password'].validate()) return;
			if($('info_password')) anket['password']=$('info_password').getValue();
		break;
		case 'pincode':
			if(!employers_info_objects['form_profile_pincode'].validate()) return;
			['pin_code','ignore_pin'].each(function(key){
				if($('info_'+key)) anket[key]=$('info_'+key).getValue();
			});
		break;
		case 'access':
			if(!employers_info_objects['form_profile_access'].validate()) return;
			['access_level','status'].each(function(key){
				if($('info_'+key)) anket[key]=$('info_'+key).getValue();
			});
		break;
	}

	new axRequest({
		url : '/admin/ajax/employers',
		data: anket,
		silent: false,
		display: 'hint',
		waiter: true,
		callback: function(success, status, data){
			if(success){
				employers_info_dataset(data);
			}
		}
	}).request();
}





//Исключение сотрудника из групп
function employers_info_exclude_from_group(){
	var tr,data;
	if(!employers_info_objects['employer_groups_table'].selectedRows.length) return;
	var groups = [];
	for(var i=0; i<employers_info_objects['employer_groups_table'].selectedRows.length;i++){
		tr = employers_info_objects['employer_groups_table'].selectedRows[i];
		if(typeOf(tr)!='element') continue;
		data = tr.retrieve('data');
		if(typeOf(data)!='object') continue;
		groups.push(data['group_id']);
	}
	employers_info_group_operation(groups, 'exclude');
}//end function




//Включение сотрудника в группы
function employers_info_include_to_group(){
	if(!employers_info_objects['all_groups_table'].selectedRows.length) return;
	var groups = [];
	for(var i=0; i<employers_info_objects['all_groups_table'].selectedRows.length;i++){
		tr = employers_info_objects['all_groups_table'].selectedRows[i];
		if(typeOf(tr)!='element') continue;
		data = tr.retrieve('data');
		if(typeOf(data)!='object') continue;
		groups.push(data['group_id']);
	}
	employers_info_group_operation(groups, 'include');
}//end function




//Операция включение, исключение сотрудника
function employers_info_group_operation(groups, action){

	if(typeOf(groups)!='array'||!groups.length) return;

	new axRequest({
		url : '/admin/ajax/employers',
		data:{
			'action':'employers.group.'+action,
			'employer_id': employers_info_objects['employer_info']['employer_id'],
			'groups': groups
		},
		silent: false,
		waiter: true,
		callback: function(success, status, data){
			if(success){
				employers_info_dataset(data);
			}
		}
	}).request();
}//end function




//Открытие окна выбора добавляемой должности сотруднику
function employers_info_post_add(){
	$('employer_info_wrapper').hide();
	$('post_selector_complete_button').hide();
	$('post_selector_period').hide();
	$('post_selector').show();
	employers_info_post_company_select();
}//end function




//Закрытие окна выбора добавляемой должности сотруднику
function employers_info_post_add_cancel(){
	$('post_selector').hide();
	$('post_selector_period').hide();
	$('employer_info_wrapper').show();
}//end function



//Выбор организации
function employers_info_post_company_select(){
	var company_id = select_getValue('post_selector_companies_select');
	if(parseInt(company_id)<1) return;
	if(typeOf(employers_info_objects['org_structure'][company_id])=='array'){
		employers_info_post_company_structure_set(company_id, employers_info_objects['org_structure'][company_id]);
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
				employers_info_objects['org_structure'][data['company_id']] = data['org_data'];
				employers_info_post_company_structure_set(data['company_id'], data['org_data']);
			}
		}
	}).request();
}//end function




//Отображение выбранной органзиционной структуры
function employers_info_post_company_structure_set(company_id, data){
	if(
		typeOf(employers_info_objects['companies_assoc'])=='object' &&
		employers_info_objects['companies_assoc'][company_id]
	){
		var company_name = employers_info_objects['companies_assoc'][company_id];
		$('post_selector_title').set('text','Выберите должность в организации '+company_name);
		employers_info_objects['orgchart'].setData(company_name, data);
	}
	$('post_selector_complete_button').hide();
}//end function



//Выбор должности
function employers_info_post_post_select(el){
	if(!el || typeOf(el)!='element' || el.hasClass('noselect')){
		$('post_selector_complete_button').hide();
		employers_info_objects['post_selected'] = null;
	}else{
		employers_info_objects['post_selected'] = {
			'company_id': el.retrieve('company_id'),
			'post_uid': el.retrieve('post_uid'),
			'full_name': el.retrieve('full_name')
		};
		$('post_selector_complete_button').show();
	}
}//end function



//Фильтр 
function employers_info_post_filter(){
	employers_info_objects['orgchart'].filter($('posts_filter').value);
}//end function



//Должность выбрана, выбор даты начала и окончания работы
function employers_info_post_selector_complete(){
	if(typeOf(employers_info_objects['post_selected'])!='object') return;
	$('post_selector').hide();
	$('post_selector_selected_name').set('html',employers_info_objects['companies_assoc'][employers_info_objects['post_selected']['company_id']]+'<h2>'+employers_info_objects['post_selected']['full_name']+'</h2>');
	$('post_selector_post_template').checked=false;
	$('post_selector_period').show();
}//end function



//Должность выбрана, добавление
function employers_info_post_selector_done(){
	if(typeOf(employers_info_objects['employer_info'])!='object') return;
	if(typeOf(employers_info_objects['post_selected'])!='object') return;
	new axRequest({
		url : '/admin/ajax/employers',
		data:{
			'action':'employers.post.add',
			'employer_id': employers_info_objects['employer_info']['employer_id'],
			'company_id': employers_info_objects['post_selected']['company_id'],
			'post_uid': employers_info_objects['post_selected']['post_uid'],
			'date_from': $('post_selector_post_date_from').value,
			'date_to': $('post_selector_post_date_to').value,
			'template': ($('post_selector_post_template').checked ? '1':'0')
		},
		silent: false,
		waiter: true,
		callback: function(success, status, data){
			if(success){
				employers_info_dataset(data);
				employers_info_post_add_cancel();
			}
		}
	}).request();
}//end function




//Обработка нажатия на кнопки управления
function employers_info_assistants_step_button_click(event){
	if (!event || (event && typeOf(event.target) != 'element')) return;
	if (event.event.which && event.event.which != 1) return;
	var div = event.target.get('tag') == 'div' ? event.target : event.target.getParent('div');
	var action = 'empty';
	switch(div.id){
		case 'assistants_assistants_button': action = 'assistants'; break;
		case 'assistants_delegates_button': action = 'delegates'; break;
		case 'assistants_history_button': action = 'callhistory'; break;
	}
	return employers_info_assistants_step_do_page(action);
}//end function


//Навигация по страницам
function employers_info_assistants_step_do_page(action){
	if(action == 'empty') return;

	if(action == 'callhistory'){
		new axRequest({
			url : '/admin/ajax/employers',
			data:{
				'action':'employers.assistant.history',
				'employer_id': employers_info_objects['employer_info']['employer_id']
			},
			silent: true,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					employers_info_dataset(data);
					employers_info_assistants_step_do_page('history');
				}
			}
		}).request();
		return;
	}

	if(!$('assistants_step_'+action)) return;
	//Слайд
	employers_info_objects['assistants_slideshow'].show($('assistants_step_'+action), {
		transition: 'fadeThroughBackground'
	});
}//end function




//Выбор сотрудника для замещения
function employers_info_assistants_selector_open(is_assistant){
	$('employer_info_wrapper').hide();
	$('assistants_list').empty();
	$('assistants_selector_term').value='';
	$('assistants_selector_wrapper').scrollTo(0, 0);
	$('assistants_selector_complete_button').hide();
	$('assistants_selector_none').hide();
	$('assistants_selector_table').hide();
	employers_info_objects['selected_assistant'] = null;
	employers_info_objects['assistants_selector_as_assistant'] = is_assistant;
	$('assistants_selector').show();
}//end function



//Закрытие окна выбора сотрудника
function employers_info_assistants_selector_cancel(){
	$('assistants_selector').hide();
	$('assistants_selector_period').hide();
	$('employer_info_wrapper').show();
}//end function




//Поиск сотрудников
function employers_info_assistants_selector_search(){

	var tobj = $('assistants_selector_term');
	var term = String(tobj.value).trim();
	if(!term.length) return;

	if(employers_info_objects['process_search']) return;
	employers_info_objects['process_search'] = true;

	new axRequest({
		url : '/admin/ajax/employers',
		data:{
			'action':'employers.search',
			'search_name': term
		},
		silent: true,
		waiter: true,
		callback: function(success, status, data){
			employers_info_objects['process_search'] = false;
			if(success){
				employers_info_objects['selected_assistant_object'] = null;
				employers_info_objects['selected_assistant'] = 0;
				$('assistants_selector_complete_button').hide();
				if(typeOf(data)=='object'&&typeOf(data['employers_search'])=='array'&&data['employers_search'].length>0){
					$('assistants_selector_none').hide();
					$('assistants_selector_table').show();
					employers_info_assistants_selector_build_list(data['employers_search']);
				}else{
					$('assistants_selector_none').show();
					$('assistants_selector_table').hide();
				}
			}
		}
	}).request();
}//end function




//Построение списка найденных сотрудников
function employers_info_assistants_selector_build_list(data){

	var list = $('assistants_list');
	var li, html, employer_name, phone, email;

	list.empty();

	//Построение списка
	for(var index=0; index<data.length; index++){
		employer_name = data[index]['search_name'];
		phone = String(data[index]['phone']).length > 0 ? data[index]['phone'] : null;
		email = String(data[index]['email']).length > 0 ? data[index]['email'] : null;
		posts = data[index]['posts'];

		html = '<div class="line"><a class="mailto" href="/admin/employers/info?employer_id='+data[index]['employer_id']+'" target="_blank">'+employer_name +'</a><br/>'+(phone || email ? '<p class="small">'+( phone ? 'Телефон: '+data[index]['phone'] : '')+(phone && email ? '<br/>':'')+(email ? 'Email: <a class="mailto" href="mailto:'+data[index]['email']+'">'+data[index]['email']+'</a>':'')+'</p>' : '')+'</div>';
		html+= '<div class="line"><p class="small">Идентификатор: '+data[index]['employer_id']+'</p></div>';
		html+= '<div class="line"><p class="small">Имя пользователя: '+data[index]['username']+'</p></div>';

		li = new Element('li').inject(list).set('html',html).addEvent('click',function(){
			if($(employers_info_objects['selected_assistant_object'])) $(employers_info_objects['selected_assistant_object']).removeClass('selected');
			if(employers_info_objects['employer_info']['employer_id'] == this.retrieve('employer_id')) return;
			this.addClass('selected');
			employers_info_objects['selected_assistant'] = this.retrieve('employer_id');
			employers_info_objects['selected_assistant_object'] = this;
			$('assistants_selector_complete_button').show();
		}).store('employer_id',data[index]['employer_id']).store('employer_data',data[index]);;

	}//Построение списка

}//end function



//Выбор сотрудника
function employers_info_assistants_selector_complete(){
	if(!$(employers_info_objects['selected_assistant_object'])) return;
	var employer = $(employers_info_objects['selected_assistant_object']).retrieve('employer_data');
	var phone = String(employer['phone']).length > 0 ? employer['phone'] : null;
	var email = String(employer['email']).length > 0 ? employer['email'] : null;
	$('assistants_selector').hide();
	$('assistants_selector_period').show();
	$('assistants_selector_selected_name').set('html','<h2>'+employer['search_name']+'</h2><p class="small">'+
	(phone || email ? ( phone ? 'Телефон: '+phone : '')+(phone && email ? '<br/>':'')+(email ? 'Email: <a class="mailto" href="mailto:'+email+'">'+email+'</a>':'') : '')+'</p>'+
	'<br><p class="small">Идентификатор: '+employer['employer_id']+'</p><br><p class="small">Имя пользователя: '+employer['username']+'</p>');
}//end function




//Делегирование полномочий выбранному сотруднику
function employers_info_assistants_selector_done(){
	if(!$(employers_info_objects['selected_assistant_object'])) return;
	var employer_id = $(employers_info_objects['selected_assistant_object']).retrieve('employer_id');
	var date_from = String($('assistants_selector_date_from').get('value')).trim();
	var date_to = String($('assistants_selector_date_to').get('value')).trim();
	var assistant_id = 0;

	if(employers_info_objects['assistants_selector_as_assistant']){
		assistant_id = employers_info_objects['employer_info']['employer_id'];
	}else{
		assistant_id = employer_id;
		employer_id = employers_info_objects['employer_info']['employer_id'];
	}

	if(employer_id == assistant_id){
		employers_info_assistants_selector_cancel();
		return;
	}

	new axRequest({
		url : '/admin/ajax/employers',
		data:{
			'action':'employers.assistant.add',
			'for_employer': employers_info_objects['employer_info']['employer_id'],
			'employer_id': employer_id,
			'assistant_id': assistant_id,
			'date_from': date_from,
			'date_to': date_to
		},
		silent: true,
		waiter: true,
		callback: function(success, status, data){
			if(success){
				employers_info_assistants_selector_cancel();
				employers_info_dataset(data);
			}
		}
	}).request();
}//end function




//Выбор права доступа из списка прав доступа
function employers_info_rights_select_change(){
	var right_type = select_getValue('employer_rights_select');
	if(!['can_add_employer','can_curator'].contains(right_type)) return;
	if(typeOf(employers_info_objects['employer_rights'])!='array') return;
	var have_rights = employers_info_objects['employer_rights'].filterSelect(right_type, '1', 0).fromField('company_id',true);

	var companies = employers_info_objects['companies_array'].clone();
	companies.unshift({'company_id':"0",'full_name':'<b>-[Все организации]-</b>'});

	employers_info_objects['employer_right_table'].setData(companies.filterSelect({
		'company_id':{
			'value': have_rights,
			'condition': 'IN'
		}
	}));

	employers_info_objects['all_right_table'].setData(companies.filterSelect({
		'company_id':{
			'value': have_rights,
			'condition': 'NOTIN'
		}
	}));

}//end function




//Исключение организации из прав доступа сотрудника
function employers_info_right_for_company_delete(){
	var tr,data;
	if(!employers_info_objects['employer_right_table'].selectedRows.length) return;
	var companies = [];
	for(var i=0; i<employers_info_objects['employer_right_table'].selectedRows.length;i++){
		tr = employers_info_objects['employer_right_table'].selectedRows[i];
		if(typeOf(tr)!='element') continue;
		data = tr.retrieve('data');
		if(typeOf(data)!='object') continue;
		companies.push(data['company_id']);
	}
	employers_info_right_operation(companies, 'delete');
}//end function




//Включение организации в права доступа сотрудника
function employers_info_right_for_company_add(){
	if(!employers_info_objects['all_right_table'].selectedRows.length) return;
	var companies = [];
	for(var i=0; i<employers_info_objects['all_right_table'].selectedRows.length;i++){
		tr = employers_info_objects['all_right_table'].selectedRows[i];
		if(typeOf(tr)!='element') continue;
		data = tr.retrieve('data');
		if(typeOf(data)!='object') continue;
		companies.push(data['company_id']);
	}
	employers_info_right_operation(companies, 'add');
}//end function




//Операция включение, исключение сотрудника
function employers_info_right_operation(companies, action){

	if(typeOf(companies)!='array'||!companies.length) return;

	new axRequest({
		url : '/admin/ajax/employers',
		data:{
			'action':'employers.right.'+action,
			'employer_id': employers_info_objects['employer_info']['employer_id'],
			'right_type': select_getValue('employer_rights_select'),
			'companies': companies
		},
		silent: false,
		waiter: true,
		callback: function(success, status, data){
			if(success){
				employers_info_dataset(data);
			}
		}
	}).request();
}//end function



//Получение печатной формы карточки сотрудника
function employers_info_profile_print(format){
	var link = '/admin/customcontent/accountprint?employer_id='+employers_info_objects['employer_info']['employer_id']+'&info='+$('print_info').getValue()+'&password='+$('print_password').getValue()+'&pincode='+$('print_pin_code').getValue()+'&format='+format;
	App.Loader.downloadFile(link);
}//end function



//Выбор должности сотрудника из списка должностей
function employers_info_select_post(){
	$('post_delete_button_area').hide();
	employers_info_objects['selected_post']=null;
	if(!employers_info_objects['posts_table'].selectedRows.length) return;
	var tr = employers_info_objects['posts_table'].selectedRows[0];
	if(typeOf(tr)!='element') return;
	var data = tr.retrieve('data');
	if(typeOf(data)!='object') return;
	employers_info_objects['selected_post']=data;
	$('post_delete_button_area').show();
}//end function



//Удаление выбранной должности
function employers_info_post_delete(){
	if(typeOf(employers_info_objects['selected_post'])!='object') return employers_info_select_post();
	App.message(
		'Подтвердите действие',
		'Вы действительно хотите '+select_getText('post_delete_type')+'?',
		'CONFIRM',
		function(){
			new axRequest({
				url : '/admin/ajax/employers',
				data:{
					'action':'employers.post.delete',
					'employer_id': employers_info_objects['employer_info']['employer_id'],
					'company_id': employers_info_objects['selected_post']['company_id'],
					'post_uid': employers_info_objects['selected_post']['post_uid'],
					'type': $('post_delete_type').getValue()
				},
				silent: false,
				waiter: true,
				display: 'hint',
				callback: function(success, status, data){
					if(success){
						employers_info_dataset(data);
					}
				}
			}).request();
		}
	);
}//end function