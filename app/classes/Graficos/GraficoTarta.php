<?php namespace TTB\Graficos;

require_once dirname(__FILE__).'/../../conf.php';

class GraficoTarta {

	function __construct() {
	}

	/**
	 * Añade un GraficoData.
	 * @param GraficoData $data
	 * @return GraficoTarta
	 */
	function addData(GraficoData $data) {
		try {
			$this->data[] = $data;
			return $this;
		} catch (ErrorException $e) {
			error_log($e);
		}
	}

	/**
	 * Obtiene el JSON de GraficoTarta para ser entregado a Chart.js.
	 * @return JSON
	 */
	function getJson() {
		if ($this->data) {
			$json = $this->data;
			return json_encode($json);
		} else {
			error_log('Debe agregar al menos una Data para generar el JSON');
		}
	}
}
