<?
	require_once 'Spreadsheet/Excel/Writer.php';
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';

	$sesion = new Sesion(array('ADM', 'COB'));
	$pagina = new Pagina($sesion);

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
								'NumFormat' => '0'));

	$tex =& $wb->addFormat(array('Size' => 11,
								'valign' => 'top',
								'Align' => 'justify',
								'Border' => 1,
								'Color' => 'black',
								'TextWrap' => 1));

	$time_format =& $wb->addFormat(array('Size' => 11,
										'VAlign' => 'top',
										'Align' => 'center',
										'Border' => 1,
										'Color' => 'black',
										'NumFormat' => '[h]:mm'));

	$money_format =& $wb->addFormat(array('Size' => 11,
										'VAlign' => 'top',
										'Align' => 'right',
										'Border' => 1,
										'Color' => 'black',
										'NumFormat' => '#,##0.00'));

	$total =& $wb->addFormat(array('Size' => 11,
									'Align' => 'right',
									'Bold' => '1',
									'FgColor' => '36',
									'Border' => 1,
									'Color' => 'black',
									'NumFormat' => '0'));

	$fila_inicial = 1;

	// Definir si hay que mostrar la columna de ordenado por
	$mostrar_ordenado_por = 0;
	if( method_exists( 'Conf', 'GetConf' ) && (Conf::GetConf( $sesion, 'OrdenadoPor' )==1 || Conf::GetConf( $sesion, 'OrdenadoPor' )==2 ) )
		$mostrar_ordenado_por = 1;

	// Definir las posiciones de las columnas
	$col = 0;
	$col_fecha = $col++;
	$col_asunto = $col++;
	$col_id_cobro = $col++;
	$col_descripcion = $col++;
	$col_abogado = $col++;
	$col_duracion_trabajada = $col++;
	$col_duracion_cobrable = $col++;
	$col_cobrable = $col++;
	$col_tarifa_hh = $col++;
	$col_valor_trabajo = $col++;
	if( ( method_exists('Conf', 'Ordenado_por') && (Conf::Ordenado_por()==1 || Conf::Ordenado_por()==2) ) || ( method_exists( 'Conf', 'GetConf' ) && (Conf::GetConf( $sesion, 'OrdenadoPor' )==1 || Conf::GetConf( $sesion, 'OrdenadoPor' )==2 ) ) )
		$col_ordenado_por = $col++;
	$col_duracion_trabajada_asunto = $col++;
	$col_duracion_cobrable_asunto = $col++;
	$col_valor_asunto = $col++;

	$paginas=0;

	// Definir variables para las fórmulas, las que están comentadas todavía no se usan.
	#$col_formula_fecha = Utiles::NumToColumnaExcel($col_fecha);
	#$col_formula_asunto = Utiles::NumToColumnaExcel($col_asunto);
	#$col_formula_descripcion = Utiles::NumToColumnaExcel($col_descripcion);
	#$col_formula_abogado = Utiles::NumToColumnaExcel($col_abogado);
	$col_formula_duracion_trabajada = Utiles::NumToColumnaExcel($col_duracion_trabajada);
	$col_formula_duracion_cobrable = Utiles::NumToColumnaExcel($col_duracion_cobrable);
	#$col_formula_cobrable = Utiles::NumToColumnaExcel($col_cobrable);
	$col_formula_tarifa_hh = Utiles::NumToColumnaExcel($col_tarifa_hh);
	$col_formula_valor_trabajo = Utiles::NumToColumnaExcel($col_valor_trabajo);
	#$col_formula_duracion_trabajada_asunto = Utiles::NumToColumnaExcel($col_duracion_trabajada_asunto);
	#$col_formula_duracion_cobrable_asunto = Utiles::NumToColumnaExcel($col_duracion_cobrable_asunto);
	#$col_formula_valor_asunto = Utiles::NumToColumnaExcel($col_valor_asunto);

	#La lista viene de la pagina en la cual se incluye esta.
	for($i = 0; $i < $lista->num; $i++)
	{
		$moneda_total = new Objeto($sesion, '', '', 'prm_moneda', 'id_moneda');
		$moneda_total->Load($trabajo->fields['id_moneda'] > 0 ? $trabajo->fields['id_moneda'] : 1);

		$trabajo = $lista->Get($i);
		if($trabajo->fields['id_contrato']!=$contrato)
		{
			if($tabla_creada)
			{
				//Se muestran los totales de la separación de contratos
				$ws1->writeFormula($fila_inicial, $col_duracion_trabajada, "=SUM($col_formula_duracion_trabajada".($primera_fila_contrato+1).":$col_formula_duracion_trabajada$fila_inicial)", $time_format);
				$ws1->writeFormula($fila_inicial, $col_duracion_cobrable, "=SUM($col_formula_duracion_cobrable".($primera_fila_contrato+1).":$col_formula_duracion_cobrable$fila_inicial)", $time_format);
				$ws1->writeFormula($fila_inicial, $col_valor_trabajo, "=SUM($col_formula_valor_trabajo".($primera_fila_contrato+1).":$col_formula_valor_trabajo$fila_inicial)", $money_format);
				//totales de asuntos(hacia el lado)
				$ws1->writeFormula($fila_inicial-1, $col_duracion_trabajada_asunto, "=SUM($col_formula_duracion_trabajada".($primera_fila_asunto+1).":$col_formula_duracion_trabajada$fila_inicial)", $time_format);
				$ws1->writeFormula($fila_inicial-1, $col_duracion_cobrable_asunto, "=SUM($col_formula_duracion_cobrable".($primera_fila_asunto+1).":$col_formula_duracion_cobrable$fila_inicial)", $time_format);
				$ws1->writeFormula($fila_inicial-1, $col_valor_asunto, "=SUM($col_formula_valor_trabajo".($primera_fila_asunto+1).":$col_formula_valor_trabajo$fila_inicial)", $money_format);
				$fila_inicial+=3;
				$ws1->write(++$fila_inicial, $col_tarifa_hh, __('Subtotal'), $tit);
				$ws1->writeFormula($fila_inicial, $col_valor_trabajo, "=SUM($col_formula_valor_trabajo".($primera_fila_contrato+1).":$col_formula_valor_trabajo".($fila_inicial-4).")", $money_format);
				if($trabajo->fields['descuento'] > 0)
				{
					$ws1->write(++$fila_inicial, $col_tarifa_hh, __('Descuento'), $tit);
					$ws1->writeNumber($fila_inicial, $col_valor_trabajo, $trabajo->fields['descuento'], $money_format);
					$ws1->write(++$fila_inicial, $col_tarifa_hh, __('Subtotal 2'), $tit);
					$ws1->writeFormula($fila_inicial, $col_valor_trabajo, "=$col_formula_valor_trabajo".($fila_inicial-1)."-$col_formula_valor_trabajo$fila_inicial", $money_format);
				}
				if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'ValorImpuesto')>0 ) || ( method_exists('Conf','ValorImpuesto') && Conf::ValorImpuesto()>0 ) )
				{
					$ws1->write(++$fila_inicial, $col_tarifa_hh, __('IVA'), $tit);
					$ws1->writeFormula($fila_inicial, $col_valor_trabajo, "=$col_formula_valor_trabajo$fila_inicial*0.".(method_exists('Conf','GetConf')?Conf::GetConf($sesion,'ValorImpuesto'):Conf::ValorImpuesto()), $money_format);
				}
				$ws1->write(++$fila_inicial, $col_tarifa_hh, __('Total'), $tit);
				$ws1->writeFormula($fila_inicial, $col_valor_trabajo, "=SUM($col_formula_valor_trabajo".($fila_inicial-1).":$col_formula_valor_trabajo$fila_inicial)", $money_format);
				$tabla_creada=false;
				$fila_inicial=1;
			}
			$paginas++;
			$ws1 =& $wb->addWorksheet($paginas.' '.substr($trabajo->fields['glosa_cliente'], 0, 20));

			$ws1->fitToPages(1, 0);
			$ws1->setZoom(75);

			// se setea el ancho de las columnas
			$ws1->setColumn($col_fecha, $col_fecha, 15);
			$ws1->setColumn($col_asunto, $col_asunto, 30);
			$ws1->setColumn($col_id_cobro, $col_id_cobro, 15);
			$ws1->setColumn($col_descripcion, $col_descripcion, 33);
			$ws1->setColumn($col_abogado, $col_abogado, 25);
			$ws1->setColumn($col_duracion_trabajada, $col_duracion_trabajada, 20);
			$ws1->setColumn($col_duracion_cobrable, $col_duracion_cobrable, 20);
			$ws1->setColumn($col_cobrable, $col_cobrable, 15.67);
			$ws1->setColumn($col_tarifa_hh, $col_tarifa_hh, 15);
			$ws1->setColumn($col_valor_trabajo, $col_valor_trabajo, 20);
			if($mostrar_ordenado_por)
				$ws1->setColumn($col_ordenado_por, $col_ordenado_por, 25);
			$ws1->setColumn($col_duracion_trabajada_asunto, $col_duracion_trabajada_asunto, 35);
			$ws1->setColumn($col_duracion_cobrable_asunto, $col_duracion_cobrable_asunto, 35);
			$ws1->setColumn($col_valor_asunto, $col_valor_asunto, 20);

			$ws1->write($fila_inicial, $col_asunto, $trabajo->fields['glosa_cliente'], $encabezado);
			$ws1->mergeCells($fila_inicial, $col_asunto, $fila_inicial, $col_valor_asunto);
			$fila_inicial += 2;

			//Se escriben los títulos por cada contrato
			$ws1->write($fila_inicial, $col_fecha, __('Fecha'), $tit);
			$ws1->write($fila_inicial, $col_asunto, __('Asunto'), $tit);
			$ws1->write($fila_inicial, $col_id_cobro, __('Cobro'), $tit);
			$ws1->write($fila_inicial, $col_descripcion, __('Descripción'), $tit);
			$ws1->write($fila_inicial, $col_abogado, __('Abogado'), $tit);
			$ws1->write($fila_inicial, $col_duracion_trabajada, __('Duración Trabajada'), $tit);
			$ws1->write($fila_inicial, $col_duracion_cobrable, __('Duración Cobrable'), $tit);
			$ws1->write($fila_inicial, $col_cobrable, __('Cobrable'), $tit);

			$ws1->write($fila_inicial, $col_tarifa_hh, __('Tarifa HH').'('.Utiles::glosa($sesion, $trabajo->fields[id_moneda_asunto], 'simbolo', 'prm_moneda', 'id_moneda').')', $tit);
			$ws1->write($fila_inicial, $col_valor_trabajo, __('Valor Trabajo').'('.Utiles::glosa($sesion, $trabajo->fields[id_moneda_asunto], 'simbolo', 'prm_moneda', 'id_moneda').')', $tit);
			if($mostrar_ordenado_por)
				$ws1->write($fila_inicial, $col_ordenado_por, __('Ordenado por'), $tit);
			$ws1->write($fila_inicial, $col_duracion_trabajada_asunto, __('Duración Trabajada Asunto'), $tit);
			$ws1->write($fila_inicial, $col_duracion_cobrable_asunto, __('Duración Cobrable Asunto'), $tit);
			$ws1->write($fila_inicial, $col_valor_asunto, __('Valor Asunto').'('.Utiles::glosa($sesion, $trabajo->fields[id_moneda_asunto], 'simbolo', 'prm_moneda', 'id_moneda').')', $tit);

			$fila_inicial++;
			$primera_fila_contrato=$fila_inicial;
			$primera_fila_asunto=$fila_inicial;
			$tabla_creada=true;
			$primer_asunto=true;
		}
		$contrato=$trabajo->fields['id_contrato'];
		//se escriben las filas
		$ws1->write($fila_inicial, $col_fecha, Utiles::sql2date($trabajo->fields[fecha], "%d-%m-%Y"), $tex);
		$ws1->write($fila_inicial, $col_asunto, $trabajo->fields['glosa_asunto'], $tex);
		$ws1->write($fila_inicial, $col_id_cobro, $trabajo->fields['id_cobro']?$trabajo->fields['id_cobro']:'', $tex);
		$text_descripcion = addslashes($trabajo->fields['descripcion']);

		$ws1->write($fila_inicial, $col_descripcion, $text_descripcion, $tex);
		if( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaUsernameEnTodoElSistema') )
			$ws1->write($fila_inicial, $col_abogado, $trabajo->fields['username'], $tex);
		else
			$ws1->write($fila_inicial, $col_abogado, $trabajo->fields['nombre'].' '.$trabajo->fields['apellido1'], $tex);
		list($duracion, $duracion_cobrada)= split('<br>', $trabajo->fields[duracion]);
		list($h, $m)= split(':', $duracion);
		$tiempo_excel = $h/(24)+ $m/(24*60); //Excel cuenta el tiempo en días
		$ws1->writeNumber($fila_inicial, $col_duracion_trabajada, $tiempo_excel, $time_format);
		list($h, $m)= split(':', $duracion_cobrada);
		$tiempo_excel = $h/(24)+ $m/(24*60); //Excel cuenta el tiempo en días
		$ws1->writeNumber($fila_inicial, $col_duracion_cobrable, $tiempo_excel, $time_format);

		$ws1->write($fila_inicial, $col_cobrable, $trabajo->fields['cobrable'] == 1 ? "SI" : "NO", $tex);
		$ws1->writeNumber($fila_inicial, $col_tarifa_hh, $trabajo->fields['tarifa_hh'], $money_format);
		$ws1->writeFormula($fila_inicial, $col_valor_trabajo, "=$col_formula_tarifa_hh".($fila_inicial+1)."*(24*($col_formula_duracion_cobrable".($fila_inicial+1)."))", $money_format);

		if($mostrar_ordenado_por)
			$ws1->write($fila_inicial, $col_ordenado_por, $trabajo->fields['solicitante'], $tex);

		if($trabajo->fields['codigo_asunto']!=$asunto && !$primer_asunto)
		{
			//totales de asuntos(hacia el lado)
			$ws1->writeFormula($fila_inicial-1, $col_duracion_trabajada_asunto, "=SUM($col_formula_duracion_trabajada".($primera_fila_asunto+1).":$col_formula_duracion_trabajada$fila_inicial)", $time_format);
			$ws1->writeFormula($fila_inicial-1, $col_duracion_cobrable_asunto, "=SUM($col_formula_duracion_cobrable".($primera_fila_asunto+1).":$col_formula_duracion_cobrable$fila_inicial)", $time_format);
			$ws1->writeFormula($fila_inicial-1, $col_valor_asunto, "=SUM($col_formula_valor_trabajo".($primera_fila_asunto+1).":$col_formula_valor_trabajo$fila_inicial)", $money_format);
			$primera_fila_asunto=$fila_inicial;
		}
		$asunto=$trabajo->fields['codigo_asunto'];
		$primer_asunto=false;
		$fila_inicial++;
	}
	$ws1->writeFormula($fila_inicial, $col_duracion_trabajada, "=SUM($col_formula_duracion_trabajada".($primera_fila_contrato+1).":$col_formula_duracion_trabajada$fila_inicial)", $time_format);
	$ws1->writeFormula($fila_inicial, $col_duracion_cobrable, "=SUM($col_formula_duracion_cobrable".($primera_fila_contrato+1).":$col_formula_duracion_cobrable$fila_inicial)", $time_format);
	$ws1->writeFormula($fila_inicial, $col_valor_trabajo, "=SUM($col_formula_valor_trabajo".($primera_fila_contrato+1).":$col_formula_valor_trabajo$fila_inicial)", $money_format);

	$ws1->writeFormula($fila_inicial-1, $col_duracion_trabajada_asunto, "=SUM($col_formula_duracion_trabajada".($primera_fila_asunto+1).":$col_formula_duracion_trabajada$fila_inicial)", $time_format);
	$ws1->writeFormula($fila_inicial-1, $col_duracion_cobrable_asunto, "=SUM($col_formula_duracion_cobrable".($primera_fila_asunto+1).":$col_formula_duracion_cobrable$fila_inicial)", $time_format);
	$ws1->writeFormula($fila_inicial-1, $col_valor_asunto, "=SUM($col_formula_valor_trabajo".($primera_fila_asunto+1).":$col_formula_valor_trabajo$fila_inicial)", $money_format);

	$wb->close();
	exit;
?>