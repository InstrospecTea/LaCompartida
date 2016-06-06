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

		$filas = array();
		if (abs($suma - $monto_total) > $monto_total / 100 or $Cobro->fields['forma_cobro'] == 'FLAT FEE') {
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
			->add_select('cobro.porcentaje_impuesto')
			->add_select('cobro.porcentaje_impuesto_gastos')
			->add_select('asunto.glosa_asunto')
			->add_select('contrato.rut')
			->add_from('trabajo')
			->add_left_join_with('usuario', 'trabajo.id_usuario = usuario.id_usuario')
			->add_left_join_with('prm_categoria_usuario', 'usuario.id_categoria_usuario = prm_categoria_usuario.id_categoria_usuario')
			->add_left_join_with('asunto', 'trabajo.codigo_asunto = asunto.codigo_asunto')
			->add_left_join_with('cobro', 'trabajo.id_cobro = cobro.id_cobro')
			->add_left_join_with('contrato', 'cobro.id_contrato = contrato.id_contrato')
			->add_restriction(CriteriaRestriction::equals('trabajo.id_cobro', $id_cobro))
			->add_restriction(CriteriaRestriction::equals('trabajo.id_tramite', 0))
			->add_restriction(CriteriaRestriction::equals('trabajo.cobrable', 1))
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

			$monto = $trabajo['cobrable'] == '1' && !empty($trabajo['monto_cobrado']) ? $trabajo['monto_cobrado'] : 1;
			$monto *= $cambios[$trabajo['id_moneda']];
			$tarifa = $trabajo['tarifa_hh'] * $cambios[$trabajo['id_moneda']];

			/**
			 * redondeo decimales ahora para que calcen los ajustes
			 */
			$horas = $this->round($trabajo['horas']);
			$monto = $this->round($monto);
			$tarifa = $this->round($tarifa);
			$ajuste = 0;
			if ($Cobro->fields['forma_cobro'] == 'FLAT FEE') {
				$monto = $tarifa * $horas;
			} else {
				$ajuste = ($monto != 0) ? ($monto - $tarifa * $horas) : 0;
			}

			$porcentaje_impuesto = $trabajo['porcentaje_impuesto'];
			if ($this->format == 'LEDES98BI V2') {
				$impuesto = $monto * $porcentaje_impuesto / 100;
			} else {
				$impuesto = 0;
			}

			$descripcion = trim(str_replace("\n", ' ', $trabajo['descripcion']));

			$fila = array(
				'LAW_FIRM_MATTER_ID' => $trabajo['codigo_asunto'],
				'LINE_ITEM_NUMBER' => $linea++, //'H' . $trabajo['id_trabajo'],
				'EXP/FEE/INV_ADJ_TYPE' => 'F',
				'LINE_ITEM_NUMBER_OF_UNITS' => $horas,
				'LINE_ITEM_ADJUSTMENT_AMOUNT' => $ajuste,
				'LINE_ITEM_TOTAL' => $monto + $impuesto,
				'LINE_ITEM_DATE' => $trabajo['fecha'],
				'LINE_ITEM_TASK_CODE' => $trabajo['codigo_tarea'],
				'LINE_ITEM_EXPENSE_CODE' => '',
				'LINE_ITEM_ACTIVITY_CODE' => $trabajo['codigo_actividad'],
				'TIMEKEEPER_ID' => $trabajo['username'],
				'LINE_ITEM_DESCRIPTION' => $descripcion,
				'LINE_ITEM_UNIT_COST' => $tarifa,
				'TIMEKEEPER_NAME' => $trabajo['nombre_usuario'],
				'TIMEKEEPER_CLASSIFICATION' => $trabajo['codigo_categoria'],
				'CLIENT_MATTER_ID' => $trabajo['codigo_homologacion'],
				'MATTER_NAME' => $trabajo['glosa_asunto'],
				'CLIENT_TAX_ID' => $trabajo['rut'],
				'LINE_ITEM_TAX_RATE' => $porcentaje_impuesto / 100,
				'LINE_ITEM_TAX_TOTAL' => $impuesto,
				'INVOICE_NET_TOTAL' => $Cobro->fields['monto_subtotal'],
				'INVOICE_CURRENCY' => $this->currency->fields['codigo'],
				'INVOICE_TAX_TOTAL' => $Cobro->fields['impuesto']
			);

			$suma += $fila['LINE_ITEM_TOTAL'];

			$suma_unit += $fila['LINE_ITEM_UNIT_COST'];

			$filas[] = $fila;

			$last_client_matter_id = $trabajo['codigo_homologacion'];
		}
		return array('filas' => $filas, 'trabajo' => $trabajo);
	}

}
