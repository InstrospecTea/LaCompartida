<?php
/**
 * Agrupador por Actividad de trabajo:
 *
 * * Agrupa por:  actividad.id_actividad
 * * Muestra: actividad.glosa_actividad o Indefinido
 * * Ordena por:  actividad.id_actividad
 *
 * Más info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Agrupador:-Area-Trabajo
 *
 */
class ActividadGrouper extends AbstractGrouperTranslator {

	/**
	 * Obtiene el campo por el cual se agrupará la query
	 * @return String Campo por el que se agrupa en par tabla.campo o alias
	 */
	function getGroupField() {
		return 'actividad.id_actividad';
	}

	/**
	 * Obtiene el campo de grupo que se devolverá en el SELECT de la query
	 * @return String par tabla.campo o alias de función
	 */
	function getSelectField() {
		$undefined = $this->getUndefinedField();
		return "IFNULL(actividad.glosa_actividad, {$undefined}) as actividad";
	}

	/**
	 * Obtiene el campo de grupo por el cual se ordenará la query
	 * @return String par tabla.campo o alias de función
	 */
	function getOrderField() {
		return 'actividad.glosa_actividad';
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Cobros
	 * @return void
	 */
	function translateForCharges(Criteria $criteria) {
		return $criteria->add_select($this->getUndefinedField(), 'actividad')
			->add_select($this->getUndefinedField(), 'id_actividad')
			->add_ordering("'actividad'")
			->add_grouping("'actividad'");
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trámites
	 * @return void
	 */
	function translateForErrands(Criteria $criteria) {
		return $criteria->add_select($this->getUndefinedField(), 'actividad')
			->add_select($this->getUndefinedField(), 'id_actividad')
			->add_ordering("'actividad'")
			->add_grouping("'actividad'");
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trabajos
	 * @return void
	 */
	function translateForWorks(Criteria $criteria) {
		return $criteria->add_select($this->getSelectField())
			->add_select($this->getGroupField())
			->add_ordering($this->getOrderField())
			->add_grouping($this->getGroupField())
			->add_left_join_with('actividad',
				CriteriaRestriction::equals(
					'actividad.codigo_actividad',
					'trabajo.codigo_actividad'
				)
			);
	}
}
