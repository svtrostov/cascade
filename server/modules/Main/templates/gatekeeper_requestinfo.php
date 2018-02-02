<div class="page_gatekeeper_request_info">
	<div class="bigblock" id="request_wrapper">
		<div class="titlebar"><h3 id="request_title">Согласование заявки</h3></div>
		<div class="contentwrapper">
			<div class="contentareafull centralarea" id="request_area">
				<ul class="blocklist" id="blocklist"></ul>

				<div class="gk_buttonarea" id="stepmaster_button_area">
					<div class="ui-button" id="button_decline"><span class="iright icon_cross" style="width:140px;">Отклонить заявку</span></div>
					<div class="ui-button" id="button_approve"><span class="iright icon_check" style="width:140px;">Одобрить заявку</span></div>
				</div>

			</div>


		</div>
	</div>

	<div id="request_none" style="display:none;">
		<h1 class="errorpage_title">Заявка не найдена</h1>
		<h2 class="errorpage_subtitle">Заявка не найдена или Вы не можете ее согласовывать</h2>
	</div>


	<div id="tmpl_request_info" style="padding:10px;display:none;">
		<div class="iline w200"><span>Номер заявки:</span><p id="info_request_id"></p></div>
		<div class="iline w200"><span>Тип заявки:</span><p id="info_request_type"></p></div>
		<div class="iline w200"><span>Информационный ресурс:</span><p id="info_iresource_name"></p></div>
		<div class="iline w200"><span>Заявку оформил:</span><p id="info_curator_name"></p></div>
		<div class="iline w200"><span>Дата оформления:</span><p id="info_create_date"></p></div>
		<br/>
		<div class="iline w200"><span>Заявитель:</span><p id="info_employer_name"></p></div>
		<div class="iline w200"><span>Работает в организации:</span><p id="info_company_name"></p></div>
		<div class="iline w200"><span>Занимает должность:</span><p id="info_post_name"></p></div>
		<div class="iline w200"><span>Контактный телефон:</span><p id="info_phone"></p></div>
		<div class="iline w200"><span>Электронная почта:</span><p id="info_email"></p></div>
		<br/>
		<div class="iline w200"><span>От Вас ожидается:</span><p id="info_gatekeeper_role_name"></p></div>
		<div id="gk_export_area">
			<br/>
			<div class="iline w200"><span>Заявка в PDF формате:</span><a href="#" onclick="gk_requestinfo_export('pdf');">Скачать</a></div>
		</div>
	</div>


	<div class="bigblock" id="gk_request_complete" style="display:none;">

		<h1 class="big_title success" id="gk_request_complete_title">Выполнено успешно</h1>
		<h2 class="big_subtitle">Обработка заявки выполнена успешно</h2>
		<br/>
		<div class="w800c">
			<a href="/main/gatekeeper/requestlist" class="dashboard_item item_gatekeeper">
				<div class="ititle">Заявки на рассмотрении</div>
				<div class="idesc">Чтобы согласовать, утвердить или исполнить заявки других сотрудников, зайдите сюда</div>
			</a>
			<a href="/main/index" class="dashboard_item item_home_page">
				<div class="ititle">На главную страницу</div>
				<div class="idesc">Нажав сюда, Вы вернетесь в главное меню</div>
			</a>
		</div>

	</div>


</div>