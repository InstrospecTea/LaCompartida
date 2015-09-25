<?php

/**
 *
 */
abstract class FilterDependantGrouperTranslator extends AbstractGrouperTranslator {
	/**
	 * Array que contiene los valores de los filtros de los que depende este agrupador.
	 * @var array
	 */
	var $filterValues = array();

	/**
	 * Asigna el valor de un filtro al agrupador.
	 * @param String $filter El nombre del filtro
	 * @param Mixed $value  El valor del filtro
	 */
	public function setFilterValue($filter, $value) {
		$this->filterValues[$filter] = $value;
	}

	/**
	 * Obtiene un array que explicita a aquellos filtros de los que este
	 * agrupador depende.
	 */
	abstract public function getFilterDependences();
}