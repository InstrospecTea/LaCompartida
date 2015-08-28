<?php

require_once dirname(dirname(__FILE__)) . '/conf.php';

class ReporteCriteria {

	// Sesion PHP
	public $sesion = null;
	// Arreglos con filtros
	public $filtros = array();
	public $parametros = array();
	public $filtros_especiales = array();
	public $rango = array();
	// Arreglo de datos
	public $tipo_dato = 0;
	// Moneda
	public $id_moneda = null;
	// Arreglo con vista
	public $vista;
	// Arreglo con resultados
	public $row;
	// String con el último error
	public $error = '';
	// El orden de los agrupadores
	public $agrupador = array();
	public $id_agrupador = array();
	public $id_agrupador_cobro = array();
	public $orden_agrupador = array();
	public $agrupador_principal = 0;
	// Campos utilizados para determinar los datos en el periodo. Default: trabajo.
	public $campo_fecha = '';
	public $campo_fecha_2 = '';
	public $campo_fecha_3 = '';
	public $campo_fecha_cobro = 'cobro.fecha_fin';
	public $campo_fecha_cobro_2 = 'cobro.fecha_emision';
	// Determina como se calcula la proporcionalidad de los montos en Flat Fee
	public $proporcionalidad = 'estandar';
	public $conf = array();
	// Codigo secundario cuando corresponde
	public $dato_usuario = 'usuario.username';
	public $dato_codigo_asunto = 'asunto.codigo_asunto_secundario';
	// Cuanto se repite la fila para cada agrupador
	public $filas = array();

	public static $tiposMoneda = array('costo', 'costo_hh', 'valor_cobrado', 'valor_tramites', 'valor_cobrado_no_estandar', 'valor_por_cobrar', 'valor_pagado', 'valor_por_pagar', 'valor_hora', 'valor_incobrable', 'diferencia_valor_estandar', 'valor_estandar', 'valor_trabajado_estandar', 'valor_por_pagar_parcial', 'valor_pagado_parcial', 'rentabilidad', 'rentabilidad_base');

	private $newCalculation = array(
		'valor_cobrado' => 'BilledAmount',
		'horas_castigadas' => null,
		'horas_cobrables' => null,
		'horas_cobradas' => null,
		'horas_convenio' => null,
		'horas_incobrables' => null,
		'horas_no_cobrables' => null,
		'horas_pagadas' => null,
		'horas_por_cobrar' => null,
		'horas_por_pagar' => null,
		'horas_spot' => null,
		'horas_trabajadas' => null,
		'horas_visibles' => null,
		'costo' => null,
		'costo_hh' => null,
		'diferencia_valor_estandar' => null,
		'rentabilidad' => null,
		'rentabilidad_base' => null,
		'valor_cobrado_no_estandar' => null,
		'valor_estandar' => null,
		'valor_hora' => null,
		'valor_incobrable' => null,
		'valor_pagado' => null,
		'valor_pagado_parcial' => null,
		'valor_por_cobrar' => null,
		'valor_por_pagar' => null,
		'valor_por_pagar_parcial' => null,
		'valor_trabajado_estandar' => null,
		'valor_tramites' => null
	);

	const TIPO_TRABAJOS = 0;
	const TIPO_TRAMITES = 1;
	const TIPO_COBROS = 2;

	public function __construct($sesion) {
		$this->sesion = $sesion;
		$this->dato_usuario = $this->nombre_usuario('usuario');
		$this->undefined_value = sprintf("'%s'", __('Indefinido'));
		$this->to_emit = sprintf("'%s'", __('Por Emitir'));
		if (Conf::GetConf($this->sesion, 'CodigoSecundario')) {
			$this->dato_codigo_asunto = 'asunto.codigo_asunto_secundario';
		} else {
			$this->dato_codigo_asunto = 'asunto.codigo_asunto';
		}
	}

	public function configuracion($opcs = array()) {
		$this->conf = array();
	}

	//Agrega un filtro
	public function addFiltro($tabla, $campo, $valor, $positivo = true) {
		if (!isset($this->filtros[$tabla . '.' . $campo])) {
			$this->filtros[$tabla . '.' . $campo] = array();
		}

		if ($positivo) {
			$this->filtros[$tabla . '.' . $campo]['positivo'][] = $valor;
		} else {
			$this->filtros[$tabla . '.' . $campo]['negativo'][] = $valor;
		}
	}

	//Indica si el tipo de dato se calcula usando Moneda.
	public function requiereMoneda($tipo_dato) {
		$extras = array('rentabilidad', 'rentabilidad_base');

		if (in_array($tipo_dato, array_merge($extras, self::$tiposMoneda))) {
			return true;
		}
		return false;
	}

	public function usaDivisor() {
		if (in_array($this->tipo_dato, array('rentabilidad', 'rentabilidad_base', 'valor_hora', 'costo_hh'))) {
			return true;
		}
		return false;
	}

	/**
	 * Establece el tipo de dato a buscar, y agrega los filtros correspondientes
	 * Tipos de dato disponibles:
	 *
	 * +-- horas_trabajadas: Total de Horas Trabajadas
	 * |  +-- horas_cobrables: Total de Horas Trabajadas en asuntos Facturables
	 * |  |  +-- horas_visibles: Horas que ve el Cliente en nota de liquidación (tras revisión)
	 * |  |  |  +-- horas_cobradas: Horas Visibles en Liquidaciones que ya fueron Emitidas
	 * |  |  |  |  +-- horas_pagadas: Horas Cobradas en Cobros con estado Pagado
	 * |  |  |  |  \-- horas_por_pagar: Horas Cobradas que aún no han sido pagadas
	 * |  |  |  +-- horas_por_cobrar: Horas Visibles que aún no se Emiten al Cliente
	 * |  |  |  \-- horas_incobrables: Horas en Cobros Incobrables
	 * |  |  \-- horas_castigadas: Diferencia de Horas Cobrables con las Horas que ve el cliente en nota de Cobro
	 * |  \-- horas_no_cobrables: Total de Horas Trabajadas en asuntos no Facturables
	 * |
	 * +-- valor_trabajado: (no implementado)
	 * |  +-- valor_cobrable: (no implementado)
	 * |  |  +-- valor_visible: (no implementado)
	 * |  |  |  +-- valor_cobrado: Valor monetario que corresponde a cada Profesional, en una Liquidación ya Emitida
	 * |  |  |  +-- valor_tramites: Valor monetario de trámites que corresponde a cada Profesional, en una Liquidación ya Emitida
	 * |  |  |  |  +-- valor_pagado: Valor Cobrado que ha sido Pagado
	 * |  |  |  |  \-- valor_por_pagar: Valor Cobrado que aún no ha sido pagado
	 * |  |  |  +-- valor_por_cobrar: Valor monetario estimado que corresponde a cada Profesional en horas por cobrar
	 * |  |  |  \-- valor_incobrable: Valor monetario que corresponde a cada Profesional, en un Cobro Incobrable
	 * |  |  +-- valor_castigado: (no implementado)
	 * |  +-- valor_no_cobrable: (no implementado)
	 * +-- valor_trabajado_estandar: Horas Trabajadas por THH Estándar, para todo Trabajo
	 * +-- valor_estandar: Valor Cobrado, si se hubiera usado THH Estándar
	 * +-- diferencia_valor_estandar: Valor Cobrado - Valor Estándar
	 * +-- valor_hora: Valor Cobrado / Horas Cobradas
	 * +-- rentabilidad_base: Valor Cobrado / Valor Trabajado Estándar
	 * +-- rentabilidad: Valor Cobrado / Valor Estándar
	 * +-- costo: Costo para la firma, por concepto de sueldos
	 * \-- costo_hh: Costo HH para la firma, por concepto de sueldos
	 *
	 * @param $nombre String tipo de dato a considerar en el reporte
	 * @param $dato_extra Datos extras (usado para determinar si mostrar trabajos sin horas castigadas)
	 * @return void sólo asigna los filtros necesarios según tipo de dato
	 */
	public function setTipoDato($nombre, $dato_extra = null) {
		$this->tipo_dato = $nombre;
		switch ($nombre) {
			case "costo":
			case "costo_hh":
			case "horas_trabajadas":
				unset($this->filtros['cobro.estado']);   // el costo es independiente de los cobros
				break;
			case "horas_cobrables":
			case "horas_visibles":
			case "horas_castigadas":
				$this->addFiltro('trabajo', 'cobrable', '1');
				$this->addFiltro('tramite', 'cobrable', '1');
				if($dato_extra == 1){
					$this->filtros_especiales[] = "(duracion - duracion_cobrada) > 0"; //no mostrar trabajos sin horas castigadas
				}
				break;

			case "horas_spot":
				$this->addFiltro('trabajo', 'cobrable', '1');
				$this->addFiltro('tramite', 'cobrable', '1');
				$this->filtros_especiales[] = " ( cobro.estado <> 'CREADO' AND cobro.estado <> 'EN REVISION' AND ( cobro.forma_cobro IN ('TASA','CAP') )) OR ( (cobro.estado IS NULL OR cobro.estado IN ('CREADO','EN REVISION')) AND (contrato.forma_cobro IN ('TASA','CAP') OR contrato.forma_cobro IS NULL ) ) ";
				break;

			case "horas_convenio":
				$this->addFiltro('trabajo', 'cobrable', '1');
				$this->addFiltro('tramite', 'cobrable', '1');
				$this->filtros_especiales[] = " ( cobro.estado <> 'CREADO' AND cobro.estado <> 'EN REVISION' AND  ( cobro.forma_cobro IN ('FLAT FEE','RETAINER') )) OR ( (cobro.estado IS NULL OR cobro.estado IN ('CREADO','EN REVISION')) AND (contrato.forma_cobro IN ('FLAT FEE','RETAINER') ) )";
				break;

			case "horas_no_cobrables":
				$this->addFiltro('trabajo', 'cobrable', '0');
				$this->addFiltro('tramite', 'cobrable', '0');
				break;

			case "horas_cobradas":
			case "valor_cobrado_no_estandar":
			case "valor_cobrado":
			case "valor_tramites":
			case "valor_hora":
			case "rentabilidad":
			case "diferencia_valor_estandar":
			case "valor_estandar":
			case "valor_pagado_parcial":
				$this->addFiltro('trabajo', 'cobrable', '1');
				$this->addFiltro('tramite', 'cobrable', '1');
				$this->addFiltro('cobro', 'estado', 'EMITIDO');
				$this->addFiltro('cobro', 'estado', 'FACTURADO');
				$this->addFiltro('cobro', 'estado', 'ENVIADO AL CLIENTE');
				$this->addFiltro('cobro', 'estado', 'PAGO PARCIAL');
				$this->addFiltro('cobro', 'estado', 'PAGADO');
				break;

			case "valor_por_cobrar":
			case "horas_por_cobrar":
				$this->addFiltro('trabajo', 'cobrable', '1');
				$this->addFiltro('tramite', 'cobrable', '1');
				$this->addFiltro('cobro', 'estado', 'EMITIDO', false);
				$this->addFiltro('cobro', 'estado', 'FACTURADO', false);
				$this->addFiltro('cobro', 'estado', 'ENVIADO AL CLIENTE', false);
				$this->addFiltro('cobro', 'estado', 'PAGO PARCIAL', false);
				$this->addFiltro('cobro', 'estado', 'PAGADO', false);
				$this->addFiltro('cobro', 'estado', 'INCOBRABLE', false);
				break;

			case "horas_pagadas":
			case "valor_pagado":
				$this->addFiltro('trabajo', 'cobrable', '1');
				$this->addFiltro('tramite', 'cobrable', '1');
				$this->addFiltro('cobro', 'estado', 'PAGO PARCIAL');
				$this->addFiltro('cobro', 'estado', 'PAGADO');
				break;

			case "horas_por_pagar":
			case "valor_por_pagar":
			case "valor_por_pagar_parcial":
				$this->addFiltro('trabajo', 'cobrable', '1');
				$this->addFiltro('tramite', 'cobrable', '1');
				$this->addFiltro('cobro', 'estado', 'EMITIDO');
				$this->addFiltro('cobro', 'estado', 'FACTURADO');
				$this->addFiltro('cobro', 'estado', 'ENVIADO AL CLIENTE');
				$this->addFiltro('cobro', 'estado', 'PAGO PARCIAL');
				break;

			case "horas_incobrables":
			case "valor_incobrable":
				$this->addFiltro('trabajo', 'cobrable', '1');
				$this->addFiltro('tramite', 'cobrable', '1');
				$this->addFiltro('cobro', 'estado', 'INCOBRABLE');
				break;
		}
	}

	//Agrega un Filtro de Rango de Fechas
	public function addRangoFecha($valor1, $valor2) {
		$this->rango['fecha_ini'] = $valor1;
		$this->rango['fecha_fin'] = $valor2;
	}

	public function setProporcionalidad($valor = 'estandar') {
		$this->proporcionalidad = $valor;
	}

	//Establece el Campo de la fecha
	public function setCampoFecha($campo_fecha) {
		if ($campo_fecha == 'cobro') {
			$this->campo_fecha = 'cobro.fecha_fin';
			$this->campo_fecha_2 = 'cobro.fecha_creacion';
			$this->campo_fecha_3 = '';
		}

		if ($campo_fecha == 'emision') {
			$this->campo_fecha = 'cobro.fecha_emision';
			$this->campo_fecha_2 = '';
			$this->campo_fecha_3 = '';

			$this->campo_fecha_cobro = 'cobro.fecha_emision';
			$this->campo_fecha_cobro_2 = '';
		}

		if ($campo_fecha == 'envio') {
			$this->campo_fecha = 'cobro.fecha_enviado_cliente';
			$this->campo_fecha_2 = '';
			$this->campo_fecha_3 = '';

			$this->campo_fecha_cobro = 'cobro.fecha_enviado_cliente';
			$this->campo_fecha_cobro_2 = '';
		}
		if ($campo_fecha == 'facturacion') {
			$this->campo_fecha = 'cobro.fecha_facturacion';
			$this->campo_fecha_2 = '';
			$this->campo_fecha_3 = '';

			$this->campo_fecha_cobro = 'cobro.fecha_facturacion';
			$this->campo_fecha_cobro_2 = '';
		}
	}

	//Los Agrupadores definen GROUP y ORDER en las queries.
	public function addAgrupador($s) {
		$this->agrupador[] = $s;
		//Para GROUP BY - Query principal por trabajos
		switch ($s) {
			case "profesional":
			case "username":
				$this->id_agrupador[] = "id_usuario";
				break;
			case "glosa_grupo_cliente":
				$this->id_agrupador[] = "id_grupo_cliente";
				break;
			case "glosa_cliente":
				$this->id_agrupador[] = "codigo_cliente";
				break;
			case "glosa_asunto":
			case "glosa_asunto_con_codigo":
			case "glosa_cliente_asunto":
				$this->id_agrupador[] = "codigo_asunto";
				break;
			case "area_trabajo":
				$this->id_agrupador[] = "trabajo.id_area_trabajo";
				break;

			default:
				$this->id_agrupador[] = $s;
		}

		//Para ORDER BY - Query principal por trabajos
		switch ($s) {
			case "mes_reporte":
			case "dia_reporte":
				$this->orden_agrupador[] = "fecha_final";
				break;
			case "area_trabajo":
				$this->orden_agrupador[] = "trabajo.id_area_trabajo";
				break;
			default:
				$this->orden_agrupador[] = $s;
		}


		//Para GROUP BY - Query secundaria por Cobros
		switch ($s) {
			//Agrupadores que no existen para Cobro sin trabajos:
			case "id_trabajo":
				break;
			case "area_asunto":
				$this->id_agrupador_cobro[] = "area_asunto";
				break;
			case "tipo_asunto":
				$this->id_agrupador_cobro[] = "tipo_asunto";
				break;
			case "glosa_asunto":
			case "glosa_asunto_con_codigo":
			case "glosa_cliente_asunto":
				$this->id_agrupador_cobro[] = "codigo_asunto";
				break;

			case "prm_area_proyecto.glosa": case "profesional":
			case "username":
				$this->id_agrupador_cobro[] = "profesional";
				break;
			case "glosa_grupo_cliente":
				$this->id_agrupador_cobro[] = "id_grupo_cliente";
				break;
			case "glosa_cliente":
				$this->id_agrupador_cobro[] = "codigo_cliente";
				break;
			case "glosa_estudio":
				$this->id_agrupador_cobro[] = "id_estudio";
				break;
			default:
				$this->id_agrupador_cobro[] = $s;
		}
	}

	//Establece la vista: los agrupadores (y su orden) son la base para la construcción de arreglos de resultado.
	public function setVista($vista) {
		$this->vista = $vista;
		$this->agrupador = array();
		$this->id_agrupador = array();

		$agrupadores = explode("-", $vista);
		if (!$vista) {
			return;
		}

		//Relleno Agrupadores faltantes (hasta 6)
		while (!$agrupadores[5]) {
			for ($i = 5; $i > 0; $i--) {
				if (isset($agrupadores[$i - 1])) {
					$agrupadores[$i] = $agrupadores[$i - 1];
				}
			}
		}

		foreach ($agrupadores as $agrupador) {
			$this->addAgrupador($agrupador);
		}
	}

	public function alt($opc1, $opc2) {
		if (!$opc2) {
			return $opc1;
		} else {
			return " IF( $opc1 IS NULL OR $opc1 = '00-00-0000' , $opc2 , $opc1 )";
		}
	}

	public function nombre_usuario($tabla) {
		if (Conf::GetConf($this->sesion, 'UsaUsernameEnTodoElSistema')) {
			return "{$tabla}.username";
		}
		return "CONCAT_WS(' ', {$tabla}.nombre, {$tabla}.apellido1, LEFT({$tabla}.apellido2, 1))";
	}

	public function cobroQuery() { //Query que añade rows para los datos de Cobros emitidos que no cuentan Trabajos
		if (empty($this->id_agrupador_cobro)) {
			return 0;
		}

		$Criteria = new Criteria($this->sesion);

		$this->addCommonColumnsToCriteria($Criteria, TIPO_COBROS);

		// TIPO DE DATO
		switch ($this->tipo_dato) {
			case 'valor_cobrado':
			case 'valor_por_cobrar':
			case 'valor_hora':
				$Criteria->add_select('0', 'valor_divisor');
				$Criteria->add_select('(1 / IFNULL(ca2.cant_asuntos, 1))
										* SUM((cobro.monto_subtotal - cobro.monto_tramites)
											* (cobro_moneda_cobro.tipo_cambio / cobro_moneda_base.tipo_cambio)
											/ (cobro_moneda.tipo_cambio / cobro_moneda_base.tipo_cambio)
										)', $this->tipo_dato);
				break;

			case 'rentabilidad_base':
				$Criteria->add_select('0', 'valor_divisor');
				$Criteria->add_select("(1 / IFNULL(ca2.cant_asuntos, 1))
										* SUM(
											IF(
												cobro.estado NOT IN ('CREADO', 'EN REVISION'),
												(cobro.monto_subtotal - cobro.monto_tramites)
													* (cobro_moneda_cobro.tipo_cambio/cobro_moneda_base.tipo_cambio)
													/ (cobro_moneda.tipo_cambio / cobro_moneda_base.tipo_cambio)
												, 0
											)
										)", $this->tipo_dato);
				break;

			case 'valor_pagado':
				$Criteria->add_select("(1 / IFNULL(ca2.cant_asuntos, 1))
										* SUM(
											IF(
												cobro.estado = 'PAGADO',
												((cobro.monto_subtotal - cobro.monto_tramites)
													* (cobro_moneda_cobro.tipo_cambio / cobro_moneda_base.tipo_cambio)
													/ (cobro_moneda.tipo_cambio / cobro_moneda_base.tipo_cambio)
												),
												0
											)
										)", $this->tipo_dato);
				break;

			case 'valor_pagado_parcial':
				$Criteria->add_select("(1 / IFNULL(ca2.cant_asuntos, 1))
										* SUM((cobro.monto_subtotal - cobro.monto_tramites)
											* (cobro_moneda_cobro.tipo_cambio / cobro_moneda_base.tipo_cambio)
											* (1 - documento.saldo_honorarios / documento.honorarios)
											/ (cobro_moneda.tipo_cambio / cobro_moneda_base.tipo_cambio)
										)");
				break;

			case 'valor_por_pagar_parcial':
				$Criteria->add_select("(1 / IFNULL(ca2.cant_asuntos, 1))
										* SUM((cobro.monto_subtotal - cobro.monto_tramites)
											* (cobro_moneda_cobro.tipo_cambio / cobro_moneda_base.tipo_cambio)
											* (documento.saldo_honorarios / documento.honorarios)
											/ (cobro_moneda.tipo_cambio / cobro_moneda_base.tipo_cambio)
										)", $this->tipo_dato);
				break;

			case 'valor_por_pagar':
				$Criteria->add_select("(1 / IFNULL(ca2.cant_asuntos, 1))
										* SUM(
											IF(
												cobro.estado IN ('PAGADO', 'INCOBRABLE'),
												0,
												(
													(cobro.monto_subtotal - cobro.monto_tramites)
													* (cobro_moneda_cobro.tipo_cambio / cobro_moneda_base.tipo_cambio)
													/ (cobro_moneda.tipo_cambio / cobro_moneda_base.tipo_cambio)
												)
											)
										)", $this->tipo_dato);
				break;

			case 'valor_incobrable':
				$Criteria->add_select("(1 / IFNULL(ca2.cant_asuntos, 1))
										* SUM(
											IF(
												cobro.estado != 'INCOBRABLE',
												0,
												(
													(cobro.monto_subtotal - cobro.monto_tramites)
													* (cobro_moneda_cobro.tipo_cambio / cobro_moneda_base.tipo_cambio)
													/ (cobro_moneda.tipo_cambio / cobro_moneda_base.tipo_cambio)
												)
											)
										)", $this->tipo_dato);
				break;

			case 'rentabilidad':
				$Criteria->add_select('0', 'valor_divisor');
				$Criteria->add_select("(1 / ifnull(ca2.cant_asuntos, 1))
										* SUM((cobro.monto_subtotal - cobro.monto_tramites)
											* (cobro_moneda_cobro.tipo_cambio / cobro_moneda_base.tipo_cambio)
											/ (cobro_moneda.tipo_cambio / cobro_moneda_base.tipo_cambio)
										)", $this->tipo_dato);
				break;
			case 'diferencia_valor_estandar':
				$Criteria->add_select("(1 / IFNULL(ca2.cant_asuntos, 1))
										* SUM((cobro.monto_subtotal - cobro.monto_tramites)
											* (cobro_moneda_cobro.tipo_cambio / cobro_moneda_base.tipo_cambio)
											/ (cobro_moneda.tipo_cambio / cobro_moneda_base.tipo_cambio)
										)", $this->tipo_dato);
				break;

			case 'valor_estandar':
			case 'valor_trabajado_estandar':
			case 'valor_tramites':
			case 'costo_hh':
				$Criteria->add_select('0', $this->tipo_dato);
				break;
		}

		$Criteria->add_from('cobro')
			->add_left_join_with('cobro_asunto',
				CriteriaRestriction::equals('cobro.id_cobro', 'cobro_asunto.id_cobro'))
			->add_left_join_with('asunto',
				CriteriaRestriction::equals('cobro_asunto.codigo_asunto', 'asunto.codigo_asunto'));

		$SubCriteria = new Criteria();
		$SubCriteria->add_from('cobro_asunto')
			->add_select('id_cobro')
			->add_select('count(codigo_asunto)', 'cant_asuntos')
			->add_grouping('id_cobro');

		$Criteria->add_left_join_with_criteria($SubCriteria, 'ca2', CriteriaRestriction::equals('ca2.id_cobro', 'cobro.id_cobro'))
			->add_left_join_with('usuario', CriteriaRestriction::equals('cobro.id_usuario', 'usuario.id_usuario'))
			->add_left_join_with('contrato', CriteriaRestriction::equals('contrato.id_contrato', 'cobro.id_contrato'))
			->add_left_join_with('cliente', CriteriaRestriction::equals('contrato.codigo_cliente', 'cliente.codigo_cliente'))
			->add_left_join_with('grupo_cliente', CriteriaRestriction::equals('grupo_cliente.id_grupo_cliente', 'cliente.id_grupo_cliente'))
			->add_left_join_with(array('prm_area_proyecto', 'area'), CriteriaRestriction::equals('asunto.id_area_proyecto', 'area.id_area_proyecto'))
			->add_left_join_with(array('prm_tipo_proyecto', 'tipo'), CriteriaRestriction::equals('asunto.id_tipo_asunto', 'tipo.id_tipo_proyecto'));

		if (in_array('id_usuario_responsable', $this->agrupador)) {
			$Criteria->add_left_join_with(array('usuario', 'usuario_responsable'), CriteriaRestriction::equals('usuario_responsable.id_usuario', 'contrato.id_usuario_responsable'));
		}
		if (in_array('id_usuario_secundario', $this->agrupador)) {
			$Criteria->add_left_join_with(array('usuario', 'usuario_secundario'), CriteriaRestriction::equals('usuario_secundario.id_usuario', 'contrato.id_usuario_secundario'));
		}

		$Criteria
				->add_left_join_with(array('prm_moneda', 'moneda_base'), CriteriaRestriction::equals('moneda_base.moneda_base', '1'))
				->add_left_join_with('prm_estudio', CriteriaRestriction::equals('cobro.id_estudio', 'prm_estudio.id_estudio'));

		if ($this->tipo_dato == 'valor_por_cobrar') {
			$tabla = 'cobro';
		} else {
			$Criteria->add_left_join_with('documento', CriteriaRestriction::and_clause(
				CriteriaRestriction::equals('documento.id_cobro', 'cobro.id_cobro'),
				CriteriaRestriction::equals('documento.tipo_doc', "'N'")
			));
			$tabla = 'documento';
		}
		//moneda buscada
		$Criteria->add_left_join_with(array("{$tabla}_moneda", 'cobro_moneda'), CriteriaRestriction::and_clause(
			CriteriaRestriction::equals("cobro_moneda.id_{$tabla}", "{$tabla}.id_{$tabla}"),
			CriteriaRestriction::equals('cobro_moneda.id_moneda', "'{$this->id_moneda}'")
		));
		//moneda del cobro
		$Criteria->add_left_join_with(array("{$tabla}_moneda", 'cobro_moneda_cobro'), CriteriaRestriction::and_clause(
			CriteriaRestriction::equals("cobro_moneda_cobro.id_{$tabla}", "{$tabla}.id_{$tabla}"),
			CriteriaRestriction::equals('cobro_moneda_cobro.id_moneda', 'cobro.id_moneda')
		));
		//moneda_base
		$Criteria->add_left_join_with(array("{$tabla}_moneda", 'cobro_moneda_base'), CriteriaRestriction::and_clause(
			CriteriaRestriction::equals("cobro_moneda_base.id_{$tabla}", "{$tabla}.id_{$tabla}"),
			CriteriaRestriction::equals('cobro_moneda_base.id_moneda', 'moneda_base.id_moneda')
		));

		/* WHERE SIN USUARIOS NI TRABAJOS */
		unset($this->filtros['trabajo.cobrable']);
		unset($this->filtros['tramite.cobrable']);

		// todo crear método que identifique cuando ejectuar la query cobro
		unset($this->filtros['trabajo.id_usuario']);

		$and_wheres = array(
			$this->getWhere(TIPO_COBROS),
			CriteriaRestriction::equals('cobro.incluye_honorarios', '1'),
			CriteriaRestriction::greater_than('cobro.monto_subtotal', '0'),
		);

		$or_wheres = array(
			CriteriaRestriction::equals('cobro.total_minutos', '0'),
			CriteriaRestriction::is_null('cobro.total_minutos')
		);

		// FFF: Si se saca el reporte a proporcionalidad cliente, esta query debe traer los cobros con monto_thh=0. Si no, los con monto_thh_estandar=0
		if ($this->proporcionalidad == 'estandar') {
			$or_wheres[] = CriteriaRestriction::and_clause(
				CriteriaRestriction::equals('cobro.monto_thh_estandar', '0'),
				CriteriaRestriction::equals('cobro.forma_cobro', "'FLAT FEE'")
			);
		} else {
			$or_wheres[] = CriteriaRestriction::and_clause(
				CriteriaRestriction::equals('cobro.monto_thh', '0'),
				CriteriaRestriction::equals('cobro.forma_cobro', "'FLAT FEE'")
			);
		}
		$or_wheres[] = CriteriaRestriction::and_clause(
			CriteriaRestriction::equals('cobro.monto_thh', '0'),
			CriteriaRestriction::not_equal('cobro.forma_cobro', "'FLAT FEE'")
		);

		$and_wheres[] = CriteriaRestriction::or_clause($or_wheres);

		$Criteria->add_restriction(CriteriaRestriction::and_clause($and_wheres));

		if (!$this->vista) {
			$Criteria->add_grouping('agrupador_general')
				->add_grouping('id_cobro');
		} else {
			$Criteria->add_grouping('id_usuario')
				->add_grouping('id_cliente')
				->add_grouping('codigo_asunto');

			if ($this->requiereMoneda($this->tipo_dato)) {
				$Criteria->add_grouping('id_cobro');
			}

			foreach ($this->id_agrupador_cobro as $a) {
				$Criteria->add_grouping($a);
			}
		}
		return $Criteria->get_plain_query();
	}

	//Ejecuta la Query y guarda internamente las filas de resultado.
	public function Query() {
		$stringquery = "";
		$this->row = array();

		if (array_key_exists($this->tipo_dato, $this->newCalculation)) {
			pr("NEW CALCULATION");

			$filtersFields = array(
				'campo_fecha' => $this->parametros['campo_fecha'],
				'fecha_ini' => Utiles::fecha2sql($this->parametros['fecha_ini']),
				'fecha_fin' => Utiles::fecha2sql($this->parametros['fecha_fin'])
			);

			$grouperFields = explode('-', $this->vista);
			$selectFields = array();

			$calculator_name = $this->newCalculation[$this->tipo_dato];
			$reflectedClass = new ReflectionClass("{$calculator_name}DataCalculator");
			$calculator = $reflectedClass->newInstance(
				$this->sesion,
				$filtersFields,
				$grouperFields,
				$selectFields,
				$this->id_moneda,
				$this->proporcionalidad
			);

			$this->row = $calculator->calculate();

			pr($calculator->getWorksCriteria()->get_plain_query());
			$criteria = $calculator->getChargesCriteria();
			if (!empty($criteria)) {
				pr($criteria->get_plain_query());
			}

			return;
		}
		pr("OLD CALCULATION");

		// Obtiene todos los datos para Trabajos
		$resp = mysql_unbuffered_query($this->getQuery(TIPO_TRABAJOS), $this->sesion->dbh) or Utiles::errorSQL($this->getQuery(TIPO_TRABAJOS), __FILE__, __LINE__, $this->sesion->dbh);
		while ($row = mysql_fetch_assoc($resp)) {
			$this->row[] = $row;
		}

		// Obtiene todos los datos para Trámites
		$resp = mysql_unbuffered_query($this->getQuery(TIPO_TRAMITES), $this->sesion->dbh) or Utiles::errorSQL($this->getQuery(TIPO_TRAMITES), __FILE__, __LINE__, $this->sesion->dbh);
		while ($row = mysql_fetch_assoc($resp)) {
			$this->row[] = $row;
		}

		// Obtiene todos los datos para Cobros
		// En caso de filtrar por área o categoría de usuario no se toman en cuenta los cobros sin horas.
		$cobroquery = $this->cobroQuery();

		if (
			$this->requiereMoneda($this->tipo_dato)
			&& $this->tipo_dato != 'valor_hora'
			&& $this->tipo_dato != 'costo'
			&& $this->tipo_dato != 'costo_hh'
			&& !empty($cobroquery)
			&& !$this->filtros['usuario.id_area_usuario']['positivo'][0]
			&& !$this->filtros['usuario.id_categoria_usuario']['positivo'][0]
			&& !$this->ignorar_cobros_sin_horas ) {
				$resp = mysql_query($cobroquery, $this->sesion->dbh) or Utiles::errorSQL($cobroquery, __FILE__, __LINE__, $this->sesion->dbh);

				while ($row = mysql_fetch_assoc($resp)) {
					$this->row[] = $row;
				}
		}
	}

	/*
		Constructor de Arreglo Resultado. TIPO BARRAS.
		Entrega un arreglo lineal de Indices, Valores y Labels. Además indica Total.
	 */

	public function toBars() {
		$data = array();
		$data['total'] = 0;
		$data['total_divisor'] = 0;
		$data['barras'] = 0;

		/* El id debe ser unico para el dato, porque se agrupará el valor bajo ese nombre en el arreglo de datos */
		if ($this->agrupador[0] == 'id_usuario_responsable') {
			$id = 'id_usuario_responsable';
			$label = 'nombre_usuario_responsable';
		} elseif ($this->agrupador[0] == 'id_usuario_secundario') {
			$id = 'id_usuario_secundario';
			$label = 'nombre_usuario_secundario';
		} elseif ($this->agrupador[0] == 'prm_area_proyecto.glosa') {
			$id = 'glosa';
			$label = 'glosa';
		} else {
			$id = $this->id_agrupador[0];
			$label = $this->agrupador[0];
		}

		foreach ($this->row as $row) {
			$nombre = $row[$id];
			if (!isset($data[$nombre])) {
				$data[$nombre]['valor'] = 0;
				$data[$nombre]['valor_divisor'] = 0;
				$data[$nombre]['label'] = $row[$label];
			}
			$data[$nombre]['valor'] += number_format($row[$this->tipo_dato], 2, ".", "");

			$data['total'] += number_format($row[$this->tipo_dato], 2, ".", "");

			if ($this->usaDivisor()) {
				$data[$nombre]['valor_divisor'] += $row['valor_divisor'];
				$data['total_divisor'] += $row['valor_divisor'];
			}
		}

		foreach ($data as $nom => $dat)
			if (is_array($dat)) {
				++$data['barras'];
			}

		/* Rentabilidad y Valor Hora son resultados de una proporcionalidad: se debe dividir por otro valor */
		if ($this->usaDivisor()) {
			foreach ($data as $nom => $dat) {
				if (is_array($dat))
					if ($dat['valor_divisor'] == 0) {
						$data[$nom]['valor'] = 0;
					} else {
						$data[$nom]['valor'] = number_format($data[$nom]['valor'] / $data[$nom]['valor_divisor'], 2, ".", "");
					}
			}
			if ($data['total_divisor'] == 0) {
				$data['total'] = 0;
			} else {
				$data['total'] = number_format($data['total'] / $data['total_divisor'], 2, ".", "");
			}
			$data['promedio'] = $data['total'];
		} else {
			if ($data['barras'] > 0) {
				$data['promedio'] = number_format($data['total'] / $data['barras'], 2, ".", "");
			} else {
				$data['promedio'] = 0;
			}
		}
		return $data;
	}

	//Arregla espacios vacíos en Barras: retorna data con los labels extra de data2.
	public function fixBar($data, $data2) {
		foreach ($data2 as $k => $d) {
			if (!isset($data[$k])) {
				$data[$k]['valor'] = 0;
				$data[$k]['label'] = $d['label'];
			}
		}
		return $data;
	}

	//divide un valor por su valor_divisor
	public function dividir(&$a) {
		if ($a['valor_divisor'] == 0) {
			if ($a['valor'] != 0) {
				$a['valor'] = '99999!*';
			}
		} else {
			$a['valor'] = number_format($a['valor'] / $a['valor_divisor'], 2, ".", "");
		}
	}

	/* Entrega el label a usar para un agrupador */

	public function label($agrupador) {
		switch ($agrupador) {
			case 'id_usuario_responsable':
				return 'nombre_usuario_responsable';
			case 'id_usuario_secundario':
				return 'nombre_usuario_secundario';
			case 'prm_area_proyecto_glosa':
				return 'glosa';
		}
		return $agrupador;
	}

	/* Constructor de Arreglo Cruzado: Sólo vista Cliente o Profesional */

	public function toCross() {
		$r = array();
		$r['total'] = 0;
		$r['total_divisor'] = 0;

		$id = $this->id_agrupador[0];
		$id_col = $this->id_agrupador[5];
		$label = $this->agrupador[0];
		$label_col = $this->agrupador[5];

		if (empty($this->row)) {
			return $r;
		}

		foreach ($this->row as $row) {
			$identificador = $row[$id];
			$identificador_col = $row[$id_col];

			if (!isset($r['labels'][$identificador])) {
				$r['labels'][$identificador] = array();
			}
			if (!isset($r['labels_col'][$identificador_col])) {
				$r['labels_col'][$identificador_col] = array();
			}
		}
		ksort($r['labels_col']);

		foreach ($this->row as $row) {
			$nombre = $row[$label];
			$identificador = $row[$id];
			$nombre_col = $row[$label_col];
			$identificador_col = $row[$id_col];

			if (!isset($r['labels'][$identificador]['nombre'])) {
				$r['labels'][$identificador]['nombre'] = $nombre;
				$r['labels'][$identificador]['total'] = 0;
				$r['labels'][$identificador]['total_divisor'] = 0;
			}
			if (!isset($r['labels_col'][$identificador_col]['nombre'])) {
				$r['labels_col'][$identificador_col]['nombre'] = $nombre_col;
				$r['labels_col'][$identificador_col]['total'] = 0;
				$r['labels_col'][$identificador_col]['total_divisor'] = 0;
			}
			if (!isset($r['celdas'][$identificador][$identificador_col]['valor'])) {
				$r['celdas'][$identificador][$identificador_col]['valor'] = 0;

				if ($this->usaDivisor()) {
					$r['celdas'][$identificador][$identificador_col]['valor_divisor'] = 0;
				}
			}
			$r['celdas'][$identificador][$identificador_col]['valor'] += number_format($row[$this->tipo_dato], 2, ".", "");
			$r['labels'][$identificador]['total'] += number_format($row[$this->tipo_dato], 2, ".", "");
			$r['labels_col'][$identificador_col]['total'] += number_format($row[$this->tipo_dato], 2, ".", "");
			$r['total'] += number_format($row[$this->tipo_dato], 2, ".", "");

			if ($this->usaDivisor()) {
				$r['celdas'][$identificador][$identificador_col]['valor_divisor'] += $row['valor_divisor'];
				$r['labels'][$identificador]['total_divisor'] += $row['valor_divisor'];
				$r['labels_col'][$identificador_col]['total_divisor'] += $row['valor_divisor'];
				$r['total_divisor'] += $row['valor_divisor'];
			}
		}
		if ($this->usaDivisor()) {
			foreach ($r['labels'] as $ide => $nom) {
				if ($r['labels'][$ide]['total_divisor'] == 0) {
					if ($r['labels'][$ide]['total'] != 0) {
						$r['labels'][$ide]['total'] = '99999!*';
					}
				} else {
					$r['labels'][$ide]['total'] = number_format($r['labels'][$ide]['total'] /
						$r['labels'][$ide]['total_divisor'], 2, ".", "");
				}
				foreach ($r['labels_col'] as $ide_col => $nom_col) {
					if ($r['celdas'][$ide][$ide_col]['valor_divisor'] == 0) {
						if ($r['celdas'][$ide][$ide_col]['valor'] != 0) {
							$r['celdas'][$ide][$ide_col]['valor'] = '99999!*';
						}
					} else {
						$r['celdas'][$ide][$ide_col]['valor'] = number_format($r['celdas'][$ide][$ide_col]['valor'] / $r['celdas'][$ide][$ide_col]['valor_divisor'], 2, ".", "");
					}
				}
			}
			foreach ($r['labels_col'] as $ide_col => $nom_col) {
				if ($r['labels_col'][$ide_col]['total_divisor'] == 0) {
					if ($r['labels_col'][$ide_col]['total'] != 0) {
						$r['labels_col'][$ide_col]['total'] = '99999!*';
					}
				} else {
					$r['labels_col'][$ide_col]['total'] = number_format($r['labels_col'][$ide_col]['total'] / $r['labels_col'][$ide_col]['total_divisor'], 2, ".", "");
				}
			}
			if ($r['total_divisor'] == 0) {
				if ($r['total'] != 0) {
					$r['total'] = '99999!*';
				}
			} else {
				$r['total'] = number_format($r['total'] / $r['total_divisor'], 2, ".", "");
			}
		}
		return $r;
	}

	/*
		Constructor de Arreglo Resultado. TIPO PLANILLA.
		Entrega un arreglo con profundidad 4, de Indices, Valores y Labels. Además indica Total para cada subgrupo.
	 */

	public function toArray() {
		$r = array(); //Arreglo resultado
		$r['total'] = 0;
		$r['total_divisor'] = 0;

		$agrupador_temp = array('a', 'b', 'c', 'd', 'e', 'f');
		$id_temp = array('id_a', 'id_b', 'id_c', 'id_d', 'id_e', 'id_f');
		for ($k = 0; $k < 6; ++$k) {
			${$agrupador_temp[$k]} = $this->agrupador[$k];

			if ($this->agrupador[$k] == 'id_usuario_responsable') {
				${$agrupador_temp[$k]} = 'nombre_usuario_responsable';
			}
			if ($this->agrupador[$k] == 'id_usuario_secundario') {
				${$agrupador_temp[$k]} = 'nombre_usuario_secundario';
			} elseif ($this->agrupador[$k] == 'prm_area_proyecto.glosa') {
				${$agrupador_temp[$k]} = 'glosa';
			}
			${$id_temp[$k]} = ($this->id_agrupador[$k] == 'prm_area_proyecto.glosa' ? 'glosa' : $this->id_agrupador[$k]);
		}

		foreach ($this->row as $row) {
			//Reseteo valores
			if (!isset($r[$row[$a]]['valor'])) {
				$r[$row[$a]]['valor'] = 0.0;
				$r[$row[$a]]['valor_divisor'] = 0.0;
				$r[$row[$a]]['filas'] = 0;
			}
			if (!isset($r[$row[$a]][$row[$b]])) {
				$r[$row[$a]][$row[$b]]['valor'] = 0.0;
				$r[$row[$a]][$row[$b]]['valor_divisor'] = 0.0;
				$r[$row[$a]][$row[$b]]['filas'] = 0;
			}
			if (!isset($r[$row[$a]][$row[$b]][$row[$c]])) {
				$r[$row[$a]][$row[$b]][$row[$c]]['valor'] = 0.0;
				$r[$row[$a]][$row[$b]][$row[$c]]['valor_divisor'] = 0.0;
				$r[$row[$a]][$row[$b]][$row[$c]]['filas'] = 0;
			}
			if (!isset($r[$row[$a]][$row[$b]][$row[$c]][$row[$d]])) {
				$r[$row[$a]][$row[$b]][$row[$c]][$row[$d]]['valor'] = 0.0;
				$r[$row[$a]][$row[$b]][$row[$c]][$row[$d]]['valor_divisor'] = 0.0;
				$r[$row[$a]][$row[$b]][$row[$c]][$row[$d]]['filas'] = 0;
			}
			if (!isset($r[$row[$a]][$row[$b]][$row[$c]][$row[$d]][$row[$e]])) {
				$r[$row[$a]][$row[$b]][$row[$c]][$row[$d]][$row[$e]]['valor'] = 0.0;
				$r[$row[$a]][$row[$b]][$row[$c]][$row[$d]][$row[$e]]['valor_divisor'] = 0.0;
				$r[$row[$a]][$row[$b]][$row[$c]][$row[$d]][$row[$e]]['filas'] = 0;
			}
			if (!isset($r[$row[$a]][$row[$b]][$row[$c]][$row[$d]][$row[$e]][$row[$f]])) {
				$r[$row[$a]][$row[$b]][$row[$c]][$row[$d]][$row[$e]][$row[$f]]['valor_divisor'] = 0.0;
			}


			//Rentabilidad y Valor/Hora necesitan dividirse por otro total.
			if ($this->usaDivisor()) {
				$resultado = $row['valor_divisor'];
				if (is_numeric($resultado)) {
					$resultado = number_format($resultado, 2, ".", "");
				}

				$r[$row[$a]][$row[$b]][$row[$c]][$row[$d]][$row[$e]][$row[$f]]['valor_divisor'] += $resultado;
				$r[$row[$a]][$row[$b]][$row[$c]][$row[$d]][$row[$e]]['valor_divisor'] += $resultado;
				$r[$row[$a]][$row[$b]][$row[$c]][$row[$d]]['valor_divisor'] += $resultado; //Sumo el valor
				$r[$row[$a]][$row[$b]][$row[$c]]['valor_divisor'] += $resultado;
				$r[$row[$a]][$row[$b]]['valor_divisor'] += $resultado;
				$r[$row[$a]]['valor_divisor'] += $resultado;
				$r['total_divisor'] += $resultado;
			}

			//En Planilla, la rentabilidad se presenta como porcentaje.
			$resultado = $row[$this->tipo_dato];
			if (is_numeric($resultado)) {
				$resultado = number_format($resultado, 2, '.', '');
				if ($this->tipo_dato == 'rentabilidad' || $this->tipo_dato == 'rentabilidad_base') {
					$resultado *= 100;
				}
			}

			//Para las 4 profunidades, sumo el valor, agrego una fila, e indico el filtro correspondiente.
			//Debido a que hay dos fuentes: trabajos y cobros sin trabajos, la ultima fila pueden ser dos unidas.
			//si lo son, no se suma fila.
			$suma_fila = 1;
			if (!isset($r[$row[$a]][$row[$b]][$row[$c]][$row[$d]][$row[$e]][$row[$f]]['valor'])) {
				$r[$row[$a]][$row[$b]][$row[$c]][$row[$d]][$row[$e]][$row[$f]]['valor'] = 0;
			} else {
				$suma_fila = 0; //Hubo Cobro y Trabajo. Esta fila son dos unidas. (no suma fila en el arreglo).
			}

			$r[$row[$a]][$row[$b]][$row[$c]][$row[$d]][$row[$e]][$row[$f]]['valor'] += $resultado; //Sumo el valor
			$r[$row[$a]][$row[$b]][$row[$c]][$row[$d]][$row[$e]][$row[$f]]['filas'] = 1;
			$r[$row[$a]][$row[$b]][$row[$c]][$row[$d]][$row[$e]][$row[$f]]['filtro_campo'] = $id_f;
			$r[$row[$a]][$row[$b]][$row[$c]][$row[$d]][$row[$e]][$row[$f]]['filtro_valor'] = $row[$id_f];
			$r[$row[$a]][$row[$b]][$row[$c]][$row[$d]][$row[$e]]['valor'] += $resultado;
			$r[$row[$a]][$row[$b]][$row[$c]][$row[$d]][$row[$e]]['filas'] += $suma_fila;
			$r[$row[$a]][$row[$b]][$row[$c]][$row[$d]][$row[$e]]['filtro_campo'] = $id_e;
			$r[$row[$a]][$row[$b]][$row[$c]][$row[$d]][$row[$e]]['filtro_valor'] = $row[$id_e];
			$r[$row[$a]][$row[$b]][$row[$c]][$row[$d]]['valor'] += $resultado;
			$r[$row[$a]][$row[$b]][$row[$c]][$row[$d]]['filas'] += $suma_fila;
			$r[$row[$a]][$row[$b]][$row[$c]][$row[$d]]['filtro_campo'] = $id_d;
			$r[$row[$a]][$row[$b]][$row[$c]][$row[$d]]['filtro_valor'] = $row[$id_d];
			$r[$row[$a]][$row[$b]][$row[$c]]['valor'] += $resultado;
			$r[$row[$a]][$row[$b]][$row[$c]]['filas'] += $suma_fila;
			$r[$row[$a]][$row[$b]][$row[$c]]['filtro_campo'] = $id_c;
			$r[$row[$a]][$row[$b]][$row[$c]]['filtro_valor'] = $row[$id_c];
			$r[$row[$a]][$row[$b]]['valor'] += $resultado;
			$r[$row[$a]][$row[$b]]['filas'] += $suma_fila;
			$r[$row[$a]][$row[$b]]['filtro_campo'] = $id_b;
			$r[$row[$a]][$row[$b]]['filtro_valor'] = $row[$id_b];
			$r[$row[$a]]['valor'] += $resultado;
			$r[$row[$a]]['filas'] += $suma_fila;
			$r[$row[$a]]['filtro_campo'] = $id_a;
			$r[$row[$a]]['filtro_valor'] = $row[$id_a];
			$r['total'] += $resultado;
		}



		/* En el caso de la Rentabilidad y el Valor por Hora, debo dividir por el 'valor divisor', en cada una de las 6 profundidades (y luego en el Total) */
		if ($this->usaDivisor()) {
			foreach ($r as $ag1 => $a) {
				if (is_array($a)) {
					$this->dividir($r[$ag1]);
					foreach ($a as $ag2 => $b) {
						if (is_array($b)) {
							$this->dividir($r[$ag1][$ag2]);
							foreach ($b as $ag3 => $c) {
								if (is_array($c)) {
									$this->dividir($r[$ag1][$ag2][$ag3]);
									foreach ($c as $ag4 => $d) {
										if (is_array($d)) {
											$this->dividir($r[$ag1][$ag2][$ag3][$ag4]);
											foreach ($d as $ag5 => $e) {
												if (is_array($e)) {
													$this->dividir($r[$ag1][$ag2][$ag3][$ag4][$ag5]);
													foreach ($e as $ag6 => $f) {
														if (is_array($f))
															$this->dividir($r[$ag1][$ag2][$ag3][$ag4][$ag5][$ag6]);
													}
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
			if ($r['total_divisor'] == 0) {
				if ($r['total'] != 0) {
					$r['total'] = '99999!*';
				}
			} else {
				$r['total'] = number_format($r['total'] / $r['total_divisor'], 2, ".", "");
			}
		}
		return $r;
	}

	public static function rellenar(&$a, $b) {
		$a['valor'] = 0;
		$a['valor_divisor'] = 0;
		$a['filas'] = 0;
		$a['filtro_campo'] = $b['filtro_campo'];
		$a['filtro_valor'] = $b['filtro_valor'];
	}

	//Arregla espacios vacíos en Arreglos. Retorna data con los campos extra en data2 (rellenando con 0).
	public static function fixArray($data, $data2) {
		foreach ($data2 as $ag1 => $a) {
			if (is_array($a)) {
				foreach ($a as $ag2 => $b) {
					if (is_array($b)) {
						if (!isset($data[$ag1][$ag2])) {
							ReporteCriteria::rellenar($data[$ag1][$ag2], $data2[$ag1][$ag2]);
						}

						foreach ($b as $ag3 => $c) {
							if (is_array($c)) {
								if (!isset($data[$ag1][$ag2][$ag3])) {
									ReporteCriteria::rellenar($data[$ag1][$ag2][$ag3], $data2[$ag1][$ag2][$ag3]);
								}

								foreach ($c as $ag4 => $d) {
									if (is_array($d)) {
										if (!isset($data[$ag1][$ag2][$ag3][$ag4])) {
											ReporteCriteria::rellenar($data[$ag1][$ag2][$ag3][$ag4], $data2[$ag1][$ag2][$ag3][$ag4]);
										}

										foreach ($d as $ag5 => $e) {
											if (is_array($e)) {
												if (!isset($data[$ag1][$ag2][$ag3][$ag4][$ag5])) {
													ReporteCriteria::rellenar($data[$ag1][$ag2][$ag3][$ag4][$ag5], $data2[$ag1][$ag2][$ag3][$ag4][$ag5]);
												}
												foreach ($e as $ag6 => $f) {
													if (is_array($f)) {
														if (!isset($data[$ag1][$ag2][$ag3][$ag4][$ag5][$ag6])) {
															$data[$ag1][$ag2][$ag3][$ag4][$ag5][$ag6]['valor'] = 0;
															$data[$ag1][$ag2][$ag3][$ag4][$ag5][$ag6]['filas'] = 1;
															$data[$ag1][$ag2][$ag3][$ag4][$ag5]['filas'] +=1;
															$data[$ag1][$ag2][$ag3][$ag4]['filas'] +=1;
															$data[$ag1][$ag2][$ag3]['filas'] +=1;
															$data[$ag1][$ag2]['filas'] +=1;
															$data[$ag1]['filas'] +=1;
															$data[$ag1][$ag2][$ag3][$ag4][$ag5][$ag6]['filtro_campo'] = $data2[$ag1][$ag2][$ag3][$ag4][$ag5][$ag6]['filtro_campo'];
															$data[$ag1][$ag2][$ag3][$ag4][$ag5][$ag6]['filtro_valor'] = $data2[$ag1][$ag2][$ag3][$ag4][$ag5][$ag6]['filtro_valor'];
														}
													} else {
														if (!array_key_exists($ag6, $data[$ag1][$ag2][$ag3][$ag4][$ag5])) {
															$data[$ag1][$ag2][$ag3][$ag4][$ag5][$ag6]['valor'] = 0;
														}
													}
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
		return $data;
	}

	//Indica el Simbolo asociado al tipo de dato.
	public function setFiltros($filtros) {
		$this->parametros = $filtros;
		if ($filtros['clientes']) {
			foreach ($filtros['clientes'] as $cliente) {
				if ($cliente) {
					$this->addFiltro('cliente', 'codigo_cliente', $cliente);
				}
			}
		}

		if ($filtros['usuarios']) {
			foreach ($filtros['usuarios'] as $usuario) {
				if ($usuario) {
					$this->addFiltro('usuario', 'id_usuario', $usuario);
				}
			}
		}

		if ($filtros['tipos_asunto']) {
			foreach ($filtros['tipos_asunto'] as $tipo) {
				if ($tipo) {
					$this->addFiltro('asunto', 'id_tipo_asunto', $tipo);
				}
			}
		}

		if ($filtros['areas_asunto']) {
			foreach ($filtros['areas_asunto'] as $area) {
				if ($area) {
					$this->addFiltro('asunto', 'id_area_proyecto', $area);
				}
			}
		}

		if ($filtros['areas_usuario']) {
			foreach ($filtros['areas_usuario'] as $area_usuario) {
				if ($area_usuario) {
					$this->addFiltro('usuario', 'id_area_usuario', $area_usuario);
				}
			}
		}

		if ($filtros['categorias_usuario']) {
			foreach ($filtros['categorias_usuario'] as $categoria_usuario) {
				if ($categoria_usuario) {
					$this->addFiltro('usuario', 'id_categoria_usuario', $categoria_usuario);
				}
			}
		}

		if ($filtros['encargados']) {
			foreach ($filtros['encargados'] as $encargado) {
				if ($encargado) {
					$this->addFiltro('contrato', 'id_usuario_responsable', $encargado);
				}
			}
		}

		if ($filtros['estado_cobro']) {
			foreach ($filtros['estado_cobro'] as $estado) {
				if ($estado) {
					$this->addFiltro('cobro', 'estado', $estado);
				}
			}
		}

		$this->addRangoFecha($filtros['fecha_ini'], $filtros['fecha_fin']);

		if ($filtros['campo_fecha']) {
			$this->setCampoFecha($filtros['campo_fecha']);
		}

		$this->setTipoDato($filtros['dato']);
		$this->setVista($filtros['vista']);
		$this->setProporcionalidad($filtros['prop']);
		$this->id_moneda = $filtros['id_moneda'];
	}

	public function simboloTipoDato($tipo_dato, $sesion, $id_moneda = '1') {
		switch ($tipo_dato) {
			case "horas_trabajadas":
			case "horas_no_cobrables":
			case "horas_cobrables":
			case "horas_cobradas":
			case "horas_visibles":
			case "horas_spot":
			case "horas_convenio":
			case "horas_castigadas":
			case "horas_por_cobrar":
			case "horas_pagadas":
			case "horas_por_pagar":
			case "horas_incobrables":
				return "Hrs.";
			case "valor_por_cobrar":
			case "valor_cobrado_no_estandar":
			case "valor_cobrado":
			case "valor_tramites":
			case "valor_pagado":
			case "valor_por_pagar":
			case "valor_incobrable":
			case "diferencia_valor_estandar":
			case "valor_estandar":
			case "valor_pagado_parcial":
			case "valor_por_pagar_parcial":
			case "valor_trabajado_estandar":
			case "costo" :
				$moneda = new Moneda($sesion);
				$moneda->Load($id_moneda);
				return $moneda->fields['simbolo'];

			case "costo_hh" :
			case "valor_hora":
				$moneda = new Moneda($sesion);
				$moneda->Load($id_moneda);
				return $moneda->fields['simbolo'] . "/Hr.";
		}
		return "%";
	}

	//Indica el tipo de dato (No especifica moneda: se usa para simple comparación entre datos).
	public static function sTipoDato($tipo_dato) {
		switch ($tipo_dato) {
			case "horas_trabajadas":
			case "horas_no_cobrables":
			case "horas_cobrables":
			case "horas_cobradas":
			case "horas_visibles":
			case "horas_spot":
			case "horas_convenio":
			case "horas_castigadas":
			case "horas_por_cobrar":
			case "horas_pagadas":
			case "horas_por_pagar":
			case "horas_incobrables":
				return "Hr.";
			case "valor_por_cobrar":
			case "valor_cobrado":
			case "valor_tramites":
			case "valor_pagado":
			case "valor_por_pagar":
			case "valor_incobrable":
			case "diferencia_valor_estandar":
			case "valor_estandar":
			case "valor_pagado_parcial":
			case "valor_por_pagar_parcial":
			case "valor_trabajado_estandar":
			case "costo":
				return "$";
			case "valor_hora":
			case "costo_hh" :
				return "$/Hr.";
		}
		return "%";
	}

	//Indica la Moneda, de ser necesaria. Se usa para añadir a un string, si lo necesita.
	public static function unidad($tipo_dato, $sesion, $id_moneda = '1') {
		switch ($tipo_dato) {
			case "valor_por_cobrar":
			case "valor_cobrado":
			case "valor_tramites":
			case "valor_cobrado_no_estandar":
			case "valor_pagado":
			case "valor_por_pagar":
			case "valor_incobrable":
			case "valor_hora":
			case "valor_pagado_parcial":
			case "valor_por_pagar_parcial":
			case "valor_trabajado_estandar":
			case "costo":
				$moneda = new Moneda($sesion);
				$moneda->Load($id_moneda);
				return " - " . $moneda->fields['glosa_moneda'];
		}
		return "";
	}

	//Transforma las horas a hh:mm en el caso de que tenga el conf y que sean horas
	public static function FormatoValor($sesion, $valor, $tipo_dato = "horas_", $tipo_reporte = "", $formato_valor = array('cifras_decimales' => 2, 'miles' => '.', 'decimales' => ',')) {
		if (Conf::GetConf($sesion, 'MostrarSoloMinutos') && strpos($tipo_dato, "oras_")) {
			$valor_horas = floor($valor);
			$valor_minutos = number_format((($valor - $valor_horas) * 60), 0);
			if ($tipo_reporte == "excel") {
				$valor_tiempo = ($valor_horas / 24) + ($valor_minutos / (60 * 24));
			} else {
				$valor_tiempo = sprintf('%02d', $valor_horas) . ":" . sprintf('%02d', $valor_minutos);
			}
			return $valor_tiempo;
		}
		if (strpos($tipo_dato, 'valor_') !== false || strpos($tipo_dato, 'costo') !== false) {
			return number_format($valor, $formato_valor['cifras_decimales'], $formato_valor['decimales'], $formato_valor['miles']);
		}
		return $valor;
	}

	private $columns = array(
		'profesional',
		'username',
		'id_usuario',
		'id_cliente',
		'codigo_cliente',
		'glosa_cliente',
		'codigo_cliente_secundario',
		'glosa',
		'glosa_asunto',
		'glosa_asunto_con_codigo',
		'codigo_asunto',
		'area_usuario',
		'categoria_usuario',
		'id_estudio',
		'agrupador_general',
		'nombre_usuario_responsable',
		'id_usuario_responsable',
		'id_usuario_secundario',
		'nombre_usuario_secundario',
		'id_contrato',
		'tipo_asunto',
		'area_asunto',
		'solicitante',
		'grupo_o_cliente',
		'id_grupo_cliente',
		'glosa_grupo_cliente',
		'glosa_cliente_asunto',
		'glosa_estudio',
		'fecha_final',
		'mes',
		'mes_reporte',
		'dia_reporte',
		'dia_corte',
		'dia_emision',
		'mes_emision',
		'id_cobro',
		'estado',
		'forma_cobro',
		'glosa_actividad',
		'area_trabajo',
		'id_trabajo'
	);

	private function rightValueForColumn($key, $type) {
		if (!in_array($key, $this->columns)) {
			return "'La columna {$key} no está en el arreglo de columnas'";
		}
		$column_value = null;
		$undefined = $this->undefined_value;
		$to_emit = $this->to_emit;
		$date_field = $this->alt($this->campo_fecha_cobro, $this->campo_fecha_cobro_2);
		switch ($key) {
			case "profesional":
				if ($type == TIPO_TRABAJOS || $type == TIPO_TRAMITES) {
					$column_value = $this->dato_usuario;
				}
				if ($type == TIPO_COBROS) {
					$column_value = $undefined;
				}
				break;
			case "username":
				if ($type == TIPO_TRABAJOS) {
					$column_value = 'usuario.username';
				}
				if ($type == TIPO_TRAMITES) {
					$column_value = 'usuario.username';
				}
				if ($type == TIPO_COBROS) {
					$column_value = $undefined;
				}
				break;
			case "id_usuario":
				if ($type == TIPO_TRABAJOS) {
					$column_value = 'usuario.id_usuario';
				}
				if ($type == TIPO_TRAMITES) {
					$column_value = 'usuario.id_usuario';
				}
				if ($type == TIPO_COBROS) {
					$column_value = -1;
				}
				break;
			case "id_cliente":
				$column_value = 'cliente.id_cliente';
				break;
			case "codigo_cliente":
				$column_value = 'cliente.codigo_cliente';
				break;
			case "glosa_cliente":
				$column_value = 'cliente.glosa_cliente';
				break;
			case "codigo_cliente_secundario":
				if (in_array('codigo_cliente_secundario', $this->agrupador)) {
					$column_value = 'cliente.codigo_cliente_secundario';
				}
				break;
			case "glosa":
				if (in_array('prm_area_proyecto.glosa', $this->agrupador)) {
					if ($type == TIPO_TRABAJOS) {
						$column_value = 'prm_area_proyecto.glosa';
					}
					if ($type == TIPO_TRAMITES) {
						$column_value = 'prm_area_proyecto.glosa';
					}
					if ($type == TIPO_COBROS) {
						$column_value = $undefined;
					}
				}
				break;
			case "glosa_asunto":
				$column_value = 'asunto.glosa_asunto';
				break;
			case "glosa_asunto_con_codigo":
				$column_value = "CONCAT({$this->dato_codigo_asunto}, ': ', asunto.glosa_asunto)";
				break;
			case "codigo_asunto":
				if ($type == TIPO_TRABAJOS) {
						$column_value = $this->dato_codigo_asunto;
				}
				if ($type == TIPO_TRAMITES) {
					$column_value = $this->dato_codigo_asunto;
				}
				if ($type == TIPO_COBROS) {
					$column_value = 'asunto.codigo_asunto';
				}
				break;
			case "area_usuario":
				if (in_array('area_usuario', $this->agrupador)) {
					if ($type == TIPO_TRABAJOS) {
						$column_value = 'IFNULL(prm_area_usuario.glosa,\'-\')';
					}
					if ($type == TIPO_TRAMITES) {
						$column_value = 'IFNULL(prm_area_usuario.glosa,\'-\')';
					}
					if ($type == TIPO_COBROS) {
						$column_value = $undefined;
					}
				}
			case "categoria_usuario":
				if (in_array('categoria_usuario', $this->agrupador)) {
					if ($type == TIPO_TRABAJOS) {
						$column_value = 'IFNULL(prm_categoria_usuario.glosa_categoria,\'-\')';
					}
					if ($type == TIPO_TRAMITES) {
						$column_value = 'IFNULL(prm_categoria_usuario.glosa_categoria,\'-\')';
					}
					if ($type == TIPO_COBROS) {
						$column_value = $undefined;
					}
				}
				break;
			case "id_estudio":
				if ($type == TIPO_TRABAJOS) {
					$column_value = CriteriaRestriction::ifnull('cobro.id_estudio', CriteriaRestriction::ifnull('estudio_contrato.id_estudio', $undefined));
				}
				if ($type == TIPO_TRAMITES) {
					$column_value = CriteriaRestriction::ifnull('cobro.id_estudio', CriteriaRestriction::ifnull('estudio_contrato.id_estudio', $undefined));
				}
				if ($type == TIPO_COBROS) {
					$column_value = CriteriaRestriction::ifnull('cobro.id_estudio', $undefined);
				}
				break;
			case "agrupador_general":
				if (!$this->vista) {
					$column_value = $undefined;
				}
				break;
			case "nombre_usuario_responsable":
				if (in_array('id_usuario_responsable', $this->agrupador)) {
					$usuario_responsable = $this->nombre_usuario('usuario_responsable');
					if ($type == TIPO_TRABAJOS) {
						$column_value = "IF(usuario_responsable.id_usuario IS NULL, 'Sin Resposable', {$usuario_responsable})";
					}
					if ($type == TIPO_TRAMITES) {
						$column_value = "IF(usuario_responsable.id_usuario IS NULL, 'Sin Resposable', {$usuario_responsable})";
					}
					if ($type == TIPO_COBROS) {
						$column_value = $usuario_responsable;
					}
				}
				break;
			case "id_usuario_responsable":
				if (in_array('id_usuario_responsable', $this->agrupador)) {
					$column_value = 'usuario_responsable.id_usuario';
				}
				break;
			case "id_usuario_secundario":
				if (in_array('id_usuario_secundario', $this->agrupador)) {
					$column_value = 'usuario_secundario.id_usuario';
				}
				break;
			case "nombre_usuario_secundario":
				if (in_array('id_usuario_secundario', $this->agrupador)) {
					$usuario_secundario = $this->nombre_usuario('usuario_secundario');
					if ($type == TIPO_TRABAJOS) {
						$column_value = "IF(usuario_secundario.id_usuario IS NULL, 'Sin Resposable Secundario', {$usuario_secundario})";
					}
					if ($type == TIPO_TRAMITES) {
						$column_value = "IF(usuario_secundario.id_usuario IS NULL, 'Sin Resposable Secundario', {$usuario_secundario})";
					}
					if ($type == TIPO_COBROS) {
						$column_value = $usuario_secundario;
					}
				}
				break;
			case "id_contrato":
				$column_value = 'contrato.id_contrato';
				break;
			case "tipo_asunto":
				$column_value = 'tipo.glosa_tipo_proyecto';
				break;
			case "area_asunto":
				$column_value = 'area.glosa';
				break;
			case "solicitante":
				$usuario_secundario = $this->nombre_usuario('usuario_secundario');
				if ($type == TIPO_TRABAJOS) {
					$column_value = 'trabajo.solicitante';
				}
				if ($type == TIPO_TRAMITES) {
					$column_value = 'tramite.solicitante';
				}
				if ($type == TIPO_COBROS) {
					$column_value = $undefined;
				}
				break;
			case "grupo_o_cliente":
				$column_value = CriteriaRestriction::ifnull('grupo_cliente.glosa_grupo_cliente', 'cliente.glosa_cliente');
				break;
			case "id_grupo_cliente":
				$column_value = 'grupo_cliente.id_grupo_cliente';
				break;
			case "glosa_grupo_cliente":
				$column_value = CriteriaRestriction::ifnull('grupo_cliente.glosa_grupo_cliente', "'-'");
				break;
			case "glosa_cliente_asunto":
				$column_value = "CONCAT(cliente.glosa_cliente, ' - ', asunto.codigo_asunto, ' ', asunto.glosa_asunto)";
				break;
			case "glosa_estudio":
				if ($type == TIPO_TRABAJOS) {
					$column_value = CriteriaRestriction::ifnull('prm_estudio.glosa_estudio', CriteriaRestriction::ifnull('estudio_contrato.glosa_estudio', $undefined));
				}
				if ($type == TIPO_TRAMITES) {
					$column_value = CriteriaRestriction::ifnull('prm_estudio.glosa_estudio', CriteriaRestriction::ifnull('estudio_contrato.glosa_estudio', $undefined));
				}
				if ($type == TIPO_COBROS) {
					$column_value = CriteriaRestriction::ifnull('prm_estudio.glosa_estudio', $undefined);
				}
				break;
			case "fecha_final":
				if ($type == TIPO_TRABAJOS) {
					$column_value = 'trabajo.fecha';
				}
				if ($type == TIPO_TRAMITES) {
					$column_value = 'tramite.fecha';
				}
				if ($type == TIPO_COBROS) {
					$column_value = $date_field;
				}
				break;
			case "mes":
				if ($type == TIPO_TRABAJOS) {
					$column_value = 'MONTH(trabajo.fecha)';
				}
				if ($type == TIPO_TRAMITES) {
					$column_value = 'MONTH(tramite.fecha)';
				}
				if ($type == TIPO_COBROS) {
					$column_value = "MONTH({$date_field})";
				}
				break;
			case 'mes_reporte':
				if (in_array('mes_reporte', $this->agrupador)) {
					if ($type == TIPO_TRABAJOS) {
						$column_value = "DATE_FORMAT(trabajo.fecha, '%m-%Y')";
					}
					if ($type == TIPO_TRAMITES) {
						$column_value = "DATE_FORMAT(tramite.fecha, '%m-%Y')";
					}
					if ($type == TIPO_COBROS) {
						$column_value = "DATE_FORMAT({$date_field}, '%m-%Y')";
					}
				}
				break;
			case 'dia_reporte':
				if (in_array('dia_reporte', $this->agrupador)) {
					if ($type == TIPO_TRABAJOS) {
						$column_value = "DATE_FORMAT(trabajo.fecha, '%d-%m-%Y')";
					}
					if ($type == TIPO_TRAMITES) {
						$column_value = "DATE_FORMAT(tramite.fecha, '%d-%m-%Y')";
					}
					if ($type == TIPO_COBROS) {
						$column_value = "DATE_FORMAT({$date_field}, '%d-%m-%Y')";
					}
				}
				break;
			case 'dia_corte':
				if (in_array('dia_corte', $this->agrupador)) {
					$column_value = "DATE_FORMAT(cobro.fecha_fin , '%d-%m-%Y')";
				}
				break;
			case 'dia_emision':
				if (in_array('dia_emision', $this->agrupador)) {
					$column_value = "IF(cobro.fecha_emision IS NULL, {$to_emit}, DATE_FORMAT(cobro.fecha_emision, '%d-%m-%Y'))";
				}
				break;
			case 'mes_emision':
				if (in_array('mes_emision', $this->agrupador)) {
					$column_value = "IF(cobro.fecha_emision IS NULL, {$to_emit}, DATE_FORMAT(cobro.fecha_emision, '%m-%Y'))";
				}
				break;
			case 'id_cobro':
				if ($type == TIPO_TRABAJOS) {
					$column_value = "IFNULL(cobro.id_cobro, 'Indefinido')";
				}
				if ($type == TIPO_TRAMITES) {
					$column_value = "IFNULL(cobro.id_cobro, 'Indefinido')";
				}
				if ($type == TIPO_COBROS) {
					$column_value = 'cobro.id_cobro';
				}
				break;
			case 'estado':
				if ($type == TIPO_TRABAJOS) {
					$column_value = "IFNULL(cobro.estado, 'Indefinido')";
				}
				if ($type == TIPO_TRAMITES) {
					$column_value = "IFNULL(cobro.estado, 'Indefinido')";
				}
				if ($type == TIPO_COBROS) {
					$column_value = 'cobro.estado';
				}
				break;
			case 'forma_cobro':
				if ($type == TIPO_TRABAJOS) {
					$column_value = "IFNULL(cobro.forma_cobro, 'Indefinido')";
				}
				if ($type == TIPO_TRAMITES) {
					$column_value = "IFNULL(cobro.forma_cobro, 'Indefinido')";
				}
				if ($type == TIPO_COBROS) {
					$column_value = 'cobro.forma_cobro';
				}
				break;
			case 'glosa_actividad':
				if (Conf::GetConf($this->sesion, 'UsoActividades')) {
					if ($type == TIPO_TRABAJOS) {
						$column_value = "IFNULL(NULLIF(IFNULL(actividad.glosa_actividad, 'Indefinido' ), ' '), 'Indefinido' )";
					}
					if ($type == TIPO_TRAMITES) {
						$column_value = "IFNULL(NULLIF(IFNULL(actividad.glosa_actividad, 'Indefinido' ), ' '), 'Indefinido' )";
					}
					if ($type == TIPO_COBROS) {
						$column_value = "' - '";
					}
				}
				break;
			case 'area_trabajo':
				if (in_array('area_trabajo', $this->agrupador)) {
					if ($type == TIPO_TRABAJOS) {
						$column_value = "IFNULL(prm_area_trabajo.glosa, 'Indefinido')";
					}
					if ($type == TIPO_TRAMITES) {
						$column_value = $undefined;
					}
					if ($type == TIPO_COBROS) {
						$column_value = "' - '";
					}
				}
				break;
			case 'id_trabajo':
				if (in_array('id_trabajo', $this->agrupador)) {
					if ($type == TIPO_TRABAJOS) {
						$column_value = 'trabajo.id_trabajo';
					}
					if ($type == TIPO_TRAMITES) {
						$column_value = $undefined;
					}
					if ($type == TIPO_COBROS) {
						$column_value = "' - '";
					}
				}
				break;
			default:
				$column_value = "'La columna {$key} no está definida'";

		}
		return $column_value;
	}

	private function addCommonColumnsToCriteria($Criteria, $type) {
		foreach ($this->columns as $column) {
			$column_value = $this->rightValueForColumn($column, $type);
			if (!is_null($column_value)) {
				$Criteria->add_select($column_value, $column);
			}
		}
	}

	private function addCurrenciesToCriteria($Criteria) {
		$Criteria
			->add_select('cobro_moneda.id_moneda')
			->add_select('cobro_moneda.tipo_cambio')
			->add_select('cobro_moneda_base.id_moneda')
			->add_select('cobro_moneda_base.tipo_cambio')
			->add_select('cobro_moneda_cobro.id_moneda')
			->add_select('cobro_moneda_cobro.tipo_cambio');
	}

	private function AddCommonDataTypeToCriteria($Criteria, $type, $data_type) {

		$values = array(
			"estandar" => array(
				"tarifa" => array(
					TIPO_TRABAJOS => 'tarifa_hh_estandar',
					TIPO_TRAMITES => 'tarifa_tramite_estandar'
				),
				"div_thh" => array(
					TIPO_TRABAJOS => "IF(cobro.monto_thh > 0, cobro.monto_thh,
															IF(cobro.monto_trabajos > 0, cobro.monto_trabajos, 1))",
					TIPO_TRAMITES =>  "IF(cobro.monto_tramites > 0, cobro.monto_tramites, 1)"
				)
			),
			"cliente" => array(
				"tarifa" => array(
					TIPO_TRABAJOS => "tarifa_hh",
					TIPO_TRAMITES => "tarifa_tramite"
				),
				"div_thh" => array(
					TIPO_TRABAJOS => "IF(cobro.monto_thh > 0, cobro.monto_thh,
															IF(cobro.monto_trabajos > 0, cobro.monto_trabajos, 1))",
					TIPO_TRAMITES => "IF(cobro.monto_tramites > 0, cobro.monto_tramites, 1)"
				)
			),
			"monto_estandar" => array(
				TIPO_TRABAJOS => "SUM(trabajo.tarifa_hh_estandar * (TIME_TO_SEC( duracion_cobrada)/3600)
													* (cobro_moneda_cobro.tipo_cambio/cobro_moneda.tipo_cambio))",
				TIPO_TRAMITES => "SUM(tramite.tarifa_tramite_estandar * (cobro_moneda_cobro.tipo_cambio/cobro_moneda.tipo_cambio))"
			),
			"monto_trabajado_estandar" => array(
				TIPO_TRABAJOS => "SUM((TIME_TO_SEC(duracion) / 3600) *
											IF(
												cobro.id_cobro IS NULL OR cobro_moneda_cobro.tipo_cambio IS NULL OR cobro_moneda.tipo_cambio IS NULL,
												trabajo.tarifa_hh_estandar * (moneda_por_cobrar.tipo_cambio / moneda_display.tipo_cambio),
												trabajo.tarifa_hh_estandar * (cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)
											))",
				TIPO_TRAMITES => "SUM(
											IF(
												cobro.id_cobro IS NULL OR cobro_moneda_cobro.tipo_cambio IS NULL OR cobro_moneda.tipo_cambio IS NULL,
												tramite.tarifa_tramite_estandar * (moneda_por_cobrar.tipo_cambio / moneda_display.tipo_cambio),
												tramite.tarifa_tramite_estandar * (cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)
											))"
			)
		);

		$tarifa = $values[$this->proporcionalidad]["tarifa"][$type];
		$div_thh = $values[$this->proporcionalidad]["div_thh"][$type];
		$monto_estandar = $values["monto_estandar"][$type];
		$monto_trabajado_estandar = $values["monto_trabajado_estandar"][$type];

		if ($type == TIPO_TRABAJOS) {
			$trabajos_amount = "((documento.monto_trabajos / (documento.monto_trabajos + documento.monto_tramites)) * documento.subtotal_sin_descuento)";
			$monto_por_pagar_parcial = "SUM(({$tarifa} * TIME_TO_SEC(duracion_cobrada) / 3600)
						* (({$trabajos_amount} * cobro_moneda_documento.tipo_cambio)
							/ ({$div_thh} * cobro_moneda_cobro.tipo_cambio))
						* (documento.saldo_honorarios / documento.honorarios) * (cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio))";

			$monto_honorarios = "SUM(({$tarifa} * TIME_TO_SEC(duracion_cobrada) / 3600)
										* (({$trabajos_amount} * cobro_moneda_documento.tipo_cambio)
										/ ({$div_thh} * cobro_moneda_cobro.tipo_cambio))
										* (cobro_moneda_cobro.tipo_cambio/cobro_moneda.tipo_cambio))";

			if ($this->tipo_dato == 'valor_por_cobrar') {
				$trabajos_amount = "((cobro.monto_trabajos / (cobro.monto_trabajos + cobro.monto_tramites)) * cobro.monto_subtotal)";
				$monto_honorarios = "SUM((({$tarifa} * TIME_TO_SEC(duracion_cobrada) / 3600)
											* ({$trabajos_amount} / {$div_thh}))
											* (cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio))";
			}
		}

		if ($type == TIPO_TRAMITES) {
			$tramites_amount = "((documento.monto_tramites / (documento.monto_trabajos + documento.monto_tramites)) * documento.subtotal_sin_descuento)";
			$monto_por_pagar_parcial = "SUM(({$tarifa})
						* (({$tramites_amount} * cobro_moneda_documento.tipo_cambio)
							/ ({$div_thh} * cobro_moneda_cobro.tipo_cambio))
						* (documento.saldo_honorarios / documento.honorarios) * (cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio))";

			$monto_honorarios = "SUM(({$tarifa})
										* (({$tramites_amount} * cobro_moneda_documento.tipo_cambio)
											/ ({$div_thh} * cobro_moneda_cobro.tipo_cambio))
										* (cobro_moneda_cobro.tipo_cambio/cobro_moneda.tipo_cambio))";
			if ($this->tipo_dato == 'valor_por_cobrar') {
				$tramites_amount = "((cobro.monto_tramites / (cobro.monto_trabajos + cobro.monto_tramites)) * cobro.monto_subtotal)";
				$monto_honorarios = "SUM(((tarifa_tramite)
											* ({$tramites_amount} / {$div_thh}))
											* (cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio))";
			}
		}

		switch ($data_type) {
			case 'valor_cobrado_no_estandar':
				if ($type == TIPO_TRABAJOS) {
					$Criteria->add_select("SUM((IF(cobro.forma_cobro='FLAT FEE', tarifa_hh_estandar, tarifa_hh) * TIME_TO_SEC(duracion_cobrada) / 3600) * (cobro_moneda_cobro.tipo_cambio/cobro_moneda.tipo_cambio))", $data_type);
				}
				if ($type == TIPO_TRAMITES) {
					$Criteria->add_select("SUM(IF(cobro.forma_cobro='FLAT FEE', tramite_tarifa_estandar, tramite_tarifa) * (cobro_moneda_cobro.tipo_cambio/cobro_moneda.tipo_cambio))", $data_type);
				}
				break;
			case 'horas_trabajadas':
			case 'horas_no_cobrables':
				if ($type == TIPO_TRABAJOS) {
					$Criteria->add_select('SUM(TIME_TO_SEC(trabajo.duracion)) / 3600', $data_type);
				}
				if ($type == TIPO_TRAMITES) {
					$Criteria->add_select('0', $data_type);
				}
				break;
			case 'horas_cobrables':
			case 'horas_spot':
			case 'horas_convenio':
				if ($type == TIPO_TRABAJOS) {
					$Criteria->add_select('SUM(TIME_TO_SEC(trabajo.duracion_cobrada)) / 3600', $data_type);
				}
				if ($type == TIPO_TRAMITES) {
					$Criteria->add_select('0', $data_type);
				}
				break;
			case 'costo':
				if ($type == TIPO_TRABAJOS) {
					$Criteria->add_select('IFNULL((cobro_moneda_base.tipo_cambio / cobro_moneda.tipo_cambio), 1) * SUM(cut.costo_hh * TIME_TO_SEC(trabajo.duracion ) / 3600)', $data_type);
				}
				if ($type == TIPO_TRAMITES) {
					$Criteria->add_select('0', $data_type);
				}
				break;
			case 'horas_castigadas':
				if ($type == TIPO_TRABAJOS) {
					$Criteria->add_select('SUM(TIME_TO_SEC(trabajo.duracion) - TIME_TO_SEC(trabajo.duracion_cobrada)) / 3600', $data_type);
				}
				if ($type == TIPO_TRAMITES) {
					$Criteria->add_select('0', $data_type);
				}
				break;
			case 'horas_visibles':
			case 'horas_cobradas':
			case 'horas_por_cobrar':
			case 'horas_pagadas':
			case 'horas_por_pagar':
			case 'horas_incobrables':
				if ($type == TIPO_TRABAJOS) {
					$Criteria->add_select('SUM(TIME_TO_SEC(trabajo.duracion_cobrada)) / 3600', $data_type);
				}
				if ($type == TIPO_TRAMITES) {
					$Criteria->add_select('0', $data_type);
				}
				break;
			case 'valor_por_cobrar':
				if ($type == TIPO_TRABAJOS) {
					//Si el trabajo está en cobro CREADO, se usa la formula de ese cobro. Si no está, se usa la tarifa de la moneda del contrato, y se convierte según el tipo de cambio actual de la moneda que se está mostrando.
					$Criteria->add_select("IF( cobro.id_cobro IS NOT NULL, {$monto_honorarios},
						SUM(
							usuario_tarifa.tarifa
							* TIME_TO_SEC( duracion_cobrada )
							* moneda_por_cobrar.tipo_cambio
							/ (moneda_display.tipo_cambio * 3600)
						)
					)", $data_type);
				}
				if ($type == TIPO_TRAMITES) {
					$Criteria->add_select("IF( cobro.id_cobro IS NOT NULL, {$monto_honorarios},
						SUM(tramite.tarifa_tramite)
							* moneda_por_cobrar.tipo_cambio
							/ (moneda_display.tipo_cambio)
						)", $data_type);
				}
				break;
			case 'valor_trabajado_estandar':
				// Esto obtiene el valor de $monto_trabajado_estanar que ya contempala el tipo de dadtos
				$Criteria->add_select($monto_trabajado_estandar, $data_type);
				break;
			case 'valor_cobrado':
			case 'valor_incobrable':
				// Esto obtiene el valor de $monto_honorarios que ya contempala el tipo de dadtos
				$Criteria->add_select($monto_honorarios, $data_type);
				break;
			case 'valor_tramites':
				if ($type == TIPO_TRABAJOS) {
					$Criteria->add_select("0", $data_type);
				}
				if ($type == TIPO_TRAMITES) {
					$Criteria->add_select($monto_honorarios, $data_type);
				}
				break;
			case 'valor_por_pagar':
			case 'valor_por_pagar_parcial':
				// Esto obtiene el valor de $monto_por_pagar_parcial que ya contempala el tipo de dadtos
				$Criteria->add_select($monto_por_pagar_parcial, $data_type);
				break;
			case 'valor_pagado':
			case 'valor_pagado_parcial':
				// Esto obtiene el valor de $monto_honorarios y $monto_por_pagar_parcial que ya contempala el tipo de dadtos
				$Criteria->add_select("$monto_honorarios - $monto_por_pagar_parcial", $data_type);
				break;
			case 'valor_estandar':
				// Esto obtiene el valor de $monto_estandar que ya contempala el tipo de dadtos
				$Criteria->add_select($monto_estandar, $data_type);
				break;
			case 'diferencia_valor_estandar':
				$Criteria->add_select("$monto_honorarios - $monto_estandar", $data_type);
				break;
			case 'rentabilidad':
				$Criteria
					->add_select($monto_estandar, 'valor_divisor')
					->add_select($monto_honorarios, $data_type);
				break;
			case 'rentabilidad_base':
				$Criteria
					->add_select($monto_trabajado_estandar, 'valor_divisor')
					->add_select("IF( cobro.estado IN ('EMITIDO','FACTURADO','ENVIADO AL CLIENTE','PAGO PARCIAL','PAGADO') , $monto_honorarios, 0)", $data_type);
				break;
			case 'valor_hora':
				if ($type == TIPO_TRABAJOS) {
					// Se necesita resultado extra: las horas cobradas
					$Criteria
						->add_select('SUM((TIME_TO_SEC(duracion_cobrada) / 3600))', 'valor_divisor')
						->add_select($monto_honorarios , $data_type);
				}
				if ($type == TIPO_TRAMITES) {
					$Criteria
						->add_select('1', 'valor_divisor')
						->add_select($monto_honorarios, $data_type);
				}
				break;
			case 'costo_hh':
				if ($type == TIPO_TRABAJOS) {
					$Criteria
						->add_select('SUM((TIME_TO_SEC(duracion) / 3600))', 'valor_divisor')
						->add_select('SUM(IFNULL((cobro_moneda_base.tipo_cambio / cobro_moneda.tipo_cambio), 1) * cut.costo_hh * (TIME_TO_SEC(duracion) / 3600))', $data_type);
				}
				if ($type == TIPO_TRAMITES) {
					$Criteria
						->add_select('1', 'valor_divisor')
						->add_select('0', $data_type);
				}
				break;
		}
	}

	//SELECT en string de Query. Elige el tipo de dato especificado.
	private function setSelect($Criteria, $type) {

		$this->addCommonColumnsToCriteria($Criteria, $type);

		if ($this->requiereMoneda($this->tipo_dato)) {
			$this->addCurrenciesToCriteria($Criteria);
		}

		$this->AddCommonDataTypeToCriteria($Criteria, $type, $this->tipo_dato);

	}

	//FROM en string de Query. Incluye las tablas necesarias.
	private function setFrom($Criteria, $type) {
		$table = 'trabajo';
		if ($type == TIPO_TRAMITES) {
			$table = 'tramite';
		}

		$Criteria->add_from($table);

		$Criteria
			->add_left_join_with('usuario', CriteriaRestriction::equals('usuario.id_usuario', "{$table}.id_usuario"))
			->add_left_join_with('asunto', CriteriaRestriction::equals('asunto.codigo_asunto', "{$table}.codigo_asunto"))
			->add_left_join_with('cobro', CriteriaRestriction::equals("{$table}.id_cobro", 'cobro.id_cobro'))
			->add_left_join_with('contrato', CriteriaRestriction::equals('contrato.id_contrato', CriteriaRestriction::ifnull('cobro.id_contrato', 'asunto.id_contrato')));

		if ($type == TIPO_TRABAJOS) {
			$Criteria->add_left_join_with(array('usuario_costo_hh', 'cut'), CriteriaRestriction::and_clause(
				CriteriaRestriction::equals("{$table}.id_usuario", 'cut.id_usuario'),
				CriteriaRestriction::equals("date_format({$table}.fecha, '%Y%m')", 'cut.yearmonth')
			));
		}

		if (in_array($this->tipo_dato, array('valor_por_cobrar', 'valor_trabajado_estandar', 'rentabilidad_base'))) {
			$Criteria
				->add_left_join_with(array('prm_moneda', 'moneda_por_cobrar'), CriteriaRestriction::equals('moneda_por_cobrar.id_moneda', 'contrato.id_moneda'))
				->add_left_join_with(array('prm_moneda', 'moneda_display'), CriteriaRestriction::equals('moneda_display.id_moneda', $this->id_moneda));

			if ($type == TIPO_TRABAJOS) {
				$on_usuario_tarifa = CriteriaRestriction::and_clause(
					CriteriaRestriction::equals('usuario_tarifa.id_usuario', "{$table}.id_usuario"),
					CriteriaRestriction::equals('usuario_tarifa.id_moneda', 'contrato.id_moneda')
				);
				if ($this->tipo_dato != 'valor_trabajado_estandar') {
					$on_usuario_tarifa = CriteriaRestriction::and_clause(
						$on_usuario_tarifa,
						CriteriaRestriction::equals('usuario_tarifa.id_tarifa', 'contrato.id_tarifa')
					);
				}
				$Criteria->add_left_join_with('usuario_tarifa', $on_usuario_tarifa);

				if ($this->tipo_dato == 'valor_trabajado_estandar') {
					$Criteria->add_inner_join_with('tarifa', CriteriaRestriction::and_clause(
						CriteriaRestriction::equals('tarifa.id_tarifa', 'usuario_tarifa.id_tarifa'),
						CriteriaRestriction::equals('tarifa.tarifa_defecto', 1)
					));
				}
			}
		}

		$Criteria
			->add_left_join_with(array('prm_area_proyecto', 'area'), CriteriaRestriction::equals('asunto.id_area_proyecto', 'area.id_area_proyecto'))
			->add_left_join_with(array('prm_tipo_proyecto', 'tipo'), CriteriaRestriction::equals('asunto.id_tipo_asunto', 'tipo.id_tipo_proyecto'))
			->add_left_join_with('cliente', CriteriaRestriction::equals('asunto.codigo_cliente', 'cliente.codigo_cliente'))
			->add_left_join_with('grupo_cliente', CriteriaRestriction::equals('cliente.id_grupo_cliente', 'grupo_cliente.id_grupo_cliente'))
			->add_left_join_with('prm_estudio', CriteriaRestriction::equals('cobro.id_estudio', 'prm_estudio.id_estudio'))
			->add_left_join_with(array('prm_estudio', 'estudio_contrato'), CriteriaRestriction::equals('contrato.id_estudio', 'estudio_contrato.id_estudio'));

		if (in_array('prm_area_proyecto.glosa', $this->agrupador)) {
			$Criteria->add_left_join_with('prm_area_proyecto', CriteriaRestriction::equals('prm_area_proyecto.id_area_proyecto', 'asunto.id_area_proyecto'));
		}
		if (in_array('area_usuario', $this->agrupador)) {
			$Criteria->add_left_join_with('prm_area_usuario', CriteriaRestriction::equals('prm_area_usuario.id', 'usuario.id_area_usuario'));
		}
		if (in_array('categoria_usuario', $this->agrupador)) {
			$Criteria->add_left_join_with('prm_categoria_usuario', CriteriaRestriction::equals('prm_categoria_usuario.id_categoria_usuario', 'usuario.id_categoria_usuario'));
		}
		if (in_array('id_usuario_responsable', $this->agrupador)) {
			$Criteria->add_left_join_with(array('usuario', 'usuario_responsable'), CriteriaRestriction::equals('usuario_responsable.id_usuario', 'contrato.id_usuario_responsable'));
		}
		if (in_array('id_usuario_secundario', $this->agrupador)) {
			$Criteria->add_left_join_with(array('usuario', 'usuario_secundario'), CriteriaRestriction::equals('usuario_secundario.id_usuario', 'contrato.id_usuario_secundario'));
		}

		//Se requiere: Moneda Buscada (en el reporte), Moneda Original (del cobro), Moneda Base.
		//Se usa CobroMoneda (cobros por cobrar) o DocumentoMoneda (cobros cobrados).
		if ($this->requiereMoneda($this->tipo_dato)) {
			$Criteria->add_left_join_with(array('prm_moneda', 'moneda_base'), CriteriaRestriction::equals('moneda_base.moneda_base', 1));
			if ($this->tipo_dato == 'valor_por_cobrar') {
				$tabla = 'cobro';
			} else {
				$tabla = 'documento';
				$Criteria->add_left_join_with('documento', CriteriaRestriction::and_clause(
					CriteriaRestriction::equals('documento.id_cobro', 'cobro.id_cobro'),
					CriteriaRestriction::equals('documento.tipo_doc', "'N'")
				));
				//moneda_del_documento
				$Criteria->add_left_join_with(array('documento_moneda', 'cobro_moneda_documento'), CriteriaRestriction::and_clause(
					CriteriaRestriction::equals("cobro_moneda_documento.id_{$tabla}", "{$tabla}.id_{$tabla}"),
					CriteriaRestriction::equals('cobro_moneda_documento.id_moneda', 'documento.id_moneda')
				));
			}
			//moneda buscada
			$Criteria->add_left_join_with(array("{$tabla}_moneda", 'cobro_moneda'), CriteriaRestriction::and_clause(
				CriteriaRestriction::equals("cobro_moneda.id_{$tabla}", "{$tabla}.id_{$tabla}"),
				CriteriaRestriction::equals('cobro_moneda.id_moneda', $this->id_moneda)
			));
			//moneda del cobro
			$Criteria->add_left_join_with(array("{$tabla}_moneda", 'cobro_moneda_cobro'), CriteriaRestriction::and_clause(
				CriteriaRestriction::equals("cobro_moneda_cobro.id_{$tabla}", "{$tabla}.id_{$tabla}"),
				CriteriaRestriction::equals('cobro_moneda_cobro.id_moneda', 'cobro.id_moneda')
			));
			//moneda_base
			$Criteria->add_left_join_with(array("{$tabla}_moneda", 'cobro_moneda_base'), CriteriaRestriction::and_clause(
				CriteriaRestriction::equals("cobro_moneda_base.id_{$tabla}", "{$tabla}.id_{$tabla}"),
				CriteriaRestriction::equals('cobro_moneda_base.id_moneda', 'moneda_base.id_moneda')
			));
		}

		if (Conf::GetConf($this->sesion, 'UsoActividades')) {
			$Criteria->add_left_join_with('actividad', CriteriaRestriction::equals("{$table}.codigo_actividad", 'actividad.codigo_actividad'));
		}

		if ($type == TIPO_TRABAJOS && in_array('area_trabajo', $this->agrupador)) {
			$Criteria->add_left_join_with('prm_area_trabajo', CriteriaRestriction::equals("{$table}.id_area_trabajo", 'prm_area_trabajo.id_area_trabajo'));
		}
	}

	//WHERE para string de Query. Incluye los filtros agregados anteriormente.
	//@param from: si viene de la query de trabajo o de cobro.
	private function getWhere($type) {
		if ($type == TIPO_TRABAJOS) {
			unset($this->filtros['tramite.cobrable']);
			$this->campo_fecha = "trabajo.fecha"; # set as default
		}
		if ($type == TIPO_TRAMITES) {
			unset($this->filtros['trabajo.cobrable']);
			$this->campo_fecha =  "tramite.fecha";  # set as default
		}
		$this->setCampoFecha($this->parametros['campo_fecha']);  # set the real
		$and_wheres = array();

		foreach ($this->filtros as $campo => $filtro) {
			foreach ($filtro as $booleano => $valor) {
				if ($booleano == 'positivo') {
					if (sizeof($filtro['positivo']) > 1) {
						$and_wheres[] = CriteriaRestriction::in($campo, $valor);
					} else {
						$and_wheres[] = CriteriaRestriction::equals($campo, "'{$valor[0]}'");
					}
				} else {
					if (sizeof($filtro['negativo']) > 1) {
						$and_wheres[] = CriteriaRestriction::or_clause(
							CriteriaRestriction::not_in($campo, $valor),
							CriteriaRestriction::is_null($campo)
						);
					} else {
						$and_wheres[] = CriteriaRestriction::or_clause(
							CriteriaRestriction::not_equal($campo, "'{$valor[0]}'"),
							CriteriaRestriction::is_null($campo)
						);
					}
				}
			}
		}

		//Añado el periodo determinado
		if ($type == TIPO_TRABAJOS || $type == TIPO_TRAMITES) {
			$campo_fecha = $this->campo_fecha;
			$campo_fecha_2 = $this->campo_fecha_2;
		} else {
			$campo_fecha = $this->campo_fecha_cobro;
			$campo_fecha_2 = $this->campo_fecha_cobro_2;
		}
		if (!empty($this->rango)) {
			$ini = Utiles::fecha2sql($this->rango['fecha_ini']);
			$fin = Utiles::fecha2sql($this->rango['fecha_fin']) . ' 23:59:59';
			$and_fecha = CriteriaRestriction::between($campo_fecha, "'$ini'", "'$fin'");
			if ($campo_fecha_2) {
				$and_fecha = CriteriaRestriction::or_clause(
					$and_fecha,
					CriteriaRestriction::and_clause(
						CriteriaRestriction::or_clause(
							CriteriaRestriction::is_null($campo_fecha),
							CriteriaRestriction::equals($campo_fecha, "'00-00-0000'")
						),
						CriteriaRestriction::between($campo_fecha_2, "'$ini'", "'$fin'")
					)
				);
			}
			$and_wheres[] = $and_fecha;
		}
		/* Si se filtra el periodo por cobro, los trabajos sin cobro emitido (y posteriores) no se ven */
		if (($campo_fecha == 'cobro.fecha_fin' || $campo_fecha == 'cobro.fecha_emision') && ($type == TIPO_TRABAJOS || $type == TIPO_TRAMITES)) {
			$and_wheres[] = CriteriaRestriction::in('cobro.estado', array('EMITIDO', 'FACTURADO', 'ENVIADO AL CLIENTE', 'PAGO PARCIAL', 'PAGADO'));
		}

		foreach ($this->filtros_especiales as $fe) {
			$and_wheres[] = $fe;
		}

		return CriteriaRestriction::and_clause($and_wheres);
	}

	//GROUP BY en string de Query. Agrupa según la vista. (arreglo de agrupadores se usa al construir los arreglos de resultados.
	private function setGroup($Criteria) {
		if (!$this->vista) {
			$Criteria
				->add_grouping('agrupador_general')
				->add_grouping('id_cobro');
		}

		$Criteria->add_grouping('id_usuario');
		$Criteria->add_grouping('id_cliente');
		$Criteria->add_grouping('codigo_asunto');

		if ($this->requiereMoneda($this->tipo_dato)) {
			$Criteria->add_grouping('id_cobro');
		}

		foreach ($this->id_agrupador as $a) {
			$Criteria->add_grouping($a);
		}
	}

	//ORDER BY en string de Query.
	private function setOrder($Criteria) {
		$agrupadores = array_unique($this->orden_agrupador);
		if (!$this->vista || empty($agrupadores)) {
			return '';
		}
		foreach ($agrupadores as $campo) {
			$Criteria->add_ordering($campo);
		}
	}

	//String de Query.
	private function getQuery($type) {
		$Criteria = new Criteria($this->sesion);
		$this->setSelect($Criteria, $type);
		$this->setFrom($Criteria, $type);
		$Criteria->add_restriction($this->getWhere($type));
		$this->setGroup($Criteria, $type);
		$this->setOrder($Criteria, $type);
		return $Criteria->get_plain_query();
	}

}
