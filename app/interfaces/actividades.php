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
$Form = new Form();

$codigo_actividad = $Actividad->fields['codigo_actividad'];
$codigo_cliente = $Actividad->extra_fields['codigo_cliente'];
$codigo_asunto = $Actividad->fields['codigo_asunto'];
?>

<form method="POST" action="actividades.php" name="form_actividades" id="form_actividades">
	<input type="hidden" name="xdesde" id="xdesde" value="">
	<input type="hidden" name="opc" id="opc" value="buscar">
	<input type="hidden" name="id_actividad" id="id_actividad" value="">

	<div style="width: 95%; margin-bottom: 5px;" align="right">
		<?php echo $Form->icon_button(__('Agregar') . ' ' . __('Actividad'), 'agregar', array('id' => 'agregar_actividad')); ?>
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
				<?php
				echo $Form->submit(__('Buscar'), array('onclick' => "jQuery('#opc').val('buscar')"));
				echo $Form->submit(__('Descargar Excel'), array('onclick' => "jQuery('#opc').val('xls')"));
				?>
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
echo $Form->script();

if ($opc == 'buscar' || $opc == 'eliminar') {
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
			nuovaFinestra('Agregar_Actividad', 670, 350, 'agregar_actividades.php?popup=1');
		});
	});

	function EditarActividad(id) {
		var url = 'agregar_actividades.php?id_actividad=' + id + '&popup=1';
		return nuovaFinestra('Editar_Actividad', 670, 350, url);
	}

	function EliminarActividad(id) {
		if (parseInt(id) > 0 && confirm('�Desea eliminar la actividad seleccionada?') == true) {
			var form = document.forms.namedItem('form_actividades');
			form.action = 'actividades.php?desde=<?php echo ($desde) ? $desde : 0 ?>';
			form.id_actividad.value = id;
			form.opc.value = 'eliminar';
			form.submit();
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
