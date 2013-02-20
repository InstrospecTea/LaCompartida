<?php
require_once dirname(__FILE__) . '/../app/conf.php';

$sesion = new Sesion(array('ADM'));

$pagina = new Pagina($sesion);
$pagina->titulo = __('Carga masiva de datos');
$pagina->PrintTop();
?>
<form method="POST" action="datos_carga_masiva.php">
	Cargar <?php echo Html::SelectArray(array('usuario', 'cliente', 'asunto'), 'tipo'); ?>
	<textarea name="raw_data" rows="18" cols="100"></textarea><br /><br />
	<input type="submit" value="Enviar"/>
	<!-- agregar opcion para cargar datos ya existentes para edicion masiva -->
</form>