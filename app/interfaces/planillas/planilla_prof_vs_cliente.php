<?php
require_once 'Spreadsheet/Excel/Writer.php';
require_once dirname(__FILE__).'/../../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';

$sesion = new Sesion( array('REP') );
$pagina = new Pagina( $sesion );

if(Conf::GetConf($sesion,'UsaUsernameEnTodoElSistema')) {
	$dato_usuario = "username";
} else {
	$dato_usuario = "CONCAT_WS(' ',nombre,apellido1)";
}

$id_moneda_seleccionada = 3;

if ($horas == 'duracion_cobrada') {
	$horas = 'if(trabajo.cobrable=1,duracion_cobrada,0)';
}

$id_moneda_seleccionada = $moneda_mostrar;


$query = "
	SELECT 
		trabajo.id_usuario, 
		cliente.codigo_cliente 'cliente',
		SUM(TIME_TO_SEC( $horas )) 'duracion',
		glosa_grupo_cliente,
		usuario_tarifa_contrato.id_moneda 'id_moneda_contrato',
		usuario_tarifa_contrato.tarifa as 'usuario_tarifa_contrato',
		usuario_tarifa_standard.id_moneda 'id_moneda_standard',
		usuario_tarifa_standard.tarifa as 'usuario_tarifa_standard'
	FROM trabajo
		JOIN usuario ON usuario.id_usuario = trabajo.id_usuario
		LEFT JOIN tarifa as tarifa_defecto ON tarifa_defecto.tarifa_defecto = 1
		LEFT JOIN asunto ON asunto.codigo_asunto = trabajo.codigo_asunto
		LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
		LEFT JOIN contrato ON contrato.id_contrato = cliente.id_contrato
		LEFT JOIN grupo_cliente ON cliente.id_grupo_cliente = grupo_cliente.id_grupo_cliente
		
		LEFT JOIN usuario_tarifa as usuario_tarifa_contrato ON trabajo.id_usuario = usuario_tarifa_contrato.id_usuario
			AND usuario_tarifa_contrato.id_moneda = contrato.id_moneda AND contrato.id_tarifa = usuario_tarifa_contrato.id_tarifa

		LEFT JOIN usuario_tarifa as usuario_tarifa_standard ON trabajo.id_usuario = usuario_tarifa_standard.id_usuario
			AND usuario_tarifa_standard.id_moneda = contrato.id_moneda AND tarifa_defecto.id_tarifa = usuario_tarifa_standard.id_tarifa

		WHERE fecha >= '$fecha1' AND fecha <= '$fecha2'
			GROUP BY trabajo.id_usuario, asunto.codigo_cliente
			ORDER BY grupo_cliente.glosa_grupo_cliente,usuario.id_categoria_usuario";


							
$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);


for($i = 0; list($id_usuario, $cliente, $duracion, $glosa_grupo_cliente, $id_moneda_contrato, $usuario_tarifa_contrato, $id_moneda_standard, $usuario_tarifa_standard) = mysql_fetch_array($resp); $i++) {
	$usuarios[$i] = $id_usuario;
	$clientes[$i] = $cliente;
	$grupos[$cliente] = $glosa_grupo_cliente;
	# En excel el tiempo se mide en dias, por eso los segundos se dividen por 60*60*24
	$arreglo[$id_usuario][$cliente] = $duracion / (60*60*24);
	//Conversion de monedas
	//Instanciar la moneda seleccionada
	$monedaSeleccionada = new Moneda($sesion);
	$monedaSeleccionada->Load($id_moneda_seleccionada);
	//Instanciar la moneda de tarifa.
	$monedaContrato = new Moneda($sesion);
	$monedaContrato->Load($id_moneda_contrato);
	//Instanciar la moneda de tarifa standard.
	$monedaStandard = new Moneda($sesion);
	$monedaStandard->Load($id_moneda_standard);
	//(moneda_contrato.tipo_cambio * usuario_tarifa_contrato.tarifa) * (SUM(TIME_TO_SEC($horas)) / 3600) AS valor_hh,
	//(moneda_defecto.tipo_cambio * usuario_tarifa_standard.tarifa) * (SUM(TIME_TO_SEC($horas)) / 3600) AS valor_standard,
	//Realizar el tipo de cambio para el valor HH y el valor standard desde la moneda del contrato hacia la moneda seleccionada.
	$utiles = new UtilesApp();
	$valor_hh = $utiles->CambiarMoneda(
		$duracion / 3600 * $usuario_tarifa_contrato,
		$monedaContrato->fields['tipo_cambio'],
		$monedaContrato->fields['cifras_decimales'],
		$monedaSeleccionada->fields['tipo_cambio'],
		$monedaSeleccionada->fields['cifras_decimales']
	);
	$valor_standard = $utiles->CambiarMoneda(
		$duracion / 3600 * $usuario_tarifa_standard,
		$monedaStandard->fields['tipo_cambio'],
		$monedaStandard->fields['cifras_decimales'],
		$monedaSeleccionada->fields['tipo_cambio'],
		$monedaSeleccionada->fields['cifras_decimales']
	);
	//Asignar los nuevos valores al array.
	$arreglo_valor_hh[$id_usuario][$cliente] = $valor_hh;
	$arreglo_valor_standard[$id_usuario][$cliente] = $valor_standard;
}


$usuarios = array_values(array_unique($usuarios));
$clientes = array_values(array_unique($clientes));

$wb = new Spreadsheet_Excel_Writer();

$wb->setCustomColor(35, 220, 255, 220);
$wb->setCustomColor(36, 255, 255, 220);

$formato_encabezado =& $wb->addFormat(array(
	'Size' => 12,
	'VAlign' => 'top',
	'Align' => 'justify',
	'Bold' => '1',
	'Color' => 'black')
);
$formato_titulo =& $wb->addFormat(array(
	'Size' => 12,
	'VAlign' => 'top',
	'Align' => 'justify',
	'Bold' => '1',
	'Locked' => 1,
	'Border' => 1,
	'FgColor' => '35',
	'Color' => 'black')
);
$formato_texto =& $wb->addFormat(array(
	'Size' => 12,
	'VAlign' => 'top',
	'Align' => 'justify',
	'Locked' => 1,
	'Border' => 1,
	'Color' => 'black')
);
$formato_texto_total =& $wb->addFormat(array(
	'Size' => 12,
	'VAlign' => 'top',
	'Align' => 'justify',
	'Bold' => 1,
	'Locked' => 1,
	'Border' => 1,
	'Color' => 'black')
);
$formato_tiempo =& $wb->addFormat(array(
	'Size' => 10,
	'VAlign' => 'top',
	'Align' => 'right',
	'Border' => 1,
	'Color' => 'black',
	'NumFormat' => '[h]:mm')
);
$formato_tiempo_total =& $wb->addFormat(array(
	'Size' => 10,
	'VAlign' => 'top',
	'Bold' => 1,
	'Align' => 'right',
	'Border' => 1,
	'Color' => 'black',
	'NumFormat' => '[h]:mm')
);
	
$cifras_decimales = Utiles::glosa($sesion, $moneda_mostrar, 'cifras_decimales', 'prm_moneda', 'id_moneda');
$simbolo_moneda = Utiles::glosa($sesion, $moneda_mostrar, 'simbolo', 'prm_moneda', 'id_moneda');

if($cifras_decimales>0) {
	$decimales = '.';
	while($cifras_decimales-- >0){
		$decimales .= '0';
	}
} else {
	$decimales = '';
}
		
$formato_moneda =& $wb->addFormat(array(
	'Size' => 11,
	'VAlign' => 'top',
	'Align' => 'right',
	'Border' => '1',
	'Color' => 'black',
	'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales")
);
$formato_moneda_total =& $wb->addFormat(array(
	'Size' => 11,
	'VAlign' => 'top',
	'Align' => 'right',
	'Bold' => 1,
	'Border' => '1',
	'Color' => 'black',
	'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales")
);

$ws1 =& $wb->addWorksheet(__('Reportes'));
$ws1->fitToPages(1,0);
$ws1->setZoom(75);

$fila_inicial = 1;

$hoy = date("d-m-Y");
$ws1->setRow($fila_inicial, 15);
$ws1->write($fila_inicial, 1, __('PERIODO'), $formato_encabezado);
$ws1->write($fila_inicial, 2, Utiles::sql2date($fecha1).' hasta '.Utiles::sql2date($fecha2), $formato_encabezado);
$ws1->mergeCells($fila_inicial,2,$fila_inicial,count($usuarios)+1);

$fila_inicial++;

$ws1->setRow($fila_inicial, 15);
$ws1->write($fila_inicial, 1, __('FECHA REPORTE'), $formato_encabezado);
$ws1->write($fila_inicial, 2, $hoy, $formato_encabezado);
$ws1->mergeCells($fila_inicial,2,$fila_inicial,count($usuarios)+1);

$fila_inicial += 2;
$columna_inicial = 1;
$total_valor_hh_asunto = array();
$total_valor_standard_asunto = array();

$ws1->write($fila_inicial, $columna_inicial-1, __('Grupo'), $formato_titulo);
$ws1->write($fila_inicial, $columna_inicial, __('Cliente'), $formato_titulo);

for($i = 0; $i < count($usuarios); $i++) {
	
	for($j = 0; $j < count($clientes); $j++) {
		
		//	usuarios
		if($j == 0) {
			$ws1->write($fila_inicial, $columna_inicial + 1 + $i, Utiles::Glosa($sesion, $usuarios[$i], $dato_usuario, "usuario"), $formato_titulo);
		}
	
		if($i == 0) {
			$ws1->write($fila_inicial+1+$j, $columna_inicial , Utiles::Glosa($sesion, $clientes[$j], "glosa_cliente", "cliente", "codigo_cliente"), $formato_texto);
			$ws1->write($fila_inicial+1+$j, $columna_inicial -1 , $grupos[$clientes[$j]], $formato_texto);
		}

		if ($arreglo[$usuarios[$i]][$clientes[$j]] > 0) {
			$ws1->writeNumber($fila_inicial + 1 + $j, $columna_inicial + 1 + $i, $arreglo[$usuarios[$i]][$clientes[$j]], $formato_tiempo);
			$total_valor_hh_asunto[$clientes[$j]] += $arreglo_valor_hh[$usuarios[$i]][$clientes[$j]];
			$total_valor_standard_asunto[$clientes[$j]] += $arreglo_valor_standard[$usuarios[$i]][$clientes[$j]];
		} else {
			$ws1->write($fila_inicial + 1 + $j, $columna_inicial + 1 + $i, '', $formato_tiempo);
		}
	}
}
	
$ws1->setColumn(0, 1, 20);
$ws1->setColumn(2, 2 + count($usuarios), 12);
	
$columna_final = $columna_inicial + $i + 1;
$ws1->setColumn($columna_final, $columna_final+2, 18);
$fila_final = $fila_inicial + $j + 1;

$ws1->write($fila_final, $columna_inicial, __(Total), $formato_texto_total);

$ws1->write($fila_inicial,$columna_final,'Total Cliente',$formato_titulo);
$ws1->write($fila_inicial,$columna_final+1,'Valor según tarifa contrato',$formato_titulo);
$ws1->write($fila_inicial,$columna_final+2,'Valor según tarifa por defecto',$formato_titulo);

//	For para ponerle los valores totales a los clientes
for($i = 0; $i < count($clientes); $i++) {
	$ws1->write($fila_inicial + 1 + $i,$columna_final+1, str_replace(',', '.', $total_valor_hh_asunto[$clientes[$i]]),$formato_moneda);
	$ws1->write($fila_inicial + 1 + $i,$columna_final+2, str_replace(',', '.', $total_valor_standard_asunto[$clientes[$i]]),$formato_moneda);
}

$fila_inicial_datos=2;

$col_fin = Utiles::NumToColumnaExcel($fila_final - 1);

for($i = 0; $i < count($usuarios); $i++) {
	$col_ini = Utiles::NumToColumnaExcel($fila_inicial_datos +$i);
	$ws1->writeFormula($fila_final , $columna_inicial + 1 + $i, "=SUM($col_ini".($fila_inicial+2).":$col_ini".($fila_final).")", $formato_tiempo_total);
}

$col_ini = Utiles::NumToColumnaExcel(2);
$col_fin = Utiles::NumToColumnaExcel(count($usuarios) + 1);

for($j = 0; $j < count($clientes); $j++) {
	$ws1->writeFormula($fila_inicial + 1 + $j, $columna_final , "=SUM($col_ini".($fila_inicial+2+$j).":$col_fin".($fila_inicial+2+$j).")", $formato_tiempo);
}

$col_formula_total = Utiles::NumToColumnaExcel($columna_final);
$col_formula_valor_contrato = Utiles::NumToColumnaExcel($columna_final+1);
$col_formula_valor_defecto = Utiles::NumToColumnaExcel($columna_final+2);
$ws1->writeFormula($fila_final, $columna_final, "=SUM($col_formula_total".($fila_inicial+2).":$col_formula_total$fila_final)", $formato_tiempo_total);
$ws1->writeFormula($fila_final, $columna_final+1, "=SUM($col_formula_valor_contrato".($fila_inicial+2).":$col_formula_valor_contrato$fila_final)", $formato_moneda_total);
$ws1->writeFormula($fila_final, $columna_final+2, "=SUM($col_formula_valor_defecto".($fila_inicial+2).":$col_formula_valor_defecto$fila_final)", $formato_moneda_total);

$wb->send("Planilla Profesional vs Cliente.xls");
$wb->close();

exit;
?>
