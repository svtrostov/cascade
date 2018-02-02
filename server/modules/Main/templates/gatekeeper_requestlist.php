<div class="bigblock">
	<div class="titlebar"><h3 id="requestlist_title">Заявки на рассмотрении</h3></div>
	<div class="contentwrapper" id="requestlist_wrapper" style="display:none;">
		<div class="contentarea" id="requestlist_area">
			<div class="w700c">
				<h1>Выберите заявку</h1>
				<h2>Ниже представлен список заявок, с которыми с настоящий момент Вы можете осуществлять какие-либо действия (например, согласовать заявку, утвердить заявку и т.д.).<br/>
					Для начала выберите интересуемую заявку из списка ниже, потом нажмите на кнопку &laquo;Заявка выбрана&raquo;</h2>
				<ul class="requestlist" id="requestlist"></ul>
			</div>
		</div>
		<div class="buttonarea">
			<div id="button_request_selected_none" style="margin-top:10px;">Для продолжения, выберите заявку...</div>
			<div class="ui-button" id="button_request_selected" onclick="gatekeeper_requestlist_select_complete();"><span>Заявка выбрана</span></div>
		</div>
	</div>

</div>

<div id="requestlist_none" style="display:none;">
	<h1 class="errorpage_title">Пока нет заявок</h1>
	<h2 class="errorpage_subtitle">В настоящий момент у Вас нет заявок для рассмотрения</h2>
</div>
