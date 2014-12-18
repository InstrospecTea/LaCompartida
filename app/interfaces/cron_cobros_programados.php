<?php

require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/classes/CronCobroProgramado.php';

$CronCobroProgramado = new CronCobroProgramado();
$CronCobroProgramado->cobrosPendientes();

?>
