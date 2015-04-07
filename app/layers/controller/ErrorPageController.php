<?php

class ErrorPageController extends AbstractController {

	public $layout = 'admin';
	protected $debug = false;

	public function __construct() {
		$this->debug = (error_reporting() & E_ERROR) == 1;
		parent::__construct();
	}

	public function __call($name, $arguments) {
		if (!method_exists($this, $name)) {
			return $this->trigger404();
		}
		$reflector = new ReflectionMethod($this, $method);
		$reflector->invokeArgs($this, $args);
	}

	public function error_404() {

	}

	public function error_controller() {
		if (!$this->debug) {
			return $this->trigger404();
		}
	}

	public function error_method() {
		if (!$this->debug) {
			return $this->trigger404();
		}
	}

	public function error_view() {
		if (!$this->debug) {
			return $this->trigger404();
		}
		$this->file_exists();
	}

	protected function afterRender() {
		exit;
	}

	private function trigger404() {
		new ControllerLoader('ErrorPage', 'error_404');
	}

	private function file_exists() {
		$file = LAYER_PATH . "/view/{$this->request['controller']}/{$this->request['action']}.ctp";
		if (!file_exists($file)) {
			if (!$this->debug) {
				die('Error 404: File not found!');
			}
			throw new Exception("File '$file' not found!", 404);
		}
	}
}
