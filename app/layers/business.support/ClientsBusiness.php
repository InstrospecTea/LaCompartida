<?php

class ClientsBusiness extends AbstractBusiness implements IClientsBusiness {

	public function getUpdatedClients($active = null, $updatedFrom = null) {
		$results = array();
		$this->loadBusiness('Searching');

		$searchCriteria = new SearchCriteria('Client');

		if (!is_null($active)) {
			$searchCriteria
				->filter('activo')
				->restricted_by('equals')
				->compare_with($active);
		}

		if (!is_null($updatedFrom)) {
			$updatedFromDate = date('Y-m-d', $updatedFrom);
			$searchCriteria->add_scope(
				'updatedFrom',
				array('args' => array($updatedFromDate))
			);
		}

		$results = $this->SearchingBusiness->searchByCriteria($searchCriteria);

		return $results;
	}

	public function getClientById($client_id) {
		$this->loadBusiness('Searching');

		$searchCriteria =  new SearchCriteria('Client');
		$searchCriteria
			->filter('id_cliente')
			->restricted_by('equals')
			->compare_with($client_id);

		$clients = $this->SearchingBusiness->searchByCriteria($searchCriteria);
		return $clients[0];
	}

	public function getMattersOfClient($client) {
		$results = array();
		$this->loadBusiness('Searching');
		$clientCode = $client->fields['codigo_cliente'];

		$searchCriteria =  new SearchCriteria('Matter');

		$searchCriteria
			->related_with('Client')->with_direction('INNER')->on_property('codigo_cliente');

		$searchCriteria
			->related_with('Language')->with_direction('LEFT')->on_property('id_idioma');

		$results = $this->SearchingBusiness->searchByCriteria(
			$searchCriteria,
			array(
				'*',
				'Client.id_cliente',
				'Language.codigo_idioma',
				'Language.glosa_idioma'
			)
		);
		return $results;
	}


	public function getUpdatedMatters($active = null, $updatedFrom = null) {
		$results = array();
		$this->loadBusiness('Searching');

		$searchCriteria = new SearchCriteria('Matter');

		if (!is_null($active)) {
			$searchCriteria
				->filter('activo')
				->restricted_by('equals')
				->compare_with($active);
		}

		if (!is_null($updatedFrom)) {
			$updatedFromDate = date('Y-m-d', $updatedFrom);
			$searchCriteria->add_scope(
				'updatedFrom',
				array('args' => array($updatedFromDate))
			);
		}

		$searchCriteria
			->related_with('Client')->with_direction('INNER')->on_property('codigo_cliente');

		$searchCriteria
			->related_with('Language')->with_direction('LEFT')->on_property('id_idioma');

		$results = $this->SearchingBusiness->searchByCriteria(
			$searchCriteria,
			array(
				'*',
				'Client.id_cliente',
				'Language.codigo_idioma',
				'Language.glosa_idioma'
			)
		);

		return $results;
	}

}
