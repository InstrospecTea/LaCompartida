<?php
$query = "
SELECT
	SQL_CALC_FOUND_ROWS
	documento.id_documento,
	documento.codigo_cliente,
	documento.monto*-1 AS monto,
	documento.saldo_pago*-1 AS saldo_pago,
	CONCAT(prm_moneda.simbolo, ' ', documento.monto*-1) AS monto_con_simbolo,
	CONCAT(prm_moneda.simbolo, ' ', documento.saldo_pago*-1) AS saldo_pago_con_simbolo,
	documento.glosa_documento,
	documento.fecha
FROM
	documento
	LEFT JOIN prm_moneda ON prm_moneda.id_moneda = documento.id_moneda
WHERE
	id_cobro IS NULL AND
	es_adelanto = 1";

//Filtros
if (isset($filtros['id_documento']) and !empty($filtros['id_documento']))
{
	$query .= " AND documento.id_documento = " . $filtros['id_documento'];
}

if (isset($filtros['codigo_cliente']) and !empty($filtros['codigo_cliente']))
{
	$query .= " AND documento.codigo_cliente = " . $filtros['codigo_cliente'];
}

if (isset($filtros['fecha_inicio']) and !empty($filtros['fecha_inicio']))
{
	$query .= " AND documento.fecha >= " . $filtros['fecha_inicio'];
}
if (isset($filtros['fecha_fin']) and !empty($filtros['fecha_fin']))
{
	$query .= " AND documento.fecha <= " . $filtros['fecha_fin'];
}
if (isset($filtros['moneda']) and !empty($filtros['moneda']))
{
	$query .= " AND documento.id_moneda = " . $filtros['moneda'];
}

$buscador = new Buscador($sesion, $query, "Objeto", $desde, $x_pag = 12, $orden);
$buscador->nombre = "buscador_adelantos";
$buscador->titulo = "Adelantos";

//Encabezados
$buscador->AgregarEncabezado("id_documento", __('N°'));
$buscador->AgregarEncabezado("codigo_cliente", __('Cliente'));
$buscador->AgregarEncabezado("fecha", __('Fecha'));
$buscador->AgregarEncabezado("monto_con_simbolo", __('Monto'));
$buscador->AgregarEncabezado("saldo_pago_con_simbolo", __('Saldo'));
$buscador->AgregarEncabezado("glosa_documento", __('Descripción'));
$buscador->AgregarFuncion(__('Opción'), "OpcionesListaAdelanto", "align=\"right\"");

$buscador->Imprimir();

function OpcionesListaAdelanto(&$fila)
{
	$opc = "";
	if ($fila->fields['saldo_pago'] > 0)
	{
		$opc = "<a href='javascript:void(0)' onclick=\"nuevaVentana('Agregar_Adelanto', 730, 580,'ingresar_documento_pago.php?id_documento=" . $fila->fields['id_documento'] .  "&adelanto=1&popup=1', 'top=100, left=155');\" ><img src='" . Conf::ImgDir() . "/editar_on.gif' border=0 title=Editar></a>";
	}
	return $opc;
}
?>