<?
	require_once 'Spreadsheet/Excel/Writer.php';
	require_once dirname(__FILE__).'/../../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../app/classes/Reporte.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';

	$sesion = new Sesion(array('REP'));
	$pagina = new Pagina($sesion);
	
	$moneda_base = Utiles::MonedaBase($sesion);
	#ARMANDO XLS
	$wb = new Spreadsheet_Excel_Writer();

	$wb->send("Planilla_resumen_profesionales.xls");

	global $formato_morado;
	global $formato_morado;

	if($moneda_base['cifras_decimales'] == 0)
		$string_decimales = "";
	else if($moneda_base['cifras_decimales'] == 1)
		$string_decimales = ".0";
	else if($moneda_base['cifras_decimales'] == 2)
		$string_decimales = ".00";

	$wb->setCustomColor ( 37, 100, 86, 171 );
	$formato_morado =&  $wb->addFormat(array('Size' => 12,
                                'VAlign' => 'top',
                                'Align' => 'left',
                                'Bold' => '1',
																'BgColor' => '37',
																'fgColor' => '37',
                                'Color' => 'white'));
	$formato_duracion_morado=$formato_morado;
	if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'MostrarSoloMinutos') ) ||  ( method_exists('Conf','MostrarSoloMinutos') && Conf::MostrarSoloMinutos() )  ) )
		$formato_duracion_morado->setNumFormat("[hh]:mm");
	$formato_morado_numero =&  $wb->addFormat(array('Size' => 12,
                                'VAlign' => 'top',
                                'Align' => 'left',
                                'Bold' => '1',
                                'BgColor' => '37',
                                'fgColor' => '37',
                                'Color' => 'white'));
	$formato_morado_numero->setNumFormat("#,##0$string_decimales");


	$wb->setCustomColor ( 36, 255, 255, 220 );
	$formato_titulo_rotado =& $wb->addFormat(array('Size' => 11,
																'Valign' => 'top',
																'Align' => 'left',
																'Border' => 1,
																'fgColor' => '36',
																'Color' => 'black'));
	$formato_titulo_rotado->setTextRotation(270);
	$formato_titulo =& $wb->addFormat(array('Size' => 11,
                                'Valign' => 'top',
                                'Align' => 'left',
                                'Border' => 1,
                                'fgColor' => '36',
                                'Color' => 'black'));
	$formato_titulo->setTextWrap();
	$wb->setCustomColor ( 38, 0, 0, 128 );
	$formato_totales =& $wb->addFormat(array('Size' => 11,
                                'Valign' => 'top',
                                'Align' => 'left',
                                'Border' => 1,
                                'fgColor' => '38',
                                'Color' => 'white'));
  $formato_duracion_totales=$formato_totales;
  if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'MostrarSoloMinutos') ) ||  ( method_exists('Conf','MostrarSoloMinutos') && Conf::MostrarSoloMinutos() )  ) )
  	$formato_duracion_totales->setNumFormat("[hh]:mm");
	$formato_totales_cobro =& $wb->addFormat(array('Size' => 11,
                                'Valign' => 'top',
                                'Align' => 'left',
                                'Border' => 1,
                                'fgColor' => '38',
                                'Color' => 'white'));
	$formato_totales_cobro->setNumFormat("#,##0$string_decimales");
	$wb->setCustomColor ( 40, 204, 204, 255 );
	$formato_periodo =& $wb->addFormat(array('Size' => 11,
                                'Valign' => 'top',
                                'Align' => 'center',
																'fgColor' => '40',
                                'Border' => 1,
                                'Bold' => 1,
                                'Color' => 'black'));
	$wb->setCustomColor ( 41, 192, 192, 192 );
	$formato_cliente_asunto =& $wb->addFormat(array('Size' => 11,
                                'Valign' => 'top',
                                'Align' => 'left',
                                'Border' => 1,
                                'Color' => 'black'));
	$formato_cliente_asunto2 =& $wb->addFormat(array('Size' => 11,
                                'Valign' => 'top',
                                'Align' => 'left',
                                'fgColor' => '41',
                                'Border' => 1,
                                'Color' => 'black'));
	$formato_cliente_asunto->setTextWrap();
	$formato_cliente_asunto2->setTextWrap();
	$formato_moneda =& $wb->addFormat(array('Size' => 11,
                                'VAlign' => 'top',
                                'Align' => 'right',
                                'Border' => 1,
																'RightColor' => 'blue',
                                'Color' => 'black'));
	$formato_moneda->setNumFormat("#,##0$string_decimales");
	$wb->setCustomColor ( 39, 102,102,153 );
	$formato_moneda2 =& $wb->addFormat(array('Size' => 11,
                                'VAlign' => 'top',
                                'Align' => 'right',
                                'Border' => 1,
								'RightColor' => 'blue',
								#'TopColor' => '41',
								#'LeftColor' => '41',
                                #'fgColor' => '39',
                                'fgColor' => '41',
                                'Color' => 'black'));
	$formato_moneda2->setNumFormat("#,##0$string_decimales");
	$wb->setCustomColor ( 35, 220, 255, 220 );
	$titulo_filas =& $wb->addFormat(array('Size' => 12,
								'Align' => 'center',
								'Bold' => '1',
								'FgColor' => '35',
								'Border' => 1,
								'Locked' => 1,
								'Color' => 'black'));
									
	#$ws1->setColumn( 1, 9, 19.00);
	$hoy = date("d-m-Y");
	$filas += 1;
	#$ws1 =& $wb->addWorksheet($profesional);
	if(is_array($usuarios))	
		$lista_usuarios = join(',',$usuarios);
	else
		die(__('Usted no ha seleccionado a ningún usuario para generar el informe'));

	if($lista_usuarios == "")
		$lista_usuarios = 'NULL';

	$where= "";
	
	if(is_array($forma_cobro))
	{
		$where = $where . " AND ( ( cobro.forma_cobro IS NULL AND (contrato.forma_cobro IS NULL OR contrato.forma_cobro IN ('".join("','",$forma_cobro)."'))) OR cobro.forma_cobro IN ('".join("','",$forma_cobro)."') )";
 	}
	$query="SELECT
					CONCAT_WS(', ',usuario.apellido1,usuario.nombre) as profesional,
					CONCAT(ec.nombre,' ',ec.apellido1) as encargado,
					CONCAT_WS('-',cliente.glosa_cliente, asunto.glosa_asunto) as cliente_asunto,
					YEAR(fecha) as periodo_ano, MONTH(fecha) as periodo_mes,
					sum(time_to_sec(duracion))/3600 as dur, sum(time_to_sec(duracion_cobrada))/3600 AS dur_cobrada,
					IF(cobro.estado='INCOBRABLE','SI','NO') as incobrable,
					IF((cobro.estado = 'CREADO' OR cobro.estado = 'EN REVISION' OR cobro.estado IS NULL),'NO','SI') as cobrado,
					SUM( (tarifa_hh*TIME_TO_SEC(duracion_cobrada)/3600) * (cobro.tipo_cambio_moneda/cobro.tipo_cambio_moneda_base) * (cobro.monto/IF(cobro.monto_thh>0,cobro.monto_thh,cobro.monto)) ) as valor_cobrado,
					trabajo.cobrable
					FROM trabajo
					LEFT JOIN cobro ON trabajo.id_cobro=cobro.`id_cobro`
					LEFT JOIN asunto ON trabajo.codigo_asunto = asunto.codigo_asunto
					LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
					LEFT JOIN usuario ON usuario.id_usuario = trabajo.id_usuario
					LEFT JOIN contrato ON asunto.id_contrato = contrato.id_contrato
					LEFT JOIN usuario AS ec ON ec.id_usuario=contrato.id_usuario_responsable
					WHERE fecha BETWEEN '$fecha_ini' AND '$fecha_fin'
					AND trabajo.id_usuario IN ($lista_usuarios) 
					$where
					GROUP BY trabajo.id_usuario,trabajo.codigo_asunto, YEAR(fecha), MONTH(fecha), IF((cobro.estado = 'CREADO' OR cobro.estado = 'EN REVISION' OR cobro.estado IS NULL),'NO','SI'),trabajo.cobrable
					ORDER BY usuario.apellido1,usuario.nombre,cliente_asunto, periodo_ano ASC, periodo_mes ASC";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	$profesional = '';
	$periodo_inicial = substr($fecha_ini,0,4)*12+ substr($fecha_ini,5,2);
	$periodo_final = substr($fecha_fin,0,4)*12+ substr($fecha_fin,5,2);

	while($row = mysql_fetch_array($resp))
	{
		if($row['profesional'] != $profesional)
		{
			$cliente_asunto = '';
			if(isset($ws1))
				Print_Resumen_Prof($ws1);//Se llena la tabla resumen para poder pasar a nueva hoja
			$profesional = $row['profesional'];
			$ws1 =& $wb->addWorksheet($profesional);
			$ws1->setInputEncoding('utf-8');
			$ws1->fitToPages(1,1);
			$ws1->setZoom(75);
			$ws1->setLandscape();
			$ws1->setMarginRight(0.25);
			$ws1->setMarginLeft(0.25);
			$ws1->setLandscape();
			$ws1->freezePanes(array(0, 3));
			$fila=20;
		}
		if($row['cliente_asunto'] != $cliente_asunto)
		{
			$fila++;
			//Defino 2 colores para el formato de las filas
			if($formato != $formato_cliente_asunto)
			{
				$formato = $formato_cliente_asunto;
				$formato_cobro = $formato_moneda;
			}
			else
			{
				$formato = $formato_cliente_asunto2;
				$formato_cobro = $formato_moneda2;
			}
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'MostrarSoloMinutos') ) ||  ( method_exists('Conf','MostrarSoloMinutos') && Conf::MostrarSoloMinutos() )  ) )
			{
				$formato->setNumFormat("[hh]:mm");
			}
			
			#Parto dejando la lìnea de un solo color y formato
			for($i=2;$i<=($periodo_final-$periodo_inicial+1)*5+1;$i++)
				$ws1->write($fila,$i,'',$formato);
			for($i=6;$i<=($periodo_final-$periodo_inicial+1)*5+1;$i+=5)
				$ws1->write($fila,$i,'',$formato_cobro);

			$cliente_asunto = $row['cliente_asunto'];
			$ws1->write($fila,1,$cliente_asunto,$formato);
			$ws1->write($fila,2,$row['encargado'],$formato);
		}	
		$periodo = $row['periodo_ano']*12 + $row['periodo_mes'] - $periodo_inicial;
		$columna_relativa = $periodo*5 +3;//Se suma 3 porque todo parte de la cuarta columna
		if($row['cobrable'] == 1)
		{
#echo "prof: $profesional fila: $fila columna: $periodo ";
			if($row['cobrado'] == 'SI' && $row['incobrable']=='NO')
			{
				$ws1->writeNumber($fila,$columna_relativa,Reporte::FormatoValor($sesion,number_format($row['dur_cobrada'],2,'.',''),"horas_","excel")  ,$formato);
				$ws1->writeNumber($fila,$columna_relativa+4,$row['valor_cobrado'],$formato_cobro);
			}//aca
			else
				$ws1->writeNumber($fila,$columna_relativa+3,Reporte::FormatoValor($sesion,number_format($row['dur_cobrada'],2,'.',''),"horas_","excel"),$formato);

			if($row['dur'] != $row['dur_cobrada'])
				$ws1->writeNumber($fila,$columna_relativa+2,Reporte::FormatoValor($sesion,number_format($row['dur'] - $row['dur_cobrada'],2,'.',''),"horas_","excel"), $formato);
		}
		else
			$ws1->writeNumber($fila,$columna_relativa+1,Reporte::FormatoValor($sesion,number_format($row['dur'],2,'.',''),"horas_","excel"),$formato);

		
			
	}
	if($profesional == '') //Si no encontró ninguna tupla
 		die(__('El informe generado no encontró ningún dato'));
	Print_Resumen_Prof($ws1);//Se llena la tabla resumen para poder pasar a nueva hoja
/*
*/	

function Print_Resumen_Prof(& $ws1)
{
	global $formato_morado;
	global $formato_duracion_morado;
	global $formato_morado_numero;
	global $profesional;
	global $fecha_ini;
	global $fecha_fin;
	global $periodo_inicial;
	global $periodo_final;
	global $formato_titulo_rotado;
	global $formato_titulo;
	global $formato_periodo;
	global $formato_cliente_asunto;
	global $formato_totales;
	global $formato_duracion_totales;
	global $formato_totales_cobro;
	global $fila, $hoy;
	$fila_titulos = 13;
	
	$time_periodo = strtotime($fecha_ini);
	$ws1->write($fila+1,1,__('TOTAL'),$formato_totales);
	$ws1->write($fila+1,2,'',$formato_totales);
	$ws1->mergeCells( $fila+1, 1, $fila+1, 2 );
	
	$ws1->setColumn( 3+$i*5, 3+$i*5+3, 6);
	for($i=0;$i <= ($periodo_final-$periodo_inicial);$i++)
	{
		$ws1->write($fila_titulos-1, 3+$i*5,date('M Y',$time_periodo), $formato_periodo); //Se imprime el titulo del periodo
		$ws1->write($fila_titulos-1, 3+$i*5+1,'', $formato_periodo); //Esto es solo para que quede con formato
		$ws1->write($fila_titulos-1, 3+$i*5+2,'', $formato_periodo); 
		$ws1->write($fila_titulos-1, 3+$i*5+3,'', $formato_periodo); 
		$ws1->write($fila_titulos-1, 3+$i*5+4,'', $formato_periodo); 
		$ws1->mergeCells( $fila_titulos-1, 3+$i*5, $fila_titulos -1, 3+$i*5+4 );
		$time_periodo = strtotime('+1 month',$time_periodo);
		$ws1->setColumn( 3+$i*5, 3+$i*5+3, 6);
		$ws1->setColumn( 3+$i*5+4, 3+$i*5+4, 15);

		#Ingreso los totales por periodo
		#Horas Trabajadas
		$ws1->WriteFormula($fila_titulos,3+$i*5,'=SUM('.excel_column(3+$i*5).($fila+2).":".excel_column(3+$i*5+3).($fila+2).")",$formato_duracion_totales);
		$ws1->write($fila_titulos, 3+$i*5+1,'', $formato_totales); //Esto es solo para que quede con formato
		$ws1->write($fila_titulos, 3+$i*5+2,'', $formato_totales); 
		$ws1->write($fila_titulos, 3+$i*5+3,'', $formato_totales); 
		$ws1->write($fila_titulos, 3+$i*5+4,'', $formato_totales); 
		$ws1->mergeCells( $fila_titulos, 3+$i*5, $fila_titulos , 3+$i*5+4 );		
		
		#Ingresos Estimados
		$ws1->WriteFormula($fila_titulos+6,3+$i*5,'='.excel_column(3+$i*5+4).($fila+2)."/IF(".excel_column(3+$i*5).($fila+2).">0;".excel_column(3+$i*5).($fila+2).";1)*(".excel_column(3+$i*5).($fila+2)."+".excel_column(3+$i*5+3).($fila+2).")",$formato_totales_cobro);
		$ws1->write($fila_titulos+6, 3+$i*5+1,'', $formato_totales_cobro); //Esto es solo para que quede con formato
		$ws1->write($fila_titulos+6, 3+$i*5+2,'', $formato_totales_cobro); 
		$ws1->write($fila_titulos+6, 3+$i*5+3,'', $formato_totales_cobro); 
		$ws1->write($fila_titulos+6, 3+$i*5+4,'', $formato_totales_cobro); 
		$ws1->mergeCells( $fila_titulos+6, 3+$i*5, $fila_titulos+6 , 3+$i*5+4 );

		#Títulos columnas verticales
		$ws1->write($fila_titulos+7, 3+$i*5, __('Hrs. Cobradas'), $formato_titulo_rotado);
		$ws1->write($fila_titulos+7, 3+$i*5+1, __('Hrs. No Cobrables'), $formato_titulo_rotado);
		$ws1->write($fila_titulos+7, 3+$i*5+2, __('Hrs. Castigadas'), $formato_titulo_rotado);
		$ws1->write($fila_titulos+7, 3+$i*5+3, __('Hrs. por Cobrar'), $formato_titulo_rotado);
		$ws1->write($fila_titulos+7, 3+$i*5+4, __('Cobrado'), $formato_titulo_rotado);

		#Se ponen todos los totales
		$fila_suma_desde= $fila_titulos + 9;
		$ws1->WriteFormula($fila+1,3+$i*5,'=SUM('.excel_column(3+$i*5)."$fila_suma_desde:".excel_column(3+$i*5).($fila+1).')',$formato_duracion_totales);
		$total_hr_cobradas= excel_column(3+$i*5).($fila+2).'+'.$total_hr_cobradas;
		
		$ws1->WriteFormula($fila+1,3+$i*5+1,'=SUM('.excel_column(3+$i*5+1)."$fila_suma_desde:".excel_column(3+$i*5+1).($fila+1).')',$formato_duracion_totales);
		$total_hr_no_cobrable= excel_column(3+$i*5+1).($fila+2).'+'.$total_hr_no_cobrable;
		
		$ws1->WriteFormula($fila+1,3+$i*5+2,'=SUM('.excel_column(3+$i*5+2)."$fila_suma_desde:".excel_column(3+$i*5+2).($fila+1).')',$formato_duracion_totales);
		$total_hr_castigada= excel_column(3+$i*5+2).($fila+2).'+'.$total_hr_castigada;
		
		$ws1->WriteFormula($fila+1,3+$i*5+3,'=SUM('.excel_column(3+$i*5+3)."$fila_suma_desde:".excel_column(3+$i*5+3).($fila+1).')',$formato_duracion_totales);
		$total_hr_por_cobrar= excel_column(3+$i*5+3).($fila+2).'+'.$total_hr_por_cobrar;
		
		$ws1->WriteFormula($fila+1,3+$i*5+4,'=SUM('.excel_column(3+$i*5+4)."$fila_suma_desde:".excel_column(3+$i*5+4).($fila+1).')',$formato_totales_cobro);
		$total_cobrado= excel_column(3+$i*5+4).($fila+2).'+'.$total_cobrado;
		
		##############################################################################
		#Horas cobradas
		$ws1->WriteFormula($fila_titulos+1,3+$i*5,'=SUM('.excel_column(3+$i*5)."$fila_suma_desde:".excel_column(3+$i*5).($fila+1).')',$formato_duracion_totales);
		$ws1->write($fila_titulos+1, 3+$i*5+1,'', $formato_totales_cobro); //Esto es solo para que quede con formato
		$ws1->write($fila_titulos+1, 3+$i*5+2,'', $formato_totales_cobro); 
		$ws1->write($fila_titulos+1, 3+$i*5+3,'', $formato_totales_cobro); 
		$ws1->write($fila_titulos+1, 3+$i*5+4,'', $formato_totales_cobro); 
		$ws1->mergeCells( $fila_titulos+1, 3+$i*5, $fila_titulos +1 , 3+$i*5+4 );
		
		#Horas no cobradas
		$ws1->WriteFormula($fila_titulos+2,3+$i*5,'=SUM('.excel_column(3+$i*5+1)."$fila_suma_desde:".excel_column(3+$i*5+1).($fila+1).')',$formato_duracion_totales);
		$ws1->write($fila_titulos+2, 3+$i*5+1,'', $formato_totales_cobro); //Esto es solo para que quede con formato
		$ws1->write($fila_titulos+2, 3+$i*5+2,'', $formato_totales_cobro); 
		$ws1->write($fila_titulos+2, 3+$i*5+3,'', $formato_totales_cobro); 
		$ws1->write($fila_titulos+2, 3+$i*5+4,'', $formato_totales_cobro); 
		$ws1->mergeCells( $fila_titulos+2, 3+$i*5, $fila_titulos +2 , 3+$i*5+4 );		
		
		#Horas castigadas
		$ws1->WriteFormula($fila_titulos+3,3+$i*5,'=SUM('.excel_column(3+$i*5+2)."$fila_suma_desde:".excel_column(3+$i*5+2).($fila+1).')',$formato_duracion_totales);
		$ws1->write($fila_titulos+3, 3+$i*5+1,'', $formato_totales_cobro); //Esto es solo para que quede con formato
		$ws1->write($fila_titulos+3, 3+$i*5+2,'', $formato_totales_cobro); 
		$ws1->write($fila_titulos+3, 3+$i*5+3,'', $formato_totales_cobro); 
		$ws1->write($fila_titulos+3, 3+$i*5+4,'', $formato_totales_cobro); 
		$ws1->mergeCells( $fila_titulos+3, 3+$i*5, $fila_titulos +3 , 3+$i*5+4 );		
		
		#Horas por cobrar
		$ws1->WriteFormula($fila_titulos+4,3+$i*5,'=SUM('.excel_column(3+$i*5+3)."$fila_suma_desde:".excel_column(3+$i*5+3).($fila+1).')',$formato_duracion_totales);
		$ws1->write($fila_titulos+4, 3+$i*5+1,'', $formato_totales_cobro); //Esto es solo para que quede con formato
		$ws1->write($fila_titulos+4, 3+$i*5+2,'', $formato_totales_cobro); 
		$ws1->write($fila_titulos+4, 3+$i*5+3,'', $formato_totales_cobro); 
		$ws1->write($fila_titulos+4, 3+$i*5+4,'', $formato_totales_cobro); 
		$ws1->mergeCells( $fila_titulos+4, 3+$i*5, $fila_titulos +4 , 3+$i*5+4 );		
		
		#Ingresos devenegados
		$ws1->WriteFormula($fila_titulos+5,3+$i*5,'=SUM('.excel_column(3+$i*5+4)."$fila_suma_desde:".excel_column(3+$i*5+4).($fila+1).')',$formato_totales_cobro);
		$ws1->write($fila_titulos+5, 3+$i*5+1,'', $formato_totales_cobro); //Esto es solo para que quede con formato
		$ws1->write($fila_titulos+5, 3+$i*5+2,'', $formato_totales_cobro); 
		$ws1->write($fila_titulos+5, 3+$i*5+3,'', $formato_totales_cobro); 
		$ws1->write($fila_titulos+5, 3+$i*5+4,'', $formato_totales_cobro); 
		$ws1->mergeCells( $fila_titulos+5, 3+$i*5, $fila_titulos +5 , 3+$i*5+4 );
		##############################################################################
	}
	$ws1->write($fila_titulos, 1, __('Horas Trabajadas'), $formato_titulo);
	$ws1->write($fila_titulos, 2,'', $formato_titulo);
	$ws1->mergeCells( $fila_titulos, 1, $fila_titulos, 2 );
	$ws1->write($fila_titulos+1, 1, __('Hrs. Cobradas'), $formato_titulo);
	$ws1->write($fila_titulos+1, 2,'', $formato_titulo);
	$ws1->mergeCells( $fila_titulos+1, 1, $fila_titulos +1 , 2 );
	$ws1->write($fila_titulos+2, 1, __('Hrs. No Cobrables'), $formato_titulo);
	$ws1->write($fila_titulos+2, 2,'', $formato_titulo);
	$ws1->mergeCells( $fila_titulos+2, 1, $fila_titulos +2 , 2 );
	$ws1->write($fila_titulos+3, 1, __('Hrs. Castigadas'), $formato_titulo);
	$ws1->write($fila_titulos+3, 2,'', $formato_titulo);
	$ws1->mergeCells( $fila_titulos+3, 1, $fila_titulos +3 , 2 );
	$ws1->write($fila_titulos+4, 1, __('Hrs. por Cobrar'), $formato_titulo);
	$ws1->write($fila_titulos+4, 2,'', $formato_titulo);
	$ws1->mergeCells( $fila_titulos+4, 1, $fila_titulos +4 , 2 );
	$ws1->write($fila_titulos+5, 1, __('Ingresos devengados'), $formato_titulo);
	$ws1->write($fila_titulos+5, 2,'', $formato_titulo);
	$ws1->mergeCells( $fila_titulos+5, 1, $fila_titulos +5 , 2 );
	$ws1->write($fila_titulos+6, 1, __('Ingresos Estimados'), $formato_titulo);
	$ws1->write($fila_titulos+6, 2,'', $formato_titulo);
	$ws1->mergeCells( $fila_titulos+6, 1, $fila_titulos +6 , 2 );
	$ws1->write($fila_titulos+7, 1, __('Cliente - Asunto'), $formato_titulo);
	$ws1->write($fila_titulos+7, 2, __('Encargado Comercial'), $formato_titulo);
	$ws1->setColumn( 1, 1, 30);
	$ws1->setColumn( 2, 2, 15);

	#$ws1->mergeCells( $filas, 1, $filas, 3 );
	$ws1->write($filas, 1, __('REPORTE RENDIMIENTO PROFESIONALES'), $formato_morado);
	$ws1->mergeCells( $filas,1, $filas, 2+4 );	

	$ws1->write(++$filas, 1, strtoupper(__('Profesional')), $formato_morado);
	$ws1->write($filas, 2, $profesional, $formato_morado);
	$ws1->mergeCells( $filas, 2, $filas, 2+4 );

	$ws1->write(++$filas, 1, __('FECHA REPORTE'), $formato_morado);
	$ws1->write($filas, 2, "$hoy", $formato_morado);
	$ws1->mergeCells( $filas, 2, $filas, 2+4 );
	$ws1->write(++$filas, 1, __('PERIODO'), $formato_morado);
	$ws1->write($filas, 2, Utiles::sql2date($fecha_ini).' a '.Utiles::sql2date($fecha_fin), $formato_morado);
	$ws1->mergeCells( $filas, 2, $filas, 2+4 );
	$ws1->write(++$filas, 1, __('HORAS TRABAJADAS'), $formato_morado);
	$ws1->writeFormula($filas, 2, "=SUM(C8:C11)", $formato_duracion_morado);
	$ws1->mergeCells( $filas, 2, $filas, 2+4 );
	$ws1->write(++$filas, 1, __('INGRESOS DEVENGADOS'), $formato_morado);
	$ws1->writeFormula($filas, 2, "=$total_cobrado 0", $formato_morado_numero);
	$ws1->mergeCells( $filas, 2, $filas, 2+4 );
	$ws1->write(++$filas, 1, __('INGRESOS ESTIMADOS'), $formato_morado);
	$ws1->writeFormula($filas, 2, "=C6/IF(C8>0;C8;1)*(C8+C11)", $formato_morado_numero);
	$ws1->mergeCells( $filas, 2, $filas, 2+4 );
	$ws1->write(++$filas, 1, __('HORAS COBRADAS'), $formato_morado);
	$ws1->writeFormula($filas, 2, "=$total_hr_cobradas 0", $formato_duracion_morado);
	$ws1->mergeCells( $filas, 2, $filas, 2+4 );
	$ws1->write(++$filas, 1, __('HORAS NO COBRABLES'), $formato_morado);
	$ws1->writeFormula($filas, 2, "=$total_hr_no_cobrable 0", $formato_duracion_morado);
	$ws1->mergeCells( $filas, 2, $filas, 2+4 );
	$ws1->write(++$filas, 1, __('HORAS CASTIGADAS'), $formato_morado);
	$ws1->writeFormula($filas, 2, "=$total_hr_castigada 0", $formato_duracion_morado);
	$ws1->mergeCells( $filas, 2, $filas, 2+4 );
	$ws1->write(++$filas, 1, __('HORAS POR COBRAR'), $formato_morado);
	$ws1->writeFormula($filas, 2, "=$total_hr_por_cobrar 0", $formato_duracion_morado);
	$ws1->mergeCells( $filas, 2, $filas, 2+4 );
}
function excel_column($col_number) 
{
	if( ($col_number < 0) || ($col_number > 701)) die('Column must be between 0(A) and 701(ZZ)');
	if($col_number < 26) 
	{
		return(chr(ord('A') + $col_number));
	}
	else
	{
		$remainder = floor($col_number / 26) - 1;
		return(chr(ord('A') + $remainder) . excel_column($col_number % 26));
	}
}
	$wb->close();
    exit;
?>
