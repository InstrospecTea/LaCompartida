<?php
require_once dirname(__FILE__) . '/../conf.php';

$Sesion = new Sesion(array('DAT'));
$Pagina = new Pagina($Sesion);

$Actividad = new Actividad($Sesion);

if ($opcion == 'guardar') {
	$Actividad->Fill($_REQUEST, true);

	if ($Actividad->Write()) {
		$Pagina->AddInfo(__('Actividad guardada con éxito'));

		echo '<script type="text/javascript">
			if (window.opener !== undefined && window.opener.Refrescar) {
				window.opener.Refrescar();
			}
			</script>';
	} else {
		$Pagina->AddError(__('Por favor corrija lo siguiente: ') . implode(', ', $Actividad->error));
	}
} else {
	if ($id_actividad != '') {
		$Actividad->Load($id_actividad);
	} else {
		if (empty($codigo_actividad)) {
			$codigo_actividad = $Actividad->AsignarCodigoActividad();
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
?>

<script type="text/javascript">
	function Validar(p) {
		if (document.getElementById('codigo_actividad').value == '') {
			alert('Debe ingresar un código.');
			document.getElementById('codigo_actividad').focus();
			return false;
		}
		if (document.getElementById('glosa_actividad').value == '') {
			alert('Debe ingresar un título.');
			document.getElementById('glosa_actividad').focus();
			return false;
		}

		document.getElementById('form_actividades').submit();

		return true;
	}
</script>

<form method="POST" action="#" name="form_actividades" id="form_actividades">
	<input type="hidden"  name="opcion" id="opcion" value="guardar">
	<input type="hidden" name="id_actividad" value="<?php echo $Actividad->fields['id_actividad']; ?>" />

	<fieldset class="border_plomo tb_base">
		<legend>Ingreso de Actividades</legend>
		<table style="border: 1px solid #BDBDBD;" class="" width="100%">
			<tr>
				<td align="right">
					<?php echo __('Código'); ?>
				</td>
				<td align="left">
					<input id="codigo_actividad" name="codigo_actividad" size="5" maxlength="5" value="<?php echo empty($codigo_actividad) ? $Actividad->fields['codigo_actividad'] : $codigo_actividad; ?>" />
				</td>
			</tr>
			<tr>
				<td align="right">
					<?php echo __('Título'); ?>
				</td>
				<td align="left">
					<input id='glosa_actividad' name='glosa_actividad' size='35' value="<?php echo $Actividad->fields['glosa_actividad']; ?>" />
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
		</table>
	</fieldset>
	<br />
	<div class="fl">
		<a class="btn botonizame" href="javascript:void(0);" icon="ui-icon-save" onclick="Validar(jQuery('#form_actividades').get(0))"><?php echo __('Guardar'); ?></a>
		<a class="btn botonizame" href="javascript:void(0);" icon="ui-icon-exit" onclick="window.close();" ><?php echo __('Cancelar'); ?></a>
	</div>
</form>
<?php $Pagina->PrintBottom($popup);
