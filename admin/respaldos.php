<?php
require_once dirname(__FILE__) . '/../app/conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';

 

function autocargaapp($class_name) {
	if (file_exists(Conf::ServerDir() . '/classes/' . $class_name . '.php')) {
		require Conf::ServerDir() . '/classes/' . $class_name . '.php';
	} else if (file_exists(Conf::ServerDir() . '/../fw/classes/' . $class_name . '.php')) {
		require Conf::ServerDir() . '/../fw/classes/' . $class_name . '.php';
	}
}

spl_autoload_register('autocargaapp');	

$S3 = new AmazonS3(array('key' => 'AKIAIQYFL5PYVQKORTBA',
			'secret' => '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'
			, 'default_cache_config' => '/var/www/virtual/cache/'));


$sesion = new Sesion(array('ADM'));


if (!defined('SUBDOMAIN')) {
	die('Error: contacte a soporte para obtener su dirección de subdominio');
} else {
	$bucketName = 'ttbackup' . SUBDOMAIN;
}

	if ($_POST['filename']) {
		$filename = $_POST['filename'];
		$curl_url = $S3->get_object_url($bucketName, $filename, "+12 hours", array('https' => true, 'returnCurlHandle' => true));
		$ch = curl_init($curl_url);
		header('Content-type: application/octet-stream');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		$data = curl_exec($ch);
		curl_close($ch);
		die();
	} else if ($_POST['dropname']) {
		$sesion->phpConsole();
		$dropname = $_POST['dropname'];
		$consumerKey = '5jys56prote7pyq';
		$consumerSecret = 'dmv6lidqcm039wc';
		require_once Conf::ServerDir() . '/classes/Dropbox.php';
		
	try {
			
			$path_parts = pathinfo($path);
			$mensajedr = '<div class="alert alert-success">Busque el archivo <b>' . $dropname. '</b> dentro de unos minutos en su carpeta dropbox <i>/Apps/TheTimeBilling/</i></div>';

			$fp = fopen('php://temp', 'rw');
			$curl_url = $S3->get_object_url($bucketName, $dropname, "2 hours", array('https' => true, 'returnCurlHandle' => true));
			$ch = curl_init($curl_url);

			
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION , 0);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_BUFFERSIZE, 256);
			
			curl_setopt($ch, CURLOPT_FILE, $fp);    // Data will be sent to our stream ;-)

			curl_exec($ch);
			$put = $dropbox->putStream($fp, $dropname);
			
			curl_close($ch);

		// Don't forget to close the "file" / stream
			fclose($fp);
			
			 
		} catch (Exception $e) {
			debug($e->getTraceAsString(), 'Exception!');
		}
	}
	ini_set('display_errors', 'Off');
	$pagina = new Pagina($sesion);



	$pagina->titulo = __('Descarga de Respaldos');
	$pagina->PrintTop();

	if (!defined('BACKUPDIR'))
		die('Consulte con soporte para acceder a sus respaldos mediante esta pantalla');
	echo $mensajedr;
	?>

	if (!defined('BACKUPDIR'))
		die('Consulte con soporte para acceder a sus respaldos mediante esta pantalla');
	echo $mensajedr;
	?>

	<br>	Estos son los respaldos disponibles para su sistema. Los enlaces de descarga sólo serán válidos  por dos horas<br><br>
	<?php
echo '<script src="//static.thetimebilling.com/js/bootstrap.min.js"></script>';
echo '<link rel="stylesheet" href="//static.thetimebilling.com/css/bootstrap-combined.min.css" />';
	echo '<form id="form_respaldo" method="post"><input type="hidden" id="dropname"/></form>';
	


	 echo '<div class="container-fluid">  
		 <div class="row-fluid"> ';
		  
	echo '<table width="750px"  class="table  table-hover table-bordered table-striped">';
	echo "<thead><tr>
		<th>Archivo</th>
		
		<th>Tama&ntilde;o</th>
		<th>Fecha Modificaci&oacute;n</th>
		<th style='width: 90px;'>Torrent</th>
		<th style='width: 45px;'>Dropbox</th> 
		</tr></thead>\n<tbody>";



	if (($bucket = $S3->list_objects($bucketName)) !== false) {

		foreach ($bucket->body as $object) {
			 
			if ($object->Size >= 20000) {

				$dropname = $object->Key;
				$torrent = $S3->get_object_url($bucketName, $dropname, "+2 days", array('torrent' => true));
				echo "<tr><td><a class='iconzip' rel='$dropname' style='  float:left;font-size:14px;' href=\"javascript::void();\">$dropname</a> &nbsp;  &nbsp;&nbsp; </td>";
				echo "<td>";
				echo round($object->Size / (1024 * 1024), 2) . ' MB';
				echo "</td>";
				echo '<td>' . date('d-m-Y', strtotime($object->LastModified)) . '</td>';
				echo "<td><a setwidth='60' class='btn botonizame' icon='ui-icon-torrent' href='$torrent'>torrent</a></td>";
				echo "<td><a   class='dropbox' rel='$dropname'   href=\"javascript::void();\"><img src='https://static.thetimebilling.com/cartas/img/dropbox_ico.png'/></a></td>";
				echo "</tr>\n";
			}
		}
	
}



echo "</tbody></table>\n";
echo '</div>  </div>';
?>
<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('.iconzip').click(function() {
			jQuery('#dropname').attr('name','filename').val(jQuery(this).attr('rel'));
			console.log(jQuery('#form_respaldo'));
			jQuery('#form_respaldo').submit();
		});
		jQuery('.dropbox').click(function() {
			jQuery('#dropname').attr('name','dropname').val(jQuery(this).attr('rel'));
			console.log(jQuery('#form_respaldo'));
			jQuery('#form_respaldo').submit();
		});
	});
</script>
<?php
$pagina->PrintBottom();

function readCallback($curl, $stream, $maxRead) {
	// read the data from the ftp stream
	$read = fgets($stream, $maxRead);

	// echo the contents just read to the client which contributes to their total download
	echo $read;

	// return the read data so the function continues to operate
	return $read;
}
 
