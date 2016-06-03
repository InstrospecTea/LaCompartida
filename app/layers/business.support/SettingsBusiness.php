<?php

/**
* Settings businesss
*/
class SettingsBusiness extends AbstractBusiness implements ISettingsBusiness {

	function getValueOf($value) {
		$this->loadBusiness('Searching');

		$searchCriteria = new SearchCriteria('Setting');
		$searchCriteria->filter('glosa_opcion')->restricted_by('equals')->compare_with("'$value'");
		$results = $this->SearchingBusiness->searchByCriteria($searchCriteria);

		if (empty($results)) {
			throw new BusinessException("There is not a defined setting with provided code '$value'.");
		}

		$setting = $results[0];
		return $setting->get('valor_opcion');
	}

	/**
	 * Obtiene configuraciones de Time Tacking
	 * @return [type] [description]
	 */
	function getTimeTrackingSettings() {
		$this->loadBusiness('Searching');

		$searchCriteria = new SearchCriteria('Setting');
		$settingCodes = $this->getTimeTrackingSettingCodes();

		$searchCriteria
			->filter('glosa_opcion')
			->restricted_by('in')
			->compare_with(array_keys($settingCodes));

		$results = $this->SearchingBusiness->searchByCriteria($searchCriteria);

		foreach ($results as $setting) {
			$key = $setting->get('glosa_opcion');
			$code = $settingCodes[$key];
			$id = $setting->get('id');
			$setting->set('id', intval($id));
			$setting->set('glosa_opcion', $code);
			$setting->set('typed_value', $this->typedValue(
				$setting->get('valor_opcion'),
				$setting->get('valores_posibles')
			));
		}

		return $results;
	}

	function typedValue($value, $type) {
		switch ($type) {
			case 'json':
				return json_decode($value);
				break;
			case 'numero':
				return intval($value);
				break;
			case 'boolean':
				return intval($value) === 1;
				break;
			default:
				return $value;
				break;
		}
	}

	function getTimeTrackingSettingCodes() {
		return array(
			'Intervalo' => 'incremental_step',
			'CantidadHorasDia' => 'max_daily_duration',
			'UsarAreaTrabajos' => 'use_working_areas',
			'UsoActividades' => 'use_activities',
			'GuardarTarifaAlIngresoDeHora' => 'use_work_rate',
			'OrdenadoPor' => 'use_requester',
			'TodoMayuscula' => 'use_uppercase',
			'PermitirCampoCobrableAProfesional' => 'allow_billable',
			'MaxDuracionTrabajo' => 'max_work_duration',
			'ZonaHoraria' => 'timezone',
			'Idioma' => 'language',
			'UsarClientesEnTracker' => 'use_clients'
		);
	}
}
