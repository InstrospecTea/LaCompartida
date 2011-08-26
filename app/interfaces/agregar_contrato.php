<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../app/classes/Contrato.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../app/classes/Cliente.php';
	require_once Conf::ServerDir().'/../app/classes/InputId.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/../app/classes/Moneda.php';
	require_once Conf::ServerDir().'/../app/classes/Tarifa.php';
	require_once Conf::ServerDir().'/../app/classes/TarifaTramite.php';
	require_once Conf::ServerDir().'/../app/classes/Funciones.php';
	require_once Conf::ServerDir().'/../app/classes/Cobro.php';
	require_once Conf::ServerDir().'/../app/classes/CobroPendiente.php';
	require_once Conf::ServerDir().'/../app/classes/Archivo.php';
	require_once Conf::ServerDir().'/../app/classes/ContratoDocumentoLegal.php';
	
	#Tooltips para las modalidades de cobro.
	$tip_tasa				= __("En esta modalidad se cobra hora a hora. Cada profesional tiene asignada su propia tarifa para cada asunto.");
	$tip_suma				= __("Es un único monto de dinero para el asunto. Aquí interesa llevar la cuenta de HH para conocer la rentabilidad del proyecto. Esta es la única modalida de ") . __("cobro") . __(" que no puede tener límites.");
	$tip_retainer			= __("El cliente compra un número de HH. El límite puede ser por horas o por un monto.");
	$tip_proporcional		= __("El cliente compra un número de horas, el exceso de horas trabajadas se cobra proporcional a la duración de cada trabajo.");
	$tip_flat				= __("El cliente acuerda cancelar un <strong>monto fijo</strong> por atender todos los trabajos de este asunto. Puede tener límites por HH o monto total");
	$tip_cap				= __("Cap");
	$tip_honorarios			= __("Solamente lleva la cuenta de las HH profesionales. Al terminar el proyecto se puede cobrar eventualmente.");
	$tip_mensual			= __("El cobro se hará de forma mensual.");
	$tip_tarifa_especial	= __("Al ingresar una nueva tarifa, esta se actualizará automáticamente.");
	$tip_subtotal			= __("El monto total ")  . __("del cobro") . __(" hasta el momento sin incluir descuentos.");
	$tip_descuento			= __("El monto del descuento.");
	$tip_total				= __("El monto total ")  . __("del cobro") . __(" hasta el momento incluidos descuentos.");
	$tip_actualizar			= __("Actualizar los montos");
	$tip_refresh			= __("Actualizar a cambio actual");

	$sesion = new Sesion(array('DAT'));
	$archivo = new Archivo($sesion);
	
	$query_permiso_tarifa = "SELECT count(*) 
														 FROM usuario_permiso 
														WHERE id_usuario = '".$sesion->usuario->fields['id_usuario']."' 
															AND codigo_permiso = 'TAR' ";
	$resp_permiso_tarifa = mysql_query($query_permiso_tarifa, $sesion->dbh) or Utiles::errorSQL($query_permiso_tarifa,__FILE__,__LINE__,$sesion->dbh);
	list( $cantidad_permisos ) = mysql_fetch_array($resp_permiso_tarifa);
	
	if( $cantidad_permisos > 0 ) 
		$tarifa_permitido = true;
	else
		$tarifa_permitido = false;
		
	$validaciones_segun_config = method_exists('Conf','GetConf') && Conf::GetConf($sesion,'ValidacionesCliente');
	$obligatorio = '<span class="req">*</span>';
	
	if($popup && !$motivo)
	{
		$pagina = new Pagina($sesion);
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


	$validaciones_segun_config = method_exists('Conf','GetConf') && Conf::GetConf($sesion,'ValidacionesCliente');
	$obligatorio = '<span class="req">*</span>';

	#CONTRATO GUARDA
	if($opcion_contrato == "guardar_contrato" && $popup && !$motivo)
	{
		$enviar_mail = 1;
		if($forma_cobro != 'TASA' && $monto == 0)
		{
			$pagina->AddError( __('Ud. ha seleccionado forma de ') . __('cobro').': '.$forma_cobro.' '.__('y no ha ingresado monto') );
			$val=true;
		}
		elseif($forma_cobro == 'TASA')
		{
			$monto = '0';
		}

		if($tipo_tarifa=='flat'){
			if(empty($tarifa_flat)){
				$pagina->AddError( __('Ud. ha seleccionado una tarifa plana pero no ha ingresado el monto') );
				$val=true;
			}
			else{
				$tarifa = new Tarifa($sesion);
				$id_tarifa = $tarifa->GuardaTarifaFlat($tarifa_flat, $id_moneda, $id_tarifa_flat);
			}
		}

		$contrato->Edit("glosa_contrato",$glosa_contrato);
		$contrato->Edit("codigo_cliente",$codigo_cliente);
		$contrato->Edit("id_usuario_responsable",( !empty($id_usuario_responsable) && $id_usuario_responsable != -1 ) ? $id_usuario_responsable : "NULL");
		if(!UtilesApp::GetConf($sesion, 'EncargadoSecundario')){
			$id_usuario_secundario = $id_usuario_responsable;
		}
		$contrato->Edit("id_usuario_secundario",(!empty($id_usuario_secundario) && $id_usuario_secundario != -1 ) ? $id_usuario_secundario : "NULL");
		$contrato->Edit("observaciones",$observaciones);
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TituloContacto') ) || ( method_exists('Conf','TituloContacto') && Conf::TituloContacto() ) )
			{
				$contrato->Edit("titulo_contacto",$titulo_contacto);
				$contrato->Edit("contacto",$nombre_contacto);
				$contrato->Edit("apellido_contacto",$apellido_contacto);
			}
		else	
			$contrato->Edit("contacto",$contacto);
		$contrato->Edit("fono_contacto",$fono_contacto_contrato);
		$contrato->Edit("email_contacto",$email_contacto_contrato);
		$contrato->Edit("direccion_contacto",$direccion_contacto_contrato);
		$contrato->Edit("id_pais",$id_pais);
		$contrato->Edit("id_cuenta",$id_cuenta);
		$contrato->Edit("es_periodico",$es_periodico);
		$contrato->Edit("activo",$activo_contrato ? 'SI' : 'NO');
		$contrato->Edit("usa_impuesto_separado", $impuesto_separado ? '1' : '0');
		$contrato->Edit("usa_impuesto_gastos", $impuesto_gastos ? '1' : '0');
		$contrato->Edit("periodo_fecha_inicio", $periodo_fecha_inicio);
		$contrato->Edit("periodo_repeticiones", $periodo_repeticiones);
		$contrato->Edit("periodo_intervalo", $periodo_intervalo);
		$contrato->Edit("periodo_unidad", $codigo_unidad);
		$monto=str_replace(',','.',$monto);//en caso de usar comas en vez de puntos
		$contrato->Edit("monto", $monto); 
		$contrato->Edit("id_moneda", $id_moneda); 
		$contrato->Edit("id_moneda_tramite", $id_moneda_tramite);
		$contrato->Edit("forma_cobro", $forma_cobro);
		$contrato->Edit("fecha_inicio_cap", Utiles::fecha2sql($fecha_inicio_cap));
		$retainer_horas=str_replace(',','.',$retainer_horas);//en caso de usar comas en vez de puntos
		$contrato->Edit("retainer_horas", $retainer_horas);
		$contrato->Edit("id_usuario_modificador", $sesion->usuario->fields['id_usuario']);
		$contrato->Edit("id_carta", $id_carta ? $id_carta : 'NULL');
		$contrato->Edit("id_formato", $id_formato ? $id_formato : 'NULL');
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
		$contrato->Edit("opc_ver_detalles_por_hora",$opc_ver_detalles_por_hora);
		$contrato->Edit("opc_ver_modalidad",$opc_ver_modalidad);
		$contrato->Edit("opc_ver_profesional",$opc_ver_profesional);
		$contrato->Edit("opc_ver_profesional_iniciales",$opc_ver_profesional_iniciales);
		$contrato->Edit("opc_ver_profesional_categoria",$opc_ver_profesional_categoria);
		$contrato->Edit("opc_ver_profesional_tarifa",$opc_ver_profesional_tarifa);
		$contrato->Edit("opc_ver_profesional_importe",$opc_ver_profesional_importe);
		$contrato->Edit("opc_ver_gastos",$opc_ver_gastos);
		$contrato->Edit("opc_ver_morosidad",$opc_ver_morosidad);
		$contrato->Edit("opc_ver_resumen_cobro",$opc_ver_resumen_cobro);
		$contrato->Edit("opc_ver_detalles_por_hora_iniciales",$opc_ver_detalles_por_hora_iniciales);
		$contrato->Edit("opc_ver_detalles_por_hora_categoria",$opc_ver_detalles_por_hora_categoria);
		$contrato->Edit("opc_ver_detalles_por_hora_tarifa",$opc_ver_detalles_por_hora_tarifa);
		$contrato->Edit("opc_ver_detalles_por_hora_importe",$opc_ver_detalles_por_hora_importe);
		$contrato->Edit("opc_ver_descuento",$opc_ver_descuento);
		$contrato->Edit("opc_ver_tipo_cambio",$opc_ver_tipo_cambio);
		$contrato->Edit("opc_ver_numpag",$opc_ver_numpag);
		$contrato->Edit("opc_ver_carta",$opc_ver_carta);
		$contrato->Edit("opc_papel",$opc_papel);
		$contrato->Edit("opc_moneda_total",$opc_moneda_total);
		$contrato->Edit("opc_moneda_gastos",$opc_moneda_gastos);
		$contrato->Edit("opc_ver_solicitante",$opc_ver_solicitante);
		$contrato->Edit("opc_ver_asuntos_separados",$opc_ver_asuntos_separados);
		$contrato->Edit("opc_ver_horas_trabajadas",$opc_ver_horas_trabajadas);
		$contrato->Edit("opc_ver_cobrable",$opc_ver_cobrable);
		if( $opc_restar_retainer )
			$contrato->Edit("opc_restar_retainer",$opc_restar_retainer);
		if( $opc_ver_detalle_retainer )
			$contrato->Edit("opc_ver_detalle_retainer",$opc_ver_detalle_retainer);
		$contrato->Edit("opc_ver_valor_hh_flat_fee",$opc_ver_valor_hh_flat_fee);
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
		$contrato->Edit("alerta_hh",$alerta_hh);
		$contrato->Edit("alerta_monto",$alerta_monto);
		$contrato->Edit("limite_hh",$limite_hh);
		$contrato->Edit("limite_monto",$limite_monto);

		$contrato->Edit("separar_liquidaciones", $separar_liquidaciones);

		if($contrato->Write())
		{
			#cobros pendientes
			CobroPendiente::EliminarPorContrato($sesion,$contrato->fields['id_contrato'] ? $contrato->fields['id_contrato'] : $id_contrato);
			for($i=2;$i <= sizeof($valor_fecha);$i++)
			{
				$cobro_pendiente=new CobroPendiente($sesion);
				$cobro_pendiente->Edit("id_contrato",$contrato->fields['id_contrato'] ? $contrato->fields['id_contrato'] : $id_contrato);
				$cobro_pendiente->Edit("fecha_cobro",Utiles::fecha2sql($valor_fecha[$i]));
				$cobro_pendiente->Edit("descripcion",$valor_descripcion[$i]);
				$cobro_pendiente->Edit("monto_estimado",$valor_monto_estimado[$i]);
				$cobro_pendiente->Write();
			}

			ContratoDocumentoLegal::EliminarDocumentosLegales($sesion, $contrato->fields['id_contrato'] ? $contrato->fields['id_contrato'] : $id_contrato);
			foreach ($docs_legales as $doc_legal) {
				if (empty($doc_legal['documento_legal']) or ( empty($doc_legal['honorario']) and empty($doc_legal['gastos_con_iva']) and empty($doc_legal['gastos_sin_iva']) )) {
					continue;
				}
				$contrato_doc_legal = new ContratoDocumentoLegal($sesion);
				$contrato_doc_legal->Edit('id_contrato', $contrato->fields['id_contrato']);
				$contrato_doc_legal->Edit('id_tipo_documento_legal', $doc_legal['documento_legal']);
				if (!empty($doc_legal['honorario'])) {
					$contrato_doc_legal->Edit('honorarios', 1);
				}
				if (!empty($doc_legal['gastos_con_iva'])) {
					$contrato_doc_legal->Edit('gastos_con_impuestos', 1);
				}
				if (!empty($doc_legal['gastos_sin_iva'])) {
					$contrato_doc_legal->Edit('gastos_sin_impuestos', 1);
				}
				$contrato_doc_legal->Edit('id_tipo_documento_legal', $doc_legal['documento_legal']);
				$contrato_doc_legal->Write();
			}
			
			$pagina->AddInfo(__('Contrato guardado con éxito'));
		}
		else
			$pagina->AddError($contrato->error);
	}

	$tarifa = new Tarifa($sesion);
	$tramite_tarifa = new TramiteTarifa($sesion);
	$tarifa_default = $tarifa->SetTarifaDefecto();
	$tramite_tarifa_default = $tramite_tarifa->SetTarifaDefecto();
	
	$idioma_default = $contrato->IdiomaPorDefecto($sesion); 

	if(empty($tarifa_flat) && !empty($contrato->fields['id_tarifa'])){
		$tarifa->Load($contrato->fields['id_tarifa']);
		$valor_tarifa_flat = $tarifa->fields['tarifa_flat'];
	}
	else if(!empty($tarifa_flat) && $tipo_tarifa!='flat'){
		$valor_tarifa_flat = null;
	}
	else $valor_tarifa_flat = $tarifa_flat;

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
<script type="text/javascript">
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
	<?php
	if( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NuevoModuloFactura') )
	{
	?>
	if (!validar_doc_legales(true)){
		return false;
	}
	<?php
	}
	?>
	
	<? if( $validaciones_segun_config ) { ?>
	// DATOS FACTURACION

	if(!form.factura_rut.value)
	{
		alert("<?=__('Debe ingresar el').' '.__('RUT').' '.__('del cliente')?>");
		form.factura_rut.focus();
		return false;
	}

	if(!form.factura_razon_social.value)
	{
		alert("<?=__('Debe ingresar la razón social del cliente')?>");
		form.factura_razon_social.focus();
		return false;
	}

	if(!form.factura_giro.value)
	{
		alert("<?=__('Debe ingresar el giro del cliente')?>");
		form.factura_giro.focus();
		return false;
	}

	if(!form.factura_direccion.value)
	{
		alert("<?=__('Debe ingresar la dirección del cliente')?>");
		form.factura_direccion.focus();
		return false;
	}

	if(form.id_pais.options[0].selected == true)
	{
		alert("<?=__('Debe ingresar el pais del cliente')?>");
		form.id_pais.focus();
		return false;
	}

	if(!form.cod_factura_telefono.value)
	{
		alert("<?=__('Debe ingresar el codigo de area del teléfono')?>");
		form.cod_factura_telefono.focus();
		return false;
	}

	if(!form.factura_telefono.value)
	{
		alert("<?=__('Debe ingresar el número de telefono')?>");
		form.factura_telefono.focus();
		return false;
	}

	// SOLICITANTE
	if(form.titulo_contacto.options[0].selected == true)
	{
		alert("<?=__('Debe ingresar el titulo del solicitante')?>");
		form.titulo_contacto.focus();
		return false;
	}

	if(!form.nombre_contacto.value)
	{
		alert("<?=__('Debe ingresar el nombre del solicitante')?>");
		form.nombre_contacto.focus();
		return false;
	}

	if(!form.apellido_contacto.value)
	{
		alert("<?=__('Debe ingresar el apellido del solicitante')?>");
		form.apellido_contacto.focus();
		return false;
	}

	if(!form.fono_contacto_contrato.value)
	{
		alert("<?=__('Debe ingresar el teléfono del solicitante')?>");
		form.fono_contacto_contrato.focus();
		return false;
	}

	if(!form.email_contacto_contrato.value)
	{
		alert("<?=__('Debe ingresar el email del solicitante')?>");
		form.email_contacto_contrato.focus();
		return false;
	}

	if(!form.direccion_contacto_contrato.value)
	{
		alert("<?=__('Debe ingresar la dirección de envío del solicitante')?>");
		form.direccion_contacto_contrato.focus();
		return false;
	}

	// DATOS DE TARIFICACION
	if(!(form.tipo_tarifa[0].checked || form.tipo_tarifa[1].checked))
	{
		alert("<?=__('Debe seleccionar un tipo de tarifa')?>");
		form.tipo_tarifa[0].focus();
		return false;
	}
	
	/* Revisa antes de enviar, que se haya escrito un monto si seleccionó tarifa plana */
	
	if( form.tipo_tarifa[1].checked && form.tarifa_flat.value.length == 0 )
	{
		alert("<?=__('Ud. ha seleccionado una tarifa plana pero no ha ingresado el monto.')?>");
		form.tarifa_flat.focus();
		return false;
	}

	/*if(!form.id_moneda.options[0].selected == true)
	{
		alert("<?=__('Debe seleccionar una moneda para la tarifa')?>");
		form.id_moneda.focus();
		return false;
	}*/

	if(!(form.forma_cobro[0].checked || form.forma_cobro[1].checked ||form.forma_cobro[2].checked ||form.forma_cobro[3].checked ||form.forma_cobro[4].checked ))
	{
		alert("<?=__('Debe seleccionar una forma de cobro') . __('para la tarifa')?>");
		form.forma_cobro[0].focus();
		return false;
	}
/*
	if(!form.opc_moneda_total.value)
	{
		alert("<?=__('Debe seleccionar una moneda para mostrar el total')?>");
		form.opc_moneda_total.focus();
		return false;
	}*/

	if(!form.observaciones.value)
	{
		alert("<?=__('Debe ingresar un detalle para la cobranza')?>");
		form.observaciones.focus();
		return false;
	}

<? } ?>
	
	if($('fc5').checked)
	{
			if(form.limite_monto.value == 0)
			{
					if(confirm('¿Desea generar una alerta cuando se supere el CAP?'))
						form.limite_monto.value = form.monto.value; 
			}
	}
	
	form.submit();
	if( window.opener )
		window.opener.Refrescar();
	return true;
}

function SetFormatoRut()
{
	var rut = $('rut').value;
	if( rut == "" )
		return true;
	while( rut.indexOf('.') != -1 )
		rut = rut.replace('.','');
	var con_raya = rut.indexOf('-');
	
	if( con_raya != -1 )
	{
		var arr_rut = rut.split('-');
		var rut = arr_rut[0];
		var dv  = arr_rut[1];
	}
	else
	{
		var dv = rut.substr(rut.length-1);
		var rut = rut.substr(0,rut.length-1);
	}
	var rut3 = rut.substr(rut.length-3,3);
	var rut2 = rut.substr(rut.length-6,3);
	var rut1 = rut.substr(0,rut.length-6);
	
	if(rut.length > 6)
		var rut = rut1 + '.' + rut2 + '.' + rut3 + '-' + dv;
	else
		var rut = rut2 + '.' + rut3 + '-' + dv;
	$('rut').value = rut;
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
function ShowProporcional()
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
	fc6 = $("fc6");

	if(fc1.checked)
		ShowTHH();
	else if(fc2.checked)
		ShowRetainer();
	else if(fc3.checked)
		ShowFlatFee();
	else if(fc5.checked)
		ShowCap();
	else if(fc6.checked)
		ShowProporcional();
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

function CreaTramiteTarifa(form, opcion)
{
	var form = $('formulario');
	if(opcion)
		nuevaVentana( 'Trámite_Tarifas', 600, 600, 'tarifas_tramites.php?popup=1', '' );
	else
	{
		var id_tramite_tarifa = form.id_tramite_tarifa.value;
		nuevaVentana( 'Trámite_Tarifas', 600, 600, 'tarifas_tramites.php?popup=1&id_tramite_tarifa_edicion='+id_tramite_tarifa, '' );
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
		text_window += '<span style="text-align:center; font-size:11px; color:#000; "><?=__('Ud. está desactivando este contrato, por lo tanto este contrato no aparecerá en la lista de la generación de ') . __('cobros')?>.</span><br>';
		text_window += '<br><table><tr>';
		text_window += '<td align="right"><span style="text-align:center; font-size:11px;color:#FF0000; "><?=__('¿Está seguro de desactivar este contrato?')?>:</span></td></tr>';
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
//Función que genera la tabla completa
function generarFechas()
{
	if($('periodo_fecha_inicio').value=='')
	{
		alert('No se ha seleccionado una fecha inicial');
		$('periodo_fecha_inicio').focus();
		return;
	}
	if($('periodo_intervalo').value=='0' || $('periodo_intervalo').value=='')
	{
		alert('No se ha seleccionado una periodicidad');
		$('periodo_intervalo').focus();
		return;
	}
	if($('valor_fecha_2') && !confirm('¿Está seguro que desea generar la tabla nuevamente?\n<?php echo __('El primer cobro'); ?> de la tabla será el '+$('periodo_fecha_inicio').value))
		return;
	//Se elimina la tabla para poner los nuevos datos
	eliminarTabla();
	//Se agregan los datos a la tabla
	addTable();
}
//Borra la tabla completa
function eliminarTabla()
{
	var filas = $('id_body').childElements().length;
	$('id_body').childElements().each(function(item){
		if(item.id!='fila_fecha_1') item.remove()
	});
}

//validacion fecha
function daysInFebruary (year)
{
	//February has 29 days in any year evenly divisible by four,
	//EXCEPT for centurial years which are not also divisible by 400.
	return (((year % 4 == 0) && ( (!(year % 100 == 0)) || (year % 400 == 0))) ? 29 : 28 );
}

function DaysArray(n)
{
	for (var i = 1; i <= n; i++)
	{
		this[i] = 31;
		if (i==4 || i==6 || i==9 || i==11) {this[i] = 30;}
		if (i==2) {this[i] = 29;}
	}
	return this;
}
<?
	#numeros de cobros existentes para ver cual sigue
	$query = "SELECT COUNT(*) FROM cobro_pendiente WHERE id_cobro IS NOT NULL AND id_contrato='".$contrato->fields['id_contrato']."'";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	list($numero_cobro) = mysql_fetch_array($resp);
?>
//agrega nuevos datos a la tabla segun la fecha inicial la periodicidad y el periodo total
function addTable()
{
	var daysInMonth = DaysArray(12);
	var periodo = parseInt($('periodo_intervalo').value);
	//se considera un periodo total de 2 años
	var cant_cobros = Math.floor(24/periodo);
	var repeticiones = parseInt($('periodo_repeticiones').value);
	//si las repeticiones son menores que la cantidad de cobros
	if(repeticiones > 0)
		cant_cobros=repeticiones;
	var fecha_inicio = $('periodo_fecha_inicio').value.split('-');
	var mes=parseInt(fecha_inicio[1]);
	var anio=parseInt(fecha_inicio[2]);
	var dia_str = '';
	var mes_str = '';
	var numero_cobro = 0;
	for(i=1;i<=cant_cobros;i++)
	{
		var dia=parseInt(fecha_inicio[0]);
		if(i==1)
		{
			$('valor_fecha_1').value=$('periodo_fecha_inicio').value;
		}
		else
		{
			mes=mes+periodo;
			if(mes > 12)
			{
				mes=mes-12;
				anio++;
			}
			if(mes < 10)
			{
				mes_str='0'+mes;
			}
			else
			{
				mes_str=mes;
			}

			if ((mes==2 && dia>daysInFebruary(anio)) || dia > daysInMonth[mes])
			{
				if(mes==2)
					dia = daysInFebruary(anio);
				else
					dia = daysInMonth[mes];
			}
			if(dia < 10)
			{
				dia_str='0'+dia;
			}
			else
			{
				dia_str=dia;
			}
			$('valor_fecha_1').value=dia_str+'-'+mes_str+'-'+anio;
		}
		numero_cobro= <?=$numero_cobro ?>+i;
		$('valor_descripcion_1').value="<?php echo __('Cobro N°'); ?> "+numero_cobro;
		if($('fc3').checked==true)
			$('valor_monto_estimado_1').value=$('monto').value;
		else
			$('valor_monto_estimado_1').value='';
		agregarFila();
	}
}
function eliminarFila(fila)
{
	$('tabla_fechas').deleteRow(fila);
	actualizarTabla();
}
function agregarFila()
{
	var largo = $('tabla_fechas').rows.length;
	if($('valor_fecha_1').value!='')
	{
		var temp1=$('valor_fecha_1').value.split('-');
		var nueva_fecha_orden=new Date(temp1[2],temp1[1],temp1[0]);
		for (var i=largo-1;i>1;i--)
		{
			temp2=$('valor_fecha_'+i).value.split('-');
			var temp_fecha_orden=new Date(temp2[2],temp2[1],temp2[0]);
			if(nueva_fecha_orden.getTime()>temp_fecha_orden.getTime())
			{
				break;
			}
		}
	}
	else
	{
		alert('No se ha ingresado una fecha.');
		return;
	}
	var fila= $('tabla_fechas').insertRow(i+1);
	var fecha=fila.insertCell(0);
	var descripcion=fila.insertCell(1);
	var monto=fila.insertCell(2);
	var borrar=fila.insertCell(3);
	fecha.innerHTML="<input type='hidden' class='fecha' value='"+$('valor_fecha_1').value+"' />"+$('valor_fecha_1').value;
	descripcion.innerHTML="<input type='text' class='descripcion' size='40' value='"+$('valor_descripcion_1').value+"' />";
	monto.innerHTML="<span class='moneda_tabla' align='center'></span>&nbsp;&nbsp;<input type='text' class='monto_estimado' size='7' value='"+$('valor_monto_estimado_1').value+"' />";
	borrar.innerHTML="<img src='<?=Conf::ImgDir()?>/eliminar.gif' style='cursor:pointer' onclick='eliminarFila(this.parentNode.parentNode.rowIndex);' />";
	$('valor_fecha_1').value = '';
	$('valor_descripcion_1').value = '';
	$('valor_monto_estimado_1').value = '';
	actualizarTabla();
}
function detallesTabla()
{
	for(var i=$('tabla_fechas').rows.length-1;i>6;i--)
	{
		if($('fila_fecha_'+i)) $('fila_fecha_'+i).toggle();
	}
	$('detalles_tabla_mostrar').toggle();
	$('detalles_tabla_esconder').toggle();
}
function actualizarTabla()
{
	var x=2;
	$$('.fecha').each(
		function(item)
		{
			item.id="valor_fecha_"+x;
			item.name="valor_fecha["+x+"]";
			x++;
		}
	);
	x=2;
	$$('.descripcion').each(
		function(item)
		{
			item.id="valor_descripcion_"+x;
			item.name="valor_descripcion["+x+"]";
			x++;
		}
	);
	x=2;
	$$('.monto_estimado').each(
		function(item)
		{
			item.id="valor_monto_estimado_"+x;
			item.name="valor_monto_estimado["+x+"]";
			x++;
		}
	);
	var largo = $('tabla_fechas').rows.length;
	for (var i = 2;i < largo;i++)
	{
		var fila = $('tabla_fechas').rows[i];
		fila.id="fila_fecha_"+i;
		var celda_a = fila.cells[0];
		var celda_b = fila.cells[1];
		var celda_c = fila.cells[2];
		var celda_d = fila.cells[3];
		celda_a.style.textAlign="center";
		celda_b.style.textAlign="left";
		celda_c.style.textAlign="right";
		celda_d.style.textAlign="center";
		if($('detalles_tabla_esconder').getStyle('display')=='none' && $('fila_fecha_'+i).getStyle('display')!='none' && i>6 )
			$('fila_fecha_'+i).toggle();
		if($('detalles_tabla_esconder').getStyle('display')=='none' && $('fila_fecha_'+i).getStyle('display')=='none' && i==6 )
			$('fila_fecha_'+i).toggle();
		if(i % 2 == 0) $('fila_fecha_'+i).bgColor="#f0f0f0";
		else $('fila_fecha_'+i).bgColor="#ffffff";
	}
	actualizarMonto();
	actualizarMoneda();
}
var simbolo = new Array();
<?
$query = "SELECT id_moneda,simbolo FROM prm_moneda";
$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
while(list($id_moneda_tabla,$simbolo_tabla) = mysql_fetch_array($resp))
{
	//echo $id_moneda_tabla;
?>
simbolo[<?=$id_moneda_tabla?>] = "<?=$simbolo_tabla?>";
<?
}
?>
function actualizarMoneda()
{
	var id_moneda=$('id_moneda').value;
	$$('.moneda_tabla').each(
		function(item)
		{
			item.innerHTML=simbolo[id_moneda];
		}
	);
}

function actualizarMonto()
{
	var id_moneda=$('id_moneda_monto').value;
	$$('.moneda_tabla').each(
		function(item)
		{
			item.innerHTML=simbolo[id_moneda];
		}
	);
	var monto=$('monto').value;
	if($('fc3').checked==true)
	{
		$$('.monto_estimado').each(
			function(item)
			{
				item.value=monto;
			}
		);
	}
}

/**
 * Detectar la selección de separar liquidaciones
 */
function mostrarOpcionMonedaParaGastos(check) {
	if (check.checked) {
		$('monedas_para_honorarios_y_gastos').show();
	} else {
		$('monedas_para_honorarios_y_gastos').hide();
	}
}
 
document.observe("dom:loaded", function() {
	$('separar_liquidaciones').observe('click', function(event) {
		var check = $(Event.element(event));
		mostrarOpcionMonedaParaGastos(check);
	});
	
	mostrarOpcionMonedaParaGastos($('separar_liquidaciones'));
});

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
   
	
	var respuesta_revisar_tarifa = false;
	
	function RevisarTarifasRequest( tarifa, moneda )
	{
		//loading("Verificanco datos");
		//cargando = true;
		var text_window = "";
		
		var http = getXMLHTTP();
		var url = 'ajax.php?accion=revisar_tarifas&id_tarifa=' + $(tarifa).value + '&id_moneda=' + $(moneda).value;
		if(http) {

			http.open('get',url,false);
			http.send(null);

			var response = http.responseText;
			return response;
		}
		//cargando = false;
		//offLoading();
		return "0::&nbsp;::0";
	}
	
	function RevisarTarifas(tarifa, moneda, f, desde_combo)
	{
		var ejecutar = true;
		if ( !desde_combo )
		{
			radio_tarifas = document.getElementsByName('tipo_tarifa');
			var seleccionado = "";
			for( k=0; k < radio_tarifas.length; k++ )
			{
				if( radio_tarifas[k].checked )
				{
					seleccionado = radio_tarifas[k].value;
				}
			}
			if( seleccionado == 'flat')
			{
				ejecutar = false;
			}
			else
			{
				ejecutar = true;
			}
		}
		
		if( ejecutar ) // cant::lista
		{
			var text_window = "";
			var respuesta = RevisarTarifasRequest(tarifa, moneda);
			var parts = respuesta.split("::");
			var todos = false;
			if( parts[0] > 0)
			{
				text_window += "<img src='<?=Conf::ImgDir()?>/alerta_16.gif'>&nbsp;&nbsp;<span style='font-size:12px; color:#FF0000; text-align:center;font-weight:bold'><u><?=__("ALERTA")?></u><br><br></span>";
				if( parts[0] < 10 )
				{
					text_window += '<span style="font-size:12px; text-align:center;font-weight:bold"><?=__('Listado de usuario con tarifa sin valor para la moneda seleccionada.')?></span><br><br>';
					text_window += '<span style="font-size:12px; text-align:left;">' + parts[1] + '</span><br><br>';
					todos = false;
				}
				else if( parts[0] == parts[2] )
				{
					text_window += '<span style="font-size:12px; text-align:center;font-weight:bold"><?=__('La tarifa seleccionada no tiene valor definido en la moneda elegida.')?></span><br><br>';
					todos = true;
				}
				else
				{
					text_window += '<span style="font-size:12px; text-align:center;font-weight:bold"><?=__('Hay más de 10 abogados sin valor para la tarifa y moneda seleccionadas.')?></span><br><br>';
					todos = false;
				}
				text_window += '<span style="font-size:12px; text-align:left;"><a href="javascript:;" onclick="CreaTarifa(this.form,false)"><?=__('Modificar tarifa.')?></a></span>';

				if( todos && !desde_combo )
				{
					Dialog.alert(text_window, 
					{
						top:100, left:80, width:400, okLabel: "<?=__('Cerrar')?>",
						buttonClass: "btn", className: "alphacube", id: 'myDialogId', destroyOnClose: true,
						ok:function(win){ 
								document.getElementById('id_tarifa').value=document.getElementById('id_tarifa_hidden').value;
								document.getElementById('id_moneda').value=document.getElementById('id_moneda_hidden').value;
								document.getElementById('id_moneda').focus();
								win.close();
								return false;
							}
					});
				}
				else
				{
					Dialog.confirm(text_window, 
					{
						top:100, left:80, width:400, okLabel: "<?php echo __('Continuar')?>", cancelLabel: "<?php echo __('Cancelar')?>", buttonClass: "btn", className: "alphacube",
						id: "myDialogId",
						cancel:function(win){ 
							document.getElementById('id_tarifa').value=document.getElementById('id_tarifa_hidden').value;
							document.getElementById('id_moneda').value=document.getElementById('id_moneda_hidden').value;
							document.getElementById('id_moneda').focus();
							respuesta_revisar_tarifa = false; 
							return respuesta_revisar_tarifa; 
						},
						ok:function(win){ 
							respuesta_revisar_tarifa = true;
							if( !desde_combo )
							{
								if( f.desde.value == 'agregar_cliente' || f.desde.value == 'agregar_asunto')
								{
									Validar(f);
								}
								else
								{
									ValidarContrato(f);
								}
							}
							else
							{
								document.getElementById('id_tarifa_hidden').value=document.getElementById('id_tarifa').value;
								document.getElementById('id_moneda_hidden').value=document.getElementById('id_moneda').value;
							}
							return respuesta_revisar_tarifa; 
						}
					});
				}
			}
			else
			{
				respuesta_revisar_tarifa = true;
				if( !desde_combo )
				{
					if( f.desde.value == 'agregar_cliente' || f.desde.value == 'agregar_asunto' )
					{
						Validar(f);
					}
					else
					{
						ValidarContrato(f);
					}
				}
				return respuesta_revisar_tarifa;
			}
		}
		else
		{
			if( !desde_combo )
			{
				if( f.desde.value == 'agregar_cliente' || f.desde.value == 'agregar_asunto' )
				{
					Validar(f);
				}
				else
				{
					ValidarContrato(f);
				}
			}
		}
	}

	var mismoEncargado = <?= UtilesApp::GetConf($sesion, 'EncargadoSecundario') && $contrato->fields['id_usuario_responsable'] == $contrato->fields['id_usuario_secundario'] ? 'true' : 'false' ?>;
	function CambioEncargado(){
		if(mismoEncargado){
			if(confirm('¿Desea cambiar también el <?=__('Encargado Secundario')?>?')){
				$('id_usuario_secundario').value = $('id_usuario_responsable').value;
			}
			else{
				mismoEncargado = false;
			}
		}
	}
</script>
<? if($popup && !$motivo){?>
<form name='formulario' id='formulario' method=post>
<input type=hidden name=codigo_cliente value="<?=$cliente->fields['codigo_cliente'] ? $cliente->fields['codigo_cliente'] : $codigo_cliente ?>" />
<input type=hidden name=opcion_contrato value="guardar_contrato" />
<input type=hidden name='id_contrato' value="<?=$contrato->fields['id_contrato'] ?>" />
<input type="hidden" name="desde" value="agregar_contrato" />
<? } ?>
<br>
<!-- Calendario DIV -->
<div id="calendar-container" style="width:221px; position:absolute; display:none;">
	<div class="floating" id="calendar"></div>
</div>

<!-- Fin calendario DIV -->
<fieldset style="width: 97%;" class="tb_base" style="border: 1px solid #BDBDBD;">
	<legend>&nbsp;<?=__('Información Comercial')?></legend>

<!-- RESPONSABLE -->
<table id='responsable' style='display:inline'>
	<tr>
		<td align="left" width='30%'>
			<?=__('Activo')?>
		</td>
<?
		if(!$contrato->loaded())
			$chk = 'checked="checked"';
		else
			$chk = '';
?>
		<td align="left" width = '70%'>
			<input type=checkbox name=activo_contrato id=activo_contrato value=1 <?=$contrato->fields['activo'] == 'SI' ? 'checked="checked"' : ''?> <?=$chk ?> onclick=InactivaContrato(this.checked) />
			&nbsp;<span><?=__('Los contratos inactivos no aparecen en el listado de cobranza.')?></span>
		</td>
	</tr>
<?
if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarImpuestoSeparado') ) || ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ) )
{
?>
	<tr>
		<td align="left" width='30%'>
			<?=__('Usa impuesto a honorario')?>
		</td>
<?
		// Se revisa también el primer contrato del cliente para el valor por defecto.
		if( $contrato->loaded() )
			{
				if( $contrato->fields['usa_impuesto_separado'] )
					$chk = 'checked="checked"';
				else
					$chk = '';
			}
		else if( Utiles::Glosa($sesion, $cliente->fields['id_contrato'], 'usa_impuesto_separado', 'contrato') )
			$chk = 'checked="checked"';
		else
			$chk = '';
?>
		<td align="left" width = '70%'>
			<input type="checkbox" name="impuesto_separado" id="impuesto_separado" value="1" <?=$chk ?> />
		</td>
	</tr>
<?
}
?>

<? 
if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarImpuestoPorGastos') ) || ( method_exists('Conf','UsarImpuestoPorGastos') && Conf::UsarImpuestoPorGastos() ) )
{
?>
	<tr>
		<td align="left" width='30%'>
			<?=__('Usa impuesto a gastos')?>
		</td>
<?
		// Se revisa también el primer contrato del cliente para el valor por defecto.
		if( $contrato->loaded() )
			{
				if( $contrato->fields['usa_impuesto_gastos'] )
					$chk_gastos = 'checked="checked"';
				else
					$chk_gastos = '';
			}
		else if(  Utiles::Glosa($sesion, $cliente->fields['id_contrato'], 'usa_impuesto_gastos', 'contrato') )
			$chk_gastos = 'checked="checked"';
		else
			$chk_gastos = '';
?>
		<td align="left" width = '70%'>
			<input type="checkbox" name="impuesto_gastos" id="impuesto_gastos" value="1" <?=$chk_gastos ?> />
		</td>
	</tr>
<?
}
?>
<tr>
	<td align="left"><?=__('Liquidar por separado (Por honorario y gastos)')?></td>
	<td align="left"><input id="separar_liquidaciones" type="checkbox" name="separar_liquidaciones" value="1" <?=$contrato->fields['separar_liquidaciones']=='1'?'checked="checked"':''?>  /></td>
</tr>
<?
	$query = "SELECT usuario.id_usuario,CONCAT_WS(' ',apellido1,apellido2,',',nombre)
				FROM usuario JOIN usuario_permiso USING(id_usuario)
				WHERE codigo_permiso='SOC' ORDER BY apellido1";
?>
	<tr>
		<td align="left" width='30%'>
			<?=__('Encargado Comercial')?>
		</td>
		<td align="left" width = '70%'>
			<?= Html::SelectQuery($sesion, $query,"id_usuario_responsable", $contrato->fields['id_usuario_responsable'] ? $contrato->fields['id_usuario_responsable']:'', 'onchange="CambioEncargado()"',"Vacio","200");?>
		</td>
	</tr>
<?	if(UtilesApp::GetConf($sesion, 'EncargadoSecundario')){
		$query = "SELECT usuario.id_usuario,CONCAT_WS(' ',apellido1,apellido2,',',nombre)
				FROM usuario
				WHERE activo = 1 OR id_usuario = '".$contrato->fields['id_usuario_secundario']."'
				ORDER BY apellido1";
?>
	<tr>
		<td align="left" width='30%'>
			<?=__('Encargado Secundario')?>
		</td>
		<td align="left" width = '70%'>
			<?= Html::SelectQuery($sesion, $query,"id_usuario_secundario", $contrato->fields['id_usuario_secundario'] ? $contrato->fields['id_usuario_secundario']:'', "","Vacio","200");?>
		</td>
	</tr>
	<?php } ?>
</table>
<br><br>
<!-- FIN RESPONSABLE -->

<!-- DATOS FACTURACION -->
<fieldset style="width: 97%;background-color: #FFFFFF;">
	<legend <?=!$div_show ? 'onClick="MuestraOculta(\'datos_factura\')" style="cursor:pointer"' : ''?>>
		<?=!$div_show ? '<span id="datos_factura_img"><img src="'.Conf::ImgDir().'/mas.gif" border="0" id="datos_factura_img"></span>': '' ?>
		&nbsp;<?=__('Datos Facturación') ?></legend>
	<table id='datos_factura' style='display:<?=$show?>'>
	<tr>
		<td align="right" width='20%'>
			<?=__('ROL/RUT') ?>
			<?php if ($validaciones_segun_config) echo $obligatorio ?>
		</td>
		<td align="left" colspan="3">
        	<input type="text" size=20 name="factura_rut" id="rut" value="<?= $contrato->fields['rut'] ?>" onblur="SetFormatoRut();validarUnicoCliente(this.value,'rut');" />
		</td>
	</tr>
   	<tr>
		<td align="right" colspan="1">
			<?=__('Razón Social')?>
			<?php if ($validaciones_segun_config) echo $obligatorio ?>
		</td>
		<td align="left" colspan="5">
			<input name='factura_razon_social' size=50 value="<?= $contrato->fields['factura_razon_social'] ?>"  />
		</td>
	</tr>
	<tr>
		<td align="right" colspan="1">
			<?=__('Giro')?>
			<?php if ($validaciones_segun_config) echo $obligatorio ?>
		</td>
		<td align="left" colspan="5">
			<input name='factura_giro' size=50 value="<?= $contrato->fields['factura_giro'] ?>"  />
		</td>
	</tr>
	<tr>
		<td align="right" colspan="1">
			<?=__('Dirección')?>
			<?php if ($validaciones_segun_config) echo $obligatorio ?>
		</td>
		<td align="left" colspan="5">
			<textarea name='factura_direccion' rows=4 cols="55" ><?= $contrato->fields['factura_direccion'] ?></textarea>
		</td>
	</tr>
	<tr>
		<td align="right" colspan="1">
			<?=__('País')?>
		</td>
		<td align="left" colspan='3'>
			<?= Html::SelectQuery($sesion, "SELECT id_pais, nombre FROM prm_pais ORDER BY preferencia DESC, nombre ASC","id_pais", $contrato->fields['id_pais'] ? $contrato->fields['id_pais'] : '', '','Vacio',260); ?>&nbsp;&nbsp;
		</td>
	</tr>
	<tr>
		<td align="right" colspan="1">
			<?=__('Teléfono')?>
			<?php if ($validaciones_segun_config) echo $obligatorio ?>
		</td>
		<td align="left" colspan="5">
			<input name='cod_factura_telefono' size=8 value="<?= $contrato->fields['cod_factura_telefono'] ?>" />&nbsp;<input name='factura_telefono' size=30 value="<?= $contrato->fields['factura_telefono'] ?>" />
		</td>
	</tr>
	<tr>
		<td align="right" colspan="1">
			<?=__('Glosa factura')?>
		</td>
		<td align="left" colspan="5">
			<textarea name='glosa_contrato' rows=4 cols="55" ><?= $contrato->fields['glosa_contrato'] ?></textarea>
		</td>
	</tr>
	<tr>
       <td align="right" colspan="1">
           <?=__('Banco')?>
       </td>
       <td align="left" colspan="5">
           <?=Html::SelectQuery($sesion,"SELECT id_banco, nombre FROM prm_banco ORDER BY orden", "id_banco", $contrato->fields['id_banco'] ? $contrato->fields['id_banco'] : $id_banco, 'onchange="CargarCuenta(\'id_banco\',\'id_cuenta\');"',"Cualquiera","150")?>
       </td>
   </tr>
   <tr>
       <td align="right" colspan="1">
           <?=__('Cuenta')?>
       </td>
       <td align="left" colspan="5">
           <?=Html::SelectQuery($sesion,"SELECT cuenta_banco.id_cuenta
																						, CONCAT( cuenta_banco.numero,
																						     IF( prm_moneda.glosa_moneda IS NOT NULL , CONCAT(' (',prm_moneda.glosa_moneda,')'),  '' ) ) AS NUMERO
																						FROM cuenta_banco
																						LEFT JOIN prm_moneda ON prm_moneda.id_moneda = cuenta_banco.id_moneda", "id_cuenta", $contrato->fields['id_cuenta'] ? $contrato->fields['id_cuenta'] : $id_cuenta, 'onchange="SetBanco(\'id_cuenta\',\'id_banco\');"',"Cualquiera","150")?>
       </td>
   </tr>

	</table>
	<?php
	   if($contrato->fields['id_cuenta']>0) {
	       ?>
	   <script>
	   SetBanco('id_cuenta','id_banco');
	   </script>
	   <?php
	   }
	?>
</fieldset>
<!-- FIN DATOS FACTURACION -->
<br>


<!-- SOLICITANTE -->
<fieldset style="width: 97%; background-color: #FFFFFF;">
	<legend <?=!$div_show ? 'onClick="MuestraOculta(\'datos_solicitante\')" style="cursor:pointer"' : ''?> >
		<?=!$div_show ? '<span id="datos_solicitante_img"><img src="'.Conf::ImgDir().'/mas.gif" border="0" id="datos_solicitante_img"></span>' : ''?>
		&nbsp;<?=__('Solicitante')?></legend>
	<table id='datos_solicitante' style='display:<?=$show?>'>
<?
if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TituloContacto') ) || ( method_exists('Conf','TituloContacto') && Conf::TituloContacto() ) )
{
?> 
	<tr>
		<td align="right" width="20%">
			<?=__('Titulo')?>
			<?php if ($validaciones_segun_config) echo $obligatorio ?>
		</td>
		<td align="left" colspan='3'>
			<?= Html::SelectQuery($sesion, "SELECT titulo, glosa_titulo FROM prm_titulo_persona ORDER BY id_titulo","titulo_contacto", $contrato->fields['titulo_contacto'] ? $contrato->fields['titulo_contacto'] : '', '','Vacio',65); ?>&nbsp;&nbsp;
		</td>
	</tr>
	<tr>
		<td align="right" width='20%'>
			<?=__('Nombre')?>
			<?php if ($validaciones_segun_config) echo $obligatorio ?>
		</td>
		<td align='left' colspan='3'>
			<input type="text" size='55' name="nombre_contacto" id="nombre_contacto" value="<?= $contrato->fields['contacto'] ?>" />
		</td>
	</tr>
	<tr>
		<td align="right" width='20%'>
			<?=__('Apellido')?>
			<?php if ($validaciones_segun_config) echo $obligatorio ?>
		</td>
		<td align='left' colspan='3'>
			<input type="text" size='55' name="apellido_contacto" id="apellido_contacto" value="<?= $contrato->fields['apellido_contacto'] ?>"  />
		</td>
	</tr>
<?
}
else
{
?>
	<tr>
		<td align="right" width='20%'>
			<?=__('Nombre')?>
			<?php if ($validaciones_segun_config) echo $obligatorio ?>
		</td>
		<td align='left' colspan='3'>
			<input type="text" size='55' name="contacto" id="contacto" value="<?= $contrato->fields['contacto'] ?>"  />
		</td>
	</tr>
<?
}
?>
    <tr>
		<td align="right" colspan="1">
			<?=__('Teléfono')?>
			<?php if ($validaciones_segun_config) echo $obligatorio ?>
		</td>
		<td align="left" colspan="5">
			<input name='fono_contacto_contrato' size=30 value="<?= $contrato->fields['fono_contacto'] ?>" />
		</td>
	</tr>
    <tr>
		<td align="right" colspan="1">
			<?=__('E-mail')?>
			<?php if ($validaciones_segun_config) echo $obligatorio ?>
		</td>
		<td align="left" colspan="5">
			<input name='email_contacto_contrato' size=55 value="<?= $contrato->fields['email_contacto'] ?>"  />
		</td>
	</tr>
    <tr>
		<td align="right" colspan="1">
			<?=__('Dirección envío')?>
			<?php if ($validaciones_segun_config) echo $obligatorio ?>
		</td>
		<td align="left" colspan="5">
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
		
	if(!$id_moneda )
		$id_moneda = Moneda::GetMonedaTarifaPorDefecto($sesion);
	if(!$id_moneda)
		$id_moneda = Moneda::GetMonedaBase($sesion);
	
	if(!$id_moneda_tramite )
		$id_moneda_tramite = Moneda::GetMonedaTramitePorDefecto($sesion);
	
	if(!$opc_moneda_total)
		$opc_moneda_total = Moneda::GetMonedaTotalPorDefecto($sesion);
	if(!$opc_moneda_total)
		$opc_moneda_total = Moneda::GetMonedaBase($sesion);
	
	$config_validar_tarifa = ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'RevisarTarifas') ? ' RevisarTarifas( \'id_tarifa\', \'id_moneda\', this.form, true);' : '' );

?>

<!-- COBRANZA -->
<fieldset style="width: 97%; background-color: #FFFFFF;">
	<legend <?=!$div_show ? 'onClick="MuestraOculta(\'datos_cobranza\')" style="cursor:pointer"' : ''?> />
		<?=!$div_show ? '<span id="datos_cobranza_img"><img src="'.Conf::ImgDir().'/mas.gif" border="0" id="datos_cobranza_img"></span>' : '' ?>
		&nbsp;<?=__('Datos de Tarificación')?>
	</legend>
	<div id='datos_cobranza' style='display:<?=$show?>' width="100%">
		<table width="100%">
			<tr>
				<td align="right" width="25%" style="font-size:10pt;">
					<?=__('Tarifa horas')?>
					<?php if ($validaciones_segun_config) echo $obligatorio ?>
				</td>
				<td align="left" width="75%" style="font-size:10pt;">
					<table>
						<tr>
							<td>
								<input type="radio" name="tipo_tarifa" id="tipo_tarifa_variable" value="variable" <?=empty($valor_tarifa_flat) ? 'checked' : ''?>/>
								<?= Html::SelectQuery($sesion, "SELECT tarifa.id_tarifa, tarifa.glosa_tarifa FROM tarifa WHERE tarifa_flat IS NULL ORDER BY tarifa.glosa_tarifa","id_tarifa", $contrato->fields['id_tarifa'] ? $contrato->fields['id_tarifa'] : $tarifa_default, 'onclick="$(\'tipo_tarifa_variable\').checked = true;" ' . ( strlen($config_validar_tarifa) > 0 ? 'onchange="' . $config_validar_tarifa . '"' : '') ); ?>
								<input type="hidden" name="id_tarifa_hidden" id="id_tarifa_hidden" value="<?php echo $contrato->fields['id_tarifa'] ? $contrato->fields['id_tarifa'] : $tarifa_default; ?>" />
								<br/>
								<input type="radio" name="tipo_tarifa" id="tipo_tarifa_flat" value="flat" <?=empty($valor_tarifa_flat) ? '' : 'checked'?>/>
								<label for="tipo_tarifa_flat">Plana por </label>
								<input id="tarifa_flat" name="tarifa_flat" onclick="$('tipo_tarifa_flat').checked = true" value="<?=$valor_tarifa_flat?>"/>
								<input type="hidden" id="id_tarifa_flat" name="id_tarifa_flat" value="<?=$contrato->fields['id_tarifa']?>"/>
							</td>
							<td>
								<?=__('Tarifa en')?>
								<?php if ($validaciones_segun_config) echo $obligatorio ?>
								<?= Html::SelectQuery($sesion, "SELECT id_moneda,glosa_moneda FROM prm_moneda ORDER BY id_moneda","id_moneda", $contrato->fields['id_moneda'] ? $contrato->fields['id_moneda'] : $id_moneda, 'onchange="actualizarMoneda(); ' . $config_validar_tarifa . ' "','',"80"); ?>
								<input type="hidden" name="id_moneda_hidden" id="id_moneda_hidden" value="<?php echo $contrato->fields['id_moneda'] ? $contrato->fields['id_moneda'] : $id_moneda; ?>" />
								&nbsp;&nbsp;
								<?php if( $tarifa_permitido ) { ?> 
									<span style='cursor:pointer' <?=TTip(__('Agregar nueva tarifa'))?> onclick='CreaTarifa(this.form,true)'><img src="<?=Conf::ImgDir()?>/mas.gif" border="0"></span>
									<span style='cursor:pointer' <?=TTip(__('Editar tarifa seleccionada'))?> onclick='CreaTarifa(this.form,false)'><img src="<?=Conf::ImgDir()?>/editar_on.gif" border="0"></span>
								<?php } ?>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		  <tr>
				<td align="right" style="font-size:10pt;">
					<?=__('Forma de cobro')?>
					<?php if ($validaciones_segun_config) echo $obligatorio ?>
				</td>
	<?
				if(!$contrato->fields['forma_cobro'])
					$contrato_forma_cobro = 'TASA';
				else
					$contrato_forma_cobro = $contrato->fields['forma_cobro'];
	?>
				<td align="left" style="font-size:10pt;">
					<div id="div_cobro" align="left">
						<input <?= TTip($tip_tasa) ?> onclick="ActualizarFormaCobro()" id=fc1 type=radio name=forma_cobro value=TASA <?= $contrato_forma_cobro == "TASA" ? "checked='checked'" : "" ?> />
						<label for="fc1">Tasas/HH</label>&nbsp; &nbsp;
						<input <?= TTip($tip_retainer) ?> onclick="ActualizarFormaCobro();" id=fc2 type=radio name=forma_cobro value=RETAINER <?= $contrato_forma_cobro == "RETAINER" ? "checked='checked'" : "" ?> />
						<label for="fc2">Retainer</label> &nbsp; &nbsp;
						<input <?= TTip($tip_flat) ?> id=fc3 onclick="ActualizarFormaCobro();" type=radio name=forma_cobro value="FLAT FEE" <?= $contrato_forma_cobro == "FLAT FEE" ? "checked='checked'" : "" ?> />
						<label for="fc4"><?=__('Flat fee')?></label>&nbsp; &nbsp;
						<input <?= TTip($tip_cap) ?> id=fc5 onclick="ActualizarFormaCobro();" type=radio name=forma_cobro value="CAP" <?= $contrato_forma_cobro == "CAP" ? "checked='checked'" : "" ?> />
						<label for="fc5"><?=__('Cap')?></label>&nbsp; &nbsp;
						<input <?= TTip($tip_proporcional) ?> onclick="ActualizarFormaCobro();" id=fc6 type=radio name=forma_cobro value=PROPORCIONAL <?= $contrato_forma_cobro == "PROPORCIONAL" ? "checked='checked'" : "" ?> />
                        <label for="fc6">Proporcional</label> &nbsp; &nbsp;
					</div>
					<div style='border:1px solid #999999;width:400px;padding:4px 4px 4px 4px' id=div_forma_cobro>
						<div id="div_monto" align="left" style="display:none; background-color:#C6DEAD;padding-left:2px;padding-top:2px;">
							&nbsp;<?=__('Monto')?>
							<?php if ($validaciones_segun_config) echo $obligatorio ?>
							&nbsp;<input id='monto' name=monto size="7" value="<?= $contrato->fields['monto'] ?>" onchange="actualizarMonto();"/>&nbsp;&nbsp;
							&nbsp;&nbsp;<?=__('Moneda')?>
							<?php if ($validaciones_segun_config) echo $obligatorio ?>
							&nbsp;<?= Html::SelectQuery($sesion, "SELECT id_moneda,glosa_moneda FROM prm_moneda ORDER BY id_moneda","id_moneda_monto", $contrato->fields['id_moneda_monto'] > 0 ? $contrato->fields['id_moneda_monto'] : ($contrato->fields['id_moneda'] > 0 ? $contrato->fields['id_moneda'] : $id_moneda_monto), 'onchange="actualizarMonto();"','',"80"); ?>
						</div>
						<div id="div_horas" align="left" style="display:none; background-color:#C6DEAD;padding-left:2px;">
							&nbsp;<?=__('Horas')?>
							<?php if ($validaciones_segun_config) echo $obligatorio ?>
							&nbsp;<input name=retainer_horas size="7" value="<?= $contrato->fields['retainer_horas'] ?>" />
						</div>
						<div id="div_fecha_cap" align="left" style="display:none; background-color:#C6DEAD;padding-left:2px;">
							<table style='border: 0px solid' bgcolor='#C6DEAD'>
							<? if($cobro){ ?>
							<tr>
								<td>
									<?=__('Monto utilizado')?>:
									<?php if ($validaciones_segun_config) echo $obligatorio ?>
								</td>
								<td align=left>&nbsp;<label style='background-color:#FFFFFF'> <?=$cobro->TotalCobrosCap($contrato->fields['id_contrato']) > 0 ? $cobro->TotalCobrosCap($contrato->fields['id_contrato']) : 0;?> </label></td>
							</tr>
							<? }?>
							<tr>
								<td>
									<?=__('Fecha inicio')?>:
									<?php if ($validaciones_segun_config) echo $obligatorio ?>
								</td>
								<td align="left">
									<input type="text" name="fecha_inicio_cap" value="<?= Utiles::sql2date($contrato->fields['fecha_inicio_cap']) ?>" id="fecha_inicio_cap" size="11" maxlength="10" />
									<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha_inicio_cap" style="cursor:pointer" />
								</td>
							</tr>
							</table>
						</div>
					</div>
				</td>
			</tr>
			<tr>
				<td align="right" colspan='1' style="font-size:10pt;">
				<?=__('Mostrar total en')?>:
				<?php if ($validaciones_segun_config) echo $obligatorio ?>
				</td>
				<td align="left">
					<?=Html::SelectQuery( $sesion, "SELECT id_moneda, glosa_moneda FROM prm_moneda ORDER BY id_moneda", 'opc_moneda_total',$contrato->fields['opc_moneda_total'] ? $contrato->fields['opc_moneda_total'] : $opc_moneda_total,'style="font-size:10pt;"','','60')?>
					<span id="monedas_para_honorarios_y_gastos" style="display: none">
						<?php echo __('para honorarios y en'); ?>
						<?php echo Html::SelectQuery( $sesion, "SELECT id_moneda, glosa_moneda FROM prm_moneda ORDER BY id_moneda", 'opc_moneda_gastos',$contrato->fields['opc_moneda_gastos'],' style="font-size:10pt;"','','60'); ?>
						<?php echo __('para gastos'); ?>.
					</span>
				</td>
			</tr>
			<tr>
				<td colspan="2"><hr size="1"></td>
			</tr>
			<tr>
				<td align="right">
					<?=__('Descuento')?>
				</td>
				<td align="left">
					<input type=text name=descuento id=descuento size=6 value=<?=$contrato->fields['descuento']?>> <input type=radio name=tipo_descuento id=tipo_descuento value='VALOR' <?=$contrato->fields['tipo_descuento'] == 'VALOR' ? 'checked="checked"' : '' ?> /><?=__('Valor')?>
					<br>
					<input type=text name=porcentaje_descuento id=porcentaje_descuento size=6 value=<?=$contrato->fields['porcentaje_descuento']?>> <input type=radio name=tipo_descuento id=tipo_descuento value='PORCENTAJE' <?=$contrato->fields['tipo_descuento'] == 'PORCENTAJE' ? 'checked="checked"' : '' ?> /><?=__('%')?>
				</td>
			</tr>
			<tr>
				<td colspan="2"><hr size="1"></td>
			</tr>
			<tr>
				<td align="right">
					<?=__('Detalle Cobranza')?>
					<?php if ($validaciones_segun_config) echo $obligatorio ?>
				</td>
				<td align="left">
					<textarea name="observaciones" rows="3" cols="47"><?=$contrato->fields['observaciones'] ? $contrato->fields['observaciones'] : '' ?></textarea>
				</td>
			</tr>
			<tr>
				<td colspan="2"><hr size="1"></td>
			</tr>
			<tr>
				<td colspan="2" align="center">
					<fieldset style="width: 97%; background-color: #FFFFFF;">
					<legend <?=!$div_show ? 'onClick="MuestraOculta(\'datos_tramites\')" style="cursor:pointer"' : ''?> />
						<?=!$div_show ? '<span id="datos_tramites_img"><img src="'.Conf::ImgDir().'/mas.gif" border="0" id="datos_tramites_img"></span>' : '' ?>
						&nbsp;<?=__('Tr&aacute;mites')?>
					</legend>
						<div id='datos_tramites' style="display:<?=$show?>;" width="100%">
							<table width="100%">
								<tr>
									<td align="right" width="25%">
										<?=__('Tarifa Tr&aacute;mites')?>
									</td>
									<td align="left" width="75%">
										<?= Html::SelectQuery($sesion, "SELECT tramite_tarifa.id_tramite_tarifa, tramite_tarifa.glosa_tramite_tarifa FROM tramite_tarifa ORDER BY tramite_tarifa.glosa_tramite_tarifa","id_tramite_tarifa", $contrato->fields['id_tramite_tarifa'] ? $contrato->fields['id_tramite_tarifa'] : $tramite_tarifa_default, ""); ?>&nbsp;&nbsp;
										<?=__('Tarifa en')?>
										<?= Html::SelectQuery($sesion, "SELECT id_moneda,glosa_moneda FROM prm_moneda ORDER BY id_moneda","id_moneda_tramite", $contrato->fields['id_moneda_tramite'] ? $contrato->fields['id_moneda_tramite'] : $id_moneda_tramite, 'onchange="actualizarMoneda();"','',"80"); ?>&nbsp;&nbsp;
										<?php if( $tarifa_permitido ) { ?> 
											<span style='cursor:pointer' <?=TTip(__('Agregar nueva tarifa'))?> onclick='CreaTramiteTarifa(this.form,true)'><img src="<?=Conf::ImgDir()?>/mas.gif" border="0"></span>
											<span style='cursor:pointer' <?=TTip(__('Editar tarifa seleccionada'))?> onclick='CreaTramiteTarifa(this.form,false)'><img src="<?=Conf::ImgDir()?>/editar_on.gif" border="0"></span>
										<?php } ?>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</td>
			</tr>

<?
				$query = "SELECT MAX(fecha_creacion) FROM cobro WHERE id_contrato='".$contrato->fields['id_contrato']."' AND estado!='CREADO' AND estado!='EN REVISION'";
				$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
				list($ultimo_cobro)=mysql_fetch_array($resp);
?>
			<tr>
				<td colspan="2" align="center">
					<fieldset style="width: 97%; background-color: #FFFFFF;">
					<legend <?=!$div_show ? 'onClick="MuestraOculta(\'datos_cobros_programados\')" style="cursor:pointer"' : ''?> />
						<?=!$div_show ? '<span id="datos_cobros_programados_img"><img src="'.Conf::ImgDir().'/mas.gif" border="0" id="datos_cobros_programados_img"></span>' : '' ?>
						&nbsp;<?=__('Cobros Programados')?>
					</legend>
						<div id='datos_cobros_programados' style='display:<?=$show?>;' width="100%">
							<table width="100%">
								<tr>
									<td align="right" width="30%">
										<?=__('Generar ') . __('Cobros') . __(' a partir del')?>
									</td>
									<td align="left">
										<input type="text" name="periodo_fecha_inicio" value="<?=$fecha_ini ?>" id="periodo_fecha_inicio" size="11" maxlength="10" />
										<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_periodo_fecha_inicio" style="cursor:pointer" />
										&nbsp;<?=$ultimo_cobro ? '<span style="font-size:10px">'.__('Fecha último cobro emitido:').' '.Utiles::sql2date($ultimo_cobro).'</span>' : ''?>
									</td>
								</tr>
								<tr>
									<td align="right">
										<?=__('Cobrar cada')?>
									</td>
									<td align="left">
										<input type="text" name="periodo_intervalo" value="<?=empty($contrato->fields['periodo_intervalo']) ? '1' : $contrato->fields['periodo_intervalo'] ?>" id="periodo_intervalo" size="3" maxlength="2" />
										<span style='font-size:10px'><?=__('meses')?></span>
									</td>
								</tr>
								<tr>
									<td align="right">
										<?=__('Durante')?>
									</td>
									<td align="left">
										<input name=periodo_repeticiones id=periodo_repeticiones size=3 value="<?= $contrato->fields['periodo_repeticiones'] ?>" />
										<span style='font-size:10px'><?=__('periodos (0 para perpetuidad)')?></span>
									</td>
								</tr>
								<tr>
									<td align="center">
										<b><?=__('Próximos Cobros')?></b>&nbsp;<img src="<?=Conf::ImgDir()?>/reload_16.png" onclick='generarFechas()' style='cursor:pointer' <?=TTip(__('Actualizar fechas según período'))?>>
									</td>
									<td>&nbsp;</td>
								</tr>
								<tr>
									<td align="center" colspan="2">
										<table id="tabla_fechas" width='75%' style='border-top: 1px solid #454545; border-right: 1px solid #454545; border-left:1px solid #454545;	border-bottom:1px solid #454545;' cellpadding="3" cellspacing="3" style="border-collapse:collapse;">
											<thead>
											<tr bgcolor=#6CA522>
												<td width="27%">Fecha</td>
												<td width="45%">Descripción</td>
												<td width="23%">Monto</td>
												<td width="5%">&nbsp;</td>
											</tr>
											</thead>
											<tbody id="id_body">
											<tr id="fila_fecha_1">
												<td align="center">
													<input type="text" name="valor_fecha[1]" value='' id="valor_fecha_1" size="11" maxlength="10" />
													<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_nueva_fecha" style="cursor:pointer" />
												</td>
												<td align="left">
													<input type="text" name="valor_descripcion[1]" value='' id="valor_descripcion_1" size="40" />
												</td>
												<td align="right">
													<span class="moneda_tabla"></span>&nbsp;
													<input type="text" name="valor_monto_estimado[1]" value='' id="valor_monto_estimado_1" size="7" />
												</td>
												<td align="center">
													<img src="<?=Conf::ImgDir()?>/mas.gif" id="img_mas" style="cursor:pointer" onclick="agregarFila();" />
												</td>
											</tr>
					<?
											$color_par = "#f0f0f0";
											$color_impar = "#ffffff";
											$query = "SELECT cp.fecha_cobro,cp.descripcion,cp.monto_estimado FROM cobro_pendiente cp WHERE cp.id_contrato='".$contrato->fields['id_contrato']."' AND cp.id_cobro IS NULL ORDER BY fecha_cobro";
											$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
											for($i=2;$temp = mysql_fetch_array($resp);$i++)
											{
					?>
											<tr bgcolor=<?=$i % 2 == 0 ? $color_par : $color_impar ?> id="fila_fecha_<?=$i ?>" class="<?=$i > 6 ? 'esconder' : 'mostrar' ?>">
												<td align="center">
													<input type='hidden' class="fecha" value="<?=Utiles::sql2date($temp['fecha_cobro'])?>" id='valor_fecha_<?=$i ?>' name='valor_fecha[<?=$i ?>]'><?=Utiles::sql2date($temp['fecha_cobro'])?>
												</td>
												<td align="left">
													<input size="40" type='text' class="descripcion" value="<?=$temp['descripcion']?>" id='valor_descripcion_<?=$i ?>' name='valor_descripcion[<?=$i ?>]'>
												</td>
												<td align="right">
													<span class="moneda_tabla" align="center"></span>&nbsp;
													<input class="monto_estimado" size="7" type='text' align="right" value="<?=empty($temp['monto_estimado']) ? '' : $temp['monto_estimado']?>" id='valor_monto_estimado_<?=$i ?>' name='valor_monto_estimado[<?=$i ?>]'>
												</td>
												<td align="center">
													<img src='<?=Conf::ImgDir()?>/eliminar.gif' style='cursor:pointer' onclick='eliminarFila(this.parentNode.parentNode.rowIndex);' />
												</td>
											</tr>
					<?
											}
					?>
										</tbody>
										</table>
										<a href="javascript:void(0)" onclick="detallesTabla();" id="detalles_tabla_mostrar" style="font-size:7pt;text-align:right;">Mostrar todos</a>
										<a href="javascript:void(0)" onclick="detallesTabla();" id="detalles_tabla_esconder" style="display:none;font-size:7pt;text-align:right;">Esconder</a>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</td>
			</tr>
		</table>
	</div>
</fieldset>
<!-- FIN COBRANZA -->

<br/>

<fieldset style="width: 97%; background-color: #FFFFFF;">
	<legend <?=!$div_show ? 'onClick="MuestraOculta(\'datos_alertas\')" style="cursor:pointer"' : ''?> >
		<?=!$div_show ? '<span id="datos_alertas_img"><img src="'.Conf::ImgDir().'/mas.gif" border="0" id="datos_alertas_img"></span>' : '' ?>
		&nbsp;<?=__('Alertas')?></legend>
	<table id="datos_alertas"  style='display:<?=$show?>'>
		<tr>
			<td colspan="4"><p>&nbsp;<?=__('El sistema enviará un email de alerta al encargado comercial si se superan estos límites:')?></p></td>
		</tr>
		<tr>
			<td align=right>
				<input name=limite_hh value="<?= $contrato->fields['limite_hh'] ? $contrato->fields['limite_hh'] : '0' ?>" title="<?=__('Total de Horas')?>" size=5 />
			</td>
			<td align=left>
				<span title="<?=__('Total de Horas')?>"><?=__('Límite de horas')?></span>
			</td>
			<td align=right>
				<input name=limite_monto value="<?= $contrato->fields['limite_monto'] ? $contrato->fields['limite_monto'] : '0' ?>" title="<?=__('Valor Total según Tarifa Hora Hombre')?>" size=5 />
			</td>
			<td align=left>
				<span title="<?=__('Valor Total según Tarifa Hora Hombre')?>"><?=__('Límite de monto')?></span>
			</td>
		</tr>
		<tr>
			<td align=right>
				<input name=alerta_hh value="<?= $contrato->fields['alerta_hh'] ? $contrato->fields['alerta_hh'] : '0'?>" title="<?=__('Total de Horas en trabajos no cobrados')?>" size=5 />
			</td>
			<td align=left>
				<span title="<?=__('Total de Horas en trabajos no cobrados')?>"><?=__('horas no cobradas')?></span>
			</td>
			<td align=right>
				<input name=alerta_monto value="<?= $contrato->fields['alerta_monto'] ? $contrato->fields['alerta_monto'] : '0'?>" title="<?=__('Valor Total según Tarifa Hora Hombre en trabajos no cobrados')?>" size=5 />
			</td>
			<td align=left>
				<span title="<?=__('Valor Total según Tarifa Hora Hombre en trabajos no cobrados')?>"><?=__('monto según horas no cobradas')?></span>
			</td>
		</tr>
	</table>
</fieldset>

<br/>

<!-- CARTAS -->
<fieldset style="width: 97%; background-color: #FFFFFF;">
	<legend <?=!$div_show ? 'onClick="MuestraOculta(\'datos_carta\')" style="cursor:pointer"' : ''?> >
		<?=!$div_show ? '<span id="datos_carta_img"><img src="'.Conf::ImgDir().'/mas.gif" border="0" id="datos_carta_img"></span>' : '' ?>
		&nbsp;<?=__('Carta')?></legend>
	<table id='datos_carta' style='display:<?=$show?>' width="100%">
		<tr>
			<td align="right" colspan='1' width='25%'>
				<?=__('Idioma')?>
			</td>
			<td align="left" colspan="5">
				<?= Html::SelectQuery($sesion,"SELECT codigo_idioma,glosa_idioma FROM prm_idioma ORDER BY glosa_idioma","codigo_idioma",$contrato->fields['codigo_idioma'] ? $contrato->fields['codigo_idioma'] : $idioma_default,'','',80);?>
			</td>
		</tr>
		<tr>
			<td align="right" colspan='1' width='25%'>
				<?=__('Formato Carta')?>
			</td>
			<td align="left" colspan="5">
				<?= Html::SelectQuery($sesion, "SELECT carta.id_carta, carta.descripcion FROM carta ORDER BY id_carta","id_carta", $contrato->fields['id_carta'], ""); ?>
			</td>
		</tr>
		<tr>
			<td align="right" colspan='1' width='25%'>
				<?=__('Formato Detalle Carta')?>
			</td>
			<td align="left" colspan="5">
				<?= Html::SelectQuery($sesion, "SELECT cobro_rtf.id_formato, cobro_rtf.descripcion FROM cobro_rtf ORDER BY cobro_rtf.id_formato","id_formato",$contrato->fields['id_formato'], ""); ?>
			</td>
		</tr>
		<tr>
			<td align="right" colspan='1'><?=__('Tamaño del papel')?>:</td>
			<td align="left" colspan='5'>
				<select name="opc_papel">
					<option value="LETTER" <?=$contrato->fields['opc_papel']=='LETTER'?'selected="selected"':''?>><?=__('Carta')?></option>
					<option value="OFFICE" <?=$contrato->fields['opc_papel']=='OFFICE'?'selected="selected"':''?>><?=__('Oficio')?></option>
					<option value="A4" <?=$contrato->fields['opc_papel']=='A4'?'selected="selected"':''?>><?=__('A4')?></option>
					<option value="A5" <?=$contrato->fields['opc_papel']=='A5'?'selected="selected"':''?>><?=__('A5')?></option>
				</select>
			</td>
		</tr>
		<?
			
				if (empty($contrato->fields['id_contrato']) && method_exists('Conf','GetConf'))
				{ 
					$contrato->Edit('opc_restar_retainer', Conf::GetConf($sesion, 'OpcRestarRetainer') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_asuntos_separados', Conf::GetConf($sesion, 'OpcVerAsuntosSeparado') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_carta', Conf::GetConf($sesion, 'OpcVerCarta') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_cobrable', Conf::GetConf($sesion, 'OpcVerCobrable') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_descuento', Conf::GetConf($sesion, 'OpcVerDescuento') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_detalle_retainer', Conf::GetConf($sesion, 'OpcVerDetalleRetainer') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_detalles_por_hora', Conf::GetConf($sesion, 'OpcVerDetallesPorHora') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_detalles_por_hora_categoria', Conf::GetConf($sesion, 'OpcVerDetallesPorHoraCategoria') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_detalles_por_hora_importe', Conf::GetConf($sesion, 'OpcVerDetallesPorHoraImporte') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_detalles_por_hora_iniciales', Conf::GetConf($sesion, 'OpcVerDetallesPorHoraIniciales') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_detalles_por_hora_tarifa', Conf::GetConf($sesion, 'OpcVerDetallesPorHoraTarifa') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_gastos', Conf::GetConf($sesion, 'OpcVerGastos') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_horas_trabajadas', Conf::GetConf($sesion, 'OpcVerHorasTrabajadas') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_modalidad', Conf::GetConf($sesion, 'OpcVerModalidad') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_morosidad', Conf::GetConf($sesion, 'OpcVerMorosidad') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_numpag', Conf::GetConf($sesion, 'OpcVerNumPag') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_profesional', Conf::GetConf($sesion, 'OpcVerProfesional') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_profesional_categoria', Conf::GetConf($sesion, 'OpcVerProfesionalCategoria') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_profesional_importe', Conf::GetConf($sesion, 'OpcVerProfesionalImporte') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_profesional_iniciales', Conf::GetConf($sesion, 'OpcVerProfesionalIniciales') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_profesional_tarifa', Conf::GetConf($sesion, 'OpcVerProfesionalTarifa') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_resumen_cobro', Conf::GetConf($sesion, 'OpcVerResumenCobro') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_solicitante', Conf::GetConf($sesion, 'OpcVerSolicitante') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_tipo_cambio', Conf::GetConf($sesion, 'OpcVerTipoCambio') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_valor_hh_flat_fee', Conf::GetConf($sesion, 'OpcVerValorHHFlatFee') == 1 ? 1 : 0);
				}
		?>
		<tr>
			<td align="right" colspan='1'><input type="checkbox" name="opc_ver_asuntos_separados" value="1" <?=$contrato->fields['opc_ver_asuntos_separados']=='1'?'checked="checked"': ''?>></td>
			<td align="left" colspan='5'><?=__('Ver asuntos por separado')?></td>
		</tr>
		<tr>
			<td align="right" colspan='1'><input type="checkbox" name="opc_ver_resumen_cobro" value="1" <?=$contrato->fields['opc_ver_resumen_cobro']=='1'?'checked="checked"': ''?>/></td>
			<td align="left" colspan='5'><?=__('Mostrar resumen del cobro')?></td>
		</tr>
		<tr>
			<td align="right" colspan='1'><input type="checkbox" name="opc_ver_modalidad" value="1" <?=$contrato->fields['opc_ver_modalidad']=='1'?'checked="checked"': ''?> /></td>
			<td align="left" colspan='5'><?=__('Mostrar modalidad del cobro')?></td>
		</tr>
		<tr>
			<td align="right">
				<input type="checkbox" name="opc_ver_detalles_por_hora" id="opc_ver_detalles_por_hora" value="1" <?=($contrato->fields['opc_ver_detalles_por_hora']=='1' )?'checked':''?>>
			</td>
			<td align="left" colspan="2" style="font-size: 10px;">
				<label for="opc_ver_detalles_por_hora"><?=__('Mostrar detalle por hora')?></label>
			</td>
		</tr>
		<tr>
			<td/>
			<td align="left" colspan='5'>
				<input type="checkbox" name="opc_ver_detalles_por_hora_iniciales" id="opc_ver_detalles_por_hora_iniciales" value="1" <?=($contrato->fields['opc_ver_detalles_por_hora_iniciales']=='1' )?'checked':''?>>
				<label for="opc_ver_detalles_por_hora_iniciales"><?=__('Iniciales')?></label>
				<input type="checkbox" name="opc_ver_detalles_por_hora_categoria" id="opc_ver_detalles_por_hora_categoria" value="1" <?=($contrato->fields['opc_ver_detalles_por_hora_categoria']=='1' )?'checked':''?>>
				<label for="opc_ver_detalles_por_hora_categoria"><?=__('Categoría')?></label>
				<input type="checkbox" name="opc_ver_detalles_por_hora_tarifa" id="opc_ver_detalles_por_hora_tarifa" value="1" <?=($contrato->fields['opc_ver_detalles_por_hora_tarifa']=='1')?'checked':''?>>
				<label for="opc_ver_detalles_por_hora_tarifa"><?=__('Tarifa')?></label>
				<input type="checkbox" name="opc_ver_detalles_por_hora_importe" id="opc_ver_detalles_por_hora_importe" value="1" <?=($contrato->fields['opc_ver_detalles_por_hora_importe']=='1')?'checked':''?>>
				<label for="opc_ver_detalles_por_hora_importe"><?=__('Importe')?></label>
			</td>
		</tr>
		<tr>
			<td align="right" colspan='1'><input type="checkbox" name="opc_ver_profesional" <?=$contrato->fields['opc_ver_profesional']=='1'?'checked="checked"':''?> /></td>
			<td align="left" colspan='5'><?=__('Mostrar detalle por profesional')?></td>
		</tr>
		<tr>
			<td/>
			<td align="left" colspan='5'>
				<input type="checkbox" name="opc_ver_profesional_iniciales" id="opc_ver_profesional_iniciales" value="1" <?=($contrato->fields['opc_ver_profesional_iniciales']=='1')?'checked':''?>>
				<label for="opc_ver_profesional_iniciales"><?=__('Iniciales')?></label>
				<input type="checkbox" name="opc_ver_profesional_categoria" id="opc_ver_profesional_categoria" value="1" <?=($contrato->fields['opc_ver_profesional_categoria']=='1')?'checked':''?>>
				<label for="opc_ver_profesional_categoria"><?=__('Categoría')?></label>
				<input type="checkbox" name="opc_ver_profesional_tarifa" id="opc_ver_profesional_tarifa" value="1" <?=($contrato->fields['opc_ver_profesional_tarifa']=='1')?'checked':''?>>
				<label for="opc_ver_profesional_tarifa"><?=__('Tarifa')?></label>
				<input type="checkbox" name="opc_ver_profesional_importe" id="opc_ver_profesional_importe" value="1" <?=($contrato->fields['opc_ver_profesional_importe']=='1')?'checked':''?>>
				<label for="opc_ver_profesional_importe"><?=__('Importe')?></label>
			</td>
		</tr>
		<tr>
			<td align="right" colspan='1'><input type="checkbox" name="opc_ver_descuento" value="1" <?=$contrato->fields['opc_ver_descuento']=='1'?'checked="checked"':''?> /></td>
			<td align="left" colspan='5'><?=__('Mostrar el descuento del cobro')?></td>
		</tr>
		<tr>
			<td align="right" colspan='1'><input type="checkbox" name="opc_ver_gastos" value="1" <?=$contrato->fields['opc_ver_gastos']=='1'?'checked="checked"':''?> /></td>
			<td align="left" colspan='5'><?=__('Mostrar gastos del cobro')?></td>
		</tr>
		<tr>
			<td align="right" colspan='1'><input type="checkbox" name="opc_ver_morosidad" value="1" <?=$contrato->fields['opc_ver_morosidad']=='1'?'checked="checked"':''?> /></td>
			<td align="left" colspan='5'><?=__('Mostrar saldo adeudado')?></td>
		</tr>
		<tr>
			<td align="right" colspan='1'><input type="checkbox" name="opc_ver_tipo_cambio" value="1" <?=$contrato->fields['opc_ver_tipo_cambio']=='1'?'checked="checked"':''?> /></td>
			<td align="left" colspan='5'><?=__('Mostrar tipos de cambio')?></td>
		</tr>
		<tr>
			<td align="right" colspan='1'><input type="checkbox" name="opc_ver_numpag" value="1" <?=$contrato->fields['opc_ver_numpag']=='1'?'checked="checked"':''?> /></td>
			<td align="left" colspan='5'><?=__('Mostrar números de página')?></td>
		</tr>
		<?
				if(method_exists('Conf','GetConf'))
					$solicitante = Conf::GetConf($sesion, 'OrdenadoPor');
				elseif(method_exists('Conf','Ordenado_por'))
					$solicitante = Conf::Ordenado_por();
				else
					$solicitante = 2;

				if($solicitante == 0)		// no mostrar
				{
?>
					<input type="hidden" name="opc_ver_solicitante" id="opc_ver_solicitante" value="0" />
<?
				}				
				elseif($solicitante == 1)	// obligatorio
				{
?>
					<tr>
						<td align="right" colspan='1'><input type="checkbox" name="opc_ver_solicitante" value="1" <?=$contrato->fields['opc_ver_solicitante']=='1'?'checked="checked"':''?>></td>
						<td align="left" colspan='5'><?=__('Mostrar solicitante')?></td>
					</tr>
<?
				}
				elseif ($solicitante == 2)	// opcional
				{
?>
								<tr>
									<td align="right" colspan='1'><input type="checkbox" name="opc_ver_solicitante" value="1" <?=$contrato->fields['opc_ver_solicitante']=='1'?'checked="checked"':''?>></td>
									<td align="left" colspan='5'><?=__('Mostrar solicitante')?></td>
								</tr>
<?
				}
?>
		<tr>
			<td align="right" colspan='1'><input type="checkbox" name="opc_ver_horas_trabajadas" value="1" <?=$contrato->fields['opc_ver_horas_trabajadas']=='1'?'checked="checked"':''?> ></td>
			<td align="left" colspan='5'><?=__('Mostrar horas trabajadas')?></td>
		</tr>
		<tr>
			<td align="right" colspan='1'><input type="checkbox" name="opc_ver_cobrable" value="1" <?=$contrato->fields['opc_ver_cobrable']=='1'?'checked="checked"':''?> ></td>
			<td align="left" colspan='5'><?=__('Mostrar trabajos no visibles')?></td>
		</tr>
<? if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'ResumenProfesionalVial') ) || ( method_exists('Conf','ResumenProfesionalVial') && Conf::ResumenProfesionalVial() ) )
		{ ?>
		<tr> 
			<td align="right" colspan='1'><input type="checkbox" name="opc_restar_retainer" value="1" <?=$contrato->fields['opc_restar_retainer']=='1'?'checked="checked"':''?>  /></td>
			<td align="left" colspan='5'><?=__('Restar valor retainer')?></td>
 		</tr>
 		<tr>
 			<td align="right"><input type="checkbox" name="opc_ver_detalle_retainer" value="1" <?=$contrato->fields['opc_ver_detalle_retainer']=='1'?'checked="checked"':''?> /></td>
 			<td align="left" colspan='5'><?=__('Mostrar detalle retainer')?></td>
 		</tr>
<?  } ?>
		<tr>
			<td align="right"><input type="checkbox" name="opc_ver_valor_hh_flat_fee" value="1" <?=$contrato->fields['opc_ver_valor_hh_flat_fee']=='1'?'checked="checked"':''?>/></td>
			<td align="left" colspan='5'><?=__('Mostrar valor HH en caso de Flat Flee')?></td>
		</tr>
		<tr>
			<td align="right" colspan='1'><input type="checkbox" name="opc_ver_carta" value="1" onclick="ActivaCarta(this.checked)" <?=$contrato->fields['opc_ver_carta']=='1'?'checked="checked"':''?> /></td>
			<td align="left" colspan='5'><?=__('Mostrar Carta')?></td>
		</tr>
	</table>
</fieldset>
<br>
<!-- FIN CARTAS -->

<!-- DOCUMENTOS -->
	<?
		if($id_cliente||$id_asunto)
		{
?>
<fieldset style="width: 97%; background-color: #FFFFFF;">
	<legend <?=!$div_show ? 'onClick="MuestraOculta(\'documentos\')" style="cursor:pointer"' : ''?> >
		<?=!$div_show ? '<span id="documentos_img"><img src="'.Conf::ImgDir().'/mas.gif" border="0" id="documentos_img"></span>' : ''?>
		&nbsp;<?=__('Documentos')?></legend>
	<table id='documentos' style='display:<?=$show?>'>
			<tr>
				<td colspan="2" align="center">
<?
					$id_contrato_ifr = $contrato->fields['id_contrato'];
?>
					<iframe name="iframe_documentos" id="iframe_documentos" src='documentos.php?id_cliente=<?=$cliente->fields['id_cliente']?>&id_contrato=<?=$id_contrato_ifr ?>' frameborder=0 style="width:650px; height:250px;"></iframe>
				</td>
			</tr>
    </table>
</fieldset>
<br>
<?
		} #fin id_cliente OR id_asunto
?>
<!-- FIN DOCUMENTOS -->

<!-- ASOCIAR DOC LEGALES -->
<? 
	if( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NuevoModuloFactura') )
	{ ?>
<fieldset style="width: 97%; background-color: #FFFFFF;">
	<legend <?=!$div_show ? 'onClick="MuestraOculta(\'div_doc_legales_asociados\')" style="cursor:pointer"' : ''?>>
		<?=!$div_show ? '<span id="doc_legales_img"><img src="'.Conf::ImgDir().'/mas.gif" border="0" id="doc_legales_img"></span>' : ''?>
		&nbsp;<?=__('Documentos legales por defecto')?>
	</legend>
	<div id="div_doc_legales_asociados" style='display:<?=$show?>'>
		<p><center>Ingrese los documentos legales que desea generar en el proceso de facturación</center></p>
		<?php include dirname(__FILE__) . '/agregar_doc_legales.php'; ?>
	</div>
</fieldset>
<? 
}
?>
<br>
<!-- ASOCIAR DOC LEGALES -->

<!-- GUARDAR -->
<? if($popup && !$motivo){ ?>
<fieldset style="width: 97%; background-color: #FFFFFF;">
	<legend><?=__('Guardar datos')?></legend>
	<table>
		<tr>
	    <td colspan=6 align="center">
<?php
	if( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'RevisarTarifas') )
	{
?>
	        <input type="button" class=btn value="<?=__('Guardar')?>" onclick="return RevisarTarifas( 'id_tarifa', 'id_moneda', this.form, false);" />
<?php
	}
	else
	{
?>
			<input type="button" class=btn value="<?=__('Guardar')?>" onclick="ValidarContrato(this.form)" />
<?php
	}
?>
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
<script type="text/javascript">
ActualizarFormaCobro();
Calendar.setup(
	{
		inputField	: "valor_fecha_1",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_nueva_fecha"		// ID of the button
	}
);
Calendar.setup(
	{
		inputField	: "periodo_fecha_inicio",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_periodo_fecha_inicio"		// ID of the button
	}
);
Calendar.setup(
	{
		inputField	: "fecha_inicio_cap",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_fecha_inicio_cap"		// ID of the button
	}
);
$$('tr.esconder').each(function(item){item.hide()});
actualizarMoneda();
</script>
<?
echo(InputId::Javascript($sesion));
?>
