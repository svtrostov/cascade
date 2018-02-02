<div class="page_iresources_add">

	<div class="bigblock"  id="anket_wrapper">
		<div class="titlebar"><h3>Добавление информационного ресурса</h3></div>
		<div class="contentwrapper" style="overflow-y:auto;">
			<div id="anket_area" style="margin:10px;"></div>
		</div>
	</div>


	<div id="tmpl_anket" style="padding:10px;display:none;width:100%;">
		<h1>Общие сведения</h1>
		<div class="fline w200"><span>Группа ресурсов*:</span><select id="info_igroup_id" style="width:312px;"></select></div>
		<div class="fline w200"><span>Полное наименование*:</span><input style="width:300px;" type="text" maxlength="255" value="" id="info_full_name"/></div>
		<div class="fline w200"><span>Печатное наименование*:</span><input style="width:300px;" type="text" maxlength="128" value="" id="info_short_name"/></div>
		<div class="fline w200"><span>Описание:</span><input style="width:300px;" type="text" maxlength="255" value=""  id="info_description"/></div>
		<div class="fline w200"><span>Расположение:</span><input style="width:200px;" type="text" maxlength="255" value=""  id="info_location"/></div>
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
		<div class="ui-button" id="iresource_add_preview_button" style="margin-left:210px;"><span class="iright icon_next">Дальше</span></div>
	</div>


	<div id="tmpl_preview" style="padding:10px;display:none;width:100%;">
		<h1>Проверьте правильность данных и если все верно, нажмите кнопку &laquo;Добавить ресурс&raquo;</h1><br/><br/>
		<div class="iline w200"><span>Группа ресурсов:</span><p id="info_igroup_id_preview"></p></div>
		<div class="iline w200"><span>Полное наименование*:</span><p id="info_full_name_preview"></p></div>
		<div class="iline w200"><span>Печатное наименование*:</span><p id="info_short_name_preview"></p></div>
		<div class="iline w200"><span>Описание:</span><p id="info_description_preview"></p></div>
		<div class="iline w200"><span>Расположение:</span><p id="info_location_preview"></p></div>
		<div class="iline w200"><span>Техническая информация:</span><p id="info_techinfo_preview"></p></div>
		<div class="iline w200"><span>Заблокирован:</span><p id="info_is_lock_preview"></p></div>
		<br/>
		<div class="iline w200"><span>Организация:</span><p id="info_company_id_preview"></p></div>
		<div class="iline w200"><span>Должность владельца:</span><p id="info_post_uid_preview"></p></div>
		<br/>
		<div class="iline w200"><span>Группа исполнителей:</span><p id="info_worker_group_preview"></p></div>
		<br/><br/><br/>
		<div class="ui-button" style="margin-left:210px;" id="iresource_add_preview_back_button"><span class="ileft icon_prev">Назад</span></div>
		<div class="ui-button" id="iresource_add_send_button" style="width:165px;margin:5px 0px;"><span>Добавить ресурс</span></div>
	</div>


	<div id="tmpl_complete" style="padding:10px;display:none;width:100%;">
		<h1>Информационный ресурс успешно добавлен</h1><br/><br/>
		<div class="iline w200"><span>Идентификатор:</span><p id="info_iresource_id_complete"></p></div>
		<br/>
		<div class="iline w200"><span>Группа ресурсов:</span><p id="info_igroup_id_complete"></p></div>
		<div class="iline w200"><span>Полное наименование:</span><p id="info_full_name_complete"></p></div>
		<div class="iline w200"><span>Печатное наименование:</span><p id="info_short_name_complete"></p></div>
		<div class="iline w200"><span>Описание:</span><p id="info_description_complete"></p></div>
		<div class="iline w200"><span>Расположение:</span><p id="info_location_complete"></p></div>
		<div class="iline w200"><span>Техническая информация:</span><p id="info_techinfo_complete"></p></div>
		<br/><br/>
		<div class="ui-button" id="iresource_add_profile_button" style="margin-left:210px;"><span>Перейти к ресурсу</span></div>
		<div class="ui-button" id="iresource_add_new_button" style="margin-left:10px;"><span>Добавить еще один ресурс</span></div>
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

</div>