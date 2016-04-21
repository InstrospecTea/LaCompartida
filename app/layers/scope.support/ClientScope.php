<?php

/**
 * Class ClientScope
 */
class ClientScope implements IClientScope {

	/**
	 * Order by client gloss
	 * @param Criteria $criteria
	 * @return mixed
	 */
	function orderByClientGloss(Criteria $criteria) {
		$criteria->add_ordering('Client.glosa_cliente', 'ASC');
		return $criteria;
	}

	function updatedFrom(Criteria $criteria, $updatedFrom) {

		$or_clauses = array();
		$or_clauses[] = CriteriaRestriction::greater_or_equals_than('Client.fecha_touch', "'{$updatedFrom}'");
		$or_clauses[] = CriteriaRestriction::greater_or_equals_than('Client.fecha_creacion', "'{$updatedFrom}'");
		$or_clauses[] = CriteriaRestriction::greater_or_equals_than('Client.fecha_modificacion', "'{$updatedFrom}'");

		$criteria->add_restriction(CriteriaRestriction::or_clause($or_clauses));

		return $criteria;
	}

}
