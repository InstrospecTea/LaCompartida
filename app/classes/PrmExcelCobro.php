<?php

class PrmExcelCobro extends Objeto {

	private $lang = 'es';
	private static $data = array();

	function __construct($sesion, $fields = '', $params = '') {
		$this->tabla = 'prm_excel_cobro';
		$this->campo_id = 'id_prm_excel_cobro';
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->guardar_fecha = false;
	}

	/**
	 * Obtiene el tamaño
	 * @param string $internal_name
	 * @param string $group
	 * @return int
	 */
	public function getTamano($internal_name, $group) {
		return (int) $this->getFieldValue($group, $internal_name, 'tamano');
	}

	/**
	 * Obtiene glosa en el Lang indicado
	 * @param string $internal_name
	 * @param string $group
	 * @param string $lang
	 * @return type
	 */
	public function getGlosa($internal_name, $group, $lang = 'es', $replace = null, $replace_with = null) {
		$text = $this->getFieldValue($group, $internal_name, "glosa_{$lang}");
		if (!is_null($replace) && !is_null($replace_with)) {
			$text = str_replace($replace, $replace_with, $text);
		}
		return $text;
	}

	private function getFieldValue($group, $internal_name, $field) {
		$data = $this->getData($group);
		return isset($data[$internal_name][$field]) ? $data[$internal_name][$field] : null;
	}

	private function getData($group) {
		if (isset(self::$data[$group])) {
			return self::$data[$group];
		}
		$Criteria = new Criteria($this->sesion);
		$list = $Criteria
			->add_select('nombre_interno')
			->add_select('glosa_es')
			->add_select('glosa_en')
			->add_select('tamano')
			->add_from($this->tabla)
			->add_restriction(CriteriaRestriction::equals('grupo', "'$group'"))
			->run();
		$data = array();
		foreach ($list as $item) {
			$key = $item['nombre_interno'];
			unset($item['nombre_interno']);
			$data[$key] = $item;
		}
		return self::$data[$group] = $data;
	}

}
