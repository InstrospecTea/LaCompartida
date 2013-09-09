<?php

require_once dirname(__FILE__) . '/../conf.php';

class FacturaProduccion {

	public static $configuracion_reporte = array (
		array (
			'field' => 'id_factura',
			'title' => 'Correlativo'
		),
		array (
			'field' => 'id_contrato',
			'title' => 'Contrato'
		),
		array (
			'field' => 'glosa_cliente',
			'title' => 'Cliente'
		),
		array (
			'field' => 'serie_documento_legal',
			'title' => 'Serie'
		),
		array (
			'field' => 'numero',
			'format' => 'number',
			'title' => 'N° Documento'
		),
		array (
			'field' => 'tipo',
			'title' => 'Tipo'
		),
		array (
			'field' => 'fecha',
			'format' => 'date',
			'title' => 'Fecha Documento'
		),
		array (
			'field' => 'total',
			'format' => 'number',
			'title' => 'Total'
		),
		array (
			'field' => 'simbolo',
			'title' => 'Símbolo Moneda'
		),
		array (
			'field' => 'numero',
			'format' => 'number',
			'title' => 'N° Documento',
			'extras' => array(
				'subtotal' => false
			)
		),
		array (
			'field' => 'username_generador',
			'title' => 'Generador'
		),

		array (
			'field' => 'nombre_generador',
			'title' => 'Nombre'
		),

		array (
			'field' => 'porcentaje_genera',
			'title' => 'Porcentaje Generador',
			'format' => 'number',
		),

		array (
			'field' => 'codigo_cliente',
			'title' => 'Codigo cliente',
		),

		array (
			'field' => 'glosas_asunto',
			'title' => 'Asuntos',
		),

	);

	function FacturaProduccion($sesion) {
		$this->sesion = $sesion;
	}

	public function QueryReporte() {
		$query = " SELECT factura.id_factura,
						contrato.id_contrato AS id_contrato,
						cliente.glosa_cliente,
						factura.serie_documento_legal,
						factura.numero,
						prm_documento_legal.codigo AS tipo,
						factura.fecha,
						factura.total*(moneda_factura.tipo_cambio)/(moneda_filtro.tipo_cambio) AS total,
						moneda_filtro.simbolo,
						usuario.id_usuario id_usuario_generador,
						usuario.username username_generador,
						CONCAT(usuario.apellido1, ' ', usuario.apellido2, ', ', usuario.nombre) AS nombre_generador,
						factura_generador.porcentaje_genera,
						factura.codigo_cliente,
						GROUP_CONCAT(asunto.glosa_asunto SEPARATOR ';') AS glosas_asunto
				   FROM factura
				   JOIN prm_documento_legal ON factura.id_documento_legal = prm_documento_legal.id_documento_legal
				   JOIN prm_moneda moneda_factura ON moneda_factura.id_moneda = factura.id_moneda
				   JOIN prm_estado_factura ON prm_estado_factura.id_estado = factura.id_estado
				   JOIN cobro ON cobro.id_cobro = factura.id_cobro
				   JOIN cliente ON cliente.codigo_cliente = cobro.codigo_cliente
				   JOIN contrato ON contrato.id_contrato = cobro.id_contrato
				   LEFT JOIN cobro_asunto ON cobro_asunto.id_cobro = factura.id_cobro
				   LEFT JOIN asunto ON asunto.codigo_asunto = cobro_asunto.codigo_asunto
				   LEFT JOIN prm_moneda moneda_filtro ON moneda_filtro.id_moneda = :currency_id
				   LEFT JOIN factura_generador ON factura_generador.id_factura = factura.id_factura
						LEFT JOIN usuario ON usuario.id_usuario = factura_generador.id_usuario
				  WHERE prm_estado_factura.id_estado != 5
				    AND factura.fecha >= :period_from AND factura.fecha <= :period_to
				  GROUP BY factura.numero";
		return $query;
	}

	public function DatosReporte($query, $params) {
		$Statement = $this->sesion->pdodbh->prepare($query);
		foreach ($params as $key => $item) {
			if (strpos($key, 'period_') !== false) {
				$Statement->bindParam($key, Utiles::fecha2sql($item));
			} else {
				$Statement->bindParam($key, $item);
			}
		}
		$Statement->execute();
		return $Statement->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Descarga el reporte excel básico según configuraciones
	 */
	public function DownloadReport($results, $tipo = 'Json') {
		$SimpleReport = new SimpleReport($this->sesion);
		$SimpleReport->SetRegionalFormat(UtilesApp::ObtenerFormatoIdioma($this->sesion));
		$SimpleReport->LoadConfiguration('FACTURA_PRODUCCION');
		$SimpleReport->LoadResults($results);
		$writer = SimpleReport_IOFactory::createWriter($SimpleReport, $tipo);
		$writer->save(__('Facturas'));
	}


}