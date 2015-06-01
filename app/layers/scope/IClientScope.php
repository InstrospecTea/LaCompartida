<?php
/**
 * Class IClientScope
 */
interface IClientScope {
	/**
	 * Order by client gloss
	 * @param Criteria $criteria
	 * @return mixed
	 */
	function orderByClientGloss(Criteria $criteria);
}
