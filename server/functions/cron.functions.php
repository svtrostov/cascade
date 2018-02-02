<?php
/*
======================================================================================
	����: cron.functions.php
	��������: 
	������� ��� ������ ��������� �����
	����������� (�) 2011 svtretyakov
======================================================================================
*/

#�������� ����������� ������� �������
if (!defined('APP_INSIDE')) exit('No direct script access allowed in '.basename(__FILE__));


#------------------------------------------------------------------
#������� ���������, ������� �� �������
function cron_proc_exists($script='', $max=1){
	$out = shell_exec('ps ax|grep '.$script.'');
	if(substr_count($out,$script) > $max*2) return true;
	return false;
}#end function



#------------------------------------------------------------------
#������� ���������� PID �������� ��� ����, ���� ������� �� �������
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
#������� �������� ������� � ��������� PID
function cron_proc_kill($pid=0){
	if($pid == 0) return false;
	$cmd = 'kill -s KILL '.$pid;
	exec($cmd, $output, $rv);
	return true;
}

?>