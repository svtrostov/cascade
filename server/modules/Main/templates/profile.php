<div class="page_profile">

	<div class="bigblock">
		<div class="titlebar"><h3>Персональные сведения</h3></div>
		<div class="contentwrapper">
			<div class="contentareafull" id="profile_wrapper">


				<div class="toolarea">
					<div class="toolbutton profile" id="button_profile"><div>Профиль</div></div>
					<div class="toolbutton posts" id="button_posts"><div>Должности</div></div>
					<div class="toolbutton mail" id="button_notice"><div>Уведомления</div></div>
					<div class="toolbutton security" id="button_security"><div>Безопасность</div></div>
				</div>

				<div class="centralarea"><div id="step_container">

					<div class="steparea" id="step_profile">
					</div>

					<div class="steparea" id="step_notice">
					</div>

					<div class="steparea" id="step_posts">
					</div>


					<div class="steparea" id="step_security">
					</div>

				</div></div>


			</div>
		</div>
	</div>


	<div id="tmpl_profile_info" style="padding:10px;display:none;width:100%;">
		<div class="iline w200"><span>Идентификатор:</span><input style="width:150px;" type="text" class="disabled" readonly="true" value="" id="info_employer_id"/></div>
		<div class="iline w200"><span>Имя пользователя:</span><input style="width:150px;" type="text" class="disabled" readonly="true" value="" id="info_username"/></div>
		<div class="iline w200"><span>Фамилия:</span><input style="width:200px;" type="text" class="disabled" readonly="true" value="" id="info_last_name"/></div>
		<div class="iline w200"><span>Имя:</span><input style="width:200px;" type="text" class="disabled" readonly="true" value="" id="info_first_name"/></div>
		<div class="iline w200"><span>Отчество:</span><input style="width:200px;" type="text" class="disabled" readonly="true" value=""  id="info_middle_name"/></div>
		<div class="iline w200"><span>Дата рождения:</span><input style="width:150px;" type="text" class="disabled" readonly="true" value="" id="info_birth_date"/></div>
		<br/><br/>
		<div class="iline w200"><span>Контактный телефон*:</span><input style="width:200px;" maxlength="64" type="text" value="" id="info_phone"/></div>
		<div class="iline w200"><span>Электронная почта:</span><input style="width:200px;" maxlength="64" type="text" value="" id="info_email"/></div>
		<div class="ui-button" style="margin-left:210px;" onclick="profile_change_info();" style="width:165px;margin:5px 0px;"><span>Сохранить</span></div>
	</div>


	<div id="tmpl_notice_info" style="padding:10px;display:none;width:100%;">
		<h2>
			Выберите типы уведомлений, которые Вы хотите получать на электронную почту (адрес Вашей электронной почты Вы можете указать в разделе &laquo;Профиль&raquo;).
		</h2>
		<div class="iline w300"><span>Процесс обработки заявок, где я - заявитель:</span><input type="checkbox" id="info_notice_me_requests"/></div>
		<div class="iline w300"><span>Процесс обработки заявок, где я - куратор:</span><input type="checkbox" id="info_notice_curator_requests"></div>
		<div class="iline w300"><span>Заявки, поступившие мне на согласование:</span><input type="checkbox" id="info_notice_gkemail_1"></div>
		<div class="iline w300"><span>Заявки, поступившие мне на утверждение:</span><input type="checkbox" id="info_notice_gkemail_2"></div>
		<div class="iline w300"><span>Заявки, поступившие мне на исполнение:</span><input type="checkbox" id="info_notice_gkemail_3"></div>
		<div class="iline w300"><span>Уведомления о заявках сотрудников:</span><input type="checkbox" id="info_notice_gkemail_4"></div>
		<div class="ui-button" style="margin-left:310px;" onclick="profile_change_notice();" style="width:165px;margin:5px 0px;"><span>Сохранить</span></div>
	</div>


	<div id="tmpl_profile_password" style="padding:10px;display:none;width:100%;">
		<h2>
			Для изменения пароля входя в систему заявок, введите в соответствующие поля ниже Ваш текущий пароль, новый пароль и повторно новый пароль в поле &laquo;Подтверждение&laquo;.<br/>
			Длина нового пароля должна быть не менее восьми символов, содержать строчные, прописные буквы и цифры.
		</h2>
		<div class="iline w150"><span>Текущий пароль*:</span><input style="width:200px;" maxlength="64" type="password" value="" id="password_prev"/></div>
		<br/><br/>
		<div class="iline w150"><span>Новый пароль*:</span><input style="width:200px;" maxlength="64" type="password" value="" id="password_new"/></div>
		<div class="iline w150"><span>Подтверждение*:</span><input style="width:200px;" maxlength="64" type="password" value="" id="password_confirm"/></div>
		<div class="ui-button" style="margin-left:160px;" onclick="profile_change_password();" style="width:195px;margin:5px 0px;"><span>Сменить пароль</span></div>
	</div>

</div>