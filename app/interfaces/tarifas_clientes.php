<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../fw/classes/Lista.php';
	require_once Conf::ServerDir().'/../app/classes/InputId.php';
	require_once Conf::ServerDir().'/classes/Funciones.php';
	require_once Conf::ServerDir().'/../app/classes/Tarifa.php';
	require_once 'Spreadsheet/Excel/Writer.php';

    $sesion = new Sesion( array('REV','ADM') );

    $pagina = new Pagina( $sesion );

    #$key = substr(md5(microtime().posix_getpid()), 0, 8);

    $wb = new Spreadsheet_Excel_Writer();

    $wb->send('Tarifas_con_clientes.xls');

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

    $f4 =& $wb->addFormat(array('Size' => 11,
                                'VAlign' => 'top',
                                'Align' => 'right',
                                'Border' => 1,
                                'Color' => 'black'));
 $f5 =& $wb->addFormat(array('Size' => 11, 'VAlign' => 'top', 'Align' => 'left', 'Color' => 'black'	));
  $fm =& $wb->addFormat(array('Size' => 11, 'VAlign' => 'top','Color' => 'black','Align'=>'merge'));
    $f4->setNumFormat("0");


    $querytarifas = "SELECT distinct id_tarifa, glosa_tarifa FROM tarifa";
   // mail('ffigueroa@lemontech.cl','Querytarifa',$querytarifas);
    $resptarifas = mysql_query($querytarifas, $sesion->dbh) or Utiles::errorSQL($querytarifas,__FILE__,__LINE__,$sesion->dbh);
   $stack="";
    while($hojas=mysql_fetch_array( $resptarifas )):



	$id_tarifa=$hojas['id_tarifa'];
	$id_tarifa_mostrar = $hojas['id_tarifa'];
    $ws1 =& $wb->addWorksheet(__('Tarifa').' '.$id_tarifa.' '.substr($hojas['glosa_tarifa'],0,15));
  $stack.="\n".__('Tarifa').' '.$id_tarifa.' '.substr($hojas['glosa_tarifa'],0,20);

	$ws1->setInputEncoding('utf-8');
	$ws1->fitToPages(1,0);
	$ws1->setZoom(75);
	#$ws1->protect( $key );

	// se setea el ancho de las columnas
	$ws1->setColumn( 0, 0,  45.00);
	$ws1->setColumn( 1, 10, 15.00);


		$PdfLinea1 = UtilesApp::GetConf($sesion, 'PdfLinea1');
		$PdfLinea2 = UtilesApp::GetConf($sesion, 'PdfLinea2');

	$ws1->write(0, 0, 'Detalle de tarifa '.$hojas['glosa_tarifa'], $encabezado);
	$ws1->mergeCells (0, 0, 0, 8);
	$info_usr1 = str_replace(array('<br>','<br/>','<br />'),' - ',$PdfLinea1);
	$ws1->write(2, 0, utf8_decode($info_usr1), $encabezado);
	$ws1->mergeCells (2, 0, 2, 8);
	$info_usr = str_replace(array('<br>','<br/>','<br />'),' - ',$PdfLinea2);
	$ws1->write(3, 0, utf8_decode($info_usr), $encabezado);
	$ws1->mergeCells (3, 0, 3, 8);

    $fila_inicial = 7;
    ################################### ENCABEZADOS #################################
    $ws1->write($fila_inicial, 0, __('Profesional'), $tit);
		$lista_monedas = new ListaObjetos($sesion,'',"SELECT * from prm_moneda Order by id_moneda ASC");
		for($x=0;$x < $lista_monedas->num;$x++)
		{
			$moneda = $lista_monedas->Get($x);
			$ws1->write($fila_inicial, $x+1, $moneda->fields['glosa_moneda'], $tit);
		}

    ########## USUARIO TARIFA ###########
		$td_tarifas = '';
		$cont = 0;
		$where = '1';
		if($id_tarifa)
			$where .= " AND usuario_tarifa.id_tarifa =$id_tarifa";
		$query_tarifas = "SELECT	usuario_tarifa.id_usuario,
														usuario_tarifa.id_tarifa,
														IF(usuario_tarifa.tarifa > 0,usuario_tarifa.tarifa,'') AS tarifa,
														usuario_tarifa.id_moneda
														FROM usuario_tarifa
														JOIN usuario ON usuario_tarifa.id_usuario = usuario.id_usuario
														JOIN usuario_permiso ON usuario_permiso.id_usuario=usuario_tarifa.id_usuario
														WHERE $where
														AND usuario.visible = 1 AND usuario_permiso.codigo_permiso='PRO'
														ORDER BY usuario.apellido1, usuario.apellido2, usuario.nombre, usuario.id_usuario, usuario_tarifa.id_moneda ASC";
		$resp = mysql_query($query_tarifas, $sesion->dbh) or Utiles::errorSQL($query_tarifas,__FILE__,__LINE__,$sesion->dbh);
		list($id_usuario_tarifa,$id_tarifa,$tarifa,$id_moneda) = mysql_fetch_array($resp);

    ###################################### SQL ######################################
    $fila_inicial++;
    $query = "SELECT usuario.id_usuario, CONCAT(usuario.apellido1,' ',usuario.apellido2,' ',usuario.nombre) AS nombre_usuario
									FROM usuario
									JOIN usuario_permiso USING(id_usuario)
									WHERE usuario.visible = 1 AND usuario_permiso.codigo_permiso='PRO' ORDER BY usuario.apellido1, usuario.apellido2, usuario.nombre, usuario.id_usuario";
		$resp2 = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		$result = mysql_query("SELECT FOUND_ROWS()");
		$row = mysql_fetch_row($result);
		$total = $row[0];
		while(list($id_usuario,$nombre_usuario) = mysql_fetch_array($resp2))
		{
			$ws1->write($fila_inicial, 0, $nombre_usuario, $f4);
			for($j=0;$j < $lista_monedas->num;$j++)
			{
				$money = $lista_monedas->Get($j);
				if($id_moneda == $money->fields['id_moneda'] && $id_usuario_tarifa == $id_usuario)
				{
					$ws1->write($fila_inicial, $j+1, $tarifa, $f4);
					list($id_usuario_tarifa,$id_tarifa,$tarifa,$id_moneda) = mysql_fetch_array($resp);
				}
				else
					$ws1->write($fila_inicial, $j+1, '', $f4);
			}
			$fila_inicial++;
		}



################################## CLIENTES POR TARIFA ######################################


		$query_clientes = "SELECT
			CONCAT_WS( ' - ', cliente.codigo_cliente, cliente.glosa_cliente) as glosa_cliente,
			GROUP_CONCAT( asunto.codigo_asunto, ' - ', asunto.glosa_asunto ) as glosa_asunto
		FROM cliente
			JOIN asunto ON ( cliente.codigo_cliente = asunto.codigo_cliente )
			JOIN contrato ON ( asunto.id_contrato = contrato.id_contrato )
		WHERE contrato.id_tarifa = '$id_tarifa_mostrar'
		GROUP BY
			cliente.codigo_cliente";
		$resp3 = mysql_query($query_clientes, $sesion->dbh) or Utiles::errorSQL($query_clientes,__FILE__,__LINE__,$sesion->dbh);
		if( mysql_num_rows( $resp3) > 0 ){
			$fila_inicial++;
			$fila_inicial++;
			$ws1->write($fila_inicial, 0, 'CLIENTES', $encabezado);
			$fila_inicial++;
			$fila_inicial++;
			$ws1->mergeCells ($fila_inicial, 0, $fila_inicial, 1);
			$ws1->write($fila_inicial, 0, 'Cliente', $tit);
			$ws1->mergeCells ($fila_inicial, 2, $fila_inicial, 28);
			$ws1->write($fila_inicial, 2, 'Asuntos', $tit);
			$fila_inicial++;
			while( list($glosa_cliente, $glosa_asuntos) = mysql_fetch_array($resp3) ) {

			    $ws1->write($fila_inicial, 0, $glosa_cliente, $f5);




				$ws1->write($fila_inicial, 2, $glosa_asuntos, $f5);

				$fila_inicial++;
			}
		}
	endwhile;
    $wb->close();

	$testimonio = "INSERT INTO z_log_fff SET fecha = NOW(), mensaje='" . mysql_real_escape_string($stack, $sesion->dbh) . "'";
	$respt = mysql_query($testimonio, $sesion->dbh);
	exit;
?>
