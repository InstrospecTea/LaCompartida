<?php
/**
 * Agrupador por Forma de Cobro:
 *
 * * Agrupa por: cobro.forma_cobro o Indefinido
 * * Muestra: cobro.forma_cobro o Indefinido
 * * Ordena por: cobro.forma_cobro o Indefinido
 *
 * Más info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Agrupador:-Forma-Cobro
 */
class FormaCobroGrouper extends AbstractGrouperTranslator {

	/**
	 * Obtiene el campo por el cual se agrupará la query
	 * @return String Campo por el que se agrupa en par tabla.campo o alias
	 */
	function getGroupField() {
		$undefined = $this->getUndefinedField();
		return "IFNULL(cobro.forma_cobro, {$undefined})";
	}

	/**
	 * Obtiene el campo de grupo que se devolverá en el SELECT de la query
	 * @return String par tabla.campo o alias de función
	 */
	function getSelectField() {
		return $this->getGroupField();
	}

	/**
	 * Obtiene el campo de grupo por el cual se ordenará la query
	 * @return String par tabla.campo o alias de función
	 */
	function getOrderField() {
		return $this->getGroupField();
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Cobros
	 * Forma de cobro del cobro
	 * @return void
	 */
	function translateForCharges(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'forma_cobro')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trámites
	 * Forma de cobro del trámite
	 * @return void
	 */
	function translateForErrands(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'forma_cobro')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trabajos
	 * Forma de cobro del trabajo
	 * @return void
	 */
	function translateForWorks(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'forma_cobro')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}
}
