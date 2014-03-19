<?php

require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/classes/CronAnulaDTE.php';

$sesion = new Sesion(null, true);
$CronAnulaDTE = new CronAnulaDTE($sesion);
$CronAnulaDTE->main();

?>
