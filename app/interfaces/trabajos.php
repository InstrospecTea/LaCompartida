<?php

require_once dirname(dirname(__FILE__)).'/conf.php';

$sesion = new Sesion(array('PRO', 'REV', 'ADM', 'COB', 'SEC'));
$pagina = new Pagina($sesion);
$Form = new Form;

$params_array['codigo_permiso'] = 'REV';
$p_revisor = $sesion->usuario->permisos->Find('FindPermiso', $params_array);

$params_array['codigo_permiso'] = 'COB';
$p_cobranza = $sesion->usuario->permisos->Find('FindPermiso', $params_array);

$query_usuario = "SELECT usuario.id_usuario, CONCAT_WS(' ', apellido1, apellido2,',',nombre) as nombre FROM usuario
			JOIN usuario_permiso USING(id_usuario) WHERE codigo_permiso='SOC' ORDER BY nombre";

if ($p_cobranza->fields['permitido']) {
	$p_revisor->fields['permitido'] = true;
}

$params_array['codigo_permiso'] = 'PRO';
$p_profesional = $sesion->usuario->permisos->Find('FindPermiso', $params_array);

if ($motivo == 'cobros' && $id_cobro) {
	$cobro = new Cobro($sesion);

	if (!$cobro->Load($id_cobro)) {
		$pagina->FatalError(__('Cobro inválido'));
	}

	if ($opc != 'buscar') {

		if ($fecha_ini == '' || $fecha_ini == '00-00-0000' || $fecha_ini == NULL) {
			$fecha_ini = Utiles::sql2date($cobro->fields['fecha_ini']);
		}

		if ($fecha_fin == '' || $fecha_fin == '00-00-0000' || $fecha_fin == NULL) {
			$fecha_fin = Utiles::sql2date($cobro->fields['fecha_fin']);
		}
	}
}

if ($p_revisor->fields['permitido'] && $accion == "eliminar") {
	$trabajo = new Trabajo($sesion);
	$trabajo->Load($id_trabajo);

	if (!$trabajo->Eliminar()) {
		$pagina->AddError($asunto->error);
	} else {
		$pagina->AddInfo(__('Trabajo') . ' ' . __('eliminado con éxito'));
	}
}

// Seteando FECHAS a formato SQL
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

// Si estamos en un cobro
if ($cobro) {
	//Significa que se apreto el boton buscar asi que hay que considerarlas nuevas fechas
	if ($opc == "buscar" && (isset($check_trabajo) && $check_trabajo == '1')) {
		if ($fecha_ini != '0000-00-00' && $fecha_ini != '') {
			$cobro->Edit('fecha_ini', $fecha_ini);
		} else {
			$cobro->Edit('fecha_ini', NULL);
		}

		if ($fecha_fin != '0000-00-00' && $fecha_fin != '') {
			$cobro->Edit('fecha_fin', $fecha_fin);
		} else {
			$fecha_hoy = date("Y-m-d", time());
			$cobro->Edit('fecha_fin', $fecha_hoy);
		}

		$cobro->Write();
	} else {
		// En caso de que no estoy buscando debo setear fecha ini y fecha fin
		$fecha_ini = $cobro->fields['fecha_ini'];
		$fecha_fin = $cobro->fields['fecha_fin'];
	}
}

// Calculado aquÃ­ para que la variable $select_usuario estÃ© disponible al generar la tabla de trabajos.
if ($p_revisor->fields['permitido']) {
	$where_usuario = '';
} else {
	$where_usuario = "AND (usuario.id_usuario IN (SELECT id_revisado FROM usuario_revisor WHERE id_revisor=" . $sesion->usuario->fields[id_usuario] . ") OR usuario.id_usuario=" . $sesion->usuario->fields[id_usuario] . ")";
}

$select_usuario = Html::SelectQuery($sesion, "SELECT usuario.id_usuario, CONCAT_WS(' ',usuario.apellido1,usuario.apellido2,',',usuario.nombre) AS nombre FROM usuario JOIN usuario_permiso USING(id_usuario) WHERE usuario.visible = 1 AND usuario_permiso.codigo_permiso='PRO' " . $where_usuario . " ORDER BY nombre ASC", "id_usuario", $id_usuario, '', 'Todos', '200');

if (isset($cobro) || $opc == 'buscar' || $excel || $excel_agrupado) {
	$where = base64_decode($where);
	$where_gastos = " 1 ";
	$where_gastos .= " AND cta_corriente.incluir_en_cobro = 'SI' AND cta_corriente.cobrable = 1 ";
	if ($where == '') {
		$where = 1;
	}
	if ($id_usuario != '') {
		$where .= " AND trabajo.id_usuario= " . $id_usuario;
	} else if (!$p_revisor->fields['permitido']) {
		// Se buscan trabajos de los usuarios a los que se puede revisar.
		$where .= " AND (usuario.id_usuario IN (SELECT id_revisado FROM usuario_revisor WHERE id_revisor=" . $sesion->usuario->fields[id_usuario] . ") OR usuario.id_usuario=" . $sesion->usuario->fields[id_usuario] . ") ";
	}

	if ($revisado == 'NO') {
		$where.= " AND trabajo.revisado = 0 ";
	}
	if ($revisado == 'SI') {
		$where.= " AND trabajo.revisado = 1 ";
	}
	if ($codigo_asunto != '' || $codigo_asunto_secundario != "") {
		if (Conf::GetConf($sesion, 'CodigoSecundario')) {
			$where.= " AND asunto.codigo_asunto_secundario = '$codigo_asunto_secundario' ";
		} else {
			$where.= " AND trabajo.codigo_asunto = '$codigo_asunto' ";
		}
	}
	if ($cobrado == 'NO') {
		$where .= " AND ( trabajo.id_cobro is null OR cobro.estado = 'CREADO' OR cobro.estado = 'EN REVISION' ) ";
	}
	if ($cobrado == 'SI') {
		$where .= " AND trabajo.id_cobro is not null AND (cobro.estado != 'CREADO' AND cobro.estado != 'EN REVISION') ";
	}

	if ($from == 'reporte') {
		if ($id_cobro) {
			$where .= " AND trabajo.id_cobro = $id_cobro ";
		}

		if ($mes) {
			$where .= " AND DATE_FORMAT(trabajo.fecha, '%m-%y') = '$mes' ";
		}

		if ($cobro_nulo) {
			$where .= " AND trabajo.id_cobro IS NULL ";
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
	if ($codigo_cliente != "" || $codigo_cliente_secundario != "") {
		if (Conf::GetConf($sesion, 'CodigoSecundario')) {
			$where .= " AND cliente.codigo_cliente_secundario ='$codigo_cliente_secundario' ";
		} else {
			$where .= " AND cliente.codigo_cliente ='$codigo_cliente' ";
		}
	}

	// SQL FECHAS
	if ($fecha_ini != '' and $fecha_ini != 'NULL' and $fecha_ini != '0000-00-00') {
		$where .= " AND trabajo.fecha >= '" . $fecha_ini . "' ";
		$where_gastos .= " AND cta_corriente.fecha >= '" . $fecha_ini . "' ";
	}

	if ($fecha_fin != '' and $fecha_fin != 'NULL' and $fecha_fin != '0000-00-00') {
		$where .= " AND trabajo.fecha <= '" . $fecha_fin . "' ";
		$where_gastos .= " AND cta_corriente.fecha <= '" . $fecha_fin . "' ";
	}

	if (isset($cobro)) { // Es decir si es que estoy llamando a esta pantalla desde un cobro
		$cobro->LoadAsuntos();
		$query_asuntos = implode("','", $cobro->asuntos);
		$id_contrato=$cobro->fields['id_contrato'];

		  if(count($cobro->asuntos)>0) {
		      $where .= " AND trabajo.codigo_asunto IN ('$query_asuntos')";// or contrato.id_contrato='$id_contrato')";
		      $where_gastos .= " AND cta_corriente.codigo_asunto IN ('$query_asuntos')";// or contrato.id_contrato='$id_contrato')";
		    } else {
		      $where .= " AND   cobro.id_contrato='$id_contrato'";
		      $where_gastos .= " AND   cobro.id_contrato='$id_contrato'";
		    }

		//$where .= " AND trabajo.cobrable = 1";
		if ($opc == 'buscar') {
			$where .= " AND (cobro.estado IS NULL OR trabajo.id_cobro = '$id_cobro')";
			$where_gastos.= " AND (cobro.estado IS NULL OR cta_corriente.id_cobro = '$id_cobro')";
		} else {
			$where .= " AND trabajo.id_cobro = '$id_cobro'";
			$where_gastos .= " AND cta_corriente.id_cobro = '$id_cobro'";
		}
	//echo '<pre> ';print_r($cobro);echo '</pre>';
		//para tema de los gastos que se preseleccionaran para cobro4.php
		$codigo_cliente = $cobro->fields['codigo_cliente'];
	} else if ($query_asuntos) { // FFF si viene seteado el codigo de asunto, lo mantengo

			if($id_contrato) {
				$where .= " AND (trabajo.codigo_asunto IN ('$query_asuntos') or cobro.id_contrato='$id_contrato')";
				$where_gastos .= " AND (cta_corriente.codigo_asunto IN ('$query_asuntos') or cobro.id_contrato='$id_contrato')";
			} else {
				$where .= " AND trabajo.codigo_asunto IN ('$query_asuntos') ";
				$where_gastos .= " AND cta_corriente.codigo_asunto IN ('$query_asuntos') ";
			}

		//$where .= " AND trabajo.cobrable = 1";

		if ($id_cobro) {
			$where .= " AND (cobro.estado IS NULL OR trabajo.id_cobro = '$id_cobro')";
			$where_gastos.= " AND (cobro.estado IS NULL OR cta_corriente.id_cobro = '$id_cobro')";
		}  else {
			$where .= " AND cobro.estado IS NULL";
			$where_gastos .= " AND cobro.estado IS NULL";
		}
	}

	if ($buscar_id_cobro){
			$where .= " AND trabajo.id_cobro='$buscar_id_cobro'";
			$where_gastos .= " AND cta_corriente.id_cobro='$buscar_id_cobro'";
		}

	$where_gasto .= " AND cta_corriente.codigo_asunto IN ('$query_asuntos') ";

	if ($cobrable == 'SI') {
		$where .= " AND trabajo.cobrable = 1";
	}
	if ($cobrable == 'NO') {
		$where .= " AND trabajo.cobrable <> 1";
	}

	//Filtros que se mandan desde el reporte Periodico
	if ($id_grupo) {
		if ($id_grupo == 'NULL') {
			$where .= " AND cliente.id_grupo_cliente IS NULL";
		} else {
			$where .= " AND cliente.id_grupo_cliente = $id_grupo";
		}
	}

	if ($id_area_usuario) {
		$where .= " AND usuario.id_area_usuario = $id_area_usuario ";
	}

	if ($clientes) {
		$where .= "	AND cliente.codigo_cliente IN ('" . base64_decode($clientes) . "')";
	}

	if ($usuarios) {
		$where .= "	AND usuario.id_usuario IN (" . base64_decode($usuarios) . ")";
	}

	$where .= " AND trabajo.id_tramite = 0 ";

	if ($id_encargado_comercial) {
		$where .= " AND contrato.id_usuario_responsable = '$id_encargado_comercial' ";
	}

	// Filtro para Actividades si están activos
	if (Conf::GetConf($sesion, 'UsoActividades') && !empty($codigo_actividad)) {
		$where .= " AND actividad.codigo_actividad = '$codigo_actividad'";
	}

		$wherelocal=$where;
		 global $where, $query;
		 $where=$wherelocal;
	// TOTAL HORAS
	$query = "SELECT
					SUM(TIME_TO_SEC(if(trabajo.cobrable=1,duracion_cobrada,0)))/3600 AS total_duracion,
					SUM(TIME_TO_SEC(duracion))/3600 AS total_duracion_trabajada ";

	  	($Slim=Slim::getInstance('default',true)) ?  $Slim->applyHook('hook_query_trabajos'):false;

	$query.=" FROM trabajo
				JOIN asunto ON trabajo.codigo_asunto = asunto.codigo_asunto
				LEFT JOIN actividad ON trabajo.codigo_actividad=actividad.codigo_actividad
				LEFT JOIN cliente ON cliente.codigo_cliente=asunto.codigo_cliente
				LEFT JOIN cobro ON cobro.id_cobro=trabajo.id_cobro
				LEFT JOIN contrato ON asunto.id_contrato =contrato.id_contrato
				LEFT JOIN usuario ON trabajo.id_usuario=usuario.id_usuario
				LEFT JOIN prm_moneda ON contrato.id_moneda=prm_moneda.id_moneda
				WHERE $where ";

	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	list($total_duracion, $total_duracion_trabajada) = mysql_fetch_array($resp);

	$select_glosa_actividad = "";
	if (Conf::GetConf($sesion, 'UsoActividades')) {
		$select_glosa_actividad = ', actividad.glosa_actividad as glosa_actividad ';
	}

	$Usuario = new UsuarioExt;
	if (Conf::GetConf($sesion, 'UsaUsernameEnTodoElSistema')){
		$select_encargado_comercial = "resp_user.username AS encargado_comercial,";
	} else {
		$campo_glosa = str_replace('usuario', 'resp_user.', $Usuario->campo_glosa);
		$select_encargado_comercial = "{$campo_glosa} AS encargado_comercial,";
	}
	$select_encargado_comercial .= 'resp_user.id_usuario AS id_encargado_comercial,';

	#BUSCAR
	$usr_nombre = $Usuario->campo_glosa;
	$query = "
		SELECT  SQL_CALC_FOUND_ROWS
			trabajo.id_trabajo,
			trabajo.id_cobro,
			trabajo.revisado,
			trabajo.id_trabajo,
			trabajo.codigo_asunto,
			trabajo.cobrable,
			trabajo.solicitante,
			prm_moneda.simbolo as simbolo,
			prm_moneda.id_moneda as id_moneda,
			asunto.codigo_cliente as codigo_cliente,
			contrato.id_moneda as id_moneda_asunto,
			asunto.id_asunto AS id,
			cliente.glosa_cliente,
			trabajo.fecha_cobro as fecha_cobro_orden,
			trabajo.descripcion,
			IF( trabajo.cobrable = 1, 'SI', 'NO') as glosa_cobrable,
			trabajo.visible,
			cobro.estado as estado_cobro,
			cobro.id_moneda as id_moneda_cobro,
			contrato.id_moneda as id_moneda_contrato,
			$usr_nombre as usr_nombre,
			usuario.username,
			usuario.id_usuario,
			usuario.nombre,
			usuario.apellido1,
			CONCAT_WS('<br>',DATE_FORMAT(trabajo.duracion,'%H:%i'),
			DATE_FORMAT(duracion_cobrada,'%H:%i')) as duracion,
			TIME_TO_SEC(trabajo.duracion)/3600 as duracion_horas,
			trabajo.tarifa_hh,
			tramite_tipo.id_tramite_tipo,
			DATE_FORMAT(trabajo.fecha_cobro,'%e-%c-%x') AS fecha_cobro,
			cobro.estado,
			cliente.glosa_cliente,
			asunto.forma_cobro,
			asunto.codigo_asunto_secundario,
			asunto.monto,
			asunto.glosa_asunto,
			contrato.descuento,
			tramite_tipo.glosa_tramite,
			trabajo.fecha,
			trabajo.fecha_creacion,
			trabajo.fecha_modificacion,
			prm_idioma.codigo_idioma as codigo_idioma,
			$select_encargado_comercial
			contrato.id_tarifa
			$select_glosa_actividad ";


   	($Slim=Slim::getInstance('default',true)) ?  $Slim->applyHook('hook_query_trabajos'):false;

	$query.="
		FROM trabajo
			LEFT JOIN asunto ON trabajo.codigo_asunto = asunto.codigo_asunto
			LEFT JOIN prm_idioma ON asunto.id_idioma = prm_idioma.id_idioma
			LEFT JOIN actividad ON trabajo.codigo_actividad=actividad.codigo_actividad
			LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
			LEFT JOIN cobro ON trabajo.id_cobro = cobro.id_cobro
			LEFT JOIN contrato ON asunto.id_contrato = contrato.id_contrato
			LEFT JOIN usuario ON trabajo.id_usuario = usuario.id_usuario
			LEFT JOIN usuario AS resp_user ON resp_user.id_usuario = contrato.id_usuario_responsable
			LEFT JOIN prm_moneda ON contrato.id_moneda = prm_moneda.id_moneda
			LEFT JOIN tramite ON trabajo.id_tramite=tramite.id_tramite
			LEFT JOIN tramite_tipo ON tramite.id_tramite_tipo=tramite_tipo.id_tramite_tipo
			WHERE $where ";

	if (($excel && $simplificado) || $excel_agrupado) {
		$query = str_replace('SELECT  SQL_CALC_FOUND_ROWS', 'SELECT  SQL_BIG_RESULT SQL_NO_CACHE  ', $query);
		$query = str_replace('WHERE 1', ' join tarifa  on tarifa.tarifa_defecto=1 left join usuario_tarifa ut on  ut.id_moneda=contrato.id_moneda and ut.id_usuario=trabajo.id_usuario and ut.id_tarifa=ifnull(contrato.id_tarifa, tarifa.id_tarifa) WHERE 1  ', $query);
		$query = str_replace('FROM trabajo', ' ,ut.tarifa as tarifa2 FROM trabajo  ', $query);

		if ($excel_agrupado) {
			$ReporteTrabajoAgrupado = new ReporteTrabajoAgrupado($sesion);
			$ReporteTrabajoAgrupado->imprimir($query, $por_socio);
		} else {
			require('ajax/cobros3.simplificado.xls.php');
		}
		exit();
	}
	if ($check_trabajo == 1 && isset($cobro) && !$excel) { //Check_trabajo vale 1 cuando aprietan boton buscar
		$query2 = "UPDATE trabajo SET id_cobro = NULL WHERE id_cobro='$id_cobro'";
		$resp = mysql_query($query2, $sesion->dbh) or Utiles::errorSQL($query2, __FILE__, __LINE__, $sesion->dbh);
		$lista_trabajos = new ListaTrabajos($sesion, '', $query);

		for ($x = 0; $x < $lista_trabajos->num; $x++) {
			$trabajo = $lista_trabajos->Get($x);
			$emitir_trabajo = new Trabajo($sesion);
			$emitir_trabajo->Load($trabajo->fields['id_trabajo']);
			$emitir_trabajo->Edit('id_cobro', $id_cobro);
			$emitir_trabajo->Write();
		}

		if ($cobro->fields['incluye_gastos']) {
			$query3 = "UPDATE cta_corriente SET id_cobro = NULL WHERE id_cobro='$id_cobro'";
			$resp = mysql_query($query3, $sesion->dbh) or Utiles::errorSQL($query3, __FILE__, __LINE__, $sesion->dbh);
			$query_gastos = "SELECT id_movimiento FROM cta_corriente LEFT JOIN cobro USING( id_cobro ) WHERE $where_gastos ";
			$lista_gastos = new ListaGastos($sesion, '', $query_gastos);

			for ($x = 0; $x < $lista_gastos->num; $x++) {
				$gasto = $lista_gastos->Get($x);
				$emitir_gasto = new Gasto($sesion);
				$emitir_gasto->Load($gasto->fields['id_movimiento']);
				$emitir_gasto->Edit('id_cobro', $id_cobro);
				$emitir_gasto->Write();
			}
		}

		$cobro->GuardarCobro();
	}

	//Se hace la lista para la edición de TODOS los trabajos del query
	//A la página de editar multiples trabajos se le pasa encriptado el where
	//de esta manera no se sobrecarga esta página
	//Esta comentado hasta encontrar una buena manera de encriptarlo
	//$query_listado_completo=mcrypt_encrypt(MCRYPT_CRYPT,Conf::Hash(),$where,MCRYPT_ENCRYPT);

	if ($orden == "") {
		if (Conf::GetConf($sesion,'RevHrsClienteFecha')) {
			$orden = " cliente.glosa_cliente ASC, trabajo.fecha ASC";
		} else {
			$orden = " trabajo.fecha ASC";
		}
	}

	if (stristr($orden, ".") === FALSE) {
		$orden = str_replace("codigo_asunto", "a1.codigo_asunto", $orden);
	}

	$x_pag = 15;
	$b = new Buscador($sesion, $query, "Trabajo", $desde, $x_pag, $orden, "", false);
	$b->mensaje_error_fecha = "N/A";
	$b->nombre = "busc_gastos";
	$b->titulo = __('Listado de') . ' ' . __('trabajos');

	if ($p_revisor->fields['permitido']) {
		$b->titulo .= "<table width=100%><tr><td align=right valign=top><span style='font-size:10px'><b>" . __('Total horas trabajadas') . ": </b>" . number_format($total_duracion_trabajada, 1) . "</span></td></tr></table>";
	}

	$b->titulo .= "<table width=100%><tr><td align=right valign=top><span style='font-size:10px'><b>" . __('Total horas cobrables corregidas') . ": </b>" . number_format($total_duracion, 1) . "</span></td></tr></table>";
	$b->AgregarFuncion("Editar", 'Editar', "align=center nowrap");
	$b->AgregarEncabezado("trabajo.fecha", __('Fecha'));
	$b->AgregarEncabezado("cliente.glosa_cliente", __('Cliente'), "align=left");
	$b->AgregarEncabezado("asunto.codigo_asunto", __('Asunto'), "align=left");

	if (Conf::GetConf($sesion, 'UsoActividades')) {
		$b->AgregarEncabezado("actividad.glosa_actividad", __('Actividad'), "align=left");
	}

	$b->AgregarEncabezado("glosa_cobrable", __('Cobrable'), "", "", "");

	if ($p_revisor->fields['permitido']) {
		$glosa_duracion = __('Hrs Trab./Cobro.');
	} else {
		$glosa_duracion = __('Hrs trab.');
	}

	$b->AgregarEncabezado("duracion", $glosa_duracion, "", "", "SplitDuracion");

	if ($p_revisor->fields['permitido'] || $p_cobranza->fields['permitido']) {
		$b->AgregarEncabezado("trabajo.id_cobro", __('Cobro'), "align=left");
	}
	// $b->AgregarEncabezado("estado",__('Estado'),"align=left");
	if ($p_revisor->fields['permitido'] || $p_cobranza->fields['permitido'] || strlen($select_usuario) > 164) {
		$b->AgregarEncabezado("usr_nombre", __('Usuario'), "align=left");
	}
	// if($p_adm->fields['permitido'])
	$b->AgregarFuncion("Opc.", 'Opciones', "align=center nowrap");
	$b->color_mouse_over = "#bcff5c";
	$b->funcionTR = "funcionTR";
}
if ($excel) {

	if ($p_cobranza->fields['permitido']) {
		$orden = "cliente.glosa_cliente,contrato.id_contrato,asunto.glosa_asunto,trabajo.fecha,trabajo.descripcion";
	}

	$b1 = new Buscador($sesion, $query, "Trabajo", $desde, '', $orden);
	$lista = $b1->lista;

	if ($p_cobranza->fields['permitido'] && Conf::GetConf($sesion, 'CobranzaExcel')) {
		require_once('cobros_generales.xls.php');
	} else if ($simplificado == 1) {
		require_once('ajax/cobros3.simplificado.xls.php');
	} else {
		require_once('cobros3.xls.php');
	}

	exit;
}

if ($word) {
	include dirname(__FILE__) . '/cobro_doc.php';
	exit;
}

$pagina->titulo = __('Listado de trabajos');
$pagina->PrintTop($popup);
?>

<script type="text/javascript">

	function GrabarCampo(accion,id_trabajo,cobro,valor) {

		var http = getXMLHTTP();
		if (valor) {
			valor = '1';
		} else {
			valor = '0';
		}

		loading("Actualizando opciones");
		http.open('get', 'ajax_grabar_campo.php?accion=' + accion + '&id_trabajo=' + id_trabajo + '&id_cobro=' + cobro + '&valor=' + valor);

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
		//todo if $motivo=="cobros",$motivo=="horas"
		var pagina_desde = '<?php echo $desde ? "&desde=$desde" : ''; ?>';
		var orden = '<?php echo $desde ? "&orden=$orden" : ''; ?>';

		<?php if ($motivo == "horas") {
			if (Conf::GetConf($sesion, 'CodigoSecundario')) {
		?>

			var cliente = 'codigo_cliente_secundario='+$('codigo_cliente_secundario').value;
			var asunto = 'codigo_asunto_secundario='+$('codigo_asunto_secundario').value;
			<?php } else { ?>
				var cliente = 'codigo_cliente='+$('codigo_cliente').value;
				var asunto = 'codigo_asunto='+$('codigo_asunto').value;
			<?php } ?>

			var cobrado = $('cobrado').value;
			var cobrable = $('cobrable').value;
			var revisado = $('revisado').value;
			var id_cobro = $('id_cobro').value;
			var encargado_comercial = $('id_encargado_comercial').value;
			var usuario = $('id_usuario').value;
			var fecha_ini = $('fecha_ini').value;
			var fecha_fin = $('fecha_fin').value;
			var url = "trabajos.php?from=horas&motivo=horas&popup=1&opc=buscar"
				+ "&cobrado=" + cobrado
				+ "&cobrable=" + cobrable
				+ "&revisado=" + revisado
				+ "&id_cobro=" + id_cobro
				+ "&" + cliente
				+ "&" + asunto
				+ "&id_encargado_comercial=" + encargado_comercial
				+ "&id_usuario=" + usuario
				+ "&fecha_ini=" + fecha_ini
				+ "&fecha_fin=" + fecha_fin
				+ pagina_desde
				+ orden;

		<?php } else if ($motivo == "cobros") { ?>

			var fecha_ini = $('fecha_ini').value;
			var fecha_fin = $('fecha_fin').value;
			var url = "trabajos.php?id_cobro=<?php echo $id_cobro ?>&motivo=cobros&popup=1&fecha_ini="+fecha_ini+"&fecha_fin="+fecha_fin+pagina_desde+orden;

		<?php } ?>

		self.location.href= url;
	}


	function GuardarCampoTrabajo(id,campo,valor) {

		var http = getXMLHTTP();
		var url = 'ajax.php?accion=actualizar_trabajo&id=' + id + '&campo=' + campo + '&valor=' + valor;

		loading("Actualizando campo");
		http.open('get', url);
		http.onreadystatechange = function() {
			if (http.readyState == 4) {
				var response = http.responseText;
				offLoading();
			}
		};
		http.send(null);
	}


	// Basado en http://snipplr.com/view/1696/get-elements-by-class-name/
	function getElementsByClassName(classname)
	{
		node = document.getElementsByTagName("body")[0];
		var a = [];
		var re = new RegExp('\\b' + classname + '\\b');
		var els = node.getElementsByTagName("*");
		for (var i=0,j=els.length; i<j; i++) {
			if(re.test(els[i].className))a.push(els[i]);
		}
		return a;
	}
	// Función para seleccionar todos las filas para editar, basada en la de phpMyAdmin
	function seleccionarTodo(valor)
	{

		jQuery('.editartrabajo').each(function() {

			if(!jQuery(this).is(':disabled')) {
			  if(valor==true) {
				  jQuery(this).attr('checked','checked');
			  } else {
				  jQuery(this).removeAttr('checked');
			  }
			}
		});

		return true;
	}
	// Encuentra los id de los trabajos seleccionados para editar, depende del id del primer <tr> que contiene al trabajo.
	// Los id quedan en un string separados por el caracter 't'.
	function getIdTrabajosSeleccionados()
	{
		var ids = '';

		jQuery('.editartrabajo').each(function() {
			var trabajoid=jQuery(this).closest('tr').attr('id');
			if(jQuery(this).is(':checked')) {
			ids += trabajoid;
			}
		});

		return ids;
	}

	// Intenta editar múltiples trabajos, genera un error si no hay trabajos seleccionados.
	function editarMultiplesArchivos()
	{
		// Los id de los trabajos seleccionados están en un solo string separados por el caracter 't'.
		// La página editar_multiples_trabajos.php se encarga de parsear este string.
		var ids = getIdTrabajosSeleccionados();
		if (ids != '') {
			nuovaFinestra('Editar_múltiples_trabajos', 700, 500, 'editar_multiples_trabajos.php?ids='+ids+'&popup=1','');
		} else {
			alert('Debe seleccionar por lo menos un trabajo para editar.');
		}
	}

	function EditarTodosLosArchivos()
	{
		var where = $('where_query_listado_completo').value;
		nuovaFinestra('Editar_multiples_trabajos', 700, 450, 'editar_multiples_trabajos.php?popup=1&listado='+where, '');
	}


</script>

<form method='post' name="form_trabajos" id="form_trabajos">
	<input type='hidden' name='opc' id='opc' value='buscar'>
	<input type='hidden' name='popup' id='popup' value='<?php echo $popup ?>'>
	<input type='hidden' name='motivo' id='motivo' value='<?php echo $motivo ?>'>
	<input type='hidden' name='id_usuario' id='id_usuario' value='<?php echo $id_usuario ?>'>
	<?php
	if ($query_asuntos) {
		echo '<input type="hidden" name="query_asuntos" id="query_asuntos" value="' . $query_asuntos . '"/>';
	}
	if ($id_contrato) {
		echo '<input type="hidden" name="id_contrato" id="id_contrato" value="' . $id_contrato . '"/>';
	}
	if ($id_cobro) {
		echo '<input type="hidden" name="id_cobro" id="id_cobro" value="' . $id_cobro . '"/>';
	}
	?>
	<input type='hidden' name='check_trabajo' id='check_trabajo' value=''>

	<fieldset class="tb_base" width="100%" style="border: 1px solid #BDBDBD;">
		<legend><?php echo __('Filtros') ?></legend>

		<table   style="border: 0px solid black;width:700px;margin:auto;" >

			<?php

			if ($motivo != "cobros") {
				if ($p_revisor->fields['permitido']) {
					?>
					<tr>

						<td style="width:180px;" class="buscadorlabel"><?php echo __('Cobrado') ?></td>
						<td align='left'>
							<?php echo Html::SelectQuery($sesion, "SELECT codigo_si_no, codigo_si_no FROM prm_si_no", "cobrado", $cobrado, ' class="fl" ', 'Todos', '60') ?>
							<div class="fl buscadorlabel" style="margin-top: 3px;width:70px;display:inline-block;" ><?php echo __('Cobrable') ?></div> <?php echo Html::SelectQuery($sesion, "SELECT codigo_si_no, codigo_si_no FROM prm_si_no", "cobrable", $cobrable, ' class="fl" ', 'Todos', '60') ?>
							<div class="fl buscadorlabel" style="margin-top: 3px;width:70px;display:inline-block;"><?php echo __('Revisado') ?></div> <?php echo Html::SelectQuery($sesion, "SELECT codigo_si_no, codigo_si_no FROM prm_si_no", "revisado", $revisado, ' class="fl" ', 'Todos', '60') ?>
						</td>
						<td class="buscadorlabel">
							<?php
							if ($motivo == 'horas') {
								echo ' <div class="fl buscadorlabel" style="margin-top: 3px;padding-right:3px;width:60px;">' . __('Cobro') . " </div><input id='id_cobro' class='fl' type='text' style='float:left;width:80px;' name='buscar_id_cobro' id='buscar_id_cobro' value='$buscar_id_cobro'/>";
							}
							?>
						</td>
					</tr>
					<?php
				}
				?>
				<tr>
					<td class="buscadorlabel" align="right">
						<?php echo __('Grupo Cliente')?>
					</td>
					<td align="left">
						<?php echo  Html::SelectQuery($sesion, "SELECT id_grupo_cliente, glosa_grupo_cliente FROM grupo_cliente", "id_grupo", $id_grupo, "", "Ninguno","width=100px")  ?>
					</td>
				</tr>

				<tr>
					<td class="buscadorlabel">
						<?php echo __('Encargado Comercial') ?>
					</td>
					<td align='left' colspan="2">
						<?php echo  Html::SelectQuery($sesion, $query_usuario, "id_encargado_comercial", $id_encargado_comercial, "", "Ninguno","width=100px")  ?>
					</td>
				</tr>

				<tr>

					<td class="buscadorlabel"><?php echo __('Nombre Cliente') ?></td>
					<td nowrap align='left' colspan="2">
						<?php UtilesApp::CampoCliente($sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario); ?>

					</td>
				</tr>
				<tr>
					<td class="buscadorlabel"><?php echo __('Asunto') ?></td>
					<td nowrap align='left' colspan="2">

						<?php
						if (Conf::GetConf($sesion,'UsoActividades')) {
							$oncambio .= 'CargarActividad();';
						}?>

						<?php UtilesApp::CampoAsunto($sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario, 320,$oncambio); ?>

					</td>
				</tr>
				<?php ($Slim=Slim::getInstance('default',true)) ?  $Slim->applyHook('hook_filtros_trabajos'):false; ?>

				<?php if (Conf::GetConf($sesion, 'UsoActividades')) { ?>
				<tr>
					<td class="buscadorlabel"><?php echo __('Actividad') ?></td>
					<td align=left width="440" nowrap>
						<?php echo InputId::Imprimir($sesion, 'actividad', 'codigo_actividad', 'glosa_actividad', 'codigo_actividad', $codigo_actividad, '', '', 300, $codigo_asunto); ?>
					</td>
				</tr>
				<?php } ?>

				<tr>
					<td class="buscadorlabel">
						<?php echo __('Usuario') ?>
					</td>
					<td align='left' colspan="2">
						<?php echo $select_usuario ?>
					</td>
				</tr>
				<tr>
					<td class="buscadorlabel">
						<?php echo __('Área Usuario') ?>
						<?php if ( Conf::GetConf($sesion, 'ValidacionesCliente') ) echo $obligatorio ?>
					</td>
					<td valign="top" class="texto" align="left">
						<?php
							$query_areas = 'SELECT id, glosa FROM prm_area_usuario ORDER BY glosa';
							if ( Conf::GetConf($sesion, 'UsarModuloRetribuciones') ) {
								$query_areas = 'SELECT area.id, CONCAT(REPEAT("&nbsp;", IF(ISNULL(padre.id), 0, 5)), area.glosa) FROM prm_area_usuario AS area
												LEFT JOIN prm_area_usuario AS padre ON area.id_padre = padre.id
												ORDER BY  IFNULL(padre.glosa, area.glosa), padre.glosa, area.glosa ASC ';
							}
							echo Html::SelectQuery($sesion, $query_areas, 'id_area_usuario', $usuario->fields['id_area_usuario'] ? $usuario->fields['id_area_usuario'] : $id_area_usuario, "", "Ninguna")
							?>
					</td>
				</tr>
				<?php

			}
			// Validando fecha
			$hoy = date('Y-m-d');

			if ($fecha_ini != '0000-00-00') {
				if (Utiles::es_fecha_sql($fecha_ini)) {
					$fecha_ini = Utiles::sql2date($fecha_ini);
				}
			} else {
				$fecha_ini = '';
			}
			if ($fecha_fin != '0000-00-00') {
				if (Utiles::es_fecha_sql($fecha_fin)) {
					$fecha_fin = Utiles::sql2date($fecha_fin);
				}
			} else {
				$fecha_fin = '';
			}
			?>
			<tr>
				<td class="buscadorlabel" colspan=1><?php echo __('Fecha desde') ?></td>
				<td align=left colspan="2">

					<input type="text" name="fecha_ini" class="fechadiff" value="<?php echo $fecha_ini ?>" id="fecha_ini" size="11" maxlength="10" />
					 &nbsp;&nbsp;&nbsp;&nbsp;
					<div class="buscadorlabel" style="margin-bottom: 3px;width:70px;display:inline-block;" ><?php echo __('Fecha hasta') ?></div>
					<input type="text" name="fecha_fin"  class="fechadiff"  value="<?php echo $fecha_fin ?>" id="fecha_fin" size="11" maxlength="10" />
					</td>

			</tr>
			<tr>
				<td></td>
				<td colspan="2"  align=left>
					<?php echo $Form->icon_button(__('Buscar'), 'find', array('id' => 'boton_buscar')); ?>
				</td>
			</tr>
		</table>
	</fieldset>

</form>

<?php if (isset($cobro) || $opc == 'buscar') { ?>
	<?php $b->Imprimir('', array('check_trabajo')); //Excluyo Checktrabajo); ?>
	<form>
		<center>
			<a href="#" onclick="seleccionarTodo(true); return false;">Seleccionar todo</a>
			&nbsp;&nbsp;&nbsp;&nbsp;
			<a href="#" onclick="seleccionarTodo(false); return false;">Desmarcar todo</a>
			&nbsp;&nbsp;&nbsp;&nbsp;
			<a href="#" onclick="editarMultiplesArchivos(); return false;" title="Editar múltiples trabajos">Editar seleccionados</a>
			<br />
			<input type='hidden' name='where_query_listado_completo' id='where_query_listado_completo' value='<?php echo urlencode(base64_encode($where)) ?>'>
			<a href="#" onclick="EditarTodosLosArchivos(); return false;" title="Editar trabajos de todo el listado">Editar trabajos de todo el listado</a>
			<br />
			<br />
			<?php echo $Form->icon_button(__('Descargar listado a Excel'), 'xls', array('id' => 'descargapro')); ?>
			<?php echo $Form->icon_button(__('Descargar listado agrupado'), 'xls', array('id' => 'descargar_excel_agrupado')); ?>
			<label><input type="checkbox" value="1" id="por_socio"/> Agrupar por socio</label>
			<br />
		</center>
	</form>

<?php
}

function Cobrable(& $fila) {
	global $id_cobro;

	if ($fila->fields['id_cobro'] == $id_cobro) {
		$checked = "checked";
	} else {
		$checked = "";
	}

	$Check = "<input type='checkbox' $checked onclick=GrabarCampo('cobrar_trabajo','" . $fila->fields['id_trabajo'] . "',$id_cobro,'');>";
	return $Check;
}

function Revisado(& $fila) {

	if ($fila->fields['revisado'] == 1) {
		$checked = "checked";
	} else {
		$checked = "";
	}

	$Check = "<input type='checkbox' $checked onmouseover=\"ddrivetip('Para marcar un trabajo como revisado haga click aquí.&lt;br&gt;Los trabajos revisados no se desplegarán en este listado la próxima vez.')\" onmouseout=\"hideddrivetip();\" onchange=\"GuardarCampoTrabajo(" . $fila->fields['id_trabajo'] . ",'revisado',this.checked ? 1 : 0)\">";
	return $Check;
}

function Opciones(& $trabajo, $texto = '') {
	$img_dir = Conf::ImgDir();
	global $motivo;
	$id_cobro = $trabajo->fields['id_cobro'];
	global $sesion;
	global $p_profesional;
	global $p_revisor;

	$cobro = new Cobro($sesion);
	$cobro->Load($id_cobro);

	if ($motivo == 'cobros') {
		$opc_html = Cobrable($trabajo);
	}

	// verificar si el usuario que inició sesión es revisor del usuario que se le está revisando las horas ingresadas
	$permiso_revisor_usuario = $sesion->usuario->Revisa($trabajo->fields['id_usuario']);

	if ($p_revisor->fields['permitido'] || $permiso_revisor_usuario) {
		if ($cobro->fields['estado'] == 'CREADO' || $cobro->fields['estado'] == 'EN REVISION' || empty($trabajo->fields['id_cobro'])) {
			$opc_html.= "<a style='vertical-align:top;' href=# onclick=\"nuovaFinestra('Editar_Trabajo',750,500,'editar_trabajo.php?id_cobro=" . $id_cobro . "&id_trabajo=" . $trabajo->fields[id_trabajo] . "&popup=1','');\" title=" . __('Editar') . ">" . (($texto == '') ? "<img src=$img_dir/editar_on.gif border=0>" : $texto) . "</a>";
		} else {
			$opc_html.= "<a style='vertical-align:top;'  href=\"javascript:void(0)\" onclick=\"alert('" ;
			$opc_html.= __("No se puede modificar este trabajo. El Cobro que lo incluye ya ha sido Emitido al Cliente.") ;
			$opc_html.= "');\" title=\"" . __('Cobro ya Emitido al Cliente') . "\">" . (($texto == '') ? "<img src=$img_dir/editar_off.gif border=0>" : $texto) . "</a>";
		}
	} else if ($p_profesional->fields['permitido']) {
		if ($trabajo->Estado() == 'Revisado') {
			$opc_html .= "<span title='" . __('Este trabajo ya ha sido revisado') . "'>" . ($texto == '') ? "<img src=$img_dir/candado_16.gif border=0 />" : $texto . "</span>";
		} else {
			if ($cobro->fields['estado'] == 'CREADO' || $cobro->fields['estado'] == 'EN REVISION' || empty($trabajo->fields['id_cobro'])) {
				$opc_html.= "<a style='vertical-align:top;'  href=# onclick=\"nuovaFinestra('Editar_Trabajo',550,450,'editar_trabajo.php?id_cobro=" . $id_cobro . "&id_trabajo=" . $trabajo->fields[id_trabajo] . "&popup=1','');\" title=" . __('Editar') . ">" . (($texto == '') ? "<img src=$img_dir/editar_on.gif border=0>" : $texto) . "</a>";
			} else {
				$opc_html.= "<a style='vertical-align:top;'  href=\"javascript:void(0)\" onclick=\"alert('" ;
			$opc_html.= __("No se puede modificar este trabajo. El Cobro que lo incluye ya ha sido Emitido al Cliente.") ;
			$opc_html.="');\" title=\"" . __('Cobro ya Emitido al Cliente') . "\" >" . (($texto == '') ? "<img src=$img_dir/editar_off.gif border=0>" : $texto) . "</a>";
			}
		}
	} else {
		$opc_html .= "<span title='" . __('Usted no tiene permiso de Revisor') . "'>" . ($texto == '') ? "<img src=$img_dir/candado_16.gif border=0 />" : $texto . "</span>";
	}

	return $opc_html;
}

function LinkAlTrabajo(& $trabajo, $texto = '') {
	$img_dir = Conf::ImgDir();
	global $motivo;
	$id_cobro = $trabajo->fields['id_cobro'];
	global $sesion;
	global $p_profesional;
	global $p_revisor;

	$cobro = new Cobro($sesion);
	$cobro->Load($id_cobro);

	// verificar si el usuario que inició sesión es revisor del usuario que se le está revisando las horas ingresadas
	$permiso_revisor_usuario = $sesion->usuario->Revisa($trabajo->fields['id_usuario']);

	if ($p_revisor->fields['permitido'] || $permiso_revisor_usuario) {
		if ($cobro->fields['estado'] == 'CREADO' || $cobro->fields['estado'] == 'EN REVISION' || empty($trabajo->fields['id_cobro'])) {
			$opc_html.= "<a style='vertical-align:top;' href=\"javascript:void(0)\" onclick=\"nuovaFinestra('Editar_Trabajo',600,500,'editar_trabajo.php?id_cobro=" . $id_cobro . "&id_trabajo=" . $trabajo->fields[id_trabajo] . "&popup=1','');\" title=" . __('Editar') . ">" . (($texto == '') ? "<img src=$img_dir/editar_on.gif border=0>" : $texto) . "</a>";
		} else {
			$opc_html.= "<a style='vertical-align:top;'  href=\"javascript:void(0)\" onclick=\"alert('" ;
			$opc_html.=__("No se puede modificar este trabajo. El Cobro que lo contiene ya ha sido Emitido al Cliente.") ;
			$opc_html.= "');\" title=\"" . __('Cobro ya Emitido al Cliente') . "\">" . (($texto == '') ? "<img src=$img_dir/editar_off.gif border=0>" : $texto) . "</a>";
		}
	} else if ($p_profesional->fields['permitido']) {
		if ($trabajo->Estado() == 'Revisado') {
			$opc_html .= "<span title='" . __('Este trabajo ya ha sido revisado') . "'>" . ($texto == '') ? "<img src=$img_dir/candado_16.gif border=0 />" : $texto . "</span>";
		} else {
			if ($cobro->fields['estado'] == 'CREADO' || $cobro->fields['estado'] == 'EN REVISION' || empty($trabajo->fields['id_cobro'])) {
				$opc_html.= "<a style='vertical-align:top;'  href=\"javascript:void(0)\" onclick=\"nuovaFinestra('Editar_Trabajo',550,450,'editar_trabajo.php?id_cobro=" . $id_cobro . "&id_trabajo=" . $trabajo->fields[id_trabajo] . "&popup=1','');\" title=" . __('Editar') . ">" . (($texto == '') ? "<img src=$img_dir/editar_on.gif border=0>" : $texto) . "</a>";
			} else {
				$opc_html.= "<a style='vertical-align:top;'  href=\"javascript:void(0)\" onclick=\"alert('";
				$opc_html.= __("No se puede modificar este trabajo.  El Cobro que lo contiene ya ha sido Emitido al Cliente.") ;
				$opc_html.= "');\" title=\"" . __('Cobro ya Emitido al Cliente') . "\" >" . (($texto == '') ? "<img src=$img_dir/editar_off.gif border=0>" : $texto) . "</a>";
			}
		}
	} else {
		$opc_html .= "<span title='" . __('Usted no tiene permiso de Revisor') . "'>" . ($texto == '') ? "<img src=$img_dir/candado_16.gif border=0 />" : $texto . "</span>";
	}


	return $opc_html;
}

function SplitDuracion($time) {
	list($h, $m, $s) = split(":", $time);
	if ($h > 0 || $s > 0) {
		return $h . ":" . $m;
	}
}

function funcionTR(& $trabajo) {
	global $sesion;
	global $p_revisor;
	global $p_cobranza;
	global $p_profesional;
	global $select_usuario;
	static $i = 0;

	$t = new Trabajo($sesion);

	$moneda_cobro = new Moneda($sesion);
	if ($trabajo->fields['id_cobro'] > 0) {
		$moneda_cobro->Load($trabajo->fields['id_moneda_cobro']);
	} else {
		$moneda_cobro->Load($trabajo->fields['id_moneda_asunto']);
	}
	if ($trabajo->fields['id_tramite'] > 0) {
		$query = "SELECT glosa_tramite FROM tramite_tipo
	JOIN tramite USING(id_tramite_tipo)
	WHERE tramite.id_tramite=" . $trabajo->fields['id_tramite'];
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		list($glosa_tramite) = mysql_fetch_array($resp);
	}

	$idioma = new Objeto($sesion, '', '', 'prm_idioma', 'codigo_idioma');
	if ($trabajo->fields['codigo_idioma'] != '') {
		$idioma->Load($trabajo->fields['codigo_idioma']);
	} else {
		$idioma->Load(strtolower(Conf::GetConf($sesion, 'Idioma')));
	}

	if ($i % 2 == 0) {
		$color = "#dddddd";
	} else {
		$color = "#ffffff";
	}

	if (Conf::GetConf($sesion, 'GuardarTarifaAlIngresoDeHora')) {
		if ($trabajo->fields['id_moneda_cobro'] > 0) {
			$id_moneda_trabajo = $trabajo->fields['id_moneda_cobro'];
		} else {
			$id_moneda_trabajo = $trabajo->fields['id_moneda_contrato'];
		}

		$tarifa = number_format($t->GetTrabajoTarifa($id_moneda_trabajo, $trabajo->fields['id_trabajo']), $moneda_cobro->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
	} else if ($trabajo->fields['tarifa_hh'] > 0 && $trabajo->fields['id_cobro'] > 0) {
		$tarifa = number_format($trabajo->fields['tarifa_hh'], $moneda_cobro->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
	} else if ($trabajo->fields['id_tramite_tipo'] == 0) {
		$tarifa = number_format(Funciones::Tarifa($sesion, $trabajo->fields['id_usuario'], $trabajo->fields['id_moneda_contrato'], $trabajo->fields['codigo_asunto']), $moneda_cobro->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
	} else {
		$tarifa = number_format(Funciones::TramiteTarifa($sesion, $trabajo->fields['id_tramite_tipo'], $trabajo->fields['id_moneda_cobro'], $trabajo->fields['codigo_asunto']), $moneda_cobro->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
	}
	list($h, $m, $s) = split(":", $trabajo->fields['duracion_cobrada']);
	$duracion = $h + ($m > 0 ? ($m / 60) : '0');
	$total = round($tarifa * $duracion, 2);
	$total_horas += $duracion;
	//	if(substr($h,0,1)=='0')
	//		$h=substr($h,1);
	$dur_cob = "$h:$m";
	$formato_fecha = UtilesApp::ObtenerFormatoFecha($sesion);

	if ($trabajo->fields['id_tramite_tipo'] > 0) {
		$html .= "<tr bgcolor=$color style=\"border-right: 1px solid #409C0B; border-left: 1px solid #409C0B;\">";
		$html .= "<td colspan=9><strong>" . $trabajo->fields['glosa_tramite'] . "</strong></td></tr>";
	}
	$html .= "<tr id=\"t" . $trabajo->fields['id_trabajo'] . "\" bgcolor=$color style=\"border-right: 1px solid #409C0B; border-left: 1px solid #409C0B;\">";
	$html .= '<td><input type="checkbox" class="editartrabajo " onmouseover="ddrivetip(\'Para editar múltiples trabajos haga click aquí.\')" onmouseout="hideddrivetip();" ></td>';
	$fecha = Utiles::sql2fecha($trabajo->fields['fecha'], $formato_fecha);
	$fecha_creacion = Utiles::sql2fecha($trabajo->fields['fecha_creacion'], $formato_fecha);
	$fecha_modificacion = Utiles::sql2fecha($trabajo->fields['fecha_modificacion'], $formato_fecha);
	$fecha_html = "<span title=\"Creado el: $fecha_creacion, Modificado el: $fecha_modificacion\">$fecha</span>";
	$html .= "<td>$fecha_html</td>";
	$html .= "<td>" . $trabajo->fields['glosa_cliente'] . "</td>";
	$html .= "<td><a title='" . $trabajo->fields['glosa_asunto'] . "'>" . $trabajo->fields['glosa_asunto'] . "</a></td>";
	if (Conf::GetConf($sesion, 'UsoActividades')) {
		if ($trabajo->fields['glosa_actividad'] == '') {
			$trabajo->fields['glosa_actividad'] = 'No Definida';
		}
		$html .= "<td nowrap>" . $trabajo->fields['glosa_actividad'] . "</td>";
	}
	$html .= "<td align=center>";
	$html .= $trabajo->fields['cobrable'] == 1 ? "SI" : "NO";
	if ($p_cobranza->fields['permitido'] && $trabajo->fields['cobrable'] == 0) {
		$html .= $trabajo->fields['visible'] == 1 ? '<br>(visible)' : '<br>(no visible)';
	}
	$html .= "</td>";
	$duracion = $trabajo->fields['duracion'];
	//echo $duracion;
	if (!$p_revisor->fields['permitido']) {
		list($duracion_trabajada, $duracion_cobrada) = split('<br>', $trabajo->fields['duracion']);
		$duracion = $duracion_trabajada;
		if (Conf::GetConf($sesion, 'TipoIngresoHoras') == 'decimal') {
			$duracion = UtilesApp::Time2Decimal($duracion_trabajada);
		}
	} else {
		if (Conf::GetConf($sesion, 'TipoIngresoHoras') == 'decimal') {
			list($duracion_trabajada, $duracion_cobrada) = split('<br>', $trabajo->fields['duracion']);
			$duracion = UtilesApp::Time2Decimal($duracion_trabajada) . "<br>" . UtilesApp::Time2Decimal($duracion_cobrada);
		}
	}
	//echo $duracion."fin<br>";
	if ($p_cobranza->fields['permitido']) {
		$editar_cobro = "<a href='javascript:void(0)' onclick=\"nuevaVentana('Generar " . __("Cobro") . "',750,660,'cobros5.php?popup=1&id_cobro=" . $trabajo->fields['id_cobro'] . "');\"'>" . $trabajo->fields['id_cobro'] . "</a>";
	} else if ($p_revisor->fields['permitido']) {
		$editar_cobro = $trabajo->fields['id_cobro'];
	}

	$html .= "<td align=center>" . $duracion . "</td>";
	if ($p_cobranza->fields['permitido'] || $p_revisor->fields['permitido']) {
		$html .= "<td>" . $editar_cobro . "</td>";
	}
	//$html .= "<td>".$trabajo->Estado()."</td>";
	if ($p_revisor->fields['permitido'] || $p_cobranza->fields['permitido'] || strlen($select_usuario) > 164) {
		if (Conf::GetConf($sesion, 'UsernameEnListaDeTrabajos') || Conf::GetConf($sesion, 'UsaUsernameEnTodoElSistema')) {
			$html .= "<td>" . $trabajo->fields['username'] . "</td>";
		} else {
			$html .= "<td>" . substr($trabajo->fields['nombre'], 0, 1) . ". " . $trabajo->fields['apellido1'] . "</td>";
		}
	}
	$html .= '<td align=center>' . Opciones($trabajo) . '</td>';
	$html .= "</tr>";
	$html .= "<tr bgcolor=$color style=\"border-right: 1px solid #409C0B; border-left: 1px solid #409C0B; border-bottom: 1px solid #409C0B; \">";

	$desc_colspan = 7;
	if ($p_cobranza->fields['permitido']) {
		$desc_colspan = 8;
	}

	// Le muestro la tarifa cuando tiene el Conf, es profesional no revisor
	$mostrar_tarifa_al_profesional =
			Conf::GetConf($sesion, 'MostrarTarifaAlProfesional') &&
			$p_profesional->fields['permitido'] &&
			!$p_revisor->fields['permitido'];

	if ($mostrar_tarifa_al_profesional) {
		$desc_colspan = 4;
	}

	if ($p_revisor->fields['permitido']) {
		$desc_colspan = 5;
	}

	//$html .= "<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
	$html .= "<td><strong>Desc.</strong></td><td colspan='" . ($desc_colspan + 1) . "' align=left>" . LinkAlTrabajo($trabajo, '#' . $trabajo->fields['id_trabajo']) . "&nbsp;" . stripslashes($trabajo->fields['descripcion']) . "</td>";

	$columna_tarifa = "<td colspan=2 align=center><strong>" . __('Tarifa') . "</strong><br>" . ( $moneda_cobro->fields['id_moneda'] > 0 ? $moneda_cobro->fields['simbolo'] : Utiles::glosa($sesion, $trabajo->fields['id_moneda_contrato'], 'simbolo', 'prm_moneda', 'id_moneda')) . " " . $tarifa . "</td>";

	if ($p_revisor->fields['permitido']) {
		$html .= '<td>Rev.' . Revisado($trabajo) . '</td>';
		$html .= $columna_tarifa;
	}

	if ($mostrar_tarifa_al_profesional) {
		$html .= $columna_tarifa;
	}

	$html .= "</tr>\n";
	$i++;
	return $html;
}

echo $Form->script();
?>
<script type="text/javascript">

	function CargarActividad() {
		CargarSelect('codigo_asunto','codigo_actividad','cargar_actividades');
	}

	jQuery(document).ready(function() {
		jQuery('#boton_buscar').click(function() {
			jQuery('#check_trabajo').attr('checked','checked').val(1);
			jQuery('#form_trabajos').submit();
		});
		jQuery('#campo_codigo_actividad').hide();

		jQuery('#descargapro').click(function() {

			jQuery('#descargapro').attr('disabled','disabled');
			var Where='<?php echo base64_encode($where) ?>';
			var Idcobro='<?php echo $id_cobro; ?>';
			var Motivo='<?php echo $motivo; ?>';
			jQuery.post('ajax/estimar_datos.php',{where:Where,id_cobro:Idcobro,motivo:Motivo},function(data) {

				if(parseInt(data)>15000) {

					var formated=data/1000;
					var dialogoconfirma = top.window.jQuery( "<div/>" );
					dialogoconfirma.attr('title','Advertencia').append('<p style="text-align:center;padding:10px;">Su consulta retorna '+formated.toFixed(3)+' datos, por lo que el sistema s&oacute;lo puede exportar a un excel simplificado y con funcionalidades limitadas.<br /><br /> Le advertimos que la descarga puede demorar varios minutos y pesar varios MB</p>');
					jQuery( "#dialog:ui-dialog" ).dialog( "destroy" );

					dialogoconfirma.dialog({
						resizable: false,
						autoOpen:true,
						height:220,
						width:450,
						modal: true,
						close:function(ev,ui) {
							dialogoconfirma.html('');
						},
						buttons: {
							"<?php echo __('Entiendo y acepto') ?>": function() {
								jQuery('#descargapro').removeAttr('disabled');
								window.location.href = 'trabajos.php?id_cobro=<?php echo $id_cobro ?>&excel=1&simplificado=1&motivo=<?php echo $motivo ?>&where=<?php echo urlencode(base64_encode($where)) ?>';
								dialogoconfirma.dialog( "close" );

								return true;
							},

							"<?php echo __('Cancelar') ?>": function() {
								jQuery('#descargapro').removeAttr('disabled');
								dialogoconfirma.dialog( "close" );

								return false;

							}
						}
					});
				} else {
					jQuery('#descargapro').removeAttr('disabled');
					window.location.href='trabajos.php?id_cobro=<?php echo $id_cobro ?>&excel=1&motivo=<?php echo $motivo ?>&where=<?php echo urlencode(base64_encode($where)) ?>';
					return true;
				}
			});

			jQuery('#descargapro').removeAttr('disabled');
		});

		jQuery('#descargar_excel_agrupado').click(function() {
			var Where='<?php echo base64_encode($where) ?>';
			var Idcobro='<?php echo $id_cobro; ?>';
			var Motivo='<?php echo $motivo; ?>';
			var por_socio = jQuery('#por_socio:checked').val();
			jQuery.post('ajax/estimar_datos.php', {
				where: Where,
				id_cobro: Idcobro,
				motivo:Motivo
			},
			function(data) {

				if(parseInt(data)>15000) {

					var formated=data/1000;
					var dialogoconfirma = top.window.jQuery( "<div/>" );
					dialogoconfirma.attr('title','Advertencia').append('<p style="text-align:center;padding:10px;">Su consulta retorna '+formated.toFixed(3)+' datos, por lo que el sistema s&oacute;lo puede exportar a un excel simplificado y con funcionalidades limitadas.<br /><br /> Le advertimos que la descarga puede demorar varios minutos y pesar varios MB</p>');
					jQuery( "#dialog:ui-dialog" ).dialog( "destroy" );

					dialogoconfirma.dialog({
						resizable: false,
						autoOpen: true,
						height: 220,
						width: 450,
						modal: true,
						close: function(ev,ui) {
							dialogoconfirma.html('');
						},
						buttons: {
							"<?php echo __('Entiendo y acepto') ?>": function() {
								window.open('trabajos.php?id_cobro=<?php echo $id_cobro ?>&excel_agrupado=1&motivo=<?php echo $motivo ?>&where=<?php echo urlencode(base64_encode($where)) ?>&por_socio=' + por_socio, '_blank');
								dialogoconfirma.dialog( "close" );
							},
							"<?php echo __('Cancelar') ?>": function() {
								dialogoconfirma.dialog( "close" );
							}
						}
					});
				} else {
					window.open('trabajos.php?id_cobro=<?php echo $id_cobro ?>&excel_agrupado=1&motivo=<?php echo $motivo ?>&where=<?php echo urlencode(base64_encode($where)) ?>&por_socio=' + por_socio, '_blank');
				}
			});

		});

	});

</script>
<?php $pagina->PrintBottom($popup);
