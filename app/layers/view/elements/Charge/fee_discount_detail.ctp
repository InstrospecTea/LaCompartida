<?php $this->Formatter = new Formatter(); ?> 
<table width="100%" style="border-collapse: collapse;">
	<tr>
		<td><?php echo __('Subtotal Honorarios'); ?></td>
		<td style="text-align:right">
			<?php 
				echo $this->Formatter->currency($feeDetiail->get('subtotal_honorarios'), $currency, $language);
			?>
		</td>
	</tr>
	<tr>
	<td><?php echo __('Descuento'); ?></td>
		<td style="text-align:right"><?php echo $this->Formatter->currency($feeDetiail->get('descuento_honorarios'), $currency, $language) ?></td>
	</tr>
	<tr style="border-top: solid 1px #888;">
		<td ><b><?php echo __('Total Honorarios'); ?></b></td>
		<td style="text-align:right;"><b><?php echo $this->Formatter->currency($feeDetiail->get('saldo_honorarios'), $currency, $language) ?></b></td>
	</tr>	
</table>