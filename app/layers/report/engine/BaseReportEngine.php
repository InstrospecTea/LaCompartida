<?php

interface BaseReportEngine {
	/*
	* Exporta los datos.
	* @return mixed
	*/
	function render($data);

	/**
	* Establece la configuraci�n del reporte.
	* @param $configuration
	*/
	function setConfiguration($configuration);
} 