<?php
/*
======================================================================================
	Файл: cron.functions.php
	Описание: 
	Функции для работы менеджера задач
	Разработано (с) 2011 svtretyakov
======================================================================================
*/

#Проверка корректного запуска скрипта
if (!defined('APP_INSIDE')) exit('No direct script access allowed in '.basename(__FILE__));


#------------------------------------------------------------------
#Функция проверяет, запущен ли процесс
function cron_proc_exists($script='', $max=1){
	$out = shell_exec('ps ax|grep '.$script.'');
	if(substr_count($out,$script) > $max*2) return true;
	return false;
}#end function



#------------------------------------------------------------------
#Функция возвращает PID процесса или ноль, если процесс не запущен
function cron_proc_pid($script=''){
	$cmd = "ps x";
	exec($cmd, $output, $rv);
	while ($element = each($output)){
		if(strpos($element[1],$script)!==false){
			$element[1] = trim($element[1]);
			$pid_array = explode(" ", $element[1]);
			$pid = array_shift($pid_array);
			if(isset($pid)) return $pid;
		}
	}
	return 0;
}#end function



#------------------------------------------------------------------
#Функция вубивает процесс с указанным PID
function cron_proc_kill($pid=0){
	if($pid == 0) return false;
	$cmd = 'kill -s KILL '.$pid;
	exec($cmd, $output, $rv);
	return true;
}

?>