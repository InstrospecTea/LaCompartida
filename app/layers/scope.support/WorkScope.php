<?php

/**
* 		
*/
class WorkScope implements IWorkScope {
	
	/**
	 * Ordena los trabajos desde el más viejo al más nuevo.
	 * @param  Criteria $criteria 
	 * @return Criteria $criteria
	 */
	function orderFromOlderToNewer(Criteria $criteria) {
		$criteria->add_ordering('Work.fecha', 'ASC');
		return $criteria;
	}
}