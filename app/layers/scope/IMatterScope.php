<?php
/**
 * Class IMatterScope
 */
interface IMatterScope {
	/**
	 * Filter by updated
	 * @param Criteria $criteria
	 * @return mixed
	 */
	function updatedFrom(Criteria $criteria, $updatedFrom);

}
