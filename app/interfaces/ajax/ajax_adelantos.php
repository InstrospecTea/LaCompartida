<?php

require_once dirname(dirname(dirname(__FILE__))) . '/conf.php';

$sesion = new Sesion(array('OFI', 'COB', 'SEC'));
$params_array['codigo_permiso'] = 'COB';
$p_cobranza = $sesion->usuario->permisos->Find('FindPermiso', $params_array);

if ($_REQUEST['accion'] == 'listaadelanto') {
	$limitdesde = isset($_REQUEST['iDisplayStart']) ? $_REQUEST['iDisplayStart'] : '0';
	$limitcantidad = isset($_REQUEST['iDisplayLength']) ? $_REQUEST['iDisplayLength'] : '25';

	$Adelanto = new Adelanto($sesion);
	$Adelanto->Fill($_REQUEST);
	$query = $Adelanto->searchQuery();
	//$query .= " LIMIT {$limitdesde}, {$limitcantidad}";

	try {
		$resp = $sesion->pdodbh->query($query);
		$rows = $sesion->pdodbh->query('SELECT FOUND_ROWS()')->fetch();
	} catch (PDOException $e) {
		if ($sesion->usuario->TienePermiso('SADM')) {
			$Slim = Slim::getInstance('default', true);
			$arrayPDOException = array('File' => $e->getFile(), 'Line' => $e->getLine(), 'Mensaje' => $e->getMessage(), 'Query' => $query, 'Trace' => json_encode($e->getTrace()), 'Parametros' => json_encode($resp));
			$Slim->view()->setData($arrayPDOException);
			$Slim->applyHook('hook_error_sql');
		}

		$resultado = array(
			'iTotalRecords' => 0,
			'iTotalDisplayRecords' => 0,
			'aaData' => array()
		);

		echo json_encode($resultado);
		exit;
	}

	$data = array(
		'iTotalRecords' => $rows[0],
		'iTotalDisplayRecords' => $rows[0],
		'aaData' => array()
	);

	foreach ($resp as $fila) {
		$data['aaData'][] = array($fila[0], $fila[1], $fila[2], $fila[3], $fila[4], $fila[5], $fila[6], $fila[7]);
	}
	echo json_encode(UtilesApp::utf8izar($data));
} else if ($_REQUEST['accion'] == 'desborraadelanto') {
	$p_cobranza = $sesion->usuario->permisos->Find('FindPermiso', $params_array);
	if ($p_cobranza) {
		$documento = new Documento($sesion);
		$id_documento = intval($_POST['id_documento']);
		echo "jQuery('#mensaje').html('recuperando adelanto...'); ";
		if (!$documento->Load($id_documento)) {
			echo "jQuery('#mensaje').html('El adelanto no existe en la base de datos.'); ";
		} else {
			$documento->Edit('es_adelanto', '1');
			$documento->Write();
			echo "jQuery('#boton_buscar').click();";
		}
	}
} else if ($_REQUEST['accion'] == 'borraadelanto') {
	$p_cobranza = $sesion->usuario->permisos->Find('FindPermiso', $params_array);
	if ($p_cobranza) {
		$documento = new Documento($sesion);
		$id_documento = intval($_POST['id_documento']);

		if (!$documento->Load($id_documento)) {
			echo "jQuery('#mensaje').html('El adelanto no existe en la base de datos.'); ";
		} else {
			if ($documento->fields['id_cobro'] || $documento->fields['monto'] != $documento->fields['saldo_pago']) {

				$cadena = implode(';', $documento->fields);
				echo "jQuery('#mensaje').html('El adelanto no puede eliminarse: ha sido utilizado en al menos  " . __('un cobro') . "'); ";
			} else {
				echo "jQuery('#mensaje').html('borrando adelanto...'); ";
				$documento->Edit('es_adelanto', '-1');
				$documento->Write();
			}
		}

		echo "jQuery('#boton_buscar').click();";
	} else {
		echo "jQuery('#mensaje').html('Usted no tiene permiso para eliminar o editar adelantos'); ";
	}
}
