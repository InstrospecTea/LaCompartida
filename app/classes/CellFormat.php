<?php

class CellFormat {

	const NORMAL = 0;
	const EVEN = 1;
	const ODD = 2;

	private $SpreadsheetExcelWriter;
	private $formats = array();
	private $default = array();

	public function __construct(WorkbookMiddleware &$SpreadsheetExcelWriter) {
		$this->SpreadsheetExcelWriter = $SpreadsheetExcelWriter;
	}

	public function serDefault(array $normal, array $even = array(), array $odd = array()) {
		$this->default = array(
			self::NORMAL => $normal,
			self::EVEN => $even,
			self::ODD => $odd
		);
	}

	public function add($alias, array $normal = array(), array $even = array(), array $odd = array()) {
		if ($this->has($alias)) {
			return $this->get($alias);
		}
		$formats = array();
		$normal += $this->default[self::NORMAL];
		$formats[self::NORMAL] = & $this->SpreadsheetExcelWriter->addFormat($normal);
		if (!empty($even) || $this->hasDefault(self::EVEN)) {
			$even += $this->default[self::EVEN] + $normal;
			$formats[self::EVEN] = & $this->SpreadsheetExcelWriter->addFormat($even);
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
		$index = ($row % 2 == 0) ? self::ODD : self::EVEN;
		return $this->formats[$alias][$index];
	}

	public function has($alias) {
		return isset($this->formats[$alias]);
	}

	private function hasDefault($index) {
		return !empty($this->default[$index]);
	}

}
