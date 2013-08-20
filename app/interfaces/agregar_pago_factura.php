<?php
require_once dirname(__FILE__) . '/../conf.php';

//La funcionalidad contenida en esta pagina puede invocarse desde integracion_contabilidad3.php (SOLO GUARDAR).
//(desde_webservice será true). Esa pagina emula el POST, es importante revisar que los cambios realizados en la FORM
//se repliquen en el ingreso de datos via webservice.
if ($desde_webservice && UtilesApp::VerificarPasswordWebServices($usuario, $password)) {
	$sesion = new Sesion();
	$factura = new Factura($sesion);
} else { //ELSE (no es WEBSERVICE)
	$sesion = new Sesion(array('COB'));
	$pagina = new Pagina($sesion);
	$id_usuario = $sesion->usuario->fields['id_usuario'];
}
$documento = new Documento($sesion);
$documento_adelanto = new Documento($sesion);
$cobro = new Cobro($sesion);
if ($id_cobro) {
	$cobro->Load($id_cobro);
	$id_moneda_cobro = $cobro->fields['opc_moneda_total'];
}

$pago = new FacturaPago($sesion);
// echo '<pre>';print_r($sesion);echo '</pre>';
//Desde el webservice viene un id_pago (id_contabilidad).
if ($desde_webservice) {
	$pago->LoadByIdContabilidad($id_contabilidad);
} else {
	if (isset($_GET['id_factura_pago'])) {
		$id_factura_pago = $_GET['id_factura_pago'];
	}

	if ($id_adelanto) {
		$query_factura_pago = "select fp.id_factura_pago
			from factura_pago fp
			join neteo_documento nd on nd.id_neteo_documento = fp.id_neteo_documento_adelanto
			join documento dd on dd.id_documento = nd.id_documento_cobro and dd.id_cobro = $id_cobro
			where nd.id_documento_pago = $id_adelanto
			";
		$res = mysql_query($query_factura_pago, $sesion->dbh) or Utiles::errorSQL($query_factura_pago, __FILE__, __LINE__, $sesion->dbh);
		list($id_factura_pago) = mysql_fetch_array($res);
		$utilizando_adelanto = true;
	}
	if (!empty($id_factura_pago)) {
		$pago->Load($id_factura_pago);
		$id_moneda = $pago->fields['id_moneda'];
		$id_moneda_cobro = $pago->fields['id_moneda_cobro'];
		$numeros_facturas = $pago->GetListaFacturasSoyPago($id_factura_pago, 'id_factura_pago', 'numero');
		$arreglo_facturas = explode(',', $lista_facturas);
		$codigo_cliente = $pago->fields['codigo_cliente'];
		$id_concepto = $pago->fields['id_concepto'];
		$tipo_doc = $pago->fields['tipo_doc'];
		$nro_documento = $pago->fields['nro_documento'];
		$nro_cheque = $pago->fields['nro_cheque'];
		$descripcion = $pago->fields['descripcion'];
		$id_banco = $pago->fields['id_banco'];
		$id_cuenta = $pago->fields['id_cuenta'];
		$pago_retencion = $pago->fields['pago_retencion'];
	} else if (!empty($lista_facturas)) {
		$numeros_facturas_tmp = array();
		$query_num_facturas = "SELECT numero FROM factura WHERE id_factura IN ($lista_facturas) ";
		$resunf = mysql_query($query_num_facturas, $sesion->dbh) or Utiles::errorSQL($query_num_facturas, __FILE__, __LINE__, $sesion->dbh);
		if (mysql_num_rows($resunf) > 0) {
			while ($numfact = mysql_fetch_array($resunf)) {
				array_push($numeros_facturas_tmp, $numfact['numero']);
			}
			$numeros_facturas = implode(',', $numeros_facturas_tmp);
		}

		$query_montos_facturas = "SELECT SUM(honorarios) as honorarios, SUM(subtotal_gastos+subtotal_gastos_sin_impuesto) as gastos
																FROM factura WHERE id_factura IN ($lista_facturas) ";
		$res_montos = mysql_query($query_montos_facturas, $sesion->dbh) or Utiles::errorSQL($query_num_facturas, __FILE__, __LINE__, $sesion->dbh);
		list($honorarios_facturas, $gastos_facturas) = mysql_fetch_array($res_montos);
		$factura_fake = new Factura($sesion);
		$hay_adelantos = $factura_fake->SaldoAdelantosDisponibles($codigo_cliente, $cobro->fields['id_contrato'], $honorarios_facturas, $gastos_facturas) > 0;
	}
}
$moneda_pago = new Moneda($sesion);
$moneda_pago->Load($id_moneda);

$moneda_cobro = new Moneda($sesion);
$moneda_cobro->Load($id_moneda_cobro);

if (!empty($pago->fields['id_neteo_documento_adelanto'])) {
	$id_neteo_documento_adelanto = $pago->fields['id_neteo_documento_adelanto'];
	$neteo = new NeteoDocumento($sesion);
	$neteo->Load($id_neteo_documento_adelanto);
	$id_adelanto = $neteo->fields['id_documento_pago'];
}

$monto_pago_adelanto = $monto_pago;



if ($id_adelanto) {
	$documento_adelanto->Load($id_adelanto);
}

global $saldo_pago;
$saldo_pago = $id_neteo_documento_adelanto ? $pago->fields['monto_moneda_cobro'] : null;
if ($id_adelanto) {

	$query_tipo_cambio = "select cm.tipo_cambio as tipo_cambio_cobro, ca.tipo_cambio as  tipo_cambio_adelanto
		from cobro c
		 join cobro_moneda cm on c.opc_moneda_total = cm.id_moneda and cm.id_cobro = $id_cobro
		 join cobro_moneda ca on ca.id_moneda = " . $documento_adelanto->fields['id_moneda'] . "  and ca.id_cobro = $id_cobro
		 where c.id_cobro = $id_cobro";

	$res_tipo_cambio = mysql_query($query_tipo_cambio, $sesion->dbh) or Utiles::errorSQL($query_tipo_cambio, __FILE__, __LINE__, $sesion->dbh);
	list($tipo_cambio_cobro, $tipo_cambio_adelanto) = mysql_fetch_array($res_tipo_cambio);
	if ($monto_pago_adelanto > $documento_adelanto->fields['saldo_pago']) {
		$monto_pago_adelanto = -$documento_adelanto->fields['saldo_pago'];
		$monto_pago = $monto_pago_adelanto * $tipo_cambio_adelanto / $tipo_cambio_cobro;
	}

	$saldo_pago = (-$documento_adelanto->fields['saldo_pago'] + $pago->fields['monto']) * $tipo_cambio_adelanto / $tipo_cambio_cobro;
}

if ($utilizando_adelanto) {
	$monto_pago_adelanto = $monto_pago * $tipo_cambio_cobro / $tipo_cambio_adelanto;
	$query = "SELECT id_concepto FROM prm_factura_pago_concepto WHERE glosa = 'Adelanto'";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	list($id_concepto) = mysql_fetch_array($resp);

	$tipo_doc = $documento_adelanto->fields['tipo_doc'];
	$nro_documento = $documento_adelanto->fields['numero_doc'];
	$nro_cheque = $documento_adelanto->fields['numero_cheque'];
	$descripcion = 'Adelanto #' . $documento_adelanto->fields['id_documento'] . ' - ' . $documento_adelanto->fields['glosa_documento'];
	$id_banco = $documento_adelanto->fields['id_banco'];
	$id_cuenta = $documento_adelanto->fields['id_cuenta'];
}


if ($opcion == 'imprimir_voucher') {
	include dirname(__FILE__) . '/factura_pago_doc.php';
	exit;
}

if ($opcion == 'guardar') {
	if ($desde_webservice) {
		$errores = array();
		if (!is_numeric($monto))
			$errores[] = __('El monto ingresado en el campo "Monto" no es válido, debe ingresar el monto del documento.');
		if (!is_numeric($monto_moneda_cobro))
			$errores[] = __('El monto ingresado en el campo "Monto pagado" no es válido, debe ingresar el monto descontado del saldo.');
	}
	else {
		if (empty($id_adelanto)) {
			if (!is_numeric($monto))
				$pagina->AddError(__('El monto ingresado en el campo "Monto" no es válido.'));
			if (!is_numeric($monto_moneda_cobro) && $monto_moneda_cobro != '')
				$pagina->AddError(__('El monto ingresado en el campo "Equivalente a" no es válido.'));
		}

		$errores = $pagina->GetErrors();
	}

	$guardar_datos = true;
	if (!empty($errores)) {
		$guardar_datos = false;
	}

	if ($guardar_datos && $id_adelanto) {
		$documento->LoadByCobro($id_cobro);
		$facturas = array();
		foreach ($_POST as $nombre_variable => $valor) {
			if (strpos($nombre_variable, 'saldo_') === 0) {
				$saldo_fact = explode('_', $nombre_variable);
				$factura = $saldo_fact[1];
				$saldo = $valor;
				$facturas[$factura] = $saldo;
			}
		}
		if ($documento->GenerarPagosDesdeAdelantos($documento->fields['id_documento'], $facturas, $id_adelanto, true)) {
			$query_factura_pago_guardar = "select fp.id_factura_pago
			from factura_pago fp
			join neteo_documento nd on nd.id_neteo_documento = fp.id_neteo_documento_adelanto
			where nd.id_documento_pago = $id_adelanto and nd.id_documento_cobro = " . $documento->fields['id_documento'];

			$res = mysql_query($query_factura_pago_guardar, $sesion->dbh) or Utiles::errorSQL($query_factura_pago_guardar, __FILE__, __LINE__, $sesion->dbh);
			list($id_factura_pago) = mysql_fetch_array($res);

			$pagina->addInfo(__('Adelanto ingresado con éxito'));
			?>
			<script type="text/javascript">
				window.opener.Refrescar();
			</script>
			<?php
		}
	} else if ($guardar_datos) {
		//echo '<pre>';print_r($_POST);echo '</pre>';
		if (empty($id_neteo_documento_adelanto)) {
			if (!empty($id_factura_pago)) {
				$pago->Edit('id_factura_pago', $id_factura_pago);
			}
			if ($desde_webservice) {
				$pago->Edit('id_contabilidad', $id_contabilidad);
			}
			if (is_numeric($_POST['id_moneda']) && $_POST['id_moneda'] != $id_moneda)
				$id_moneda = $_POST['id_moneda']; // permite refrescar tipo de moneda al editar cobro

			$pago->Edit('fecha', Utiles::fecha2sql($fecha), true);

			$codigo_cliente_factura = $_POST['codigo_cliente_factura'];
			$monto = $_POST['monto'];
			$monto_moneda_cobro = $_POST['monto_moneda_cobro'];
			$id_moneda_cobro = $_POST['id_moneda_cobro'];
			$tipo_doc = $_POST['tipo_doc'];
			$nro_documento = $_POST['numero_doc'];
			$numero_cheque = $_POST['numero_cheque'];
			$descripcion = $_POST['glosa_documento'];
			$id_banco = $_POST['id_banco'];
			$id_cuenta = $_POST['id_cuenta'];
			$pago_retencion = $_POST['pago_retencion'];
			$id_concepto = $_POST['id_concepto'];

			$pago->Edit('codigo_cliente', $codigo_cliente_factura, true);
			$pago->Edit('monto', $monto, true);
			$pago->Edit('id_moneda', $id_moneda, true);
			$pago->Edit('monto_moneda_cobro', $monto_moneda_cobro, true);
			$pago->Edit('id_moneda_cobro', $id_moneda_cobro, true);
			$pago->Edit('tipo_doc', $tipo_doc, true);
			$pago->Edit('nro_documento', $nro_documento, true);
			$pago->Edit('nro_cheque', $numero_cheque, true);
			$pago->Edit('descripcion', $descripcion, true);
			$pago->Edit('id_banco', $id_banco, true);
			$pago->Edit('id_cuenta', $id_cuenta, true);
			$pago->Edit('pago_retencion', $pago_retencion, true);
			$pago->Edit('id_concepto', $id_concepto, true);
		}
		else {
			$pago->LoadByNeteoAdelanto($id_neteo_documento_adelanto);
		}

		if ($pago->Write()) {
			$cta_cte_fact = new CtaCteFact($sesion);
			$neteos = array();

			if ($desde_webservice) {
				$neteos[] = array($id_factura, $monto_moneda_cobro);
			}
			else
				foreach ($_POST as $nombre_variable => $valor) {
					if (strpos($nombre_variable, 'saldo_') === 0) {
						$saldo_fact = explode('_', $nombre_variable);
						$factura = $saldo_fact[1];
						$saldo = $valor;
						$neteos[] = array($factura, $saldo);
					}
				}
			$documento->LoadByCobro($id_cobro);
			$id_factura_pago = $pago->fields['id_factura_pago'];
			$cta_cte_fact->IngresarPago($pago, $neteos, $id_cobro, &$pagina, $ids_monedas_factura_pago, $tipo_cambios_factura_pago);
			$monto_pago -= $monto;

			$cobro->CambiarEstadoSegunFacturas();

			//Al llamar desde Webservice, IngresarPago utilizó una $pagina falsa. Se puede ver el contenido mediante:
			//echo $pagina->Output();
			if ($desde_webservice) {
				if ($pago->fields['id_factura_pago'])
					$resultado = array('id_pago' => $pago->fields['id_factura_pago'], 'resultado_pago' => 'El ' . __('pago') . ' se ha guardado exitosamente.');
				else
					$resultado = array('id_pago' => $pago->fields['id_factura_pago'], 'resultado_pago' => 'Error al guardar el documento de ' . __('pago') . ': ' . $pagina->Output());
				return 'DONE';
			}//Si vengo del webservice, no continua.
			?>
			<script type="text/javascript">
				window.opener.Refrescar();
			</script>
			<?php
		}
	} else {
		$resultado = array('id_pago' => '-', 'resultado_pago' => 'Error al guardar el ' . __('pago') . '.');
		return 'ERROR';
	}
}

$mvto_pago = new CtaCteFactMvto($sesion);
$mvto_pago->LoadByPago($id_factura_pago);

$imprimir_voucher = false;
$pagina->PrintTop($popup);
$join_facturas = 'LEFT JOIN';
$on_facturas = '';
if ($lista_facturas) {
	$definir_orden = " IF(f.id_factura IN ($lista_facturas), IF(monto_pago > 0, 0, 1),  IF(monto_pago > 0, 1, 2))  AS orden ";
} else {
	$definir_orden = " IF(monto_pago > 0, 0, 1) AS orden ";
}
$where_lista_cobro = "";
if ($id_cobro) {
	$definir_orden2 = " IF(f.id_cobro = '$id_cobro', 0, f.id_cobro) AS orden2 ";
	$where_lista_cobro = " OR f.id_cobro = '$id_cobro' ";
} else {
	$definir_orden2 = " f.id_cobro AS orden2 ";
}

$query__listado = "SELECT SQL_CALC_FOUND_ROWS
						f.id_cobro,
						f.id_factura,
						f.numero,
						pdl.glosa AS glosa_documento_legal,
						IF(ccfm.saldo-ccfmn.monto=0, 0, IF(ccfmn.monto > 0,-ccfm.saldo+ccfmn.monto,-ccfm.saldo)) as saldo_factura,
						ccfmn.monto AS monto_pago,
						f.id_moneda,
						pm.simbolo as simbolo,
						pm.tipo_cambio,
						pm.cifras_decimales,
						$definir_orden,
						$definir_orden2
					FROM cta_cte_fact_mvto AS ccfm
					LEFT JOIN cta_cte_fact_mvto_neteo AS ccfmn
						ON ccfm.id_cta_cte_mvto = ccfmn.id_mvto_deuda
						AND ccfmn.id_mvto_pago = '{$mvto_pago->fields['id_cta_cte_mvto']}'
					$join_facturas factura AS f ON ccfm.id_factura = f.id_factura $on_facturas
					JOIN prm_moneda AS pm ON f.id_moneda = pm.id_moneda
					LEFT JOIN prm_documento_legal AS pdl ON pdl.id_documento_legal = f.id_documento_legal
					WHERE (f.codigo_cliente = '$codigo_cliente' $where_lista_cobro)
						AND f.id_moneda = '$id_moneda_cobro'
						AND f.anulado = 0
						AND pdl.codigo!='NC'
						AND ccfm.saldo < 0
						OR ccfmn.monto != 0";
?>

<script type="text/javascript">
	jQuery(document).ready(function() {

		jQuery('.saldojq').keyup(function() {
			var decimales_cobro = Number(jQuery('#cifras_decimales_cobro').val());
			var decimales = Number(jQuery('#cifras_decimales_pago').val());

			var total = Number(0);
			var total_pagar = Number(0);
			MontoValido(jQuery(this).attr('id'));
			var max = Number(jQuery('#x_' + jQuery(this).attr('id').replace('_', '_hide_')).val());
			var max_saldo = Number(jQuery('#saldo_pago_aux').val());
			if (Number(jQuery(this).val()) > max)
				jQuery(this).val(Redondear(max, decimales_cobro));
			jQuery('.saldojq').each(function() {
				total = total + Number(jQuery(this).val());
			});
			jQuery('.saldojq:not([readonly="readonly"])').each(function() {
				total_pagar = total_pagar + Number(jQuery(this).val());
			});

			var moneda = jQuery('#id_moneda').val();
			var num2 = Number(jQuery('#factura_pago_moneda_<?php echo $id_moneda_cobro; ?>').val());
			var div = Number(jQuery('#factura_pago_moneda_' + moneda).val());

<?php
if ($id_adelanto) {
	?>
				if (total_pagar > max_saldo) {
					jQuery(this).val(Redondear(Number(jQuery(this).val()) - (total_pagar - max_saldo), decimales_cobro));
					total_pagar = max_saldo;
				}
				if (total > max_saldo) {
					total = max_saldo;
				}
	<?php
} else {
	$total_pagar = $total;
}
?>

			jQuery('#monto_moneda_cobro').val(Redondear(total_pagar, decimales_cobro));

			var monto_total = Redondear(total * num2 / div, decimales);
			var monto_total_pagar = Redondear(total_pagar * num2 / div, decimales);

			jQuery('#monto').val(monto_total_pagar);
			jQuery('#saldo_adelanto').val(Number(jQuery('#saldo_pago_original').val().replace(',', '.')) - monto_total);

		}).keyup();
		jQuery('#monto_moneda_cobro').keyup(function() {
			var moneda = jQuery('#id_moneda').val();
			var num1 = Number(jQuery('#monto_moneda_cobro').val());
			var num2 = Number(jQuery('#factura_pago_moneda_<?php echo $id_moneda_cobro; ?>').val());
			var div = Number(jQuery('#factura_pago_moneda_' + moneda).val());
			var decimales = Number(jQuery('#cifras_decimales_pago').val());

			var monto_total = Redondear(num1 * num2 / div, decimales);
			jQuery('#monto').val(monto_total).keyup();
			jQuery('#saldo_adelanto').val(Redondear(Number(jQuery('#saldo_pago_original').val().replace(',', '.')) - monto_total, decimales));

		});
	});
	function ShowCheque()
	{
		if ($('tipo_doc').value == "C")
			$('tr_cheque').style.display = "table-row";
		else
			$('tr_cheque').style.display = "none";
	}



	function MostrarTipoCambioPago()
	{
		$('TipoCambioDocumentoPago').show();
	}

	function MontoValido(id_campo)
	{
		var monto = document.getElementById(id_campo).value;
		if (monto.match(/^\d+(\.\d+)?$/))
			return false;

		monto = monto.replace(/[^\d.,]/g, '');
		monto = monto.replace(',', '.');
		if (monto == '')
			monto = '0';
		var arr_monto = monto.split('.');
		var monto = arr_monto[0];
		for ($i = 1; $i < arr_monto.length - 1; $i++)
			monto += arr_monto[$i];
		if (arr_monto.length > 1)
			monto += '.' + arr_monto[arr_monto.length - 1];

		document.getElementById(id_campo).value = monto;
	}

	function CancelarDocumentoMonedaPago()
	{
		$('TipoCambioDocumentoPago').hide();
	}

	function ActualizarDocumentoMonedaPago()
	{
		ids_monedas = $('ids_monedas_factura_pago').value;
		arreglo_ids = ids_monedas.split(',');
		$('tipo_cambios_factura_pago').value = "";
		for (var i = 0; i < arreglo_ids.length - 1; i++)
			$('tipo_cambios_factura_pago').value += $('factura_pago_moneda_' + arreglo_ids[i]).value + ",";
		i = arreglo_ids.length - 1;
		$('tipo_cambios_factura_pago').value += $('factura_pago_moneda_' + arreglo_ids[i]).value;
		if ($('id_factura_pago') != '')
		{
			var tc = new Array();
			for (var i = 0; i < arreglo_ids.length; i++)
				tc[i] = $('factura_pago_moneda_' + arreglo_ids[i]).value;
			$('contenedor_tipo_load').innerHTML =
					"<table width=510px><tr><td align=center><br><br><img src='<?php echo Conf::ImgDir() ?>/ajax_loader.gif'/><br><br></td></tr></table>";
			var http = getXMLHTTP();
			var url = root_dir + '/app/interfaces/ajax.php?accion=actualizar_factura_pago_moneda&id_factura=<?php echo $factura->fields['id_factura'] ?>&ids_monedas=' + ids_monedas + '&tcs=' + tc.join(',');
			http.open('get', url);
			http.onreadystatechange = function()
			{
				if (http.readyState == 4)
				{
					var response = http.responseText;
					if (response == 'EXITO')
					{
						$('contenedor_tipo_load').innerHTML = '';
					}
				}
			}
			http.send(null);
			CancelarDocumentoMonedaPago();
		}
	}

	function ActualizarMonto()
	{
<?php if (!empty($id_neteo_documento_adelanto)) echo 'return false;'; ?>
		var lista_facturas = $('lista_facturas').value;
		var arreglo_facturas = lista_facturas.split(',');

		var cifras_decimales = $('cifras_decimales_pago').value;
		var monto = Number(0);
		for (var i = 0; i <= arreglo_facturas.length - 1; i++)
		{
			$$('[id^="saldo_"].saldojq').each(function(elem) {
				ids = elem.id.split('_');
				if (ids[1] == arreglo_facturas[i])
				{
					var saldo_fact = Number($('x_saldo_hide_' + ids[1]).value);
					if (Number(elem.value) > saldo_fact)
						elem.value = saldo_fact;
					monto += Number(Redondear(elem.value, cifras_decimales));
				}
			});
		}
		$('monto_moneda_cobro').value = Redondear(monto, cifras_decimales);
		if ($('id_moneda') == '<?php echo $id_moneda_cobro ?>') {
			$('monto').value = $('monto_moneda_cobro').value;
		}
	}

	var suma_saldo = 0;
	var monto_tmp = 0;
	function ActualizarMontoMonedaCobro() {
		var moneda = $('id_moneda').value;
		if (moneda == '<?php echo $id_moneda_cobro ?>') {
			$('span_monto_equivalente').style.visibility = 'hidden';
			$('monto_moneda_cobro').value = $('monto').value;
		}
		else {
			$('span_monto_equivalente').style.visibility = 'visible';
			$('monto_moneda_cobro').value = Redondear($('monto').value * $('factura_pago_moneda_' + moneda).value / $('factura_pago_moneda_<?php echo $id_moneda_cobro ?>').value, $('cifras_decimales_pago').value);
		}
		ActualizarMontosIndividuales('monto_moneda_cobro');

<?php if (empty($id_neteo_documento_adelanto)) { ?>
			if (monto_tmp > 0 && !confirm('<?php echo __("El monto ingresado excede el saldo a pagar") ?> (' +
					suma_saldo + ')\n<?php echo __("¿Está seguro que desea continuar?") ?>')) {
				continuar = 0;
				$('monto').value = suma_saldo;
			}
<?php } else { ?>
			if (monto_tmp < 0) {
				alert('La suma de los pagos supera el monto del adelanto (' + monto_tmp + ')');
				continuar = 0;
			}
<?php } ?>
	}

	function CargarCuenta(origen, destino)
	{
		var http = getXMLHTTP();
		var url = 'ajax.php?accion=cargar_cuentas&id=' + $(origen).value;

		loading("Actualizando campo");
		http.open('get', url);
		http.onreadystatechange = function()
		{
			if (http.readyState == 4)
			{
				var response = http.responseText;
				if (response == "~noexiste")
					alert("Ústed no tiene cuentas en este banco.");
				else
				{
					$(destino).options.length = 0;
					cuentas = response.split('//');

					for (var i = 0; i < cuentas.length; i++)
					{
						valores = cuentas[i].split('|');

						var option = new Option();
						option.value = valores[0];
						option.text = valores[1];

						try
						{
							$(destino).add(option);
						}
						catch (err)
						{
							$(destino).add(option, null);
						}
					}
				}
				offLoading();
			}
		};
		http.send(null);
	}

	function SetBanco(origen, destino)
	{
		var http = getXMLHTTP();
		var url = 'ajax.php?accion=buscar_banco&id=' + $(origen).value;

		loading("Actualizando campo");
		http.open('get', url);
		http.onreadystatechange = function()
		{
			if (http.readyState == 4)
			{
				var response = http.responseText;
				$(destino).value = response;
				offLoading();
			}
		};
		http.send(null);
	}

	function ActualizarMontosIndividuales(id)
	{
		suma_saldo = 0;
		monto_tmp = 0;
		var cifras_decimales = $('cifras_decimales_pago').value;
		var lista_facturas = $('lista_facturas').value;
		var arreglo_facturas = lista_facturas.split(',');
		var monto = $(id).value;
		for (var i = 0; i <= arreglo_facturas.length - 1; i++)
		{
			$$('[id^="saldo_"].saldojq').each(function(elem) {
				ids = elem.id.split('_');
				if (ids[1] == arreglo_facturas[i]) {
					var saldo_individual = Math.max(Math.min($('x_saldo_hide_' + ids[1]).value, monto), 0);
					elem.value = Redondear(saldo_individual, cifras_decimales);
					monto -= saldo_individual;
					suma_saldo += saldo_individual;
				}
			});
		}
		monto_tmp = monto;
	}

	function Imprimir_voucher(form, id_factura_pago)
	{
		form.opcion.value = 'imprimir_voucher';
		form.action = "agregar_pago_factura.php?$id_factura_pago=" + id_factura_pago + "&popup=1";
		form.submit();
		return true;
	}

	function CalculaPagoIva()
	{
		var cifras_decimales = $('cifras_decimales_pago').value;
		var lista_facturas = $('lista_facturas').value;
		arreglo_facturas = lista_facturas.split(',');
		var porcentaje_impuesto_retencion = 12;
		for (var i = 0; i <= arreglo_facturas.length - 1; i++)
		{
			var monto_pagos = $('x_saldo_hide_' + arreglo_facturas[i]).value;
			var monto = document.getElementById('monto');

			if ($('pago_retencion').checked)
			{
				if ($('pago_retencion_monto_loaded').value != 'false') {
					monto_retencion_impuestos = $('pago_retencion_monto_loaded').value;
				}
				else {
					monto_retencion_impuestos = monto_pagos * porcentaje_impuesto_retencion;
					monto_retencion_impuestos = (monto_retencion_impuestos.round()) / 100;
					monto_retencion_impuestos = monto_retencion_impuestos.toFixed(cifras_decimales);
				}
				$('saldo_' + arreglo_facturas[i]).value = monto_retencion_impuestos;
			}
			else
			{
				if ($('pago_retencion_monto_loaded').value != 'false') {
					monto_retencion_impuestos = monto_pagos * 100;
					monto_retencion_impuestos = (monto_retencion_impuestos.round()) / porcentaje_impuesto_retencion;
					monto_retencion_impuestos = monto_retencion_impuestos.toFixed(cifras_decimales);
					$('saldo_' + arreglo_facturas[i]).value = monto_retencion_impuestos;
				}
				else {
					$('saldo_' + arreglo_facturas[i]).value = monto_pagos;
				}
			}
		}
		ActualizarMonto();
	}
	var continuar = 1;
	function Guardar(form)
	{
		$('boton_guardar').disabled = true;
		continuar = 1;
		ValidaMontoSaldoPago(form);

<?php if (UtilesApp::GetConf($sesion, 'CodigoSecundario')) { ?>

			if ($('codigo_cliente_secundario').value == '') {
				alert('Debe ingresar un cliente.');
				$('codigo_cliente').focus();
				$('boton_guardar').disabled = false;
				return false;
			}

<? } else { ?>

			if ($('codigo_cliente').value == '') {
				alert('Debe ingresar un cliente.');
				$('codigo_cliente').focus();
				$('boton_guardar').disabled = false;
				return false;
			}

<?php } ?>
		// Validaciones de montos
		if (!isNumber($('monto').value)) {
			alert('El formato del monto ingresado no es valido.');
			$('monto').focus();
			$('boton_guardar').disabled = false;
			return false;
		}
		else if (!isNumber($('monto_moneda_cobro').value)) {
			alert('El formato del monto ingresado no es valido.');
			$('monto_moneda_cobro').focus();
			$('boton_guardar').disabled = false;
			return false;
		}

		if ($('monto').value <= 0) {
			alert('El monto ingresado debe ser mayor a 0');
			$('monto').focus();
			$('boton_guardar').disabled = false;
			return false;
		} else if ($('monto_moneda_cobro').value <= 0) {
			alert('El monto ingresado debe ser mayor a 0');
			$('monto_moneda_cobro').focus();
			$('boton_guardar').disabled = false;
			return false;
		}

		$$('[id^="saldo_"].saldojq').each(function(elem) {
			if (!isNumber($(elem.id).value)) {
				alert('El formato del monto ingresado no es valido.');
				$(elem.id).focus();
				$('boton_guardar').disabled = false;
				return false;
			}
		});
		if (continuar == 0) {
			$('boton_guardar').disabled = false;
			return false;
		}
		else {
			form.action = "agregar_pago_factura.php?popup=1";
			if ($F('id_factura_pago'))
			{
				form.action += '&id_factura_pago=' + $F('id_factura_pago');
			}
			form.action = document.location.href;
			form.opcion.value = 'guardar';
			form.submit();
			return Validar ? Validar(form) : true;
		}
	}

	function isNumber(n) {
		return !isNaN(parseFloat(n)) && isFinite(n);
	}

	function ValidaMontoSaldoPago(form)
	{
		var cifras_decimales = $('cifras_decimales_pago').value;
		var monto = $('monto_moneda_cobro').value;
		var monto_orig = Number(Redondear(monto, cifras_decimales));
		var suma = 0;
		$$('[id^="saldo_"].saldojq').each(function(elem) {
			var ids = elem.id.split('_');
			var saldo_individual = Math.max(Math.min($('x_saldo_hide_' + ids[1]).value, monto), 0);
			if (!jQuery(elem).attr('readonly') || <?php echo (!$id_adelanto ? "true" : "false") ?>) {
				monto -= Number(Redondear(saldo_individual, cifras_decimales));
				suma += Number(elem.value);
			}
		});

		monto = Number(Redondear(monto, cifras_decimales));
		suma = Number(Redondear(suma, cifras_decimales));
<?php if (empty($id_neteo_documento_adelanto)) { ?>
			if (monto > 0 && !confirm('<?php echo __("El monto ingresado excede el saldo a pagar") ?> (' +
					Redondear(($('monto_moneda_cobro').value - monto), cifras_decimales) + ')\n<?php echo __("¿Está seguro que desea continuar?") ?>')) {
				continuar = 0;
			}
<?php } else { ?>
			if (suma > monto_orig) {
				alert('La suma de los pagos (' + suma + ') no puede ser mayor al monto del adelanto (' + monto_orig + ')');
				continuar = 0;
			}
<?php } ?>
	}
</script>

<?php echo Autocompletador::CSS(); ?>
<form method=post action="" id="form_documentos" autocomplete='off'>
	<input type=hidden name=opcion value="guardar" />
	<input type=hidden name='id_doc_cobro' id='id_doc_cobro' value='<?php echo $id_doc_cobro ?>' />
	<input type=hidden name='id_cobro' id='id_cobro' value='<?php echo $id_cobro ?>' />
	<input type="hidden" name='lista_facturas' id='lista_facturas' value='<?php echo $lista_facturas ?>' />
	<input type=hidden name='id_factura_pago' id='id_factura_pago' value='<?php echo $pago->fields['id_factura_pago']; ?>' />
	<input type=hidden name='cifras_decimales_pago' id='cifras_decimales_pago' value="<?php echo $moneda_pago->fields['cifras_decimales'] ?>" />
	<input type=hidden name='cifras_decimales_cobro' id='cifras_decimales_cobro' value="<?php echo $moneda_cobro->fields['cifras_decimales'] ?>" />
	<input type=hidden name='id_factura_pago' id='id_factura_pago' value="<?php echo $pago->fields['id_factura_pago'] ?>" />
	<input type="hidden" name="pago_retencion_monto_loaded" id="pago_retencion_monto_loaded" value="<?php echo $pago->fields['pago_retencion'] ? $pago->fields['monto'] : 'false' ?>" />
	<input type="hidden" name="codigo_cliente_factura" value="<?php echo $pago->fields['codigo_cliente'] ? $pago->fields['codigo_cliente'] : $codigo_cliente ?>" >
	<input type=hidden name='id_neteo_documento_adelanto' id='id_neteo_documento_adelanto' value="<?php echo $id_neteo_documento_adelanto ?>" />
	<!-- Calendario DIV -->
	<div id="calendar-container" style="width:221px; position:absolute; display:none;">
		<div class="floating" id="calendar"></div>
	</div>
	<!-- Fin calendario DIV -->
	<br>
	<table width='90%'>
		<tr>
			<td align=left><b><?php echo $txt_pagina ?></b></td>
		</tr>
	</table>
	<br>

	<table style="border: 0px solid black;" width='90%'>
		<tr>
			<td align=left width="50%">
				<b><?php echo __('Información de Pago') ?> </b>
			</td>
			<?php
			$query = "SELECT count(*) FROM documento WHERE pago_retencion = 1 AND id_cobro = '$id_cobro'";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
			list( $existe_pago_retencion ) = mysql_fetch_array($resp);

			if (Conf::GetConf($sesion, 'PagoRetencionImpuesto') && empty($id_neteo_documento_adelanto)) {
				?>
				<td align=right width="50%">
					<input type="checkbox" name="pago_retencion" id="pago_retencion" onchange="CalculaPagoIva();" value=1 <?php echo $pago->fields['pago_retencion'] ? "checked='checked'" : "" ?> />&nbsp;<?php echo __('Pago retención impuestos') ?>
				</td>
			<?php } else { ?>
				<td align=right width="25%">
					&nbsp;
				</td>
				<?php
			}

			if (!$id_adelanto && $hay_adelantos && empty($pago->fields['id_factura_pago'])) {
				$saldo_gastos = $gastos_facturas > 0 ? '&pago_gastos=1' : '';
				$saldo_honorarios = $honorarios_facturas > 0 ? '&pago_honorarios=1' : '';
				?>
				<td>
					<button type="button" onclick="nuovaFinestra('Adelantos', 730, 470, 'lista_adelantos.php?popup=1&id_cobro=<?php echo $id_cobro; ?>&codigo_cliente=<?php echo $codigo_cliente ?>&elegir_para_pago=1<?php echo $saldo_honorarios; ?><?php echo $saldo_gastos; ?>&id_contrato=<?php echo $cobro->fields['id_contrato']; ?>&desde_factura_pago=1', 'top=\'100\', left=\'125\', scrollbars=\'yes\'');
			return false;" ><?php echo __('Utilizar un adelanto'); ?></button>
				</td align=right width="25%">
			<?php } ?>


		</tr>
	</table>
	<table id="tabla_informacion" style="border: 1px solid black;" width='90%'>
		<tr>
			<td align=right width="20%">
				<?php echo __('Fecha') ?>
			</td>
			<td align=left colspan="3">
				<input type="text" name="fecha" value="<?php echo $pago->fields['fecha'] ? Utiles::sql2date($pago->fields['fecha']) : date('d-m-Y') ?>" id="fecha" size="11" maxlength="10" />
				<img src="<?php echo Conf::ImgDir() ?>/calendar.gif" id="img_fecha" style="cursor:pointer" />
			</td>
		</tr>
		<tr>
			<td align="right" width="20%"><?php echo __('Cliente ') ?></td>
			<td colspan="3" align="left">
				<?php
				if (Conf::GetConf($sesion, 'CodigoSecundario')) {
					$cliente = new Cliente($sesion);
					$codigo_cliente = $cobro->fields['codigo_cliente'];
					$codigo_cliente_secundario = $cliente->CodigoACodigoSecundario($codigo_cliente);
				}

				if (Conf::GetConf($sesion, 'TipoSelectCliente') == 'autocompletador') {
					if (Conf::GetConf($sesion, 'CodigoSecundario')) {
						echo Autocompletador::ImprimirSelector($sesion, '', $codigo_cliente_secundario, '', 280, "CargarTabla(1);");
					} else {
						echo Autocompletador::ImprimirSelector($sesion, $pago->fields['codigo_cliente'] ? $pago->fields['codigo_cliente'] : $codigo_cliente, '', '', 280, "CargarTabla(1);");
					}
				} else {
					if (Conf::GetConf($sesion, 'CodigoSecundario')) {
						echo InputId::ImprimirSinCualquiera($sesion, "cliente", "codigo_cliente_secundario", "glosa_cliente", "codigo_cliente_secundario", $codigo_cliente_secundario, "", "", 280);
					} else {
						echo InputId::ImprimirSinCualquiera($sesion, "cliente", "codigo_cliente", "glosa_cliente", "codigo_cliente", $pago->fields['codigo_cliente'] ? $pago->fields['codigo_cliente'] : $codigo_cliente, " disabled ", "CargarTabla(1);", 280);
					}
				}
				?>
			</td>
		</tr>

		<tr>
			<td align=right>
				<?php echo __('Monto') ?>
			</td>
			<td align=left colspan="3">
				<?php
				$monto_pago = str_replace(',', '.', $monto_pago);
				$saldo_pago_original = -$documento_adelanto->fields['saldo_pago'];
				if (!$utilizando_adelanto && $pago->fields['monto']) {
					$monto_pago_adelanto = $pago->fields['monto'];
				} else if ($utilizando_adelanto && $pago->fields['monto']) {
					$monto_pago_adelanto += $pago->fields['monto'];
					$saldo_pago_original += $pago->fields['monto'];
				}
				if ($id_adelanto && !$utilizando_adelanto) {
					$saldo_pago_original += $pago->fields['monto'];
				}

				$saldo_adelanto = $saldo_pago_original - $monto_pago_adelanto;
				$monto_pago_adelanto = str_replace(',', '.', $monto_pago_adelanto);
				if (empty($monto_pago_adelanto)) {
					$monto_pago_adelanto = 0;
				}
				?>
				<input name="monto" id="monto" size=10 value="<?php echo number_format($monto_pago_adelanto, $moneda_pago->fields['cifras_decimales'], '.', '') ?>" onkeyup="MontoValido(this.id);
		ActualizarMontoMonedaCobro();"/>

				<?php echo Html::SelectQuery($sesion, "SELECT id_moneda,glosa_moneda FROM prm_moneda ORDER BY id_moneda", "id_moneda", $id_moneda, 'onchange="ActualizarMontoMonedaCobro()"', '', "80"); ?>
				<span style="color:#FF0000; font-size:10px">*</span>

				<span id="span_monto_equivalente" style="visibility:<?php echo $id_moneda_cobro == $id_moneda ? 'hidden' : 'visible' ?>">
					Equivalente a <?php echo $moneda_cobro->fields['simbolo'] ?>
					<input id="monto_moneda_cobro" name="monto_moneda_cobro"  value="<?php echo number_format($pago->fields['monto_moneda_cobro'] ? $pago->fields['monto_moneda_cobro'] : $monto_pago, $moneda_cobro->fields['cifras_decimales'], '.', '') ?>" onkeyup="MontoValido(this.id);
		ActualizarMontosIndividuales(this.id);" />
					<input type="hidden" id="id_moneda_cobro" name="id_moneda_cobro" value="<?php echo $id_moneda_cobro ?>" />
				</span>
			</td>
		</tr>

		<?php if ($id_adelanto) { ?>
			<tr>
				<td align=right>
					<?php echo __('Saldo Adelanto') ?>
				</td>
				<td align=left>
					<input type="text" name="saldo_adelanto" id="saldo_adelanto" size=10 value="<?php echo $saldo_adelanto; ?>" readonly="readonly"/>
					<input type="text"  class="oculto" style="display:none;"   name="saldo_pago_original" id="saldo_pago_original" size=10 value="<?php echo $saldo_pago_original; ?>" readonly="readonly"/>
					<input type="text"  class="oculto" style="display:none;"   name="saldo_pago_aux" id="saldo_pago_aux" size=10 value="<?php echo $saldo_pago; ?>" readonly="readonly"/>
				</td>
			</tr>
		<?php } ?>

		<tr>
			<td align=right>
				<?php echo __('Concepto') ?>
			</td>
			<td align=left colspan="3">
				<?php echo Html::SelectQuery($sesion, "SELECT id_concepto,glosa FROM prm_factura_pago_concepto ORDER BY orden", "id_concepto", $id_concepto, '', '', "168"); ?>
			</td>
		</tr>
		<tr>
			<td align=right>
				<?php echo __('Tipo:') ?>
			</td>
			<td align=left>
				<?php
				$query = "SELECT codigo, glosa FROM prm_tipo_pago WHERE familia = 'P' ORDER BY orden ASC";
				$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

				$tipos = array();
				while (list($codigo, $glosa) = mysql_fetch_array($resp)) {
					$tipos[$codigo] = $glosa;
				}
				echo Html::SelectArrayDecente($tipos, 'tipo_doc', $tipo_doc, 'id="tipo_doc" onchange="ShowCheque();"', '', '100px');
				echo __('N° Documento:');
				?>
				<input name=numero_doc size=10 value="<?php echo str_replace("-", "", $nro_documento); ?>" />
			</td>
		</tr>
		<tr id="tr_cheque" style="display:none;">
			<td align=right width="30%">
				<?php echo __('N° Cheque') ?>
			</td>
			<td align="left" colspan="3" width="70%">
				<input name=numero_cheque id=numero_cheque size=10 value="<?php echo $nro_cheque; ?>" />
			</td>
		</tr>
		<tr>
			<td align=right>
				<?php echo __('Descripción') ?>
			</td>
			<td align=left colspan="3">
				<textarea name="glosa_documento" id="glosa_documento" cols="45" rows="3"><?php
				if ($descripcion)
					echo $descripcion;
				else if ($id_cobro) {
					echo "Pago de Factura # " . $numeros_facturas;
				}
				?></textarea>
			</td>
		</tr>

		<tr>
			<td align=right>
				<?php echo __('Banco') ?>
			</td>
			<td align=left colspan="3">
				<?php echo Html::SelectQuery($sesion, "SELECT id_banco, nombre FROM prm_banco ORDER BY orden", "id_banco", $id_banco, 'onchange="CargarCuenta(\'id_banco\',\'id_cuenta\');"', "Cualquiera", "150") ?>
			</td>
		</tr>
		<tr>
			<td align=right>
				<?php echo __('N° Cuenta') ?>
			</td>
			<td align=left colspan="3">
				<?php
				if (!empty($id_banco)) {
					$where_banco = " WHERE cuenta_banco.id_banco = '$id_banco' ";
				} else {
					$where_banco = " WHERE 1=2 ";
				}
				$query = "SELECT cuenta_banco.id_cuenta,
								CONCAT( cuenta_banco.numero,
								IF (prm_moneda.glosa_moneda IS NOT NULL , CONCAT(' (',prm_moneda.glosa_moneda,')'),  '' ) ) AS NUMERO
						   FROM cuenta_banco
						   LEFT JOIN prm_moneda ON prm_moneda.id_moneda = cuenta_banco.id_moneda $where_banco ";
				echo Html::SelectQuery($sesion, $query, "id_cuenta", $id_cuenta, 'onchange="SetBanco(\'id_cuenta\',\'id_banco\');"', "Cualquiera", "150")
				?>
			</td>
		</tr>
		<tr>
			<td colspan="4" align=center>
				<?php
				if ($pago->fields['id_factura_pago']) {
					?>
					<input id="btn_imprimir_voucher" type=button class=btn value="<?php echo __('Imprimir voucher') ?>" onclick="Imprimir_voucher(this.form, '<?php echo $pago->fields['id_factura_pago'] ?>');" />
					&nbsp;
				<?php } ?>
				<img src="<?php echo Conf::ImgDir() ?>/money_16.gif" border=0> <a href='javascript:void(0)' onclick="MostrarTipoCambioPago()" title="<?php echo __('Tipo de Cambio del Documento de Pago al ser pagado.') ?>"><?php echo __('Actualizar Tipo de Cambio') ?></a>
			</td>
		</tr>
		<tr>
			<td align=right colspan="4">
				&nbsp;
			</td>
		</tr>
		<tr>
			<td align=right colspan="4">
				<div id="TipoCambioDocumentoPago" style="display:none; left: 50px; top: 250px; background-color: white; position:absolute; z-index: 4;">
					<fieldset style="background-color:white;">
						<legend><?php echo __('Tipo de Cambio Documento de Pago') ?></legend>
						<div id="contenedor_tipo_load">&nbsp;</div>
						<div id="contenedor_tipo_cambio">
							<table style='border-collapse:collapse;' cellpadding='3'>
								<tr>
									<?php
									if ($pago->fields['id_factura_pago']) {
										$query = "SELECT count(*)
													FROM cta_cte_fact_mvto_moneda
													LEFT JOIN cta_cte_fact_mvto AS ccfm ON ccfm.id_cta_cte_mvto=cta_cte_fact_mvto_moneda.id_cta_cte_fact_mvto
													WHERE ccfm.id_factura_pago = '" . $pago->fields['id_factura_pago'] . "'";
										$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
										list($cont) = mysql_fetch_array($resp);
									}
									else
										$cont = 0;
									if ($cont > 0) {
										$query =
												"SELECT prm_moneda.id_moneda, glosa_moneda, cta_cte_fact_mvto_moneda.tipo_cambio
													FROM cta_cte_fact_mvto_moneda
													JOIN prm_moneda ON cta_cte_fact_mvto_moneda.id_moneda = prm_moneda.id_moneda
													LEFT JOIN cta_cte_fact_mvto ON cta_cte_fact_mvto.id_cta_cte_mvto = cta_cte_fact_mvto_moneda.id_cta_cte_fact_mvto
													WHERE cta_cte_fact_mvto.id_factura_pago = '" . $pago->fields['id_factura_pago'] . "'";
										$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
									} else {
										$query = "SELECT id_moneda, glosa_moneda, tipo_cambio FROM prm_moneda";
										$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
									}
									$num_monedas = 0;
									$ids_monedas = array();
									$tipo_cambios = array();
									while (list($id_moneda, $glosa_moneda, $tipo_cambio) = mysql_fetch_array($resp)) {
										?>
										<td>
											<span><b><?php echo $glosa_moneda ?></b></span><br>
											<input type='text' size=9 id='factura_pago_moneda_<?php echo $id_moneda ?>' name='factura_pago_moneda_<?php echo $id_moneda ?>' value='<?php echo $tipo_cambio ?>' />
										</td>
										<?php
										++$num_monedas;
										$ids_monedas[] = $id_moneda;
										$tipo_cambios[] = $tipo_cambio;
									}
									?>
								<tr>
									<td colspan=<?php echo $num_monedas ?> align=center>
										<input type=button onclick="ActualizarDocumentoMonedaPago($('todo_cobro'))" value="<?php echo __('Guardar') ?>" />
										<input type=button onclick="CancelarDocumentoMonedaPago()" value="<?php echo __('Cancelar') ?>" />
										<input type=hidden id="tipo_cambios_factura_pago" name="tipo_cambios_factura_pago" value="<?php echo implode(',', $tipo_cambios) ?>" />
										<input type=hidden id="ids_monedas_factura_pago" name="ids_monedas_factura_pago" value="<?php echo implode(',', $ids_monedas) ?>" />
									</td>
								</tr>
							</table>
						</div>
					</fieldset>

				</div>
			</td>
		</tr>
	</table>

	<br>
	<table style="border: 0px solid black;" width='90%'>
		<tr>
			<td align=left>
				<input type=button class=btn id="boton_guardar" value="<?php echo __('Guardar') ?>" onclick='Guardar(this.form);' />
				<input type=button class=btn value="<?php echo __('Cerrar') ?>" onclick="Cerrar();" />
			</td>
		</tr>
	</table>
	<?php
	$x_pag = 15;
	$b = new Buscador($sesion, $query__listado, "Objeto", 0, 0, "orden ASC, orden2 ASC");
	$b->mensaje_error_fecha = "N/A";
	$b->nombre = "busc_facturas";
	$b->titulo = __('Listado de') . ' ' . __('documentos legales');
	$b->AgregarEncabezado("id_cobro", __('N° Cobro'), "align=center");
	$b->AgregarEncabezado("numero", __('N° Documento'), "align=center");
	$b->AgregarEncabezado("glosa_documento_legal", __('Tipo Documento'), "align=center");
	$b->AgregarEncabezado("simbolo", __('Moneda'), "align=center");
	$b->AgregarEncabezado("saldo_factura", __('Saldo por pagar'), "align=center");
	$b->AgregarFuncion("Pagar", 'Opciones', "align=center nowrap");
	$b->color_mouse_over = "#bcff5c";

	$b->Imprimir("", array(), false);

	function Opciones(& $fila) {
		global $lista_facturas;
		global $saldo_pago;
		global $id_cobro;
		global $id_adelanto;

		$arreglo_facturas = explode(',', $lista_facturas);

		if (abs($fila->fields['saldo_factura']) < 0.000001)
			$fila->fields['saldo_factura'] = 0;

		if ($fila->fields['monto_pago'] > 0) {
			$monto_a_pagar = $fila->fields['monto_pago'];
		} else if (in_array($fila->fields['id_factura'], $arreglo_facturas)) {
			$monto_a_pagar = $saldo_pago === null ? $fila->fields['saldo_factura'] : min($fila->fields['saldo_factura'], $saldo_pago);
		}
		else
			$monto_a_pagar = "0";

		if ($saldo_pago !== null) {
			$saldo_pago -= $monto_a_pagar;
		}

		$readonly = ($id_adelanto && ($id_cobro != $fila->fields['id_cobro'])) ? "readonly='readonly'" : '';
		$opc_html = $fila->fields['simbolo'] . "&nbsp;<input $readonly type=\"text\" size=7 class=\"saldojq\" id=\"saldo_" . $fila->fields['id_factura'] . "\" name=\"saldo_" . $fila->fields['id_factura'] . "\" value=\"" . $monto_a_pagar . "\" onkeyup=\"MontoValido( this.id );ActualizarMonto();\"/>";
		$opc_html .= "<input type=hidden name=\"x_saldo_hide_" . $fila->fields['id_factura'] . "\" id=\"x_saldo_hide_" . $fila->fields['id_factura'] . "\" value=\"" . $fila->fields['saldo_factura'] . "\" />";
		$opc_html .= "<input type=hidden name=\"tipo_cambio_" . $fila->fields['id_factura'] . "\" id=\"tipo_cambio_" . $fila->fields['id_factura'] . "\" value=\"" . $fila->fields['tipo_cambio'] . "\" />";
		$opc_html .= "<input type=hidden name=\"cifras_decimales_" . $fila->fields['id_factura'] . "\" id=\"cifras_decimales_" . $fila->fields['id_factura'] . "\" value=\"" . $fila->fields['cifras_decimales'] . "\" />";
		return $opc_html;
	}
	?>
</form>


<script type="text/javascript">
<?php if (!empty($id_adelanto)) { ?>
		$('tabla_informacion').select('input, select, textarea').each(function(elem) {
			elem.disabled = 'disabled';
		});
	<?php
	if ($pago->fields['id_factura_pago']) {
		?>
			$('btn_imprimir_voucher').disabled = false;
		<?php
	}
}
?>

	Calendar.setup(
			{
				inputField: "fecha", // ID of the input field
				ifFormat: "%d-%m-%Y", // the date format
				button: "img_fecha"		// ID of the button
			}
	);
	Calendar.setup(
			{
				inputField: "fecha_pago", // ID of the input field
				ifFormat: "%d-%m-%Y", // the date format
				button: "img_fecha_pago"		// ID of the button
			}
	);
</script>
<?php
if (Conf::GetConf($sesion, 'TipoSelectCliente') == 'autocompletador') {
	echo Autocompletador::Javascript($sesion, false);
}
echo InputId::Javascript($sesion);
$pagina->PrintBottom($popup);
?>
