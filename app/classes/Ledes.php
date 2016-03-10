<?php

require_once dirname(__FILE__) . '/../conf.php';

class Ledes extends Objeto {

	/**
	 * Número de decimales a mostrar.
	 * @var int
	 */
	protected $decimales = 4;

	/**
	 * @param $Sesion
	 */
	function __construct($Sesion) {
		$this->sesion = $Sesion;
	}

	/**
	 * genera un archivo ledes para uno o mas cobros
	 * @param mixed $ids_cobros id del cobro o array de ids
	 * @return string contenido del archivo
	 */
	public function ExportarCobrosLedes($ids_cobros) {
		$datos = array();
		if (!is_array($ids_cobros)) {
			$ids_cobros = array($ids_cobros);
		}
		foreach ($ids_cobros as $id_cobro) {
			$datos += $this->CobroLedes($id_cobro);
		}
		return $this->GenerarArchivoLedes($datos);
	}

	/**
	 * genera los datos de un cobro para rellenar el archivo ledes
	 * todo: revisar ajustes por item segun forma de cobro, rellenar campos raros
	 * @param int $id_cobro
	 * @return array datos
	 */
	public function CobroLedes($id_cobro) {
		$filas = array();

		//cargar datos de la bd
		$Cobro = new Cobro($this->sesion);
		$Cobro->Load($id_cobro);

		$x_resultados = UtilesApp::ProcesaCobroIdMoneda($this->sesion, $id_cobro);
		$gastos = UtilesApp::ProcesaGastosCobro($this->sesion, $id_cobro);

		$moneda_cobro = $x_resultados['id_moneda'];
		$cambios = $x_resultados['tipo_cambio'];
		foreach ($cambios as $id_moneda => $tipo_cambio) {
			$cambios[$id_moneda] = $tipo_cambio / $x_resultados['tipo_cambio'][$moneda_cobro];
		}

		$suma = 0;
		$fecha_min = date('Y-m-d');
		$codigo_asunto = null;
		$last_client_matter_id = "";
		$suma_unit = 0;
		$linea = 1;

		$filas_trabajos = $this->generarFilasTrabajos($Cobro, $linea, $fecha_min, $cambios, $codigo_asunto, $last_client_matter_id, $suma, $suma_unit);
		$filas = array_merge($filas, $filas_trabajos['filas']);

		$filas_tramites = $this->generarFilasTramites($Cobro, $linea, $fecha_min, $cambios, $codigo_asunto, $last_client_matter_id, $suma, $suma_unit, $filas_trabajos['trabajo']);
		$filas = array_merge($filas, $filas_tramites);

		$filas_gastos = $this->generarFilasGastos($Cobro, $linea, $fecha_min, $cambios, $codigo_asunto, $last_client_matter_id, $suma, $suma_unit, $gastos);
		$filas = array_merge($filas, $filas_gastos);

		if (!$codigo_asunto) {
			$query = "SELECT codigo_asunto FROM asunto WHERE id_contrato = " . $Cobro->fields['id_contrato'] . " LIMIT 1";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($codigo_asunto) = mysql_fetch_assoc($resp);
		}

		if (empty($last_client_matter_id)) {
			$Cobro->LoadAsuntos();
			$codigo_asunto = $Cobro->asuntos[0];

			$Asunto = new Asunto($this->sesion);
			$Asunto->LoadByCodigo($codigo_asunto);

			$last_client_matter_id = $Asunto->fields['codigo_homologacion'];
		}

		//Ajustes del cobro
		$filas_ajuste = $this->ajustesCobro($Cobro, $x_resultados, $moneda_cobro, $fecha_min, $codigo_asunto, $last_client_matter_id, $suma, $suma_unit);
		$filas = array_merge($filas, $filas_ajuste['filas']);
		$datos_cobro = $filas_ajuste['datos_cobro'];

		foreach ($filas as $k => $fila) {
			// Será así?
			$fila['LAW_FIRM_MATTER_ID'] = $datos_cobro['LAW_FIRM_ID'];

			$filas[$k] = $datos_cobro + $fila;
		}

		return $filas;
	}

	/**
	 * Genera la fila de ajustes a nivel de cobro para corregir segun forma de cobro, descuentos, etc
	 * @param $Cobro
	 * @param $x_resultados
	 * @param $moneda_cobro
	 * @param $fecha_min
	 * @param $codigo_asunto
	 * @param $last_client_matter_id
	 * @param $suma
	 * @param $suma_unit
	 * @return array
	 * @internal param $datos_cobro
	 */
	public function ajustesCobro(&$Cobro, &$x_resultados, &$moneda_cobro, $fecha_min, $codigo_asunto, &$last_client_matter_id, &$suma, &$suma_unit) {
		$id_cobro = $Cobro->fields['id_cobro'];
		//Obtener los datos de la liquidación
		// Obtengo el código secundario del cliente
		$Cliente = new Cliente($this->sesion);
		$Cliente->LoadByCodigo($Cobro->fields['codigo_cliente']);

		$datos_cobro = array(
			'INVOICE_DATE' => $Cobro->fields['fecha_emision'],
			'INVOICE_NUMBER' => $id_cobro,
			'CLIENT_ID' => $Cliente->fields['codigo_homologacion'],
			'INVOICE_TOTAL' => $x_resultados['monto_total_cobro'][$moneda_cobro],
			'BILLING_START_DATE' => $Cobro->fields['fecha_ini'],
			'BILLING_END_DATE' => $Cobro->fields['fecha_fin'],
			'INVOICE_DESCRIPTION' => $Cobro->fields['se_esta_cobrando'],
			'LAW_FIRM_ID' => UtilesApp::GetConf($this->sesion, 'IdentificadorEstudio')
		);
		if ($datos_cobro['BILLING_START_DATE'] < '2000-01-01') {
			$datos_cobro['BILLING_START_DATE'] = $fecha_min;
		}
		$monto_total = $datos_cobro['INVOICE_TOTAL'] * 1;
		$filas = array();
		if (abs($suma - $monto_total) > $monto_total / 100) {
			$monto = $monto_total - $suma;

			$fila = array(
				'LAW_FIRM_MATTER_ID' => $codigo_asunto,
				'LINE_ITEM_NUMBER' => 'IF1',
				'EXP/FEE/INV_ADJ_TYPE' => 'IF',
				'LINE_ITEM_NUMBER_OF_UNITS' => 0,
				'LINE_ITEM_ADJUSTMENT_AMOUNT' => $monto,
				'LINE_ITEM_TOTAL' => $monto,
				'LINE_ITEM_DATE' => $datos_cobro['INVOICE_DATE'],
				'LINE_ITEM_TASK_CODE' => '',
				'LINE_ITEM_EXPENSE_CODE' => '',
				'LINE_ITEM_ACTIVITY_CODE' => '',
				'TIMEKEEPER_ID' => '',
				'LINE_ITEM_DESCRIPTION' => 'Ajuste',
				'LINE_ITEM_UNIT_COST' => 0,
				'TIMEKEEPER_NAME' => '',
				'TIMEKEEPER_CLASSIFICATION' => '',
				'CLIENT_MATTER_ID' => $last_client_matter_id
			);

			$suma += $fila['LINE_ITEM_TOTAL'];

			$suma_unit += $fila['LINE_ITEM_UNIT_COST'];

			$filas[] = $fila;
		}
		return array('filas' => $filas, 'datos_cobro' => $datos_cobro);
	}

	/**
	 * Genera las líneas correspondientes a los trabajos del cobro
	 * @param $Cobro
	 * @param $linea
	 * @param $fecha_min
	 * @param $cambios
	 * @param $codigo_asunto
	 * @param $last_client_matter_id
	 * @param $suma
	 * @param $suma_unit
	 * @return array
	 */
	public function generarFilasTrabajos(&$Cobro, &$linea, $fecha_min, $cambios, &$codigo_asunto, &$last_client_matter_id, &$suma, &$suma_unit) {

		$id_cobro = $Cobro->fields['id_cobro'];
		$filas = array();
		/**
		 * Obtener los trabajos
		 */

		$Criteria = new Criteria($this->sesion);
		$trabajos = $Criteria->add_select('trabajo.id_trabajo')
			->add_select('trabajo.codigo_asunto')
			->add_select('trabajo.monto_cobrado')
			->add_select('TIME_TO_SEC(trabajo.duracion_cobrada)/3600', 'horas')
			->add_select('trabajo.tarifa_hh')
			->add_select('trabajo.fecha')
			->add_select('trabajo.id_usuario')
			->add_select('trabajo.descripcion')
			->add_select('trabajo.cobrable')
			->add_select('trabajo.id_moneda')
			->add_select("CONCAT(usuario.apellido1, ', ', usuario.nombre)", 'nombre_usuario')
			->add_select('usuario.username')
			->add_select('prm_categoria_usuario.codigo_categoria')
			->add_select('trabajo.codigo_actividad')
			->add_select('trabajo.codigo_tarea')
			->add_select('asunto.codigo_homologacion')
			->add_from('trabajo')
			->add_left_join_with('usuario', 'trabajo.id_usuario = usuario.id_usuario')
			->add_left_join_with('prm_categoria_usuario', 'usuario.id_categoria_usuario = prm_categoria_usuario.id_categoria_usuario')
			->add_left_join_with('asunto', 'trabajo.codigo_asunto = asunto.codigo_asunto')
			->add_restriction(CriteriaRestriction::equals('trabajo.id_cobro', $id_cobro))
			->add_restriction(CriteriaRestriction::equals('trabajo.id_tramite', 0))
			->run();

		foreach ($trabajos as $trabajo) {
			if ($Cobro->fields['forma_cobro'] != 'FLAT FEE' && $this->round($trabajo['horas']) == 0) {
				continue;
			}
			if ($fecha_min > $trabajo['fecha']) {
				$fecha_min = $trabajo['fecha'];
			}
			if (!$codigo_asunto) {
				$codigo_asunto = $trabajo['codigo_asunto'];
			}

			$monto = $trabajo['cobrable'] == '1' && !empty($trabajo['monto_cobrado']) ? $trabajo['monto_cobrado'] : 0;
			$monto *= $cambios[$trabajo['id_moneda']];
			$tarifa = $trabajo['tarifa_hh'] * $cambios[$trabajo['id_moneda']];

			/**
			 * redondeo decimales ahora para que calcen los ajustes
			 */
			$horas = $this->round($trabajo['horas']);
			$monto = $this->round($monto);
			$tarifa = $this->round($tarifa);

			if ($Cobro->fields['forma_cobro'] == 'FLAT FEE') {
				$tarifa = 0;
			}
			$ajuste = ($monto != 0) ? ($monto - $tarifa * $horas) : 0;

			$descripcion = trim(str_replace("\n", ' ', $trabajo['descripcion']));

			$fila = array(
				'LAW_FIRM_MATTER_ID' => $trabajo['codigo_asunto'],
				'LINE_ITEM_NUMBER' => $linea++, //'H' . $trabajo['id_trabajo'],
				'EXP/FEE/INV_ADJ_TYPE' => 'F',
				'LINE_ITEM_NUMBER_OF_UNITS' => $horas,
				'LINE_ITEM_ADJUSTMENT_AMOUNT' => $ajuste,
				'LINE_ITEM_TOTAL' => $monto,
				'LINE_ITEM_DATE' => $trabajo['fecha'],
				'LINE_ITEM_TASK_CODE' => $trabajo['codigo_tarea'],
				'LINE_ITEM_EXPENSE_CODE' => '',
				'LINE_ITEM_ACTIVITY_CODE' => $trabajo['codigo_actividad'],
				'TIMEKEEPER_ID' => $trabajo['username'],
				'LINE_ITEM_DESCRIPTION' => $descripcion,
				'LINE_ITEM_UNIT_COST' => $tarifa,
				'TIMEKEEPER_NAME' => $trabajo['nombre_usuario'],
				'TIMEKEEPER_CLASSIFICATION' => $trabajo['codigo_categoria'],
				'CLIENT_MATTER_ID' => $trabajo['codigo_homologacion']
			);

			$suma += $fila['LINE_ITEM_TOTAL'];

			$suma_unit += $fila['LINE_ITEM_UNIT_COST'];

			$filas[] = $fila;

			$last_client_matter_id = $trabajo['codigo_homologacion'];
		}
		return array('filas' => $filas, 'trabajo' => $trabajo);
	}

	/**
	 * Genera las líneas correspondientes a los trámites del cobro
	 * @param $Cobro
	 * @param $linea
	 * @param $fecha_min
	 * @param $cambios
	 * @param $codigo_asunto
	 * @param $last_client_matter_id
	 * @param $suma
	 * @param $suma_unit
	 * @param $trabajo
	 * @return array
	 */
	public function generarFilasTramites(&$Cobro, &$linea, $fecha_min, $cambios, &$codigo_asunto, &$last_client_matter_id, &$suma, &$suma_unit, $trabajo) {

		$id_cobro = $Cobro->fields['id_cobro'];
		$filas = array();
		/**
		 * Obtener los trámites
		 */

		$Criteria = new Criteria($this->sesion);
		$tramites = $Criteria->add_select('tramite.id_tramite')
			->add_select('tramite.codigo_asunto')
			->add_select('tramite.tarifa_tramite')
			->add_select('TIME_TO_SEC(tramite.duracion)/3600', 'horas')
			->add_select('tramite.fecha')
			->add_select('tramite.id_usuario')
			->add_select('tramite.descripcion')
			->add_select('tramite.id_moneda_tramite')
			->add_select("CONCAT(usuario.apellido1, ', ', usuario.nombre)", 'nombre_usuario')
			->add_select('usuario.username')
			->add_select('prm_categoria_usuario.codigo_categoria')
			->add_select('tramite.codigo_actividad')
			->add_select('tramite.codigo_tarea')
			->add_select('asunto.codigo_homologacion')
			->add_select('tramite.cobrable')
			->add_from('tramite')
			->add_left_join_with('usuario', 'tramite.id_usuario = usuario.id_usuario')
			->add_left_join_with('prm_categoria_usuario', 'prm_categoria_usuario.id_categoria_usuario = usuario.id_categoria_usuario')
			->add_left_join_with('asunto', 'tramite.codigo_asunto = asunto.codigo_asunto')
			->add_restriction(CriteriaRestriction::equals('tramite.id_cobro', $id_cobro))
			->run();

		foreach ($tramites as $tramite) {
			if ($fecha_min > $tramite['fecha']) {
				$fecha_min = $tramite['fecha'];
			}
			if (!$codigo_asunto) {
				$codigo_asunto = $tramite['codigo_asunto'];
			}

			$horas = $tramite['horas'] > 0 ? $tramite['horas'] : 1;
			$monto = $tramite['cobrable'] == '1' && !empty($tramite['tarifa_tramite']) ? $tramite['tarifa_tramite'] : 0;
			$monto *= $cambios[$tramite['id_moneda_tramite']];
			$tarifa = $tramite['tarifa_tramite'] * $cambios[$tramite['id_moneda_tramite']];

			/**
			 * redondeo decimales ahora para que calcen los ajustes
			 */
			$horas = $this->round($horas);
			$monto = $this->round($monto);
			$tarifa = $this->round($tarifa / $horas);
			$ajuste = ($monto != 0) ? ($monto - $tarifa * $horas) : 0;

			$descripcion = trim(str_replace("\n", ' ', $tramite['descripcion']));

			$fila = array(
				'LAW_FIRM_MATTER_ID' => $tramite['codigo_asunto'],
				'LINE_ITEM_NUMBER' => $linea++, //'T' . $tramite['id_tramite'],
				'EXP/FEE/INV_ADJ_TYPE' => 'F',
				'LINE_ITEM_NUMBER_OF_UNITS' => $horas,
				'LINE_ITEM_ADJUSTMENT_AMOUNT' => $ajuste,
				'LINE_ITEM_TOTAL' => $monto,
				'LINE_ITEM_DATE' => $tramite['fecha'],
				'LINE_ITEM_TASK_CODE' => $trabajo['codigo_tarea'],
				'LINE_ITEM_EXPENSE_CODE' => '',
				'LINE_ITEM_ACTIVITY_CODE' => $trabajo['codigo_actividad'],
				'TIMEKEEPER_ID' => $tramite['username'],
				'LINE_ITEM_DESCRIPTION' => $descripcion,
				'LINE_ITEM_UNIT_COST' => $tarifa,
				'TIMEKEEPER_NAME' => $tramite['nombre_usuario'],
				'TIMEKEEPER_CLASSIFICATION' => $tramite['codigo_categoria'],
				'CLIENT_MATTER_ID' => $tramite['codigo_homologacion']
			);

			$suma += $fila['LINE_ITEM_TOTAL'];

			$suma_unit += $fila['LINE_ITEM_UNIT_COST'];

			$filas[] = $fila;

			$last_client_matter_id = $tramite['codigo_homologacion'];
		}
		return $filas;
	}

	/**
	 * Genera las filas correspondiente a los gastos del cobro
	 * @param $Cobro
	 * @param $linea
	 * @param $fecha_min
	 * @param $cambios
	 * @param $codigo_asunto
	 * @param $last_client_matter_id
	 * @param $suma
	 * @param $suma_unit
	 * @param $gastos
	 * @return array
	 */
	public function generarFilasGastos(&$Cobro, &$linea, $fecha_min, $cambios, &$codigo_asunto, &$last_client_matter_id, &$suma, &$suma_unit, $gastos) {

		$id_cobro = $Cobro->fields['id_cobro'];
		$filas = array();
		/**
		 * Obtener los gastos
		 */

		$Criteria = new Criteria($this->sesion);
		$gastos_result = $Criteria
			->add_select('cta_corriente.id_movimiento')
			->add_select('cta_corriente.id_usuario')
			->add_select('CONCAT(usuario.apellido1, \', \', usuario.nombre)', 'nombre_usuario')
			->add_select('usuario.username')
			->add_select('prm_categoria_usuario.codigo_categoria')
			->add_select('cta_corriente.codigo_gasto')
			->add_select('asunto.codigo_homologacion')
			->add_from('cta_corriente')
			->add_left_join_with('usuario', 'cta_corriente.id_usuario = usuario.id_usuario')
			->add_left_join_with('prm_categoria_usuario', 'usuario.id_categoria_usuario = prm_categoria_usuario.id_categoria_usuario')
			->add_left_join_with('asunto', 'cta_corriente.codigo_asunto = asunto.codigo_asunto')
			->add_restriction(CriteriaRestriction::equals('cta_corriente.id_cobro', $id_cobro))
			->run();
		$datos_gastos = array();
		foreach ($gastos_result as $gasto) {
			$datos_gastos[$gasto['id_movimiento']] = $gasto;
		}

		foreach ($gastos['gasto_detalle'] as $gasto) {
			if ($fecha_min > $gasto['fecha']) {
				$fecha_min = $gasto['fecha'];
			}
			if (!$codigo_asunto) {
				$codigo_asunto = $gasto['codigo_asunto'];
			}
			$datos = $datos_gastos[$gasto['id_movimiento']];

			/**
			 * redondeo decimales ahora para que calcen los ajustes
			 */
			$total = $this->round($gasto['monto_total_mas_impuesto'] * $cambios[$gasto['id_moneda']]);
			$sin_impuestos = $this->round($gasto['monto_total'] * $cambios[$gasto['id_moneda']]);

			$descripcion = trim(str_replace("\n", ' ', $gasto['descripcion']));

			$fila = array(
				'LAW_FIRM_MATTER_ID' => $gasto['codigo_asunto'],
				'LINE_ITEM_NUMBER' => $linea++, //'G' . $gasto['id_movimiento'],
				'EXP/FEE/INV_ADJ_TYPE' => 'E',
				'LINE_ITEM_NUMBER_OF_UNITS' => '1',
				'LINE_ITEM_ADJUSTMENT_AMOUNT' => $total - $sin_impuestos,
				'LINE_ITEM_TOTAL' => $total,
				'LINE_ITEM_DATE' => $gasto['fecha'],
				'LINE_ITEM_TASK_CODE' => '',
				'LINE_ITEM_EXPENSE_CODE' => $datos['codigo_gasto'],
				'LINE_ITEM_ACTIVITY_CODE' => '',
				'TIMEKEEPER_ID' => $datos['username'],
				'LINE_ITEM_DESCRIPTION' => $descripcion,
				'LINE_ITEM_UNIT_COST' => $sin_impuestos,
				'TIMEKEEPER_NAME' => $datos['nombre_usuario'],
				'TIMEKEEPER_CLASSIFICATION' => $datos['codigo_categoria'],
				'CLIENT_MATTER_ID' => $datos['codigo_homologacion']
			);

			$suma += $fila['LINE_ITEM_TOTAL'];

			$suma_unit += $fila['LINE_ITEM_UNIT_COST'];

			$filas[] = $fila;

			$last_client_matter_id = $datos['codigo_homologacion'];
		}
		return $filas;
	}

	/**
	 * recibe los datos y genera el archivo en formato ledes
	 * @param array $datos arreglo de filas generado por CobroLedes
	 * @param string $formato por ahora solo LEDES1998B (default), se podrian agregar LEDES1998BI y LEDES1998BI V2
	 * @return string contenido del archivo
	 */
	public function GenerarArchivoLedes($datos, $formato = 'LEDES1998B') {
		//nombre del formato en la primera fila
		$out = $formato . "[]" . "\r\n";

		$campos = $this->getFormat($formato);

		//nombres de los campos en la segunda
		$out .= implode('|', array_keys($campos)) . "[]" . "\r\n";

		//datos en cada fila
		foreach ($datos as $dato) {
			$fila = array();
			foreach ($campos as $campo => $formato) {
				$valor = isset($dato[$campo]) ? $dato[$campo] : '';

				if (isset($valor)) {
					//sacar caracteres especiales
					$valor = str_replace('|', '/', $valor);
					$valor = str_replace('[]', '[ ]', $valor);

					//formatear datos
					if ($formato == 'date') {
						$valor = date('Ymd', strtotime($valor));
					} else if (is_numeric($formato)) {
						if (strlen($valor) > $formato) {
							$valor = substr($valor, 0, $formato);
						}
					} else if ($formato) {
						list(, $decimales) = explode('.', $formato);
						$valor = str_replace(',', '.', $valor);
						$valor = number_format($valor, $decimales, '.', '');
					}
				}

				$fila[] = $valor;
			}
			$out .= implode('|', $fila) . "[]" . "\r\n";
		}

		return $out;
	}

	/**
	 * revisa si a algun dato del cobro (asuntos, trabajos, tramites y gastos) le falta algun dato especifico de LEDES
	 * @param int $id_cobro
	 * @return array array(descripcionDelError => array(glosas, de, los, datos, fallados))
	 */
	public function ValidarDatos($id_cobro) {
		$codigos_asuntos = array();
		$queries = array(
			"SELECT codigo_asunto FROM trabajo WHERE id_cobro = $id_cobro AND id_tramite = 0",
			"SELECT codigo_asunto FROM tramite WHERE id_cobro = $id_cobro",
			"SELECT codigo_asunto FROM cta_corriente WHERE id_cobro = $id_cobro"
		);
		foreach ($queries as $query) {
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			while (list($codigo) = mysql_fetch_array($resp)) {
				$codigos_asuntos[] = "'$codigo'";
			}
		}
		if (empty($codigos_asuntos)) {
			$codigos_asuntos[] = "''";
		}

		$queries = array(
			'Asuntos sin código de homologación' =>
				"SELECT glosa_asunto
					FROM asunto
					WHERE codigo_asunto IN (" . implode(',', $codigos_asuntos) . ")
						AND codigo_homologacion IS NULL",
			/* 'otros_asuntos_sin_codigo' =>
			  "SELECT a.glosa_asunto
			  FROM asunto a
			  JOIN cobro c ON a.codigo_cliente = c.codigo_cliente
			  WHERE codigo_asunto NOT IN (".implode(',', $codigos_asuntos).")
			  AND codigo_homologacion IS NULL
			  AND c.id_cobro = $id_cobro", */
			'Trabajos sin código de actividad' =>
				"SELECT descripcion
					FROM trabajo
					WHERE id_cobro = $id_cobro
					AND id_tramite = 0
					AND codigo_actividad IS NULL",
			'Trabajos sin código UTBMS' =>
				"SELECT descripcion
					FROM trabajo
					WHERE id_cobro = $id_cobro
					AND id_tramite = 0
					AND codigo_tarea IS NULL",
			'Trámites sin código de actividad' =>
				"SELECT descripcion
					FROM tramite
					WHERE id_cobro = $id_cobro
					AND codigo_actividad IS NULL",
			'Trámites sin código UTBMS' =>
				"SELECT descripcion
					FROM tramite
					WHERE id_cobro = $id_cobro
					AND codigo_tarea IS NULL",
			'Gastos sin código UTBMS' =>
				"SELECT descripcion
					FROM cta_corriente
					WHERE id_cobro = $id_cobro
					AND codigo_gasto IS NULL"
		);
		$errores = array();
		foreach ($queries as $error => $query) {
			$error = utf8_decode(__($error));
			$errores[$error] = array();
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			while (list($campo) = mysql_fetch_array($resp)) {
				$errores[$error][] = utf8_encode($campo);
			}
		}

		return $errores;
	}


	/**
	 * redondea un valor a los decimales definidos
	 * @param type $numero
	 * @return type
	 */
	protected function round($numero) {
		$n = 10;
		for ($i = 1; $i < $this->decimales; $i++) {
			$n *= 10;
		}
		return floatval(round($numero * $n)) / $n;
	}

	/**
	 * Obtiene los decimales predeterminados
	 * @return mixed
	 */
	public function getDecimales() {
		return $this->decimales;
	}

	/**
	 * Establece los decimales a utilizar
	 * @param mixed $decimales
	 */
	public function setDecimales($decimales) {
		$this->decimales = $decimales;
	}

	protected function getFormat($format) {
		$numero = "N.{$this->decimales}";
		$ledes = array(
			'LEDES98BI V2' => array(
				'INVOICE_DATE' => 'date',
				'INVOICE_NUMBER' => 20,
				'CLIENT_ID' => 20,
				'LAW_FIRM_MATTER_ID' => 20,
				'INVOICE_TOTAL' => $numero,
				'BILLING_START_DATE' => 'date',
				'BILLING_END_DATE' => 'date',
				'INVOICE_DESCRIPTION' => 15000,
				'LINE_ITEM_NUMBER' => 20,
				'EXP/FEE/INV_ADJ_TYPE' => 2,
				'LINE_ITEM_NUMBER_OF_UNITS' => $numero,
				'LINE_ITEM_ADJUSTMENT_AMOUNT' => $numero,
				'LINE_ITEM_TOTAL' => $numero,
				'LINE_ITEM_DATE' => 'date',
				'LINE_ITEM_TASK_CODE' => 20,
				'LINE_ITEM_EXPENSE_CODE' => 20,
				'LINE_ITEM_ACTIVITY_CODE' => 20,
				'TIMEKEEPER_ID' => 20,
				'LINE_ITEM_DESCRIPTION' => 15000,
				'LAW_FIRM_ID' => 20,
				'LINE_ITEM_UNIT_COST' => $numero,
				'TIMEKEEPER_NAME' => 30,
				'TIMEKEEPER_CLASSIFICATION' => 10,
				'CLIENT_MATTER_ID' => 20,
				'PO_NUMBER' => 100,
				'CLIENT_TAX_ID' => 20,
				'MATTER_NAME' => 255,
				'INVOICE_TAX_TOTAL' => $numero,
				'INVOICE_NET_TOTAL' => $numero,
				'INVOICE_CURRENCY' => 3,
				'TIMEKEEPER_LAST_NAME' => 30,
				'TIMEKEEPER_FIRST_NAME' => 30,
				'ACCOUNT_TYPE' => 1,
				'LAW_FIRM_NAME' => 60,
				'LAW_FIRM_ADDRESS_1' => 60,
				'LAW_FIRM_ADDRESS_2' => 60,
				'LAW_FIRM_CITY' => 40,
				'LAW_FIRM_STATEorREGION' => 40,
				'LAW_FIRM_POSTCODE' => 40,
				'LAW_FIRM_COUNTRY' => 3,
				'CLIENT_NAME' => 60,
				'CLIENT_ADDRESS_1' => 60,
				'CLIENT_ADDRESS_2' => 60,
				'CLIENT_CITY' => 40,
				'CLIENT_STATEorREGION' => 40,
				'CLIENT_POSTCODE' => 20,
				'CLIENT_COUNTRY' => 3,
				'LINE_ITEM_TAX_RATE' => $numero,
				'LINE_ITEM_TAX_TOTAL' => $numero,
				'LINE_ITEM_TAX_TYPE' => $numero,
				'INVOICE_REPORTED_TAX_TOTAL' => $numero,
				'INVOICE_TAX_CURRENCY' => 3
			),
			'LEDES1998B' => array(
				'INVOICE_DATE' => 'date',
				'INVOICE_NUMBER' => 20,
				'CLIENT_ID' => 20,
				'LAW_FIRM_MATTER_ID' => 20,
				'INVOICE_TOTAL' => $numero,
				'BILLING_START_DATE' => 'date',
				'BILLING_END_DATE' => 'date',
				'INVOICE_DESCRIPTION' => 15000,
				'LINE_ITEM_NUMBER' => 20,
				'EXP/FEE/INV_ADJ_TYPE' => 2,
				'LINE_ITEM_NUMBER_OF_UNITS' => $numero,
				'LINE_ITEM_ADJUSTMENT_AMOUNT' => $numero,
				'LINE_ITEM_TOTAL' => $numero,
				'LINE_ITEM_DATE' => 'date',
				'LINE_ITEM_TASK_CODE' => 20,
				'LINE_ITEM_EXPENSE_CODE' => 20,
				'LINE_ITEM_ACTIVITY_CODE' => 20,
				'TIMEKEEPER_ID' => 20,
				'LINE_ITEM_DESCRIPTION' => 15000,
				'LAW_FIRM_ID' => 20,
				'LINE_ITEM_UNIT_COST' => $numero,
				'TIMEKEEPER_NAME' => 30,
				'TIMEKEEPER_CLASSIFICATION' => 10,
				'CLIENT_MATTER_ID' => 20
			)
		);

		return $ledes[$format];
	}


}
