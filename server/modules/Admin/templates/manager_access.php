<div class="page_manager_access">



	<div class="bigblock" id="employers_wrapper">
		<div class="titlebar"><h3>Раздача прав доступа</h3></div>
		<div class="contentwrapper">
			<div class="contentareafull">

				<div id="employers_list_area">
				<!--Сотрудники-->
					<div id="employers_area">
						<div class="table_filter">
							<div class="flabel">Фильтр:</div>
							<div class="fbutton" id="manager_access_employers_filter_button"></div>
							<div class="finput"><input type="text" value="" id="manager_access_employers_filter" placeholder="Поиск по ФИО или логину сотрудника..."/></div>
						</div>
						<div id="employers_table_area" class="table_area"></div>
					</div>
					<div id="employers_info">
						<div class="ui-button-light" onclick="manager_access_add();" style="width:250px;margin:5px 0px;"><span>Добавить сотруднику права доступа</span></div>
						<div class="ui-button-light" onclick="manager_access_get_privs();" style="width:250px;margin:5px 0px;"><span>Показать итоговый доступ сотрудника</span></div>
						<div id="employers_info_area"></div>
					</div>
					<div id="employers_splitter"></div>
				<!--Сотрудники-->
				</div>

				<div id="employers_list_none">
					<h1 class="errorpage_title">Нет сотрудников</h1>
					<h1>Странно, но сотрудники не обнаружены, как Вы зашли?</h1>
				</div>

			</div>
		</div>
	</div>



	<div class="bigblock" id="new_access_wrapper" style="display:none;">
		<div class="titlebar"><h3 id="new_access_title">Добавление прав доступа сотруднику</h3></div>
		<div class="contentwrapper">
			<div class="contentarea" id="new_access_area" style="overflow:hidden;">
				<div id="access_add_selected_area">
					<h2 style="margin-left:10px;">Выбранные объекты доступа</h2>
					<div id="access_add_selected_table" class="table_area"></div>
				</div>
				<div id="access_add_objects_area">
					<h2 style="margin-left:10px;">Доступные объекты</h2>
					<div id="access_add_objects_table" class="table_area"></div>
				</div>
				<div id="access_add_splitter">
					<div id="access_add_splitter_handle"></div>
					<div class="toolbutton include" id="button_object_include"><div>Добавить</div></div>
					<div class="toolbutton exclude" id="button_object_exclude"><div>Удалить</div></div>
				</div>
			</div>

			<div class="buttonarea">
				<div class="ui-button" onclick="manager_access_add_complete();"><span>Добавить выбранные объекты</span></div>
				<div class="ui-button" onclick="manager_access_add_close();"><span>Закрыть</span></div>
			</div>

		</div>
	</div>




	<div class="bigblock" id="acl_employer_access_wrapper" style="display:none;">
		<div class="titlebar"><h3 id="acl_employer_access_title">Итоговый доступ для сотрудника</h3></div>
		<div class="contentwrapper">
			<div class="contentarea">


				<div id="tabs_area" class="tabs_area absolute">
				<!--tabs_area-->

					<ul class="tabs">
						<li class="tab">Доступ к объектам</li>
						<li class="tab">Трассировка</li>
					</ul>

					<div class="tab_content">
					<!--Объекты-->
						<div id="acl_objects_area"></div>
					<!--Объекты-->
					</div>


					<div class="tab_content">
					<!--Трассировка-->
						<div id="acl_explain_area"></div>
					<!--Трассировка-->
					</div>

				<!--tabs_area-->
				</div>

			</div>

			<div class="buttonarea">
				<div class="ui-button" onclick="manager_access_privs_close();"><span>Закрыть</span></div>
			</div>

		</div>
	</div>




	<div id="tmpl_access_actions" style="padding:10px;display:none;width:100%;">
		<div class="ui-button-light" onclick="manager_access_eaction('restrict',true);" style="width:150px;margin:5px 0px;"><span>Установить запрет</span></div>
		<div class="ui-button-light" onclick="manager_access_eaction('restrict',false);" style="width:150px;margin:5px 0px;"><span>Снять запрет</span></div>
		<div class="ui-button-light" onclick="manager_access_eaction('delete',true);" style="width:150px;margin:5px 0px;"><span>Удалить объекты</span></div>
		<div style="margin-top:10px;">
			<select style="width:308px;" id="eaction_company_select"></select>
			<div class="ui-button-light" onclick="manager_access_eaction('company',0);" style="width:150px;margin:5px 0px;"><span>Изменить организацию</span></div>
		</div>
	</div>



</div>