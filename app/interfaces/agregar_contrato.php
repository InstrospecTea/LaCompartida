<?php
require_once dirname(__FILE__) . '/../conf.php';

$Sesion = new Sesion(array('DAT'));

//Tooltips para las modalidades de cobro.
$tip_tasa = __('Tip tasa');
$tip_retainer = __('Tip retainer');
$tip_cap = __('Tip cap');
$tip_hitos = __('Tip hitos');
$tip_flat = __('Tip flat');
$tip_escalonada = __('Tip escalonada');
$tip_honorarios = __('Tip honorarios');
$tip_mensual = __('Tip mensual');
$tip_tarifa_especial = __('Tip tarifa especial');
$tip_individual = __('Tip individual');
$tip_proporcional = __('Tip proporcional');
$tip_retainer_usuarios = __("Si usted selecciona usuarios en esta lista, las horas de estos usuarios se van a descontar de las horas retainer con preferencia");
$tip_subtotal = __("El monto total ") . __("del cobro") . __(" hasta el momento sin incluir descuentos.");
$tip_descuento = __("El monto del descuento.");
$tip_total = __("El monto total ") . __("del cobro") . __(" hasta el momento incluidos descuentos.");
$tip_actualizar = __("Actualizar los montos");
$tip_refresh = __("Actualizar a cambio actual");

$color_par = "#f0f0f0";
$color_impar = "#ffffff";

$archivo = new Archivo($Sesion);
$AutocompleteHelper = new FormAutocompleteHelper();
if (!isset($SelectHelper)) {
	$SelectHelper = new FormSelectHelper();
}
// previene override del objero, ya que se incluye desde otras interfaces.
function TTip($texto) {
	return "onmouseover=\"ddrivetip('$texto');\" onmouseout=\"hideddrivetip('$texto');\"";
}

if (empty($cliente)) {
	$cliente = new Cliente($Sesion);
}

if (!isset($Pagina)) {
	$Pagina = new Pagina($Sesion);
}

$validacionesCliente = Conf::GetConf($Sesion, 'ValidacionesCliente');
$validacionesClienteJS = $validacionesCliente ? 'true' : 'false';

if (!isset($contractValidation)) {
	require_once Conf::ServerDir() . '/interfaces/agregar_contrato_validaciones.php';
}

$obligatorios = function($key) use ($validacionesCliente, $contractValidation) {
	if (!$validacionesCliente) {
		return '';
	}
	if (isset($contractValidation)) {
		return $contractValidation->validationSkipped($key) ? '' : '<span class="req">*</span>';
	} else {
		return '<span class="req">*</span>';
	}
};

$modulo_retribuciones_activo = Conf::GetConf($Sesion, 'UsarModuloRetribuciones');

if (!defined('HEADERLOADED')) {
	$addheaderandbottom = true;
}

if ($addheaderandbottom || ($popup && !$motivo)) {

	$show = 'inline';

	$contrato = new Contrato($Sesion);

	if($id_contrato > 0) {
		if(!$contrato->Load($id_contrato)) {
			$Pagina->FatalError(__('C�digo inv�lido'));
		}

		$cobro = new Cobro($Sesion);
	}

	if($contrato->fields['codigo_cliente'] != '') {
		$cliente->LoadByCodigo($contrato->fields['codigo_cliente']);
	}

	if($contrato->fields['id_moneda'] == '') {
		$contrato->fields['id_moneda'] = $cliente->fields['id_moneda'];
	}

	if($id_contrato) {
		$Pagina->titulo = __('Editar Contrato');
	} else {
		$Pagina->titulo = __('Agregar Contrato');
	}
} else {
	$show = 'none';
}

$contrato_defecto = new Contrato($Sesion);
if (!empty($cliente->fields["id_contrato"])) {
	$contrato_defecto->Load($cliente->fields["id_contrato"]);
}

$contrato_nuevo = isset($contrato_nuevo) ? $contrato_nuevo : false;
if (isset($cargar_datos_contrato_cliente_defecto) && !empty($cargar_datos_contrato_cliente_defecto)) {
	$contrato->fields = $cargar_datos_contrato_cliente_defecto;
	$contrato_nuevo = true;
}

// CONTRATO GUARDA
if ($opcion_contrato == "guardar_contrato" && $popup && !$motivo) {
	$enviar_mail = 1;

	$Cliente = new Cliente($Sesion);

	if (!$Cliente->LoadByCodigo($codigo_cliente)) {
		$Pagina->AddError(__('El cliente seleccionado no existe en el sistema'));
	}

	if ($forma_cobro != 'TASA' && $forma_cobro != 'HITOS' && $forma_cobro != 'ESCALONADA' && $monto == 0) {
		$Pagina->AddError(__('Ud. ha seleccionado forma de ') . __('cobro') . ': ' . $forma_cobro . ' ' . __('y no ha ingresado monto'));
	} else if ($forma_cobro == 'TASA') {
		$monto = '0';
	}

	if ($tipo_tarifa == 'flat') {
		if (empty($tarifa_flat)) {
			$Pagina->AddError(__('Ud. ha seleccionado una tarifa plana pero no ha ingresado el monto'));
		} else {
			$tarifa = new Tarifa($Sesion);
			$id_tarifa = $tarifa->GuardaTarifaFlat($tarifa_flat, $id_moneda, $id_tarifa_flat);
			$_REQUEST['id_tarifa'] = $id_tarifa;
		}
	}

	if ($usuario_responsable_obligatorio && empty($id_usuario_responsable) or $id_usuario_responsable == '-1') {
		$Pagina->AddError(__("Debe ingresar el") . " " . __('Encargado Principal'));
	}

	if (Conf::GetConf($Sesion, 'EncargadoSecundario') && (empty($id_usuario_secundario) or $id_usuario_secundario == '-1')) {
		$Pagina->AddError(__("Debe ingresar el") . " " . __('Encargado Secundario'));
	}

	if (isset($_REQUEST['nombre_contacto'])) {
		// nombre_contacto no existe como campo en la tabla contrato y es necesario crear la variable "contacto" dentro de _REQUEST
		$_REQUEST['contacto'] = trim($_REQUEST['nombre_contacto']);
	}

	$activo_antes = $contrato->fields['activo'];
	$contrato->Fill($_REQUEST, true);

	if (!$Pagina->GetErrors() && $contrato->Write()) {
		if ($activo_antes != $contrato->fields['activo'] && $contrato->fields['activo'] == 'NO') {
			// Desactiva asuntos del contrato.
			$where = new CriteriaRestriction("id_contrato = '{$contrato->fields['id_contrato']}' AND activo");
			$Criteria = new Criteria($Sesion);
			$asuntos = $Criteria
				->add_from('asunto')
				->add_restriction($where)
				->add_select('count(*)', 'total')
				->run();
			if ($asuntos[0]['total'] > 0) {
				$query_asuntos = "UPDATE asunto SET activo = 0, fecha_modificacion = now() WHERE id_contrato = {$contrato->fields['id_contrato']} AND activo";
				mysql_query($query_asuntos, $Sesion->dbh);
				$Pagina->AddInfo(sprintf(__('Se desactivaron %d asuntos'), $asuntos[0]['total']));
			}
		}

		// cobros pendientes
		CobroPendiente::EliminarPorContrato($Sesion, $contrato->fields['id_contrato'] ? $contrato->fields['id_contrato'] : $id_contrato);
		if ($contrato->fields['forma_cobro'] !== 'FLAT FEE') {
			$valor_fecha = array();
		}
		for ($i = 2; $i <= sizeof($valor_fecha); $i++) {
			$cobro_pendiente = new CobroPendiente($Sesion);
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
			$cobro_pendiente = new CobroPendiente($Sesion);
			$cobro_pendiente->Edit("id_contrato", $contrato->fields['id_contrato'] ? $contrato->fields['id_contrato'] : $id_contrato);
			$cobro_pendiente->Edit("fecha_cobro", empty($hito_fecha[$i]) ? 'NULL' : Utiles::fecha2sql($hito_fecha[$i]));
			$cobro_pendiente->Edit("descripcion", $hito_descripcion[$i]);
			$cobro_pendiente->Edit("observaciones", $hito_observaciones[$i]);
			$cobro_pendiente->Edit("monto_estimado", $hito_monto_estimado[$i]);
			$cobro_pendiente->Edit("hito", '1');
			$cobro_pendiente->Edit("notificado", 0);
			$cobro_pendiente->Write();
		}

		ContratoDocumentoLegal::EliminarDocumentosLegales($Sesion, $contrato->fields['id_contrato'] ? $contrato->fields['id_contrato'] : $id_contrato);
		if (is_array($docs_legales)) {
			foreach ($docs_legales as $doc_legal) {
				if (empty($doc_legal['documento_legal']) or ( empty($doc_legal['honorario']) and empty($doc_legal['gastos_con_iva']) and empty($doc_legal['gastos_sin_iva']) )) {
					continue;
				}
				$contrato_doc_legal = new ContratoDocumentoLegal($Sesion);
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

			if (Conf::GetConf($Sesion, 'EncargadoSecundario')) {
				mysql_query("UPDATE cliente SET id_usuario_encargado = '" .
						((!empty($id_usuario_secundario) && $id_usuario_secundario != -1 ) ? $id_usuario_secundario : "NULL") .
						"' WHERE id_contrato = " . $contrato->fields['id_contrato'], $Sesion->dbh);
			}
		}
		$Pagina->AddInfo(__('Contrato guardado con �xito'));
	} else {
		$Pagina->AddError($contrato->error);
	}
}

$tarifa = new Tarifa($Sesion);
$tramite_tarifa = new TramiteTarifa($Sesion);
$tarifa_default = $tarifa->SetTarifaDefecto();
$tramite_tarifa_default = $tramite_tarifa->SetTarifaDefecto();

$idioma_default = $contrato->IdiomaPorDefecto($Sesion);

if (empty($tarifa_flat) && !empty($contrato->fields['id_tarifa'])) {
	$tarifa->Load($contrato->fields['id_tarifa']);
	$valor_tarifa_flat = $tarifa->fields['tarifa_flat'];
} else if (!empty($tarifa_flat) && $tipo_tarifa != 'flat') {
	$valor_tarifa_flat = null;
} else {
	$valor_tarifa_flat = $tarifa_flat;
}

if ($addheaderandbottom || ($popup && !$motivo)) {
	$Pagina->PrintTop($popup);
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
$resp = mysql_query($query_count, $Sesion->dbh);
list($cant_encargados) = mysql_fetch_array($resp);

// Para validaciones de plugins activos de momento solo para "facturacion_electronica_mx"
$query_plugins = "SELECT archivo_nombre FROM prm_plugin
	WHERE activo = 1 and archivo_nombre = 'facturacion_electronica_mx.php'";
$resp_plugins = mysql_query($query_plugins, $Sesion->dbh);
list ($plugins_activos) = mysql_fetch_array($resp_plugins);


$Moneda = new Moneda($Sesion);
$CuentaBanco = new CuentaBanco($Sesion);
$PrmBanco = new PrmBanco($Sesion);
$PrmCodigo = new PrmCodigo($Sesion);
$PrmPais = new PrmPais($Sesion);
$Idioma = new Idioma($Sesion);
$TramiteTarifa = new TramiteTarifa($Sesion);
$Carta = new Carta($Sesion);
$CobroRtf = new CobroRtf($Sesion);
$Form = new Form();
$Html = new \TTB\Html();
?>
<script type="text/javascript">

	function ValidarContrato(form) {

		if (!form) {
			var form = jQuery('[name="formulario"]').get(0);
		}

		var plugin_facturacion_mx = '<?php echo $plugins_activos ?>';

		if (plugin_facturacion_mx != '') {
			if(form.id_pais.options[0].selected == true) {
				alert("<?php echo __('Debe ingresar el pais del cliente. Es Obligatorio debido a Facturaci�n Electr�nica') ?>");
				form.id_pais.focus();
				return false;
			}
		}

		<?php if (Conf::GetConf($Sesion, 'NuevoModuloFactura')) { ?>
			if (!validar_doc_legales(true)){
				return false;
			}
		<?php } ?>

		<?php echo $contractValidation->getClientValidationsScripts(); ?>

		if($('fc5').checked)
		{
			if(form.limite_monto.value == 0)
			{
				if(confirm('�Desea generar una alerta cuando se supere el CAP?'))
					form.limite_monto.value = form.monto.value;
			}
		}

		form.submit();
		if( window.opener )
			window.opener.Refrescar();
		return true;
	}

	function SetFormatoRut() {
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
					alert('No puede agregar un escal�n nuevo, si no ha llenado los datos del escalon actual');
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
			if(jQuery("#fc1").is(':checked')) {
				laID='fc1';
			} else if(jQuery("#fc2").is(':checked')) {
				laID='fc2';
			} else if(jQuery("#fc3").is(':checked')) {
				laID='fc3';
			} else if(jQuery("#fc5").is(':checked')) {
				laID='fc5';
			} else if(jQuery("#fc6").is(':checked')) {
				laID='fc6';
			} else if(jQuery("#fc7").is(':checked')) {
				laID='fc7';
			} else if(jQuery("#fc8").is(':checked')) {
				laID='fc8';
			}
		}

		if (laID != "fc3" && jQuery("#tabla_fechas #id_body").children().length > 1) {
			if(! confirm("El contrato tiene cobros programados, �est� seguro que desea cambia la Forma de Tarificaci�n?. Al aceptar, los cobros programados ser�n eliminados.")) {
				laID = "fc3";
			};
		};

		jQuery("#div_cobro label").removeClass('ui-state-focus');
		jQuery("#div_cobro label").removeClass('ui-state-active');
		jQuery("[for='" + laID + "']").addClass('ui-state-focus');
		jQuery("[for='" + laID + "']").addClass('ui-state-active');
		jQuery("#" + laID).attr('checked', true);

		jQuery("#div_forma_cobro").css({'width':'400px','margin-left':'21%'}).hide();
		jQuery("#div_retainer_usuarios").css('display','inline').hide();
		jQuery("#div_monto").hide();
		jQuery("#div_horas").hide();
		jQuery("#span_monto").hide();
		jQuery("#div_fecha_cap").hide();
		jQuery("#div_escalonada").hide();
		jQuery("#tabla_hitos").hide();
		jQuery("#id_moneda_monto").show();
		jQuery('#tr_cobros_programados').hide();


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
			jQuery('#tr_cobros_programados').show();
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
			nuovaFinestra( 'Tr�mite_Tarifas', 600, 600, 'tarifas_tramites.php?popup=1&crear=1', '' );
		else
		{
			//var id_tramite_tarifa = form.id_tramite_tarifa.value;
			if(!id_tramite_tarifa)
				var id_tramite_tarifa = jQuery('#id_tramite_tarifa').val();
			nuovaFinestra( 'Tr�mite_Tarifas', 600, 600, 'tarifas_tramites.php?popup=1&id_tramite_tarifa_edicion='+id_tramite_tarifa, '' );
		}
	}

	function ActualizarTarifaTramiteDesdePopup() {
		var destino = 'id_tramite_tarifa';
		loading("Actualizando campo");

		var url = 'ajax.php';
		var datos = {accion: 'cargar_tarifas_tramites'};
		jQuery.get(url, datos, function(response) {
			if( response == "~noexiste" ) {
				$(destino).options.length = 0;
			} else {
				$(destino).options.length = 0;
				cuentas = response.split('//');

				for (var i = 0; i < cuentas.length; i++) {
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
		}, 'text');
	}



	/*
	 * Desactivar contrato para no verlo en cobros. (generaci�n)
	 */
	function InactivaContrato(alerta, opcion) {
		form = jQuery('[name="formulario"]').get(0);

		var activo_contrato = $('activo_contrato');
		if (!alerta) {
			var img = '<center><img src="<?php echo Conf::ImgDir() ?>/ajax_loader.gif" /></center>';
			jQuery('<p/>')
					.html(img)
					.attr('title', '<?php echo __('ALERTA'); ?>')
					.load('ajax/agregar_contrato.php', {accion: 'msg_desactivar', id_contrato: '<?php echo $contrato->fields['id_contrato']; ?>'})
					.dialog({
						resizable: false,
						modal: true,
						dialogClass: 'no-close',
						closeOnEscape: false,
						width: 350,
						'buttons': {
							'Cancelar': function() {
								activo_contrato.checked = true;
								jQuery('#desactivar_contrato').remove();
								jQuery(this).dialog('close');
							},
							'Aceptar': function() {
								jQuery('[name="formulario"]').append('<input type="hidden" value="1" id="desactivar_contrato" name="desactivar_contrato"/>');
								ValidarContrato(this.form);
								return true;
							}
						}
					});
		} else {
			return false;
		}
	}

	//Funci�n que genera la tabla completa
	function generarFechas()
	{
		if($('periodo_fecha_inicio').value == '') {
			alert('No se ha seleccionado una fecha inicial');
			$('periodo_fecha_inicio').focus();
			return;
		}
		if($('periodo_intervalo').value == '0' || $('periodo_intervalo').value == '') {
			alert('No se ha seleccionado una periodicidad');
			$('periodo_intervalo').focus();
			return;
		}
		if($('valor_fecha_2') && !confirm('�Est� seguro que desea generar la tabla nuevamente?\n<?php echo __('El primer cobro'); ?> de la tabla ser� el '+$('periodo_fecha_inicio').value))
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
			if (item.id != 'fila_fecha_1') {
				item.remove();
			}
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
			if (i==4 || i==6 || i==9 || i==11) {
				this[i] = 30;
			}
			if (i==2) {
				this[i] = 29;
			}
		}
		return this;
	}

	<?php
	// numeros de cobros existentes para ver cual sigue
	$query = "SELECT COUNT(*) FROM cobro_pendiente WHERE id_cobro IS NOT NULL AND id_contrato='" . $contrato->fields['id_contrato'] . "' AND hito = '0'";
	$resp = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);
	list($numero_cobro) = mysql_fetch_array($resp);
	?>
	//agrega nuevos datos a la tabla segun la fecha inicial la periodicidad y el periodo total
	function addTable()
	{
		var daysInMonth = DaysArray(12);
		var periodo = parseInt($('periodo_intervalo').value);
		//se considera un periodo total de 2 a�os
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
			$('valor_descripcion_1').value="<?php echo __('Cobro N�'); ?> "+numero_cobro;
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
		monto.innerHTML="<div class='input-prepend input'><span class='moneda_tabla add-on' align='center'></span><input type='text' class='monto_estimado' size='10' value='"+$('valor_monto_estimado_1').value+"' /></div>";
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
			if($('fila_fecha_'+i)) {
				$('fila_fecha_'+i).toggle();
			}
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
			if(i % 2 == 0) {
				$('fila_fecha_'+i).bgColor="#f0f0f0";
			} else {
				$('fila_fecha_'+i).bgColor="#ffffff";
			}
		}
		actualizarMonto();
		actualizarMoneda();
	}
	var simbolo = new Array();
<?php
$query = "SELECT id_moneda,simbolo FROM prm_moneda";
$resp = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);
while (list($id_moneda_tabla, $simbolo_tabla) = mysql_fetch_array($resp)) {
	?>
		simbolo[<?php echo $id_moneda_tabla ?>] = "<?php echo $simbolo_tabla ?>";
<?php } ?>

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
	 * Detectar la selecci�n de separar liquidaciones
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

	function CargarCuenta(origen, destino) {
		loading('Actualizando campo');
		var url = 'ajax.php';

		jQuery.get(url, {accion: 'cargar_cuentas', id: jQuery('#' + origen).val()}, function(response) {
			var select = jQuery('#' + destino);
			select[0].options.length = 0;
			if( response == '~noexiste' ) {
				alert('Usted no tiene cuentas en este banco.');
			} else {
				cuentas = response.split('//');

				for (var i = 0; i < cuentas.length; i++) {
					valores = cuentas[i].split('|');

					var valor = '';
					if( valores[0] == "Vacio") {
						valor = '';
					} else {
						valor = valores[0];
					}

					var option = jQuery('<option/>', {value: valor}).text(valores[1]);
					select.append(option);
				}
			}
			offLoading();
		}, 'text');
	}

	function SetBanco(origen, destino) {
		var url = 'ajax.php';

		loading('Actualizando campo');

		jQuery.get(url, {accion: 'buscar_banco', id: jQuery('#' + origen).val()}, function(response) {
			jQuery('#' + destino).val(response);
			offLoading();
		}, 'text');
	}


	var respuesta_revisar_tarifa = false;

	function RevisarTarifasRequest( tarifa, moneda ) {
		var data = {
			accion: 'revisar_tarifas',
			id_tarifa: jQuery('#' + tarifa).val(),
			id_moneda: jQuery('#' + moneda).val()
		};
		var text_window = '';
		if( $('desde') && $('desde').value == 'agregar_asunto') {
			if( $('cobro_independiente') ) {
				if( $('cobro_independiente').checked ) {
					data.cobro_independiente = 'SI';
					var cliente = '';
				} else {
					var cobro_independiente = '&cobro_independiente=NO';
					data.cobro_independiente = 'NO';
					data.codigo_cliente = jQuery('#<?php echo Conf::GetConf($Sesion, 'CodigoSecundario') ? 'codigo_cliente_secundario' : 'codigo_cliente'; ?>').val();
				}
			}
		}
		var url = 'ajax.php';
		var respuesta = '0::&nbsp;::0';
		jQuery.ajax(url, {
			async: false,
			data: data,
			success: function(response) {
				respuesta = response;
			}
		});
		return respuesta;
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
					text_window += '<span style="font-size:12px; text-align:center;font-weight:bold"><?php echo __('Hay m�s de 10 abogados sin valor para la tarifa y moneda seleccionadas.') ?></span><br><br>';
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
								if( jQuery('#desde').val() == 'agregar_cliente' || jQuery('#desde').val() == 'agregar_cliente')
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
					if( jQuery('#desde').val() == 'agregar_cliente' || jQuery('#desde').val() == 'agregar_asunto' )
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
				if( jQuery('#desde').val() == 'agregar_cliente' || jQuery('#desde').val() == 'agregar_asunto' )
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

	var mismoEncargado = <?php echo Conf::GetConf($Sesion, 'EncargadoSecundario') && $contrato->fields['id_usuario_responsable'] == $contrato->fields['id_usuario_secundario'] ? 'true' : 'false' ?>;
	var CopiarEncargadoAlAsunto=<?php echo (Conf::GetConf($Sesion, "CopiarEncargadoAlAsunto") ) ? '1' : '0'; ?>;
	var EncargadoSecundario=<?php echo (Conf::GetConf($Sesion, "EncargadoSecundario") ) ? '1' : '0'; ?>;
    var DesdeAgregaCliente=<?php echo ($desde_agrega_cliente ) ? '1' : '0'; ?>;

	function CambioEncargado(elemento){

		if (CopiarEncargadoAlAsunto && DesdeAgregaCliente) {

			if (elemento.name == "id_usuario_responsable") {
				if (EncargadoSecundario ) {
					$('id_usuario_secundario').value = $('id_usuario_responsable').value;
					if (jQuery('#id_usuario_secundario').length>0) {
						jQuery('#id_usuario_secundario').attr('disabled', 'disabled');
					}

				} else {

					$('id_usuario_encargado').value = $('id_usuario_responsable').value;
					if(jQuery('#id_usuario_encargado').length>0) {
						jQuery('#id_usuario_encargado').attr('disabled', 'disabled');
					}
				}

			} else {
				if(mismoEncargado && $('id_usuario_secundario').value == '-1' ){
					if(confirm('�Desea cambiar tambi�n el <?php echo __('Encargado Secundario'); ?> ?')){
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
		if(!validarHito($('fila_hito_1'))) {
			return false;
		}

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
		var fecha_split = fecha.split('-');
		var valid_day = (parseInt(fecha_split[0], 10) > 0 && parseInt(fecha_split[0], 10) <= 31);
		var valid_month = (parseInt(fecha_split[1], 10) > 0 && parseInt(fecha_split[1], 10) <= 12);
		var valid_year = parseInt(fecha_split[2], 10) >= 1969;
		var desc = $F('hito_descripcion_'+num);
		var monto = Number($F('hito_monto_estimado_'+num));

		if ($('hito_fecha_' + num).disabled) {
			return true;
		}
		if (permitirVacio && !fecha && !desc && !monto) {
			return true;
		}
		if (!valid_day || !valid_month || !valid_year) {
			alert('Ingrese una fecha v�lida para el hito');
			$('hito_fecha_'+num).focus();
			return false;
		}
		if(!desc){
			alert('Ingrese una descripci�n v�lida para el hito');
			$('hito_descripcion_'+num).focus();
			return false;
		}
		if(isNaN(monto) || monto <= 0){
			alert('Ingrese un monto v�lido para el hito');
			$('hito_monto_estimado_'+num).focus();
			return false;
		}
		return true;
	}

	function eliminarHito(elem){
		if (confirm('�Est� seguro que desea eliminar este hito?')) {
			$(elem).up('tr').remove();
		}
	}

</script>
<?php if ($popup && !$motivo) { ?>

	<form name='formulario' id='formulario' method="post">
		<input type="hidden" name="codigo_cliente" value="<?php echo $cliente->fields['codigo_cliente'] ? $cliente->fields['codigo_cliente'] : $codigo_cliente ?>" />
		<input type="hidden" name='opcion_contrato' value="guardar_contrato" />
		<input type="hidden" name='id_contrato' value="<?php echo isset($cargar_datos_contrato_cliente_defecto) ? '' : $contrato->fields['id_contrato']; ?>" />
		<input type="hidden" name="desde" value="agregar_contrato" />
<?php } ?>
	<br />
	<!-- Calendario DIV -->
	<div id="calendar-container" style="width:221px; position:absolute; display:none;">
		<div class="floating" id="calendar"></div>
	</div>

	<!-- Fin calendario DIV -->
	<fieldset style="width: 97%;" class="tb_base" style="border: 1px solid #BDBDBD;">
		<legend>&nbsp;<?php echo __('Informaci�n Comercial') ?></legend>

		<!-- RESPONSABLE -->
		<table id="responsable">
			<tr   class="controls controls-row ">
				<td class="al">
					<div class="span4"><?php echo __('Activo') ?></div>
				<?php
				$chk = '';
				if (!$contrato->loaded()) {
					$chk = 'checked="checked"';
				}
				?>
				</td>
				<td class="al">
					<label for="activo_contrato" class="inline-help">
						<input type="hidden" name="activo_contrato" value="0"/>
						<input type="checkbox" class="span1" name="activo_contrato" id="activo_contrato" value="1" <?php echo $contrato->fields['activo'] == 'SI' ? 'checked="checked"' : '' ?> <?php echo $chk ?> onclick="InactivaContrato(this.checked);" />
					&nbsp;<?php echo __('Los contratos inactivos no aparecen en el listado de cobranza.') ?></label>
				 </td>
			</tr>
			<?php if (Conf::GetConf($Sesion, 'UsarImpuestoSeparado')) { ?>
				<tr   class="controls controls-row ">
					<td class="al">
						<div class="span4"><?php echo __('Usa impuesto a honorario') ?></div>
					<?php
						// Se revisa tambi�n el primer contrato del cliente para el valor por defecto.
						$chk = '';
						if ($contrato->loaded()) {
							if ($contrato->fields['usa_impuesto_separado']) {
								$chk = 'checked="checked"';
							}
						} else if (Utiles::Glosa($Sesion, $cliente->fields['id_contrato'], 'usa_impuesto_separado', 'contrato')) {
							$chk = 'checked="checked"';
						}
					?>
					</td>
					<td class="al">
						<input type="hidden" name="usa_impuesto_separado" value="0"/>
						<input class="span1" type="checkbox" name="usa_impuesto_separado" id="usa_impuesto_separado" value="1" <?php echo $chk ?> />
					</td>
				</tr>
			<?php } ?>
			<?php if (Conf::GetConf($Sesion, 'UsarImpuestoPorGastos')) { ?>
				<tr class="controls controls-row ">
					<td class="al">
						<div class="span4"><?php echo __('Usa impuesto a gastos') ?></div>
					<?php
					// Se revisa tambi�n el primer contrato del cliente para el valor por defecto.
					$chk_gastos = '';
					if ($contrato->loaded()) {
						if ($contrato->fields['usa_impuesto_gastos']) {
							$chk_gastos = 'checked="checked"';
						}
					} else if (Utiles::Glosa($Sesion, $cliente->fields['id_contrato'], 'usa_impuesto_gastos', 'contrato')) {
						$chk_gastos = 'checked="checked"';
					}
					?>
					</td>
					<td class="al">
						<input type="hidden" name="usa_impuesto_gastos" value="0"/>
						<input class="span1"  type="checkbox" name="usa_impuesto_gastos" id="impuesto_gastos" value="1" <?php echo $chk_gastos ?> />
					</td>
				</tr>
			<?php } ?>

			<?php
			if ($contrato->Loaded()) {
				$separar_liquidaciones = $contrato->fields['separar_liquidaciones'];
				$exportacion_ledes = $contrato->fields['exportacion_ledes'];
			} else if (Conf::GetConf($Sesion, 'SepararLiquidacionesPorDefecto')) {
				$separar_liquidaciones = '1';
			} else {
				$separar_liquidaciones = '0';
			}
			?>
			<tr class="controls controls-row ">
				<td class="al">
					<div class="span4">	<?php echo __('Liquidar por separado (honorario y gastos)') ?></div>
				</td>
				<td class="al">
					<div class="span1">
						<input type="hidden" name="separar_liquidaciones" value="0"/>
						<input  class="span1" id="separar_liquidaciones" type="checkbox" name="separar_liquidaciones" value="1" <?php echo $separar_liquidaciones == '1' ? 'checked="checked"' : '' ?>  />
					</div>
				</td>
			</tr>
			<tr class="controls controls-row ">
				<td class="al">
					<div class="span4">
						<?php
						echo __('Encargado Comercial');
						if ($usuario_responsable_obligatorio) {
							echo $obligatorios('id_usuario_responsable');
						}
						?>
					</div>
				</td>
				<td class="al"><!-- Nuevo Select -->
					<?php
					if (Conf::GetConf($Sesion, 'CopiarEncargadoAlAsunto') && $contrato_defecto->Loaded() && !$contrato->Loaded()) {
						echo $Form->select('id_usuario_responsable', $Sesion->usuario->ListarActivos('', 'SOC'), $contrato_defecto->fields['id_usuario_responsable'] ? $contrato_defecto->fields['id_usuario_responsable'] : $id_usuario_responsable, array('empty' => 'Seleccione...', 'style' => 'width: 200px', 'class' => 'span3', 'onchange' => 'CambioEncargado(this)', 'disabled' => 'disabled'));
						echo '(Se copia del contrato principal)';
						echo '<input type="hidden" value="' . ($contrato_defecto->fields['id_usuario_responsable'] ? $contrato_defecto->fields['id_usuario_responsable'] :  $id_usuario_responsable) . '" name="id_usuario_responsable" />';
					} else {
						if ($contrato_defecto->Loaded() && $contrato->Loaded()) {
							if (Conf::GetConf($Sesion, 'CopiarEncargadoAlAsunto') && !$desde_agrega_cliente) {
								echo $Form->select('id_usuario_responsable', $Sesion->usuario->ListarActivos('', 'SOC'), $contrato->fields['id_usuario_responsable'] ? $contrato->fields['id_usuario_responsable'] : $id_usuario_responsable, array('empty' => 'Seleccione...', 'style' => 'width: 200px', 'class' => 'span3', 'onchange' => 'CambioEncargado(this)', 'disabled' => 'disabled'));
								echo '<input type="hidden" value="' . ($contrato_defecto->fields['id_usuario_responsable'] ? $contrato_defecto->fields['id_usuario_responsable'] : $id_usuario_responsable) . '" name="id_usuario_responsable" />';
								echo '(Se copia del contrato principal)';
							} else {
								//FFF si estoy agregando o editando un asunto que se cobra por separado
								echo $Form->select('id_usuario_responsable', $Sesion->usuario->ListarActivos('', 'SOC'), $contrato->fields['id_usuario_responsable'] ? $contrato->fields['id_usuario_responsable'] : $id_usuario_responsable, array('empty' => 'Seleccione...', 'style' => 'width: 200px', 'class' => 'span3', 'onchange' => 'CambioEncargado(this)'));
							}
						} else if (Conf::GetConf($Sesion, 'CopiarEncargadoAlAsunto') && $desde_agrega_cliente) {
							// Estoy creando un cliente (y su contrato por defecto).
							echo $Form->select('id_usuario_responsable', $Sesion->usuario->ListarActivos('', 'SOC'), $contrato->fields['id_usuario_responsable'] ? $contrato->fields['id_usuario_responsable'] : $id_usuario_responsable, array('empty' => 'Seleccione...', 'style' => 'width: 200px', 'class' => 'span3', 'onchange' => 'CambioEncargado(this)'));
						} else {
							echo $Form->select('id_usuario_responsable', $Sesion->usuario->ListarActivos('', 'SOC'), $contrato->fields['id_usuario_responsable'] ? $contrato->fields['id_usuario_responsable'] : $id_usuario_responsable, array('empty' => 'Seleccione...', 'style' => 'width: 200px', 'class' => 'span3'));
						}
					}
					?>
				</td>
			</tr>
			<?php if ($modulo_retribuciones_activo) { ?>
				<tr>
				<td class="al">
					<div class="span4">
						<?php echo __('Retribuci�n') . ' ' . __('Encargado Comercial');?>
					</div>
				</td>
				<td class="al">
					<input name="retribucion_usuario_responsable" type="text" size="6" value="<?php echo empty($contrato->fields['id_contrato']) ? Conf::GetConf($Sesion, 'RetribucionUsuarioResponsable') : $contrato->fields['retribucion_usuario_responsable']; ?>"/>%
				</td>
				</tr>
			<?php } ?>

			<?php if (Conf::GetConf($Sesion, 'EncargadoSecundario')) { ?>
				<tr class="controls controls-row ">
					<td class="al">
						<div class="span4">
							<?php
							echo __('Encargado Secundario');
							if (Conf::GetConf($Sesion, 'ObligatorioEncargadoSecundarioCliente')) {
								echo $obligatorios('id_usuario_secundario');
							}
							?>
						</div>
					</td>
					<td class="al">
						<?php echo Html::SelectArrayDecente($Sesion->usuario->ListarActivos("OR id_usuario = '{$contrato->fields['id_usuario_secundario']}'"), 'id_usuario_secundario', $contrato->fields['id_usuario_secundario'], 'class="span3"', 'Vacio', '200px'); ?>
					</td>
				</tr>
				<?php if ($modulo_retribuciones_activo) { ?>
					<tr>
						<td class="al">
							<div class="span4">
								<?php echo __('Retribuci�n') . ' ' . __('Encargado Secundario'); ?>
							</div>
						</td>
						<td class="al">
							<input name="retribucion_usuario_secundario" type="text" size="6" value="<?php echo empty($contrato->fields['id_contrato']) ? Conf::GetConf($Sesion, 'RetribucionUsuarioSecundario') : $contrato->fields['retribucion_usuario_secundario']; ?>" />%
						</td>
					</tr>
				<?php }
			} ?>
			<?php if (Conf::GetConf($Sesion, 'ExportacionLedes')) { ?>
				<tr   class="controls controls-row ">
					<td class="al">
						<div class="span4"><?php echo __('Usa exportaci�n LEDES'); ?></div>
					</td>
					<td class="al">
						<input type="hidden" name="exportacion_ledes" value="0"/>	<input  class="span1" id="exportacion_ledes" type="checkbox" name="exportacion_ledes" value="1" <?php echo $exportacion_ledes == '1' ? 'checked="checked"' : '' ?>  />
					</td>
				</tr>
			<?php } ?>
		</table>
		<!-- FIN RESPONSABLE -->

		<!-- DATOS FACTURACION -->
		<fieldset style="width: 97%;background-color: #FFFFFF;">
			<legend <?php echo!$div_show ? 'onClick="MuestraOculta(\'datos_factura\')" style="cursor:pointer"' : '' ?>>
				<?php if (!$div_show) { ?>
					<span id="datos_factura_img"><img src="<?php echo Conf::ImgDir(); ?>/mas.gif" border="0" id="datos_factura_img"></span>
				<?php } ?>
				&nbsp;<?php echo __('Datos Facturaci�n') ?>
			</legend>
			<table id='datos_factura' style='display:<?php echo $show ?>'>
				<tr>
					<td align="right" width='20%'>
						<?php echo __('ROL/RUT') . $obligatorios('factura_rut'); ?>
					</td>
					<td align="left" colspan="3">
						<input type="text" name="factura_rut" id="rut" value="<?php echo $contrato->fields['rut'] ? $contrato->fields['rut'] : $factura_rut ?>" size="30" maxlength="50" />
					</td>
				</tr>
				<tr>
					<td align="right" colspan="1">
						<?php echo __('Raz�n Social') . $obligatorios('factura_razon_social'); ?>
					</td>
					<td align="left" colspan="5">
						<input type="text" name='factura_razon_social' size="50" value="<?php echo $contrato->fields['factura_razon_social'] ? $contrato->fields['factura_razon_social'] : $factura_razon_social ?>"  />
					</td>
				</tr>
				<tr>
					<td align="right" colspan="1">
						<?php echo __('Giro') . $obligatorios('factura_giro'); ?>
					</td>
					<td align="left" colspan="5">
						<?php if (Conf::GetConf($Sesion, 'UsaGiroClienteParametrizable')) { ?>
							<?php echo Html::SelectArrayDecente($PrmCodigo->Listar("WHERE prm_codigo.grupo = 'GIRO_CLIENTE' ORDER BY prm_codigo.glosa ASC"), 'id_pais', $contrato->fields['factura_giro'] ? $contrato->fields['factura_giro'] : $factura_giro); ?>
						<?php } else { ?>
							<?php
								$prmGiro = new PrmGiro($Sesion);
								$giros = $prmGiro->Listar();
								if (count($giros) > 0) {
									echo $SelectHelper->ajax_select(
										'factura_giro_select',
										$contrato->fields['factura_giro'] ? $contrato->fields['factura_giro'] : $factura_giro,
										array('class' => 'span3', 'style' => 'display:inline', 'id' => 'factura_giro_select'),
										array(
											'source' => 'ajax/ajax_prm.php?prm=Giro&fields=orden,requiere_desglose&id=glosa',
											'onChange' => '
												var element = selected_factura_giro_select;
												var original_value = FormSelectHelper.original_factura_giro_select;
												if (element && element.requiere_desglose == "1") {
													jQuery("#factura_giro").val(original_value);
													jQuery("#factura_giro").show();
												} else {
													jQuery("#factura_giro").val(element.glosa);
													jQuery("#factura_giro").hide();
												}
												if (original_value && Object.keys(element).length == 0) {
													jQuery("#factura_giro_select").val("Otro");
													jQuery("#factura_giro").val(original_value);
													jQuery("#factura_giro").show();
												}
											'
										)
									);
									echo $Form->input('factura_giro', $contrato->fields['factura_giro'], array('placeholder' => __('Giro'), 'style' => 'display:none', 'class' => 'span3', 'label' => false, 'id' => 'factura_giro'));
								} else {
									echo $Form->input('factura_giro', $contrato->fields['factura_giro'], array('placeholder' => __('Giro'), 'class' => 'span3', 'label' => false, 'id' => 'factura_giro'));
								}
							?>
						<?php } ?>
					</td>
				</tr>
				<tr>
					<td align="right" colspan="1">
						<?php echo __('Direcci�n') . $obligatorios('factura_direccion'); ?>
					</td>
					<td align="left" colspan="5">
						<textarea class="span4" name='factura_direccion' rows=3 cols="55" ><?php echo $contrato->fields['factura_direccion'] ? $contrato->fields['factura_direccion'] : $factura_direccion ?></textarea>
					</td>
				</tr>
				<tr>
					<td align="right" colspan="1">
						<?php echo __('Comuna') . $obligatorios('factura_comuna'); ?>
					</td>
					<td align="left" colspan="5">
						<input  type="text"  name='factura_comuna' size=50 value="<?php echo $contrato->fields['factura_comuna'] ? $contrato->fields['factura_comuna'] : $factura_comuna ?>"  />
					</td>
				</tr>
				<tr>
					<td align="right" colspan="1">
						<?php echo __('C�digo Postal'); ?>
					</td>
					<td align="left" colspan="5">
						<input  type="text"  name='factura_codigopostal' size=50 value="<?php echo $contrato->fields['factura_codigopostal'] ? $contrato->fields['factura_codigopostal'] : $factura_codigopostal ?>"  />
					</td>
				</tr>
				<tr>
					<td align="right" colspan="1">
						<?php echo __('Ciudad') . $obligatorios('factura_ciudad'); ?>
					</td>
					<td align="left" colspan="5">
						<input  type="text"  name='factura_ciudad' size=50 value="<?php echo $contrato->fields['factura_ciudad'] ? $contrato->fields['factura_ciudad'] : $factura_ciudad ?>"  />
					</td>
				</tr>
				<tr>
					<td align="right" colspan="1">
						<?php echo __('Pa�s') . $obligatorios('id_pais'); ?>
					</td>
					<td align="left" colspan='3'>
						<?php echo Html::SelectArrayDecente($PrmPais->Listar('ORDER BY nombre ASC'), 'id_pais', $contrato->fields['id_pais'] ? $contrato->fields['id_pais'] : $id_pais, 'class ="span3"', 'Vac�o', '260px'); ?>
					</td>
				</tr>

				<?php if (Conf::GetConf($Sesion, 'RegionCliente')) { ?>
					<tr>
						<td align="right" colspan="1">
					<?php echo __('Regi�n') . $obligatorios('region_cliente'); ?>
						</td>
						<td align="left" colspan="5">
							<input type="text" name='region_cliente' size=50 value="<?php echo $contrato->fields['region_cliente'] ? $contrato->fields['region_cliente'] : $region_cliente ?>" />
						</td>
					</tr>
				<?php } ?>

				<tr>
					<td align="right" colspan="1">
						<?php echo __('Tel�fono') . $obligatorios('cod_factura_telefono'); ?>
					</td>
					<td align="left" colspan="5">
						<input type="text" class="span1" name='cod_factura_telefono' size=8 value="<?php echo $contrato->fields['cod_factura_telefono'] ? $contrato->fields['cod_factura_telefono'] : $cod_factura_telefono ?>" />&nbsp;<input type="text" class="span2" name='factura_telefono' size=30 value="<?php echo $contrato->fields['factura_telefono'] ?>" />
					</td>
				</tr>
				<tr>
					<td align="right" colspan="1">
						<?php echo __('Glosa factura') ?>
					</td>
					<td align="left" colspan="5">
						<textarea class="span4" name='glosa_contrato' rows=4 cols="55" ><?php echo $contrato->fields['glosa_contrato'] ? $contrato->fields['glosa_contrato'] : $glosa_contrato ?></textarea>
					</td>
				</tr>
				<?php
				$id_banco = $CuentaBanco->IdBancoDeCuenta($contrato->fields['id_cuenta'] ? $contrato->fields['id_cuenta'] : $id_cuenta);
				?>
				<tr>
					<td align="right" colspan="1">
						<?php echo __('Banco') ?>
					</td>
					<td align="left" colspan="2">
						<?php echo Html::SelectArrayDecente($PrmBanco->Listar('ORDER BY orden'), 'id_banco', $id_banco, 'onchange="CargarCuenta(\'id_banco\',\'id_cuenta\');"', 'Cualquiera', '150px'); ?>
					</td>

					<td align="right" colspan="1">
						<?php echo __('Cuenta') ?>
					</td>
					<td align="left" colspan="2">
						<?php echo Html::SelectArrayDecente($CuentaBanco->ListarDelBanco($id_banco), 'id_cuenta', $contrato->fields['id_cuenta'] ? $contrato->fields['id_cuenta'] : $id_cuenta, '', $tiene_banco ? '' : 'Cualquiera', '150px'); ?>
					</td>
				</tr>
				<?php
				if (Conf::GetConf($Sesion, 'SegundaCuentaBancaria')) {
					$id_banco2 = $CuentaBanco->IdBancoDeCuenta($contrato->fields['id_cuenta2'] ? $contrato->fields['id_cuenta2'] : $id_cuenta2);
					?>
					<tr>
						<td align="right" colspan="1">
							<?php echo __('Banco Secundario') ?>
						</td>
						<td align="left" colspan="2">
							<?php echo Html::SelectArrayDecente($PrmBanco->Listar('ORDER BY orden'), 'id_banco2', $id_banco2, 'onchange="CargarCuenta(\'id_banco2\',\'id_cuenta2\');"', 'Cualquiera', '150px'); ?>
						</td>

						<td align="right" colspan="1">
							<?php echo __('Cuenta Secundaria') ?>
						</td>
						<td align="left" colspan="2">
							<?php echo Html::SelectArrayDecente($CuentaBanco->ListarDelBanco($id_banco2), 'id_cuenta2', $contrato->fields['id_cuenta2'] ? $contrato->fields['id_cuenta2'] : $id_cuenta2, '', $tiene_banco ? '' : 'Cualquiera', '150px'); ?>
						</td>
					</tr>
				<?php } ?>
				<?php
				$estudios_array = PrmEstudio::GetEstudios($Sesion);

				// Si no viene de un POST puede ser nuevo o existente, si es nuevo ocupo el del $contrato
				if (empty($id_estudio)) {
					$id_estudio = $contrato->fields['id_estudio'];
				}
				if (count($estudios_array) > 1) { ?>
					<tr>
						<td align="right"><?php echo __('Compan�a') ?></td>
						<td align="left" colspan="5">
							<?php echo Html::SelectArray($estudios_array, 'id_estudio', $id_estudio); ?>
						</td>
					</tr>
				<?php } else { ?>
					<input type="hidden" name="id_estudio" value="<?php echo $estudios_array[0]['id_estudio']; ?>" />
				<?php } ?>
			</table>

		</fieldset>
		<!-- FIN DATOS FACTURACION -->
		<br>


		<!-- SOLICITANTE -->
		<fieldset style="width: 97%; background-color: #FFFFFF;">
			<legend <?php echo !$div_show ? 'onClick="MuestraOculta(\'datos_solicitante\')" style="cursor:pointer"' : '' ?> >
				<?php if (!$div_show) {?>
					<span id="datos_solicitante_img"><img src="<?php echo Conf::ImgDir(); ?>/mas.gif" border="0" id="datos_solicitante_img"></span>
				<?php } ?>
				&nbsp;<?php echo __('Solicitante') ?></legend>
			<table id='datos_solicitante' style='display:<?php echo $show ?>'>
				<?php if (Conf::GetConf($Sesion, 'TituloContacto')) { ?>
					<tr>
						<td align="right" width="20%">
							<?php echo __('Titulo') . $obligatorios('titulo_contacto'); ?>
						</td>
						<td align="left" colspan='3'>
							<?php
							$PrmTituloPersona = new PrmTituloPersona($Sesion);
							echo Html::SelectArrayDecente($PrmTituloPersona->Listar('ORDER BY id_titulo'), 'titulo_contacto', $contrato->fields['titulo_contacto'] ? $contrato->fields['titulo_contacto'] : $titulo_contacto, 'class="span3"', 'Vacio', '120px');
							?>&nbsp;&nbsp;
						</td>
					</tr>
					<tr>
						<td align="right" width='20%'>
							<?php echo __('Nombre') . $obligatorios('nombre_contacto'); ?>
						</td>
						<td align='left' colspan='3'>
							<input type="text" size='55' name="nombre_contacto" id="nombre_contacto" value="<?php echo $contrato->fields['contacto'] ? $contrato->fields['contacto'] : $nombre_contacto ?>" />
						</td>
					</tr>
					<tr>
						<td align="right" width='20%'>
							<?php echo __('Apellido') . $obligatorios('apellido_contacto'); ?>
						</td>
						<td align='left' colspan='3'>
							<input type="text" size='55' name="apellido_contacto" id="apellido_contacto" value="<?php echo $contrato->fields['apellido_contacto'] ? $contrato->fields['apellido_contacto'] : $apellido_contacto ?>"  />
						</td>
					</tr>
				<?php } else { ?>
					<tr>
						<td align="right" width='20%'>
							<?php echo __('Nombre') . $obligatorios('contacto'); ?>
						</td>
						<td align='left' colspan='3'>
							<input type="text" size='55' name="contacto" id="contacto" value="<?php echo $contrato->fields['contacto'] ? $contrato->fields['contacto'] : $contacto ?>"  />
						</td>
					</tr>
				<?php } ?>
				<tr>
					<td align="right" colspan="1">
						<?php echo __('Tel�fono') . $obligatorios('fono_contacto_contrato'); ?>
					</td>
					<td align="left" colspan="5">
						<input type="text" name="fono_contacto_contrato" id="fono_contacto_contrato" size="30" value="<?php echo $contrato->fields['fono_contacto'] ? $contrato->fields['fono_contacto'] : $fono_contacto_contrato ?>" />
					</td>
				</tr>
				<tr>
					<td align="right" colspan="1">
						<?php echo __('E-mail') . $obligatorios('email_contacto_contrato'); ?>
					</td>
					<td align="left" colspan="5">
						<input type="text" name="email_contacto_contrato" id="email_contacto_contrato" size="55" value="<?php echo $contrato->fields['email_contacto'] ? $contrato->fields['email_contacto'] : $email_contacto_contrato ?>"  />
					</td>
				</tr>
				<tr>
					<td align="right" colspan="1">
						<?php echo __('Direcci�n env�o') . $obligatorios('direccion_contacto_contrato'); ?>
					</td>
					<td align="left" colspan="5">
						<textarea name="direccion_contacto_contrato" id="direccion_contacto_contrato" rows="4" cols="55" ><?php echo $contrato->fields['direccion_contacto'] ?  $contrato->fields['direccion_contacto'] : $direccion_contacto_contrato ?></textarea>
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
				if ($contrato->fields['periodo_fecha_inicio'] != '0000-00-00' && $contrato->fields['periodo_fecha_inicio'] != '' && $contrato->fields['periodo_fecha_inicio'] != 'NULL') {
					$fecha_ini = Utiles::sql2date($contrato->fields['periodo_fecha_inicio']);
				}
			}
		} else {
			$fecha_ini = Utiles::sql2date($contrato->fields['periodo_fecha_inicio']);
		}

		if (!$id_moneda) {
			$id_moneda = Moneda::GetMonedaTarifaPorDefecto($Sesion);
		}
		if (!$id_moneda) {
			$id_moneda = Moneda::GetMonedaBase($Sesion);
		}

		if (!$id_moneda_tramite) {
			$id_moneda_tramite = Moneda::GetMonedaTramitePorDefecto($Sesion);
		}

		if (!$opc_moneda_total) {
			$opc_moneda_total = Moneda::GetMonedaTotalPorDefecto($Sesion);
		}
		if (!$opc_moneda_total) {
			$opc_moneda_total = Moneda::GetMonedaBase($Sesion);
		}

		$config_validar_tarifa = ( Conf::GetConf($Sesion, 'RevisarTarifas') ? ' RevisarTarifas( \'id_tarifa\', \'id_moneda\', this.form, true);' : '' );
		?>

		<!-- COBRANZA -->
		<fieldset style="width: 98%; background-color: #FFFFFF;">
			<legend <?php echo!$div_show ? 'onClick="MuestraOculta(\'datos_cobranza\')" style="cursor:pointer"' : '' ?> />
				<?php if (!$div_show) { ?>
					<span id="datos_cobranza_img"><img src="<?php echo Conf::ImgDir() ?>/mas.gif" border="0" id="datos_cobranza_img"></span>
				<?php } ?>
			&nbsp;<?php echo __('Datos de Tarificaci�n') ?>
			</legend>
			<div id='datos_cobranza' style='display:<?php echo $show ?>' width="98%">
				<table width="100%" >
					<tr id="divthh">
						<td  class="ar">
							<?php echo __('Tarifa horas') . $obligatorios('tipo_tarifa'); ?>
						</td>
						<td align="left" width="80%" style="font-size:10pt;">
							<table  style="float:left;" class="span7">
								<tr>
									<td class="span4">
										<div class="controls controls-row ">
											<input type="radio" name="tipo_tarifa" id="tipo_tarifa_variable" value="variable" <?php echo empty($valor_tarifa_flat) ? 'checked' : '' ?>/>
											<?php echo Html::SelectArrayDecente($tarifa->Listar('WHERE tarifa.tarifa_flat IS NULL ORDER BY tarifa.glosa_tarifa'), 'id_tarifa', $contrato->fields['id_tarifa'] ? $contrato->fields['id_tarifa'] : $tarifa_default, 'onclick="$(\'tipo_tarifa_variable\').checked = true;" ' . ( strlen($config_validar_tarifa) > 0 ? 'onchange="' . $config_validar_tarifa . '"' : '')); ?>
											<input type="hidden" name="id_tarifa_hidden" id="id_tarifa_hidden" value="<?php echo $contrato->fields['id_tarifa'] ? $contrato->fields['id_tarifa'] : $tarifa_default; ?>" />
										</div>

										<div class="controls controls-row ">
											<label for="tipo_tarifa_flat" class="span2"><input type="radio" name="tipo_tarifa" id="tipo_tarifa_flat" value="flat" <?php echo empty($valor_tarifa_flat) ? '' : 'checked' ?>/>
												 <?php echo __('Plana por'); ?>
											</label>
											<input id="tarifa_flat" class="input-small" type="text" name="tarifa_flat" onclick="$('tipo_tarifa_flat').checked = true" value="<?php echo $valor_tarifa_flat ?>"/>
											<input type="hidden" id="id_tarifa_flat"  name="id_tarifa_flat" value="<?php echo empty($valor_tarifa_flat) ? '' : $contrato->fields['id_tarifa'] ?>"/>
										</div>
									</td>
									<td>
										<?php echo __('Tarifa en') . $obligatorios('id_moneda'); ?>
										<?php echo Html::SelectArrayDecente($Moneda->Listar('ORDER BY id_moneda'), 'id_moneda', $contrato->fields['id_moneda'] ? $contrato->fields['id_moneda'] : $id_moneda, 'onchange="actualizarMoneda(); ' . $config_validar_tarifa . '"', '', '80px'); ?>
										<input type="hidden" name="id_moneda_hidden" id="id_moneda_hidden" value="<?php echo $contrato->fields['id_moneda'] ? $contrato->fields['id_moneda'] : $id_moneda; ?>" />
										&nbsp;&nbsp;
										<?php if ($Sesion->usuario->Es('TAR')) { ?>
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
							<?php echo __('Forma de Tarificaci�n') . $obligatorios('forma_cobro'); ?>
						</td>
						<?php
						if (!$contrato->fields['forma_cobro'])
							$contrato_forma_cobro = 'TASA';
						else
							$contrato_forma_cobro = $contrato->fields['forma_cobro'];

						// Setear valor del multiselect por de usuarios retainer
						if (!is_array($usuarios_retainer)) {
							$usuarios_retainer = explode(',', $contrato->fields['retainer_usuarios']);
						}
						?>
						<td align="left" style="font-size:10pt;">
							<input type="hidden" id="forma_cobro_posterior"  name="forma_cobro_posterior" value="<?php echo $contrato_forma_cobro ?>"/>
							<div id="div_cobro" class="buttonset">
								<input class="formacobro" id="fc1" type="radio" name="forma_cobro" value="TASA" <?php echo $contrato_forma_cobro == "TASA" ? "checked='checked'" : "" ?> />
								<label <?php echo TTip($tip_tasa) ?>  for="fc1"><?php echo __('Tasas/HH'); ?></label>&nbsp;
								<input class="formacobro"  id="fc2" type=radio name="forma_cobro" value="RETAINER" <?php echo $contrato_forma_cobro == "RETAINER" ? "checked='checked'" : "" ?> />
								<label <?php echo TTip($tip_retainer) ?>  for="fc2">Retainer</label> &nbsp;
								<input class="formacobro"  id="fc3" type="radio" name="forma_cobro"  value="FLAT FEE" <?php echo $contrato_forma_cobro == "FLAT FEE" ? "checked='checked'" : "" ?> />
								<label <?php echo TTip($tip_flat) ?> for="fc3"><?php echo __('Flat fee') ?></label>&nbsp;
								<input class="formacobro"  id="fc5" type="radio" name="forma_cobro"  value="CAP" <?php echo $contrato_forma_cobro == "CAP" ? "checked='checked'" : "" ?> />
								<label  <?php echo TTip($tip_cap) ?>  for="fc5"><?php echo __('Cap') ?></label>&nbsp;
								<input class="formacobro"  id="fc6" type="radio" name="forma_cobro"  value="PROPORCIONAL" <?php echo $contrato_forma_cobro == "PROPORCIONAL" ? "checked='checked'" : "" ?> />
								<label <?php echo TTip($tip_proporcional) ?> for="fc6">Proporcional</label> &nbsp;
								<input class="formacobro"  id="fc7" type="radio" name="forma_cobro"  value="HITOS" <?php echo $contrato_forma_cobro == "HITOS" ? "checked='checked'" : "" ?> />
								<label <?php echo TTip($tip_hitos) ?> for="fc7"><?php echo __('Hitos') ?></label>
								<?php if (!Conf::GetConf($Sesion, 'EsconderTarifaEscalonada')) { ?>
									<input class="formacobro"  id="fc8" type="radio" name="forma_cobro"  value="ESCALONADA" <?php echo $contrato_forma_cobro == "ESCALONADA" ? "checked='checked'" : "" ?> />
									<label <?php echo TTip($tip_escalonada) ?> for="fc8"><?php echo __('Escalonada') ?></label>
								<?php } ?>
							</div>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<div style='border:1px solid #999999;width:400px;padding:4px 4px 4px 4px' id="div_forma_cobro">
								<div id="div_monto" align="left" style="display:none; background-color:#C6DEAD;padding-left:2px;padding-top:2px;">
									<span id="span_monto">
										<?php echo __('Monto') . $obligatorios('monto_posterior'); ?>
										<input type="hidden" id="monto_posterior"  name="monto_posterior" value="<?php echo $contrato->fields['monto'] ?>"/>
										&nbsp;<input id="monto" name="monto" size="7" value="<?php echo $contrato->fields['monto'] ?>" onchange="actualizarMonto();"/>&nbsp;&nbsp;
									</span>
									&nbsp;&nbsp;
									<?php echo __('Moneda') . $obligatorios('id_moneda_monto'); ?>
									&nbsp;
									<?php
									$id_moneda_seleccionada = $contrato->fields['id_moneda_monto'] ? $contrato->fields['id_moneda_monto'] : ($contrato->fields['id_moneda'] ? $contrato->fields['id_moneda'] : $id_moneda_monto);
									echo Html::SelectArrayDecente($Moneda->Listar('ORDER BY id_moneda'), 'id_moneda_monto', $id_moneda_seleccionada, 'onchange="actualizarMonto();"', '', '80px');
									?>
								</div>
								<div id="div_horas" align="left" style="display:none; vertical-align: top; background-color:#C6DEAD;padding-left:2px;">
									&nbsp;
									<?php echo __('Horas') . $obligatorios('retainer_horas'); ?>
									&nbsp;<input name="retainer_horas" size="7" value="<?php echo $contrato->fields['retainer_horas'] ?>" style="vertical-align: top;" />
									<!-- Incluiremos un multiselect de usuarios para definir los usuarios de quienes se
											 desuentan las horas con preferencia -->
									<?php if (Conf::GetConf($Sesion, 'RetainerUsuarios')) { ?>
										<div id="div_retainer_usuarios" style="display:inline; vertical-align: top; background-color:#C6DEAD;padding-left:2px;">
											&nbsp;<?php echo __('Usuarios') ?>
											&nbsp;<?php echo Html::SelectArrayDecente($Sesion->usuario->ListarActivos('', 'PRO'), 'usuarios_retainer[]', $usuarios_retainer, TTip($tip_retainer_usuarios) . 'class="selectMultiple" multiple size="5"', '', '160px'); ?>
										</div>
									<?php } ?>
								</div>
								<div id="div_fecha_cap" align="left" style="display:none; background-color:#C6DEAD;padding-left:2px;">
									<table style='border: 0px solid' bgcolor='#C6DEAD'>
										<?php if ($cobro) { ?>
											<tr>
												<td>
													<?php echo __('Monto utilizado') . $obligatorios('monto'); ?>
												</td>
												<td align="left">&nbsp;<label style='background-color:#FFFFFF'> <?php echo $cobro->TotalCobrosCap($contrato->fields['id_contrato']) > 0 ? $cobro->TotalCobrosCap($contrato->fields['id_contrato']) : 0; ?> </label></td>
											</tr>
										<?php } ?>
										<tr>
											<td>
												<?php echo __('Fecha inicio') . $obligatorios('fecha_inicio_cap'); ?>
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
															<?php echo Html::SelectArrayDecente($tarifa->Listar(), 'esc_id_tarifa_1', $contrato->fields['esc1_id_tarifa'], '', '', '120px; font-size:9pt;'); ?>
														</span>
														<span id="tipo_forma_1_2" <?php echo $contrato->fields['esc1_monto'] > 0 ? 'style="display: inline-block;"' : 'style="display: none;"'; ?> >
															<input type="text" size="7" style="font-size:9pt; width:116px;" id="esc_monto_1" value="<?php if (!empty($contrato->fields['esc1_monto'])) echo $contrato->fields['esc1_monto']; else echo ''; ?>" name="esc_monto[]" />
														</span>
													</span>
													<span><?php echo __('en'); ?></span>
													<?php echo Html::SelectArrayDecente($Moneda->Listar('ORDER BY id_moneda'), 'esc_id_moneda_1', $contrato->fields['esc1_id_moneda'], '', '', '70px; font-size:9pt;'); ?>
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
															<?php echo Html::SelectArrayDecente($tarifa->Listar(), 'esc_id_tarifa_2', $contrato->fields['esc2_id_tarifa'], '', '', '120px; font-size:9pt;'); ?>
														</span>
														<span id="tipo_forma_2_2" <?php echo $contrato->fields['esc2_monto'] > 0 ? 'style="display: inline-block;"' : 'style="display: none;"'; ?> >
															<input type="text" size="7" style="font-size:9pt; width:116px;" id="esc_monto_2" name="esc_monto[]" value="<?php if (!empty($contrato->fields['esc2_monto'])) echo $contrato->fields['esc2_monto']; else echo ''; ?>" />
														</span>
													</span>
													<span><?php echo __('en'); ?></span>
													<?php echo Html::SelectArrayDecente($Moneda->Listar('ORDER BY id_moneda'), 'esc_id_moneda_2', $contrato->fields['esc2_id_moneda'], '', '', '70px; font-size:9pt;'); ?>
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
															<?php echo Html::SelectArrayDecente($tarifa->Listar(), 'esc_id_tarifa_3', $contrato->fields['esc3_id_tarifa'], '', '', '120px; font-size:9pt;'); ?>
														</span>
														<span id="tipo_forma_3_2" <?php echo $contrato->fields['esc3_monto'] > 0 ? 'style="display: inline-block;"' : 'style="display: none;"'; ?> >
															<input type="text" size="7" style="font-size:9pt; width:116px;" id="esc_monto_3" name="esc_monto[]" value="<?php if (!empty($contrato->fields['esc3_monto'])) echo $contrato->fields['esc3_monto']; else echo ''; ?>" />
														</span>
													</span>
													<span><?php echo __('en'); ?></span>
													<?php echo Html::SelectArrayDecente($Moneda->Listar('ORDER BY id_moneda'), 'esc_id_moneda_3', $contrato->fields['esc3_id_moneda'], '', '', '70px; font-size:9pt;'); ?>
													<span><?php echo __('con'); ?> </span>
													<input type="text" name="esc_descuento[]" id="esc_descuento_3" value="<?php	echo empty($contrato->fields['esc3_descuento']) ? '' : $contrato->fields['esc3_descuento']; ?>" size="4" />
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
															<?php echo Html::SelectArrayDecente($tarifa->Listar(), 'esc_id_tarifa_4', $contrato->fields['esc4_id_tarifa'], '', '', '60px; font-size:9pt;'); ?>
														</span>
														<span id="tipo_forma_4_2" <?php echo $contrato->fields['esc4_monto'] > 0 ? 'style="display: inline-block;"' : 'style="display: none;"'; ?>>
															<input type="text" size="7" style="font-size:9pt; width:116px;" id="esc_monto_4" value="<?php if (!empty($contrato->fields['esc4_monto'])) echo $contrato->fields['esc4_monto']; else echo ''; ?>" name="esc_monto[]" />
														</span>
													</span>
													<span><?php echo __('en'); ?></span>
													<?php echo Html::SelectArrayDecente($Moneda->Listar('ORDER BY id_moneda'), 'esc_id_moneda_4', $contrato->fields['esc4_id_moneda'], '', '', '70px; font-size:9pt;'); ?>
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
										<td width="45%">Descripci�n</td>
										<td width="23%">Monto</td>
										<td width="5%">&nbsp;</td>
									</tr>
								</thead>
								<tbody id="body_hitos">
									<?php
									$cobros_pendientes = array();
									if ($contrato->Loaded()) {
										$Criteria = new Criteria($Sesion);
										$cobros_pendientes = $Criteria
												->add_from('cobro_pendiente')
												->add_select('fecha_cobro')
												->add_select('descripcion')
												->add_select('monto_estimado')
												->add_select($contrato_nuevo ? 'NULL' : 'id_cobro', 'id_cobro')
												->add_select('observaciones')
												->add_restriction(CriteriaRestriction::and_clause(
														CriteriaRestriction::equals('id_contrato', $contrato->fields['id_contrato']),
														CriteriaRestriction::equals('hito', '1')
												))
												->add_ordering('id_cobro_pendiente')
												->run();
									}
									$total_cobros_pendientes = count($cobros_pendientes);
									for ($i = 2; $i - 2 < $total_cobros_pendientes; $i++) {
										$temp = $cobros_pendientes[$i - 2];
										$disabled = empty($temp['id_cobro']) ? '' : ' disabled="disabled" ';
										?>
										<tr bgcolor="<?php echo $i % 2 == 0 ? $color_par : $color_impar ?>" id="fila_hito_<?php echo $i ?>" >
											<td align="center" nowrap>
												<?php if ($disabled) { ?>
													<input type="hidden" name="hito_disabled[<?php echo $i ?>]" value= "" />
												<?php } ?>
												<input type="text" name="hito_fecha[<?php echo $i ?>]" value='<?php echo Utiles::sql2date($temp['fecha_cobro']) ?>' id="hito_fecha_<?php echo $i ?>" size="11" maxlength="10" <?php echo $disabled ?>/>
													<?php if (!$disabled) { ?>
														<img src="<?php echo Conf::ImgDir() ?>/calendar.gif" id="img_fecha_hito_<?php echo $i ?>" style="cursor:pointer" />
													<?php } ?>
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
												<input type="text" name="hito_monto_estimado[<?php echo $i ?>]" value='<?php echo empty($temp['monto_estimado']) ? '' : number_format($temp['monto_estimado'], 2, '.', '') ?>' id="hito_monto_estimado_<?php echo $i ?>" size="20" <?php echo $disabled ?>/>
											</td>
											<td align="center">
												<?php if (!$disabled) { ?>
													<img src='<?php echo Conf::ImgDir() ?>/eliminar.gif' style='cursor:pointer' onclick='eliminarHito(this);' />
												<?php } ?>
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
											<input type="text" name="hito_monto_estimado[1]" value='' id="hito_monto_estimado_1" size="20" />
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
						<td class="ar" >
							<?php echo __('Mostrar total en') . $obligatorios('opc_moneda_total'); ?>
						</td>
						<td align="left">
							<?php echo Html::SelectArrayDecente($Moneda->Listar('ORDER BY id_moneda'), 'opc_moneda_total', $contrato->fields['opc_moneda_total'] ? $contrato->fields['opc_moneda_total'] : $opc_moneda_total, '', '', '60px; font-size:10pt;'); ?>
							<span id="monedas_para_honorarios_y_gastos" style="display: none">
								<?php
								echo __('para honorarios y en');
								echo Html::SelectArrayDecente($Moneda->Listar('ORDER BY id_moneda'), 'opc_moneda_gastos', $contrato->fields['opc_moneda_gastos'], '', '', '60px; font-size:10pt;');
								echo __('para gastos');
								?>.
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
							<?php echo __('Detalle Cobranza') . $obligatorios('observaciones'); ?>
						</td>
						<td align="left">
							<textarea name="observaciones" rows="3" cols="47"><?php echo $contrato->fields['observaciones'] ? $contrato->fields['observaciones'] : $observaciones ?></textarea>
						</td>
					</tr>
					<tr>
						<td colspan="2"><hr size="1"></td>
					</tr>
					<tr>
						<td colspan="2" align="center">
							<fieldset style="width: 97%; background-color: #FFFFFF;">
								<legend <?php echo !$div_show ? 'onClick="MuestraOculta(\'datos_tramites\')" style="cursor:pointer"' : '' ?> />
									<?php echo !$div_show ? '<span id="datos_tramites_img"><img src="' . Conf::ImgDir() . '/mas.gif" border="0" id="datos_tramites_img"></span>' : '' ?>
									&nbsp;<?php echo __('Tr�mites') ?>
								</legend>
								<div id='datos_tramites' style="display:<?php echo $show ?>;" width="100%">
									<table width="100%">
										<tr>
											<td align="right" width="25%">
												<?php echo __('Tarifa Tr�mites') ?>
											</td>
											<td align="left" width="75%">
												<?php echo Html::SelectArrayDecente($TramiteTarifa->Listar('ORDER BY tramite_tarifa.glosa_tramite_tarifa'), 'id_tramite_tarifa', $contrato->fields['id_tramite_tarifa'] ? $contrato->fields['id_tramite_tarifa'] : $tramite_tarifa_default); ?>&nbsp;&nbsp;
												<?php echo __('Tarifa en') ?>
												<?php echo Html::SelectArrayDecente($Moneda->Listar('ORDER BY id_moneda'), 'id_moneda_tramite', $contrato->fields['id_moneda_tramite'] ? $contrato->fields['id_moneda_tramite'] : $id_moneda_tramite, 'onchange="actualizarMoneda();"', '', '80px'); ?>&nbsp;&nbsp;
												<?php if ($Sesion->usuario->Es('TAR')) { ?>
													<span style='cursor:pointer' <?php echo TTip(__('Agregar nueva tarifa')) ?> onclick='CreaTramiteTarifa(this.form,true)'><img src="<?php echo Conf::ImgDir() ?>/mas.gif" border="0"></span>
													<span style='cursor:pointer' <?php echo TTip(__('Editar tarifa seleccionada')) ?> onclick='CreaTramiteTarifa(this.form,false)'><img src="<?php echo Conf::ImgDir() ?>/editar_on.gif" border="0"></span>
												<?php } ?>
											</td>
										</tr>
									</table>
									<br />
								</div>
							</fieldset>
						</td>
					</tr>

					<?php
					$query = "SELECT count(1) AS total FROM asunto WHERE id_contrato='{$contrato->fields['id_contrato']}'";
					$asuntos = mysql_fetch_assoc(mysql_query($query, $Sesion->dbh));
					?>
					<tr id="tr_cobros_programados">
						<td colspan="2" align="center">
							<fieldset style="width: 97%; background-color: #FFFFFF;">
								<legend <?php echo!$div_show ? 'onClick="MuestraOculta(\'datos_cobros_programados\')" style="cursor:pointer"' : '' ?> />
									<?php if (!$div_show ) { ?>
										<span id="datos_cobros_programados_img"><img src="<?php echo Conf::ImgDir() ?>/mas.gif" border="0" id="datos_cobros_programados_img"/></span>
									<?php } ?>
								&nbsp;<?php echo __('Cobros Programados') ?>
								</legend>
								<div id='datos_cobros_programados' style='display:<?php echo $show ?>;' width="100%">
									<?php
									if ($asuntos['total'] > 0) {
										$query = "SELECT MAX(fecha_creacion) FROM cobro WHERE id_contrato='" . $contrato->fields['id_contrato'] . "' AND estado!='CREADO' AND estado!='EN REVISION'";
										$resp = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);
										list($ultimo_cobro) = mysql_fetch_array($resp);
										?>

										<script type="text/javascript">
											var toggleVisibilityOtherInterval;
											var to;
											var updateErrandRate;
											var listIncludedErrands;

											jQuery('document').ready(function () {
												toggleVisibilityOther = function (sender, id) {
													var j_other = jQuery('#' + id);
													var j_sender = jQuery(sender);
													if (j_sender.val() == -1) {
														//j_other.attr('disabled', false);
														j_sender.attr('disabled', true);
														j_other.show();

													} else {
														j_sender.attr('disabled', false);
														j_other.hide();
													}
												};
											});
										</script>

										<table width="100%">
											<tr>
												<td align="right" width="30%">
													<?php echo __('Generar ') . __('Cobros') . __(' a partir del') ?>
												</td>
												<td align="left">
													<input type="text" name="periodo_fecha_inicio" id="periodo_fecha_inicio"
														size="11" maxlength="10" value="<?php echo $fecha_ini ?>" />
													<img src="<?php echo Conf::ImgDir() ?>/calendar.gif"
														id="img_periodo_fecha_inicio" style="cursor:pointer" />
													&nbsp;
													<?php if ($ultimo_cobro) { ?>
														<span style="font-size:10px">
															<?php echo __('Fecha �ltimo cobro emitido:') . ' ' . Utiles::sql2date($ultimo_cobro); ?>
														</span>
													<?php } ?>
												</td>
											</tr>
											<tr>
												<td align="right" style="vertical-align: middle;">
													<?php echo __('Cobrar cada') ?>
												</td>
												<td align="left" style="vertical-align: middle;">
													<?php
													$intervalos_disponibles = array(
														'1' => '1 ' . __('Mes'),
														'2' => '2 ' . __('Meses'),
														'3' => '3 ' . __('Meses'),
														'4' => '4 ' . __('Meses'),
														'6' => '6 ' . __('Meses'),
														'12' => '1 ' . __('A�o')/*,
														'-1' => __('Otro')*/
													);

													$repeticiones_disponibles = array(
														'0' => __('Indefinidamente'),
														'1' => __('Por 1 per�odo'),
														'2' => __('Por 2 per�odos'),
														'3' => __('Por 3 per�odos'),
														'4' => __('Por 4 per�odos'),
														'5' => __('Por 5 per�odos')/*,
														'-1' => __('Otro')*/
													);

													echo Html::SelectArrayDecente(
														$intervalos_disponibles,
														'periodo_intervalo',
														$contrato->fields['periodo_intervalo'],
														'onChange="toggleVisibilityOther(this, \'periodic_billing_interval_other\')"'
													);
													?>
													&nbsp;
													<span id="periodic_billing_interval_other" style="display: none">
														<input type="text" name="periodo_intervalo2" size="3" maxlength="2"
															value="<?php echo $contrato->fields['periodo_intervalo']; ?>" />
														<?php echo __('meses'); ?>
													</span>
													<?php __('durante'); ?>
													&nbsp;
													<?php
													echo Html::SelectArrayDecente(
														$repeticiones_disponibles,
														'periodo_repeticiones',
														$contrato->fields['periodo_repeticiones'],
														'onChange="toggleVisibilityOther(this, \'periodic_billing_repeat_other\')"'
													);
													?>
													<span id="periodic_billing_repeat_other" style="display: none">
														<input type="text" name="periodo_repeticiones2" size="3" maxlength="2"
															value="<?php echo $contrato->fields['periodo_repeticiones']; ?>"/>
														<?php echo __('veces'); ?>
													</span>
												</td>
											</tr>
											<tr>
												<td>&nbsp;</td>
												<td>
													<label>
														<input type="hidden" name="emitir_liquidacion_al_generar" value="0" />
														<input type="checkbox" name="emitir_liquidacion_al_generar" value="1"
															<?php echo $contrato->fields['emitir_liquidacion_al_generar'] == 1 ? 'checked="checked"' : ''; ?>>
														<?php echo __('Emitir esta liquidaci�n al generar'); ?>
													</label>
												</td>
											</tr>
											<tr>
												<td>&nbsp;</td>
												<td>
													<label>
														<input type="hidden" name="enviar_liquidacion_al_generar" value="0" />
														<input type="checkbox" name="enviar_liquidacion_al_generar" value="1"
															<?php echo $contrato->fields['enviar_liquidacion_al_generar'] == 1 ? 'checked="checked"' : ''; ?>>
														<?php echo __('Enviar por Email esta liquidaci�n al Cliente'); ?>
													</label>
												</td>
											</tr>
											<tr>
												<td colspan="2">&nbsp;</td>
											</tr>
											<tr>
												<td colspan="2">
													<table width="100%">
														<tr>
															<td width="15%">&nbsp;</td>
															<td>
																<strong><?php echo __('Pr�ximos Cobros') ?></strong>
																&nbsp;
																<img src="<?php echo Conf::ImgDir() ?>/reload_16.png" onclick='generarFechas()' style='cursor:pointer' <?php echo TTip(__('Actualizar fechas seg�n per�odo')) ?>>
															</td>
														</tr>
													</table>
												</td>
											</tr>
											<tr>
												<td align="center" colspan="2">

													<table id="tabla_fechas" width="70%" style="border:1px solid #999;" cellpadding="2" cellspacing="2" style="border-collapse:collapse;">
														<thead>
															<tr bgcolor="#6CA522">
																<td width="20%">Fecha</td>
																<td width="65%">Descripci&oacute;n</td>
																<td width="10%">Monto</td>
																<td width="5%">&nbsp;</td>
															</tr>
														</thead>
														<tbody id="id_body">
															<tr id="fila_fecha_1">
																<td align="center">
																	<input type="text" class="fechadiff" name="valor_fecha[1]" value="" id="valor_fecha_1" maxlength="10" size="10" />
																</td>
																<td align="left">
																	<input type="text" name="valor_descripcion[1]" value='' id="valor_descripcion_1" size="40" />
																</td>
																<td align="right">
																	<div class="input-prepend input">
																		<span class="moneda_tabla add-on"></span><input type="text" name="valor_monto_estimado[1]" value="" id="valor_monto_estimado_1" size="10" />
																	</div>
																</td>
																<td align="center">
																	<img src="<?php echo Conf::ImgDir() ?>/mas.gif" id="img_mas" style="cursor:pointer" onclick="agregarFila();" />
																</td>
															</tr>
															<?php
															$color_par = "#f0f0f0";
															$color_impar = "#ffffff";
															$query = "SELECT cp.fecha_cobro, cp.descripcion, cp.monto_estimado FROM cobro_pendiente cp WHERE cp.id_contrato = '{$contrato->fields['id_contrato']}' AND cp.id_cobro IS NULL AND cp.hito = '0' ORDER BY fecha_cobro";
															$resp = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);
															for ($i = 2; $temp = mysql_fetch_array($resp); $i++) {
																?>
																<tr bgcolor="<?php echo $i % 2 == 0 ? $color_par : $color_impar ?>" id="fila_fecha_<?php echo $i ?>" class="<?php echo $i > 6 ? 'esconder' : 'mostrar' ?>">
																	<td align="center" style="vertical-align:middle">
																		<input type="hidden" class="fecha" value="<?php echo Utiles::sql2date($temp['fecha_cobro']) ?>" id="valor_fecha_<?php echo $i ?>" name="valor_fecha[<?php echo $i ?>]"><?php echo Utiles::sql2date($temp['fecha_cobro']) ?>
																	</td>
																	<td align="left">
																		<input size="40" type="text" class="descripcion" value="<?php echo $temp['descripcion'] ?>" id="valor_descripcion_<?php echo $i ?>" name="valor_descripcion[<?php echo $i ?>]">
																	</td>
																	<td align="right">
																		<div class="input-prepend input">
																			<span class="moneda_tabla" align="center"></span>
																			<input class="monto_estimado" size="10" type="text" align="right" value="<?php echo empty($temp['monto_estimado']) ? '' : $temp['monto_estimado'] ?>" id="valor_monto_estimado_<?php echo $i ?>" name="valor_monto_estimado[<?php echo $i ?>]">
																		</div>
																	</td>
																	<td align="center">
																		<img src="<?php echo Conf::ImgDir() ?>/eliminar.gif" style="cursor:pointer" onclick="eliminarFila(this.parentNode.parentNode.rowIndex);" />
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
											<tr>
												<td colspan="2">&nbsp;</td>
											</tr>
											<?php
												$TramiteTipo = new TramiteTipo($Sesion);
											?>
											<script type="text/javascript">
											var addIncludedErrand;
											var removeIncludedErrand;
											var updateErrandRate;
											var listIncludedErrands;

											jQuery('document').ready(function () {
												var contract_id = <?php echo $contrato->fields['id_contrato']; ?>;
												var url_included_errands = root_dir + '/api/index.php/contracts/' + contract_id + '/included_errands';

												addIncludedErrand = function() {
													var errand_type_id = jQuery('#included_errand_type_id').val();

													jQuery.ajax({
														url: url_included_errands,
														type: 'POST',
														data: {
															errand_type_id: errand_type_id
														}
													}).done(function(data) {
														jQuery('#included_errand_type_id').val('');
														jQuery('#included_errand_value').html('');
														listIncludedErrands();
													});
												};

												removeIncludedErrand = function(included_errand_id) {
													jQuery.ajax({
														url: url_included_errands,
														type: 'DELETE',
														data: {
															included_errand_id: included_errand_id
														}
													}).done(function(data) {
														listIncludedErrands();
													});
												};

												updateErrandRate = function(sender) {
													var errand_type_id = jQuery(sender).val();
													var errand_rate_id = jQuery('#id_tramite_tarifa').val();
													var errand_currency_id = jQuery('#id_moneda_tramite').val();
													var url = root_dir + '/api/index.php/errand_rates/' + errand_rate_id + '/values';

													jQuery.ajax({
														url: url,
														data: {
															errand_type_id: errand_type_id,
															errand_currency_id: errand_currency_id
														}
													}).done(function(data) {
														if (errand_type_id == '' || data.length == 0) {
															alert('La tarifa de este tr�mite no est� definida.');
														} else {
															var errand_value = data[0];
															var value = errand_value.simbolo_moneda + ' ' + errand_value.tarifa;
															jQuery('#included_errand_value').html(value);
														}

													});
												};

												listIncludedErrands = function() {
													jQuery.ajax({
														url: url_included_errands
													}).done(function(included_errands) {
														var errand = {};
														var included_errands_html = '';
														var included_errands_total = 0;
														var included_errand_currency = '';

														for (i = 0; i < included_errands.length; i++) {
															errand = included_errands[i];

															included_errands_total += parseFloat(errand.tarifa_tramite);

															errand_template =
																'<tr>'
																	+ '<td>' + errand.glosa_tramite + '</td>'
																	+ '<td align="right">' + errand.simbolo_moneda + ' ' + errand.tarifa_tramite + '</td>'
																	+ '<td align="center">'
																	+	'	<img src="<?php echo Conf::ImgDir() ?>/menos.gif" style="cursor:pointer"'
																	+ ' onclick="removeIncludedErrand(' + errand.id_contrato_tramite + ');" />'
																	+ '</td>'
																+ '</tr>';

															included_errands_html += errand_template;
														}

														included_errand_currency = errand.simbolo_moneda || '';
														included_errands_total = included_errand_currency + ' ' + included_errands_total.toFixed(2);

														jQuery('#included_errands_list').html(included_errands_html);
														jQuery('#included_errands_total').html(included_errands_total);
													});
												};

												// First list
												listIncludedErrands();
											});
											</script>
											<tr>
												<td colspan="2">
													<table width="100%">
														<tr>
															<td width="15%">&nbsp;</td>
															<td>
																<strong><?php echo __('Tr�mites autom�ticos') ?></strong>
																<em>
																	<?php echo __('(estos tr�mites se incluir�n autom�ticamente en la nueva liquidaci�n generada)'); ?>
																</em>
															</td>
														</tr>
													</table>
												</td>
											</tr>
											<tr>
												<td colspan="2" align="center">
													<table width="70%" style="border: 1px solid grey;">
														<thead>
															<tr bgcolor="#6CA522">
																<td width="75%">Tr�mite</td>
																<td width="20%">Valor</td>
																<td width="5%">&nbsp;</td>
															</tr>
														</thead>
														<tbody id="included_errands_list">
														</tbody>
														<tfoot>
															<tr>
																<td>
																	<?php
																	echo Html::SelectArrayDecente(
																		$TramiteTipo->Listar('ORDER BY glosa_tramite'),
																		'included_errand_type_id',
																		'',
																		'onChange="updateErrandRate(this);"',
																		'Seleccione un tr�mite',
																		'320px'
																	);
																	?>
																</td>
																<td align="right" id="included_errand_value">
																</td>
																<td align="center">
																	<img src="<?php echo Conf::ImgDir() ?>/mas.gif" id="tramite_automatico_mas"
																		style="cursor:pointer" onclick="addIncludedErrand();" />
																</td>
															</tr>
															<tr>
																<td align="right"><strong>Total</strong></td>
																<td align="right"><strong id="included_errands_total"></strong></td>
																<td>&nbsp;</td>
															</tr>
														</tfoot>
													</table>
												</td>
											</tr>
										</table>
									<?php
									} else {
										echo $Html->alert(
											'El contrato debe tener asuntos asociados para generar ' . __('Cobros Programados'),
											'',
											array('class' => 'alert-thin')
										);
									}
									?>
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
				&nbsp;<?php echo __('Alertas') ?>
			</legend>
			<table id="datos_alertas"  style='display:<?php echo $show ?>'>
				<tr>
					<td colspan="4">
						<table>
							<tr>
								<td rowspan="3"><?php echo __('Si se superan estos l�mites, el sistema enviar� un email de alerta a:'); ?></td>
								<td>
									<label for="notificar_encargado_principal"> <input type="hidden" name="notificar_encargado_principal" value="0"/><input type="checkbox" name="notificar_encargado_principal" id="notificar_encargado_principal" value="1" <?php echo $contrato->fields['notificar_encargado_principal'] == '1' ? 'checked="checked"' : ''; ?> />
									<?php echo __('Encargado Comercial'); ?></label>
								</td>
							</tr>
							<?php if (Conf::GetConf($Sesion, 'EncargadoSecundario')) { ?>
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
									<small><em><?php echo __('Separados por coma'); ?> <strong>(,)</strong> Ej: correo@dominio.com<strong>,</strong>usuario@estudio.net</em></small>
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
						<span title="<?php echo __('Total de Horas') ?>"><?php echo __('L�mite de horas') ?></span>
					</td>
					<td align=right>
						<input  type="text" class="span1" name=limite_monto value="<?php echo $contrato->fields['limite_monto'] ? $contrato->fields['limite_monto'] : '0' ?>" title="<?php echo __('Valor Total seg�n Tarifa Hora Hombre') ?>" size=5 />
					</td>
					<td align=left>
						<span title="<?php echo __('Valor Total seg�n Tarifa Hora Hombre') ?>"><?php echo __('L�mite de monto') ?></span>
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
						<input type="text" class="span1"  name=alerta_monto value="<?php echo $contrato->fields['alerta_monto'] ? $contrato->fields['alerta_monto'] : '0' ?>" title="<?php echo __('Valor Total seg�n Tarifa Hora Hombre en trabajos no cobrados') ?>" size=5 />
					</td>
					<td align=left>
						<span title="<?php echo __('Valor Total seg�n Tarifa Hora Hombre en trabajos no cobrados') ?>"><?php echo __('monto seg�n horas no cobradas') ?></span>
					</td>
				</tr>
			</table>
		</fieldset>

		<br/>

		<!-- CARTAS -->
		<fieldset style="width: 97%; background-color: #FFFFFF;">
			<legend <?php echo!$div_show ? 'onClick="MuestraOculta(\'datos_carta\')" style="cursor:pointer"' : '' ?> >
				<?php if (!$div_show) { ?>
					<span id="datos_carta_img"><img src="<?php echo Conf::ImgDir(); ?>/mas.gif" border="0" id="datos_carta_img"></span>
				<?php } ?>
				&nbsp;<?php echo __('Carta') ?>
			</legend>
			<table id="datos_carta" style="display:<?php echo $show ?>; width:100%">
				<tr>
					<td align="right" colspan='1' width='25%'>
						<?php echo __('Idioma') ?>
					</td>
					<td align="left" colspan="5">
						<?php echo Html::SelectArrayDecente($Idioma->Listar('ORDER BY glosa_idioma'), 'codigo_idioma', $contrato->fields['codigo_idioma'] ? $contrato->fields['codigo_idioma'] : $idioma_default, 'class="span3"', '', '80px'); ?>
					</td>
				</tr>
				<tr>
					<td align="right" colspan='1' width='25%'>
				<?php echo __('Formato Carta') ?>
					</td>
					<td align="left" colspan="5">
						<?php echo Html::SelectArrayDecente($Carta->Listar('ORDER BY id_carta'), 'id_carta', $contrato->fields['id_carta'], 'class="span3"'); ?>
					</td>
				</tr>
				<tr>
					<td align="right" colspan='1' width='25%'>
				<?php echo __('Formato Detalle Carta') ?>
					</td>
					<td align="left" colspan="5">
						<?php echo Html::SelectArrayDecente($CobroRtf->Listar('ORDER BY cobro_rtf.id_formato'), 'id_formato', $contrato->fields['id_formato'], 'class="span3"'); ?>
					</td>
				</tr>
				<tr>
					<td align="right" colspan='1'><?php echo __('Tama�o del papel') ?>:</td>
					<td align="left" colspan='5'>
						<?php
						if ($contrato->fields['opc_papel'] == '' && Conf::GetConf($Sesion, 'PapelPorDefecto')) {
							$contrato->fields['opc_papel'] = Conf::GetConf($Sesion, 'PapelPorDefecto');
						}
						$tamanos_papel = array(
							'LETTER' => __('Carta'),
							'LEGAL' => __('Oficio'),
							'A4' => __('A4'),
							'A5' => __('A5')
						);
						?>
						<?php echo Html::SelectArrayDecente($tamanos_papel, 'opc_papel', $contrato->fields['opc_papel']); ?>
					</td>
				</tr>
				<?php
				if (empty($contrato->fields['id_contrato'])) {
					$contrato->Edit('opc_restar_retainer', Conf::GetConf($Sesion, 'OpcRestarRetainer') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_asuntos_separados', Conf::GetConf($Sesion, 'OpcVerAsuntosSeparado') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_carta', Conf::GetConf($Sesion, 'OpcVerCarta') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_cobrable', Conf::GetConf($Sesion, 'OpcVerCobrable') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_descuento', Conf::GetConf($Sesion, 'OpcVerDescuento') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_detalle_retainer', Conf::GetConf($Sesion, 'OpcVerDetalleRetainer') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_detalles_por_hora', Conf::GetConf($Sesion, 'OpcVerDetallesPorHora') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_detalles_por_hora_categoria', Conf::GetConf($Sesion, 'OpcVerDetallesPorHoraCategoria') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_detalles_por_hora_importe', Conf::GetConf($Sesion, 'OpcVerDetallesPorHoraImporte') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_detalles_por_hora_iniciales', Conf::GetConf($Sesion, 'OpcVerDetallesPorHoraIniciales') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_detalles_por_hora_tarifa', Conf::GetConf($Sesion, 'OpcVerDetallesPorHoraTarifa') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_gastos', Conf::GetConf($Sesion, 'OpcVerGastos') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_concepto_gastos', Conf::GetConf($Sesion, 'OpcVerConceptoGastos') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_horas_trabajadas', Conf::GetConf($Sesion, 'OpcVerHorasTrabajadas') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_modalidad', Conf::GetConf($Sesion, 'OpcVerModalidad') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_morosidad', Conf::GetConf($Sesion, 'OpcVerMorosidad') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_numpag', Conf::GetConf($Sesion, 'OpcVerNumPag') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_profesional', Conf::GetConf($Sesion, 'OpcVerProfesional') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_profesional_categoria', Conf::GetConf($Sesion, 'OpcVerProfesionalCategoria') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_profesional_importe', Conf::GetConf($Sesion, 'OpcVerProfesionalImporte') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_profesional_iniciales', Conf::GetConf($Sesion, 'OpcVerProfesionalIniciales') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_profesional_tarifa', Conf::GetConf($Sesion, 'OpcVerProfesionalTarifa') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_resumen_cobro', Conf::GetConf($Sesion, 'OpcVerResumenCobro') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_solicitante', Conf::GetConf($Sesion, 'OpcVerSolicitante') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_tipo_cambio', Conf::GetConf($Sesion, 'OpcVerTipoCambio') == 1 ? 1 : 0);
					$contrato->Edit('opc_ver_valor_hh_flat_fee', Conf::GetConf($Sesion, 'OpcVerValorHHFlatFee') == 1 ? 1 : 0);
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
						<?php echo __('Categor�a') ?></label>
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
						<?php echo __('Categor�a') ?></label>
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
				<?php if (Conf::GetConf($Sesion, 'PrmGastos')) { ?>
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
					<td align="left" colspan='5'><label><?php echo __('Mostrar n�meros de p�gina') ?></label></td>
				</tr>
				<tr>
					<td align="right"><input type="hidden" name="opc_ver_columna_cobrable" value="0"/><input type="checkbox" name="opc_ver_columna_cobrable"  id="opc_ver_columna_cobrable" value="1" <?php echo $contrato->fields['opc_ver_columna_cobrable'] == '1' ? 'checked' : '' ?>></td>
					<td align="left"  ><label for="opc_ver_numpag"><?php echo __('Mostrar columna cobrable') ?></label></td>
				</tr> <!-- Andres Oestemer -->
				<?php $solicitante = Conf::GetConf($Sesion, 'OrdenadoPor');
				if ($solicitante == 0) {  // no mostrar ?>
					<input type="hidden" name="opc_ver_solicitante" id="opc_ver_solicitante" value="0" />
				<?php } elseif ($solicitante == 1) { ?>
					<tr>
						<td align="right" colspan='1'><input type="hidden" name="opc_ver_solicitante" value="0"/><input type="checkbox" name="opc_ver_solicitante"  value="1" <?php echo $contrato->fields['opc_ver_solicitante'] == '1' ? 'checked="checked"' : '' ?>></td>
						<td align="left" colspan='5'><label><?php echo __('Mostrar solicitante') ?></label></td>
					</tr>
				<?php } elseif ($solicitante == 2) {  ?>
					<tr>
						<td align="right" colspan='1'><input type="hidden" name="opc_ver_solicitante" value="0"/><input type="checkbox" name="opc_ver_solicitante"  value="1" <?php echo $contrato->fields['opc_ver_solicitante'] == '1' ? 'checked="checked"' : '' ?>></td>
						<td align="left" colspan='5'><label><?php echo __('Mostrar solicitante') ?></label></td>
					</tr>
				<?php } ?>
				<tr>
					<td align="right" colspan='1'><input type="hidden" name="opc_ver_horas_trabajadas" value="0"/><input type="checkbox" name="opc_ver_horas_trabajadas"  value="1" <?php echo $contrato->fields['opc_ver_horas_trabajadas'] == '1' ? 'checked="checked"' : '' ?> ></td>
					<td align="left" colspan='5'><label><?php echo __('Mostrar horas trabajadas') ?></label></td>
				</tr>
				<tr>
					<td align="right" colspan='1'><input type="hidden" name="opc_ver_cobrable" value="0"/><input type="checkbox" name="opc_ver_cobrable"  value="1" <?php echo $contrato->fields['opc_ver_cobrable'] == '1' ? 'checked="checked"' : '' ?> ></td>
					<td align="left" colspan='5'><label><?php echo __('Mostrar trabajos no visibles') ?></label></td>
				</tr>
				<?php if (Conf::GetConf($Sesion, 'ResumenProfesionalVial') ) { ?>
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
		<br/>
		<!-- FIN CARTAS -->

		<!-- DOCUMENTOS -->
		<?php  if ($id_cliente || $id_asunto) {?>
			<fieldset style="width: 97%; background-color: #FFFFFF;">
				<legend <?php echo !$div_show ? 'onClick="MuestraOculta(\'documentos\')" style="cursor:pointer"' : '' ?> >
					<?php if (!$div_show) {?>
						<span id="documentos_img"><img src="<?php echo Conf::ImgDir() ?>/mas.gif" border="0" id="documentos_img"></span>
					<?php } ?>
					&nbsp;<?php echo __('Documentos') ?></legend>
				<table id='documentos' style='display:<?php echo $show ?>'>
					<tr>
						<td colspan="2" align="center">
							<iframe  name="iframe_documentos" id="iframe_documentos" src='documentos.php?id_cliente=<?php echo $cliente->fields['id_cliente'] ?>&id_contrato=<?php echo $contrato->fields['id_contrato']; ?>' frameborder=0 style="width:650px; height:250px;"></iframe>
						</td>
					</tr>
				</table>
			</fieldset>
			<br/>
		<?php } #fin id_cliente OR id_asunto ?>
		<!-- FIN DOCUMENTOS -->

		<!-- ASOCIAR DOC LEGALES -->
		<?php if (Conf::GetConf($Sesion, 'NuevoModuloFactura')) { ?>
			<fieldset style="width: 97%; background-color: #FFFFFF;">
				<legend <?php echo !$div_show ? 'onClick="MuestraOculta(\'div_doc_legales_asociados\')" style="cursor:pointer"' : '' ?>>
					<?php if (!$div_show) { ?>
						<span id="doc_legales_img"><img src="<?php echo Conf::ImgDir(); ?>/mas.gif" border="0" id="doc_legales_img"></span>
					<?php } ?>
					&nbsp;<?php echo __('Documentos legales por defecto') ?>
				</legend>
				<div id="div_doc_legales_asociados" style='display:<?php echo $show ?>'>
					<p><center>Ingrese los documentos legales que desea generar en el proceso de facturaci�n</center></p>
					<?php include dirname(__FILE__) . '/agregar_doc_legales.php'; ?>
				</div>
			</fieldset>
		<?php } ?>
		<br/>
		<!-- ASOCIAR DOC LEGALES -->

		<!-- Modulo de  producci�n-->
		<?php if (Conf::GetConf($Sesion, 'UsarModuloProduccion') && $cliente->Loaded() && $contrato->Loaded()) { ?>
			<script type="text/javascript">
				jQuery('document').ready(function() {
					var $ = jQuery;
					var generator_url = "<?php echo Conf::RootDir() . '/api/index.php/clients/' . $cliente->fields['id_cliente'] . '/contracts/' . $contrato->fields['id_contrato'] . '/generators' ?>";
					var actionButtons = function(id_contract_generator) {
						return '<td align="center"  class="border_plomo" style="white-space:nowrap; width: 52px;">\
							<a data-id="' + id_contract_generator + '" class="fl edit_generator ui-button editar" style="margin: 3px 1px;width: 18px;height: 18px;" title="Modificar Generador" href="javascript:void(0)">&nbsp;</a>\
							<a data-id="' + id_contract_generator + '" class="fl delete_generator ui-button cruz_roja" style="margin: 3px 1px;width: 18px;height: 18px;" title="Eliminar Generador">&nbsp;</a>\
						</td>';
					};

					var showAlert = function(type, message) {
						var alert_html = '<div id="generator_message"><table width="70%" class="' + type + '">\
							<tbody><tr>\
								<td valign="top" align="left" style="font-size: 12px;">\
								' + message + '</td>\
							</tr></tbody>\
						</table></br></div>';
						$('#user_generators_form').before(alert_html);
						setTimeout(function(){
							$('#generator_message').remove()
						}, 3000);
					};

					var loadGeneratorForm = function(state, data) {
						$('#form_generator_status').val(state);
						if (state == 'NEW') {
							$('#id_contract_generator').val('');
							$('#id_user_generator').val('');
							$('#percent_generator').val('');
							$('#add_generator').val('Agregar');
							$('#cancel_generator').val('Cancelar').hide();
						} else if (state == 'EDIT') {
							$('#id_contract_generator').val(data.id_contract_generator);
							$('#id_user_generator').val(data.id_user_generator);
							$('#percent_generator').val(data.percent_generator);
							$('#add_generator').val('Modificar');
							$('#cancel_generator').val('Cancelar').show();
						}
					}

					var loadGenerators = function() {
						$.ajax({ url: generator_url })
							.done(function(data) {
								rows = $('<tbody>');
								header = $("<tr bgcolor='#A3D55C'>")
								header.append('<td align="left" class="border_plomo"><b><?php echo __('Usuario'); ?></b></td>');
								header.append('<td align="left" class="border_plomo"><b><?php echo __('Area Usuario'); ?></b></td>');
								header.append('<td align="right" class="border_plomo"><b><?php echo __('Porcentaje Genera'); ?></b></td>');
								header.append('<td align="right" class="border_plomo"><b><?php echo __('Acciones'); ?></b></td>');
								rows.append(header);

								$.each(data, function(i, generator) {
									generator_row = $('<tr>');
									generator_row.append('<td align="left" class="border_plomo user-data" data-user_id="' + generator.id_usuario + '">'+ generator.nombre + '</td>');
									generator_row.append('<td align="left" class="border_plomo">' + generator.area_usuario + '</td>');
									generator_row.append('<td align="right" class="border_plomo percent-data" data-percent_value="' + generator.porcentaje_genera + '">' + generator.porcentaje_genera + '%</td>');
									generator_row.append(actionButtons(generator.id_contrato_generador));
									rows.append(generator_row);
								});

								$('#user_generators_result').html(rows);
							});
					};

					$(document).on('click', '.edit_generator', function() {
						var percent = $(this).closest('tr').find('.percent-data').data('percent_value');
						var user_id = $(this).closest('tr').find('.user-data').data('user_id');
						var generator_id = $(this).data('id');
						loadGeneratorForm('EDIT', {
							id_contract_generator: generator_id,
							id_user_generator: user_id,
							percent_generator: percent
						});
					});

					$(document).on('click', '.delete_generator', function() {
						if (!confirm('�Seguro desea elminar este usuario?')) {
							return;
						}
						var generator_id = $(this).data('id');
						$.ajax({
							url: generator_url + '/' + generator_id,
							type: 'DELETE'
						}).done(function(data) {
							loadGenerators();
						});
					});

					$(document).on('click', '#cancel_generator', function() {
						loadGeneratorForm('NEW', {});
					});


					$('#add_generator').click(function() {
						var percent = $('#percent_generator').val();
						var user = $('#id_user_generator').val();
						var id_contract_generator = $('#id_contract_generator').val();
						var form_status = $('#form_generator_status').val();
						if (percent && user && percent.length > 0) {
							if (parseInt(percent) < 1 || parseInt(percent) > 100) {
								showAlert('alerta', 'El porcentaje debe estar entre 1 y 100');
								return;
							}

							if (form_status == 'EDIT') {
								$.ajax({
									url: generator_url + '/' + id_contract_generator,
									type: 'POST',
									data: {percent_generator: percent}
								}).done(function(data) {
									loadGeneratorForm('NEW', {});
									loadGenerators();
								});
							} else if (form_status == 'NEW') {
								if ($('td[data-user_id="' + user + '"]').length > 0) {
									showAlert('alerta', 'El profesional ya existe, favor agregue otro o modif�quelo desde el listado');
									return;
								}
								$.ajax({
									url: generator_url,
									type: 'PUT',
									data: {
										percent_generator: percent,
										user_id:user
									}
								}).done(function(data) {
									showAlert('info', 'Profesional agregado con �xito');
									loadGeneratorForm('NEW', {});
									loadGenerators();
								});
							}
						} else {
							showAlert('alerta', 'Ingrese todos los datos para agregar el usuario');
						}
					});
					loadGeneratorForm('NEW', {});
					loadGenerators();
				});
			</script>

			<fieldset class="border_plomo tb_base">
				<legend><?php echo __('Profesionales') . ' ' . __('Generadores') ?></legend>
				<table width="80%" border="0" style="border: 1px solid #BDBDBD;" cellpadding="3" cellspacing="3" id="user_generators_form">
					<tbody>
						<tr>
							<td>
								<?php echo __('Profesional') ?>
							</td>
							<td>
								<?php echo Html::SelectArrayDecente($Sesion->usuario->ListarActivos('', true), 'id_user_generator', '', '', 'Seleccione', '200px'); ?>
							</td>
							<td>
								<?php echo __('Porcentaje Genera'); ?>:
							</td>
							<td>
								<input type="text" size="6" class="text_box" name='percent_generator' id="percent_generator" value="" style="border: 1px solid rgb(204, 204, 204);">
							</td>
							<td>
								<input type="hidden" id="form_generator_status" value="" />
								<input type="hidden" id="id_contract_generator" value="" />
								<?php
								echo $Form->button(__('Agregar') . ' ' . __('Generador'), array('id' => 'add_generator'));
								echo $Form->button(__('Cancelar'), array('id' => 'cancel_generator'));
								?>
							</td>
						<tr>
					</tbody>
				</table>

				<table width="80%" border="0" style="border: 1px solid #BDBDBD;" cellpadding="3" cellspacing="3" id="user_generators_result">

				</table>
			</fieldset>
			<br>
		<? } ?>

		<!-- Fin modulo de generadores -->

		<!-- GUARDAR -->
		<?php if ($popup && !$motivo) { ?>
			<fieldset style="width: 97%; background-color: #FFFFFF;">
				<legend><?php echo __('Guardar datos') ?></legend>
				<table>
					<tr>
						<td colspan="6" align="center">
							<?php
							if (Conf::GetConf($Sesion, 'RevisarTarifas')) {
								$onclick = "return RevisarTarifas( 'id_tarifa', 'id_moneda', this.form, false)";
							} else {
								$onclick = "ValidarContrato(this.form)";
							}
							echo $Form->button(__('Guardar'), array('onclick' => $onclick));
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
<?php }
echo $Form->script();
?>
<script type="text/javascript">
	jQuery(document).ready(function() {
		ActualizarFormaCobro();
		jQuery(".formacobro").click(function() {
			var laID=jQuery(this).attr('id');
			ActualizarFormaCobro(laID);
		});
	});


	function YoucangonowMichael() {
		<?php
		if ($contrato->fields['id_cuenta']) {
			echo "SetBanco('id_cuenta','id_banco');";
		}
		?>
	}

	if (jQuery('#periodo_fecha_inicio').length != 0) {
		Calendar.setup({
			inputField	: "periodo_fecha_inicio",				// ID of the input field
			ifFormat		: "%d-%m-%Y",			// the date format
			button			: "img_periodo_fecha_inicio"		// ID of the button
		});
	}

	if (jQuery('#fecha_inicio_cap').length != 0) {
		Calendar.setup({
			inputField	: "fecha_inicio_cap",				// ID of the input field
			ifFormat		: "%d-%m-%Y",			// the date format
			button			: "img_fecha_inicio_cap"		// ID of the button
		});
	}

	$$('[id^="hito_fecha_"]').each(function(elem){
		Calendar.setup({
			inputField	: elem.id,				// ID of the input field
			ifFormat		: "%d-%m-%Y",			// the date format
			button			: elem.id.replace('hito_fecha_', 'img_fecha_hito_')
		});
	});
	$$('tr.esconder').each(function(item) {
		item.hide();
	});
	actualizarMoneda();

	<?php
	if (Conf::GetConf($Sesion, "CopiarEncargadoAlAsunto") && !$desde_agrega_cliente) {
		if (Conf::GetConf($Sesion, 'EncargadoSecundario')) {
			echo "if(jQuery('#id_usuario_secundario').length>0) jQuery('#id_usuario_secundario').attr('disabled','disabled');";
		}
		echo "if (jQuery('#id_usuario_encargado').length>0) jQuery('#id_usuario_encargado').attr('disabled','disabled');";
	}
	?>

</script>
<?php
echo(InputId::Javascript($Sesion));

if ($addheaderandbottom || ($popup && !$motivo)) {
	$Pagina->PrintBottom($popup);
}
