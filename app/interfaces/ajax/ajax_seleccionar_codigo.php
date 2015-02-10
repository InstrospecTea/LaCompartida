<?php
require_once dirname(__FILE__).'/../../conf.php';

$Sesion = new Sesion();
$PrmCodigo = new PrmCodigo($Sesion);

$term = strtolower(utf8_decode(addslashes($_POST['term'])));
$code = $_GET['codigo'];
$list = $PrmCodigo->Listar("WHERE prm_codigo.grupo = '{$code}' ORDER BY prm_codigo.glosa ASC");
echo json_encode(UtilesApp::utf8izar($list));