<?php

class TipoAsuntoFilter extends AbstractUndependantFilterTranslator {

	function getFieldName() {
		return 'prm_tipo_proyecto.glosa_tipo_proyecto';
	}

	function translateForCharges(Criteria $criteria) {
		return $this->addData(
			$this->getFilterData(),
			$criteria
		)->add_select(
			$this->getFieldName()
		)->add_left_join_with(
			'cobro_asunto',
			CriteriaRestriction::equals(
				'cobro_asunto.id_cobro', 
				'cobro.id_cobro'
			)
		)->add_left_join_with(
			'asunto',
			CriteriaRestriction::equals(
				'asunto.codigo_asunto', 
				'cobro_asunto.codigo_asunto'
			)
		)->add_left_join_with(
			'prm_tipo_proyecto',
			CriteriaRestriction::equals(
				'prm_tipo_proyecto.id_tipo_proyecto',
				'asunto.id_tipo_asunto'
			)
		);
	}

	function translateForErrands(Criteria $criteria) {
		return $this->addData(
			$this->getFilterData(),
			$criteria
		)->add_select(
			$this->getFieldName()
		)->add_left_join_with(
			'asunto',
			CriteriaRestriction::equals(
				'asunto.codigo_asunto', 
				'tramite.codigo_asunto'
			)
		)->add_left_join_with(
			'prm_tipo_proyecto',
			CriteriaRestriction::equals(
				'prm_tipo_proyecto.id_tipo_proyecto',
				'asunto.id_tipo_asunto'
			)
		);
	}

	function translateForWorks(Criteria $criteria) {
		return $this->addData(
			$this->getFilterData(),
			$criteria
		)->add_select(
			$this->getFieldName()
		)->add_left_join_with(
			'asunto',
			CriteriaRestriction::equals(
				'asunto.codigo_asunto', 
				'trabajo.codigo_asunto'
			)
		)->add_left_join_with(
			'prm_tipo_proyecto',
			CriteriaRestriction::equals(
				'prm_tipo_proyecto.id_tipo_proyecto',
				'asunto.id_tipo_asunto'
			)
		);
	}
}