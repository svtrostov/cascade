<div class="page_assistants">
	<div class="bigblock">
		<div class="titlebar"><h3>Замещения в системе заявок</h3></div>
		<div class="contentwrapper">
			<div class="contentareafull" id="assistants_wrapper">


				<div class="toolarea">
					<div class="toolbutton assistants" id="button_assistants"><div>Меня замещают</div></div>
					<div class="toolbutton delegates" id="button_delegates"><div>Я замещаю</div></div>
					<div class="toolbutton about" id="button_about"><div>Подсказка</div></div>
				</div>

				<div class="centralarea"><div id="step_container">

					<div class="steparea" id="step_assistants">
						<div class="ui-button-light" onclick="assistants_selector_open();" style="width:200px;margin:5px 0px;"><span>Добавить заместителя</span></div>
						<div id="assistants_table_area"></div>
					</div>

					<div class="steparea" id="step_delegates">
						<div id="delegates_table_area"></div>
					</div>


					<div class="steparea" id="step_about">
						<h1>Что такое замещение и как оно работает</h1>
						<h2>Вы можете делегировать свое право работы с заявками (согласование, утверждение, исполнение) другим сотрудникам Вашей организации.<br/>
						Ваши коллеги, в свою очередь, также могут передать Вам свои полномочия по работе с заявками.</h2>
						<h2>Например, Вы активно участвуете в процессе согласования заявок и в один прекрасный момент собрались в отпуск на пару недель, отдохнуть от повседневной суеты. 
						Разумеется, Ваше отсутствие не должно останавливать бизнес-процессы в компании.
						Чтобы Ваши коллеги не ждали, когда Вы вернетесь с отдыха, Вы можете одному из них, например, своему заместителю, делегировать право согласования заявок вместо Вас.
						В результате во время Вашего отсутствия Ваш заместитель за Вас выполняет функцию согласующего заявки.
						</h2>
					</div>

				</div></div>


			</div>
		</div>
	</div>


	<div class="bigblock" id="assistants_selector" style="display:none;">
		<div class="titlebar"><h3>Выберите замещающего Вас сотрудника</h3></div>
		<div class="contentwrapper">
			<div class="contentarea" id="assistants_selector_wrapper"><div class="w700c">
				<div class="iline wauto">
					<span>Поиск сотрудника:</span>
					<input type="text" style="width:400px;" id="assistants_selector_term" value="" placeholder="Введите фамилию сотрудника..."/>
					<input type="button" style="width:60px;" id="assistants_selector_search_button" value="Поиск" onclick="assistants_selector_search();"/>
				</div>
				<br/>
				<div id="assistants_selector_none" style="display:none;"><h2>Нет сотрудников с указанной фамилией...</h2></div>
				<div id="assistants_selector_table" style="display:none;">
					<ul class="requestlist" id="selectorlist"></ul>
				</div>
			</div></div>
			<div class="buttonarea">
				<div class="ui-button" id="assistants_selector_complete_button" onclick="assistants_selector_complete();"><span>Выбрать сотрудника</span></div>
				<div class="ui-button" onclick="assistants_selector_cancel();"><span>Закрыть</span></div>
			</div>
		</div>
	</div>


	<div class="bigblock" id="assistants_selector_period" style="display:none;">
		<div class="titlebar"><h3>Укажите период предоставления полномочий</h3></div>
		<div class="contentwrapper">
			<div class="contentarea" id="assistants_selector_wrapper"><div class="w700c">
				<h1>Делегировать полномочия сотруднику:</h1>
				<ul class="requestlist"><li><div class="iline" id="assistants_selector_selected_name"></div></li></ul>
				<br>
				<h1>На период:</h1>
				<div class="iline w250"><span>Начиная с даты (включительно):</span><input type="text" style="width:120px;" id="assistants_selector_date_from" value="" class="calendar_input"/></div>
				<div class="iline w250"><span>Заканчивая датой (включительно):</span><input type="text" style="width:120px;" id="assistants_selector_date_to" value="" class="calendar_input"/></div>
				<br>
				<div class="iline w250"><span>Подтверждаю правильность:</span><input type="checkbox" id="assistants_selector_confirm" value="1" onchange="assistants_selector_confirm_change();"/></div>
				<br>
				<div class="ui-button" id="assistants_selector_done_button" onclick="assistants_selector_done();"><span>Делегировать полномочия</span></div>
			</div></div>
			<div class="buttonarea">
				<div class="ui-button" onclick="assistants_selector_cancel();"><span>Закрыть</span></div>
			</div>
		</div>
	</div>


</div>