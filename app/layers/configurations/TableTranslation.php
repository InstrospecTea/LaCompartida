<?php
namespace TTB\Configurations;

abstract class TableTranslation
{

	/**
	 * @var $tables arreglo de los nombres de las tablas
	 * @example [nombre_original => english_name]
	 */
	static protected $tables = [
		'carta' => 'letter',
		'cobro_rtf' => 'rtf_charge',
		'asunto' => 'matter'
	];
	static protected $inverted = [];

	static public function original($name) {
		$tables = self::invert();
		return $tables[$name];
	}

	static public function english($name) {
		return self::$tables[$name];
	}

	static private function invert() {
		if (!empty(self::$inverted)) {
			return self::$inverted;
		}
		self::$inverted = array_flip(self::$tables);
		return self::$inverted;
	}

}
