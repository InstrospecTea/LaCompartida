<?php 
	require_once dirname(__FILE__).'/../classes/Sesion.php';
	require_once dirname(__FILE__).'/../classes/Pagina.php';
	require_once dirname(__FILE__).'/../../app/conf.php';

	$sesion = new Sesion();

	$pagina = new Pagina($sesion);

	$sesion->Logout($salir);

	$pagina->Redirect(Conf::logoutRedirect().'&by=user');
?>
