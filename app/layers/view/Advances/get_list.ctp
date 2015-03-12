<h2 class="al"><?php echo __('Adelantos'); ?></h2>
<p class="al">
	(<?php echo $Pagination->text(); ?>)
</p>

<?php

echo $this->Paginator->pages($Pagination, true, false, 6);
$this->EntitiesListator->loadEntities($listResults->toArray());
$this->EntitiesListator->addColumn(__('N°'), 'id_documento');
$this->EntitiesListator->addColumn(__('Cliente'), 'glosa_cliente');
$this->EntitiesListator->addColumn(__('Fecha'), 'fecha');
$this->EntitiesListator->addColumn(__('Descripción'), 'glosa_documento');
$this->EntitiesListator->addColumn(__('Monto'), 'monto_con_simbolo');
$this->EntitiesListator->addColumn(__('Saldo'), 'saldo_pago_con_simbolo');

$view = &$this;
if ($this->params['elegir_para_pago']) {

	$this->EntitiesListator->addColumn(__('Elegir para pago'), function($entity) use ($view) {
		if ($view->params['desde_factura_pago']) {
			$onClick = "ElegirParaPago(window.opener.location.href.replace(/&id_moneda=\d+/, '') + '&id_adelanto={$entity->get('id_documento')}&id_moneda={$entity->get('id_moneda')}')";
		} else if ($view->params['como_funcion']) {
			$onClick = "window.opener.utilizarAdelanto({$entity->get('id_documento')});window.close();";
		} else {
			$onClick = "ElegirParaPago(root_dir + '/app/interfaces/ingresar_documento_pago.php?id_cobro={$view->params['id_cobro']}&id_documento={$entity->get('id_documento')}&popup=1&pago=true&codigo_cliente={$entity->get('codigo_cliente')}')";
		}
		return $view->Form->button('Utilizar', array('onclick' => $onClick));
	});
} else {

	$this->EntitiesListator->addColumn(__('Opción'), function($entity) use ($view) {
		$accion_adelanto = $view->Form->image_link("editar_on.gif", false, array('onclick' => "nuovaFinestra('Agregar_Adelanto', 730, 580,'ingresar_documento_pago.php?id_documento={$entity->get('id_documento')}&adelanto=1&popup=1', 'top=100, left=155')"));

		$onclick = $entity->get('monto') == $entity->get('saldo_pago') ?
				"EliminarAdelanto({$entity->get('id_documento')})" :
				"alert('No se puede eliminar el adelanto porque ha sido utilizado como abono en algún " . __('Cobro') . "')";
		$accion_adelanto .= $view->Form->image_link('cruz_roja_nuevo.gif', false, array('onclick' => $onclick));

		return $accion_adelanto;
	});
}

echo $this->EntitiesListator->render();
?>
<script type="text/javascript" charset="utf-8">
	function EliminarAdelanto(adelanto) {
		if (confirm('¿Esta seguro que desea eliminar el adelanto?')) {
			window.location.href = "adelantos.php?id_documento_e=" + adelanto + "&opc=eliminar";
		}
	}

<?php if ($this->params['elegir_para_pago']) { ?>
		function ElegirParaPago(url) {
	<?php if ($this->params['mantener_ventana']) { ?>
				document.location.href = url + '&ocultar_boton_adelantos=1';
	<?php } else { ?>
				window.opener.location.href = url;
				window.close();
	<?php } ?>
			return false;
		}
<?php } ?>
</script>
