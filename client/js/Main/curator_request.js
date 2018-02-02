;(function(){
var PAGE_NAME = 'curator_request';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': ['employer_search_table','iresource_selector_table', 'ir_roles_table'],
		'validators': ['search_form','contact_form'],
		'employer_search_table':null,
		'iresource_selector_table': null,
		'ir_roles_table':null,
		'search_form':null,
		'contact_form':null,
		//
		'slideshow':null,
		'step_index': 1,
		'step_max': 3,
		//
		'selected_employers': {},
		'selected_employers_count': 0,
		'selected_company_id': 0,
		'ir_for_company_id': 0,
		'ir_for_employers': null,
		'ir_types': null,
		'ir_types_assoc': null,
		'ir_list':null,
		'ir_groups': null,
		'ir_count': 0,
		'ir_request':null,
		'iresources': null,
		'periodical': null,
		'effect':null
	},


	/*******************************************************************
	 * Инициализация
	 ******************************************************************/

	//Вход на страницу
	enter: function(success, status, data){
		App.Location.addEvent('beforeLoadPage', App.pages[PAGE_NAME].exit);
		this.objects = $unlink(this.defaults);
		this.start(data);
	},//end function



	//Выход со страницы
	exit: function(){
		App.Location.removeEvent('beforeLoadPage', App.pages[PAGE_NAME].exit);
		var self = App.pages[PAGE_NAME];
		self.irClearAll();
		self.objects['tables'].each(function(table){
			if(self.objects[table]) self.objects[table].terminate();
		});
		self.objects['validators'].each(function(validator){
			if(self.objects[validator]) self.objects[validator].destroy();
		});
		for(var i in self.objects) self.objects[i] = null;
		self.objects = {};
	},//end function



	//Инициализация страницы
	start: function(data){

		//Проверка данных
		if(typeOf(data)!='object' || typeOf(data['companies'])!='array' || !data['companies'].length){
			$('bigblock_wrapper').hide();
			$('bigblock_none').show();
			return false;
		}


		set_splitter_h({
			'left'		: $('employers_area'),
			'right'		: $('stepmaster_area'),
			'splitter'	: $('stepmaster_splitter'),
			'parent'	: $('bigblock_contentwrapper')
		});

		//Построение слайдов
		this.objects['slideshow'] = new jsSlideShow('step_container');

		//Поиск cотрудников
		$('search_employer_button').addEvent('click',this.searchEmployers.bind(this));
		$('employer_name').addEvent('keypress',function(event){if(event.code ==13){App.pages[PAGE_NAME].searchEmployers();}});
		this.objects['search_form'] = new jsValidator('step_1');
		this.objects['search_form'].minValue('employer_company',1,'Выберите организацию').required('employer_name').minLength('employer_name',2).alpha('employer_name');
		$('selected_employer_del_button').addEvent('click',this.employerDelete.bind(this));

		this.objects['contact_form'] = new jsValidator('step_3');
		this.objects['contact_form'].phone('input_ir_phone').email('input_ir_email');

		//Применение данных
		this.setData(data);

		$('button_ir_add').addEvent('click',this.irSelectorOpen.bind(this));
		$('iresource_selector_complete_button').addEvent('click',this.irSelectorComplete.bind(this));
		$('iresource_selector_cancel_button').addEvent('click',this.irSelectorClose.bind(this));
		$('iresource_selector_groups_select').addEvent('change',this.irSelectorChangeGroup.bind(this));
		set_splitter_h({
			'left'		: $('iresource_selector_groups_area'),
			'right'		: $('iresource_selector_list'),
			'splitter'	: $('iresource_selector_splitter'),
			'parent'	: $('iresource_selector_wrapper')
		});
		//Инициализация таблицы выбора информационных ресурсов
		this.objects['iresource_selector_table'] = new jsTable('iresource_selector_list_area_wrapper',{
			columns: [
				{
					width:'30%',
					sortable:true,
					caption: 'Информационный ресурс',
					dataSource:'full_name'
				},
				{
					width:'40%',
					sortable:true,
					caption: 'Описание',
					dataSource:'description'
				},
				{
					width:'20%',
					sortable:true,
					caption: 'Группа ресурсов',
					dataSource:'igroup_id',
					dataFunction:function(table, cell, text, data){
						var result='';
						if(data['igroup_id']!='0'){
							result = App.pages[PAGE_NAME].objects['ir_groups'].filterResult('full_name', 'igroup_id', data['igroup_id']);
						}else{
							result = '-[Без группы]-';
						}
						if(result==''){
							result = '-[???? ID:'+data['igroup_id']+']-';
						}
						return result;
					}
				}
			],
			selectType:1
		});
		this.objects['iresource_selector_table'].addEvent('click', this.irSelectorSelectIResource.bind(this));
		$('iresources_filter').addEvent('keydown',function(e){if(e.code==13) App.pages[PAGE_NAME].irSelectorFilter();});
		$('iresources_filter_button').addEvent('click',this.irSelectorFilter.bind(this));
		$('ir_roles_close_button').addEvent('click', this.irRolesClose.bind(this));
		$('ir_roles_complete_button').addEvent('click', this.irRolesComplete.bind(this));
		$('ir_roles_iresource_list').addEvent('change', function(){
			App.pages[PAGE_NAME].irRolesChangeIresource(null);
		});
		$('ir_roles_sections_show_button').addEvent('click', this.irRolesSectionsShow.bind(this));
		$('ir_roles_sections_hide_button').addEvent('click', this.irRolesSectionsHide.bind(this));
		$('button_ir_trash').addEvent('click', this.irRemoveAll.bind(this));

		this.doPage('first');
		$('button_step_next').hide().addEvent('click',this.pageNavigationEvent.bind(this));
		$('button_step_prev').hide().addEvent('click',this.pageNavigationEvent.bind(this));
		$('button_step_done').hide().addEvent('click',this.pageNavigationEvent.bind(this));


		$('step_1_filter_area').flash('#e1e181','#fff',5);

	},//end function





	/*******************************************************************
	 * Обработка данных
	 ******************************************************************/

	//Обработка данных
	setData: function(data){
		var type;
		if(typeOf(data)!='object') return;
		var selected_iresource = false;


		//Организации
		if(typeOf(data['companies'])=='array'){
			data['companies'].sort(function(a,b){if(a['company_name']>b['company_name'])return 1;return -1;});
			this.objects['companies'] = $unlink(data['companies']);
			data['companies'].unshift({'company_id':'0','company_name':'-[Выберите организацию]-'});
			select_add({
				'list': 'employer_company',
				'key': 'company_id',
				'value': 'company_name',
				'options': data['companies'],
				'default': '0',
				'clear': true
			});
		}//Организации


		//Типы доступа
		if(typeOf(data['irtypes'])=='array'){
			this.objects['ir_types_assoc'] = {};
			this.objects['ir_types'] = $unlink(data['irtypes']);
			for(var i=0; i<data['irtypes'].length;i++){
				this.objects['ir_types_assoc'][String(data['irtypes'][i]['item_id'])] = data['irtypes'][i]['full_name'];
			}
		}//Типы доступа


		//Список информационных ресурсов
		if(typeOf(data['irlist'])=='array'){
			this.objects['ir_list'] = $unlink(data['irlist']);
			select_add({
				'list': 'ir_roles_iresource_list',
				'options': [{'iresource_id':'0','full_name':'-[Выберите информационный ресурс]-'}],
				'key': 'iresource_id',
				'value': 'full_name',
				'clear': true
			});
		}//Список информационных ресурсов



		//Список групп информационных ресурсов
		if(typeOf(data['igroups'])=='array'){
			this.objects['ir_groups'] = $unlink(data['igroups']);
			data['igroups'].unshift({'igroup_id':'0','full_name':'-[Все информационные ресурсы]-'});
			select_add({
				'list': 'iresource_selector_groups_select',
				'options': data['igroups'],
				'key': 'igroup_id',
				'value': 'full_name',
				'default': 0,
				'clear': true
			});
		}//Список групп информационных ресурсов


	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/


	//Построение списка найденных сотрудников
	employersDataSet: function(data){
		$('employer_search_hint').hide();
		if(!data.length){
			$('employer_search_results').hide();
			$('employer_search_none').show();
			return;
		}else{
			$('employer_search_none').hide();
			$('employer_search_results').show();
		}

		$('employer_search_noselect').show();
		$('employer_search_select').hide();
		$('employer_search_select_bottom').hide();

		if(!this.objects['employer_search_table']){
			this.objects['employer_search_table'] = new jsTable('employer_search_table',{
				'dataBackground1':'#efefef',
				selectType:2,
				columns:[
					{
						caption: 'ФИО сотрудника',
						dataSource:'employer_name',
						width:150,
						dataStyle:{'text-align':'left'}
					},
					{
						caption: 'Должность',
						dataSource:'post_name',
						width:150,
						dataStyle:{'text-align':'left'},
						dataFunction:function(table, cell, text, data){
							return '<b>'+data['company_name']+'</b><br>'+data['post_name'];
						}
					},
					{
						caption: 'Руководитель',
						dataSource:'boss_post_name',
						width:150,
						dataStyle:{'text-align':'left'},
						dataFunction:function(table, cell, text, data){
							var boss_name = '';
							if(String(data['boss_post_uid']) == '0'){
								boss_name = '-Нет руководителя-';
							}else{
								if(data['bosses'].length == 0){
										boss_name = '<font class="error">Отсутствует линейный руководитель</font>';
								}else{
									boss_name = '<p class="neutral" style="display:inline-block;">';
									for(var i=0; i<data['bosses'].length;i++){
										boss_name+= data['bosses'][i]['employer_name']+' (c '+data['bosses'][i]['post_from']+')<br/>';
									}
									boss_name+='</p>';
								}
							}
							return data['boss_post_name']+'<br>'+boss_name;
						}
					}
				]
			});
			this.objects['employer_search_table'].addEvent('click',this.employerSelectUpdateInterface.bind(this));
			$('search_employer_add_button').addEvent('click',this.employerAdd.bind(this));
			$('search_employer_add_button_bottom').addEvent('click',this.employerAdd.bind(this));
		}

		this.objects['employer_search_table'].setData(data);
		this.employerSelectUpdateInterface();
	},//end function




	//Построение списка объектов доступа
	irDataSet: function(data){
		if(!data.length){
			$('ir_roles_none').show();
			$('ir_roles_table').hide();
			return;
		}else{
			$('ir_roles_none').hide();
			$('ir_roles_table').show();
		}

		if(!this.objects['ir_roles_table']){

			//Инициализация таблицы выбора объектов доступа
			this.objects['ir_roles_table'] = new jsTable('ir_roles_table',{
				sectionCollapsible:true,
				columns: [
					{
						width:'30%',
						sortable:false,
						caption: 'Функционал',
						dataSource:'full_name'
					},
					{
						width:'60%',
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
					/*
					{
						width:'140px',
						sortable:false,
						caption: 'Текущий доступ',
						dataSource:'ir_current',
						styles:{'min-width':'140px'},
						dataStyle:{'text-align':'center'},
						dataFunction: function(table, cell, text, data){
							text = String(text);
							if(text == '0') return '---';
							return typeOf(App.pages[PAGE_NAME].objects['ir_types_assoc'][text])=='string' ? App.pages[PAGE_NAME].objects['ir_types_assoc'][text] : '-[??? ID:'+text+']-';
						}
					},
					*/
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
								text[i] = String(text[i]);
								select_add({
									'list': irt,
									'options': [[text[i],typeOf(App.pages[PAGE_NAME].objects['ir_types_assoc'][text[i]])=='string' ? App.pages[PAGE_NAME].objects['ir_types_assoc'][text[i]] : '-[??? ID:'+text[i]+']-']]
								});
							}
							select_set(irt, data['ir_selected']);
							irt.store('iresource_id',data['iresource_id']);
							irt.store('irole_id',data['irole_id']);
							irt.addEvent('change', function(event){
								var iresource_id = this.retrieve('iresource_id');
								var irole_id = this.retrieve('irole_id');
								if(!iresource_id) return false;
								if(typeOf(App.pages[PAGE_NAME].objects['ir_request'][iresource_id])!='array') return false;
								////Читается как UPDATE ARRAY SET [setColumn] = [value] WHERE [termColumn] = [term] LIMIT [limit]
								App.pages[PAGE_NAME].objects['ir_request'][iresource_id].filterUpdate('ir_selected', this.getValue(), 'irole_id', irole_id, 1);
							});
							return '';
						}
					}
				],
				'dataBackground1': '#fff',
				'dataBackground2': '#fff',
				selectType:1,
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

		}

		this.objects['ir_roles_table'].setData(data);
	},//end function





	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/


	//Навигация по страницам мастера - Обработка нажатия на кнопки управления
	pageNavigationEvent: function(event){
		if (!event || (event && typeOf(event.target) != 'element')) return;
		if (event.event.which && event.event.which != 1) return;
		var div = event.target.get('tag') == 'div' ? event.target : event.target.getParent('div');
		var action = 'empty';
		switch(div.id){
			case 'button_step_done': action = 'complete'; break;
			case 'button_step_prev': action = 'prev'; break;
			case 'button_step_next': action = 'next'; break;
		}
		return this.doPage(action);
	},//end function



	//Навигация по страницам мастера
	doPage: function(action){

		var increment = 0, 
			step_title='',
			process,
			show_next_button = true;

		switch(action){
			case 'empty': return;
			case 'first': this.objects['step_index'] = 1; increment = 0; break;
			case 'next': increment = 1; break;
			case 'prev': increment = -1; break;
			case 'complete': return this.requestComplete(); break;
			default: 
				this.objects['step_index'] = parseInt(action); 
				increment = 0; 
			break;
		}

		var index = this.objects['step_index'] + increment;
		if(index < 1 ) return;
		if(index > this.objects['step_max'] ) return;

		if(!$('step_'+index)) return;


		//Обработка перед отображением страницы
		switch(index){

			//Шаг 1: Выбрать сотрудников
			case 1:
				step_title = 'Шаг 1: Выберите сотрудников';
				if(this.objects['selected_employers_count'] > 0) $('employers_list_tool').show(); else $('employers_list_tool').hide();
			break;


			//Шаг 2: Формирование заявки
			case 2:
				process = false;
				show_next_button = false;
				step_title = 'Шаг 2: Формирование заявки';
				if(this.objects['selected_employers_count']==0){
					App.message('Вы не выбрали сотрудников','Выберите хотя бы одного сотрудника, для которого Вы формируете заявку','warning');
					return;
				}
				if(this.objects['ir_for_company_id']==0 || this.objects['ir_for_company_id'] != this.objects['selected_company_id']){
					this.objects['ir_for_company_id'] = this.objects['selected_company_id'];
					this.objects['ir_list'] = null;
					this.objects['ir_request'] = null;
					this.objects['ir_for_employers'] = [];
					this.irClearAll();
					for(var employer_id in this.objects['selected_employers']){
						if(typeOf(this.objects['selected_employers'][employer_id])!='object') continue;
						this.objects['ir_for_employers'].push(this.objects['selected_employers'][employer_id]);
					}
					if(!this.objects['ir_for_employers'].length){
						App.message('Вы не выбрали сотрудников','Выберите хотя бы одного сотрудника, для которого Вы формируете заявку','warning');
						return;
					}
					$('ir_list').empty();
					process = true;
				}else{
					if(typeOf(this.objects['ir_list'])!='array'){
						process = true;
					}else{
						this.irListUpdateInterface();
					}
				}
				$('employers_list_tool').hide();
				if(process) return this.getIRData();
			break;


			//Шаг 3: Заявка сформирована
			case 3:
				step_title = 'Шаг 3: Заявка сформирована и готова к отправке';
				if(this.objects['ir_count'] < 1){
					App.message('Ничего не выбрано','Вы не выбрали информационные ресурсы и функционал, к которому запрашиваете доступ.','warning');
					return;
				}
			break;

		}//Обработка перед отображением страницы


		$('step_title').set('html',step_title);

		this.objects['step_index'] = index;

		if(this.objects['step_index'] == this.objects['step_max']){
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
		this.objects['slideshow'].show($('step_'+index), {
			transition: (increment == 1 ? 'fadeThroughBackground' : 'fadeThroughBackground')
		});



	},//end function






	//Успешное завершение составления заявки
	requestComplete: function(){

		if(!this.objects['contact_form'].validate()) return;
		var iresources = '', access = '', role_id, ir_selected, count=0, ir_count=0, employers='',employers_count=0;
		var index, row;
		if(typeOf(this.objects['ir_request'])!='object'){
			return App.message(
				'Ошибка JavaScript',
				'В заявке отсутствуют информационные ресурсы',
				'error'
			);
		}

		var items = $$('#employers_items LI');
		if(!items.length){
			this.selectedEmployersUpdateInterface();
			return App.message(
				'Отсутствуют заявители',
				'Не найдены сотрудники, для которых оформляется заявка',
				'error'
			);
		}

		for(var i=0; i<items.length; i++){
			var data = items[i].retrieve('employer_info');
			if(typeOf(data)!='object') continue;
			employers+='&e[]='+data['employer_id']+'|'+data['post_uid'];
			employers_count++;
		}

		if(!employers_count){
			return App.message(
				'Ошибка JavaScript',
				'Не найдены сотрудники, для которых оформляется заявка',
				'error'
			);
		}

		//Формирование запроса
		for(var iresource_id in this.objects['ir_request']){
			iresource_id = String(iresource_id).toInt();
			if(!iresource_id) continue;
			if(typeOf(this.objects['ir_request'][iresource_id])!='array') continue;
			ir_count = 0;
			//Формирование массива запрашиваемых объектов доступа по информационному ресурсу
			for(index=0; index < this.objects['ir_request'][iresource_id].length; index++){
				row = this.objects['ir_request'][iresource_id][index];
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
				'action=request.multisave'+
				'&company_id='+encodeURIComponent(this.objects['ir_for_company_id'])+
				'&phone='+encodeURIComponent($('input_ir_phone').value)+
				'&email='+encodeURIComponent($('input_ir_email').value)+
				employers+
				iresources+
				access,
			silent: true,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					$('bigblock_wrapper').hide();
					$('ir_request_complete').show();
				}
			}
		}).request();

	},//end function




	/*******************************************************************
	 * Шаг 1 - поиск сотрудников, формирование списка заявителей
	 ******************************************************************/



	//Поиск сотрудников
	searchEmployers: function(){
		if(!this.objects['search_form'].validate()) return;
		new axRequest({
			url : '/main/ajax/employer',
			data:{
				'action':'curator.employer.search',
				'company_id': (this.objects['selected_employers_count'] == 0 ? $('employer_company').getValue() : this.objects['selected_company_id']),
				'employer_name': $('employer_name').value,
				'search_type': $('search_type').getValue(),
				'term_type': $('term_type').getValue()
			},
			silent: true,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].employersDataSet(data);
				}
			}
		}).request();
	},//end function




	//Выбран сотрудник в результатах поиска сотрудников
	employerSelectUpdateInterface: function(){
		if(!this.objects['employer_search_table'].selectedRows.length){
			$('employer_search_noselect').show();
			$('employer_search_select').hide();
			$('employer_search_select_bottom').hide();
			return;
		}
		$('employer_search_noselect').hide();
		$('employer_search_select').show();
		$('employer_search_select_bottom').show();
	},//end function




	//Добавление сотрудника в список
	employerAdd: function(data){
		if(!this.objects['employer_search_table'].selectedRows.length) return;
		var first_count = this.objects['selected_employers_count'];
		for(var i=0; i<this.objects['employer_search_table'].selectedRows.length; i++){
			var tr = this.objects['employer_search_table'].selectedRows[i];
			if(typeOf(tr)!='element') continue;
			var data = tr.retrieve('data');
			if(typeOf(data)!='object') continue;
			if(typeOf(this.objects['selected_employers'][data['employer_id']])=='object') continue;
			if(this.objects['selected_employers_count'] == 0){
				$('employer_company').setValue(data['company_id']);
				$('employer_company').disable();
				$('employers_none').hide();
				$('employers_list').show();
				$('employers_list_tool').show();
				$('button_step_next').show();
			}
			this.objects['selected_employers'][data['employer_id']] = $unlink(data);
			this.objects['selected_employers_count']++;
			this.objects['selected_company_id']=data['company_id'];
			this.buildEmployerItem(data);
		}
		this.selectedEmployersUpdateInterface();
		this.objects['employer_search_table'].clearSelected();
		this.employerSelectUpdateInterface();
		if(first_count == 0 && this.objects['selected_employers_count'] > 0) $('employers_area').flash('#fffea1','#fff');
	},//end function




	//Удаление сотрудника из списка
	employerDelete: function(){
		if(this.objects['step_index'] != 1) return;
		var items = $$('#employers_items LI.selected');
		if(!items.length) return this.selectedEmployersUpdateInterface();
		for(var i=0; i<items.length; i++){
			var data = items[i].retrieve('employer_info');
			if(typeOf(data)!='object') continue;
			var employer_id = data['employer_id'];
			this.objects['selected_employers'][employer_id] = null;
			this.objects['selected_employers_count']--;
			if(this.objects['selected_employers_count'] == 0){
				$('employer_company').setValue(0);
				$('employer_company').enable();
				$('employers_none').show();
				$('employers_list').hide();
				$('employers_list_tool').hide();
				$('button_step_next').hide();
			}
			items[i].destroy();
		}
		this.selectedEmployersUpdateInterface();
	},//end function




	//Построение элемента выбранного сотрудника в списке выбранных сотрудников
	buildEmployerItem: function(data){

		if(typeOf(data)!='object') return false;

		var li = new Element('li').inject($('employers_items'));
		var employer_name = new Element('div',{'class':'employer_name','html':data['employer_name']}).inject(li);
		var employer_company = new Element('div',{'class':'employer_post','html':data['company_name']}).inject(li);
		var employer_post = new Element('div',{'class':'employer_post','html':data['post_name']}).inject(li);
		//Клик по выбранному сотруднику
		li.addEvent('click',function(e){
			if(this.hasClass('selected')) this.removeClass('selected'); else this.addClass('selected');
			App.pages[PAGE_NAME].selectedEmployersUpdateInterface();
		}).store('employer_info',data);

	},//end function


	//Обновление интерфейса выбранных сотрудников
	selectedEmployersUpdateInterface: function(){
		var items = $$('#employers_items LI.selected');
		if(!items.length){
			$('employers_list_tool_unselect').show();
			$('employers_list_tool_select').hide();
			return;
		}
		$('employers_list_tool_unselect').hide();
		$('employers_list_tool_select').show();
	},//end function




	//Запрос данных для начала формирования заявки
	getIRData: function(){
		if(this.objects['ir_list'] != null) return true;

		new axRequest({
			url : '/main/ajax/request',
			data:{
				'action':'get.irdata',
				'company_id': this.objects['ir_for_company_id']
			},
			silent: true,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					if(typeOf(data['irlist'])!='array' || !data['irlist'].length){
						App.message(
							'Нет доступных информационных ресурсов',
							'Для сотрудников выбранной организации в настоящий момент нет доступных информационных ресурсов.<br/><br/>'+
							'Свяжитесь с администратором для разрешения данной ситуации.',
							'error'
						);
						return;
					}
					App.pages[PAGE_NAME].setData(data);
					App.pages[PAGE_NAME].doPage('next');
					$('step_2_filter_area').flash('#e1e181','#fff',5);
				}
			}
		}).request();
	},//function



	/*******************************************************************
	 * Шаг 2 - выбор ИР, формирвоание заявки
	 ******************************************************************/


	//Обновление интерфейса: отображение/сокрытие списка ИР
	irListUpdateInterface: function(){
		if(this.objects['ir_count']<1){
			$('ir_area').hide();
			$('ir_none').show();
			$('ir_trash_area').hide();
			$('button_step_next').hide();
		}else{
			$('ir_area').show();
			$('ir_none').hide();
			$('ir_trash_area').show();
			$('button_step_next').show();
		}
	},//end function


	//Открытие окна выбора информационного ресурса
	irSelectorOpen: function(){
		$('bigblock_wrapper').hide();
		$('iresource_selector_complete_button').hide();
		$('iresource_selector').show();
		this.irSelectorChangeGroup();
	},//end function



	//Закрытие окна выбора информационного ресурса
	irSelectorClose: function(){
		$('iresource_selector').hide();
		$('bigblock_wrapper').show();
		this.irListUpdateInterface();
	},//end function



	//Выбран информационный ресурс
	irSelectorComplete: function(){
		$('iresource_selector_complete_button').hide();
		if(!this.objects['iresource_selector_table'].selectedRows.length) return;
		var tr = this.objects['iresource_selector_table'].selectedRows[0];
		if(typeOf(tr)!='element') return;
		var data = tr.retrieve('data');
		if(typeOf(data)!='object') return;
		$('iresource_selector').hide();
		this.irRolesOpen(data['iresource_id']);
	},//end function



	//Выбор группы информационных ресурсов
	irSelectorChangeGroup: function(){
		$('iresource_selector_complete_button').hide();
		$('iresources_filter').setValue('');
		var igroup_id = $('iresource_selector_groups_select').getValue();
		if(String(igroup_id)=='0'){
			this.objects['iresource_selector_table'].setData(this.objects['ir_list']);
			return;
		}
		this.objects['iresource_selector_table'].setData(
			this.objects['ir_list'].filterSelect({
				'igroup_id':{
					'value': igroup_id,
					'condition': '='
				}
			})
		);
	},//end function



	//Выбор информационного ресурса
	irSelectorSelectIResource: function(){
		$('iresource_selector_complete_button').hide();
		if(!this.objects['iresource_selector_table'].selectedRows.length) return;
		var tr = this.objects['iresource_selector_table'].selectedRows[0];
		if(typeOf(tr)!='element') return;
		var data = tr.retrieve('data');
		if(typeOf(data)!='object') return;
		$('iresource_selector_complete_button').show();
	},//end function



	//Выбор группы информационных ресурсов
	irSelectorFilter: function(){
		this.objects['iresource_selector_table'].filter($('iresources_filter').getValue());
	},//end function



	//Открытие окна выбора ролей доступа в заявке
	irRolesOpen: function(iresource_id){
		if(this.objects['ir_request'] == null){
			this.objects['ir_request'] = {};
			this.objects['iresources'] = {};
		}
		if(!iresource_id) $('ir_selector_iresource_list').setValue(0);
		this.irRolesChangeIresource(iresource_id);
		$('bigblock_wrapper').hide();
		$('ir_roles').show();
		$('ir_roles_wrapper').scrollTo(0, 0);
	},//end function



	//Смена информационного ресурса в списке ролей доступа в заявке
	irRolesChangeIresource: function(iresource_id){
		var iresource_list = $('ir_roles_iresource_list');
		if(iresource_id){
			iresource_id = parseInt(iresource_id);
			select_set(iresource_list, iresource_id);
		}else{
			iresource_id = parseInt(iresource_list.getValue());
		}
		$('ir_roles_table').hide();
		$('ir_roles_none').hide();
		$('ir_roles_select').hide();

		if(!iresource_id){
			$('ir_roles_select').show();
			return;
		}

		//Объекты выбранного ИР еще не закешированы
		if(typeOf(this.objects['ir_request'][iresource_id])!='array'){
			iresource_list.disable();
			new axRequest({
				url : '/main/ajax/request',
				data:{
					'action':'get.irroles',
					'iresource_id': iresource_id,
					'company_id': this.objects['ir_for_company_id']
				},
				silent: true,
				waiter: true,
				callback: function(success, status, data){
					iresource_list.enable();
					if(success){
						if(typeOf(data)!='array') return;
						App.pages[PAGE_NAME].objects['ir_request'][iresource_id] = data;
						App.pages[PAGE_NAME].irRolesIresourceBuild(iresource_id);
						select_add({
							'list': 'ir_roles_iresource_list',
							'options': [{'iresource_id':iresource_id,'full_name':App.pages[PAGE_NAME].objects['ir_list'].filterResult('full_name', 'iresource_id', iresource_id)}],
							'key': 'iresource_id',
							'value': 'full_name',
							'clear': false
						});
						select_sort('ir_roles_iresource_list');
						select_set(iresource_list, iresource_id);
					}
				}
			}).request();

		}//Объекты выбранного ИР еще не закешированы
		else{
			this.irRolesIresourceBuild(iresource_id);
		}
	},//end function



	//Закрытие окна выбора ролей доступа в заявке
	irRolesClose: function(){
		$('ir_roles').hide();
		$('bigblock_wrapper').show();
	},//end function



	//Построение списка объектов доступа
	irRolesIresourceBuild: function(iresource_id){
		if(typeOf(this.objects['ir_request'][iresource_id])!='array'){
			return App.message(
				'Ошибка JavaScript',
				'Массив объектов доступа для информационного ресурса задан некорректно.<br/><br/>'+
				'Свяжитесь с администратором для разрешения данной ситуации.',
				'error'
			);
		}
		this.irDataSet(this.objects['ir_request'][iresource_id]);
	},//end function




	//Добавление информационного ресурса и выбранных прав доступа в итоговый слайдер
	irRolesComplete: function(){
		iresource_id = parseInt($('ir_roles_iresource_list').getValue());
		if(!iresource_id || typeOf(this.objects['ir_request'][iresource_id])!='array'){
			return App.message(
				'Ошибка JavaScript',
				'Информационный ресурс указан некорректно',
				'error'
			);
		}

		var item_exists = false;
		var idata = [], row, section=null, is_section;

		//Формирование массива запрашиваемых объектов доступа по информационному ресурсу
		for(var index=0; index < this.objects['ir_request'][iresource_id].length; index++){

			row = this.objects['ir_request'][iresource_id][index];
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
			this.irRemove(iresource_id);
		}else{
			//Проверка существования объектов в результирующем списке
			if(typeOf(this.objects['iresources'])!='object') this.objects['iresources'] = {};
			if(typeOf(this.objects['iresources'][iresource_id])!='object'){
				this.objects['iresources'][iresource_id] = {
					'item': null,
					'table': null
				};
			}else{
				if(this.objects['iresources'][iresource_id]['item']) item_exists = true;
			}
			if(!item_exists){
				this.irAdd(iresource_id);
				this.objects['ir_count']++;
			}
			this.objects['iresources'][iresource_id]['table'].setData(idata);
		}

		this.irListUpdateInterface();

		//Закрытие окна выбора
		this.irRolesClose();

	},//end function



	/*Отображение / сокрытие секций таблицы*/
	irRolesSectionsShow: function(){
		if(this.objects['ir_roles_table']) this.objects['ir_roles_table'].allSectionsDisplay(true);
	},//end function
	irRolesSectionsHide: function(){
		if(this.objects['ir_roles_table']) this.objects['ir_roles_table'].allSectionsDisplay(false);
	},//end function




	//Создание элемента ИР в слайдере
	irAdd: function(iresource_id){

		var li = new Element('li',{'class':'dark'}).inject('ir_list');
		var heading = new Element('h3',{'class':'opened'}).inject(li);
		var heading_collapser = new Element('a',{'class':'collapser'}).inject(heading);
		var heading_toolbar = new Element('div',{'class':'toolbar'}).inject(heading);
		var heading_title = new Element('span').inject(heading).set('html',this.objects['ir_list'].filterResult('full_name', 'iresource_id', iresource_id));

		this.objects['iresources'][iresource_id]['item'] = li;

		//Редактирование ИР
		new Element('span',{
			'title':'Редактировать запрашиваемый функционал',
			'class':'ui-icon-white ui-icon-pencil'
		}).inject(heading_toolbar).setStyles({
			'cursor':'pointer'
		}).addEvents({
			click: function(e){
				App.pages[PAGE_NAME].irRolesOpen(iresource_id);
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
					'Вы действительно хотите убрать из заявки информационный ресурс: '+App.pages[PAGE_NAME].objects['ir_list'].filterResult('full_name', 'iresource_id', iresource_id)+'?',
					'CONFIRM',
					function(){
						App.pages[PAGE_NAME].irRemove(iresource_id);
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
		this.objects['iresources'][iresource_id]['table'] = new jsTable(container,{
			'class': 'jsTableLight',
			columns: [
				{
					width:'30%',
					sortable:false,
					caption: 'Функционал',
					dataSource:'full_name'
				},
				{
					width:'60%',
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
				/*
				{
					caption: 'Текущий доступ',
					sortable:false,
					width:'120px',
					dataSource:'ir_current',
					dataStyle:{'text-align':'center','min-width':'120px'},
					dataFunction:function(table, cell, text, data){
						if(String(text) == '0') return '---';
						return typeOf(App.pages[PAGE_NAME].objects['ir_types_assoc'][text])=='string' ? App.pages[PAGE_NAME].objects['ir_types_assoc'][text] : '-[??? ID:'+text+']-';
					}
				},
				*/
				{
					caption: 'Запросить доступ',
					sortable:false,
					width:'120px',
					dataSource:'ir_selected',
					dataStyle:{'text-align':'center','min-width':'120px'},
					dataFunction:function(table, cell, text, data){
						if(String(text) == '0') return '--Нет--';
						return typeOf(App.pages[PAGE_NAME].objects['ir_types_assoc'][text])=='string' ? App.pages[PAGE_NAME].objects['ir_types_assoc'][text] : '-[??? ID:'+text+']-';
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

	},//end function




	//Удаление из заявки конкретного ИР
	irRemove: function(iresource_id){
		if(typeOf(this.objects['ir_request'])=='object'){
			if(typeOf(this.objects['ir_request'][iresource_id])=='array'){
				for(var i=0; i< this.objects['ir_request'][iresource_id].length; i++){
					if(typeOf(this.objects['ir_request'][iresource_id][i])=='object')
					this.objects['ir_request'][iresource_id][i]['ir_selected']=0;
				}
			}
		}
		if(typeOf(this.objects['iresources'])=='object'){
			if(typeOf(this.objects['iresources'][iresource_id])=='object'){
				if(this.objects['iresources'][iresource_id]['table']){
					this.objects['iresources'][iresource_id]['table'].terminate();
					this.objects['iresources'][iresource_id]['table'] = null;
				}
				if(this.objects['iresources'][iresource_id]['item']){
					this.objects['iresources'][iresource_id]['item'].destroy();
					this.objects['iresources'][iresource_id]['item'] = null;
				}
				this.objects['ir_count']--;
				this.irListUpdateInterface();
			}
			this.objects['iresources'][iresource_id] = null;
		}
	},//end function



	//Удаление из заявки всех ИР
	irRemoveAll: function(){
		App.message(
			'Подтвердите действие',
			'Вы действительно хотите убрать из заявки все выбранные информационные ресурсы?',
			'CONFIRM',
			function(){
				if(typeOf(this.objects['ir_request'])=='object'){
					for(var iresource_id in this.objects['ir_request']){
						if(typeOf(this.objects['ir_request'][iresource_id])=='array'){
							for(var i=0; i< this.objects['ir_request'][iresource_id].length; i++){
								if(typeOf(this.objects['ir_request'][iresource_id][i])=='object')
								this.objects['ir_request'][iresource_id][i]['ir_selected']=0;
							}
						}
					}
				}
				this.irClearAll();
			}.bind(this)
		);
	},//end function




	//Удаление объектов из результирующего списка
	irClearAll: function(){

		if(typeOf(this.objects['ir_request'])=='object'){
			for(var iresource_id in this.objects['ir_request']){
				if(typeOf(this.objects['ir_request'][iresource_id])=='array'){
					for(var i=0; i< this.objects['ir_request'][iresource_id].length; i++){
						if(typeOf(this.objects['ir_request'][iresource_id][i])=='object'){
							this.objects['ir_request'][iresource_id][i]['ir_selected']=0;
						}
					}
				}
			}
		}

		if(typeOf(this.objects['iresources'])=='object'){
			for(var i in this.objects['iresources']){
				if(typeOf(this.objects['iresources'][i])=='object'){
					if(this.objects['iresources'][i]['table']){
						this.objects['iresources'][i]['table'].terminate();
						this.objects['iresources'][i]['table'] = null;
					}
					if(this.objects['iresources'][i]['item']){
						this.objects['iresources'][i]['item'].destroy();
						this.objects['iresources'][i]['item'] = null;
					}
				}
				this.objects['iresources'][i] = null;
			}
		}

		this.objects['iresources'] = null;
		this.objects['ir_count']=0;
		$('ir_list').empty();
		this.irListUpdateInterface();
	},//end function


	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();