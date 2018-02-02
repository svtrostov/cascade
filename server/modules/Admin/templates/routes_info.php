<div class="page_routes_info" id="page_routes_info">

	<div class="bigblock" id="bigblock_wrapper">
		<div class="titlebar normal">
			<a class="expander" href="#" id="bigblock_expander"></a>
			<h3 id="bigblock_title">Карточка маршрута согласования</h3>
		</div>
		<div class="contentwrapper">
			<div class="contentareafull">

				<div id="tabs_area" class="tabs_area absolute">
				<!--tabs_area-->

					<ul class="tabs">
						<li class="tab">Общие настройки</li>
						<li class="tab">Условия выбора маршрута</li>
						<li class="tab">Дизайнер маршрута</li>
					</ul>

					<div class="tab_content">
					<!--Общие настройки-->
						<div class="tab_content_wrapper" id="anket_form">
							<h1>Общие сведения</h1>
							<div class="iline w200"><span>Идентификатор:</span><input style="width:100px;" type="text" class="disabled" readonly="true" value="" id="info_route_id"/></div>
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
							<div class="ui-button" id="route_info_save_button" style="width:165px;margin:5px 0px;"><span>Сохранить изменения</span></div>
						</div>
					<!--Общие настройки-->
					</div>



					<div class="tab_content">
					<!--Параметры маршрута-->
						<div class="workflow_area">
							<div class="tool_area">
								<div class="left">
									<div class="ui-button-light" id="param_add_button" style="margin:0px;"><span class="ileft icon_add">Добавить условие</span></div>
								</div>
								<div id="param_tool_edit" class="left">
									<div class="ui-button-light" id="param_del_button" style="margin:0px;"><span class="ileft icon_delete">Удалить условие</span></div>
								</div>
							</div>
							<div id="table_params_area">
								<div id="table_params"></div>
								<br>
								<div class="splitline"></div>
								<h2>Примечание:</h2>
								<div class="param_desc">
									При выборе маршрута система вычисляет &laquo;веса&raquo; всех условий для всех маршрутов подходящего типа и выбирает маршрут, имеющий наибольший вес.<br>
									Вес вычисляется для каждого условия отдельно, веса условий одного маршрута не суммируются.<br>
									При формировании заявки, на входе имеются следующие параметры, на основании которых происходит расчет весов каждого условия и выбор маршрута: 
									<ul>
									<li>1. <b>Конкретный сотрудник</b> - заявитель совпадает с указанным в условии сотрудником, вес - 20;</li>
									<li>2. <b>Должность в организации</b> - заявитель занимает указанную должность, вес - 10;</li>
									<li>3. <b>Группа сотрудников</b> - заявитель включен в указанную группу сотрудников, вес - 5;</li>
									<li>4. <b>Информационный ресурс</b> - заявитель запросил доступ к указанному информационному ресурсу, вес - 3;</li>
									<li>5. <b>Организация</b> - заявитель работает в указанной организации, вес - 1;</li>
									</ul>
									<br>
									Заданные параметры условия также служат фильтром при выборе маршрута и рассматриваются через логическое &laquo;И&raquo;.<br>
									Если один или несколько заданных параметров условия не совпадают со входными значениями, данный маршрут будет проигнорирован.<br>
									Таким образом, только при совпадении всех заданных параметров в условии со входными параметрами, для условия маршрута будет расчитан его вес.<br>
									Условия маршрута рассматриваются отдельно друг от друга, через логическое &laquo;ИЛИ&raquo;
								</div>
							</div>
							<div id="table_params_none" style="display:none;">
								<h1 class="errorpage_title">Нет условий</h1>
							</div>
						</div>
					<!--Параметры маршрута-->
					</div>



					<div class="tab_content">
					<!--Дизайнер маршрута-->
						<div class="workflow_area">
							<div class="tool_area">
								<div class="left">
									<div class="ui-button-light" id="workflow_new_button" style="margin:0px;"><span class="ileft icon_new">Новый</span></div>
									<div class="ui-button-light" id="workflow_reload_button" style="margin:0px;"><span class="ileft icon_refresh">Обновить</span></div>
								</div>
								<div class="left">
									<div class="ui-button-light" id="workflow_save_button" style="margin:0px;"><span class="ileft icon_save">Сохранить</span></div>
								</div>
								<div class="left">
									<div class="ui-button-light" id="unit_add_button" style="margin:0px;"><span class="ileft icon_add">Добавить</span></div>
								</div>
							</div>
							<div id="workflow" class="workflow"></div>
						</div>
						<!--Дизайнер маршрута-->
					</div>


				<!--tabs_area-->
				</div>

				<div id="tabs_none" style="display:none;">
					<h1 class="errorpage_title">Маршрут не найден</h1>
				</div>

			</div>
		</div>
	</div>



	<div class="bigblock" id="unit_selector" style="display:none;">
		<div class="titlebar"><h3 id="unit_selector_title">Добавление нового элемента маршрута</h3></div>
		<div class="contentwrapper">
			<div class="contentarea" id="unit_selector_wrapper">
				<h1>Добавление нового элемента маршрута</h1>
				<h2>
					Выберите тип добавляемого элемента, гейткипера и его роль в процессе согласования.<br>
				</h2>
				<br>
				<div class="fline w150"><span>Тип элемента:</span><select style="width:412px;" id="unit_type">
					<option value="1">Начало маршрута</option>
					<option value="2">Гейткипер</option>
					<option value="3">Завершение маршрута: исполнено</option>
					<option value="4">Завершение маршрута: отклонено</option>
				</select></div>

				<div id="unit_type_1">
					<div class="type_sub">
					Данный элемент является блоком начала маршрута.<br>
					Блок начала маршрута в схеме маршрута считается отправной точкой для начала процесса согласования заявки.<br>
					Должен присутствовать обязательно в любом маршруте в единственном экземпляре.
					</div>
				</div>

				<div id="unit_type_2">
					<div class="type_sub">
					Данный элемент является блоком добавления гейткипера на стадии согласования заявки.<br>
					Выберите гейткипера и укажите его роль в процессе согласования.
					</div>
					<br>
					<div class="fline w150"><span>Роль гейткипера:</span><select id="gatekeeper_role">
						<option value="1">Согласование заявки</option>
						<option value="2">Утверждение заявки</option>
						<option value="3">Исполнение заявки</option>
						<option value="4">Уведомление о заявке</option>
					</select></div>
					<br>
					<div class="fline w150"><span>Гейткипер:</span><select id="gatekeeper_type" style="width:412px;">
						<option value="1">Определенный сотрудник</option>
						<option value="2">Руководитель заявителя</option>
						<option value="3">Руководитель организации заявителя</option>
						<option value="4">Владелец информационного ресурса</option>
						<option value="5">Группа сотрудников</option>
						<option value="6">Сотрудник, занимающий определенную должность</option>
						<option value="7">Группа исполнителей, назначенных информационному ресурсу</option>
					</select></div>


					<div class="gatekeeper_type_area">


						<div id="gatekeeper_type_1">
							<div class="type_sub">
								Выберите сотрудника, через которого должна проходить заявка по данному маршруту на текущем этапе согласования.<br>
								<h2>Выберите сотрудника</h2>
								<div id="gatekeeper_employer_none">Сотрудник не выбран...</div>
								<div id="gatekeeper_employer_area" style="display:none;">
									<div class="fline w100"><span>ID сотрудника:</span><p id="gatekeeper_employer_id"></p></div>
									<div class="fline w100"><span>Сотрудник:</span><p id="gatekeeper_employer_name"></p></div>
								</div>
								<br>
								<input type="button" id="change_gatekeeper_employer_button" value="Выбрать..."/>
								<input type="button" id="change_gatekeeper_employer_cancel_button" value="Отмена"/>
							</div>
						</div>


						<div id="gatekeeper_type_2">
							<div class="type_sub">
								Руководитель заявителя будет выбран автоматически исходя из занимаемой им должности и согласно данным организационной структуры предприятия в которой работает заявитель.
							</div>
						</div>


						<div id="gatekeeper_type_3">
							<div class="type_sub">
								Руководитель организации будет выбран автоматически согласно данным организационной структуры предприятия в которой работает заявитель.
							</div>
						</div>


						<div id="gatekeeper_type_4">
							<div class="type_sub">
								Владелец информационного ресурса, к которому в заявке запрашивается доступ, будет выбран автоматически и берется из настроек информационного ресурса.<br>
							</div>
						</div>


						<div id="gatekeeper_type_5">
							<div class="type_sub">
								Выберите группу сотрудников, через которых должна проходить заявка по данному маршруту на текущем этапе согласования.<br>
								<h2>Выберите группу сотрудников</h2>
								<select id="gatekeeper_group" style="width:412px;"></select>
							</div>
						</div>


						<div id="gatekeeper_type_6">
							<div class="type_sub">
								Выберите организацию и определенную должность, чтобы занимающие ее сотрудники, могли обработать заявку на текущем этапе согласования.<br>
								<h2>Выберите должность</h2>
								<div id="gatekeeper_post_none">Вы пока ничего не выбрали...</div>
								<div id="gatekeeper_post_area" style="display:none;">
									<div class="fline w100"><span>Организация:</span><p id="gatekeeper_company_name"></p></div>
									<div class="fline w100"><span>Должность:</span><p id="gatekeeper_post_name"></p></div>
								</div>
								<br>
								<input type="button" id="change_gatekeeper_post_button" value="Выбрать..."/>
								<input type="button" id="change_gatekeeper_post_cancel_button" value="Отмена"/>
							</div>
						</div>


						<div id="gatekeeper_type_7">
							<div class="type_sub">
							Группа исполнителей, назначенных информационному ресурсу в качастве ответственных администраторов, будет выбрана автоматически исходя из настроек информационного ресурса.<br>
							</div>
						</div>

					</div>

				</div>

				<div id="unit_type_3">
					<div class="type_sub">
					Данный элемент является блоком успешного завершения маршрута.<br>
					Блок успешного завершения маршрута, является конечной точкой процесса согласования заявки, по достижении которой считается, что заявка согласована и исполнена.<br>
					Должен присутствовать обязательно в любом маршруте в единственном экземпляре.
					</div>
				</div>

				<div id="unit_type_4">
					<div class="type_sub">
					Данный элемент является блоком неуспешного завершения маршрута.<br>
					Блок неуспешного завершения маршрута, является конечной точкой процесса согласования заявки, по достижении которой считается, что заявка была полностью отклонена и дальнейшему согласованию не подлежит.<br>
					Должен присутствовать обязательно в любом маршруте в единственном экземпляре.
					</div>
				</div>

			</div>
			<div class="buttonarea">
				<div class="ui-button" id="unit_selector_complete_button"><span>Добавить элемент</span></div>
				<div class="ui-button" id="unit_selector_cancel_button"><span>Закрыть</span></div>
			</div>
		</div>
	</div>



	<div class="bigblock" id="param_selector" style="display:none;">
		<div class="titlebar"><h3 id="unit_selector_title">Добавление нового условия</h3></div>
		<div class="contentwrapper">
			<div class="contentarea" id="param_selector_wrapper">
				<h1>Добавление нового условия</h1>
				<h2>
					Здесь Вы можете задать условия, по которым система будет определять, подходит ли данный маршрут для обработки заявки.<br>
				</h2>
				<div class="splitline"></div>

				<h2>Выберите сотрудника, для которого применим данный маршрут</h2>
				<div id="param_employer_none">Любой сотрудник</div>
				<div id="param_employer_area" style="display:none;">
					<div class="fline w100"><span>ID сотрудника:</span><p id="param_employer_id"></p></div>
					<div class="fline w100"><span>Сотрудник:</span><p id="param_employer_name"></p></div>
				</div>
				<br>
				<input type="button" id="change_param_employer_button" value="Выбрать..."/>
				<input type="button" id="change_param_employer_cancel_button" value="Отмена"/>

				<div class="splitline"></div>

				<h2>Выберите организацию и/или должность, для которых применим данный маршрут</h2>
				<div id="param_post_none">Любая организация и должность</div>
				<div id="param_post_area" style="display:none;">
					<div class="fline w100"><span>Организация:</span><p id="param_company_name"></p></div>
					<div class="fline w100"><span>Должность:</span><p id="param_post_name"></p></div>
				</div>
				<br>
				<input type="button" id="change_param_post_button" value="Выбрать..."/>
				<input type="button" id="change_param_post_cancel_button" value="Отмена"/>

				<div class="splitline"></div>
				<h2>Выберите группу сотрудников, для которых применим данный маршрут</h2>
				<select id="param_for_group" style="width:412px;"></select>

				<div class="splitline"></div>
				<h2>Выберите информационный ресурс, для которого применим данный маршрут</h2>
				<select id="param_for_resource" style="width:412px;"></select>

				<div class="splitline"></div>
				<h2>Примечание:</h2>
				<div class="param_desc">
					При выборе маршрута система вычисляет &laquo;веса&raquo; всех условий для всех маршрутов подходящего типа и выбирает маршрут, имеющий наибольший вес.<br>
					Вес вычисляется для каждого условия отдельно, веса условий одного маршрута не суммируются.<br>
					При формировании заявки, на входе имеются следующие параметры, на основании которых происходит расчет весов каждого условия и выбор маршрута: 
					<ul>
					<li>1. <b>Конкретный сотрудник</b> - заявитель совпадает с указанным в условии сотрудником, вес - 20;</li>
					<li>2. <b>Должность в организации</b> - заявитель занимает указанную должность, вес - 10;</li>
					<li>3. <b>Группа сотрудников</b> - заявитель включен в указанную группу сотрудников, вес - 5;</li>
					<li>4. <b>Информационный ресурс</b> - заявитель запросил доступ к указанному информационному ресурсу, вес - 3;</li>
					<li>5. <b>Организация</b> - заявитель работает в указанной организации, вес - 1;</li>
					</ul>
				</div>

			</div>
			<div class="buttonarea">
				<div class="ui-button" id="param_selector_complete_button"><span>Добавить параметр</span></div>
				<div class="ui-button" id="param_selector_cancel_button"><span>Закрыть</span></div>
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



	<div class="bigblock" id="employer_selector" style="display:none;">
		<div class="titlebar"><h3>Выберите сотрудника</h3></div>
		<div class="contentwrapper">
			<div class="contentarea" id="employer_selector_wrapper"><div class="w700c">
				<div class="iline wauto">
					<span>Поиск сотрудника:</span>
					<input type="text" style="width:400px;" id="employer_selector_term" value="" placeholder="Введите фамилию сотрудника..."/>
					<input type="button" style="width:60px;" id="employer_selector_term_button" value="Поиск"/>
				</div>
				<br/>
				<div id="employer_selector_none" style="display:none;"><h2>Сотрудники не найдены...</h2></div>
				<div id="employer_selector_table" style="display:none;">
					<ul class="requestlist" id="employer_selector_list"></ul>
				</div>
			</div></div>
			<div class="buttonarea">
				<div class="ui-button" id="employer_selector_complete_button"><span>Выбрать сотрудника</span></div>
				<div class="ui-button" id="employer_selector_cancel_button"><span>Закрыть</span></div>
			</div>
		</div>
	</div>


</div>