<?php

require_once dirname(__FILE__).'/../fw/classes/Sesion.php';

$sesion = new Sesion(null, true);

$resultado=mysql_query("select max(version) as version from version_db",$sesion->dbh);

if(!$resultado) {
    if(file_exists('version.php')) include_once('version.php');
} else {
    $valor=mysql_fetch_row($resultado);
    
    if (count($valor)==0) {
    if(file_exists('version.php')) include_once('version.php');    
    } else {
    $VERSION = $valor[0] ; if( $_GET['show'] == 1 ) echo 'Ver. '.$VERSION; 
    }
} 

?>
