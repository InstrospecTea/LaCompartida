<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../app/classes/Contrato.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../app/classes/Cliente.php';
	require_once Conf::ServerDir().'/../app/classes/Moneda.php';
	require_once Conf::ServerDir().'/../app/classes/InputId.php';
	require_once Conf::ServerDir().'/../app/classes/Tarifa.php';
	require_once Conf::ServerDir().'/../app/classes/Funciones.php';
	require_once Conf::ServerDir().'/../app/classes/Cobro.php';
	require_once Conf::ServerDir().'/../app/classes/Archivo.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';

	$sesion = new Sesion(array('DAT'));
	$pagina = new Pagina($sesion);
	$archivo = new Archivo($sesion);

	if($popup && !$motivo)
	{
		$show = 'inline';
		function TTip($texto)
		{
			return "onmouseover=\"ddrivetip('$texto');\" onmouseout=\"hideddrivetip('$texto');\"";
		}
		$contrato = new Contrato($sesion);
		if($id_contrato > 0)
		{
			if(!$contrato->Load($id_contrato))
				$pagina->FatalError(__('Código inválido'));

			$cobro = new Cobro($sesion);
		}

		if($contrato->fields['codigo_cliente'] != '')
		{
			$cliente = new Cliente($sesion);
			$cliente->LoadByCodigo($contrato->fields['codigo_cliente']);
		}

		if($contrato->fields['id_moneda'] == '')
			$contrato->fields['id_moneda'] = $cliente->fields['id_moneda'];

		if($id_contrato)
			$pagina->titulo = __('Editar Contrato');
		else
			$pagina->titulo = __('Agregar Contrato');
	}
	else
		$show = 'none';


	#CONTRATO GUARDA
	if($opcion_contrato == "guardar_contrato" && $popup && !$motivo)
	{
		$enviar_mail = 1;
		if($forma_cobro != 'TASA' && $monto == 0)
		{
			$pagina->AddError( __('Ud. a seleccionado forma de cobro:').' '.$forma_cobro.' '.__('y no ha ingresado monto') );
			$val=true;
		}
		elseif($forma_cobro == 'TASA')
		{
			$monto = '0';
		}

		$contrato->Edit("glosa_contrato",$glosa_contrato);
		$contrato->Edit("codigo_cliente",$codigo_cliente);
		$contrato->Edit("id_usuario_responsable",$id_usuario_responsable ? $id_usuario_responsable : '1');
		$contrato->Edit("centro_costo",$centro_costo);
		$contrato->Edit("contacto",$contacto);
		$contrato->Edit("fono_contacto",$fono_contacto_contrato);
		$contrato->Edit("email_contacto",$email_contacto_contrato);
		$contrato->Edit("direccion_contacto",$direccion_contacto_contrato);
		$contrato->Edit("es_periodico",$es_periodico);
		$contrato->Edit("activo",$activo_contrato ? 'SI' : 'NO');

		if($es_periodico == 'SI')
			$contrato->Edit("periodo_fecha_inicio", $periodo_fecha_inicio);
		else
			$contrato->Edit("periodo_fecha_inicio", $fecha_estimada_cobro);

		$contrato->Edit("periodo_repeticiones", $periodo_repeticiones);
		$contrato->Edit("periodo_intervalo", $periodo_intervalo);
		$contrato->Edit("periodo_unidad", $codigo_unidad);
		$monto=str_replace(',','.',$monto);//en caso de usar comas en vez de puntos
		$contrato->Edit("monto", $monto);
		$contrato->Edit("id_moneda", $id_moneda);
		$contrato->Edit("forma_cobro", $forma_cobro);
		$contrato->Edit("fecha_inicio_cap", Utiles::fecha2sql($fecha_inicio_cap));
		$retainer_horas=str_replace(',','.',$retainer_horas);//en caso de usar comas en vez de puntos
		$contrato->Edit("retainer_horas", $retainer_horas);
		$contrato->Edit("id_usuario_modificador", $sesion->usuario->fields['id_usuario']);
		$contrato->Edit("id_carta", $id_carta ? $id_carta : 'NULL');
		$contrato->Edit("id_tarifa", $id_tarifa ? $id_tarifa : 'NULL');
		$contrato->Edit("id_tramite_tarifa", $id_tramite_tarifa ? $id_tramite_tarifa : 'NULL');
		#facturacion
		$contrato->Edit("rut",$factura_rut);
		$contrato->Edit("factura_razon_social",$factura_razon_social);
		$contrato->Edit("factura_giro",$factura_giro);
		$contrato->Edit("factura_direccion",$factura_direccion);
		$contrato->Edit("factura_telefono",$factura_telefono);
		$contrato->Edit("cod_factura_telefono",$cod_factura_telefono);
		#Opc contrato
		$contrato->Edit("opc_ver_modalidad",$opc_ver_modalidad);
		$contrato->Edit("opc_ver_profesional",$opc_ver_profesional);
		$contrato->Edit("opc_ver_gastos",$opc_ver_gastos);
		$contrato->Edit("opc_ver_morosidad",$opc_ver_morosidad);
		$contrato->Edit("opc_ver_numpag",$opc_ver_numpag);
		$contrato->Edit("opc_ver_carta",$opc_ver_carta);
		$contrato->Edit("opc_papel",$opc_papel);
		$contrato->Edit("opc_moneda_total",$opc_moneda_total);
		$contrato->Edit("codigo_idioma",$codigo_idioma != '' ? $codigo_idioma : 'es');
		#descto
		$contrato->Edit("tipo_descuento",$tipo_descuento);
		if($tipo_descuento == 'PORCENTAJE')
		{
			$porcentaje_descuento=str_replace(',','.',$porcentaje_descuento);//en caso de usar comas en vez de puntos
			$contrato->Edit("porcentaje_descuento",$porcentaje_descuento > 0 ? $porcentaje_descuento : '0');
			$contrato->Edit("descuento", '0');
		}
		else
		{
			$descuento=str_replace(',','.',$descuento);//en caso de usar comas en vez de puntos
			$contrato->Edit("descuento",$descuento > 0 ? $descuento : '0');
			$contrato->Edit("porcentaje_descuento",'0');
		}

		$contrato->Edit("id_moneda_monto",$id_moneda_monto);

		if($contrato->Write())
		{

			$pagina->AddInfo(__('Contrato guardado con éxito'));
?>
			<script language="javascript">
				window.opener.Refrescar();
					/*{

						//window.close();
					}*/
			</script>
<?		}
		else
			$pagina->AddError($contrato->error);
	}

	$tarifa = new Tarifa($sesion);
	$tarifa_default = $tarifa->SetTarifaDefecto();

	if($popup && !$motivo)

		$pagina->PrintTop($popup);

	if($popup && !$motivo)
		$div_show = true;	#aqui es popup de contrato directo agregar.
	else
		$div_show = false;

	$query_count = "SELECT COUNT(usuario.id_usuario)
				FROM usuario JOIN usuario_permiso USING(id_usuario)
				WHERE codigo_permiso='SOC'";
	$resp = mysql_query($query_count,$sesion->dbh);
	list($cant_encargados) = mysql_fetch_array($resp);
?>
<script>
function ValidarContrato(form)
{
	if(!form)
		var form = $('formulario');

	/*var seleccionado = false;
	for( var i=0; actual = form.elements.id_moneda[i]; i++ )
	{
		if(form.elements.id_moneda[i].checked)
		{
			seleccionado = true;
			break;
		}
	}
	if(!seleccionado)
	{
		alert('<?=__("Debe seleccionar una Moneda")?>');
		return false;
	}

	seleccionado = false;
	for( var i=0; actual = form.elements.forma_cobro[i]; i++ )
	{
		if(form.elements.forma_cobro[i].checked)
		{
			seleccionado = true;
			break;
		}
	}
	if(!seleccionado)
	{
		alert('<?=__("Debe seleccionar una forma de cobro")?>');
		return false;
	}*/

	form.submit();
	return true;
}

function MuestraOculta(divID)
{
	var divArea = $(divID);
	var divAreaImg = $(divID+"_img");
	var divAreaVisible = divArea.style['display'] != "none";
	if(divAreaVisible)
	{
		divArea.style['display'] = "none";
		divAreaImg.innerHTML = "<img src='../templates/default/img/mas.gif' border='0' title='Desplegar'>";
	}
	else
	{
		divArea.style['display'] = "inline";
		divAreaImg.innerHTML = "<img src='../templates/default/img/menos.gif' border='0' title='Ocultar'>";
	}
}

function TogglePeriodico(chk)
{

	if(chk)
  {
  		document.getElementById("tr_fecha_estimada_cobro").style.display = "none";
      document.getElementById("div_cobro_periodos").style.display = "inline";
      //document.getElementById("div_cobro_periodos").style.display = "block";
  }
  else
  {
  		document.getElementById("div_cobro_periodos").style.display = "none";
      document.getElementById("tr_fecha_estimada_cobro").style.display = "inline";
      //document.getElementById("tr_fecha_estimada_cobro").style.display = "block";
	}
}

function ShowTHH()
{
	div = $("div_forma_cobro");
	div.style.display = "none";
	div = $("div_monto");
	div.style.display = "none";
	div = $("div_horas");
	div.style.display = "none";
	div = $("div_fecha_cap");
	div.style.display = "none";
}
function ShowFlatFee()
{
	div = $("div_forma_cobro");
	div.style.display = "block";
	div = $("div_monto");
	div.style.display = "block";
	div = $("div_horas");
	div.style.display = "none";
	div = $("div_fecha_cap");
	div.style.display = "none";
}
function ShowRetainer()
{
	div = $("div_forma_cobro");
	div.style.display = "block";
  div = $("div_monto");
  div.style.display = "block";
  div = $("div_horas");
  div.style.display = "block";
  div = $("div_fecha_cap");
	div.style.display = "none";
}
function ShowCap()
{
	div = $("div_forma_cobro");
	div.style.display = "block";
	div = $("div_monto");
	div.style.display = "block";
	div = $("div_horas");
	div.style.display = "none";
	div = $("div_fecha_cap");
	div.style.display = "block";
}
function ActualizarFormaCobro()
{
	fc1 = $("fc1");
	fc2 = $("fc2");
	fc3 = $("fc3");
	fc5 = $("fc5");

	if(fc1.checked)
		ShowTHH();
	else if(fc2.checked)
		ShowRetainer();
	else if(fc3.checked)
		ShowFlatFee();
	else if(fc5.checked)
		ShowCap();
}
function CreaTarifa(form, opcion)
{
	var form = $('formulario');
	if(opcion)
		nuevaVentana( 'Tarifas', 600, 600, 'agregar_tarifa.php?popup=1', '' );
	else
	{
		var id_tarifa = form.id_tarifa.value;
		nuevaVentana( 'Tarifas', 600, 600, 'agregar_tarifa.php?popup=1&id_tarifa_edicion='+id_tarifa, '' );
	}
}

/*
	Desactivar contrato para no verlo en cobros. (generación)
*/
function InactivaContrato(alerta, opcion)
{
	var form = $('formulario');
	var activo_contrato = $('activo_contrato');
	if(!alerta)
	{
		var text_window = "<img src='<?=Conf::ImgDir()?>/alerta_16.gif'>&nbsp;&nbsp;<span style='font-size:12px; color:#FF0000; text-align:center;font-weight:bold'><u><?=__("ALERTA")?></u><br><br>";
		text_window += '<span style="text-align:center; font-size:11px; color:#000; "><?=__('Ud. está desactivando este contrato, por lo tanto este contrato no aparecerá en la lista de la generación de cobros')?>.</span><br>';
		text_window += '<br><table><tr>';
		text_window += '<td align=right><span style="text-align:center; font-size:11px;color:#FF0000; "><?=__('¿Está seguro de desactivar este contrato?')?>:</span></td></tr>';
		text_window += '</table>';
		Dialog.confirm(text_window,
		{
			top:150, left:290, width:400, okLabel: "<?=__('Aceptar')?>", cancelLabel: "<?=__('Cancelar')?>", buttonClass: "btn", className: "alphacube",
			id: "myDialogId",
			cancel:function(win){ activo_contrato.checked = true; return false; },
			ok:function(win){ ValidarContrato(this.form); return true; }
		});
	}
	else
		return false;
}
</script>
<? if($popup && !$motivo){?>
<form name='formulario' id='formulario' method=post>
<input type=hidden name=codigo_cliente value="<?=$cliente->fields['codigo_cliente'] ? $cliente->fields['codigo_cliente'] : $codigo_cliente ?>" />
<input type=hidden name=opcion_contrato value="guardar_contrato" />
<input type=hidden name='id_contrato' value="<?=$contrato->fields['id_contrato'] ?>" />
<? } ?>
<br>
<!-- Calendario DIV -->
<div id="calendar-container" style="width:221px; position:absolute; display:none;">
	<div class="floating" id="calendar"></div>
</div>

<!-- Fin calendario DIV -->
<fieldset style="width: 97%;">
	<legend>&nbsp;<?=__('Información Comercial')?></legend>

<!-- RESPONSABLE -->
<table id='responsable' style='display:inline'>
	<tr>
		<td align=left width='30%'>
			<?=__('Activo')?>
		</td>
<?
		if(!$contrato->loaded())
			$chk = 'checked';
		else
			$chk = '';
?>
		<td align=left width = '70%'>
			<input type=checkbox name=activo_contrato id=activo_contrato value=1 <?=$contrato->fields['activo'] == 'SI' ? 'checked' : ''?> <?=$chk ?> onclick=InactivaContrato(this.checked)>
		</td>
	</tr>
<?
	$query = "SELECT usuario.id_usuario,CONCAT_WS(' ',apellido1,apellido2,',',nombre)
				FROM usuario JOIN usuario_permiso USING(id_usuario)
				WHERE codigo_permiso='SOC' ORDER BY apellido1";
?>
	<tr>
		<td align=left width='30%'>
			<?=__('Encargado Comercial')?>
		</td>
		<td align=left width = '70%'>
			<?= Html::SelectQuery($sesion, $query,"id_usuario_responsable", $contrato->fields['id_usuario_responsable'] ? $contrato->fields['id_usuario_responsable']:$sesion->usuario->fields['id_usuario'], "","","200");?>
			&nbsp;
			<?=__('Centro Costo')?>
			<?= Html::SelectQuery($sesion, "SELECT codigo_centro_costo, glosa_centro_costo FROM centro_costo", "centro_costo", $contrato->fields['centro_costo'], "", "", "80");
      ?>
		</td>
	</tr>
</table>
<br><br>
<!-- FIN RESPONSABLE -->

<!-- DATOS FACTURACION -->
<fieldset style="width: 98%;">
	<legend <?=!$div_show ? 'onClick="MuestraOculta(\'datos_factura\')" style="cursor:pointer"' : ''?>>
		<?=!$div_show ? '<span id="datos_factura_img"><img src="'.Conf::ImgDir().'/mas.gif" border="0" id="datos_factura_img"></span>': '' ?>
		&nbsp;<?=__('Datos Facturación') ?></legend>
	<table id='datos_factura' style='display:<?=$show?>'>
	<tr>
		<td align=right width='20%'>
			<?=__('ROL/RUT')?>
		</td>
		<td align=left colspan=3>
        	<input type="text" size=20 name="factura_rut" id="rut" value="<?= $contrato->fields['rut'] ?>"  />
		</td>
	</tr>
   	<tr>
		<td align=right colspan=1>
			<?=__('Razón Social')?>
		</td>
		<td align=left colspan=5>
			<input name='factura_razon_social' size=50 value="<?= $contrato->fields['factura_razon_social'] ?>"  />
		</td>
	</tr>
	<tr>
		<td align=right colspan=1>
			<?=__('Giro')?>
		</td>
		<td align=left colspan=5>
			<input name='factura_giro' size=50 value="<?= $contrato->fields['factura_giro'] ?>"  />
		</td>
	</tr>
    <tr>
		<td align=right colspan=1>
			<?=__('Dirección')?>
		</td>
		<td align=left colspan=5>
			<textarea name='factura_direccion' rows=4 cols="55" ><?= $contrato->fields['factura_direccion'] ?></textarea>
		</td>
	</tr>
  <tr>
		<td align=right colspan=1>
			<?=__('Teléfono')?>
		</td>
		<td align=left colspan=5>
			<input name='cod_factura_telefono' size=8 value="<?= $contrato->fields['cod_factura_telefono'] ?>" />&nbsp;<input name='factura_telefono' size=30 value="<?= $contrato->fields['factura_telefono'] ?>" />
		</td>
	</tr>
	<tr>
		<td align=right colspan=1>
			<?=__('Glosa factura')?>
		</td>
		<td align=left colspan=5>
			<textarea name='glosa_contrato' rows=4 cols="55" ><?= $contrato->fields['glosa_contrato'] ?></textarea>
		</td>
	</tr>
  </table>
</fieldset>
<!-- FIN DATOS FACTURACION -->
<br>


<!-- SOLICITANTE -->
<fieldset style="width: 98%">
	<legend <?=!$div_show ? 'onClick="MuestraOculta(\'datos_solicitante\')" style="cursor:pointer"' : ''?> >
		<?=!$div_show ? '<span id="datos_solicitante_img"><img src="'.Conf::ImgDir().'/mas.gif" border="0" id="datos_solicitante_img"></span>' : ''?>
		&nbsp;<?=__('Solicitante')?></legend>
	<table id='datos_solicitante' style='display:<?=$show?>'>
	<tr>
		<td align=right width='20%'>
			<?=__('Nombre')?>
		</td>
		<td align='left' colspan='3'>
			<input type="text" size='55' name="contacto" id="contacto" value="<?= $contrato->fields['contacto'] ?>"  />
		</td>
	</tr>
    <tr>
		<td align=right colspan=1>
			<?=__('Teléfono')?>
		</td>
		<td align=left colspan=5>
			<input name='fono_contacto_contrato' size=30 value="<?= $contrato->fields['fono_contacto'] ?>" />
		</td>
	</tr>
    <tr>
		<td align=right colspan=1>
			<?=__('E-mail')?>
		</td>
		<td align=left colspan=5>
			<input name='email_contacto_contrato' size=55 value="<?= $contrato->fields['email_contacto'] ?>"  />
		</td>
	</tr>
    <tr>
		<td align=right colspan=1>
			<?=__('Dirección envío')?>
		</td>
		<td align=left colspan=5>
			<textarea name='direccion_contacto_contrato' rows=4 cols="55" ><?= $contrato->fields['direccion_contacto'] ?></textarea>
		</td>
	</tr>

    </table>
</fieldset>
<!-- FIN SOLICITANTE -->

<br>
<?
	$fecha_ini = date('d-m-Y');

	if($popup && !$motivo)
	{
			if($contrato->loaded())
	    {
        if($contrato->fields['periodo_fecha_inicio']!='0000-00-00' && $contrato->fields['periodo_fecha_inicio']!='' && $contrato->fields['periodo_fecha_inicio'] != 'NULL')
					$fecha_ini = Utiles::sql2date($contrato->fields['periodo_fecha_inicio']);
	    }
	}
	else
		$fecha_ini = Utiles::sql2date($contrato->fields['periodo_fecha_inicio']);

	if(!$id_moneda)
		$id_moneda = Moneda::GetMonedaTarifaPorDefecto($sesion);
	if(!$id_moneda)
		$id_moneda = Moneda::GetMonedaBase($sesion);
?>

<!-- COBRANZA -->
<fieldset style="width: 98%">
	<legend <?=!$div_show ? 'onClick="MuestraOculta(\'datos_cobranza\')" style="cursor:pointer"' : ''?> />
		<?=!$div_show ? '<span id="datos_cobranza_img"><img src="'.Conf::ImgDir().'/mas.gif" border="0" id="datos_cobranza_img"></span>' : '' ?>
		&nbsp;<?=__('Datos de Cobranza')?>
	</legend>
	<div id='datos_cobranza' style='display:<?=$show?>' width="100%">
		<table width="100%">
			<tr>
				<td width="25%" align=right>
					<?=__('Cobro Periodico')?>
				</td>
				<td align=left width="75%">
					<input onclick="TogglePeriodico(this.checked);" type="checkbox" name="es_periodico" id="es_periodico" value="SI" <?= $contrato->fields['es_periodico'] == 'SI' ? "checked" : "" ?> >
				</td>
			</tr>
		</table>
<?
	$display = $contrato->fields['es_periodico'] == 'SI' ? "inline" : "none";
?>
		<table id='div_cobro_periodos' style="display:'<?=$display ?>" width="100%">
		<tr>
			<td width="25%">
				&nbsp;
			</td>
			<td align='left' width="75%">&nbsp;
				<div style='border:1px solid #999999;width:400px;padding:4px 4px 4px 4px'>
					<table style='border: 1px solid' bgcolor='#C6DEAD' width="99%">
						<tr>
							<td align=right>
								<?=__('Fecha Primer Cobro')?>
							</td>
							<td align=left>
								<input type="text" name="periodo_fecha_inicio" value="<?=$fecha_ini ?>" id="periodo_fecha_inicio" size="11" maxlength="10" />
								<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_periodo_fecha_inicio" style="cursor:pointer" />
							</td>
						</tr>
						<tr>
							<td align=right>
								<?=__('Cobrar')?>
							</td>
							<td align=left>
								<?= Funciones::PrintRadioUnidad($sesion,"codigo_unidad",$contrato->fields[periodo_unidad] ? $contrato->fields[periodo_unidad] : '1') ?>
								<input type='hidden' name='periodo_intervalo' size='5' value="<?= $contrato->fields['periodo_intervalo'] ?>" />
							</td>
						</tr>
						<tr>
							<td align=right>
								<?=__('Durante')?>
							</td>
							<td align=left>
								<input name=periodo_repeticiones size=3 value="<?= $contrato->fields['periodo_repeticiones'] ?>" />
								<span style='font-size:10px'><?=__('periodos (dejar en 0 para perpetuidad)')?></span>
							</td>
						</tr>
					</table>
				</div>
			</td>
		</tr>
	  </table>

		<table id='tr_fecha_estimada_cobro' width="100%">
			<tr>
				<td align=right width="25%">
					<?=__('Fecha Estimada de Cobro')?>
				</td>
				<td align=left width="75%">
					<input type="text" name="fecha_estimada_cobro" value="<?=$fecha_ini ?>" id="fecha_estimada_cobro" size="11" maxlength="10" />
					<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha_estimada_cobro" style="cursor:pointer" />
				</td>
			</tr>
		</table>

		<table width="100%">
			<tr>
				<td colspan=2><hr size=1></td>
			</tr>
		  <tr>
				<td align=right width="25%">
					<?=__('Tarifa en')?>
				</td>
				<td align=left width="75%">
					<?= Html::SelectQuery($sesion, "SELECT id_moneda,glosa_moneda FROM prm_moneda ORDER BY id_moneda","id_moneda", $contrato->fields['id_moneda'] ? $contrato->fields['id_moneda'] : $id_moneda, '','',"80"); ?>&nbsp;&nbsp;
					<?=__('Tarifa')?>&nbsp;
					<?= Html::SelectQuery($sesion, "SELECT tarifa.id_tarifa, tarifa.glosa_tarifa FROM tarifa ORDER BY id_tarifa","id_tarifa", $contrato->fields['id_tarifa'] ? $contrato->fields['id_tarifa'] : $tarifa_default, ""); ?>&nbsp;&nbsp;
					<span style='cursor:pointer' <?=TTip(__('Agregar nueva tarifa'))?> onclick='CreaTarifa(this.form,true)'><img src="<?=Conf::ImgDir()?>/mas.gif" border="0"></span>
					<span style='cursor:pointer' <?=TTip(__('Editar tarifa seleccionada'))?> onclick='CreaTarifa(this.form,false)'><img src="<?=Conf::ImgDir()?>/editar_on.gif" border="0"></span>
				</td>
			</tr>

		  <tr>
				<td align=right>
					<?=__('Forma de cobro')?>
				</td>
	<?
				if(!$contrato->fields['forma_cobro'])
					$contrato_forma_cobro = 'TASA';
				else
					$contrato_forma_cobro = $contrato->fields['forma_cobro'];
	?>
				<td align=left>
					<div id="div_cobro" align=left>
						<input <?= TTip($tip_tasa) ?> onclick="ActualizarFormaCobro()" id=fc1 type=radio name=forma_cobro value=TASA <?= $contrato_forma_cobro == "TASA" ? "checked" : "" ?> />
						<label for="fc1">Tasas/HH</label>&nbsp; &nbsp;
						<input <?= TTip($tip_retainer) ?> onclick="ActualizarFormaCobro();" id=fc2 type=radio name=forma_cobro value=RETAINER <?= $contrato_forma_cobro == "RETAINER" ? "checked" : "" ?> />
						<label for="fc3">Retainer</label> &nbsp; &nbsp;
						<input <?= TTip($tip_flat) ?> id=fc3 onclick="ActualizarFormaCobro();" type=radio name=forma_cobro value="FLAT FEE" <?= $contrato_forma_cobro == "FLAT FEE" ? "checked" : "" ?> />
						<label for="fc4"><?=__('Flat fee')?></label>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
						<input <?= TTip($tip_cap) ?> id=fc5 onclick="ActualizarFormaCobro();" type=radio name=forma_cobro value="CAP" <?= $contrato_forma_cobro == "CAP" ? "checked" : "" ?> />
						<label for="fc5"><?=__('Cap')?></label>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
					</div>
					<div style='border:1px solid #999999;width:400px;padding:4px 4px 4px 4px' id=div_forma_cobro>
						<div id=div_monto align=left style="display:none; background-color:#C6DEAD;padding-left:2px;padding-top:2px;">
							&nbsp;<?=__('Monto')?>&nbsp;<input name=monto size="7" value="<?= $contrato->fields['monto'] ?>" />&nbsp;&nbsp;
							&nbsp;&nbsp;<?=__('Moneda')?>&nbsp;<?= Html::SelectQuery($sesion, "SELECT id_moneda,glosa_moneda FROM prm_moneda ORDER BY id_moneda","id_moneda_monto", $contrato->fields['id_moneda_monto'] > 0 ? $contrato->fields['id_moneda_monto'] : $contrato->fields['id_moneda'] > 0 ? $contrato->fields['id_moneda'] : $id_moneda_monto, '','',"80"); ?>
						</div>
						<div id=div_horas align=left style="display:none; background-color:#C6DEAD;padding-left:2px;">
							&nbsp;<?=__('Horas')?>
							&nbsp;<input name=retainer_horas size="7" value="<?= $contrato->fields['retainer_horas'] ?>" />
						</div>
						<div id=div_fecha_cap align=left style="display:none; background-color:#C6DEAD;padding-left:2px;">
							<table style='border: 0px solid' bgcolor='#C6DEAD'>
							<? if($cobro){ ?>
							<tr>
								<td><?=__('Monto utilizado')?>:</td><td align=left>&nbsp;<label style='background-color:#FFFFFF'> <?=$cobro->TotalCobrosCap($contrato->fields['id_contrato']) > 0 ? $cobro->TotalCobrosCap($contrato->fields['id_contrato']) : 0;?> </label></td>
							</tr>
							<? }?>
							<tr>
								<td><?=__('Fecha inicio')?>:</td><td align=left valign='top'><input type="text" name="fecha_inicio_cap" value="<?=Utiles::sql2date($contrato->fields['fecha_inicio_cap'])?>" id="fecha_inicio_cap" size="11" maxlength="10" />
					<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha_inicio_cap" style="cursor:pointer" /></td>
							</tr>
							</table>
						</div>
					</div>
				</td>
			</tr>

			<tr>
				<td align=right><?=__('Descuento')?></td>
				<td align=left>
					<input type=text name=descuento id=descuento size=6 value=<?=$contrato->fields['descuento']?>> <input type=radio name=tipo_descuento id=tipo_descuento value='VALOR' <?=$contrato->fields['tipo_descuento'] == 'VALOR' ? 'checked' : '' ?> ><?=__('Valor')?>
					<br>
					<input type=text name=porcentaje_descuento id=porcentaje_descuento size=6 value=<?=$contrato->fields['porcentaje_descuento']?>> <input type=radio name=tipo_descuento id=tipo_descuento value='PORCENTAJE' <?=$contrato->fields['tipo_descuento'] == 'PORCENTAJE' ? 'checked' : '' ?>><?=__('%')?>
				</td>
			</tr>
		</table>

	<!--Samuel-->
		<table width="100%">
<?
		if($id_cliente||$id_asunto)
		{
?>
			<tr>
				<td colspan=2 align=center>
<?
					$id_contrato_ifr = $contrato->fields['id_contrato'];
?>
					<iframe name=iframe_documentos id=iframe_documentos src='documentos.php?id_cliente=<?=$cliente->fields['id_cliente']?>&id_contrato=<?=$id_contrato_ifr ?>' frameborder=0 width=650px height=370px></iframe>
				</td>
			</tr>
<?
		} #fin id_cliente OR id_asunto
?>
<?
		//Samuel: no sé para que se usa esto.
		/*if(!$motivo)
		{
?>
	  	<tr>
				<td align=right>
					<?=__('Asuntos')?>
				</td>
				<td align=left>
<?
					if($contrato->fields['codigo_cliente'] == "")
						echo __('Debes guardar primero el contrato para seleccionar los asuntos')."<br>";
			 		$lista_asuntos = new ListaObjetos($sesion,'',"SELECT codigo_asunto, glosa_asunto, id_contrato FROM asunto
			                                                    WHERE codigo_cliente = '".$contrato->fields['codigo_cliente']."' AND activo=1
			                                                    ORDER BY glosa_asunto");
					echo  "<select name='asuntos[]' id='asuntos' multiple size=6  style='width: 200px;'>";
					for($x=0;$x< $lista_asuntos->num;$x++)
					{
						$asunto = $lista_asuntos->Get($x);
		?>
						<option value='<?=$asunto->fields['codigo_asunto']?>' <?= ($contrato->fields['id_contrato'] == $asunto->fields['id_contrato']  ? "selected" : "") ?>><?=$asunto->fields['glosa_asunto']." (".$asunto->fields['id_contrato'].")"?></option>
		<?
					}
					echo "</select>";
?>
				</td>
			</tr>
<?
		}*/ #Fin if Motivo
?>
	 	</table>
	</div>
</fieldset>
<!-- FIN COBRANZA -->

<br>

<!-- CARTAS -->
<fieldset style="width: 98%">
	<legend <?=!$div_show ? 'onClick="MuestraOculta(\'datos_carta\')" style="cursor:pointer"' : ''?> >
		<?=!$div_show ? '<span id="datos_carta_img"><img src="'.Conf::ImgDir().'/mas.gif" border="0" id="datos_carta_img"></span>' : '' ?>&nbsp;<?=__('Carta')?></legend>
	<table id='datos_carta' style='display:<?=$show?>' width="100%">
		<tr>
			<td align=right colspan='1' width='25%'>
				<?=__('Idioma')?>
			</td>
			<td align=left colspan=5>
				<?= Html::SelectQuery($sesion,"SELECT codigo_idioma,glosa_idioma FROM prm_idioma ORDER BY glosa_idioma","codigo_idioma",$contrato->fields['codigo_idioma'],'','',80);?>
			</td>
		</tr>
		<tr>
			<td align=right colspan='1' width='25%'>
				<?=__('Formato Carta')?>
			</td>
			<td align=left colspan=5>
				<?= Html::SelectQuery($sesion, "SELECT carta.id_carta, carta.descripcion FROM carta ORDER BY id_carta","id_carta", $contrato->fields['id_carta'], ""); ?>
			</td>
		</tr>
		<tr>
			<td align="right" colspan='1'><?=__('Tamaño del papel')?>:</td>
			<td align="left" colspan='5'>
<?php
if ($contrato->fields['opc_papel'] == '' && UtilesApp::GetConf($sesion, 'PapelPorDefecto')) {
	$contrato->fields['opc_papel'] = UtilesApp::GetConf($sesion, 'PapelPorDefecto');
}
?>
				<select name="opc_papel">
					<option value="LETTER" <?php echo $contrato->fields['opc_papel'] == 'LETTER' ? 'selected="selected"' : '' ?>><?php echo __('Carta'); ?></option>
					<option value="LEGAL" <?php echo $contrato->fields['opc_papel'] == 'LEGAL' ? 'selected="selected"' : '' ?>><?php echo __('Oficio'); ?></option>
					<option value="A4" <?php echo $contrato->fields['opc_papel'] == 'A4' ? 'selected="selected"' : '' ?>><?php echo __('A4'); ?></option>
					<option value="A5" <?php echo $contrato->fields['opc_papel'] == 'A5' ? 'selected="selected"' : '' ?>><?php echo __('A5'); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<td align="right" colspan='1'><?=__('Mostrar total en')?>:</td>
			<td align="left" colspan='5'>
				<?=Html::SelectQuery( $sesion, "SELECT id_moneda, glosa_moneda FROM prm_moneda ORDER BY id_moneda", 'opc_moneda_total',$contrato->fields['opc_moneda_total'],'onchange="ActualizarTipoCambioOpcion(this.form, this.value);"','','60')?>
			</td>
		</tr>
		<?
		if(!$contrato->loaded())
			$checked = 'checked';
		else
			$checked = '';
		?>
		<tr>
			<td align="right" colspan='1'><input type="checkbox" name="opc_ver_modalidad" value="1" <?=$contrato->fields['opc_ver_modalidad']=='1'?'checked': ''?> <?=$checked?>></td>
			<td align="left" colspan='5'><?=__('Mostrar modalidad del cobro')?></td>
		</tr>
		<tr>
			<td align="right" colspan='1'><input type="checkbox" name="opc_ver_profesional" value="1" <?=$contrato->fields['opc_ver_profesional']=='1'?'checked':''?> <?=$checked?>></td>
			<td align="left" colspan='5'><?=__('Mostrar detalle por profesional')?></td>
		</tr>
		<tr>
			<td align="right" colspan='1'><input type="checkbox" name="opc_ver_gastos" value="1" <?=$contrato->fields['opc_ver_gastos']=='1'?'checked':''?> <?=$checked?>></td>
			<td align="left" colspan='5'><?=__('Mostrar gastos del cobro')?></td>
		</tr>
		<tr>
			<td align="right" colspan='1'><input type="checkbox" name="opc_ver_morosidad" value="1" <?=$contrato->fields['opc_ver_morosidad']=='1'?'checked':''?> <?=$checked?>></td>
			<td align="left" colspan='5'><?=__('Mostrar saldo adeudado')?></td>
		</tr>
		<tr>
			<td align="right" colspan='1'><input type="checkbox" name="opc_ver_numpag" value="1" <?=$contrato->fields['opc_ver_numpag']=='1'?'checked':''?> <?=$checked?>></td>
			<td align="left" colspan='5'><?=__('Mostrar números de página')?></td>
		</tr>
		<tr>
			<td align="right" colspan='1'><input type="checkbox" name="opc_ver_carta" value="1" onclick="ActivaCarta(this.checked)" <?=$contrato->fields['opc_ver_carta']=='1'?'checked':''?> <?=$checked ?>></td>
			<td align="left" colspan='5'><?=__('Mostrar Carta')?></td>
		</tr>
	</table>
</fieldset>
<br><br>
<!-- FIN CARTAS -->

<!-- GUARDAR -->
<? if($popup && !$motivo){ ?>
<fieldset style="width: 98%">
	<legend><?=__('Guardar datos')?></legend>
	<table>
		<tr>
	    <td colspan=6 align="center">
	        <input type=button class=btn value=<?=__('Guardar')?> onclick="ValidarContrato(this.form)" />
	  	</td>
		</tr>
	</table>
</fieldset>
<? } ?>
<!-- FIN GUARDAR -->

</fieldset>
<!-- FIN INFORMACION COMERCIAL GENERAL -->
<? if($popup && !$motivo){?>
</form>
<? } ?>
<br>
<?= InputId::Javascript($sesion) ?>
<? #if($popup && !$motivo){
?>
<script type="text/javascript">
ActualizarFormaCobro();
TogglePeriodico(document.formulario.es_periodico.checked);
Calendar.setup(
	{
		inputField	: "periodo_fecha_inicio",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_periodo_fecha_inicio"		// ID of the button
	}
);
Calendar.setup(
	{
		inputField	: "fecha_estimada_cobro",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_fecha_estimada_cobro"		// ID of the button
	}
);
Calendar.setup(
	{
		inputField	: "fecha_inicio_cap",
		ifFormat	: "%d-%m-%Y",
		button		: "img_fecha_inicio_cap"
	}
);
</script>
<? #}
?>