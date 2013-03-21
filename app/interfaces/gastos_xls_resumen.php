<?php

require_once dirname(__FILE__) . '/../conf.php';

require_once 'Spreadsheet/Excel/Writer.php';

$sesion = new Sesion(array('OFI', 'COB'));
$pagina = new Pagina($sesion);
$gasto =new Gasto($sesion);

#$key = substr(md5(microtime().posix_getpid()), 0, 8);

$wb = new Spreadsheet_Excel_Writer();


$query = "SELECT id_moneda, simbolo, cifras_decimales FROM prm_moneda WHERE moneda_base=1";
$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
list($id_moneda, $simbolo_moneda, $cifras_decimales) = mysql_fetch_array($resp);

if ($cifras_decimales) {
    $decimales = '.';
    while ($cifras_decimales--)
	$decimales .= '#';
}
else
    $decimales = '';

$wb->setCustomColor(35, 220, 255, 220);
$wb->setCustomColor(36, 255, 255, 220);
$formato_encabezado = & $wb->addFormat(array('Size' => 12,
	    'VAlign' => 'top',
	    'Align' => 'justify',
	    'Bold' => '1',
	    'Color' => 'black'));
$formato_moneda_encabezado = & $wb->addFormat(array('NumFormat' => "[$$simbolo_moneda] #,###,0$decimales",
	    'Size' => 12,
	    'VAlign' => 'top',
	    'Align' => 'justify',
	    'Bold' => '1',
	    'Color' => 'black'));
$formato_titulo = & $wb->addFormat(array('Size' => 12,
	    'VAlign' => 'top',
	    'Align' => 'justify',
	    'Bold' => '1',
	    'Locked' => 1,
	    'Border' => 1,
	    'FgColor' => '35',
	    'Color' => 'black'));
$formato_normal = & $wb->addFormat(array('Size' => 10,
	    'VAlign' => 'top',
	    'Align' => 'justify',
	    'Border' => 1,
	    'Color' => 'black'));
$formato_moneda = & $wb->addFormat(array('NumFormat' => "[$$simbolo_moneda] #,###,0$decimales",
	    'Border' => 1,
	    'Size' => 10,
	    'Align' => 'right'));
$formato_total = & $wb->addFormat(array('Size' => 10,
	    'VAlign' => 'top',
	    'Align' => 'justify',
	    'Bold' => '1',
	    'Border' => 1,
	    'Color' => 'black'));
$formato_moneda_total = & $wb->addFormat(array('NumFormat' => "[$$simbolo_moneda] #,###,0$decimales",
	    'Border' => 1,
	    'Bold' => '1',
	    'Size' => 10,
	    'Align' => 'right'));

$ws1 = & $wb->addWorksheet(__('Reportes'));
$ws1->setInputEncoding('utf-8');
$ws1->fitToPages(1, 0);
$ws1->setZoom(75);

// se setea el ancho de las columnas
$ws1->setColumn(0, 0, 10);
$ws1->setColumn(1, 1, 30);
$ws1->setColumn(2, 2, 30);
$ws1->setColumn(3, 3, 30);
$ws1->setColumn(4, 4, 30);
$ws1->setColumn(4, 5, 30);
if (UtilesApp::GetConf($sesion, 'UsarGastosCobrable')) {
    $ws1->setColumn(4, 6, 10);
    $ws1->setColumn(4, 7, 30);
    if (UtilesApp::GetConf($sesion, 'MostrarMontosPorCobrar')) {
	$ws1->setColumn(4, 8, 30);
    }
} else {
    $ws1->setColumn(4, 6, 30);
    if (UtilesApp::GetConf($sesion, 'MostrarMontosPorCobrar')) {
	$ws1->setColumn(4, 7, 30);
    }
}

$ws1->write(0, 1, __('Resumen de gastos'), $formato_encabezado);
$ws1->mergeCells(0, 1, 0, 6);

$columna_cliente = 1;
$columna_egreso = 2;
if (UtilesApp::GetConf($sesion, 'UsaMontoCobrable')) {
    $columna_egreso_cobrable = 3;
    $columna_ingreso = 4;
    $columna_ingreso_cobrable = 5;
    if (UtilesApp::GetConf($sesion, 'UsarGastosCobrable')) {
	$columna_es_cobrable = 6;
	$columna_balance = 7;
    } else {
	$columna_balance = 6;
    }
} else {
    $columna_ingreso = 3;
    $columna_balance = 4;
}

if (UtilesApp::GetConf($sesion, 'MostrarMontosPorCobrar')) {
    $columna_gastos_por_cobrar = $columna_balance + 1;
}

if ($codigo_cliente) {
    $info_usr1 = str_replace('<br>', ' - ', 'Cliente: ' . Utiles::Glosa($sesion, $codigo_cliente, 'glosa_cliente', 'cliente', 'codigo_cliente'));
    $ws1->write(2, 1, utf8_decode($info_usr1), $formato_encabezado);
    $ws1->mergeCells(2, 1, 2, 4);
}
if ($codigo_asunto) {
    $info_usr = str_replace('<br>', ' - ', 'Asunto: ' . Utiles::Glosa($sesion, $codigo_asunto, 'glosa_asunto', 'asunto', 'codigo_asunto'));
    $ws1->write(3, 1, utf8_decode($info_usr), $formato_encabezado);
    $ws1->mergeCells(3, 1, 3, 4);
}
########################### SQL INFORME DE GASTOS #########################
$where = $gasto->WhereQuery($_REQUEST);
$join_extra = "";


$moneda_base = Utiles::MonedaBase($sesion);
$moneda = new Moneda($sesion);
$total_balance_egreso = 0;
$total_balance_ingreso = 0;
$total_balance_egreso_cobrable = 0;
$total_balance_ingreso_cobrable = 0;

if (UtilesApp::GetConf($sesion, 'MostrarMontosPorCobrar')) {
    $total_gastos_por_cobrar = 0;
    $total_gastos_por_cobrar_cliente = 0;
    $total_gastos_facturados = 0;  /* en realidad son "facturados" */
    $total_gastos_facturados_cliente = 0;
    $id_cobro_anterior = 0;
    $acumulado_factura_cobro_anterior = 0;
}

$filas = 7;
$fila_inicio = 7;

$ws1->write($filas, $columna_cliente, __('Cliente'), $formato_titulo);
$ws1->write($filas, $columna_egreso, __('Egreso'), $formato_titulo);
if (UtilesApp::GetConf($sesion, 'UsaMontoCobrable'))
    $ws1->write($filas, $columna_egreso_cobrable, __('Monto cobrable egreso'), $formato_titulo);
$ws1->write($filas, $columna_ingreso, __('Ingreso'), $formato_titulo);
if (UtilesApp::GetConf($sesion, 'UsaMontoCobrable'))
    $ws1->write($filas, $columna_ingreso_cobrable, __('Monto cobrable ingreso'), $formato_titulo);
if (UtilesApp::GetConf($sesion, 'UsarGastosCobrable'))
    $ws1->write($filas, $columna_es_cobrable, __('Cobrable'), $formato_titulo);
$ws1->write($filas, $columna_balance, __('Balance'), $formato_titulo);
if (UtilesApp::GetConf($sesion, 'MostrarMontosPorCobrar')) {
    $ws1->write($filas, $columna_gastos_por_cobrar, __('Monto Por Facturar'), $formato_titulo);
}
$filas++;
$flag_sin_orden_previo = 0;
if ($orden == "") {
    $orden = " cliente.glosa_cliente ASC, cta_corriente.codigo_cliente ASC ";
    $flag_sin_orden_previo = 1;
}

$col_select = "";
if (UtilesApp::GetConf($sesion, 'UsaMontoCobrable') || UtilesApp::GetConf($sesion, 'MostrarMontosPorCobrar')) {
    $col_select = " ,if(cta_corriente.cobrable = 1,'Si','No') as esCobrable ";
}

if (UtilesApp::GetConf($sesion, 'MostrarMontosPorCobrar')) {
    $col_select .= ", cobro.estado as estado_cobro, 
					
					( SELECT SUM(subtotal_gastos * cmf.tipo_cambio / cmb.tipo_cambio ) + SUM(subtotal_gastos_sin_impuesto * cmf.tipo_cambio / cmb.tipo_cambio) as stgastosfactura 
						FROM factura 
							JOIN cobro_moneda cmf ON (factura.id_cobro = cmf.id_cobro AND factura.id_moneda = cmf.id_moneda )
							JOIN cobro_moneda cmb ON (factura.id_cobro = cmb.id_cobro AND cmb.id_moneda = {$moneda_base['id_moneda']} )
						WHERE factura.id_cobro = cta_corriente.id_cobro AND cta_corriente.id_cobro IS NOT NULL AND ( factura.estado != 'ANULADA' OR factura.id_estado = 1 ) 
	) as acumulado_factura";
    $join_extra .= "LEFT JOIN cobro_moneda as moneda_gastos_segun_cobro ON moneda_gastos_segun_cobro.id_cobro = cobro.id_cobro AND moneda_gastos_segun_cobro.id_moneda = cta_corriente.id_moneda ";
    $orden .= ", cta_corriente.id_cobro ASC ";
}

if ($flag_sin_orden_previo) {
    $orden .=", fecha DESC ";
}
$query=$gasto->SearchQuery($sesion, $where.'  ORDER BY '.$orden,  $col_select,$join_extra);

 
 if($_GET['query']=='query') die($query);
 $gastos=$sesion->pdodbh->query($query)->fetchAll(PDO::FETCH_ASSOC);


$primergasto=$gastos[0];

/*$egreso = $primergasto['egreso'] * $primergasto['tipo_cambio_segun_cobro'] / $moneda_base['tipo_cambio'];
$ingreso = $primergasto['ingreso'] * $primergasto['tipo_cambio_segun_cobro'] / $moneda_base['tipo_cambio'];

if ($egreso > 0) {
    $egreso_cobrable = $primergasto['monto_cobrable_moneda_base'];
}
if ($ingreso > 0) {
    $ingreso_cobrable = $primergasto['monto_cobrable_moneda_base'];
}*/
$nombre_cliente_anterior = $primergasto['glosa_cliente'];
$codigo_cliente_anterior = $primergasto['codigo_cliente'];
$acumulado_factura_cobro_anterior = !empty($primergasto['acumulado_factura']) ? $primergasto['acumulado_factura'] : 0;
$id_cobro_anterior = !empty($primergasto['id_cobro']) ? $primergasto['id_cobro'] : 0;

$col_egreso_para_formula = Utiles::NumToColumnaExcel($columna_egreso);
$col_egreso_cobrable_para_formula = Utiles::NumToColumnaExcel($columna_egreso_cobrable);
$col_ingreso_para_formula = Utiles::NumToColumnaExcel($columna_ingreso);
$col_ingreso_cobrable_para_formula = Utiles::NumToColumnaExcel($columna_ingreso_cobrable);
$col_balance_para_formula = Utiles::NumToColumnaExcel($columna_balance);

if (UtilesApp::GetConf($sesion, 'MostrarMontosPorCobrar')) {
    $col_gastos_por_cobrar_para_formula = Utiles::NumToColumnaExcel($columna_gastos_por_cobrar);
}

foreach($gastos as $gasto) {
     $columna_actual = 0;
     //echo '<pre><h2>'.$nombre_cliente_anterior.'</h2>';print_r($gasto);echo '</pre>'; 
    

 
    if (UtilesApp::GetConf($sesion, 'MostrarMontosPorCobrar')) {
	$gastos_por_cobrar = $gasto['estado_cobro']=='PAGADO'? 0 : $gasto['ingresooegreso'] == 'egreso' ? $gasto['monto_cobrable_moneda_base'] : (-1 * $gasto['monto_cobrable_moneda_base'] );
    }
    if ($gasto['ingresooegreso'] == 'egreso'  ) {
		$total_balance_egreso +=($gasto['egreso'] * $gasto['tipo_cambio_segun_cobro']) / $moneda_base['tipo_cambio'];
		$total_balance_egreso_cobrable +=($gasto['monto_cobrable_moneda_base']);
		if($gasto['estado_cobro']=='PAGADO') {
				$total_balance_ingreso +=($gasto['egreso'] * $gasto['tipo_cambio_segun_cobro']) / $moneda_base['tipo_cambio'];
				$total_balance_ingreso_cobrable +=($gasto['monto_cobrable_moneda_base']);
		}

    }

    if ($gasto['ingresooegreso'] == 'ingreso') {
    	if($gasto['estado_cobro']!='PAGADO') {
			$total_balance_ingreso +=($gasto['ingreso'] * $gasto['tipo_cambio_segun_cobro']) / $moneda_base['tipo_cambio'];
			$total_balance_ingreso_cobrable +=($gasto['monto_cobrable_moneda_base']);
		}
    }
    
    if ($codigo_cliente_anterior == $gasto['codigo_cliente']) {
		if ($gasto['ingresooegreso'] == 'egreso') {
			$egreso += (double) ($gasto['egreso'] * $gasto['tipo_cambio_segun_cobro'] / $moneda_base['tipo_cambio']);
			$egreso_cobrable += (double) ($gasto['monto_cobrable_moneda_base']);
			if($gasto['estado_cobro']=='PAGADO') {
			    $ingreso += (double) ($gasto['egreso'] * $gasto['tipo_cambio_segun_cobro'] / $moneda_base['tipo_cambio']);
			    $ingreso_cobrable += (double) ($gasto['monto_cobrable_moneda_base']);
			}
		}
		if ($gasto['ingresooegreso'] == 'ingreso') {
			if($gasto['estado_cobro']!='PAGADO') {
			    $ingreso += (double) ($gasto['ingreso'] * $gasto['tipo_cambio_segun_cobro'] / $moneda_base['tipo_cambio']);
			    $ingreso_cobrable += (double) ($gasto['monto_cobrable_moneda_base']);
			}
		}

		if (UtilesApp::GetConf($sesion, 'MostrarMontosPorCobrar')) {
		    if ($gasto['esCobrable'] == 'Si') {
			if ($gasto['estado_cobro'] == ''
				|| $gasto['estado_cobro'] == 'CREADO'
				|| $gasto['estado_cobro'] == 'EN REVISION') {
			    $total_gastos_por_cobrar_cliente += (double) ( $gastos_por_cobrar * $gasto['tipo_cambio_segun_cobro'] / $moneda_base['tipo_cambio']);
			    $total_gastos_por_cobrar += (double) ( $gastos_por_cobrar * $gasto['tipo_cambio_segun_cobro'] / $moneda_base['tipo_cambio']);
			} elseif ($gasto['estado_cobro'] == 'EMITIDO'
				|| $gasto['estado_cobro'] == 'FACTURADO'
				|| $gasto['estado_cobro'] == 'ENVIADO AL CLIENTE'
				|| $gasto['estado_cobro'] == 'PAGO PARCIAL'
				 ) {
			    $total_gastos_por_cobrar_cliente += (double) ( $gastos_por_cobrar * $gasto['tipo_cambio_segun_cobro'] / $moneda_base['tipo_cambio']);
			    $total_gastos_por_cobrar += (double) ( $gastos_por_cobrar * $gasto['tipo_cambio_segun_cobro'] / $moneda_base['tipo_cambio']);
			} else if($gasto['estado_cobro'] == 'PAGADO'
				|| $gasto['estado_cobro'] == 'INCOBRABLE') 	 {
				 $total_gastos_por_cobrar_cliente +=0;
				  $total_gastos_por_cobrar += 0;
			    }

		    }
	}

			/*
			 * esto va afuera, por que si el ultimo gasto para el cobro revisado es "no cobrable", 
			 * omitir�a descontar los gastos de facturas con pago parcial o facturadas 			 * 
			 */
			if ($gasto['id_cobro'] != $id_cobro_anterior && $gasto['estado_cobro']!='PAGADO') {
			    $total_gastos_por_cobrar_cliente -= ( $acumulado_factura_cobro_anterior );
			    $total_gastos_por_cobrar -= ( $acumulado_factura_cobro_anterior );
			    $acumulado_factura_cobro_anterior = $gasto['acumulado_factura'];
			    $id_cobro_anterior = $gasto['id_cobro'];
			}
    } else {
	$ws1->write($filas, $columna_cliente, $nombre_cliente_anterior, $formato_normal);
	$ws1->writeNumber($filas, $columna_egreso, $egreso, $formato_moneda);
	$ws1->writeNumber($filas, $columna_ingreso, $ingreso, $formato_moneda);

	if (UtilesApp::GetConf($sesion, 'UsarGastosCobrable')) {
	    $ws1->write($filas, $columna_es_cobrable, $gasto['esCobrable'], $formato_moneda);

	    if ($gasto['esCobrable'] == 'No') {
			if (UtilesApp::GetConf($sesion, 'UsaMontoCobrable')) {
			    $ws1->writeNumber($filas, $columna_egreso_cobrable, 0, $formato_moneda);
 
			    $ws1->writeNumber($filas, $columna_ingreso_cobrable, 0, $formato_moneda);
			}

			$ws1->writeFormula($filas, $columna_balance, "=$col_balance_para_formula" . ($filas), $formato_moneda);
	    } else {
			if (UtilesApp::GetConf($sesion, 'UsaMontoCobrable')) {
			    $ws1->writeNumber($filas, $columna_egreso_cobrable, $egreso_cobrable, $formato_moneda);
	 
			    $ws1->writeNumber($filas, $columna_ingreso_cobrable, $ingreso_cobrable, $formato_moneda);
	 
			    $ws1->writeFormula($filas, $columna_balance, "=$col_ingreso_cobrable_para_formula" . ($filas + 1) . " - $col_egreso_cobrable_para_formula" . ($filas + 1), $formato_moneda);
			} else {
			    $ws1->writeFormula($filas, $columna_balance, "=$col_ingreso_para_formula" . ($filas + 1) . " - $col_egreso_para_formula" . ($filas + 1), $formato_moneda);
			}
	    }

	} else {
		    if (UtilesApp::GetConf($sesion, 'UsaMontoCobrable')) {
				$ws1->writeNumber($filas, $columna_egreso_cobrable, $egreso_cobrable, $formato_moneda);
		 
				$ws1->writeNumber($filas, $columna_ingreso_cobrable, $ingreso_cobrable, $formato_moneda);
		 
				$ws1->writeFormula($filas, $columna_balance, "=$col_ingreso_cobrable_para_formula" . ($filas + 1) . " - $col_egreso_cobrable_para_formula" . ($filas + 1), $formato_moneda);

		    } else {

				$ws1->writeFormula($filas, $columna_balance, "=$col_ingreso_para_formula" . ($filas + 1) . " - $col_egreso_para_formula" . ($filas + 1), $formato_moneda);
		    }
	}

	if ($gasto['id_cobro'] != $id_cobro_anterior) {
	    $total_gastos_por_cobrar_cliente -= ( $acumulado_factura_cobro_anterior );
	    $total_gastos_por_cobrar -= ( $acumulado_factura_cobro_anterior );
	    $acumulado_factura_cobro_anterior = $gasto['acumulado_factura'];
	    $id_cobro_anterior = $gasto['id_cobro'];
	}

	if (UtilesApp::GetConf($sesion, 'MostrarMontosPorCobrar')) {

	    $ws1->writeNumber($filas, $columna_gastos_por_cobrar, max($total_gastos_por_cobrar_cliente, 0), $formato_moneda);
	    $total_gastos_por_cobrar -= min($total_gastos_por_cobrar_cliente, 0);
	}

	$filas++;

	

	if ($gasto['ingresooegreso'] == 'egreso') {
	    $egreso = (double) ($gasto['egreso'] * $gasto['tipo_cambio_segun_cobro'] / $moneda_base['tipo_cambio']);
	    $egreso_cobrable = (double) ($gasto['monto_cobrable_moneda_base']);
	    $ingreso=  ($gasto['estado_cobro'] == 'PAGADO')? $egreso:0;
	    $ingreso_cobrable = ($gasto['estado_cobro'] == 'PAGADO')? $egreso_cobrable:0;
	}
	if ($gasto['ingresooegreso'] == 'ingreso') {
	    $egreso_cobrable = 0;
	    $egreso=0;
	    $ingreso = ($gasto['estado_cobro'] == 'PAGADO')? 0: (double) ($gasto['ingreso'] * $gasto['tipo_cambio_segun_cobro'] / $moneda_base['tipo_cambio']);
	    $ingreso_cobrable = ($gasto['estado_cobro'] == 'PAGADO')? 0: (double) ($gasto['monto_cobrable_moneda_base']);
	}

	if (UtilesApp::GetConf($sesion, 'MostrarMontosPorCobrar')) {
	    if ($gasto['esCobrable'] == 'Si') {
			if ($gasto['estado_cobro'] == ''
				|| $gasto['estado_cobro'] == 'CREADO'
				|| $gasto['estado_cobro'] == 'EN REVISION' 
			  	|| $gasto['estado_cobro'] == 'EMITIDO'
				|| $gasto['estado_cobro'] == 'FACTURADO'
				|| $gasto['estado_cobro'] == 'ENVIADO AL CLIENTE'
				|| $gasto['estado_cobro'] == 'PAGO PARCIAL') {
			    $total_gastos_por_cobrar_cliente = (double) ( $gastos_por_cobrar * $gasto['tipo_cambio_segun_cobro'] / $moneda_base['tipo_cambio']);
			    $total_gastos_por_cobrar += (double) ( $gastos_por_cobrar * $gasto['tipo_cambio_segun_cobro'] / $moneda_base['tipo_cambio']);
			} else if($gasto['estado_cobro'] == 'PAGADO'
				|| $gasto['estado_cobro'] == 'INCOBRABLE') 	 {
				 $total_gastos_por_cobrar_cliente +=0;
				  $total_gastos_por_cobrar += 0;
			}
	    } else {
		$total_gastos_por_cobrar_cliente = 0;
	    }
	}

	$nombre_cliente_anterior = $gasto['glosa_cliente'];
	$codigo_cliente_anterior = $gasto['codigo_cliente'];
    }
   // echo '<br>'.$ingreso_cobrable .' vs '.$egreso_cobrable. ' = '.($ingreso-$egreso);
}
//die();
//para que descuente facturas para el ultimo cobro (si es que tiene asociadas )
$total_gastos_por_cobrar_cliente -= ( $acumulado_factura_cobro_anterior );
$total_gastos_por_cobrar -= ( $acumulado_factura_cobro_anterior );
//echo $query; exit; 

$columna_actual = 0;
$ws1->write($filas, $columna_cliente, $nombre_cliente_anterior, $formato_normal);

$ws1->writeNumber($filas, $columna_egreso, $egreso, $formato_moneda);
if (UtilesApp::GetConf($sesion, 'UsaMontoCobrable')) {
    $ws1->writeNumber($filas, $columna_egreso_cobrable, $egreso_cobrable, $formato_moneda);
}
$ws1->writeNumber($filas, $columna_ingreso, $ingreso, $formato_moneda);
if (UtilesApp::GetConf($sesion, 'UsaMontoCobrable')) {
    $ws1->writeNumber($filas, $columna_ingreso_cobrable, $ingreso_cobrable, $formato_moneda);
}



    if (UtilesApp::GetConf($sesion, 'UsaMontoCobrable')) {
	
				$ws1->writeFormula($filas, $columna_balance, "=$col_ingreso_cobrable_para_formula" . ($filas + 1) . " - $col_egreso_cobrable_para_formula" . ($filas + 1), $formato_moneda);

		    } else {

				$ws1->writeFormula($filas, $columna_balance, "=$col_ingreso_para_formula" . ($filas + 1) . " - $col_egreso_para_formula" . ($filas + 1), $formato_moneda);
		    }
$ws1->write($filas, $columna_es_cobrable, $gasto['esCobrable'], $formato_moneda);
if (UtilesApp::GetConf($sesion, 'MostrarMontosPorCobrar')) {
    //$ws1->writeNumber($filas, $columna_gastos_por_cobrar, $total_gastos_por_cobrar_cliente, $formato_moneda);
    $ws1->writeNumber($filas, $columna_gastos_por_cobrar, max($total_gastos_por_cobrar_cliente, 0), $formato_moneda);
    $total_gastos_por_cobrar -= min($total_gastos_por_cobrar_cliente, 0);
}

++$filas;

$ws1->write($filas, $columna_cliente, __('Total'), $formato_total);
$ws1->writeFormula($filas, $columna_egreso, "=SUM($col_egreso_para_formula" . ($fila_inicio + 2) . ":$col_egreso_para_formula$filas)", $formato_moneda_total);
if (UtilesApp::GetConf($sesion, 'UsaMontoCobrable')) {
    $ws1->writeFormula($filas, $columna_egreso_cobrable, "=SUM($col_egreso_cobrable_para_formula" . ($fila_inicio + 2) . ":$col_egreso_cobrable_para_formula$filas)", $formato_moneda_total);
}
$ws1->writeFormula($filas, $columna_ingreso, "=SUM($col_ingreso_para_formula" . ($fila_inicio + 2) . ":$col_ingreso_para_formula$filas)", $formato_moneda_total);
if (UtilesApp::GetConf($sesion, 'UsaMontoCobrable')) {
    $ws1->writeFormula($filas, $columna_ingreso_cobrable, "=SUM($col_ingreso_cobrable_para_formula" . ($fila_inicio + 2) . ":$col_ingreso_cobrable_para_formula$filas)", $formato_moneda_total);
}
if (UtilesApp::GetConf($sesion, 'UsaMontoCobrable')) {
    $ws1->writeFormula($filas, $columna_balance, "=$col_ingreso_cobrable_para_formula" . ($filas + 1) . " - $col_egreso_cobrable_para_formula" . ($filas + 1), $formato_moneda_total);
} else {
    $ws1->writeFormula($filas, $columna_balance, "=$col_ingreso_para_formula" . ($filas + 1) . " - $col_egreso_para_formula" . ($filas + 1), $formato_moneda_total);
}
$ws1->write($filas, $columna_es_cobrable, '', $formato_moneda);
if (UtilesApp::GetConf($sesion, 'MostrarMontosPorCobrar')) {
    $ws1->writeNumber($filas, $columna_gastos_por_cobrar, $total_gastos_por_cobrar, $formato_moneda_total);
}

if ($total_balance_egreso_cobrable > 0 && $total_balance_ingreso_cobrable > 0) {
    $total_balance = $total_balance_ingreso_cobrable - $total_balance_egreso_cobrable;
} elseif ($total_balance_egreso_cobrable > 0) {
    $total_balance = - $total_balance_egreso_cobrable;
} elseif ($total_balance_ingreso > 0) {
    $total_balance = $total_balance_ingreso_cobrable;
}

$ws1->write(5, 1, __("Total balance"), $formato_encabezado);
$ws1->writeFormula(5, 2, "=$col_balance_para_formula" . ($filas + 1), $formato_moneda_encabezado);
$wb->send('Planilla_gastos.xls');
$wb->close();
exit;
 
