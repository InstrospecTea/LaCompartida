<?php
require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion(array('OFI'));

$pagina = new Pagina($sesion);
$id_usuario = isset($id_usuario) ? $id_usuario : $sesion->usuario->fields['id_usuario'];

$gasto = new Gasto($sesion);
$ingreso = new Gasto($sesion);
$usuario = new UsuarioExt($sesion);

if ($id_gasto != '') {
	$gasto->Load($id_gasto);

	if ($codigo_asunto != $gasto->fields['codigo_asunto']) { //revisar para codigo secundario
		$cambio_asunto = true;
	}
}

if (($gasto->Loaded() && $gasto->fields['egreso'] > 0 ) || $prov == 'false') {
	$txt_pagina = $id_gasto ? __('Edici�n de Gastos') : __('Ingreso de Gastos');
	$txt_tipo = __('Gasto');
	$prov = 'false';
} else {
	$txt_pagina = $id_gasto ? __('Edici�n de Provisi�n') : __('Ingreso de Provisi�n');
	$txt_tipo = __('Provisi�n');
	$prov = 'true';
}

if (Conf::GetConf($sesion, 'A�adeAutoincrementableGasto')) {
	$criteria = new Criteria($sesion);
	$criteria
		->add_select('nro_seguimiento')
		->add_from('prm_nro_seguimiento_gasto')
		->add_ordering('id_nro_seguimiento_gasto')
		->add_ordering_criteria('DESC')
		->add_limit(1);
	$result = $criteria->run();
	if(empty($result)){
		$proposed = 1;
	} else {
		$proposed = $result[0]['nro_seguimiento'] + 1;
	}
}

$logged_user_can_save = true;
if ($gasto->Loaded()){
	$UserManager = new UserManager($sesion);
	$logged_user_id = $sesion->usuario->fields['id_usuario'];
	$expense_creator_user_id = $gasto->fields['id_usuario'];
	$logged_user_can_save = ($logged_user_id == $expense_creator_user_id) ||
												  ($UserManager->isGlobalReviewer($logged_user_id)) ||
												  ($UserManager->reviewsUser($logged_user_id, $expense_creator_user_id));
}
if ($opcion == "guardar") {
	if (!$logged_user_can_save) {
		$info = __('No se han podido guardar los cambios').'. '.__('Acceso denegado').'.';
		$pagina->AddInfo($info);
	} else {
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

		// Buscar cliente seg�n asunto seleccionado para revisar consistencia ...
		$query = "SELECT codigo_cliente FROM asunto WHERE codigo_asunto = '$codigo_asunto'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		list($codigo_cliente_segun_asunto) = mysql_fetch_array($resp);

		if ($codigo_cliente_segun_asunto != $codigo_cliente) {
			$pagina->AddError("El asunto seleccionado no corresponde al cliente seleccionado.");
		}

		// Solo cuando el gasto sea nuevo valido que el asunto se encuentre activo
		if (!empty($id_gasto) || $id_gasto == 0) {
			$asunto = new Asunto($sesion);
			$asunto->LoadByCodigo($codigo_asunto);

			if (!$asunto->fields['activo']) {
				$pagina->AddError('Debe seleccionar un ' . __('Asunto') . ' activo');
			}
		}

		$errores = $pagina->GetErrors();

		if (empty($errores)) {
			if ($_POST['cobrable'] == 1) {
				$gasto->Edit("cobrable", 1);
			} else {
				if (Conf::GetConf($sesion, 'UsarGastosCobrable')) {
					$gasto->Edit("cobrable", "0");
				} else {
					$gasto->Edit("cobrable", "1");
				}
			}

			/**
			 *  Si el gasto se considera con IVA,
			 *  se calcula en base al porcentaje impuesto gasto
			 *  del cobro
			 */
			if (!Conf::GetConf($sesion, 'UsarGastosConSinImpuesto')) {
				$con_impuesto = 1;
			}

			$gasto->Edit("con_impuesto", $con_impuesto == 1 ? "SI" : "NO");

			$monto = str_replace(',', '.', $monto);
			if ($prov == 'true') {
				$gasto->Edit("ingreso", $monto);
				$gasto->Edit("monto_cobrable", $monto);
			} else if ($prov == 'false') {
				if (Conf::GetConf($sesion, 'UsaMontoCobrable')) {
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
			$gasto->Edit("id_usuario", !empty($id_usuario) ? $id_usuario : "NULL");
			$gasto->Edit("descripcion", $descripcion);
			$gasto->Edit("id_glosa_gasto", (!empty($glosa_gasto) && $glosa_gasto != -1) ? $glosa_gasto : "NULL");
			$gasto->Edit("id_moneda", $id_moneda);
			$gasto->Edit("codigo_cliente", $codigo_cliente ? $codigo_cliente : "NULL");
			$gasto->Edit("codigo_asunto", $codigo_asunto ? $codigo_asunto : "NULL");
			$gasto->Edit("codigo_gasto", $codigo_gasto ? $codigo_gasto : "NULL");
			$gasto->Edit("id_usuario_orden", (!empty($id_usuario_orden) && $id_usuario_orden != -1) ? $id_usuario_orden : "NULL");
			$gasto->Edit("id_cta_corriente_tipo", $id_cta_corriente_tipo ? $id_cta_corriente_tipo : "NULL");
			$gasto->Edit("numero_documento", $numero_documento ? $numero_documento : "NULL");
			$gasto->Edit("cuenta_gasto", $cuenta_gasto ? $cuenta_gasto : "NULL");
			$gasto->Edit("detraccion", $detraccion ? $detraccion : "NULL");

			if ($id_tipo_documento_asociado) {
				$gasto->Edit("id_tipo_documento_asociado", $id_tipo_documento_asociado);
			}

			if (Conf::GetConf($sesion, 'FacturaAsociadaCodificada')) {
				$numero_factura_asociada = $pre_numero_factura_asociada . '-' . $post_numero_factura_asociada;
			}

			$gasto->Edit("codigo_factura_gasto", $numero_factura_asociada ? $numero_factura_asociada : "NULL");
			$gasto->Edit("fecha_factura", $fecha_factura_asociada ? Utiles::fecha2sql($fecha_factura_asociada) : "NULL");
			$gasto->Edit("numero_ot", $numero_ot ? $numero_ot : "NULL");

			if ($elimina_ingreso != '') {
				if (!$ingreso->EliminaIngreso($id_gasto)) {
					$ingreso_eliminado = '<br>' . __('El ingreso no pudo ser eliminado ya que existen otros gastos asociados.');
				}
			}

			//$gasto->Edit('id_movimiento_pago', NULL);

			// Ha cambiado el asunto del gasto se setea id_cobro NULL
			if ($cambio_asunto) {
				$gasto->Edit('id_cobro', 'NULL');
			}

			$gasto->Edit('id_proveedor', $id_proveedor ? $id_proveedor : NULL);
			$gasto->Edit('estado_pago', !empty($estado_pago) ? $estado_pago : NULL);
			if (Conf::GetConf($sesion, 'OrdenadoPor')) {
				$gasto->Edit('solicitante', $solicitante ? addslashes($solicitante) : null);
			}

			if (Conf::GetConf($sesion, 'A�adeAutoincrementableGasto')) {
				if (Gasto::VerificaIdentificador($sesion, $autoincrementable, $id_gasto) == "1"){
					$info = 'No se ha podido guardar los cambios debido a que el identificador ingresado ya est� en uso, por favor asigne otro.';
					$pagina->AddInfo($info);
				} else {
					if (empty($gasto->fields['nro_seguimiento'])) {
						Gasto::ActualizaUltimoIdentificador($sesion, $id_gasto, $autoincrementable);
					} else {
						if ($autoincrementable != $gasto->fields['nro_seguimiento'] ) {
							Gasto::ActualizaUltimoIdentificador($sesion, $id_gasto, $autoincrementable);
						}
					}
					$gasto->Edit("nro_seguimiento", $autoincrementable ? $autoincrementable : "NULL");
					if ($gasto->Write()) {
						$pagina->AddInfo($txt_tipo . ' ' . __('Guardado con �xito.') . ' ' . $ingreso_eliminado);
					}
				}
			} else {
				if ($gasto->Write()) {
					$pagina->AddInfo($txt_tipo . ' ' . __('Guardado con �xito.') . ' ' . $ingreso_eliminado);
				}
			}
		}
	}
}


$pagina->titulo = $txt_pagina;
$pagina->PrintTop($popup);
$Form = new Form;
?>

<script type="text/javascript">
	<?= UtilesApp::GetConfJS($sesion, 'OrdenadoPor'); ?>

	if (parent.window.Refrescarse) {
		parent.window.Refrescarse();
	} else if (window.opener.Refrescar) {
		window.opener.Refrescar();
	}

	function ShowGastos(valor) {
		$('tabla_gastos').style.display = valor ? 'inline' : 'none';
	}

	function CambiaMonto(form) {
		var monto = form.monto.value;

		<?php if (Conf::GetConf($sesion, 'ComisionGastos')) { ?>
			form.monto_cobrable.value = (form.monto.value * (1+form.porcentajeComision.value/100)).toFixed(2);
		<?php } else { ?>
			if (form.monto_cobrable) {
				form.monto_cobrable.value = form.monto.value;
			}
		<?php } ?>
	}

	function Validar() {
		monto = parseFloat(jQuery('#monto').val());

		if (jQuery('#monto_cobrable').length > 0) {
			monto_cobrable = parseFloat(jQuery('#monto_cobrable').val());
			if (monto <= 0 || isNaN(monto)) {
				monto = monto_cobrable;
			}
		}

		<?php if (Conf::GetConf($sesion, 'A�adeAutoincrementableGasto')) { ?>
			var identificador = parseInt(jQuery('#autoincrementable').val());
			jQuery('#autoincrementable').val(identificador);

			if (identificador == '' || isNaN(identificador) ) {
				alert('<?php echo __('Debe ingresar un identificador v�lido.'); ?>');
				jQuery('#autoincrementable').focus();
				return false;
			}
		<?php } ?>

		<?php if (Conf::GetConf($sesion, 'CodigoSecundario')) { ?>
			if (!jQuery('#codigo_cliente_secundario').val()) {
				alert('<?php echo __('Debe seleccionar un cliente'); ?>');
				jQuery('#codigo_cliente_secundario').focus();
				return false;
			}

			if (!jQuery('#codigo_asunto_secundario').val()) {
				alert('<?php echo __('Ud. debe seleccionar un') . ' ' . __('asunto'); ?>');
				jQuery('#codigo_asunto_secundario').focus();
				return false;
			}
		<?php } else { ?>
			if (!jQuery('#codigo_cliente').val()) {
				alert('<?php echo __('Debe seleccionar un cliente'); ?>');
				jQuery('#codigo_cliente').focus();
				return false;
			}

			if (!jQuery('#codigo_asunto').val()) {
				alert('<?php echo __('Ud. debe seleccionar un') . ' ' . __('asunto'); ?>');
				jQuery('#codigo_asunto').focus();
				return false;
			}
		<?php } ?>

		if (typeof  RevisarConsistenciaClienteAsunto == 'function') {
			RevisarConsistenciaClienteAsunto(jQuery('#form_gastos')[0]);
		}

		<?php if ($prov == 'false' && Conf::GetConf($sesion, 'UsaMontoCobrable')) { ?>
			if ((monto <= 0 || isNaN(monto)) && (monto_cobrable <= 0 || isNaN(monto_cobrable))) {
				alert('<?php echo __('Debe ingresar un monto para el gasto'); ?>');
				jQuery('#monto').focus();
				return false;
			}
		<?php } else { ?>
			if ((monto <= 0 || isNaN(monto))) {
				alert('<?php echo __('Debe ingresar un monto para el gasto'); ?>');
				jQuery('#monto').focus();
				return false;
			}
		<?php } ?>

		if (jQuery('#id_moneda option:selected').length == 0) {
			alert('<?php echo __('Debe seleccionar una Moneda'); ?>');
			return false;
		}

		if (!jQuery('#descripcion').val()) {
			alert('<?php echo __('Debe ingresar una descripci�n'); ?>');
			jQuery('#descripcion').focus();
			return false;
		}

		if (jQuery('#id_usuario').val() == -1) {
			jQuery('#id_usuario').val(<?php echo $id_usuario; ?>);
		}

		if (OrdenadoPor == 1 && !jQuery('#solicitante').val()) {
			alert("<?php echo __('Debe ingresar la persona que solicit� el gasto') ?>");
			jQuery('#solicitante').focus();
			return false;
		}

		<?php if (Conf::GetConf($sesion, 'TodoMayuscula')) { ?>
			if (jQuery('#descripcion').val()) {
				jQuery('#descripcion').val(jQuery('#descripcion').val().toUpperCase());
			}
			if (OrdenadoPor) {
				jQuery('#solicitante').val(jQuery('#solicitante').val().toUpperCase());
			}
		<?php } ?>

		return true;
	}

	function CheckEliminaIngreso(chk) {
		var form = $('form_gastos');
		form.elimina_ingreso.value  = chk ? 1 : '';
		return true;
	}

	function ActualizarDescripcion() {
		var w = $('glosa_gasto').selectedIndex;
		var selected_text = $('glosa_gasto').options[w].text;
		$('descripcion').value = selected_text;
	}

	<?php
	$contrato = new Contrato($sesion);

	if (Conf::GetConf($sesion, 'CodigoSecundario')) {
		$contrato->LoadByCodigoAsuntoSecundario($codigo_asunto_secundario);
		echo 'var CodigoSecundario = 1;';
	} else {
		$contrato->LoadByCodigoAsunto($codigo_asunto);
		echo 'var CodigoSecundario = 0;';
	}

	$gasto->extra_fields['id_contrato'] = $contrato->fields['id_contrato'];
	?>

	function AgregarNuevo(tipo, prov)	{
		if (CodigoSecundario) {
			var codigo_cliente_secundario = $('codigo_cliente_secundario').value;
			var codigo_asunto_secundario = $('codigo_asunto_secundario').value;
			if (tipo == 'gasto') {
				var urlo = "agregar_gasto.php?popup=1&prov=" + prov + "&codigo_cliente_secundario=" + codigo_cliente_secundario + "&codigo_asunto_secundario=" + codigo_asunto_secundario;
				window.location=urlo;
			}
		} else {
			var codigo_cliente = $('codigo_cliente').value;
			var codigo_asunto = $('codigo_asunto').value;
			if (tipo == 'gasto') {
				var urlo = "agregar_gasto.php?popup=1&prov=" + prov + "&codigo_cliente=" + codigo_cliente + "&codigo_asunto=" + codigo_asunto;
				window.location = urlo;
			}
		}
	}

	function AgregarProveedor() {
		var urlo = 'agregar_proveedor.php?popup=1';
		nuovaFinestra('Agregar_Proveedor', 430, 370, urlo);
	}
</script>

<form method="post" style="padding:5px;" id="form_gastos" autocomplete='off'>
	<input type="hidden" id="opcion" name="opcion" value="guardar" />
	<input type="hidden" name="id_gasto" value="<?php echo $gasto->fields['id_movimiento']; ?>" />
	<input type="hidden" name="id_gasto_general" value="<?php echo $gasto->fields['id_gasto_general']; ?>" />
	<input type="hidden" name='prov' value='<?php echo $prov; ?>'>
	<input type="hidden" name="id_movimiento_pago" id="id_movimiento_pago" value=<?php echo $gasto->fields['id_movimiento_pago']; ?>>
	<input type="hidden" name="elimina_ingreso" id="elimina_ingreso" value=''>

	<div id="calendar-container" style="width:221px; position:absolute; display:none;">
		<div class="floating" id="calendar"></div>
	</div>

	<br>
	<table width='90%'>
		<tr>
			<td align="left"><b><?php echo $txt_pagina; ?></b></td>
		</tr>
	</table>
	<?php
	if (Conf::GetConf($sesion, 'CodigoSecundario')) {
		if (!$codigo_cliente_secundario) {
			if ($gasto->fields['codigo_cliente']) {
				$codigo_cliente = $gasto->fields['codigo_cliente'];
			}

			$query = "SELECT codigo_cliente_secundario FROM cliente WHERE codigo_cliente = '$codigo_cliente'";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
			list($codigo_cliente_secundario) = mysql_fetch_array($resp);
		}

		if (!$codigo_asunto_secundario) {
			if ($gasto->fields['codigo_asunto']) {
				$codigo_asunto = $gasto->fields['codigo_asunto'];
			}

			$query = "SELECT codigo_asunto_secundario FROM asunto WHERE codigo_asunto = '$codigo_asunto'";
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

	<br>
	<div id="celda_agregar_gasto fr" style="width:96%;">
		<span class="fl">
			<b><?php echo __('Informaci�n de'); ?> <?php echo $prov == 'true' ? __('provisi�n') : __('gasto'); ?></b>
		</span>

		<?php echo $Form->icon_button($prov == 'true' ? __('Nueva provisi�n') : __('Nuevo gasto'), 'agregar', array('onclick' => "AgregarNuevo('gasto', {$prov});", 'class' => 'fr', 'style' => 'margin: 2px;')); ?>
		<?php ($Slim = Slim::getInstance('default',true)) ? $Slim->applyHook('hook_agregar_gasto_inicio') : false; ?>
	</div>

	<table class="border_plomo" style="background-color: #FFFFFF;" width='96%'>
	<?php if ($id_gasto) { ?>
		<tr>
			<td align="right">
				<?php echo __('Id. gasto'); ?>
			</td>
			<td align="left">
				<input name="id_gasto_cliente" id="id_gasto_cliente" size="10" disabled="disabled" value="<?php echo $id_gasto ?>" />
			</td>
		</tr>
	<?php } ?>
		<tr>
			<td align="right">
				<?php echo __('Fecha'); ?>
			</td>
			<td align="left">
				<input type="text" name="fecha" class="fechadiff" value="<?php echo $gasto->fields[fecha] ? Utiles::sql2date($gasto->fields[fecha]) : date('d-m-Y'); ?>" id="fecha" size="11" maxlength="10" />
			</td>
		</tr>

		<tr>
			<td align="right">
				<?php echo __('Cliente'); ?>
			</td>
			<td align="left">
				<?php UtilesApp::CampoCliente($sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario); ?>
				<span style="color:#FF0000; font-size:10px">*</span>
			</td>
		</tr>

		<tr>
			<td align="right">
				<?php echo __('Asunto'); ?>
			</td>
			<td align="left">
				<?php UtilesApp::CampoAsunto($sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario); ?>
				<span style="color:#FF0000; font-size:10px">*</span>
			</td>
		</tr>

		<?php if(Conf::GetConf($sesion, 'ExportacionLedes')) { ?>
		<tr>
			<td align="right">
				<?php echo __('C�digo UTBMS'); ?>
			</td>
			<td align="left">
				<?php echo InputId::ImprimirCodigo($sesion, 'UTBMS_EXPENSE', "codigo_gasto", $gasto->fields['codigo_gasto']); ?>
			</td>
		</tr>
		<?php } ?>

		<?php if (Conf::GetConf($sesion, 'TipoGasto') && $prov == 'false') { ?>
			<tr>
				<td align="right">
					<?php echo __('Tipo de Gasto'); ?>
				</td>
				<td align="left">
					<?php echo Html::SelectQuery($sesion, "SELECT id_cta_corriente_tipo, glosa FROM prm_cta_corriente_tipo order by glosa", "id_cta_corriente_tipo", $gasto->fields['id_cta_corriente_tipo'] ? $gasto->fields['id_cta_corriente_tipo'] : '1', '', '', "160"); ?>
				</td>
			</tr>
		<?php } ?>

		<?php
			/**
			 * La cuenta de gasto se utiliza en algunos estudios para indicar el centro de costo al cual ir�n los gastos en su contabilidad
			 */
			$PrmCodigo = new PrmCodigo($sesion);
			$cuenta_gasto_array = $PrmCodigo->Listar("WHERE grupo = 'CUENTA_GASTO' ORDER BY id_codigo ASC");
			if (count($cuenta_gasto_array) > 0) {
		?>
			<tr>
				<td align="right">
					<?php echo __('Cuenta de Gasto'); ?>
				</td>
				<td align="left">
					<?php echo Html::SelectArrayDecente($cuenta_gasto_array, 'cuenta_gasto', $gasto->fields['cuenta_gasto'], '', '', ''); ?>
				</td>
			</tr>
		<?php } ?>

		<?php
			/**
			 * La detracci�n se utiliza en Per� para posteriores c�lculo de impuestos
			 */
			$detraccion_array = $PrmCodigo->Listar("WHERE grupo = 'DETRACCION' ORDER BY id_codigo ASC");
			if (count($detraccion_array) > 0) {
		?>
			<tr>
				<td align="right">
					<?php echo __('Detracci�n'); ?>
				</td>
				<td align="left">
					<?php echo Html::SelectArrayDecente($detraccion_array, 'detraccion', $gasto->fields['detraccion'], '', '', ''); ?>
				</td>
			</tr>
		<?php } ?>

		<tr>
			<td align="right">
				<?php echo __('Proveedor'); ?>
			</td>
			<td align="left">
				<?php
					$proveedor = new Proveedor($sesion);
					echo Html::SelectArrayDecente($proveedor->Listar('ORDER BY glosa ASC'), 'id_proveedor', $gasto->fields['id_proveedor'], '', 'Cualquiera', '160px');

				?>
				<a href='javascript:void(0)' onclick="AgregarProveedor();" title="Agregar Proveedor"><img src="<?php echo Conf::ImgDir(); ?>/agregar.gif" border=0 ></a>
			</td>
		</tr>
	<?php if (Conf::GetConf($sesion, 'UsaEstadoPagoGastos')) { ?>
		<tr>
			<td align="right">
				<?php echo __('Estado Pago'); ?>
			</td>
			<td align="left">
				<?php echo Html::SelectQuery($sesion, "SELECT codigo, glosa FROM prm_codigo WHERE grupo = 'ESTADO_PAGO_GASTOS' ORDER BY glosa ASC", "estado_pago", $gasto->fields['estado_pago'], "", ""); ?>
			</td>
		</tr>
	<?php } ?>
		<tr>
			<td align="right">
				<?php echo __('Monto'); ?>
			</td>
			<td align="left">
				<input name="monto" id="monto" size="10"  value="<?php printf("%.2F", $gasto->fields['egreso'] ? $gasto->fields['egreso'] : $gasto->fields['ingreso']);   ?>" />
				<span style="color:#FF0000; font-size:10px">*</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<?php echo __('Moneda'); ?>&nbsp;
				<?php echo Html::SelectQuery($sesion, "SELECT id_moneda,glosa_moneda FROM prm_moneda ORDER BY id_moneda", "id_moneda", $gasto->fields['id_moneda'] ? $gasto->fields['id_moneda'] : '', '', '', "80"); ?>
				<span style="color:#FF0000; font-size:10px">*</span>
			</td>
		</tr>

		<?php if (Conf::GetConf($sesion, 'A�adeAutoincrementableGasto')) { ?>
			<tr>
				<td align="right">
					<?php echo __('Identificador'); ?>
				</td>
				<td align="left">
					<input name="autoincrementable" id="autoincrementable" size="10" value="<?php echo($gasto->fields['nro_seguimiento'] ? $gasto->fields['nro_seguimiento'] : $proposed)  ?>" />
					<span style="color:#FF0000; font-size:10px">*</span>
				</td>
			</tr>
		<?php } ?>

		<?php if (Conf::GetConf($sesion, 'ComisionGastos') && $prov == 'false') { ?>
			<tr>
				<td align="right">
					<?php echo __('Porcentaje comisi�n'); ?>
				</td>
				<td align="left">
					<input name="porcentajeComision" size="10"   value="<?php echo Conf::GetConf($sesion, 'ComisionGastos'); ?>" /> %
				</td>
			</tr>
		<?php } ?>

		<?php if ($prov == 'false' && Conf::GetConf($sesion, 'UsaMontoCobrable')) { ?>
			<tr>
				<td align="right">
					<?php echo __('Monto cobrable'); ?>&nbsp;
				</td>
				<td align="left">
					<input name="monto_cobrable" id="monto_cobrable" size="10" value="<?php  printf("%.2F", $gasto->fields['monto_cobrable']); ?>" />
				</td>
			</tr>
		<?php } ?>

		<?php
		if (Conf::GetConf($sesion, 'PrmGastos')) {
			$_onchange = '';
			$_titulo = 'Vacio';
			if (Conf::GetConf($sesion, 'PrmGastosActualizarDescripcion')) {
				$_onchange = 'onchange="ActualizarDescripcion()"';
				$_titulo = '';
			}
			?>
			<tr>
				<td align="right">
					<?php echo __('Descripci�n Parametrizada'); ?>
				</td>
				<td align="left">
					<?php echo Html::SelectQuery($sesion, "SELECT id_glosa_gasto,glosa_gasto FROM prm_glosa_gasto ORDER BY id_glosa_gasto", "glosa_gasto", $gasto->fields['id_glosa_gasto'] ? $gasto->fields['id_glosa_gasto'] : '', $_onchange, $_titulo, "300"); ?>
				</td>
			</tr>
		<?php } ?>

		<?php if (Conf::GetConf($sesion, 'NumeroGasto')) { ?>
			<tr>
				<td align="right">
					<?php echo __('N� Documento'); ?>
				</td>
				<td align="left">
					<input name=numero_documento size="10" value="<?php echo ($gasto->fields['numero_documento'] && $gasto->fields['numero_documento'] != 'NULL') ? $gasto->fields['numero_documento'] : ''; ?>" />
				</td>
			</tr>
		<?php } ?>

		<?php if (Conf::GetConf($sesion, 'FacturaAsociada')) { ?>
			<tr>
				<td align="right">
					<?php echo __('Documento Asociado'); ?>
				</td>
				<td align="left">
					<?php
					echo Html::SelectQuery($sesion, "SELECT id_tipo_documento_asociado, glosa FROM prm_tipo_documento_asociado ORDER BY id_tipo_documento_asociado", "id_tipo_documento_asociado", $gasto->fields['id_tipo_documento_asociado'] ? $gasto->fields['id_tipo_documento_asociado'] : '', '', 'Vacio', "140");

					if (Conf::GetConf($sesion, 'FacturaAsociadaCodificada')) {
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
						<input name="pre_numero_factura_asociada" size="3" maxlength="3" value="<?php echo $pre_numero_factura_asociada ? $pre_numero_factura_asociada : '' ?>" />
						<span>-</span>
						<input name="post_numero_factura_asociada" size="10" maxlength="10" value="<?php echo $post_numero_factura_asociada ? $post_numero_factura_asociada : '' ?>" />
					<?php } else { ?>
						<input name="numero_factura_asociada" size="10" value="<?php echo ($gasto->fields['codigo_factura_gasto'] && $gasto->fields['codigo_factura_gasto'] != 'NULL') ? $gasto->fields['codigo_factura_gasto'] : ''; ?>" />
					<?php } ?>

					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				</td>
			</tr>

			<tr>
				<td align="right">
					<?php echo __('Fecha Documento'); ?>
				</td>
				<td align="left">
					<input type="text" name="fecha_factura_asociada" class="fechadiff" value="<?php echo ($gasto->fields['fecha_factura'] && $gasto->fields['fecha_factura'] != 'NULL') ? Utiles::sql2date($gasto->fields['fecha_factura']) : '' ?>" id="fecha_factura_asociada" size="11" maxlength="10" />
				</td>
			</tr>
		<?php } ?>

		<?php if (Conf::GetConf($sesion, 'NumeroOT') && $prov == 'false') { ?>
			<tr>
				<td align="right">
					<?php echo __('N� OT'); ?>
				</td>
				<td align="left">
					<input name="numero_ot" size="10" value="<?php echo ($gasto->fields['numero_ot'] && $gasto->fields['numero_ot'] != 'NULL') ? $gasto->fields['numero_ot'] : '' ?>" />
				</td>
			</tr>
		<?php } ?>

		<tr id='descripcion_gastos'>
			<td align="right">
				<?php $size = Conf::GetConf($sesion, 'IdiomaGrande') ? '18px' : '9px'; ?>
				<?= __('Descripci�n') ?><br/><span id="txt_span" style="background-color: #C6FAAD; font-size:<?= $size; ?>"></span>
			</td>
			<td align="left">
				<?= $Form->spellCheck('descripcion', stripslashes($gasto->fields['descripcion']), array('cols' => 45, 'rows' => 3, 'label' => false));?>
			</td>
		</tr>

		<?php
		// Por definicion las provisiones no deben tener Impuestos
		if ($prov == 'false') {
			if (Conf::GetConf($sesion, 'UsarImpuestoPorGastos') && Conf::GetConf($sesion, 'UsarGastosConSinImpuesto')) {
				?>
				<tr>
					<td align="right">
						<?php
						echo __('Con Impuesto');
						if ($gasto->fields['con_impuesto'] == 'SI' || (empty($gasto->fields['id_movimiento']) && Conf::GetConf($sesion, 'GastosConImpuestosPorDefecto'))) {
							$con_impuesto_check = 'checked';
						} else {
							$con_impuesto_check = '';
						}
						?>
					</td>
					<td align="left">
						<input type="checkbox" id="con_impuesto" name="con_impuesto" value="1" <?php echo $con_impuesto_check; ?>>
					</td>
				</tr>
				<?php
			}
		} ?>

		<?php if (Conf::GetConf($sesion, 'UsarGastosCobrable')) { ?>
			<tr>
				<td align="right">
					<?php
					echo __('Cobrable');
					$cobrable_checked = 'checked';
					if ($id_gasto > 0) {
						$cobrable_checked = $gasto->fields['cobrable'] == 1 ? 'checked' : '';
					}
					?>
				</td>
				<td align="left">
					<input type="checkbox" id="cobrable" name="cobrable" value="1" <?php echo $cobrable_checked; ?>>
				</td>
			</tr>
		<?php } ?>
		<tr>
			<td align="right" colspan="2">&nbsp;</td>
		</tr>
		<?php if (Conf::GetConf($sesion, 'OrdenadoPor')): ?>
			<tr>
				<td align="right">
					<?= $Form->label(__('Solicitado por'), 'solicitante'); ?>
				</td>
				<td align="left">
					<?= $Form->input('solicitante', $gasto->fields['solicitante'], array('size' => '32', 'label' => false)); ?>
					<?php if (Conf::GetConf($sesion, 'OrdenadoPor') == 1): ?>
						<?= $Form->Html->span('*', array('style' => 'color:#FF0000; font-size:10px')); ?>
					<?php endif; ?>
				</td>
			</tr>
		<?php endif; ?>
		<?php
		$usuario_defecto = empty($gasto->fields['id_movimiento']) ? $sesion->usuario->fields['id_usuario'] : '';
		if ($prov == 'false') { ?>
			<tr>
				<td align="right">
					<?php echo __('Ordenado por'); ?>
				</td>
				<td align="left"><!-- Nuevo Select -->
					<?php echo $Form->select('id_usuario_orden', $usuario->get_usuarios_gastos(), $gasto->fields['id_usuario_orden'] ? $gasto->fields['id_usuario_orden'] : $usuario_defecto, array('style' => 'width: 170px')); ?>
				</td>
			</tr>
		<?php } ?>
		<tr>
			<td align="right">
				<?php echo __('Ingresado por'); ?>
			</td>
			<td align="left">
				<?php echo $Form->select('id_usuario', $usuario->get_usuarios_gastos(), isset($gasto->fields['id_usuario']) ? $gasto->fields['id_usuario'] : $usuario_defecto, array('style' => 'width: 170px')); ?>
			</td>
		</tr>
		<tr>
			<td></td>
		</tr>

		<tr>
			<td align="right">
				<?php if ($logged_user_can_save) { echo $Form->icon_submit(__('Guardar'), 'save'); } ?>
			</td>
			<td align="left">
				<?php echo $Form->icon_button(__('Cancelar'), 'exit', array('onclick' => 'window.close();')); ?>
			</td>
		</tr>
	</table>
</form>
<?php echo $Form->script(); ?>
<script type="text/javascript">
<?php
UtilesApp::GetConfJS($sesion, 'IdiomaGrande');
?>

	jQuery("#autoincrementable").change(function(){
		jQuery.post('ajax/ajax_gastos.php',{ opc: "identificador", identificador: jQuery("#autoincrementable").val()})
		.done(function(data){
			if (data == "1") {
				alert('<?php echo __('El valor del identificador ya est� siendo utilizado.'); ?>');
				jQuery('#autoincrementable').focus();
			}
		});
	});

	jQuery("#monto, #monto_cobrable").change(function() {
		var str = jQuery(this).val();
		jQuery(this).val(str.replace(',', '.'));
		jQuery(this).parseNumber({format:"0.00", locale:"us"});
		jQuery(this).formatNumber({format:"0.00", locale:"us"});
		if (jQuery(this).attr('id') == 'monto') {
			CambiaMonto(this.form, this.id);
		}
	});
	jQuery('#form_gastos').submit(function() {
		return Validar();
	});

	jQuery('#codigo_asunto, #codigo_asunto_secundario').change(function () {
		var codigo = jQuery(this).val();

		if (!codigo) {
			jQuery('#txt_span').html('');
			return false;
		} else {
			jQuery.get(root_dir + '/app/Matters/getLanguage/' + codigo, function (language) {
				if (!language) {
					return;
				};

				if (IdiomaGrande) {
					jQuery('#txt_span').html(language.name);
				} else {
					jQuery('#txt_span').html('Idioma: ' + language.name);
				}

				jQuery('#descripcion').data('googie').setCurrentLanguage(language.code);
			});
		}
	}).change();
</script>

<?php $pagina->PrintBottom($popup);
