<?php
/**
 * Agrupador por Estado:
 *
 * * Agrupa por: cobro.estado
 * * Muestra: cobro.estado
 * * Ordena por: cobro.estado
 *
 * Más info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Agrupador:-Estado
 */
class EstadoGrouper extends AbstractGrouperTranslator {

	/**
	 * Obtiene el campo por el cual se agrupará la query
	 * @return String Campo por el que se agrupa en par tabla.campo o alias
	 */
	function getGroupField() {
		return 'cobro.estado';
	}

	/**
	 * Obtiene el campo de grupo que se devolverá en el SELECT de la query
	 * @return String par tabla.campo o alias de función
	 */
	function getSelectField() {
		return 'cobro.estado';
	}
	/**
	 * Obtiene el campo de grupo por el cual se ordenará la query
	 * @return String par tabla.campo o alias de función
	 */
	function getOrderField() {
		return 'cobro.estado';
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Cobros
	 * Glosa del cliente de cada asunto incluido en la liquidación
	 * @return void
	 */

	function translateForCharges(Criteria $Criteria) {
		$Criteria
			->add_select("IFNULL({$this->getSelectField()}, 'Indefinido')", 'estado')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trámites
	 * Glosa del cliente del asunto del trámite
	 * @return void
	 */
	function translateForErrands(Criteria $Criteria) {
		$Criteria
			->add_select("IFNULL({$this->getSelectField()}, 'Indefinido')", 'estado')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField())
			->add_left_join_with('cobro',
				CriteriaRestriction::equals('cobro.id_cobro', 'tramite.id_cobro')
			);

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trabajos
	 * Glosa del cliente del asunto del trabajo
	 * @return void
	 */
	function translateForWorks(Criteria $Criteria) {
		$Criteria
			->add_select("IFNULL({$this->getSelectField()}, 'Indefinido')", 'estado')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField())
			->add_left_join_with('cobro',
				CriteriaRestriction::equals('cobro.id_cobro', 'trabajo.id_cobro')
			);

		return $Criteria;
	}
}
