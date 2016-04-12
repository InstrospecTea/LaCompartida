<?php

class UsersBusiness extends AbstractBusiness implements IUsersBusiness {

	public function getUserById($userId, $includes = array()) {
		$this->loadBusiness('Searching');
		$searchCriteria =  new SearchCriteria('User');
		$searchCriteria
			->filter('id_usuario')
			->restricted_by('equals')
			->compare_with($userId);

		$users = $this->SearchingBusiness->searchByCriteria(
			$searchCriteria,
			array(
				'*',
				'CONCAT(nombre, " ", apellido1, " ", apellido2) AS full_name',
				'TIME_TO_SEC(User.alert_hour) AS alert_seconds'
			)
		);

		$user = $users[0];

		if (!empty($user) && in_array('settings', $includes)) {
			$settings = $this->buildUserSettings($user);
			$user->set('settings', $settings);
		}

		return $user;
	}

	public function getRoles($userId)  {
		$this->loadBusiness('Searching');
		$searchCriteria = new SearchCriteria('UserPermission');

		$searchCriteria
			->filter('id_usuario')
			->restricted_by('equals')
			->compare_with($userId);

		$result = $this->SearchingBusiness->searchByCriteria(
			$searchCriteria
		);
		$results = array();
		foreach ($result as $userRole) {
			$results[] = $userRole->get('codigo_permiso');
		}
		return $results;
	}

	private function getUserSettings($user) {
		$settings = array(
			'max_daily_hours' => (1439 / 60.0),
			'weekly_alert' => (int) $user->get('alerta_semanal'),
			'daily_alert' => (int) $user->get('alerta_diaria'),
			'min_daily_hours' => (float) $user->get('restriccion_diario'),
			'min_weekly_hours' => (float) $user->get('restriccion_min'),
			'max_weekly_hours' => (float) $user->get('restriccion_max'),
			'days_track_works' => (int) $user->get('dias_ingreso_trabajo'),
			'receive_alerts' => (int) $user->get('receive_alerts'),
			'alert_hour' => $user->get('alert_seconds')
		);
		return $settings;
	}

	private function buildUserSettings($user) {

		$settings = $this->getUserSettings($user);
		$maxDailyHours = Conf::GetConf($this->Session, 'CantidadHorasDia');

		if (!empty($maxDailyHours)) {
			$settings['max_daily_hours'] = $maxDailyHours / 60.0;
		}
		return $settings;
	}

}
