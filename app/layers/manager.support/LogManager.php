<?php

class LogManager extends AbstractManager implements ILogManager {
	/**
	 * Obtiene la bitácora de una tabla en cierto movimiento
	 * @param 	string $table_title
	 * @param 	string $id_field
	 * @return 	Array humanizado
	 */
	public function getLogs($table_title, $id_field) {
		$Logs = $this->getLogsWithUsername($table_title, $id_field);
		$Logs = $this->replaceCodes($Logs, $table_title);

		return Humanize::convert($table_title, $Logs);
	}

	/**
	 * Reemplaza los códigos por campos humanizados
	 * @param 	Array $logs
	 * @return 	Array
	 */
	private function replaceCodes($logs, $table_title) {
		$relations = Humanize::getRelations($table_title);
		foreach ($logs as $key => $value) {
			$campo_tabla = $value->get('campo_tabla');

			if (is_null($relations[$campo_tabla])) {
				continue;
			}

			$service_name = $relations[$campo_tabla]['service_name'];
			$service_class = "{$service_name}Service";

			$this->loadService($service_name);
			$old_value = $this->$service_class->get($value->get('valor_antiguo'), $relations[$campo_tabla]['field']);
			$new_value = $this->$service_class->get($value->get('valor_nuevo'), $relations[$campo_tabla]['field']);
			$logs[$key]->fields['valor_antiguo'] = $old_value->get($relations[$campo_tabla]['field']);
			$logs[$key]->fields['valor_nuevo'] = $new_value->get($relations[$campo_tabla]['field']);
		}

		return $logs;
	}

	private function getLogsWithUsername($table_title, $id_field) {
		$this->loadManager("Search");

		$chargeSearchCriteria = new SearchCriteria('LogDatabase');
		$chargeSearchCriteria
			->related_with('User')
			->with_direction('LEFT')
			->on_property('id_usuario')
			->on_entity_property('usuario');
		$chargeSearchCriteria
			->filter('titulo_tabla')
			->restricted_by('equals')
			->compare_with("'{$table_title}'");
		$chargeSearchCriteria
			->filter('id_field')
			->restricted_by('equals')
			->compare_with($id_field);
		$chargeSearchCriteria
			->add_scope_for('Log', 'orderByDate', array('args' => array('DESC')));
		$chargeResults = $this->SearchManager->searchByCriteria(
			$chargeSearchCriteria,
			array('*', "CONCAT(User.nombre, ' ', User.apellido1) as username")
		);

		return $chargeResults;
	}
}
