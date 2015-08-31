<?php

class CategoriaUsuarioGrouper extends AbstractGrouperTranslator {

	function getGroupField() {
		return 'categoria_usuario';
	}

	function getSelectField() {
		return 'prm_categoria_usuario.glosa_categoria';
	}

	function getOrderField() {
		return 'categoria_usuario';
	}

	/**
	 * TODO: Hay un undefined acá, segun la documentación, pero no se si aplica porque puedo llegar por los usuarios de
	 * los trabajos del cobro.
	 **/
	function translateForCharges(Criteria $criteria) {
		return $criteria->add_select(
			sprintf("'%s'", 'Indefinido'),
			'categoria_usuario'
		)->add_ordering(
			'categoria_usuario'
		)->add_grouping(
			'categoria_usuario'
		);
	}

	function translateForErrands(Criteria $criteria) {
		return $criteria->add_select(
			$this->getSelectField(),
			'categoria_usuario'
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
			'categoria_usuario'
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