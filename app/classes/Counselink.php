<?php

require_once dirname(__FILE__) . '/../conf.php';

/**
 * Created by vladzur.
 * Date: 30-10-15
 * Time: 10:14 AM
 *
 * Adaptación para el formato LEDES de Counselink
 */
class Counselink extends Ledes{

	/**
	 * Número de decimales a mostrar
	 * @var int
	 */
	protected $decimales = 2;

	protected $x_resultados;

	/**
	 * Counselink constructor.
	 * @param $Sesion
	 */
	public function __construct($Sesion) {
		$this->sesion = $Sesion;
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
		$this->x_resultados = $x_resultados;
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
				c.codigo_categoria,
				t.codigo_actividad,
				t.codigo_tarea,
				a.codigo_homologacion
			FROM trabajo t
			JOIN usuario u ON t.id_usuario = u.id_usuario
			JOIN prm_categoria_usuario c ON u.id_categoria_usuario = c.id_categoria_usuario
			JOIN asunto a ON t.codigo_asunto = a.codigo_asunto
			WHERE t.id_cobro = $id_cobro AND t.id_tramite = 0";


		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		while ($trabajo = mysql_fetch_assoc($resp)) {
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
				$moneda_cobro = $this->x_resultados['id_moneda'];
				$tarifa = $this->x_resultados['monto_total_cobro'][$moneda_cobro];
				$horas = 1;
				$monto = $tarifa * $horas;
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

			if ($Cobro->fields['forma_cobro'] == 'FLAT FEE') {
				break;
			}
		}
		return array('filas' => $filas, 'trabajo' => $trabajo);
	}

}
