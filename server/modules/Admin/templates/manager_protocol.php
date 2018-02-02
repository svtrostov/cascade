<div class="page_manager_protocol" id="page_manager_protocol">

	<div class="bigblock" id="bigblock_wrapper">
		<div class="titlebar normal">
			<a class="expander" href="#" id="bigblock_expander"></a>
			<h3 id="bigblock_title">Протокол действий пользователей</h3>
		</div>
		<div class="contentwrapper">
			<div class="contentareafull">

				<div class="filter_area">

					<div class="filter_line">
						<div class="left">
							<select id="filter_company_id"  style="max-width:250px;"></select>
						</div>
						<div class="left">
							<select id="filter_acl_name" style="max-width:250px;"></select>
						</div>
						<div class="left">
							<select id="filter_object_type"  style="max-width:250px;"></select>
						</div>
						<div class="left">
							<input type="text" value="" id="filter_object_id" placeholder="Введите ID объекта..."/>
						</div>
					</div>

					<div class="filter_line">
						<div class="left">
							<input type="text" value="" id="filter_date_from" class="calendar_input"/>
							<input type="text" value="" id="filter_date_to" class="calendar_input"/>
						</div>
						<div class="left">
							<input type="text" value="" id="filter_employer_id" placeholder="Введите ID сотрудника..."/>
							<div class="ui-button-light" id="filter_button" style="margin:0px;"><span class="ileft icon_filter">Фильтр</span></div>
							<div class="ui-button-light" id="filter_clear_button" style="margin:0px;"><span class="ileft icon_filter_clear">Сбросить</span></div>
						</div>
					</div>

				</div>

				<div id="requests_area">
					<div id="protocol_table_wrapper"><div id="protocol_table"></div></div>
					<div id="protocol_none" style="display:none;">
						<h1 class="errorpage_title">Записи не найдены</h1>
					</div>
				</div>

			</div>
		</div>
	</div>


</div>