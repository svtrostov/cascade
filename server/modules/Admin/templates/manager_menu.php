<div class="page_manager_menu">

	<div class="bigblock" id="designmenu_wrapper">
		<div class="titlebar"><h3>Менеджер меню</h3></div>
		<div class="contentwrapper">
			<div class="contentareafull">


				<div id="designmenu_area"></div>
				<div id="designmenu_splitter"></div>
				<div id="designmenu_info">
					<div class="ui-button-light" onclick="manager_menu_item_new();" style="width:200px;margin:5px 0px;"><span>Добавить элемент меню</span></div>
					<div class="ui-button-light" id="button_delete_item" onclick="manager_menu_item_delete();" style="width:200px;margin:5px 0px;"><span>Удалить выбранный элемент</span></div>
					<div id="designmenu_info_area"></div>
				</div>

			</div>
		</div>
	</div>


	<div id="tmpl_item_info" style="padding:10px;display:none;width:100%;">
		<div class="fline w150"><span>Идентификатор:</span><input style="width:150px;" type="text" class="disabled" readonly="true" value="" id="info_item_id"/></div>
		<div class="fline w150"><span>Тип элемента:</span><select style="width:150px;" id="info_is_folder" onchange="manager_menu_change_item_type();"><option value="0">Пункт меню</option><option value="1">Раздел меню</option></select></div>
		<div class="fline w150"><span>Раздел меню:</span><select style="width:300px;" id="info_parent_id"></select></div>
		<div class="fline w150"><span>Объект доступа:</span><select style="width:300px;" id="info_access_object_id"></select></div>
		<div class="fline w150"><span>Заблокирован:</span><input type="checkbox" id="info_is_lock"></div>
		<div class="fline w150"><span>Заголовок:</span><input style="width:200px;" maxlength="255" type="text" value="" id="info_title"/></div>
		<div class="fline w150"><span>0писание:</span><input style="width:200px;" maxlength="255" type="text" value="" id="info_desc"/></div>
		<div class="fline w150"><span>CSS класс:</span><input style="width:200px;" maxlength="255" type="text" value="" id="info_class"/></div>
		<div class="fline w150"><span>Гиперссылка:</span><input style="width:200px;" maxlength="255" type="text" value="" id="info_href"/></div>
		<div class="fline w150"><span>Как открывать:</span><select style="width:150px;" id="info_target"><option value="_self">В текущем окне</option><option value="_blank">В новом окне</option></select></div>
		<div class="ui-button-light" style="margin-left:160px;" onclick="manager_menu_item_change_save();" style="width:165px;margin:5px 0px;"><span>Сохранить</span></div>
	</div>


	<div id="tmpl_item_new" style="padding:10px;display:none;width:100%;">
		<div class="fline w150"><span>Тип элемента:</span><select style="width:150px;" id="add_is_folder" onchange="manager_menu_change_item_type();"><option value="0">Пункт меню</option><option value="1">Раздел меню</option></select></div>
		<div class="fline w150"><span>Раздел меню:</span><select style="width:300px;" id="add_parent_id"></select></div>
		<div class="fline w150"><span>Объект доступа:</span><select style="width:300px;" id="add_access_object_id"></select></div>
		<div class="fline w150"><span>Заблокирован:</span><input type="checkbox" id="add_is_lock"></div>
		<div class="fline w150"><span>Заголовок:</span><input style="width:200px;" maxlength="255" type="text" value="" id="add_title"/></div>
		<div class="fline w150"><span>0писание:</span><input style="width:200px;" maxlength="255" type="text" value="" id="add_desc"/></div>
		<div class="fline w150"><span>CSS класс:</span><input style="width:200px;" maxlength="255" type="text" value="" id="add_class"/></div>
		<div class="fline w150"><span>Гиперссылка:</span><input style="width:200px;" maxlength="255" type="text" value="" id="add_href"/></div>
		<div class="fline w150"><span>Как открывать:</span><select style="width:150px;" id="add_target"><option value="_self">В текущем окне</option><option value="_blank">В новом окне</option></select></div>
		<div class="ui-button-light" style="margin-left:160px;" onclick="manager_menu_item_new_save();" style="width:165px;margin:5px 0px;"><span>Добавить</span></div>
	</div>

</div>