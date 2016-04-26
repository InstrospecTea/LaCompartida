<?php

class TasksBusiness extends AbstractBusiness implements ITasksBusiness {

	public function getUpdatedTasks($active = null, $updatedFrom = null) {
		$results = array();
		$this->loadBusiness('Searching');

		$searchCriteria = new SearchCriteria('Task');
		$searchCriteria->related_with('Matter')->on_property('codigo_asunto');
		$searchCriteria->related_with('Client')->on_property('codigo_cliente');

		// No existe en la tabla
		// if (!is_null($active)) {
		// 	$searchCriteria
		// 		->filter('activo')
		// 		->restricted_by('equals')
		// 		->compare_with($active);
		// }

		if (!is_null($updatedFrom)) {
			$updatedFromDate = date('Y-m-d', $updatedFrom);
			$searchCriteria->add_scope(
				'updatedFrom',
				array('args' => array($updatedFromDate))
			);
		}

		$results = $this->SearchingBusiness->searchByCriteria(
			$searchCriteria,
			array(
				'*',
				'Matter.id_asunto',
				'Client.id_cliente'
			)
		);

		return $results;
	}

}
