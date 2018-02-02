<div class="page_manager_acl">

	<div class="bigblock" id="acl_wrapper">
		<div class="titlebar"><h3>Менеджер объектов ACL</h3></div>
		<div class="contentwrapper">
			<div class="contentareafull">


				<div id="tabs_area" class="tabs_area absolute">
				<!--tabs_area-->

					<ul class="tabs">
						<li class="tab">Объекты ACL</li>
						<li class="tab">Контейнеры ролей</li>
					</ul>

					<div class="tab_content">
					<!--Объекты ACL-->
						<div id="objects_area">
							<div class="objects_table_filter">
								<div class="flabel">Фильтр:</div>
								<div class="fbutton" id="manager_acl_objects_filter_button"></div>
								<div class="finput"><input type="text" value="" id="manager_acl_objects_filter"/></div>
							</div>
							<div id="objects_table_area_wrapper"><div id="objects_table_area" class="objects_table_area"></div></div>
						</div>
						<div id="objects_splitter"></div>
						<div id="objects_info">
							<div class="ui-button-light" onclick="manager_acl_object_new();" style="width:120px;margin:5px 0px;"><span>Добавить объект</span></div>
							<div class="ui-button-light" id="button_delete_object" onclick="manager_acl_object_delete();" style="width:120px;margin:5px 0px;"><span>Удалить объект</span></div>
							<div id="objects_info_area"></div>
						</div>
					<!--Объекты ACL-->
					</div>



					<div class="tab_content">
					<!--Контейнеры ролей-->
						<div id="containers_roles_area">
							<div id="roles_area">
								<div class="objects_table_filter">
									<div class="flabel">Роль:</div>
									<div class="fselect"><select id="manager_acl_objects_roles_select" onchange="manager_acl_select_role();"></select></div>
								</div>
								<div id="roles_area_table_wrapper"><div id="roles_area_table" class="objects_table_area"></div></div>
							</div>

							<div id="allowed_area">
								<div class="objects_table_filter">
									<div class="flabel">Фильтр:</div>
									<div class="fbutton" id="manager_acl_allowed_filter_button"></div>
									<div class="finput"><input type="text" value="" id="manager_acl_allowed_filter"/></div>
								</div>
								<div id="allowed_area_table_wrapper"><div id="allowed_area_table" class="objects_table_area"></div></div>
							</div>

							<div id="roles_splitter">
								<div id="roles_splitter_handle"></div>
								<div class="toolbutton include" id="button_object_include"><div>Включить</div></div>
								<div class="toolbutton exclude" id="button_object_exclude"><div>Исключить</div></div>
							</div>

						</div>
						
						<div id="containers_roles_none">
							<h1 class="errorpage_title">Нет ролей</h1>
							<h1>Добавьте объекты контейнеров ролей</h1>
						</div>
					<!--Контейнеры ролей-->
					</div>

				<!--tabs_area-->
				</div>

			</div>
		</div>
	</div>


	<div id="tmpl_object_info" style="padding:10px;display:none;width:100%;">
		<div class="fline w150"><span>Идентификатор:</span><input style="width:150px;" type="text" class="disabled" readonly="true" value="" id="oinfo_object_id"/></div>
		<div class="fline w150"><span>Тип объекта:</span><select class="disabled" disabled="true" style="width:213px;" id="oinfo_type"></select></div>
		<div class="fline w150"><span>Заблокирован:</span><input type="checkbox" id="oinfo_is_lock"></div>
		<div class="fline w150"><span>Внутреннее имя:</span><input style="width:200px;" maxlength="255" type="text" value="" id="oinfo_name"/></div>
		<div class="fline w150"><span>0писание:</span><input style="width:200px;" maxlength="255" type="text" value="" id="oinfo_desc"/></div>
		<div class="fline w150"><span>Минимальный AL:</span><input style="width:70px;" maxlength="255" type="text" value="0" id="oinfo_min_access_level"/></div>
		<div class="fline w150"><span>Для всех компаний:</span><input type="checkbox" id="oinfo_for_all_companies"></div>
		<div class="ui-button-light" style="margin-left:160px;" onclick="manager_acl_object_change_save();" style="width:165px;margin:5px 0px;"><span>Сохранить</span></div>
	</div>


	<div id="tmpl_object_new" style="padding:10px;display:none;width:100%;">
		<div class="fline w150"><span>Тип объекта:</span><select style="width:213px;" id="onew_type"></select></div>
		<div class="fline w150"><span>Заблокирован:</span><input type="checkbox" id="onew_is_lock"></div>
		<div class="fline w150"><span>Внутреннее имя:</span><input style="width:200px;" maxlength="255" type="text" value="" id="onew_name"/></div>
		<div class="fline w150"><span>0писание:</span><input style="width:200px;" maxlength="255" type="text" value="" id="onew_desc"/></div>
		<div class="fline w150"><span>Минимальный AL:</span><input style="width:70px;" maxlength="255" type="text" value="0" id="onew_min_access_level"/></div>
		<div class="fline w150"><span>Для всех компаний:</span><input type="checkbox" id="onew_for_all_companies"></div>
		<div class="ui-button-light" style="margin-left:160px;" onclick="manager_acl_object_new_save();" style="width:165px;margin:5px 0px;"><span>Добавить</span></div>
	</div>


</div>