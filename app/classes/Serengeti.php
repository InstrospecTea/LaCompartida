<?php

require_once dirname(__FILE__) . '/../conf.php';

/**
 * Created by vladzur.
 * Date: 30-10-15
 * Time: 10:12 AM
 *
 * Adaptación para el formato LEDES de Serengeti
 */
class Serengeti  extends Ledes{

	/**
	 * Número de decimales a mostrar, el oficial es 4 pero esta gente quiere 2...
	 * @var int
	 */
	protected $decimales = 2;

	/**
	 * Serengeti constructor.
	 * @param $Sesion
	 */
	public function __construct($Sesion) {
		$this->sesion = $Sesion;
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
		$ajuste = 0;
		$filas = array();
		if (abs($suma - $monto_total) > $monto_total / 100) {
			$monto = $monto_total - $suma;
			if ($Cobro->fields['forma_cobro'] == 'FLAT FEE') {
				$ajuste = $monto_total - $suma_unit;
			}
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
				'LINE_ITEM_UNIT_COST' => $ajuste,
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
			if ($fecha_min > $trabajo['fecha']) {
				$fecha_min = $trabajo['fecha'];
			}
			if (!$codigo_asunto) {
				$codigo_asunto = $trabajo['codigo_asunto'];
			}

			$monto = $trabajo['cobrable'] == '1' && !empty($trabajo['monto_cobrado']) ? $trabajo['monto_cobrado'] : 1;
			$monto *= $cambios[$trabajo['id_moneda']];
			$tarifa = $trabajo['tarifa_hh'] * $cambios[$trabajo['id_moneda']];

			/**
			 * redondeo decimales ahora para que calcen los ajustes
			 */
			$horas =$this->round($trabajo['horas']);
			$monto = $this->round($monto);
			$tarifa = $this->round($tarifa);

			if ($Cobro->fields['forma_cobro'] == 'FLAT FEE') {
				$ajuste = 0;
				$tarifa = $monto;
			} else {
				$ajuste = ($monto != 0) ? ($monto - $tarifa * $horas) : 0;
			}

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

}
