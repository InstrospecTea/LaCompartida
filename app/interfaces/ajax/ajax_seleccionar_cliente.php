<?php
require_once dirname(__FILE__).'/../../conf.php';

$sesion = new Sesion();
$pedazo = strtolower(utf8_decode(addslashes($_POST['term'])));

$id = Conf::GetConf($sesion,'CodigoSecundario') ? 'codigo_cliente_secundario':'codigo_cliente';
$id_usuario = empty($_POST['id_usuario']) ? $sesion->usuario->fields['id_usuario']:$_POST['id_usuario'];

if (empty($pedazo)) {
	$query = "(
			SELECT DISTINCT
				cliente.{$id} AS id,
				cliente.glosa_cliente AS value
			FROM trabajo
			INNER JOIN asunto USING (codigo_asunto)
			INNER JOIN cliente USING (codigo_cliente)
			WHERE trabajo.id_usuario = {$id_usuario}
			ORDER BY trabajo.fecha DESC
			LIMIT 0, 5
		) UNION (
			SELECT DISTINCT
				cliente.{$id},
				cliente.glosa_cliente
			FROM asunto
			INNER JOIN cliente USING (codigo_cliente)
			ORDER BY asunto.fecha_creacion DESC
			LIMIT 0, 5
		)";

} else {
	$query = "SELECT {$id} AS id, glosa_cliente AS value
		FROM cliente
		WHERE activo = 1 AND lcase(glosa_cliente) LIKE '%$pedazo%'
		ORDER BY glosa_cliente
		LIMIT 20";
}

$resp = $sesion->pdodbh->query($query)->fetchAll(PDO::FETCH_ASSOC);

if (count($resp) > 0) {
	echo json_encode(UtilesApp::utf8izar($resp));
} else {
	echo json_encode(array('id' => 'cualquiera', 'value' => __('Cualquiera')));
}
