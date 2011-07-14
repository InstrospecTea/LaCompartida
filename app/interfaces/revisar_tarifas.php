<?php

	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../app/classes/Cliente.php';
	require_once Conf::ServerDir().'/../app/classes/InputId.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/../app/classes/Moneda.php';
	require_once Conf::ServerDir().'/../app/classes/Tarifa.php';
	require_once Conf::ServerDir().'/../app/classes/TarifaTramite.php';
	require_once Conf::ServerDir().'/../app/classes/Funciones.php';
	require_once Conf::ServerDir().'/../app/classes/Cobro.php';
	
	$sesion = new Sesion(array('REV'));
    $pagina = new Pagina($sesion);
    $id_usuario = $sesion->usuario->fields['id_usuario'];
	
	$id_moneda = ( isset( $_GET["id_moneda"]) && is_numeric($_GET["id_moneda"]) ? $_GET["id_moneda"] : 0 );
	$id_tarifa = ( isset( $_GET["id_tarifa"]) && is_numeric($_GET["id_tarifa"]) ? $_GET["id_tarifa"] : 0 );
	
	$query_usarios_sin_tarifa = "SELECT CONCAT( apellido1,' ', apellido2, ', ', nombre) as nombre_completo FROM usuario as u 
		JOIN usuario_permiso as up USING( id_usuario ) 
		WHERE up.codigo_permiso = 'PRO' AND u.id_usuario NOT IN ( 
			SELECT ut.id_usuario FROM usuario_tarifa as ut WHERE ut.id_moneda=" . $id_moneda . " AND ut.id_tarifa = " . $id_tarifa . " 
		)";
	$resp_usuarios_sin_tarifa = mysql_query($query_usarios_sin_tarifa, $sesion->dbh) or Utiles::errorSQL($query_usarios_sin_tarifa,__FILE__,__LINE__,$sesion->dbh);
	$numrows = mysql_num_rows($resp_usuarios_sin_tarifa);
	
	if( $numrows > 0)
	{
		$todos = "";
		while( $ust = mysql_fetch_array($resp_usuarios_sin_tarifa))
		{
			$todos .= ( strlen( $todos ) > 0 ? "<br />" : "");
			$todos .= htmlentities( $ust["nombre_completo"], ENT_QUOTES, 'ISO-8859-1' );
		}
		echo $numrows . "::" . $todos;
	}
	else
	{
		echo $numrows . "::" . "";
	}
?>
