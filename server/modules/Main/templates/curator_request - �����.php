<div class="page_curator_request">
	<div class="bigblock" id="stepmaster_contentwrapper">
		<div class="titlebar"><h3 id="step_title">Создание заявки</h3></div>
		<div class="contentwrapper">
			<div class="contentarea" id="stepmaster_area" style="display:none;">
				<div id="step_container">
				
					<div class="steparea" id="step_1">
						<div class="w700c">
							<h1>Найдите сотрудника для которого будет оформлена заявка</h1><br/>
							<div class="iline w200"><span>Организация сотрудника:</span><select style="width:415px;" id="employer_company"></select></div>
							<div class="iline w200"><span>ФИО сотрудника:</span><input style="width:403px;" placeholder="Введите полностью или частично фамилию сотрудника..." type="text" maxlength="64" value="" id="employer_name"/></div>
							<div class="ui-button-light" style="margin-left:210px;" onclick="curator_request_search_employer();" style="width:165px;margin:5px 0px;"><span>Поиск</span></div>
							<br/><br/>
							<div id="employer_search_results" style="display:none;">
								<h1>Выберите сотрудника и занимаемую должность</h1><br/>
								<div id="employer_post_area"></div>
							</div>
							<div id="employer_search_none" style="display:none;">
								<h1>Сотрудники не найдены</h1><br/>
							</div>
						</div>
					</div>
					
					
					<div class="steparea" id="step_2">
						<div class="toolarea">
							<div class="toolbutton add" id="button_ir_add" onclick="curator_request_ir_selector_open(null);"><div>Добавить</div></div>
							<div class="toolbutton trash" id="button_ir_trash" onclick="curator_request_ir_trash();"><div>Отменить все</div></div>
						</div>
						<div class="centralarea">
							<div id="ir_none">
								<h1>Выберите информационные ресурсы и роли доступа для сотрудника:</h1>
								<h1 class="title_employer_name warning"></h1>
								<h2>На этом шаге Вам необходимо указать к каким ресурсам и какой именно доступ Вы запрашиваете.</h2>
								<h2>Для начала, нажмите на кнопку &laquo;Добавить&raquo;, она находится слева от этой надписи.</h2>
								<h2>
								В открывшейся форме выберите информационный ресурс и 
								напротив интересуемых позиций, укажите какой именно доступ Вы хотите.
								</h2>
							
							</div>
							<div id="ir_area" style="display:none;">
								<h1>Вы формируете заявку и запрашиваете доступ для сотрудника:</h1>
								<h1 class="title_employer_name warning"></h1><br>
								<ul id="ir_list" class="blocklist"></ul>
							</div>
						</div>
					</div>
					
					
					<div class="steparea" id="step_3">
						<div class="w700c">
							<h1>Заявка для сотрудника:</h1>
							<h1 class="title_employer_name warning"></h1>
							<h1>сформирована и готова к отправке</h1>
							<h2>Чтобы инициировать процесс согласования, нажмите кнопку &laquo;Готово&raquo;</h2>
							<br/>
							<h2>
							В полях ниже предлагаем Вам оставить свои контактные данные.<br/>
							Они могут понадобиться обрабатывающим заявку сотрудникам для связи с Вами. 
							</h2>
							<div class="iline w150"><span>Контактный телефон:</span><input type="text" maxlength="64" style="width:200px;" id="input_ir_phone" value="<?=addslashes(User::_get('phone'));?>"></div>
							<div class="iline w150"><span>Электронная почта:</span><input type="text" maxlength="64" style="width:200px;" id="input_ir_email" value="<?=addslashes(User::_get('email'));?>"></div>
						</div>
					</div>
					
					
					<div class="steparea" id="step_4">
						<h1>Step 4</h1>
					</div>
					
					
					<div class="steparea" id="step_5">
						<h1>Step 5</h1>
					</div>
				
				
				</div>
			</div>
			<div class="buttonarea" id="stepmaster_button_area" style="display:none;">
				<div class="ui-button" id="button_step_prev"><span class="ileft icon_prev">Назад</span></div>
				<div class="ui-button" id="button_step_next"><span class="iright icon_next">Дальше</span></div>
				<div class="ui-button" id="button_step_done"><span class="ileft icon_check">Готово</span></div>
			</div>
		</div>
	</div>


	<div class="bigblock" id="ir_selector" style="display:none;">
		<div class="titlebar"><h3>Выберите информационный ресурс и укажите запрашиваемый доступ</h3></div>
		<div class="contentwrapper">
			<div class="contentarea" id="ir_selector_wrapper">
				<div class="iline wauto">
					<span>Выберите информационный ресурс:</span>
					<select style="width:500px;" id="ir_selector_iresource_list" onchange="curator_request_ir_selector_iresource_list_change();"></select>
				</div>
				<div id="ir_selector_none" style="display:none;"><h1>В выбранном информационном ресурсе нет функционала, к которому Вы могли бы запросить доступ</h1></div>
				<div id="ir_selector_select" style="display:none;">
					<br><h2>В списке выше, выберите информационный ресурс, к которому Вы запрашиваете доступ</h2>
				</div>
				<div id="ir_selector_table">
					<div id="ir_selector_toolbar">
						<a href="#" onclick="curator_request_irs_sections_display(true);">Развернуть все</a>
						<a href="#" onclick="curator_request_irs_sections_display(false);">Свернуть все</a>
					</div>
				</div>
			</div>
			<div class="buttonarea">
				<div class="ui-button" onclick="curator_request_ir_selector_complete();"><span>Ок</span></div>
				<div class="ui-button" onclick="curator_request_ir_selector_cancel();"><span>Закрыть</span></div>
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
				<div class="idesc">Нажмите сюда, чтобы запросить доступ к информационным ресурсам для сотрудника организации</div>
			</a>
			<a href="/main/index" class="dashboard_item item_home_page">
				<div class="ititle">На главную страницу</div>
				<div class="idesc">Нажав сюда, Вы вернетесь в главное меню</div>
			</a>
		</div>

	</div>

	<div id="stepmaster_none" style="display:none;">
		<h1 class="errorpage_title">Функционал недоступен</h1>
		<h2 class="errorpage_subtitle">Вы не можете запрашивать доступ от имени других сотрудников</h2>
	</div>

</div>