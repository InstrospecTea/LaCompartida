<?php

require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion(array('COB'));
$autocompletador = (UtilesApp::GetConf($sesion, 'TipoSelectCliente') == 'autocompletador');
$codigoSecundario = (UtilesApp::GetConf($sesion, 'CodigoSecundario'));

$pagina = new Pagina($sesion);
$id_usuario = $sesion->usuario->fields['id_usuario'];

// para manejar el uso de adelantos sin tener que pichicatear las cifras.
if ($_POST['montoadelanto']) {
	$montoadelanto = $_POST['montoadelanto'];
	$monto = $_POST['monto'] = str_replace('-', '', $montoadelanto);
}
if (!$pago) {
	$pago = $_POST['pago'] = $_GET['pago'];
}
if (!$codigo_cliente) {
	$codigo_cliente = $_POST['codigo_cliente'] = $_GET['codigo_cliente'];
}
if (!$id_cobro) {
	$id_cobro = $_POST['id_cobro'] = $_GET['id_cobro'];
}

$documento = new Documento($sesion);
$cobro = new Cobro($sesion);
if ($id_cobro) {
	$cobro->Load($id_cobro);
}

$id_solicitud_adelanto = $_REQUEST['id_solicitud_adelanto'];

if ($id_solicitud_adelanto && !$id_documento && UtilesApp::GetConf($sesion, 'UsarModuloSolicitudAdelantos')) {
	// Para asociar una solicitud al adelanto
	require_once Conf::ServerDir() . '/classes/SolicitudAdelanto.php';

	$SolicitudAdelanto = new SolicitudAdelanto($sesion);
	$SolicitudAdelanto->Load($id_solicitud_adelanto);

	$calculo_solicitud = $sesion->pdodbh->query($SolicitudAdelanto->SearchQuery())->fetch(PDO::FETCH_ASSOC);

	if (empty($calculo_solicitud['saldo_solicitud_adelanto'])) {
		$calculo_solicitud['saldo_solicitud_adelanto'] = $SolicitudAdelanto->fields['monto'];
	}
	// Cargo los datos básicos de un nuevo adelanto
	$documento->fields['monto'] = $calculo_solicitud['saldo_solicitud_adelanto'];
	$documento->fields['id_moneda'] = $SolicitudAdelanto->fields['id_moneda'];
	$documento->fields['glosa_documento'] = $SolicitudAdelanto->fields['descripcion'];
	$documento->fields['id_contrato'] = $SolicitudAdelanto->fields['id_contrato'];
	$id_contrato = $SolicitudAdelanto->fields['id_contrato'];
	$codigo_cliente = $SolicitudAdelanto->fields['codigo_cliente'];
	$codigo_asunto = $SolicitudAdelanto->fields['codigo_asunto'];

	if ($id_contrato && !$codigo_asunto) {
		$Asunto = new Asunto($sesion);
		$Asunto->LoadByContrato($id_contrato);
		$codigo_asunto = $Asunto->fields['codigo_asunto'];
	}
}

$documento_cobro = new Documento($sesion);
$documento_cobro->LoadByCobro($id_cobro);
$id_doc_cobro = $documento_cobro->fields['id_documento'];
$moneda_documento = new Moneda($sesion);
$moneda_documento->Load($documento_cobro->fields['id_moneda']);
$cifras_decimales = $moneda_documento->fields['cifras_decimales'];

$cambios_en_saldo_honorarios = array();
$cambios_en_saldo_gastos = array();

if ($id_documento) {
	$documento->Load($id_documento);

	if (UtilesApp::GetConf($sesion, 'UsarModuloSolicitudAdelantos')) {
		$id_solicitud_adelanto = $documento->fields['id_solicitud_adelanto'];
	}
	($Slim = Slim::getInstance('default', true)) ? $Slim->applyHook('hook_guardar_documento_pago') : false;

	if ($id_cobro) {
		$monto_usado = $documento->MontoUsadoAdelanto($id_cobro);
	}
	($Slim = Slim::getInstance('default', true)) ? $Slim->applyHook('hook_guardar_documento_pago') : false;
}

if ($codigoSecundario && $codigo_cliente_secundario != '') {
	$cliente = new Cliente($sesion);
	$codigo_cliente = $cliente->CodigoSecundarioACodigo($codigo_cliente_secundario);
}

if ($opcion == "guardar") {

	$mensaje1 = 'monto usado ' . $monto_usado . ' POST: ' . addslashes(var_export($_POST, true));
	$mensaje2 = 'GET: ' . addslashes(var_export($_GET, true));

	// Construir arreglo_pagos_detalle
	$datos_neteo = array();
	foreach ($_POST as $key => $val) {
		$pedazos = array_reverse(explode('_', $key));

		if (is_numeric($pedazos[0]) && in_array($pedazos[1], array('honorarios', 'gastos')) && ($pedazos[2] == "pago" || $pedazos[2] == "cobro" )) {
			$sentencia.=$pedazos[0] . " y " . $pedazos[1] . " y " . $pedazos[2] . "\n";
		}

		if (is_numeric($pedazos[0]) && in_array($pedazos[1], array('honorarios', 'gastos')) && $pedazos[2] == "pago") {
			if (!is_array($datos_neteo[$pedazos[0]])) {
				$datos_neteo[$pedazos[0]] = array();
			}
			$datos_neteo[$pedazos[0]][$pedazos[2] . '_' . $pedazos[1]] = $val;
		}
	}

	$arreglo_pagos_detalle = array();
	foreach ($datos_neteo as $llave => $valor) {
		if ($valor['pago_honorarios'] > 0 || $valor['pago_gastos'] > 0) {
			$arreglo_data = array();
			$query = "SELECT id_cobro, id_moneda FROM documento WHERE id_documento = '" . $llave . "'";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
			list($id_cobro_neteo, $id_moneda_neteo) = mysql_fetch_array($resp);

			$arreglo_data['id_moneda'] = $id_moneda_neteo;
			$arreglo_data['id_documento_cobro'] = $llave;
			$arreglo_data['monto_honorarios'] = $valor['pago_honorarios'];
			$arreglo_data['monto_gastos'] = $valor['pago_gastos'];
			$arreglo_data['id_cobro'] = $id_cobro_neteo;
			array_push($arreglo_pagos_detalle, $arreglo_data);
		}
	}

	$nuevo = empty($id_documento);
	$usando_adelanto = $id_documento && !$adelanto && $documento->fields['es_adelanto'];

	$sentencia.=implode("\n", $arreglo_pagos_detalle);
	$testimonio = "INSERT INTO z_log_fff SET fecha = NOW(), mensaje='el bit es $usando_adelanto y sentencias:" . $sentencia . "\n" . $mensaje1 . "\n" . $mensaje2 . "'";

	try {
		$id_documento = $documento->IngresoDocumentoPago($pagina, $id_cobro, $codigo_cliente, $monto, $id_moneda, $tipo_doc, $numero_doc, $fecha, $glosa_documento, $id_banco, $id_cuenta, $numero_operacion, $numero_cheque, $ids_monedas_documento, $tipo_cambios_documento, $arreglo_pagos_detalle, null, $adelanto, $pago_honorarios, $pago_gastos, $usando_adelanto, $id_contrato, !empty($pagar_facturas), $id_usuario_ingresa, $id_usuario_orden, $id_solicitud_adelanto, $codigo_asunto);
	} catch (Exception $e) {
		echo 'Ha ocurrido un problema: <br>' . $e->getMessage() . '<br>';
	}

	$documento->Load($id_documento);
}

if ($documento->Loaded()) {
	$codigo_cliente = $documento->fields['codigo_cliente'];
	$codigo_asunto = $documento->fields['codigo_asunto'];
}

if ($codigoSecundario && $codigo_cliente != '') {
	$cliente = new Cliente($sesion);
	$codigo_cliente_secundario = $cliente->CodigoACodigoSecundario($codigo_cliente);
	if (!empty($codigo_asunto)) {
		$Asunto = new Asunto();
		$codigo_asunto_secundario = $Asunto->CodigoACodigoSecundario($codigo_asunto);
	}
}

$txt_pagina = $id_documento ? (empty($adelanto) ? __('Edición de Pago') : __('Edición del Adelanto')) : (empty($adelanto) ? __('Documento de Pago') : __('Documento de Adelanto'));
$txt_tipo = empty($adelanto) ? __('Documento de Pago') : __('Documento de Adelanto');

$pagina->titulo = $txt_pagina;

$pagina->PrintTop($popup);
?>

<script  type="text/javascript" src="https://static.thetimebilling.com/js/typewatch.js"></script>

<?php if ($opcion == "guardar") { ?>
	<script type="text/javascript">
	if( window.opener.Refrescarse ) {
		window.opener.Refrescarse();
	} else if ( window.opener.Refrescar ) {
		window.opener.Refrescar();
	}
	</script>
<?php } ?>

<script type="text/javascript">
    function formato_numeros() {
		var cantidad_decimales = $('cifras_decimales').value;
		var ceros = "0".times(parseFloat(cantidad_decimales));
		var decimales = '#.' + ceros;
		format = decimales;
		return {format: format, locale: 'us'};
	}

	function Uniformar() {
		jQuery('.saldojq, #monto_pagos, #monto, #monto_aux, #saldo_pago, #saldo_pago_aux').each(function() {
			jQuery(this).val(jQuery(this).val().replace(',','.'));
			jQuery(this).parseNumber(formato_numeros());
			jQuery(this).formatNumber(formato_numeros());
		});
	}

	function Validar(form) {
		var tipopago=jQuery('#tipodocumento').val();

		monto = parseFloat(jQuery('#monto').val());
		// alert(monto);
		if (isNaN(monto) || monto == '') {
			alert('<?php echo __('Debe ingresar un monto para el pago') ?>');
			$('monto').focus();
			return false;
		}
		if(jQuery('#montoadelanto').length>0) {
			saldo_adelanto=parseFloat(jQuery('#saldo_pago').val());
			monto_adelanto=parseFloat(jQuery('#montoadelanto').val().replace('-',''));
			if (saldo_adelanto<0) {
				alert('El adelanto ('+ monto_adelanto +') no alcanza a cubrir la cantidad ingresada ('+ monto +')');
				return false;
			}
		}

		var monto_pagos = Math.round($F('monto_pagos')*1000)/1000;
		var monto_pagos_real=monto_pagos+Number(jQuery('#anteriorduro').val());

		monto = Math.round(monto*1000)/1000;

		<?php if ($codigoSecundario) { ?>
			var cod_cli_seg = $F('<?php echo $autocompletador ? 'codigo_cliente_secundario' : 'campo_codigo_cliente_secundario'; ?>');
			if (cod_cli_seg == '-1' || cod_cli_seg == "") {
				alert('<?php echo __('Debe ingresar un cliente') ?>');
				return false;
			}

		<?php } else { ?>
			var cod_cli = $F('<?php echo $autocompletador ? 'codigo_cliente' : 'campo_codigo_cliente'; ?>');
			if (cod_cli == '-1' || cod_cli == "") {
				alert('<?php echo __('Debe ingresar un cliente') ?>');
				return false;
			}

		<?php } ?>

		if (monto <= 0 || (jQuery('#montoadelanto').length==0 && monto_pagos_real<=0 && jQuery('#elajax').val() != '')) {
			alert('<?php echo __('El monto de un pago debe ser siempre mayor a 0') ?>');
			$('monto').focus();
			return false;
		}

		if (form.glosa_documento.value == "") {
			alert('<?php echo __('Debe ingresar una descripción') ?>');
			form.glosa_documento.focus();
			return false;
		}
		if (monto > monto_pagos && $('es_adelanto') && $F('es_adelanto')!='1') {
			alert("El Monto del documento ("+monto+") es superior a la suma de los Pagos ("+monto_pagos+").");
			return false;
		} else if(monto < monto_pagos) {
			alert("La suma de los Pagos ("+monto_pagos+") es superior al Monto del documento ("+monto+").");
			return false;
		}

		<?php if ($monto_usado !== null) { ?>
			var monto_neteo = $$('input[type="text"][id^="pago_"]').inject(0, function(suma, elem) { return suma + Number($F(elem)); });
			var monto_usado = <?php echo $monto_usado; ?>;
			if(monto_neteo < monto_usado){
				alert('No puede ingresar un monto menor al monto que ya ha sido usado para pagar otras facturas ('+monto_usado+')');
				return false;
			}
		<?php } ?>
		<?php if (!empty($adelanto)) { ?>
			if ($$('input[id^="pago_honorarios_"]:not([value="0"])').length && !$('pago_honorarios').checked){
				alert('El adelanto se ha usado para pagar honorarios. No puede deshabilitar esta opción.');
				return false;
			}
			if ($$('input[id^="pago_gastos_"]:not([value="0"])').length && !$('pago_gastos').checked){
				alert('El adelanto se ha usado para pagar gastos. No puede deshabilitar esta opción.');
				return false;
			}
		<?php } else if (UtilesApp::GetConf($sesion, 'NuevoModuloFactura') && $monto_usado === null) { ?>
			var hayFacturas = $(window.opener.document.documentElement).select('[id^="saldo"]').any(function(e){
				return $(e).next('[id^="id_moneda"][value="'+$F('id_moneda')+'"]');
			});
			if (hayFacturas && confirm('¿Desea usar este adelanto para pagar automáticamente las facturas con saldo pendiente?')){
				$('pagar_facturas').value = '1';
			}
		<?php } ?>
		form.submit();
	}

	function CheckEliminaIngreso(chk) {
		jQuery('#elimina_ingreso').val(chk ? 1 : '');
		return true;
	}

	function procesar_saldo_pagos(self) {
		var total = parseFloat(0);
		var tipopago = jQuery('#tipodocumento').val();
		var anterior = 0;
		self.val(self.val().replace(',','.'));
		jQuery("input[id^='pago_gastos_anterior']").each(function() {
			anterior+=Math.max(0,Number(jQuery(this).val()));
		});
		jQuery("input:hidden[id^='pago_honorarios_anterior']").each(function() {
			anterior+=Math.max(0,Number(jQuery(this).val()));
		});
		MontoValido(self.attr('id'));
		self.val(Math.max(0,Math.min(self.val(),Math.max(0,parseFloat(jQuery('#'+self.attr('id').replace('pago','cobro')).val())))));

		jQuery('.saldojq').each(function() {
			total=parseFloat(total)+parseFloat(jQuery(this).val());
		});

		total = parseFloat(total)+parseFloat(jQuery("#anteriorduro").val());

		if (tipopago!='adelanto') {
			jQuery('#monto').val(total).formatNumber(formato_numeros());
		}
		jQuery('#monto_aux').val(total).formatNumber(formato_numeros());
		jQuery('#monto_pagos').val(total).formatNumber(formato_numeros());

		if (jQuery('#montoadelanto').length>0) {
			monto_adelanto=parseFloat(jQuery('#montoadelanto').val().replace('-',''));
			var delta=jQuery('#monto_pagos').val()-monto_adelanto;
			if (delta>0) {
				alert('Exceso adelanto');
				self.val(self.val()-delta);
				total=total-delta;
				jQuery('#monto_aux').val(Number(total));
				jQuery('#monto_pagos').val(Number(total));
			}
			jQuery('#saldo_pago').val(monto_adelanto-total).formatNumber(formato_numeros());
		}

		if (jQuery('#saldo_pago_aux').length>0) {
			saldopagomaximo=anterior+jQuery('#saldo_pago_aux').val()*1.000;
			if (total>saldopagomaximo) {
				total=saldopagomaximo;
			}
			jQuery('#monto').val(total).formatNumber(formato_numeros());
			if (tipopago!='documento' && tipopago!='adelanto') {
				SetMontoPagos();
			}

			jQuery('#saldo_pago').val(saldopagomaximo-total) ;
		}
		jQuery('#saldo_pago').formatNumber(formato_numeros());
	}

	jQuery('document').ready(function() {

		jQuery('#id_moneda').bind('change', function() {
			var tipopago = jQuery('#tipodocumento').val();
			if (tipopago == 'editaadelanto') {
				return false;
			}
			if (tipopago == 'nuevoadelanto') {
				return true;
			}
			var oldmoneda = jQuery('#moneda_aux').val();
			var newmoneda = jQuery('#id_moneda').val();
			var oldtasa = jQuery('#documento_moneda_' + oldmoneda).val();
			var newtasa = jQuery('#documento_moneda_' + newmoneda).val();

			jQuery('#moneda_aux').val(jQuery('#id_moneda').val());

			CargarTabla(1, oldtasa, newtasa);
		});

		jQuery('.saldojq').live('focus', function() {
			var self = jQuery(this);
			jQuery(this).typeWatch({
				callback: function() {
					procesar_saldo_pagos(self);
				},
				wait: 700,
				highlight: false,
				captureLength: 1
			});
		});

		jQuery('#pago_honorarios').live('change', function(){
			if(jQuery('#pago_honorarios').is(':checked')){
				jQuery('#acepta_honorarios').val('1');
			} else {
				jQuery('#acepta_honorarios').val('0');
			};
			CargarTabla(1);
		});

		jQuery('#pago_gastos').live('change', function(){
			if(jQuery('#pago_gastos').is(':checked')){
				jQuery('#acepta_gastos').val('1');
			} else {
				jQuery('#acepta_gastos').val('0');
			};
			CargarTabla(1);
		});

		jQuery('#monto').typeWatch({
			callback: function() {
				Uniformar();
				Repartir();
				SetMontoPagos();

			},
			wait: 700,
			highlight: false,
			captureLength: 1
		});

	});



	function Repartir() {
		var monto_tmp=0;
		var saldo=(1.0000*jQuery('#monto').val()-1.0000*jQuery("#anteriorduro").val());
		if (saldo<0 || jQuery('.saldojq').length==0) {
			if (Number(jQuery("#anteriorduro").val())>0){
				jQuery('#monto').val(jQuery("#anteriorduro").val());
			}
			return false;
		}
		//console.log('a repartir: '+saldo);
		jQuery('.saldojq').each(function() {

			jQuery(this).val(Math.min(saldo, Math.max(0,Number(jQuery('#'+jQuery(this).attr('id').replace('pago','cobro')).val()))));

			saldo=saldo-jQuery(this).parseNumber(formato_numeros());

			if (saldo<0) {
				saldo=0;
			} else {
				monto_tmp=    monto_tmp+jQuery(this).parseNumber(formato_numeros());
			}
			jQuery(this).formatNumber(formato_numeros());
		});
		if(monto_tmp>0) {
			jQuery('#monto, #monto_pagos').val(1.000 * (monto_tmp) + 1.000 * jQuery("#anteriorduro").val());

		}

	}

	function CargarTabla(actualizar, oldtasa, newtasa) {
		<?php
		if (!empty($adelanto) && !$id_documento) {
			echo 'return;';
		}
		?>
		if (!oldtasa) {
			var oldtasa = 1;
		}
		if (!newtasa) {
			var newtasa = 1;
		}
		var tipopago = jQuery('#tipodocumento').val();
		var anterior = 0;
		var select_moneda = jQuery('#id_moneda').val();
		var id_documento = jQuery('#id_documento').val();
		var total = 0;
		var id_cobro = '<?php echo $id_cobro ? $id_cobro : 0; ?>';

		<?php if ($codigoSecundario) { ?>
			var codigo_cliente_secundario = $F('<?php echo gh ? 'codigo_cliente_secundario' : 'campo_codigo_cliente_secundario'; ?>');
			var url = root_dir + '/app/interfaces/ajax_pago_documentos.php?id_moneda=' + select_moneda + '&codigo_cliente_secundario=' + codigo_cliente_secundario+'&id_cobro='+id_cobro;
		<?php } else { ?>
			var codigo_cliente = $F('<?php echo $autocompletador ? 'codigo_cliente'  : 'campo_codigo_cliente'; ?>');
			var url = root_dir + '/app/interfaces/ajax_pago_documentos.php?id_moneda=' + select_moneda + '&codigo_cliente=' + codigo_cliente+'&id_cobro='+id_cobro;
		<?php } ?>

		if (actualizar) {
			url += ''<?php if (!empty($cambios_en_saldo_honorarios)) echo "+'&c_hon=" . implode(',', $cambios_en_saldo_honorarios) . "'"; if (!empty($cambios_en_saldo_gastos)) echo "+'&c_gas=" . implode(',', $cambios_en_saldo_gastos) . "'"; ?>;
		}
		if (id_documento || id_cobro){
			jQuery('#codigo_cliente').attr('readonly',true);
			jQuery('#glosa_cliente').attr('readonly',true);
		}
		if (id_documento){
			url += '&id_documento='+id_documento;
		}
		url += '&id_contrato='+$F('id_contrato');

		<?php if (!empty($adelanto)) { ?>
			url += '&adelanto=1';
		<?php } else if ($id_documento && $documento->fields['es_adelanto']) { ?>
			url += '&usar_adelanto=1';
		<?php } ?>

		jQuery('#elajax').val(url);
		jQuery.get(url, function(data) {
			jQuery('#tabla_pagos').html(data);

			jQuery("input:hidden[id^='pago_gastos_anterior']").each(function() {
				anterior+=Math.max(0,1.000*(jQuery(this).val()));
			});
			jQuery("input:hidden[id^='pago_honorarios_anterior']").each(function() {
				anterior+=Math.max(0,1.000*(jQuery(this).val()));
			});

			if (tipopago=='nuevopago' && anterior>0  && id_documento) {
				tipopago='documento'
				jQuery('#tipopago').val('documento');
			}

			jQuery('#saldo_pago_aux').val(1.000*jQuery('#monto_aux').val()-1.000*anterior);

			if (tipopago=='adelanto') jQuery('#overlaytipocambio').hide();
			if (tipopago=='editaadelanto') {
				jQuery('#overlaytipocambio').hide();
				monedaadelanto = jQuery('#id_moneda').val();
				jQuery('#id_moneda').attr({'id': 'readonlymoneda', 'name': 'readonlymoneda', 'readonly': 'readonly'});
				jQuery('#tabla_informacion').append('<input id="id_moneda" name="id_moneda" type="hidden" value="'+monedaadelanto+'" />');
			}
			if (tipopago=='documento' || tipopago=='nuevopago' || tipopago=='adelanto') {
				if (jQuery('#acepta_honorarios').length>0  && jQuery('#acepta_honorarios').val()==0) {
					jQuery("input:text[id^='pago_honorarios_']").attr('disabled','disabled').removeClass('saldojq');
				} else {
					jQuery("input:text[id^='pago_honorarios_']").removeAttr('disabled').addClass('saldojq');
				}
				if (jQuery('#pago_gastos').length>0 && jQuery('#acepta_gastos').val()==0) {
					jQuery("input:text[id^='pago_gastos_']").attr('disabled','disabled').removeClass('saldojq');
				} else {
					jQuery("input:text[id^='pago_gastos_']").removeAttr('disabled').addClass('saldojq');
				}

				jQuery('.saldojq').each(function() {
					total=total+(Math.max(0,1.000*jQuery(this).val()));
				});
				jQuery('#anteriorduro').val(anterior-total).formatNumber(formato_numeros());

				if(actualizar)   {
					jQuery('#monto_pagos').val(1.000 * total);

					if (jQuery('#saldo_pago_aux').length>0) {

						if ( (tipopago=='documento' || tipopago=='adelanto')) {
							if (jQuery('.saldojq').length==0) {
								jQuery('#monto').val(anterior-total).formatNumber(formato_numeros());
							} else if(total>0) {
								jQuery('#monto').val(anterior).formatNumber(formato_numeros());
							} else {
								jQuery('#monto').val(jQuery('#saldo_pago_aux').parseNumber(formato_numeros())+anterior).formatNumber(formato_numeros());
								Repartir();
							}

						} else {
							jQuery('#monto').val(Math.min(total,anterior+1.000*(jQuery('#saldo_pago_aux').val()))).formatNumber(formato_numeros());
						}
						jQuery('#saldo_pago').val(1.000*jQuery('#monto_aux').val()-1.000*jQuery('#monto').val());

					} else {
						if (tipopago=='documento' || tipopago=='adelanto') {
							jQuery('#monto').val(anterior).formatNumber(formato_numeros());

						} else {
							jQuery('#monto').val(total).formatNumber(formato_numeros());
							jQuery('#monto_aux').val(total).formatNumber(formato_numeros());
						}
					}
				} else {
					jQuery('#monto_pagos').val(Number(jQuery('#monto').val()));
					if (tipopago=='documento' || tipopago=='adelanto' ) {
						jQuery("input[id^='cobro_gastos_']").each(function() {    jQuery(this).val(parseInt(jQuery(this).val()*oldtasa*100/newtasa)/100);            });
						jQuery("input[id^='cobro_honorarios_']").each(function() {    jQuery(this).val(parseInt(jQuery(this).val()*oldtasa*100/newtasa)/100);    });
						jQuery('#anteriorduro').val(1.0000*(jQuery(this).val()*oldtasa/newtasa));
						jQuery('#pagosanteriores').val(1.0000*(jQuery(this).val()*oldtasa/newtasa));
					}
				}
			} else {
				jQuery("input:text[id^='pago_honorarios_']").attr('readonly',true).attr('disabled','disabled').removeClass('saldojq');
				jQuery("input:text[id^='pago_gastos_']").attr('readonly',true).attr('disabled','disabled').removeClass('saldojq');
				jQuery("#monto").attr('readonly',true)
			}

			if (tipopago!='documento' && tipopago!='adelanto')	{
				SetMontoPagos();
			}
		});
	}

	function MontoValido( id_campo ) {
		var monto = $F( id_campo );
		if (monto<0) {
			monto=0;
		}
		$(id_campo).value = monto;

		if(monto.match(/^\d+(\.\d+)?$/)) return false;

		monto = monto.replace(/[^\d.,]/g, '');
		monto = monto.replace(',','.');
		if(monto=='') monto = '0';
		var arr_monto = monto.split('.');
		var monto = arr_monto[0];
		for ($i=1;$i<arr_monto.length-1;$i++) {
			monto += arr_monto[$i];
		}
		if ( arr_monto.length > 1 ) {
			monto += '.' + arr_monto[arr_monto.length-1];
		}

		$( id_campo ).value = monto;
	}


	function CalculaPagoIva() {
		var id_doc_cobro = $('id_doc_cobro').value;
		var monto_pagos = $('monto_pagos').value;
		var cifras_decimales = $('cifras_decimales').value;
		var monto = document.getElementById('monto');

		if ($('pago_retencion').checked) {
			monto_retencion_impuestos = monto_pagos*12;
			monto_retencion_impuestos = (monto_retencion_impuestos.round())/100;
			$('pago_honorarios_'+id_doc_cobro).value = monto_retencion_impuestos;
			if( $('pago_gastos_'+id_doc_cobro) )
				$('pago_gastos_'+id_doc_cobro).value = 0;
			$('monto').value = monto_retencion_impuestos;
		} else {
			$('pago_honorarios_'+id_doc_cobro).value = monto_pagos;
			$('monto').value = monto_pagos;
		}
	}

	function  Actualizar_Monto_Pagos(tipo, id) {
		return true;
	}

	function SetMontoPagos() {
		var monto_tmp;
		var tipopago=jQuery('#tipodocumento').val();

		var anterior=0;
		jQuery("input[id^='pago_gastos_anterior']").each(function() {
			anterior+=Math.max(0,jQuery(this).parseNumber(formato_numeros()));
		});
		jQuery("input:hidden[id^='pago_honorarios_anterior']").each(function() {
			anterior+=Math.max(0,jQuery(this).parseNumber(formato_numeros()));
		});

		if (tipopago == 'nuevopago') {

			jQuery('#monto_aux').val(jQuery('#monto').parseNumber(formato_numeros()));
			jQuery('#monto_pagos').val(jQuery('#monto').parseNumber(formato_numeros()));

		} else {
			jQuery("#pagosanteriores").val(anterior);
			if (1.000*(jQuery('#monto').val())==0 && (tipopago=='documento' || tipopago=='adelanto'))  {
				jQuery('#monto').val(anterior);

			}

			jQuery('#monto_aux').val(jQuery('#monto').parseNumber(formato_numeros()));
			jQuery('#monto_pagos').val(jQuery('#monto').parseNumber(formato_numeros()));
		}


		<?php
		if ($_GET['adelanto'] == 1 AND !$_GET['id_documento'] AND !$id_documento) {
			echo 'return;';
		}
		?>
		var cifras_decimales = Number(jQuery('#cifras_decimales').val());
		if (monto_tmp==undefined) monto_tmp=0;
		if (jQuery('#saldo_pago_aux').length>0) {
			saldopagomaximo=anterior+Number(jQuery('#saldo_pago_aux').val());
			if (monto_tmp>saldopagomaximo) {
				jQuery('#monto').val(saldopagomaximo);
				Repartir();
				monto_tmp=saldopagomaximo;
			}

			jQuery('#saldo_pago').val(saldopagomaximo-monto_tmp);
		}
		<?php if (!$documento->Loaded()) { ?>
			if(jQuery('#monto_pagos').length > 0) {
				var monto_pagos = jQuery('#monto_pagos').parseNumber(formato_numeros());
				jQuery('#monto').val(monto_pagos).formatNumber(formato_numeros());
				jQuery('#monto_aux').val(monto_pagos).formatNumber(formato_numeros());
			}
		<?php } else if ($documento->fields['es_adelanto'] == '1') { ?>
			var saldoaux=jQuery('#saldo_pago_aux').parseNumber(formato_numeros());
			var elmonto=jQuery('#monto').parseNumber(formato_numeros());
			try {
				var montoadelanto=jQuery('#mondoadelanto').parseNumber(formato_numeros());
			} catch (e) {
			}

			if(jQuery('#mondoadelanto').length>0) {
				jQuery('#saldo_pago').val(anterior+montoadelanto-elmonto).formatNumber(formato_numeros());
			} else {
				jQuery('#saldo_pago').val(anterior+saldoaux-elmonto).formatNumber(formato_numeros());
			}
		<?php } ?>
		if(tipopago=='editaadelanto') {
			jQuery('#monto').val(jQuery('#monto_aux').parseNumber(formato_numeros())).formatNumber(formato_numeros());
			jQuery('#saldo_pago').val(jQuery('#saldo_pago_aux').parseNumber(formato_numeros())).formatNumber(formato_numeros());
		}
	}

	function ActualizarDocumentoMoneda(id_documento) {
		ids_monedas = jQuery('#ids_monedas_documento_'+id_documento).val();
		arreglo_ids = ids_monedas.split(',');
		var tc = new Array();
		for(var i = 0; i< arreglo_ids.length; i++) {
			tc[i] = jQuery('#documento_'+id_documento+'_moneda_'+arreglo_ids[i]).val();
		}

		var url = root_dir + '/app/interfaces/ajax.php?accion=actualizar_documento_moneda&id_documento='+id_documento+'&ids_monedas=' + ids_monedas+'&tcs='+tc.join(',');

		jQuery.get(url,function(response) {
			if(response == 'EXITO') 	{
				CargarTabla(0);
			}
		});
		jQuery('#tabla_pagos').html( "<img src='//static.thetimebilling.com/images/ajax_loader.gif'/>");
	}

	function MostrarTipoCambioPago() {
		$('TipoCambioDocumentoPago').show();
	}
	function CancelarDocumentoMonedaPago() {
		$('TipoCambioDocumentoPago').hide();
	}
	function ActualizarDocumentoMonedaPago() {
		ids_monedas = $('ids_monedas_documento').value;
		arreglo_ids = ids_monedas.split(',');
		$('tipo_cambios_documento').value = "";
		for(var i = 0; i<arreglo_ids.length-1; i++) {
			$('tipo_cambios_documento').value += $('documento_moneda_'+arreglo_ids[i]).value + ",";
		}
		i=arreglo_ids.length-1;
		$('tipo_cambios_documento').value += $('documento_moneda_'+arreglo_ids[i]).value;
		if( $('id_documento') != '' ) {
			var tc = new Array();
			for(var i = 0; i< arreglo_ids.length; i++) {
				tc[i] = $('documento_moneda_'+arreglo_ids[i]).value;
			}
			$('contenedor_tipo_load').innerHTML =
				"<table width=510px><tr><td align=center><br><br><img src='<?php echo Conf::ImgDir() ?>/ajax_loader.gif'/><br><br></td></tr></table>";
			var http = getXMLHTTP();
			var url = root_dir + '/app/interfaces/ajax.php?accion=actualizar_documento_moneda&id_documento=<?php echo $documento->fields['id_documento'] ?>&ids_monedas=' + ids_monedas+'&tcs='+tc.join(',');
			http.open('get', url);
			http.onreadystatechange = function() {
				if(http.readyState == 4) {
					var response = http.responseText;
					if(response == 'EXITO') {
						$('contenedor_tipo_load').innerHTML = '';
					}
				}
			}
			http.send(null);
			CancelarDocumentoMonedaPago();
		}
	}
</script>
<?php echo Autocompletador::CSS(); ?>
<form method='post' action="#" id="form_documentos" autocomplete='off'>
	<input type='hidden' name='opcion' value="guardar" />
	<input type='hidden' name='id_documento' id ='id_documento' value="<?php echo $documento->fields['id_documento'] ? $documento->fields['id_documento'] : '' ?>" />
	<input type='hidden' name='pago' value='<?php echo $pago ?>'>
	<input type='hidden' name='id_doc_cobro' id='id_doc_cobro' value='<?php echo $id_doc_cobro ?>' />
	<input type='hidden'  name='cifras_decimales' id='cifras_decimales' value='<?php echo ($cifras_decimales) ? $cifras_decimales : 2; ?>' />
	<input type='hidden' name='cobro' value='<?php echo $id_cobro ?>'>
	<input type='hidden' name='elimina_ingreso' id='elimina_ingreso' value=''>
	<input type='text' class="oculto" style="display:none;"  name='pago_honorarios' id='acepta_honorarios' value='<?php echo $id_documento ? $documento->fields['pago_honorarios'] : '1' ?>'/>
	<input type='text' class="oculto" style="display:none;"   name='pago_gastos' id='acepta_gastos' value='<?php echo $id_documento ? $documento->fields['pago_gastos'] : '1' ?>'/>
	<?php if (!$adelanto) { ?>
		<input type='hidden' name='es_adelanto' id='es_adelanto' value='<?php echo $id_documento ? $documento->fields['es_adelanto'] : '' ?>'/>
		<input type='hidden' name='id_contrato' id='id_contrato' value='<?php echo $id_documento ? $documento->fields['id_contrato'] : '' ?>'/>
	<?php } else { ?>
		<input type="hidden" name="adelanto" id="adelanto" value="<?php echo $adelanto; ?>" />
	<?php } ?>
	<?php if ($id_documento && $documento->fields['es_adelanto'] == '1') { ?>
		<input type='text'  class="oculto" style="display:none;"  name='montoadelanto' id="montoadelanto" value='<?php echo $documento->fields['monto']; ?>'/>
		<?php
	}

	if (empty($adelanto)) {
		?>
		<input type='hidden'  class="oculto" style="display:none;"  name='codigo_cliente_adelanto' value='<?php echo $documento->fields['codigo_cliente'] ?>'/>
		<input type='hidden' class="oculto" style="display:none;"   name='id_moneda' value='<?php echo $documento->fields['id_moneda'] ?>'/>
		<?php
	}
	if ($id_documento || $_GET['id_documento']) {
		if ($_GET['adelanto'] == 1) {
			$tipodocumento = 'editaadelanto';
		} else if ($documento->fields['es_adelanto']) {
			$tipodocumento = 'adelanto';
		} else {
			$tipodocumento = 'documento';
		}
	} else {
		if ($_GET['adelanto'] == 1) {
			$tipodocumento = 'nuevoadelanto';
		} else {
			$tipodocumento = 'nuevopago';
		}
	}
	?>
	<input type='text'  class="oculto" style="display:none;"   name='tipodocumento' id="tipodocumento" value='<?php echo $tipodocumento ?>' size="20"/>
	<input type='text'  class="oculto" style="display:none;"  name='pagosanteriores' id="pagosanteriores" value='0' size="20"/>
	<input type='text'  class="oculto" style="display:none;"  name='anteriorduro' id="anteriorduro" value='0' size="20"/>

	<input type='hidden' name='pagar_facturas' id="pagar_facturas" value='0'/>

	<input type='text'  class="oculto" style="display:none;"  name='elajax' id="elajax" value='' size="120"/>

	<!-- Calendario DIV -->
	<div id="calendar-container" style="width:221px; position:absolute; display:none;">
		<div class="floating" id="calendar"></div>
	</div>
	<!-- Fin calendario DIV -->
	<br>
	<table width='90%' id="txt_pagina">
		<tr>
			<td align="left"><b><?php echo $txt_pagina ?></b></td>
		</tr>
	</table>
	<br>

	<table style="border: 0px solid black;" width='90%'>
		<tr>
			<td align="left" width="50%">
				<b><?php echo __('Información de Documento') ?> </b>
			</td>
			<td align="right" width="50%">
				<?php
				$query = "SELECT count(*) FROM documento WHERE pago_retencion = 1 AND id_cobro = '$id_cobro'";
				$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
				list( $existe_pago_retencion ) = mysql_fetch_array($resp);
				if (!$existe_pago_retencion && $id_cobro && UtilesApp::GetConf($sesion, 'PagoRetencionImpuesto') && (!$id_documento || $documento->fields['es_adelanto'] != '1')) {
					?>
					<input type="checkbox" name="pago_retencion" id="pago_retencion" onchange="CalculaPagoIva();" value="1" <?php echo $pago_retencion ? "checked='checked'" : "" ?> />&nbsp;<?php echo __('Pago retención impuestos') ?>&nbsp;
					<?php
				}
				if ($id_cobro) {
					$pago_honorarios = $documento_cobro->fields['saldo_honorarios'] != 0 ? 1 : 0;
					$pago_gastos = $documento_cobro->fields['saldo_gastos'] != 0 ? 1 : 0;
					$hay_adelantos = $documento->SaldoAdelantosDisponibles($codigo_cliente, $cobro->fields['id_contrato'], $pago_honorarios, $pago_gastos) > 0;
				} else {
					$hay_adelantos = false;
				}
				if (!$adelanto && $hay_adelantos && !$ocultar_boton_adelantos) {
					$saldo_gastos = $documento_cobro->fields['saldo_gastos'] > 0 ? '&pago_gastos=1' : '';
					$saldo_honorarios = $documento_cobro->fields['saldo_honorarios'] > 0 ? '&pago_honorarios=1' : '';
					?>
					<button type="button" onclick="nuovaFinestra('Adelantos', 730, 470, 'lista_adelantos.php?popup=1&id_cobro=<?php echo $id_cobro; ?>&codigo_cliente=<?php echo $codigo_cliente ?>&elegir_para_pago=1<?php echo $saldo_honorarios; ?><?php echo $saldo_gastos; ?>&id_contrato=<?php echo $cobro->fields['id_contrato']; ?>', 'top=\'100\', left=\'125\', scrollbars=\'yes\'');return false;" ><?php echo __('Utilizar un adelanto'); ?></button>
				<?php } ?>
			</td>
		</tr>
	</table>
	<table id="tabla_informacion" style="border: 1px solid black;" width='90%'>
		<tr>
			<td align="right"><?php echo __('Fecha') ?></td>
			<td align="left">
				<input type="text" name="fecha" class="fechadiff" value="<?php echo $documento->fields['fecha'] ? Utiles::sql2date($documento->fields['fecha']) : date('d-m-Y') ?>" id="fecha" size="11" maxlength="10" />

			</td>
		</tr>
		<?php if ($id_solicitud_adelanto && UtilesApp::GetConf($sesion, 'UsarModuloSolicitudAdelantos')) { ?>
			<tr>
				<td align="right"><?php echo __('Solicitud de Adelanto') ?></td>
				<td align="left">
					<input type="text" name="id_solicitud_adelanto" readonly="readonly" value="<?php echo $id_solicitud_adelanto; ?>" id="id_solicitud_adelanto" size="11" />
				</td>
			</tr>
		<?php } ?>
		<tr>
			<td align="right" width="20%"><?php echo __('Cliente') ?></td>
			<td colspan="3" align="left">
				<?php UtilesApp::CampoCliente($sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario, '', 280, "+CargarTabla(1);"); ?>
			</td>
		</tr>
		<?php
		if ($adelanto) {
			UtilesApp::FiltroAsuntoContrato($sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario, $id_contrato, 280);
		}
		?>
		<tr>
			<td align="right">
				<?php echo __('Monto') ?>
			</td>
			<td align="left">
				<?php
				if ($id_cobro && !$adelanto) {
					$disabled_monto = 'class="actualizador"';
				}
				if (!$adelanto) {
					$disabled_monto .= ' readonly="readonly" style="background: #eee; cursor: default"';
				}
				?>
				<input name="monto" <?php echo $disabled_monto ?> id="monto" size="10" value="<?php echo str_replace("-", "", $documento->fields['monto']); ?>" />
				<input name="monto_aux"  class="oculto" style="display:none;"   type="text" id="monto_aux" size="10" value="<?php echo str_replace("-", "", $documento->fields['monto']); ?>" />

				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<?php echo __('Moneda') ?>&nbsp;
				<?php
				if ($documento->fields['id_documento'] || $documento->fields['id_moneda']) {
					$moneda_usada = $documento->fields['id_moneda'];
				} else if ($id_cobro) {
					$moneda_usada = $cobro->fields['opc_moneda_total'];
				} else {
					$moneda_usada = '';
				}
				?>
				<?php echo Html::SelectQuery($sesion, "SELECT id_moneda,glosa_moneda FROM prm_moneda ORDER BY id_moneda", "id_moneda", $moneda_usada, '', '', "80"); ?>
				<input type="hidden" name="id_moneda_aux" id="moneda_aux" value='<?php echo $moneda_usada ?>'/>
				<span style="color:#FF0000; font-size:10px">*</span>

			</td>
		</tr>
		<?php if ($id_documento && $documento->fields['es_adelanto'] == '1') { ?>
			<tr>
				<td align="right">
					<?php echo __('Saldo Adelanto') ?>
				</td>
				<td align="left">
					<input type="text" name="saldo_pago" id="saldo_pago" size="10" value="<?php echo str_replace("-", "", $documento->fields['saldo_pago']); ?>" readonly="readonly"/>
					<input type="text"  class="oculto" style="display:none;"   name="saldo_pago_aux" id="saldo_pago_aux" size="10" value="<?php echo str_replace("-", "", $documento->fields['saldo_pago']); ?>" readonly="readonly"/>
				</td>
			</tr>
		<?php } ?>
		<tr>
			<td align=right>
				<?php echo __('Tipo:') ?>
			</td>
			<td align=left>
				<?php
				if ($pago) {
					$query = "SELECT codigo, glosa FROM prm_tipo_pago WHERE familia = 'P' ORDER BY orden ASC";
				} else if ($adelanto) {
					$query = "SELECT codigo, glosa FROM prm_tipo_pago WHERE familia = 'T' ORDER BY orden ASC";
				}

				$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

				$tipos = array();
				while (list($codigo, $glosa) = mysql_fetch_array($resp)) {
					$tipos[$codigo] = $glosa;
				}

				echo Html::SelectArrayDecente($tipos, 'tipo_doc', $tipo_doc, 'id="tipo_doc" onchange="ShowCheque();"', '', '100px');
				echo __('N° Documento:');
				?>
				<input name=numero_doc size=10 value="<?php echo str_replace("-", "", $numero_doc); ?>" />
			</td>

		<tr>
			<td align="right">
				<?php echo __('Descripción') ?>
			</td>
			<td align="left">
				<textarea name="glosa_documento" id="glosa_documento" cols="45" rows="3"><?php
				if ($documento->fields['glosa_documento']) {
					echo $documento->fields['glosa_documento'];
				} else if ($id_cobro) {
					echo "Pago de " . __('Cobro') . " #" . $id_cobro;
				}
				?></textarea>
			</td>
		</tr>
		<?php
		if ($documento->fields['id_cuenta']) {
			$id_banco = $documento->fields['id_banco'];
			$id_cuenta = $documento->fields['id_cuenta'];
		}
		?>
		<tr>
			<td align="right">
				<?php echo __('Banco') ?>
			</td>
			<td align="left">
				<?php echo InputId::Imprimir($sesion, "prm_banco", "id_banco", "nombre", "id_banco", $id_banco, "", "CargarSelect('id_banco','id_cuenta','cargar_cuenta_banco');", 125, $id_cuenta); ?>
			</td>
		</tr>
		<?php
		if (!empty($id_banco)) {
			$where_banco = " WHERE cuenta_banco.id_banco = '$id_banco' ";
		} else {
			$where_banco = " WHERE 1=2 ";
		}
		?>
		<tr>
			<td align="right">
				<?php echo __('N° Cuenta') ?>
			</td>
			<td align="left">
				<?php echo InputId::Imprimir($sesion, "cuenta_banco", "id_cuenta", "numero", "id_cuenta", $id_cuenta, "", "", 125, "", "", "", !empty($id_banco) ? $id_banco : "no_existe" ); ?>
			</td>
		</tr>
		<tr>
			<td align="right">
				<?php echo __('N° Operación') ?>
			</td>
			<td align="left">
				<input name="numero_operacion" id="numero_operacion" size="15" value="<?php echo $documento->fields['numero_operacion']; ?>" />
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<?php echo __('N° Cheque') ?>&nbsp;
				<input name="numero_cheque" id="numero_cheque" size="15" value="<?php echo $documento->fields['numero_cheque']; ?>" />
			</td>
		</tr>

		<?php if (empty($adelanto)) { ?>
			<tr>
				<td colspan="2" align='center' id='overlaytipocambio'>
					<img src="<?php echo Conf::ImgDir() ?>/money_16.gif" border="0"> <a  href='javascript:void(0)' onclick="MostrarTipoCambioPago()" title="<?php echo __('Tipo de Cambio del Documento de Pago al ser pagado.') ?>"><?php echo __('Actualizar Tipo de Cambio') ?></a>
				</td>
			</tr>
			<tr>
				<td align="right" colspan="2">
					&nbsp;
				</td>
			</tr>
			<tr>
				<td align="right" colspan="2">
					<div id="TipoCambioDocumentoPago" style="display:none; left: 100px; top: 300px; background-color: white; position:absolute; z-index: 4;">
						<fieldset style="background-color:white;">
							<legend><?php echo __('Tipo de Cambio Documento de Pago') ?></legend>
							<div id="contenedor_tipo_load">&nbsp;</div>
							<div id="contenedor_tipo_cambio">
								<div style="padding-top:5px; padding-bottom:5px;">&nbsp;<img src="<?php echo Conf::ImgDir() ?>/alerta_16.gif" title="Alerta" />&nbsp;&nbsp;<?php echo __('Este tipo de cambio sólo afecta al Documento de Pago en los Reportes. No modifica la Carta de') . " " . __('Cobro') . "." ?></div>
								<table style='border-collapse:collapse;' cellpadding='3'>
									<tr>
										<?php
										if ($documento->fields['id_documento']) {
											$query = "SELECT count(*) FROM documento_moneda WHERE id_documento = '" . $documento->fields['id_documento'] . "'";
											$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
											list($cont) = mysql_fetch_array($resp);
										}
										else
											$cont = 0;
										if ($cont > 0) {
											$query =
												"SELECT prm_moneda.id_moneda, glosa_moneda, documento_moneda.tipo_cambio
												FROM documento_moneda
												JOIN prm_moneda ON documento_moneda.id_moneda = prm_moneda.id_moneda
												WHERE id_documento = '" . $documento->fields['id_documento'] . "'";
											$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
										} else {
											$query =
												"SELECT prm_moneda.id_moneda, glosa_moneda, documento_moneda.tipo_cambio
												FROM documento_moneda
												JOIN prm_moneda ON documento_moneda.id_moneda = prm_moneda.id_moneda
												WHERE id_documento = '" . $documento_cobro->fields['id_documento'] . "'";
											$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
										}
										$num_monedas = 0;
										$ids_monedas = array();
										$tipo_cambios = array();
										while (list($id_moneda, $glosa_moneda, $tipo_cambio) = mysql_fetch_array($resp)) {
											?>
											<td>
												<span><b><?php echo $glosa_moneda ?></b></span><br>
												<input type='text' size="9" id='documento_moneda_<?php echo $id_moneda ?>' name='documento_moneda_<?php echo $id_moneda ?>' value='<?php echo $tipo_cambio ?>' />
											</td>
											<?php
											$num_monedas++;
											$ids_monedas[] = $id_moneda;
											$tipo_cambios[] = $tipo_cambio;
										}
										?>
									<tr>
										<td colspan=<?php echo $num_monedas ?> align="center">
											<input type="button" onclick="ActualizarDocumentoMonedaPago($('todo_cobro'))" value="<?php echo __('Guardar') ?>" />
											<input type="button" onclick="CancelarDocumentoMonedaPago()" value="<?php echo __('Cancelar') ?>" />
											<input type="hidden" id="tipo_cambios_documento" name="tipo_cambios_documento" value="<?php echo implode(',', $tipo_cambios) ?>" />
											<input type="hidden" id="ids_monedas_documento" name="ids_monedas_documento" value="<?php echo implode(',', $ids_monedas) ?>" />
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
			<?php
			($Slim = Slim::getInstance('default', true)) ? $Slim->applyHook('hook_ingresar_documento_pago') : false;
		}
		?>
	</table>

	<br>
	<table style="border: 0px solid black;" width='90%'>
		<tr>
			<td align="left">
				<a class="btn botonizame" href="javascript:void();" icon="ui-icon-save" onclick="return Validar(jQuery('#form_documentos').get(0));"><?php echo __('Guardar') ?></a>
				<a class="btn botonizame"  href="javascript:void();" icon="ui-icon-exit" onclick="Cerrar();" ><?php echo __('Cancelar') ?></a>
			</td>
		</tr>
	</table>
	<?php if (!empty($adelanto) && empty($id_documento)) { ?>
		<input type="hidden" id="monto_pagos" />
	<?php } ?>

	<div id = "tabla_pagos"> </div>
	<div id = "tabla_jq"> </div>
	<script type="text/javascript">
<?php if (empty($adelanto) && $id_documento && $documento->fields['es_adelanto'] == '1') { ?>
		$('tabla_informacion').select('input, select, textarea').each(function(elem){
			elem.readonly = 'readonly';
		});
	<?php
}
if (empty($adelanto) || $id_documento) {
	?>
			CargarTabla(1);

<?php } ?>

	jQuery(document).ready(function() {
		jQuery('#monto').bind('click', function() {
			jQuery("input:text[id^='pago_honorarios_'][value!=0], input:text[id^='pago_gastos_'][value!=0]").first().focus().select();
		});
		<?php if (UtilesApp::GetConf($sesion, 'UsarModuloSolicitudAdelantos')) { ?>
			CargarTabla(1);
		<?php } ?>
	});
	</script>

</form>
<script type="text/javascript">
	jQuery('.oculto').hide();
	if(window.location!=parent.window.location) jQuery('#txt_pagina').hide();

</script>
<?php
if ($autocompletador) {
	echo Autocompletador::Javascript($sesion, false, 'CargarTabla(1);');
}
echo InputId::Javascript($sesion, "", "No existen N° de cuenta asociadas a este banco.");
$pagina->PrintBottom($popup);
