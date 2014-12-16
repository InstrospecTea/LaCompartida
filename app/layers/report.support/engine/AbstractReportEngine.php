<?php

abstract class AbstractReportEngine implements BaseReportEngine {

	/*
	* Exporta los datos según la instancia de {@link ReporteEngine}
	* @return mixed
	*/
	abstract function render($data);

	/**
	 * Establece la configuración del reporte.
	 * @param $configuration
	 */
	abstract function setConfiguration($configuration);
}
