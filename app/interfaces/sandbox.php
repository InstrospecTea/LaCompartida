<?php
require_once dirname(__FILE__) . '/../conf.php';

	$sesion = new Sesion();
	$cobroPendiente = new CobroPendiente($sesion);
	$cobroPendiente->GenerarCobrosPeriodicos($sesion);
