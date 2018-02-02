<html>
	<head>
		<title>{%LANG::Main/user,auth/title%}</title>
		<meta http-equiv="X-UA-Compatible" content="IE=9"/>
		<link rel="stylesheet" type="text/css" href="/client/themes/{%USER_THEME%}/css/ui-reset.css"/>
		<link rel="stylesheet" type="text/css" href="/client/themes/{%USER_THEME%}/css/ui-login.css"/>
		<script type="text/javascript">
			var COOKIE_PREFIX = "{%SESSION_COOKIE%}";
			var INTERFACE_LANG = "{%USER_LANGUAGE%}";
			var INTERFACE_THEME = "{%USER_THEME%}";
			var INTERFACE_MODULE = "{%REQUEST_MODULE%}";
			var APP_AUTOCREATE_DISABLE = true;
		</script>
		
	</head>
	<body>
	
		<div class="login_area">
			<div class="logo">{%LANG::general,project_name_full%}</div>
			{%error%}
			<div class="loginbox">
				<form action="/main/login" method="post">
					<input type="hidden" name="page" value="{%GET::page,1%}" />
					<div class="password_field"><input type="password" name="pin" placeholder="{%LANG::Main/user,auth/pin%}" class="required" value="" /></div>
					<div class="buttonline">
						<input type="submit" class="loginbutton" value="{%LANG::Main/user,auth/submit%}"/>
						<input type="button" style="float:right;" class="loginbutton" value="Без сертификата" onclick="window.location.href='/main/login?logintype=login';"/>
					</div>
				</form>
			</div>
			<br/>
			<div class="loginbox">
				<b>{%LANG::Main/user,auth/cert_info%}:</b><br/>
				{%LANG::Main/user,auth/cert_o%}: {%SSL_CLIENT_S_DN_O%}<br/>
				{%LANG::Main/user,auth/cert_cn%}: {%SSL_CLIENT_S_DN_CN%}
			</div>
		</div>

	</body>	

</html>
