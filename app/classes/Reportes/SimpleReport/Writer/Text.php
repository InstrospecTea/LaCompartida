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

		switch ($column->format) {
			case 'number':
				$padder = '0';
				$type = 'd';
				$precision = 0;
				break;
			case 'float':
				$padder = '0';
				$type = 'f';
				$precision = 2;
				break;
			default:
				$padder = ' ';
				$type = 's';
				$precision = $length;
				break;
		}

		$alignment = ($column->extras['align'] == 'left') ? "-" : "";

		$format = "%'{$padder}{$alignment}{$length}.{$precision}{$type}";

		$field = $column->field;

		if (!empty($column->extras['real_field'])) {
			$field = $column->extras['real_field'];
		}

		$value = $result[$field];
		
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

		$translate = $column->extras['translate'];
		if (count($translate) > 1) {
			$value = $translate[$value];
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

