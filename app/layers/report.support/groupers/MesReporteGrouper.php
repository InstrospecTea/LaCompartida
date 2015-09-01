<?php
/**
 * Agrupador por Mes del reporte
 *
 * * Agrupa por: DATE_FORMAT(fecha, '%m-%Y')
 * * Muestra: DATE_FORMAT(fecha, '%m-%Y')
 * * Ordena por:  DATE_FORMAT(fecha, '%m-%Y')
 *
 * Más info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Agrupador:-Mes-Reporte
 */
class MesReporteGrouper extends AbstractGrouperTranslator {

	/**
	 * Obtiene el campo por el cual se agrupará la query
	 * @return String Campo por el que se agrupa en par tabla.campo o alias
	 */
	function getGroupField() {
		return "DATE_FORMAT(fecha, '%m-%Y')";
	}

	/**
	 * Obtiene el campo de grupo que se devolverá en el SELECT de la query
	 * @return String par tabla.campo o alias de función
	 */
	function getSelectField() {
		return "DATE_FORMAT(fecha, '%m-%Y')";
	}

	/**
	 * Obtiene el campo de grupo por el cual se ordenará la query
	 * @return String par tabla.campo o alias de función
	 */
	function getOrderField() {
		return "DATE_FORMAT(fecha, '%m-%Y')";
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Cobros
	 * IMPORTANT!!! mes-año de la Fecha que venga en el filtro campo_fecha
	 * @return void
	 */
	function translateForCharges(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'mes_reporte')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trámites
	 * mes-año de la fecha del trámite
	 * @return void
	 */
	function translateForErrands(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'mes_reporte')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trabajos
	 * mes-año de la fecha del trabajo
	 * @return void
	 */
	function translateForWorks(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'mes_reporte')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}
}
