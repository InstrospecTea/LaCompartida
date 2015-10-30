<?php
require_once dirname(__FILE__) . '/../conf.php';

$Sesion = new Sesion(array('DAT'));
$Pagina = new Pagina($Sesion);
$Actividad = new Actividad($Sesion);
$AreaProyecto = new AreaProyecto($Sesion);
$PrmTipoProyecto = new PrmTipoProyecto($Sesion);

$SelectHelper = new FormSelectHelper();
$Form = new Form;

$refresh_parent = false;

if ($opcion == 'guardar') {
	if (Conf::GetConf($Sesion, 'CodigoSecundario')) {
		$asunto = new Asunto($Sesion);
		$codigo_asunto = $asunto->CodigoSecundarioACodigo($codigo_asunto_secundario);
		$_REQUEST['codigo_asunto'] = $codigo_asunto;
	}

	$Actividad->Fill($_REQUEST, true);

	$Actividad->Edit('activo', $_REQUEST['activo'] == 1 ? '1': '0');
	$Actividad->Edit('codigo_asunto', empty($Actividad->fields['codigo_asunto']) ? 'NULL': $Actividad->fields['codigo_asunto']);
	$Actividad->Edit('id_area_proyecto', empty($Actividad->fields['id_area_proyecto']) ? 'NULL': $Actividad->fields['id_area_proyecto']);
	$Actividad->Edit('id_tipo_proyecto', empty($Actividad->fields['id_tipo_proyecto']) ? 'NULL': $Actividad->fields['id_tipo_proyecto']);

	if ($Actividad->Write()) {
		$Pagina->AddInfo(__('Actividad guardada con éxito'));
		if ($Actividad->fields['codigo_asunto'] == 'NULL') {
			$Actividad->Edit('codigo_asunto', null);
		}
		$refresh_parent = true;
	} else {
		$Pagina->AddError(__('Por favor corrija lo siguiente: ') . implode(', ', $Actividad->error));
	}
} else {
	if ($id_actividad != '') {
		$Actividad->Load($id_actividad);
	} else {
		if (empty($codigo_actividad)) {
			$codigo_actividad = $Actividad->AsignarCodigoActividad();
			$Actividad->fields['activo'] = 1;
		}
	}

	$Actividad->Fill($_REQUEST);
}

$Pagina->titulo = __('Actividad');
if ($Actividad->Loaded()) {
	$Pagina->titulo = __('Edición') . ' de ' . $Pagina->titulo . ' N° ' . $Actividad->fields['id_actividad'];

	if (!empty($Actividad->fields['codigo_asunto'])) {
		$Asunto = new Asunto($Sesion);
		$Asunto->LoadByCodigo($Actividad->fields['codigo_asunto']);

		$glosa_asunto = $Asunto->fields['glosa_asunto'];

		$Cliente = new Cliente($Sesion);
		$Cliente->LoadByCodigo($Asunto->fields['codigo_cliente']);

		$glosa_cliente = $Cliente->fields['glosa_cliente'];
		$Actividad->extra_fields['codigo_cliente'] = $Cliente->fields['codigo_cliente'];
	}
}

$Pagina->PrintTop($popup);

if ($refresh_parent) {
	$Form->Html->script_block('if (window.opener !== undefined && window.opener.Refrescar) {window.opener.Refrescar();}');
}
?>
<script type="text/javascript">
	jQuery(document).ready(function() {
		var glosa_actividad = jQuery('#glosa_actividad').val();

		if (window.opener !== undefined && window.opener.Refrescar) {
			window.opener.Refrescar();
		}

		if (!glosa_actividad){
			jQuery('#td_check_activo').hide();
			jQuery('#td_text_activo').hide();
		} else {
			jQuery('#td_check_activo').show();
			jQuery('#td_text_activo').show();
		}

		jQuery('#form_actividades').submit(function(e) {
			if (!Validar()) {
				e.preventDefault();
			}
		});
	});

	function Validar() {
		if (jQuery('#codigo_actividad').val() == '') {
			alert('Debe ingresar un código.');
			jQuery('#codigo_actividad').focus();
			return false;
		}

		if (jQuery('#glosa_actividad').val() == '') {
			alert('Debe ingresar un título.');
			jQuery('#glosa_actividad').focus();
			return false;
		}

		if (jQuery('#codigo_cliente').val() && !jQuery('#codigo_asunto').val()) {
			alert('Si selecciona un cliente debe seleccionar un asunto.');
			return false;
		}

		if (jQuery('#codigo_cliente_secundario').size() && jQuery('#codigo_asunto_secundario').size()) {
			if (jQuery('#codigo_cliente_secundario').val() && !jQuery('#codigo_asunto_secundario').val()) {
				alert('Si selecciona un cliente debe seleccionar un asunto.');
				return false;
			}
		}

		return true;
	}
</script>

<?php
echo $Form->create('form_actividades', array('method' => 'POST', 'action' => '#'));
echo $Form->hidden('opcion', 'guardar', array('id' => 'opcion'));
echo $Form->hidden('id_actividad', $Actividad->fields['id_actividad'], array('id' => 'id_actividad'));
?>
	<fieldset class="border_plomo tb_base">
		<legend><?php echo __('Ingreso de Actividades'); ?></legend>
		<table style="border: 1px solid #BDBDBD;" class="" width="100%">
			<tr>
				<td align="right">
					<?php echo __('Código'); ?>
				</td>
				<td align="left">
					<?php echo $Form->input(
						'codigo_actividad',
						empty($codigo_actividad) ? $Actividad->fields['codigo_actividad'] : $codigo_actividad,
						array('size' => 5, 'maxlength' => 5, 'label' => false)
					); ?>
				</td>
			</tr>
			<tr>
				<td align="right">
					<?php echo __('Título'); ?>
				</td>
				<td align="left">
					<?php echo $Form->input(
						'glosa_actividad',
						$Actividad->fields['glosa_actividad'],
						array('size' => 35, 'label' => false)
					); ?>
				</td>
			</tr>
			<tr>
				<td align="right">
					<?php echo __('Cliente'); ?>
				</td>
				<td align="left">
					<?php UtilesApp::CampoCliente($Sesion, $Actividad->extra_fields['codigo_cliente'], $codigo_cliente_secundario, $Actividad->fields['codigo_asunto'], $codigo_asunto_secundario); ?>
				</td>
			</tr>
			<tr>
				<td align="right">
					<?php echo __('Asunto'); ?>
				</td>
				<td align="left">
					<?php UtilesApp::CampoAsunto($Sesion, $Actividad->extra_fields['codigo_cliente'], $codigo_cliente_secundario, $Actividad->fields['codigo_asunto'], $codigo_asunto_secundario); ?>
				</td>
			</tr>
			<?php if ($Actividad->Loaded()) { ?>
				<tr>
					<td align="right"><label for="activo"><?php echo __('Activa'); ?></label></td>
					<td style="text-align:left">
						<?php
						$activo = ($Actividad->fields['activo'] == 1);
						echo $Form->checkbox('activo', 1, $activo, array('id' => 'activo', 'title' => 'Al inactivar no sera listada en ingreso de horas', 'label' => false));
						?>
					</td>
				</tr>
			<?php } ?>
			<tr>
				<td align="right"><?php echo __('Área') . ' ' . __('asunto'); ?></td>
				<td align="left">
					<?php echo $Form->select('id_area_proyecto', $AreaProyecto->Listar('ORDER BY orden ASC'), $Actividad->fields['id_area_proyecto']); ?>
				</td>
			</tr>
			<tr>
				<td align="right"><?php echo __('Categoría') . ' ' . __('asunto'); ?></td>
				<td align="left">
					<?php echo $Form->select('id_tipo_proyecto', $PrmTipoProyecto->Listar('ORDER BY orden, glosa_tipo_proyecto ASC'), $Actividad->fields['id_tipo_proyecto']); ?>
				</td>
			</tr>
		</table>
	</fieldset>
	<br />
	<div class="fl">
		<?php
		echo $Form->icon_submit(__('Guardar'), 'save');
		echo $Form->icon_button(__('Cancelar'), 'exit', array('onclick' => "window.close();"));
		?>
	</div>
<?php
echo $Form->end();
echo $Form->script();
$Pagina->PrintBottom($popup);
