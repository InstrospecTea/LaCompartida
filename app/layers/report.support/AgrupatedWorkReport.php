<?php


class AgrupatedWorkReport extends AbstractReport implements IAgrupatedWorkReport{

	/**
	 * Definición del proceso de agrupación de datos definido para cada reporte.
	 * @param $data
	 * @return array
	 */
	protected function agrupateData($data) {
		$grupos = array();
		$t = count($data);

		for ($x = 0; $x < $t; ++$x) {
			$fila = $data[$x];

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
					'nombre' => $fila->['client_glosa_cliente'],
					'asuntos' => array()
				);
			}

			$id_asunto = $fila->['matter_id_asunto'];
			if (empty($grupos[$id_socio]['usuarios'][$id_usuario]['clientes'][$codigo_cliente]['asuntos'][$id_asunto])) {
				$grupos[$id_socio]['usuarios'][$id_usuario]['clientes'][$codigo_cliente]['asuntos'][$id_asunto] = array(
					'codigo_cliente' => $codigo_cliente,
					'nombre' => $fila->['matter_glosa_asunto'],
					'trabajos' => array()
				);
			}

			$trabajo = array();
			$trabajo['usr_nombre'] = $lawyer_name;
			$trabajo['fecha'] = $fila->['work_fecha'];
			$trabajo['descripcion'] = $fila->['work_descripcion'];
			$trabajo['id_moneda'] = $fila->fields['work_id_moneda'];

			$duration_parts = explode(":", $trabajo['duracion_horas']);
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
		return '<h1>Hola mundo!</h1>';
	}

	private function getFileName() {
		return 'ReporteTrabajoAgrupado';
	}

	private function getTitle() {
		return 'Reporte Trabajo Agrupado';
	}

	private function getHeader() {
		return '<h1>waaaaa</h1>';
	}

	private function getFooter() {
		return '';
	}

}