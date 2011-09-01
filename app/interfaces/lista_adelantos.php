<?php
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
$sesion = new Sesion(array('COB'));
if ($popup)
{
	require_once Conf::ServerDir() . '/../fw/classes/Buscador.php';
	require_once Conf::ServerDir() . '/../fw/classes/Pagina.php';
	$pagina = new Pagina($sesion);
	$pagina->titulo = $titulo ? $titulo : "Adelantos";
	$pagina->PrintTop($popup);
}

$query = "
SELECT
	SQL_CALC_FOUND_ROWS
	documento.id_documento,
	documento.id_cobro,
	documento.codigo_cliente,
	IF(documento.monto = 0, 0, documento.monto*-1) AS monto,
	IF(documento.saldo_pago = 0, 0, documento.saldo_pago*-1) AS saldo_pago,
	CONCAT(prm_moneda.simbolo, ' ', IF(documento.monto = 0, 0, documento.monto*-1)) AS monto_con_simbolo,
	CONCAT(prm_moneda.simbolo, ' ', IF(documento.saldo_pago = 0, 0, documento.saldo_pago*-1)) AS saldo_pago_con_simbolo,
	documento.glosa_documento,
	documento.fecha
FROM
	documento
	LEFT JOIN prm_moneda ON prm_moneda.id_moneda = documento.id_moneda
WHERE
	es_adelanto = 1";

//Filtros
if (isset($codigo_cliente)) $filtros['codigo_cliente'] = $codigo_cliente;
if (isset($pago_honorarios)) $filtros['pago_honorarios'] = $pago_honorarios;
if (isset($pago_gastos)) $filtros['pago_gastos'] = $pago_gastos;

if (isset($filtros['id_documento']) and !empty($filtros['id_documento']))
{
	$query .= " AND documento.id_documento = " . $filtros['id_documento'];
}
if (isset($filtros['codigo_cliente']) and !empty($filtros['codigo_cliente']))
{
	$query .= " AND documento.codigo_cliente = '" . $filtros['codigo_cliente'] . "'";
}
if (isset($filtros['fecha_inicio']) and !empty($filtros['fecha_inicio']))
{
	$query .= " AND documento.fecha >= '" . date("Y-m-d", strtotime($filtros['fecha_inicio'])) . "'";
}
if (isset($filtros['fecha_fin']) and !empty($filtros['fecha_fin']))
{
	$query .= " AND documento.fecha <= '" . date("Y-m-d", strtotime($filtros['fecha_fin'])) . "'";
}
if (isset($filtros['moneda']) and !empty($filtros['moneda']))
{
	$query .= " AND documento.id_moneda = " . $filtros['moneda'];
}
if(isset($filtros['pago_honorarios']) && isset($filtros['pago_gastos'])){
	$query .= " AND (documento.pago_honorarios = 1 OR documento.pago_gastos = 1)";
}
else{
	if (isset($filtros['pago_honorarios']))
	{
		$query .= " AND documento.pago_honorarios = " . $filtros['pago_honorarios'];
	}
	if (isset($filtros['pago_gastos']))
	{
		$query .= " AND documento.pago_gastos = " . $filtros['pago_gastos'];
	}
}
if($elegir_para_pago || isset($filtros['tiene_saldo'])){
	$query .= " AND saldo_pago < 0";
}
$buscador = new Buscador($sesion, $query, "Objeto", $desde, $x_pag = 12, empty($orden) ? 'fecha_creacion DESC' : $orden);
$buscador->nombre = "buscador_adelantos";
$buscador->titulo = "Adelantos";

//Encabezados
$buscador->AgregarEncabezado("id_documento", __('N°'));
$buscador->AgregarEncabezado("codigo_cliente", __('Cliente'));
$buscador->AgregarEncabezado("fecha", __('Fecha'));
$buscador->AgregarEncabezado("monto_con_simbolo", __('Monto'), "align=\"right\"");
$buscador->AgregarEncabezado("saldo_pago_con_simbolo", __('Saldo'), "align=\"right\"");
$buscador->AgregarEncabezado("glosa_documento", __('Descripción'));

if ($elegir_para_pago)
{
	$buscador->AgregarFuncion(__('Elegir para pago'), "ElegirParaPago");
}
else
{
	$buscador->AgregarFuncion(__('Opción'), "OpcionesListaAdelanto");
}

$buscador->Imprimir();

function ElegirParaPago(&$fila)
{
	global $id_cobro;
	return '<button type="button" onclick="ElegirParaPago(\'' . Conf::RootDir() . '/app/interfaces/ingresar_documento_pago.php?id_cobro=' . $id_cobro . '&id_documento=' . $fila->fields['id_documento'] . '&popup=1&pago=true&codigo_cliente=' . $fila->fields['codigo_cliente'] . '\')">Utilizar</button>';
}

function OpcionesListaAdelanto(&$fila)
{
	$opc = "";
	if ($fila->fields['saldo_pago'] > 0)
	{
		$opc = "<a href='javascript:void(0)' onclick=\"nuevaVentana('Agregar_Adelanto', 730, 580,'ingresar_documento_pago.php?id_documento=" . $fila->fields['id_documento'] .  "&adelanto=1&popup=1', 'top=100, left=155');\" ><img src='" . Conf::ImgDir() . "/editar_on.gif' border='0' title='Editar' /></a>";
	}
	else
	{
		$opc = "<img src='" . Conf::ImgDir() . "/editar_off.gif' border='0' title='Editar' />";
	}
	return $opc;
}
?>
<script type="text/javascript" charset="utf-8">
	<?php if ($elegir_para_pago) { ?>
	function ElegirParaPago(url)
	{
		window.opener.location.href = url;
		window.close();
		return false;
	}
	<?php } ?>
</script>
<?php if ($popup) $pagina->PrintBottom($popup); ?>