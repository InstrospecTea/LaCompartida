<?php
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
	require_once Conf::ServerDir().'/classes/Trabajo.php';
	require_once Conf::ServerDir().'/classes/Asunto.php';
	require_once Conf::ServerDir().'/classes/UtilesApp.php';
	require_once Conf::ServerDir().'/classes/Autocompletador.php';

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
		var cobrableOriginal = valoresDefault.cobrable;
		var cobrable = jQuery('#cobrable').val();
		if(cobrable){
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
		return true;
	}

	//solo se puede modificar el campo visible si no es cobrable
	function CheckVisible()
	{
		var incobrable = jQuery('[name=cobrable]').val() == '0';
		//.toggle lo deja como block
		jQuery('#divVisible').css('display', incobrable ? 'inline' : 'none');
	}
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
<form id="form_editar_trabajo" name="form_editar_trabajo" method="post" action="<?php echo $_SERVER[PHP_SELF]?>">
	<input type=hidden name=opcion value="guardar" />
	<input type=hidden name=popup value='<?php echo $popup?>' id="popup">

	<?php	if($txt_opcion) {	?>
		<table style='border:1px solid black' <?php echo $txt_opcion ? 'style=display:inline' : 'style=display:none'?> width=90%>
			<tr>
				<td align=left><span style=font-weight:bold; font-size:9px; backgroundcolor:#c6dead><?php echo $txt_opcion?></span></td>
			</tr>
		</table>
		<br>
	<?php	}	?>

	<table style="border:1px solid black" id="tbl_trabajo" width="90%">
		<tr>
			<td>&nbsp;</td>
			<td align="right">
				<?php echo __('Cliente'); ?>
			</td>
			<td align="left">
				<?php UtilesApp::CampoCliente($sesion, $valores_default['codigo_cliente'], $valores_default['codigo_cliente_secundario'], $valores_default['codigo_asunto'], $valores_default['codigo_asunto_secundario']); ?>
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
		</tr>

		<?php if (Conf::GetConf($sesion, 'UsoActividades')) { ?>
			<tr>
				<td colspan="2" align=right>
					<?php echo __('Actividad'); ?>
				</td>
				<td align=left>
					<?php echo  InputId::Imprimir($sesion, 'actividad', 'codigo_actividad', 'glosa_actividad', 'codigo_actividad', $valores_default['codigo_actividad']); ?>
				</td>
			</tr>
		<?php } else { ?>
			<input type="hidden" name="codigo_actividad" id="codigo_actividad">
			<input type="hidden" name="campo_codigo_actividad" id="campo_codigo_actividad">
		<?php } ?>

		<?php if($permiso_revisor) { ?>
			<tr>
				<td colspan="2" align=right>
					<?php echo __('Total Horas') ?>
				</td>
				<td align=left>
					<input type="text" name="total_duracion_cobrable_horas" size="5" value="<?php echo $total_duracion_cobrable_horas; ?>" />
					&nbsp;<?php echo __('Hrs')?>&nbsp;
					<input type="text" name="total_duracion_cobrable_minutos" size="5" value="<?php echo $total_duracion_cobrable_minutos; ?>" />
					&nbsp;<?php echo __('Min')?>&nbsp;&nbsp;&nbsp;<span style="color:red;font-size:7pt"><?php echo __('(se modificará la duración cobrable de los trabajos seleccionados)') ?></span>
				</td>
			</tr>
		<?php } ?>

		<?php if(!$permiso_profesional || $permiso_revisor) { ?>
			<tr>
				<td colspan="2" align=right>
					<?php echo __('Cobrable')?><br/>
				</td>
				<td align="left">
					<?php echo Html::SelectArray(array(array('', ''), array('0', 'No'), array('1', 'Si')), 'cobrable', $valores_default['cobrable'], 'onchange="CheckVisible()"', '', '50px'); ?>
					<div id="divVisible" style="display:<?php echo $valores_default['cobrable'] != '0' ? 'none' : 'inline'?>" >
						<?php if($permiso_revisor) {
							echo __('Visible');
							echo Html::SelectArray(array(array('', ''), array('0', 'No'), array('1', 'Si')), 'visible', $valores_default['visible'], 'onMouseover="ddrivetip(\'Trabajo será visible en la '.__('Nota de Cobro').'\')" onMouseout="hideddrivetip()"', '', '50px');
						} ?>
					</div>
				</td>
			</tr>
		<?php } ?>
		<?php if($num_trabajos > 0) { ?>
				<tr>
					<td colspan=2></td>
					<td colspan=3 align=left>
						<a onclick="return confirm('<?php echo __('¿Desea eliminar estos trabajos?'); ?>')" href="?opcion=eliminar&ids=<?php echo "$ids&popup=$popup"; ?>">
							<span style="border: 1px solid black; background-color: #ff0000;color:#FFFFFF;">&nbsp;<?php echo __('Eliminar trabajos') ?>&nbsp;</span>
						</a>
					</td>
				</tr>
		<?php	} ?>
		<tr>
			<td colspan='3' align='right'>
				<input type="hidden" name="opcion" value="guardar" />
				<input type="hidden" name="ids" value="<?php echo(''.$ids); ?>" />
				<input type="submit" class="btn" value="<?php echo __('Guardar')?>" onclick="return Validar(this.form);" />
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
		list($h,$m,$s) = explode(":",$time);
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
