<?php

class NotaCobroDocumento2 extends NotaCobroDocumento {

	protected $tiene_tag_asuntos_no_separados;

	function GenerarDocumento2($parser, $theTag = 'INFORME', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, &$idioma, & $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto, $mostrar_asuntos_cobrables_sin_horas = FALSE) {

		global $contrato;
		global $cobro_moneda;
		global $masi;
		global $x_detalle_profesional;
		global $x_resumen_profesional;
		global $x_factor_ajuste;
		global $x_resultados;
		global $x_cobro_gastos;

		$moneda_total = new Objeto($this->sesion, '', '', 'prm_moneda', 'id_moneda');
		$moneda_total->Load($this->fields['opc_moneda_total'] > 0 ? $this->fields['opc_moneda_total'] : 1);
		$this->fields['opc_mostrar_asuntos_cobrables_sin_horas'] = $mostrar_asuntos_cobrables_sin_horas ? TRUE : $this->fields['opc_mostrar_asuntos_cobrables_sin_horas'];

		if (!isset($parser->tags[$theTag])) {
			return;
		}

		$this->FillTemplateData($idioma, $moneda);
		$html = $this->RenderTemplate($parser->tags[$theTag]);
		if (!$this->tiene_tag_asuntos_no_separados) {
			$this->tiene_tag_asuntos_no_separados = strpos($html, '%ASUNTOS_NO_SEPARADOS%');
		}
		switch ($theTag) {

			case 'INFORME': //GenerarDocumento2
				#INSERTANDO CARTA
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

				$html = str_replace('%xfecha_mes_dos_digitos%', date("m", strtotime($this->fields['fecha_emision'])), $html);
				$html = str_replace('%xfecha_ano_dos_digitos%', date("y", strtotime($this->fields['fecha_emision'])), $html);
				$html = str_replace('%xfecha_mes_dia_ano%', date("m-d-Y", strtotime($this->fields['fecha_emision'])), $html);

				$fechacabecera = ($this->fields['fecha_emision'] == 'NULL' || $this->fields['fecha_emision'] == '0000-00-00' || $this->fields['fecha_emision'] == "") ? time() : strtotime($this->fields['fecha_emision']);

				$html = str_replace('%xfecha_mespalabra_dia_ano%', strftime(Utiles::FormatoStrfTime("%B %e, %Y"), $fechacabecera), $html);
				$html = str_replace('%xnro_factura%', $this->fields['id_cobro'], $html);
				$html = str_replace('%xnombre_cliente%', $contrato->fields['factura_razon_social'], $html);
				$html = str_replace('%xglosa_cliente%', $contrato->fields['factura_razon_social'], $html);
				$html = str_replace('%xdireccion%', nl2br($contrato->fields['factura_direccion']), $html);
				$html = str_replace('%xrut%', $contrato->fields['rut'], $html);

				require_once('CartaCobro.php');

				$CartaCobro = new CartaCobro($this->sesion, $this->fields, $this->ArrayFacturasDelContrato, $this->ArrayTotalesDelContrato);

				if (isset($this->DetalleLiquidaciones)) {
					$CartaCobro->DetalleLiquidaciones = $this->DetalleLiquidaciones;
				}

				$textocarta = $CartaCobro->GenerarDocumentoCarta2($parser_carta, 'CARTA', $lang, $moneda_cliente_cambio, $moneda_cli, $idioma, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $cliente, $id_carta);
				$html = str_replace('%COBRO_CARTA%', $textocarta, $html);

				$PdfLinea1 = Conf::GetConf($this->sesion, 'PdfLinea1');
				$PdfLinea2 = Conf::GetConf($this->sesion, 'PdfLinea2');
				$PdfLinea3 = Conf::GetConf($this->sesion, 'PdfLinea3');

				$query = "SELECT count(*) FROM cta_corriente
								 WHERE id_cobro=" . $this->fields['id_cobro'];
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
				$html = str_replace('%direccion_blr%', __('%direccion_blr%'), $html);
				$html = str_replace('%glosa_fecha%', __('Fecha') . ':', $html);
				$html = str_replace('%glosa_fecha_mayuscula%', __('FECHA'), $html);
				$html = str_replace('%texto_factura%', __('FACTURA'), $html);
				$html = str_replace('%fecha_gqmc%', ($this->fields['fecha_emision'] == '0000-00-00 00:00:00' or $this->fields['fecha_emision'] == '' or $this->fields['fecha_emision'] == 'NULL') ? ucwords(strftime(Utiles::FormatoStrfTime("%e %B %Y"), time())) : ucwords(strftime(Utiles::FormatoStrfTime("%e %B %Y"), strtotime($this->fields['fecha_emision']))), $html);
				$html = str_replace('%fecha%', ($this->fields['fecha_emision'] == '0000-00-00 00:00:00' or $this->fields['fecha_emision'] == '' or $this->fields['fecha_emision'] == 'NULL') ? Utiles::sql2fecha(date('Y-m-d'), $idioma->fields['formato_fecha']) : Utiles::sql2fecha($this->fields['fecha_emision'], $idioma->fields['formato_fecha']), $html);

				if ($lang == 'es') {
					$fecha_lang = ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%d de %B de %Y'));
				} else {
					$fecha_lang = date('F d, Y');
				}

				$time_fecha_fin = strtotime($this->fields['fecha_fin']);
				$fecha_mes_del_cobro = strftime("%B %Y", mktime(0, 0, 0, date("m", $time_fecha_fin), date("d", $time_fecha_fin) - 5, date("Y", $time_fecha_fin)));

				$cliente = new Cliente($this->sesion);

				if (Conf::GetConf($this->sesion, 'CodigoSecundario')) {
					$codigo_cliente = $cliente->CodigoACodigoSecundario($this->fields['codigo_cliente']);
				} else {
					$codigo_cliente = $this->fields['codigo_cliente'];
				}

				$html = str_replace('%fecha_mes_dos_digitos%', date("m", $time_fecha_fin), $html);
				$html = str_replace('%fecha_ano_dos_digitos%', date("y", $time_fecha_fin), $html);
				$html = str_replace('%fecha_dia_mes_ano%', date("d/m/Y"), $html);
				$html = str_replace('%codigo_cliente%', $codigo_cliente, $html);
				$html = str_replace('%fecha_mes_del_cobro%', ucfirst($fecha_mes_del_cobro), $html);
				$html = str_replace('%fecha_larga%', $fecha_lang, $html);

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
				$html = str_replace('%label_codigo_cliente%', __('Código') . ' ' . __('Cliente'), $html);
				$html = str_replace('%nota_cobro_acl%', __('Nota de Cobro ACL'), $html);
				$html = str_replace('%reference_no_acl%', __('reference no acl'), $html);
				$html = str_replace('%servicios%', __('Servicios'), $html);
				$html = str_replace('%honorarios%', __('Honorarios'), $html);
				$html = str_replace('%gastos_acl%', __('Gastos ACL'), $html);
				$html = str_replace('%otros%', __('Otros'), $html);
				$html = str_replace('%subtotales%', __('Subtotal'), $html);
				$html = str_replace('%impuestos%', __('Impuesto'), $html);
				$html = str_replace('%total_deuda%', __('Total Adeudado'), $html);
				$html = str_replace('%instruccion_deposito%', __('Instrucciones Depósito'), $html);
				$html = str_replace('%beneficiario_deposito%', __('Titular'), $html);
				$html = str_replace('%banco%', __('Banco'), $html);
				$html = str_replace('%direccion%', __('Dirección'), $html);
				$html = str_replace('%cuenta_bancaria%', __('Cuenta'), $html);

				if ($lang == 'es') {
					$query_categoria_lang = "IFNULL( prm_categoria_usuario.glosa_categoria, ' ' ) as categoria_usuario";
				} else {
					$query_categoria_lang = "IFNULL( prm_categoria_usuario.glosa_categoria_lang, ' ' ) as categoria_usuario";
				}

				$query = "SELECT
								CONCAT_WS(' ',usuario.nombre,usuario.apellido1,usuario.apellido2) as nombre_encargado,
								usuario.rut,
								IFNULL(usuario.dv_rut, 'NA'),
								$query_categoria_lang
							FROM usuario
							JOIN contrato ON usuario.id_usuario=contrato.id_usuario_responsable
							JOIN cobro ON contrato.id_contrato=cobro.id_contrato
							LEFT JOIN prm_categoria_usuario ON ( usuario.id_categoria_usuario = prm_categoria_usuario.id_categoria_usuario AND usuario.id_categoria_usuario != 0 )
							WHERE cobro.id_cobro=" . $this->fields['id_cobro'];

				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($nombre_encargado, $rut_usuario, $dv_usuario, $categoria_usuario) = mysql_fetch_array($resp);

				$html = str_replace('%encargado_comercial%', $nombre_encargado, $html);

				if (trim($dv_usuario) != 'NA' && strlen(trim($dv_usuario)) != 0) {
					$rut_usuario .= "-" . $dv_usuario;
				}

				$html = str_replace('%rut_encargado%', $rut_usuario, $html);

				$html = str_replace('%CLIENTE%', $this->GenerarSeccionCliente($parser->tags['CLIENTE'], $idioma, $moneda, $asunto), $html);

				if ($this->fields['forma_cobro'] == 'ESCALONADA') {
					$html = str_replace('%DETALLE_COBRO%', "%DETALLE_COBRO%\n\n%TABLA_ESCALONADA%", $html);
				}

				$html = str_replace('%DETALLE_COBRO%', $this->GenerarDocumento2($parser, 'DETALLE_COBRO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%RESUMEN_DETALLADO_HITOS%', $this->GenerarDocumento2($parser, 'RESUMEN_DETALLADO_HITOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%RESUMEN_ASUNTOS%', $this->GenerarDocumento2($parser, 'RESUMEN_ASUNTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);

				if ($this->fields['forma_cobro'] == 'ESCALONADA') {

					$this->CargarEscalonadas();
					$html_tabla = "<br /><span class=\"titulo_seccion\">" . __('Detalle Tarifa Escalonada') . "</span> <table class=\"tabla_normal\" width=\"50%\">%filas_escalas%</table>";
					$html_fila = "";
					for ($i = 1; $i <= self::MAX_ESC; $i++) {
						if ($this->fields['esc' . $i . '_tiempo'] != 0) {
							$detalle_escala = "";

							if (!empty($this->escalonadas[$i]['tiempo_inicial'])) {
								$detalle_escala .= $this->escalonadas[$i]['tiempo_inicial'] . ' - ';
							}

							$detalle_escala .= !empty($this->escalonadas[$i]['tiempo_final']) && $this->escalonadas[$i]['tiempo_final'] != 'NULL' ? $this->escalonadas[$i]['tiempo_final'] . ' hrs. ' : ' ' . __('Más hrs') . ' ';
							$detalle_escala .=!empty($this->escalonadas[$i]['id_tarifa']) && $this->escalonadas[$i]['id_tarifa'] != 'NULL' ? " " . __('Tarifa HH') . " " : " " . __('monto fijo') . " ";
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
					}
					$html_tabla = str_replace('%filas_escalas%', $html_fila, $html_tabla);
					$html = str_replace('%TABLA_ESCALONADA%', $html_tabla, $html);
				}

				if ($this->fields['opc_ver_morosidad']) {
					//Tiene adelantos
					$query = "SELECT COUNT(*) AS nro_adelantos
								FROM documento
								LEFT JOIN prm_moneda ON prm_moneda.id_moneda = documento.id_moneda
								WHERE documento.codigo_cliente = '" . $this->fields['codigo_cliente'] . "' AND documento.es_adelanto = 1
								AND (documento.id_contrato = " . $this->fields['id_contrato'] . " OR documento.id_contrato IS NULL)";

					$adelantos = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

					$adelanto = mysql_fetch_assoc($adelantos);

					if ($adelanto['nro_adelantos'] > 0) {
						$html = str_replace('%ADELANTOS%', $this->GenerarDocumento2($parser, 'ADELANTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					} else {
						$html = str_replace('%ADELANTOS%', '', $html);
					}

					$html = str_replace('%COBROS_ADEUDADOS%', $this->GenerarDocumento2($parser, 'COBROS_ADEUDADOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {

					$html = str_replace('%ADELANTOS%', '', $html);
					$html = str_replace('%COBROS_ADEUDADOS%', '', $html);
				}

				if ($this->fields['forma_cobro'] == 'CAP') {
					$html = str_replace('%RESUMEN_CAP%', $this->GenerarDocumento2($parser, 'RESUMEN_CAP', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%RESUMEN_CAP%', '', $html);
				}

				if (Conf::GetConf($this->sesion, 'ParafoAsuntosSoloSiHayTrabajos')) {

					if ($cont_trab || $cont_tram || ( $cont_gastos > 0 && Conf::GetConf($this->sesion, 'SepararGastosPorAsunto') )) {
						$html = str_replace('%ASUNTOS_NO_SEPARADOS%',$this->GenerarDocumento2($parser, 'ASUNTOS_NO_SEPARADOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
						$html = str_replace('%ASUNTOS%', $this->GenerarDocumento2($parser, 'ASUNTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);

					} else {
						$html = str_replace('%ASUNTOS_NO_SEPARADOS%', '', $html);
						$html = str_replace('%ASUNTOS%', '', $html);
					}
				} else {
					$html = str_replace('%ASUNTOS_NO_SEPARADOS%', $this->GenerarDocumento2($parser, 'ASUNTOS_NO_SEPARADOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					$html = str_replace('%ASUNTOS%', $this->GenerarDocumento2($parser, 'ASUNTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				}

				$html = str_replace('%TRAMITES%', '', $html);

				if (Conf::GetConf($this->sesion, 'ParafoGastosSoloSiHayGastos')) {

					if ($cont_gastos) {
						$html = str_replace('%GASTOS%', $this->GenerarDocumento2($parser, 'GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					} else {
						$html = str_replace('%GASTOS%', '', $html);
					}
				} else {
					$html = str_replace('%GASTOS%', $this->GenerarDocumento2($parser, 'GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				}

				$html = str_replace('%CTA_CORRIENTE%', $this->GenerarDocumento2($parser, 'CTA_CORRIENTE', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%TIPO_CAMBIO%', $this->GenerarDocumentoComun($parser, 'TIPO_CAMBIO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%MOROSIDAD%', $this->GenerarDocumentoComun($parser, 'MOROSIDAD', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%GLOSA_ESPECIAL%', $this->GenerarDocumentoComun($parser, 'GLOSA_ESPECIAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%RESUMEN_PROFESIONAL_POR_CATEGORIA%', $this->GenerarSeccionResumenProfesional($parser, 'RESUMEN_PROFESIONAL_POR_CATEGORIA', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);

				if (Conf::GetConf($this->sesion, 'ParafoAsuntosSoloSiHayTrabajos') && ($this->fields['incluye_honorarios'] == 0)) {
					$html = str_replace('%RESUMEN_PROFESIONAL%', '', $html);
				} else {
					$html = str_replace('%RESUMEN_PROFESIONAL%', $this->GenerarSeccionResumenProfesional($parser, 'RESUMEN_PROFESIONAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				}

				$html = str_replace('%ENDOSO%', $this->GenerarDocumentoComun($parser, 'ENDOSO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);

				if ($masi) {
					$html = str_replace('%SALTO_PAGINA%', $this->GenerarDocumentoComun($parser, 'SALTO_PAGINA', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%SALTO_PAGINA%', '', $html);
				}

				$html = str_replace('%DESGLOSE_POR_ASUNTO_DETALLE%', $this->GenerarDocumentoComun($parser, 'DESGLOSE_POR_ASUNTO_DETALLE', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%DESGLOSE_POR_ASUNTO_TOTALES%', $this->GenerarDocumentoComun($parser, 'DESGLOSE_POR_ASUNTO_TOTALES', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);

				if (Conf::GetConf($this->sesion, 'NuevoModuloFactura')) {
					$query = "SELECT CAST( GROUP_CONCAT( numero ) AS CHAR ) AS numeros
								FROM factura
								WHERE id_cobro =" . $this->fields['id_cobro'];

					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

					list($numero_factura) = mysql_fetch_array($resp);

					if (!$numero_factura) {
						$numero_factura = '';
					}

					$html = str_replace('%numero_factura%', $numero_factura, $html);
				} else if (Conf::GetConf($this->sesion, 'PermitirFactura')) {
					$html = str_replace('%numero_factura%', $this->fields['documento'], $html);
				} else {
					$html = str_replace('%numero_factura%', $this->fields['documento'], $html);
				}

				if ($this->fields['fecha_emision'] == '0000-00-00 00:00:00' or $this->fields['fecha_emision'] == '' or $this->fields['fecha_emision'] == 'NULL') {
					$html = str_replace('%xcorrelativo_aguilar%', 'N/A', $html);
				} else {
					$html = str_replace('%xcorrelativo_aguilar%', 'DN-' . date("ym", strtotime($this->fields['fecha_emision'])) . '-' . $this->fields['documento'], $html);
				}

				if ($lang == 'es') {
					$html = str_replace('%honorarios_vouga%', __('HONORARIOS'), $html);
				} else {
					$html = str_replace('%honorarios_vouga%', __('FEES'), $html);
				}

				$html = str_replace('%TOTAL_CON_ADELANTOS%', $this->GenerarDocumento2($parser, 'TOTAL_CON_ADELANTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);

				break;

			case 'DETALLE_COBRO': //GenerarDocumento2

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

				$html = str_replace('%glosa_cliente%', $contrato->fields['factura_razon_social'], $html);
				$html = str_replace('%reporte_servicio%', __('Reporte de Servicios'), $html);
				$html = str_replace('%aviso_de_cobro%', 'Aviso de cobro', $html);
				$html = str_replace('%factura_o_nd%', 'Factura o ND', $html);
				$html = str_replace('%fecha_fin_gastos_liq%', 'Gastos liquidados hasta', $html);
				$html = str_replace('%honorario_yo_gastos%', __('honorario_yo_gastos'), $html);
				$html = str_replace('%materia%', __('Materia'), $html);
				$html = str_replace('%glosa_asunto_sin_codigo%', $imprimir_asuntos, $html);
				$html = str_replace('%resumen_cobro%', __('Resumen Nota de Cobro'), $html);
				$html = str_replace('%fecha%', __('Fecha'), $html);
				$html = str_replace('%texto_fecha_emision%', __('Fecha Emisión'), $html);
				$html = str_replace('%instrucciones_pago%', __('INSTRUCCIONES DE PAGO'), $html);
				$html = str_replace('%giro_bancario%', __('Giro bancario a'), $html);
				$html = str_replace('%descuento_liquidacion%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['descuento'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%impuesto_liquidacion%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($impuesto_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%subtotal_gastos_honorarios_liquidacion%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($this->fields['monto_subtotal'], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				if (array_key_exists('codigo_contrato', $contrato->fields)) {
					$html = str_replace('%glosa_codigo_contrato%', __('Código') . ' ' . __('Contrato'), $html);
					$html = str_replace('%codigo_contrato%', $contrato->fields['codigo_contrato'], $html);
				} else {
					$html = str_replace('%glosa_codigo_contrato%', '', $html);
					$html = str_replace('%codigo_contrato%', '', $html);
				}

				$html = str_replace('%fecha_corte%', __('Fecha de Corte'), $html);

				$html = str_replace('%fecha_emision_glosa%', ($this->fields['fecha_emision'] == '0000-00-00' or $this->fields['fecha_emision'] == '') ? '&nbsp;' : __('Fecha emisión'), $html);
				$html = str_replace('%fecha_emision%', ($this->fields['fecha_emision'] == '0000-00-00' or $this->fields['fecha_emision'] == '') ? '&nbsp;' : Utiles::sql2fecha($this->fields['fecha_emision'], $idioma->fields['formato_fecha']), $html);
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

				$html = str_replace('%cobro%', __('Cobro') . ' ' . __('N°'), $html);
				$html = str_replace('%texto_cobro_nr%', __('Cobro N°'), $html);
				$html = str_replace('%reference%', __('%reference_no%'), $html);
				$html = str_replace('%valor_cobro%', $this->fields['id_cobro'], $html);
				$html = str_replace('%total_simbolo%', __('Total') . ' (' . $moneda_total->fields['simbolo'] . ')', $html);
				$html = str_replace('%boleta%', empty($this->fields['documento']) ? '' : __('Boleta'), $html);
				$html = str_replace('%encargado%', __('Director proyecto'), $html);

				if (!$contrato->fields['id_usuario_responsable']) {
					$nombre_encargado = '';
				} else {
					$query = "SELECT CONCAT_WS(' ',nombre,apellido1,apellido2) as nombre_encargado
								FROM usuario
								WHERE id_usuario=" . $contrato->fields['id_usuario_responsable'];

					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					list($nombre_encargado) = mysql_fetch_array($resp);
				}

				$html = str_replace('%encargado_valor%', $nombre_encargado, $html);
				$html = str_replace('%factura%', empty($this->fields['documento']) ? '' : __('Factura'), $html);
				$html = str_replace('%factura_acl%', empty($this->fields['documento']) ? '' : __('Factura ACL'), $html);

				if (empty($this->fields['documento'])) {
					$html = str_replace('%pctje_blr%', '33%', $html);
					$html = str_replace('%FACTURA_NUMERO%', '', $html);
					$html = str_replace('%NUMERO_FACTURA%', '', $html);
				} else {
					$html = str_replace('%pctje_blr%', '25%', $html);
					$html = str_replace('%FACTURA_NUMERO%', $this->GenerarDocumento2($parser, 'FACTURA_NUMERO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					$html = str_replace('%NUMERO_FACTURA%', $this->GenerarDocumento2($parser, 'NUMERO_FACTURA', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				}

				$html = str_replace('%factura_nro%', empty($this->fields['documento']) ? '' : __('Factura') . ' ' . __('N°'), $html);
				$html = str_replace('%cobro_nro%', __('Carta') . ' ' . __('N°'), $html);
				$html = str_replace('%nro_cobro%', $this->fields['id_cobro'], $html);
				$html = str_replace('%cobro_factura_nro%', empty($this->fields['documento']) ? '' : $this->fields['documento'], $html);
				$html = str_replace('%nro_factura%', empty($this->fields['documento']) ? '' : $this->fields['documento'], $html);

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

				//el siguiente query extrae la descripcion de forma_cobro de la tabla prm_forma_cobro
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
				$html = str_replace('%detalle_modalidad_lowercase%', $this->fields['opc_ver_modalidad'] == 1 && $this->fields['forma_cobro'] != 'ESCALONADA' ? $detalle_modalidad_lowercase : '', $html);
				$html = str_replace('%periodo%', (($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') and ( $this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '')) ? '' : __('Periodo'), $html);
				$html = str_replace('%periodo_cobro%', (($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') and ( $this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '')) ? '' : __('Periodo Cobro'), $html);
				$html = str_replace('%valor_periodo_ini%', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? '' : __('Desde') . ' ' . Utiles::sql2fecha($this->fields['fecha_ini'], $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%valor_periodo_fin%', ($this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '') ? '' : __('hasta') . ' ' . Utiles::sql2fecha($this->fields['fecha_fin'], $idioma->fields['formato_fecha']), $html);
				$html = str_replace('%fecha_ini%', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? '' : __('Fecha desde'), $html);
				$html = str_replace('%fecha_ini_primer_trabajo%', __('Fecha desde'), $html);

				$html = str_replace('%nota_transferencia%', '<u>' . __('Nota') . '</u>:' . __('Por favor recuerde incluir cualquier tarifa o ') . __('cobro') . __(' por transferencia por parte de vuestro banco con el fin de evitar cargos en las próximas facturas.'), $html);

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
				$html = str_replace('%total_horas%', __('total_horas'), $html);

				if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
					$duracion_decimal_cobrable = number_format($horas_cobrables + $minutos_cobrables / 60, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', '');
					$html = str_replace('%valor_horas%', $duracion_decimal_cobrable, $html);
				} else {
					$html = str_replace('%valor_horas%', $horas_cobrables . ':' . $minutos_cobrables, $html);
				}

				if ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$html = str_replace('%DETALLE_COBRO_RETAINER%', $this->GenerarDocumento($parser, 'DETALLE_COBRO_RETAINER', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					$html = str_replace('%DETALLE_TARIFA_ADICIONAL%', $this->GenerarDocumento($parser, 'DETALLE_TARIFA_ADICIONAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%DETALLE_COBRO_RETAINER%', '', $html);
					$html = str_replace('%DETALLE_TARIFA_ADICIONAL%', '', $html);
				}

				if (Conf::GetConf($this->sesion, 'ParafoAsuntosSoloSiHayTrabajos') && ($this->fields['incluye_honorarios'] == 0)) {
					$html = str_replace('%honorarios%', '', $html);
				} else if (Conf::GetConf($this->sesion, 'ResumenProfesionalVial')) {
					$html = str_replace('%honorarios%', __('Honorarios totales'), $html);

					if ($this->fields['opc_restar_retainer']) {
						$html = str_replace('%RESTAR_RETAINER%', $this->GenerarDocumento2($parser, 'RESTAR_RETAINER', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					} else {
						$html = str_replace('%RESTAR_RETAINER%', '', $html);
					}

					$html = str_replace('%descuento%', __('Otros'), $html);
					$html = str_replace('%saldo%', __('Saldo por pagar'), $html);
					$html = str_replace('%equivalente%', __('Equivalente a'), $html);
				} else {
					$html = str_replace('%honorarios%', __('Honorarios'), $html);
				}

				$html = str_replace('%honorarios_con_lang%', __($this->fields['codigo_idioma'] . '_Honorarios'), $html);

				if ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$html = str_replace('%honorarios_totales%', __('Honorarios Totales'), $html);
				} else {
					$html = str_replace('%honorarios_totales%', __('Honorarios'), $html);
				}

				$html = str_replace('%honorarios_mta%', __('Honorarios totales'), $html);
				$html = str_replace('%valor_honorarios_totales%', $x_resultados['monto'][$this->fields['id_moneda']], $html);
				//$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo']

				$html = str_replace('%valor_honorarios_totales_moneda_total%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($x_resultados['monto_trabajos'][$this->fields['opc_moneda_total']] + $x_resultados['impuesto'][$this->fields['opc_moneda_total']], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				//$html = str_replace('%valor_honorarios_totales_moneda_total%', $x_resultados['monto'][$this->fields['opc_moneda_total']], $html);

				$html = str_replace('%fees%', __('%fees%'), $html); //en vez de Legal Fee es Legal Fees en inglés
				$html = str_replace('%expenses%', __('%expenses%'), $html); //en vez de Disbursements es Expenses en inglés
				$html = str_replace('%total_honorarios%', __('Total Honorarios'), $html);

				//variable que se usa para la nota de cobro de vial
				$monto_contrato_id_moneda = UtilesApp::CambiarMoneda($this->fields['monto_contrato'], $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['tipo_cambio'], $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['cifras_decimales'], $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales']);
				//$monto_cobro_menos_monto_contrato_moneda_tarifa = number_format($this->fields['monto']-($this->fields['monto_contrato']*$cobro_moneda->moneda[$this->fields['id_moneda_monto']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']),$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','');
				$monto_cobro_menos_monto_contrato_moneda_tarifa = number_format($this->fields['monto'] - $monto_contrato_id_moneda, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');

				if (Conf::GetConf($this->sesion, 'ParafoAsuntosSoloSiHayTrabajos') && ($this->fields['incluye_honorarios'] == 0)) {
					$html = str_replace('%valor_honorarios_demo%', '', $html);
				} else {
					if ($this->EsCobrado()) {
						$html = str_replace('%valor_honorarios_demo%', $moneda->fields['simbolo'] . $this->espacio . number_format($x_resultados['monto_trabajo_con_descuento'][$this->fields['id_moneda']], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					} else {
						$html = str_replace('%valor_honorarios_demo%', $moneda->fields['simbolo'] . $this->espacio . number_format($x_resultados['monto_trabajo_con_descuento'][$this->fields['id_moneda']], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					}

					if (( Conf::GetConf($this->sesion, 'ResumenProfesionalVial') ) && ( $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL' ) && $this->fields['opc_restar_retainer']) {
						$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'] . $this->espacio . number_format($monto_cobro_menos_monto_contrato_moneda_tarifa, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					}

					if (Conf::GetConf($this->sesion, 'CalculacionCyC')) {
						$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto_subtotal'] - $this->fields['descuento'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					} else if (Conf::GetConf($this->sesion, 'UsarImpuestoSeparado') && $contrato->fields['usa_impuesto_separado']) {
						$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto_subtotal'] - $this->fields['descuento'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					} else {
						$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					}
				}

				$html = str_replace('%horas_decimales%', __('Horas'), $html);
				$minutos_decimal = $minutos_cobrables / 60;
				$duracion_decimal = $horas_cobrables + $minutos_decimal;
				$html = str_replace('%valor_horas_decimales%', number_format($duracion_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				$en_pesos = $x_resultados['monto'][$this->fields['id_moneda_base']];
				$total_en_moneda = $x_resultados['monto'][$this->fields['opc_moneda_total']];
				$subtotal_en_moneda_cyc = $x_resultados['monto_subtotal'][$this->fields['opc_moneda_total']];
				$descuento_cyc = $x_resultados['descuento'][$this->fields['opc_moneda_total']];

				if (Conf::GetConf($this->sesion, 'CalculacionCyC')) {
					$impuestos_cyc_approximacion = number_format(($subtotal_en_moneda_cyc - $descuento_cyc) * ($this->fields['porcentaje_impuesto'] / 100), $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				} else {
					$impuestos_cyc_approximacion = $x_resultados['impuesto'][$this->fields['opc_moneda_total']];
				}

				$impuestos_cyc = $impuestos_cyc_approximacion;

				$html = str_replace('%valor_honorarios_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($subtotal_en_moneda_cyc - $descuento_cyc, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idoma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_honorarios_monedabase%', $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] ? '' : $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($total_en_moneda, $cobro_moneda->moneda[$this->fields['moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_honorarios_monedabase_tyc%', $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] || ( $this->fields['id_moneda'] == 2 && $this->fields['codigo_idioma'] == 'en' ) ? '' : $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($total_en_moneda, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				$html = str_replace('%monedabase%', $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] ? '' : __('Equivalente a'), $html);
				$html = str_replace('%equivalente_a_la_fecha%', $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] ? '' : __('Equivalente a la fecha'), $html);

				#detalle total gastos
				if (Conf::GetConf($this->sesion, 'ParafoGastosSoloSiHayGastos') && ($this->fields['incluye_gastos'] == 0)) {
					$html = str_replace('%gastos%', '', $html);
				} else {
					$html = str_replace('%gastos%', __('Gastos'), $html);
				}

				$total_gastos_moneda = $x_cobro_gastos['gasto_total'];

				if ($this->fields['monto_subtotal'] > 0) {
					$html = str_replace('%DETALLE_HONORARIOS%', $this->GenerarDocumento2($parser, 'DETALLE_HONORARIOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%DETALLE_HONORARIOS%', '', $html);
				}

				if ($total_gastos_moneda > 0) {
					$html = str_replace('%DETALLE_GASTOS%', $this->GenerarDocumento2($parser, 'DETALLE_GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%DETALLE_GASTOS%', '', $html);
				}

				if ($this->fields['monto_tramites'] > 0) {
					$html = str_replace('%DETALLE_TRAMITES%', $this->GenerarDocumento2($parser, 'DETALLE_TRAMITES', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%DETALLE_TRAMITES%', '', $html);
				}

				if (Conf::GetConf($this->sesion, 'CalculacionCyC')) {
					$total_gastos_moneda = round($total_gastos_moneda, $moneda_total->fields['cifras_decimales']);
				}

				$impuestos_total_gastos_moneda = round($total_gastos_moneda * ($this->fields['porcentaje_impuesto_gastos'] / 100), $moneda_total->fields['cifras_decimales']);

				if (Conf::GetConf($this->sesion, 'ParafoGastosSoloSiHayGastos') && ($this->fields['incluye_gastos'] == 0)) {
					$html = str_replace('%valor_gastos%', '', $html);
				} else {
					$html = str_replace('%valor_gastos%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($total_gastos_moneda, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}

				$total_cobro = $total_en_moneda + $total_gastos_moneda + $x_resultados['impuesto_gastos'][$this->fields['opc_moneda_total']];
				$total_cobro_cyc = $subtotal_en_moneda_cyc + $total_gastos_moneda - $descuento_cyc;
				$total_cobro_demo = $x_resultados['monto_total_cobro'][$this->fields['opc_moneda_total']];
				$iva_cyc = $impuestos_total_gastos_moneda + $impuestos_cyc;
				$saldo_total_cobro = $x_resultados['saldo_gastos'][$this->fields['opc_moneda_total']];

				$html = str_replace('%total_cobro%', __('Total Cobro'), $html);
				$html = str_replace('%totalcobro%', __('total_cobro'), $html);
				$html = str_replace('%total_cobro_mta%', __('GRAN TOTAL'), $html);
				$html = str_replace('%total_cobro_cyc%', __('Honorarios y Gastos'), $html);
				$html = str_replace('%total_cyc%', __('Total'), $html);
				$html = str_replace('%iva_cyc%', __('IVA') . '(' . $this->fields['porcentaje_impuesto'] . '%)', $html);
				$html = str_replace('%honorarios_y_gastos%', '(' . __('Honorarios y Gastos') . ')', $html);

				$html = str_replace('%valor_total_cobro_demo%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($total_cobro_demo, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_total_cobro_cyc%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($total_cobro_cyc, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_iva_cyc%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($iva_cyc, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idoma->fields['separador_miles']), $html);
				$html = str_replace('%valor_total_cyc%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($total_cobro_cyc + $iva_cyc, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_total_cobro%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($total_cobro, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_total_cobro_sin_simbolo%', number_format($total_cobro, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_uf%', __('Valor UF') . ' ' . date('d.m.Y'), $html);
				$html = str_replace('%valor_saldo_total_cobro%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($saldo_total_cobro, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				if ($this->fields['opc_ver_tipo_cambio'] == 0) {
					$html = str_replace('%glosa_tipo_cambio_moneda%', '', $html);
					$html = str_replace('%valor_tipo_cambio_moneda%', '', $html);
				} else {
					$html = str_replace('%glosa_tipo_cambio_moneda%', __('Tipo de Cambio'), $html);
					$html = str_replace('%valor_tipo_cambio_moneda%', $cobro_moneda->moneda[$this->fields['id_moneda_base']]['simbolo'] . $this->espacio . number_format($cobro_moneda->moneda[$moneda->fields['id_moneda']]['tipo_cambio'], 2, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}

				$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL%', $this->GenerarDocumentoComun($parser, 'DETALLE_COBRO_MONEDA_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);

				if ($this->fields['opc_ver_descuento']) {
					$html = str_replace('%DETALLE_COBRO_DESCUENTO%', $this->GenerarDocumento2($parser, 'DETALLE_COBRO_DESCUENTO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					$html = str_replace('%DETALLE_COBRO_DESCUENTO_NUEVO%', $this->GenerarDocumentoComun($parser, 'DETALLE_COBRO_DESCUENTO_NUEVO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%DETALLE_COBRO_DESCUENTO%', '', $html);
					$html = str_replace('%DETALLE_COBRO_DESCUENTO_NUEVO%', '', $html);
				}

				$simbolo_moneda = $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'];
				$cifras_decimales = $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'];
				$separador_decimales = $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'];
				$html = str_replace('%honorarios_nuevo%', __('Honorarios'), $html);
				$html = str_replace('%valor_honorarios_nuevo%', $simbolo_moneda . $this->espacio . number_format($x_resultados['monto_honorarios'][$this->fields['opc_moneda_total']], $cifras_decimales, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				if ($this->fields['porcentaje_impuesto'] > 0 || $this->fields['porcentaje_impuesto_gastos'] > 0) {
					$html = str_replace('%IMPUESTO%', $this->GenerarDocumento2($parser, 'IMPUESTO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
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

					$html = str_replace('%valor_descuento%', '(' . $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['descuento'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ')', $html);
					$html = str_replace('%valor_bruto%', $moneda->fields['simbolo'] . $this->espacio . number_format($valor_bruto, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

					if (( $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL' ) && $this->fields['opc_restar_retainer']) {
						$html = str_replace('%valor_equivalente%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($monto_cobro_menos_monto_contrato_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					} else {
						$html = str_replace('%valor_equivalente%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($total_en_moneda, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					}
				}

				$html = str_replace('%total_subtotal_cobro%', __('Total Cobro'), $html);

				if ($this->fields['id_carta'] == 3) {
					$html = str_replace('%nota_disclaimer%', __('Nota Disclaimer'), $html);
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
									INNER join prm_moneda moneda_base ON moneda_base.id_moneda = 1
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
							INNER JOIN prm_moneda moneda_base ON moneda_base.id_moneda = 1
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

				if ($lang == 'es') {

					$glosa_moneda_plural_lang = $moneda_total->fields['glosa_moneda_plural'];

					if (empty($total_parte_decimal)) {
						$monto_palabra_parte_entera = strtoupper(Numbers_Words::toWords($total_parte_entera, 'es'));
						$monto_total_palabra = $monto_palabra_parte_entera . ' ' . mb_strtoupper($glosa_moneda_plural_lang);
						$monto_total_palabra_cero_cien = $monto_palabra_parte_entera . ' ' . mb_strtoupper($glosa_moneda_plural_lang) . ' CON 00/100 CENTAVOS';
					} else {
						$monto_palabra_parte_entera = strtoupper(Numbers_Words::toWords($total_parte_entera, 'es'));
						$monto_palabra_parte_decimal = strtoupper(Numbers_Words::toWords($total_parte_decimal * $fix_decimal, 'es'));
						$monto_total_palabra = $monto_palabra_parte_entera . ' ' . mb_strtoupper($glosa_moneda_plural_lang, 'UTF-8') . ' CON ' . $monto_palabra_parte_decimal . ' ' . 'CENTAVOS';
						$monto_total_palabra_cero_cien = $monto_palabra_parte_entera . ' ' . mb_strtoupper($glosa_moneda_plural_lang) . ' CON ' . $total_parte_decimal * $fix_decimal . '/100 CENTAVOS';
					}
				} else {

					$glosa_moneda_plural_lang = $moneda_total->fields['glosa_moneda_plural_lang'];

					if (empty($total_parte_decimal)) {
						$monto_palabra_parte_entera = strtoupper(Numbers_Words::toWords($total_parte_entera, 'en_US'));
						$monto_total_palabra = $monto_palabra_parte_entera . ' ' . mb_strtoupper($glosa_moneda_plural_lang);
						$monto_total_palabra_cero_cien = $monto_palabra_parte_entera . ' ' . mb_strtoupper($glosa_moneda_plural_lang) . ' CON 00/100 CENTAVOS';
					} else {
						$monto_palabra_parte_entera = strtoupper(Numbers_Words::toWords($total_parte_entera, 'en_US'));
						$monto_palabra_parte_decimal = strtoupper(Numbers_Words::toWords($total_parte_decimal, 'en_US'));
						$monto_total_palabra = $monto_palabra_parte_entera . ' ' . mb_strtoupper($glosa_moneda_plural_lang, 'UTF-8') . ' WITH ' . $monto_palabra_parte_decimal . ' ' . 'CENTS';
						$monto_total_palabra_cero_cien = $monto_palabra_parte_entera . ' ' . mb_strtoupper($glosa_moneda_plural_lang, 'UTF-8') . ' WITH ' . $total_parte_decimal * $fix_decimal . '/100 CENTS';
					}
				}

				$html = str_replace('%monto_total_palabra_cero_cien%', $monto_total_palabra_cero_cien, $html);
				$html = str_replace('%monto_total_palabra%', $monto_total_palabra, $html);

				$html = str_replace('%titulo_adelantos%', __('Adelantos'), $html);
				$html = str_replace('%saldo_cobro%', __('Saldo') . ' ' . __('Cobro'), $html);

				$Documento = new Documento($this->sesion);
				$advances_total = $Documento->getTotalAdvanceCharge($this->fields['id_cobro']);

				$moneda = $cobro_moneda->moneda[$this->fields['opc_moneda_total']];
				$html = str_replace(
					'%monto_total_adelanto%',
					$moneda['simbolo'] . $this->espacio . number_format($advances_total, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']),
					$html
				);

				break;

			case 'RESUMEN_DETALLADO_HITOS':
				$this->hitos = $contrato->ObtenerHitos($this->fields['id_contrato']);

				if (count($this->hitos) > 0) {
					$html = str_replace('%HITOS_DETALLADO_FILAS%', $this->GenerarDocumentoComun($parser, 'HITOS_DETALLADO_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					$html = str_replace('%HITOS_DETALLADO_ENCABEZADO%', $this->GenerarDocumentoComun($parser, 'HITOS_DETALLADO_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					$html = str_replace('%HITOS_DETALLADO_TOTAL%', $this->GenerarDocumentoComun($parser, 'HITOS_DETALLADO_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					$html = str_replace('%glosa_hitos%', __('Hitos'), $html);
					$html = str_replace('%salto_linea%', '<br>', $html);
					$html = str_replace('%hr%', '<hr size="2" class="separador">', $html);
				} else {
					$html = str_replace('%HITOS_DETALLADO_FILAS%', '', $html);
					$html = str_replace('%HITOS_DETALLADO_ENCABEZADO%', '', $html);
					$html = str_replace('%HITOS_DETALLADO_TOTAL%', '', $html);
					$html = str_replace('%glosa_hitos%', '', $html);
					$html = str_replace('%salto_linea%', '', $html);
					$html = str_replace('%hr%', '', $html);
				}
				break;

			case 'RESUMEN_ASUNTOS':
				$html = str_replace('%resumen_asuntos%', __('Resumen Asuntos'), $html);
				$html = str_replace('%RESUMEN_ASUNTOS_ENCABEZADO%', $this->GenerarDocumento2($parser, 'RESUMEN_ASUNTOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%RESUMEN_ASUNTOS_FILAS%', $this->GenerarDocumento2($parser, 'RESUMEN_ASUNTOS_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%RESUMEN_ASUNTOS_FILAS_FLAT_FEE%', $this->GenerarDocumento2($parser, 'RESUMEN_ASUNTOS_FILAS_FLAT_FEE', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%RESUMEN_ASUNTOS_TOTAL%', $this->GenerarDocumento2($parser, 'RESUMEN_ASUNTOS_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				break;

			case 'ADELANTOS': //GenerarDocumento2
				$html = str_replace('%titulo_adelantos%', __('Adelantos por asignar'), $html);
				$html = str_replace('%ADELANTOS_ENCABEZADO%', $this->GenerarDocumento2($parser, 'ADELANTOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%ADELANTOS_FILAS%', $this->GenerarDocumento2($parser, 'ADELANTOS_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%ADELANTOS_TOTAL%', $this->GenerarDocumento2($parser, 'ADELANTOS_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%ADELANTOS_FILAS_TOTAL%', $this->GenerarDocumento2($parser, 'ADELANTOS_FILAS_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				break;

			case 'COBROS_ADEUDADOS': //GenerarDocumento2
				$html = str_replace('%titulo_adelantos%', __('Saldo anterior'), $html);
				$html = str_replace('%ADELANTOS_ENCABEZADO%', $this->GenerarDocumento2($parser, 'ADELANTOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%COBROS_ADEUDADOS_FILAS_TOTAL%', $this->GenerarDocumento2($parser, 'COBROS_ADEUDADOS_FILAS_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				break;

			case 'HITOS_ENCABEZADO': //GenerarDocumento2
				global $total_hitos, $estehito, $cantidad_hitos, $moneda_hitos, $tipo_cambio_hitos;
				$html = str_replace('%fecha%', __('Fecha'), $html);
				$html = str_replace('%descripcion%', __('Descripción'), $html);
				$html = str_replace('%valor%', __('Valor') . ' ' . $moneda_hitos, $html);

				break;

			case 'HITOS_FILAS': //GenerarDocumento2
				global $total_hitos, $estehito, $cantidad_hitos, $moneda_hitos, $tipo_cambio_hitos;
				$query_hitos = "select * from (select  (select count(*) total from cobro_pendiente cp2 where cp2.id_contrato=cp.id_contrato) total,  @a:=@a+1 as rowid, round(if(cbr.id_cobro=cp.id_cobro, @a,0),0) as thisid,  ifnull(cp.fecha_cobro,0) as fecha_cobro, cp.descripcion, cp.monto_estimado, pm.simbolo, pm.codigo, pm.tipo_cambio  FROM `cobro_pendiente` cp join  contrato c using (id_contrato) join prm_moneda pm using (id_moneda) join cobro cbr using(id_contrato)  join (select @a:=0) FFF
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

			case 'HITOS_TOTAL': //GenerarDocumento2
				global $total_hitos, $estehito, $cantidad_hitos, $moneda_hitos, $tipo_cambio_hitos;

				$html = str_replace('%total%', __('Total'), $html);
				$html = str_replace('%total_hitos%', $total_hitos . ' ' . $moneda_hitos, $html);

				break;

			case 'ADELANTOS_ENCABEZADO': //GenerarDocumento2
				$html = str_replace('%fecha%', __('Fecha'), $html);
				$html = str_replace('%descripcion%', __('Descripción'), $html);
				$html = str_replace('%monto%', __('Monto') . ' (' . $moneda_total->fields['simbolo'] . ')', $html);
				$html = str_replace('%saldo%', __('Saldo'), $html);
				break;

			case 'ADELANTOS_FILAS':
				$ChargeManager = new ChargeManager($this->sesion);
				$advances = $ChargeManager->getAdvances($this->fields['id_cobro']);

				$moneda = $cobro_moneda->moneda[$this->fields['opc_moneda_total']];

				foreach ($advances as $key => $value) {
					$_html = $html;
					$_html = str_replace('%fecha%', Utiles::sql2fecha($value['fecha'], $idioma->fields['formato_fecha']), $_html);
					$_html = str_replace('%descripcion%', $value['glosa'], $_html);
					$_html = str_replace(
						'%monto_adelanto%',
						$moneda['simbolo'] . $this->espacio . number_format($value['monto'], $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']),
						$_html
					);
					$final .= $_html;
				}

				$html = $final;
				break;

			case 'ADELANTOS_TOTAL':
				$html = str_replace('%total%', __('Total'), $html);
				$Documento = new Documento($this->sesion);
				$advances_total = $Documento->getTotalAdvanceCharge($this->fields['id_cobro']);

				$moneda = $cobro_moneda->moneda[$this->fields['opc_moneda_total']];
				$html = str_replace(
					'%monto_total_adelanto%',
					$moneda['simbolo'] . $this->espacio . number_format($advances_total, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']),
					$html
				);
				break;

			case 'TOTAL_CON_ADELANTOS':
				$html = str_replace('%total%', __('Saldo') . ' ' . __('Cobro'), $html);

				$saldo_total_cobro = $x_resultados['saldo_gastos'][$this->fields['opc_moneda_total']];

				$html = str_replace('%valor_total%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($saldo_total_cobro, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			case 'ADELANTOS_FILAS_TOTAL': //GenerarDocumento2
				$saldo = 0;
				$monto_total = 0;
				$moneda = $cobro_moneda->moneda[$this->fields['opc_moneda_total']];

				//Adelantos
				$query = "
				SELECT documento.id_documento, documento.fecha, documento.glosa_documento, IF(documento.saldo_pago = 0, 0, documento.saldo_pago*-1) AS saldo_pago, IF(documento.monto = 0, 0, documento.monto*-1) AS monto, prm_moneda.tipo_cambio
				FROM documento
				LEFT JOIN prm_moneda ON prm_moneda.id_moneda = documento.id_moneda
				WHERE documento.codigo_cliente = '" . $this->fields['codigo_cliente'] . "' AND documento.es_adelanto = 1 AND documento.saldo_pago < 0
				AND (documento.id_contrato = " . $this->fields['id_contrato'] . " OR documento.id_contrato IS NULL)";
				$adelantos = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				while ($adelanto = mysql_fetch_assoc($adelantos)) {
					$fila_adelanto_ = str_replace('%descripcion%', $adelanto['glosa_documento'], $html);

					$monto_saldo = $adelanto['saldo_pago'] * $adelanto['tipo_cambio'] / $moneda['tipo_cambio'];
					$monto_saldo_simbolo = $moneda['simbolo'] . $this->espacio . number_format($monto_saldo, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
					$fila_adelanto_ = str_replace('%saldo_pago%', $monto_saldo_simbolo, $fila_adelanto_);

					$monto = $adelanto['monto'] * $adelanto['tipo_cambio'] / $moneda['tipo_cambio'];
					$monto_simbolo = $moneda['simbolo'] . $this->espacio . number_format($monto, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
					$fila_adelanto_ = str_replace('%monto%', $monto_simbolo, $fila_adelanto_);

					$fila_adelanto_ = str_replace('%fecha%', date("d-m-Y", strtotime($adelanto['fecha'])), $fila_adelanto_);

					$saldo += (float) $monto_saldo;
					$monto_total += (float) $monto;
					$fila_adelantos .= $fila_adelanto_;
				}

				$fila_adelantos .= '<tr class="tr_total">
					<td align="right" colspan="2">' . __('Saldo a favor de cliente') . '</td>
					<td align="right">' . $moneda['simbolo'] . $this->espacio . number_format($monto_total, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . '</td>
					<td align="right">' . $moneda['simbolo'] . $this->espacio . number_format($saldo, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . '</td>
					</tr>';

				$html = $fila_adelantos;
				break;

			case 'COBROS_ADEUDADOS_FILAS_TOTAL': //GenerarDocumento2
				$saldo = 0;
				$monto_total = 0;
				$moneda = $cobro_moneda->moneda[$this->fields['opc_moneda_total']];

				//Deuda
				$query = "
				SELECT documento.glosa_documento, documento.fecha, documento.monto * cm1.tipo_cambio / cm2.tipo_cambio AS monto, ( documento.saldo_honorarios + documento.saldo_gastos ) * cm1.tipo_cambio / cm2.tipo_cambio AS saldo_cobro
				FROM documento
				LEFT JOIN cobro ON cobro.id_cobro = documento.id_cobro
				LEFT JOIN cobro_moneda as cm1 ON cm1.id_cobro = documento.id_cobro AND cm1.id_moneda = documento.id_moneda
				LEFT JOIN cobro_moneda as cm2 ON cm2.id_cobro = '" . $this->fields['id_cobro'] . "' AND cm2.id_moneda = '" . $this->fields['opc_moneda_total'] . "'
				WHERE documento.codigo_cliente = '" . $this->fields['codigo_cliente'] . "'
				AND documento.es_adelanto <> 1 AND documento.tipo_doc = 'N'
				AND (documento.saldo_honorarios + documento.saldo_gastos) > 0
				AND documento.id_cobro <> " . $this->fields['id_cobro'] . "
				AND cobro.estado NOT IN ('PAGADO', 'INCOBRABLE', 'CREADO', 'EN REVISION')";

				$adelantos = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				while ($adelanto = mysql_fetch_assoc($adelantos)) {
					$fila_adelanto_ = str_replace('%descripcion%', $adelanto['glosa_documento'], $html);

					$monto_saldo_simbolo = $moneda['simbolo'] . $this->espacio . number_format($adelanto['saldo_cobro'], $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
					$fila_adelanto_ = str_replace('%saldo_pago%', $monto_saldo_simbolo, $fila_adelanto_);

					$monto_simbolo = $moneda['simbolo'] . $this->espacio . number_format($adelanto['monto'], $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
					$fila_adelanto_ = str_replace('%monto%', $monto_simbolo, $fila_adelanto_);

					$fila_adelanto_ = str_replace('%fecha%', date("d-m-Y", strtotime($adelanto['fecha'])), $fila_adelanto_);
					$saldo += (float) $adelanto['saldo_cobro'];
					$monto_total += (float) $adelanto['monto'];
					$fila_adelantos .= $fila_adelanto_;
				}

				if (empty($fila_adelantos)) {
					$fila_adelantos .= '<tr><td colspan="4"><i>' . __('Sin saldo anterior') . '</i></td></tr>';
				} else {
					$fila_adelantos .= '<tr class="tr_total">
				<td align="right" colspan="2">' . __('Saldo anterior') . '</td>
				<td align="right">' . $moneda['simbolo'] . $this->espacio . number_format($monto_total, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . '</td>
				<td align="right">' . $moneda['simbolo'] . $this->espacio . number_format($saldo, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . '</td>
				</tr>';
				}

				$html = $fila_adelantos;
				break;

			case 'RESUMEN_ASUNTOS_ENCABEZADO':

				$html = str_replace('%resumenasuntos%', __('resumen_asunto'), $html);
				$html = str_replace('%codigo_asunto%', __('Codigo Asunto'), $html);
				$html = str_replace('%asunto%', __('Asunto'), $html);
				$html = str_replace('%nombre_asunto%', __('Nombre Asunto'), $html);
				$html = str_replace('%glosa_asunto%', __('Descripción'), $html);
				$html = str_replace('%horas%', __('Horas'), $html);
				$html = str_replace('%importe%', __('Importe'), $html);
				break;

			case 'RESUMEN_ASUNTOS_FILAS':

				$row_tmpl = $html;
				$html = '';

				for ($k = 0; $k < count($this->asuntos); $k++) {
					$asunto = new Asunto($this->sesion);
					$asunto->LoadByCodigo($this->asuntos[$k]);

					$query = "SELECT
								asunto.codigo_asunto,
								asunto.codigo_asunto_secundario,
								asunto.glosa_asunto,
								SUM(TIME_TO_SEC(duracion_cobrada)) AS duracion_cobrada,
								SUM(monto_cobrado) as importe
							FROM trabajo
							JOIN asunto ON asunto.codigo_asunto=trabajo.codigo_asunto
							WHERE trabajo.codigo_asunto = '" . $asunto->fields['codigo_asunto'] . "'
								AND trabajo.id_cobro = '" . $this->fields['id_cobro'] . "'
								AND trabajo.cobrable = 1
								AND id_tramite=0
								AND duracion_cobrada > 0
								GROUP BY glosa_asunto ASC";

					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					while (list($codigo_asunto, $codigo_asunto_secundario, $glosa_asunto, $duracion_cobrada, $importe) = mysql_fetch_array($resp)) {
						$row = $row_tmpl;
						$horas = floor($duracion_cobrada / 3600);
						$minutes = (($duracion_cobrada / 60 ) % 60);
						$seconds = ($duracion_cobrada % 60);

						list($solo_codigo_cliente, $solo_codigo_asunto_secundario) = split("-", $codigo_asunto_secundario);

						$row = str_replace('%solo_codigo_asunto_secundario%', $solo_codigo_asunto_secundario, $row);

						$row = str_replace('%codigo_asunto%', $codigo_asunto, $row);
						$row = str_replace('%codigo_asunto_secundario%', $codigo_asunto_secundario, $row);
						$row = str_replace('%glosa_asunto%', $glosa_asunto, $row);
						$row = str_replace('%horas%', $horas . ':' . sprintf("%02d", $minutes), $row);

						if ($this->fields['forma_cobro'] == 'TASA' || $this->fields['forma_cobro'] == 'CAP' || $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
							$row = str_replace('%importe%', number_format($importe, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						}

						if ($this->fields['forma_cobro'] == 'FLAT FEE') {
							$row = str_replace('%importe%', '', $row);
						}

						$html .= $row;
					}
				}

				break;

			case 'RESUMEN_ASUNTOS_FILAS_FLAT_FEE':

				$row_tmpl = $html;
				$html = '';

				//	Asuntos Flat Fee : Lista asuntos asociados al cobro tabla "cobro_asuntos"

				$query = "SELECT asunto.codigo_asunto, asunto.codigo_asunto_secundario, asunto.glosa_asunto FROM cobro_asunto LEFT JOIN asunto ON ( cobro_asunto.codigo_asunto = asunto.codigo_asunto ) WHERE id_cobro ='{$this->fields['id_cobro']}'";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

				while (list($codigo_asunto_secundario_ff, $codigo_asunto_secundario_ff, $glosa_asunto_ff) = mysql_fetch_array($resp)) {
					$row = $row_tmpl;

					$row = str_replace('%codigo_asunto_ff%', $codigo_asunto_secundario_ff, $row);
					$row = str_replace('%codigo_asunto_secundario_ff%', $codigo_asunto_secundario_ff, $row);
					$row = str_replace('%glosa_asunto_ff%', $glosa_asunto_ff, $row);

					$html .= $row;
				}

				break;

			case 'RESUMEN_ESCALONES':

				break;

			case 'RESUMEN_ASUNTOS_TOTAL':

				$query = "
						SELECT SUM(TIME_TO_SEC(duracion_cobrada)) as duracion,SUM(monto_cobrado) as subtotal_sin_impuesto
						FROM trabajo
						JOIN asunto ON asunto.codigo_asunto=trabajo.codigo_asunto
						WHERE trabajo.id_cobro = '" . $this->fields['id_cobro'] . "'
						AND trabajo.cobrable = 1
						AND id_tramite=0";

				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

				while (list($duracion_cobrada, $subtotal_sin_impuesto) = mysql_fetch_array($resp)) {

					$horas = floor($duracion_cobrada / 3600);
					$minutes = (($duracion_cobrada / 60 ) % 60);
					$seconds = ($duracion_cobrada % 60);

					$valor_monto_contrato = $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto_contrato'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);

					$html = str_replace('%impuesto%', __('Impuesto'), $html);
					$html = str_replace('%total%', __('Total'), $html);
					$html = str_replace('%igv%', __('I.G.V.'), $html);
					$html = str_replace('%servicios_prestados%', __('Servicios prestados'), $html);
					$html = str_replace('%fecha_inicial%', __('Fecha desde'), $html);
					$html = str_replace('%fecha_final%', __('Fecha hasta'), $html);

					//	Se saca la fecha inicial según el primer trabajo para evitar que fecha desde sea 1969
					$query = "SELECT fecha FROM trabajo WHERE id_cobro='" . $this->fields['id_cobro'] . "' AND visible='1' ORDER BY fecha LIMIT 1";
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

					if (mysql_num_rows($resp) > 0) {
						list($fecha_primer_trabajo) = mysql_fetch_array($resp);
					} else {
						$fecha_primer_trabajo = $this->fields['fecha_fin'];
					}

					if ($lang == 'en') {
						$html = str_replace('%desde%', date('m/d/y', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? strtotime($fecha_primer_trabajo) : strtotime($this->fields['fecha_ini'])), $html);
						$html = str_replace('%hasta%', date('m/d/y', strtotime($this->fields['fecha_fin'])), $html);
					} else {
						$html = str_replace('%desde%', date('d-m-y', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? strtotime($fecha_primer_trabajo) : strtotime($this->fields['fecha_ini'])), $html);
						$html = str_replace('%hasta%', date('d-m-y', strtotime($this->fields['fecha_fin'])), $html);
					}

					if ($this->fields['forma_cobro'] == 'RETAINER') {
						$glosa_tipo_monto = __('Monto Retainer');
					}

					if ($this->fields['forma_cobro'] == 'FLAT FEE') {
						$glosa_tipo_monto = __('Monto Flat fee');
					}

					if ($this->fields['forma_cobro'] == 'PROPORCIONAL') {
						$glosa_tipo_monto = __('Monto Proporcional');
					}

					$tr_retainer .= '<tr class="tr_datos"><td width="10%">&nbsp;</td><td align="left" width="60%"><b>' . $glosa_tipo_monto . '</b></td><td align="right" width="30%">' . $valor_monto_contrato . '</td></tr>';

					if ($this->fields['forma_cobro'] == 'TASA' || $this->fields['forma_cobro'] == 'CAP') {
						$html = str_replace('%subtotal%', __('Subtotal'), $html);
						$html = str_replace('%monto_retainer%', '', $html);
						$html = str_replace('%valor_monto_contrato%', '', $html);
						$html = str_replace('%monto_total%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['impuesto'] + $subtotal_sin_impuesto, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
						$html = str_replace('%monto_subtotal%', $moneda->fields['simbolo'] . $this->espacio . number_format($subtotal_sin_impuesto, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
						$html = str_replace('%tr_retainer%', '', $html);
					}

					if ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
						$html = str_replace('%subtotal%', __('Subtotal Excesos'), $html);
						$html = str_replace('%monto_retainer%', __('Monto Retainer'), $html);
						$html = str_replace('%valor_monto_contrato%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto_contrato'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
						$html = str_replace('%monto_total%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['impuesto'] + $subtotal_sin_impuesto + $this->fields['monto_contrato'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
						$html = str_replace('%monto_subtotal%', $moneda->fields['simbolo'] . $this->espacio . number_format($subtotal_sin_impuesto, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
						$html = str_replace('%tr_retainer%', $tr_retainer, $html);
					}

					if ($this->fields['forma_cobro'] == 'FLAT FEE') {
						$html = str_replace('%subtotal%', '', $html);
						$html = str_replace('%monto_retainer%', __('Monto Flat fee'), $html);
						$html = str_replace('%valor_monto_contrato%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto_contrato'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
						$html = str_replace('%monto_total%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['impuesto'] + $subtotal_sin_impuesto + $this->fields['monto_contrato'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
						$html = str_replace('%monto_subtotal%', '', $html);
						$html = str_replace('%tr_retainer%', $tr_retainer, $html);
					}


					$html = str_replace('%total_horas%', $horas . ':' . sprintf("%02d", $minutes), $html);
					$html = str_replace('%monto_impuesto%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['impuesto'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}

				break;

			case 'RESTAR_RETAINER': //GenerarDocumento2
				if ($columna_hrs_retainer || $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL')
					$html = str_replace('%retainer%', __('Retainer'), $html);
				else
					$html = str_replace('%retainer%', '', $html);
				if ($columna_hrs_retainer || $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$html = str_replace('%valor_retainer%', '(' . $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['simbolo'] . $this->espacio . number_format($this->fields['monto_contrato'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . ')', $html);
				} else {
					$html = str_replace('%valor_retainer%', '', $html);
				}
				break;

			case 'DETALLE_COBRO_RETAINER': //GenerarDocumento2
				$html = str_replace('%horas_retainer%', 'Horas retainer', $html);
				$html = str_replace('%valor_horas_retainer%', Utiles::horaDecimal2HoraMinuto($this->fields['retainer_horas']), $html);
				$html = str_replace('%horas_adicionales%', 'Horas adicionales', $html);
				$html = str_replace('%valor_horas_adicionales%', Utiles::horaDecimal2HoraMinuto(($this->fields['total_minutos'] / 60) - $this->fields['retainer_horas']), $html);
				$html = str_replace('%honorarios_retainer%', 'Honorarios retainer', $html);
				$html = str_replace('%valor_honorarios_retainer%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $x_resultados['monto_contrato'][$this->fields['id_moneda']], $html);
				$html = str_replace('%honorarios_adicionales%', 'Honorarios adicionales', $html);
				$html = str_replace('%valor_honorarios_adicionales%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . ($x_resultados['monto'][$this->fields['id_moneda']] - $x_resultados['monto_contrato'][$this->fields['id_moneda']]), $html);
				break;

			case 'DETALLE_TARIFA_ADICIONAL': //GenerarDocumento2
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

			case 'FACTURA_NUMERO': //GenerarDocumento2
				$html = str_replace('%factura_nro%', __('Factura') . ' ' . __('N°'), $html);
				break;

			case 'NUMERO_FACTURA': //GenerarDocumento2
				$html = str_replace('%nro_factura%', $this->fields['documento'], $html);
				break;

			case 'DETALLE_HONORARIOS': //GenerarDocumento2
				$horas_cobrables = floor(($this->fields['total_minutos']) / 60);
				$minutos_cobrables = sprintf("%02d", $this->fields['total_minutos'] % 60);
				$duracion_cobrable_decimal = number_format($horas_cobrables + $minutos_cobrables / 60, 1, ',', '');
				$html = str_replace('%horas%', __('Total Horas'), $html);
				if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
					$html = str_replace('%valor_horas%', $duracion_cobrable_decimal, $html);
				} else {
					$html = str_replace('%valor_horas%', $horas_cobrables . ':' . $minutos_cobrables, $html);
				}
				$html = str_replace('%honorarios%', __('Honorarios'), $html);
				if (Conf::GetConf($this->sesion, 'UsarImpuestoSeparado') && $contrato->fields['usa_impuesto_separado']) {
					$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto'] - $this->fields['impuesto'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				} else {
					$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}

				if ($this->fields['opc_ver_descuento']) {
					$html = str_replace('%DETALLE_COBRO_DESCUENTO%', $this->GenerarDocumento2($parser, 'DETALLE_COBRO_DESCUENTO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
					$html = str_replace('%DETALLE_COBRO_DESCUENTO_NUEVO%', $this->GenerarDocumentoComun($parser, 'DETALLE_COBRO_DESCUENTO_NUEVO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%DETALLE_COBRO_DESCUENTO%', '', $html);
					$html = str_replace('%DETALLE_COBRO_DESCUENTO_NUEVO%', '', $html);
				}

				$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL%', $this->GenerarDocumentoComun($parser, 'DETALLE_COBRO_MONEDA_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				break;

			case 'DETALLE_TRAMITES': //GenerarDocumento2
				$html = str_replace('%tramites%', __('Trámites'), $html);
				$html = str_replace('%tramites_castropal%', __('Otros Servicios'), $html);
				$aproximacion_tramites = number_format($this->fields['monto_tramites'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$valor_tramites = $aproximacion_tramites * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'];
				$html = str_replace('%valor_tramites%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($valor_tramites, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				break;

			case 'DETALLE_GASTOS': //GenerarDocumento2
				$html = str_replace('%gastos%', __('Gastos'), $html);
				$total_gastos_moneda = 0;
				$impuestos_total_gastos_moneda = 0;

				$total_gastos_moneda = $x_cobro_gastos['gasto_total'];
				$impuestos_total_gastos_moneda = $x_cobro_gastos['gasto_impuesto'];
				if (Conf::GetConf($this->sesion, 'CalculacionCyC')) {
					$total_gastos_moneda = $x_cobro_gastos['gasto_total'];
				}
				$impuestos_total_gastos_moneda = $x_cobro_gastos['gasto_impuesto'];
				$html = str_replace('%valor_gastos%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($total_gastos_moneda, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			case 'DETALLE_COBRO_DESCUENTO': //GenerarDocumento2
				$html = str_replace('%honorarios%', __('Subtotal Honorarios'), $html);
				/* $cobro_moneda array de monedas al tiempo de emitir/generar el cobro */
				if ($this->fields['descuento'] == 0) {
					if (Conf::GetConf($this->sesion, 'FormatoNotaCobroMTA')) {

						$valor_honorarios = number_format($x_resultados['monto_subtotal'][$this->fields['opc_moneda_total']], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
						$html = str_replace('%valor_honorarios%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . $valor_honorarios, $html);
						$html = str_replace('%valor_descuento%', '', $html);
						$html = str_replace('%porcentaje_descuento%', '', $html);
						$html = str_replace('%descuento%', '', $html);
						break;
					} else {
						return '';
					}
				}

				$valor_honorarios = number_format($x_resultados['monto_subtotal'][$this->fields['opc_moneda_total']], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
				$valor_descuento = number_format($x_resultados['descuento'][$this->fields['opc_moneda_total']], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
				$valor_honorarios_demo = $x_resultados['monto_trabajos'][$this->fields['id_moneda']];

				if ($this->EsCobrado()) {
					$valor_descuento_demo = $x_resultados['descuento_honorarios'][$this->fields['id_moneda']];
				} else {
					$valor_descuento_demo = $x_resultados['descuento'][$this->fields['id_moneda']];
				}

				$html = str_replace('%valor_honorarios_demo%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($valor_honorarios_demo, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_descuento_demo%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($valor_descuento_demo, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				if (Conf::GetConf($this->sesion, 'CalculacionCyC')) {
					$html = str_replace('%valor_honorarios%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . $valor_honorarios, $html);
					$html = str_replace('%valor_descuento%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . $valor_descuento, $html);
				}

				$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto_subtotal'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%descuento%', __('Descuento'), $html);

				if ($x_resultados['monto_trabajos'][$this->fields['id_moneda']] > 0) {
					$porcentaje_demo = ($x_resultados['descuento'][$this->fields['id_moneda']] * 100) / $x_resultados['monto_subtotal'][$this->fields['id_moneda']];
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

			case 'RESUMEN_CAP': //GenerarDocumento2
				$html = str_replace('%desglose_cap%', __('Desglose CAP'), $html);
				$html = str_replace('%COBROS_DEL_CAP%', $this->GenerarDocumento2($parser, 'COBROS_DEL_CAP', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%texto_numero_cobro%', __('Nº Cobro'), $html);
				$html = str_replace('%texto_valor_cap_del_cobro%', __('Total Cobro'), $html);

				$html = str_replace('%total_utilizado_cobros%', __('Total Utilizado'), $html);
				$html = str_replace('%cap_utilizado_cobros%', $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['simbolo'] . $this->espacio . number_format($this->resumen_cap->monto_utilizado, $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				$html = str_replace('%glosa_cap%', __('CAP'), $html);

				$html = str_replace('%cap%', __('Total CAP'), $html);
				$html = str_replace('%valor_cap%', $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['simbolo'] . $this->espacio . number_format($this->fields['monto_contrato'], $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				$html = str_replace('%total_utilizado%', __('Total Utilizado'), $html);
				$html = str_replace('%cap_utilizado%', $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['simbolo'] . $this->espacio . number_format($this->resumen_cap->monto_utilizado, $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				$monto_restante = $this->fields['monto_contrato'] - $this->resumen_cap->monto_utilizado;
				$html = str_replace('%restante%', __('Monto restante'), $html);
				$html = str_replace('%valor_restante%', $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['simbolo'] . $this->espacio . number_format($monto_restante, $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				$html = str_replace('%salto_linea%', '<br>', $html);
				$html = str_replace('%hr%', '<hr size="2" class="separador">', $html);

				break;

			case 'COBROS_DEL_CAP': //GenerarDocumento2
				$this->resumen_cap = new stdClass();
				$this->resumen_cap->caps = $contrato->ObtenerCAPs($this->fields['id_contrato']);;
				$this->resumen_cap->monto_utilizado = 0;
				$row_tmpl = $html;
				$html = '';
				foreach ($this->resumen_cap->caps as $cap) {
					$row = $row_tmpl;

					$estado = '';
					if ($cap['estado'] == 'CREADO' || $cap['estado'] == 'EN REVISION') {
						$estado = __('(NO EMITIDO)');
					} else {
						$this->resumen_cap->monto_utilizado += $cap['monto_cap'];
					}

					$row = str_replace('%numero_cobro%', __('Cobro') . ' ' . $cap['id_cobro'] . ' ' . $estado, $row);
					$row = str_replace('%valor_cap_del_cobro%', $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['simbolo'] . $this->espacio . number_format($cap['monto_cap'], $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

					$html .= $row;
				}
				break;

			case 'ASUNTOS': //GenerarDocumento2
				$row_tmpl = $html;
				$html = '';
				if ($this->fields['opc_ver_asuntos_separados'] || !$this->tiene_tag_asuntos_no_separados) {
					for ($k = 0; $k < count($this->asuntos); $k++) {

						$asunto = new Asunto($this->sesion);
						$asunto->LoadByCodigo($this->asuntos[$k]);

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
										AND id_tramite=0 " . ($this->fields['opc_ver_cobrable'] ? "" : "AND trabajo.visible = 1");
						$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
						list($cont_trabajos) = mysql_fetch_array($resp);

						$query = "SELECT count(*) FROM cta_corriente
									 WHERE id_cobro=" . $this->fields['id_cobro'] . "
										AND codigo_asunto='" . $asunto->fields['codigo_asunto'] . "'";
						$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
						list($cont_gastos) = mysql_fetch_array($resp);
						$row = $row_tmpl;
						$row = str_replace('%separador%', '<hr size="2" class="separador">', $row);

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
								$row = str_replace('%TRABAJOS_ENCABEZADO%', $this->GenerarDocumento2($parser, 'TRABAJOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
								$row = str_replace('%TRABAJOS_FILAS%', $this->GenerarDocumento2($parser, 'TRABAJOS_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
								$row = str_replace('%TRABAJOS_TOTAL%', $this->GenerarDocumento2($parser, 'TRABAJOS_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
							} else {
								$row = str_replace('%espacio_trabajo%', '', $row);
								$row = str_replace('%servicios%', '', $row);
								$row = str_replace('%TRABAJOS_ENCABEZADO%', '', $row);
								$row = str_replace('%TRABAJOS_FILAS%', '', $row);
								$row = str_replace('%TRABAJOS_TOTAL%', '', $row);
							}
							$row = str_replace('%DETALLE_PROFESIONAL%', $this->GenerarDocumento2($parser, 'DETALLE_PROFESIONAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
						} else if ($this->fields['opc_mostrar_asuntos_cobrables_sin_horas'] == 1) {
							$row = str_replace('%espacio_trabajo%', '', $row);
							$row = str_replace('%DETALLE_PROFESIONAL%', '', $row);
							$row = str_replace('%servicios%', 'No existen trabajos asociados a este asunto.', $row);
							$row = str_replace('%TRABAJOS_ENCABEZADO%', '', $row);
							$row = str_replace('%TRABAJOS_FILAS%', '', $row);
							$row = str_replace('%TRABAJOS_TOTAL%', '', $row);
						} else {
							$row = str_replace('%espacio_trabajo%', '', $row);
							$row = str_replace('%DETALLE_PROFESIONAL%', '', $row);
							$row = str_replace('%servicios%', '', $row);
							$row = str_replace('%TRABAJOS_ENCABEZADO%', '', $row);
							$row = str_replace('%TRABAJOS_FILAS%', '', $row);
							$row = str_replace('%TRABAJOS_TOTAL%', '', $row);
						}

						/*
             Gastos implementado
            */
						if ($this->fields['opc_ver_gastos'] != 0) {
							/*
               Revisar si se trate sobre el nuevo template
               */
							if ($k == 0 && trim(strstr($row, '%GASTOS_FILAS%')) != '') {
								$templateNotaCobroGastosSeparados = 1;
							}
							foreach ($x_cobro_gastos['gasto_detalle'] as $d) {
								if ($this->asuntos[$k] == $d['codigo_asunto']) {
									$asunto_tiene_gastos = 1;
									break;
								}
							}

							if ($templateNotaCobroGastosSeparados && $asunto_tiene_gastos) {
								$asunto_tiene_gastos = 0;
								//$html = str_replace('%separador%', '<hr size="2" class="separador">', $html);
								$row = str_replace('%glosa_gastos%', __('Gastos'), $row);
								if ($lang == 'es') {
									$row = str_replace('%glosa_gasto%', __('GASTOS'), $row);
								} else {
									$row = str_replace('%glosa_gasto%', __('EXPENSES'), $row);
								}
								$row = str_replace('%expenses%', __('%expenses%'), $row); //en vez de Disbursements es Expenses en inglés
								$row = str_replace('%detalle_gastos%', __('Detalle de gastos'), $row);

								$row = str_replace('%GASTOS_ENCABEZADO%', $this->GenerarDocumento2($parser, 'GASTOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
								$row = str_replace('%GASTOS_FILAS%', $this->GenerarDocumento2($parser, 'GASTOS_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
								$row = str_replace('%GASTOS_TOTAL%', $this->GenerarDocumento2($parser, 'GASTOS_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
							} else {
								//$html = str_replace('%separador%', '<hr size="2" class="separador">', $html);
								$row = str_replace('%glosa_gastos%', '', $row);
								if ($lang == 'es') {
									$row = str_replace('%glosa_gasto%', '', $row);
								} else {
									$row = str_replace('%glosa_gasto%', '', $row);
								}
								$row = str_replace('%expenses%', '', $row); //en vez de Disbursements es Expenses en inglés
								$row = str_replace('%detalle_gastos%', '', $row);

								$row = str_replace('%GASTOS_ENCABEZADO%', '', $row);
								$row = str_replace('%GASTOS_FILAS%', '', $row);
								$row = str_replace('%GASTOS_TOTAL%', '', $row);
							}
						}


						$query_hitos = "SELECT count(*) from cobro_pendiente where hito=1 and id_cobro=" . $this->fields['id_cobro'];
						$resp_hitos = mysql_query($query_hitos, $this->sesion->dbh) or Utiles::errorSQL($query_hitos, __FILE__, __LINE__, $this->sesion->dbh);

						list($cont_hitos) = mysql_fetch_array($resp_hitos);
						$row = str_replace('%hitos%', '<br>' . __('Hitos') . '<br/><br/>', $row);
						if ($cont_hitos > 0) {
							global $total_hitos, $estehito, $cantidad_hitos, $moneda_hitos, $tipo_cambio_hitos;

							$row = str_replace('%HITOS_FILAS%', $this->GenerarDocumento2($parser, 'HITOS_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
							$row = str_replace('%HITOS_TOTAL%', $this->GenerarDocumento2($parser, 'HITOS_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
							$row = str_replace('%HITOS_ENCABEZADO%', $this->GenerarDocumento2($parser, 'HITOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
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
						// El parametro separar_asunto se define para asegurarse que solamente se separan los asuntos,
						// cuando el template de ese cliente lo soporta.
						$asunto->separar_asuntos = true;
						if (Conf::GetConf($this->sesion, 'ParafoGastosSoloSiHayGastos')) {
							if ($cont_gastos > 0) {
								$row = str_replace('%GASTOS%', $this->GenerarDocumento2($parser, 'GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
							} else {
								$row = str_replace('%GASTOS%', '', $row);
							}
						} else {
							$row = str_replace('%GASTOS%', $this->GenerarDocumento2($parser, 'GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
						}

						$asunto->separar_asuntos = false;
						#especial mb
						$row = str_replace('%codigo_asunto_mb%', __('Código M&B'), $row);

						if ($cont_trabajos > 0 || $cont_hitos > 0 || $asunto->fields['trabajos_total_duracion'] > 0 || $asunto->fields['trabajos_total_duracion_trabajada'] > 0 || $cont_tramites > 0 || ($cont_gastos > 0 && $templateNotaCobroGastosSeparados) || Conf::GetConf($this->sesion, 'MostrarAsuntosSinTrabajosGastosTramites') || ($this->fields['opc_mostrar_asuntos_cobrables_sin_horas'] == 1 && !$this->get_detalle_en_asuntos())) {
							$html .= $row;
						}

						$html = str_replace('%texto_servicios_profesionales%', __('Servicios Profesionales por hora'), $html);
						$html = str_replace('%descripcion_servicios%', __('Descripción de Servicios'), $html);
						$html = str_replace('%para_los_servicios_prestados%', __('Para los servicios profesionales prestados'), $html);
					}
				}
				break;

			case 'ASUNTOS_NO_SEPARADOS': //GeneraDocumento2
				$row_tmpl = $html;
				$html = '';
				if (!$this->fields['opc_ver_asuntos_separados']) {
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
					$row_tmpl = str_replace('%asuntos_no_separados%', __('Asuntos'), $row_tmpl);
					$row_tmpl = str_replace('%TRABAJOS_ENCABEZADO%', $this->GenerarDocumento2($parser, 'TRABAJOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row_tmpl);
					$rows = array();
					$totales_row = array();
					for ($k = 0; $k < count($this->asuntos); $k++) {
						$asunto = new Asunto($this->sesion);
						$asunto->LoadByCodigo($this->asuntos[$k]);
						$Criteria = new Criteria($this->sesion);
						$Criteria->add_select('count(*)', 'cantidad')
							->add_from('trabajo')
							->add_restriction(CriteriaRestriction::equals('id_cobro', $this->fields['id_cobro']))
							->add_restriction(CriteriaRestriction::equals('codigo_asunto', "'{$asunto->fields['codigo_asunto']}'"))
							->add_restriction(CriteriaRestriction::equals('id_tramite', 0));
						if ($this->fields['opc_ver_cobrable']) {
							$Criteria->add_restriction(CriteriaRestriction::equals('visible', 1));
						}
						$trabajos = $Criteria->run();
						$cont_trabajos = $trabajos[0]['cantidad'];
						if ($cont_trabajos > 0) {
							$rows[] = $this->GenerarDocumento2($parser, 'TRABAJOS_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto);
						}
					}
					$html .= $row_tmpl;
					$html = str_replace('%TRABAJOS_FILAS%', implode(' ', $rows), $html);
					$html = str_replace('%TRABAJOS_TOTAL_AGRUPADO%', $this->GenerarDocumento2($parser, 'TRABAJOS_TOTAL_AGRUPADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				}
				break;

			case 'TRAMITES': //GenerarDocumento2
				$row_tmpl = $html;
				$html = '';
				for ($k = 0; $k < count($this->asuntos); $k++) {
					$asunto = new Asunto($this->sesion);
					$asunto->LoadByCodigo($this->asuntos[$k]);

					$categoria_duracion_horas = 0;
					$categoria_duracion_minutos = 0;
					$categoria_valor = 0;
					$total_trabajos_categoria = '';
					$encabezado_trabajos_categoria = '';

					$query = "SELECT count(*) FROM CTA_CORRIENTE WHERE id_cobro=" . $this->fields['id_cobro'];
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

					$row = str_replace('%TRAMITES_ENCABEZADO%', $this->GenerarDocumentoComun($parser, 'TRAMITES_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
					$row = str_replace('%TRAMITES_FILAS%', $this->GenerarDocumentoComun($parser, 'TRAMITES_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
					$row = str_replace('%TRAMITES_TOTAL%', $this->GenerarDocumentoComun($parser, 'TRAMITES_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);

					$row = str_replace('%DETALLE_PROFESIONAL%', $this->GenerarDocumento2($parser, 'DETALLE_PROFESIONAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);

					if (Conf::GetConf($this->sesion, 'ParafoGastosSoloSiHayGastos')) {
						if ($cont_gastos > 0) {
							$row = str_replace('%GASTOS%', $this->GenerarDocumento2($parser, 'GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
						} else {
							$row = str_replace('%GASTOS%', '', $row);
						}
					} else {
						$row = str_replace('%GASTOS%', $this->GenerarDocumento2($parser, 'GASTOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $row);
					}

					$row = str_replace('%codigo_asunto_mb%', __('Código M&B'), $row);

					if ($asunto->fields['trabajos_total_duracion'] > 0 || $asunto->fields['trabajos_total_duracion_trabajada'] > 0) {
						$html .= $row;
					}
				}
				break;

			case 'TRABAJOS_ENCABEZADO': //GenerarDocumento2

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
				$html = str_replace('%num_registro%', __('Nº Registro'), $html);
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
				$html = str_replace('%horas%', __('Horas'), $html);
				$html = str_replace('%monto%', __('Monto'), $html);
				$html = str_replace('%abogado_raz%', __('abogado_raz'), $html);
				$html = str_replace('%tiempo_raz%', __('tiempo_raz'), $html);

				if ($this->fields['opc_ver_columna_cobrable']) {
					$html = str_replace('%cobrable%', __('<td align="center" width="80">Cobrable</td>'), $html);  // tAndres Oestemer
				} else {
					$html = str_replace('%cobrable%', '', $html);
				}

				if (Conf::GetConf($this->sesion, 'OrdenarPorCategoriaUsuario')) {
					if (!empty($this->siguiente['categoria_abogado'])) {
						$categoria = $this->siguiente['categoria_abogado'];
						unset($this->siguiente['categoria_abogado']);
					} else {

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
					}
					$html = str_replace('%categoria_abogado%', __($categoria), $html);
				} else if (Conf::GetConf($this->sesion, 'SepararPorUsuario')) {
					if (!empty($this->siguiente['nombre_usuario'])) {
						$abogado = $this->siguiente['nombre_usuario'];
						$tarifa = $this->siguiente['tarifa_usuario'];

						unset($this->siguiente['nombre_usuario']);
						unset($this->siguiente['tarifa_usuario']);
					} else {
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
					}
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
					$html = str_replace('%duracion_descontada_bmahj%', __('Hrs. Castigadas'), $html);
					$html = str_replace('%duracion_descontada%', __('Hrs.:Mins. Descontadas'), $html);

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

				if ($ImprimirValorTrabajo && $this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION') {
					$html = str_replace('%valor%', '', $html);
				} else {
					$html = str_replace('%valor%', __('Valor'), $html);
				}
				$html = str_replace('%valor_siempre%', __('Valor'), $html);
				$html = str_replace('%tarifa_fee%', __('%tarifa_fee%'), $html);

				if ($this->fields['opc_ver_detalles_por_hora_categoria'] == 1) {
					$html = str_replace('%td_categoria%', '<td class="td_categoria" align="left">%categoria%</td>', $html);
				} else {
					$html = str_replace('%td_categoria%', '', $html);
				}
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

			case 'TRABAJOS_FILAS': //GenerarDocumento2
				global $categoria_duracion_horas;
				global $categoria_duracion_minutos;
				global $categoria_valor;

				$row_tmpl = $html;
				$html = '';
				$where_horas_cero = '';

				//esto funciona por Conf si el metodo del conf OrdenarPorCategoriaUsuarioe s true se ordena por categoria
				if (Conf::GetConf($this->sesion, 'TrabajosOrdenarPorCategoriaNombreUsuario')) {
					$select_categoria = ", prm_categoria_usuario.id_categoria_usuario";
					$order_categoria = "prm_categoria_usuario.orden, usuario.id_usuario, ";
				} else if (Conf::GetConf($this->sesion, 'TrabajosOrdenarPorCategoriaUsuario')) {
					$select_categoria = ", prm_categoria_usuario.id_categoria_usuario";
					$order_categoria = "prm_categoria_usuario.orden, usuario.id_usuario, ";
				} elseif (Conf::GetConf($this->sesion, 'SepararPorUsuario')) {
					$select_categoria = ", prm_categoria_usuario.id_categoria_usuario";
					$order_categoria = "usuario.id_categoria_usuario, usuario.id_usuario, ";
				} elseif (Conf::GetConf($this->sesion, 'TrabajosOrdenarPorCategoriaDetalleProfesional')) {
					$select_categoria = "";
					$order_categoria = "usuario.id_categoria_usuario DESC, ";
				} elseif (Conf::GetConf($this->sesion, 'TrabajosOrdenarPorFechaCategoria')) {
					$select_categoria = ", prm_categoria_usuario.id_categoria_usuario";
					$order_categoria = "trabajo.fecha, usuario.id_categoria_usuario, usuario.id_usuario, ";
				} else {
					$select_categoria = "";
					$order_categoria = "";
				}

				if (!method_exists('Conf', 'MostrarHorasCero')) {
					if ($this->fields['opc_ver_horas_trabajadas'])
						$where_horas_cero = "AND trabajo.duracion > '0000-00-00 00:00:00'";
					else
						$where_horas_cero = "AND trabajo.duracion_cobrada > '0000-00-00 00:00:00'";
				}

				if ($this->fields['opc_ver_valor_hh_flat_fee'] && $this->fields['forma_cobro'] != 'ESCALONADA') {
					$dato_monto_cobrado = " ( trabajo.tarifa_hh * TIME_TO_SEC( trabajo.duracion_cobrada ) ) / 3600 ";
				} else {
					$dato_monto_cobrado = " trabajo.monto_cobrado ";
				}

				if ($this->fields['opc_ver_horas_trabajadas'] == 0) {
					$cobrable = " AND trabajo.cobrable = 1";
				}

				if ($this->fields['opc_ver_cobrable']) {
					$visible = "";
					if ($this->fields['opc_ver_horas_trabajadas'] == 0) {
						$cobrable = " AND ((trabajo.cobrable = 0 AND trabajo.visible = 0)
										OR (trabajo.cobrable = 1 AND trabajo.visible = 1))";
					}
				} else {
					$visible = "AND trabajo.visible = 1";
				}

				if ($lang == 'es') {
					$query_categoria_lang = "prm_categoria_usuario.glosa_categoria AS categoria,";
				} else {
					$query_categoria_lang = "IFNULL(prm_categoria_usuario.glosa_categoria_lang ,prm_categoria_usuario.glosa_categoria) AS categoria,";
				}

				/*
				 * 	Contenido de filas de seccion trabajo.
				 */
				$query = "SELECT SQL_CALC_FOUND_ROWS
									IF(trabajo.cobrable,trabajo.duracion_cobrada,'00:00:00') as duracion_cobrada,
									trabajo.duracion_retainer,
									trabajo.descripcion,
									trabajo.fecha,
									trabajo.id_usuario,
									$dato_monto_cobrado as monto_cobrado,
									trabajo.visible,
									trabajo.cobrable,
									trabajo.id_trabajo,
									trabajo.tarifa_hh,
									IF (trabajo.cobrable, trabajo.tarifa_hh * ( TIME_TO_SEC( duracion_cobrada ) / 3600 ),0) as importe,
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
							$cobrable
							$visible
							AND trabajo.id_tramite=0 $where_horas_cero
							ORDER BY $order_categoria trabajo.fecha ASC,trabajo.descripcion";

				$lista_trabajos = new ListaTrabajos($this->sesion, '', $query);

				$asunto->fields['trabajos_total_duracion'] = 0;
				$asunto->fields['trabajos_total_valor'] = 0;
				$asunto->fields['trabajos_total_duracion_retainer'] = 0;
				$asunto->fields['trabajos_total_importe'] = 0;

				for ($i = 0; $i < $lista_trabajos->num; $i++) {
					$trabajo = $lista_trabajos->Get($i);

					$total_trabajo_importe = $trabajo->fields['importe'];
					$total_trabajo_monto_cobrado = $trabajo->fields['monto_cobrado'];
					$tarifa_hh = $trabajo->fields['tarifa_hh'];
					$duracion_cobrada = $trabajo->fields['duracion_cobrada'];
					$duracion_retainer = $trabajo->fields['duracion_retainer'];
					$duracion = $trabajo->fields['duracion'];
					$retainer_cobro = $this->fields['retainer_horas'];

					list($h, $m, $s) = split(":", $duracion_cobrada);
					list($h_retainer, $m_retainer, $s_retainer) = split(":", $duracion_retainer);
					list($ht, $mt, $st) = split(":", $duracion);

					$duracion_cobrada_decimal = $h + $m / 60 + $s / 3600;
					$asunto->fields['trabajos_total_duracion'] += $h * 60 + $m + $s / 60;
					$asunto->fields['trabajos_total_valor'] += $trabajo->fields['monto_cobrado'];
					$asunto->fields['trabajos_total_duracion_retainer'] += $h_retainer * 60 + $m_retainer + $s_retainer / 60;
					$asunto->fields['trabajos_total_importe'] += $trabajo->fields['importe'];
					$asunto->fields['trabajos_total_duracion_trabajada'] += $ht * 60 + $mt + $st / 60;
					$duracion_decimal_trabajada = $ht + $mt / 60 + $st / 3600;
					$duracion_decimal_retainer = $h_retainer + $m_retainer / 60 + $s_retainer / 3600;
					$duracion_decimal_descontada = $ht - $h + ($mt - $m) / 60 + ($st - $s) / 3600;
					if ($horas_retainer - $horas_trabajadas < 0) {
						$duracion_decimal_descontada = $duracion_decimal_descontada - $duracion_decimal_retainer;
					}

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
					$categoria_duracion_trabajada += $duracion_decimal_trabajada;
					$categoria_duracion_descontada += $duracion_decimal_descontada;

					$row = $row_tmpl;
					$row = str_replace('%valor_codigo_asunto%', $trabajo->fields['codigo_asunto'], $row);
					$row = str_replace('%fecha%', Utiles::sql2fecha($trabajo->fields['fecha'], $idioma->fields['formato_fecha']), $row);
					if ($this->fields['estado'] == 'CREADO' || $this->fields['estado'] == 'EN REVISION') {
						$row = str_replace('%td_id_trabajo%', '<td align="center">%ntrabajo%</td>', $row);
					} else {
						$row = str_replace('%td_id_trabajo%', '', $row);
					}
					$row = str_replace('%ntrabajo%', $trabajo->fields['id_trabajo'], $row);
					$row = str_replace('%descripcion%', ucfirst(stripslashes($trabajo->fields['descripcion'])), $row);
					$row = str_replace('%descripcion_mayus%', strtoupper($trabajo->fields['descripcion']), $row);
					if ($this->fields['opc_ver_solicitante']) {
						$row = str_replace('%td_solicitante%', '<td align="left">%solicitante%</td>', $row);
					} else {
						$row = str_replace('%td_solicitante%', '', $row);
					}
					$row = str_replace('%solicitante%', $this->fields['opc_ver_solicitante'] ? $trabajo->fields['solicitante'] : '', $row);

					$row = str_replace('%username%', $trabajo->fields['username'], $row);

					if ($this->fields['opc_ver_detalles_por_hora_iniciales']) {
						$row = str_replace('%profesional%', $trabajo->fields['username'], $row);
					} else {
						$row = str_replace('%profesional%', $trabajo->fields['nombre_usuario'], $row);
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
						$row = str_replace('%tarifa%', number_format(($total_trabajo_monto_cobrado / $duracion_cobrada_decimal), $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%tarifa_ajustada%', number_format($trabajo->fields['tarifa_hh'] * $x_factor_ajuste, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					} else {
						$row = str_replace('%tarifa%', number_format($trabajo->fields['tarifa_hh'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%tarifa_ajustada%', number_format($trabajo->fields['tarifa_hh'] * $x_factor_ajuste, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					}

					if ($this->fields['opc_ver_detalles_por_hora_importe'] == 1) {
						$row = str_replace('%td_importe%', '<td align="center">%importe%</td>', $row);
						$row = str_replace('%td_importe_ajustado%', '<td align="center">%importe_ajustado%</td>', $row);
					} else {
						$row = str_replace('%td_importe%', '', $row);
						$row = str_replace('%td_importe_ajustado%', '', $row);
					}
					if ($this->fields['forma_cobro'] == 'ESCALONADA') {
						$row = str_replace('%importe%', number_format($total_trabajo_monto_cobrado, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					} else {
						$row = str_replace('%importe%', number_format($total_trabajo_importe, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					}
					$row = str_replace('%importe_ajustado%', number_format($total_trabajo_importe * $x_factor_ajuste, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

					//paridad
					$row = str_replace('%paridad%', $i % 2 ? 'impar' : 'par', $row);

					$row = str_replace('%iniciales%', $trabajo->fields['username'], $row);

					$ImprimirDuracionTrabajada = Conf::GetConf($this->sesion, 'ImprimirDuracionTrabajada');

					if ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
						$row = str_replace('%td_retainer%', '<td align="center">%duracion_retainer%</td>', $row);
						if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
							$row = str_replace('%duracion_retainer%', number_format($duracion_decimal_retainer, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $row);
						} else {
							$row = str_replace('%duracion_retainer%', $h_retainer . ':' . sprintf("%02d", $m_retainer), $row);
						}
					} else {
						$row = str_replace('%td_retainer%', '', $row);
					}

					if ($this->fields['forma_cobro'] == 'FLAT FEE') {
						$row = str_replace('%duracion_decimal_trabajada%', '', $row);
						$row = str_replace('%duracion_trabajada%', '', $row);
						$row = str_replace('%duracion_decduracion_trabajadaimal_descontada%', '', $row);
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
					} else {
						$row = str_replace('%cobrable%', __(''), $row);
					}

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

								// Permite a TRABAJOS_ENCABEZADO poner la categoría correcta reutilizando la lógica
								$this->siguiente['categoria_abogado'] = $trabajo_siguiente->fields['categoria'];
								$encabezado_trabajos_categoria .= $this->GenerarDocumento2($parser, 'TRABAJOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto);

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

								if ($this->fields['opc_ver_detalles_por_hora_importe'] == 1) {
									$html3 = str_replace('%td_importe%', '<td align="center">%importe%</td>', $html3);
								} else {
									$html3 = str_replace('%td_importe%', '', $html3);
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

								// Permite a TRABAJOS_ENCABEZADO poner el nombre correcto reutilizando la lógica
								$this->siguiente['nombre_usuario'] = $trabajo_siguiente->fields['nombre_usuario'];
								$this->siguiente['tarifa_usuario'] = $trabajo_siguiente->fields['tarifa_hh'];
								$encabezado_trabajos_categoria .= $this->GenerarDocumento2($parser, 'TRABAJOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto);

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
								$html3 = str_replace('%td_importe%', '<td align="center">%importe%</td>', $html3);
								$html3 = str_replace('%td_importe_ajustado%', '<td align="center">%importe_ajustado%</td>', $html3);
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

			case 'TRABAJOS_TOTAL_AGRUPADO': //GeneraDocumento2
				if ($this->fields['estado'] == 'CREADO' || $this->fields['estado'] == 'EN REVISION') {
					$html = str_replace('%td_id_trabajo%', '<td align="center">%ntrabajo%</td>', $html);
				} else {
					$html = str_replace('%td_id_trabajo%', '', $html);
				}
				$html = str_replace('%ntrabajo%', __('&nbsp;'), $html);
				$duracion_trabajada_total = 0;
				$duracion_cobrada_total = 0;
				$duracion_retainer_total = 0;
				$duracion_descontada_total = 0;
				$monto_total = 0;
				$ImprimirDuracionTrabajada = Conf::GetConf($this->sesion, 'ImprimirDuracionTrabajada');
				for ($k = 0; $k < count($this->asuntos); $k++) {
					$asunto = new Asunto($this->sesion);
					$asunto->LoadByCodigo($this->asuntos[$k]);
					$Criteria = new Criteria($this->sesion);
					$Criteria->add_select('count(*)', 'cantidad')
						->add_from('trabajo')
						->add_restriction(CriteriaRestriction::equals('id_cobro', $this->fields['id_cobro']))
						->add_restriction(CriteriaRestriction::equals('codigo_asunto', "'{$asunto->fields['codigo_asunto']}'"))
						->add_restriction(CriteriaRestriction::equals('id_tramite', 0));
					if ($this->fields['opc_ver_cobrable']) {
						$Criteria->add_restriction(CriteriaRestriction::equals('visible', 1));
					}
					$trabajos = $Criteria->run();
					$cont_trabajos = $trabajos[0]['cantidad'];
					if ($cont_trabajos > 0) {
						//Esto es sólo para acumular totales en los asuntos :O
						$this->GenerarDocumento2($parser, 'TRABAJOS_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto);
						//Ahora a acumular
						$duracion_trabajada_total = $duracion_trabajada_total + ($asunto->fields['trabajos_total_duracion_trabajada']) / 60;
						$duracion_cobrada_total = $duracion_cobrada_total + ($asunto->fields['trabajos_total_duracion']) / 60;
						$duracion_retainer_total = $duracion_retainer_total + ($asunto->fields['trabajos_total_duracion_retainer']) / 60;
						$duracion_descontada_total = $duracion_trabajada_total - $duracion_cobrada_total;
						$monto_total = $monto_total + $asunto->fields['trabajos_total_importe'];
					}
				}
				if ($this->fields['opc_ver_solicitante']) {
					$html = str_replace('%td_solicitante%', '<td>&nbsp;</td>', $html);
				} else {
					$html = str_replace('%td_solicitante%', '', $html);
				}
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

				if ($this->fields['opc_ver_detalles_por_hora_importe'] == 1) {
					$html = str_replace('%td_importe%', '<td align="center">%importe%</td>', $html);
					$html = str_replace('%td_importe_ajustado%', '<td align="center">%importe_ajustado%</td>', $html);
				} else {
					$html = str_replace('%td_importe%', '', $html);
					$html = str_replace('%td_importe_ajustado%', '', $html);
				}
				$html = str_replace('%importe%', $moneda->fields['simbolo'] . $this->espacio . number_format($monto_total, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%importe_ajustado%', $moneda->fields['simbolo'] . $this->espacio . number_format($monto_total * $x_factor_ajuste, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				if ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$html = str_replace('%td_retainer%', '<td align="center">%duracion_retainer%</td>', $html);
					if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
						$html = str_replace('%duracion_retainer%', number_format($duracion_retainer_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html);
					} else {
						$html = str_replace('%duracion_retainer%', Utiles::Decimal2GlosaHora($duracion_retainer_total), $html);
					}
				} else {
					$html = str_replace('%td_retainer%', '', $html);
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


				if ($this->fields['opc_ver_columna_cobrable'] == 1) {
					$html = str_replace('%cobrable%', __('<td>&nbsp;</td>'), $html);
				} else {
					$html = str_replace('%cobrable%', __(''), $html);
				}

				$ImprimirValorTrabajo = Conf::GetConf($this->sesion, 'ImprimirValorTrabajo');

				$aproximacion_monto_cyc = number_format($this->fields['monto_subtotal'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$subtotal_en_moneda_cyc = $aproximacion_monto_cyc * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']);

				if ($ImprimirValorTrabajo && $this->fields['estado'] != 'CREADO' && $this->fields['estado'] != 'EN REVISION') {
					$html = str_replace('%valor%', '', $html);
					$html = str_replace('%valor_cyc%', '', $html);
				} else {
					$html = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($subtotal_en_moneda_cyc, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					$html = str_replace('%valor%', $moneda->fields['simbolo'] . $this->espacio . number_format($monto_total, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}
				$html = str_replace('%valor_siempre%', $moneda->fields['simbolo'] . $this->espacio . number_format($monto_total, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%total_raz%', __('total_raz'), $html);

				break;


			case 'TRABAJOS_TOTAL': //GenerarDocumento2
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

				if ($this->fields['opc_ver_solicitante']) {
					$html = str_replace('%td_solicitante%', '<td>&nbsp;</td>', $html);
				} else {
					$html = str_replace('%td_solicitante%', '', $html);
				}
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

				if ($this->fields['opc_ver_detalles_por_hora_importe'] == 1) {
					$html = str_replace('%td_importe%', '<td align="center">%importe%</td>', $html);
					$html = str_replace('%td_importe_ajustado%', '<td align="center">%importe_ajustado%</td>', $html);
				} else {
					$html = str_replace('%td_importe%', '', $html);
					$html = str_replace('%td_importe_ajustado%', '', $html);
				}
				$html = str_replace('%importe%', $moneda->fields['simbolo'] . $this->espacio . number_format($asunto->fields['trabajos_total_importe'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%importe_ajustado%', $moneda->fields['simbolo'] . $this->espacio . number_format($asunto->fields['trabajos_total_importe'] * $x_factor_ajuste, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				if ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$html = str_replace('%td_retainer%', '<td align="center">%duracion_retainer%</td>', $html);
					if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
						$html = str_replace('%duracion_retainer%', number_format($duracion_retainer_total, Conf::GetConf($this->sesion, 'CantidadDecimalesIngresoHoras'), ',', ''), $html);
					} else {
						$html = str_replace('%duracion_retainer%', Utiles::Decimal2GlosaHora($duracion_retainer_total), $html);
					}
				} else {
					$html = str_replace('%td_retainer%', '', $html);
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


				if ($this->fields['opc_ver_columna_cobrable'] == 1) {
					$html = str_replace('%cobrable%', __('<td>&nbsp;</td>'), $html);
				} else {
					$html = str_replace('%cobrable%', __(''), $html);
				}

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

			case 'DETALLE_PROFESIONAL': //GenerarDocumento2
				global $columna_hrs_retainer;
				if ($this->fields['opc_ver_profesional'] == 0) {
					return '';
				}
				$html = str_replace('%glosa_profesional%', __('Detalle profesional'), $html);
				$html = str_replace('%detalle_tiempo_por_abogado%', __('Detalle tiempo por abogado'), $html);
				$html = str_replace('%detalle_honorarios%', __('Detalle de honorarios profesionales'), $html);
				$html = str_replace('%PROFESIONAL_ENCABEZADO%', $this->GenerarSeccionResumenProfesional($parser, 'PROFESIONAL_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%PROFESIONAL_FILAS%', $this->GenerarDocumento2($parser, 'PROFESIONAL_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%PROFESIONAL_TOTAL%', $this->GenerarDocumento2($parser, 'PROFESIONAL_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);

				if ($this->fields['opc_ver_descuento']) {
					$html = str_replace('%DETALLE_COBRO_DESCUENTO%', $this->GenerarDocumento2($parser, 'DETALLE_COBRO_DESCUENTO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
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

			case 'IMPUESTO': //GenerarDocumento2
				if ($this->fields['porcentaje_impuesto'] > 0 && $this->fields['porcentaje_impuesto_gastos'] > 0 && $this->fields['porcentaje_impuesto'] != $this->fields['porcentaje_impuesto_gastos'])
					$html = str_replace('%impuesto%', __('Impuesto') . ' (' . $this->fields['porcentaje_impuesto'] . '% / ' . $this->fields['porcentaje_impuesto_gastos'] . '% )', $html);
				else if ($this->fields['porcentaje_impuesto'] > 0)
					$html = str_replace('%impuesto%', __('Impuesto') . ' (' . $this->fields['porcentaje_impuesto'] . '%)', $html);
				else if ($this->fields['porcentaje_impuesto_gastos'] > 0)
					$html = str_replace('%impuesto%', __('Impuesto') . ' (' . $this->fields['porcentaje_impuesto_gastos'] . '%)', $html);
				else
					$html = str_replace('%impuesto%', '', $html);

				$html = str_replace('%impuesto_mta%', __('Impuesto') . ' (' . $this->fields['porcentaje_impuesto'] . '%)', $html);

				$impuesto_moneda_total = $x_resultados['monto_iva'][$this->fields['opc_moneda_total']];

				$html = str_replace('%valor_impuesto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($impuesto_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				// Muñoz y Tamayo
				$impuesto_solo_honorarios = $x_resultados['monto_iva_hh'][$this->fields['opc_moneda_total']];
				$html = str_replace('%valor_impuesto_honorarios%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($impuesto_solo_honorarios, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				break;

			case 'IMPUESTO_HONORARIOS': //GenerarDocumento2
				if ($this->fields['porcentaje_impuesto'] > 0) {
					$html = str_replace('%impuesto%', __('Impuesto') . ' (' . $this->fields['porcentaje_impuesto'] . '%)', $html);
				} else {
					$html = str_replace('%impuesto%', __('Impuesto'), $html);
				}

				$html = str_replace('%valor_impuesto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($x_resultados['impuesto'][$this->fields['opc_moneda_total']], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				break;

			case 'IMPUESTO_GASTOS': //GenerarDocumento2
				if ($this->fields['porcentaje_impuesto_gastos'] > 0) {
					$html = str_replace('%impuesto_gastos%', __('Impuesto') . ' (' . $this->fields['porcentaje_impuesto_gastos'] . '%)', $html);
				} else {
					$html = str_replace('%impuesto_gastos%', __('Impuesto'), $html);
				}

				$html = str_replace('%valor_impuesto_gastos%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($x_resultados['impuesto_gastos'][$this->fields['opc_moneda_total']], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				break;

			case 'ADELANTOS_FILAS': //GenerarDocumento2
				$saldo = 0;
				$moneda = $cobro_moneda->moneda[$this->fields['opc_moneda_total']];

				//Adelantos
				$query = "
				SELECT documento.glosa_documento, documento.saldo_pago, prm_moneda.tipo_cambio
				FROM documento
				LEFT JOIN prm_moneda ON prm_moneda.id_moneda = documento.id_moneda
				WHERE documento.codigo_cliente = '" . $this->fields['codigo_cliente'] . "' AND documento.es_adelanto = 1 AND documento.saldo_pago < 0";
				$adelantos = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				while ($adelanto = mysql_fetch_assoc($adelantos)) {
					$fila_adelanto_ = str_replace('%descripcion%', $adelanto['glosa_documento'], $html);
					$fila_adelanto_ = str_replace('%saldo_pago%', $moneda['simbolo'] . $adelanto['saldo_pago'] * $adelanto['tipo_cambio'] / $moneda['tipo_cambio'], $fila_adelanto_);
					$saldo += (int) $adelanto['saldo_pago'];
					$fila_adelantos .= $fila_adelanto_;
				}

				$fila_adelantos .= '<tr><td colspan="2">&nbsp;</td></tr>';

				//Pagos
				$query = "
				SELECT documento.glosa_documento, IF(documento.saldo_pago = 0, 0, (documento.saldo_pago * -1)) AS saldo_pago, prm_moneda.tipo_cambio
				FROM documento
				LEFT JOIN prm_moneda ON prm_moneda.id_moneda = documento.id_moneda
				WHERE documento.codigo_cliente = '" . $this->fields['codigo_cliente'] . "' AND documento.es_adelanto <> 1 AND documento.tipo_doc NOT IN ('N') AND documento.saldo_pago > 0";
				$adelantos = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				while ($adelanto = mysql_fetch_assoc($adelantos)) {
					$fila_adelanto_ = str_replace('%descripcion%', $adelanto['glosa_documento'], $html);
					$fila_adelanto_ = str_replace('%saldo_pago%', $moneda['simbolo'] . $adelanto['saldo_pago'] * $adelanto['tipo_cambio'] / $moneda['tipo_cambio'], $fila_adelanto_);
					$saldo += (int) $adelanto['saldo_pago'];
					$fila_adelantos .= $fila_adelanto_;
				}

				$fila_adelantos .= '<tr><td colspan="2">&nbsp;</td></tr>';

				//Deuda
				$query = "
				SELECT documento.glosa_documento, ( documento.saldo_honorarios + documento.saldo_gastos ) * cm1.tipo_cambio / cm2.tipo_cambio AS saldo_cobro
				FROM documento
				LEFT JOIN cobro_moneda as cm1 ON cm1.id_cobro = documento.id_cobro AND cm1.id_moneda = documento.id_moneda
				LEFT JOIN cobro_moneda as cm2 ON cm2.id_cobro = '" . $this->fields['id_cobro'] . "' AND cm2.id_moneda = '" . $this->fields['opc_moneda_total'] . "'
				WHERE documento.codigo_cliente = '" . $this->fields['codigo_cliente'] . "' AND documento.es_adelanto <> 1 AND documento.tipo_doc = 'N' AND documento.saldo_honorarios + documento.saldo_gastos > 0 AND documento.id_cobro <> " . $this->fields['id_cobro'];
				$adelantos = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				while ($adelanto = mysql_fetch_assoc($adelantos)) {
					$fila_adelanto_ = str_replace('%descripcion%', $adelanto['glosa_documento'], $html);
					$fila_adelanto_ = str_replace('%saldo_pago%', $moneda['simbolo'] . $adelanto['saldo_cobro'], $fila_adelanto_);
					$saldo += (int) $adelanto['saldo_cobro'];
					$fila_adelantos .= $fila_adelanto_;
				}

				$fila_adelantos .= '<tr><td colspan="2">&nbsp;</td></tr>';
				$fila_adelantos .= '<tr class="tr_total"><td>' . __('Total por pagar') . '</td><td align="right">' . $moneda['simbolo'] . $saldo . '</td></tr>';

				$html = $fila_adelantos;
				break;

			case 'PROFESIONAL_FILAS': //GenerarDocumento2
				$row_tmpl = $html;
				$html = '';
				if (is_array($x_detalle_profesional[$asunto->fields['codigo_asunto']])) {
					$retainer = false;
					$descontado = false;
					$flatfee = false;

					if (is_array($x_resumen_profesional)) {
						foreach ($x_resumen_profesional as $data) {
							if ($data['duracion_retainer'] > 0 && ( $this->fields['forma_cobro'] != 'FLAT FEE' || Conf::GetConf($this->sesion, 'ResumenProfesionalVial'))) {
								$retainer = true;
							}
							if (( $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL' ) && Conf::GetConf($this->sesion, 'ResumenProfesionalVial')) {
								$retainer = true;
							}
							if ($data['duracion_incobrables'] > 0)
								$descontado = true;
							if ($data['flatfee'] > 0 || $this->fields['forma_cobro'] == 'FLAT FEE')
								$flatfee = true;
						}
					}

					$totales['tiempo_retainer'] = 0;
					$totales['tiempo_trabajado'] = 0;
					$totales['tiempo_trabajado_real'] = 0;
					$totales['tiempo'] = 0;
					$totales['tiempo_flatfee'] = 0;
					$totales['tiempo_descontado'] = 0;
					$totales['tiempo_descontado_real'] = 0;
					$totales['valor_total'] = 0;

					foreach ($x_detalle_profesional[$asunto->fields['codigo_asunto']] as $prof => $data) {
						// Para mostrar un resumen de horas de cada profesional al principio del documento.
						$row = $row_tmpl;
						$totales['valor'] += $data['valor_tarificada'];
						$totales['tiempo_retainer'] += 60 * $data['duracion_retainer'];
						$totales['tiempo_trabajado'] += 60 * $data['duracion_cobrada'];
						$totales['tiempo_trabajado_real'] += 60 * $data['duracion_trabajada'];
						$totales['tiempo'] += 60 * $data['duracion_tarificada'];
						$totales['tiempo_flatfee'] += 60 * $data['flatfee'];
						$totales['tiempo_descontado'] += 60 * $data['duracion_incobrables'];
						$totales['tiempo_descontado_real'] += 60 * $data['duracion_descontada'];
						if ($this->fields['forma_cobro'] == 'ESCALONADA') {
							$totales['valor_total'] += $data['monto_cobrado_escalonada'];
						} else {
							$totales['valor_total'] += $data['valor_tarificada'];
						}

						if ($this->fields['opc_ver_profesional_iniciales'] == 1)
							$row = str_replace('%nombre_siglas%', $data['username'], $row);
						else
							$row = str_replace('%nombre_siglas%', $data['nombre_usuario'], $row);
						$row = str_replace('%nombre%', $data['nombre_usuario'], $row);
						$row = str_replace('%username%', $data['username'], $row);

						if (!$asunto->fields['cobrable']) {
							$row = str_replace('%hrs_retainer%', '', $row);
							$row = str_replace('%hrs_descontadas%', '', $row);
							$row = str_replace('%hh%', '', $row);
							$row = str_replace('%valor_hh%', '', $row);
							$row = str_replace('%valor_hh_cyc%', '', $row);
						}

						if ($this->fields['opc_ver_horas_trabajadas']) {
							$row = str_replace('%hh_trabajada%', $data['glosa_duracion_trabajada'], $row);
							$row = str_replace('%hrs_trabajadas%', $data['glosa_duracion_trabajada'], $row);
							if ($descontado) {
								$row = str_replace('%td_descontada%', '<td align=\'center\'>%hh_descontada%</td>', $row);
								$row = str_replace('%hh_descontada%', $data['glosa_duracion_descontada'], $row);
							} else {
								$row = str_replace('%td_descontada%', '', $row);
								$row = str_replace('%hh_descontada%', '', $row);
							}
						} else {
							$row = str_replace('%td_descontada%', '', $row);
							$row = str_replace('%hh_trabajada%', '', $row);
							$row = str_replace('%hh_descontada%', '', $row);
						}
						if ($retainer || $flatfee) {
							$row = str_replace('%td_cobrable%', '<td align=\'center\'>%hh_cobrable%</td>', $row);
							$row = str_replace('%hh_cobrable%', $data['glosa_duracion_cobrada'], $row);
							if ($retainer) {
								$row = str_replace('%td_retainer%', '<td align=\'center\'>%hh_retainer%</td>', $row);
								$row = str_replace('%hh_retainer%', $data['glosa_duracion_retainer'], $row);
							} else {
								$row = str_replace('%td_retainer%', '', $row);
								$row = str_replace('%hh_retainer%', '', $row);
							}
						} else {
							$row = str_replace('%td_cobrable%', '', $row);
							$row = str_replace('%td_retainer%', '', $row);
							$row = str_replace('%hh_cobrable%', '', $row);
							$row = str_replace('%hh_retainer%', '', $row);
						}
						$row = str_replace('%hh_demo%', $data['glosa_duracion_tarificada'], $row);

						if ($this->fields['opc_ver_profesional_tarifa'] == 1) {
							$row = str_replace('%td_tarifa%', '<td align="center">%tarifa_horas_demo%</td>', $row);
							$row = str_replace('%tarifa_horas_demo%', number_format($data['tarifa'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							$row = str_replace('%td_monto_tarifa%', '<td align="center">%monto_tarifa_horas%</td>', $row);
						} else {
							$row = str_replace('%td_tarifa%', '', $row);
							$row = str_replace('%tarifa_horas_demo%', '', $row);
							$row = str_replace('%td_monto_tarifa%', '', $row);
						}

						if ($this->fields['opc_ver_profesional_importe'] == 1) {
							$row = str_replace('%td_importe%', '<td align="right">%total_horas_demo%</td>', $row);
							$row = str_replace('%total_horas_demo%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($data['valor_tarificada'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						} else {
							$row = str_replace('%td_importe%', '', $row);
							$row = str_replace('%total_horas_demo%', '', $row);
						}

						if (!$asunto->fields['cobrable']) {
							$row = str_replace('%hrs_retainer%', '', $row);
							$row = str_replace('%hrs_descontadas%', '', $row);
							$row = str_replace('%hh%', '', $row);
							$row = str_replace('%valor_hh%', '', $row);
							$row = str_replace('%valor_hh_cyc%', '', $row);
						}

						//muestra las iniciales de los profesionales
						//Las iniciales fueron reemplazas por el username. Pivotal: 109198728
						$row = str_replace('%iniciales%', $data['nombre_usuario'], $row);

						if ($descontado || $retainer || $flatfee) {
							if ($this->fields['opc_ver_horas_trabajadas']) {
								$row = str_replace('%hrs_trabajadas_real%', $data['glosa_duracion_trabajada'], $row);
								$row = str_replace('%hrs_descontadas_real%', $data['glosa_duracion_descontada'], $row);
							} else {
								$row = str_replace('%hrs_trabajadas_real%', '', $row);
								$row = str_replace('%hrs_descontadas_real%', '', $row);
							}
							$row = str_replace('%hrs_trabajadas%', $data['glosa_duracion_cobrada'], $row);
						} else if ($this->fields['opc_ver_horas_trabajadas']) {
							$row = str_replace('%hrs_trabajadas_real%', $data['glosa_duracion_trabajada'], $row);
							$row = str_replace('%hrs_trabajadas%', $data['glosa_duracion_cobrada'], $row);
							$row = str_replace('%hrs_descontadas_real%', $data['glosa_duracion_descontada'], $row);
						} else {
							$row = str_replace('%hrs_trabajadas%', '', $row);
							$row = str_replace('%hrs_trabajadas_real%', '', $row);
						}
						if ($retainer) {
							if ($data['duracion_retainer'] > 0) {
								if ($this->fields['forma_cobro'] == 'PROPORCIONAL')
									$row = str_replace('%hrs_retainer%', floor($data['duracion_retainer']) . ':' . sprintf('%02d', floor(( floor($data['duracion_retainer']) - $data['duracion_retainer']) * 60)) . ':' . sprintf('%02d', round(( floor($data['duracion_retainer']) - $data['duracion_retainer']) * 3600)), $row);
								else // retainer simple, no imprime segundos
									$row = str_replace('%hrs_retainer%', $data['glosa_duracion_retainer'], $row);
								$row = str_replace('%horas_retainer%', number_format($data['duracion_retainer'], 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							}
							else {
								$row = str_replace('%hrs_retainer%', '-', $row);
								$row = str_replace('%horas_retainer%', '', $row);
							}
						} else {
							if ($flatfee) {
								if ($data['flatfee'] > 0)
									$row = str_replace('%hrs_retainer%', $data['flatfee'], $row);
								else
									$row = str_replace('%hrs_retainer%', '', $row);
							}
							$row = str_replace('%hrs_retainer%', '', $row);
							$row = str_replace('%horas_retainer%', '', $row);
						}
						if ($descontado) {
							$row = str_replace('%columna_horas_no_cobrables%', '<td align="center" width="65">%hrs_descontado%</td>', $row);
							if ($data['duracion_incobrables'] > 0)
								$row = str_replace('%hrs_descontadas%', $data['glosa_duracion_incobrables'], $row);
							else
								$row = str_replace('%hrs_descontadas%', '-', $row);
							if ($data['duracion_descontada'] > 0)
								$row = str_replace('%hrs_descontadas_real%', $data['glosa_duracion_descontada'], $row);
							else
								$row = str_replace('hrs_descontadas_real%', '-', $row);
						}
						else {
							$row = str_replace('%columna_horas_no_cobrables%', '', $row);
							$row = str_replace('%hrs_descontadas_real%', '', $row);
							$row = str_replace('%hrs_descontadas%', '', $row);
						}
						if ($flatfee) {
							$row = str_replace('%hh%', '0:00', $row);
						} else if ($this->fields['forma_cobro'] == 'ESCALONADA') {
							$row = str_replace('%hh%', $data['glosa_duracion_cobrada'], $row);
						} else {
							$row = str_replace('%hh%', $data['glosa_duracion_tarificada'], $row);
						}

						$row = str_replace('%valor_hh%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($data['tarifa'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

						if ($this->fields['forma_cobro'] == 'ESCALONADA') {
							$row = str_replace('%valor_hh_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format(($data['monto_cobrado_escalonada'] / $data['duracion_cobrada']) * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						} else {
							$row = str_replace('%valor_hh_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($data['tarifa'] * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						}

						$row = str_replace('%total%', $moneda->fields['simbolo'] . number_format($data['valor_tarificada'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

						if ($this->fields['forma_cobro'] == 'ESCALONADA') {
							$row = str_replace('%total_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($data['monto_cobrado_escalonada'] * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						} else {
							$row = str_replace('%total_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($data['valor_tarificada'] * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						}

						$row = str_replace('%hrs_trabajadas_previo%', '', $row);
						$row = str_replace('%horas_trabajadas_especial%', '', $row);
						$row = str_replace('%horas_cobrables%', '', $row);

						if ($this->fields['opc_ver_profesional_categoria'] == 1) {
							$row = str_replace('%categoria%', __($data['glosa_categoria']), $row);
						} else {
							$row = str_replace('%categoria%', '', $row);
						}

						if ($this->fields['forma_cobro'] == 'FLAT FEE') {
							$row = str_replace('%horas%', number_format($data['duracion_cobrada'], 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						} else {
							$row = str_replace('%horas%', number_format($data['duracion_tarificada'], 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						}

						$row = str_replace('%tarifa_horas%', $flatfee ? '' : number_format($data['tarifa'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%total_horas%', $flatfee ? '' : number_format($data['valor_tarificada'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

						if ($this->fields['opc_ver_horas_trabajadas'] && $data['duracion_trabajada'] && $data['duracion_trabajada'] != '0:00') {
							$html .= $row;
						} else if ($data['duracion_cobrada'] && $data['duracion_cobrada'] != '0:00') {
							$html .= $row;
						}
					}
				}

				break;

			case 'PROFESIONAL_TOTAL': //GenerarDocumento2
				$retainer = false;
				$descontado = false;
				$flatfee = false;

				if (is_array($x_resumen_profesional)) {
					foreach ($x_resumen_profesional as $data) {
						if ($data['duracion_retainer'] > 0 && ($this->fields['forma_cobro'] != 'FLAT FEE' || Conf::GetConf($this->sesion, 'ResumenProfesionalVial'))) {
							$retainer = true;
						}
						if (($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') && Conf::GetConf($this->sesion, 'ResumenProfesionalVial')) {
							$retainer = true;
						}
						if ($data['duracion_descontada'] > 0)
							$descontado = true;
						if ($data['flatfee'] > 0)
							$flatfee = true;
					}
				}

				if ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$html = str_replace('%DETALLE_PROFESIONAL_RETAINER%', $this->GenerarDocumento2($parser, 'DETALLE_PROFESIONAL_RETAINER', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				} else {
					$html = str_replace('%DETALLE_PROFESIONAL_RETAINER%', '', $html);
				}

				if (!$asunto->fields['cobrable']) {
					$html = str_replace('%hh_trabajada%', '', $html);
					$html = str_replace('%hh_descontada%', '', $html);
					$html = str_replace('%hh_cobrable%', '', $html);
					$html = str_replace('%hh_retainer%', '', $html);
					$html = str_replace('%hh_demo%', '', $html);
					$html = str_replace('%valor_hh%', '', $html);
					$html = str_replace('%valor_hh_cyc%', '', $html);
					$html = str_replace('%hh%', '', $html);
					$html = str_replace('%hrs_retainer%', '', $html);
					$html = str_replace('%hrs_descontadas%', '', $html);
					$html = str_replace('%hrs_descontadas_real%', '', $html);
				}

				$horas_cobrables = floor(($totales['tiempo']) / 60);
				$minutos_cobrables = sprintf("%02d", round($totales['tiempo']) % 60);
				$segundos_cobrables = round(60 * ($totales['tiempo'] - floor($totales['tiempo'])));
				$horas_trabajadas = floor(($totales['tiempo_trabajado']) / 60);
				$minutos_trabajadas = sprintf("%02d", round($totales['tiempo_trabajado']) % 60);
				$horas_trabajadas_real = floor(($totales['tiempo_trabajado_real']) / 60);
				$minutos_trabajadas_real = sprintf("%02d", round($totales['tiempo_trabajado_real']) % 60);
				#RETAINER
				$horas_retainer = floor(($totales['tiempo_retainer']) / 60);
				$minutos_retainer = sprintf("%02d", round($totales['tiempo_retainer'] % 60));
				$segundos_retainer = sprintf("%02d", round(60 * ($totales['tiempo_retainer'] - floor($totales['tiempo_retainer']))));
				$horas_flatfee = floor(($totales['tiempo_flatfee']) / 60);
				$minutos_flatfee = sprintf("%02d", round($totales['tiempo_flatfee']) % 60);
				$horas_descontado = floor(($totales['tiempo_descontado']) / 60);
				$minutos_descontado = sprintf("%02d", round($totales['tiempo_descontado']) % 60);
				$horas_descontado_real = floor(($totales['tiempo_descontado_real']) / 60);
				$minutos_descontado_real = sprintf("%02d", round($totales['tiempo_descontado_real']) % 60);

				$html = str_replace('%glosa%', __('Total'), $html);
				$html = str_replace('%glosa_honorarios%', __('Total Honorarios'), $html);

				if ($this->fields['opc_ver_horas_trabajadas']) {
					$html = str_replace('%hh_trabajada%', $horas_trabajadas_real . ':' . $minutos_trabajadas_real, $html);
					if ($descontado) {
						$html = str_replace('%td_descontada%', '<td align=\'center\'>%hh_descontada%</td>', $html);
						$html = str_replace('%hh_descontada%', Utiles::Decimal2GlosaHora($totales['tiempo_descontado'] / 60), $html);
						$html = str_replace('%hrs_descontadas%', Utiles::Decimal2GlosaHora($totales['tiempo_descontado'] / 60), $html);
					} else {
						$html = str_replace('%td_descontada%', '', $html);
						$html = str_replace('%hh_descontada%', '', $html);
						$html = str_replace('%hrs_descontadas%', '', $html);
					}
				} else {
					$html = str_replace('%td_descontada%', '', $html);
					$html = str_replace('%hh_trabajada%', '', $html);
					$html = str_replace('%hh_descontada%', '', $html);
					$html = str_replace('%hrs_descontadas%', '', $html);
				}

				if ($retainer || $flatfee) {
					$html = str_replace('%td_cobrable%', '<td align=\'center\'>%hh_cobrable%</td>', $html);
					$html = str_replace('%hh_cobrable%', $horas_trabajadas . ':' . $minutos_trabajadas, $html);
					if ($retainer) {
						$html = str_replace('%td_retainer%', '<td align=\'center\'>%hh_retainer%</td>', $html);
						$html = str_replace('%hh_retainer%', $horas_retainer . ':' . $minutos_retainer, $html);
					} else {
						$html = str_replace('%td_retainer%', '', $html);
						$html = str_replace('%hh_retainer%', '', $html);
					}
				} else {
					$html = str_replace('%td_cobrable%', '', $html);
					$html = str_replace('%td_retainer%', '', $html);
					$html = str_replace('%hh_cobrable%', '', $html);
					$html = str_replace('%hh_retainer%', '', $html);
				}

				$html = str_replace('%hh_demo%', $horas_cobrables . ':' . $minutos_cobrables, $html);
				$html = str_replace('%td_tarifa%', '<td>&nbsp;</td>', $html);

				if ($this->fields['opc_ver_profesional_importe'] == 1) {
					$html = str_replace('%td_importe%', '<td align="right">%total_horas_demo%</td>', $html);
					$html = str_replace('%total_horas_demo%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . $this->espacio . number_format($totales['valor_total'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				} else {
					$html = str_replace('%td_importe%', '', $html);
					$html = str_replace('%total_horas_demo%', '', $html);
				}

				if ($this->fields['opc_ver_profesional_importe'] == 1) {
					$html = str_replace('%td_importe%', '<td>&nbsp;</td>', $html);
				} else {
					$html = str_replace('%td_importe%', '', $html);
				}

				if ($descontado || $retainer || $flatfee) {
					if ($this->fields['opc_ver_horas_trabajadas']) {
						$html = str_replace('%hrs_trabajadas_real%', $horas_trabajadas_real . ':' . $minutos_trabajadas_real, $html);
						$html = str_replace('%hrs_descontadas_real%', Utiles::Decimal2GlosaHora($totales['tiempo_descontado_real'] / 60), $html);
					} else {
						$html = str_replace('%hrs_trabajadas_real%', '', $html);
						$html = str_replace('%hrs_descontadas_real%', '', $html);
					}
					$html = str_replace('%hrs_trabajadas%', $horas_trabajadas . ':' . $minutos_trabajadas, $html);
				} else if ($this->fields['opc_ver_horas_trabajadas']) {
					$html = str_replace('%hrs_trabajadas%', $horas_trabajadas . ':' . $minutos_trabajadas, $html);
					$html = str_replace('%hrs_trabajadas_real%', $horas_trabajadas_real . ':' . $minutos_trabajadas_real, $html);
					$html = str_replace('%hrs_descontadas_real%', Utiles::Decimal2GlosaHora($totales['tiempo_descontado_real'] / 60), $html);
				} else {
					$html = str_replace('%hrs_trabajadas%', '', $html);
					$html = str_replace('%hrs_trabajadas_real%', '', $html);
				}

				$html = str_replace('%hrs_trabajadas_previo%', '', $html);
				$html = str_replace('%horas_trabajadas_especial%', '', $html);
				$html = str_replace('%horas_cobrables%', '', $html);

				if ($retainer) {
					if ($this->fields['forma_cobro'] == 'PROPORCIONAL') {
						$minutos_retainer_redondeados = sprintf("%02d", $minutos_retainer + round($segundos_retainer / 60));
						$html = str_replace('%hrs_retainer%', $horas_retainer . ':' . $minutos_retainer_redondeados, $html);
					} else {// retainer simple, no imprime segundos
						$html = str_replace('%hrs_retainer%', $horas_retainer . ':' . $minutos_retainer, $html);
					}
					$minutos_retainer_decimal = $minutos_retainer / 60;
					$duracion_retainer_decimal = $horas_retainer + $minutos_retainer_decimal;
					$html = str_replace('%horas_retainer%', number_format($duracion_retainer_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				} else {
					$html = str_replace('%horas_retainer%', '', $html);
					if ($flatfee) {
						$html = str_replace('%hrs_retainer%', $horas_flatfee . ':' . $minutos_flatfee, $html);
					} else {
						$html = str_replace('%hrs_retainer%', '', $html);
					}
				}

				if ($descontado) {
					$html = str_replace('%columna_horas_no_cobrables%', '<td align="center" width="65">%hrs_descontadas%</td>', $html);
					$html = str_replace('%hrs_descontadas_real%', Utiles::Decimal2GlosaHora($totales['tiempo_descontado_real'] / 60), $html);
					$html = str_replace('%hrs_descontadas%', $horas_descontado . ':' . $minutos_descontado, $html);
				} else {
					$html = str_replace('%columna_horas_no_cobrables%', '', $html);
					$html = str_replace('%hrs_descontadas_real%', '', $html);
					$html = str_replace('%hrs_descontadas%', '', $html);
				}

				if ($flatfee) {
					$html = str_replace('%hh%', '0:00', $html);
				} else if ($this->fields['forma_cobro'] == 'PROPORCIONAL') {
					$minutos_cobrables_redondeados = sprintf("%02d", $minutos_cobrables + round($segundos_cobrables / 60));
					$html = str_replace('%hh%', "$horas_cobrables:$minutos_cobrables_redondeados", $html);
				} else if ($this->fields['forma_cobro'] == 'ESCALONADA') {
					$html = str_replace('%hh%', $horas_trabajadas . ':' . sprintf("%02d", $minutos_trabajadas), $html);
				} else { // retainer simple, no imprime segundos
					$html = str_replace('%hh%', $horas_cobrables . ':' . sprintf("%02d", $minutos_cobrables), $html);
				}

				$aproximacion_monto_cyc = number_format($this->fields['monto_subtotal'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
				$subtotal_en_moneda_cyc = $aproximacion_monto_cyc * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']);

				$html = str_replace('%total%', $moneda->fields['simbolo'] . $this->espacio . number_format($totales['valor'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%total_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($subtotal_en_moneda_cyc, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				#horas en decimal
				if ($this->fields['forma_cobro'] == 'FLAT FEE') {
					$minutos_decimal = $minutos_trabajadas / 60;
					$duracion_decimal = $horas_trabajadas + $minutos_decimal;
					$html = str_replace('%horas_mb%', number_format($duracion_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				} else {
					$minutos_decimal = $minutos_cobrables / 60;
					$duracion_decimal = $horas_cobrables + $minutos_decimal;
					$html = str_replace('%horas_mb%', number_format($totales['tiempo'] / 60 + $minutos_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				}

				$html = str_replace('%total_honorarios%', $flatfee ? $moneda->fields['simbolo'] . $this->espacio . number_format($this->fields['monto_subtotal'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) : $moneda->fields['simbolo'] . $this->espacio . number_format($totales['valor'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				$html = str_replace('%horas%', number_format($duracion_decimal, 1, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			case 'DETALLE_PROFESIONAL_RETAINER': //GenerarDocumento2
				$html = str_replace('%retainer%', __('Retainer'), $html);
				$html = str_replace('%valor_retainer%', $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['simbolo'] . $this->espacio . number_format($this->fields['monto_contrato'], $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			case 'GASTOS': //GenerarDocumento2
				if ($this->fields['opc_ver_gastos'] == 0) {
					return '';
				}
				/*
				  Solamente para antiguas templates
				 */
				if ($templateNotaCobroGastosSeparados) {
					break;
				}
				$html = str_replace('%separador%', '<hr size="2" class="separador">', $html);
				$html = str_replace('%glosa_gastos%', __('Gastos'), $html);
				$html = str_replace('%detalle_gastos_raz%', __('detalledegastos'), $html);
				if ($lang == 'es') {
					$html = str_replace('%glosa_gasto%', __('GASTOS'), $html);
				} else {
					$html = str_replace('%glosa_gasto%', __('EXPENSES'), $html);
				}
				$html = str_replace('%expenses%', __('%expenses%'), $html); //en vez de Disbursements es Expenses en inglés
				$html = str_replace('%detalle_gastos%', __('Detalle de gastos'), $html);

				$html = str_replace('%GASTOS_ENCABEZADO%', $this->GenerarDocumento2($parser, 'GASTOS_ENCABEZADO', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%GASTOS_FILAS%', $this->GenerarDocumento2($parser, 'GASTOS_FILAS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%GASTOS_TOTAL%', $this->GenerarDocumento2($parser, 'GASTOS_TOTAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto), $html);

				break;

			case 'GASTOS_ENCABEZADO': //GenerarDocumento2
				$html = str_replace('%td_monto_original%', $moneda_total->fields['id_moneda'] == $this->fields['id_moneda_base'] ? '' : '<td align="center" width="80">%monto_original%</td>', $html);

				$query = "SELECT count(*) FROM cta_corriente WHERE id_cobro = '" . $this->fields['id_cobro'] . "' AND id_moneda != '" . $this->fields['opc_moneda_total'] . "' ";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($cantidad_gastos_en_otra_moneda) = mysql_fetch_array($resp);

				$html = str_replace('%glosa_gastos%', __('Gastos'), $html);
				$html = str_replace('%descripcion_gastos%', __('Descripción de Gastos'), $html);
				$html = str_replace('%descripcion%', __('Descripción'), $html);
				$html = str_replace('%fecha%', __('Fecha'), $html);
				$html = str_replace('%ruc_proveedor%', __('RUT (RUC)'), $html);
				$html = str_replace('%num_doc%', __('N° Documento'), $html);
				$html = str_replace('%tipo_gasto%', __('Tipo'), $html);
				$html = str_replace('%monto%', __('Monto') . ' (' . $moneda_total->fields['simbolo'] . ')', $html);
				$html = str_replace('%monto_moneda_total%', __('Monto') . ' (' . $moneda_total->fields['simbolo'] . ')', $html);

				$html = str_replace('%glosa_asunto%', __('Asunto'), $html);

				if ($lang == 'es') {
					$html = str_replace('%asunto_id%', __('ID<br>Asunto'), $html);
				} else {
					$html = str_replace('%asunto_id%', __('Matter<br>ID'), $html);
				}

				if (Conf::GetConf($this->sesion, 'MostrarProveedorenGastos')) {
					$html = str_replace('%proveedor%', __('Proveedor'), $html);
				} else {
					$html = str_replace('%proveedor%', '', $html);
				}

				if ($this->fields['opc_ver_solicitante']) {
					$html = str_replace('%solicitante%', __('Ordenado<br>Por'), $html);
				} else {
					$html = str_replace('%solicitante%', '', $html);
				}

				if ($cantidad_gastos_en_otra_moneda > 0 || !Conf::GetConf($this->sesion, 'MontoGastoOriginalSiMonedaDistinta')) {
					$html = str_replace('%monto_original%', __('Monto'), $html);
				} else {
					$html = str_replace('%monto_original%', '', $html);
				}

				if ($this->fields['porcentaje_impuesto_gastos'] > 0) {
					$html = str_replace('%td_monto_impuesto_total%', '<td style="text-align:center;">%monto_impuesto_total%</a>', $html);
					$html = str_replace('%td_monto_moneda_total_con_impuesto%', '<td style="text-align:center;">%monto_moneda_total_con_impuesto%</a>', $html);

					$html = str_replace('%monto_impuesto_total%', __('Monto Impuesto') . ' (' . $moneda_total->fields['simbolo'] . ')', $html);
					$html = str_replace('%monto_iva_total%', __('IVA') . ' (' . $moneda_total->fields['simbolo'] . ')', $html);
					$html = str_replace('%monto_impuesto_total_cc%', __('Monto_Impuesto_cc') . ' (' . $moneda_total->fields['simbolo'] . ')', $html);
					$html = str_replace('%monto_moneda_total_con_impuesto%', __('Monto total') . ' (' . $moneda_total->fields['simbolo'] . ')', $html);
					$html = str_replace('%total_monto_moneda%', __('Total') . ' (' . $moneda_total->fields['simbolo'] . ')', $html);
				} else {
					$html = str_replace('%monto_impuesto_total%', '', $html);
					$html = str_replace('%monto_iva_total%', '', $html);
					$html = str_replace('%monto_impuesto_total_cc%', '', $html);
					$html = str_replace('%monto_moneda_total_con_impuesto%', '', $html);
					$html = str_replace('%total_monto_moneda%', '', $html);
					//si no hay impuesto para los gastos, no dibujo esas celdas
					$html = str_replace('%td_monto_impuesto_total%', '&nbsp;', $html);
					$html = str_replace('%td_monto_moneda_total_con_impuesto%', '&nbsp;', $html);
				}
				break;

			case 'GASTOS_FILAS':  //GenerarDocumento2
				$html = str_replace('%td_monto_original%', $moneda_total->fields['id_moneda'] == $this->fields['id_moneda_base'] ? '' : '<td align="center">%monto_original%</td>', $html);

				$row_tmpl = $html;
				$html = '';
				if (Conf::GetConf($this->sesion, 'SepararGastosPorAsunto')) {
					if (!empty($asunto->fields['codigo_asunto']) && $asunto->separar_asuntos) {
						$where_gastos_asunto = " AND codigo_asunto='" . $asunto->fields['codigo_asunto'] . "'";
					}
				} else {
					$where_gastos_asunto = "";
				}
				$query = "SELECT SQL_CALC_FOUND_ROWS
						cta_corriente.*
						, prm_cta_corriente_tipo.glosa AS tipo_gasto
					FROM cta_corriente
					LEFT JOIN prm_cta_corriente_tipo ON cta_corriente.id_cta_corriente_tipo = prm_cta_corriente_tipo.id_cta_corriente_tipo
					WHERE id_cobro = '{$this->fields['id_cobro']}'
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
					$row = str_replace('%proveedor%', '&nbsp;', $row);
					$row = str_replace('%solicitante%', '&nbsp;', $row);
					$row = str_replace('%descripcion%', __('No hay gastos en este cobro'), $row);
					$row = str_replace('%descripcion_b%', '(' . __('No hay gastos en este cobro') . ')', $row);
					$row = str_replace('%monto_original%', '&nbsp;', $row);
					$row = str_replace('%monto%', '&nbsp;', $row);
					$row = str_replace('%monto_moneda_total%', '&nbsp;', $row);
					$row = str_replace('%monto_moneda_total_sin_simbolo%', '&nbsp;', $row);
					$row = str_replace('%valor_codigo_asunto%', $detalle->fields['codigo_asunto'], $row);
					$row = str_replace('%monto_impuesto_total%', '&nbsp;', $row);
					$row = str_replace('%monto_moneda_total_con_impuesto%', '&nbsp;', $row);
					$row = str_replace('%td_monto_impuesto_total%', '&nbsp;', $row);
					$row = str_replace('%td_monto_moneda_total_con_impuesto%', '&nbsp;', $row);
					$row = str_replace('%glosa_asunto%', '&nbsp;', $row);
					$row = str_replace('%ruc_proveedor%', '&nbsp;', $row);
					$row = str_replace('%total_impuesto%', '&nbsp;', $row);
					$row = str_replace('%total_con_impuesto%', '&nbsp;', $row);
					$html .= $row;
				}
				$cont_gasto_egreso = 0;
				$cont_gasto_ingreso = 0;

				global $monto_gastos_neto_por_asunto;
				global $monto_gastos_impuesto_por_asunto;
				global $monto_gastos_bruto_por_asunto;

				$monto_gastos_neto_por_asunto = 0;
				$monto_gastos_impuesto_por_asunto = 0;
				$monto_gastos_bruto_por_asunto = 0;

				foreach ($x_cobro_gastos['gasto_detalle'] as $id_gasto => $detalle) {
					if (Conf::GetConf($this->sesion, 'SepararGastosPorAsunto') && $asunto->separar_asuntos && !empty($asunto->fields['codigo_asunto']) && $asunto->fields['codigo_asunto'] != $detalle['codigo_asunto']) {
						continue;
					}
					$row = $row_tmpl;
					$row = str_replace('%fecha%', Utiles::sql2fecha($detalle['fecha'], $idioma->fields['formato_fecha']), $row);
					$row = str_replace('%num_doc%', $detalle['numero_documento'], $row);
					$row = str_replace('%tipo_gasto%', $detalle['tipo_gasto'], $row);
					$row = str_replace('%glosa_asunto%', $detalle['glosa_asunto'], $row);

					$query = "SELECT rut FROM prm_proveedor WHERE id_proveedor = '" . $detalle['id_proveedor'] . "' ";
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					list($rut) = mysql_fetch_array($resp);

					$row = str_replace('%ruc_proveedor%', $rut, $row);

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

					if (substr($detalle['descripcion'], 0, 41) == 'Saldo aprovisionado restante tras Cobro #') {
						$row = str_replace('%descripcion%', __('Saldo aprovisionado restante tras Cobro #') . substr($detalle['descripcion'], 42), $row);
						$row = str_replace('%descripcion_b%', __('Saldo aprovisionado restante tras Cobro #') . substr($detalle['descripcion'], 42), $row);
					} else if (substr($gasto->fields['descripcion'], 0, 41) == 'Saldo aprovisionado restante tras Cobro #') {
						$row = str_replace('%descripcion%', __('Saldo aprovisionado restante tras Cobro #') . substr($gasto->fields['descripcion'], 42), $row);
						$row = str_replace('%descripcion_b%', __('Saldo aprovisionado restante tras Cobro #') . substr($gasto->fields['descripcion'], 42), $row);
					} else {
						$row = str_replace('%descripcion%', __($detalle['descripcion']), $row);
						$row = str_replace('%descripcion_b%', __($detalle['descripcion']), $row); #Ojo, este no debería existir
					}

					if ($detalle['id_moneda'] != $this->fields['opc_moneda_total'] && Conf::GetConf($this->sesion, 'MontoGastoOriginalSiMonedaDistinta')) {
						$row = str_replace('%monto_original%', $cobro_moneda->moneda[$detalle['id_moneda']]['simbolo'] . $this->espacio . number_format($detalle['monto_original'], $cobro_moneda->moneda[$detalle['id_moneda']]['cifras_decimales'], $cobro_moneda->moneda[$gasto->fields['id_moneda']]['separador_decimales'], $cobro_moneda->moneda[$gasto->fields['id_moneda']]['separador_miles']), $row);
					} else {
						$row = str_replace('%monto_original%', '', $row);
					}
					#$row = str_replace('%monto%', $moneda_total->fields['simbolo'].' '.number_format($saldo_moneda_total,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);

					$monto_gastos_neto_por_asunto += $detalle['monto_total'];
					$monto_gastos_impuesto_por_asunto += $detalle['monto_total_impuesto'];
					$monto_gastos_bruto_por_asunto += $detalle['monto_total_mas_impuesto'];

					$row = str_replace('%monto_moneda_total%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($detalle['monto_total'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					$row = str_replace('%monto%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($detalle['monto_total'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

					// El código de aquí a 10 lineas más abajo es inútil ya que los reemplazos se hacen el las lineas anteriores bajos las mismas condiciones
					$row = str_replace('%monto_moneda_total%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($detalle['monto_total'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					$row = str_replace('%monto%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($saldo_moneda_total, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					$row = str_replace('%monto_moneda_total%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($detalle['monto_total'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

					$row = str_replace('%monto_moneda_total_sin_simbolo%', number_format($detalle['monto_total'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					$row = str_replace('%valor_codigo_asunto%', $gasto->fields['codigo_asunto'], $row);

					if ($this->fields['porcentaje_impuesto_gastos'] > 0) {
						$row = str_replace('%td_monto_impuesto_total%', '<td style="text-align:center;">%monto_impuesto_total%</a>', $row);
						$row = str_replace('%td_monto_moneda_total_con_impuesto%', '<td style="text-align:center;">%monto_moneda_total_con_impuesto%</a>', $row);
						$row = str_replace('%monto_impuesto_total%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($detalle['monto_total_impuesto'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%monto_moneda_total_con_impuesto%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($detalle['monto_total_mas_impuesto'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%total_con_impuesto%', number_format($detalle['monto_total_mas_impuesto'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						$row = str_replace('%total_impuesto%', number_format($detalle['monto_total_impuesto'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					} else {
						$row = str_replace('%td_monto_impuesto_total%', ' ', $row);
						$row = str_replace('%td_monto_moneda_total_con_impuesto%', ' ', $row);
						$row = str_replace('%monto_impuesto_total%', '', $row);
						$row = str_replace('%monto_moneda_total_con_impuesto%', '', $row);
						$row = str_replace('%total_con_impuesto%', '', $row);
						$row = str_replace('%total_impuesto%', '', $row);
					}

					// FACTURA FABARA

					if ($this->fields['porcentaje_impuesto_gastos'] > 0) {
						$row = str_replace('%columna_impuesto%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($detalle['monto_total_impuesto'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					} else {
						$row = str_replace('%columna_impuesto%', '--', $row);
					}

					$row = str_replace('%columna_monto_gasto%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($detalle['monto_total'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
					$row = str_replace('%columna_subtotal_con_impuesto%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($detalle['monto_total_mas_impuesto'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);

					$html .= $row;
				}
				break;

			case 'GASTOS_TOTAL': //GenerarDocumento2
				global $monto_gastos_neto_por_asunto;
				global $monto_gastos_impuesto_por_asunto;
				global $monto_gastos_bruto_por_asunto;

				$html = str_replace('%td_monto_original%', $moneda_total->fields['id_moneda'] == $this->fields['id_moneda_base'] ? '' : '<td>&nbsp;</td>', $html);
				$html = str_replace('%total%', __('Total'), $html);
				$html = str_replace('%glosa_total%', __('Total Gastos'), $html);
				if ($lang == 'es') {
					$html = str_replace('%sub_total_gastos%', __('Sub total gastos'), $html);
				} else {
					$html = str_replace('%sub_total_gastos%', __('Sub total for expenses'), $html);
				}
				#$html = str_replace('%valor_total%', ''/*$cobro_moneda->fields['simbolo'].' '.number_format($totales['total_moneda_cobro'],$cobro_moneda->fields['cifras_decimales']*/,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);

				/* pegar lo que hice pal otro caso */
				$id_moneda_base = Moneda::GetMonedaBase($this->sesion);

				#$html = str_replace('%valor_total%', ''/*$cobro_moneda->fields['simbolo'].' '.number_format($totales['total_moneda_cobro'],$cobro_moneda->fields['cifras_decimales']*/,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
				if ($this->fields['id_moneda_base'] <= 0) {
					$tipo_cambio_cobro_moneda_base = 1;
				} else {
					$tipo_cambio_cobro_moneda_base = $cobro_moneda->moneda[$id_moneda_base]['tipo_cambio'];
				}

				# Comentado por ICC $gastos_moneda_total = $totales['total']*$moneda->fields['tipo_cambio']/$tipo_cambio_moneda_total;
				if (Conf::GetConf($this->sesion, 'SepararGastosPorAsunto') && !empty($asunto->fields['codigo_asunto']) && $asunto->separar_asuntos) {
					$gastos_moneda_total = $monto_gastos_neto_por_asunto;
				} else {
					$gastos_moneda_total = $x_cobro_gastos['gasto_total'];
				}

				$html = str_replace('%total_gastos_moneda_total%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($totales['total'], $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				#$gastos_moneda_total = ($totales['total']*($moneda->fields['tipo_cambio']/$moneda_base->fields['tipo_cambio']))/$this->fields['opc_moneda_total_tipo_cambio'];
				$html = str_replace('%valor_total_monedabase%', $moneda_total->fields['simbolo'] . $this->espacio . number_format($gastos_moneda_total, $moneda_total->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				$contr = new Contrato($this->sesion);
				$contr->Load($this->fields['id_contrato']);

				if ($moneda_total->fields['id_moneda'] != $id_moneda_base) {
					$html = str_replace('%glosa_total_moneda_base%', __('Total Moneda Base'), $html);
					$gastos_moneda_total_contrato = ( $gastos_moneda_total * ( $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio'])) / $tipo_cambio_cobro_moneda_base;
					$html = str_replace(array('%valor_total_moneda_carta%', '%valor_total_monedabase%'), $cobro_moneda->moneda[$id_moneda_base]['simbolo'] . $this->espacio . number_format($gastos_moneda_total_contrato, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				} else {
					$html = str_replace('%glosa_total_moneda_base%', '&nbsp;', $html);
					$html = str_replace('%valor_total_moneda_carta%', '&nbsp;', $html);
				}

				if (Conf::GetConf($this->sesion, 'SepararGastosPorAsunto') && !empty($asunto->fields['codigo_asunto']) && $asunto->separar_asuntos) {
					$gasto_impuesto_moneda_total = $monto_gastos_impuesto_por_asunto;
					$gasto_bruto_moneda_total = $monto_gastos_bruto_por_asunto;
				} else {
					$gasto_impuesto_moneda_total = $x_cobro_gastos['gasto_impuesto'];
					$gasto_bruto_moneda_total = $x_cobro_gastos['gasto_total_con_impuesto'];
				}

				$html = str_replace('%valor_total_monedabase_sin_impuesto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($gasto_bruto_moneda_total - $gasto_impuesto_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				if ($this->fields['porcentaje_impuesto_gastos'] > 0) {
					$html = str_replace('%td_valor_impuesto_monedabase%', '<td style="text-align:center;">%valor_impuesto_monedabase%</a>', $html);
					$html = str_replace('%valor_impuesto_monedabase_fabara%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($gasto_impuesto_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					$html = str_replace('%td_valor_total_monedabase_con_impuesto%', '<td style="text-align:center;">%valor_total_monedabase_con_impuesto%</a>', $html);

					$html = str_replace('%valor_impuesto_monedabase%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($gasto_impuesto_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					$html = str_replace('%valor_total_monedabase_con_impuesto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($gasto_bruto_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					$html = str_replace('%valor_total_monedabase_sin_impuesto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($gasto_bruto_moneda_total - $gasto_impuesto_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
				} else {
					$html = str_replace('%td_valor_impuesto_monedabase%', '', $html);
					$html = str_replace('%valor_impuesto_monedabase_fabara%', '--', $html);
					$html = str_replace('%td_valor_total_monedabase_con_impuesto%', '', $html);
					$html = str_replace('%valor_impuesto_monedabase%', '', $html);
					$html = str_replace('%valor_total_monedabase_con_impuesto%', '', $html);
				}

				$html = str_replace('%valor_total_monedabase_con_impuesto_fabara%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'] . $this->espacio . number_format($gasto_bruto_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

				break;

			case 'CTA_CORRIENTE': //GenerarDocumento2
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
