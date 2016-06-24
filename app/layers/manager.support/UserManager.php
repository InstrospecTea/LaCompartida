<?php

class UserManager extends AbstractManager implements IUserManager {
	/**
	 * Obtiene el total de horas trabajadas de un usuario
	 * @param 	string $user_id
	 * @param 	string $init_date (Y-m-d)
	 * @param 	string $end_date (Y-m-d)
	 * @return 	String
	 */
	public function getHoursWorked($user_id, $init_date = null, $end_date = null) {
		if (empty($user_id) || !is_numeric($user_id)) {
			throw new InvalidIdentifier;
		}

		$this->loadService('Work');

		$restrictions = $this->generateRestrictions($user_id, $init_date, $end_date);
		$Works = $this->WorkService->findAll($restrictions, 'duracion');

		return $this->calculateHours($Works);
	}

	/**
	 * Obtiene el total de horas trabajadas de un usuario con filtros
	 * @param 	string $user_id
	 * @param 	string $client_id
	 * @param 	array $areas
	 * @param 	array $categories
	 * @param 	string $init_date (Y-m-d)
	 * @param 	string $end_date (Y-m-d)
	 * @return 	String
	 */
	public function getHoursWorkedByFilters($user_id, $client_id = null, $areas = null, $categories = null, $init_date = null, $end_date = null) {
		if (empty($user_id) || !is_numeric($user_id)) {
			throw new InvalidIdentifier;
		}

		$this->loadManager('Search');
		$SearchCriteria = new SearchCriteria('Work');

		$SearchCriteria
			->related_with('Matter')
			->with_direction('LEFT')
			->on_property('codigo_asunto')
			->on_entity_property('codigo_asunto');

		$SearchCriteria
			->related_with('User')
			->with_direction('LEFT')
			->on_entity_property('id_usuario');

		$SearchCriteria
			->related_with('Client')
			->with_direction('LEFT')
			->joined_with('Matter')
			->on_entity_property('codigo_cliente');

		$SearchCriteria
			->filter('id_usuario')
			->for_entity('Work')
			->restricted_by('equals')
			->compare_with($user_id);

		$SearchCriteria
				->filter('activo')
				->for_entity('User')
				->restricted_by('equals')
				->compare_with(1);

		if (!empty($client_id)) {
			if (Conf::GetConf($this->Sesion, 'CodigoSecundario')) {
				$client_code = 'codigo_cliente_secundario';
			} else {
				$client_code = 'codigo_cliente';
			}

			$SearchCriteria
				->filter($client_code)
				->for_entity('Client')
				->restricted_by('in')
				->compare_with($client_id);
		}

		if (!empty($areas)) {
			$SearchCriteria
				->filter('id_area_usuario')
				->for_entity('User')
				->restricted_by('in')
				->compare_with($areas);
		}

		if (!empty($categories)) {
			$SearchCriteria
				->filter('id_categoria_usuario')
				->for_entity('User')
				->restricted_by('in')
				->compare_with($categories);
		}

		if (!empty($init_date) && \Utiles::es_fecha_sql($init_date)){
			$SearchCriteria
				->filter('fecha')
				->restricted_by('greater_than')
				->compare_with("'{$init_date}'");
		}

		if (!empty($end_date) && \Utiles::es_fecha_sql($end_date)){
			$SearchCriteria
				->filter('fecha')
				->restricted_by('lower_than')
				->compare_with("'{$end_date}'");
		}

		$Works = $this->SearchManager->searchByCriteria(
			$SearchCriteria,
			array(
				'Work.duracion'
			)
		);

		return $this->calculateHours($Works->toArray());
	}

	private function generateRestrictions($user_id, $init_date, $end_date){
		$restrictions_array = array(CriteriaRestriction::equals('id_usuario', $user_id));

		if (!empty($init_date) && \Utiles::es_fecha_sql($init_date)){
			array_push($restrictions_array, CriteriaRestriction::greater_than('fecha', "'{$init_date}'"));
		}
		if (!empty($end_date) && \Utiles::es_fecha_sql($end_date)){
			array_push($restrictions_array, CriteriaRestriction::lower_than('fecha', "'{$end_date}'"));
		}

		return CriteriaRestriction::and_clause($restrictions_array);
	}

	private function calculateHours($Works){
		$timestamp = new \Carbon\Carbon('00:00:00');
		foreach ($Works as $key => $value) {
			$Carbon = new \Carbon\Carbon($value->get('duracion'));

			$timestamp->addHours($Carbon->hour);
			$timestamp->addMinutes($Carbon->minute);
			$timestamp->addSeconds($Carbon->second);
		}

		if ($timestamp->eq(new \Carbon\Carbon('00:00:00'))) {
			return null;
		}

		return $timestamp->toTimeString();
	}
}
