<?php
require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion(array('COB'));
$pagina = new PaginaCobro($sesion, $id_cobro);

$cliente = new Cliente($sesion);
$cliente->LoadByCodigo($cobro->fields['codigo_cliente']);
$nombre_cliente = $cliente->fields['glosa_cliente'];
$pagina->titulo = __('Emitir') . ' ' . __('Cobro') . __(' :: Selección de asuntos #') . $id_cobro . __(' ') . $nombre_cliente;

if ($id_cobro) {

	$cobro = new Cobro($sesion);

	if (!$cobro->Load($id_cobro)) {
		$pagina->AddError(__('Cliente inválido'));
	}

	if (!$cliente->LoadByCodigo($cobro->fields['codigo_cliente'])) {
		$pagina->FatalError(__('Cliente inválido'));
	}

	$cobro->Edit('etapa_cobro', '1');
	$cobro->Write();
} else if ($id_contrato > 0) {

	$contrato = new Contrato($sesion);

	if (!$contrato->Load($id_contrato)) {
		$pagina->FatalError(__('Cliente inválido'));
	}

	$cobro = new Cobro($sesion);
	$cobro->Edit('id_usuario', $sesion->usuario->fields['id_usuario']);
	$cobro->Edit('codigo_cliente', $contrato->fields['codigo_cliente']);
	$cobro->Edit('id_contrato', $contrato->fields['id_contrato']);
	$cobro->Edit('id_moneda', $contrato->fields['id_moneda']);

	$moneda = new Moneda($sesion);
	$moneda->Load($contrato->fields['id_moneda']);
	$cobro->Edit('tipo_cambio_moneda', $moneda->fields['tipo_cambio']);
	$cobro->Edit('forma_cobro', $contrato->fields['forma_cobro']);
	$cobro->Edit('monto_contrato', $contrato->fields['monto']);
	$cobro->Edit('retainer_horas', $contrato->fields['retainer_horas']);
	$cobro->Write();

	$id_cobro = $cobro->fields['id_cobro'];
	$cobro->Load($id_cobro);

	if (!$cliente->LoadByCodigo($cobro->fields['codigo_cliente'])) {
		$pagina->FatalError("Cliente inválido");
	}

	$contrato->AddCobroAsuntos($id_cobro);
	/* Guardando los tipo de cambio de las monedas */
	if ($id_cobro) {
		$cobro_moneda = new CobroMoneda($sesion);
		$cobro_moneda->ActualizarTipoCambioCobro($id_cobro);
	}
} else if ($codigo_cliente) {

	if (!$cliente->LoadByCodigo($codigo_cliente)) {
		$pagina->FatalError(__('Cliente inválido'));
	}

	$cobro = new Cobro($sesion);
	$cobro->Edit('id_usuario', $sesion->usuario->fields['id_usuario']);
	$cobro->Edit('codigo_cliente', $cliente->fields['codigo_cliente']);
	$cobro->Edit('id_moneda', $cliente->fields['id_moneda']);
	$cobro->Edit('forma_cobro', 'TASA'); #set forma_cobro default TASA
	$moneda = new Moneda($sesion);
	$moneda->Load($cliente->fields['id_moneda']);
	$cobro->Edit('tipo_cambio_moneda', $moneda->fields['tipo_cambio']);
	$cobro->Write();
	$id_cobro = $cobro->fields['id_cobro'];
	$cobro->Load($id_cobro);

	/* Guardando los tipo de cambio de las monedas */
	if ($id_cobro) {
		$cobro_moneda = new CobroMoneda($sesion);
		$cobro_moneda->ActualizarTipoCambioCobro($id_cobro);
	}
} else {
	$pagina->FatalError(__('Debe especificar un cliente o') . ' ' . __('cobro'));
}

if ($cobro->fields['estado'] <> 'CREADO' && $cobro->fields['estado'] <> 'EN REVISION') {
	$pagina->Redirect("cobros6.php?id_cobro=" . $id_cobro . "&popup=1&contitulo=true");
}

if ($opc == "siguiente") {
	if (!empty($cobro->fields['incluye_honorarios'])) {
		$pagina->Redirect("cobros3.php?id_cobro=" . $id_cobro . "&popup=1&contitulo=true");
	} else {
		$pagina->Redirect("cobros4.php?id_cobro=" . $id_cobro . "&popup=1&contitulo=true");
	}
}
?>

<script type="text/javascript">
	function calcHeight(idIframe, idMainElm) {
		ifr = $(idIframe);
		the_size = ifr.$(idMainElm).offsetHeight + 20;
		new Effect.Morph(ifr, {
			style: 'height:' + the_size + 'px',
			duration: 0.2
		});
	}
</script>
<?php
$pagina->PrintTop($popup);

if ($popup) {
	echo '<table width="100%" border="0" cellspacing="0" cellpadding="2">';
		echo '<tr>';
			echo '<td valign="top" align="left" class="titulo" bgcolor="rgb(163, 213, 92)">';
				echo __('Emitir') . ' ' . __('Cobro') . __(' :: Selección de asuntos #') . $id_cobro . __(' ') . $nombre_cliente;
			echo '</td>';
		echo '</tr>';
	echo '</table>';
}

$pagina->PrintPasos($sesion, 1, '', $id_cobro, $cobro->fields['incluye_gastos'], $cobro->fields['incluye_honorarios']);

if ($orden == "") {
	$orden = "fecha_creacion DESC";
}

$query = "SELECT SQL_CALC_FOUND_ROWS asunto.*,id_moneda,cobro_asunto.id_cobro  
FROM asunto LEFT JOIN cobro_asunto ON asunto.codigo_asunto = cobro_asunto.codigo_asunto AND cobro_asunto.id_cobro = '$id_cobro' 
WHERE asunto.activo=1 AND asunto.codigo_cliente = '" . $cliente->fields['codigo_cliente'] . "'
AND asunto.cobrable = 1";
?>
<script type="text/javascript">

	function GrabarCampo(accion, asunto, cobro, valor, id_moneda)
	{
		var http = getXMLHTTP();

		if (valor) {
			valor = 'agregar';
		} else {
			valor = 'eliminar';
		}

		loading("Actualizando opciones");
		http.open('get', 'ajax_grabar_campo.php?accion=' + accion + '&codigo_asunto=' + asunto + '&id_cobro=' + cobro + '&valor=' + valor + '&id_moneda=' + id_moneda);

		http.onreadystatechange = function() {

			if (http.readyState == 4) {
				var response = http.responseText;
				var update = new Array();
				if (response.indexOf('OK') == -1) {
					alert(response);
				}
				offLoading();
			}
		};
		http.send(null);
	}

</script>

<?php
if (!$checkall) {
	$checkall = 0;
}
?>

	<form method="post">
	<input type="hidden" name="opc">
	<input type="hidden" name="id_cobro" value=<?php $id_cobro ?>>
		<table width=100%>
			<tr>
				<td align="right">
					<input type="button" class="btn" value="<?php echo __('Siguiente >>') ?>" onclick="this.form.opc.value = 'siguiente'; this.form.submit();">
				</td>
			</tr>
			<tr>
				<td class="cvs" align="center" colspan="2">
					<iframe name="asuntos" id="asuntos" onload="calcHeight(this.id, 'pagina_body');" src='asuntos.php?codigo_cliente=<?php echo $cliente->fields['codigo_cliente'] ?>&opc=entregar_asunto&id_cobro=<?= $id_cobro ?>&popup=1&motivo=cobros&checkall=<?= $checkall ?>' frameborder="0" width="800px" height="320px"></iframe>
				</td>
			</tr>
		</table>
	</form>

	<?php

	function Cobrable(& $fila) {
		global $id_cobro;
		$checked = '';
		if ($fila->fields['id_cobro'] == $id_cobro and $id_cobro != '')
			$checked = "checked";
		$id_moneda = $fila->fields['id_moneda'];
		$Check = "<input type='checkbox' $checked onchange=GrabarCampo('agregar_asunto','" . $fila->fields['codigo_asunto'] . "','$id_cobro',this.checked,$id_moneda,$monto)>";
		return $Check;
	}

	function funcionTR(& $asunto) {
		static $i = 0;

		if ($i % 2 == 0) {
			$color = "#dddddd";
		} else {
			$color = "#ffffff";
		}
		$formato_fecha = "%d/%m/%y";
		$fecha = Utiles::sql2fecha($asunto->fields[fecha_ultimo_cobro], $formato_fecha);
		$html .= "<tr bgcolor=$color style=\"border-right: 1px solid #409C0B; border-left: 1px solid #409C0B; border-bottom: 1px solid #409C0B; \">";
		$html .= "<td align=center>" . $asunto->fields['codigo_asunto'] . "</td>";
		$html .= "<td align=center>" . $asunto->fields['glosa_asunto'] . "</td>";
		$html .= "<td align=center>$fecha</td>";
		$html .= "<td align=center>" . Cobrable($asunto) . "</td>";
		$html .= "</tr>";
		$i++;
		return $html;
	}

	$pagina->PrintBottom($popup);
	?>
