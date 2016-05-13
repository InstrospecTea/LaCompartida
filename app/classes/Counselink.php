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
		$Contrato = new Contrato($this->sesion);
		$Contrato->Load($Cobro->fields['id_contrato']);
		$this->format = $Contrato->fields['tipo_ledes'];
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
				$moneda_cobro = $this->x_resultados['id_moneda'];
				$tarifa = $this->x_resultados['monto_total_cobro'][$moneda_cobro];
				$horas = 1;
				$monto = $tarifa * $horas;
			}
			$ajuste = ($monto != 0) ? ($monto - $tarifa * $horas) : 0;

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
				'LINE_ITEM_TAX_RATE' => $porcentaje_impuesto,
				'LINE_ITEM_TAX_TOTAL' => $impuesto,
				'INVOICE_NET_TOTAL' => $Cobro->fields['monto_subtotal'],
				'INVOICE_CURRENCY' => $this->currency->fields['codigo'],
				'INVOICE_TAX_TOTAL' => $Cobro->fields['impuesto']
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
