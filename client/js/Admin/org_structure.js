var org_structure_objects = {};

//Вход на страницу
function org_structure_enter_page(success, status, data){
	org_structure_start(data);
}//end function


//Выход со страницы
function org_structure_exit_page(){
	if(org_structure_objects['orgchart'])org_structure_objects['orgchart'].empty();
	for(var i in org_structure_objects){
		org_structure_objects[i] = null;
	}
	org_structure_objects = {};
	App.Location.removeEvent('beforeLoadPage', org_structure_exit_page);
}//end function



//Инициализация
function org_structure_start(data){
	App.Location.addEvent('beforeLoadPage', org_structure_exit_page);

	org_structure_objects['company_id']=0;
	org_structure_objects['companies_array']=null;
	org_structure_objects['companies_assoc']=null;
	org_structure_objects['posts_array']=null;
	org_structure_objects['posts_assoc']=null;
	org_structure_objects['org_data'] = [];
	org_structure_objects['org_change'] = false;

	org_structure_objects['orgchart'] = new jsOrgChart('org_chart_tree', {});
	org_structure_objects['orgchart'].addEvents({
		'selectNode': org_structure_select_block,
		'dblclickNode': org_structure_edit_block,
		'change': function(){org_structure_set_change_status(true);}
	});
	$('org_post_select').addEvent('dblclick', org_structure_post_complete);

	org_structure_objects['orgchartdragscroll'] = new Drag('org_chart_area', {
		style: false,
		invert: true,
		modifiers: {x: 'scrollLeft', y: 'scrollTop'}
	});
	org_structure_objects['orgchartscroll'] =  new Scroller($('org_chart_area'), {area: 70, velocity: 1});
	$('org_chart_area').addEvents({
		'scroll':function(){if(org_structure_objects['orgchartdragscroll'])org_structure_objects['orgchartdragscroll'].cancel();}
	});
	org_structure_objects['zoomslider'] = new Slider($('zoom_slider'), $('zoom_slider_knob'), {
		range: [20, 1],
		initialStep: 10,
		steps: 19,
		mode: 'vertical',
		onChange: function(value){
			if(value){
				var zoom = value*0.1;
				$('org_chart_tree').style.zoom = (value*10)+'%';
				//$('org_chart_tree').setStyle('-moz-transform','scale('+zoom+')');
				//$('org_chart_tree').setStyle('-webkit-transform','scale('+zoom+')');

				$('zoom_slider_title').set('text',(value*10));
				org_structure_objects['zoomvolume'] = value;
			}
		}
	});
	org_structure_objects['zoomvolume'] = 10;
	$('zoom_slider_knob').addEvent('dblclick',function(){org_structure_objects['zoomslider'].set(10);});
	$('zoom_slider_plus').addEvent('click',function(){org_structure_objects['zoomslider'].set(org_structure_objects['zoomvolume']+1);});
	$('zoom_slider_minus').addEvent('click',function(){org_structure_objects['zoomslider'].set(org_structure_objects['zoomvolume']-1);});
	$('org_chart_area').addEvent('mousewheel',function(event){
		var mode = (event.wheel < 0);
		org_structure_objects['zoomslider'].set(org_structure_objects['zoomvolume'] + (mode ? -1 : 1));
		event.stop();
	});

	org_structure_setdata(data);

	['change','keyup'].each(function(ev){
		$('org_post_filter').addEvent(ev,function(){
			var post_id = (typeOf(org_structure_objects['selectedNodeElement'])=='element' ? org_structure_objects['selectedNodeElement'].retrieve('post_id') : 0);
			select_filter('org_post_select',this.get('value'), post_id);
		});
	});
	$('org_post_filter_button').addEvent('click',function(){$('org_post_filter').set('value','').fireEvent('change');});


}//end function




//Применение данных
function org_structure_setdata(data){

	if(typeOf(data)!='object') return;

	//Выбранная организация
	if(data['company_id']){
		org_structure_objects['company_id'] = data['company_id'];
		select_set('org_company_select',data['company_id']);
	}//Выбранная организация


	//Список организаций
	if(typeOf(data['companies'])=='array'){
		data['companies'].sort(function(a,b){if(a['full_name']>b['full_name'])return 1;return -1;});
		org_structure_objects['companies_array']=[];
		org_structure_objects['companies_assoc']={};
		for(var i=0;i<data['companies'].length;i++){
			org_structure_objects['companies_array'].push(data['companies'][i]);
			org_structure_objects['companies_assoc'][data['companies'][i]['company_id']]=data['companies'][i]['full_name'];
		}
		select_add({
			'list': 'org_company_select',
			'key': 'company_id',
			'value': 'full_name',
			'options': org_structure_objects['companies_array'],
			'default': org_structure_objects['company_id'],
			'clear': true
		});
	}//Список организаций


	//Список должностей
	if(typeOf(data['posts'])=='array'){
		data['posts'].sort(function(a,b){if(a['full_name']>b['full_name'])return 1;return -1;});
		org_structure_objects['posts_array']=[];
		org_structure_objects['posts_assoc']={};
		for(var i=0;i<data['posts'].length;i++){
			org_structure_objects['posts_array'].push(data['posts'][i]);
			org_structure_objects['posts_assoc'][data['posts'][i]['post_id']]=data['posts'][i]['full_name'];
		}
	}//Список должностей




	//Данные организационной структуры
	if(typeOf(data['org_data'])=='array'){
		if(
			typeOf(org_structure_objects['companies_assoc'])=='object' &&
			org_structure_objects['companies_assoc'][org_structure_objects['company_id']]
		){
			var company_name = org_structure_objects['companies_assoc'][org_structure_objects['company_id']];
			$('orgstructure_wrapper_title').set('text','Настройки организационной структуры '+company_name);
			org_structure_objects['orgchart'].setData(company_name, data['org_data']);
			org_structure_objects['orgchart'].rootUL.getElement('>li').addEvents({
				'mouseover': org_structure_objects['orgchartscroll'].start.bind(org_structure_objects['orgchartscroll']),
				'mouseout': org_structure_objects['orgchartscroll'].stop.bind(org_structure_objects['orgchartscroll'])
			});
			org_structure_set_change_status(false);
			org_structure_select_block(null);
		}
	}//Данные организационной структуры

}//end function



//Развертывание окна организационной структуры на весь экран
function org_structure_fullscreen(){
	var panel = $('orgstructure_wrapper').getParent('.page_org_structure');
	var title = $('orgstructure_wrapper_title').getParent('.titlebar');
	if(!org_structure_objects['orgpanel']){
		org_structure_objects['orgpanel'] = {
			'parent': panel.getParent()
		}
	}
	if(title.hasClass('expanded')){
		title.removeClass('expanded').addClass('normal');
		panel.inject(org_structure_objects['orgpanel']['parent']);
	}else{
		title.removeClass('normal').addClass('expanded');
		panel.inject(document.body);
	}
}//end function




//Выбран блок
function org_structure_select_block(el){
	if(!el || typeOf(el)!='element' || el.hasClass('noselect')){
		$('org_chart_tool_block').hide();
		org_structure_objects['selectedNodeElement'] = null;
	}else{
		org_structure_objects['selectedNodeElement'] = el;
		$('org_chart_tool_block').show();
	}
}//end function




//Удаление блока
function org_structure_delete_block(){
	if(
		typeOf(org_structure_objects['selectedNodeElement'])!='element' ||
		!org_structure_objects['orgchart'] ||
		typeOf(org_structure_objects['orgchart'].rootUL)!='element' ||
		typeOf(org_structure_objects['posts_array']) != 'array'
	) return;

	App.message(
		'Подтвердите действие',
		'Вы действительно хотите удалить выбранную должность, включаяя все вложенные должности?',
		'CONFIRM',
		function(){
			var li = org_structure_objects['selectedNodeElement'].getParent();
			var ul = li.getParent();
			if(!ul || !li) return;
			li.destroy();
			if(!ul.getChildren('li').length) ul.destroy();
			org_structure_objects['selectedNodeElement'] = null;
			$('org_chart_tool_block').hide();
			org_structure_set_change_status(true);
		}
	);
}//end function




//Добавление нового сотрудника
function org_structure_add_block(){
	org_structure_post_open(false);
}//end function


//Изменение должности сотрудника
function org_structure_edit_block(){
	org_structure_post_open(true);
}//end function



//Открытие окна выбора должности
function org_structure_post_open(is_edit){
	if(
		(is_edit && typeOf(org_structure_objects['selectedNodeElement'])!='element') ||
		!org_structure_objects['orgchart'] ||
		typeOf(org_structure_objects['orgchart'].rootUL)!='element' ||
		typeOf(org_structure_objects['posts_array']) != 'array'
	) return;
	var post_id = (is_edit ? org_structure_objects['selectedNodeElement'].retrieve('post_id') : '0');
	var post_name = (typeOf(org_structure_objects['selectedNodeElement'])=='element' ? org_structure_objects['selectedNodeElement'].retrieve('full_name') : null);
	org_structure_objects['nodeAction'] = (is_edit ? 'edit' : 'add');
	$('post_action_title').set('html','Сейчас Вы '+(is_edit ? '<font color="blue">изменяете должность</font>: '+post_name : '<font color="blue">добавляете должность</font> '+(post_name ? ', <font color="green">где руководитель</font>: '+post_name : '<font color="red">не имеющую вышестоящего руководителя</font>')));
	$('orgstructure_wrapper').hide();
	$('orgstructure_post_window').show();
	var p, used_posts = [];
	var aa = org_structure_objects['orgchart'].rootUL.getElements('a');
	for(var i=0;i<aa.length;i++){
		if(aa[i].hasClass('header')) continue;
		p = aa[i].retrieve('post_id');
		if(!p) continue;
		if(is_edit && String(p) == String(post_id)){
			continue;
		}
		used_posts.push(p);
	}
	var posts = org_structure_objects['posts_array'].clone();
	select_add({
		'list': 'org_post_select',
		'key': 'post_id',
		'value': 'full_name',
		'options': posts.filterSelect({
			'post_id':{
				'value': used_posts,
				'condition': 'NOTIN'
			}
		}),
		'default': (is_edit ? post_id : 0),
		'clear': true,
		'filter': $('org_post_filter').get('value')
	});
	if(is_edit && $('org_post_select').options.length>0 && $('org_post_select').selectedIndex > -1){
		var oh = 18;
		var h = Math.max(0, $('org_post_select').selectedIndex * oh - $('org_post_select').getCoordinates().height);
		$('org_post_select').scrollTo(0, h);
	}
	$('org_structure_post_complete_button').set('text',(is_edit?'Изменить должность':'Добавить должность'));
}//end function




//Закрытие окна выбора должности
function org_structure_post_close(){
	$('orgstructure_post_window').hide();
	$('orgstructure_wrapper').show();
}//end function




//Выбрана должность
function org_structure_post_complete(){
	if(
		!org_structure_objects['orgchart'] ||
		$('org_post_select').selectedIndex == -1
	) return org_structure_post_close();
	var post_id = select_getValue($('org_post_select'));
	var post_name = select_getText($('org_post_select'));
	if(org_structure_objects['nodeAction'] == 'edit'){
		if(typeOf(org_structure_objects['selectedNodeElement'])!='element') return org_structure_post_close();
		org_structure_objects['selectedNodeElement'].store('post_id',post_id);
		org_structure_objects['selectedNodeElement'].set('text',post_name);
		org_structure_set_change_status(true);
		return org_structure_post_close();
	}

	var my_li = (typeOf(org_structure_objects['selectedNodeElement'])!='element' ? org_structure_objects['orgchart'].rootUL.getFirst('li') : org_structure_objects['selectedNodeElement'].getParent('li'));
	if(typeOf(my_li)!='element') return org_structure_post_close();
	var my_ul = my_li.getFirst('ul');
	if(!my_ul) my_ul = new Element('ul').inject(my_li);
	var new_li = org_structure_objects['orgchart'].createLINode(my_ul,{'full_name':post_name,'post_id':post_id});
	org_structure_set_change_status(true);
	org_structure_post_close();
}//end function





//Установка статуса изменения структуры
function org_structure_set_change_status(is_changed){
	org_structure_objects['org_change'] = is_changed;
	if(!is_changed){
		App.Location.setBeforeExitFunction(null);
		return;
	}
	App.Location.setBeforeExitFunction(function(data){
		App.echo(data);
		org_structure_check_change(function(){
			App.Location.setBeforeExitFunction(null);
			App.Location.doPage(data);
		});
	});
}//end function




//Проверка была ли сохраненаорганизационная структура
function org_structure_check_change(callback){
	if(!org_structure_objects['org_change']) return callback();
	App.message(
		'Подтвердите действие',
		'Вы вносили изменения в организационную диаграмму, но не сохранили ее, продолжить без сохранения?',
		'CONFIRM',
		function(){
			return callback();
		}
	);
}//end function



//Смена организации
function org_structure_company_change(){
	return org_structure_check_change(do_org_structure_load);
}



//Смена организации
function org_structure_reload(){
	return org_structure_check_change(do_org_structure_load);
}



//Загрузка организационной структуры
function do_org_structure_load(){
	var company_id = select_getValue('org_company_select');
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
				org_structure_setdata(data);
				org_structure_select_block(null);
			}
		}
	}).request();
}//end function




//Сохранение организационной структуры
function org_structure_save(){
	App.message(
		'Подтвердите действие',
		'Вы действительно хотите сохранить организационную структуру?',
		'CONFIRM',
		function(){
			do_org_structure_save();
		}
	);
}
function do_org_structure_save(){

	var post_id, parent, parent_id, posts=[], parents=[];
	var aa = org_structure_objects['orgchart'].rootUL.getElements('a');
	for(var i=0;i<aa.length;i++){
		if(aa[i].hasClass('header')) continue;
		post_id = parseInt(aa[i].retrieve('post_id'));
		if(!post_id) continue;
		parent = aa[i].getParent('ul').getParent('li').getElement('a');
		if(!parent) continue;
		parent_id = (parent.hasClass('header') ? 0 : parseInt(parent.retrieve('post_id')));
		posts.push(post_id);
		parents.push(parent_id);
	}

	var company_id = select_getValue('org_company_select');
	new axRequest({
		url : '/admin/ajax/org',
		data:{
			'action':'org.structure.save',
			'company_id': company_id,
			'p':posts,
			'b':parents
		},
		silent: false,
		waiter: true,
		callback: function(success, status, data){
			if(success){
				org_structure_setdata(data);
				org_structure_set_change_status(false);
			}
		}
	}).request();
}//end function




//Получение информации по выбранной должности
function org_structure_info_block(){
	var company_id = select_getValue('org_company_select');
	new axRequest({
		url : '/admin/ajax/org',
		data:{
			'action':'org.post.info',
			'company_id': company_id,
			'post_uid': (typeOf(org_structure_objects['selectedNodeElement'])=='element' ? org_structure_objects['selectedNodeElement'].retrieve('post_uid') : null)
		},
		silent: false,
		waiter: true,
		callback: function(success, status, data){
			if(success){
				org_structure_info_build_employers(data['employers']);
			}
		}
	}).request();
}//end function



//Построение списка $('orgstructure_info_window').hide();
function org_structure_info_build_employers(data){

	//Должности не найдены
	if(typeOf(data)!='array') return;

	$('info_company_name').setValue(select_getText('org_company_select'));
	$('info_post_name').setValue(org_structure_objects['selectedNodeElement'].retrieve('full_name'));

	var area = $('employer_list_area');
	var radio, employer_id, employer_name, post_from, post_to;

	if(!data.length){
		$('employer_search_none').show();
		$('employer_search_results').hide();
	}else{
		$('employer_search_none').hide();
		$('employer_search_results').show();
	}

	area.empty();

	//Построение списка
	for(var indx=0; indx<data.length; indx++){

		employer_id = data[indx]['employer_id'];
		employer_name = data[indx]['employer_name'];
		post_from = data[indx]['post_from'];
		post_to = data[indx]['post_to'];

		radio = new Element('div',{'class':'radioarea'}).inject(area).set('html',
			'<div class="line"><span>Идентификатор:</span>'+employer_id+'</div>'+
			'<div class="line"><span>Сотрудник:</span><a target="_blank" class="mailto" href="/admin/employers/info?employer_id='+employer_id+'">'+employer_name+'</a></div>'+
			'<div class="line"><span>Начало работы:</span>'+post_from+'</div>'
		);
	}//Построение списка

	$('orgstructure_wrapper').hide();
	$('orgstructure_info_window').show();

}//end function



//Закрытие окна списка сотрудников
function org_structure_info_close(){
	$('orgstructure_info_window').hide();
	$('orgstructure_wrapper').show();
}$('orgstructure_info_window').hide();
