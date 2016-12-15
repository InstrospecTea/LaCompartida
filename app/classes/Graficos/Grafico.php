<?php

namespace TTB\Graficos;

use TTB\Graficos\Dataset;

class Grafico {
	use GraficoTrait;

	protected $type = 'bar';
	protected $labels = [];
	protected $datasets = [];
	protected $options = [];
	protected $name_chart = '';

	/**
	 * Define el tipo del Gr�fico.
	 * @param string $type
	 * @return Grafico
	 */
	public function setType($type) {
		$this->type = $type;
		return $this;
	}

	/**
	 * Define el nombre del Gr�fico.
	 * @param string $name_chart
	 * @return Grafico
	 */
	public function setNameChart($name_chart) {
		$this->name_chart = $this->isUTF8($name_chart) ? $name_chart : utf8_encode($name_chart);
		return $this;
	}

	/**
	 * A�ade un Dataset.
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
	 * A�ade los labels al Grafico.
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
	 * A�ade un label al Grafico.
	 * @param string $label
	 * @return Grafico
	 */
	public function addLabel($label) {
		if (empty($label)) {
			error_log('Debe enviar un String no vac�o');
		}
		$this->labels[] = $this->isUTF8($label) ? $label : utf8_encode($label);
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
				error_log('Debe agregar labels para generar el JSON del gr�fico');
				return $this->getJsonError(__('No existen datos para generar el gr�fico'));
			}
		} else {
			error_log('Debe agregar al menos un Dataset para generar el JSON del gr�fico');
			return $this->getJsonError(__('No existen datos para generar el gr�fico'));
		}
		return json_encode($json);
	}

	/**
	 * Obtiene JSON de error.
	 * @return JSON
	 */
	public function getJsonError($message) {
		$json = [
			'error' => [
				'message' => $this->isUTF8($message) ? $message : utf8_encode($message)
			]
		];

		return json_encode($json);
	}
}