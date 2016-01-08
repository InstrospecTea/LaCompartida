<?php

class AreaAgrupatedChargeReport extends AbstractReport implements IAreaAgrupatedChargeReport {

	protected $Html;

	/**
	 * Definición del proceso de agrupación de datos definido para cada reporte.
	 * @param $data
	 * @return array
	 */
	protected function agrupateData($data) {
		return $this->areaAgrupation($data);
	}

	protected function present() {
		$this->setConfiguration('filename', $this->getFileName());
		$this->setConfiguration('content', $this->createHtmlContent());
		$this->setConfiguration('style', $this->getStyles());
	}

	protected function setUp() {
		$this->Html = new \TTB\Html();
		$this->loadBusiness('Charging');
		$this->loadBusiness('Coining');
	}

	private function areaAgrupation($data) {
		$areas = array();
		foreach ($data as $item) {
			$area = empty($item['area_asunto']) ? 'Sin area' : $item['area_asunto'];
			if (array_key_exists($area, $areas)) {
				array_push($areas[$area], $item);
			} else {
				$areas[$area][] = $item;
			}
		}
		return $areas;
	}

	public function test() {
		return $this->data;
	}

	private function getFileName() {
		return 'reporte_cobros_por_area';
	}

	private function createHtmlContent() {
		$html = '';
		foreach ($this->data as $area => $cobros) {
			$html .= "<h3>Área {$area}</h3>";
			$html .= "<table class='table'>";
			$html .= "<tr>
<th>N° Factura</th>
<th>Fecha Creación</th>
<th>Cliente</th>
<th>Código</th>
<th>Asunto</th>
<th>Encargado comercial</th>
<th>Valor cobrado</th>
<th>Estado</th>
<th>Forma de tarificación</th>
<th>N° del cobro</th>
</tr>";
			$total_cobrado = 0;
			foreach ($cobros as $cobro) {
				$BaseCurrency = $this->CoiningBusiness->getCurrency($cobro['id_moneda_base']);
				$valor_cobrado = round($cobro['valor_cobrado'], $BaseCurrency->fields['decimales']);
				$total_cobrado += $valor_cobrado;

				$html .= "<tr>";
				$html .= "<td>{$cobro['numero']}</td>";
				$html .= "<td>{$cobro['fecha_creacion']}</td>";
				$html .= "<td>{$cobro['glosa_cliente']}</td>";
				$html .= "<td>{$cobro['codigo_asunto']}</td>";
				$html .= "<td>{$cobro['glosa_asunto']}</td>";
				$html .= "<td>{$cobro['nombre']}</td>";
				$html .= "<td>{$BaseCurrency->fields['simbolo']}{$valor_cobrado}</td>";
				$html .= "<td>{$cobro['estado']}</td>";
				$html .= "<td>{$cobro['forma_cobro']}</td>";
				$html .= "<td>{$cobro['id_cobro']}</td>";
				$html .= "</tr>";
			}
			$html .= "<tr>
<td colspan='6' class='total_label'>Totales</td>
<td>{$BaseCurrency->fields['simbolo']}{$total_cobrado}</td>
<td colspan='3'></td>
</tr>";
			$html .= "</table>";
		}
		return $html;
	}

	private function getStyles() {
		return '@page {
			margin: 1.8cm;
		}

		body {
			font-family: sans-serif;
			text-align: justify;
			font-size: 11pt;
		}
		h1,
		h2,
		h3,
		h4 {
			margin: .2em;
		}
		h1 {font-size: 1.6em;}
		h2 {font-size: 1.4em;}
		h3 {font-size: 1.3em;}
		h4 {font-size: 1.2em;}


		hr {
			page-break-after: always;
			border: 0;
		}
		#doc_header {
			text-align: center;
		}
		#doc_header:first {
			display: block;
		}
		#header,
		#footer {
			left: 0;
			right: 0;
			color: #000;
			font-size: 0.9em;
		}
		#header {
			top: 0;
		}
		#footer {
			bottom: 0;
			border-top: 1px solid #aaa;
		}
		#header > div {
			border: 0px solid #000;
			border-top-width: 2px;
			border-bottom-width: 2px;
			padding-left: 3em;
		}
		.table,
		#footer table {
			width: 100%;
			border-collapse: collapse;
			border: none;
		}

		#footer td {
			padding: 0;
			width: 50%;
		}
		#header .table th,
		#header .table td {
			font-weight: bold;
			vertical-align: middle;
		}

		.page-number {
			text-align: right;
		}
		.page-number:before {
			content: counter(page);
		}

		.usuario {
			margin-top: 1em;
			margin-right: 1em;
		}
		.margin {
			margin-left: 1em;
		}
		.table td,
		.table th {
			font-size: 10pt;
			vertical-align: top;
		}
		.table .col1 {
			width: 10em;
		}
		.table .col3 {
			width: 8em
		}
		.table .col1,
		.table .col3.title {
			text-align: center;
		}
		.table .col3,
		.table .col2 {
			text-align: right;
		}';
	}

}
