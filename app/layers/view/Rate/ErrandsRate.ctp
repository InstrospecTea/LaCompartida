<?php if ($diseno_nuevo): ?>
<table width="90%" class="tb_base"><tr><td align="center">
<?php endif; ?>

<form name="formulario" id="formulario" method="post" action="" autocomplete="off">
	<input type="hidden" id="id_tramite_tarifa_edicion" name="id_tramite_tarifa_edicion" value="">

	<div style="width:95%; text-align:right; margin-bottom:5px;">
		<input type="button" id="crear_nueva_tarifa" value='<?php echo __('Crear nueva tarifa'); ?>' class="btn">
		<label><input type="checkbox" id="usar_tarifa_previa" value="1" <?php echo $usar_tarifa_previa ? 'checked' : '' ?> /><?php echo __('Copiar Datos'); ?></label>
	</div>
	<table width='95%' border="0" style="border: 1px solid #BDBDBD">
		<tr valign="middle">
			<td align="right" class="edicion_tarifa"><?php echo __('Tarifa'); ?>:</td>
			<td align="left" class="edicion_tarifa">
				<select id="id_tramite_tarifa" name="id_tramite_tarifa">
				<?php foreach($rates as $rate): ?>
					<option value="<?php echo $rate['id_tramite_tarifa'] ?>" <?php echo $rate['tarifa_defecto'] ? 'selected' : '' ?>>
						<?php echo utf8_decode($rate['glosa_tramite_tarifa']) ?>
					</option>
				<?php endforeach; ?>
				</select>
			</td>
			<td>
				<label>
					<span class="edicion_tarifa">
						<?php echo __('Nombre')?>:
					</span>
					<span class="hide nueva_tarifa">
						<?php echo __('Nueva Tarifa'); ?>:
					</span>
					<input type="text" name="glosa_tramite_tarifa" id="glosa_tramite_tarifa" value="">
				</label>
			</td>
			<td>
				<input type="hidden" name="tarifa_defecto" value="">
				<label id="label_tarifa_defecto" class="edicion_tarifa">
					<b><?php echo __('Tarifa por Defecto')?></b>
				</label>
				<label id="tarifa_no_defecto" class="hide nueva_tarifa">
					<input type="checkbox" name="checkbox_tarifa_defecto" id="checkbox_tarifa_defecto"> <?php echo __('Defecto')?>
				</label>
			</td>
			<td align="right">
				<button type="button" id="guardar_tarifa" class=""><?php echo __('Guardar'); ?></button>
				<button type="button" id="eliminar_tarifa" class="btn_rojo edicion_tarifa"><?php echo __('Eliminar Tarifa'); ?></button>
				<button type="button" id="cancelar_nueva_tarifa" class="hide btn_rojo nueva_tarifa"><?php echo __('Cancelar'); ?></button>
			</td>
		</tr>
	</table>

	<br>
	<?php if ($diseno_nuevo): ?>
		<table width='95%' border="1" style='border-top: 1px solid #BDBDBD; border-right: 1px solid #BDBDBD; border-left:1px solid #BDBDBD;	border-bottom: none' cellpadding="3" cellspacing="3" id='tbl_tarifa'>
			<tr bgcolor="#A3D55C">
				<td align="left" class="border_plomo"><b><?php echo __("Tramite")?></b></td>
				<?php foreach($coins as $coin): ?>
				<td align="center" class="border_plomo"><b><?php echo $coin ?></b></td>
				<?php endforeach; ?>
			</tr>
			<?php foreach($errands_rate_table as $errand => $errand_rate): ?>
			<tr>
				<td align="left" class="border_plomo"><?php echo $errand ?></td>
				<?php foreach($errand_rate as $coin): ?>
				<td align="right" class="border_plomo"><input type="text" size="6" id="" class="tarifas" name="tarifa_moneda[<?php echo $coin->id_moneda ?>][<?php echo $coin->id_tramite_tipo ?>]" value="" data-errandtype="<?php echo $coin->id_tramite_tipo ?>" data-coin="<?php echo $coin->id_moneda ?>"></td>
				<?php endforeach; ?>
			<tr>
			<?php endforeach; ?>
		</table>
		<?php else: ?>
		<table width='100%' border="1" style='border-top: 1px solid #454545; border-right: 1px solid #454545; border-left:1px solid #454545;	border-bottom: none' cellpadding="3" cellspacing="3" id='tbl_tarifa'>
			<tr bgcolor=#6CA522>
				<td align=left><b><?php echo __("Tramite")?></b></td>
				<?php foreach($coins as $coin): ?>
				<td align="center" class="border_plomo"><b><?php echo $coin ?></b></td>
				<?php endforeach; ?>
			</tr>
			<?php foreach($errands_rate_table as $errand => $errand_rate): ?>
			<tr>
				<td align=left class="border_plomo"><?php echo $errand ?></td>
				<?php foreach($errand_rate as $coin): ?>
				<td align="right" class="border_plomo"><input type="text" size="6" id="" class="tarifas" name="tarifa_moneda[<?php echo $coin->id_moneda ?>][<?php echo $coin->id_tramite_tipo ?>]" value="" data-errandtype="<?php echo $coin->id_tramite_tipo ?>" data-coin="<?php echo $coin->id_moneda ?>"></td>
				<?php endforeach; ?>
			<tr>
			<?php endforeach; ?>
		</table>
		<?php endif; ?>
</form>

<?php if ($diseno_nuevo): ?>
</td></tr></table>
<?php endif; ?>


<?php #Aquí comienza la rotería... ?>
<script type="text/javascript">
	var dm_root = root_dir + '/app/Rate/';
	var tarifa_temp = '';

	jQuery(function() {
		jQuery.loadData(jQuery('#id_tramite_tarifa').val());

		jQuery('.tarifas').on('focus', function() {
			tarifa_temp = jQuery.trim(jQuery(this).val());
		});

		jQuery('.tarifas').on('blur', function() {
			if (jQuery.trim(jQuery(this).val()) == '' && tarifa_temp != '') {
				if (confirm('¿Está seguro de querer eliminar la tarifa?\nEsto puede provocar inconsistencia de datos en los trámites ya creados.')) {
					jQuery(this).val('0');
				} else {
					jQuery(this).val(tarifa_temp);
					jQuery(this).focus();
				}
			}
		});

		jQuery('#id_tramite_tarifa').on('change', function() {
			if (confirm('<?php echo __("Confirma cambio de tarifa?"); ?>')) {
				jQuery.loadData(jQuery('#id_tramite_tarifa').val());
			};
		});

		jQuery('#eliminar_tarifa').on('click', function() {
			if (jQuery('[name="tarifa_defecto"]').val() == 0) {
				var num_contratos = jQuery.contractsWithRate(jQuery('#id_tramite_tarifa').val());
				if (num_contratos > 0) {
					if (confirm('<?php echo  __("La tarifa posee"); ?> ' + num_contratos + " <?php echo __('contratos asociados. \nSi continua se le asignará la tarifa estándar a los contratos afectados.\n¿Está seguro de continuar?.'); ?>")) {
						jQuery.changeDefaultRateOnContract(jQuery('#id_tramite_tarifa').val());
					};
				} else if (confirm('¿<?php echo __("Está seguro de eliminar la") . " " . __("tarifa")?>?')) {
					jQuery.deleteRate(jQuery('#id_tramite_tarifa').val());
				};
			} else {
				alert('No puede eliminar la tarifa estándar (por defecto)');
			};
		});

		jQuery('#guardar_tarifa').on('click', function() {
			jQuery.saveRate();
		});

		jQuery('#crear_nueva_tarifa').on('click', function() {
			if (!jQuery('#usar_tarifa_previa').is(':checked')) {
				jQuery('input[type="text"], #id_tramite_tarifa_edicion, input[type="checkbox"], [name="tarifa_defecto"]').val(null);
			} else {
				jQuery('input[type="text"][class!="tarifas"], #id_tramite_tarifa_edicion, input[type="checkbox"], [name="tarifa_defecto"]').val(null);
			};
			jQuery('.edicion_tarifa').hide();
			jQuery('.nueva_tarifa').show();
			jQuery('#usar_tarifa_previa').attr('checked', false);
		});

		jQuery('#cancelar_nueva_tarifa').on('click', function() {
			jQuery('input[type="text"]').val(null);
			jQuery('.edicion_tarifa').show();
			jQuery('.nueva_tarifa').hide();
			jQuery.loadData(jQuery('#id_tramite_tarifa').val());
		});
	});

	jQuery.loadData = function(rate_id) {
		jQuery.ajax({
			url: dm_root + 'ErrandsRateValue',
			data: {
				'id_tarifa': rate_id
			},
			dataType: 'JSON',
			method: 'POST',
			success: function(response) {
				var tarifa;
				jQuery('[name="glosa_tramite_tarifa"]').val(response.errand_rate_detail.glosa_tramite_tarifa);
				jQuery('[name="tarifa_defecto"]').val(response.errand_rate_detail.tarifa_defecto);
				jQuery('[name="id_tramite_tarifa_edicion"]').val(response.errand_rate_detail.id_tramite_tarifa);
				jQuery('.tarifas').val(null);

				jQuery('#label_tarifa_defecto, #tarifa_no_defecto').css({display: 'none'});

				if (response.errand_rate_detail.tarifa_defecto == 1) {
					jQuery('#label_tarifa_defecto').css({display: 'block'});
				} else {
					jQuery('#tarifa_no_defecto').css({display: 'block'});
				};

				for (var i = 0; i < response['errands_rate_values'].length; ++i) {
					tarifa = response['errands_rate_values'][i];
					jQuery('[name="tarifa_moneda[' + tarifa['id_moneda'] + '][' + tarifa['id_tramite_tipo'] + ']"]').val(tarifa['tarifa']);
				};
			},
			error: function() {
				//
			}
		});
	};

	jQuery.deleteRate = function(rate_id) {
		jQuery.ajax({
			url: dm_root + 'deleteErrandRate',
			data: {
				'id_tarifa': rate_id
			},
			dataType: 'JSON',
			method: 'POST',
			success: function(response) {
				if (response.success) {
					setTimeout(function(){
						alert(response.message);
						location.reload();
					}, 1);
				} else {
					alert(success.message);
				};
			},
			error: function(response) {
				//
			}
		});
	};

	jQuery.saveRate = function() {
		var params = {};

		if (parseInt(jQuery('#id_tramite_tarifa_edicion').val())) {
			params.rate_id = jQuery('#id_tramite_tarifa_edicion').val();
		};

		if (jQuery('#checkbox_tarifa_defecto').is(':checked') || jQuery('[name="tarifa_defecto"]').val() == 1) {
			params.tarifa_defecto = 1;
		} else {
			params.tarifa_defecto = 0;
		};

		if (jQuery.trim(jQuery('#glosa_tramite_tarifa').val()) == '') {
			alert('La glosa de la tarifa no puede estar vacía');
			jQuery('#glosa_tramite_tarifa').focus();
			return false;
		};

		params.glosa_tramite_tarifa = jQuery.trim(jQuery('#glosa_tramite_tarifa').val());

		jQuery('.tarifas').each(function() {
			var rate = jQuery(this);
			if (rate.val() != '') {
				if (typeof params.rates == 'undefined') {
					params.rates = [];
				};

				params.rates.push({
					id_tramite_tipo: rate.data('errandtype'),
					id_moneda: rate.data('coin'),
					tarifa: rate.val()
				});
			};
		});

		jQuery.ajax({
			url: dm_root + 'saveErrandRate',
			data: {
				params
			},
			dataType: 'JSON',
			method: 'POST',
			success: function(response) {
				if (response.success) {
					setTimeout(function(){
						alert(response.message);
						location.reload();
					}, 1);
				} else {
					alert(success.message);
				};
			},
			error: function(response) {
				//
			}
		});
	};

	jQuery.contractsWithRate = function(rate_id) {
		var num_contratos = 0;

		jQuery.ajax({
			url: dm_root + 'contractsWithErrandRate',
			data: {
				'id_tarifa': rate_id
			},
			dataType: 'JSON',
			method: 'POST',
			async: false,
			success: function(response) {
				num_contratos = response.num_rows;
			},
			error: function() {
				//
			}
		});

		return num_contratos;
	};

	jQuery.changeDefaultRateOnContract = function(rate_id) {
		var num_contratos = 0;

		jQuery.ajax({
			url: dm_root + 'changeDefaultErrandRateOnContract',
			data: {
				'id_tarifa': rate_id
			},
			dataType: 'JSON',
			method: 'POST',
			async: false,
			success: function(response) {
				if (response.success) {
					jQuery.deleteRate(rate_id);
				};
			},
			error: function() {
				//
			}
		});

		return num_contratos;
	};
</script>

<style>
	#tbl_tarifa {
		font-size: 10px;
		padding: 1px;
		margin: 0px;
		vertical-align: middle;
		border:1px solid #CCCCCC;
	}

	.hide {
		display: none;
	}

	.text_box {
		font-size: 10px;
		text-align:right;
	}

	input[type="text"] {
		border: 1px solid #CCCCCC;
  }

	input[type="text"]:focus {
		border: 2px solid #000000;
	}
</style>

<?php
	// echo $this->Html->css(Conf::RootDir() . '/public/css/bootstrap.css');
