var manager_menu_objects = {};

//Вход на страницу
function manager_menu_enter_page(success, status, data){
	manager_menu_start(data);
}//end function


//Выход со страницы
function manager_menu_exit_page(){
	if(manager_menu_objects['treedesign']){
		manager_menu_objects['treedesign'].clear();
	}
	for(var i in manager_menu_objects){
		manager_menu_objects[i] = null;
	}
	manager_menu_objects = {};
	App.Location.removeEvent('beforeLoadPage', manager_menu_exit_page);
}//end function



//Инициализация процесса создания заявки
function manager_menu_start(data){
	App.Location.addEvent('beforeLoadPage', manager_menu_exit_page);
	manager_menu_objects['menu_id'] = data['menu_id'];
	manager_menu_objects['treedesign'] = new jsTreeMenuDesign({
		'parent': 'designmenu_area',
		'menu_id': manager_menu_objects['menu_id'],
		'onselectnode': manager_menu_selectnode
	});

	manager_menu_objects['iteminfo'] = build_blockitem({
		'parent': 'designmenu_info_area',
		'title'	: 'Настройки элемента меню'
	});
	$('tmpl_item_info').show().inject(manager_menu_objects['iteminfo']['container']);
	manager_menu_objects['iteminfo']['li'].hide();

	manager_menu_objects['itemnew'] = build_blockitem({
		'list': manager_menu_objects['iteminfo']['list'],
		'title'	: 'Добавить элемент меню'
	});
	manager_menu_objects['itemnew']['li'].hide();
	$('tmpl_item_new').show().inject(manager_menu_objects['itemnew']['container']);

	manager_menu_objects['splitter'] = set_splitter_h({
		'left'		: $('designmenu_area'),
		'right'		: $('designmenu_info'),
		'splitter'	: $('designmenu_splitter'),
		'parent'	: $('designmenu_splitter').getParent('.contentareafull')
	});

	manager_menu_dataset(data);
}//end function




//Выбрана нода в меню
function manager_menu_selectnode(node){
	if(typeOf(node)!='object'){
		manager_menu_objects['iteminfo']['li'].hide();
		return;
	}
	$('info_item_id').value = node['item_id'];
	$('info_href').value = node['href'];
	$('info_title').value = node['title'];
	$('info_desc').value = node['desc'];
	$('info_class').value = node['class'];
	select_set('info_access_object_id',node['access_object_id']);
	select_set('info_parent_id',node['parent_id']);
	select_set('info_target',node['target']);
	select_set('info_is_folder',(node['is_folder']?'1':'0'));
	$('info_is_lock').checked = String(node['is_lock'])=='1'?true:false;
	manager_menu_change_item_type();
	manager_menu_objects['selectednode'] = node;
	$('button_delete_item').show();
	manager_menu_objects['iteminfo']['li'].show();
	manager_menu_objects['itemnew']['li'].hide();
}//end function



//Выбор типа элемента
function manager_menu_change_item_type(){
	var is_folder = (String(select_getValue('info_is_folder')) == '1' ? true : false);
	if(is_folder){
		$('info_href').addClass('disabled').set('readonly',true);
		$('info_target').addClass('disabled').set('disabled',true);
	}else{
		$('info_href').removeClass('disabled').set('readonly',false);
		$('info_target').removeClass('disabled').set('disabled',false);
	}
}//end function






//Применение данных, отрисовка меню
function manager_menu_dataset(nodes, default_item_id){
	manager_menu_objects['selectednode'] = null;
	manager_menu_objects['iteminfo']['li'].hide();
	$('button_delete_item').hide();
	if(typeOf(nodes['menu'])!='array'){
		App.message('Ошибка данных','Получены некорректные данные, работа с менеджером меню невозможна.<br/>Попробуйте обновить страницу.','WARNING');
		manager_menu_objects['treedesign'].clear();
		$('designmenu_wrapper').hide();
		return;
	}

	//Построение списка разделов
	select_add({
		'list': ['info_parent_id','add_parent_id'],
		'key': 'item_id',
		'value': 'title',
		'options': [{'item_id':'0','title':'=[Без раздела]='}].combine(nodes['menu'].filterSelect('is_folder','1',0)),
		'clear': true
	});

	//Построение списка ACL объектов
	select_add({
		'list': ['info_access_object_id','add_access_object_id'],
		'key': 'object_id',
		'value': 'namedesc',
		'options': [{'object_id':'0','namedesc':'=[Не привязан к объекту ACL]='}].combine((typeOf(nodes['aobjects'])=='array' ? nodes['aobjects'] : [])),
		'clear': true
	});

	if(nodes['default_id']) default_item_id = nodes['default_id'];

	//Построение меню
	manager_menu_objects['treedesign'].build(nodes['menu']);
	if(default_item_id) manager_menu_objects['treedesign'].selectNodeById(default_item_id, true);

}//end function






//Нажата кнопка добавления элемента меню
function manager_menu_item_new(){
	if(!manager_menu_objects['treedesign']) return;
	if(typeOf(manager_menu_objects['treedesign'].selectedNode)=='element'){
		manager_menu_objects['treedesign'].selectedNode.removeClass('selected');
	}
	manager_menu_objects['selectednode'] = null;
	manager_menu_objects['iteminfo']['li'].hide();
	manager_menu_objects['itemnew']['li'].show();
	$('button_delete_item').hide();
}//end function





//Изменение элемента меню
function manager_menu_item_change_save(){

	new axRequest({
		url : '/admin/ajax/manager_menu',
		data:{
			'action':'menu.item.edit',
			'menu_id': manager_menu_objects['menu_id'],
			'item_id': $('info_item_id').value,
			'parent_id': select_getValue('info_parent_id'),
			'access_object_id': select_getValue('info_access_object_id'),
			'is_lock': ($('info_is_lock').checked?'1':'0'),
			'is_folder': select_getValue('info_is_folder'),
			'href': $('info_href').value,
			'target': select_getValue('info_target'),
			'title': $('info_title').value,
			'desc': $('info_desc').value,
			'class': $('info_class').value
		},
		silent: false,
		waiter: true,
		callback: function(success, status, data){
			if(success){
				manager_menu_dataset(data);
			}
		}
	}).request();

}//end function





//Добавление элемента меню
function manager_menu_item_new_save(){

	new axRequest({
		url : '/admin/ajax/manager_menu',
		data:{
			'action':'menu.item.new',
			'menu_id': manager_menu_objects['menu_id'],
			'parent_id': select_getValue('add_parent_id'),
			'access_object_id': select_getValue('add_access_object_id'),
			'is_lock': ($('add_is_lock').checked?'1':'0'),
			'is_folder': select_getValue('add_is_folder'),
			'href': $('add_href').value,
			'target': select_getValue('add_target'),
			'title': $('add_title').value,
			'desc': $('add_desc').value,
			'class': $('add_class').value
		},
		silent: false,
		waiter: true,
		callback: function(success, status, data){
			if(success){
				manager_menu_dataset(data);
			}
		}
	}).request();

}//end function





//Удаление элемента меню
function manager_menu_item_delete(){

	if(typeOf(manager_menu_objects['selectednode'])!='object' || String(manager_menu_objects['selectednode']['item_id']) != String($('info_item_id').value)) return;
	var item_id = String($('info_item_id').value);

	App.message(
		'Подтвердите действие',
		'Вы действительно хотите удалить выбранный пункт меню?<br/>Если удаляется раздел, все дочерние элементы будут перемещены в корень меню',
		'CONFIRM',
		function(){
			new axRequest({
				url : '/admin/ajax/manager_menu',
				data:{
					'action':'menu.item.delete',
					'menu_id': manager_menu_objects['menu_id'],
					'item_id': item_id,
				},
				silent: false,
				waiter: true,
				callback: function(success, status, data){
					if(success){
						manager_menu_dataset(data);
					}
				}
			}).request();
		}
	);

}//end function

