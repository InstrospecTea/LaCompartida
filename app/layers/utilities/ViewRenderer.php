<?php

class ViewRenderer {
	public $data;
	public $title;
	public $request;
	public $helpers;
	protected $Session;
	private $vars = array();
	private $loadedClass = array();
	private $filePath = '/view';

	public function __construct($Session) {
		$this->Session = $Session;
	}
	public function render($_layout = false, $_element = false) {
		if ($_element == false) {
			$file = "{$this->filePath}/{$this->request['controller']}/{$this->request['action']}.ctp";
		} else {
			$absolute = preg_match('/^\//', $_element);
			$_path = $absolute ? '' : "/{$this->request['controller']}/";
			$_file = $_element;
			$file = "{$this->filePath}{$_path}{$_file}.ctp";
		}
		$vars = array_merge($this->vars, array('title_for_layout' => $this->title));
		$content_for_layout = $this->_render($file, $vars);

		if ($_layout === false) {
			return $content_for_layout;
		}

		return $this->_render("/{$this->filePath}/layouts/{$_layout}.ctp", array('title_for_layout' => $this->title, 'content_for_layout' => $content_for_layout));
	}

	public function element($element, $vars = array()) {
		$Renderer = new self($this->Session);
		$Renderer->title = $this->title;
		$Renderer->data = $this->data;
		$Renderer->request = $this->request;
		$Renderer->helpers = $this->helpers;
		$Renderer->set($vars);
		$_path = preg_match('/^\//', $element) ? '' : '/elements/';
		$file = "{$this->filePath}{$_path}{$element}.ctp";
		return $Renderer->_render($file, $vars);
	}

	public function flash() {
		return $this->element('Flash/flash_notice');
	}

	private function _render($file, $vars) {
		$this->loadHelpers();
		extract($vars);
		ob_start();
		require LAYER_PATH . $file;
		return ob_get_clean();
	}

	/**
	 * Setea las variables internas
	 * @param $var
	 */
	public function set($var) {
		$this->vars = array_merge($this->vars, $var);
	}

	/**
	 * Carga una clase de app o fw
	 * @param type $classname
	 * @param type $alias
	 */
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
			$file = LAYER_PATH . $this->filePath . "/helpers/{$helper}.php";
			if (is_readable($file)) {
				$fileHelper = LAYER_PATH . $this->filePath . "/helpers/Helper.php";
				if (!class_exists($fileHelper)) {
					require_once $fileHelper;
				}
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