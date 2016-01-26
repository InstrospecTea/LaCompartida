<?php

class SalesAccountingConceptsReport extends AbstractReport implements ISalesAccountingConceptsReport {

	private $format = array();
	private $Ws = null;
	private $row = 0;

	protected function setUp() {
		$this->setOutputType('Spreadsheet');
		$this->reportEngine->engine->setCustomColor(35, 220, 255, 220);
		$this->reportEngine->engine->setCustomColor(36, 255, 255, 220);
		$this->setConfiguration('filename', __('reporte_ventas'));

		$this->Ws = $this->reportEngine->engine->addWorksheet(__('Reportes'));
		$this->Ws->setInputEncoding('utf-8');
		$this->Ws->fitToPages(1, 0);
		$this->Ws->setZoom(90);
		$this->Ws->setColumn(1, 1, 70.00);
	}

	protected function agrupateData($data) {
		return $data;
	}

	private function setHeader() {
		$this->row += 1;
		$this->Ws->mergeCells($this->row, 1, $this->row, 13);
		$this->Ws->write($this->row, 1, __('REPORTE TOTAL MONTOS FACTURADOS MENSUALMENTE'), $this->format['header']);

		for ($x = 2; $x < 14; $x++) {
			$this->Ws->write($this->row, $x, '', $this->format['header']);
		}

		$this->row += 2;
		$this->Ws->write($this->row, 1, __('GENERADO EL:'), $this->format['text']);
		$this->Ws->mergeCells($this->row, 2, $this->row, 13);
		$this->Ws->write($this->row, 2, date("d-m-Y H:i:s"), $this->format['text']);

		for ($x = 3; $x < 14; $x++) {
			$this->Ws->write($this->row, $x, '', $this->format['text']);
		}

		$this->row += 1;
		$this->Ws->write($this->row, 1, __('PERIODO ENTRE:'), $this->format['text']);
		$this->Ws->mergeCells($this->row, 2, $this->row, 13);
		$this->Ws->write($this->row, 2, "{$this->parameters['start_date']} HASTA {$this->parameters['end_date']}", $this->format['text']);

		for ($x = 3; $x < 14; $x++) {
			$this->Ws->write($this->row, $x, '', $this->format['text']);
		}

		$this->row++;
		$comparison_text = __('Los montos facturados se comparan con el monto THH segun') . ' ';

		if ($this->parameters['rate'] == 'monto_thh') {
			$comparison_text .= __('tarifa del cliente');
		} else {
			$comparison_text .= __('tarifa estandar');
		}

		$this->Ws->write($this->row, 1, $comparison_text, $this->format['text']);
		$this->Ws->mergeCells($this->row, 1, $this->row, 13);
	}

	protected function present() {
		$this->setFormat();
		$row = 0;

		$currency = $this->parameters['display_currency']->fields['id_moneda'];
		$currency_symbol = $this->parameters['display_currency']->fields['simbolo'];

		$this->setHeader();

		if (!empty($this->data)) {
			foreach ($this->data as $sale) {
				if (empty($sales_client[$sale['client_code']]['name'])) {
					$sales_client[$sale['client_code']]['name'] = $sale['client'];
				}
				$sales_client[$sale['client_code']]['periods'][$sale['period']] = $sale['total_period'];
			}

			$start_date = new DateTime($this->parameters['start_date']);
			$end_date = new DateTime($this->parameters['end_date']);
			$diff_date = $start_date->diff($end_date);
			$total_months = ($diff_date->y * 12) + $diff_date->m;
			$periods = array();
			$next_period = $start_date;

			$this->Ws->setColumn(2, $total_months + 2, 16);

			for ($x = 0; $x <= $total_months ; $x++) {
				$periods[date_format($next_period, 'Ym')] = date_format($next_period, 'M Y');
				$next_period->add(new DateInterval('P1M'));
			}

			$this->row += 3;

			$col = 2;
			foreach ($periods as $period_key => $period_value) {
				$this->Ws->write($this->row, $col, $period_value, $this->format['title']);
				$col++;
			}

			$this->row++;
			$start_row = $this->row + 1;

			foreach ($sales_client as $sale_client) {
				$this->Ws->write($this->row, 1, $sale_client['name'], $this->format['title']);
				$col = 2;
				foreach ($periods as $period_key => $period_value) {
					$total_period = 0;
					if (!empty($sale_client['periods'][$period_key])) {
						$total_period = $sale_client['periods'][$period_key];
					}
					$format = $total_period >= 0 ? $this->format['currency'] : $this->format['currency_red'];
					$this->Ws->write($this->row, $col, $total_period, $format);
					$col++;
				}
				$this->row++;
			}

			$this->Ws->write($this->row, 1, __('Total'), $this->format['title']);

			for ($x = 2; $x <= 2 + $total_months; $x++) {
				$col = Utiles::NumToColumnaExcel($x);
				$this->Ws->writeFormula($this->row, $x, "=SUM({$col}{$start_row}:{$col}{$this->row})", $this->format['currency']);
			}
		} else {
			$this->Ws->mergeCells($this->row, 2, $this->row, 13);
			$this->Ws->write($this->row, 2, __('No se encontraron resultados'), $this->format['text']);

			for ($x = 3; $x < 14; $x++) {
				$this->Ws->write($this->row, $x, '', $this->format['text']);
			}
		}
	}

	private function setFormat() {
		$currency_decimals = '';
		$currency_symbol = $this->parameters['display_currency']->fields['simbolo'];

		if (!empty($this->parameters['display_currency']->fields['cifras_decimales'])) {
			$currency_decimals = '.';
			for ($x = 0; $x < (int) $this->parameters['display_currency']->fields['cifras_decimales']; $x++) {
				$currency_decimals .= '0';
			}
		}

		$this->format['header'] = $this->reportEngine->engine->addFormat(
			array(
				'Size' => 12,
				'VAlign' => 'top',
				'Align' => 'left',
				'Bold' => '1',
				'underline' => 1,
				'Color' => 'black'
			)
		);
		$this->format['text'] = $this->reportEngine->engine->addFormat(
			array(
				'Size' => 11,
				'Valign' => 'top',
				'Align' => 'left',
				'Color' => 'black'
			)
		);
		$this->format['title'] = $this->reportEngine->engine->addFormat(
			array(
				'Size' => 12,
				'Align' => 'center',
				'Bold' => '1',
				'FgColor' => '35',
				'Border' => 1,
				'Locked' => 1,
				'Color' => 'black'
			)
		);
		$this->format['currency'] = $this->reportEngine->engine->addFormat(
			array(
				'Size' => 11,
				'VAlign' => 'top',
				'Align' => 'right',
				'Border' => 1,
				'Color' => 'black',
				'NumFormat' => "[\${$currency_symbol}] #,###,0{$currency_decimals}"
			)
		);
		$this->format['currency_red'] = $this->reportEngine->engine->addFormat(
			array(
				'Size' => 11,
				'VAlign' => 'top',
				'Align' => 'right',
				'Border' => 1,
				'Bold' => 1,
				'Color' => 'red',
				'NumFormat' => "[\${$currency_symbol}] #,###,0{$currency_decimals}"
			)
		);
	}

}
