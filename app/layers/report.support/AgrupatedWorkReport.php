<?php

require_once APP_PATH.'/classes/Html.php';

class AgrupatedWorkReport extends AbstractReport implements IAgrupatedWorkReport{

	var $Html;

	protected function setUp() {
		$this->Html = new \TTB\Html;
		$this->loadBusiness('Coining');
		$this->baseCurrency = $this->CoiningBusiness->getBaseCurrency();
	}

	/**
	 * Definición del proceso de agrupación de datos definido para cada reporte.
	 * @param $data
	 * @return array
	 */
	protected function agrupateData($data) {
		if ($this->parameters['agrupationType'] == 'lawyer') {
			return $this->laywerAgrupation($data);
		}
		if ($this->parameters['agrupationType'] == 'client') {
			return $this->clientAgrupation($data);
		}
		return $this->clientAgrupation($data);
	}

	/**
	 * @param $data
	 * @return array
	 */
 	private function laywerAgrupation($data) {
		$grupos = array();
		$t = count($data);
		for ($x = 0; $x < $t; ++$x) {
			$fila = $data[$x];
			$id_usuario = $fila->fields['lawyer_id_usuario'];
			$lawyer_name = "{$fila->fields['lawyer_apellido1']}, {$fila->fields['lawyer_nombre']}";
			if (empty($grupos[$id_usuario])) {
				$grupos[$id_usuario] = array(
					'nombre' => $lawyer_name,
					'clientes' => array()
				);
			}

			$codigo_cliente = $fila->fields['client_codigo_cliente'];
			if (empty($grupos[$id_usuario]['clientes'][$codigo_cliente])) {
				$grupos[$id_usuario]['clientes'][$codigo_cliente] = array(
					'nombre' => $fila->fields['client_glosa_cliente'],
					'asuntos' => array()
				);
			}

			$id_asunto = $fila->fields['matter_id_asunto'];
			if (empty($grupos[$id_usuario]['clientes'][$codigo_cliente]['asuntos'][$id_asunto])) {
				$grupos[$id_usuario]['clientes'][$codigo_cliente]['asuntos'][$id_asunto] = array(
					'codigo_cliente' => $codigo_cliente,
					'nombre' => $fila->fields['matter_glosa_asunto'],
					'trabajos' => array()
				);
			}

			$trabajo = array();
			$trabajo['usr_nombre'] = $lawyer_name;
			$trabajo['fecha'] = $fila->fields['work_fecha'];
			$trabajo['descripcion'] = $fila->fields['work_descripcion'];
			$trabajo['id_moneda'] = $fila->fields['work_id_moneda'];
			$duration_parts = explode(":", $fila->fields['work_duracion']);
			$trabajo['duracion_minutos'] = $duration_parts[0] * 60 + $duration_parts[1];
			$trabajo['valor_facturado'] = $trabajo['duracion_minutos'] * $fila->fields['work_tarifa_hh_estandar'];
			$grupos[$id_usuario]['clientes'][$codigo_cliente]['asuntos'][$id_asunto]['trabajos'][] = $trabajo;
		}
		return $grupos;
	}

	/**
	 * @param $data
	 * @return array
	 */
	private function clientAgrupation($data) {
		$grupos = array();
		$t = count($data);
		for ($x = 0; $x < $t; ++$x) {
			$fila = $data[$x];
			$por_socio = $this->parameters['group_by_partner'];
			$id_socio = $por_socio ? $fila->fields['user_id_usuario'] : 0;

			if (empty($grupos[$id_socio])) {
				$grupos[$id_socio] = array(
					'nombre' => "{$fila->fields['user_apellido1']}, {$fila->fields['user_nombre']}",
					'usuarios' => array()
				);
			}
			$id_usuario = $fila->fields['lawyer_id_usuario'];
			$lawyer_name = "{$fila->fields['lawyer_apellido1']}, {$fila->fields['lawyer_nombre']}";

			if (empty($grupos[$id_socio]['usuarios'][$id_usuario])) {
				$grupos[$id_socio]['usuarios'][$id_usuario] = array(
					'nombre' => $lawyer_name,
					'clientes' => array()
				);
			}

			$codigo_cliente = $fila->fields['client_codigo_cliente'];
			if (empty($grupos[$id_socio]['usuarios'][$id_usuario]['clientes'][$codigo_cliente])) {
				$grupos[$id_socio]['usuarios'][$id_usuario]['clientes'][$codigo_cliente] = array(
					'nombre' => $fila->fields['client_glosa_cliente'],
					'asuntos' => array()
				);
			}

			$id_asunto = $fila->fields['matter_id_asunto'];
			if (empty($grupos[$id_socio]['usuarios'][$id_usuario]['clientes'][$codigo_cliente]['asuntos'][$id_asunto])) {
				$grupos[$id_socio]['usuarios'][$id_usuario]['clientes'][$codigo_cliente]['asuntos'][$id_asunto] = array(
					'codigo_cliente' => $codigo_cliente,
					'nombre' => $fila->fields['matter_glosa_asunto'],
					'trabajos' => array()
				);
			}

			$trabajo = array();
			$trabajo['usr_nombre'] = $lawyer_name;
			$trabajo['fecha'] = $fila->fields['work_fecha'];
			$trabajo['descripcion'] = $fila->fields['work_descripcion'];
			$trabajo['id_moneda'] = $fila->fields['work_id_moneda'];
			$duration_parts = explode(":", $fila->fields['work_duracion']);
			$trabajo['duracion_minutos'] = $duration_parts[0] * 60 + $duration_parts[1];
			$trabajo['valor_facturado'] = $trabajo['duracion_minutos'] * $fila->fields['work_tarifa_hh_estandar'];
			$grupos[$id_socio]['usuarios'][$id_usuario]['clientes'][$codigo_cliente]['asuntos'][$id_asunto]['trabajos'][] = $trabajo;
		}
		return $grupos;
	}

	protected function present() {
		$this->setConfiguration('filename', $this->getFileName());
		$this->setConfiguration('content', $this->getHTML());
		$this->setConfiguration('title', $this->getTitle());
		$this->setConfiguration('style', $this->getStyles());
		$this->setConfiguration('header', $this->getHeader());
		$this->setConfiguration('footer', $this->getFooter());
	}

	private function getStyles() {
		return '@page {
			margin: 1.8cm;
		}

		body {
			font-family: sans-serif;
			margin-top: 3cm;
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
			display: none;
		}
		#doc_header:first {
			display: block;
		}
		#header,
		#footer {
			position: fixed;
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

	private function getHTML() {
		if ($this->parameters['agrupationType'] == 'lawyer') {
			return $this->createLawyerHtmlContent();
		}
		if ($this->parameters['agrupationType'] == 'client') {
			return $this->createClientHtmlContent();
		}
		return $this->createClientHtmlContent();
	}

	private function getFileName() {
		return 'revision_horas_agrupadas';
	}

	private function getTitle() {
		if ($this->parameters['agrupationType'] == 'lawyer') {
			return __('LISTA DE COBRO POR ABOGADO');
		} else {
			$group_by_partner = $this->parameters['group_by_partner'];
			return __('LISTA DE COBRO POR CLIENTE') . ($group_by_partner ? ' ' . __('SOCIO A CARGO') : '');
		}
	}

	private function getHeader() {
		$header = $this->parameter['companyName'];
		$title = $this->getTitle();
		$col1 = __('FECHA');
		$col2 = __('ABOGADO');
		$col3 = __('TIEMPO EN MINUTOS');
		$col4 = __('VALOR FACTURADO');
	if ($this->parameters['agrupationType'] == 'lawyer') {
		return <<<HTML
			<h1 id="doc_header">
				{$header}<br />
				{$title}
			</h1>
			<div>
				<table class="table">
					<tr>
						<td class="col1">{$col1}</td>
						<td>{$col2}</td>
						<td class="col3 title">{$col3}</td>
					</tr>
				</table>
			</div>
HTML;
	} else {
		return <<<HTML
			<h1 id="doc_header">
				{$header}<br />
				{$title}
			</h1>
			<div>
				<table class="table">
					<tr>
						<td class="col1">{$col1}</td>
						<td>{$col2}</td>
						<td class="col3 title">{$col3}</td>
						<td class="col3 title">{$col4}</td>
					</tr>
				</table>
			</div>
HTML;
		}
	}

	private function getFooter() {
		$date = date('d M Y');

return <<<HTML
<table>
	<tr>
	<td>{$date}</td>
	<td>
		<div class="page-number"></div>
	</td>
	</tr>
</table>
HTML;
	}

	private function createClientHtmlContent() {
		$html = '';
		$por_socio = $this->parameters['groupByPartner'];
		foreach ($this->data as $socio) {
			$html_usuarios = '';
			foreach ($socio['usuarios'] as $usuario) {
				$nombre_usuario = $this->Html->tag('h2', $this->Html->tag('u', $usuario['nombre']));
				$html_clientes = '';
				$ths = '';
				$total_minutos_abogado = 0;
				$total_facturado_abogado = 0;
				foreach ($usuario['clientes'] as $cliente) {
					$nombre_cliente = $this->Html->tag('h3', $cliente['nombre']);
					$html_asuntos = '';
					$total_minutos_cliente = 0;
					$total_facturado_cliente = 0;
					foreach ($cliente['asuntos'] as $asunto) {
						$nombre_asunto = $this->Html->tag('h4', $asunto['nombre']);
						$trabajos = count($asunto['trabajos']) === 0 ? '' : $this->createWorkTable($asunto['trabajos'], true);
						$html_asuntos .= $this->Html->tag('div', $nombre_asunto . $trabajos['html'], array('class' => 'margin'));
						$total_minutos_cliente += $trabajos['minutos'];
						$total_facturado_cliente += $trabajos['total_facturado'];
					}
					$trs = $this->Html->tag(
						'tr',
						$this->Html->tag('th', '', array('class' => 'col1')) .
						$this->Html->tag('th', __('Total cliente'), array('class' => 'col2')) .
						$this->Html->tag('th', $total_minutos_cliente, array('class' => 'col3')) .
						$this->Html->tag('th', $this->CoiningBusiness->formatAmount($total_facturado_cliente, $this->baseCurrency, ',', '.'), array('class' => 'col3'))
					);
					$html_asuntos .= $this->Html->tag('table', $trs, array('class' => 'table'));
					$html_clientes .= $this->Html->tag('div', $nombre_cliente . $html_asuntos, array('class' => 'margin'));
					$total_minutos_abogado += $total_minutos_cliente;
					$total_facturado_abogado += $total_facturado_cliente;
				}
				$ths .= $this->Html->tag('th', '', array('class' => 'col1'));
				$ths .= $this->Html->tag('th', __('Total abogado'), array('class' => 'col2'));
				$ths .= $this->Html->tag('th', $total_minutos_abogado, array('class' => 'col3'));
				$ths .= $this->Html->tag(
					'th',
					$this->CoiningBusiness->formatAmount(
						$total_facturado_abogado,
						$this->baseCurrency,
						',',
						'.'
					),
					array('class' => 'col3')
				);
				$trs = $this->Html->tag('tr', $ths);
				$html_clientes .= $this->Html->tag('table', $trs, array('class' => 'table'));
				$html_usuarios .= $this->Html->tag('div', $nombre_usuario . $html_clientes, array('class' => 'usuario'));
			}

			$nombre_socio = $por_socio ? __('Socio a cargo') . ': ' . $this->Html->tag('u', $socio['nombre']) : '';
			$html .= $this->Html->tag('div', $this->Html->tag('h2', $nombre_socio) . $html_usuarios);
		}

		return $html;
	}

	private function createLawyerHtmlContent() {
		$html = '';
		foreach ($this->data as $usuario) {
			$nombre_usuario = $this->Html->tag('h2', $this->Html->tag('u', $usuario['nombre']));
			$html_clientes = '';
			$total_minutos_abogado = 0;
			foreach ($usuario['clientes'] as $cliente) {
				$nombre_cliente = $this->Html->tag('h3', $cliente['nombre']);
				$html_asuntos = '';
				$total_minutos_cliente = 0;
				foreach ($cliente['asuntos'] as $asunto) {
					$nombre_asunto = $this->Html->tag('h4', $asunto['nombre']);
					$trabajos = count($asunto['trabajos']) === 0 ? '' : $this->createWorkTable($asunto['trabajos'], false);
					$html_asuntos .= $this->Html->tag('div', $nombre_asunto . $trabajos['html'], array('class' => 'margin'));
					$total_minutos_cliente += $trabajos['minutos'];
				}
				$trs = $this->Html->tag('tr', $this->Html->tag('th', '', array('class' => 'col1')) .
					$this->Html->tag('th', __('Total cliente'), array('class' => 'col2')) .
					$this->Html->tag('th', $total_minutos_cliente, array('class' => 'col3'))
				);
				$html_asuntos .= $this->Html->tag('table', $trs, array('class' => 'table'));
				$html_clientes .= $this->Html->tag('div', $nombre_cliente . $html_asuntos, array('class' => 'margin'));
				$total_minutos_abogado += $total_minutos_cliente;
			}
			$trs = $this->Html->tag('tr', $this->Html->tag('th', '', array('class' => 'col1')) .
				$this->Html->tag('th', __('Total abogado'), array('class' => 'col2')) .
				$this->Html->tag('th', $total_minutos_abogado, array('class' => 'col3'))
			);
			$html_clientes .= $this->Html->tag('table', $trs, array('class' => 'table'));
			$html .= $this->Html->tag('div', $nombre_usuario . $html_clientes, array('class' => 'usuario'));
		}
		return $html;
	}

	private function createWorkTable($data, $with_invoiced) {
		$trs = '';
		$ths = '';
		$total = 0;
		$total_facturado = 0;

		foreach ($data as $fila) {
			$tds = '';
			$valor_facturado = $this->CoiningBusiness->changeCurrency(
				$fila['valor_facturado'],
				$this->CoiningBusiness->getCurrency($fila['id_moneda']),
				$this->baseCurrency
			);

			if ($with_invoiced) {
				$tds .= $this->Html->tag('td', $fila['fecha'], array('class' => 'col1'));
				$tds .= $this->Html->tag('td', "{$fila['usr_nombre']}<br/>{$fila['descripcion']}");
				$tds .= $this->Html->tag('td', $fila['duracion_minutos'], array('class' => 'col3'));
				$tds .= $this->Html->tag(
					'td',
					$this->CoiningBusiness->formatAmount(
						$valor_facturado,
						$this->baseCurrency,
						',',
						'.'
					),
					array('class' => 'col3')
				);

				$trs .= $this->Html->tag('tr', $tds);
			} else {
				$tds .= $this->Html->tag('td', $fila['fecha'], array('class' => 'col1'));
				$tds .= $this->Html->tag('td', "{$fila['usr_nombre']}<br/>{$fila['descripcion']}");
				$tds .= $this->Html->tag('td', $fila['duracion_minutos'], array('class' => 'col3'));

				$trs .= $this->Html->tag('tr', $tds);
			}

			$total += $fila['duracion_minutos'];
			$total_facturado += $valor_facturado;
		}

		if ($with_invoiced) {
			$ths .= $this->Html->tag('th', '', array('class' => 'col1'));
			$ths .= $this->Html->tag('th', __('Total asunto'), array('class' => 'col2'));
			$ths .= $this->Html->tag('th', $total, array('class' => 'col3'));
			$ths .= $this->Html->tag(
				'th',
				$this->CoiningBusiness->formatAmount(
					$total_facturado,
					$this->baseCurrency,
					',',
					'.'
				),
				array('class' => 'col3')
			);

			$trs .= $this->Html->tag('tr', $ths);
		} else {
			$ths .= $this->Html->tag('th', '', array('class' => 'col1'));
			$ths .= $this->Html->tag('th', __('Total asunto'), array('class' => 'col2'));
			$ths .= $this->Html->tag('th', $total, array('class' => 'col3'));

			$trs .= $this->Html->tag('tr', $ths);
		}

		return array(
			'html' => $this->Html->tag('table', $trs, array('class' => 'table')),
			'minutos' => $total,
			'total_facturado' => $total_facturado
		);
	}

}