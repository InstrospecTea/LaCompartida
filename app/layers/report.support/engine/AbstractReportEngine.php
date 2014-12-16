<?php

abstract class AbstractReportEngine implements BaseReportEngine {

	var $configuration = array();

	/**
	 * Exporta los datos según la instancia de {@link ReporteEngine}
	 * @param $data
	 * @return mixed
	 * @throws ReportEngineException
	 */
	function render($data) {
		if (empty($data)) {
			throw new ReportEngineException('The data for render can not be empty.');
		}
		$this->configurateReport();
		return $this->buildReport($data);
	}

	/**
	 * Establece una configuración para la instancia de {@link ReportEngine). Cada
	 * configuración tiene una clave única que la identifica semánticamente.
	 * @param $configurationKey Clave semántica para la configuración.
	 * @param $configuration Valor que tiene la configuración.
	 * @return mixed
	 */
	function setConfiguration($configurationKey, $configuration) {
		$this->configuration[$configurationKey] = $configuration;
	}

	abstract protected function buildReport($data);

	abstract protected function configurateReport();
}
