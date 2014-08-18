<?php
require_once dirname(__FILE__) . '/../../conf.php';

header('Content-type: text/html; charset=iso-8859-1');

$sesion = new Sesion(array('COB'));

$cobro = new Cobro($sesion);
$contrato = new Contrato($sesion);
$documento_cobro = new Documento($sesion);
$factura = new Factura($sesion);
$idioma = new Objeto($sesion, '', '', 'prm_idioma', 'codigo_idioma');
$factura_pago = new FacturaPago($sesion);
if (!$id_cobro) {
	$id_cobro = $_POST['id_cobro'];
}
if (!$opc) {
	$opc = $_POST['opc'];
}
$confirma = $_POST['confirma'];
$opc_informar_contabilidad = $_POST['opc_ic'];




if (!$cobro->Load($id_cobro)) {
	$pagina = new PaginaCobro($sesion);
	$pagina->FatalError(__('Cobro inválido'));
}

$cobro_moneda = new CobroMoneda($sesion);
$cobro_moneda->Load($cobro->fields['id_cobro']);
$tipo_cambio_moneda_total = $cobro_moneda->GetTipoCambio($id_cobro, $cobro->fields['opc_moneda_total'] != '' ? $cobro->fields['opc_moneda_total'] : 1);
if ($documento_cobro->LoadByCobro($id_cobro)) {
	$moneda_documento = new Moneda($sesion);
	$moneda_documento->Load($documento_cobro->fields['id_moneda']);
} else {
	$moneda_documento = new Moneda($sesion);
	$moneda_documento->Load($cobro->fields['opc_moneda_total']);
}

if ($opc == 'refrescar') {
	switch ($cobro->fields['estado_contabilidad']) {
		case 'NO INFORMADO':
			$estado_c = __('Sin informar');
			$titulo_c = __('El Cobro no ha sido informado.');
			break;
		case 'PARA INFORMAR':
			$estado_c = __('Para informar');
			$titulo_c = __('El Cobro se ha informado a Contabilidad');
			break;
		case 'PARA INFORMAR Y FACTURAR':
			$estado_c = __('Para informar y facturar');
			$titulo_c = __('El Cobro se ha informado a Contabilidad con la instrucción de facturar.');
			break;
		case 'INFORMADO':
			$estado_c = __('Informado');
			$titulo_c = __('El Cobro ha sido requerido por Contabilidad');
			break;
		case 'INFORMADO Y FACTURADO':
			$estado_c = __('Informado para Facturar');
			$titulo_c = __('El Cobro ha sido requerido por Contabilidad, se ha indicado que debe facturarse');
			break;
		default:
			$estado_c = $cobro->fields['estado_contabilidad'];
	}

	echo $estado_c . '|' . $titulo_c . '|' . $cobro->fields['estado_contabilidad'] . '|Estado cobro refrescado|' . $cobro->fields['nota_venta_contabilidad'];
} else if ($opc == 'listapagos') {

	$x_resultados = UtilesApp::ProcesaCobroIdMoneda($sesion, $cobro->fields['id_cobro'], array(), 0, true);

	$retorno = '<tr style="height: 26px;">
						<td colspan=3 align="left" bgcolor="#dfdfdf" style="font-size: 11px; font-weight: bold; vertical-align: middle;">
							<img src="' . Conf::ImgDir() . '/coins_16.png" border="0" alt="Imprimir"/> ';
	$retorno.= __('Pago');
	$retorno.= '</td>		</tr>';


	if ($cobro->fields['incluye_honorarios'] == 1) {
		if ($documento_cobro->fields['honorarios_pagados'] == 'SI') {

			$retorno.= '<tr>	<td align=left colspan="3" >';
			$retorno.= __('Pago de Honorarios Completo');
			$retorno.= '&nbsp;&nbsp;</td>	</tr>';
		} else {
			$retorno.= '<tr><td align=left colspan="3" >';
			$retorno.= __('Saldo Pendiente Honorarios');
			$retorno.= ':&nbsp;&nbsp;</td></tr>	<tr><td align=right colspan="3" >';
			$retorno.= $moneda_documento->fields['simbolo'] . '&nbsp;';
			$retorno.= ($documento_cobro->fields['saldo_honorarios'] > 0) ? $x_resultados['saldo_honorarios'][$moneda_documento->fields['id_moneda']] : '0';
			$retorno.= '</td></tr>';
		}
	}
	if ($cobro->fields['incluye_gastos'] == 1) {
		if ($documento_cobro->fields['gastos_pagados'] == 'SI') {
			$retorno.= '<tr><td align=left colspan="3" >';
			$retorno.= __('Pago de Gastos Completo');
			$retorno.= '&nbsp;&nbsp;</td>	</tr>';
		} else {
			$retorno.= '<tr><td align=left colspan="3" >';
			$retorno.= __('Saldo Pendiente Gastos');
			$retorno.= ':&nbsp;&nbsp; </td>	</tr>	<tr> <td align=right colspan="3" >';
			$retorno.= $moneda_documento->fields['simbolo'] . '&nbsp;';
			$retorno.= ($documento_cobro->fields['saldo_gastos'] > 0) ? $x_resultados['saldo_gastos'][$moneda_documento->fields['id_moneda']] : '0';
			$retorno.= '	</td>	</tr>';
		}
	}
	$retorno.= '<tr><td colspan="3"><hr></td>	</tr>';
	$lista_pagos = $documento_cobro->ListaPagos();
	if ($lista_pagos) {

		$retorno.= '<tr><td align=left colspan="3">';
		$retorno.= __("Lista de Documentos de Pago");
		$retorno.= ':	<input type="hidden" id="hay_pagos" value="si"/></td></tr>';
		$retorno.= $lista_pagos;
		$retorno.= '<tr><td colspan="3"><hr></td></tr>';
	} else {
		$retorno.= '<input type="hidden" id="hay_pagos" value="no"/>';
	}

	$retorno.= '<tr><td colspan="3" align=center><img src="' . Conf::ImgDir() . '/money_16.gif" border=0> <a href="javascript:void(0)" onclick="MostrarTipoCambio()" title="' . __('Tipo de Cambio del Documento de Cobro al ser pagado.') . '">' . __('Actualizar Tipo de Cambio') . '</a></td>	</tr>	<tr>	<td colspan="3" align=center>	&nbsp;	</td>	</tr>';


	$faltan_pagos = $cobro->fields['estado'] != 'INCOBRABLE' && ( $documento_cobro->fields['honorarios_pagados'] == 'NO' || $documento_cobro->fields['gastos_pagados'] == 'NO' );
	$hay_adelantos = false;
	if ($faltan_pagos && !Conf::GetConf($sesion, 'NuevoModuloFactura')) {
		$pago_honorarios = $documento_cobro->fields['saldo_honorarios'] != 0 ? 1 : 0;
		$pago_gastos = $documento_cobro->fields['saldo_gastos'] != 0 ? 1 : 0;
		$hay_adelantos = $documento_cobro->SaldoAdelantosDisponibles($cobro->fields['codigo_cliente'], $cobro->fields['id_contrato'], $pago_honorarios, $pago_gastos) > 0;
	}

	if ($faltan_pagos && !Conf::GetConf($sesion, 'NuevoModuloFactura')) {

		$retorno.= '	<tr>	<td colspan="3"><hr>	</td>		</tr><tr><td colspan="3" align=center>	';
		if ($hay_adelantos)
			$retorno.= '<img src="' . Conf::ImgDir() . '/agregar.gif" border=0 /> <a href="javascript:void(0)" onclick="UsarAdelanto(' . $pago_honorarios . ',' . $pago_gastos . ')" title="' . __('Usar Adelanto') . '">' . __('Usar Adelanto') . '</a><br/><br/>';
		$retorno.= '<img src="' . Conf::ImgDir() . '/agregar.gif" border=0 /> <a href="javascript:void(0)" onclick="AgregarPago()" title="' . __('Agregar Pago') . '">' . __('Agregar Pago') . '</a>';

		$retorno.= '</td></tr><tr><td colspan="3" align=center>&nbsp;	</td>	</tr>';
	}





	echo $retorno;
} else if ($opc == 'guardar' && ($opc_informar_contabilidad == 'parainfo' OR $opc_informar_contabilidad == 'parainfoyfacturar')) {

	if ($cobro->fields['estado_contabilidad'] == 'INFORMADO' && $opc_informar_contabilidad == 'parainfo' && $confirma == 0) {
		$mensajeinterno = 'Se ignora la orden porque el webservice cambia el estado a INFORMADO';
	} else if ($cobro->fields['estado_contabilidad'] == 'INFORMADO Y FACTURADO' && $opc_informar_contabilidad == 'parainfoyfacturar' && $confirma == 0) {
		$mensajeinterno = 'Se ignora la orden porque el webservice cambia el estado a INFORMADO Y FACTURADO';
	} else if ($cobro->fields['estado_contabilidad'] == 'PARA INFORMAR' && $opc_informar_contabilidad == 'parainfo' && $confirma == 0) {
		$mensajeinterno = 'Nada cambia';
	} else if ($cobro->fields['estado_contabilidad'] == 'PARA INFORMAR Y FACTURAR' && $opc_informar_contabilidad == 'parainfoyfacturar' && $confirma == 0) {
		$mensajeinterno = 'Nada cambia';
	} else {
		if ($opc_informar_contabilidad == 'parainfo') {
			$cobro->Edit('estado_contabilidad', 'PARA INFORMAR');
		} else if ($opc_informar_contabilidad == 'parainfoyfacturar') {
			$cobro->Edit('estado_contabilidad', 'PARA INFORMAR Y FACTURAR');
		}

		$cobro->Write();
		$mensajeinterno = 'Cambio Realizado';
	}
	switch ($cobro->fields['estado_contabilidad']) {
		case 'NO INFORMADO':
			$estado_c = __('Sin informar');
			$titulo_c = __('El Cobro no ha sido informado.');
			break;
		case 'PARA INFORMAR':
			$estado_c = __('Para informar');
			$titulo_c = __('El Cobro se ha informado a Contabilidad');
			break;
		case 'PARA INFORMAR Y FACTURAR':
			$estado_c = __('Para informar y facturar');
			$titulo_c = __('El Cobro se ha informado a Contabilidad con la instrucción de facturar.');
			break;
		case 'INFORMADO':
			$estado_c = __('Informado');
			$titulo_c = __('El Cobro ha sido requerido por Contabilidad');
			break;
		case 'INFORMADO Y FACTURADO':
			$estado_c = __('Informado para Facturar');
			$titulo_c = __('El Cobro ha sido requerido por Contabilidad, se ha indicado que debe facturarse');
			break;
		default:
			$estado_c = $cobro->fields['estado_contabilidad'];
	}
	echo $estado_c . '|' . $titulo_c . '|' . $cobro->fields['estado_contabilidad'] . '|' . $mensajeinterno . '|' . $cobro->fields['nota_venta_contabilidad'];
} else if ($opc == 'cajafacturas') {

	$idioma->Load($contrato->fields['codigo_idioma']);
	$contrato->Load($cobro->fields['id_contrato']);



	$x_resultados = UtilesApp::ProcesaCobroIdMoneda($sesion, $cobro->fields['id_cobro'], array(), 0, true);

	if ($cobro->fields['modalidad_calculo'] == 1) {
		$saldo_honorarios = $x_resultados['subtotal_honorarios'][$cobro->fields['opc_moneda_total']] - $x_resultados['descuento_honorarios'][$cobro->fields['opc_moneda_total']];
		$saldo_disponible_trabajos = $saldo_trabajos = $x_resultados['monto_trabajos'][$cobro->fields['opc_moneda_total']] - $x_resultados['descuento_honorarios'][$cobro->fields['opc_moneda_total']];
		if ($saldo_disponible_trabajos < 0) {
			$saldo_disponible_tramites = $saldo_tramites = $x_resultados['monto_tramites'][$cobro->fields['opc_moneda_total']] + $saldo_disponible_trabajos;
			$saldo_disponible_trabajos = 0;
		} else {
			$saldo_disponible_tramites = $saldo_tramites = $x_resultados['monto_tramites'][$cobro->fields['opc_moneda_total']];
		}
	} else {
		if ($cobro->fields['porcentaje_impuesto'] > 0) {
			$honorarios_original = $cobro->fields['monto_subtotal'] - $cobro->fields['descuento'];
		} else {
			$honorarios_original = $cobro->fields['monto'];
		}
		$aproximacion_monto = number_format($honorarios_original, $cobro_moneda->moneda[$cobro->fields['id_moneda']]['cifras_decimales'], '.', '');
		$saldo_honorarios = $aproximacion_monto * ($cobro_moneda->moneda[$cobro->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio']);

		//Caso retainer menor de un valor y distinta tarifa (diferencia por decimales)
		if ((($cobro->fields['total_minutos'] / 60) < $cobro->fields['retainer_horas']) && ($cobro->fields['forma_cobro'] == 'RETAINER' || $cobro->fields['forma_cobro'] == 'PROPORCIONAL') && $cobro->fields['id_moneda'] != $cobro->fields['id_moneda_monto']) {
			$saldo_honorarios = $honorarios_original * ($cobro_moneda->moneda[$cobro->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio']);
		}
		//Caso flat fee
		if ($cobro->fields['forma_cobro'] == 'FLAT FEE' && $cobro->fields['id_moneda'] != $cobro->fields['id_moneda_monto'] && $cobro->fields['id_moneda_monto'] == $cobro->fields['opc_moneda_total'] && empty($cobro->fields['descuento']) && empty($cobro->fields['monto_tramites'])) {
			$saldo_honorarios = $cobro->fields['monto_contrato'];
		}
		$saldo_honorarios = number_format($saldo_honorarios, $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['cifras_decimales'], '.', '');
	}

	if ($saldo_honorarios < 0) {
		$saldo_honorarios = 0;
	}

	$saldo_gastos_con_impuestos = $x_resultados['subtotal_gastos'][$moneda_documento->fields['id_moneda']] - $x_resultados['subtotal_gastos_sin_impuesto'][$moneda_documento->fields['id_moneda']];
	if ($saldo_gastos_con_impuestos < 0) {
		$saldo_gastos_con_impuestos = 0;
	}

	$saldo_gastos_sin_impuestos = $x_resultados['subtotal_gastos_sin_impuesto'][$moneda_documento->fields['id_moneda']];
	if ($saldo_gastos_sin_impuestos < 0) {
		$saldo_gastos_sin_impuestos = 0;
	}

	$diferencia_cobro_factura = Conf::GetConf($sesion, 'NuevoModuloFactura') ? $cobro->DiferenciaCobroConFactura() : null;
		CobroHtml::setMoneda($moneda_documento);
		CobroHtml::setIdioma($idioma);

		$data_cobro = compact('id_cobro', 'saldo_honorarios', 'saldo_gastos_con_impuestos', 'saldo_gastos_sin_impuestos');
		$data_cobro['fecha'] = date('d-m-Y', strtotime($cobro->fields['fecha_emision']));
		if ($cobro->fields['porcentaje_impuesto'] > 0 || $cobro->fields['porcentaje_impuesto_gastos'] > 0) {
			$data_cobro['iva'] = $x_resultados['monto_iva'][$moneda_documento->fields['id_moneda']];
		} else {
			$data_cobro['iva'] = 0;
		}

		echo CobroHtml::cajafacturasHead($diferencia_cobro_factura);
		echo CobroHtml::cajafacturasCobro($data_cobro);

		if (Conf::GetConf($sesion, 'NuevoModuloFactura')) {
			//documentos existentes. usar funcion magica (???)
			$facturas = Factura::ListarDelCobro($sesion, $id_cobro);

			$fila = 0;
			$total_facturas = count($facturas);
			while ($fila < $total_facturas) {
				$datos_factura = $facturas[$fila];
				//si el documento no esta anulado, lo cuento para el saldo disponible a facturar (notas de credito suman, los demas restan)
				if ($datos_factura['codigo'] != 'A') {
					$mult = $datos_factura['cod_tipo'] == 'NC' ? 1 : -1;
					$saldo_honorarios += $datos_factura['subtotal_sin_descuento'] * $mult;
					$saldo_gastos_con_impuestos += $datos_factura['subtotal_gastos'] * $mult;
					$saldo_gastos_sin_impuestos += $datos_factura['subtotal_gastos_sin_impuesto'] * $mult;
				}

				$Factura = new Factura($sesion);
				$Factura->Load($datos_factura['id_factura']);

				$moneda_factura = new Moneda($sesion);
				$moneda_factura->Load($datos_factura['id_moneda']);

				CobroHtml::setMoneda($moneda_factura);

				echo CobroHtml::cajafacturasFilaFactura($Factura, $documento_cobro, $fila++, $datos_factura);
			}

			//agregar docs, defaulteando segun conf

			$query_contrato_docs_legales = "SELECT id_tipo_documento_legal, honorarios, gastos_con_impuestos, gastos_sin_impuestos
                                    FROM contrato_documento_legal
                                    WHERE id_contrato = " . $contrato->fields['id_contrato'];

			$contrato_docs_legales = mysql_query($query_contrato_docs_legales, $sesion->dbh) or Utiles::errorSQL($query_contrato_docs_legales, __FILE__, __LINE__, $sesion->dbh);
			$nro_docs_legales = mysql_num_rows($contrato_docs_legales);

			if (!$nro_docs_legales) {
				$query_contrato_docs_legales = "SELECT id_tipo_documento_legal, honorarios, gastos_con_impuestos, gastos_sin_impuestos
                                        FROM contrato_documento_legal
                                        WHERE id_contrato IS NULL";

				$contrato_docs_legales = mysql_query($query_contrato_docs_legales, $sesion->dbh) or Utiles::errorSQL($query_contrato_docs_legales, __FILE__, __LINE__, $sesion->dbh);
			}

			if (($saldo_honorarios) < 0.0001) {
				$saldo_honorarios = 0;
			}
			if (($saldo_gastos_con_impuestos) < 0.0001) {
				$saldo_gastos_con_impuestos = 0;
			}
			if (($saldo_gastos_sin_impuestos) < 0.0001) {
				$saldo_gastos_sin_impuestos = 0;
			}

			$saldo_disponible_honorarios = $saldo_honorarios;
			$saldo_disponible_gastos_con_impuestos = $saldo_gastos_con_impuestos;
			$saldo_disponible_gastos_sin_impuestos = $saldo_gastos_sin_impuestos;

			$boton_pagar = '<button type="button" onclick="AgregarPagoFactura()" >' . __('Pagar') . '</button>';

			$idx = 0;
			while (list($agregar_tipo, $agregar_honorarios, $agregar_gastos_con_impuestos, $agregar_gastos_sin_impuestos) = $f = mysql_fetch_array($contrato_docs_legales)) {
				if ($agregar_honorarios && $saldo_honorarios ||
						$agregar_gastos_con_impuestos && $saldo_gastos_con_impuestos ||
						$agregar_gastos_sin_impuestos && $saldo_gastos_sin_impuestos) {

					$idx++;
					$honorarios_doc = 0;

					if ($agregar_honorarios) {
						$honorarios_doc = number_format($saldo_honorarios, $moneda_documento->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
						$saldo_honorarios = 0;
					}

					$gastos_con_impuestos_doc = 0;
					if ($agregar_gastos_con_impuestos) {
						$gastos_con_impuestos_doc = number_format($saldo_gastos_con_impuestos, $moneda_documento->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
						$saldo_gastos_con_impuestos = 0;
					}
					$gastos_sin_impuestos_doc = 0;
					if ($agregar_gastos_sin_impuestos) {
						$gastos_sin_impuestos_doc = number_format($saldo_gastos_sin_impuestos, $moneda_documento->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
						$saldo_gastos_sin_impuestos = 0;
					}
					?>
								<tr>
									<td colspan="3">
				<?php echo Html::SelectQuery($sesion, "SELECT id_documento_legal, glosa FROM prm_documento_legal", 'tipo_documento_legal_' . $idx, $agregar_tipo, '', '', 100); ?>
									</td>
									<td nowrap>
				<?php echo $moneda_documento->fields['simbolo'] ?>&nbsp;<input  class="mini_input"  type="text" id="honorarios_<?php echo $idx ?>" value="<?php echo $honorarios_doc ?>" size="8" onkeydown="MontoValido(this.id);"/>
									</td>
									<td nowrap>
				<?php echo $moneda_documento->fields['simbolo'] ?>&nbsp;<input  class="mini_input"  type="text" id="gastos_con_impuestos_<?php echo $idx ?>" value="<?php echo $gastos_con_impuestos_doc ?>" size="8" onkeydown="MontoValido(this.id);"/>
									</td>
									<td nowrap>
				<?php echo $moneda_documento->fields['simbolo'] ?>&nbsp;<input type="text" class="mini_input" id="gastos_sin_impuestos_<?php echo $idx ?>" value="<?php echo $gastos_sin_impuestos_doc ?>" size="8" onkeydown="MontoValido(this.id);"/>
									</td>
									<td align="center">
										<button type="button" onclick="AgregarFactura(<?php echo $idx ?>)" ><?php echo __('Emitir'); ?></button>
									</td>
									<td/>
									<td/>
									<td/>
									<td/>
									<td align="center">
				<?php
				echo $boton_pagar;
				$boton_pagar = '';
				?>
									</td>
								</tr>

				<?php
			}
		}

		//si quedaron saldos o no se genero nada default, tiro uno mas
		if (!$idx || $saldo_honorarios || $saldo_gastos_con_impuestos || $saldo_gastos_sin_impuestos) {
			?>
						<tr>
							<td colspan="3">
			<?php echo Html::SelectQuery($sesion, "SELECT id_documento_legal, glosa FROM prm_documento_legal", 'tipo_documento_legal_0', '', '', '', 100); ?>
							</td>
							<td nowrap>
			<?php echo $moneda_documento->fields['simbolo'] ?>&nbsp;<input type="text" class="mini_input" id="honorarios_0" value="<?php echo number_format($saldo_honorarios, $moneda_documento->fields['cifras_decimales'], $idioma->fields['separador_decimales'], '') ?>" size="8" onkeydown="MontoValido(this.id);"/>
							</td>
							<td nowrap>
			<?php echo $moneda_documento->fields['simbolo'] ?>&nbsp;<input type="text" class="mini_input"  id="gastos_con_impuestos_0" value="<?php echo number_format($saldo_gastos_con_impuestos, $moneda_documento->fields['cifras_decimales'], $idioma->fields['separador_decimales'], '') ?>" size="8" onkeydown="MontoValido(this.id);"/>
							</td>
							<td nowrap>
			<?php echo $moneda_documento->fields['simbolo'] ?>&nbsp;<input type="text" id="gastos_sin_impuestos_0" class="mini_input"  value="<?php echo number_format($saldo_gastos_sin_impuestos, $moneda_documento->fields['cifras_decimales'], $idioma->fields['separador_decimales'], '') ?>" size="8" onkeydown="MontoValido(this.id);"/>
							</td>
							<td align="center">
								<button type="button"  onclick="AgregarFactura(0)" ><?php echo __('Emitir') ?></button>
								<input type="hidden" id="honorarios_disponibles" value="<?php echo number_format($saldo_disponible_honorarios, $moneda_documento->fields['cifras_decimales'], $idioma->fields['separador_decimales'], ''); ?>"/>
								<input type="hidden" id="trabajos_disponibles" value="<?php echo number_format($saldo_disponible_trabajos, $moneda_documento->fields['cifras_decimales'], $idioma->fields['separador_decimales'], ''); ?>"/>
								<input type="hidden" id="tramites_disponibles" value="<?php echo number_format($saldo_disponible_tramites, $moneda_documento->fields['cifras_decimales'], $idioma->fields['separador_decimales'], ''); ?>"/>
								<input type="hidden" id="gastos_con_iva_disponibles" value="<?php echo $saldo_disponible_gastos_con_impuestos ?>"/>
								<input type="hidden" id="gastos_sin_iva_disponibles" value="<?php echo $saldo_disponible_gastos_sin_impuestos ?>"/>
							</td>
							<td/>
							<td/>
							<td/>

							<td align="center">
			<?php
			echo $boton_pagar;
			$boton_pagar = '';
			?>
							</td><td/>
						</tr>
			<?php
		}
	}
}
