<div class="page_routes_list" id="page_routes_list">

	<div class="bigblock" id="bigblock_wrapper">
		<div class="titlebar normal">
			<a class="expander" href="#" id="bigblock_expander"></a>
			<h3 id="bigblock_title">Маршруты согласования</h3>
		</div>
		<div class="contentwrapper">
			<div class="contentareafull">

				<div class="filter_area">

					<div class="filter_line">
						<div class="left">
							<select id="filter_route_status">
								<option value="all">Все маршруты</option>
								<option value="1">Активные</option>
								<option value="0">Блокированные</option>
							</select>
						</div>
						<div class="left">
							<select id="filter_route_type">
								<option value="all">Все маршруты</option>
								<option value="1">Для заявок сотрудников</option>
								<option value="2">Для шаблонов должностей</option>
								<option value="3">Для шаблонов на новых сотрудников</option>
								<option value="4">Для заявок блокировки доступа</option>
							</select>
						</div>
						<div class="left">
							<input type="text" value="" id="filter_search_name" placeholder="Фильтр по названию маршрута..."/>
							<div class="ui-button-light" id="filter_button" style="margin:0px;"><span class="ileft icon_filter">Фильтр</span></div>
						</div>
					</div>

				</div>

				<div id="routes_area">
					<div id="routes_table_wrapper"><div id="routes_table"></div></div>
					<div id="routes_none" style="display:none;">
						<h1 class="errorpage_title">Маршруты не найдены</h1>
					</div>
				</div>

			</div>
		</div>
	</div>


</div>