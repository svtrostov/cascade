<div class="page_curator_request">

	<div id="bigblock_none" style="display:none;">
		<h1 class="errorpage_title">Функционал недоступен</h1>
		<h2 class="errorpage_subtitle">Вы не можете запрашивать доступ от имени других сотрудников</h2>
	</div>

	<div class="bigblock" id="bigblock_wrapper">
		<div class="titlebar"><h3 id="step_title">Создание заявки</h3></div>
		<div class="contentwrapper" id="bigblock_contentwrapper">

			<div id="employers_area">
				<div id ="employers_none">
					<center><b>Вы пока не выбрали ни одного сотрудника, для которого будете формировать заявку.</b></center>
					<br/>
					Используя поиск, найдите одного или нескольких сотрудников для которых будет оформлена заявка.<br/>
					<br/>
					При добавлении нескольких сотрудников, все они должны работать в одной организации.
				</div>
				<div id ="employers_list" style="display:none;">
					<div id="employers_items_desc">Вы формируете заявку для следующих сотрудников:</div>
					<ul id ="employers_items"></ul>
				</div>
				<div id="employers_list_tool" style="display:none;">
					<div id="employers_list_tool_select" style="text-align:center;"><div class="ui-button-light" id="selected_employer_del_button" style="margin:0px;"><span>Удалить сотрудников</span></div></div>
					<div id="employers_list_tool_unselect" style="text-align:center;">Выберите сотрудников, которых надо удалить из списка</div>
				</div>
			</div>



			<div id="stepmaster_area"><!--stepmaster_area-->

				<div id="step_container"><!--step_container-->

					<div class="steparea" id="step_1"><!--step_1-->
						<div class="filter_area" id="step_1_filter_area">
							<div class="filter_line">
								<div class="left">
									<select style="width:230px;" id="employer_company"></select>
								</div>
								<div class="left">
									<select style="width:160px;" id="search_type">
										<option value="employer" selected>ФИО сотрудника</option>
										<option value="post">Название должности</option>
									</select>
									<select style="width:120px;" id="term_type">
										<option value="begin" selected>начинается с</option>
										<option value="contain">содержит</option>
									</select>
									<input style="width:203px;" placeholder="Введите поисковый запрос..." type="text" maxlength="64" value="" id="employer_name"/>
									<div class="ui-button-light" id="search_employer_button" style="margin:0px;"><span class="ileft icon_filter">Поиск</span></div>
								</div>
							</div>
						</div>

						<div class="employers_area">
							<div id="employer_search_hint">
								<h2>
									1. Выберите организацию, в которой работают сотрудники;<br>
									2. Выполните поиск сотрудников, введя в строке поиска полностью или частично фамилию или должность сотрудника;<br>
									3. В результатах поиска выберите нужных сотрудников и добавьте их в список заявителей;<br>
									4. После формирования списка заявителей, переходите к следующему шагу оформления заявки.<br>
								</h2>
							</div>
							<div id="employer_search_results" style="display:none;">
								<div id="employer_search_select">
									<div class="ui-button" id="search_employer_add_button" style="margin:0px;"><span class="ileft icon_left">Вы выбрали сотрудников, нажмите здесь чтобы добавить их в список заявителей</span></div>
								</div>
								<div id="employer_search_noselect">
									<h2>Выберите интересуемых сотрудников из списка ниже...</h2>
								</div>
								<div id="employer_search_table"></div>
								<div id="employer_search_select_bottom">
									<div class="ui-button" id="search_employer_add_button_bottom" style="margin:0px;"><span class="ileft icon_left">Вы выбрали сотрудников, нажмите здесь чтобы добавить их в список заявителей</span></div>
								</div>
							</div>
							<div id="employer_search_none" style="display:none;">
								<h1 class="errorpage_title">Сотрудники не найдены</h1>
							</div>
						</div>


					</div><!--step_1-->



					<div class="steparea" id="step_2"><!--step_2-->

						<div class="filter_area" id="step_2_filter_area">
							<div class="filter_line">
								<div class="left" id="ir_add_area">
									<div class="ui-button-light" id="button_ir_add" style="margin:0px;"><span class="ileft icon_add">Добавить ресурс</span></div>
								</div>
								<div class="left" id="ir_trash_area">
									<div class="ui-button-light" id="button_ir_trash" style="margin:0px;"><span class="ileft icon_trash">Удалить все</span></div>
								</div>
							</div>
						</div>

						<div id="ir_none">
							<h1>Выберите информационные ресурсы и роли доступа.</h1>
							<h2>На этом шаге Вам необходимо указать к каким ресурсам и какой именно доступ Вы запрашиваете.</h2>
							<h2>Для начала, нажмите на кнопку &laquo;Добавить ресурс&raquo;, она находится сверху от этой надписи.</h2>
							<h2>
							В открывшейся форме выберите информационный ресурс и нажмите &laquo;Выбрать ресурс&raquo;, далее  
							напротив интересуемых позиций, укажите какой именно доступ Вы хотите.
							</h2>
						</div>

						<div id="ir_area" style="display:none;">
							<h1>Вы формируете заявку и запрашиваете доступ:</h1>
							<ul id="ir_list" class="blocklist"></ul>
						</div>


					</div><!--step_2-->


					<div class="steparea" id="step_3"><!--step_3-->
						<div class="w700c">
							<h1>Заявка сформирована и готова к отправке</h1>
							<h2>Чтобы инициировать процесс согласования, нажмите кнопку &laquo;Готово&raquo;</h2>
							<br/>
							<h2>
							В полях ниже предлагаем Вам оставить свои контактные данные.<br/>
							Они могут понадобиться обрабатывающим заявку сотрудникам для связи с Вами. 
							</h2>
							<div class="iline w150"><span>Контактный телефон:</span><input type="text" maxlength="64" style="width:200px;" id="input_ir_phone" value="<?=addslashes(User::_get('phone'));?>"></div>
							<div class="iline w150"><span>Электронная почта:</span><input type="text" maxlength="64" style="width:200px;" id="input_ir_email" value="<?=addslashes(User::_get('email'));?>"></div>
						</div>
					</div><!--step_3-->


				</div><!--step_container-->


				<div id="stepmaster_button_area">
					<div class="ui-button" id="button_step_prev"><span class="ileft icon_prev">Назад</span></div>
					<div class="ui-button" id="button_step_next"><span class="iright icon_next">Дальше</span></div>
					<div class="ui-button" id="button_step_done"><span class="ileft icon_check">Готово</span></div>
				</div>

			</div><!--stepmaster_area-->


			<div id="stepmaster_splitter"></div>

		</div>
	</div>





	<div class="bigblock" id="iresource_selector" style="display:none;">
		<div class="titlebar"><h3 id="iresource_selector_title">Выберите информационный ресурс</h3></div>
		<div class="contentwrapper">
			<div class="contentarea" id="iresource_selector_wrapper">

				<div id="iresource_selector_groups_area" class="sleft_panel">
					<select id="iresource_selector_groups_select" size="2"></select>
				</div>
				<div id="iresource_selector_list" class="sright_panel">
					<div class="table_filter">
						<div class="flabel">Фильтр:</div>
						<div class="fbutton" id="iresources_filter_button"></div>
						<div class="finput"><input type="text" value="" id="iresources_filter"/></div>
					</div>

					<div id="iresource_selector_list_area_wrapper"></div>
				</div>
				<div id="iresource_selector_splitter" class="small_splitter"></div>

			</div>
			<div class="buttonarea">
				<div class="ui-button" id="iresource_selector_complete_button"><span>Выбрать ресурс</span></div>
				<div class="ui-button" id="iresource_selector_cancel_button"><span>Закрыть</span></div>
			</div>
		</div>
	</div>


	<div class="bigblock" id="ir_roles" style="display:none;">
		<div class="titlebar"><h3>Выберите информационный ресурс и укажите запрашиваемый доступ</h3></div>
		<div class="contentwrapper">
			<div class="contentarea" id="ir_roles_wrapper">
				<div class="iline wauto">
					<span>Выберите информационный ресурс:</span>
					<select style="width:500px;" id="ir_roles_iresource_list"></select>
				</div>
				<div id="ir_roles_none" style="display:none;"><h1>В выбранном информационном ресурсе нет функционала, к которому Вы могли бы запросить доступ</h1></div>
				<div id="ir_roles_select" style="display:none;">
					<br><h2>В списке выше, выберите информационный ресурс, к которому Вы запрашиваете доступ</h2>
				</div>
				<div id="ir_roles_table">
					<div id="ir_roles_toolbar">
						<a href="#" id="ir_roles_sections_show_button">Развернуть все</a>
						<a href="#" id="ir_roles_sections_hide_button">Свернуть все</a>
					</div>
				</div>
			</div>
			<div class="buttonarea">
				<div class="ui-button" id="ir_roles_complete_button"><span>Ок</span></div>
				<div class="ui-button" id="ir_roles_close_button"><span>Закрыть</span></div>
			</div>
		</div>
	</div>



	<div class="bigblock" id="ir_request_complete" style="display:none;">

		<h1 class="big_title success">Заявка создана</h1>
		<h2 class="big_subtitle">Отследить статус согласования можно через раздел &laquo;Заявки с моим участием&raquo; в меню &laquo;История&raquo;</h2>
		<br/>
		<div class="w800c">
			<a href="/main/curator/request" class="dashboard_item item_request_employer">
				<div class="ititle">Заявка для сотрудника</div>
				<div class="idesc">Нажмите сюда, чтобы запросить доступ к информационным ресурсам для другого сотрудника</div>
			</a>
			<a href="/main/index" class="dashboard_item item_home_page">
				<div class="ititle">На главную страницу</div>
				<div class="idesc">Нажав сюда, Вы вернетесь в главное меню</div>
			</a>
		</div>

	</div>

</div>