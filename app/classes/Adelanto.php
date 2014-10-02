<?php

require_once dirname(__FILE__) . '/../conf.php';

/**
 * Clase para manejar las solicitudes de adelanto
 */
class Adelanto extends Documento {

	public static $configuracion_reporte = array(
		array('field' => 'id_documento', 'title' => 'Nº Adelanto'),
		array('field' => 'fecha', 'title' => 'Fecha'),
		array('field' => 'glosa_cliente', 'title' => 'Cliente'),
		array('field' => 'asuntos', 'title' => 'Asunto'),
		array('field' => 'tipo_moneda', 'title' => 'Moneda'),
		array('field' => 'monto', 'title' => 'Monto'),
		array('field' => 'saldo_pago', 'title' => 'Saldo'),
		array('field' => 'tipo_pago_glosa', 'title' => 'Tipo'),
		array('field' => 'glosa_documento', 'title' => 'Descripción'),
		array('field' => 'banco_nombre', 'title' => 'Banco'),
		array('field' => 'numero_cuenta', 'title' => 'Cuenta'),
		array('field' => 'uso', 'title' => 'Uso'),
		array('field' => 'cobros', 'title' => 'Cobros'),
		array('field' => 'facturas', 'title' => 'Documentos Legales')
	);

	function formatoFecha($fecha) {
		$_fecha = explode('-', str_replace('/', '-', $fecha));
		return intval($_fecha[2] . $_fecha[1] . $_fecha[0]);
	}

	function searchQuery() {
		$where = 'adelanto.es_adelanto = ' . (isset($this->extra_fields['eliminados']) ? -1 : 1);

		if (isset($this->extra_fields['tiene_saldo']) && $this->extra_fields['tiene_saldo'] == 1) {
			$where .= ' AND adelanto.saldo_pago < 0 ';
		}
		if (!empty($this->extra_fields['id_documento'])) {
			$id_documento = intval($this->extra_fields['id_documento']);
			$where .= " AND adelanto.id_documento = {$id_documento}";
		}
		if (!empty($this->fields['codigo_asunto'])) {
			$where .= " AND asunto.codigo_asunto like '%{$this->fields['codigo_asunto']}%'";
		}
		if (!empty($this->fields['codigo_cliente'])) {
			$where .= " AND cliente.codigo_cliente = '{$this->fields['codigo_cliente']}' ";
		}
		if (!empty($this->fields['id_contrato'])) {
			$id_contrato = intval($this->fields['id_contrato']);
			$where .= " AND (adelanto.id_contrato = '{$id_contrato}' OR adelanto.id_contrato IS NULL)";
		}
		if (!empty($this->extra_fields['fecha1'])) {
			$fecha1 = $this->formatoFecha($this->extra_fields['fecha1']);
			$where .= " AND adelanto.fecha >= {$fecha1}";
		}
		if (!empty($this->extra_fields['fecha2'])) {
			$fecha2 = $this->formatoFecha($this->extra_fields['fecha2']);
			$where .= " AND adelanto.fecha <= {$fecha2}";
		}
		if (!empty($this->extra_fields['moneda_adelanto'])) {
			$id_moneda = intval($this->extra_fields['moneda_adelanto']);
			$where .= " AND adelanto.id_moneda = {$id_moneda}";
		}

		if (Conf::GetConf($this->sesion, 'NuevoModuloFactura')) {
			$select_group_concat = "GROUP_CONCAT(documento_cobro.id_cobro) AS cobros,
				GROUP_CONCAT(factura.numero) AS facturas";
			$left_join = "LEFT JOIN neteo_documento ON neteo_documento.id_documento_pago = adelanto.id_documento
				LEFT JOIN documento AS documento_cobro ON documento_cobro.id_documento = neteo_documento.id_documento_cobro
				LEFT JOIN factura_pago ON factura_pago.id_neteo_documento_adelanto = neteo_documento.id_neteo_documento
				LEFT JOIN cta_cte_fact_mvto AS ccfm ON factura_pago.id_factura_pago = ccfm.id_factura_pago
				LEFT JOIN cta_cte_fact_mvto_neteo AS ccfmn ON ccfmn.id_mvto_pago = ccfm.id_cta_cte_mvto
				LEFT JOIN cta_cte_fact_mvto AS ccfm2 ON ccfmn.id_mvto_deuda = ccfm2.id_cta_cte_mvto
				LEFT JOIN factura ON factura.id_factura = ccfm2.id_factura";
		} else {
			$select_group_concat = "GROUP_CONCAT(documento_cobro.id_cobro) AS cobros,
				GROUP_CONCAT(cobro.documento) AS facturas";
			$left_join = "LEFT JOIN neteo_documento ON neteo_documento.id_documento_pago = adelanto.id_documento
				LEFT JOIN documento AS documento_cobro ON documento_cobro.id_documento = neteo_documento.id_documento_cobro
				LEFT JOIN cobro ON cobro.id_cobro = documento_cobro.id_cobro";
		}

		$query = "SELECT SQL_CALC_FOUND_ROWS
			adelanto.id_documento,
			cliente.glosa_cliente,
			adelanto.fecha,
			IF(adelanto.id_contrato IS NULL, 'Todos los Asuntos', GROUP_CONCAT(DISTINCT asunto.glosa_asunto ORDER BY asunto.glosa_asunto ASC)) AS asuntos,
			IF(adelanto.monto = 0, 0, adelanto.monto * -1) AS monto,
			IF(adelanto.saldo_pago = 0, 0, adelanto.saldo_pago * -1) AS saldo_pago,
			adelanto.glosa_documento,
			prm_moneda.id_moneda,
			prm_moneda.glosa_moneda AS tipo_moneda,
			prm_moneda.cifras_decimales,
			prm_tipo_pago.glosa AS tipo_pago_glosa,
			prm_banco.nombre AS banco_nombre,
			cuenta_banco.numero AS numero_cuenta,
			IF(adelanto.pago_honorarios = 1 AND adelanto.pago_gastos = 1, 'HyG',
			IF(adelanto.pago_honorarios = 1 AND adelanto.pago_gastos = 0, 'H',
			IF(adelanto.pago_honorarios = 0 AND adelanto.pago_gastos = 1, 'G',
			''))) AS uso,
			{$select_group_concat}
		FROM documento AS adelanto
			JOIN prm_moneda ON prm_moneda.id_moneda = adelanto.id_moneda
			JOIN cliente ON cliente.codigo_cliente = adelanto.codigo_cliente
			LEFT JOIN asunto ON asunto.codigo_cliente = adelanto.codigo_cliente AND asunto.id_contrato = adelanto.id_contrato
			LEFT JOIN prm_tipo_pago ON prm_tipo_pago.codigo = adelanto.tipo_doc
			LEFT JOIN prm_banco ON prm_banco.id_banco = adelanto.id_banco
			LEFT JOIN cuenta_banco ON cuenta_banco.id_banco = adelanto.id_banco
			{$left_join}
		WHERE {$where}
		GROUP BY adelanto.id_documento";

		return $query;
	}

	/**
	 * Descarga el reporte excel básico según configuraciones
	 */
	public function DownloadExcel() {
		require_once Conf::ServerDir() . '/classes/Reportes/SimpleReport.php';

		$SimpleReport = new SimpleReport($this->sesion);
		$SimpleReport->SetRegionalFormat(UtilesApp::ObtenerFormatoIdioma($this->sesion));
		$SimpleReport->LoadConfiguration('ADELANTOS');

		$query = $this->searchQuery();

		$statement = $this->sesion->pdodbh->prepare($query);
		$statement->execute();
		$results = $statement->fetchAll(PDO::FETCH_ASSOC);
		$SimpleReport->LoadResults($results);

		$writer = SimpleReport_IOFactory::createWriter($SimpleReport, 'Spreadsheet');
		$writer->save('Adelanto');
	}

}
