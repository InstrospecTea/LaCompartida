<?php

/**
 * Class MatterScope
 */
class MatterScope implements IMatterScope {

	function updatedFrom(Criteria $criteria, $updatedFrom) {

		$or_clauses = array();
		$or_clauses[] = CriteriaRestriction::greater_or_equals_than('Matter.fecha_touch', "'{$updatedFrom}'");
		$or_clauses[] = CriteriaRestriction::greater_or_equals_than('Matter.fecha_creacion', "'{$updatedFrom}'");
		$or_clauses[] = CriteriaRestriction::greater_or_equals_than('Matter.fecha_modificacion', "'{$updatedFrom}'");

		$criteria->add_restriction(CriteriaRestriction::or_clause($or_clauses));

		return $criteria;
	}

}
