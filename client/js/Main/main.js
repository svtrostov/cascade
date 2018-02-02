var main_objects = {};


//Вход на страницу
function main_enter_page(success, status, data){
	main_start(data);
}//end function



//Выход со страницы
function main_exit_page(){
	Element.stopBehaviors();
	for(var i in main_objects){
		main_objects[i] = null;
	}
	main_objects = {};
	App.Location.removeEvent('beforeLoadPage', main_exit_page);
}//end function



//Инициализация процесса создания заявки
function main_start(data){
	App.Location.addEvent('beforeLoadPage', main_exit_page);


	if(typeOf(data)=='object' && data['requests']){
		var requests_count = parseInt(data['requests']);
		var requests_out = requests_count;
		requests_100 = requests_count % 100;
		requests_10 = requests_count % 10;
		if(requests_10==1) requests_out+=' заявка';
		else if(requests_10>1&&requests_10<5&&(requests_100<10||requests_100>14)) requests_out+=' заявки';
		else requests_out+=' заявок';
		$$('.gatekeeper_requests_count_area').each(function(id){id.show();});
		$$('.gatekeeper_requests_count_value').each(function(id){id.set('html', requests_out);});
		Element.startBehaviors();
	}else{
		$$('.gatekeeper_requests_count_area').each(function(id){id.hide();});
	}

}//end function