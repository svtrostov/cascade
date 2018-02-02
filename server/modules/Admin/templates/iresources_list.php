<div class="page_iresources_list" id="page_iresources_list">

	<div class="bigblock" id="bigblock_wrapper">
		<div class="titlebar normal">
			<a class="expander" href="#" id="bigblock_expander"></a>
			<h3 id="bigblock_title">Редактирование списка информационных ресурсов</h3>
		</div>
		<div class="contentwrapper">
			<div class="contentareafull">

				<div class="filter_area">

					<div class="filter_line">
						<div class="left">
							<select id="filter_iresource_status">
								<option value="all">Все ресурсы</option>
								<option value="1">Активные</option>
								<option value="0">Блокированные</option>
							</select>
						</div>
						<div class="left">
							<select id="filter_igroup_id"></select>
						</div>
						<div class="left">
							<select id="filter_company_id"></select>
						</div>
						<div class="left">
							<input type="text" value="" id="filter_search_name" placeholder="Фильтр по названию ресурса..."/>
							<div class="ui-button-light" id="filter_button" style="margin:0px;"><span class="ileft icon_filter">Фильтр</span></div>
						</div>
					</div>

				</div>

				<div id="iresources_area">
					<div id="iresources_table_wrapper"><div id="iresources_table"></div></div>
					<div id="iresources_none" style="display:none;">
						<h1 class="errorpage_title">Ресурсы не найдены</h1>
					</div>
				</div>

			</div>
		</div>
	</div>


	<div id="tmpl_company_info" style="padding:10px;display:none;width:100%;">
		<div class="fline w150"><span>Идентификатор:</span><input style="width:150px;" type="text" class="disabled" readonly="true" value="" id="oinfo_company_id"/></div>
		<div class="fline w150"><span>Полное имя:</span><input style="width:300px;" maxlength="255" type="text" value="" id="oinfo_full_name"/></div>
		<div class="fline w150"><span>Печатное имя:</span><input style="width:300px;" maxlength="255" type="text" value="" id="oinfo_short_name"/></div>
		<div class="fline w150"><span>Заблокирован:</span><input type="checkbox" id="oinfo_is_lock"></div>
		<div class="ui-button-light" style="margin-left:160px;" onclick="org_iresources_change_save();" style="width:165px;margin:5px 0px;"><span>Сохранить</span></div>
	</div>


	<div id="tmpl_company_new" style="padding:10px;display:none;width:100%;">
		<div class="fline w150"><span>Полное имя:</span><input style="width:300px;" maxlength="255" type="text" value="" id="onew_full_name"/></div>
		<div class="fline w150"><span>Печатное имя:</span><input style="width:300px;" maxlength="255" type="text" value="" id="onew_short_name"/></div>
		<div class="fline w150"><span>Заблокирован:</span><input type="checkbox" id="onew_is_lock"></div>
		<div class="ui-button-light" style="margin-left:160px;" onclick="org_iresources_new_save();" style="width:165px;margin:5px 0px;"><span>Добавить</span></div>
	</div>


</div>