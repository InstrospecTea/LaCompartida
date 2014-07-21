<?php
require_once dirname(__FILE__) . '/../conf.php';

$Sesion = new Sesion(array('DAT'));
$Pagina = new Pagina($Sesion);

$id_usuario = $Sesion->usuario->fields['id_usuario'];

$Actividad = new Actividad($Sesion);
$Actividad->Fill($_REQUEST);

switch ($opc) {
	case 'eliminar':
		if ($Actividad->Delete()) {
			$Pagina->AddInfo(__('Actividad') . ' ' . __('eliminada con �xito'));
		} else {
			$Pagina->AddError($Actividad->error);
		}
		break;
	case 'xls':
		$Actividad->DownloadExcel();
		break;
}

$Pagina->titulo = __('Actividades');
$Pagina->PrintTop();

$codigo_actividad = $Actividad->fields['codigo_actividad'];
$codigo_cliente = $Actividad->extra_fields['codigo_cliente'];
$codigo_asunto = $Actividad->fields['codigo_asunto'];
?>

<form method="POST" action="actividades.php" name="form_actividades" id="form_actividades">
	<input type="hidden" name="xdesde" id="xdesde" value="">
	<input type="hidden" name="opc" value="buscar">

	<div style="width: 95%; text-align: "right"; margin: 4px auto;" align="right">
		 <a href="#" class="btn botonizame" icon="agregar" id="agregar_actividad" title="<?php echo __('Agregar'); ?>" onclick=""><?php echo __('Agregar') . ' ' . __('Actividad'); ?></a>
	</div>

	<table style="border: 1px solid #BDBDBD;" class="tb_base" width="90%">
		<tr>
			<td align="right" width="25%">&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align="right">
				<?php echo __('C�digo'); ?>
			</td>
			<td align=left>
				<input name="codigo_actividad" size="5" maxlength="5" value="<?php echo $codigo_actividad; ?>" id="codigo_actividad" />
			</td>
		</tr>
		<tr>
			<td align="right">
				<?php echo __('T�tulo'); ?>
			</td>
			<td align=left>
				<input <?php echo $tooltip ?> name='glosa_actividad' id='glosa_actividad' size='35' value="<?php echo $Actividad->fields['glosa_actividad']; ?>" />
			</td>
		</tr>
		<tr>
			<td align="right">
				<?php echo __('Cliente'); ?>
			</td>
			<td align=left nowrap>
				<?php UtilesApp::CampoCliente($Sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario); ?>
			</td>
		</tr>
		<tr>
			<td align="right">
				<?php echo __('Asunto'); ?>
			</td>
			<td align="left">
				<?php UtilesApp::CampoAsunto($Sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario, 320, null, $glosa_asunto, false); ?>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td colspan="2" align="center">
				<input name="boton_buscar" id="boton_buscar" type="submit" value="<?php echo __('Buscar') ?>" class="btn" onclick="javascript:this.form.opc.value = 'buscar'"/>
				<input name="boton_excel" id="boton_excel" type="submit" value="<?php echo __('Descargar Excel') ?>" class="btn" onclick="javascript:this.form.opc.value = 'xls'"/>
			</td>
			<td align="left">
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
	</table>
</form>
<br/><br/>

<?php
if ($opc == 'buscar') {
	if ($orden == '') {
		$orden = 'id_actividad';
	}

	if (!$desde) {
		$desde = 0;
	}

	if (empty($_REQUEST['codigo_asunto']) && !empty($_REQUEST['glosa_asunto'])) {
		$Actividad->fields['glosa_asunto'] = $_REQUEST['glosa_asunto'];
	}

	$x_pag = 25;
	$b = new Buscador($Sesion, $Actividad->SearchQuery(), 'Actividad', $desde, $x_pag, $orden);
	$b->AgregarEncabezado('glosa_actividad', __('Nombre Actividad'), 'align=left');
	$b->AgregarEncabezado('glosa_asunto', __('Asunto'), 'align=left');
	$b->AgregarEncabezado('glosa_cliente', __('Cliente'), 'align=left');
	$b->AgregarEncabezado('codigo_actividad', __('C�digo'), 'align=left');
	$b->AgregarFuncion('', 'acciones', 'align=center');
	$b->color_mouse_over = '#bcff5c';
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
			nuovaFinestra('Agregar_Actividad', 670, 300, 'agregar_actividades.php?popup=1');
		});
	});

	function EditarActividad(id) {
		var url = 'agregar_actividades.php?id_actividad=' + id + '&popup=1';
		return nuovaFinestra('Editar_Actividad', 670, 300, url);
	}

	function EliminarActividad(id) {
		if (parseInt(id) > 0 && confirm('�Desea eliminar la actividad seleccionada?') == true) {
			var url = 'actividades.php?id_actividad=' + id + '&opc=eliminar&desde=<?php echo ($desde) ? $desde : '0' ?>';
			self.location.href = url;
		}
	}

	function Refrescar() {
		jQuery('#boton_buscar').click();
	}

	function BuscarFacturas(form, from) {
		if (!form) {
			var form = $('form_actividades');
		}

		switch (from) {
			case 'buscar':
				form.action = 'actividades.php';
				break;
			case 'exportar_excel':
				form.action = 'actividades_xls.php';
				break;
			default:
				return false;
		}

		form.submit();
		return true;
	}
</script>

<?php
$Pagina->PrintBottom();
