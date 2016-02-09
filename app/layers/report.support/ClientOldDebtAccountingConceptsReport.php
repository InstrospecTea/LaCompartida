<?php

class ClientOldDebtAccountingConceptsReport extends AbstractReport implements IClientOldDebtAccountingConceptsReport {

	protected function agrupateData($data) {
		return $data;
	}

	protected function setUp() {
	}

	protected function present() {
		$this->setConfiguration('configuration', $this->getReportConfiguration());
		$this->setConfiguration('filename', $this->getFileName());
		$this->setConfiguration('title', $this->getTitle());
		$this->setConfiguration('writer', $this->getWriter());
	}

	private function getFileName() {
		return 'Reporte_antiguedad_deuda';
	}

	private function getTitle() {
		return 'Reporte Antigüedad Deudas Clientes';
	}

	private function getWriter() {
		return (!is_null($this->parameters['format']) ? $this->parameters['format'] : 'Html');
	}

	private function getReportConfiguration() {
		$config = array(
			array(
				'field' => 'glosa_cliente',
				'title' => __('Cliente'),
				'extras' => array(
					'attrs' => 'width="25%" style="text-align:left;"'
				)
			),
			array(
				'field' => 'facturas',
				'title' => __('Facturas'),
				'extras' => array(
					'attrs' => 'width="10%" style="text-align:left;"',
					'class' => 'identificadores'
				)
			),
			array(
				'field' => 'rango1',
				'title' => '0-30 ' . __('días'),
				'extras' => array(
					'attrs' => 'width="5%" style="text-align:left;"'
				)
			),
			array(
				'field' => 'rango2',
				'title' => '31-60 ' . __('días'),
				'extras' => array(
					'attrs' => 'width="5%" style="text-align:left;"'
				)
			),
			array(
				'field' => 'rango3',
				'title' => '61-90 ' . __('días'),
				'extras' => array(
					'attrs' => 'width="5%" style="text-align:left;"'
				)
			),
			array(
				'field' => 'rango4',
				'title' => '91+ ' . __('días'),
				'extras' => array(
					'attrs' => 'width="5%" style="text-align:left;"'
				)
			),
			array(
				'field' => 'total',
				'title' => __('Total'),
				'extras' => array(
					'attrs' => 'width="5%" style="text-align:left;font-weight:bold"'
				)
			)
		);
		return $config;
	}
}
