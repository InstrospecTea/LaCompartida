<?php
/**
 * Agrupador por categoría de usuario:
 *
 * * Agrupa por:  prm_categoria_usuario.glosa_categoria
 * * Muestra: prm_categoria_usuario.glosa_categoria o Indefinido
 * * Ordena por: prm_categoria_usuario.glosa_categoria
 *
 * Más info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Agrupador:-Categoria-Usuario
 */
class CategoriaUsuarioGrouper extends AbstractGrouperTranslator {

	/**
	 * Obtiene el campo por el cual se agrupará la query
	 * @return String Campo por el que se agrupa en par tabla.campo o alias
	 */
	function getGroupField() {
		return 'categoria_usuario';
	}

	/**
	 * Obtiene el campo de grupo que se devolverá en el SELECT de la query
	 * @return String par tabla.campo o alias de función
	 */
	function getSelectField() {
		return 'prm_categoria_usuario.glosa_categoria';
	}

	/**
	 * Obtiene el campo de grupo por el cual se ordenará la query
	 * @return String par tabla.campo o alias de función
	 */
	function getOrderField() {
		return 'categoria_usuario';
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Cobros
	 * @return void
	 */
	function translateForCharges(Criteria $criteria) {
		$undefined = $this->getUndefinedField();
		return $criteria->add_select(
			sprintf("'%s'", 'Indefinido'),
			'categoria_usuario'
		)->add_ordering(
			'categoria_usuario'
		)->add_grouping(
			'categoria_usuario'
		);
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trámites
	 * @return void
	 */
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

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trabajos
	 * @return void
	 */
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