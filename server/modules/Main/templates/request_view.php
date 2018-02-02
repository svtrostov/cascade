<div class="page_request_view">
	<div class="bigblock" id="request_wrapper">
		<div class="titlebar"><h3 id="request_title">Просмотр заявки</h3></div>
		<div class="contentwrapper">
			<div class="contentareafull centralarea" id="request_area">
				<ul class="blocklist" id="blocklist"></ul>
			</div>


		</div>
	</div>

	<div id="request_none" style="display:none;">
		<h1 class="errorpage_title">Заявка не найдена</h1>
		<h2 class="errorpage_subtitle">Заявка не найдена или Вы не можете ее просматривать</h2>
	</div>


	<div id="tmpl_request_info" style="padding:10px;display:none;">
		<div class="iline w200"><span>Номер заявки:</span><p id="info_request_id"></p></div>
		<div class="iline w200"><span>Тип заявки:</span><p id="info_request_type"></p></div>
		<div class="iline w200"><span>Информационный ресурс:</span><p id="info_iresource_name"></p></div>
		<div class="iline w200"><span>Заявку оформил:</span><p id="info_curator_name"></p></div>
		<div class="iline w200"><span>Дата оформления:</span><p id="info_create_date"></p></div>
		<br/>
		<div class="iline w200"><span>Статус заявки:</span><p id="info_route_status"></p></div>
		<div class="iline w200"><span>Примечание по статусу:</span><p id="info_route_status_desc"></p></div>
		<br/>
		<div class="iline w200"><span>Заявитель:</span><p id="info_employer_name"></p></div>
		<div class="iline w200"><span>Работает в организации:</span><p id="info_company_name"></p></div>
		<div class="iline w200"><span>Занимает должность:</span><p id="info_post_name"></p></div>
		<div class="iline w200"><span>Контактный телефон:</span><p id="info_phone"></p></div>
		<div class="iline w200"><span>Электронная почта:</span><p id="info_email"></p></div>
		<div id="gk_export_area">
			<br/>
			<div class="iline w200"><span>Заявка в PDF формате:</span><a href="#" onclick="request_view_export('pdf');">Скачать</a></div>
		</div>
	</div>

</div>
