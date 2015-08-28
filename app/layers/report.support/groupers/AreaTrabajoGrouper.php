<?php

class AreaTrabajoGrouper extends AbstractGrouperTranslator {

	function getGroupField() {
		return 'trabajo.id_area_trabajo';
	}

	function getSelectField() {
		return "IFNULL(prm_area_trabajo.glosa, 'Indefinido') as 'prm_area_trabajo.glosa'";
	}

	function getOrderField() {
		return 'trabajo.id_area_trabajo';
	}

	function translateForCharges(Criteria $criteria) {
		return $criteria->add_select(
			"'-'",
			"'prm_area_trabajo.glosa'"
		)->add_ordering(
			"'prm_area_trabajo.glosa'"
		)->add_grouping(
			"'prm_area_trabajo.glosa'"
		);
	}

	function translateForErrands(Criteria $criteria) {
		return $criteria->add_select(
			sprintf("'%s'", 'Indefinido'),
			"'prm_area_trabajo.glosa'"
		)->add_ordering(
			"'prm_area_trabajo.glosa'"
		)->add_grouping(
			"'prm_area_trabajo.glosa'"
		);
	}

	function translateForWorks(Criteria $criteria) {
		return $criteria->add_select(
			$this->getSelectField()
		)->add_ordering(
			$this->getOrderField()
		)->add_grouping(
			$this->getGroupField()
		)->add_left_join_with(
			'prm_area_trabajo',
			CriteriaRestriction::equals(
				'prm_area_trabajo.id_area_trabajo',
				'trabajo.id_area_trabajo'
			)
		);
	}
}