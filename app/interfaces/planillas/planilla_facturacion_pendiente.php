<?php
$tini = time();
$fechactual=date('Ymd');
require_once 'Spreadsheet/Excel/Writer.php';
require_once dirname(__FILE__) . '/../../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Pagina.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/../fw/classes/Html.php';
require_once Conf::ServerDir() . '/../app/classes/Debug.php';
require_once Conf::ServerDir() . '/classes/InputId.php';
require_once Conf::ServerDir() . '/classes/Cliente.php';
require_once Conf::ServerDir() . '/classes/Trabajo.php';
require_once Conf::ServerDir() . '/classes/Reporte.php';
require_once Conf::ServerDir() . '/classes/Moneda.php';
require_once Conf::ServerDir() . '/classes/UtilesApp.php';
require_once Conf::ServerDir() . '/classes/ReporteContrato.php';


$sesion = new Sesion(array('REP'));
$pagina = new Pagina($sesion);
$formato_fecha = UtilesApp::ObtenerFormatoFecha($sesion);
$AtacheSecundarioSoloAsunto = UtilesApp::GetConf($sesion, 'AtacheSecundarioSoloAsunto');

set_time_limit(3600);

if ($xls) {
	$fecha1=date('Y-m-d',strtotime($fecha1));
	$fecha2=date('Y-m-d',strtotime($fecha2));
	$moneda = new Moneda($sesion);
	$id_moneda_referencia = $moneda->GetMonedaTipoCambioReferencia($sesion);
	$id_moneda_base = $moneda->GetMonedaBase($sesion);

	$arreglo_monedas = ArregloMonedas($sesion);

	$moneda_base = Utiles::MonedaBase($sesion);
	#ARMANDO XLS
	$wb = new Spreadsheet_Excel_Writer();

	$wb->setCustomColor(35, 220, 255, 220);
	$wb->setCustomColor(36, 255, 255, 220);

	$formato_encabezado = & $wb->addFormat(array('Size' => 12,
				'VAlign' => 'top',
				'Align' => 'left',
				'Bold' => '1',
				'underline' => 1,
				'Color' => 'black'));

	$formato_texto = & $wb->addFormat(array('Size' => 11,
				'Valign' => 'top',
				'Align' => 'left',
				'Border' => 1,
				'Color' => 'black',
				'TextWrap' => 1));
	$formato_tiempo = & $wb->addFormat(array('Size' => 11,
				'VAlign' => 'top',
				'Border' => 1,
				'Color' => 'black',
				'NumFormat' => '[h]:mm'));
	$formato_numero = & $wb->addFormat(array('Size' => 11,
				'VAlign' => 'top',
				'Border' => 1,
				'Color' => 'black',
				'NumFormat' => 0));
	$formato_titulo = & $wb->addFormat(array('Size' => 12,
				'Align' => 'center',
				'Bold' => '1',
				'FgColor' => '35',
				'Border' => 1,
				'Locked' => 1,
				'Color' => 'black'));
	$fdd = & $wb->addFormat(array('Size' => 11,
				'VAlign' => 'top',
				'Align' => 'justify',
				'Border' => 1,
				'Color' => 'black'));
	$fdd->setNumFormat(0.0);

	$mostrar_encargado_secundario = UtilesApp::GetConf($sesion, 'EncargadoSecundario');

	$formatos_moneda = array();
	$query = 'SELECT id_moneda, simbolo, cifras_decimales
				FROM prm_moneda
				ORDER BY id_moneda';
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	while (list($id_moneda, $simbolo_moneda, $cifras_decimales) = mysql_fetch_array($resp)) {
		if ($cifras_decimales > 0) {
			$decimales = '.';
			while ($cifras_decimales-- > 0) {
				$decimales .= '0';
			}
		} else {
			$decimales = '';
		}
		$formatos_moneda[$id_moneda] = & $wb->addFormat(array('Size' => 11,
					'VAlign' => 'top',
					'Align' => 'right',
					'Border' => '1',
					'Color' => 'black',
					'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"));
	}
	$formatos_moneda_tc = array();
	$query = 'SELECT id_moneda, simbolo, cifras_decimales
				FROM prm_moneda
				ORDER BY id_moneda';
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	while (list($id_moneda, $simbolo_moneda, $cifras_decimales) = mysql_fetch_array($resp)) {
		$formatos_moneda_tc[$id_moneda] = & $wb->addFormat(array('Size' => 11,
					'VAlign' => 'top',
					'Align' => 'right',
					'Border' => '1',
					'Color' => 'black',
					'NumFormat' => "[$$simbolo_moneda] #,###,0.00"));
	}
	$cifras_decimales = $moneda_base['cifras_decimales'];
	if ($cifras_decimales > 0) {
		$decimales = '.';
		while ($cifras_decimales-- > 0) {
			$decimales .= '0';
		}
	} else {
		$decimales = '';
	}
	$formato_moneda_base_rojo = & $wb->addFormat(array('Size' => 11,
				'VAlign' => 'top',
				'Align' => 'right',
				'Border' => 1,
				'Color' => 'red',
				'NumFormat' => '[$' . $moneda_base['simbolo'] . "] #,###,0$decimales"));

	$ws1 = & $wb->addWorksheet(__('Facturacion'));
	$ws1->setInputEncoding('utf-8');
	$ws1->fitToPages(1, 0);
	$ws1->setZoom(75);

	$filas += 1;
	$ws1->mergeCells($filas, 1, $filas, 2);
	$ws1->write($filas, 1, __('REPORTE HORAS POR FACTURAR'), $formato_encabezado);
	$ws1->write($filas, 2, '', $formato_encabezado);
	$filas +=2;
	$ws1->write($filas, 1, __('GENERADO EL:'), $formato_texto);
	$ws1->write($filas, 2, date("d-m-Y H:i:s"), $formato_texto);

	$filas +=4;
	$col = 0;

	$col_cliente = ++$col;
	if (!$ocultar_encargado) {
		$col_usuario_encargado = ++$col;
	}
	if ($mostrar_encargado_secundario && !$ocultar_encargado) {
		$col_usuario_encargado_secundario = ++$col;
	}
	if ($AtacheSecundarioSoloAsunto && !$ocultar_encargado) {
		$col_encargado_asunto = ++$col;
		$ws1->setColumn($col_encargado_asunto, $col_encargado_asunto, 40);
	}
	if (UtilesApp::GetConf($sesion, 'MostrarColumnaCodigoAsuntoHorasPorFacturar')) {
		$col_codigo_asunto = ++$col;
	}
	$col_glosa_asunto = ++$col;

	if (UtilesApp::GetConf($sesion, 'MostrarColumnaAsuntoCobrableHorasPorFacturar')) {
		$col_asunto_cobrable = ++$col;
	}
	if (!$ocultar_ultimo_trabajo) {
		$col_ultimo_trabajo = ++$col;
	}

	if (UtilesApp::GetConf($sesion, 'MostrarColumnasGastosEnHorasPorFacturar')) {
		$col_ultimo_gasto = ++$col;
		$col_monto_gastos = ++$col;
		$col_monto_gastos_mb = ++$col;
	}
	if (!$ocultar_ultimo_cobro) {
		$col_ultimo_cobro = ++$col;
	}
	if (!$ocultar_estado_ultimo_cobro) {
		$col_estado_ultimo_cobro = ++$col;
	}
	if( !$ocultar_fecha_corte ) {
		$col_fecha_corte = ++$col;
	}
	$col_horas_trabajadas = ++$col;
	$col_forma_cobro = ++$col;
	if ($desglosar_moneda) {
		foreach ($arreglo_monedas as $id_moneda => $moneda) {
			$col_valor_estimado_{$id_moneda} = ++$col;
		}
	} else {
		$col_valor_estimado = ++$col;
	}
	$col_tipo_cambio = ++$col;
	if ($id_moneda_base != $id_moneda_referencia) {
		$col_tipo_cambio_moneda_base = ++$col;
	}
	$col_valor_en_moneda_base = ++$col;
	$col_valor_en_moneda_base_segun_THH = ++$col;

	if ($debug) {
		$col_monto_contrato = ++$col;
		$col_horas_retainer = ++$col;
		$col_valor_cap = ++$col;
		$col_porcentaje_retainer = ++$col;
	}
	unset($col);

	$ws1->setColumn($col_cliente, $col_cliente, 40);
	if (!$ocultar_encargado) {
		$ws1->setColumn($col_usuario_encargado, $col_usuario_encargado, 40);
		if ($mostrar_encargado_secundario) {
			$ws1->setColumn($col_usuario_encargado_secundario, $col_usuario_encargado_secundario, 40);
		}
	}
	if (UtilesApp::GetConf($sesion, 'MostrarColumnaCodigoAsuntoHorasPorFacturar')) {
		$ws1->setColumn($col_codigo_asunto, $col_codigo_asunto, 16);
	}
	$ws1->setColumn($col_glosa_asunto, $col_glosa_asunto, 40);
	if (UtilesApp::GetConf($sesion, 'MostrarColumnaAsuntoCobrableHorasPorFacturar')) {
		$ws1->setColumn($col_asunto_cobrable, $col_asunto_cobrable, 13);
	}
	if (!$ocultar_ultimo_trabajo) {
		$ws1->setColumn($col_ultimo_trabajo, $col_ultimo_trabajo, 15);
	}
	if (UtilesApp::GetConf($sesion, 'MostrarColumnasGastosEnHorasPorFacturar')) {
		$ws1->setColumn($col_ultimo_gasto, $col_ultimo_gasto, 15);
		$ws1->setColumn($col_monto_gastos, $col_monto_gastos, 18);
		$ws1->setColumn($col_monto_gastos_mb, $col_monto_gastos_mb, 18);
	}
	if (!$ocultar_ultimo_cobro) {
		$ws1->setColumn($col_ultimo_cobro, $col_ultimo_cobro, 14);
	}
	if (!$ocultar_estado_ultimo_cobro) {
		$ws1->setColumn($col_estado_ultimo_cobro, $col_estado_ultimo_cobro, 22);
	}
	if( !$ocultar_fecha_corte ) {
		$ws1->setColumn($col_fecha_corte, $col_fecha_corte, 14);
	}
	$ws1->setColumn($col_forma_cobro, $col_forma_cobro, 14);
	if ($desglosar_moneda) {
		foreach ($arreglo_monedas as $id_moneda => $moneda) {
			$ws1->setColumn($col_valor_estimado_{$id_moneda}, $col_valor_estimado_{$id_moneda}, 22);
		}
	} else {
		$ws1->setColumn($col_valor_estimado, $col_valor_estimado, 18);
	}
	$ws1->setColumn($col_tipo_cambio, $col_tipo_cambio, 14);
	if ($id_moneda_base != $id_moneda_referencia) {
		$ws1->setColumn($col_tipo_cambio_moneda_base, $col_tipo_cambio_moneda_base, 14);
	}
	$ws1->setColumn($col_valor_en_moneda_base, $col_valor_en_moneda_base, 18);
	$ws1->setColumn($col_valor_en_moneda_base_segun_THH, $col_valor_en_moneda_base_segun_THH, 23);
	$ws1->setColumn($col_horas_trabajadas, $col_horas_trabajadas, 19);

	if ($debug) {
		$ws1->setColumn($col_monto_contrato, $col_monto_contrato, 18);
		$ws1->setColumn($col_horas_retainer, $col_horas_retainer, 18);
		$ws1->setColumn($col_valor_cap, $col_valor_cap, 18);
		$ws1->setColumn($col_porcentaje_retainer, $col_porcentaje_retainer, 18);
	}

	$ws1->write($filas, $col_cliente, __('Cliente'), $formato_titulo);
	if (!$ocultar_encargado) {
		$ws1->write($filas, $col_usuario_encargado, __('Encargado Comercial'), $formato_titulo);
		if ($mostrar_encargado_secundario) {
			$ws1->write($filas, $col_usuario_encargado_secundario, __('Encargado Secundario'), $formato_titulo);
		}
		if ($AtacheSecundarioSoloAsunto) {
			$ws1->write($filas, $col_encargado_asunto, __('Encargado Secundario') . ' ' . __('Asunto'), $formato_titulo);
		}
	}
	if (UtilesApp::GetConf($sesion, 'MostrarColumnaCodigoAsuntoHorasPorFacturar')) {
		$ws1->write($filas, $col_codigo_asunto, __('Código Asunto'), $formato_titulo);
	}
	$ws1->write($filas, $col_glosa_asunto, __('Asunto'), $formato_titulo);
	if (UtilesApp::GetConf($sesion, 'MostrarColumnaAsuntoCobrableHorasPorFacturar')) {
		$ws1->write($filas, $col_asunto_cobrable, __('Cobrable'), $formato_titulo);
	}
	if (!$ocultar_ultimo_trabajo)
		$ws1->write($filas, $col_ultimo_trabajo, __('Último trabajo'), $formato_titulo);
	if (UtilesApp::GetConf($sesion, 'MostrarColumnasGastosEnHorasPorFacturar')) {
		$ws1->write($filas, $col_ultimo_gasto, __('Último gasto'), $formato_titulo);
		$ws1->write($filas, $col_monto_gastos, __('Monto gastos'), $formato_titulo);
		$ws1->write($filas, $col_monto_gastos_mb, __('Monto gastos ' . $moneda_base['simbolo']), $formato_titulo);
	}
	if (!$ocultar_ultimo_cobro) {
		$ws1->write($filas, $col_ultimo_cobro, __('Último cobro'), $formato_titulo);
	}	
	if (!$ocultar_estado_ultimo_cobro) {
		$ws1->write($filas, $col_estado_ultimo_cobro, __('Estado último cobro'), $formato_titulo);
	}
	if( !$ocultar_fecha_corte ) {
		$ws1->write($filas, $col_fecha_corte, __('Fecha Corte'), $formato_titulo);
	}
	
	$ws1->write($filas, $col_forma_cobro, __('Forma cobro'), $formato_titulo);
	if ($desglosar_moneda) {
		foreach ($arreglo_monedas as $id_moneda => $moneda) {
			$ws1->write($filas, $col_valor_estimado_{$id_moneda}, __('Valor estimado') . ' ' . __($moneda['glosa_moneda']), $formato_titulo);
		}
	} else {
		$ws1->write($filas, $col_valor_estimado, __('Valor estimado'), $formato_titulo);
	}
	$ws1->write($filas, $col_tipo_cambio, __('Tipo Cambio'), $formato_titulo);
	if ($id_moneda_base != $id_moneda_referencia) {
		$ws1->write($filas, $col_tipo_cambio_moneda_base, __('Tipo Cambio ' . $arreglo_monedas[$id_moneda_base]['simbolo']), $formato_titulo);
	}
	$ws1->write($filas, $col_valor_en_moneda_base, __('Valor en ' . Moneda::GetSimboloMoneda($sesion, Moneda::GetMonedaBase($sesion))), $formato_titulo);
	$ws1->write($filas, $col_valor_en_moneda_base_segun_THH, __('Valor en ' . Moneda::GetSimboloMoneda($sesion, Moneda::GetMonedaBase($sesion)) . ' según THH'), $formato_titulo);
	$ws1->write($filas, $col_horas_trabajadas, __('Horas trabajadas'), $formato_titulo);
	if ($debug) {
		$ws1->write($filas, $col_monto_contrato, __('Monto Contrato'), $formato_titulo);
		$ws1->write($filas, $col_horas_retainer, __('Horas Retainer'), $formato_titulo);
		$ws1->write($filas, $col_valor_cap, __('Cap Usado'), $formato_titulo);
		$ws1->write($filas, $col_porcentaje_retainer, __('Porcentaje Retainer'), $formato_titulo);
	}

	$where_trabajo = "  trabajo.estadocobro in ('SIN COBRO','CREADO','EN REVISION' ) ";
	$where_gasto = "  cta_corriente.estadocobro in ('SIN COBRO','CREADO','EN REVISION' )  ";
	if ($fecha1 != '' && $fecha2 != '') {
		$where_trabajo .= " AND trabajo.fecha >= '" . $fecha1 . "' AND trabajo.fecha <= '" . $fecha2 . "'";
		$where_gasto .= " AND cta_corriente.fecha >= '" . $fecha1 . "' AND cta_corriente.fecha <= '" . $fecha2 . "' ";
	}
	$where_gasto .= " AND cta_corriente.incluir_en_cobro = 'SI' ";
	$where = " 1 ";
	if (is_array($socios)) {
		$lista_socios = join("','", $socios);
		$where .= " AND contrato.id_usuario_responsable IN ('$lista_socios')";
	}
	if (is_array($encargados)) {
		$lista_encargados = join("','", $encargados);
		$where .= " AND asunto.id_encargado IN ('$lista_encargados')";
	}
	if ($separar_asuntos) {
		$group_by = "asunto.codigo_asunto";
	} else {
		$group_by = "contrato.id_contrato";
	}

	if (UtilesApp::GetConf($sesion, 'CodigoSecundario')) {
		$codigos_asuntos_secundarios = "GROUP_CONCAT( asunto.codigo_asunto_secundario ) as codigos_asuntos_secundarios, ";
		$codigo_asunto_secundario_sep = "asunto.codigo_asunto_secundario, ";
	} else {
		$codigos_asuntos_secundarios = "";
		$codigo_asunto_secundario_sep = "";
	}


	if (!UtilesApp::existecampo('eliminado', 'olap_liquidaciones', $sesion->dbh))
		mysql_query("ALTER TABLE  `olap_liquidaciones` ADD  `Eliminado` TINYINT( 1 ) NOT NULL DEFAULT  '0' COMMENT 'Cuando el campo es igual a 1 el trabajo, cobro o trámite fue eliminado, ya no hay que tomarlo en cuenta para la query'", $sesion->dbh);



	$update1 = "update trabajo join cobro c on trabajo.id_cobro=c.id_cobro set trabajo.estadocobro=c.estado where c.fecha_touch >= trabajo.fecha_touch ;";
	$update2 = "update cta_corriente join cobro c on  cta_corriente.id_cobro=c.id_cobro  set cta_corriente.estadocobro=c.estado  where c.fecha_touch >= cta_corriente.fecha_touch;";
	$update3 = "update tramite join cobro c on tramite.id_cobro=c.id_cobro set tramite.estadocobro=c.estado where c.fecha_touch >= tramite.fecha_touch ;";
	$update3A = "update olap_liquidaciones ol left join trabajo t on ol.id_entry=t.id_trabajo set ol.eliminado=1 where ol.tipo='TRB' and t.id_trabajo is null";
	$update3B = "update olap_liquidaciones ol left join cta_corriente cc on ol.id_entry=cc.id_movimiento set ol.eliminado=1 where ol.tipo='GAS' and cc.id_movimiento is null";
	$resp = mysql_query($update1, $sesion->dbh);
	$resp = mysql_query($update2, $sesion->dbh);
	$resp = mysql_query($update3, $sesion->dbh);
	$resp = mysql_query($update3A, $sesion->dbh);
	$resp = mysql_query($update3B, $sesion->dbh);
	list($maxolaptime) = mysql_fetch_array(mysql_query("SELECT DATE_FORMAT( date_add(MAX( fecha_modificacion ), interval -1 day) ,  '%Y%m%d' ) AS maxfecha FROM olap_liquidaciones", $sesion->dbh));

	$update4 = "replace delayed into olap_liquidaciones (SELECT
                                                                asunto.codigo_asunto as codigos_asuntos,
                                                                asunto.codigo_asunto_secundario, 
								  contrato.id_usuario_responsable,
								   asunto.glosa_asunto as asuntos,
								   (asunto.cobrable+1) as asuntos_cobrables,
								    cliente.id_cliente, 		cliente.codigo_cliente_secundario, cliente.glosa_cliente,   cliente.fecha_creacion,cliente.id_cliente_referencia,
								
								CONCAT_WS( ec.nombre, ec.apellido1, ec.apellido2 ) as nombre_encargado_comercial,
								ec.username as username_encargado_comercial,
								CONCAT_WS( es.nombre, es.apellido1, es.apellido2 ) as nombre_encargado_secundario,
								es.username as username_encargado_secundario,
								contrato.id_contrato,
                                                                contrato.monto, 
								contrato.forma_cobro,
								contrato.retainer_horas,
								contrato.id_moneda as id_moneda_contrato,
								contrato.opc_moneda_total as id_moneda_total,
                                                              
															  movs.*,0
								FROM  asunto JOIN contrato  using (id_contrato)
								JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
								join
								(select  'TRB' as tipo,10000000+tr.id_trabajo as id_unico,
								 tr.id_trabajo, tr.id_usuario, tr.codigo_asunto, tr.cobrable, 2 as incluir_en_cobro, TIME_TO_SEC(duracion_cobrada) as duracion_cobrada_segs,
								 0 as monto_cobrable,TIME_TO_SEC(duracion_cobrada)*tarifa_hh as monto_thh, TIME_TO_SEC(duracion_cobrada)*tarifa_hh_estandar as monto_thh_estandar, tr.id_moneda, tr.fecha,  tr.id_cobro ,tr.estadocobro
								,fecha_modificacion from  trabajo tr where   fecha_touch>=$maxolaptime 

								 union all

								 SELECT 'GAS' as tipo, 20000000+cc.id_movimiento as id_unico,
								 cc.id_movimiento,cc.id_usuario_orden, cc.codigo_asunto,cc.cobrable, if(cc.incluir_en_cobro='SI',2,1) as incluir_en_cobro, 0 as duracion_cobrada_segs,
								IF( ISNULL( cc.egreso ) , -1, 1 ) * cc.monto_cobrable, 0 as monto_thh, 0 as monto_thh_estandar, cc.id_moneda, cc.fecha, cc.id_cobro,cc.estadocobro
								,fecha_modificacion from  cta_corriente cc WHERE cc.codigo_asunto IS NOT NULL and  fecha_touch>=$maxolaptime


								union all

								select 'TRA' as tipo, 30000000 + tram.id_tramite as id_unico,
								tram.id_tramite, tram.id_usuario, tram.codigo_asunto, tram.cobrable,  2 as incluir_en_cobro, TIME_TO_SEC(duracion) as duracion_cobrada_segs,
								tram.tarifa_tramite, 0 as monto_thh, 0 as monto_thh_estandar,tram.id_moneda_tramite,  tram.fecha, tram.id_cobro, tram.estadocobro 
								,fecha_modificacion from tramite tram where fecha_touch>=$maxolaptime

								) movs on movs.codigo_asunto=asunto.codigo_asunto
								 LEFT JOIN usuario as ec ON ec.id_usuario = contrato.id_usuario_responsable
															LEFT JOIN usuario as es ON es.id_usuario = contrato.id_usuario_secundario)

								"; // quito "tr.id_tramite = 0  AND tr.duracion_cobrada >0 and" de la segunda subquery
	$resp = mysql_query($update4, $sesion->dbh);

if($fechactual-$maxolaptime>2) {
	$update5 = "truncate table trabajos_por_actualizar;";
	$update6 = "replace into trabajos_por_actualizar (
		select id_trabajo,t.codigo_asunto, ol.duracion_cobrada_segs,time_to_sec(t.duracion_cobrada),ol.fecha_modificacion, t.fecha_touch   
		from olap_liquidaciones ol join trabajo t on ol.id_entry=t.id_trabajo
		where  ol.tipo='TRB'  	and ol.duracion_cobrada_segs!=time_to_sec(t.duracion_cobrada));";
	$resp = mysql_query($update5, $sesion->dbh);
	$resp = mysql_query($update6, $sesion->dbh);

	$update7 = "replace delayed into olap_liquidaciones (SELECT
                                                                asunto.codigo_asunto as codigos_asuntos,
                                                                asunto.codigo_asunto_secundario, 
								  contrato.id_usuario_responsable,
								   asunto.glosa_asunto as asuntos,
								   (asunto.cobrable+1) as asuntos_cobrables,
								    cliente.id_cliente, 		cliente.codigo_cliente_secundario, cliente.glosa_cliente,   cliente.fecha_creacion,cliente.id_cliente_referencia,
								
								CONCAT_WS( ec.nombre, ec.apellido1, ec.apellido2 ) as nombre_encargado_comercial,
								ec.username as username_encargado_comercial,
								CONCAT_WS( es.nombre, es.apellido1, es.apellido2 ) as nombre_encargado_secundario,
								es.username as username_encargado_secundario,
								contrato.id_contrato,
                                                                contrato.monto, 
								contrato.forma_cobro,
								contrato.retainer_horas,
								contrato.id_moneda as id_moneda_contrato,
								contrato.opc_moneda_total as id_moneda_total,
                                                              
															  movs.*,0
								FROM  asunto JOIN contrato  using (id_contrato)
								JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
								join
								(select  'TRB' as tipo,10000000+tr.id_trabajo as id_unico,
								 tr.id_trabajo, tr.id_usuario, tr.codigo_asunto, tr.cobrable, 2 as incluir_en_cobro, TIME_TO_SEC(duracion_cobrada) as duracion_cobrada_segs,
								 0 as monto_cobrable,TIME_TO_SEC(duracion_cobrada)*tarifa_hh as monto_thh, TIME_TO_SEC(duracion_cobrada)*tarifa_hh_estandar as monto_thh_estandar, tr.id_moneda, tr.fecha,  tr.id_cobro ,tr.estadocobro
								,fecha_modificacion from  trabajo tr where  id_trabajo in (select id_trabajo from trabajos_por_actualizar)  

								 

								 
								) movs on movs.codigo_asunto=asunto.codigo_asunto
								 LEFT JOIN usuario as ec ON ec.id_usuario = contrato.id_usuario_responsable
															LEFT JOIN usuario as es ON es.id_usuario = contrato.id_usuario_secundario)

								";
	$resp = mysql_query($update7, $sesion->dbh);
}

	$querycobros = "SELECT
								GROUP_CONCAT( asunto.codigo_asunto ) as codigos_asuntos,
								$codigos_asuntos_secundarios
								asunto.glosa_asunto,
								GROUP_CONCAT( asunto.glosa_asunto ) as asuntos,
                                                                asunto.codigo_asunto, 
																$codigo_asunto_secundario_sep 
                                                                GROUP_CONCAT( IF(asunto.cobrable=1,'SI','NO') ) as asuntos_cobrables,
								cliente.glosa_cliente,
								GROUP_CONCAT( cliente.glosa_cliente ) as clientes,
								CONCAT_WS( ec.nombre, ec.apellido1, ec.apellido2 ) as nombre_encargado_comercial,
								ec.username as username_encargado_comercial,
								CONCAT_WS( es.nombre, es.apellido1, es.apellido2 ) as nombre_encargado_secundario,
								es.username as username_encargado_secundario,
								CONCAT_WS( encargado_asunto.nombre, encargado_asunto.apellido1, encargado_asunto.apellido2 ) as nombre_encargado_asunto,
								encargado_asunto.username as username_encargado_asunto,
								contrato.id_contrato,
                                                                contrato.monto, 
								contrato.forma_cobro,
								contrato.id_moneda as id_moneda_contrato,
								contrato.opc_moneda_total as id_moneda_total
							FROM asunto
							LEFT JOIN contrato USING( id_contrato )
							LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
							LEFT JOIN usuario as ec ON ec.id_usuario = contrato.id_usuario_responsable
							LEFT JOIN usuario as es ON es.id_usuario = contrato.id_usuario_secundario
							left join usuario as encargado_asunto on encargado_asunto.id_usuario=asunto.id_encargado 
							WHERE $where
								AND ( ( SELECT count(*)
													FROM trabajo
										 LEFT JOIN cobro ON cobro.id_cobro = trabajo.id_cobro
										 		 WHERE trabajo.codigo_asunto = asunto.codigo_asunto
										 		 	 AND trabajo.cobrable = 1
										 		 	 AND trabajo.id_tramite = 0
										 		 	 AND trabajo.duracion_cobrada != '00:00:00'
										 		 	 AND $where_trabajo ) > 0
										OR ( SELECT count(*)
														FROM cta_corriente
											LEFT JOIN cobro ON cobro.id_cobro = cta_corriente.id_cobro
													WHERE cta_corriente.codigo_asunto = asunto.codigo_asunto
														AND cta_corriente.cobrable = 1
                                                                                                                AND cta_corriente.monto_cobrable > 0 
														AND $where_gasto ) > 0 )
							GROUP BY $group_by ";

	$fila_inicial = $filas + 2;
	$tiempomatriz = array();

	if($enviamail) mail('ffigueroa@lemontech.cl','Horas por Facturar '.Conf::AppName(),$querycobros);
	
	
	$reportecontrato = new ReporteContrato($sesion, false, $separar_asuntos, $fecha1, $fecha2);
	$ultimocobro = $reportecontrato->arrayultimocobro;
	//echo '<pre>';print_r($ultimocobro);echo '</pre>';                                die();
	$arrayolap = $reportecontrato->arrayolap;


	//echo '<pre>';  print_r($ultimocobro);  echo '</pre>';   				echo $querycobros;			   
	$respcobro = mysql_query($querycobros, $sesion->dbh) or Utiles::errorSQL($querycobros, __FILE__, __LINE__, $sesion->dbh);


	while ($cobro = mysql_fetch_array($respcobro)) {


		$id_contrato = $cobro['id_contrato'];



		// Definir datos ...
		if ($separar_asuntos) {
			$reportecontrato->LoadContrato($id_contrato, $cobro['codigo_asunto'], $fecha1, $fecha2, false);
			list($monto_estimado_gastos, $simbolo_moneda_gastos, $id_moneda_gastos, $horas_no_cobradas, $fecha_ultimo_trabajo, $fecha_ultimo_gasto) = $arrayolap[$cobro['codigo_asunto']];
		} else {
			$reportecontrato->LoadContrato($id_contrato, '', $fecha1, $fecha2, false);
			list($monto_estimado_gastos, $simbolo_moneda_gastos, $id_moneda_gastos, $horas_no_cobradas, $fecha_ultimo_trabajo, $fecha_ultimo_gasto) = $arrayolap[$id_contrato];
		}


		list($monto_estimado_trabajos, $simbolo_moneda_trabajos, $id_moneda_trabajos,
				$cantidad_asuntos,
				$monto_estimado_trabajos_segun_contrato, $simbolo_moneda_trabajos_segun_contrato, $id_moneda_trabajos_segun_contrato,
				$monto_estimado_thh, $simbolo_moneda_thh, $id_moneda_thh) = $reportecontrato->arraymonto;






		if (UtilesApp::GetConf($sesion, 'CodigoSecundario')) {
			$codigos_asuntos = implode("\n", explode(',', $cobro['codigos_asuntos_secundarios']));
		} else {
			$codigos_asuntos = implode("\n", explode(',', $cobro['codigos_asuntos']));
		}
		$asuntos = implode("\n", explode(',', $cobro['asuntos']));
		$asuntos_cobrables = implode("\n", explode(',', $cobro['asuntos_cobrables']));

		if (empty($id_moneda_trabajos)) {
			$id_moneda_trabajos = $cobro['id_moneda_contrato'];
		}
		++$filas;

		$ws1->write($filas, $col_cliente, $cobro['glosa_cliente'], $formato_texto);
		if (!$ocultar_encargado) {
			if (method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'UsaUsernameEnTodoElSistema')) {
				$ws1->write($filas, $col_usuario_encargado, $cobro['username_encargado_comercial'], $formato_texto);
				if ($mostrar_encargado_secundario)
					$ws1->write($filas, $col_usuario_encargado_secundario, $cobro['username_encargado_secundario'], $formato_texto);

				if ($AtacheSecundarioSoloAsunto)
					$ws1->write($filas, $col_encargado_asunto, $cobro['username_encargado_asunto'], $formato_texto);
			}
			else {
				$ws1->write($filas, $col_usuario_encargado, $cobro['nombre_encargado_comercial'], $formato_texto);
				if ($mostrar_encargado_secundario)
					$ws1->write($filas, $col_usuario_encargado_secundario, $cobro['nombre_encargado_secundario'], $formato_texto);

				if ($AtacheSecundarioSoloAsunto)
					$ws1->write($filas, $col_encargado_asunto, $cobro['nombre_encargado_asunto'], $formato_texto);
			}
		}
		if (UtilesApp::GetConf($sesion, 'MostrarColumnaCodigoAsuntoHorasPorFacturar')) {
			$ws1->write($filas, $col_codigo_asunto, $codigos_asuntos, $formato_texto);
		}
		$ws1->write($filas, $col_glosa_asunto, $asuntos, $formato_texto);
		if (UtilesApp::GetConf($sesion, 'MostrarColumnaAsuntoCobrableHorasPorFacturar')) {
			$ws1->write($filas, $col_asunto_cobrable, $asuntos_cobrables, $formato_texto);
		}
		if (!$ocultar_ultimo_trabajo)
			$ws1->write($filas, $col_ultimo_trabajo, empty($fecha_ultimo_trabajo) ? "" : Utiles::sql2fecha($fecha_ultimo_trabajo, $formato_fecha, "-"), $formato_texto);

		$monto_estimado_gastos_monedabase = UtilesApp::CambiarMoneda($monto_estimado_gastos, number_format($arreglo_monedas[$id_moneda_gastos]['tipo_cambio'], $arreglo_monedas[$id_moneda_gastos]['cifras_decimales'], '.', ''), $arreglo_monedas[$id_moneda_gastos]['cifras_decimales'], number_format($arreglo_monedas[$moneda_base['id_moneda']]['tipo_cambio'], $arreglo_monedas[$moneda_base['id_moneda']]['cifras_decimales'], '.', ''), $arreglo_monedas[$moneda_base['id_moneda']]['cifras_decimales']);

		if (UtilesApp::GetConf($sesion, 'MostrarColumnasGastosEnHorasPorFacturar')) {
			$ws1->write($filas, $col_ultimo_gasto, empty($fecha_ultimo_gasto) ? "" : Utiles::sql2fecha($fecha_ultimo_gasto, $formato_fecha, "-"), $formato_texto);
			$ws1->write($filas, $col_monto_gastos, $monto_estimado_gastos, $formatos_moneda[$id_moneda_gastos]);
			$ws1->write($filas, $col_monto_gastos_mb, $monto_estimado_gastos_monedabase, $formatos_moneda[$moneda_base['id_moneda']]);
		}
		if (!$ocultar_ultimo_cobro) {
			if ($separar_asuntos) :
				$ws1->write($filas, $col_ultimo_cobro, $ultimocobro[$cobro['codigo_asunto']]['fecha_emision'] != '' ? Utiles::sql2fecha($ultimocobro[$cobro['codigo_asunto']]['fecha_emision'], $formato_fecha, "-") : '', $formato_texto);
			else:
				$ws1->write($filas, $col_ultimo_cobro, $ultimocobro[$id_contrato]['fecha_emision'] != '' ? Utiles::sql2fecha($ultimocobro[$id_contrato]['fecha_emision'], $formato_fecha, "-") : '', $formato_texto);
			endif;
		}  else {
			 
				$ws1->write($filas, $col_estado_ultimo_cobro,$ocultar_estado_ultimo_cobro, $formato_texto);
			 
		}

		if (!$ocultar_estado_ultimo_cobro) {
			if ($separar_asuntos) :
				$ws1->write($filas, $col_estado_ultimo_cobro, $ultimocobro[$cobro['codigo_asunto']]['estado'] != '' ? $ultimocobro[$cobro['codigo_asunto']]['estado'] : '', $formato_texto);
			else:
				$ws1->write($filas, $col_estado_ultimo_cobro, $ultimocobro[$id_contrato]['estado'] != '' ? $ultimocobro[$id_contrato]['estado'] : '', $formato_texto);
			endif;
		} 
		
		if (!$ocultar_fecha_corte) {
			if ($separar_asuntos) :
				$ws1->write($filas, $col_fecha_corte, $ultimocobro[$cobro['codigo_asunto']]['fecha_fin'] != '' ? Utiles::sql2fecha($ultimocobro[$cobro['codigo_asunto']]['fecha_fin'], $formato_fecha, "-") : '', $formato_texto);
			else:
				$ws1->write($filas, $col_fecha_corte, $ultimocobro[$id_contrato]['fecha_fin'] != '' ? Utiles::sql2fecha($ultimocobro[$id_contrato]['fecha_fin'], $formato_fecha, "-") : '', $formato_texto);
			endif;
		}

		if (UtilesApp::GetConf($sesion, 'TipoIngresoHoras') == 'decimal') {
			$ws1->write($filas, $col_horas_trabajadas, number_format($horas_no_cobradas, 1, '.', ''), $fdd);
		} else {
			$ws1->write($filas, $col_horas_trabajadas, number_format($horas_no_cobradas / 24, 6, '.', ''), $formato_tiempo);
		}
		$ws1->write($filas, $col_forma_cobro, $cobro['forma_cobro'], $formato_texto);

		// En el primer asunto de un contrato hay que actualizar el valor descuento al contrato actual
		if ($id_contrato != $id_contrato_anterior)
		//FFF la siguiente linea trae los descuentos a nivel de contrato
			$valor_descuento = $cobro['valor_descuento'];

		//FFF: la siguiente trae los descuentos hechos en los cobros no emitidos	(no es lo mismo que a nivel de contrato)
		//		$valor_descuento = $reportecontrato->arraydescuentos[$cobro['id_contrato']];

		$valor_estimado = $monto_estimado_trabajos;




		if ($cobro['forma_cobro'] == 'CAP') {
			if ($separar_asuntos) {
				$cobro_aux = new Cobro($sesion);
				$usado = $cobro_aux->TotalCobrosCap($id_contrato); //Llevamos lo cobrado en el CAP a la moneda TOTAL
				if ($monto_estimado_trabajos_segun_contrato + $usado > $cobro['monto']) {
					$cantidad_asuntos = $reportecontrato->asuntosporfacturar;
					list($monto_hh_asunto, $x, $y) = $reportecontrato->MHHXA;
					list($monto_hh_contrato, $X, $Y) = $reportecontrato->MHHXC;
					unset($x, $y, $X, $Y);

					if ($monto_hh_contrato > 0) {
						$factor = number_format($monto_hh_asunto / $monto_hh_contrato, 6, '.', '');
					} else {
						$factor = number_format(1 / $cantidad_asuntos, 6, '.', '');
					}
					$valor_estimado = ( $cobro['monto'] - $usado ) * $factor;
					if ($valor_estimado < 0)
						$valor_estimado = 0;
				}
				else
					$valor_estimado = $monto_estimado_trabajos;
			} else {
				$cobro_aux = new Cobro($sesion);
				$usado = $cobro_aux->TotalCobrosCap($id_contrato); //Llevamos lo cobrado en el CAP a la moneda TOTAL
				if ($monto_estimado_trabajos + $usado > $cobro['monto']) {
					$valor_estimado = $cobro['monto'] - $usado;
					if ($valor_estimado < 0)
						$valor_estimado = 0;
				}
				else
					$valor_estimado = $monto_estimado_trabajos;
			}
		}
		else {
			$valor_estimado = $monto_estimado_trabajos;
		}
		// Aplicar descuentos del contrato al valor estimado
		if ($cobro['porcentaje_descuento'] > 0) {
			$valor_descuento = $valor_estimado * $cobro['porcentaje_descuento'];
			$valor_estimado = $valor_estimado - $valor_descuento;
			if ($valor_descuento > 0)
				$ws1->writeNote($filas, $col_valor_estimado, 'Incluye descuento por ' . $arreglo_monedas[$cobro['id_moneda_contrato']]['simbolo'] . ' ' . $valor_descuento);
		}
		else if ($valor_descuento > 0) {
			$valor_estimado = $valor_estimado - $valor_descuento;
			if ($valor_descuento > 0)
				$ws1->writeNote($filas, $col_valor_estimado, 'Incluye descuento por ' . $arreglo_monedas[$cobro['id_moneda_contrato']]['simbolo'] . ' ' . $valor_descuento);

			if ($valor_estimado < 0) {
				$valor_descuento = abs($valor_estimado);
				$valor_estimado = 0;
			}
			else
				$valor_descuento = 0;
		}



		$valor_estimado = UtilesApp::CambiarMoneda($valor_estimado, number_format($arreglo_monedas[$id_moneda_trabajos]['tipo_cambio'], $arreglo_monedas[$id_moneda_trabajos]['cifras_decimales'], '.', ''), $arreglo_monedas[$id_moneda_trabajos]['cifras_decimales'], number_format($arreglo_monedas[$cobro['id_moneda_total']]['tipo_cambio'], $arreglo_monedas[$cobro['id_moneda_total']]['cifras_decimales'], '.', ''), $arreglo_monedas[$cobro['id_moneda_total']]['cifras_decimales']);
		$valor_estimado_moneda_base = UtilesApp::CambiarMoneda($valor_estimado, number_format($arreglo_monedas[$cobro['id_moneda_total']]['tipo_cambio'], $arreglo_monedas[$cobro['id_moneda_total']]['cifras_decimales'], '.', ''), $arreglo_monedas[$cobro['id_moneda_total']]['cifras_decimales'], number_format($moneda_base['tipo_cambio'], $moneda_base['cifras_decimales'], '.', ''), $moneda_base['cifras_decimales']);
		$valor_thh_moneda_base = UtilesApp::CambiarMoneda($monto_estimado_thh, number_format($arreglo_monedas[$id_moneda_thh]['tipo_cambio'], $arreglo_monedas[$id_moneda_thh]['cifras_decimales'], '.', ''), $arreglo_monedas[$id_moneda_thh]['cifras_decimales'], number_format($moneda_base['tipo_cambio'], $moneda_base['cifras_decimales'], '.', ''), $moneda_base['cifras_decimales']);


		if ($desglosar_moneda) {
			foreach ($arreglo_monedas as $id_moneda => $moneda) {
				if ($id_moneda == $cobro['id_moneda_total']) {
					$ws1->writeNumber($filas, $col_valor_estimado_{$id_moneda}, $valor_estimado, $formatos_moneda[$cobro['id_moneda_total']]);
				} else {
					$ws1->write($filas, $col_valor_estimado_{$id_moneda}, '', $formato_texto);
				}
			}
		} else {
			$ws1->writeNumber($filas, $col_valor_estimado, $valor_estimado, $formatos_moneda[$cobro['id_moneda_total']]);
		}
		$ws1->writeNumber($filas, $col_tipo_cambio, number_format($arreglo_monedas[$cobro['id_moneda_total']]['tipo_cambio'], $arreglo_monedas[$cobro['id_moneda_total']]['cifras_decimales'], '.', ''), $formatos_moneda_tc[$id_moneda_referencia]);
		if ($id_moneda_base != $id_moneda_referencia) {
			$ws1->writeNumber($filas, $col_tipo_cambio_moneda_base, number_format($arreglo_monedas[$id_moneda_base]['tipo_cambio'], $arreglo_monedas[$id_moneda_base]['cifras_decimales'], '.', ''), $formatos_moneda_tc[$id_moneda_referencia]);
		}

		$ws1->write($filas, $col_valor_en_moneda_base, $valor_estimado_moneda_base, $formatos_moneda[$moneda_base['id_moneda']]);

		if ($valor_estimado_moneda_base < $valor_thh_moneda_base)
			$formato = $formato_moneda_base_rojo;
		else
			$formato = $formatos_moneda[$moneda_base['id_moneda']];
		$ws1->write($filas, $col_valor_en_moneda_base_segun_THH, $valor_thh_moneda_base, $formato);

		// $tact=microtime(true);
		/* $ws1->writeNumber($filas, $col_valor_en_moneda_base_segun_THH+1, round($reportecontrato->tiempos[0]-$tant,4) , $formato_numero );                     
		  $ws1->writeNumber($filas, $col_valor_en_moneda_base_segun_THH+2, round($reportecontrato->tiempos[1]-$reportecontrato->tiempos[0],4) , $formato_numero );
		  $ws1->writeNumber($filas, $col_valor_en_moneda_base_segun_THH+3, round($reportecontrato->tiempos[2]-$reportecontrato->tiempos[1],4) , $formato_numero );
		  $ws1->writeNumber($filas, $col_valor_en_moneda_base_segun_THH+4, round($reportecontrato->tiempos[3]-$reportecontrato->tiempos[2],4) , $formato_numero );
		  $ws1->writeNumber($filas, $col_valor_en_moneda_base_segun_THH+5, round($reportecontrato->tiempos[4]-$reportecontrato->tiempos[3],4) , $formato_numero );
		  $ws1->writeNumber($filas, $col_valor_en_moneda_base_segun_THH+6, round($reportecontrato->tiempos[5]-$reportecontrato->tiempos[4],4) , $formato_numero );
		  $ws1->writeNumber($filas, $col_valor_en_moneda_base_segun_THH+7, round($tact-$reportecontrato->tiempos[5],4) , $formato_numero ); */


		//$tant=$tact;
		// Excel guarda los tiempos en base a días, por eso se divide en 24.
		//$ws1->writeNumber($filas, $col_horas_trabajadas, $cobro['horas_por_cobrar']/24, $formato_tiempo);

		if ($debug) {
			if ($cobro['forma_cobro'] != 'TASA')
				$ws1->write($filas, $col_monto_contrato, $cobro['monto'], $formatos_moneda[$cobro['id_moneda_total']]);
			if ($cobro['forma_cobro'] == 'PROPORCIONAL' || $cobro['forma_cobro'] == 'RETAINER')
				$ws1->write($filas, $col_horas_retainer, $cobro['retainer_horas'], $formato_tiempo);
			if ($cobro['forma_cobro'] == 'CAP')
				$ws1->write($filas, $col_valor_cap, $usado, $formatos_moneda[$cobro['id_moneda_total']]);
			if ($cobro['forma_cobro'] == 'PROPORCIONAL' || $cobro['forma_cobro'] == 'RETAINER')
				$ws1->write($filas, $col_porcentaje_retainer, $porcentaje_retainer, $formato_numero);

			$ws1->write($filas, $col_porcentaje_retainer + 1, $cobro['horas_por_cobrar'], $formato_numero);
		}
		// Memorizarse el id_contrato para ver en el proximo
		// paso si todavia estamos en el mismo contrato, importante por el tema del descuento
		$id_contrato_anterior = $id_contrato;
	}

	if ($fila_inicial != ($filas + 2)) {
		// Escribir totales
		$col_formula_valor_en_moneda_base = Utiles::NumToColumnaExcel($col_valor_en_moneda_base);
		$ws1->writeFormula(++$filas, $col_valor_en_moneda_base, "=SUM($col_formula_valor_en_moneda_base$fila_inicial:$col_formula_valor_en_moneda_base$filas)", $formatos_moneda[$moneda_base['id_moneda']]);

		$col_formula_valor_en_moneda_base_segun_THH = Utiles::NumToColumnaExcel($col_valor_en_moneda_base_segun_THH);
		$ws1->writeFormula($filas, $col_valor_en_moneda_base_segun_THH, "=SUM($col_formula_valor_en_moneda_base_segun_THH$fila_inicial:$col_formula_valor_en_moneda_base_segun_THH$filas)", $formatos_moneda[$moneda_base['id_moneda']]);

		$col_formula_horas_trabajadas = Utiles::NumToColumnaExcel($col_horas_trabajadas);

		if (UtilesApp::GetConf($sesion, 'TipoIngresoHoras') == 'decimal') {
			$ws1->writeFormula($filas, $col_horas_trabajadas, "=SUM($col_formula_horas_trabajadas$fila_inicial:$col_formula_horas_trabajadas$filas)", $fdd);
		} else {
			$ws1->writeFormula($filas, $col_horas_trabajadas, "=SUM($col_formula_horas_trabajadas$fila_inicial:$col_formula_horas_trabajadas$filas)", $formato_tiempo);
		}
	}
	$tfin = time();
	$ws1->write(3, 3, "demora " . ($tfin - $tini) . " segundos", $formato_texto);
	$ws1->write(3, 4, "desde " . $fecha1 . " a " . $fecha2, $formato_texto);
	$wb->send("Planilla horas por facturar.xls");
	$wb->close();

	//   mail('ffigueroa@lemontech.cl','gen reporte',"Demoró mas o menos ".($tfin-$tini)." segundos y esta es la query \n".$query);
	exit;
}

$pagina->titulo = __('Reporte Horas por Facturar');
$pagina->PrintTop();
?>
<script type="text/javascript">
    function MostrarOpcionesParaOcultar()
    {
        $('tr_opciones_ocultar').style.display = 'table-row';
        $('abrir_opciones_ocultar').style.display = 'none';
        $('cerrar_opciones_ocultar').style.display = 'block';
    }
    function OcultarOpcionesParaOcultar()
    {
        $('tr_opciones_ocultar').style.display = 'none';
        $('abrir_opciones_ocultar').style.display = 'block';
        $('cerrar_opciones_ocultar').style.display = 'none';
    }
    
</script>
<style>
	.formwidth {width:<?php echo ($AtacheSecundarioSoloAsunto ? 600 : 400); ?>px;}
</style>
<form method=post name=formulario action="planilla_facturacion_pendiente.php?xls=1">

    <input type="hidden" name="reporte" value="generar" />
	<table  class="border_plomo tb_base" style="width:<?php echo ($AtacheSecundarioSoloAsunto ? 650 : 400); ?>px;">
		<tr><td>&nbsp;&nbsp;&nbsp;</td>
			<td  >
<?php echo __('Fecha desde') ?>
			</td> <td   colspan="2">
				<input type="text" class="fechadiff" name="fecha1" id="fecha1" value="<?php echo ($fecha1?$fecha1:date('d-m-Y',strtotime('-1 year'))); ?>"/>			</td>
			<td>&nbsp;</td>
		</tr><tr><td>&nbsp;</td>
			<td   >
<?php echo __('Fecha hasta') ?>
			</td> <td   colspan="2">
				<input type="text" class="fechadiff" name="fecha2" id="fecha2" value="<?php echo ($fecha2?$fecha2:date('d-m-Y')); ?>"/>
			</td><td>&nbsp;</td>
		</tr><tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
		<tr>


<?php
echo '<td style="text-align:center;" colspan="' . ($AtacheSecundarioSoloAsunto ? 2 : 5) . '">';
echo 'Filtrar por ' . __('Encargado Comercial') . '<br/>(Opcional)<br/>';
echo Html::SelectQuery($sesion, "SELECT usuario.id_usuario,CONCAT_WS(' ',apellido1,apellido2,',',nombre)
					FROM usuario JOIN usuario_permiso USING(id_usuario)
					WHERE codigo_permiso='SOC' ORDER BY apellido1", "socios[]", $socios, "class=\"selectMultiple\" multiple size=12 ", "", "260");
?>
			</td>
<?php if ($AtacheSecundarioSoloAsunto) { ?>
				<td>&nbsp;</td><td style="text-align:center;" colspan="2">
	<?php echo 'Filtrar por ' . __('Encargado Secundario') . ' del ' . __('Asunto') . '<br/>(Opcional)<br/>';
	echo Html::SelectQuery($sesion, "SELECT usuario.id_usuario,CONCAT_WS(' ',apellido1,apellido2,',',nombre)
					FROM usuario JOIN usuario_permiso USING(id_usuario)
					WHERE codigo_permiso='PRO' ORDER BY apellido1", "encargados[]", $encargados, "class=\"selectMultiple\" multiple size=12 ", "", "260");
	?>
				</td>
				<?php } ?>
		</tr>
		<tr><td>&nbsp;</td>
			<td  align="left" colspan="4">
				<div class="formwidth"> &nbsp;&nbsp;&nbsp;
					<input type="checkbox" value=1 name="separar_asuntos" <?php echo $separar_asuntos ? 'checked' : '' ?> /><?php echo __('Separar Asuntos') ?><br/>
					&nbsp;&nbsp;&nbsp;
					<input type="checkbox" value=1 name="desglosar_moneda" <?php echo $desglosar_moneda ? 'checked' : '' ?> /><?php echo __('Desglosar monto por monedas') ?><br/></div>
			</td>
		</tr>

		<tr><td>&nbsp;</td>
			<td  align="left" colspan="4">
				<div id="abrir_opciones_ocultar" onclick="MostrarOpcionesParaOcultar();" style="display:block;"><img src=<?php echo Conf::ImgDir() . '/mas.gif' ?>  />&nbsp;<b><?php echo __('Ocultar columnas:') ?></b></div>
				<div id="cerrar_opciones_ocultar" onclick="OcultarOpcionesParaOcultar();" style="display:none;"><img src=<?php echo Conf::ImgDir() . '/menos.gif' ?>  />&nbsp;<b><?php echo __('Ocultar columnas:') ?></b></div>
			</td>
		</tr>
		<tr  id="tr_opciones_ocultar" style="display:none;">
			<td>&nbsp;</td> <td align="left" colspan="4">
			<?php
			if ($_POST['reporte'] != 'generar') {
				$ocultar_encargado = UtilesApp::GetConf($sesion, 'OcultarColumnasHorasPorFacturar');
				$ocultar_ultimo_trabajo = UtilesApp::GetConf($sesion, 'OcultarColumnasHorasPorFacturar');
				$ocultar_ultimo_cobro = UtilesApp::GetConf($sesion, 'OcultarColumnasHorasPorFacturar');
				$ocultar_estado_ultimo_cobro = UtilesApp::GetConf($sesion, 'OcultarColumnasHorasPorFacturar');
			}
			?>
				&nbsp;&nbsp;&nbsp;<input type="checkbox" value=1 name="ocultar_encargado" <?php echo $ocultar_encargado ? 'checked="checked"' : '' ?> /><?php echo __('Ocultar columna') . ' ' . __('encargado') ?><br/>
				&nbsp;&nbsp;&nbsp;<input type="checkbox" value=1 name="ocultar_ultimo_trabajo" <?php echo $ocultar_ultimo_trabajo ? 'checked="checked"' : '' ?> /><?php echo __('Ocultar columna') . ' ' . __('ultimo trabajo') ?><br/>
				&nbsp;&nbsp;&nbsp;<input type="checkbox" value=1 name="ocultar_ultimo_cobro" <?php echo $ocultar_ultimo_cobro ? 'checked="checked"' : '' ?> /><?php echo __('Ocultar columna') . ' ' . __('ultimo cobro') ?><br/>
				&nbsp;&nbsp;&nbsp;<input type="checkbox" value=1 name="ocultar_estado_ultimo_cobro" <?php echo $ocultar_estado_ultimo_cobro ? 'checked="checked"' : '' ?> /><?php echo __('Ocultar columna estado') . ' ' . __('ultimo cobro') ?><br/>
				&nbsp;&nbsp;&nbsp;<input type="checkbox" value=1 name="ocultar_fecha_corte" <?php echo $ocultar_fecha_corte ? 'checked="checked"' : '' ?> /><?php echo __('Ocultar columna') . ' ' . __('fecha de corte') ?><br/>
				&nbsp;&nbsp;&nbsp;<input type="checkbox" value=1 name="separar_asuntos" <?php echo $separar_asuntos ? 'checked="checked"' : '' ?> /><?php echo __('Separar Asuntos') ?><br/>
				&nbsp;&nbsp;&nbsp;<input type="checkbox" value=1 name="desglosar_moneda" <?php echo $desglosar_moneda ? 'checked="checked"' : '' ?> /><?php echo __('Desglosar monto por monedas') ?><br/>
<?php if ($sesion->usuario->fields['rut'] == '99511620') echo '&nbsp;&nbsp;&nbsp;<input type="checkbox" name="enviamail" id="enviamail"/> Enviar correo al admin<br/>'; ?>
			</td>
		</tr>
		<tr>

			<td align=right colspan="4">
				<input type="hidden" name="debug" value="<?php echo $debug ?>" />
				<input type="submit" class=btn value="<?php echo __('Generar reporte') ?>" name="btn_reporte">
			</td><td>&nbsp;</td>
		</tr>
	</table>
</form>
<?php
	echo(InputId::Javascript($sesion));
	$pagina->PrintBottom();
?>
