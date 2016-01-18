<?php namespace TTB\Graficos;

require_once dirname(__FILE__).'/../../conf.php';

class GraficoData {

	/**
	 * Constructor de la clase. Añade colores random.
	 */
	function __construct() {
		$r = rand(0, 200);
		$g = rand(0, 200);
		$b = rand(0, 200);
		$this->addColor($r, $g, $b);
		$this->addHighlight(($r + 20), ($g + 20), ($b + 20));
	}

	/**
	 * Añade el valor del GraficoData.
	 * @param int $value
	 * @return GraficoData
	 */
	public function addValue($value) {
		if (!empty($value) && is_numeric($value)) {
			$this->value = $value;
			return $this;
		} else {
			error_log('Debe ingresar un número válido');
		}
	}

	/**
	 * Añade el color del GraficoData.
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @return GraficoData
	 */
	public function addColor($r, $g, $b) {
		if ($this->validarRGB($r, $g, $b)) {
			$this->color = 'rgb(' . $r . ', ' . $g . ', ' . $b . ')';
			return $this;
		} else {
			error_log('Debe ingresar un color válido');
		}
	}

	/**
	 * Añade el highlight del GraficoData.
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @return GraficoData
	 */
	public function addHighlight($r, $g, $b) {
		if ($this->validarRGB($r, $g, $b)) {
			$this->highlight = 'rgb(' . $r . ', ' . $g . ', ' . $b . ')';
			return $this;
		} else {
			error_log('Debe ingresar un color válido');
		}
	}

	/**
	 * Añade el label del GraficoData. Si blanco es true el valor de label puede ser vacío.
	 * @param string $label
	 * @param boolean $blanco
	 * @return GraficoData
	 */
	public function addLabel($label, $blanco) {
		if (!empty($label)) {
			$this->label = mb_detect_encoding($label, 'UTF-8', true) ? $label : utf8_encode($label);
			return $this;
		} else if($blanco) {
			$this->label = '';
			return $this;
		} else {
			error_log('Debe ingresar un label válido');
		}
	}

	/**
	 * Valida que los elementos r, g y b sean válidos.
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @return boolean
	 */
	private function validarRGB($r, $g, $b) {
		if ((is_int($r) && $r <= 255) &&
				(is_int($g) && $g <= 255) &&
				(is_int($b) && $b <= 255)) {
			return true;
		} else {
			return false;
		}
	}
}
