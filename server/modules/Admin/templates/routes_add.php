<div class="page_routes_add">

	<div class="bigblock"  id="anket_wrapper">
		<div class="titlebar"><h3>Добавление маршрута согласования заявок</h3></div>
		<div class="contentwrapper" style="overflow-y:auto;">
			<div id="anket_area" style="margin:10px;"></div>
		</div>
	</div>


	<div id="tmpl_anket" style="padding:10px;display:none;width:100%;">
		<h1>Общие сведения</h1>
		<div class="fline w200"><span>Полное наименование*:</span><input style="width:300px;" type="text" maxlength="255" value="" id="info_full_name"/></div>
		<div class="fline w200"><span>Описание:</span><input style="width:300px;" type="text" maxlength="255" value=""  id="info_description"/></div>
		<div class="fline w200"><span>Заблокирован:</span><input type="checkbox" value="1" id="info_is_lock"/></div>
		<div class="splitline"></div>
		<h1>Свойства маршрута</h1>
		<div class="fline w200"><span>Тип маршрута (применимость):</span><select id="info_route_type">
			<option value="1" selected>Для заявок сотрудников</option>
			<option value="2">Для шаблонов должностей</option>
			<option value="3">Для шаблонов на новых сотрудников</option>
			<option value="4">Для заявок блокировки доступа</option>
		</select></div>
		<div class="fline w200"><span>Маршрут по-умолчанию:</span><input type="checkbox" value="1" id="info_is_default"/></div>
		<div class="fline w200"><span>Приоритет маршрута*:</span><input style="width:100px;" type="text" maxlength="6" value="0"  id="info_priority"/></div>
		<div class="splitline"></div>
		<br>
		<div class="ui-button" id="route_add_preview_button" style="margin-left:210px;"><span class="iright icon_next">Дальше</span></div>
	</div>


	<div id="tmpl_preview" style="padding:10px;display:none;width:100%;">
		<h1>Проверьте правильность данных в анкете и если все верно, нажмите кнопку &laquo;Добавить маршрут&raquo;</h1><br/><br/>
		<div class="iline w200"><span>Полное наименование*:</span><p id="info_full_name_preview"></p></div>
		<div class="iline w200"><span>Описание:</span><p id="info_description_preview"></p></div>
		<div class="iline w200"><span>Заблокирован:</span><p id="info_is_lock_preview"></p></div>
		<br/>
		<div class="iline w200"><span>Тип маршрута:</span><p id="info_route_type_preview"></p></div>
		<div class="iline w200"><span>Маршрут по-умолчанию:</span><p id="info_is_default_preview"></p></div>
		<div class="iline w200"><span>Приоритет маршрута:</span><p id="info_priority_preview"></p></div>
		<br/><br/><br/>
		<div class="ui-button" style="margin-left:210px;" id="route_add_preview_back_button"><span class="ileft icon_prev">Назад</span></div>
		<div class="ui-button" id="route_add_send_button" style="width:165px;margin:5px 0px;"><span>Добавить маршрут</span></div>
	</div>


	<div id="tmpl_complete" style="padding:10px;display:none;width:100%;">
		<h1>Маршрут успешно добавлен</h1><br/><br/>
		<div class="iline w200"><span>Идентификатор:</span><p id="info_route_id_complete"></p></div>
		<br/>
		<div class="iline w200"><span>Полное наименование:</span><p id="info_full_name_complete"></p></div>
		<div class="iline w200"><span>Описание:</span><p id="info_description_complete"></p></div>
		<br/><br/>
		<div class="ui-button" id="route_add_profile_button" style="margin-left:210px;"><span>Перейти к маршруту</span></div>
	</div>


</div>