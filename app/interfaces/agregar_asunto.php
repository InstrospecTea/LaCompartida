<?php
require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion(array('DAT', 'SASU'));
$pagina = new Pagina($sesion);
$id_usuario = $sesion->usuario->fields['id_usuario'];

$tip_tasa = "En esta modalidad se cobra hora a hora. Cada profesional tiene asignada su propia tarifa para cada asunto.";
$tip_suma = "Es un único monto de dinero para el asunto. Aquí interesa llevar la cuenta de HH para conocer la rentabilidad del proyecto. Esta es la única modalida de " . __('cobro') . " que no puede tener límites.";
$tip_retainer = "El cliente compra un número de HH. El límite puede ser por horas o por un monto.";
$tip_flat = "El cliente acuerda cancelar un <strong>monto fijo mensual</strong> por atender todos los trabajos de este asunto. Puede tener límites por HH o monto total";
$tip_honorarios = "Sólamente lleva la cuenta de las HH profesionales. Al terminar el proyecto se puede cobrar eventualmente.";
$tip_mensual = __('El cobro') . " se hará de forma mensual.";
$tip_tarifa_especial = "Al ingresar una nueva tarifa, esta se actualizará automáticamente.";
$tip_individual = __('El cobro') . " se hará de forma individual de acuerdo al monto definido por Cliente.";

function TTip($texto) {
	return "onmouseover=\"ddrivetip('$texto');\" onmouseout=\"hideddrivetip('$texto');\"";
}

if (UtilesApp::GetConf($sesion, 'CodigoObligatorio')) {
	$codigo_obligatorio = true;
} else {
	$codigo_obligatorio = false;
}

$validaciones_segun_config = UtilesApp::GetConf($sesion, 'ValidacionesCliente');
$obligatorio = '<span class="req">*</span>';
$usuario_responsable_obligatorio = UtilesApp::GetConf($sesion, 'ObligatorioEncargadoComercial');
$usuario_secundario_obligatorio = UtilesApp::GetConf($sesion, 'ObligatorioEncargadoSecundarioAsunto');
$encargado_obligatorio = UtilesApp::GetConf($sesion, 'AtacheSecundarioSoloAsunto') == 1;

$contrato = new Contrato($sesion);
$cliente = new Cliente($sesion);
$asunto = new Asunto($sesion);

if ($codigo_cliente_secundario != '') {
	$cliente->LoadByCodigoSecundario($codigo_cliente_secundario);
	$codigo_cliente = $cliente->fields['codigo_cliente'];
	$query_codigos = "SELECT id_asunto, codigo_asunto_secundario FROM asunto WHERE codigo_cliente='$codigo_cliente'";
	$resp_codigos = mysql_query($query_codigos, $sesion->dbh) or Utiles::errorSQL($query_codigos, __FILE__, __LINE__, $sesion->dbh);

	while (list($id_asunto_temp, $codigo_asunto_secundario_temp) = mysql_fetch_array($resp_codigos)) {
		$caracteres = strlen($codigo_asunto_secundario);
		if ($codigo_asunto_secundario == substr($codigo_asunto_secundario_temp, $caracteres)) {
			if (empty($id_asunto) || $id_asunto != $id_asunto_temp) {
				$pagina->FatalError('El código ingresado ya existe');
			}
		}
	}
}

if ($id_asunto > 0) {
	if (!$asunto->Load($id_asunto)) {
		$pagina->FatalError('Código inválido');
	}

	if ($asunto->fields['id_contrato'] > 0) {
		$contrato->Load($asunto->fields['id_contrato']);
	}

	$cliente->LoadByCodigo($asunto->fields['codigo_cliente']);
	if (!$cliente->Loaded()) {
		if ($codigo_cliente != '') {
			$cliente->LoadByCodigo($codigo_cliente);
		}
	} else if ($cliente->fields['codigo_cliente'] != $codigo_cliente) {
		// Esto hay que revisarlo se usó como parche y se debería de corregir
		if (UtilesApp::GetConf($sesion, 'CodigoEspecialGastos')) {
			$codigo_asunto = $asunto->AsignarCodigoAsunto($codigo_cliente, $glosa_asunto);
		} else {
			$codigo_asunto = $asunto->AsignarCodigoAsunto($codigo_cliente);
		}
		// validación para que al cambiar un asunto de un cliente a otro,
		// no existan cobros ni gastos asociados para el cliente inicial
		if ($opcion == "guardar") {

			$query = "SELECT COUNT(*) FROM cobro WHERE id_cobro IN (SELECT c.id_cobro FROM cobro_asunto c WHERE codigo_asunto = '" . $asunto->fields['codigo_asunto'] . "' ) AND codigo_cliente = '" . $cliente->fields['codigo_cliente'] . "' ";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
			list($count) = mysql_fetch_array($resp);

			if ($count > 0) {
				$pagina->AddError(__('No se puede cambiar el cliente a un asunto que tiene ') . __('cobros') . ' ' . __('asociados'));
			}

			$query = "SELECT COUNT(*) FROM cta_corriente WHERE codigo_asunto = '" . $asunto->fields['codigo_asunto'] . "' AND codigo_cliente = '" . $cliente->fields['codigo_cliente'] . "' ";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
			list($count) = mysql_fetch_array($resp);

			if ($count > 0) {
				$pagina->AddError(__('No se puede cambiar el cliente a un asunto que tiene gastos asociados'));
			}
		}
	} else if ($cliente->fields['codigo_cliente_secundario'] != $codigo_cliente_secundario && UtilesApp::GetConf($sesion, 'CodigoSecundario')) {
		$codigo_asunto = $asunto->AsignarCodigoAsunto($codigo_cliente);
	}
}

if ($codigo_cliente != '') {
	$cliente->LoadByCodigo($codigo_cliente);
}

if ($cliente->Loaded() && empty($id_asunto) && (!isset($opcion) || $opcion != "guardar")) {
	$contrato_cliente = new Contrato($sesion);
	$contrato_cliente->Load($cliente->fields['id_contrato']);
	$cargar_datos_contrato_cliente_defecto = $contrato_cliente->fields;
}

if ($opcion == "guardar") {
	$enviar_mail = 1;

	// Validaciones
	if ($validaciones_segun_config && $cobro_independiente) {
		if (empty($glosa_asunto)) {
			$pagina->AddError(__("Por favor ingrese el nombre del cliente"));
		}
		if (empty($codigo_cliente)) {
			$pagina->AddError(__("Por favor ingrese el codigo del cliente"));
		}
		if (empty($factura_rut)) {
			$pagina->AddError(__("Por favor ingrese ROL/RUT de la factura"));
		}
		if (empty($factura_razon_social)) {
			$pagina->AddError(__("Por favor ingrese la razón social de la factura"));
		}
		if (empty($factura_giro)) {
			$pagina->AddError(__("Por favor ingrese el giro de la factura"));
		}
		if (empty($factura_direccion)) {
			$pagina->AddError(__("Por favor ingrese la dirección de la factura"));
		}
		if (UtilesApp::existecampo('factura_comuna', 'contrato', $sesion)) {
			if (empty($factura_comuna)) {
				$pagina->AddError(__("Por favor ingrese la comuna de la factura"));
			}
		}

		if (UtilesApp::existecampo('factura_ciudad', 'contrato', $sesion)) {
			if (empty($factura_ciudad)) {
				$pagina->AddError(__("Por favor ingrese la ciudad de la factura"));
			}
		}

		if (empty($factura_telefono)) {
			$pagina->AddError(__("Por favor ingrese el teléfono de la factura"));
		}

		if (UtilesApp::GetConf($sesion, 'TituloContacto')) {
			if (empty($titulo_contacto)) {
				$pagina->AddError(__("Por favor ingrese titulo del solicitante"));
			}
			if (empty($nombre_contacto)) {
				$pagina->AddError(__("Por favor ingrese nombre del solicitante"));
			}
			if (empty($apellido_contacto)) {
				$pagina->AddError(__("Por favor ingrese apellido del solicitante"));
			}
		} else {
			if (empty($contacto)) {
				$pagina->AddError(__("Por favor ingrese contanto del solicitante"));
			}
		}

		if (empty($fono_contacto_contrato)) {
			$pagina->AddError(__("Por favor ingrese el teléfono del solicitante"));
		}
		if (empty($email_contacto_contrato)) {
			$pagina->AddError(__("Por favor ingrese el correo del solicitante"));
		}
		if (empty($direccion_contacto_contrato)) {
			$pagina->AddError(__("Por favor ingrese la dirección del solicitante"));
		}

		if (empty($id_tarifa)) {
			$pagina->AddError(__("Por favor ingrese la tarifa en la tarificación"));
		}
		if (empty($id_moneda)) {
			$pagina->AddError(__("Por favor ingrese la moneda de la tarifa en la tarificación"));
		}

		if (empty($forma_cobro)) {
			$pagina->AddError(__("Por favor ingrese la forma de ") . __("cobro") . __(" en la tarificación"));
		} else {
			switch ($forma_cobro) {
				case "RETAINER":
					if (empty($monto)) {
						$pagina->AddError(__("Por favor ingrese el monto para el retainer en la tarificación"));
					}
					if ($retainer_horas <= 0) {
						$pagina->AddError(__("Por favor ingrese las horas para el retainer en la tarificación"));
					}
					if (empty($id_moneda_monto)) {
						$pagina->AddError(__("Por favor ingrese la moneda para el retainer en la tarificación"));
					}
					break;
				case "FLAT FEE":
					if (empty($monto)) {
						$pagina->AddError(__("Por favor ingrese el monto para el flat fee en la tarificación"));
					}
					if (empty($id_moneda_monto)) {
						$pagina->AddError(__("Por favor ingrese la moneda para el flat fee en la tarificación"));
					}
					break;
				case "CAP":
					if (empty($monto)) {
						$pagina->AddError(__("Por favor ingrese el monto para el cap en la tarificación"));
					}
					if (empty($id_moneda_monto)) {
						$pagina->AddError(__("Por favor ingrese la moneda para el cap en la tarificación"));
					}
					if (empty($fecha_inicio_cap)) {
						$pagina->AddError(__("Por favor ingrese la fecha de inicio para el cap en la tarificación"));
					}
					break;
				case "PROPORCIONAL":
					if (empty($monto)) {
						$pagina->AddError(__("Por favor ingrese el monto para el proporcional en la tarificación"));
					}
					if ($retainer_horas <= 0) {
						$pagina->AddError(__("Por favor ingrese las horas para el proporcional en la tarificación"));
					}
					if (empty($id_moneda_monto)) {
						$pagina->AddError(__("Por favor ingrese la moneda para el proporcional en la tarificación"));
					}
					break;
				case "ESCALONADA":
					if (empty($_POST['esc_tiempo'][0])) {
						$pagina->AddError(__("Por favor ingrese el tiempo para la primera escala"));
					}
					break;
				case "TASA":
				case "HITOS":
					break;
				default:
					$pagina->AddError(__("Por favor ingrese la forma de ") . __("cobro") . __(" en la tarificación"));
			}
		}

		if (empty($opc_moneda_total)) {
			$pagina->AddError(__("Por favor ingrese la moneda a mostrar el total de la tarifa en la tarificación"));
		}
		if (empty($observaciones)) {
			$pagina->AddError(__("Por favor ingrese la observacion en la tarificación"));
		}
	}

	if ($cobro_independiente) {
		if ($usuario_responsable_obligatorio && (empty($id_usuario_responsable) or $id_usuario_responsable == '-1') && $desde_agrega_cliente) {
			$pagina->AddError(__("Debe ingresar el") . " " . __('Encargado Comercial'));
		}

		if ($usuario_secundario_obligatorio && UtilesApp::GetConf($sesion, 'EncargadoSecundario') && (empty($id_usuario_secundario) or $id_usuario_secundario == '-1')) {
			$pagina->AddError(__("Debe ingresar el") . " " . __('Encargado Secundario'));
		}
	}

	$errores = $pagina->GetErrors();
	if (!empty($errores)) {
		$val = true;
		$loadasuntos = false;
	}

	if (!$val || $opc_copiar) {
		$as = new Asunto($sesion);
		$as->LoadByCodigo($codigo_asunto);
		if ($as->Loaded()) {
			$enviar_mail = 0;
		}
		if (!$asunto->Loaded() || !$codigo_asunto) {
			if (UtilesApp::GetConf($sesion, 'CodigoEspecialGastos')) {
				$codigo_asunto = $asunto->AsignarCodigoAsunto($codigo_cliente, $glosa_asunto);
			} else {
				$codigo_asunto = $asunto->AsignarCodigoAsunto($codigo_cliente);
			}
		}
		if (!$cliente) {
			$cliente = new Cliente($sesion);
		}
		if (!$codigo_cliente_secundario) {
			$codigo_cliente_secundario = $cliente->CodigoACodigoSecundario($codigo_cliente);
		}
		$asunto->NoEditar("opcion");
		$asunto->NoEditar("popup");
		$asunto->NoEditar("motivo");
		$asunto->NoEditar("id_usuario_tarifa");
		$asunto->NoEditar("id_moneda_tarifa");
		$asunto->NoEditar("tarifa_especial");
		//$asunto->EditarTodos();
		$asunto->Edit("id_usuario", $sesion->usuario->fields['id_usuario']);
		$asunto->Edit("codigo_asunto", $codigo_asunto, true);

		$caracteres = strlen($codigo_asunto_secundario);

		if (UtilesApp::GetConf($sesion, 'CodigoSecundario')) {
			$asunto->Edit("codigo_asunto_secundario", $codigo_cliente_secundario . '-' . substr(strtoupper($codigo_asunto_secundario), -$caracteres));
		} else {
			if ($codigo_asunto_secundario) {
				$asunto->Edit("codigo_asunto_secundario", $codigo_cliente_secundario . '-' . strtoupper($codigo_asunto_secundario));
			} else {
				$asunto->Edit("codigo_asunto_secundario", $codigo_asunto);
			}
		}
		if (UtilesApp::GetConf($sesion, 'TodoMayuscula')) {
			$glosa_asunto = strtoupper($glosa_asunto);
		}
		$asunto->Edit("glosa_asunto", $glosa_asunto);
		$asunto->Edit("codigo_cliente", $codigo_cliente, true);
		if (UtilesApp::GetConf($sesion, 'ExportacionLedes')) {
			$asunto->Edit("codigo_homologacion", $codigo_homologacion ? $codigo_homologacion : 'NULL');
		}
		$asunto->Edit("id_tipo_asunto", $id_tipo_asunto, true);
		$asunto->Edit("id_area_proyecto", $id_area_proyecto, true);
		$asunto->Edit("id_idioma", $id_idioma);
		$asunto->Edit("descripcion_asunto", $descripcion_asunto);
		$asunto->Edit("id_encargado", !empty($id_encargado) ? $id_encargado : "NULL");
		$asunto->Edit("id_encargado2", !empty($id_encargado2) ? $id_encargado2 : "NULL");
		$asunto->Edit("contacto", $asunto_contacto);
		$asunto->Edit("fono_contacto", $fono_contacto);
		$asunto->Edit("email_contacto", $email_contacto);
		$asunto->Edit("actividades_obligatorias", $actividades_obligatorias ? '1' : '0');
		$asunto->Edit("activo", intval($activo), true);
		if (!$activo) {
			$fecha_inactivo = date('Y-m-d H:i:s');
			$asunto->Edit("fecha_inactivo", $fecha_inactivo, true);
		} else {
			$asunto->Edit("fecha_inactivo", '', true);
		}
		$asunto->Edit("cobrable", intval($cobrable), true);
		$asunto->Edit("mensual", $mensual ? "SI" : "NO");
		$asunto->Edit("alerta_hh", $asunto_alerta_hh);
		$asunto->Edit("alerta_monto", $asunto_alerta_monto);
		$asunto->Edit("limite_hh", $asunto_limite_hh);
		$asunto->Edit("limite_monto", $asunto_limite_monto);

		//if($asunto->Write())
		//{
		if ($cobro_independiente) {
			#CONTRATO
			if ($asunto->fields['id_contrato'] != $cliente->fields['id_contrato']) {
				$contrato->Load($asunto->fields['id_contrato']);
			} else if ($asunto->fields['id_contrato_indep'] > 0 && ($asunto->fields['id_contrato_indep'] != $cliente->fields['id_contrato'])) {
				$contrato->Load($asunto->fields['id_contrato_indep']);
			} else {
				$contrato = new Contrato($sesion);
			}
			if ($forma_cobro != 'TASA' && $forma_cobro != 'HITOS' && $forma_cobro != 'ESCALONADA' && $monto == 0) {
				$pagina->AddError(__('Ud. ha seleccionado forma de ') . __('cobro') . ': ' . $forma_cobro . ' ' . __('y no ha ingresado monto'));
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

			if (isset($_REQUEST['nombre_contacto'])) {
				// nombre_contacto no existe como campo en la tabla contrato y es necesario crear la variable "contacto" dentro de _REQUEST
				$_REQUEST['contacto'] = trim($_REQUEST['nombre_contacto']);
			}

			$contrato->Fill($_REQUEST, true);
			$contrato->Edit('codigo_cliente', $codigo_cliente);

			if ($contrato->Write()) {
				#Subiendo Archivo
				if (!empty($archivo_data)) {
					$archivo->Edit('id_contrato', $contrato->fields['id_contrato']);
					$archivo->Edit('descripcion', $descripcion);
					$archivo->Edit('archivo_data', $archivo_data);
					$archivo->Write();
				}
				#cobro pendiente
				CobroPendiente::EliminarPorContrato($sesion, $contrato->fields['id_contrato'] ? $contrato->fields['id_contrato'] : $id_contrato);
				for ($i = 2; $i <= sizeof($valor_fecha); $i++) {
					$cobro_pendiente = new CobroPendiente($sesion);
					$cobro_pendiente->Edit("id_contrato", $contrato->fields['id_contrato'] ? $contrato->fields['id_contrato'] : $id_contrato);
					$cobro_pendiente->Edit("fecha_cobro", Utiles::fecha2sql($valor_fecha[$i]));
					$cobro_pendiente->Edit("descripcion", $valor_descripcion[$i]);
					$cobro_pendiente->Edit("monto_estimado", $valor_monto_estimado[$i]);
					$cobro_pendiente->Write();
				}

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

				$asunto->Edit("id_contrato", $contrato->fields['id_contrato']);
				$asunto->Edit("id_contrato_indep", $contrato->fields['id_contrato']);

				if ($asunto->Write())
					$pagina->AddInfo(__('Asunto') . ' ' . __('Guardado con exito') . '<br>' . __('Contrato guardado con éxito'));
				else
					$pagina->AddError($asunto->error);

				ContratoDocumentoLegal::EliminarDocumentosLegales($sesion, $contrato->fields['id_contrato'] ? $contrato->fields['id_contrato'] : $id_contrato);
				if (is_array($docs_legales)) {
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
			} else {
				$pagina->AddError($contrato->error);
			}
		} #fin if independiente
		else {
			$asunto->Edit("id_contrato", $cliente->fields['id_contrato']);

			$contrato_indep = $asunto->fields['id_contrato_indep'];
			$asunto->Edit("id_contrato_indep", null);
			if ($asunto->Write()) {
				$pagina->AddInfo(__('Asunto') . ' ' . __('Guardado con exito'));
				$contrato_obj = new Contrato($sesion);
				$contrato_obj->Load($contrato_indep);
				$contrato_obj->Eliminar();
			}
			else
				$pagina->AddError($asunto->error);
		}
		if (method_exists('Conf', 'GetConf')) {
			$MailAsuntoNuevo = Conf::GetConf($sesion, 'MailAsuntoNuevo');
		} else if (method_exists('Conf', 'MailAsuntoNuevo')) {
			$MailAsuntoNuevo = Conf::MailAsuntoNuevo();
		}

		if ($enviar_mail && $MailAsuntoNuevo)
			EnviarEmail($asunto);
	}
	#}
	#else
	#$pagina->AddError($asunto->error);
}

$id_idioma_default = $contrato->IdIdiomaPorDefecto($sesion);

$pagina->titulo = "Ingreso de " . __('asunto');
$pagina->PrintTop($popup);
?>
<script type="text/javascript">
	function Volver(form)
	{
		window.opener.location = 'agregar_cliente.php?id_cliente=<?php echo $cliente->fields['id_cliente'] ?>';
		window.close();
	}

	function MuestraPorValidacion(divID)
	{
		var divArea = $(divID);
		var divAreaImg = $(divID + "_img");
		var divAreaVisible = divArea.style['display'] != "none";
		divArea.style['display'] = "inline";
		divAreaImg.innerHTML = "<img src='../templates/default/img/menos.gif' border='0' title='Ocultar'>";
	}

	function Validar(form)
	{
		if (!form) {
			var form = $('formulario');
		}

<?php if (UtilesApp::GetConf($sesion, 'AtacheSecundarioSoloAsunto') == 1): ?>
			if (form.id_encargado && !form.id_encargado.value) {
				alert('<?php echo 'Debe ingresar ' . __('Usuario encargado') ?>');
				form.id_encargado.focus();
				return false;
			}
<?php endif; ?>

		if (!form.glosa_asunto.value)
		{
			alert("Debe ingresar un título");
			form.glosa_asunto.focus();
			return false;
		}

<?php
if (UtilesApp::GetConf($sesion, 'TodoMayuscula')) {
	echo "form.glosa_asunto.value=form.glosa_asunto.value.toUpperCase();";
}

if (UtilesApp::GetConf($sesion, 'CodigoSecundario')) {
	?>
			if (!form.codigo_cliente_secundario.value)
			{
				alert("Debe ingresar un cliente");
				form.codigo_cliente_secundario.focus();
				return false;
			}
			if (!form.codigo_asunto_secundario.value)
			{
				alert("<?php echo __('Debe ingresar el código secundario del asunto') ?>");
				form.codigo_asunto_secundario.focus();
				return false;
			}
	<?php
} else {
	?>
			if (!form.codigo_cliente.value)
			{
				alert("Debe ingresar un cliente");
				form.codigo_cliente.focus();
				return false;
			}
	<?php
}
?>

		if (document.getElementById('cobro_independiente').checked) {
<?php if ($validaciones_segun_config) { ?>
				// DATOS FACTURACION

				if (!form.factura_rut.value)
				{
					alert("<?php echo __('Debe ingresar el') . ' ' . __('RUT') . ' ' . __('del cliente') ?>");
					MuestraPorValidacion('datos_factura');
					form.factura_rut.focus();
					return false;
				}

				if (!form.factura_razon_social.value)
				{
					alert("<?php echo __('Debe ingresar la razón social del cliente') ?>");
					MuestraPorValidacion('datos_factura');
					form.factura_razon_social.focus();
					return false;
				}

				if (!form.factura_giro.value)
				{
					alert("<?php echo __('Debe ingresar el giro del cliente') ?>");
					MuestraPorValidacion('datos_factura');
					form.factura_giro.focus();
					return false;
				}

				if (!form.factura_direccion.value)
				{
					alert("<?php echo __('Debe ingresar la dirección del cliente') ?>");
					MuestraPorValidacion('datos_factura');
					form.factura_direccion.focus();
					return false;
				}
	<?php if (UtilesApp::existecampo('factura_comuna', 'contrato', $sesion)) { ?>
					if (!form.factura_comuna.value)
					{
						alert("<?php echo __('Debe ingresar la comuna del cliente') ?>");
						MuestraPorValidacion('datos_factura');
						form.factura_comuna.focus();
						return false;
					}
	<?php } ?>

	<?php if (UtilesApp::existecampo('factura_ciudad', 'contrato', $sesion)) { ?>
					if (!form.factura_ciudad.value)
					{
						alert("<?php echo __('Debe ingresar la ciudad del cliente') ?>");
						MuestraPorValidacion('datos_factura');
						form.factura_ciudad.focus();
						return false;
					}
	<?php } ?>

				if (form.id_pais.options[0].selected == true)
				{
					alert("<?php echo __('Debe ingresar el pais del cliente') ?>");
					MuestraPorValidacion('datos_factura');
					form.id_pais.focus();
					return false;
				}

				if (!form.cod_factura_telefono.value)
				{
					alert("<?php echo __('Debe ingresar el codigo de area del teléfono') ?>");
					MuestraPorValidacion('datos_factura');
					form.cod_factura_telefono.focus();
					return false;
				}

				if (!form.factura_telefono.value)
				{
					alert("<?php echo __('Debe ingresar el número de telefono') ?>");
					MuestraPorValidacion('datos_factura');
					form.factura_telefono.focus();
					return false;
				}

				// SOLICITANTE
				if (form.titulo_contacto.options[0].selected == true)
				{
					alert("<?php echo __('Debe ingresar el titulo del solicitante') ?>");
					MuestraPorValidacion('datos_solicitante');
					form.titulo_contacto.focus();
					return false;
				}

				if (!form.nombre_contacto.value)
				{
					alert("<?php echo __('Debe ingresar el nombre del solicitante') ?>");
					MuestraPorValidacion('datos_solicitante');
					form.nombre_contacto.focus();
					return false;
				}

				if (!form.apellido_contacto.value)
				{
					alert("<?php echo __('Debe ingresar el apellido del solicitante') ?>");
					MuestraPorValidacion('datos_solicitante');
					form.apellido_contacto.focus();
					return false;
				}

				if (!form.fono_contacto_contrato.value)
				{
					alert("<?php echo __('Debe ingresar el teléfono del solicitante') ?>");
					MuestraPorValidacion('datos_solicitante');
					form.fono_contacto_contrato.focus();
					return false;
				}

				if (!form.email_contacto_contrato.value)
				{
					alert("<?php echo __('Debe ingresar el email del solicitante') ?>");
					MuestraPorValidacion('datos_solicitante');
					form.email_contacto_contrato.focus();
					return false;
				}

				if (!form.direccion_contacto_contrato.value)
				{
					alert("<?php echo __('Debe ingresar la dirección de envío del solicitante') ?>");
					MuestraPorValidacion('datos_solicitante');
					form.direccion_contacto_contrato.focus();
					return false;
				}

				// DATOS DE TARIFICACION
				if (!(form.tipo_tarifa[0].checked || form.tipo_tarifa[1].checked))
				{
					alert("<?php echo __('Debe seleccionar un tipo de tarifa') ?>");
					MuestraPorValidacion('datos_cobranza');
					form.tipo_tarifa[0].focus();
					return false;
				}

				/* Revisa antes de enviar, que se haya escrito un monto si seleccionó tarifa plana */

				if (form.tipo_tarifa[1].checked && form.tarifa_flat.value.length == 0)
				{
					alert("<?php echo __('Ud. ha seleccionado una tarifa plana pero no ha ingresado el monto.') ?>");
					MuestraPorValidacion('datos_cobranza');
					form.tarifa_flat.focus();
					return false;
				}

				/*if(!form.id_moneda.options[0].selected == true)
				 {
				 alert("<?php echo __('Debe seleccionar una moneda para la tarifa') ?>");
				 MuestraPorValidacion('datos_cobranza');
				 form.id_moneda.focus();
				 return false;
				 }*/

				if (!$$('[name="forma_cobro"]').any(function(elem) {
					return elem.checked;
				}))
				{
					alert("<?php echo __('Debe seleccionar una forma de cobro') . ' ' . __('para la tarifa') ?>");
					form.forma_cobro[0].focus();
					return false;
				}

				if ($('fc7').checked) {
					if ($$('[id^="fila_hito_"]').any(function(elem) {
						return !validarHito(elem, true);
					})) {
						return false;
					}
					if (!$$('[id^="hito_monto_"]').any(function(elem) {
						return Number(elem.value) > 0;
					})) {
						alert("<?php echo __('Debe ingresar al menos un hito válido') ?>");
						$('hito_descripcion_1').focus();
						return false;
					}
				}
				/*
				 if(!form.opc_moneda_total.value)
				 {
				 alert("<?php echo __('Debe seleccionar una moneda para mostrar el total') ?>");
				 MuestraPorValidacion('datos_cobranza');
				 form.opc_moneda_total.focus();
				 return false;
				 }*/

				if (!form.observaciones.value)
				{
					alert("<?php echo __('Debe ingresar un detalle para la cobranza') ?>");
					MuestraPorValidacion('datos_cobranza');
					form.observaciones.focus();
					return false;
				}

<?php }
if ($usuario_responsable_obligatorio) {
	?>
				if ($('id_usuario_responsable').value == '-1' && $('cobro_independiente').checked)
				{
					alert("<?php echo __("Debe ingresar el") . " " . __('Encargado Comercial') ?>");
					$('id_usuario_responsable').focus();
					return false;
				}
<?php } ?>

<?php if ($usuario_secundario_obligatorio && UtilesApp::GetConf($sesion, 'EncargadoSecundario')) { ?>
				if ($('id_usuario_secundario').value == '-1' && $('cobro_independiente').checked)
				{
					alert("<?php echo __("Debe ingresar el") . " " . __('Encargado Secundario') ?>");
					$('id_usuario_secundario').focus();
					return false;
				}
<?php } ?>
		}

		form.submit();
		return true;
	}

	function InfoCobro()
	{
		campo = document.getElementById("codigo_cliente")
		cliente = campo.value;
		var http = getXMLHTTP();

		http.open('get', 'ajax.php?accion=info_cobro&codigo_cliente=' + cliente);
		http.onreadystatechange = function()
		{
			if (http.readyState == 4)
			{
				var response = http.responseText;
				var update = new Array();
				if (response.indexOf('|') != -1 && response.indexOf('VACIO') != -1)
					alert(response);
				else if (response.indexOf('|') != -1)
				{
					arreglo = response.split("|");
					/*
					 document.formulario.razon_social.value = arreglo[0];
					 document.formulario.rut.value = arreglo[1];
					 document.formulario.giro.value = arreglo[2];
					 document.formulario.direccion_contacto.value = arreglo[3];
					 */
				}
				else
				{
					/*
					 document.formulario.razon_social.value = "";
					 document.formulario.rut.value = "";
					 document.formulario.giro.value = "";
					 document.formulario.direccion_contacto.value = "";
					 */
				}
			}
		};
		http.send(null);
	}

	function CheckCodigo()
	{
		campo = document.getElementById("codigo_asunto")
		asunto = campo.value;
		var http = getXMLHTTP();

		http.open('get', 'ajax.php?accion=check_codigo_asunto&codigo_asunto=' + asunto);
		http.onreadystatechange = function()
		{
			if (http.readyState == 4)
			{
				var response = http.responseText;
				var update = new Array();
				if (response.indexOf('OK') == -1 && response.indexOf('NO') == -1)
				{
					alert(response);
				}
				else
				{
					if (response.indexOf('NO') != -1)
					{
						alert("<?php echo __('El código ingresado ya se encuentra asignado a otro asunto. Por favor ingrese uno nuevo') ?>");
						campo.value = "";
						campo.focus();
					}
				}
			}
		};
		http.send(null);
	}

	function HideMonto()
	{
		div = document.getElementById("div_monto");
		div.style.display = "none";
	}

	function ShowMonto()
	{
		div = document.getElementById("div_monto");
		div.style.display = "block";
	}

	function MostrarMonto()
	{
		fc1 = document.getElementById("fc1");

		if (fc1.checked)
			HideMonto();
		else
			ShowMonto();
	}

	function MostrarFormaCobro()
	{
		cobro_independiente = document.getElementById("cobro_independiente");

		if (cobro_independiente.checked)
		{
			ShowFormaCobro();
			MostrarMonto();
		} else {
			HideFormaCobro();
			HideMonto();
		}
	}

	function HideFormaCobro()
	{
		div = document.getElementById("div_cobro");
		div.style.display = "none";
	}

	function ShowFormaCobro()
	{
		div = document.getElementById("div_cobro");
		div.style.display = "block";
	}

	function Mostrar(form)
	{
		alert(form.mensual.value);
	}

	function Contratos(codigo, id_contrato)
	{
		var div = $("div_contrato");
		var http = getXMLHTTP();

		http.open('get', 'ajax.php?accion=lista_contrato&codigo_cliente=' + codigo + '&id_contrato=' + id_contrato, false);
		http.onreadystatechange = function()
		{
			if (http.readyState == 4)
			{
				var response = http.responseText;
				div.innerHTML = response;
			}
		};
		http.send(null);
	}

	function ShowContrato(form, valor)
	{
		var tbl = $('tbl_contrato');
		var check = $(valor);
		var td = $('tbl_copiar_datos');

		if (check.checked)
		{
			tbl.style['display'] = 'inline';
			td.style['display'] = 'inline';
		}
		else
		{
			tbl.style['display'] = 'none';
			td.style['display'] = 'none';
		}
	}

	function SetearLetraCodigoSecundario()
	{
		var codigo_cliente_secundario = $('codigo_cliente_secundario').value;
		$('glosa_codigo_cliente_secundario').innerHTML = '&nbsp;&nbsp;' + codigo_cliente_secundario + '-';
	}
</script>
<!--onKeyUp="highlight(event)" onClick="highlight(event)"-->
<form name=formulario id=formulario method=post>
	<input type=hidden name=opcion value="guardar" />
	<input type=hidden name=opc_copiar value="" />
	<input type=hidden name=id_asunto value="<?php echo $asunto->fields['id_asunto'] ?>" />
	<input type="hidden" name="desde" id="desde" value="agregar_asunto" />

	<table width="90%"><tr><td align="center">
				<fieldset class="border_plomo tb_base">
					<legend><?php echo __('Datos generales') ?></legend>
					<table>
						<tr>
							<td align=right>
								<?php echo __('Código') ?>
							</td>
							<td align=left>
								<input id=codigo_asunto name=codigo_asunto <?php echo $codigo_obligatorio ? 'readonly="readonly"' : '' ?> size=10 maxlength=10 value="<?php echo $asunto->fields['codigo_asunto'] ?>" onchange="this.value = this.value.toUpperCase();<?php if (!$asunto->Loaded())
									echo "CheckCodigo();";
								?>"/>
								&nbsp;&nbsp;&nbsp;
								<?php
								echo __('Código secundario');
								if ($cliente->fields['codigo_cliente_secundario']) {
									$glosa_codigo_cliente_secundario = '&nbsp;&nbsp;' . $cliente->fields['codigo_cliente_secundario'] . '-';
								} else {
									$glosa_codigo_cliente_secundario = '&nbsp;&nbsp;';
								}
								?>

								<div id="glosa_codigo_cliente_secundario" style="width: 50px; display: inline;"><?php echo $glosa_codigo_cliente_secundario; ?></div>
<?php

if (empty($opcion)){
	$caracteres = strlen($cliente->fields['codigo_cliente']);
}
$field_codigo_asunto_secundario = substr($asunto->fields['codigo_asunto_secundario'], -$caracteres);

if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'CodigoSecundario') ) || ( method_exists('Conf', 'CodigoSecundario') && Conf::CodigoSecundario() ))) {
	echo "<input id=codigo_asunto_secundario name=codigo_asunto_secundario size='15' maxlength='6' value='" . $field_codigo_asunto_secundario . "' onchange='this.value=this.value.toUpperCase();' style='text-transform: uppercase;'/><span style='color:#FF0000; font-size:10px'>*</span>";
} else {
	if ($asunto->fields['codigo_asunto_secundario'] != ''){
		list( $codigo_cli_sec, $codigo_asunto_secundario ) = split("-", $asunto->fields['codigo_asunto_secundario']);
	}
	echo "<input id=codigo_asunto_secundario name=codigo_asunto_secundario size='15' maxlength='20' value='" . $field_codigo_asunto_secundario . "' onchange='this.value=this.value.toUpperCase();' style='text-transform: uppercase;'/><span style='font-size:10px'>(" . __('Opcional') . ")</span>";
}
?>
							</td>
						</tr>
						<?php if (UtilesApp::GetConf($sesion, 'ExportacionLedes')) { ?>
							<tr>
								<td align="right" title="<?php echo __('Código con el que el cliente identifica internamente el asunto. Es obligatorio si se desea generar un archivo en formato LEDES'); ?>">
									<?php echo __('Código de homologación'); ?>
								</td>
								<td align=left>
									<input name="codigo_homologacion" size="45" value="<?php echo $asunto->fields['codigo_homologacion']; ?>" />
								</td>
							</tr>
<?php } ?>
						<tr>
							<td align=right>
								<?php echo __('Título') ?>
							</td>
							<td align=left>
								<input name=glosa_asunto size=45 value="<?php echo $asunto->fields['glosa_asunto'] ?>" />
								<span style="color:#FF0000; font-size:10px">*</span>
							</td>
						</tr>
						<tr>
							<td align=right>
								<?php echo __('Cliente') ?>
							</td>
							<td align=left>
<?php
if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'CodigoSecundario') ) || ( method_exists('Conf', 'CodigoSecundario') && Conf::CodigoSecundario() ))) {
	echo InputId::Imprimir($sesion, "cliente", "codigo_cliente_secundario", "glosa_cliente", "codigo_cliente_secundario", $cliente->fields['codigo_cliente_secundario'], "  ", "SetearLetraCodigoSecundario(); CambioEncargadoSegunCliente(this.value);");
} else {
	echo InputId::Imprimir($sesion, "cliente", "codigo_cliente", "glosa_cliente", "codigo_cliente", $asunto->fields['codigo_cliente'] ? $asunto->fields['codigo_cliente'] : $cliente->fields['codigo_cliente'], "  ", "CambioEncargadoSegunCliente(this.value);");
}
?>
								<span style="color:#FF0000; font-size:10px">*</span>
							</td>
						</tr>
						<tr>
							<td align=right>
								<?php echo __('Idioma') ?>
							</td>
							<td align=left>
								<?php echo Html::SelectQuery($sesion, "SELECT * FROM prm_idioma", "id_idioma", $asunto->fields['id_idioma'] ? $asunto->fields['id_idioma'] : $id_idioma_default, "", "", "80"); ?>&nbsp;&nbsp;
								<?php echo __('Categoría de asunto') ?>
<?php echo Html::SelectQuery($sesion, "SELECT * FROM prm_tipo_proyecto", "id_tipo_asunto", $asunto->fields['id_tipo_asunto'], ""); ?>
							</td>
						</tr>
						<tr>
							<td align=right>
<?php echo __('Área') . ' ' . __('asunto') ?>
							</td>
							<td align=left>
<?php echo Html::SelectQuery($sesion, "SELECT id_area_proyecto, glosa FROM prm_area_proyecto ORDER BY orden", "id_area_proyecto", $asunto->fields['id_area_proyecto'], "", "", ""); ?>&nbsp;&nbsp;
							</td>
						</tr>
						<tr>
							<td align=right>
						<?php echo __('Descripción') ?>
							</td>
							<td align=left>
								<textarea name=descripcion_asunto cols="50"><?php echo $asunto->fields['descripcion_asunto'] ?></textarea>
							</td>
						</tr>
						<?php
						//if (!UtilesApp::GetConf($sesion, 'EncargadoSecundario') ) {

						echo '<tr><td align=right>';
						echo __('Usuario responsable');
						echo '</td><td align=left>';
						echo Html::SelectQuery($sesion, "SELECT usuario.id_usuario,CONCAT_WS(' ',apellido1,apellido2,',',nombre)
																				FROM usuario
																				WHERE usuario.id_usuario IN (SELECT id_usuario FROM usuario_permiso)
																				AND usuario.activo = 1
																				ORDER BY usuario.apellido1", "id_encargado", $asunto->fields['id_encargado'], "  ", "Seleccione", "200");
						if (isset($encargado_obligatorio) && $encargado_obligatorio):
							echo $obligatorio;
						endif;
						echo '</td></tr>';
						//}
						IF (UtilesApp::GetConf($sesion, 'AsuntosEncargado2')) {
							echo '<tr><td align=right>' . __('Encargado 2');
							echo '</td><td align=left>';
							echo Html::SelectQuery($sesion, "SELECT usuario.id_usuario,CONCAT_WS(' ',apellido1,apellido2,',',nombre)
																				FROM usuario
																				WHERE usuario.id_usuario IN (SELECT id_usuario FROM usuario_permiso)
																				AND usuario.activo = 1
																				ORDER BY usuario.apellido1", "id_encargado2", $asunto->fields['id_encargado2'], "", "Seleccione", "200");
							echo '</td></tr>';
						}
						?>
						<tr>
							<td align=right>
<?php echo __('Contacto solicitante') ?>
							</td>
							<td align=left>
								<input name=asunto_contacto size=30 value="<?php echo $asunto->fields[contacto] ?>" />
							</td>
						</tr>
						<tr>
							<td align=right>
<?php echo __('Teléfono Contacto') ?>
							</td>
							<td align=left>
								<input name=fono_contacto value="<?php echo $asunto->fields[fono_contacto] ?>" />
								&nbsp;&nbsp;&nbsp;
<?php echo __('E-mail contacto') ?>
								<input name=email_contacto value="<?php echo $asunto->fields[email_contacto] ?>" />
							</td>
						</tr>
						<tr>
							<td align=right>
								<label for="activo"><?php echo __('Activo') ?></label>
							</td>
							<td align=left>
								<input type=checkbox name=activo id=activo value="1" <?php echo $asunto->fields['activo'] == 1 ? "checked" : "" ?> <?php echo!$asunto->Loaded() ? 'checked' : '' ?> />
								&nbsp;&nbsp;&nbsp;
								<label for="cobrable"><?php echo __('Cobrable') ?></label>
								<input  type=checkbox name=cobrable id=cobrable value="1" <?php echo $asunto->fields['cobrable'] == 1 ? "checked" : "" ?><?php echo!$asunto->Loaded() ? 'checked' : '' ?>  />
								&nbsp;&nbsp;&nbsp;
								<label for="actividades_obligatorias"><?php echo __('Actividades obligatorias') ?></label>
								<input type=checkbox id=actividades_obligatorias name=actividades_obligatorias value="1" <?php echo $asunto->fields['actividades_obligatorias'] == 1 ? "checked" : "" ?> />
							</td>
						</tr>
					</table>
				</fieldset>
				<!--
					<fieldset>
						<legend>Información de cobro</legend>
					<table>
					<tr>
						<td align=right>
							Razón Social
						</td>
						<td align=left>
							<input name=razon_social value="<?php echo $asunto->fields[razon_social] ?>" />
						</td>
					</tr>
					<tr>
						<td align=right>
<?php echo __('RUT') ?>
						</td>
						<td align=left>
							<input name=rut value="<?php echo $asunto->fields[rut] ?>" />
						</td>
					</tr>
					<tr>
						<td align=right>
							Dirección
						</td>
						<td align=left>
							<input name=direccion_contacto value="<?php echo $asunto->fields[direccion_contacto] ?>" />
						</td>
					</tr>
					<tr>
						<td align=right>
							Giro
						</td>
						<td align=left>
							<input name=giro value="<?php echo $asunto->fields[giro] ?>" />
						</td>
					</tr>
					</table>
					</fieldset>
				-->
				<br>
				<?php
				if ($asunto->fields['id_contrato'] && ($asunto->fields['id_contrato'] != $cliente->fields['id_contrato']) && ($asunto->fields['codigo_cliente'] == $cliente->fields['codigo_cliente'])) {
					$checked = 'checked';
				} else {
					$checked = '';
				}

				$hide_areas = '';
				$params_asuntos_array['codigo_permiso'] = 'SASU';
				$permisos_asuntos = $sesion->usuario->permisos->Find('FindPermiso', $params_asuntos_array); #tiene permiso de admin de asuntos
				if ($permisos_asuntos->fields['permitido']) {
					$hide_areas = 'style="display: none;"';
				}
				?>
				<table width='100%' cellspacing='0' cellpadding='0'>
					<tr>
						<td  <?php echo $hide_areas; ?> >
							<input type="checkbox" name='cobro_independiente' id='cobro_independiente' onclick="ShowContrato(this.form, this)" value='1' <?php echo $checked ?> >&nbsp;&nbsp;
							<label for="cobro_independiente"><?php echo __('Se cobrará de forma independiente') ?></label>
						</td>
						<td id='tbl_copiar_datos' style='display:<?php echo $checked != '' ? 'inline' : 'none' ?>;'>
							&nbsp;
						</td>
					</tr>
				</table>
				<br>
				<div  id='tbl_contrato' style="display:<?php echo $checked != '' ? 'inline-table' : 'none' ?>;">

<?php if (!$permisos_asuntos->fields['permitido']) {
	require_once Conf::ServerDir() . '/interfaces/agregar_contrato.php';
} ?>


				</div>
				<br>
				<fieldset class="border_plomo tb_base">
					<legend><?php echo __('Alertas') . ' ' . __('Asunto') ?></legend>
					<p>&nbsp;<?php echo __('El sistema enviará un email de alerta al encargado si se superan estos límites:') ?></p>
					<table>
						<tr>
							<td align=right>
								<input name="asunto_limite_hh" value="<?php echo $asunto->fields['limite_hh'] ? $asunto->fields['limite_hh'] : '0' ?>" title="<?php echo __('Total de Horas') ?>" size=5 />
							</td>
							<td colspan=3 align=left>
								<span title="<?php echo __('Total de Horas') ?>"><?php echo __('Límite de horas') ?></span>
							</td>
							<td align=right>
								<input name="asunto_limite_monto" value="<?php echo $asunto->fields['limite_monto'] ? $asunto->fields['limite_monto'] : '0' ?>" title="<?php echo __('Valor Total según Tarifa Hora Hombre') ?>" size=5 />
							</td>
							<td colspan=3 align=left>
								<span title="<?php echo __('Valor Total según Tarifa Hora Hombre') ?>"><?php echo __('Límite de monto') ?></span>
							</td>
						</tr>
						<tr>
							<td align=right>
								<input name="asunto_alerta_hh" value="<?php echo $asunto->fields['alerta_hh'] ? $asunto->fields['alerta_hh'] : '0' ?>" title="<?php echo __('Total de Horas en trabajos no cobrados') ?>" size=5 />
							</td>
							<td colspan=3 align=left>
								<span title="<?php echo __('Total de Horas en trabajos no cobrados') ?>"><?php echo __('horas no cobradas') ?></span>
							</td>
							<td align=right>
								<input name="asunto_alerta_monto" value="<?php echo $asunto->fields['alerta_monto'] ? $asunto->fields['alerta_monto'] : '0' ?>" title="<?php echo __('Valor Total según Tarifa Hora Hombre en trabajos no cobrados') ?>" size=5 />
							</td>
							<td colspan=3 align=left>
								<span title="<?php echo __('Valor Total según Tarifa Hora Hombre en trabajos no cobrados') ?>"><?php echo __('monto según horas no cobradas') ?></span>
							</td>
						</tr>
					</table>
				</fieldset>
				<br>

				<!-- GUARDAR -->
				<fieldset class="border_plomo tb_base">
					<legend><?php echo __('Guardar datos') ?></legend>
					<table>
						<tr>
							<td colspan=6 align="center">
						<?php
						if (UtilesApp::GetConf($sesion, 'RevisarTarifas')) {
							$funcion_validar = "return RevisarTarifas('id_tarifa', 'id_moneda', this.form, false);";
						} else {
							$funcion_validar = "return Validar(this.form);";
						}
						?>
								<input type='button' class='btn' value="<?php echo __('Guardar'); ?>" onclick="<?php echo $funcion_validar; ?>" />
							</td>
						</tr>
<?php
if ($motivo == "agregar_proyecto") {
	?>
							<!--  <tr>
									&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

										<td width="680" align=right><img src="<?php echo Conf::ImgDir() ?>/volver.gif" border="0" /> <a href="#" onclick="Volver();" title="Volver">Volver Atr&aacute;s</a></td>
								</tr>
							-->
	<?php
}
?>
					</table>
				</fieldset>
			</td></tr></table>
	<br>
</form>

<script>

	//Contratos('<?php echo $asunto->fields['codigo_cliente']; ?>','<?php echo $asunto->fields['id_contrato']; ?>');
	var form = $('formulario');
	ShowContrato(form, 'cobro_independiente');

	jQuery('document').ready(function() {

		jQuery('#codigo_cliente, #codigo_cliente, #codigo_cliente, #codigo_cliente').change(function() {
			CambioEncargadoSegunCliente(jQuery(this).val());
		});
	});
	function CambioEncargadoSegunCliente(idcliente) {
		var CopiarEncargadoAlAsunto =<?php echo (UtilesApp::GetConf($sesion, "CopiarEncargadoAlAsunto") ? '1' : '0'); ?>;
		var UsuarioSecundario =<?php echo (UtilesApp::GetConf($sesion, 'EncargadoSecundario') ? '1' : '0' ); ?>;
		var ObligatorioEncargadoSecundarioAsunto =<?php echo (UtilesApp::GetConf($sesion, 'ObligatorioEncargadoSecundarioAsunto') ? '1' : '0' ); ?>;
		jQuery('#id_usuario_secundario').removeAttr('disabled');
		jQuery('#id_usuario_responsable').removeAttr('disabled');
		jQuery.post('../ajax.php', {accion: 'busca_encargado_por_cliente', codigobuscado: idcliente}, function(data) {
			if (window.console)
				console.debug(data);
			var ladata = data.split('|');
			jQuery('#id_usuario_responsable').attr({'disabled': ''}).val(ladata[0]);
			if (ladata[1] && jQuery('#id_usuario_secundario option[value=' + ladata[1] + ']').length > 0) {
				if (UsuarioSecundario)
					jQuery('#id_usuario_secundario').attr({'disabled': ''}).val(ladata[1]);
			} else {
				if (ladata[2])
					jQuery('#id_usuario_secundario').append('<option value="' + ladata[1] + '" selected="selected">' + ladata[2] + '</option>').attr({'disabled': ''}).val(ladata[1]);
				;
			}

			jQuery('#id_usuario_responsable').removeAttr('disabled');
			if (CopiarEncargadoAlAsunto) {
				jQuery('#id_usuario_responsable').attr({'disabled': 'disabled'});
				if (UsuarioSecundario)
					jQuery('#id_usuario_secundario').attr({'disabled': 'disabled'});
			} else if (ObligatorioEncargadoSecundarioAsunto) {

				if (UsuarioSecundario)
					jQuery('#id_usuario_secundario').removeAttr('disabled');
			}


			jQuery('#id_usuario_responsable, #id_usuario_secundario').removeClass('loadingbar');
		});
		jQuery('#id_usuario_responsable, #id_usuario_secundario').addClass('loadingbar');
	}

</script>
<?php echo InputId::Javascript($sesion) ?>

<?php
$pagina->PrintBottom($popup);

function EnviarEmail($asunto) {
	global $sesion;

	$glosa = $asunto->fields['glosa_asunto'];
	$codigo = $asunto->fields['codigo_asunto'];
	$cod_cliente = $asunto->fields['codigo_cliente'];
	$desc = $asunto->fields['descripcion_asunto'];

	$query = "SELECT glosa_cliente FROM cliente WHERE codigo_cliente='$cod_cliente'";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	list($cliente) = mysql_fetch_array($resp);

	if (method_exists('Conf', 'GetConf'))
		$MailSistema = Conf::GetConf($sesion, 'MailSistema');
	else if (method_exists('Conf', 'MailSistema'))
		$MailSistema = Conf::MailSistema();

	if (UtilesApp::GetConf($sesion, 'MailAsuntoNuevoATodosLosAdministradores')) {
		$query = "SELECT usuario.nombre,usuario.email FROM usuario LEFT JOIN usuario_permiso USING( id_usuario ) WHERE usuario.activo=1 AND usuario_permiso.codigo_permiso='ADM'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		while (list($nombre, $email) = mysql_fetch_array($resp)) {
			$from = Conf::AppName();
			$headers = "From: Time & Billing <" . $MailSistema . ">" . "\r\n" .
							"Reply-To: " . $MailSistema . "\r\n" .
							'X-Mailer: PHP/' . phpversion();
			$mensaje = "Estimado(a) $nombre,\nse ha agregado el asunto $glosa ($codigo) al cliente $cliente en el sistema de Time & Billing.\nDescripción: $desc\nRecuerda refrescar las listas de la aplicación local Time & Billing.";
			Utiles::Insertar($sesion, "Nuevo asunto - " . Conf::AppName(), $mensaje, $email, $nombre);
		}
	} else {
		$from = Conf::AppName();
		$headers = "From: Time & Billing <" . $MailSistema . ">" . "\r\n" .
						"Reply-To: " . $MailSistema . "\r\n" .
						'X-Mailer: PHP/' . phpversion();
		$mensaje = "Estimado(a) Admin,\nse ha agregado el asunto $glosa ($codigo) al cliente $cliente en el sistema de Time & Billing.\nDescripción: $desc\nRecuerda refrescar las listas de la aplicación local Time & Billing.";
		$email = UtilesApp::GetConf($sesion, 'MailAdmin');
		Utiles::Insertar($sesion, "Nuevo asunto - " . Conf::AppName(), $mensaje, $email, $nombre);
	}
}
?>
