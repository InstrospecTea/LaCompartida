<? 
	require_once 'Spreadsheet/Excel/Writer.php';
	require_once dirname(__FILE__).'/../../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../app/classes/UtilesApp.php';
	
	$sesion = new Sesion();
	$pagina = new Pagina($sesion);
	
	
	if( $opc == "generar_reporte" )
	{
		#ARMANDO XLS
		$wb = new Spreadsheet_Excel_Writer();

		$wb->setCustomColor(35, 220, 255, 220);
		$wb->setCustomColor(36, 255, 255, 220);
		
		$encabezado =& $wb->addFormat(array('Size' => 12,
									'VAlign' => 'top',
									'Align' => 'left',
									'Bold' => '1',
									'underline'=>1,
									'Color' => 'black'));
		$formato_texto =& $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'left',
									'FgColor' => '35',
									'Border' => 1,
									'Color' => 'black',
									'TextWrap' => 1));
		$formato_texto_sin_color =& $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'left',
									'Border' => 1,
									'Color' => 'black',
									'TextWrap' => 1));
		$formato_numero_color = & $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'center',
									'FgColor' => '35',
									'Border' => 1,
									'Color' => 'black',
									'TextWrap' => 1,
									'NumFormat' => 0));
		$formato_numero =& $wb->addFormat(array('Size' => 11,
									'VAlign' => 'top',
									'Align' => 'center',
									'Border' => 1,
									'Color' => 'black',
									'NumFormat' => 0));
		$formato_numero_decimales_color = & $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'center',
									'FgColor' => '35',
									'Border' => 1,
									'Color' => 'black',
									'TextWrap' => 1,
									'NumFormat' => "#,###,0.00"));
		$formato_numero_decimales =& $wb->addFormat(array('Size' => 11,
									'VAlign' => 'top',
									'Align' => 'center',
									'Border' => 1,
									'Color' => 'black',
									'NumFormat' => "#,###,0.00"));
		$formato_titulo =& $wb->addFormat(array('Size' => 12,
									'Align' => 'center',
									'Bold' => '1',
									'FgColor' => '35',
									'Border' => 1,
									'Locked' => 1,
									'Color' => 'black'));
									
		$ws1 =& $wb->addWorksheet(__('Demora Ingreso Horas'));
		$ws1->setInputEncoding('utf-8');
		$ws1->fitToPages(1,0);
		$ws1->setZoom(75);
		
		if( is_array($usuarios) )
			{
				$lista_usuarios = implode(',',$usuarios);
				$where .= " AND u.id_usuario IN ( ".$lista_usuarios." ) ";
			}
			
		$query_usuarios = "SELECT 
												u.id_usuario, 
												CONCAT_WS(' ', nombre, apellido1, apellido2 ) as NombreUsuario 
											FROM usuario AS u 
											JOIN usuario_permiso AS up ON u.id_usuario = up.id_usuario 
											WHERE u.activo = 1 
												AND up.codigo_permiso = 'PRO'
												$where ";
		$resp_usuarios = mysql_query($query_usuarios,$sesion->dbh) or Utiles::errorSQL($query_usuarios,__FILE__,__LINE__,$sesion->dbh);
		
		$filas = 1;
		$ws1->mergeCells($filas,1,$filas,2);
		$ws1->write($filas,1,'Reporte sobre la demora en el ingreso de horas',$encabezado);
		$ws1->write($filas,2,'',$encabezado);
		$filas += 2;
		$ws1->write($filas,1,'Trabajos desde: ',$formato_texto_sin_color);
		$ws1->write($filas++,2,$fecha1,$formato_texto_sin_color);
		$ws1->write($filas,1,'Trabajos hasta: ',$formato_texto_sin_color);
		$ws1->write($filas,2,$fecha2,$formato_texto_sin_color);
		$filas += 2;
		$cantidad_meses = Utiles::CantidadMeses( $fecha1, $fecha2 );
		
		// Setear Columnas:
		$col = 1;
		$ws1->setColumn($col,$col,40);$col++;
		while( $col < 4 * $cantidad_meses + 2 )
		{
			$ws1->setColumn($col,$col,15);$col++;
		}
		$col = 2;
		$meses = UtilesApp::ArregloMeses();
		$x_mes  = date("n",strtotime($fecha1));
		$x_anio = date("Y",strtotime($fecha1));
		$ws1->write($filas+1, $col-1, 'Nombre', $formato_titulo);
		while( $col < 4 * $cantidad_meses + 2 )
		{
			$ws1->mergeCells($filas, $col, $filas, $col+3);
			$ws1->write($filas, $col+1, $meses[$x_mes].' '.$x_anio, $formato_titulo);
			$ws1->write($filas, $col,'', $formato_titulo);
			$ws1->write($filas, $col+2,'', $formato_titulo);
			$ws1->write($filas, $col+3,'', $formato_titulo);
			$ws1->write($filas+1, $col++, 'Mayor', $formato_titulo);
			$ws1->write($filas+1, $col++, 'Menor', $formato_titulo);
			$ws1->write($filas+1, $col++, 'Promedio', $formato_titulo);
			$ws1->write($filas+1, $col++, 'N° Trabajos', $formato_titulo);
			if( $x_mes == 12 )
				{
					$x_anio++;
					$x_mes = 1;
		}
			else
				$x_mes++;
		}
		$filas += 2;
		$i = 1;
		while( list( $id_usuario, $NombreUsuario ) = mysql_fetch_array($resp_usuarios) )
		{
			$col = 1;
			$ws1->write($filas, $col++, $NombreUsuario, $formato_texto);
			$query_datos = " SELECT 
													MONTH( t.fecha ) as MesHora, 
													YEAR( t.fecha ) as AnioHora, 
													COUNT(*) as CantidadHoras, 
													MAX(DATEDIFF( t.fecha_creacion, t.fecha )) as MaxDemoraIngreso, 
													MIN(DATEDIFF( t.fecha_creacion, t.fecha )) as MinDemoraIngreso, 
													AVG(DATEDIFF( t.fecha_creacion, t.fecha )) as AvgDemoraIngreso 
												FROM trabajo t 
												WHERE t.id_usuario = '".$id_usuario."' 
													AND t.fecha > '".$fecha1."' 
													AND t.fecha < '".$fecha2."' 
												GROUP BY MONTH( t.fecha ),YEAR( t.fecha ) 
												ORDER BY YEAR( t.fecha ) ASC, MONTH( t.fecha ) ASC ";
			$resp_datos = mysql_query($query_datos,$sesion->dbh) or Utiles::errorSQL($query_datos,__FILE__,__LINE__,$sesion->dbh);
			
			$arreglo_datos = array();
			for($i=2;$i< 4*$cantidad_meses+2;$i++)
				$arreglo_datos[$i] = "0";
				
			while( list( $MesHora, $AnioHora, $CantidadHoras, $MaxDemoraIngreso, $MinDemoraIngreso, $AvgDemoraIngreso ) = mysql_fetch_array($resp_datos) )
			{
				$MesHora < 10 ? $fecha_dato = "$AnioHora-0$MesHora-01" : $fecha_dato = "$AnioHora-$MesHora-01";
				$x_factor = Utiles::CantidadMeses($fecha1,$fecha_dato) - 1;
				$i = 2 + 4 * $x_factor;
				
				$arreglo_datos[$i] = abs($MaxDemoraIngreso) ? $MaxDemoraIngreso : "0";$i++;
				$arreglo_datos[$i] = abs($MinDemoraIngreso) ? $MinDemoraIngreso : "0";$i++;
				$arreglo_datos[$i] = abs($AvgDemoraIngreso) ? $AvgDemoraIngreso : "0";$i++;
				$arreglo_datos[$i] = $CantidadHoras ? $CantidadHoras : "0";$i++;
			}
			foreach($arreglo_datos as $col => $val)
				{
					if(0 < ($col-1)%8 && ($col-1)%8 < 5)
						{
							if( ($col-1)%4 == 3 )
								$ws1->write($filas,$col,$val,$formato_numero_decimales);
							else
								$ws1->write($filas,$col,$val,$formato_numero);
						}
					else
						{ 
							if( ($col-1)%4 == 3 )
								$ws1->write($filas,$col,$val,$formato_numero_decimales_color);
							else
								$ws1->write($filas,$col,$val,$formato_numero_color);
						}
				}
			$filas++;
		}
		$wb->send("planilla_demora_ingreso_horas.xls");
		$wb->close();
		exit;
	}
	$pagina->titulo = __('Demora ingreso de horas');
	$pagina->PrintTop();
?>


<form method=post name=formulario action="#">
	<table class="border_plomo tb_base">
		<tr>
			<td align=right>
				<?=__('Fecha desde')?>
			</td>
			<td align=left>
				<?= Html::PrintCalendar("fecha1", "$fecha1"); ?>
			</td>
		</tr>
		<tr>
			<td align=right>
				<?=__('Fecha hasta')?>
			</td>
			<td align=left>
				<?= Html::PrintCalendar("fecha2", "$fecha2"); ?>
			</td>
		</tr>
		<tr>
			<td align=center colspan="2">
				<?=Html::SelectQuery($sesion,"SELECT usuario.id_usuario,CONCAT_WS(' ',apellido1,apellido2,',',nombre)
					FROM usuario JOIN usuario_permiso USING(id_usuario)
					WHERE codigo_permiso='SOC' ORDER BY apellido1", "usuarios[]", $usuarios,"class=\"selectMultiple\" multiple size=6 ","","200"); ?>
			</td>
		</tr>
		<tr>
			<td align=center colspan=2>
				<input type="hidden" name="opc" value="generar_reporte"/>
				<input type="submit" class=btn value="<?=__('Generar reporte')?>" name="btn_reporte">
			</td>
		</tr>
	</table>
</form>

<? 
$pagina->PrintBottom();
?>
