<?php
/**
 * Class IActiIClientScopevityScope
 */
interface IClientScope {
	/**
	 * Filter by updated
	 * @param Criteria $criteria
	 * @return mixed
	 */
	function updatedFrom(Criteria $criteria, $updatedFrom);

	/* Order by client gloss
	 * @param Criteria $criteria
	 * @return mixed
	 */
	function orderByClientGloss(Criteria $criteria);
}
