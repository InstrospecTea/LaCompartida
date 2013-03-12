<?php
require_once dirname(__FILE__) . '/../../app/conf.php';
$sesion = new Sesion(array('ADM'));
$pagina = new Pagina($sesion);
$pagina->titulo = __('Auditorias');
$pagina->PrintTop();?>

<a href="correos.php">Correos</a>

<?php
$pagina->PrintBottom();