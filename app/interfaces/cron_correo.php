<?php
require_once dirname(__FILE__) . '/../conf.php';
set_time_limit(180);

$Sesion = new Sesion(null, true);

$encolados = 0;
$enviados = 0;
$no_enviados = 0;
$errores = '';
$debug = false;

if (($argv[1] == 'debug') || (isset($_GET['debug']) && $_GET['debug'] == '1')) {
	$debug = true;
}

$query = "SELECT id_log_correo, subject, mensaje, mail, nombre, id_archivo_anexo, intento_envio
	FROM log_correo
	WHERE enviado = 0 AND (intento_envio IS NULL OR intento_envio < 5)
	LIMIT 0, 60";
$resp = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);

while ($log_correo = mysql_fetch_array($resp)) {
	$correos = array();
	$addresses = explode(',', $log_correo['mail']);
	++$encolados;

	foreach ($addresses as $address) {
		$correo = array('nombre' => $log_correo['nombre'], 'mail' => trim($address));
		if (validEmail($address)) {
			array_push($correos, $correo);
		} else {
			$errores .= "<li>Error correo #{$log_correo['id_log_correo']}: '&lt;{$log_correo['nombre']}&gt; $address' <i>Dirección no válida</i></li>";
		}
	}

	if (!empty($correos)) {
		if (Utiles::EnviarMail($Sesion, $correos, $log_correo['subject'], $log_correo['mensaje'], true, $log_correo['id_archivo_anexo'])) {
			$query2 = "UPDATE log_correo SET enviado = 1, fecha_modificacion = NOW(), fecha_envio = NOW() WHERE id_log_correo = {$log_correo['id_log_correo']}";
			$resp2 = mysql_query($query2, $Sesion->dbh) or Utiles::errorSQL($query2, __FILE__, __LINE__, $Sesion->dbh);
			++$enviados;
		}
	} else {
		$log_correo['intento_envio'] = ((int) $log_correo['intento_envio']) + 1;
		$query2 = "UPDATE log_correo SET intento_envio = {$log_correo['intento_envio']}, fecha_modificacion = NOW() WHERE id_log_correo = {$log_correo['id_log_correo']}";
		$resp2 = mysql_query($query2, $Sesion->dbh) or Utiles::errorSQL($query2, __FILE__, __LINE__, $Sesion->dbh);
		++$no_enviados;
	}
}

if ($debug === true) {
	if (!empty($encolados)) {
		echo "<h1>$encolados correos detectados para su env&iacute;o</h1>";
	} else {
		echo "<h1>No se encontraron correos pendientes para su env&iacute;o</h1>";
	}

	if (!empty($enviados)) {
		echo "<br>Correos enviados: $enviados";
	}

	if (!empty($no_enviados)) {
		echo "<br>Correos NO enviados: $no_enviados";
		echo "<ul>{$errores}</ul>";
	}
}

// verificar si existen correos NO enviados y con reintentos
$minutos = (int) date('i');
if ($minutos == 10) {
	$query = "SELECT count(*) AS total FROM log_correo WHERE enviado = 0 AND intento_envio >= 5";
	$resp = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);
	$log_correo = mysql_fetch_assoc($resp);

	// solo enviar si existen correos pendientes con errores por enviar
	if ($log_correo['total'] > 0) {
		$dbName = Conf::dbName();
		$mensaje = "<b>Atención:</b> Se encontraron {$log_correo['total']} correos con errores para el cliente '$dbName'";

		Utiles::EnviarMail(
			$Sesion,
			array(array('nombre' => 'Administrador', 'mail' => 'correosmalos@thetimebilling.com')),
			"$dbName: {$log_correo['total']} correos con errores",
			$mensaje
		);

		if ($debug === true) {
			echo "<br>Correo aviso administrador enviado";
		}
	}
}

/**
 * Validate an email address.
 * Provide email address (raw input)
 * Returns true if the email address has the email address format and the domain exists.
 */
function validEmail($email) {
	$email = trim($email);
	$isValid = true;
	$atIndex = strrpos($email, "@");
	if (is_bool($atIndex) && !$atIndex) {
		$isValid = false;
	} else {
		$domain = substr($email, $atIndex + 1);
		$local = substr($email, 0, $atIndex);
		$localLen = strlen($local);
		$domainLen = strlen($domain);
		if ($localLen < 1 || $localLen > 64) {
			// local part length exceeded
			$isValid = false;
		} else if ($domainLen < 1 || $domainLen > 255) {
			// domain part length exceeded
			$isValid = false;
		} else if ($local[0] == '.' || $local[$localLen - 1] == '.') {
			// local part starts or ends with '.'
			$isValid = false;
		} else if (preg_match('/\\.\\./', $local)) {
			// local part has two consecutive dots
			$isValid = false;
		} else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
			// character not valid in domain part
			$isValid = false;
		} else if (preg_match('/\\.\\./', $domain)) {
			// domain part has two consecutive dots
			$isValid = false;
		} else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\", "", $local))) {
			// character not valid in local part unless
			// local part is quoted
			if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\", "", $local))) {
				$isValid = false;
			}
		}
		if ($isValid && !(checkdnsrr($domain, "MX") || checkdnsrr($domain, "A"))) {
			// domain not found in DNS
			$isValid = false;
		}
	}
	return $isValid;
}
