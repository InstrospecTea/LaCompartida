<?php
require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion(array('COB'));
$pagina = new Pagina($sesion);
$Html = new \TTB\Html;
$documento = new Documento($sesion);

if ($id_documento != '') {
	$documento->Load($id_documento);
	$codigo_cliente = $documento->fields['codigo_cliente'];
}

if ($opcion == 'guardar') {

	$monto_honorarios = str_replace(',', '.', $monto_honorarios);
	$monto_gastos = str_replace(',', '.', $monto_gastos);

	$monto = $monto_honorarios + $monto_gastos;

	// Documento de Cobro: tipo de documento = 3
	$documento->Edit('id_tipo_documento', 3);

	$moneda = new Moneda($sesion);
	$moneda->Load($id_moneda);
	$moneda_base = Utiles::MonedaBase($sesion);

	$monto_base = $monto * $moneda->fields['tipo_cambio'] / $moneda_base['tipo_cambio'];

	$documento->Edit('honorarios', number_format($monto_honorarios, $moneda->fields['cifras_decimales'], '.', ''));
	$documento->Edit('gastos', number_format($monto_gastos, $moneda->fields['cifras_decimales'], '.', ''));
	$documento->Edit('saldo_honorarios', number_format($monto_honorarios, $moneda->fields['cifras_decimales'], '.', ''));
	$documento->Edit('saldo_gastos', number_format($monto_gastos, $moneda->fields['cifras_decimales'], '.', ''));

	if ($monto_gastos == 0) {
		$documento->Edit('gastos_pagados', 'SI');
	} else if ($monto_honorarios == 0) {
		$documento->Edit('honorarios_pagados', 'SI');
	}

	$documento->Edit('monto', number_format($monto, $moneda->fields['cifras_decimales'], '.', ''));
	$documento->Edit('monto_base', number_format($monto_base, $moneda_base['cifras_decimales'], '.', ''));

	$documento->Edit('tipo_doc', $tipo_doc);
	$documento->Edit('numero_doc', $numero_doc);
	$documento->Edit('id_moneda', $id_moneda);
	$documento->Edit('fecha', Utiles::fecha2sql($fecha));
	$documento->Edit('glosa_documento', $glosa_documento);
	$documento->Edit('codigo_cliente', $codigo_cliente);

	if ($documento->Write()) {
		$id_documento = $documento->fields['id_documento'];
		$pagina->addInfo(__('Pago ingresado con éxito'));
	} else {
		$pagina->AddError($documento->error);
	}
}

$txt_pagina = $id_documento ? __('Edición de Documento de') . ' ' . __('Cobro') : __('Documento de') . ' ' . __('Cobro');
$txt_tipo = __('Documento de') . ' ' . __('Cobro');

$pagina->titulo = $txt_pagina;
$pagina->PrintTop($popup);
?>
<form method="post" action="<?php echo $SERVER[PHP_SELF]; ?>" id="form_documentos" autocomplete="off">
	<input type="hidden" name="opcion" value="guardar" />
	<input type="hidden" name="id_documento" value="<?php echo $documento->fields['id_documento']; ?>" />
	<input type="hidden" name="pago" value="<?php echo $pago; ?>" />
	<input type="hidden" name="elimina_ingreso" id="elimina_ingreso" value="" />
	<br>
	<table width='90%'>
		<tr>
			<td align="left"><b><?php echo $txt_pagina; ?></b></td>
		</tr>
	</table>
	<br>
	<table style="border: 0px solid black;" width="90%">
		<tr>
			<td align="left">
				<b><?php echo __('Información de Documento'); ?> </b>
			</td>
		</tr>
	</table>
	<table style="border: 1px solid black;" width="90%">
		<tr>
			<td align="right">
				<?php echo __('Fecha'); ?>
			</td>
			<td align="left">
				<?php echo $Html::PrintCalendar('fecha', Utiles::sql2date($documento->fields['fecha'])); ?>
			</td>
		</tr>
		<tr>
			<td align="right" width="30%"><?php echo __('Cliente '); ?></td>
			<td colspan="3" align="left">
				<?php echo InputId::ImprimirSinCualquiera($sesion, 'cliente', 'codigo_cliente', 'glosa_cliente', 'codigo_cliente', $codigo_cliente, ' ', '', 280); ?>
				<span style="color:#FF0000; font-size:10px">*</span>
			</td>
		</tr>
		<tr>
			<td align="right">
				<?php echo __('Monto Honorarios'); ?>
			</td>
			<td align="left">
				<input name="monto_honorarios" id="monto_honorarios" size="10" value="<?php echo str_replace('-', '', $documento->fields['honorarios']); ?>" />
				<span style="color:#FF0000; font-size:10px">*</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<?php echo __('Moneda'); ?>&nbsp;
				<?php echo Html::SelectQuery($sesion, "SELECT id_moneda, glosa_moneda FROM prm_moneda ORDER BY id_moneda", 'id_moneda', $documento->fields['id_moneda'] ? $documento->fields['id_moneda'] : '', '', '', '80'); ?>
				<span style="color:#FF0000; font-size:10px">*</span>
			</td>
		</tr>
		<tr>
			<td align="right">
				<?php echo __('Monto Gastos'); ?>
			</td>
			<td align="left">
				<input name="monto_gastos" id="monto_gastos" size="10" value="<?php echo str_replace('-', '', $documento->fields['gastos']); ?>" />
				<span style="color:#FF0000; font-size:10px">*</span>
			</td>
		</tr>
		<tr>
			<td align="right">
				<?php echo __('Descripción'); ?>
			</td>
			<td align=left>
				<textarea name="glosa_documento" id="glosa_documento" cols="45" rows="3"><?php echo $documento->fields['glosa_documento'] ? $documento->fields['glosa_documento'] : 'Cobro externo al sistema de Time Tracking.'; ?></textarea>
				<span style="color:#FF0000; font-size:10px">*</span>
			</td>
		</tr>
		<tr>
			<td align="right" colspan="2">&nbsp;</td>
		</tr>
	</table>
	<br>
	<table style="border: 0px solid black;" width="90%">
		<tr>
			<td align="center">
				<input type="submit" class="btn" value="<?php echo __('Guardar'); ?>" />
				<input type="button" class="btn" value="<?php echo __('Cerrar'); ?>" onclick="Cerrar();" />
			</td>
		</tr>
	</table>
</form>

<script type="text/javascript">
jQuery(function() {
	jQuery('#form_documentos').submit(function(e) {
		var codigo_cliente = jQuery('#codigo_cliente option:selected').text().trim();
		if (codigo_cliente == '' || codigo_cliente == '-1') {
			alert('<?php echo __('Debe seleccionar un cliente'); ?>');
			jQuery('#codigo_cliente').focus();
			e.preventDefault();
			return;
		}
		if (jQuery('#monto_honorarios').val() == '') {
			alert('<?php echo __('Debe ingresar un monto para el pago'); ?>');
			jQuery('#monto_honorarios').focus();
			e.preventDefault();
			return;
		}
		if (jQuery('#monto_gastos').val() == '') {
			alert('<?php echo __('Debe ingresar un monto para el pago'); ?>');
			jQuery('#monto_gastos').focus();
			e.preventDefault();
			return;
		}
		if (jQuery('#glosa_documento').val() == '') {
			alert('<?php echo __('Debe ingresar una descripción'); ?>');
			jQuery('#glosa_documento').focus();
			e.preventDefault();
			return;
		}
	});
});
</script>

<?php
echo InputId::Javascript($sesion);
$pagina->PrintBottom($popup);
