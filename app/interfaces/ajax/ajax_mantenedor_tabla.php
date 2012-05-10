<?php

require_once dirname(__FILE__).'/../../app/conf.php';
require_once Conf::ServerDir().'/../fw/classes/Sesion.php';


require_once Conf::ServerDir().'/../fw/tablas/funciones_mantencion_tablas.php';


	$sesion = new Sesion(array('ADM'));
if(isset($_POST['glosatabla'])) {
    $nombretabla=$_POST['glosatabla'];
    echo  Tabla($sesion, $nombretabla,1);
}


	
?>