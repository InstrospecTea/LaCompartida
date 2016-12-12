<?php

require_once dirname(__FILE__) . '/../conf.php';

class PrmTipoDocumentoIdentidad extends Objeto {

	public function __construct($Sesion, $fields = '', $params = '') {
		$this->tabla = 'prm_tipo_documento_identidad';
		$this->campo_id = 'id_tipo_documento_identidad';
		$this->campo_glosa = 'glosa';
		$this->sesion = $Sesion;
		$this->fields = $fields;
	}

	/**
	 * Carga un registro buscando por codigo_dte
	 * @param string $dte_code código DTE a buscar
	 */
	public function loadByDteCode($dte_code) {
		$criteria = new Criteria($this->sesion);
		$criteria
			->add_select($this->campo_id)
			->add_from($this->tabla)
			->add_restriction(CriteriaRestriction::equals('codigo_dte', "'$dte_code'"));
		try {
			$result = $criteria->run();
			$this->Load($result[0][$this->campo_id]);
		} catch (Exception $e) {
			echo "Error: {$e} {$criteria->__toString()}";
		}
	}

}
