<?php
require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion(array('COB'));
$pagina = new Pagina($sesion);
$lista = new ListaMonedas($sesion, '', 'SELECT * FROM prm_moneda');

$pagina->titulo = __('Tipo de Cambio');

if ($opc == 'guardar') {
	$moneda = new Moneda($sesion);
	$error = false;
	$fecha_historial = date('Y-m-d H:i:s');

	for ($x = 0; $x < $lista->num; $x++) {
		$_moneda = $lista->Get($x);
		$moneda->Load($_moneda->fields['id_moneda']);

		if ($moneda->fields['tipo_cambio_referencia'] == 1) {
			$moneda->Edit('tipo_cambio', '1');
		} else {
			$moneda->Edit('tipo_cambio', ${'valor_' . $moneda->fields['id_moneda']});
		}

		$moneda->Edit('cifras_decimales', ${'decimales_' . $moneda->fields['id_moneda']});

		if (!$moneda->Write()) {
			$error = true;
		} else {
			$moneda->GuardaHistorial($sesion, $fecha_historial);
		}
	}

	if (!$error) {
		$pagina->AddInfo(__('Datos actualizados correctamente'));
	}

	// actualizar lista
	$lista = new ListaMonedas($sesion, '', 'SELECT * FROM prm_moneda');
}

$pagina->PrintTop();
?>

<style type="text/css">.txt_input { text-align: right; }</style>

<form name="formulario" id="formulario" method="post" autocomplete="off">
	<input type="hidden" name="opc" value="guardar">
	<table width="50%" align="center" class="border_plomo tb_base" cellspacing="5" border="0">
		<thead>
			<tr>
				<td>
					<strong><?php echo __('Moneda'); ?></strong>
				</td>
				<td>
					<strong><?php echo __('Tasa'); ?></strong>
				</td>
				<td>
					<strong><?php echo __('Decimales'); ?></strong>
				</td>
			</tr>
		</thead>
		<tbody>
			<?php for($x = 0; $x < $lista->num; $x++) { ?>
				<?php $moneda = $lista->Get($x); ?>
				<tr>
					<td align="right">
						<?php echo $moneda->fields['glosa_moneda']; ?>:
						<?php echo $moneda->fields['moneda_base'] ? '<br/><small style="color:#999;"">(moneda base)</small>' : ''; ?>
					</td>
					<td>
						<input type="text" class="txt_input" size="10" value="<?php echo $moneda->fields['tipo_cambio']; ?>" <?php echo $moneda->fields['tipo_cambio_referencia'] ? 'disabled' : ''; ?> name="valor_<?php echo $moneda->fields['id_moneda']; ?>" id="valor_<?php echo $moneda->fields['id_moneda']; ?>">
					</td>
					<td>
						<input type="text" class="txt_input decimales" size="2" value="<?php echo $moneda->fields['cifras_decimales']; ?>" readonly="readonly" name="decimales_<?php echo $moneda->fields['id_moneda']; ?>" id="decimales_<?php echo $moneda->fields['id_moneda']; ?>">
					</td>
				</tr>
			<?php } ?>
			<tr>
				<td colspan="3">&nbsp;</td>
			</tr>
			<tr>
				<td colspan="3" align="center">
					<input type="submit" class="btn" value="<?php echo __('Guardar'); ?>" onclick="return Validar(this.form);">
				</td>
			</tr>
		</tbody>
	</table>
</form>

<script>
jQuery('document').ready(function() {
	jQuery('.decimales').dblclick(function() {
		jQuery(this).removeAttr('readonly');
	});

	jQuery('.decimales').each(function() {
		var decimales = jQuery(this).val();
		var str_decimales = '';
		for(var n = decimales; n > 0; n--) {
			str_decimales += '0';
		}
		var targetcell = jQuery(this).attr('id').replace('decimales_', 'valor_');
		jQuery('#' + targetcell).formatNumber({format: '0.' + str_decimales, locale: 'us'});
	});

	jQuery('.decimales').on('keyup',function() {
		var decimales = jQuery(this).val();
		var str_decimales = '';
		for(var n = decimales; n > 0; n--) {
			str_decimales += '0';
		}
		var targetcell = jQuery(this).attr('id').replace('decimales_', 'valor_');
		jQuery('#' + targetcell).formatNumber({format: '0.' + str_decimales, locale: 'us'});
	});

	<?php
	for ($x = 0; $x < $lista->num; $x++) {
			$moneda = $lista->Get($x);
			$cf = $moneda->fields['cifras_decimales'];
			if ($cf > 0) {
				$dec = '.';
				while ($cf-- > 0) {
					$dec .= '0';
				}
			}
	?>

		jQuery('#valor_<?php echo $moneda->fields['id_moneda']; ?>').blur(function() {
			var str = jQuery(this).val();
			jQuery(this).val( str.replace(',', '.'));
			jQuery(this).parseNumber({format: '#<?php echo $dec; ?>', locale: 'us'});
			jQuery(this).formatNumber({format: '#<?php echo $dec; ?>', locale: 'us'});
		});

	<?php } ?>
});

function Validar(form) {
	var errores = '';
	<?php for ($x = 0; $x < $lista->num; $x++) { ?>
		<?php $moneda = $lista->Get($x); ?>
		if (form.<?php echo 'valor_' . $moneda->fields['id_moneda']; ?>.value.length == 0 || form.<?php echo 'valor_' . $moneda->fields['id_moneda']; ?>.value == 0) {
			errores += "- <?php echo $moneda->fields['glosa_moneda']; ?> \n";
		}
	<?php } ?>

	if (errores.length > 0) {
		alert("<?php echo __('Se encontraron errores al guardar los tipos de cambio, por favor revise los siguientes valores'); ?>: \n\n" + errores + "<?php echo __('\ne intentelo nuevamente'); ?>");
		return false;
	}

	return true;
}

</script>

<?php $pagina->PrintBottom(); ?>
