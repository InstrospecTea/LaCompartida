<?php

class NotaCobroDocumento extends NotaCobroDocumentoComun {

	function GenerarDocumento($parser, $theTag = 'INFORME', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, & $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto) {
		global $contrato;
		global $cobro_moneda;
		global $masi;

		$moneda_total = new Objeto($this->sesion, '', '', 'prm_moneda', 'id_moneda');
		$moneda_total->Load($this->fields['opc_moneda_total'] > 0 ? $this->fields['opc_moneda_total'] : 1);

		if (!isset($parser->tags[$theTag])) {
			return;
		}

		$this->FillTemplateData($idioma, $moneda);

		$html = $this->RenderTemplate($parser->tags[$theTag]);

		switch ($theTag) {
			case 'INFORME': //GenerarDocumento

				$nuevomodulofactura = Conf::GetConf($this->sesion, 'NuevoModuloFactura');

				if (strpos($html, '%INFORME_GASTOS%') !== false) {

					$this->ArrayFacturasDelContrato = $this->FacturasDelContrato($this->sesion, $nuevomodulofactura, null, 'G');
					$this->ArrayTotalesDelContrato = $this->TotalesDelContrato($this->ArrayFacturasDelContrato, $nuevomodulofactura, $this->fields['id_cobro']);
					$html = str_replace('%INFORME_GASTOS%', '', $html);
				} else if (strpos($html, '%INFORME_HONORARIOS%') !== false) {

					$this->ArrayFacturasDelContrato = $this->FacturasDelContrato($this->sesion, $nuevomodulofactura, null, 'H');
					$this->ArrayTotalesDelContrato = $this->TotalesDelContrato($this->ArrayFacturasDelContrato, $nuevomodulofactura, $this->fields['id_cobro']);
					$html = str_replace('%INFORME_HONORARIOS%', '', $html);
				}

				include_once ('CartaCobro.php');

				$CartaCobro = new CartaCobro($this->sesion, $this->fields, $this->ArrayFacturasDelContrato, $this->ArrayTotalesDelContrato);

				if (isset($this->DetalleLiquidaciones)) {
					$CartaCobro->DetalleLiquidaciones = $this->DetalleLiquidaciones;
				}

				$textocarta = $CartaCobro->GenerarDocumentoCarta($parser_carta, 'CARTA', $lang, $moneda_cliente_cambio, $moneda_cli, $idioma, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $cliente, $id_carta);
				$html = str_replace('%COBRO_CARTA%', $textocarta, $html);

				$PdfLinea1 = Conf::GetConf($this->sesion, 'PdfLinea1');
				$PdfLinea2 = Conf::GetConf($this->sesion, 'PdfLinea2');
				$PdfLinea3 = Conf::GetConf($this->sesion, 'PdfLinea3');

				$query = "SELECT count(*) FROM cta_corriente WHERE id_cobro=" . $this->fields['id_cobro'];
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($cont_gastos) = mysql_fetch_array($resp);

				$query = "SELECT count(*) FROM trabajo WHERE id_cobro = " . $this->fields['id_cobro'];
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($cont_trab) = mysql_fetch_array($resp);

				$query = "SELECT count(*) FROM tramite WHERE id_cobro = " . $this->fields['id_cobro'];
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($cont_tram) = mysql_fetch_array($resp);

				$html = str_replace('%cobro%', __('NOTA DE COBRO') . ' # ', $html);
				$html = str_replace('%valor_cobro%', $this->fields['id_cobro'], $html);
				$html = str_replace('%logo%', Conf::LogoDoc(true), $html);
				$html = str_replace('%titulo%', $PdfLinea1, $html);
				$html = str_replace('%logo_cobro%', Conf::Server() . Conf::ImgDir(), $html);
				$html = str_replace('%subtitulo%', $PdfLinea2, $html);
				$html = str_replace('%direccion%', $PdfLinea3, $html);
				$html = str_replace('%direccion_blr%', __('%direccion_blr%'), $html);
				$html = str_replace('%glosa_fecha%', __('Fecha') . ':', $html);
				$html = str_replace('%glosa_fecha_mayuscula%', __('FECHA'), $html);
				$html = str_replace('%texto_factura%', __('FACTURA'), $html);
				$html = str_replace('%fecha_gqmc%', ($this->fields['fecha_emision'] == '0000-00-00 00:00:00' or $this->fields['fecha_emision'] == '' or $this->fields['fecha_emision'] == 'NULL') ? ucwords(strftime(Utiles::FormatoStrfTime("%e %B %Y"), time())) : ucwords(strftime(Utiles::FormatoStrfTime("%e %B %Y"), strtotime($this->fields['fecha_emision']))), $html);
				$html = str_replace('%fecha%', ($this->fields['fecha_cobro'] == '0000-00-00 00:00:00' or $this->fields['fecha_cobro'] == '' or $this->fields['fecha_cobro'] == 'NULL') ? Utiles::sql2fecha(date('Y-m-d'), $idioma->fields['formato_fecha']) : Utiles::sql2fecha($this->fields['fecha_emision'], $idioma->fields['formato_fecha']), $html);

				if ($lang == 'es') {
					$fecha_lang = ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%d de %B de %Y'));
				} else {
					$fecha_lang = date('F d, Y');
				}

				$fecha_mes_del_cobro = strtotime($this->fields['fecha_fin']);
				$fecha_mes_del_cobro = strftime("%B %Y", mktime(0, 0, 0, date("m", $fecha_mes_del_cobro), date("d", $fecha_mes_del_cobro) - 5, date("Y", $fecha_mes_del_cobro)));

				$html = str_replace('%fecha_mes_del_cobro%', ucfirst($fecha_mes_del_cobro), $html);
				$html = str_replace('%fecha_larga%', $fecha_lang, $html);
				$html = str_replace('%fecha_dia_mes_ano%', date("d/m/Y"), $html);

				$query = "SELECT CONCAT(a.nombre, ' ', a.apellido1, ' ', a.apellido2) FROM usuario AS a JOIN contrato ON a.id_usuario=contrato.id_usuario_responsable JOIN cobro ON cobro.id_contrato=contrato.id_contrato WHERE cobro.id_cobro=" . $this->fields['id_cobro'];
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($nombre_encargado) = mysql_fetch_array($resp);

				if ($this->fields['estado'] == 'CREADO' || $this->fields['estado'] == 'EN REVISION') {
					$html = str_replace('%nombre_socio_estado%', $nombre_encargado, $html);
				} else {
					$html = str_replace('%nombre_socio_estado%', '', $html);
				}

				$html = str_replace('%nombre_socio%', $nombre_encargado, $html);
				$html = str_replace('%socio%', __('SOCIO'), $html);
				$html = str_replace('%socio_cobrador%', __('SOCIO COBRADOR'), $html);
				$html = str_replace('%fono%', __('TELÉFONO'), $html);
				$html = str_replace('%fax%', __('TELEFAX'), $html);
				$html = str_replace('%asunto%', __('Asunto'), $html);
				$html = str_replace('%glosa_asunto%', __('Glosa') . ' ' . __('Asunto'), $html);
				$html = str_replace('%codigo_asunto%', __('Código') . ' ' . __('Asunto'), $html);

				$cliente = new Cliente($this->sesion);

				if (Conf::GetConf($this->sesion, 'CodigoSecundario')) {
					$codigo_cliente = $cliente->CodigoACodigoSecundario($this->fields['codigo_cliente']);
				} else {
					$codigo_cliente = $this->fields['codigo_cliente'];
				}

				$html = str_replace('%codigo_cliente%', $codigo_cliente, $html);
				$html = str_replace('%CLIENTE%', $this->GenerarSeccionCliente($parser->tags['CLIENTE'], $idioma, $moneda, $asunto), $html);

				if ($this->fields['forma_cobro'] == 'ESCALONADA') {
					$html = str_replace('%DETALLE_COBRO%', "%DETALLE_COBRO%\n\n%TABLA_ESCALONADA%", $html);
				}

				$html = str_replace('%DETALLE_COBRO%', $this->GenerarDocumento($parser, 'DETALLE_COBRO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);

				if ($this->fields['forma_cobro'] == 'ESCALONADA') {
					$this->CargarEscalonadas();

					$html_tabla = "<br /><span class=\"titulo_seccion\">" . __('Detalle Tarifa Escalonada') . "</span>
									<table class=\"tabla_normal\" width=\"50%\">%filas_escalas%</table>";
					$html_fila = "";

					for ($i = 1; $i <= $this->escalonadas['num']; $i++) {

						$detalle_escala = "";

						if (!empty($this->escalonadas[$i]['tiempo_inicial'])) {
							$detalle_escala .= $this->escalonadas[$i]['tiempo_inicial'] . ' - ';
						}

						$detalle_escala .= !empty($this->escalonadas[$i]['tiempo_final']) && $this->escalonadas[$i]['tiempo_final'] != 'NULL' ? $this->escalonadas[$i]['tiempo_final'] . ' hrs. ' : ' ' . __('Más hrs') . ' ';
						$detalle_escala .= !empty($this->escalonadas[$i]['id_tarifa']) && $this->escalonadas[$i]['id_tarifa'] != 'NULL' ? " " . __('Tarifa HH') . " " : " " . __('monto fijo') . " ";

						if (!empty($this->fields['esc' . $i . '_descuento']) && $this->fields['esc' . $i . '_descuento'] != 'NULL') {
							$detalle_escala .= " " . __('con descuento') . " {$this->fields['esc' . $i . '_descuento']}% ";
						}

						if (!empty($this->fields['esc' . $i . '_monto']) && $this->fields['esc' . $i . '_monto'] != 'NULL') {
							$query_glosa_moneda = "SELECT simbolo FROM prm_moneda WHERE id_moneda='{$this->escalonadas[$i]['id_moneda']}' LIMIT 1";
							$resp = mysql_query($query_glosa_moneda, $this->sesion->dbh) or Utiles::errorSQL($query_glosa_moneda, __FILE__, __LINE__, $this->sesion->dbh);
							list( $simbolo_moneda ) = mysql_fetch_array($resp);
							$monto_escala = number_format($this->escalonadas[$i]['monto'], $cobro_moneda->moneda[$this->escalonadas[$i]['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
							$detalle_escala .= ": $simbolo_moneda $monto_escala";
						}
						$html_fila .= "	<tr> <td>$detalle_escala</td> </tr>\n";
					}

					$html_tabla = str_replace('%filas_escalas%', $html_fila, $html_tabla);
					$html = str_replace('%TABLA_ESCALONADA%', $html_tabla, $html);
				}

				if ($this->fields['forma_cobro'] == 'CAP') {
					$html = str_replace('%RESUMEN_CAP%', $this->GenerarDocumento($parser, 'RESUMEN_CAP', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%RESUMEN_CAP%', '', $html);
				}

				if (Conf::GetConf($this->sesion, 'ParafoAsuntosSoloSiHayTrabajos')) {
					if ($cont_trab || $cont_tram) {
						$html = str_replace('%ASUNTOS%', $this->GenerarDocumento($parser, 'ASUNTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					} else {
						$html = str_replace('%ASUNTOS%', '', $html);
					}
				} else {
					$html = str_replace('%ASUNTOS%', $this->GenerarDocumento($parser, 'ASUNTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				}

				$html = str_replace('%TRAMITES%', '', $html);

				if (Conf::GetConf($this->sesion, 'ParafoGastosSoloSiHayGastos')) {
					if ($cont_gastos)
						$html = str_replace('%GASTOS%', $this->GenerarDocumento($parser, 'GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					else
						$html = str_replace('%GASTOS%', '', $html);
				} else {
					$html = str_replace('%GASTOS%', $this->GenerarDocumento($parser, 'GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				}

				$html = str_replace('%CTA_CORRIENTE%', $this->GenerarDocumento($parser, 'CTA_CORRIENTE', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%TIPO_CAMBIO%', $this->GenerarDocumento($parser, 'TIPO_CAMBIO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%MOROSIDAD%', $this->GenerarDocumentoComun($parser, 'MOROSIDAD', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%GLOSA_ESPECIAL%', $this->GenerarDocumentoComun($parser, 'GLOSA_ESPECIAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%RESUMEN_PROFESIONAL_POR_CATEGORIA%', $this->GenerarSeccionResumenProfesional($parser, 'RESUMEN_PROFESIONAL_POR_CATEGORIA', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%RESUMEN_PROFESIONAL%', $this->GenerarSeccionResumenProfesional($parser, 'RESUMEN_PROFESIONAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%TIPO_CAMBIO%', $this->GenerarDocumento($parser, 'TIPO_CAMBIO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);

				if ($masi) {
					$html = str_replace('%SALTO_PAGINA%', $this->GenerarDocumentoComun($parser, 'SALTO_PAGINA', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%SALTO_PAGINA%', '', $html);
				}

				break;

			case 'DETALLE_COBRO': //GenerarDocumento

				/**
				  * Detalle de tarifa escalonada.
				  */
				$chargingBusiness = new ChargingBusiness($this->sesion);
				$coiningBusiness = new CoiningBusiness($this->sesion);
				$translatingBusiness = new TranslatingBusiness($this->sesion);
				$currency = $coiningBusiness->getCurrency($this->fields['opc_moneda_total']);
				$language = $translatingBusiness->getLanguageByCode($idioma->fields['codigo_idioma']);
				$slidingScales = $chargingBusiness->getSlidingScales($this->fields['id_cobro']);
				$table = $chargingBusiness->getSlidingScalesDetailTable($slidingScales, $currency, $language);
				$workDetailTable = $chargingBusiness->getSlidingScalesWorkDetail($chargingBusiness->getCharge($this->fields['id_cobro']));
				$html = str_replace('%detalle_escalones%', $table, $html);
				$html = str_replace('%detalle_trabajos_escalones%', $workDetailTable, $html);

				if ($this->fields['opc_ver_resumen_cobro'] == 0) {
					return '';
				}

				$imprimir_asuntos = '';
				for ($k = 0; $k < count($this->asuntos); $k++) {
					$asunto = new Asunto($this->sesion);
					$asunto->LoadByCodigo($this->asuntos[$k]);
					$imprimir_asuntos .= $asunto->fields['glosa_asunto'];
					if (($k + 1) < count($this->asuntos)) {
						$imprimir_asuntos .= '<br />';
					}
				}

				if (array_key_exists('codigo_contrato', $contrato->fields)) {
					$html = str_replace('%glosa_codigo_contrato%', __('Código') . ' ' . __('Contrato'), $html);
					$html = str_replace('%codigo_contrato%', $contrato->fields['codigo_contrato'], $html);
				} else {
					$html = str_replace('%glosa_codigo_contrato%', '', $html);
					$html = str_replace('%codigo_contrato%', '', $html);
				}

				$html = str_replace('%glosa_cliente%', $contrato->fields['factura_razon_social'], $html);
				$html = str_replace('%reporte_servicio%', __('Reporte de Servicios'), $html);
				$html = str_replace('%aviso_de_cobro%', 'Aviso de cobro', $html);
				$html = str_replace('%factura_o_nd%', 'Factura o ND', $html);
				$html = str_replace('%honorario_yo_gastos%', __('honorario_yo_gastos'), $html);
				$html = str_replace('%materia%', __('Materia'), $html);
				$html = str_replace('%glosa_asunto_sin_codigo%', $imprimir_asuntos, $html);
				$html = str_replace('%resumen_cobro%', __('Resumen Nota de Cobro'), $html);
				$html = str_replace('%fecha%', __('Fecha'), $html);
				$html = str_replace('%texto_fecha_emision%', __('Fecha Emisión'), $html);
				$html = str_replace('%fecha_emision_glosa%', ($this->fields['fecha_emision'] == '0000-00-00' || $this->fields['fecha_emision'] == '' || $this->fields['fecha_emision'] == NULL ) ? '&nbsp;' : __('Fecha emisión'), $html);
				$html = str_replace('%fecha_emision%', ($this->fields['fecha_emision'] == '0000-00-00' || $this->fields['fecha_emision'] == '' || $this->fields['fecha_emision'] == NULL ) ? '&nbsp;' : Utiles::sql2fecha($this->fields['fecha_emision'], $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%cobro%', __('Cobro') . ' ' . __('N°'), $html);
				$html = str_replace('%texto_cobro_nr%', __('Cobro N°'), $html);
				$html = str_replace('%reference%', __('%reference_no%'), $html);
				$html = str_replace('%valor_cobro%', $this->fields['id_cobro'], $html);
				$html = str_replace('%total_simbolo%', __('Total') . ' (' . $moneda_total->fields['simbolo'] . ')', $html);
				$html = str_replace('%boleta%', empty($this->fields['documento']) ? '' : __('Boleta'), $html);
				$html = str_replace('%encargado%', __('Director proyecto'), $html);
				$html = str_replace('%instrucciones_pago%', __('INSTRUCCIONES DE PAGO'), $html);
				$html = str_replace('%giro_bancario%', __('Giro bancario a'), $html);
				$html = str_replace('%fecha_corte%', __('Fecha de Corte'), $html);
				$html = str_replace('%descuento_liquidacion%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['descuento'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%impuesto_liquidacion%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($impuesto_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%subtotal_gastos_honorarios_liquidacion%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($this->fields['monto_subtotal'], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				$horas_cobrables = floor(($this->fields['total_minutos']) / 60);
				$minutos_cobrables = sprintf("%02d", $this->fields['total_minutos'] % 60);

				$detalle_modalidad = $this->ObtenerDetalleModalidad($this->fields, $cobro_moneda->moneda[$this->fields['id_moneda_monto']], $idioma);
				$detalle_modalidad_lowercase = strtolower($detalle_modalidad);

				if (Conf::GetConf($this->sesion, 'ResumenProfesionalVial')) {
					$html = str_replace('%glosa_cobro%', __('Liquidación de honorarios profesionales %desde% hasta %hasta%'), $html);
				} else {
					$html = str_replace('%glosa_cobro%', __('Detalle Cobro'), $html);
				}

				if ($lang == "en") {
					$html = str_replace('%glosa_cobro_aguilar%', __('Debit Note details'), $html);
				} else {
					$html = str_replace('%glosa_cobro_aguilar%', __('Nota de Débito'), $html);
				}


				if (!$contrato->fields['id_usuario_responsable']) {
					$nombre_encargado = '';
				} else {
					$query = "SELECT CONCAT_WS(' ',nombre,apellido1,apellido2) as nombre_encargado FROM usuario WHERE id_usuario=" . $contrato->fields['id_usuario_responsable'];
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					list($nombre_encargado) = mysql_fetch_array($resp);
				}

				$html = str_replace('%encargado_valor%', $nombre_encargado, $html);
				$html = str_replace('%factura%', empty($this->fields['documento']) ? '' : __('Factura'), $html);

				if (empty($this->fields['documento'])) {
					$html = str_replace('%pctje_blr%', '33%', $html);
					$html = str_replace('%FACTURA_NUMERO%', '', $html);
					$html = str_replace('%NUMERO_FACTURA%', '', $html);
				} else {
					$html = str_replace('%pctje_blr%', '25%', $html);
					$html = str_replace('%FACTURA_NUMERO%', $this->GenerarDocumento($parser, 'FACTURA_NUMERO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					$html = str_replace('%NUMERO_FACTURA%', $this->GenerarDocumento($parser, 'NUMERO_FACTURA', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				}

				$html = str_replace('%factura_nro%', empty($this->fields['documento']) ? '' : __('Factura') . ' ' . __('N°'), $html);
				$html = str_replace('%cobro_nro%', __('Carta') . ' ' . __('N°'), $html);
				$html = str_replace('%nro_cobro%', $this->fields['id_cobro'], $html);
				$html = str_replace('%cobro_factura_nro%', empty($this->fields['documento']) ? '' : $this->fields['documento'], $html);
				$html = str_replace('%nro_factura%', empty($this->fields['documento']) ? '' : $this->fields['documento'], $html);
				$nuevomodulofactura = Conf::GetConf($this->sesion, 'NuevoModuloFactura');
				$facturasRS = $this->ArrayFacturasDelContrato;

				foreach ($facturasRS as $factura => $datos) {
					if ($datos[0]['id_cobro'] != $this->fields['id_cobro']) {
						unset($facturasRS[$factura]);
					}
				}

				$html = str_replace('%lista_facturas%', implode(', ', array_keys($facturasRS)), $html);
				$html = str_replace('%modalidad%', $this->fields['opc_ver_modalidad'] == 1 ? __('Modalidad') : '', $html);
				$html = str_replace('%tipo_honorarios%', $this->fields['opc_ver_modalidad'] == 1 ? __('Tipo de Honorarios') : '', $html);
				if ($this->fields['forma_cobro'] == 'RETAINER' && $contrato->fields['glosa_contrato'] != '') {
					$html = str_replace('%valor_modalidad_tyc%', $this->fields['opc_ver_modalidad'] == 1 ? __($contrato->fields['glosa_contrato']) : '', $html);
				} else {
					$html = str_replace('%valor_modalidad_tyc%', $this->fields['opc_ver_modalidad'] == 1 ? __($this->fields['forma_cobro']) : '', $html);
				}

				$html = str_replace('%valor_modalidad%', $this->fields['opc_ver_modalidad'] == 1 ? __($this->fields['forma_cobro']) : '', $html);

				//La siguiente cosulta extrae la descripcion de forma_cobro de la tabla prm_forma_cobro

				$query = "SELECT descripcion FROM prm_forma_cobro WHERE forma_cobro = '" . $this->fields['forma_cobro'] . "'";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				$row = mysql_fetch_row($resp);
				$descripcion_forma_cobro = $row[0];

				if ($this->fields['forma_cobro'] == 'TASA') {
					$html = str_replace('%valor_modalidad_ucfirst%', $this->fields['opc_ver_modalidad'] == 1 ? __('Tarifa por Hora') : '', $html);
				} else {
					$html = str_replace('%valor_modalidad_ucfirst%', $this->fields['opc_ver_modalidad'] == 1 ? __($descripcion_forma_cobro) : '', $html);
				}

				$html = str_replace('%detalle_modalidad%', $this->fields['opc_ver_modalidad'] == 1 && $this->fields['forma_cobro'] != 'ESCALONADA' ? $detalle_modalidad : '', $html);
				$html = str_replace('%detalle_modalidad_lowercase%', $this->fields['opc_ver_modalidad'] == 1 && $this->fields['forma_cobro'] != 'ESCALONADA' ? $detalle_modalidad_lowercase : '', $html);

				if ($this->fields['forma_cobro'] == 'RETAINER' && $contrato->fields['glosa_contrato'] != '') {
					$html = str_replace('%detalle_modalidad_tyc%', '', $html);
				} else {
					$html = str_replace('%detalle_modalidad_tyc%', $this->fields['opc_ver_modalidad'] == 1 && $this->fields['forma_cobro'] != 'ESCALONADA' ? $detalle_modalidad : '', $html);
				}

				$html = str_replace('%tipo_tarifa%', $this->fields['opc_ver_modalidad'] == 1 ? $detalle_modalidad : '', $html);
				$html = str_replace('%periodo%', (($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') and ( $this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '')) ? '' : __('Periodo'), $html);
				$html = str_replace('%periodo_cobro%', (($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') and ( $this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '')) ? '' : __('Periodo Cobro'), $html);
				$html = str_replace('%valor_periodo_ini%', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? '' : Utiles::sql2fecha($this->fields['fecha_ini'], $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%valor_periodo_fin%', ($this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '') ? '' : __('hasta') . ' ' . Utiles::sql2fecha($this->fields['fecha_fin'], $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%fecha_ini%', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? '' : __('Fecha desde'), $html);
				$html = str_replace('%fecha_ini_primer_trabajo%', __('Fecha desde'), $html);
				$html = str_replace('%nota_transferencia%', '<u>' . __('Nota') . '</u>:' . __('Por favor recuerde incluir cualquier tarifa o ') . __('cobro') . __(' por transferencia por parte de vuestro banco con el fin de evitar cargos en las próximas facturas.'), $html);

				/*
				 * 	Se saca la fecha inicial según el primer trabajo
				 * 	esto es especial para LyR
				 */

				$query = "SELECT fecha FROM trabajo WHERE id_cobro='" . $this->fields['id_cobro'] . "' AND visible='1' ORDER BY fecha LIMIT 1";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

				//acá se calcula si hay trabajos o no (porque si no sale como fecha 1969)
				if (mysql_num_rows($resp) > 0) {
					list($fecha_primer_trabajo) = mysql_fetch_array($resp);
				} else {
					$fecha_primer_trabajo = $this->fields['fecha_fin'];
				}

				//También se saca la fecha final según el último trabajo
				$query = "SELECT LAST_DAY(fecha) FROM trabajo WHERE id_cobro='" . $this->fields['id_cobro'] . "' AND visible='1' ORDER BY fecha DESC LIMIT 1";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				//acá se calcula si hay trabajos o no (porque si no sale como fecha 1969)
				if (mysql_num_rows($resp) > 0) {
					list($fecha_ultimo_trabajo) = mysql_fetch_array($resp);
				} else {
					$fecha_ultimo_trabajo = $this->fields['fecha_fin'];
				}

				$fecha_inicial_primer_trabajo = date('Y-m-01', strtotime($fecha_primer_trabajo));
				$fecha_final_ultimo_trabajo = date('Y-m-d', strtotime($fecha_ultimo_trabajo));

				if (Conf::GetConf($this->sesion, 'ResumenProfesionalVial')) {
					if ($lang == 'en') {
						$html = str_replace('%desde%', date('m/d/y', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? strtotime($fecha_inicial_primer_trabajo) : strtotime($this->fields['fecha_ini'])), $html);
						$html = str_replace('%hasta%', date('m/d/y', strtotime($this->fields['fecha_fin'])), $html);
					} else {
						$html = str_replace('%desde%', date('d-m-y', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? strtotime($fecha_inicial_primer_trabajo) : strtotime($this->fields['fecha_ini'])), $html);
						$html = str_replace('%hasta%', date('d-m-y', strtotime($this->fields['fecha_fin'])), $html);
					}
				}

				$html = str_replace('%valor_fecha_ini_primer_trabajo%', Utiles::sql2fecha($fecha_inicial_primer_trabajo, $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%valor_fecha_fin_ultimo_trabajo%', Utiles::sql2fecha($fecha_final_ultimo_trabajo, $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%valor_fecha_ini_o_primer_trabajo%', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? Utiles::sql2fecha($fecha_primer_trabajo, $idioma->fields['formato_fecha']) : Utiles::sql2fecha($this->fields['fecha_ini'], $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%valor_fecha_ini%', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? '' : Utiles::sql2fecha($this->fields['fecha_ini'], $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%fecha_fin%', ($this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '') ? '' : __('Fecha hasta'), $html);
				$html = str_replace('%valor_fecha_fin%', ($this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '') ? '' : Utiles::sql2fecha($this->fields['fecha_fin'], $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%horas%', __('Total Horas'), $html);
				$html = str_replace('%valor_horas%', $horas_cobrables . ':' . $minutos_cobrables, $html);

				if ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$html = str_replace('%DETALLE_COBRO_RETAINER%', $this->GenerarDocumento($parser, 'DETALLE_COBRO_RETAINER', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					$html = str_replace('%DETALLE_TARIFA_ADICIONAL%', $this->GenerarDocumento($parser, 'DETALLE_TARIFA_ADICIONAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%DETALLE_COBRO_RETAINER%', '', $html);
					$html = str_replace('%DETALLE_TARIFA_ADICIONAL%', '', $html);
				}

				if (Conf::GetConf($this->sesion, 'ResumenProfesionalVial')) {
					$html = str_replace('%honorarios%', __('Honorarios totales'), $html);

					if ($this->fields['opc_restar_retainer']) {
						$html = str_replace('%RESTAR_RETAINER%', $this->GenerarDocumento($parser, 'RESTAR_RETAINER', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					} else {
						$html = str_replace('%RESTAR_RETAINER%', '', $html);
					}

					$html = str_replace('%descuento%', __('Otros'), $html);
					$html = str_replace('%saldo%', __('Saldo por pagar'), $html);
					$html = str_replace('%equivalente%', __('Equivalente a'), $html);
				} else {
					$html = str_replace('%honorarios%', __('Honorarios'), $html);
				}

				if ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$html = str_replace('%honorarios_totales%', __('Honorarios Totales'), $html);
				} else {
					$html = str_replace('%honorarios_totales%', __('Honorarios'), $html);
				}

				$html = str_replace('%honorarios_mta%', __('Honorarios totales'), $html);
				$html = str_replace('%valor_honorarios_totales%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . number_format($this->fields['monto'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_honorarios_totales_moneda_total%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($this->fields['monto'], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%fees%', __('%fees%'), $html); //en vez de Legal Fee es Legal Fees en inglés
				$html = str_replace('%expenses%', __('%expenses%'), $html); //en vez de Disbursements es Expenses en inglés
				$html = str_replace('%total_honorarios%', __('Total Honorarios'), $html);

				$valor_trabajos_demo = number_format($this->fields['monto_trabajos'] - $this->fields['descuento'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');

				//variable que se usa para la nota de cobro de vial

				$monto_cobro_menos_monto_contrato_moneda_tarifa = number_format($this->fields['monto'] - ($this->fields['monto_contrato'] * $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');

				$html = str_replace('%valor_honorarios_demo%', $moneda->fields['simbolo'] . $this->espacio . number_format($valor_trabajos_demo, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				if (Conf::GetConf($this->sesion, 'ResumenProfesionalVial') && ( $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL' ) && $this->fields['opc_restar_retainer']) {
					$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'] . $this->espacio . number_format($monto_cobro_menos_monto_contrato_moneda_tarifa, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}

				if (Conf::GetConf($this->sesion, 'CalculacionCYC')) {
					$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto_subtotal'] - $this->fields['descuento'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				} else if (Conf::GetConf($this->sesion, 'UsarImpuestoSeparado') && $contrato->fields['usa_impuesto_separado']) {
					$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto_subtotal'] - $this->fields['descuento'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				} else {
					$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}

				$html = str_replace('%horas_decimales%', __('Horas'), $html);
				$minutos_decimal = $minutos_cobrables / 60;
				$duracion_decimal = $horas_cobrables + $minutos_decimal;
				$html = str_replace('%valor_horas_decimales%', number_format($duracion_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				if ($this->fields['tipo_cambio_moneda_base'] <= 0) {
					$tipo_cambio_cobro_moneda_base = 1;
				} else {
					$tipo_cambio_cobro_moneda_base = $this->fields['tipo_cambio_moneda_base'];
				}

				$en_pesos = $this->fields['monto'] * ($this->fields['tipo_cambio_moneda'] / $tipo_cambio_cobro_moneda_base);
				$aproximacion_monto = number_format($this->fields['monto'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$aproximacion_monto_cyc = number_format($this->fields['monto_subtotal'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$aproximacion_monto_trabajos_demo = number_format($this->fields['monto_trabajos'] - $this->fields['descuento'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$valor_trabajos_demo_moneda_total = $aproximacion_monto_trabajos_demo * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'];
				$total_en_moneda = $aproximacion_monto * ($this->fields['tipo_cambio_moneda'] / $tipo_cambio_cobro_moneda_base) / ($tipo_cambio_moneda_total / $tipo_cambio_cobro_moneda_base);
				$subtotal_en_moneda_cyc = $aproximacion_monto_cyc * ($this->fields['tipo_cambio_moneda'] / $tipo_cambio_cobro_moneda_base) / ($tipo_cambio_moneda_total / $tipo_cambio_cobro_moneda_base);

				if ($this->fields['id_moneda'] == 2 && $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'] == 0) {
					$descuento_cyc_approximacion = number_format($this->fields['descuento'], 2, '.', '');
				} else {
					$descuento_cyc_approximacion = number_format($this->fields['descuento'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				}

				$descuento_cyc = $descuento_cyc_approximacion * ($this->fields['tipo_cambio_moneda'] / $tipo_cambio_cobro_moneda_base) / ($tipo_cambio_moneda_total / $tipo_cambio_cobro_moneda_base);

				if (Conf::GetConf($this->sesion, 'CalculacionCYC')) {
					$impuestos_cyc_approximacion = number_format(($subtotal_en_moneda_cyc - $descuento_cyc) * ($this->fields['porcentaje_impuesto'] / 100), $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				} else {
					$impuestos_cyc_approximacion = number_format($this->fields['impuesto'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
					$impuestos_cyc_approximacion *= ($this->fields['tipo_cambio_moneda'] / $tipo_cambio_cobro_moneda_base) / ($tipo_cambio_moneda_total / $tipo_cambio_cobro_moneda_base);
				}

				$impuestos_cyc = $impuestos_cyc_approximacion;

				//Caso retainer menor de un valor y distinta tarifa (diferencia por decimales)
				if ((($this->fields['total_minutos'] / 60) < $this->fields['retainer_horas']) && ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') && $this->fields['id_moneda'] != $this->fields['id_moneda_monto']) {
					$total_en_moneda = $this->fields['monto'] * ($this->fields['tipo_cambio_moneda'] / $tipo_cambio_cobro_moneda_base) / ($tipo_cambio_moneda_total / $tipo_cambio_cobro_moneda_base);
				}

				//Caso flat fee
				if ($this->fields['forma_cobro'] == 'FLAT FEE' && $this->fields['id_moneda'] != $this->fields['id_moneda_monto'] && $this->fields['id_moneda_monto'] == $this->fields['opc_moneda_total'] && empty($this->fields['descuento'])) {
					$total_en_moneda = $this->fields['monto_contrato'];
				}

				$html = str_replace('%valor_honorarios_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($subtotal_en_moneda_cyc - $descuento_cyc, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idoma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_honorarios_monedabase%', $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] ? '' : $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($total_en_moneda, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_honorarios_monedabase_tyc%', $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] || ( $this->fields['id_moneda'] == 2 && $this->fields['codigo_idioma'] == 'en' ) ? '' : $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($total_en_moneda, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_honorarios_monedabase_demo%', $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] ? '' : $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($valor_trabajos_demo_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				$html = str_replace('%monedabase%', $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] ? '' : __('Equivalente a'), $html);
				$html = str_replace('%equivalente_a_la_fecha%', $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] ? '' : __('Equivalente a la fecha'), $html);

				#detalle total gastos
				$html = str_replace('%gastos%', __('Gastos'), $html);

				$query = "SELECT SQL_CALC_FOUND_ROWS * FROM cta_corriente WHERE id_cobro='" . $this->fields['id_cobro'] . "' AND (egreso > 0 OR ingreso > 0) AND cta_corriente.incluir_en_cobro = 'SI' ORDER BY fecha ASC";
				$lista_gastos = new ListaGastos($this->sesion, '', $query);

				$total_gastos_moneda = 0;

				for ($i = 0; $i < $lista_gastos->num; $i++) {

					$gasto = $lista_gastos->Get($i);

					if ($gasto->fields['egreso'] > 0) {
						$saldo = $gasto->fields['monto_cobrable'];
					} elseif ($gasto->fields['ingreso'] > 0) {
						$saldo = -$gasto->fields['monto_cobrable'];
					}

					$monto_gasto = $saldo;
					$saldo_moneda_total = $saldo * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']);

					if (Conf::GetConf($this->sesion, 'CalculacionCYC')) {
						$saldo_moneda_total = number_format($saldo_moneda_total, $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['cifras_decimales'], ".", "");
					}

					$total_gastos_moneda += $saldo_moneda_total;
				}

				if ($this->fields['monto_subtotal'] > 0) {
					$html = str_replace('%DETALLE_HONORARIOS%', $this->GenerarDocumento($parser, 'DETALLE_HONORARIOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%DETALLE_HONORARIOS%', '', $html);
				}

				if ($total_gastos_moneda > 0) {
					$html = str_replace('%DETALLE_GASTOS%', $this->GenerarDocumento($parser, 'DETALLE_GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%DETALLE_GASTOS%', '', $html);
				}

				if ($this->fields['monto_tramites'] > 0 || $this->fields['opc_mostrar_tramites_no_cobrables']) {
					$html = str_replace('%DETALLE_TRAMITES%', $this->GenerarDocumento($parser, 'DETALLE_TRAMITES', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%DETALLE_TRAMITES%', '', $html);
				}

				if (Conf::GetConf($this->sesion, 'CalculacionCYC')) {
					$total_gastos_moneda = round($total_gastos_moneda, $moneda_total->fields['cifras_decimales']);
				}

				$impuestos_total_gastos_moneda = round($total_gastos_moneda * ($this->fields['porcentaje_impuesto_gastos'] / 100), $moneda_total->fields['cifras_decimales']);

				#total nota cobro
				$total_cobro = $total_en_moneda + $total_gastos_moneda;
				$total_cobro_demo = number_format(number_format($this->fields['monto'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '') * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], '.', '') + number_format($this->fields['monto_gastos'], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], '.', '');
				$total_cobro_cyc = $subtotal_en_moneda_cyc + $total_gastos_moneda - $descuento_cyc;
				$iva_cyc = $impuestos_total_gastos_moneda + $impuestos_cyc;

				$html = str_replace('%total_cobro%', __('Total Cobro'), $html);
				$html = str_replace('%total_cobro_mta%', __('GRAN TOTAL'), $html);
				$html = str_replace('%total_cobro_cyc%', __('Honorarios y Gastos'), $html);

				$html = str_replace('%valor_uf%', __('Valor UF') . ' ' . date('d.m.Y'), $html);
				$html = str_replace('%iva_cyc%', __('IVA') . '(' . $this->fields['porcentaje_impuesto'] . '%)', $html);
				$html = str_replace('%honorarios_y_gastos%', '(' . __('Honorarios y Gastos') . ')', $html);
				$html = str_replace('%total_cyc%', __('Total'), $html);

				$html = str_replace('%valor_gastos%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($total_gastos_moneda, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_total_cobro_demo%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($total_cobro_demo, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_total_cobro_cyc%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($total_cobro_cyc, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_iva_cyc%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($iva_cyc, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idoma->fields['separador_miles']), $html);
				$html = str_replace('%valor_total_cyc%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($total_cobro_cyc + $iva_cyc, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_total_cobro%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($total_cobro, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				$html = str_replace('%valor_total_cobro_sin_simbolo%', number_format($total_cobro, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_uf%', __('Valor UF') . ' ' . date('d.m.Y'), $html);

				if ($this->fields['opc_ver_tipo_cambio'] == 0) {
					$html = str_replace('%glosa_tipo_cambio_moneda%', '', $html);
					$html = str_replace('%valor_tipo_cambio_moneda%', '', $html);
				} else {
					$html = str_replace('%glosa_tipo_cambio_moneda%', __('Tipo de Cambio'), $html);
					$html = str_replace('%valor_tipo_cambio_moneda%', $cobro_moneda->moneda[$this->fields['id_moneda_base']]['simbolo'] . $this->espacio . number_format($cobro_moneda->moneda[$moneda->fields['id_moneda']]['tipo_cambio'], 2, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}

				$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL%', $this->GenerarDocumentoComun($parser, 'DETALLE_COBRO_MONEDA_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);

				if ($this->fields['opc_ver_descuento']) {
					$html = str_replace('%DETALLE_COBRO_DESCUENTO%', $this->GenerarDocumento($parser, 'DETALLE_COBRO_DESCUENTO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					$html = str_replace('%DETALLE_COBRO_DESCUENTO_NUEVO%', $this->GenerarDocumentoComun($parser, 'DETALLE_COBRO_DESCUENTO_NUEVO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%DETALLE_COBRO_DESCUENTO%', '', $html);
					$html = str_replace('%DETALLE_COBRO_DESCUENTO_NUEVO%', '', $html);
				}

				global $x_resultados;
				$simbolo_moneda = $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'];
				$cifras_decimales = $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'];
				$separador_decimales = $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'];
				$html = str_replace('%honorarios_nuevo%', __('Honorarios'), $html);
				$html = str_replace('%valor_honorarios_nuevo%', $simbolo_moneda . $this->espacio . number_format($x_resultados['monto_honorarios'][$this->fields['opc_moneda_total']], $cifras_decimales, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				if ($this->fields['porcentaje_impuesto'] > 0 || $this->fields['porcentaje_impuesto_gastos'] > 0) {
					$html = str_replace('%IMPUESTO%', $this->GenerarDocumento($parser, 'IMPUESTO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					$html = str_replace('%IMPUESTO_HONORARIOS%', $this->GenerarDocumento2($parser, 'IMPUESTO_HONORARIOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					$html = str_replace('%IMPUESTO_GASTOS%', $this->GenerarDocumento2($parser, 'IMPUESTO_GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%IMPUESTO%', '', $html);
				}

				if (Conf::GetConf($this->sesion, 'ResumenProfesionalVial')) {
					$valor_bruto = $this->fields['monto'];

					if (Conf::GetConf($this->sesion, 'UsarImpuestoSeparado') && $contrato->fields['usa_impuesto_separado']) {
						$valor_bruto -= $this->fields['impuesto'];
					}

					$valor_bruto += $this->fields['descuento'];
					$monto_cobro_menos_monto_contrato_moneda_total = $monto_cobro_menos_monto_contrato_moneda_tarifa * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'];

					$html = str_replace('%valor_bruto%', $moneda->fields['simbolo'] . $this->espacio . number_format($valor_bruto, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					$html = str_replace('%valor_descuento%', '(' . $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['descuento'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ')', $html);

					if (( $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL' ) && $this->fields['opc_restar_retainer']) {
						$html = str_replace('%valor_equivalente%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($monto_cobro_menos_monto_contrato_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					} else {
						$html = str_replace('%valor_equivalente%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($total_en_moneda, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					}
				}

				$html = str_replace('%total_subtotal_cobro%', __('Total Cobro'), $html);

				if ($this->fields['id_carta'] == 3) {
					$html = str_replace('%nota_disclaimer%', __('Nota_Disclaimer'), $html);
				} else {
					$html = str_replace('%nota_disclaimer%', ' ', $html);
				}

				if ($this->fields['opc_ver_morosidad']) {
					$html = str_replace('%DETALLES_PAGOS%', $this->GenerarSeccionDetallePago($parser->tags['DETALLES_PAGOS'], $idioma), $html);
					$html = str_replace('%DETALLES_PAGOS_CONTRATO%', $this->GenerarSeccionDetallePagoContrato($parser->tags['DETALLES_PAGOS_CONTRATO'], $idioma), $html);
				} else {
					$html = str_replace('%DETALLES_PAGOS%', '', $html);
					$html = str_replace('%DETALLES_PAGOS_CONTRATO%', '', $html);
				}

				$and_saldo_adelantos = '';
				$and_saldo_gastos = '';
				$and_saldo_liquidaciones = '';

				if (Conf::GetConf($this->sesion, "SaldoClientePorAsunto")) {
					$and_saldo_adelantos = "AND d.id_contrato = '" . $asunto->fields['id_contrato'] . "' ";
					$and_saldo_gastos = "AND asunto.id_contrato = '" . $asunto->fields['id_contrato'] . "' ";
					$and_saldo_liquidaciones = "AND cobro.id_contrato = '" . $asunto->fields['id_contrato'] . "' ";
				}

				$query_saldo_adelantos = "SELECT SUM(- 1 * d.saldo_pago * (moneda_documento.tipo_cambio / moneda_base.tipo_cambio)) AS saldo_adelantos
										FROM documento d
									INNER JOIN prm_moneda moneda_documento ON d.id_moneda = moneda_documento.id_moneda
									INNER join prm_moneda moneda_base ON moneda_base.moneda_base = 1
									INNER JOIN cliente ON cliente.codigo_cliente = d.codigo_cliente
									LEFT JOIN contrato ON d.id_contrato = contrato.id_contrato
									LEFT JOIN usuario encargado_comercial ON encargado_comercial.id_usuario = contrato.id_usuario_responsable
										WHERE cliente.activo = 1
											AND (d.id_contrato IS NULL OR contrato.activo = 'SI')
											AND d.es_adelanto = 1
											AND d.saldo_pago < 0
											$and_saldo_adelantos
											AND d.pago_gastos = '1'
											AND d.codigo_cliente = '" . $this->fields['codigo_cliente'] . "' ";

				$resp_saldo_adelantos = mysql_query($query_saldo_adelantos, $this->sesion->dbh) or Utiles::errorSQL($query_saldo_adelantos, __FILE__, __LINE__, $this->sesion->dbh);
				list($monto_saldo_adelantos) = mysql_fetch_array($resp_saldo_adelantos);

				$query_saldo_gastos = "SELECT SUM( - 1 * cta_corriente.egreso)
												FROM cta_corriente
											INNER JOIN cliente ON cliente.codigo_cliente = cta_corriente.codigo_cliente
											INNER JOIN asunto ON asunto.codigo_asunto = cta_corriente.codigo_asunto
											INNER JOIN contrato ON asunto.id_contrato = contrato.id_contrato
											LEFT JOIN cobro ON cta_corriente.id_cobro = cobro.id_cobro
												WHERE cliente.activo = 1
													AND contrato.activo = 'SI'
													AND cta_corriente.cobrable = 1
													AND (cta_corriente.id_cobro IS NULL
													OR cobro.estado IN ('CREADO' , 'EN REVISION'))
													AND cta_corriente.id_neteo_documento IS NULL
													AND cta_corriente.documento_pago IS NULL
													$and_saldo_gastos
													AND cta_corriente.codigo_cliente = '" . $this->fields['codigo_cliente'] . "' ";

				$resp_saldo_gastos = mysql_query($query_saldo_gastos, $this->sesion->dbh) or Utiles::errorSQL($query_saldo_gastos, __FILE__, __LINE__, $this->sesion->dbh);
				list($monto_saldo_gastos) = mysql_fetch_array($resp_saldo_gastos);

				$query_saldo_liquidaciones = "SELECT SUM(- 1 * (d.saldo_honorarios + d.saldo_gastos) * (tipo_cambio_documento.tipo_cambio / tipo_cambio_base.tipo_cambio)) AS saldo_liquidaciones
								FROM documento d
							INNER JOIN cobro ON cobro.id_cobro = d.id_cobro
							INNER JOIN prm_moneda moneda_documento ON d.id_moneda = moneda_documento.id_moneda
							INNER JOIN cobro_moneda tipo_cambio_documento ON tipo_cambio_documento.id_moneda = moneda_documento.id_moneda AND tipo_cambio_documento.id_cobro = cobro.id_cobro
							INNER JOIN prm_moneda moneda_base ON moneda_base.moneda_base = 1
							INNER JOIN cobro_moneda tipo_cambio_base ON tipo_cambio_base.id_moneda = moneda_base.id_moneda AND tipo_cambio_base.id_cobro = cobro.id_cobro
							INNER JOIN contrato ON contrato.id_contrato = cobro.id_contrato
							INNER JOIN cliente ON cliente.codigo_cliente = d.codigo_cliente
								WHERE
									cliente.activo = 1
									AND contrato.activo = 'SI'
									AND d.tipo_doc = 'N'
									AND cobro.estado NOT IN ('CREADO' , 'EN REVISION', 'INCOBRABLE')
									$and_saldo_liquidaciones
									AND cobro.incluye_gastos = '1'
									AND cobro.incluye_honorarios = '0'
									AND d.saldo_gastos > 0
									AND d.codigo_cliente = '" . $this->fields['codigo_cliente'] . "' ";

				$resp_saldo_liquidaciones = mysql_query($query_saldo_liquidaciones, $this->sesion->dbh) or Utiles::errorSQL($query_saldo_liquidaciones, __FILE__, __LINE__, $this->sesion->dbh);
				list($monto_saldo_liquidaciones) = mysql_fetch_array($resp_saldo_liquidaciones);

				$monto_saldo_cliente = $monto_saldo_adelantos + $monto_saldo_gastos + $monto_saldo_liquidaciones;

				$monto_saldo_moneda_impresion = UtilesApp::CambiarMoneda($monto_saldo_cliente, 1, 0, $x_resultados['tipo_cambio_opc_moneda_total'], $x_resultados['cifras_decimales_opc_moneda_total']);

				if ($monto_saldo_cliente < 0) {
					$texto_saldo_favor_o_contra = 'Saldo en contra';
				} else {
					$texto_saldo_favor_o_contra = 'Saldo a favor';
				}

				$monto_saldo_liquidaciones = $moneda_total->fields['simbolo'] . ' ' . number_format($monto_saldo_liquidaciones, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
				$monto_saldo_adelantos = $moneda_total->fields['simbolo'] . ' ' . number_format($monto_saldo_adelantos, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
				$monto_saldo_final = $moneda_total->fields['simbolo'] . ' ' . number_format($monto_saldo_moneda_impresion, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);

				$html = str_replace('%texto_saldo_liquidaciones%', __('Saldo liquidaciones'), $html);
				$html = str_replace('%monto_saldo_liquidaciones%', $monto_saldo_liquidaciones, $html);

				$html = str_replace('%texto_saldo_adelantos%', __('Saldo adelantos'), $html);
				$html = str_replace('%monto_saldo_adelantos%', $monto_saldo_adelantos, $html);

				$html = str_replace('%texto_saldo_favor_o_contra%', $texto_saldo_favor_o_contra, $html);
				$html = str_replace('%monto_saldo_final%', $monto_saldo_final, $html);

				list($total_parte_entera, $total_parte_decimal) = explode('.', $x_resultados['monto_total_cobro'][$this->fields['opc_moneda_total']]);

				if (strlen($total_parte_decimal) == '2') {
					$fix_decimal = '1';
				} else {
					$fix_decimal = '10';
				}

				$Numbers_Words = new Numbers_Words();

				if ($lang == 'es') {

					$glosa_moneda_plural_lang = $moneda_total->fields['glosa_moneda_plural'];

					if (empty($total_parte_decimal)) {
						$monto_palabra_parte_entera = strtoupper($Numbers_Words->toWords($total_parte_entera, 'es'));
						$monto_total_palabra = $monto_palabra_parte_entera . ' ' . mb_strtoupper($glosa_moneda_plural_lang);
						$monto_total_palabra_cero_cien = $monto_palabra_parte_entera . ' ' . mb_strtoupper($glosa_moneda_plural_lang) . ' CON 00/100 CENTAVOS';
					} else {
						$monto_palabra_parte_entera = strtoupper($Numbers_Words->toWords($total_parte_entera, 'es'));
						$monto_palabra_parte_decimal = strtoupper($Numbers_Words->toWords($total_parte_decimal * $fix_decimal, 'es'));
						$monto_total_palabra = $monto_palabra_parte_entera . ' ' . mb_strtoupper($glosa_moneda_plural_lang, 'UTF-8') . ' CON ' . $monto_palabra_parte_decimal . ' ' . 'CENTAVOS';
						$monto_total_palabra_cero_cien = $monto_palabra_parte_entera . ' ' . mb_strtoupper($glosa_moneda_plural_lang) . ' CON ' . $total_parte_decimal * $fix_decimal . '/100 CENTAVOS';
					}
				} else {

					$glosa_moneda_plural_lang = $moneda_total->fields['glosa_moneda_plural_lang'];

					if (empty($total_parte_decimal)) {
						$monto_palabra_parte_entera = strtoupper($Numbers_Words->toWords($total_parte_entera, 'en_US'));
						$monto_total_palabra = $monto_palabra_parte_entera . ' ' . mb_strtoupper($glosa_moneda_plural_lang);
						$monto_total_palabra_cero_cien = $monto_palabra_parte_entera . ' ' . mb_strtoupper($glosa_moneda_plural_lang) . ' CON 00/100 CENTAVOS';
					} else {
						$monto_palabra_parte_entera = strtoupper($Numbers_Words->toWords($total_parte_entera, 'en_US'));
						$monto_palabra_parte_decimal = strtoupper($Numbers_Words->toWords($total_parte_decimal, 'en_US'));
						$monto_total_palabra = $monto_palabra_parte_entera . ' ' . mb_strtoupper($glosa_moneda_plural_lang, 'UTF-8') . ' WITH ' . $monto_palabra_parte_decimal . ' ' . 'CENTS';
						$monto_total_palabra_cero_cien = $monto_palabra_parte_entera . ' ' . mb_strtoupper($glosa_moneda_plural_lang, 'UTF-8') . ' WITH ' . $total_parte_decimal * $fix_decimal . '/100 CENTS';
					}
				}

				$html = str_replace('%monto_total_palabra_cero_cien%', $monto_total_palabra_cero_cien, $html);
				$html = str_replace('%monto_total_palabra%', $monto_total_palabra, $html);


				break;

			case 'RESTAR_RETAINER': //GenerarDocumento

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

			case 'DETALLE_COBRO_RETAINER': //GenerarDocumento
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

			case 'DETALLE_TARIFA_ADICIONAL': //GenerarDocumento
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

			case 'FACTURA_NUMERO': //GenerarDocumento
				$html = str_replace('%factura_nro%', __('Factura') . ' ' . __('N°'), $html);
				break;

			case 'NUMERO_FACTURA': //GenerarDocumento
				$html = str_replace('%nro_factura%', $this->fields['documento'], $html);
				break;

			case 'DETALLE_HONORARIOS': //GenerarDocumento

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
					$html = str_replace('%DETALLE_COBRO_DESCUENTO%', $this->GenerarDocumento($parser, 'DETALLE_COBRO_DESCUENTO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					$html = str_replace('%DETALLE_COBRO_DESCUENTO_NUEVO%', $this->GenerarDocumentoComun($parser, 'DETALLE_COBRO_DESCUENTO_NUEVO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%DETALLE_COBRO_DESCUENTO%', '', $html);
					$html = str_replace('%DETALLE_COBRO_DESCUENTO_NUEVO%', '', $html);
				}

				$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL%', $this->GenerarDocumentoComun($parser, 'DETALLE_COBRO_MONEDA_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);

				break;

			case 'DETALLE_GASTOS': //GenerarDocumento

				$html = str_replace('%gastos%', __('Gastos'), $html);

				$query = "SELECT SQL_CALC_FOUND_ROWS * FROM cta_corriente WHERE id_cobro='" . $this->fields['id_cobro'] . "' AND (egreso > 0 OR ingreso > 0) AND cta_corriente.incluir_en_cobro = 'SI' ORDER BY fecha ASC";
				$lista_gastos = new ListaGastos($this->sesion, '', $query);
				$total_gastos_moneda = 0;

				for ($i = 0; $i < $lista_gastos->num; $i++) {
					$gasto = $lista_gastos->Get($i);

					if ($gasto->fields['egreso'] > 0) {
						$saldo = $gasto->fields['monto_cobrable'];
					} elseif ($gasto->fields['ingreso'] > 0) {
						$saldo = -$gasto->fields['monto_cobrable'];
					}

					$monto_gasto = $saldo;
					$saldo_moneda_total = $saldo * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']);
					$total_gastos_moneda += $saldo_moneda_total;
				}

				if (Conf::GetConf($this->sesion, 'CalculacionCYC')) {
					$total_gastos_moneda = round($total_gastos_moneda, $moneda_total->fields['cifras_decimales']);
				}

				$impuestos_total_gastos_moneda = round($total_gastos_moneda * ($this->fields['porcentaje_impuesto_gastos'] / 100), $moneda_total->fields['cifras_decimales']);

				$html = str_replace('%valor_gastos%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($total_gastos_moneda, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			case 'DETALLE_TRAMITES': //GenerarDocumento

				$html = str_replace('%tramites%', __('Trámites'), $html);
				$html = str_replace('%tramites_castropal%', __('Otros Servicios'), $html);
				$aproximacion_tramites = number_format($this->fields['monto_tramites'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$valor_tramites = $aproximacion_tramites * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'];

				$html = str_replace('%valor_tramites%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($valor_tramites, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			case 'DETALLE_COBRO_DESCUENTO': //GenerarDocumento

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
				$html = str_replace('%valor_honorarios_con_descuento%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%total_honorarios%', __('Total Honorarios'), $html);

				break;

			case 'RESUMEN_CAP': //GenerarDocumento

				$monto_restante = $this->fields['monto_contrato'] - ( $this->TotalCobrosCap() + ($this->fields['monto_trabajos'] - $this->fields['descuento']) * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['tipo_cambio'] );

				$html = str_replace('%cap%', __('Total CAP'), $html);

				$html = str_replace('%valor_cap%', $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['simbolo'] . $this->espacio . $this->fields['monto_contrato'], $html);
				$html = str_replace('%valor_restante%', $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['simbolo'] . $this->espacio . number_format($monto_restante, $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				$html = str_replace('%COBROS_DEL_CAP%', $this->GenerarDocumento($parser, 'COBROS_DEL_CAP', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%restante%', __('Monto restante'), $html);

				break;

			case 'COBROS_DEL_CAP': //GenerarDocumento

				$row_tmpl = $html;
				$html = '';

				$query = "SELECT cobro.id_cobro, (monto_trabajos*cm2.tipo_cambio)/cm1.tipo_cambio
										FROM cobro
										JOIN contrato ON cobro.id_contrato=contrato.id_contrato
										JOIN cobro_moneda as cm1 ON cobro.id_cobro=cm1.id_cobro AND cm1.id_moneda=contrato.id_moneda_monto
										JOIN cobro_moneda as cm2 ON cobro.id_cobro=cm2.id_cobro AND cm2.id_moneda=cobro.id_moneda
									 WHERE cobro.id_contrato=" . $this->fields['id_contrato'] . "
										 AND cobro.forma_cobro='CAP'";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

				while (list($id_cobro, $monto_cap) = mysql_fetch_array($resp)) {
					$row = $row_tmpl;

					$row = str_replace('%numero_cobro%', __('Cobro') . ' ' . $id_cobro, $row);
					$row = str_replace('%valor_cap_del_cobro%', $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['simbolo'] . $this->espacio . number_format($monto_cap, $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

					$html .= $row;
				}

				break;

			case 'ASUNTOS': //GenerarDocumento

				$row_tmpl = $html;
				$html = '';

				for ($k = 0; $k < count($this->asuntos); $k++) {
					$asunto = new Asunto($this->sesion);
					$asunto->LoadByCodigo($this->asuntos[$k]);

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

					$query = "SELECT count(*) FROM tramite WHERE id_cobro=" . $this->fields['id_cobro'] . " AND codigo_asunto='" . $asunto->fields['codigo_asunto'] . "'";
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					list($cont_tramites) = mysql_fetch_array($resp);

					$query = "SELECT count(*) FROM trabajo WHERE id_cobro=" . $this->fields['id_cobro'] . " AND codigo_asunto='" . $asunto->fields['codigo_asunto'] . "' AND id_tramite=0";
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					list($cont_trabajos) = mysql_fetch_array($resp);

					$query = "SELECT count(*) FROM cta_corriente WHERE id_cobro=" . $this->fields['id_cobro'] . " AND codigo_asunto='" . $asunto->fields['codigo_asunto'] . "'";
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

					if (Conf::GetConf($this->sesion, 'GlosaAsuntoSinCodigo')) {
						$row = str_replace('%glosa_asunto%', $asunto->fields['glosa_asunto'], $row);
						$row = str_replace('%glosa_asunto_secundario%', $asunto->fields['glosa_asunto'], $row);
					} else {
						$row = str_replace('%glosa_asunto%', $asunto->fields['codigo_asunto'] . " - " . $asunto->fields['glosa_asunto'], $row);
						$row = str_replace('%glosa_asunto_secundario%', $asunto->fields['codigo_asunto_secundario'] . " - " . $asunto->fields['glosa_asunto'], $row);
					}

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
							$row = str_replace('%TRABAJOS_ENCABEZADO%', $this->GenerarDocumento($parser, 'TRABAJOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
							$row = str_replace('%TRABAJOS_FILAS%', $this->GenerarDocumento($parser, 'TRABAJOS_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
							$row = str_replace('%TRABAJOS_TOTAL%', $this->GenerarDocumento($parser, 'TRABAJOS_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
						} else {
							$row = str_replace('%espacio_trabajo%', '', $row);
							$row = str_replace('%servicios%', '', $row);
							$row = str_replace('%TRABAJOS_ENCABEZADO%', '', $row);
							$row = str_replace('%TRABAJOS_FILAS%', '', $row);
							$row = str_replace('%TRABAJOS_TOTAL%', '', $row);
						}

						$row = str_replace('%DETALLE_PROFESIONAL%', $this->GenerarDocumento($parser, 'DETALLE_PROFESIONAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
					} else {

						$row = str_replace('%espacio_trabajo%', '', $row);
						$row = str_replace('%DETALLE_PROFESIONAL%', '', $row);
						$row = str_replace('%servicios%', '', $row);
						$row = str_replace('%TRABAJOS_ENCABEZADO%', '', $row);
						$row = str_replace('%TRABAJOS_FILAS%', '', $row);
						$row = str_replace('%TRABAJOS_TOTAL%', '', $row);
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
						if ($cont_gastos > 0) {
							$row = str_replace('%GASTOS%', $this->GenerarDocumento($parser, 'GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
						} else {
							$row = str_replace('%GASTOS%', '', $row);
						}
					} else {
						$row = str_replace('%GASTOS%', $this->GenerarDocumento($parser, 'GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
					}

					#especial mb

					$row = str_replace('%codigo_asunto_mb%', __('Código M&B'), $row);

					if ($asunto->fields['trabajos_total_duracion'] > 0 || $asunto->fields['trabajos_total_duracion_trabajada'] > 0 || $cont_tramites > 0 || Conf::GetConf($this->sesion, 'MostrarAsuntosSinTrabajosGastosTramites')) {
						$html .= $row;
					}
				}
				break;

			case 'TRAMITES': //GenerarDocumento

				$row_tmpl = $html;
				$html = '';

				for ($k = 0; $k < count($this->asuntos); $k++) {
					$asunto = new Asunto($this->sesion);
					$asunto->LoadByCodigo($this->asuntos[$k]);

					unset($GLOBALS['profesionales']);
					$profesionales = array();

					unset($GLOBALS['resumen_profesionales']);
					$resumen_profesionales = array();

					unset($GLOBALS['totales']);
					$totales = array();
					$totales['tiempo_tramites'] = 0;
					$totales['tiempo_tramites_trabajado'] = 0;
					$totales['tiempo_tramites_retainer'] = 0;
					$totales['tiempo_tramites_flatfee'] = 0;
					$totales['tiempo_tramites_descontado'] = 0;
					$totales['valor_tramites'] = 0;
					$categoria_duracion_horas = 0;
					$categoria_duracion_minutos = 0;
					$categoria_valor = 0;
					$total_trabajos_categoria = '';
					$encabezado_trabajos_categoria = '';

					$query = "SELECT count(*) FROM CTA_CORRIENTE
									 WHERE id_cobro=" . $this->fields['id_cobro'];
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, $this->sesion->dbh);
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
					$row = str_replace('%glosa_asunto%', $asunto->fields['codigo_asunto'] . " - " . $asunto->fields['glosa_asunto'], $row);
					$row = str_replace('%glosa_asunto_sin_codigo%', $asunto->fields['glosa_asunto'], $row);
					$row = str_replace('%glosa_asunto_codigo_area%', $asunto->fields['codigo_asunto'] . '-' . sprintf("%02d", ($asunto->fields['id_area_proyecto'] - 1)) . " - " . $asunto->fields['glosa_asunto'], $row);
					$row = str_replace('%valor_codigo_asunto%', $asunto->fields['codigo_asunto'], $row);
					$row = str_replace('%codigo_cliente_secundario%', empty($cliente->fields['codigo_cliente_secundario']) ? '' : __('Código Cliente'), $row);
					$row = str_replace('%valor_codigo_cliente_secundario%', empty($cliente->fields['codigo_cliente_secundario']) ? '' : empty($cliente->fields['codigo_cliente_secundario']), $row);
					$row = str_replace('%contacto%', empty($asunto->fields['contacto']) ? '' : __('Contacto'), $row);
					$row = str_replace('%valor_contacto%', empty($asunto->fields['contacto']) ? '' : $asunto->fields['contacto'], $row);
					$row = str_replace('%servicios%', __('Servicios prestados'), $row);
					$row = str_replace('%registro%', __('Registro de Tiempo'), $row);
					$row = str_replace('%telefono%', empty($asunto->fields['fono_contacto']) ? '' : __('Teléfono'), $row);
					$row = str_replace('%valor_telefono%', empty($asunto->fields['fono_contacto']) ? '' : $asunto->fields['fono_contacto'], $row);

					// SECCION TRAMITES GENERADO EN FUNCION GenerarDcumentoComun
					$row = str_replace('%TRAMITES_ENCABEZADO%', $this->GenerarDocumentoComun($parser, 'TRAMITES_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
					$row = str_replace('%TRAMITES_FILAS%', $this->GenerarDocumentoComun($parser, 'TRAMITES_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
					$row = str_replace('%TRAMITES_TOTAL%', $this->GenerarDocumentoComun($parser, 'TRAMITES_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);

					$row = str_replace('%DETALLE_PROFESIONAL%', $this->GenerarDocumento($parser, 'DETALLE_PROFESIONAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);

					if (Conf::GetConf($this->sesion, 'ParafoGastosSoloSiHayGastos')) {
						if ($cont_gastos > 0) {
							$row = str_replace('%GASTOS%', $this->GenerarDocumento($parser, 'GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
						} else {
							$row = str_replace('%GASTOS%', '', $row);
						}
					} else {
						$row = str_replace('%GASTOS%', $this->GenerarDocumento($parser, 'GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
					}

					$row = str_replace('%codigo_asunto_mb%', __('Código M&B'), $row);

					if ($asunto->fields['trabajos_total_duracion'] > 0 || $asunto->fields['trabajos_total_duracion_trabajada'] > 0) {
						$html .= $row;
					}
				}
				break;

			case 'TRABAJOS_ENCABEZADO': //GenerarDocumento

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
				$html = str_replace('%staff%', __('Staff'), $html);
				$html = str_replace('%abogado%', __('Abogado'), $html);
				$html = str_replace('%abogado_raz%', __('abogado_raz'), $html);
				$html = str_replace('%tiempo_raz%', __('tiempo_raz'), $html);
				$html = str_replace('%duracion_cobrable%', __('Duración cobrable'), $html);
				$html = str_replace('%monto_total%', __('Monto total'), $html);
				$html = str_replace('%Total%', __('Total'), $html);

				if ($lang == 'es') {
					$html = str_replace('%id_asunto%', __('ID Asunto'), $html);
					$html = str_replace('%tarifa_hora%', __('Tarifa<br>Hora'), $html);
				} else {
					$html = str_replace('%id_asunto%', __('Matter <br> ID'), $html);
					$html = str_replace('%tarifa_hora%', __('Hourly<br> Rate'), $html);
				}

				$html = str_replace('%horas%', __('Horas'), $html);
				$html = str_replace('%monto%', __('Monto'), $html);

				if ($this->fields['opc_ver_columna_cobrable']) {
					$html = str_replace('%cobrable%', __('<td align="center" width="80">Cobrable</td>'), $html);  // tAndres Oestemer
				} else {
					$html = str_replace('%cobrable%', '', $html);
				}

				if ($this->fields['opc_ver_detalles_por_hora_categoria'] == 1) {
					$html = str_replace('%td_categoria%', '<td>&nbsp;</td>', $html);
				} else {
					$html = str_replace('%td_categoria%', '', $html);
				}

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

					$query = "SELECT
								CONCAT(usuario.nombre,' ',usuario.apellido1),
								trabajo.tarifa_hh
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
					$html = str_replace('%duracion_retainer%', __('Duración Retainer'), $html);
				} else {
					$html = str_replace('%td_retainer%', '', $html);
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

					if ($descontado) {
						$html = str_replace('%duracion_descontada_bmahj%', __('Hrs. Descontadas'), $html);
					} else {
						$html = str_replace('%duracion_descontada_bmahj%', '', $html);
					}

					$html = str_replace('%duracion_trabajada%', __('Duración trabajada'), $html);
					$html = str_replace('%duracion%', __('Duración cobrable'), $html);

					if ($descontado) {
						$html = str_replace('%duracion_descontada%', __('Duración descontada'), $html);
					} else {
						$html = str_replace('%duracion_descontada%', '', $html);
					}
				} else if ($this->fields['opc_ver_horas_trabajadas']) {

					$html = str_replace('%duracion_trabajada_bmahj%', __('Hrs. Trabajadas'), $html);
					$html = str_replace('%duracion_bmahj%', __('Hrs. Tarificadas'), $html);
					$html = str_replace('%tiempo%', __('Tiempo'), $html);
					$html = str_replace('%duracion_descontada_bmahj%', __('Hrs. Descontadas'), $html);

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

				if ($ImprimirValorTrabajo && $this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION') {
					$html = str_replace('%valor%', '', $html);
				} else {
					$html = str_replace('%valor%', __('Valor'), $html);
				}

				$html = str_replace('%valor_siempre%', __('Valor'), $html);
				$html = str_replace('%tarifa_fee%', __('%tarifa_fee%'), $html);

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

			case 'TRABAJOS_FILAS': //GenerarDocumento
				global $categoria_duracion_horas;
				global $categoria_duracion_minutos;
				global $categoria_valor;
				global $resumen_profesional_id_usuario;
				global $resumen_profesional_nombre;
				global $resumen_profesional_username;
				global $resumen_profesional_hrs_trabajadas;
				global $resumen_profesional_hrs_retainer;
				global $resumen_profesional_hrs_descontadas;
				global $resumen_profesional_hh;
				global $resumen_profesional_valor_hh;
				global $resumen_profesional_categoria;
				global $resumen_profesional_id_categoria;
				global $resumen_profesionales;

				$row_tmpl = $html;
				$html = '';
				$where_horas_cero = '';

				if ($lang == 'es') {
					$select_categoria = "prm_categoria_usuario.glosa_categoria AS categoria, prm_categoria_usuario.id_categoria_usuario";
				} else {
					$select_categoria = "IFNULL(prm_categoria_usuario.glosa_categoria_lang,prm_categoria_usuario.glosa_categoria) AS categoria, prm_categoria_usuario.id_categoria_usuario";
				}

				$join_categoria = "LEFT JOIN prm_categoria_usuario ON usuario.id_categoria_usuario=prm_categoria_usuario.id_categoria_usuario";
				$order_categoria = Conf::Read('OrdenTrabajosNotaCobro');

				if (Conf::GetConf($this->sesion, 'MostrarHorasCero')) {
					if ($this->fields['opc_ver_horas_trabajadas']) {
						$where_horas_cero = "AND trabajo.duracion > '0000-00-00 00:00:00'";
					} else {
						$where_horas_cero = "AND trabajo.duracion_cobrada > '0000-00-00 00:00:00'";
					}
				}

				if ($this->fields['opc_ver_valor_hh_flat_fee'] && $this->fields['forma_cobro'] != 'ESCALONADA')
					$dato_monto_cobrado = " ( trabajo.tarifa_hh * TIME_TO_SEC( trabajo.duracion_cobrada ) ) / 3600 ";
				else
					$dato_monto_cobrado = " trabajo.monto_cobrado ";

				if ($this->fields['opc_ver_cobrable']) {
					$and .= "";
				} else {
					$and .= "AND trabajo.visible = 1";
				}

				//Tabla de Trabajos.
				//se hace select a los visibles y cobrables para diferenciarlos, tambien se selecciona
				//la duracion retainer.
				$query = "SELECT SQL_CALC_FOUND_ROWS
								 trabajo.duracion_cobrada,
					       trabajo.duracion_retainer,
					       trabajo.descripcion,
					       trabajo.fecha,
					       trabajo.id_usuario,
					       {$dato_monto_cobrado} AS monto_cobrado,
					       trabajo.visible,
					       trabajo.cobrable,
					       trabajo.id_trabajo,
					       trabajo.tarifa_hh,
					       trabajo.codigo_asunto,
					       trabajo.solicitante,
					       CONCAT_WS(' ', nombre, apellido1) AS nombre_usuario,
					       usuario.username,
					       trabajo.duracion,
								 {$select_categoria}
					FROM trabajo
					LEFT JOIN usuario ON trabajo.id_usuario = usuario.id_usuario {$join_categoria}
					WHERE trabajo.id_cobro = '{$this->fields['id_cobro']}'
					    AND trabajo.codigo_asunto = '{$asunto->fields['codigo_asunto']}' {$and}
					    AND trabajo.id_tramite = 0
							{$where_horas_cero}
					ORDER BY {$order_categoria};";

				$lista_trabajos = new ListaTrabajos($this->sesion, '', $query);

				$asunto->fields['trabajos_total_duracion'] = 0;
				$asunto->fields['trabajos_total_valor'] = 0;
				$asunto->fields['trabajos_total_duracion_retainer'] = 0;

				for ($i = 0; $i < $lista_trabajos->num; $i++) {
					$trabajo = $lista_trabajos->Get($i);
					list($ht, $mt, $st) = explode(":", $trabajo->fields['duracion']);
					list($h, $m, $s) = explode(":", $trabajo->fields['duracion_cobrada']);
					list($h_retainer, $m_retainer, $s_retainer) = explode(":", $trabajo->fields['duracion_retainer']);
					$duracion_cobrada_decimal = $h + $m / 60 + $s / 3600;
					$asunto->fields['trabajos_total_duracion'] += $h * 60 + $m + $s / 60;
					$asunto->fields['trabajos_total_valor'] += $trabajo->fields['monto_cobrado'];
					$asunto->fields['trabajos_total_duracion_retainer'] += $h_retainer * 60 + $m_retainer + $s_retainer / 60;
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

					// Para mostrar un resumen de horas de cada profesional al principio del documento.
					for ($k = 0; $k < count($resumen_profesional_nombre); ++$k)
						if ($resumen_profesional_id_usuario[$k] == $trabajo->fields['id_usuario'])
							break;
					// Si el profesional no estaba en el resumen lo agregamos
					if ($k == count($resumen_profesional_nombre)) {
						$resumen_profesional_id_usuario[$k] = $trabajo->fields['id_usuario'];
						$resumen_profesional_nombre[$k] = $trabajo->fields['nombre_usuario'];
						$resumen_profesional_username[$k] = $trabajo->fields['username'];
						$resumen_profesional_hrs_trabajadas[$k] = 0;
						$resumen_profesional_hrs_retainer[$k] = 0;
						$resumen_profesional_hrs_descontadas[$k] = 0;
						$resumen_profesional_hh[$k] = 0;
						$resumen_profesional_valor_hh[$k] = $trabajo->fields['tarifa_hh'];
						$resumen_profesional_categoria[$k] = $trabajo->fields['categoria'];
						$resumen_profesional_id_categoria[$k] = $trabajo->fields['id_categoria_usuario'];
					}
					$resumen_profesional_hrs_trabajadas[$k] += $h + $m / 60 + $s / 3600;

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

					$row = $row_tmpl;

					if ($this->fields['opc_ver_detalles_por_hora_categoria'] == 1) {
						$row = str_replace('%td_categoria%', '<td>&nbsp;</td>', $row);
					} else {
						$row = str_replace('%td_categoria%', '', $row);
					}

					if ($this->fields['opc_ver_detalles_por_hora_tarifa'] == 1) {
						$row = str_replace('%td_tarifa%', '<td width="80" align="center">%tarifa%</td>', $row);
						$row = str_replace('%td_tarifa_ajustada%', '<td width="80" align="center">%tarifa%</td>', $row);
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

					$row = str_replace('%fecha%', Utiles::sql2fecha($trabajo->fields['fecha'], $idioma->fields['formato_fecha']), $row);
					if ($this->fields['estado'] == 'CREADO' || $this->fields['estado'] == 'EN REVISION') {
						$row = str_replace('%td_id_trabajo%', '<td align="center">%ntrabajo%</td>', $row);
					} else {
						$row = str_replace('%td_id_trabajo%', '', $row);
					}
					$row = str_replace('%ntrabajo%', $trabajo->fields['id_trabajo'], $row);
					$row = str_replace('%descripcion%', ucfirst(stripslashes(htmlentities($trabajo->fields['descripcion']))), $row);
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
					$row = str_replace('%valor_codigo_asunto%', $asunto->fields['codigo_asunto'], $row);
					//paridad
					$row = str_replace('%paridad%', $i % 2 ? 'impar' : 'par', $row);
					//Las iniciales fueron reemplazas por el username. Pivotal: 109198728
					$row = str_replace('%iniciales%', $trabajo->fields['username'], $row);

					$row = str_replace('%username%', $trabajo->fields['username'], $row);

					if ($this->fields['opc_ver_columna_cobrable']) {
						if ($trabajo->fields['cobrable'] == 1)
							$row = str_replace('%cobrable%', __('<td align="center">Si</td>'), $row);
						else
							$row = str_replace('%cobrable%', __('<td align="center">No</td>'), $row);
					} else
						$row = str_replace('%cobrable%', __(''), $row);

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
						$row = str_replace('%td_retainer%', '<td width="80" align="center">%duracion_retainer%</td>', $row);
						$row = str_replace('%duracion_retainer%', $h_retainer . ':' . sprintf("%02d", $m_retainer), $row);
					} else {
						$row = str_replace('%td_retainer%', '', $row);
					}

					if ($this->fields['forma_cobro'] == 'FLAT FEE') {
						$row = str_replace('%duracion_decimal_trabajada%', '', $row);
						$row = str_replace('%duracion_trabajada%', '', $row);
						$row = str_replace('%duracion_decimal_descontada%', '', $row);
						$row = str_replace('%duracion_descontada%', '', $row);
						if (!$this->fields['opc_ver_horas_trabajadas']) {
							$row = str_replace('%duracion_decimal%', number_format($duracion_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							$row = str_replace('%duracion%', $h . ':' . sprintf("%02d", $m), $row);
						} else {
							$row = str_replace('%duracion_decimal%', number_format($duracion_decimal_trabajada, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							$row = str_replace('%duracion%', $ht . ':' . $mt, $row);
						}
					}
					if ($ImprimirDuracionTrabajada && ( $this->fields['estado'] == 'CREADO' || $this->fields['estado'] == 'EN REVISION' )) {
						$row = str_replace('%duracion_decimal_trabajada%', number_format($duracion_decimal_trabajada, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						if ($horas_descontadas < 0 || $minutos_descontadas < 0)
							$row = str_replace('%duracion_trabajada%', $h . ':' . sprintf("%02d", $m), $row);
						else
							$row = str_replace('%duracion_trabajada%', $ht . ':' . sprintf("%02d", $mt), $row);
						if ($horas_descontadas < 0 || $minutos_descontadas < 0)
							$row = str_replace('%duracion_descontada%', '0:00', $row);
						else
							$row = str_replace('%duracion_descontada%', $horas_descontadas . ':' . sprintf("%02d", $minutos_descontadas), $row);
						$row = str_replace('%duracion_decimal_descontada%', number_format($duracion_decimal_descontada, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					}
					else if ($this->fields['opc_ver_horas_trabajadas']) {
						$row = str_replace('%duracion_decimal_trabajada%', number_format($duracion_decimal_trabajada, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						if ($horas_descontadas < 0 || $minutos_descontadas < 0) {
							$row = str_replace('%duracion_trabajada%', $h . ':' . sprintf("%02d", $m), $row);
							$row = str_replace('%duracion_descontada%', '0:00', $row);
						} else {
							$row = str_replace('%duracion_trabajada%', $ht . ':' . sprintf("%02d", $mt), $row);
							$row = str_replace('%duracion_descontada%', $horas_descontadas . ':' . sprintf("%02d", $minutos_descontadas), $row);
						}
						$row = str_replace('%duracion_decimal_descontada%', number_format($duracion_decimal_descontada, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					} else {
						$row = str_replace('%duracion_descontada%', '', $row);
						$row = str_replace('%duracion_decimal_descontada%', '', $row);
						$row = str_replace('%duracion_decimal_trabajada%', '', $row);
						$row = str_replace('%duracion_trabajada%', '', $row);
					}

					$row = str_replace('%duracion_decimal%', number_format($duracion_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					$row = str_replace('%duracion%', $h . ':' . $m, $row);

					$ImprimirValorTrabajo = Conf::GetConf($this->sesion, 'ImprimirValorTrabajo');

					if ($ImprimirValorTrabajo && $this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION') {
						$row = str_replace('%valor%', '', $row);
						$row = str_replace('%valor_cyc%', '', $row);
					} else {
						$row = str_replace('%valor%', number_format($trabajo->fields['monto_cobrado'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%valor_con_moneda%', $moneda->fields['simbolo'] . " " . number_format($trabajo->fields['monto_cobrado'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%valor_cyc%', number_format($trabajo->fields['monto_cobrado'] * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					}

					if ($this->fields['opc_ver_detalles_por_hora_importe'] == 1) {
						$row = str_replace('%td_importe%', '<td width="80" align="center">%valor_siempre%</td>', $row);
						$row = str_replace('%td_importe_ajustado%', '<td width="80" align="center">%valor_siempre%</td>', $row);
					} else {
						$row = str_replace('%td_importe%', '', $row);
						$row = str_replace('%td_importe_ajustado%', '', $row);
					}

					$row = str_replace('%valor_siempre%', number_format($trabajo->fields['monto_cobrado'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					$row = str_replace('%categoria_usuario%', '', $row);
					if (Conf::GetConf($this->sesion, 'OrdenarPorCategoriaUsuario')) {
						$trabajo_siguiente = $lista_trabajos->Get($i + 1);
						if (!empty($trabajo_siguiente->fields['id_categoria_usuario'])) {
							if ($trabajo->fields['id_categoria_usuario'] != $trabajo_siguiente->fields['id_categoria_usuario']) {
								$html3 = $parser->tags['TRABAJOS_TOTAL'];
								$html3 = str_replace('%glosa%', __('Total'), $html3);
								$categoria_duracion_horas += floor($categoria_duracion_minutos / 60);
								$categoria_duracion_minutos = round($categoria_duracion_minutos % 60);
								$html3 = str_replace('%duracion%', sprintf('%02d', $categoria_duracion_horas) . ':' . sprintf('%02d', $categoria_duracion_minutos), $html3);

								if (Conf::GetConf($this->sesion, 'NoImprimirValorTrabajo') && $this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION') {
									$html3 = str_replace('%valor%', '', $html3);
									$html3 = str_replace('%valor_cyc%', '', $html3);
								} else {
									$html3 = str_replace('%valor%', $moneda->fields['simbolo'] . $this->espacio . number_format($categoria_valor, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
									$html3 = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($categoria_valor * ( $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
								}

								$total_trabajos_categoria .= $html3;

								$html3 = $parser->tags['TRABAJOS_ENCABEZADO'];
								$html3 = str_replace('%duracion%', __('Duración'), $html3);
								$html3 = str_replace('%cobrable%', __('<td align="center" width="80">Cobrable</td>'), $html3);
								$html3 = str_replace('%fecha%', __('Fecha'), $html3);
								$html3 = str_replace('%descripcion%', __('Descripción'), $html3);
								$html3 = str_replace('%profesional%', __('Profesional'), $html3);
								$html3 = str_replace('%abogado%', __('Abogado'), $html3);

								$html3 = str_replace('%categoria_abogado%', __($trabajo_siguiente->fields['categoria']), $html3);
								if (Conf::GetConf($this->sesion, 'NoImprimirValorTrabajo') && $this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION') {
									$html3 = str_replace('%valor%', '', $html3);
									$html3 = str_replace('%valor_cyc%', '', $html3);
								} else {
									$html3 = str_replace('%valor%', __('Valor'), $html3);
									$html3 = str_replace('%valor_cyc%', __('Valor'), $html3);
								}
								$encabezado_trabajos_categoria .= $html3;

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
							$html3 = str_replace('%duracion%', sprintf('%02d', $categoria_duracion_horas) . ':' . sprintf('%02d', $categoria_duracion_minutos), $html3);
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
					}
					if (Conf::GetConf($this->sesion, 'SepararPorUsuario')) {
						$trabajo_siguiente = $lista_trabajos->Get($i + 1);
						if (!empty($trabajo_siguiente->fields['nombre_usuario'])) {
							if ($trabajo->fields['nombre_usuario'] != $trabajo_siguiente->fields['nombre_usuario']) {
								$html3 = $parser->tags['TRABAJOS_TOTAL'];
								$html3 = str_replace('%glosa%', __('Total'), $html3);
								$categoria_duracion_horas += floor($categoria_duracion_minutos / 60);
								$categoria_duracion_minutos = round($categoria_duracion_minutos % 60);
								$html3 = str_replace('%duracion%', sprintf('%02d', $categoria_duracion_horas) . ':' . sprintf('%02d', $categoria_duracion_minutos), $html3);


								if (Conf::GetConf($this->sesion, 'NoImprimirValorTrabajo') && $this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION') {
									$html3 = str_replace('%valor%', '', $html3);
									$html3 = str_replace('%valor_cyc%', '', $html3);
								} else {
									$html3 = str_replace('%valor%', $moneda->fields['simbolo'] . $this->espacio . number_format($categoria_valor, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
									$html3 = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($categoria_valor * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
								}

								$total_trabajos_categoria .= $html3;

								$html3 = $parser->tags['TRABAJOS_ENCABEZADO'];
								$html3 = str_replace('%duracion%', __('Duración'), $html3);
								$html3 = str_replace('%cobrable%', __('<td align="center" width="80">Cobrable</td>'), $html3);
								$html3 = str_replace('%fecha%', __('Fecha'), $html3);
								$html3 = str_replace('%descripcion%', __('Descripción'), $html3);
								$html3 = str_replace('%profesional%', __('Profesional'), $html3);
								$html3 = str_replace('%abogado%', __('Abogado'), $html3);
								$html3 = str_replace('%categoria_abogado%', __($trabajo_siguiente->fields['nombre_usuario']), $html3);
								$html3 = str_replace('%tarifa%', $moneda->fields['simbolo'] . $this->espacio . number_format($trabajo_siguiente->fields['tarifa_hh'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ' / hr.', $html3);

								if (Conf::GetConf($this->sesion, 'NoImprimirValorTrabajo') && $this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION') {
									$html3 = str_replace('%valor%', '', $html3);
									$html3 = str_replace('%valor_cyc%', '', $html3);
								} else {
									$html3 = str_replace('%valor%', __('Valor'), $html3);
									$html3 = str_replace('%valor_cyc%', __('Valor'), $html3);
								}

								$encabezado_trabajos_categoria .= $html3;

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
							$html3 = str_replace('%duracion%', sprintf('%02d', $categoria_duracion_horas) . ':' . sprintf('%02d', $categoria_duracion_minutos), $html3);
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
					}

					$html .= $row;
				}
				break;

			case 'TRABAJOS_TOTAL': //GenerarDocumento
				if ($this->fields['estado'] == 'CREADO' || $this->fields['estado'] == 'EN REVISION') {
					$html = str_replace('%td_id_trabajo%', '<td align="center">%ntrabajo%</td>', $html);
				} else {
					$html = str_replace('%td_id_trabajo%', '', $html);
				}
				$html = str_replace('%ntrabajo%', __('&nbsp;'), $html);

				$ImprimirDuracionTrabajada = Conf::GetConf($this->sesion, 'ImprimirDuracionTrabajada');

				$horas_cobrables = floor(($asunto->fields['trabajos_total_duracion']) / 60);
				$minutos_cobrables = sprintf("%02d", $asunto->fields['trabajos_total_duracion'] % 60);
				$duracion_retainer_total = ($asunto->fields['trabajos_total_duracion_retainer']) / 60;
				$minutos_decimal = $minutos_cobrables / 60;
				$duracion_decimal = $horas_cobrables + $minutos_decimal;

				$horas_trabajado = floor(($asunto->fields['trabajos_total_duracion_trabajada']) / 60);
				$minutos_trabajado = sprintf("%02d", $asunto->fields['trabajos_total_duracion_trabajada'] % 60);
				$minutos_decimal_trabajada = $minutos_trabajado / 60;
				$duracion_decimal_trabajada = $horas_trabajado + $minutos_decimal_trabajada;

				$horas_retainer = floor(($asunto->fields['trabajos_total_duracion_retainer']) / 60);
				$minutos_retainer = sprintf("%02d", $asunto->fields['trabajos_total_duracion_retainer'] % 60);

				if (($minutos_trabajado - $minutos_cobrables) < 0) {
					$horas_descontadas = $horas_trabajado - $horas_cobrables - 1;
					$minutos_descontadas = $minutos_trabajado - $minutos_cobrables + 60;
				} else {
					$horas_descontadas = $horas_trabajado - $horas_cobrables;
					$minutos_descontadas = $minutos_trabajado - $minutos_cobrables;
				}

				$minutos_decimal_descontadas = $minutos_descontadas / 60;
				$duracion_decimal_descontada = $horas_descontadas + $minutos_decimal_descontadas;

				if ($this->fields['opc_ver_solicitante']) {
					$html = str_replace('%td_solicitante%', '<td>&nbsp;</td>', $html);
				} else {
					$html = str_replace('%td_solicitante%', '', $html);
				}
				if ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$html = str_replace('%td_retainer%', '<td align="center">%duracion_retainer%</td>', $html);
					$html = str_replace('%duracion_retainer%', Utiles::Decimal2GlosaHora($duracion_retainer_total), $html);
				} else {
					$html = str_replace('%td_retainer%', '', $html);
				}

				if ($this->fields['forma_cobro'] == 'FLAT FEE') {
					$html = str_replace('%duracion_decimal_trabajada%', '', $html);
					$html = str_replace('%duracion_trabajada%', '', $html);
					$html = str_replace('%duracion_descontada%', '', $html);
					$html = str_replace('%duracion_decimal_descontada%', '', $html);
					if ($this->fields['opc_ver_horas_trabajadas']) {
						$html = str_replace('%duracion_decimal%', number_format($duracion_decimal_trabajada, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
						$html = str_replace('%duracion%', $horas_trabajado . ':' . $minutos_trabajado, $html);
					} else {
						$html = str_replace('%duracion_decimal%', number_format($duracion_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
						$html = str_replace('%duracion%', $horas_cobrables . ':' . $minutos_cobrables, $html);
					}
				}
				if ($ImprimirDuracionTrabajada && ( $this->fields['estado'] == 'CREADO' || $this->fields['estado'] == 'EN REVISION' )) {

					$html = str_replace('%duracion_decimal_trabajada%', number_format($duracion_decimal_trabajada, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					$html = str_replace('%duracion_trabajada%', $horas_trabajado . ':' . $minutos_trabajado, $html);
					if ($descontado) {
						$html = str_replace('%duracion_decimal_descontada%', number_format($duracion_decimal_descontada, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
						$html = str_replace('%duracion_descontada%', $horas_descontadas . ':' . sprintf("%02", $minutos_descontadas), $html);
					} else {
						$html = str_replace('%duracion_decimal_descontada%', '', $html);
						$html = str_replace('%duracion_descontada%', '', $html);
					}
				} else if ($this->fields['opc_ver_horas_trabajadas']) {
					$html = str_replace('%duracion_trabajada%', $horas_trabajado . ':' . $minutos_trabajado, $html);
					$html = str_replace('%duracion_decimal_trabajada%', number_format($duracion_decimal_trabajada, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					$html = str_replace('%duracion_descontada%', $horas_descontadas . ':' . sprintf("%02d", $minutos_descontadas), $html);
					$html = str_replace('%duracion_decimal_descontada%', number_format($duraoion_decimal_descontada, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				} else {
					$html = str_replace('%duracion_decimal_trabajada%', '', $html);
					$html = str_replace('%duracion_trabajada%', '', $html);
					$html = str_replace('%duracion_descontada%', '', $html);
					$html = str_replace('%duracion_decimal_descontada%', '', $html);
				}

				$html = str_replace('%glosa%', __('Total Trabajos'), $html);
				$html = str_replace('%duracion_decimal%', number_format($duracion_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%duracion%', $horas_cobrables . ':' . $minutos_cobrables, $html);

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

				if ($this->fields['opc_ver_detalles_por_hora_importe'] == 1) {
					$html = str_replace('%td_importe%', '<td width="80" align="center">%valor_siempre%</td>', $html);
					$html = str_replace('%td_importe_ajustado%', '<td width="80" align="center">%valor_siempre%</td>', $html);
				} else {
					$html = str_replace('%td_importe%', '', $html);
					$html = str_replace('%td_importe_ajustado%', '', $html);
				}

				$html = str_replace('%valor_siempre%', $moneda->fields['simbolo'] . $this->espacio . number_format($asunto->fields['trabajos_total_valor'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				if ($this->fields['opc_ver_detalles_por_hora_categoria'] == 1) {
					$html = str_replace('%td_categoria%', '<td>&nbsp;</td>', $html);
				} else {
					$html = str_replace('%td_categoria%', '', $html);
				}

				if ($this->fields['opc_ver_detalles_por_hora_tarifa'] == 1) {
					$html = str_replace('%td_tarifa%', '<td>&nbsp;</td>', $html);
					$html = str_replace('%td_tarifa_ajustada%', '<td>&nbsp;</td>', $html);
				} else {
					$html = str_replace('%td_tarifa%', '', $html);
					$html = str_replace('%td_tarifa_ajustada%', '', $html);
				}

				$html = str_replace('%total_raz%', __('total_raz'), $html);

				break;

			case 'DETALLE_PROFESIONAL': //GenerarDocumento

				if ($this->fields['opc_ver_profesional'] == 0)
					return '';
				$html = str_replace('%glosa_profesional%', __('Detalle profesional'), $html);
				$html = str_replace('%detalle_tiempo_por_abogado%', __('Detalle tiempo por abogado'), $html);
				$html = str_replace('%detalle_honorarios%', __('Detalle de honorarios profesionales'), $html);
				$html = str_replace('%PROFESIONAL_ENCABEZADO%', $this->GenerarSeccionResumenProfesional($parser, 'PROFESIONAL_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%PROFESIONAL_FILAS%', $this->GenerarDocumento($parser, 'PROFESIONAL_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%PROFESIONAL_TOTAL%', $this->GenerarDocumento($parser, 'PROFESIONAL_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);

				if ($this->fields['opc_ver_descuento']) {
					$html = str_replace('%DETALLE_COBRO_DESCUENTO%', $this->GenerarDocumento($parser, 'DETALLE_COBRO_DESCUENTO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
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

			case 'IMPUESTO': //GenerarDocumento
				$html = str_replace('%impuesto%', __('Impuesto') . ' (' . $this->fields['porcentaje_impuesto'] . '%)', $html);
				$html = str_replace('%impuesto_mta%', __('Impuesto') . ' (' . $this->fields['porcentaje_impuesto'] . '%)', $html);

				if ($this->fields['tipo_cambio_moneda_base'] <= 0) {
					$tipo_cambio_cobro_moneda_base = 1;
				} else {
					$tipo_cambio_cobro_moneda_base = $this->fields['tipo_cambio_moneda_base'];
				}

				$aproximacion_impuesto = number_format($this->fields['impuesto'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$impuesto_moneda_total = $aproximacion_impuesto * ($this->fields['tipo_cambio_moneda'] / $tipo_cambio_cobro_moneda_base) / ($tipo_cambio_moneda_total / $tipo_cambio_cobro_moneda_base) + $this->fields['impuesto_gastos'];

				$html = str_replace('%valor_impuesto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($impuesto_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				$impuesto_solo_honorarios = $x_resultados['monto_iva_hh'][$this->fields['opc_moneda_total']];

				$html = str_replace('%valor_impuesto_honorarios%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($impuesto_solo_honorarios, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				break;

			case 'PROFESIONAL_FILAS': //GenerarDocumento
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
						if ($this->fields['opc_ver_detalles_por_hora_tarifa'] == 1) {
							$row = str_replace('%td_tarifa%', '<td>&nbsp;</td>', $row);
							$row = str_replace('%td_tarifa_ajustada%', '<td>&nbsp;</td>', $row);
						} else {
							$row = str_replace('%td_tarifa%', '', $row);
							$row = str_replace('%td_tarifa_ajustada%', '', $row);
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
						}
						else {
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

						if ($segundos_cobrables) { // Se usan solo para el cobro prorrateado.
							$resumen_profesional_hh[$k] += $segundos_cobrables / 3600;
						}
						if ($flatfee) {
							$resumen_profesional_hh[$k] = 0;
						}
					}
				}
				break;

			case 'PROFESIONAL_TOTAL': //GenerarDocumento
				$retainer = false;
				$descontado = false;
				$flatfee = false;
				if (is_array($profesionales)) {
					foreach ($profesionales as $prof => $data) {
						if ($data['retainer'] > 0)
							$retainer = true;
						if ($data['descontado'] > 0)
							$descontado = true;
						if ($data['flatfee'] > 0)
							$flatfee = true;
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
				//$html = str_replace('%horas_cobrables%',$horas_trabajadas.':'.$minutos_trabajadas,$html);

				if ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL')
					$html = str_replace('%DETALLE_PROFESIONAL_RETAINER%', $this->GenerarDocumento($parser, 'DETALLE_PROFESIONAL_RETAINER', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
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

				$minutos_decimal = $minutos_cobrables / 60;
				$duracion_decimal = $horas_cobrables + $minutos_decimal;

				$html = str_replace('%total_honorarios%', $flatfee ? $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto_subtotal'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) : $moneda->fields['simbolo'] . $this->espacio . number_format($totales['valor'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%horas%', number_format($duracion_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%horas_mb%', number_format($duracion_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			case 'DETALLE_PROFESIONAL_RETAINER': //GenerarDocumento
				$html = str_replace('%retainer%', __('Retainer'), $html);
				$html = str_replace('%valor_retainer%', $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['simbolo'] . $this->espacio . number_format($this->fields['monto_contrato'], $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			/*
			  GASTOS -> esto s?lo lista los gastos agregados al cobro obteniendo un total
			 */
			case 'GASTOS': //GenerarDocumento
				if ($this->fields['opc_ver_gastos'] == 0)
					return '';

				$html = str_replace('%glosa_gastos%', __('Gastos'), $html);
				$html = str_replace('%expenses%', __('%expenses%'), $html); //en vez de Disbursements es Expenses en ingl?s
				$html = str_replace('%detalle_gastos%', __('Detalle de gastos'), $html);
				$html = str_replace('%detalle_gastos_raz%', __('detalledegastos'), $html);
				if ($lang == 'es') {
					$html = str_replace('%glosa_gasto%', __('GASTOS'), $html);
				} else {
					$html = str_replace('%glosa_gasto%', __('EXPENSES'), $html);
				}
				$html = str_replace('%GASTOS_ENCABEZADO%', $this->GenerarDocumento($parser, 'GASTOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%GASTOS_FILAS%', $this->GenerarDocumento($parser, 'GASTOS_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%GASTOS_TOTAL%', $this->GenerarDocumento($parser, 'GASTOS_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				break;

			case 'GASTOS_ENCABEZADO': //GenerarDocumento
				$html = str_replace('%glosa_gastos%', __('Gastos'), $html);
				$html = str_replace('%descripcion_gastos%', __('Descripción de Gastos'), $html);
				$html = str_replace('%fecha%', __('Fecha'), $html);
				$html = str_replace('%num_doc%', __('N° Documento'), $html);
				$html = str_replace('%tipo_gasto%', __('Tipo'), $html);
				$html = str_replace('%descripcion%', __('Descripción'), $html);
				$html = str_replace('%monto_original%', __('Monto'), $html);
				$html = str_replace('%ordenado_por%', __('Ordenado<br>Por'), $html);
				$html = str_replace('%monto_moneda_total%', __('Monto') . ' (' . $moneda_total->fields['simbolo'] . ')', $html);

				if ($lang == 'es') {
					$html = str_replace('%asunto_id%', __('ID<br>Asunto'), $html);
				} else {
					$html = str_replace('%asunto_id%', __('Matter<br>ID'), $html);
				}

				$html = str_replace('%monto_impuesto_total%', '', $html);
				$html = str_replace('%monto_moneda_total_con_impuesto%', '', $html);

				if ($this->fields['opc_ver_solicitante']) {
					$html = str_replace('%solicitante%', __('Ordenado<br>Por'), $html);
				} else {
					$html = str_replace('%solicitante%', '', $html);
				}

				if (Conf::GetConf($this->sesion, 'MostrarProveedorenGastos')) {
					$html = str_replace('%proveedor%', __('Proveedor'), $html);
				} else {
					$html = str_replace('%proveedor%', '', $html);
				}
				break;

			case 'GASTOS_FILAS': //GenerarDocumento
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
					$row = str_replace('%valor_codigo_asunto%', $gasto->fields['codigo_asunto'], $row);
					$row = str_replace('%fecha%', Utiles::sql2fecha($gasto->fields['fecha'], $idioma->fields['formato_fecha']), $row);
					$row = str_replace('%num_doc%', $gasto->fields['numero_documento'], $row);
					$row = str_replace('%tipo_gasto%', $gasto->fields['tipo_gasto'], $row);

					if (substr($gasto->fields['descripcion'], 0, 41) == 'Saldo aprovisionado restante tras Cobro #') {
						$row = str_replace('%descripcion%', __('Saldo aprovisionado restante tras Cobro #') . htmlentities(substr($gasto->fields['descripcion'], 42)), $row);
						$row = str_replace('%descripcion_b%', __('Saldo aprovisionado restante tras Cobro #') . htmlentities(substr($gasto->fields['descripcion'], 42)), $row);
					} else {
						$row = str_replace('%descripcion%', htmlentities(__($gasto->fields['descripcion'])), $row);
						$row = str_replace('%descripcion_b%', htmlentities(__($gasto->fields['descripcion'])), $row); #Ojo, este no deber?a existir
					}

					$row = str_replace('%monto_original%', $cobro_moneda->moneda[$gasto->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($monto_gasto, $cobro_moneda->moneda[$gasto->fields['id_moneda']]['cifras_decimales'], $cobro_moneda->moneda[$gasto->fields['id_moneda']]['separador_decimales'], $cobro_moneda->moneda[$gasto->fields['id_moneda']]['separador_miles']), $row);
					$row = str_replace('%monto_moneda_total%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($saldo_moneda_total, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

					$row = str_replace('%monto_moneda_total_sin_simbolo%', number_format($saldo_moneda_total, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					$html .= $row;
				}

				$html = str_replace('%monto_impuesto_total%', '', $html);
				$html = str_replace('%monto_moneda_total_con_impuesto%', '', $html);

				$html = str_replace('%proveedor%', '', $html);

				$html = str_replace('%solicitante%', '', $html);

				break;

			case 'GASTOS_TOTAL': //GenerarDocumento
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
					$html = str_replace('%valor_total_monedabase%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($totales['total'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				} else {
					$html = str_replace('%glosa_total_moneda_base%', '&nbsp;', $html);
					$html = str_replace('%valor_total_moneda_base%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($totales['total'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					$html = str_replace('%valor_total_moneda_carta%', '&nbsp;', $html);
					$html = str_replace('%valor_total_monedabase%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($totales['total'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}

				$contr = new Contrato($this->sesion);
				$contr->Load($this->fields['id_contrato']);

				$gastos_moneda_total_contrato = ( $totales['total'] * ( $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio'])) / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'];

				$html = str_replace('%valor_total_moneda_carta%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($gastos_moneda_total_contrato, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				$html = str_replace('%valor_impuesto_monedabase%', '', $html);
				$html = str_replace('%valor_total_monedabase_con_impuesto%', '', $html);
				break;

			case 'TIPO_CAMBIO': //GenerarDocumento
				if ($this->fields['opc_ver_tipo_cambio'] == 0) {
					return '';
				}
				//Tipos de Cambio
				$html = str_replace('%titulo_tipo_cambio%', __('Tipos de Cambio'), $html);
				foreach ($cobro_moneda->moneda as $id => $moneda) {
					$html = str_replace("%glosa_moneda_id_$id%", __($moneda['glosa_moneda']), $html);
					$html = str_replace("%simbolo_moneda_id_$id%", $moneda['simbolo'], $html);
					$html = str_replace("%valor_moneda_id_$id%", number_format($moneda['tipo_cambio'], 2, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}
				break;

			/*
			  CTA_CORRIENTE -> nuevo tag para la representación de la cuenta corriente (gastos, provisiones)
			  aparecerá como Saldo Inicial; Movimientos del periodo; Saldo Periodo; Saldo Final
			 */
			case 'CTA_CORRIENTE': //GenerarDocumento
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
		}
		return $html;
	}

}
