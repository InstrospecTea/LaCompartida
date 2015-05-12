<?php
require_once dirname(__FILE__) . '/../../conf.php';

$sesion = new Sesion(array('ADM'));

$queryuser = "SELECT
	usuario.rut,
	usuario.id_usuario,
	LOWER(concat(usuario.apellido1, ' ', usuario.apellido2, ', ', usuario.nombre)) AS nombrecompleto,
	MAX(usuario.activo) AS ACT,
	SUM(IF(usuario_permiso.codigo_permiso = 'DAT', 1, 0)) AS DAT,
	SUM(IF(usuario_permiso.codigo_permiso = 'ADM', 1, 0)) AS ADM,
	SUM(IF(usuario_permiso.codigo_permiso = 'COB', 1, 0)) AS COB,
	SUM(IF(usuario_permiso.codigo_permiso = 'EDI', 1, 0)) AS EDI,
	SUM(IF(usuario_permiso.codigo_permiso = 'LEE', 1, 0)) AS LEE,
	SUM(IF(usuario_permiso.codigo_permiso = 'OFI', 1, 0)) AS OFI,
	SUM(IF(usuario_permiso.codigo_permiso = 'PRO', 1, 0)) AS PRO,
	SUM(IF(usuario_permiso.codigo_permiso = 'REP', 1, 0)) AS REP,
	SUM(IF(usuario_permiso.codigo_permiso = 'REV', 1, 0)) AS REV,
	SUM(IF(usuario_permiso.codigo_permiso = 'SEC', 1, 0)) AS SEC,
	SUM(IF(usuario_permiso.codigo_permiso = 'SOC', 1, 0)) AS SOC,
	SUM(IF(usuario_permiso.codigo_permiso = 'TAR', 1, 0)) AS TAR,
	SUM(IF(usuario_permiso.codigo_permiso = 'RET', 1, 0)) AS RET,
	SUM(IF(usuario_permiso.codigo_permiso = 'ALL', 1, 0)) AS PALL,
	(SELECT count(*) FROM contrato WHERE contrato.id_usuario_responsable = usuario.id_usuario) AS contratos
FROM usuario
	LEFT JOIN usuario_permiso ON usuario_permiso.id_usuario = usuario.id_usuario
WHERE
	usuario.rut != '99511620'
GROUP BY usuario.id_usuario, nombrecompleto";

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
