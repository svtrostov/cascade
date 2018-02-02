<div class="page_iresources_owner" id="page_iresources_owner">

	<div class="bigblock" id="bigblock_wrapper">
		<div class="titlebar normal">
			<h3 id="bigblock_title">Управление правами доступа к Вашим информационным ресурсам</h3>
		</div>
		<div class="contentwrapper">
			<div class="contentareafull">


				<div class="navigationarea">
					<div class="iline wauto">
						<span>Выберите информационный ресурс:</span>
						<select style="width:550px;" id="iresource_selector"></select>
					</div>
				</div>


				<div class="centralarea" id="centralarea_container" style="display:none;">

					<div id="tabs_area" class="tabs_area absolute">
					<!--tabs_area-->

						<ul class="tabs">
							<li class="tab">Доступ по сотрудникам</li>
							<li class="tab">Доступ по функционалу</li>
						</ul>

						<div class="tab_content">
						<!--По сотрудникам-->
							<div id="employers_area" class="sleft_panel">
								<div id="employers_table_area">
									<div class="table_filter">
										<div class="flabel">Фильтр:</div>
										<div class="fbutton" id="employers_filter_button"></div>
										<div class="finput"><input type="text" value="" id="employers_filter"/></div>
									</div>
									<div id="employers_table_wrapper"><div id="employers_table" class="table_area"></div></div>
								</div>
								<div id="employers_none" class="none_message">В настоящий момент нет сотрудников, которые имеют доступ к этому информационному ресурсу</div>
							</div>
							<div id="iroles_list_area" class="sright_panel">
								<div id="iroles_list_table_area">
									<div class="tool_area">
										<div class="left">
											<div id="objects_tool_lock">
												<div class="ui-button-light" id="object_lock_button" style="margin:0px;"><span class="ileft icon_lock">Блокировать доступ к выбранным элементам</span></div>
											</div>
											<div id="objects_tool_none">
												Выбранному сотруднику доступен следующий функционал:
											</div>
										</div>
										<div class="right">
											<div class="iline wauto"><span>Фильтр:</span><input type="text" style="width:150px;" id="objects_filter"/></div>
										</div>
									</div>
									<div id="iroles_list_table_wrapper"><div id="iroles_list_table" class="table_area"></div></div>
								</div>
								<div id="iroles_list_none" class="none_message">У выбранного сотрудника нет доступа к какому-либо функционалу указанного информационного ресурса</div>
								<div id="iroles_list_select" class="none_message">Выберите сотрудника в списке слева, чтобы просмотреть его права доступа в указанном информационном ресурсе</div>
							</div>
							<div id="employers_splitter" class="small_splitter"></div>
						<!--По сотрудникам-->
						</div>

						<div class="tab_content">
						<!--По функционалу-->
							<div id="iroles_area" class="sleft_panel">
								<div id="iroles_table_area">
									<div class="table_filter">
										<div class="flabel">Фильтр:</div>
										<div class="fbutton" id="iroles_filter_button"></div>
										<div class="finput"><input type="text" value="" id="iroles_filter"/></div>
									</div>
									<div id="iroles_table_wrapper"><div id="iroles_table" class="table_area"></div></div>
								</div>
								<div id="iroles_none" class="none_message">В этом информационном ресурсе нет функционала</div>
							</div>
							<div id="employers_list_area" class="sright_panel">
								<div id="employers_list_table_area">
									<div class="tool_area">
										<div class="left">
											<div id="employers_tool_none">
												Следующим сотрудникам доступен выбранный функционал:
											</div>
										</div>
										<div class="right">
											<div class="iline wauto"><span>Фильтр:</span><input type="text" style="width:150px;" id="employer_list_filter"/></div>
										</div>
									</div>
									<div id="employers_list_table_wrapper"><div id="employers_list_table" class="table_area"></div></div>
								</div>
								<div id="employers_list_none" class="none_message">Нет сотрудников, которым доступен выбранный функционал</div>
								<div id="employers_list_select" class="none_message">Выберите элемент в списке слева, чтобы просмотреть какие сотрудники имеют к нему доступ</div>
							</div>
							<div id="iroles_splitter" class="small_splitter"></div>
						<!--По функционалу-->
						</div>

					<!--tabs_area-->
					</div>

				</div>


			</div>
		</div>
	</div>


	<div id="bigblock_none" style="display:none;">
		<h1 class="errorpage_title">Страница недоступна</h1>
		<h2 class="errorpage_subtitle">Вы не являетесь владельцем каких-либо информационных ресурсов</h2>
	</div>



	<div class="bigblock" id="ir_locker" style="display:none;">
		<div class="titlebar"><h3>Оформление заявки на блокировку доступа</h3></div>
		<div class="contentwrapper">
			<div class="contentarea" id="ir_locker_wrapper"><div class="w900c">

				<h1><font color="red">Оформление заявки на блокировку доступа</font></h1>
				<h2>
					Вы формируете заявку на блокировку доступа сотрудника к функционалу информационного ресурса.<br>
					<font color="black">После оформления заявки, доступ будет заблокирован не моментально, а только после исполнения заявки IT специалистами.</font><br>
					В случае возникновения необходимости отмены заявки на блокировку, самостоятельно ее отменить Вы не можете, для отмены заявки обратитесь к администратору системы.
				</h2>
				<br>

				<h1>Информация для заявки:</h1>
				<div class="iline w200"><span>Информационный ресурс:</span><p class="request_iresource_name"></p></div>
				<div class="iline w200"><span>ФИО Сотрудника:</span><p class="request_employer_name"></p></div>
				<div class="iline w200"><span>Организация сотрудника:</span><p class="request_employer_company"></p></div>
				<div class="iline w200"><span>Должность сотрудника:</span><p class="request_employer_post"></p></div>
				<div class="iline w200"><span>Телефон:</span><p class="request_employer_phone"></p></div>
				<div class="iline w200"><span>E-Mail:</span><p class="request_employer_email"></p></div>
				<br>
				
				<h1>Будет заблокирован следующий функционал:</h1>
				<div id="objects_lock_table" class="table_full_area"></div><br>

			</div></div>
			<div class="buttonarea">
				<div class="ui-button" id="button_ir_locker_complete"><span>Блокировать доступ</span></div>
				<div class="ui-button" id="button_ir_locker_cancel"><span>Закрыть</span></div>
			</div>
		</div>
	</div>



	<div class="bigblock" id="ir_locker_success" style="display:none;">
		<div class="titlebar"><h3>Заявка на блокировку доступа создана успешно</h3></div>
		<div class="contentwrapper">
			<div class="contentarea" id="ir_locker_success_wrapper"><div class="w900c">

				<h1><font color="green">Заявка на блокировку доступа создана успешно</font></h1>
				<h2>Отслеживать статус исполнения заявки Вы можете через раздел &laquo;Заявки с моим участием&raquo; в меню &laquo;История&raquo;</h2>
				<br>
				<div class="splitline"></div>
				<h1>Информация по заявке:</h1>
				<div class="fline w200"><span>Номер заявки:</span><p class="success_request_id"></p></div>
				<div class="fline w200"><span>Тип заявки:</span><p style="color:red;">Блокировка доступа</p></div>
				<div class="fline w200"><span>Информационный ресурс:</span><p class="request_iresource_name"></p></div>
				<div class="fline w200"><span>ФИО Сотрудника:</span><p class="request_employer_name"></p></div>
				<div class="fline w200"><span>Организация сотрудника:</span><p class="request_employer_company"></p></div>
				<div class="fline w200"><span>Должность сотрудника:</span><p class="request_employer_post"></p></div>
				<div class="fline w200"><span>Телефон:</span><p class="request_employer_phone"></p></div>
				<div class="fline w200"><span>E-Mail:</span><p class="request_employer_email"></p></div>
				<br>
				<div class="splitline"></div>
				<h2>
					В случае возникновения необходимости отмены заявки на блокировку, самостоятельно ее отменить Вы не можете, для отмены заявки обратитесь к администратору системы.
				</h2>

			</div></div>
			<div class="buttonarea">
				<div class="ui-button" id="button_ir_locker_success_cancel"><span>Закрыть</span></div>
			</div>
		</div>
	</div>


</div>