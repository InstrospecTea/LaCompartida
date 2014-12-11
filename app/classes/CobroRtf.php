<?php

require_once dirname(__FILE__) . '/../conf.php';

/**
 * Formatos de las notas de cobro
 */
class CobroRtf extends Objeto {

	function __construct($Sesion, $fields = '', $params = '') {
		$this->tabla = 'cobro_rtf';
		$this->campo_id = 'id_formato';
		$this->campo_glosa = 'descripcion';
		$this->sesion = $Sesion;
		$this->fields = $fields;
	}

	/**
	 * Carga el primer registro de la tabla cobro_rtf
	 * @return boolean TRUE si fue cargado con exito el formato de la nota de cobro, de lo contrario retorna FALSE
	 */
	function loadFirst() {
		$Criteria = new Criteria($this->sesion);

		$cobro_rtf = array_shift(
			$Criteria
				->add_select('cobro_rtf.id_formato')
				->add_from('cobro_rtf')
				->add_ordering('cobro_rtf.id_formato')
				->add_limit(1)
				->run()
		);

		if (!empty($cobro_rtf)) {
			$this->Load($cobro_rtf['id_formato']);
		}

		return $this->Loaded();
	}

}
