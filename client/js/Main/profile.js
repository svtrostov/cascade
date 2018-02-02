var profile_objects = {};


//Вход на страницу
function profile_enter_page(success, status, data){
	profile_start(data);
}//end function



//Выход со страницы
function profile_exit_page(){

	if(profile_objects['form_contact'])profile_objects['form_contact'].destroy();
	if(profile_objects['form_password'])profile_objects['form_password'].destroy();
	if(profile_objects['posts_table']) profile_objects['posts_table'].terminate();

	for(var i in profile_objects){
		profile_objects[i] = null;
	}
	profile_objects = {};
	App.Location.removeEvent('beforeLoadPage', profile_exit_page);
}//end function



//Инициализация процесса создания заявки
function profile_start(data){
	App.Location.addEvent('beforeLoadPage', profile_exit_page);

	//Построение слайдов
	profile_objects['slideshow'] = new jsSlideShow('step_container');
	profile_do_page('profile');

	profile_objects['posts_table'] = null;
	profile_objects['form_contact'] = new jsValidator('tmpl_profile_info');
	profile_objects['form_contact'].required('info_phone').phone('info_phone').email('info_email');

	profile_objects['form_password'] = new jsValidator('tmpl_profile_password');
	profile_objects['form_password'].required('password_prev').required('password_new').password('password_new').matches('password_confirm','password_new','Пароли не совпадают');

	//Обработка событий
	$('button_profile').addEvent('click',profile_event_button);
	$('button_posts').addEvent('click',profile_event_button);
	$('button_security').addEvent('click',profile_event_button);
	$('button_notice').addEvent('click',profile_event_button);

	var el;
	for(var key in data['info']){
		el = $('info_'+key);
		if(typeOf(el)!='element') continue;
		if(el.get('type')=='checkbox') el.checked = (data['info'][key]=='1'?true:false);
		else el.value = data['info'][key];
	}

	var infoarea = build_blockitem({
		'parent': 'step_profile',
		'title'	: 'Персональная информация'
	});
	$('tmpl_profile_info').show().inject(infoarea['container']);


	var noticearea = build_blockitem({
		'parent': 'step_notice',
		'title'	: 'Уведомления по электронной почте'
	});
	$('tmpl_notice_info').show().inject(noticearea['container']);


	profile_build_posts(data['posts']);

	var passwordarea = build_blockitem({
		'parent': 'step_security',
		'title'	: 'Смена пароля'
	});
	$('tmpl_profile_password').show().inject(passwordarea['container']);

	if(typeOf(REQUEST_LAST_DATA_GET)=='object'){
		if(['profile','notice','posts','security'].contains(REQUEST_LAST_DATA_GET['id_area'])){
			profile_do_page(REQUEST_LAST_DATA_GET['id_area']);
		}
	}

}//end function



//Обработка нажатия на кнопки управления
function profile_event_button(event){
	if (!event || (event && typeOf(event.target) != 'element')) return;
	if (event.event.which && event.event.which != 1) return;
	var div = event.target.get('tag') == 'div' ? event.target : event.target.getParent('div');
	var action = 'empty';
	switch(div.id){
		case 'button_profile': action = 'profile'; break;
		case 'button_notice': action = 'notice'; break;
		case 'button_posts': action = 'posts'; break;
		case 'button_security': action = 'security'; break;
	}
	return profile_do_page(action);
}//end function





//Навигация по страницам мастера
function profile_do_page(action){

	if(action == 'empty') return;
	if(!$('step_'+action)) return;

	//Слайд
	profile_objects['slideshow'].show($('step_'+action), {
		transition: 'fadeThroughBackground'
	});


}//end function




//Построение таблицы должностей
function profile_build_posts(data){

	if(typeOf(data)!='array'||!data.length){
		if(profile_objects['posts_table'] != null){
			profile_objects['posts_table'].terminate();
			profile_objects['posts_table'] = null;
		}
		$('step_posts').empty();
		new Element('h2',{'html':'В настоящий момент системе неизвестно, на каких должностях Вы работаете.'}).inject('step_posts');
		return;
	}else{
		if(profile_objects['posts_table'] != null){
			profile_objects['posts_table'].setData(data);
			return;
		}
		$('step_posts').empty();
	}

	var postsarea = build_blockitem({
		'parent': 'step_posts',
		'title'	: 'Занимаемые должности'
	});

	postsarea['container'].setStyles({
		'padding': '0px',
		'margin': '0px'
	});

	//Таблица со списком доступов
	profile_objects['posts_table'] = new jsTable(postsarea['container'],{
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
				dataSource:'boss_post_id',
				dataStyle:{'text-align':'left','min-width':'120px'},
				dataFunction:function(table, cell, text, data){
					if(text=='0') return '<b>-[Нет руководителя]-</b>';
					if(text!='0' && (typeOf(data['bosses'])!='array'||!data['bosses'].length)) return '<b>'+data['boss_post_name']+'</b><br/><div class="error">-[Отсутствует руководитель]-</div>';
					var result='<b>'+data['boss_post_name']+'</b>';
					for(var i=0; i<data['bosses'].length; i++){
						result+= '<br/>'+data['bosses'][i]['employer_name']+' (c '+data['bosses'][i]['post_from']+')';
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
			}
		],
		'dataBackground1': '#fff',
		'dataBackground2': '#fff',
		selectType:1
	});
	profile_objects['posts_table'].setData(data);

}//end function





//Изменить контактные данные
function profile_change_info(){

	if(!profile_objects['form_contact'].validate()) return;

	//Отправка заявки
	new axRequest({
		url : '/main/ajax/employer',
		data:{
			'action':'profile.info.change',
			'phone': $('info_phone').value,
			'email': $('info_email').value
		},
		silent: false,
		waiter: true,
		callback: function(success, status, data){
			if(success){}
		}
	}).request();

}



//Изменить пароль
function profile_change_password(){

	if(!profile_objects['form_password'].validate()) return;

	//Отправка заявки
	new axRequest({
		url : '/main/ajax/employer',
		data:{
			'action':'profile.password.change',
			'pwdprev': $('password_prev').value,
			'pwdnew': $('password_new').value,
			'pwdconfirm': $('password_confirm').value
		},
		silent: false,
		waiter: true,
		callback: function(success, status, data){
			$('password_prev').value = '';
			$('password_new').value = '';
			$('password_confirm').value = '';
		}
	}).request();

}



//Изменить уведомления
function profile_change_notice(){

	if(!profile_objects['form_contact'].validate()) return;

	//Отправка заявки
	new axRequest({
		url : '/main/ajax/employer',
		data:{
			'action':'profile.notice.change',
			'notice_me_requests': ($('info_notice_me_requests').checked?'1':'0'),
			'notice_curator_requests': ($('info_notice_curator_requests').checked?'1':'0'),
			'notice_gkemail_1': ($('info_notice_gkemail_1').checked?'1':'0'),
			'notice_gkemail_2': ($('info_notice_gkemail_2').checked?'1':'0'),
			'notice_gkemail_3': ($('info_notice_gkemail_3').checked?'1':'0'),
			'notice_gkemail_4': ($('info_notice_gkemail_4').checked?'1':'0')
		},
		silent: false,
		waiter: true,
		callback: function(success, status, data){
			if(success){}
		}
	}).request();

}
