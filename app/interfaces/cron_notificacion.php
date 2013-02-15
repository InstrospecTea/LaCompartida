<?php
require_once dirname(dirname(__FILE__)) . '/classes/CronNotificacion.php';

ini_set('display_errors', 'on');
$Cron = new CronNotificacion;
$correo = $argv[1] == 'correo' || isset($_GET['correo']);
$desplegar_correo = $argv[1] == 'desplegar_correo' || isset($_GET['desplegar_correo']);
$Cron->main($correo, $desplegar_correo);
