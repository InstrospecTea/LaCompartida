<?php
require_once dirname(__FILE__).'/../conf.php';
session_start();
#se cambia el nombre de la aplicacion
$_SESSION['APP'] = 'Lemontech S.A - Causas Judiciales';
header("Location: ".Conf::HostJuicios());
?>
