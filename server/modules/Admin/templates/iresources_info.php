<div class="page_iresources_info" id="page_iresources_info">

	<div class="bigblock" id="bigblock_wrapper">
		<div class="titlebar normal">
			<a class="expander" href="#" id="bigblock_expander"></a>
			<h3 id="bigblock_title">Карточка информационного ресурса</h3>
		</div>
		<div class="contentwrapper">
			<div class="contentareafull">

				<div id="tabs_area" class="tabs_area absolute">
				<!--tabs_area-->

					<ul class="tabs">
						<li class="tab">Общие настройки</li>
						<li class="tab">Объекты доступа</li>
						<li class="tab">Доступен в организациях</li>
					</ul>

					<div class="tab_content">
					<!--Общие настройки-->
						<div class="tab_content_wrapper" id="anket_form">
							<h1>Общие сведения</h1>
							<div class="iline w200"><span>Идентификатор:</span><input style="width:100px;" type="text" class="disabled" readonly="true" value="" id="info_iresource_id"/></div>
							<div class="fline w200"><span>Группа ресурсов*:</span><select id="igroup_id" style="width:312px;"></select></div>
							<div class="fline w200"><span>Полное наименование*:</span><input style="width:300px;" type="text" maxlength="255" value="" id="info_full_name"/></div>
							<div class="fline w200"><span>Печатное наименование*:</span><input style="width:300px;" type="text" maxlength="128" value="" id="info_short_name"/></div>
							<div class="fline w200"><span>Описание:</span><input style="width:300px;" type="text" maxlength="255" value=""  id="info_description"/></div>
							<div class="fline w200"><span>Расположение:</span><input style="width:300px;" type="text" maxlength="255" value=""  id="info_location"/></div>
							<div class="fline w200"><span>Техническая информация:</span><input style="width:300px;" type="text" maxlength="255" value=""  id="info_techinfo"/></div>
							<div class="fline w200"><span>Заблокирован:</span><input type="checkbox" value="1" id="info_is_lock"/></div>
							<div class="splitline"></div>
							<h1>Владелец информационного ресурса</h1>
							<div id="selected_post_none">Пока не выбран...</div>
							<div id="selected_post_area" style="display:none;">
								<div class="fline w100"><span>Организация:</span><p id="selected_company_name"></p></div>
								<div class="fline w100"><span>Должность:</span><p id="selected_post_name"></p></div>
							</div>
							<br>
							<input type="button" id="change_post_button" value="Выбрать..."/>
							<input type="button" id="change_post_cancel_button" value="Отмена"/>
							<div class="splitline"></div>
							<h1>Группа исполнителей</h1>
							<select id="info_worker_group" style="width:422px;"></select>
							<div class="splitline"></div>
							<br>
							<div class="ui-button" id="iresource_info_save_button" style="width:165px;margin:5px 0px;"><span>Сохранить изменения</span></div>
						</div>
					<!--Общие настройки-->
					</div>



					<div class="tab_content">
					<!--Объекты доступа-->
						<div id="sections_area" class="sleft_panel">
							<div class="tool_area">
								<div class="left">
									<div class="ui-button-light" id="section_add_button" style="margin:0px;"><span class="ileft icon_add">Добавить</span></div>
								</div>
								<div id="sections_tool_edit" class="left">
									<div class="ui-button-light" id="section_edit_button" style="margin:0px;"><span class="ileft icon_edit">Изменить</span></div>
									<div class="ui-button-light" id="section_del_button" style="margin:0px;"><span class="ileft icon_delete">Удалить</span></div>
								</div>
							</div>
							<div id="sections_table_area_wrapper"><div id="sections_table_area" class="table_area"></div></div>
						</div>
						<div id="objects_splitter" class="small_splitter"></div>
						<div id="objects_area" class="sright_panel">
							<div class="tool_area">
								<div class="left">
									<div class="ui-button-light" id="object_reload_button" style="margin:0px;"><span class="ileft icon_reload">Перегрузить</span></div>
									<div class="ui-button-light" id="object_import_button" style="margin:0px;"><span class="ileft icon_copy">Импорт</span></div>
								</div>
								<div class="left">
									<div class="ui-button-light" id="object_add_button" style="margin:0px;"><span class="ileft icon_add">Добавить</span></div>
								</div>
								<div id="objects_tool_edit" class="left">
									<div class="ui-button-light" id="object_edit_button" style="margin:0px;"><span class="ileft icon_edit">Изменить</span></div>
									<div class="ui-button-light" id="object_del_button" style="margin:0px;"><span class="ileft icon_delete">Удалить</span></div>
								</div>
								<div class="right">
									<div class="iline wauto"><span>Фильтр:</span><input type="text" style="width:100px;" id="objects_filter"/></div>
								</div>
							</div>

							<div id="objects_table_area_wrapper"><div id="objects_table_area" class="table_area"></div></div>
						</div>
					<!--Объекты доступа-->
					</div>



					<div class="tab_content">
					<!--Доступ к ресурсу-->
						<div id="groups_area">
							<div id="companies_area" class="left_panel">
								<h2>Доступен сотрудникам организаций:</h2>
								<div id="companies_area_table" class="table_area"></div>
							</div>

							<div id="all_companies_area" class="right_panel">
								<h2>Все организации:</h2>
								<div id="all_companies_area_table" class="table_area"></div>
							</div>

							<div id="companies_splitter" class="splitter">
								<div id="companies_splitter_handle" class="splitter_handle"></div>
								<div class="toolbutton include" id="button_company_include"><div>Включить</div></div>
								<div class="toolbutton exclude" id="button_company_exclude"><div>Исключить</div></div>
							</div>

						</div>
					<!--Доступ к ресурсу-->
					</div>


				<!--tabs_area-->
				</div>

				<div id="tabs_none" style="display:none;">
					<h1 class="errorpage_title">Ресурс не найден</h1>
				</div>

			</div>
		</div>
	</div>




	<div class="bigblock" id="post_selector" style="display:none;">
		<div class="titlebar"><h3 id="post_selector_title">Выберите организацию и должность</h3></div>
		<div class="contentwrapper">
			<div class="contentarea" id="post_selector_wrapper">

				<div id="post_selector_companies_area" class="sleft_panel">
					<select id="post_selector_companies_select" size="2"></select>
				</div>
				<div id="post_selector_org_structure" class="sright_panel">
					<div class="table_filter">
						<div class="flabel">Фильтр:</div>
						<div class="fbutton" id="posts_filter_button"></div>
						<div class="finput"><input type="text" value="" id="posts_filter"/></div>
					</div>

					<div id="post_selector_org_structure_area_wrapper"><div id="post_selector_org_structure_area" class="org_tree"></div></div>
				</div>
				<div id="post_selector_splitter" class="small_splitter"></div>

			</div>
			<div class="buttonarea">
				<div class="ui-button" id="post_selector_complete_button"><span>Выбрать должность</span></div>
				<div class="ui-button" id="post_selector_cancel_button"><span>Закрыть</span></div>
			</div>
		</div>
	</div>




	<div class="bigblock" id="object_add" style="display:none;">
		<div class="titlebar"><h3>Добавление объектов доступа</h3></div>
		<div class="contentwrapper">
			<div class="contentarea" id="object_add_wrapper">
				<h1>Добавление объектов доступа</h1>
				<h2>
					Вы можете добавть несколько объектов доступа за один раз, указав в поле &laquo;Полное название&raquo; каждое название объекта с новой строки.<br>
					При этом указанное &laquo;Печатное название&raquo;, &laquo;Описание&raquo; и прочие параметры будут идентичны для всех добавляемых объектов. 
				</h2>
				<div class="fline w150"><span>Полное название*:</span><p><textarea style="width:400px;height:150px;" id="add_full_name"></textarea></p></div>
				<div class="fline w150"><span>Печатное название:</span><input style="width:400px;" type="text" maxlength="255" value="" id="add_short_name"/></div>
				<div class="fline w150"><span>Описание:</span><input style="width:400px;" type="text" maxlength="255" value="" id="add_description"/></div>
				<div class="fline w150"><span>Раздел ресурса:</span><select style="width:412px;" id="add_section"></select></div>
				<div class="fline w150"><span>Важность (0-10)*:</span><input style="width:100px;" type="text" maxlength="2" value="" id="add_weight"/></div>
				<div class="fline w150"><span>Заблокирован:</span><input type="checkbox" value="1" id="add_is_lock"/></div>
				<div class="fline w150"><span>Типы доступа:</span><p id="add_ir_type_area"></p></div>
			</div>
			<div class="buttonarea">
				<div class="ui-button" id="object_add_complete_button"><span>Добавить объект(ы)</span></div>
				<div class="ui-button" id="object_add_cancel_button"><span>Закрыть</span></div>
			</div>
		</div>
	</div>



	<div class="bigblock" id="object_edit" style="display:none;">
		<div class="titlebar"><h3>Свойства объекта доступа</h3></div>
		<div class="contentwrapper">
			<div class="contentarea" id="object_edit_wrapper">
				<h1>Свойства объекта доступа</h1>
				<h2>Изменения свойств вступят в силу после нажития на кнопку &laquo;Сохранить изменения&raquo;</h2>
				<div class="fline w150"><span>Идентификатор:</span><input style="width:100px;" type="text" class="disabled" readonly="true" value="" id="edit_irole_id"/></div>
				<div class="fline w150"><span>Полное название*:</span><input style="width:400px;" type="text" maxlength="255" value="" id="edit_full_name"/></div>
				<div class="fline w150"><span>Печатное название:</span><input style="width:400px;" type="text" maxlength="255" value="" id="edit_short_name"/></div>
				<div class="fline w150"><span>Описание:</span><input style="width:400px;" type="text" maxlength="255" value="" id="edit_description"/></div>
				<div class="fline w150"><span>Раздел ресурса:</span><select style="width:412px;" id="edit_owner_id"></select></div>
				<div class="fline w150"><span>Важность (0-10)*:</span><input style="width:100px;" type="text" maxlength="2" value="" id="edit_weight"/></div>
				<div class="fline w150"><span>Заблокирован:</span><input type="checkbox" value="1" id="edit_is_lock"/></div>
				<div class="fline w150"><span>Типы доступа:</span><p id="edit_ir_type_area"></p></div>
				<div class="splitline"></div>
				<h1>Скриншот</h1>
				<h2>
					Изменение скриншота для объекта доступа не связано с его свойствами и не требует сохранения<br>
					Поддерживаемые форматы изображений: GIF, PNG, JPG
				</h2>
				<form action="/admin/ajax/iresources" method="post" enctype="multipart/form-data" id="screenshot_upload_form">
					<input type="hidden" name="action" value="irole.screenshot.upload"/>
					<input type="hidden" name="ajax" value="1"/>
					<input type="hidden" name="iresource_id" value=""/>
					<input type="hidden" name="irole_id" value=""/>
					<div id="file_exists_area">
						Уже есть загруженное изображение, чтобы просмотреть, <a href="#" id="screenshot_preview_link">нажмите здесь</a>.<br/>
						Для удаления изображения <a href="#" id="screenshot_delete_link">нажмите здесь</a>.
					</div>
					<div class="file_input_div">
						<div class="ui-button-light" style="position: absolute;width:150px;"><span>Выберите файл...</span></div>
						<input type="file" name="screenshot" class="file_input_hidden"/>
						<div class="ui-button-light" style="margin-left:170px;width:150px;" id="screenshot_upload_button"><span>Загрузить на сервер</span></div>
					</div>
				</form>
			</div>
			<div class="buttonarea">
				<div class="ui-button" id="object_edit_complete_button"><span>Сохранить изменения</span></div>
				<div class="ui-button" id="object_edit_cancel_button"><span>Закрыть</span></div>
			</div>
		</div>
	</div>



	<div class="bigblock" id="section_add" style="display:none;">
		<div class="titlebar"><h3>Добавление разделов</h3></div>
		<div class="contentwrapper">
			<div class="contentarea" id="section_add_wrapper">
				<h1>Добавление разделов</h1>
				<h2>
					Вы можете добавть несколько разделов за один раз, указав в поле &laquo;Полное название&raquo; каждое название раздела с новой строки.<br>
				</h2>
				<div class="fline w150"><span>Полное название*:</span><p><textarea style="width:400px;height:200px;" id="section_add_full_name"></textarea></p></div>
				<div class="fline w150"><span>Печатное название:</span><input style="width:400px;" type="text" maxlength="255" value="" id="section_add_short_name"/></div>
				<div class="fline w150"><span>Описание:</span><input style="width:400px;" type="text" maxlength="255" value="" id="section_add_description"/></div>
			</div>
			<div class="buttonarea">
				<div class="ui-button" id="section_add_complete_button"><span>Добавить раздел(ы)</span></div>
				<div class="ui-button" id="section_add_cancel_button"><span>Закрыть</span></div>
			</div>
		</div>
	</div>


	<div class="bigblock" id="section_edit" style="display:none;">
		<div class="titlebar"><h3>Свойства раздела</h3></div>
		<div class="contentwrapper">
			<div class="contentarea" id="section_edit_wrapper">
				<h1>Свойства раздела</h1><br/>
				<div class="fline w150"><span>Полное название*:</span><input style="width:400px;" type="text" maxlength="255" value="" id="section_edit_full_name"/></div>
				<div class="fline w150"><span>Печатное название:</span><input style="width:400px;" type="text" maxlength="255" value="" id="section_edit_short_name"/></div>
				<div class="fline w150"><span>Описание:</span><input style="width:400px;" type="text" maxlength="255" value="" id="section_edit_description"/></div>
			</div>
			<div class="buttonarea">
				<div class="ui-button" id="section_edit_complete_button"><span>Сохранить изменения</span></div>
				<div class="ui-button" id="section_edit_cancel_button"><span>Закрыть</span></div>
			</div>
		</div>
	</div>



	<div class="bigblock" id="import_panel" style="display:none;">
		<div class="titlebar"><h3>Импорт объектов доступа из другого информационного ресурса</h3></div>
		<div class="contentwrapper">
			<div class="contentarea" id="object_add_wrapper">
				<h1>Импорт объектов доступа из другого информационного ресурса</h1>
				<h2>
					Выберите информационный ресурс из которого будете копировать объекты.<br>
					Мастер импорта позволяет скопировать все или только выбранные объекты.<br>
				</h2>
				<br>
				<div class="fline w200"><span>Информационный ресурс:</span><select style="width:612px;" id="import_iresource_id"></select></div>
				<div class="fline w200"><span>Запрашиваемое действие:</span><select style="width:412px;" id="import_type"><option value="copy">Скопировать все объекты</option><option value="custom">Скопировать выбранные объекты</option><option value="clone">Клонировать объекты</option></select></div>
				<div class="import_type_description">
					<div id="import_type_copy">Данное действие выполнит копирование всех объектов доступа из выбранного информационного ресурса, добавив их к существующим объектам в текущем ресурсе</div>
					<div id="import_type_clone">
						Данное действие выполнит копирование всех объектов доступа из выбранного информационного ресурса.<br>
						<font color="red"><b>Перед копированием, все объекты текущего информационного ресурса будут удалены.</b></font><br>
						Операция будет отклонена, если объекты доступа теущего информационного ресурса уже используются в заявках или шаблонах.
					</div>
					<div id="import_type_custom">
						Данное действие выполнит копирование только выбранных объектов из указанного ресурса.<br><br>
						<div id="import_objects_area">
							<b>Выберите объекты для копирования:</b><br>
							<div id="import_objects_list"></div>
							<br><b>При копировании поместить объекты в раздел:</b><br>
							<select style="width:612px;" id="import_section"></select>
						</div>
						<div id="import_objects_none">
							<b>Выбранный информационный ресурс не содержит объектов</b>
						</div>
					</div>
				</div>
				<br>
				<div class="fline w200"><span>Копировать скриншоты:</span><input type="checkbox" value="1" id="import_screenshots"/></div>
			</div>
			<div class="buttonarea">
				<div class="ui-button" id="import_complete_button"><span>Импортировать</span></div>
				<div class="ui-button" id="import_cancel_button"><span>Закрыть</span></div>
			</div>
		</div>
	</div>


</div>