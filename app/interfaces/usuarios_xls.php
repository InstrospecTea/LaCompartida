<?php
	require_once dirname(__FILE__).'/../conf.php';
  require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
  require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
  require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
  require_once Conf::ServerDir().'/../fw/classes/Html.php';
  require_once Conf::ServerDir().'/../app/classes/Debug.php';
  require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
  require_once Conf::ServerDir().'/../app/classes/Cliente.php';
  require_once Conf::ServerDir().'/../app/classes/InputId.php';
  require_once Conf::ServerDir().'/classes/Funciones.php';
  require_once 'Spreadsheet/Excel/Writer.php';

	//Parámetros generales para los 2 casos de listas a extraer
  $sesion = new Sesion( array('REV','ADM') );
  $pagina = new Pagina( $sesion );

  #$key = substr(md5(microtime().posix_getpid()), 0, 8);
  $wb = new Spreadsheet_Excel_Writer();
  $wb->setCustomColor ( 35, 220, 255, 220 );
  $wb->setCustomColor ( 36, 255, 255, 220 );
  $encabezado =& $wb->addFormat(array('Size' => 12,
	                              'VAlign' => 'top',
	                              'Align' => 'justify',
	                              'Bold' => '1',
	                              'Color' => 'black'));
  $tit =& $wb->addFormat(array('Size' => 12,
	                              'VAlign' => 'top',
	                              'Align' => 'justify',
	                              'Bold' => '1',
	                              'Locked' => 1,
	                              'Border' => 1,
	                              'FgColor' => '35',
	                              'Color' => 'black'));
	$f3c =& $wb->addFormat(array('Size' => 10,
	                              'Align' => 'left',
	                              'Bold' => '1',
	                              'FgColor' => '35',
	                              'Border' => 1,
	                              'Locked' => 1,
	                              'Color' => 'black'));
	$f4 =& $wb->addFormat(array('Size' => 10,
	                              'VAlign' => 'top',
	                              'Align' => 'justify',
	                              'Border' => 1,
	                              'Color' => 'black'));
  $ws1 =& $wb->addWorksheet(__('Usuarios'));
	$ws1->setInputEncoding('utf-8');
	$ws1->fitToPages(1,0);
	$ws1->setZoom(75);
	#$ws1->protect( $key );
	if (method_exists('Conf','GetConf'))
	{
		$PdfLinea1 = Conf::GetConf($sesion, 'PdfLinea1');
		$PdfLinea2 = Conf::GetConf($sesion, 'PdfLinea2');
	}
	else
	{
		$PdfLinea1 = Conf::PdfLinea1();
		$PdfLinea2 = Conf::PdfLinea2();
	}
	$where = 1;
	if ($activo == 1)
		$where .= " AND u.activo = $activo AND (nombre LIKE '%$nombre%' OR apellido1 LIKE '%$nombre%' OR apellido2 LIKE '%$nombre%')";
  
  //Lista de vacaciones
  if(!empty($vacacion))
  {
  	$wb->send('Lista_vacaciones_usuarios.xls');
  	$ws1->setColumn( 1, 1, 25.00);
		$ws1->setColumn( 2, 2, 25.00);
		$ws1->setColumn( 3, 3, 20.00);
		$ws1->setColumn( 4, 4, 20.00);
		$ws1->write(0, 0, 'Lista de vacaciones de Usuarios', $encabezado);
		$ws1->mergeCells (0, 0, 0, 8);
		$info_usr1 = str_replace('<br>',' - ',$PdfLinea1);
		$ws1->write(2, 0, utf8_decode($info_usr1), $encabezado);
		$ws1->mergeCells (2, 0, 2, 8);
		$info_usr = str_replace('<br>',' - ',$PdfLinea2);
		$ws1->write(3, 0, utf8_decode($info_usr), $encabezado);
		$ws1->mergeCells (3, 0, 3, 8);
		$i=0;
		$fila_inicial = 7;
		if( ( method_exists('Conf','GetConf') && strtolower(Conf::GetConf($sesion,'NombreIdentificador'))=='rut' ) || ( method_exists('Conf','NombreIdentificador') && strtolower(Conf::NombreIdentificador())=='rut' ) )
			$glosa_rut = 'Rut';
		else
			$glosa_rut = 'CNI';
		
		$ws1->write($fila_inicial, 1, __('Nombre'), $tit);	  
	  $ws1->write($fila_inicial, 2, $glosa_rut, $tit);
	  $ws1->write($fila_inicial, 3, __('Fecha inicio'), $tit);
	  $ws1->write($fila_inicial, 4, __('Fecha fin'), $tit);
	  $fila_inicial++;

		$query = "SELECT u.id_usuario, CONCAT_WS(' ',u.nombre, u.apellido1, u.apellido2) AS nombre, u.rut, u.dv_rut,
										UV.fecha_inicio, UV.fecha_fin
										FROM usuario AS u
										JOIN usuario_vacacion AS UV on u.id_usuario = UV.id_usuario
										WHERE $where ORDER BY u.id_usuario, nombre";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);	
	  while($row = mysql_fetch_assoc($resp))
	  {
			$i=0;
			$ws1->write($fila_inicial, 1, $row[nombre], $f4);
	    $ws1->write($fila_inicial, 2, $row[rut].'-'.$row[dv_rut], $f4);
	    $ws1->write($fila_inicial, 3, Utiles::sql2date($row[fecha_inicio]), $f4);
	    $ws1->write($fila_inicial, 4, Utiles::sql2date($row[fecha_fin]), $f4);
	    $fila_inicial++;
		}
	}
	else if( !empty($modificaciones) )
	{
		$wb->send('Lista_modificaciones_usuarios.xls');
		$ws1->setColumn( 1, 1, 25.00);
		$ws1->setColumn( 2, 2, 25.00);
  	$ws1->setColumn( 3, 3, 25.00);
		$ws1->setColumn( 4, 4, 20.00);
		$ws1->setColumn( 5, 5, 20.00);
		$ws1->setColumn( 6, 6, 20.00);
		$ws1->setColumn( 7, 7, 50.00);
		$ws1->write(0, 0, 'Lista de modificaciones de Usuarios', $encabezado);
		$ws1->mergeCells (0, 0, 0, 8);
		$info_usr1 = str_replace('<br>',' - ',$PdfLinea1);
		$ws1->write(2, 0, utf8_decode($info_usr1), $encabezado);
		$ws1->mergeCells (2, 0, 2, 8);
		$info_usr = str_replace('<br>',' - ',$PdfLinea2);
		$ws1->write(3, 0, utf8_decode($info_usr), $encabezado);
		$ws1->mergeCells (3, 0, 3, 8);
		$i=0;
		$fila_inicial = 7;
		if( ( method_exists('Conf','GetConf') && strtolower(Conf::GetConf($sesion,'NombreIdentificador'))=='rut' ) || ( method_exists('Conf','NombreIdentificador') && strtolower(Conf::NombreIdentificador())=='rut' ) )
			$glosa_rut = 'Rut';
		else
			$glosa_rut = 'CNI';
		
		$ws1->write($fila_inicial, 1, __('Usuario'), $tit);
		$ws1->write($fila_inicial, 2, __('Fecha creación'), $tit);
		$ws1->write($fila_inicial, 3, __('Fecha modificación'), $tit);
	  $ws1->write($fila_inicial, 4, __('Dato modificado'), $tit);
	  $ws1->write($fila_inicial, 5, __('Valor actual'), $tit);
	  $ws1->write($fila_inicial, 6, __('Valor anterior'), $tit);
	  $ws1->write($fila_inicial, 7, __('Modificado por'), $tit);
	  $fila_inicial++;
		$query = "SELECT u.id_usuario, CONCAT_WS(' ',u.nombre, u.apellido1, u.apellido2) AS nombre, u.rut, u.dv_rut,u.fecha_creacion,
										UV.fecha, UV.id_usuario_creador, UV.nombre_dato, UV.valor_original, UV.valor_actual
										FROM usuario AS u
										JOIN usuario_cambio_historial AS UV on u.id_usuario = UV.id_usuario
										WHERE $where AND UV.nombre_dato IN ('id_categoria_usuario','activo') ORDER BY u.id_usuario, UV.fecha DESC";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);	
	  while($row = mysql_fetch_assoc($resp))
	  {
			$i=0;
			if( trim($row['nombre_dato']) == 'id_categoria_usuario' )
			{
				$row['nombre_dato'] = 'categoría';
				$glosa_actual = (!empty($row['valor_actual'])) ? Utiles::Glosa($sesion, $row['valor_actual'], 'glosa_categoria', 'prm_categoria_usuario','id_categoria_usuario') : 'sin asignación';
				$glosa_origen = (!empty($row['valor_original'])) ? Utiles::Glosa($sesion, $row['valor_original'], 'glosa_categoria', 'prm_categoria_usuario','id_categoria_usuario') : 'sin asignación';
			}
			else
			{
				$glosa_actual = (!empty($row['valor_actual'])) ? 'activo' : 'inactivo';
				$glosa_origen = (!empty($row['valor_original'])) ? 'activo' : 'inactivo';
			}
			
			$ws1->write($fila_inicial, 1, $row[nombre], $f4);
			$ws1->write($fila_inicial, 2, Utiles::sql2date($row[fecha_creacion]), $f4);
	    $ws1->write($fila_inicial, 3, Utiles::sql2date($row[fecha]), $f4);
	    $ws1->write($fila_inicial, 4, $row[nombre_dato], $f4);
	    $ws1->write($fila_inicial, 5, $glosa_actual, $f4);
	    $ws1->write($fila_inicial, 6, $glosa_origen, $f4);
	    $ws1->write($fila_inicial, 7, Utiles::Glosa($sesion, $row['id_usuario_creador'], "CONCAT_WS(' ',nombre,apellido1,apellido2) AS glosa", 'usuario','id_usuario'), $f4);
	    $fila_inicial++;
		}
	}
	else
	{
	  $wb->send('Lista_Usuarios.xls');

	  $f4->setNumFormat("0");
	  $time_format =& $wb->addFormat(array('Size' => 10,
	                              'VAlign' => 'top',
	                              'Align' => 'justify',
	                              'Border' => 1,
	                              'Color' => 'black'));
	  $time_format->setNumFormat('[h]:mm');
		$total =& $wb->addFormat(array('Size' => 10,
	                              'Align' => 'right',
	                              'Bold' => '1',
	                              'FgColor' => '36',
	                              'Border' => 1,
	                              'Color' => 'black'));
	  $total->setNumFormat("0");
		$i=0;
		$col++;
		$col_nombre = $col++;
		$col_categoria = $col++;
		$col_email = $col++;
		$col_rut = $col++;
		$col_dias_ingreso_trabajo = $col++;
		$col_area = $col++;
		$col_activo = $col++;
		$col_revisa = $col++;
		$col_retraso_maximo = $col++;
		$col_restriccion_minima = $col++;
		$col_restriccion_maxima = $col++;
		$col_restriccion_mensual = $col++;
	 
		// se setea el ancho de las columnas
		$ws1->setColumn( $col_nombre, $col_nombre,  25.00);
		$ws1->setColumn( $col_categoria, $col_categoria,  25.00);
		$ws1->setColumn( $col_email, $col_email,  30.00);
		$ws1->setColumn( $col_rut, $col_rut, 20.00);
		$ws1->setColumn( $col_dias_ingreso_trabajo, $col_dias_ingreso_trabajo,  22.00);
		$ws1->setColumn( $col_area, $col_area,  20.00);
		$ws1->setColumn( $col_activo, $col_activo,  15.00);
		$ws1->setColumn( $col_revisa, $col_revisa,  35.00);
		$ws1->setColumn( $col_retraso_maximo, $col_retraso_maximo,  20.00);
		$ws1->setColumn( $col_restriccion_minima, $col_restriccion_minima,  20.00);
		$ws1->setColumn( $col_restriccion_maxima, $col_restriccion_maxima,  19.80);
		$ws1->setColumn( $col_restriccion_mensual, $col_restriccion_mensual,  19.80);
	
		$ws1->write(0, 0, 'LISTADO DE Usuarios', $encabezado);
		$ws1->mergeCells (0, 0, 0, 8);
		$info_usr1 = str_replace('<br>',' - ',$PdfLinea1);
		$ws1->write(2, 0, utf8_decode($info_usr1), $encabezado);
		$ws1->mergeCells (2, 0, 2, 8);
		$info_usr = str_replace('<br>',' - ',$PdfLinea2);
		$ws1->write(3, 0, utf8_decode($info_usr), $encabezado);
		$ws1->mergeCells (3, 0, 3, 8);
		$i=0;
		$fila_inicial = 7;
		if( ( method_exists('Conf','GetConf') && strtolower(Conf::GetConf($sesion,'NombreIdentificador'))=='rut' ) || ( method_exists('Conf','NombreIdentificador') && strtolower(Conf::NombreIdentificador())=='rut' ) )
			$glosa_rut = 'Rut';
		else
			$glosa_rut = 'CNI';
		
		$ws1->write($fila_inicial, $col_nombre, __('Nombre'), $tit);
	  $ws1->write($fila_inicial, $col_categoria, __('Categoria'), $tit);
	  $ws1->write($fila_inicial, $col_email, __('Email'), $tit);
	  $ws1->write($fila_inicial, $col_rut, $glosa_rut, $tit);
	  $ws1->write($fila_inicial, $col_dias_ingreso_trabajo, __('Dias ingreso trabajo'), $tit);
	  $ws1->write($fila_inicial, $col_area, __('Area'), $tit);
	  $ws1->write($fila_inicial, $col_activo, __('Activo'), $tit);
	  $ws1->write($fila_inicial, $col_revisa, __('Revisa'), $tit);
	  $ws1->write($fila_inicial, $col_retraso_maximo, __('Retraso maxima'), $tit);
	  $ws1->write($fila_inicial, $col_restriccion_minima, __('Restriccion minima semanal'), $tit);
	  $ws1->write($fila_inicial, $col_restriccion_maxima, __('Restriccion maxima semanal'), $tit);
	  $ws1->write($fila_inicial, $col_restriccion_mensual, __('Restriccion mensual'), $tit);
	  $fila_inicial++;
	  
	  ###################################### SQL ######################################
		$query = "SELECT u.id_usuario,CONCAT_WS(' ',u.nombre, u.apellido1, u.apellido2) AS nombre,
										cu.glosa_categoria, u.email, u.restriccion_min, u.restriccion_max,
										u.retraso_max, u.restriccion_mensual, u.dias_ingreso_trabajo,
										au.glosa, u.activo, u.rut, u.dv_rut 
										FROM usuario AS u
										LEFT JOIN prm_categoria_usuario AS cu on u.id_categoria_usuario=cu.id_categoria_usuario
										LEFT JOIN prm_area_usuario AS au on u.id_area_usuario=au.id
										WHERE $where ORDER BY nombre";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	  while($row = mysql_fetch_array($resp))
	  {
	  	$i=0;
			$ws1->write($fila_inicial, $col_nombre, $row[nombre], $f4);
	    $ws1->write($fila_inicial, $col_categoria, $row[glosa_categoria], $f4);
	    $ws1->write($fila_inicial, $col_email, $row[email], $f4);
	    $ws1->write($fila_inicial, $col_rut, $row[rut].($row[dv_rut]?'-'.$row[dv_rut]:''), $f4);
	    $ws1->write($fila_inicial, $col_dias_ingreso_trabajo, $row[dias_ingreso_trabajo], $f4);
	    $ws1->write($fila_inicial, $col_area, $row[glosa], $f4);
	    if($row[activo]==0)
	    {$ws1->write($fila_inicial, $col_activo, 'no', $f4);}
	    if($row[activo]==1)
	    {$ws1->write($fila_inicial, $col_activo, 'si', $f4);}
	    $query_revisor="SELECT CONCAT_WS(' ',u.nombre,u.apellido1,u.apellido2) as nombre
	    								FROM usuario_revisor AS ur
	    								JOIN usuario AS u ON ur.id_revisado=u.id_usuario
	    								WHERE ur.id_revisor=".$row[id_usuario];
	    $resp_revisor = mysql_query($query_revisor, $sesion->dbh) or Utiles::errorSQL($query_revisor,__FILE__,__LINE__,$sesion->dbh);
			$revisa="";
			while($row_revisor = mysql_fetch_array($resp_revisor))
			{
				$revisa .= $row_revisor[nombre]."\n";
			}
			$ws1->write($fila_inicial, $col_revisa, $revisa, $f4);
			$ws1->write($fila_inicial, $col_retraso_maximo, $row[retraso_max], $f4);
	    $ws1->write($fila_inicial, $col_restriccion_minima, $row[restriccion_min], $f4);
	    $ws1->write($fila_inicial, $col_restriccion_maxima, $row[restriccion_max], $f4);
	    $ws1->write($fila_inicial, $col_restriccion_mensual, $row[restriccion_mensual], $f4);
			$fila_inicial++;
		}
	}

  $wb->close();
  exit;
?>