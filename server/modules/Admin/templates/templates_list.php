<div class="page_templates_list" id="page_templates_list">

	<div class="bigblock" id="bigblock_wrapper">
		<div class="titlebar normal">
			<a class="expander" href="#" id="bigblock_expander"></a>
			<h3 id="bigblock_title">Типовые шаблоны заявок для должностей</h3>
		</div>
		<div class="contentwrapper">
			<div class="contentareafull">

				<div class="filter_area">

					<div class="filter_line">
						<div class="left">
							<select id="filter_template_status">
								<option value="all">Все шаблоны</option>
								<option value="1">Активные</option>
								<option value="0">Блокированные</option>
							</select>
						</div>
						<div class="left">
							<select id="filter_template_type">
								<option value="all">Для всех</option>
								<option value="1">Для новых сотрудников</option>
								<option value="0">Для существующих</option>
							</select>
						</div>
						<div class="left">
							<select id="filter_company_id"></select>
						</div>
						<div class="left">
							<input type="text" value="" id="filter_search_name" placeholder="Фильтр по названию шаблона..."/>
							<div class="ui-button-light" id="filter_button" style="margin:0px;"><span class="ileft icon_filter">Фильтр</span></div>
						</div>
					</div>

				</div>

				<div id="templates_area">
					<div id="templates_table_wrapper"><div id="templates_table"></div></div>
					<div id="templates_none" style="display:none;">
						<h1 class="errorpage_title">Шаблоны не найдены</h1>
					</div>
				</div>

			</div>
		</div>
	</div>


</div>