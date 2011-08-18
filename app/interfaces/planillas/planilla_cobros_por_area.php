<?
	require_once 'Spreadsheet/Excel/Writer.php';
	require_once dirname(__FILE__).'/../../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
	require_once Conf::ServerDir().'/classes/Cliente.php';
	require_once Conf::ServerDir().'/classes/Moneda.php';
	require_once Conf::ServerDir().'/classes/Trabajo.php';

	$sesion = new Sesion(array('REP'));
	$pagina = new Pagina($sesion);

	if($xls)
	{
		$moneda_base = Utiles::MonedaBase($sesion);
		#ARMANDO XLS
		$wb = new Spreadsheet_Excel_Writer();

		$wb->setCustomColor ( 35, 220, 255, 220 );
		$wb->setCustomColor ( 36, 255, 255, 220 );

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
		$formato_texto_centrado =& $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'center',
									'Border' => 1,
									'Color' => 'black',
									'TextWrap' => 1));
		$formato_tiempo =& $wb->addFormat(array('Size' => 11,
									'VAlign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Color' => 'black',
									'NumFormat' => '[h]:mm'));
		$formato_titulo =& $wb->addFormat(array('Size' => 12,
									'Align' => 'center',
									'Bold' => '1',
									'FgColor' => '35',
									'Border' => 1,
									'Locked' => 1,
									'Color' => 'black'));

		// Generar formatos para los distintos tipos de moneda
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


		$ws1 =& $wb->addWorksheet(__('Facturacion'));
		$ws1->setInputEncoding('utf-8');
		$ws1->fitToPages(1,5);
		$ws1->setZoom(75);
		$ws1->hideGridlines();
		$ws1->setLandscape();

		// Definir columnas
		$col = 0;
		$col_numero_factura = ++$col;
		$col_fecha_creacion = ++$col;
		$col_cliente = ++$col;
		$col_asunto = ++$col;
		$col_encargado_comercial = ++$col;
		$col_duracion_trabajada = ++$col;
		$col_duracion_cobrada = ++$col;
		$col_ingreso = ++$col;
		$col_ingreso_en_moneda_base = ++$col;
		$col_gastos_en_moneda_base = ++$col;
		$col_estado = ++$col;
		$col_forma_de_cobro = ++$col;
		$col_numero_cobro = ++$col;
		unset($col);

		// Para usar en las fï¿½rmulas de los totales
		$col_formula_duracion_trabajada = Utiles::NumToColumnaExcel($col_duracion_trabajada);
		$col_formula_duracion_cobrada = Utiles::NumToColumnaExcel($col_duracion_cobrada);
		$col_formula_ingreso_en_moneda_base = Utiles::NumToColumnaExcel($col_ingreso_en_moneda_base);
		$col_formula_gastos_en_moneda_base = Utiles::NumToColumnaExcel($col_gastos_en_moneda_base);

		$ws1->setColumn($col_numero_factura, $col_numero_factura, 15);
		$ws1->setColumn($col_fecha_creacion, $col_fecha_creacion, 17);
		$ws1->setColumn($col_cliente, $col_cliente, 35);
		$ws1->setColumn($col_asunto, $col_asunto, 35);
		$ws1->setColumn($col_encargado_comercial, $col_encargado_comercial, 35);
		$ws1->setColumn($col_duracion_trabajada, $col_duracion_trabajada, 20);
		$ws1->setColumn($col_duracion_cobrada, $col_duracion_cobrada, 20);
		$ws1->setColumn($col_ingreso, $col_ingreso, 15);
		$ws1->setColumn($col_ingreso_en_moneda_base, $col_ingreso_en_moneda_base, 20);
		$ws1->setColumn($col_gastos_en_moneda_base, $col_gastos_en_moneda_base, 15);
		$ws1->setColumn($col_estado, $col_estado, 22);
		$ws1->setColumn($col_forma_de_cobro, $col_forma_de_cobro, 19);
		$ws1->setColumn($col_numero_cobro, $col_numero_cobro, 15);

		$filas += 1;
		$ws1->mergeCells($filas, 1, $filas, 3);
		$ws1->write($filas, $col_fecha_creacion, __('REPORTE COBROS POR AREA'), $formato_encabezado);
		for($x=2;$x<4;$x++)
			$ws1->write($filas, $x, '', $formato_encabezado);
		$filas += 2;
		$ws1->write($filas, $col_fecha_creacion,__('GENERADO EL:'),$formato_texto);
		$ws1->write($filas, $col_cliente,date("d-m-Y H:i:s"),$formato_texto);

		$query_fecha = '';
		if( $fecha1 != '' and $fecha2 != '' )
		{
			$query_fecha = " AND cobro.fecha_creacion >= '".Utiles::fecha2sql($fecha1)."' AND cobro.fecha_creacion <= '".Utiles::fecha2sql($fecha2)." 23:59:59'";
			$filas +=1;
			$ws1->write($filas, $col_fecha_creacion,__('FECHA CONSULTA:'),$formato_texto);
			$ws1->write($filas, $col_cliente,$fecha1.' - '.$fecha2,$formato_texto);
		}
		$query_estado = 'AND cobro.estado =';

		switch($estado)
		{
			case 'creado':
				$query_estado .= "'CREADO'";
				break;
			case 'en_revision':
				$query_estado .= "'EN REVISION'";
				break;
			case 'emitido':
				$query_estado .= "'EMITIDO'";
				break;
			case 'facturado':
				$query_estado .= "'FACTURADO'";
				break;
			case 'pago_parcial':
				$query_estado .= "'PAGO PARCIAL'";
				break;
			case 'enviado':
				$query_estado .= "'ENVIADO AL CLIENTE'";
				break;
			case 'pagado':
				$query_estado .= "'PAGADO'";
				break;
			case 'todos':
				$query_estado = '';
				break;
		}

		$query_usuarios = '';
		if(is_array($usuarios))
			$query_usuarios = " AND usuario.id_usuario IN (".implode(',',$usuarios).") ";

		$filas +=4;
		$query ="SELECT		asunto.id_area_proyecto
							,cobro.fecha_creacion
							,cliente.glosa_cliente
							,asunto.glosa_asunto
							,CONCAT(usuario.nombre,' ', usuario.apellido1) AS nombre
							,cobro.saldo_final_gastos
							,cobro.estado
							,cobro.documento as numero 
							,cobro.forma_cobro
							,cobro.id_cobro
							,cobro.opc_moneda_total
							,area.glosa
							,moneda_cobro.simbolo
							,moneda_cobro.id_moneda
							,cobro.total_minutos
							,cobro.opc_moneda_total
							,(SUM( TIME_TO_SEC(duracion_cobrada))/60) AS duracion_cobrada
							,(SUM( TIME_TO_SEC(duracion))/60) AS duracion
							,IF( SUM( TIME_TO_SEC(duracion_cobrada))/60 != cobro.total_minutos,
								 ( ( ( SUM( TIME_TO_SEC(duracion_cobrada))/60 ) / cobro.total_minutos ) * ROUND(cobro.monto_subtotal-cobro.descuento,moneda_cobro.cifras_decimales)),
								 ROUND(cobro.monto_subtotal-cobro.descuento,moneda_cobro.cifras_decimales))
								 AS monto_proporcional
							,IF( SUM( TIME_TO_SEC(duracion_cobrada))/60 != cobro.total_minutos,
								( ( ( SUM( TIME_TO_SEC(duracion_cobrada))/60 ) / cobro.total_minutos ) * ROUND(cobro.monto_subtotal-cobro.descuento,moneda_cobro.cifras_decimales) * ( cobro_moneda_cobro.tipo_cambio / cobro_moneda_moneda_base.tipo_cambio )),
								ROUND(ROUND(cobro.monto_subtotal-cobro.descuento,moneda_cobro.cifras_decimales) * ( cobro_moneda_cobro.tipo_cambio / cobro_moneda_moneda_base.tipo_cambio ),moneda_base.cifras_decimales)) AS total_moneda_base
							FROM cobro
							LEFT JOIN cliente ON cliente.codigo_cliente = cobro.codigo_cliente
							LEFT JOIN cobro_asunto ON cobro_asunto.id_cobro = cobro.id_cobro
							LEFT JOIN asunto ON asunto.codigo_asunto = cobro_asunto.codigo_asunto
							LEFT JOIN trabajo ON trabajo.id_cobro = cobro.id_cobro AND trabajo.codigo_asunto = asunto.codigo_asunto
							LEFT JOIN contrato ON contrato.id_contrato = cobro.id_contrato
							LEFT JOIN usuario ON usuario.id_usuario = contrato.id_usuario_responsable
							LEFT JOIN prm_area_proyecto AS area ON area.id_area_proyecto = asunto.id_area_proyecto
							LEFT JOIN prm_moneda as moneda_cobro ON moneda_cobro.id_moneda = cobro.id_moneda
							LEFT JOIN cobro_moneda as cobro_moneda_cobro ON cobro_moneda_cobro.id_cobro = cobro.id_cobro AND cobro_moneda_cobro.id_moneda = moneda_cobro.id_moneda 
							LEFT JOIN prm_moneda as moneda_base ON moneda_base.moneda_base = 1 
							LEFT JOIN cobro_moneda as cobro_moneda_moneda_base ON cobro_moneda_moneda_base.id_cobro = cobro.id_cobro AND cobro_moneda_moneda_base.id_moneda = moneda_base.id_moneda 
							WHERE 1 $query_fecha $query_estado $query_usuarios 
							GROUP BY cobro.id_cobro, asunto.id_area_proyecto 
							ORDER BY asunto.id_area_proyecto, cliente.glosa_cliente, cobro.id_cobro;";

		#Clientes
		$area = '';
		$area_total_moneda_base=0;
		$sin_area = false; //@todo: esto es temporal
		$tabla_creada = false;
                //echo "<script></script>$query";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		while($cobro = mysql_fetch_array($resp))
		{
			$cobro_moneda = new CobroMoneda($sesion);
			$cobro_moneda->Load( $cobro['id_cobro'] );
			$query = "SELECT SQL_CALC_FOUND_ROWS * FROM cta_corriente 
								 WHERE id_cobro=".$cobro['id_cobro']." AND ( ingreso > 0 OR egreso > 0 ) AND cta_corriente.incluir_en_cobro='SI'
								 ORDER BY fecha ASC";
			$lista_gastos = new ListaGastos($sesion,'',$query);
			$saldo_gastos=0;
			
			$x_resultados = UtilesApp::ProcesaCobroIdMoneda($sesion, $cobro['id_cobro']);
			$saldo_monto=$x_resultados['monto'][$cobro['opc_moneda_total']];
			$saldo_honorarios=$x_resultados['monto_honorarios'][$cobro['opc_moneda_total']];
			$saldo_total_moneda_base=$cobro['total_moneda_base'];
			
			for($i=0;$i<$lista_gastos->num;$i++)
			{
				$gasto = $lista_gastos->Get($i);
				if($gasto->fields['egreso']>0)
				{
					$saldo_gastos += UtilesApp::CambiarMoneda($gasto->fields['monto_cobrable'], $cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'],  $cobro_moneda->moneda[$gasto->fields['id_moneda']]['cifras_decimales'], $cobro_moneda->moneda[$cobro['opc_moneda_total']]['tipo_cambio'],  $cobro_moneda->moneda[$cobro['opc_moneda_total']]['cifras_decimales']);
					//$saldo_gastos += $x_resultados['monto_cobrable'][$cobro['opc_moneda_total']];
					//$saldo_gastos += $gasto->fields['monto_cobrable'] * $cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$cobro['opc_moneda_total']]['tipo_cambio'];
				}
				else if($gasto->fields['ingreso']>0)
				{
					$saldo_gastos -= UtilesApp::CambiarMoneda($gasto->fields['monto_cobrable'], $cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'],  $cobro_moneda->moneda[$gasto->fields['id_moneda']]['cifras_decimales'], $cobro_moneda->moneda[$cobro['opc_moneda_total']]['tipo_cambio'],  $cobro_moneda->moneda[$cobro['opc_moneda_total']]['cifras_decimales']);
					//$saldo_gastos -= $x_resultados['monto_cobrable'][$cobro['opc_moneda_total']];
					//$saldo_gastos -= $gasto->fields['monto_cobrable'] * $cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$cobro['opc_moneda_total']]['tipo_cambio'];
				}
			}

			if(!$sin_area&&empty($cobro['id_area_proyecto']))
			{
				$filas += 2;
				$ws1->mergeCells($filas, 1, $filas, 3);
				$ws1->write($filas, $col_numero_factura,__('Sin Area'), $formato_encabezado);
				for($x=2;$x<4;$x++)
					$ws1->write($filas, $x, '', $formato_encabezado);
				$filas +=1;
				$ws1->write($filas, $col_numero_factura,__('N° Factura'),$formato_titulo);
				$ws1->write($filas, $col_fecha_creacion,__('Fecha Creación'),$formato_titulo);
				$ws1->write($filas, $col_cliente,__('Cliente'),$formato_titulo);
				$ws1->write($filas, $col_asunto,__('Asunto'),$formato_titulo);
				$ws1->write($filas, $col_encargado_comercial,__('Encargado Comercial'),$formato_titulo);
				$ws1->write($filas, $col_duracion_trabajada,__('Duración Trabajada'),$formato_titulo);
				$ws1->write($filas, $col_duracion_cobrada,__('Duración Cobrada'),$formato_titulo);
				$ws1->write($filas, $col_ingreso,__('Ingreso'),$formato_titulo);
				$ws1->write($filas, $col_ingreso_en_moneda_base,__('Ingreso en '.Moneda::GetGlosaPluralMonedaBase($sesion)),$formato_titulo);
				$ws1->write($filas, $col_gastos_en_moneda_base,__('Gastos'),$formato_titulo);
				$ws1->write($filas, $col_estado,__('Estado'),$formato_titulo);
				$ws1->write($filas, $col_forma_de_cobro,__('Forma de Cobro'),$formato_titulo);
				$ws1->write($filas, $col_numero_cobro,__('N° del Cobro'),$formato_titulo);
				$sin_area=true;
				$tabla_creada = true;
				$fila_inicial = $filas + 2;
			}
			if ($cobro['glosa']!=$area)
			{
				//Escribiendo el Total del Area
				if ($tabla_creada)
				{
					$filas +=1;
					$ws1->write($filas, $col_numero_factura,__('Total'), $formato_encabezado);
					$ws1->writeFormula($filas, $col_duracion_trabajada, "=SUM($col_formula_duracion_trabajada$fila_inicial:$col_formula_duracion_trabajada$filas)", $formato_tiempo);
					$ws1->writeFormula($filas, $col_duracion_cobrada, "=SUM($col_formula_duracion_cobrada$fila_inicial:$col_formula_duracion_cobrada$filas)", $formato_tiempo);
					$ws1->writeFormula($filas, $col_ingreso_en_moneda_base, "=SUM($col_formula_ingreso_en_moneda_base$fila_inicial:$col_formula_ingreso_en_moneda_base$filas)", $formatos_moneda[$moneda_base['id_moneda']]);
					$ws1->writeFormula($filas, $col_gastos_en_moneda_base, "=SUM($col_formula_gastos_en_moneda_base$fila_inicial:$col_formula_gastos_en_moneda_base$filas)", $formatos_moneda[$moneda_base['id_moneda']]);
					$area_total_moneda_base = 0;
					$area_total_cobros = 0;
					$total_horas = 0;
					$total_minutos = 0;
					$total_horas_trabajadas = 0;
					$total_minutos_trabajados = 0;
				}
				$filas += 2;
				$ws1->mergeCells($filas, 1, $filas, 3);
				$ws1->write($filas, $col_numero_factura,__('Area').' '.$cobro['glosa'], $formato_encabezado);
				for($x=2;$x<4;$x++)
					$ws1->write($filas, $x, '', $formato_encabezado);
				$filas +=1;
				
				$ws1->write($filas, $col_numero_factura,__('N° Factura'),$formato_titulo);
				$ws1->write($filas, $col_fecha_creacion,__('Fecha Creación'),$formato_titulo);
				$ws1->write($filas, $col_cliente,__('Cliente'),$formato_titulo);
				$ws1->write($filas, $col_asunto,__('Asunto'),$formato_titulo);
				$ws1->write($filas, $col_encargado_comercial,__('Abogado'),$formato_titulo);
				$ws1->write($filas, $col_duracion_trabajada,__('Duración Trabajada'),$formato_titulo);
				$ws1->write($filas, $col_duracion_cobrada,__('Duración Cobrada'),$formato_titulo);
				$ws1->write($filas, $col_ingreso,__('Ingreso'),$formato_titulo);
				$ws1->write($filas, $col_ingreso_en_moneda_base,__('Ingreso en '.Moneda::GetGlosaPluralMonedaBase($sesion)),$formato_titulo);
				$ws1->write($filas, $col_gastos_en_moneda_base,__('Gastos'),$formato_titulo);
				$ws1->write($filas, $col_estado,__('Estado'),$formato_titulo);
				$ws1->write($filas, $col_forma_de_cobro,__('Forma de Cobro'),$formato_titulo);
				$ws1->write($filas, $col_numero_cobro,__('N° del Cobro'),$formato_titulo);
				$tabla_creada = true;
				$fila_inicial = $filas + 2;
			}
			$area_total_cobros += $saldo_gastos;
			$area_total_moneda_base += $cobro['total_moneda_base'];
			$area=$cobro['glosa'];
			$filas +=1;
			$ws1->write($filas, $col_numero_factura,$cobro['numero'], $formato_texto_centrado);
			$ws1->write($filas, $col_fecha_creacion,Utiles::sql2date($cobro['fecha_creacion']),$formato_texto_centrado);
			$ws1->write($filas, $col_cliente,$cobro['glosa_cliente'],$formato_texto);
			$ws1->write($filas, $col_asunto,$cobro['glosa_asunto'],$formato_texto);
			$ws1->write($filas, $col_encargado_comercial,$cobro['nombre'],$formato_texto);
			$ws1->write($filas, $col_duracion_trabajada, str_replace(',', '.', $cobro['duracion_cobrada']/60/24), $formato_tiempo);
			$ws1->write($filas, $col_duracion_cobrada, str_replace(',', '.', $cobro['duracion']/60/24), $formato_tiempo);
			$valor_estimado = $cobro['monto_proporcional'];
			$ws1->writeNumber($filas, $col_ingreso,$valor_estimado,$formatos_moneda[$cobro['id_moneda']]);
			$ws1->writeNumber($filas, $col_ingreso_en_moneda_base,$saldo_total_moneda_base,$formatos_moneda[$moneda_base['id_moneda']]);
			$ws1->writeNumber($filas, $col_gastos_en_moneda_base,$saldo_gastos,$formatos_moneda[$moneda_base['id_moneda']]);
			$ws1->write($filas, $col_estado,$cobro['estado'],$formato_texto_centrado);
			$ws1->write($filas, $col_forma_de_cobro,$cobro['forma_cobro'],$formato_texto_centrado);
			$ws1->write($filas, $col_numero_cobro, $cobro['id_cobro'] ,$formato_texto_centrado);
		}
		if ($tabla_creada)
		{
			$filas +=1;
			$ws1->write($filas, $col_numero_factura,__('Total'), $formato_encabezado);
			$ws1->writeFormula($filas, $col_duracion_trabajada, "=SUM($col_formula_duracion_trabajada$fila_inicial:$col_formula_duracion_trabajada$filas)", $formato_tiempo);
			$ws1->writeFormula($filas, $col_duracion_cobrada, "=SUM($col_formula_duracion_cobrada$fila_inicial:$col_formula_duracion_cobrada$filas)", $formato_tiempo);
			$ws1->writeFormula($filas, $col_ingreso_en_moneda_base, "=SUM($col_formula_ingreso_en_moneda_base$fila_inicial:$col_formula_ingreso_en_moneda_base$filas)", $formatos_moneda[$moneda_base['id_moneda']]);
			$ws1->writeFormula($filas, $col_gastos_en_moneda_base, "=SUM($col_formula_gastos_en_moneda_base$fila_inicial:$col_formula_gastos_en_moneda_base$filas)", $formatos_moneda[$moneda_base['id_moneda']]);		}
		else
		{
			$ws1->mergeCells($filas, 1, $filas, 3);
			$ws1->write($filas, $col_numero_factura,__('No se encontraron resultados'), $formato_encabezado);
			for($x=2;$x<4;$x++)
				$ws1->write($filas, $x, '', $formato_encabezado);
		}
		$wb->send("planilla cobros area.xls");
		$wb->close();
		exit;
	}
	$pagina->titulo = __('Reporte Cobros por Area');
	$pagina->PrintTop();
	$hoy = date("Y-m-d");
?>
<form method=post name=formulario action="planilla_cobros_por_area.php?xls=1">
<table width="90%"><tr><td>
	<fieldset class="border_plomo tb_base">
	<legend>
	<?=__('Filtros')?>
	</legend>
	<table style=" width: 90%;" cellpadding="4">
		<tr>
			<td align=right >
				<?=__('Fecha desde')?>:
			</td>
			<td align=left>
				<!--/<?= Html::PrintCalendar("fecha1", "$fecha1"); ?>-->
				<input type="text" name="fecha1" value="<?= date("d-m-Y",strtotime("$hoy - 1 month")) ?>" id="fecha1" size="11" maxlength="10" />
					<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha_ini" style="cursor:pointer" />
			</td>
		</tr>
		<tr>
			<td align=right >
				<?=__('Fecha hasta')?>:
			</td>
			<td align=left>
				<!--<?= Html::PrintCalendar("fecha2", "$fecha2"); ?>-->
				<input type="text" name="fecha2" value="<?= date("d-m-Y",strtotime("$hoy")) ?>" id="fecha2" size="11" maxlength="10" />
					<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha_fin" style="cursor:pointer" />
			</td>
		</tr>
		<tr>
			<td align=right >
				<?=__('Estado del Cobro')?>:
			</td>
			<td align=left>
				<select name="estado" id="estado" >
					<option value="todos" ><?=__('Todos') ?></option>
					<option value="creado" ><?=__('Creado') ?></option>
					<option value="en_revision" ><?=__('En Revisión') ?></option>
					<option value="emitido" ><?=__('Emitido') ?></option>
					<option value="facturado"><?=__('Facturado') ?></option>
					<!--<option value="enviado"><?=__('Facturado') ?></option>-->
					<option value="enviado" ><?=__('Enviado al Cliente') ?></option>
					<option value="pago_parcial"><?=__('Pago Parcial') ?></option>
					<option value="pagado" ><?=__('Pagado') ?></option>
				</select>
			</td>
		</tr>

		<tr>
			<td align=right>
				<?=__("Encargado")?>:
			</td>
			<td align=left>
				<?=Html::SelectQuery($sesion,"SELECT usuario.id_usuario, CONCAT_WS(' ',usuario.apellido1,usuario.apellido2,',',usuario.nombre) AS nombre FROM usuario JOIN usuario_permiso USING(id_usuario) WHERE usuario_permiso.codigo_permiso='PRO' ORDER BY nombre ASC", "usuarios[]",$usuarios,"class=\"selectMultiple\" multiple size=6 ","","200"); ?>
			</td>
		</tr>
		<tr>
			<td align=center colspan=2>
				<input type="submit" class=btn value="<?=__('Generar reporte')?>" name="btn_reporte">
			</td>
		</tr>
	</table>
	</fieldset>
</td></tr></table>
</form>


	<script>
	Calendar.setup(
		{
			inputField	: "fecha1",				// ID of the input field
			ifFormat		: "%d-%m-%Y",			// the date format
			button			: "img_fecha_ini"		// ID of the button
		}
	);
	Calendar.setup(
		{
			inputField	: "fecha2",				// ID of the input field
			ifFormat		: "%d-%m-%Y",			// the date format
			button			: "img_fecha_fin"		// ID of the button
		}
	);
	</script>
<?
	$pagina->PrintBottom();
?>
