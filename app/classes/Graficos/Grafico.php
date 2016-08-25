<?php

namespace TTB\Graficos;

use TTB\Graficos\Dataset;

class Grafico {

	protected $type = 'bar';
	protected $labels = [];
	protected $datasets = [];
	protected $options = [];
	protected $name_chart = '';

	/**
	 * Define el tipo del Gráfico.
	 * @param string $type
	 * @return Grafico
	 */
	public function setType($type) {
		$this->type = $type;
		return $this;
	}

	/**
	 * Define el nombre del Gráfico.
	 * @param string $name_chart
	 * @return Grafico
	 */
	public function setNameChart($name_chart) {
		$this->name_chart = mb_detect_encoding($name_chart, 'UTF-8', true) ? $name_chart : utf8_encode($name_chart);
		return $this;
	}

	/**
	 * Añade un Dataset.
	 * @param Dataset $Dataset
	 * @return Grafico
	 */
	public function addDataset(Dataset $Dataset) {
		try {
			$this->datasets[] = $Dataset;
			return $this;
		} catch (ErrorException $e) {
			error_log($e);
		}
	}

	/**
	 * Añade los labels al Grafico.
	 * @param array $labels
	 * @return Grafico
	 */
	public function addLabels($labels) {
		if (!is_array($labels)) {
			error_log('Debe enviar un array');
		}
		foreach ($labels as $value) {
			$this->addLabel($value);
		}
		return $this;
	}

	/**
	 * Añade un label al Grafico.
	 * @param string $label
	 * @return Grafico
	 */
	public function addLabel($label) {
		if (empty($label)) {
			error_log('Debe enviar un String no vacío');
		}
		$this->labels[] = mb_detect_encoding($label, 'UTF-8', true) ? $label : utf8_encode($label);
		return $this;
	}

	/**
	 * Define las opciones al Grafico.
	 * @param array $options
	 * @return Grafico
	 */
	public function setOptions($options) {
		if (!is_array($options)) {
			error_log('Debe enviar un array');
		}
		$this->options = $options;
		return $this;
	}

	/**
	 * Obtiene el JSON de Grafico para ser entregado a Chart.js.
	 * @return JSON
	 */
	public function getJson() {
		if ($this->datasets) {
			if ($this->labels) {
				$json = [
					'type' => $this->type,
					'name_chart' => $this->name_chart,
					'data' => [
						'labels' => $this->labels,
						'datasets' => $this->datasets
					],
					'options' => $this->options
				];
			} else {
				$json = [
					'error' => [
						'code' => 1,
						'message' => 'Debe agregar labels para generar el JSON'
					]
				];
			}
		} else {
			$json = [
				'error' => [
					'code' => 2,
					'message' => 'Debe agregar al menos un Dataset para generar el JSON'
				]
			];
		}
		return json_encode($json);
	}
}
