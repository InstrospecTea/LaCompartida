<?php
	ini_set('max_execution_time', 300);
	
	require_once dirname(__FILE__).'/../conf.php';
    require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
    require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
    require_once Conf::ServerDir().'/../fw/classes/Html.php';
    require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
    require_once Conf::ServerDir().'/../app/classes/Debug.php';
    require_once Conf::ServerDir().'/../app/classes/Cliente.php';
    require_once Conf::ServerDir().'/../app/classes/InputId.php';
    require_once Conf::ServerDir().'/../app/classes/UtilesApp.php';
    require_once Conf::ServerDir().'/classes/Funciones.php';
    require_once 'Spreadsheet/Excel/Writer.php';

    $sesion = new Sesion( array('REV','ADM') );
	
    $wb = new Spreadsheet_Excel_Writer();

    $wb->send('Planilla_Asuntos_Factura.xls');

    $wb->setCustomColor ( 35, 0, 255, 255 );
    $wb->setCustomColor ( 36, 255, 255, 0 );
    $wb->setCustomColor ( 37, 255, 255, 160 );

    $encabezado =& $wb->addFormat(array('Size' => 12,
                                'VAlign' => 'top',
                                'Align' => 'justify',
                                'Bold' => '1',
                                'Color' => 'black'));
  
    $tit1 =& $wb->addFormat(array('Size' => 12,
                                'VAlign' => 'top',
                                'Align' => 'justify',
                                'Bold' => '1',
                                'Locked' => 1,
                                'Border' => 1,
                                'FgColor' => '35',
                                'Color' => 'black'));

    $tit2 =& $wb->addFormat(array('Size' => 10,
                                'Align' => 'justify',
                                'Bold' => '1',
                                'FgColor' => '36',
                                'Border' => 1,
                                'Locked' => 1,
                                'Color' => 'red'));

    $f4 =& $wb->addFormat(array('Size' => 10,
                                'VAlign' => 'top',
                                'Align' => 'justify',
                                'Border' => 1,
                                'Color' => 'black'));
    $f4->setNumFormat("0");

	$f5 =& $wb->addFormat(array('Size' => 10,
                                'VAlign' => 'top',
                                'Align' => 'justify',
                                'Border' => 1,
                                'FgColor' => '37',
                                'Color' => 'black'));
    $f5->setNumFormat("0");

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


   $ws1 =& $wb->addWorksheet(__('Asuntos'));
   $ws1->setInputEncoding('utf-8');
   $ws1->fitToPages(1,0);
   $ws1->setZoom(75);
   #$ws1->protect( $key );
   $col=0;
   
   $headers1 = array( 
		'Grupo',
		'Código',
		'Nombre cliente',
		'Encargado Comercial',
		'Encargado Secundario',
		'Rut',
		'Dirección'
   );
   $headers2 = array( 
		'Asunto',
		'Código',
		'Datos de Facturación',
		'Rut',
		'Giro',
		'Dirección',
		'tarifa'
   );
   
	$mostrar_encargado_secundario = UtilesApp::GetConf($sesion, 'EncargadoSecundario');
	$mostrar_codigo_secundario = UtilesApp::GetConf($sesion,'CodigoSecundario');

	$col=0;
	// se setea el ancho de las columnas
	$ws1->setColumn( $col, $col++,  15.00);
	$ws1->setColumn( $col, $col++,  15.00);
	$ws1->setColumn( $col, $col++,  35.00);
	$ws1->setColumn( $col, $col++,  18.00);
	if($mostrar_encargado_secundario)
		$ws1->setColumn( $col, $col++,  18.00);
	else unset($headers1[4]);
	$ws1->setColumn( $col, $col++,  16.00);
	$ws1->setColumn( $col, $col++,  30.00);
	$ws1->setColumn( $col, $col++,  22.80);
	$ws1->setColumn( $col, $col++,  17.50);
	$ws1->setColumn( $col, $col++,  30.00);
	$ws1->setColumn( $col, $col++,  20.00);
	$ws1->setColumn( $col, $col++,  18.00);
	$ws1->setColumn( $col, $col++,  22.00);
	$ws1->setColumn( $col, $col++,  27.00);
	$ws1->setColumn( $col, $col++,  18.00);

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

	$ws1->write(0, 0, 'LISTADO DE ASUNTOS', $encabezado);
	$ws1->mergeCells (0, 0, 0, 8);
	$info_usr1 = str_replace('<br>',' - ',$PdfLinea1);
	$ws1->write(2, 0, utf8_decode($info_usr1), $encabezado);
	$ws1->mergeCells (2, 0, 2, 8);
	$info_usr = str_replace('<br>',' - ',$PdfLinea2);
	$ws1->write(3, 0, utf8_decode($info_usr), $encabezado);
	$ws1->mergeCells (3, 0, 3, 8);
	$i=0;
	$fila_inicial = 6;

	foreach($headers1 as $i => $h)
		$ws1->write($fila_inicial, $i, __($h), $tit1);
	foreach($headers2 as $j => $h)
		$ws1->write($fila_inicial, $i+1+$j, __($h), $tit2);
	
    $fila_inicial++;

    ###################################### SQL ######################################
    $where = 1;

    if($activo)
	{
		if($activo== 'SI')
			$activo = 1;
		else $activo = 0;
	$where .= " AND a1.activo = $activo ";
	}

	if($codigo_asunto != "")
		$where .= " AND a1.codigo_asunto Like '$codigo_asunto%'";

	if($glosa_asunto != "")
	{
		$nombre = strtr($glosa_asunto, ' ', '%' );
		$where .= " AND a1.glosa_asunto Like '%$glosa_asunto%'";
	}

	if($codigo_cliente || $codigo_cliente_secundario)
	{
		if ($mostrar_codigo_secundario)
		{
			$where .= " AND cliente.codigo_cliente_secundario = '$codigo_cliente_secundario'";
			$cliente = new Cliente($sesion);
			if($cliente->LoadByCodigoSecundario($codigo_cliente_secundario))
				$codigo_cliente=$cliente->fields['codigo_cliente'];
		}
		else
		{
			$where .= " AND cliente.codigo_cliente = '$codigo_cliente'";
		}
	}

	if($opc == "entregar_asunto")
		$where .= " AND a1.codigo_cliente = '$codigo_cliente' ";

	if($fecha1 || $fecha2)
		$where .= " AND a1.fecha_creacion BETWEEN '".Utiles::fecha2sql($fecha1)."' AND '".Utiles::fecha2sql($fecha2)." 23:59:59'";

	if($motivo == "cobros")
		$where .= " AND a1.activo='1' AND a1.cobrable = '1'";

	if($id_usuario)
		$where .= " AND a1.id_encargado = '$id_usuario' ";

	if($id_area_proyecto)
		$where .= " AND a1.id_area_proyecto = '$id_area_proyecto' ";
		
    $query = "SELECT SQL_CALC_FOUND_ROWS *, 
						grupo_cliente.glosa_grupo_cliente,
						cliente.glosa_cliente,
						cliente.codigo_cliente,
						a1.codigo_asunto,
						cliente.codigo_cliente_secundario,
						a1.codigo_asunto_secundario,
						
						contrato_cliente.rut AS rut_cliente,
						IFNULL(contrato.rut, contrato_cliente.rut) AS rut,
						contrato_cliente.factura_direccion AS direccion_cliente,
						IFNULL(contrato.factura_direccion, contrato_cliente.factura_direccion) AS direccion,
						IFNULL(contrato.factura_razon_social, contrato_cliente.factura_razon_social) AS razon_social,
						IFNULL(contrato.factura_giro, contrato_cliente.factura_giro) AS giro,
						tarifa.glosa_tarifa,
						a1.id_moneda, 
						a1.activo,
						a1.fecha_creacion,
						usuario.username as username,
						usuario.apellido1 as apellido1,
						usuario.nombre as nombre,
						usuario_secundario.username as username_secundario,
						usuario_secundario.apellido1 as apellido1_secundario,
						usuario_secundario.nombre as nombre_secundario
                    FROM asunto AS a1
                    LEFT JOIN cliente ON cliente.codigo_cliente=a1.codigo_cliente
					LEFT JOIN grupo_cliente ON cliente.id_grupo_cliente = grupo_cliente.id_grupo_cliente
					LEFT JOIN contrato AS contrato_cliente ON contrato_cliente.id_contrato = cliente.id_contrato
                    LEFT JOIN contrato ON contrato.id_contrato = a1.id_contrato
                    LEFT JOIN tarifa ON contrato.id_tarifa=tarifa.id_tarifa
                    LEFT JOIN usuario ON a1.id_encargado = usuario.id_usuario
					LEFT JOIN usuario as usuario_secundario ON contrato.id_usuario_secundario = usuario_secundario.id_usuario
                    WHERE $where
                    ORDER BY
                    grupo_cliente.glosa_grupo_cliente, cliente.codigo_cliente, a1.codigo_asunto, a1.codigo_cliente ASC ";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);

		while($row = mysql_fetch_array($resp))
		{
			$col = 0;
			$ws1->write($fila_inicial, $col++, $row['glosa_grupo_cliente'], $f4);

			if($mostrar_codigo_secundario)
			{
				$ws1->write($fila_inicial, $col++, $row['codigo_cliente_secundario'], $f4);
			}
			else
			{
				$ws1->write($fila_inicial, $col++,"'".$row['codigo_cliente'], $f4);
			}

            $ws1->write($fila_inicial, $col++, $row['glosa_cliente'], $f4);


			if(UtilesApp::GetConf($sesion,'UsaUsernameEnTodoElSistema') ){
	            $ws1->write($fila_inicial, $col++, $row['username'], $f4);
				if($mostrar_encargado_secundario)
					$ws1->write($fila_inicial, $col++, $row['username_secundario'], $f4);
			}
	        else{
	          	$ws1->write($fila_inicial, $col++, $row['apellido1'].', '.$row['nombre'], $f4);
				if($mostrar_encargado_secundario)
					$ws1->write($fila_inicial, $col++,
						empty($row['username_secundario']) ? '' : $row['apellido1_secundario'].', '.$row['nombre_secundario'], $f4);
			}

			
			$ws1->write($fila_inicial, $col++, $row['rut_cliente'], $f4);
			$ws1->write($fila_inicial, $col++, $row['direccion_cliente'], $f4);

			
            $ws1->write($fila_inicial, $col++, $row['glosa_asunto'], $f5);

            if($mostrar_codigo_secundario)
			{
				$ws1->write($fila_inicial, $col++, $row['codigo_asunto'], $f5);
			}
			else
			{
				$ws1->write($fila_inicial, $col++, $row['codigo_asunto_secundario'], $f5);
			}


			$ws1->write($fila_inicial, $col++, $row['razon_social'], $f5);
			$ws1->write($fila_inicial, $col++, $row['rut'], $f5);
			$ws1->write($fila_inicial, $col++, $row['giro'], $f5);
			$ws1->write($fila_inicial, $col++, $row['direccion'], $f5);

            $ws1->write($fila_inicial, $col++, $row['glosa_tarifa'], $f5);
			$fila_inicial++;
		}

    $wb->close();
    exit;
?>
