<?php

interface BaseReportEngine {

	/**
	* Exporta los datos según la instancia de {@link ReporteEngine}
	* @return mixed
	*/
	function render($data);

	/**
	 * Establece una configuración para la instancia de {@link ReportEngine). Cada
	 * configuración tiene una clave única que la identifica semánticamente.
	 * @param $configurationKey Clave semántica para la configuración.
	 * @param $configuration Valor que tiene la configuración.
	 * @return mixed
	 */
	function setConfiguration($configurationKey, $configuration);
} 