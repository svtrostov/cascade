<div class="page_employers_groups">

	<div class="bigblock" id="acl_wrapper">
		<div class="titlebar"><h3>Редактирование списка групп сотрудников</h3></div>
		<div class="contentwrapper">
			<div class="contentareafull">

				<div id="tabs_area" class="tabs_area absolute">
				<!--tabs_area-->

					<ul class="tabs">
						<li class="tab">Список групп</li>
						<li class="tab">Распределение сотрудников по группам</li>
					</ul>

					<div class="tab_content">
					<!--Группы-->
						<div id="groups_area">
							<div class="groups_table_filter">
								<div class="flabel">Фильтр:</div>
								<div class="fbutton" id="employers_groups_filter_button"></div>
								<div class="finput"><input type="text" value="" id="employers_groups_filter"/></div>
							</div>
							<div id="groups_table_area_wrapper"><div id="groups_table_area" class="groups_table_area"></div></div>
						</div>
						<div id="groups_splitter"></div>
						<div id="groups_info">
							<div class="ui-button-light" onclick="employers_groups_new();"><span>Добавить группу</span></div>
							<div class="ui-button-light" id="button_delete_group" onclick="employers_groups_delete();"><span>Удалить группу</span></div>
							<div id="groups_info_area"></div>
						</div>
					<!--Группы-->
					</div>



					<div class="tab_content">
					<!--Группы сотрудников-->
						<div id="containers_groups_area">
							<div id="selected_group_area">
								<div class="groups_table_filter">
									<div class="flabel">Группа:</div>
									<div class="fselect"><select id="employers_groups_select" onchange="employers_groups_select_group();"></select></div>
								</div>
								<div id="included_employers_area_table_wrapper"><div id="included_employers_area_table" class="groups_table_area"></div></div>
							</div>

							<div id="all_employers_area">
								<div class="groups_table_filter">
									<div class="flabel">Фильтр:</div>
									<div class="fbutton" id="employers_groups_all_filter_button"></div>
									<div class="finput"><input type="text" value="" id="employers_groups_all_filter" placeholder="Поиск по ФИО или логину сотрудника..."/></div>
								</div>
								<div id="all_employers_area_table_wrapper"><div id="all_employers_area_table" class="groups_table_area"></div></div>
							</div>

							<div id="group_employers_splitter">
								<div id="group_employers_splitter_handle"></div>
								<div class="toolbutton include" id="button_employers_include"><div>Включить</div></div>
								<div class="toolbutton exclude" id="button_employers_exclude"><div>Исключить</div></div>
							</div>

						</div>
						
						<div id="containers_groups_none">
							<h1 class="errorpage_title">Нет групп</h1>
							<h1>Добавьте группы сотрудников</h1>
						</div>
					<!--Группы сотрудников-->
					</div>



				<!--tabs_area-->
				</div>

			</div>
		</div>
	</div>


	<div id="tmpl_group_info" style="padding:10px;display:none;width:100%;">
		<div class="fline w150"><span>Идентификатор:</span><input style="width:150px;" type="text" class="disabled" readonly="true" value="" id="oinfo_group_id"/></div>
		<div class="fline w150"><span>Полное имя:</span><input style="width:300px;" maxlength="255" type="text" value="" id="oinfo_full_name"/></div>
		<div class="fline w150"><span>Печатное имя:</span><input style="width:300px;" maxlength="255" type="text" value="" id="oinfo_short_name"/></div>
		<div class="ui-button-light" style="margin-left:160px;" onclick="employers_groups_change_save();" style="width:165px;margin:5px 0px;"><span>Сохранить</span></div>
	</div>


	<div id="tmpl_group_new" style="padding:10px;display:none;width:100%;">
		<div class="fline w150"><span>Полное имя:</span><input style="width:300px;" maxlength="255" type="text" value="" id="onew_full_name"/></div>
		<div class="fline w150"><span>Печатное имя:</span><input style="width:300px;" maxlength="255" type="text" value="" id="onew_short_name"/></div>
		<div class="ui-button-light" style="margin-left:160px;" onclick="employers_groups_new_save();" style="width:165px;margin:5px 0px;"><span>Добавить</span></div>
	</div>


</div>