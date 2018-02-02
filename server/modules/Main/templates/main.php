<div class="gatekeeper_requests_count_area">
	<h1 class="notify_title">
		<a href="/main/gatekeeper/requestlist"
		data-filter="pulse pulse:pulse-two"
		data-pulse='{
			"duration":2000,
			"property":"opacity",
			"from":0.5,"to":1
		}'
		data-pulse-two='{
			"duration":2000,
			"property":"color",
			"from":"#929BA8",
			"to":"#efefef"
		}'
		>У Вас на рассмотрении <span class="gatekeeper_requests_count_value"></span>, нажмите для просмотра</a>
	</h1>
</div>

<div class="block dashboard">


	<div class="titlebar"><h3>Выберите действие</h3></div>

		<a href="/main/requests/new" class="dashboard_item item_request_new">
			<div class="ititle">Создать заявку</div>
			<div class="idesc">Нажмите сюда, если Вам требуется запросить доступ к информационным ресурсам</div>
		</a>

		<?php
		if(!empty($this->variables['can_curator'])){
			echo'
			<a href="/main/curator/request" class="dashboard_item item_request_employer">
				<div class="ititle">Заявка для сотрудника</div>
				<div class="idesc">Нажмите сюда, чтобы запросить доступ к информационным ресурсам для другого сотрудника</div>
			</a>
			';
		}
		if(!empty($this->variables['can_add_employers'])){
			echo'
			<a href="/main/curator/employer" class="dashboard_item item_add_employer">
				<div class="ititle">Добавить сотрудника</div>
				<div class="idesc">Нажмите сюда для заполнения анкеты на нового сотрудника организации</div>
			</a>
			';
		}
		if(!empty($this->variables['is_ir_owner'])){
			echo'
			<a href="/main/iresources/owner" class="dashboard_item item_iresources_owner">
				<div class="ititle">Мои ресурсы</div>
				<div class="idesc">Вы являетесь владельцем одного или нескольких информационных ресурсов, для управления доступом нажмите здесь</div>
			</a>
			';
		}
		?>

		<a href="/main/requests/complete" class="dashboard_item item_access_list">
			<div class="ititle">Мой доступ</div>
			<div class="idesc">Здесь можно просмотреть Ваши текущие права доступа к корпоративным информационным ресурсам</div>
		</a>

		<a href="/main/gatekeeper/requestlist" class="dashboard_item item_gatekeeper">
			<div class="ititle">Заявки на рассмотрении</div>
			<div class="idesc">Чтобы согласовать, утвердить или исполнить заявки других сотрудников, зайдите сюда
				<div class="gatekeeper_requests_count_area" style="margin-top:10px;font-weight:bold;">У Вас <span class="gatekeeper_requests_count_value"></span></div>
			</div>
		</a>

		<a href="/main/assistants" class="dashboard_item item_assistants">
			<div class="ititle">Замещение</div>
			<div class="idesc">Если Вы уходите в отпуск, то здесь Вы можете указать сотрудников, которые будут временно вместо Вас согласовывать заявки</div>
		</a>

</div>