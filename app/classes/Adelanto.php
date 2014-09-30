<?php

require_once dirname(__FILE__) . '/../conf.php';

/**
 * Clase para manejar las solicitudes de adelanto
 */
class Adelanto extends Documento {

	public static $configuracion_reporte = array(
		array('field' => 'id_documento', 'title' => 'N° Adelanto'),
		array('field' => 'glosa_cliente', 'title' => 'Cliente'),
		array('field' => 'fecha', 'title' => 'Fecha'),
		array('field' => 'asuntos', 'title' => 'Asunto'),
		array('field' => 'tipo_moneda', 'title' => 'Moneda'),
		array('field' => 'monto', 'title' => 'Monto'),
		array('field' => 'saldo_pago', 'title' => 'Saldo')
	);

	function formatoFecha($fecha) {
		$_fecha = explode('-', str_replace('/', '-', $fecha));
		return intval($_fecha[2] . $_fecha[1] . $_fecha[0]);
	}

	function searchQuery() {
		$where = (isset($this->extra_fields['eliminados']) ? 'es_adelanto = -1' : 'es_adelanto = 1');

		if (isset($this->extra_fields['tiene_saldo']) && $this->extra_fields['tiene_saldo'] == 1) {
			$where .= ' AND saldo_pago < 0 ';
		}
		if (!empty($this->fields['id_documento'])) {
			$id_documento = intval($this->fields['id_documento']);
			$where .= " AND documento.id_documento = {$id_documento}";
		}
		if (!empty($this->extra_fields['campo_codigo_asunto'])) {
			$where .= " AND asuntos.codigo_asuntos like '%{$this->extra_fields['campo_codigo_asunto']}%'";
		}
		if (!empty($this->extra_fields['codigo_cliente'])) {
			$where .= " AND cliente.codigo_cliente = '{$this->extra_fields['codigo_cliente']}' ";
		}
		if (!empty($this->fields['id_contrato'])) {
			$id_contrato = intval($this->fields['id_contrato']);
			$where .= " AND (documento.id_contrato = '{$id_contrato}' OR documento.id_contrato IS NULL)";
		}
		if (!empty($this->extra_fields['fecha1'])) {
			$fecha1 = $this->formatoFecha($this->extra_fields['fecha1']);
			$where .= " AND documento.fecha >= {$fecha1}";
		}
		if (!empty($this->extra_fields['fecha2'])) {
			$fecha2 = $this->formatoFecha($this->extra_fields['fecha2']);
			$where .= " AND documento.fecha <= {$fecha2}";
		}
		if (!empty($this->extra_fields['moneda_adelanto'])) {
			$id_moneda = intval($this->extra_fields['moneda']);
			$where .= " AND documento.id_moneda = {$id_moneda}";
		}

		$query = "SELECT SQL_CALC_FOUND_ROWS
			documento.id_documento,
			cliente.glosa_cliente,
			documento.fecha,
			IF(documento.id_contrato IS NULL, 'Todos los Asuntos', GROUP_CONCAT(glosa_asunto)) AS asuntos,
			IF(documento.monto = 0, 0, documento.monto * -1) AS monto,
			IF(documento.saldo_pago = 0, 0, documento.saldo_pago * -1) AS saldo_pago,
			documento.glosa_documento,
			prm_moneda.id_moneda,
			prm_moneda.simbolo AS tipo_moneda,
			prm_moneda.cifras_decimales
		FROM documento
			JOIN prm_moneda ON prm_moneda.id_moneda = documento.id_moneda
			JOIN cliente ON documento.codigo_cliente = cliente.codigo_cliente
			LEFT JOIN asunto ON documento.codigo_cliente = asunto.codigo_cliente AND (documento.id_contrato = asunto.id_contrato)
		WHERE {$where}
		GROUP BY documento.id_documento, cliente.glosa_cliente, documento.fecha, documento.monto, documento.saldo_pago, documento.glosa_documento, prm_moneda.id_moneda";

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
