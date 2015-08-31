<?php

class CategoriaUsuarioFilter extends AbstractUndependantFilterTranslator {

	function getFieldName() {
		return 'prm_categoria_usuario.glosa_categoria';
	}

	function getSelect() {
		return "IFNULL(prm_categoria_usuario.glosa_categoria, '-')";
	}

	function translateForCharges(Criteria $criteria) {
		return $criteria;
	}

	function translateForErrands(Criteria $criteria) {
		return $this->addData(
			$this->getFilterData(),
			$criteria
		)->add_select(
			$this->getSelect(),
			"'prm_categoria_usuario.glosa_categoria'"
		)->add_left_join_with(
			'usuario cat_usuario_filter_usuario',
			CriteriaRestriction::equals(
				'cat_usuario_filter_usuario.id_usuario', 
				'tramite.id_usuario'
			)
		)->add_left_join_with(
			'prm_categoria_usuario',
			CriteriaRestriction::equals(
				'prm_categoria_usuario.id_categoria_usuario',
				'cat_usuario_filter_usuario.id_categoria_usuario'
			)
		);
	}

	function translateForWorks(Criteria $criteria) {
		return $this->addData(
			$this->getFilterData(),
			$criteria
		)->add_select(
			$this->getSelect()
		)->add_left_join_with(
			'usuario cat_usuario_filter_usuario',
			CriteriaRestriction::equals(
				'cat_usuario_filter_usuario.id_usuario', 
				'trabajo.id_usuario'
			)
		)->add_left_join_with(
			'prm_categoria_usuario',
			CriteriaRestriction::equals(
				'prm_categoria_usuario.id_categoria_usuario',
				'cat_usuario_filter_usuario.id_categoria_usuario'
			)
		);
	}
}