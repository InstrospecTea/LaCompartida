<?php
require_once dirname(__FILE__) . '/../../conf.php';

$sesion = new Sesion(array('ADM','COB','SEC'));
$gasto= new Gasto($sesion);

$limitdesde = isset($_REQUEST['iDisplayStart']) ? $_REQUEST['iDisplayStart'] : '0';
$limitcantidad = isset($_REQUEST['iDisplayLength']) ? $_REQUEST['iDisplayLength'] : '25';
$arrayorden = array(0 => 'fecha', 1 => 'glosa_cliente', 5 => 'egreso', 6 => 'ingreso', 7 => 'con_impuesto', 8 => 'estado', 10 => 'cobrable');
$orden = $arrayorden[intval($_REQUEST['iSortCol_0'])] . " " . $_REQUEST['sSortDir_0'];

if (!isset($where) || (isset($where) && $where == '')) {
	$where = 1;
}
if ($_REQUEST['opc'] == 'contratoasunto') {
	$codigo_asunto = $_REQUEST['codigo_asunto'];
	$data = array('id_contrato' => '');
	if ($codigo_asunto) {
		$contrato = new Contrato($sesion);
		$contrato->LoadByCodigoAsunto($codigo_asunto);
		$data['id_contrato'] = $contrato->fields['id_contrato'];
		if(isset($contrato->fields['codigo_contrato'])){
			$data['codigo_contrato'] = $contrato->fields['codigo_contrato'];
		}
	}
	echo json_encode($data);
	exit;
}
if ($_REQUEST['opc'] == 'actualizagastos') {

	$whereclause = base64_decode($_POST['whereclause']);
	$querypreparar = "UPDATE cta_corriente
						JOIN asunto using (codigo_asunto)
						JOIN contrato on contrato.id_contrato=asunto.id_contrato
						JOIN cliente on contrato.codigo_cliente=asunto.codigo_cliente
						LEFT JOIN cobro on cta_corriente.id_cobro=cobro.id_cobro ";

	$setclause = ' set cta_corriente.fecha_touch=now() ';
	if (isset($_POST['montocastigar'])) {
		$setclause.=', cta_corriente.monto_cobrable=0';
	}
	if (isset($_POST['id_proveedor']) && intval($_POST['id_proveedor'] > 0)) {
		$setclause.=', cta_corriente.id_proveedor=' . intval($_POST['id_proveedor']);
	}
	if (isset($_POST['codigo_asunto']) && $_POST['codigo_asunto'] != '') {
		$setclause.=", cta_corriente.codigo_asunto='{$_POST['codigo_asunto']}'";
	} else if (!empty($_POST['codigo_asunto_secundario'])) {
		$setclause.=", cta_corriente.codigo_asunto='{$_POST['codigo_asunto_secundario']}'";
	}


	$querypreparar.=$setclause . ' WHERE ' . $whereclause;

	debug($querypreparar);
	$query = $sesion->pdodbh->prepare($querypreparar);

	$query->execute();
	echo "jQuery('#boton_buscar').click();";
	die();
} else if ($_REQUEST['opc'] == 'buscar' || ($_GET['opclistado'] == 'listado' && $_GET['selectodos'] == 1) || $_GET['totalctacorriente'] == 1) {
	if ($where == 1) {
		$where=$gasto->WhereQuery($_REQUEST);
	} else {
		$where = base64_decode($where);
	}

	$idioma_default = new Objeto($sesion, '', '', 'prm_idioma', 'codigo_idioma');
	$idioma_default->Load(strtolower(UtilesApp::GetConf($sesion, 'Idioma')));

	$col_select = " ,if(cta_corriente.cobrable = 1,'Si','No') as esCobrable ";

	
}


$cobrosnoeditables = array();
if ($_GET['totalctacorriente']) { ?>
	<form id="buscacliente" method="POST" action="seguimiento_cobro.php" target="_blank">
		<b><?php
			echo __('Balance cuenta gastos'); ?>: <?php echo UtilesApp::GetSimboloMonedaBase($sesion);

			if ($where == 1) {
				$where=$gasto->WhereQuery($_REQUEST);
			} else {
				$where = base64_decode($where);
			}
			$balance = $gasto::TotalCuentaCorriente($sesion, $where, $cobrable, true);
			if ($codigo_cliente_secundario || $codigo_cliente) { ?>
				<input type="hidden" id="codcliente" name="codcliente" value="1"/>
			<?php } else { ?>
				<input type="hidden" id="codcliente" name="codcliente" value="0"/>
			<?php }
			if (is_array($balance)) {
				echo number_format($balance[0], 0, $idioma_default->fields['separador_decimales'], $idioma_default->fields['separador_miles']); ?>
				<input type="hidden" id="codigo_cliente" name="codigo_cliente" value="<?php echo $codigo_cliente; ?>"/>
				<input type="hidden" id="codigo_cliente_secundario" name="codigo_cliente_secundario" value="<?php echo $codigo_cliente_secundario; ?>"/>
				<input type="hidden" id="festado" name="estado[]" value="CREADO"/>
				<input type="hidden" id="op" name="opc" value="buscar"/>
				<input type="hidden" id="ffin" name="fecha_fin" value="<?php echo date('d-m-Y'); ?>"/>
				<input type="hidden"  name="balance" 	value="<?php echo $balance[0]; ?>"/>
				<input type="hidden"  name="ingreso" 	value="<?php echo $balance[1]; ?>"/>
				<input type="hidden"  name="egreso" 	value="<?php echo $balance[2]; ?>"/>
				<input type="hidden"  name="borrador" 	value="<?php echo $balance[3]; ?>"/>
				
			<?php } else {
				echo number_format($balance, 0, $idioma_default->fields['separador_decimales'], $idioma_default->fields['separador_miles']);
			}?>
		</b>
	</form>
	<?php
	exit;
} else if ($_GET['opclistado'] == 'listado') { ?>
	<form id="form_edita_gastos_masivos">
		<table id="overlayeditargastos">
			<?php
			if ($_GET['selectodos'] == 1) {
				$where.="  AND (cobro.estado is null or cobro.estado in ('SIN COBRO','CREADO','EN REVISION'))";
			} else {
				$arraygasto = explode(';', ($_GET['movimientos']));
				if (sizeof($arraygasto) > 0) {
					$where = " ( cobro.estado is null or cobro.estado in ('SIN COBRO','CREADO','EN REVISION') ) and id_movimiento in (" . implode(',', $arraygasto) . ")  ";
				}
			}

			$querypreparar = "update cta_corriente
								JOIN asunto using(codigo_asunto)
								JOIN contrato on contrato.id_contrato=asunto.id_contrato
								JOIN cliente on contrato.codigo_cliente=asunto.codigo_cliente
								LEFT JOIN cobro on cta_corriente.id_cobro=cobro.id_cobro
											set fecha_touch=now()
									WHERE $where";


			if (UtilesApp::GetConf($sesion, 'TipoGasto') && $prov == 'false') {
				?>
				<tr>
					<td align=right>
						<?php echo __('Tipo de Gasto') ?>
					</td>
					<td align=left>
						<?php echo Html::SelectQuery($sesion, "SELECT id_cta_corriente_tipo, glosa FROM prm_cta_corriente_tipo", "id_cta_corriente_tipo", '1', '', '', "160"); ?>
					</td>
				</tr>
			<?php } ?>
			<tr>
				<td align="right">
					<?php echo __('Proveedor') ?>
				</td>
				<td align="left">
					<?php echo Html::SelectQuery($sesion, "SELECT id_proveedor, glosa FROM prm_proveedor ORDER BY glosa", "id_proveedor", '0', '', 'Cualquiera', "160"); ?>
				</td>
			</tr>
			<tr>
				<td align="right">
					<?php echo 'Castigar ' . __('Monto') ?>
				</td>
				<td align="left">
					<input name="montocastigar" id="montocastigar" type="checkbox"   value="0" />
					<span style="color:#777; font-size:10px"> (Al activar se baja el monto <?php echo __('cobrable'); ?> a cero para todos los gastos seleccionados)</span>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<input type="hidden" size="100" id="whereclause" name="whereclause" value="<?php echo base64_encode($where); ?>"/>
					<p>S&oacute;lo se modificar&aacute; los gastos que no pertenezcan a <?php echo __('Cobros emitidos'); ?></p>
				</td>
			</tr>
		</table>
	</form>
<?php
} else if ($_REQUEST['opc'] == 'buscar') {

	$selectfrom = $gasto::SelectFromQuery();

				
	$query = $gasto->SearchQuery($sesion,$where." order by $orden 	LIMIT $limitdesde,$limitcantidad",$col_select);


	$selectcount = "SELECT COUNT(*) FROM $selectfrom 	WHERE $where ";
//echo $selectcount;



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
			'iTotalRecords' => $rows[0],
			'iTotalDisplayRecords' => $rows[0],
			'aaData' => array()
		);
		echo json_encode($resultado);
		exit;
	}


	$i = 0;
	/*  $resp = mysql_query($query, $sesion->dbh);
	  $rows=mysql_fetch_row(mysql_query('SELECT FOUND_ROWS()', $sesion->dbh)); */


	$resultado = array(
		'iTotalRecords' => $rows[0],
		'iTotalDisplayRecords' => $rows[0],
		'aaData' => array()
	);
	$mas = 0;
	foreach ($resp as $fila) {
		$stringarray = array(
/*0*/			$fila['id_movimiento'],
/*1*/			date('d-m-Y', strtotime($fila['fecha'])),
/*2*/			$fila['numero_ot'],
/*3*/			$fila['glosa_cliente']  ? utf8_encode($fila['codigo_cliente'].'|'.$fila['glosa_cliente']) : ' - ',
/*4*/			$fila['glosa_asunto'] ? utf8_encode($fila['glosa_asunto']) : ' - ',
/*5*/			$fila['descripcion'] ? utf8_encode($fila['descripcion']) : ' ',
/*6*/			$fila['ingresooegreso']=='egreso' ? $fila['simbolo'] . ' ' . $fila['monto_cobrable']:' ',
/*7*/			$fila['ingresooegreso']=='ingreso' ? $fila['simbolo'] . ' ' . $fila['ingreso'] : ' ',
/*8*/			$fila['con_impuesto'] ? $fila['con_impuesto'] : ' ',
/*9*/			$fila['estado_cobro'] ? $fila['estado_cobro'] : ' ',
/*10*/			$fila['id_cobro'] ? $fila['id_cobro'] : ' ',
/*11*/			$fila['esCobrable'] ? $fila['esCobrable'] : 'No',
/*12*/			$fila['contrato_activo'] ? $fila['contrato_activo'] : ' ',
/*13*/			$fila['ingresooegreso']=='egreso' ? $fila['simbolo'] . ' ' . $fila['egreso'] : ' ',
/*14*/			$fila['id_contrato'],
/*15*/			$fila['tipo'] ? $fila['tipo'] : ' - '
		);
		$resultado['aaData'][] = $stringarray;
		$mas += $fila['egreso'];
		$mas -= $fila['ingreso'];
	}
	echo json_encode($resultado);
}

function Monto(& $fila) {
	global $sesion;
	$idioma = new Objeto($sesion, '', '', 'prm_idioma', 'codigo_idioma');
	if ($fila->fields['codigo_idioma'] != '') {
		$idioma->Load($fila->fields['codigo_idioma']);
	} else {
		$idioma->Load(strtolower(UtilesApp::GetConf($sesion, 'Idioma')));
	}
	return $fila->fields['egreso'] > 0 ? $fila->fields[simbolo] . " " . number_format($fila->fields['monto_cobrable'], $fila->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) : '';
}

function removeBOM($string) {
	return str_replace(array('\n', "\n"), '', $string);
}

function Ingreso(& $fila) {
	global $sesion;
	$idioma = new Objeto($sesion, '', '', 'prm_idioma', 'codigo_idioma');
	if ($fila->fields['codigo_idioma'] != '') {
		$idioma->Load($fila->fields['codigo_idioma']);
	} else {
		$idioma->Load(strtolower(UtilesApp::GetConf($sesion, 'Idioma')));
	}
	return $fila->fields['ingreso'] > 0 ? $fila->fields['simbolo'] . " " . number_format($fila->fields['monto_cobrable'], $fila->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) : '';
}
