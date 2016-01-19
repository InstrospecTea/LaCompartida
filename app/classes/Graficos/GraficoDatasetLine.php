<?php namespace TTB\Graficos;

require_once dirname(__FILE__).'/../../conf.php';

class GraficoDatasetLine extends GraficoDataset {
	/* TODO: Cambiar el color cuando diseño lo defina.*/
	const R = 220;
	const G = 220;
	const B = 220;

	/**
	 * Constructor de la clase. Añade colores por defecto.
	 */
	public function __construct() {
		$this->addFillColor(self::R, self::G, self::B, 0.2);
		$this->addStrokeColor(self::R, self::G, self::B, 1);
		$this->addPointColor(self::R, self::G, self::B, 1);
		$this->addPointStrokeColor(255, 255, 255, 1);
		$this->addPointHighlightFill(255, 255, 255, 1);
		$this->addPointHighlightStroke(self::R, self::G, self::B, 1);
	}

	/**
	 * Añade un PointColor al GraficoDatasetLine.
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @param number $a
	 * @return GraficoDatasetLine
	 */
	public function addPointColor($r = self::R, $g = self::G, $b = self::B, $a = 0.5) {
		if ($this->validarRGBA($r, $g, $b, $a)) {
			$this->pointColor = 'rgba(' . $r . ', ' . $g . ', ' . $b . ', ' . $a . ')';
			return $this;
		} else {
			error_log('Debe ingresar un color válido');
		}
	}

	/**
	 * Añade un PointStrokeColor al GraficoDatasetLine.
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @param number $a
	 * @return GraficoDatasetLine
	 */
	public function addPointStrokeColor($r = self::R, $g = self::G, $b = self::B, $a = 1) {
		if ($this->validarRGBA($r, $g, $b, $a)) {
			$this->pointStrokeColor = 'rgba(' . $r . ', ' . $g . ', ' . $b . ', ' . $a . ')';
			return $this;
		} else {
			error_log('Debe ingresar un color válido');
		}
	}

	/**
	 * Añade un PointHighlightFill al GraficoDatasetLine.
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @param number $a
	 * @return GraficoDatasetLine
	 */
	public function addPointHighlightFill($r = self::R, $g = self::G, $b = self::B, $a = 1) {
		if ($this->validarRGBA($r, $g, $b, $a)) {
			$this->pointHighlightFill = 'rgba(' . $r . ', ' . $g . ', ' . $b . ', ' . $a . ')';
			return $this;
		} else {
			error_log('Debe ingresar un color válido');
		}
	}

	/**
	 * Añade un PointHighlightStroke al GraficoDatasetLine.
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @param number $a
	 * @return GraficoDatasetLine
	 */
	public function addPointHighlightStroke($r = self::R, $g = self::G, $b = self::B, $a = 1) {
		if ($this->validarRGBA($r, $g, $b, $a)) {
			$this->pointHighlightStroke = 'rgba(' . $r . ', ' . $g . ', ' . $b . ', ' . $a . ')';
			return $this;
		} else {
			error_log('Debe ingresar un color válido');
		}
	}

	/**
	 * Valida que los elementos r, g, b y a sean válidos.
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
