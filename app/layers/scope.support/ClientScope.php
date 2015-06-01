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
}
