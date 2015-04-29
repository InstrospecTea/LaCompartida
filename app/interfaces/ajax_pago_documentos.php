<?php
require_once dirname(__FILE__) . '/../conf.php';
header('Content-Type: text/html; charset=ISO-8859-1');

$sesion = new Sesion('');
$pagina = new Pagina ($sesion);

// Saldos actualizados
if ($c_hon) {
	$c_hon = explode(',', $c_hon);
}

if ($c_gas) {
	$c_gas = explode(',', $c_gas);
}

 // En caso de codigo secundario define codigo normal
if ($codigo_cliente_secundario != '' && $codigo_cliente == '') {
	$cliente = new Cliente($sesion);
	$codigo_cliente = $cliente->CodigoSecundarioACodigo($codigo_cliente_secundario);
}

// Normalmente no se incluyen Documentos pagados, pero si se est� editando un documento, se deben mostrar los documentos pagados que este documento pague.
if ($id_documento) {
	$join_neteo .= "LEFT JOIN neteo_documento AS neteo ON (
		neteo.id_documento_cobro = documento.id_documento
		AND neteo.id_documento_pago = '{$id_documento}'
	)";
	$or_neteo = 'OR neteo.id_neteo_documento IS NOT NULL ';
} else {
	$join_neteo = '';
	$or_neteo = '';
}

if ($id_cobro) {
	$orden2 = $id_documento ? 'IF(neteo.id_neteo_documento IS NOT NULL, 1, documento.id_documento)' : 'documento.id_documento';
	$orden_docs = " IF(documento.id_cobro = '{$id_cobro}' , 0, {$orden2}) AS orden, ";
	$orden = 'orden ASC';
} else if ($adelanto) {
	$orden_docs = ' IF(neteo.id_neteo_documento IS NOT NULL, 1, documento.id_documento) AS orden, ';
	$orden = 'orden ASC';
} else {
	$orden_docs = '';
	$orden = 'documento.id_documento';
}

$query = "SELECT
	SQL_CALC_FOUND_ROWS *,
	documento.id_documento,
	documento.id_cobro,
	documento.honorarios,
	documento.saldo_honorarios,
	documento.gastos,
	documento.saldo_gastos,
	documento.honorarios_pagados,
	documento.gastos_pagados,
	IF(documento.honorarios_pagados = 'NO' OR documento.gastos_pagados = 'NO', 0, 1) as pagado,
	{$orden_docs}
	cobro.fecha_ini,
	cobro.fecha_fin,
	documento.fecha,
	documento_moneda_cobro.tipo_cambio AS t_c_documento_moneda_cobro,
	documento_moneda_pago.tipo_cambio AS t_c_documento_moneda_pago,
	moneda_pago.tipo_cambio AS t_c_moneda_pago,
	moneda_cobro.tipo_cambio AS t_c_moneda_cobro,
	moneda_pago.cifras_decimales AS decimales_moneda_pago,
	moneda_cobro.cifras_decimales AS decimales_moneda_cobro,
	moneda_pago.simbolo,
	moneda_cobro.glosa_moneda AS glosa_moneda_cobro,
	moneda_pago.glosa_moneda AS glosa_moneda_pago

	FROM documento
		JOIN prm_moneda moneda_cobro ON (moneda_cobro.id_moneda = documento.id_moneda)
		JOIN prm_moneda moneda_pago ON (moneda_pago.id_moneda = {$id_moneda})
		LEFT JOIN cobro ON (documento.id_cobro = cobro.id_cobro)
		LEFT JOIN documento_moneda AS documento_moneda_cobro ON (
			documento_moneda_cobro.id_documento = documento.id_documento
			AND documento_moneda_cobro.id_moneda = documento.id_moneda
		)
		LEFT JOIN documento_moneda AS documento_moneda_pago ON (
			documento_moneda_pago.id_documento = documento.id_documento
			AND documento_moneda_pago.id_moneda = {$id_moneda})

		{$join_neteo}

	WHERE 1
		AND (documento.honorarios_pagados = 'NO' OR documento.gastos_pagados = 'NO' {$or_neteo})
		AND (documento.monto > 0 OR documento.id_cobro IS NOT NULL)
		AND documento.codigo_cliente = '{$codigo_cliente}' AND documento.tipo_doc = 'N' ";

if ($usar_adelanto){
	$query .= " AND (documento.id_cobro = '{$id_cobro}' OR neteo.id_neteo_documento IS NOT NULL)";
}

if ($id_contrato) {
	$query .= " AND (cobro.id_contrato = '{$id_contrato}' OR neteo.id_neteo_documento IS NOT NULL)";
}

$x_pag = 0;
$b = new Buscador($sesion, $query, 'Objeto', $desde, $x_pag, $orden);
$b->nombre = 'busc_cobros';
$b->titulo = isset($adelanto) || isset($usar_adelanto) ? 'Utilizaci�n del adelanto' : "Indique el Pago de Documentos de " . __('Cobros') . " pendientes";
$b->AgregarEncabezado('id_documento', __('N�'), 'align="left"');
$b->AgregarFuncion(__('Descipci�n'), 'DescripcionHonorariosGastos','align="left"');
$b->AgregarFuncion(__('Fecha'), 'Fecha', 'align="right" nowrap');
$b->AgregarFuncion(__('Saldo Honorarios'), 'Saldo_Honorarios', 'align="right" nowrap');
$b->AgregarFuncion(__('Pago'), 'Pago_Honorarios', 'align="right" nowrap');
$b->AgregarFuncion(__('Saldo Gastos'), 'Saldo_Gastos', 'align="right" nowrap');
$b->AgregarFuncion(__('Pago'), 'Pago_Gastos', 'align="right" nowrap');
$b->AgregarFuncion(__('TC'), 'TipoCambio', 'align="right" nowrap title="' . __('Tipo Cambio') . '"');
$b->color_mouse_over = '#bcff5c';

$pago_default = 0;

function FormatoFecha($fecha) {
	$fecha = explode('-', $fecha);
	return $fecha[0] . '/' . $fecha[1] . '/' . $fecha[2];
}

function Fecha(&$fila) {
	$html = '';

	if ($fila->fields['id_cobro']) {
		if ($fila->fields['fecha_ini'] != '0000-00-00') {
			$html .= FormatoFecha($fila->fields['fecha_ini']);
			$html .= ' al<br>';
		}
		if ($fila->fields['fecha_fin'] != '0000-00-00') {
			$html .= FormatoFecha($fila->fields['fecha_fin']);
		} else {
			$html .= ' - ';
		}
	} else {
		$html .= FormatoFecha($fila->fields['fecha']);
	}

	return $html;
}

function obtenerTiposCambios(&$fila) {
	if ($fila->fields['t_c_documento_moneda_cobro']) {
		// Si el Documento ven�a de un Cobro, se usa el cambio de CobroMoneda
		$cambio_cobro = $fila->fields['t_c_documento_moneda_cobro'];
		$cambio_pago = $fila->fields['t_c_documento_moneda_pago'];
	} else {
		// Si el Documento es un Cobro ingresado s�lo como documento, se usa el tipo de cambio actual
		$cambio_cobro = $fila->fields['t_c_moneda_cobro'];
		$cambio_pago = $fila->fields['t_c_moneda_pago'];
	}

	return array(
		'cobro' => $cambio_cobro,
		'pago' => $cambio_pago
	);
}

function conversorMoneda($monto = 0, $origen = 1, $destino = 1, $decimales = 0) {
	$monto_total = $monto * ($origen / $destino);
	return round($monto_total, $decimales);
}

// Formato US
function formatoNumero($valor = 0, $decimales = 0, $separador_miles = ',', $separador_decimales = '.') {
	return number_format($valor, $decimales, $separador_decimales, $separador_miles);
}

function Valor_Monto_Honorarios($fila) {
	$cambio = obtenerTiposCambios($fila);
	$decimales_pago = $fila->fields['decimales_moneda_pago'];

	$monto_total = conversorMoneda($fila->fields['honorarios'], $cambio['cobro'], $cambio['pago'], $decimales_pago);
	$monto_total = formatoNumero($monto_total, $decimales_pago);

	return $monto_total;
}

function Valor_Monto_Gastos($fila) {
	$cambio = obtenerTiposCambios($fila);
	$decimales_pago = $fila->fields['decimales_moneda_pago'];

	$monto_total = conversorMoneda($fila->fields['gastos'], $cambio['cobro'], $cambio['pago'], $decimales_pago);
	$monto_total = formatoNumero($monto_total, $decimales_pago);

	return $monto_total;
}

function DescripcionHonorariosGastos(&$fila) {
	$html = '';
	$id_documento = $fila->fields['id_documento'];
	$id_cobro = $fila->fields['id_cobro'];

	$html .= "<a href='cobros5.php?id_cobro={$id_cobro}&popup=1'>{$fila->fields['glosa_documento']}</a>";

	// Honorarios
	$monto_total = Valor_Monto_Honorarios($fila);
	$html .= "<br>Honorarios: {$fila->fields['simbolo']} {$monto_total}.";

	// Gastos
	$monto_total = Valor_Monto_Gastos($fila);
	$html .= "Gastos: {$fila->fields['simbolo']} {$monto_total}";

	$cambio = obtenerTiposCambios($fila);

	$decimales_pago = $fila->fields['decimales_moneda_pago'];
	$decimales_cobro = $fila->fields['decimales_moneda_cobro'];

	// Documento pendiente
	$html .= "<input type=\"hidden\" name=\"documento_pendiente_{$id_documento}\" value=\"{$id_documento}\" \>";
	// Tipo de Cambio Cobro
	$html .= "<input type=\"hidden\" name=\"cambio_cobro_{$id_documento}\" value=\"{$cambio['cobro']}\" \>";
	// Tipo de Cambio Pago
	$html .= "<input type=\"hidden\" name=\"cambio_pago_{$id_documento}\" value=\"{$cambio['pago']}\" \>";
	// Decimales Pago
	$html .= "<input type=\"hidden\" name=\"decimales_pago_{$id_documento}\" value=\"{$decimales_pago}\" \>";
	// Decimales Cobro
	$html .= "<input type=\"hidden\" name=\"decimales_cobro_{$id_documento}\" value=\"{$decimales_cobro}\" \>";
	// Decimales Cobro
	$html .= "<input type=\"hidden\" name=\"id_cobro_{$id_documento}\" value=\"{$id_cobro}\" \>";

	return $html;
}

function Valor_Saldo_Honorarios($fila, $separador_miles = ',') {
	$cambio = obtenerTiposCambios($fila);
	$decimales_pago = $fila->fields['decimales_moneda_pago'];

	$saldo_total = conversorMoneda($fila->fields['saldo_honorarios'], $cambio['cobro'], $cambio['pago'], $decimales_pago);
	$saldo_total = formatoNumero($saldo_total, $decimales_pago, $separador_miles);

	return $saldo_total;
}

function Saldo_Honorarios(&$fila) {
	global $c_hon;

	$saldo_total = Valor_Saldo_Honorarios($fila);
	$html_cobro .= $fila->fields['simbolo'] . ' ' . $saldo_total;

	if ($c_hon && in_array($fila->fields['id_documento_cobro'], $c_hon)) {
		$html_cobro .= '<br><span style="background-color: #D6FF9F; font-size:9px;">&nbsp;Saldo Actualizado&nbsp;</span> ';
	}

	return $html_cobro;
}

function Pago_Honorarios(&$fila) {
	global $sesion;
	global $id_documento;
	global $id_cobro;
	global $pago_default;
	global $usar_adelanto;

	$cambio = obtenerTiposCambios($fila);
	$decimales = $fila->fields['decimales_moneda_pago'];
	$neteo_documento = new NeteoDocumento($sesion);
	$saldo = Valor_Saldo_Honorarios($fila, '');

	if ($neteo_documento->Ids($id_documento, $fila->fields['id_documento'])) {
		$valor_neteo = $neteo_documento->fields['valor_pago_honorarios'];
		$valor = conversorMoneda($valor_neteo, $cambio['cobro'], $cambio['pago'], $decimales);
		$valor = formatoNumero($valor, $decimales, '');
	} else if($id_cobro === $fila->fields['id_cobro'] && !$id_documento) {
		$valor = $saldo;
	} else {
		$valor = 0;
	}

	$pago_default += $valor;

	$html = '';
	$editable = !$fila->fields['pagado'] && (!isset($usar_adelanto) || $id_cobro == $fila->fields['id_cobro']);

	if (!$editable) {
		$_valor = formatoNumero($valor, $fila->fields['decimales_moneda_pago']);
		$html .= "{$fila->fields['simbolo']} {$_valor}";
	}

	if ($fila->fields['honorarios'] != 0) {
		$type = !$editable ? 'hidden' : 'text';
		$html .= "<input type=\"{$type}\" name=\"pago_honorarios_{$fila->fields['id_documento']}\" id=\"pago_honorarios_{$fila->fields['id_documento']}\" value=\"{$valor}\" size=\"9\" onchange=\"Actualizar_Monto_Pagos('honorarios', {$fila->fields['id_documento']});SetMontoPagos();\" />";
		$html .= "<input type=\"hidden\" name=\"pago_honorarios_anterior_{$fila->fields['id_documento']}\" id=\"pago_honorarios_anterior_{$fila->fields['id_documento']}\" value=\"{$valor}\" size=\"9\" />";
		$html .= "<input type=\"hidden\" name=\"cobro_honorarios_{$fila->fields['id_documento']}\" id=\"cobro_honorarios_{$fila->fields['id_documento']}\" value=\"" . formatoNumero($saldo + $valor_neteo, 2, '', '.') . "\" size=\"9\" /> ";
	} else {
		$html = '0';
	}

	return $html;
}

function Valor_Saldo_Gastos($fila, $separador_miles = ',') {
	$cambio = obtenerTiposCambios($fila);
	$decimales_pago = $fila->fields['decimales_moneda_pago'];

	$saldo_total = conversorMoneda($fila->fields['saldo_gastos'], $cambio['cobro'], $cambio['pago'], $decimales_pago);
	$saldo_total = formatoNumero($saldo_total, $decimales_pago, $separador_miles);

	return $saldo_total;
}

function Saldo_Gastos(&$fila) {
	global $c_gas;

	$html_cobro = '';
	$saldo_total = Valor_Saldo_Gastos($fila);
	$html_cobro .= $fila->fields['simbolo'] . ' '.  $saldo_total;

	if ($c_gas && in_array($fila->fields['id_documento_cobro'], $c_gas)) {
		$html_cobro .= '<br><span style="background-color:#D6FF9F;font-size:9px;">&nbsp;Saldo Actualizado&nbsp;</span>';
	}

	return $html_cobro;
}

function Pago_Gastos(&$fila) {
	global $sesion;
	global $id_documento;
	global $id_cobro;
	global $pago_default;
	global $usar_adelanto;

	$cambio = obtenerTiposCambios($fila);
	$decimales = $fila->fields['decimales_moneda_pago'];
	$neteo_documento = new NeteoDocumento($sesion);
	$saldo = Valor_Saldo_Gastos($fila, '');

	if ($neteo_documento->Ids($id_documento, $fila->fields['id_documento'])) {
		$valor_neteo = $neteo_documento->fields['valor_pago_gastos'];
		$valor = conversorMoneda($valor_neteo, $cambio['cobro'], $cambio['pago'], $decimales);
		$valor = formatoNumero($valor, $decimales, '');
	} else if ($id_cobro === $fila->fields['id_cobro'] && !$id_documento) {
		$valor = $saldo;
	} else {
		$valor = 0;
	}

	$pago_default += $valor;

	$html = '';
	$editable = !$fila->fields['pagado'] && (!isset($usar_adelanto) || $id_cobro == $fila->fields['id_cobro']);

	if (!$editable) {
		$_valor = formatoNumero($valor, $decimales);
		$html .= "{$fila->fields['simbolo']} {$_valor}";
	}

	if ($fila->fields['gastos'] != 0) {
		$type = !$editable ? 'hidden' : 'text';
		$html .= "<input type=\"{$type}\" name=\"pago_gastos_{$fila->fields['id_documento']}\" id=\"pago_gastos_{$fila->fields['id_documento']}\" value=\"{$valor}\" size=\"9\" onchange=\"Actualizar_Monto_Pagos('gastos', {$fila->fields['id_documento']});SetMontoPagos();\" /> ";
		$html .= "<input type=\"hidden\" name=\"pago_gastos_anterior_{$fila->fields['id_documento']}\" id=\"pago_gastos_anterior_{$fila->fields['id_documento']}\" value=\"{$valor}\" size=\"9\" /> ";
		$html .= "<input type=\"hidden\" name=\"cobro_gastos_{$fila->fields['id_documento']}\" id=\"cobro_gastos_{$fila->fields['id_documento']}\" value=\"" . formatoNumero($saldo + $valor_neteo, 2, '', '.') . "\" size=\"9\" /> ";
	} else {
		$html = '0';
	}

	return $html;
}

function TipoCambio(&$fila) {
	global $id_cobro;
	global $sesion;

	if ($fila->fields['t_c_documento_moneda_cobro']) {
		$cambio_cobro = $fila->fields['t_c_documento_moneda_cobro'];
		$cambio_pago = $fila->fields['t_c_documento_moneda_pago'];
	} else {
		// Si el Documento es un Cobro ingresado s�lo como documento, se usa el tipo de cambio actual
		$cambio_cobro = $fila->fields['t_c_moneda_cobro'];
		$cambio_pago = $fila->fields['t_c_moneda_pago'];
	}

	$moneda_cobro = $fila->fields['glosa_moneda_cobro'];
	$moneda_pago = $fila->fields['glosa_moneda_pago'];
	$cambio_cobro_actual = $fila->fields['t_c_moneda_cobro'];
	$cambio_pago_actual = $fila->fields['t_c_moneda_pago'];

	$html .= '<img style="cursor:pointer" src="' . Conf::ImgDir() . '/money_16.gif" title="Tipo de cambio ' . __('Cobro') . ': ' . $cambio_cobro . '. Tipo de cambio Pago: ' . $cambio_pago . ' " onclick="$(\'calculos_' . $fila->fields['id_documento'] . '\').toggle();" />';

	$html .= "</td></tr><tr id=\"calculos_{$fila->fields['id_documento']}\" style=\"display:none;\"><td colspan=\"8\"><fieldset><legend>Tipo de Cambio</legend><table width=\"100%\" style=\"border-collapse:collapse;\" cellpadding=\"3\"><tr>";

	// Lista de monedas del documento
	$query = "SELECT id_documento, prm_moneda.id_moneda, glosa_moneda, documento_moneda.tipo_cambio
		FROM documento_moneda
			JOIN prm_moneda ON documento_moneda.id_moneda = prm_moneda.id_moneda
		WHERE id_documento = '{$fila->fields['id_documento']}'";

	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	$num_monedas = 0;
	$ids_monedas = array();

	while (list($id_documento, $id_moneda, $glosa_moneda, $tipo_cambio) = mysql_fetch_array($resp)) {
		$html .= "<td><span><b>{$glosa_moneda}</b></span><input type=\"text\" size=\"9\" id=\"documento_{$id_documento}_moneda_{$id_moneda}\" name=\"documento_{$id_documento}_moneda_{$id_moneda}\" value=\"{$tipo_cambio}\" /></td>";
		$num_monedas++;
		$ids_monedas[] = $id_moneda;
	}

	$html .= "<tr><td colspan=\"{$num_monedas}\" align=\"center\"><a href=\"javascript:void(0);\" onclick=\"ActualizarDocumentoMoneda('{$fila->fields['id_documento']}')\">Actualizar Tipo de Cambio</a><input type=\"hidden\" id=\"ids_monedas_documento_{$fila->fields['id_documento']}\" value=\"" . implode(',', $ids_monedas) . "\" /></td></tr>";

	$html .= '</tr></table></fieldset>';

	if ($fila->fields['id_cobro'] == $id_cobro) {
		$html .= '</td></tr><tr><td colspan="8"><hr>'; //cierre td dado por buscador
	}

	return $html;
}

echo $b->Imprimir('', array(''), false);
echo '<input type="hidden" name="monto_pagos" id="monto_pagos" value="' . str_replace(',', '.', $pago_default) . '" size="9" />';
