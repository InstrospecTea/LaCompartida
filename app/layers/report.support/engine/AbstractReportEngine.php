<?php

abstract class AbstractReportEngine implements BaseReportEngine {

	/*
	* Exporta los datos seg�n la instancia de {@link ReporteEngine}
	* @return mixed
	*/
	abstract function render($data);

	/**
	 * Establece la configuraci�n del reporte.
	 * @param $configuration
	 */
	abstract function setConfiguration($configuration);
}
