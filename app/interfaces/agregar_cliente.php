<?php
require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion(array('DAT'));
$pagina = new Pagina($sesion);
$id_usuario = $sesion->usuario->fields['id_usuario'];
$desde_agrega_cliente = true;

$cliente = new Cliente($sesion);
$contrato = new Contrato($sesion);
$archivo = new Archivo($sesion);
$codigo_obligatorio = true;
if ( UtilesApp::GetConf($sesion, 'CodigoObligatorio') )   {
	if (!Conf::CodigoObligatorio())
		$codigo_obligatorio = false;
	else
		$codigo_obligatorio = true;
}
if ($id_cliente > 0) {
	$cliente->Load($id_cliente);
	$contrato->Load($cliente->fields['id_contrato']);
	$cobro = new Cobro($sesion);
} else {
	$codigo_cliente = $cliente->AsignarCodigoCliente();
	$cliente->fields['codigo_cliente'] = $codigo_cliente;
}

$validaciones_segun_config = UtilesApp::GetConf($sesion, 'ValidacionesCliente');
$obligatorio = '<span class="req">*</span>';
$usuario_responsable_obligatorio = UtilesApp::GetConf($sesion, 'ObligatorioEncargadoComercial');
$usuario_secundario_obligatorio = UtilesApp::GetConf($sesion, 'ObligatorioEncargadoSecundarioCliente');
$CodigoSecundario=UtilesApp::GetConf($sesion, 'CodigoSecundario') ;
if ($opcion == "guardar") {
	#Validaciones
	$cli = new Cliente($sesion);
	$cli->LoadByCodigo($codigo_cliente);
	$val = false;
	if ($cli->Loaded()) {
		if (!$activo)
			$cli->InactivarAsuntos();
		if (($cli->fields['id_cliente'] != $cliente->fields['id_cliente']) and ($cliente->Loaded())) {
			$pagina->AddError(__('Existe cliente'));
			$val = true;
		}
		if (!$cliente->Loaded()) {
			$pagina->AddError(__('Existe cliente'));
			$val = true;
		}
		if ($codigo_cliente_secundario) {
			$query_codigos = "SELECT codigo_cliente_secundario FROM cliente WHERE id_cliente != '" . $cli->fields['id_cliente'] . "'";
			$resp_codigos = mysql_query($query_codigos, $sesion->dbh) or Utiles::errorSQL($query_codigos, __FILE__, __LINE__, $sesion->dbh);
			while (list($codigo_cliente_secundario_temp) = mysql_fetch_array($resp_codigos)) {
				if ($codigo_cliente_secundario == $codigo_cliente_secundario_temp) {
					$pagina->FatalError('El código ingresado ya existe');
					$val = true;
				}
			}
		}
		$loadasuntos = false;
	} else {
		$loadasuntos = true;
		if ($codigo_cliente_secundario) {
			$query_codigos = "SELECT codigo_cliente_secundario FROM cliente";
			$resp_codigos = mysql_query($query_codigos, $sesion->dbh) or Utiles::errorSQL($query_codigos, __FILE__, __LINE__, $sesion->dbh);
			while (list($codigo_cliente_secundario_temp) = mysql_fetch_array($resp_codigos)) {
				if ($codigo_cliente_secundario == $codigo_cliente_secundario_temp) {
					$pagina->FatalError('El código ingresado ya existe');
					$val = true;
				}
			}
		}
	}

	if(UtilesApp::GetConf($sesion, 'EncargadoSecundario')) {
            $id_usuario_secundario = (!empty($id_usuario_secundario) && $id_usuario_secundario != -1 ) ? $id_usuario_secundario : 0;
	}

	//Validaciones segun la configuración
	if ($validaciones_segun_config) {
		if (empty($glosa_cliente))
			$pagina->AddError(__("Por favor ingrese el nombre del cliente"));
		if (empty($codigo_cliente))
			$pagina->AddError(__("Por favor ingrese el codigo del cliente"));
		if (empty($factura_rut))
			$pagina->AddError(__("Por favor ingrese ROL/RUT de la factura"));
		if (empty($factura_razon_social))
			$pagina->AddError(__("Por favor ingrese la razón social de la factura"));
		if (empty($factura_giro))
			$pagina->AddError(__("Por favor ingrese el giro de la factura"));
		if (empty($factura_direccion))
			$pagina->AddError(__("Por favor ingrese la dirección de la factura"));

		if( UtilesApp::existecampo('factura_ciudad', 'contrato', $sesion)) {
			if (empty($factura_ciudad))
			$pagina->AddError(__("Por favor ingrese la ciudad de la factura"));
		}

		if( UtilesApp::existecampo('factura_comuna', 'contrato', $sesion)) {
			if (empty($factura_comuna))
			$pagina->AddError(__("Por favor ingrese la comuna de la factura"));
		}
		if (empty($factura_telefono))
			$pagina->AddError(__("Por favor ingrese el teléfono de la factura"));
		if ( UtilesApp::GetConf($sesion,'ClienteReferencia') && ( empty($id_cliente_referencia) || $id_cliente_referencia == '-1' ) )
			$pagina->AddError(__("Por favor ingrese la referencia"));

		if ((method_exists('Conf', 'GetConf') and Conf::GetConf($sesion, 'TituloContacto')) or
				(method_exists('Conf', 'TituloContacto') and Conf::TituloContacto())) {
			if (empty($titulo_contacto))
				$pagina->AddError(__("Por favor ingrese titulo del solicitante"));
			if (empty($nombre_contacto))
				$pagina->AddError(__("Por favor ingrese nombre del solicitante"));
			if (empty($apellido_contacto))
				$pagina->AddError(__("Por favor ingrese apellido del solicitante"));
		}
		else {
			if (empty($contacto))
				$pagina->AddError(__("Por favor ingrese contanto del solicitante"));
		}

		if (empty($fono_contacto_contrato))
			$pagina->AddError(__("Por favor ingrese el teléfono del solicitante"));
		if (empty($email_contacto_contrato))
			$pagina->AddError(__("Por favor ingrese el correo del solicitante"));
		if (empty($direccion_contacto_contrato))
			$pagina->AddError(__("Por favor ingrese la dirección del solicitante"));

		if (empty($id_tarifa))
			$pagina->AddError(__("Por favor ingrese la tarifa en la tarificación"));
		if (empty($id_moneda))
			$pagina->AddError(__("Por favor ingrese la moneda de la tarifa en la tarificación"));

		if (empty($forma_cobro)) {
			$pagina->AddError(__("Por favor ingrese la forma de ") . __("cobro") . __(" en la tarificación"));
		} else {
			switch ($forma_cobro) {
				case "RETAINER":
					if (empty($monto))
						$pagina->AddError(__("Por favor ingrese el monto para el retainer en la tarificación"));
					if ($retainer_horas <= 0)
						$pagina->AddError(__("Por favor ingrese las horas para el retainer en la tarificación"));
					if (empty($id_moneda_monto))
						$pagina->AddError(__("Por favor ingrese la moneda para el retainer en la tarificación"));
					break;
				case "FLAT FEE":
					if (empty($monto))
						$pagina->AddError(__("Por favor ingrese el monto para el flat fee en la tarificación"));
					if (empty($id_moneda_monto))
						$pagina->AddError(__("Por favor ingrese la moneda para el flat fee en la tarificación"));
					break;
				case "CAP":
					if (empty($monto))
						$pagina->AddError(__("Por favor ingrese el monto para el cap en la tarificación"));
					if (empty($id_moneda_monto))
						$pagina->AddError(__("Por favor ingrese la moneda para el cap en la tarificación"));
					if (empty($fecha_inicio_cap))
						$pagina->AddError(__("Por favor ingrese la fecha de inicio para el cap en la tarificación"));
					break;
				case "PROPORCIONAL":
					if (empty($monto))
						$pagina->AddError(__("Por favor ingrese el monto para el proporcional en la tarificación"));
					if ($retainer_horas <= 0)
						$pagina->AddError(__("Por favor ingrese las horas para el proporcional en la tarificación"));
					if (empty($id_moneda_monto))
						$pagina->AddError(__("Por favor ingrese la moneda para el proporcional en la tarificación"));
					break;
				case "ESCALONADA":
					if( empty($_POST['esc_tiempo'][0])){
						$pagina->AddError(__("Por favor ingrese el tiempo para la primera escala"));
					}
					break;
				case "TASA":
				case "HITOS":
					break;
				default:
					$pagina->AddError(__("Por favor ingrese la forma de") . __("cobro") . __("en la tarificación"));
			}
		}

		if (empty($opc_moneda_total))
			$pagina->AddError(__("Por favor ingrese la moneda a mostrar el total de la tarifa en la tarificación"));
		if (empty($observaciones))
			$pagina->AddError(__("Por favor ingrese la observacion en la tarificación"));
	}

        if ($usuario_responsable_obligatorio && (empty($id_usuario_responsable) or $id_usuario_responsable == '-1')) {
            $pagina->AddError(__("Debe ingresar el") . " " . __('Encargado Comercial'));
        }

        if ($usuario_secundario_obligatorio && UtilesApp::GetConf($sesion, 'EncargadoSecundario') && (empty($id_usuario_secundario) or $id_usuario_secundario == '-1')) {
            $pagina->AddError( __("Debe ingresar el") . " " . __('Encargado Secundario'));
        }

	$errores = $pagina->GetErrors();
	if (!empty($errores)) {
		$val = true;
		$loadasuntos = false;
	}

	if (!$val) {
		$cliente->Edit("glosa_cliente", $glosa_cliente);
		$cliente->Edit("codigo_cliente", $codigo_cliente);
		if ($codigo_cliente_secundario)
			$cliente->Edit("codigo_cliente_secundario", strtoupper($codigo_cliente_secundario));
		else
			$cliente->Edit("codigo_cliente_secundario", $codigo_cliente);
		#$cliente->Edit("rsocial",$rsocial);
		#$cliente->Edit("rut",$rut);
		#$cliente->Edit("dv",$dv);
		#$cliente->Edit("dir_calle",$dir_calle);
		/* $cliente->Edit("dir_numero",$dir_numero);
		  $cliente->Edit("dir_comuna",$dir_comuna ? $dir_comuna : "NULL"); */
		#$cliente->Edit("giro",$giro);
		$cliente->Edit("id_moneda", 1);
		#$cliente->Edit("monto",$monto);
		#$cliente->Edit("forma_cobro",$forma_cobro);
		#$cliente->Edit("nombre_contacto",$nombre_contacto);
		#$cliente->Edit("cod_fono_contacto",$cod_fono_contacto);
		#$cliente->Edit("fono_contacto",$fono_contacto);
		#$cliente->Edit("mail_contacto",$mail_contacto);
		if ($activo != 1 && $cliente->fields['activo'] == '1')
			$cliente->Edit("fecha_inactivo", date('Y-m-d H:i:s'));
		else if ($activo == 1 && $cliente->fields['activo'] != '1')
			$cliente->Edit("fecha_inactivo", 'NULL');
		$cliente->Edit("activo", $activo == 1 ? '1' : '0');
		$cliente->Edit("id_usuario_encargado", $id_usuario_encargado);
		$cliente->Edit("id_grupo_cliente", $id_grupo_cliente > 0 ? $id_grupo_cliente : 'NULL');
		$cliente->Edit("alerta_hh", $cliente_alerta_hh);
		$cliente->Edit("alerta_monto", $cliente_alerta_monto);
		$cliente->Edit("limite_hh", $cliente_limite_hh);
		$cliente->Edit("limite_monto", $cliente_limite_monto);
		$cliente->Edit("id_cliente_referencia", ( !empty($id_cliente_referencia) && $id_cliente_referencia != '-1' ) ? $id_cliente_referencia : "NULL" );

					if($cliente->Write())
					{

			#CONTRATO
			$contrato->Load($cliente->fields['id_contrato']);
			if ($forma_cobro != 'TASA' && $forma_cobro != 'HITOS' && $forma_cobro != 'ESCALONADA' && $monto == 0) {
				$pagina->AddError(__('Ud. ha seleccionado forma de cobro:') . ' ' . $forma_cobro . ' ' . __('y no ha ingresado monto'));
				$val = true;
			} elseif ($forma_cobro == 'TASA')
				$monto = '0';

			if ($tipo_tarifa == 'flat') {
				if (empty($tarifa_flat)) {
					$pagina->AddError(__('Ud. ha seleccionado una tarifa plana pero no ha ingresado el monto'));
					$val = true;
				} else {
					$tarifa = new Tarifa($sesion);
					$id_tarifa = $tarifa->GuardaTarifaFlat($tarifa_flat, $id_moneda, $id_tarifa_flat);
					$_REQUEST['id_tarifa'] = $id_tarifa;
				}
			}

			$contrato->Fill($_REQUEST, true);
			$contrato->Edit('codigo_cliente', $codigo_cliente);

			if ($contrato->Write()) {
				#cobros pendientes
				CobroPendiente::EliminarPorContrato($sesion, $contrato->fields['id_contrato']);
				for ($i = 2; $i <= sizeof($valor_fecha); $i++) {
					$cobro_pendiente = new CobroPendiente($sesion);
					$cobro_pendiente->Edit("id_contrato", $contrato->fields['id_contrato']);
					$cobro_pendiente->Edit("fecha_cobro", Utiles::fecha2sql($valor_fecha[$i]));
					$cobro_pendiente->Edit("descripcion", $valor_descripcion[$i]);
					$cobro_pendiente->Edit("monto_estimado", $valor_monto_estimado[$i]);
					$cobro_pendiente->Write();
				}
				$cliente->Edit("id_contrato", $contrato->fields['id_contrato']);

				foreach (array_keys($hito_fecha) as $i) {
					if (empty($hito_monto_estimado[$i]))
						continue;
					$cobro_pendiente = new CobroPendiente($sesion);
					$cobro_pendiente->Edit("id_contrato", $contrato->fields['id_contrato'] ? $contrato->fields['id_contrato'] : $id_contrato);
					$cobro_pendiente->Edit("fecha_cobro", empty($hito_fecha[$i]) ? 'NULL' : Utiles::fecha2sql($hito_fecha[$i]));
					$cobro_pendiente->Edit("descripcion", $hito_descripcion[$i]);
					$cobro_pendiente->Edit("observaciones", $hito_observaciones[$i]);
					$cobro_pendiente->Edit("monto_estimado", $hito_monto_estimado[$i]);
					$cobro_pendiente->Edit("hito", '1');
					$cobro_pendiente->Write();
				}

				ContratoDocumentoLegal::EliminarDocumentosLegales($sesion, $contrato->fields['id_contrato'] ? $contrato->fields['id_contrato'] : $id_contrato);
				if (is_array($doc_legales)) {
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
				}
				if ($cliente->Write()) {
					$pagina->AddInfo(__('Cliente') . ' ' . __('Guardado con exito') . '<br>' . __('Contrato guardado con éxito'));
				}
			}
			else
				$pagina->AddError($contrato->error);
		}
		else
			$pagina->AddError($cliente->error);
	}

			$asuntos = explode(';', UtilesApp::GetConf($sesion, 'AgregarAsuntosPorDefecto'));

	if ( $asuntos[0] == "true" && $loadasuntos ) {

		for ($i = 1; $i < count($asuntos); $i++) {

			$asunto = new Asunto($sesion);
			$asunto->Edit('codigo_asunto', $asunto->AsignarCodigoAsunto($codigo_cliente));
			$asunto->Edit('codigo_asunto_secundario', $asunto->AsignarCodigoAsuntoSecundario($codigo_cliente_secundario));
			$asunto->Edit('glosa_asunto', $asuntos[$i]);
			$asunto->Edit('codigo_cliente', $codigo_cliente);
			//if($i==1 || $asuntos[0]==false)
			$asunto->Edit('id_contrato', $contrato->fields['id_contrato']);
			//else
			//$asunto->Edit('id_contrato',$contra->fields['id_contrato']);
			$asunto->Edit('id_usuario', $id_usuario);
			$asunto->Edit('contacto', $contacto);
			$asunto->Edit("fono_contacto", $fono_contacto_contrato);
			$asunto->Edit("email_contacto", $email_contacto_contrato);
			$asunto->Edit("direccion_contacto", $direccion_contacto_contrato);
			if (!$id_usuario_encargado || $id_usuario_encargado==-1) {
                $id_usuario_encargado = ($id_usuario_secundario)? $id_usuario_secundario : 0;
            }
            $asunto->Edit("id_encargado", $id_usuario_encargado);
			$asunto->Write();
		}
	}
}

$pagina->titulo = __('Ingreso cliente');
$pagina->PrintTop();
?>
<script type="text/javascript">
	var CodigoSecundario=<?php echo $CodigoSecundario; ?>;
	var glosa_cliente_unica = false;
	var rut_cliente_unica = false;
	var glosa_cliente_tmp = '';
	var rut_cliente_tmp = '';
	var tmp_time = 0;
	var cargando = false;


	function validarUnicoCliente(dato,campo,id_cliente)
	{


		var accion = 'existe_'+campo+'_cliente';
		if(id_cliente !== undefined)
		{
			var url_ajax = 'ajax.php?accion='+accion+'&dato_cliente='+dato+'&id_cliente='+id_cliente;
		}
		else
		{
			var url_ajax = 'ajax.php?accion='+accion+'&dato_cliente='+dato;
		}




	jQuery.get(url_ajax,function(response) {

		if(response==0)
			{
				if(campo == 'glosa')
				{
					glosa_cliente_unica = true;
				}
				else if(campo == 'rut')
				{
					rut_cliente_unica = true;
				}
			}
			else if(response==1)
			{
				if(campo == 'glosa')
				{
					glosa_cliente_unica = false;
				}
				else if(campo == 'rut')
				{
					rut_cliente_unica = false;
				}
			}
				return response;
		});


	}

	function Validar(form)
	{
		if(!form)
			var form = $('formulario');

		if(!form.glosa_cliente.value)
		{
			alert("<?php echo  __('Debe ingresar el nombre del cliente') ?>");
			form.glosa_cliente.focus();
			return false;
		}

		if(validarUnicoCliente(form.glosa_cliente.value,'glosa',form.id_cliente.value))
		{
			if(!glosa_cliente_unica)
			{
				if(!confirm(("<?php echo  __('El nombre del cliente ya existe, ¿desea continuar de todas formas?') ?>")))
				{
					form.glosa_cliente.focus();
					return false;
				}
			}
		}

<?php if ($validaciones_segun_config) { ?>
			// DATOS FACTURACION

			<?php if( UtilesApp::GetConf($sesion,'ClienteReferencia') ) { ?>

			if(!form.id_cliente_referencia.value || form.id_cliente_referencia.value == -1)
			{
				alert("<?php echo  __('Debe ingresar la referencia')?>");
				form.id_cliente_referencia.focus();
				return false;
			}

			<?php } ?>

			if(!form.factura_rut.value)
			{
				alert("<?php echo  __('Debe ingresar el') . ' ' . __('RUT') . ' ' . __('del cliente') ?>");
				MuestraPorValidacion('datos_factura');
				form.factura_rut.focus();
				return false;
			}

			if(!form.factura_razon_social.value)
			{
				alert("<?php echo  __('Debe ingresar la razón social del cliente') ?>");
				MuestraPorValidacion('datos_factura');
				form.factura_razon_social.focus();
				return false;
			}

			if(!form.factura_giro.value)
			{
				alert("<?php echo  __('Debe ingresar el giro del cliente') ?>");
				MuestraPorValidacion('datos_factura');
				form.factura_giro.focus();
				return false;
			}

			if(!form.factura_direccion.value)
			{
				alert("<?php echo  __('Debe ingresar la dirección del cliente') ?>");
				MuestraPorValidacion('datos_factura');
				form.factura_direccion.focus();
				return false;
			}
			<?php if( UtilesApp::existecampo('factura_ciudad', 'contrato', $sesion)) {	?>
				if(!form.factura_ciudad.value)
			{
				alert("<?php echo  __('Debe ingresar la ciudad del cliente') ?>");
				MuestraPorValidacion('datos_factura');
				form.factura_ciudad.focus();
				return false;
				}
			<?php } ?>

			<?php if( UtilesApp::existecampo('factura_comuna', 'contrato', $sesion)) {	?>
				if(!form.factura_comuna.value)
			{
				alert("<?php echo  __('Debe ingresar la comuna del cliente') ?>");
				MuestraPorValidacion('datos_factura');
				form.factura_comuna.focus();
				return false;
			}
			<?php } ?>

			if(form.id_pais.options[0].selected == true)
			{
				alert("<?php echo  __('Debe ingresar el pais del cliente') ?>");
				MuestraPorValidacion('datos_factura');
				form.id_pais.focus();
				return false;
			}

			if(!form.cod_factura_telefono.value)
			{
				alert("<?php echo  __('Debe ingresar el codigo de area del teléfono') ?>");
				MuestraPorValidacion('datos_factura');
				form.cod_factura_telefono.focus();
				return false;
			}

			if(!form.factura_telefono.value)
			{
				alert("<?php echo  __('Debe ingresar el número de telefono') ?>");
				MuestraPorValidacion('datos_factura');
				form.factura_telefono.focus();
				return false;
			}

			// SOLICITANTE
			if(form.titulo_contacto.options[0].selected == true)
			{
				alert("<?php echo  __('Debe ingresar el titulo del solicitante') ?>");
				MuestraPorValidacion('datos_solicitante');
				form.titulo_contacto.focus();
				return false;
			}

			if(!form.nombre_contacto.value)
			{
				alert("<?php echo  __('Debe ingresar el nombre del solicitante') ?>");
				MuestraPorValidacion('datos_solicitante');
				form.nombre_contacto.focus();
				return false;
			}

			if(!form.apellido_contacto.value)
			{
				alert("<?php echo  __('Debe ingresar el apellido del solicitante') ?>");
				MuestraPorValidacion('datos_solicitante');
				form.apellido_contacto.focus();
				return false;
			}

			if(!form.fono_contacto_contrato.value)
			{
				alert("<?php echo  __('Debe ingresar el teléfono del solicitante') ?>");
				MuestraPorValidacion('datos_solicitante');
				form.fono_contacto_contrato.focus();
				return false;
			}

			if(!form.email_contacto_contrato.value)
			{
				alert("<?php echo  __('Debe ingresar el email del solicitante') ?>");
				MuestraPorValidacion('datos_solicitante');
				form.email_contacto_contrato.focus();
				return false;
			}

			if(!form.direccion_contacto_contrato.value)
			{
				alert("<?php echo  __('Debe ingresar la dirección de envío del solicitante') ?>");
				MuestraPorValidacion('datos_solicitante');
				form.direccion_contacto_contrato.focus();
				return false;
			}

			// DATOS DE TARIFICACION
			if(!(form.tipo_tarifa[0].checked || form.tipo_tarifa[1].checked))
			{
				alert("<?php echo  __('Debe seleccionar un tipo de tarifa') ?>");
				MuestraPorValidacion('datos_cobranza');
				form.tipo_tarifa[0].focus();
				return false;
			}

			/* Revisa antes de enviar, que se haya escrito un monto si seleccionó tarifa plana */

			if( form.tipo_tarifa[1].checked && form.tarifa_flat.value.length == 0 )
			{
				alert("<?php echo  __('Ud. ha seleccionado una tarifa plana pero no ha ingresado el monto.') ?>");
				MuestraPorValidacion('datos_cobranza');
				form.tarifa_flat.focus();
				return false;
			}

			/*if(!form.id_moneda.options[0].selected == true)
		{
			alert("<?php echo  __('Debe seleccionar una moneda para la tarifa') ?>");
			MuestraPorValidacion('datos_cobranza');
			form.id_moneda.focus();
			return false;
		}*/

			if(!$$('[name="forma_cobro"]').any(function(elem){return elem.checked;}))
			{
				alert("<?php echo  __('Debe seleccionar una forma de cobro') . ' ' . __('para la tarifa') ?>");
				form.forma_cobro[0].focus();
				return false;
			}

			if($('fc7').checked){
				if($$('[id^="fila_hito_"]').any(function(elem){return !validarHito(elem, true);})){
					return false;
				}
				if(!$$('[id^="hito_monto_"]').any(function(elem){return Number(elem.value)>0;})){
					alert("<?php echo  __('Debe ingresar al menos un hito válido') ?>");
					$('hito_descripcion_1').focus();
					return false;
				}
			}
			/*
		if(!form.opc_moneda_total.value)
		{
			alert("<?php echo  __('Debe seleccionar una moneda para mostrar el total') ?>");
			MuestraPorValidacion('datos_cobranza');
			form.opc_moneda_total.focus();
			return false;
		}*/

			if(!form.observaciones.value)
			{
				alert("<?php echo  __('Debe ingresar un detalle para la cobranza') ?>");
				MuestraPorValidacion('datos_cobranza');
				form.observaciones.focus();
				return false;
			}

<?php } ?>

		// NUEVO MODULO FACTURA
<?php
if (method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'NuevoModuloFactura')) {
	?>
				if (!validar_doc_legales(true)) {
					return false;
				}
<?php } ?>

<?php
if (UtilesApp::GetConf($sesion, 'TodoMayuscula')) {
	echo "form.glosa_cliente.value=form.glosa_cliente.value.toUpperCase();";
}
?>

<?php
if (UtilesApp::GetConf($sesion, 'CodigoSecundario')) {
	?>
			if(!form.codigo_cliente_secundario.value)
			{
				alert("<?php echo  __('Debe ingresar el código secundario del cliente') ?>");
				form.codigo_cliente_secundario.focus();
				return false;
			}
	<?php
}
?>

		if(form.factura_rut.value)
		{
			validarUnicoCliente(form.factura_rut.value,'rut',form.id_cliente.value);
			if(!rut_cliente_unica)
			{
				if(!confirm(("<?php echo  __('El rut del cliente ya existe, ¿desea continuar de todas formas?') ?>")))
				{
					form.factura_rut.focus();
					return false;
				}
			}
		}

                <?php if ($usuario_responsable_obligatorio) { ?>
                if ($('id_usuario_responsable').value == '-1')
                {
                    alert("<?php echo  __("Debe ingresar el") . " " . __('Encargado Comercial') ?>");
                    $('id_usuario_responsable').focus();
                    return false;
                }
                <?php } ?>

                <?php if ($usuario_secundario_obligatorio && UtilesApp::GetConf($sesion, 'EncargadoSecundario')) { ?>
                if ($('id_usuario_secundario').value == '-1')
                {
                    alert("<?php echo  __("Debe ingresar el") . " " . __('Encargado Secundario') ?>");
                    $('id_usuario_secundario').focus();
                    return false;
                }
                <?php } ?>

		form.submit();
		return true;
	}

	function MuestraPorValidacion(divID)
	{
		var divArea = $(divID);
		var divAreaImg = $(divID+"_img");
		var divAreaVisible = divArea.style['display'] != "none";
		divArea.style['display'] = "inline";
		divAreaImg.innerHTML = "<img src='../templates/default/img/menos.gif' border='0' title='Ocultar'>";
	}

	function calcHeight(idIframe, idMainElm){
		ifr = $(idIframe);
		the_size = ifr.$(idMainElm).offsetHeight + 20;
		new Effect.Morph(ifr, {
			style: 'height:'+the_size+'px',
			duration: 0.2
		});
	}

	function ShowMonto()
	{
		div = document.getElementById("div_monto");
		div.style.display = "block";
	}


	function HideMonto()
	{
		div = document.getElementById("div_monto");
		div.style.display = "none";
	}
	function goLite(form, boton)
	{
		var btn = $(boton);
		btn.style['color'] = '#336699';
		btn.style['borderTopColor'] = '#666666';
		btn.style['borderBottomColor'] = '#666666';
	}

	function goDim(form, boton)
	{
		var btn = $(boton);
		btn.style['color'] = '#777777';
		btn.style['borderTopColor'] = '#AAAAAA';
		btn.style['borderBottomColor'] = '#AAAAAA';
	}
	function iframeLoad(url)
	{
		window.document.getElementById('iframe_asuntos').src = url;
	}

</script>
<script src="//static.thetimebilling.com/js/bootstrap.min.js"></script>

<!--<link rel="stylesheet" href="//static.thetimebilling.com/css/bootstrap.min.css" />-->
<style>

	 textarea, input[type="text"], input[type="password"], input[type="datetime"], input[type="datetime-local"], input[type="date"], input[type="month"], input[type="time"], input[type="week"]
	, input[type="number"], input[type="email"], input[type="url"], input[type="search"], input[type="tel"], input[type="color"], .uneditable-input {padding: 1px 2px !important;}
	select {padding: 1px 1px 1px 3px !important;height:20px !important;}

	h2 {font-size:14px;line-height:18px;}
	  legend {vertical-align: top  !important;
margin-bottom: 15px !important;;
	border-bottom:0 none !important;
	  width:auto !important}
	.input-append .add-on, .input-prepend .add-on {padding: 2px 5px;}
fieldset {border:1px solid #CCC !important;margin:auto;}

	.selectMultiple {height:80px !important;}

</style>


<form name='formulario' id="formulario-cliente" method="post" action="<?php echo  $_SERVER[PHP_SELF] ?>" >
	<input type="hidden" name="opcion" value="guardar" />
		<input type="hidden" name='opcion_contrato' value="guardar_contrato" />
	<input type="hidden" name="id_cliente" value="<?php echo  $cliente->fields['id_cliente'] ?>" />
	<input type="hidden" name="id_contrato" value="<?php echo  $contrato->fields['id_contrato'] ?>" />
	<input type="hidden" name="desde" id="desde" value="agregar_cliente" />
<?php
$tip_tasa = __('Tip tasa');
$tip_suma = __('Tip suma');
$tip_retainer = __('Tip retainer');
$tip_proporcional = __('El cliente compra un número de horas, el exceso de horas trabajadas se cobra proporcional a la duración de cada trabajo.');
$tip_flat = __('Tip flat');
$tip_honorarios = __('Tip honorarios');
$tip_mensual = __('Tip mensual');
$tip_tarifa_especial = __('Tip tarifa especial');

function TTip($texto) {
	return "onmouseover=\"ddrivetip('$texto');\" onmouseout=\"hideddrivetip('$texto');\"";
}
?>

							<fieldset   id="formularioinicial" class="tb_base" style="border: 1px solid #BDBDBD;">
								<legend><?php echo  __('Agregar Cliente') ?>&nbsp;&nbsp;<?php echo  $cliente->fields['activo'] == 0 && $id_cliente ? '<span style="color:#FF0000; font-size:10px">(' . __('Este cliente está Inactivo') . ')</span>' : '' ?></legend>
							 <table width='90%' cellspacing='3' cellpadding='3' >
									<tr  class="controls controls-row " >
										<td class="ar"  width="200">


											<div class="span2"><?php echo  __('Codigo') ;	 if ($validaciones_segun_config);		echo $obligatorio ?></div >
										</td>
										<td class="al " width="600">
										<div   class="controls controls-row " style="white-space:nowrap;">	  <input type="text"  style="float:left;" class="input-small  span2"  placeholder=".input-small" name="codigo_cliente" size="5" maxlength="5" <?php echo  $codigo_obligatorio ? 'readonly="readonly"' : '' ?> value="<?php echo  $cliente->fields['codigo_cliente'] ?>" onchange="this.value=this.value.toUpperCase()" />
											<div class="span4"  style="float:left;">&nbsp;&nbsp;&nbsp;<label ><?php echo  __('Código secundario') ?>
												<input type="text"class="input-small "  id="codigo_cliente_secundario" name="codigo_cliente_secundario" size="15" maxlength="20" value="<?php echo  $cliente->fields['codigo_cliente_secundario'] ?>" onchange="this.value=this.value.toUpperCase()" style='text-transform: uppercase;' />
				<?php
				if ( $CodigoSecundario )  {
					echo "<span  class=\"help-inline\" style='color:#FF0000;'>*</span>";
				} else {
					echo "<span class=\"help-inline\"  >(" . __('Opcional') . ")</span>";
				}
				?>
												</div>
											</div>
										</td>
								</tr>
								<tr class="controls controls-row">
									<td class="ar">


											<div class="span2"><?php echo  __('Nombre') ?>
											<span class="req inline-help">*</span></div>
									</td>
									<td class="al">
											<input type="text" class="span5" name="glosa_cliente" id="glosa_cliente" size="50" value="<?php echo  $cliente->fields['glosa_cliente'] ?>"  />

							</td>
						</tr>
							<tr  class="controls controls-row ">
								<td class="ar">


											<div class="span2"><?php echo  __('Grupo') ?></div>
								</td>
								<td class="al">
											<?php echo  Html::SelectQuery($sesion, "SELECT * FROM grupo_cliente", "id_grupo_cliente", $cliente->fields[id_grupo_cliente], " class='span3' ", __('Ninguno')) ?>

							</td>
						</tr>
										 	<?php if( UtilesApp::GetConf($sesion,'ClienteReferencia') ):
										 echo '<tr>
											<td class="ar">
													<div   class="controls controls-row ">'. __('Referencia') ;
												 if ($validaciones_segun_config)		echo $obligatorio ;
											 echo '</div></td><td class="al"><div class="span2">';
												 echo Html::SelectQuery($sesion,"SELECT id_cliente_referencia, glosa_cliente_referencia FROM prm_cliente_referencia ORDER BY orden ASC","id_cliente_referencia",$cliente->fields['id_cliente_referencia'] ? $cliente->fields['id_cliente_referencia'] : '', " class='span3' ", "Vacio");
												 echo '</div>
												</td>
											</tr>';
									endif; ?>

<?php
$params_array['lista_permisos'] = array('REV'); // permisos de consultor jefe
$permisos = $sesion->usuario->permisos->Find('FindPermiso', $params_array);

if ($permisos->fields['permitido']) {
	$where = 1;
} else {
	$where = "usuario_secretario.id_secretario = '" . $sesion->usuario->fields['id_usuario'] . "' OR usuario.id_usuario IN ('$id_usuario','" . $sesion->usuario->fields['id_usuario'] . "')";
}

$query = "SELECT usuario.id_usuario, CONCAT_WS(' ', apellido1, apellido2,',',nombre) AS nombre FROM usuario LEFT JOIN usuario_secretario ON usuario.id_usuario = usuario_secretario.id_profesional WHERE $where AND usuario.activo=1 AND usuario.visible=1 GROUP BY id_usuario ORDER BY nombre";


?>

<?php if ( UtilesApp::GetConf($sesion, 'VerCampoUsuarioEncargado') != 1):
	  if(!UtilesApp::GetConf($sesion, 'EncargadoSecundario')):
		  if(UtilesApp::GetConf($sesion, 'AtacheSecundarioSoloAsunto')==0):

					  echo '<tr  class="controls controls-row ">
								<td class="ar">
									 	 <div class="span2">'. __('Usuario encargado').' </div> ';
										if ($validaciones_segun_config):
											echo $obligatorio;
										endif;
								echo '</td>
								<td class="al"> ';
			 							$id_default = $cliente->fields['id_usuario_encargado'] ? $cliente->fields['id_usuario_encargado'] : '';
										echo Html::SelectQuery($sesion, $query, "id_usuario_encargado", $id_default,  " class='span3' ", 'Vacio', 'width="170"');
						echo '
						</td>
					</tr>	 ';
		 endif;
	  endif;
  endif; ?>
					 <tr class="controls controls-row ">
						<td class="ar">

								 <div class="span2">
								    <?php echo  __('Fecha Creación') ;
									$intfechacreacion=intval( date('Ymd',strtotime($cliente->fields['fecha_creacion'])));
									if($intfechacreacion>19990101) {
										$fecha_creacion=date('d-m-Y',strtotime($cliente->fields['fecha_creacion']));
									} else {
										$fecha_creacion=date('d-m-Y');
									}

									?>

								 </div>
						 </td>
						<td class="al">
										  <div class="span3">
											<input type="text" name="fecha_creacion" class="span2 fechadiff" id="fecha_creacion" readonly="true" size="50" value="<?php echo  $fecha_creacion; ?>"  />
										 </div>

							</td>
						</tr>
						<tr class="controls controls-row ">
									<td class="ar">



									 <div class="span2">		<?php echo  __('Activo') ?></div>
							 </td>
							<td class="al">
										 <div class="span4">	<label for  class="activo">	<input type='checkbox' name='activo' id="activo" value='1' <?php echo  $cliente->fields['activo'] == 1 ? 'checked="checked"' : !$id_cliente ? 'checked="checked"' : ''  ?>/>
											&nbsp;<?php echo  __('Los clientes inactivos no aparecen en los listados.') ?></label> </div>

							</td>
						</tr>
			<tr>
				<td align="right" colspan="2">
					<div class="span6">&nbsp;<!--espaciador-->		 </div>
							</td>
						</tr>

							</fieldset>

							<table width='100%' cellspacing="0" cellpadding="0">
								<tr>
									<td>
										<?php require_once Conf::ServerDir() . '/interfaces/agregar_contrato.php'; ?>
									</td>
								</tr>
							</table>
							<table width='100%' cellspacing="0" cellpadding="0" style="<?php
if (UtilesApp::GetConf($sesion, 'AlertaCliente') )  {
	echo '';
} else {
	echo 'display:none;';
}
?>">
								<tr>
									<td colspan="2" align="center">
										<fieldset  class="border_plomo tb_base">
											<legend><?php echo  __('Alertas') ?></legend>
											<p>&nbsp;<?php echo  __('El sistema enviará un email de alerta al encargado del cliente si se superan estos límites:') ?></p>
											<table>
												<tr>
													<td align=right>
														<input name=cliente_limite_hh value="<?php echo  $cliente->fields['limite_hh'] ? $cliente->fields['limite_hh'] : '0' ?>" size=5 title="<?php echo  __('Total de Horas') ?>"/>
													</td>
													<td colspan=3 align=left>
														<span title="<?php echo  __('Total de Horas') ?>"><?php echo  __('Límite de horas') ?></span>
													</td>
													<td align=right>
														<input name=cliente_limite_monto value="<?php echo  $cliente->fields['limite_monto'] ? $cliente->fields['limite_monto'] : '0' ?>" size=5 title="<?php echo  __('Valor Total según Tarifa Hora Hombre') ?>"/>
													</td>
													<td colspan=3 align=left>
														<span title="<?php echo  __('Valor Total según Tarifa Hora Hombre') ?>"><?php echo  __('Límite de monto') ?></span>
													</td>
												</tr>
												<tr>
													<td align=right>
														<input name=cliente_alerta_hh value="<?php echo  $cliente->fields['alerta_hh'] ? $cliente->fields['alerta_hh'] : '0' ?>" title="<?php echo  __('Total de Horas en trabajos no cobrados') ?>" size=5 />
													</td>
													<td colspan=3 align=left>
														<span title="<?php echo  __('Total de Horas en trabajos no cobrados') ?>"><?php echo  __('horas no cobradas') ?></span>
													</td>
													<td align=right>
														<input name=cliente_alerta_monto value="<?php echo  $cliente->fields['alerta_monto'] ? $cliente->fields['alerta_monto'] : '0' ?>" title="<?php echo  __('Valor Total según Tarifa Hora Hombre en trabajos no cobrados') ?>" size=5 />
													</td>
													<td colspan=3 align=left>
														<span title="<?php echo  __('Valor Total según Tarifa Hora Hombre en trabajos no cobrados') ?>"><?php echo  __('monto según horas no cobradas') ?>
													</td>
												</tr>
											</table>
										</fieldset>
									</td>
								</tr>
							</table>

							<table width="100%" cellspacing="3" cellpadding="3">
								<tr>
									<td colspan="2" align="center">
<?php
if ($cant_encargados > 0) {
	if (UtilesApp::GetConf($sesion, 'RevisarTarifas')) {
		$funcion_validar = "return RevisarTarifas('id_tarifa', 'id_moneda', jQuery('#formulario-cliente').get(0), false);";
	} else {
		$funcion_validar = "return Validar(jQuery('#formulario-cliente').get(0));";
	}
		?>
								<a href="javascript:void(0);" icon="ui-icon-save" class="btn botonizame" onclick="<?php echo $funcion_validar; ?>" /><?php echo __('Guardar'); ?></a>
		<?php
} else {
	?>
											<span style="font-size:10px;background-color:#C6DEAD"><?php echo  __('No se han configurado encargados comerciales') . '<br>' . __('Para configurar los encargados comerciales debe ir a Usuarios y activar el perfil comercial.') ?></span>
										<?php } ?>
									</td>
								</tr>
							</table>
							<br/><br/>
							<table width="100%">
								<tr>
									<td class="cvs" align="center">
										<input type="button" name="asuntos" id='asuntos' class=" botonizame" value="<?php echo  __('Asuntos') ?>" onMouseOver="goLite(this.form,this)" onMouseOut="goDim(this.form,this)" onClick="iframeLoad('asuntos.php?codigo_cliente=<?php echo  $cliente->fields['codigo_cliente'] ?>&opc=entregar_asunto&popup=1&from=agregar_cliente')" />
									</td>
									<td class="cvs" align="center">
										<input type="button" name="contratos" id='contratos' class=" botonizame" value="<?php echo  __('Contratos') ?>" onMouseOver="goLite(this.form,this.name)" onMouseOut="goDim(this.form,this)" onClick="iframeLoad('contratos.php?codigo_cliente=<?php echo  $cliente->fields['codigo_cliente'] ?>&popup=1&buscar=1&activo=SI')" />
									</td>
									<td class="cvs" align="center">
										<input type="button" name="cobros" id='cobros' class=" botonizame" value="<?php echo  __('Cobros') ?>" onMouseOver="goLite(this.form,this)" onMouseOut="goDim(this.form,this)" onClick="iframeLoad('lista_cobros.php?codigo_cliente=<?php echo  $cliente->fields['codigo_cliente'] ?>&popup=1&opc=buscar&no_mostrar_filtros=1')" />
									</td>
								</tr>
								<tr>
									<td class="cvs" align="center" colspan=3>
<iframe name='iframe_asuntos'  class="resizableframe" id='iframe_asuntos' src='about:blank' style="width:100%;border:0 none;">&nbsp;</iframe>


									</td>
								</tr>
							</table>

				</form>


<script type="text/javascript">
	<?php
if ($CodigoSecundario) {
	echo "var iframesrc='asuntos.php?codigo_cliente_secundario=" . $cliente->fields['codigo_cliente_secundario'] . "&opc=entregar_asunto&popup=1&from=agregar_cliente';";
} else {
	echo "var iframesrc='asuntos.php?codigo_cliente=" . $cliente->fields['codigo_cliente'] . "&opc=entregar_asunto&popup=1&from=agregar_cliente';";
}
?>

  jQuery(document).ready(function() {


		 setTimeout(function() {
			jQuery( "#iframe_asuntos" ).attr('src',iframesrc);
		}, 2000);
	});

    jQuery('#codigo_cliente_secundario').blur(function() {
	if(jQuery(this).val()=="") return;
	<?php if ($_GET['id_cliente'])  {
				echo 'var id_cliente='.intval($_GET['id_cliente']).';';
			} else {
				echo 'var id_cliente=null;';
			}
?>
			var dato=jQuery(this).val();
			var campo=jQuery(this).attr('id');
			var accion = 'existe_'+campo+'_cliente';
			var url_ajax = 'ajax.php?accion='+accion+'&dato_cliente='+dato;
			jQuery.get(url_ajax,function(data) {
			objResp=null;
				try {
					var objResp=JSON.parse(data);

				} catch (e) {
					console.log(e)
				}

					if(objResp) {
					var bd_cliente=1*objResp.id_cliente;
						if(id_cliente!=null && bd_cliente==id_cliente) {
							console.log(id_cliente, bd_cliente);
							return true;
						} else {
							console.log(id_cliente, objResp);

							var codigo_cliente=objResp.codigo_cliente;
							var codigo_cliente_secundario=objResp.codigo_cliente_secundario;
							var glosa_cliente=objResp.glosa_cliente;
							if(codigo_cliente!="") {
							jQuery('#formularioinicial').prepend('<div  class="alert"><span  id="alerta"></span><a class="close" data-dismiss="alert">×</a>  </div>'  );
							var MensajeAlerta="Error: el código secundario "+codigo_cliente_secundario+" ya existe en la Base de Datos y corresponde a <a href='?id_cliente="+bd_cliente+"'>["+codigo_cliente +"] "+glosa_cliente+"</a>."
							jQuery('#alerta').html(MensajeAlerta).alert();
							}
						}
					}

			});
		});





    </script>


<?php
$pagina->NewPrintBottom();

