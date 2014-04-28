<?php
require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion(array('COB'));
$pagina = new PaginaCobro($sesion);

$cobro = new Cobro($sesion);

if (!$cobro->Load($id_cobro)) {
	$pagina->FatalError(__('Cobro inválido'));
}

$cobro->GuardarCobro();

$cobro->CargarEscalonadas();
$enpdf = ( $opc == 'guardar_cobro_pdf' ? true : false );

$cliente = new Cliente($sesion);
$cliente->LoadByCodigo($cobro->fields['codigo_cliente']);
$nombre_cliente = $cliente->fields['glosa_cliente'];
$pagina->titulo = __('Emitir') . ' ' . __('Cobro') . __(' :: Detalle #') . $id_cobro . __(' ') . $nombre_cliente;

//Contrato
$contrato = new Contrato($sesion);
$contrato->Load($cobro->fields['id_contrato']);

// Idioma
$idioma = new Objeto($sesion, '', '', 'prm_idioma', 'codigo_idioma');

if (method_exists('Conf', 'GetConf')) {
	$idioma->Load(Conf::GetConf($sesion, 'Idioma'));
} else {
	$idioma->Load(Conf::Idioma());
}

// Moneda
$moneda_base = new Objeto($sesion, '', '', 'prm_moneda', 'id_moneda');
$moneda_base->Load($cobro->fields['id_moneda_base']);

$retainer = "";

if ($cobro->fields['estado'] != 'CREADO' && $cobro->fields['estado'] != 'EN REVISION' && $opc != 'anular_emision') {
	$pagina->Redirect("cobros6.php?id_cobro=" . $id_cobro . "&popup=1&contitulo=true&opc=guardar");
}

if ($opc == 'anular_emision') {
	
	if ($estado == 'EN REVISION') {
		$cobro->AnularEmision('EN REVISION');
	} else {
		$cobro->AnularEmision();
	}
	
	#Se ingresa la anotación en el historial
	$estado_anterior = $cobro->fields['estado'];
	$nuevo_estado = $estado;

	if ($estado_anterior != $nuevo_estado) {

	}

} else if ($opc == 'guardar_cobro' || $opc == 'guardar_cobro_pdf') { 

	//	Guardamos todos los datos del cobro
	// 	Si se ajustar el valor del cobro por monto,
	// 	llamar a la function AjustarPorMonto de la clase Cobro

	if (!is_numeric($cobro_monto_honorarios)) {
		$cobro_monto_honorarios = 0;
	}

	if ($ajustar_monto_hide && $ajustar_monto_hide != "false") {
		$cobro->Edit('monto_ajustado', $cobro_monto_honorarios);
	} else {
		$cobro->Edit('monto_ajustado', "0");
	}

	/* tarifa escalonada */
	if (isset($_POST['esc_tiempo'])) {
		
		for ($i = 1; $i <= sizeof($_POST['esc_tiempo']); $i++) {
		
			if ($_POST['esc_tiempo'][$i - 1] != '') {
				$cobro->Edit('esc' . $i . '_tiempo', $_POST['esc_tiempo'][$i - 1]);
		
				if ($_POST['esc_selector'][$i - 1] != 1) {
					//caso monto
					$cobro->Edit('esc' . $i . '_id_tarifa', "NULL");
					$cobro->Edit('esc' . $i . '_monto', $_POST['esc_monto'][$i - 1]);
				} else {
					//caso tarifa
					$cobro->Edit('esc' . $i . '_id_tarifa', $_POST['esc_id_tarifa_' . $i]);
					$cobro->Edit('esc' . $i . '_monto', "NULL");
				}

				$cobro->Edit('esc' . $i . '_id_moneda', $_POST['esc_id_moneda_' . $i]);
				$cobro->Edit('esc' . $i . '_descuento', $_POST['esc_descuento'][$i - 1]);

			} else {
				
				$cobro->Edit('esc' . $i . '_tiempo', "NULL");
				$cobro->Edit('esc' . $i . '_id_tarifa', "NULL");
				$cobro->Edit('esc' . $i . '_monto', "NULL");
				$cobro->Edit('esc' . $i . '_id_moneda', "NULL");
				$cobro->Edit('esc' . $i . '_descuento', "NULL");

			}
		}
	}

	$cobro->Edit("opc_ver_detalles_por_hora", $opc_ver_detalles_por_hora);
	$cobro->Edit('id_moneda', $cobro_id_moneda);
	$cobro->Edit('tipo_cambio_moneda', $cobro_tipo_cambio);
	$cobro->Edit('forma_cobro', $cobro_forma_cobro);
	$cobro->Edit('id_moneda_monto', $id_moneda_monto);
	$cobro->Edit('monto_contrato', $cobro_monto_contrato);
	$cobro->Edit('retainer_horas', $cobro_retainer_horas);
	
	if (is_array($usuarios_retainer)) {
		$cobro_retainer_usuarios = implode(',', $usuarios_retainer);
	} else {
		$cobro_retainer_usuarios = $usuarios_retainer;
	}

	$cobro->Edit('retainer_usuarios', $cobro_retainer_usuarios);

	#################### OPCIONES #######################
	$cobro->Edit('opc_moneda_total', $opc_moneda_total);
	$cobro->Edit("opc_ver_modalidad", $opc_ver_modalidad);
	$cobro->Edit("opc_ver_profesional", $opc_ver_profesional);
	$cobro->Edit("opc_ver_gastos", $opc_ver_gastos);
	$cobro->Edit("opc_ver_concepto_gastos", $opc_ver_concepto_gastos);
	$cobro->Edit("opc_ver_morosidad", $opc_ver_morosidad);
	$cobro->Edit("opc_ver_resumen_cobro", $opc_ver_resumen_cobro);
	$cobro->Edit("opc_ver_profesional_iniciales", $opc_ver_profesional_iniciales);
	$cobro->Edit("opc_ver_profesional_categoria", $opc_ver_profesional_categoria);
	$cobro->Edit("opc_ver_profesional_tarifa", $opc_ver_profesional_tarifa);
	$cobro->Edit("opc_ver_profesional_importe", $opc_ver_profesional_importe);
	$cobro->Edit("opc_ver_detalles_por_hora_categoria", $opc_ver_detalles_por_hora_categoria);
	$cobro->Edit("opc_ver_detalles_por_hora_iniciales", $opc_ver_detalles_por_hora_iniciales);
	$cobro->Edit("opc_ver_detalles_por_hora_tarifa", $opc_ver_detalles_por_hora_tarifa);
	$cobro->Edit("opc_ver_detalles_por_hora_importe", $opc_ver_detalles_por_hora_importe);
	$cobro->Edit("opc_ver_tipo_cambio", $opc_ver_tipo_cambio);
	$cobro->Edit("opc_ver_descuento", $opc_ver_descuento);
	$cobro->Edit("opc_ver_numpag", $opc_ver_numpag);
	$cobro->Edit("opc_papel", $opc_papel);
	$cobro->Edit("opc_ver_solicitante", $opc_ver_solicitante);
	$cobro->Edit('opc_ver_carta', $opc_ver_carta);
	$cobro->Edit("opc_ver_asuntos_separados", $opc_ver_asuntos_separados);
	$cobro->Edit("opc_ver_horas_trabajadas", $opc_ver_horas_trabajadas);
	$cobro->Edit("opc_ver_cobrable", $opc_ver_cobrable);
	
	#################### OPCIONES Vial Olivares #######################
	$cobro->Edit("opc_restar_retainer", $opc_restar_retainer);
	$cobro->Edit("opc_ver_detalle_retainer", $opc_ver_detalle_retainer);
	$cobro->Edit("opc_ver_valor_hh_flat_fee", $opc_ver_valor_hh_flat_fee);
	$cobro->Edit('id_carta', $id_carta);
	$cobro->Edit('id_formato', $id_formato);
	$cobro->Edit('codigo_idioma', $lang);
	
	if (trim($se_esta_cobrando) != '') {
		$cobro->Edit('se_esta_cobrando', $se_esta_cobrando);
	}

	$cobro->Edit("opc_ver_columna_cobrable", $opc_ver_columna_cobrable);
	$cobro->Write(); //Se guarda porque despues se necesita para recalcular los datos del cobro
	
	################### DESCUENTOS #####################
	if ($tipo_descuento == 'PORCENTAJE') {
		$total_descuento = ($cobro->fields['monto_subtotal'] * $porcentaje_descuento) / 100;
		$cobro->Edit('descuento', $total_descuento);
		$cobro->Edit('porcentaje_descuento', $porcentaje_descuento);
	} elseif ($tipo_descuento == 'VALOR') {
		$cobro->Edit('descuento', $cobro_descuento);
		$cobro->Edit('porcentaje_descuento', '0');
	}

	$cobro->Edit('tipo_descuento', $tipo_descuento);
	$cobro_moneda_cambio = new CobroMoneda($sesion);
	$cobro_moneda_cambio->UpdateTipoCambioCobro($cobro_id_moneda, $cobro_tipo_cambio, $id_cobro);

	$ret = $cobro->GuardarCobro();

	##################### EMISION ######################

	if ($accion == 'emitir' && $ret == '') {	 
		/* Guardo el cobro generando los movimientos de cuenta corriente */
		$query_pagos = "SELECT count(*) FROM neteo_documento nd JOIN documento d ON nd.id_documento_cobro = d.id_documento WHERE d.id_cobro = '{$cobro->fields['id_cobro']}'";
		$resp_pagos = mysql_query($query_pagos, $sesion->dbh) or Utiles::errorSQL($query_pagos, __FILE__, __LINE__, $sesion->dbh);
		list($cantidad_pagos) = mysql_fetch_array($resp_pagos);
		
		if ($cantidad_pagos > 0) {
			$cobro->ReiniciarDocumento();
		} else {
			$cobro->GuardarCobro(true);
		}

		$query_usuarioresponsable = "select id_usuario_responsable, id_usuario_secundario from contrato where id_contrato=" . $cobro->fields['id_contrato'];
		$resp_usuarioresponsable = mysql_query($query_usuarioresponsable, $sesion->dbh) or Utiles::errorSQL($query_usuarioresponsable, __FILE__, __LINE__, $sesion->dbh);
		list($id_usuario_responsable, $id_usuario_secundario) = mysql_fetch_array($resp_usuarioresponsable);

		$timeemision = strtotime($cobro->fields['fecha_emision']);

		if ($timeemision <= 0) {
			$nuevafechaemision = date('Y-m-d H:i:s');
			$cobro->Edit('fecha_emision', $nuevafechaemision);
		}

		if (array_key_exists('id_ultimo_emisor', $cobro->fields)) {
			$cobro->Edit('id_ultimo_emisor', $sesion->usuario->fields['id_usuario']);
		}
		if (array_key_exists('id_usuario_responsable', $cobro->fields)) {
			$cobro->Edit('id_usuario_responsable', $id_usuario_responsable);
		}
		if (array_key_exists('id_usuario_secundario', $cobro->fields)) {
			$cobro->Edit('id_usuario_secundario', $id_usuario_secundario);
		}

		if (Conf::GetConf($sesion, 'SeEstaCobrandoEspecial')) {
			$cobro->Edit('se_esta_cobrando', $cobro->GlosaSeEstaCobrando());
		}

		if ($cobro->Write()) {
			if (!empty($usar_adelantos)) {
				$documento = new Documento($sesion);
				$documento->LoadByCobro($id_cobro);
				$documento->GenerarPagosDesdeAdelantos($documento->fields['id_documento']);
			}
			if (empty($cobro->fields['estado_anterior']) || in_array($cobro->fields['estado_anterior'], array('CREADO', 'EN REVISION'))) {
				$cobro->CambiarEstadoSegunFacturas();
			} else {
				$cobro->Edit('estado', $cobro->fields['estado_anterior']);
				$cobro->Write();
			}
			$refrescar = "<script language='javascript' type='text/javascript'>if(window.opener.Refrescar) window.opener.Refrescar(" . $id_foco . ");</script>";
			$pagina->Redirect("cobros6.php?id_cobro=" . $id_cobro . "&popup=1&contitulo=true&refrescar=1&opc=guardar");
		}

	#################### IMPRESION #####################
	} else if ($accion == 'imprimir' && $ret == '') {  
		include dirname(__FILE__) . '/cobro_doc.php';
		exit;
	} else if ($accion == 'descargar_excel_especial') {
		if (Conf::GetConf($sesion, 'XLSFormatoEspecial') != '') {
			require_once Conf::ServerDir() . '/../app/interfaces/' . Conf::GetConf($sesion, 'XLSFormatoEspecial');
		}
		exit;
	} else if ($accion == 'descargar_excel_rentabilidad') {
		require_once Conf::ServerDir() . '/../app/interfaces/cobros_xls_rentabilidad.php';
	} else if ($accion == 'descargar_excel') {
		require_once Conf::ServerDir() . '/../app/interfaces/cobros_xls.php';
		exit;
	
	################## ANTERIOR PASO ###################
	} else if ($accion == 'anterior') {		 
		if (!empty($cobro->fields['incluye_gastos'])) {
			$pagina->Redirect("cobros4.php?id_cobro=" . $id_cobro . "&popup=1&contitulo=true");
		} else {
			$pagina->Redirect("cobros_tramites.php?id_cobro=" . $id_cobro . "&popup=1&contitulo=true");
		}
	}

	if ($ret != '') {
		$pagina->AddInfo($ret);
	} else {
		$pagina->AddInfo(__('Información actualizada'));
	}
} else if ($opc == 'up_cambios') {
	$pagina->AddInfo(__('Los cambios han sido actualizados correctamente.'));
} else if ($opc == 'en_revision') {
	$cobro->Edit('estado', 'EN REVISION');
	$cobro->Edit('fecha_en_revision', date('Y-m-d H:i:s'));
	if ($cobro->Write()) {
		$pagina->AddInfo(__('El Cobro ha sido transferido') . " " . __('al estado: En Revisión'));
	}
	$historial_comentario = __('COBRO EN REVISION');

} else if ($opc == 'volver_a_creado') {

	$query = "SELECT count(*) FROM neteo_documento nd JOIN documento d ON nd.id_documento_cobro = d.id_documento WHERE d.id_cobro = '{$cobro->fields['id_cobro']}'";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	list($cantidad_pagos) = mysql_fetch_array($resp);

	$query = "SELECT count(*) FROM factura WHERE estado!='ANULADA' and id_cobro = '" . $cobro->fields['id_cobro'] . "' ";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	list($cantidad_facturas) = mysql_fetch_array($resp);

	if ($cantidad_pagos > 0) {
		$pagina->AddError("Este cobro no se puede volver al estado CREADO, ya que tiene un pago asociado.");
	} else if ($cantidad_facturas > 0) {
		$pagina->AddError("Este cobro no se puede volver al estado CREADO, ya que tiene facturas asociados.");
	} else {
		$cobro->Edit('estado', 'CREADO');
		if ($cobro->Write()) {
			$pagina->AddInfo(__('El Cobro ha sido transferido') . " " . __('al estado: Creado'));
		}
		$historial_comentario = __('REVISION ANULADO');
		##Historial##
		$estado_anterior = $cobro->fields['estado'];

		if ($estado_anterior != 'CREADO') {
			/* $his = new Observacion($sesion);
			  $his->Edit('fecha',date('Y-m-d H:i:s'));
			  $his->Edit('comentario',$historial_comentario);
			  $his->Edit('id_usuario',$sesion->usuario->fields['id_usuario']);
			  $his->Edit('id_cobro',$cobro->fields['id_cobro']);
			  $his->Write(); */
		}
	}
}

$cobro->Edit('etapa_cobro', '4');
if ($cobro->Write()) {
	$refrescar = "<script language='javascript' type='text/javascript'>if( window.opener.Refrescar ) window.opener.Refrescar(" . $id_foco . ");</script>";
}

$moneda_cobro = new Objeto($sesion, '', '', 'prm_moneda', 'id_moneda');
$moneda_cobro->Load($cobro->fields['id_moneda']);

$pagina->PrintTop($popup);
if ($popup) {
	?>
	<table width="100%" border="0" cellspacing="0" cellpadding="2">
		<tr>
			<td valign="top" align="left" class="titulo" bgcolor="<?php echo Conf::GetConf($sesion, 'ColorTituloPagina') ?>">
	<?php echo __('Emitir') . " " . __('Cobro') . __(' :: Detalle #') . $id_cobro . __(' ') . $nombre_cliente; ?>
			</td>
		</tr>
	</table>
	<br>
	<?php
}

$pagina->PrintPasos($sesion, 4, '', $id_cobro, $cobro->fields['incluye_gastos'], $cobro->fields['incluye_honorarios']);

#Tooltips para las modalidades de cobro.
$tip_tasa = __("En esta modalidad se cobra hora a hora. Cada profesional tiene asignada su propia tarifa para cada asunto.");
$tip_suma = __("Es un único monto de dinero para el asunto. Aquí interesa llevar la cuenta de HH para conocer la rentabilidad del proyecto. Esta es la única modalidad de ") . __("cobro") . __(" que no puede tener límites.");
$tip_retainer = __("El cliente compra un número de HH. El límite puede ser por horas o por un monto.");
$tip_retainer_usuarios = __("Si usted selecciona usuarios en esta lista, las horas de estos usuarios se van a descontar de las horas retainer con preferencia");
$tip_proporcional = __("El cliente compra un número de horas, el exceso de horas trabajadas se cobra proporcional a la duración de cada trabajo.");
$tip_escalonada = __("El cliente define una serie de escalas de tiempos durante las cuales podrá variar la tarifa, definir un monto específico y un descuento individual.");
$tip_mostrar_escalonada = __("Mostrar detalles de tarifa escalonada");
$tip_ocultar_escalonada = __("Ocultar detalles de tarifa escalonada");
$tip_flat = __("El cliente acuerda cancelar un <strong>monto fijo mensual</strong> por atender todos los trabajos de este asunto. Puede tener límites por HH o monto total");
$tip_cap = __("Cap");
$tip_honorarios = __("Solamente lleva la cuenta de las HH profesionales. Al terminar el proyecto se puede cobrar eventualmente.");
$tip_mensual = __("El cobro se hará de forma mensual.");
$tip_tarifa_especial = __("Al ingresar una nueva tarifa, esta se actualizará automáticamente.");
$tip_subtotal = __("El monto total") . " " . __("del cobro") . " " . __("hasta el momento sin gastos y sin incluir descuentos.");
$tip_descuento = __("El monto del descuento.");
$tip_total = __("El monto total") . " " . __("del cobro") . " " . __("hasta el momento incluidos descuentos.");
$tip_actualizar = __("Actualizar los montos");
$tip_refresh = __("Actualizar a cambio actual");

function TTip($texto) {
	return "onmouseover=\"ddrivetip('$texto');\" onmouseout=\"hideddrivetip('$texto');\"";
}

echo $refrescar;
?>

<script language="javascript" type="text/javascript">
	<!-- //
	function SubirExcel()
	{
		nuevaVentana('SubirExcel',500,300,"subir_excel.php");
		return false;
	}

	function Refrescar()
	{
		var id_cobro = $('id_cobro').value;
		var vurl = "cobros5.php?popup=1&id_cobro="+id_cobro+"&id_foco=2";
		self.location.href = vurl;
	}

	function Anterior( form )
	{
		if(!form) {
			var form = $('form_cobro5');
		}

		form.accion.value = 'anterior';
		form.submit();
		return true;
	}

	function ActualizarTarifas( form )
	{
		var http = getXMLHTTP();
		http.open('get', 'ajax.php?accion=actualizar_tarifas&id_cobro='+document.getElementById('id_cobro').value);
		http.onreadystatechange = function()
		{
			if(http.readyState == 4)
			{
				response = http.responseText;
				if( response == "OK" ) {
					alert('Tarifas actualizados con éxito.');
					$('form_cobro5').submit();
					return true;
				} else {
					alert('No se pudieron actualizar las tarifas.')
					return false;
				}
			}
		}
		http.send(null);
	}

	function showOpcionDetalle( id, bloqueDetalle )
	{
		if( $(id).checked ) {
			$(bloqueDetalle).style.display = "table-row";
		} else {
			$(bloqueDetalle).style.display = "none";
		}
	}

	function AjustarMonto( accion )
	{
		form = document.getElementById('form_cobro5');

		if( accion == 'ajustar' ) {

			document.getElementById('cobro_monto_honorarios').value = "";
			document.getElementById('cobro_monto_honorarios').readOnly = false;
			document.getElementById('ajustar_monto_hide').value = true;
			document.getElementById('cancelar_ajustacion').style.display = 'inline';
			document.getElementById('tr_monto_original').style.display = 'table-row';
			document.getElementById('cobro_monto_honorarios').focus();

		} else if( accion == 'cancelar' ) {
			
			document.getElementById('cobro_monto_honorarios').value = document.getElementById('monto_original').value;
			document.getElementById('cobro_monto_honorarios').readOnly = true;
			document.getElementById('ajustar_monto_hide').value = false;
			document.getElementById('cancelar_ajustacion').style.display = 'none';
			document.getElementById('tr_monto_original').style.display = 'none';

			form.submit();
			return true;
		}
	}

	function MontoValido( id_campo )
	{
		var monto = document.getElementById( id_campo ).value.replace('\,','.');
		var arr_monto = monto.split('\.');
		var monto = arr_monto[0];
		
		for($i=1;$i<arr_monto.length-1;$i++) {
			monto += arr_monto[$i];
		}

		if( arr_monto.length > 1 ) {
			monto += '.' + arr_monto[arr_monto.length-1];
		}

		document.getElementById( id_campo ).value = monto;
	}

	function AgregarParametros( form )
	{
		if(!form) {
			var form = $('form_cobro5');
		}

		for(var i=0;i < form.cobro_id_moneda.length;i++) {
			if( form.cobro_id_moneda[i].checked ) {
				form.cobro_id_moneda.value = form.cobro_id_moneda[i].value;
			}
		}

		for(var i=0;i < form.cobro_forma_cobro.length;i++) {
			if( form.cobro_forma_cobro[i].checked ) {
				form.cobro_forma_cobro.value = form.cobro_forma_cobro[i].value;
			}
		}

		if(form.cobro_id_moneda.value) {
			var valor_cobro_id_moneda=form.cobro_id_moneda.value;
		} else {
			var i=0;

			while( form.cobro_id_moneda[i] ) {
				if( form.cobro_id_moneda[i].checked == true ) {
					var valor_cobro_id_moneda = form.cobro_id_moneda[i].value;
				}

				i++;
			}
		}

		var cobro_tipo_cambio = document.getElementById('cobro_tipo_cambio_'+parseInt(valor_cobro_id_moneda)).value;

		form.cobro_tipo_cambio.value = parseFloat(cobro_tipo_cambio);

		if( form.cobro_id_moneda.value == '' ) {
			alert("<?php echo __('Tienes que ingresar el tipo de moneda.') ?>");
			return false;
		}

		if( form.cobro_forma_cobro.value == '' ) {
			alert("<?php echo __('Tienes que ingresar la forma de cobro.') ?>");
			return false;
		}

		if( form.cobro_descuento.value == '' ) {
			form.cobro_descuento.value = 0;
		}

		return true;

		// alert("<?php echo __('Error al procesar los parámetros.') ?>");
		// return false;
	}

	function EnRevision( form )
	{
		form.opc.value = 'en_revision';
		form.submit();
		return true;v4lh4ll4

	}

	function VolverACreado( form )
	{
		if($('existe_factura').value == 1 )
		{
			alert("<?php echo __('No se puede regresar a estado CREADO. Existen Documentos Tributarios creados para') . " " . __('este cobro') ?>");
			return false;
		}

		form.opc.value = 'volver_a_creado';
		form.submit();
		return true;
	}

	function Emitir(form)
	{
		jQuery('#btn_emitir_cobro').attr("disabled","disabled");
		var http = getXMLHTTP();
		http.open('get', 'ajax.php?accion=num_abogados_sin_tarifa&id_cobro='+document.getElementById('id_cobro').value);
		
		http.onreadystatechange = function()
		{
			if(http.readyState == 4)
			{
				var response = http.responseText;
				response = response.split('//');
				
				<?php if (Conf::GetConf($sesion, 'GuardarTarifaAlIngresoDeHora')) { ?>

					var text_window = "<img src='<?php echo Conf::ImgDir() ?>/alerta_16.gif'>&nbsp;&nbsp;<span style='font-size:12px; color:#FF0000; text-align:center;font-weight:bold'><u><?php echo __("ALERTA") ?></u><br><br>";
					
					if( response[0] != 0 ) {
						if( response[0] < 2 ) {
							text_window += '<span style="text-align:center; font-size:11px; color:#000; "><?php echo __("El siguiente trabajo ") ?></span><br /><br />';
						} else if ( response[0] <= 10 ) {
							text_window += '<span style="text-align:center; font-size:11px; color:#000; "><?php echo __("Los siguientes trabajos ") ?></span><br><br>';
						} else {
							text_window += '<span style="text-align:center; font-size:11px; color:#000; "><?php echo __("hay más de 10 trabajos que") ?></span><br><br>';
						}
						for(i=1;i<response.length;i++) {
							var datos = response[i].split('~');
							if ( response[0] <= 10 ) {
								text_window += '<br /><span style="text-align:center; font-size:11px; color:#000; ">'+datos[1]+'</span> <a href="javascript:;" onclick="nuevaVentana(\'Editar_Trabajo\',600,500,\'editar_trabajo.php?id_cobro=&id_trabajo='+datos[0]+'&popup=1\',\'\');" style="color:blue;">Corregir aquí</a><br>';
							}
						}
						if( response[0] < 2 ) {
							text_window += '<span style="text-align:center; font-size:11px; color:#000; "><?php echo __(" no tiene tarifa definida.") ?></span><br>';
						} else {
							text_window += '<br><span style="text-align:center; font-size:11px; color:#000; "><?php echo __(" no tienen tarifa definida.") ?></span><br>';
						}
					}
					text_window += '<span style="text-align:center; font-size:11px; color:#000; "><?php echo __("Una vez efectuado") . " " . __("el cobro") . ", " . __("la información no podrá ser modificada sin reemitir") . " " . __("el cobro") . ", " . __("¿Está seguro que desea Emitir") . " " . __("el Cobro") . "?" ?></span><br>';
					text_window += '<br><table><tr>';
					text_window += '</table>';
					
				<?php } else { ?>

					var text_window = "<img src='<?php echo Conf::ImgDir() ?>/alerta_16.gif'>&nbsp;&nbsp;<span style='font-size:12px; color:#FF0000; text-align:center;font-weight:bold'><u><?php echo __("ALERTA") ?></u><br><br>";
					if( response[0] != 0 ) {
						if( response[0] < 2 )
							text_window += '<span style="text-align:center; font-size:11px; color:#000; "><?php echo __("La tarifa del abogado ") ?></span>';
						else
							text_window += '<span style="text-align:center; font-size:11px; color:#000; "><?php echo __("Las tarifas de los abogados ") ?></span><br><br>';
						for(i=1;i<response.length;i++)
						{
							var datos = response[i].split('~');
							if( response[0] < 2 )
								text_window += '<span style="text-align:center; font-size:11px; color:#000; ">'+datos[1]+'</span>';
							else
								text_window += '<span style="text-align:center; font-size:11px; color:#000; ">'+datos[1]+'</span><br>';
						}
						if( response[0] < 2 )
							text_window += '<span style="text-align:center; font-size:11px; color:#000; "><?php echo __(" no esta definido.") ?></span><br>';
						else
							text_window += '<br><span style="text-align:center; font-size:11px; color:#000; "><?php echo __(" no estan definidos.") ?></span><br>';
						text_window += '<a href="#" onclick="DefinirTarifas();" style="color:blue;">Definir tarifas</a><br><br>';
					}
					text_window += '<span style="text-align:center; font-size:11px; color:#000; "><?php echo __("Una vez efectuado") . " " . __("el cobro") . ", " . __("la información no podrá ser modificada sin reemitir") . " " . __("el cobro") . ", " . __("¿Está seguro que desea Emitir") . " " . __("el Cobro") . "?" ?></span><br>';
					text_window += '<br><table><tr>';
					text_window += '</table>';
				
				<?php } ?>

				Dialog.confirm(text_window,
				{
					top:150,
					left:290,
					width:400,
					okLabel: "<?php echo __('Continuar') ?>", cancelLabel: "<?php echo __('Cancelar') ?>", buttonClass: "btn", className: "alphacube",
					id: "myDialogId",

					cancel:function(win){ jQuery('#btn_emitir_cobro').removeAttr("disabled"); return false; },
					ok:function(win){
						var modulo_facturacion = <?php echo Conf::GetConf($sesion, 'NuevoModuloFactura') ? 'true' : 'false'; ?>;
						if( !AgregarParametros( form ) ) {
							return false;
						} else {
							var adelantos = $F('saldo_adelantos');
							var total = Number($F('total_honorarios'))+Number($F('total_gastos'));
							if(!modulo_facturacion && adelantos && confirm('Tiene disponibles '+adelantos+' en adelantos.\n¿Desea utilizarlos automáticamente para '+
								(Number(adelantos.replace(/[^\d\.]/g,'')) < total ? 'abonar' : 'pagar')+' este cobro?')){
								$('usar_adelantos').value = '1';
							}
							form.accion.value = 'emitir';
							form.opc.value = 'guardar_cobro';
							form.submit();
							return true;
						}
					}
				});
			}
		};
		http.send(null);
	}

	function DefinirTarifas()
	{
		var id_tarifa = document.getElementById('id_tarifa').value;
		nuevaVentana('Definir_Tarifas', 700, 550, 'agregar_tarifa.php?id_tarifa_edicion='+id_tarifa+'&popup=1','');
	}

	function ImprimirCobro(form)
	{
		if(!form) {
			var form = $('form_cobro5');
		}
		if( !AgregarParametros( form ) ) {
			return false;
		}
		form.accion.value = 'imprimir';
		form.opc.value = 'guardar_cobro';
		form.submit();
		return true;
	}

	function ImprimirCobroPDF(form)
	{
		if(!form) {
			var form = $('form_cobro5');
		}
		if( !AgregarParametros( form ) ) {
			return false;
		}
		form.accion.value = 'imprimir';
		form.opc.value = 'guardar_cobro_pdf';
		form.submit();
		return true;
	}

	function ImprimirExcel( form, formato_especial )
	{
		if(!form){
			var form = $('form_cobro5');
		}
		if( !AgregarParametros( form ) ) {
			return false;
		}
		if( formato_especial == 'rentabilidad' ) {
			form.accion.value = 'descargar_excel_rentabilidad';
		} else if( formato_especial == 'especial' ) {
			form.accion.value = 'descargar_excel_especial';
		} else {
			form.accion.value = 'descargar_excel';
		}
		form.opc.value = 'guardar_cobro';
		form.submit();
		return true;
	}

	function GuardaCobro( form )
	{
		if(!form) {
			var form = $('form_cobro5');
		}

		if( !AgregarParametros( form ) ) {
			return false;
		}

		form.accion.value = '';
		form.opc.value = 'guardar_cobro';
		form.submit();
		return true;
	}

	function ActualizarMontos( form )
	{
		if(!form) {
			var form = $('form_cobro5');
		}

		if(form.cobro_descuento.value=='') {
			alert("<?php echo __('Ud. debe ingresar un descuento a realizar.') ?>");
			form.cobro_descuento.focus();
			return false;
		}

		if ( !AgregarParametros( form ) )
			return false;

		form.opc.value = 'guardar_cobro';
		form.submit();
		return true;
	}

	function ShowMonto( showHoras, showUsuarios )
	{
		var div = document.getElementById("div_monto");
		div.style.display = "block";

		if( showHoras ) {
			div = document.getElementById("div_horas");
			div.style.display = "block";
			if( showUsuarios ) {
				div = document.getElementById("td_retainer_usuarios");
				div.style.display = "inline";
			} else {
				div = document.getElementById("td_retainer_usuarios");
				div.style.display = "none";
			}
		} else {
			div = document.getElementById("div_horas");
			div.style.display = "none";
		}
		document.getElementById('ajustar_monto').style.display = 'none';
	}

	function HideMonto()
	{
		var div = document.getElementById("div_monto");
		div.style.display = "none";

		div = document.getElementById("div_horas");
		div.style.display = "none";

		document.getElementById('ajustar_monto').style.display = 'inline';
	}

	function DisplayEscalas(mostrar) {
		var div = document.getElementById("div_escalonada");
		if( mostrar ){
			div.style.display = "block";
		} else {
			div.style.display = "none";
		}
	}

	function ActualizaRango(desde, cant) {
		var aplicar = parseInt(desde.substr(-1,1));
		var ini = 0;
		num_escalas = (document.getElementsByName('esc_tiempo[]')).length;
		for( var i = aplicar; i< num_escalas; i++){

			if( i > 1){
				ini = 0;
				for( var j = i; j > 1; j-- ){
					ini += parseFloat(document.getElementById('esc_tiempo_'+(j-1)).value, 10);
					if( ini.length == 0 || isNaN(ini)){
						ini = 0;
					}
				}
			}

			valor_actual = document.getElementById('esc_tiempo_'+(i)).value;
			if( i == aplicar ){
				if( cant.length > 0 && !isNaN(cant)){
					tiempo_final = parseFloat(ini,10) + parseFloat(cant,10);
				} else {
					tiempo_final = parseFloat(ini, 10);
				}
			} else {
				if( valor_actual.length > 0 && !isNaN(valor_actual)){
					tiempo_final = parseFloat(ini,10) + parseFloat(valor_actual,10);
				} else {
					tiempo_final = parseFloat(ini, 10);
				}
			}
			revisor = document.getElementById('esc_tiempo_'+(i)).value;
			if( valor_actual.length == 0 || isNaN(valor_actual)){
				ini = 0;
				tiempo_final = 0;
			}
			tiempo_final = Math.round(100*tiempo_final)/100;
			donde = document.getElementById('esc_rango_'+i);
			donde.innerHTML = ini + ' - ' + tiempo_final;
		}

	}

	function cambia_tipo_forma(valor, desde){
		var aplicar = parseInt(desde.substr(-1,1));
		var donde = 'tipo_forma_' + aplicar + '_';
		var selector = document.getElementById(desde);

		for( var i = 1; i <= selector.length; i++ ){
			if( i == valor ) {
				document.getElementById(donde+i).style.display = 'inline-block';
			} else {
				document.getElementById(donde+i).style.display = 'none';
			}
		}
	}

	function setear_valores_escalon( donde, desde, tiempo, tipo, id_tarifa, monto, id_moneda, descuento ) {
		if( desde != '' ) {
			/* si le paso desde donde copiar, los utilizo */
			document.getElementById('esc_tiempo_' + donde).value = document.getElementById('esc_tiempo_' + desde).value;
			document.getElementById('esc_selector_' + donde).value = document.getElementById('esc_selector_' + desde).value;
			cambia_tipo_forma(document.getElementById('esc_selector_' + desde).value, 'esc_selector_' + donde);
			document.getElementById('esc_id_tarifa_' + donde).value = document.getElementById('esc_id_tarifa_' + desde).value;
			document.getElementById('esc_monto_' + donde).value = document.getElementById('esc_monto_' + desde).value;
			document.getElementById('esc_id_moneda_' + donde).value = document.getElementById('esc_id_moneda_' + desde).value;
			document.getElementById('esc_descuento_' + donde).value = document.getElementById('esc_descuento_' + desde).value;
		} else {
			/* sino utilizo los valores entregados individualmente */
			document.getElementById('esc_tiempo_' + donde).value = tiempo;
			document.getElementById('esc_selector_' + donde).value = tipo;
			cambia_tipo_forma(1,'esc_selector_' + donde);
			document.getElementById('esc_id_tarifa_' + donde).value = id_tarifa;
			document.getElementById('esc_monto_' + donde).value = monto;
			document.getElementById('esc_id_moneda_' + donde).value = id_moneda;
			document.getElementById('esc_descuento_' + donde).value = descuento;

		}
	}

	function agregar_eliminar_escala(divID){
		var numescala = parseInt(divID.substr(-1,1));
		var divArea = document.getElementById(divID);
		var divAreaImg = document.getElementById(divID+"_img");
		var divAreaVisible = divArea.style['display'] != "none";
		var esconder = "";

		if( !divAreaVisible ){
			for( var i = numescala; i> 1; i--){
				var valor_anterior = document.getElementById('esc_tiempo_'+(i-1)).value;
				if( valor_anterior != '' && valor_anterior > 0 ){
					divArea.style['display'] = "inline-block";
					divAreaImg.innerHTML = "<img src='../templates/default/img/menos.gif' border='0' title='Ocultar'> Eliminar";
				} else {
					alert('No puede agregar un escalón nuevo, si no ha llenado los datos del escalón actual');
					return 0;
				}
			}
		} else {
			num_escalas = (document.getElementsByName('esc_tiempo[]')).length;
			esconder = divID;
			for( var i = numescala; i <= (num_escalas-2) ; i++ ){
				var siguiente = document.getElementById('esc_tiempo_'+(parseInt(i)+1));
				if( siguiente.style.display != "none"){
					valor_siguiente = document.getElementById('esc_tiempo_'+(parseInt(i)+1)).value;
					if( valor_siguiente > 0 ){
						setear_valores_escalon(i, (i+1),0,1,1,0,1,0);
						ActualizaRango('esc_tiempo_'+i, document.getElementById('esc_tiempo_'+(i+1)).value);
						setear_valores_escalon((i+1), '','',1,1,'',1,'');
						ActualizaRango('esc_tiempo_'+(parseInt(i)+1), '');
						esconder = "escalon_" + (parseInt(numescala)+1);

					} else {
						id_sgte = "escalon_" +(parseInt(i)+1);
						document.getElementById(id_sgte).style.display = "none";
						document.getElementById(id_sgte+"_img").innerHTML = "<img src='../templates/default/img/mas.gif' border='0' title='Desplegar'> Agregar";
					}
				} else {
					setear_valores_escalon(i, '','',1,1,'',1,'');
					ActualizaRango('esc_tiempo_'+i, '');
					esconder = "escalon_" + i;
					/*i = num_escalas;*/
				}
			}
			setear_valores_escalon(parseInt(esconder.substr(-1,1)), '','',1,1,'',1,'');
			ActualizaRango('esc_tiempo_'+esconder.substr(-1,1), '');
			document.getElementById(esconder).style.display = 'none';
			divAreaImg = document.getElementById(esconder+"_img");
			divAreaImg.innerHTML = "<img src='../templates/default/img/mas.gif' border='0' title='Desplegar'> Agregar";
		}
	}


	function RecalcularTotal(desc) //isCap -> pasa true si es forma_cobro CAP
	{
		var subtotal = parseFloat(document.getElementById("cobro_subtotal").value);
		var descuento = parseFloat(desc); //document.getElementById("cobro_descuento").value);
		var totalObj = document.getElementById("cobro_total");
		var form = document.getElementById("form_cobro5");
		var cobroMontoContrato = document.getElementById("cobro_monto_contrato");

		if (jQuery('#fc5').attr('checked') != 'checked') {
			if (parseFloat(descuento) > parseFloat(subtotal)) {
				descuento = 0;
				form.cobro_descuento.value = 0;
			}
		} else {
			if (parseFloat(descuento) > parseFloat(cobroMontoContrato.value)) {
				descuento = 0;
				form.cobro_descuento.value = 0;
				alert('El descuento aplicado no puede ser superior al monto definido en el CAP.');
			}
		}
		if( isNaN(descuento) ) {
			descuento = 0;
		}
		var impuesto=0;
		<?php
			if ( Conf::GetConf($sesion, 'UsarImpuestoSeparado')  || $contrato->fields['usa_impuesto_separado']) {
				?>
						var campoImpuesto = document.getElementById("cobro_impuesto");
						valorImpuesto = (subtotal - descuento)*(<?php echo $cobro->fields['porcentaje_impuesto'] ? $cobro->fields['porcentaje_impuesto'] : 0 ?>)/100;
						campoImpuesto.value = valorImpuesto.toFixed(2);
						impuesto = parseFloat(campoImpuesto.value);

				<?php
			}
		?>
		valorTotal = subtotal - descuento + impuesto;
		totalObj.value = valorTotal.toFixed(2);
	}


	function ToggleDiv( divId )
	{
		var divObj = document.getElementById( divId );

		if( divObj )
		{
			if( divObj.style.display == 'none' )
				divObj.style.display = 'inline';
			else
				divObj.style.display = 'none';
		}
	}

	function ActualizarTipoCambio( form, valor )
	{
		form.cobro_tipo_cambio.value = valor;
	}

	function ActualizarPadre()
	{
		if( window.opener.Refrescar )
			window.opener.Refrescar(<?php echo $id_foco ?>);
	}

	/*Array tipo de cambios de prm_moneda JS*/
	var tipo_cambio = new Array(false);
	
	<?php
	$monedas = new ListaMonedas($sesion, '', 'SELECT * FROM prm_moneda');
	for ($i = 0; $i < $monedas->num; $i++) {
	$moneda = $monedas->Get($i);
	?>
		
	tipo_cambio[<?php echo $moneda->fields['id_moneda'] ?>]= <?php echo $moneda->fields['tipo_cambio'] ?>;
	<?php } ?>



	/* Actualiza los tipos de cambio al cambio actual de cada moneda */
	function UpdateTipoCambio( form )
	{
		var form = document.getElementById('form_cobro5');
		var id_cobro = document.getElementById('id_cobro').value;

		if(confirm('<?php echo __("¿Desea actualizar al tipo de cambio actual?") ?>'))
		{
			var http = getXMLHTTP();
			http.open('get', 'ajax.php?accion=update_cobro_moneda&id_cobro='+id_cobro);
			http.onreadystatechange = function()
			{
				if(http.readyState == 4)
				{
					var response = http.responseText;
					if(response)
					{
						msg_div = $('msg_cambio');
						msg_div.style.display = 'inline';
						form.opc.value = 'up_cambios';
						form.submit();
					}
				}
			};
			http.send(null);
		}
	}

	/* Ajax guarda tipo de cambio en cobro_moneda */
	function GuardaTipoCambio( id_moneda, tipo_cambio )
	{
		var form = jQuery('#form_cobro5');
		var msg_cambio = $('msg_cambio');

		if(!parseFloat(tipo_cambio) || parseFloat(tipo_cambio) == 0)
		{
			alert('<?php echo __("El monto ingresado del tipo de cambio es incorrecto") ?>');
			var field_tipo_cambio = 'cobro_tipo_cambio_'+id_moneda;
			var tipo_cambio_id = $(field_tipo_cambio);
			tipo_cambio_id.value = 1;
			tipo_cambio_id.focus();
			return false;
		} 	else 	{
			var id_cobro = $('id_cobro').value;
			tipo_cambio = tipo_cambio.replace(',','.');
			if(window.console) console.log(id_moneda+' tipo cambio es'+tipo_cambio);


			jQuery.get('ajax_grabar_campo.php?accion=guardar_tipo_cambio&id_cobro='+id_cobro+'&id_moneda='+id_moneda+'&tipo_cambio='+tipo_cambio,function(data) {

				if(data=='OK')		{
					msg_cambio.style.display = 'inline';
					return true;
				} else {
					return false;
				}
			});



		}
	}


	function ActualizarSaldoAdelantos(){
		var tipos_cambio = [];
		$$('[id^="cobro_tipo_cambio_"]').each(function(elem){
			tipos_cambio.push(elem.id.substr('cobro_tipo_cambio_'.length)+':'+elem.value);
		});
		var http = getXMLHTTP();
		http.open('get', 'ajax.php?accion=saldo_adelantos&codigo_cliente=<?php echo $cobro->fields['codigo_cliente'] ?>&id_contrato=<?php echo $cobro->fields['id_contrato'] ?>&pago_honorarios='+(Number($F('total_honorarios'))>0?1:0)+'&pago_gastos='+(Number($F('total_gastos'))>0?1:0)+'&id_moneda='+$F('opc_moneda_total')+'&tipocambio='+tipos_cambio.join(';'));
		http.onreadystatechange = function()
		{
			if(http.readyState == 4)
			{
				var response = http.responseText;
				if(response)
				{
					$('saldo_adelantos').value = response;
					return true;
				}
				else
					return false;
			}
		};
		http.send(null);
	}


	/*CANCELA UPDATE CAP*/
	function CancelaUpdateCap()
	{
		var form = $('form_cobro5');
		form.cobro_monto_contrato.value = parseFloat(form.monto_contrato.value);
		return true;
	}

	/* UPDATE valor de cap para COBRO y CONTRATO asosiado */
	function UpdateCap(monto_update, guardar)
	{
		if(!guardar)
		{
			var text_window = "<img src='<?php echo Conf::ImgDir() ?>/alerta_16.gif'>&nbsp;&nbsp;<span style='font-size:12px; color:#FF0000; text-align:center;font-weight:bold'><u><?php echo __("ALERTA") ?></u><br><br>";
			text_window += '<span style="text-align:center; font-size:11px; color:#000; "><?php echo __('Ud. está modificando el valor del CAP. Si Ud. modifica ese valor, también se modificará el valor del CAP en el contrato asociado') . ', ' . __('el valor del CAP según contrato es de') . ': <u>' . $contrato->fields['monto'] . ' ' . $moneda_cobro->fields['glosa_moneda'] . '</u><br><br>' . __('¿desea realizar esta operación?') ?></span><br>';
			Dialog.confirm(text_window,
			{
				top:250, left:290, width:400, okLabel: "<?php echo __('Aceptar') ?>", cancelLabel: "<?php echo __('Cancelar') ?>", buttonClass: "btn", className: "alphacube",
				id: "myDialogId",
				cancel:function(win){ CancelaUpdateCap() },
				ok:function(win){ UpdateCap(monto_update,true); }
			});
		}

		if(!parseFloat(monto_update))
			return false;
		var form = $('form_cobro5');
		var id_cobro = $('id_cobro').value;
		var id_contrato = $('id_contrato').value;
		var id_moneda_monto = $('id_moneda_monto').value;

		if(guardar == true)
		{
			var http = getXMLHTTP();
			http.open('get', 'ajax.php?accion=update_cap&id_cobro='+id_cobro+'&id_contrato='+id_contrato+'&monto_update='+monto_update+'&id_moneda_monto='+id_moneda_monto);
			http.onreadystatechange = function()
			{
				if(http.readyState == 4)
				{
					var response = http.responseText;
					if(response)
					{
						var form_montos = $('form_cobro5');
						form_montos.submit();
						return true;
					}
				}
			};
			http.send(null);
		}
		else
			return false;
	}
	// -->
</script>

<?php
$x_resultados = UtilesApp::ProcesaCobroIdMoneda($sesion, $cobro->fields['id_cobro'], array(), 0, false);

#Para revisar si existen facturas (No puede volver a creado).
$query = "select count(*) from factura_cobro fc join factura f using(id_factura)  where   f.estado!='ANULADA' AND fc.id_cobro= '" . $cobro->fields['id_cobro'] . "'";
$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
list($numero_facturas_asociados) = mysql_fetch_array($resp);
if ($numero_facturas_asociados > 0)
	$existe_factura = 1;
else
	$existe_factura = 0;
?>

<form method="post" id="form_cobro5" name="form_cobro5" >
	<input type="hidden" name="existe_factura" id="existe_factura" value="<?php echo $existe_factura ?>" />
	<input type="hidden" name="id_cobro" id="id_cobro" value="<?php echo $id_cobro ?>">
	<input type="hidden" name="ajustar_monto_hide" id="ajustar_monto_hide" value="<?php echo $cobro->fields['monto_ajustado'] > 0 ? true : false ?>" />
	<input type="hidden" name="opc" value="guardar_cobro">
	<input type="hidden" name="id_contrato" value="<?php echo $cobro->fields['id_contrato'] ?>" id="id_contrato">
	<input type="hidden" name="excedido" value="<?php echo $excedido ?>" />
	<input type="hidden" name="monto_contrato" id="monto_contrato" value="<?php echo $cobro->fields['monto_contrato'] ?>">
	<input type="hidden" name="cobro_tipo_cambio" value="<?php echo $cobro->fields['tipo_cambio_moneda'] ?>" size="8">
	<input type="hidden" name="id_tarifa" id="id_tarifa" value="<?php echo $contrato->fields['id_tarifa'] ?>" />
	<input type="hidden" name="accion" value="" id="accion">
	<input type="hidden" name="saldo_adelantos" value="<?php
		$documento = new Documento($sesion);
		$pago_honorarios = (float) ($cobro->fields['monto_subtotal']) ? 1 : 0;
		$pago_gastos = (float) ($cobro->fields['subtotal_gastos']) ? 1 : 0;
		$cobro_moneda = new ListaMonedas($sesion, '', 'SELECT * FROM cobro_moneda WHERE id_cobro = ' . $id_cobro);
		$tipo_cambio_cobro = Array();
		
		for ($i = 0; $i < $monedas->num; $i++) {
			$cambio_moneda = $cobro_moneda->Get($i);
			if (empty($cambio_moneda->fields['tipo_cambio'])) {
				$moneda = $monedas->Get($i);
				$tipo_cambio_cobro[$cambio_moneda->fields['id_moneda']] = $moneda->fields['tipo_cambio'];
			} else {
				$tipo_cambio_cobro[$cambio_moneda->fields['id_moneda']] = $cambio_moneda->fields['tipo_cambio'];
			}
		}

		echo $documento->SaldoAdelantosDisponibles($cobro->fields['codigo_cliente'], $cobro->fields['id_contrato'], $pago_honorarios, $pago_gastos, $cobro->fields['opc_moneda_total'], $tipo_cambio_cobro);
		
		?>" id="saldo_adelantos" />
	<input type="hidden" name="usar_adelantos" value="" id="usar_adelantos" />

	<table width="720px">
		<tr>
			<td align="left"><input type="button" class="btn" value="<?php echo __('<< Anterior') ?>" onclick="Anterior(this.form);"></td>
			<td align="right">

			<?php if ($cobro->fields['estado'] == 'CREADO') { ?>
					<input type="button" class="btn" value="<?php echo __('Revisar Cobro') ?>" onclick="EnRevision(this.form);">
			<?php } else if ($cobro->fields['estado'] == 'EN REVISION') { ?>
					En Revisión. &nbsp;&nbsp;
					<input type="button" class="btn" value="<?php echo __('Volver al estado CREADO') ?>" onclick="VolverACreado(this.form);">
			<?php }	?>
				<input type="button" class="btn" id="btn_emitir_cobro" value="<?php echo __('Emitir Cobro') ?>" onclick="Emitir(this.form);">
			</td>
		</tr>
	</table>

	<br>

	<table width="100%" cellspacing="3" cellpadding="3">
		<tr>
			<td align="left" style="background-color: #A3D55C; color: #000000; font-size: 14px; font-weight: bold;">
				<?php echo __('parámetros del Cobro') ?>
			</td>
		</tr>
	</table>

	<?php if (!empty($cobro->fields['incluye_honorarios'])) { ?>
	
		<fieldset id="periodo" style="width: 95%">
			<legend><?php echo __('Periodo') ?></legend>
			<table width="100%" cellspacing="3" cellpadding="3">
				<tr>
					<td align="center">
					<?php echo __('Periodo') . ': ' ?>
					<?php echo $cobro->fields['fecha_ini'] != '0000-00-00' ? __('Desde') . ': ' . Utiles::sql2date($cobro->fields['fecha_ini']) . ' ' : '' ?>
					<?php echo $cobro->fields['fecha_fin'] != '0000-00-00' ? __('Hasta') . ': ' . Utiles::sql2date($cobro->fields['fecha_fin']) : '' ?>
						&nbsp;&nbsp;&nbsp;<a href='cobros3.php?id_cobro=<?php echo $cobro->fields['id_cobro'] ?>&popup=1' title='<?php echo __('Editar periodo') ?>'><span style='font-size:11px'>Editar</span></a>
					</td>
				</tr>
			</table>
		</fieldset>

	<?php } ?>

	<!-- Moneda -->
	<fieldset id="moneda" style="width: 95%">
		<legend><?php echo __('Moneda') ?></legend>
		<table width="100%" cellspacing="3" cellpadding="3">
			<tr>
				<td align="center">
					<table width="95%">
						<tr>
							<td colspan='<?php echo $monedas->num ?>' align='left' style='padding-left:20px; padding-right:10px'>
								<table width="100%">
									<tr>
										<td align="left">
											<span style="font-size:9px; color:#FF7D7D; font-style:italic">Ingresar decimales con punto. Ejemplo 23024.33</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
										</td>
										<td align=right>
											<span "style"="font-size:9px; color:#FF7D7D; font-style:italic;">Actualizar a los tipos de cambio actuales</span>&nbsp;&nbsp;<img <?php echo TTip($tip_refresh) ?> style="cursor:pointer" src="<?php echo Conf::ImgDir() ?>/download_from_web.gif" onclick="UpdateTipoCambio(this.form)">
										</td>
									</tr>
								</table>
							</td>
						</tr>
						
						<tr>

						<?php
						/* Lista de moneda del cobro */
						$cobro_moneda = new ListaMonedas($sesion, '', 'SELECT * FROM cobro_moneda WHERE id_cobro = ' . $id_cobro);
						$tipo_cambio_cobro = Array();
						
						for ($i = 0; $i < $monedas->num; $i++) {
							$cambio_moneda = $cobro_moneda->Get($i);
							$tipo_cambio_cobro[$cambio_moneda->fields['id_moneda']] = $cambio_moneda->fields['tipo_cambio'];
						}

						for ($i = 0; $i < $monedas->num; $i++) {
							$moneda = $monedas->Get($i);
						?>
								<td align='center' style='padding-left:10px; padding-right:10px'>
									<input type="radio" id="cobro_id_moneda<?php echo $i ?>" name="cobro_id_moneda"  value="<?php echo $moneda->fields['id_moneda'] ?>" <?php echo $moneda->fields['id_moneda'] == $cobro->fields['id_moneda'] ? 'checked' : '' ?> onclick="ActualizarTipoCambio(this.form, '<?php echo $moneda->fields['tipo_cambio'] ?>');" ><label for="cobro_id_moneda<?php echo $i ?>"><?php echo $moneda->fields['glosa_moneda'] ?></label>
								</td>
						<?php } ?>
						
						</tr>
						
						<input type="hidden" name="monedas_num" value=<?php echo $monedas->num > 0 ? $monedas->num : 0 ?> id="monedas_num">
						
						<tr>

						<?php for ($i = 0; $i < $monedas->num; $i++) {
							$moneda = $monedas->Get($i);
							$tipo = $tipo_cambio_cobro[$moneda->fields['id_moneda']];
						?>
							<td align='center' style='padding-left:10px; padding-right:10px'>
								<input type="text" size="8" name="cobro_tipo_cambio_<?php echo $moneda->fields['id_moneda'] ?>" id="cobro_tipo_cambio_<?php echo $moneda->fields['id_moneda'] ?>" value="<?php echo $tipo > 0 ? number_format($tipo, max($moneda->fields['cifras_decimales'], 7), '.', '') : number_format($moneda->fields['tipo_cambio'], max($moneda->fields['cifras_decimales'], 7), '.', '') ?>" onchange="GuardaTipoCambio(<?php echo $moneda->fields['id_moneda'] ?>,this.value)">
							</td>
						<?php } ?>

						</tr>

						<tr>
							<td colspan='<?php echo $monedas->num ?>' align='center' style='padding-left:20px; padding-right:10px'>
								<div id='msg_cambio' style='font-size:10px;display:none;color:#FF7D7D'><?php echo __('Los tipos de cambio han sido actualizados correctamente') ?></div>
							</td>
						</tr>

					</table>
				</td>
			</tr>
		</table>
	</fieldset>
	<!-- fin Moneda -->

	<!-- Modalidad -->
	<fieldset id="forma_cobro" style="width: 95%; display: <?php echo ( $cobro->fields['incluye_honorarios'] != 0 ? "block" : "none"); ?>">
		<legend><?php echo __('Forma de cobro') ?></legend>
		<table width='100%' cellspacing='3' cellpadding='3'>
			<tr>
				<td align="center">
					<?php echo __('Forma de cobro') ?>
				</td>
				<td align="center">
					<?php if ($cobro->fields['forma_cobro'] == '') {
						$cobro_forma_cobro = 'TASA';
					} else {
						$cobro_forma_cobro = $cobro->fields['forma_cobro'];
					}

					if (!is_array($usuarios_retainer)) {
						$usuarios_retainer = explode(',', $cobro->fields['retainer_usuarios']);
					}

					?>

					<input <?php echo TTip($tip_tasa) ?> onclick="HideMonto();DisplayEscalas(false);" id="fc1" type="radio" name="cobro_forma_cobro" value="TASA" <?php echo $cobro_forma_cobro == "TASA" ? "checked" : "" ?> />
					<label for="fc1">Tasas/HH</label>&nbsp; &nbsp;
					<input <?php echo TTip($tip_retainer) ?> onclick="ShowMonto(true, true);DisplayEscalas(false);" id="fc3" type="radio" name="cobro_forma_cobro" value="RETAINER" <?php echo $cobro_forma_cobro == "RETAINER" ? "checked" : "" ?> />
					<label for="fc3">Retainer</label> &nbsp; &nbsp;
					<input <?php echo TTip($tip_flat) ?> onclick="ShowMonto(false, false);DisplayEscalas(false);" id="fc4" type="radio" name="cobro_forma_cobro" value="FLAT FEE" <?php echo $cobro_forma_cobro == "FLAT FEE" ? "checked" : "" ?> />
					<label for="fc4">Flat fee</label>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
					<?php if($cobro->fields['id_contrato']){ ?>
					<input <?php echo TTip($tip_cap) ?> onclick="ShowMonto(false, false);DisplayEscalas(false);" id="fc5" type="radio" name="cobro_forma_cobro" value="CAP" <?php echo $cobro_forma_cobro == "CAP" ? "checked" : "" ?> />
					<label for="fc5"><?php echo __('Cap') ?></label>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
					<?php } ?>
					<input <?php echo TTip($tip_proporcional) ?> onclick="ShowMonto(true, false);DisplayEscalas(false);" id="fc6" type=radio name="cobro_forma_cobro" value="PROPORCIONAL" <?php echo $cobro_forma_cobro == "PROPORCIONAL" ? "checked" : "" ?> />
					<label for="fc6">Proporcional</label> &nbsp; &nbsp;
					
					<?php if (!Conf::GetConf($sesion, 'EsconderTarifaEscalonada')) { ?>
						<input <?php echo TTip($tip_escalonada) ?> id="fc7" type=radio name="cobro_forma_cobro" onclick="HideMonto();DisplayEscalas(true);" value="ESCALONADA" <?php echo $cobro_forma_cobro == "ESCALONADA" ? "checked" : "" ?> />
						<label for="fc7">Escalonada</label>
					<?php } ?>
					&nbsp; &nbsp;

					<div id="div_monto" align="left" style="display:none; background-color:#F8FBBD; padding-left:20px">
						<table>
							<tr>
								<td>
									<?php echo __('Monto') ?>
								</td>
								<td>
									<input id="cobro_monto_contrato" name="cobro_monto_contrato" size="7" value="<?php echo $cobro->fields['monto_contrato'] ?>" <?php echo $cobro->fields['id_contrato'] && $cobro->fields['forma_cobro'] == 'CAP' ? 'onchange="UpdateCap(this.value, false)"' : '' ?>>

								</td>
								<td>
								&nbsp;&nbsp;&nbsp;&nbsp;
								<?php echo __('Moneda') ?>&nbsp;
								<?php echo Html::SelectQuery($sesion, "SELECT id_moneda,glosa_moneda FROM prm_moneda ORDER BY id_moneda", "id_moneda_monto", $cobro->fields['id_moneda_monto'] ? $cobro->fields['id_moneda_monto'] : $id_moneda_monto, '', '', "80"); ?>
								</td>
							</tr>
						</table>
					</div>

					<div id="div_horas" align="left" style="display:none; background-color:#F8FBBD; padding-left:20px">
						<table>
							<tr>
								<td align=left style="vertical-align: top;">
									<?php echo __('Horas') ?>			
								</td>
								<td align=left style="vertical-align: top;">
									<input name="cobro_retainer_horas" size="7" value="<?php echo $cobro->fields['retainer_horas'] ?>" />
								</td>
								<!-- Incluiremos un multiselect de usuarios para definir los usuarios de quienes se
										 desuentan las horas con preferencia -->
								<?php if (Conf::GetConf($sesion, 'RetainerUsuarios')) { ?>
									<td id="td_retainer_usuarios" align="left" style="display:inline; background-color:#F8FBBD; padding-left:20px">
										&nbsp;<?php echo __('Usuarios') ?>
										&nbsp;<?php echo Html::SelectQuery($sesion, "SELECT usuario.id_usuario, CONCAT_WS(' ', nombre, apellido1, apellido2) FROM usuario JOIN usuario_permiso USING( id_usuario ) WHERE usuario.activo = 1 AND codigo_permiso = 'PRO'", 'usuarios_retainer[]', $usuarios_retainer, TTip($tip_retainer_usuarios) . " class=\"selectMultiple\" multiple size=3 ", "", "160"); ?>
									</td>
								<?php } ?>
							</tr>
						</table>
					</div>

					<?php
						$rango1 = ( $cobro->escalonadas[1]['tiempo_inicial'] ?
										$cobro->escalonadas[1]['tiempo_inicial'] : '0' ) . ' - ' . ( $cobro->escalonadas[1]['tiempo_final'] ?
										$cobro->escalonadas[1]['tiempo_final'] : '' );
						$rango2 = ( $cobro->escalonadas[2]['tiempo_inicial'] ?
										$cobro->escalonadas[2]['tiempo_inicial'] : '0' ) . ' - ' . ( $cobro->escalonadas[2]['tiempo_final'] ?
										$cobro->escalonadas[2]['tiempo_final'] : '' );
						$rango3 = ( $cobro->escalonadas[3]['tiempo_inicial'] ?
										$cobro->escalonadas[3]['tiempo_inicial'] : '0' ) . ' - ' . ( $cobro->escalonadas[3]['tiempo_final'] ?
										$cobro->escalonadas[3]['tiempo_final'] : '' );
						$ultimo_rango = ( $cobro->escalonadas[$cobro->escalonadas['num']]['tiempo_inicial'] ?
										$cobro->escalonadas[$cobro->escalonadas['num']]['tiempo_inicial'] : '0' ) . ' - ' . ( $cobro->escalonadas[$cobro->escalonadas['num']]['tiempo_final'] ?
										$cobro->escalonadas[$cobro->escalonadas['num']]['tiempo_final'] : '' );
					?>

					<div id="div_escalonada" align="left" style="display:<?php echo ($cobro_forma_cobro == "ESCALONADA") ? "block" : "none" ?>; background-color:#F8FBBD; padding-left:20px">
						
						<div class="template_escalon" id="escalon_1">
						
							<table style='padding: 5px; border: 0px solid' bgcolor='#F8FBBD'>
								<tr>
									<td valign="bottom">
										<div style="display:inline-block; width: 65px;"><?php echo __('Las primeras'); ?> </div>

											<input type="text" name="esc_tiempo[]" id="esc_tiempo_1" size="4" value="<?php if (!empty($cobro->fields['esc1_tiempo'])) echo $cobro->fields['esc1_tiempo']; else echo '0'; ?>" onkeyup="ActualizaRango(this.id , this.value);" />
											<span><?php echo __('horas trabajadas'); ?> (</span>
											<div id="esc_rango_1" style="display:inline-block; width: 50px; text-align: center;"><?php echo $rango1; ?></div> 
											<span>) <?php echo __('aplicar'); ?></span>
								
											<select name="esc_selector[]" id="esc_selector_1" onchange="cambia_tipo_forma(this.value, this.id);">
												<option value="1" <?php echo!isset($cobro->fields['esc1_monto']) || $cobro->fields['esc1_monto'] == 0 ? 'selected="selected"' : ''; ?>>tarifa</option>
												<option value="2" <?php echo $cobro->fields['esc1_monto'] > 0 ? 'selected="selected"' : ''; ?> >monto</option>
											</select>

											<span>
												<span id="tipo_forma_1_1" <?php echo!isset($cobro->fields['esc1_monto']) || $cobro->fields['esc1_monto'] == 0 ? 'style="display: inline-block;"' : 'style="display: none;"' ?> >
													<?php echo Html::SelectQuery($sesion, "SELECT id_tarifa, glosa_tarifa FROM tarifa", "esc_id_tarifa_1", $cobro->fields['esc1_id_tarifa'], 'style="font-size:9pt; width:120px;"'); ?>
												</span>
												<span id="tipo_forma_1_2" <?php echo $cobro->fields['esc1_monto'] > 0 ? 'style="display: inline-block;"' : 'style="display: none;"'; ?> >
													<input type="text" size="8" style="font-size:9pt; width:116px;" id="esc_monto_1" value="<?php echo ($cobro->fields['esc1_monto'] > 0) ? $cobro->fields['esc1_monto'] : '0'; ?>" name="esc_monto[]" />
												</span>
											</span>

											<span><?php echo __('en'); ?></span>
											<?php echo Html::SelectQuery($sesion, "SELECT id_moneda, glosa_moneda FROM prm_moneda ORDER BY id_moneda", 'esc_id_moneda_1', $cobro->fields['esc1_id_moneda'], 'style="font-size:9pt; width:70px;"'); ?>
											<span><?php echo __('con'); ?> </span>
											<input type="text" name="esc_descuento[]" id="esc_descuento_1" value="<?php if (!empty($cobro->fields['esc1_descuento'])) echo $cobro->fields['esc1_descuento']; else echo '0'; ?>" size="4" />
											<span><?php echo __('% dcto.'); ?> </span>

										</div>
									</td>
								</tr>
							</table>

							<div onclick="agregar_eliminar_escala('escalon_2')" style="cursor:pointer;" >
								<span id="escalon_2_img"><?php echo!($cobro->fields['esc2_tiempo'] > 0) ? '<img src="' . Conf::ImgDir() . '/mas.gif" border="0" id="datos_cobranza_img"> ' . __('Agregar') : '<img src="' . Conf::ImgDir() . '/menos.gif" border="0" id="datos_cobranza_img"> ' . __('Eliminar') ?>	</span>
							</div>

						</div>

						<div class="template_escalon" id="escalon_2" style="display: <?php echo isset($cobro->fields['esc2_tiempo']) && $cobro->fields['esc2_tiempo'] > 0 ? 'block' : 'none'; ?>;">
							
							<table style='padding: 5px; border: 0px solid' bgcolor='#F8FBBD'>
								<tr>
									<td valign="bottom">

										<div style="display:inline-block; width: 65px;"><?php echo __('Las siguientes'); ?> </div>
										<input type="text" name="esc_tiempo[]" id="esc_tiempo_2" size="4" value="<?php if (!empty($cobro->fields['esc2_tiempo'])) echo $cobro->fields['esc2_tiempo']; else echo '0'; ?>" onkeyup="ActualizaRango(this.id , this.value);" />
										<span><?php echo __('horas trabajadas'); ?> (</span> <div id="esc_rango_2" style="display:inline-block; width: 50px; text-align: center;"><?php echo $rango2; ?></div> <span>) <?php echo __('aplicar'); ?></span>
										
										<select name="esc_selector[]" id="esc_selector_2" onchange="cambia_tipo_forma(this.value, this.id);">
											<option value="1" <?php echo!isset($cobro->fields['esc2_monto']) || $cobro->fields['esc1_monto'] == 0 ? 'selected="selected"' : ''; ?>>tarifa</option>
											<option value="2" <?php echo $cobro->fields['esc2_monto'] > 0 ? 'selected="selected"' : ''; ?> >monto</option>
										</select>
										
										<span>
											<span id="tipo_forma_2_1" <?php echo!isset($cobro->fields['esc2_monto']) || $cobro->fields['esc2_monto'] == 0 ? 'style="display: inline-block;"' : 'style="display: none;"' ?> >
												<?php echo Html::SelectQuery($sesion, "SELECT id_tarifa, glosa_tarifa FROM tarifa", "esc_id_tarifa_2", $cobro->fields['esc2_id_tarifa'], 'style="font-size:9pt; width:120px;"'); ?>
											</span>
											<span id="tipo_forma_2_2" <?php echo $cobro->fields['esc2_monto'] > 0 ? 'style="display: inline-block;"' : 'style="display: none;"'; ?>>
												<input type="text" size="8" style="font-size:9pt; width:116px;" id="esc_monto_2" name="esc_monto[]" value="<?php echo ($cobro->fields['esc2_monto'] > 0) ? $cobro->fields['esc2_monto'] : '0'; ?>" />
											</span>
										</span>
										
										<span><?php echo __('en'); ?></span>
										<?php echo Html::SelectQuery($sesion, "SELECT id_moneda, glosa_moneda FROM prm_moneda ORDER BY id_moneda", 'esc_id_moneda_2', $cobro->fields['esc2_id_moneda'], 'style="font-size:9pt; width:70px;"'); ?>
										<span><?php echo __('con'); ?> </span>
										<input type="text" name="esc_descuento[]" value="<?php if (!empty($cobro->fields['esc2_descuento'])) echo $cobro->fields['esc2_descuento']; else echo ''; ?>" id="esc_descuento_2" size="4" />
										<span><?php echo __('% dcto.'); ?> </span>

									</td>
								</tr>
							</table>

							<div onclick="agregar_eliminar_escala('escalon_3')" style="cursor:pointer;" >
								<span id="escalon_3_img"><?php echo!($cobro->fields['esc3_tiempo'] > 0) ? '<img src="' . Conf::ImgDir() . '/mas.gif" border="0" id="datos_cobranza_img"> ' . __('Agregar') : '<img src="' . Conf::ImgDir() . '/menos.gif" border="0" id="datos_cobranza_img"> ' . __('Eliminar') ?>	</span>
							</div>

						</div>

						<div class="template_escalon" id="escalon_3" style="display: <?php echo isset($cobro->fields['esc3_tiempo']) && $cobro->fields['esc3_tiempo'] > 0 ? 'block' : 'none'; ?>;">
							
							<table style='padding: 5px; border: 0px solid' bgcolor='#F8FBBD'>
								<tr>
									<td valign="bottom">
										<div style="display:inline-block; width: 65px;"><?php echo __('Las siguientes'); ?> </div>
										<input type="text" name="esc_tiempo[]" id="esc_tiempo_3" size="4" value="<?php if (!empty($cobro->fields['esc3_tiempo'])) echo $cobro->fields['esc3_tiempo']; else echo '0'; ?>" onkeyup="ActualizaRango(this.id , this.value);" />
										<span><?php echo __('horas trabajadas'); ?> (</span> <div id="esc_rango_3" style="display:inline-block; width: 50px; text-align: center;"><?php echo $rango3; ?></div> <span>) <?php echo __('aplicar'); ?></span>
										
										<select name="esc_selector[]" id="esc_selector_3" onchange="cambia_tipo_forma(this.value, this.id);">
											<option value="1" <?php echo!isset($cobro->fields['esc3_monto']) || $cobro->fields['esc1_monto'] == 0 ? 'selected="selected"' : ''; ?>>tarifa</option>
											<option value="2" <?php echo $cobro->fields['esc3_monto'] > 0 ? 'selected="selected"' : ''; ?> >monto</option>
										</select>
										
										<span>
											<span id="tipo_forma_3_1" <?php echo!isset($cobro->fields['esc3_monto']) || $cobro->fields['esc3_monto'] == 0 ? 'style="display: inline-block;"' : 'style="display: none;"' ?> >
												<?php echo Html::SelectQuery($sesion, "SELECT id_tarifa, glosa_tarifa FROM tarifa", "esc_id_tarifa_3", $cobro->fields['esc3_id_tarifa'], 'style="font-size:9pt; width:120px;"'); ?>
											</span>
											<span id="tipo_forma_3_2" <?php echo $cobro->fields['esc3_monto'] > 0 ? 'style="display: inline-block;"' : 'style="display: none;"'; ?> >
												<input type="text" size="8" style="font-size:9pt; width:116px;" id="esc_monto_3" name="esc_monto[]" value="<?php echo ($cobro->fields['esc3_monto'] > 0) ? $cobro->fields['esc3_monto'] : '0'; ?>" />
											</span>
										</span>
										
										<span><?php echo __('en'); ?></span>
										<?php echo Html::SelectQuery($sesion, "SELECT id_moneda, glosa_moneda FROM prm_moneda ORDER BY id_moneda", 'esc_id_moneda_3', $cobro->fields['esc3_id_moneda'], 'style="font-size:9pt; width:70px;"'); ?>
										<span><?php echo __('con'); ?> </span>
										<input type="text" name="esc_descuento[]" id="esc_descuento_3" value="<?php if (!empty($cobro->fields['esc3_descuento'])) echo $cobro->fields['esc3_descuento']; else echo '0'; ?>" size="4" />
										<span><?php echo __('% dcto.'); ?> </span>
									</td>
								</tr>
							</table>

						</div>
						<div class="template_escalon" id="escalon_4">
							<table style='padding: 5px; border: 0px solid' bgcolor='#F8FBBD'>
								<tr>
									<td valign="bottom">
										<div style="display:inline-block; width: 170px;"><?php echo __('Para el resto de horas trabajadas'); ?> </div>
										<?php echo __('aplicar'); ?>
										<input type="hidden" name="esc_tiempo[]" id="esc_tiempo_4" value="-1" size="4" onkeyup="ActualizaRango(this.id , this.value);" />
										
										<select name="esc_selector[]" id="esc_selector_4" onchange="cambia_tipo_forma(this.value, this.id);">
											<option value="1" <?php echo!isset($cobro->fields['esc4_monto']) || $cobro->fields['esc1_monto'] == 0 ? 'selected="selected"' : ''; ?>>tarifa</option>
											<option value="2" <?php echo $cobro->fields['esc4_monto'] > 0 ? 'selected="selected"' : ''; ?> >monto</option>
										</select>
										
										<span>
											<span id="tipo_forma_4_1" <?php echo!isset($cobro->fields['esc4_monto']) || $cobro->fields['esc4_monto'] == 0 ? 'style="display: inline-block;"' : 'style="display: none;"' ?> >
												<?php echo Html::SelectQuery($sesion, "SELECT id_tarifa, glosa_tarifa FROM tarifa", "esc_id_tarifa_4", $cobro->fields['esc4_id_tarifa'], 'style="font-size:9pt; width:120px;"'); ?>
											</span>
											<span id="tipo_forma_4_2" <?php echo $cobro->fields['esc4_monto'] > 0 ? 'style="display: inline-block;"' : 'style="display: none;"'; ?> >
												<input type="text" size="8" style="font-size:9pt; width:116px;" id="esc_monto_4" value="<?php echo $cobro->fields['esc4_monto'] > 0 ? $cobro->fields['esc4_monto'] : '0'; ?>" name="esc_monto[]" />
											</span>
										</span>

										<span><?php echo __('en'); ?></span>
										<?php echo Html::SelectQuery($sesion, "SELECT id_moneda, glosa_moneda FROM prm_moneda ORDER BY id_moneda", 'esc_id_moneda_4', $cobro->fields['esc4_id_moneda'], 'style="font-size:9pt; width:70px;"'); ?>
										<span><?php echo __('con'); ?> </span>
										<input type="text" name="esc_descuento[]" id="esc_descuento_4" value="<?php if (!empty($cobro->fields['esc4_descuento'])) echo $cobro->fields['esc4_descuento']; else echo ''; ?>" size="4" />
										<span><?php echo __('% dcto.'); ?> </span>

									</td>
								</tr>
							</table>
						</div>
					</div>
				</td>
			</tr>

			<tr>
				<td align="center" colspan="2"><a href="#" onclick="ActualizarTarifas();" title="Actualizar las tarifas de todos los trabajos de este cobro">Actualizar tarifas</a></td>
			</tr>

		</table>
	</fieldset>
	<!-- fin Modalidad -->

	<table width=100% cellspacing="3" cellpadding="3">
		<tr>
			<td valign="middle" align="left" style="background-color: #A3D55C; color: #000000; font-size: 14px; font-weight: bold;">
				<?php echo __('Resumen final del Cobro') ?>
			</td>
		</tr>
	</table>

	<?php if ($cobro->fields['forma_cobro'] == 'TASA') {
		if ($cobro->fields['monto_ajustado'] > 0) {
			$display_ajustar = 'style="display: table-row;"';
			$deshabilitar = '';
			$display_buton_cancelar = 'style="display: inline;"';
			$display_buton_ajuste = 'style="display: none;"';
		} else {
			$display_ajustar = 'style="display: none;"';
			$deshabilitar = 'readonly="readonly"';
			$display_buton_cancelar = 'style="display: none;"';
			$display_buton_ajuste = 'style="display: inline;"';
		}
	} else {
		$display_ajustar = 'style="display: none;"';
		$deshabilitar = 'readonly="readonly"';
		$display_buton_cancelar = 'style="display: none;"';
		$display_buton_ajuste = 'style="display: none;"';
	} ?>

	<table width="100%" cellspacing="3" cellpadding="3">
		<tr>
			<td align="left">
				
				<table cellspacing="1" cellpadding="2" style='border:1px dotted #bfbfcf'>
					<tr>
						<td colspan="2" bgcolor="#dfdfdf">
							<span style="font-weight: bold; font-size: 11px;"><?php echo __('Honorarios') ?></span>
						</td>
					</tr>
					<tr>
						<td align="right" width="45%" nowrap>
							<?php echo __('Trabajos') ?> (<span id="divCobroUnidadHonorarios" style='font-size:10px'><?php echo $moneda_cobro->fields['simbolo'] ?></span>):
						</td>
						<td align="left" width="55%" nowrap>
							<input type="text" name="cobro_monto_honorarios" id="cobro_monto_honorarios" onkeydown="MontoValido( this.id );" value="<?php echo number_format($cobro->fields['monto_subtotal'] - $cobro->CalculaMontoTramites($cobro), $moneda_cobro->fields['cifras_decimales'], '.', '') ?>" size="12" <?php echo $deshabilitar ?> style="text-align: right;" onkeydown="MontoValido( this.id );">
							&nbsp;&nbsp;<img src="<?php echo Conf::ImgDir() ?>/reload_16.png" onclick='GuardaCobro(this.form)' style='cursor:pointer' <?php echo TTip($tip_actualizar) ?>>
							<img id="ajustar_monto" <?php echo $display_buton_ajuste ?> src="<?php echo Conf::ImgDir() . '/editar_on.gif' ?>" title="<?php echo __('Ajustar Monto') ?>" border=0 style="cursor:pointer" onclick="AjustarMonto('ajustar');">
							<img id="cancelar_ajustacion" <?php echo $display_buton_cancelar ?> src="<?php echo Conf::ImgDir() . '/cruz_roja_nuevo.gif' ?>" title="<?php echo __('Usar Monto Original') ?>" border=0 style='cursor:pointer' onclick="AjustarMonto('cancelar')">
						</td>
					</tr>
					<tr id="tr_monto_original" <?php echo $display_ajustar ?>>
						<td>
							<?php echo __('Monto Original') ?> (<span id="divCobroUnidadHonorarios" style='font-size:10px'><?php echo $moneda_cobro->fields['simbolo'] ?></span>):
						</td>
						<td align="left">
							<input type="text" id="monto_original" name="monto_original" value="<?php echo number_format($cobro->fields['monto_original'], $moneda_cobro->fields['cifras_decimales'], '.', '') ?>" size="12" disabled style="text-align: right;">
						</td>
					</tr>
					<tr>
						<td align="right" width="45%" nowrap>
							<?php echo __('Trámites') ?> (<span id="divCobroUnidadTramites" style='font-size:10px'><?php echo $moneda_cobro->fields['simbolo'] ?></span>):
						</td>
						<td align="left" width="55%" nowrap>
							<input type="text" id="cobro_monto_tramites" value="<?php echo number_format($cobro->CalculaMontoTramites($cobro), $moneda_cobro->fields['cifras_decimales'], '.', '') ?>" size="12" readonly="readonly" style="text-align: right;">
						</td>
					</tr>
					<tr>
						<td align="right" width="45%" nowrap>
							<?php echo __('Subtotal') ?> (<span id="divCobroUnidadSubtotal" style='font-size:10px'><?php echo $moneda_cobro->fields['simbolo'] ?></span>):
						</td>
						<td align="left" width="55%" nowrap>
							<input type="text" id="cobro_subtotal" value="<?php echo number_format($cobro->fields['monto_subtotal'], $moneda_cobro->fields['cifras_decimales'], '.', '') ?>" size="12" readonly="readonly" style="text-align: right;" <?php echo TTip($tip_subtotal) ?>>
						</td>
					</tr>
					<tr bgcolor='#F3F3F3'>
						<td align="right" nowrap>
							<?php echo __('Descuento') ?> (<span id="divCobroUnidadDescuento" style='font-size:10px'><?php echo $moneda_cobro->fields['simbolo'] ?></span>):
						</td>
						<td align="left" nowrap>
						
							<?php if ($cobro->fields['tipo_descuento'] == '') {
								$chk = 'VALOR';
							} else {
								$chk = $cobro->fields['tipo_descuento'];
							} ?>

							<input type="text" name="cobro_descuento" style="text-align: right;" id="cobro_descuento" onkeydown="MontoValido( this.id );" size=12 value=<?php echo number_format($cobro->fields['descuento'], $moneda_cobro->fields['cifras_decimales'], '.', '') ?> onchange="RecalcularTotal(this.value);" <?php echo TTip($tip_descuento) ?>>
							<input type="radio" name="tipo_descuento" id="tipo_descuento" value='VALOR' <?php echo $chk == 'VALOR' ? 'checked' : '' ?> ><?php echo __('Valor') ?>
						</td>
					</tr>
					<tr bgcolor='#F3F3F3'>
						<td align="right">&nbsp;</td>
						<td align="left">
							<input type="text" name="porcentaje_descuento" style="text-align: right;" id="porcentaje_descuento" onkeydown="MontoValido( this.id );" size=12 value=<?php echo number_format((!empty($cobro->fields['porcentaje_descuento']) ? $cobro->fields['porcentaje_descuento'] : '0'), $moneda_cobro->fields['cifras_decimales'], '.', '') ?>>
							<input type="radio" name="tipo_descuento" id="tipo_descuento" value='PORCENTAJE' <?php echo $chk == 'PORCENTAJE' ? 'checked' : '' ?>><?php echo __('%') ?>
						</td>
					</tr>
							<?php
							if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'UsarImpuestoSeparado') ) || ( method_exists('Conf', 'UsarImpuestoSeparado') ) ) && $contrato->fields['usa_impuesto_separado']) {
								?>
						<tr>
							<td align="right"><?php echo __('Impuesto') ?> (<span id="divCobroImpuestoUnidad" style='font-size:10px'><?php echo $cobro->fields['porcentaje_impuesto'] . '%' ?></span>):</td>
							<td align="left"><input type="text" id="cobro_impuesto" value="<?php echo number_format(($cobro->fields['monto_subtotal'] - $cobro->fields['descuento']) * $cobro->fields['porcentaje_impuesto'] / 100, $moneda_cobro->fields['cifras_decimales'], '.', '') ?>" size="12" readonly="readonly" style="text-align: right;" ></td>
						</tr>
								<?php
							}
							?>
					<tr>
						<td align="right"><?php echo __('Total') ?> (<span id="divCobroUnidadTotal" style='font-size:10px'><?php echo $moneda_cobro->fields['simbolo'] ?></span>):</td>
						<td align="left"><input type="text" id="cobro_total" value="<?php echo number_format(round($cobro->fields['monto'], 2), $moneda_cobro->fields['cifras_decimales'], '.', '') ?>" size="12" readonly="readonly" style="text-align: right;" <?php echo TTip($tip_total) ?>></td>
					</tr>
				</table>
			</td>

			<td align="center">

				<?php
				$moneda_total = new Moneda($sesion);
				$moneda_total->Load($cobro->fields['opc_moneda_total']);
				$cobro_moneda_tipo_cambio = new CobroMoneda($sesion);
				$cobro_moneda_tipo_cambio->Load($id_cobro);
				$tipo_cambio_moneda_cobro = $cobro_moneda_tipo_cambio->moneda[$cobro->fields['id_moneda']]['tipo_cambio'];
				$tipo_cambio_moneda_total = $cobro_moneda_tipo_cambio->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio'];
				$cifras_decimales_moneda_cobro = $cobro_moneda_tipo_cambio->moneda[$cobro->fields['id_moneda']]['cifras_decimales'];
				$cifras_decimales_moneda_total = $cobro_moneda_tipo_cambio->moneda[$cobro->fields['opc_moneda_total']]['cifras_decimales'];
				?>

				<?php if (Conf::GetConf($sesion, 'UsarImpuestoPorGastos') && !empty($cobro->fields['incluye_gastos'])) { ?>
					<table cellspacing="1" cellpadding="2" style='border:1px dotted #bfbfcf'>
						<tr>
							<td colspan="2" bgcolor="#dfdfdf">
								<span style="font-weight: bold; font-size: 11px;"><?php echo __('Gastos') ?></span>
							</td>
						</tr>
						<tr>
							<td align="right" width="45%" nowrap>
						<?php echo __('Subtotal Gastos c/IVA') ?> (<span id="divCobroUnidadGastos" style='font-size:10px'><?php echo $moneda_cobro->fields['simbolo'] ?></span>):
							</td>
							<td align="left" width="55%" nowrap>
								<input type="text" id="subtotal_gastos_con" value="<?php echo $x_resultados['gastos']['subtotal_gastos_con_impuestos'][$moneda_cobro->fields['id_moneda']] ?>" size="12" readonly="readonly" style="text-align: right;" />
							</td>
						</tr>
						<tr>
							<td align="right" width="45%" nowrap>
						<?php echo __('Subtotal Gastos s/IVA') ?> (<span id="divCobroUnidadGastos" style='font-size:10px'><?php echo $moneda_cobro->fields['simbolo'] ?></span>):
							</td>
							<td align="left" width="55%" nowrap>
								<input type="text" id="subtotal_gastos_sin" value="<?php echo $x_resultados['gastos']['subtotal_gastos_sin_impuestos'][$moneda_cobro->fields['id_moneda']] ?>" size="12" readonly="readonly" style="text-align: right;" />
							</td>
						</tr>
						<tr>
							<td align="right" width="45%" nowrap>
					<?php echo __('Impuestos Gastos') ?> (<span id="divCobroUnidadGastos" style='font-size:10px'><?php echo $moneda_cobro->fields['simbolo'] ?></span>):
							</td>
							<td align="left" width="55%" nowrap>
								<input type="text" id="impuestos_gastos" value="<?php echo $x_resultados['gastos']['gasto_impuesto'][$moneda_cobro->fields['id_moneda']] ?>" size="12" readonly="readonly" style="text-align: right;" />
							</td>
						</tr>
						<tr>
							<td align="right" width="45%" nowrap>
					<?php echo __('Total Gastos') ?> (<span id="divCobroUnidadGastos" style='font-size:10px'><?php echo $moneda_cobro->fields['simbolo'] ?></span>):
							</td>
							<td align="left" width="55%" nowrap>
								<input type="text" id="total_gastos" value="<?php echo $x_resultados['gastos']['gasto_total_con_impuesto'][$moneda_cobro->fields['id_moneda']] ?>" size="12" readonly="readonly" style="text-align: right;" />
							</td>
						</tr>
					</table>
					<br />

				<?php } ?>

				<!--Agregar un resumen en moneda total para mejor indicacion de esta,
						Versiones anteriores quedan comentado por sia caso que volvemos a estas mas tarde-->
				<table cellspacing="0" cellpadding="3" style='border:1px dotted #bfbfcf'>
					<tr>
						<td colspan="2" bgcolor="#dfdfdf">
							<span style="font-weight: bold; font-size: 11px;"><?php echo __('Resumen total') ?></span>
						</td>
					</tr>
					<tr>
						<td align="right" width="45%" nowrap>
							<span style='font-size:10px;float:left'><?php echo __('Total Honorarios ') . (Conf::GetConf($sesion, 'UsarImpuestoSeparado') ? '<br/>(' . __('con impuestos') . ')' : '') ?></span> (<span id="divCobroUnidadHonorariosTotal" style='font-size:10px'><?php echo $moneda_total->fields['simbolo'] ?></span>):
						</td>
						<td align="left" width="55%" nowrap>
							<input type="text" id="total_honorarios" value="<?php echo $x_resultados['monto'][$cobro->fields['opc_moneda_total']] ?>" size="12" readonly="readonly" style="text-align: right;">
						</td>
					</tr>
					<tr>
						<td align="right" width="45%" nowrap>
							<span style='font-size:10px;float:left'><?php echo __('Total Gastos ') . (Conf::GetConf($sesion, 'UsarImpuestoPorGastos') ? '<br/>(' . __('con impuestos') . ')' : '') ?></span> (<span id="divCobroUnidadGastosTotal" style='font-size:10px'><?php echo $moneda_total->fields['simbolo'] ?></span>):
						</td>
						<td align="left" width="55%" nowrap>
							<input type="text" id="total_gastos" value="<?php echo $x_resultados['monto_gastos'][$cobro->fields['opc_moneda_total']] ?>" size="12" readonly="readonly" style="text-align: right;">
						</td>
					</tr>
					<tr>
						<td align="right" width="45%" nowrap>
							<span style='font-size:10px'><?php echo __('Total') ?></span> (<span id="divCobroUnidadGastosTotal" style='font-size:10px'><?php echo $moneda_total->fields['simbolo'] ?></span>):
						</td>
						<td align="left" width="55%" nowrap>
							<input type="text" id="total" value="<?php echo number_format($x_resultados['monto_gastos'][$cobro->fields['opc_moneda_total']] + $x_resultados['monto'][$cobro->fields['opc_moneda_total']], $moneda_total->fields['cifras_decimales'], '.', '') ?>" size="12" readonly="readonly" style="text-align: right;">
						</td>
					</tr>
				</table>

				<br>
				
				<table cellspacing="0" cellpadding="3" style='border:1px dotted #bfbfcf'>
					<tr>
						<td bgcolor="#dfdfdf">
							<span style="font-weight: bold; font-size: 11px;"><?php echo __('Se esta cobrando:') ?></span>
						</td>
					</tr>
					<tr>
						<td>
							<?php
							$se_esta_cobrando = $cobro->GlosaSeEstaCobrando();

							if (Conf::GetConf($sesion, 'SeEstaCobrandoEspecial')) {
								$disabled = "disabled";
								$lineas = 'rows="6"';
								$columnas = 'cols="25"';
							} else {
								$disabled = "";
								$lineas = 'rows="3"';
								$columnas = '';

								if (trim($cobro->fields['se_esta_cobrando']) != '') {
									$se_esta_cobrando = $cobro->fields['se_esta_cobrando'];
								}
							} ?>
							<textarea name="se_esta_cobrando" <?php echo "$lineas $columnas $disabled"; ?> id="se_esta_cobrando"><?php echo $se_esta_cobrando; ?></textarea>
						</td>
					</tr>
				</table>
			</td>

			<td align="center">
				<!-- OPCIONES IMPRESION -->
				<table width="270" border="0" cellspacing="0" cellpadding="3" style="border: 1px dotted #bfbfcf;" align=right>
					<tr>
						<td align="left" bgcolor="#dfdfdf" style="font-size: 11px; font-weight: bold; vertical-align: middle;">
							<img src="<?php echo Conf::ImgDir() ?>/imprimir_16.gif" border="0" alt="Imprimir"/> <?php echo __('Versi&oacute;n para imprimir') ?>
						</td>
						<td align="right" bgcolor="#dfdfdf" style="vertical-align: middle;">
							<a href="javascript:void(0);" style="color: #990000; font-size: 9px; font-weight: normal;" onclick="ToggleDiv('doc_opciones');"><?php echo __('opciones') ?></a>
						</td>
					</tr>
					<tr>
						<td align="center" colspan="2">
							<div id="doc_opciones" style="display: none; position: relative;">
								<table border="0" cellspacing="0" cellpadding="2" style="font-size: 10px;">
									<tr>
										<td colspan="3">&nbsp;</td>
									</tr>
									<tr>
										<td align="right"><input type="checkbox" name="opc_ver_asuntos_separados" id="opc_ver_asuntos_separados" value="1" <?php echo $cobro->fields['opc_ver_asuntos_separados'] == '1' ? 'checked' : '' ?>></td>
										<td align="left" colspan="2" style="font-size: 10px;"><label for="opc_ver_asuntos_separados"><?php echo __('Ver asuntos por separado') ?></label></td>
									</tr>
									<tr>
										<td align="right"><input type="checkbox" name="opc_ver_resumen_cobro" id="opc_ver_resumen_cobro" value="1" <?php echo $cobro->fields['opc_ver_resumen_cobro'] == '1' ? 'checked' : '' ?>></td>
										<td align="left" colspan="2" style="font-size: 10px;"><label for="opc_ver_resumen_cobro"><?php echo __('Mostrar resumen del cobro') ?></label></td>
									</tr>
									<tr>
										<td align="right"><input type="checkbox" name="opc_ver_modalidad" id="opc_ver_modalidad" value="1" <?php echo $cobro->fields['opc_ver_modalidad'] == '1' ? 'checked' : '' ?>></td>
										<td align="left" colspan="2" style="font-size: 10px;"><label for="opc_ver_modalidad"><?php echo __('Mostrar modalidad del cobro') ?></label></td>
									</tr>
										
									<?php if ($cobro->fields['opc_ver_profesional']) {
										$display_detalle_profesional = "style='display: table-row;'";
									} else {
										$display_detalle_profesional = "style='display: none;'";
									}

									if ($cobro->fields['opc_ver_detalles_por_hora']) {
										$display_detalle_por_hora = "style='display: table-row;'";
									} else {
										$display_detalle_por_hora = "style='display: none;'";
									} ?>
									
									<tr>
										<td align="right"><input type="checkbox" name="opc_ver_profesional" id="opc_ver_profesional" value="1" <?php echo $cobro->fields['opc_ver_profesional'] == '1' ? 'checked' : '' ?> onchange="showOpcionDetalle( this.id, 'tr_detalle_profesional');"></td>
										<td align="left" colspan="2" style="font-size: 10px;"><label for="opc_ver_profesional"><?php echo __('Mostrar detalle por profesional') ?></label></td>
									</tr>

									<tr id="tr_detalle_profesional" <?php echo $display_detalle_profesional ?> >
									
										<td align="left" colspan="2" style="font-size: 10px;">
											<table width="100%">
												<tr>
													<td width="40%" align="left">
														<input type="checkbox" name="opc_ver_profesional_iniciales" id="opc_ver_profesional_iniciales" value="1" <?php echo $cobro->fields['opc_ver_profesional_iniciales'] == '1' ? 'checked' : '' ?>>
														<label for="opc_ver_profesional_iniciales"><?php echo __('Iniciales') ?></label>
													</td>
													<td width="60%" align="left">
														<input type="checkbox" name="opc_ver_profesional_categoria" id="opc_ver_profesional_categoria" value="1" <?php echo $cobro->fields['opc_ver_profesional_categoria'] == '1' ? 'checked' : '' ?>>
														<label for="opc_ver_profesional_categoria"><?php echo __('Categoría') ?></label>
													</td>
												</tr>
												<tr>
													<td width="40%" align="left">
														<input type="checkbox" name="opc_ver_profesional_tarifa" id="opc_ver_profesional_tarifa" value="1" <?php echo $cobro->fields['opc_ver_profesional_tarifa'] == '1' ? 'checked' : '' ?>>
														<label for="opc_ver_profesional_tarifa"><?php echo __('Tarifa') ?></label>
													</td>
													<td width="60%" align="left">
														<input type="checkbox" name="opc_ver_profesional_importe" id="opc_ver_profesional_importe" value="1" <?php echo $cobro->fields['opc_ver_profesional_importe'] == '1' ? 'checked' : '' ?>>
														<label for="opc_ver_profesional_importe"><?php echo __('Importe') ?></label>
													</td>
												</tr>
											</table>
										</td>
									</tr>
									<tr>
										<td align="right">
											<input type="checkbox" name="opc_ver_detalles_por_hora" id="opc_ver_detalles_por_hora" value="1" <?php echo $cobro->fields['opc_ver_detalles_por_hora'] == '1' ? 'checked' : '' ?> onchange="showOpcionDetalle( this.id, 'tr_detalle_por_hora');">
										</td>
										<td align="left" colspan="2" style="font-size: 10px;">
											<label for="opc_ver_detalles_por_hora"><?php echo __('Mostrar detalle por hora') ?></label>
										</td>
									</tr>
									<tr id="tr_detalle_por_hora" <?php echo $display_detalle_por_hora ?> >
										<td/>
										<td align="left" colspan="2" style="font-size: 10px;">
											<table width="100%">
												<tr>
													<td width="40%" align="left">
														<input type="checkbox" name="opc_ver_detalles_por_hora_iniciales" id="opc_ver_detalles_por_hora_iniciales" value="1" <?php echo $cobro->fields['opc_ver_detalles_por_hora_iniciales'] == '1' ? 'checked' : '' ?>>
														<label for="opc_ver_detalles_por_hora_iniciales"><?php echo __('Iniciales') ?></label>
													</td>
													<td width="60%" align="left">
														<input type="checkbox" name="opc_ver_detalles_por_hora_categoria" id="opc_ver_detalles_por_hora_categoria" value="1" <?php echo $cobro->fields['opc_ver_detalles_por_hora_categoria'] == '1' ? 'checked' : '' ?>>
														<label for="opc_ver_detalles_por_hora_categoria"><?php echo __('Categoría') ?></label>
													</td>
												</tr>
												<tr>
													<td width="40%" align="left">
														<input type="checkbox" name="opc_ver_detalles_por_hora_tarifa" id="opc_ver_detalles_por_hora_tarifa" value="1" <?php echo $cobro->fields['opc_ver_detalles_por_hora_tarifa'] == '1' ? 'checked' : '' ?>>
														<label for="opc_ver_detalles_por_hora_tarifa"><?php echo __('Tarifa') ?></label>
													</td>
													<td width="60%" align="left">
														<input type="checkbox" name="opc_ver_detalles_por_hora_importe" id="opc_ver_detalles_por_hora_importe" value="1" <?php echo $cobro->fields['opc_ver_detalles_por_hora_importe'] == '1' ? 'checked' : '' ?>>
														<label for="opc_ver_detalles_por_hora_importe"><?php echo __('Importe') ?></label>
													</td>
												</tr>
											</table>
										</td>
									</tr>
                                    <tr>
                                        <td align="right"><input type="checkbox" name="opc_ver_gastos" id="opc_ver_gastos" value="1" <?php echo $cobro->fields['opc_ver_gastos'] == '1' ? 'checked' : '' ?>></td>
                                        <td align="left" style="font-size: 10px;"><label for="opc_ver_gastos"><?php echo __('Mostrar gastos del cobro') ?></label></td>
                                    </tr>
									
									<?php if (Conf::GetConf($sesion, 'PrmGastos')) { ?>
										<tr>
											<td align="right"><input type="checkbox" name="opc_ver_concepto_gastos" id="opc_ver_concepto_gastos" value="1" <?php echo $cobro->fields['opc_ver_concepto_gastos'] == '1' ? 'checked' : '' ?>></td>
											<td align="left" style="font-size: 10px;"><label for="opc_ver_concepto_gastos"><?php echo __('Mostrar concepto de gastos') ?></label></td>
										</tr>
									<?php } ?>

                                    <tr>
                                        <td align="right"><input type="checkbox" name="opc_ver_morosidad" id="opc_ver_morosidad" value="1" <?php echo $cobro->fields['opc_ver_morosidad'] == '1' ? 'checked' : '' ?>></td>
                                        <td align="left" style="font-size: 10px;"><label for="opc_ver_morosidad"><?php echo __('Mostrar saldo adeudado') ?></label></td>
                                    </tr>
                                    <tr>
                                        <td align="right"><input type="checkbox" name="opc_ver_tipo_cambio" id="opc_ver_tipo_cambio" value="1" <?php echo $cobro->fields['opc_ver_tipo_cambio'] == '1' ? 'checked' : '' ?>></td>
                                        <td align="left" style="font-size: 10px;"><label for="opc_ver_tipo_cambio"><?php echo __('Mostrar tipos de cambio') ?></label></td>
                                    </tr>
                                    <tr>
                                        <td align="right"><input type="checkbox" name="opc_ver_descuento" id="opc_ver_descuento" value="1" <?php echo $cobro->fields['opc_ver_descuento'] == '1' ? 'checked' : '' ?>></td>
                                        <td align="left" style="font-size: 10px;"><label for="opc_ver_descuento"><?php echo __('Mostrar el descuento del cobro') ?></label></td>
                                    </tr>
                                    <tr>
                                        <td align="right"><input type="checkbox" name="opc_ver_numpag" id="opc_ver_numpag" value="1" <?php echo $cobro->fields['opc_ver_numpag'] == '1' ? 'checked' : '' ?>></td>
                                        <td align="left" style="font-size: 10px;"><label for="opc_ver_numpag"><?php echo __('Mostrar números de página') ?></label></td>
                                    </tr>
                                    <tr>
                                        <td align="right"><input type="checkbox" name="opc_ver_columna_cobrable" id="opc_ver_columna_cobrable" value="1" <?php echo $cobro->fields['opc_ver_columna_cobrable'] == '1' ? 'checked' : '' ?>></td>
                                        <td align="left" style="font-size: 10px;"><label for="opc_ver_columna_cobrable"><?php echo __('Mostrar columna cobrable') ?></label></td>
                                    </tr>
									
									<?php
									$solicitante = Conf::GetConf($sesion, 'OrdenadoPor');

									if ($solicitante == 0) {  // no mostrar
									?>
										<input type="hidden" name="opc_ver_solicitante" id="opc_ver_solicitante" value="0" />
									<?php
									} elseif ($solicitante == 1) { // obligatorio
									?>
										<tr>
											<td align="right"><input type="checkbox" name="opc_ver_solicitante" id="opc_ver_solicitante" value="1" <?php echo $cobro->fields['opc_ver_solicitante'] == '1' ? 'checked="checked"' : '' ?>></td>
											<td align="left" colspan="2" style="font-size: 10px;"><label for="opc_ver_solicitante"><?php echo __('Mostrar solicitante') ?></label></td>
										</tr>
										<tr>
									<?php
									} elseif ($solicitante == 2) { // opcional
									?>
										<tr>
											<td align="right"><input type="checkbox" name="opc_ver_solicitante" id="opc_ver_solicitante" value="1" <?php echo $cobro->fields['opc_ver_solicitante'] == '1' ? 'checked="checked"' : '' ?>></td>
											<td align="left" colspan="2" style="font-size: 10px;"><label for="opc_ver_solicitante"><?php echo __('Mostrar solicitante') ?></label></td>
										</tr>
										<tr>
									<?php } ?>

										<td align="right"><input type="checkbox" name="opc_ver_horas_trabajadas" id="opc_ver_horas_trabajadas" value="1" <?php echo $cobro->fields['opc_ver_horas_trabajadas'] == '1' ? 'checked' : '' ?>></td>
										<td align="left" colspan="2" style="font-size: 10px;"><label for="opc_ver_horas_trabajadas"><?php echo __('Mostrar horas trabajadas') ?></label></td>
									</tr>
									<tr>
										<td align="right"><input type="checkbox" name="opc_ver_cobrable" id="opc_ver_cobrable" value="1" <?php echo $cobro->fields['opc_ver_cobrable'] == '1' ? 'checked' : '' ?>></td>
										<td align="left" colspan="2" style="font-size: 10px;"><label for="opc_ver_cobrable"><?php echo __('Mostrar trabajos no visibles') ?></label></td>
									</tr>

									<?php if ( Conf::GetConf($sesion, 'ResumenProfesionalVial') ) { ?>
										<tr>
											<td align="right"><input type="checkbox" name="opc_restar_retainer" id="opc_restar_retainer" value="1" <?php echo $cobro->fields['opc_restar_retainer'] == '1' ? 'checked="checked"' : '' ?> ></td>
											<td align="left" colspan="2" style="font-size: 10px;"><label for="opc_restar_retainer"><?php echo __('Restar valor retainer') ?></td>
										</tr>
										<tr>
											<td align="right"><input type="checkbox" name="opc_ver_detalle_retainer" id="opc_ver_detalle_retainer" value="1" <?php echo $cobro->fields['opc_ver_detalle_retainer']=='1'?'checked="checked"':''?> ></td>
											<td align="left" colspan="2" style="font-size: 10px;"><label for="opc_ver_detalle_retainer"><?php echo __('Mostrar detalle retainer')?></td>
										</tr>
									<?php } ?>

									<tr>
										<td align="right"><input type="checkbox" name="opc_ver_valor_hh_flat_fee" id="opc_ver_valor_hh_flat_fee" value="1" <?php echo $cobro->fields['opc_ver_valor_hh_flat_fee']=='1'?'checked':''?>></td>
										<td align="left" colspan="2" style="font-size: 10px;"><label for="opc_ver_valor_hh_flat_fee"><?php echo __('Mostrar tarifa proporcional en base a HH')?></label></td>
									</tr>
									<tr>
										<td align="right"><input type="checkbox" name="opc_ver_carta" id="opc_ver_carta" value="1" onclick="ActivaCarta(this.checked)" <?php echo $cobro->fields['opc_ver_carta']=='1'?'checked':''?>></td>
										<td align="left" colspan="2" style="font-size: 10px;"><label for="opc_ver_carta"><?php echo __('Mostrar Carta')?></label></td>
									</tr>
									<tr>
										<td style="font-size: 10px;" colspan="3">
											<?php echo __('Formato de carta')?>:
										</td>
									</tr>
									
									<tr>
										<td align="left" colspan="3">
										<?php 
										$query_formato_carta = "SELECT carta.id_carta, carta.descripcion FROM carta ORDER BY id_carta";
										echo  Html::SelectQuery($sesion, $query_formato_carta, "id_carta", $cobro->fields['id_carta'] ? $cobro->fields['id_carta'] : $contrato->fields['id_carta'], ($cobro->fields['opc_ver_carta']=='1'?'':'disabled') . ' class="wide"','',200); ?>
										</td>
									</tr>
									<tr>
										<td style="font-size: 10px;"  colspan="3">
											<?php echo __('Formato Detalle Carta Cobro')?>:
										</td>
									</tr>
									<tr>
										<td align="left" colspan="3">
										<?php
										$query_formato_ncobro = "SELECT cobro_rtf.id_formato, cobro_rtf.descripcion	FROM cobro_rtf ORDER BY cobro_rtf.id_formato";
										echo  Html::SelectQuery($sesion, $query_formato_ncobro,"id_formato", $cobro->fields['id_formato'] ? $cobro->fields['id_formato'] : $contrato->fields['id_formato'], 'class="wide"','Seleccione',200); ?>
										</td>
									</tr>
									<tr>
										<td align="left" style="font-size: 10px;" colspan="3">
											<?php echo __('Tamaño del papel')?>:
										</td>
									</tr>
									<tr>
										<td align="left" colspan="3">
										<?php
										if ($cobro->fields['opc_papel'] == '' && Conf::GetConf($sesion, 'PapelPorDefecto')) {
											$cobro->fields['opc_papel'] = Conf::GetConf($sesion, 'PapelPorDefecto');
										}
										?>
										<select name="opc_papel">
											<option value="LETTER" <?php echo $cobro->fields['opc_papel'] == 'LETTER' ? 'selected="selected"' : '' ?>><?php echo __('Carta'); ?></option>
											<option value="LEGAL" <?php echo $cobro->fields['opc_papel'] == 'LEGAL' ? 'selected="selected"' : '' ?>><?php echo __('Oficio'); ?></option>
											<option value="A4" <?php echo $cobro->fields['opc_papel'] == 'A4' ? 'selected="selected"' : '' ?>><?php echo __('A4'); ?></option>
											<option value="A5" <?php echo $cobro->fields['opc_papel'] == 'A5' ? 'selected="selected"' : '' ?>><?php echo __('A5'); ?></option>
										</select>
									</td>
								</tr>
								<tr>
									<td colspan="2">&nbsp;</td>
								</tr>
							</table>
							</div>
						</td>
			    	</tr>
					
					<tr>
						<td align="center" colspan="2">
							<table width="180">
								<tr>
									<td width="50%" style="font-size: 10px;" nowrap>
										<?php echo __('Mostrar total en')?>:
									</td>
									<td width="50%">
										<?php echo Html::SelectQuery($sesion,"SELECT id_moneda, glosa_moneda FROM prm_moneda ORDER BY id_moneda", 'opc_moneda_total',$cobro->fields['opc_moneda_total'],'onchange="ActualizarSaldoAdelantos();"','','70');?>
									</td>
								</tr>
								<tr>
									<td style="font-size: 10px;" nowrap>
										<?php echo __('Idioma')?>:
									</td>
									<td>
										<?php echo Html::SelectQuery($sesion,"SELECT codigo_idioma,glosa_idioma FROM prm_idioma ORDER BY glosa_idioma","lang",$cobro->fields['codigo_idioma'] != '' ? $cobro->fields['codigo_idioma'] : $contrato->fields['codigo_idioma'] ,'','',70);?>
									</td>
								</tr>
								<tr>
									<td colspan="2" align="center">
										<a href="#" style="font-size: 10px;" onclick="SubirExcel();">Subir excel modificado</a>
									</td>
								</tr>
								<tr>
									<td colspan="2">&nbsp;</td>
								</tr>
								<tr>
									<td colspan="2" align="center">
										<br class="clearfix vpx" />	<a  class="btn botonizame"  icon="ui-icon-doc"  setwidth="185"  onclick="return ImprimirCobro(jQuery('#form_cobro5').get(0));" /><?php echo __('Descargar Archivo')?></a>
										<?php if( Conf::GetConf($sesion,'MostrarBotonCobroPDF') ) { ?>
											<br class="clearfix vpx" />	<a  class="btn botonizame"  icon="ui-icon-pdf"  setwidth="185"   onclick="return ImprimirCobroPDF(jQuery('#form_cobro5').get(0));" /><?php echo __('Descargar Archivo')?> PDF</a>
										<?php } ?>

										<?php if( !Conf::GetConf($sesion, 'EsconderExcelCobroModificable') ) { ?>
											<br class="clearfix vpx" />	<a  class="btn botonizame"  icon="ui-icon-xls"  setwidth="185"   onclick="ImprimirExcel(jQuery('#form_cobro5').get(0));" /><?php echo __('descargar_excel_modificable')?></a>
										<?php } ?>

										<?php if( Conf::GetConf($sesion, 'ExcelRentabilidadFlatFee') ) { ?>
											<br class="clearfix vpx" />	<a  class="btn botonizame"  icon="ui-icon-xls"  setwidth="185"  onclick="ImprimirExcel(jQuery('#form_cobro5').get(0), 'rentabilidad');" /><?php echo __('Excel rentabilidad')?></a>
										<?php } ?>

										<?php if( !Conf::GetConf($sesion,'EsconderDescargarLiquidacionEnBorrador')  && Conf::GetConf($sesion, 'XLSFormatoEspecial' ) != '' && Conf::GetConf($sesion, 'XLSFormatoEspecial' ) != 'cobros_xls.php' ) { ?>
										 	<br class="clearfix vpx" />	<a  class="btn botonizame"  icon="ui-icon-xls"  setwidth="185" onclick="ImprimirExcel(jQuery('#form_cobro5').get(0), 'especial');" /><?php echo __('Descargar Excel Cobro')?></a>
										<?php } ?>
								 	</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			<!-- FIN   OPCIONES IMPRESION -->
		</td>
	</tr>
</table>

<table width="100%">
	<tr>
		<td align='center'>
            <a style="margin:auto;display:block;" href="#" id="enviar" class="btn botonizame" icon="ui-icon-save"  setwidth="220" onclick="GuardaCobro(jQuery(this).closest('form').get(0)); "><?php echo __('Guardar Cambios') ?></a>
		</td>
	</tr>
	<tr>
		<td align='right'>
			<hr size='1px'>
		</td>
	</tr>
</table>

</form>

<br>

<iframe src="historial_cobro.php?id_cobro=<?php echo $id_cobro?>" width=600px height=450px style="border: none;" frameborder=0></iframe>

<script language="javascript" type="text/javascript">
	
	window.onunload = ActualizarPadre;
	var form = document.getElementById('form_cobro5');

	if( form ) {
		if( form.cobro_forma_cobro[0].checked ) {
			HideMonto();
		} else if( form.cobro_forma_cobro[1].checked ) {
			ShowMonto(true, true);
		} else if( form.cobro_forma_cobro[2].checked ) {
			ShowMonto(false, false);
		} else if( form.cobro_forma_cobro[3].checked ) {
			ShowMonto(false, false);
		} else if( form.cobro_forma_cobro[4].checked ) {
			ShowMonto(true, false);
		}
	}

	function ActivaCarta(check)
	{
		if( !form ){
			var form = $('form_cobro5');
		}

		if(check) {
			form.id_carta.disabled = false;
		} else {
			form.id_carta.disabled = true;
		}
	}

	<?php for ($i = 0; $i < $monedas->num; $i++) {
		$moneda = $monedas->Get($i);
		$cf = max($moneda->fields['cifras_decimales'], 7);
		if ($cf > 0) {
			$dec = ".";
			while ($cf-- > 0) {
				$dec .= "0";
			}
		}
	?>
	
	jQuery("#cobro_tipo_cambio_<?php echo $moneda->fields['id_moneda'] ?>").blur(function(){
		var str = jQuery(this).val();
	   	jQuery(this).val( str.replace(',','.') );
	   	jQuery(this).parseNumber({format:"#.0000000", locale:"us"});
	   	jQuery(this).formatNumber({format:"#.0000000", locale:"us"});
	});

<?php } ?>

</script>

<?php $pagina->PrintBottom($popup); ?>
