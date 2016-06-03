<?php

/**
 * Class MatterScope
 */
class TaskScope implements ITaskScope {

	function updatedFrom(Criteria $criteria, $updatedFrom) {

		$or_clauses = array();
		$or_clauses[] = CriteriaRestriction::greater_or_equals_than('Task.fecha_creacion', "'{$updatedFrom}'");
		$or_clauses[] = CriteriaRestriction::greater_or_equals_than('Task.fecha_modificacion', "'{$updatedFrom}'");

		$criteria->add_restriction(CriteriaRestriction::or_clause($or_clauses));

		return $criteria;
	}

}
