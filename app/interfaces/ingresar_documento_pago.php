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
	require_once Conf::ServerDir().'/classes/UtilesApp.php';
	require_once Conf::ServerDir().'/classes/Observacion.php';
	require_once Conf::ServerDir().'/classes/Autocompletador.php';

	$sesion = new Sesion(array('COB'));
	$pagina = new Pagina($sesion);
	$id_usuario = $sesion->usuario->fields['id_usuario'];
	
	$documento = new Documento($sesion);
	$cobro = new Cobro($sesion);
	if($id_cobro)
		$cobro->Load($id_cobro);
	
	$documento_cobro = new Documento($sesion);
	$documento_cobro->LoadByCobro($id_cobro);
	$id_doc_cobro = $documento_cobro->fields['id_documento'];
	$moneda_documento = new Moneda($sesion);
	$moneda_documento->Load($documento_cobro->fields['id_moneda']);
	$cifras_decimales = $moneda_documento->fields['cifras_decimales'];
	
	$cambios_en_saldo_honorarios = array();
	$cambios_en_saldo_gastos = array();
	
	if($id_documento)
	{
		$documento->Load($id_documento);
	}
	
	if($opcion == "guardar")
	{
		// Construir arreglo_pagos_detalle
		$datos_neteo = array();
		foreach($_POST as $key => $val)
		{
			$pedazos = array_reverse(explode('_',$key));
			if( is_numeric($pedazos[0]) && in_array($pedazos[1],array('honorarios','gastos')) && $pedazos[2]=="pago" )
				{
					if( !is_array($datos_neteo[$pedazos[0]]) )
						$datos_neteo[$pedazos[0]] = array();
					$datos_neteo[$pedazos[0]][$pedazos[2].'_'.$pedazos[1]] = $val;
				}
		}
		$arreglo_pagos_detalle = array();
		foreach($datos_neteo as $llave => $valor)
		{
			if( $valor['pago_honorarios'] > 0 || $valor['pago_gastos'] > 0 )
			{
				$arreglo_data = array();
				$query = "SELECT id_cobro, id_moneda FROM documento WHERE id_documento = '".$llave."'";
				$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
				list($id_cobro,$id_moneda) = mysql_fetch_array($resp);
				
				$arreglo_data['id_moneda'] 					= $id_moneda;
				$arreglo_data['id_documento_cobro'] = $llave;
				$arreglo_data['monto_honorarios']		= $valor['pago_honorarios'];
				$arreglo_data['monto_gastos']				= $valor['pago_gastos'];
				$arreglo_data['id_cobro'] 					= $id_cobro;
				array_push($arreglo_pagos_detalle,$arreglo_data);
			}
		}

		$id_documento = $documento->IngresoDocumentoPago($pagina, $id_cobro, empty($codigo_cliente) ? $codigo_cliente_secundario : $codigo_cliente, $monto, $id_moneda, $tipo_doc, $numero_doc, $fecha, $glosa_documento, $id_banco, $id_cuenta, $numero_operacion, $numero_cheque, $ids_monedas_documento, $tipo_cambios_documento, $arreglo_pagos_detalle, null, $adelanto, $pago_honorarios, $pago_gastos, $id_documento && !$adelanto && $documento->fields['es_adelanto']);
		
		$documento->Load($id_documento);
		$monto_neteos =  $documento->fields['saldo_pago']-$documento->fields['monto'];
		$monto_pago = -1*$documento->fields['monto'];
	}

	if($documento->Loaded())
	{
		$codigo_cliente = $documento->fields['codigo_cliente'];
	}

	if (((method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario')) || 
		( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario())) && $codigo_cliente != '')
	{
		$cliente = new Cliente($sesion);
		$codigo_cliente_secundario = $cliente->CodigoACodigoSecundario( $codigo_cliente );
	}

	$txt_pagina = $id_documento ? (empty($adelanto) ? __('Edición de Pago') : __('Edición del Adelanto')) : (empty($adelanto) ? __('Documento de Pago') : __('Documento de Adelanto'));
	$txt_tipo = empty($adelanto) ? __('Documento de Pago') : __('Documento de Adelanto');

	$pagina->titulo = $txt_pagina;
	$pagina->PrintTop($popup);
?>

<script type="text/javascript">
//Extend the scal library to add draggable calendar support.
//This script block can be added to the scal.js file.
Object.extend(scal.prototype,
{
    toggleCalendar: function()
    {
        var element = $(this.options.wrapper) || this.element;
        this.options[element.visible() ? 'onclose' : 'onopen'](element);
        this.options[element.visible() ? 'closeeffect' : 'openeffect'](element, {duration: 0.5});
    },

    isOpen: function()
    {
        return ( $(this.options.wrapper) || this.element).visible();
    }
});

//this is a global variable to have only one instance of the calendar
var calendar = null;

//@element   => is the <div> where the calender will be rendered by Scal.
//@input     => is the <input> where the date will be updated.
//@container => is the <div> for dragging.
//@source    => is the img/button which raises up the calender, the script will locate the calenar over this control.
function showCalendar(element, input, container, source)
{
    if (!calendar)
    {
        container = $(container);
        //the Draggable handle is hard coded to "rtop" to avoid other parameter.
        new Draggable(container, {handle: "rtop", starteffect: Prototype.emptyFunction, endeffect: Prototype.emptyFunction});

        //The singleton calendar is created.
        calendar = new scal(element, $(input),
        {
            updateformat: 'dd-mm-yyyy',
            closebutton: '&nbsp;',
            wrapper: container
        });
    }
    else
    {
        calendar.updateelement = $(input);
    }

    var date = new Date($F(input));
    calendar.setCurrentDate(isNaN(date) ? new Date() : date);

    //Locates the calendar over the calling control  (in this example the "img").
    if (source = $(source))
    {
        Position.clone($(source), container, {setWidth: false, setHeight: false, offsetLeft: source.getWidth() + 2});
    }

    //finally show the calendar =)
    calendar.openCalendar();
};


document.observe('dom:loaded', function() {
});

function Cerrar()
{
	window.opener.Refrescar();
	window.close();
}

function Validar(form)
{
	monto = parseFloat(form.monto.value);

	var monto_pagos = document.getElementById('monto_pagos');

	if(isNaN(monto))
	{
		alert('<?=__('Debe ingresar un monto para el pago')?>');
		form.monto.focus();
		return false;
	}
	
	<?php if (UtilesApp::GetConf($sesion, 'CodigoSecundario')) { ?>
	var cod_cli_seg = document.getElementById('codigo_cliente_secundario').value
	if (cod_cli_seg == '-1' || cod_cli_seg == "") {
		alert('<?=__('Debe ingresar un cliente')?>');
		return false;
	}
	<?php } else { ?>
	var cod_cli = document.getElementById('codigo_cliente').value;
	if (cod_cli == '-1' || cod_cli == "") {
		alert('<?php echo __('Debe ingresar un cliente') ?>');
		return false;
	}
	<?php } ?>
	
	if(monto < 0)
	{
		alert('<?=__('El monto de un pago debe ser siempre mayor a 0')?>');
		form.monto.focus();
		return false;
	}

	if(form.glosa_documento.value == "")
	{
		alert('<?=__('Debe ingresar una descripción')?>');
		form.glosa_documento.focus();
		return false;
	}
	if(monto > monto_pagos.value && $('es_adelanto') && $F('es_adelanto')!='1')
	{
		alert("El Monto del documento ("+monto+") es superior a la suma de los Pagos ("+monto_pagos.value+").");
		return false;
	}
	else if(monto < monto_pagos.value)
	{
		alert("La suma de los Pagos ("+monto_pagos.value+") es superior al Monto del documento ("+monto+").");
		return false;
	}
	
	<?php if (!empty($adelanto)) { ?>
	if($$('input[id^="pago_honorarios_"]:not([value="0"])').length && !$('pago_honorarios').checked){
		alert('El adelanto se ha usado para pagar honorarios. No puede deshabilitar esta opción.');
		return false;
	}
	if($$('input[id^="pago_gastos_"]:not([value="0"])').length && !$('pago_gastos').checked){
		alert('El adelanto se ha usado para pagar gastos. No puede deshabilitar esta opción.');
		return false;
	}
	<?php } ?>

}

function CheckEliminaIngreso(chk)
{
	var form = $('form_documentos');
	if(chk)
		form.elimina_ingreso.value = 1;
	else
		form.elimina_ingreso.value = '';

	return true;
}

function CargarTabla(mostrar_actualizado)
{
	var select_moneda = document.getElementById('id_moneda');
	var tabla_pagos = document.getElementById('tabla_pagos');
	var id_documento = document.getElementById('id_documento');

	var http = getXMLHTTP();
<? 
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
	{ 
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
		{ ?>
			var codigo_cliente_secundario = document.getElementById('codigo_cliente_secundario');
<?  } 
		else
		{ ?>
			var codigo_cliente_secundario = document.getElementById('campo_codigo_cliente_secundario');
<?	}	?>
		var url = root_dir + '/app/interfaces/ajax_pago_documentos.php?id_moneda=' + select_moneda.value + '&codigo_cliente_secundario=' + codigo_cliente_secundario.value<? if($id_cobro) echo "+'&id_cobro=".$id_cobro."'"; else echo "+'&id_cobro=0'"; ?>;
<?}
	else
	{ 
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
		{ ?>
			var codigo_cliente = document.getElementById('codigo_cliente');
<?  } 
		else
		{ ?>
			var codigo_cliente = document.getElementById('campo_codigo_cliente');
<?	}	?>
		var url = root_dir + '/app/interfaces/ajax_pago_documentos.php?id_moneda=' + select_moneda.value + '&codigo_cliente=' + codigo_cliente.value<? if($id_cobro) echo "+'&id_cobro=".$id_cobro."'"; else echo "+'&id_cobro=0'"; ?>;
<?} ?>
	

	if(mostrar_actualizado)
		url += ''<? if(!empty($cambios_en_saldo_honorarios)) echo "+'&c_hon=".implode(',',$cambios_en_saldo_honorarios)."'"; if(!empty($cambios_en_saldo_gastos)) echo "+'&c_gas=".implode(',',$cambios_en_saldo_gastos)."'";?>;

	if(id_documento.value)
		url += '&id_documento='+id_documento.value;

	<?php if (!empty($adelanto)) { ?>
	url += '&adelanto=1';
	<?php } ?>

	http.open('get', url);
	http.onreadystatechange = function()
	{
		if(http.readyState == 4)
		{
			var response = http.responseText;
			tabla_pagos.innerHTML = response;
			var tipopago = ['honorarios', 'gastos'];
			for(var i=0; i<2; i++){
				var disable = $F('pago_'+tipopago[i]) == '0';
				$$('input[id^="pago_'+tipopago[i]+'_"]').each(function(elem){
					if(disable) elem.disabled = 'disabled';
					else elem.removeAttribute('disabled');
				});
			}
			SetMontoPagos();
			<?php if($documento->fields['es_adelanto']=='1' && $id_cobro){?>
				var id_cobro = '<?=$documento_cobro->fields['id_documento']?>';
				var saldo = Number($F('saldo_pago'));
				var pago_honorarios = $('pago_honorarios_'+id_cobro);
				var pago_gastos = $('pago_gastos_'+id_cobro);
				if((!pago_honorarios || pago_honorarios.value == '0') && (!pago_gastos || pago_gastos.value == '0')){
					if(pago_honorarios && !pago_honorarios.disabled){
						var monto = Number($F('cobro_honorarios_'+id_cobro)) < saldo ? Number($F('cobro_honorarios_'+id_cobro)) : saldo;
						pago_honorarios.value = monto;
						saldo -= monto;
					}
					if(pago_gastos && !pago_gastos.disabled){
						var monto = Number($F('cobro_gastos_'+id_cobro) < saldo) ? Number($F('cobro_gastos_'+id_cobro)) : saldo;
						pago_gastos.value = monto;
						saldo -= monto;
					}
				}
				$('saldo_pago').value = saldo;
			<?php } ?>
		}
	};
	http.send(null);
}

function CalculaPagoIva()
{
	var id_doc_cobro = $('id_doc_cobro').value;
	var monto_pagos = $('monto_pagos').value;
	var cifras_decimales = $('cifras_decimales').value;
	var monto = document.getElementById('monto');
	
	if( $('pago_retencion').checked )
		{
			monto_retencion_impuestos = monto_pagos*12;
			monto_retencion_impuestos = (monto_retencion_impuestos.round())/100;
			$('pago_honorarios_'+id_doc_cobro).value = monto_retencion_impuestos;
			if( $('pago_gastos_'+id_doc_cobro) )
				$('pago_gastos_'+id_doc_cobro).value = 0;
			$('monto').value = monto_retencion_impuestos;
		}
	else
		{
			$('pago_honorarios_'+id_doc_cobro).value = monto_pagos;
			$('monto').value = monto_pagos;
		}
}

function  Actualizar_Monto_Pagos(tipo,id)
{
			var campo_pago = document.getElementById('pago_'+tipo+'_'+id);
			var campo_pago_anterior = document.getElementById('pago_'+tipo+'_anterior_'+id);
			var monto_pagos = document.getElementById('monto_pagos');

			var texto_pago = campo_pago.value.replace(/,/,".");
			var resultado = monto_pagos.value*1 - campo_pago_anterior.value*1 + texto_pago*1;
			if(!isNaN(resultado))
			{
				monto_pagos.value = resultado;
				campo_pago_anterior.value = texto_pago;
			}
}

function SetMontoPagos()
{
	<? if(!$documento->Loaded()){?>
		var monto_pagos = document.getElementById('monto_pagos');
		var monto = document.getElementById('monto');
		if(monto_pagos)
		{
			monto.value = Math.round(monto_pagos.value * 100) / 100;
		}
	<?}
	else if($documento->fields['es_adelanto']=='1'){?>
		$('saldo_pago').value = Math.round(($F('monto') - $F('monto_pagos')) * 100) / 100;
	<?php } ?>
}

function ActualizarDocumentoMoneda(id_documento)
{
	ids_monedas = $('ids_monedas_documento_'+id_documento).value;
	arreglo_ids = ids_monedas.split(',');
	var tc = new Array();
	for(var i = 0; i< arreglo_ids.length; i++)
			tc[i] = $('documento_'+id_documento+'_moneda_'+arreglo_ids[i]).value;
	$('tabla_pagos').innerHTML = "<img src='<?=Conf::ImgDir()?>/ajax_loader.gif'/>";
	var http = getXMLHTTP();
	var url = root_dir + '/app/interfaces/ajax.php?accion=actualizar_documento_moneda&id_documento='+id_documento+'&ids_monedas=' + ids_monedas+'&tcs='+tc.join(',');	
	http.open('get', url);
	http.onreadystatechange = function()
	{
		if(http.readyState == 4)
		{
			var response = http.responseText;
			if(response == 'EXITO')
			{
				CargarTabla(0);	
			}
		}
	}	
	http.send(null);
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
	ids_monedas = $('ids_monedas_documento').value;
	arreglo_ids = ids_monedas.split(',');
	$('tipo_cambios_documento').value = "";
	for(var i = 0; i<arreglo_ids.length-1; i++)
		$('tipo_cambios_documento').value += $('documento_moneda_'+arreglo_ids[i]).value + ",";
	i=arreglo_ids.length-1;
	$('tipo_cambios_documento').value += $('documento_moneda_'+arreglo_ids[i]).value;
	if( $('id_documento') != '' )
		{
			var tc = new Array();
			for(var i = 0; i< arreglo_ids.length; i++)
					tc[i] = $('documento_moneda_'+arreglo_ids[i]).value;
			$('contenedor_tipo_load').innerHTML = 
			"<table width=510px><tr><td align=center><br><br><img src='<?=Conf::ImgDir()?>/ajax_loader.gif'/><br><br></td></tr></table>";
			var http = getXMLHTTP();
			var url = root_dir + '/app/interfaces/ajax.php?accion=actualizar_documento_moneda&id_documento=<?=$documento->fields['id_documento']?>&ids_monedas=' + ids_monedas+'&tcs='+tc.join(',');	
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
</script>
<? echo Autocompletador::CSS(); ?>
<form method=post action="<?= $SERVER[PHP_SELF] ?>" id="form_documentos" autocomplete='off'>
<input type=hidden name=opcion value="guardar" />
<input type=hidden name='id_documento' id ='id_documento' value="<?= $documento->fields['id_documento']? $documento->fields['id_documento']:''  ?>" />
<input type=hidden name='pago' value='<?=$pago?>'>
<input type=hidden name='id_doc_cobro' id='id_doc_cobro' value='<?=$id_doc_cobro?>' />
<input type=hidden name='cifras_decimales' id='cifras_decimales' value='<?=$cifras_decimales?>' />
<input type=hidden name='cobro' value='<?=$id_cobro?>'>
<input type=hidden name=elimina_ingreso id=elimina_ingreso value=''>
<?php if(!$adelanto){ ?>
<input type=hidden name='pago_honorarios' id='pago_honorarios' value='<?=$id_documento ? $documento->fields['pago_honorarios'] : ''?>'/>
<input type=hidden name='pago_gastos' id='pago_gastos' value='<?=$id_documento ? $documento->fields['pago_gastos'] : ''?>'/>
<input type=hidden name='es_adelanto' id='es_adelanto' value='<?=$id_documento ? $documento->fields['es_adelanto'] : ''?>'/>
<?php } ?>
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
			<b><?=__('Información de Documento') ?> </b>
		</td>
		<td align=right width="50%">
<?php
	$query = "SELECT count(*) FROM documento WHERE pago_retencion = 1 AND id_cobro = '$id_cobro'";
	$resp	 = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	list( $existe_pago_retencion ) = mysql_fetch_array($resp);
	if( !$existe_pago_retencion && $id_cobro && UtilesApp::GetConf($sesion,'PagoRetencionImpuesto') && (!$id_documento || $documento->fields['es_adelanto'] != '1')) { ?>
		
			<input type="checkbox" name="pago_retencion" id="pago_retencion" onchange="CalculaPagoIva();" value=1 <?=$pago_retencion ? "checked='checked'" : "" ?> />&nbsp;<?=__('Pago retención impuestos')?>&nbsp;
<?php }
if(!$adelanto){
		$saldo_gastos = $documento_cobro->fields['saldo_gastos'] > 0 ? '&pago_gastos=1' : '';
		$saldo_honorarios = $documento_cobro->fields['saldo_honorarios'] > 0 ? '&pago_honorarios=1' : '';  ?>
		<button type="button" onclick="nuevaVentana('Adelantos', 730, 470, 'lista_adelantos.php?popup=1&id_cobro=<?php echo $id_cobro; ?>&codigo_cliente=<?php echo $codigo_cliente ?>&elegir_para_pago=1<?php echo $saldo_honorarios; ?><?php echo $saldo_gastos; ?>', 'top=\'100\', left=\'125\', scrollbars=\'yes\'');return false;" ><?php echo __('Seleccionar un adelanto'); ?></button>
<?php } ?>
		</td>
	</tr>
</table>
<table id="tabla_informacion" style="border: 1px solid black;" width='90%'>
	<tr>
		<td align=right>
			<?=__('Fecha')?>
		</td>
		<td align=left>
			<input type="text" name="fecha" value="<?=$documento->fields['fecha'] ? Utiles::sql2date($documento->fields['fecha']) : date('d-m-Y') ?>" id="fecha" size="11" maxlength="10" />
			<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha" style="cursor:pointer" />
		</td>
	</tr>
	<tr>
		<td align="right" width="30%"><?=__('Cliente ')?></td>
		<td colspan="3" align="left">
			<?
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
				{
					if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
						echo Autocompletador::ImprimirSelector($sesion, '', $codigo_cliente_secundario, '', 280, "CargarTabla(1);");
					else
						echo Autocompletador::ImprimirSelector($sesion, $codigo_cliente, '', '', 280, "CargarTabla(1);");
				}
			else
				{
				if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
						{
							echo InputId::ImprimirSinCualquiera($sesion,"cliente","codigo_cliente_secundario","glosa_cliente","codigo_cliente_secundario", $codigo_cliente_secundario,"","", 280);
						}
						else
						{
							echo InputId::ImprimirSinCualquiera($sesion,"cliente","codigo_cliente","glosa_cliente", "codigo_cliente", $codigo_cliente," ","CargarTabla(1);", 280);
						}
				} ?>
		</td>
	</tr>

		<tr>
			<td align=right>
				<?=__('Monto')?>
			</td>
			<td align=left> 
				<? if($id_cobro && !$adelanto)
				   {
						$disabled_monto = ' readonly onclick="alert(\''.__('Modifique los Pagos individuales').'\')" ';
				   }
				?>
				<input name=monto <?=$disabled_monto?> id=monto size=10 value="<? echo str_replace("-","",$documento->fields['monto']);  ?>" />
				<span style="color:#FF0000; font-size:10px">*</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<?=__('Moneda')?>&nbsp;
				<?
					if($documento->fields['id_documento'])
						$moneda_usada = $documento->fields['id_moneda'];
					else if($id_cobro)
						$moneda_usada = $cobro->fields['opc_moneda_total'];
					else
						$moneda_usada = '';
					?>
				<?= Html::SelectQuery($sesion, "SELECT id_moneda,glosa_moneda FROM prm_moneda ORDER BY id_moneda","id_moneda", $moneda_usada, 'onchange="CargarTabla(0)"','',"80"); ?>
				<span style="color:#FF0000; font-size:10px">*</span>
			</td>
		</tr>
		<?php if($id_documento && $documento->fields['es_adelanto']=='1'){ ?>
		<tr>
			<td align=right>
				<?=__('Saldo Adelanto')?>
			</td>
			<td align=left>
				<input name="saldo_pago" id="saldo_pago" size=10 value="<? echo str_replace("-","",$documento->fields['saldo_pago']); ?>" disabled="disabled"/>
			</td>
		</tr>
		<?php } ?>
		<tr>
			<td align=right>
				<?=__('Número Documento:')?>
			</td>
			<td align=left>
				<input name="numero_doc" id="numero_doc" size=20 value="<? echo str_replace("-","",$documento->fields['numero_doc']);  ?>" />
						<?=__('Tipo:')?>&nbsp;
				<select name='tipo_doc' id='tipo_doc'  style='width: 80px;'>
				<? if($documento->fields['tipo_doc']=='E' || $documento->fields['tipo_doc']=='' || $documento->fields['tipo_doc']=='N' ) { ?>
					<option value='E' selected>Efectivo</option>
					<option value='C'>Cheque</option>
					<option value='T'>Transferencia</option>
					<option value='O'>Otro</option>
				<? } if($documento->fields['tipo_doc']=='C') { ?>
					<option value='E'>Efectivo</option>
					<option value='C' selected>Cheque</option>
					<option value='T'>Transferencia</option>
					<option value='O'>Otro</option>
				<? } if($documento->fields['tipo_doc']=='T') { ?>
					<option value='E'>Efectivo</option>
					<option value='C'>Cheque</option>
					<option value='T' selected>Transferencia</option>
					<option value='O'>Otro</option>
				<? } if($documento->fields['tipo_doc']=='O') { ?>
					<option value='E'>Efectivo</option>
					<option value='C'>Cheque</option>
					<option value='T'>Transferencia</option>
					<option value='O' selected>Otro</option>
				<? } ?>
					</select>
			</td>
		</tr>

	<tr>
		<td align=right>
			<?=__('Descripción')?>
		</td>
		<td align=left>
			<textarea name="glosa_documento" id="glosa_documento" cols="45" rows="3"><?
				if($documento->fields['glosa_documento'])
					echo $documento->fields['glosa_documento'];
				else if($id_cobro)
					echo "Pago de " . __('Cobro') . " #".$id_cobro;
			?></textarea>
		</td>
	</tr>
	<?php
	if($documento->fields['id_cuenta']){
		$id_banco = $documento->fields['id_banco'];
		$id_cuenta = $documento->fields['id_cuenta'];
	}
	?>
	<tr>
		<td align=right>
			<?=__('Banco')?>
		</td>
		<td align=left>
			<?=InputId::Imprimir($sesion,"prm_banco","id_banco","nombre", "id_banco", $id_banco,"","CargarSelect('id_banco','id_cuenta','cargar_cuenta_banco');", 125, $id_cuenta);?>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('N° Cuenta')?>
		</td>
		<td align=left>
			<?=InputId::Imprimir($sesion,"cuenta_banco","id_cuenta","numero", "id_cuenta", $id_cuenta,"","", 125, $id_banco);?>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('N° Operación')?>
		</td>
		<td align=left>
			<input name=numero_operacion id=numero_operacion size=15 value="<? echo $documento->fields['numero_operacion'];  ?>" />
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<?=__('N° Cheque')?>&nbsp;
				<input name=numero_cheque id=numero_cheque size=15 value="<? echo $documento->fields['numero_cheque'];  ?>" />
		</td>
	</tr>
	
	<?php if (empty($adelanto)) { ?>
	<tr>
		<td colspan="2" align=center>
			<img src="<?=Conf::ImgDir()?>/money_16.gif" border=0> <a href='javascript:void(0)' onclick="MostrarTipoCambioPago()" title="<?=__('Tipo de Cambio del Documento de Pago al ser pagado.')?>"><?=__('Actualizar Tipo de Cambio')?></a>
		</td>
	</tr>
	<tr>
		<td align=right colspan="2">
			&nbsp;
		</td>
	</tr>	
	<tr>
		<td align=right colspan="2">
			<div id="TipoCambioDocumentoPago" style="display:none; left: 100px; top: 300px; background-color: white; position:absolute; z-index: 4;">
				<fieldset style="background-color:white;">
				<legend><?=__('Tipo de Cambio Documento de Pago')?></legend>
				<div id="contenedor_tipo_load">&nbsp;</div>
				<div id="contenedor_tipo_cambio">
				<div style="padding-top:5px; padding-bottom:5px;">&nbsp;<img src="<?=Conf::ImgDir()?>/alerta_16.gif" title="Alerta" />&nbsp;&nbsp;<?=__('Este tipo de cambio sólo afecta al Documento de Pago en los Reportes. No modifica la Carta de') .  " " . __('Cobro') . "."?></div>
				<table style='border-collapse:collapse;' cellpadding='3'>
					<tr>
						<?
						if( $documento->fields['id_documento'] )
							{
								$query = "SELECT count(*) FROM documento_moneda WHERE id_documento = '".$documento->fields['id_documento']."'";
								$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
								list($cont) = mysql_fetch_array($resp);
							}
						else
							$cont = 0;
						if( $cont > 0 )
							{
								$query = 
								"SELECT prm_moneda.id_moneda, glosa_moneda, documento_moneda.tipo_cambio 
								FROM documento_moneda 
								JOIN prm_moneda ON documento_moneda.id_moneda = prm_moneda.id_moneda
								WHERE id_documento = '".$documento->fields['id_documento']."'";
								$resp =mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__, $sesion->dbh);
							}
						else
							{
								$query =
								"SELECT prm_moneda.id_moneda, glosa_moneda, documento_moneda.tipo_cambio 
								FROM documento_moneda 
								JOIN prm_moneda ON documento_moneda.id_moneda = prm_moneda.id_moneda 
								WHERE id_documento = '".$documento_cobro->fields['id_documento']."'";
								$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__, $sesion->dbh);
							}
						$num_monedas=0; $ids_monedas = array(); $tipo_cambios = array();
						while(list($id_moneda,$glosa_moneda,$tipo_cambio) = mysql_fetch_array($resp))
						{
						?>
							<td>
									<span><b><?=$glosa_moneda?></b></span><br>
									<input type='text' size=9 id='documento_moneda_<?=$id_moneda?>' name='documento_moneda_<?=$id_moneda?>' value='<?=$tipo_cambio?>' />
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
							<input type=hidden id="tipo_cambios_documento" name="tipo_cambios_documento" value="<?=implode(',',$tipo_cambios)?>" />
							<input type=hidden id="ids_monedas_documento" name="ids_monedas_documento" value="<?=implode(',',$ids_monedas)?>" />
						</td>
					</tr>
			</table>
			</div>
			</fieldset>
			
			</div>
		</td>
	</tr>
	<?php } ?>
	<?php if (!empty($adelanto)) { ?>
	<tr>
		<td align="right">
			<input type="checkbox" name="pago_honorarios" id="pago_honorarios" value="1" <?php echo empty($id_documento) ? "checked='checked'" : ($documento->fields['pago_honorarios'] ? "checked='checked'" : "") ?> />
		</td>
		<td align="left">
			<label for="pago_honorarios"><?php echo __('Para el pago de honorarios') ?></label>
		</td>
	</tr>
	<tr>
		<td align="right">
			<input type="checkbox" name="pago_gastos" id="pago_gastos" value="1" <?php echo empty($id_documento) ? "checked='checked'" : ($documento->fields['pago_gastos'] ? "checked='checked'" : "") ?> />
		</td>
		<td align="left">
			<label for="pago_gastos"><?php echo __('Para el pago de gastos') ?></label>
		</td>
	</tr>
	<?php } ?>
</table>

<br>
<table style="border: 0px solid black;" width='90%'>
	<tr>
		<td align=left>
			<input type=submit class=btn value="<?=__('Guardar')?>" onclick='return Validar(this.form);' /> <input type=button class=btn value="<?=__('Cerrar')?>" onclick="Cerrar();" />
		</td>
	</tr>
</table>

<div id = "tabla_pagos"> </div>
<script>
	<?php if(empty($adelanto) && $id_documento && $documento->fields['es_adelanto'] == '1') { ?>
		$('tabla_informacion').select('input, select, textarea').each(function(elem){
			elem.disabled = 'disabled';
		});
	<?php }?>
	CargarTabla(1);
</script>

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
	echo InputId::Javascript($sesion,"","No existen N° de cuenta asociadas a este banco.");
	$pagina->PrintBottom($popup);
?>
