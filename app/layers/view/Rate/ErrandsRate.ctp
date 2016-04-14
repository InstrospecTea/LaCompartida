<?php if ($diseno_nuevo): ?>
<table width="90%" class="tb_base"><tr><td align="center">
<?php endif; ?>

<form name="formulario" id="formulario" method="post" action="" autocomplete="off">
	<input type="hidden" id="id_tramite_tarifa_edicion" name="id_tramite_tarifa_edicion" value="">

	<div style="width:95%; text-align:right; margin-bottom:5px;">
		<input type="button" id="crear_nueva_tarifa" value="<?php echo __('Crear nueva tarifa'); ?>" class="btn">
		<label><input type="checkbox" id="usar_tarifa_previa" value="1" <?php echo $usar_tarifa_previa ? 'checked' : '' ?> /><?php echo __('Copiar Datos'); ?></label>
	</div>
	<table width="95%" border="0" style="border: 1px solid #BDBDBD">
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
		<table width="95%" border="1" style="border-top: 1px solid #BDBDBD; border-right: 1px solid #BDBDBD; border-left:1px solid #BDBDBD;	border-bottom: none" cellpadding="3" cellspacing="3" id="tbl_tarifa">
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
		<table width="100%" border="1" style="border-top: 1px solid #454545; border-right: 1px solid #454545; border-left:1px solid #454545;	border-bottom: none;" cellpadding="3" cellspacing="3" id="tbl_tarifa">
			<tr bgcolor=#6CA522>
				<td align="left"><b><?php echo __("Tramite")?></b></td>
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
		<?php endif; ?>
</form>

<?php if ($diseno_nuevo): ?>
</td></tr></table>
<?php endif; ?>

<?php
	echo $this->Html->script(Conf::RootDir() . '/app/layers/assets/js/errands_rate.js');
	echo $this->Html->css(Conf::RootDir() . '/app/layers/assets/css/errands_rate.css');
