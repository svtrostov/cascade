<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru" dir="ltr">
	<head>
		<title>{%LANG::general,project_name_full%}</title>
		<meta http-equiv="X-UA-Compatible" content="IE=9"/>
		<meta charset="UTF-8">
		<meta http-equiv="Content-Type" content="text; charset=utf-8" />
		<link rel="stylesheet" type="text/css" href="/client/themes/{%USER_THEME%}/css/ui-reset.css"/>
		<link rel="stylesheet" type="text/css" href="/client/themes/{%USER_THEME%}/css/ui-index.css"/>
		<script type="text/javascript">
			var COOKIE_PREFIX = "{%SESSION_COOKIE%}";
			var INTERFACE_LANG = "{%USER_LANGUAGE%}";
			var INTERFACE_THEME = "{%USER_THEME%}";
			var INTERFACE_IMAGES = "/client/themes/{%USER_THEME%}/images";
			var INTERFACE_CSS = "/client/themes/{%USER_THEME%}/css";
			var INTERFACE_MODULE = "{%REQUEST_MODULE%}";
			var REQUEST_INFO = {%REQUEST_INFO%};
		</script>
		<script type="text/javascript" src="/client/js/lib/__core.js"></script>
		<script type="text/javascript" src="/client/js/lib/__more.js"></script>
		<script type="text/javascript" src="/client/js/lib/__utils.js"></script>
		<script type="text/javascript" src="/client/js/lib/__app.js"></script>
		<script type="text/javascript" src="/client/js/lib/axRequest.class.js"></script>
		<script type="text/javascript" src="/client/js/lib/jsSlideShow.class.js"></script>
		<script type="text/javascript" src="/client/js/lib/jsMessage.class.js"></script>
		<script type="text/javascript" src="/client/js/lib/jsList.class.js"></script>
		<script type="text/javascript" src="/client/js/lib/jsTable.class.js"></script>
		<script type="text/javascript" src="/client/js/lib/jsPicker.class.js"></script>
		<script type="text/javascript" src="/client/js/lib/jsValidator.class.js"></script>
		<script type="text/javascript" src="/client/js/lib/jsSlimbox.class.js"></script>
		<script type="text/javascript" src="/client/js/lib/jsTabPanel.class.js"></script>
		<script type="text/javascript" src="/client/js/Main/stackController.js"></script>
		<script type="text/javascript" src="/client/js/Main/jsMainMenu.class.js"></script>

	</head>
	<body>

		<div id="user_interactive_editor"></div>

		<div id="spinner">
			<div>
				<img src="/client/themes/{%USER_THEME%}/images/spinner_big.gif"/><br/>
				<span class="logotext">идет загрузка...</span>
			</div>
		</div>
		
		<div class="userbar"><!--userbar begin-->
		
			<div class="profile">
				<div class="avatar">
					<div class="img"></div>
					<a href="/main/requests/view" class="unreadcount" id="unreadcount">0</a>
				</div>
				<div class="profileinfo">
					<h3 class="username">{%USER::search_name%}</h3>
					<span class="ip_addr">{%REQUEST::ip_addr%}</span>
					<div class="clear"></div>
					<a href="/main/profile" class="profilebutton">Профиль</a>
					<a href="/main/accesslog" class="profilebutton">История входов</a>
					<a href="/logout" class="profilebutton">Выход</a>
				</div>
			</div>

			<div id="navigation_area"></div>

		</div><!--userbar end-->
	
	
		<div id="mainarea"></div>
		
		
	</body>
</html>
