<?php

use Carbon\Carbon;

class Date extends Carbon {

	private $escapes = array();

	public function format($format) {
		$format = $this->escape($format, 'M');
		$format = $this->escape($format, 'F');
		$format = $this->escape($format, 'l');
		$format = $this->escape($format, 'D');
		$format = Utiles::interpolate($this->escapes, $format);
		$this->escapes = array();
		return parent::format($format);
	}

	private function escapeText($text) {
		return preg_replace('/(.)/', '\\\$1', __($text));
	}

	private function escape($format, $char) {
		if (strpos($format, $char) === false) {
			return $format;
		}
		$index = count($this->escapes);
		$result = $this->escapeText(parent::format($char));
		$this->escapes[$index] = $result;
		return str_replace($char, '{' . $index . '}', $format);;
	}

	/**
	 * __toString() devuelve solo la fecha 'Y-m-d'
	 */
	public function toDate() {
		self::$toStringFormat = array_shift(explode(' ', self::DEFAULT_TO_STRING_FORMAT));
		return $this;
	}

	/**
	 * __toString() devuelve solo la hora 'H:i:s'
	 */
	public function toTime() {
		self::$toStringFormat = array_pop(explode(' ', self::DEFAULT_TO_STRING_FORMAT));
		return $this;
	}

}
