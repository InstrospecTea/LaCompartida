<?php echo $this->Html->css(Conf::RootDir() . '/app/layers/assets/css/clientOldDueAccountingConcepts.css'); ?>

<form method="post" name="form" action="">
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
					'id_encargado_comercial',
					UsuarioExt::QueryComerciales($this->Session),
					$id_encargado_comercial,
					array('empty' => __('Ninguno'))
				); ?>
			</td>
		</tr>
		<tr>
			<td align="right">
				<label for="solo_monto_facturado"><?php echo __('Considerar s�lo monto facturado'); ?></label>
			</td>
			<td align="left">
				<?php echo $this->Form->checkbox('solo_monto_facturado', 1, !empty($this->data['solo_monto_facturado']), array('label' => false)); ?>
				<div class="inlinehelp help" title="<?php echo __('S�lo monto facturado'); ?>" help="<?php echo __('El reporte por defecto considera el saldo liquidado de cada liquidaci�n. Active este campo para considerar s�lo el saldo facturado'); ?>">?</div>
			</td>
		</tr>
		<tr>
			<td align="right">
				<label for="totales_especiales"><?php echo __('Incluir totales normales y vencidos'); ?></label>
			</td>
			<td align="left">
				<?php echo $this->Form->checkbox('totales_especiales', 1, !empty($this->data['totales_especiales']), array('label' => false)); ?>
				<div class="inlinehelp help" title="<?php echo __('Totales normales y vencidos'); ?>" help="<?php echo __('Incluir en el reporte el c�lculo de montos totales, tanto normales como vencidos'); ?>">?</div>
			</td>
		</tr>
		<tr>
			<td align="right">
				<label for="mostrar_detalle"><?php echo __('Desglosar reporte'); ?></label>
			</td>
			<td align="left">
				<?php echo $this->Form->checkbox('mostrar_detalle', 1, !empty($this->data['mostrar_detalle']), array('label' => false)); ?>
				<div class="inlinehelp help" title="<?php echo __('Desglosar reporte'); ?>" help="<?php echo __('El reporte por defecto solo muestra los totales agrupados para cada resultado que se obtiene. Active esta opci�n para mostrar el detalle de cada agrupaci�n de totales'); ?>">?</div>
			</td>
		</tr>
		<tr>
			<td align="right">
				<label for="encargado_comercial"><?php echo __('Incluir encargado comercial'); ?></label>
			</td>
			<td align="left">
				<?php echo $this->Form->checkbox('encargado_comercial', 1, !empty($this->data['encargado_comercial']), array('label' => false)); ?>
				<div class="inlinehelp help" title="<?php echo __('Incluir encargado comercial'); ?>" help="<?php echo __('Incluye en el reporte informaci�n respecto del encargado comercial asociado a los clientes'); ?>">?</div>
			</td>
		</tr>
		<tr>
			<td align="right">
				<?php echo __('Grupo Cliente'); ?>
			</td>
			<td align="left">
				<?php echo $this->Form->select('id_grupo_cliente', GrupoCliente::obtenerGruposSelect($this->Session), $id_grupo_cliente); ?>
			</td>
		</tr>
		<tr>
			<td align="right">
				<label for="tipo_liquidacion"><?php echo __('Tipo de Liquidaci�n'); ?></label>
			</td>
			<td align="left">
				<?php echo Html::SelectArray(
					array(
						array('1', __('S�lo Honorarios')),
						array('2', __('S�lo Gastos')),
						array('3', __('S�lo Mixtas (Honorarios y Gastos)'))
					),
					'tipo_liquidacion',
					$_REQUEST['tipo_liquidacion'],
					'',
					__('Todas')
				); ?>
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
			<td align="right">&nbsp;</td>
			<td align="left">
				<label>
					<?php echo $this->Form->checkbox('display_tax', 1, !empty($this->data['display_tax']), array('label' => false)); ?>
					<?php echo __('Mostrar valores con impuesto'); ?>
				</label>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td colspan="4" align="center">
				<?php echo $this->Form->submit(__('Buscar')); ?>
				<?php echo $this->Form->submit(__('Descargar Excel')); ?>
			</td>
		</tr>
	</table>
</form>

<?php echo $this->Html->script('//static.thetimebilling.com/js/bootstrap.min.js'); ?>
<?php echo $this->Html->script(Conf::RootDir() . '/app/layers/assets/js/clientOldDueAccountingConcepts.js'); ?>
