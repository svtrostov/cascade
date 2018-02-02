<div class="page_org_companies">

	<div class="bigblock" id="acl_wrapper">
		<div class="titlebar"><h3>Редактирование списка организаций</h3></div>
		<div class="contentwrapper">
			<div class="contentareafull">

				<div id="companies_area">
					<div class="companies_table_filter">
						<div class="flabel">Фильтр:</div>
						<div class="fbutton" id="org_companies_filter_button"></div>
						<div class="finput"><input type="text" value="" id="org_companies_filter"/></div>
					</div>
					<div id="companies_table_area_wrapper"><div id="companies_table_area" class="companies_table_area"></div></div>
				</div>
				<div id="companies_splitter"></div>
				<div id="companies_info">
					<div class="ui-button-light" onclick="org_companies_new();"><span>Добавить организацию</span></div>
					<div class="ui-button-light" id="button_delete_company" onclick="org_companies_delete();"><span>Удалить организацию</span></div>
					<div id="companies_info_area"></div>
				</div>


			</div>
		</div>
	</div>


	<div id="tmpl_company_info" style="padding:10px;display:none;width:100%;">
		<div class="fline w150"><span>Идентификатор:</span><input style="width:150px;" type="text" class="disabled" readonly="true" value="" id="oinfo_company_id"/></div>
		<div class="fline w150"><span>Полное имя:</span><input style="width:300px;" maxlength="255" type="text" value="" id="oinfo_full_name"/></div>
		<div class="fline w150"><span>Печатное имя:</span><input style="width:300px;" maxlength="255" type="text" value="" id="oinfo_short_name"/></div>
		<div class="fline w150"><span>Заблокирован:</span><input type="checkbox" id="oinfo_is_lock"></div>
		<div class="ui-button-light" style="margin-left:160px;" onclick="org_companies_change_save();" style="width:165px;margin:5px 0px;"><span>Сохранить</span></div>
	</div>


	<div id="tmpl_company_new" style="padding:10px;display:none;width:100%;">
		<div class="fline w150"><span>Полное имя:</span><input style="width:300px;" maxlength="255" type="text" value="" id="onew_full_name"/></div>
		<div class="fline w150"><span>Печатное имя:</span><input style="width:300px;" maxlength="255" type="text" value="" id="onew_short_name"/></div>
		<div class="fline w150"><span>Заблокирован:</span><input type="checkbox" id="onew_is_lock"></div>
		<div class="ui-button-light" style="margin-left:160px;" onclick="org_companies_new_save();" style="width:165px;margin:5px 0px;"><span>Добавить</span></div>
	</div>


</div>