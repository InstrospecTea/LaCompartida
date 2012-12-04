<?php
require_once '/var/www/html/addbd.php';

if(defined('BACKUP')&& (BACKUP==3 ||BACKUP=='3')) {
	include('offline.php');
die();
}
if(defined('FILEPATH')) {
	setcookie('vhost', FILEPATH, time()+60*60*4,ROOTDIR);

	include(APPPATH.'/index.php');
	 } else {
	 
		 include('offline.php');
		die();
	 }
