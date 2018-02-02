var curator_request_objects = {};


//Вход на страницу
function curator_request_enter_page(success, status, data){
	if(
		typeOf(data)!='object' ||
		typeOf(data['companies'])!='array' ||
		!data['companies'].length
	){
		$('stepmaster_contentwrapper').hide();
		$('stepmaster_none').show();
		return false;
	}
	curator_request_start(data);
	curator_request_do_page('first');
	$('button_step_next').hide();
	$('button_ir_trash').hide();
}//end function



//Выход со страницы
function curator_request_exit_page(){
	if(curator_request_objects['ir_selector_table']) curator_request_objects['ir_selector_table'].terminate();
	if(curator_request_objects['search_form']) curator_request_objects['search_form'].destroy();
	if(curator_request_objects['contact_form'])curator_request_objects['contact_form'].destroy();

	for(var i in curator_request_objects){
		curator_request_objects[i] = null;
	}
	curator_request_objects = {};
	App.Location.removeEvent('beforeLoadPage', curator_request_exit_page);
}//end function



//Инициализация процесса создания заявки
function curator_request_start(data){
	App.Location.addEvent('beforeLoadPage', curator_request_exit_page);

	//Построение слайдов
	curator_request_objects['slideshow'] = new jsSlideShow('step_container');

	//Список организаций
	select_add({
		'list'		: 'employer_company',
		'options'	: data['companies'],
		'key'		: 'company_id',
		'value'		: 'company_name'
	});

	curator_request_objects['search_form'] = new jsValidator('step_1');
	curator_request_objects['search_form']
	.minValue('employer_company',1,'Выберите организацию')
	.required('employer_name').minLength('employer_name',2).alpha('employer_name');

	curator_request_objects['contact_form'] = new jsValidator('step_3');
	curator_request_objects['contact_form'].phone('input_ir_phone').email('input_ir_email');

	//Инициализация таблицы выбора объектов доступа
	curator_request_objects['ir_selector_table'] = new jsTable('ir_selector_table',{
		sectionCollapsible:true,
		columns: [
			{
				width:'30%',
				sortable:false,
				caption: 'Функционал',
				dataSource:'full_name'
			},
			{
				width:'40%',
				sortable:false,
				caption: 'Описание',
				dataSource:'description',
				dataFunction:function(table, cell, text, data){

					var span = new Element('div',{
						'styles':{
							'margin-left':'30px'
						}
					}).set('text',text);

					if(!data['screenshot']||data['screenshot']==''){
						new Element('img',{
							'src':INTERFACE_IMAGES+'/preview_none.png',
						}).inject(cell).setStyles({
							'cursor':'default',
							'float':'left'
						});
						span.inject(cell);
						return '';
					}
					new Element('img',{
						'src':INTERFACE_IMAGES+'/preview_active.png',
					}).inject(cell).setStyles({
						'cursor':'pointer',
						'float':'left'
					}).addEvents({
						'click': function(e){
							preview_irole(data['irole_id']);
						}
					});
					span.inject(cell);
					return '';

				}
			},
			{
				width:'80px',
				sortable:false,
				caption: 'Важность',
				dataSource:'weight',
				styles:{'min-width':'80px'},
				dataStyle:{'text-align':'center'},
				dataFunction:function(table, cell, text, data){
					if(text<3) return 'Низкая';
					if(text<6) return 'Средняя';
					if(text<8) return 'Высокая';
					return 'Критично';
				}
			},
			{
				width:'140px',
				sortable:false,
				caption: 'Текущий доступ',
				dataSource:'ir_current',
				styles:{'min-width':'140px'},
				dataStyle:{'text-align':'center'},
				dataFunction:function(table, cell, text, data){
					if(text == '0') return '---';
					return curator_request_objects['IR_TYPES'].filterResult('full_name', 'item_id', text);
				}
			},
			{
				caption: 'Запросить доступ',
				sortable:false,
				width:'140px',
				dataSource:'ir_types',
				styles:{'min-width':'140px'},
				dataFunction:function(table, cell, text, data){
					var irt = select_add({
						'parent': cell,
						'options': [['0','-- Нет --']]
					});
					for(var i=0; i<text.length;i++){
						select_add({
							'list': irt,
							'options': [[text[i],curator_request_objects['IR_TYPES'].filterResult('full_name', 'item_id', text[i])]]
						});
					}
					select_set(irt, data['ir_selected']);
					irt.store('iresource_id',data['iresource_id']);
					irt.store('irole_id',data['irole_id']);
					irt.addEvent('change',curator_request_select_ir_type);
					return '';
				}
			}
		],
		'dataBackground1': '#fff',
		'dataBackground2': '#fff',
		selectType:0,
		rowFunction: function(table, row, data){
			var color = '#FFAFAF';
			if(data['weight']<3) color = '#FFFFFF';
			else if(data['weight']<6) color = '#FFFDE6';
			else if(data['weight']<8) color = '#FFDEDE';
			return {
				'background': color,
				'bg_recalc': false
			}
		}
	});

	//Выбранная должность
	curator_request_objects['posts_loaded'] = false;
	curator_request_objects['selected_employer_id'] = 0;
	curator_request_objects['selected_post'] = null;
	curator_request_objects['selected_post_object'] = null;
	curator_request_objects['access_for_employer_id'] = 0;
	curator_request_objects['access_for_company_id'] = 0;
	curator_request_objects['access_for_post'] = 0;
	curator_request_objects['IR_LIST'] = null;
	curator_request_objects['IR_REQUEST'] = null;
	curator_request_objects['IR_TYPES'] = null;
	curator_request_objects['IRESOURCES'] = null;
	curator_request_objects['IRESOURCES_COUNT']=0;

	//Обработка событий
	$('button_step_done').addEvent('click',curator_request_event_button).hide();
	$('button_step_prev').addEvent('click',curator_request_event_button).hide();
	$('button_step_next').addEvent('click',curator_request_event_button).show();

	//Обработка событий нажатия клафиши ENTER на поле
	['employer_name'].each(function(item){
		$(item).addEvent('keypress',function(event){
			if(event.code ==13){ 
				curator_request_search_employer();
			}
		});
	});

	curator_request_objects['step_index'] = 1;
	curator_request_objects['step_max'] = 3;
	curator_request_stepmaster_show();
}//end function




//Обработка нажатия на кнопки управления
function curator_request_event_button(event){
	if (!event || (event && typeOf(event.target) != 'element')) return;
	if (event.event.which && event.event.which != 1) return;
	var div = event.target.get('tag') == 'div' ? event.target : event.target.getParent('div');
	var action = 'empty';
	switch(div.id){
		case 'button_step_done': action = 'complete'; break;
		case 'button_step_prev': action = 'prev'; break;
		case 'button_step_next': action = 'next'; break;
	}
	return curator_request_do_page(action);
}//end function




//Навигация по страницам мастера
function curator_request_do_page(action){

	var increment = 0, 
		step_title='',
		process,
		show_next_button = true;

	switch(action){
		case 'empty': return;
		case 'first': curator_request_objects['step_index'] = 1; increment = 0; break;
		case 'next': increment = 1; break;
		case 'prev': increment = -1; break;
		case 'complete': 
			curator_request_irs_save();
			return;
		break;
		default: 
			curator_request_objects['step_index'] = parseInt(action); 
			increment = 0; 
		break;
	}

	var index = curator_request_objects['step_index'] + increment;
	if(index < 1 ) return;
	if(index > curator_request_objects['step_max'] ) return;
	
	if(!$('step_'+index)) return;


	//Обработка перед отображением страницы
	switch(index){

		//Шаг 1: Выбрать должность
		case 1:
			step_title = 'Шаг 1: Выберите сотрудника и занимаемую им должность';
		break;



		//Шаг 2: Формирование заявки
		case 2:
			process = false;
			show_next_button = false;
			step_title = 'Шаг 2: Формирование заявки';
			if(typeOf(curator_request_objects['selected_post'])!='object'){
				App.message('Вы не выбрали должность','Выберите должность, в рамках которой Вы планируете пользоваться запрашиваемым доступом','warning');
				return;
			}
			//Выбранная должность не соответствует должности, для которой формировались объекты доступа
			if(String(curator_request_objects['access_for_post']) != String(curator_request_objects['selected_post']['post_uid'])){
				curator_request_objects['IR_LIST'] = null;
				curator_request_objects['IR_REQUEST'] = null;
				curator_request_irs_clear();
				curator_request_objects['access_for_employer_id'] = curator_request_objects['selected_post']['employer_id'];
				curator_request_objects['access_for_post'] = curator_request_objects['selected_post']['post_uid'];
				curator_request_objects['access_for_company_id'] = curator_request_objects['selected_post']['company_id'];
				$$('.title_employer_name').each(function(item){item.set('text',curator_request_objects['selected_post']['employer_name']);});
				$('ir_list').empty();
				process = true;
			}else{
				if(typeOf(curator_request_objects['IR_LIST'])!='array'){
					process = true;
				}else{
					curator_request_irs_update_interface();
				}
			}
			if(process) return curator_request_get_data();
		break;



		//Шаг 3: Выбрать должность
		case 3:
			step_title = 'Шаг 3: Заявка сформирована и готова к отправке';
			if(curator_request_objects['IRESOURCES_COUNT'] < 1){
				App.message('Ничего не выбрано','Вы не выбрали информационные ресурсы и функционал, к которому запрашиваете доступ.','warning');
				return;
			}
		break;


	}//Обработка перед отображением страницы


	$('step_title').set('html',step_title);

	curator_request_objects['step_index'] = index;

	if(curator_request_objects['step_index'] == curator_request_objects['step_max']){
		$('button_step_next').hide();
		$('button_step_done').show();
	}else{
		if(show_next_button) $('button_step_next').show();
		$('button_step_done').hide();
	}

	if(index == 1){
		$('button_step_prev').hide();
	}else{
		$('button_step_prev').show();
	}

	//Слайд
	curator_request_objects['slideshow'].show($('step_'+index), {
		transition: (increment == 1 ? 'fadeThroughBackground' : 'fadeThroughBackground')
	});


}//end function


function curator_request_stepmaster_show(){
	$('stepmaster_area').show();
	$('stepmaster_button_area').show();
	$('stepmaster_contentwrapper').show();
}
function curator_request_stepmaster_hide(){
	$('stepmaster_area').hide();
	$('stepmaster_button_area').hide();
	$('stepmaster_contentwrapper').hide();
}



//Получение списка сотрудников
function curator_request_search_employer(){
	if(!curator_request_objects['search_form'].validate()) return;
	new axRequest({
		url : '/main/ajax/employer',
		data:{
			'action':'curator.employer.search',
			'company_id': select_getValue('employer_company'),
			'employer_name': $('employer_name').value
		},
		silent: true,
		waiter: true,
		callback: function(success, status, data){
			if(success){
				curator_request_build_employer_posts(data);
			}
		}
	}).request();
}//end function




//Построение списка должностей
function curator_request_build_employer_posts(data){

	//Должности не найдены
	if(typeOf(data)!='array' || !data.length){
		$('employer_search_results').hide();
		$('employer_search_none').show();
		return;
	}
	$('employer_search_none').hide();
	$('employer_search_results').show();

	var area = $('employer_post_area');
	var radio, employer_id, employer_name, company_name, employer_post, boss_post, boss_name;

	area.empty();

	//Построение списка должностей
	for(var indx=0; indx<data.length; indx++){

		employer_id = data[indx]['employer_id'];
		employer_name = data[indx]['employer_name'];
		company_name = data[indx]['company_name'];
		employer_post = data[indx]['post_name'];
		
		if(String(data[indx]['boss_post_uid']) == '0'){
			boss_name = boss_post = '-Нет руководителя-';
		}else{
			boss_post =  data[indx]['boss_post_name'];
			
			if(data[indx]['bosses'].length == 0){
					boss_name = '<font class="error">Отсутствует линейный руководитель</font>';
			}else{
				boss_name = '<p class="neutral" style="display:inline-block;">';
				for(var i=0; i<data[indx]['bosses'].length;i++){
					boss_name+= data[indx]['bosses'][i]['employer_name']+' (c '+data[indx]['bosses'][i]['post_from']+')<br/>';
				}
				boss_name+='</p>';
			}
			
		}

		radio = new Element('div',{'class':'radioarea'}).inject(area).set('html',
			'<div class="line"><span>Сотрудник:</span>'+employer_name+'</div>'+
			'<div class="line"><span>Организация:</span>'+company_name+'</div>'+
			'<div class="line"><span>Должность:</span>'+employer_post+'</div>'+
			'<div class="line"><span>Руководитель:</span><p class="neutral" style="display:inline-block;">'+boss_post+'</p></div>'+
			'<div class="line"><span>&nbsp;</span>'+boss_name+'</div>'
		).addEvent('click',function(){
			curator_request_objects['selected_post'] = this.retrieve('post_info');
			curator_request_objects['selected_employer_id'] = curator_request_objects['selected_post']['employer_id'];
			if($(curator_request_objects['selected_post_object'])) $(curator_request_objects['selected_post_object']).removeClass('selected');
			this.addClass('selected');
			curator_request_objects['selected_post_object'] = this;
			$('button_step_next').show();
		}).store('post_info',data[indx]);
		
	}//Построение списка должностей

	curator_request_objects['posts_loaded'] = true;
}//end function



/*Открытие окна выбора ИР*/
function curator_request_ir_selector_open(iresource_id){
	if(curator_request_objects['IR_REQUEST'] == null){
		curator_request_objects['IR_REQUEST'] = {};
		curator_request_objects['IRESOURCES'] = {};
		iresource_id = null;
	}
	$('ir_selector_iresource_list').setValue(0);
	curator_request_ir_selector_iresource_list_change(iresource_id);
	$('ir_selector').show();
	$('ir_selector_wrapper').scrollTo(0, 0);
}//end function




/*Закрытие окна выбора ИР без каких-либо изменений*/
function curator_request_ir_selector_cancel(){
	$('ir_selector').hide();
}//end function




/*Очистка списка ИР*/
function curator_request_ir_trash(){
	App.message(
		'Подтвердите действие',
		'Вы действиктльно хотите убрать из заявки все выбранные информационные ресурсы?',
		'CONFIRM',
		function(){
			if(typeOf(curator_request_objects['IR_REQUEST'])=='object'){
				for(var iresource_id in curator_request_objects['IR_REQUEST']){
					if(typeOf(curator_request_objects['IR_REQUEST'][iresource_id])=='array'){
						for(var i=0; i< curator_request_objects['IR_REQUEST'][iresource_id].length; i++){
							if(typeOf(curator_request_objects['IR_REQUEST'][iresource_id][i])=='object')
							curator_request_objects['IR_REQUEST'][iresource_id][i]['ir_selected']=0;
						}
					}
				}
			}
			curator_request_irs_clear();
		}
	);
}//end function




/*Удаление из заявки конкретного ИР*/
function curator_request_ir_remove(iresource_id){
	if(typeOf(curator_request_objects['IR_REQUEST'])=='object'){
		if(typeOf(curator_request_objects['IR_REQUEST'][iresource_id])=='array'){
			for(var i=0; i< curator_request_objects['IR_REQUEST'][iresource_id].length; i++){
				if(typeOf(curator_request_objects['IR_REQUEST'][iresource_id][i])=='object')
				curator_request_objects['IR_REQUEST'][iresource_id][i]['ir_selected']=0;
			}
		}
	}
	if(typeOf(curator_request_objects['IRESOURCES'])=='object'){
		if(typeOf(curator_request_objects['IRESOURCES'][iresource_id])=='object'){
			if(curator_request_objects['IRESOURCES'][iresource_id]['table']){
				curator_request_objects['IRESOURCES'][iresource_id]['table'].terminate();
				curator_request_objects['IRESOURCES'][iresource_id]['table'] = null;
			}
			if(curator_request_objects['IRESOURCES'][iresource_id]['item']){
				curator_request_objects['IRESOURCES'][iresource_id]['item'].destroy();
				curator_request_objects['IRESOURCES'][iresource_id]['item'] = null;
			}
			curator_request_objects['IRESOURCES_COUNT']--;
			curator_request_irs_update_interface();
		}
		curator_request_objects['IRESOURCES'][iresource_id] = null;
	}
}//end function




/*Запрос данных для начала формирования заявки*/
function curator_request_get_data(){

	if(curator_request_objects['IR_LIST'] != null) return true;

	new axRequest({
		url : '/main/ajax/request',
		data:{
			'action':'get.data',
			'company_id': curator_request_objects['access_for_company_id'],
			'irlist': 1,
			'irtypes': 1,
			'post_uid': curator_request_objects['access_for_post'],
			'employer_id': curator_request_objects['access_for_employer_id']
		},
		silent: true,
		waiter: true,
		callback: function(success, status, data){
			if(success){
				curator_request_objects['IR_TYPES'] = data['irtypes'];
				if(typeOf(data['irlist'])!='array' || !data['irlist'].length){
					App.message(
						'Нет доступных информационных ресурсов',
						'Для сотрудников '+curator_request_objects['selected_post']['company_name']+' в настоящий момент нет доступных информационных ресурсов.<br/><br/>'+
						'Свяжитесь с администратором для разрешения данной ситуации.',
						'error'
					);
					curator_request_objects['IR_LIST'] = false;
					return;
				}
				curator_request_objects['IR_LIST'] = $unlink(data['irlist']);
				data['irlist'].unshift({'iresource_id':'0','full_name':'-[Выберите информационный ресурс]-'});
				select_add({
					'list': 'ir_selector_iresource_list',
					'options': data['irlist'],
					'key': 'iresource_id',
					'value': 'full_name',
					'clear': true
				});
				curator_request_do_page('next');
			}
		}
	}).request();
	
}//end function



/*Выбор ИР для добавления / редактирования списка объектов доступа*/
function curator_request_ir_selector_iresource_list_change(iresource_id){


	var iresource_list = $('ir_selector_iresource_list');
	if(iresource_id){
		select_set(iresource_list, iresource_id);
	}else{
		iresource_id = parseInt(select_getValue(iresource_list));
	}

	$('ir_selector_table').hide();
	$('ir_selector_none').hide();
	$('ir_selector_select').hide();

	if(!iresource_id){
		$('ir_selector_select').show();
		return;
	}


	//Объекты выбранного ИР еще не закешированы
	if(typeOf(curator_request_objects['IR_REQUEST'][iresource_id])!='array'){
		iresource_list.disable();

		new axRequest({
			url : '/main/ajax/request',
			data:{
				'action':'get.roles',
				'iresource_id': iresource_id,
				'post_uid': curator_request_objects['access_for_post'],
				'employer_id': curator_request_objects['access_for_employer_id'],
				'company_id': curator_request_objects['access_for_company_id']
			},
			silent: true,
			waiter: true,
			callback: function(success, status, data){
				iresource_list.enable();
				if(success){
					if(typeOf(data)!='array') return;
					curator_request_objects['IR_REQUEST'][iresource_id] = data;
					curator_request_ir_selector_table_build(iresource_id);
				}
			}
		}).request();

	}//Объекты выбранного ИР еще не закешированы
	else{
		curator_request_ir_selector_table_build(iresource_id);
	}
	
}//end function




//Построение списка объектов доступа
function curator_request_ir_selector_table_build(iresource_id){

	if(typeOf(curator_request_objects['IR_REQUEST'][iresource_id])!='array'){
		return App.message(
			'Ошибка JavaScript',
			'Массив объектов доступа для информационного ресурса задан некорректно.<br/><br/>'+
			'Свяжитесь с администратором для разрешения данной ситуации.',
			'error'
		);
	}

	if(curator_request_objects['IR_REQUEST'][iresource_id].length == 0){
		$('ir_selector_none').show();
		$('ir_selector_table').hide();
	}else{
		$('ir_selector_none').hide();
		curator_request_objects['ir_selector_table'].setData(curator_request_objects['IR_REQUEST'][iresource_id]);
		$('ir_selector_table').show();
	}

}//end function




//Выбор типа доступа из таблицы прав доступа
function curator_request_select_ir_type(){
	var iresource_id = this.retrieve('iresource_id');
	var irole_id = this.retrieve('irole_id');
	if(!iresource_id) return false;
	if(typeOf(curator_request_objects['IR_REQUEST'][iresource_id])!='array') return false;
	////Читается как UPDATE ARRAY SET [setColumn] = [value] WHERE [termColumn] = [term] LIMIT [limit]
	curator_request_objects['IR_REQUEST'][iresource_id].filterUpdate('ir_selected', select_getValue(this), 'irole_id', irole_id, 1);
}//end function





//Добавление информационного ресурса и выбранных прав доступа в итоговый слайдер
function curator_request_ir_selector_complete(){
	iresource_id = parseInt(select_getValue('ir_selector_iresource_list'));
	if(!iresource_id || typeOf(curator_request_objects['IR_REQUEST'][iresource_id])!='array'){
		return App.message(
			'Ошибка JavaScript',
			'Информационный ресурс указан некорректно',
			'error'
		);
	}

	var item_exists = false;
	var idata = [], row, section=null, is_section;

	//Формирование массива запрашиваемых объектов доступа по информационному ресурсу
	for(var index=0; index < curator_request_objects['IR_REQUEST'][iresource_id].length; index++){

		row = curator_request_objects['IR_REQUEST'][iresource_id][index];
		is_section = (typeOf(row)=='object' ? false : true);
		if(is_section){
			section = row;
		}else{
			if(String(row['ir_selected']).toInt()>0){
				if(section != null){
					idata.push(section);
					section = null;
				}
				idata.push(row);
			}
		}

	}//Формирование массива запрашиваемых объектов доступа по информационному ресурсу



	//Не выбран ни один объект доступа
	if(idata.length == 0){
		//Удаление из результирующего списка 
		curator_request_ir_remove(iresource_id);
	}else{
		//Проверка существования объектов в результирующем списке
		if(typeOf(curator_request_objects['IRESOURCES'])!='object') curator_request_objects['IRESOURCES'] = {};
		if(typeOf(curator_request_objects['IRESOURCES'][iresource_id])!='object'){
			curator_request_objects['IRESOURCES'][iresource_id] = {
				'item': null,
				'table': null
			};
		}else{
			if(curator_request_objects['IRESOURCES'][iresource_id]['item']) item_exists = true;
		}
		if(!item_exists){
			curator_request_irs_add(iresource_id);
			curator_request_objects['IRESOURCES_COUNT']++;
		}
		curator_request_objects['IRESOURCES'][iresource_id]['table'].setData(idata);
	}

	curator_request_irs_update_interface();

	//Закрытие окна выбора
	curator_request_ir_selector_cancel();

}//end function



/*Отображение / сокрытие секций таблицы*/
function curator_request_irs_sections_display(visible){
	if(curator_request_objects['ir_selector_table']) curator_request_objects['ir_selector_table'].allSectionsDisplay(visible);
}


/*Создание элемента ИР в слайдере*/
function curator_request_irs_add(iresource_id){

	var li = new Element('li',{'class':'dark'}).inject('ir_list');
	var heading = new Element('h3',{'class':'opened'}).inject(li);
	var heading_collapser = new Element('a',{'class':'collapser'}).inject(heading);
	var heading_toolbar = new Element('div',{'class':'toolbar'}).inject(heading);
	var heading_title = new Element('span').inject(heading).set('html',curator_request_objects['IR_LIST'].filterResult('full_name', 'iresource_id', iresource_id));

	curator_request_objects['IRESOURCES'][iresource_id]['item'] = li;

	//Редактирование ИР
	new Element('span',{
		'title':'Редактировать запрашиваемый функционал',
		'class':'ui-icon-white ui-icon-pencil'
	}).inject(heading_toolbar).setStyles({
		'cursor':'pointer'
	}).addEvents({
		click: function(e){
			curator_request_ir_selector_open(iresource_id);
			e.stop();
			return false;
		}
	});	

	//Удаление ИР
	new Element('span',{
		'title':'Удалить из заявки данный информационный ресурс',
		'class':'ui-icon-white ui-icon-trash'
	}).inject(heading_toolbar).setStyles({
		'cursor':'pointer'
	}).addEvents({
		click: function(e){
			App.message(
				'Подтвердите действие',
				'Вы действиктльно хотите убрать из заявки информационный ресурс: '+curator_request_objects['IR_LIST'].filterResult('full_name', 'iresource_id', iresource_id)+'?',
				'CONFIRM',
				function(){
					curator_request_ir_remove(iresource_id);
				}
			);
			e.stop();
			return false;
		}
	});	

	var div = new Element('div',{'class':'collapse'}).inject(li);
	var container = new Element('div',{'class':'collapse-container'}).inject(div);

	container.setStyles({
		'padding': '0px',
		'margin': '0px'
	});

	var collapsible = new Fx.Slide(div, {
		duration: 100, 
		transition: Fx.Transitions.linear,
		onComplete: function(request){ 
			var open = request.getStyle('margin-top').toInt();
			if(open >= 0) new Fx.Scroll(window).toElement(heading);
			if(open) heading.addClass('closed').removeClass('opened');
			else heading.addClass('opened').removeClass('closed');
			request.setStyle('height','auto');
		}
	});
	
	heading.onclick = function(){
		collapsible.toggle();
		return false;
	}

	//Таблица со списком доступов
	curator_request_objects['IRESOURCES'][iresource_id]['table'] = new jsTable(container,{
		'class': 'jsTableLight',
		columns: [
			{
				width:'30%',
				sortable:false,
				caption: 'Функционал',
				dataSource:'full_name'
			},
			{
				width:'40%',
				sortable:false,
				caption: 'Описание',
				dataSource:'description',
				dataFunction:function(table, cell, text, data){

					var span = new Element('div',{
						'styles':{
							'margin-left':'30px'
						}
					}).set('text',text);

					if(!data['screenshot']||data['screenshot']==''){
						new Element('img',{
							'src':INTERFACE_IMAGES+'/preview_none.png',
						}).inject(cell).setStyles({
							'cursor':'default',
							'float':'left'
						});
						span.inject(cell);
						return '';
					}
					new Element('img',{
						'src':INTERFACE_IMAGES+'/preview_active.png',
					}).inject(cell).setStyles({
						'cursor':'pointer',
						'float':'left'
					}).addEvents({
						'click': function(e){
							preview_irole(data['irole_id']);
						}
					});
					span.inject(cell);
					return '';

				}
			},
			{
				width:'80px',
				sortable:false,
				caption: 'Важность',
				dataSource:'weight',
				dataStyle:{'text-align':'center','min-width':'80px'},
				dataFunction:function(table, cell, text, data){
					if(text<3) return 'Низкая';
					if(text<6) return 'Средняя';
					if(text<8) return 'Высокая';
					return 'Критично';
				}
			},
			{
				caption: 'Текущий доступ',
				sortable:false,
				width:'120px',
				dataSource:'ir_current',
				dataStyle:{'text-align':'center','min-width':'120px'},
				dataFunction:function(table, cell, text, data){
					if(String(text) == '0') return '---';
					return curator_request_objects['IR_TYPES'].filterResult('full_name', 'item_id', text);
				}
			},
			{
				caption: 'Запросить доступ',
				sortable:false,
				width:'120px',
				dataSource:'ir_selected',
				dataStyle:{'text-align':'center','min-width':'120px'},
				dataFunction:function(table, cell, text, data){
					if(String(text) == '0') return '--Нет--';
					return curator_request_objects['IR_TYPES'].filterResult('full_name', 'item_id', text);
				}
			}
		],
		rowFunction: function(table, row, data){
			var color = '#FFAFAF';
			if(data['weight']<3) color = '#FFFFFF';
			else if(data['weight']<6) color = '#FFFDE6';
			else if(data['weight']<8) color = '#FFDEDE';
			return {
				'background': color,
				'bg_recalc': false
			}
		},
		'dataBackground1': '#fff',
		'dataBackground2': '#fff',
		selectType:1
	});

}//end function




//Удаление объектов из результирующего списка
function curator_request_irs_clear(){

	if(typeOf(curator_request_objects['IR_REQUEST'])=='object'){
		for(var iresource_id in curator_request_objects['IR_REQUEST']){
			if(typeOf(curator_request_objects['IR_REQUEST'][iresource_id])=='array'){
				for(var i=0; i< curator_request_objects['IR_REQUEST'][iresource_id].length; i++){
					if(typeOf(curator_request_objects['IR_REQUEST'][iresource_id][i])=='object'){
						curator_request_objects['IR_REQUEST'][iresource_id][i]['ir_selected']=0;
					}
				}
			}
		}
	}

	if(typeOf(curator_request_objects['IRESOURCES'])=='object'){
		for(var i in curator_request_objects['IRESOURCES']){
			if(curator_request_objects['IRESOURCES'][i]['table']){
				curator_request_objects['IRESOURCES'][i]['table'].terminate();
				curator_request_objects['IRESOURCES'][i]['table'] = null;
			}
			if(curator_request_objects['IRESOURCES'][i]['item']){
				curator_request_objects['IRESOURCES'][i]['item'].destroy();
				curator_request_objects['IRESOURCES'][i]['item'] = null;
			}
			curator_request_objects['IRESOURCES'][i] = null;
		}
	}

	curator_request_objects['IRESOURCES'] = null;
	curator_request_objects['IRESOURCES_COUNT']=0;
	$('ir_list').empty();
	curator_request_irs_update_interface();
}//end function




//Обновление интерфейса: отображение/сокрытие элементов
function curator_request_irs_update_interface(){
	if(curator_request_objects['IRESOURCES_COUNT']>0){
		$('ir_area').show();
		$('ir_none').hide();
		$('button_ir_trash').show();
		$('button_step_next').show();
	}else{
		$('ir_area').hide();
		$('ir_none').show();
		$('button_ir_trash').hide();
		$('button_step_next').hide();
	}
}//end function



//Сохранение заявки
function curator_request_irs_save(){

	if(!curator_request_objects['contact_form'].validate()) return;

	var iresources = '', access = '', role_id, ir_selected, count=0, ir_count=0;
	var index, row;

	if(typeOf(curator_request_objects['IR_REQUEST'])!='object')
		return App.message(
			'Ошибка JavaScript',
			'В заявке отсутствуют информационные ресурсы',
			'error'
		);

	//Формирование запроса
	for(var iresource_id in curator_request_objects['IR_REQUEST']){

		iresource_id = String(iresource_id).toInt();
		if(!iresource_id) continue;
		if(typeOf(curator_request_objects['IR_REQUEST'][iresource_id])!='array') continue;

		ir_count = 0;

		//Формирование массива запрашиваемых объектов доступа по информационному ресурсу
		for(index=0; index < curator_request_objects['IR_REQUEST'][iresource_id].length; index++){
			row = curator_request_objects['IR_REQUEST'][iresource_id][index];
			if(typeOf(row)=='object' ){
				irole_id = String(row['irole_id']).toInt();
				ir_selected = String(row['ir_selected']).toInt();
				if(irole_id>0 && ir_selected>0){
					access += 
						'&a[]='+iresource_id +
						'|'+row['irole_id']+
						'|'+row['ir_selected'];
					count++;
					ir_count++;
				}
			}
		}//Формирование массива запрашиваемых объектов доступа по информационному ресурсу

		if(ir_count > 0){
			iresources += '&ir[]='+iresource_id;
		}

	}//Формирование запроса

	if(count==0){
		App.message('Нельзя сохранить пустую заявку','Вы не выбрали информационные ресурсы и функционал, к которому запрашиваете доступ.','warning');
		return;
	}


	//Отправка заявки
	new axRequest({
		url : '/main/ajax/request',
		data:
			'action=request.save'+
			'&phone='+encodeURIComponent($('input_ir_phone').value)+
			'&email='+encodeURIComponent($('input_ir_email').value)+
			'&post_uid='+curator_request_objects['access_for_post']+
			'&employer_id='+curator_request_objects['access_for_employer_id']+
			'&company_id='+curator_request_objects['access_for_company_id']+
			iresources+
			access,
		silent: true,
		waiter: true,
		callback: function(success, status, data){
			if(success){
				curator_request_objects['request_id'] = data['request_id'];
				$('stepmaster_contentwrapper').hide();
				$('ir_request_complete').show();
			}
		}
	}).request();

}//end function


