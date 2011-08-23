<?
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
	
   set_time_limit(150);

    $pagina = new Pagina( $sesion );

    #$key = substr(md5(microtime().posix_getpid()), 0, 8);

    $wb = new Spreadsheet_Excel_Writer();

    $wb->send('Planilla_Asuntos.xls');

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

	$mostrar_encargado_secundario = UtilesApp::GetConf($sesion, 'EncargadoSecundario');
	$mostrar_codigo_secundario = UtilesApp::GetConf($sesion,'CodigoSecundario');

   $ws1 =& $wb->addWorksheet(__('Asuntos'));
   $ws1->setInputEncoding('utf-8');
   $ws1->fitToPages(1,0);
   $ws1->setZoom(75);
   #$ws1->protect( $key );
   $col=0;
   
   $col_codigo = $col++;
   $col_titulo = $col++;
   $col_glosa_cliente = $col++;
   $col_codigo_secundario = $col++;
   $col_descripcion = $col++;
   $col_horas_trabajadas = $col++;
   $col_horas_a_cobrar = $col++;
   $col_encargado = $col++;
	if($mostrar_encargado_secundario)
		$col_encargado_secundario = $col++;
   $col_tarifa = $col++;
   $col_moneda = $col++;
   $col_forma_cobro = $col++;
   $col_monto_asunto = $col++;
   $col_tipo_proyecto = $col++;
   $col_area_proyecto = $col++;
   $col_fecha_creacion = $col++;
   $col_nombre_contacto = $col++;
   $col_fono_contacto = $col++;
   $col_mail_contacto = $col++;
   $col_dir_contacto = $col++;
   $col_idioma = $col++;
   $col_cobrable = $col++;
   $col_act_oblicatorio = $col++;
   
    // se setea el ancho de las columnas
    $ws1->setColumn( $col_codigo, $col_codigo,  15.00);
	$ws1->setColumn( $col_titulo, $col_titulo,  45.00);
	$ws1->setColumn( $col_glosa_cliente, $col_glosa_cliente,  45.00);
	$ws1->setColumn( $col_codigo_secundario, $col_codigo_secundario,  15.00);
	$ws1->setColumn( $col_descripcion, $col_descripcion,  45.00);
	$ws1->setColumn( $col_horas_trabajadas, $col_horas_trabajadas,  19.80);
	$ws1->setColumn( $col_horas_a_cobrar, $col_horas_a_cobrar,  19.80);
	$ws1->setColumn( $col_encargado, $col_encargado,  28.50);
	if($mostrar_encargado_secundario)
		$ws1->setColumn( $col_encargado_secundario, $col_encargado_secundario,  28.50);
	$ws1->setColumn( $col_tarifa, $col_tarifa,  30.00);
	$ws1->setColumn( $col_moneda, $col_moneda,  20.00);
	$ws1->setColumn( $col_forma_cobro, $col_forma_cobro,  20.00);
	$ws1->setColumn( $col_monto_asunto, $col_monto_asunto,  20.00);
	$ws1->setColumn( $col_tipo_proyecto, $col_tipo_proyecto,  20.00);
	$ws1->setColumn( $col_area_proyecto, $col_area_proyecto,  20.00);
	$ws1->setColumn( $col_fecha_creacion, $col_fecha_creacion,  20.00);
	$ws1->setColumn( $col_nombre_contacto, $col_nombre_contacto,  30.00);
	$ws1->setColumn( $col_fono_contacto, $col_fono_contacto,  30.00);
	$ws1->setColumn( $col_mail_contacto, $col_mail_contacto,  30.00);
	$ws1->setColumn( $col_dir_contacto, $col_dir_contacto, 30.00);
	$ws1->setColumn( $col_idioma, $col_idioma, 20.00);
	$ws1->setColumn( $col_cobrable, $col_cobrable, 10.00);$i++;
	$ws1->setColumn( $col_act_oblicatorio, $col_act_oblicatorio, 20.00);

	$PdfLinea1 = UtilesApp::GetConf($sesion, 'PdfLinea1');
	$PdfLinea2 = UtilesApp::GetConf($sesion, 'PdfLinea2');

	$ws1->write(0, 0, 'LISTADO DE ASUNTOS', $encabezado);
	$ws1->mergeCells (0, 0, 0, 8);
	$info_usr1 = str_replace('<br>',' - ',$PdfLinea1);
	$ws1->write(2, 0, utf8_decode($info_usr1), $encabezado);
	$ws1->mergeCells (2, 0, 2, 8);
	$info_usr = str_replace('<br>',' - ',$PdfLinea2);
	$ws1->write(3, 0, utf8_decode($info_usr), $encabezado);
	$ws1->mergeCells (3, 0, 3, 8);
	$i=0;
	$fila_inicial = 7;
	$ws1->write($fila_inicial, $col_codigo, __('Código'), $tit);
    $ws1->write($fila_inicial, $col_titulo, __('Título'), $tit);
    $ws1->write($fila_inicial, $col_glosa_cliente, __('Cliente'), $tit);
    $ws1->write($fila_inicial, $col_codigo_secundario, __('Código Secundario'), $tit);
    $ws1->write($fila_inicial, $col_descripcion, __('Descripción'), $tit);
    $ws1->write($fila_inicial, $col_horas_trabajadas, __('Horas Trabajadas'), $tit);
    $ws1->write($fila_inicial, $col_horas_a_cobrar, __('Horas a cobrar'), $tit);
    $ws1->write($fila_inicial, $col_encargado, __('Encargado'), $tit);
	if($mostrar_encargado_secundario)
		$ws1->write($fila_inicial, $col_encargado_secundario, __('Encargado Secundario'), $tit);
    $ws1->write($fila_inicial, $col_tarifa, __('Tarifa'), $tit);
		$ws1->write($fila_inicial, $col_moneda, __('Moneda'), $tit);
		$ws1->write($fila_inicial, $col_forma_cobro, __('Forma Cobro'), $tit);
		$ws1->write($fila_inicial, $col_monto_asunto, __('Monto(FF/R/C)'), $tit);
    $ws1->write($fila_inicial, $col_tipo_proyecto, __('Tipo de Proyecto'), $tit);
    $ws1->write($fila_inicial, $col_area_proyecto, __('Area de Práctica'), $tit);
    $ws1->write($fila_inicial, $col_fecha_creacion, __('Fecha Creación'), $tit);
    $ws1->write($fila_inicial, $col_nombre_contacto, __('Nombre Contacto'), $tit);
    $ws1->write($fila_inicial, $col_fono_contacto, __('Teléfono Contacto'), $tit);
    $ws1->write($fila_inicial, $col_mail_contacto, __('E-mail Contacto'), $tit);
    $ws1->write($fila_inicial, $col_dir_contacto, __('Dirección Contacto'), $tit);
    $ws1->write($fila_inicial, $col_idioma, __('Idioma'), $tit);
		$ws1->write($fila_inicial, $col_cobrable, __('Cobrable'), $tit);
		$ws1->write($fila_inicial, $col_act_oblicatorio, __('Act. Obligatorias'), $tit);
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
			
		//Este query es mejorable, se podría sacar horas_no_cobradas y horas_trabajadas, pero ya no se podría ordenar por estos campos.
    $query = "SELECT SQL_CALC_FOUND_ROWS
					*,
			    							a1.codigo_asunto,
			    							a1.id_moneda, 
			    							a1.activo,
			            			a1.fecha_creacion, 
			            			(
			            				SELECT 
			            					SUM(TIME_TO_SEC(duracion_cobrada))/3600 
				            			FROM trabajo AS t2
													LEFT JOIN cobro on t2.id_cobro=cobro.id_cobro
													WHERE (cobro.estado IS NULL OR cobro.estado = 'CREADO' OR cobro.estado = 'EN REVISION')
													AND t2.codigo_asunto=a1.codigo_asunto
													AND t2.cobrable = 1
												) AS horas_no_cobradas,
												(
													SELECT 
														SUM(TIME_TO_SEC(duracion))/3600
			                    FROM trabajo AS t3
			                    WHERE
			                      t3.codigo_asunto=a1.codigo_asunto
			                    AND t3.cobrable = 1
			                  ) AS horas_trabajadas,
												ca.id_cobro AS id_cobro_asunto, 
												tarifa.glosa_tarifa,
												prm_tipo_proyecto.glosa_tipo_proyecto AS tipo_proyecto,
												prm_area_proyecto.glosa AS area_proyecto, 
												a1.codigo_asunto_secundario as codigo_secundario,
												contrato.monto,
												contrato.forma_cobro,
					prm_moneda.glosa_moneda,
					usuario.username as username,
					usuario.apellido1 as apellido1,
					usuario.nombre as nombre,
					usuario_secundario.username as username_secundario,
					usuario_secundario.apellido1 as apellido1_secundario,
					usuario_secundario.nombre as nombre_secundario
                    FROM asunto AS a1
                    LEFT JOIN cliente ON cliente.codigo_cliente=a1.codigo_cliente
                    LEFT JOIN contrato ON contrato.id_contrato = a1.id_contrato
                    LEFT JOIN tarifa ON contrato.id_tarifa=tarifa.id_tarifa
                    LEFT JOIN cobro_asunto AS ca ON (ca.codigo_asunto=a1.codigo_asunto AND ca.id_cobro='$id_cobro')
                    LEFT JOIN prm_idioma ON a1.id_idioma = prm_idioma.id_idioma
                    LEFT JOIN prm_tipo_proyecto ON a1.id_tipo_asunto=prm_tipo_proyecto.id_tipo_proyecto
                    LEFT JOIN prm_area_proyecto ON a1.id_area_proyecto=prm_area_proyecto.id_area_proyecto
                    LEFT JOIN prm_moneda ON contrato.id_moneda=prm_moneda.id_moneda
                    LEFT JOIN usuario ON a1.id_encargado = usuario.id_usuario
				LEFT JOIN usuario as usuario_secundario ON contrato.id_usuario_secundario = usuario_secundario.id_usuario
                    WHERE $where
                    GROUP BY a1.codigo_asunto ORDER BY
                    a1.codigo_asunto, a1.codigo_cliente ASC";

		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	  while($row = mysql_fetch_array($resp))
    {
						if($mostrar_codigo_secundario)
						{
							$ws1->write($fila_inicial, $col_codigo, $row['codigo_secundario'], $f4);
						}
						else
						{
							$ws1->write($fila_inicial, $col_codigo, $row['codigo_asunto'], $f4);
						}
            $ws1->write($fila_inicial, $col_titulo, $row['glosa_asunto'], $f4);
            $ws1->write($fila_inicial, $col_glosa_cliente, $row['glosa_cliente'], $f4);
            if($mostrar_codigo_secundario)
						{
							$ws1->write($fila_inicial, $col_codigo_secundario, $row['codigo_asunto'], $f4);
						}
						else
						{
							$ws1->write($fila_inicial, $col_codigo_secundario, $row['codigo_secundario'], $f4);
						}
            $ws1->write($fila_inicial, $col_descripcion, $row['descripcion_asunto'], $f4);
            $ws1->write($fila_inicial, $col_horas_trabajadas, $row['horas_trabajadas'], $f4);
            $ws1->write($fila_inicial, $col_horas_a_cobrar, $row['horas_no_cobradas'], $f4);
            if(UtilesApp::GetConf($sesion,'UsaUsernameEnTodoElSistema') ){
	            $ws1->write($fila_inicial, $col_encargado, $row['username'], $f4);
				if($mostrar_encargado_secundario)
					$ws1->write($fila_inicial, $col_encargado_secundario, $row['username_secundario'], $f4);
			}
			else{
	          	$ws1->write($fila_inicial, $col_encargado, $row['apellido1'].', '.$row['nombre'], $f4);
				if($mostrar_encargado_secundario)
					$ws1->write($fila_inicial, $col_encargado_secundario,
						empty($row['username_secundario']) ? '' : $row['apellido1_secundario'].', '.$row['nombre_secundario'], $f4);
			}
            $ws1->write($fila_inicial, $col_tarifa, $row['glosa_tarifa'], $f4);
						$ws1->write($fila_inicial, $col_moneda, $row['glosa_moneda'], $f4);
						$ws1->write($fila_inicial, $col_forma_cobro, $row['forma_cobro'], $f4);
						$ws1->write($fila_inicial, $col_monto_asunto, $row['monto'], $f4);
            $ws1->write($fila_inicial, $col_tipo_proyecto, $row['tipo_proyecto'], $f4);
            $ws1->write($fila_inicial, $col_area_proyecto, $row['area_proyecto'], $f4);
	   $formato_fecha = UtilesApp::ObtenerFormatoFecha($sesion);
	   $formato_fecha = str_replace( "/", "-", $formato_fecha);
            $ws1->write($fila_inicial, $col_fecha_creacion, Utiles::sql2date($row['fecha_creacion'], $formato_fecha, '-'), $f4);
            $ws1->write($fila_inicial, $col_nombre_contacto, $row['contacto'], $f4);
            $ws1->write($fila_inicial, $col_fono_contacto, $row['fono_contacto'], $f4);
						$ws1->write($fila_inicial, $col_mail_contacto, $row['email_contacto'], $f4);
						$ws1->write($fila_inicial, $col_dir_contacto, $row['direccion_contacto'], $f4);
						$ws1->write($fila_inicial, $col_idioma, $row['glosa_idioma'], $f4);
						$ws1->write($fila_inicial, $col_cobrable, $row['cobrable'] == 1 ? 'SI':'NO', $f4);
						$ws1->write($fila_inicial, $col_act_oblicatorio, $row['actividades_obligatorias'] == 1 ? 'SI':'NO', $f4);
						$fila_inicial++;
		}

    $wb->close();
    exit;
?>
