<div class="page_org_posts">

	<div class="bigblock" id="acl_wrapper">
		<div class="titlebar"><h3>Редактирование списка должностей</h3></div>
		<div class="contentwrapper">
			<div class="contentareafull">

				<div id="posts_area">
					<div class="posts_table_filter">
						<div class="flabel">Фильтр:</div>
						<div class="fbutton" id="org_posts_filter_button"></div>
						<div class="finput"><input type="text" value="" id="org_posts_filter"/></div>
					</div>
					<div id="posts_table_area_wrapper"><div id="posts_table_area" class="posts_table_area"></div></div>
				</div>
				<div id="posts_splitter"></div>
				<div id="posts_info">
					<div class="ui-button-light" onclick="org_posts_new();"><span>Добавить должность</span></div>
					<div class="ui-button-light" id="button_delete_post" onclick="org_posts_delete();"><span>Удалить должность</span></div>
					<div id="posts_info_area"></div>
				</div>

			</div>
		</div>
	</div>


	<div id="tmpl_post_info" style="padding:10px;display:none;width:100%;">
		<div class="fline w150"><span>Идентификатор:</span><input style="width:150px;" type="text" class="disabled" readonly="true" value="" id="oinfo_post_id"/></div>
		<div class="fline w150"><span>Полное имя:</span><input style="width:300px;" maxlength="255" type="text" value="" id="oinfo_full_name"/></div>
		<div class="fline w150"><span>Печатное имя:</span><input style="width:300px;" maxlength="255" type="text" value="" id="oinfo_short_name"/></div>
		<div class="ui-button-light" style="margin-left:160px;" onclick="org_posts_change_save();" style="width:165px;margin:5px 0px;"><span>Сохранить</span></div>
	</div>


	<div id="tmpl_post_new" style="padding:10px;display:none;width:100%;">
		<div class="fline w150"><span>Полное имя:</span><input style="width:300px;" maxlength="255" type="text" value="" id="onew_full_name"/></div>
		<div class="fline w150"><span>Печатное имя:</span><input style="width:300px;" maxlength="255" type="text" value="" id="onew_short_name"/></div>
		<div class="ui-button-light" style="margin-left:160px;" onclick="org_posts_new_save();" style="width:165px;margin:5px 0px;"><span>Добавить</span></div>
	</div>


</div>