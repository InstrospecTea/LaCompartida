<?php

require_once dirname(__FILE__) . '/../conf.php';

set_time_limit(180);
$sesion = new Sesion(null, true);
$sesion->phpConsole();
$alerta = new Alerta($sesion);
$encolados = array();
$enviados = 0;


$query = "SELECT id_log_correo, subject, mensaje, mail, nombre, id_archivo_anexo FROM log_correo WHERE enviado=0 limit 0,60";
$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

while (list($id, $subject, $mensaje, $mail, $nombre, $id_archivo_anexo ) = mysql_fetch_array($resp)) {
	$correos = array();
	$adresses = explode(',', $mail);

	foreach ($adresses as $adress) {
		$correo = array('nombre' => $nombre, 'mail' => trim($adress));

		if (UtilesApp::isValidEmail($adress)) {
			array_push($correos, $correo);
		} else {
			error_log('Se intenta enviar correo no valido ' . $adress . ' (usuario ' . $nombre . ')');
		}
	}

	$encolados[] = array(json_encode($adresses), $subject);
	if (count($correos) > 0) {

		if (Utiles::EnviarMail($sesion, $correos, $subject, $mensaje, true, $id_archivo_anexo)) {

			$query2 = "UPDATE log_correo SET enviado=1 WHERE id_log_correo=" . $id;
			$resp2 = mysql_query($query2, $sesion->dbh) or Utiles::errorSQL($query2, __FILE__, __LINE__, $sesion->dbh);
			$enviados++;
		}
	}
}

echo '<br>Se ha detectado ' . count($encolados) . ' correos pendientes:';
echo '<br>Se ha  enviado ' . $enviados . ' correos pendientes';
