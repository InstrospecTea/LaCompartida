<?php

class Descriptor {

	private static $_instances = array();
	private static $_tables = array();
	private static $Session;

	private $tableDef = array();
	private $totalFields = 0;

	/**
	 * Obtiene o crea una instancia de la clase.
	 * @param type $table
	 * @return type
	 */
	private static function getInstance($table) {
		if (empty(self::$_instances[$table])) {
			self::$_instances[$table] = new self(self::describe($table));
		}
		return self::$_instances[$table];
	}

	/**
	 * Asigna una clase de Sesion
	 * @param Sesion $Session
	 */
	private static function setSession(Sesion $Session) {
		if (is_null(self::$Session)) {
			self::$Session = $Session;
		}
	}

	/**
	 * Devuelve la descripción de la tabla indicada.
	 * @param type $table
	 * @return type
	 */
	private static function describe($table) {
		if (!isset(self::$_tables[$table])) {
			$query = "DESCRIBE {$table}";
			self::$_tables[$table] = self::$Session->pdodbh->query($query)->fetchAll(PDO::FETCH_ASSOC);
		}
		return self::$_tables[$table];
	}

	/**
	 * Retorna una instancia del descriptor para la tabla indicada.
	 * @param Sesion $Session
	 * @param string $table
	 * @return type
	 */

	public static function table(Sesion $Session, $table) {
		self::setSession($Session);
		return self::getInstance($table);
	}

	public function __construct($tableDef) {
		$this->tableDef = $tableDef;
		$this->totalFields = count($tableDef);
	}

	/**
	 * Obtiene los datos por defecto de los campos indicados.
	 * @param type $fields
	 */
	public function getDefaults($fields) {
		$defaults = array();
		for ($i = 0; $i < $this->totalFields; ++$i) {
			$field = $this->tableDef[$i];
			if (in_array($field['Field'], $fields)) {
				$defaults[$field['Field']] = $field['Default'];
			}
		}
		return $defaults;
	}
}