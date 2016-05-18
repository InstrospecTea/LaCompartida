<?php

class ClientsController extends AbstractController {

	protected $contractDataFields = array(
			'alerta_hh',
			'alerta_monto',
			'BankAccount.id_banco',
			'cod_factura_telefono',
			'codigo_idioma',
			'direccion_contacto AS direccion_contacto_contrato',
			'email_contacto AS email_contacto_contrato',
			'factura_ciudad',
			'factura_codigopostal',
			'factura_comuna',
			'factura_direccion',
			'factura_giro',
			'factura_razon_social',
			'factura_telefono',
			'fono_contacto AS fono_contacto_contrato',
			'forma_cobro',
			'glosa_contrato',
			'id_carta',
			'id_cuenta',
			'id_estudio',
			'id_formato',
			'id_moneda',
			'id_moneda_monto',
			'id_moneda_tramite',
			'id_pais',
			'id_tarifa',
			'id_tramite_tarifa',
			'id_usuario_responsable',
			'descuento',
			'porcentaje_descuento',
			'limite_hh',
			'limite_monto',
			'monto',
			'notificar_encargado_principal',
			'notificar_otros_correos',
			'observaciones',
			'opc_moneda_total',
			'opc_mostrar_tramites_no_cobrables',
			'opc_papel',
			'opc_restar_retainer',
			'opc_ver_asuntos_separados',
			'opc_ver_carta',
			'opc_ver_cobrable',
			'opc_ver_columna_cobrable',
			'opc_ver_descuento',
			'opc_ver_detalle_retainer',
			'opc_ver_detalles_por_hora',
			'opc_ver_detalles_por_hora_categoria',
			'opc_ver_detalles_por_hora_importe',
			'opc_ver_detalles_por_hora_iniciales',
			'opc_ver_detalles_por_hora_tarifa',
			'opc_ver_gastos',
			'opc_ver_horas_trabajadas',
			'opc_ver_modalidad',
			'opc_ver_morosidad',
			'opc_ver_numpag',
			'opc_ver_profesional',
			'opc_ver_profesional_categoria',
			'opc_ver_profesional_importe',
			'opc_ver_profesional_iniciales',
			'opc_ver_profesional_tarifa',
			'opc_ver_resumen_cobro',
			'opc_ver_solicitante',
			'opc_ver_tipo_cambio',
			'opc_ver_valor_hh_flat_fee',
			'rut AS factura_rut',
			'tipo_descuento'
		);

	public function getContractData($client_code = null) {
		if (empty($client_code)) {
			return $this->renderJSON(false);
		}

		if (Configure::read('PrmGastos')) {
			$this->contractDataFields[] = 'opc_ver_concepto_gastos';
		}

		if (Configure::read('TituloContacto')) {
			$this->contractDataFields[] = 'titulo_contacto';
			$this->contractDataFields[] = 'contacto AS nombre_contacto';
			$this->contractDataFields[] = 'apellido_contacto';
		} else {
			$this->contractDataFields[] = 'contacto';
		}

		$codigo_cliente_key = Configure::read('CodigoSecundario') ? 'codigo_cliente_secundario' : 'codigo_cliente';
		$searchCriteria = new SearchCriteria('Contract');
		$searchCriteria->related_with('Client')->on_property('codigo_cliente');
		$searchCriteria->related_with('BankAccount')->on_property('id_cuenta');
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
