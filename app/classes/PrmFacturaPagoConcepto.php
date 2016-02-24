<?php
require_once dirname(__FILE__) . '/../conf.php';

class PrmFacturaPagoConcepto extends ObjetoExt {

	public function __construct($sesion, $fields = '', $params = '') {
		$this->tabla = 'prm_factura_pago_concepto';
		$this->campo_id = 'id_concepto';
		$this->campo_glosa = 'glosa';
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->campo_orden = 'orden';
	}

}
