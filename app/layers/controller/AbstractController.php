<?php

/**
 * Class AbstractController
 * @TODO: Undocumented methods.
 */
abstract class AbstractController {

	protected $Session;
	protected $messageManager;
	protected $permisions;
	protected $layoutTitle;
	protected $layout = 'default';
	protected $data = array();
	protected $params;
	protected $router;
	protected $autoRender = true;
	private $vars = array();
	private $loadedClass = array();
	public $helpers = array('Form', array('\TTB\Html', 'Html'));


	public function __construct() {
		$this->Session = new \TTB\Sesion($this->permisions);
		Configure::setSession($this->Session);
		$this->verifySession();
		$this->messageManager = new MessageManager();
	}

	protected function verifySession() {
		if (!$this->Session->logged) {
			$this->Session->logout();
			$this->redirect(Configure::read('logoutRedirect'));
		}
	}

	public function _dispatch($method, $args = array()) {
		$this->loadRequestParameters($method, $args);
		if ($this->Session->has('post_data')) {
			$this->data = $this->Session->read('post_data');
			$this->Session->drop('post_data');
		}

		try {
			$reflector = new ReflectionMethod($this, $method);
		} catch (ReflectionException $re) {
			$get = array('controller' => $this->request['controller'], 'method' => $method);
			new ControllerLoader('ErrorPage', 'error_method', array(), $this->request['isAjax'], $get);
		}
		$reflector->invokeArgs($this, $args);

		if ($this->autoRender !== false) {
			$this->_render();
		}
	}

	/**
	 * Set the public vars
	 * @param mixed $var
	 * @param string $value
	 */
	protected function set($var, $value = null) {
		if (is_array($var)) {
			$this->vars = array_merge($this->vars, $var);
		} else {
			$this->vars[$var] = $value;
		}
	}

	protected function render($view, $layout = null) {
		if (!is_null($layout)) {
			$this->layout = $layout;
		}
		$this->_render($view);
		exit;
	}

	protected function renderJSON($data = null) {
		if (!is_null($data)) {
			$this->data = UtilesApp::utf8izar($data);
		}
		$this->render('/elements/json', 'ajax');
	}

	protected function renderTemplate($template, $data = null) {
		if (!is_null($data)) {
			$this->data = UtilesApp::utf8izar($data);
		}
		return $this->_render("{$template}", true);
	}

	/**
	 * Añade un mensaje de información.
	 * @param $message
	 */
	protected function info($message) {
		$this->messageManager->addMessage(new FlashMessage('I', $message));
	}

	/**
	 * Añade un mensaje de error.
	 * @param $message
	 */
	protected function error($message) {
		$this->messageManager->addMessage(new FlashMessage('E', $message));
	}

	/**
	 * Añade un mensaje de éxito.
	 * @param $message
	 */
	protected function success($message) {
		$this->messageManager->addMessage(new FlashMessage('S', $message));
	}

	protected function beforeRender() {}

	protected function afterRender() {}



	/**
	 * Carga una clase Model al vuelo
	 * @param string $classname
	 * @param string $alias
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

	/**
	 * Carga una clase utilities al vuelo.
	 * @param $classname
	 */
	protected function loadUtility($classname) {
		if (in_array($classname, $this->loadedClass)) {
			return;
		}
		$this->{$classname} = new $classname($this->Session);
		$this->loadedClass[] = $classname;
	}

	/**
	 * Carga una clase business al vuelo.
	 * @param $name
	 */
	protected function loadBusiness($name) {
		$classname = "{$name}Business";
		if (in_array($classname, $this->loadedClass)) {
			return;
		}
		$filename = LAYER_PATH . "/business.support/{$classname}.php";
		require $filename;
		$this->{$classname} = new $classname($this->Session);
		$this->loadedClass[] = $classname;
	}

	protected function redirect($url, $post_data = null) {
		if (!empty($post_data)) {
			$this->Session->write('post_data', $post_data);
		}
		header("Location: $url");
	}

	/**
	 *
	 */
	private function loadRequestParameters($method, $args) {
		$reflector = new ReflectionClass($this);
		$this->name = $reflector->name;
		$this->passed_args = $args;
		$this->params = $_GET;
		$this->data = $_POST;

		$this->request = array(
			'controller' => preg_replace('/Controller$/', '', $reflector->name),
			'action' => $method,
			'method' => strtolower($_SERVER['REQUEST_METHOD']),
			'isAjax' => !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
		);
	}

	private function _render($_element = false, $_asTemplate = false) {
		$this->beforeRender();
		$this->loadUtility('ViewRenderer');
		$this->ViewRenderer->helpers = $this->helpers;
		$this->ViewRenderer->title = $this->layoutTitle;
		$this->ViewRenderer->params = $this->params;
		$this->ViewRenderer->data = $this->data;
		$this->ViewRenderer->request = $this->request;
		$this->ViewRenderer->set($this->vars);

		if ($_asTemplate) {
			return $this->ViewRenderer->element($_element, $this->vars);
		} else {
			echo $this->ViewRenderer->render($this->layout, $_element);
			$this->afterRender();
		}
	}

	/**
	 * Carga un Reporte al vuelo
	 * @param string $name
	 * @param string $alias
	 * @return type
	 */
	protected function loadReport($name, $alias = null) {
		$classname = "{$name}Report";
		if (empty($alias)) {
			$alias = $classname;
		}
		if (in_array($alias, $this->loadedClass)) {
			return;
		}
		$this->{$alias} = new $classname($this->Session);
		$this->loadedClass[] = $alias;
	}

}
