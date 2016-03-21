<?php
require_once dirname(__FILE__) . '/../conf.php';

$Sesion = new Sesion();
$cobroPendiente = new CobroPendiente($Sesion);
$cobroPendiente->GenerarCobrosPeriodicos($Sesion);

echo "Proceso de generación de cobros periodicos finalizado";

// $CronCobroProgramado = new CronCobroProgramado();
// $CronCobroProgramado->cobrosPendientes();
