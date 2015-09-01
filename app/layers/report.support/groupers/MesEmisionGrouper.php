<?php
/**
 * Agrupador por Mes de la emisión de la liquidación
 *
 * * Agrupa por: IF(cobro.fecha_emision IS NULL, 'Por Emitir', DATE_FORMAT(cobro.fecha_emision, '%m-%Y'))
 * * Muestra: IF(cobro.fecha_emision IS NULL, 'Por Emitir', DATE_FORMAT(cobro.fecha_emision, '%m-%Y'))
 * * Ordena por:  cobro.fecha_emision
 *
 * Más info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Agrupador:-Mes-Emision
 */
class  extends AbstractGrouperTranslator {

	/**
	 * Obtiene el campo por el cual se agrupará la query
	 * @return String Campo por el que se agrupa en par tabla.campo o alias
	 */
	function getGroupField() {
		return "IF(cobro.fecha_emision IS NULL, 'Por Emitir', DATE_FORMAT(cobro.fecha_emision, '%m-%Y'))";
	}

	/**
	 * Obtiene el campo de grupo que se devolverá en el SELECT de la query
	 * @return String par tabla.campo o alias de función
	 */
	function getSelectField() {
		return "IF(cobro.fecha_emision IS NULL, 'Por Emitir', DATE_FORMAT(cobro.fecha_emision, '%m-%Y'))";
	}

	/**
	 * Obtiene el campo de grupo por el cual se ordenará la query
	 * @return String par tabla.campo o alias de función
	 */
	function getOrderField() {
		return "cobro.fecha_emision";
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Cobros
	 * Fecha de emisión de la liquidación
	 * @return void
	 */
	function translateForCharges(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'mes_emision')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trámites
	 * Fecha de emisión de la liquidación del trámite
	 * @return void
	 */
	function translateForErrands(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'mes_emision')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trabajos
	 * Fecha de emisión de la liquidación del trabajo
	 * @return void
	 */
	function translateForWorks(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'mes_emision')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}
}
