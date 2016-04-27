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
		if (!class_exists('Helper')) {
			$fileHelper = LAYER_PATH . $this->helpersPath . "/helpers/Helper.php";
			require_once $fileHelper;
		}
		foreach ($this->helpers as $helper) {
			$class_name = is_array($helper) ? $helper[0] : $helper;
			$alias = is_array($helper) ? $helper[1] : null;
			$file = LAYER_PATH . $this->helpersPath . "/helpers/{$class_name}.php";
			if (is_readable($file)) {
				require_once $file;
			}
			$this->loadModel($class_name, $alias);
		}
	}
}
