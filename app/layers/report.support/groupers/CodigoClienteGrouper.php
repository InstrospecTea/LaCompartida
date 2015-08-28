<?php

class CodigoClienteGrouper extends AbstractGrouperTranslator {

	function getGroupField() {
		return 'cliente.codigo_cliente';
	}

	function getSelectField() {
		return 'cliente.codigo_cliente';
	}

	function getOrderField() {
		return 'cliente.codigo_cliente';
	}

	function translateForCharges(Criteria $Criteria) {
		return $Criteria;
	}

	function translateForErrands(Criteria $Criteria) {
		return $Criteria;
	}

	function translateForWorks(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'codigo_cliente')
			->add_left_join_with('asunto', 'asunto.codigo_asunto = trabajo.codigo_asunto')
			->add_left_join_with('cliente', 'asunto.codigo_cliente = cliente.codigo_cliente')
			->add_grouping($this->getSelectField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}
}
