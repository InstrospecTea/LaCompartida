<?php

require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion(array('ADM'));
$DocManager = new DocManager($sesion);
$CartaCobro = new CartaCobro($sesion);

/* AJAX check  */
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {

    $seccion = $_GET['seccion'];

    if (empty($seccion)) {
        exit('ERROR ');
    }
    
    $out = '';

    $tags = UtilesApp::mergeKeyValue($CartaCobro->diccionario[$seccion]);
    
    foreach ($tags as $val => $key) {
        $out .= '<option value="' . $val . '">' . basename($key) . "</option>\n";
    }

    //Output the file options
    exit($out);
}