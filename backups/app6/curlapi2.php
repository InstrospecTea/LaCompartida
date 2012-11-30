<?php
if (extension_loaded('newrelic')) {
		newrelic_set_appname ('app6');
	   }
header('Access-Control-Allow-Origin: *');
echo fffcurl('http://api.chartbeat.com/live/quickstats/?host=thetimebilling.com&apikey=5a007a9f295fec790260c1b6a1f3dbe1');

function fffcurl($url) {
	$ch = curl_init($url);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch,CURLOPT_HEADER, 0);	
	curl_setopt($ch,CURLOPT_USERAGENT,'Googlebot/2.1');
	curl_setopt($ch,CURLOPT_TIMEOUT,10);	
	$data = curl_exec($ch);
	curl_close($ch);
	return $data;
}

?>