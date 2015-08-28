<?php

class AreaUsuarioGrouper extends AbstractGrouperTranslator {

    function getGroupField() {
        return 'prm_area_usuario.glosa';
    }

    function getSelectField() {
        return 'IFNULL(prm_area_usuario.glosa,\'-\')';
    }

    function getOrderField() {
        return 'prm_area_usuario.glosa';
    }

    /**
     * TODO: Hay un undefined acá, segun la documentación, pero no se si aplica porque puedo llegar por los usuarios de
     * los trabajos del cobro.
     **/
    function translateForCharges(Criteria $criteria) {
        return $criteria->add_select(
            $this->getSelectField(),
            "'prm_area_usuario.glosa'"
        )->add_ordering(
            $this->getOrderField()
        )->add_grouping(
            $this->getGroupField()
        )->add_left_join_with(
            'trabajo',
            CriteriaRestriction::equals(
                'trabajo.id_cobro',
                'cobro.id_cobro'
            )
        )->add_left_join_with(
            'usuario',
            CriteriaRestriction::equals(
                'usuario.id_usuario',
                'trabajo.id_usuario'
            )
        )->add_left_join_with(
            'prm_area_usuario',
            CriteriaRestriction::equals(
                'prm_area_usuario.id',
                'usuario.id_area_usuario'
            )
        );
    }

    function translateForErrands(Criteria $criteria) {
        return $criteria->add_select(
            $this->getSelectField(),
            'prm_area_usuario.glosa'
        )->add_ordering(
            $this->getOrderField()
        )->add_grouping(
            $this->getGroupField()
        )->add_left_join_with(
            'usuario',
            CriteriaRestriction::equals(
                'usuario.id_usuario',
                'tramite.id_usuario'
            )
        )->add_left_join_with(
            'prm_area_usuario',
            CriteriaRestriction::equals(
                'prm_area_usuario.id',
                'usuario.id_area_usuario'
            )
        );
    }

    function translateForWorks(Criteria $criteria) {
        return $criteria->add_select(
            $this->getSelectField(),
            'prm_area_usuario.glosa'
        )->add_ordering(
            $this->getOrderField()
        )->add_grouping(
            $this->getGroupField()
        )->add_left_join_with(
            'usuario',
            CriteriaRestriction::equals(
                'usuario.id_usuario',
                'trabajo.id_usuario'
            )
        )->add_left_join_with(
            'prm_area_usuario',
            CriteriaRestriction::equals(
                'prm_area_usuario.id',
                'usuario.id_area_usuario'
            )
        );
    }
}