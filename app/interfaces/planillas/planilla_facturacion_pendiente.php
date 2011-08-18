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
	require_once Conf::ServerDir().'/classes/Moneda.php';

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
			$group_by="contrato.id_contrato";

		$filas +=4;
		$col = 0;

		$col_codigo_cliente = ++$col;
		$col_cliente = ++$col;
		$col_usuario_encargado = ++$col;
		$col_asunto = ++$col;
		$col_ultimo_cobro = ++$col;
		$col_estado_ultimo_cobro = ++$col;
		$col_horas_trabajadas = ++$col;
		$col_forma_cobro = ++$col;
		$col_valor_estimado = ++$col;
		$col_tipo_cambio = ++$col;
		$col_valor_en_moneda_base = ++$col;
		$col_valor_en_moneda_base_segun_THH = ++$col;

		if($debug)
		{
			$col_monto_contrato = ++$col;
			$col_horas_retainer = ++$col;
			$col_valor_cap = ++$col;
			$col_porcentaje_retainer = ++$col;
		}
		unset($col);

		$ws1->setColumn($col_codigo_cliente, $col_codigo_cliente, 16);
		$ws1->setColumn($col_cliente, $col_cliente, 40);
		$ws1->setColumn($col_usuario_encargado, $col_usuario_encargado, 40);
		$ws1->setColumn($col_asunto, $col_asunto, 40);
		$ws1->setColumn($col_ultimo_cobro, $col_ultimo_cobro, 14);
		$ws1->setColumn($col_estado_ultimo_cobro, $col_estado_ultimo_cobro, 22);
		$ws1->setColumn($col_forma_cobro, $col_forma_cobro, 14);
		$ws1->setColumn($col_valor_estimado, $col_valor_estimado, 18);
		$ws1->setColumn($col_tipo_cambio, $col_tipo_cambio, 14);
		$ws1->setColumn($col_valor_en_moneda_base, $col_valor_en_moneda_base, 18);
		$ws1->setColumn($col_valor_en_moneda_base_segun_THH, $col_valor_en_moneda_base_segun_THH, 23);
		$ws1->setColumn($col_horas_trabajadas, $col_horas_trabajadas, 19);

		if($debug)
		{	
			$ws1->setColumn($col_monto_contrato, $col_monto_contrato, 18);
			$ws1->setColumn($col_horas_retainer, $col_horas_retainer, 18);
			$ws1->setColumn($col_valor_cap, $col_valor_cap, 18);
			$ws1->setColumn($col_porcentaje_retainer, $col_porcentaje_retainer, 18);
		}

		$ws1->write($filas, $col_codigo_cliente, __('CÛdigo Asunto'), $formato_titulo);
		$ws1->write($filas, $col_cliente, __('Cliente'), $formato_titulo);
		$ws1->write($filas, $col_usuario_encargado, __('Encargado'), $formato_titulo);
		$ws1->write($filas, $col_asunto, __('Asunto'), $formato_titulo);
		$ws1->write($filas, $col_ultimo_cobro, __('⁄ltimo cobro'), $formato_titulo);
		$ws1->write($filas, $col_estado_ultimo_cobro, __('Estado ˙ltimo cobro'), $formato_titulo);
		$ws1->write($filas, $col_forma_cobro, __('Forma cobro'), $formato_titulo);
		$ws1->write($filas, $col_valor_estimado, __('Valor estimado'), $formato_titulo);
		$ws1->write($filas, $col_tipo_cambio, __('Tipo Cambio'), $formato_titulo);
		$ws1->write($filas, $col_valor_en_moneda_base, __('Valor en '.Moneda::GetSimboloMoneda($sesion,Moneda::GetMonedaBase($sesion))), $formato_titulo);
		$ws1->write($filas, $col_valor_en_moneda_base_segun_THH, __('Valor en '.Moneda::GetSimboloMoneda($sesion,Moneda::GetMonedaBase($sesion)).' seg˙n THH'), $formato_titulo);
		$ws1->write($filas, $col_horas_trabajadas, __('Horas trabajadas'), $formato_titulo);
		if($debug)
		{
			$ws1->write($filas, $col_monto_contrato, __('Monto Contrato'), $formato_titulo);
			$ws1->write($filas, $col_horas_retainer, __('Horas Retainer'), $formato_titulo);
			$ws1->write($filas, $col_valor_cap, __('Cap Usado'), $formato_titulo);
			$ws1->write($filas, $col_porcentaje_retainer, __('Porcentaje Retainer'), $formato_titulo);
		}

		$query =
		"SELECT glosa_cliente,
			asuntos,
			forma_cobro,
			if(tabla1.forma_cobro <> 'TASA', tabla1.monto_contrato, tabla1.valor_hh) AS monto,
			valor_hh AS valor_cobro,  -- Este dato est√° en la moneda de la tarifa --
			valor_hh_std AS valor_cobro_std,  -- Este dato est√° en la moneda de la tarifa --
			codigo_cliente,
			codigo_asunto,
			tabla1.id_contrato,
			usuario,
			usuario_username,
			hr_por_cobrar AS horas_por_cobrar,
			retainer_horas AS retainer_horas,
		(SELECT cobro.estado
				FROM cobro
				WHERE cobro.codigo_cliente = tabla1.codigo_cliente
					AND cobro.estado <> 'CREADO' AND cobro.estado <> 'EN REVISION' ORDER BY cobro.fecha_fin DESC LIMIT 1) AS estado_ultimo_cobro,
			(SELECT MAX(cobro.fecha_fin)
				FROM cobro
				WHERE cobro.codigo_cliente = tabla1.codigo_cliente
					AND cobro.estado <> 'CREADO' AND cobro.estado <> 'EN REVISION') AS fecha_ultimo_cobro,
			tabla1.glosa_contrato,
			tabla1.id_moneda_retainer,
			tabla1.id_moneda_total,
			tabla1.id_moneda_tarifa,
			tabla1.valor_descuento,
			tabla1.porcentaje_descuento 
		FROM
			(	-- Selecciono Todos los asuntos con sus horas por cobrar y con el monto seg˙n THH --
			SELECT cliente.`codigo_cliente`,
				cliente.glosa_cliente,
				CONCAT(usuario.nombre,' ',usuario.apellido1) as usuario,
				usuario.username as usuario_username,
				GROUP_CONCAT( distinct glosa_asunto SEPARATOR '\n' ) AS asuntos	,
				contrato.forma_cobro AS forma_cobro,
				asunto.codigo_asunto,
				asunto.glosa_asunto,
				SUM( TIME_TO_SEC( duracion_cobrada ) )/3600 AS hr_por_cobrar,
				sum(
					(SELECT tarifa FROM usuario_tarifa where usuario_tarifa.id_usuario = trabajo.id_usuario
				   	AND usuario_tarifa.id_moneda = contrato.id_moneda
						AND usuario_tarifa.id_tarifa = contrato.id_tarifa)
				* TIME_TO_SEC( duracion_cobrada ))/3600 AS valor_hh,
				sum(
              (SELECT tarifa FROM usuario_tarifa where usuario_tarifa.id_usuario = trabajo.id_usuario
                  AND usuario_tarifa.id_moneda = contrato.id_moneda
                  AND usuario_tarifa.id_tarifa = (select id_tarifa FROM tarifa where tarifa_defecto=1))
				* TIME_TO_SEC( duracion_cobrada ))/3600 AS valor_hh_std,
				contrato.id_contrato,
				contrato.monto AS monto_contrato,
				contrato.`glosa_contrato`,
				contrato.retainer_horas AS retainer_horas,
				contrato.id_moneda_monto AS id_moneda_retainer,
				contrato.opc_moneda_total AS id_moneda_total,
				contrato.id_moneda AS id_moneda_tarifa,
				contrato.descuento AS valor_descuento,
				contrato.porcentaje_descuento AS porcentaje_descuento 
			FROM 
				trabajo
				LEFT JOIN asunto ON trabajo.codigo_asunto = asunto.codigo_asunto
				LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
				LEFT JOIN contrato ON (contrato.id_contrato = IF(asunto.id_contrato > 0, asunto.id_contrato, cliente.id_contrato))
				LEFT JOIN cobro ON trabajo.id_cobro = cobro.id_cobro
				LEFT JOIN usuario ON usuario.id_usuario=contrato.id_usuario_responsable
			WHERE trabajo.cobrable =1 AND trabajo.id_tramite = 0 
				$where
				AND (trabajo.id_cobro IS NULL OR cobro.estado = 'CREADO' OR cobro.estado = 'EN REVISION')
--				AND asunto.`activo` = 1    No se bien pq est√° esta linea --
			GROUP BY $group_by
			ORDER BY cliente.glosa_cliente) AS tabla1
	";	
		//LEFT JOIN prm_moneda as moneda_total ON moneda_total.id_moneda = tabla1.id_moneda_total";
		#Clientes
		$arreglo_monedas = ArregloMonedas($sesion);
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		$fila_inicial = $filas+2;
		while($cobro = mysql_fetch_array($resp))
		{
			++$filas;
			$ws1->write($filas, $col_codigo_cliente, $cobro['codigo_asunto'], $formato_texto);
			$ws1->write($filas, $col_cliente, $cobro['glosa_cliente'], $formato_texto);
			if( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaUsernameEnTodoElSistema') )
				$ws1->write($filas, $col_usuario_encargado, $cobro['usuario_username'], $formato_texto);
			else
				$ws1->write($filas, $col_usuario_encargado, $cobro['usuario'], $formato_texto);
			$ws1->write($filas, $col_asunto,$cobro['asuntos'], $formato_texto);
			$ws1->write($filas, $col_ultimo_cobro,$cobro['fecha_ultimo_cobro'] != '' ? Utiles::sql2date($cobro['fecha_ultimo_cobro']) : '', $formato_texto);
			$ws1->write($filas, $col_estado_ultimo_cobro,$cobro['estado_ultimo_cobro'] != '' ? $cobro['estado_ultimo_cobro'] : '', $formato_texto);
			$ws1->write($filas, $col_forma_cobro,$cobro['forma_cobro'], $formato_texto);

			//El valor estimado lo manejareos en la moneda total para la coherencia del reporte.
			$cobro['monto'] = $cobro['monto']   * ( $arreglo_monedas[$cobro['id_moneda_retainer']]['tipo_cambio']/$arreglo_monedas[$cobro['id_moneda_total']]['tipo_cambio']);
			$cobro['valor_cobro'] = $cobro['valor_cobro'] * ( $arreglo_monedas[$cobro['id_moneda_tarifa']]['tipo_cambio']/$arreglo_monedas[$cobro['id_moneda_total']]['tipo_cambio']); //lo llevamos a moneda total
			$cobro['valor_cobro_std'] = $cobro['valor_cobro_std'] * ( $arreglo_monedas[$cobro['id_moneda_tarifa']]['tipo_cambio']/$arreglo_monedas[$cobro['id_moneda_total']]['tipo_cambio']); //lo llevamos a moneda total
			//Todo a moneda total para c√lculos
			
			// En el primer asunto de un contrato hay que actualizar el valor descuento al contrato actual
			if( $cobro['id_contrato'] != $id_contrato_anterior )
				$valor_descuento = $cobro['valor_descuento'];
			
			$valor_estimado = $cobro['monto'];
			
			if($cobro['forma_cobro']=='CAP')	
			{
					$cobro_aux = new Cobro($sesion);
					$usado = $cobro_aux->TotalCobrosCap($cobro['id_contrato'],$cobro['id_moneda_total']); //Llevamos lo cobrado en el CAP a la moneda TOTAL
					if($cobro['valor_cobro']+$usado > $cobro['monto'] )
					{
						$valor_estimado = $cobro['monto'] - $usado;
						if($valor_estimado < 0)
							$valor_estimado = 0;
					}
					else
						$valor_estimado = $cobro['valor_cobro'];
			}
			if($cobro['forma_cobro']=='PROPORCIONAL' || $cobro['forma_cobro']=='RETAINER')
			{
					if($cobro['retainer_horas'] > 0)
					if($cobro['retainer_horas'] < $cobro['horas_por_cobrar'])
					{
						$porcentaje_retainer = 1.00*($cobro['horas_por_cobrar']-$cobro['retainer_horas'])/$cobro['horas_por_cobrar'];
						$valor_estimado += $cobro['valor_cobro']*$porcentaje_retainer;
					}
			}
			if($cobro['forma_cobro'] == 'TASA')
			{
				$valor_estimado = $cobro['valor_cobro'];
			}
			// Aplicar descuentos del contrato al valor estimado
			if( $cobro['porcentaje_descuento'] > 0 )
				{
					$valor_estimado *= ( 1 - $cobro['porcentaje_descuento']/100 );
				}
			else if( $valor_descuento > 0 )
				{
					$valor_estimado = $valor_estimado - $valor_descuento;
					if( $valor_estimado < 0 )
						{
							$valor_descuento =  abs($valor_estimado); 
							$valor_estimado = 0;
						}
					else
						$valor_descuento = 0;
				}
			$ws1->writeNumber($filas, $col_valor_estimado, $valor_estimado, $formatos_moneda[$cobro['id_moneda_total']]);
			$ws1->writeNumber($filas, $col_tipo_cambio,$arreglo_monedas[$cobro['id_moneda_total']]['tipo_cambio'], $formatos_moneda[$moneda_base['id_moneda']]);
			//$valor_estimado_moneda_base = str_replace(',', '.', $valor_estimado *$cobro['tipo_cambio']/$moneda_base['tipo_cambio']);
			$valor_estimado_moneda_base = UtilesApp::CambiarMoneda($valor_estimado, $arreglo_monedas[$cobro['id_moneda_total']]['tipo_cambio'], $moneda_base['cifras_decimales'], $moneda_base['tipo_cambio'],$moneda_base['cifras_decimales']);
			$texto_prueba = 'VE:'.$valor_estimado.' TC:'.$cobro['tipo_cambio'].'. VEMB:'.$valor_estimado_moneda_base;
			$ws1->write($filas, $col_valor_en_moneda_base, $valor_estimado_moneda_base, $formatos_moneda[$moneda_base['id_moneda']]);
			//$ws1->write($filas, $col_valor_en_moneda_base, $texto_prueba, $formato);

			//$valor_thh_moneda_base = str_replace(',', '.', $cobro['valor_cobro'] * $cobro['tipo_cambio']/$moneda_base['tipo_cambio']);
			//$valor_thh_moneda_base corresponde a el valor a tarifa standar
			$valor_thh_moneda_base = UtilesApp::CambiarMoneda($cobro['valor_cobro_std'],$arreglo_monedas[ $cobro['id_moneda_total']]['tipo_cambio'], $moneda_base['cifras_decimales'], $moneda_base['tipo_cambio'],$moneda_base['cifras_decimales']);
			if($valor_estimado_moneda_base < $valor_thh_moneda_base )
				$formato = $formato_moneda_base_rojo;
			else
				$formato = $formatos_moneda[$moneda_base['id_moneda']];
			$ws1->write($filas, $col_valor_en_moneda_base_segun_THH, $valor_thh_moneda_base, $formato);

			// Excel guarda los tiempos en base a dÌas, por eso se divide en 24.
			$ws1->writeNumber($filas, $col_horas_trabajadas, $cobro['horas_por_cobrar']/24, $formato_tiempo);

			if($debug)
			{
				if($cobro['forma_cobro'] != 'TASA')
				$ws1->write($filas, $col_monto_contrato, $cobro['monto'], $formatos_moneda[$cobro['id_moneda_total']]);
				if($cobro['forma_cobro'] == 'PROPORCIONAL' || $cobro['forma_cobro'] == 'RETAINER')
					$ws1->write($filas, $col_horas_retainer, $cobro['retainer_horas'] , $formato_tiempo);
				if($cobro['forma_cobro'] == 'CAP')
					$ws1->write($filas, $col_valor_cap, $usado, $formatos_moneda[$cobro['id_moneda_total']]);
				if($cobro['forma_cobro'] == 'PROPORCIONAL' || $cobro['forma_cobro'] == 'RETAINER')
					$ws1->write($filas, $col_porcentaje_retainer, $porcentaje_retainer, $formato_numero);

				$ws1->write($filas, $col_porcentaje_retainer+1,$cobro['horas_por_cobrar'], $formato_numero);
			}
			// Memorizarse el id_contrato para ver en el proximo 
			// paso si todavia estamos en el mismo contrato, importante por el tema del descuento
			$id_contrato_anterior = $cobro['id_contrato'];
		}
		
		if($fila_inicial != ($filas+2))
		{
			// Escribir totales
			$col_formula_valor_en_moneda_base = Utiles::NumToColumnaExcel($col_valor_en_moneda_base);
			$ws1->writeFormula(++$filas, $col_valor_en_moneda_base, "=SUM($col_formula_valor_en_moneda_base$fila_inicial:$col_formula_valor_en_moneda_base$filas)", $formatos_moneda[$moneda_base['id_moneda']]);

			$col_formula_valor_en_moneda_base_segun_THH = Utiles::NumToColumnaExcel($col_valor_en_moneda_base_segun_THH);
			$ws1->writeFormula($filas, $col_valor_en_moneda_base_segun_THH, "=SUM($col_formula_valor_en_moneda_base_segun_THH$fila_inicial:$col_formula_valor_en_moneda_base_segun_THH$filas)", $formatos_moneda[$moneda_base['id_moneda']]);

			$col_formula_horas_trabajadas = Utiles::NumToColumnaExcel($col_horas_trabajadas);
		
			$ws1->writeFormula($filas, $col_horas_trabajadas, "=SUM($col_formula_horas_trabajadas$fila_inicial:$col_formula_horas_trabajadas$filas)", $formato_tiempo);
		}

		$wb->send("Planilla horas por facturar.xls");
		$wb->close();
		exit;
	}

	$pagina->titulo = __('Reporte FacturaciÛn pendiente');
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
				<input type="hidden" name="debug" value="<?=$debug?>" />
				<input type="submit" class=btn value="<?=__('Generar reporte')?>" name="btn_reporte">
			</td>
		</tr>
	</table>
</form>
<?
	echo(InputId::Javascript($sesion));
	$pagina->PrintBottom();
?>
