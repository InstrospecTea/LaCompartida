<?php

/**
 *
 * Clase con métodos para Settings
 *
 */
class SettingsAPI extends AbstractSlimAPI {

	public function getSettings() {
		$Session = $this->session;
		$Slim = $this->slim;
		$this->validateAuthTokenSendByHeaders();

		$settings = array();

		if (is_array($Session->arrayconf) && !empty($Session->arrayconf)) {
			if (array_key_exists('Intervalo', $Session->arrayconf)) {
				array_push($settings, array('code' => 'IncrementalStep', 'value' => $Session->arrayconf['Intervalo']));
			}

			if (array_key_exists('CantidadHorasDia', $Session->arrayconf)) {
				array_push($settings, array('code' => 'TotalDailyTime', 'value' => $Session->arrayconf['CantidadHorasDia']));
			}

			if (array_key_exists('UsarAreaTrabajos', $Session->arrayconf)) {
				array_push($settings, array('code' => 'UseWorkingAreas', 'value' => $Session->arrayconf['UsarAreaTrabajos']));
			}

			if (array_key_exists('UsoActividades', $Session->arrayconf)) {
				array_push($settings, array('code' => 'UseActivities', 'value' => $Session->arrayconf['UsoActividades']));
			}

			if (array_key_exists('UsarAreaTrabajos', $Session->arrayconf)) {
				array_push($settings, array('code' => 'UseAreas', 'value' => $Session->arrayconf['UsarAreaTrabajos']));
			}

			if (array_key_exists('GuardarTarifaAlIngresoDeHora', $Session->arrayconf)) {
				array_push($settings, array('code' => 'UseWorkRate', 'value' => $Session->arrayconf['GuardarTarifaAlIngresoDeHora']));
			}

			if (array_key_exists('OrdenadoPor', $Session->arrayconf)) {
				array_push($settings, array('code' => 'UseRequester', 'value' => $Session->arrayconf['OrdenadoPor']));
			}

			if (array_key_exists('TodoMayuscula', $Session->arrayconf)) {
				array_push($settings, array('code' => 'UseUppercase', 'value' => $Session->arrayconf['TodoMayuscula']));
			}

			if (array_key_exists('PermitirCampoCobrableAProfesional', $Session->arrayconf)) {
				array_push($settings, array('code' => 'AllowBillable', 'value' => $Session->arrayconf['PermitirCampoCobrableAProfesional']));
			} else {
				array_push($settings, array('code' => 'AllowBillable', 'value' => 0));
			}

			if (array_key_exists('MaxDuracionTrabajo', $Session->arrayconf)) {
				array_push($settings, array('code' => 'MaxWorkDuration', 'value' => $Session->arrayconf['MaxDuracionTrabajo']));
			}
		}

		$this->outputJson($settings);
	}

}
