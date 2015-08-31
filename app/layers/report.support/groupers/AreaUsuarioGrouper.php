<?php
/**
 * Agrupador por área de usuario:
 *
 * * Agrupa por:  prm_area_usuario.glosa
 * * Muestra: prm_area_usuario.glosa o Indefinido
 * * Ordena por:  prm_area_usuario.glosa
 *
 * Más info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Agrupador:-Area-Usuario
 *
 */
class AreaUsuarioGrouper extends AbstractGrouperTranslator {

	/**
	 * Obtiene el campo por el cual se agrupará la query
	 * @return String Campo por el que se agrupa en par tabla.campo o alias
	 */
	function getGroupField() {
		return 'area_usuario';
	}

	/**
	 * Obtiene el campo de grupo que se devolverá en el SELECT de la query
	 * @return String par tabla.campo o alias de función
	 */
	function getSelectField() {
		$undefined = $this->getUndefinedField();
		return "IFNULL(prm_area_usuario.glosa, {$undefined})";
	}

	/**
	 * Obtiene el campo de grupo por el cual se ordenará la query
	 * @return String par tabla.campo o alias de función
	 */
	function getOrderField() {
		return 'area_usuario';
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Cobros
	 * @return void
	 */
	function translateForCharges(Criteria $criteria) {
		$undefined = $this->getUndefinedField();
		return $criteria->add_select(
			$this->getUndefinedField(),
			'area_usuario'
		)->add_ordering(
			'area_usuario'
		)->add_grouping(
			'area_usuario'
		);
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trámites
	 * @return void
	 */
	function translateForErrands(Criteria $criteria) {
		return $criteria->add_select(
			$this->getSelectField(),
			'area_usuario'
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

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trabajos
	 * @return void
	 */
	function translateForWorks(Criteria $criteria) {
		return $criteria->add_select(
			$this->getSelectField(),
			'area_usuario'
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