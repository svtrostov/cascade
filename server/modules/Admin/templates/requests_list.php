<div class="page_requests_list" id="page_requests_list">

	<div class="bigblock" id="bigblock_wrapper">
		<div class="titlebar normal">
			<a class="expander" href="#" id="bigblock_expander"></a>
			<h3 id="bigblock_title">Заявки сотрудников</h3>
		</div>
		<div class="contentwrapper">
			<div class="contentareafull">

				<div class="filter_area">

					<div class="filter_line">
						<div class="left">
							<select id="filter_request_type">
								<option value="all" selected>Все типы заявок</option>
								<option value="2">Запрос доступа</option>
								<option value="3">Блокировка доступа</option>
							</select>
						</div>
						<div class="left">
							<select id="filter_request_status">
								<option value="1" selected>В процессе согласования</option>
								<option value="0">Отмененные заявки</option>
								<option value="2">Приостановленные</option>
								<option value="100">Исполненные заявки</option>
							</select>
						</div>
						<div class="left">
							<select id="filter_company_id"></select>
						</div>
						<div class="left">
							<select id="filter_iresource_id"></select>
						</div>
						<div class="left">
							<select id="filter_route_id"></select>
						</div>
					</div>

					<div class="filter_line">
						<div class="left">
							<select id="filter_period">
								<option value="all">За все время</option>
								<option value="1" selected>За последние сутки</option>
								<option value="7">За последнюю неделю</option>
								<option value="30">За последний месяц</option>
								<option value="90">За последние три месяца</option>
								<option value="365">За последний год</option>
							</select>
						</div>
						<div class="left">
							<input type="text" value="" id="filter_search_term" placeholder="Введите Номер заявки или ФИО..."/>
							<select id="filter_search_term_type">
								<option value="employer" selected>... ФИО заявителя</option>
								<option value="curator">... ФИО куратора</option>
								<option value="gatekeeper">... ФИО гейткипера</option>
							</select>
							<div class="ui-button-light" id="filter_button" style="margin:0px;"><span class="ileft icon_filter">Фильтр</span></div>
							<div class="ui-button-light" id="filter_clear_button" style="margin:0px;"><span class="ileft icon_filter_clear">Сбросить</span></div>
						</div>
					</div>

				</div>

				<div id="requests_area">
					<div id="requests_table_wrapper"><div id="requests_table"></div></div>
					<div id="requests_none" style="display:none;">
						<h1 class="errorpage_title">Заявки не найдены</h1>
					</div>
				</div>

			</div>
		</div>
	</div>


</div>