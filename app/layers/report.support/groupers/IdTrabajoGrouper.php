<?php
/**
 * Agrupador por Identificador del trabajo
 *
 * * Agrupa por: trabajo.id_trabajo
 * * Muestra: trabajo.id_trabajo
 * * Ordena por:  trabajo.id_trabajo
 *
 * Más info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Agrupador:-Id-Trabajo
 */
class IdTrabajoGrouper extends AbstractGrouperTranslator {

	/**
	 * Obtiene el campo por el cual se agrupará la query
	 * @return String Campo por el que se agrupa en par tabla.campo o alias
	 */
	function getGroupField() {
		return "trabajo.id_trabajo";
	}

	/**
	 * Obtiene el campo de grupo que se devolverá en el SELECT de la query
	 * @return String par tabla.campo o alias de función
	 */
	function getSelectField() {
		return "trabajo.id_trabajo";
	}

	/**
	 * Obtiene el campo de grupo por el cual se ordenará la query
	 * @return String par tabla.campo o alias de función
	 */
	function getOrderField() {
		return "trabajo.id_trabajo";
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Cobros
	 * $this->getUndefinedField()
	 * @return void
	 */
	function translateForCharges(Criteria $Criteria) {
		$Criteria
			->add_select($this->getUndefinedField(), 'id_trabajo')
			->add_grouping($this->getUndefinedField())
			->add_ordering($this->getUndefinedField());

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trámites
	 * $this->getUndefinedField()
	 * @return void
	 */
	function translateForErrands(Criteria $Criteria) {
		$Criteria
			->add_select($this->getUndefinedField(), 'id_trabajo')
			->add_grouping($this->getUndefinedField())
			->add_ordering($this->getUndefinedField());

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trabajos
	 * Identificador del trabajo
	 * @return void
	 */
	function translateForWorks(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'id_trabajo')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}
}
