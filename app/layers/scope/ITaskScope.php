<?php
/**
 * Class ITaskScope
 */
interface ITaskScope {
	/**
	 * Filter by updated
	 * @param Criteria $criteria
	 * @return mixed
	 */
	function updatedFrom(Criteria $criteria, $updatedFrom);

}
