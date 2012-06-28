<?php

	require_once dirname(__FILE__).'/../../conf.php';
    require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
    require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
    require_once Conf::ServerDir().'/../fw/classes/Lista.php';
    require_once Conf::ServerDir().'/../app/classes/Debug.php';
    require_once Conf::ServerDir().'/../app/classes/Moneda.php';
    require_once Conf::ServerDir().'/../app/classes/Gasto.php';
    require_once Conf::ServerDir().'/classes/Funciones.php'; 
    require_once Conf::ServerDir().'/classes/UtilesApp.php';


$sesion = new Sesion(array('ADM'));
 $pagina = new Pagina( $sesion );
set_time_limit(300);
$currency = array();
$querycurrency = "select * from prm_moneda";
$respcurrency = mysql_query($querycurrency, $sesion->dbh) or die('NOO');
$i = 0;
while ($filac = mysql_fetch_assoc($respcurrency)) {
	$currency[++$i] = $filac;
	 
}

class HTMLtoXLS {

	private $filename;	//Filename which the excel file will be returned as
    private $rowNo = 0;	// Keep track of the row numbers
	private $handle;
	private $tini;

	#Class constructor
	function __construct($filename) { 
		$this->filename = $filename;
		$this->tini=time();
		//if(!$this->handle=fopen($filename,"w")) die('No se pudo abrir '.$filename);
		// if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) ob_start("ob_gzhandler"); else ob_start();
		header('Content-Type: application/vnd.ms-excel');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('content-disposition: attachment;filename=planillon_gastos.xls');

		echo '<HTML LANG="es">
		<title>Planillon de Gastos</title>
		<TITLE>Planillon de Gastos</TITLE>
		</head>
		<body>';
	}
 

	public function addHeader($tablaheader) {
	#Accepts an array or var which gets added to the top of the spreadsheet as a header.

		fwrite($this->handle,'<table>');
		
		foreach($tablaheader as $filaheader) fwrite($this->handle,'<tr><td>'.implode('</td><td>',$filaheader).'</td></tr>');
		fwrite($this->handle,'</table>');
		fwrite($this->handle,'<table border="1">');
	}
	
	public function echoHeader($tablaheader) {
	#Accepts an array or var which gets added to the top of the spreadsheet as a header.

		echo '<table>';
		
		foreach($tablaheader as $filaheader)  echo '<tr><td>'.implode('</td><td>',$filaheader).'</td></tr>';
		echo '</table>';
		echo '<table border="1">';
	}
	

	public function addRow($fila,$colordefondo='') {
	#Accepts an array or var which gets added to the spreadsheet body

		if($colordefondo=='') {
			fwrite($this->handle,'<tr><td>'.implode('</td><td>',$fila).'</td></tr>');
		} else {
			fwrite($this->handle,'<tr><td bgcolor="'.$colordefondo.'">'.implode('</td><td bgcolor="'.$colordefondo.'">',$fila).'</td></tr>');
		}
	
	}
 	public function echoRow($fila,$colordefondo='') {
	#Accepts an array or var which gets added to the spreadsheet body

		if($colordefondo=='') {
			echo '<tr><td>'.implode('</td><td>',$fila).'</td></tr>';
		} else {
			echo '<tr><th width="70" bgcolor="'.$colordefondo.'"><b>'.implode('</b></th><th width="90" style="width:90pt;" bgcolor="'.$colordefondo.'"><b>',$fila).'</b></th></tr>';
		}
	
	}

 
	

 
}



########################### SQL INFORME DE GASTOS #########################
$where = 1;
if ($cobrado == 'NO') {
	$where .= " AND cta_corriente.id_cobro is null ";
}
if ($cobrado == 'SI') {
	$where .= " AND cta_corriente.id_cobro is not null AND (cobro.estado = 'EMITIDO' OR cobro.estado = 'PAGADO' OR cobro.estado = 'ENVIADO AL CLIENTE' OR cobro.estado = 'FACTURADO' OR cobro.estado = 'PAGO PARCIAL') ";
}
if ($codigo_cliente) {
	$where .= " AND cta_corriente.codigo_cliente = '$codigo_cliente'";
}
if ($codigo_asunto) {
	$where .= " AND cta_corriente.codigo_asunto = '$codigo_asunto'";
}
if ($id_usuario_responsable) {
	$where .= " AND contrato.id_usuario_responsable = '$id_usuario_responsable'";
}
if ($id_usuario_orden) {
	$where .= " AND cta_corriente.id_usuario_orden = '$id_usuario_orden'";
}
if ($id_tipo) {
	$where .= " AND cta_corriente.id_cta_corriente_tipo = '$id_tipo'";
}
if ($clientes_activos == 'activos') {
	$where .= " AND ( ( cliente.activo = 1 AND asunto.activo = 1 ) OR ( cliente.activo AND asunto.activo IS NULL ) ) ";
}
if ($clientes_activos == 'inactivos') {
	$where .= " AND ( cliente.activo != 1 OR asunto.activo != 1 ) ";
}
if ($fecha1 && $fecha2) {
	$where .= " AND cta_corriente.fecha BETWEEN '" . Utiles::fecha2sql($fecha1) . "' AND '" . Utiles::fecha2sql($fecha2) . ' 23:59:59' . "' ";
} else if ($fecha1) {
	$where .= " AND cta_corriente.fecha >= '" . Utiles::fecha2sql($fecha1) . "' ";
} else if ($fecha2) {
	$where .= " AND cta_corriente.fecha <= '" . Utiles::fecha2sql($fecha2) . "' ";
} else if (!empty($id_cobro)) {
	$where .= " AND cta_corriente.id_cobro = '$id_cobro' ";
}

// Filtrar por moneda del gasto
if ($moneda_gasto != '') {
	$where .= " AND cta_corriente.id_moneda=$moneda_gasto ";
}





$col_select = "";
$filtrocobrable = false;
if (UtilesApp::GetConf($sesion, 'UsarGastosCobrable')) {
	$col_select = " ,if(cta_corriente.cobrable = 1,'Si','No') as esCobrable ";
	$filtrocobrable = true;
}
if (UtilesApp::GetConf($sesion, 'UsaAfectoImpuesto')) {
	$col_select .= ", IF( cta_corriente.con_impuesto IS NOT NULL, cta_corriente.con_impuesto, ' - ') as afecto_impuesto";
}
if (UtilesApp::GetConf($sesion, 'PrmGastos') && !(UtilesApp::GetConf($sesion, 'PrmGastosActualizarDescripcion'))) {
	$col_select .= ", IF( cta_corriente.id_glosa_gasto IS NOT NULL, prm_glosa_gasto.glosa_gasto, '-') as concepto";
}




$total_balance_egreso = 0;
$total_balance_ingreso = 0;

$querygastos = "SELECT  SQL_BIG_RESULT SQL_NO_CACHE  cta_corriente.egreso, cta_corriente.ingreso, cta_corriente.monto_cobrable, cta_corriente.codigo_cliente, cliente.glosa_cliente, 
					cta_corriente.id_cobro, cta_corriente.id_moneda, prm_moneda.simbolo, cta_corriente.fecha, asunto.codigo_asunto, asunto.glosa_asunto,
					cta_corriente.descripcion, prm_cta_corriente_tipo.glosa as glosa_tipo, cta_corriente.numero_documento,
					cta_corriente.numero_ot, cta_corriente.codigo_factura_gasto, cta_corriente.fecha_factura, prm_tipo_documento_asociado.glosa as tipo_doc_asoc, 
					prm_moneda.cifras_decimales, cobro.estado
					$col_select,
					prm_proveedor.rut as rut_proveedor, prm_proveedor.glosa as nombre_proveedor,
					CONCAT(usuario.apellido1 , ', ' , usuario.nombre) as usuario_ingresa,
					CONCAT(usuario2.apellido1 , ', ' , usuario2.nombre) as usuario_ordena
					FROM cta_corriente 
					LEFT JOIN asunto USING(codigo_asunto)
					LEFT JOIN contrato ON asunto.id_contrato = contrato.id_contrato 
					LEFT JOIN cobro ON cobro.id_cobro=cta_corriente.id_cobro 
					LEFT JOIN usuario ON usuario.id_usuario=cta_corriente.id_usuario
					LEFT JOIN usuario as usuario2 ON usuario2.id_usuario=cta_corriente.id_usuario_orden
					LEFT JOIN prm_moneda ON cta_corriente.id_moneda=prm_moneda.id_moneda
					LEFT JOIN prm_tipo_documento_asociado ON cta_corriente.id_tipo_documento_asociado = prm_tipo_documento_asociado.id_tipo_documento_asociado
					JOIN cliente ON cta_corriente.codigo_cliente = cliente.codigo_cliente
					LEFT JOIN prm_cta_corriente_tipo ON (prm_cta_corriente_tipo.id_cta_corriente_tipo = cta_corriente.id_cta_corriente_tipo)
					LEFT JOIN prm_proveedor ON ( cta_corriente.id_proveedor = prm_proveedor.id_proveedor )
					LEFT JOIN prm_glosa_gasto ON ( cta_corriente.id_glosa_gasto = prm_glosa_gasto.id_glosa_gasto )
					WHERE $where ";




 
$cabecera = array();
$col = 0;
		$col_fecha = $col++;
		 
		if (!$codigo_cliente){
			$col_cliente = $col++;
		}
		if (!$codigo_asunto){
			$col_codigo = $col++;		
			$col_asunto = $col++;
		}
		if ( UtilesApp::GetConf($sesion,'TipoGasto') ){
			$col_tipo = $col++;
		}
		if ( UtilesApp::GetConf( $sesion, 'PrmGastos') && !(UtilesApp::GetConf($sesion, 'PrmGastosActualizarDescripcion'))){
			$col_concepto = $col++;
		}
		$col_descripcion = $col++;
		$col_egreso = $col++;
		$col_ingreso = $col++;
		if ( UtilesApp::GetConf($sesion,'UsaMontoCobrable') ){
			$col_monto_cobrable = $col++;
		}
		if ( UtilesApp::GetConf($sesion,'UsaAfectoImpuesto') ){
			$col_afecto_impuesto = $col++;
		}
		$col_liquidacion = $col++;
		$col_estado = $col++;
		if ( UtilesApp::GetConf($sesion,'UsarGastosCobrable') ){
			$col_facturable = $col++;
		}
		if ( UtilesApp::GetConf($sesion,'FacturaAsociada') ){
			$col_factura = $col++;
			$col_tipo_doc = $col++;
			$col_fecha_factura = $col++;
		}
		if ( UtilesApp::GetConf($sesion,'NumeroGasto') ){
			$col_numero_documento = $col++;
		}
		if ( UtilesApp::GetConf($sesion,'NumeroOT') ){
			$col_numero_ot = $col++;
		}
		$col_rut_proveedor = $col++;
		$col_nombre_proveedor = $col++;
		$col_ingresado_por = $col++;
		$col_ordenado_por = $col++;
		

		 $cabecera[$col_fecha]=  __('Fecha');
    if(!$codigo_cliente){
    	$cabecera[$col_cliente]= __('Cliente');
	}
    if(!$codigo_asunto){
    	$cabecera[$col_codigo]= __('Código');
    	$cabecera[$col_asunto]= __('Asunto');
	}
	if ( UtilesApp::GetConf($sesion,'NumeroGasto') ){
		$cabecera[$col_numero_documento]= (__('N° Documento'));
	}
	if ( UtilesApp::GetConf($sesion,'NumeroOT') ){
		$cabecera[$col_numero_ot]=(__('N° OT'));
	}
	if ( UtilesApp::GetConf($sesion,'FacturaAsociada') ){
		$cabecera[$col_factura]=(__('N° Documento'));
		$cabecera[$col_tipo_doc]=(__('Tipo Documento'));
		$cabecera[$col_fecha_factura]=__('Fecha Documento');
	}
	if ( UtilesApp::GetConf($sesion,'TipoGasto') ){
		$cabecera[$col_tipo]= __('Tipo');
	}
	if ( UtilesApp::GetConf( $sesion, 'PrmGastos') && !(UtilesApp::GetConf($sesion, 'PrmGastosActualizarDescripcion'))){
		$cabecera[$col_concepto]= __('Concepto'); #concepto nueva forma de gastos;
	}
    $cabecera[$col_descripcion]= (__('Descripción'));
    $cabecera[$col_egreso]= __('Egreso');
	
    $cabecera[$col_ingreso]= __('Ingreso');
    $columna_balance_valor = $col_ingreso;
    if(( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable')) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ))
	{
	    $cabecera[$col_monto_cobrable]= __('Monto cobrable');
	}
if( UtilesApp::GetConf($sesion,'UsaAfectoImpuesto') ) {
	$cabecera[$col_afecto_impuesto]= __('Afecto a Impuesto');
}
    $cabecera[$col_liquidacion]= __('Cobro');
    $cabecera[$col_estado]= __('Estado Cobro');
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarGastosCobrable') ) || ( method_exists('Conf','UsarGastosCobrable') && Conf::TipoGasto() ) )
	{
		$cabecera[$col_facturable]= __('Cobrable');
	}
	$cabecera[$col_rut_proveedor]= __('RUT Proveedor');
	$cabecera[$col_nombre_proveedor]= __('Nombre Proveedor');
	$cabecera[$col_ingresado_por]= __('Creado por');
	$cabecera[$col_ordenado_por]= __('Ordenado por');
	
	
		
		
		
		
		 

$ciclo = 0;

#create an instance of the class
$xls = new HTMLtoXLS();

#lets set some headers for top of the spreadsheet
#
$headerarray=array();
if(!$fecha1) $fecha1='01-01-1990';
if(!$fecha2) $fecha2=date('d-m-Y');
$headerarray[]= array('Fecha', $fecha1,$fecha2);
$headerarray[]= array('Resumen de Gastos'); 
$headerarray[]= null;
$headerarray[]= null;
$headerarray[]= null;
$headerarray[]= null;

//$xls->addHeader($headerarray);
$xls->echoHeader($headerarray);

$formato_fecha = UtilesApp::ObtenerFormatoFecha($sesion);
//$xls->addRow($cabecera,"#33CC00");
$xls->echoRow($cabecera,"#99EE33");


 $numerogasto= (( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NumeroGasto') ) || ( method_exists('Conf','NumeroGasto') && Conf::NumeroGasto() )) ;
$numeroOT=( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NumeroOT') ) || ( method_exists('Conf','NumeroOT') && Conf::NumeroOT() ) );
 $facturaasociada=( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'FacturaAsociada') );
$tipogasto=  ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoGasto') ) || ( method_exists('Conf','TipoGasto') && Conf::TipoGasto() ) );
 $concepto= ( UtilesApp::GetConf( $sesion, 'PrmGastos') && !(UtilesApp::GetConf($sesion, 'PrmGastosActualizarDescripcion')));
 $usagastocobrable= ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarGastosCobrable') ) || ( method_exists('Conf','UsarGastosCobrable') && Conf::UsarGastosCobrable() ) );
$usamontocobrable=( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable') ) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ) );
$usaafectoaimpuesto=UtilesApp::GetConf($sesion,'UsaAfectoImpuesto'); 

$resp = mysql_unbuffered_query($querygastos) or die (mysql_error($sesion->dbh));

while ($row = mysql_fetch_array($resp)) {
	// echo $ciclo.'<br>';
 $monto_egreso=0;
 $monto_ingreso=0;
	if ($ciclo > 0) {
		if($row['egreso']>0)	$monto_egreso = $row['monto_cobrable'] * $currency[intval($row['id_moneda'])]['tipo_cambio'] / $currency[$id_moneda]['tipo_cambio'];
		if($row['ingreso']>0)	$monto_ingreso = $row['monto_cobrable'] * $currency[intval($row['id_moneda'])]['tipo_cambio'] / $currency[$id_moneda]['tipo_cambio'];
		
	} else {
		$id_moneda = $row['id_moneda'];

			if($row['egreso']>0) $monto_egreso =  $row['monto_cobrable'];
			if($row['ingreso']>0) $monto_ingreso =  $row['monto_cobrable'];
		
	}
	
	if (!$filtrocobrable || $row['esCobrable']) {
		$total_balance_egreso+=$monto_egreso;
		$total_balance_ingreso+=$monto_ingreso;
	}
	
	$fila=array();
	$ciclo++;
	$fila[$col_fecha]= Utiles::sql2date($row['fecha'], $formato_fecha);
	    if(!$codigo_cliente){
	    	$fila[$col_cliente]= $row['glosa_cliente'];
		}
	    if(!$codigo_asunto){
	    	$fila[$col_codigo]= $row['codigo_asunto'];
	    	$fila[$col_asunto]= $row['glosa_asunto'];
		}
	   
		
		if($numerogasto)		$fila[$col_numero_documento]= $row[numero_documento];
		
		
		
		if($numeroOT)		$fila[$col_numero_ot]= $row[numero_ot];
		
		if($facturaasociada)
		{
			$fila[$col_factura]= !empty($row['codigo_factura_gasto']) ? $row['codigo_factura_gasto'] : "";
			$fila[$col_tipo_doc]= !empty($row['tipo_doc_asoc']) ? $row['tipo_doc_asoc'] : "";
			$fila[$col_fecha_factura]= !empty($row['fecha_factura']) && $row['fecha_factura'] != '0000-00-00' ? Utiles::sql2fecha($row['fecha_factura'],$formato_fecha) : '-' ;
		}
	   
		
		if($tipogasto)		$fila[$col_tipo]= $row['glosa_tipo'];
		
		
		if($concepto)	$fila[$col_concepto]= $row['concepto']; #concepto nueva forma de gastos;
		
	    $fila[$col_descripcion]= $row['descripcion'];
	    if( $row['id_moneda'] > 0 || $row['id_moneda']==$id_moneda )
	    {
	    	$fila[$col_egreso]= $currency[$id_moneda]['simbolo'].' '.number_format($monto_egreso,$currency[$id_moneda]['cifras_decimales'],',','.');
	    	$fila[$col_ingreso]=$currency[$id_moneda]['simbolo'].' '.number_format($monto_ingreso,$currency[$id_moneda]['cifras_decimales'],',','.');
	    }	    else	    {      
	    	$fila[$col_egreso]= $row['ingreso'] ? '' : $row['simbolo'] . " " . number_format($row['egreso'],$row['cifras_decimales'],",",".");
			$fila[$col_ingreso]= $row['egreso'] ? '' : $row['simbolo'] . " " . number_format($row['ingreso'],$row['cifras_decimales'],",",".");
	    }
	    
		if($usagastocobrable) 		{
			if($row['esCobrable'] == 'No') {
				if($usamontocobrable) {
					$fila[$col_monto_cobrable]= $currency[$id_moneda]['simbolo'].' 0';
				}
			}	else 	{
					if($usamontocobrable) {
				
					if( $moneda_gasto > 0 || $moneda_unica ){
						$fila[$col_monto_cobrable]= $currency[$id_moneda]['simbolo'].' '.$row['monto_cobrable']; 
					} 		else			{
						$fila[$col_monto_cobrable]= $row['simbolo'] . " " . number_format($row['monto_cobrable'],$row['cifras_decimales'],",","."); 
						}
				 }	
			}
		}
	    
	    if( $usaafectoaimpuesto ) 		$fila[$col_afecto_impuesto]= $row['afecto_impuesto'];
	    $fila[$col_liquidacion]= $row['id_cobro'];
		$fila[$col_estado]= $row['estado'];
		
		if($usagastocobrable)	$fila[$col_facturable]= $row['esCobrable'];
		
		$fila[$col_rut_proveedor]= $row['rut_proveedor'];
		$fila[$col_nombre_proveedor]= $row['nombre_proveedor'];
		$fila[$col_ingresado_por]= $row['usuario_ingresa'];
		$fila[$col_ordenado_por]= $row['usuario_ordena'];
		
	
	//$xls->addRow($fila);
		$xls->echoRow($fila);

}

if ($total_balance_egreso > 0 && $total_balance_ingreso > 0) {
	$total_balance = $total_balance_ingreso - $total_balance_egreso;
} elseif ($total_balance_egreso > 0) {
	$total_balance = -$total_balance_egreso;
	
} elseif ($total_balance_ingreso > 0) {
	$total_balance = $total_balance_ingreso;
	
}

$filatotales=array();
$filatotales[]='Total Balance';

 for($col=0;$col<=$col_ingreso+1;$col++) $filatotales[]='';

$filatotales[$col_ingreso+2]=$currency[$id_moneda]['simbolo'].' '.number_format($total_balance,$currency[$id_moneda]['cifras_decimales'],',','.');

//$xls->addRow($filatotales);
$xls->echoRow($filatotales);
//$xls->saveFile();




echo '</table></body> </HTML>';





?>
