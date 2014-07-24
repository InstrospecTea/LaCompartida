<?php
require_once dirname(__FILE__) . '/../../conf.php';

$sesion = new Sesion();

$asuntos = array();
$codigo_secundario = Conf::GetConf($sesion, 'CodigoSecundario') == '0' ? false : true;

$id_usuario = isset($_POST['id_usuario']) ? $_POST['id_usuario'] : $sesion->usuario->fields['id_usuario'];
$glosa_asunto = isset($_POST['glosa_asunto']) ? strtolower(utf8_decode($_POST['glosa_asunto'])) : null;
$codigo_cliente = isset($_POST['codigo_cliente']) ? $_POST['codigo_cliente'] : null;

$campo_codigo_asunto = $codigo_secundario ? 'codigo_asunto_secundario' : 'codigo_asunto';
$campo_codigo_cliente = $codigo_secundario ? 'codigo_cliente_secundario' : 'codigo_cliente';

if (empty($glosa_asunto)) 	{
	$query = "(
		SELECT DISTINCT asunto.{$campo_codigo_asunto} AS id, asunto.glosa_asunto AS value
		FROM trabajo
			JOIN asunto using (codigo_asunto)
		WHERE trabajo.id_usuario = {$id_usuario}
		ORDER BY trabajo.fecha DESC
		LIMIT 0,5
	)
	UNION
	(
		SELECT DISTINCT asunto.{$campo_codigo_asunto} AS id, asunto.glosa_asunto AS value
		FROM asunto
		ORDER BY asunto.fecha_creacion DESC
		LIMIT 0,5
	)";
} else	{
	$query_filter = 1;

	$tabla = 'asunto';
	$fields = "asunto.{$campo_codigo_asunto} AS id, asunto.glosa_asunto AS value";
	$join = '';
	if ($codigo_secundario) {
		$join = "INNER JOIN cliente ON cliente.codigo_cliente = asunto.codigo_cliente";
		$tabla = 'cliente';
	}

	if ($codigo_cliente) {
		$query_filter = "{$tabla}.{$campo_codigo_cliente} = '{$codigo_cliente}'";
	}
	
	$query = "SELECT $fields
		FROM asunto
		$join
		WHERE asunto.activo = 1 AND LOWER(asunto.glosa_asunto) LIKE '%{$glosa_asunto}%' AND {$query_filter}
		ORDER BY asunto.glosa_asunto
		LIMIT 10";
}

$resp = $sesion->pdodbh->query($query)->fetchAll(PDO::FETCH_ASSOC);

if (!empty($resp)) {
	$asuntos = UtilesApp::utf8izar($resp);
}

echo json_encode($asuntos);
