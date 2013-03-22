<?php
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../app/classes/Contrato.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Pagina.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/../fw/classes/Buscador.php';
require_once Conf::ServerDir() . '/../fw/classes/Html.php';
require_once Conf::ServerDir() . '/../app/classes/Cliente.php';
require_once Conf::ServerDir() . '/../app/classes/InputId.php';
require_once Conf::ServerDir() . '/../app/classes/Debug.php';
require_once Conf::ServerDir() . '/../app/classes/Moneda.php';
require_once Conf::ServerDir() . '/../app/classes/Tarifa.php';
require_once Conf::ServerDir() . '/../app/classes/TarifaTramite.php';
require_once Conf::ServerDir() . '/../app/classes/Funciones.php';
require_once Conf::ServerDir() . '/../app/classes/Cobro.php';
require_once Conf::ServerDir() . '/../app/classes/CobroPendiente.php';
require_once Conf::ServerDir() . '/../app/classes/Archivo.php';
require_once Conf::ServerDir() . '/../app/classes/ContratoDocumentoLegal.php';
require_once Conf::ServerDir() . '/../app/classes/UtilesApp.php';

//Tooltips para las modalidades de cobro.
$tip_tasa = __("En esta modalidad se cobra hora a hora. Cada profesional tiene asignada su propia tarifa para cada asunto.");
$tip_suma = __("Es un único monto de dinero para el asunto. Aquí interesa llevar la cuenta de HH para conocer la rentabilidad del proyecto. Esta es la única modalida de ") . __("cobro") . __(" que no puede tener límites.");
$tip_retainer = __("El cliente compra un número de HH. El límite puede ser por horas o por un monto.");
$tip_retainer_usuarios = __("Si usted selecciona usuarios en esta lista, las horas de estos usuarios se van a descontar de las horas retainer con preferencia");
$tip_proporcional = __("El cliente compra un número de horas, el exceso de horas trabajadas se cobra proporcional a la duración de cada trabajo.");
$tip_flat = __("El cliente acuerda cancelar un <strong>monto fijo</strong> por atender todos los trabajos de este asunto. Puede tener límites por HH o monto total");
$tip_cap = __("Cap");
$tip_hitos = __("Hitos");
$tip_escalonada = __("Escalonada");
$tip_honorarios = __("Solamente lleva la cuenta de las HH profesionales. Al terminar el proyecto se puede cobrar eventualmente.");
$tip_mensual = __("El cobro se hará de forma mensual.");
$tip_tarifa_especial = __("Al ingresar una nueva tarifa, esta se actualizará automáticamente.");
$tip_subtotal = __("El monto total ") . __("del cobro") . __(" hasta el momento sin incluir descuentos.");
$tip_descuento = __("El monto del descuento.");
$tip_total = __("El monto total ") . __("del cobro") . __(" hasta el momento incluidos descuentos.");
$tip_actualizar = __("Actualizar los montos");
$tip_refresh = __("Actualizar a cambio actual");

$color_par = "#f0f0f0";
$color_impar = "#ffffff";

$sesion = new Sesion(array('DAT'));
$archivo = new Archivo($sesion);

$query_permiso_tarifa = "SELECT count(*)
							FROM usuario_permiso
							WHERE id_usuario = '{$sesion->usuario->fields['id_usuario']}'
							AND codigo_permiso = 'TAR' ";

$resp_permiso_tarifa = mysql_query($query_permiso_tarifa, $sesion->dbh) or Utiles::errorSQL($query_permiso_tarifa, __FILE__, __LINE__, $sesion->dbh);
list( $cantidad_permisos ) = mysql_fetch_array($resp_permiso_tarifa);

$tarifa_permitido = ($cantidad_permisos > 0);

$validaciones_segun_config = method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'ValidacionesCliente');
$obligatorio = '<span class="req">*</span>';
$modulo_retribuciones_activo = Conf::GetConf($sesion, 'UsarModuloRetribuciones');

if (!defined('HEADERLOADED'))
	$addheaderandbottom = true;
if ($addheaderandbottom || ($popup && !$motivo)) {
	$pagina = new Pagina($sesion);
	$show = 'inline';

	function TTip($texto) {
		return "onmouseover=\"ddrivetip('$texto');\" onmouseout=\"hideddrivetip('$texto');\"";
	}

	$contrato = new Contrato($sesion);
	if ($id_contrato > 0) {
		if (!$contrato->Load($id_contrato)) {
			$pagina->FatalError(__('Código inválido'));
		}

		$cobro = new Cobro($sesion);
	}


	if ($contrato->fields['codigo_cliente'] != '') {
		$cliente = new Cliente($sesion);
		$cliente->LoadByCodigo($contrato->fields['codigo_cliente']);
	}

	if ($contrato->fields['id_moneda'] == '') {
		$contrato->fields['id_moneda'] = $cliente->fields['id_moneda'];
	}

	if ($id_contrato) {
		$pagina->titulo = __('Editar Contrato');
	} else {
		$pagina->titulo = __('Agregar Contrato');
	}
} else {
	$show = 'none';
}

$contrato_defecto = new Contrato($sesion);
if (!empty($cliente->fields["id_contrato"])) {
	$contrato_defecto->Load($cliente->fields["id_contrato"]);
}

$validaciones_segun_config = UtilesApp::GetConf($sesion, 'ValidacionesCliente');
$obligatorio = '<span class="req">*</span>';

if (isset($cargar_datos_contrato_cliente_defecto) && !empty($cargar_datos_contrato_cliente_defecto)) {
	$contrato->fields = $cargar_datos_contrato_cliente_defecto;
}
 
// CONTRATO GUARDA
if ($opcion_contrato == "guardar_contrato" && $popup && !$motivo) {
	$enviar_mail = 1;
	if ($forma_cobro != 'TASA' && $forma_cobro != 'HITOS' && $forma_cobro != 'ESCALONADA' && $monto == 0) {
		$pagina->AddError(__('Ud. ha seleccionado forma de ') . __('cobro') . ': ' . $forma_cobro . ' ' . __('y no ha ingresado monto'));
		$val = true;
	} else if ($forma_cobro == 'TASA') {
		$monto = '0';
	}

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

	if ($usuario_responsable_obligatorio && empty($id_usuario_responsable) or $id_usuario_responsable == '-1') {
		$pagina->AddError(__("Debe ingresar el") . " " . __('Encargado Principal'));
		$val = true;
	}

	if (UtilesApp::GetConf($sesion, 'EncargadoSecundario') && (empty($id_usuario_secundario) or $id_usuario_secundario == '-1')) {
		$pagina->AddError(__("Debe ingresar el") . " " . __('Encargado Secundario'));
		$val = true;
	}
	
	$contrato->Fill($_REQUEST, true);
	
	if ($contrato->Write()) {
		// cobros pendientes
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
			if (empty($hito_monto_estimado[$i])) {
				continue;
			}
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

			if (UtilesApp::GetConf($sesion, 'EncargadoSecundario')) {
				mysql_query("UPDATE cliente SET id_usuario_encargado = '" .
						((!empty($id_usuario_secundario) && $id_usuario_secundario != -1 ) ? $id_usuario_secundario : "NULL") .
						"' WHERE id_contrato = " . $contrato->fields['id_contrato'], $sesion->dbh);
			}
		}
		$pagina->AddInfo(__('Contrato guardado con éxito'));
	} else {
		$pagina->AddError($contrato->error);
	}
}

$tarifa = new Tarifa($sesion);
$tramite_tarifa = new TramiteTarifa($sesion);
$tarifa_default = $tarifa->SetTarifaDefecto();
$tramite_tarifa_default = $tramite_tarifa->SetTarifaDefecto();

$idioma_default = $contrato->IdiomaPorDefecto($sesion);

if (empty($tarifa_flat) && !empty($contrato->fields['id_tarifa'])) {
	$tarifa->Load($contrato->fields['id_tarifa']);
	$valor_tarifa_flat = $tarifa->fields['tarifa_flat'];
} else if (!empty($tarifa_flat) && $tipo_tarifa != 'flat') {
	$valor_tarifa_flat = null;
} else {
	$valor_tarifa_flat = $tarifa_flat;
}

if ($addheaderandbottom || ($popup && !$motivo)) {
	$pagina->PrintTop($popup);
}

$contrato->CargarEscalonadas();

$rango1 = ( $contrato->escalonadas[1]['tiempo_inicial'] ?
				$contrato->escalonadas[1]['tiempo_inicial'] : '0' ) . ' - ' . ( $contrato->escalonadas[1]['tiempo_final'] ?
				$contrato->escalonadas[1]['tiempo_final'] : '' );
$rango2 = ( $contrato->escalonadas[2]['tiempo_inicial'] ?
				$contrato->escalonadas[2]['tiempo_inicial'] : '0' ) . ' - ' . ( $contrato->escalonadas[2]['tiempo_final'] ?
				$contrato->escalonadas[2]['tiempo_final'] : '' );
$rango3 = ( $contrato->escalonadas[3]['tiempo_inicial'] ?
				$contrato->escalonadas[3]['tiempo_inicial'] : '0' ) . ' - ' . ( $contrato->escalonadas[3]['tiempo_final'] ?
				$contrato->escalonadas[3]['tiempo_final'] : '' );
$ultimo_rango = ( $contrato->escalonadas[$contrato->escalonadas['num']]['tiempo_inicial'] ?
				$contrato->escalonadas[$contrato->escalonadas['num']]['tiempo_inicial'] : '0' ) . ' - ' . ( $contrato->escalonadas[$contrato->escalonadas['num']]['tiempo_final'] ?
				$contrato->escalonadas[$contrato->escalonadas['num']]['tiempo_final'] : '' );

$div_show = ($popup && !$motivo); // aqui es popup de contrato directo agregar.

$query_count = "SELECT COUNT(usuario.id_usuario)
				FROM usuario JOIN usuario_permiso USING(id_usuario)
				WHERE codigo_permiso='SOC'";
$resp = mysql_query($query_count, $sesion->dbh);
list($cant_encargados) = mysql_fetch_array($resp);
?>
<script type="text/javascript">
	function ValidarContrato(form)
	{
		if(!form) var form = jQuery('[name="formulario"]').get(0);
			 


<?php if (UtilesApp::GetConf($sesion, 'NuevoModuloFactura')) { ?>
			if (!validar_doc_legales(true)){
				return false;
			}
<?php } ?>

<?php if ($validaciones_segun_config) { ?>
			// DATOS FACTURACION

			if(!form.factura_rut.value)
			{
				alert("<?php echo __('Debe ingresar el') . ' ' . __('RUT') . ' ' . __('del cliente') ?>");
				form.factura_rut.focus();
				return false;
			}

			if(!form.factura_razon_social.value)
			{
				alert("<?php echo __('Debe ingresar la razón social del cliente') ?>");
				form.factura_razon_social.focus();
				return false;
			}

			if(!form.factura_giro.value)
			{
				alert("<?php echo __('Debe ingresar el giro del cliente') ?>");
				form.factura_giro.focus();
				return false;
			}

			if(!form.factura_direccion.value)
			{
				alert("<?php echo __('Debe ingresar la dirección del cliente') ?>");
				form.factura_direccion.focus();
				return false;
			}
	<?php if (UtilesApp::existecampo('factura_ciudad', 'contrato', $sesion)) { ?>
								if(!form.factura_ciudad.value)
								{
									alert("<?php echo __('Debe ingresar la cuidad del cliente') ?>");
									form.factura_cuidad.focus();
									return false;
								}
	<?php } ?>

	<?php if (UtilesApp::existecampo('factura_comuna', 'contrato', $sesion)) { ?>
								if(!form.factura_comuna.value)
								{
									alert("<?php echo __('Debe ingresar la comuna del cliente') ?>");
									form.factura_comuna.focus();
									return false;
								}
	<?php } ?>

							if(form.id_pais.options[0].selected == true)
							{
								alert("<?php echo __('Debe ingresar el pais del cliente') ?>");
								form.id_pais.focus();
								return false;
							}

							if(!form.cod_factura_telefono.value)
							{
								alert("<?php echo __('Debe ingresar el codigo de area del teléfono') ?>");
								form.cod_factura_telefono.focus();
								return false;
							}

							if(!form.factura_telefono.value)
							{
								alert("<?php echo __('Debe ingresar el número de telefono') ?>");
								form.factura_telefono.focus();
								return false;
							}

							// SOLICITANTE
							if(form.titulo_contacto.options[0].selected == true)
							{
								alert("<?php echo __('Debe ingresar el titulo del solicitante') ?>");
								form.titulo_contacto.focus();
								return false;
							}

							if(!form.nombre_contacto.value)
							{
								alert("<?php echo __('Debe ingresar el nombre del solicitante') ?>");
								form.nombre_contacto.focus();
								return false;
							}

							if(!form.apellido_contacto.value)
							{
								alert("<?php echo __('Debe ingresar el apellido del solicitante') ?>");
								form.apellido_contacto.focus();
								return false;
							}

							if(!form.fono_contacto_contrato.value)
							{
								alert("<?php echo __('Debe ingresar el teléfono del solicitante') ?>");
								form.fono_contacto_contrato.focus();
								return false;
							}

							if(!form.email_contacto_contrato.value)
							{
								alert("<?php echo __('Debe ingresar el email del solicitante') ?>");
								form.email_contacto_contrato.focus();
								return false;
							}

							if(!form.direccion_contacto_contrato.value)
							{
								alert("<?php echo __('Debe ingresar la dirección de envío del solicitante') ?>");
								form.direccion_contacto_contrato.focus();
								return false;
							}

							// DATOS DE TARIFICACION
							if(!(form.tipo_tarifa[0].checked || form.tipo_tarifa[1].checked))
							{
								alert("<?php echo __('Debe seleccionar un tipo de tarifa') ?>");
								form.tipo_tarifa[0].focus();
								return false;
							}

							/* Revisa antes de enviar, que se haya escrito un monto si seleccionó tarifa plana */

							if( form.tipo_tarifa[1].checked && form.tarifa_flat.value.length == 0 )
							{
								alert("<?php echo __('Ud. ha seleccionado una tarifa plana pero no ha ingresado el monto.') ?>");
								form.tarifa_flat.focus();
								return false;
							}

							/*if(!form.id_moneda.options[0].selected == true)
			{
				alert("<?php echo __('Debe seleccionar una moneda para la tarifa') ?>");
				form.id_moneda.focus();
				return false;
			}*/

							if(!$$('[name="forma_cobro"]').any(function(elem){return elem.checked;}))
							{
								alert("<?php echo __('Debe seleccionar una forma de cobro') . ' ' . __('para la tarifa') ?>");
								form.forma_cobro[0].focus();
								return false;
							}

							if($('fc7').checked){
								if($$('[id^="fila_hito_"]').any(function(elem){return !validarHito(elem, true);})){
									return false;
								}
								if(!$$('[id^="hito_monto_"]').any(function(elem){return Number(elem.value)>0;})){
									alert("<?php echo __('Debe ingresar al menos un hito válido') ?>");
									$('hito_descripcion_1').focus();
									return false;
								}
							}
							/*
			if(!form.opc_moneda_total.value)
			{
				alert("<?php echo __('Debe seleccionar una moneda para mostrar el total') ?>");
				form.opc_moneda_total.focus();
				return false;
			}*/

							if(!form.observaciones.value)
							{
								alert("<?php echo __('Debe ingresar un detalle para la cobranza') ?>");
								form.observaciones.focus();
								return false;
							}

<?php } ?>
<?php if (UtilesApp::GetConf($sesion, 'EncargadoSecundario')) { ?>
							if ($('id_usuario_secundario').value == '-1')
							{
								alert("<?php echo __("Debe ingresar el") . " " . __('Encargado Secundario') ?>");
	                    
								jQuery('#id_usuario_secundario').removeAttr('disabled').focus();
								return false;
							}
<?php } ?>

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
						if( rut.length >= 6 ) {
							var rut2 = rut.substr(rut.length-6,3);
							var rut1 = rut.substr(0,rut.length-6);
						} else {
							var rut2 = rut.substr(0,rut.length-3);
						}
						if(rut.length > 6)
							var rut = rut1 + '.' + rut2 + '.' + rut3 + '-' + dv;
						else if( rut.length > 3 )
							var rut = rut2 + '.' + rut3 + '-' + dv;
						else
							var rut = rut3 + '-' + dv;
                
						$('rut').value = rut;
					}

					function MuestraOculta(divID)
					{
						var divArea = jQuery('#'+divID);
						var divAreaImg = jQuery('#'+divID+'_img');

		
						if(divArea.is(':visible')) {
							divArea.slideUp();
							divAreaImg.innerHTML = "<img src='//static.thetimebilling.com/images/mas.gif' border='0' title='Desplegar'>";
						} else	{
							divArea.slideDown();
							divAreaImg.innerHTML = "<img src='//static.thetimebilling.com/images/menos.gif' border='0' title='Ocultar'>";
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
						jQuery("#div_forma_cobro").css('width','400px').hide();
						jQuery("#div_monto").hide();
						jQuery("#div_horas").show();
						jQuery("#div_fecha_cap").hide();
						jQuery("#div_escalonada").hide();
						jQuery("#tabla_hitos").hide();
						jQuery("#span_monto").show();

					}
					function ShowFlatFee()
					{
						jQuery("#div_forma_cobro").css('width','400px').show();
						jQuery("#div_monto").show();
						jQuery("#div_horas").hide();
						jQuery("#div_fecha_cap").hide();
						jQuery("#div_escalonada").hide();
						jQuery("#tabla_hitos").hide();
						jQuery("#span_monto").show();

					}
					function ShowRetainer()
					{
		
						jQuery("#div_forma_cobro").css('width','400px').show();
						jQuery("#div_monto").show();
						jQuery("#div_horas").show();
						jQuery("#div_fecha_cap").hide();
						jQuery("#div_escalonada").hide();
						jQuery("#tabla_hitos").hide();
						jQuery("#span_monto").show();
						jQuery("#div_retainer_usuarios").css('display','inline').show();
               
					}
					function ShowProporcional()
					{
						jQuery("#div_forma_cobro").css('width','400px').show();
						jQuery("#div_monto").show();
						jQuery("#div_horas").show();
						jQuery("#div_fecha_cap").hide();
						jQuery("#div_escalonada").hide();
						jQuery("#tabla_hitos").hide();
						jQuery("#span_monto").show();
						jQuery("#div_retainer_usuarios").css('display','inline').hide();


					}
					function ShowCap()
					{
		
						jQuery("#div_forma_cobro").css('width','400px').show();
						jQuery("#div_monto").show();
						jQuery("#div_horas").hide();
						jQuery("#div_fecha_cap").show();
						jQuery("#div_escalonada").hide();
						jQuery("#tabla_hitos").hide();
						jQuery("#span_monto").show();
						jQuery("#div_retainer_usuarios").css('display','inline').hide();
              
  
					}
					function ShowHitos()
					{
						jQuery("#div_forma_cobro").css('width','91%').show();
						jQuery("#div_monto").show();
						jQuery("#div_horas").hide();
						jQuery("#div_fecha_cap").hide();
						jQuery("#div_escalonada").hide();
						jQuery("#tabla_hitos").show();
						jQuery("#id_moneda_monto").show();
						jQuery("#span_monto").hide();
						jQuery("#div_retainer_usuarios").css('display','inline').hide();
            

					}
					function ShowEscalonada()
					{
						jQuery("#div_forma_cobro").css('width','730px').show();
						jQuery("#div_monto").hide();
						jQuery("#div_horas").hide();
						jQuery("#div_fecha_cap").hide();
						jQuery("#div_escalonada").show();
						jQuery("#tabla_hitos").hide();
						jQuery("#span_monto").hide();
						jQuery("#div_retainer_usuarios").css('display','inline').hide();
                

					}
					function ActualizaRango(desde, cant){
						var aplicar = parseInt(desde.substr(-1,1));
						var ini = 0;
						num_escalas = (document.getElementsByName('esc_tiempo[]')).length;
						for( var i = aplicar; i< num_escalas; i++){
			
							if( i > 1){
								ini = 0;
								for( var j = i; j > 1; j-- ){
									ini += parseFloat(document.getElementById('esc_tiempo_'+(j-1)).value);
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
	
					function setear_valores_escalon( donde, desde, tiempo, tipo, id_tarifa, monto, id_moneda, descuento ){
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
									alert('No puede agregar un escalón nuevo, si no ha llenado los datos del escalon actual');
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
	
					function ActualizarFormaCobro(laID) {
		
						if(!laID) {
							if(jQuery("#fc1").is(':checked')) laID='fc1';
			
							else if(jQuery("#fc2").is(':checked'))
								laID='fc2';
							else if(jQuery("#fc3").is(':checked'))
								laID='fc3';
							else if(jQuery("#fc5").is(':checked'))
								laID='fc5';
							else if(jQuery("#fc6").is(':checked'))
								laID='fc6';
							else if(jQuery("#fc7").is(':checked'))
								laID='fc7';
							else if(jQuery("#fc8").is(':checked'))
								laID='fc8';
						}
		   
						jQuery("#div_forma_cobro").css({'width':'400px','margin-left':'21%'}).hide();
						jQuery("#div_retainer_usuarios").css('display','inline').hide();
						jQuery("#div_monto").hide();
						jQuery("#div_horas").hide();
						jQuery("#span_monto").hide();
						jQuery("#div_fecha_cap").hide();
						jQuery("#div_escalonada").hide();
						jQuery("#tabla_hitos").hide();
						jQuery("#id_moneda_monto").show();
		 
		
						if(laID=="fc1") {	//ShowTHH();
							jQuery("#div_horas").show();
							jQuery("#span_monto").show();
							jQuery("#divthh").fadeTo('fast',1);
						} else if(laID=="fc2") {	    //ShowRetainer();
							jQuery("#div_forma_cobro").css({'width':'400px','margin-left':'21%'}).show();
							jQuery("#div_monto").show();
							jQuery("#div_horas").show();
							jQuery("#span_monto").show();
							jQuery("#divthh").fadeTo('fast',1);
							jQuery("#div_retainer_usuarios").css('display','inline').show();
		       
						} else if(laID=="fc3")	{   //ShowFlatFee();
							jQuery("#div_forma_cobro").css({'width':'400px','margin-left':'21%'}).show();
							jQuery("#div_monto").show();
							jQuery("#span_monto").show();
							jQuery("#divthh").fadeTo('slow',0.2);
						} else if(laID=="fc5")	{   //ShowCap();
							jQuery("#div_forma_cobro").css({'width':'400px','margin-left':'21%'}).show();
							jQuery("#div_monto").show();
							jQuery("#span_monto").show();
							jQuery("#div_fecha_cap").show();
							jQuery("#divthh").fadeTo('fast',1);
		      
						} else if(laID=="fc6")	{   //ShowProporcional();
							jQuery("#div_forma_cobro").css({'width':'400px','margin-left':'21%'}).show();
							jQuery("#div_monto").show();
							jQuery("#span_monto").show();
							jQuery("#div_horas").show();
							jQuery("#divthh").fadeTo('fast',1);
						} else if(laID=="fc7") {	//ShowHitos();
							jQuery("#div_forma_cobro").css({'width':'500px','margin-left':'21%'}).show();
							jQuery("#div_monto").show();
							jQuery("#divthh").fadeTo('slow',0.2);
							jQuery("#tabla_hitos").slideDown();
		  
						} else if(laID=="fc8") {//	ShowEscalonada();
							jQuery("#div_forma_cobro").css({'width':'720px','margin-left':'5%'}).show();
							jQuery("#divthh").fadeTo('slow',0.2);
							jQuery("#div_escalonada").slideDown();
		    
						}
		
						/*if(jQuery("#fc1").is(':checked'))
			ShowTHH();
		else if(jQuery("#fc2").is(':checked'))
			ShowRetainer();
		else if(jQuery("#fc3").is(':checked'))
			ShowFlatFee();
		else if(jQuery("#fc5").is(':checked'))
			ShowCap();
		else if(jQuery("#fc6").is(':checked'))
			ShowProporcional();
		else if(jQuery("#fc7").is(':checked'))
			ShowHitos();
		else if(jQuery("#fc8").is(':checked'))
			ShowEscalonada();*/
					}

					function CreaTarifa(form, opcion, id_tarifa)
					{
						var form = $('formulario');
						if(opcion)
							nuovaFinestra( 'Tarifas', 600, 600, 'agregar_tarifa.php?popup=1', '' );
						else
						{
							if(!id_tarifa)
								var id_tarifa = jQuery('#id_tarifa').val();
							nuovaFinestra( 'Tarifas', 600, 600, 'agregar_tarifa.php?popup=1&id_tarifa_edicion='+id_tarifa, '' );
						}
					}

					function CreaTramiteTarifa(form, opcion, id_tramite_tarifa)
					{
						var form = $('formulario');
						if(opcion)
							nuovaFinestra( 'Trámite_Tarifas', 600, 600, 'tarifas_tramites.php?popup=1&crear=1', '' );
						else
						{
							//var id_tramite_tarifa = form.id_tramite_tarifa.value;
							if(!id_tramite_tarifa)
								var id_tramite_tarifa = jQuery('#id_tramite_tarifa').val();
							nuovaFinestra( 'Trámite_Tarifas', 600, 600, 'tarifas_tramites.php?popup=1&id_tramite_tarifa_edicion='+id_tramite_tarifa, '' );
						}
					}

					function ActualizarTarifaTramiteDesdePopup() {
						//document.getElementById('id_tramite_tarifa_holder').innerHtml = "<select name='' id=''><option>me los cagué a todos</option></select>";
						var http = getXMLHTTP();
						var url = 'ajax.php?accion=cargar_tarifas_tramites';
						var destino = 'id_tramite_tarifa';
						loading("Actualizando campo");
						http.open('get', url);
						http.onreadystatechange = function()
						{
							if(http.readyState == 4)
							{
								var response = http.responseText;
								if( response == "~noexiste" ) {
									$(destino).options.length = 0;
								} else {
									$(destino).options.length = 0;
									cuentas = response.split('//');

									for(var i=0;i<cuentas.length;i++)
									{
										valores = cuentas[i].split('|');

										var option = new Option();
										if( valores[0] == "Vacio") {
											option.value = '';
										} else {
											option.value = valores[0];
										}
										option.text = valores[1];

										try {
											$(destino).add(option);
										} 
										catch(err) {
											$(destino).add(option,null);
										}
									}
								}
								offLoading();
							}
						};
						http.send(null);
					}
	


					/*
	Desactivar contrato para no verlo en cobros. (generación)
					 */
					function InactivaContrato(alerta, opcion)
					{
						var form = $('formulario');
						if(!form) form = jQuery('[name="formulario"]').get(0);
						var activo_contrato = $('activo_contrato');
						if(!alerta)
						{
							var text_window = "<img src='<?php echo Conf::ImgDir() ?>/alerta_16.gif'>&nbsp;&nbsp;<span style='font-size:12px; color:#FF0000; text-align:center;font-weight:bold'><u><?php echo __("ALERTA") ?></u><br><br>";
							text_window += '<span style="text-align:center; font-size:11px; color:#000; "><?php echo __('Ud. está desactivando este contrato, por lo tanto este contrato no aparecerá en la lista de la generación de ') . __('cobros') ?>.</span><br>';
							text_window += '<br><table><tr>';
							text_window += '<td align="right"><span style="text-align:center; font-size:11px;color:#FF0000; "><?php echo __('¿Está seguro de desactivar este contrato?') ?>:</span></td></tr>';
							text_window += '</table>';
							Dialog.confirm(text_window,
							{
								top:150, left:290, width:400, okLabel: "<?php echo __('Aceptar') ?>", cancelLabel: "<?php echo __('Cancelar') ?>", buttonClass: "btn", className: "alphacube",
								id: "myDialogId",
								cancel:function(win){ activo_contrato.checked = true; 
									jQuery('#desactivar_contrato').remove();
									return false; },
								ok:function(win){
									jQuery('[name="formulario"]').append('<input type="hidden" value="1" id="desactivar_contrato" name="desactivar_contrato"/>');
									ValidarContrato(this.form); return true; 
									
								}
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
<?php
// numeros de cobros existentes para ver cual sigue
$query = "SELECT COUNT(*) FROM cobro_pendiente WHERE id_cobro IS NOT NULL AND id_contrato='" . $contrato->fields['id_contrato'] . "' AND hito = '0'";
$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
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
		var mes=parseInt(fecha_inicio[1]*1);
		var anio=parseInt(fecha_inicio[2]*1);
		var dia_str = '';
		var mes_str = '';
		var numero_cobro = 0;

		for(i=1;i<=cant_cobros;i++)
		{
			var dia=parseInt(fecha_inicio[0]*1);
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
			numero_cobro= <?php echo $numero_cobro ?>+i;
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
		borrar.innerHTML="<img src='<?php echo Conf::ImgDir() ?>/eliminar.gif' style='cursor:pointer' onclick='eliminarFila(this.parentNode.parentNode.rowIndex);' />";
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
<?php
$query = "SELECT id_moneda,simbolo FROM prm_moneda";
$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
while (list($id_moneda_tabla, $simbolo_tabla) = mysql_fetch_array($resp)) {
	//echo $id_moneda_tabla;
	?>
		simbolo[<?php echo $id_moneda_tabla ?>] = "<?php echo $simbolo_tabla ?>";
	<?php
}
?>
	function actualizarMoneda()
	{
		var id_moneda=$('id_moneda_monto').value;
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
					alert( "Usted no tiene cuentas en este banco." );
				else
				{
					$(destino).options.length = 0;
					cuentas = response.split('//');

					for(var i=0;i<cuentas.length;i++)
					{
						valores = cuentas[i].split('|');

						var option = new Option();
						if( valores[0] == "Vacio") {
							option.value = '';
						} else {
							option.value = valores[0];
						}
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
		if( $('desde') && $('desde').value == 'agregar_asunto') {
			if( $('cobro_independiente') ) {
				if( $('cobro_independiente').checked ) {
					var cobro_independiente = '&cobro_independiente=SI';
					var cliente = '';
				} else {
					var cobro_independiente = '&cobro_independiente=NO';
					//var cliente = '&codigo_cliente='+$('codigo_cliente').value;
					
					var cliente = '&codigo_cliente='+$('<?php echo UtilesApp::GetConf($sesion, 'CodigoSecundario') ? 'codigo_cliente_secundario' : 'codigo_cliente'; ?>').value;
					
				}
			} else {
				var cliente = '';
				var cobro_independiente = "";
			}
		} else {
			var cliente = '';
			var cobro_independiente = "";
		}
		var http = getXMLHTTP();
		var url = 'ajax.php?accion=revisar_tarifas&id_tarifa=' + $('id_tarifa').value + '&id_moneda=' + $(moneda).value + cobro_independiente + cliente;
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

		if( ejecutar && ( jQuery('#desde').val() == 'agregar_cliente' || jQuery('#desde').val() == 'agregar_contrato' || ( jQuery('#desde').val() == 'agregar_asunto' && jQuery('#cobro_independiente').is(':checked') ) ) ) // cant::lista
		{
			var text_window = "";
			var respuesta = RevisarTarifasRequest(tarifa, moneda);
			var parts = respuesta.split("::");
			var todos = false;
			if( parts[0] > 0)
			{
				text_window += "<img src='<?php echo Conf::ImgDir() ?>/alerta_16.gif'>&nbsp;&nbsp;<span style='font-size:12px; color:#FF0000; text-align:center;font-weight:bold'><u><?php echo __("ALERTA") ?></u><br><br></span>";
				if( parts[0] < 10 )
				{
					text_window += '<span style="font-size:12px; text-align:center;font-weight:bold"><?php echo __('Listado de usuario con tarifa sin valor para la moneda seleccionada.') ?></span><br><br>';
					text_window += '<span style="font-size:12px; text-align:left;">' + parts[1] + '</span><br><br>';
					todos = false;
				}
				else if( parts[0] == parts[2] )
				{
					text_window += '<span style="font-size:12px; text-align:center;font-weight:bold"><?php echo __('La tarifa seleccionada no tiene valor definido en la moneda elegida.') ?></span><br><br>';
					todos = true;
				}
				else
				{
					text_window += '<span style="font-size:12px; text-align:center;font-weight:bold"><?php echo __('Hay más de 10 abogados sin valor para la tarifa y moneda seleccionadas.') ?></span><br><br>';
					todos = false;
				}
				text_window += '<span style="font-size:12px; text-align:left;"><a href="javascript:;" onclick="CreaTarifa(this.form,false,'+parts[3]+')"><?php echo __('Modificar tarifa.') ?></a></span>';

				if( todos && !desde_combo )
				{
					Dialog.alert(text_window,
					{
						top:100, left:80, width:400, okLabel: "<?php echo __('Cerrar') ?>",
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
						top:100, left:80, width:400, okLabel: "<?php echo __('Continuar') ?>", cancelLabel: "<?php echo __('Cancelar') ?>", buttonClass: "btn", className: "alphacube",
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

	var mismoEncargado = <?php echo UtilesApp::GetConf($sesion, 'EncargadoSecundario') && $contrato->fields['id_usuario_responsable'] == $contrato->fields['id_usuario_secundario'] ? 'true' : 'false' ?>;
	var CopiarEncargadoAlAsunto=<?php echo (UtilesApp::GetConf($sesion, "CopiarEncargadoAlAsunto") ) ? '1' : '0'; ?>;	
	var EncargadoSecundario=<?php echo (UtilesApp::GetConf($sesion, "EncargadoSecundario") ) ? '1' : '0'; ?>;	
    var DesdeAgregaCliente=<?php echo ($desde_agrega_cliente ) ? '1' : '0'; ?>;	
			
	function CambioEncargado(elemento){

	
		if (CopiarEncargadoAlAsunto && DesdeAgregaCliente) { 
			
			
		
			if (elemento.name == "id_usuario_responsable") {
				if (EncargadoSecundario ) {  
					$('id_usuario_secundario').value = $('id_usuario_responsable').value;
					if(jQuery('#id_usuario_secundario').length>0) jQuery('#id_usuario_secundario').attr('disabled','disabled');

				} else { 
				
					$('id_usuario_encargado').value = $('id_usuario_responsable').value;
					if(jQuery('#id_usuario_encargado').length>0) jQuery('#id_usuario_encargado').attr('disabled','disabled');
				}  
		
		 
			} else { 
				if(mismoEncargado && $('id_usuario_secundario').value == '-1' ){			
					if(confirm('¿Desea cambiar también el <?php echo __('Encargado Secundario'); ?> ?')){
						if(EncargadoSecundario)  {
							$('id_usuario_secundario').value = $('id_usuario_responsable').value;
						} else { 
							$('id_usuario_encargado').value = $('id_usuario_responsable').value;
						}
					} else {
						mismoEncargado = false;
					}
				}
			
		 	
			} 
		 
		}
	}

	function agregarHito(){
		if(!validarHito($('fila_hito_1'))) return false;

		for(var num = 2; $('fila_hito_'+num); num++);

		var nuevo = $($('fila_hito_1').cloneNode(true));
		nuevo.id = 'fila_hito_'+num;
		nuevo.select('[id$="_1"]').each(function(elem){
			elem.id = elem.id.replace('1', num);
			elem.name = elem.name.replace('1', num);
		});
		var btn = nuevo.down('[src$="mas.gif"]');
		btn.src = btn.src.replace('mas.gif', 'eliminar.gif');

		/*   btn.onclick=eliminarHito(this); */
		var onclick = btn.getAttribute("onclick");  
				
		if(typeof(onclick) != "function") { 
			btn.setAttribute('onclick','eliminarHito(this);' ); // para FF,IE8-IE9,Chrome
				
		} else {
			btn.onclick = function() { // Para IE7
				eliminarHito(this);
			}; 
		}

		$('fila_hito_1').insert({before: nuevo});
		$('fila_hito_1').select('input').each(function(elem){
			elem.value = '';
		});
		$('fila_hito_1').setAttribute('bgcolor', $('fila_hito_1').getAttribute('bgcolor') == '<?php echo $color_par ?>' ? '<?php echo $color_impar ?>' : '<?php echo $color_par ?>');

		Calendar.setup({
			inputField	: 'hito_fecha_'+num,				// ID of the input field
			ifFormat	: "%d-%m-%Y",			// the date format
			button		: 'img_fecha_hito_'+num		// ID of the button
		});
	}

	function validarHito(fila, permitirVacio){
		var num = fila.id.match(/\d+$/)[0];
		var fecha = $F('hito_fecha_'+num);
		var desc = $F('hito_descripcion_'+num);
		var monto = Number($F('hito_monto_estimado_'+num));

		if($('hito_fecha_'+num).disabled) return true;
		if(permitirVacio && !fecha && !desc && !monto) return true;
		/*if(fecha && !(new Date(fecha.replace(/(\d+)-(\d+)-(\d+)/, '$2/$1/$3')).getTime() > new Date().getTime())){
			
			alert('Ingrese una fecha válida para el hito');
			$('hito_fecha_'+num).focus();
			return false;
		}*/
		if(!desc){
			alert('Ingrese una descripción válida para el hito');
			$('hito_descripcion_'+num).focus();
			return false;
		}
		if(isNaN(monto) || monto <= 0){
			alert('Ingrese un monto válido para el hito');
			$('hito_monto_estimado_'+num).focus();
			return false;
		}
		return true;
	}

	function eliminarHito(elem){
		if(confirm('¿Está seguro que desea eliminar este hito?')) $(elem).up('tr').remove();
	}
	
</script>
<?php if ($popup && !$motivo) { ?>

	<form name='formulario' id='formulario' method=post>
		<input type=hidden name=codigo_cliente value="<?php echo $cliente->fields['codigo_cliente'] ? $cliente->fields['codigo_cliente'] : $codigo_cliente ?>" />
		<input type=hidden name='opcion_contrato' value="guardar_contrato" />
		<input type=hidden name='id_contrato' value="<?php echo isset($cargar_datos_contrato_cliente_defecto) ? '' : $contrato->fields['id_contrato']; ?>" />
		<input type="hidden" name="desde" value="agregar_contrato" />
<?php } ?>
	<br />
	<!-- Calendario DIV -->
	<div id="calendar-container" style="width:221px; position:absolute; display:none;">
		<div class="floating" id="calendar"></div>
	</div>

	<!-- Fin calendario DIV -->
	<fieldset style="width: 97%;" class="tb_base" style="border: 1px solid #BDBDBD;">
		<legend>&nbsp;<?php echo __('Información Comercial') ?></legend>

		<!-- RESPONSABLE -->
		<table id="responsable">
			<tr   class="controls controls-row ">
				<td class="al"><div class="span4">
				<?php echo __('Activo') ?>
				</div>
				<?php
				$chk = '';
				if (!$contrato->loaded()) {
					$chk = 'checked="checked"';
				}
				?>
				</td>
				<td class="al"> 
					<label for="activo_contrato" class="inline-help"><input type="hidden" name="activo_contrato" value="0"/><input type="checkbox" class="span1" name="activo_contrato" id="activo_contrato" value="1" <?php echo $contrato->fields['activo'] == 'SI' ? 'checked="checked"' : '' ?> <?php echo $chk ?> onclick="InactivaContrato(this.checked);" />
					&nbsp;<?php echo __('Los contratos inactivos no aparecen en el listado de cobranza.') ?></label>
				 </td>
			</tr>
		<?php if (UtilesApp::GetConf($sesion, 'UsarImpuestoSeparado')) { ?>
				<tr   class="controls controls-row ">
					<td class="al">
						<div class="span4">
						<?php echo __('Usa impuesto a honorario') ?>
					</div>
					<?php
						// Se revisa también el primer contrato del cliente para el valor por defecto.
						$chk = '';
						if ($contrato->loaded()) {
							if ($contrato->fields['usa_impuesto_separado']) {
								$chk = 'checked="checked"';
							}
						} else if (Utiles::Glosa($sesion, $cliente->fields['id_contrato'], 'usa_impuesto_separado', 'contrato')) {
							$chk = 'checked="checked"';
						}
					?>
					</td><td class="al"><input type="hidden" name="usa_impuesto_separado" value="0"/>
						<input class="span1" type="checkbox" name="usa_impuesto_separado" id="usa_impuesto_separado" value="1" <?php echo $chk ?> />
						
					</td>
				</tr>
				<?php 	}
				if (UtilesApp::GetConf($sesion, 'UsarImpuestoPorGastos')) {
				?>
				<tr   class="controls controls-row ">
					<td class="al"><div class="span4">
					<?php echo __('Usa impuesto a gastos') ?>
					</div>
					<?php
					// Se revisa también el primer contrato del cliente para el valor por defecto.
					$chk_gastos = '';
					if ($contrato->loaded()) {
						if ($contrato->fields['usa_impuesto_gastos']) {
							$chk_gastos = 'checked="checked"';
						}
					} else if (Utiles::Glosa($sesion, $cliente->fields['id_contrato'], 'usa_impuesto_gastos', 'contrato')) {
						$chk_gastos = 'checked="checked"';
					}
					?>
					</td><td class="al"><input type="hidden" name="usa_impuesto_gastos" value="0"/>
						<input class="span1"  type="checkbox" name="usa_impuesto_gastos" id="impuesto_gastos" value="1" <?php echo $chk_gastos ?> />
					</td>
				</tr>
				<?php
			}

			if ($contrato->Loaded()) {
				$separar_liquidaciones = $contrato->fields['separar_liquidaciones'];
				$exportacion_ledes = $contrato->fields['exportacion_ledes'];
			} else if (UtilesApp::GetConf($sesion, 'SepararLiquidacionesPorDefecto')) {
				$separar_liquidaciones = '1';
			} else {
				$separar_liquidaciones = '0';
			}
			?>
			<tr   class="controls controls-row ">
				<td class="al">
				<div class="span4">	<?php echo __('Liquidar por separado (honorario y gastos)') ?></div></td>
				<td class="al">
				<div class="span1"><input type="hidden" name="separar_liquidaciones" value="0"/><input  class="span1" id="separar_liquidaciones" type="checkbox" name="separar_liquidaciones" value="1" <?php echo $separar_liquidaciones == '1' ? 'checked="checked"' : '' ?>  /></div>
			</td></tr>
			<?php
			$query = "SELECT usuario.id_usuario,CONCAT_WS(' ',apellido1,apellido2,',',nombre)
				FROM usuario JOIN usuario_permiso USING(id_usuario)
				WHERE codigo_permiso='SOC' ORDER BY apellido1";
			?>
			<tr   class="controls controls-row ">
				<td class="al"><div class="span4">
					<?php
					echo __('Encargado Comercial');

					if ($usuario_responsable_obligatorio)
						echo $obligatorio;
					//print_r($contrato_defecto);
					?>
				</div></td>
<td class="al">
				<?php

				if (UtilesApp::GetConf($sesion, 'CopiarEncargadoAlAsunto') && $contrato_defecto->Loaded() && !$contrato->Loaded()) {
					echo Html::SelectQuery($sesion, $query, "id_usuario_responsable", $contrato_defecto->fields['id_usuario_responsable'], ' class="span3" onchange="CambioEncargado(this)" disabled="disabled"', "Vacio", "200");
					echo '(Se copia del contrato principal)';
					echo '<input type="hidden" value="' . $contrato_defecto->fields['id_usuario_responsable'] . '" name="id_usuario_responsable" />';
				} else {
					if ($contrato_defecto->Loaded() && $contrato->Loaded()) {
						if (UtilesApp::GetConf($sesion, 'CopiarEncargadoAlAsunto') && !$desde_agrega_cliente) {
							echo Html::SelectQuery($sesion, $query, "id_usuario_responsable", $contrato->fields['id_usuario_responsable'] ? $contrato->fields['id_usuario_responsable'] : "", 'class="span3"  onchange="CambioEncargado(this)" disabled="disabled"', "Vacio", "200");
							echo '<input type="hidden" value="' . $contrato_defecto->fields['id_usuario_responsable'] . '" name="id_usuario_responsable" />';
							echo '(Se copia del contrato principal)';
						} else {
							//FFF si estoy agregando o editando un asunto que se cobra por separado
							echo Html::SelectQuery($sesion, $query, "id_usuario_responsable", $contrato->fields['id_usuario_responsable'] ? $contrato->fields['id_usuario_responsable'] : "", ' class="span3" onchange="CambioEncargado(this)"', "Vacio", "200");
						}
					} else if (UtilesApp::GetConf($sesion, 'CopiarEncargadoAlAsunto') && $desde_agrega_cliente) {
						// Estoy creando un cliente (y su contrato por defecto). 
						echo Html::SelectQuery($sesion, $query, "id_usuario_responsable", $contrato->fields['id_usuario_responsable'] ? $contrato->fields['id_usuario_responsable'] : "  ", ' class="span3"  onchange="CambioEncargado(this)"', "Vacio", "200");
					} else {

						echo Html::SelectQuery($sesion, $query, "id_usuario_responsable", $contrato->fields['id_usuario_responsable'] ? $contrato->fields['id_usuario_responsable'] : '', 'class="span3" ', "Vacio", "200");
					}
				}
				?>
</td>
			</tr>
			<?php
			if ($modulo_retribuciones_activo) {
				?>
				<tr>
				<td class="al"><div class="span4">
					<?php
					echo __('Retribución') . ' ' . __('Encargado Comercial');
					?>
				</div></td>
				<td class="al">
					<input name="retribucion_usuario_responsable" type="text" size="6" value="<?php
						echo empty($contrato->fields['id_contrato']) ? UtilesApp::GetConf($sesion, 'RetribucionUsuarioResponsable') : $contrato->fields['retribucion_usuario_responsable'];
					?>"/>%
				</td>
				</tr>
			<?php
			}//$modulo_retribuciones_activo

			if (UtilesApp::GetConf($sesion, 'EncargadoSecundario')) {
				$query = "SELECT usuario.id_usuario,CONCAT_WS(' ',apellido1,apellido2,',',nombre)
				FROM usuario
				WHERE activo = 1 OR id_usuario = '" . $contrato->fields['id_usuario_secundario'] . "'
				ORDER BY apellido1";
				?>
				<tr   class="controls controls-row ">
					<td class="al"><div class="span4">
							<?php echo __('Encargado Secundario') ?>
							<?php if ($usuario_secundario_obligatorio) echo $obligatorio; ?>
					</div></td>
						<td class="al"> 
					<?php echo Html::SelectQuery($sesion, $query, "id_usuario_secundario", $contrato->fields['id_usuario_secundario'] ? $contrato->fields['id_usuario_secundario'] : '', " class='span3' ", "Vacio", "200"); ?>
						</div></td>
					</tr>
				<?php
				if ($modulo_retribuciones_activo) {
				?>
				<tr>
				<td class="al"><div class="span4">
					<?php
					echo __('Retribución') . ' ' . __('Encargado Secundario');
					?>
				</div></td>
				<td class="al">
					<input name="retribucion_usuario_secundario" type="text" size="6" value="<?php
						echo empty($contrato->fields['id_contrato']) ? UtilesApp::GetConf($sesion, 'RetribucionUsuarioSecundario') : $contrato->fields['retribucion_usuario_secundario'];
					?>" />%
				</td>
				</tr>
						<?php
					}
			}
				  if (UtilesApp::GetConf($sesion, 'ExportacionLedes')) { ?>
					<tr   class="controls controls-row ">
						<td class="al"><div class="span4">
	<?php echo __('Usa exportación LEDES'); ?>
							</div></td>
								<td class="al">
								<input type="hidden" name="exportacion_ledes" value="0"/>	<input  class="span1" id="exportacion_ledes" type="checkbox" name="exportacion_ledes" value="1" <?php echo $exportacion_ledes == '1' ? 'checked="checked"' : '' ?>  />
								</td>
							 
					<?php } ?>
					</tr>
		</table>
					<br><br>
					<!-- FIN RESPONSABLE -->
					<?php
					if (UtilesApp::GetConf($sesion, 'SetFormatoRut'))
						$setformato = "SetFormatoRut();";
					else
						$setformato = "";
					?>






					<!-- DATOS FACTURACION -->
					<fieldset style="width: 97%;background-color: #FFFFFF;">
						<legend <?php echo!$div_show ? 'onClick="MuestraOculta(\'datos_factura\')" style="cursor:pointer"' : '' ?>>
<?php echo!$div_show ? '<span id="datos_factura_img"><img src="' . Conf::ImgDir() . '/mas.gif" border="0" id="datos_factura_img"></span>' : '' ?>
							&nbsp;<?php echo __('Datos Facturación') ?></legend>
						<table id='datos_factura' style='display:<?php echo $show ?>'>
							<tr>
								<td align="right" width='20%'>
<?php echo __('ROL/RUT') ?>
<?php if ($validaciones_segun_config)
	echo $obligatorio
	?>
								</td>
								<td align="left" colspan="3">
									<input type="text" size=20 name="factura_rut" id="rut" value="<?php echo $contrato->fields['rut'] ?>" onblur="<?php echo $setformato ?>validarUnicoCliente(this.value,'rut');" />
								</td>
							</tr>
							<tr>
								<td align="right" colspan="1">
<?php echo __('Razón Social') ?>
<?php if ($validaciones_segun_config)
	echo $obligatorio
	?>
								</td>
								<td align="left" colspan="5">
									<input type="text" name='factura_razon_social' size=50 value="<?php echo $contrato->fields['factura_razon_social'] ?>"  />
								</td>
							</tr>
							<tr>
								<td align="right" colspan="1">
<?php echo __('Giro') ?>
<?php if ($validaciones_segun_config)
	echo $obligatorio
	?>
								</td>
								<td align="left" colspan="5">
									<input  type="text" name='factura_giro' size=50 value="<?php echo $contrato->fields['factura_giro'] ?>"  />
								</td>
							</tr>
							<tr>
								<td align="right" colspan="1">
<?php echo __('Dirección') ?>
							<?php if ($validaciones_segun_config)
								echo $obligatorio
								?>
								</td>
								<td align="left" colspan="5">
									<textarea class="span4" name='factura_direccion' rows=3 cols="55" ><?php echo $contrato->fields['factura_direccion'] ?></textarea>
								</td>
							</tr>
<?php if (UtilesApp::existecampo('factura_comuna', 'contrato', $sesion)) { ?>
								<tr>
									<td align="right" colspan="1">
								<?php echo __('Comuna') ?>
								<?php if ($validaciones_segun_config)
									echo $obligatorio
									?>
									</td>
									<td align="left" colspan="5">
										<input  type="text"  name='factura_comuna' size=50 value="<?php echo $contrato->fields['factura_comuna'] ?>"  />
									</td>
								</tr>
										<?php
									}
if (UtilesApp::existecampo('factura_codigopostal', 'contrato', $sesion)) { ?>
								<tr>
									<td align="right" colspan="1">
								<?php echo __('Código Postal'); ?>
																</td>
									<td align="left" colspan="5">
										<input  type="text"  name='factura_codigopostal' size=50 value="<?php echo $contrato->fields['factura_codigopostal'] ?>"  />
									</td>
								</tr>
										<?php
									}									
									if (UtilesApp::existecampo('factura_ciudad', 'contrato', $sesion)) {
										?>
								<tr>
									<td align="right" colspan="1">
								<?php echo __('Ciudad') ?>
	<?php if ($validaciones_segun_config)
		echo $obligatorio
		?>
									</td>
									<td align="left" colspan="5">
										<input  type="text"  name='factura_ciudad' size=50 value="<?php echo $contrato->fields['factura_ciudad'] ?>"  />
									</td>
								</tr>
<?php } ?>
							<tr>
								<td align="right" colspan="1">
									<?php echo __('País') ?>
									<?php if ($validaciones_segun_config)
										echo $obligatorio
										?>
								</td>
								<td align="left" colspan='3'>
<?php echo Html::SelectQuery($sesion, "SELECT id_pais, nombre FROM prm_pais ORDER BY preferencia DESC, nombre ASC", "id_pais", $contrato->fields['id_pais'] ? $contrato->fields['id_pais'] : '', 'class ="span3"', 'Vacio', 260); ?>&nbsp;&nbsp;
								</td>
							</tr>
							<tr>
								<td align="right" colspan="1">
									<?php echo __('Teléfono') ?>
<?php if ($validaciones_segun_config)
	echo $obligatorio
	?>
								</td>
								<td align="left" colspan="5">
									<input type="text" class="span1" name='cod_factura_telefono' size=8 value="<?php echo $contrato->fields['cod_factura_telefono'] ?>" />&nbsp;<input type="text" class="span2" name='factura_telefono' size=30 value="<?php echo $contrato->fields['factura_telefono'] ?>" />
								</td>
							</tr>
							<tr>
								<td align="right" colspan="1">
							<?php echo __('Glosa factura') ?>
								</td>
								<td align="left" colspan="5">
									<textarea class="span4" name='glosa_contrato' rows=4 cols="55" ><?php echo $contrato->fields['glosa_contrato'] ?></textarea>
								</td>
							</tr>
							<?php
							$id_banco = false;
							if ($contrato->fields['id_cuenta'] && is_numeric($contrato->fields['id_cuenta'])) {
								$query_banco = 'SELECT b.* FROM cuenta_banco c, prm_banco b WHERE b.id_banco = c.id_banco AND c.id_cuenta = ' . $contrato->fields['id_cuenta'];
								$result = mysql_query($query_banco, $sesion->dbh);

								if (!$result) {
									
								} else {
									$result = mysql_fetch_object($result);
									$id_banco = $result->id_banco;
								}
							}

							if ($id_banco) {
								$where_banco = " WHERE cuenta_banco.id_banco = '$id_banco' ";
							} else {
								$where_banco = " WHERE 1=2 ";
							}
							?>                
							<tr>
								<td align="right" colspan="1">
									<?php echo __('Banco') ?>
								</td>
								<td align="left" colspan="2">
							<?php echo Html::SelectQuery($sesion, "SELECT id_banco, nombre FROM prm_banco ORDER BY orden", "id_banco", $id_banco, 'onchange="CargarCuenta(\'id_banco\',\'id_cuenta\');"', "Cualquiera", "150") ?>
								</td>

								<td align="right" colspan="1">
							<?php echo __('Cuenta') ?>
								</td>
								<td align="left" colspan="2">
							<?php
							$query_cuenta_banco = "SELECT cuenta_banco.id_cuenta , CONCAT( cuenta_banco.numero, IF( prm_moneda.glosa_moneda IS NOT NULL , CONCAT(' (',prm_moneda.glosa_moneda,')'),  '' ) ) AS NUMERO FROM cuenta_banco LEFT JOIN prm_moneda ON prm_moneda.id_moneda = cuenta_banco.id_moneda " . $where_banco;
							?>
							<?php echo Html::SelectQuery($sesion, $query_cuenta_banco, "id_cuenta", $contrato->fields['id_cuenta'] ? $contrato->fields['id_cuenta'] : $id_cuenta, '', $tiene_banco ? "" : "Cualquiera", "150") ?>
								</td>
							</tr>
							<?php
							if (UtilesApp::GetConf($sesion, 'SegundaCuentaBancaria')) {
								$id_banco = false;
								if ($contrato->fields['id_cuenta2'] && is_numeric($contrato->fields['id_cuenta2'])) {
									$query_banco = "SELECT b.* FROM cuenta_banco c, prm_banco b WHERE b.id_banco = c.id_banco AND c.id_cuenta = '{$contrato->fields['id_cuenta2']}'";
									$result = mysql_query($query_banco, $sesion->dbh);

									if (!$result) {
										
									} else {
										$result = mysql_fetch_object($result);
										$id_banco2 = $result->id_banco;
									}
								}

								if ($id_banco2) {
									$where_banco2 = " WHERE cuenta_banco.id_banco = '$id_banco2' ";
								} else {
									$where_banco2 = " WHERE 1=2 ";
								}
								?>
								<tr>
									<td align="right" colspan="1">
										<?php echo __('Banco Secundario') ?>
									</td>
									<td align="left" colspan="2">
								<?php echo Html::SelectQuery($sesion, "SELECT id_banco, nombre FROM prm_banco ORDER BY orden", "id_banco2", $id_banco2, 'onchange="CargarCuenta(\'id_banco2\',\'id_cuenta2\');"', "Cualquiera", "150") ?>
									</td>

									<td align="right" colspan="1">
	<?php echo __('Cuenta Secundaria') ?>
									</td>
									<td align="left" colspan="2">
	<?php
	$query_cuenta_banco = "SELECT cuenta_banco.id_cuenta , CONCAT( cuenta_banco.numero, IF( prm_moneda.glosa_moneda IS NOT NULL , CONCAT(' (',prm_moneda.glosa_moneda,')'),  '' ) ) AS NUMERO FROM cuenta_banco LEFT JOIN prm_moneda ON prm_moneda.id_moneda = cuenta_banco.id_moneda " . $where_banco2;
	?>
	<?php echo Html::SelectQuery($sesion, $query_cuenta_banco, "id_cuenta2", $contrato->fields['id_cuenta2'] ? $contrato->fields['id_cuenta2'] : $id_cuenta2, '', $tiene_banco ? "" : "Cualquiera", "150") ?>
									</td>
								</tr>
								<?php
							}
							?>
						</table>

					</fieldset>
					<!-- FIN DATOS FACTURACION -->
					<br>


					<!-- SOLICITANTE -->
					<fieldset style="width: 97%; background-color: #FFFFFF;">
						<legend <?php echo!$div_show ? 'onClick="MuestraOculta(\'datos_solicitante\')" style="cursor:pointer"' : '' ?> >
									<?php echo!$div_show ? '<span id="datos_solicitante_img"><img src="' . Conf::ImgDir() . '/mas.gif" border="0" id="datos_solicitante_img"></span>' : '' ?>
							&nbsp;<?php echo __('Solicitante') ?></legend>
						<table id='datos_solicitante' style='display:<?php echo $show ?>'>
<?php
if (( method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'TituloContacto') ) || ( method_exists('Conf', 'TituloContacto') && Conf::TituloContacto() )) {
	?>
								<tr>
									<td align="right" width="20%">
	<?php echo __('Titulo') ?>
	<?php if ($validaciones_segun_config)
		echo $obligatorio
		?>
									</td>
									<td align="left" colspan='3'>
										<?php echo Html::SelectQuery($sesion, "SELECT titulo, glosa_titulo FROM prm_titulo_persona ORDER BY id_titulo", "titulo_contacto", $contrato->fields['titulo_contacto'] ? $contrato->fields['titulo_contacto'] : '', '', 'Vacio', 65); ?>&nbsp;&nbsp;
									</td>
								</tr>
								<tr>
									<td align="right" width='20%'>
	<?php echo __('Nombre') ?>
	<?php if ($validaciones_segun_config)
		echo $obligatorio
		?>
									</td>
									<td align='left' colspan='3'>
										<input type="text" size='55' name="nombre_contacto" id="nombre_contacto" value="<?php echo $contrato->fields['contacto'] ?>" />
									</td>
								</tr>
								<tr>
									<td align="right" width='20%'>
										<?php echo __('Apellido') ?>
										<?php if ($validaciones_segun_config)
											echo $obligatorio
											?>
									</td>
									<td align='left' colspan='3'>
										<input type="text" size='55' name="apellido_contacto" id="apellido_contacto" value="<?php echo $contrato->fields['apellido_contacto'] ?>"  />
									</td>
								</tr>
								<?php
							}
							else {
								?>
								<tr>
									<td align="right" width='20%'>
	<?php echo __('Nombre') ?>
	<?php if ($validaciones_segun_config)
		echo $obligatorio
		?>
									</td>
									<td align='left' colspan='3'>
										<input type="text" size='55' name="contacto" id="contacto" value="<?php echo $contrato->fields['contacto'] ?>"  />
									</td>
								</tr>
										<?php
									}
									?>
							<tr>
								<td align="right" colspan="1">
<?php echo __('Teléfono') ?>
<?php if ($validaciones_segun_config)
	echo $obligatorio
	?>
								</td>
								<td align="left" colspan="5">
									<input type="text" name='fono_contacto_contrato' size=30 value="<?php echo $contrato->fields['fono_contacto'] ?>" />
								</td>
							</tr>
							<tr>
								<td align="right" colspan="1">
<?php echo __('E-mail') ?>
<?php if ($validaciones_segun_config)
	echo $obligatorio
	?>
								</td>
								<td align="left" colspan="5">
									<input type="text" name='email_contacto_contrato' size=55 value="<?php echo $contrato->fields['email_contacto'] ?>"  />
								</td>
							</tr>
							<tr>
								<td align="right" colspan="1">
					<?php echo __('Dirección envío') ?>
					<?php if ($validaciones_segun_config)
						echo $obligatorio
						?>
								</td>
								<td align="left" colspan="5">
									<textarea name='direccion_contacto_contrato' rows=4 cols="55" ><?php echo $contrato->fields['direccion_contacto'] ?></textarea>
								</td>
							</tr>

						</table>
					</fieldset>
					<!-- FIN SOLICITANTE -->

					<br>
					<?php
					$fecha_ini = date('d-m-Y');

					if ($popup && !$motivo) {
						if ($contrato->loaded()) {
							if ($contrato->fields['periodo_fecha_inicio'] != '0000-00-00' && $contrato->fields['periodo_fecha_inicio'] != '' && $contrato->fields['periodo_fecha_inicio'] != 'NULL')
								$fecha_ini = Utiles::sql2date($contrato->fields['periodo_fecha_inicio']);
						}
					}
					else
						$fecha_ini = Utiles::sql2date($contrato->fields['periodo_fecha_inicio']);

					if (!$id_moneda)
						$id_moneda = Moneda::GetMonedaTarifaPorDefecto($sesion);
					if (!$id_moneda)
						$id_moneda = Moneda::GetMonedaBase($sesion);

					if (!$id_moneda_tramite)
						$id_moneda_tramite = Moneda::GetMonedaTramitePorDefecto($sesion);

					if (!$opc_moneda_total)
						$opc_moneda_total = Moneda::GetMonedaTotalPorDefecto($sesion);
					if (!$opc_moneda_total)
						$opc_moneda_total = Moneda::GetMonedaBase($sesion);

					$config_validar_tarifa = ( UtilesApp::GetConf($sesion, 'RevisarTarifas') ? ' RevisarTarifas( \'id_tarifa\', \'id_moneda\', this.form, true);' : '' );
					?>

					<!-- COBRANZA -->
					<fieldset style="width: 98%; background-color: #FFFFFF;">
						<legend <?php echo!$div_show ? 'onClick="MuestraOculta(\'datos_cobranza\')" style="cursor:pointer"' : '' ?> />
<?php echo!$div_show ? '<span id="datos_cobranza_img"><img src="' . Conf::ImgDir() . '/mas.gif" border="0" id="datos_cobranza_img"></span>' : '' ?>
						&nbsp;<?php echo __('Datos de Tarificación') ?>
						</legend>
						<div id='datos_cobranza' style='display:<?php echo $show ?>' width="98%">
							<table width="100%" >
								<tr id="divthh">
									<td  class="ar "  >
													<?php echo __('Tarifa horas') ?>
													<?php if ($validaciones_segun_config)
														echo $obligatorio
														?>
									</td>
									<td align="left" width="80%" style="font-size:10pt;">
										<table  style="float:left;" class="span7">
											<tr>
												<td class="span4">
													<div   class="controls controls-row ">
														<input   type="radio" name="tipo_tarifa" id="tipo_tarifa_variable" value="variable" <?php echo empty($valor_tarifa_flat) ? 'checked' : '' ?>/>
<?php echo Html::SelectQuery($sesion, "SELECT tarifa.id_tarifa, tarifa.glosa_tarifa FROM tarifa WHERE tarifa_flat IS NULL ORDER BY tarifa.glosa_tarifa", "id_tarifa", $contrato->fields['id_tarifa'] ? $contrato->fields['id_tarifa'] : $tarifa_default, 'onclick="$(\'tipo_tarifa_variable\').checked = true;" ' . ( strlen($config_validar_tarifa) > 0 ? 'onchange="' . $config_validar_tarifa . '"' : '')); ?>
													<input type="hidden" name="id_tarifa_hidden" id="id_tarifa_hidden" value="<?php echo $contrato->fields['id_tarifa'] ? $contrato->fields['id_tarifa'] : $tarifa_default; ?>" />
													</div>
													 
													<div   class="controls controls-row ">
														
															
														 <label for="tipo_tarifa_flat"  class="span2" >	<input type="radio"  name="tipo_tarifa" id="tipo_tarifa_flat" value="flat" <?php echo empty($valor_tarifa_flat) ? '' : 'checked' ?>/>
															 Plana por </label>
														<input id="tarifa_flat" class="input-small"  type="text" name="tarifa_flat" onclick="$('tipo_tarifa_flat').checked = true" value="<?php echo $valor_tarifa_flat ?>"/>

														<input type="hidden" id="id_tarifa_flat"  name="id_tarifa_flat" value="<?php echo $contrato->fields['id_tarifa'] ?>"/>
													</div>
												</td>
												<td>
									<?php echo __('Tarifa en') ?>
									<?php if ($validaciones_segun_config)
										echo $obligatorio
										?>
									<?php echo Html::SelectQuery($sesion, "SELECT id_moneda,glosa_moneda FROM prm_moneda ORDER BY id_moneda", "id_moneda", $contrato->fields['id_moneda'] ? $contrato->fields['id_moneda'] : $id_moneda, 'onchange="actualizarMoneda(); ' . $config_validar_tarifa . ' "', '', "80"); ?>
													<input type="hidden" name="id_moneda_hidden" id="id_moneda_hidden" value="<?php echo $contrato->fields['id_moneda'] ? $contrato->fields['id_moneda'] : $id_moneda; ?>" />
													&nbsp;&nbsp;
									<?php if ($tarifa_permitido) { ?>
														<span style='cursor:pointer' <?php echo TTip(__('Agregar nueva tarifa')) ?> onclick='CreaTarifa(this.form,true,false)'><img src="<?php echo Conf::ImgDir() ?>/mas.gif" border="0"></span>
														<span style='cursor:pointer' <?php echo TTip(__('Editar tarifa seleccionada')) ?> onclick='CreaTarifa(this.form,false,false)'><img src="<?php echo Conf::ImgDir() ?>/editar_on.gif" border="0"></span>
<?php } ?>
												</td>
											</tr>
										</table>
									</td>
								</tr>
								<tr>
									<td  class="ar ">
<?php echo __('Forma de cobro') ?>
<?php if ($validaciones_segun_config)
	echo $obligatorio
	?>
									</td>
											<?php
											if (!$contrato->fields['forma_cobro'])
												$contrato_forma_cobro = 'TASA';
											else
												$contrato_forma_cobro = $contrato->fields['forma_cobro'];

											// Setear valor del multiselect por de usuarios retainer
											if (!is_array($usuarios_retainer))
												$usuarios_retainer = explode(',', $contrato->fields['retainer_usuarios']);
											?>
									<td align="left" style="font-size:10pt;">
										<div id="div_cobro" class="buttonset">
											<input <?php echo TTip($tip_tasa) ?> class="formacobro" id="fc1" type="radio" name="forma_cobro" value="TASA" <?php echo $contrato_forma_cobro == "TASA" ? "checked='checked'" : "" ?> />
											<label for="fc1">Tasas/HH</label>&nbsp;
											<input <?php echo TTip($tip_retainer) ?> class="formacobro"  id="fc2" type=radio name="forma_cobro" value="RETAINER" <?php echo $contrato_forma_cobro == "RETAINER" ? "checked='checked'" : "" ?> />
											<label for="fc2">Retainer</label> &nbsp;
											<input <?php echo TTip($tip_flat) ?>  class="formacobro"  id="fc3" type="radio" name="forma_cobro"  value="FLAT FEE" <?php echo $contrato_forma_cobro == "FLAT FEE" ? "checked='checked'" : "" ?> />
											<label for="fc3"><?php echo __('Flat fee') ?></label>&nbsp;
											<input <?php echo TTip($tip_cap) ?>   class="formacobro"  id="fc5" type="radio" name="forma_cobro"  value="CAP" <?php echo $contrato_forma_cobro == "CAP" ? "checked='checked'" : "" ?> />
											<label for="fc5"><?php echo __('Cap') ?></label>&nbsp;
											<input <?php echo TTip($tip_proporcional) ?>  class="formacobro"  id="fc6" type="radio" name="forma_cobro"  value="PROPORCIONAL" <?php echo $contrato_forma_cobro == "PROPORCIONAL" ? "checked='checked'" : "" ?> />
											<label for="fc6">Proporcional</label> &nbsp;
											<input <?php echo TTip($tip_hitos) ?>  class="formacobro"  id="fc7" type="radio" name="forma_cobro"  value="HITOS" <?php echo $contrato_forma_cobro == "HITOS" ? "checked='checked'" : "" ?> />
											<label for="fc7"><?php echo __('Hitos') ?></label>
												<?php if (!UtilesApp::GetConf($sesion, 'EsconderTarifaEscalonada')) { ?>
												<input <?php echo TTip($tip_escalonada) ?>  class="formacobro"  id="fc8" type="radio" name="forma_cobro"  value="ESCALONADA" <?php echo $contrato_forma_cobro == "ESCALONADA" ? "checked='checked'" : "" ?> />
												<label for="fc8"><?php echo __('Escalonada') ?></label>
<?php } ?>
										</div>
									</td></tr>
								<tr><td colspan="2">
										<div style='border:1px solid #999999;width:400px;padding:4px 4px 4px 4px' id="div_forma_cobro">
											<div id="div_monto" align="left" style="display:none; background-color:#C6DEAD;padding-left:2px;padding-top:2px;">
												<span id="span_monto">&nbsp;<?php echo __('Monto') ?>
												<?php if ($validaciones_segun_config)
													echo $obligatorio
													?>
													&nbsp;<input id='monto' name=monto size="7" value="<?php echo $contrato->fields['monto'] ?>" onchange="actualizarMonto();"/>&nbsp;&nbsp;
												</span>
												&nbsp;&nbsp;<?php echo __('Moneda') ?>
<?php if ($validaciones_segun_config)
	echo $obligatorio
	?>
												&nbsp;<?php
echo
Html::SelectQuery($sesion, "SELECT id_moneda,glosa_moneda FROM prm_moneda ORDER BY id_moneda", "id_moneda_monto", $contrato->fields['id_moneda_monto'] > 0 ? $contrato->fields['id_moneda_monto'] : ($contrato->fields['id_moneda'] > 0 ? $contrato->fields['id_moneda'] : $id_moneda_monto), 'onchange="actualizarMonto();"', '', "80");
?>
											</div>
											<div id="div_horas" align="left" style="display:none; vertical-align: top; background-color:#C6DEAD;padding-left:2px;">
												&nbsp;<?php echo __('Horas') ?>
															<?php if ($validaciones_segun_config)
																echo $obligatorio
																?>
												&nbsp;<input name=retainer_horas size="7" value="<?php echo $contrato->fields['retainer_horas'] ?>" style="vertical-align: top;" />
												<!-- Incluiremos un multiselect de usuarios para definir los usuarios de quienes se 
														 desuentan las horas con preferencia -->
<?php if (method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'RetainerUsuarios')) { ?>
													<div id="div_retainer_usuarios" style="display:inline; vertical-align: top; background-color:#C6DEAD;padding-left:2px;">
														&nbsp;<?php echo __('Usuarios') ?>
														&nbsp;<?php echo Html::SelectQuery($sesion, "SELECT usuario.id_usuario, CONCAT_WS(' ', nombre, apellido1, apellido2) FROM usuario JOIN usuario_permiso USING( id_usuario ) WHERE usuario.activo = 1 AND codigo_permiso = 'PRO'", 'usuarios_retainer[]', $usuarios_retainer, TTip($tip_retainer_usuarios) . " class=\"selectMultiple\" multiple size='5' height='60' ", "", "160"); ?> 
													</div>
<?php } ?>
											</div>
											<div id="div_fecha_cap" align="left" style="display:none; background-color:#C6DEAD;padding-left:2px;">
												<table style='border: 0px solid' bgcolor='#C6DEAD'>
<?php if ($cobro) { ?>
														<tr>
															<td>
	<?php echo __('Monto utilizado') ?>:
	<?php if ($validaciones_segun_config)
		echo $obligatorio
		?>
															</td>
															<td align=left>&nbsp;<label style='background-color:#FFFFFF'> <?php echo $cobro->TotalCobrosCap($contrato->fields['id_contrato']) > 0 ? $cobro->TotalCobrosCap($contrato->fields['id_contrato']) : 0; ?> </label></td>
														</tr>
																		<?php } ?>
													<tr>
														<td>
<?php echo __('Fecha inicio') ?>:
<?php if ($validaciones_segun_config)
	echo $obligatorio
	?>
														</td>
														<td align="left">
															<input type="text" name="fecha_inicio_cap" value="<?php echo Utiles::sql2date($contrato->fields['fecha_inicio_cap']) ?>" id="fecha_inicio_cap" size="11" maxlength="10" />
															<img src="<?php echo Conf::ImgDir() ?>/calendar.gif" id="img_fecha_inicio_cap" style="cursor:pointer" />
														</td>
													</tr>
												</table>
											</div>
											<div id="div_escalonada" align="left" style="display:none; background-color:#C6DEAD;padding-left:2px;">
												<div class="template_escalon" id="escalon_1">
													<table style='padding: 5px; border: 0px solid' bgcolor='#C6DEAD'>
														<tr>
															<td valign="bottom" nowrap>
																<div style="display:inline-block; width: 65px;"><?php echo __('Las primeras'); ?> </div>
																<input type="text" name="esc_tiempo[]" id="esc_tiempo_1" size="3" value="<?php if (!empty($contrato->fields['esc1_tiempo'])) echo $contrato->fields['esc1_tiempo']; else echo ''; ?>" onkeyup="ActualizaRango(this.id , this.value);" /> 
																<span><?php echo __('horas trabajadas'); ?> (</span> <div id="esc_rango_1" style="display:inline-block; width: 50px; text-align: center;"><?php echo $rango1; ?></div> <span>) <?php echo __('aplicar'); ?></span>
																<select name="esc_selector[]" id="esc_selector_1" onchange="cambia_tipo_forma(this.value, this.id);">
																	<option value="1" <?php echo!isset($contrato->fields['esc1_monto']) || $contrato->fields['esc1_monto'] == 0 ? 'selected="selected"' : ''; ?>>tarifa</option>
																	<option value="2" <?php echo $contrato->fields['esc1_monto'] > 0 ? 'selected="selected"' : ''; ?> >monto</option>
																</select>
																<span>
																	<span id="tipo_forma_1_1" <?php echo!isset($contrato->fields['esc1_monto']) || $contrato->fields['esc1_monto'] == 0 ? 'style="display: inline-block;"' : 'style="display: none;"'; ?> >
<?php echo Html::SelectQuery($sesion, "SELECT id_tarifa, glosa_tarifa FROM tarifa", "esc_id_tarifa_1", $contrato->fields['esc1_id_tarifa'], 'style="font-size:9pt; width:120px;"'); ?>
																	</span>
																	<span id="tipo_forma_1_2" <?php echo $contrato->fields['esc1_monto'] > 0 ? 'style="display: inline-block;"' : 'style="display: none;"'; ?> >
																		<input type="text" size="7" style="font-size:9pt; width:116px;" id="esc_monto_1" value="<?php if (!empty($contrato->fields['esc1_monto'])) echo $contrato->fields['esc1_monto']; else echo ''; ?>" name="esc_monto[]" />
																	</span>
																</span>
																<span><?php echo __('en'); ?></span> 
<?php echo Html::SelectQuery($sesion, "SELECT id_moneda, glosa_moneda FROM prm_moneda ORDER BY id_moneda", 'esc_id_moneda_1', $contrato->fields['esc1_id_moneda'], 'style="font-size:9pt; width:70px;"'); ?> 
																<span><?php echo __('con'); ?> </span>
																<input type="text" name="esc_descuento[]" id="esc_descuento_1" value="<?php if (!empty($contrato->fields['esc1_descuento'])) echo $contrato->fields['esc1_descuento']; else echo ''; ?>" size="4" /> 
																<span><?php echo __('% dcto.'); ?> </span>
															</td>
														</tr>
													</table>
													<div onclick="agregar_eliminar_escala('escalon_2')" style="cursor:pointer;" >
														<span id="escalon_2_img"><?php echo!isset($contrato->fields['esc2_tiempo']) && $contrato->fields['esc2_tiempo'] <= 0 ? '<img src="' . Conf::ImgDir() . '/mas.gif" border="0" id="datos_cobranza_img"> ' . __('Agregar') : '<img src="' . Conf::ImgDir() . '/menos.gif" border="0" id="datos_cobranza_img"> ' . __('Eliminar') ?>	</span>	
													</div>
												</div>
												<div class="template_escalon" id="escalon_2" style="display: <?php echo isset($contrato->fields['esc2_tiempo']) && $contrato->fields['esc2_tiempo'] > 0 ? 'block' : 'none'; ?>;">
													<table style='padding: 5px; border: 0px solid' bgcolor='#C6DEAD'>
														<tr>
															<td valign="bottom" nowrap>
																<div style="display:inline-block; width: 65px;"><?php echo __('Las siguientes'); ?> </div>
																<input type="text" name="esc_tiempo[]" id="esc_tiempo_2" size="3" value="<?php if (!empty($contrato->fields['esc2_tiempo'])) echo $contrato->fields['esc2_tiempo']; else echo ''; ?>" onkeyup="ActualizaRango(this.id , this.value);" /> 
																<span><?php echo __('horas trabajadas'); ?> (</span> <div id="esc_rango_2" style="display:inline-block; width: 50px; text-align: center;"><?php echo $rango2; ?></div> <span>) <?php echo __('aplicar'); ?></span>
																<select name="esc_selector[]" id="esc_selector_2" onchange="cambia_tipo_forma(this.value, this.id);">
																	<option value="1" <?php echo!isset($contrato->fields['esc2_monto']) || $contrato->fields['esc1_monto'] == 0 ? 'selected="selected"' : ''; ?>>tarifa</option>
																	<option value="2" <?php echo $contrato->fields['esc2_monto'] > 0 ? 'selected="selected"' : ''; ?> >monto</option>
																</select>
																<span>
																	<span id="tipo_forma_2_1" <?php echo!isset($contrato->fields['esc2_monto']) || $contrato->fields['esc2_monto'] == 0 ? 'style="display: inline-block;"' : 'style="display: none;"'; ?> >
<?php echo Html::SelectQuery($sesion, "SELECT id_tarifa, glosa_tarifa FROM tarifa", "esc_id_tarifa_2", $contrato->fields['esc2_id_tarifa'], 'style="font-size:9pt; width:120px;"'); ?>
																	</span>
																	<span id="tipo_forma_2_2" <?php echo $contrato->fields['esc2_monto'] > 0 ? 'style="display: inline-block;"' : 'style="display: none;"'; ?> >
																		<input type="text" size="7" style="font-size:9pt; width:116px;" id="esc_monto_2" name="esc_monto[]" value="<?php if (!empty($contrato->fields['esc2_monto'])) echo $contrato->fields['esc2_monto']; else echo ''; ?>" />
																	</span>
																</span>
																<span><?php echo __('en'); ?></span> 
<?php echo Html::SelectQuery($sesion, "SELECT id_moneda, glosa_moneda FROM prm_moneda ORDER BY id_moneda", 'esc_id_moneda_2', $contrato->fields['esc2_id_moneda'], 'style="font-size:9pt; width:70px;"'); ?> 
																<span><?php echo __('con'); ?> </span>
																<input type="text" name="esc_descuento[]" value="<?php if (!empty($contrato->fields['esc2_descuento'])) echo $contrato->fields['esc2_descuento']; else echo ''; ?>" id="esc_descuento_2" size="4" /> 
																<span><?php echo __('% dcto.'); ?> </span>
															</td>
														</tr>
													</table>
													<div onclick="agregar_eliminar_escala('escalon_3')" style="cursor:pointer;" >
														<span id="escalon_3_img"><?php echo!isset($contrato->fields['esc3_tiempo']) && $contrato->fields['esc3_tiempo'] <= 0 ? '<img src="' . Conf::ImgDir() . '/mas.gif" border="0" id="datos_cobranza_img"> ' . __('Agregar') : '<img src="' . Conf::ImgDir() . '/menos.gif" border="0" id="datos_cobranza_img"> ' . __('Eliminar') ?>	</span>	
													</div>
												</div>
												<div class="template_escalon" id="escalon_3" style="display: <?php echo isset($contrato->fields['esc3_tiempo']) && $contrato->fields['esc3_tiempo'] > 0 ? 'block' : 'none'; ?>;">
													<table style='padding: 5px; border: 0px solid' bgcolor='#C6DEAD'>
														<tr>
															<td valign="bottom" nowrap>
																<div style="display:inline-block; width: 65px;"><?php echo __('Las siguientes'); ?> </div>
																<input type="text" name="esc_tiempo[]" id="esc_tiempo_3" size="3" value="<?php if (!empty($contrato->fields['esc3_tiempo'])) echo $contrato->fields['esc3_tiempo']; else echo ''; ?>" onkeyup="ActualizaRango(this.id , this.value);" /> 
																<span><?php echo __('horas trabajadas'); ?> (</span> <div id="esc_rango_3" style="display:inline-block; width: 50px; text-align: center;"><?php echo $rango3; ?></div> <span>) <?php echo __('aplicar'); ?></span>
																<select name="esc_selector[]" id="esc_selector_3" onchange="cambia_tipo_forma(this.value, this.id);">
																	<option value="1" <?php echo!isset($contrato->fields['esc3_monto']) || $contrato->fields['esc1_monto'] == 0 ? 'selected="selected"' : ''; ?>>tarifa</option>
																	<option value="2" <?php echo $contrato->fields['esc3_monto'] > 0 ? 'selected="selected"' : ''; ?> >monto</option>
																</select>
																<span>
																	<span id="tipo_forma_3_1" <?php echo!isset($contrato->fields['esc3_monto']) || $contrato->fields['esc3_monto'] == 0 ? 'style="display: inline-block;"' : 'style="display: none;"'; ?> >
<?php echo Html::SelectQuery($sesion, "SELECT id_tarifa, glosa_tarifa FROM tarifa", "esc_id_tarifa_3", $contrato->fields['esc3_id_tarifa'], 'style="font-size:9pt; width:120px;"'); ?>
																	</span>
																	<span id="tipo_forma_3_2" <?php echo $contrato->fields['esc3_monto'] > 0 ? 'style="display: inline-block;"' : 'style="display: none;"'; ?> >
																		<input type="text" size="7" style="font-size:9pt; width:116px;" id="esc_monto_3" name="esc_monto[]" value="<?php if (!empty($contrato->fields['esc3_monto'])) echo $contrato->fields['esc3_monto']; else echo ''; ?>" />
																	</span>
																</span>
																<span><?php echo __('en'); ?></span> 
<?php echo Html::SelectQuery($sesion, "SELECT id_moneda, glosa_moneda FROM prm_moneda ORDER BY id_moneda", 'esc_id_moneda_3', $contrato->fields['esc3_id_moneda'], 'style="font-size:9pt; width:70px;"'); ?> 
																<span><?php echo __('con'); ?> </span>
																<input type="text" name="esc_descuento[]" id="esc_descuento_3" value="<?php if (!empty($contrato->fields['esc3_descuento'])) echo $contrato->fields['esc3_descuento']; else echo ''; ?>" size="4" /> 
																<span><?php echo __('% dcto.'); ?> </span>
															</td>
														</tr>
													</table>
												</div>
												<div class="template_escalon" id="escalon_4">
													<table style='padding: 5px; border: 0px solid' bgcolor='#C6DEAD'>
														<tr>
															<td valign="bottom" nowrap>
																<div style="display:inline-block; width: 200px;"><?php echo __('Para el resto de horas trabajadas, aplicar'); ?> </div> 
																<input type="hidden" name="esc_tiempo[]" id="esc_tiempo_4" value="-1" size="3" onkeyup="ActualizaRango(this.id , this.value);" /> 
																<select name="esc_selector[]" id="esc_selector_4" onchange="cambia_tipo_forma(this.value, this.id);">
																	<option value="1" <?php echo!isset($contrato->fields['esc4_monto']) || $contrato->fields['esc1_monto'] == 0 ? 'selected="selected"' : ''; ?>>tarifa</option>
																	<option value="2" <?php echo $contrato->fields['esc4_monto'] > 0 ? 'selected="selected"' : ''; ?> >monto</option>
																</select>
																<span>
																	<span id="tipo_forma_4_1" <?php echo!isset($contrato->fields['esc4_monto']) || $contrato->fields['esc4_monto'] == 0 ? 'style="display: inline-block;"' : 'style="display: none;"'; ?> >
												<?php echo Html::SelectQuery($sesion, "SELECT id_tarifa, glosa_tarifa FROM tarifa", "esc_id_tarifa_4", $contrato->fields['esc4_id_tarifa'], 'style="font-size:9pt; width:120px;"'); ?>
																	</span>
																	<span id="tipo_forma_4_2" <?php echo $contrato->fields['esc4_monto'] > 0 ? 'style="display: inline-block;"' : 'style="display: none;"'; ?>>
																		<input type="text" size="7" style="font-size:9pt; width:116px;" id="esc_monto_4" value="<?php if (!empty($contrato->fields['esc4_monto'])) echo $contrato->fields['esc4_monto']; else echo ''; ?>" name="esc_monto[]" />
																	</span>
																</span>
																<span><?php echo __('en'); ?></span> 
<?php echo Html::SelectQuery($sesion, "SELECT id_moneda, glosa_moneda FROM prm_moneda ORDER BY id_moneda", 'esc_id_moneda_4', $contrato->fields['esc4_id_moneda'], 'style="font-size:9pt; width:60px;"'); ?> 
																<span><?php echo __('con'); ?> </span>
																<input type="text" name="esc_descuento[]" id="esc_descuento_4" value="<?php echo $contrato->fields['esc4_descuento']; ?>" size="4" /> 
																<span><?php echo __('% dcto.'); ?> </span> 
															</td>
														</tr>
													</table>
												</div>
											</div>								
										</div>

										<table id="tabla_hitos" width='93%' style='border-top: 1px solid #454545; border-right: 1px solid #454545; border-left:1px solid #454545;	border-bottom:1px solid #454545;' cellpadding="3" cellspacing="3" style="border-collapse:collapse;">
											<thead>
												<tr bgcolor=#6CA522>
													<td width="27%">Fecha Recordatorio</td>
													<td width="45%">Descripción</td>
													<td width="23%">Monto</td>
													<td width="5%">&nbsp;</td>
												</tr>
											</thead>
											<tbody id="body_hitos">
<?php
$query = "SELECT fecha_cobro, descripcion, monto_estimado, id_cobro, observaciones FROM cobro_pendiente WHERE id_contrato='" . $contrato->fields['id_contrato'] . "' AND hito = '1' ORDER BY id_cobro_pendiente";
$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
for ($i = 2; $temp = mysql_fetch_array($resp); $i++) {
	$disabled = empty($temp['id_cobro']) ? '' : ' disabled="disabled" ';
	?>
													<tr bgcolor="<?php echo $i % 2 == 0 ? $color_par : $color_impar ?>" id="fila_hito_<?php echo $i ?>" >
														<td align="center" nowrap>
															<input type="text" name="hito_fecha[<?php echo $i ?>]" value='<?php echo Utiles::sql2date($temp['fecha_cobro']) ?>' id="hito_fecha_<?php echo $i ?>" size="11" maxlength="10" <?php echo $disabled ?>/>
	<?php if (!$disabled) { ?><img src="<?php echo Conf::ImgDir() ?>/calendar.gif" id="img_fecha_hito_<?php echo $i ?>" style="cursor:pointer" /><?php } ?>
															<br/>
															<span style="float:right">Observaciones:</span>
														</td>
														<td align="left">
															<input type="text" name="hito_descripcion[<?php echo $i ?>]" value='<?php echo $temp['descripcion'] ?>' id="hito_descripcion_<?php echo $i ?>" size="40" <?php echo $disabled ?>/>
															<br/>
															<input type="text" name="hito_observaciones[<?php echo $i ?>]" value='<?php echo $temp['observaciones'] ?>' id="hito_observaciones_<?php echo $i ?>" size="40" <?php echo $disabled ?>/>
														</td>
														<td align="right" nowrap>
															<span class="moneda_tabla"></span>&nbsp;
															<input type="text" name="hito_monto_estimado[<?php echo $i ?>]" value='<?php echo empty($temp['monto_estimado']) ? '' : number_format($temp['monto_estimado'], 2, '.', '') ?>' id="hito_monto_estimado_<?php echo $i ?>" size="7" <?php echo $disabled ?>/>
														</td>
														<td align="center">
	<?php if (!$disabled) { ?><img src='<?php echo Conf::ImgDir() ?>/eliminar.gif' style='cursor:pointer' onclick='eliminarHito(this);' /><?php } ?>
														</td>
													</tr>
										<?php } ?>
												<tr bgcolor="<?php echo $i % 2 == 0 ? $color_par : $color_impar ?>" id="fila_hito_1">
													<td align="center" nowrap>
														<input type="text" name="hito_fecha[1]" value='' id="hito_fecha_1" size="11" maxlength="10" />
														<img src="<?php echo Conf::ImgDir() ?>/calendar.gif" id="img_fecha_hito_1" style="cursor:pointer" />
														<br/>
														<span style="float:right">Observaciones:</span>
													</td>
													<td align="left">
														<input type="text" name="hito_descripcion[1]" value='' id="hito_descripcion_1" size="40" />
														<br/>
														<input type="text" name="hito_observaciones[1]" value='' id="hito_observaciones_1" size="40" />
													</td>
													<td align="right" nowrap>
														<span class="moneda_tabla"></span>&nbsp;
														<input type="text" name="hito_monto_estimado[1]" value='' id="hito_monto_estimado_1" size="7" />
													</td>
													<td align="center">
														<img src="<?php echo Conf::ImgDir() ?>/mas.gif" style="cursor:pointer" onclick="agregarHito();" />
													</td>
												</tr>
											</tbody>
										</table>
									</td>
								</tr>
								<tr><td colspan="2">&nbsp;</td></tr>
								<tr>
									<td class="ar " >
<?php echo __('Mostrar total en') ?>:
<?php if ($validaciones_segun_config)
	echo $obligatorio
	?>
									</td>
									<td align="left">
<?php echo Html::SelectQuery($sesion, "SELECT id_moneda, glosa_moneda FROM prm_moneda ORDER BY id_moneda", 'opc_moneda_total', $contrato->fields['opc_moneda_total'] ? $contrato->fields['opc_moneda_total'] : $opc_moneda_total, 'style="font-size:10pt;"', '', '60') ?>
										<span id="monedas_para_honorarios_y_gastos" style="display: none">
<?php echo __('para honorarios y en'); ?>
<?php echo Html::SelectQuery($sesion, "SELECT id_moneda, glosa_moneda FROM prm_moneda ORDER BY id_moneda", 'opc_moneda_gastos', $contrato->fields['opc_moneda_gastos'], ' style="font-size:10pt;"', '', '60'); ?>
<?php echo __('para gastos'); ?>.
										</span>
									</td>
								</tr>
								<tr>
									<td colspan="2"><hr size="1"></td>
								</tr>
								<tr>
									<td class="ar ">
<?php echo __('Descuento') ?>
									</td>
									<td align="left">
											<div   class="controls controls-row ">
												<input style="float:left;" type=text class="span2" name=descuento id=descuento size=6 value=<?php echo $contrato->fields['descuento'] ?>> <div class="span1"> <input type=radio name=tipo_descuento id=tipo_descuento value='VALOR' <?php echo $contrato->fields['tipo_descuento'] == 'VALOR' ? 'checked="checked"' : '' ?> /><span class="inline-help"><?php echo __('Valor') ?></span>
												</div>
											</div>
										<br>
										<div   class="controls controls-row ">
											<input class="span2"   style="float:left;"  type=text name=porcentaje_descuento id=porcentaje_descuento size=6 value=<?php echo $contrato->fields['porcentaje_descuento'] ?>> <div class="span1">  <input type=radio name=tipo_descuento id=tipo_descuento value='PORCENTAJE' <?php echo $contrato->fields['tipo_descuento'] == 'PORCENTAJE' ? 'checked="checked"' : '' ?> /> <span class="inline-help">  <?php echo __('%') ?></span>
												</div>
											</div>
									</td>
								</tr>
								<tr>
									<td colspan="2"><hr size="1"></td>
								</tr>
								<tr>
									<td class="ar ">
<?php echo __('Detalle Cobranza') ?>
															<?php if ($validaciones_segun_config)
																echo $obligatorio
																?>
									</td>
									<td align="left">
										<textarea name="observaciones" rows="3" cols="47"><?php echo $contrato->fields['observaciones'] ? $contrato->fields['observaciones'] : '' ?></textarea>
									</td>
								</tr>
								<tr>
									<td colspan="2"><hr size="1"></td>
								</tr>
								<tr>
									<td colspan="2" align="center">
										<fieldset style="width: 97%; background-color: #FFFFFF;">
											<legend <?php echo!$div_show ? 'onClick="MuestraOculta(\'datos_tramites\')" style="cursor:pointer"' : '' ?> />
<?php echo!$div_show ? '<span id="datos_tramites_img"><img src="' . Conf::ImgDir() . '/mas.gif" border="0" id="datos_tramites_img"></span>' : '' ?>
											&nbsp;<?php echo __('Tr&aacute;mites') ?>
											</legend>
											<div id='datos_tramites' style="display:<?php echo $show ?>;" width="100%">
												<table width="100%">
													<tr>
														<td align="right" width="25%">
<?php echo __('Tarifa Tr&aacute;mites') ?>
														</td>
														<td align="left" width="75%">
															<?php echo Html::SelectQuery($sesion, "SELECT tramite_tarifa.id_tramite_tarifa, tramite_tarifa.glosa_tramite_tarifa FROM tramite_tarifa ORDER BY tramite_tarifa.glosa_tramite_tarifa", "id_tramite_tarifa", $contrato->fields['id_tramite_tarifa'] ? $contrato->fields['id_tramite_tarifa'] : $tramite_tarifa_default, ""); ?>&nbsp;&nbsp;
															<?php echo __('Tarifa en') ?>
<?php echo Html::SelectQuery($sesion, "SELECT id_moneda,glosa_moneda FROM prm_moneda ORDER BY id_moneda", "id_moneda_tramite", $contrato->fields['id_moneda_tramite'] ? $contrato->fields['id_moneda_tramite'] : $id_moneda_tramite, 'onchange="actualizarMoneda();"', '', "80"); ?>&nbsp;&nbsp;
<?php if ($tarifa_permitido) { ?>
																<span style='cursor:pointer' <?php echo TTip(__('Agregar nueva tarifa')) ?> onclick='CreaTramiteTarifa(this.form,true)'><img src="<?php echo Conf::ImgDir() ?>/mas.gif" border="0"></span>
																<span style='cursor:pointer' <?php echo TTip(__('Editar tarifa seleccionada')) ?> onclick='CreaTramiteTarifa(this.form,false)'><img src="<?php echo Conf::ImgDir() ?>/editar_on.gif" border="0"></span>
<?php } ?>
														</td>
													</tr>
												</table>
											</div>
										</fieldset>
									</td>
								</tr>

<?php
$query = "SELECT MAX(fecha_creacion) FROM cobro WHERE id_contrato='" . $contrato->fields['id_contrato'] . "' AND estado!='CREADO' AND estado!='EN REVISION'";
$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
list($ultimo_cobro) = mysql_fetch_array($resp);
?>
								<tr>
									<td colspan="2" align="center">
										<fieldset style="width: 97%; background-color: #FFFFFF;">
											<legend <?php echo!$div_show ? 'onClick="MuestraOculta(\'datos_cobros_programados\')" style="cursor:pointer"' : '' ?> />
<?php echo!$div_show ? '<span id="datos_cobros_programados_img"><img src="' . Conf::ImgDir() . '/mas.gif" border="0" id="datos_cobros_programados_img"></span>' : '' ?>
											&nbsp;<?php echo __('Cobros Programados') ?>
											</legend>
											<div id='datos_cobros_programados' style='display:<?php echo $show ?>;' width="100%">
												<table width="100%">
													<tr>
														<td align="right" width="30%">
<?php echo __('Generar ') . __('Cobros') . __(' a partir del') ?>
														</td>
														<td align="left">
															<input type="text" name="periodo_fecha_inicio" value="<?php echo $fecha_ini ?>" id="periodo_fecha_inicio" size="11" maxlength="10" />
															<img src="<?php echo Conf::ImgDir() ?>/calendar.gif" id="img_periodo_fecha_inicio" style="cursor:pointer" />
															&nbsp;<?php echo $ultimo_cobro ? '<span style="font-size:10px">' . __('Fecha último cobro emitido:') . ' ' . Utiles::sql2date($ultimo_cobro) . '</span>' : '' ?>
														</td>
													</tr>
													<tr>
														<td align="right">
<?php echo __('Cobrar cada') ?>
														</td>
														<td align="left">
															<input type="text" name="periodo_intervalo" value="<?php echo empty($contrato->fields['periodo_intervalo']) ? '1' : $contrato->fields['periodo_intervalo'] ?>" id="periodo_intervalo" size="3" maxlength="2" />
															<span style='font-size:10px'><?php echo __('meses') ?></span>
														</td>
													</tr>
													<tr>
														<td align="right">
<?php echo __('Durante') ?>
														</td>
														<td align="left">
															<input  type="text" name=periodo_repeticiones id=periodo_repeticiones size=3 value="<?php echo $contrato->fields['periodo_repeticiones'] ?>" />
															<span style='font-size:10px'><?php echo __('periodos (0 para perpetuidad)') ?></span>
														</td>
													</tr>
													<tr>
														<td align="center">
															<b><?php echo __('Próximos Cobros') ?></b>&nbsp;<img src="<?php echo Conf::ImgDir() ?>/reload_16.png" onclick='generarFechas()' style='cursor:pointer' <?php echo TTip(__('Actualizar fechas según período')) ?>>
														</td>
														<td>&nbsp;</td>
													</tr>
													<tr>
														<td align="center" colspan="2">
															<table id="tabla_fechas" class="span8" style='width:80%;border-top: 1px solid #454545; border-right: 1px solid #454545; border-left:1px solid #454545;	border-bottom:1px solid #454545;' cellpadding="2" cellspacing="2" style="border-collapse:collapse;">
																<thead>
																	<tr bgcolor=#6CA522>
																		<td width="110">Fecha</td>
																		<td  >Descripción</td>
																		<td width="23%">Monto</td>
																		<td width="5%">&nbsp;</td>
																	</tr>
																</thead>
																<tbody id="id_body">
																	<tr id="fila_fecha_1">
																		<td align="center" class="span2">
																			<input type="text" class="input-small fechadiff" name="valor_fecha[1]" value='' id="valor_fecha_1" size="10" maxlength="10" />
																			 
																		</td>
																		<td align="left">
																			<input type="text" name="valor_descripcion[1]" value='' id="valor_descripcion_1" size="40" />
																		</td>
																		<td align="right">
																			 
																			
																		<div class="input-prepend input">
																			<span class="moneda_tabla add-on"></span><input type="text"  class="span2"   name="valor_monto_estimado[1]" value='' id="valor_monto_estimado_1" size="7" />
																		</div>
																		</td>
																		<td align="center">
																			<img src="<?php echo Conf::ImgDir() ?>/mas.gif" id="img_mas" style="cursor:pointer" onclick="agregarFila();" />
																		</td>
																	</tr>
<?php 

$color_par = "#f0f0f0";
$color_impar = "#ffffff";
$query = "SELECT cp.fecha_cobro,cp.descripcion,cp.monto_estimado FROM cobro_pendiente cp WHERE cp.id_contrato='" . $contrato->fields['id_contrato'] . "' AND cp.id_cobro IS NULL AND cp.hito = '0' ORDER BY fecha_cobro";
$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
for ($i = 2; $temp = mysql_fetch_array($resp); $i++) {
	?>
																		<tr bgcolor=<?php echo $i % 2 == 0 ? $color_par : $color_impar ?> id="fila_fecha_<?php echo $i ?>" class="<?php echo $i > 6 ? 'esconder' : 'mostrar' ?>">
																			<td align="center">
																				<input type='hidden' class="fecha" value="<?php echo Utiles::sql2date($temp['fecha_cobro']) ?>" id='valor_fecha_<?php echo $i ?>' name='valor_fecha[<?php echo $i ?>]'><?php echo Utiles::sql2date($temp['fecha_cobro']) ?>
																			</td>
																			<td align="left">
																				<input size="40" type='text' class="descripcion" value="<?php echo $temp['descripcion'] ?>" id='valor_descripcion_<?php echo $i ?>' name='valor_descripcion[<?php echo $i ?>]'>
																			</td>
																			<td align="right">
																				<span class="moneda_tabla" align="center"></span>&nbsp;
																				<input class="monto_estimado" size="7" type='text' align="right" value="<?php echo empty($temp['monto_estimado']) ? '' : $temp['monto_estimado'] ?>" id='valor_monto_estimado_<?php echo $i ?>' name='valor_monto_estimado[<?php echo $i ?>]'>
																			</td>
																			<td align="center">
																				<img src='<?php echo Conf::ImgDir() ?>/eliminar.gif' style='cursor:pointer' onclick='eliminarFila(this.parentNode.parentNode.rowIndex);' />
																			</td>
																		</tr>
	<?php
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
						<legend <?php echo!$div_show ? 'onClick="MuestraOculta(\'datos_alertas\')" style="cursor:pointer"' : '' ?> >
<?php echo!$div_show ? '<span id="datos_alertas_img"><img src="' . Conf::ImgDir() . '/mas.gif" border="0" id="datos_alertas_img"></span>' : '' ?>
							&nbsp;<?php echo __('Alertas') ?></legend>
						<table id="datos_alertas"  style='display:<?php echo $show ?>'>
							<tr>
								<td colspan="4">
									<table>
										<tr>
											<td rowspan="3"><?php echo __('Si se superan estos límites, el sistema enviará un email de alerta a:'); ?></td>
											<td>
												<label for="notificar_encargado_principal"> <input type="hidden" name="notificar_encargado_principal" value="0"/><input type="checkbox" name="notificar_encargado_principal" id="notificar_encargado_principal" value="1" <?php echo $contrato->fields['notificar_encargado_principal'] == '1' ? 'checked="checked"' : ''; ?> />
												<?php echo __('Encargado Comercial'); ?></label>
											</td>
										</tr>
<?php if (UtilesApp::GetConf($sesion, 'EncargadoSecundario')) { ?>
											<tr>
												<td>
													<label for="notificar_encargado_secundario"> <input type="hidden" name="notificar_encargado_secundario" value="0"/><input type="checkbox" name="notificar_encargado_secundario" id="notificar_encargado_secundario" value="1" <?php echo $contrato->fields['notificar_encargado_secundario'] == '1' ? 'checked="checked"' : ''; ?> />
													<?php echo __('Encargado Secundario'); ?></label>
												</td>
											</tr>
<?php } ?>
										<tr>
											<td>
												<label for="enviar_alerta_otros_correos"> <input type="hidden" name="enviar_alerta_otros_correos" value="0"/><input type="checkbox" name="enviar_alerta_otros_correos" id="enviar_alerta_otros_correos" value="1" <?php echo $contrato->fields['notificar_otros_correos'] != '' ? 'checked="checked"' : ''; ?> />
												<?php echo __('Otros'); ?></label><br />
												<input type="text" name="notificar_otros_correos" size="65" value="<?php echo $contrato->fields['notificar_otros_correos']; ?>" />
												<br />
												<small><em>Separados por coma <strong>(,)</strong> Ej: correo@dominio.com<strong>,</strong>usuario@estudio.net</em></small>
											</td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td align=right>
									<input  type="text" class="span1" name=limite_hh value="<?php echo $contrato->fields['limite_hh'] ? $contrato->fields['limite_hh'] : '0' ?>" title="<?php echo __('Total de Horas') ?>" size=5 />
								</td>
								<td align=left>
									<span title="<?php echo __('Total de Horas') ?>"><?php echo __('Límite de horas') ?></span>
								</td>
								<td align=right>
									<input  type="text" class="span1" name=limite_monto value="<?php echo $contrato->fields['limite_monto'] ? $contrato->fields['limite_monto'] : '0' ?>" title="<?php echo __('Valor Total según Tarifa Hora Hombre') ?>" size=5 />
								</td>
								<td align=left>
									<span title="<?php echo __('Valor Total según Tarifa Hora Hombre') ?>"><?php echo __('Límite de monto') ?></span>
								</td>
							</tr>
							<tr>
								<td align=right>
									<input  type="text" class="span1" name=alerta_hh value="<?php echo $contrato->fields['alerta_hh'] ? $contrato->fields['alerta_hh'] : '0' ?>" title="<?php echo __('Total de Horas en trabajos no cobrados') ?>" size=5 />
								</td>
								<td align=left>
									<span title="<?php echo __('Total de Horas en trabajos no cobrados') ?>"><?php echo __('horas no cobradas') ?></span>
								</td>
								<td align=right>
									<input type="text" class="span1"  name=alerta_monto value="<?php echo $contrato->fields['alerta_monto'] ? $contrato->fields['alerta_monto'] : '0' ?>" title="<?php echo __('Valor Total según Tarifa Hora Hombre en trabajos no cobrados') ?>" size=5 />
								</td>
								<td align=left>
									<span title="<?php echo __('Valor Total según Tarifa Hora Hombre en trabajos no cobrados') ?>"><?php echo __('monto según horas no cobradas') ?></span>
								</td>
							</tr>
						</table>
					</fieldset>

					<br/>
				
					<!-- CARTAS -->
					<fieldset style="width: 97%; background-color: #FFFFFF;">
						<legend <?php echo!$div_show ? 'onClick="MuestraOculta(\'datos_carta\')" style="cursor:pointer"' : '' ?> >
									<?php echo!$div_show ? '<span id="datos_carta_img"><img src="' . Conf::ImgDir() . '/mas.gif" border="0" id="datos_carta_img"></span>' : '' ?>
							&nbsp;<?php echo __('Carta') ?></legend>
						<table   id='datos_carta' style='display:<?php echo $show ?>' width="100%">
							<tr>
								<td align="right" colspan='1' width='25%'>
<?php echo __('Idioma') ?>
								</td>
								<td align="left" colspan="5">
<?php echo Html::SelectQuery($sesion, "SELECT codigo_idioma,glosa_idioma FROM prm_idioma ORDER BY glosa_idioma", "codigo_idioma", $contrato->fields['codigo_idioma'] ? $contrato->fields['codigo_idioma'] : $idioma_default, ' class="span3" ', '', 80); ?>
								</td>
							</tr>
							<tr>
								<td align="right" colspan='1' width='25%'>
							<?php echo __('Formato Carta') ?>
								</td>
								<td align="left" colspan="5">
							<?php echo Html::SelectQuery($sesion, "SELECT carta.id_carta, carta.descripcion FROM carta ORDER BY id_carta", "id_carta", $contrato->fields['id_carta'], ' class="span3" ' ); ?>
								</td>
							</tr>
							<tr>
								<td align="right" colspan='1' width='25%'>
							<?php echo __('Formato Detalle Carta') ?>
								</td>
								<td align="left" colspan="5">
							<?php echo Html::SelectQuery($sesion, "SELECT cobro_rtf.id_formato, cobro_rtf.descripcion FROM cobro_rtf ORDER BY cobro_rtf.id_formato", "id_formato", $contrato->fields['id_formato'], ' class="span3" '); ?>
								</td>
							</tr>
							<tr>
								<td align="right" colspan='1'><?php echo __('Tamaño del papel') ?>:</td>
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
<?php


if (empty($contrato->fields['id_contrato']) && method_exists('Conf', 'GetConf')) {
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
	$contrato->Edit('opc_ver_concepto_gastos', Conf::GetConf($sesion, 'OpcVerConceptoGastos') == 1 ? 1 : 0);
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
								<td align="right" colspan='1'><input type="hidden" name="opc_ver_asuntos_separados" value="0"/><input type="checkbox" name="opc_ver_asuntos_separados"  value="1" <?php echo $contrato->fields['opc_ver_asuntos_separados'] == '1' ? 'checked="checked"' : '' ?>></td>
								<td align="left" colspan='5'><label><?php echo __('Ver asuntos por separado') ?></label></td>
							</tr>
							<tr>
								<td align="right" colspan='1'><input type="hidden" name="opc_ver_resumen_cobro" value="0"/><input type="checkbox" name="opc_ver_resumen_cobro"  value="1" <?php echo $contrato->fields['opc_ver_resumen_cobro'] == '1' ? 'checked="checked"' : '' ?>/></td>
								<td align="left" colspan='5'><label><?php echo __('Mostrar resumen del cobro') ?></label></td>
							</tr>
							<tr>
								<td align="right" colspan='1'><input type="hidden" name="opc_ver_modalidad" value="0"/><input type="checkbox" name="opc_ver_modalidad"  value="1" <?php echo $contrato->fields['opc_ver_modalidad'] == '1' ? 'checked="checked"' : '' ?> /></td>
								<td align="left" colspan='5'><label><?php echo __('Mostrar modalidad del cobro') ?></label></td>
							</tr>
							<tr>
								<td align="right">
									<input type="hidden" name="opc_ver_detalles_por_hora" value="0"/><input type="checkbox" name="opc_ver_detalles_por_hora"  id="opc_ver_detalles_por_hora" value="1" <?php echo ($contrato->fields['opc_ver_detalles_por_hora'] == '1' ) ? 'checked' : '' ?>/>
								</td>
								<td align="left" colspan="2" style="font-size: 10px;">
									<label for="opc_ver_detalles_por_hora"><label><?php echo __('Mostrar detalle por hora') ?></label>
								</td>
							</tr>
							<tr>
								<td/>
								<td align="left" colspan='5'>
									<label class="ckechbox inline" for="opc_ver_detalles_por_hora_iniciales"><input type="hidden" name="opc_ver_detalles_por_hora_iniciales" value="0"/><input type="checkbox" name="opc_ver_detalles_por_hora_iniciales"  id="opc_ver_detalles_por_hora_iniciales" value="1" <?php echo ($contrato->fields['opc_ver_detalles_por_hora_iniciales'] == '1' ) ? 'checked' : '' ?>>
									<?php echo __('Iniciales') ?></label>
									<label class="ckechbox inline"for="opc_ver_detalles_por_hora_categoria"><input type="hidden" name="opc_ver_detalles_por_hora_categoria" value="0"/><input type="checkbox" name="opc_ver_detalles_por_hora_categoria"  id="opc_ver_detalles_por_hora_categoria" value="1" <?php echo ($contrato->fields['opc_ver_detalles_por_hora_categoria'] == '1' ) ? 'checked' : '' ?>>
									<?php echo __('Categoría') ?></label>
									<label class="ckechbox inline"for="opc_ver_detalles_por_hora_tarifa"><input type="hidden" name="opc_ver_detalles_por_hora_tarifa" value="0"/><input type="checkbox" name="opc_ver_detalles_por_hora_tarifa"  id="opc_ver_detalles_por_hora_tarifa" value="1" <?php echo ($contrato->fields['opc_ver_detalles_por_hora_tarifa'] == '1') ? 'checked' : '' ?>>
									<?php echo __('Tarifa') ?></label>
									<label class="ckechbox inline" for="opc_ver_detalles_por_hora_importe"><input type="hidden" name="opc_ver_detalles_por_hora_importe" value="0"/><input type="checkbox" name="opc_ver_detalles_por_hora_importe"  id="opc_ver_detalles_por_hora_importe" value="1" <?php echo ($contrato->fields['opc_ver_detalles_por_hora_importe'] == '1') ? 'checked' : '' ?>>
									<?php echo __('Importe') ?></label>
								</td>
							</tr>
							<tr>
								<td align="right" colspan='1'><input type="checkbox" value="1" name="opc_ver_profesional" <?php echo $contrato->fields['opc_ver_profesional'] == '1' ? 'checked="checked"' : '' ?> /></td>
								<td align="left" colspan='5'><label><?php echo __('Mostrar detalle por profesional') ?></label></td>
							</tr>
							<tr>
								<td/>
								<td align="left" colspan='5'>
									<label class="ckechbox inline" for="opc_ver_profesional_iniciales"><input type="hidden" name="opc_ver_profesional_iniciales" value="0"/><input type="checkbox" name="opc_ver_profesional_iniciales"  id="opc_ver_profesional_iniciales" value="1" <?php echo ($contrato->fields['opc_ver_profesional_iniciales'] == '1') ? 'checked' : '' ?>/>
									<?php echo __('Iniciales') ?></label>
									<label class="ckechbox inline" for="opc_ver_profesional_categoria"><input type="hidden" name="opc_ver_profesional_categoria" value="0"/><input type="checkbox" name="opc_ver_profesional_categoria"  id="opc_ver_profesional_categoria" value="1" <?php echo ($contrato->fields['opc_ver_profesional_categoria'] == '1') ? 'checked' : '' ?>/>
									<?php echo __('Categoría') ?></label>
									<label class="ckechbox inline" for="opc_ver_profesional_tarifa"><input type="hidden" name="opc_ver_profesional_tarifa" value="0"/><input type="checkbox" name="opc_ver_profesional_tarifa"  id="opc_ver_profesional_tarifa" value="1" <?php echo ($contrato->fields['opc_ver_profesional_tarifa'] == '1') ? 'checked' : '' ?>/>
									<?php echo __('Tarifa') ?></label>
									<label class="ckechbox inline"  for="opc_ver_profesional_importe"><input type="hidden" name="opc_ver_profesional_importe" value="0"/><input type="checkbox" name="opc_ver_profesional_importe"  id="opc_ver_profesional_importe" value="1" <?php echo ($contrato->fields['opc_ver_profesional_importe'] == '1') ? 'checked' : '' ?>/>
									<?php echo __('Importe') ?></label>
								</td>
							</tr>
							<tr>
								<td align="right" colspan='1'><input type="hidden" name="opc_ver_descuento" value="0"/><input type="checkbox" name="opc_ver_descuento"  value="1" <?php echo $contrato->fields['opc_ver_descuento'] == '1' ? 'checked="checked"' : '' ?> /></td>
								<td align="left" colspan='5'><label><?php echo __('Mostrar el descuento del cobro') ?></label></td>
							</tr>
							<tr>
								<td align="right" colspan='1'><input type="hidden" name="opc_ver_gastos" value="0"/><input type="checkbox" name="opc_ver_gastos"  value="1" <?php echo $contrato->fields['opc_ver_gastos'] == '1' ? 'checked="checked"' : '' ?> /></td>
								<td align="left" colspan='5'><label><?php echo __('Mostrar gastos del cobro') ?></label></td>
							</tr>
							<?php if (UtilesApp::GetConf($sesion, 'PrmGastos')) { ?>
								<tr>
									<td align="right" colspan='1'><input type="hidden" name="opc_ver_concepto_gastos" value="0"/><input type="checkbox" name="opc_ver_concepto_gastos"  value="1" <?php echo $contrato->fields['opc_ver_concepto_gastos'] == '1' ? 'checked="checked"' : '' ?> /></td>
									<td align="left" colspan='5'><label><?php echo __('Mostrar concepto de gastos') ?></label></td>
								</tr>
							<?php } ?>
							<tr>
								<td align="right" colspan='1'><input type="hidden" name="opc_ver_morosidad" value="0"/><input type="checkbox" name="opc_ver_morosidad"  value="1" <?php echo $contrato->fields['opc_ver_morosidad'] == '1' ? 'checked="checked"' : '' ?> /></td>
								<td align="left" colspan='5'><label><?php echo __('Mostrar saldo adeudado') ?></label></td>
							</tr>
							<tr>
								<td align="right" colspan='1'><input type="hidden" name="opc_ver_tipo_cambio" value="0"/><input type="checkbox" name="opc_ver_tipo_cambio"  value="1" <?php echo $contrato->fields['opc_ver_tipo_cambio'] == '1' ? 'checked="checked"' : '' ?> /></td>
								<td align="left" colspan='5'><label><?php echo __('Mostrar tipos de cambio') ?></label></td>
							</tr>
							<tr>
								<td align="right" colspan='1'><input type="hidden" name="opc_ver_numpag" value="0"/><input type="checkbox" name="opc_ver_numpag"  value="1" <?php echo $contrato->fields['opc_ver_numpag'] == '1' ? 'checked="checked"' : '' ?> /></td>
								<td align="left" colspan='5'><label><?php echo __('Mostrar números de página') ?></label></td>
							</tr>
							<tr>        
								<td align="right"><input type="hidden" name="opc_ver_columna_cobrable" value="0"/><input type="checkbox" name="opc_ver_columna_cobrable"  id="opc_ver_columna_cobrable" value="1" <?php echo $contrato->fields['opc_ver_columna_cobrable'] == '1' ? 'checked' : '' ?>></td>
								<td align="left"  ><label for="opc_ver_numpag"><?php echo __('Mostrar columna cobrable') ?></label></td>
							</tr> <!-- Andres Oestemer -->
<?php
if (method_exists('Conf', 'GetConf')) {
	$solicitante = Conf::GetConf($sesion, 'OrdenadoPor');
} else if (method_exists('Conf', 'Ordenado_por')) {
	$solicitante = Conf::Ordenado_por();
} else {
	$solicitante = 2;
}

if ($solicitante == 0) {  // no mostrar
	?>
								<input type="hidden" name="opc_ver_solicitante" id="opc_ver_solicitante" value="0" />
	<?php
} elseif ($solicitante == 1) { // obligatorio
	?>
								<tr>
									<td align="right" colspan='1'><input type="hidden" name="opc_ver_solicitante" value="0"/><input type="checkbox" name="opc_ver_solicitante"  value="1" <?php echo $contrato->fields['opc_ver_solicitante'] == '1' ? 'checked="checked"' : '' ?>></td>
									<td align="left" colspan='5'><label><?php echo __('Mostrar solicitante') ?></label></td>
								</tr>
	<?php
} elseif ($solicitante == 2) { // opcional
	?>
								<tr>
									<td align="right" colspan='1'><input type="hidden" name="opc_ver_solicitante" value="0"/><input type="checkbox" name="opc_ver_solicitante"  value="1" <?php echo $contrato->fields['opc_ver_solicitante'] == '1' ? 'checked="checked"' : '' ?>></td>
									<td align="left" colspan='5'><label><?php echo __('Mostrar solicitante') ?></label></td>
								</tr>
	<?php
}
?>
							<tr>
								<td align="right" colspan='1'><input type="hidden" name="opc_ver_horas_trabajadas" value="0"/><input type="checkbox" name="opc_ver_horas_trabajadas"  value="1" <?php echo $contrato->fields['opc_ver_horas_trabajadas'] == '1' ? 'checked="checked"' : '' ?> ></td>
								<td align="left" colspan='5'><label><?php echo __('Mostrar horas trabajadas') ?></label></td>
							</tr>
							<tr>
								<td align="right" colspan='1'><input type="hidden" name="opc_ver_cobrable" value="0"/><input type="checkbox" name="opc_ver_cobrable"  value="1" <?php echo $contrato->fields['opc_ver_cobrable'] == '1' ? 'checked="checked"' : '' ?> ></td>
								<td align="left" colspan='5'><label><?php echo __('Mostrar trabajos no visibles') ?></label></td>
							</tr>
<?php if (UtilesApp::GetConf($sesion, 'ResumenProfesionalVial') ) { ?>
								<tr>
									<td align="right" colspan='1'><input type="hidden" name="opc_restar_retainer" value="0"/><input type="checkbox" name="opc_restar_retainer"  value="1" <?php echo $contrato->fields['opc_restar_retainer'] == '1' ? 'checked="checked"' : '' ?>  /></td>
									<td align="left" colspan='5'><label><?php echo __('Restar valor retainer') ?></label></td>
								</tr>
								<tr>
									<td align="right"><input type="hidden" name="opc_ver_detalle_retainer" value="0"/><input type="checkbox" name="opc_ver_detalle_retainer"  value="1" <?php echo $contrato->fields['opc_ver_detalle_retainer'] == '1' ? 'checked="checked"' : '' ?> /></td>
									<td align="left" colspan='5'><label><?php echo __('Mostrar detalle retainer') ?></label></td>
								</tr>
<?php } else { ?>
		<tr>
			<td><input type="hidden" id="opc_restar_retainer" name="opc_restar_retainer" value="1" /></td>
		<td><input type="hidden" id="opc_ver_detalle_retainer" name="opc_ver_detalle_retainer" value="1"/></td>
		</tr>
<?php } ?>								
							<tr>
								<td align="right"><input type="hidden" name="opc_ver_valor_hh_flat_fee" value="0"/><input type="checkbox" name="opc_ver_valor_hh_flat_fee"  value="1" <?php echo $contrato->fields['opc_ver_valor_hh_flat_fee'] == '1' ? 'checked="checked"' : '' ?>/></td>
								<td align="left" colspan='5'><label><?php echo __('Mostrar tarifa proporcional en base a HH'); ?></label></td>
							</tr>
							<tr>
								<td align="right" colspan='1'><input type="hidden" name="opc_ver_carta" value="0"/><input type="checkbox" name="opc_ver_carta"  value="1" onclick="ActivaCarta(this.checked)" <?php echo $contrato->fields['opc_ver_carta'] == '1' ? 'checked="checked"' : '' ?> /></td>
								<td align="left" colspan='5'><label><?php echo __('Mostrar Carta') ?></label></td>
							</tr>
						</table>
					</fieldset>
					<br>
					<!-- FIN CARTAS -->

					<!-- DOCUMENTOS -->
							<?php
							if ($id_cliente || $id_asunto) {
								?>
						<fieldset style="width: 97%; background-color: #FFFFFF;">
							<legend <?php echo!$div_show ? 'onClick="MuestraOculta(\'documentos\')" style="cursor:pointer"' : '' ?> >
								<?php echo!$div_show ? '<span id="documentos_img"><img src="' . Conf::ImgDir() . '/mas.gif" border="0" id="documentos_img"></span>' : '' ?>
								&nbsp;<?php echo __('Documentos') ?></legend>
							<table id='documentos' style='display:<?php echo $show ?>'>
								<tr>
									<td colspan="2" align="center">
						<?php
						$id_contrato_ifr = $contrato->fields['id_contrato'];
						?>
										<iframe  name="iframe_documentos" id="iframe_documentos" src='documentos.php?id_cliente=<?php echo $cliente->fields['id_cliente'] ?>&id_contrato=<?php echo $id_contrato_ifr ?>' frameborder=0 style="width:650px; height:250px;"></iframe>
									</td>
								</tr>
							</table>
						</fieldset>
						<br>
	<?php
} #fin id_cliente OR id_asunto
?>
					<!-- FIN DOCUMENTOS -->

					<!-- ASOCIAR DOC LEGALES -->
									<?php
									if (UtilesApp::GetConf($sesion, 'NuevoModuloFactura')) {
										?>
						<fieldset style="width: 97%; background-color: #FFFFFF;">
							<legend <?php echo!$div_show ? 'onClick="MuestraOculta(\'div_doc_legales_asociados\')" style="cursor:pointer"' : '' ?>>
										<?php echo!$div_show ? '<span id="doc_legales_img"><img src="' . Conf::ImgDir() . '/mas.gif" border="0" id="doc_legales_img"></span>' : '' ?>
								&nbsp;<?php echo __('Documentos legales por defecto') ?>
							</legend>
							<div id="div_doc_legales_asociados" style='display:<?php echo $show ?>'>
								<p><center>Ingrese los documentos legales que desea generar en el proceso de facturación</center></p>
						<?php include dirname(__FILE__) . '/agregar_doc_legales.php'; ?>
							</div>
						</fieldset>
	<?php
}
?>
					<br>
					<!-- ASOCIAR DOC LEGALES -->

					<!-- GUARDAR -->
<?php if ($popup && !$motivo) { ?>
						<fieldset style="width: 97%; background-color: #FFFFFF;">
							<legend><?php echo __('Guardar datos') ?></legend>
							<table>
								<tr>
									<td colspan=6 align="center">
	<?php
	if (UtilesApp::GetConf($sesion, 'RevisarTarifas')) {
		?>
											<input type="button" class=btn value="<?php echo __('Guardar') ?>" onclick="return RevisarTarifas( 'id_tarifa', 'id_moneda', this.form, false);" />
		<?php
	} else {
		?>
											<input type="button" class=btn value="<?php echo __('Guardar') ?>" onclick="ValidarContrato(this.form)" />
		<?php
	}
	?>
									</td>
								</tr>
							</table>
						</fieldset>
<?php } ?>
					<!-- FIN GUARDAR -->

					</fieldset>
					<!-- FIN INFORMACION COMERCIAL GENERAL -->
<?php if ($popup && !$motivo) { ?>
						</form>
<?php } ?>
					<script type="text/javascript">
						jQuery(document).ready(function() {
							ActualizarFormaCobro();
							jQuery(".formacobro").click(function() {
								var laID=jQuery(this).attr('id');
								ActualizarFormaCobro(laID);
							});
						});
     

						function YoucangonowMichael() {
   
<?php if ($contrato->fields['id_cuenta']) echo "SetBanco('id_cuenta','id_banco');"; ?>
							}
	
	
						 
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
							$$('[id^="hito_fecha_"]').each(function(elem){
								Calendar.setup(
								{
									inputField	: elem.id,				// ID of the input field
									ifFormat		: "%d-%m-%Y",			// the date format
									button			: elem.id.replace('hito_fecha_', 'img_fecha_hito_')
								}
							);});
							$$('tr.esconder').each(function(item){item.hide()});
							actualizarMoneda();

<?php
if (UtilesApp::GetConf($sesion, "CopiarEncargadoAlAsunto") && !$desde_agrega_cliente) {
	if (UtilesApp::GetConf($sesion, 'EncargadoSecundario')) {
		echo "if(jQuery('#id_usuario_secundario').length>0) jQuery('#id_usuario_secundario').attr('disabled','disabled');";
	}
	echo "if(jQuery('#id_usuario_encargado').length>0) jQuery('#id_usuario_encargado').attr('disabled','disabled');";
}
?>
					
				
					
					</script>
<?php
echo(InputId::Javascript($sesion));

if ($addheaderandbottom || ($popup && !$motivo))
	$pagina->PrintBottom($popup);
?>
