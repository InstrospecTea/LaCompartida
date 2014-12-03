<?php

abstract class Helper {

	public $helpers = array();
	private $filePath = '/view/helpers';

	public function __construct() {
		$this->loadHelpers();
	}

	protected function loadModel($classname, $alias = null) {
		if (empty($alias)) {
			$alias = $classname;
		}
		if (in_array($classname, $this->loadedClass)) {
			return;
		}
		$this->{$alias} = new $classname($this->Session);
		$this->loadedClass[] = $classname;
	}

	protected function loadHelpers() {
		foreach ($this->helpers as $helper) {
			$file = LAYER_PATH . $this->filePath . "/{$helper}.php";
			if (is_readable($file)) {
				require_once $file;
			}
			if (is_array($helper)) {
				$this->loadModel($helper[0], $helper[1]);
			} else {
				$this->loadModel($helper);
			}
		}
	}
}
