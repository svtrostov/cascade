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
#outlook a{padding:0;}
body{width:100% !important;-webkit-text-size-adjust:none;background-color:#FAFAFA;margin:0;padding:0;font-family:Tahoma,Verdana,Arial,sans-serif;font-size:12px;line-height:16px;}
.wrapper{position:relative;max-width:1200px !important;margin:10px;background-color:#FFFFFF;border:1px solid #CCCCCC;}
.content{margin:10px;padding:10px;}
.title{display:block;margin:20px 0px;background-color:#E2E2E2;font-family:Georgia,"Times New Roman",Times,serif;border-top:1px solid #B3AE98;border-bottom:1px solid #B3AE98;}
.title div{margin:10px;padding:10px;}
.title h1{display:block;font-size:36px;line-height:36px;font-weight:bold;text-align:left;margin:0px;}
.title span{display:block;font-size:11px;font-weight:normal;text-align:left;margin:0px;}
h2{display:block;font-size:16px;font-weight:bold;text-align:left;margin:40px 0px 0px 0px;padding:10px 0px;}
//h4{display:block;font-size:12px;font-weight:bold;text-align:left;background:#E8E7DF;margin:0px;padding:5px;}
//h5{color:#666;display:block;font-size:12px;font-weight:bold;text-align:left;margin:0px;padding:5px;}
//table,td,th{border-collapse:collapse;border:1px solid #B3AE98;text-align:left;vertical-align:top;}
table{width:100%;cursor:default;}
td{margin:0;padding:0;}
tr.approve{background-color:#F1FCEB;}
tr.decline{background-color:#FCEBEB;}
tr.normal{background-color:#FFF;}
th{padding:10px 10px;margin:0;text-align:center;font-weight:bold;background-color:#CCC8B3;}
a:link,a:visited,a .yshortcuts{color:#336699;font-weight:normal;text-decoration:underline;}
a:hover{color:#0060FF;}
.c{text-align:center;}
.np{padding:0;vertical-align:top;}
.b{font-weight:bold;}
.s{font-size:10px;color:#666;}
.db{display:block;}
.m50{margin:5px 0px;}
ul{list-style:none;width:100%;margin:0;padding:0px;}
li{padding:5px;}
li:hover{background:#eaeaea;}
.block{margin:10px 5px;}


.lw100{display: block;min-width:120px;}
.lw100 span{float:left;display:block;margin-right:10px;font-weight:bold;color:#505b6b;width:120px;}
.lw100 div{display:block;margin-left:130px;}
.line{display:block;margin:5px 0px;border-top:1px solid #B3AE98;}
p{font-size:16px;text-align:left;}
p.info{font-size:12px;text-align:left;}
a.button{
	color: #FFFFFF;
	background-color:#336699;
	-webkit-border-radius:5px;
	-moz-border-radius:5px;
	border-radius:5px;
	text-decoration:none;
	padding: 3px 5px;
	margin:5px 0px;
	width:auto;
}
a.button:hover{
	background-color:#225577;
}


h4,.h4{font-size:1em;font-weight:bold;text-align:left;background-color:#E8E7DF;padding:5px;margin:0px;}
h5,.h5{font-size:1em;font-weight:bold;text-align:left;padding:5px;margin:0px;}
.ilist{padding:5px;}
.k{width:120px;}
.v{}
.name{font-weight:bold;color:#666;}
.phone,.email{font-size:-0.2em;color:#666;}
.approve{background-color:#F1FCEB;}
.decline{background-color:#FCEBEB;}
.normal{background-color:#FFFFFF;}
</style>
</head>
<body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0"><center><div class="wrapper">

<div style="margin:10px;color:red;">Это письмо сгененировано автоматически и не требует ответа.</div>

<div class="title">
	<div>
		<h1>&laquo;Каскад&raquo;</h1>
		<span>Система менеджмента заявок</span>
	</div>
</div>

<div class="content">

<p class="hello">Уважаемый(ая) {%EMPLOYER_NAME%},<br/><br/>
поступили новые заявки, к процессу согласования которых Вы имеете отношение:</p>



{%CONTENT%}



<br/>
<div class="line"></div>

<p class="info">Вы получили это письмо, поскольку участвуете в процессе согласования заявок на доступ к корпоративным информационным ресурсам.<br/>
Если Вы не участвуете в процессе согласования или не знаете что делать, свяжитесь с администратором системы.</p>

</div></div></center></body>
</html>