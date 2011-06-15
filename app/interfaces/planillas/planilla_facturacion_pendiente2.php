<?
	require_once 'Spreadsheet/Excel/Writer.php';
	require_once dirname(__FILE__).'/../../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
	require_once Conf::ServerDir().'/classes/Cliente.php';
	require_once Conf::ServerDir().'/classes/Trabajo.php';
	require_once Conf::ServerDir().'/classes/Reporte.php';

	$sesion = new Sesion(array('REP'));
	$pagina = new Pagina($sesion);

	set_time_limit(300);
	
	if($xls)
	{
		$moneda_base = Utiles::MonedaBase($sesion);
		#ARMANDO XLS
		$wb = new Spreadsheet_Excel_Writer();

		$wb->setCustomColor(35, 220, 255, 220);
		$wb->setCustomColor(36, 255, 255, 220);

		$formato_encabezado =& $wb->addFormat(array('Size' => 12,
									'VAlign' => 'top',
									'Align' => 'left',
									'Bold' => '1',
									'underline'=>1,
									'Color' => 'black'));

		$formato_texto =& $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'left',
									'Border' => 1,
									'Color' => 'black',
									'TextWrap' => 1));
		$formato_tiempo =& $wb->addFormat(array('Size' => 11,
									'VAlign' => 'top',
									'Border' => 1,
									'Color' => 'black',
									'NumFormat' =>'[h]:mm'));
		$formato_numero =& $wb->addFormat(array('Size' => 11,
									'VAlign' => 'top',
									'Border' => 1,
									'Color' => 'black',
									'NumFormat' => 0));
		$formato_titulo =& $wb->addFormat(array('Size' => 12,
									'Align' => 'center',
									'Bold' => '1',
									'FgColor' => '35',
									'Border' => 1,
									'Locked' => 1,
									'Color' => 'black'));

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
																'Align' => 'right',
																'Border' => '1',
																'Color' => 'black',
																'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"));
		}
		$cifras_decimales = $moneda_base['cifras_decimales'];
		if($cifras_decimales>0)
		{
			$decimales = '.';
			while($cifras_decimales-- >0)
				$decimales .= '0';
		}
		else
			$decimales = '';
		$formato_moneda_base_rojo =& $wb->addFormat(array('Size' => 11,
														'VAlign' => 'top',
														'Align' => 'right',
														'Border' => 1,
														'Color' => 'red',
														'NumFormat' => '[$'.$moneda_base['simbolo']."] #,###,0$decimales"));

		$ws1 =& $wb->addWorksheet(__('Facturacion'));
		$ws1->setInputEncoding('utf-8');
		$ws1->fitToPages(1,0);
		$ws1->setZoom(75);

		$filas += 1;
		$ws1->mergeCells($filas, 1, $filas, 2);
		$ws1->write($filas, 1, __('REPORTE HORAS POR FACTURAR'), $formato_encabezado);
		$ws1->write($filas, 2, '', $formato_encabezado);
		$filas +=2;
		$ws1->write($filas,1,__('GENERADO EL:'),$formato_texto);
		$ws1->write($filas,2,date("d-m-Y H:i:s"),$formato_texto);
		
		$where = '';
		if( $fecha1 != '' and $fecha2 != '' )
		{
			$where .= " AND trabajo.fecha >= '".$fecha1."' AND trabajo.fecha <= '".$fecha2."'";
			$filas +=1;
			$ws1->write($filas,1,__('FECHA CONSULTA:'),$formato_texto);
			$ws1->write($filas,2,Utiles::sql2date($fecha1).' - '.Utiles::sql2date($fecha2),$formato_texto);
		}
		if(is_array($socios))
		{
			$lista_socios = join("','", $socios);
			$where .= " AND contrato.id_usuario_responsable IN ('$lista_socios')";
		}
		if($separar_asuntos)
			$group_by="asunto.codigo_asunto";
		else
			$group_by="asunto.codigo_cliente,asunto.id_contrato";

		$filas +=4;
		$col = 0;

		$col_codigo_cliente = ++$col;
		$col_cliente = ++$col;
		$col_usuario_encargado = ++$col;
		$col_asunto = ++$col;
		$col_ultimo_cobro = ++$col;
		$col_forma_cobro = ++$col;
		$col_valor_estimado = ++$col;
		$col_tipo_cambio = ++$col;
		$col_valor_en_moneda_base = ++$col;
		$col_valor_en_moneda_base_segun_THH = ++$col;
		$col_horas_trabajadas = ++$col;
		unset($col);

		$ws1->setColumn($col_codigo_cliente, $col_codigo_cliente, 14);
		$ws1->setColumn($col_cliente, $col_cliente, 40);
		$ws1->setColumn($col_usuario_encargado, $col_usuario_encargado, 40);
		$ws1->setColumn($col_asunto, $col_asunto, 40);
		$ws1->setColumn($col_ultimo_cobro, $col_ultimo_cobro, 14);
		$ws1->setColumn($col_forma_cobro, $col_forma_cobro, 14);
		$ws1->setColumn($col_valor_estimado, $col_valor_estimado, 18);
		$ws1->setColumn($col_tipo_cambio, $col_tipo_cambio, 14);
		$ws1->setColumn($col_valor_en_moneda_base, $col_valor_en_moneda_base, 18);
		$ws1->setColumn($col_valor_en_moneda_base_segun_THH, $col_valor_en_moneda_base_segun_THH, 23);
		$ws1->setColumn($col_horas_trabajadas, $col_horas_trabajadas, 19);

		$ws1->write($filas, $col_codigo_cliente, __('Código'), $formato_titulo);
		$ws1->write($filas, $col_cliente, __('Cliente'), $formato_titulo);
		$ws1->write($filas, $col_usuario_encargado, __('Encargado'), $formato_titulo);
		$ws1->write($filas, $col_asunto, __('Asunto'), $formato_titulo);
		$ws1->write($filas, $col_ultimo_cobro, __('Ultimo cobro'), $formato_titulo);
		$ws1->write($filas, $col_forma_cobro, __('Forma cobro'), $formato_titulo);
		$ws1->write($filas, $col_valor_estimado, __('Valor estimado'), $formato_titulo);
		$ws1->write($filas, $col_tipo_cambio, __('Tipo Cambio'), $formato_titulo);
		$ws1->write($filas, $col_valor_en_moneda_base, __('Valor en $'), $formato_titulo);
		$ws1->write($filas, $col_valor_en_moneda_base_segun_THH, __('Valor en $ según THH'), $formato_titulo);
		$ws1->write($filas, $col_horas_trabajadas, __('Horas trabajadas'), $formato_titulo);

		$query =
		"SELECT glosa_cliente,
			asuntos,
			forma_cobro,
			if(tabla1.forma_cobro <> 'TASA', tabla1.monto_contrato, tabla1.valor_hh) AS monto,
			valor_hh AS valor_cobro,
			prm_moneda.id_moneda,
			prm_moneda.glosa_moneda,
			prm_moneda.tipo_cambio,
			codigo_cliente,
			usuario,
			hr_por_cobrar AS horas_por_cobrar,
			(SELECT MAX(cobro.fecha_fin)
				FROM cobro
				WHERE cobro.codigo_cliente = tabla1.codigo_cliente
					AND cobro.estado <> 'CREADO' AND cobro.estado <> 'EN REVISION') AS fecha_ultimo_cobro,
			tabla1.glosa_contrato
		FROM
			(	-- Selecciono Todos los asuntos con sus horas por cobrar y con el monto según THH --
			SELECT cliente.`codigo_cliente`,
				cliente.glosa_cliente,
				CONCAT(usuario.nombre,' ',usuario.apellido1) as usuario,
				GROUP_CONCAT( distinct glosa_asunto SEPARATOR '\n' ) AS asuntos	,
				contrato.forma_cobro AS forma_cobro,
				asunto.codigo_asunto,
				asunto.glosa_asunto,
				SUM( TIME_TO_SEC( duracion_cobrada ) )/3600 AS hr_por_cobrar,
				asunto.id_contrato,
				sum( usuario_tarifa.tarifa * TIME_TO_SEC( duracion_cobrada )/3600) AS valor_hh,
				contrato.monto AS monto_contrato,
				contrato.`glosa_contrato`,
				contrato.id_moneda AS id_moneda
			FROM cliente
				JOIN asunto ON asunto.codigo_cliente = cliente.codigo_cliente
				LEFT JOIN contrato ON (contrato.id_contrato = IF(asunto.id_contrato > 0, asunto.id_contrato, cliente.id_contrato))
				LEFT JOIN trabajo ON trabajo.codigo_asunto = asunto.codigo_asunto
				LEFT JOIN cobro ON trabajo.id_cobro = cobro.id_cobro
				LEFT JOIN usuario ON usuario.id_usuario=contrato.id_usuario_responsable
				LEFT JOIN usuario_tarifa ON (usuario_tarifa.id_usuario = trabajo.id_usuario
					AND usuario_tarifa.id_moneda = contrato.id_moneda
					AND usuario_tarifa.id_tarifa = contrato.id_tarifa)
			WHERE trabajo.cobrable =1
				$where
				AND (trabajo.id_cobro IS NULL OR cobro.estado = 'CREADO' OR cobro.estado = 'EN REVISION')
				AND asunto.`activo` = 1
			GROUP BY $group_by
			ORDER BY cliente.glosa_cliente) AS tabla1
		LEFT JOIN prm_moneda ON prm_moneda.id_moneda = tabla1.id_moneda";
		#Clientes
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		$fila_inicial = $filas+2;
		while($cobro = mysql_fetch_array($resp))
		{
			++$filas;
			$ws1->write($filas, $col_codigo_cliente, $cobro['codigo_cliente'], $formato_texto);
			$ws1->write($filas, $col_cliente, $cobro['glosa_cliente'], $formato_texto);
			$ws1->write($filas, $col_usuario_encargado, $cobro['usuario'], $formato_texto);
			$ws1->write($filas, $col_asunto,$cobro['asuntos'], $formato_texto);
			$ws1->write($filas, $col_ultimo_cobro,$cobro['fecha_ultimo_cobro'] != '' ? Utiles::sql2date($cobro['fecha_ultimo_cobro']) : '', $formato_texto);
			$ws1->write($filas, $col_forma_cobro,$cobro['forma_cobro'], $formato_texto);
			$valor_estimado = $cobro['monto'];
			$ws1->writeNumber($filas, $col_valor_estimado, $valor_estimado, $formatos_moneda[$cobro['id_moneda']]);
			$ws1->writeNumber($filas, $col_tipo_cambio,$cobro['tipo_cambio'], $formatos_moneda[$moneda_base['id_moneda']]);
			//$valor_estimado_moneda_base = str_replace(',', '.', $valor_estimado *$cobro['tipo_cambio']/$moneda_base['tipo_cambio']);
			$valor_estimado_moneda_base = UtilesApp::CambiarMoneda($valor_estimado, $cobro['tipo_cambio'], $moneda_base['cifras_decimales'], $moneda_base['tipo_cambio'],$moneda_base['cifras_decimales']);
			$ws1->write($filas, $col_valor_en_moneda_base, $valor_estimado_moneda_base, $formatos_moneda[$moneda_base['id_moneda']]);

			//$valor_thh_moneda_base = str_replace(',', '.', $cobro['valor_cobro'] * $cobro['tipo_cambio']/$moneda_base['tipo_cambio']);
			$valor_thh_moneda_base = UtilesApp::CambiarMoneda($cobro['valor_cobro'], $cobro['tipo_cambio'], $moneda_base['cifras_decimales'], $moneda_base['tipo_cambio'],$moneda_base['cifras_decimales']);
			if($valor_estimado_moneda_base < $valor_thh_moneda_base )
				$formato = $formato_moneda_base_rojo;
			else
				$formato = $formatos_moneda[$moneda_base['id_moneda']];
			$ws1->write($filas, $col_valor_en_moneda_base_segun_THH, $valor_thh_moneda_base, $formato);

			// Excel guarda los tiempos en base a días, por eso se divide en 24.
			$ws1->writeNumber($filas, $col_horas_trabajadas, $cobro['horas_por_cobrar']/24, $formato_tiempo);
		}

		// Escribir totales
		$col_formula_valor_en_moneda_base = Utiles::NumToColumnaExcel($col_valor_en_moneda_base);
		$ws1->writeFormula(++$filas, $col_valor_en_moneda_base, "=SUM($col_formula_valor_en_moneda_base$fila_inicial:$col_formula_valor_en_moneda_base$filas)", $formatos_moneda[$moneda_base['id_moneda']]);

		$col_formula_valor_en_moneda_base_segun_THH = Utiles::NumToColumnaExcel($col_valor_en_moneda_base_segun_THH);
		$ws1->writeFormula($filas, $col_valor_en_moneda_base_segun_THH, "=SUM($col_formula_valor_en_moneda_base_segun_THH$fila_inicial:$col_formula_valor_en_moneda_base_segun_THH$filas)", $formatos_moneda[$moneda_base['id_moneda']]);

		$col_formula_horas_trabajadas = Utiles::NumToColumnaExcel($col_horas_trabajadas);
		$ws1->writeFormula($filas, $col_horas_trabajadas, "=SUM($col_formula_horas_trabajadas$fila_inicial:$col_formula_horas_trabajadas$filas)", $formato_tiempo);

		$wb->send("Planilla horas por facturar.xls");
		$wb->close();
		exit;
	}

	$pagina->titulo = __('Reporte Facturación pendiente');
	$pagina->PrintTop();
?>
<form method=post name=formulario action="planilla_facturacion_pendiente.php?xls=1">
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
					WHERE codigo_permiso='SOC' ORDER BY apellido1", "socios[]", $socios,"class=\"selectMultiple\" multiple size=6 ","","200"); ?>
			</td>
		</tr>
		<tr>
			<td align=center colspan="2">
				<input type="checkbox" value=1 name="separar_asuntos" <?=$separar_asuntos ? 'checked' : ''?>><?=__('Separar Asuntos')?>
			</td>
		</tr>
		<tr>
			<td align=right colspan=2>
				<input type="submit" class=btn value="<?=__('Generar reporte')?>" name="btn_reporte">
			</td>
		</tr>
	</table>
</form>
<?
	echo(InputId::Javascript($sesion));
	$pagina->PrintBottom();
?>
