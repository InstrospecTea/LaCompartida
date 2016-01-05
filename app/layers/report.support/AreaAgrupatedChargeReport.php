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
			$area = empty($item['glosa']) ? 'Sin area' : $item['glosa'];
			if (array_key_exists($area, $areas)) {
				array_push($areas[$area],  $item);
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

<th>Encargado comercial</th>
<th>Duración trabajada</th>
<th>Duración cobrada</th>
<th>Ingreso</th>
<th>Ingreso en base</th>
<th>Gastos</th>
<th>Estado</th>
<th>Forma de tarificación</th>
<th>N° del cobro</th>
</tr>";
			$total_duracion = 0;
			$total_duracion_cobrada = 0;
			$total_base = 0;
			$total_gastos = 0;
			foreach ($cobros as $cobro) {
				$BaseCurrency = $this->CoiningBusiness->getCurrency($cobro['id_moneda_base']);
				$total_duracion += $cobro['duracion'];
				$total_duracion_cobrada += $cobro['duracion_cobrada'];
				$total_base += $cobro['total_moneda_base'];
				$total_gastos += $cobro['saldo_final_gastos'];

				$html .= "<tr>";
				$html .= "<td>{$cobro['numero']}</td>";
				$html .= "<td>{$cobro['fecha_creacion']}</td>";
				$html .= "<td>{$cobro['glosa_cliente']}</td>";

				$html .= "<td>{$cobro['nombre']}</td>";
				$duracion = Utiles::Decimal2GlosaHora($cobro['duracion'] /60);
				$html .= "<td>{$duracion}</td>";
				$duracion_cobrada = Utiles::Decimal2GlosaHora($cobro['duracion_cobrada'] /60);
				$html .= "<td>{$duracion_cobrada}</td>";
				$html .= "<td>{$cobro['simbolo']} {$cobro['monto_proporcional']}</td>";
				$html .= "<td>{$BaseCurrency->fields['simbolo']}{$cobro['total_moneda_base']}</td>";
				$html .= "<td>{$BaseCurrency->fields['simbolo']}{$cobro['saldo_final_gastos']}</td>";
				$html .= "<td>{$cobro['estado']}</td>";
				$html .= "<td>{$cobro['forma_cobro']}</td>";
				$html .= "<td>{$cobro['id_cobro']}</td>";
				$html .= "</tr>";
			}
			$formato_total_duracion = Utiles::Decimal2GlosaHora($total_duracion /60);
			$formato_total_duracion_cobrada = Utiles::Decimal2GlosaHora($total_duracion_cobrada /60);
			$html .= "<tr>
<td colspan='4'></td>
<td>{$formato_total_duracion}</td>
<td>{$formato_total_duracion_cobrada}</td>
<td></td>
<td>{$BaseCurrency->fields['simbolo']}{$total_base}</td>
<td>{$BaseCurrency->fields['simbolo']}{$total_gastos}</td>
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
