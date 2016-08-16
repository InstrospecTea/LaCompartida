<?php namespace TTB\Graficos;

require_once dirname(__FILE__).'/../../conf.php';

class GraficoDataset {
	/* TODO: Cambiar el color cuando dise�o lo defina.*/
	const R = 151;
	const G = 187;
	const B = 205;
	/**
	 * Constructor de la clase. A�ade colores por defecto.
	 */
	public function __construct() {
		$this->addFillColor(self::R, self::G, self::B, 0.5);
		$this->addStrokeColor(self::R, self::G, self::B, 0.8);
		$this->addHighlightFill(self::R, self::G, self::B, 0.75);
		$this->addHighlightStroke(self::R, self::G, self::B, 1);
	}

	/**
	 * A�ade un label a al GraficoDataset.
	 * @param string $label
	 * @return GraficoDataset
	 */
	public function addLabel($label) {
		if (!empty($label)) {
			$this->label = mb_detect_encoding($label, 'UTF-8', true) ? $label : utf8_encode($label);
			return $this;
		} else {
			error_log('Debe ingresar un String no vac�o');
		}
	}

	/**
	 * A�ade un FillColor al GraficoDataset.
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @param number $a
	 * @return GraficoDataset
	 */
	public function addFillColor($r = self::R, $g = self::G, $b = self::B, $a = 0.5) {
		if ($this->validarRGBA($r, $g, $b, $a)) {
			$this->fillColor = 'rgba(' . $r . ', ' . $g . ', ' . $b . ', ' . number_format($a, 2, '.', '') . ')';
			return $this;
		} else {
			error_log('Debe ingresar un color v�lido');
		}
	}

	/**
	 * A�ade un StrokeColor al GraficoDataset.
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @param number $a
	 * @return GraficoDataset
	 */
	public function addStrokeColor($r = self::R, $g = self::G, $b = self::B, $a = 0.8) {
		if ($this->validarRGBA($r, $g, $b, $a)) {
			$this->strokeColor = 'rgba(' . $r . ', ' . $g . ', ' . $b . ', ' . number_format($a, 2, '.', '') . ')';
			return $this;
		} else {
			error_log('Debe ingresar un color v�lido');
		}
	}

	/**
	 * A�ade un HighlightFill al GraficoDataset.
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @param number $a
	 * @return GraficoDataset
	 */
	public function addHighlightFill($r = self::R, $g = self::G, $b = self::B, $a = 0.75) {
		if ($this->validarRGBA($r, $g, $b, $a)) {
			$this->highlightFill = 'rgba(' . $r . ', ' . $g . ', ' . $b . ', ' . number_format($a, 2, '.', '') . ')';
			return $this;
		} else {
			error_log('Debe ingresar un color v�lido');
		}
	}

	/**
	 * A�ade un HighlightStroke al GraficoDataset.
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @param number $a
	 * @return GraficoDataset
	 */
	public function addHighlightStroke($r = self::R, $g = self::G, $b = self::B, $a = 1) {
		if ($this->validarRGBA($r, $g, $b, $a)) {
			$this->highlightStroke = 'rgba(' . $r . ', ' . $g . ', ' . $b . ', ' . number_format($a, 2, '.', '') . ')';
			return $this;
		} else {
			error_log('Debe ingresar un color v�lido');
		}
	}

	/**
	 * A�ade la data del GraficoDataset.
	 * @param array $data
	 * @return GraficoDataset
	 */
	public function addData($data) {
		if(is_array($data)) {
			$this->data = $data;
			return $this;
		} else {
			error_log('Debe ingresar un la data como array');
		}
	}

	/**
	 * Valida que los elementos r, g, b y a sean v�lidos.
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @param number $a
	 * @return boolean
	 */
	private function validarRGBA($r, $g, $b, $a) {
		if ((is_int($r) && $r <= 255) &&
				(is_int($g) && $g <= 255) &&
				(is_int($b) && $b <= 255) && is_numeric($a)) {
			return true;
		} else {
			return false;
		}
	}

}
