<?php
/***********************************************************************
 * ШАБЛОНЫ ПИСЕМ: 
 * Шаблон сообщения, посылаемого гейткиперу, 
 * для согласования заявки
 * ---------------------------------------------------------------------
 * 
 * Используемые подстановки:
 * {%MAIL_SUBJECT%} - тема письма
 * {%EMPLOYER_NAME%} - ФИО получателя письма
 * {%CONTENT%} - Основной контент письма
 * {%REQUEST_IRESOURCE%} - Название информационного ресурса, к которому запрошен доступ
 * {%LINK%} - URL адрес ссылки для перехода в соответствующий раздел интерфейса
 * 
 **********************************************************************/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Система заявок</title>
<style type="text/css">
/* Client-specific Styles */
#outlook a{padding:0;} /* Force Outlook to provide a "view in browser" button. */
body{width:100% !important;} .ReadMsgBody{width:100%;} .ExternalClass{width:100%;} /* Force Hotmail to display emails at full width */
body{-webkit-text-size-adjust:none;} /* Prevent Webkit platforms from changing default text sizes. */
/* Reset Styles */
body{margin:0; padding:0;}
img{border:0; height:auto; line-height:100%; outline:none; text-decoration:none;}
td,th{border-collapse:collapse;font-family:Arial;font-size:12px;}
th{padding:10px 10px;margin:0;text-align:center;font-weight:bold;background-color:#CCC8B3;}
p{font-size:1.1em;}
h1,.h1,h2,.h2{color:#202020;display:block;font-size:20px;font-weight:bold;line-height:100%;padding-top:20px;text-align:left;}
h4,.h4{font-size:12px;font-weight:bold;text-align:left;background-color:#E8E7DF;padding:5px;margin:0px;}
h5,.h5{font-size:12px;font-weight:bold;text-align:left;padding:5px;margin:0px;}
ul{list-style:none;width:100%;margin:0;padding:0px;}
li{padding:5px;}
li:hover{background:#eaeaea;}
#wrapper{height:100% !important; margin:0; padding:0; width:100% !important;padding:20px;}

/*common page elements*/
body, #wrapper{background-color:#FAFAFA;}
#container{border: 1px solid #CCCCCC;max-width:1200px;background-color:#FFFFFF;}
#warning{color: red;}
#header{background-color:#E2E2E2;border-top: 1px solid #B3AE98;border-bottom: 1px solid #B3AE98;padding:10px;}
.header_bg{background-color:#E2E2E2;}
#header_title{font-size:32px;font-weight:bold;font-family:Georgia,"Times New Roman",Times,serif;}
#header_desc{font-size:11px;font-family:Georgia,"Times New Roman",Times,serif;}
#content{padding:10px;}
.ilist{padding:5px;}
.k{width:120px;}
.v{}
.name{font-weight:bold;color:#666;}
.phone,.email{font-size:-0.2em;color:#666;}
.approve{background-color:#F1FCEB;}
.decline{background-color:#FCEBEB;}
.normal{background-color:#FFFFFF;}
a.button{color:#FFFFFF;background-color:#336699;-webkit-border-radius:5px;-moz-border-radius:5px;border-radius:5px;text-decoration:none;line-height:20px;padding:3px 5px;margin:5px 0px;width:auto;}
a.button:hover{background-color:#225577;}
</style>
</head>

<body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0"><center>
<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="wrapper"><tr><td align="center" valign="top">

	<table border="0" cellpadding="0" cellspacing="0" width="100%" id="container"><tr><td align="center" valign="top">

		<table border="0" cellpadding="0" cellspacing="0" width="100%">
			<tr><td width="100%" height="30" align="center" valign="middle" id="warning">Это письмо сгененировано автоматически и не требует ответа.</td></tr>
		</table>

		<table border="0" cellpadding="0" cellspacing="0" width="100%" id="header">
			<tr><td width="100%" height="60" align="left" valign="middle">
				<table border="0" cellpadding="0" cellspacing="0" width="100%" class="header_bg">
					<tr><td width="50%" align="left" valign="bottom" id="header_title" class="header_bg">&laquo;Каскад&raquo;</td>
					<td width="50%" align="right" valign="middle" rowspan="2" style="padding:5px;" class="header_bg">
						<a class="button" href="<?=Config::getOption('general','server_address','#');?>" style="padding:10px;line-height:30px;">Вход в систему</a>
					</td></tr>
					<tr><td width="50%" align="left" valign="top" id="header_desc" class="header_bg">&nbsp;</td></tr>
				</table>
			</td></tr>
		</table>

		<table border="0" cellpadding="0" cellspacing="0" width="100%" id="content">
			<tr><td>&nbsp;</td></tr>
			<tr><td width="100%" align="left" valign="top">
				<p>Уважаемый(ая) {%EMPLOYER_NAME%},<br/>поступили новые заявки, к процессу согласования которых Вы имеете отношение:</p>
			</td></tr>
			<tr><td width="100%" align="left" valign="top">
				{%CONTENT%}
			</td></tr>
		</table>

	</td></tr></table>

</td></tr></table>

</center></body>
</html>