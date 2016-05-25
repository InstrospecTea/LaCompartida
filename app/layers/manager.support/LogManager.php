<?php

class LogManager extends AbstractManager implements ILogManager {
	/**
	 * Obtiene la bitácora de una tabla en cierto movimiento
	 * @param 	string $table_title
	 * @param 	string $id_field
	 * @return 	Array humanizado
	 */
	public function getLogs($table_title, $id_field) {
		$this->loadService('LogDatabase');

		$restrictions = CriteriaRestriction::and_clause(
			CriteriaRestriction::equals('titulo_tabla', "'{$table_title}'"),
			CriteriaRestriction::equals('id_field', $id_field)
		);

		$Logs = $this->LogDatabaseService->findAll($restrictions, null, array('log_db.fecha DESC'));
		$Logs = $this->replaceCodes($Logs);

		return Humanize::convert($table_title, $Logs);
	}

	/**
	 * Reemplaza los códigos por campos humanizados
	 * @param 	Array $logs
	 * @return 	Array
	 */
	private function replaceCodes($logs) {
		foreach ($logs as $key => $value) {
			switch ($value->get('campo_tabla')) {
				case 'id_idioma':
					$service_name = 'Language';
					$field = 'glosa_idioma';
					break;

				case 'id_area_proyecto':
					$service_name = 'ProjectArea';
					$field = 'glosa';
					break;
				case 'id_encargado':
					$service_name = 'User';
					$field = "concat(nombre, ' ', apellido1)";
					break;
				case 'codigo_cliente':
					$service_name = 'Client';
					$field = "glosa_cliente";
					break;
				case 'id_tipo_asunto':
					$service_name = 'MatterType';
					$field = "glosa_tipo_proyecto";
					break;
			}

			if (!isset($service_name)) {
				continue;
			}

			$service_class = "{$service_name}Service";

			$this->loadService("{$service_name}");
			$old_value = $this->$service_class->get($value->get('valor_antiguo'), "{$field}");
			$new_value = $this->$service_class->get($value->get('valor_nuevo'), "{$field}");
			$logs[$key]->fields['valor_antiguo'] = $old_value->get($field);
			$logs[$key]->fields['valor_nuevo'] = $new_value->get($field);
			unset($service_name);
			unset($field);
		}

		return $logs;
	}
}
