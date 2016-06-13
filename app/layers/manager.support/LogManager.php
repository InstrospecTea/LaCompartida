<?php

class LogManager extends AbstractManager implements ILogManager {
	/**
	 * Obtiene la bit�cora de una tabla en cierto movimiento
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
	 * Reemplaza los c�digos por campos humanizados
	 * @param 	Array $logs
	 * @return 	Array
	 */
	private function replaceCodes($logs, $table_title) {
		$relations = Humanize::getRelations($table_title);
		foreach ($logs as $key => $value) {
			$campo_tabla = $value->get('campo_tabla');

			if (!isset($relations[$campo_tabla])) {
				continue;
			}

			if (strtolower($value->get('valor_nuevo')) == 'null') {
				$logs[$key]->set('valor_nuevo', '');
			}

			$service_name = $relations[$campo_tabla]['service_name'];
			$service_class = "{$service_name}Service";
			$field = $relations[$campo_tabla]['field'];
			$old_value_field = $value->get('valor_antiguo');
			$new_value_field = $value->get('valor_nuevo');

			$special_service_method = $relations[$campo_tabla]['special_service_method'];
			$service_method = $special_service_method ? $special_service_method : 'get';

			$this->loadService($service_name);
			try {
				$old_value = $this->$service_class->$service_method($old_value_field, $field);
				$logs[$key]->set('valor_antiguo', $old_value->get($field));
			} catch (ServiceException $e) {
			}

			try {
				$new_value = $this->$service_class->$service_method($new_value_field, $field);
				$logs[$key]->set('valor_nuevo', $new_value->get($field));
			} catch (ServiceException $e) {
			}
		}

		return $logs;
	}

	/**
	 * Obtiene los logs con el nombre del usuario
	 * @param 	string $table_title
	 * @param 	string $id_field
	 * @return 	SplFixedArray
	 */
	private function getLogsWithUsername($table_title, $id_field) {
		$this->loadManager('Search');

		$logSearchCriteria = new SearchCriteria('LogDatabase');
		$logSearchCriteria
			->related_with('User')
			->with_direction('LEFT')
			->on_property('id_usuario')
			->on_entity_property('usuario');
		$logSearchCriteria
			->filter('titulo_tabla')
			->restricted_by('equals')
			->compare_with("'{$table_title}'");
		$logSearchCriteria
			->filter('id_field')
			->restricted_by('equals')
			->compare_with($id_field);
		$logSearchCriteria
			->add_scope_for('Log', 'orderByDate', array('args' => array('DESC')));
		$logResults = $this->SearchManager->searchByCriteria(
			$logSearchCriteria,
			array('*', "CONCAT(User.nombre, ' ', User.apellido1) AS username")
		);

		return $logResults;
	}
}
