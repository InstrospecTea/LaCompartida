<?php
	require_once dirname(__FILE__).'/../conf.php';

	$sesion = new Sesion(array('PRO','REV'));
	$pagina = new Pagina($sesion);

	$permiso_revisor = $sesion->usuario->Es('REV');
	$permiso_profesional = $sesion->usuario->Es('PRO');

	if($listado) {
		$where_query_listado_completo = preg_replace("/\r\n|\r|\n/", '', base64_decode($listado));
		$where_query_listado_completo = mysql_real_escape_string($where_query_listado_completo);
		$where_query_listado_completo = str_replace("\'","'",$where_query_listado_completo);
		$where_query_listado_completo = str_replace(";","",$where_query_listado_completo);
		$where_query_listado_completo = ereg_replace("[dD][rR][oO][pP]","",$where_query_listado_completo);
		$where_query_listado_completo = ereg_replace("[dD][eE][lL][eE][tT][eE]","",$where_query_listado_completo);
		$where_query_listado_completo = ereg_replace("[aA][lL][tT][eE][rR][ ]*[tT][aA][bB][lL][eE]","",$where_query_listado_completo);
		$where_query_listado_completo = ereg_replace("[aA][lL][tT][eE][rR][ ]*[tT][aA][bB][lL][eE]","",$where_query_listado_completo);

		if($where_query_listado_completo) {
			$query_listado_completo = "SELECT trabajo.id_trabajo
																	FROM trabajo
																	JOIN asunto ON trabajo.codigo_asunto=asunto.codigo_asunto
																	LEFT JOIN actividad ON trabajo.codigo_actividad=actividad.codigo_actividad
																	LEFT JOIN cliente ON cliente.codigo_cliente=asunto.codigo_cliente
																	LEFT JOIN cobro ON cobro.id_cobro=trabajo.id_cobro
																	LEFT JOIN contrato ON asunto.id_contrato=contrato.id_contrato
																	LEFT JOIN usuario ON trabajo.id_usuario=usuario.id_usuario
																	WHERE $where_query_listado_completo";

			//$query = mcrypt_decrypt(MCRYPT_CRYPT,Conf::Hash(),$listado_completo,MCRYPT_ENCRYPT);
			$resp = mysql_query($query_listado_completo, $sesion->dbh) or Utiles::errorSQL($query_listado_completo,__FILE__,__LINE__,$sesion->dbh);
			$ids="";
			while($trabajo_temporal_query=mysql_fetch_array($resp)) {
				$ids.="t".$trabajo_temporal_query['id_trabajo'];
			}
		}
	}

	// Parsear el string con la lista de ids, vienen separados por el caracter 't' ("t1t2t35t23456")
	$ids_trabajos = explode('t', substr($ids, 1));
	$num_trabajos = count($ids_trabajos);

	$campo_asunto = 'codigo_asunto';
	$campo_cliente = 'codigo_cliente';
	if (Conf::GetConf($sesion, 'CodigoSecundario')) {
		$campo_asunto = 'codigo_asunto_secundario';
		$campo_cliente = 'codigo_cliente_secundario';
	}

	// Cargar cada trabajo en un arreglo y validar que sigan siendo editables
	$trabajos = array();
	foreach ($ids_trabajos as $id) {
		$t = new Trabajo($sesion) or die("No se pudo cargar el trabajo $id");
		$t->Load($id);
		$trabajos[] = $t;

		$estado = $t->Estado();

		if ($estado == __('Cobrado')) {
			$pagina->AddError(__('Trabajos masivos ya cobrados'));
			$pagina->PrintTop($popup);
			$pagina->PrintBottom($popup);
			exit;
		}

		if ($estado == 'Revisado' && !$permiso_revisor) {
			$pagina->AddError(__('Trabajo ya revisado'));
			$pagina->PrintTop($popup);
			$pagina->PrintBottom($popup);
			exit;
		}
	}

	if (!isset($total_duracion_cobrable_horas) && !isset($total_duracion_cobrable_minutos)) {
		$total_minutos_cobrables = 0;

		foreach ($trabajos as $t) {
			// se calcula en minutos porque el intervalo es en minutos
			$time = explode(':', $t->fields['duracion_cobrada']);
			$total_minutos_cobrables += intval($time[0]) * 60 + intval($time[1]);
		}

		$total_duracion_cobrable_horas = floor($total_minutos_cobrables / 60);
		$total_duracion_cobrable_minutos = $total_minutos_cobrables % 60;
	}

	if(!$codigo_asunto_secundario && Conf::GetConf($sesion,'CodigoSecundario') && $num_trabajos > 0) {
		//se carga el codigo secundario
		$asunto = new Asunto($sesion);
		$asunto->LoadByCodigo($trabajos[0]->fields['codigo_asunto']);
		$codigo_asunto_secundario = $asunto->fields['codigo_asunto_secundario'];
		$cliente = new Cliente($sesion);
		$cliente->LoadByCodigo($asunto->fields['codigo_cliente']);
		$codigo_cliente_secundario = $cliente->fields['codigo_cliente_secundario'];
	}

	if($opcion == "guardar") {
		$trabajo = new Trabajo($sesion);

		$resultado = $trabajo->EditarMasivo($trabajos, $_POST);
		if($resultado['error']){
			$pagina->AddError($resultado['error']);
		}
		if(!empty($resultado['info'])){
			foreach ($resultado['info'] as $msg) {
				$pagina->AddInfo($msg);
			}
		}
		if($resultado['modificados'] > 0) {
			if($resultado['modificados'] == 1) {
				$pagina->AddInfo(__('Trabajo').' '.__('Guardado con exito'));
			}
			else {
				$pagina->AddInfo($resultado['modificados'].' '.__('trabajo').'s '.__('guardados con exito.'));
			}
			//refresca el listado de horas.php cuando se graba la informacion desde el popup
			echo '<script>window.opener.Refrescar();</script>';
		}
	}
	else if($opcion == "eliminar") {
		//ELIMINAR TRABAJOS
		foreach ($trabajos as $t) {
			if(!$t->Eliminar()) {
				$pagina->AddError($t->error);
			}
		}
		if($num_trabajos==1)
			$pagina->AddInfo(__('Trabajo').' '.__('eliminado con éxito'));
		else
			$pagina->AddInfo($num_trabajos.' '.__('trabajo').'s '.__('aliminados con éxito.'));

		echo '<script>window.opener.Refrescar();</script>';
	}

	$keys = array_flip(array('codigo_asunto', 'codigo_actividad', 'cobrable', 'visible'));
	$valores_default = array_intersect_key($trabajos[0]->fields, $keys);
	$hay_cobros = false;
	foreach ($trabajos as $t) {
		$valores = array_intersect_key($t->fields, $keys);
		$valores_default = array_intersect_assoc($valores_default, $valores);
		$hay_cobros = $hay_cobros || !empty($t->fields['id_cobro']);
	}

	$cobrable_indeterminado = false;
	if (!isset($valores_default['cobrable'])) {
		$cobrable_indeterminado = true;
	}

	// Título opcion
	if($opcion == '' && $num_trabajos > 0)
		$txt_opcion = __('Modificación masiva de Trabajos');
	else if($opcion == 'nuevo')
		$txt_opcion = __('Agregando nuevo Trabajo');
	else if($opcion == '')
		$txt_opcion = '';

	if($num_trabajos) {
		if($valores_default['codigo_asunto']){
			$valores_default['codigo_cliente'] = $trabajos[0]->get_codigo_cliente();
			$valores_default['codigo_cliente_secundario'] = $codigo_cliente_secundario;
			$valores_default['codigo_asunto_secundario'] = $codigo_asunto_secundario;
		}
		else if(!empty($ids_trabajos)){
			$ids_csv = implode($ids_trabajos, ',');
			$query = "SELECT DISTINCT codigo_cliente
					FROM trabajo
						JOIN asunto ON asunto.codigo_asunto = trabajo.codigo_asunto
					WHERE id_trabajo IN ($ids_csv)";
			$codigos = $sesion->pdodbh->query($query)->fetchAll(PDO::FETCH_COLUMN);
			if(count($codigos) == 1){
				$valores_default['codigo_cliente'] = $codigos[0];
				$valores_default['codigo_cliente_secundario'] = $codigo_cliente_secundario;
			}
		}
	}

	$pagina->titulo = __('Modificación masiva de').' '.__('Trabajos');
	$pagina->PrintTop($popup);
?>
<script type="text/javascript">
	var valoresDefault = <?php echo json_encode($valores_default); ?>;
	var hayCobros = <?php echo $hay_cobros ? 'true' : 'false'; ?>;

	function Validar(form)
	{
		var cobrableOriginal = valoresDefault.cobrable == 1 ? true : false;
		var cobrable = jQuery('#cobrable').is(':checked');
		var cobrable_check = jQuery('#cobrable_check').is(':checked');

		checkBoxValues(form);

		if(cobrable_check){
			if(cobrable != cobrableOriginal){
				var msg = 'Uno o más trabajos no cobrables pasarán a estado cobrable. ¿Desea continuar?';
				if(cobrable == '0'){
					msg = 'Uno o más trabajos cobrables pasarán a estado no cobrable. ¿Desea continuar?';
				}
				if(!confirm(msg)){
					return false;
				}
			}
		}

		//Valida si el asunto ha cambiado para este trabajo que es parte de un cobro, si ha cambiado se emite un mensaje indicandole lo ki pa
		var codigoAsunto = form.codigo_asunto.value;
		var asuntoOriginal = valoresDefault.codigo_asunto;
		if(codigoAsunto && codigoAsunto != asuntoOriginal && hayCobros){
			var msg = 'Ud. está modificando un trabajo que pertenece a <?php echo __('un cobro'); ?>. Si acepta, el trabajo se podría desvincular de este <?php echo __('cobro'); ?> y vincularse a <?php echo __('un cobro'); ?> pendiente para el nuevo asunto en caso de que exista.';
			if(!confirm(msg)){
				return false;
			}
		}
		sendPost('form_editar_trabajo', 'opcion', 'guardar');
	}

	function checkBoxValues(form)
	{
		if (jQuery('#check_cobrable').is(':checked')) {
			cobrable_editado = document.createElement('input');
			cobrable_editado.setAttribute('name', 'cobrable');
			cobrable_editado.setAttribute('type', 'hidden');
			if (jQuery('#cobrable').is(':checked')) {
				cobrable_editado.setAttribute('value', '1');
			} else {
				cobrable_editado.setAttribute('value', '0');
			}

			form.appendChild(cobrable_editado);
		}

		if (jQuery('#check_visible').is(':checked')) {
			visible_editado = document.createElement('input');
			visible_editado.setAttribute('name', 'visible');
			visible_editado.setAttribute('type', 'hidden');
			if (jQuery('#visible').is(':checked')) {
				visible_editado.setAttribute('value', '1');
			} else {
				visible_editado.setAttribute('value', '0');
			}
			form.appendChild(visible_editado);
		}
	}

	function CargarActividad() {
		CargarSelect('<?php echo $campo_asunto ?>', 'codigo_actividad', 'cargar_actividades');
	}

	function sendPost(form, name, value) {
		var form = document.getElementById(form);

		var field = document.createElement('input');
		field.setAttribute('type', 'hidden');
		field.setAttribute('name', name);
		field.setAttribute('value', value);

		form.appendChild(field);

		document.body.appendChild(form);
		form.submit();
	}

	jQuery(function() {
		var campo_cliente = '<?php echo $campo_cliente; ?>';
		var campo_asunto = '<?php echo $campo_asunto; ?>';
		var cobrable_indeterminado = '<?php echo $cobrable_indeterminado; ?>';

		jQuery(document).ready(function() {
			if (cobrable_indeterminado) {
				jQuery("#cobrable").prop("indeterminate", true);
			}

			disabledElements(
				[
					'#' + campo_cliente,
					'#campo_' + campo_cliente,
					'#' + campo_asunto,
					'#glosa_asunto',
					'#glosa_asunto_btn',
					'#campo_codigo_actividad',
					'#codigo_actividad',
					'#total_duracion_cobrable_horas',
					'#total_duracion_cobrable_minutos',
					'#cobrable',
					'#visible'
				]);
			checkVisible();
			toggleButtonAcept();
		});

		jQuery('#check_cliente').on('click', function() {
			toggleDisabledElement('#check_cliente', ['#' + campo_cliente, '#campo_' + campo_cliente]);

			// Si edita un cliente se debe enviar el asunto también
			if (jQuery('#check_cliente').prop('checked')) {
				jQuery('#check_asunto').prop('checked', true);
			} else {
				jQuery('#check_asunto').prop('checked', false);
			}
			toggleDisabledElement('#check_asunto', ['#' + campo_asunto, '#glosa_asunto', '#glosa_asunto_btn']);
		});

		jQuery('#check_asunto').on('click', function() {
			toggleDisabledElement('#check_asunto', ['#' + campo_asunto, '#glosa_asunto', '#glosa_asunto_btn']);

			// Si edita un asunto se debe enviar el cliente también
			if (jQuery('#check_asunto').prop('checked')) {
				jQuery('#check_cliente').prop('checked', true);
			} else {
				jQuery('#check_cliente').prop('checked', false);
			}
			toggleDisabledElement('#check_cliente', ['#' + campo_cliente, '#campo_' + campo_cliente]);
		});

		jQuery('#check_actividad').on('click', function() {
			toggleDisabledElement('#check_actividad', ['#campo_codigo_actividad', '#codigo_actividad']);
		});

		jQuery('#check_total_horas').on('click', function() {
			toggleDisabledElement('#check_total_horas', ['#total_duracion_cobrable_horas', '#total_duracion_cobrable_minutos']);
		});

		jQuery('#check_cobrable').on('click', function() {
			toggleDisabledElement('#check_cobrable', ['#cobrable']);
		});

		jQuery('#check_visible').on('click', function() {
			toggleDisabledElement('#check_visible', ['#visible']);
		});

		jQuery('#eliminar_btn').on('click', function() {
			if (confirm("<?php echo __('¿Desea eliminar estos trabajos?'); ?>")) {
				sendPost('form_editar_trabajo', 'opcion', 'eliminar');
			} else {
				return false;
			}
		});

		jQuery('#cobrable').on('click', function() {
			checkVisible();
		});

		//solo se puede modificar el campo visible si no es cobrable
		function checkVisible() {
			if (jQuery('#cobrable').prop('checked')) {
				jQuery('#tr_visible').css('display', 'none');
			} else {
				jQuery('#tr_visible').css('display', '');
			}
		}

		function toggleDisabledElement(id_check, id_elements) {
			if (jQuery(id_check).prop('checked')) {
				jQuery.each(id_elements, function(index, value){
					if (jQuery(value).is('a')) {
						jQuery(value).show();
					} else {
						jQuery(value).prop('disabled', false);
					}
				});
			} else {
				jQuery.each(id_elements, function(index, value){
					if (jQuery(value).is('a')) {
						jQuery(value).hide();
					} else {
						jQuery(value).prop('disabled', true);
					}
				});
			}
			toggleButtonAcept();
		}

		function toggleButtonAcept(){
			if (jQuery('#check_cliente').prop('checked')) {
				jQuery('#aceptar_btn').show();
			} else if (jQuery('#check_asunto').prop('checked')) {
				jQuery('#aceptar_btn').show();
			} else if (jQuery('#check_actividad').prop('checked')) {
				jQuery('#aceptar_btn').show();
			} else if (jQuery('#check_total_horas').prop('checked')) {
				jQuery('#aceptar_btn').show();
			} else if (jQuery('#check_cobrable').prop('checked')) {
				jQuery('#aceptar_btn').show();
			} else if (jQuery('#check_visible').prop('checked') && !jQuery('#cobrable').prop('checked')) {
				jQuery('#aceptar_btn').show();
			} else {
				jQuery('#aceptar_btn').hide();
			}
		}

		function disabledElements(id_elements){
			jQuery.each(id_elements, function(index, value){
				if (jQuery(value).is('a')) {
						jQuery(value).hide();
					} else {
						jQuery(value).prop('disabled', true);
					}
			});
		}
	});

</script>
<?php
echo(Autocompletador::CSS());
if($opcion == "eliminar")
{
	echo '<button onclick="window.close();">'.__('Cerrar ventana').'</button>';
}
else
{
?>
<form id="form_editar_trabajo" name="form_editar_trabajo" method="post" action="<?php echo $_SERVER[PHP_SELF]?>" style="display: inline-block !important;">
	<input type="hidden" name="popup" value='<?php echo $popup?>' id="popup">

	<?php	if($txt_opcion) {	?>
		<table style="border: 0px" <?php echo $txt_opcion ? "style=display:inline" : "style=display:none"?>>
			<tr>
				<td align="left"><span style="font-weight:bold; font-size:9px; backgroundcolor:#c6dead"><?php echo $txt_opcion?></span></td>
			</tr>
		</table>
		<br>
	<?php	}	?>

	<table style="border: 0px" id="tbl_trabajo">
		<tr>
			<td>&nbsp;</td>
			<td align="right">
				<?php echo __('Cliente'); ?>
			</td>
			<td align="left">
				<?php UtilesApp::CampoCliente($sesion, $valores_default['codigo_cliente'], $valores_default['codigo_cliente_secundario'], $valores_default['codigo_asunto'], $valores_default['codigo_asunto_secundario']); ?>
			</td>
			<td>
				<input type="checkbox" id="check_cliente" value="" />
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td align="right">
				 <?php echo __('Asunto'); ?>
			</td>
			<td align="left">
				<?php
				$oncambio = '';
				if (Conf::GetConf($sesion, 'UsoActividades') || Conf::GetConf($sesion, 'ExportacionLedes')) {
					$oncambio .= 'CargarActividad();';
				}
				UtilesApp::CampoAsunto($sesion, $valores_default['codigo_cliente'], $valores_default['codigo_cliente_secundario'], $valores_default['codigo_asunto'], $valores_default['codigo_asunto_secundario'], 320, $oncambio, $glosa_asunto, false); ?>
			</td>
			<td>
				<input type="checkbox" id="check_asunto" value="" />
			</td>
		</tr>

		<?php if (Conf::GetConf($sesion, 'UsoActividades')) { ?>
			<tr>
				<td colspan="2" align="right">
					<?php echo __('Actividad'); ?>
				</td>
				<td align="left">
					<?php echo  InputId::Imprimir($sesion, 'actividad', 'codigo_actividad', 'glosa_actividad', 'codigo_actividad', $valores_default['codigo_actividad'], '', '', 300, $valores_default['codigo_asunto']); ?>
				</td>
				<td>
					<input type="checkbox" id="check_actividad" value="" />
				</td>
			</tr>
		<?php } else { ?>
			<input type="hidden" name="codigo_actividad" id="codigo_actividad">
			<input type="hidden" name="campo_codigo_actividad" id="campo_codigo_actividad">
		<?php } ?>

		<?php if($permiso_revisor) { ?>
			<tr>
				<td colspan="2" align="right">
					<?php echo __('Total Horas') ?>
				</td>
				<td align="left">
					<input type="text" id="total_duracion_cobrable_horas" name="total_duracion_cobrable_horas" size="5" value="<?php echo $total_duracion_cobrable_horas; ?>" />
					&nbsp;<?php echo __('Hrs')?>&nbsp;
					<input type="text" id="total_duracion_cobrable_minutos" name="total_duracion_cobrable_minutos" size="5" value="<?php echo $total_duracion_cobrable_minutos; ?>" />
					&nbsp;<?php echo __('Min')?>&nbsp;&nbsp;&nbsp;<span style="color:red;font-size:7pt"><?php echo __('(se modificará la duración cobrable de los trabajos seleccionados)') ?></span>
				</td>
				<td>
					<input type="checkbox" id="check_total_horas" value="" />
				</td>
			</tr>
		<?php } ?>

		<?php if(!$permiso_profesional || $permiso_revisor) { ?>
			<tr>
				<td colspan="2" align="right">
					<?php echo __('Cobrable')?><br/>
				</td>
				<td align="left">
					<input type="checkbox" id="cobrable" <?php echo $valores_default['cobrable'] == 1 ? 'checked' : ''; ?> />
				</td>
				<td>
					<input type="checkbox" id="check_cobrable" value="" />
				</td>
			</tr>
				<tr id="tr_visible">
					<td colspan="2" align="right"><?php echo __('Visible'); ?></td>
					<td align="left">
						<input type="checkbox" id="visible" <?php echo $valores_default['visible'] == 1 ? 'checked' : ''; ?> />
					</td>
					<td>
						<input type="checkbox" id="check_visible" value="" />
					</td>
				</tr>

		<?php } ?>
			<tr>
				<td colspan="4" align="right">
					<input type="hidden" name="ids" value="<?php echo(''.$ids); ?>" />
					<?php if($num_trabajos > 0) { ?>
						<input type="submit" id="eliminar_btn" class="btn" style="background: #ff0000 !important; color:#FFFFFF !important;" value="<?php echo __('Eliminar trabajos') ?>" />
					<?php	} ?>
					<input type="submit" id="aceptar_btn" class="btn" value="<?php echo __('Guardar')?>" onclick="return Validar(this.form);" />
				</td>
			</tr>
	</table>
</form>

<?php }
if(Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador')
	{
		echo(Autocompletador::Javascript($sesion));
	}
	echo(InputId::Javascript($sesion));
	$pagina->PrintBottom($popup);
	function SplitDuracion($time)
	{
		list($h,$m,$s) = split(":",$time);
		return $h.":".$m;
	}
	function Substring($string)
	{
		if(strlen($string) > 250)
			return substr($string, 0, 250)."...";
		else
			return $string;
	}
?>
