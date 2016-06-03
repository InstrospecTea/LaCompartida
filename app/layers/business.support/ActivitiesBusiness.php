<?php

class ActivitiesBusiness extends AbstractBusiness implements IActivitiesBusiness {

	public function getActivitesByMatterId($matterId = null, $active = null, $all = false) {
		$activities = array();
		$matter = null;

		$this->loadBusiness('Searching');

		$searchCriteria = new SearchCriteria('Activity');
		$searchCriteria->related_with('Matter')->on_property('codigo_asunto');

		if (!empty($matterId)) {
			$matterCriteria = new SearchCriteria('Matter');

			$matterCriteria
				->filter('id_asunto')
				->restricted_by('equals')
				->compare_with($matterId);

			$matters = $this->SearchingBusiness->searchByCriteria($matterCriteria);
			$matter = $matters[0];
		}

		if (!$all) {
			$searchCriteria->add_scope(
				'matterRestrictions',
				array('args' => array($matter))
			);
		}

		if (!is_null($active)) {
			$searchCriteria
				->filter('activo')
				->restricted_by('equals')
				->compare_with($active);
		}

		$activities = $this->SearchingBusiness->searchByCriteria(
			$searchCriteria,
			array(
				'*',
				'Matter.id_asunto'
			)
		);

		return $activities;
	}

}
