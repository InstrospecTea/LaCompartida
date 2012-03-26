<?php
require_once dirname(__FILE__) . '/../../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
$sesion = new Sesion(array('COB'));
header("Content-Type: text/html; charset=ISO-8859-1");
require_once Conf::ServerDir() . '/../fw/classes/Buscador.php';

	

	$desde=$_POST['xdesde'];
        

      

$query = "SELECT
	SQL_CALC_FOUND_ROWS
	documento.id_documento,
	documento.id_cobro,
	cliente.glosa_cliente,
	cliente.codigo_cliente,
	IF(documento.monto = 0, 0, documento.monto*-1) AS monto,
	IF(documento.saldo_pago = 0, 0, documento.saldo_pago*-1) AS saldo_pago,
	CONCAT(prm_moneda.simbolo, ' ', IF(documento.monto = 0, 0, documento.monto*-1)) AS monto_con_simbolo,
	CONCAT(prm_moneda.simbolo, ' ', IF(documento.saldo_pago = 0, 0, documento.saldo_pago*-1)) AS saldo_pago_con_simbolo,
	documento.glosa_documento,
	documento.fecha
FROM
	documento
	LEFT JOIN prm_moneda ON prm_moneda.id_moneda = documento.id_moneda
	LEFT JOIN cliente ON documento.codigo_cliente = cliente.codigo_cliente
WHERE
	es_adelanto = 1";

//Filtros
if (isset($codigo_cliente)) $filtros['codigo_cliente'] = $codigo_cliente;
if (isset($pago_honorarios)) $filtros['pago_honorarios'] = $pago_honorarios;
if (isset($pago_gastos)) $filtros['pago_gastos'] = $pago_gastos;
if (isset($id_contrato)) $filtros['id_contrato'] = $id_contrato;
if (isset($moneda)) $filtros['moneda'] = $moneda;

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
if(isset($filtros['id_contrato'])){
	$query .= " AND (documento.id_contrato = '".$filtros['id_contrato']."' OR documento.id_contrato IS NULL)";
}
$buscador = new Buscador($sesion, $query, "Objeto", $desde, $x_pag = 12, empty($orden) ? 'documento.fecha_creacion DESC' : $orden);
$buscador->nombre = "buscador_adelantos";
$buscador->titulo = "Adelantos";

//Encabezados
$buscador->AgregarEncabezado("id_documento", __('N°'));
$buscador->AgregarEncabezado("glosa_cliente", __('Cliente'));
$buscador->AgregarEncabezado("fecha", __('Fecha'));
$buscador->AgregarEncabezado("glosa_documento", __('Descripción'), "align=\"center\"");
$buscador->AgregarEncabezado("monto_con_simbolo", __('Monto'), "align=\"right\"");
$buscador->AgregarEncabezado("saldo_pago_con_simbolo", __('Saldo'), "align=\"right\"");

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
$accion_adelanto = "<a href='javascript:void(0)' onclick=\"nuovaFinestra('Agregar_Adelanto', 730, 580,'ingresar_documento_pago.php?id_documento=" . $fila->fields['id_documento'] .  "&adelanto=1&popup=1', 'top=100, left=155');\" ><img src='" . Conf::ImgDir() . "/editar_on.gif' border='0' title='Editar' /></a>";
   	// $accion_adelanto = "<a href='ingresar_documento_pago.php?id_documento=" . $fila->fields['id_documento'] .  "&adelanto=1&popup=1&codigo_cliente=". $fila->fields['codigo_cliente'] ."&tipopago=editaadelanto' onclick=\"return hs.htmlExpand(this, {objectType: 'iframe',height:580,width:800})\" title='Editar Adelanto'><img src='" . Conf::ImgDir() . "/editar_on.gif' border='0' title='Editar' /></a>";
	
    if ($fila->fields['monto'] == $fila->fields['saldo_pago'])
	{
		$accion_adelanto .= "<a style='cursor:pointer;'><img src='" . Conf::ImgDir() . "/cruz_roja_nuevo.gif' border='0' title='Eliminar' onclick='EliminarAdelanto(" . $fila->fields['id_documento'] . ");return false;' /></a>";
	}
	else
	{
		$accion_adelanto .= "<a style='cursor:pointer;'><img src='" . Conf::ImgDir() . "/cruz_roja_nuevo.gif' border='0' title='Eliminar' onclick='alert(\"No se puede eliminar el adelanto porque ha sido utilizado como abono en algún " . __('Cobro') . "\");return false;' /></a>";
	}
	return $accion_adelanto;
}
?>
<script type="text/javascript" charset="utf-8">

	function EliminarAdelanto(adelanto)
	{
		if (confirm('¿Esta seguro que desea eliminar el adelanto?'))
		{
			window.location.href = "adelantos.php?id_documento_e=" + adelanto + "&opc=eliminar";
		}
	}
	<?php if ($elegir_para_pago) { ?>
	function ElegirParaPago(url)
	{
		<?php if($mantener_ventana){ ?>
		document.location.href = url + '&ocultar_boton_adelantos=1';
		<?php } else { ?>
		window.opener.location.href = url;
		window.close();
		<?php } ?>
		return false;
	}
	<?php } ?>
</script>
