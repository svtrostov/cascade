<?php
/*==================================================================================================
Описание : Language settings
Модуль   : Main
Категория: User
Язык     : English
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');


return array(
	
	'auth'=>array(
	
		'title'		=> 'Login page',
		'form_title'=> 'Please enter below your\'s username and password',

		'username'	=> 'Username',
		'password'	=> 'Password',
		'remember'	=> 'Remember me',
		'submit'	=> 'Login',

		'errors'=>array(
			'no_username' 		=> 'Please enter your Username and password',
			'incorrect_login' 	=> 'Incorrect username or password',
			'account_locked'	=> 'Your account has been locked',
			'account_locked_to'	=> 'Your account has been locked to '
		)
	
	)


);#end lang

?>