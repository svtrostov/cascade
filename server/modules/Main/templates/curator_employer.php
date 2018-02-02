<div class="page_curator_employer">

	<div class="bigblock"  id="anket_wrapper">
		<div class="titlebar"><h3>Анкета нового сотрудника</h3></div>
		<div class="contentwrapper" style="overflow-y:auto;">
			<div id="anket_area" style="margin:10px;"></div>
		</div>
	</div>


	<div id="tmpl_anket" style="padding:10px;display:none;width:100%;">
		<div class="fline w200"><span>Организация*:</span><select style="width:415px;" id="info_company" onchange="curator_employer_change_company();"></select></div>
		<div class="fline w200"><span>Должность*:</span><p><input style="width:703px;" type="text" placeholder="Фильтр списка должностей, введите часть названия должности..." value="" id="info_post_filter"/><br><select size="10" style="width:715px;" id="info_post"></select></p></div>
		<div class="fline w200"><span>№ приказа о приеме:</span><input style="width:200px;" type="text" maxlength="32" value="" id="info_order_no"/></div>
		<div class="fline w200"><span>Начало работы*:</span><input style="width:100px;" type="text" class="calendar_input" value="" id="info_order_date"/></div>
		<br/><br/>
		<div class="fline w200"><span>Фамилия*:</span><input style="width:200px;" type="text" maxlength="32" value="" id="info_last_name"/></div>
		<div class="fline w200"><span>Имя*:</span><input style="width:200px;" type="text" maxlength="32" value="" id="info_first_name"/></div>
		<div class="fline w200"><span>Отчество*:</span><input style="width:200px;" type="text" maxlength="32" value=""  id="info_middle_name"/></div>
		<div class="fline w200"><span>Дата рождения*:</span><input style="width:100px;" type="text" class="calendar_input" value="" id="info_birth_date"/></div>
		<div class="fline w200"><span>Контактный телефон*:</span><input style="width:200px" placeholder="Пример: 8 (800) 123-45-67" maxlength="64" type="text" value="" id="info_phone"/></div>
		<div class="fline w200"><span>Электронная почта:</span><input style="width:200px;" placeholder="Пример: example@example.ru" maxlength="64" type="text" value="" id="info_email"/></div>
		<div class="fline w200"><span>Работает за компьютером:</span><input type="checkbox" value="1" id="info_work_computer"/></div>
		<div class="fline w200"><span>Нужен пропуск в офис:</span><input type="checkbox" value="1" id="info_need_accesscard"/></div>
		<div class="fline w200"><span>Примечание:</span><p><textarea style="width:400px;height:150px;" id="info_comment"></textarea></p></div>
		<div class="ui-button" style="margin-left:210px;" onclick="curator_employer_preview();" style="width:165px;margin:5px 0px;"><span class="iright icon_next">Дальше</span></div>
	</div>


	<div id="tmpl_preview" style="padding:10px;display:none;width:100%;">
		<h1>Проверьте правильность данных в анкете и если все верно, нажмите кнопку &laquo;Добавить сотрудника&raquo;</h1><br/><br/>
		<div class="iline w200"><span>Организация:</span><p id="info_company_preview"></p></div>
		<div class="iline w200"><span>Должность:</span><p id="info_post_preview"></p></div>
		<div class="iline w200"><span>№ приказа о приеме:</span><p id="info_order_no_preview"></p></div>
		<div class="iline w200"><span>Дата приказа:</span><p id="info_order_date_preview"></p></div>
		<br/><br/>
		<div class="iline w200"><span>Фамилия*:</span><p id="info_last_name_preview"></p></div>
		<div class="iline w200"><span>Имя*:</span><p id="info_first_name_preview"></p></div>
		<div class="iline w200"><span>Отчество*:</span><p id="info_middle_name_preview"></p></div>
		<div class="iline w200"><span>Дата рождения*:</span><p id="info_birth_date_preview"></p></div>
		<div class="iline w200"><span>Контактный телефон*:</span><p id="info_phone_preview"></p></div>
		<div class="iline w200"><span>Электронная почта:</span><p id="info_email_preview"></p></div>
		<div class="iline w200"><span>Работает за компьютером:</span><p id="info_work_computer_preview"></p></div>
		<div class="iline w200"><span>Нужен пропуск в офис:</span><p id="info_need_accesscard_preview"></p></div>
		<div class="iline w200"><span>Примечание:</span><p id="info_comment_preview"></p></div>
		<br/><br/><br/>
		<div class="ui-button" style="margin-left:210px;" onclick="curator_employer_anket();"><span class="ileft icon_prev">Назад</span></div>
		<div class="ui-button" onclick="curator_employer_anket_send();" style="width:165px;margin:5px 0px;"><span>Добавить сотрудника</span></div>
	</div>

	<div id="anket_none" style="display:none;">
		<h1 class="errorpage_title">Функционал недоступен</h1>
		<h2 class="errorpage_subtitle">Вы не можете заводить новых сотрудников</h2>
	</div>


	<div class="bigblock" id="anket_complete" style="display:none;">

		<h1 class="big_title success">Анкета успешно добавлена</h1>
		<h2 class="big_subtitle">Дальнейшие действия по обработке анкеты возлагаются на подразделение IT</h2>
		<br/>
		<div class="w800c">
			<a href="/main/curator/employer" class="dashboard_item item_add_employer">
				<div class="ititle">Добавить сотрудника</div>
				<div class="idesc">Нажмите сюда для заполнения анкеты на нового сотрудника организации</div>
			</a>

			<a href="/main/index" class="dashboard_item item_home_page">
				<div class="ititle">На главную страницу</div>
				<div class="idesc">Нажав сюда, Вы вернетесь в главное меню</div>
			</a>
		</div>

	</div>

</div>