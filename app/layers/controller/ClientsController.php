<?php

class ClientsController extends AbstractController {

	protected $contractDataFields = array(
			'rut AS factura_rut',
			'factura_razon_social',
			'factura_direccion',
			'factura_comuna',
			'factura_ciudad',
			'factura_giro',
			'factura_codigopostal',
			'cod_factura_telefono',
			'factura_telefono',
			'id_pais',
			'glosa_contrato',
			'id_moneda',
			'id_tarifa',
			'forma_cobro',
			'monto',
			'id_moneda_monto',
			'opc_moneda_total',
			'id_usuario_responsable'
		);

	public function getContractData($client_code = null) {
		if (empty($client_code)) {
			return $this->renderJSON(false);
		}
		$codigo_cliente_key = Configure::read('CodigoSecundario') ? 'codigo_cliente_secundario' : 'codigo_cliente';
		$searchCriteria = new SearchCriteria('Contract');
		$searchCriteria->related_with('Client')->on_property('codigo_cliente');
		$searchCriteria->filter("{$codigo_cliente_key}")->for_entity('Client')->compare_with("'{$client_code}'");
		$searchCriteria->filter('id_contrato')->restricted_by('equals')->compare_with('Client.id_contrato');
		$fields = $this->contractDataFields;
		if (Configure::read('EncargadoSecundario')) {
			$searchCriteria->related_with('User')->on_property('id_usuario')->on_entity_property('id_usuario_secundario');
			$searchCriteria->filter('id_contrato')->restricted_by('equals')->compare_with('Client.id_contrato');
			$fields[] = 'Contract.id_usuario_secundario';
			$fields[] = "CONCAT(User.apellido1, ' ', User.apellido2, ', ', User.nombre) AS nombre_usuario_secundario";
		}
		$this->loadBusiness('Searching');
		$results = $this->SearchingBusiness->searchByCriteria($searchCriteria, $fields);

		return $this->renderJSON(isset($results[0]) ? $results[0]->fields : false);
	}

}
