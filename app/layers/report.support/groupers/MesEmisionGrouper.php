<?php
/**
 * Agrupador por Mes de la emisi�n de la liquidaci�n
 *
 * * Agrupa por: IF(cobro.fecha_emision IS NULL, 'Por Emitir', DATE_FORMAT(cobro.fecha_emision, '%m-%Y'))
 * * Muestra: IF(cobro.fecha_emision IS NULL, 'Por Emitir', DATE_FORMAT(cobro.fecha_emision, '%m-%Y'))
 * * Ordena por:  cobro.fecha_emision
 *
 * M�s info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Agrupador:-Mes-Emision
 */
class  extends AbstractGrouperTranslator {

	/**
	 * Obtiene el campo por el cual se agrupar� la query
	 * @return String Campo por el que se agrupa en par tabla.campo o alias
	 */
	function getGroupField() {
		return "IF(cobro.fecha_emision IS NULL, 'Por Emitir', DATE_FORMAT(cobro.fecha_emision, '%m-%Y'))";
	}

	/**
	 * Obtiene el campo de grupo que se devolver� en el SELECT de la query
	 * @return String par tabla.campo o alias de funci�n
	 */
	function getSelectField() {
		return "IF(cobro.fecha_emision IS NULL, 'Por Emitir', DATE_FORMAT(cobro.fecha_emision, '%m-%Y'))";
	}

	/**
	 * Obtiene el campo de grupo por el cual se ordenar� la query
	 * @return String par tabla.campo o alias de funci�n
	 */
	function getOrderField() {
		return "cobro.fecha_emision";
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Cobros
	 * Fecha de emisi�n de la liquidaci�n
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
	 * Traduce los keys de agrupadores a campos para la query de Tr�mites
	 * Fecha de emisi�n de la liquidaci�n del tr�mite
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
	 * Fecha de emisi�n de la liquidaci�n del trabajo
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
