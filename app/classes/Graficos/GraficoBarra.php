<?php namespace TTB\Graficos;

require_once dirname(__FILE__).'/../../conf.php';

class GraficoBarra {

	/**
	 * Constructor de la clase.
	 */
	function __construct() {
		$this->name_chart = "Grafico bonito";
	}

	/**
	 * Añade el nombre del Gráfico.
	 * @param string $name_chart
	 * @return GraficoBarra
	 */
	function addNameChart($name_chart) {
		if (!empty($name_chart)) {
			$this->name_chart = mb_detect_encoding($name_chart, 'UTF-8', true) ? $name_chart : utf8_encode($name_chart);
			return $this;
		} else {
			error_log('Debe enviar un String no vacío');
		}
	}

	/**
	 * Añade un GraficoDataset.
	 * @param GraficoDataset $datasets
	 * @return GraficoBarra
	 */
	function addDataSets(GraficoDataset $datasets) {
		try {
			$this->datasets[] = $datasets;
			return $this;
		} catch (ErrorException $e) {
			error_log($e);
		}
	}

	/**
	 * Añade los labels al GraficoBarra.
	 * @param array $labels
	 * @return GraficoBarra
	 */
	function addLabels($labels) {
		if (is_array($labels)) {
			foreach ($labels as $i => $value) {
				$labels[$i] = mb_detect_encoding($value, 'UTF-8', true) ? $value : utf8_encode($value);
			}
			$this->labels = $labels;
			return $this;
		} else {
			error_log('Debe enviar un array');
		}
	}

	/**
	 * Añade un label al GraficoBarra.
	 * @param string $label
	 * @return GraficoBarra
	 */
	function addLabel($label) {
		if (!empty($label)) {
			$this->labels[] = mb_detect_encoding($label, 'UTF-8', true) ? $label : utf8_encode($label);
			return $this;
		} else {
			error_log('Debe enviar un String no vacío');
		}
	}

	/**
	 * Obtiene el JSON de GraficoBarra para ser entregado a Chart.js.
	 * @return JSON
	 */
	function getJson() {
		if ($this->datasets) {
			if ($this->labels) {
				$json = [
					'labels' => $this->labels,
					'datasets' => $this->datasets,
					'name_chart' => $this->name_chart
				];
				return json_encode($json);
			} else {
				error_log('Debe agregar labels para generar el JSON');
			}
		} else {
			error_log('Debe agregar al menos un Dataset para el JSON');
		}
	}
}
