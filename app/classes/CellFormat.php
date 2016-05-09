<?php

class CellFormat {

	const NORMAL = 0;
	const PAIR = 1;
	const ODD = 2;

	private $SpreadsheetExcelWriter;
	private $formats = array();
	private $default = array();

	public function __construct(Spreadsheet_Excel_Writer &$SpreadsheetExcelWriter) {
		$this->SpreadsheetExcelWriter = $SpreadsheetExcelWriter;
	}

	public function serDefault(Array $normal, Array $pair = array(), Array $odd = array()) {
		$this->default = array(
			self::NORMAL => $normal,
			self::PAIR => $pair,
			self::ODD => $odd
		);
	}

	public function add($alias, Array $normal = array(), Array $pair = array(), Array $odd = array()) {
		if ($this->has($alias)) {
			return $this->get($alias);
		}
		$formats = array();
		$normal += $this->default[self::NORMAL];
		$formats[self::NORMAL] = & $this->SpreadsheetExcelWriter->addFormat($normal);
		if (!empty($pair) || $this->hasDefault(self::PAIR)) {
			$pair += $this->default[self::PAIR] + $normal;
			$formats[self::PAIR] = & $this->SpreadsheetExcelWriter->addFormat($pair);
		}
		if (!empty($odd) || $this->hasDefault(self::ODD)) {
			$odd += $this->default[self::ODD] + $normal;
			$formats[self::ODD] = & $this->SpreadsheetExcelWriter->addFormat($odd);
		}
		$this->formats[$alias] = $formats;
		return $this->formats[$alias][self::NORMAL];
	}

	public function get($alias, $row = null) {
		if (!$this->has($alias)) {
			throw new Exception("Alias '{$alias}' no valido!");
		}
		if (is_null($row)) {
			return $this->formats[$alias][self::NORMAL];
		}
		$index = ($row % 2 == 0) ? self::ODD : self::PAIR;
		return $this->formats[$alias][$index];
	}

	public function has($alias) {
		return isset($this->formats[$alias]);
	}

	private function hasDefault($index) {
		return !empty($this->default[$index]);
	}

}
