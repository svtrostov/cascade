<div class="page_employers_add">

	<div class="bigblock"  id="anket_wrapper">
		<div class="titlebar"><h3>Добавление нового сотрудника</h3></div>
		<div class="contentwrapper" style="overflow-y:auto;">
			<div id="anket_area" style="margin:10px;"></div>
		</div>
	</div>


	<div id="tmpl_anket" style="padding:10px;display:none;width:100%;">
		<h1>Введите основные сведения о сотруднике</h1><br/><br/>
		<div class="fline w200"><span>Фамилия*:</span><input style="width:200px;" type="text" maxlength="32" value="" id="info_last_name"/></div>
		<div class="fline w200"><span>Имя*:</span><input style="width:200px;" type="text" maxlength="32" value="" id="info_first_name"/></div>
		<div class="fline w200"><span>Отчество*:</span><input style="width:200px;" type="text" maxlength="32" value=""  id="info_middle_name"/></div>
		<div class="fline w200"><span>Дата рождения*:</span><input style="width:100px;" type="text" class="calendar_input" value="" id="info_birth_date"/></div>
		<div class="fline w200"><span>Контактный телефон*:</span><input style="width:200px" placeholder="Пример: 8 (800) 123-45-67" maxlength="64" type="text" value="" id="info_phone"/></div>
		<div class="fline w200"><span>Электронная почта:</span><input style="width:200px;" placeholder="Пример: example@example.ru" maxlength="64" type="text" value="" id="info_email"/></div>
		<div class="ui-button" style="margin-left:210px;" onclick="employers_add_preview();" style="width:165px;margin:5px 0px;"><span class="iright icon_next">Дальше</span></div>
	</div>


	<div id="tmpl_preview" style="padding:10px;display:none;width:100%;">
		<h1>Проверьте правильность данных в анкете и если все верно, нажмите кнопку &laquo;Добавить сотрудника&raquo;</h1><br/><br/>
		<div class="iline w200"><span>Фамилия*:</span><p id="info_last_name_preview"></p></div>
		<div class="iline w200"><span>Имя*:</span><p id="info_first_name_preview"></p></div>
		<div class="iline w200"><span>Отчество*:</span><p id="info_middle_name_preview"></p></div>
		<div class="iline w200"><span>Дата рождения*:</span><p id="info_birth_date_preview"></p></div>
		<div class="iline w200"><span>Контактный телефон*:</span><p id="info_phone_preview"></p></div>
		<div class="iline w200"><span>Электронная почта:</span><p id="info_email_preview"></p></div>
		<br/><br/><br/>
		<div class="ui-button" style="margin-left:210px;" onclick="employers_add_anket();"><span class="ileft icon_prev">Назад</span></div>
		<div class="ui-button" onclick="employers_add_anket_send();" style="width:165px;margin:5px 0px;"><span>Добавить сотрудника</span></div>
	</div>


	<div id="tmpl_complete" style="padding:10px;display:none;width:100%;">
		<h1>Новый сотрудник успешно добавлен</h1><br/><br/>
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
		<div class="ui-button" onclick="employers_add_to_card();" style="margin-left:210px;"><span>Перейти в карточку сотрудника</span></div>
	</div>


</div>