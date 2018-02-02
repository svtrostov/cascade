<?php
/***********************************************************************
 * ШАБЛОНЫ ПИСЕМ: 
 * Шаблон сообщения, посылаемого гейткиперу, 
 * для согласования заявки
 * ---------------------------------------------------------------------
 * 
 * Используемые подстановки:
 * {%MAIL_SUBJECT%} - тема письма
 * {%GATEKEEPER_NAME%} - ФИО гейткипера, получателя письма
 * {%REQUEST_ID%} - Идентификатор заявки
 * {%EMPLOYER_NAME%} - ФИО заявителя
 * {%EMPLOYER_COMPANY%} - Организация, в которой работает заявитель
 * {%EMPLOYER_POST%} - Должность заявителя
 * {%REQUEST_IRESOURCE%} - Название информационного ресурса, к которому запрошен доступ
 * {%LINK%} - URL адрес ссылки для перехода в соответствующий раздел интерфейса
 * 
 **********************************************************************/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<title>{%MAIL_SUBJECT%}</title>
		<style type="text/css">
			#outlook a{padding:0;}
			body{
				width:100% !important;
				-webkit-text-size-adjust:none;
				background-color:#FAFAFA;
				margin:0;
				padding:0;
			}
			a:link, 
			a:visited,
			a .yshortcuts{
				color:#336699;
				font-weight:normal;
				text-decoration:underline;
			}
			h1{
				font-size: 16px;
			}
			p.info{
				color: #505b6b;
				text-align: justify;
			}
			.wrapper{
				position: relative;
				width: 650px;
				margin: 10px auto;
				padding: 10px;
				background-color:#FFFFFF;
				border: 1px solid #CCCCCC;
			}
			.header{
				padding: 5px;
				color: #FFFFFF;
				font-size: 26px;
				font-style: normal;
				text-shadow: 1px 1px 2px black, 0 0 1em #CCCCCC;
				border-bottom: 1px solid #363d47;
				background-color:#336699;
				-webkit-border-radius:5px;
				-moz-border-radius:5px;
				border-radius:5px;
			}
			.content{
				padding: 5px;
			}
			.footer{
				border-top: 1px solid #363d47;
				padding:5px;
				font-size: 12px;
				color: #363d47;
				text-align:right;
			}
			.block{
				padding: 5px;
				border: 1px solid #999999;
			}
			.block > .iline:first-child{
				border-top: 1px dotted #FFFFFF;
			}
			.iline{
				display: block;
				line-height: 24px;
				color: #2b323b;
				font-weight: bold;
				min-width: 100px;
				font-size: 14px;
				cursor: default;
				border-top: 1px dotted #CCCCCC;
			}
			.iline span{
				float:left;
				display: block;
				margin-right: 10px;
				font-weight: bold;
				color: #505b6b;
				width: 200px;
			}
			.iline:hover{
				background-color:#FCFBF4;
			}
			.iline:hover span{
				color: #2b323b;
			}
			.iline div{
				display: block;
				margin-left: 210px;
			}
			a.button{
				color: #FFFFFF;
				background-color:#336699;
				-webkit-border-radius:5px;
				-moz-border-radius:5px;
				border-radius:5px;
				text-decoration:none;
				padding: 5px 10px;
			}
			a.button:hover{
				background-color:#225577;
			}
		</style>
	</head>
	<body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0"><div class="wrapper">

		<div class="header">Система заявок</div>
		<div class="content">
			<h1>Уважаемый(ая) {%GATEKEEPER_NAME%},<br/>
			Вам поступила новая заявка для согласования</h1>
			<br/>
			<div class="block">
				<div class="iline"><span>Номер заявки:</span><div>{%REQUEST_ID%}</div></div>
				<div class="iline"><span>Заявитель:</span><div>{%EMPLOYER_NAME%}</div></div>
				<div class="iline"><span>Работает в организации:</span><div>{%EMPLOYER_COMPANY%}</div></div>
				<div class="iline"><span>Занимает должность:</span><div>{%EMPLOYER_POST%}</div></div>
				<div class="iline"><span>Просит доступ к ресурсу:</span><div>{%REQUEST_IRESOURCE%}</div></div>
			</div>
			<br/><br/>
			<center>
			<a class="button" href="{%LINK%}" target="_blank">Нажмите здесь чтобы перейти к заявке</a>
			</center>
			<br/>
			<p class="info">Вы получили это письмо, поскольку участвуете в процессе согласования заявок на доступ к корпоративным информационным ресурсам.<br/>
			Если Вы не участвуете в процессе согласования или не знаете что делать, свяжитесь с администратором системы.</p>
		</div>
		<div class="footer">
		Разработано &copy; 2013 &laquo;Exsul technologies&raquo;
		</div>

	</div></body>
</html>