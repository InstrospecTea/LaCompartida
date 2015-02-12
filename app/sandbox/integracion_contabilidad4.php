<?php
require_once('../conf.php');

$Sesion = new Sesion;

if (Conf::GetConf($Sesion, 'NuevaLibreriaNusoap')) {
	require_once('../../web_services/lib2/nusoap.php');
} else {
	require_once('../../web_services/lib/nusoap.php');
}

function ListaCobrosFacturados($usuario, $password, $timestamp) {
	$Sesion = new Sesion();
	$time = mktime();

	//Mapeo usernames a centro_de_costos
	$username_centro_de_costo = array();

	if (UtilesApp::VerificarPasswordWebServices($usuario, $password)) {
		$lista_cobros = array();

		if ($timestamp) {
			$query_timestamp = ' OR cobro.id_cobro IN (SELECT DISTINCT id_cobro FROM log_contabilidad WHERE timestamp >= ' . intval(mysql_real_escape_string($timestamp)) . " ) ";
		} else {
			$query_timestamp = '';
		}

		$query = "SELECT cobro.id_cobro,
				cobro.codigo_cliente,
				cobro.estado,
				cobro.opc_moneda_total,
				cobro.fecha_ini,
				cobro.fecha_fin,
				contrato.factura_razon_social,
				contrato.factura_direccion,
				contrato.rut,
				cobro.monto_subtotal,
				cobro.subtotal_gastos,
				cobro.descuento,
				cobro.monto,
				cobro.monto_gastos,
				cobro.fecha_emision,
				cobro_moneda_tarifa.tipo_cambio as tipo_cambio_moneda,
				cobro.tipo_cambio_moneda_base,
				cobro.nota_venta_contabilidad,
				cobro.se_esta_cobrando,
				usuario.username AS encargado_comercial,
				usuario_secundario.username AS encargado_secundario,
				cobro.estado_contabilidad,
				prm_moneda.cifras_decimales,
				cobro_moneda_mt.tipo_cambio as tipo_cambio_moneda_total,
				prm_moneda_total.cifras_decimales as cifras_decimales_total,
				prm_moneda_total.codigo as codigo,
				carta.descripcion as glosa_carta,
				cobro.documento
			FROM cobro
				JOIN cobro_moneda as cobro_moneda_mt ON cobro_moneda_mt.id_cobro = cobro.id_cobro AND cobro_moneda_mt.id_moneda = cobro.opc_moneda_total
				JOIN cobro_moneda as cobro_moneda_tarifa ON cobro_moneda_tarifa.id_cobro = cobro.id_cobro AND cobro_moneda_tarifa.id_moneda = cobro.id_moneda
				LEFT JOIN prm_moneda ON prm_moneda.id_moneda = cobro.id_moneda
				LEFT JOIN prm_moneda AS prm_moneda_total ON prm_moneda_total.id_moneda = cobro.opc_moneda_total
				LEFT JOIN carta ON carta.id_carta = cobro.id_carta
				LEFT JOIN contrato ON contrato.id_contrato = cobro.id_contrato
				LEFT JOIN usuario ON contrato.id_usuario_responsable = usuario.id_usuario
				LEFT JOIN usuario AS usuario_secundario ON contrato.id_usuario_secundario = usuario_secundario.id_usuario
			WHERE cobro.estado_contabilidad IN ('PARA INFORMAR', 'PARA INFORMAR Y FACTURAR')
				{$query_timestamp}
			GROUP BY cobro.id_cobro";

		if (!($resp = mysql_query($query, $Sesion->dbh))) {
			return new soap_fault('Client', '', 'Error SQL.' . $query, '');
		}

		while ($temp = mysql_fetch_array($resp)) {
			$cobro = array();
			$id_cobro = $temp['id_cobro'];

			$cobro['id_cobro'] = $id_cobro;
			$cobro['nota_venta'] = $temp['nota_venta_contabilidad'];
			$cobro['descripcion'] = $temp['se_esta_cobrando'];
			$cobro['encargado_comercial'] = $temp['encargado_comercial'];
			$cobro['encargado_secundario'] = $temp['encargado_secundario'];
			$cobro['codigo_cliente'] = $temp['codigo_cliente'];
			$cobro['estado'] = $temp['estado'];
			$cobro['moneda'] = $temp['codigo'];

			$cobro['fecha_ini'] = $temp['fecha_ini'];
			$cobro['fecha_fin'] = $temp['fecha_fin'];
			$cobro['razon_social'] = $temp['factura_razon_social'];
			$cobro['direccion'] = $temp['factura_direccion'];
			$cobro['rut'] = $temp['rut'];

			$cobro['timestamp'] = $time;
			$cobro['facturar'] = 'NO';

			if ($temp['estado_contabilidad'] == 'PARA INFORMAR Y FACTURAR' ||
					$temp['estado_contabilidad'] == 'INFORMADO Y FACTURADO') {
				$cobro['facturar'] = 'SI';
			}

			/* INICIA CALCULO DE MONTOS DEL COBRO. VER cobros6.php */
			$c = new Cobro($Sesion);
			$c->Load($id_cobro);
			$x_resultados = UtilesApp::ProcesaCobroIdMoneda($Sesion, $c->fields['id_cobro'], array(), 0, true);

			/* FIN CALCULO DE MONTOS DEL COBRO */

			//calculo del monto sin iva
			$cobro['total_honorarios_sin_iva'] = number_format($x_resultados['monto_honorarios'][$c->fields['opc_moneda_total']], 2, '.', '');

			//monto del cobro
			$cobro['total_honorarios'] = number_format($x_resultados['monto'][$c->fields['opc_moneda_total']], 2, '.', '');

			//gastos sin iva
			$cobro['total_gastos_sin_iva'] = number_format($temp['subtotal_gastos'], $temp['cifras_decimales_total'], '.', '');
			//gastos con iva
			$cobro['total_gastos'] = number_format($temp['monto_gastos'], $temp['cifras_decimales_total'], '.', '');

			$cobro['fecha_emision'] = $temp['fecha_emision'];

			$cobro['glosa_carta'] = $temp['glosa_carta'];
			$cobro['numero_factura'] = $temp['documento'];

			/* Se crea una instancia de Reporte para ver el peso de cada usuario */
			$reporte = new Reporte($Sesion);
			$reporte->id_moneda = $temp['opc_moneda_total'];
			$reporte->setTipoDato('valor_cobrado');
			$reporte->setVista('id_cobro-username');
			$reporte->addFiltro('cobro', 'id_cobro', $id_cobro);
			$reporte->Query();
			$r = $reporte->toArray();

			/* Se obtienen además las horas de cada usuario. */
			$reporte = new Reporte($Sesion);
			$reporte->id_moneda = $temp['opc_moneda_total'];
			$reporte->setVista('id_cobro-username');
			$reporte->addFiltro('cobro', 'id_cobro', $id_cobro);
			$reporte->setTipoDato('horas_cobrables');
			$reporte->Query();
			$r_cobradas = $reporte->toArray();

			$reporte = new Reporte($Sesion);
			$reporte->id_moneda = $temp['opc_moneda_total'];
			$reporte->setVista('id_cobro-username');
			$reporte->addFiltro('cobro', 'id_cobro', $id_cobro);
			$reporte->setTipoDato('horas_trabajadas');
			$reporte->Query();
			$r_trabajadas = $reporte->toArray();

			$total_cobrado = $r[$id_cobro][$id_cobro][$id_cobro][$id_cobro][$id_cobro]['valor'];

			// Asuntos
			$query_asuntos = "SELECT
				a.codigo_asunto AS codigo,
				a.codigo_asunto_secundario AS codigo_secundario,
				a.glosa_asunto AS glosa,
				u.username,
				SUM(TIME_TO_SEC(t.duracion)) / 3600.0 AS horas_trabajadas,
				SUM(TIME_TO_SEC(t.duracion_cobrada)) / 3600.0 AS horas_cobradas
				FROM trabajo t
				INNER JOIN asunto a ON a.codigo_asunto = t.codigo_asunto
				INNER JOIN usuario u ON u.id_usuario = t.id_usuario
				WHERE t.id_cobro = '{$id_cobro}' AND t.cobrable = 1
				GROUP BY t.id_usuario";
			$stmt = $Sesion->pdodbh->prepare($query_asuntos);
			$stmt->execute();

			$asuntos = array();
			$asuntos_array = array();
			$participacion_asunto = array();

			while ($r_asunto = $stmt->fetch()) {
				$cod_asunto = $r_asunto['codigo'];

				if (!array_key_exists($cod_asunto, $asuntos_array)) {
					$asuntos_array[$cod_asunto] = array(
						'codigo' => $cod_asunto,
						'codigo_secundario' => $r_asunto['codigo_secundario'],
						'glosa' => $r_asunto['glosa'],
						'valor' => 0.0
					);
				}

				if (!array_key_exists('horas_trabajadas', $asuntos_array[$cod_asunto])) {
					$asuntos_array[$cod_asunto]['horas_trabajadas'] = 0;
					$asuntos_array[$cod_asunto]['horas_cobradas'] = 0;
				}

				$asuntos_array[$cod_asunto]['horas_trabajadas'] += $r_asunto['horas_trabajadas'];
				$asuntos_array[$cod_asunto]['horas_cobradas'] += $r_asunto['horas_cobradas'];

				$participacion_asunto[$cod_asunto][$r_asunto['username']]['horas_trabajadas'] = number_format($r_asunto['horas_trabajadas'], 2, '.', '');
				$participacion_asunto[$cod_asunto][$r_asunto['username']]['horas_cobradas'] = number_format($r_asunto['horas_cobradas'], 2, '.', '');
			}

			$total_horas_cobradas = $r_cobradas[$id_cobro][$id_cobro][$id_cobro][$id_cobro][$id_cobro]['valor'];

			foreach ($asuntos_array as $cod_asunto => $valores) {
				if ($total_horas_cobradas > 0) {
					$valores['valor'] = ($valores['horas_cobradas'] / $total_horas_cobradas) * $total_cobrado;
				}

				// Formateo
				$valores['valor'] = number_format($valores['valor'], 2, '.', '');
				$valores['horas_trabajadas'] = number_format($valores['horas_trabajadas'], 2, '.', '');
				$valores['horas_cobradas'] = number_format($valores['horas_cobradas'], 2, '.', '');

				$asuntos[] = $valores;
			}

			$cobro['ListaAsuntos'] = $asuntos;

			$usuarios_cobro = array();

			if (is_array($r[$id_cobro][$id_cobro][$id_cobro][$id_cobro][$id_cobro])) {
				foreach ($r[$id_cobro][$id_cobro][$id_cobro][$id_cobro][$id_cobro] as $key => $dato) {
					if (is_array($dato)) {
						$usuario_cobro = array();
						$usuario_cobro['username'] = $key;

						if (!isset($username_centro_de_costo[$key])) {
							$usuario_temp = new UsuarioExt($Sesion);
							$usuario_temp->LoadByNick($key);
							if ($usuario_temp->Loaded()) {
								$username_centro_de_costo[$key] = $usuario_temp->fields['centro_de_costo'];
							} else {
								$username_centro_de_costo[$key] = '';
							}
						}

						$usuario_cobro['centro_de_costo'] = $username_centro_de_costo[$key];
						$usuario_cobro['valor'] = number_format($dato['valor'], 2, '.', '');
						$usuario_cobro['horas_trabajadas'] = number_format($r_trabajadas[$id_cobro][$id_cobro][$id_cobro][$id_cobro][$id_cobro][$key]['valor'], 2, '.', '');
						$usuario_cobro['horas_cobradas'] = number_format($r_cobradas[$id_cobro][$id_cobro][$id_cobro][$id_cobro][$id_cobro][$key]['valor'], 2, '.', '');

						if ($total_cobrado) {
							$usuario_cobro['peso'] = $dato['valor'] / $total_cobrado;
						} else {
							$usuario_cobro['peso'] = 0;
						}

						// Listado de asuntos para el usuario
						$usuario_asuntos = array();
						foreach ($asuntos as $as) {
							$cod_asunto = $as['codigo'];

							$as['horas_trabajadas'] = $participacion_asunto[$cod_asunto][$key]['horas_trabajadas'];
							$as['horas_cobradas'] = $participacion_asunto[$cod_asunto][$key]['horas_cobradas'];

							if ($usuario_cobro['horas_cobradas'] > 0) {
								$as['valor'] = ($as['horas_cobradas'] / $usuario_cobro['horas_cobradas']) * $usuario_cobro['valor'];
							}

							$as['valor'] = number_format($as['valor'], 2, '.', '');

							$usuario_asuntos[] = $as;
						}

						$usuario_cobro['ListaAsuntos'] = $usuario_asuntos;

						$usuarios_cobro[] = $usuario_cobro;
					}
				}
			}

			$cobro['ListaUsuariosCobro'] = $usuarios_cobro;

			//Actualizo los datos:
			$nuevo_estado = 'INFORMADO';
			if ($cobro['facturar'] == 'SI') {
				$nuevo_estado = 'INFORMADO Y FACTURADO';
			}

			$query_actualiza = "UPDATE cobro SET fecha_contabilidad = NOW(), estado_contabilidad = '{$nuevo_estado}' WHERE id_cobro = '{$id_cobro}'";
			$respuesta = mysql_query($query_actualiza, $Sesion->dbh) or Utiles::errorSQL($query_actualiza, __FILE__, __LINE__, $Sesion->dbh);

			// $query_ingresa = "INSERT INTO log_contabilidad (id_cobro,timestamp) VALUES (" . $id_cobro . "," . $time . ");";
			// $respuesta_in = mysql_query($query_ingresa, $Sesion->dbh) or Utiles::errorSQL($query_ingresa, __FILE__, __LINE__, $Sesion->dbh);

			$query_facturas = " SELECT
				factura.id_factura,
				factura.id_factura_padre,
				factura.codigo_cliente,
				factura.comprobante_erp,
				factura.condicion_pago,
				SUM(factura_cobro.monto_factura) as monto_factura,
				factura.numero,
				prm_documento_legal.glosa as tipo,
				prm_estado_factura.glosa,
				prm_estado_factura.codigo,
				factura.subtotal_sin_descuento,
				honorarios,
				ccfm.saldo as saldo,
				subtotal_gastos,
				subtotal_gastos_sin_impuesto,
				iva,
				prm_documento_legal.codigo as cod_tipo,
				cliente,
				RUT_cliente,
				direccion_cliente,
				fecha,
				descripcion,
				factura.id_moneda,
				pm.tipo_cambio,
				pm.cifras_decimales,
				prm_moneda.codigo as codigo_moneda_factura,
				factura.serie_documento_legal as serie
			FROM factura
				JOIN prm_moneda AS pm ON factura.id_moneda = pm.id_moneda
				LEFT JOIN cta_cte_fact_mvto AS ccfm ON factura.id_factura = ccfm.id_factura
				JOIN prm_documento_legal ON factura.id_documento_legal = prm_documento_legal.id_documento_legal
				JOIN prm_estado_factura ON factura.id_estado = prm_estado_factura.id_estado
				JOIN prm_moneda ON prm_moneda.id_moneda = factura.id_moneda
				LEFT JOIN factura_cobro ON factura_cobro.id_factura = factura.id_factura
			WHERE factura.id_cobro = '{$id_cobro}'
			GROUP BY factura.id_factura";

			$respu = mysql_query($query_facturas, $Sesion->dbh) or Utiles::errorSQL($query_facturas, __FILE__, __LINE__, $Sesion->dbh);

			$facturas_cobro = Array();
			while (list( $id_factura, $id_factura_padre, $codigo_cliente_factura, $comprobante_erp, $condicion_pago, $monto, $numero, $tipo, $estado, $cod_estado, $subtotal_honorarios, $honorarios, $saldo, $subtotal_gastos, $subtotal_gastos_sin_impuesto, $impuesto, $cod_tipo, $cliente, $RUT_cliente, $direccion_cliente, $fecha, $descripcion, $id_moneda_factura, $tipo_cambio_factura, $cifras_decimales_factura, $codigo_moneda_factura, $serie ) = mysql_fetch_array($respu)) {

				$mult = $cod_tipo == 'NC' ? 1 : -1;

				// Si el documento no esta anulado, lo cuento para el saldo disponible a facturar (notas de credito suman, los demas restan)
				if ($cod_estado != 'A') {
					$saldo_honorarios += $subtotal_honorarios * $mult;
					$saldo_gastos_con_impuestos += $subtotal_gastos * $mult;
					$saldo_gastos_sin_impuestos += $subtotal_gastos_sin_impuesto * $mult;
				}

				$factura_cobro['id_factura'] = $id_factura;
				$factura_cobro['codigo_cliente'] = $codigo_cliente_factura;
				$factura_cobro['codigo_factura_lemontech'] = $id_factura;
				$factura_cobro['codigo_factura_asociada_lemontech'] = $id_factura_padre;
				$factura_cobro['comprobante_erp'] = $comprobante_erp;
				$factura_cobro['condicion_pago'] = $condicion_pago;
				$factura_cobro['tipo'] = $tipo;
				$factura_cobro['numero'] = $numero;
				$factura_cobro['honorarios'] = number_format($subtotal_honorarios, 2, '.', '');
				$factura_cobro['gastos_sin_iva'] = number_format($subtotal_gastos_sin_impuesto, 2, '.', '');
				$factura_cobro['gastos_con_iva'] = number_format($subtotal_gastos, 2, '.', '');
				$factura_cobro['impuestos'] = number_format($impuesto, 2, '.', '');
				$factura_cobro['total'] = number_format($subtotal_honorarios + $subtotal_gastos + $subtotal_gastos_sin_impuesto + $impuesto, 2, '.', '');
				$factura_cobro['estado'] = $estado;
				$factura_cobro['saldo'] = number_format($saldo, 2, '.', '');

				$factura_cobro['cliente'] = $cliente;
				$factura_cobro['rut_cliente'] = $RUT_cliente;
				$factura_cobro['direccion_cliente'] = $direccion_cliente;
				$factura_cobro['fecha'] = $fecha;
				$factura_cobro['descripcion'] = $descripcion;
				$factura_cobro['moneda'] = $codigo_moneda_factura;

				if (Conf::GetConf($Sesion, 'NumeroFacturaConSerie')) {
					$serie = $serie ? $serie : '001';
					$factura_cobro['serie'] = str_pad($serie, 3, '0', STR_PAD_LEFT);
				} else {
					$factura_cobro['serie'] = $serie;
				}

				$uc = $usuarios_cobro;
				$factura_cobro['ListaUsuariosFactura'] = $uc;

				foreach ($factura_cobro['ListaUsuariosFactura'] as $key => $user) {
					$factura_cobro['ListaUsuariosFactura'][$key]['valor'] = number_format($user['peso'] * $factura_cobro['honorarios'], 2, '.', '');
				}

				// Incluir los pagos
				$query_pagos = "SELECT
						fp.id_factura_pago AS id,
						fp.fecha,
						fp.monto,
						prm_moneda.codigo AS moneda,
						ccfmn.monto_pago AS monto_pagado,
						fp.tipo_doc AS tipo_documento,
						fp.nro_documento AS numero_documento,
						fp.nro_cheque AS numero_cheque,
						fp.descripcion,
						prm_banco.nombre AS banco,
						cuenta_banco.numero AS cuenta,
						fp.pago_retencion,
						pfpc.glosa AS concepto
					FROM factura_pago fp
						LEFT JOIN cta_cte_fact_mvto ccfm_pago ON ccfm_pago.id_factura_pago = fp.id_factura_pago
						LEFT JOIN cta_cte_fact_mvto_neteo ccfmn ON ccfmn.id_mvto_pago = ccfm_pago.id_cta_cte_mvto
						LEFT JOIN cta_cte_fact_mvto ccfm_deuda ON ccfm_deuda.id_cta_cte_mvto = ccfmn.id_mvto_deuda
						LEFT JOIN factura f ON f.id_factura = ccfm_deuda.id_factura
						LEFT JOIN prm_moneda ON prm_moneda.id_moneda = fp.id_moneda
						LEFT JOIN prm_banco ON prm_banco.id_banco = fp.id_banco
						LEFT JOIN cuenta_banco ON cuenta_banco.id_cuenta = fp.id_cuenta
						LEFT JOIN prm_factura_pago_concepto pfpc ON pfpc.id_concepto = fp.id_concepto
					WHERE f.id_factura = '{$id_factura}'";

				$result_pagos = mysql_query($query_pagos, $Sesion->dbh) or Utiles::errorSQL($query_pagos, __FILE__, __LINE__, $Sesion->dbh);
				$facturas_pagos = array();
				while ($fp = mysql_fetch_assoc($result_pagos)) {
					$facturas_pagos[] = $fp;
				}

				$factura_cobro['ListaPagos'] = $facturas_pagos;

				$facturas_cobro[] = $factura_cobro;
			}
			$cobro['ListaFacturasCobro'] = $facturas_cobro;

			$lista_cobros[] = $cobro;
		}

		return new soapval('lista_cobros_emitidos', 'ListaCobros', $lista_cobros);
	}

	return new soap_fault('Client', '', 'Usuario o contraseña incorrecta.', '');
}

try {
	$response = ListaCobrosFacturados($_REQUEST['usuario'], $_REQUEST['password'], $_REQUEST['timestamp']);
	var_dump($response);
} catch(Exception $e) {
	var_dump($e->getTraceAsString());
}

exit;