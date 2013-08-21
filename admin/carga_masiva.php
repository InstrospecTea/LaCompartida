<?php
require_once dirname(__FILE__) . '/../app/conf.php';

$sesion = new Sesion(array('ADM'));

$pagina = new Pagina($sesion);
$pagina->titulo = __('Carga masiva de datos');
$pagina->PrintTop();
?>
<form method="POST" action="datos_carga_masiva.php">
	<!-- TODO: agregar opcion para cargar datos ya existentes para edicion masiva -->
	Cargar <?php echo Html::SelectArray(array('UsuarioExt', 'Cliente', 'Asunto', 'Factura', 'FacturaPago'), 'clase'); ?>
	<a href="javascript:ejemplo()">¿Qué debo pegar en el recuadro? (descarga ejemplo)</a>
	<textarea name="raw_data" rows="18" cols="100"></textarea><br /><br />
	<input type="submit" value="Enviar"/>
</form>
<script type="text/javascript">
function ejemplo(){
	document.location.href = 'ejemplos_xls/' + jQuery('[name=clase]').val() + '.xls';
}
</script>