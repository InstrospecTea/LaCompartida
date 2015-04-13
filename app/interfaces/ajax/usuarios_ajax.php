<?php
require_once dirname(__FILE__).'/../../conf.php';

$sesion = new Sesion(array('ADM'));
$where = " AND usr.rut != '99511620' ";

$queryuser = "SELECT
	usr.rut,
	usr.id_usuario,
	LOWER(concat(usr.apellido1, ' ', usr.apellido2, ', ', usr.nombre)) AS nombrecompleto,
	MAX(usr.activo) AS ACT,
	SUM(IF(up.codigo_permiso = 'DAT', 1, 0)) AS DAT,
	SUM(IF(up.codigo_permiso = 'ADM', 1, 0)) AS ADM,
	SUM(IF(up.codigo_permiso = 'COB', 1, 0)) AS COB,
	SUM(IF(up.codigo_permiso = 'EDI', 1, 0)) AS EDI,
	SUM(IF(up.codigo_permiso = 'LEE', 1, 0)) AS LEE,
	SUM(IF(up.codigo_permiso = 'OFI', 1, 0)) AS OFI,
	SUM(IF(up.codigo_permiso = 'PRO', 1, 0)) AS PRO,
	SUM(IF(up.codigo_permiso = 'REP', 1, 0)) AS REP,
	SUM(IF(up.codigo_permiso = 'REV', 1, 0)) AS REV,
	SUM(IF(up.codigo_permiso = 'SEC', 1, 0)) AS SEC,
	SUM(IF(up.codigo_permiso = 'SOC', 1, 0)) AS SOC,
	SUM(IF(up.codigo_permiso = 'TAR', 1, 0)) AS TAR,
	SUM(IF(up.codigo_permiso = 'RET', 1, 0)) AS RET,
	SUM(IF(up.codigo_permiso = 'ALL', 1, 0)) AS PALL,
	SUM(IF(contrato.id_contrato IS NOT NULL, 1, 0)) AS contratos
FROM usuario AS usr
	LEFT JOIN usuario_permiso AS up ON up.id_usuario = usr.id_usuario
	LEFT JOIN contrato ON contrato.id_usuario_responsable = usr.id_usuario
WHERE 1
	$where
GROUP BY usr.id_usuario, nombrecompleto";

$resp = mysql_query($queryuser, $sesion->dbh) or die( mysql_error());
echo '{ "aaData": [';

$i = 0;

while($fila = mysql_fetch_assoc($resp)) {
	if (++$i > 1) {
		echo ',';
	}
	$fila['nombrecompleto'] = ucwords(utf8_encode(trim($fila['nombrecompleto'])));
  echo json_encode($fila) ;
}

echo '] }';
