<?php

class ClientesFilter extends AbstractUndependantFilterTranslator {

    function getFieldName() {
        return 'cliente.codigo_cliente';
    }

    function translateForCharges(Criteria $criteria) {
        return $this->addData(
            $this->getFilterData(),
            $criteria
        )->add_select(
            $this->getFieldName()
        )->add_left_join_with(
            'contrato', CriteriaRestriction::equals(
                'contrato.id_contrato', 'cobro.id_contrato'
            )
        )->add_left_join_with(
            'cliente', CriteriaRestriction::equals(
                'contrato.codigo_cliente', 'cliente.codigo_cliente'
            )
        );
    }

    function translateForErrands(Criteria $criteria) {

    }

    function translateForWorks(Criteria $criteria) {

    }
}