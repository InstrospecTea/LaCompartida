<?php

abstract class AbstractReportEngine implements BaseReportEngine {

	var $configuration = array();

	/**
	 * Exporta los datos seg�n la instancia de {@link ReporteEngine}
	 * @param $data
	 * @return mixed
	 * @throws ReportEngineException
	 */
	function render($data) {
		$this->configurateReport();
		return $this->buildReport($data);
	}

	/**
	 * Establece una configuraci�n para la instancia de {@link ReportEngine). Cada
	 * configuraci�n tiene una clave �nica que la identifica sem�nticamente.
	 * @param $configurationKey string Clave sem�ntica para la configuraci�n.
	 * @param $configuration string Valor que tiene la configuraci�n.
	 * @return mixed
	 */
	function setConfiguration($configurationKey, $configuration) {
		$this->configuration[$configurationKey] = $configuration;
	}

	abstract protected function buildReport($data);

	abstract protected function configurateReport();
}
