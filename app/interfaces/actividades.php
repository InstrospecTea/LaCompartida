<?php
require_once dirname(__FILE__).'/../conf.php';

$Sesion = new Sesion(array('DAT'));
$Pagina = new Pagina($Sesion);

$id_usuario = $Sesion->usuario->fields['id_usuario'];

$Actividad = new Actividad($Sesion);
$Actividad->Fill($_REQUEST);

// echo print_r($Actividad->extra_fields, true); exit;
if ($accion == 'eliminar') {
	if ($Actividad->Delete()) {
		$Pagina->AddInfo(__('Actividad').' '.__('eliminada con éxito'));
	} else {
		$Pagina->AddError($Actividad->error);
	}
}

$Pagina->titulo = __('Actividades');
$Pagina->PrintTop();
?>

<form method="POST" action="actividades.php" name="form_actividades" id="form_actividades">
<input  id="xdesde"  name="xdesde" type="hidden" value="">
<input type="hidden" name="accion" value="buscar" />

<table style="border: 0px solid black" width='90%'>
	<tr>
		<td></td>
		<td colspan="3" align="right">
			<a href="#" class="btn botonizame" icon="agregar" id="agregar_actividad" title="<?php echo __('Agregar') ?>" onclick=""><?php echo __('Agregar') . ' ' . __('Actividad') ?></a>
		</td>
	</tr>
</table>
<table style="border: 1px solid #BDBDBD;" class="tb_base" width="90%">
	<tr>
		<td align=right>
			<?php echo __('Código')?>
		</td>
		<td align=left>
			<input name="codigo_actividad" size="5" maxlength="5" value="<?php echo $Actividad->fields['codigo_actividad']?>" id="codigo_actividad" />
		</td>
	</tr>
	<tr>
		<td align=right>
			<?php echo __('Título')?>
		</td>
		<td align=left>
			<input <?php echo  $tooltip ?> name='glosa_actividad' id='glosa_actividad' size='35' value="<?php echo  $Actividad->fields['glosa_actividad'] ?>" />
		</td>
	</tr>
	<tr>
		<td align=right>
			<?php echo __('Cliente')?>
		</td>
		<td align=left nowrap>
			<?php UtilesApp::CampoCliente($Sesion, $Actividad->extra_fields['codigo_cliente'], $codigo_cliente_secundario, $Actividad->fields['codigo_asunto'], $codigo_asunto_secundario); ?>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?php echo __('Asunto')?>
		</td>
		<td align=left >
			<?php UtilesApp::CampoAsunto($Sesion, $Actividad->extra_fields['codigo_cliente'], $codigo_cliente_secundario, $Actividad->fields['codigo_asunto'], $codigo_asunto_secundario); ?>
		</td>
	</tr>
	<tr>
		<td colspan=2 align="center">
			<input name="boton_buscar" id="boton_buscar" type="submit" value="<?php echo __('Buscar') ?>" class="btn"  onclick="javascript:this.form.accion.value = 'buscar'"/>
		</td>
	</tr>
</table>
</form>
<br/><br/>
<?php
if ($accion == 'buscar') {

	if ($orden == "") {
		$orden = "id_actividad";
	}	

	if (!$desde) {
		$desde = 0;
	}
	
	$x_pag = 25;					
	$b = new Buscador($Sesion, $Actividad->SearchQuery(), 'Actividad', $desde, $x_pag, $orden);
	$b->AgregarEncabezado("glosa_actividad", __('Nombre Actividad'), "align=left");
	$b->AgregarEncabezado("glosa_asunto",__('Asunto'), "align=left");
	$b->AgregarEncabezado("glosa_cliente", __('Cliente'), "align=left");
	$b->AgregarEncabezado("codigo_actividad", __('Código'), "align=left");
	$b->AgregarFuncion("",'acciones', "align=center");
	$b->color_mouse_over = "#bcff5c";
	$b->Imprimir();
}

function acciones(& $fila) {
	global $Sesion;
	
	$boton_editar = '<a href="javascript:void(0);" onclick="EditarActividad(' . $fila->fields['id_actividad'] . ');" title="Editar Actividad">'
		. '<img src="' . Conf::ImgDir() . '/editar_on.gif" border="0" alt="Editar Actividad" /></a>';

	$boton_eliminar = '<a href="javascript:void(0);" onclick="EliminarActividad(' . $fila->fields['id_actividad'] . ');">'
		. '<img src="' . Conf::ImgDir() . '/cruz_roja_nuevo.gif" border="0" alt="Eliminar" /></a>';
	
	return "$boton_editar $boton_eliminar";
}
?>
<script type="text/javascript">

jQuery(document).ready(function() {
	jQuery("#agregar_actividad").click(function() {
		nuovaFinestra('Agregar_Actividad',670,300,'agregar_actividades.php?popup=1'); 
	});
});

function EditarActividad(id) {
	var url = 'agregar_actividades.php?id_actividad=' + id + '&popup=1';
	return nuovaFinestra('Editar_Actividad', 670, 300, url);
}

function EliminarActividad(id) {
	if (parseInt(id) > 0 && confirm('¿Desea eliminar la actividad seleccionada') == true) {
		var url = 'actividades.php?id_actividad=' + id + '&accion=eliminar&desde=<?php echo ($desde) ? $desde : '0'?>';
		self.location.href = url;
	}
}

function Refrescar() {
	document.forms[0].submit();
}
</script>
<?php
$Pagina->PrintBottom();
