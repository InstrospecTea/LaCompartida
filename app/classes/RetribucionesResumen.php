<?php

require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Lista.php';
require_once Conf::ServerDir() . '/../fw/classes/Objeto.php';
require_once Conf::ServerDir() . '/classes/Debug.php';


class RetribucionesResumen {

	public static $configuracion_reporte = array(
		array(
			'field' => 'glosa_area_padre',
			'title' => 'Área Padre',
			'group' => '1',
			'extras' => array(
				'width' => 8,
			)
		),
		array(
			'field' => 'glosa_area',
			'title' => 'Área Hija',
			'group' => '2',
			'extras' => array(
				'width' => 8,
			)
		),
		array(
			'field' => 'nombre',
			'title' => 'Profesional',
			'extras' => array(
				'width' => 30,
				'attrs' => 'style="text-align:left;width:180px"'
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
				'attrs' => 'style="text-align:right"',
				'subtotal' => false
			)
		),
		array(
			'field' => 'monto_cobrado',
			'title' => 'Monto Cobrado',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'simbolo',
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
			'title' => 'Ret. Por trabajo',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'simbolo',
				'attrs' => 'style="text-align:right;width:90px;border-left:solid 10px #FFF"'
			)
		),
		array(
			'field' => 'retribucion_usuario_responsable',
			'title' => 'Ret. Encargado Comercial',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'simbolo',
				'attrs' => 'style="text-align:right;width:90px"'
			)
		),
		 array(
			'field' => 'retribucion_usuario_secundario',
			'title' => 'Ret. Encargado Secundario',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'simbolo',
				'attrs' => 'style="text-align:right;width:90px"'
			)
		),
		 array(
			'field' => '=SUM(%retribucion_usuario_secundario%,%retribucion_usuario_responsable%,%retribucion_abogados%)',
			'title' => 'Ret. Total',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'simbolo',
				'attrs' => 'style="text-align:right;width:90px"'
			)
		)
	);

	public static $configuracion_subreporte = array(
		array(
			'field' => 'id_cobro',
			'title' => 'N° Cobro',
			'extras' => array(
				'width' => 8,
				'attrs' => 'style="text-align:right;width:30px"'
			)
		),
		array(
			'field' => 'porcentaje_intervencion',
			'title' => '% Intervención',
			'format' => 'number',
			'extras' => array(
				'subtotal' => false,
				'attrs' => 'style="text-align:righ;width:100px"'
			)
		),
	array(
			'field' => 'porcentaje_retribucion',
			'title' => '% Retribución',
			'format' => 'number',
			'extras' => array(
				'width' => 20,
				'subtotal' => false,
				'attrs' => 'style="text-align:right"'
			)
		),
		array(
			'field' => 'horas_cobradas',
			'title' => 'Horas Cobradas',
			'format' => 'time',
			'extras' => array(
				'width' => 20,
				'attrs' => 'style="text-align:right"',
				'subtotal' => false
			)
		),
		array(
			'field' => 'monto_cobrado',
			'title' => 'Monto Cobrado',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'simbolo',
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
			'title' => 'Ret. Por trabajo',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'simbolo',
				'attrs' => 'style="text-align:right;width:90px;border-left:solid 10px #FFF"'
			)
		),
		array(
			'field' => 'retribucion_usuario_responsable',
			'title' => 'Ret. Encargado Comercial',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'simbolo',
				'attrs' => 'style="text-align:right;width:90px"'
			)
		),
		 array(
			'field' => 'retribucion_usuario_secundario',
			'title' => 'Ret. Encargado Secundario',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'simbolo',
				'attrs' => 'style="text-align:right;width:90px"'
			)
		),
		 array(
			'field' => '=SUM(%retribucion_usuario_secundario%,%retribucion_usuario_responsable%,%retribucion_abogados%)',
			'title' => 'Ret. Total',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'simbolo',
				'attrs' => 'style="text-align:right;width:90px"'
			)
		)
	);

	function RetribucionesResumen($sesion, $fields = "", $params = "") {
		$this->campo_id = "id_usuario";
		$this->sesion = $sesion;
	}

	public function MarcarRetribuidos($filtros = array(), $flag_marca = true){
		$this->filtros = $filtros;
		$this->ObtieneDatos(false); //sin procesarlos
		extract($this->filtros);
		$fecha = date('Y-m-d H:i:s'); //no uso NOW() para que queden todas identicas
		$porcentaje_retribucion_socios = $this->porcentaje_retribucion_socios;

		$campo_intervencion = $tipo_calculo == 'duracion_cobrada' ?
			'IF(c.total_minutos > 0, TIME_TO_SEC(t.duracion_cobrada) / (c.total_minutos * 60), 0)' :
			'IF(d.monto_trabajos > 0 and c.monto_thh_estandar > 0, TIME_TO_SEC(t.duracion_cobrada) / 3600 * t.tarifa_hh_estandar / c.monto_thh_estandar, 0)';

		$campo_monto = 'd.monto_trabajos * mc.tipo_cambio / mf.tipo_cambio';
		$campo_monto_intervencion = "$campo_monto * $campo_intervencion";

		$where_usuarios = !empty($usuarios) ? ' IN (' . implode(', ', $usuarios) . ') ' : ' IS NOT NULL ';
		try {

			$query_marcar = "UPDATE trabajo t
					JOIN cobro c ON c.id_cobro = t.id_cobro
					JOIN documento d ON d.id_cobro = c.id_cobro AND d.tipo_doc = 'N'
					JOIN cobro_moneda mc ON mc.id_cobro = c.id_cobro AND mc.id_moneda = c.opc_moneda_total
					JOIN cobro_moneda mf ON mf.id_cobro = c.id_cobro AND mf.id_moneda = $moneda_filtro
					JOIN usuario u ON u.id_usuario = t.id_usuario
				SET
					fecha_retribucion = '$fecha',
					id_moneda_retribucion = $moneda_filtro,
					monto_retribucion_usuario = $campo_monto_intervencion * u.porcentaje_retribucion / 100,
					monto_retribucion_area = $campo_monto_intervencion * $porcentaje_retribucion_socios / 100

				WHERE t.id_cobro IN ($cobros)
					AND t.cobrable = 1
					AND t.fecha_retribucion IS NULL
					AND t.id_usuario $where_usuarios";

			$this->sesion->pdodbh->exec($query_marcar);

			foreach(array('responsable', 'secundario') as $encargado){
				$query_marcar_encargado = "UPDATE cobro c
						JOIN documento d ON d.id_cobro = c.id_cobro AND d.tipo_doc = 'N'
						JOIN cobro_moneda mc ON mc.id_cobro = c.id_cobro AND mc.id_moneda = c.opc_moneda_total
						JOIN cobro_moneda mf ON mf.id_cobro = c.id_cobro AND mf.id_moneda = $moneda_filtro
						JOIN contrato con ON con.id_contrato = c.id_contrato
					SET
						fecha_retribucion_$encargado = '$fecha',
						monto_retribucion_$encargado = $campo_monto * con.retribucion_usuario_$encargado / 100,
						id_moneda_retribucion_$encargado = $moneda_filtro
					WHERE c.id_cobro IN ($cobros)
						AND c.id_usuario_$encargado $where_usuarios";

				$this->sesion->pdodbh->exec($query_marcar_encargado);
			}
			return true;
		} catch (PDOException $e) {
			return false;
		}
	}

	public function GetListaCobros() {
		if (isset($this->cobros_group) && !empty($this->cobros_group)) {
			$lista_id_cobros = array_keys($this->cobros_group);
			return empty($lista_id_cobros) ? '0' : implode(', ', $lista_id_cobros);
		} else {
			return '0';
		}
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

	public function QueryCobros() {
		extract($this->filtros);
		$wheres = array();
		$campo_fecha = $this->GetFechaEstado($estado);
		if (!empty($estado)) {
			$wheres[] = "cobro.estado IN('" . str_replace(',', "', '", $estado) . "')";
		}

			$wheres[] = "cobro.$campo_fecha BETWEEN '" . Utiles::fecha2sql($fecha1) . "' AND '" . Utiles::fecha2sql($fecha2) . "'";

		$where = implode(' AND ', $wheres);

		$campos_retribucion = '
			contrato.retribucion_usuario_responsable,
			contrato.retribucion_usuario_secundario';
		if($retribuidos){
			$cond = $retribuidos == 'SI' ? 'IS NOT NULL' : 'IS NULL';
			$campos_retribucion = "
			IF(cobro.fecha_retribucion_responsable $cond, contrato.retribucion_usuario_responsable, 0) as retribucion_usuario_responsable,
			IF(cobro.fecha_retribucion_secundario $cond, contrato.retribucion_usuario_secundario, 0) as retribucion_usuario_secundario";
		}

		$query_cobros = "SELECT
			cobro.id_cobro,
			cobro.monto_trabajos / cobro.monto_thh_estandar as rentabilidad,
			cobro.total_minutos,
			documento.monto_trabajos*(moneda_cobro.tipo_cambio)/(moneda_filtro.tipo_cambio) as monto_trabajos,
			cobro.id_usuario_responsable,
			cobro.id_usuario_secundario,
			prm_moneda.simbolo,
			$campos_retribucion
		FROM cobro
			JOIN documento ON documento.id_cobro = cobro.id_cobro AND documento.tipo_doc = 'N'
			JOIN cobro_moneda as moneda_filtro ON moneda_filtro.id_cobro = cobro.id_cobro
				AND moneda_filtro.id_moneda = $moneda_filtro
			JOIN prm_moneda ON moneda_filtro.id_moneda = prm_moneda.id_moneda
			JOIN cobro_moneda as moneda_cobro ON moneda_cobro.id_cobro = cobro.id_cobro
				AND moneda_cobro.id_moneda =cobro.opc_moneda_total
			JOIN contrato ON contrato.id_contrato = cobro.id_contrato
		WHERE cobro.monto_subtotal > 0 AND $where
		GROUP BY cobro.id_cobro";

		return $query_cobros;

	}

	public function QueryUsuarios() {
		extract($this->filtros);
		$wheres_detalle = array();

		if (!empty($usuarios)) {
			$wheres_detalle[] = 'todos.id_usuario IN (' . implode(', ', $usuarios) . ')';
		}
		if (!empty($id_area_usuario)) {
			$wheres_detalle[] = "area.id = $id_area_usuario";
		}
		$wheres_detalle[] = "todos.visible = 1 AND usuario_permiso.codigo_permiso='PRO'";

		$where_detalle = implode(' AND ', $wheres_detalle);

		$where_trabajo = '';
		if($retribuidos){
			$where_trabajo = ' AND fecha_retribucion ' . ($retribuidos == 'SI' ? 'IS NOT NULL' : 'IS NULL');
		}

		$query_detalle = "SELECT todos.id_usuario,
				 trabajo.id_cobro,
				 Concat(todos.nombre, ' ', todos.apellido1)                   AS nombre,
				 todos.porcentaje_retribucion,
				 area.id                                                      AS id_area,
				 area.id_padre                                                AS id_area_padre,
				 Ifnull(area.id_padre, area.id)                               AS id_area_grupo,
				 Ifnull(area_padre.glosa, area.glosa)                         AS glosa_area_padre,
				 area.glosa                                                   AS glosa_area,
				 Sum(Time_to_sec(trabajo.duracion_cobrada)) / 60              AS minutos_cobrados,
				 Sum(Time_to_sec(trabajo.duracion_cobrada) / 3600 * trabajo.tarifa_hh_estandar) *
					( moneda_cobro.tipo_cambio ) / ( moneda_filtro.tipo_cambio ) AS monto_cobrado,
					prm_moneda.simbolo
			FROM   usuario AS todos
						 JOIN prm_area_usuario area
							 ON area.id = todos.id_area_usuario
						 LEFT JOIN prm_area_usuario area_padre
										ON area_padre.id = area.id_padre
						 JOIN usuario_permiso using(id_usuario)
						 LEFT JOIN ((SELECT id_cobro,
															 duracion_cobrada,
															 tarifa_hh_estandar,
															 id_usuario,
															 id_moneda
												FROM   trabajo
												WHERE  trabajo.id_cobro IN ($cobros)
															 AND trabajo.cobrable = 1 $where_trabajo ) AS trabajo
												JOIN cobro_moneda AS moneda_cobro
													ON moneda_cobro.id_cobro = trabajo.id_cobro
												JOIN cobro_moneda AS moneda_filtro
													ON moneda_filtro.id_cobro = trabajo.id_cobro
														 AND moneda_filtro.id_moneda = $moneda_filtro
														 AND moneda_cobro.id_moneda = trabajo.id_moneda
												JOIN prm_moneda ON moneda_filtro.id_moneda = prm_moneda.id_moneda)
										ON todos.id_usuario = trabajo.id_usuario
			WHERE  $where_detalle
			GROUP  BY todos.id_usuario,
								trabajo.id_cobro
			ORDER  BY glosa_area_padre,
								area.id_padre,
								glosa_area,
								todos.id_usuario ";

		return $query_detalle;

	}

	public function GetCobrosGroup() {
		$response_encabezados = $this->sesion->pdodbh->query($this->QueryCobros());
		return $response_encabezados->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_GROUP);
	}

	public function GetDetalleUsuario() {
		$response_usuarios = $this->sesion->pdodbh->query($this->QueryUsuarios());
		return $response_usuarios->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_GROUP);
	}

	public function ObtieneDatos($procesa = true) {
		extract($this->filtros);
		$moneda = new Moneda($this->sesion);
		$moneda->Load($moneda_filtro);
		$this->simbolo_moneda = $moneda->fields['simbolo'];
		$this->porcentaje_retribucion_socios = Conf::GetConf($this->sesion, 'RetribucionCentroCosto');
		$this->cobros_group = $this->GetCobrosGroup();
		$this->filtros['cobros'] = $this->GetListaCobros();
		$this->usuario_detalles = $this->GetDetalleUsuario();
		if ($procesa) {
			$this->ProcesaDatosSubreporte();
		}
	}

	private function AcumulaDatos($id_usuario, $trabajo, $valores, &$datos_usuario, &$datos_subreporte) {
		$datos_usuario["glosa_area_padre"] = $trabajo['glosa_area_padre'];
		$datos_usuario["glosa_area"] = $trabajo['glosa_area'];
		$datos_usuario["nombre"] = $trabajo['nombre'];
		$datos_usuario["porcentaje_retribucion"] = $valores['porcentaje_retribucion'];
		$datos_usuario["horas_cobradas"] += $valores['horas_cobradas'];
		$datos_usuario["monto_cobrado"] += $valores['monto_cobrado'];
		$datos_usuario["retribucion_socios"] += $valores['retribucion_socios'];
		$datos_usuario["retribucion_abogados"] += $valores['retribucion_abogados'];
		$datos_usuario["retribucion_usuario_responsable"] += $valores['retribucion_usuario_responsable'];
		$datos_usuario["retribucion_usuario_secundario"] += $valores['retribucion_usuario_secundario'];
		$datos_usuario["simbolo"] = $this->simbolo_moneda;
		$datos_subreporte[$id_usuario][] = $valores;
	}

	private function LimpiaDatosUsuario($id_usuario, &$datos_usuario) {
		$datos_usuario = array(
				"glosa_area_padre" => "",
				"glosa_area" => "",
				"nombre" => "",
				"porcentaje_retribucion" => 0,
				"horas_cobradas" => 0,
				"monto_cobrado" => 0,
				"retribucion_socios" => 0,
				"retribucion_abogados" => 0,
				"retribucion_usuario_responsable" => 0,
				"retribucion_usuario_secundario" => 0,
				"id_usuario" => $id_usuario,
				"simbolo" => $this->simbolo_moneda
			);
	}

	private function AgregaDatosUsuario($id_usuario, $trabajo,  &$datos_usuario) {
		$datos_usuario['glosa_area_padre'] = $trabajo['glosa_area_padre'];
		$datos_usuario['glosa_area'] = $trabajo['glosa_area'];
		$datos_usuario["porcentaje_retribucion"] = $trabajo['porcentaje_retribucion'];
		$datos_usuario['nombre'] = $trabajo['nombre'];
		$datos_usuario['id_usuario'] = $id_usuario;
		$datos_usuario['simbolo'] = $this->simbolo_moneda;
	}

	public function ProcesaDatosSubreporte() {
		extract($this->filtros);
		$encabezados = $this->cobros_group;
		$detalles = $this->usuario_detalles;
		$retribucion_encabezado = array();
		$this->genera_responsable = false;
		$this->genera_secundario  = false;

		foreach ($encabezados as $id_cobro => $encabezado) {
			$encabezado = reset($encabezado);
			if (!empty($encabezado['id_usuario_responsable']) && !empty($encabezado['retribucion_usuario_responsable']) &&
				(empty($usuarios) || in_array($encabezado['id_usuario_responsable'], $usuarios))) {
				$retribucion_encabezado[$encabezado['id_usuario_responsable']][$id_cobro]['responsable'] = $encabezado['retribucion_usuario_responsable'] * $encabezado['monto_trabajos'] / 100;
				$this->genera_responsable = true;
			}
			if (!empty($encabezado['id_usuario_secundario']) && !empty($encabezado['retribucion_usuario_secundario']) &&
				(empty($usuarios) || in_array($encabezado['id_usuario_secundario'], $usuarios))) {
				$retribucion_encabezado[$encabezado['id_usuario_secundario']][$id_cobro]['secundario'] = $encabezado['retribucion_usuario_secundario'] * $encabezado['monto_trabajos'] / 100;
				$this->genera_secundario = true;
			}
		}

		$datos_reporte = array();
		$datos_subreporte = array();
		foreach ($detalles as $id_usuario=>$retribucion) {
			$this->LimpiaDatosUsuario($id_usuario, $datos_usuario);
			foreach ($retribucion as $trabajo) {
				$id_cobro = $trabajo['id_cobro'];
				if (isset($id_cobro)) {
					$cobro 		= $encabezados[$id_cobro][0];
					$rentabilidad = $cobro['rentabilidad'];
					$monto_responsable = 0;
					$monto_secundario = 0;

					if ($tipo_calculo == 'duracion_cobrada') {
						$porcentaje_intervencion = $cobro['total_minutos'] ? $trabajo['minutos_cobrados'] / $cobro['total_minutos'] * 100 : 0;
					} else {
						$porcentaje_intervencion = $cobro['monto_trabajos'] ? $trabajo['monto_cobrado'] * $rentabilidad / $cobro['monto_trabajos'] * 100 : 0;
					}

					if (isset($retribucion_encabezado[$id_usuario][$id_cobro])) {
						$monto_responsable = (isset($retribucion_encabezado[$id_usuario][$id_cobro]['responsable']) ?
								$retribucion_encabezado[$id_usuario][$id_cobro]['responsable'] : 0);

						$monto_secundario = (isset($retribucion_encabezado[$id_usuario][$id_cobro]['secundario']) ?
								$retribucion_encabezado[$id_usuario][$id_cobro]['secundario'] : 0);

						unset($retribucion_encabezado[$id_usuario][$id_cobro]);
					}
					$monto_retribucion_abogados = $cobro['monto_trabajos'] * $porcentaje_intervencion * $trabajo['porcentaje_retribucion'] / (100 * 100);
					$valores = array("id_cobro" 			=> $id_cobro,
						"porcentaje_intervencion" 			=> $porcentaje_intervencion,
						"porcentaje_retribucion"				=> $trabajo['porcentaje_retribucion'],
						"horas_cobradas" 								=> $trabajo['minutos_cobrados']/60,
						"monto_cobrado" 								=> ($trabajo['monto_cobrado'] * $rentabilidad),
						"retribucion_socios" 						=> $cobro['monto_trabajos'] * $porcentaje_intervencion * $this->porcentaje_retribucion_socios / (100 * 100),
						"retribucion_abogados" 					=> $monto_retribucion_abogados,
						"retribucion_usuario_responsable"=> $monto_responsable,
						"retribucion_usuario_secundario"=> $monto_secundario,
						"simbolo"												=> $this->simbolo_moneda
					);
					$this->AcumulaDatos($id_usuario, $trabajo, $valores, $datos_usuario, $datos_subreporte);
				} else {
					if (isset($retribucion_encabezado[$id_usuario]) && !empty($retribucion_encabezado[$id_usuario])) {
						foreach ($retribucion_encabezado[$id_usuario] as $id_cobro => $responsables) {
							$valores = array("id_cobro" 			=> $id_cobro,
								"porcentaje_intervencion" 			=> 0,
								"porcentaje_retribucion"				=> $trabajo['porcentaje_retribucion'],
								"horas_cobradas" 								=> 0,
								"monto_cobrado" 								=> 0,
								"retribucion_socios" 						=> 0,
								"retribucion_abogados" 					=> 0,
								"retribucion_usuario_responsable"=> $responsables['responsable'],
								"retribucion_usuario_secundario"=> $responsables['secundario'],
								"simbolo"												=> $this->simbolo_moneda
							);
		 					$this->AcumulaDatos($id_usuario, $trabajo, $valores, $datos_usuario, $datos_subreporte);
						}
					} else {
						$this->AgregaDatosUsuario($id_usuario, $trabajo, $datos_usuario);
					}
				}
			}
			$datos_reporte[] = $datos_usuario;
		}
		$this->datos_reporte = $datos_reporte;
		$this->datos_subreporte = $datos_subreporte;

	}

	public function PreparaReporte($tipo_reporte){
		extract($this->filtros);
		$porcentaje_retribucion_socios = $this->porcentaje_retribucion_socios;

		$reporte = new SimpleReport($this->sesion);
		$reporte->LoadConfiguration('RETRIBUCIONES_RESUMEN_ENCABEZADO');
		if ($porcentaje_retribucion_socios>0) {
			$reporte->Config->columns['retribucion_socios']->Title("Aporte al Área $porcentaje_retribucion_socios%");
		} else {
			$reporte->Config->columns['retribucion_socios']->Visible(false);
		}

		$reporte->Config->columns['horas_cobradas']->Visible($tipo_calculo != 'monto_cobrado');
		$reporte->Config->columns['monto_cobrado']->Visible($tipo_calculo == 'monto_cobrado');

		$reporte->SetCustomFormat(array(
			'odd_color' => 'fff',
			'repeat_header_each_row' => ($tipo_reporte == 'Spreadsheet' && $incluir_detalle),
			'collapsible' => $incluir_detalle
		));

		$subreporte = new SimpleReport($this->sesion);
		$subreporte->LoadConfiguration('RETRIBUCIONES_RESUMEN_DETALLE');
		if ($porcentaje_retribucion_socios>0) {
			$subreporte->Config->columns['retribucion_socios']->Title("Aporte al Área $porcentaje_retribucion_socios%");
		} else {
			$subreporte->Config->columns['retribucion_socios']->Visible(false);
		}
		$subreporte->Config->columns['horas_cobradas']->Visible($tipo_calculo != 'monto_cobrado');
		$subreporte->Config->columns['monto_cobrado']->Visible($tipo_calculo == 'monto_cobrado');

		if(!UtilesApp::GetConf($this->sesion, 'EncargadoSecundario') || !$this->genera_secundario){
			$reporte->Config->columns['retribucion_usuario_secundario']->Visible(false);
			$subreporte->Config->columns['retribucion_usuario_secundario']->Visible(false);
		}
		if(!$this->genera_responsable){
			$reporte->Config->columns['retribucion_usuario_responsable']->Visible(false);
			$subreporte->Config->columns['retribucion_usuario_responsable']->Visible(false);
		}


		if ($incluir_detalle) {
			$subreporte->LoadResults($this->datos_subreporte);
			$reporte->AddSubReport(array(
				'SimpleReport' => $subreporte,
				'Keys' => array('id_usuario'),
				'Level' => 1
			));
		}

		$reporte->LoadResults($this->datos_reporte);
		return $reporte;
	}

	public function GeneraReporte($titulo, $filtros, $tipo_reporte) {
		require_once Conf::ServerDir() . '/classes/Reportes/SimpleReport.php';
		$this->filtros = $filtros;
		$this->ObtieneDatos();
		$reporte = $this->PreparaReporte($tipo_reporte);
		$writer = SimpleReport_IOFactory::createWriter($reporte, $tipo_reporte);
		return $writer->save($titulo);

	}
	public function DownloadExcel($titulo, $filtros = array()) {
		$this->GeneraReporte($titulo, $filtros,'Spreadsheet');
	}

	public function PrintHtml($titulo, $filtros = array()) {
		return $this->GeneraReporte($titulo, $filtros, 'Html');
	}


}