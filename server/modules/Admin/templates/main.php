<div id="page_admin_main" class="page_admin_main">

	<div id="admin_dashboard">

		<div class="left_dashboard_area">
			<div>
				<h1 class="admin_area_title">&laquo;Каскад&raquo;</h1>
				<h2 class="admin_area_subtitle">Панель администрирования</h2>
				<br><br>
				<div class="dash_buttons">
					<?php
					$dash_buttons = array(
						'page.employers.list' => '<a href="/admin/employers/list" class="dash_item dash_item_employers"><div class="ititle">Сотрудники</div><div class="idesc">Управление учетными данными сотрудников</div></a>',
						'page.employers.ankets' => '<a href="/admin/employers/ankets" class="dash_item dash_item_employer_anket"><div class="ititle">Анкеты сотрудников</div><div class="idesc">Управление анкетами новых сотрудников</div></a>',
						'page.employers.add' => '<a href="/admin/employers/add" class="dash_item dash_item_employer_add"><div class="ititle">Новый сотрудник</div><div class="idesc">Создание учетной записи нового сотрудника</div></a>',
						'page.org.companies' => '<a href="/admin/org/companies" class="dash_item dash_item_companies"><div class="ititle">Организации</div><div class="idesc">Администрирование списка предприятий</div></a>',
						'page.org.posts' => '<a href="/admin/org/posts" class="dash_item dash_item_posts"><div class="ititle">Должности</div><div class="idesc">Администрирование списка должностей</div></a>',
						'page.org.structure' => '<a href="/admin/org/structure" class="dash_item dash_item_org_tree"><div class="ititle">Организационная структура</div><div class="idesc">Настройка структуры предприятий</div></a>',
						'page.iresources.list' => '<a href="/admin/iresources/list" class="dash_item dash_item_iresources"><div class="ititle">Информационные ресурсы</div><div class="idesc">Администрирование информационных ресурсов</div></a>',
						'page.iresources.add' => '<a href="/admin/iresources/add" class="dash_item dash_item_iresource_add"><div class="ititle">Новый ресурс</div><div class="idesc">Создание нового информационного ресурса</div></a>',
						'page.routes.list' => '<a href="/admin/routes/list" class="dash_item dash_item_routes"><div class="ititle">Маршруты заявок</div><div class="idesc">Администрирование маршрутов согласования заявок</div></a>',
						'page.templates.list' => '<a href="/admin/templates/list" class="dash_item dash_item_templates"><div class="ititle">Шаблоны заявок</div><div class="idesc">Администрирование типовых шаблонов заявок</div></a>',
						'page.requests.list' => '<a href="/admin/requests/list" class="dash_item dash_item_requests"><div class="ititle">Заявки сотрудников</div><div class="idesc">Управление заявками сотрудников</div></a>',
						'page.matrix.employers' => '<a href="/admin/matrix/employers" class="dash_item dash_item_matrix"><div class="ititle">Матрица доступа</div><div class="idesc">Анализ и управление правами доступа сотрудников</div></a>'
					);
					foreach($dash_buttons as $acl_object=>$content){
						if(UserAccess::_checkAccess($acl_object,0)){
							echo $content;
						}
					}
					?>
					
				</div>
				<ul id="left_dashboard_list" class="blocklist"></ul>
			</div>
		</div>

		<div class="right_dashboard_area">
			<div><ul id="right_dashboard_list" class="blocklist"></ul></div>
		</div>

	</div>


	<div id="tmpl_stats" style="padding:10px;display:none;">
		<div class="iline w200"><span>Организаций:</span><p id="stats_companies_total"></p></div>
		<div class="iline w200"><span>Сотрудников:</span><p id="stats_employers_total"></p></div>
		<div class="iline w200"><span>Информационных ресурсов:</span><p id="stats_iresources_total"></p></div>
		<div class="iline w200"><span>Маршрутов:</span><p id="stats_routes_total"></p></div>
		<div class="iline w200"><span>Групп сотрудников:</span><p id="stats_groups_total"></p></div>
		<div class="iline w200"><span>Шаблонов:</span><p id="stats_templates_total"></p></div>
		<div class="iline w200"><span>Создано заявок:</span><p id="stats_requests_total"></p></div>
		<div class="iline w200"><span>Активных заявок по ресурсам:</span><p id="stats_request_iresources_total"></p></div>
		<div class="iline w200"><span>Завершенных по ресурсам:</span><p id="stats_request_iresources_hist_total"></p></div>
	</div>



</div>
