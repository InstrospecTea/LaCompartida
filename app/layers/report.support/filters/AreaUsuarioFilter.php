<?php

class AreaUsuarioFilter extends AbstractUndependantFilterTranslator {

	function getSelect() {
		return "IFNULL(prm_area_usuario.glosa, '-')"
	}

	function getFieldName() {
		return 'prm_area_usuario.glosa';
	}

	function translateForCharges(Criteria $criteria) {
		return $criteria;
	}

	function translateForErrands(Criteria $criteria) {
		return $this->addData(
			$this->getFilterData(),
			$criteria
		)->add_select(
			$this->getSelect(),
			"'prm_area_usuario.glosa'"
		)->add_left_join_with(
			'usuario',
			CriteriaRestriction::equals(
				'usuario.id_usuario', 
				'tramite.id_usuario'
			)
		)->add_left_join_with(
			'prm_area_usuario',
			CriteriaRestriction::equals(
				'prm_area_usuario.id',
				'usuario.id_area_usuario'
			)
		);
	}

	function translateForWorks(Criteria $criteria) {
		return $this->addData(
			$this->getFilterData(),
			$criteria
		)->add_select(
			$this->getSelect()
		)->add_left_join_with(
			'usuario',
			CriteriaRestriction::equals(
				'usuario.id_usuario', 
				'trabajo.id_usuario'
			)
		)->add_left_join_with(
			'prm_area_usuario',
			CriteriaRestriction::equals(
				'prm_area_usuario.id',
				'usuario.id_area_usuario'
			)
		);
	}
}