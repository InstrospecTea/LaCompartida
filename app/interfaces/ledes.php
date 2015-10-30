<?php
require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion('');
$cobro = new Cobro($sesion);
$contrato = new Contrato($sesion);
$cobro->Load($id_cobro);
$contrato->Load($cobro->fields['id_contrato']);

if ($contrato->fields['formato_ledes'] == 'serengeti') {
	$Ledes = new Serengeti($sesion);
}

if ($contrato->fields['formato_ledes'] == 'tymetrix') {
	$Ledes = new TyMetrix($sesion);
}

$data =  $Ledes->ExportarCobrosLedes($id_cobro);

header("Content-type: text");
header('Content-Length: '.strlen($data));
header("Content-Disposition: attachment; filename=\"LEDES98B_$id_cobro.txt\"");
echo $data;
exit;
