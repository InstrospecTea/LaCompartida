<tr class="inovice-data">
	<td align="right" width="100">&nbsp;</td>
	<td align="right" style="vertical-align:bottom" width="250"><?php echo __('Subtotal Honorarios'); ?></td>
	<td align="left" width="100">
		<?php echo $currency->get('simbolo'); ?>
		<input type="text" disabled="true" id="subtotal_honorarios_bruto" value="<?php 
				echo $feeDetiail->get('subtotal_honorarios');
			?>"/>
	</td>
	<td align="left">&nbsp;</td>	
</tr>
<tr class="inovice-data">
	<td align="right" width="100">&nbsp;</td>
	<td align="right" style="vertical-align:bottom" width="250"><?php echo __('Descuento Honorarios'); ?></td>
	<td align="left" width="100">
		<?php echo $currency->get('simbolo'); ?>
		<input type="text" disabled="true" id="subtotal_honorarios_bruto" value="<?php 
				echo $feeDetiail->get('descuento_honorarios');
			?>"/>
	</td>
	<td align="left">&nbsp;</td>	
</tr>