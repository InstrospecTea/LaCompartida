<?php

require_once dirname(__FILE__) . '/../conf.php'; 

$sesion = new Sesion(array('PRO', 'REV', 'COB', 'SEC'));
$pagina = new Pagina($sesion);

$t = new Trabajo($sesion);

if ($_REQUEST['id_trab'] > 0) {
	$t->Load($_REQUEST['id_trab']);
}

if ($_REQUEST['opcion'] == "eliminar") {
	$t = new Trabajo($sesion);
	$t->Load($_REQUEST['id_trabajo']);
	if ($t->Estado() == "Abierto") {
		if (!$t->Eliminar()) {
			$pagina->AddError($t->error);
		}
	}
}

if (UtilesApp::GetConf($sesion, 'CodigoSecundario')) {
	$codigo_cliente_url = 'codigo_cliente_secundario';
	$codigo_asunto_url = 'codigo_asunto_secundario';
} else {
	$codigo_cliente_url = 'codigo_cliente';
	$codigo_asunto_url = 'codigo_asunto';
}

$pagina->titulo = __('Revisar horas');
$pagina->PrintTop();

if ($estado == "") {
	$estado = "abiertos";
}

if ($from == 'cliente') {
	$url_iframe = 'trabajos.php?popup=1&id_usuario=' . $id_usuario . '&' . $codigo_cliente_url . '=' . $codigo_cliente . '&opc=buscar&fecha_ini=' . Utiles::sql2date($fecha_ini) . '&fecha_fin=' . Utiles::sql2date($fecha_fin) . '&id_grupo=' . $id_grupo . '&clientes=' . $clientes . '&usuarios=' . $usuarios;
} else if ($from == 'asunto') {
	$url_iframe = 'trabajos.php?popup=1&id_usuario=' . $id_usuario . '&' . $codigo_cliente_url . '=' . $codigo_cliente . '&' . $codigo_asunto_url . '=' . $codigo_asunto . '&opc=buscar&fecha_ini=' . Utiles::sql2date($fecha_ini) . '&fecha_fin=' . Utiles::sql2date($fecha_fin);
} else if ($from == 'reporte') {

	$url_iframe = 'trabajos.php?popup=1&opc=buscar&from=reporte';
	$url_iframe .= $id_usuario ? "&id_usuario=" . $id_usuario : '';
	$url_iframe .= $usuarios ? "&usuarios=" . $usuarios : '';
	$url_iframe .= $fecha_ini ? "&fecha_ini=" . $fecha_ini : '';
	$url_iframe .= $fecha_fin ? "&fecha_fin=" . $fecha_fin : '';

	if (UtilesApp::GetConf($sesion, 'CodigoSecundario')&& !empty($codigo_cliente)) {
		$query_cod_secundario = "SELECT codigo_cliente_secundario FROM cliente where codigo_cliente = $codigo_cliente";
		$resp = mysql_query($query_cod_secundario) or Utiles::errorSQL($query_cod_secundario, __FILE__, __LINE__);
		list($codigo_cliente_secundario) = mysql_fetch_array($resp);
		$url_iframe .= $codigo_cliente ? '&' . $codigo_cliente_url . '='. $codigo_cliente_secundario : '';
	} else {
		$url_iframe .= $codigo_cliente ? '&' . $codigo_cliente_url . '='. $codigo_cliente : '';
	}

	if (UtilesApp::GetConf($sesion, 'CodigoSecundario')&& !empty($codigo_asunto)) {
		$url_iframe .= $codigo_asunto ? '&' . $codigo_asunto_url . '=' . $codigo_asunto_secundario : '';
	} else {
		$url_iframe .= $codigo_asunto ? '&' . $codigo_asunto_url . '=' . $codigo_asunto : '';
	}

	$url_iframe .= $codigo_asunto ? '&' . $codigo_asunto_url . '=' . $codigo_asunto : '';
	$url_iframe .= $id_grupo_cliente ? "&id_grupo_cliente=" . $id_grupo_cliente : '';
	$url_iframe .= $mes ? "&mes=" . $mes : '';
	$url_iframe .= $estado ? "&estado=" . $estado : '';
	$url_iframe .= $lis_usuarios ? "&lis_usuarios=" . $lis_usuarios : '';
	$url_iframe .= $lis_clientes ? "&lis_clientes=" . $lis_clientes : '';
	$url_iframe .= $campo_fecha ? "&campo_fecha=" . $campo_fecha : '';

	if ($id_cobro) {
		if ($id_cobro != 'Indefinido') {
			$url_iframe .= "&id_cobro=" . $id_cobro;
		}
	}

	$url_iframe .= $solicitante ? "&solicitante=" . $solicitante : '';

} else if ($from == 'horas') {
	$id_usuario = $sesion->usuario->fields['id_usuario'];
	$url_iframe = "trabajos.php?popup=1&id_usuario=" . $id_usuario . '&' . $codigo_cliente_url . '=' . $codigo_cliente . "&opc=buscar";
	if ($id_cobro && $id_cobro != 'Indefinido') {
			$url_iframe .= "&id_cobro=" . $id_cobro;
		}
} else {
	$id_usuario = $sesion->usuario->fields['id_usuario'];
	$url_iframe = "trabajos.php?popup=1&id_usuario=" . $id_usuario . "&motivo=horas";
}

if (UtilesApp::GetConf($sesion, 'UsoActividades')) {
	$url_iframe .= "&glosa_actividad=" . $glosa_actividad;
	if (isset($sin_actividad_definida) && $sin_actividad_definida) {
		$url_iframe .= "&sin_actividad_definida==1";
	}
}

echo "<iframe name='trabajos' class='resizableframe'  id='trabajos' src='" . $url_iframe . "' frameborder=0 width=100% height=2000px></iframe>";
$pagina->PrintBottom();