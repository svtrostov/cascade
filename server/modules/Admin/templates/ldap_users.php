<div class="page_ldap_users" id="page_ldap_users">

	<div class="bigblock" id="bigblock_wrapper">
		<div class="titlebar normal">
			<a class="expander" href="#" id="bigblock_expander"></a>
			<h3 id="bigblock_title">LDAP users</h3>
		</div>
		<div class="contentwrapper">
			<div class="contentareafull">

				<div id="filter_area">

					<div class="filter_line">
						<div class="left">
							<div class="ui-button-light" id="reload_button"><span class="ileft icon_reload" style="padding-left:25px;">Перегрузить</span></div>
						</div>
						<div class="left">
							<input type="text" value="" id="filter_search_name" placeholder="Фильтр пользователей..."/>
							<div class="ui-button-light" id="filter_button"><span class="ileft icon_filter" style="padding-left:25px;">Фильтр</span></div>
						</div>
						<div class="left" id="user_tool_area">
							<div class="ui-button-light" id="import_button"><span class="ileft icon_import" style="padding-left:25px;">Импорт сотрудника</span></div>
						</div>
					</div>

				</div>


				<div id="users_area">
					<div id="users_table_wrapper"><div id="users_table"></div></div>
					<div id="users_none" style="display:none;">
						<h1 class="errorpage_title">Пользователи не найдены</h1>
						<h2 class="errorpage_subtitle">Отсутствующие в локальной базе Пользователи домена не найдены</h2>
					</div>
				</div>


			</div>
		</div>
	</div>



	<div class="bigblock" id="import_info_wrapper" style="display:none;">
		<div class="titlebar"><h3 id="import_info_title">Импорт сотрудника</h3></div>
		<div class="contentwrapper">
			<div class="contentareafull">

				<div id="tabs_area" class="tabs_area absolute">
				<!--tabs_area-->

					<ul class="tabs">
						<li class="tab">Импортируемый сотрудник</li>
						<li class="tab">Похожие сотрудники</li>
					</ul>


					<div class="tab_content">
					<!--Импортируемый сотрудник-->

						<div class="tab_content_area" id="import_form_area">
							<div class="splitline"></div>
							<h1>Сведения Active Directory</h1>
							<div class="fline w200"><span>Имя пользователя:</span><p id="adinfo_username"></p></div>
							<div class="fline w200"><span>ФИО пользователя:</span><p id="adinfo_displayname"></p></div>
							<div class="fline w200"><span>Должность:</span><p id="adinfo_title"></p></div>
							<div class="fline w200"><span>Организация:</span><p id="adinfo_company"></p></div>
							<div class="fline w200"><span>Подразделение:</span><p id="adinfo_department"></p></div>
							<div class="fline w200"><span>E-mail:</span><p id="adinfo_mail"></p></div>
							<div class="fline w200"><span>Телефон:</span><p id="adinfo_telephone"></p></div>
							<div class="splitline"></div>
							<h1>Указанная должность</h1>
							<div id="selected_post_area" style="display:none;">
								<div class="fline w200"><span>Организация:</span><p id="selected_company_name"></p></div>
								<div class="fline w200"><span>Должность:</span><p id="selected_post_name"></p></div>
							</div>
							<input type="button" id="change_post_button" value="Задать должность..."/>
							<input type="button" id="change_post_cancel_button" value="Отмена"/>
							<div class="splitline"></div>
							<h1>Контактная информация</h1>
							<div class="fline w200"><span>Фамилия*:</span><input style="width:200px;" type="text" maxlength="32" value="" id="info_last_name"/></div>
							<div class="fline w200"><span>Имя*:</span><input style="width:200px;" type="text" maxlength="32" value="" id="info_first_name"/></div>
							<div class="fline w200"><span>Отчество*:</span><input style="width:200px;" type="text" maxlength="32" value=""  id="info_middle_name"/></div>
							<div class="fline w200"><span>Дата рождения:</span><input style="width:100px;" type="text" class="calendar_input" value="" id="info_birth_date"/></div>
							<div class="fline w200"><span>Контактный телефон:</span><input style="width:200px" placeholder="Пример: 8 (800) 123-45-67" maxlength="64" type="text" value="" id="info_phone"/></div>
							<div class="fline w200"><span>Электронная почта:</span><input style="width:200px;" placeholder="Пример: example@example.ru" maxlength="64" type="text" value="" id="info_email"/></div>
							<div class="splitline"></div>
							<div id="anket_checked_form">
								<div  style="margin-left:210px;margin-top:10px;">
									<h1>Импорт сотрудника</h1>
									Нажав на кнопку &laquo;Импорт сотрудника&raquo;, будет создана локальная учетная запись сотрудника, <br/>
									а также будут автоматически сгенерированы заявки на доступ к корпоративным информационным ресурсам:<br/>
									<input id="template_post" type="checkbox" value="1" checked="true"/> на основании занимаемой сотрудником должности из типового шаблона доступа (при наличии такового)<br/>
									<br/>
									<div class="ui-button-light" style="margin:5px 0px;" id="import_cancel_button"><span>Отмена</span></div>
									<div class="ui-button-light" style="margin:5px 0px;" id="import_complete_button"><span>Импорт сотрудника</span></div>
								</div>
							</div>
						</div>

					<!--Импортируемый сотрудник-->
					</div>



					<div class="tab_content">
					<!--Похожие сотрудники-->

						<div class="tab_content_area">
							<div id="employers_table"></div>
							<div id="employers_none" style="display:none;">
								<h1 class="errorpage_title">Сотрудники не найдены</h1>
							</div>
						</div>

					<!--Похожие сотрудники-->
					</div>


				<!--tabs_area-->
				</div>

			</div>
		</div>
	</div>




	<div class="bigblock" id="post_selector" style="display:none;">
		<div class="titlebar"><h3 id="post_selector_title">Выберите организацию и должность</h3></div>
		<div class="contentwrapper">
			<div class="contentarea" id="post_selector_wrapper">

				<div id="post_selector_companies_area" class="sleft_panel">
					<select id="post_selector_companies_select" size="2"></select>
				</div>
				<div id="post_selector_org_structure" class="sright_panel">
					<div class="table_filter">
						<div class="flabel">Фильтр:</div>
						<div class="fbutton" id="posts_filter_button"></div>
						<div class="finput"><input type="text" value="" id="posts_filter"/></div>
					</div>

					<div id="post_selector_org_structure_area_wrapper"><div id="post_selector_org_structure_area" class="org_tree"></div></div>
				</div>
				<div id="post_selector_splitter" class="small_splitter"></div>

			</div>
			<div class="buttonarea">
				<div class="ui-button" id="post_selector_complete_button"><span>Выбрать должность</span></div>
				<div class="ui-button" id="post_selector_cancel_button"><span>Закрыть</span></div>
			</div>
		</div>
	</div>



	<div class="bigblock" id="import_complete_wrapper" style="display:none;">
		<div class="titlebar"><h3 id="import_complete_title">Пользователь импортирован</h3></div>
		<div class="contentwrapper">
			<div class="contentarea" id="post_selector_wrapper">

				<div>
					<h1>Пользователь импортирован</h1>
					<h2>Новый сотрудник успешно добавлен</h2>
					<br/><br/>
					<div class="iline w200"><span>Идентификатор:</span><p id="info_employer_id_complete"></p></div>
					<br/>
					<div class="iline w200"><span>Имя пользователя:</span><p id="info_username_complete"></p></div>
					<div class="iline w200"><span>Пароль:</span><p id="info_password_complete"></p></div>
					<div class="iline w200"><span>PIN-код:</span><p id="info_pin_code_complete"></p></div>
					<br/>
					<div class="iline w200"><span>Фамилия:</span><p id="info_last_name_complete"></p></div>
					<div class="iline w200"><span>Имя:</span><p id="info_first_name_complete"></p></div>
					<div class="iline w200"><span>Отчество:</span><p id="info_middle_name_complete"></p></div>
					<div class="iline w200"><span>Дата рождения:</span><p id="info_birth_date_complete"></p></div>
					<div class="iline w200"><span>Контактный телефон:</span><p id="info_phone_complete"></p></div>
					<div class="iline w200"><span>Электронная почта:</span><p id="info_email_complete"></p></div>
					<br/><br/>
					<a style="margin-left:210px;" id="employer_profile_button" href="#" target="_blank">Открыть карточку сотрудника</a>
				</div>

			</div>
			<div class="buttonarea">
				<div class="ui-button" id="import_complete_cancel_button"><span>Закрыть</span></div>
			</div>
		</div>
	</div>



</div>