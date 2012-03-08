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

	$sesion = new Sesion(array('ADM'));
	$pagina = new Pagina($sesion);
	$id_usuario = $sesion->usuario->fields['id_usuario'];

	$documento = new Documento($sesion);
	$cobro = new Cobro($sesion);

	$cambios_en_saldo_honorarios = array();
	$cambios_en_saldo_gastos = array();

	if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) && $codigo_cliente != '' )
		{
			$cliente = new Cliente($sesion);
			$codigo_cliente_secundario = $cliente->CodigoACodigoSecundario( $codigo_cliente );
		}
	
	if($id_documento)
	{
		$documento->Load($id_documento);
		$codigo_cliente = $documento->fields['codigo_cliente'];
	}

	if($opcion == "guardar")
	{
		if($id_cobro)
				{
				$cobro->Load($id_cobro);
				$query="UPDATE cobro SET fecha_cobro='".Utiles::fecha2sql($fecha)." 00:00:00' WHERE id_cobro=".$id_cobro;
				$resp=mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
				}
				
		$query = "SELECT activo FROM cliente WHERE codigo_cliente=".$codigo_cliente;
		$resp=mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		list($activo)=mysql_fetch_array($resp);
		
		if($activo==1) 
			{
			$monto=str_replace(',','.',$monto);
	
			/*Es pago, asi que monto es negativo*/
			$multiplicador = -1.0;
			$documento->Edit("id_tipo_documento",2);
	
			$moneda = new Moneda($sesion);
			$moneda->Load($id_moneda);
			$moneda_base = Utiles::MonedaBase($sesion);
	
			$monto_base = $monto * $moneda->fields['tipo_cambio'] / $moneda_base['tipo_cambio'];
	
			$documento->Edit("monto",number_format($monto*$multiplicador,$moneda->fields['cifras_decimales'],".",""));
			$documento->Edit("monto_base",number_format($monto_base*$multiplicador,$moneda_base['cifras_decimales'],".",""));
			$documento->Edit("saldo_pago",number_format($monto*$multiplicador,$moneda->fields['cifras_decimales'],".",""));
			$documento->Edit("id_cobro",$id_cobro);
			$documento->Edit('tipo_doc',$tipo_doc);
			$documento->Edit("numero_doc",$numero_doc);
			$documento->Edit("id_moneda",$id_moneda);
			$documento->Edit("fecha",Utiles::fecha2sql($fecha));
			$documento->Edit("glosa_documento",$glosa_documento);
			$documento->Edit("codigo_cliente",$codigo_cliente);
	
			$out_neteos = "";
	
			if($documento->Write())
				{
					$id_documento = $documento->fields['id_documento'];
					$pagina->addInfo(__('Pago ingresado con éxito'));
		
							//Si se ingresa el documento, se ingresan los pagos
							foreach($_POST as $key => $post)
							{
								if(strpos($key,'documento_pendiente_') !== false && $post > 0)
								{
									$id_documento_cobro = $post;
		
									$pago_honorarios = str_replace(',','.',$_POST['pago_honorarios_'.$post]);
									$pago_gastos = str_replace(',','.',$_POST['pago_gastos_'.$post]);
		
									$cambio_cobro = $_POST['cambio_cobro_'.$post];
									$cambio_pago = $_POST['cambio_pago_'.$post];
		
									$decimales_cobro = $_POST['decimales_cobro_'.$post];
									$decimales_pago = $_POST['decimales_pago_'.$post];
		
									$id_cobro_neteado = $_POST['id_cobro_'.$post];
		
									if(!$pago_gastos) $pago_gastos = 0;
									if(!$pago_honorarios) $pago_honorarios = 0;
		
									/*Guardo los saldos, para indicar cuales fueron actualizados*/
									$documento_cobro_aux = new Documento($sesion);
									if($documento_cobro_aux->Load($id_documento_cobro))
									{
										$saldo_honorarios_anterior = $documento_cobro_aux->fields['saldo_honorarios'];
										$saldo_gastos_anterior = $documento_cobro_aux->fields['saldo_gastos'];
									}
		
									$neteo_documento = new NeteoDocumento($sesion);
		
									//Si el neteo existía, está siendo modificado y se debe partir de 0:
									if( $neteo_documento->Ids($id_documento,$id_documento_cobro)) 
									$out_neteos .= $neteo_documento->Reestablecer($decimales_cobro); 
									else
									$out_neteos .= "<tr><td>No</td><td>0</td><td>0</td>";
		
									//Luego se modifica
									if($pago_honorarios != 0 || $pago_gastos != 0)
										$out_neteos .= $neteo_documento->Escribir($pago_honorarios,$pago_gastos,$cambio_pago,$cambio_cobro,$decimales_pago,$decimales_cobro,$id_cobro_neteado);
		
									/*Compruebo cambios en saldos para mostrar mensajes de actualizacion*/
									$documento_cobro_aux = new Documento($sesion);
									if($documento_cobro_aux->Load($id_documento_cobro))
									{
										if($saldo_honorarios_anterior != $documento_cobro_aux->fields['saldo_honorarios'])
											$cambios_en_saldo_honorarios[] = $id_documento_cobro;
										if($saldo_gastos_anterior != $documento_cobro_aux->fields['saldo_gastos'])
											$cambios_en_saldo_gastos[] = $id_documento_cobro;
									}
								}
						}
		
						$documento->Load($id_documento);
						$monto_neteos =  $documento->fields['saldo_pago']-$documento->fields['monto'];
						$monto_pago = -1*$documento->fields['monto'];
						
						?>
						<script type="text/javascript">
						window.opener.Refrescar();
						</script> <?
				}
				else
					$pagina->AddError($documento->error);
			}
		else
		{ ?>
			<script type="text/javascript">alert('�No se puede modificar un pago de un cliente inactivo!');</script>
<?	}
		
		$out_neteos = "<table border=1><tr> <td>Id Cobro</td><td>Faltaba</td> <td>Aportaba y Devolví</td> <td>Pasó a Faltar</td> <td>Ahora aporto</td> <td>Ahora falta </td> </tr>".$out_neteos."</table>";
		//echo $out_neteos;
	}

	 $txt_pagina = $id_documento ? __('Edici�n de Pago') : __('Documento de Pago');
	 $txt_tipo = __('Documento de Pago');

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
	if(monto > monto_pagos.value)
	{
		return confirm("El Monto del documento ("+monto+") es superior a la suma de los Pagos ("+monto_pagos.value+"). ¿Está seguro que desea continuar?");
	}
	else if(monto < monto_pagos.value)
	{
	if( confirm("La suma de los Pagos ("+monto_pagos.value+") es superior al Monto del documento ("+monto+"). ¿Está seguro que desea continuar?") );
			{
				document.getElementById('pago_honorarios_570').value=monto;
				return true;
			}
		return false;
	}

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

	http.open('get', url);
	http.onreadystatechange = function()
	{
		if(http.readyState == 4)
		{
			var response = http.responseText;
			tabla_pagos.innerHTML = response;
			SetMontoPagos();
		}
	};
	http.send(null);
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
	<?}?>
	<?if($opcion != "guardar"){?>
	window.setTimeout($('form_documentos').submit(),100);
	<?}else{?>
	window.close();
	<?}?>
}


</script>
<? echo Autocompletador::CSS(); ?>
<form method=post action="<?= $SERVER[PHP_SELF] ?>" id="form_documentos" autocomplete='off'>
<input type=hidden name=opcion value="guardar" />
<input type=hidden name='id_documento' id ='id_documento' value="<?= $documento->fields['id_documento']? $documento->fields['id_documento']:''  ?>" />
<input type=hidden name='pago' value='<?=$pago?>'>
<input type=hidden name='cobro' value='<?=$id_cobro?>'>
<input type=hidden name=elimina_ingreso id=elimina_ingreso value=''>
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
		<td align=left>
			<b><?=__('Información de Documento') ?> </b>
		</td>
	</tr>
</table>
<table style="border: 1px solid black;" width='90%'>
	<tr>
		<td align=right>
			<?=__('Fecha')?>
		</td>
		<td align=left>
			<input type="text" name="fecha" value="<?=$documento->fields[fecha] ? Utiles::sql2date($documento->fields[fecha]) : date('d-m-Y') ?>" id="fecha" size="11" maxlength="10" />
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
				<input name=monto id=monto size=10 value="<? echo str_replace("-","",$documento->fields['monto']);  ?>" />
				<span style="color:#FF0000; font-size:10px">*</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<?=__('Moneda')?>&nbsp;
				<?
					if($id_documento)
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
		<tr>
			<td align=right>
				<?=__('Número Documento:')?>
			</td>
			<td align=left>
				<input name=numero_doc size=20 value="<? echo str_replace("-","",$documento->fields['numero_doc']);  ?>" />
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
			<textarea name=glosa_documento cols="45" rows="3"><?
				if($documento->fields['glosa_documento'])
					echo $documento->fields['glosa_documento'];
				else if($id_cobro)
					echo "Pago de " . __('Cobro') . " #".$id_cobro.'. Generado automaticamente por el sistema el '.date('d-m-Y').'.';
			?></textarea>
		</td>
	</tr>
	<tr>
		<td align=right colspan="2">&nbsp;</td>
	</tr>
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
	echo InputId::Javascript($sesion);
	$pagina->PrintBottom($popup);
?>
