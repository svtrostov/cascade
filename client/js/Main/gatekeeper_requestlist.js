var gatekeeper_requestlist_objects = {};


//Вход на страницу
function gatekeeper_requestlist_enter_page(success, status, data){
	gatekeeper_requestlist_start();
	gatekeeper_requestlist_build_list(data);
	build_scrolldown($('requestlist_area'), 50);
}//end function



//Выход со страницы
function gatekeeper_requestlist_exit_page(){
	$('requestlist').destroy();
	for(var i in gatekeeper_requestlist_objects){
		gatekeeper_requestlist_objects[i] = null;
	}
	gatekeeper_requestlist_objects = {};
	App.Location.removeEvent('beforeLoadPage', gatekeeper_requestlist_exit_page);
}//end function



//Инициализация
function gatekeeper_requestlist_start(){
	App.Location.addEvent('beforeLoadPage', gatekeeper_requestlist_exit_page);
	$('button_request_selected').hide();
	$('button_request_selected_none').show();
	gatekeeper_requestlist_objects['selected_request_id'] = 0;
	gatekeeper_requestlist_objects['selected_iresource_id'] = 0;
	gatekeeper_requestlist_objects['selected_request_object'] = null;
}//end function



//Построение списка заявок для согласования
function gatekeeper_requestlist_build_list(data){

	var list = $('requestlist');
	list.empty();

	if(typeOf(data)!='array'||!data.length){
		$('requestlist_wrapper').hide();
		$('requestlist_none').show();
		$('requestlist_title').set('text','Пока нет заявок');
		return;
	}
	$('requestlist_wrapper').show();
	$('requestlist_none').hide();
	$('requestlist_title').set('text','Заявки на рассмотрении: '+data.length);

	var li, role_name, gk_type, employer_name, company_name, post_name, iresource_name, phone, email, request_type;
	//Построение списка заявок
	for(var index=0; index<data.length; index++){
		employer_name = data[index]['employer_name'];
		company_name = data[index]['company_name'];
		post_name = data[index]['post_name'];
		iresource_name = data[index]['iresource_name'];
		phone = String(data[index]['phone']).length > 0 ? data[index]['phone'] : null;
		email = String(data[index]['email']).length > 0 ? data[index]['email'] : null;
		request_type = (String(data[index]['request_type']) == '3' ? '<font color="red">Блокировка доступа</font>' : '<font color="green">Запрос доступа</font>' );
		switch(data[index]['gatekeeper_role']){
			case '1': role_name = 'Согласование заявки'; break;
			case '2': role_name = 'Утверждение заявки'; break;
			case '3': role_name = 'Исполнение заявки'; break;
			default: '-[???]-';
		}
		gk_type = (data[index]['is_assistant'] == '1' ? ' (Вы - заместитель)' : '');
		li = new Element('li').inject(list).set('html',
			'<div class="line"><span>Номер заявки:</span><div>'+data[index]['request_id']+'</div></div>'+
			'<div class="line"><span>Тип заявки:</span><div>'+request_type+'</div></div>'+
			'<div class="line"><span>Заявитель:</span><div>'+employer_name +
			(phone || email ? '<div class="small">'+( phone ? 'Телефон: '+data[index]['phone'] : '')+(phone && email ? '<br/>':'')+(email ? 'Email: <a class="mailto" href="mailto:'+data[index]['email']+'">'+data[index]['email']+'</a>':'')+'</div>' : '')+'</div></div>'+
			'<div class="line"><span>Работает в организации:</span><div>'+data[index]['company_name']+'</div></div>'+
			'<div class="line"><span>Занимает должность:</span><div>'+data[index]['post_name']+'</div></div>'+
			'<div class="line"><span>Информационный ресурс:</span><div>'+data[index]['iresource_name']+'</div></div>'+
			'<div class="line"><span>От Вас ожидается:</span><div>'+role_name+gk_type+'</div></div>'
		).addEvent('click',function(){
			if($(gatekeeper_requestlist_objects['selected_request_object'])) $(gatekeeper_requestlist_objects['selected_request_object']).removeClass('selected');
			this.addClass('selected');
			gatekeeper_requestlist_objects['selected_request_id'] = this.retrieve('request_id');
			gatekeeper_requestlist_objects['selected_iresource_id'] = this.retrieve('iresource_id');
			gatekeeper_requestlist_objects['selected_request_object'] = this;
			$('button_request_selected_none').hide();
			$('button_request_selected').show();
		}).store('request_id',data[index]['request_id']).store('iresource_id',data[index]['iresource_id']);

	}//Построение списка заявок

}//end function


function gatekeeper_requestlist_select_complete(){
	var request_id = parseInt(gatekeeper_requestlist_objects['selected_request_id']);
	var iresource_id = parseInt(gatekeeper_requestlist_objects['selected_iresource_id']);
	if(!request_id || !iresource_id){
		return App.message(
			'Выберите заявку',
			'Для продолжения Вам необходимо сначала выбрать заявку',
			'error'
		);
	}
	App.Location.doPage({
		'href': '/main/gatekeeper/requestinfo?request_id='+request_id+'&iresource_id='+iresource_id,
		'url': '/main/gatekeeper/requestinfo',
		'data': {
			'request_id': request_id,
			'iresource_id': iresource_id
		},
		'method':'get'
	});
}//end function

