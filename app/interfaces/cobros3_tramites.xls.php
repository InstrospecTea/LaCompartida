<?
	require_once 'Spreadsheet/Excel/Writer.php';
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/Funciones.php';

	$sesion = new Sesion(array('REV', 'ADM', 'PRO'));
	$pagina = new Pagina($sesion);

	#$key = substr(md5(microtime().posix_getpid()), 0, 8);

	$wb = new Spreadsheet_Excel_Writer();

	$wb->setVersion(8);
	$wb->send('Revisión de cobros.xls');
	$wb->setCustomColor(35, 220, 255, 220);
	$wb->setCustomColor(36, 255, 255, 220);

	$encabezado =& $wb->addFormat(array('Size' => 12,
								'VAlign' => 'top',
								'Align' => 'justify',
								'Bold' => '1',
								'Color' => 'black'));
	$tit =& $wb->addFormat(array('Size' => 12,
								'VAlign' => 'top',
								'Align' => 'center',
								'Bold' => '1',
								'Locked' => 1,
								'Border' => 1,
								'FgColor' => '35',
								'Color' => 'black'));
	$f3c =& $wb->addFormat(array('Size' => 11,
								'Align' => 'left',
								'Bold' => '1',
								'FgColor' => '35',
								'Border' => 1,
								'Locked' => 1,
								'Color' => 'black'));
	$f4 =& $wb->addFormat(array('Size' => 11,
								'VAlign' => 'top',
								'Align' => 'justify',
								'Border' => 1,
								'Color' => 'black',
								'NumFormat' => 0));
	$tex =& $wb->addFormat(array('Size' => 11,
								'valign' => 'top',
								'Align' => 'justify',
								'Border' => 1,
								'Color' => 'black',
								'TextWrap' => 1));
	$time_format =& $wb->addFormat(array('Size' => 11,
								'VAlign' => 'top',
								'Align' => 'justify',
								'Border' => 1,
								'Color' => 'black',
								'NumFormat' => '[h]:mm'));
	$total =& $wb->addFormat(array('Size' => 11,
								'Align' => 'right',
								'Bold' => '1',
								'FgColor' => '36',
								'Border' => 1,
								'Color' => 'black',
								'NumFormat' => 0));

	$ws =& $wb->addWorksheet(__('Reportes'));
	#$ws->setInputEncoding('utf-8');
	$ws->fitToPages(1, 0);
	$ws->setZoom(75);

	// Definición de columnas
		$col_fecha = 0;
		$col_cliente = 1;
		$col_asunto = 2;
		$col_descripcion = 3;
		$col_nombre = 4;
		$col_apellido = 5;
		$col_duracion = 6;
		$col_valor_tramite = 7;


	// Valores para las fórmulas
	$col_formula_duracion = Utiles::NumToColumnaExcel($col_duracion);
	$col_formula_valor_tramite = Utiles::NumToColumnaExcel($col_valor_tramite);

	$col = 3;
	// Setear el ancho de las columnas
	$ws->setColumn($col_fecha, $col_fecha, 10);
	$ws->setColumn($col_cliente, $col_cliente, 30);
	$ws->setColumn($col_asunto, $col_asunto, 30);
	$ws->setColumn($col_descripcion, $col_descripcion, 33);
	$ws->setColumn($col_nombre, $col_nombre, 30);
	$ws->setColumn($col_apellido, $col_apellido, 25);
	$ws->setColumn($col_duracion, $col_duracion, 15.67);
	$ws->setColumn($col_valor_tramite, $col_valor_tramite, 20);

	if(method_exists('Conf', 'GetConf'))
	{
		$PdfLinea1 = Conf::GetConf($sesion, 'PdfLinea1');
		$PdfLinea2 = Conf::GetConf($sesion, 'PdfLinea2');
	}
	else
	{
		$PdfLinea1 = Conf::PdfLinea1();
		$PdfLinea2 = Conf::PdfLinea2();
	}

	$info_usr1 = str_replace('<br>', ' - ', $PdfLinea1);
	$ws->write(1, 0, $info_usr1, $encabezado);
	$ws->mergeCells(1, 0, 1, 9);
	$info_usr = str_replace('<br>', ' - ', $PdfLinea2);
	$ws->write(2, 0, utf8_decode($info_usr), $encabezado);
	$ws->mergeCells(2, 0, 2, 9);

	$fila_inicial = 4;

	$ws->write($fila_inicial, $col_fecha, __('Fecha'), $tit);
	$ws->write($fila_inicial, $col_cliente, __('Cliente'), $tit);
	$ws->write($fila_inicial, $col_asunto, __('Asunto'), $tit);
	$ws->write($fila_inicial, $col_descripcion, __('Descripción'), $tit);
	$ws->write($fila_inicial, $col_nombre, __('Nombre'), $tit);
	$ws->write($fila_inicial, $col_apellido, __('Apellido'), $tit);
	$ws->write($fila_inicial, $col_duracion, __('Duración'), $tit);
	$params_array['codigo_permiso'] = 'COB';
	$p_cobranza = $sesion->usuario->permisos->Find('FindPermiso', $params_array);
	if($p_cobranza->fields['permitido'])
	{
		$ws->write($fila_inicial, $col_valor_tramite, __('Valor tramite'), $tit);
	}
	$fila_inicial++;

	#La lista viene de la pagina en la cual se incluye esta.
	for($i = 0; $i < $lista->num; $i++)
	{
		$tramite = $lista->Get($i);
//echo '<pre>'; var_dump($tramite->fields); exit;
		$moneda_total = new Objeto($sesion, '', '', 'prm_moneda', 'id_moneda');
		$moneda_total->Load($tramite->fields['id_moneda_asunto'] > 0 ? $tramite->fields['id_moneda_asunto'] : 1);
		
		// Redefinimos el formato de la moneda, para que sea consistente con la cifra.
		$simbolo_moneda = $moneda_total->fields['simbolo'];
		$cifras_decimales = $moneda_total->fields['cifras_decimales'];
		if($cifras_decimales)
		{
			$decimales = '.';
			while($cifras_decimales--)
				$decimales .= '0';
		}
		else
			$decimales = '';
		$money_format =& $wb->addFormat(array('Size' => 11,
								'VAlign' => 'top',
								'Align' => 'justify',
								'Border' => 1,
								'Color' => 'black',
								'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"));

		$ws->write($fila_inicial + $i, $col_fecha, Utiles::sql2date($tramite->fields[fecha], "%d-%m-%Y"), $tex);
		$ws->write($fila_inicial + $i, $col_cliente, $tramite->fields[glosa_cliente], $tex);
		$ws->write($fila_inicial + $i, $col_asunto, $tramite->fields[glosa_asunto], $tex);
		
		$text_descripcion = addslashes($tramite->fields['glosa_tramite'].'   '.$tramite->fields['descripcion']);

		$ws->write($fila_inicial + $i, $col_descripcion, $text_descripcion, $tex);
		$ws->write($fila_inicial + $i, $col_nombre, $tramite->fields[nombre], $tex);
		$ws->write($fila_inicial + $i, $col_apellido, $tramite->fields[apellido1], $tex);

		$duracion= $tramite->fields[duracion];
		list($h, $m)= split(':', $duracion);
		$tiempo_excel = $h/(24)+ $m/(24*60); //Excel cuenta el tiempo en días
		$ws->writeNumber($fila_inicial + $i, $col_duracion, $tiempo_excel, $time_format);

		$params_array['codigo_permiso'] = 'REV';
		$p_revisor = $sesion->usuario->permisos->Find('FindPermiso', $params_array);
		
		if($p_cobranza->fields['permitido'])
		{
			$tarifa = Funciones::TramiteTarifa($sesion, $tramite->fields['id_tramite_tipo'],$tramite->fields['id_moneda_asunto'],$tramite->fields['codigo_asunto']); 
			$ws->writeNumber($fila_inicial + $i, $col_valor_tramite, $tarifa, $money_format);
		}
	}

	$ws->writeFormula($fila_inicial+$i, $col_duracion, "=SUM($col_formula_duracion".($fila_inicial+1).":$col_formula_duracion".($fila_inicial+$i).")", $time_format);
	// No tiene sentido sumar los totales porque pueden estar en monedas distintas.
	
	$wb->close();
	exit;
?>
