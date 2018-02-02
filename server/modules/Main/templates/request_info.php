<div class="page_request_info">
	<div class="bigblock" id="request_wrapper">
		<div class="titlebar"><h3 id="step_title">Карточка заявки</h3></div>
		<div class="contentwrapper">

			<div class="navigationarea">
				<div class="iline wauto">
					<span>Выберите информационный ресурс:</span>
					<select style="width:500px;" id="ir_selector_iresource_list" onchange="request_info_ir_selector_iresource_list_change();"></select>
				</div>
			</div>

			<div class="centralarea" id="step_container">

				<div class="steparea" id="step_info"></div>

			</div>

		</div>
	</div>


	<div id="request_none" style="display:none;">
		<h1 class="errorpage_title">Заявка не найдена</h1>
		<h2 class="errorpage_subtitle">Заявка не найдена или Вы не можете ее просматривать</h2>
	</div>


	<div id="tmpl_iresource_info" style="padding:10px;display:none;">
		<div class="iline w200"><span>Номер заявки:</span><p id="info_request_id"></p></div>
		<div class="iline w200"><span>Тип заявки:</span><p id="info_request_type"></p></div>
		<div class="iline w200"><span>Заявку оформил:</span><p id="info_curator_name"></p></div>
		<div class="iline w200"><span>Дата оформления:</span><p id="info_create_date"></p></div>
		<br/>
		<div class="iline w200"><span>Заявитель:</span><p id="info_employer_name"></p></div>
		<div class="iline w200"><span>Работает в организации:</span><p id="info_company_name"></p></div>
		<div class="iline w200"><span>Занимает должность:</span><p id="info_post_name"></p></div>
		<div class="iline w200"><span>Контактный телефон:</span><p id="info_phone"></p></div>
		<div class="iline w200"><span>Электронная почта:</span><p id="info_email"></p></div>
	</div>

</div>