<div class="page_templates_info" id="page_templates_info">

	<div class="bigblock" id="bigblock_wrapper">
		<div class="titlebar normal">
			<a class="expander" href="#" id="bigblock_expander"></a>
			<h3 id="bigblock_title">Карточка шаблона заявки</h3>
		</div>
		<div class="contentwrapper">
			<div class="contentareafull">

				<div id="tabs_area" class="tabs_area absolute">
				<!--tabs_area-->

					<ul class="tabs">
						<li class="tab">Общие настройки</li>
						<li class="tab">Шаблон заявки</li>
					</ul>

					<div class="tab_content">
					<!--Общие настройки-->
						<div class="tab_content_wrapper" id="anket_form">
							<h1>Общие сведения</h1>
							<div class="iline w200"><span>Идентификатор:</span><input style="width:100px;" type="text" class="disabled" readonly="true" value="" id="info_template_id"/></div>
							<div class="fline w200"><span>Полное наименование*:</span><input style="width:300px;" type="text" maxlength="255" value="" id="info_full_name"/></div>
							<div class="fline w200"><span>Описание:</span><input style="width:300px;" type="text" maxlength="255" value=""  id="info_description"/></div>
							<div class="fline w200"><span>Заблокирован:</span><input type="checkbox" value="1" id="info_is_lock"/></div>
							<div class="fline w200"><span>Для новых сотрудников:</span><input type="checkbox" value="1" id="info_is_for_new_employer"/></div>
							<div class="splitline"></div>
							<h1>Применим для должности</h1>
							<div id="selected_post_none">Любая должность в любой организации...</div>
							<div id="selected_post_area" style="display:none;">
								<div class="fline w100"><span>Организация:</span><p id="selected_company_name"></p></div>
								<div class="fline w100"><span>Должность:</span><p id="selected_post_name"></p></div>
							</div>
							<br>
							<input type="button" id="change_post_button" value="Выбрать..."/>
							<input type="button" id="change_post_cancel_button" value="Отмена"/>
							<div class="splitline"></div>
							<br>
							<div class="ui-button" id="template_info_save_button" style="width:165px;margin:5px 0px;"><span>Сохранить изменения</span></div>
						</div>
					<!--Общие настройки-->
					</div>



					<div class="tab_content">
					<!--Объекты доступа-->
						<div class="toolarea">
							<div class="toolbutton add" id="button_ir_add"><div>Добавить</div></div>
							<div class="toolbutton trash" id="button_ir_trash"><div>Удалить все</div></div>
							<div class="toolbutton save" id="button_ir_save"><div>Сохранить</div></div>
							<div class="toolbutton copy" id="button_ir_copy"><div>Импорт</div></div>
						</div>
						<div class="centralarea">
							<div id="ir_none">
								<h1>Выберите информационные ресурсы и роли доступа</h1>
								<h2>Здесь осуществляется настройка шаблона, укажите к каким ресурсам и какой именно доступ будет в шаблоне.</h2>
								<h2>Для начала, нажмите на кнопку &laquo;Добавить&raquo;, она находится слева от этой надписи.</h2>
								<h2>
								В открывшейся форме выберите информационный ресурс и 
								напротив интересуемых позиций, укажите какой именно доступ будет предоставлен в данном шаблоне.
								</h2>
							</div>
							<div id="ir_area" style="display:none;">
								<h1>Вы формируете шаблон заявки для должности и указываете доступ:</h1><br>
								<ul id="ir_list" class="blocklist"></ul>
							</div>
						</div>
					<!--Объекты доступа-->
					</div>



				<!--tabs_area-->
				</div>

				<div id="tabs_none" style="display:none;">
					<h1 class="errorpage_title">Шаблон не найден</h1>
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




	<div class="bigblock" id="ir_selector" style="display:none;">
		<div class="titlebar"><h3>Выберите информационный ресурс и укажите требуемый доступ</h3></div>
		<div class="contentwrapper">
			<div class="contentarea" id="ir_selector_wrapper">
				<div class="iline wauto">
					<span>Выберите информационный ресурс:</span>
					<select style="width:500px;" id="ir_selector_iresource_list"></select>
				</div>
				<div id="ir_selector_none" style="display:none;"><br><h2>В выбранном информационном ресурсе нет функционала, к которому можно запросить доступ</h2></div>
				<div id="ir_selector_table">
					<div id="ir_selector_toolbar">
						<a href="#" id="button_ir_selector_sections_expand">Развернуть все</a>
						<a href="#" id="button_ir_selector_sections_collapse">Свернуть все</a>
					</div>
				</div>
			</div>
			<div class="buttonarea">
				<div class="ui-button" id="button_ir_selector_complete"><span>Ок</span></div>
				<div class="ui-button" id="button_ir_selector_cancel"><span>Закрыть</span></div>
			</div>
		</div>
	</div>


	<div class="bigblock" id="import_panel" style="display:none;">
		<div class="titlebar"><h3>Импорт объектов доступа из другого шаблона</h3></div>
		<div class="contentwrapper">
			<div class="contentarea" id="object_add_wrapper">
				<h1>Импорт объектов доступа из другого шаблона</h1>
				<h2>
					Выберите шаблон из которого будете копировать объекты.<br>
					Мастер импорта позволяет скопировать все или только выбранные объекты.<br>
				</h2>
				<br>
				<div class="fline w200"><span>Шаблон-источник:</span><select style="width:412px;" id="import_template_id"></select></div>
				<div class="fline w200"><span>Запрашиваемое действие:</span><select style="width:412px;" id="import_type"><option value="copy">Скопировать все объекты</option><option value="clone">Клонировать шаблон</option></select></div>
				<div class="import_type_description">
					<div id="import_type_copy">
						Данное действие выполнит копирование всех объектов доступа из выбранного шаблона, добавив их к существующим объектам в текущем шаблоне.<br>
						<br>
						<div class="fline wauto"><span>Копировать с заменой:</span><input type="checkbox" value="1" id="import_copy_replace"/></div>
						Если установлена галочка в поле &laquo;Копировать с заменой&raquo;, то при совпадении объектов доступа, объекты текущего шаблона будут заменены объектами из шаблона-источника, в противном случае они останутся без изменений.
						</div>
					<div id="import_type_clone">
						Данное действие выполнит копирование всех объектов доступа из выбранного шаблона.<br>
						<font color="red"><b>Перед копированием, все объекты текущего шаблона будут удалены.</b></font>
					</div>
				</div>
			</div>
			<div class="buttonarea">
				<div class="ui-button" id="import_complete_button"><span>Импортировать</span></div>
				<div class="ui-button" id="import_cancel_button"><span>Закрыть</span></div>
			</div>
		</div>
	</div>

</div>