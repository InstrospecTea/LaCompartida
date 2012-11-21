<?php

require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Lista.php';
require_once Conf::ServerDir() . '/../fw/classes/Objeto.php';
require_once Conf::ServerDir() . '/classes/Debug.php';


class Retribuciones {

	public static $configuracion_reporte = array(
		array(
			'field' => 'id_cobro',
			'title' => 'N° Cobro',
			'extras' => array(
				'width' => 8,
				'attrs' => 'style="text-align:right"'
			)
		),
		array(
			'field' => 'glosa_cliente',
			'title' => 'Cliente',
			'group' => '1',
			'extras' => array(
				'width' => 20,
				'attrs' => 'style="text-align:left"'
			)
		),
		array(
			'field' => 'documento',
			'title' => 'Facturas',
			'extras' => array(
				'width' => 45,
				'attrs' => 'style="text-align:right"'
			)
		),
		array(
			'field' => 'glosa_asuntos',
			'title' => 'Asuntos',
			'extras' => array(
				'width' => 45,
				'attrs' => 'style="text-align:left"'
			)
		),
		array(
			'field' => 'fecha_emision',
			'title' => 'Fecha Emisión',
			'format' => 'date',
			'extras' => array(
				'width' => 20
			)
		),
		array(
			'field' => 'fecha_facturacion',
			'title' => 'Fecha Facturación',
			'format' => 'date',
			'extras' => array(
				'width' => 20
			)
		),
		array(
			'field' => 'fecha_cobro',
			'title' => 'Fecha Pago',
			'format' => 'date',
			'extras' => array(
				'width' => 20
			)
		),
		array(
			'field' => 'total_horas',
			'title' => 'Horas',
			'format' => 'time',
			'extras' => array(
				'width' => 20,
				'attrs' => 'style="text-align:right"',
				'subtotal' => false
			)
		),
		array(
			'field' => 'monto',
			'title' => 'Monto',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'simbolo',
				'subtotal' => false,
				'attrs' => 'style="text-align:right"'
			)
		),
		array(
			'field' => 'monto_trabajos',
			'title' => 'Monto Honorarios',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'simbolo',
				'subtotal' => false,
				'attrs' => 'style="text-align:right"'
			)
		),
		array(
			'field' => 'nombre_usuario_responsable',
			'title' => 'Encargado Comercial',
			'extras' => array(
				'width' => 30,
				'attrs' => 'style="text-align:left"'
			)
		),
		array(
			'field' => 'retribucion_usuario_responsable',
			'title' => 'Ret. Encargado Comercial',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'simbolo',
				'subtotal' => false,
				'attrs' => 'style="text-align:right"'
			)
		),
		array(
			'field' => 'nombre_usuario_secundario',
			'title' => 'Encargado Secundario',
			'extras' => array(
				'width' => 30,
				'attrs' => 'style="text-align:left"'
			)
		),
		array(
			'field' => 'retribucion_usuario_secundario',
			'title' => 'Ret. Encargado Secundario',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'simbolo',
				'subtotal' => false,
				'attrs' => 'style="text-align:right"'
			)
		),
	);

	public static $configuracion_subreporte = array(
		array(
			'field' => 'glosa_area_padre',
			'title' => 'Área',
			'group' => '1',
			'extras' => array(
				'width' => 8,
				'attrs' => 'style="text-align:left"'
			)
		),
		array(
			'field' => 'glosa_area',
			'title' => 'Área',
			'group' => '2',
			'extras' => array(
				'width' => 8,
				'attrs' => 'style="text-align:left"'
			)
		),
		array(
			'field' => 'nombre',
			'title' => 'Distribución de la Retribución',
			'extras' => array(
				'width' => 8,
				'attrs' => 'style="text-align:left;padding-left:30px;"'
			)
		),
		array(
			'field' => 'porcentaje_retribucion',
			'title' => '% Retribución',
			'extras' => array(
				'width' => 20,
				'attrs' => 'style="text-align:right"'
			)
		),
		array(
			'field' => 'horas_cobradas',
			'title' => 'Horas Cobradas',
			'format' => 'time',
			'extras' => array(
				'width' => 20,
				'attrs' => 'style="text-align:right"'
			)
		),
		array(
			'field' => 'monto_cobrado',
			'title' => 'Monto Cobrado',
			'format' => 'number',
			'extras' => array(
				'width' => 100,
				'symbol' => 'simbolo',
				'attrs' => 'style="text-align:right"'
			)
		),

		array(
			'field' => 'porcentaje_intervencion',
			'title' => '% Intervención',
			'format' => 'number',
			'extras' => array(
				'decimals' => 2,
				'attrs' => 'style="text-align:right"'
			)
		),
		array(
			'field' => 'retribucion_socios',
			'title' => 'Aporte al Área [%]',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'simbolo',
				'attrs' => 'style="text-align:right"'
			)
		),
		array(
			'field' => 'retribucion_abogados',
			'title' => 'Retribución por trabajo',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'simbolo',
				'attrs' => 'style="text-align:right"'
			)
		)
	);

	function Retribuciones($sesion, $fields = "", $params = "") {
		$this->campo_id = "id_usuario";
		$this->sesion = $sesion;
	}

	public function GetListaCobros() {
		if (isset($this->reporte_data) && !empty($this->reporte_data)) {
			$lista_id_cobros = array_map('reset', $this->reporte_data);
			$id_cobros = empty($lista_id_cobros) ? '0' : implode(', ', $lista_id_cobros);
			return $id_cobros;
		} else {
			return '0';
		}
	}

	public function FetchReporte($filtros, $fetch) {
		$response = $this->sesion->pdodbh->query($this->QueryReporte($filtros));
		$this->reporte_data = $response->fetchAll($fetch);
		return $this->reporte_data;
	}

	public function FetchSubReporte($filtros, $fetch) {
		$response = $this->sesion->pdodbh->query($this->QuerySubReporte($filtros));
		$this->subreporte_data = $response->fetchAll($fetch);
		return $this->subreporte_data;
	}

	public function GetFechaEstado($estado) {
		$campo_fecha = 'fecha_emision';
		if (!empty($estado)) {
			$campo_fecha_estado = array(
				'EMITIDO,ENVIADO AL CLIENTE' => 'fecha_emision',
				'FACTURADO,PAGO PARCIAL' => 'fecha_facturacion',
				'PAGADO' => 'fecha_cobro',
			);
			$campo_fecha = $campo_fecha_estado[$estado];
		}
		return $campo_fecha;
	}

	public function QueryReporte($filtros = array()) {
		extract($filtros);
		$wheres = array();
		$campo_fecha = $this->GetFechaEstado($estado);

		if (!empty($estado)) {
			$wheres[] = "cobro.estado IN('" . str_replace(',', "', '", $estado) . "')";
		}

		$wheres[] = "cobro.$campo_fecha BETWEEN '" . Utiles::fecha2sql($fecha1) . "' AND '" . Utiles::fecha2sql($fecha2) . "'";
		$wheres[] = "moneda_filtro.id_moneda = $moneda_filtro";
		if (!empty($usuarios)) {
			$wheres[] = 'usuario_responsable.id_usuario IN (' . implode(', ', $usuarios) . ')';
		}

		$where = implode(' AND ', $wheres);

		$query = "SELECT
				cobro.id_cobro,
				documento.monto_trabajos*(moneda_cobro.tipo_cambio)/(moneda_filtro.tipo_cambio) as monto_trabajos,
				cobro.monto_trabajos / cobro.monto_thh_estandar as rentabilidad,
				cobro.opc_moneda_total,
				cobro.total_minutos,
				cobro.total_minutos / 60 as total_horas,
				cobro.documento,
				cobro.estado,
				cobro.fecha_emision,
				cobro.fecha_cobro,
				cobro.fecha_facturacion,
				documento.monto*(moneda_cobro.tipo_cambio)/(moneda_filtro.tipo_cambio) as monto,
				prm_moneda.simbolo,
				prm_moneda.cifras_decimales,
				cliente.glosa_cliente,
				GROUP_CONCAT(asunto.glosa_asunto SEPARATOR ', ') as glosa_asuntos,
				(contrato.retribucion_usuario_responsable/100)*documento.monto_trabajos*(moneda_cobro.tipo_cambio)/(moneda_filtro.tipo_cambio) as retribucion_usuario_responsable,
				CONCAT(usuario_responsable.nombre, ' ', usuario_responsable.apellido1) as nombre_usuario_responsable,
				(contrato.retribucion_usuario_secundario/100)*documento.monto_trabajos*(moneda_cobro.tipo_cambio)/(moneda_filtro.tipo_cambio) as retribucion_usuario_secundario,
				CONCAT(usuario_secundario.nombre, ' ', usuario_secundario.apellido1) as nombre_usuario_secundario
			FROM cobro
				JOIN documento ON documento.id_cobro = cobro.id_cobro AND documento.tipo_doc = 'N'
				JOIN cobro_moneda as moneda_filtro ON moneda_filtro.id_cobro = cobro.id_cobro
				JOIN prm_moneda ON moneda_filtro.id_moneda = prm_moneda.id_moneda
				JOIN cobro_moneda as moneda_cobro ON moneda_cobro.id_cobro = cobro.id_cobro
					AND moneda_cobro.id_moneda =cobro.opc_moneda_total
				JOIN contrato ON contrato.id_contrato = cobro.id_contrato
				JOIN cliente ON cliente.codigo_cliente = contrato.codigo_cliente
				JOIN cobro_asunto on cobro_asunto.id_cobro = cobro.id_cobro
				JOIN asunto ON asunto.codigo_asunto = cobro_asunto.codigo_asunto
				LEFT JOIN usuario AS usuario_responsable ON usuario_responsable.id_usuario = contrato.id_usuario_responsable
				LEFT JOIN usuario AS usuario_secundario ON usuario_secundario.id_usuario = contrato.id_usuario_secundario
			WHERE cobro.monto_subtotal > 0 AND $where
			GROUP BY cobro.id_cobro";
		//echo "<!-- " . $query . " -->";
		return $query;
	}

	public function QuerySubReporte($filtros = array()) {
		extract($filtros);
		$query_detalle = "SELECT
			trabajo.id_cobro,
			CONCAT(usuario.nombre, ' ', usuario.apellido1) as nombre,
			usuario.porcentaje_retribucion,
			area.id AS id_area,
			area.id_padre AS id_area_padre,
			IFNULL(area.id_padre, area.id) AS id_area_grupo,
			area.glosa AS glosa_area,
			IFNULL(area_padre.glosa, area.glosa) AS glosa_area_padre,
			area_padre.glosa as glosa_area_padre_2,
			SUM(TIME_TO_SEC(trabajo.duracion_cobrada))/60 as minutos_cobrados,
			SUM(TIME_TO_SEC(trabajo.duracion_cobrada))/3600 as horas_cobradas,
			SUM(TIME_TO_SEC(trabajo.duracion_cobrada)/3600*trabajo.tarifa_hh_estandar)*(moneda_cobro.tipo_cambio)/(moneda_filtro.tipo_cambio) as monto_cobrado,
			prm_moneda.simbolo
		FROM trabajo
			JOIN cobro_moneda as moneda_filtro ON moneda_filtro.id_cobro = trabajo.id_cobro
			JOIN prm_moneda ON prm_moneda.id_moneda = moneda_filtro.id_moneda
			JOIN cobro_moneda as moneda_cobro ON moneda_cobro.id_cobro = trabajo.id_cobro
				AND moneda_cobro.id_moneda = trabajo.id_moneda
			JOIN usuario ON usuario.id_usuario = trabajo.id_usuario
			JOIN prm_area_usuario area ON area.id = usuario.id_area_usuario
			LEFT JOIN prm_area_usuario area_padre ON area_padre.id = area.id_padre
		WHERE trabajo.id_cobro IN ($cobros) AND moneda_filtro.id_moneda = $moneda_filtro AND trabajo.cobrable = 1
		GROUP BY trabajo.id_cobro, trabajo.id_usuario
		ORDER BY id_cobro, glosa_area_padre, area.id_padre, glosa_area";
		//echo "<!-- $query_detalle -->";
		return $query_detalle;
	}

	public function ProcesaDatosSubreporte($datos_reporte, &$datos_subreporte, $tipo_calculo, $porcentaje_retribucion_socios) {

		foreach ($datos_reporte as $encabezado) {
			$detalles_retribucion = $datos_subreporte[$encabezado['id_cobro']];
			if(!$detalles_retribucion){
				$detalles_retribucion = array();
			}
			foreach ($detalles_retribucion as $index=>$retribucion) {
				$rentabilidad = $encabezado['rentabilidad'];
				if ($tipo_calculo == 'duracion_cobrada'){
					$porcentaje_intervencion = $retribucion['minutos_cobrados'] / $encabezado['total_minutos'] * 100;
					$rentabilidad = 1;
				} else{
					$porcentaje_intervencion = ($retribucion['monto_cobrado']*$rentabilidad) / $encabezado['monto_trabajos'] * 100;
				}
				$retribucion_socios = $encabezado['monto_trabajos'] * $porcentaje_intervencion * $porcentaje_retribucion_socios / (100 * 100);
				$retribucion_abogados = $encabezado['monto_trabajos'] * $porcentaje_intervencion * $retribucion['porcentaje_retribucion'] / (100 * 100);
				$datos_subreporte[$encabezado['id_cobro']][$index]['porcentaje_intervencion'] = $porcentaje_intervencion;
				$datos_subreporte[$encabezado['id_cobro']][$index]['porcentaje_intervencion'] = $porcentaje_intervencion;
				$datos_subreporte[$encabezado['id_cobro']][$index]['retribucion_socios'] = $retribucion_socios;
				$datos_subreporte[$encabezado['id_cobro']][$index]['retribucion_abogados'] = $retribucion_abogados;
				$datos_subreporte[$encabezado['id_cobro']][$index]['monto_cobrado'] = $rentabilidad*$retribucion['monto_cobrado'];
			}
		}
	}

	public function PreparaReporte($tipo, $datos_reporte, $datos_subreporte, $filtros = array()){
		extract($filtros);
		$porcentaje_retribucion_socios = Conf::GetConf($this->sesion, 'RetribucionCentroCosto');

		$reporte = new SimpleReport($this->sesion);
		$reporte->LoadConfiguration('RETRIBUCIONES_ENCABEZADO');
		if(!UtilesApp::GetConf($this->sesion, 'EncargadoSecundario')){
			$reporte->Config->columns['nombre_usuario_secundario']->Visible(false);
			$reporte->Config->columns['retribucion_usuario_secundario']->Visible(false);
		}
		$tipo_fecha = $this->GetFechaEstado($estado);
		$reporte->Config->columns['fecha_facturacion']->Visible($tipo_fecha == 'fecha_facturacion');
		$reporte->Config->columns['fecha_cobro']->Visible($tipo_fecha == 'fecha_cobro');

		$reporte->SetCustomFormat(array(
			'odd_color' => 'fff',
			'repeat_header_each_row' => true
		));

		//Prepare child data here !!!
		$this->ProcesaDatosSubreporte($datos_reporte, $datos_subreporte, $tipo_calculo, $porcentaje_retribucion_socios);
		//end prepare child dat
		$subreporte = new SimpleReport($this->sesion);
		$subreporte->LoadConfiguration('RETRIBUCIONES_DETALLE');

		$subreporte->Config->columns['horas_cobradas']->Visible($tipo_calculo != 'monto_cobrado');
		$subreporte->Config->columns['monto_cobrado']->Visible($tipo_calculo == 'monto_cobrado');
		if ($porcentaje_retribucion_socios>0) {
			$subreporte->Config->columns['retribucion_socios']->Title("Aporte al Área $porcentaje_retribucion_socios%");
		} else {
			$subreporte->Config->columns['retribucion_socios']->Visible(false);
		}

		$subreporte->LoadResults($datos_subreporte);
		$subreporte->SetCustomFormat(array(
			'single_table' => true,
			'odd_color' => 'fff'
		));
		$reporte->AddSubReport(array(
			'SimpleReport' => $subreporte,
			'Keys' => array('id_cobro'),
			'Level' => 1
		));
		$reporte->LoadResults($datos_reporte);
		return $reporte;
	}

	public function DownloadExcel($tipo, $datos_reporte, $datos_subreporte, $filtros = array()) {
		require_once Conf::ServerDir() . '/classes/Reportes/SimpleReport.php';
		$reporte = $this->PreparaReporte($tipo, $datos_reporte, $datos_subreporte, $filtros);
		$writer = SimpleReport_IOFactory::createWriter($reporte, 'Spreadsheet');
		$writer->save('Retribuciones Detalle');
	}

	public function PrintHtml($tipo, $datos_reporte, $datos_subreporte, $filtros = array()) {
		require_once Conf::ServerDir() . '/classes/Reportes/SimpleReport.php';
		$reporte = $this->PreparaReporte($tipo, $datos_reporte, $datos_subreporte, $filtros);
		$writer = SimpleReport_IOFactory::createWriter($reporte, 'Html');
		return $writer->save('Retribuciones Detalle');
	}


}