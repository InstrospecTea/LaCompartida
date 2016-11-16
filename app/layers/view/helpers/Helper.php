<?php

abstract class Helper {

	public $helpers = array();
	private $filePath = '/view/helpers';
	protected $loadedClass = array();

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
			$class_name = is_array($helper) ? $helper[0] : $helper;
			$alias = is_array($helper) ? $helper[1] : null;
			$this->loadModel($class_name, $alias);
		}
	}
}
