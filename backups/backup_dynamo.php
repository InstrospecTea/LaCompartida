#!/usr/bin/php
<?php

require_once dirname(__FILE__) . '/autoload.php';

$errores = [];

$Body = new Body();

set_time_limit(7200);

$Body->add('leyendo conf');
require_once dirname(__DIR__) . '/app/conf.php';
require_once dirname(__FILE__) . '/config.php';
$dir_temp = Conf::read('dir_temp');
$fecha = date('Y-m-d');


$Body->add('limpiando temporales');
$temps = glob("$dir_temp/*_*.sql*");
if (!empty($temps)) {
	$Body->add('borrando ' . count($temps) . ' archivos temporales');
	foreach ($temps as $temp) {
		if (!unlink($temp)) {
			$errores[] = $Body->add("error al borrar temporal antiguo $temp");
		}
	}
}

$Body->add('leyendo DynamoDB');

$tenants = Backup::getTenants();

$Body->add('termina la lectura de DynamoDB');

$db_updater = new DatabaseUpdater(Conf::Hash());
$backup_mysql_error = Conf::read('backup_mysql_error');

$bucket_name = 'ttbackups';
$S3 = new S3($bucket_name);
foreach ($tenants as $tenant) {

	if (isset($argv[1]) && $argv[1] != $tenant['dbname']) {
		continue;
	}

	if ($tenant['backup'] != 1) {
		$Body->add("Respaldo apagado para {$tenant['dominio']}", true);
		continue;
	}

	$Body->add("respaldando {$tenant['dominio']}");
	$slave_host = $tenant['dbhost'];
	$subdominio = preg_replace('/^([^\.]+)\..*/', '$1', $tenant['subdominiosubdir']);

	$Body->add("comprobando bucket S3 {$bucket_name}");
	try {
		$S3->createBucket($bucket_name);
	} catch (Exception $e) {
		print_r($e);
	}

	$duracion = [
		'days' => intval($tenant['days']),
		'weeks' => intval($tenant['weeks']),
		'day' => $tenant['dia']
	];
	if (Backup::fechaBorrable($fecha, $duracion)) {
		$datos_duracion = "day:{$tenant['days']}, week:{$tenant['weeks']}, day:{$tenant['dia']}";
		$Body->add("saltandose respaldo de {$tenant['dbname']} por configuracion de duracion de backups ($datos_duracion)", true);
		continue;
	}

	$Body->add("respaldando {$tenant['dbname']} en {$tenant['vhost']}");

	$path = "{$dir_temp}/{$tenant['dbname']}_{$fecha}.sql.gz";
	$file_bkp = "{$subdominio}/{$tenant['dbname']}_{$fecha}.sql.gz";

	if ($tenant['mistery'] == 1) {
		$tenant['dbpass'] = Utiles::decrypt($tenant['dbpass'], $tenant['backupdir']);
	}

	if ($S3->fileExists($file_bkp)) {
		$Body->add("respaldo $file_bkp ya existe: omitiendo...");
	} else {
		if (!file_exists($dir_temp)) {
			$Body->add("creando directorio temporal $dir_temp");
			if (!mkdir($dir_temp, 0755, true)) {
				$errores[] = $Body->add("error al crear directorio $dir_temp");
				continue;
			}
		}

		$Body->add("dumpeando a $path");
		Backup::mysqlError('');
		$out = Backup::makeDump($tenant, $path);
		$ret = Backup::mysqlError();
		if ($ret) {
			$errores[] = $Body->add("error generando dump para {$tenant['dbname']}. retornado: {$ret}\noutput: " . implode("\n", $out));
			if (file_exists($path)) {
				$Body->add('borrando dump fallido');
				if (!unlink($path)) {
					$errores[] = $Body->add('error al borrar dump fallido');
				}
			}
			continue;
		}

		$Body->add("copiando a S3: $path");
		try {
			$S3->uploadFile($file_bkp, $path);
			$db_updater->update('update_db', $tenant['subdominiosubdir'], $tenant['update_db']);
		} catch (Exception $e) {
			print_r($e);
		}

		if ($ret) {
			$errores[] = $Body->add("error copiando a S3, retornado: {$ret}\noutput: " . implode("\n", $out));
		}

		if (!unlink($path)) {
			$errores[] = $Body->add("error al borrar el comprimido temporal {$path}");
		}
	}

	$db_updater->update('update_db', $tenant['subdominiosubdir'], $tenant['update_db']);

	/*	 * ********* CLONANDO ***************** */
	if (!empty($tenant['dbclon']) && $tenant['dbclon'] != '_') {
		$Body->add("clonando a " . $tenant['dbclon']);
		Backup::mysqlError('');
		$pos = strpos($tenant['dbclon'], ':') !== false;
		$db_clon = [
			'dbhost' => $pos ? preg_replace('/^([^:]+).*/', '$1', $tenant['dbclon']) : $tenant['dbhost'],
			'dbname' => $pos ? preg_replace('/^[^:]+:(.*)/', '$1', $tenant['dbclon']) : $tenant['dbclon'],
		];

		if ($db_clon['dbname'] == $tenant['dbname'] && $db_clon['dbhost'] == $tenant['dbhost']) {
			$errores[] = $Body->add("no se puede clonar {$db_clon['dbhost']}.{$tenant['dbname']} sobre si misma");
		} else {
			$out = Backup::cloneDb($tenant, $db_clon['dbname']);
			$ret = Backup::mysqlError();
			if ($ret) {
				$sentencia = self::$lastSentence;
				$msg = "error clonando {$tenant['dbname']} ";
				$msg .= "en {$db_clon['dbhost']} {$db_clon['dbname']}: ";
				$msg .= "\n {$sentencia} ";
				$msg .= "\n {$ret}";
				$msg .= "\noutput: " . implode("\n", $out);
				$errores[] = $Body->add($msg);
			}
		}
	}

	$Body->add("Listando contenidos de {$bucket_name}/{$subdominio}");

	$files = $S3->listBucket($subdominio);

	$respaldos_borrados = Backup::deleteBackups($files, $S3, $duracion);
	if ($respaldos_borrados !== false) {
		$Body->add(count($respaldos_borrados) . " respaldos viejos o fallados eliminados de {$bucket_name}/{$subdominio}}");
		$Body->add(implode("<br/>\n ", $respaldos_borrados));
	}

	echo "\n";
}

$espacio_disco_local = disk_free_space($dir_temp) / (1024 * 1024 * 1024);
if ($espacio_disco_local < Conf::read('alerta_disco_temp')) {
	$errores[] = $Body->add("quedan solo {$espacio_disco_local} GB libres en $dir_temp");
}

if (!empty($errores)) {
	$Body->add(count($errores) . " errores, mandando mail...");
	$Sesion = new Sesion();
	$subject = count($errores) . ' problemas en proceso de backups';
	if (!Utiles::EnviarMail($Sesion, Conf::read('send_to'), $subject, $Body, false)) {
		$Body->add('error mandando mail: ' . Utiles::$emailError);
	} else {
		$Body->add('mail ok');
	}
}

$Body->add('fin');
