<?php

/**
 * Class IDocumentScope
 */
interface IDocumentScope {

	/**
	 * Agrega filtro que considera a aquellos documentos que son adelanto.
	 * @param Criteria $criteria
	 */
	function isAdvance(Criteria $criteria);

	/**
	 * Agrega filtro que considera a aquellos documentos que tienen saldo
	 * @param Criteria $criteria
	 */
	function hasBalance(Criteria $criteria);

	/**
	 * Agrega filtro que considera a aquellos documentos que perteneces a un contrato o a ninguno.
	 * @param Criteria $criteria
	 * @param type $contract_id
	 */
	function hasOrNotContract(Criteria $criteria, $contract_id);

}