<div class="page_request_view_list">
	<div class="bigblock">
		<div class="titlebar"><h3>Заявки к которым Вы имеете какое-либо отношение</h3></div>
		<div class="contentwrapper">

			<div id="request_view_list_wrapper" class="contentareafull">

				<div class="toolarea">
					<div class="iline">Статус просмотра:<br/>
						<select id="view_watched" style="width:170px;">
							<option value="all">Все заявки</option>
							<option value="1">Только просмотренные</option>
							<option value="0">Только новые заявки</option>
						</select>
					</div>
					<br/>
					<div class="iline">Тип заявки:<br/>
						<select id="view_type" style="width:170px;">
							<option value="all">Все заявки</option>
							<option value="2" selected>Запрос доступа</option>
							<option value="3">Блокировка доступа</option>
						</select>
					</div>
					<br/>
					<div class="iline">Временной интервал:<br/>
						<select id="view_period" style="width:170px;">
							<option value="all">За все время</option>
							<option value="1" selected>За последние сутки</option>
							<option value="7">За последнюю неделю</option>
							<option value="30">За последний месяц</option>
							<option value="90">За последние три месяца</option>
							<option value="365">За последний год</option>
						</select>
					</div>
					<br/>
					<div class="iline wauto">Степень участия:</div>
					<div class="iline wauto"><span><input type="checkbox" id="view_owner" value="1"/></span>В которых я заявитель</div>
					<div class="iline wauto"><span><input type="checkbox" id="view_curator" value="1"/></span>В которых я куратор</div>
					<div class="iline wauto"><span><input type="checkbox" id="view_gatekeeper" value="1"/></span>Согласованные мною</div>
					<div class="iline wauto"><span><input type="checkbox" id="view_performer" value="1"/></span>Исполненные мною</div>
					<div class="iline wauto"><span><input type="checkbox" id="view_watcher" value="1"/></span>Для ознакомления</div>
					<br/>
					<div class="iline">Где заявитель:<br><input type="text" id="view_employer" value="" placeholder="Фамилия заявителя..." style="width:155px;"/><br/><br/></div>
					<div class="ui-button" onclick="request_view_list_filter();" style="width:165px;margin:5px 0px;"><span>Фильтр заявок</span></div>
				</div>

				<div class="centralarea"><div id="request_view_list_table_area"></div></div>

			</div>
		</div>
	</div>




	<div id="tmpl_request_list_filter" style="padding:10px;display:none;">
		
		<div class="iline w200"><span>Начиная с даты:</span><p id="info_request_id"></p></div>
		<div class="iline w200"><span>Заканчивая датой:</span><p id="info_iresource_name"></p></div>
		<br/>
		<div class="iline w200"><span>Организация:</span><p id="info_create_date"></p></div>
		<div class="iline w200"><span>Информационный ресурс:</span><p id="info_curator_name"></p></div>
		<br/>
		<div class="iline w200"><span>Заявитель:</span><p id="info_employer_name"></p></div>
		<div class="iline w200"><span>Работает в организации:</span><p id="info_company_name"></p></div>
		<div class="iline w200"><span>Занимает должность:</span><p id="info_post_name"></p></div>
		<div class="iline w200"><span>Контактный телефон:</span><p id="info_phone"></p></div>
		<div class="iline w200"><span>Электронная почта:</span><p id="info_email"></p></div>
		<br/>
		<div class="iline w200"><span>От Вас ожидается:</span><p id="info_gatekeeper_role_name"></p></div>
	</div>

</div>