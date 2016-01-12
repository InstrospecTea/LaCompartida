<form method="post" name="formulario" action="">
	<table width="90%">
		<tr>
			<td>
				<fieldset class="border_plomo tb_base">
					<legend>
						<?php echo __('Filtros'); ?>
					</legend>
					<table style=" width: 90%;" cellpadding="4">
						<tr>
							<td align="right">
								<?php echo __('Fecha desde'); ?>:
							</td>
							<td align="left">
								<?php echo $Html::PrintCalendar('fecha1', $fecha1); ?>
							</td>
						</tr>
						<tr>
							<td align="right">
								<?php echo __('Fecha hasta'); ?>:
							</td>
							<td align="left">
								<?php echo $Html::PrintCalendar('fecha2', $fecha2); ?>
							</td>
						</tr>
						<tr>
							<td align="right">
								<?php echo __('Estado del Cobro'); ?>:
							</td>
							<td align="left">
								<select name="estado" id="estado">
									<option value="todos"><?php echo __('Todos'); ?></option>
									<option value="CREADO"><?php echo __('Creado'); ?></option>
									<option value="EN REVISION"><?php echo __('En Revisión'); ?></option>
									<option value="EMITIDO"><?php echo __('Emitido'); ?></option>
									<option value="FACTURADO"><?php echo __('Facturado'); ?></option>
									<!--<option value="enviado"><?php echo __('Facturado') ?></option>-->
									<option value="ENVIADO"><?php echo __('Enviado al Cliente'); ?></option>
									<option value="PAGO PARCIAL"><?php echo __('Pago Parcial'); ?></option>
									<option value="PAGADO"><?php echo __('Pagado'); ?></option>
								</select>
							</td>
						</tr>

						<tr>
							<td align="right">
								<?php echo __("Encargado"); ?>:
							</td>
							<td align="left"><!-- Nuevo Select -->
								<?php echo $Form->select('usuarios[]', $listaUsuarios, null, array('empty' => FALSE, 'style' => 'width: 200px', 'class' => 'selectMultiple', 'multiple' => 'multiple', 'size' => '6')); ?>
							</td>
						</tr>
						<tr>
							<td align="center" colspan="2">
								<input type="submit" class="btn" value="<?php echo __('Generar reporte'); ?>" name="btn_reporte">
							</td>
						</tr>
					</table>
				</fieldset>
			</td>
		</tr>
	</table>
</form>
