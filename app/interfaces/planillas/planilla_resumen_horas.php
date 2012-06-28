<?php
	require_once 'Spreadsheet/Excel/Writer.php';
	require_once dirname(__FILE__).'/../../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/../app/classes/Reporte.php';

	/*
		Este archivo debe ser llamado mediante require_once() desde otro archivo (actualmente solo desde app/interfaces/reporte_financiero.php)
		Necesita las liguientes variables para funcionar:
		$sesion
		$fecha1	: fecha inicio periodo consulta, en formato dd-mm-aaaa.
		$fecha2	: fecha término periodo consulta, en formato dd-mm-aaaa.
		$vista	: varible que indica la forma de agrupar los datos. Puede tomar los siguientes valores:
					- 'profesional'
					- 'mes'
					- 'glosa_cliente'
					- 'glosa_asunto' : agrupa primero por cliente y luego por asunto.
	*/

	if (!(  ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'ReportesAvanzados') ) || ( method_exists('Conf','ReportesAvanzados') && Conf::ReportesAvanzados() ) ) )
	{
		exit;
	}

	$query = "SELECT id_moneda, simbolo, cifras_decimales FROM prm_moneda WHERE moneda_base=1";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	list($id_moneda, $simbolo_moneda, $cifras_decimales) = mysql_fetch_array($resp);

	$wb = new Spreadsheet_Excel_Writer();

	$wb->setCustomColor(35, 220, 255, 220);
	$wb->setCustomColor(36, 255, 255, 220);
	$wb->setCustomColor(40, 204, 204, 255);
	$wb->setCustomColor(41, 192, 192, 192);
	$wb->setCustomColor(42, 255, 204, 0);

	// Formatos para distintos tipos de celdas
	$formato_titulo_1 =& $wb->addFormat(array('FgColor' => '35', 'Size' => 12, 'VAlign' => 'top', 'Align' => 'justify', 'Bold' => '1', 'Locked' => 1, 'Border' => 1, 'Color' => 'black'));
	$formato_titulo_2 =& $wb->addFormat(array('FgColor' => '36', 'Size' => 12, 'VAlign' => 'top', 'Align' => 'justify', 'Bold' => '1', 'Locked' => 1, 'Border' => 1, 'Color' => 'black'));
	$formato_titulo_3 =& $wb->addFormat(array('FgColor' => '40', 'Size' => 12, 'VAlign' => 'top', 'Align' => 'justify', 'Bold' => '1', 'Locked' => 1, 'Border' => 1, 'Color' => 'black'));
	$formato_titulo_4 =& $wb->addFormat(array('FgColor' => '41', 'Size' => 12, 'VAlign' => 'top', 'Align' => 'justify', 'Bold' => '1', 'Locked' => 1, 'Border' => 1, 'Color' => 'black'));
	$formato_titulo_5 =& $wb->addFormat(array('FgColor' => '42', 'Size' => 12, 'VAlign' => 'top', 'Align' => 'justify', 'Bold' => '1', 'Locked' => 1, 'Border' => 1, 'Color' => 'black'));
	$formato_nombre =& $wb->addFormat(array('Border' => 1, 'Size' => 12, 'VAlign' => 'top'));
	$formato_porcentaje =& $wb->addFormat(array('NumFormat' => '0.##%', 'Border' => 1, 'Size' => 12, 'Align' => 'right'));
	$formato_porcentaje_total =& $wb->addFormat(array('NumFormat' => '0.##%', 'Border' => 1, 'Size' => 12, 'Align' => 'right', 'Bold' => '1'));
	if($cifras_decimales)
	{
		$decimales = '.';
		while($cifras_decimales--)
			$decimales .= '#';
	}
	else
		$decimales = '';
	$formato_moneda =& $wb->addFormat(array('NumFormat' => "[$$simbolo_moneda] #,###,0$decimales", 'Border' => 1, 'Size' => 12, 'Align' => 'right'));
	$formato_moneda_total =& $wb->addFormat(array('NumFormat' => "[$$simbolo_moneda] #,###,0$decimales", 'Border' => 1, 'Size' => 12, 'Align' => 'right', 'Bold' => '1'));
	$formato_numero =& $wb->addFormat(array('NumFormat' => "#,###,0.##", 'Border' => 1, 'Size' => 12, 'Align' => 'right'));
	$formato_numero_total =& $wb->addFormat(array('NumFormat' => "#,###,0.##", 'Border' => 1, 'Size' => 12, 'Align' => 'right', 'Bold' => '1'));
	$formato_encabezado =& $wb->addFormat(array('Bold' => '1', 'Size' => 12, 'Align' => 'justify', 'VAlign' => 'top', 'Color' => 'black'));

	$offset_columnas = 1;
	$offset_filas = 2;

	// Declarar hoja
	$ws =& $wb->addWorksheet(__("Reporte financiero"));
	$ws->setInputEncoding('utf-8');
	$ws->fitToPages(1,0);
	$ws->setZoom(80);

	// Imprimir encabezado
	$ws->write($offset_filas, $offset_columnas, __("Reporte financiero"), $formato_encabezado);
	$ws->write($offset_filas+2, $offset_columnas, __("Generado el:"), $formato_encabezado);
	$ws->write($offset_filas+2, $offset_columnas+1, date("Y-m-d h:i:s"), $formato_encabezado);
	$ws->write($offset_filas+3, $offset_columnas, __("Fecha consulta:"), $formato_encabezado);
	$ws->write($offset_filas+3, $offset_columnas+1, "$fecha1 - $fecha2", $formato_encabezado);

	// Setear el ancho de las columnas y unir celdas del encabezado.
	if($offset_columnas>0)
		$ws->setColumn(0 , $offset_columnas-1, 5);
	if($vista == 'glosa_asunto')
	{
		$ws->setColumn($offset_columnas, $offset_columnas, 30);
		$ws->setColumn($offset_columnas+1, $offset_columnas+1, 15);
		$ws->setColumn($offset_columnas+2, $offset_columnas+2, 30);
		$ws->setColumn($offset_columnas+3, $offset_columnas+13, 15);
	}
	else
	{
		$ws->setColumn($offset_columnas, $offset_columnas, 30);
		$ws->setColumn($offset_columnas+1, $offset_columnas+11, 15);
	}
	$ws->mergeCells($offset_filas, $offset_columnas, $offset_filas, $offset_columnas+5);
	$ws->mergeCells($offset_filas+2, $offset_columnas+1, $offset_filas+2, $offset_columnas+5);
	$ws->mergeCells($offset_filas+3, $offset_columnas+1, $offset_filas+3, $offset_columnas+5);

	$offset_filas += 7;

	// Imprimir títulos de la tabla
	if($vista == 'glosa_asunto')
	{
		$ws->write($offset_filas, $offset_columnas, __('glosa_cliente'), $formato_titulo_1);
		++$offset_columnas;
		$ws->write($offset_filas, $offset_columnas, __('Código'), $formato_titulo_1);
		++$offset_columnas;
	}
	$ws->write($offset_filas, $offset_columnas, __($vista), $formato_titulo_1);
	$ws->write($offset_filas, $offset_columnas+1, __('Horas trabajadas'), $formato_titulo_2);
	$ws->write($offset_filas, $offset_columnas+2, __('Horas cobrables'), $formato_titulo_2);
	$ws->write($offset_filas, $offset_columnas+3, __('Horas cobrables corregidas'), $formato_titulo_2);
	$ws->write($offset_filas, $offset_columnas+4, __('Horas cobradas'), $formato_titulo_2);
	$ws->write($offset_filas, $offset_columnas+5, __('Horas pagadas'), $formato_titulo_2);
	$ws->write($offset_filas, $offset_columnas+6, __('Valor cobrado'), $formato_titulo_3);
	$ws->write($offset_filas, $offset_columnas+7, __('Valor cobrado por hora'), $formato_titulo_3);
	$ws->write($offset_filas, $offset_columnas+8, __('Costo'), $formato_titulo_4);
	$ws->write($offset_filas, $offset_columnas+9, __('Costo por hora trabajada'), $formato_titulo_4);
	$ws->write($offset_filas, $offset_columnas+10, __('Margen bruto'), $formato_titulo_5);
	$ws->write($offset_filas, $offset_columnas+11, __('Porcentaje margen'), $formato_titulo_5);

	// Varibles necesarias para obtener los distintos tipos de horas usando la clase Reporte
	$tipo_dato = array();
	$tipo_dato[] = 'horas_trabajadas';
	$tipo_dato[] = 'horas_cobrables';
	$tipo_dato[] = 'horas_visibles';
	$tipo_dato[] = 'horas_cobradas';
	$tipo_dato[] = 'horas_pagadas';
	$tipo_dato[] = 'valor_cobrado';		// Este se compara con las horas trabajadas para tener el costo real por hora.
	$tipo_dato[] = 'valor_pagado';		// Este se puede comparar con las horas pagadas.
	$tipo_dato[] = 'valor_por_pagar';

	// Número de columnas para rellenar con ceros al final, 5 de horas y 1 de valor cobrado
	$numero_columnas_a_llenar = 6;

	$fila = $offset_filas;
	$ids = array();
	$i=0;

	$meses = array(__("Enero"), __("Febrero"), __("Marzo"), __("Abril"), __("Mayo"), __("Junio"),__("Julio"),__("Agosto"),__("Septiembre"),__("Octubre"),__("Noviembre"),__("Diciembre"));
	$fecha1_a = substr($fecha1, 6);
	$fecha2_a = substr($fecha2, 6);
	$fecha1_m = substr($fecha1, 3, 2)-1;
	$fecha2_m = substr($fecha2, 3, 2);
	if($fecha2_m==12)
	{
		$fecha2_m = 0;
		++$fecha2_a;
	}

	if($vista == 'profesional')
	{
                $largo_meses = array('',31, Utiles::es_bisiesto($fecha_anio)?29:28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
                $fecha_ini = $fecha1_a.'-'.$fecha1_m.'-01';
                $fecha_fin = $fecha2_a.'-'.$fecha2_m.'-'.$largo_meses[$fecha2_m];
		if( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaUsernameEnTodoElSistema') )
			$dato_profesional = "username";
		else
			$dato_profesional = "CONCAT(apellido1,' ',apellido2,', ',nombre)";
                
                if( $seleccion == 'profesionales' ) {
                    $where_usuarios = " AND usuario_permiso.codigo_permiso = 'PRO' ";
                } 
                
                $where_usuarios .= " AND ( ( SELECT SUM(costo) FROM usuario_costo 
                                        WHERE usuario_costo.id_usuario = usuario.id_usuario 
                                          AND usuario_costo.fecha >= '$fecha_ini' 
                                          AND usuario_costo.fecha <= '$fecha_fin' ) > 0 OR 
                                        ( SELECT SUM( TIME_TO_SEC( duracion_cobrada ) ) FROM trabajo 
                                            WHERE trabajo.id_usuario = usuario.id_usuario 
                                            AND trabajo.fecha >= '$fecha_ini' 
                                            AND trabajo.fecha <= '$fecha_fin' ) > 0 ) "; 
                
		// Lista de abogados sobre los que se calculan valores.
		$query = "SELECT
									".$dato_profesional." AS nombre_usuario,
				usuario.id_usuario 
								FROM usuario
                              LEFT JOIN usuario_permiso ON usuario_permiso.id_usuario = usuario.id_usuario AND usuario_permiso.codigo_permiso = 'PRO' 
                              WHERE visible = 1 $where_usuarios 
								ORDER BY apellido1, apellido2, nombre, id_usuario";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		while(list($nombre_usuario, $id_usr) = mysql_fetch_array($resp))
		{
			$ws->write(++$fila, $offset_columnas, $nombre_usuario, $formato_nombre);
			// Se lleva un registro de las celdas vacías para después rellenarlas con ceros.
			// Se necesita porque Excel detecta un error si una celda ha sido sobreescrita y no muestra bien el archivo.
			for($j=0; $j<$numero_columnas_a_llenar; ++$j)
				$vacio[$i][$j] = true;
			$ids[] = $id_usr;
			++$i;
		}
	}
	elseif($vista == 'glosa_cliente')
	{
		// Lista de clientes sobre los que se calculan valores. Aparece el encargado comercial
		$query = "SELECT
									glosa_cliente,
									codigo_cliente
								FROM cliente
								ORDER BY glosa_cliente, id_cliente";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		while(list($nombre_cliente, $id_cli) = mysql_fetch_array($resp))
		{
			$ws->write(++$fila, $offset_columnas, $nombre_cliente, $formato_nombre);
			// Se lleva un registro de las celdas vacías para después rellenarlas con ceros.
			// Se necesita porque Excel detecta un error si una celda ha sido sobreescrita y no muestra bien el archivo.
			for($j=0; $j<$numero_columnas_a_llenar; ++$j)
				$vacio[$i][$j] = true;
			$ids[] = sprintf("%04d", $id_cli);
			++$i;
		}
	}
	elseif($vista == 'mes')
	{
		for($a=0; $a<$fecha2_a-$fecha1_a+1; ++$a)
			for($m=($a==0?$fecha1_m:0); $m<($a==$fecha2_a-$fecha1_a?$fecha2_m:12); ++$m)
			{
				$ws->write(++$fila, $offset_columnas, ($fecha1_a+$a).' - '.$meses[$m], $formato_nombre);
				// Se lleva un registro de las celdas vacías para después rellenarlas con ceros.
				// Se necesita porque Excel detecta un error si una celda ha sido sobreescrita y no muestra bien el archivo.
				for($j=0; $j<$numero_columnas_a_llenar; ++$j)
					$vacio[$i][$j] = true;
				$ids[] = ($m+1).'-'.($fecha1_a+$a);
				++$i;
			}
	}
	elseif($vista == 'glosa_asunto')
	{
		$query = "SELECT DISTINCT
									trabajo.codigo_asunto,
									cliente.glosa_cliente,
									asunto.glosa_asunto
								FROM trabajo
									LEFT JOIN asunto ON trabajo.codigo_asunto=asunto.codigo_asunto
									LEFT JOIN cliente ON asunto.codigo_cliente=cliente.codigo_cliente
									WHERE trabajo.fecha >= '".Utiles::fecha2sql($fecha1)."' AND trabajo.fecha <= '".Utiles::fecha2sql($fecha2)."'
									ORDER BY trabajo.codigo_asunto";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		$nombre_temp = '';
		$n_clientes = 0;
		while(list($codigo_asunto, $nombre_cliente, $nombre_asunto) = mysql_fetch_array($resp))
		{
			++$fila;
			if($nombre_temp != $nombre_cliente)
			{
				// Revisar si hay que fusionar varias celdas verticalmente.
				if($n_clientes>0)
				{
					$ws->mergeCells($fila-$n_clientes, $offset_columnas-2, $fila-1, $offset_columnas-2);
					$n_clientes = 0;
				}
				$ws->write($fila, $offset_columnas-2, $nombre_cliente, $formato_nombre);
				$nombre_temp = $nombre_cliente;
			}
			++$n_clientes;

			$ws->write($fila, $offset_columnas-1, $codigo_asunto, $formato_nombre);
			$ws->write($fila, $offset_columnas, $nombre_asunto, $formato_nombre);
			// Se lleva un registro de las celdas vacías para después rellenarlas con ceros.
			// Se necesita porque Excel detecta un error si una celda ha sido sobreescrita y no muestra bien el archivo.
			for($j=0; $j<$numero_columnas_a_llenar; ++$j)
				$vacio[$i][$j] = true;
			$ids[] = $codigo_asunto;
			++$i;
		}
	}

	// Imprimir las horas que existen en el sistema
	for($i=0; $i<5; ++$i)
	{
		$reporte = new Reporte($sesion);
		$reporte->id_moneda = $id_moneda;
		// $fecha1 y $fecha2 deben estar en formato dd-mm-aaaa
		$reporte->addRangoFecha($fecha1, $fecha2);
		$vista_reporte = $vista == "glosa_asunto" ? "codigo_asunto" : $vista;
		$reporte->setVista($vista_reporte);
		imprimir_datos_columna($ws, $reporte, $tipo_dato[$i], $ids, $offset_columnas+1+$i, $formato_numero, $vista);
	}

	// Imprimir valor cobrado
	$reporte = new Reporte($sesion);
	$reporte->id_moneda = $id_moneda;
	// $fecha1 y $fecha2 deben estar en formato dd-mm-aaaa
	$reporte->addRangoFecha($fecha1, $fecha2);
	$reporte->setVista($vista);
	imprimir_datos_columna($ws, $reporte, $tipo_dato[5], $ids, $offset_columnas+1+$i, $formato_moneda, $vista);

	// variables para usar en las fórmulas
	$col_trabajadas = 			Utiles::NumToColumnaExcel($offset_columnas+1);
	$col_cobrables = 			Utiles::NumToColumnaExcel($offset_columnas+2);
	$col_cobrables_corregidas =	Utiles::NumToColumnaExcel($offset_columnas+3);
	$col_cobradas = 			Utiles::NumToColumnaExcel($offset_columnas+4);
	$col_pagadas = 				Utiles::NumToColumnaExcel($offset_columnas+5);
	$col_valor_cobrado =		Utiles::NumToColumnaExcel($offset_columnas+6);
	$col_costo = 				Utiles::NumToColumnaExcel($offset_columnas+8);
	$col_margen_bruto = 		Utiles::NumToColumnaExcel($offset_columnas+10);

	// Imprimir costo
	for($t=0; $t<count($ids); ++$t)
	{
		if($vista=='profesional')
		{
			$query3 = "SELECT SUM(costo)
						FROM usuario_costo
						WHERE id_usuario=$ids[$t] AND fecha >= '".Utiles::fecha2sql($fecha1)."' AND fecha <= '".Utiles::fecha2sql($fecha2)."'";
			$resp3 = mysql_query($query3, $sesion->dbh) or Utiles::errorSQL($query3, __FILE__, __LINE__, $sesion->dbh);
			list($costo) = mysql_fetch_array($resp3);
		}
		elseif($vista=='glosa_cliente')
		{
			// $ids[$t] guarda codigo_cliente
			$duracion_mes = array('31', (Utiles::es_bisiesto($fecha2_a)?'29':'28'), '31', '30', '31', '30', '31', '31', '30', '31', '30', '31');
			$costo = 0;
			for($a=0; $a<$fecha2_a-$fecha1_a+1; ++$a)
				for($m=($a==0?$fecha1_m:0); $m<($a==$fecha2_a-$fecha1_a?$fecha2_m:12); ++$m)
				{
					$f1 = ($fecha1_a+$a)."-".sprintf("%02d", $m+1)."-01";
					$f2 = ($fecha1_a+$a)."-".sprintf("%02d", $m+1)."-".$duracion_mes[$m];
					// ids de los profesionales que trabajaron para el cliente $ids[$t] en cada mes
					$query_cliente_1 = "SELECT DISTINCT
																	trabajo.id_usuario,
																	SUM(TIME_TO_SEC(trabajo.duracion)) AS duracion
																FROM trabajo
																	LEFT JOIN cobro ON trabajo.id_cobro=cobro.id_cobro
																	LEFT JOIN asunto ON trabajo.codigo_asunto=asunto.codigo_asunto
																WHERE (cobro.codigo_cliente=$ids[$t] OR asunto.codigo_cliente=$ids[$t])
																	AND '$f1'<=trabajo.fecha AND trabajo.fecha<='$f2'
																GROUP BY trabajo.id_usuario
																ORDER BY trabajo.id_usuario";
					$resp_cliente_1 = mysql_query($query_cliente_1, $sesion->dbh) or Utiles::errorSQL($query_cliente_1, __FILE__, __LINE__, $sesion->dbh);

					// duración total trabajada por cada profesional en cada mes
					$query_cliente_2 = "SELECT DISTINCT
																	id_usuario,
																	SUM(TIME_TO_SEC(trabajo.duracion)) AS duracion
																FROM trabajo
																WHERE '$f1'<=fecha AND fecha<='$f2'
																GROUP BY id_usuario
																ORDER BY id_usuario";
					$resp_cliente_2 = mysql_query($query_cliente_2, $sesion->dbh) or Utiles::errorSQL($query_cliente_2, __FILE__, __LINE__, $sesion->dbh);

					$query_cliente_3 = "SELECT
																	id_usuario,
																	costo
																FROM usuario_costo
																WHERE MONTH(fecha)=" . ($m+1) . " AND YEAR(fecha)=" . ($a+$fecha1_a) . "
																ORDER BY id_usuario";
					$resp_cliente_3 = mysql_query($query_cliente_3, $sesion->dbh) or Utiles::errorSQL($query_cliente_3, __FILE__, __LINE__, $sesion->dbh);

					while(list($id_usuario, $dur_trabajada) = mysql_fetch_array($resp_cliente_1))
					{
						$id_2 = -23; // cualquier id que sea distinto a $id_usuario funciona
						$id_3 = -23;
						if(mysql_num_rows($resp_cliente_2))
							mysql_data_seek($resp_cliente_2, 0);
						if(mysql_num_rows($resp_cliente_3))
							mysql_data_seek($resp_cliente_3, 0);
						while($resp_cliente_2 && $id_2!=$id_usuario && list($id_2, $dur_total) = mysql_fetch_array($resp_cliente_2))
							;
						while($resp_cliente_3 && $id_3!=$id_usuario && list($id_3, $costo_mes) = mysql_fetch_array($resp_cliente_3))
							;

						if($id_2!=$id_usuario)
							$dur_total = 0;
						if($id_3!=$id_usuario)
							$costo_mes = 0;

						if($dur_total == 0)
							$costo = 0;
						else
							$costo += $costo_mes*$dur_trabajada/$dur_total;
					}
				}
		}
		elseif($vista=='mes')
		{
			list($m, $a) = split('-', $ids[$t]);
			$query3 = "SELECT
										SUM(costo)
									FROM usuario_costo
									WHERE MONTH(fecha)=$m AND YEAR(fecha)=$a";
			$resp3 = mysql_query($query3, $sesion->dbh) or Utiles::errorSQL($query3, __FILE__, __LINE__, $sesion->dbh);
			list($costo) = mysql_fetch_array($resp3);
		}
		elseif($vista=='glosa_asunto')
		{
			$duracion_mes = array('31', (Utiles::es_bisiesto($fecha2_a)?'29':'28'), '31', '30', '31', '30', '31', '31', '30', '31', '30', '31');
			$costo = 0;
			for($a=0; $a<$fecha2_a-$fecha1_a+1; ++$a)
				for($m=($a==0?$fecha1_m:0); $m<($a==$fecha2_a-$fecha1_a?$fecha2_m:12); ++$m)
				{
					$f1 = ($fecha1_a+$a)."-".sprintf("%02d", $m+1)."-01";
					$f2 = ($fecha1_a+$a)."-".sprintf("%02d", $m+1)."-".$duracion_mes[$m];
					// ids de los profesionales que trabajaron para el asunto $ids[$t] en cada mes
					$query_cliente_1 = "SELECT DISTINCT
																	trabajo.id_usuario,
																	SUM(TIME_TO_SEC(trabajo.duracion)) AS duracion
																FROM trabajo
																WHERE trabajo.codigo_asunto='$ids[$t]'
																	AND '$f1'<=trabajo.fecha AND trabajo.fecha<='$f2'
																GROUP BY trabajo.id_usuario
																ORDER BY trabajo.id_usuario";
					$resp_cliente_1 = mysql_query($query_cliente_1, $sesion->dbh) or Utiles::errorSQL($query_cliente_1, __FILE__, __LINE__, $sesion->dbh);

					// duración total trabajada por cada profesional en cada mes
					$query_cliente_2 = "SELECT DISTINCT
																	id_usuario,
																	SUM(TIME_TO_SEC(trabajo.duracion)) AS duracion
																FROM trabajo
																WHERE '$f1'<=fecha AND fecha<='$f2'
																GROUP BY id_usuario
																ORDER BY id_usuario";
					$resp_cliente_2 = mysql_query($query_cliente_2, $sesion->dbh) or Utiles::errorSQL($query_cliente_2, __FILE__, __LINE__, $sesion->dbh);

					$query_cliente_3 = "SELECT
																id_usuario,
																costo
															FROM usuario_costo
															WHERE MONTH(fecha)=" . ($m+1) . " AND YEAR(fecha)=" . ($a+$fecha1_a) . "
															ORDER BY id_usuario";
					$resp_cliente_3 = mysql_query($query_cliente_3, $sesion->dbh) or Utiles::errorSQL($query_cliente_3, __FILE__, __LINE__, $sesion->dbh);

					while(list($id_usuario, $dur_trabajada) = mysql_fetch_array($resp_cliente_1))
					{
						$id_2 = -23; // cualquier id que sea distinto a $id_usuario funciona
						$id_3 = -23;
						if(mysql_num_rows($resp_cliente_2))
							mysql_data_seek($resp_cliente_2, 0);
						if(mysql_num_rows($resp_cliente_3))
							mysql_data_seek($resp_cliente_3, 0);
						while($resp_cliente_2 && $id_2!=$id_usuario && list($id_2, $dur_total) = mysql_fetch_array($resp_cliente_2))
							;
						while($resp_cliente_3 && $id_3!=$id_usuario && list($id_3, $costo_mes) = mysql_fetch_array($resp_cliente_3))
							;

						if($id_2!=$id_usuario)
							$dur_total = 0;
						if($id_3!=$id_usuario)
							$costo_mes = 0;

						if($dur_total == 0)
							$costo = 0;
						else
							$costo += $costo_mes*$dur_trabajada/$dur_total;
					}
				}
		}

		// Imprimir valor cobrado por hora
		$ws->writeFormula($offset_filas+$t+1, $offset_columnas+7, "=IF($col_cobradas".($offset_filas+$t+2).">0, $col_valor_cobrado".($offset_filas+$t+2)."/$col_cobradas".($offset_filas+$t+2).", \"- \")", $formato_moneda);
		// Imprimir costo por profesional
		$ws->writeNumber($offset_filas+$t+1, $offset_columnas+8, $costo?$costo:0, $formato_moneda);
		// Imprimir costo por hora trabajada
		$ws->writeFormula($offset_filas+$t+1, $offset_columnas+9, "=IF($col_trabajadas".($offset_filas+$t+2).">0, $col_costo".($offset_filas+$t+2)."/$col_trabajadas".($offset_filas+$t+2).", \"- \")", $formato_moneda);
		// Imprimir margen bruto
		$ws->writeFormula($offset_filas+$t+1, $offset_columnas+10, "=$col_valor_cobrado".($offset_filas+$t+2)."-$col_costo".($offset_filas+$t+2), $formato_moneda);
		// Imprimir porcentaje margen
		$ws->writeFormula($offset_filas+$t+1, $offset_columnas+11, "=IF($col_valor_cobrado".($offset_filas+$t+2).">0, $col_margen_bruto".($offset_filas+$t+2)."/$col_valor_cobrado".($offset_filas+$t+2) . ", \"- \")", $formato_porcentaje);
	}

	// Imprimir totales, están afuera del 'for' porque usan otro formato
	++$fila;
	$ws->write($fila, $offset_columnas, __("Total"), $formato_nombre);
	$ws->writeFormula($fila, $offset_columnas+1, "=SUM($col_trabajadas".($offset_filas+2).":$col_trabajadas".($fila).")", $formato_numero_total);
	$ws->writeFormula($fila, $offset_columnas+2, "=SUM($col_cobrables".($offset_filas+2).":$col_cobrables".($fila).")", $formato_numero_total);
	$ws->writeFormula($fila, $offset_columnas+3, "=SUM($col_cobrables_corregidas".($offset_filas+2).":$col_cobrables_corregidas".($fila).")", $formato_numero_total);
	$ws->writeFormula($fila, $offset_columnas+4, "=SUM($col_cobradas".($offset_filas+2).":$col_cobradas".($fila).")", $formato_numero_total);
	$ws->writeFormula($fila, $offset_columnas+5, "=SUM($col_pagadas".($offset_filas+2).":$col_pagadas".($fila).")", $formato_numero_total);
	$ws->writeFormula($fila, $offset_columnas+6, "=SUM($col_valor_cobrado".($offset_filas+2).":$col_valor_cobrado".($fila).")", $formato_moneda_total);
	$ws->writeFormula($fila, $offset_columnas+7, "=IF($col_cobradas".($offset_filas+$t+2).">0, $col_valor_cobrado".($offset_filas+$t+2)."/$col_cobradas".($offset_filas+$t+2).", \"- \")", $formato_moneda_total);
	$ws->writeFormula($fila, $offset_columnas+8, "=SUM($col_costo".($offset_filas+2).":$col_costo".($fila).")", $formato_moneda_total);
	$ws->writeFormula($fila, $offset_columnas+9, "=IF($col_trabajadas".($offset_filas+$t+2).">0, $col_costo".($offset_filas+$t+2)."/$col_trabajadas".($offset_filas+$t+2).", \"- \")", $formato_moneda_total);
	$ws->writeFormula($offset_filas+$t+1, $offset_columnas+10, "=$col_valor_cobrado".($offset_filas+$t+2)."-$col_costo".($offset_filas+$t+2), $formato_moneda_total);
	$ws->writeFormula($offset_filas+$t+1, $offset_columnas+11, "=IF($col_valor_cobrado".($offset_filas+$t+2).">0, $col_margen_bruto".($offset_filas+$t+2)."/$col_valor_cobrado".($offset_filas+$t+2) . ", \"- \")", $formato_porcentaje_total);

	// Rellenar con ceros los espacios vacíos
	for($i=0; $i<count($ids); ++$i)
		for($j=0; $j<$numero_columnas_a_llenar; ++$j)
			if($vacio[$i][$j])
				$ws->writeNumber($offset_filas+1+$i, $offset_columnas+1+$j, 0, $j<5?$formato_numero:$formato_moneda);

	// Terminar de imprimir
	$wb->send("Planilla resumen horas.xls");
	$wb->close();

	// Sirve para imprimir una columna, usando la clase Reporte
	function imprimir_datos_columna($ws, $reporte, $tipo_dato, $ids, $columna, $formato, $vista)
	{
		global $vacio;
		global $offset_filas;
		global $offset_columnas;

		$reporte->setTipoDato($tipo_dato);
		$reporte->Query();
		$r = $reporte->toArray();
		foreach($r as $k_a => $a)
			if(is_array($a))
				foreach($a as $filtro)
					if($filtro['filtro_campo'] == 'id_usuario' || $filtro['filtro_campo'] == 'codigo_cliente' || $filtro['filtro_campo'] == 'mes' || $filtro['filtro_campo'] == 'codigo_asunto')
					{
						for($t=0; $t<count($ids); ++$t)
							if($filtro['filtro_valor'] == $ids[$t])
							{
								$ws->writeNumber($offset_filas+1+$t, $columna, $a['valor']?$a['valor']:0, $formato);
								$vacio[$t][$columna-$offset_columnas-1] = false;
								break;
							}
						break;
					}
	}
?>