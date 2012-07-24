<?php

require_once dirname(__FILE__) . '/../conf.php';

require_once Conf::ServerDir() . '/../fw/classes/Lista.php';
require_once Conf::ServerDir() . '/../fw/classes/Objeto.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';

require_once Conf::ServerDir() . '/../app/classes/Cobro.php';
require_once Conf::ServerDir() . '/../app/classes/Documento.php';
require_once Conf::ServerDir() . '/../app/classes/Trabajo.php';
require_once Conf::ServerDir() . '/../app/classes/Tramite.php';
require_once Conf::ServerDir() . '/../app/classes/Gasto.php';
require_once Conf::ServerDir() . '/../app/classes/UtilesApp.php';

class Ledes extends Objeto {

	function Ledes($sesion) {
		$this->sesion = $sesion;
	}

	/**
	 * genera un archivo ledes para uno o mas cobros
	 * @param mixed $ids_cobros id del cobro o array de ids
	 * @return string contenido del archivo
	 */
	function ExportarCobrosLedes($ids_cobros) {
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
	function CobroLedes($id_cobro) {
		$filas = array();

		//cargar datos de la bd
		$cobro = new Cobro($this->sesion);
		$cobro->Load($id_cobro);

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

		$categorias = array(
			'' => '', //por si acaso...
			'1' => 'PT', //socio
			'2' => 'AS', //asociado senior
			'3' => 'AS', //asociado junior
			'4' => 'LA', //precurador [sic]
			'5' => 'OT', //administrativo
			'6' => 'OT' //otro
		);

		/**
		 * Obtener los trabajos
		 */
		$query = "SELECT
				t.id_trabajo,
				t.codigo_asunto,
				t.monto_cobrado,
				TIME_TO_SEC(t.duracion_cobrada)/3600 as horas,
				t.tarifa_hh,
				t.fecha,
				t.id_usuario,
				t.descripcion,
				t.cobrable,
				t.id_moneda,
				CONCAT(u.apellido1, ', ', u.nombre) as nombre_usuario,
				u.username,
				c.id_categoria_lemontech,
				t.codigo_actividad,
				t.codigo_tarea,
				a.codigo_homologacion
			FROM trabajo t
			JOIN usuario u ON t.id_usuario = u.id_usuario
			JOIN prm_categoria_usuario c ON u.id_categoria_usuario = c.id_categoria_usuario
			JOIN asunto a ON t.codigo_asunto = a.codigo_asunto
			WHERE t.id_cobro = $id_cobro AND t.id_tramite = 0";

		$linea = 1;
		
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		while ($trabajo = mysql_fetch_assoc($resp)) {
			if ($fecha_min > $trabajo['fecha']) {
				$fecha_min = $trabajo['fecha'];
			}
			if (!$codigo_asunto) {
				$codigo_asunto = $trabajo['codigo_asunto'];
			}

			$monto = $trabajo['cobrable'] == '1' && !empty($trabajo['monto_cobrado']) ? $trabajo['monto_cobrado'] : 0;
			$monto *= $cambios[$trabajo['id_moneda']];
			$tarifa = $trabajo['tarifa_hh'] * $cambios[$trabajo['id_moneda']];

			$descripcion = str_replace("\n", ' ', $trabajo['descripcion']);
			$descripcion = trim($descripcion);
			
			$fila = array(
				'LAW_FIRM_MATTER_ID' => $trabajo['codigo_asunto'],
				'LINE_ITEM_NUMBER' => $linea++, //'H' . $trabajo['id_trabajo'],
				'EXP/FEE/INV_ADJ_TYPE' => 'F',
				'LINE_ITEM_NUMBER_OF_UNITS' => $trabajo['horas'],
				'LINE_ITEM_ADJUSTMENT_AMOUNT' => $monto - $tarifa * $trabajo['horas'],
				'LINE_ITEM_TOTAL' => $monto,
				'LINE_ITEM_DATE' => $trabajo['fecha'],
				'LINE_ITEM_TASK_CODE' => $trabajo['codigo_tarea'],
				'LINE_ITEM_EXPENSE_CODE' => '',
				'LINE_ITEM_ACTIVITY_CODE' => $trabajo['codigo_actividad'],
				'TIMEKEEPER_ID' => $trabajo['username'],
				'LINE_ITEM_DESCRIPTION' => $descripcion,
				'LINE_ITEM_UNIT_COST' => $tarifa,
				'TIMEKEEPER_NAME' => $trabajo['nombre_usuario'],
				'TIMEKEEPER_CLASSIFICATION' => $categorias[$trabajo['id_categoria_lemontech']],
				'CLIENT_MATTER_ID' => $trabajo['codigo_homologacion']
			);

			$suma += $fila['LINE_ITEM_TOTAL'];

			$filas[] = $fila;
		}

		/**
		 * Obtener los trámites
		 */
		$query = "SELECT
				t.id_tramite,
				t.codigo_asunto,
				t.tarifa_tramite,
				TIME_TO_SEC(t.duracion)/3600 as horas,
				t.tarifa_tramite,
				t.fecha,
				t.id_usuario,
				t.descripcion,
				t.id_moneda_tramite,
				CONCAT(u.apellido1, ', ', u.nombre) as nombre_usuario,
				u.username,
				c.id_categoria_lemontech,
				t.codigo_actividad,
				t.codigo_tarea,
				a.codigo_homologacion
			FROM tramite t
			JOIN usuario u ON t.id_usuario = u.id_usuario
			JOIN prm_categoria_usuario c ON u.id_categoria_usuario = c.id_categoria_usuario
			JOIN asunto a ON t.codigo_asunto = a.codigo_asunto
			WHERE t.id_cobro = $id_cobro";

		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		while ($tramite = mysql_fetch_assoc($resp)) {
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
			
			$descripcion = str_replace("\n", ' ', $tramite['descripcion']);
			$descripcion = trim($descripcion);
			
			$fila = array(
				'LAW_FIRM_MATTER_ID' => $tramite['codigo_asunto'],
				'LINE_ITEM_NUMBER' => $linea++, //'T' . $tramite['id_tramite'],
				'EXP/FEE/INV_ADJ_TYPE' => 'F',
				'LINE_ITEM_NUMBER_OF_UNITS' => $horas,
				'LINE_ITEM_ADJUSTMENT_AMOUNT' => $monto - $tarifa,
				'LINE_ITEM_TOTAL' => $monto,
				'LINE_ITEM_DATE' => $tramite['fecha'],
				'LINE_ITEM_TASK_CODE' => $trabajo['codigo_tarea'],
				'LINE_ITEM_EXPENSE_CODE' => '',
				'LINE_ITEM_ACTIVITY_CODE' => $trabajo['codigo_actividad'],
				'TIMEKEEPER_ID' => $tramite['username'],
				'LINE_ITEM_DESCRIPTION' => $descripcion,
				'LINE_ITEM_UNIT_COST' => $tarifa / $horas,
				'TIMEKEEPER_NAME' => $tramite['nombre_usuario'],
				'TIMEKEEPER_CLASSIFICATION' => $categorias[$tramite['id_categoria_lemontech']],
				'CLIENT_MATTER_ID' => $tramite['codigo_homologacion']
			);

			$suma += $fila['LINE_ITEM_TOTAL'];

			$filas[] = $fila;
		}

		/**
		 * Obtener los gastos
		 */
		$query = "SELECT g.id_movimiento,
				g.id_usuario,
				CONCAT(u.apellido1, ', ', u.nombre) as nombre_usuario,
				u.username,
				c.id_categoria_lemontech,
				g.codigo_gasto,
				a.codigo_homologacion
			FROM cta_corriente g
			JOIN usuario u ON g.id_usuario = u.id_usuario
			JOIN prm_categoria_usuario c ON u.id_categoria_usuario = c.id_categoria_usuario
			JOIN asunto a ON g.codigo_asunto = a.codigo_asunto
			WHERE g.id_cobro = $id_cobro";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		$datos_gastos = array();
		while ($gasto = mysql_fetch_assoc($resp)) {
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

			$descripcion = str_replace("\n", ' ', $gasto['descripcion']);
			$descripcion = trim($descripcion);
			
			$fila = array(
				'LAW_FIRM_MATTER_ID' => $gasto['codigo_asunto'],
				'LINE_ITEM_NUMBER' => $linea++, //'G' . $gasto['id_movimiento'],
				'EXP/FEE/INV_ADJ_TYPE' => 'E',
				'LINE_ITEM_NUMBER_OF_UNITS' => '1',
				'LINE_ITEM_ADJUSTMENT_AMOUNT' => $gasto['monto_total_impuesto'] * $cambios[$gasto['id_moneda']],
				'LINE_ITEM_TOTAL' => $gasto['monto_total_mas_impuesto'] * $cambios[$gasto['id_moneda']],
				'LINE_ITEM_DATE' => $gasto['fecha'],
				'LINE_ITEM_TASK_CODE' => '',
				'LINE_ITEM_EXPENSE_CODE' => $datos['codigo_gasto'],
				'LINE_ITEM_ACTIVITY_CODE' => '',
				'TIMEKEEPER_ID' => $datos['username'],
				'LINE_ITEM_DESCRIPTION' => $descripcion,
				'LINE_ITEM_UNIT_COST' => $gasto['monto_total'] * $cambios[$gasto['id_moneda']],
				'TIMEKEEPER_NAME' => $datos['nombre_usuario'],
				'TIMEKEEPER_CLASSIFICATION' => $categorias[$datos['id_categoria_lemontech']],
				'CLIENT_MATTER_ID' => $datos['codigo_homologacion']
			);

			$suma += $fila['LINE_ITEM_TOTAL'];

			$filas[] = $fila;
		}

		/**
		 * Obtener los datos de la liquidación
		 */
		// Obtengo el código secundario del cliente
		
		$datos_cobro = array(
			'INVOICE_DATE' => $cobro->fields['fecha_emision'],
			'INVOICE_NUMBER' => $id_cobro,
			'CLIENT_ID' => $cobro->fields['codigo_cliente'],
			'INVOICE_TOTAL' => $x_resultados['monto_total_cobro'][$moneda_cobro],
			'BILLING_START_DATE' => $cobro->fields['fecha_ini'],
			'BILLING_END_DATE' => $cobro->fields['fecha_fin'],
			'INVOICE_DESCRIPTION' => $cobro->fields['se_esta_cobrando'],
			'LAW_FIRM_ID' => UtilesApp::GetConf($this->sesion, 'IdentificadorEstudio')
		);
		if ($datos_cobro['BILLING_START_DATE'] < '2000-01-01') {
			$datos_cobro['BILLING_START_DATE'] = $fecha_min;
		}

		if (!$codigo_asunto) {
			$query = "SELECT codigo_asunto FROM asunto WHERE id_contrato = " . $cobro->fields['id_contrato'] . " LIMIT 1";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($codigo_asunto) = mysql_fetch_assoc($resp);
		}

		//ajuste a nivel de cobro para corregir segun forma de cobro, descuentos, etc
		//todo: hacer ajustes por separado con descripcion? este hace calzar barsamente
		$monto_total = $datos_cobro['INVOICE_TOTAL'] * 1;
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
				'CLIENT_MATTER_ID' => ''
			);

			$suma += $fila['LINE_ITEM_TOTAL'];

			$filas[] = $fila;
		}

		/* echo '<pre>';
		  print_r(array(
		  'cobro' => $cobro->fields,
		  'x_resultados' => $x_resultados,
		  'gastos' => $gastos,
		  'filas' => $filas,
		  'cambios' => $cambios,
		  'datos_cobro' => $datos_cobro
		  )); */

		foreach ($filas as $k => $fila) {
			// Será así?
			$fila['LAW_FIRM_MATTER_ID'] = $datos_cobro['LAW_FIRM_ID'];
			
			$filas[$k] = $datos_cobro + $fila;
		}

		return $filas;
	}

	/**
	 * recibe los datos y genera el archivo en formato ledes
	 * @param array $datos arreglo de filas generado por CobroLedes
	 * @param string $formato por ahora solo LEDES1998B (default), se podrian agregar LEDES1998BI y LEDES1998BI V2
	 * @return string contenido del archivo
	 */
	function GenerarArchivoLedes($datos, $formato = 'LEDES1998B') {
		//nombre del formato en la primera fila
		$out = $formato . "[]" . "\r\n";

		$campos = array(
			'INVOICE_DATE' => 'date',
			'INVOICE_NUMBER' => 20,
			'CLIENT_ID' => 20,
			'LAW_FIRM_MATTER_ID' => 20,
			'INVOICE_TOTAL' => 'N.2', //'N.4', es el oficial
			'BILLING_START_DATE' => 'date',
			'BILLING_END_DATE' => 'date',
			'INVOICE_DESCRIPTION' => 15000,
			'LINE_ITEM_NUMBER' => 20,
			'EXP/FEE/INV_ADJ_TYPE' => 2,
			'LINE_ITEM_NUMBER_OF_UNITS' => 'N.2', //'N.4', es el oficial
			'LINE_ITEM_ADJUSTMENT_AMOUNT' => 'N.2', //'N.4', es el oficial
			'LINE_ITEM_TOTAL' => 'N.2', //'N.4', es el oficial
			'LINE_ITEM_DATE' => 'date',
			'LINE_ITEM_TASK_CODE' => 20,
			'LINE_ITEM_EXPENSE_CODE' => 20,
			'LINE_ITEM_ACTIVITY_CODE' => 20,
			'TIMEKEEPER_ID' => 20,
			'LINE_ITEM_DESCRIPTION' => 15000,
			'LAW_FIRM_ID' => 20,
			'LINE_ITEM_UNIT_COST' => 'N.2', //'N.4', es el oficial
			'TIMEKEEPER_NAME' => 30,
			'TIMEKEEPER_CLASSIFICATION' => 10,
			'CLIENT_MATTER_ID' => 20
		);

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
	function ValidarDatos($id_cobro) {
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

}
