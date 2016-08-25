<?php

namespace TTB\Graficos;

class DatasetLine extends Dataset {
	const R = 151;
	const G = 187;
	const B = 205;

  /**
	 * Define lineTension al DatasetLine.
	 * @param number $lineTension
	 * @return Dataset
	 */
	public function setLineTension($lineTension) {
		if (!is_numeric($lineTension)) {
			error_log('Debe ingresar un número');
		}
		$this->lineTension = number_format($lineTension, 2, '.', '');
		return $this;
	}

	/**
	 * Define border cap style al DatasetLine.
	 * https://developer.mozilla.org/en-US/docs/Web/API/CanvasRenderingContext2D/lineCap
	 * @param string $borderCapStyle (butt, round y square)
	 * @return Dataset
	 */
	public function setBorderCapStyle($borderCapStyle = 'butt') {
		$this->borderCapStyle = $borderCapStyle;
		return $this;
	}

	/**
	 * Define border dash al DatasetLine.
	 * https://developer.mozilla.org/en-US/docs/Web/API/CanvasRenderingContext2D/setLineDash
	 * @param array $borderDash
	 * @return Dataset
	 */
	public function setBorderDash(array $borderDash) {
		$this->borderDash = $borderDash;
		return $this;
	}

	/**
	 * Define border dash offset al DatasetLine.
	 * https://developer.mozilla.org/en-US/docs/Web/API/CanvasRenderingContext2D/lineDashOffset
	 * @param numeric $borderDashOffset
	 * @return Dataset
	 */
	public function setBorderDashOffset($borderDashOffset) {
		if (!is_numeric($borderDashOffset)) {
			error_log('Debe ingresar un número');
		}
		$this->borderDashOffset = number_format($borderDashOffset, 2, '.', '');
		return $this;
	}

	/**
	 * Define border join style al DatasetLine.
	 * https://developer.mozilla.org/en-US/docs/Web/API/CanvasRenderingContext2D/lineJoin
	 * @param string
	 * @return Dataset
	 */
	public function setBorderJoinStyle($borderJoinStyle = 'miter') {
		$this->borderJoinStyle = $borderJoinStyle;
		return $this;
	}

	/**
	 * Define point border color al DatasetLine.
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @param number $a
	 * @return Dataset
	 */
	public function setPointBorderColor($r = self::R, $g = self::G, $b = self::B, $a = 0.8) {
		if (!parent::validarRGBA($r, $g, $b, $a)) {
			error_log('Debe ingresar un color válido');
		}
		$a = number_format($a, 2, '.', '');
		$this->pointBorderColor = "rgba({$r}, {$g}, {$b}, {$a})";
		return $this;
	}

	/**
	 * Define point background color al DatasetLine.
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @param number $a
	 * @return Dataset
	 */
	public function setPointBackgroundColor($r = self::R, $g = self::G, $b = self::B, $a = 0.5) {
		if (!parent::validarRGBA($r, $g, $b, $a)) {
			error_log('Debe ingresar un color válido');
		}
		$a = number_format($a, 2, '.', '');
		$this->pointBackgroundColor = "rgba({$r}, {$g}, {$b}, {$a})";
		return $this;
	}

	/**
	 * Define point border width al DatasetLine.
	 * @param numeric $pointBorderWidth
	 * @return Dataset
	 */
	public function setPointBorderWidth($pointBorderWidth = 1) {
		if (!is_numeric($pointBorderWidth)) {
			error_log('Debe ingresar un número');
		}
		$this->pointBorderWidth = number_format($pointBorderWidth, 2, '.', '');
		return $this;
	}

	/**
	 * Define point hover radius al DatasetLine.
	 * @param numeric $pointHoverRadius
	 * @return Dataset
	 */
	public function setPointHoverRadius($pointHoverRadius = 5) {
		if (!is_numeric($pointHoverRadius)) {
			error_log('Debe ingresar un número');
		}
		$this->pointHoverRadius = number_format($pointHoverRadius, 2, '.', '');
		return $this;
	}

	/**
	 * Define point hover background color al DatasetLine.
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @param number $a
	 * @return Dataset
	 */
	public function setPointHoverBackgroundColor($r = self::R, $g = self::G, $b = self::B, $a = 0.5) {
		if (!parent::validarRGBA($r, $g, $b, $a)) {
			error_log('Debe ingresar un color válido');
		}
		$a = number_format($a, 2, '.', '');
		$this->pointHoverBackgroundColor = "rgba({$r}, {$g}, {$b}, {$a})";
		return $this;
	}

	/**
	 * Define point hover border color al DatasetLine.
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @param number $a
	 * @return Dataset
	 */
	public function setPointHoverBorderColor($r = self::R, $g = self::G, $b = self::B, $a = 0.8) {
		if (!parent::validarRGBA($r, $g, $b, $a)) {
			error_log('Debe ingresar un color válido');
		}
		$a = number_format($a, 2, '.', '');
		$this->pointHoverBorderColor = "rgba({$r}, {$g}, {$b}, {$a})";
		return $this;
	}

	/**
	 * Define point hover border width al DatasetLine.
	 * @param number $pointHoverBorderWidth
	 * @return Dataset
	 */
	public function setPointHoverBorderWidth($pointHoverBorderWidth = 2) {
		if (!is_numeric($pointHoverBorderWidth)) {
			error_log('Debe ingresar un número');
		}
		$this->pointHoverBorderWidth = number_format($pointHoverBorderWidth, 2, '.', '');
		return $this;
	}

	/**
	 * Define point radius al DatasetLine.
	 * @param numeric $pointRadius
	 * @return Dataset
	 */
	public function setPointRadius($pointRadius = 1) {
		if (!is_numeric($pointRadius)) {
			error_log('Debe ingresar un número');
		}
		$this->pointRadius = number_format($pointRadius, 2, '.', '');
		return $this;
	}

	/**
	 * Define point hit radius al DatasetLine.
	 * @param numeric $pointHitRadius
	 * @return Dataset
	 */
	public function setPointHitRadius($pointHitRadius = 10) {
		if (!is_numeric($pointHitRadius)) {
			error_log('Debe ingresar un número');
		}
		$this->pointHitRadius = number_format($pointHitRadius, 2, '.', '');
		return $this;
	}

	/**
	 * Define span gaps al DatasetLine.
	 * @param boolean $spanGaps
	 * @return Dataset
	 */
	public function setspanGaps($spanGaps = false) {
		$this->spanGaps = $spanGaps;
		return $this;
	}

}
