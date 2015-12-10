<?php namespace TTB\Graficos;

require_once dirname(__FILE__).'/../../conf.php';

class GraficoDataset {
	/* TODO: Cambiar el color cuando diseño lo defina.*/
	const R = 151;
	const G = 187;
	const B = 205;
	/**
	 * Constructor de la clase. Añade colores por defecto.
	 */
	public function __construct() {
		$this->addFillColor(self::R, self::G, self::B, 0.5);
		$this->addStrokeColor(self::R, self::G, self::B, 0.8);
		$this->addHighlightFill(self::R, self::G, self::B, 0.75);
		$this->addHighlightStroke(self::R, self::G, self::B, 1);
	}

	/**
	 * Añade un label a al GraficoDataset.
	 * @param string $label
	 * @return GraficoDataset
	 */
	public function addLabel($label) {
		if (!empty($label)) {
			$this->label = $label;
			return $this;
		} else {
			error_log('Debe ingresar un String no vacío');
		}
	}

	/**
	 * Añade un FilColor al GraficoDataset.
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @param number $a
	 * @return GraficoDataset
	 */
	public function addFillColor($r = self::R, $g = self::G, $b = self::B, $a = 0.5) {
		if ($this->validarRGBA($r, $g, $b, $a)) {
			$this->fillColor = 'rgba(' . $r . ', ' . $g . ', ' . $b . ', ' . $a . ')';
			return $this;
		} else {
			error_log('Debe ingresar un color válido');
		}
	}

	/**
	 * Añade un StrokeColor al GraficoDataset.
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @param number $a
	 * @return GraficoDataset
	 */
	public function addStrokeColor($r = self::R, $g = self::G, $b = self::B, $a = 0.8) {
		if ($this->validarRGBA($r, $g, $b, $a)) {
			$this->strokeColor = 'rgba(' . $r . ', ' . $g . ', ' . $b . ', ' . $a . ')';
			return $this;
		} else {
			error_log('Debe ingresar un color válido');
		}
	}

	/**
	 * Añade un HighlightFill al GraficoDataset.
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @param number $a
	 * @return GraficoDataset
	 */
	public function addHighlightFill($r = self::R, $g = self::G, $b = self::B, $a = 0.75) {
		if ($this->validarRGBA($r, $g, $b, $a)) {
			$this->highlightFill = 'rgba(' . $r . ', ' . $g . ', ' . $b . ', ' . $a . ')';
			return $this;
		} else {
			error_log('Debe ingresar un color válido');
		}
	}

	/**
	 * Añade un HighlightStroke al GraficoDataset.
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @param number $a
	 * @return GraficoDataset
	 */
	public function addHighlightStroke($r = self::R, $g = self::G, $b = self::B, $a = 1) {
		if ($this->validarRGBA($r, $g, $b, $a)) {
			$this->highlightStroke = 'rgba(' . $r . ', ' . $g . ', ' . $b . ', ' . $a . ')';
			return $this;
		} else {
			error_log('Debe ingresar un color válido');
		}
	}

	/**
	 * Añade la data del GraficoDataset.
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
	 * Valida que los elementos r, g, b y a sean válidos.
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @param number $a
	 * @return boolean
	 */
	private function validarRGBA($r, $g, $b, $a) {
		if ((is_int($r) AND $r <= 255) AND
				(is_int($g) AND $g <= 255) AND
				(is_int($b) AND $b <= 255) AND is_numeric($a)) {
			return true;
		} else {
			return false;
		}
	}

}
