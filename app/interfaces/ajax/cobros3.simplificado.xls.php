<?php
	
	require_once dirname(__FILE__).'/../../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
        require_once Conf::ServerDir().'/classes/UtilesApp.php';

	$sesion = new Sesion(array('REV', 'ADM', 'PRO'));
	$pagina = new Pagina($sesion);
set_time_limit(300);
$currency = array();
$querycurrency = "select * from prm_moneda";
$respcurrency = mysql_query($querycurrency, $sesion->dbh) or die('NOO');
$i = 0;
while ($filac = mysql_fetch_assoc($respcurrency)) {
	$currency[$filac['id_moneda']] = $filac;
        
	 
}



class HTMLtoXLS {

	
      
	private $tini;

	#Class constructor
	function __construct() { 
		
		$this->tini=time();

		header('Content-Type: application/vnd.ms-excel');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('content-disposition: attachment;filename=planillon_trabajos.xls');

		echo '<HTML LANG="es">
		<title>Planillon de Trabajos</title>
		<TITLE>Planillon de Trabajos</TITLE>
		</head>
		<body>';
	}
 


	
	public function echoHeader($tablaheader) {
	#Accepts an array or var which gets added to the top of the spreadsheet as a header.

		echo '<table>';
		
		foreach($tablaheader as $filaheader)  echo '<tr><td>'.implode('</td><td>',$filaheader).'</td></tr>';
		echo '</table>';
		echo '<table border="1">';
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
      

	// Definición de columnas
	$col = 0;
        $ColumnaIdYCodigoAsuntoAExcelRevisarHoras=false;
        $UsoActividades=false;
        if( UtilesApp::GetConf($sesion,'ColumnaIdYCodigoAsuntoAExcelRevisarHoras') ) {
            $ColumnaIdYCodigoAsuntoAExcelRevisarHoras=true;
            $col_id_trabajo = $col++;
        }
	$col_fecha = $col++;
	$col_cliente = $col++;
        
        if($ColumnaIdYCodigoAsuntoAExcelRevisarHoras)    $col_codigo_asunto = $col++;
        
	$col_asunto = $col++;
	$col_id_cobro = $col++;
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsoActividades') ) || ( method_exists('Conf','UsoActividades') && Conf::UsoActividades() ) )
	{
	$UsoActividades=true;
            $col_actividad = $col++;
	}
        
        if( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaUsernameEnTodoElSistema') ) {
                        $UsaUsernameEnTodoElSistema=true;
			 
                } else {
			$UsaUsernameEnTodoElSistema=false;
                        
                }
                
          if( UtilesApp::GetConf($sesion,'TipoIngresoHoras') == 'decimal' ) {
                    $TipoIngresoHorasdecimal=true;
                 
                } else {
                    $TipoIngresoHorasdecimal=false;
		
                }
				
				
                    if( UtilesApp::GetConf($sesion,'CodigoSecundario') ) {
                        $CodigoSecundario=true;
                   } else {
                        $CodigoSecundario=false;
                    }
				
	$col_descripcion = $col++;
	$col_nombre_usuario = $col++;
	$col_duracion = $col++;
	$col_duracion_cobrada = $col++;
	$col_cobrable = $col++;
	$col_tarifa_hh = $col++;
	$col_valor_trabajo = $col++;
	
	 
	$col = 3;
	 

	$fila_inicial = 4;

     $cobranzapermitido=false;
        $params_array['codigo_permiso'] = 'COB';
	$p_cobranza = $sesion->usuario->permisos->Find('FindPermiso', $params_array);
	if($p_cobranza->fields['permitido']) $cobranzapermitido=true;
    
     $revisorpermitido=false;
        $params_array['codigo_permiso'] = 'REV';
		$p_revisor = $sesion->usuario->permisos->Find('FindPermiso', $params_array);
		if($p_revisor->fields['permitido']) $revisorpermitido=true;
      
     $cabecera=array();           
         if($ColumnaIdYCodigoAsuntoAExcelRevisarHoras)    $cabecera[$col_id_trabajo]= __('N° Trabajo');
         
	$cabecera[ $col_fecha]= __('Fecha');
	$cabecera[ $col_cliente]= __('Cliente');
         if($ColumnaIdYCodigoAsuntoAExcelRevisarHoras)  $cabecera[$col_codigo_asunto]= __('Código Asunto');
        
	$cabecera[$col_asunto]= __('Asunto');
	$cabecera[$col_id_cobro]= __('Cobro');
	if( $UsoActividades)	$cabecera[ $col_actividad]= __('Actividad');
	
	$cabecera[ $col_descripcion]= __('Descripción');
	$cabecera[ $col_nombre_usuario]= __('Nombre Usuario');
	$cabecera[ $col_duracion]= __('Duración');
	$cabecera[ $col_duracion_cobrada]= __('Duración cobrada');
	$cabecera[ $col_cobrable]= __('Cobrable');
	
	
            
	if($cobranzapermitido)	$cabecera[ $col_tarifa_hh]= __('Tarifa HH');
	if($cobranzapermitido)	$cabecera[ $col_valor_trabajo]= __('Valor Trabajo');
	
	$fila_inicial++;

$i = 0;

#create an instance of the class


#lets set some headers for top of the spreadsheet
#
$headerarray=array();

$headerarray[]= null;
$headerarray[]= null;
$headerarray[]= null;
$headerarray[]= null;

//$xls->addHeader($headerarray);


$formato_fecha = UtilesApp::ObtenerFormatoFecha($sesion);

$sesion->dbh2 = @mysql_connect(Conf::dbHost(), Conf::dbUser(), Conf::dbPass()) or die(mysql_error());
		mysql_select_db(Conf::dbName(),$sesion->dbh2) or die(mysql_error($sesion->dbh2));

$resptrabajo = mysql_unbuffered_query($query,$sesion->dbh2) or die (mysql_error($sesion->dbh2));




$xls = new HTMLtoXLS();
$xls->echoHeader($headerarray);
 $xls->echoRow($cabecera,"#99EE33"); 
$duracionfinal=0;
$duracioncobradafinal=0;
while ($trabajo = mysql_fetch_array($resptrabajo)) {
	
	$i++;
          $fila=array();
		
		$moneda_total = ($trabajo['id_moneda_cobro'] > 0) ? $currency[$trabajo['id_moneda_cobro']] : ( $trabajo['id_moneda_asunto'] ? $currency[$trabajo['id_moneda_asunto']] : $currency[1] ) ;
		
		// Redefinimos el formato de la moneda, para que sea consistente con la cifra.
		$simbolo_moneda = $moneda_total['simbolo'];
		$cifras_decimales = $moneda_total['cifras_decimales'];
		if($cifras_decimales)
		{
			$decimales = '.';
			while($cifras_decimales--)
				$decimales .= '0';
		}
		else
			$decimales = '';
		

                if( $ColumnaIdYCodigoAsuntoAExcelRevisarHoras)     $fila[ $col_id_trabajo]= $trabajo['id_trabajo'];
                
		$fila[ $col_fecha]= Utiles::sql2date($trabajo['fecha'], "%d-%m-%Y");
		$fila[ $col_cliente]= $trabajo['glosa_cliente'];
                if( $ColumnaIdYCodigoAsuntoAExcelRevisarHoras)  {
                    if( $CodigoSecundario ) {
                       
                        $fila[ $col_codigo_asunto]= $trabajo['codigo_asunto_secundario'];
                    } else {
                        
                        $fila[ $col_codigo_asunto]= $trabajo['codigo_asunto'];
                    }
                }
		$fila[ $col_asunto]= $trabajo['glosa_asunto'];
		$fila[ $col_id_cobro]= $trabajo['id_cobro']?$trabajo['id_cobro']:'';
		if( $UsoActividades) 	$fila[ $col_actividad]= $trabajo[glosa_actividad];

		$text_descripcion = addslashes($trabajo['descripcion']);

		$fila[ $col_descripcion]= $text_descripcion;
		if( $UsaUsernameEnTodoElSistema ) {
                       
			$fila[ $col_nombre_usuario]= $trabajo['username'];
                } else {
			 
                        $fila[ $col_nombre_usuario]= $trabajo['usr_nombre'];
                }
                
		list($duracion, $duracion_cobrada)= explode('<br>', $trabajo['duracion']);
		list($h, $m)= explode(':', $duracion);
                $duracion_decimal = $h+$m/60;
				$dd=number_format($duracion_decimal,1,'.','');
				 $duracionfinal+=$duracion_decimal;
		$tiempo_excel = $h/(24)+ $m/(24*60); //Excel cuenta el tiempo en días
                if( $TipoIngresoHorasdecimal ) {
                  
                    $fila[ $col_duracion]= "$dd"  ;
                } else {
                   
		$fila[ $col_duracion]= "$duracion"  ;
                }

		

	
			if($trabajo['cobrable'] == 0) 		$duracion_cobrada = '00:00';
			list($h, $m)= explode(':', $duracion_cobrada);

                        $duracion_cobrada_decimal = $h+$m/60;
						$celda_dcd=number_format($duracion_cobrada_decimal, 1,'.','');
						$duracioncobradafinal+=$duracion_cobrada_decimal;
			$tiempo_excel = $h/(24)+ $m/(24*60); //Excel cuenta el tiempo en días
    	if($revisorpermitido)		{ 
			if( $TipoIngresoHorasdecimal ) {
                                $fila[ $col_duracion_cobrada]=  "$celda_dcd";
                        } else {
			$fila[ $col_duracion_cobrada]=  "$duracion_cobrada";
                    }
		} 	else {
			 $fila[ $col_duracion_cobrada]=  '';
		}
		$fila[ $col_cobrable]=  $trabajo['cobrable'] == 1 ? "SI" : "NO";
		if($cobranzapermitido)
		{
			// Tratamos de sacar la tarifa del trabajo, si no está guardada usamos la tarifa estándar.
			if( $trabajo['tarifa_hh'] > 0 && !empty($trabajo['estado_cobro']) && $trabajo['estado_cobro'] != 'CREADO' && $trabajo['estado_cobro'] != 'EN REVISION' ) {
			$tarifa = $trabajo['tarifa_hh'];
			} elseif($trabajo['tarifa2']>0) 	{
					$tarifa = $trabajo['tarifa2'];
				} 	else {
					$tarifa=0;
				}
			}
			$fila[ $col_tarifa_hh]=  $simbolo_moneda.' '.number_format($tarifa,1,'.','');
                        
			$fila[$col_valor_trabajo]=$simbolo_moneda.' '. number_format($duracion_cobrada_decimal*$tarifa,1,'.','');
             
            
             $xls->echoRow($fila);
	}
       
        /*if( $TipoIngresoHorasdecimal ) {
           $fila[   $col_duracion, "=SUM($col_formula_duracion".($fila_inicial+1).":$col_formula_duracion".($fila_inicial+$i).")", $fdd);
           $fila[   $col_duracion_cobrada, "=SUM($col_formula_duracion_cobrada".($fila_inicial+1).":$col_formula_duracion_cobrada".($fila_inicial+$i).")", $fdd);
        } else {
	$fila[  $col_duracion, "=SUM($col_formula_duracion".($fila_inicial+1).":$col_formula_duracion".($fila_inicial+$i).")", $time_format);
	$fila[  $col_duracion_cobrada, "=SUM($col_formula_duracion_cobrada".($fila_inicial+1).":$col_formula_duracion_cobrada".($fila_inicial+$i).")", $time_format);
        }*/
	// No tiene sentido sumar los totales porque pueden estar en monedas distintas.
	
	  $xls->echoRow(array('','','','','',' ','<b>Totales</b>','', number_format($duracionfinal,1,'.',''),number_format($duracioncobradafinal,1,'.','')));
        
echo '</table></body> </HTML>';



?>
