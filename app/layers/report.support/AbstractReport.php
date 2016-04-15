<?php

abstract class AbstractReport implements BaseReport {

	public $data;
	public $reportEngine;
	public $parameters;
	public $Session;
	protected $helpers = array();
	protected $helpersPath = '/view';
	private $loadedClass = array();

	public function __construct(Sesion $Session) {
		$this->Session = $Session;
		$this->loadHelpers();
		$this->setUp();
	}

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

	protected function loadHelpers() {
		if (!class_exists('Helper')) {
			$fileHelper = LAYER_PATH . $this->helpersPath . "/helpers/Helper.php";
			require_once $fileHelper;
		}
		foreach ($this->helpers as $helper) {
			$file = LAYER_PATH . $this->helpersPath . "/helpers/{$helper}.php";
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

	/**
	 * Exporta los datos según el tipo de {@link ReportEngine} configurado.
	 * @return mixed
	 * @throws ReportException
	 */
	function render() {
		if (!empty($this->reportEngine)) {
			$this->present();
			return $this->reportEngine->render($this->data);
		} else {
			throw new ReportException('This report instance does not have an instance of ReportEngine.');
		}
	}

	/**
	 * Asigna el formato de salida que utiliza el reporte. Debe ser una instancia soportada por la jerarquía de
	 * {@link ReportEngine}.
	 * @param $type
	 * @return void
	 * @throws ReportException
	 */
	function setOutputType($type) {
		$classname = "{$type}ReportEngine";
		try {
			$class = new ReflectionClass($classname);
			$this->reportEngine = $class->newInstance();
		} catch (ReflectionException $ex) {
			throw new ReportException("There is not a ReportEngine defined for the $type output.");
		}
	}

	/**
	 * Asigna los datos a la instancia de reporte. Estos datos son los que el reporte utiliza para generar agrupaciones
	 * y totalizaciones.
	 * @param array $data
	 * @return void
	 */
	function setData($data) {
		$this->data = $data;
		$this->doAgrupations();
	}

	/**
	 * Establece la configuración del reporte. Será utilizado, según corresponda, por el {@link ReportEngine} asigando
	 * al reporte.
	 * @param $configurationKey
	 * @param $configuration
	 * @throws ReportException
	 */
	function setConfiguration($configurationKey, $configuration) {
		if (!empty($this->reportEngine)) {
			$this->reportEngine->setConfiguration($configurationKey, $configuration);
		} else {
			throw new ReportException('This report instance does not have an instance of ReportEngine.');
		}
	}

	/**
	 * Establece los parametros del reporte. Será utilizado para generar la estructura del reporte.
	 * @param $parameterKey
	 * @param $parameter
	 */
	function setParameter($parameterKey, $parameter) {
		$this->parameters[$parameterKey] = $parameter;
	}

	/**
	 * Establece los parametros del reporte. Será utilizado para generar la estructura del reporte.
	 * @param $parameters
	 */
	function setParameters($parameters) {
		$this->parameters = $parameters;
	}

	/**
	 * Retorna una instancia que pertenece a la jerarquía de {@link ReportEngine} según el tipo indicado como parametro.
	 * @return mixed
	 * @throws ReportException
	 */
	protected function getReportEngine() {
		if (!empty($this->reportEngine)) {
			return $this->reportEngine;
		} else {
			throw new ReportException('This report instance does not have an instance of ReportEngine.');
		}
	}

	/**
	 * Realiza agrupaciones con los datos establecidos en el reporte.
	 * @return mixed
	 */
	protected function doAgrupations() {
		$this->data = $this->agrupateData($this->data);
	}

	/**
	 * Carga un Negocio al vuelo
	 * @param string $name
	 * @param string $alias
	 * @return type
	 */
	protected function loadBusiness($name, $alias = null) {
		$classname = "{$name}Business";
		if (empty($alias)) {
			$alias = $classname;
		}
		if (in_array($alias, $this->loadedClass)) {
			return;
		}
		$this->{$alias} = new $classname($this->Session);
		$this->loadedClass[] = $alias;
	}

	/**
	 * Definición del proceso de agrupación de datos definido para cada reporte.
	 * @param $data
	 * @return array
	 */
	abstract protected function agrupateData($data);

	abstract protected function present();

	abstract protected function setUp();
}
