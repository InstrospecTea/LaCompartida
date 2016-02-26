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
				<label for="include_trade_manager"><?php echo __('Incluir encargado comercial'); ?></label>
			</td>
			<td align="left">
				<?php echo $this->Form->checkbox('include_trade_manager', 1, !empty($this->data['include_trade_manager']), array('label' => false)); ?>
				<div class="inlinehelp help" title="<?php echo __('Incluir encargado comercial'); ?>" help="<?php echo __('Incluye en el reporte información respecto del encargado comercial asociado a los clientes'); ?>">?</div>
			</td>
		</tr>
		<?php if (!empty($client_group)) { ?>
		<tr>
			<td align="right">
				<?php echo __('Grupo Cliente'); ?>
			</td>
			<td align="left">
				<?php echo $this->Form->select('client_group_id', $client_group, $this->data['client_group_id']); ?>
			</td>
		</tr>
		<?php } ?>
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
