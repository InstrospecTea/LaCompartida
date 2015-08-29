<?php

class IdCobroGrouper extends AbstractGrouperTranslator {

	private $uniqueField = "IFNULL(cobro.id_cobro, 'Indefinido')";

	function getGroupField() {
		return $this->uniqueField;
	}

	function getSelectField() {
		return $this->uniqueField;
	}

	function getOrderField() {
		return $this->uniqueField;
	}

	function translateForCharges(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'id_cobro')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}

	function translateForErrands(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'id_cobro')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}

	function translateForWorks(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'id_cobro')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}
}
