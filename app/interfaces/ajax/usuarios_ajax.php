<?php

require_once dirname(__FILE__).'/../../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Sesion.php';

$sesion = new Sesion(array('ADM'));

$permiso_sadmin = $sesion->usuario->permisos->datos[10]->fields['permitido'];

//if ($permiso_sadmin == 0) {
	$where = " WHERE usr.rut != '99511620' ";
//} else {
	//$where = " WHERE 1 ";
//}

$queryuser="
	select 
		usr.
		rut,
		usr.
		id_usuario,
		lower(concat(usr.apellido1,' ',usr.apellido2,', ',usr.nombre))  nombrecompleto,
		max(activo) as ACT,
		sum(if(up.codigo_permiso='DAT',1,0)) as DAT,
		sum(if(up.codigo_permiso='ADM',1,0)) as ADM,
		sum(if(up.codigo_permiso='COB',1,0)) as COB,
		sum(if(up.codigo_permiso='EDI',1,0)) as EDI,
		sum(if(up.codigo_permiso='LEE',1,0)) as LEE,
		sum(if(up.codigo_permiso='OFI',1,0)) as OFI,
		sum(if(up.codigo_permiso='PRO',1,0)) as PRO,
		sum(if(up.codigo_permiso='REP',1,0)) as REP,
		sum(if(up.codigo_permiso='REV',1,0)) as REV,
		sum(if(up.codigo_permiso='SEC',1,0)) as SEC,
		sum(if(up.codigo_permiso='SOC',1,0)) as SOC,
		sum(if(up.codigo_permiso='TAR',1,0)) as TAR,
		sum(if(up.codigo_permiso='RET',1,0)) as RET,
		sum(if(up.codigo_permiso='ALL',1,0)) as PALL
	from usuario usr
		left join usuario_permiso up on up.id_usuario=usr.id_usuario 
		$where
		group by usr.id_usuario, nombrecompleto";

$resp = mysql_query($queryuser, $sesion->dbh) or die( mysql_error())                ;

echo '{ "aaData": [';

$i=0;

while($fila= mysql_fetch_assoc($resp)) {
	if(++$i>1) {
		echo ',';	
	}
		
	$fila['nombrecompleto']=ucwords(utf8_encode(trim($fila['nombrecompleto'])));
    echo json_encode($fila) ;
}

echo '] }';