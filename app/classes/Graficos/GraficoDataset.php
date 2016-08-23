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
		$this->addBackgroundColor(self::R, self::G, self::B, 0.5);
		$this->addBorderColor(self::R, self::G, self::B, 0.8);
		$this->addHoverBackgroundColor(self::R, self::G, self::B, 0.75);
		$this->addHoverBorderColor(self::R, self::G, self::B, 1);
		$this->addFill();
		$this->addBorderWidth();
	}

	/**
	 * Añade un tipo a al GraficoDataset.
	 * @param string $label
	 * @return GraficoDataset
	 */
	public function addType($type) {
		if (!empty($type)) {
			$this->type = $type;
			return $this;
		} else {
			error_log('Debe ingresar un String no vacío');
		}
	}

	/**
	 * Añade fill a al GraficoDataset.
	 * @param string $label
	 * @return GraficoDataset
	 */
	public function addFill($fill = false) {
		$this->fill = $fill;
		return $this;
	}

	/**
	 * Añade ancho borde a al GraficoDataset.
	 * @param int $borderWidth
	 * @return GraficoDataset
	 */
	public function addBorderWidth($borderWidth = 2) {
		if (is_int($borderWidth)) {
			$this->borderWidth = $borderWidth;
			return $this;
		} else {
			error_log('Debe ingresar un entero');
		}
	}

	/**
	 * Añade un tipo a al GraficoDataset.
	 * @param string $label
	 * @return GraficoDataset
	 */
	public function addYAxisID($yAxisID) {
		if (!empty($yAxisID)) {
			$this->yAxisID = $yAxisID;
			return $this;
		} else {
			error_log('Debe ingresar un String no vacío');
		}
	}

	/**
	 * Añade un label a al GraficoDataset.
	 * @param string $label
	 * @return GraficoDataset
	 */
	public function addLabel($label) {
		if (!empty($label)) {
			$this->label = mb_detect_encoding($label, 'UTF-8', true) ? $label : utf8_encode($label);
			return $this;
		} else {
			error_log('Debe ingresar un String no vacío');
		}
	}

	/**
	 * Añade un BackgroundColor al GraficoDataset.
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @param number $a
	 * @return GraficoDataset
	 */
	public function addBackgroundColor($r = self::R, $g = self::G, $b = self::B, $a = 0.5) {
		if ($this->validarRGBA($r, $g, $b, $a)) {
			$this->backgroundColor = 'rgba(' . $r . ', ' . $g . ', ' . $b . ', ' . number_format($a, 2, '.', '') . ')';
			return $this;
		} else {
			error_log('Debe ingresar un color válido');
		}
	}

	/**
	 * Añade un BorderColor al GraficoDataset.
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @param number $a
	 * @return GraficoDataset
	 */
	public function addBorderColor($r = self::R, $g = self::G, $b = self::B, $a = 0.8) {
		if ($this->validarRGBA($r, $g, $b, $a)) {
			$this->borderColor = 'rgba(' . $r . ', ' . $g . ', ' . $b . ', ' . number_format($a, 2, '.', '') . ')';
			return $this;
		} else {
			error_log('Debe ingresar un color válido');
		}
	}

	/**
	 * Añade unHoverBackgroundColor al GraficoDataset.
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @param number $a
	 * @return GraficoDataset
	 */
	public function addHoverBackgroundColor($r = self::R, $g = self::G, $b = self::B, $a = 0.75) {
		if ($this->validarRGBA($r, $g, $b, $a)) {
			$this->hoverBackgroundColor = 'rgba(' . $r . ', ' . $g . ', ' . $b . ', ' . number_format($a, 2, '.', '') . ')';
			return $this;
		} else {
			error_log('Debe ingresar un color válido');
		}
	}

	/**
	 * Añade un HoverBorderColor al GraficoDataset.
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @param number $a
	 * @return GraficoDataset
	 */
	public function addHoverBorderColor($r = self::R, $g = self::G, $b = self::B, $a = 1) {
		if ($this->validarRGBA($r, $g, $b, $a)) {
			$this->hoverBorderColor = 'rgba(' . $r . ', ' . $g . ', ' . $b . ', ' . number_format($a, 2, '.', '') . ')';
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
		if ((is_int($r) && $r <= 255) &&
				(is_int($g) && $g <= 255) &&
				(is_int($b) && $b <= 255) && is_numeric($a)) {
			return true;
		} else {
			return false;
		}
	}

}
