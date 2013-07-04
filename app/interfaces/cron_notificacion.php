<?php
date_default_timezone_set('America/Santiago');
require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion(null, true);
$CronNotificacion = new CronNotificacion($sesion);

/**
 *  ej cron_notificacion.php correo
 *  ej cron_notificacion.php?correo=correo
 */
if (($argv[1] == 'correo') || (isset($_GET['correo']) && $_GET['correo']=='correo' )) {
	$correo = 'generar_correo';
/**
 * ej cron_notificacion.php desplegar_correo aefgaeddfesdg23k1h3kk1
 * ej cron_notificacion.php?correo=desplegar_correo&desplegar_correo=aefgaeddfesdg23k1h3kk1
 */
} else if (($argv[1] == 'desplegar_correo') || (isset($_GET['correo']) && $_GET['correo'] == 'desplegar_correo')) {
	$correo = 'desplegar_correo';
/**
 * ej cron_notificacion.php simular_correo aefgaeddfesdg23k1h3kk1
 * ej cron_notificacion.php?correo=simular_correo&simular_correo=aefgaeddfesdg23k1h3kk1
 */
} else if (($argv[1] == 'simular_correo') || (isset($_GET['correo']) && $_GET['correo'] == 'simular_correo')) {
	$correo = 'simular_correo';
} else {
	$correo = null;
}

/**
 *
 * @var string $desplegar_correo toma el valor del parámetro desplegar_correo cuando viene, o el "hash"  de la línea de comando
 * @var string $forzar_semanal toma el valor del parámetro forzar_semanal cuando viene, o el "hash"  de la línea de comando
 */
$desplegar_correo = $argv[2] || $_GET['desplegar_correo'];
$forzar_semanal = $argv[2] || $_GET['forzar_semanal'];

$CronNotificacion->main($correo, $desplegar_correo, $forzar_semanal);
