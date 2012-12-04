<?php
if (extension_loaded('newrelic')) {
		newrelic_set_appname ('app6');
	   }
 ini_set('display_errors','Off'); 
 date_default_timezone_set('America/Santiago');
$nowtime = time();
header('Content-Type: text/javascript; charset=utf8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Max-Age: 3628800');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
$urlo = parse_url($_SERVER['HTTP_REFERER']);
if (isset($_REQUEST['clavicula'])) {
	include('sdb.php');
	$tabla = 'clientes';
	$simpledb = new SimpleDB();
	$simpledb->setAuth('AKIAIQYFL5PYVQKORTBA', 'q5dgekDyR9DgGVX7/Zp0OhgrMjiI0KgQMAWRNZwn');

	if ((!empty($urlo['host']) && $dominio = $urlo['host']) || $dominio = base64_decode($_POST['from']) ) {
		$stamp = $nowtime + 864000;


		file_put_contents('v/' . $dominio . '.inf', date('Y-m-d H:i:s'));
		$dato = $simpledb->getAttributes($tabla, $dominio);
		if (isset($dato['restringido'])) {
			if ($dato['restringido'] == 1) {
				$restringido = '1';
				$stamp = $nowtime + 43200;
				$simpledb->putAttributes($tabla, $dominio, 
				array('restringido' =>  array('value' => '0', 'replace' => 'true'), 
					'lastlogin' => array('value' => $nowtime, 'replace' => 'true') , 
					'validuntil' => array('value' => $stamp, 'replace' => 'true')
					)
				);
			} else {
				$stamp = $nowtime + 864000;
				$restringido = '0';
			}
		} else {

			$stamp = $nowtime + 864000;
			$restringido = '0';
		}
		$strestringido = ($restringido ? 'restringido' : 'no restringido');

		$dato['strestringido'] = $strestringido;
	//	mail('ffigueroa@lemontech.cl', $_SERVER['SERVER_NAME'], $dominio . ' ' . json_encode($dato));
		$myFile = "logfile.log";
		$fh = fopen($myFile, 'a');
		fwrite($fh, "\n" . date('Y-m-d H:i:s') . ' ' . $dominio . ' ' . $stamp . ' ' . date('Y-m-d H:i', $stamp) . $strestringido);
		fclose($fh);
		$simpledb->putAttributes($tabla, $dominio, 
				array('restringido' =>  array('value' => $restringido, 'replace' => 'true'), 
					'lastlogin' => array('value' => $nowtime, 'replace' => 'true') , 
					'validuntil' => array('value' => $stamp, 'replace' => 'true')
					)
				);
		$stamper = base64_encode($stamp);


		echo "jQuery.post('../ajax.php',{accion:'actualiza_beacon', beaconleft:'$stamper' },function(insert) {		console.log(insert)	});";
	}
}
?>
