<?php

interface BaseReport {
	/*
	* Exporta los datos en el formato indicado como parametro.
	* @param $type
	* @return mixed
	*/
	function render();

	/*
	* Asigna los datos a la instancia de reporte. Estos datos son los que el reporte utiliza para generar agrupaciones
	* y totalizaciones.
	* @param array $data
	* @return void
	*/
	function setData($data);

	/**
	 * Establece la configuraci�n del reporte. Ser� utilizado, seg�n corresponda, por el {@link ReportEngine} asigando
	 * al reporte.
	 * @param $configuration
	 * @throws ReportException
	 */
	function setConfiguration($configuration);


}