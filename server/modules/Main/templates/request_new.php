<div class="page_request_new">
	<div class="bigblock" id="stepmaster_contentwrapper">
		<div class="titlebar"><h3 id="step_title">Создание заявки</h3></div>
		<div class="contentwrapper">
			<div class="contentarea" id="stepmaster_area" style="display:none;">
				<div id="step_container">
				
					<div class="steparea" id="step_1">
						<div class="w700c">
							<h1>Выберите должность, в рамках которой Вы формируете заявку</h1><br/>
							<div id="employer_post_area"></div>
						</div>
					</div>
					
					
					<div class="steparea" id="step_2">
						<div class="toolarea">
							<div class="toolbutton add" id="button_ir_add" onclick="request_new_iresource_selectorOpen(null);"><div>Добавить</div></div>
							<div class="toolbutton trash" id="button_ir_trash" onclick="request_new_ir_trash();"><div>Отменить все</div></div>
						</div>
						<div class="centralarea">
							<div id="ir_none">
								<h1>Выберите информационные ресурсы и роли доступа</h1>
								<h2>На этом шаге Вам необходимо указать к каким ресурсам и какой именно доступ Вы запрашиваете.</h2>
								<h2>Для начала, нажмите на кнопку &laquo;Добавить&raquo;, она находится слева от этой надписи.</h2>
								<h2>
								В открывшейся форме выберите информационный ресурс и нажмите &laquo;Выбрать ресурс&raquo;, далее  
								напротив интересуемых позиций, укажите какой именно доступ Вы хотите.
								</h2>
							
							</div>
							<div id="ir_area" style="display:none;">
								<h1>Вы формируете заявку и запрашиваете доступ:</h1><br>
								<ul id="ir_list" class="blocklist"></ul>
							</div>
						</div>
					</div>
					
					
					<div class="steparea" id="step_3">
						<div class="w700c">
							<h1>Ваша заявка сформирована и готова к отправке</h1>
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
					<select style="width:500px;" id="ir_selector_iresource_list" onchange="request_new_ir_selector_iresource_list_change();"></select>
				</div>
				<div id="ir_selector_none" style="display:none;"><h1>В выбранном информационном ресурсе нет функционала, к которому Вы могли бы запросить доступ</h1></div>
				<div id="ir_selector_select" style="display:none;">
					<br><h2>В списке выше, выберите информационный ресурс, к которому Вы запрашиваете доступ</h2>
				</div>
				<div id="ir_selector_table">
					<div id="ir_selector_toolbar">
						<a href="#" onclick="request_new_irs_sections_display(true);">Развернуть все</a>
						<a href="#" onclick="request_new_irs_sections_display(false);">Свернуть все</a>
					</div>
				</div>
			</div>
			<div class="buttonarea">
				<div class="ui-button" onclick="request_new_ir_selector_complete();"><span>Ок</span></div>
				<div class="ui-button" onclick="request_new_ir_selector_cancel();"><span>Закрыть</span></div>
			</div>
		</div>
	</div>



	<div class="bigblock" id="ir_request_complete" style="display:none;">

		<h1 class="big_title success">Заявка создана</h1>
		<h2 class="big_subtitle">Отследить статус согласования можно через &laquo;Историю заявок&raquo;</h2>
		<br/>
		<div class="w800c">
			<a href="#" class="dashboard_item item_request_info" onclick="request_new_to_request_info();">
				<div class="ititle">Перейти к заявке</div>
				<div class="idesc">Открыть карточку только что созданной заявки</div>
			</a>
			<a href="/main/requests/new" class="dashboard_item item_request_new" onclick="">
				<div class="ititle">Создать заявку</div>
				<div class="idesc">Нажмите сюда, если Вам требуется сделать еще одну заявку</div>
			</a>
			<a href="/main/requests/history" class="dashboard_item item_request_history">
				<div class="ititle">История заявок</div>
				<div class="idesc">Здесь можно просмотреть все созданные Вами ранее заявки и отследить статус процесса их согласования</div>
			</a>
			<a href="/main/index" class="dashboard_item item_home_page">
				<div class="ititle">На главную страницу</div>
				<div class="idesc">Нажав сюда, Вы вернетесь в главное меню</div>
			</a>
		</div>

	</div>


	<div class="bigblock" id="iresource_selector" style="display:none;">
		<div class="titlebar"><h3 id="iresource_selector_title">Выберите информационный ресурс</h3></div>
		<div class="contentwrapper">
			<div class="contentarea" id="iresource_selector_wrapper">

				<div id="iresource_selector_groups_area" class="sleft_panel">
					<select id="iresource_selector_groups_select" size="2" onchange="request_new_iresource_selectorChangeGroup();"></select>
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
				<div class="ui-button" id="iresource_selector_complete_button" onclick="request_new_iresource_selectorComplete();"><span>Выбрать ресурс</span></div>
				<div class="ui-button" id="iresource_selector_cancel_button" onclick="request_new_iresource_selectorClose();"><span>Закрыть</span></div>
			</div>
		</div>
	</div>

</div>