<?php

use TTB\Debug;
abstract class AppShell {

	public $debug;
	public $data;
	private $loadedClass = array();
	private $time_start;

	protected $Session;

	public function __construct() {
		$this->Session = new \TTB\Sesion();
		Configure::setSession($this->Session);

		// start log time lapse
		$this->time_script_start = microtime(true);
	}

	public abstract function main();

	/**
	 * Escribe texto en consola
	 * @param string $text
	 */
	public function out($text) {
		Debug::pr($text);
	}

	/**
	 * Carga una clase Model al vuelo
	 * @param string $classname
	 * @param string $alias
	 * @param array $return_instance Indica si requiere retornar la instancia de clase
	 * @return type
	 */
	protected function loadModel($classname, $alias = null, $return_instance = false) {
		if ($return_instance) {
			return new $classname($this->Session);
		}
		if (empty($alias)) {
			$alias = $classname;
		}
		if (in_array($classname, $this->loadedClass)) {
			return;
		}
		$this->{$alias} = new $classname($this->Session);
		$this->loadedClass[] = $classname;
	}

	/**
	 * Escribe texto en consola si esta ejecutando en modo @debug
	 * @param type $text
	 */
	protected function debug($text) {
		if ($this->debug) {
			$bt = debug_backtrace();
			$caller = array_shift($bt);
			$this->out("L[{$caller['line']}] " . print_r($text, true));
		}
	}

	/**
	 * Setea el tiempo de inicio del script
	 * @param microtime $time_start
	 */
	public function setTimeScriptStart($time_start = null) {
		if (!is_null($time_start)) {
			$this->time_script_start = $time_start;
		} else {
			$this->time_script_start = microtime(true);
		}
	}

	/**
	 * Obtiene el tiempo transcurrido de ejecución del script
	 * @param string $format
	 */
	public function getTimeLapse($format = 'H:i:s') {
		$time_end = microtime(true);
		$time = $time_end - $this->time_script_start;
		return gmdate($format, $time);
	}
}
