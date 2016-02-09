<?php echo $this->Html->css(Conf::RootDir() . '/app/layers/assets/css/clientOldDebtAccountingConcepts.css'); ?>

<?php echo $this->Form->create('client_old_debt_accounting_concepts', array('method' => 'post')); ?>
	<?php echo $this->Form->hidden('option', 'buscar'); ?>
	<table class="border_plomo tb_base" width="80%">
		<tr>
			<td align="right">
				<?php echo __('Cliente'); ?>
			</td>
			<td align="left">
				<?php echo UtilesApp::CampoCliente(
					$this->Session,
					$this->data['client_code'],
					$this->data['client_secondary_code']
				); ?>
			</td>
		</tr>
		<?php UtilesApp::FiltroAsuntoContrato(
			$this->Session,
			$this->data['client_code'],
			$this->data['client_secondary_code'],
			$this->data['matter_code'],
			$this->data['matter_secondary_code'],
			$this->data['contract_id']
		); ?>
		<tr>
			<td align="right">
				<?php echo __('Encargado Comercial'); ?>
			</td>
			<td align="left">
				<?php echo $this->Form->select(
					'trade_manager_id',
					UsuarioExt::QueryComerciales($this->Session),
					$this->data['trade_manager_id'],
					array('empty' => __('Ninguno'))
				); ?>
			</td>
		</tr>
		<tr>
			<td align="right">
				<label for="total_special"><?php echo __('Incluir totales normales y vencidos'); ?></label>
			</td>
			<td align="left">
				<?php echo $this->Form->checkbox('total_special', 1, !empty($this->data['total_special']), array('label' => false)); ?>
				<div class="inlinehelp help" title="<?php echo __('Totales normales y vencidos'); ?>" help="<?php echo __('Incluir en el reporte el cálculo de montos totales, tanto normales como vencidos'); ?>">?</div>
			</td>
		</tr>
		<tr>
			<td align="right">
				<label for="show_detail"><?php echo __('Desglosar reporte'); ?></label>
			</td>
			<td align="left">
				<?php echo $this->Form->checkbox('show_detail', 1, !empty($this->data['show_detail']), array('label' => false)); ?>
				<div class="inlinehelp help" title="<?php echo __('Desglosar reporte'); ?>" help="<?php echo __('El reporte por defecto solo muestra los totales agrupados para cada resultado que se obtiene. Active esta opción para mostrar el detalle de cada agrupación de totales'); ?>">?</div>
			</td>
		</tr>
		<tr>
			<td align="right">
				<label for="include_trade_manager"><?php echo __('Incluir encargado comercial'); ?></label>
			</td>
			<td align="left">
				<?php echo $this->Form->checkbox('include_trade_manager', 1, !empty($this->data['include_trade_manager']), array('label' => false)); ?>
				<div class="inlinehelp help" title="<?php echo __('Incluir encargado comercial'); ?>" help="<?php echo __('Incluye en el reporte información respecto del encargado comercial asociado a los clientes'); ?>">?</div>
			</td>
		</tr>
		<tr>
			<td align="right">
				<?php echo __('Grupo Cliente'); ?>
			</td>
			<td align="left">
				<?php echo $this->Form->select('client_group_id', $client_group, $this->data['client_group_id']); ?>
			</td>
		</tr>
		<tr>
			<td align="right">
				<label for="billing_type"><?php echo __('Tipo de Liquidación'); ?></label>
			</td>
			<td align="left">
				<?php echo Html::SelectArray($billing_type, 'billing_type', $this->data['billing_type'], '', __('Todas')); ?>
			</td>
		</tr>
		<tr>
			<td align="right">
				<?php echo __('Fecha de corte'); ?>
			</td>
			<td align="left">
				<?php echo $this->Html->PrintCalendar('end_date', $this->data['end_date']); ?>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td colspan="2" align="center">
				<?php echo $this->Form->button(__('Buscar'), array('id' => 'button_search')); ?>
				<?php echo $this->Form->button(__('Descargar Excel'), array('id' => 'button_download_excel')); ?>
			</td>
		</tr>
	</table>
<?php $this->Form->end(); ?>

<div id="seguimiento_template">
	<div class="popover left">
		<div class="arrow"></div>
		<h3 class="popover-title"><?php echo __('Seguimiento del cliente'); ?></h3>
		<div class="popover-content">
			<iframe id="seguimiento_iframe" width="100%" border="0" style="border: 1px solid white" src="../ajax/ajax_seguimiento.php"></iframe>
		</div>
	</div>
</div>

<script type="text/javascript">
	var img_dir = '<?php echo Conf::ImgDir(); ?>';
	var root_dir = '<?php echo Conf::RootDir(); ?>';
</script>

<?php echo $this->Html->script('//static.thetimebilling.com/js/bootstrap.min.js'); ?>
<?php echo $this->Html->script(Conf::RootDir() . '/app/layers/assets/js/clientOldDebtAccountingConcepts.js'); ?>

<?php if ($Report) { ?>
	<div class="simple_report_html">
		<?php $Report->render(); ?>
	</div>
<?php } ?>
