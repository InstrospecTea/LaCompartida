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
	 * Establece la configuración del reporte. Será utilizado, según corresponda, por el {@link ReportEngine} asigando
	 * al reporte.
	 * @param $configuration
	 * @throws ReportException
	 */
	function setConfiguration($configuration);


}