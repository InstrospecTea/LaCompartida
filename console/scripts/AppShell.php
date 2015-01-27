<?php

abstract class AppShell {

	public $debug;
	public $data;

	protected $Session;

	public function __construct() {
		$this->Session = new \TTB\Sesion();
		Configure::setSession($this->Session);
	}

	public abstract function main();

	/**
	 * Escribe texto en consola
	 * @param string $text
	 */
	public function out($text) {
		echo "{$text}\n";
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
}
