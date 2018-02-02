<div class="page_matrix_employers" id="page_matrix_employers">

	<div class="bigblock" id="bigblock_wrapper">
		<div class="titlebar normal">
			<a class="expander" href="#" id="bigblock_expander"></a>
			<h3 id="bigblock_title">Матрица доступа: по сотрудникам</h3>
		</div>
		<div class="contentwrapper">
			<div class="contentareafull">


				<div id="request_area">

					<div class="navigationarea">
							<input type="text" style="width:260px;" id="employer_term" value="" placeholder="Введите фамилию сотрудника..."/>
							<input type="button" style="width:60px;" id="employer_term_button" value="Поиск"/>
					</div>

					<div class="centralarea" id="centralarea_container">

						<div id="employers_area" class="sleft_panel">
								<div id="employers_table" class="table_area"></div>
								<div id="employers_none">Сотрудники не найдены</div>
						</div>
						<div id="iresources_area" class="sright_panel">

							<div id="ir_none"><h1 class="errorpage_title">Пусто</h1></div>
							<div id="ir_area" style="display:none;">

								<div class="tool_area">
									<div class="left">
										<select style="width:350px;" id="post_selector"></select>
									</div>
									<div id="objects_tool_lock" class="left">
										<div class="ui-button-light" id="object_lock_button" style="margin:0px;"><span class="ileft icon_lock">Блокировать</span></div>
									</div>
									<div class="right" id="objects_filter_area">
										<div class="iline wauto"><span>Фильтр:</span><input type="text" style="width:100px;" id="objects_filter"/></div>
									</div>
								</div>

								<div id="ir_list_wrapper"><ul id="ir_list" class="blocklist"></ul><div id="ir_list_none" style="display:none;">В рамках указанной должности сотруднику не были назначены права доступа</div></div>
							</div>

						</div>
						<div id="employers_splitter" class="small_splitter"></div>

					</div>

				</div>

			</div>
		</div>
	</div>

</div>