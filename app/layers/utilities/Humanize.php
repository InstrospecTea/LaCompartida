<?php

abstract class Humanize {
	/**
	 * Agrega el texto humanizado a la data
	 * @param 	string $table_title
	 * @param 	Array $data
	 * @return 	Array humanizado
	 */
	public static function convert($table_title, $data) {
		$rules = self::getRules($table_title);
		$black_list = self::getBlackList($table_title);

		if ($rules === false) {
			return $data;
		}

		foreach ($data as $key => $logs) {
			if ($black_list[$logs->get('campo_tabla')]) {
				unset($data[$key]);
				continue;
			}
			$method = $rules[$logs->get('campo_tabla')];
			$data[$key]->fields['humanized'] = self::$method($logs);
		}

		return $data;
	}

	/**
	 * Obtiene las relaciones de una tabla
	 * @param 	string $table_title
	 * @return 	Array
	 */
	public static function getRelations($tabla_title) {
		$class_name = \TTB\Utiles::pascalize("{$tabla_title}Humanize");

		if (class_exists($class_name)) {
			return $class_name::$relations;
		}
		return false;
	}

	/**
	 * Obtiene las reglas de una tabla
	 * @param 	string $table_title
	 * @return 	Array
	 */
	protected static function getRules($tabla_title) {
		$class_name = \TTB\Utiles::pascalize("{$tabla_title}Humanize");

		if (class_exists($class_name)) {
			return $class_name::$rules;
		}
		return false;
	}

	/**
	 * Obtiene la lista de campos ignorados
	 * @param 	string $table_title
	 * @return 	Array
	 */
	protected static function getBlackList($tabla_title) {
		$class_name = \TTB\Utiles::pascalize("{$tabla_title}Humanize");

		if (class_exists($class_name)) {
			return $class_name::$black_list;
		}
		return false;
	}

	/**
	 * Obtiene el diccionario de una tabla
	 * @param 	string $table_title
	 * @return 	Array
	 */
	protected static function getDictionary($tabla_title) {
		$class_name = \TTB\Utiles::pascalize("{$tabla_title}Humanize");

		if (class_exists($class_name)) {
			return $class_name::$dictionary;
		}
		return false;
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
			$string_humanize .= "{$field_humanize} a cambiado de {$data->fields['valor_antiguo']} a {$data->fields['valor_nuevo']}";
		} else if (empty($data->fields['valor_nuevo'])) {
			$string_humanize .= "el valor ({$data->fields['valor_antiguo']}) de {$field_humanize} a sido eliminado";
		} else if (empty($data->fields['valor_antiguo'])) {
			$string_humanize .= "{$field_humanize} a cambiado a {$data->fields['valor_nuevo']}";
		}

		return \TTB\Utiles::pascalize($string_humanize);
	}

	/**
	 * Genera texto humanizado con un mensaje literal
	 * @param 	Array $data
	 * @return 	String
	 */
	protected static function literalMessage($data) {
		$tabla_title = \TTB\Utiles::pascalize($data->fields['titulo_tabla']);
		$value_lower = strtolower($data->fields['valor_nuevo']);
		return "El {$tabla_title} a sido {$value_lower}";;
	}

	/**
	 * Genera texto humanizado para el campo activo
	 * @param 	Array $data
	 * @return 	String
	 */
	protected static function activeInactive($data) {
		$tabla_title = \TTB\Utiles::pascalize($data->fields['titulo_tabla']);
		if ($data->fields['valor_nuevo']) {
			$string_humanize = "El {$tabla_title} a sido activado";
		} else {
			$string_humanize = "El {$tabla_title} a sido desactivado";
		}

		return $string_humanize;
	}

	/**
	 * Genera texto humanizado para el campo cobrable
	 * @param 	Array $data
	 * @return 	String
	 */
	protected static function chargeable($data) {
		$tabla_title = \TTB\Utiles::pascalize($data->fields['titulo_tabla']);
		if ($data->fields['valor_nuevo']) {
			$string_humanize = "El {$tabla_title} es cobrable";
		} else {
			$string_humanize = "El {$tabla_title} no es cobrable";
		}

		return $string_humanize;
	}

	/**
	 * Genera texto humanizado para el campo actividades obligatorias
	 * @param 	Array $data
	 * @return 	String
	 */
	protected static function mandatoryActivities($data) {
		$tabla_title = \TTB\Utiles::pascalize($data->fields['titulo_tabla']);
		if ($data->fields['valor_nuevo']) {
			$string_humanize = "El {$tabla_title} utiliza actividades obligatorias";
		} else {
			$string_humanize = "El {$tabla_title} no utiliza actividades obligatorias";
		}

		return $string_humanize;
	}

}
