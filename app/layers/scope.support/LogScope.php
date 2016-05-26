<?php

/**
 * Class ClientScope
 */
class LogScope implements ILogScope {

	/**
	 * Order by log date
	 * @param Criteria $criteria
	 * @param $order
	 * @return mixed
	 */
	function orderByDate(Criteria $criteria, $order) {
		$criteria->add_ordering('LogDatabase.fecha', empty($order) ? 'ASC' : $order);
		return $criteria;
	}
}
