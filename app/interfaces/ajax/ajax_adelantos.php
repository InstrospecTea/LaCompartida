<?php

require_once dirname(dirname(dirname(__FILE__))) . '/conf.php';

$sesion = new Sesion(array('OFI', 'COB', 'SEC'));
$params_array['codigo_permiso'] = 'COB';
$p_cobranza = $sesion->usuario->permisos->Find('FindPermiso', $params_array);

function formatofecha($fechasucia) {
	$fechasucia = explode('-', str_replace('/', '-', $fechasucia));
	$fechalimpia = intval($fechasucia[2] . $fechasucia[1] . $fechasucia[0]);
	return $fechalimpia;
}

if ($_REQUEST['accion'] == 'listaadelanto') {
	$limitdesde = isset($_REQUEST['iDisplayStart']) ? $_REQUEST['iDisplayStart'] : '0';
	$limitcantidad = isset($_REQUEST['iDisplayLength']) ? $_REQUEST['iDisplayLength'] : '25';

	if (empty($where)) {
		$where = 1;
	}
	$where .= ' AND ' . (isset($_GET['eliminados']) ? 'es_adelanto = -1' : 'es_adelanto = 1');
	if (isset($_GET['tiene_saldo']) && $_GET['tiene_saldo'] == 1) {
		$where .= ' AND saldo_pago < 0 ';
	}
	if (!empty($_GET['id_documento'])) {
		$where .= ' AND documento.id_documento = ' . intval($_GET['id_documento']);
	}
	if (!empty($_GET['campo_codigo_asunto'])) {
		$where .= " AND asuntos.codigo_asuntos like '%{$_GET['campo_codigo_asunto']}%'";
	}
	if (!empty($_GET['codigo_cliente'])) {
		$where .= " AND cliente.codigo_cliente = '{$_GET['codigo_cliente']}' ";
	}
	if (!empty($_GET['id_contrato'])) {
		$where .= " AND (documento.id_contrato = '" . intval($_GET['id_contrato']) . "' OR documento.id_contrato IS NULL)";
	}

	if (!empty($_GET['fecha1'])) {
		$where .= ' AND documento.fecha >= ' . formatofecha($_GET['fecha1']);
	}
	if (!empty($_GET['fecha2'])) {
		$where .= ' AND documento.fecha <=  ' . formatofecha($_GET['fecha2']);
	}
	if (!empty($_GET['moneda_adelanto'])) {
		$where .= ' AND documento.id_moneda = ' . intval($_GET['moneda']);
	}

	$selectfrom = "FROM documento
		JOIN prm_moneda ON prm_moneda.id_moneda = documento.id_moneda
		JOIN cliente ON documento.codigo_cliente = cliente.codigo_cliente
		left join (
			SELECT codigo_cliente, id_contrato, GROUP_CONCAT( glosa_asunto ) AS glosa_asuntos
			FROM asunto
			GROUP BY id_contrato, codigo_cliente
		) asuntos on documento.codigo_cliente = asuntos.codigo_cliente and (documento.id_contrato = asuntos.id_contrato)
	WHERE $where";

	$query = "SELECT
		documento.id_documento,
		cliente.glosa_cliente,
		documento.fecha,
		IF(documento.id_contrato is null, 'Todos los Asuntos',  asuntos.glosa_asuntos) as asuntos,
		IF(documento.monto = 0, 0, documento.monto*-1) AS monto,
		IF(documento.saldo_pago = 0, 0, documento.saldo_pago*-1) AS saldo_pago,
		documento.glosa_documento,
		prm_moneda.id_moneda
		$selectfrom
		LIMIT $limitdesde,$limitcantidad";

	$selectcount = "SELECT COUNT(*) $selectfrom ";

	try {
		$rows = $sesion->pdodbh->query($selectcount)->fetch();
		$resp = $sesion->pdodbh->query($query);
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
		$data['aaData'][] = array(
			$fila[0],
			$fila[1],
			$fila[2],
			$fila[3],
			$fila[4],
			$fila[5],
			$fila[6],
			$fila[7]
		);
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
				//$documento->Delete();
				$documento->Edit('es_adelanto', '-1');
				$documento->Write();
			}
		}

		echo "jQuery('#boton_buscar').click();";
	} else {
		echo "jQuery('#mensaje').html('Usted no tiene permiso para eliminar o editar adelantos'); ";
	}
}

