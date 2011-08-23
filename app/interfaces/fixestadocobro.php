<?php
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';

	global $sesion;
	
	$fh = fopen('../../../update.log', 'w+');
	$query1 = " SELECT f.id_cobro FROM factura f JOIN cobro c WHERE f.id_estado != 5 AND f.estado != 'ANULADA' AND f.anulado = 0 GROUP BY f.id_cobro AND c.cobro = 'EMITIDO'";
	$result = mysql_query($query1, $sesion->dbh);
	
	while( list($id_cobro) = mysql_fetch_array($result))
	{
		$queryu = "UPDATE cobro SET estado='FACTURADO' WHERE id_cobro='" . $id_cobro . "'";
		fwrite($fh, "Query: " .  $queryu . "\n");
	}

?>
