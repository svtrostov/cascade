<div class="page_employers_anket_info">


	<div class="bigblock" id="employer_anket_info_wrapper">
		<div class="titlebar"><h3 id="employer_anket_infoo_title">Анкета нового сотрудника</h3></div>
		<div class="contentwrapper">
			<div class="contentareafull">

				<div id="tabs_area" class="tabs_area absolute">
				<!--tabs_area-->

					<ul class="tabs">
						<li class="tab">Анкета сотрудника</li>
						<li class="tab">Похожие сотрудники</li>
					</ul>


					<div class="tab_content">
					<!--Анкета сотрудника-->

						<div class="tab_content_area" id="anket_form_area">
							<div class="splitline"></div>
							<h1>Общие сведения</h1>
							<div class="fline w200"><span>ID анкеты:</span><p id="info_anket_id"></p></div>
							<div class="fline w200"><span>Статус:</span><p id="info_anket_type"></p></div>
							<div class="fline w200" id="approved_time_label"><span>Время согласования:</span><p id="info_approved_time"></p></div>
							<div class="ui-button-light" style="margin-left:210px;display:none;width:200px;" id="employer_profile_button2"><span>Перейти в карточку сотрудника</span></div>
							<div class="splitline"></div>
							<h1>Указанная должность</h1>
							<div class="fline w200"><span>Организация:</span><p id="info_company_name"></p></div>
							<div class="fline w200"><span>Должность:</span><p id="info_post_name"></p></div>
							<div id="selected_post_area" style="display:none;">
								<div class="splitline"></div>
								<h1>Вами выбрана должность</h1>
								<div class="fline w200"><span>Организация:</span><p id="selected_company_name"></p></div>
								<div class="fline w200"><span>Должность:</span><p id="selected_post_name"></p></div>
							</div>
							<input type="button" id="change_post_button" style="margin-left:210px;" value="Изменить должность..."/>
							<input type="button" id="change_post_cancel_button" value="Отмена"/>
							<div class="splitline"></div>
							<h1>Формальные сведения</h1>
							<div class="fline w200"><span>№ приказа о приеме:</span><input style="width:200px;" type="text" maxlength="32" value="" id="info_order_no"/></div>
							<div class="fline w200"><span>Начало работы*:</span><input style="width:100px;" type="text" class="calendar_input" value="" id="info_post_from"/></div>
							<div class="splitline"></div>
							<h1>Контактная информация</h1>
							<div class="fline w200"><span>Фамилия*:</span><input style="width:200px;" type="text" maxlength="32" value="" id="info_last_name"/></div>
							<div class="fline w200"><span>Имя*:</span><input style="width:200px;" type="text" maxlength="32" value="" id="info_first_name"/></div>
							<div class="fline w200"><span>Отчество*:</span><input style="width:200px;" type="text" maxlength="32" value=""  id="info_middle_name"/></div>
							<div class="fline w200"><span>Дата рождения*:</span><input style="width:100px;" type="text" class="calendar_input" value="" id="info_birth_date"/></div>
							<div class="fline w200"><span>Контактный телефон*:</span><input style="width:200px" placeholder="Пример: 8 (800) 123-45-67" maxlength="64" type="text" value="" id="info_phone"/></div>
							<div class="fline w200"><span>Электронная почта:</span><input style="width:200px;" placeholder="Пример: example@example.ru" maxlength="64" type="text" value="" id="info_email"/></div>
							<div class="fline w200"><span>Работает за компьютером:</span><input type="checkbox" value="1" id="info_work_computer"/></div>
							<div class="fline w200"><span>Нужен пропуск в офис:</span><input type="checkbox" value="1" id="info_need_accesscard"/></div>
							<div class="fline w200"><span>Примечание:</span><p><textarea style="width:400px;height:150px;" id="info_comment"></textarea></p></div>
							<div id="anket_checked_form">
							<div class="ui-button-light" style="margin-left:210px;" id="anket_save_button" style="margin:5px 0px;"><span>Сохранить изменения</span></div>
							<div class="splitline"></div>
								<div  style="margin-left:210px;margin-top:10px;">
									<h1>Согласование</h1>
									<b>Данная анкета еще не была согласована или отклонена.<br/></b>
									Отклонив анкету, никаких дальнейших действий не произойдет.<br/>
									Согласуя анкету, будет создана локальная учетная запись сотрудника, <br/>
									а также будут автоматически сгенерированы заявки на доступ к корпоративным информационным ресурсам:<br/>
									<input id="template_post" type="checkbox" value="1" checked="true"/> на основании занимаемой сотрудником должности из типового шаблона доступа (при наличии такового)<br/>
									<input id="template_new" type="checkbox" value="1"  checked="true"/> из шаблона доступа для новых сотрудников (учетная запись AD, электронная почта и т.д.)<br/>
									<br/>
									<div class="ui-button-light" style="margin:5px 0px;" id="anket_decline_button"><span>Отклонить анкету</span></div>
									<div class="ui-button-light" style="margin:5px 0px;" id="anket_approve_button"><span>Сохранить и согласовать анкету</span></div>
								</div>
							</div>
						</div>


						<div id="anket_complete_area" class="tab_content_area" style="display:none;">
							<h1>Анкета согласована</h1>
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
							<div class="ui-button" style="margin-left:210px;" id="employer_profile_button"><span>Перейти в карточку сотрудника</span></div>
						</div>

					<!--Анкета сотрудника-->
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

				<div id="tabs_none" style="display:none;">
					<h1 class="errorpage_title">Анкета не найдена</h1>
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



</div>