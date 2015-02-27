<?php

class AdvancingBusiness extends AbstractBusiness {//implements ISandboxingBusiness {

	/**
	 * Obtiene la lista paginada de los adelantos que complen las condiciones.
	 * @param type $filters
	 * @return type
	 */
	function getList($filters) {
		$searchCriteria = new SearchCriteria('Document');
		$searchCriteria->related_with('Currency')->on_property('id_moneda');
		$searchCriteria->related_with('Client')->on_property('codigo_cliente');
		$searchCriteria->add_scope('isAdvance');

		if (!empty($filters['id_documento'])) {
			$searchCriteria->filter('id_documento')->restricted_by('equals')->compare_with($filters['id_documento']);
		}
		if (!empty($filters['codigo_cliente'])) {
			$searchCriteria->filter('codigo_cliente')->restricted_by('equals')->compare_with($filters['codigo_cliente']);
		}
		if (!empty($filters['fecha_inicio'])) {
			$fecha = date("Y-m-d", strtotime($filters['fecha_inicio']));
			$searchCriteria->filter('fecha')->restricted_by('greater_or_equals_than')->compare_with("'$fecha'");
		}
		if (!empty($filters['fecha_fin'])) {
			$fecha = date("Y-m-d", strtotime($filters['fecha_fin']));
			$searchCriteria->filter('fecha')->restricted_by('lower_or_equals_than')->compare_with("'$fecha'");
		}
		if (!empty($filters['moneda'])) {
			$searchCriteria->filter('id_moneda')->restricted_by('equals')->compare_with($filters['moneda']);
		} else if (!empty($filters['moneda_adelanto'])) {
			$searchCriteria->filter('id_documento')->restricted_by('equals')->compare_with($filters['moneda_adelanto']);
		}
		if (!empty($filters['pago_honorarios'])) {
			$searchCriteria->filter('pago_honorarios')->restricted_by('equals')->compare_with($filters['pago_honorarios']);
		}
		if (!empty($filters['pago_gastos'])) {
			$searchCriteria->filter('pago_gastos')->restricted_by('equals')->compare_with($filters['pago_gastos']);
		}
		if (!empty($filters['elegir_para_pago']) || !empty($filters['tiene_saldo'])) {
			$searchCriteria->add_scope('hasBalance');
		}
		if (!empty($filters['id_contrato'])) {
			$searchCriteria->add_scope('hasOrNotContract', $filters['id_contrato']);
		}

		$this->loadBusiness('Searching');

		$fields = array(
				'Document.id_documento',
				'Document.id_cobro',
				'Client.glosa_cliente',
				'Client.codigo_cliente',
				'IF(Document.monto = 0, 0, Document.monto*-1) AS monto',
				'IF(Document.saldo_pago = 0, 0, Document.saldo_pago*-1) AS saldo_pago',
				"CONCAT(Currency.simbolo, ' ', IF(Document.monto = 0, 0, Document.monto*-1)) AS monto_con_simbolo",
				"CONCAT(Currency.simbolo, ' ', IF(Document.saldo_pago = 0, 0, Document.saldo_pago*-1)) AS saldo_pago_con_simbolo",
				'Document.glosa_documento',
				'Document.fecha',
				'Document.id_moneda'
		);
		return $this->SearchingBusiness->paginateByCriteria($searchCriteria, $fields, $page);
	}

}
