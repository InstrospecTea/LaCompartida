<?php

require_once dirname(dirname(__FILE__)) . '/conf.php';

class Reporte {

	// Sesion PHP
	var $sesion = null;
	// Arreglos con filtros
	var $filtros = array();
	var $filtros_especiales = array();
	var $rango = array();
	//Arreglo de datos
	var $tipo_dato = 0;
	//Arreglo con vista
	var $vista;
	//Arreglo con resultados
	var $row;
	// String con el último error
	var $error = '';
	//El orden de los agrupadores
	var $agrupador = array();
	var $id_agrupador = array();
	var $id_agrupador_cobro = array();
	var $orden_agrupador = array();
	var $agrupador_principal = 0;
	//Campos utilizados para determinar los datos en el periodo. Default: trabajo.
	var $campo_fecha = 'trabajo.fecha';
	var $campo_fecha_2 = '';
	var $campo_fecha_3 = '';
	var $campo_fecha_cobro = 'cobro.fecha_fin';
	var $campo_fecha_cobro_2 = 'cobro.fecha_emision';
	//Determina como se calcula la proporcionalidad de los montos en Flat Fee
	var $proporcionalidad = 'estandar';
	var $conf = array();
	//Codigo secundario cuando corresponde
	var $dato_usuario = 'usuario.username';
	var $dato_codigo_asunto = 'asunto.codigo_asunto_secundario';
//Cuanto se repite la fila para cada agrupador
	var $filas = array();

	function Reporte($sesion) {
		$this->sesion = $sesion;

		if (method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'UsaUsernameEnTodoElSistema')) {
			$this->dato_usuario = 'usuario.username';
		} else {
			$this->dato_usuario = 'CONCAT_WS(\' \',usuario.nombre, usuario.apellido1, LEFT(usuario.apellido2,1))';
		}
		if (UtilesApp::GetConf($this->sesion, 'CodigoSecundario')) {
			$this->dato_codigo_asunto = 'asunto.codigo_asunto_secundario';
		} else {
			$this->dato_codigo_asunto = 'asunto.codigo_asunto';
		}
	}

	function configuracion($opcs = array()) {
		$this->conf = array();
	}

	//Agrega un filtro
	function addFiltro($tabla, $campo, $valor, $positivo = true) {
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
	function requiereMoneda($tipo_dato) {
		$extras = array('rentabilidad', 'rentabilidad_base');

		if (in_array($tipo_dato, array_merge($extras, Reporte::tiposMoneda()))) {
			return true;
		}
		return false;
	}

	//Retorna un arreglo con los tipos de datos que requieren especificar Moneda
	function tiposMoneda() {
		return array('costo', 'costo_hh', 'valor_cobrado', 'valor_por_cobrar', 'valor_pagado', 'valor_por_pagar', 'valor_hora', 'valor_incobrable', 'diferencia_valor_estandar', 'valor_estandar', 'valor_trabajado_estandar');
	}

	function usaDivisor() {
		if (in_array($this->tipo_dato, array('rentabilidad', 'rentabilidad_base', 'valor_hora', 'costo_hh'))) {
			return true;
		}
		return false;
	}

	//Establece el tipo de dato a buscar, y agrega los filtros correspondientes
	function setTipoDato($nombre) {
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
				break;

			case "horas_spot":
				$this->addFiltro('trabajo', 'cobrable', '1');
				$this->filtros_especiales[] = " ( cobro.estado <> 'CREADO' AND cobro.estado <> 'EN REVISION' AND ( cobro.forma_cobro IN ('TASA','CAP') )) OR ( (cobro.estado IS NULL OR cobro.estado IN ('CREADO','EN REVISION')) AND (contrato.forma_cobro IN ('TASA','CAP') OR contrato.forma_cobro IS NULL ) ) ";
				break;

			case "horas_convenio":
				$this->addFiltro('trabajo', 'cobrable', '1');
				$this->filtros_especiales[] = " ( cobro.estado <> 'CREADO' AND cobro.estado <> 'EN REVISION' AND  ( cobro.forma_cobro IN ('FLAT FEE','RETAINER') )) OR ( (cobro.estado IS NULL OR cobro.estado IN ('CREADO','EN REVISION')) AND (contrato.forma_cobro IN ('FLAT FEE','RETAINER') ) )";
				break;

			case "horas_no_cobrables":
				$this->addFiltro('trabajo', 'cobrable', '0');
				break;

			case "horas_cobradas":
			case "valor_cobrado":
			case "valor_hora":
			case "rentabilidad":
			case "diferencia_valor_estandar":
			case "valor_estandar":
			case "valor_pagado_parcial":
				$this->addFiltro('trabajo', 'cobrable', '1');
				$this->addFiltro('cobro', 'estado', 'EMITIDO');
				$this->addFiltro('cobro', 'estado', 'FACTURADO');
				$this->addFiltro('cobro', 'estado', 'ENVIADO AL CLIENTE');
				$this->addFiltro('cobro', 'estado', 'PAGO PARCIAL');
				$this->addFiltro('cobro', 'estado', 'PAGADO');
				break;

			case "valor_por_cobrar":
			case "horas_por_cobrar":
				$this->addFiltro('trabajo', 'cobrable', '1');
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
				$this->addFiltro('cobro', 'estado', 'PAGADO');
				break;

			case "horas_por_pagar":
			case "valor_por_pagar":
			case "valor_por_pagar_parcial":
				$this->addFiltro('trabajo', 'cobrable', '1');
				$this->addFiltro('cobro', 'estado', 'EMITIDO');
				$this->addFiltro('cobro', 'estado', 'FACTURADO');
				$this->addFiltro('cobro', 'estado', 'ENVIADO AL CLIENTE');
				$this->addFiltro('cobro', 'estado', 'PAGO PARCIAL');
				break;

			case "horas_incobrables":
			case "valor_incobrable":
				$this->addFiltro('trabajo', 'cobrable', '1');
				$this->addFiltro('cobro', 'estado', 'INCOBRABLE');
				break;
		}
	}

	//Agrega un Filtro de Rango de Fechas
	function addRangoFecha($valor1, $valor2) {
		$this->rango['fecha_ini'] = $valor1;
		$this->rango['fecha_fin'] = $valor2;
	}

	function setProporcionalidad($valor = 'estandar') {
		$this->proporcionalidad = $valor;
	}

	//Establece el Campo de la fecha
	function setCampoFecha($campo_fecha) {
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
	}

	//Los Agrupadores definen GROUP y ORDER en las queries.
	function addAgrupador($s) {
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
			case "area_asunto":
			case "tipo_asunto":
			case "id_trabajo":
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
			default:
				$this->id_agrupador_cobro[] = $s;
		}
	}

	//Establece la vista: los agrupadores (y su orden) son la base para la construcción de arreglos de resultado.
	function setVista($vista) {
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

	function alt($opc1, $opc2) {
		if (!$opc2) {
			return $opc1;
		} else {
			return " IF( $opc1 IS NULL OR $opc1 = '00-00-0000' , $opc2 , $opc1 )";
		}
	}

	function nombre_usuario($usuario) {
		if (UtilesApp::GetConf($this->sesion, 'UsaUsernameEnTodoElSistema')) {
			return $usuario . '.username ';
		}
		return 'CONCAT_WS(\' \',' . $usuario . '.nombre, ' . $usuario . '.apellido1, LEFT(' . $usuario . '.apellido2,1)) ';
	}

	function cobroQuery() { //Query que añade rows para los datos de Cobros emitidos que no cuentan Trabajos
		if (empty($this->id_agrupador_cobro)) {
			return 0;
		}

		$campo_fecha = $this->alt($this->campo_fecha_cobro, $this->campo_fecha_cobro_2);

		$s = 'SELECT \'' . __('Indefinido') . '\' as profesional,
					\'' . __('Indefinido') . '\' as username,
					-1 as id_usuario,
					cliente.id_cliente,
					cliente.codigo_cliente,
					' . (in_array('codigo_cliente_secundario', $this->agrupador) ? 'cliente.codigo_cliente_secundario,' : '') . '
					' . (in_array('prm_area_proyecto.glosa', $this->agrupador) ? "'" . __('Indefinido') . "' AS glosa," : '') . '
					\'' . __('Indefinido') . '\' as categoria_usuario,
					\'' . __('Indefinido') . '\' as area_usuario,
					' . (in_array('id_usuario_responsable', $this->agrupador) ? $this->nombre_usuario('usuario_responsable') . ' AS nombre_usuario_responsable,' : '') . '
					' . (in_array('id_usuario_responsable', $this->agrupador) ? ' usuario_responsable.id_usuario AS id_usuario_responsable,' : '') . '
					' . (in_array('id_usuario_secundario', $this->agrupador) ? ' usuario_secundario.id_usuario AS id_usuario_secundario,' : '') . '
					' . (!$this->vista ? "'Indefinido' AS agrupador_general," : '') . '
					' . (in_array('id_usuario_secundario', $this->agrupador) ? $this->nombre_usuario('usuario_secundario') . ' AS nombre_usuario_secundario,' : '') . '
					cliente.glosa_cliente,
					asunto.glosa_asunto,
					CONCAT(' . $this->dato_codigo_asunto . ',\': \',asunto.glosa_asunto) AS glosa_asunto_con_codigo,
					asunto.codigo_asunto,
					contrato.id_contrato,
					\' - \' as tipo_asunto,
					\' - \' as area_asunto,
					grupo_cliente.id_grupo_cliente,
					IFNULL(grupo_cliente.glosa_grupo_cliente,\'-\') as glosa_grupo_cliente,
					CONCAT(cliente.glosa_cliente,\' - \',asunto.codigo_asunto,\' \',asunto.glosa_asunto) as glosa_cliente_asunto,
					IFNULL(grupo_cliente.glosa_grupo_cliente,cliente.glosa_cliente) as grupo_o_cliente,

					' . $campo_fecha . ' as fecha_final,
					DATE_FORMAT( ' . $campo_fecha . ' , \'%m-%Y\') as mes_reporte,
					DATE_FORMAT( ' . $campo_fecha . ' , \'%d-%m-%Y\') as dia_reporte,
					' . (in_array('dia_corte', $this->agrupador) ? 'DATE_FORMAT( cobro.fecha_fin , \'%d-%m-%Y\') as dia_corte,' : '') . '
					' . (in_array('dia_emision', $this->agrupador) ? 'IF(cobro.fecha_emision IS NULL,\'' . __('Por Emitir') . '\',DATE_FORMAT( cobro.fecha_emision , \'%d-%m-%Y\')) as dia_emision,' : '') . '
					' . (in_array('mes_emision', $this->agrupador) ? 'IF(cobro.fecha_emision IS NULL,\'' . __('Por Emitir') . '\',DATE_FORMAT( cobro.fecha_emision , \'%m-%Y\')) as mes_emision,' : '') . '
					cobro.id_cobro,
					cobro.estado AS estado,
					cobro.forma_cobro AS forma_cobro,
				';

		if (UtilesApp::GetConf($this->sesion, 'UsoActividades')) {
			$s .= " ' - ' as glosa_actividad, ";
		}

		if (in_array('area_trabajo', $this->agrupador)) {
			$s.= " ' - ' as area_trabajo, ";
		}

		// TIPO DE DATO
		switch ($this->tipo_dato) {
			case 'valor_cobrado':
			case 'valor_por_cobrar':
			case 'valor_hora':
				$s .= ' 0 as valor_divisor, (1/ifnull(ca2.cant_asuntos,1))*SUM( cobro.monto_subtotal
								*	(cobro_moneda_cobro.tipo_cambio/cobro_moneda_base.tipo_cambio)
								/	(cobro_moneda.tipo_cambio/cobro_moneda_base.tipo_cambio) )';
				break;

			case 'rentabilidad_base':
				$s .= ' 0 as valor_divisor, (1/ifnull(ca2.cant_asuntos,1))*SUM( IF(cobro.estado NOT IN (\'CREADO\',\'EN REVISION\'), cobro.monto_subtotal
								*	(cobro_moneda_cobro.tipo_cambio/cobro_moneda_base.tipo_cambio)
								/	(cobro_moneda.tipo_cambio/cobro_moneda_base.tipo_cambio), 0 ))';
				break;

			case 'valor_pagado':
				$s .= ' (1/ifnull(ca2.cant_asuntos,1))*SUM( IF(cobro.estado = \'PAGADO\',
								(cobro.monto_subtotal
								*	(cobro_moneda_cobro.tipo_cambio/cobro_moneda_base.tipo_cambio)
								/	(cobro_moneda.tipo_cambio/cobro_moneda_base.tipo_cambio)
								),
								0) )';
				break;

			case 'valor_pagado_parcial':
				$s .= ' (1/ifnull(ca2.cant_asuntos,1))*SUM( (cobro.monto_subtotal
								*	(cobro_moneda_cobro.tipo_cambio/cobro_moneda_base.tipo_cambio)
								*  ( 1 - documento.saldo_honorarios / documento.honorarios)
								/	(cobro_moneda.tipo_cambio/cobro_moneda_base.tipo_cambio) ) )';
				break;

			case 'valor_por_pagar_parcial':
				$s .= ' (1/ifnull(ca2.cant_asuntos,1))*SUM( (cobro.monto_subtotal
								*	(cobro_moneda_cobro.tipo_cambio/cobro_moneda_base.tipo_cambio)
								*  ( documento.saldo_honorarios / documento.honorarios)
								/	(cobro_moneda.tipo_cambio/cobro_moneda_base.tipo_cambio) ) )';
				break;

			case 'valor_por_pagar':
				$s .= '(1/ifnull(ca2.cant_asuntos,1))*SUM( IF(cobro.estado = \'PAGADO\' || cobro.estado = \'INCOBRABLE\' , 0,
								(cobro.monto_subtotal
								*	(cobro_moneda_cobro.tipo_cambio/cobro_moneda_base.tipo_cambio)
								/	(cobro_moneda.tipo_cambio/cobro_moneda_base.tipo_cambio)
								)) )';
				break;

			case 'valor_incobrable':
				$s .= '(1/ifnull(ca2.cant_asuntos,1))*SUM( IF(cobro.estado <> \'INCOBRABLE\', 0,
								(cobro.monto_subtotal
								*	(cobro_moneda_cobro.tipo_cambio/cobro_moneda_base.tipo_cambio)
								/	(cobro_moneda.tipo_cambio/cobro_moneda_base.tipo_cambio)
								)) )';
				break;

			case 'rentabilidad':
				$s .= ' 0 AS valor_divisor,
								(1/ifnull(ca2.cant_asuntos,1))*SUM(cobro.monto_subtotal * (cobro_moneda_cobro.tipo_cambio/cobro_moneda_base.tipo_cambio) / (cobro_moneda.tipo_cambio/cobro_moneda_base.tipo_cambio) )';
				break;
			case 'diferencia_valor_estandar':
				$s .= ' (1/ifnull(ca2.cant_asuntos,1))*SUM( cobro.monto_subtotal
								*	(cobro_moneda_cobro.tipo_cambio/cobro_moneda_base.tipo_cambio)
								/	(cobro_moneda.tipo_cambio/cobro_moneda_base.tipo_cambio) )';
				break;

			case 'valor_estandar':
			case 'valor_trabajado_estandar':
			case 'costo_hh':
				$s .= ' SUM( 0 )';
				break;
		}
		$s .= ' as ' . $this->tipo_dato;
		$s .= ' FROM cobro
						left join cobro_asunto using (id_cobro)
						LEFT JOIN  asunto using (codigo_asunto)
						LEFT JOIN  (select id_cobro, count(codigo_asunto) cant_asuntos from cobro_asunto group by id_cobro) ca2 on ca2.id_cobro=cobro.id_cobro
			 			LEFT JOIN usuario ON cobro.id_usuario=usuario.id_usuario
						LEFT JOIN contrato ON contrato.id_contrato = cobro.id_contrato
						LEFT JOIN cliente ON contrato.codigo_cliente = cliente.codigo_cliente
						LEFT JOIN grupo_cliente ON grupo_cliente.id_grupo_cliente = cliente.id_grupo_cliente
						' . (in_array('id_usuario_responsable', $this->agrupador) ? ' LEFT JOIN usuario AS usuario_responsable ON usuario_responsable.id_usuario = contrato.id_usuario_responsable' : '') . '
						' . (in_array('id_usuario_secundario', $this->agrupador) ? ' LEFT JOIN usuario AS usuario_secundario ON usuario_secundario.id_usuario = contrato.id_usuario_secundario' : '') . '
						LEFT JOIN prm_moneda AS moneda_base ON (moneda_base.moneda_base = 1)
					';

		if ($this->tipo_dato == 'valor_por_cobrar') {
			$tabla = 'cobro';
		} else {
			$s .= " LEFT JOIN documento ON documento.id_cobro = cobro.id_cobro AND documento.tipo_doc = 'N' ";
			$tabla = 'documento';
		}
		//moneda buscada
		$s .= " LEFT JOIN " . $tabla . "_moneda as cobro_moneda ON (cobro_moneda.id_" . $tabla . " = " . $tabla . ".id_" . $tabla . " AND cobro_moneda.id_moneda = '" . $this->id_moneda . "' )";
		//moneda del cobro
		$s .= " LEFT JOIN " . $tabla . "_moneda as cobro_moneda_cobro on (cobro_moneda_cobro.id_" . $tabla . " = " . $tabla . ".id_" . $tabla . " AND cobro_moneda_cobro.id_moneda = cobro.id_moneda )";
		//moneda_base
		$s .= " LEFT JOIN " . $tabla . "_moneda as cobro_moneda_base on (cobro_moneda_base.id_" . $tabla . " = " . $tabla . ".id_" . $tabla . " AND cobro_moneda_base.id_moneda = moneda_base.id_moneda )";



		/* WHERE SIN USUARIOS NI TRABAJOS */
		unset($this->filtros['trabajo.cobrable']);
		unset($this->filtros['trabajo.id_usuario']);

		unset($this->filtros['asunto.id_area_proyecto']);
		unset($this->filtros['asunto.id_tipo_asunto']);

		$s .= $this->sWhere('cobro');


		$s .= '  AND cobro.incluye_honorarios=1 AND cobro.monto_subtotal>0  AND (cobro.total_minutos = 0 OR cobro.total_minutos IS NULL  ';

		// FFF: Si se saca el reporte a proporcionalidad cliente, esta query debe traer los cobros con monto_thh=0. Si no, los con monto_thh_estandar=0
		if ($this->proporcionalidad == 'estandar') {
			$s .= ' OR (cobro.monto_thh_estandar = 0 AND cobro.forma_cobro = \'FLAT FEE\') ';
		} else {
			$s .= ' OR (cobro.monto_thh = 0 AND cobro.forma_cobro = \'FLAT FEE\') ';
		}
		$s .= ' OR (cobro.forma_cobro <> \'FLAT FEE\' AND cobro.monto_thh = 0) )';

		if (!$this->vista) {
			$s .= ' GROUP BY agrupador_general, id_cobro ';
		} else {
			$agrupa_cobro = array();
			$agrupa_cobro[] = "id_usuario";
			$agrupa_cobro[] = "id_cliente";
			$agrupa_cobro[] = "codigo_asunto";

			if ($this->requiereMoneda($this->tipo_dato)) {
				$agrupa_cobro[] = "id_cobro";
			}

			foreach ($this->id_agrupador_cobro as $a) {
				$agrupa_cobro[] = $a;
			}


			$s .= ' GROUP BY ' . implode(', ', $agrupa_cobro);
		}
		return $s;
	}

	//SELECT en string de Query. Elige el tipo de dato especificado.
	function sSELECT() {

		$s = 'SELECT	' . $this->dato_usuario . ' as profesional,
						usuario.username as username,
						usuario.id_usuario,
						cliente.id_cliente,
						cliente.codigo_cliente,
						' . (in_array('codigo_cliente_secundario', $this->agrupador) ? 'cliente.codigo_cliente_secundario,' : '') . '
						' . (in_array('prm_area_proyecto.glosa', $this->agrupador) ? 'prm_area_proyecto.glosa,' : '') . '
						' . (in_array('area_usuario', $this->agrupador) ? 'IFNULL(prm_area_usuario.glosa,\'-\') as area_usuario,' : '') . '
						' . (in_array('categoria_usuario', $this->agrupador) ? 'IFNULL(prm_categoria_usuario.glosa_categoria,\'-\') as categoria_usuario,' : '') . '
						' . (in_array('id_usuario_responsable', $this->agrupador) ? 'IF(usuario_responsable.id_usuario IS NULL,\'Sin Resposable\', ' . $this->nombre_usuario('usuario_responsable') . ') AS nombre_usuario_responsable,' : '') . '
						' . (in_array('id_usuario_responsable', $this->agrupador) ? ' usuario_responsable.id_usuario AS id_usuario_responsable,' : '') . '
						' . (in_array('id_usuario_secundario', $this->agrupador) ? ' usuario_secundario.id_usuario AS id_usuario_secundario,' : '') . '
						' . (!$this->vista ? "'Indefinido' AS agrupador_general," : '') . '
						' . (in_array('id_usuario_secundario', $this->agrupador) ? 'IF(usuario_secundario.id_usuario IS NULL,\'Sin Resposable Secundario\',' . $this->nombre_usuario('usuario_secundario') . ') AS nombre_usuario_secundario,' : '') . '
						cliente.glosa_cliente,
						asunto.glosa_asunto,
						CONCAT(' . $this->dato_codigo_asunto . ',\': \',asunto.glosa_asunto) AS glosa_asunto_con_codigo,
						' . $this->dato_codigo_asunto . ' as codigo_asunto,
						contrato.id_contrato,
						tipo.glosa_tipo_proyecto AS tipo_asunto,
						area.glosa AS area_asunto,
						grupo_cliente.id_grupo_cliente,
						IFNULL(grupo_cliente.glosa_grupo_cliente,\'-\') as glosa_grupo_cliente,
						CONCAT(cliente.glosa_cliente,\' - \',asunto.codigo_asunto,\' \',asunto.glosa_asunto) as glosa_cliente_asunto,
						IFNULL(grupo_cliente.glosa_grupo_cliente,cliente.glosa_cliente) as grupo_o_cliente,
						trabajo.fecha as fecha_final,
						' . (in_array('mes_reporte', $this->agrupador) ? 'DATE_FORMAT(trabajo.fecha, \'%m-%Y\') as mes_reporte,' : '') . '
						' . (in_array('dia_reporte', $this->agrupador) ? 'DATE_FORMAT(trabajo.fecha, \'%d-%m-%Y\') as dia_reporte,' : '') . '
						' . (in_array('dia_corte', $this->agrupador) ? 'DATE_FORMAT( cobro.fecha_fin , \'%d-%m-%Y\') as dia_corte,' : '') . '
						' . (in_array('dia_emision', $this->agrupador) ? 'IF(cobro.fecha_emision IS NULL,\'' . __('Por Emitir') . '\',DATE_FORMAT( cobro.fecha_emision , \'%d-%m-%Y\')) as dia_emision,' : '') . '
						' . (in_array('mes_emision', $this->agrupador) ? 'IF(cobro.fecha_emision IS NULL,\'' . __('Por Emitir') . '\',DATE_FORMAT( cobro.fecha_emision , \'%m-%Y\')) as mes_emision,' : '') . '
						IFNULL(cobro.id_cobro,\'Indefinido\') as id_cobro,
						IFNULL(cobro.estado,\'Indefinido\') as estado,
						IFNULL(cobro.forma_cobro,\'Indefinido\') as forma_cobro,
						';
		if (UtilesApp::GetConf($this->sesion, 'UsoActividades')) {
			$s .= " IFNULL( NULLIF( IFNULL( actividad.glosa_actividad, 'Indefinido' ), ' ' ), 'Indefinido' ) as glosa_actividad, ";
		}

		if (in_array('area_trabajo', $this->agrupador)) {
			$s.= " IFNULL( prm_area_trabajo.glosa, 'Indefinido' ) as area_trabajo, ";
		}
		if (in_array('id_trabajo', $this->agrupador)) {
			$s.= ' trabajo.id_trabajo, ';
		}



		//Datos que se repiten
		$s_monto_thh_simple = "IF(cobro.monto_thh > 0,
					cobro.monto_thh + cobro.monto_tramites,
					IF(cobro.monto_trabajos + cobro.monto_tramites > 0,
					cobro.monto_trabajos + cobro.monto_tramites, 1))";
		$s_monto_thh_estandar = "IF(cobro.monto_thh_estandar > 0,
					cobro.monto_thh_estandar + cobro.monto_tramites,
					IF(cobro.monto_trabajos + cobro.monto_tramites > 0,
					cobro.monto_trabajos + cobro.monto_tramites, 1))";

		//El calculo de la proporcionalidad puede hacerse como ' A / B ' o, si no es estandar, ' C / D '.
		// A : monto estandar de este trabajo
		// B : monto total de los trabajos en tarifa estandar
		// C : monto de este trabajo (tarifa de cliente)
		// D : monto total de trabajos (tarifa del cliente)

		if ($this->proporcionalidad == 'estandar') {
			$s_tarifa = "IF(cobro.forma_cobro='FLAT FEE',tarifa_hh_estandar,tarifa_hh)";
			$s_monto_thh = "IF(cobro.forma_cobro='FLAT FEE'," . $s_monto_thh_estandar . "," . $s_monto_thh_simple . ")";
		} else {
			$s_tarifa = "tarifa_hh";
			$s_monto_thh = $s_monto_thh_simple;
		}



		$monto_estandar = "SUM(
									trabajo.tarifa_hh_estandar
									* (TIME_TO_SEC( duracion_cobrada)/3600)
									* (cobro_moneda_cobro.tipo_cambio/cobro_moneda.tipo_cambio)
							  )";
		$monto_predicho = "SUM(
									usuario_tarifa.tarifa
									* TIME_TO_SEC( duracion_cobrada )
									* moneda_por_cobrar.tipo_cambio
									/ (moneda_display.tipo_cambio * 3600)
								)";
		$monto_trabajado_estandar = "SUM(
										trabajo.tarifa_hh_estandar
										* (TIME_TO_SEC( duracion)/3600)
										* (cobro_moneda_cobro.tipo_cambio/cobro_moneda.tipo_cambio)
									)";

		//Si el Reporte está configurado para usar el monto del documento, el tipo de dato es valor, y no valor_por_cobrar
		if ($this->tipo_dato != 'valor_por_cobrar') {
			$monto_honorarios = "SUM(
										(
											" . $s_tarifa . "
											* TIME_TO_SEC( duracion_cobrada)/3600
										)
										*	(
												( (documento.subtotal_sin_descuento)  * cobro_moneda_documento.tipo_cambio )
												/   (" . $s_monto_thh . " * cobro_moneda_cobro.tipo_cambio )
											)
										*	(cobro_moneda_cobro.tipo_cambio/cobro_moneda.tipo_cambio)
									)";
		} else {
			$monto_honorarios = "SUM(
									(
										" . $s_tarifa . "
										* TIME_TO_SEC( duracion_cobrada)/3600
									)
									*	(cobro.monto_trabajos / " . $s_monto_thh . " )
									*	(cobro_moneda_cobro.tipo_cambio/cobro_moneda.tipo_cambio)
								)";
		}
		//Agrega el cuociente saldo_honorarios/honorarios, que indica el porcentaje que falta pagar de este trabajo.
		$monto_por_pagar_parcial = "SUM(
										(
											" . $s_tarifa . "
											* TIME_TO_SEC( duracion_cobrada)/3600
										)
										*	(
												( documento.subtotal_sin_descuento * cobro_moneda_documento.tipo_cambio )
												/   (" . $s_monto_thh . " * cobro_moneda_cobro.tipo_cambio )
											)
										*   ( documento.saldo_honorarios / documento.honorarios)
										*	(cobro_moneda_cobro.tipo_cambio/cobro_moneda.tipo_cambio)
									)";
		$datos_monedas = "cobro_moneda.id_moneda, cobro_moneda.tipo_cambio, cobro_moneda_base.id_moneda, cobro_moneda_base.tipo_cambio, cobro_moneda_cobro.id_moneda, cobro_moneda_cobro.tipo_cambio";

		switch ($this->tipo_dato) {
			case "horas_trabajadas":
			case "horas_no_cobrables":
				$s .= "SUM(TIME_TO_SEC( trabajo.duracion ))/3600.0";
				break;

			case "horas_cobrables":
				$s .= "SUM(TIME_TO_SEC( trabajo.duracion_cobrada ))/3600.0";
				break;

			case "costo":
				$s .= $datos_monedas . ", ifnull((cobro_moneda_base.tipo_cambio/cobro_moneda.tipo_cambio),1)*SUM(cut.costo_hh*TIME_TO_SEC( trabajo.duracion ))/3600";

				break;

			case "horas_castigadas":
				$s .= "SUM(TIME_TO_SEC(trabajo.duracion)-TIME_TO_SEC(trabajo.duracion_cobrada))/3600";
				break;

			case "horas_visibles":
			case "horas_cobradas":
			case "horas_por_cobrar":
			case "horas_pagadas":
			case "horas_por_pagar":
			case "horas_incobrables":
				$s .= "SUM(TIME_TO_SEC(trabajo.duracion_cobrada))/3600";
				break;

			case 'valor_por_cobrar':
				//Si el trabajo está en cobro CREADO, se usa la formula de ese cobro. Si no está, se usa la tarifa de la moneda del contrato, y se convierte según el tipo de cambio actual de la moneda que se está mostrando.
				$s .= " $datos_monedas, IF( cobro.id_cobro IS NOT NULL, $monto_honorarios ,	$monto_predicho)";
				break;

			case 'valor_trabajado_estandar':
				$s .= " $datos_monedas, $monto_trabajado_estandar";
				break;

			case "valor_cobrado":
			case "valor_pagado":
			case "valor_por_pagar":
			case "valor_incobrable":
				$s .= " $datos_monedas, $monto_honorarios";
				break;

			case "valor_por_pagar_parcial":
				$s .= " $datos_monedas, $monto_por_pagar_parcial";
				break;

			case "valor_pagado_parcial":
				$s .= " $datos_monedas, $monto_honorarios - $monto_por_pagar_parcial";
				break;

			case "valor_estandar":
				$s .= " $datos_monedas,	$monto_estandar ";
				break;

			case "diferencia_valor_estandar":
				$s .= " $datos_monedas, ( $monto_honorarios - $monto_estandar )";
				break;

			case 'rentabilidad':
				/* Se necesita resultado extra: lo que se habría cobrado */
				$s .= " $monto_estandar  AS valor_divisor, ";
				$s .= " $datos_monedas, $monto_honorarios ";
				break;
			case 'rentabilidad_base':
				$s .= " $monto_trabajado_estandar AS valor_divisor, ";
				$s .= " $datos_monedas, IF( cobro.estado IN ('EMITIDO','FACTURADO','ENVIADO AL CLIENTE','PAGO PARCIAL','PAGADO'),$monto_honorarios, 0)";
				break;

			case "valor_hora":
				/* Se necesita resultado extra: las horas cobradas */
				$s .= "SUM((TIME_TO_SEC( duracion_cobrada)/3600)) as valor_divisor, ";
				$s .= " $datos_monedas, $monto_honorarios ";
				break;

			case "costo_hh":
				$s .= "SUM((TIME_TO_SEC( duracion)/3600)) as valor_divisor, ";
				$s .= $datos_monedas . ",SUM( ifnull((cobro_moneda_base.tipo_cambio/cobro_moneda.tipo_cambio),1)*cut.costo_hh * 	(TIME_TO_SEC( duracion)/3600)	) ";

				break;
		}
		$s .= ' as ' . $this->tipo_dato;
		return $s;
	}

	//FROM en string de Query. Incluye las tablas necesarias.
	function sFrom() {
		//Calculo de valor por cobrar requiere Tarifa, Tipo de Cambio
		$join_por_cobrar = "
						LEFT JOIN usuario_tarifa ON usuario_tarifa.id_tarifa = contrato.id_tarifa AND usuario_tarifa.id_usuario = trabajo.id_usuario AND usuario_tarifa.id_moneda = contrato.id_moneda
						LEFT JOIN prm_moneda AS moneda_por_cobrar ON moneda_por_cobrar.id_moneda = contrato.id_moneda
						LEFT JOIN prm_moneda AS moneda_display ON moneda_display.id_moneda = '" . $this->id_moneda . "'
					";

		$s = ' FROM trabajo left join usuario_costo_hh cut on trabajo.id_usuario=cut.id_usuario and date_format(trabajo.fecha,\'%Y%m\')=cut.yearmonth
					LEFT JOIN usuario ON usuario.id_usuario = trabajo.id_usuario
					LEFT JOIN asunto ON asunto.codigo_asunto = trabajo.codigo_asunto
					LEFT JOIN cobro on trabajo.id_cobro = cobro.id_cobro
					LEFT JOIN contrato ON ( contrato.id_contrato = IFNULL(cobro.id_contrato, asunto.id_contrato))
					' . ($this->tipo_dato == 'valor_por_cobrar' ? $join_por_cobrar : '') . '
					LEFT JOIN prm_area_proyecto AS area ON asunto.id_area_proyecto = area.id_area_proyecto
					LEFT JOIN prm_tipo_proyecto AS tipo ON asunto.id_tipo_asunto = tipo.id_tipo_proyecto
					LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
					LEFT JOIN grupo_cliente ON cliente.id_grupo_cliente = grupo_cliente.id_grupo_cliente
					' . (in_array('prm_area_proyecto.glosa', $this->agrupador) ? 'LEFT JOIN prm_area_proyecto ON prm_area_proyecto.id_area_proyecto = asunto.id_area_proyecto' : '') . '
					' . (in_array('area_usuario', $this->agrupador) ? 'LEFT JOIN prm_area_usuario ON prm_area_usuario.id = usuario.id_area_usuario' : '') . '
					' . (in_array('categoria_usuario', $this->agrupador) ? 'LEFT JOIN prm_categoria_usuario ON prm_categoria_usuario.id_categoria_usuario = usuario.id_categoria_usuario' : '') . '
					' . (in_array('id_usuario_responsable', $this->agrupador) ? 'LEFT JOIN usuario AS usuario_responsable ON usuario_responsable.id_usuario = contrato.id_usuario_responsable' : '') . '
					' . (in_array('id_usuario_secundario', $this->agrupador) ? 'LEFT JOIN usuario AS usuario_secundario ON usuario_secundario.id_usuario = contrato.id_usuario_secundario' : '') . '
					';
		//Se requiere: Moneda Buscada (en el reporte), Moneda Original (del cobro), Moneda Base.
		//Se usa CobroMoneda (cobros por cobrar) o DocumentoMoneda (cobros cobrados).
		if ($this->requiereMoneda($this->tipo_dato)) {
			$s .= " LEFT JOIN prm_moneda as moneda_base ON moneda_base.moneda_base = '1' ";
			if ($this->tipo_dato == 'valor_por_cobrar') {
				$tabla = 'cobro';
			} else {
				$tabla = 'documento';
				$s .= " LEFT JOIN documento ON documento.id_cobro = cobro.id_cobro AND documento.tipo_doc = 'N' ";
				//moneda_del_documento
				$s .= " LEFT JOIN documento_moneda as cobro_moneda_documento on (cobro_moneda_documento.id_" . $tabla . " = " . $tabla . ".id_" . $tabla . " AND cobro_moneda_documento.id_moneda = documento.id_moneda )";
			}
			//moneda buscada
			$s .= " LEFT JOIN " . $tabla . "_moneda as cobro_moneda ON (cobro_moneda.id_" . $tabla . " = " . $tabla . ".id_" . $tabla . " AND cobro_moneda.id_moneda = '" . $this->id_moneda . "' )";
			//moneda del cobro
			$s .= " LEFT JOIN " . $tabla . "_moneda as cobro_moneda_cobro on (cobro_moneda_cobro.id_" . $tabla . " = " . $tabla . ".id_" . $tabla . " AND cobro_moneda_cobro.id_moneda = cobro.id_moneda )";
			//moneda_base
			$s .= " LEFT JOIN " . $tabla . "_moneda as cobro_moneda_base on (cobro_moneda_base.id_" . $tabla . " = " . $tabla . ".id_" . $tabla . " AND cobro_moneda_base.id_moneda = moneda_base.id_moneda )";
		}

		if (UtilesApp::GetConf($this->sesion, 'UsoActividades')) {
			$s .= " LEFT JOIN actividad ON ( trabajo.codigo_actividad = actividad.codigo_actividad ) ";
		}

		if (in_array('area_trabajo', $this->agrupador)) {
			$s .= " LEFT JOIN prm_area_trabajo ON ( trabajo.id_area_trabajo = prm_area_trabajo.id_area_trabajo ) ";
		}
		return $s;
	}

	//WHERE para string de Query. Incluye los filtros agregados anteriormente.
	//@param from: si viene de la query de trabajo o de cobro.
	function sWhere($from = 'trabajo') {
		$s = " WHERE 1 ";
		foreach ($this->filtros as $campo => $filtro) {
			foreach ($filtro as $booleano => $valor) {
				if ($booleano == 'positivo') {
					if (sizeof($filtro['positivo']) > 1) {
						$lista_opciones = join("','", $valor);
						$s .= "	AND " . $campo . " IN ('" . $lista_opciones . "')";
					} else {
						$s .= " AND " . $campo . " = '" . $valor[0] . "'";
					}
				} else {
					if (sizeof($filtro['negativo']) > 1) {
						$lista_opciones = join("','", $valor);
						$s .= "	AND (" . $campo . " NOT IN ('" . $lista_opciones . "') OR " . $campo . " IS NULL)";
					} else {
						$s .= " AND (" . $campo . " <> '" . $valor[0] . "' OR " . $campo . " IS NULL)";
					}
				}
			}
		}
		//Añado el periodo determinado
		if ($from == 'trabajo') {
			$campo_fecha = $this->campo_fecha;
			$campo_fecha_2 = $this->campo_fecha_2;
		} else {
			$campo_fecha = $this->campo_fecha_cobro;
			$campo_fecha_2 = $this->campo_fecha_cobro_2;
		}
		if (!empty($this->rango)) {
			$s .= " AND ( " . $campo_fecha . " BETWEEN '" . Utiles::fecha2sql($this->rango['fecha_ini']) . "' AND '" . Utiles::fecha2sql($this->rango['fecha_fin']) . " 23:59:59' ";
			if ($campo_fecha_2) {
				$s.= " OR ( (" . $campo_fecha . " IS NULL OR " . $campo_fecha . " = '00-00-0000') AND " . $campo_fecha_2 . " BETWEEN '" . Utiles::fecha2sql($this->rango['fecha_ini']) . "' AND '" . Utiles::fecha2sql($this->rango['fecha_fin']) . " 23:59:59' ) ";
			}
			$s.=') ';
		}
		/* Si se filtra el periodo por cobro, los trabajos sin cobro emitido (y posteriores) no se ven */
		if (($campo_fecha == 'cobro.fecha_fin' || $campo_fecha == 'cobro.fecha_emision') && $from == 'trabajo')
			$s .= " AND cobro.estado IN ('EMITIDO','FACTURADO','ENVIADO AL CLIENTE','PAGO PARCIAL','PAGADO') ";

		foreach ($this->filtros_especiales as $fe) {
			$s .= " AND (" . $fe . ") ";
		}

		return $s;
	}

	//GROUP BY en string de Query. Agrupa según la vista. (arreglo de agrupadores se usa al construir los arreglos de resultados.
	function sGroup() {
		if (!$this->vista) {
			return ' GROUP BY agrupador_general, id_cobro ';
		}

		$agrupa = array();
		$agrupa[] = "id_usuario";
		$agrupa[] = "id_cliente";
		$agrupa[] = "codigo_asunto";

		if ($this->requiereMoneda($this->tipo_dato)) {
			$agrupa[] = "id_cobro";
		}

		foreach ($this->id_agrupador as $a) {
			$agrupa[] = $a;
		}
		$group_by = ' GROUP BY ' . implode(', ', $agrupa);
		return $group_by;
	}

	//ORDER BY en string de Query.
	function sOrder() {
		if (!$this->vista) {
			return '';
		}
		return ' ORDER BY ' . implode(', ', $this->orden_agrupador);
	}

	//String de Query.
	function sQuery() {
		$s = '';
		$s .= $this->sSelect();
		$s .= $this->sFrom();
		$s .= $this->sWhere();
		$s .= $this->sGroup();
		$s .= $this->sOrder();

		return $s;
	}

	//Ejecuta la Query y guarda internamente las filas de resultado.
	function Query() {
		$stringquery = "";
		$resp = mysql_unbuffered_query($this->sQuery(), $this->sesion->dbh) or Utiles::errorSQL($this->sQuery(), __FILE__, __LINE__, $this->sesion->dbh);

		$this->row = array();
		while ($row = mysql_fetch_array($resp)) {
			$this->row[] = $row;
		}

		// En caso de filtrar por área o categoría de usuario no se toman en cuenta los cobros sin horas.
		if (
			$this->requiereMoneda($this->tipo_dato)
			&& $this->tipo_dato != 'valor_hora'
			&& $this->tipo_dato != 'costo'
			&& $this->tipo_dato != 'costo_hh'
			&& $this->cobroQuery()
			&& !$this->filtros['usuario.id_area_usuario']['positivo'][0]
			&& !$this->filtros['usuario.id_categoria_usuario']['positivo'][0]
			&& !$this->ignorar_cobros_sin_horas
		) {

			$cobroquery = $this->cobroQuery();
			$stringquery = " \n\n\n union all \n\n\n $cobroquery";
			$testimonio = "INSERT INTO z_log_fff SET fecha = NOW(), mensaje='" . mysql_real_escape_string($this->sQuery() . $stringquery . "\n\n --tipo dato es " . $this->tipo_dato, $this->sesion->dbh) . "'";
			$respt = mysql_query($testimonio, $this->sesion->dbh);

			$resp = mysql_query($cobroquery, $this->sesion->dbh) or Utiles::errorSQL($cobroquery, __FILE__, __LINE__, $this->sesion->dbh);
			while ($row = mysql_fetch_array($resp)) {
				$this->row[] = $row;
			}
		} else {
			$testimonio = "INSERT INTO z_log_fff SET fecha = NOW(), mensaje='" . mysql_real_escape_string($this->sQuery() . "\n\n --tipo dato es " . $this->tipo_dato, $this->sesion->dbh) . "'";
			$respt = mysql_query($testimonio, $this->sesion->dbh);
		}
	}

	/*
	  Constructor de Arreglo Resultado. TIPO BARRAS.
	  Entrega un arreglo lineal de Indices, Valores y Labels. Además indica Total.
	 */

	function toBars() {
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
	function fixBar($data, $data2) {
		foreach ($data2 as $k => $d) {
			if (!isset($data[$k])) {
				$data[$k]['valor'] = 0;
				$data[$k]['label'] = $d['label'];
			}
		}
		return $data;
	}

	//divide un valor por su valor_divisor
	function dividir(&$a) {
		if ($a['valor_divisor'] == 0) {
			if ($a['valor'] != 0) {
				$a['valor'] = '99999!*';
			}
		} else {
			$a['valor'] = number_format($a['valor'] / $a['valor_divisor'], 2, ".", "");
		}
	}

	/* Entrega el label a usar para un agrupador */

	function label($agrupador) {
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

	function toCross() {
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

	function toArray() {
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
				$resultado = number_format($resultado, 2, ".", "");
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

	function rellenar(&$a, $b) {
		$a['valor'] = 0;
		$a['valor_divisor'] = 0;
		$a['filas'] = 0;
		$a['filtro_campo'] = $b['filtro_campo'];
		$a['filtro_valor'] = $b['filtro_valor'];
	}

	//Arregla espacios vacíos en Arreglos. Retorna data con los campos extra en data2 (rellenando con 0).
	function fixArray($data, $data2) {
		foreach ($data2 as $ag1 => $a) {
			if (is_array($a)) {
				foreach ($a as $ag2 => $b) {
					if (is_array($b)) {
						if (!isset($data[$ag1][$ag2])) {
							Reporte::rellenar($data[$ag1][$ag2], $data2[$ag1][$ag2]);
						}

						foreach ($b as $ag3 => $c) {
							if (is_array($c)) {
								if (!isset($data[$ag1][$ag2][$ag3])) {
									Reporte::rellenar($data[$ag1][$ag2][$ag3], $data2[$ag1][$ag2][$ag3]);
								}

								foreach ($c as $ag4 => $d) {
									if (is_array($d)) {
										if (!isset($data[$ag1][$ag2][$ag3][$ag4])) {
											Reporte::rellenar($data[$ag1][$ag2][$ag3][$ag4], $data2[$ag1][$ag2][$ag3][$ag4]);
										}

										foreach ($d as $ag5 => $e) {
											if (is_array($e)) {
												if (!isset($data[$ag1][$ag2][$ag3][$ag4][$ag5])) {
													Reporte::rellenar($data[$ag1][$ag2][$ag3][$ag4][$ag5], $data2[$ag1][$ag2][$ag3][$ag4][$ag5]);
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
	function simboloTipoDato($tipo_dato, $sesion, $id_moneda = '1') {
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
			case "valor_cobrado":
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
	function sTipoDato($tipo_dato) {
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
	function unidad($tipo_dato, $sesion, $id_moneda = '1') {
		switch ($tipo_dato) {
			case "valor_por_cobrar":
			case "valor_cobrado":
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
	function FormatoValor($sesion, $valor, $tipo_dato = "horas_", $tipo_reporte = "", $formato_valor = array('cifras_decimales' => 2, 'miles' => '.', 'decimales' => ',')) {
		if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'MostrarSoloMinutos') ) || ( method_exists('Conf', 'MostrarSoloMinutos') && Conf::MostrarSoloMinutos() ) ) && strpos($tipo_dato, "oras_")) {
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