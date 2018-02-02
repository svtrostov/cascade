<div class="page_employers_info">

	<div class="bigblock" id="employer_info_wrapper">
		<div class="titlebar"><h3 id="employer_info_title">Карточка сотрудника</h3></div>
		<div class="contentwrapper">
			<div class="contentareafull">

				<div id="tabs_area" class="tabs_area absolute">
				<!--tabs_area-->

					<ul class="tabs">
						<li class="tab">Профиль</li>
						<li class="tab">Группы</li>
						<li class="tab">Должности</li>
						<li class="tab">Замещение</li>
						<li class="tab">Права</li>
					</ul>

					<div class="tab_content">
					<!--Профиль-->
						<div class="toolarea">
							<div class="toolbutton profile" id="profile_info_button"><div>Сотрудник</div></div>
							<div class="toolbutton mail" id="profile_notice_button"><div>Уведомления</div></div>
							<div class="toolbutton security" id="profile_account_button"><div>Учетная запись</div></div>
							<div class="toolbutton certificate" id="profile_certificate_button"><div>Сертификаты</div></div>
						</div>
						<div id="profile_wrapper">
							<div id="profile_step_container">
								<div class="steparea" id="profile_step_info"></div>
								<div class="steparea" id="profile_step_account"></div>
								<div class="steparea" id="profile_step_notice"></div>
								<div class="steparea" id="profile_step_certificate"></div>
							</div>
						</div>
					<!--Профиль-->
					</div>



					<div class="tab_content">
					<!--Группы-->
						<div id="groups_area">
							<div id="employer_groups_area" class="left_panel">
								<div id="employer_groups_area_table" class="table_area"></div>
							</div>

							<div id="all_groups_area" class="right_panel">
								<div id="all_groups_area_table" class="table_area"></div>
							</div>

							<div id="groups_splitter" class="splitter">
								<div id="groups_splitter_handle" class="splitter_handle"></div>
								<div class="toolbutton include" id="button_group_include"><div>Включить</div></div>
								<div class="toolbutton exclude" id="button_group_exclude"><div>Исключить</div></div>
							</div>

						</div>
					<!--Группы-->
					</div>



					<div class="tab_content">
					<!--Должности-->
						<div>
							<div class="ui-button-light" style="" onclick="employers_info_post_add();" style="margin:5px 0px;"><span>Добавить должность</span></div>
							<div id="post_delete_button_area">
								<select style="width:300px;" id="post_delete_type">
									<option value="delete">Удалить должность без блокировки доступа</option>
									<option value="deletelock" selected>Удалить должность и заблокировать доступ</option>
								</select>
								<div class="ui-button-light" style="" onclick="employers_info_post_delete();" style="margin:5px 0px;"><span>Удалить</span></div>
								
							</div>
						</div>
						<div id="employer_posts_area_table"></div>
					<!--Должности-->
					</div>



					<div class="tab_content">
					<!--Делегирование-->
						<div class="toolarea">
							<div class="toolbutton assistants" id="assistants_assistants_button"><div>Заместители</div></div>
							<div class="toolbutton delegates" id="assistants_delegates_button"><div>Замещает</div></div>
							<div class="toolbutton history" id="assistants_history_button"><div>История</div></div>
						</div>
						<div id="assistants_wrapper">
							<div id="assistants_step_container">
								<div class="steparea" id="assistants_step_assistants">
									<div class="ui-button-light" onclick="employers_info_assistants_selector_open(false);" style="width:200px;margin:5px 0px;"><span>Добавить заместителя</span></div>
									<div id="assistants_table_area"></div>
								</div>
								<div class="steparea" id="assistants_step_delegates">
									<div class="ui-button-light" onclick="employers_info_assistants_selector_open(true);" style="margin:5px 0px;"><span>Сделать заместителем другого сотрудника</span></div>
									<div id="delegates_table_area"></div>
								</div>
								<div class="steparea" id="assistants_step_history"></div>
							</div>
						</div>
					<!--Делегирование-->
					</div>



					<div class="tab_content">
					<!--Права-->
						<div id="containers_roles_area">
							<div id="employer_rights_area" class="left_panel">
								<div class="table_filter">
									<div class="flabel">Права:</div>
									<div class="fselect"><select id="employer_rights_select" onchange="employers_info_rights_select_change();">
										<option value="can_add_employer" selected>Заполнять анкеты на новых сотрудников</option>
										<option value="can_curator">Оформлять заявки для сотрудников</option>
									</select></div>
								</div>
								<div id="employer_rights_area_table" class="table_area"></div>
							</div>

							<div id="all_right_area" class="right_panel">
								<div id="all_rights_area_table" class="table_area"></div>
							</div>

							<div id="right_splitter" class="splitter">
								<div id="right_splitter_handle" class="splitter_handle"></div>
								<div class="toolbutton include" id="button_right_include"><div>Включить</div></div>
								<div class="toolbutton exclude" id="button_right_exclude"><div>Исключить</div></div>
							</div>

						</div>
					<!--Права-->
					</div>


				<!--tabs_area-->
				</div>

				<div id="tabs_none" style="display:none;">
					<h1 class="errorpage_title">Сотрудник не найден</h1>
				</div>

			</div>
		</div>
	</div>


	<div id="tmpl_profile_info" style="padding:10px;display:none;width:100%;">
		<div class="iline w200"><span>Идентификатор:</span><input style="width:150px;" type="text" class="disabled" readonly="true" value="" id="info_employer_id"/></div>
		<div class="iline w200" id="info_anket_id_area" style="display:none;margin-top:10px;"><span>Анкета сотрудника:</span><a href="#" id="info_anket_id_link" target="_blank">Просмотреть</a></div>
		<br/><br/>
		<div class="iline w200"><span>Фамилия*:</span><input style="width:200px;" type="text" id="info_last_name"/></div>
		<div class="iline w200"><span>Имя*:</span><input style="width:200px;" type="text" id="info_first_name"/></div>
		<div class="iline w200"><span>Отчество*:</span><input style="width:200px;" type="text" id="info_middle_name"/></div>
		<div class="iline w200"><span>Дата рождения:</span><input class="calendar_input" style="width:100px;" type="text" value="" id="info_birth_date"/></div>
		<br/><br/>
		<div class="iline w200"><span>Контактный телефон*:</span><input style="width:200px;" maxlength="64" type="text" value="" id="info_phone"/></div>
		<div class="iline w200"><span>Электронная почта:</span><input style="width:200px;" maxlength="64" type="text" value="" id="info_email"/></div>
		<div class="ui-button-light" style="margin-left:210px;" onclick="employers_info_profile_change_info('info');" style="margin:5px 0px;"><span>Сохранить изменения</span></div>
	</div>


	<div id="tmpl_notice_info" style="padding:10px;display:none;width:100%;">
		<div class="iline w300"><span>Процесс обработки заявок, где заявитель:</span><input type="checkbox" id="info_notice_me_requests"/></div>
		<div class="iline w300"><span>Процесс обработки заявок, где куратор:</span><input type="checkbox" id="info_notice_curator_requests"></div>
		<div class="iline w300"><span>Заявки, поступившие на согласование:</span><input type="checkbox" id="info_notice_gkemail_1"></div>
		<div class="iline w300"><span>Заявки, поступившие на утверждение:</span><input type="checkbox" id="info_notice_gkemail_2"></div>
		<div class="iline w300"><span>Заявки, поступившие на исполнение:</span><input type="checkbox" id="info_notice_gkemail_3"></div>
		<div class="iline w300"><span>Уведомления о заявках сотрудников:</span><input type="checkbox" id="info_notice_gkemail_4"></div>
		<div class="ui-button-light" style="margin-left:310px;" onclick="employers_info_profile_change_info('notice');" style="margin:5px 0px;"><span>Сохранить изменения</span></div>
	</div>

	<div id="tmpl_profile_account_username" style="padding:10px;display:none;width:100%;">
		<div class="iline w150"><span>Имя пользователя:</span><input style="width:150px;" type="text"value="" id="info_username"/></div>
		<div class="ui-button-light" style="margin-left:160px;" onclick="employers_info_profile_change_info('username');" style="margin:5px 0px;"><span>Сменить имя пользователя</span></div>
	</div>

	<div id="tmpl_profile_account_password" style="padding:10px;display:none;width:100%;">
		<div class="iline w150"><span>Новый пароль:</span><input style="width:200px;" maxlength="64" type="text" value="" id="info_password"/></div>
		<div class="ui-button-light" style="margin-left:160px;" onclick="employers_info_profile_change_info('password');" style="margin:5px 0px;"><span>Сменить пароль</span></div>
		<div class="ui-button-light" onclick="$('info_password').set('value',generate_password(8));" style="margin:5px 0px;"><span>Случайно</span></div>
	</div>

	<div id="tmpl_profile_account_pincode" style="padding:10px;display:none;width:100%;">
		<div class="iline w150"><span>Вход без PIN-кода:</span><input type="checkbox" id="info_ignore_pin"></div>
		<div class="iline w150"><span>Новый PIN-код:</span><input style="width:200px;" maxlength="6" type="text" value="" id="info_pin_code"/></div>
		<div class="ui-button-light" style="margin-left:160px;" onclick="employers_info_profile_change_info('pincode');" style="margin:5px 0px;"><span>Сохранить</span></div>
		<div class="ui-button-light" onclick="$('info_pin_code').set('value',generate_pin(4));" style="margin:5px 0px;"><span>Случайно</span></div>
	</div>

	<div id="tmpl_profile_account_print" style="padding:10px;display:none;width:100%;">
		<h2>Выберите, какие сведения Вы хотите вывести на печать</h2>
		<div class="iline w250"><span>Контактная информация сотрудника:</span><input type="checkbox" id="print_info" checked="true"></div>
		<div class="iline w250"><span>Пароль:</span><input type="checkbox" id="print_password" checked="true"></div>
		<div class="iline w250"><span>PIN-код:</span><input type="checkbox" id="print_pin_code" checked="true"></div>
		<div class="ui-button-light" style="margin-left:260px;" onclick="employers_info_profile_print('pdf');" style="margin:5px 0px;"><span>PDF</span></div>
		<div class="ui-button-light" onclick="employers_info_profile_print('docx');" style="margin:5px 0px;"><span>PIN-конверт</span></div>
	</div>

	<div id="tmpl_profile_account_access" style="padding:10px;display:none;width:100%;">
		<div class="iline w200"><span>Статус учетной записи:</span><select style="width:140px;" id="info_status"><option value="0">Заблокирован</option><option value="1">Активен</option></select></div>
		<div class="iline w200"><span>Уроведь доступа (AL)*:</span><input style="width:100;" type="text" id="info_access_level"/></div>
		<div class="ui-button-light" style="margin-left:210px;" onclick="employers_info_profile_change_info('access');" style="margin:5px 0px;"><span>Сохранить</span></div>
	</div>

	<div id="tmpl_profile_certificate_upload" style="padding:10px;display:none;width:100%;">
		<h2>Выберите файл сертификата клиента X.509 в формате Base64</h2>
		<form action="/admin/ajax/employers" method="post" enctype="multipart/form-data" id="certificate_upload_form">
			<input type="hidden" name="action" value="employers.certificate.upload"/>
			<input type="hidden" name="ajax" value="1"/>
			<input type="hidden" name="employer_id" value=""/>
			<div class="file_input_div">
				<!--input type="button" value="Выберите файл..."/-->
				<div class="ui-button-light" style="position: absolute;width:150px;"><span>Выберите файл...</span></div>
				<input type="file" name="certificate" class="file_input_hidden"/>
				<div class="ui-button-light" onclick="employers_info_profile_upload_certificate();" style="display:none;margin-left:170px;width:150px;" id="upload_certificate_button"><span>Загрузить на сервер</span></div>
			</div>
		</form>
	</div>

	<div id="tmpl_profile_certificate" style="display:none;width:100%;"></div>



	<div class="bigblock" id="post_selector" style="display:none;">
		<div class="titlebar"><h3 id="post_selector_title">Выберите организацию и должность</h3></div>
		<div class="contentwrapper">
			<div class="contentarea" id="post_selector_wrapper">

				<div id="post_selector_companies_area" class="sleft_panel">
					<select id="post_selector_companies_select" size="2" onchange="employers_info_post_company_select();"></select>
				</div>
				<div id="post_selector_org_structure" class="sright_panel">
					<div class="table_filter">
						<div class="flabel">Фильтр:</div>
						<div class="fbutton" id="posts_filter_button"></div>
						<div class="finput"><input type="text" value="" id="posts_filter"/></div>
					</div>

					<div id="post_selector_org_structure_area" class="org_tree"></div>
				</div>
				<div id="post_selector_splitter" class="small_splitter"></div>

			</div>
			<div class="buttonarea">
				<div class="ui-button" id="post_selector_complete_button" onclick="employers_info_post_selector_complete();"><span>Выбрать должность</span></div>
				<div class="ui-button" onclick="employers_info_post_add_cancel();"><span>Закрыть</span></div>
			</div>
		</div>
	</div>


	<div class="bigblock" id="post_selector_period" style="display:none;">
		<div class="titlebar"><h3>Укажите период работы сотрудника на выбранной должности</h3></div>
		<div class="contentwrapper">
			<div class="contentarea" id="post_selector_period_wrapper"><div class="w700c">
				<h1>Добавление должности сотруднику:</h1>
				<ul class="requestlist"><li><div class="iline" id="post_selector_selected_name"></div></li></ul>
				<br>
				<h1>Занимает указанную должность:</h1>
				<div class="iline w250"><span>Начиная с даты (включительно):</span><input type="text" style="width:120px;" id="post_selector_post_date_from" value="" class="calendar_input"/></div>
				<div class="iline w250"><span>Заканчивая датой (включительно):</span><input type="text" style="width:120px;" id="post_selector_post_date_to" value="31.12.2099" class="calendar_input"/></div>
				<br>
				<div class="iline w250"><span>Оформить заявку из шаблона:</span><input type="checkbox" id="post_selector_post_template" value="1"/></div>
				<br>
				<div class="ui-button-light" style="margin-left:260px;" onclick="employers_info_post_selector_done();"><span>Добавить должность</span></div>
			</div></div>
			<div class="buttonarea">
				<div class="ui-button" onclick="employers_info_post_add_cancel();"><span>Закрыть</span></div>
			</div>
		</div>
	</div>



	<div class="bigblock" id="assistants_selector" style="display:none;">
		<div class="titlebar"><h3>Выберите сотрудника для замещения</h3></div>
		<div class="contentwrapper">
			<div class="contentarea" id="assistants_selector_wrapper"><div class="w700c">
				<div class="iline wauto">
					<span>Поиск сотрудника:</span>
					<input type="text" style="width:400px;" id="assistants_selector_term" value="" placeholder="Введите фамилию сотрудника..."/>
					<input type="button" style="width:60px;" id="assistants_selector_term_button" value="Поиск"/>
				</div>
				<br/>
				<div id="assistants_selector_none" style="display:none;"><h2>Сотрудники не найдены...</h2></div>
				<div id="assistants_selector_table" style="display:none;">
					<ul class="requestlist" id="assistants_list"></ul>
				</div>
			</div></div>
			<div class="buttonarea">
				<div class="ui-button" id="assistants_selector_complete_button" onclick="employers_info_assistants_selector_complete();"><span>Выбрать сотрудника</span></div>
				<div class="ui-button" onclick="employers_info_assistants_selector_cancel();"><span>Закрыть</span></div>
			</div>
		</div>
	</div>



	<div class="bigblock" id="assistants_selector_period" style="display:none;">
		<div class="titlebar"><h3>Укажите период замещения</h3></div>
		<div class="contentwrapper">
			<div class="contentarea" id="assistants_selector_wrapper"><div class="w700c">
				<h1>Выбранный сотрудник:</h1>
				<ul class="requestlist"><li><div class="iline" id="assistants_selector_selected_name"></div></li></ul>
				<br>
				<h1>На период:</h1>
				<div class="iline w250"><span>Начиная с даты (включительно):</span><input type="text" style="width:120px;" id="assistants_selector_date_from" value="" class="calendar_input"/></div>
				<div class="iline w250"><span>Заканчивая датой (включительно):</span><input type="text" style="width:120px;" id="assistants_selector_date_to" value="" class="calendar_input"/></div>
				<br>
				<div class="ui-button-light" id="assistants_selector_done_button" style="margin-left:260px;" onclick="employers_info_assistants_selector_done();"><span>Делегировать полномочия</span></div>
			</div></div>
			<div class="buttonarea">
				<div class="ui-button" onclick="employers_info_assistants_selector_cancel();"><span>Закрыть</span></div>
			</div>
		</div>
	</div>


</div>