<?php

interface BaseReportEngine {
	/*
	* Exporta los datos.
	* @return mixed
	*/
	function render($data);

	/**
	* Establece la configuración del reporte.
	* @param $configuration
	*/
	function setConfiguration($configuration);
} 