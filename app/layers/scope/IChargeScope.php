<?php

/**
 * Class IChargeScope
 */
interface IChargeScope {

	/**
	 * Añade un filtro que considera a aquellos cobros que pueden ser facturados.
	 * @param $criteria
	 * @return mixed
	 */
	function canBeInvoiced(Criteria $criteria);

	/**
	 * Añade un filtro que considera a aquellos cobros que tienen trámites.
	 * @param Criteria $criteria
	 * @return mixed
	 */
	function hasErrands(Criteria $criteria);

	/**
	 * Añade un filtro que considera a aquellos cobros que tienen gastos.
	 * @param Criteria $criteria
	 * @return mixed
	 */
	function hasExpenses(Criteria $criteria);

	/**
	 * Añade un filtro que considera a aquellos cobros que tienen honorarios.
	 * @param Criteria $criteria
	 * @return mixed
	 */
	function hasFees(Criteria $criteria);

	/**
	 * Añade un filtro que considera a aquellos cobros que adelantos disponibles.
	 * @param Criteria $criteria
	 * @return mixed
	 */
	function hasAdvancesAvailables(Criteria $criteria);
} 