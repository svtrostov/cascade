<div class="page_requests_info" id="page_requests_info">

	<div class="bigblock" id="bigblock_wrapper">
		<div class="titlebar normal">
			<a class="expander" href="#" id="bigblock_expander"></a>
			<h3 id="bigblock_title">Карточка заявки</h3>
		</div>
		<div class="contentwrapper">
			<div class="contentareafull">


				<div id="request_area">

					<div class="navigationarea">
						<div class="iline wauto">
							<span>Выберите раздел заявки:</span>
							<select style="width:550px;" id="area_selector"></select>
						</div>
					</div>

					<div class="centralarea" id="areas_container">
						<div class="steparea" id="area_info"></div>
					</div>

				</div>



				<div id="request_none" style="display:none;">
					<h1 class="errorpage_title">Заявка не найдена</h1>
					<h2 class="errorpage_subtitle" id="error_desc"></h2>
				</div>


			</div>
		</div>
	</div>



	<div id="tmpl_request_info" style="padding:10px;display:none;">
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


	<div id="tmpl_request_iresource" style="display:none;" class="tabs_area">
	<!--tabs_area-->

		<ul class="tabs">
			<li class="tab">Процесс согласования</li>
			<li class="tab">Объекты доступа</li>
			<li class="tab">Комментарии</li>
		</ul>

		<div class="tab_content process">
			<div class="iline w200"><span>Информационный ресурс:</span><p class="request_iresource_name"></p></div>
			<div class="iline w200"><span>Маршрут согласования:</span><p class="request_iresource_route"></p></div>
			<div class="iline w200"><span>Статус согласования:</span><p class="request_iresource_status"></p></div>
			<br/>
		</div>
		<div class="tab_content objects"></div>
		<div class="tab_content comments"></div>

	<!--tabs_area-->
	</div>



</div>