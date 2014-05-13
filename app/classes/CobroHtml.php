<?php

require_once dirname(__FILE__) . '/../conf.php';

class CobroHtml {
	protected static $moneda;
	protected static $idioma;

	public static function setMoneda(Moneda $Moneda) {
		self::$moneda = $Moneda->fields;
	}

	public static function setIdioma(Objeto $Idioma) {
		self::$idioma = $Idioma->fields;
	}

	public static function number_format($value) {
		return self::$moneda['simbolo'] . '&nbsp;' . number_format($value, self::$moneda['cifras_decimales'], self::$idioma['separador_decimales'], self::$idioma['separador_miles']);
	}


	public static function cajafacturasHead($diferencia_cobro_factura) {
		$img_dir  = Conf::ImgDir();
		$dcf = '';
		if (!empty($diferencia_cobro_factura)) {
			$dcf = <<<HTML
				<span style="border: 1px solid #bfbfcf; color: #ffffff; background-color: #ff0000; float: right; padding: 2px">{$diferencia_cobro_factura}</span>
HTML;
		}

		$titulos = array(
			'tipo_documento' => __('Tipo') . ' ' . __('Documento'),
			'numero' => __('Número'),
			'fecha' => __('Fecha'),
			'honorarios' => __('Honorarios'),
			'gasto_c_iva' => __('Gasto') . ' ' . __('c/IVA'),
			'gasto_s_iva'=> __('Gasto') . ' ' . __('s/IVA'),
			'agregar_pago' => __('Agregar Pago'),
			'impuesto' =>  __('Impuesto'),
			'total' =>  __('Total'),
			'estado' =>  __('Estado'),
			'saldo' =>  __('Saldo'),
			'por_pagar' =>  __('por pagar'),
			'acciones' =>  __('Acciones'),
			'documento_tributario' => __('Documentos Tributarios')
		);
		$html = <<<HTML
			<tr style="height: 26px;">
				<td colspan="12" align="left" bgcolor="#dfdfdf" style="font-size: 11px; font-weight: bold; vertical-align: middle;" colspan=2>
					<img src="{$img_dir}/imprimir_16.gif" border="0" alt="Imprimir"/>
					{$titulos['documento_tributario']}
					$dcf
				</td>
			</tr>
			<tr>
				<th>{$titulos['tipo_documento']}</th>
				<th>{$titulos['numero']}</th>
				<th style="white-space:nowrap; width:78px;">{$titulos['fecha']}</th>
				<th>{$titulos['honorarios']}</th>
				<th>{$titulos['gasto_c_iva']}</th>
				<th>{$titulos['gasto_s_iva']}</th>
				<th>{$titulos['impuesto']}</th>
				<th>{$titulos['total']}</th>
				<th>{$titulos['estado']}</th>
				<th>{$titulos['saldo']}<br>{$titulos['por_pagar']}</th>
				<th>{$titulos['agregar_pago']}</th>
				<th>{$titulos['acciones']}</th>
			</tr>
HTML;
		return $html;
	}

	public static function cajafacturasCobro($cobro) {
		$cobro['cobro'] =  __('Cobro');
		$cobro['total'] = $cobro['saldo_honorarios'] + $cobro['saldo_gastos_con_impuestos'] + $cobro['saldo_gastos_sin_impuestos'] + $cobro['iva'];
		$cobro['f_honorarios'] = self::number_format($cobro['saldo_honorarios']);
		$cobro['f_gastos_con_impuestos'] = self::number_format($cobro['saldo_gastos_con_impuestos']);
		$cobro['f_gastos_sin_impuestos'] = self::number_format($cobro['saldo_gastos_sin_impuestos']);
		$cobro['f_iva'] = empty($cobro['iva']) ? '' : self::number_format($cobro['iva']);
		$cobro['f_total'] = self::number_format($cobro['total']);

		$html = <<<HTML
			<tr style="background:#EFE;">
				<td>{$cobro['cobro']}</td>
				<td>{$cobro['id_cobro']}</td>
				<td style="width:78px;">{$cobro['fecha']}</td>
				<td>{$cobro['f_honorarios']}<input type="hidden" name="honorarios_total" id="honorarios_total" value="{$cobro['saldo_honorarios']}" /></td>
				<td>{$cobro['f_gastos_con_impuestos']}<input type="hidden" name="gastos_con_iva_total" id="gastos_con_iva_total" value="{$cobro['saldo_gastos_con_impuestos']}" /></td>
				<td>{$cobro['f_gastos_sin_impuestos']}<input type="hidden" name="gastos_con_iva_total" id="gastos_con_iva_total" value="{$cobro['saldo_gastos_sin_impuestos']}" /></td>
				<td>{$cobro['f_iva']}</td>
				<td><strong>{$cobro['f_total']}</strong></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
			</tr>
HTML;
			return $html;
	}

	/**
	 *
	 * @param Factura $Factura
	 * @param type $fila
	 * @param type $datos_factura
	 * @return type
	 */
	public static function cajafacturasFilaFactura(Factura $Factura, Documento $Documento, $fila, $datos_factura) {
		$color_fila = $fila % 2 ? '#f2f2ff' : '#ffffff';
		$datos_factura['fecha'] = date('d-m-Y', strtotime($Factura->fields['fecha']));
		$datos_factura['f_subtotal_sin_descuento'] = self::number_format($datos_factura['subtotal_sin_descuento']);
		$datos_factura['f_subtotal_gastos'] = self::number_format($datos_factura['subtotal_gastos']);
		$datos_factura['f_subtotal_gastos_sin_impuesto'] = self::number_format($datos_factura['subtotal_gastos_sin_impuesto']);
		$datos_factura['f_iva'] = self::number_format($datos_factura['iva']);
		$datos_factura['total'] = $datos_factura['subtotal_sin_descuento'] + $datos_factura['subtotal_gastos'] + $datos_factura['subtotal_gastos_sin_impuesto'] + $datos_factura['iva'];
		$datos_factura['f_total'] = self::number_format($datos_factura['total']);
		$datos_factura['f_saldo'] = self::number_format(-$datos_factura['saldo']);
		$datos_factura['saldo'] = str_replace(',', '.', -$datos_factura['saldo']);
		$datos_factura['numero'] = $Factura->ObtenerNumero(null, null, null, true);

		$html_tools = self::cajafacturasFilaFacturaTools($Factura, $datos_factura);

		$html = <<<HTML
			<tr bgcolor="{$color_fila}">
				<td>{$datos_factura['tipo']}</td>
				<td style="width:78px;white-space:nowrap;">{$datos_factura['numero']}</td>
				<td>{$datos_factura['fecha']}</td>
				<td>{$datos_factura['f_subtotal_sin_descuento']}</td>
				<td>{$datos_factura['f_subtotal_gastos']}</td>
				<td>{$datos_factura['f_subtotal_gastos_sin_impuesto']}</td>
				<td>{$datos_factura['f_iva']}</td>
				<td><strong>{$datos_factura['f_total']}</strong></td>
				<td>{$datos_factura['estado']}</td>
				<td align="right">
					{$datos_factura['f_saldo']}
					<input type="hidden" name="saldo_{$datos_factura['id_factura']}" id="saldo_{$datos_factura['id_factura']}" value="{$datos_factura['saldo']} />
					<input type="hidden" name="id_moneda_factura_{$datos_factura['id_factura']}" id="id_moneda_factura_{$datos_factura['id_factura']}" value="{$datos_factura['id_moneda']}" />
					<input type="hidden" name="tipo_cambio_factura_{$datos_factura['id_factura']}" id="tipo_cambio_factura_{$datos_factura['id_factura']}" value="{$datos_factura['tipo_cambio']}" />
					<input type="hidden" name="cifras_decimales_factura_{$datos_factura['id_factura']}" id="cifras_decimales_factura_{$datos_factura['id_factura']}" value="{$datos_factura['cifras_decimales']}" />
				</td>

				<td align="center">
					<input type="checkbox" name="pagar_factura_{$datos_factura['id_factura']}" id="pagar_factura_{$datos_factura['id_factura']}" value="{$datos_factura['saldo']}" class="tooltip" alt="Active esta casilla y luego pinche en 'Pagar' para añadir pagos" />
				</td>
				<td style="white-space:nowrap;cursor:pointer;">$html_tools</td>
			</tr>
HTML;

		$html .= self::cajafacturasFilaFacturaPagos($Factura, $Documento, $datos_factura);

		return $html;
	}

	/**
	 *
	 * @param Factura $Factura
	 * @param type $datos_factura
	 */
	public static function cajafacturasFilaFacturaTools(Factura $Factura, $datos_factura) {
		$img_dir  = Conf::ImgDir();

		$html = <<<HTML
			<a href='javascript:void(0)' onclick="nuovaFinestra('Editar_Factura', 800, 600, 'agregar_factura.php?id_factura={$datos_factura['id_factura']}&popup=1&id_cobro={$datos_factura['id_cobro']}', 'top=100, left=155, scrollbars=yes');" ><img src='{$img_dir}/editar_on.gif' border="0" title="Editar"/></a>
HTML;

		if (Conf::GetConf($Factura->sesion, 'ImprimirFacturaDoc')) {
			$html .= <<<HTML
			<a href="javascript:void(0)" onclick="ValidarFactura('', {$datos_factura['id_factura']}, 'imprimir');"><img src="{$img_dir}/doc.gif" border="0" title="Descargar Word"/></a>
HTML;
		}
		if ($Slim = Slim::getInstance('default', true)) {
			$data = array('Factura' => $Factura);
			$Slim->applyHook('hook_cobros7_botones_after', &$data);
		}
		if (!($data && $data['content'])) {
			if (Conf::GetConf($Factura->sesion, 'ImprimirFacturaPdf')) {
				$html .= <<<HTML
					<a href='javascript:void(0)' onclick="ValidarFactura('', {$datos_factura['id_factura']}, 'imprimir_pdf');" ><img src='{$img_dir}/pdf.gif' border="0" title="Descargar Pdf"/></a>
HTML;
			}
		} else {
			echo($data['content']);
		}
		$html .= <<<HTML
			<img title="Ver pagos para este documento" src="{$img_dir}/ver_persona_nuevo.gif" onclick="MostrarVerDocumentosPagos({$datos_factura['id_factura']});" border="0" alt="Examinar" />
HTML;
		return $html;
	}


	public static function cajafacturasFilaFacturaPagos(Factura $Factura, Documento $Documento, $datos_factura) {
		$html_lista_pagos = FacturaPago::HtmlListaPagos($Factura->sesion, $Factura, $Documento->fields['id_documento']);
		$titulo = __('Lista de pagos asociados a documento #') . $datos_factura['numero'];
		$cancelar = __('Cancelar');
		$html = <<<HTML
			<tr>
				<td align=right colspan="12">
					<div id="VerDocumentosPagos_{$datos_factura['id_factura']}" style="display:none; left: 100px; top: 250px; background-color: white; position:absolute; z-index: 4;">
						<fieldset style="background-color:white;">
							<legend>{$titulo}</legend>
							<div id="contenedor_tipo_load">&nbsp;</div>
							<div id="contenedor_tipo_cambio">
								{$html_lista_pagos}
								<table style='border-collapse:collapse;' cellpadding='3'>
									<tr>
										<td colspan="{$num_monedas}" align="center">
											<input type="button" onclick="CancelarVerDocumentosPagos({$datos_factura['id_factura']})" value="$cancelar" />
										</td>
									</tr>
								</table>
							</div>
						</fieldset>
					</div>
				</td>
			</tr>
HTML;
		return $html;
	}
}