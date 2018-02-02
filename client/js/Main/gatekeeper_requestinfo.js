var gk_requestinfo_objects = {};


//Вход на страницу
function gk_requestinfo_enter_page(success, status, data){
	App.Location.addEvent('beforeLoadPage', gk_requestinfo_exit_page);
	if(
		typeOf(data)!='object' ||
		typeOf(data['info'])!='object' ||
		typeOf(data['iresource'])!='object'||
		typeOf(data['ir_types'])!='array' ||
		typeOf(data['roles'])!='array'
	){
		$('request_wrapper').hide();
		$('request_none').show();
		return false;
	}
	gk_requestinfo_start(data);
	build_scrolldown($('request_area'), 50);
}//end function



//Выход со страницы
function gk_requestinfo_exit_page(){

	if(gk_requestinfo_objects['IRTABLE']){
		gk_requestinfo_objects['IRTABLE'].terminate();
	}

	if(gk_requestinfo_objects['IRCOMMENTS']){
		gk_requestinfo_objects['IRCOMMENTS'].destroy();
	}

	for(var i in gk_requestinfo_objects){
		gk_requestinfo_objects[i] = null;
	}
	gk_requestinfo_objects = {};
	App.Location.removeEvent('beforeLoadPage', gk_requestinfo_exit_page);
}//end function



//Инициализация заявки
function gk_requestinfo_start(data){

	gk_requestinfo_objects['REQUEST_ID'] = parseInt(data['info']['request_id']);
	gk_requestinfo_objects['REQUEST_TYPE'] = parseInt(data['info']['request_type']);
	gk_requestinfo_objects['IRESOURCE_ID'] = parseInt(data['iresource']['iresource_id']);
	gk_requestinfo_objects['IR_TYPES'] = data['ir_types'];
	gk_requestinfo_objects['IRTABLE'] = null;
	gk_requestinfo_objects['IRCOMMENTS'] = null;
	gk_requestinfo_objects['IRCHANGE'] = false;
	gk_requestinfo_objects['IRCHROLES'] = {};
	gk_requestinfo_objects['GKACTION'] = '';
	gk_requestinfo_objects['GKROLE'] = parseInt(data['info']['gatekeeper_role']);

	//Навигация
	select_add({
		'list':'ir_selector_iresource_list',
		'options':[['info','Общие сведения о заявке']]
	});
	select_add({
		'list'		: 'ir_selector_iresource_list',
		'options'	: data['iresources'],
		'key'		: 'iresource_id',
		'value'		: 'iresource_name'
	});

	$('request_title').set('html',data['info']['gatekeeper_role_name']);

	//Инфо заявки
	var fields = ['request_id','request_type','iresource_name','curator_name','create_date','employer_name','company_name','post_name','phone','email','gatekeeper_role_name'];
	var text;
	for(var i=0;i<fields.length;i++){
		if($('info_'+fields[i])){
			text = data['info'][fields[i]];
			if(fields[i] == 'request_type'){
				text = (String(text) == '3' ? '<font color="red">Блокировка доступа</font>' : '<font color="green">Запрос доступа</font>' );
			}
			$('info_'+fields[i]).set('html', text);
		}
	}
	var infoarea = build_blockitem({
		'list': 'blocklist',
		'title'	: 'Общие сведения о заявке'
	});
	$('tmpl_request_info').inject(infoarea['container']).show();

	if(String(data['info']['gatekeeper_role'])=='3'){
		$('gk_export_area').show();
	}else{
		$('gk_export_area').hide();
	}

	//Комментарии
	gk_requestinfo_build_comments(data);

	//кнопки Одобрить/Отклонить
	if(String(data['info']['can_approve'])!='1' || !['1', '2', '3'].contains(String(data['info']['gatekeeper_role']))){
		$('button_approve').hide();
	}else{
		$('button_approve').addEvent('click',gk_requestinfo_approve);
	}
	if(String(data['info']['can_decline'])!='1' || !['1', '2'].contains(String(data['info']['gatekeeper_role']))){
		$('button_decline').hide();
	}else{
		$('button_decline').addEvent('click',gk_requestinfo_decline);
	}
	switch(String(data['info']['gatekeeper_role'])){
		case '1': 
			gk_requestinfo_objects['GKACTION'] = 'Согласовать заявку';
			$('button_approve').getElement('span').set('html',gk_requestinfo_objects['GKACTION']);
		break;
		case '2': 
			gk_requestinfo_objects['GKACTION'] = 'Утвердить заявку';
			$('button_approve').getElement('span').set('html',gk_requestinfo_objects['GKACTION']);
		break;
		case '3': 
			gk_requestinfo_objects['GKACTION'] = 'Заявка исполнена';
			$('button_approve').getElement('span').set('html',gk_requestinfo_objects['GKACTION']);
		break;
	}

	//Запрашиваемый доступ
	gk_requestinfo_build_roles_table();
	gk_requestinfo_objects['IRTABLE'].setData(data['roles']);

}//end function




/*Построение списка доступов*/
function gk_requestinfo_build_roles_table(){

	var iroles = build_blockitem({
		'list'	: 'blocklist',
		'title'	: 'Запрашиваемый доступ'
	});

	var togle_view_funct = function(e){
		e.stop();
		if(!gk_requestinfo_objects['IRTABLE_MF']){
			gk_requestinfo_objects['IRTABLE'].multiFilter([
				{
					'column':4,
					'value': ['-[']
				},
				{
					'column':6,
					'value':['-[']
				}
			], true);
			gk_requestinfo_objects['IRTABLE_MF'] = true;
		}else{
			gk_requestinfo_objects['IRTABLE'].multiFilter(null);
			gk_requestinfo_objects['IRTABLE_MF'] = false;
		}
	};

	if(gk_requestinfo_objects['GKROLE'] == 1 || gk_requestinfo_objects['GKROLE'] == 2){
		//Изменение представления
		new Element('span',{
			'title':'Скрыть/показать запрошенный функционал',
			'class':'ui-icon-white ui-icon-transferthick-e-w',
			'styles':{
				'cursor':'pointer'
			},
			'events':{
				'click': togle_view_funct
			}
		}).inject(iroles['toolbar']);
	}


	iroles['container'].setStyles({
		'padding': '0px',
		'margin': '0px'
	});

	var is_lock_request = (gk_requestinfo_objects['REQUEST_TYPE']==3);

	//Таблица со списком доступов
	gk_requestinfo_objects['IRTABLE'] = new jsTable(iroles['container'],{
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
				caption: 'Запрошен заявителем',
				sortable:false,
				width:'120px',
				dataSource:'ir_request',
				dataStyle:{'text-align':'center','min-width':'120px'},
				dataFunction:function(table, cell, text, data){
					if(String(text) == '0') return '-[Нет]-';
					if(is_lock_request) return '-[Блокировать]-';
					return gk_requestinfo_objects['IR_TYPES'].filterResult('full_name', 'item_id', text);
				}
			},
			{
				caption: 'Согласован гейткипером',
				sortable:false,
				width:'120px',
				dataSource:'ir_types',
				dataStyle:{'text-align':'center','min-width':'120px'},
				dataFunction:function(table, cell, text, data){
					if(gk_requestinfo_objects['GKROLE'] != 1 && gk_requestinfo_objects['GKROLE'] !=2){
						if(String(data['ir_selected']) == '0') return '-[Нет]-';
						if(is_lock_request) return '-[Блокировать]-';
						return gk_requestinfo_objects['IR_TYPES'].filterResult('full_name', 'item_id', data['ir_selected']);
					}
					var irt = select_add({
						'parent': cell,
						'options': [['0','-[Нет]-']]
					});
					if(is_lock_request){
						select_add({
							'list': irt,
							'options': [['1','-[Блокировать]-']]
						});
					}else{
						for(var i=0; i<text.length;i++){
							select_add({
								'list': irt,
								'options': [[text[i],gk_requestinfo_objects['IR_TYPES'].filterResult('full_name', 'item_id', text[i])]]
							});
						}
					}
					select_set(irt, data['ir_selected']);
					irt.store('iresource_id',data['iresource_id']);
					irt.store('irole_id',data['irole_id']);
					irt.addEvent('change',gk_requestinfo_select_ir_type.bind(irt));
					return '';
				}
			},
			{
				caption: 'Изменения',
				sortable:false,
				width:'120px',
				dataSource:'ir_selected',
				dataStyle:{'text-align':'center','min-width':'120px','font-size':'10px'},
				dataFunction:function(table, cell, text, data){
					var action = '';
					switch(String(data['update_type'])){
						case '0': return '-[Не менялся]-';
						case '1': action = 'Добавлено'; break;
						case '2': action = 'Изменено'; break;
						case '3': action = 'Удалено'; break;
					}
					return action +'<br>'+ data['update_time'] +'<br>'+ (data['gatekeeper_id']=='0'? 'Администратор' : data['gatekeeper_name']);
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

}




/*Изменение типа доступа*/
function gk_requestinfo_select_ir_type(){
	gk_requestinfo_objects['IRCHANGE'] = true;
	var irole_id = this.retrieve('irole_id');
	gk_requestinfo_objects['IRCHROLES'][irole_id] = {
		'request_id': gk_requestinfo_objects['REQUEST_ID'],
		'iresource_id': gk_requestinfo_objects['IRESOURCE_ID'],
		'irole_id': irole_id,
		'ir_selected': select_getValue(this)
	};
}





/*Построение списка комментариев*/
function gk_requestinfo_build_comment_list(comments){
	var comment;
	for(var i=0; i<comments.length; i++){
		comment = comments[i];
		build_commentitem({
			'list'		: gk_requestinfo_objects['IRCOMMENTS'],
			'author'	: (String(comment['employer_id']) == '0' ? 'Администратор' : comment['employer_name']),
			'timestamp'	: comment['timestamp'],
			'message'	: comment['comment'],
			'bg_color'	: (i%2==0 ? null : '#FFFFFF')
		});
	}
}


/*Построение списка комментариев*/
function gk_requestinfo_build_comments(data){

	var request_id = data['info']['request_id'];
	var iresource_id = data['iresource']['iresource_id'];
	var comments = data['iresource']['comments'];
	var icomments = build_blockitem({
		'list'	: 'blocklist',
		'title'	: 'Комментарии к заявке'
	});

	//Комментарии
	var icomments_area = new Element('div').inject(icomments['container']);
	var comment_list = new Element('ul',{'class':'commentlist'}).inject(icomments_area);
	gk_requestinfo_objects['IRCOMMENTS'] = comment_list;

	if(typeOf(comments)!='array'||!comments.length){
		new Element('h2',{'html':'Комментарии отсутствуют'}).inject(comment_list);
	}
	else{
		gk_requestinfo_build_comment_list(comments);
	}

	if(parseInt(data['info']['can_comment'])!=1 || !['1', '2', '3'].contains(String(data['info']['gatekeeper_role']))) return;

	var add_comment_funct = function(e){
		App.comment('Добавить комментарий','',function(comment){
			comment = String(comment).trim();
			if(!comment.length) return;
			new axRequest({
				url : '/main/ajax/request',
				data:{
					'action':'comment.add',
					'irlist': 1,
					'request_id': request_id,
					'iresource_id': iresource_id,
					'comment': comment,
					'returncomments': 1
				},
				silent: true,
				waiter: true,
				callback: function(success, status, data){
					if(success){
						if(typeOf(gk_requestinfo_objects['IRCOMMENTS'])!='element') return;
						if(typeOf(data)=='array'&&data.length){
							gk_requestinfo_objects['IRCOMMENTS'].empty();
							gk_requestinfo_build_comment_list(data);
						}
					}
				}
			}).request();
		});
		e.stop();
	};

	//Добавление комментария
	new Element('span',{
		'title':'Добавить комментарий к заявке',
		'class':'ui-icon-white ui-icon-comment',
		'styles':{
			'cursor':'pointer'
		},
		'events':{
			'click': add_comment_funct
		}
	}).inject(icomments['toolbar']);

	var comment_button = new Element('div',{
		'class':'ui-button',
		'events':{
			'click': add_comment_funct
		}
	}).inject(icomments_area);
	new Element('span',{'text':'Добавить комментарий'}).inject(comment_button);

};





/*Одобрение заявки*/
function gk_requestinfo_approve(){
	if(gk_requestinfo_objects['GKROLE'] == 3){
		App.comment(
			'Статус исполнения заявки',
			'',
			function(comment){
				comment = String(comment).trim();
				if(!comment.length){
					App.message('Не указан комментарий','Вы должны указать статус исполнения заявки','ERROR');
					return false;
				}
				gk_requestinfo_complete_do('approve', comment);
			},
			'Заявка исполнена'
		);
		return true;
	}
	if(gk_requestinfo_objects['IRCHANGE']){
		App.message(
			'Подтвердите действие',
			'Вы действительно хотите '+String(gk_requestinfo_objects['GKACTION']).toLowerCase()+' с внесенными изменениями в запрашиваемый функционал?',
			'CONFIRM',
			function(){
				gk_requestinfo_complete_do('approve', '');
			}
		);
	}else{
		gk_requestinfo_complete_do('approve', '');
	}
}




/*Отклонение заявки*/
function gk_requestinfo_decline(){
	App.comment(
		'Причина отклонения заявки',
		'',
		function(comment){
			comment = String(comment).trim();
			if(!comment.length){
				App.message('Не указана причина отклонения заявки','Вы не можете отклонить заявку не указав причину Вашего решения','ERROR');
				return false;
			}
			gk_requestinfo_complete_do('decline', comment);
		},
		'Отклонить заявку'
	);
}




/*Обработка действия*/
function gk_requestinfo_complete_do(action, comment){

	var row, access='';

	if(action == 'approve'){
		for(var i in gk_requestinfo_objects['IRCHROLES']){
			row = gk_requestinfo_objects['IRCHROLES'][i];
			access +=	'&a[]='+row['request_id']+
						'|'+row['iresource_id']+
						'|'+row['irole_id']+
						'|'+row['ir_selected'];
		}
	}

	//Отправка заявки
	new axRequest({
		url : '/main/ajax/request',
		data:
			'action=request.'+action+
			'&request_id='+encodeURIComponent(gk_requestinfo_objects['REQUEST_ID'])+
			'&iresource_id='+encodeURIComponent(gk_requestinfo_objects['IRESOURCE_ID'])+
			'&comment='+encodeURIComponent(comment)+
			access,
		silent: true,
		waiter: true,
		callback: function(success, status, data){
			if(success){
				$('request_wrapper').hide();
				$('gk_request_complete_title').set('html',data['desc']);
				$('gk_request_complete').show();
			}
		}
	}).request();

}



/*Получить заявку в PDF формате*/
function gk_requestinfo_export(format){
	var link = '/main/customcontent/reports?format='+format+'&report_type=performer&request_id='+gk_requestinfo_objects['REQUEST_ID']+'&iresource_id='+gk_requestinfo_objects['IRESOURCE_ID'];
	App.Loader.downloadFile(link);
}