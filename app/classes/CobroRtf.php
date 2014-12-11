<?php

require_once dirname(__FILE__) . '/../conf.php';

class CobroRtf extends Objeto {

	function __construct($Sesion, $fields = '', $params = '') {
		$this->tabla = 'cobro_rtf';
		$this->campo_id = 'id_formato';
		$this->campo_glosa = 'descripcion';
		$this->sesion = $Sesion;
		$this->fields = $fields;
	}

	function loadFirst() {
		$Criteria = new Criteria($this->sesion);

		$cobro_rtf = array_shift($Criteria->add_select('cobro_rtf.id_formato')->add_from('cobro_rtf')->add_ordering('cobro_rtf.id_formato')->add_restriction(CriteriaRestriction::equals('id_formato', 4))->add_limit(1)->run());

		if (!empty($cobro_rtf)) {
			$this->Load($cobro_rtf['id_formato']);
		}

		return $this->Loaded();
	}

}
