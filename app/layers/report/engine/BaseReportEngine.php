<?php

interface BaseReportEngine {

	/**
	* Exporta los datos seg�n la instancia de {@link ReporteEngine}
	* @return mixed
	*/
	function render($data);

	/**
	 * Establece una configuraci�n para la instancia de {@link ReportEngine). Cada
	 * configuraci�n tiene una clave �nica que la identifica sem�nticamente.
	 * @param $configurationKey Clave sem�ntica para la configuraci�n.
	 * @param $configuration Valor que tiene la configuraci�n.
	 * @return mixed
	 */
	function setConfiguration($configurationKey, $configuration);
} 