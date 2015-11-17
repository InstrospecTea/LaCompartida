<?php
	include ('codebase/connector/scheduler_connector.php');
	include ('samples/common/config.php');
	
	$res=mysql_connect($server, $user, $pass);
	mysql_select_db($db_name);
	
	$scheduler = new schedulerConnector($res);
	//$scheduler->enable_log("log.txt",true);
	$scheduler->render_table("tevents","event_id","start_date,end_date,event_name,type");
?>