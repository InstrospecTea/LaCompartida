<?php

namespace TTB\Graficos;

class Dataset {
	/* TODO: Cambiar el color cuando diseño lo defina.*/
	const R = 151;
	const G = 187;
	const B = 205;
	/**
	 * Constructor de la clase. Añade colores por defecto.
	 */
	public function __construct() {
		$this->setBackgroundColor(self::R, self::G, self::B, 0.5);
		$this->setBorderColor(self::R, self::G, self::B, 0.8);
		$this->setHoverBackgroundColor(self::R, self::G, self::B, 0.75);
		$this->setHoverBorderColor(self::R, self::G, self::B, 1);
		$this->setFill();
		$this->setBorderWidth(2);
	}

	/**
	 * Define un tipo a al Dataset.
	 * @param string $label
	 * @return Dataset
	 */
	public function setType($type) {
		if (empty($type)) {
			error_log('Debe ingresar un String no vacío');
		}
		$this->type = $type;
		return $this;
	}

	/**
	 * Define fill a al Dataset.
	 * @param string $label
	 * @return Dataset
	 */
	public function setFill($fill = false) {
		$this->fill = $fill;
		return $this;
	}

	/**
	 * Define ancho borde a al Dataset.
	 * @param int $borderWidth
	 * @return Dataset
	 */
	public function setBorderWidth($borderWidth) {
		if (!is_int($borderWidth)) {
			error_log('Debe ingresar un entero');
		}
		$this->borderWidth = $borderWidth;
		return $this;
	}

	/**
	 * Define un tipo a al Dataset.
	 * @param string $label
	 * @return Dataset
	 */
	public function setYAxisID($yAxisID) {
		if (empty($yAxisID)) {
			error_log('Debe ingresar un String no vacío');
		}
		$this->yAxisID = $yAxisID;
		return $this;
	}

	/**
	 * Define un label a al Dataset.
	 * @param string $label
	 * @return Dataset
	 */
	public function setLabel($label) {
		if (empty($label)) {
			error_log('Debe ingresar un String no vacío');
		}
		$this->label = mb_detect_encoding($label, 'UTF-8', true) ? $label : utf8_encode($label);
		return $this;
	}

	/**
	 * Define un BackgroundColor al Dataset.
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @param number $a
	 * @return Dataset
	 */
	public function setBackgroundColor($r = self::R, $g = self::G, $b = self::B, $a = 0.5) {
		if (!$this->validarRGBA($r, $g, $b, $a)) {
			error_log('Debe ingresar un color válido');
		}
		$a = number_format($a, 2, '.', '');
		$this->backgroundColor = "rgba({$r}, {$g}, {$b}, {$a})";
		return $this;
	}

	/**
	 * Define un BorderColor al Dataset.
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @param number $a
	 * @return Dataset
	 */
	public function setBorderColor($r = self::R, $g = self::G, $b = self::B, $a = 0.8) {
		if (!$this->validarRGBA($r, $g, $b, $a)) {
			error_log('Debe ingresar un color válido');
		}
		$a = number_format($a, 2, '.', '');
		$this->borderColor = "rgba({$r}, {$g}, {$b}, {$a})";
		return $this;
	}

	/**
	 * Define unHoverBackgroundColor al Dataset.
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @param number $a
	 * @return Dataset
	 */
	public function setHoverBackgroundColor($r = self::R, $g = self::G, $b = self::B, $a = 0.75) {
		if (!$this->validarRGBA($r, $g, $b, $a)) {
			error_log('Debe ingresar un color válido');
		}
		$a = number_format($a, 2, '.', '');
		$this->hoverBackgroundColor = "rgba({$r}, {$g}, {$b}, {$a})";
		return $this;
	}

	/**
	 * Define un HoverBorderColor al Dataset.
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @param number $a
	 * @return Dataset
	 */
	public function setHoverBorderColor($r = self::R, $g = self::G, $b = self::B, $a = 1) {
		if (!$this->validarRGBA($r, $g, $b, $a)) {
			error_log('Debe ingresar un color válido');
		}
		$a = number_format($a, 2, '.', '');
		$this->hoverBorderColor = "rgba({$r}, {$g}, {$b}, {$a})";
		return $this;
	}

	/**
	 * Define la data del Dataset.
	 * @param array $data
	 * @return Dataset
	 */
	public function setData(array $data) {
		$this->data = $data;
		return $this;
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

	protected function getRandomColor($a = 1) {
		$r = rand(100, 200);
		$g = rand(100, 200);
		$b = rand(100, 200);
		return "rgba({$r}, {$g}, {$b}, {$a})";
	}

}
