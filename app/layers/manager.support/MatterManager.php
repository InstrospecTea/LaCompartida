<?php

class MatterManager extends AbstractManager implements BaseManager {
  /**
	 * Obtiene los cobros de los trabajos asociados al asunto
	 */
	public function getChargesOfWorks($matter_code) {
		$Criteria = new Criteria($this->Sesion);
		$Criteria->add_select('GROUP_CONCAT(DISTINCT id_cobro SEPARATOR ", ")', 'cobros')
			->add_from('trabajo')
			->add_restriction(CriteriaRestriction::is_not_null('id_cobro'))
			->add_restriction(CriteriaRestriction::equals('trabajo.codigo_asunto', "'{$matter_code}'"));

    $result = $Criteria->first();
    return $result !== false ? $result['cobros'] : false;
	}

	/**
	 * Obtiene los cobros de los trabajos asociados al asunto
	 */
	public function getChargesOfErrands($matter_code) {
    $Criteria = new Criteria($this->Sesion);
		$Criteria->add_select('GROUP_CONCAT(DISTINCT id_cobro SEPARATOR ", ")', 'cobros')
			->add_from('tramite')
			->add_restriction(CriteriaRestriction::is_not_null('id_cobro'))
			->add_restriction(CriteriaRestriction::equals('tramite.codigo_asunto', "'{$matter_code}'"));

		$result = $Criteria->first();
    return $result !== false ? $result['cobros'] : false;
	}

	/**
	 * Obtiene los cobros de los gastos asociados al asunto
	 */
	public function getChargesOfExpenses($matter_code) {
    $Criteria = new Criteria($this->Sesion);
		$Criteria->add_select('GROUP_CONCAT(DISTINCT id_cobro SEPARATOR ", ")', 'cobros')
			->add_from('cta_corriente')
			->add_restriction(CriteriaRestriction::is_not_null('id_cobro'))
			->add_restriction(CriteriaRestriction::is_not_null('egreso'))
			->add_restriction(CriteriaRestriction::equals('cta_corriente.codigo_asunto', "'{$matter_code}'"));

    $result = $Criteria->first();
		return $result !== false ? $result['cobros'] : false;
	}

	/**
	 * Obtiene los cobros de los adelantos asociados al asunto
	 */
	public function getChargesOfAdvances($matter_code) {
    $Criteria = new Criteria($this->Sesion);
		$Criteria->add_select('GROUP_CONCAT(DISTINCT dc.id_cobro SEPARATOR ", ")', 'cobros')
			->add_from('neteo_documento nd')
			->add_inner_join_with('documento dc', CriteriaRestriction::equals('nd.id_documento_cobro', 'dc.id_documento'))
			->add_inner_join_with('documento da', CriteriaRestriction::equals('nd.id_documento_pago', 'da.id_documento'))
			->add_restriction(CriteriaRestriction::equals('da.es_adelanto', "1"))
			->add_restriction(CriteriaRestriction::equals('da.codigo_asunto', "'{$matter_code}'"));

    $result = $Criteria->first();
    return $result !== false ? $result['cobros'] : false;
	}
}
