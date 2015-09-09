<?php

require_once dirname(dirname(__FILE__)) . '/conf.php';

class ReporteCriteria {
	// Sesion PHP
	public $sesion = null;

	private $options = null;
	private $newCalculation = array(
		'horas_trabajadas' => 'HorasTrabajadas',
		'horas_spot' => 'HorasSpot',
		'horas_no_cobrables' => 'HorasNoCobrables',
		'valor_cobrado' => 'ValorCobrado',
		'horas_castigadas' => 'HorasCastigadas',
		'horas_cobrables' => 'HorasCobrables',
		'horas_cobradas' => 'HorasCobradas',
		'horas_convenio' => 'HorasConvenio',
		'horas_incobrables' => 'HorasIncobrables',
		'horas_pagadas' => 'HorasPagadas',
		'horas_por_cobrar' => 'HorasPorCobrar',
		'horas_por_pagar' => 'HorasPorPagar',
		'horas_visibles' => 'HorasVisibles',
		'costo' => 'Costo',
		'costo_hh' => 'CostoHh',
		'diferencia_valor_estandar' => 'DiferenciaValorEstandar',
		'rentabilidad' => 'Rentabilidad',
		'rentabilidad_base' => 'RentabilidadBase',
		'valor_cobrado_no_estandar' => 'ValorCobradoNoEstandar',
		'valor_estandar' => 'ValorCobradoEstandar',
		'valor_hora' => 'ValorHora',
		'valor_pagado' => 'ValorPagado',
		'valor_pagado_parcial' => 'ValorPagadoParcial',
		'valor_incobrable' => 'ValorIncobrable',
		'valor_por_cobrar' => 'ValorPorCobrar',
		'valor_por_pagar' => 'ValorPorPagar',
		'valor_por_pagar_parcial' => 'ValorPorPagarParcial',
		'valor_trabajado_estandar' => 'ValorTrabajadoEstandar',
		'valor_tramites' => 'ValorCobradoTramites'
	);

	// Arreglos con filtros custom que se piden desde afuera addFiltro()
	private $filtros = array();
	// Parámetros son los filtros estándar que se pasan en setFiltros()
	private $parametros = array();
	// Es el rango de fechas se puede cambiar con addRangoFecha()
	private $rango = array();
	// String: El tipo de datos que se está consultando (reporte)
	// se establece con setTipoDato()
	private $tipo_dato = null;
	// String: establece los grupos y orden de la información
	private $vista;

	// Moneda en la que se quiere consultar el reporte accesible desde afuera
	public $id_moneda = null;
	// Arreglo con resultados, puede ser accedido desde afuera :(
	public $row;

	/*
	TODO: Revisar desde aqui is deben ser públicas estas variables
	 */
	// El orden de los agrupadores
	public $agrupador = array();
	public $id_agrupador = array();

	public $orden_agrupador = array();

	// Campos utilizados para determinar los datos en el periodo. Default: trabajo.
	public $campo_fecha = '';
	public $campo_fecha_2 = '';
	public $campo_fecha_3 = '';
	public $campo_fecha_cobro = 'cobro.fecha_fin';
	// Determina como se calcula la proporcionalidad de los montos en Flat Fee
	public $proporcionalidad = 'estandar';

	// Cuanto se repite la fila para cada agrupador
	public $filas = array();

	public static $tiposMoneda = array('costo', 'costo_hh', 'valor_cobrado', 'valor_tramites', 'valor_cobrado_no_estandar', 'valor_por_cobrar', 'valor_pagado', 'valor_por_pagar', 'valor_hora', 'valor_incobrable', 'diferencia_valor_estandar', 'valor_estandar', 'valor_trabajado_estandar', 'valor_por_pagar_parcial', 'valor_pagado_parcial', 'rentabilidad', 'rentabilidad_base');
	/*
	TODO END
	*/

	public function __construct($sesion) {
		$this->sesion = $sesion;
	}

	//Agrega un filtro personalizado
	public function addFiltro($tabla, $campo, $valor, $positivo = true) {
		$direction_key = $positivo ? 'equals' : 'not_equal';
		$this->filtros["{$tabla}.{$campo}.{$direction_key}"] = $valor;
	}

	//Indica si el tipo de dato se calcula usando Moneda.
	public function requiereMoneda($tipo_dato) {
		if (in_array($tipo_dato, self::$tiposMoneda)) {
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
	 * |  |  |  |  +-- valor_pagado_parcial: Valor Cobrado que ha sido Pagado parcialmente
	 * |  |  |  |  +-- valor_por_pagar: Valor Cobrado que aún no ha sido pagado
	 * |  |  |  |  \-- valor_por_pagar_parcial: Valor por pagar parcial
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
	public function setTipoDato($nombre, $options = null) {
		$this->tipo_dato = $nombre;
		$this->options = $options;
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
		}

		if ($campo_fecha == 'envio') {
			$this->campo_fecha = 'cobro.fecha_enviado_cliente';
			$this->campo_fecha_2 = '';
			$this->campo_fecha_3 = '';

			$this->campo_fecha_cobro = 'cobro.fecha_enviado_cliente';
		}
		if ($campo_fecha == 'facturacion') {
			$this->campo_fecha = 'cobro.fecha_facturacion';
			$this->campo_fecha_2 = '';
			$this->campo_fecha_3 = '';

			$this->campo_fecha_cobro = 'cobro.fecha_facturacion';
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

	//Ejecuta la Query y guarda internamente las filas de resultado.
	public function Query() {
		$stringquery = "";
		$this->row = array();
		if (array_key_exists($this->tipo_dato, $this->newCalculation)
				&& !empty($this->newCalculation[$this->tipo_dato])) {
			$filtersFields = array(
				'campo_fecha' => $this->parametros['campo_fecha'],
				'fecha_ini' => Utiles::fecha2sql($this->rango['fecha_ini']),
				'fecha_fin' => Utiles::fecha2sql($this->rango['fecha_fin']),
				'usuarios' => $this->sanitizeArray($this->parametros['usuarios']),
				'clientes' => $this->sanitizeArray($this->parametros['clientes']),
				'tipo_asunto' => $this->sanitizeArray($this->parametros['tipos_asunto']),
				'area_asunto' => $this->sanitizeArray($this->parametros['areas_asunto']),
				'area_usuario' => $this->sanitizeArray($this->parametros['areas_usuario']),
				'categoria_usuario' => $this->sanitizeArray($this->parametros['categorias_usuario']),
				'encargados' => $this->sanitizeArray($this->parametros['encargados']),
				'estado_cobro' => $this->sanitizeArray($this->parametros['estado_cobro'])
			);

			$options = array();
			$this->options = 1;
			if (!empty($this->options) && $this->options == 1) {
				$options['hidde_penalized_hours'] = true;
			}
			if ($this->ignorar_cobros_sin_horas) {
				$options['ignore_charges_query'] = true;
			}

			$grouperFields = $this->agrupador;
			$calculator_name = $this->newCalculation[$this->tipo_dato];
			$reflectedClass = new ReflectionClass("{$calculator_name}DataCalculator");
			$calculator = $reflectedClass->newInstance(
				$this->sesion,
				$filtersFields,
				$grouperFields,
				$options,
				$this->id_moneda,
				$this->proporcionalidad
			);

			$this->row = $calculator->calculate();

			return;
		}
	}


	/**
	 * Elimina los elementos que contengan un vacio como valor
	 * @param  array $array
	 * @return array
	 */
	private function sanitizeArray($array) {
		if (is_array($array)) {
			foreach($array as $k => $v) {
				if (trim($v) == '') {
					unset($array[$k]);
				}
			}
		}
		return $array;
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

	/* Constructor de Arreglo Cruzado: sólo vista Cliente o Profesional */

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


}
