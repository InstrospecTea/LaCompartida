<?php
/**
 * Class ILogScope
 */
interface ILogScope {

	/**
	 * Order by log date
	 * @param Criteria $criteria
	 * @param $order
	 * @return mixed
	 */
	function orderByDate(Criteria $criteria, $order);
}
