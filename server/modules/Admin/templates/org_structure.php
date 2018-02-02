<div class="page_org_structure">


	<div class="bigblock" id="orgstructure_wrapper">
		<div class="titlebar normal">
			<a class="expander" href="#" onclick="org_structure_fullscreen();"></a>
			<h3 id="orgstructure_wrapper_title">Настройки организационной структуры</h3>
		</div>
		<div class="contentwrapper">
			<div class="contentareafull">
			<!--Content-->
				<div id="org_chart_area"><div id="org_chart_tree" class="org_chart"></div></div>

				<div id="zoom_slider_wrapper">
					<div id="zoom_slider_title"></div>
					<div id="zoom_slider_plus" class="ui-icon-white ui-icon-zoomin"></div>
					<div id="zoom_slider">
						<div id="zoom_slider_knob"></div>
					</div>
					<div id="zoom_slider_minus" class="ui-icon-white ui-icon-zoomout"></div>
				</div>

				<div id="org_chart_tool_area">
					<div class="left">
						<div class="ui-button-light" onclick="org_structure_reload();" style="margin:0px;"><span class="ileft icon_reload">&nbsp;Перегрузить</span></div>
						<div class="ui-button-light" onclick="org_structure_save();" style="margin:0px;"><span class="ileft icon_save">&nbsp;Сохранить</span></div>
					</div>
					<div class="left">
						<div class="ui-button-light" onclick="org_structure_add_block();" style="margin:0px;"><span class="ileft icon_add">&nbsp;Добавить</span></div>
					</div>
					<div id="org_chart_tool_block" class="left" style="display:none;">
						<div class="ui-button-light" onclick="org_structure_edit_block();" style="margin:0px;"><span class="ileft icon_edit">&nbsp;Изменить</span></div>
						<div class="ui-button-light" onclick="org_structure_delete_block();" style="margin:0px;"><span class="ileft icon_delete">&nbsp;Удалить</span></div>
						<div class="ui-button-light" onclick="org_structure_info_block();" style="margin:0px;"><span class="ileft icon_info">&nbsp;Инфо</span></div>
					</div>
					<div class="right">
						<div class="iline wauto"><span>Организация:</span><select style="width:250px;" id="org_company_select" onchange="org_structure_company_change();"></select></div>
					</div>
				</div>

			<!--Content-->
			</div>
		</div>
	</div>




	<div class="bigblock" id="orgstructure_post_window" style="display:none;">
		<div class="titlebar">
			<h3>Выберите должность для размещения в организационной структуре</h3>
		</div>
		<div class="contentwrapper">
			<div class="contentarea">
				<div id="post_action_title">Действие</div>
				<div class="posts_filter">
					<div class="flabel">Фильтр:</div>
					<div class="fbutton" id="org_post_filter_button"></div>
					<div class="finput"><input type="text" value="" id="org_post_filter" placeholder="Введите часть названия должности для фильтрации..."/></div>
				</div>
				<div id="org_post_select_area"><select id="org_post_select" size="2"></select></div>
			</div>
			<div class="buttonarea">
				<div class="ui-button" onclick="org_structure_post_complete();"><span id="org_structure_post_complete_button">Готово</span></div>
				<div class="ui-button" onclick="org_structure_post_close();"><span>Отмена</span></div>
			</div>
		</div>
	</div>

	<div class="bigblock" id="orgstructure_info_window" style="display:none;">
		<div class="titlebar">
			<h3>Информация о выбранной должности</h3>
		</div>
		<div class="contentwrapper">
			<div class="contentarea" style="overflow-y:auto;"><div class="w700c">

				<br/>
				<div class="iline w150"><span>Организация:</span><p id="info_company_name"></p></div>
				<div class="iline w150"><span>Должность:</span><p id="info_post_name"></p></div>

				<br/><br/>
				<div id="employer_search_results" style="display:none;">
					<h2>Следующие сотрудники занимают выбранную должность:</h2><br/>
					<div id="employer_list_area"></div>
				</div>
				<div id="employer_search_none" style="display:none;">
					<h2>Нет сотрудников, занимающих выбранную должность</h2><br/>
				</div>

			</div></div>
			<div class="buttonarea">
				<div class="ui-button" onclick="org_structure_info_close();"><span>Закрыть</span></div>
			</div>
		</div>
	</div>


</div>