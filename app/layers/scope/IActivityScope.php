<?php
/**
 * Class IActivityScope
 */
interface IActivityScope {
	/**
	 * Filter by Project dadta
	 * @param Criteria $criteria
	 * @return mixed
	 */
	function matterRestrictions(Criteria $criteria, $matter);
}
