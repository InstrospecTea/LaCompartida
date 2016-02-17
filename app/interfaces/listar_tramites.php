<?php
require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion(array('PRO', 'REV', 'ADM', 'COB', 'SEC'));
$pagina = new Pagina($sesion);
$Form = new Form();

$p_revisor = $sesion->usuario->Es('REV');
$p_cobranza = $sesion->usuario->Es('COB');
$p_profesional = $sesion->usuario->Es('PRO');

if ($p_cobranza) {
	//Si tiene premido COB, se le asigna permiso REV
	$p_revisor = true;
}


if ($accion == 'eliminar' && $p_revisor) {
	$accion = '';
	$tramite = new Tramite($sesion);
	$tramite->Load($id_tramite);
	if ($tramite->Eliminar()) {
		$pagina->AddInfo(__('Tr�mite') . ' ' . __('eliminado con �xito'));
	} else {
		$pagina->AddError($tramite->error);
	}
	unset($tramite);
}

##Seteando FECHAS a formato SQL
if ($fecha_ini != '') {
	$fecha_ini = Utiles::fecha2sql($fecha_ini);
} else {
	$fecha_ini = Utiles::fecha2sql($fecha_ini, '0000-00-00');
}

if ($fecha_fin != '') {
	$fecha_fin = Utiles::fecha2sql($fecha_fin);
} else {
	$fecha_fin = Utiles::fecha2sql($fecha_fin, '0000-00-00');
}

if ($id_cobro == 'Indefinido') {
	$cobro_nulo = true;
	unset($id_cobro);
}

#Si estamos en un cobro
if ($id_cobro) {
	$cobro = new Cobro($sesion);
	$cobro->Load($id_cobro);

	if (!$cobro->Load($id_cobro)) {
		$pagina->FatalError(__('Cobro inv�lido'));
	} else {
		//En caso de que no estoy buscando debo setear fecha ini y fecha fin
		$fecha_ini = $cobro->fields['fecha_ini'];
		$fecha_fin = $cobro->fields['fecha_fin'];
	}
}

// Calculado aquí para que la variable $select_usuario esté disponible al generar la tabla de trabajos.
if ($p_revisor) {
	$where_usuario = '';
} else {
	$where_usuario = "AND (usuario.id_usuario IN (SELECT id_revisado FROM usuario_revisor WHERE id_revisor=" . $sesion->usuario->fields['id_usuario'] . ") OR usuario.id_usuario=" . $sesion->usuario->fields['id_usuario'] . ")";
}

$where = base64_decode($where);
if ($where == '') {
	$where .= 1;
}

if ($id_usuario != '') {
	$where .= " AND tramite.id_usuario='$id_usuario' ";
} else if (!$p_revisor) {
	// Se buscan trabajos de los usuarios a los que se puede revisar.
	$where .= " AND (usuario.id_usuario IN (SELECT id_revisado FROM usuario_revisor WHERE id_revisor=" . $sesion->usuario->fields['id_usuario'] . ") OR usuario.id_usuario=" . $sesion->usuario->fields['id_usuario'] . ") ";
}

if ($revisado == 'NO') {
	$where.= " AND tramite.revisado = 0 ";
}

if ($revisado == 'SI') {
	$where.= " AND tramite.revisado = 1 ";
}

if (Conf::GetConf($sesion, 'CodigoSecundario')) {
	if (!empty($codigo_asunto_secundario)) {
		$asunto = new Asunto($sesion);
		$codigo_asunto = $asunto->CodigoSecundarioACodigo($codigo_asunto_secundario);
	}
}

if ($codigo_asunto != '') {
	$where.= " AND tramite.codigo_asunto = '".$codigo_asunto."' ";
} else if (trim($glosa_asunto) != '') {
	$where.= " AND asunto.glosa_asunto LIKE '%{$glosa_asunto}%' ";
}


if ($cobrado == 'NO') {
	$where .= " AND tramite.id_cobro is null ";
}

if ($cobrado == 'SI') {
	$where .= " AND tramite.id_cobro is not null AND (cobro.estado = 'EMITIDO' OR cobro.estado = 'PAGADO' OR cobro.estado = 'ENVIADO AL CLIENTE' OR cobro.estado = 'FACTURADO' OR cobro.estado = 'PAGO PARCIAL') ";
}

if ($from == 'reporte') {
	if ($id_cobro) {
		$where .= " AND contrato.id_contrato = cobro.id_contrato ";
	}

	if ($mes) {
		$where .= " AND CONCAT(Month(fecha),'-',Year(fecha)) = '$mes' ";
	}

	if ($cobro_nulo) {
		$where .= " AND tramite.id_cobro IS NULL ";
	}

	if ($estado) {
		if ($estado != 'abiertos') {
			if ($estado == 'Indefinido') {
				$where .= " AND cobro.id_cobro IS NULL";
			} else {
				$where .= " AND cobro.estado = '$estado' ";
			}
		}
	}

	if ($lis_clientes) {
		$where .= " AND cliente.codigo_cliente IN (" . $lis_clientes . ") ";
	}
	if ($lis_usuarios) {
		$where .= " AND usuario.id_usuario IN (" . $lis_usuarios . ") ";
	}
}

//Estos filtros son tambien para la pag. mis horas
if ($activo) {
	if ($activo == 'SI') {
		$activo = 1;
	} else {
		$activo = 0;
	}

	$where .= " AND a1.activo = $activo ";
}

if (Conf::GetConf($sesion, 'CodigoSecundario')) {
	if ($codigo_cliente_secundario != "") {
		$where .= " AND cliente.codigo_cliente_secundario = '$codigo_cliente_secundario' ";
	}
} else {
	if ($codigo_cliente != "") {
		$where .= " AND cliente.codigo_cliente ='$codigo_cliente' ";
	}
}

#SQL FECHAS
if ($fecha_ini != '' and $fecha_ini != 'NULL' and $fecha_ini != '0000-00-00') {
	$where .= " AND tramite.fecha >= '" . $fecha_ini . "' ";
}

if ($fecha_fin != '' and $fecha_fin != 'NULL' and $fecha_fin != '0000-00-00') {
	$where .= " AND tramite.fecha <= '" . $fecha_fin . "' ";
}

if (isset($cobro)) { // Es decir si es que estoy llamando a esta pantalla desde un cobro
	$cobro->LoadAsuntos();
	$query_asuntos = implode("','", $cobro->asuntos);
	$where .= " AND tramite.codigo_asunto IN ('$query_asuntos') ";
	//$where .= " AND tramite.cobrable = 1";
	if ($opc == 'buscar') {
		$where .= " AND (cobro.estado IS NULL OR tramite.id_cobro = '$id_cobro')";
	} else {
		$where .= " AND tramite.id_cobro = '$id_cobro'";
	}
}

//Filtros que se mandan desde el reporte Periodico
if ($trabajo_si_no == 'SI') {
	$where .= " AND trabajo_si_no=1 ";
} else if ($trabajo_si_no == 'NO') {
	$where .= " AND trabajo_si_no=0 ";
}

if ($id_encargado_asunto) {
	$where .= " AND asunto.id_encargado = '$id_encargado_asunto' ";
}

if ($id_encargado_comercial) {
	$where .= " AND contrato.id_usuario_responsable = '$id_encargado_comercial' ";
}

if ($clientes) {
	$where .= "	AND cliente.codigo_cliente IN ('" . base64_decode($clientes) . "')";
}

if ($usuarios) {
	$where .= "	AND usuario.id_usuario IN (" . base64_decode($usuarios) . ")";
}

#TOTAL HORAS
#BUSCAR
$query = "SELECT DISTINCT SQL_CALC_FOUND_ROWS
				tramite.id_cobro,
				tramite.id_tramite,
				tramite.id_moneda_tramite,
				tramite.fecha,
				tramite.codigo_asunto,
				tramite.revisado,
				prm_moneda.simbolo as simbolo,
				asunto.codigo_cliente as codigo_cliente,
				contrato.id_moneda as id_moneda_asunto,
				asunto.id_asunto AS id,
				cobro.fecha_cobro as fecha_cobro_orden,
				IF(tramite.cobrable=1,'SI','NO') as glosa_cobrable,
				cobro.estado as estado_cobro,
				usuario.username,
				usuario.nombre,
				usuario.apellido1,
				usuario.apellido2,
				CONCAT_WS(' ',usuario.nombre,usuario.apellido1, usuario.apellido2) as usr_nombre,
				tramite.id_tramite_tipo,
				DATE_FORMAT(tramite.fecha,'%e-%c-%x') AS fecha_cobro,
				cobro.estado,
				asunto.forma_cobro,
				asunto.monto,
				asunto.glosa_asunto,
				tramite.descripcion,
				contrato.id_contrato,
				contrato.descuento,
				tramite_tipo.glosa_tramite,
				tramite.tarifa_tramite,
				tramite.id_moneda_tramite_individual,
				tramite.tarifa_tramite_individual,
				tramite.duracion,
				prm_idioma.codigo_idioma,
				tramite.cobrable

	            FROM tramite

				JOIN asunto ON tramite.codigo_asunto = asunto.codigo_asunto
				LEFT JOIN prm_idioma ON prm_idioma.id_idioma = asunto.id_idioma
				JOIN contrato ON asunto.id_contrato = contrato.id_contrato
				JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
				JOIN tramite_tipo ON tramite.id_tramite_tipo=tramite_tipo.id_tramite_tipo
				LEFT JOIN cobro ON tramite.id_cobro = cobro.id_cobro
				LEFT JOIN usuario ON tramite.id_usuario = usuario.id_usuario
				LEFT JOIN prm_moneda ON contrato.id_moneda = prm_moneda.id_moneda

			WHERE $where ";

if ($check_tramite == 1 && isset($cobro) && !$excel) { //check_tramite vale 1 cuando aprietan boton buscar
	$query2 = "UPDATE tramite SET id_cobro = NULL WHERE id_cobro='$id_cobro'";
	$resp = mysql_query($query2, $sesion->dbh) or Utiles::errorSQL($query2, __FILE__, __LINE__, $sesion->dbh);
	$lista_tramites = new ListaTramites($sesion, '', $query);
	for ($x = 0; $x < $lista_tramites->num; $x++) {
		$tramite = $lista_tramites->Get($x);
		$emitir_tramite = new Tramite($sesion);
		$emitir_tramite->Load($tramite->fields['id_tramite']);
		$emitir_tramite->Edit('id_cobro', $id_cobro);
		$emitir_tramite->Write();
	}
}
//Se hace la lista para la edici�n de TODOS los trabajos del query
$lista_tramites = new ListaTramites($sesion, '', $query);
$ids_listado_tramites = "";

for ($x = 0; $x < $lista_tramites->num; $x++) {
	$tramite = $lista_tramites->Get($x);
	$ids_listado_tramites.="t" . $tramite->fields['id_tramite'];
}
if ($orden == "") {

	$orden = Conf::GetConf($sesion,'OrdenRevisarTramites');

	// Se intenta seguir la l�gica de negocio anterior.
	if ($orden != "cliente.glosa_cliente ASC, tramite.fecha ASC") {

		if ($opc_orden == 'edit') {
			$orden = "tramite.fecha_modificacion DESC";
		} else {
			$orden = "tramite.fecha DESC, tramite.descripcion";
		}

	}
}

if (stristr($orden, ".") === FALSE) {
	$orden = str_replace("codigo_asunto", "a1.codigo_asunto", $orden);
}

$x_pag = 15;
$b = new Buscador($sesion, $query, "Tramite", $desde, $x_pag, $orden);
$b->mensaje_error_fecha = "N/A";
$b->nombre = "busc_gastos";
$b->titulo = __('Listado de') . ' ' . __('tr�mites');
$b->AgregarFuncion("Editar", 'Editar', "align=center nowrap");
$b->AgregarEncabezado("tramite_tipo.glosa_tramite", __('Descrip.'), "align=center");
$b->AgregarEncabezado("tramite.fecha", __('Fecha'));
$b->AgregarEncabezado("cliente.glosa_cliente,asunto.codigo_asunto", __('Cliente/Asunto'), "align=center");

if ($p_revisor) {
	$b->AgregarEncabezado("tramite.cobrable", __('Cobrable'), "align=center");
}

if ($p_revisor) {
	$glosa_duracion = __('Hrs Trab.');
} else {
	$glosa_duracion = __('Hrs trab.');
}

$b->AgregarEncabezado("duracion", $glosa_duracion, "", "", "SplitDuracion");
$b->AgregarEncabezado("tramite.id_cobro", __('Cobro'), "align=center");

if ($p_revisor || strlen($select_usuario) > 164) {
	$b->AgregarEncabezado("usr_nombre", __('Usuario'), "align=center");
}

if ($p_revisor || $p_adm->fields['permitido']) {
	$b->AgregarEncabezado("tramite.tarifa_tramite", __('Tarifa'), "align=center");
}

$b->AgregarFuncion("Opc.", 'Opciones', "align=center nowrap");
$b->color_mouse_over = "#bcff5c";
$b->funcionTR = "funcionTR";

if ($excel) {

	$b1 = new Buscador($sesion, $query, "Trabajo", $desde, '', $orden);
	$lista = $b1->lista;

	if ($p_cobranza && Conf::GetConf($sesion, 'CobranzaExcel')) {
		require_once('cobros_generales_tramites.xls.php');
	} else {
		require_once('cobros3_tramites.xls.php');
	}
	exit;
}

$pagina->titulo = __('Listado de tr�mites');
$pagina->PrintTop($popup);
?>

<script type="text/javascript">

	function GrabarCampo(accion, id_tramite, cobro, valor) {
		var http = getXMLHTTP();
		if (valor) {
			valor = '1';
		} else {
			valor = '0';
		}

		loading("Actualizando opciones");
		http.open('get', 'ajax_grabar_campo.php?accion=' + accion + '&id_tramite=' + id_tramite + '&id_cobro=' + cobro + '&valor=' + valor);
		http.onreadystatechange = function() {
			if (http.readyState == 4) {
				var response = http.responseText;
				var update = new Array();
				if (response.indexOf('OK') == -1) {
					alert(response);
				}
				offLoading();
			}
		};
		http.send(null);
	}

	function Refrescar() {
		document.form_buscador.submit();
	}


	function AgregarNuevo(name) {
		var usuario = jQuery('#id_usuario').length > 0 ? jQuery('#id_usuario').val() : <?php echo $sesion->usuario->fields[id_usuario]; ?>;

		<?php if (Conf::GetConf($sesion, 'CodigoSecundario')) {	?>
			var cliente = jQuery('#codigo_cliente_secundario').val();
			var asunto = jQuery('#codigo_asunto_secundario').val();
			urlo = 'ingreso_tramite.php?popup=1&codigo_cliente_secundario=' + cliente + '&codigo_asunto_secundario=' + asunto + '&id_usuario=' + usuario;

		<?php } else { 	?>
			var cliente = jQuery('#codigo_cliente').val();
			var asunto = jQuery('#codigo_asunto').val();
			urlo = 'ingreso_tramite.php?popup=1&codigo_cliente=' + cliente + '&codigo_asunto=' + asunto + '&id_usuario=' + usuario;

		<?php } ?>

		nuovaFinestra('Agregar_Tramite', 750, 470, urlo, 'top=100, left=125');
	}


	function EliminaTramite(id) {
		jQuery('#accion').val('eliminar');
		jQuery('#id_tramite').val(id);
		jQuery('#form_tramites').submit();
		return true;
	}

	function GuardarCampoTrabajo(id, campo, valor) {
		var http = getXMLHTTP();
		var url = '_ajax.php?accion=actualizar_trabajo&id=' + id + '&campo=' + campo + '&valor=' + valor;

		loading("Actualizando campo");
		http.open('get', url);
		http.onreadystatechange = function()
		{
			if (http.readyState == 4)
			{
				var response = http.responseText;

				offLoading();
			}
		};
		http.send(null);
	}

	// Basado en http://snipplr.com/view/1696/get-elements-by-class-name/
	function getElementsByClassName(classname) {
		node = document.getElementsByTagName("body")[0];
		var a = [];
		var re = new RegExp('\\b' + classname + '\\b');
		var els = node.getElementsByTagName("*");
		for (var i = 0, j = els.length; i < j; i++)
			if (re.test(els[i].className))
				a.push(els[i]);
		return a;
	}
	// Funci�n para seleccionar todos las filas para editar, basada en la de phpMyAdmin
	function seleccionarTodo(valor) {
		var rows = getElementsByClassName('buscador')[0].getElementsByTagName('tr');
		var checkbox;
		// Se selecciona fila por medio porque cada trabajo ocupa dos filas de la tabla y el checkbox para editar est� en la primera fila de cada trabajo.
		for (var i = 0; i < rows.length; i++)
		{
			checkbox = rows[i].getElementsByTagName('input')[0];
			if (checkbox && checkbox.type == 'checkbox' && checkbox.disabled == false) {
				checkbox.checked = valor;
			}
		}
		return true;
	}

	// Encuentra los id de los trabajos seleccionados para editar, depende del id del primer <tr> que contiene al trabajo.
	// Los id quedan en un string separados por el caracter 't'.
	function getIdTrabajosSeleccionados() {
		var rows = getElementsByClassName('buscador')[0].getElementsByTagName('tr');
		var checkbox;
		var ids = '';
		// Se revisa fila por medio porque cada trabajo ocupa dos filas de la tabla y el checkbox para editar est� en la primera fila de cada trabajo.
		for (var i = 0; i < rows.length; i++)
		{
			checkbox = rows[i].getElementsByTagName('input')[0];

			if (checkbox)
			{
				if (checkbox.checked == true) {
					ids += rows[i].id;
				}
			}
		}
		return ids;
	}
	// Intenta editar m�ltiples trabajos, genera un error si no hay trabajos seleccionados.
	function editarMultiplesArchivos() {
		// Los id de los trabajos seleccionados est�n en un solo string separados por el caracter 't'.
		// La p�gina editar_multiples_trabajos.php se encarga de parsear este string.
		var ids = getIdTrabajosSeleccionados();
		if (ids != '') {
			nuovaFinestra('Editar_m�ltiples_tr�mites', 700, 450, 'editar_multiples_tramites.php?ids=' + ids + '&popup=1', '');
		} else {
			alert('Debe seleccionar por lo menos un trabajo para editar.');
		}
	}
</script>


<form method='post' name="form_tramites" id="form_tramites">
	<input type='hidden' name='opc' id='opc' value='buscar'>
	<input type='hidden' name='accion' id='accion'>
	<input type='hidden' name='id_tramite' id='id_tramite'>
	<input type='hidden' name='id_cobro' id='id_cobro' value='<?php echo $id_cobro ?>'>
	<input type='hidden' name='popup' id='popup' value='<?php echo $popup ?>'>
	<input type='hidden' name='motivo' id='motivo' value='<?php echo $motivo ?>'>
	<input type='hidden' name='check_tramite' id='check_tramite' value=''>
	<!-- Calendario DIV -->
	<div id="calendar-container" style="width:221px; position:absolute; display:none;">
		<div class="floating" id="calendar"></div>
	</div>

	<?php
	if ($motivo != "cobros") {
		if (Conf::GetConf($sesion, 'UsaDisenoNuevo'))
			$width_tabla = 'width="90%"';
		else
			$width_tabla = 'width="100%"';
		?>
		<!-- Fin calendario DIV -->
		<center>
			<table <?php echo $width_tabla ?>>
				<tr>
					<td>
						<fieldset class="tb_base" style="border: 1px solid #BDBDBD;" width="100%">
							<legend><?php echo __('Filtros') ?></legend>
							<table style="border: 0px solid black;" >
								<?php if ($p_revisor) { ?>
									<tr>
										<td class="buscadorlabel">
											<?php echo __('Trabajo') ?>
										</td>
										<td align="left" colspan="3">
											<?php echo Html::SelectQuery($sesion, "SELECT codigo_si_no, codigo_si_no FROM prm_si_no ORDER BY id_codigo_si_no", "trabajo_si_no", $trabajo_si_no, '', 'Todos', '60') ?>
										</td>
									</tr>
								<?php }	?>
								<tr>
									<td class="buscadorlabel">
										<?php echo __('Nombre Cliente') ?>
									</td>
									<td nowrap align="left" colspan="3">
										<?php UtilesApp::CampoCliente($sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario); ?>
									</td>
								</tr>

								<tr>
									<td class="buscadorlabel">
										<?php echo __('Asunto') ?>
									</td>
									<td nowrap align="left" colspan="3">
										<?php UtilesApp::CampoAsunto($sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario, 320, '', $glosa_asunto, false); ?>
									</td>
								</tr>

								<tr>
									<td class="buscadorlabel">
										<?php echo __('Responsable Asunto'); ?>
									</td>
									<td align="left" colspan="3"><!-- Nuevo Select -->
										<?php echo $Form->select('id_encargado_asunto', $sesion->usuario->ListarActivos('', true), $id_encargado_asunto, array('empty' => 'Todos', 'style' => 'width: 200px')); ?>
									</td>
								</tr>

								<tr>
									<td class="buscadorlabel">
										<?php echo __('Encargado Comercial') ?>
									</td>
									<td align="left" colspan="3"><!-- Nuevo Select -->
										<?php echo $Form->select('id_encargado_comercial', $sesion->usuario->ListarActivos('', 'SOC'), $id_encargado_comercial, array('empty' => 'Todos', 'style' => 'width: 200px')); ?>
									</td>
								</tr>

								<tr>
									<td class="buscadorlabel">
										<?php echo __('Usuario') ?>
									</td>
									<td align="left" colspan="3"><!-- Nuevo Select -->
										<?php
										$where_usuario = '';

										if (! $p_revisor) {
											$where_usuario = "AND {$sesion->usuario->tabla}.id_usuario IN (SELECT id_revisado FROM usuario_revisor WHERE id_revisor={$sesion->usuario->fields['id_usuario']}) OR usuario.id_usuario={$sesion->usuario->fields['id_usuario']}";
										}

										echo $Form->select('id_usuario', $sesion->usuario->ListarActivos($where_usuario, 'PRO'), $id_usuario, array('empty' => 'Todos', 'style' => 'width: 200px'));
										?>
									</td>
								</tr>
								<?php
								### Validando fecha
								$hoy = date('Y-m-d');
								$fecha_ini = Utiles::sql2date($fecha_ini);
								$fecha_fin = Utiles::sql2date($fecha_fin);
								?>
								<tr>
									<td class="buscadorlabel">
										<?php echo __('Fecha desde') ?>:
									</td>
									<td align="left">
										<input type="text" name="fecha_ini" class="fechadiff" value="<?php echo $fecha_ini ?>" id="fecha_ini" size="11" maxlength="10" />
									</td>
									<td class="buscadorlabel">
										<?php echo __('Fecha hasta') ?>:
									</td>
									<td>
										<input type="text" name="fecha_fin" class="fechadiff" value="<?php echo $fecha_fin ?>" id="fecha_fin" size="11" maxlength="10" />
									</td>
								</tr>
								<tr>
									<td>&nbsp;</td>
									<td colspan="2" align="left">
										<?php echo $Form->submit(__('Buscar'), array('onclick' => "jQuery('#check_tramite').val(1)")); ?>
									</td>
									<td align="right">
										<?php echo $Form->icon_button(__('Agregar') . ' ' . __('tr�mite'), 'agregar', array('onclick' => "AgregarNuevo('tramite')")); ?>
									</td>
								</tr>
							</table>
						</fieldset>
					</td>
				</tr>
			</table>
		</center>
	<?php }	?>
</form>

<?php
echo $Form->script();
if (isset($cobro) || $opc == 'buscar') {
	echo "<center>";
	$b->Imprimir('', array('check_tramite')); //Excluyo Checktramite);
	?>
	<a href="#" onclick="seleccionarTodo(true);
					return false;">Seleccionar todo</a>
	&nbsp;&nbsp;&nbsp;&nbsp;
	<a href="#" onclick="seleccionarTodo(false);
					return false;">Desmarcar todo</a>
	&nbsp;&nbsp;&nbsp;&nbsp;
	<a href="#" onclick="editarMultiplesArchivos();
					return false;" title="Editar m�ltiples tr�mites">Editar seleccionados</a>
	<br />

	<a href="#" onclick="nuovaFinestra('Editar_listado_tr�mites', 700, 450, 'editar_multiples_tramites.php?ids=<?php echo $ids_listado_tramites ?>&popup=1&listado_completo=1', '');" title="Editar trabajos de todo el listado">Editar trabajos de todo el listado</a>

	<br />
	<input type="button" class="btn" value="<?php echo __('Descargar listado a Excel') ?>" onclick="window.open('listar_tramites.php?id_cobro=<?php echo $id_cobro ?>&excel=1&motivo=<?php echo $motivo ?>&where=<?php echo urlencode(base64_encode($where)) ?>')">
	<br />
	</center>
		<!--<input type=button class=btn value="<?php echo __('Descargar Archivo a Word') ?>" onclick="window.open('trabajos.php?id_cobro=<?php echo $id_cobro ?>&word=1&motivo=<?php echo $motivo ?>&where=<?php echo urlencode(base64_encode($where)) ?>')">-->
	<?php
}

function Cobrable(& $fila) {
	global $id_cobro;

	if ($fila->fields['id_cobro'] == $id_cobro) {
		$checked = "checked";
	} else {
		$checked = "";
	}

	$Check = "<input type='checkbox' $checked onclick=GrabarCampo('cobrar_tramite','" . $fila->fields['id_tramite'] . "',$id_cobro,'');>";
	return $Check;
}

function Revisado(& $fila) {
	if ($fila->fields['revisado'] == 1) {
		$checked = "checked";
	} else {
		$checked = "";
	}

	$Check = "<input type='checkbox' $checked onmouseover=\"ddrivetip('Para marcar un tr�mite como revisado haga click aqu�.&lt;br&gt;Los tr�mites revisados no se desplegar�n en este listado la pr�xima vez.')\" onmouseout=\"hideddrivetip();\" onchange=\"GuardarCampoTrabajo(" . $fila->fields['id_trabajo'] . ",'revisado',this.checked ? 1 : 0)\">";
	return $Check;
}

function Opciones(& $tramite) {
	$img_dir = Conf::ImgDir();
	global $motivo;
	$id_cobro = $tramite->fields['id_cobro'];
	global $sesion;
	global $p_profesional;
	global $p_revisor;

	$cobro = new Cobro($sesion);
	$cobro->Load($id_cobro);

	if ($motivo == 'cobros') {
		$opc_html = Cobrable($tramite);
	}

	if ($p_revisor) {

		if ($cobro->fields['estado'] == 'CREADO' || $cobro->fields['estado'] == 'EN REVISION' || empty($tramite->fields['id_cobro'])) {
			$opc_html.= "<a href=# onclick=\"nuovaFinestra('Editar_Tr�mite',650,450,'ingreso_tramite.php?id_cobro=" . $id_cobro . "&id_tramite=" . $tramite->fields['id_tramite'] . "&popup=1&opcion=edit','');\" title=" . __('Editar') . "><img src=$img_dir/editar_on.gif border=0></a>";
		} else {
			$opc_html.= "<a href=# onclick=\"alert('" . __('No se puede modificar este tr�mite.\nEl Cobro que lo incluye ya ha sido Emitido al Cliente.') . "');\" title=\"" . __('Cobro ya Emitido al Cliente') . "\"><img src=$img_dir/editar_off.gif border=0></a>";
		}

		if ( Conf::GetConf($sesion, 'UsaDisenoNuevo') ) {
			if ($cobro->fields['estado'] == 'CREADO' || $cobro->fields['estado'] == 'EN REVISION' || empty($tramite->fields['id_cobro'])) {
				$opc_html.= "<a href='javascript:void(0);' onclick=\"if (confirm('�" . __('Est&aacute; seguro de eliminar el') . " " . __('tr�mite') . "?'))EliminaTramite(" . $tramite->fields['id_tramite'] . ");\"><img src='" . Conf::ImgDir() . "/cruz_roja_nuevo.gif' border=0 alt='Eliminar' /></a>";
			} else {
				$opc_html.= "<a href=# onclick=\"alert('" . __('No se puede eliminar este tr�mite.\nEl Cobro que lo incluye ya ha sido Emitido al Cliente.') . "');\" title=\"" . __('Cobro ya Emitido al Cliente') . "\"><img src='" . Conf::ImgDir() . "/cruz_roja_nuevo.gif' border=0 alt='Eliminar' /></a>";
			}
		} else {
			if ($cobro->fields['estado'] == 'CREADO' || $cobro->fields['estado'] == 'EN REVISION' || empty($tramite->fields['id_cobro'])) {
				$opc_html.= "<a href='javascript:void(0);' onclick=\"if (confirm('�" . __('Est&aacute; seguro de eliminar el') . " " . __('tr�mite') . "?'))EliminaTramite(" . $tramite->fields['id_tramite'] . ");\"><img src='" . Conf::ImgDir() . "/cruz_roja.gif' border=0 alt='Eliminar' /></a>";
			} else {
				$opc_html.= "<a href=# onclick=\"alert('" . __('No se puede eliminar este tr�mite.\nEl Cobro que lo incluye ya ha sido Emitido al Cliente.') . "');\" title=\"" . __('Cobro ya Emitido al Cliente') . "\"><img src='" . Conf::ImgDir() . "/cruz_roja.gif' border=0 alt='Eliminar' /></a>";
			}
		}

	} elseif ($p_profesional) {

		if ($tramite->Estado() == 'Revisado') {
			$opc_html .= "<img src=$img_dir/candado_16.gif border=0 title='" . __('Este trabajo ya ha sido revisado') . "'>";
		} else {

			if ($cobro->fields['estado'] == 'CREADO' || $cobro->fields['estado'] == 'EN REVISION' || empty($tramite->fields['id_cobro'])) {
				$opc_html.= "<a href=# onclick=\"nuovaFinestra('Editar_Tr�mite',550,450,'ingreso_tramite.php?id_cobro=" . $id_cobro . "&id_tramite=" . $tramite->fields['id_tramite'] . "&popup=1opcion=edit','');\" title=" . __('Editar') . "><img src=$img_dir/editar_on.gif border=0></a>";
			} else {
				$opc_html.= "<a href=# onclick=\"alert('" . __('No se puede modificar este tr�mite.\nEl Cobro que lo incluye ya ha sido Emitido al Cliente.') . "');\" title=\"" . __('Cobro ya Emitido al Cliente') . "\" ><img src=$img_dir/editar_off.gif border=0></a>";
			}

			if ($cobro->fields['estado'] == 'CREADO' || $cobro->fields['estado'] == 'EN REVISION' || empty($tramite->fields['id_cobro'])) {
				$opc_html.= "<a href='javascript:void(0);' onclick=\"if (confirm('�" . __('Est&aacute; seguro de eliminar el') . " " . __('tr�mite') . "?'))EliminaTramite(" . $tramite->fields['id_tramite'] . ");\"><img src='" . Conf::ImgDir() . "/cruz_roja.gif' border=0 alt='Eliminar' /></a>";
			} else {
				$opc_html.= "<a href=# onclick=\"alert('" . __('No se puede eliminar este tr�mite.\nEl Cobro que lo incluye ya ha sido Emitido al Cliente.') . "');\" title=\"" . __('Cobro ya Emitido al Cliente') . "\"><img src='" . Conf::ImgDir() . "/cruz_roja.gif' border=0 alt='Eliminar' /></a>";
			}
		}

	} else {
		$opc_html .= "<img src=$img_dir/candado_16.gif border=0 title='" . __('Usted no tiene permiso de Revisor') . "'>";
	}

	return $opc_html;
}

function SplitDuracion($time) {
	list($h, $m, $s) = explode(":", $time);
	if ($h > 0 || $s > 0) {
		return $h . ":" . $m;
	}

}

function funcionTR(& $tramite) {
	global $sesion;
	global $id_cobro;
	global $p_revisor;
	global $p_cobranza;
	global $select_usuario;
	static $i = 0;

	if ($i % 2 == 0) {
		$color = "#dddddd";
	} else {
		$color = "#ffffff";
	}

	$idioma = new Objeto($sesion, '', '', 'prm_idioma', 'codigo_idioma');

	if ($tramite->fields['codigo_idioma'] != '') {
		$idioma->Load($tramite->fields['codigo_idioma']);
	} else {
		$idioma->Load(strtolower(Conf::GetConf($sesion, 'Idioma')));
	}

	if ($tramite->fields['tarifa_tramite_individual'] > 0) {
		$tarifa = $tramite->fields['tarifa_tramite_individual'];
	} else {
		$tarifa = $tramite->fields['tarifa_tramite'];
	}

	list($h, $m, $s) = explode(":", $tramite->fields['duracion_defecto']);

	$duracion = $h + ($m > 0 ? ($m / 60) : '0');
	$total = round($tarifa, 2);
	$total_horas += $duracion;
	$queryformato = "SELECT pi.formato_fecha FROM prm_idioma pi JOIN cobro c ON (  pi.codigo_idioma = c.codigo_idioma) WHERE c.id_cobro='" . $id_cobro . "' LIMIT 1";
	$formato_fecha = UtilesApp::ObtenerFormatoFecha($sesion);

	$fecha = Utiles::sql2fecha($tramite->fields['fecha'], $formato_fecha);
	$html .= "<tr id=\"t" . $tramite->fields[id_tramite] . "\" bgcolor=$color style=\"border-right: 1px solid #409C0B; border-left: 1px solid #409C0B; border-bottom: 1px solid #409C0B;\">";
	$html .= '<td><input type="checkbox" onmouseover="ddrivetip(\'Para editar m�ltiples tr�mites haga click aqu�.\')" onmouseout="hideddrivetip();" ></td>';
	$glosa_tramite = $tramite->fields['glosa_tramite'];
	$descripcion = $tramite->fields['descripcion'];

	if (strlen($glosa_tramite) > 18 && strlen($descripcion) > 18) {
		$html .= "<td width=120 nowrap><div onmouseover=\"ddrivetip('<b>$glosa_tramite</b><br>$descripcion');\" onmouseout=\"hideddrivetip();\" style=\"max-width: 100px;\"><strong>" . substr($glosa_tramite, 0, 16) . "..</strong><br>" . substr($descripcion, 0, 16) . "..</div></td>";
	} else if (strlen($glosa_tramite) > 18) {
		$html .= "<td width=120 nowrap><div onmouseover=\"ddrivetip('<b>$glosa_tramite</b><br>$descripcion');\" onmouseout=\"hideddrivetip();\" style=\"max-width: 100px;\"><strong>" . substr($glosa_tramite, 0, 16) . "..</strong><br>" . $descripcion . "</div></td>";
	} else if (strlen($descripcion) > 18) {
		$html .= "<td width=120 nowrap><div onmouseover=\"ddrivetip('<b>$glosa_tramite</b><br>$descripcion');\" onmouseout=\"hideddrivetip();\" style=\"max-width: 100px;\"><strong>" . $glosa_tramite . "</strong><br>" . substr($descripcion, 0, 16) . "..</div></td>";
	} else{
		$html .= "<td width=120 nowrap><div style=\"max-width: 100px;\"><strong>" . $glosa_tramite . "</strong><br>" . $descripcion . "</div></td>";
	}

	$html .= "<td>$fecha</td>";

	$codigo_cliente = $tramite->fields['codigo_cliente'];
	$cliente = new Cliente($sesion);
	$cliente->LoadByCodigo($codigo_cliente);

	$html .= "<td><b>" . $cliente->fields['glosa_cliente'] . "</b><br>" . $tramite->fields['glosa_asunto'] . "</td>";

	if ($p_revisor) {
		if ($tramite->fields['cobrable'] == 1) {
			$html .= "<td align=center>SI</td>";
		} else {
			$html .= "<td align=center>NO</td>";
		}
	}

	$duracion = $tramite->fields['duracion'];

	if (!$p_revisor) {
		$duracion_trabajada = $tramite->fields['duracion'];

		$duracion = $duracion_trabajada;
		if (Conf::GetConf($sesion, 'TipoIngresoHoras') == 'decimal') {
			$duracion = UtilesApp::Time2Decimal($duracion_trabajada);
		}
	} else {
		if (Conf::GetConf($sesion, 'TipoIngresoHoras') == 'decimal') {
			$duracion_trabajada = $tramite->fields['duracion'];
			$duracion = UtilesApp::Time2Decimal($duracion_trabajada);
		}
	}
	//echo $duracion."fin<br>";
	if ($p_cobranza) {
		$editar_cobro = "<a href='javascript:void(0)' onclick=\"nuevaVentana('Generar Cobro',750,660,'cobros5.php?popup=1&id_cobro=" . $tramite->fields['id_cobro'] . "');\"'>" . $tramite->fields['id_cobro'] . "</a>";
	} else {
		$editar_cobro = $tramite->fields['id_cobro'];
	}

	$id_tramite = $tramite->fields['id_moneda_tramite'];
	if (!empty($tramite->fields['id_moneda_tramite_individual']) && $tramite->fields['id_moneda_tramite_individual'] != $tramite->fields['id_moneda_tramite']) {
		$id_tramite = $tramite->fields['id_moneda_tramite_individual'];
	}

	$moneda_tramite = new Moneda($sesion);
	if ($tramite->fields['tarifa_tramite_individual'] > 0) {
		$moneda_tramite->Load($tramite->fields['id_moneda_tramite_individual']);
	} else {
		$moneda_tramite->Load($tramite->fields['id_moneda_tramite']);
	}

	$html .= "<td align=center>" . $duracion . "</td>";
	$html .= "<td>" . $editar_cobro . "</td>";
	if ($p_revisor || strlen($select_usuario) > 164) {
		if (Conf::GetConf($sesion, 'UsaUsernameEnTodoElSistema')) {
			$html .= "<td align=center>" . $tramite->fields['username'] . "</td>";
		} else {
			$html .= "<td align=center>" . substr($tramite->fields['nombre'], 0, 1) . substr($tramite->fields['apellido1'], 0, 1) . substr($tramite->fields['apellido2'], 0, 1) . "</td>";
		}
	}
	if ($p_revisor || $p_adm->fields['permitido']) {
		//$html .= "<td align=center><strong>" . __('Tarifa') . "</strong><br>" . $moneda_tramite->fields['codigo'] . " " . number_format($tarifa, $moneda_tramite->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . "</td>";
		$html .= "<td align=center><strong>" . __('Tarifa') . "</strong><br>" . $moneda_tramite->fields['simbolo'] . " " . number_format($tarifa, $moneda_tramite->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . "</td>";
	}
	$html .= '<td align=center nowrap>' . Opciones($tramite) . '</td>';
	$html .= "</tr>";

	$i++;
	return $html;
}

$pagina->PrintBottom($popup);

