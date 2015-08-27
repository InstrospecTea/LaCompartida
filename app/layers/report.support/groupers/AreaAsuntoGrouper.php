<?php

class AreaAsuntoGrouper extends AbstractGrouperTranslator {

    function getGroupField() {
        return 'prm_area_proyecto.glosa';
    }

    function getSelectField() {
        return 'prm_area_proyecto.glosa';
    }

    function getOrderField() {
        return 'prm_area_proyecto.glosa';
    }

    function translateForCharges(Criteria $criteria) {
        return $criteria->add_select(
            $this->getGroupField()
        )->add_ordering(
            $this->getOrderField()
        )->add_grouping(
            $this->getGroupField()
        )->add_left_join_with(
            'cobro_asunto',
            CriteriaRestriction::equals(
                'cobro_asunto.id_cobro',
                'cobro.id_cobro'
            )
        )->add_left_join_with(
            'asunto',
            CriteriaRestriction::equals(
                'asunto.codigo_asunto',
                'cobro_asunto.codigo_asunto'
            )
        )->add_left_join_with(
            'prm_area_proyecto',
            CriteriaRestriction::equals(
                'asunto.id_area_proyecto',
                'prm_area_proyecto.id_area_proyecto'
            )
        );
    }

    function translateForErrands(Criteria $criteria) {
        return $criteria->add_select(
            $this->getGroupField()
        )->add_ordering(
            $this->getOrderField()
        )->add_grouping(
            $this->getGroupField()
        )->add_left_join_with(
            'asunto',
            CriteriaRestriction::equals(
                'asunto.codigo_asunto',
                'tramite.codigo_asunto'
            )
        )->add_left_join_with(
            'prm_area_proyecto',
            CriteriaRestriction::equals(
                'asunto.id_area_proyecto',
                'prm_area_proyecto.id_area_proyecto'
            )
        );
    }

    function translateForWorks(Criteria $criteria) {
        return $criteria->add_select(
            $this->getGroupField()
        )->add_ordering(
            $this->getOrderField()
        )->add_grouping(
            $this->getGroupField()
        )->add_left_join_with(
            'asunto',
            CriteriaRestriction::equals(
                'asunto.codigo_asunto',
                'trabajo.codigo_asunto'
            )
        )->add_left_join_with(
            'prm_area_proyecto',
            CriteriaRestriction::equals(
                'asunto.id_area_proyecto',
                'prm_area_proyecto.id_area_proyecto'
            )
        );
    }
}