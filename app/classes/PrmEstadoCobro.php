<?php
require_once dirname(__FILE__) . '/../conf.php';

class PrmEstadoCobro extends ObjetoExt {
	public function __construct($sesion, $fields = '', $params = '') {
		$this->tabla = 'prm_estado_cobro';
		$this->campo_id = 'codigo_estado_cobro';
		$this->campo_glosa = 'codigo_estado_cobro';
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->campo_orden = 'orden';
	}
}
