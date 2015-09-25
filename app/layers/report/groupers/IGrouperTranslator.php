<?php

/**
 * Interfaz que representa los traductores de Grupos
 */
interface IGrouperTranslator {

	/**
	 * Obtiene el campo por el cual se agrupará la query
	 * @return String Campo por el que se agrupa en par tabla.campo o alias
	 */
	function getGroupField();

	/**
	 * Obtiene el campo de grupo que se devolverá en el SELECT de la query
	 * @return String par tabla.campo o alias de función
	 */
	function getSelectField();

	/**
	 * Obtiene el campo de grupo por el cual se ordenará la query
	 * @return String par tabla.campo o alias de función
	 */
	function getOrderField();

	/**
	 * Traduce los keys de agrupadores a campos para la query de Cobros
	 * @return void
	 */
	function translateForCharges(Criteria $criteria);

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trámites
	 * @return void
	 */
	function translateForErrands(Criteria $criteria);

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trabajos
	 * @return void
	 */
	function translateForWorks(Criteria $criteria);

}