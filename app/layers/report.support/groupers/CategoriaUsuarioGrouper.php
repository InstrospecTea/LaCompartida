<?php

class CategoriaUsuarioGrouper extends AbstractGrouperTranslator {

	function getGroupField() {
		return 'prm_categoria_usuario.glosa_categoria';
	}

	function getSelectField() {
		return 'prm_categoria_usuario.glosa_categoria';
	}

	function getOrderField() {
		return 'prm_categoria_usuario.glosa_categoria';
	}

	/**
	 * TODO: Hay un undefined acá, segun la documentación, pero no se si aplica porque puedo llegar por los usuarios de
	 * los trabajos del cobro.
	 **/
	function translateForCharges(Criteria $criteria) {
		return $criteria->add_select(
			sprintf("'%s'", 'Indefinido'),
			"'prm_categoria_usuario.glosa_categoria'"
		)->add_ordering(
			"'prm_categoria_usuario.glosa_categoria'"
		)->add_grouping(
			"'prm_categoria_usuario.glosa_categoria'"
		);
	}

	function translateForErrands(Criteria $criteria) {
		return $criteria->add_select(
			$this->getSelectField(),
			"'prm_categoria_usuario.glosa_categoria'"
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
			'prm_categoria_usuario',
			CriteriaRestriction::equals(
				'prm_categoria_usuario.id_categoria_usuario',
				'usuario.id_categoria_usuario'
			)
		);
	}

	function translateForWorks(Criteria $criteria) {
		return $criteria->add_select(
			$this->getSelectField(),
			"'prm_categoria_usuario.glosa_categoria'"
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
			'prm_categoria_usuario',
			CriteriaRestriction::equals(
				'prm_categoria_usuario.id_categoria_usuario',
				'usuario.id_categoria_usuario'
			)
		);
	}
}