var curator_employer_objects = {};


//Вход на страницу
function curator_employer_enter_page(success, status, data){
	if(
		typeOf(data)!='object' ||
		typeOf(data['companies'])!='array' ||
		!data['companies'].length
	){
		$('anket_wrapper').hide();
		$('anket_none').show();
		return false;
	}
	curator_employer_start(data);
}//end function



//Выход со страницы
function curator_employer_exit_page(){

	if(curator_employer_objects['form'])curator_employer_objects['form'].destroy();

	for(var i in curator_employer_objects){
		curator_employer_objects[i] = null;
	}
	curator_employer_objects = {};
	App.Location.removeEvent('beforeLoadPage', curator_employer_exit_page);
}//end function




//Инициализация процесса создания заявки
function curator_employer_start(data){
	App.Location.addEvent('beforeLoadPage', curator_employer_exit_page);

	curator_employer_objects['posts'] = {};

	//Построение анкеты
	curator_employer_objects['anketarea'] = build_blockitem({
		'parent': 'anket_area',
		'title'	: 'Заполните анкету сотрудника'
	});
	$('tmpl_anket').show().inject(curator_employer_objects['anketarea']['container']);

	select_add({
		'list'		: 'info_company',
		'options'	: data['companies'],
		'key'		: 'company_id',
		'value'		: 'company_name'
	});

	//Создание календарей
	new Picker.Date($$('.calendar_input'), {
		timePicker: false,
		positionOffset: {x: 0, y: 2},
		pickerClass: 'calendar',
		useFadeInOut: false
	});

	curator_employer_change_company();
	curator_employer_filter_posts();

	//Построение предпросмотра
	curator_employer_objects['previewarea'] = build_blockitem({
		'parent': 'anket_area',
		'title'	: 'Предварительный просмотр'
	});
	curator_employer_objects['previewarea']['li'].hide();
	$('tmpl_preview').show().inject(curator_employer_objects['previewarea']['container']);

	curator_employer_objects['form'] = new jsValidator('tmpl_anket');
	curator_employer_objects['form']
	.minValue('info_post',1,'Укажите должность сотрудника')
	.required('info_first_name').alpha('info_first_name')
	.required('info_last_name').alpha('info_last_name')
	.required('info_middle_name').alpha('info_middle_name')
	.required('info_post_filter')
	.required('info_birth_date').date('info_birth_date')
	.required('info_order_date').date('info_order_date')
	.required('info_phone').phone('info_phone')
	.email('info_email');

}//end function





//Изменить организацию
function curator_employer_change_company(){

	var company_id = select_getValue('info_company');
	$('info_post').options.length = 0;

	if(typeOf(curator_employer_objects['posts'][company_id])=='array'){
		curator_employer_set_posts(curator_employer_objects['posts'][company_id]);
		return;
	}

	select_add({
		'list'		: 'info_post',
		'options'	: [[-1,'Получение данных...']]
	});
	$('info_post').set('disabled',true).addClass('disabled');
	$('info_post_filter').set('disabled',true).addClass('disabled');

	new axRequest({
		url : '/main/ajax/employer',
		data:{
			'action':'curator.company.posts',
			'company_id': company_id
		},
		silent: true,
		waiter: true,
		callback: function(success, status, data){
			if(success){
				curator_employer_objects['posts'][data['company_id']] = data['posts'];
				curator_employer_set_posts(data['posts']);
			}
		}
	}).request();

}//end function



//Построение списка должностей
function curator_employer_set_posts(data, noStoreData){
	$('info_post').empty();
	select_add({
		'list'		: 'info_post',
		'options'	: data,
		'key'		: 'post_uid',
		'value'		: 'post_name'
	});
	$('info_post_filter').set('disabled',false).removeClass('disabled');
	$('info_post').set('disabled',false).removeClass('disabled');
	if(!noStoreData){
		$('info_post').store('data',data);
		$('info_post_filter').set('value','');
	}
	$('info_post').selectedIndex = -1;
}//end function



function curator_employer_filter_posts(){
	var funct = function(){
		var data = $('info_post').retrieve('data');
		var result = [];
		var text, value = String($('info_post_filter').value).toLowerCase();
		for(var i=0; i<data.length; i++){
			text = String(String(data[i]['post_name']).split('/')[0]).toLowerCase();
			if(String(text).contains(value)) result.push(data[i]);
		}
		curator_employer_set_posts(result, true);
	};
	$('info_post_filter').addEvent('change', funct).addEvent('keyup', funct);
	$('info_post').addEvent('change', function(){
		$('info_post_filter').value = String(String(select_getText('info_post')).split('/')[0]).trim();
	});
}//end function




//Предварительный просмотр
function curator_employer_preview(){
	if(!curator_employer_objects['form'].validate()) return;
	curator_employer_objects['anket'] = {
		'company_id': select_getValue('info_company'),
		'post_uid': select_getValue('info_post'),
		'order_no': $('info_order_no').value,
		'post_from': $('info_order_date').value,
		'first_name': $('info_first_name').value,
		'last_name': $('info_last_name').value,
		'middle_name': $('info_middle_name').value,
		'birth_date': $('info_birth_date').value,
		'phone': $('info_phone').value,
		'email': $('info_email').value,
		'work_computer': ($('info_work_computer').checked ? '1':'0'),
		'need_accesscard': ($('info_need_accesscard').checked ? '1':'0'),
		'comment': $('info_comment').value
	};
	curator_employer_objects['preview'] = {
		'company': select_getText('info_company'),
		'post': select_getText('info_post'),
		'order_no': ($('info_order_no').value != '' ? $('info_order_no').value : '-[Не указан]-'),
		'order_date': $('info_order_date').value,
		'first_name': $('info_first_name').value,
		'last_name': $('info_last_name').value,
		'middle_name': $('info_middle_name').value,
		'birth_date': $('info_birth_date').value,
		'phone': ($('info_phone').value ? $('info_phone').value : '-[Не указан]-'),
		'email': ($('info_email').value ? $('info_email').value : '-[Не указан]-'),
		'work_computer': ($('info_work_computer').checked ? 'Да':'Нет'),
		'need_accesscard': ($('info_need_accesscard').checked ? 'Да':'Нет'),
		'comment': ($('info_comment').value !='' ? $('info_comment').value : '-[Не указан]-')
	};

	var id;
	for(var key in curator_employer_objects['preview']){
		id = 'info_'+key+'_preview';
		if(!$(id))continue;
		$(id).set('text',curator_employer_objects['preview'][key]);
	}

	curator_employer_objects['anketarea']['li'].hide();
	curator_employer_objects['previewarea']['li'].show();
}//end function



function curator_employer_anket(){
	curator_employer_objects['anketarea']['li'].show();
	curator_employer_objects['previewarea']['li'].hide();
}//end function



//Отправка анкеты
function curator_employer_anket_send(){
	var anket = Object.merge({},curator_employer_objects['anket'],{'action':'curator.add.employer'});
	new axRequest({
		url : '/main/ajax/employer',
		data: anket,
		silent: true,
		waiter: true,
		callback: function(success, status, data){
			if(success){
				curator_employer_objects['previewarea']['li'].hide();
				$('anket_wrapper').hide();
				$('anket_complete').show();
			}
		}
	}).request();
}//end function