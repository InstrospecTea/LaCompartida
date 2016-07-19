<?php

abstract class Humanize {
	/**
	 * Agrega el texto humanizado a la data
	 * @param 	string $table_name
	 * @param 	Array $data
	 * @return 	Array humanizado
	 */
	public static function convert($table_name, $data) {
		$rules = self::getRules($table_name);
		$black_list = self::getBlackList($table_name);

		if ($rules === false) {
			return $data;
		}

		foreach ($data as $key => $logs) {
			if ($black_list[$logs->get('campo_tabla')]) {
				$data->offsetUnset($key);
				continue;
			}
			$method = $rules[$logs->get('campo_tabla')];
			$data[$key]->fields['humanized'] = self::$method($logs);
		}

		return $data;
	}

	/**
	 * Obtiene las relaciones de una tabla
	 * @param 	string $table_name
	 * @return 	Array
	 */
	public static function getRelations($table_name) {
		$class_name = self::getClassName($table_name);

		if (class_exists($class_name)) {
			return $class_name::$relations;
		}
		return false;
	}

	/**
	 * Obtiene las reglas de una tabla
	 * @param 	string $table_name
	 * @return 	Array
	 */
	protected static function getRules($table_name) {
		$class_name = self::getClassName($table_name);

		if (class_exists($class_name)) {
			return $class_name::$rules;
		}
		return false;
	}

	/**
	 * Obtiene la lista de campos ignorados
	 * @param 	string $table_name
	 * @return 	Array
	 */
	protected static function getBlackList($table_name) {
		$class_name = self::getClassName($table_name);

		if (class_exists($class_name)) {
			return $class_name::$black_list;
		}
		return false;
	}

	/**
	 * Obtiene el diccionario de una tabla
	 * @param 	string $table_name
	 * @return 	Array
	 */
	protected static function getDictionary($table_name) {
		$class_name = self::getClassName($table_name);

		if (class_exists($class_name)) {
			return $class_name::$dictionary;
		}
		return false;
	}

	/**
	 * Obtiene el nombre de una clase
	 * @param 	string $table_name
	 * @return 	string
	 */
	private static function getClassName($table_name) {
		if (isset($table_name) && !empty($table_name)) {
			return Utiles::pascalize("{$table_name}Humanize");
		}
		return '';
	}

	/**
	 * Obtiene el nombre de la tabla
	 * @param 	array $data
	 * @return 	string
	 */
	private static function getTableName($data) {
		if (isset($data) && !empty($data)) {
			return Utiles::pascalize($data->fields['titulo_tabla']);
		}
		return '';
	}

	/**
	 * Genera texto humanizado valor por valor
	 * @param 	Array $data
	 * @return 	String
	 */
	protected static function valueForValue($data) {
		$dictionary = self::getDictionary($data->fields['titulo_tabla']);
		$data->fields['valor_antiguo'] = trim($data->fields['valor_antiguo']);
		$field_humanize = $dictionary[$data->fields['campo_tabla']];

		if (!empty($data->fields['valor_antiguo']) && !empty($data->fields['valor_nuevo'])) {
			$string_humanize .= "{$field_humanize} ha cambiado de {$data->fields['valor_antiguo']} a {$data->fields['valor_nuevo']}";
		} else if (empty($data->fields['valor_nuevo'])) {
			$string_humanize .= "el valor ({$data->fields['valor_antiguo']}) de {$field_humanize} a sido eliminado";
		} else if (empty($data->fields['valor_antiguo'])) {
			$string_humanize .= "{$field_humanize} ha cambiado a {$data->fields['valor_nuevo']}";
		}

		return Utiles::pascalize($string_humanize);
	}

	/**
	 * Genera texto humanizado con un mensaje literal
	 * @param 	Array $data
	 * @return 	String
	 */
	protected static function literalMessage($data) {
		$table_name = self::getTableName($data);
		$value_lower = strtolower($data->fields['valor_nuevo']);
		return "El {$table_name} ha sido {$value_lower}";;
	}

	/**
	 * Genera texto humanizado para el campo activo
	 * @param 	Array $data
	 * @return 	String
	 */
	protected static function activeInactive($data) {
		$table_name = self::getTableName($data);
		if ($data->fields['valor_nuevo']) {
			$string_humanize = "El {$table_name} ha sido activado";
		} else {
			$string_humanize = "El {$table_name} ha sido desactivado";
		}

		return $string_humanize;
	}

	/**
	 * Genera texto humanizado para el campo cobrable
	 * @param 	Array $data
	 * @return 	String
	 */
	protected static function chargeable($data) {
		$table_name = self::getTableName($data);
		if ($data->fields['valor_nuevo']) {
			$string_humanize = "El {$table_name} es cobrable";
		} else {
			$string_humanize = "El {$table_name} no es cobrable";
		}

		return $string_humanize;
	}

	/**
	 * Genera texto humanizado para el campo actividades obligatorias
	 * @param 	Array $data
	 * @return 	String
	 */
	protected static function mandatoryActivities($data) {
		$table_name = self::getTableName($data);
		if ($data->fields['valor_nuevo']) {
			$string_humanize = "El {$table_name} utiliza actividades obligatorias";
		} else {
			$string_humanize = "El {$table_name} no utiliza actividades obligatorias";
		}

		return $string_humanize;
	}

	/**
	 * Genera texto humanizado para el campo contrato independiente
	 * @param 	Array $data
	 * @return 	String
	 */
	protected static function contractToggle($data) {
		$table_name = self::getTableName($data);
		if (!empty($data->fields['valor_nuevo'])) {
			$string_humanize = "El {$table_name} ha pasado a cobrarse de forma independiente";
		} else {
			$string_humanize = "El {$table_name} ha dejado de cobrarse de forma independiente";
		}

		return $string_humanize;
	}

}
