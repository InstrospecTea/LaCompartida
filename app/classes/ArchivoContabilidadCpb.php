<?php

class ArchivoContabilidadCpb {

	private static $Session;
	private static $areas_por_mostrar = array();
	private static $usuarios_por_mostrar = array();
	private static $mostrar_gastos = false;
	private static $num_mask = '%s%03d%07d';
	private static $date_format = '%d/%m/%Y';

	public static function Ofrece_Planilla_Registro_Ventas() {
		echo '<a class="btn botonizame" icon="ui-icon-invoice2" id="boton_registro_ventas" name="boton_registro_ventas"
			onclick="jQuery(\'#form_facturas\').attr(\'action\',\'facturas.php?opc=buscar&descargar_excel=1&planilla=registro_ventas\').submit();">' . __('Registro Ventas') . '</a>';
	}

	public static function Descarga_Planilla_Registro_Ventas() {
		if (empty($_GET['planilla']) || $_GET['planilla'] != 'registro_ventas') {
			return;
		}
		global $factura, $where, $numero, $fecha1, $fecha2,
		$tipo_documento_legal_buscado, $codigo_cliente, $codigo_cliente_secundario,
		$codigo_asunto, $codigo_asunto_secundario, $id_contrato, $id_cia,
		$id_cobro, $id_estado, $id_moneda, $grupo_ventas, $razon_social,
		$descripcion_factura, $serie, $desde_asiento_contable;

		self::$Session = new \TTB\Sesion();

		require_once Conf::ServerDir() . '/classes/Reportes/SimpleReport.php';
		$SimpleReport = new SimpleReport(self::$Session);

		if (empty($tipo_documento_legal_buscado)) {
			$query = 'SELECT GROUP_CONCAT(id_documento_legal SEPARATOR ",") ids FROM prm_documento_legal WHERE NOT codigo = "RG" ';
			$pdl = self::query($query)->fetch(PDO::FETCH_ASSOC);
			$tipo_documento_legal_buscado = explode(',', $pdl['ids']);
		}

		$results = $factura->DatosReporte(false, $where, $numero, $fecha1, $fecha2, $tipo_documento_legal_buscado, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario, $id_contrato, $id_cia, $id_cobro, $id_estado, $id_moneda, $grupo_ventas, $razon_social, $descripcion_factura, $serie, $desde_asiento_contable);

		$total_results = count($results);

		$documents = array();
		$report_data = array();

		/**
		 * 1.- Recorro para calcular montos y asignar otros valores.
		 */
		for ($key = 0; $key < $total_results; ++$key) {
			$result = self::addExtraData($results[$key]);
			$result['add_report'] = true;
			if ($result['tipo'] == 'NC') {
				$documents['nc'][] = $result;
			} else {
				$documents['no_nc'][$result['id']] = $result;
			}
		}

		unset($results);

		set_time_limit(0);

		/**
		 * 2.- Verifico que todas las NC tengas una Factura.
		 */
		$total_nc = count($documents['nc']);
		for ($key = 0; $key < $total_nc; ++$key) {
			$id = $documents['nc'][$key]['id_padre'];
			if ($documents['nc'][$key]['serie_documento_legal'] == '002') {
				$documents['nc'][$key]['aporte_gg'] = $documents['nc'][$key]['neto'];
				self::$mostrar_gastos = true;
			} else if (!isset($documents['no_nc'][$id])) {
				if ($id) {
					$documents['no_nc'][$id] = self::getFactura($id);
				}
			}
		}

		/**
		 * 3.- Recorro los documentos que no son NC para desglosar los Centros de costo.
		 */
		$total_no_nc = count($documents['no_nc']);
		$keys = array_keys($documents['no_nc']);
		for ($x = 0; $x < $total_no_nc; ++$x) {
			$key = $keys[$x];
			$document = $documents['no_nc'][$key];
			$documents['no_nc'][$key] = self::calcularAportes($document);
		}
		/**
		 * 4.- Recorro los documentos NC para descontar de las facturas.
		 */
		$total_nc = count($documents['nc']);
		for ($key = 0; $key < $total_nc; ++$key) {
			$id = $documents['nc'][$key]['id_padre'];
			$documents['nc'][$key] = self::calcularDescuentoFactura($documents['nc'][$key], $documents['no_nc'][$id]);
		}

		/**
		 * 5.- Mesclo no_nc y nc, si corresponde
		 */
		$total_no_nc = count($documents['no_nc']);
		$keys = array_keys($documents['no_nc']);
		for ($x = 0; $x < $total_no_nc; ++$x) {
			$key = $keys[$x];
			$document = $documents['no_nc'][$key];
			if ($document['add_report']) {
				unset($document['aportes']);
				$report_data[] = $document;
			}
		}
		$total_nc = count($documents['nc']);
		for ($key = 0; $key < $total_nc; ++$key) {
			$document = $documents['nc'][$key];
			unset($document['aportes']);
			$report_data[] = $document;
		}

		unset($documents);
		$report_data = array_filter($report_data);
		usort($report_data, array('self', 'ordenaReporte'));

		$cofiguracion_mini = self::getConfiguracion($no_asignado);

		$extras = array(
			'width' => 15,
			'subtotal' => false,
			'symbol' => 'simbolo',
			'decimals' => 'cifras_decimales'
		);

		if (self::$mostrar_gastos) {
			$cofiguracion_mini[] = array(
				'field' => 'aporte_gg',
				'title' => 'Centro de Costo - Gastos',
				'format' => 'number',
				'extras' => $extras
			);
		}

		if (!empty(self::$usuarios_por_mostrar)) {
			$r_areas = self::getUsuarios();
			$k_areas = array();
			foreach ($r_areas as $area) {
				$k_areas[$area['id']] = $area['glosa'];
			}
			foreach ($k_areas as $id => $area) {
				$cofiguracion_mini[] = array(
					'field' => "aporte_usuario_{$id}",
					'title' => $area,
					'format' => 'number',
					'extras' => $extras
				);
			}
		}

		if (!empty(self::$areas_por_mostrar)) {
			$r_areas = self::getAreas();

			$k_areas = array();
			foreach ($r_areas as $area) {
				$k_areas[$area['id']] = $area['glosa'];
			}
			foreach ($k_areas as $id => $area) {
				$cofiguracion_mini[] = array(
					'field' => "aporte_area_{$id}",
					'title' => $area,
					'format' => 'number',
					'extras' => $extras
				);
			}
		}

		$SimpleReport->LoadConfigFromArray($cofiguracion_mini);

		$SimpleReport->LoadResults($report_data);
		$SimpleReport->Config->SetTitle(strtoupper(strftime('%B', strtotime($report_data[1]['fecha']))));
		$writer = SimpleReport_IOFactory::createWriter($SimpleReport, 'Spreadsheet');
		$writer->save(__('Registro_Ventas'));
		exit;
	}

	private static function ordenaReporte($filaA, $filaB) {
		if ($filaA['codigodocumento'] == $filaB['codigodocumento']) {
			if ($filaA['serie_documento_legal'] == $filaB['serie_documento_legal']) {
				if ($filaA['fechax'] == $filaB['fechax']) {
					return ($filaA['numero'] > $filaB['numero']) ? +1 : -1;
				} else {
					return strcmp($filaA['fechax'], $filaB['fechax']);
				}
			} else {
				return ($filaA['serie_documento_legal'] > $filaB['serie_documento_legal']) ? +1 : -1;
			}
		} else {
			return strcmp($filaA['codigodocumento'], $filaB['codigodocumento']);
		}
	}



	private static function sumar($lo, $ro, $round = 8) {
		$ret = round($lo + $ro, $round);
		return floatval($ret);
	}

	private static function restar($lo, $ro, $round = 8) {
		$ret = round($lo - $ro, $round);
		return floatval($ret);
	}

	private static function multiplicar($lo, $ro, $round = 8) {
		$ret = round($lo * $ro, $round);
		return floatval($ret);
	}

	private static function dividir($lo, $ro, $round = 8) {
		$ret = round($lo / $ro, $round);
		return floatval($ret);
	}

	private static function query($query) {
		$stmt = self::$Session->pdodbh->prepare($query);
		$stmt->execute();
		return $stmt;
	}

	private static function getConfiguracion($no_asignado) {
		$extras = array(
			'width' => 15,
			'subtotal' => false,
			'symbol' => 'simbolo',
			'decimals' => 'cifras_decimales'
		);
		$cofiguracion_mini = array(
			array(
				'field' => 'numero',
				'title' => 'Correlativo',
				'format' => 'text',
				'extras' => array('width' => 10)
			),
			array(
				'field' => 'fechax',
				'title' => 'Fecha',
				'format' => 'text',
				'extras' => array('width' => 10)
			),
			array(
				'field' => 'serie_documento_legal',
				'title' => 'Serie',
				'format' => 'text',
				'extras' => array('width' => 6)
			),
			array(
				'field' => 'codigodocumento',
				'title' => 'Documento',
				'extras' => array('width' => 15)
			),
			array(
				'field' => 'tipo_doc',
				'title' => 'Tipo Doc.',
				'extras' => array('width' => 9)
			),
			array(
				'field' => 'RUT_cliente',
				'title' => 'Identidad',
				'extras' => array('width' => 12)
			),
			array(
				'field' => 'factura_rsocial',
				'title' => 'Razon Social',
				'extras' => array('width' => 30)
			),
			array(
				'field' => 'neto',
				'title' => 'Neto',
				'format' => 'number',
				'extras' => $extras
			),
			array(
				'field' => 'importe',
				'title' => 'Importe',
				'format' => 'number',
				'extras' => $extras
			),
			array(
				'field' => 'iva',
				'title' => __('IVA'),
				'format' => 'number',
				'extras' => $extras
			),
			array(
				'field' => 'servicio',
				'title' => 'Servicio',
				'format' => 'number',
				'extras' => $extras
			),
			array(
				'field' => 'otros',
				'title' => 'Otro',
				'format' => 'number',
				'extras' => $extras
			),
			array(
				'field' => 'total',
				'title' => 'Total',
				'format' => 'number',
				'extras' => $extras
			),
			array(
				'field' => 'descrec',
				'title' => 'Desc/Rec',
				'extras' => array('width' => 10)
			),
			array(
				'field' => 'observaciones',
				'title' => 'Observaciones',
				'extras' => array('width' => 15)
			),
			array(
				'field' => 'estado',
				'title' => 'Estado',
				'extras' => array('width' => 15)
			),
			array(
				'field' => 'moneda',
				'title' => 'Moneda',
				'extras' => array('width' => 8)
			),
			array(
				'field' => 'tipo_cambio',
				'title' => 'Tipo cambio',
				'extras' => array('width' => 13)
			),
			array('field' => 'banco', 'title' => 'banco', 'extras' => array('width' => 10)),
			array('field' => 'No Operaci?n', 'title' => 'No Operaci?n', 'extras' => array('width' => 10)),
			array('field' => 'fecha2', 'title' => 'fecha', 'extras' => array('width' => 10)),
			array('field' => 'factura_padre_fecha', 'title' => 'Fecha Factura Ref', 'extras' => array('width' => 10)),
			array('field' => 'factura_padre_numero', 'title' => 'N?mero Factura Ref', 'extras' => array('width' => 10)),
			array('field' => 'factura_padre_tipo_cambio', 'title' => 'Tipo Cambio Ref', 'extras' => array('width' => 10)),
			array('field' => 'porcentaje_impuesto', 'title' => 'Porc. ' . __('IVA'), 'extras' => array('width' => 10)),
		);

		if ($no_asignado) {
			$cofiguracion_mini[] = array(
				'field' => 'no_asignado',
				'title' => 'No Asignado',
				'format' => 'number',
				'extras' => $extras
			);
		}

		return $cofiguracion_mini;
	}

	private static function getUsuariosCobro($id_cobro) {
		$query_usuarios = "SELECT DISTINCT
								U.id_usuario id,
								concat_ws(' ', U.nombre, U.apellido1) nombre,
								T.tarifa_hh tarifa,
								T.id_moneda,
								IFNULL(AU.id, 0) id_area,
								IFNULL(AU.id_padre, 0) id_area_padre,
								ROUND(SUM(TIME_TO_SEC(T.duracion_cobrada)) / 60, 0) trabajo_duracion_minutos,
								ROUND(SUM(TIME_TO_SEC(T.duracion_retainer)) / 60, 0) trabajo_duracion_minutos_retainer,
								ROUND(CB.total_minutos / 60, 6) cobro_duracion,
								CB.total_minutos cobro_duracion_minutos,
								CB.forma_cobro,
								ROUND(CB.retainer_horas * 60, 0) retainer_minutos,
								CB.retainer_horas,
								CB.monto_contrato contrato_monto,
								IF(
									CB.tipo_descuento = 'VALOR',
									IF(
										CB.forma_cobro = 'RETAINER' OR CB.forma_cobro = 'PROPORCIONAL',
										(CB.descuento / (CB.monto_subtotal - CB.monto_contrato)) * 100,
										(CB.descuento / CB.monto_subtotal) * 100
									),
									CB.porcentaje_descuento
								) AS descuento,
								D.subtotal_sin_descuento monto_cobro
							FROM cobro CB
								INNER JOIN documento D ON D.id_cobro = CB.id_cobro AND D.tipo_doc = 'N'
								INNER JOIN trabajo T ON CB.id_cobro = T.id_cobro
								INNER JOIN usuario U ON U.id_usuario = T.id_usuario
								LEFT JOIN prm_area_usuario AU ON AU.id = U.id_area_usuario
							WHERE
								CB.id_cobro = {$id_cobro}
								AND T.cobrable = 1
							GROUP BY U.id_usuario, U.id_area_usuario";

		return self::query($query_usuarios);
	}

	private static function getAreas() {
		$areas = implode(',', array_filter(array_unique(self::$areas_por_mostrar)));
		$query_areas = "SELECT
					area_hijo.id,
					IF(area_padre.id IS NULL, area_hijo.glosa, CONCAT(area_padre.glosa, ' - ', area_hijo.glosa)) AS glosa
				FROM prm_area_usuario area_hijo
				LEFT JOIN prm_area_usuario area_padre ON area_padre.id = area_hijo.id_padre
				WHERE area_hijo.id IN ($areas)
				ORDER BY area_padre.glosa ASC, area_hijo.glosa ASC";

		return self::query($query_areas)->fetchAll(PDO::FETCH_ASSOC);
	}

	private static function getUsuarios() {
		$usuarios_por_mostrar = implode(',', array_filter(array_unique(self::$usuarios_por_mostrar)));
		$query_usuarios = "SELECT id_usuario id, CONCAT(nombre, ' ', apellido1, ' ', apellido2) glosa
								FROM usuario
								WHERE id_usuario IN ($usuarios_por_mostrar)";
		return self::query($query_usuarios)->fetchAll(PDO::FETCH_ASSOC);
	}

	private static function calcularAportes($document) {
		$neto = $document['neto'];
		if ($document['codigo_estado'] == 'A' || empty($neto)) {
			return $document;
		}
		if ($document['serie_documento_legal'] == '002') {
			$document['aporte_gg'] = $neto;
			self::$mostrar_gastos = true;
			return $document;
		}
		// Calcular el peso de los usuarios que participaron en el cobro
		$socios = array();
		$usuarios_cobro = self::getUsuariosCobro($document['id_cobro']);
		$trabajos_en_retainer = false;
		while ($r_peso = $usuarios_cobro->fetch(PDO::FETCH_ASSOC)) {
			switch ($r_peso['forma_cobro']) {
				case 'RETAINER':
					if ($r_peso['contrato_monto'] == $neto) {
						$r_peso['trabajo_duracion'] = $r_peso['trabajo_duracion_minutos_retainer'];
					} else {
						$r_peso['trabajo_duracion'] = self::restar(
							$r_peso['trabajo_duracion_minutos'],
							$r_peso['trabajo_duracion_minutos_retainer']
						);
					}

					$as_flat_fee = (($r_peso['cobro_duracion_minutos'] < $r_peso['retainer_minutos']) && $r_peso['contrato_monto'] == $neto);
					if ($as_flat_fee) {
						$r_peso['tarifa'] = self::dividir($r_peso['contrato_monto'], $r_peso['cobro_duracion']);
					} else if ($r_peso['contrato_monto'] == $neto) {
						$trabajos_en_retainer = true;
						$r_peso['tarifa'] = self::dividir($r_peso['contrato_monto'], $r_peso['retainer_horas']);
					}
					break;
				case 'FLAT FEE':
					$r_peso['tarifa'] = self::dividir($r_peso['contrato_monto'], $r_peso['cobro_duracion']);
				default:
					$r_peso['trabajo_duracion'] = $r_peso['trabajo_duracion_minutos'];
			}

			if (empty($document['forma_cobro'])) {
				$document['forma_cobro'] = $r_peso['forma_cobro'];
			}

			$r_peso['valor_trabajo'] = self::multiplicar(
				$r_peso['tarifa'],
				self::dividir($r_peso['trabajo_duracion'], 60)
			);

			if (!empty($r_peso['descuento'])) {
				$porcentaje_descuento = self::dividir($r_peso['descuento'], 100);
				$r_peso['valor_trabajo'] = self::restar(
					$r_peso['valor_trabajo'],
					self::multiplicar($r_peso['valor_trabajo'], $porcentaje_descuento)
				);
			}

			$id_socio = empty($r_peso['id_area_padre']) ? $r_peso['id_area'] : $r_peso['id_area_padre'];
			if (!isset($socios[$id_socio])) {
				$socios[$id_socio] = array(
					'total_minutos' => 0,
					'total_minutos_sin_socio' => 0,
					'valor_trabajo' => 0,
					'trabajos' => array()
				);
			}
			if ($id_socio == $r_peso['id_area']) {
				$socios[$id_socio]['valor_trabajo'] = $r_peso['valor_trabajo'];
			} else if ($id_socio == $r_peso['id_area_padre'] && $r_peso['trabajo_duracion'] > 0) {
				$socios[$id_socio]['trabajos'][] = $r_peso;

				$socios[$id_socio]['total_minutos_sin_socio'] = self::sumar(
					$socios[$id_socio]['total_minutos_sin_socio'],
					$r_peso['trabajo_duracion']
				);
			}

			$socios[$id_socio]['total_minutos'] = self::sumar(
				$socios[$id_socio]['total_minutos'],
				$r_peso['trabajo_duracion']
			);
		}

		$total = $neto;
		$areas = array(
			'a' => array(),
			'u' => array()
		);
		foreach ($socios as $id => $socio) {
			if (empty($socio['trabajos'])) {
				continue;
			}
			foreach ($socio['trabajos'] as $r_peso) {
				if (empty($r_peso['trabajo_duracion'])) {
					continue;
				}

				$r_peso['porc_horas'] = self::dividir(
					$r_peso['trabajo_duracion'],
					$socio['total_minutos_sin_socio']
				);

				if ($r_peso['forma_cobro'] == 'FLAT FEE') {
					$horas_con_socio = self::dividir(
						self::multiplicar($socio['total_minutos'], $r_peso['porc_horas']),
						60
					);
					$aporte_area = self::multiplicar($horas_con_socio, $r_peso['tarifa']);
				} else {
					$aporte_socio = self::multiplicar(
						$socio['valor_trabajo'],
						$r_peso['porc_horas']
					);
					$aporte_area = self::sumar($r_peso['valor_trabajo'], $aporte_socio);
				}

				$as_flat_fee = (($r_peso['cobro_duracion_minutos'] < $r_peso['retainer_minutos']) && $r_peso['contrato_monto'] == $neto);
				if ($r_peso['forma_cobro'] == 'RETAINER') {
					if ($trabajos_en_retainer || $as_flat_fee) {
						$porcentaje_factura_cobro = 1;
					} else {
						$cobro_sin_retainer = self::restar($r_peso['monto_cobro'], $r_peso['contrato_monto']);
						$porcentaje_factura_cobro = self::dividir($document['neto'], $cobro_sin_retainer);
					}
				} else {
					$porcentaje_factura_cobro = self::dividir($document['neto'], $r_peso['monto_cobro']);
				}
				$aporte_area = self::multiplicar($porcentaje_factura_cobro, $aporte_area);

				$total = self::restar($total, $aporte_area);
				$id_area = $r_peso['id_area'];
				if (isset($areas['a'][$id_area])) {
					$areas['a'][$id_area] = self::sumar($areas['a'][$id_area], $aporte_area);
				} else {
					$areas['a'][$id_area] = $aporte_area;
				}
			}
		}

		if (round($total, 2) > 0) {
			$query_contrato = "SELECT C.id_usuario_secundario FROM contrato C WHERE C.id_contrato = {$document['idcontrato']}";
			$cc_contrato = self::query($query_contrato)->fetch(PDO::FETCH_ASSOC);
			$id_usuario = empty($cc_contrato['id_usuario_secundario']) ? 'no_asignado' : $cc_contrato['id_usuario_secundario'];
			if (isset($areas['u'][$id_usuario])) {
				$areas['u'][$id_usuario] = self::sumar($areas['u'][$id_usuario], $total);
			} else {
				$areas['u'][$id_usuario] = $total;
			}
		} else if (round($total, 2) < 0) {
			// Correcci?n decimal por redondeo
			reset($areas['a']);
			$count = self::dividir(abs($total), 0.01);
			for ($x = 0; $x < $count; ++$x) {
				$k = key($areas['a']);
				$areas['a'][$k] = self::restar($areas['a'][$k], 0.01);
				next($areas['a']);
			}
		}

		foreach ($areas['a'] as $area => $valor) {
			if (!empty($valor)) {
				$document["aporte_area_{$area}"] = round($valor, 2);
				$document['aportes']["aporte_area_{$area}"] = $document["aporte_area_{$area}"];
				self::$areas_por_mostrar[] = $area;
			}
		}

		foreach ($areas['u'] as $usuario => $valor) {
			if (!empty($valor)) {
				if ($usuario == 'no_asignado') {
					$document['no_asignado'] = round($valor, 2);
					$no_asignado = true;
				} else {
					$document["aporte_usuario_{$usuario}"] = round($valor, 2);
					$document['aportes']["aporte_usuario_{$usuario}"] = $document["aporte_usuario_{$usuario}"];
					if ($document["aporte_usuario_{$usuario}"] > 0) {
						self::$usuarios_por_mostrar[] = $usuario;
					}
				}
			}
		}
		return $document;
	}

	private static function calcularDescuentoFactura($nc, $factura) {
		$factor = self::dividir($nc['neto'], $factura['neto']);
		foreach ($factura['aportes'] as $key => $aporte) {
			$nc[$key] = self::multiplicar($aporte, $factor);
		}
		return $nc;
	}

	private static function getFactura($key_factura) {
		global $factura;
		list($tipo, $serie, $numero) = explode('_', $key_factura);
		if (!empty($tipo)) {
			$tipo = self::getType($tipo, true);
			$query = "SELECT id_documento_legal FROM prm_documento_legal WHERE codigo = '{$tipo}'";
			$pdl = self::query($query)->fetch(PDO::FETCH_ASSOC);
		}

		$results = $factura->DatosReporte(false, '', $numero, null, null, $pdl['id_documento_legal'], null, null, null, null, null, null, null, null, null, null, null, null, $serie, null);
		return self::addExtraData($results[0]);
	}

	private static function addExtraData($result) {
		$tipo_doc = self::getType($result['tipo']);
		$serie = $result['serie_documento_legal'];
		$result['id'] = sprintf('%s_%s_%s', $tipo_doc, $result['serie_documento_legal'], $result['numero']);
		$result['numero'] = sprintf('%1$08d', $result['numero']);
		$result['descrec'] = 0;
		$result['servicio'] = 0;
		$result['otros'] = '0';
		$result['estado'] = strtoupper($result['estado']);
		$result['fechax'] = strftime(self::$date_format, strtotime($result['fecha']));
		$result['codigodocumento'] = sprintf(self::$num_mask, $tipo_doc, $serie, $result['numero']);
		$result['serie_documento_legal'] = str_pad($serie, 3, '0', STR_PAD_LEFT);
		$result['tipo_doc'] = 6;
		$result['moneda'] = str_replace(array('1', '2', '3'), array('PEN', 'DOL', 'EUR'), $result['id_moneda']);
		$result['RUT_cliente'] = (!empty($result['RUT_cliente']) && substr($result['RUT_cliente'], 0, 7) != '0000000') ? $result['RUT_cliente'] : 'AUTO';
		$result['factura_rsocial'] = $result['factura_rsocial'];
		$result['tipo_cambio'] = round($result['tipo_cambio_factura'], 4);
		$result['aportes'] = array();

		if ($result['codigo_estado'] == 'A') {
			$result['neto'] = 0;
			$result['iva'] = 0;
			$result['total'] = 0;
			$result['importe'] = 0;
			return $result;
		}

		$result['neto'] = self::sumar(self::sumar($result['honorarios'], $result['subtotal_gastos'], 2), $result['subtotal_gastos_sin_impuesto'], 2);
		$result['total'] = self::sumar($result['neto'], $result['iva'], 2);
		if ($result['tipo'] == 'NC') {
			$tipo_doc_padre = self::getType($result['factura_padre_codigo']);
			$result['id_padre'] = sprintf('%s_%s_%s', $tipo_doc_padre, $result['factura_padre_serie'], $result['factura_padre_numero']);
			$result['factura_padre_fecha'] = strftime(self::$date_format, strtotime($result['factura_padre_fecha']));
			$result['factura_padre_numero'] = sprintf(self::$num_mask, $tipo_doc_padre, $result['factura_padre_serie'], $result['factura_padre_numero']);
			$result['factura_padre_tipo_cambio'] = round($result['factura_padre_tipo_cambio'], 4);
		}
		return $result;
	}

	private static function getType($type, $reverse = false) {
		$types = array(
			'FA' => 'FAC',
			'BO' => 'BOL',
			'NC' => 'NAB',
			'ND' => 'NDB'
		);

		if ($reverse) {
			return array_search($type, $types);
		} else {
			return $types[$type];
		}
	}

}
