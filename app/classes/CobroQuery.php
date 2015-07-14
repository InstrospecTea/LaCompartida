<?php

class CobroQuery extends Cobro {

	public function genera_cobros($filtros) {
		$where = 1;
		$having = "";
		if ($filtros['activo']) {
			$where .= " AND contrato.activo = 'SI' ";
			$having = " HAVING cantidad_asuntos > 0";
		} else {
			$where .= " AND contrato.activo = 'NO' ";
		}
		if ($filtros['id_usuario']) {
			$where .= " AND contrato.id_usuario_responsable = '{$filtros['id_usuario']}' ";
		}
		if ($filtros['id_usuario_secundario']) {
			$where .= " AND contrato.id_usuario_secundario = '{$filtros['id_usuario_secundario']}' ";
		}
		if ($filtros['codigo_asunto']) {
			$where .= " AND asunto.codigo_asunto ='{$filtros['codigo_asunto']}' ";
		}
		if ($filtros['codigo_cliente']) {
			$where .= " AND cliente.codigo_cliente = '{$filtros['codigo_cliente']}' ";
		}
		if ($filtros['id_grupo_cliente']) {
			$where .= " AND (cliente.id_grupo_cliente = '{$filtros['id_grupo_cliente']}' OR grupo_cliente.id_grupo_cliente = '{$filtros['id_grupo_cliente']}' )";
		}
		if ($filtros['forma_cobro']) {
			$where .= " AND contrato.forma_cobro = '{$filtros['forma_cobro']}' ";
		}
		if ($filtros['tipo_liquidacion']) {//1-2 = honorarios-gastos, 3 = mixtas
			$where .= " AND contrato.separar_liquidaciones = '" . ($filtros['tipo_liquidacion'] == '3' ? 0 : 1) . "' ";
			if ($filtros['tipo_liquidacion'] == 1) {
				$where .= " AND (contrato.forma_cobro != 'HITOS') ";
			}
		}

		$mostrar_codigo_asuntos = "";

		if (Conf::GetConf($this->sesion, 'MostrarCodigoAsuntoEnListados')) {
			$mostrar_codigo_asuntos = "asunto.codigo_asunto";
			if (Conf::GetConf($this->sesion, 'CodigoSecundario')) {
				$mostrar_codigo_asuntos .= "_secundario";
			}
			$mostrar_codigo_asuntos .= ", ' ', ";
		}


		global $contratofields;

		$query = "SELECT SQL_CALC_FOUND_ROWS
						contrato.id_contrato,
						contrato.codigo_cliente,
						cliente.glosa_cliente,
						contrato.forma_cobro,
						contrato.monto,
						contrato.codigo_idioma,
						moneda.simbolo,
						GROUP_CONCAT($mostrar_codigo_asuntos glosa_asunto SEPARATOR '|') as asuntos,
						count(glosa_asunto) as cantidad_asuntos,
						asunto.glosa_asunto as asunto_lista,
						contrato.forma_cobro,
						CONCAT(moneda_monto.simbolo, ' ', contrato.monto) AS monto_total,
						contrato.activo,
						(SELECT MAX(fecha_fin) FROM cobro WHERE cobro.id_contrato = contrato.id_contrato) as fecha_ultimo_cobro,
						tarifa.glosa_tarifa,
						contrato.incluir_en_cierre,
						contrato.retainer_horas,
						moneda_monto.simbolo as simbolo_moneda_monto,
						moneda_monto.cifras_decimales as cifras_decimales_moneda_monto,
						contrato.separar_liquidaciones";
		($Slim = Slim::getInstance('default', true)) ? $Slim->applyHook('hook_query_generar_cobro') : false;
		$query .= "	FROM contrato
						JOIN tarifa ON contrato.id_tarifa = tarifa.id_tarifa
						JOIN cliente ON cliente.codigo_cliente=contrato.codigo_cliente AND cliente.activo = 1
						LEFT JOIN asunto ON asunto.id_contrato=contrato.id_contrato
						LEFT JOIN grupo_cliente  ON grupo_cliente.codigo_cliente=contrato.codigo_cliente
						JOIN prm_moneda as moneda ON (moneda.id_moneda=contrato.id_moneda)
						LEFT JOIN prm_moneda as moneda_monto ON moneda_monto.id_moneda=contrato.id_moneda_monto
					WHERE $where
					GROUP BY contrato.id_contrato
					$having";
		return $query;
	}
}