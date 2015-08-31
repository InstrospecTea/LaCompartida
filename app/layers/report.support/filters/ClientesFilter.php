<?php

class ClientesFilter extends AbstractUndependantFilterTranslator {

	function getFieldName() {
		if (Conf::GetConf($this->Session, 'CodigoSecundario')) {
			return 'cliente.codigo_cliente_secundario';
		} else {
			return 'cliente.codigo_cliente'
		}
	}

	function translateForCharges(Criteria $criteria) {
		return $this->addData(
			$this->getFilterData(),
			$criteria
		)->add_select(
			$this->getFieldName()
		)->add_left_join_with(
			'contrato',
			CriteriaRestriction::equals(
				'contrato.id_contrato', 'cobro.id_contrato'
			)
		)->add_left_join_with(
			'cliente',
			CriteriaRestriction::equals(
				'cliente.codigo_cliente', 'contrato.codigo_cliente'
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
			'cliente',
			CriteriaRestriction::equals(
				'cliente.codigo_cliente',
				'asunto.codigo_cliente'
			)
		)
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
			'cliente',
			CriteriaRestriction::equals(
				'cliente.codigo_cliente',
				'asunto.codigo_cliente'
			)
		)
	}
}