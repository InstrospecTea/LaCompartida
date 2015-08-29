<?php

class GlosaAsuntoGrouper extends AbstractGrouperTranslator {

	function getGroupField() {
		return 'asunto.codigo_asunto';
	}

	function getSelectField() {
		return 'asunto.glosa_asunto';
	}

	function getOrderField() {
		return 'asunto.glosa_asunto';
	}

	function translateForCharges(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'glosa_asunto')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField())
			->add_left_join_with('cobro_asunto',
				CriteriaRestriction::equals('cobro_asunto.id_cobro', 'cobro.id_cobro'))
			->add_left_join_with('asunto',
				CriteriaRestriction::equals('asunto.codigo_asunto', 'cobro_asunto.codigo_asunto'));

		return $Criteria;
	}

	function translateForErrands(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'glosa_asunto')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField())
			->add_left_join_with('asunto',
				CriteriaRestriction::equals('asunto.codigo_asunto', 'tramite.codigo_asunto')
			);

		return $Criteria;
	}

	function translateForWorks(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'glosa_asunto')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField())
			->add_left_join_with('asunto',
				CriteriaRestriction::equals('asunto.codigo_asunto', 'trabajo.codigo_asunto')
			);

		return $Criteria;
	}
}
