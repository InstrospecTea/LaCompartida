<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../app/classes/Cliente.php';
	require_once Conf::ServerDir().'/../app/classes/InputId.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/Funciones.php';
	require_once 'Spreadsheet/Excel/Writer.php';

	$sesion = new Sesion( array('REV','ADM') );

	$pagina = new Pagina( $sesion );

	#$key = substr(md5(microtime().posix_getpid()), 0, 8);

	$wb = new Spreadsheet_Excel_Writer();

	$wb->send('Planilla_Clientes.xls');

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
	$f4->setNumFormat("0000");
	
		$formatos_moneda = array();
		$query = 'SELECT id_moneda, simbolo, cifras_decimales 
				FROM prm_moneda
				ORDER BY id_moneda';
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		while(list($id_moneda, $simbolo_moneda, $cifras_decimales) = mysql_fetch_array($resp)){
			if($cifras_decimales>0)
			{
				$decimales = '.';
				while($cifras_decimales-- >0)
					$decimales .= '0';
			}
			else
				$decimales = '';
			$formatos_moneda[$id_moneda] =& $wb->addFormat(array('Size' => 11,
																'VAlign' => 'top',
																'Align' => 'justify',
																'Border' => '1',
																'Color' => 'black',
																'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"));
		}
		
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


	$ws1 =& $wb->addWorksheet(__('Reportes'));
	$ws1->setInputEncoding('utf-8');
	$ws1->fitToPages(1,0);
	$ws1->setZoom(75);
	#$ws1->protect( $key );

$col=0;
	$col_codigo = $col++;
	$col_nombre = $col++;
	$col_grupo = $col++;
	$col_encargado = $col++;
	$col_codigo_secundario = $col++;
	$col_rut =$col++;
	$col_rsocial = $col++;
	$col_tarifa = $col++;
	$col_moneda = $col++;
	$col_forma_cobro = $col++;
	$col_monto = $col++;
	$col_direccion = $col++;
	$col_telefono = $col++;
	$col_contacto = $col++;
	$col_fono_contacto = $col++;
	$col_mail_contacto = $col++;
	$col_dir_contacto = $col++;
	
	// se setea el ancho de las columnas
	$columna=0;
	$ws1->setColumn( $col_codigo, $col_codigo,  8.00);
	$ws1->setColumn( $col_nombre, $col_nombre,  45.00);
	$ws1->setColumn( $col_grupo, $col_grupo,  20.00);
	$ws1->setColumn( $col_encargado, $col_encargado,  25.00);
	$ws1->setColumn( $col_codigo_secundario, $col_codigo_secundario,  16.00);
	$ws1->setColumn( $col_rut, $col_rut,  16.00);
	$ws1->setColumn( $col_rsocial, $col_rsocial,  45.00);
	$ws1->setColumn( $col_tarifa, $col_tarifa,  30.00);
	$ws1->setColumn( $col_moneda, $col_moneda,  20.00);
	$ws1->setColumn( $col_forma_cobro, $col_forma_cobro,  20.00);
	$ws1->setColumn( $col_monto, $col_monto,  20.00);
	$ws1->setColumn( $col_direccion, $col_direccion,  40.00);
	$ws1->setColumn( $col_telefono, $col_telefono,  20.00);
	$ws1->setColumn( $col_contacto, $col_contacto,  45.00);
	$ws1->setColumn( $col_fono_contacto, $col_fono_contacto,  20.00);
	$ws1->setColumn( $col_mail_contacto, $col_mail_contacto,  30.00);
	$ws1->setColumn( $col_dir_contacto, $col_dir_contacto, 40.00);

	$ws1->write(0, 0, 'LISTADO DE CLIENTES', $encabezado);
			$ws1->mergeCells (0, 0, 0, 8);
			
	/* Filtro si es grupo */        
	if($id_grupo_cliente > 0)
	{
		$ws1->write(2, 0, __('Grupo').': '.Utiles::Glosa( $sesion, $id_grupo_cliente, 'glosa_grupo_cliente', 'grupo_cliente', 'id_grupo_cliente'), $encabezado);
		$ws1->mergeCells (2, 0, 2, 8);
	}


	$fila_inicial = 3;
	$columna = 0;
	$ws1->write($fila_inicial, $col_codigo, __('C�digo'), $tit);
	$ws1->write($fila_inicial, $col_nombre, __('Nombre'), $tit);
	$ws1->write($fila_inicial, $col_grupo, __('Grupo'), $tit);
	$ws1->write($fila_inicial, $col_encargado, __('Encargado Comercial'), $tit);
	$ws1->write($fila_inicial, $col_codigo_secundario, __('C�digo Secundario'), $tit);
	$ws1->write($fila_inicial, $col_rut, __('Rut'), $tit);
	$ws1->write($fila_inicial, $col_rsocial, __('Raz�n Social'), $tit);
	$ws1->write($fila_inicial, $col_tarifa, __('Tarifa'), $tit);
	$ws1->write($fila_inicial, $col_moneda, __('Moneda'), $tit);
	$ws1->write($fila_inicial, $col_forma_cobro, __('Forma Cobro'), $tit);
	$ws1->write($fila_inicial, $col_monto, __('Monto(FF/R/C)'), $tit);
	$ws1->write($fila_inicial, $col_direccion, __('Direcci�n'), $tit);
	$ws1->write($fila_inicial, $col_telefono, __('Tel�fono'), $tit);    
	$ws1->write($fila_inicial, $col_contacto, __('Nombre Contacto'), $tit);
	$ws1->write($fila_inicial, $col_fono_contacto, __('Tel�fono Contacto'), $tit);
	$ws1->write($fila_inicial, $col_mail_contacto, __('E-mail Contacto'), $tit);
	$ws1->write($fila_inicial, $col_dir_contacto, __('Direcci�n contacto'), $tit);
	$fila_inicial++;
	
	$where = '1';
	if($glosa_cliente != '')
	{
		$nombre = strtr($glosa_cliente, ' ', '%' );
		$where .= " AND cliente.glosa_cliente Like '%$nombre%'";
	}
	if( $codigo != '')
		$where .= " AND codigo_cliente = '$codigo'";
	if( $id_grupo_cliente > 0 )
		$where .= " AND cliente.id_grupo_cliente = ".$id_grupo_cliente."";
	if(!empty($fecha1)){
			$where .= " AND cliente.fecha_creacion >= '".Utiles::fecha2sql($fecha1)."' ";
		}
		if(!empty($fecha2)){
			$where .= " AND cliente.fecha_creacion <= '".Utiles::fecha2sql($fecha2)."' ";
		}
	if($solo_activos == 1)
		$where .= " AND cliente.activo = 1 ";

	$query = "SELECT SQL_CALC_FOUND_ROWS cliente.codigo_cliente,
								cliente.codigo_cliente_secundario, 
								cliente.glosa_cliente, 
								grupo_cliente.glosa_grupo_cliente, 
								moneda.glosa_moneda,
								CONCAT(usuario.nombre,' ',usuario.apellido1) as usuario_nombre, 
								usuario.username,
								contrato.factura_razon_social, 
								CONCAT(contrato.cod_factura_telefono,' ',contrato.factura_telefono) as telefono,
								contrato.factura_direccion, 
								contrato.rut, 
								CONCAT_WS(' ',contrato.contacto,contrato.apellido_contacto) as contacto, 
								contrato.fono_contacto, 
								contrato.email_contacto, 
								contrato.direccion_contacto,
								contrato.forma_cobro,
								contrato.monto,
								tarifa.glosa_tarifa,
								contrato.id_moneda_monto 
						FROM cliente 
						LEFT JOIN grupo_cliente USING (id_grupo_cliente)
						LEFT JOIN contrato ON cliente.id_contrato = contrato.id_contrato
						LEFT JOIN prm_moneda AS moneda ON contrato.id_moneda = moneda.id_moneda 
						LEFT JOIN usuario ON contrato.id_usuario_responsable = usuario.id_usuario 
						LEFT JOIN tarifa ON contrato.id_tarifa=tarifa.id_tarifa
						WHERE $where ORDER BY cliente.glosa_cliente ASC";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	while($row = mysql_fetch_array($resp))
	{
		if ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') )
			$ws1->write($fila_inicial, $col_codigo, $row['codigo_cliente_secundario'], $f4);
		else
			$ws1->write($fila_inicial, $col_codigo, $row['codigo_cliente'], $f4);
		$ws1->write($fila_inicial, $col_nombre, $row['glosa_cliente'], $f4);
		$ws1->write($fila_inicial, $col_grupo, $row['glosa_grupo_cliente'], $f4);
		if( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaUsernameEnTodoElSistema') )
			$ws1->write($fila_inicial, $col_encargado, $row['username'], $f4);
		else
			$ws1->write($fila_inicial, $col_encargado, $row['usuario_nombre'], $f4);
		if( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') )
			$ws1->write($fila_inicial, $col_codigo_secundario, $row['codigo_cliente'], $f4);
		else
			$ws1->write($fila_inicial, $col_codigo_secundario, $row['codigo_cliente_secundario'], $f4);
		$ws1->write($fila_inicial, $col_rut, $row['rut'], $f4);            
		$ws1->write($fila_inicial, $col_rsocial, $row['factura_razon_social'], $f4);
		$ws1->write($fila_inicial, $col_tarifa, $row['glosa_tarifa'], $f4);
		$ws1->write($fila_inicial, $col_moneda, $row['glosa_moneda'], $f4);
		$ws1->write($fila_inicial, $col_forma_cobro, $row['forma_cobro'], $f4);
		$ws1->write($fila_inicial, $col_monto, $row['monto'], $formatos_moneda[$row['id_moneda_monto']]);
		$ws1->write($fila_inicial, $col_direccion, $row['factura_direccion'], $f4);
		$ws1->write($fila_inicial, $col_telefono, $row['telefono'], $f4);
		$ws1->write($fila_inicial, $col_contacto, $row['contacto'], $f4);
		$ws1->write($fila_inicial, $col_fono_contacto, $row['fono_contacto'], $f4);
		$ws1->write($fila_inicial, $col_mail_contacto, $row['email_contacto'], $f4);
		$ws1->write($fila_inicial, $col_dir_contacto, $row['direccion_contacto'], $f4);
		$fila_inicial++;
	}

	$wb->close();
	exit;
?>
