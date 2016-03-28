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
		$this->Ws->setColumn(1, 1, 100);
	}

	protected function agrupateData($data) {
		return $data;
	}

	private function writeNewRow($col_from, $col_to, $text, $format) {
		$this->row++;
		$this->Ws->mergeCells($this->row, $col_from, $this->row, $col_to);
		$this->Ws->write($this->row, $col_from, $text, $format);
	}

	private function setHeader() {
		$this->writeNewRow(1, 13, __('REPORTE TOTAL MONTOS FACTURADOS MENSUALMENTE'), $this->format['header']);
		$this->row++;
		$this->writeNewRow(1, 13,  __('Generado el') . ' ' . date('d-m-Y H:i:s'), $this->format['text']);
		$this->writeNewRow(1, 13,  __('Periodo entre') . " {$this->parameters['start_date']} hasta {$this->parameters['end_date']}", $this->format['text']);
		$display_tax_text = ($this->parameters['display_tax'] == '1') ? __('Mostrar valores con impuesto') : __('Mostrar valores sin impuesto');
		$this->writeNewRow(1, 13, $display_tax_text, $this->format['text']);

		if (!empty($this->parameters['company'])) {
			$this->writeNewRow(1, 13, __('glosa_estudio') . ' ' . $this->parameters['company_name'], $this->format['text']);
		}
	}

	protected function present() {
		$this->setFormat();
		$this->setHeader();
		$separated_by_invoice = false;
		$col_periods = 2;
		$sales_client = array();

		$this->row++;

		if ($this->parameters['separated_by_invoice'] == '1') {
			$separated_by_invoice = true;
			$col_periods = 3;
			$this->Ws->write($this->row, 2, __('Factura'), $this->format['title']);
			$this->Ws->setColumn(2, 2, 16);
		}

		$currency = $this->parameters['display_currency']->fields['id_moneda'];
		$currency_symbol = $this->parameters['display_currency']->fields['simbolo'];

		if (!empty($this->data)) {
			foreach ($this->data as $sale) {
				if (!$separated_by_invoice) {
					if (empty($sales_client[$sale['client_code']]['name'])) {
						$sales_client[$sale['client_code']]['name'] = $sale['client'];
					}
					$sales_client[$sale['client_code']]['periods'][$sale['period']] += $sale['total_period'];
				} else {
					$sales_client[] = array(
						'name' => $sale['client'],
						'invoice' => $sale['invoice'],
						'periods' => array(
							$sale['period'] => $sale['total_period']
						)
					);
				}
			}

			$start_date = new DateTime($this->parameters['start_date']);
			$end_date = new DateTime($this->parameters['end_date']);
			$diff_date = $start_date->diff($end_date);
			$total_months = ($diff_date->y * 12) + $diff_date->m;
			$periods = array();
			$next_period = $start_date;

			$this->Ws->setColumn($col_periods, $total_months + $col_periods, 16);

			for ($x = 0; $x <= $total_months ; $x++) {
				$periods[date_format($next_period, 'Ym')] = date_format($next_period, 'M Y');
				$next_period->add(new DateInterval('P1M'));
			}

			$col = $col_periods;
			foreach ($periods as $period_key => $period_value) {
				$this->Ws->write($this->row, $col, $period_value, $this->format['title']);
				$col++;
			}

			$this->row++;
			$start_row = $this->row + 1;

			foreach ($sales_client as $sale_client) {
				$this->Ws->write($this->row, 1, $sale_client['name'], $this->format['title']);

				if ($this->parameters['separated_by_invoice'] == '1') {
					$this->Ws->write($this->row, 2, $sale_client['invoice'], $this->format['invoice']);
				}

				$col = $col_periods;

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

			for ($x = $col_periods; $x <= ($total_months + $col_periods); $x++) {
				$col = Utiles::NumToColumnaExcel($x);
				$this->Ws->writeFormula($this->row, $x, "=SUM({$col}{$start_row}:{$col}{$this->row})", $this->format['currency']);
			}
		} else {
			$this->row++;
			$this->writeNewRow(1, 13, __('No se encontraron resultados'), $this->format['header']);
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
		$this->format['invoice'] = $this->reportEngine->engine->addFormat(
			array(
				'Size' => 11,
				'VAlign' => 'top',
				'Align' => 'center',
				'Border' => 1,
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
