<?php

/**
 * @author matias.orellana
 * Export to TEXT any report
 *
 */
class SimpleReport_Writer_Text implements SimpleReport_Writer_IWriter {

	/**
	 * @var SimpleReport
	 */
	var $SimpleReport;

	/**
	 * @var Filename for the report
	 */
	var $_filename = '';

	public function __construct(SimpleReport $simpleReport) {
		$this->SimpleReport = $simpleReport;
	}

	public function save($filename = null) {
		$results = $this->SimpleReport->RunReport();
		$columns = $this->SimpleReport->Config->VisibleColumns();
		$filters = $this->SimpleReport->filters;
		$this->_filename = $filename;

		$txt = "";

		foreach ($results as $r_i => $r) {
			foreach ($columns as $col_i => $c) {
				$txt .= $this->format($r, $c, $r_i + 1);
			}
			$txt .= "\n";
		}

		$this->outputTXT($txt);
	}

	public function format($result, $column, $iterator) {
		$length = $column->extras['length'];

		if ($column->format == 'number') {
			$padder = '0';
			$type = 'd';
		} else {
			$padder = ' ';
			$type = 's';
		}

		$format = "%'{$padder}{$length}.{$length}{$type}";

		$field = strpos($column->field, "fixed") === 0 ? "fixed" : $column->field;

		$value = $result[$column->field];
		if ($column->format == 'date') {
			$value = Utiles::sql2fecha($value, '%d/%m/%Y');
		}

		switch ($field) {
			case 'fixed':
				$value = $column->extras['value'];
				break;
			case 'iterator':
				$value = $iterator;
				break;

			default:
				$value = trim(str_replace(array("\n", "\r"), ' ', $value));
				break;
		}

		return sprintf($format, $value);
	}

	function outputTXT($response) {
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
		header("Cache-Control: no-cache, must-revalidate");
		header("Pragma: no-cache");
		header('Content-type: text/plain');
		header('Content-Disposition: attachment; filename="' . $this->_filename . '.txt"');
		echo $response;
		exit;
	}

}

