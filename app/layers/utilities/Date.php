<?php
include dirname(dirname(__DIR__)) . '/conf.php';

use Carbon\Carbon;

class Date extends Carbon {

	private $escapes = array();

	public function format($format) {
		$this->escape($format, 'M');
		$this->escape($format, 'F');
		$this->escape($format, 'l');
		$this->escape($format, 'D');
		$format = str_replace(array_keys($this->escapes), $this->escapes, $format);
		return parent::format($format);
	}

	private function escapeText($text) {
		return preg_replace('/(.)/', '\\\$1', __($text));
	}

	private function escape($format, $char) {
		if (strpos($format, $char) === false) {
			return $format;
		}
		$result = $this->escapeText(parent::format($char));
		$this->escapes[$char] = $result;
	}

}

$Sesion = new Sesion();

for ($x = 1; $x <= 12; ++$x) {
	$dt = Date::parse("2016-$x-1");
	pr($dt->toCookieString());
	pr($dt->toRfc822String());
	pr($dt->format('l d \d\e F \d\e Y'));
	pr($dt->format('F, l d \d\e Y'));
}
