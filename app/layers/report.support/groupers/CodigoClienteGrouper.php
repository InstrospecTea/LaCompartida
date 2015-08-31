<?php

class CodigoClienteGrouper extends AbstractGrouperTranslator {

	function getGroupField() {
		return 'codigo_cliente';
	}

	function getSelectField() {
		return 'cliente.codigo_cliente'
	}

	function getOrderField() {
		return 'codigo_cliente';
	}

	function translateForCharges(Criteria $Criteria) {
		$Criteria->add_select(
			$this->getSelectField(), 'codigo_cliente'
		)->add_left_join_with(
			'cliente',
			'cliente.codigo_cliente = cobro.codigo_cliente'
		)->add_grouping(
			$this->getGroupField()
		)->add_ordering(
			$this->getOrderField()
		);

		return $Criteria;
	}

	function translateForErrands(Criteria $Criteria) {
		$Criteria->add_select(
			$this->getSelectField(),
			'codigo_cliente'
		)->add_left_join_with(
			'asunto', 
			CriteriaRestriction::equals(
				'asunto.codigo_asunto',
				'tramite.codigo_asunto'
			)
		)->add_left_join_with(
			'cliente',
			CriteriaRestriction::equals(
				'cliente.codigo_cliente',
				'asunto.codigo_cliente'
			)
		)->add_grouping(
			$this->getGroupField()
		)->add_ordering(
			$this->getOrderField()
		);

		return $Criteria;
	}

	function translateForWorks(Criteria $Criteria) {
		$Criteria->add_select(
			$this->getSelectField(),
			'codigo_cliente'
		)->add_left_join_with(
			'asunto', 
			CriteriaRestriction::equals(
				'asunto.codigo_asunto',
				'trabajo.codigo_asunto'
			)
		)->add_left_join_with(
			'cliente',
			CriteriaRestriction::equals(
				'cliente.codigo_cliente',
				'asunto.codigo_cliente'
			)
		)->add_grouping(
			$this->getGroupField()
		)->add_ordering(
			$this->getOrderField()
		);

		return $Criteria;
	}
}
