<?php
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Pagina.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/../fw/classes/Html.php';
require_once Conf::ServerDir() . '/../fw/classes/Buscador.php';
require_once Conf::ServerDir() . '/classes/UtilesApp.php';
require_once Conf::ServerDir() . '/classes/InputId.php';
require_once Conf::ServerDir() . '/classes/Trabajo.php';
require_once Conf::ServerDir() . '/classes/Funciones.php';
require_once Conf::ServerDir() . '/../app/classes/Debug.php';
require_once Conf::ServerDir() . '/classes/Gasto.php';
require_once Conf::ServerDir() . '/classes/Autocompletador.php';
// require_once Conf::ServerDir().'/classes/GastoGeneral.php';

$sesion = new Sesion(array('OFI'));
$pagina = new Pagina($sesion);
$id_usuario = isset($id_usuario) ? $id_usuario : $sesion->usuario->fields['id_usuario'];

$gasto = new Gasto($sesion);
// $gastoGeneral = new GastoGeneral($sesion);

$ingreso = new Gasto($sesion);


if ($id_gasto != "") {
	$gasto->Load($id_gasto);

	if ($gasto->fields['id_movimiento_pago'] != '') {
		$ingreso->Load($gasto->fields['id_movimiento_pago']);
	}
	if ($codigo_asunto != $gasto->fields['codigo_asunto']) { //revisar para codigo secundario
		$cambio_asunto = true;
	}
}

if ( ( $gasto->Loaded() && $gasto->fields['egreso'] > 0 ) || $prov == 'false') {
	$txt_pagina = $id_gasto ? __('Edición de Gastos') : __('Ingreso de Gastos');
	$txt_tipo = __('Gasto');
	$prov = 'false';
} else {
	$txt_pagina = $id_gasto ? __('Edición de Provisión') : __('Ingreso de Provisión');
	$txt_tipo = __('Provisión');
	$prov = 'true';
}

if ($opcion == "guardar") {
	if (!$codigo_cliente && $codigo_cliente_secundario) {
		$query = "SELECT codigo_cliente FROM cliente WHERE codigo_cliente_secundario = '$codigo_cliente_secundario'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		list($codigo_cliente) = mysql_fetch_array($resp);
	}
	if (!$codigo_asunto && $codigo_asunto_secundario) {
		$query = "SELECT codigo_asunto FROM asunto WHERE codigo_asunto_secundario = '$codigo_asunto_secundario'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		list($codigo_asunto) = mysql_fetch_array($resp);
	}

	// Buscar cliente según asunto seleccionado para revisar consistencia ...
	$query = "SELECT codigo_cliente FROM asunto WHERE codigo_asunto = '$codigo_asunto'";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	list($codigo_cliente_segun_asunto) = mysql_fetch_array($resp);

	if ($codigo_cliente_segun_asunto != $codigo_cliente) {
		$pagina->AddError("El asunto seleccionado no corresponde al cliente seleccionado.");
	}

	$errores = $pagina->GetErrors();

	if (empty($errores)) {
		if ($_POST['cobrable'] == 1) {
			$gasto->Edit("cobrable", 1);
		} else {
			if (UtilesApp::GetConf($sesion, 'UsarGastosCobrable')) {
				$gasto->Edit("cobrable", "0");
			} else {
				$gasto->Edit("cobrable", "1");
			}
		}

		/*
		 *  Si el gasto se considera con IVA,
		 *  se calcula en base al porcentaje impuesto gasto
		 *  del cobro
		 */
		if (!UtilesApp::GetConf($sesion, 'UsarGastosConSinImpuesto')) {
			$con_impuesto = 1;
		}
		$gasto->Edit("con_impuesto", $con_impuesto == 1 ? "SI" : "NO");


		$monto = str_replace(',', '.', $monto);
		if ($prov == 'true') {
			$gasto->Edit("ingreso", $monto);
			$gasto->Edit("monto_cobrable", $monto);
		} else if ($prov == 'false') {
			if (UtilesApp::GetConf($sesion, 'UsaMontoCobrable')) {
				if ($monto <= 0) {
					$gasto->Edit("egreso", $monto_cobrable);
				} else {
					$gasto->Edit("egreso", $monto);
				}

				if ($monto_cobrable >= 0) {
					$monto_cobrable = str_replace(',', '.', $monto_cobrable);
					$gasto->Edit("monto_cobrable", $monto_cobrable);
				} else {
					$gasto->Edit("monto_cobrable", $monto);
				}
			} else {

				$gasto->Edit("egreso", $monto);
				$gasto->Edit("monto_cobrable", $monto);
			}
		}

		$gasto->Edit("fecha", Utiles::fecha2sql($fecha));
		$gasto->Edit("id_usuario", $id_usuario);
		$gasto->Edit("descripcion", $descripcion);
		$gasto->Edit("id_glosa_gasto", (!empty($glosa_gasto) && $glosa_gasto != -1) ? $glosa_gasto : "NULL");
		$gasto->Edit("id_moneda", $id_moneda);
		$gasto->Edit("codigo_cliente", $codigo_cliente ? $codigo_cliente : "NULL");
		$gasto->Edit("codigo_asunto", $codigo_asunto ? $codigo_asunto : "NULL");
		$gasto->Edit("codigo_gasto",$codigo_gasto ? $codigo_gasto : "NULL");
		$gasto->Edit("id_usuario_orden", (!empty($id_usuario_orden) && $id_usuario_orden != -1) ? $id_usuario_orden : "NULL");
		$gasto->Edit("id_cta_corriente_tipo", $id_cta_corriente_tipo ? $id_cta_corriente_tipo : "NULL");
		$gasto->Edit("numero_documento", $numero_documento ? $numero_documento : "NULL");

		$gasto->Edit("id_tipo_documento_asociado", $id_tipo_documento_asociado ? $id_tipo_documento_asociado : -1);
		if (UtilesApp::GetConf($sesion, 'FacturaAsociadaCodificada')) {
			$numero_factura_asociada = $pre_numero_factura_asociada . '-' . $post_numero_factura_asociada;
		}
		$gasto->Edit("codigo_factura_gasto", $numero_factura_asociada ? $numero_factura_asociada : "NULL");
		$gasto->Edit("fecha_factura", $fecha_factura_asociada ? Utiles::fecha2sql($fecha_factura_asociada) : "");
		$gasto->Edit("numero_ot", $numero_ot ? $numero_ot : "NULL");



		if ($pagado && $prov == 'false') {
			$ingreso->Edit('fecha', $fecha_pago ? Utiles::fecha2sql($fecha_pago) : "NULL");
			$ingreso->Edit("id_usuario", $id_usuario);
			$ingreso->Edit("descripcion", $descripcion_ingreso);
			$ingreso->Edit("id_moneda", $gasto->fields['id_moneda'] ? $gasto->fields['id_moneda'] : $id_moneda);
			$ingreso->Edit("codigo_cliente", $codigo_cliente ? $codigo_cliente : "NULL");
			$ingreso->Edit("codigo_asunto", $codigo_asunto ? $codigo_asunto : "NULL");
			$ingreso->Edit("id_usuario_orden", (!empty($id_usuario_orden) && $id_usuario_orden != -1 ) ? $id_usuario_orden : "NULL");
			if (UtilesApp::GetConf($sesion, 'UsaMontoCobrable') && $monto_cobrable > 0) {
				$ingreso->Edit('ingreso', $monto_pago ? $monto_pago : $monto_cobrable );
			} else {
				$ingreso->Edit('ingreso', $monto_pago ? $monto_pago : '0');
			}
			if (UtilesApp::GetConf($sesion, 'UsaMontoCobrable')) {
				$ingreso->Edit('monto_cobrable', $monto_cobrable ? $monto_cobrable : $ingreso);
			} else {
				$ingreso->Edit('monto_cobrable', $ingreso);
			}
			$ingreso->Edit("documento_pago", $documento_pago ? $documento_pago : "NULL");
			if ($ingreso->Write()) {
				$gasto->Edit('id_movimiento_pago', $ingreso->fields['id_movimiento'] ? $ingreso->fields['id_movimiento'] : 'NULL');
			}
		} else {
			if ($elimina_ingreso != '') {
				if (!$ingreso->EliminaIngreso($id_gasto)) {
					$ingreso_eliminado = '<br>' . __('El ingreso no pudo ser eliminado ya que existen otros gastos asociados.');
				}
			}

			$gasto->Edit('id_movimiento_pago', NULL);
		}
		/*
		  Ha cambiado el asunto del gasto se setea id_cobro NULL
		 */
		if ($cambio_asunto) {
			$gasto->Edit('id_cobro', 'NULL');
		}

		$gasto->Edit('id_proveedor', $id_proveedor ? $id_proveedor : NULL);

		if ($gasto->Write()) {
			$pagina->AddInfo($txt_tipo . ' ' . __('Guardado con éxito.') . ' ' . $ingreso_eliminado);
			?>
			<script language='javascript'>
				if(  parent.window.Refrescarse ) {
					parent.window.Refrescarse(); 
				} else if( window.opener.Refrescar ) {
					window.opener.Refrescar(); 
				}
			</script>
			<?php
		}
	}
}
	global $gasto;
	$contrato=new Contrato($sesion);
	
	if (UtilesApp::GetConf($sesion, 'CodigoSecundario')) {
		$contrato->LoadByCodigoAsuntoSecundario($codigo_asunto_secundario);
		echo 'var CodigoSecundario=1;';
	} else {
		$contrato->LoadByCodigoAsunto($codigo_asunto);
		echo 'var CodigoSecundario=0;';
	} 
	$gasto->extra_fields['id_contrato']=$contrato->fields['id_contrato'];
	
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

	function ShowGastos(valor)
	{
		if(valor)
			$('tabla_gastos').style.display = 'inline';
		else
			$('tabla_gastos').style.display = 'none';
	}


	function CambiaMonto( form )
	{
		var monto = form.monto.value;
		form.monto.value = monto.replace(',','.');
<?php
if (UtilesApp::GetConf($sesion, 'ComisionGastos')) {
	?>
				form.monto_cobrable.value = (form.monto.value * (1+form.porcentajeComision.value/100)).toFixed(2);
	<?php
} else {
	?>
				if( form.monto_cobrable )
					form.monto_cobrable.value = form.monto.value;
	<?php
}
?>
	}

	function Validar(form)
	{
		monto = parseFloat(form.monto.value);
		if( form.monto_cobrable ) {
			monto_cobrable = parseFloat(form.monto_cobrable.value);
		}

		if( form.monto_cobrable && ( monto <= 0 || isNaN(monto) ) ) {
			monto=monto_cobrable;
		}

<?php if (UtilesApp::GetConf($sesion, 'CodigoSecundario')) { ?>
			if($('codigo_cliente_secundario').value == '')
			{
				alert('<?php echo __('Debe seleccionar un cliente') ?>');
				form.codigo_cliente_secundario.focus();
				return false;
			}
			if($('campo_codigo_asunto_secundario').value == '')
			{
				alert('<?php echo __('Ud. debe seleccionar un') . ' ' . __('asunto') ?>');
				form.codigo_asunto_secundario.focus();
				return false;
			}
<?php } else { ?>
			if($('codigo_cliente').value == '')
			{
				alert('<?php echo __('Debe seleccionar un cliente') ?>');
				form.codigo_cliente.focus();
				return false;
			}
			if($('campo_codigo_asunto').value == '')
			{
				alert('<?php echo __('Ud. debe seleccionar un') . ' ' . __('asunto') ?>');
				form.codigo_asunto.focus();
				return false;
			}
<?php } ?>

		  if(typeof  RevisarConsistenciaClienteAsunto == 'function')       RevisarConsistenciaClienteAsunto( form );
	
<?php if (UtilesApp::GetConf($sesion, 'UsaMontoCobrable')) { ?>
			if((monto <= 0 || isNaN(monto)) && (monto_cobrable <= 0 || isNaN(monto_cobrable)))
			{
				alert('<?php echo __('Debe ingresar un monto para el gasto') ?>');
				form.monto.focus();
				return false;
			}
<?php } else { ?>
			if((monto <= 0 || isNaN(monto)))
			{
				alert('<?php echo __('Debe ingresar un monto para el gasto') ?>');
				form.monto.focus();
				return false;
			}
<?php } ?>
		if(form.descripcion.value == "")
		{
			alert('<?php echo __('Debe ingresar una descripción') ?>');
			form.descripcion.focus();
			return false;
		}
<?php
if (UtilesApp::GetConf($sesion, 'TodoMayuscula')) {
	?>
				if(form.descripcion.value != "")
				{
					form.descripcion.value=form.descripcion.value.toUpperCase();
				}
	<?php
}
?>

		var radio_choice = false;
		for( i=0; i < form.id_moneda.options.length; i++ )
		{
			if( form.id_moneda.options[i].selected == true && form.id_moneda.value != '')
			{
				radio_choice = true;
			}
		}
		if (!radio_choice)
		{
			alert('<?php echo __('Debe seleccionar una Moneda') ?>');
			return false;
		}
        
        //if(jQuery('#id_usuario_orden').val()==-1) jQuery('#id_usuario_orden').val(<?php echo $id_usuario; ?>);
        if(jQuery('#id_usuario').val()==-1) jQuery('#id_usuario').val(<?php echo $id_usuario; ?>);
		form.submit();
	}

	function CheckEliminaIngreso(chk)
	{
		var form = $('form_gastos');
		if(chk) {
			form.elimina_ingreso.value = 1;
		} else {
			form.elimina_ingreso.value = '';
		}

		return true;
	}

	function ActualizarDescripcion()
	{
		var w = $('glosa_gasto').selectedIndex;
		var selected_text = $('glosa_gasto').options[w].text;
		$('descripcion').value = selected_text;
	}

	function CargaIdioma( codigo )
	{
		if(jQuery('#txt_span').length==0) {
			return true;
		}
        var txt_span = document.getElementById('txt_span');
        
		if(!codigo)
		{
			txt_span.innerHTML = '';
			return false;
		}
		else
		{
			var accion = 'idioma';
			var http = getXMLHTTP();
			http.open('get','ajax.php?accion='+accion+'&codigo_asunto='+codigo, true);
			http.onreadystatechange = function()
			{
				if(http.readyState == 4)
				{
					var response = http.responseText;
					var idio = response.split("|");
<?php
if (UtilesApp::GetConf($sesion, 'IdiomaGrande')) {
	?>
							txt_span.innerHTML = idio[1];
	<?php
} else {
	?>
							txt_span.innerHTML = 'Idioma: '+idio[1];
	<?php
}
?>
				}
			};
			http.send(null);
		}
	}

	function AgregarNuevo(tipo, prov)
	{
<?php
if (UtilesApp::GetConf($sesion, 'CodigoSecundario')) {
	?>
				var codigo_cliente_secundario = $('codigo_cliente_secundario').value;
				var codigo_asunto_secundario = $('codigo_asunto_secundario').value;
				if(tipo == 'gasto')
				{
					var urlo = "agregar_gasto.php?popup=1&prov="+prov+"&codigo_cliente_secundario="+codigo_cliente_secundario+"&codigo_asunto_secundario="+codigo_asunto_secundario;
					window.location=urlo;
				}
	<?php
} else {
	?>
				var codigo_cliente = $('codigo_cliente').value;
				var codigo_asunto = $('codigo_asunto').value;
				if(tipo == 'gasto')
				{
					var urlo = "agregar_gasto.php?popup=1&prov="+prov+"&codigo_cliente="+codigo_cliente+"&codigo_asunto="+codigo_asunto;
					window.location=urlo;
				}
<?php } ?>
	}

	function AgregarProveedor()
	{
		var urlo = 'agregar_proveedor.php?popup=1';
		nuovaFinestra('Agregar_Proveedor',430,370,urlo);
	}
</script>
<?php echo(Autocompletador::CSS()); ?>
<form method=post action="<?php echo $SERVER[PHP_SELF] ?>"  id="form_gastos" autocomplete='off'>
	<input type=hidden name=opcion value="guardar" />
	<input type=hidden name=id_gasto value="<?php echo $gasto->fields['id_movimiento'] ?>" />
	<input type=hidden name=id_gasto_general value="<?php echo $gasto->fields['id_gasto_general'] ?>" />
	<input type=hidden name='prov' value='<?php echo $prov ?>'>
	<input type=hidden name=id_movimiento_pago id=id_movimiento_pago value=<?php echo $gasto->fields['id_movimiento_pago'] ?>>
	<input type=hidden name=elimina_ingreso id=elimina_ingreso value=''>
	<!-- Calendario DIV -->
	<div id="calendar-container" style="width:221px; position:absolute; display:none;">
		<div class="floating" id="calendar"></div>
	</div>
	<!-- Fin calendario DIV -->
	<br>
	<table width='90%'>
		<tr>
			<td align=left><b><?php echo $txt_pagina ?></b></td>
		</tr>
	</table>
	<br>

	<?php
	if (UtilesApp::GetConf($sesion, 'CodigoSecundario')) {
		if (!$codigo_cliente_secundario) {
			if ($gasto->fields['codigo_cliente']) {
				$codigo_cliente = $gasto->fields['codigo_cliente'];
			}

			$query = "SELECT codigo_cliente_secundario FROM cliente WHERE codigo_cliente='$codigo_cliente'";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
			list($codigo_cliente_secundario) = mysql_fetch_array($resp);
		}

		if (!$codigo_asunto_secundario) {
			if ($gasto->fields['codigo_asunto']) {
				$codigo_asunto = $gasto->fields['codigo_asunto'];
			}

			$query = "SELECT codigo_asunto_secundario FROM asunto WHERE codigo_asunto='$codigo_asunto'";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
			list($codigo_asunto_secundario) = mysql_fetch_array($resp);
		}
	} else {
		if (!$codigo_cliente) {
			$codigo_cliente = $gasto->fields['codigo_cliente'];
		}

		if (!$codigo_asunto) {
			$codigo_asunto = $gasto->fields['codigo_asunto'];
		}
	}
	?>

 		<div id="celda_agregar_gasto fr" style="width:96%;" >
			 <span class="fl">
				<b><?php echo __('Información de') ?> <?php echo $prov == 'true' ? __('provisión') : __('gasto') ?></b>
			</span>
			
				 <a href='javascript:void(0)' class="fr btn botonizame" icon="agregar"   style="margin:2px;" onclick="AgregarNuevo('gasto',<?php echo $prov ?>);" title="Agregar Gasto"><?php echo $prov == 'true' ? __('Nueva provisión') : __('Nuevo gasto') ?></a>
				<?php ($Slim=Slim::getInstance('default',true)) ? $Slim->applyHook('hook_agregar_gasto_inicio') : false; ?>
		</div>
	<table class="border_plomo" style="background-color: #FFFFFF;" width='90%'>
		<tr>
			<td align=right>
				<?php echo __('Fecha') ?>
			</td>
			<td align=left>
				<input type="text" name="fecha" class="fechadiff" value="<?php echo $gasto->fields[fecha] ? Utiles::sql2date($gasto->fields[fecha]) : date('d-m-Y') ?>" id="fecha" size="11" maxlength="10" />
			</td>
		</tr>
		<tr>
			<td align=right>
				<?php echo __('Cliente') ?>
			</td>
			<td align=left>
			<?php UtilesApp::CampoCliente($sesion,$codigo_cliente,$codigo_cliente_secundario,$codigo_asunto,$codigo_asunto_secundario); ?>

				<span style="color:#FF0000; font-size:10px">*</span>
			</td>
		</tr>
		<tr>
			<td align=right>
				<?php echo __('Asunto') ?>
			</td>
			<td align=left>
			 <?php   UtilesApp::CampoAsunto($sesion,$codigo_cliente,$codigo_cliente_secundario,$codigo_asunto,$codigo_asunto_secundario); ?>

				<span style="color:#FF0000; font-size:10px">*</span>
			</td>
		</tr>
		<?php if(UtilesApp::GetConf($sesion, 'ExportacionLedes')){ ?>
		<tr>
			<td align=right>
				<?php echo __('Código UTBMS'); ?>
			</td>
			<td align=left width="440" nowrap>
				<?php echo InputId::ImprimirCodigo($sesion, 'UTBMS_EXPENSE', "codigo_gasto", $gasto->fields['codigo_gasto']); ?>
			</td>
		</tr>
		<?php } ?>
		<?php if (UtilesApp::GetConf($sesion, 'TipoGasto') && $prov == 'false') { ?>
			<tr>
				<td align=right>
					<?php echo __('Tipo de Gasto') ?>
				</td>
				<td align=left>
					<?php echo Html::SelectQuery($sesion, "SELECT id_cta_corriente_tipo, glosa FROM prm_cta_corriente_tipo", "id_cta_corriente_tipo", $gasto->fields['id_cta_corriente_tipo'] ? $gasto->fields['id_cta_corriente_tipo'] : '1', '', '', "160"); ?>
				</td>
			</tr>
		<?php } ?>
		<tr>
			<td align=right>
				<?php echo __('Proveedor') ?>
			</td>
			<td align=left>
				<?php echo Html::SelectQuery($sesion, "SELECT id_proveedor, glosa FROM prm_proveedor ORDER BY glosa", "id_proveedor", $gasto->fields['id_proveedor'] ? $gasto->fields['id_proveedor'] : '0', '', 'Cualquiera', "160"); ?>
				<a href='javascript:void(0)' onclick="AgregarProveedor();" title="Agregar Proveedor"><img src="<?php echo Conf::ImgDir() ?>/agregar.gif" border=0 ></a>
			</td>
		</tr>

		<tr>
			<td align=right>
				<?php echo __('Monto') ?>
			</td>
			<td align=left>
				<input name="monto" id="monto" size=10 onchange="CambiaMonto(this.form);" value="<?php echo $gasto->fields['egreso'] ? $gasto->fields['egreso'] : $gasto->fields['ingreso'] ?>" />
				<span style="color:#FF0000; font-size:10px">*</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<?php echo __('Moneda') ?>&nbsp;
				<?php echo Html::SelectQuery($sesion, "SELECT id_moneda,glosa_moneda FROM prm_moneda ORDER BY id_moneda", "id_moneda", $gasto->fields['id_moneda'] ? $gasto->fields['id_moneda'] : '', '', '', "80"); ?>
				<span style="color:#FF0000; font-size:10px">*</span>
			</td>
		</tr>

		<?php if (UtilesApp::GetConf($sesion, 'ComisionGastos') && $prov == 'false') { ?>
			<tr>
				<td align="right">
					<?php echo __('Porcentaje comisión') ?>
				</td>
				<td align="left">
					<input name="porcentajeComision" size="10" onchange="CambiaMonto(this.form);" value="<?php echo method_exists('Conf', 'GetConf') ? Conf::GetConf($sesion, 'ComisionGastos') : Conf::ComisionGastos() ?>" /> %
				</td>
			</tr>
		<?php } ?>
		<?php if ($prov == 'false' && UtilesApp::GetConf($sesion, 'UsaMontoCobrable')) { ?>
			<tr>
				<td align=right>
					<?php echo __('Monto cobrable') ?>&nbsp;
				</td>
				<td align=left>
					<input name="monto_cobrable" id="monto_cobrable" size=10 value="<?php echo $gasto->fields['monto_cobrable'] ?>" />
				</td>
			</tr>
		<?php } ?>
		<?php
		if (UtilesApp::GetConf($sesion, 'PrmGastos')) {
			$_onchange = '';
			$_titulo = 'Vacio';
			if (UtilesApp::GetConf($sesion, 'PrmGastosActualizarDescripcion')) {
				$_onchange = 'onchange="ActualizarDescripcion()"';
				$_titulo = '';
			}
			?>
			<tr>
				<td align=right>
					<?php echo __('Descripción Parametrizada') ?>
				</td>
				<td align=left>
					<?php echo Html::SelectQuery($sesion, "SELECT id_glosa_gasto,glosa_gasto FROM prm_glosa_gasto ORDER BY id_glosa_gasto", "glosa_gasto", $gasto->fields['id_glosa_gasto'] ? $gasto->fields['id_glosa_gasto'] : '', $_onchange, $_titulo, "300"); ?>
				</td>
			</tr>
		<?php } ?>
		<?php if (UtilesApp::GetConf($sesion, 'NumeroGasto')) { ?>
			<tr>
				<td align=right>
					<?php echo __('N° Documento') ?>
				</td>
				<td align=left>
					<input name=numero_documento size=10 value="<?php echo ($gasto->fields['numero_documento'] && $gasto->fields['numero_documento'] != 'NULL') ? $gasto->fields['numero_documento'] : '' ?>" />
				</td>
			</tr>
		<?php } ?>
		<?php if (UtilesApp::GetConf($sesion, 'FacturaAsociada')) { ?>
			<tr>
				<td align=right>
					<?php echo __('Documento Asociado') ?>
				</td>
				<td align=left>
					<?php echo Html::SelectQuery($sesion, "SELECT id_tipo_documento_asociado, glosa FROM prm_tipo_documento_asociado ORDER BY id_tipo_documento_asociado", "id_tipo_documento_asociado", $gasto->fields['id_tipo_documento_asociado'] ? $gasto->fields['id_tipo_documento_asociado'] : '', '', 'Vacio', "140"); ?>
					<?php
					if (UtilesApp::GetConf($sesion, 'FacturaAsociadaCodificada')) {
						$numero_factura = explode('-', $gasto->fields['codigo_factura_gasto']);
						$tamano_numero_factura = sizeof($numero_factura);
						if ($tamano_numero_factura > 1) {
							$pre_numero_factura_asociada = $numero_factura[0];
							$post_numero_factura_asociada = $numero_factura[1];
							for ($i = 2; $i < $tamano_numero_factura; $i++) {
								$post_numero_factura_asociada .= '-' . $numero_factura[$i];
							}
						}
						?>
						<input name="pre_numero_factura_asociada" size=3 maxlength=3 value="<?php echo $pre_numero_factura_asociada ? $pre_numero_factura_asociada : '' ?>" />
						<span>-</span>
						<input name="post_numero_factura_asociada" size=10 maxlength=10 value="<?php echo $post_numero_factura_asociada ? $post_numero_factura_asociada : '' ?>" />

					<?php } else { ?>
						<input name="numero_factura_asociada" size=10 value="<?php echo ($gasto->fields['codigo_factura_gasto'] && $gasto->fields['codigo_factura_gasto'] != 'NULL') ? $gasto->fields['codigo_factura_gasto'] : '' ?>" />
					<?php } ?>

					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				</td>
			</tr>
			<tr>
				<td align="right">
					<?php echo __('Fecha Documento') ?>
				</td>
				<td>
					<input type="text" name="fecha_factura_asociada" class="fechadiff" value="<?php echo ($gasto->fields['fecha_factura'] && $gasto->fields['fecha_factura'] != 'NULL') ? Utiles::sql2date($gasto->fields['fecha_factura']) : '' ?>" id="fecha_factura_asociada" size="11" maxlength="10" />
				</td>
			</tr>
		<?php } ?>

		<?php if (UtilesApp::GetConf($sesion, 'NumeroOT') && $prov == 'false') { ?>
			<tr>
				<td align=right>
					<?php echo __('N° OT') ?>
				</td>
				<td align=left>
					<input name=numero_ot size=10 value="<?php echo ($gasto->fields['numero_ot'] && $gasto->fields['numero_ot'] != 'NULL') ? $gasto->fields['numero_ot'] : '' ?>" />
				</td>
			</tr>
		<?php } ?>
		<tr id='descripcion_gastos'>
			<td align=right>
				<?php if (UtilesApp::GetConf($sesion, 'IdiomaGrande')) { ?>
					<?php echo __('Descripción') ?><br/><span id=txt_span style="background-color: #C6FAAD; font-size:18px"></span>
				<?php } else { ?>
					<?php echo __('Descripción') ?>
				<?php } ?>
			</td>
			<td align=left>
				<textarea id='descripcion' name=descripcion cols="45" rows="3"><?php echo $checked_general == '' ? $gasto->fields['descripcion'] : $gastoGeneral->fields['descripcion']; ?></textarea>

				<script type="text/javascript">
					var googie2 = new GoogieSpell("../../fw/js/googiespell/", "sendReq.php?lang=");
					googie2.setLanguages({'es': 'Español', 'en': 'English'});
					googie2.dontUseCloseButtons();
					googie2.setSpellContainer("spell_container");
					googie2.decorateTextarea("descripcion");
				</script>
			</td>
		</tr>
		<?php
// Por definicion las provisiones no deben tener Impuestos
		if ($prov == 'false') {
			if (UtilesApp::GetConf($sesion, 'UsarImpuestoPorGastos') && UtilesApp::GetConf($sesion, 'UsarGastosConSinImpuesto')) {
				?>
				<tr>
					<td align=right>
						<?php echo __('Con Impuesto') ?>
						<?php
						if ($gasto->fields['con_impuesto'] == 'SI') {
							$con_impuesto_check = 'checked';
						} else {
							$con_impuesto_check = '';
						}
						?>
					</td>
					<td align=left>
						<input type="checkbox" id="con_impuesto" name="con_impuesto" value="1" <?php echo $con_impuesto_check; ?>>
					</td>
				</tr>
				<?php
			}
		}
		?>
		<?php if (UtilesApp::GetConf($sesion, 'UsarGastosCobrable')) { ?>
			<tr>
				<td align=right>
					<?php echo __('Cobrable') ?>
					<?php
					$cobrable_checked = 'checked';

					if ($id_gasto > 0) {
						if ($gasto->fields['cobrable'] == 1) {
							$cobrable_checked = 'checked';
						} else {
							$cobrable_checked = '';
						}
					}
					?>
				</td>
				<td align=left>
					<input type="checkbox" id="cobrable" name="cobrable" value="1" <?php echo $cobrable_checked; ?>>
				</td>
			</tr>
		<?php } ?>
		<tr>
			<td align=right colspan="2">&nbsp;</td>
		</tr>
		<?php
		$usuario_defecto = empty($gasto->fields['id_movimiento']) ? $sesion->usuario->fields['id_usuario'] : '';
		if ($prov == 'false') {
			?>
			<tr>
				<td align=right>
					<?php echo __('Ordenado por') ?>
				</td>
				<td align=left>
					<?php echo Html::SelectQuery($sesion, "SELECT id_usuario, CONCAT_WS(', ', apellido1, nombre) FROM usuario ORDER BY apellido1", "id_usuario_orden", $gasto->fields['id_usuario_orden'] ? $gasto->fields['id_usuario_orden'] : $usuario_defecto, "", "Vacio", '170'); ?>
				</td>
			</tr>
		<?php } ?>
		<tr>
			<td align=right>
				<?php echo __('Ingresado por') ?>
			</td>
			<td align=left>
				<!-- $sesion, $query, $name, $selected='', $opciones='',$titulo='',$width='150' -->
				<?php echo Html::SelectQuery($sesion, "SELECT id_usuario, CONCAT_WS(', ', apellido1, nombre) FROM usuario ORDER BY apellido1", "id_usuario", isset($gasto->fields['id_usuario']) ? $gasto->fields['id_usuario'] : $usuario_defecto, "", "Vacio", '170'); ?>
			</td>
		</tr>
	</table>

	<?php if ($prov == 'false') { ?>
		<br />
		<?php if ($gasto->fields['id_movimiento_pago'] > 0) { ?>
			<div id='tabla_gastos'>
				<table style="border: 1px solid black;" width='90%'>
					<tr>
						<td align=right>
							<?php echo __('Fecha') ?>
						</td>
						<td align=left>
							<input type="text" name="fecha_pago" class="fechadiff" value="<?php echo $ingreso->fields['fecha'] ? Utiles::sql2date($ingreso->fields['fecha']) : date('d-m-Y') ?>" id="fecha_pago" size="11" maxlength="10" />
 						</td>
					</tr>
					<tr>
						<td align=right>
							<?php echo __('Documento') ?>
						</td>
						<td align=left>
							<input type="text" name="documento_pago" id=documento_pago value="<?php echo $ingreso->fields['documento_pago'] ?>">
						</td>
					</tr>
					<tr>
						<td align=right>
							<?php echo __('Monto') ?> <label><?php echo $ingreso->fields['id_moneda'] ? Utiles::Glosa($sesion, $ingreso->fields['id_moneda'], 'simbolo', 'prm_moneda', 'id_moneda') : '' ?></label>
						</td>
						<td align=left>
							<input type="text" name="monto_pago" id=monto_pago value="<?php echo $ingreso->fields['ingreso'] ?>" style="text-align:right" size=8>
							<input type=hidden name=tipo_moneda value=<?php echo Utiles::Glosa($sesion, $ingreso->fields['id_moneda'], 'simbolo', 'prm_moneda', 'id_moneda') ?>>
						</td>
					</tr>
					<tr>
						<td align=right>
							<?php echo __('Descripción') ?>
						</td>
						<td align=left>
							<textarea name=descripcion_ingreso cols="45" rows="3"><?php echo $ingreso->fields['descripcion'] ?></textarea>
						</td>
					</tr>
					<?php
					if (UtilesApp::GetConf($sesion, 'UsarImpuestoPorGastos') && UtilesApp::GetConf($sesion, 'UsarGastosConSinImpuesto')) {
						?>
						<tr>
							<td align=right>
								<?php echo __('Con Impuesto') ?>
								<?php
								if ($gasto->fields['con_impuesto'] == 'SI') {
									$con_impuesto_check = 'checked';
								} else {
									$con_impuesto_check = '';
								}
								?>
							</td>
							<td align=left>
								<input type="checkbox" id="con_impuesto" name="con_impuesto" value="1" <?php echo $con_impuesto_check; ?>>
							</td>
						</tr>
						<?php
					}
				}
				?>
			</table>
		</div>
		<?php
	}
	?>

	<br />
	<table style="border: 0px solid black;" width='90%'>
		<tr>
			<td align=left>
				<input type=button class=btn value="<?php echo __('Guardar') ?>" onclick="return Validar(this.form);" /> <input type=button class=btn value="<?php echo __('Cerrar') ?>" onclick="Cerrar();" />
			</td>
		</tr>
	</table>

</form>
<script type="text/javascript">
	 
<?php if (UtilesApp::GetConf($sesion, 'IdiomaGrande') && $codigo_asunto) { ?>
		CargaIdioma("<?php echo $codigo_asunto ?>");
<?php } ?>
	jQuery("#monto").blur(function(){
		var str = jQuery(this).val();
		jQuery(this).val( str.replace(',','.') );
		jQuery(this).parseNumber({format:"#.00", locale:"us"});
		jQuery(this).formatNumber({format:"#.00", locale:"us"});
	});
	jQuery("#monto_cobrable").blur(function(){
		var str = jQuery(this).val();
		jQuery(this).val( str.replace(',','.') );
		jQuery(this).parseNumber({format:"#.00", locale:"us"});
		jQuery(this).formatNumber({format:"#.00", locale:"us"});
	});
</script>
<?php
if (UtilesApp::GetConf($sesion, 'TipoSelectCliente') == 'autocompletador') {
	echo Autocompletador::Javascript($sesion);
}
echo InputId::Javascript($sesion);
$pagina->PrintBottom($popup);
