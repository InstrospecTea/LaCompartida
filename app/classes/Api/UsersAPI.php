<?php

/**
 *
 * Clase con métodos para Usuarios
 *
 */
class UsersAPI extends AbstractSlimAPI {

	public function getUserById($id) {
		$Session = $this->session;
		$Slim = $this->slim;

		if (is_null($id) || empty($id)) {
			$this->halt(__('Invalid user ID'), 'InvalidUserID');
		}

		$this->validateAuthTokenSendByHeaders();

		$User = new Usuario($Session);
		$user = array();

		if (!$User->LoadId($id)) {
			$this->halt(__("The user doesn't exist"), 'UserDoesntExist');
		} else {
			$max_daily_minutes = method_exists('Conf','CantidadHorasDia') ? Conf::CantidadHorasDia() : 1439;
			$user = array(
				'id' => (int) $User->fields['id_usuario'],
				'code' => $User->fields['rut'],
				'name' => $User->fields['nombre'] . ' ' . $User->fields['apellido1'] . ' ' . $User->fields['apellido2'],
				'weekly_alert' => !empty($User->fields['alerta_semanal']) ? (int) $User->fields['alerta_semanal'] : null,
				'daily_alert' =>  !empty($User->fields['alerta_diaria']) ? (int) $User->fields['alerta_diaria'] : null,
				'min_daily_hours' => !empty($User->fields['restriccion_diario']) ? (float) $User->fields['restriccion_diario'] : null,
				'max_daily_hours' => (float) ($max_daily_minutes / 60.0),
				'min_weekly_hours' => !empty($User->fields['restriccion_min']) ? $User->fields['restriccion_min'] : null,
				'max_weekly_hours' => !empty($User->fields['restriccion_max']) ? $User->fields['restriccion_max'] : null,
				'days_track_works' => !empty($User->fields['dias_ingreso_trabajo']) ? $User->fields['dias_ingreso_trabajo'] : null,
				'receive_alerts' => !empty($User->fields['receive_alerts']) ? $User->fields['receive_alerts'] : 0,
				'alert_hour' => !empty($User->fields['alert_hour']) ? $this->time2seconds($User->fields['alert_hour']) : 0
			);
		}

		$this->outputJson($user);
	}

	public function updateUserSettings($id) {
		$Session = $this->session;
		$Slim = $this->slim;

		if (is_null($id) || empty($id)) {
			$this->halt(__('Invalid user ID'), 'InvalidUserID');
		}

		$this->validateAuthTokenSendByHeaders();

		$User = new Usuario($Session);
		$receive_alerts = (int) $Slim->request()->params('receive_alerts');
		$alert_hour = $Slim->request()->params('alert_hour');

		if (!$User->LoadId($id)) {
			$this->halt(__("The user doesn't exist"), 'UserDoesntExist');
		} else {
			$User->Edit('receive_alerts', $receive_alerts);
			$User->Edit('alert_hour', date('H:i:s', $alert_hour));

			if (!$User->Write()) {
				$this->halt(__('Unexpected error when saving data'), 'UnexpectedSave');
			}

			$max_daily_minutes = method_exists('Conf','CantidadHorasDia') ? Conf::CantidadHorasDia() : 1439;
			$user = array(
				'id' => (int) $User->fields['id_usuario'],
				'code' => $User->fields['rut'],
				'name' => $User->fields['nombre'] . ' ' . $User->fields['apellido1'] . ' ' . $User->fields['apellido2'],
				'weekly_alert' => !empty($User->fields['alerta_semanal']) ? (int) $User->fields['alerta_semanal'] : null,
				'daily_alert' =>  !empty($User->fields['alerta_diaria']) ? (int) $User->fields['alerta_diaria'] : null,
				'min_daily_hours' => !empty($User->fields['restriccion_diario']) ? (float) $User->fields['restriccion_diario'] : null,
				'max_daily_hours' => (float) ($max_daily_minutes / 60.0),
				'min_weekly_hours' => !empty($User->fields['restriccion_min']) ? $User->fields['restriccion_min'] : null,
				'max_weekly_hours' => !empty($User->fields['restriccion_max']) ? $User->fields['restriccion_max'] : null,
				'days_track_works' => !empty($User->fields['dias_ingreso_trabajo']) ? $User->fields['dias_ingreso_trabajo'] : null,
				'receive_alerts' => !empty($User->fields['receive_alerts']) ? $User->fields['receive_alerts'] : 0,
				'alert_hour' => !empty($User->fields['alert_hour']) ? $this->time2seconds($User->fields['alert_hour']) : 0
			);

			$this->outputJson($user);
		}
	}

}
