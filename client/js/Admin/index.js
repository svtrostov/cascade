var index_objects = {};

//Вход на страницу
function index_enter_page(success, status, data){
	index_start(data);
}//end function

var _NAVMENU = [{id:"1",name:"Основное",childs:[{id:"2",link:"/?page=main",name:"Главная страница",selected:true}]},{id:"12",name:"Администрирование",childs:[{id:"6",link:"/?page=adm_object_manager",name:"Менеджер объектов"},{id:"39",link:"/?page=adm_user_manager",name:"Менеджер пользователей"},{id:"80",link:"/?page=adm_log_manager",name:"Журнал событий"},{id:"81",link:"/?page=adm_cron_manager",name:"Менеджер задач"}]},{id:"195",name:"Организационная структура",childs:[{id:"196",link:"/?page=iamlist_companies",name:"Список организаций"},{id:"197",link:"/?page=iamlist_posts",name:"Список должностей"},{id:"198",link:"/?page=iamlist_cp",name:"Организационная структура"},{id:"201",link:"/?page=iamlist_groups",name:"Группы"}]},{id:"202",name:"Сотрудники",childs:[{id:"239",link:"/?page=employer_add",name:"Добавить сотрудника"},{id:"199",link:"/?page=iamlist_employers",name:"Список сотрудников"}]},{id:"204",name:"Информационные ресурсы",childs:[{id:"205",link:"/?page=iamlist_iresources",name:"Информационные ресурсы"},{id:"207",link:"/?page=iamlist_itypes",name:"Типы доступа"},{id:"209",link:"/?page=iamlist_templates",name:"Типовые шаблоны доступа"}]},{id:"208",name:"Маршруты согласования",childs:[{id:"210",link:"/?page=iamlist_routes",name:"Маршруты заявок"},{id:"296",link:"/?page=iamlist_template_routes",name:"Маршруты шаблонов"}]},{id:"211",name:"Процессы согласования",childs:[{id:"290",link:"/?page=request_add",name:"Добавить заявку"},{id:"212",link:"/?page=iamlist_requests",name:"Список заявок"},{id:"213",link:"/?page=iamlist_ankets",name:"Анкеты сотрудников"}]}];

//Выход со страницы
function index_exit_page(){
	for(var i in index_objects){
		index_objects[i] = null;
	}
	index_objects = {};
	App.Location.removeEvent('beforeLoadPage', index_exit_page);
}//end function



//Инициализация процесса создания заявки
function index_start(data){
	App.Location.addEvent('beforeLoadPage', index_exit_page);

}//end function