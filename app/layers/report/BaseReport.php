<?php

interface BaseReport {
	/**
	* Exporta los datos en el formato indicado como parametro.
	* @return mixed
	*/
	function render();

	/**
	* Asigna los datos a la instancia de reporte. Estos datos son los que el reporte utiliza para generar agrupaciones
	* y totalizaciones.
	* @param array $data
	* @return void
	*/
	function setData($data);

	/**
	 * Establece la configuraci�n del reporte. Ser� utilizado, seg�n corresponda, por el {@link ReportEngine} asigando
	 * al reporte.
	 * @param $configurationKey
	 * @param $configuration
	 * @throws ReportException
	 */
	function setConfiguration($configurationKey, $configuration);

	/**
	 * Asigna un parametro al reporte. Ser� utilizado para generar la estructura del reporte.
	 * @param $parameterKey
	 * @param $parameter
	 */
	function setParameter($parameterKey, $parameter);

	/**
	 * Establece los parametros del reporte. Ser� utilizado para generar la estructura del reporte.
	 * @param $parameters
	 */
	function setParameters($parameters);
}