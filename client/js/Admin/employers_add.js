var employers_add_objects = {};

//Вход на страницу
function employers_add_enter_page(success, status, data){
	employers_add_start(data);
}//end function


//Выход со страницы
function employers_add_exit_page(){
	if(employers_add_objects['form']) employers_add_objects['form'].destroy();
	for(var i in employers_add_objects){
		employers_add_objects[i] = null;
	}
	employers_add_objects = {};
	App.Location.removeEvent('beforeLoadPage', employers_add_exit_page);
}//end function



//Инициализация процесса
function employers_add_start(data){
	App.Location.addEvent('beforeLoadPage', employers_add_exit_page);

	employers_add_objects['employer'] = null;

	//Создание календарей
	new Picker.Date($$('.calendar_input'), {
		timePicker: false,
		positionOffset: {x: 0, y: 2},
		pickerClass: 'calendar',
		useFadeInOut: false
	});


	//Построение анкеты
	employers_add_objects['anketarea'] = build_blockitem({
		'parent': 'anket_area',
		'title'	: 'Заполните анкету сотрудника'
	});
	$('tmpl_anket').show().inject(employers_add_objects['anketarea']['container']);

	//Построение предпросмотра
	employers_add_objects['previewarea'] = build_blockitem({
		'parent': 'anket_area',
		'title'	: 'Предварительный просмотр'
	});
	employers_add_objects['previewarea']['li'].hide();
	$('tmpl_preview').show().inject(employers_add_objects['previewarea']['container']);


	//Построение предпросмотра
	employers_add_objects['completearea'] = build_blockitem({
		'parent': 'anket_area',
		'title'	: 'Сотрудник успешно добавлен'
	});
	employers_add_objects['completearea']['li'].hide();
	$('tmpl_complete').show().inject(employers_add_objects['completearea']['container']);


	//Проверка формы
	employers_add_objects['form'] = new jsValidator('tmpl_anket');
	employers_add_objects['form']
	.required('info_first_name').alpha('info_first_name')
	.required('info_last_name').alpha('info_last_name')
	.required('info_middle_name').alpha('info_middle_name')
	.required('info_birth_date').date('info_birth_date')
	.required('info_phone').phone('info_phone')
	.email('info_email');

}//end function




//Предварительный просмотр
function employers_add_preview(){
	if(!employers_add_objects['form'].validate()) return;
	employers_add_objects['anket'] = {
		'first_name': $('info_first_name').value,
		'last_name': $('info_last_name').value,
		'middle_name': $('info_middle_name').value,
		'birth_date': $('info_birth_date').value,
		'phone': $('info_phone').value,
		'email': $('info_email').value
	};
	employers_add_objects['preview'] = {
		'first_name': $('info_first_name').value,
		'last_name': $('info_last_name').value,
		'middle_name': $('info_middle_name').value,
		'birth_date': $('info_birth_date').value,
		'phone': ($('info_phone').value ? $('info_phone').value : '-[Не указан]-'),
		'email': ($('info_email').value ? $('info_email').value : '-[Не указан]-')
	};

	var id;
	for(var key in employers_add_objects['preview']){
		id = 'info_'+key+'_preview';
		if(!$(id))continue;
		$(id).set('text',employers_add_objects['preview'][key]);
	}

	employers_add_objects['anketarea']['li'].hide();
	employers_add_objects['previewarea']['li'].show();
}//end function



//Заполнение анкеты
function employers_add_anket(){
	employers_add_objects['anketarea']['li'].show();
	employers_add_objects['previewarea']['li'].hide();
}//end function



//Отправка анкеты
function employers_add_anket_send(){
	var anket = Object.merge({},employers_add_objects['anket'],{'action':'employers.add'});
	new axRequest({
		url : '/admin/ajax/employers',
		data: anket,
		silent: true,
		waiter: true,
		callback: function(success, status, data){
			if(success){
				employers_add_complete(data);
				employers_add_objects['previewarea']['li'].hide();
				employers_add_objects['completearea']['li'].show();
			}
		}
	}).request();
}//end function




//Сотрудник добавлен
function employers_add_complete(data){

	if(typeOf(data)!='object') return;
	if(typeOf(data['employer'])!='object') return;
	employers_add_objects['employer'] = data['employer'];
	var id;
	for(var key in data['employer']){
		id = 'info_'+key+'_complete';
		if(!$(id))continue;
		$(id).set('text',data['employer'][key]);
	}

}//end function


//Переход в карточку сотрудника
function employers_add_to_card(){
	if(typeOf(employers_add_objects['employer'])!='object') return;
	App.Location.doPage({
		'href': '/admin/employers/info?employer_id='+employers_add_objects['employer']['employer_id'],
		'url': '/admin/employers/info',
		'data': {
			'employer_id': employers_add_objects['employer']['employer_id']
		},
		'method':'get'
	});
}//end function