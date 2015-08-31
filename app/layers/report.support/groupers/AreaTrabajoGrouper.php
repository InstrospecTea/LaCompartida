<?php
/**
 * Agrupador por área de trabajo:
 *
 * * Agrupa por:  trabajo.id_area_trabajo
 * * Muestra: prm_area_trabajo.glosa o Indefinido
 * * Ordena por:  trabajo.id_area_trabajo
 *
 * Más info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Agrupador:-Area-Trabajo
 *
 */
class AreaTrabajoGrouper extends AbstractGrouperTranslator {

	/**
	 * Obtiene el campo por el cual se agrupará la query
	 * @return String Campo por el que se agrupa en par tabla.campo o alias
	 */
	function getGroupField() {
		return 'trabajo.id_area_trabajo';
	}

	/**
	 * Obtiene el campo de grupo que se devolverá en el SELECT de la query
	 * @return String par tabla.campo o alias de función
	 */
	function getSelectField() {
		$undefined = $this->getUndefinedField();
		return "IFNULL(prm_area_trabajo.glosa, {$undefined}) as 'prm_area_trabajo.glosa'";
	}

	/**
	 * Obtiene el campo de grupo por el cual se ordenará la query
	 * @return String par tabla.campo o alias de función
	 */
	function getOrderField() {
		return 'trabajo.id_area_trabajo';
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Cobros
	 * @return void
	 */
	function translateForCharges(Criteria $criteria) {
		return $criteria->add_select(
			$this->getUndefinedField(),
			"'prm_area_trabajo.glosa'"
		)->add_ordering(
			"'prm_area_trabajo.glosa'"
		)->add_grouping(
			"'prm_area_trabajo.glosa'"
		);
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trámites
	 * @return void
	 */
	function translateForErrands(Criteria $criteria) {
		return $criteria->add_select(
			$this->getUndefinedField(),
			"'prm_area_trabajo.glosa'"
		)->add_ordering(
			"'prm_area_trabajo.glosa'"
		)->add_grouping(
			"'prm_area_trabajo.glosa'"
		);
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trabajos
	 * @return void
	 */
	function translateForWorks(Criteria $criteria) {
		return $criteria->add_select(
			$this->getSelectField()
		)->add_ordering(
			$this->getOrderField()
		)->add_grouping(
			$this->getGroupField()
		)->add_left_join_with(
			'prm_area_trabajo',
			CriteriaRestriction::equals(
				'prm_area_trabajo.id_area_trabajo',
				'trabajo.id_area_trabajo'
			)
		);
	}
}