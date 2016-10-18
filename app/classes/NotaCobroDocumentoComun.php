<?php

class NotaCobroDocumentoComun extends NotaCobroConfig {

	function GenerarDocumentoComun($parser, $theTag = 'INFORME', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, & $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto) {
		// Reune los anchors que no varían entre ambas modalidades de cálculo
		global $contrato;
		global $cobro_moneda;
		//global $moneda_total;
		global $masi;

		$moneda_total = new Objeto($this->sesion, '', '', 'prm_moneda', 'id_moneda');
		$moneda_total->Load($this->fields['opc_moneda_total'] > 0 ? $this->fields['opc_moneda_total'] : 1);

		if (!isset($parser->tags[$theTag])) {
			return;
		}

		$this->FillTemplateData($idioma, $moneda);

		$html = $this->RenderTemplate($parser->tags[$theTag]);

		switch ($theTag) {

			case 'ENDOSO': //GenerarDocumentoComun
				global $x_resultados;

				$subtotalgastos = ($totales['valor_total']) + ($totales['total_egreso']);
				$monto_total_neto = $monto_gastos_bruto_por_asunto + $subtotalgastos;

				$query = "	SELECT b.nombre, cb.numero, cb.cod_swift, cb.CCI
								FROM cuenta_banco cb
								LEFT JOIN prm_banco b ON b.id_banco = cb.id_banco
								WHERE cb.id_cuenta = '" . $contrato->fields['id_cuenta'] . "'";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($glosa_banco, $numero_cuenta, $codigo_swift, $codigo_cci) = mysql_fetch_array($resp);
				$html = str_replace('%numero_cuenta_contrato%', $numero_cuenta, $html);
				$html = str_replace('%glosa_banco_contrato%', $glosa_banco, $html);
				$html = str_replace('%codigo_swift%', $codigo_swift, $html);
				$html = str_replace('%codigo_cci%', $codigo_cci, $html);

				$html = str_replace('%valor_total_sin_impuesto%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($subtotalgastos, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_impuesto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($x_resultados['impuesto'][$this->fields['opc_moneda_total']], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_total_con_impuesto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($x_resultados['monto_total_cobro'][$this->fields['opc_moneda_total']], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				$html = str_replace('%tipo_gbp_segun_moneda%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['glosa_moneda_plural'], $html);
				$html = str_replace('%texto_instrucciones%', __('texto_instrucciones'), $html);

				if ($lang == 'es') {

					$html = str_replace('%total_sin_impuesto%', __('TOTAL SIN IMPUESTOS'), $html);
					$html = str_replace('%impuesto%', __('I.V.A. (10%)'), $html);
					$html = str_replace('%total_factura%', __('TOTAL FACTURA'), $html);
					$html = str_replace('%instrucciones_deposito%', __('INSTRUCCIONES DE TRANSFERENCIA BANCARIA A VOUGA & OLMEDO ABOGADOS'), $html);
					$html = str_replace('%solicitud%', __('FAVOR INCLUIR NOMBRE DE LA EMPRESA Y NÚMERO DE FACTURA.'), $html);
					##Textos Cuesta Campos##
					$html = str_replace('%pago_via%', __('Pago vía transferencia a la siguiente cuenta:'), $html);
					$html = str_replace('%solicitud_cheques%', __('textoSolicitudCheque'), $html);
					$html = str_replace('%caso_dudas%', __('En caso de dudas o comentarios al respecto no dude en contactarnos.'), $html);
					$html = str_replace('%atentamente%', __('Atentamente,'), $html);
					$html = str_replace('%sucursal%', __('Sucursal'), $html);
					$html = str_replace('%cuenta%', __('Cuenta'), $html);
					$html = str_replace('%direccion%', __('Direción'), $html);
					$html = str_replace('%banco%', __('Banco'), $html);
					$html = str_replace('%beneficiario%', __('Beneficiario'), $html);
				} else {

					$html = str_replace('%total_sin_impuesto%', __('TOTAL BEFORE TAXES'), $html);
					$html = str_replace('%impuesto%', __('V.A.T. (10%)'), $html);
					$html = str_replace('%total_factura%', __('TOTAL INVOICE'), $html);
					$html = str_replace('%instrucciones_deposito%', __('INSTRUCTIONS FOR PAYMENTS TO VOUGA & OLMEDO ABOGADOS:<br>ELECTRONIC TRANSFER VIA SWIFT MT103 MESSAGE:'), $html);
					$html = str_replace('%solicitud%', __('CORPORATE NAME AND INVOICE # MUST BE INCLUDED.'), $html);

					##Textos Cuesta Campos##
					$html = str_replace('%pago_via%', __(' Payment by wire transfer to the following account:'), $html);
					$html = str_replace('%solicitud_cheques%', __('textoSolicitudCheque'), $html);
					$html = str_replace('%caso_dudas%', __('Please feel free to contact us should you have any questions or comments on the above.'), $html);
					$html = str_replace('%atentamente%', __('Very truly yours'), $html);
					$html = str_replace('%sucursal%', __('Branch'), $html);
					$html = str_replace('%cuenta%', __('Account'), $html);
					$html = str_replace('%direccion%', __('Address'), $html);
					$html = str_replace('%banco%', __('Bank'), $html);
					$html = str_replace('%beneficiario%', __('Bnf'), $html);
				}

				$html = str_replace('%tipo_gbp_segun_moneda%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['glosa_moneda_plural'], $html);

				if ($this->fields['id_carta'] == 3) {
					$html = str_replace('%nota_disclaimer%', __('nota_disclaimer'), $html);
				} else {
					$html = str_replace('%nota_disclaimer%', ' ', $html);
				}


				break;

			case 'DESGLOSE_POR_ASUNTO_DETALLE': //GenerarDocumentoComun
				global $subtotal_hh, $subtotal_gasto, $subtotal_tramite, $impuesto_hh, $impuesto_gasto, $impuesto_tramite, $simbolo, $cifras_decimales;

				$query_desglose_asuntos = "SELECT    pm.cifras_decimales, pm.simbolo, @rownum:=@rownum+1 as rownum, ca.id_cobro, ca.codigo_asunto,a.glosa_asunto
							,if(@rownum=kant,@sumat1:=(1.0000-@sumat1), round(ifnull(trabajos.trabajos_thh/monto_thh,0),8)) pthh
							,@sumat1:=@sumat1+round(ifnull(trabajos.trabajos_thh/monto_thh,0),8) pthhac
							,if(@rownum=kant,@sumat2:=(1.0000-@sumat2), round(ifnull(trabajos.trabajos_thh_estandar/monto_thh_estandar,0),4)) pthhe
							,@sumat2:=@sumat2+round(ifnull(trabajos.trabajos_thh_estandar/monto_thh_estandar,0),8) pthheac
							,if(@rownum=kant,@sumag:=(1.0000-@sumag), round(ifnull(gastos.gastos/subtotal_gastos,0),8))  pg
							,@sumag:=@sumag+round(ifnull(gastos.gastos/subtotal_gastos,0),8) pgac
							,if(@rownum=kant,@sumat3:=(1.0000-@sumat3), round(ifnull(tramites.tramites/monto_tramites,0),8))  pt
							,@sumat3:=@sumat3+round(ifnull(tramites.tramites/monto_tramites,0),8) ptac
							,c.monto_trabajos
							,c.monto_thh
							,c.monto_thh_estandar
							,c.subtotal_gastos
							,c.monto_tramites
							,c.impuesto
							,c.impuesto_gastos
							, (c.monto_tramites * c.porcentaje_impuesto / 100) as impuesto_tramites
							,kant.kant

							FROM cobro_asunto ca
							join cobro c on (c.id_cobro = ca.id_cobro)
							join asunto a on (a.codigo_asunto = ca.codigo_asunto)
							join (select id_cobro, count(codigo_asunto) kant from cobro_asunto group by id_cobro) kant on kant.id_cobro=c.id_cobro
							join (select @rownum:=0, @sumat1:=0, @sumat2:=0, @sumag:=0, @sumat3:=0) fff
							join prm_moneda pm on pm.id_moneda=c.id_moneda
						join prm_moneda doc_moneda on doc_moneda .id_moneda=c.opc_moneda_total

							left join (SELECT id_cobro, codigo_asunto, SUM( TIME_TO_SEC( duracion_cobrada ) /3600 * tarifa_hh ) AS trabajos_thh, SUM( TIME_TO_SEC( duracion_cobrada ) /3600 * tarifa_hh_estandar ) AS trabajos_thh_estandar
							FROM trabajo  WHERE trabajo.id_tramite is null
							GROUP BY codigo_asunto,id_cobro) trabajos on trabajos.id_cobro=c.id_cobro and trabajos.codigo_asunto=ca.codigo_asunto

							left join (select id_cobro, codigo_asunto, sum(ifnull(egreso,0)-ifnull(ingreso,0)) gastos
							from cta_corriente where cobrable=1
							group by id_cobro, codigo_asunto) gastos on gastos.id_cobro=c.id_cobro and gastos.codigo_asunto=ca.codigo_asunto

							left join (SELECT id_cobro, codigo_asunto, SUM( IFNULL(tarifa_tramite,0)) AS tramites
							FROM tramite
							GROUP BY codigo_asunto,id_cobro) tramites on tramites.id_cobro=c.id_cobro and tramites.codigo_asunto=ca.codigo_asunto

							WHERE ca.id_cobro=" . $this->fields['id_cobro'];

				$rest_desglose_asuntos = mysql_query($query_desglose_asuntos, $this->sesion->dbh) or Utiles::errorSQL($query_desglose_asuntos, __FILE__, __LINE__, $this->sesion->dbh);
				$row_tmpl = $html;

				$html = '';
				while ($rowdesglose = mysql_fetch_array($rest_desglose_asuntos)) {
					list($subtotal_hh, $subtotal_gasto, $subtotal_tramite, $impuesto_hh, $impuesto_gasto, $impuesto_tramite, $simbolo, $cifras_decimales) = array($rowdesglose['monto_trabajos'], $rowdesglose['subtotal_gastos'], $rowdesglose['monto_tramites'], $rowdesglose['impuesto'], $rowdesglose['impuesto_gastos'], $rowdesglose['impuesto_tramites'], $rowdesglose['simbolo'], $rowdesglose['cifras_decimales']);
					$row = $row_tmpl;

					$row = str_replace('%codigo_asunto%', $rowdesglose['codigo_asunto'], $row);
					$row = str_replace('%glosa_asunto%', $rowdesglose['glosa_asunto'], $row);
					$row = str_replace('%simbolo%', $simbolo, $row);
					$row = str_replace('%honorarios_asunto%', number_format(round($rowdesglose['monto_trabajos'] * $rowdesglose['pthh'], $cifras_decimales), 2), $row);
					$row = str_replace('%gastos_asunto%', number_format(round($rowdesglose['subtotal_gastos'] * $rowdesglose['pg'], $cifras_decimales), 2), $row);
					$row = str_replace('%tramites_asunto%', number_format(round($rowdesglose['monto_tramites'] * $rowdesglose['pt'], $cifras_decimales), 2), $row);

					$html .= $row;
				}


				break;
			//FFF Esto se hizo para Aguilar Castillo Love
			case 'DESGLOSE_POR_ASUNTO_TOTALES': //GenerarDocumentoComun
				global $subtotal_hh, $subtotal_gasto, $subtotal_tramite, $impuesto_hh, $impuesto_gasto, $impuesto_tramite, $simbolo, $cifras_decimales;

				$html = str_replace('%simbolo%', $simbolo, $html);
				$html = str_replace('%desglose_subtotal_hh%', number_format(round($subtotal_hh, $cifras_decimales), 2), $html);
				$html = str_replace('%desglose_subtotal_gasto%', number_format(round($subtotal_gasto, $cifras_decimales), 2), $html);
				$html = str_replace('%desglose_subtotal_tramite%', number_format(round($subtotal_tramite, $cifras_decimales), 2), $html);
				$html = str_replace('%desglose_impuesto_hh%', number_format(round($impuesto_hh, $cifras_decimales), 2), $html);
				$html = str_replace('%desglose_impuesto_gasto%', number_format(round($impuesto_gasto, $cifras_decimales), 2), $html);
				$html = str_replace('%desglose_impuesto_tramite%', number_format(round($impuesto_tramite, $cifras_decimales), 2), $html);

				$html = str_replace('%desglose_grantotal%', number_format(round(floatval($subtotal_hh) + floatval($subtotal_gasto) + floatval($subtotal_tramite) + floatval($impuesto_hh) + floatval($impuesto_gasto) + floatval($impuesto_tramites), $cifras_decimales), 2), $html);

				$html = str_replace('%subtotales%', __('Subtotal'), $html);
				$html = str_replace('%impuestos%', __('Impuesto'), $html);
				$html = str_replace('%total_deuda%', __('Total Adeudado'), $html);

				break;



			case 'RESTAR_RETAINER': //GenerarDocumentoComun
				if ($columna_hrs_retainer || $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$html = str_replace('%retainer%', __('Retainer'), $html);
				} else {
					$html = str_replace('%retainer%', '', $html);
				}
				if ($columna_hrs_retainer || $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$html = str_replace('%valor_retainer%', '(' . $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['simbolo'] . $this->espacio . number_format($this->fields['monto_contrato'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ')', $html);
				} else {
					$html = str_replace('%valor_retainer%', '', $html);
				}
				break;

			case 'DETALLE_COBRO_RETAINER': //GenerarDocumentoComun
				$monto_contrato_moneda_tarifa = number_format($this->fields['monto_contrato'] * $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$monto_cobro_menos_monto_contrato_moneda_tarifa = number_format($this->fields['monto'] - ($this->fields['monto_contrato'] * $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');

				$html = str_replace('%horas_retainer%', 'Horas retainer', $html);
				$html = str_replace('%valor_horas_retainer%', Utiles::horaDecimal2HoraMinuto($this->fields['retainer_horas']), $html);
				$html = str_replace('%horas_adicionales%', 'Horas adicionales', $html);
				$html = str_replace('%valor_horas_adicionales%', Utiles::horaDecimal2HoraMinuto(($this->fields['total_minutos'] / 60) - $this->fields['retainer_horas']), $html);
				$html = str_replace('%honorarios_retainer%', 'Honorarios retainer', $html);
				$html = str_replace('%valor_honorarios_retainer%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . number_format($monto_contrato_moneda_tarifa, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%honorarios_adicionales%', 'Honorarios adicionales', $html);
				$html = str_replace('%valor_honorarios_adicionales%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . number_format($monto_cobro_menos_monto_contrato_moneda_tarifa, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				break;

			case 'DETALLE_TARIFA_ADICIONAL': //GenerarDocumentoComun
				$tarifas_adicionales = $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . " ";

				$query = "SELECT DISTINCT tarifa_hh FROM trabajo WHERE id_cobro = '" . $this->fields['id_cobro'] . "' ORDER BY tarifa_hh DESC";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

				$i = 0;
				while (list($tarifa_hh) = mysql_fetch_array($resp)) {
					if ($i == 0)
						$tarifas_adicionales .= "$tarifa_hh/hr";
					else
						$tarifas_adicionales .= ", $tarifa_hh/hr";
					$i++;
				}

				$html = str_replace('%tarifa_adicional%', __('Tarifa adicional por hora'), $html);
				$html = str_replace('%valores_tarifa_adicionales%', $tarifas_adicionales, $html);
				break;

			case 'FACTURA_NUMERO': //GenerarDocumentoComun
				$html = str_replace('%factura_nro%', __('Factura') . ' ' . __('N°'), $html);
				break;

			case 'NUMERO_FACTURA': //GenerarDocumentoComun
				$html = str_replace('%nro_factura%', $this->fields['documento'], $html);
				break;

			case 'DETALLE_HONORARIOS': //GenerarDocumentoComun
				$horas_cobrables = floor(($this->fields['total_minutos']) / 60);
				$minutos_cobrables = sprintf("%02d", $this->fields['total_minutos'] % 60);
				$html = str_replace('%horas%', __('Total Horas'), $html);
				$html = str_replace('%valor_horas%', $horas_cobrables . ':' . $minutos_cobrables, $html);
				$html = str_replace('%honorarios%', __('Honorarios'), $html);
				if (Conf::GetConf($this->sesion, 'UsarImpuestoSeparado') && $contrato->fields['usa_impuesto_separado']) {
					$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto'] - $this->fields['impuesto'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				} else {
					$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}

				if ($this->fields['opc_ver_descuento']) {
					$html = str_replace('%DETALLE_COBRO_DESCUENTO%', $this->GenerarDocumentoComun($parser, 'DETALLE_COBRO_DESCUENTO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					$html = str_replace('%DETALLE_COBRO_DESCUENTO_NUEVO%', $this->GenerarDocumentoComun($parser, 'DETALLE_COBRO_DESCUENTO_NUEVO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%DETALLE_COBRO_DESCUENTO%', '', $html);
					$html = str_replace('%DETALLE_COBRO_DESCUENTO_NUEVO%', '', $html);
				}

				$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL%', $this->GenerarDocumentoComun($parser, 'DETALLE_COBRO_MONEDA_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				break;

			case 'DETALLE_GASTOS': //GenerarDocumentoComun
				$html = str_replace('%gastos%', __('Gastos'), $html);
				$query = "SELECT SQL_CALC_FOUND_ROWS *
								FROM cta_corriente
								WHERE id_cobro='" . $this->fields['id_cobro'] . "' AND (egreso > 0 OR ingreso > 0) AND cta_corriente.incluir_en_cobro = 'SI'
								ORDER BY fecha ASC";
				$lista_gastos = new ListaGastos($this->sesion, '', $query);
				$total_gastos_moneda = 0;
				for ($i = 0; $i < $lista_gastos->num; $i++) {
					$gasto = $lista_gastos->Get($i);

					if ($gasto->fields['egreso'] > 0)
						$saldo = $gasto->fields['monto_cobrable'];
					elseif ($gasto->fields['ingreso'] > 0)
						$saldo = -$gasto->fields['monto_cobrable'];

					$monto_gasto = $saldo;
					$saldo_moneda_total = $saldo * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']);
					$total_gastos_moneda += $saldo_moneda_total;
				}

				if (Conf::GetConf($this->sesion, 'CalculacionCyC')) {
					$total_gastos_moneda = round($total_gastos_moneda, $moneda_total->fields['cifras_decimales']);
				}

				$impuestos_total_gastos_moneda = round($total_gastos_moneda * ($this->fields['porcentaje_impuesto_gastos'] / 100), $moneda_total->fields['cifras_decimales']);
				$html = str_replace('%valor_gastos%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($total_gastos_moneda, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			case 'DETALLE_TRAMITES': //GenerarDocumentoComun
				$html = str_replace('%tramites%', __('Trámites'), $html);
				$html = str_replace('%tramites_castropal%', __('Otros Servicios'), $html);
				$aproximacion_tramites = number_format($this->fields['monto_tramites'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$valor_tramites = $aproximacion_tramites * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'];
				$html = str_replace('%valor_tramites%', $moneda_total->fields['simbolo'] . number_format($valor_tramites, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				break;

			case 'DETALLE_COBRO_MONEDA_TOTAL': //GenerarDocumentoComun

				global $x_resultados;

				if ($this->fields['opc_moneda_total'] == $this->fields['id_moneda']) {
					return '';
				}

				#valor en moneda previa selección para impresión
				if ($this->fields['tipo_cambio_moneda_base'] <= 0) {
					$tipo_cambio_cobro_moneda_base = 1;
				} else {
					$tipo_cambio_cobro_moneda_base = $this->fields['tipo_cambio_moneda_base'];
				}

				if (Conf::GetConf($this->sesion, 'CalculacionCyC')) {
					$aproximacion_monto = number_format($this->fields['monto_subtotal'] - $this->fields['descuento'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
					$total_en_moneda = $aproximacion_monto * ($this->fields['tipo_cambio_moneda'] / $tipo_cambio_cobro_moneda_base) / ($tipo_cambio_moneda_total / $tipo_cambio_cobro_moneda_base);
				} else {
					$aproximacion_monto = number_format($this->fields['monto'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
					$total_en_moneda = $aproximacion_monto * ($this->fields['tipo_cambio_moneda'] / $tipo_cambio_cobro_moneda_base) / ($tipo_cambio_moneda_total / $tipo_cambio_cobro_moneda_base);
				}
				//Caso retainer menor de un valor y distinta tarifa (diferencia por decimales)
				if ((($this->fields['total_minutos'] / 60) < $this->fields['retainer_horas']) && ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') && $this->fields['id_moneda'] != $this->fields['id_moneda_monto']) {
					$total_en_moneda = $this->fields['monto'] * ($this->fields['tipo_cambio_moneda'] / $tipo_cambio_cobro_moneda_base) / ($tipo_cambio_moneda_total / $tipo_cambio_cobro_moneda_base);
				}

				//Caso flat fee
				if ($this->fields['forma_cobro'] == 'FLAT FEE' && $this->fields['id_moneda'] != $this->fields['id_moneda_monto'] && $this->fields['id_moneda_monto'] == $this->fields['opc_moneda_total'] && empty($this->fields['descuento'])) {
					$total_en_moneda = $this->fields['monto_contrato'];
				}

				$valor_trabajos_demo_moneda_total = $x_resultados['monto_trabajo_con_descuento'][$this->fields['opc_moneda_total']];
				$html = str_replace('%monedabase%', __('Equivalente a'), $html);
				$html = str_replace('%total_pagar%', __('Total a Pagar'), $html);

				if ((Conf::GetConf($this->sesion, 'UsarImpuestoSeparado')) && $contrato->fields['usa_impuesto_separado'] && (!Conf::GetConf($this->sesion, 'CalculacionCyC'))) {
					$total_en_moneda -= $this->fields['impuesto'] * ($this->fields['tipo_cambio_moneda'] / $tipo_cambio_cobro_moneda_base) / ($tipo_cambio_moneda_total / $tipo_cambio_cobro_moneda_base);
				}

				$html = str_replace('%valor_honorarios_monedabase_demo%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($valor_trabajos_demo_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_honorarios_monedabase%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format(floor($total_en_moneda), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_honorarios_monedabase_mb%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($x_resultados['monto'][$this->fields['opc_moneda_total']], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				break;

			case 'DETALLE_COBRO_DESCUENTO_NUEVO': //GenerarDocumentoComun
				global $x_resultados;

				$simbolo_moneda = $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'];
				$cifras_decimales = $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'];
				$separador_decimales = $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'];

				if ($this->fields['monto_trabajos'] > 0 && $this->fields['monto_tramites'] > 0) {
					$html = str_replace('%trabajos%', __('Total Trabajos'), $html);
					$html = str_replace('%valor_trabajos%', $simbolo_moneda . $this->espacio . number_format($x_resultados['monto_trabajos'][$this->fields['id_moneda']], $cifras_decimales, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

					$html = str_replace('%tramites%', __('Total Trámites'), $html);
					$html = str_replace('%valor_tramites%', $simbolo_moneda . $this->espacio . number_format($x_resultados['monto_tramites'][$this->fields['id_moneda']], $cifras_decimales, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}

				$html = str_replace('%subtotal_honorarios%', __('Subtotal'), $html);
				$html = str_replace('%valor_subtotal_honorarios%', $simbolo_moneda . $this->espacio . number_format($x_resultados['monto_subtotal'][$this->fields['id_moneda']], $cifras_decimales, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				$html = str_replace('%descuento%', __('Descuento'), $html);
				if ($this->fields['porcentaje_descuento'] > 0) {
					$html = str_replace('%porcentaje_descuento%', "({$this->fields['porcentaje_descuento']}%)", $html);
				} else {
					$html = str_replace('%porcentaje_descuento%', '', $html);
				}
				$html = str_replace('%valor_descuento%', $simbolo_moneda . $this->espacio . number_format($x_resultados['descuento'][$this->fields['id_moneda']], $cifras_decimales, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				$html = str_replace('%honorarios%', __('Honorarios'), $html);
				$html = str_replace('%valor_honorarios%', $simbolo_moneda . $this->espacio . number_format($x_resultados['monto_honorarios'][$this->fields['id_moneda']], $cifras_decimales, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);


				break;

			case 'DETALLE_COBRO_DESCUENTO': //GenerarDocumentoComun

				if ($this->fields['descuento'] == 0) {
					if (Conf::GetConf($this->sesion, 'FormatoNotaCobroMTA')) {
						$html = str_replace('%honorarios%', __('Subtotal Honorarios'), $html);
						$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto_subtotal'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
						$html = str_replace('%valor_descuento%', '', $html);
						$html = str_replace('%porcentaje_descuento%', '', $html);
						$html = str_replace('%descuento%', '', $html);
						break;
					} else {
						return '';
					}
				}

				$aproximacion_honorarios = number_format($this->fields['monto_subtotal'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$aproximacion_descuento = number_format($this->fields['descuento'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$valor_trabajos_demo = number_format($this->fields['monto_trabajos'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idoma->fields['separador_miles']);
				$valor_descuento_demo = number_format($this->fields['descuento'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
				$valor_honorarios = number_format($aproximacion_honorarios * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
				$valor_descuento = number_format($aproximacion_descuento * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);

				$html = str_replace('%valor_honorarios_demo%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . $valor_trabajos_demo, $html);
				$html = str_replace('%valor_descuento_demo%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . $valor_descuento_demo, $html);

				if (Conf::GetConf($this->sesion, 'CalculacionCyC')) {
					$html = str_replace('%valor_honorarios%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . $valor_honorarios, $html);
					$html = str_replace('%valor_descuento%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . $valor_descuento, $html);
				}

				$html = str_replace('%honorarios%', __('Subtotal Honorarios'), $html);
				$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto_subtotal'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%descuento%', __('Descuento'), $html);

				if ($this->fields['monto_trabajos'] > 0) {
					$porcentaje_demo = ($this->fields['descuento'] * 100) / $this->fields['monto_trabajos'];
				}

				$html = str_replace('%porcentaje_descuento_demo%', ' (' . number_format($porcentaje_demo, 0) . '%)', $html);

				if ($this->fields['monto_subtotal'] > 0) {
					$porcentaje = ($this->fields['descuento'] * 100) / $this->fields['monto_subtotal'];
				}

				$html = str_replace('%porcentaje_descuento%', ' (' . number_format($porcentaje, 0) . '%)', $html);
				$html = str_replace('%valor_descuento%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['descuento'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%total_honorarios%', __('Total Honorarios'), $html);
				$html = str_replace('%valor_honorarios_con_descuento%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);


				break;

			case 'ASUNTOS': //GenerarDocumentoComun
				$row_tmpl = $html;
				$html = '';
				$cliente = "";
				global $profesionales;
				$profesionales = array();


				$queryasuntos = "SELECT asunto.codigo_asunto, asunto.codigo_cliente, cliente.glosa_cliente
									FROM trabajo
									LEFT JOIN asunto
									USING ( codigo_asunto )
									LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
									WHERE id_cobro ={$this->fields['id_cobro']}
									UNION
									SELECT asunto.codigo_asunto, asunto.codigo_cliente, cliente.glosa_cliente
									FROM cta_corriente
									LEFT JOIN asunto
									USING ( codigo_asunto )
									LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
									WHERE id_cobro ={$this->fields['id_cobro']}
									UNION
									SELECT asunto.codigo_asunto, asunto.codigo_cliente, cliente.glosa_cliente
									FROM tramite
									LEFT JOIN asunto
									USING ( codigo_asunto )
									LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
									WHERE id_cobro ={$this->fields['id_cobro']}";

				try {
					$arregloasuntos = $this->sesion->pdodbh->query($queryasuntos);
				} catch (PDOException $e) {
					Utiles::errorSQL($queryasuntos, "", "", NULL, "", $e);
					exit;
				}
				foreach ($arregloasuntos as $filaasunto) {
					$asunto = new Asunto($this->sesion);
					$asunto->LoadByCodigo($filaasunto['codigo_asunto']);

					unset($GLOBALS['profesionales']);
					$profesionales = array();

					unset($GLOBALS['resumen_profesionales']);
					$resumen_profesionales = array();

					unset($GLOBALS['totales']);
					$totales = array();
					$totales['tiempo'] = 0;
					$totales['tiempo_trabajado'] = 0;
					$totales['tiempo_trabajado_real'] = 0;
					$totales['tiempo_retainer'] = 0;
					$totales['tiempo_flatfee'] = 0;
					$totales['tiempo_descontado'] = 0;
					$totales['tiempo_descontado_real'] = 0;
					$totales['valor'] = 0;
					$categoria_duracion_horas = 0;
					$categoria_duracion_minutos = 0;
					$categoria_valor = 0;
					$total_trabajos_categoria = '';
					$encabezado_trabajos_categoria = '';

					$query = "SELECT count(*) FROM tramite
									WHERE id_cobro=" . $this->fields['id_cobro'] . "
										AND codigo_asunto='" . $asunto->fields['codigo_asunto'] . "'";
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					list($cont_tramites) = mysql_fetch_array($resp);

					$query = "SELECT count(*) FROM trabajo
									WHERE id_cobro=" . $this->fields['id_cobro'] . "
										AND codigo_asunto='" . $asunto->fields['codigo_asunto'] . "'
										AND id_tramite=0";
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					list($cont_trabajos) = mysql_fetch_array($resp);

					$query = "SELECT count(*) FROM cta_corriente
									 WHERE id_cobro=" . $this->fields['id_cobro'] . "
										AND codigo_asunto='" . $asunto->fields['codigo_asunto'] . "'";
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					list($cont_gastos) = mysql_fetch_array($resp);

					$row = $row_tmpl;

					if (count($this->asuntos) > 1) {
						$row = str_replace('%salto_pagina_varios_asuntos%', '&nbsp;<br clear=all style="mso-special-character:line-break; page-break-before:always" size="1" class="divisor">', $row);
						$row = str_replace('%salto_pagina_un_asunto%', '', $row);
						$row = str_replace('%asunto_extra%', __('Asunto'), $row);
						$row = str_replace('%glosa_asunto_sin_codigo_extra%', $asunto->fields['glosa_asunto'], $row);
					} else {
						$row = str_replace('%salto_pagina_varios_asuntos%', '', $row);
						$row = str_replace('%salto_pagina_un_asunto%', '&nbsp;<br clear=all style="mso-special-character:line-break; page-break-before:always" size="1" class="divisor">', $row);
						$row = str_replace('%asunto_extra%', '', $row);
						$row = str_replace('%glosa_asunto_sin_codigo_extra%', '', $row);
					}

					$row = str_replace('%asunto%', __('Asunto'), $row);

					if ($filaasunto['codigo_cliente'] != $cliente) {  //empiezo una nueva seccion de clientes
						$row = str_replace('%asuntos_cliente%', 'background:#EFEFEF;border-top:1px solid #999;height:20px;vertical-align:middle', $row);
						$row = str_replace('%etiqueta_cliente%', __('Asuntos') . '  del ' . __('Cliente'), $row);
						$row = str_replace('%codigo_cliente_cambio%', $filaasunto['codigo_cliente'], $row);
						$row = str_replace('%glosa_cliente%', $filaasunto['glosa_cliente'], $row);
						$cliente = $filaasunto['codigo_cliente'];
					} else {
						$row = str_replace('%asuntos_cliente%', 'height:0px;', $row);
						$row = str_replace('%etiqueta_cliente%', '', $row);
						$row = str_replace('%codigo_cliente_cambio%', '', $row);
						$row = str_replace('%glosa_cliente%', '', $row);
						$cliente = $filaasunto['codigo_cliente'];
					}

					if (Conf::GetConf($this->sesion, 'GlosaAsuntoSinCodigo')) {
						$row = str_replace('%glosa_asunto%', $asunto->fields['glosa_asunto'], $row);
						$row = str_replace('%glosa_asunto_secundario%', $asunto->fields['glosa_asunto'], $row);
					} else {
						$row = str_replace('%glosa_asunto%', $asunto->fields['codigo_asunto'] . " - " . $asunto->fields['glosa_asunto'], $row);
						$row = str_replace('%glosa_asunto_secundario%', $asunto->fields['codigo_asunto_secundario'] . " - " . $asunto->fields['glosa_asunto'], $row);
					}
					$row = str_replace('%codigo_cliente%', $filaasunto['codigo_cliente'], $row);
					$row = str_replace('%glosa_asunto_sin_codigo%', $asunto->fields['glosa_asunto'], $row);
					$row = str_replace('%glosa_asunto_codigo_area%', $asunto->fields['codigo_asunto'] . '-' . sprintf("%02d", ($asunto->fields['id_area_proyecto'] - 1)) . " - " . $asunto->fields['glosa_asunto'], $row);
					$row = str_replace('%valor_codigo_asunto%', $asunto->fields['codigo_asunto'], $row);
					$row = str_replace('%codigo_cliente_secundario%', empty($cliente->fields['codigo_cliente_secundario']) ? '' : __('Código Cliente'), $row);
					$row = str_replace('%valor_codigo_cliente_secundario%', empty($cliente->fields['codigo_cliente_secundario']) ? '' : empty($cliente->fields['codigo_cliente_secundario']), $row);
					$row = str_replace('%contacto%', empty($asunto->fields['contacto']) ? '' : __('Contacto'), $row);
					$row = str_replace('%valor_contacto%', empty($asunto->fields['contacto']) ? '' : $asunto->fields['contacto'], $row);

					$row = str_replace('%registro%', __('Registro de Tiempo'), $row);
					$row = str_replace('%telefono%', empty($asunto->fields['fono_contacto']) ? '' : __('Teléfono'), $row);
					$row = str_replace('%valor_telefono%', empty($asunto->fields['fono_contacto']) ? '' : $asunto->fields['fono_contacto'], $row);
					if ($cont_trabajos > 0) {
						if ($this->fields["opc_ver_detalles_por_hora"] == 1) {
							$row = str_replace('%espacio_trabajo%', '<br>', $row);
							$row = str_replace('%servicios%', __('Servicios prestados'), $row);
							$row = str_replace('%TRABAJOS_ENCABEZADO%', $this->GenerarDocumentoComun($parser, 'TRABAJOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
							$row = str_replace('%TRABAJOS_FILAS%', $this->GenerarDocumentoComun($parser, 'TRABAJOS_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
							$row = str_replace('%TRABAJOS_TOTAL%', $this->GenerarDocumentoComun($parser, 'TRABAJOS_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
						} else {
							$row = str_replace('%espacio_trabajo%', '', $row);
							$row = str_replace('%servicios%', '', $row);
							$row = str_replace('%TRABAJOS_ENCABEZADO%', '', $row);
							$row = str_replace('%TRABAJOS_FILAS%', '', $row);
							$row = str_replace('%TRABAJOS_TOTAL%', '', $row);
						}
						$row = str_replace('%DETALLE_PROFESIONAL%', $this->GenerarDocumentoComun($parser, 'DETALLE_PROFESIONAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
					} else {
						$row = str_replace('%espacio_trabajo%', '', $row);
						$row = str_replace('%DETALLE_PROFESIONAL%', '', $row);
						$row = str_replace('%servicios%', '', $row);
						$row = str_replace('%TRABAJOS_ENCABEZADO%', '', $row);
						$row = str_replace('%TRABAJOS_FILAS%', '', $row);
						$row = str_replace('%TRABAJOS_TOTAL%', '', $row);
					}

					$query_hitos = "SELECT count(*) from cobro_pendiente where hito=1 and id_cobro=" . $this->fields['id_cobro'];
					$resp_hitos = mysql_query($query_hitos, $this->sesion->dbh) or Utiles::errorSQL($query_hitos, __FILE__, __LINE__, $this->sesion->dbh);

					list($cont_hitos) = mysql_fetch_array($resp_hitos);
					$row = str_replace('%hitos%', '<br>' . __('Hitos') . '<br/><br/>', $row);
					if ($cont_hitos > 0) {
						global $total_hitos, $estehito, $cantidad_hitos, $moneda_hitos, $tipo_cambio_hitos;

						$row = str_replace('%HITOS_FILAS%', $this->GenerarDocumentoComun($parser, 'HITOS_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
						$row = str_replace('%HITOS_TOTAL%', $this->GenerarDocumentoComun($parser, 'HITOS_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
						$row = str_replace('%HITOS_ENCABEZADO%', $this->GenerarDocumentoComun($parser, 'HITOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
						$row = str_replace('%hitos%', '<br>' . __('Hitos') . '(' . $estehito . ' de ' . $total_hitos . ')<br/><br/>', $row);
					} else {
						$row = str_replace('%hitos%', '', $row);
						$row = str_replace('%HITOS_ENCABEZADO%', '', $row);
						$row = str_replace('%HITOS_FILAS%', '', $row);
						$row = str_replace('%HITOS_TOTAL%', '', $row);
					}

					if ($cont_tramites > 0) {
						$row = str_replace('%espacio_tramite%', '<br>', $row);
						$row = str_replace('%servicios_tramites%', __('Trámites'), $row);
						$row = str_replace('%titulo_seccion_tramites_castropal%', __('Otros Servicios'), $row);
						$row = str_replace('%servicios_tramites_castropal%', __('Otros Servicios Profesionales'), $row);
						$row = str_replace('%TRAMITES_ENCABEZADO%', $this->GenerarDocumentoComun($parser, 'TRAMITES_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
						$row = str_replace('%TRAMITES_FILAS%', $this->GenerarDocumentoComun($parser, 'TRAMITES_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
						$row = str_replace('%TRAMITES_TOTAL%', $this->GenerarDocumentoComun($parser, 'TRAMITES_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
					} else {
						$row = str_replace('%espacio_tramite%', '', $row);
						$row = str_replace('%servicios_tramites%', '', $row);
						$row = str_replace('%titulo_seccion_tramites_castropal%', '', $row);
						$row = str_replace('%servicios_tramites_castropal%', '', $row);
						$row = str_replace('%TRAMITES_ENCABEZADO%', '', $row);
						$row = str_replace('%TRAMITES_FILAS%', '', $row);
						$row = str_replace('%TRAMITES_TOTAL%', '', $row);
					}
					if (Conf::GetConf($this->sesion, 'ParafoGastosSoloSiHayGastos')) {
						if ($cont_gastos > 0)
							$row = str_replace('%GASTOS%', $this->GenerarDocumentoComun($parser, 'GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
						else
							$row = str_replace('%GASTOS%', '', $row);
					} else
						$row = str_replace('%GASTOS%', $this->GenerarDocumentoComun($parser, 'GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);

					#especial mb
					$row = str_replace('%codigo_asunto_mb%', __('Código M&B'), $row);

					if ($asunto->fields['trabajos_total_duracion'] > 0 || $asunto->fields['trabajos_total_duracion_trabajada'] > 0 || $cont_tramites > 0 || Conf::GetConf($this->sesion, 'MostrarAsuntosSinTrabajosGastosTramites')) {
						$html .= $row;
					}
				}
				$arregloasuntos->closeCursor();
				break;

			//FFF DESGLOSE DE HITOS
			case 'HITOS_ENCABEZADO': //GenerarDocumentoComun
				global $total_hitos, $estehito, $cantidad_hitos, $moneda_hitos, $tipo_cambio_hitos;
				$html = str_replace('%fecha%', __('Fecha'), $html);
				$html = str_replace('%descripcion%', __('Descripción'), $html);
				$html = str_replace('%valor%', __('Valor') . ' ' . $moneda_hitos, $html);

				break;

			case 'HITOS_FILAS': //GenerarDocumentoComun
				global $total_hitos, $estehito, $cantidad_hitos, $moneda_hitos, $tipo_cambio_hitos;
				$query_hitos = "select * from (select  (select count(*) total from cobro_pendiente cp2 where cp2.id_contrato=cp.id_contrato) total,  @a:=@a+1 as rowid, round(if(cbr.id_cobro=cp.id_cobro, @a,0),0) as thisid,  ifnull(cp.fecha_cobro,0) as fecha_cobro, cp.descripcion, cp.monto_estimado, pm.simbolo, pm.codigo, pm.tipo_cambio  FROM `cobro_pendiente` cp join  contrato c on (c.id_contrato = cp.id_contrato) join prm_moneda pm on (pm.id_moneda = cp.id_moneda) join cobro cbr on (cbr.id_contrato = cp.id_contrato)  join (select @a:=0) FFF
					where cp.hito=1 and cbr.id_cobro=" . $this->fields['id_cobro'] . ") hitos where hitos.thisid!=0 ";


				$resp_hitos = mysql_query($query_hitos, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				$row_tmpl = $html;
				$html = '';
				while ($hitos = mysql_fetch_array($resp_hitos)) {
					$row = $row_tmpl;
					$row = str_replace('%fecha%', ($hitos['fecha_cobro'] == 0 ? '' : date('d-m-Y', strtotime($hitos['fecha_cobro']))), $row);
					$row = str_replace('%descripcion%', $hitos['descripcion'], $row);
					$total_hitos = $total_hitos + $hitos['monto_estimado'];
					$moneda_hitos = $hitos['simbolo'];
					$estehito = $hitos['thisid'];
					$cantidad_hitos = $hitos['total'];
					$tipo_cambio_hitos = $hitos['tipo_cambio'];
					$row = str_replace('%valor_hitos%', $hitos['monto_estimado'] . ' ' . $moneda_hitos, $row);
					$html .= $row;
				}

				break;

			case 'HITOS_TOTAL': //GenerarDocumentoComun
				global $total_hitos, $estehito, $cantidad_hitos, $moneda_hitos, $tipo_cambio_hitos;

				$html = str_replace('%total%', __('Total'), $html);
				$html = str_replace('%total_hitos%', $total_hitos . ' ' . $moneda_hitos, $html);

				break;

			case 'HITOS_DETALLADO_ENCABEZADO': //GenerarDocumentoComun
				global $total_hitos, $total_real, $moneda_hitos, $duracion;
				$html = str_replace('%descripcion%', __('Descripción'), $html);
				$html = str_replace('%estado%', __('Estado'), $html);
				$html = str_replace('%fecha%', __('Fecha'), $html);
				$html = str_replace('%duracion_hitos%', __('Duración'), $html);
				$html = str_replace('%monto_hito%', __('Monto del Hito') . " ({$moneda_hitos})", $html);
				$html = str_replace('%monto_real%', __('Valor Real Actualizado') . " ({$moneda_hitos})", $html);

				break;

			case 'HITOS_DETALLADO_FILAS': //GenerarDocumentoComun
				global $total_hitos, $total_real, $moneda_hitos, $duracion;

				$hitos = $this->hitos;
				$row_tmpl = $html;
				$html = '';
				$total_hitos = 0;
				foreach ($hitos as $hito) {
					$row = $row_tmpl;
					$row = str_replace('%descripcion%', $hito['descripcion'], $row);
					$row = str_replace('%estado%', $hito['estado'], $row);
					$row = str_replace('%fecha%', $hito['fecha_hito'], $row);
					$duracion += $hito['total_minutos'];
					$total_minutos = str_pad(floor($hito['total_minutos'] / 60), 2, '0', STR_PAD_LEFT) . ':' . str_pad(($hito['total_minutos'] % 60), 2, '0', STR_PAD_LEFT);
					$row = str_replace('%duracion_hitos%', $total_minutos, $row);
					$total_hitos += $hito['monto_estimado'];
					$total_real += $hito['monto_thh'];
					$moneda_hitos = $hito['simbolo'];

					$tipo_cambio_hitos = $hito['tipo_cambio'];
					$row = str_replace('%monto_hito%', $moneda_hitos . ' ' . $hito['monto_estimado'], $row);
					$row = str_replace('%monto_real%', (empty($hito['monto_thh'])) ? '' : $moneda_hitos . ' ' . $hito['monto_thh'], $row);
					$html .= $row;
				}
				break;

			case 'HITOS_DETALLADO_TOTAL': //GenerarDocumentoComun
				global $total_hitos, $total_real, $moneda_hitos, $duracion;
				$html = str_replace('%total%', __('Total'), $html);
				$html = str_replace('%duracion_hitos%', str_pad(floor($duracion / 60), 2, '0', STR_PAD_LEFT) . ':' . str_pad(($duracion % 60), 2, '0', STR_PAD_LEFT), $html);
				$html = str_replace('%total_hitos%', $moneda_hitos . ' ' . $total_hitos, $html);
				$html = str_replace('%total_real%', $moneda_hitos . ' ' . $total_real, $html);

				break;

			case 'TRAMITES_ENCABEZADO': //GenerarDocumentoComun

				$html = str_replace('%tramites%', __('Trámites'), $html);
				$html = str_replace('%tramites_castropal%', __('Otros Servicios'), $html);
				$html = str_replace('%solicitante%', __('Solicitado Por'), $html);
				$html = str_replace('%ordenado_por%', $this->fields['opc_ver_solicitante'] ? __('Ordenado Por') : '', $html);
				$html = str_replace('%periodo%', (($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') and ( $this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '')) ? '' : __('Periodo'), $html);
				$html = str_replace('%valor_periodo_ini%', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? '' : Utiles::sql2fecha($this->fields['fecha_ini'], $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%valor_periodo_fin%', ($this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '') ? '' : __('hasta') . ' ' . Utiles::sql2fecha($this->fields['fecha_fin'], $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%cliente%', __('Cliente'), $html);
				$html = str_replace('%glosa_cliente%', $cliente->fields['glosa_cliente'], $html);
				$html = str_replace('%asunto%', __('Asunto'), $html);
				$html = str_replace('%glosa_asunto%', $asunto->fields['glosa_asunto'], $html);
				$html = str_replace('%fecha%', __('Fecha'), $html);
				$html = str_replace('%descripcion%', __('Descripción'), $html);
				$html = str_replace('%servicios_prestados%', __('Servicios Prestados'), $html);
				$html = str_replace('%servicios_tramites%', __('Trámites'), $html);
				$html = str_replace('%servicios_tramites_castropal%', 'Otros Servicios Profesionales', $html);
				$html = str_replace('%detalle_trabajo%', __('Detalle del Trámite Realizado'), $html);
				$html = str_replace('%profesional%', __('Profesional'), $html);
				$html = str_replace('%abogado%', __('Abogado'), $html);
				$html = str_replace('%horas%', __('Horas'), $html);
				$html = str_replace('%cobrable%', __('<td align="center" width="80">Cobrable</td>'), $html);

				if (Conf::GetConf($this->sesion, 'OrdenarPorCategoriaUsuario')) {
					if ($lang == 'es') {
						$query_categoria_lang = "cat.glosa_categoria";
					} else {
						$query_categoria_lang = "IFNULL(cat.glosa_categoria_lang , cat.glosa_categoria)";
					}

					$query = "SELECT $query_categoria_lang
								FROM trabajo
									JOIN usuario ON trabajo.id_usuario=usuario.id_usuario
									JOIN prm_categoria_usuario AS cat ON cat.id_categoria_usuario=usuario.id_categoria_usuario
										WHERE trabajo.id_cobro = '" . $this->fields['id_cobro'] . "'
											AND trabajo.codigo_asunto = '" . $asunto->fields['codigo_asunto'] . "'
											AND trabajo.visible=1
											ORDER BY cat.orden, usuario.id_usuario, trabajo.fecha ASC
												LIMIT 1";

					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					list($categoria) = mysql_fetch_array($resp);

					$html = str_replace('%categoria_abogado%', __($categoria), $html);
				} else
					$html = str_replace('%categoria_abogado%', '', $html);

				//Por conf se ve si se imprime la duracion trabajada cuando el cobro este en estado creado tambien
				//Por conf se ve si se imprime o no el valor del trabajo
				$html = str_replace('%duracion_tramites%', __('Duración'), $html);
				$html = str_replace('%valor_tramites%', __('Valor'), $html);
				$html = str_replace('%valor%', __('Valor'), $html);
				$html = str_replace('%valor_siempre%', __('Valor'), $html);
				$html = str_replace('%tarifa_fee%', __('%tarifa_fee%'), $html);
				break;

			case 'TRAMITES_FILAS': //GenerarDocumentoComun
				global $categoria_duracion_horas;
				global $categoria_duracion_minutos;
				global $categoria_valor;

				$row_tmpl = $html;
				$html = '';

				if ($lang == 'es') {
					$select_categoria = ", prm_categoria_usuario.glosa_categoria AS categoria, prm_categoria_usuario.id_categoria_usuario";
				} else {
					$select_categoria = ", IFNULL(prm_categoria_usuario.glosa_categoria_lang,prm_categoria_usuario.glosa_categoria) AS categoria, prm_categoria_usuario.id_categoria_usuario";
				}

				$join_categoria = "LEFT JOIN prm_categoria_usuario ON usuario.id_categoria_usuario=prm_categoria_usuario.id_categoria_usuario";

				if (Conf::GetConf($this->sesion, 'TramitesOrdenarPorCategoriaNombreUsuario')) {
					$order_categoria = "prm_categoria_usuario.orden, usuario.nombre, usuario.apellido1, usuario.id_usuario, ";
				} else if (Conf::GetConf($this->sesion, 'TramitesOrdenarPorCategoriaUsuario')) {
					$order_categoria = "prm_categoria_usuario.orden, usuario.id_usuario, ";
				} else if (Conf::GetConf($this->sesion, 'TramitesOrdenarPorCategoriaDetalleProfesional')) {
					$select_categoria = "";
					$order_categoria = "usuario.id_categoria_usuario DESC, ";
				} else if (Conf::GetConf($this->sesion, 'TramitesOrdenarPorFechaCategoria')) {
					$join_categoria = "LEFT JOIN prm_categoria_usuario ON usuario.id_categoria_usuario=prm_categoria_usuario.id_categoria_usuario";
					$order_categoria = "tramite.fecha, usuario.id_categoria_usuario, usuario.id_usuario, ";
				} else {
					$select_categoria = "";
					$join_categoria = "";
					$order_categoria = "";
				}

				$tramite_cobrable = '';
				if (!$this->fields['opc_mostrar_tramites_no_cobrables']) {
					$tramite_cobrable = ' AND tramite.cobrable=1 ';
				}
				$query_lista_tramites = "
					SELECT SQL_CALC_FOUND_ROWS
						tramite.duracion,
						tramite_tipo.glosa_tramite as glosa_tramite,
						tramite.descripcion,
						tramite.fecha,
						tramite.id_usuario,
						tramite.id_tramite,
						tramite.solicitante,
						tramite.tarifa_tramite as tarifa,
						tramite.codigo_asunto,
						tramite.id_moneda_tramite,
						tramite.cobrable,
						CONCAT_WS(' ', nombre, apellido1) as nombre_usuario {$select_categoria}, usuario.username
					FROM tramite
						JOIN asunto ON asunto.codigo_asunto=tramite.codigo_asunto
						JOIN contrato ON asunto.id_contrato=contrato.id_contrato
						JOIN tramite_tipo ON tramite.id_tramite_tipo=tramite_tipo.id_tramite_tipo
						LEFT JOIN usuario ON tramite.id_usuario=usuario.id_usuario
						{$join_categoria}
							WHERE tramite.id_cobro = '{$this->fields['id_cobro']}'
								AND tramite.codigo_asunto = '{$asunto->fields['codigo_asunto']}'
								{$tramite_cobrable}
								AND tramite.fecha BETWEEN '{$this->fields['fecha_ini']} ' AND '{$this->fields['fecha_fin']}'
						ORDER BY {$order_categoria} tramite.fecha ASC,tramite.descripcion";

				$lista_tramites = new ListaTramites($this->sesion, '', $query_lista_tramites);

				$asunto->fields['tramites_total_duracion'] = 0;
				$asunto->fields['tramites_total_valor'] = 0;

				if ($lista_tramites->num == 0) {
					$row = $row_tmpl;
					$row = str_replace('%iniciales%', '&nbsp;', $row);
					$row = str_replace('%fecha%', '&nbsp;', $row);
					$row = str_replace('%descripcion%', __('No hay trámites en este asunto'), $row);
					$row = str_replace('%valor%', '&nbsp;', $row);
					$row = str_replace('%duracion_tramites%', '&nbsp;', $row);
					$row = str_replace('%valor_tramites%', '&nbsp;', $row);
					$row = str_replace('%valor_siempre%', '&nbsp;', $row);
					$html .= $row;
				}

				for ($i = 0; $i < $lista_tramites->num; $i++) {
					$tramite = $lista_tramites->Get($i);
					list($h, $m, $s) = split(":", $tramite->fields['duracion']);
					if (!$tramite->fields['cobrable']) {
						$tarifa_tramite = 0;
					} else {
						$tarifa_tramite = $tramite->fields['tarifa'];
					}
					$asunto->fields['tramites_total_duracion'] += $h * 60 + $m + $s / 60;
					$asunto->fields['tramites_total_valor'] += $tarifa_tramite;
					$categoria_duracion_horas += round($h);
					$categoria_duracion_minutos += round($m);
					$categoria_valor += $tarifa_tramite;

					$row = $row_tmpl;
					$row = str_replace('%fecha%', Utiles::sql2fecha($tramite->fields['fecha'], $idioma->fields['formato_fecha']), $row);
					$row = str_replace('%descripcion%', ucfirst(stripslashes(htmlentities($tramite->fields['glosa_tramite']) . '<br>' . htmlentities($tramite->fields['descripcion']))), $row);
					$row = str_replace('%tramite_glosa%', ucfirst(stripslashes(htmlentities($tramite->fields['glosa_tramite']))), $row);
					$row = str_replace('%tramite_descripcion%', ucfirst(stripslashes(htmlentities($tramite->fields['descripcion']))), $row);

					$row = str_replace('%solicitante%', $this->fields['opc_ver_solicitante'] ? $tramite->fields['solicitante'] : '', $row);

					//muestra las iniciales de los profesionales
					//Las iniciales fueron reemplazas por el username. Pivotal: 109198728
					$row = str_replace('%iniciales%', $tramite->fields['username'], $row);
					$row = str_replace('%username%', $tramite->fields['username'], $row);

					if ($this->fields['opc_ver_detalles_por_hora_iniciales'] == 1) {
						$row = str_replace('%profesional%', $tramite->fields['username'], $row);
					} else {
						$row = str_replace('%profesional%', $tramite->fields['nombre_usuario'], $row);
					}


					list($ht, $mt, $st) = explode(":", $tramite->fields['duracion']);
					$asunto->fields['tramites_total_duracion_trabajado'] += $ht * 60 + $mt + $st / 60;
					$asunto->fields['trabajos_total_duracion_trabajada'] += $ht * 60 + $mt + $st / 60;
					$duracion_decimal_trabajada = $ht + $mt / 60 + $st / 3600;

					$ImprimirDuracionTrabajada = Conf::GetConf($this->sesion, 'ImprimirDuracionTrabajada');

					$saldo = $tarifa_tramite;
					$monto_tramite = $saldo;
					$monto_tramite_moneda_total = $saldo * ($cobro_moneda->moneda[$tramite->fields['id_moneda_tramite']]['tipo_cambio'] / $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']);
					$totales['total_tramites'] += $saldo;

					$minutos_decimal = $m / 60;
					$duracion_decimal = $h + $minutos_decimal + $s / 3600;
					$row = str_replace('%duracion_decimal%', number_format($duracion_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					$row = str_replace('%duracion%', $h . ':' . $m, $row);
					$row = str_replace('%duracion_tramites%', $h . ':' . $m, $row);

					$row = str_replace('%valor%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($saldo, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					$row = str_replace('%valor_siempre%', number_format($tramite->fields['tarifa'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					$row = str_replace('%valor_tramites%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($saldo, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

					if (Conf::GetConf($this->sesion, 'TramitesOrdenarPorCategoriaUsuario')) {

						$tramite_siguiente = $lista_tramites->Get($i + 1);

						if (!empty($tramite_siguiente->fields['id_categoria_usuario'])) {

							if ($tramite->fields['id_categoria_usuario'] != $tramite_siguiente->fields['id_categoria_usuario']) {

								$html3 = $parser->tags['TRAMITES_TOTAL'];
								$html3 = str_replace('%glosa%', __('Total'), $html3);
								$categoria_duracion_horas += floor($categoria_duracion_minutos / 60);
								$categoria_duracion_minutos = round($categoria_duracion_minutos % 60);
								$html3 = str_replace('%duracion%', sprintf('%02d', $categoria_duracion_horas) . ':' . sprintf('%02d', $categoria_duracion_minutos), $html3);


								if ((Conf::GetConf($this->sesion, 'NoImprimirValorTrabajo')) && ($this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION')) {
									$html3 = str_replace('%valor%', '', $html3);
								} else {
									$html3 = str_replace('%valor%', $moneda->fields['simbolo'] . $this->espacio . number_format($categoria_valor, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
								}

								$total_tramites_categoria .= $html3;

								$html3 = $parser->tags['TRAMITES_ENCABEZADO'];
								$html3 = str_replace('%duracion%', __('Duración'), $html3);
								$html3 = str_replace('%cobrable%', __('<td align="center" width="80">Cobrable</td>'), $html3);
								$html3 = str_replace('%fecha%', __('Fecha'), $html3);
								$html3 = str_replace('%descripcion%', __('Descripción'), $html3);
								$html3 = str_replace('%profesional%', __('Profesional'), $html3);
								$html3 = str_replace('%abogado%', __('Abogado'), $html3);
								$html3 = str_replace('%categoria_abogado%', __($tramite_siguiente->fields['categoria']), $html3);
								if (Conf::GetConf($this->sesion, 'NoImprimirValorTrabajo') && $this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION')
									$html3 = str_replace('%valor%', '', $html3);
								else
									$html3 = str_replace('%valor%', __('Valor'), $html3);
								$encabezado_tramites_categoria .= $html3;

								$row = str_replace('%TRAMITES_CATEGORIA%', $total_tramites_categoria . $encabezado_tramites_categoria, $row);
								$categoria_duracion_horas = 0;
								$categoria_duracion_minutos = 0;
								$categoria_valor = 0;
								$total_trabajos_categoria = '';
								$encabezado_trabajos_categoria = '';
							}
							else {
								$row = str_replace('%TRAMITES_CATEGORIA%', '', $row);
							}
						} else {
							$html3 = $parser->tags['TRAMITES_TOTAL'];
							$html3 = str_replace('%glosa%', __('Total'), $html3);
							$categoria_duracion_horas += floor($categoria_duracion_minutos / 60);
							$categoria_duracion_minutos = round($categoria_duracion_minutos % 60);
							$html3 = str_replace('%duracion%', sprintf('%02d', $categoria_duracion_horas) . ':' . sprintf('%02d', $categoria_duracion_minutos), $html3);

							if ($this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION' && Conf::GetConf($this->sesion, 'NoImprimirValorTrabajo')) {
								$html3 = str_replace('%valor%', '', $html3);
							} else {
								$html3 = str_replace('%valor%', $moneda->fields['simbolo'] . $this->espacio . number_format($categoria_valor, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
							}

							$total_tramites_categoria .= $html3;
							$row = str_replace('%TRAMITES_CATEGORIA%', $total_tramites_categoria, $row);
							$categoria_duracion_horas = 0;
							$categoria_duracion_minutos = 0;
							$categoria_valor = 0;
							$total_tramites_categoria = '';
							$encabezado_tramites_categoria = '';
						}
					}
					$html .= $row;
				}
				break;

			case 'TRAMITES_TOTAL': //GenerarDocumentoComun
				global $x_resultados;
				$horas_cobrables_tramites = floor(($asunto->fields['tramites_total_duracion_trabajado']) / 60);
				$minutos_cobrables_tramites = sprintf("%02d", $asunto->fields['tramites_total_duracion_trabajado'] % 60);
				$horas_cobrables = floor(($asunto->fields['trabajos_total_duracion_trabajada']) / 60);
				$minutos_cobrables = sprintf("%02d", $asunto->fields['trabajos_total_duracion_trabajada'] % 60);

				$html = str_replace('%glosa_tramites%', __('Total ' . __('Trámites')), $html);
				$html = str_replace('%glosa_tramites_castropal%', __('Total otros servicios'), $html);
				$html = str_replace('%glosa%', __('Total'), $html);
				$minutos_decimal = $minutos_cobrables / 60;
				$duracion_decimal = $horas_cobrables + $minutos_decimal;
				$html = str_replace('%duracion_decimal%', number_format($duracion_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%duracion_tramites%', $horas_cobrables_tramites . ':' . $minutos_cobrables_tramites, $html);
				$html = str_replace('%duracion%', $horas_cobrables . ':' . $minutos_cobrables, $html);

				$html = str_replace('%valor_tramites%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($totales['total_tramites'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				if ($this->fields['opc_moneda_total'] == $this->fields['id_moneda']) {
					$html = str_replace('%valor_tramites_monedabase_mb%', '', $html);
				} else {
					$html = str_replace('%valor_tramites_monedabase_mb%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($x_resultados['monto_tramites'][$this->fields['opc_moneda_total']], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}

				$html = str_replace('%valor_siempre%', $moneda->fields['simbolo'] . $this->espacio . number_format($asunto->fields['tramites_total_valor'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			case 'TRABAJOS_ENCABEZADO': //GenerarDocumentoComun
				if ($this->fields['estado'] == 'CREADO' || $this->fields['estado'] == 'EN REVISION') {
					$html = str_replace('%td_id_trabajo%', '<td align="center">%ntrabajo%</td>', $html);
				} else {
					$html = str_replace('%td_id_trabajo%', '', $html);
				}
				$html = str_replace('%ntrabajo%', __('N°</br>Trabajo'), $html);
				if ($this->fields['opc_ver_solicitante']) {
					$html = str_replace('%td_solicitante%', '<td width="16%" align="left">%solicitante%</td>', $html);
				} else {
					$html = str_replace('%td_solicitante%', '', $html);
				}
				$html = str_replace('%solicitante%', __('Solicitado Por'), $html);
				if ($lang == 'es') {
					$html = str_replace('%id_asunto%', __('ID Asunto'), $html);
					$html = str_replace('%tarifa_hora%', __('Tarifa<br>Hora'), $html);
				} else {
					$html = str_replace('%id_asunto%', __('Matter <br> ID'), $html);
					$html = str_replace('%tarifa_hora%', __('Hourly<br> Rate'), $html);
				}
				$html = str_replace('%importe%', __('Importe'), $html);
				$html = str_replace('%tarifa_hora%', __('Tarifa Hora'), $html);
				$html = str_replace('%ordenado_por%', $this->fields['opc_ver_solicitante'] ? __('Ordenado Por') : '', $html);
				$html = str_replace('%ordenado_por_jjr%', $this->fields['opc_ver_solicitante'] ? __('Solicitado Por') : '', $html);
				$html = str_replace('%periodo%', (($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') and ( $this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '')) ? '' : __('Periodo'), $html);
				$html = str_replace('%valor_periodo_ini%', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? '' : Utiles::sql2fecha($this->fields['fecha_ini'], $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%valor_periodo_fin%', ($this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '') ? '' : __('hasta') . ' ' . Utiles::sql2fecha($this->fields['fecha_fin'], $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%cliente%', __('Cliente'), $html);
				$html = str_replace('%glosa_cliente%', $cliente->fields['glosa_cliente'], $html);
				$html = str_replace('%asunto%', __('Asunto'), $html);
				$html = str_replace('%glosa_asunto%', $asunto->fields['glosa_asunto'], $html);
				$html = str_replace('%fecha%', __('Fecha'), $html);
				$html = str_replace('%descripcion%', __('Descripción'), $html);
				$html = str_replace('%servicios_prestados%', __('Servicios Prestados'), $html);
				$html = str_replace('%detalle_trabajo%', __('Detalle del Trabajo Realizado'), $html);
				$html = str_replace('%profesional%', __('Profesional'), $html);
				$html = str_replace('%duracion_cobrable%', __('Duración cobrable'), $html);
				$html = str_replace('%monto_total%', __('Monto total'), $html);
				$html = str_replace('%staff%', __('Staff'), $html);
				$html = str_replace('%abogado%', __('Abogado'), $html);
				$html = str_replace('%abogado_raz%', __('abogado_raz'), $html);
				$html = str_replace('%tiempo_raz%', __('tiempo_raz'), $html);
				$html = str_replace('%horas%', __('Horas'), $html);
				$html = str_replace('%monto%', __('Monto'), $html);

				if ($this->fields['opc_ver_columna_cobrable'])
					$html = str_replace('%cobrable%', __('<td align="center" width="80">Cobrable</td>'), $html);  // tAndres Oestemer
				else
					$html = str_replace('%cobrable%', '', $html);

				if (Conf::GetConf($this->sesion, 'TrabajosOrdenarPorCategoriaUsuario')) {

					if ($lang == 'es') {
						$query_categoria_lang = "cat.glosa_categoria";
					} else {
						$query_categoria_lang = "IFNULL(cat.glosa_categoria_lang , cat.glosa_categoria)";
					}

					$query = "SELECT $query_categoria_lang
									FROM trabajo
									JOIN usuario ON trabajo.id_usuario=usuario.id_usuario
									JOIN prm_categoria_usuario AS cat ON cat.id_categoria_usuario=usuario.id_categoria_usuario
									WHERE trabajo.id_cobro = '" . $this->fields['id_cobro'] . "'
									AND trabajo.codigo_asunto = '" . $asunto->fields['codigo_asunto'] . "'
									AND trabajo.visible=1
									ORDER BY cat.orden, usuario.id_usuario, trabajo.fecha ASC
									LIMIT 1";
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					list($categoria) = mysql_fetch_array($resp);
					$html = str_replace('%categoria_abogado%', __($categoria), $html);
				} else if (Conf::GetConf($this->sesion, 'SepararPorUsuario')) {
					$query = "SELECT CONCAT(usuario.nombre,' ',usuario.apellido1),trabajo.tarifa_hh
									FROM trabajo
									JOIN usuario ON trabajo.id_usuario=usuario.id_usuario
									WHERE trabajo.id_cobro = '" . $this->fields['id_cobro'] . "'
									AND trabajo.codigo_asunto = '" . $asunto->fields['codigo_asunto'] . "'
									AND trabajo.visible=1
									ORDER BY usuario.id_categoria_usuario, usuario.id_usuario, trabajo.fecha ASC
									LIMIT 1";
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					list($abogado, $tarifa) = mysql_fetch_array($resp);
					$html = str_replace('%categoria_abogado%', __($abogado), $html);

					$html = str_replace('%tarifa%', $moneda->fields['simbolo'] . $this->espacio . number_format($tarifa, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				} else {
					$html = str_replace('%categoria_abogado%', '', $html);
				}

				//Por conf se ve si se imprime la duracion trabajada cuando el cobro este en estado creado tambien
				$ImprimirDuracionTrabajada = Conf::GetConf($this->sesion, 'ImprimirDuracionTrabajada');

				/* Lo anchores con la extension _bmahj usa Bofill Mir y lo que hace es que llama a las columnas
				  en la lista de trabajos igual como a las columnas en el resumen profesional */

				if ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$html = str_replace('%td_retainer%', '<td width="80" align="center">%duracion_retainer%</td>', $html);
					$html = str_replace('%td_sobre_retainer%', '<td width="80" align="center">%duracion_sobre_retainer%</td>', $html);
					$html = str_replace('%duracion_retainer%', __('Duración Retainer'), $html);
					$html = str_replace('%duracion_sobre_retainer%', __('Duración Tarificada'), $html);
				} else {
					$html = str_replace('%td_retainer%', '', $html);
					$html = str_replace('%td_sobre_retainer%', '', $html);
				}

				if ($this->fields['forma_cobro'] == 'FLAT FEE') {
					$html = str_replace('%duracion_trabajada_bmahj%', '', $html);
					$html = str_replace('%duracion_descontada_bmahj%', '', $html);
					$html = str_replace('%duracion_bmahj%', __('Hrs. Trabajadas'), $html);
					$html = str_replace('%tiempo%', __('Tiempo'), $html);

					$html = str_replace('%duracion_trabajada%', '', $html);
					$html = str_replace('%duracion_descontada%', '', $html);
					$html = str_replace('%duracion%', __('Duración trabajada'), $html);
				}
				if ($ImprimirDuracionTrabajada && ( $this->fields['estado'] == 'CREADO' || $this->fields['estado'] == 'EN REVISION' )) {
					$html = str_replace('%duracion_trabajada_bmahj%', __('Hrs. Trabajadas'), $html);
					$html = str_replace('%duracion_bmahj%', __('Hrs. Tarificadas'), $html);
					$html = str_replace('%tiempo%', __('Tiempo'), $html);
					$html = str_replace('%duracion_descontada_bmahj%', __('Hrs. Castigadas'), $html);
					$html = str_replace('%duracion_descontada%', __('Hrs.:Mins. Descontadas'), $html);

					$html = str_replace('%duracion_trabajada%', __('Duración trabajada'), $html);
					$html = str_replace('%duracion%', __('Duración cobrable'), $html);
					if ($descontado)
						$html = str_replace('%duracion_descontada%', __('Duración descontada'), $html);
					else
						$html = str_replace('%duracion_descontada%', '', $html);
				}
				else if ($this->fields['opc_ver_horas_trabajadas']) {
					$html = str_replace('%duracion_trabajada_bmahj%', __('Hrs. Trabajadas'), $html);
					$html = str_replace('%duracion_bmahj%', __('Hrs. Tarificadas'), $html);
					$html = str_replace('%tiempo%', __('Tiempo'), $html);
					$html = str_replace('%duracion_descontada_bmahj%', __('Hrs. Castigadas'), $html);
					$html = str_replace('%duracion_descontada%', __('Hrs.:Mins. Descontadas'), $html);

					$html = str_replace('%duracion_trabajada%', __('Duración trabajada'), $html);
					$html = str_replace('%duracion%', __('Duración cobrable'), $html);
					$html = str_replace('%duracion_descontada%', __('Duración castigada'), $html);
				} else {
					$html = str_replace('%duracion_trabajada_bmahj%', '', $html);
					$html = str_replace('%duracion_descontada_bmahj%', '', $html);
					$html = str_replace('%duracion_bmahj%', __('Hrs. Tarificadas'), $html);
					$html = str_replace('%tiempo%', __('Tiempo'), $html);

					$html = str_replace('%duracion_trabajada%', '', $html);
					$html = str_replace('%duracion_descontada%', '', $html);
					$html = str_replace('%duracion%', __('Duración'), $html);
				}
				$html = str_replace('%duracion_tyc%', __('Duración'), $html);
				//Por conf se ve si se imprime o no el valor del trabajo
				$ImprimirValorTrabajo = Conf::GetConf($this->sesion, 'ImprimirValorTrabajo');

				if ($ImprimirValorTrabajo && $this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION')
					$html = str_replace('%valor%', '', $html);
				else
					$html = str_replace('%valor%', __('Valor'), $html);
				$html = str_replace('%valor_siempre%', __('Valor'), $html);
				$html = str_replace('%tarifa_fee%', __('%tarifa_fee%'), $html);

				if ($this->fields['opc_ver_detalles_por_hora_categoria'] == 1)
					$html = str_replace('%td_categoria%', '<td class="td_categoria" align="left">%categoria%</td>', $html);
				else
					$html = str_replace('%td_categoria%', '', $html);
				$html = str_replace('%categoria%', __($this->fields['codigo_idioma'] . '_Categoría'), $html);

				if ($this->fields['opc_ver_detalles_por_hora_tarifa'] == 1) {
					$html = str_replace('%td_tarifa%', '<td width="80" align="center">%tarifa%</td>', $html);
					$html = str_replace('%td_tarifa_ajustada%', '<td width="80" align="center">%tarifa%</td>', $html);
				} else {
					$html = str_replace('%td_tarifa%', '', $html);
					$html = str_replace('%td_tarifa_ajustada%', '', $html);
				}
				$html = str_replace('%tarifa%', __('Tarifa'), $html);

				if ($this->fields['opc_ver_detalles_por_hora_importe'] == 1) {
					$html = str_replace('%td_importe%', '<td width="80" align="center">%importe%</td>', $html);
					$html = str_replace('%td_importe_ajustado%', '<td width="80" align="center">%importe%</td>', $html);
				} else {
					$html = str_replace('%td_importe%', '', $html);
					$html = str_replace('%td_importe_ajustado%', '', $html);
				}
				$html = str_replace('%importe%', __($this->fields['codigo_idioma'] . '_Importe'), $html);
				break;

			case 'TRABAJOS_FILAS': //GenerarDocumentoComun
				global $categoria_duracion_horas;
				global $categoria_duracion_minutos;
				global $categoria_valor;
				global $profesionales;
				$row_tmpl = $html;
				$html = '';
				$where_horas_cero = '';

				if ($lang == 'es') {
					$select_categoria = ", prm_categoria_usuario.glosa_categoria AS categoria, prm_categoria_usuario.id_categoria_usuario";
				} else {
					$select_categoria = ", IFNULL(prm_categoria_usuario.glosa_categoria_lang, prm_categoria_usuario.glosa_categoria) AS categoria, prm_categoria_usuario.id_categoria_usuario";
				}

				$join_categoria = "LEFT JOIN prm_categoria_usuario ON usuario.id_categoria_usuario=prm_categoria_usuario.id_categoria_usuario";

				//esto funciona por Conf si el metodo del conf OrdenarPorCategoriaUsuario es true se ordena por categoria
				if (Conf::GetConf($this->sesion, 'TrabajosOrdenarPorCategoriaNombreUsuario')) {
					$order_categoria = "prm_categoria_usuario.orden, usuario.nombre, usuario.apellido1, usuario.id_usuario, ";
				} else if (Conf::GetConf($this->sesion, 'TrabajosOrdenarPorCategoriaUsuario')) {
					$order_categoria = "prm_categoria_usuario.orden, usuario.id_usuario, ";
				} else if (Conf::GetConf($this->sesion, 'SepararPorUsuario')) {
					$order_categoria = "usuario.id_categoria_usuario, usuario.id_usuario, ";
				} else if (Conf::GetConf($this->sesion, 'TrabajosOrdenarPorCategoriaDetalleProfesional')) {
					$select_categoria = "";
					$order_categoria = "usuario.id_categoria_usuario DESC, ";
				} else if (Conf::GetConf($this->sesion, 'TrabajosOrdenarPorFechaCategoria')) {
					$order_categoria = "trabajo.fecha, usuario.id_categoria_usuario, usuario.id_usuario, ";
				} else {
					$select_categoria = "";
					$join_categoria = "";
					$order_categoria = "";
				}

				if (!method_exists('Conf', 'MostrarHorasCero')) {
					if ($this->fields['opc_ver_horas_trabajadas']) {
						$where_horas_cero = "AND trabajo.duracion > '0000-00-00 00:00:00'";
					} else {
						$where_horas_cero = "AND trabajo.duracion_cobrada > '0000-00-00 00:00:00'";
					}
				}

				if ($this->fields['opc_ver_valor_hh_flat_fee'] && $this->fields['forma_cobro'] != 'ESCALONADA') {
					$dato_monto_cobrado = " ( trabajo.tarifa_hh * TIME_TO_SEC( trabajo.duracion_cobrada ) ) / 3600 ";
				} else {
					$dato_monto_cobrado = " trabajo.monto_cobrado ";
				}

				if ($this->fields['opc_ver_cobrable']) {
					$and .= "";
				} else {
					$and .= "AND trabajo.visible = 1";
				}

				if ($lang == 'es') {
					$query_categoria_lang = "prm_categoria_usuario.glosa_categoria AS categoria,";
				} else {
					$query_categoria_lang = "IFNULL(prm_categoria_usuario.glosa_categoria_lang ,prm_categoria_usuario.glosa_categoria) AS categoria,";
				}

				//Tabla de Trabajos.
				//se hace select a los visibles y cobrables para diferenciarlos, tambien se selecciona
				//la duracion retainer.
				$query = "SELECT SQL_CALC_FOUND_ROWS
									trabajo.duracion_cobrada,
									trabajo.duracion_retainer,
									trabajo.duracion_cobrada-trabajo.duracion_retainer as duracion_tarificada,
									trabajo.descripcion,
									trabajo.fecha,
									trabajo.id_usuario,
									$dato_monto_cobrado as monto_cobrado,
									trabajo.visible,
									trabajo.cobrable,
									trabajo.id_trabajo,
									trabajo.tarifa_hh,
									trabajo.tarifa_hh * ( TIME_TO_SEC( duracion_cobrada ) / 3600 ) as importe,

									trabajo.codigo_asunto,
									trabajo.solicitante,
									$query_categoria_lang
									CONCAT_WS(' ', nombre, apellido1) as nombre_usuario,
									trabajo.duracion,
									usuario.username as username $select_categoria
							FROM trabajo
							LEFT JOIN usuario ON trabajo.id_usuario=usuario.id_usuario
							LEFT JOIN cobro ON cobro.id_cobro = trabajo.id_cobro
							LEFT JOIN prm_categoria_usuario ON usuario.id_categoria_usuario=prm_categoria_usuario.id_categoria_usuario
							WHERE trabajo.id_cobro = '" . $this->fields['id_cobro'] . "'
							AND trabajo.codigo_asunto = '" . $asunto->fields['codigo_asunto'] . "'
							$and AND trabajo.id_tramite=0 $where_horas_cero
							ORDER BY $order_categoria trabajo.fecha ASC,trabajo.descripcion";

				$lista_trabajos = new ListaTrabajos($this->sesion, '', $query);

				$asunto->fields['trabajos_total_duracion'] = 0;
				$asunto->fields['trabajos_total_valor'] = 0;
				$asunto->fields['trabajos_total_duracion_retainer'] = 0;
				$asunto->fields['trabajos_total_importe'] = 0;



				for ($i = 0; $i < $lista_trabajos->num; $i++) {
					$trabajo = $lista_trabajos->Get($i);
					list($ht, $mt, $st) = split(":", $trabajo->fields['duracion']);
					list($h, $m, $s) = split(":", $trabajo->fields['duracion_cobrada']);
					list($h_retainer, $m_retainer, $s_retainer) = split(":", $trabajo->fields['duracion_retainer']);
					$duracion_cobrada_decimal = $h + $m / 60 + $s / 3600;
					$asunto->fields['trabajos_total_duracion'] += $h * 60 + $m + $s / 60;
					$asunto->fields['trabajos_total_valor'] += $trabajo->fields['monto_cobrado'];
					$asunto->fields['trabajos_total_duracion_retainer'] += $h_retainer * 60 + $m_retainer + $s_retainer / 60;
					$asunto->fields['trabajos_total_duracion_sobre_retainer'] += ($h_retainer - $h) * 60 + ($m_retainer - $m) + ($s_retainer - $s) / 60;

					$asunto->fields['trabajos_total_importe'] += $trabajo->fields['importe'];
					$asunto->fields['trabajos_total_duracion_trabajada'] += $ht * 60 + $mt + $st / 60;
					$duracion_decimal_trabajada = $ht + $mt / 60 + $st / 3600;
					$duracion_decimal_descontada = $ht - $h + ($mt - $m) / 60 + ($st - $s) / 3600;
					$duracion_decimal_retainer = $h_retainer + $m_retainer / 60 + $s_retainer / 3600;
					$duracion_decimal_sobre_retainer = ($h - $h_retainer) + ($m - $m_retainer) / 60 + ($s - $s_retainer) / 3600;
					$minutos_decimal = $m / 60;
					$duracion_decimal = $h + $minutos_decimal + $s / 3600;

					if (($mt - $m) < 0) {
						$horas_descontadas = $ht - $h - 1;
						$minutos_descontadas = $mt - $m + 60;
					} else {
						$horas_descontadas = $ht - $h;
						$minutos_descontadas = $mt - $m;
					}

					$categoria_duracion_horas+=round($h);
					$categoria_duracion_minutos+=round($m);
					$categoria_valor+=$trabajo->fields['monto_cobrado'];

					if (!isset($profesionales[$trabajo->fields['nombre_usuario']])) {
						$profesionales[$trabajo->fields['nombre_usuario']] = array();
						$profesionales[$trabajo->fields['nombre_usuario']]['tiempo_trabajado_real'] = 0; // horas realmente trabajadas segun duracion en vez de duracion_cobrada
						$profesionales[$trabajo->fields['nombre_usuario']]['tiempo_trabajado'] = 0; //el tiempo trabajado es cobrable y no cobrable
						$profesionales[$trabajo->fields['nombre_usuario']]['tiempo'] = 0; //tiempo cobrable
						$profesionales[$trabajo->fields['nombre_usuario']]['valor'] = 0;
						$profesionales[$trabajo->fields['nombre_usuario']]['retainer'] = 0;
						$profesionales[$trabajo->fields['nombre_usuario']]['flatfee'] = 0;
						$profesionales[$trabajo->fields['nombre_usuario']]['descontado_real'] = 0;
						$profesionales[$trabajo->fields['nombre_usuario']]['descontado'] = 0; //tiempo no cobrable
						$profesionales[$trabajo->fields['nombre_usuario']]['tarifa'] = $trabajo->fields['tarifa_hh'];
						$profesionales[$trabajo->fields['nombre_usuario']]['id_categoria_usuario'] = $trabajo->fields['id_categoria_usuario']; //nombre de la categoria
						$profesionales[$trabajo->fields['nombre_usuario']]['categoria'] = $trabajo->fields['categoria']; // nombre de la categoria
					}
					if (Conf::GetConf($this->sesion, 'GuardarTarifaAlIngresoDeHora')) {
						$profesionales[$trabajo->fields['nombre_usuario']]['tarifa'] = $trabajo->fields['tarifa_hh'];
					}

					$categoria_duracion_trabajada += $duracion_decimal_trabajada;
					$categoria_duracion_descontada += $duracion_decimal_descontada;

					//se agregan los valores para el detalle de profesionales
					$profesionales[$trabajo->fields['nombre_usuario']]['tiempo_trabajado_real'] += $ht * 60 + $mt + $st / 60;
					$profesionales[$trabajo->fields['nombre_usuario']]['descontado_real'] += ( $ht - $h ) * 60 + ( $mt - $m ) + ( $st - $s ) / 60;
					$profesionales[$trabajo->fields['nombre_usuario']]['tiempo_trabajado'] += $h * 60 + $m + $s / 60;
					if ($this->fields['forma_cobro'] == 'FLAT FEE' && $trabajo->fields['cobrable'] == '1') {
						$profesionales[$trabajo->fields['nombre_usuario']]['flatfee'] += $h * 60 + $m + $s / 60;
					}
					if ($trabajo->fields['cobrable'] == '0') {
						$profesionales[$trabajo->fields['nombre_usuario']]['descontado_real'] += $ht * 60 + $mt + $st / 60;
						$profesionales[$trabajo->fields['nombre_usuario']]['descontado'] += $h * 60 + $m + $s / 60;
					} else {
						$profesionales[$trabajo->fields['nombre_usuario']]['tiempo'] += $h * 60 + $m + $s / 60;
						$profesionales[$trabajo->fields['nombre_usuario']]['valor'] += $trabajo->fields['monto_cobrado'];
					}
					if ($h_retainer * 60 + $m_retainer + $s_retainer / 60 > 0) {
						$profesionales[$trabajo->fields['nombre_usuario']]['retainer'] += $h_retainer * 60 + $m_retainer + $s_retainer / 60;
					}

					if ($this->fields['opc_ver_detalles_por_hora_categoria'] == 1)
						$row = str_replace('%td_categoria%', '<td align="left">%categoria%</td>', $row);
					else
						$row = str_replace('%td_categoria%', '', $row);
					$row = str_replace('%categoria%', __($trabajo->fields['categoria']), $row);

					if ($this->fields['opc_ver_detalles_por_hora_tarifa'] == 1) {
						$row = str_replace('%td_tarifa%', '<td align="center">%tarifa%</td>', $row);
						$row = str_replace('%td_tarifa_ajustada%', '<td align="center">%tarifa_ajustada%</td>', $row);
					} else {
						$row = str_replace('%td_tarifa%', '', $row);
						$row = str_replace('%td_tarifa_ajustada%', '', $row);
					}
					if ($this->fields['forma_cobro'] == 'ESCALONADA') {
						$row = str_replace('%tarifa%', number_format(($trabajo->fields['monto_cobrado'] / $duracion_cobrada_decimal), $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%tarifa_ajustada%', number_format($trabajo->fields['tarifa_hh'] * $x_factor_ajuste, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					} else {
						$row = str_replace('%tarifa%', number_format($trabajo->fields['tarifa_hh'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%tarifa_ajustada%', number_format($trabajo->fields['tarifa_hh'] * $x_factor_ajuste, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					}
					$row = $row_tmpl;
					$row = str_replace('%valor_codigo_asunto%', $trabajo->fields['codigo_asunto'], $row);
					$row = str_replace('%fecha%', Utiles::sql2fecha($trabajo->fields['fecha'], $idioma->fields['formato_fecha']), $row);
					$row = str_replace('%descripcion%', ucfirst(stripslashes(htmlentities($trabajo->fields['descripcion']))), $row);
					if ($this->fields['estado'] == 'CREADO' || $this->fields['estado'] == 'EN REVISION') {
						$row = str_replace('%td_id_trabajo%', '<td align="center">%ntrabajo%</td>', $row);
					} else {
						$row = str_replace('%td_id_trabajo%', '', $row);
					}
					$row = str_replace('%ntrabajo%', $trabajo->fields['id_trabajo'], $row);
					if ($this->fields['opc_ver_solicitante']) {
						$row = str_replace('%td_solicitante%', '<td align="left">%solicitante%</td>', $row);
					} else {
						$row = str_replace('%td_solicitante%', '', $row);
					}

					$row = str_replace('%solicitante%', $this->fields['opc_ver_solicitante'] ? $trabajo->fields['solicitante'] : '', $row);
					if ($this->fields['opc_ver_detalles_por_hora_iniciales']) {
						$row = str_replace('%profesional%', $trabajo->fields['username'], $row);
					} else {
						$row = str_replace('%profesional%', $trabajo->fields['nombre_usuario'], $row);
					}

					//paridad
					$row = str_replace('%paridad%', $i % 2 ? 'impar' : 'par', $row);

					//muestra las iniciales de los profesionales
					//Las iniciales fueron reemplazas por el username. Pivotal: 109198728
					$row = str_replace('%iniciales%', $trabajo->fields['username'], $row);

					$row = str_replace('%username%', $trabajo->fields['username'], $row);

					if ($this->fields['opc_ver_columna_cobrable']) {
						if ($trabajo->fields['cobrable'] == 1) {
							$row = str_replace('%cobrable%', __('<td align="center">Si</td>'), $row);
						} else {
							$row = str_replace('%cobrable%', __('<td align="center">No</td>'), $row);
						}
					} else {
						$row = str_replace('%cobrable%', __(''), $row);
					}

					if ($ht < $h || ( $ht == $h && $mt < $m ) || ( $ht == $h && $mt == $m && $st < $s ))
						$asunto->fields['trabajos_total_duracion_trabajada'] += $h * 60 + $m + $s / 60;
					else
						$asunto->fields['trabajos_total_duracion_trabajada'] += $ht * 60 + $mt + $st / 60;
					$duracion_decimal_trabajada = $ht + $mt / 60 + $st / 3600;
					$duracion_decimal_descontada = $ht - $h + ($mt - $m) / 60 + ($st - $s) / 3600;
					$minutos_decimal = $m / 60;
					$duracion_decimal = $h + $minutos_decimal + $s / 3600;

					if (($mt - $m) < 0) {
						$horas_descontadas = $ht - $h - 1;
						$minutos_descontadas = $mt - $m + 60;
					} else {
						$horas_descontadas = $ht - $h;
						$minutos_descontadas = $mt - $m;
					}

					$ImprimirDuracionTrabajada = Conf::GetConf($this->sesion, 'ImprimirDuracionTrabajada');

					if ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
						$row = str_replace('%td_retainer%', '<td align="center">%duracion_retainer%</td>', $row);
						$row = str_replace('%td_sobre_retainer%', '<td align="center">%duracion_retainer%</td>', $row);
						if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
							$row = str_replace('%duracion_retainer%', number_format($duracion_decimal_retainer, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $row);
							$row = str_replace('%duracion_sobre_retainer%', number_format($duracion_decimal_sobre_retainer, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $row);
						} else {
							$row = str_replace('%duracion_retainer%', $h_retainer . ':' . sprintf("%02d", $m_retainer), $row);
							$row = str_replace('%duracion_sobre_retainer%', ($h - $h_retainer) . ':' . sprintf("%02d", ($m - $m_retainer)), $row);
						}
					} else {
						$row = str_replace('%duracion_sobre_retainer%', '%duracion%', $row);
						$row = str_replace('%td_retainer%', '', $row);
					}

					if ($this->fields['forma_cobro'] == 'FLAT FEE') {
						$row = str_replace('%duracion_decimal_trabajada%', '', $row);
						$row = str_replace('%duracion_trabajada%', '', $row);
						$row = str_replace('%duracion_decimal_descontada%', '', $row);
						$row = str_replace('%duracion_descontada%', '', $row);

						if (!$this->fields['opc_ver_horas_trabajadas']) {
							$row = str_replace('%duracion_decimal%', number_format($duracion_decimal, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
								$row = str_replace('%duracion%', number_format($duracion_decimal, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $row);
							} else {
								$row = str_replace('%duracion%', $h . ':' . sprintf("%02d", $m), $row);
							}
						} else {
							$row = str_replace('%duracion_decimal%', number_format($duracion_decimal_trabajada, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
								$row = str_replace('%duracion%', number_format($duracion_decimal_trabajada, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $row);
							} else {
								$row = str_replace('%duracion%', $ht . ':' . sprintf("%02d", $mt), $row);
							}
						}
					}
					if ($ImprimirDuracionTrabajada && ( $this->fields['estado'] == 'CREADO' || $this->fields['estado'] == 'EN REVISION' )) {
						$row = str_replace('%duracion_decimal_trabajada%', number_format($duracion_decimal_trabajada, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
							$row = str_replace('%duracion_trabajada%', number_format($duracion_decimal_trabajada, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $row);
							$row = str_replace('%duracion_descontada%', number_format($duracion_decimal_descontada, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $row);
						} else {
							$row = str_replace('%duracion_trabajada%', $ht . ':' . sprintf("%02d", $mt), $row);
							$row = str_replace('%duracion_descontada%', Utiles::Decimal2GlosaHora($duracion_decimal_descontada), $row);
						}
						$row = str_replace('%duracion_decimal_descontada%', number_format($duracion_decimal_descontada, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					} else if ($this->fields['opc_ver_horas_trabajadas']) {
						$row = str_replace('%duracion_decimal_trabajada%', number_format($duracion_decimal_trabajada, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
							$row = str_replace('%duracion_trabajada%', number_format($duracion_decimal_trabajada, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $row);
							$row = str_replace('%duracion_descontada%', number_format($duracion_decimal_descontada, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $row);
						} else {
							$row = str_replace('%duracion_trabajada%', $ht . ':' . sprintf("%02d", $mt), $row);
							$row = str_replace('%duracion_descontada%', Utiles::Decimal2GlosaHora($duracion_decimal_descontada), $row);
						}
						$row = str_replace('%duracion_decimal_descontada%', number_format($duracion_decimal_descontada, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					} else {
						$row = str_replace('%duracion_descontada%', '', $row);
						$row = str_replace('%duracion_decimal_descontada%', '', $row);
						$row = str_replace('%duracion_decimal_trabajada%', '', $row);
						$row = str_replace('%duracion_trabajada%', '', $row);
					}

					$row = str_replace('%duracion_decimal%', number_format($duracion_decimal, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
						$row = str_replace('%duracion%', number_format($duracion_decimal, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $row);
					} else {
						$row = str_replace('%duracion%', $h . ':' . $m, $row);
					}

					if ($this->fields['opc_ver_columna_cobrable']) {
						if ($trabajo->fields['cobrable'] == 1) {
							$row = str_replace('%cobrable%', __('<td align="center">Si</td>'), $row);
						} else {
							$row = str_replace('%cobrable%', __('<td align="center">No</td>'), $row);
						}
					} else
						$row = str_replace('%cobrable%', __(''), $row);


					$ImprimirValorTrabajo = Conf::GetConf($this->sesion, 'ImprimirValorTrabajo');

					if ($ImprimirValorTrabajo && $this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION') {
						$row = str_replace('%valor%', '', $row);
						$row = str_replace('%valor_cyc%', '', $row);
					} else {
						$row = str_replace('%valor%', number_format($trabajo->fields['monto_cobrado'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%valor_con_moneda%', $moneda->fields['simbolo'] . number_format($trabajo->fields['monto_cobrado'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%valor_cyc%', number_format($trabajo->fields['monto_cobrado'] * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					}
					$row = str_replace('%valor_siempre%', number_format($trabajo->fields['monto_cobrado'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

					if (Conf::GetConf($this->sesion, 'OrdenarPorCategoriaUsuario')) {
						$trabajo_siguiente = $lista_trabajos->Get($i + 1);
						if (!empty($trabajo_siguiente->fields['id_categoria_usuario'])) {
							if ($trabajo->fields['id_categoria_usuario'] != $trabajo_siguiente->fields['id_categoria_usuario']) {
								$html3 = $parser->tags['TRABAJOS_TOTAL'];
								$html3 = str_replace('%glosa%', __('Total'), $html3);
								$categoria_duracion_horas += floor($categoria_duracion_minutos / 60);
								$categoria_duracion_minutos = round($categoria_duracion_minutos % 60);
								$html3 = str_replace('%duracion%', sprintf('%02d:%02d', $categoria_duracion_horas, $categoria_duracion_minutos), $html3);
								$html3 = str_replace('%duracion_trabajada%', sprintf('%02d:%02d', floor($categoria_duracion_trabajada), round(($categoria_duracion_trabajada * 60) % 60)), $html3);
								$html3 = str_replace('%duracion_descontada%', sprintf('%02d:%02d', floor($categoria_duracion_descontada), round(($categoria_duracion_descontada * 60) % 60)), $html3);

								if (Conf::GetConf($this->sesion, 'NoImprimirValorTrabajo') && $this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION') {
									$html3 = str_replace('%valor%', '', $html3);
									$html3 = str_replace('%valor_cyc%', '', $html3);
								} else {
									$html3 = str_replace('%valor%', $moneda->fields['simbolo'] . $this->espacio . number_format($categoria_valor, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
									$html3 = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($categoria_valor * ( $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
								}

								$total_trabajos_categoria .= $html3;

								$encabezado_trabajos_categoria .= $this->GenerarDocumentoComun($parser, 'TRABAJOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto);

								$row = str_replace('%TRABAJOS_CATEGORIA%', $total_trabajos_categoria . $encabezado_trabajos_categoria, $row);
								$categoria_duracion_horas = 0;
								$categoria_duracion_minutos = 0;
								$categoria_valor = 0;
								$total_trabajos_categoria = '';
								$encabezado_trabajos_categoria = '';
							} else {
								$row = str_replace('%TRABAJOS_CATEGORIA%', '', $row);
							}
						} else {
							$html3 = $parser->tags['TRABAJOS_TOTAL'];
							$html3 = str_replace('%glosa%', __('Total'), $html3);
							$categoria_duracion_horas += floor($categoria_duracion_minutos / 60);
							$categoria_duracion_minutos = round($categoria_duracion_minutos % 60);

							$html3 = str_replace('%duracion%', sprintf('%02d:%02d', $categoria_duracion_horas, $categoria_duracion_minutos), $html3);
							$html3 = str_replace('%duracion_trabajada%', sprintf('%02d:%02d', floor($categoria_duracion_trabajada), round(($categoria_duracion_trabajada * 60) % 60)), $html3);
							$html3 = str_replace('%duracion_descontada%', sprintf('%02d:%02d', floor($categoria_duracion_descontada), round(($categoria_duracion_descontada * 60) % 60)), $html3);

							if ($this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION' && Conf::GetConf($this->sesion, 'NoImprimirValorTrabajo')) {
								$html3 = str_replace('%valor%', '', $html3);
								$html3 = str_replace('%valor_cyc%', '', $html3);
							} else {
								$html3 = str_replace('%valor%', $moneda->fields['simbolo'] . $this->espacio . number_format($categoria_valor, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
								$html3 = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($categoria_valor * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
							}

							$total_trabajos_categoria .= $html3;
							$row = str_replace('%TRABAJOS_CATEGORIA%', $total_trabajos_categoria, $row);
							$categoria_duracion_horas = 0;
							$categoria_duracion_minutos = 0;
							$categoria_valor = 0;
							$total_trabajos_categoria = '';
							$encabezado_trabajos_categoria = '';
						}
					} else if (Conf::GetConf($this->sesion, 'SepararPorUsuario')) {
						$trabajo_siguiente = $lista_trabajos->Get($i + 1);
						if (!empty($trabajo_siguiente->fields['nombre_usuario'])) {
							if ($trabajo->fields['nombre_usuario'] != $trabajo_siguiente->fields['nombre_usuario']) {
								$html3 = $parser->tags['TRABAJOS_TOTAL'];
								$html3 = str_replace('%glosa%', __('Subtotal'), $html3);
								$categoria_duracion_horas += floor($categoria_duracion_minutos / 60);
								$categoria_duracion_minutos = round($categoria_duracion_minutos % 60);
								$html3 = str_replace('%duracion%', sprintf('%02d:%02d', $categoria_duracion_horas, $categoria_duracion_minutos), $html3);

								if (Conf::GetConf($this->sesion, 'NoImprimirValorTrabajo') && $this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION') {
									$html3 = str_replace('%valor%', '', $html3);
									$html3 = str_replace('%valor_cyc%', '', $html3);
								} else {
									$html3 = str_replace('%valor%', $moneda->fields['simbolo'] . $this->espacio . number_format($categoria_valor, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
									$html3 = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($categoria_valor * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
								}

								if ($this->fields['opc_ver_detalles_por_hora_categoria'] == 1) {
									$html3 = str_replace('%td_categoria%', '<td>&nbsp;</td>', $html3);
								} else {
									$html3 = str_replace('%td_categoria%', '', $html3);
								}
								if ($this->fields['opc_ver_detalles_por_hora_tarifa'] == 1) {
									$html3 = str_replace('%td_tarifa%', '<td>&nbsp;</td>', $html3);
									$html3 = str_replace('%td_tarifa_ajustada%', '<td>&nbsp;</td>', $html3);
								} else {
									$html3 = str_replace('%td_tarifa%', '', $html3);
									$html3 = str_replace('%td_tarifa_ajustada%', '', $html3);
								}

								$html3 = $parser->tags['TRABAJOS_ENCABEZADO'];
								$html3 = str_replace('%duracion%', __('Duración'), $html3);
								$html3 = str_replace('%cobrable%', __('<td align="center" width="80">Cobrable</td>'), $html3);
								$html3 = str_replace('%fecha%', __('Fecha'), $html3);
								$html3 = str_replace('%descripcion%', __('Descripción'), $html3);
								$html3 = str_replace('%profesional%', __('Profesional'), $html3);
								$html3 = str_replace('%abogado%', __('Abogado'), $html3);
								$html3 = str_replace('%categoria_abogado%', __($trabajo_siguiente->fields['nombre_usuario']), $html3);
								$html3 = str_replace('%tarifa%', $moneda->fields['simbolo'] . $this->espacio . number_format($trabajo_siguiente->fields['tarifa_hh'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' / hr.', $html3);

								if ((Conf::GetConf($this->sesion, 'NoImprimirValorTrabajo')) && $this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION') {
									$html3 = str_replace('%valor%', '', $html3);
									$html3 = str_replace('%valor_cyc%', '', $html3);
								} else {
									$html3 = str_replace('%valor%', __('Valor'), $html3);
									$html3 = str_replace('%valor_cyc%', __('Valor'), $html3);
								}
								$encabezado_trabajos_categoria .= $html3;

								if ($this->fields['opc_ver_horas_trabajadas'] == 1) {
									$html3 = str_replace('%duracion_trabajada%', sprintf('%02d:%02d', floor($categoria_duracion_trabajada), round(($categoria_duracion_trabajada * 60) % 60)), $html3);
									$html3 = str_replace('%duracion_descontada%', sprintf('%02d:%02d', floor($categoria_duracion_descontada), round(($categoria_duracion_descontada * 60) % 60)), $html3);
								} else {
									$html3 = str_replace('%duracion_trabajada%', '', $html3);
									$html3 = str_replace('%duracion_descontada%', '', $html3);
								}

								$html3 = str_replace('%importe%', number_format($categoria_valor, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);

								$total_trabajos_categoria .= $html3;

								$encabezado_trabajos_categoria .= $this->GenerarDocumentoComun($parser, 'TRABAJOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto);

								$row = str_replace('%TRABAJOS_CATEGORIA%', $total_trabajos_categoria . $encabezado_trabajos_categoria, $row);
								$categoria_duracion_horas = 0;
								$categoria_duracion_minutos = 0;
								$categoria_duracion_trabajada = 0;
								$categoria_duracion_descontada = 0;
								$categoria_valor = 0;
								$total_trabajos_categoria = '';
								$encabezado_trabajos_categoria = '';
							} else {
								$row = str_replace('%TRABAJOS_CATEGORIA%', '', $row);
							}
						} else {
							$html3 = $parser->tags['TRABAJOS_TOTAL'];
							$html3 = str_replace('%glosa%', __('Subtotal'), $html3);
							$categoria_duracion_horas += floor($categoria_duracion_minutos / 60);
							$categoria_duracion_minutos = round($categoria_duracion_minutos % 60);

							$html3 = str_replace('%duracion%', sprintf('%02d:%02d', $categoria_duracion_horas, $categoria_duracion_minutos), $html3);

							if ($this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION' && Conf::GetConf($this->sesion, 'NoImprimirValorTrabajo')) {
								$html3 = str_replace('%valor%', '', $html3);
								$html3 = str_replace('%valor_cyc%', '', $html3);
							} else {
								$html3 = str_replace('%valor%', $moneda->fields['simbolo'] . $this->espacio . number_format($categoria_valor, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
								$html3 = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($categoria_valor * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
							}

							if ($this->fields['opc_ver_detalles_por_hora_categoria'] == 1) {
								$html3 = str_replace('%td_categoria%', '<td>&nbsp;</td>', $html3);
							} else {
								$html3 = str_replace('%td_categoria%', '', $html3);
							}

							if ($this->fields['opc_ver_detalles_por_hora_tarifa'] == 1) {
								$html3 = str_replace('%td_tarifa%', '<td>&nbsp;</td>', $html3);
								$html3 = str_replace('%td_tarifa_ajustada%', '<td>&nbsp;</td>', $html3);
							} else {
								$html3 = str_replace('%td_tarifa%', '', $html3);
								$html3 = str_replace('%td_tarifa_ajustada%', '', $html3);
							}

							if ($this->fields['opc_ver_detalles_por_hora_importe'] == 1) {
								$html3 = str_replace('%td_importe%', '<td align="right">%importe%</td>', $html3);
								$html3 = str_replace('%td_importe_ajustado%', '<td align="right">%importe_ajustado%</td>', $html3);
							} else {
								$html3 = str_replace('%td_importe%', '', $html3);
								$html3 = str_replace('%td_importe_ajustado%', '', $html3);
							}

							if ($this->fields['opc_ver_horas_trabajadas'] == 1) {
								$html3 = str_replace('%duracion_trabajada%', sprintf('%02d:%02d', floor($categoria_duracion_trabajada), round(($categoria_duracion_trabajada * 60) % 60)), $html3);
								$html3 = str_replace('%duracion_descontada%', sprintf('%02d:%02d', floor($categoria_duracion_descontada), round(($categoria_duracion_descontada * 60) % 60)), $html3);
							} else {
								$html3 = str_replace('%duracion_trabajada%', '', $html3);
								$html3 = str_replace('%duracion_descontada%', '', $html3);
							}

							$html3 = str_replace('%importe%', number_format($categoria_valor, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);

							$total_trabajos_categoria .= $html3;
							$row = str_replace('%TRABAJOS_CATEGORIA%', $total_trabajos_categoria, $row);
							$categoria_duracion_horas = 0;
							$categoria_duracion_minutos = 0;
							$categoria_duracion_trabajada = 0;
							$categoria_duracion_descontada = 0;
							$categoria_valor = 0;
							$total_trabajos_categoria = '';
							$encabezado_trabajos_categoria = '';
						}
					} else {
						$row = str_replace('%TRABAJOS_CATEGORIA%', '', $row);
					}
					$html .= $row;
				}
				break;


			case 'TRABAJOS_TOTAL': //GenerarDocumentoComun
				if ($this->fields['estado'] == 'CREADO' || $this->fields['estado'] == 'EN REVISION') {
					$html = str_replace('%td_id_trabajo%', '<td align="center">%ntrabajo%</td>', $html);
				} else {
					$html = str_replace('%td_id_trabajo%', '', $html);
				}
				$html = str_replace('%ntrabajo%', __('&nbsp;'), $html);

				$ImprimirDuracionTrabajada = Conf::GetConf($this->sesion, 'ImprimirDuracionTrabajada');

				$duracion_trabajada_total = ($asunto->fields['trabajos_total_duracion_trabajada']) / 60;
				$duracion_cobrada_total = ($asunto->fields['trabajos_total_duracion']) / 60;
				$duracion_retainer_total = ($asunto->fields['trabajos_total_duracion_retainer']) / 60;
				$duracion_descontada_total = $duracion_trabajada_total - $duracion_cobrada_total;
				$duracion_sobre_retainer_total = $duracion_cobrada_total - $duracion_retainer_total;
				if ($this->fields['opc_ver_solicitante']) {
					$html = str_replace('%td_solicitante%', '<td>&nbsp;</td>', $html);
				} else {
					$html = str_replace('%td_solicitante%', '', $html);
				}
				if ($this->fields['opc_ver_detalles_por_hora_categoria'] == 1)
					$html = str_replace('%td_categoria%', '<td>&nbsp;</td>', $html);
				else
					$html = str_replace('%td_categoria%', '', $html);

				if ($this->fields['opc_ver_detalles_por_hora_tarifa'] == 1) {
					$html = str_replace('%td_tarifa%', '<td>&nbsp;</td>', $html);
					$html = str_replace('%td_tarifa_ajustada%', '<td>&nbsp;</td>', $html);
				} else {
					$html = str_replace('%td_tarifa%', '', $html);
					$html = str_replace('%td_tarifa_ajustada%', '', $html);
				}

				if ($this->fields['opc_ver_detalles_por_hora_importe'] == 1) {
					$html = str_replace('%td_importe%', '<td align="right">%importe%</td>', $html);
					$html = str_replace('%td_importe_ajustado%', '<td align="right">%importe_ajustado%</td>', $html);
				} else {
					$html = str_replace('%td_importe%', '', $html);
					$html = str_replace('%td_importe_ajustado%', '', $html);
				}
				$html = str_replace('%importe%', number_format($asunto->fields['trabajos_total_importe'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%importe_ajustado%', number_format($asunto->fields['trabajos_total_importe'] * $x_factor_ajuste, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				if ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$html = str_replace('%td_retainer%', '<td align="center">%duracion_retainer%</td>', $html);
					$html = str_replace('%td_sobre_retainer%', '<td align="center">%duracion_retainer%</td>', $html);
					if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
						$html = str_replace('%duracion_retainer%', number_format($duracion_retainer_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html);
						$html = str_replace('%duracion_sobre_retainer%', number_format($duracion_sobre_retainer_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html);
					} else {
						$html = str_replace('%duracion_retainer%', Utiles::Decimal2GlosaHora($duracion_retainer_total), $html);
						$html = str_replace('%duracion_sobre_retainer%', Utiles::Decimal2GlosaHora($duracion_sobre_retainer_total), $html);
					}
				} else {
					$html = str_replace('%duracion_sobre_retainer%', '%duracion%', $html);
					$html = str_replace('%td_retainer%', '', $html);
					$html = str_replace('%td_sobre_retainer%', '', $html);
				}

				if ($this->fields['forma_cobro'] == 'FLAT FEE') {
					$html = str_replace('%duracion_decimal_trabajada%', '', $html);
					$html = str_replace('%duracion_trabajada%', '', $html);
					$html = str_replace('%duracion_descontada%', '', $html);
					$html = str_replace('%duracion_decimal_descontada%', '', $html);
					if ($this->fields['opc_ver_horas_trabajadas']) {
						$html = str_replace('%duracion_decimal%', number_format($duracion_trabajada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
						if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
							$html = str_replace('%duracion%', number_format($duracion_trabajada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html);
						} else {
							$html = str_replace('%duracion%', Utiles::Decimal2GlosaHora($duracion_trabajada_total), $html);
						}
					} else {
						$html = str_replace('%duracion_decimal%', number_format($duracion_cobrada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
						if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
							$html = str_replace('%duracion%', number_format($duracion_cobrada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html);
						} else {
							$html = str_replace('%duracion%', Utiles::Decimal2GlosaHora($duracion_cobrada_total), $html);
						}
					}
				}
				if ($ImprimirDuracionTrabajada && ( $this->fields['estado'] == 'CREADO' || $this->fields['estado'] == 'EN REVISION' )) {
					$html = str_replace('%duracion_decimal_trabajada%', number_format($duracion_trabajada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					$html = str_replace('%duracion_decimal_descontada%', number_format($duracion_descontada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
						$html = str_replace('%duracion_trabajada%', number_format($duracion_trabajada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html);
						$html = str_replace('%duracion_descontada%', number_format($duracion_descontada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html);
					} else {
						$html = str_replace('%duracion_trabajada%', Utiles::Decimal2GlosaHora($duracion_trabajada_total), $html);
						$html = str_replace('%duracion_descontada%', Utiles::Decimal2GlosaHora($duracion_descontada_total), $html);
					}
				} else if ($this->fields['opc_ver_horas_trabajadas']) {
					$html = str_replace('%duracion_decimal_trabajada%', number_format($duracion_trabajada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					$html = str_replace('%duracion_decimal_descontada%', number_format($duracion_descontada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
						$html = str_replace('%duracion_trabajada%', number_format($duracion_trabajada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html);
						$html = str_replace('%duracion_descontada%', number_format($duracion_descontada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html);
					} else {
						$html = str_replace('%duracion_trabajada%', Utiles::Decimal2GlosaHora($duracion_trabajada_total), $html);
						$html = str_replace('%duracion_descontada%', Utiles::Decimal2GlosaHora($duracion_descontada_total), $html);
					}
				} else {
					$html = str_replace('%duracion_decimal_trabajada%', '', $html);
					$html = str_replace('%duracion_trabajada%', '', $html);
					$html = str_replace('%duracion_descontada%', '', $html);
					$html = str_replace('%duracion_decimal_descontada%', '', $html);
				}

				$html = str_replace('%glosa%', __('Total Trabajos'), $html);
				$html = str_replace('%duracion_decimal%', number_format($duracion_cobrada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
					$html = str_replace('%duracion%', number_format($duracion_cobrada_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html);
				} else {
					$html = str_replace('%duracion%', Utiles::Decimal2GlosaHora($duracion_cobrada_total), $html);
				}


				if ($this->fields['opc_ver_columna_cobrable'] == 1)
					$html = str_replace('%cobrable%', __('<td>&nbsp;</td>'), $html);
				else
					$html = str_replace('%cobrable%', __(''), $html);

				$ImprimirValorTrabajo = Conf::GetConf($this->sesion, 'ImprimirValorTrabajo');

				$aproximacion_monto_cyc = number_format($this->fields['monto_subtotal'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$subtotal_en_moneda_cyc = $aproximacion_monto_cyc * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']);


				if ($ImprimirValorTrabajo && $this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION') {
					$html = str_replace('%valor%', '', $html);
					$html = str_replace('%valor_cyc%', '', $html);
				} else {
					$html = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($subtotal_en_moneda_cyc, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					$html = str_replace('%valor%', $moneda->fields['simbolo'] . $this->espacio . number_format($asunto->fields['trabajos_total_valor'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}

				$html = str_replace('%valor_siempre%', $moneda->fields['simbolo'] . $this->espacio . number_format($asunto->fields['trabajos_total_valor'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%total_raz%', __('total_raz'), $html);

				break;

			case 'DETALLE_PROFESIONAL': //GenerarDocumentoComun

				if ($this->fields['opc_ver_profesional'] == 0) {
					return '';
				}

				$html = str_replace('%glosa_profesional%', __('Detalle profesional'), $html);
				$html = str_replace('%detalle_tiempo_por_abogado%', __('Detalle tiempo por abogado'), $html);
				$html = str_replace('%detalle_honorarios%', __('Detalle de honorarios profesionales'), $html);
				$html = str_replace('%PROFESIONAL_ENCABEZADO%', $this->GenerarSeccionResumenProfesional($parser, 'PROFESIONAL_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%PROFESIONAL_FILAS%', $this->GenerarDocumentoComun($parser, 'PROFESIONAL_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%PROFESIONAL_TOTAL%', $this->GenerarDocumentoComun($parser, 'PROFESIONAL_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);

				if ($this->fields['opc_ver_descuento']) {
					$html = str_replace('%DETALLE_COBRO_DESCUENTO%', $this->GenerarDocumentoComun($parser, 'DETALLE_COBRO_DESCUENTO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					$html = str_replace('%DETALLE_COBRO_DESCUENTO_NUEVO%', $this->GenerarDocumentoComun($parser, 'DETALLE_COBRO_DESCUENTO_NUEVO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%DETALLE_COBRO_DESCUENTO%', '', $html);
					$html = str_replace('%DETALLE_COBRO_DESCUENTO_NUEVO%', '', $html);
				}

				if (count($this->asuntos) > 1) {
					$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL_POR_ASUNTO%', $this->GenerarDocumentoComun($parser, 'DETALLE_COBRO_MONEDA_TOTAL_POR_ASUNTO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL%', '', $html);
				} else {
					$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL%', $this->GenerarDocumentoComun($parser, 'DETALLE_COBRO_MONEDA_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL_POR_ASUNTO%', '', $html);
				}
				break;

			case 'IMPUESTO': //GenerarDocumentoComun
				$html = str_replace('%impuesto%', __('Impuesto') . ' (' . $this->fields['porcentaje_impuesto'] . '%)', $html);
				$html = str_replace('%impuesto_mta%', __('Impuesto') . ' (' . $this->fields['porcentaje_impuesto'] . '% )', $html);

				if ($this->fields['tipo_cambio_moneda_base'] <= 0) {
					$tipo_cambio_cobro_moneda_base = 1;
				} else {
					$tipo_cambio_cobro_moneda_base = $this->fields['tipo_cambio_moneda_base'];
				}

				$aproximacion_impuesto = number_format($this->fields['impuesto'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$impuesto_moneda_total = $aproximacion_impuesto * ($this->fields['tipo_cambio_moneda'] / $tipo_cambio_cobro_moneda_base) / ($tipo_cambio_moneda_total / $tipo_cambio_cobro_moneda_base) + $this->fields['impuesto_gastos'];
				$html = str_replace('%valor_impuesto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($impuesto_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				// Muñoz y Tamayo
				$impuesto_solo_honorarios = $x_resultados['monto_iva_hh'][$this->fields['opc_moneda_total']];

				$html = str_replace('%valor_impuesto_honorarios%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($impuesto_solo_honorarios, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			case 'PROFESIONAL_FILAS': //GenerarDocumentoComun
				$row_tmpl = $html;
				$html = '';

				if (is_array($profesionales)) {
					$retainer = false;
					$descontado = false;
					$flatfee = false;

					// Para mostrar un resumen de horas de cada profesional al principio del documento.
					global $resumen_profesional_nombre;
					global $resumen_profesional_hrs_trabajadas;
					global $resumen_profesional_hrs_retainer;
					global $resumen_profesional_hrs_descontadas;
					global $resumen_profesional_hh;
					global $resumen_profesional_valor_hh;
					global $resumen_profesional_categoria;
					global $resumen_profesional_id_categoria;
					global $resumen_profesionales;

					foreach ($profesionales as $prof => $data) {
						if ($data['retainer'] > 0)
							$retainer = true;
						if ($data['descontado'] > 0)
							$descontado = true;
						if ($data['flatfee'] > 0)
							$flatfee = true;
					}

					// Si el conf lo indica, ordenamos los profesionales por categoría.
					if (Conf::GetConf($this->sesion, 'OrdenarPorTarifa')) {
						foreach ($profesionales as $prof => $data) {
							$tarifa_profesional[$prof] = $data['tarifa'];
						}
						if (sizeof($tarifa_profesional) > 0)
							array_multisort($tarifa_profesional, SORT_DESC, $profesionales);
					} else if (Conf::GetConf($this->sesion, 'OrdenarPorFechaCategoria')) {
						foreach ($profesionales as $prof => $data) {
							$categoria[$prof] = $data['id_categoria_usuario'];
						}
						if (sizeof($categoria) > 0)
							array_multisort($categoria, SORT_ASC, $profesionales);
					}
					foreach ($profesionales as $prof => $data) {
						// Para mostrar un resumen de horas de cada profesional al principio del documento.
						for ($k = 0; $k < count($resumen_profesional_nombre); ++$k)
							if ($resumen_profesional_nombre[$k] == $prof)
								break;
						$totales['valor'] += $data['valor'];
						//se pasan los minutos a horas:minutos
						$horas_trabajadas_real = floor(($data['tiempo_trabajado_real']) / 60);
						$minutos_trabajadas_real = sprintf("%02d", $data['tiempo_trabajado_real'] % 60);
						$horas_trabajadas = floor(($data['tiempo_trabajado']) / 60);
						$minutos_trabajadas = sprintf("%02d", $data['tiempo_trabajado'] % 60);
						$horas_descontado_real = floor(($data['descontado_real']) / 60);
						$minutos_descontado_real = sprintf("%02d", $data['descontado_real'] % 60);
						$horas_descontado = floor(($data['descontado']) / 60);
						$minutos_descontado = sprintf("%02d", $data['descontado'] % 60);
						$horas_retainer = floor(($data['retainer']) / 60);
						$minutos_retainer = sprintf("%02d", $data['retainer'] % 60);
						$segundos_retainer = sprintf("%02d", round(60 * ($data['retainer'] - floor($data['retainer']))));

						$horas_flatfee = floor(($data['flatfee']) / 60);
						$minutos_flatfee = sprintf("%02d", $data['flatfee'] % 60);
						if ($retainer) {
							$totales['tiempo_retainer'] += $data['retainer'];
							$totales['tiempo_trabajado'] += $data['tiempo_trabajado'];
							if ($data['tiempo_trabajado'] > $data['tiempo_trabajado_real'])
								$totales['tiempo_trabajado_real'] += $data['tiempo_trabajado'];
							else
								$totales['tiempo_trabajado_real'] += $data['tiempo_trabajado_real'];

							$totales['tiempo'] += $data['tiempo'] - $data['retainer'];
							$horas_cobrables = floor(($data['tiempo']) / 60) - $horas_retainer;
							$minutos_cobrables = sprintf("%02d", ($data['tiempo'] % 60) - $minutos_retainer);
							if ($this->fields['forma_cobro'] == 'PROPORCIONAL') {
								$segundos_cobrables = sprintf("%02d", 60 - $segundos_retainer);
								--$minutos_cobrables;
							}
							if ($minutos_cobrables < 0) {
								--$horas_cobrables;
								$minutos_cobrables += 60;
							}
						} else {
							$totales['tiempo'] += $data['tiempo'];
							$totales['tiempo_trabajado'] += $data['tiempo_trabajado'];
							if ($data['tiempo_trabajado'] > $data['tiempo_trabajado_real'])
								$totales['tiempo_trabajado_real'] += $data['tiempo_trabajado'];
							else
								$totales['tiempo_trabajado_real'] += $data['tiempo_trabajado_real'];
							$horas_cobrables = floor(($data['tiempo']) / 60);
							$minutos_cobrables = sprintf("%02d", $data['tiempo'] % 60);
						}
						if ($flatfee) {
							$totales['tiempo_flatfee'] += $data['flatfee'];
						}
						if ($descontado || $this->fields['opc_ver_horas_trabajadas']) {
							$totales['tiempo_descontado'] += $data['descontado'];
							if ($data['descontado_real'] >= 0)
								$totales['tiempo_descontado_real'] += $data['descontado_real'];
						}
						$row = $row_tmpl;
						$row = str_replace('%nombre%', $prof, $row);

						if (!$asunto->fields['cobrable']) {
							$row = str_replace('%hrs_retainer%', '', $row);
							$row = str_replace('%hrs_descontadas%', '', $row);
							$row = str_replace('%hrs_descontadas_real%', '', $row);
							$row = str_replace('%hh%', '', $row);
							$row = str_replace('%valor_hh%', '', $row);
							$row = str_replace('%valor_hh_cyc%', '', $row);
						}

						//muestra las iniciales de los profesionales
						//Las iniciales fueron reemplazas por el username. Pivotal: 109198728
						$row = str_replace('%iniciales%', $data['username'], $row);
						$row = str_replace('%username%', $data['username'], $row);

						if ($descontado || $retainer || $flatfee) {
							if ($this->fields['opc_ver_horas_trabajadas']) {
								if ($horas_descontado_real < 0 || substr($minutos_descontado_real, 0, 1) == '-') {
									$row = str_replace('%hrs_trabajadas_real%', $horas_cobrables . ':' . $minutos_cobrables, $row);
									$row = str_replace('%hrs_descontadas_real%', '0:00', $row);
								} else {
									$row = str_replace('%hrs_trabajadas_real%', $horas_trabajadas_real . ':' . $minutos_trabajadas_real, $row);
									$row = str_replace('%hrs_descontadas_real%', $horas_descontado_real . ':' . $minutos_descontado_real, $row);
								}
							} else {
								$row = str_replace('%hrs_trabajadas_real%', '', $row);
								$row = str_replace('%hrs_descontadas_real%', '', $row);
							}
							$row = str_replace('%hrs_trabajadas%', $horas_trabajadas . ':' . $minutos_trabajadas, $row);
							//$resumen_profesional_hrs_trabajadas[$k] += $horas_trabajadas + $minutos_trabajadas/60;
						} else if ($this->fields['opc_ver_horas_trabajadas']) {
							if ($horas_descontado_real < 0 || substr($minutos_descontado_real, 0, 1) == '-') {
								$row = str_replace('%hrs_trabajadas_real%', $horas_cobrables . ':' . $minutos_cobrables, $row);
								$row = str_replace('%hrs_descontadas_real%', '0:00', $row);
							} else {
								$row = str_replace('%hrs_trabajadas_real%', $horas_trabajadas_real . ':' . $minutos_trabajadas_real, $row);
								$row = str_replace('%hrs_descontadas_real%', $horas_descontado_real . ':' . $minutos_descontado_real, $row);
							}
							$row = str_replace('%hrs_trabajadas%', $horas_trabajadas . ':' . $minutos_trabajadas, $row);
						} else {
							$row = str_replace('%hrs_trabajadas%', '', $row);
							$row = str_replace('%hrs_trabajadas_real%', '', $row);
						}
						if ($retainer) {
							if ($data['retainer'] > 0) {
								if ($this->fields['forma_cobro'] == 'PROPORCIONAL') {
									$minutos_retainer_redondeados = sprintf("%02d", $minutos_retainer + round($segundos_retainer / 60));
									$row = str_replace('%hrs_retainer%', $horas_retainer . ':' . $minutos_retainer_redondeados, $row);
									$resumen_profesional_hrs_retainer[$k] += $horas_retainer + $minutos_retainer / 60 + $segundos_retainer / 3600;
								} else { // retainer simple, no imprime segundos
									$row = str_replace('%hrs_retainer%', $horas_retainer . ':' . $minutos_retainer, $row);
									$resumen_profesional_hrs_retainer[$k] += $horas_retainer + $minutos_retainer / 60;
								}
								$minutos_retainer_decimal = $minutos_retainer / 60;
								$duracion_retainer_decimal = $horas_retainer + $minutos_retainer_decimal;
								$row = str_replace('%horas_retainer%', number_format($duracion_retainer_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							} else {
								$row = str_replace('%hrs_retainer%', '-', $row);
								$row = str_replace('%horas_retainer%', '', $row);
							}
						} else {
							if ($flatfee) {
								if ($data['flatfee'] > 0) {
									$row = str_replace('%hrs_retainer%', $horas_flatfee . ':' . $minutos_flatfee, $row);
									$resumen_profesional_hrs_retainer[$k] += $horas_flatfee + $minutos_flatfee / 60;
								} else
									$row = str_replace('%hrs_retainer%', '', $row);
							}
							$row = str_replace('%hrs_retainer%', '', $row);
							$row = str_replace('%horas_retainer%', '', $row);
						}

						if ($descontado) {
							$row = str_replace('%columna_horas_no_cobrables%', '<td align="center" width="65">%hrs_descontado%</td>', $row);
							if ($data['descontado'] > 0) {
								$row = str_replace('%hrs_descontadas%', $horas_descontado . ':' . $minutos_descontado, $row);
								$resumen_profesional_hrs_descontadas[$k] += $horas_descontado + $minutos_descontado / 60;
							} else
								$row = str_replace('%hrs_descontadas%', '-', $row);
							if ($data['descontado_real'] > 0) {
								$row = str_replace('%hrs_descontadas_real%', $horas_descontado_real . ':' . $minutos_descontado_real, $row);
							} else
								$row = str_replace('hrs_descontadas_real%', '-', $row);
						} else {
							$row = str_replace('%columna_horas_no_cobrables%', '', $row);
							$row = str_replace('%hrs_descontadas_real%', '', $row);
							$row = str_replace('%hrs_descontadas%', '', $row);
						}

						if ($flatfee) {
							$row = str_replace('%hh%', '0:00', $row);
						} else {
							if ($this->fields['forma_cobro'] == 'PROPORCIONAL') {
								$minutos_cobrables_redondeados = sprintf("%02d", $minutos_cobrables + round($segundos_cobrables / 60));
								$row = str_replace('%hh%', "$horas_cobrables:$minutos_cobrables_redondeados", $row);
							} else // Otras formas de cobro, no imprime segundos
								$row = str_replace('%hh%', $horas_cobrables . ':' . sprintf("%02d", $minutos_cobrables), $row);
						}

						$row = str_replace('%valor_hh%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($data['tarifa'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%valor_hh_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($data['tarifa'] * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%total%', $moneda->fields['simbolo'] . $this->espacio . number_format($data['valor'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%total_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($data['valor'] * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

						$row = str_replace('%hrs_trabajadas_previo%', '', $row);
						$row = str_replace('%horas_trabajadas_especial%', '', $row);
						$row = str_replace('%horas_cobrables%', '', $row);

						#horas en decimal

						$minutos_decimal = $minutos_cobrables / 60;
						$duracion_decimal = $horas_cobrables + $minutos_decimal;

						$row = str_replace('%horas%', number_format($duracion_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

						if ($this->fields['opc_ver_profesional_tarifa'] == 1) {
							$row = str_replace('%tarifa_horas%', $flatfee ? '' : number_format($data['tarifa'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						} else {
							$row = str_replace('%tarifa_horas%', '', $row);
						}

						$row = str_replace('%total_horas%', $flatfee ? '' : number_format($data['valor'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

						if ($this->fields['opc_ver_horas_trabajadas'] && $horas_trabajadas_real . ':' . $minutos_trabajadas != '0:00') {
							$html .= $row;
						} else if ($horas_trabajadas . ':' . $minutos_trabajadas != '0:00') {
							$html .= $row;
						}

						$resumen_profesional_hh[$k] += $horas_cobrables + $minutos_cobrables / 60;

						// Se usan solo para el cobro prorrateado.
						if ($segundos_cobrables) {
							$resumen_profesional_hh[$k] += $segundos_cobrables / 3600;
						}
						if ($flatfee) {
							$resumen_profesional_hh[$k] = 0;
						}
					}
				}
				break;

			case 'PROFESIONAL_TOTAL': //GenerarDocumentoComun

				$retainer = false;
				$descontado = false;
				$flatfee = false;
				if (is_array($profesionales)) {
					foreach ($profesionales as $prof => $data) {
						if ($data['retainer'] > 0) {
							$retainer = true;
						}
						if ($data['descontado'] > 0) {
							$descontado = true;
						}
						if ($data['flatfee'] > 0) {
							$flatfee = true;
						}
					}
				}

				if (!$asunto->fields['cobrable']) {
					$html = str_replace('%hrs_retainer%', '', $html);
					$html = str_replace('%hrs_descontadas%', '', $html);
					$html = str_replace('%hrs_descontadas_real%', '', $html);
					$html = str_replace('%hh%', '', $html);
					$html = str_replace('%valor_hh%', '', $html);
					$html = str_replace('%valor_hh_cyc%', '', $html);
				}

				$horas_cobrables = floor(($totales['tiempo']) / 60);
				$minutos_cobrables = sprintf("%02d", $totales['tiempo'] % 60);
				$segundos_cobrables = round(60 * ($totales['tiempo'] - floor($totales['tiempo'])));
				$horas_trabajadas = floor(($totales['tiempo_trabajado']) / 60);
				$minutos_trabajadas = sprintf("%02d", $totales['tiempo_trabajado'] % 60);
				$horas_trabajadas_real = floor(($totales['tiempo_trabajado_real']) / 60);
				$minutos_trabajadas_real = sprintf("%02d", $totales['tiempo_trabajado_real'] % 60);
				$horas_retainer = floor(($totales['tiempo_retainer']) / 60);
				$minutos_retainer = sprintf("%02d", $totales['tiempo_retainer'] % 60);
				$segundos_retainer = sprintf("%02d", round(60 * ($totales['tiempo_retainer'] - floor($totales['tiempo_retainer']))));
				$horas_flatfee = floor(($totales['tiempo_flatfee']) / 60);
				$minutos_flatfee = sprintf("%02d", $totales['tiempo_flatfee'] % 60);
				$horas_descontado = floor(($totales['tiempo_descontado']) / 60);
				$minutos_descontado = sprintf("%02d", $totales['tiempo_descontado'] % 60);
				$horas_descontado_real = floor(($totales['tiempo_descontado_real']) / 60);
				$minutos_descontado_real = sprintf("%02d", $totales['tiempo_descontado_real'] % 60);
				$html = str_replace('%glosa%', __('Total'), $html);
				$html = str_replace('%glosa_honorarios%', __('Total Honorarios'), $html);

				if ($descontado || $retainer || $flatfee) {
					if ($this->fields['opc_ver_horas_trabajadas']) {
						$html = str_replace('%hrs_trabajadas_real%', $horas_trabajadas_real . ':' . $minutos_trabajadas_real, $html);
						$html = str_replace('%hrs_descontadas_real%', $horas_descontado_real . ':' . $minutos_descontado_real, $html);
					} else {
						$html = str_replace('%hrs_trabajadas_real%', '', $html);
						$html = str_replace('%hrs_descontadas_real%', '', $html);
					}
					$html = str_replace('%hrs_trabajadas%', $horas_trabajadas . ':' . $minutos_trabajadas, $html);
				} else if ($this->fields['opc_ver_horas_trabajadas']) {
					$html = str_replace('%hrs_trabajadas%', $horas_trabajadas . ':' . $minutos_trabajadas, $html);
					$html = str_replace('%hrs_trabajadas_real%', $horas_trabajadas_real . ':' . $minutos_trabajadas_real, $html);
					$html = str_replace('%hrs_descontadas_real%', $horas_descontado_real . ':' . $minutos_descontado_real, $html);
				} else {
					$html = str_replace('%hrs_trabajadas%', '', $html);
					$html = str_replace('%hrs_trabajadas_real%', '', $html);
				}

				$html = str_replace('%hrs_trabajadas_previo%', '', $html);
				$html = str_replace('%horas_trabajadas_especial%', '', $html);
				$html = str_replace('%horas_cobrables%', '', $html);

				if ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL')
					$html = str_replace('%DETALLE_PROFESIONAL_RETAINER%', $this->GenerarDocumentoComun($parser, 'DETALLE_PROFESIONAL_RETAINER', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				else
					$html = str_replace('%DETALLE_PROFESIONAL_RETAINER%', '', $html);

				if ($retainer) {
					if ($this->fields['forma_cobro'] == 'PROPORCIONAL') {
						$minutos_retainer_redondeados = sprintf("%02d", $minutos_retainer + round($segundos_retainer / 60));
						$html = str_replace('%hrs_retainer%', $horas_retainer . ':' . $minutos_retainer_redondeados, $html);
					} else // retainer simple, no imprime segundos
						$html = str_replace('%hrs_retainer%', $horas_retainer . ':' . $minutos_retainer, $html);
					$minutos_retainer_decimal = $minutos_retainer / 60;
					$duracion_retainer_decimal = $horas_retainer + $minutos_retainer_decimal;
					$html = str_replace('%horas_retainer%', number_format($duracion_retainer_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}
				else {
					$html = str_replace('%horas_retainer%', '', $html);
					if ($flatfee)
						$html = str_replace('%hrs_retainer%', $horas_flatfee . ':' . $minutos_flatfee, $html);
					else
						$html = str_replace('%hrs_retainer%', '', $html);
				}
				if ($descontado) {
					$html = str_replace('%columna_horas_no_cobrables%', '<td align="center" width="65">%hrs_descontadas%</td>', $html);
					$html = str_replace('%hrs_descontadas_real%', $horas_descontadas_real . ':' . $minutos_descontadas_real, $html);
					$html = str_replace('%hrs_descontadas%', $horas_descontado . ':' . $minutos_descontado, $html);
				} else {
					$html = str_replace('%columna_horas_no_cobrables%', '', $html);
					$html = str_replace('%hrs_descontadas_real%', '', $html);
					$html = str_replace('%hrs_descontadas%', '', $html);
				}
				if ($flatfee)
					$html = str_replace('%hh%', '0:00', $html);
				else
				if ($this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$minutos_cobrables_redondeados = sprintf("%02d", $minutos_cobrables + round($segundos_cobrables / 60));
					$html = str_replace('%hh%', "$horas_cobrables:$minutos_cobrables_redondeados", $html);
				} else // retainer simple, no imprime segundos
					$html = str_replace('%hh%', $horas_cobrables . ':' . sprintf("%02d", $minutos_cobrables), $html);

				$aproximacion_monto_cyc = number_format($this->fields['monto_subtotal'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$subtotal_en_moneda_cyc = $aproximacion_monto_cyc * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']);

				$html = str_replace('%total%', $moneda->fields['simbolo'] . $this->espacio . number_format($totales['valor'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%total_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($subtotal_en_moneda_cyc, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				#horas en decimal
				$minutos_decimal = $minutos_cobrables / 60;
				$duracion_decimal = $horas_cobrables + $minutos_decimal;

				$html = str_replace('%total_honorarios%', $flatfee ? $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto_subtotal'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) : $moneda->fields['simbolo'] . number_format($totales['valor'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%horas%', number_format($duracion_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			case 'DETALLE_PROFESIONAL_RETAINER': //GenerarDocumentoComun
				$html = str_replace('%retainer%', __('Retainer'), $html);
				$html = str_replace('%valor_retainer%', $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['simbolo'] . $this->espacio . number_format($this->fields['monto_contrato'], $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			case 'DETALLE_COBRO_MONEDA_TOTAL_POR_ASUNTO': //GenerarDocumentoComun
				if ($this->fields['opc_moneda_total'] == $this->fields['id_moneda'])
					return '';

				//valor en moneda previa selección para impresión
				if ($this->fields['tipo_cambio_moneda_base'] <= 0) {
					$tipo_cambio_cobro_moneda_base = 1;
				} else {
					$tipo_cambio_cobro_moneda_base = $this->fields['tipo_cambio_moneda_base'];
				}

				$aproximacion_monto = number_format($totales['valor'], $moneda->fields['cifras_decimales'], '.', '');
				$total_en_moneda = $aproximacion_monto * ($this->fields['tipo_cambio_moneda'] / $tipo_cambio_cobro_moneda_base) / ($tipo_cambio_moneda_total / $tipo_cambio_cobro_moneda_base);

				$html = str_replace('%valor_honorarios_monedabase%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . '&nbsp;' . number_format($total_en_moneda, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			/*
			  GASTOS -> esto s?lo lista los gastos agregados al cobro obteniendo un total
			 */
			case 'GASTOS': //GenerarDocumentoComun
				if ($this->fields['opc_ver_gastos'] == 0)
					return '';

				$html = str_replace('%glosa_gastos%', __('Gastos'), $html);
				$html = str_replace('%expenses%', __('%expenses%'), $html); //en vez de Disbursements es Expenses en ingl?s
				$html = str_replace('%detalle_gastos_raz%', __('detalledegastos'), $html);
				if ($lang == 'es') {
					$html = str_replace('%factura%', __('Factura'), $html);
				} else {
					$html = str_replace('%factura%', __('Factura'), $html);
				}
				$html = str_replace('%detalle_gastos%', __('Detalle de gastos'), $html);

				$html = str_replace('%GASTOS_ENCABEZADO%', $this->GenerarDocumentoComun($parser, 'GASTOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%GASTOS_FILAS%', $this->GenerarDocumentoComun($parser, 'GASTOS_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%GASTOS_TOTAL%', $this->GenerarDocumentoComun($parser, 'GASTOS_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				break;

			case 'GASTOS_ENCABEZADO': //GenerarDocumentoComun
				$html = str_replace('%glosa_gastos%', __('Gastos'), $html);
				$html = str_replace('%descripcion_gastos%', __('Descripción de Gastos'), $html);
				$html = str_replace('%fecha%', __('Fecha'), $html);
				$html = str_replace('%num_doc%', __('N° Documento'), $html);
				$html = str_replace('%tipo_gasto%', __('Tipo'), $html);
				$html = str_replace('%descripcion%', __('Descripción'), $html);
				$html = str_replace('%monto_original%', __('Monto'), $html);
				$html = str_replace('%monto_moneda_total%', __('Monto') . ' (' . $moneda_total->fields['simbolo'] . ')', $html);
				$html = str_replace('%ordenado_por%', __('Ordenado<br>Por'), $html);
				if ($lang == 'es') {
					$html = str_replace('%asunto_id%', __('ID<br>Asunto'), $html);
				} else {
					$html = str_replace('%asunto_id%', __('Matter<br>ID'), $html);
				}

				$html = str_replace('%monto_impuesto_total%', '', $html);
				$html = str_replace('%monto_moneda_total_con_impuesto%', '', $html);

				if (Conf::GetConf($this->sesion, 'MostrarProveedorenGastos')) {
					$html = str_replace('%proveedor%', __('Proveedor'), $html);
				} else {
					$html = str_replace('%proveedor%', '', $html);
				}

				break;

			case 'GASTOS_FILAS': //GenerarDocumentoComun
				$row_tmpl = $html;
				$html = '';
				if (method_exists('Conf', 'SepararGastosPorAsunto') && Conf::SepararGastosPorAsunto()) {
					$where_gastos_asunto = " AND codigo_asunto='" . $asunto->fields['codigo_asunto'] . "'";
				} else {
					$where_gastos_asunto = "";
				}
				$query = "SELECT SQL_CALC_FOUND_ROWS *, prm_cta_corriente_tipo.glosa AS tipo_gasto
								FROM cta_corriente
								LEFT JOIN prm_cta_corriente_tipo ON cta_corriente.id_cta_corriente_tipo=prm_cta_corriente_tipo.id_cta_corriente_tipo
								WHERE id_cobro='" . $this->fields['id_cobro'] . "'
									AND monto_cobrable > 0
									AND cta_corriente.incluir_en_cobro = 'SI'
									AND cta_corriente.cobrable = 1
								$where_gastos_asunto
								ORDER BY fecha ASC";

				$lista_gastos = new ListaGastos($this->sesion, '', $query);
				$totales['total'] = 0;
				$totales['total_moneda_cobro'] = 0;
				if ($lista_gastos->num == 0) {
					$row = $row_tmpl;
					$row = str_replace('%fecha%', '&nbsp;', $row);
					$row = str_replace('%descripcion%', __('No hay gastos en este cobro'), $row);
					$row = str_replace('%descripcion_b%', '(' . __('No hay gastos en este cobro') . ')', $row);
					$row = str_replace('%monto_original%', '&nbsp;', $row);
					$row = str_replace('%monto%', '&nbsp;', $row);
					$row = str_replace('%monto_moneda_total%', '&nbsp;', $row);
					$row = str_replace('%monto_moneda_total_sin_simbolo%', '&nbsp;', $row);
					$row = str_replace('%valor_codigo_asunto%', $gasto->fields['codigo_asunto'], $row);
					$html .= $row;
				}

				for ($i = 0; $i < $lista_gastos->num; $i++) {
					$gasto = $lista_gastos->Get($i);

					//Cargar cobro_moneda

					$cobro_moneda = new CobroMoneda($this->sesion);
					$cobro_moneda->Load($this->fields['id_cobro']);

					if ($gasto->fields['egreso'] > 0)
						$saldo = $gasto->fields['monto_cobrable'];
					elseif ($gasto->fields['ingreso'] > 0)
						$saldo = -$gasto->fields['monto_cobrable'];

					$monto_gasto = $saldo;
					$saldo_moneda_total = $saldo * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']);
					if (Conf::GetConf($this->sesion, 'CalculacionCyC'))
						$saldo_moneda_total = number_format($saldo_moneda_total, $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['cifras_decimales'], ".", "");

					$totales['total'] += $saldo_moneda_total;
					$totales['total_moneda_cobro'] += $saldo;

					$row = $row_tmpl;
					$row = str_replace('%fecha%', Utiles::sql2fecha($gasto->fields['fecha'], $idioma->fields['formato_fecha']), $row);
					$row = str_replace('%num_doc%', $gasto->fields['numero_documento'], $row);
					$row = str_replace('%tipo_gasto%', $gasto->fields['tipo_gasto'], $row);

					if (substr($gasto->fields['descripcion'], 0, 41) == 'Saldo aprovisionado restante tras Cobro #') {
						$row = str_replace('%descripcion%', __('Saldo aprovisionado restante tras Cobro #') . substr($gasto->fields['descripcion'], 42), $row);
						$row = str_replace('%descripcion_b%', __('Saldo aprovisionado restante tras Cobro #') . substr($gasto->fields['descripcion'], 42), $row);
					} else {
						$row = str_replace('%descripcion%', __($gasto->fields['descripcion']), $row);
						$row = str_replace('%descripcion_b%', __($gasto->fields['descripcion']), $row); #Ojo, este no deber?a existir
					}

					$row = str_replace('%monto_original%', $cobro_moneda->moneda[$gasto->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($monto_gasto, $cobro_moneda->moneda[$gasto->fields['id_moneda']]['cifras_decimales'], $cobro_moneda->moneda[$gasto->fields['id_moneda']]['separador_decimales'], $cobro_moneda->moneda[$gasto->fields['id_moneda']]['separador_miles']), $row);

					$row = str_replace('%monto_moneda_total%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($saldo_moneda_total, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

					$row = str_replace('%monto_moneda_total_sin_simbolo%', number_format($saldo_moneda_total, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					$html .= $row;
				}

				$html = str_replace('%monto_impuesto_total%', '', $html);
				$html = str_replace('%monto_moneda_total_con_impuesto%', '', $html);

				if (Conf::GetConf($this->sesion, 'MostrarProveedorenGastos')) {
					$row = str_replace('%proveedor%', $detalle['glosa_proveedor'], $row);
				} else {
					$row = str_replace('%proveedor%', '', $row);
				}

				if ($this->fields['opc_ver_solicitante']) {
					$row = str_replace('%solicitante%', $detalle['username'], $row);
				} else {
					$row = str_replace('%solicitante%', '', $row);
				}

				break;

			case 'GASTOS_TOTAL': //GenerarDocumentoComun
				$html = str_replace('%total%', __('Total'), $html);
				$html = str_replace('%glosa_total%', __('Total Gastos'), $html);
				if ($lang == 'es') {
					$html = str_replace('%sub_total_gastos%', __('Sub total gastos'), $html);
				} else {
					$html = str_replace('%sub_total_gastos%', __('Sub total for expenses'), $html);
				}
				$cobro_moneda = new CobroMoneda($this->sesion);
				$cobro_moneda->Load($this->fields['id_cobro']);

				$id_moneda_base = Moneda::GetMonedaBase($this->sesion);

				#$html = str_replace('%valor_total%', ''/*$cobro_moneda->fields['simbolo'].' '.number_format($totales['total_moneda_cobro'],$cobro_moneda->fields['cifras_decimales']*/,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
				if ($this->fields['id_moneda_base'] <= 0)
					$tipo_cambio_cobro_moneda_base = 1;
				else
					$tipo_cambio_cobro_moneda_base = $cobro_moneda->moneda[$id_moneda_base]['tipo_cambio'];

				#$gastos_moneda_total = ($totales['total']*($this->fields['tipo_cambio_moneda']/$this->fields['tipo_cambio_moneda_base']))/$this->fields['opc_moneda_total_tipo_cambio'];
				#$gastos_moneda_total = ($totales['total']*($this->fields['tipo_cambio_moneda']/$tipo_cambio_cobro_moneda_base))/$tipo_cambio_moneda_total;
				# Comentado por ICC $gastos_moneda_total = $totales['total']*$moneda->fields['tipo_cambio']/$tipo_cambio_moneda_total;
				$gastos_moneda_total = $totales['total'];

				$html = str_replace('%total_gastos_moneda_total%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($totales['total'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				if ($moneda_total->fields['id_moneda'] != $id_moneda_base) {
					$html = str_replace('%glosa_total_moneda_base%', __('Total Moneda Base'), $html);
					$gastos_moneda_total_contrato = ( $gastos_moneda_total * ( $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio'])) / $tipo_cambio_cobro_moneda_base;
					$html = str_replace('%valor_total_moneda_carta%', $cobro_moneda->moneda[$id_moneda_base]['simbolo'] . $this->espacio . number_format($gastos_moneda_total_contrato, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				} else {
					$html = str_replace('%glosa_total_moneda_base%', '&nbsp;', $html);
					$html = str_replace('%valor_total_moneda_base%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($totales['total'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					$html = str_replace('%valor_total_moneda_carta%', '&nbsp;', $html);
				}

				$contr = new Contrato($this->sesion);
				$contr->Load($this->fields['id_contrato']);

				$gastos_moneda_total_contrato = ( $totales['total'] * ( $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio'])) / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'];
				$html = str_replace('%valor_total_moneda_carta%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($gastos_moneda_total_contrato, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				$html = str_replace('%valor_impuesto_monedabase%', '', $html);
				$html = str_replace('%valor_total_monedabase_con_impuesto%', '', $html);
				break;

			/*
			  CTA_CORRIENTE -> nuevo tag para la representación de la cuenta corriente (gastos, provisiones)
			  aparecerá como Saldo Inicial; Movimientos del periodo; Saldo Periodo; Saldo Final
			 */
			case 'CTA_CORRIENTE': //GenerarDocumentoComun
				if ($this->fields['opc_ver_gastos'] == 0)
					return '';

				$html = str_replace('%titulo_detalle_cuenta%', __('Saldo de Gastos Adeudados'), $html);
				$html = str_replace('%descripcion_cuenta%', __('Descripción'), $html);
				$html = str_replace('%monto_cuenta%', __('Monto'), $html);

				$html = str_replace('%CTA_CORRIENTE_SALDO_INICIAL%', $this->GenerarDocumentoComun($parser, 'CTA_CORRIENTE_SALDO_INICIAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%CTA_CORRIENTE_MOVIMIENTOS_ENCABEZADO%', $this->GenerarDocumentoComun($parser, 'CTA_CORRIENTE_MOVIMIENTOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%CTA_CORRIENTE_MOVIMIENTOS_FILAS%', $this->GenerarDocumentoComun($parser, 'CTA_CORRIENTE_MOVIMIENTOS_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%CTA_CORRIENTE_MOVIMIENTOS_TOTAL%', $this->GenerarDocumentoComun($parser, 'CTA_CORRIENTE_MOVIMIENTOS_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%CTA_CORRIENTE_SALDO_FINAL%', $this->GenerarDocumentoComun($parser, 'CTA_CORRIENTE_SALDO_FINAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				break;

			case 'CTA_CORRIENTE_SALDO_INICIAL': //GenerarDocumentoComun
				$saldo_inicial = $this->SaldoInicialCuentaCorriente();

				$html = str_replace('%saldo_inicial_cuenta%', __('Saldo inicial'), $html);
				$html = str_replace('%valor_saldo_inicial_cuenta%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($saldo_inicial, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			case 'CTA_CORRIENTE_MOVIMIENTOS_ENCABEZADO': //GenerarDocumentoComun
				$html = str_replace('%movimientos%', __('Movimientos del periodo'), $html);
				$html = str_replace('%fecha%', __('Fecha'), $html);
				$html = str_replace('%descripcion%', __('Descripción'), $html);
				$html = str_replace('%egreso%', __('Egreso') . ' (' . $moneda_total->fields['simbolo'] . ')', $html);
				$html = str_replace('%ingreso%', __('Ingreso') . ' (' . $moneda_total->fields['simbolo'] . ')', $html);
				break;

			case 'CTA_CORRIENTE_MOVIMIENTOS_FILAS': //GenerarDocumentoComun
				$row_tmpl = $html;
				$html = '';
				$query = "SELECT SQL_CALC_FOUND_ROWS * FROM cta_corriente
								WHERE id_cobro='" . $this->fields['id_cobro'] . "' AND (egreso > 0 OR ingreso > 0) AND cta_corriente.incluir_en_cobro = 'SI' ORDER BY fecha ASC";
				$lista_gastos = new ListaGastos($this->sesion, '', $query);
				$totales['total'] = 0;
				global $total_egreso;
				global $total_ingreso;
				$total_egreso = 0;
				$total_ingreso = 0;
				if ($lista_gastos->num == 0) {
					$row = $row_tmpl;
					$row = str_replace('%fecha%', '&nbsp;', $row);
					$row = str_replace('%descripcion%', __('No hay gastos en este cobro'), $row);
					$row = str_replace('%monto_egreso%', '&nbsp;', $row);
					$row = str_replace('%monto_ingreso%', '&nbsp;', $row);
					$html .= $row;
				}

				for ($i = 0; $i < $lista_gastos->num; $i++) {
					$gasto = $lista_gastos->Get($i);

					$row = $row_tmpl;

					if ($gasto->fields['egreso'] > 0) {

						$monto_egreso = $gasto->fields['monto_cobrable'];
						$totales['total'] += $gasto->fields['monto_cobrable'] * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']); #error gasto 2
						$totales['total_egreso'] += $gasto->fields['monto_cobrable'] * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']); #error gasto 3
						$row = str_replace('%fecha%', Utiles::sql2fecha($gasto->fields['fecha'], $idioma->fields['formato_fecha']), $row);
						$row = str_replace('%descripcion%', $gasto->fields['descripcion'], $row);
						$row = str_replace('%monto_egreso%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($monto_egreso * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']), $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row); #error gasto 4
						$row = str_replace('%monto_ingreso%', '', $row);
					} elseif ($gasto->fields['ingreso'] > 0) {

						$monto_ingreso = $gasto->fields['monto_cobrable'];
						$totales['total'] -= $gasto->fields['monto_cobrable'] * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']); #error gasto 5
						$totales['total_ingreso'] += $gasto->fields['monto_cobrable'] * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']); #error gasto 6
						$row = str_replace('%fecha%', Utiles::sql2fecha($gasto->fields['fecha'], $idioma->fields['formato_fecha']), $row);
						$row = str_replace('%descripcion%', $gasto->fields['descripcion'], $row);
						$row = str_replace('%monto_egreso%', '', $row);

						$row = str_replace('%monto_ingreso%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($monto_ingreso * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']), $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row); #error gasto 7
					}
					$html .= $row;
				}
				break;

			case 'CTA_CORRIENTE_MOVIMIENTOS_TOTAL': //GenerarDocumentoComun

				$html = str_replace('%total%', __('Total'), $html);
				$gastos_moneda_total = $totales['total'];

				$html = str_replace('%total_monto_egreso%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($totales['total_egreso'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%total_monto_ingreso%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($totales['total_ingreso'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%saldo_periodo%', __('Saldo del periodo'), $html);
				$html = str_replace('%total_monto_gastos%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($totales['total_ingreso'] - $totales['total_egreso'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			case 'CTA_CORRIENTE_SALDO_FINAL': //GenerarDocumentoComun
				#Total de gastos en moneda que se muestra el cobro.
				$saldo_inicial = $this->SaldoInicialCuentaCorriente();
				$gastos_moneda_total = $totales['total'];
				$saldo_cobro = $gastos_moneda_total;
				$saldo_final = $saldo_inicial - $saldo_cobro;
				$html = str_replace('%saldo_final_cuenta%', __('Saldo final'), $html);

				$html = str_replace('%valor_saldo_final_cuenta%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($saldo_final, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			case 'TIPO_CAMBIO': //GenerarDocumentoComun
				if ($this->fields['opc_ver_tipo_cambio'] == 0)
					return '';
				//Tipos de Cambio
				$html = str_replace('%titulo_tipo_cambio%', __('Tipos de Cambio'), $html);
				foreach ($cobro_moneda->moneda as $id => $moneda) {
					$html = str_replace("%glosa_moneda_id_$id%", __($moneda['glosa_moneda']), $html);
					$html = str_replace("%simbolo_moneda_id_$id%", $moneda['simbolo'], $html);
					$html = str_replace("%valor_moneda_id_$id%", number_format($moneda['tipo_cambio'], 2, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}
				break;

			case 'INFORME_GASTOS':
				$nuevomodulofactura = Conf::GetConf($this->sesion, 'NuevoModuloFactura');
				$this->ArrayFacturasDelContrato = $this->FacturasDelContrato($this->sesion, $nuevomodulofactura, null, 'G');
				$totalescontrato = $this->TotalesDelContrato($this->ArrayFacturasDelContrato, $nuevomodulofactura, $this->fields['id_cobro']);
				break;


			case 'INFORME_HONORARIOS':
				$nuevomodulofactura = Conf::GetConf($this->sesion, 'NuevoModuloFactura');
				$this->ArrayFacturasDelContrato = $this->FacturasDelContrato($this->sesion, $nuevomodulofactura, null, 'H');
				$totalescontrato = $this->TotalesDelContrato($this->ArrayFacturasDelContrato, $nuevomodulofactura, $this->fields['id_cobro']);
				break;

			case 'MOROSIDAD': //GenerarDocumentoComun
				if ($this->fields['opc_ver_morosidad'] == 0)
					return '';

				$html = str_replace('%titulo_morosidad%', __('Saldo Adeudado'), $html);
				$html = str_replace('%nota_disclaimer2%', __('nota_morosidad'), $html);
				$html = str_replace('%MOROSIDAD_ENCABEZADO%', $this->GenerarDocumentoComun($parser, 'MOROSIDAD_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%MOROSIDAD_FILAS%', $this->GenerarDocumentoComun($parser, 'MOROSIDAD_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%MOROSIDAD_HONORARIOS_TOTAL%', $this->GenerarDocumentoComun($parser, 'MOROSIDAD_HONORARIOS_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%MOROSIDAD_GASTOS%', $this->GenerarDocumentoComun($parser, 'MOROSIDAD_GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%MOROSIDAD_TOTAL%', $this->GenerarDocumentoComun($parser, 'MOROSIDAD_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				break;


			case 'MOROSIDAD_ENCABEZADO': //GenerarDocumentoComun
				$html = str_replace('%numero_nota_cobro%', __('Folio Carta'), $html);
				$html = str_replace('%numero_factura%', __('Factura'), $html);
				$html = str_replace('%fecha%', __('Fecha'), $html);
				$html = str_replace('%moneda%', __('Moneda'), $html);
				$html = str_replace('%monto_moroso%', __('Monto'), $html);
				break;

			case 'MOROSIDAD_FILAS': //GenerarDocumentoComun
				$row_tmpl = $html;
				$html = '';

				$nuevomodulofactura = Conf::GetConf($this->sesion, 'NuevoModuloFactura');
				$facturasRS = $this->ArrayFacturasDelContrato; //($this->sesion,$nuevomodulofactura);
				$totalescontrato = $this->ArrayTotalesDelContrato; //($facturasRS,$nuevomodulofactura,$this->fields['id_cobro']);

				$totales = $totalescontrato['contrato'];
				$totalescobro = $totalescontrato[$this->fields['id_cobro']];

				if (count($facturasRS) > 0) {

					if ($nuevomodulofactura) {

						foreach ($facturasRS as $facturanumero => $facturaarray) {

							$factura = $facturaarray[0];
							$factura['facturanumero'] = $facturanumero;

							$monto_honorarios = number_format($factura['subtotal_honorarios'], $factura['cifras_decimales'], '.', '');
							$monto_gastos_c_iva = number_format($factura['subtotal_gastos'], $factura['cifras_decimales'], '.', '');
							$monto_gastos_s_iva = number_format($factura['subtotal_gastos_sin_impuesto'], $factura['cifras_decimales'], '.', '');
							$monto_gastos = $monto_gastos_c_iva + $monto_gastos_s_iva;
							$monto_honorarios_moneda = $monto_honorarios * $factura['tasa_cambio'];
							$monto_gastos_c_iva_moneda = $monto_gastos_c_iva * $factura['tasa_cambio'];
							$monto_gastos_s_iva_moneda = $monto_gastos_s_iva * $factura['tasa_cambio'];
							$monto_gastos_moneda = $monto_gastos * $factura['tasa_cambio'];
							$total_en_moneda = $monto_honorarios_moneda = $total_honorarios * ($factura['tipo_cambio_moneda'] / $factura['tipo_cambio']);

							if ($factura['incluye_honorarios'] == 1) {
								$saldo_honorarios = -1 * $factura['saldo'];
								$saldo_gastos = 0;
							} else {
								$saldo_honorarios = 0;
								$saldo_gastos = -1 * $factura['saldo'];
							}

							if (($saldo_honorarios + $saldo_gastos) == 0) {
								continue;
							}

							$row = $row_tmpl;
							$row = str_replace('%numero_nota_cobro%', $factura['id_cobro'], $row);
							$row = str_replace('%numero_factura%', $factura['facturanumero'] ? $factura['facturanumero'] : ' - ', $row);
							$row = str_replace('%fecha%', Utiles::sql2fecha($factura['fecha_enviado_cliente'], '%d-%m-%Y') == 'No existe fecha' ? Utiles::sql2fecha($factura['fecha_emision'], '%d-%m-%Y') : Utiles::sql2fecha($factura['fecha_enviado_cliente'], '%d-%m-%Y'), $row);
							$row = str_replace('%moneda%', $factura['simbolo'] . '&nbsp;', $row);
							$row = str_replace('%moneda_total%', $factura['simbolo_moneda_total'] . '&nbsp;', $row);

							$row = str_replace('%monto_honorarios%', number_format($monto_honorarios, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							$row = str_replace('%monto_honorarios_moneda%', number_format($monto_honorarios_moneda, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

							$row = str_replace('%monto_gastos_c_iva%', number_format($monto_gastos_c_iva, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							$row = str_replace('%monto_gastos_c_iva_moneda%', number_format($monto_gastos_c_iva_moneda, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

							$row = str_replace('%monto_gastos_s_iva%', number_format($monto_gastos_s_iva, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							$row = str_replace('%monto_gastos_s_iva_moneda%', number_format($monto_gastos_s_iva_moneda, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

							$row = str_replace('%monto_gastos%', number_format($monto_gastos, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							$row = str_replace('%monto_gastos_moneda%', number_format($monto_gastos_moneda, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

							$row = str_replace('%monto_total%', number_format($monto_gastos + $monto_honorarios, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

							$row = str_replace('%saldo_honorarios%', number_format($saldo_honorarios, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							$row = str_replace('%saldo_gastos%', number_format($saldo_gastos, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

							$row = str_replace(array('%saldo_total%', '%monto_moroso_documento%'), number_format($saldo_honorarios + $saldo_gastos, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

							$row = str_replace('%monto_moroso_moneda_total%', number_format(($monto_gastos_moneda + $monto_honorarios_moneda), $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							$row = str_replace('%monto_moroso%', number_format(($monto_gastos_moneda + $monto_honorarios_moneda), $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

							$html.=$row;
						}
					} else {

						$query_saldo_adeudado = "
							SELECT d.id_cobro, c.documento, d.fecha, prm_m.simbolo, d.subtotal_honorarios, c.monto_gastos,d.saldo_honorarios, d.saldo_gastos, d.honorarios_pagados, d.gastos_pagados
								FROM documento d
							LEFT JOIN cobro c ON d.id_cobro = c.id_cobro
							LEFT JOIN prm_moneda prm_m ON d.id_moneda = prm_m.id_moneda
								WHERE d.codigo_cliente = '" . $this->fields['codigo_cliente'] . "'
									AND (d.saldo_honorarios + d.saldo_gastos) > 0
									AND c.documento IS NOT NULL
									AND c.id_cobro IS NOT NULL
									AND c.estado != 'CREADO'
									AND c.estado != 'EMITIDO'
									AND c.estado != 'EN REVISION'
									AND c.estado != 'INCOBRABLE'
									AND c.estado != 'PAGADO'
									GROUP BY id_cobro";

						$resp = mysql_query($query_saldo_adeudado, $this->sesion->dbh) or Utiles::errorSQL($query_saldo_adeudado, __FILE__, __LINE__, $this->sesion->dbh);

						while (list($d_numero_cobro, $c_numero_factura, $d_fecha_documento, $d_simbolo_moneda_documento, $d_subtotal_honorarios, $c_montogastos, $d_saldo_honorarios, $d_saldo_gasto, $d_honorarios_pagados, $d_gastos_pagados) = mysql_fetch_array($resp)) {

							$saldo_adeudado = $d_saldo_honorarios + $d_saldo_gasto;

							$row = $row_tmpl;

							//Lyr
							$row = str_replace('%numero_nota_cobro%', $d_numero_cobro, $row);
							$row = str_replace('%numero_factura%', $c_numero_factura ? $c_numero_factura : ' - ', $row);
							$row = str_replace('%fecha%', Utiles::sql2fecha($d_fecha_documento, '%d-%m-%Y'), $row);
							$row = str_replace('%moneda_total%', $d_simbolo_moneda_documento . '&nbsp;', $row);
							$row = str_replace('%monto_moroso_documento%', number_format($saldo_adeudado, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

							//Modulo Facturacion
							$row = str_replace('%moneda%', $factura['simbolo'] . '&nbsp;', $row);

							$row = str_replace('%monto_honorarios%', number_format($d_subtotal_honorarios, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							$row = str_replace('%monto_gastos%', number_format($c_montogastos, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

							$row = str_replace('%saldo_honorarios%', number_format($d_saldo_honorarios, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							$row = str_replace('%saldo_gastos%', number_format($d_saldo_gasto, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

							$row = str_replace('%monto_moroso_moneda_total%', number_format(($monto_gastos_moneda + $monto_honorarios_moneda), $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

							$html.=$row;
						}
					}
				} else {
					$html = str_replace('%numero_nota_cobro%', __('No hay facturas adeudadas'), $html);
				}

				break;

			case 'MOROSIDAD_HONORARIOS_TOTAL': //GenerarDocumentoComun
			case 'MOROSIDAD_HONORARIOS': //GenerarDocumentoComun
				$html = str_replace('%numero_nota_cobro%', '', $html);
				$html = str_replace('%numero_factura%', '', $html);
				$html = str_replace('%fecha%', '', $html);
				$html = str_replace('%moneda%', __('Total Honorarios Adeudados') . ':', $html);


				$html = str_replace('%monto_moroso_documento%', $totales['simbolo_moneda_total'] . $this->espacio . number_format($totales['saldo_honorarios'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%monto_moroso%', $totales['simbolo_moneda_total'] . $this->espacio . number_format($totales['saldo_honorarios_moneda'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				//$html = str_replace('%nota%', __('Nota: Si al recibo de esta carta su cuenta se encuentra al día, por favor dejar sin efecto.'), $html);
				$html = str_replace('%nota%', __('nota_morosidad_honorarios'), $html);
				break;

			case 'MOROSIDAD_GASTOS_TOTAL': //GenerarDocumentoComun
			case 'MOROSIDAD_GASTOS': //GenerarDocumentoComun
				$html = str_replace('%numero_nota_cobro%', '', $html);
				$html = str_replace('%numero_factura%', '', $html);
				$html = str_replace('%fecha%', '', $html);
				$html = str_replace('%moneda%', __('Total Gastos Adeudados') . ':', $html);

				$html = str_replace('%monto_moroso_documento%', $totales['simbolo_moneda_total'] . $this->espacio . number_format($totales['saldo_gastos'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%monto_moroso%', $totales['simbolo_moneda_total'] . $this->espacio . number_format($totales['saldo_gastos_moneda'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				// $html = str_replace('%nota%', __('Nota: Si al recibo de esta carta su cuenta se encuentra al día, por favor dejar sin efecto.'), $html);
				$html = str_replace('%nota%', __('nota_morosidad_gastos'), $html);
				break;

			case 'MOROSIDAD_TOTAL': //GenerarDocumentoComun
				$html = str_replace('%numero_nota_cobro%', '', $html);
				$html = str_replace('%numero_factura%', '', $html);
				$html = str_replace('%fecha%', '', $html);
				$html = str_replace('%moneda%', __('Total Adeudado') . ':', $html);

				if ($nuevomodulofactura) {
					$html = str_replace('%monto_moroso_documento%', $totales['simbolo_moneda_total'] . $this->espacio . number_format(($totales['saldo_honorarios'] + $totales['saldo_gastos']), $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					$html = str_replace('%monto_moroso%', $totales['simbolo_moneda_total'] . $this->espacio . number_format(($totales['saldo_gastos_moneda'] + $totales['saldo_honorarios_moneda']), $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				} else {

					$query_saldo_adeudado_total = "
							SELECT SUM(d.saldo_honorarios + d.saldo_gastos) as saldo_total
								FROM documento d
							LEFT JOIN cobro c ON d.id_cobro = c.id_cobro
								WHERE d.codigo_cliente = '" . $this->fields['codigo_cliente'] . "'
									AND (d.saldo_honorarios + d.saldo_gastos) > 0
									AND c.documento IS NOT NULL
									AND c.id_cobro IS NOT NULL
									AND c.estado != 'CREADO'
									AND c.estado != 'EMITIDO'
									AND c.estado != 'EN REVISION'
									AND c.estado != 'INCOBRABLE'
									AND c.estado != 'PAGADO'";

					$resp = mysql_query($query_saldo_adeudado_total, $this->sesion->dbh) or Utiles::errorSQL($query_saldo_adeudado_total, __FILE__, __LINE__, $this->sesion->dbh);
					list($saldo_total) = mysql_fetch_array($resp);
					$html = str_replace('%monto_moroso_documento%', $totales['simbolo_moneda_total'] . $this->espacio . number_format($saldo_total, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}

				// $html = str_replace('%nota%', __('Nota: Si al recibo de esta carta su cuenta se encuentra al día, por favor dejar sin efecto.'), $html);
				$html = str_replace('%nota%', __('nota_morosidad_total'), $html);
				break;

			case 'GLOSA_ESPECIAL': //GenerarDocumentoComun
				if ($this->fields['codigo_idioma'] != 'en')
					$html = str_replace('%glosa_especial%', 'Emitir cheque/transferencia a nombre de<br />
														TORO Y COMPAÑÍA LIMITADA<br />
														Rut.: 77.440.670-0<br />
														Banco Bice<br />
														Cta. N° 15-72569-9<br />
														Santiago - Chile', $html);
				else
					$html = str_replace('%glosa_especial%', 'Beneficiary: Toro y Compañia Limitada, Abogados-Consultores<br />
														Tax Identification Number:  77.440.670-0<br />
														DDA Number:  50704183518<br />
														Bank:  Banco de Chile<br />
														Address:  Apoquindo 5470, Las Condes<br />
														City:  Santiago<br />
														Country: Chile<br />
														Swift code:  BCHICLRM', $html);
				break;

			case 'SALTO_PAGINA': //GenerarDocumentoComun
				//no borrarle al css el BR.divisor
				break;
		}
		return $html;
	}

}
