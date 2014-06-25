<?php

require_once dirname(__FILE__) . '/Reportes/SimpleReport.php';
require_once '../classes/Html.php';

//require_once '../classes/DomPdf/dompdf_config.inc.php';
require_once '../dompdf/dompdf_config.inc.php';

class ReporteTrabajoAgrupado {

	public $Sesion;
	public $Html;

	public function __construct(Sesion $Sesion) {
		$this->Sesion = $Sesion;
		$this->DomPdf = new DOMPDF;
		$this->Html = new \TTB\Html;
	}

	function imprimir($query, $por_socio) {
		ini_set('memory_limit', '256M');
		$data = $this->query($query);
		$groupos = $this->agrupar($data, $por_socio);
		$html = $this->crear_html($groupos, $por_socio);

		$this->DomPdf->load_html(($html));
		$this->DomPdf->render();
		$this->DomPdf->stream('lista_de_cobros.pdf');
	}

	public function query($query) {
		$statement = $this->Sesion->pdodbh->prepare($query);
		$statement->execute();
		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}

	public function agrupar($data, $por_socio) {
		$grupos = array();
		$t = count($data);
		$trabajo_keys = array_combine(array('fecha', 'duracion_horas', 'usr_nombre', 'descripcion'), array(1, 1, 1, 1));

		for ($x = 0; $x < $t; ++$x) {
			$fila = $data[$x];

			$id_socio = $por_socio ? $fila['id_encargado_comercial'] : 0;
			if (empty($grupos[$id_socio])) {
				$grupos[$id_socio] = array(
					'nombre' => $fila['encargado_comercial'],
					'usuarios' => array()
				);
			}

			$id_usuario = $fila['id_usuario'];
			if (empty($grupos[$id_socio]['usuarios'][$id_usuario])) {
				$grupos[$id_socio]['usuarios'][$id_usuario] = array(
					'nombre' => $fila['usr_nombre'],
					'clientes' => array()
				);
			}

			$codigo_cliente = $fila['codigo_cliente'];
			if (empty($grupos[$id_socio]['usuarios'][$id_usuario]['clientes'][$codigo_cliente])) {
				$grupos[$id_socio]['usuarios'][$id_usuario]['clientes'][$codigo_cliente] = array(
					'nombre' => $fila['glosa_cliente'],
					'asuntos' => array()
				);
			}

			$id_asunto = $fila['id'];
			if (empty($grupos[$id_socio]['usuarios'][$id_usuario]['clientes'][$codigo_cliente]['asuntos'][$id_asunto])) {
				$grupos[$id_socio]['usuarios'][$id_usuario]['clientes'][$codigo_cliente]['asuntos'][$id_asunto] = array(
					'codigo_cliente' => $codigo_cliente,
					'nombre' => $fila['glosa_asunto'],
					'trabajos' => array()
				);
			}

			$trabajo = array_intersect_key($fila, $trabajo_keys);
			$trabajo['duracion_minutos'] = round($trabajo['duracion_horas'] * 60, 0);
			$grupos[$id_socio]['usuarios'][$id_usuario]['clientes'][$codigo_cliente]['asuntos'][$id_asunto]['trabajos'][] = $trabajo;
		}

		return $grupos;
	}

	protected function crear_html($data, $por_socio) {
		$contenido = $this->crear_contenido_html($data, $por_socio);
		$textos = array(
			'fecha' => date('d M Y'),
			'titulo' => __('LISTA DE COBRO POR ABOGADO') . ($por_socio ? ' ' . __('SOCIO A CARGO') : ''),
			'col1' => __('FECHA'),
			'col2' => __('ABOGADO'),
			'col3' => __('TIEMPO EN MINUTOS'),
			'encabezado' => Conf::GetConf($this->Sesion, 'NombreEmpresa')
		);
		return <<<HTML
<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xml:lang="en" xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<title>{$textos['titulo']}</title>
<style type="text/css">
@page {
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
}
</style>

</head>

<body>
<div id="header">
	<h1 id="doc_header">
		{$textos['encabezado']}<br />
		{$textos['titulo']}
	</h1>
	<div>
		<table class="table">
			<tr>
				<td class="col1">{$textos['col1']}</td>
				<td>{$textos['col2']}</td>
				<td class="col3 title">{$textos['col3']}</td>
			</tr>
		</table>
	</div>
</div>

<div id="footer">
	<table>
		<tr>
		<td>{$textos['fecha']}</td>
		<td>
			<div class="page-number"></div>
		</td>
		</tr>
	</table>
</div>

{$contenido}

</body></html>
HTML;
	}

	private function crear_contenido_html($data, $por_socio) {
		$html = '';
		foreach ($data as $socio) {
			$html_usuarios = '';
			foreach ($socio['usuarios'] as $usuario) {
				$nombre_usuario = $this->Html->tag('h2', $this->Html->tag('u', $usuario['nombre']));
				$html_clientes = '';
				foreach ($usuario['clientes'] as $cliente) {
					$nombre_cliente = $this->Html->tag('h3', $cliente['nombre']);
					$html_asuntos = '';
					foreach ($cliente['asuntos'] as $asunto) {
						$nombre_asunto = $this->Html->tag('h4', $asunto['nombre']);
						$trabajos = count($asunto['trabajos']) === 0 ? '' : $this->crear_tabla_trabnajos($asunto['trabajos']);
						$html_asuntos .= $this->Html->tag('div', $nombre_asunto . $trabajos, array('class' => 'margin'));
					}
					$html_clientes .= $this->Html->tag('div', $nombre_cliente . $html_asuntos, array('class' => 'margin'));
				}
				$html_usuarios .= $this->Html->tag('div', $nombre_usuario . $html_clientes, array('class' => 'usuario'));
			}
			$nombre_socio = $por_socio ? __('Socio a cargo') . ': ' . $this->Html->tag('u', $socio['nombre']) : '';
			$html .= $this->Html->tag('div', $this->Html->tag('h2', $nombre_socio) . $html_usuarios);
		}
		return $html;
	}

	private function crear_tabla_trabnajos($data) {
		$trs = '';
		$total = 0;
		foreach ($data as $fila) {
			$trs .= $this->Html->tag('tr', $this->Html->tag('td', $fila['fecha'], array('class' => 'col1')) .
					$this->Html->tag('td', "{$fila['usr_nombre']}<br/>{$fila['descripcion']}") .
					$this->Html->tag('td', $fila['duracion_minutos'], array('class' => 'col3'))
			);
			$total += $fila['duracion_minutos'];
		}

		$trs .= $this->Html->tag('tr', $this->Html->tag('th', '', array('class' => 'col1')) .
				$this->Html->tag('th', __('Total asunto'), array('class' => 'col2')) .
				$this->Html->tag('th', $total, array('class' => 'col3'))
		);
		return $this->Html->tag('table', $trs, array('class' => 'table'));
	}

}