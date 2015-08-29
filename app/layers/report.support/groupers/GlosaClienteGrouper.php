<?php

class GlosaClienteGrouper extends AbstractGrouperTranslator {

	function getGroupField() {
		return 'cliente.codigo_cliente';
	}

	function getSelectField() {
		return 'cliente.glosa_cliente';
	}

	function getOrderField() {
		return 'cliente.glosa_cliente';
	}

	function translateForCharges(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'glosa_cliente')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField())
			->add_left_join_with('contrato',
				CriteriaRestriction::equals('contrato.id_contrato', 'cobro.id_contrato')
			)
			->add_left_join_with('cliente',
				CriteriaRestriction::equals('cliente.codigo_cliente', 'contrato.codigo_cliente')
			);

		return $Criteria;
	}

	function translateForErrands(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'glosa_cliente')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField())
			->add_left_join_with('asunto',
				CriteriaRestriction::equals('asunto.codigo_asunto', 'tramite.codigo_asunto')
			)
			->add_left_join_with('cliente',
				CriteriaRestriction::equals('cliente.codigo_cliente', 'asunto.codigo_cliente')
			);

		return $Criteria;
	}

	function translateForWorks(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'glosa_cliente')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField())
			->add_left_join_with('asunto',
				CriteriaRestriction::equals('asunto.codigo_asunto', 'trabajo.codigo_asunto')
			)
			->add_left_join_with('cliente',
				CriteriaRestriction::equals('cliente.codigo_cliente', 'asunto.codigo_cliente')
			);

		return $Criteria;
	}
}
