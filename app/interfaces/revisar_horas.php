<?php

require_once dirname(__FILE__).'/../conf.php';

$sesion = new Sesion(array('REV'));
$pagina = new Pagina($sesion);
$id_usuario = $sesion->usuario->fields['id_usuario'];

$t = new Trabajo($sesion);

if($id_trab > 0) {
    $t->Load($id_trab);
}

if($opcion == "eliminar") {
    
    $t = new Trabajo($sesion);
    $t->Load($id_trabajo);
    
    if($t->Estado() == "Abierto") {
        if(!$t->Eliminar()) {
            $pagina->AddError($t->error);
        }
    }
}

$pagina->titulo = __('Revisar horas');
$pagina->PrintTop();

if($estado == "") {
    $estado = "abiertos";
}

echo '<iframe name="trabajos" id="trabajos" src="trabajos.php?popup=1&id_usuario=<?php echo $id_usuario?>" frameborder="0" width="740px" height="10000px"></iframe>';

$pagina->PrintBottom();

?>
