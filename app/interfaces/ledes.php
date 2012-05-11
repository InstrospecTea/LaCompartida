<?php
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../app/classes/Ledes.php';

$sesion = new Sesion('');
$Ledes = new Ledes($sesion);
$data =  $Ledes->ExportarCobrosLedes($id_cobro);

header("Content-type: text");
header('Content-Length: '.strlen($data));
header("Content-Disposition: attachment; filename=\"LEDES98B_$id_cobro.txt\"");
echo $data;
exit;