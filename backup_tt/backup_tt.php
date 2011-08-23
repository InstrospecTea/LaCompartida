<?php

require_once dirname(__FILE__) . '/conf.php';
/*
  foreach db
  generar dump.sql en /tmp
  generar comprimido .tar.gz
  borrar .sql
  copiar comprimido a dominio/backups/db_fecha.sql.tar.gz
  borrar comprimido en /tmp
  borrar backups viejos (dejar los ultimos 7 dias y los 4 viernes anteriores)
 */

function loguear($msg) {
	$msg = '[' . date('Y-m-d H:i:s') . "] $msg";
	echo "$msg\n";
	return $msg;
}

function EnviarMail($conf, $subject, $body) {
	require_once dirname(__FILE__) . '/PHPMailer/class.phpmailer.php';
	try {
		$mail = new PHPMailer();

		$mail->IsSMTP(); // telling the class to use SMTP
		$mail->SMTPAuth = true; // enable SMTP authentication
		$mail->SMTPSecure = 'ssl'; // sets the prefix to the servier
		$mail->Host = $conf['host']; // sets GMAIL as the SMTP server
		$mail->Port = $conf['port']; // set the SMTP port for the GMAIL server

		$mail->Username = $conf['user']; //recordar poner en el conf el correo completo: algo@lemontech.cl
		$mail->Password = $conf['pass'];

		$mail->SetFrom($conf['from'], 'Mailer Backups TT');

		$correos = explode(',', $conf['to']);
		foreach ($correos as $correo) {
			$mail->AddAddress($correo);
		}

		$mail->Subject = $subject;
		$mail->AltBody = 'Debe utilizar un lector de correos que acepte HTML'; // optional, comment out and test
		$mail->MsgHTML($body);

		if (!$mail->Send()) {
			return "Error: {$mail->ErrorInfo}";
		}
		return null;
	} catch (Exception $e) {
		return "Excepcion: {$e->getMessage()}";
	}
}

$errores = array();

//calcular las fechas de los backups q no se borran
function fechaBorrable($fechaviejo, $duracion) {
	if (empty($duracion)) {
		$duracion = array(7, 4, 'friday');
	}

	$diaSemana = isset($duracion[2]) ? $duracion[2] : 'friday';

	$timeDias = strtotime(-($duracion[0] - 1) . ' days');
	$fechaDias = date('Y-m-d', $timeDias);
	$vierneses = array();

	for ($i = $duracion[1]; $i; $i--) {
		$vierneses[] = date('Y-m-d', strtotime("-$i $diaSemana", $timeDias));
	}

	return $fechaviejo < $fechaDias && !in_array($fechaviejo, $vierneses);
}

loguear('leyendo conf');

//leer conf de mysql y lista de dbs
$conf = new CONF();
$fecha = date('Y-m-d');

loguear('limpiando temporales');
$temps = glob("{$conf->dir_temp}/*_*.sql*");
if (!empty($temps)) {
	loguear('borrando ' . count($temps) . ' archivos temporales');
}
foreach ($temps as $temp) {
	if (file_exists($temp) && !unlink($temp)) {
		$errores[] = loguear("error al borrar temporal antiguo $temp");
	}
}

foreach ($conf->dbs as $sitio) {
	list($db, $dominio, $confDuracion) = $sitio;

	$duracion = empty($confDuracion) ? array(7, 4, 'friday') :
			(is_array($confDuracion) ? $confDuracion : $conf->duracion[$confDuracion]);

	if (fechaBorrable($fecha, $duracion)) {
		loguear("saltandose respaldo de $db por configuracion de duracion de backups (" . print_r($duracion, true) . ')');
		continue;
	}

	loguear("respaldando $db en $dominio");

	$dir = "{$conf->dir_base}/$dominio/backups";
	if (!file_exists($dir)) {
		loguear("creando directorio $dir");
		if (!mkdir($dir, 0755, true)) {
			$errores[] = loguear("error al crear directorio $dir");
			continue;
		}
	}

	if (!file_exists($conf->dir_temp)) {
		loguear("creando directorio temporal {$conf->dir_temp}");
		if (!mkdir($conf->dir_temp, 0755, true)) {
			$errores[] = loguear("error al crear directorio {$conf->dir_temp}");
			continue;
		}
	}

	$espacio = disk_free_space($dir) / (1024 * 1024 * 1024);
	if ($espacio < $conf->alerta_disco_base) {
		$errores['espacio_base'] = loguear("quedan solo $espacio GB libres en $dir");
	}

	$out = array();
	$ret = 0;

	//genero el dump sql
	$path = "{$conf->dir_temp}/$db_$fecha.sql";
	loguear("dumpeando a $path");
	exec("(mysqldump --add-drop-table -h{$conf->host} -u{$conf->login} -p{$conf->password} $db > $path) 2>&1", $out, $ret);
	if ($ret) {
		$errores[] = loguear("error generando dump para $db. retornado: $ret\noutput: " . implode("\n", $out));
		if (file_exists($path)) {
			loguear("borrando dump fallado");
			if (is_file($path) && !unlink($path)) {
				$errores[] = loguear("error al borrar dump fallado");
			}
		}
		continue;
	}

	//lo comprimo y borro el descomprimido
	loguear('comprimiendo archivo');
	exec("tar -cz -f $path.tar.gz $path 2>&1", $out, $ret);
	if ($ret) {
		$errores[] = loguear("error al comprimir dump para $db. retornado: $ret\noutput: " . implode("\n", $out));
		if (file_exists("$path.tar.gz")) {
			loguear("borrando comprimido fallado");
			if (!unlink("$path.tar.gz")) {
				$errores[] = loguear("error al borrar comprimido fallado");
			}
		}
		if (file_exists($path) && !unlink($path)) {
			$errores[] = loguear("error al borrar $path");
		}
		continue;
	}
	if (file_exists($path) && !unlink($path)) {
		$errores[] = loguear("error al borrar $path");
	}

	//copio el comprimido al directorio correspondiente (q estaria en otra maquina asi q no se genera directo alla)
	loguear("copiando a $dir");
	if (!copy("$path.tar.gz", "$dir/{$db}_{$fecha}.sql.tar.gz")) {
		$errores[] = loguear("error al copiar backup temporal de $bd a $dir");
		if (file_exists("$path.tar.gz") && !unlink("$path.tar.gz")) {
			$errores[] = loguear("error al borrar el comprimido temporal $path.tar.gz");
		}
		continue;
	} else if (file_exists("$path.tar.gz") && !unlink("$path.tar.gz")) {
		$errores[] = loguear("error al borrar el comprimido temporal $path.tar.gz");
	}

	//borro los backups antiguos q no sean de esta semana o de los ultimos 5 viernes
	loguear('borrando backups antiguos...');


	$viejos = glob("$dir/{$db}_*.sql.tar.gz");
	foreach ($viejos as $viejo) {
		if (preg_match("/\d{4}-\d{2}-\d{2}/", $viejo, $match)) {
			$fechaviejo = $match[0];
			if (fechaBorrable($fechaviejo, $duracion)) {
				loguear("borrando backup antiguo $viejo");
				if (file_exists($viejo) && !unlink($viejo)) {
					$errores[] = loguear("error al borrar $viejo");
				}
			}
		}
	}

	$espacio = disk_free_space($dir) / (1024 * 1024 * 1024);
	if ($espacio < $conf->alerta_disco_base) {
		$errores['espacio_base'] = loguear("quedan solo $espacio GB libres en $dir");
	} else if (isset($errores['espacio_base'])) {
		unset($errores['espacio_base']);
	}
}

$espacio_disco_local = disk_free_space($conf->dir_temp) / (1024 * 1024 * 1024);
if ($espacio_disco_local < $conf->alerta_disco_temp) {
	$errores[] = loguear("quedan solo $espacio_disco_local GB libres en {$conf->dir_temp}");
}


if (!empty($errores)) {
	loguear(count($errores) . ' errores, mandando mail...');
	$errorMail = EnviarMail($conf->mailer, count($errores) . ' problemas en proceso de backups', implode("<br/>\n", $errores));
	if ($errorMail) {
		loguear('error mandando mail: ' . $errorMail);
	} else {
		loguear('mail ok');
	}
}

loguear('fin');
?>