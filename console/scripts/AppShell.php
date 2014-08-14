<?php

abstract class AppShell {

	public $Sesion;

	public function __construct() {
		$this->Sesion = new Sesion;
	}

	public function out($v) {
		$me = get_class($this);
		printf("%s: %s\n", $me, print_r($v, true));
	}

	public abstract function main();
}
