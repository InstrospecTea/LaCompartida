<?php
require_once dirname(__FILE__) . '/../conf.php';

// var_dump($_POST); exit;

$Sesion = new Sesion(array('DAT'));
$Pagina = new Pagina($Sesion);
$Actividad = new Actividad($Sesion);
$Form = new Form();

switch ($opc) {
	case 'eliminar':
		if ($Actividad->Load($_REQUEST['id_actividad'])) {
			if ($Actividad->Delete()) {
				$Pagina->AddInfo(__('Actividad') . ' ' . __('eliminada con éxito'));
			} else {
				$Pagina->AddError($Actividad->error);
				$Actividad = new Actividad($Sesion);
			}
		} else {
			$Pagina->AddError(__('Actividad') . ' ' . __('no existe'));
		}
		break;
	case 'xls':
		$Actividad->Fill($_REQUEST);
		$Actividad->DownloadExcel();
		break;
	case 'buscar':
		$Actividad->Fill($_REQUEST);
		break;
}

$Pagina->titulo = __('Actividades');
$Pagina->PrintTop();

echo $Form->create('form_actividades', array('action' => 'actividades.php'));
echo $Form->hidden('opc', 'buscar');
echo $Form->hidden('desde');
echo $Form->hidden('id_actividad');
?>
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
				<?php echo __('Código'); ?>
			</td>
			<td align="left">
				<?php echo $Form->input('codigo_actividad', $codigo_actividad, array('label' => false, 'size' => 5, 'maxlength' => 5)); ?>
			</td>
		</tr>
		<tr>
			<td align="right">
				<?php echo __('Título'); ?>
			</td>
			<td align="left">
				<?php echo $Form->input('glosa_actividad', $glosa_actividad, array('label' => false, 'size' => 35)); ?>
			</td>
		</tr>
		<tr>
			<td align="right">
				<?php echo __('Cliente'); ?>
			</td>
			<td align="left" nowrap>
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
<?php echo $Form->end(); ?>

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
	$b->AgregarEncabezado('codigo_actividad', __('Código Actividad'), 'align="left"');
	$b->AgregarEncabezado('glosa_actividad', __('Nombre Actividad'), 'align="left"');
	$b->AgregarEncabezado('glosa_asunto', __('Asunto'), 'align="left"');
	$b->AgregarEncabezado('glosa_cliente', __('Cliente'), 'align="left"');
	$b->AgregarEncabezado('codigo_actividad', __('Código'), 'align="left"');
	$b->AgregarFuncion('', 'acciones', 'align="center"');
	$b->color_mouse_over = '#bcff5c';
	$b->Imprimir();
}

function acciones(&$fila) {
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
		if (parseInt(id) > 0 && confirm("<?php echo __('¿Desea eliminar la actividad seleccionada?'); ?>")) {
			jQuery('#form_actividades').attr('action', 'actividades.php');
			jQuery('#id_actividad').val(id);
			jQuery('#opc').val('eliminar');
			jQuery('#desde').val('<?php echo !empty($desde) ? $desde : 0; ?>');
			jQuery('#form_actividades').submit();
		}
	}
</script>

<?php
$Pagina->PrintBottom();
