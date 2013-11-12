<?php

require_once dirname(__FILE__) . '/../../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';

header("Content-Type: text/html; charset=ISO-8859-1");

$sesion = new Sesion(array('TAR'));
$desde = $_POST['xdesde'];

$tarifasfaltantes = "
	SELECT us.id_usuario, ct.id_moneda, ct.tarifa, ct.id_tarifa
	FROM usuario us
	JOIN categoria_tarifa ct
	USING ( id_categoria_usuario ) 
	LEFT JOIN usuario_tarifa ut ON ut.id_usuario = us.id_usuario
	AND ut.id_moneda = ct.id_moneda
	AND ut.id_tarifa = ct.id_tarifa
	WHERE id_usuario_tarifa IS NULL ";

$resptarifas = mysql_query($tarifasfaltantes, $sesion->dbh);
$i = 0;

while ($fila = mysql_fetch_row($resptarifas)) {

	$insertquery = "insert ignore into usuario_tarifa (id_usuario, id_moneda, tarifa, id_tarifa) values ($fila[0],$fila[1],$fila[2],$fila[3])";

	if (mysql_query($insertquery, $sesion->dbh)) {
		++$i;
	} else {
		echo mysql_error() . '<br>';
	}
}

echo 'Se insertaron ' . $i . ' tarifas';
?>