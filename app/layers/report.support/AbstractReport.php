<?php

abstract class AbstractReport implements BaseReport {

	var $data;
	var $reportEngine;
	var $parameters;

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
		$type = strtoupper($type);
		$classname = "{$type}ReportEngine";
		try {
			$class = new ReflectionClass($classname);
			$this->reportEngine = $class->newInstance();
		} catch(ReflectionException $ex) {
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
	 * Definición del proceso de agrupación de datos definido para cada reporte.
	 * @param $data
	 * @return array
	 */
	abstract protected function agrupateData($data);

	abstract protected function present();


}