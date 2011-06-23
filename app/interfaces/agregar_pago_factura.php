<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
	require_once Conf::ServerDir().'/classes/Trabajo.php';
	require_once Conf::ServerDir().'/classes/Funciones.php';
	require_once Conf::ServerDir().'/classes/Documento.php';
	require_once Conf::ServerDir().'/classes/Cobro.php';
	require_once Conf::ServerDir().'/classes/NeteoDocumento.php';
	require_once Conf::ServerDir().'/classes/Moneda.php';
	require_once Conf::ServerDir().'/classes/Observacion.php';
	require_once Conf::ServerDir().'/classes/Autocompletador.php';
	require_once Conf::ServerDir().'/classes/FacturaPago.php';
	require_once Conf::ServerDir().'/classes/CtaCteFact.php';
	require_once Conf::ServerDir().'/classes/CtaCteFactMvto.php';
	require_once Conf::ServerDir().'/classes/UtilesApp.php';
	require_once Conf::ServerDir().'/classes/Cliente.php';

	$sesion = new Sesion(array('COB'));
	$pagina = new Pagina($sesion);
	$id_usuario = $sesion->usuario->fields['id_usuario'];
	
	$documento = new Documento($sesion);
	$cobro = new Cobro($sesion);
	if($id_cobro){
		$cobro->Load($id_cobro);
		$id_moneda_cobro = $cobro->fields['opc_moneda_total'];
	}

	$pago = new FacturaPago($sesion);
	if(isset($_GET['id_factura_pago'])) { $id_factura_pago = $_GET['id_factura_pago'];}
	if(!empty($id_factura_pago))
	{
		$pago->Load($id_factura_pago);
		$id_moneda = $pago->fields['id_moneda'];
		$id_moneda_cobro = $pago->fields['id_moneda_cobro'];
		$lista_facturas = $pago->GetListaFacturasSoyPago($id_factura_pago);
		$codigo_cliente = $pago->fields['codigo_cliente'];
	}
	
	$moneda_pago = new Moneda($sesion);
	$moneda_pago->Load($id_moneda);
	
	$moneda_cobro = new Moneda($sesion);
	$moneda_cobro->Load($id_moneda_cobro);

	if($opcion == 'imprimir_voucher')
	{
		include dirname(__FILE__).'/factura_pago_doc.php';
		exit;
	}
	
	if($opcion == 'guardar'){

		if(!empty($id_factura_pago))
		{
			$pago->Edit('id_factura_pago',$id_factura_pago);
		}
		$pago->Edit('fecha', Utiles::fecha2sql($fecha));
		//$pago->Edit('codigo_cliente', $codigo_cliente);
		$pago->Edit('codigo_cliente', $codigo_cliente_factura);
		$pago->Edit('monto', $monto);
		$pago->Edit('id_moneda', $id_moneda);
		$pago->Edit('monto_moneda_cobro', $monto_moneda_cobro);
		$pago->Edit('id_moneda_cobro', $id_moneda_cobro);
		$pago->Edit('tipo_doc', $tipo_doc);
		$pago->Edit('nro_documento', $numero_doc);
		$pago->Edit('nro_cheque', $numero_cheque);
		$pago->Edit('descripcion', $glosa_documento);
		$pago->Edit('id_banco', $id_banco);
		$pago->Edit('id_cuenta', $id_cuenta);
		$pago->Edit('pago_retencion', $pago_retencion);
		$pago->Edit('id_concepto', $id_concepto);

		if($pago->Write()){
			$cta_cte_fact = new CtaCteFact($sesion);
			$neteos = array();
			foreach($_POST as $nombre_variable => $valor){
				if(strpos($nombre_variable, 'saldo_') === 0){
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

			
			?>
			<script type="text/javascript">
				window.opener.Refrescar();
			</script>
			<?

			
		}
	}

	$mvto_pago = new CtaCteFactMvto($sesion);
	$mvto_pago->LoadByPago($id_factura_pago);

	$imprimir_voucher=false;
	$pagina->PrintTop($popup);
	
	if( $lista_facturas ) 
		$definir_orden = " IF(f.id_factura IN ($lista_facturas), '1', '2') AS orden ";
	else
		$definir_orden = " '1' AS orden ";

	$where_lista_cobro = "";
	if( $id_cobro )
	{
		$definir_orden2 = " IF(f.id_cobro = '$id_cobro', '1', '2') AS orden2 ";
		$where_lista_cobro = " OR f.id_cobro = '$id_cobro' ";
	}
	else
		$definir_orden2 = " '1' AS orden2 ";

	$query__listado = "SELECT SQL_CALC_FOUND_ROWS
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
										AND ccfmn.id_mvto_pago = '".$mvto_pago->fields['id_cta_cte_mvto']."' 
							LEFT JOIN factura AS f ON ccfm.id_factura = f.id_factura 
							JOIN prm_moneda AS pm ON f.id_moneda = pm.id_moneda 
							LEFT JOIN prm_documento_legal AS pdl ON pdl.id_documento_legal = f.id_documento_legal";
	$query__listado .="	WHERE (f.codigo_cliente = '$codigo_cliente'";
	$query__listado .=  $where_lista_cobro;
	$query__listado .=" ) AND f.id_moneda = '$id_moneda_cobro' AND f.anulado = 0 AND ccfm.saldo != 0 OR ccfmn.monto != 0";
	
	
?>

<script type="text/javascript">
	function ShowCheque()
	{
		if( $('tipo_doc').value == "C" )
			$('tr_cheque').style.display = "table-row";
		else
			$('tr_cheque').style.display = "none";
	}
	
	function Cerrar()
	{
		window.opener.Refrescar();
		window.close();
	}
	
	function MostrarTipoCambioPago()
	{
		$('TipoCambioDocumentoPago').show();
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
		for(var i = 0; i<arreglo_ids.length-1; i++)
			$('tipo_cambios_factura_pago').value += $('factura_pago_moneda_'+arreglo_ids[i]).value + ",";
		i=arreglo_ids.length-1;
		$('tipo_cambios_factura_pago').value += $('factura_pago_moneda_'+arreglo_ids[i]).value;
		if( $('id_factura_pago') != '' )
			{
				var tc = new Array();
				for(var i = 0; i< arreglo_ids.length; i++)
						tc[i] = $('factura_pago_moneda_'+arreglo_ids[i]).value;
				$('contenedor_tipo_load').innerHTML = 
				"<table width=510px><tr><td align=center><br><br><img src='<?=Conf::ImgDir()?>/ajax_loader.gif'/><br><br></td></tr></table>";
				var http = getXMLHTTP();
				var url = root_dir + '/app/interfaces/ajax.php?accion=actualizar_factura_pago_moneda&id_factura=<?=$factura->fields['id_factura']?>&ids_monedas=' + ids_monedas+'&tcs='+tc.join(',');	
				http.open('get', url);
				http.onreadystatechange = function()
				{
					if(http.readyState == 4)
					{
						var response = http.responseText;
						if(response == 'EXITO')
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
		var lista_facturas = $('lista_facturas').value;
		var arreglo_facturas = lista_facturas.split(',');

		var cifras_decimales = $('cifras_decimales_pago').value;
		var monto = Number(0);
		for(var i = 0; i<=arreglo_facturas.length-1; i++)
		{
			$$('[id^="saldo_"]').each(function(elem){
				ids = elem.id.split('_');
				if(ids[1]==arreglo_facturas[i])
				{
					var saldo_fact = Number($('x_saldo_hide_'+ids[1]).value);
					if(Number(elem.value) > saldo_fact) elem.value = saldo_fact;
					monto += Number(Redondear(elem.value, cifras_decimales));
				}
			});
		}
		$('monto_moneda_cobro').value = Redondear(monto, cifras_decimales);
		if($F('id_moneda') == '<?=$id_moneda_cobro?>'){
			$('monto').value = $('monto_moneda_cobro').value;
		}
	}

	function Redondear(num, decimales){
		return Number(num).toFixed(decimales);
	}

	var suma_saldo = 0;
	var monto_tmp = 0;
	function ActualizarMontoMonedaCobro(){
		var moneda = $('id_moneda').value;
		if(moneda == '<?=$id_moneda_cobro?>'){
			$('span_monto_equivalente').style.visibility = 'hidden';
			$('monto_moneda_cobro').value = $('monto').value;
		}
		else{
			$('span_monto_equivalente').style.visibility = 'visible';
			$('monto_moneda_cobro').value = Redondear($('monto').value * $('factura_pago_moneda_'+moneda).value / $('factura_pago_moneda_<?=$id_moneda_cobro?>').value, $('cifras_decimales_pago').value);
		}
		ActualizarMontosIndividuales('monto_moneda_cobro');

		if(monto_tmp > 0 && !confirm('<?=__("El monto ingresado excede el saldo a pagar")?> ('+
			suma_saldo + ')\n<?=__("¿Está seguro que desea continuar?")?>')){
			continuar = 0;
			$('monto').value = suma_saldo;
		}

	}

	function CargarCuenta( origen, destino ) 
	{
		var http = getXMLHTTP();
		var url = 'ajax.php?accion=cargar_cuentas&id=' + $(origen).value;

    loading("Actualizando campo");
    http.open('get', url);
    http.onreadystatechange = function()
    {
       if(http.readyState == 4)
       {
          var response = http.responseText;
          if( response == "~noexiste" )
          	alert( "Ústed no tiene cuentas en este banco." );
          else
          	{
          		$(destino).options.length = 0;
          		cuentas = response.split('//');
          		
          		for(var i=0;i<cuentas.length;i++)
          		{
          			valores = cuentas[i].split('|');
          			
          			var option = new Option();
          			option.value = valores[0];
          			option.text = valores[1];
          			
          			try
          			{
          				$(destino).add(option);
          			}
          			catch(err)
          			{
          				$(destino).add(option,null);
          			}
          		}
          	}
					offLoading();
       }
    };
    http.send(null);
	}
	
	function SetBanco( origen, destino )
	{
		var http = getXMLHTTP();
    var url = 'ajax.php?accion=buscar_banco&id=' + $(origen).value;

    loading("Actualizando campo");
    http.open('get', url);
    http.onreadystatechange = function()
    {
       if(http.readyState == 4)
       {
          var response = http.responseText;
					$(destino).value = response;
          offLoading();
       }
    };
    http.send(null);
	}
	
	function ActualizarMontosIndividuales( id )
	{
		suma_saldo=0;
		monto_tmp=0;
		var cifras_decimales = $('cifras_decimales_pago').value;
		var lista_facturas = $('lista_facturas').value;
		var arreglo_facturas = lista_facturas.split(',');
		var monto = $(id).value;
		for(var i = 0; i<=arreglo_facturas.length-1; i++)
		{
			$$('[id^="saldo_"]').each(function(elem){
				ids = elem.id.split('_');
				if(ids[1]==arreglo_facturas[i]) {
					var saldo_individual = Math.max(Math.min($('x_saldo_hide_'+ids[1]).value,monto),0);
					elem.value = Redondear(saldo_individual, cifras_decimales);
					monto -= saldo_individual;
					suma_saldo += saldo_individual;
				}
			});
		}
		monto_tmp = monto;
	}

	function Imprimir_voucher(form,id_factura_pago)
	{
		form.opcion.value = 'imprimir_voucher';
		form.action = "agregar_pago_factura.php?$id_factura_pago="+id_factura_pago+"&popup=1";
		form.submit();
		return true;
	}

	function CalculaPagoIva()
	{
		var cifras_decimales = $('cifras_decimales_pago').value;
		var lista_facturas = $('lista_facturas').value;
		arreglo_facturas = lista_facturas.split(',');
		var porcentaje_impuesto_retencion = 12;
		for(var i = 0; i<=arreglo_facturas.length-1; i++)
		{
			var monto_pagos = $('x_saldo_hide_'+arreglo_facturas[i]).value;
			var monto = document.getElementById('monto');

			if( $('pago_retencion').checked )
			{
				if($('pago_retencion_monto_loaded').value!='false'){
					monto_retencion_impuestos = $('pago_retencion_monto_loaded').value;
				}
				else{
					monto_retencion_impuestos = monto_pagos*porcentaje_impuesto_retencion;
					monto_retencion_impuestos = (monto_retencion_impuestos.round())/100;
					monto_retencion_impuestos = monto_retencion_impuestos.toFixed(cifras_decimales);
				}
				$('saldo_'+arreglo_facturas[i]).value = monto_retencion_impuestos;
			}
			else
			{
				if($('pago_retencion_monto_loaded').value!='false'){
					monto_retencion_impuestos = monto_pagos*100;
					monto_retencion_impuestos = (monto_retencion_impuestos.round())/porcentaje_impuesto_retencion;
					monto_retencion_impuestos = monto_retencion_impuestos.toFixed(cifras_decimales);
					$('saldo_'+arreglo_facturas[i]).value = monto_retencion_impuestos;
				}
				else{
					$('saldo_'+arreglo_facturas[i]).value = monto_pagos;
				}
			}
		}
		ActualizarMonto();
	}
	var continuar = 1;
	function Guardar(form)
	{
		continuar = 1;
		ValidaMontoSaldoPago(form);

		if(continuar==0){
			return false;
		}
		else {
			if($('id_factura_pago').value)
			{
				var id_factura_pago = $('id_factura_pago').value;
				form.action = "agregar_pago_factura.php?id_factura_pago="+id_factura_pago+'&popup=1';
			}
			else
			{
				form.action = "agregar_pago_factura.php?popup=1";
			}
			form.opcion.value = 'guardar';
			form.submit();
			return Validar(form);
			
		}
	}

	function ValidaMontoSaldoPago(form)
	{
		var cifras_decimales = $('cifras_decimales_pago').value;
		var monto = $('monto_moneda_cobro').value;
		$$('[id^="saldo_"]').each(function(elem){
			ids = elem.id.split('_');
			var saldo_individual = Math.max(Math.min($('x_saldo_hide_'+ids[1]).value,monto),0);
			monto -= Number(Redondear(saldo_individual, cifras_decimales));
		});
		monto = Number(Redondear(monto, cifras_decimales));
		if(monto > 0 && !confirm('<?=__("El monto ingresado excede el saldo a pagar")?> ('+
			Redondear(($('monto_moneda_cobro').value - monto), cifras_decimales)+')\n<?=__("¿Está seguro que desea continuar?")?>')){
			continuar = 0;
		}
	}
</script>

<? echo Autocompletador::CSS(); ?>
<form method=post action="" id="form_documentos" autocomplete='off'>
<input type=hidden name=opcion value="guardar" />
<input type=hidden name='id_doc_cobro' id='id_doc_cobro' value='<?=$id_doc_cobro?>' />
<input type=hidden name='lista_facturas' id='lista_facturas' value='<?=$lista_facturas?>' />
<input type=hidden name='id_factura_pago' id='id_factura_pago' value='<?=$pago->fields['id_factura_pago'];?>' />
<input type=hidden name='cifras_decimales_pago' id='cifras_decimales_pago' value="<?=$moneda_cobro->fields['cifras_decimales']?>" />
<input type=hidden name='id_factura_pago' id='id_factura_pago' value="<?=$pago->fields['id_factura_pago']?>" />
<input type="hidden" name="pago_retencion_monto_loaded" id="pago_retencion_monto_loaded" value="<?=$pago->fields['pago_retencion'] ? $pago->fields['monto'] : 'false' ?>" />
<input type="hidden" name="codigo_cliente_factura" value="<?= $pago->fields['codigo_cliente'] ? $pago->fields['codigo_cliente'] : $codigo_cliente?>" >
<!-- Calendario DIV -->
<div id="calendar-container" style="width:221px; position:absolute; display:none;">
	<div class="floating" id="calendar"></div>
</div>
<!-- Fin calendario DIV -->
<br>
<table width='90%'>
	<tr>
		<td align=left><b><?=$txt_pagina ?></b></td>
	</tr>
</table>
<br>

<table style="border: 0px solid black;" width='90%'>
	<tr>
		<td align=left width="50%">
			<b><?=__('Información de Pago') ?> </b>
		</td>
<?
	$query = "SELECT count(*) FROM documento WHERE pago_retencion = 1 AND id_cobro = '$id_cobro'";
	$resp	 = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	list( $existe_pago_retencion ) = mysql_fetch_array($resp);
	
	if( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'PagoRetencionImpuesto') )
		{ ?>
		<td align=right width="50%">
			<input type="checkbox" name="pago_retencion" id="pago_retencion" onchange="CalculaPagoIva();" value=1 <?=$pago->fields['pago_retencion'] ? "checked='checked'" : "" ?> />&nbsp;<?=__('Pago retención impuestos')?>
		</td>
<?	}
	else
		{ ?>
		<td align=right width="50%">
			&nbsp;
		</td>
<?	}
		?>
	</tr>
</table>
<table style="border: 1px solid black;" width='90%'>
	<tr>
		<td align=right width="30%">
			<?=__('Fecha')?>
		</td>
		<td align=left colspan="3">
			<input type="text" name="fecha" value="<?=$pago->fields['fecha'] ? Utiles::sql2date($pago->fields['fecha']) : date('d-m-Y') ?>" id="fecha" size="11" maxlength="10" />
			<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha" style="cursor:pointer" />
		</td>
	</tr>
	<tr>
		<td align="right" width="30%"><?=__('Cliente ')?></td>
		<td colspan="3" align="left">
			<?
			if(UtilesApp::GetConf($sesion,'CodigoSecundario')){
				$cliente = new Cliente($sesion);
				$codigo_cliente_secundario = $cliente->CodigoACodigoSecundario( $codigo_cliente );
			}
			if(UtilesApp::GetConf($sesion,'TipoSelectCliente')=='autocompletador')
				{
					if(UtilesApp::GetConf($sesion,'CodigoSecundario') )
						echo Autocompletador::ImprimirSelector($sesion, '', $codigo_cliente_secundario, '', 280, "CargarTabla(1);");
					else
						echo Autocompletador::ImprimirSelector($sesion, $pago->fields['codigo_cliente'] ? $pago->fields['codigo_cliente'] : $codigo_cliente, '', '', 280, "CargarTabla(1);");
				}
			else
				{
				if(UtilesApp::GetConf($sesion,'CodigoSecundario') )
						{
							echo InputId::ImprimirSinCualquiera($sesion,"cliente","codigo_cliente_secundario","glosa_cliente","codigo_cliente_secundario", $codigo_cliente_secundario,"","", 280);
						}
						else
						{
							echo InputId::ImprimirSinCualquiera($sesion,"cliente","codigo_cliente","glosa_cliente", "codigo_cliente", $pago->fields['codigo_cliente'] ? $pago->fields['codigo_cliente'] : $codigo_cliente," disabled ","CargarTabla(1);", 280);
						}
				} ?>
		</td>
	</tr>

		<tr>
			<td align=right>
				<?=__('Monto')?>
			</td>
			<td align=left colspan="3">
				<?php $monto_pago=str_replace(',','.',$monto_pago);?>
				<input name=monto id=monto size=10 onchange="ActualizarMontoMonedaCobro();" value="<?=number_format($pago->fields['monto'] ? $pago->fields['monto'] : $monto_pago,$moneda_pago->fields['cifras_decimales'],'.','')?>" />

				<?= Html::SelectQuery($sesion, "SELECT id_moneda,glosa_moneda FROM prm_moneda ORDER BY id_moneda","id_moneda", $id_moneda, 'onchange="ActualizarMontoMonedaCobro()"','',"80"); ?>
				<span style="color:#FF0000; font-size:10px">*</span>

				<span id="span_monto_equivalente" style="visibility:<?=$id_moneda_cobro==$id_moneda ? 'hidden' : 'visible'?>">
					Equivalente a <?=$moneda_cobro->fields['simbolo']?>
					<input id="monto_moneda_cobro" name="monto_moneda_cobro" onchange="ActualizarMontosIndividuales( this.id );" value="<?=number_format($pago->fields['monto_moneda_cobro'] ? $pago->fields['monto_moneda_cobro'] : $monto_pago,$moneda_cobro->fields['cifras_decimales'],'.','')?>" />
					<input type="hidden" id="id_moneda_cobro" name="id_moneda_cobro" value="<?=$id_moneda_cobro?>" />
				</span>
			</td>
		</tr>
		<tr>
			<td align=right>
				<?=__('Concepto')?>
			</td>
			<td align=left colspan="3">
				<?= Html::SelectQuery($sesion, "SELECT id_concepto,glosa FROM prm_factura_pago_concepto ORDER BY orden","id_concepto", $pago->fields['id_concepto'], '','',"168"); ?>
			</td>
		</tr>
		<tr>
			<td align=right>
				<?=__('Tipo:')?>
			</td>
			<td align=left>
				<select name='tipo_doc' id='tipo_doc'  style='width: 100px;' onchange="ShowCheque();">
					<?php
						$tipos = array(
							'T'=>'Transferencia',
							'E'=>'Efectivo',
							'C'=>'Cheque',
							'O'=>'Otro');
						$cod_tipo = $pago->fields['tipo_doc'];
						if(!in_array($cod_tipo, array('E', 'C', 'O'))) $cod_tipo = 'T';
						foreach($tipos as $k => $v){?>
							<option value="<?=$k?>" <?=$k==$cod_tipo?'selected':''?>><?=$v?></option>
						<?}
					?>
				</select>

				<?=__('N° Documento:')?>
				<input name=numero_doc size=10 value="<? echo str_replace("-","",$pago->fields['nro_documento']);  ?>" />
			</td>
		</tr>
		<tr id="tr_cheque" style="display:none;">
			<td align=right width="30%">
				<?=__('N° Cheque')?>
			</td>
			<td align="left" colspan="3" width="70%">
				<input name=numero_cheque id=numero_cheque size=10 value="<? echo $documento->fields['nro_cheque'];  ?>" />
			</td>
		</tr>
	<tr>
		<td align=right>
			<?=__('Descripción')?>
		</td>
		<td align=left colspan="3">
			<textarea name=glosa_documento cols="45" rows="3"><?
				if($pago->fields['descripcion'])
					echo $pago->fields['descripcion'];
				else if($id_cobro) {
					echo "Pago de Factura # ".$lista_facturas;
				}
			?></textarea>
		</td>
	</tr>
	<?php
	if($pago->fields['id_cuenta']){
		$id_banco = $pago->fields['id_banco'];
		$id_cuenta = $pago->fields['id_cuenta'];
	}
	?>
	<tr>
		<td align=right>
			<?=__('Banco')?>
		</td>
		<td align=left colspan="3">
			<?=Html::SelectQuery($sesion,"SELECT id_banco, nombre FROM prm_banco ORDER BY orden", "id_banco", $pago->fields['id_banco'] ? $pago->fields['id_banco'] : $id_banco, 'onchange="CargarCuenta(\'id_banco\',\'id_cuenta\');"',"Cualquiera","150")?>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('N° Cuenta')?>
		</td>
		<td align=left colspan="3">
			<?=Html::SelectQuery($sesion,"SELECT cuenta_banco.id_cuenta
, CONCAT( cuenta_banco.numero,
     IF( prm_moneda.glosa_moneda IS NOT NULL , CONCAT(' (',prm_moneda.glosa_moneda,')'),  '' ) ) AS NUMERO
FROM cuenta_banco
LEFT JOIN prm_moneda ON prm_moneda.id_moneda = cuenta_banco.id_moneda", "id_cuenta", $pago->fields['id_cuenta'] ? $pago->fields['id_cuenta'] : $id_cuenta, 'onchange="SetBanco(\'id_cuenta\',\'id_banco\');"',"Cualquiera","150")?>
		</td>
	</tr>
	<tr>
		<td colspan="4" align=center>
			<?php
			if( $pago->fields['id_factura_pago'] ){
			?>
			<input type=button class=btn value="<?=__('Imprimir voucher')?>" onclick="Imprimir_voucher(this.form,'<?=$pago->fields['id_factura_pago']?>');" />
			&nbsp;
			<?php } ?>
			<img src="<?=Conf::ImgDir()?>/money_16.gif" border=0> <a href='javascript:void(0)' onclick="MostrarTipoCambioPago()" title="<?=__('Tipo de Cambio del Documento de Pago al ser pagado.')?>"><?=__('Actualizar Tipo de Cambio')?></a>
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
				<legend><?=__('Tipo de Cambio Documento de Pago')?></legend>
				<div id="contenedor_tipo_load">&nbsp;</div>
				<div id="contenedor_tipo_cambio">
				<table style='border-collapse:collapse;' cellpadding='3'>
					<tr>
						<?
						if( $pago->fields['id_factura_pago'] )
							{
								$query = "SELECT count(*) 
													FROM cta_cte_fact_mvto_moneda 
													LEFT JOIN cta_cte_fact_mvto AS ccfm ON ccfm.id_cta_cte_mvto=cta_cte_fact_mvto_moneda.id_cta_cte_fact_mvto
													WHERE ccfm.id_factura_pago = '".$pago->fields['id_factura_pago']."'";
								$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
								list($cont) = mysql_fetch_array($resp);
							}
						else
							$cont = 0;
						if( $cont > 0 )
							{
								$query = 
								"SELECT prm_moneda.id_moneda, glosa_moneda, cta_cte_fact_mvto_moneda.tipo_cambio 
									FROM cta_cte_fact_mvto_moneda 
									JOIN prm_moneda ON cta_cte_fact_mvto_moneda.id_moneda = prm_moneda.id_moneda 
									LEFT JOIN cta_cte_fact_mvto ON cta_cte_fact_mvto.id_cta_cte_mvto = cta_cte_fact_mvto_moneda.id_cta_cte_fact_mvto
									WHERE cta_cte_fact_mvto.id_factura_pago = '".$pago->fields['id_factura_pago']."'";
								$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__, $sesion->dbh);
							}
						else
							{ 
								$query = "SELECT id_moneda, glosa_moneda, tipo_cambio FROM prm_moneda";
								$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__, $sesion->dbh);
							}
						$num_monedas=0; $ids_monedas = array(); $tipo_cambios = array();
						while(list($id_moneda,$glosa_moneda,$tipo_cambio) = mysql_fetch_array($resp))
						{
						?>
							<td>
									<span><b><?=$glosa_moneda?></b></span><br>
									<input type='text' size=9 id='factura_pago_moneda_<?=$id_moneda?>' name='factura_pago_moneda_<?=$id_moneda?>' value='<?=$tipo_cambio?>' />
							</td>
						<?
							$num_monedas++;
							$ids_monedas[] = $id_moneda;
							$tipo_cambios[] = $tipo_cambio;
						}
						?>
					<tr>
						<td colspan=<?=$num_monedas?> align=center>
							<input type=button onclick="ActualizarDocumentoMonedaPago($('todo_cobro'))" value="<?=__('Guardar')?>" />
							<input type=button onclick="CancelarDocumentoMonedaPago()" value="<?=__('Cancelar')?>" />
							<input type=hidden id="tipo_cambios_factura_pago" name="tipo_cambios_factura_pago" value="<?=implode(',',$tipo_cambios)?>" />
							<input type=hidden id="ids_monedas_factura_pago" name="ids_monedas_factura_pago" value="<?=implode(',',$ids_monedas)?>" />
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
			<input type=button class=btn value="<?=__('Guardar')?>" onclick='Guardar(this.form);' />
			<input type=button class=btn value="<?=__('Cerrar')?>" onclick="Cerrar();" />
		</td>
	</tr>
</table>
<?
	$x_pag = 15;
	$b = new Buscador($sesion, $query__listado, "Objeto", 0, 0, "orden ASC, orden2 ASC");
	$b->mensaje_error_fecha = "N/A";
	$b->nombre = "busc_facturas";
	$b->titulo = __('Listado de').' '.__('documentos legales');
	$b->titulo .= "<table width=100%>";
	$b->AgregarEncabezado("numero",__('N° Documento'),"align=center");
	$b->AgregarEncabezado("glosa_documento_legal",__('Tipo Documento'),"align=center");
	$b->AgregarEncabezado("simbolo",__('Moneda'),"align=center");
	$b->AgregarEncabezado("saldo_factura",__('Saldo por pagar'),"align=center");
	$b->AgregarFuncion("Pagar",'Opciones',"align=center nowrap");
	$b->color_mouse_over = "#bcff5c";

	$b->Imprimir("",array(),false);
	function Opciones(& $fila)
	{
		global $lista_facturas;
		
		$arreglo_facturas = explode(',',$lista_facturas);
		
		if(abs($fila->fields['saldo_factura'])<0.000001) 
			$fila->fields['saldo_factura'] = 0;
		
		if( $fila->fields['monto_pago'] > 0 )
			$monto_a_pagar = $fila->fields['monto_pago'];
		else if( in_array($fila->fields['id_factura'],$arreglo_facturas) )
			$monto_a_pagar = $fila->fields['saldo_factura'];
		else
			$monto_a_pagar = "0";
			
		$opc_html = $fila->fields['simbolo']."&nbsp;<input type=\"text\" size=7 id=\"saldo_".$fila->fields['id_factura']."\" name=\"saldo_".$fila->fields['id_factura']."\" onchange=\"ActualizarMonto();\" value=\"".$monto_a_pagar."\" />";
		$opc_html .= "<input type=hidden name=\"x_saldo_hide_".$fila->fields['id_factura']."\" id=\"x_saldo_hide_".$fila->fields['id_factura']."\" value=\"".$fila->fields['saldo_factura']."\" />";
		$opc_html .= "<input type=hidden name=\"tipo_cambio_".$fila->fields['id_factura']."\" id=\"tipo_cambio_".$fila->fields['id_factura']."\" value=\"".$fila->fields['tipo_cambio']."\" />";
		$opc_html .= "<input type=hidden name=\"cifras_decimales_".$fila->fields['id_factura']."\" id=\"cifras_decimales_".$fila->fields['id_factura']."\" value=\"".$fila->fields['cifras_decimales']."\" />";
		return $opc_html;
	}
?>
</form>


<script type="text/javascript">

Calendar.setup(
	{
		inputField	: "fecha",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_fecha"		// ID of the button
	}
);
Calendar.setup(
	{
		inputField	: "fecha_pago",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_fecha_pago"		// ID of the button
	}
);
</script>
<?
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
	{
		echo Autocompletador::Javascript($sesion,false);
	}
	echo InputId::Javascript($sesion);
	$pagina->PrintBottom($popup);
?>
