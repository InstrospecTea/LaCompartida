<?php
require_once dirname(__FILE__) . '/../conf.php';

$Sesion = new Sesion();
$cobroPendiente = new CobroPendiente($Sesion);
$cobroPendiente->GenerarCobrosPeriodicos($Sesion);

// $CronCobroProgramado = new CronCobroProgramado();
// $CronCobroProgramado->cobrosPendientes();
