<?php

defined('LAYER_PATH') || define('LAYER_PATH', dirname(dirname(__FILE__)));
defined('APP_PATH') || define('APP_PATH', dirname(LAYER_PATH));
defined('ROOT_PATH') || define('ROOT_PATH', dirname(APP_PATH));

class ControllerLoader {

	/**
	 * Carga un controlador y ejecuta su dispatch
	 * @param string $controller
	 * @param string $method
	 * @param array $args
	 * @param boolean $ajax
	 * @param array $get
	 * @param array $post
	 */
	public function __construct($controller, $method, Array $args = null, $ajax = false, Array $get = null, Array $post = null) {
		$class_name = "{$controller}Controller";
		$filename = LAYER_PATH . "/controller/{$class_name}.php";
		if (!file_exists($filename) && $controller != 'ErrorPage') {
			$get = array('controller' => $controller);
			new ControllerLoader('ErrorPage', 'error_controller', array(), $ajax, $get);
		}
		require_once(LAYER_PATH . '/controller/AbstractController.php');
		require_once($filename);
		if (!empty($get)) {
			$_GET = (array) $get;
		}
		if (!empty($post)) {
			$_POST = (array) $post;
		}
		if ($ajax) {
			$_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
		}

		$instance = new $class_name;
		$instance->_dispatch($method, array_filter((array) $args));
	}

}
