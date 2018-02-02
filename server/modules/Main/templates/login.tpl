<html>
	<head>
		<title>{%LANG::Main/user,auth/title%}</title>
		<meta http-equiv="X-UA-Compatible" content="IE=9"/>
		<meta charset="UTF-8">
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
					<input type="hidden" name="logintype" value="login" />
					<div class="username_field"><input type="text" name="username" placeholder="{%LANG::Main/user,auth/username%}" class="required" value="" /></div>
					<div class="password_field"><input type="password" name="password" placeholder="{%LANG::Main/user,auth/password%}" class="required" value="" /></div>
					<div class="buttonline">
						<input type="submit" class="loginbutton" value="{%LANG::Main/user,auth/submit%}"/>
						<label for="remember">{%LANG::Main/user,auth/remember%} <input type="checkbox" name="remember" value="1"/></label>
					</div>
				</form>
			</div>
			
		
		</div>

	</body>	

</html>
