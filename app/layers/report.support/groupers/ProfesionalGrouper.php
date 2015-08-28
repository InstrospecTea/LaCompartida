<?php

class ProfesionalGrouper extends AbstractGrouperTranslator {

	private $uniqueField = "id_usuario";

	function getGroupField() {
		return 'id_usuario';
	}

	function getSelectField() {
		return $this->getUserField();
	}

	function getOrderField() {
		return $this->getUserField();
	}

	function translateForCharges(Criteria $Criteria) {
		$Criteria
			->add_select($this->getUndefinedField(), 'profesional')
			->add_grouping($this->getUndefinedField())
			->add_ordering($this->getUndefinedField());

		return $Criteria;
	}

	function translateForErrands(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'profesional')
			->add_grouping($this->getSelectField())
			->add_ordering($this->getOrderField())
			->add_left_join_with(
			'usuario',
				CriteriaRestriction::equals(
					'usuario.id_usuario',
					'tramite.id_usuario'
				)
			);

		return $Criteria;
	}

	function translateForWorks(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'profesional')
			->add_grouping($this->getSelectField())
			->add_ordering($this->getOrderField())
			->add_left_join_with(
			'usuario',
				CriteriaRestriction::equals(
					'usuario.id_usuario',
					'trabajo.id_usuario'
				)
			);

		return $Criteria;
	}
}
