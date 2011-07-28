<?
	require_once 'Spreadsheet/Excel/Writer.php';
	require_once dirname(__FILE__).'/../../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
	require_once Conf::ServerDir().'/classes/Cobro.php';

	$sesion = new Sesion(array('REP'));
	//Revisa el Conf si esta permitido
	
	set_time_limit(300);
	
	$pagina = new Pagina($sesion);
	/*Genera Excel*/
	if($xls)
	{
		$filas = 0;
		$espacio = 3;
		
		#ARMANDO XLS
		$wb = new Spreadsheet_Excel_Writer();
		
		$wb->setCustomColor(35, 220, 255, 220);
		$wb->setCustomColor(36, 255, 255, 220);
		$wb->setCustomColor(37, 220, 220, 220);

		$encabezado =& $wb->addFormat(array('Size' => 12,
									'VAlign' => 'top',
									'Align' => 'left',
									'Bold' => '1',
									'underline' =>1,
									'Color' => 'black'));
		$txt =& $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'left',
									'Color' => 'black',
									'TextWrap' => 1));
		$txt_right =& $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'right',
									'Color' => 'black',
									'TextWrap' => 1));
		$txt_opcion =& $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'left',
									'Border' => 1,
									'Color' => 'black',
									'TextWrap' => 1));
		$txt_opcion_right =& $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Color' => 'black',
									'TextWrap' => 1));
		$txt_valor =& $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Color' => 'black',
									'TextWrap' => 1));
		$formato_nulo = $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Color' => 'black',
									'FgColor' => 37,
									'TextWrap' => 1));
		$formato_grafico_monto = $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Color' => 'black',
									'FgColor' => 'green',
									'TextWrap' => 1));
		$formato_grafico_monto_sobrepasado = $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Color' => 'black',
									'FgColor' => 'red',
									'TextWrap' => 1));
		$formato_grafico_alerta = $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Color' => 'black',
									'FgColor' => 'yellow',
									'TextWrap' => 1));
		$txt_centro =& $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'center',
									'Border' => 1,
									'Color' => 'black',
									'TextWrap' => 1));
		$txt_izquierda =& $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'left',
									'Border' => 1,
									'Color' => 'black',
									'TextWrap' => 1));
		$fecha =& $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'center',
									'Border' => 1,
									'Color' => 'black',
									'TextWrap' => 1));
		$numeros =& $wb->addFormat(array('Size' => 11,
									'VAlign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Color' => 'black',
									'NumFormat' => '0'));
		$numeros_alerta =& $wb->addFormat(array('Size' => 11,
									'VAlign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Color' => 'black',
									'FgColor' => 'red',
									'NumFormat' => '0'));
		$titulo_filas =& $wb->addFormat(array('Size' => 12,
									'Align' => 'center',
									'Bold' => '1',
									'FgColor' => '35',
									'Border' => 1,
									'Locked' => 1,
									'Color' => 'black'));
		$formato_moneda =& $wb->addFormat(array('Size' => 11,
									'VAlign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Color' => 'black',
									'NumFormat' => "#,##0.00"));
		$formato_moneda_alerta =& $wb->addFormat(array('Size' => 11,
									'VAlign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Color' => 'black',
									'FgColor' => 'red',
									'NumFormat' => "#,##0.00"));
		$porcentaje = $wb->addFormat(array('Size' => 11,
									'VAlign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Color' => 'black'));
		$porcentaje->setNumFormat("0%;[Red]-0%");

		$ws1 =& $wb->addWorksheet(__('Alertas'));
		$ws1->setInputEncoding('utf-8');
		$ws1->fitToPages(1, 5);
		$ws1->setZoom(75);
		$ws1->hideGridlines();
		$ws1->setLandscape();

		// Definir columnas a usar
		$indice_columnas = 0;

		$col_cliente = $indice_columnas++;
		$col_contrato = $indice_columnas++;
		$col_asuntos = $indice_columnas++;
		$col_encargado_comercial = $indice_columnas++;

		//Cada valor, y despues su alerta
		$col_glosa_alerta = $indice_columnas++;
		$col_porcentaje = $indice_columnas++;
		
		$col_grafico_alerta = array();
		$grafico = 20;
		for($i=0;$i<$grafico;$i++)
			$col_grafico_alerta[$i] = $indice_columnas++;
		$col_grafico = $col_grafico_alerta[0];

		$col_deglose = $indice_columnas++;
		$col_monto = $indice_columnas++;
		$col_tipo = $indice_columnas++;
		
		//Fin definición columnas
		unset($indice_columnas);

		// Definir los anchos de las columnas
		function setCol($col,$ancho)
		{
			global $ws1;
			$ws1->setColumn($col,$col,$ancho);	
		}
		// Poner un Header de tabla
		function setHead($fila,$col,$txt)
		{
			global $ws1, $titulo_filas;
			$ws1->write($fila,$col,$txt,$titulo_filas);
		}
		function superHeader($fila,$col_ini,$col_fin,$txt,$formato)
		{
			global $ws1;
			$ws1->write($fila,$col_ini,$txt,$formato);
			for($i=$col_ini+1; $i<=$col_fin; $i++)
				$ws1->write($fila,$i,'',$formato);
			$ws1->mergeCells($fila, $col_ini, $fila, $col_fin);		
		}

		setCol($col_cliente,30);
		setCol($col_contrato,12);
		setCol($col_asuntos,28);
		setCol($col_encargado_comercial,24);
		setCol($col_glosa_alerta,24);
		for($i=0;$i<$grafico;$i++)
			setCol($col_grafico_alerta[$i],2);

		setCol($col_deglose,18);
		setCol($col_monto,15);
		setCol($col_tipo,6);
		setCol($col_porcentaje,12);


		$ws1->mergeCells($filas, $col_cliente, $filas, $col_factura);
		$ws1->write($filas, $col_cliente, __('REPORTE ALERTAS'), $encabezado);
		++$filas;
		$ws1->write($filas, $col_cliente+1, __('GENERADO EL:'), $txt_opcion);
		$ws1->write($filas, $col_contrato+1, date("d-m-Y H:i:s"), $txt_opcion);


		$filas++;

		//Headers
		setHead($filas,$col_cliente,__('Cliente'));
		setHead($filas,$col_contrato,__('Contrato'));
		setHead($filas,$col_asuntos,__('Asuntos'));
		setHead($filas,$col_encargado_comercial,__('Encargado Comercial'));
		setHead($filas,$col_glosa_alerta,__('Alerta'));
		superHeader($filas,$col_grafico_alerta[0],$col_grafico_alerta[$grafico-1],__('Avance'),$titulo_filas);
		superHeader($filas,$col_deglose,$col_tipo,__('Valor'),$titulo_filas);
		setHead($filas,$col_porcentaje,__('%'));

		$ws1->freezePanes(array(3, 0, 3, 0));
		
		//Query de contratos
		$where = "1";
		if(is_array($clientes))
		{
			$lista_clientes = join("','", $clientes);
			$where .= " AND cliente.codigo_cliente IN ('$lista_clientes')";
		}
		if(is_array($socios))
		{
			$lista_socios = join("','", $socios);
			$where .= " AND contrato.id_usuario_responsable IN ('$lista_socios')";
		}
		if(is_array($grupos))
		{
			$lista_grupos = join("','", $grupos);
			$where .= " AND cliente.id_grupo_cliente IN ('$lista_grupos')";
		}

		$where_alerta = '';
		if($filtrar_contrato != 'todo')
			$where_alerta = ' AND (contrato.limite_monto > 0 OR contrato.limite_hh > 0 OR contrato.alerta_hh > 0 OR contrato.alerta_monto > 0)';

		++$filas;
		$query = "SELECT contrato.id_contrato,
				contrato.forma_cobro,
				usuario.id_usuario,
				usuario.username,
				cliente.glosa_cliente,
				cliente.codigo_cliente,
				GROUP_CONCAT(asunto.glosa_asunto SEPARATOR '\\n') as asuntos
		FROM contrato
		JOIN usuario ON (contrato.id_usuario_responsable = usuario.id_usuario)
		JOIN cliente ON (contrato.codigo_cliente = cliente.codigo_cliente)
		JOIN asunto ON (asunto.id_contrato = contrato.id_contrato)
		WHERE $where AND contrato.activo = 'SI' AND cliente.activo = '1'
		$where_alerta
		GROUP BY contrato.id_contrato
		ORDER BY cliente.glosa_cliente";

		$tipo_alerta = array();
		
		//Aqui guardo cada fila de los resultados.
		$res = array();
		function addAlerta(&$res,$codigo_cliente,$glosa_cliente,$id_contrato,$contrato,$asuntos,$encargado,$alerta)
		{
			global $espacio;
			if($alerta==null)
				return 0;

			//Creo el cliente
			if(!isset($res[$codigo_cliente]))
				$res[$codigo_cliente] = array('glosa'=>$glosa_cliente,'filas'=>0,'contratos'=>array());
			if(!isset($res[$codigo_cliente]['contratos'][$id_contrato]))
				$res[$codigo_cliente]['contratos'][$id_contrato] = array('glosa'=>$contrato,'asuntos'=>$asuntos,'encargado'=>$encargado,'filas'=>0,'alertas'=>array());
	
			$res[$codigo_cliente]['contratos'][$id_contrato]['alertas'][] = $alerta;
			//Cada fila ocupa $espacio filas
			$res[$codigo_cliente]['filas'] += $espacio;
			$res[$codigo_cliente]['contratos'][$id_contrato]['filas']+= $espacio;
		}
		function newAlerta($tipo,$monto,$tope,$simbolo)
		{
			global $grafico,$filtrar_contrato;
			
			if($filtrar_contrato == 'en_alerta')
				if($tope > $monto)
					return null;

			$alerta = array();
			$alerta['glosa'] = $tipo;
			$alerta['monto'] = $monto;
			$alerta['tope'] = $tope;
			$alerta['simbolo'] = $simbolo;

			if($monto > $tope)
			{
				$alerta['grafico_monto'] = $grafico;
				$alerta['grafico_tope'] = ceil( $tope * $grafico / $monto);
			}
			else
			{
				$alerta['grafico_monto'] = ceil( $monto * $grafico / $tope);
				$alerta['grafico_tope'] = $grafico;
			}
			return $alerta;
		}

		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		while($row = mysql_fetch_array($resp))
		{
			$en_alerta = false;	
			#Carga de datos:
				$contrato = new Contrato($sesion);
				$contrato->Load($row['id_contrato']);

				// Los cuatro límites: monto desde siempre, horas desde siempre, horas no emitidas, monto no emitido. 
				list($total_monto,$moneda_total_monto) =  $contrato->TotalMonto();
				$total_horas_trabajadas = $contrato->TotalHoras();
				$total_horas_ult_cobro =  $contrato->TotalHoras(false);
				list($total_monto_ult_cobro,$moneda_desde_ult_cobro) =  $contrato->TotalMonto(false);	
		

				if($contrato->fields['limite_hh'] > 0 )
				{
					$alerta = newAlerta('Horas Totales',$total_horas_trabajadas,$contrato->fields['limite_hh'],'Hrs.');
					addAlerta($res,$row['codigo_cliente'],$row['glosa_cliente'],$row['id_contrato'],$row['forma_cobro'],$row['asuntos'],$row['username'],$alerta);
				}

				if($contrato->fields['limite_monto'] > 0)
				{
					$alerta = newAlerta('Monto Total',$total_monto,$contrato->fields['limite_monto'],$moneda_total_monto);
					addAlerta($res,$row['codigo_cliente'],$row['glosa_cliente'],$row['id_contrato'],$row['forma_cobro'],$row['asuntos'],$row['username'],$alerta);
				}

				if($contrato->fields['alerta_hh'] > 0 )
				{
					$alerta = newAlerta('Horas por Cobrar',$total_horas_ult_cobro,$contrato->fields['alerta_hh'],'Hrs.');
					addAlerta($res,$row['codigo_cliente'],$row['glosa_cliente'],$row['id_contrato'],$row['forma_cobro'],$row['asuntos'],$row['username'],$alerta);
				}

				if(($total_monto_ult_cobro > $contrato->fields['alerta_monto']) && ($contrato->fields['alerta_monto'] > 0))
				{
					$alerta = newAlerta('Monto por Cobrar',$total_horas_ult_cobro,$contrato->fields['alerta_hh'],$moneda_desde_ult_cobro);
					addAlerta($res,$row['codigo_cliente'],$row['glosa_cliente'],$row['id_contrato'],$row['forma_cobro'],$row['asuntos'],$row['username'],$alerta);
				}
		}

		foreach($res as $cliente)
		{
			$ws1->write($filas,$col_cliente,$cliente['glosa'],$txt_opcion);
			for($i = 1; $i < $cliente['filas'];$i++)
				$ws1->write($filas+$i,$col_cliente,'',$txt_opcion);
			$ws1->mergeCells($filas,$col_cliente,$filas+$cliente['filas']-1,$col_cliente);
		
			$filas_contrato = $filas;
			foreach($cliente['contratos'] as $contrato)
			{	
				$ws1->write($filas_contrato,$col_contrato,$contrato['glosa'],$txt_opcion);
				$ws1->write($filas_contrato,$col_asuntos,$contrato['asuntos'],$txt_opcion);
				$ws1->write($filas_contrato,$col_encargado_comercial,$contrato['encargado'],$txt_opcion);
				for($i = 1; $i < $contrato['filas'];$i++)
				{
					$ws1->write($filas_contrato+$i,$col_contrato,'',$txt_opcion);
					$ws1->write($filas_contrato+$i,$col_asuntos,'',$txt_opcion);
					$ws1->write($filas_contrato+$i,$col_encargado_comercial,'',$txt_opcion);
				}
				
				foreach($contrato['alertas'] as $j => $alerta)
				{
					$ws1->write($filas_contrato+$j*$espacio,$col_glosa_alerta,$alerta['glosa'],$txt_opcion);
					$ws1->write($filas_contrato+$j*$espacio+1,$col_glosa_alerta,'',$txt_opcion);
					$ws1->write($filas_contrato+$j*$espacio+2,$col_glosa_alerta,'',$txt_opcion);
					$ws1->mergeCells($filas_contrato+$j*$espacio,$col_glosa_alerta,$filas_contrato+$j*$espacio+2,$col_glosa_alerta);


					$ws1->write($filas_contrato+$j*$espacio,$col_porcentaje,number_format($alerta['monto']/$alerta['tope'],2,'.',''),$porcentaje);
					$ws1->write($filas_contrato+$j*$espacio+1,$col_porcentaje,'',$txt_opcion);
					$ws1->write($filas_contrato+$j*$espacio+2,$col_porcentaje,'',$txt_opcion);
					$ws1->mergeCells($filas_contrato+$j*$espacio,$col_porcentaje,$filas_contrato+$j*$espacio+2,$col_porcentaje);
				
				
					//Grafico monto
					$f = $formato_grafico_monto;
					if($alerta['monto'] > $alerta['tope'])
						$f = $formato_grafico_monto_sobrepasado;
					for($k = 0; $k<$alerta['grafico_monto'];$k++)
						$ws1->write($filas_contrato+$j*$espacio,$col_grafico+$k,'',$f);
					if($alerta['grafico_monto'])
						$ws1->mergeCells($filas_contrato+$j*$espacio,$col_grafico,$filas_contrato+$j*$espacio,$col_grafico+$k-1);
					
					//Grafico alerta
					for($k = 0; $k<$alerta['grafico_tope'];$k++)
						$ws1->write($filas_contrato+$j*$espacio+1,$col_grafico+$k,'',$formato_grafico_alerta);
					if($alerta['grafico_tope'])
						$ws1->mergeCells($filas_contrato+$j*$espacio+1,$col_grafico,$filas_contrato+$j*$espacio+1,$col_grafico+$k-1);
					
					//Deglose
					$ws1->write($filas_contrato+$j*$espacio,$col_deglose,__('Ingresado:'),$txt_right);
					$ws1->write($filas_contrato+$j*$espacio,$col_monto,$alerta['monto'],$formato_moneda);
					$ws1->write($filas_contrato+$j*$espacio,$col_tipo,$alerta['simbolo'],$txt);
					
					$ws1->write($filas_contrato+$j*$espacio+1,$col_deglose,__('Tope:'),$txt_right);
					$ws1->write($filas_contrato+$j*$espacio+1,$col_monto,$alerta['tope'],$formato_moneda);
					$ws1->write($filas_contrato+$j*$espacio+1,$col_tipo,$alerta['simbolo'],$txt);
					
					//Tercera fila: comentar si $espacio = 2
					$ws1->write($filas_contrato+$j*$espacio+2,$col_deglose,'',$txt);
					$ws1->write($filas_contrato+$j*$espacio+2,$col_monto,'',$txt);
					$ws1->write($filas_contrato+$j*$espacio+2,$col_tipo,'',$txt);
					
				}
				$ws1->mergeCells($filas_contrato,$col_contrato,$filas_contrato+$contrato['filas']-1,$col_contrato);	
				$ws1->mergeCells($filas_contrato,$col_asuntos,$filas_contrato+$contrato['filas']-1,$col_asuntos);			
				$ws1->mergeCells($filas_contrato,$col_encargado_comercial,$filas_contrato+$contrato['filas']-1,$col_encargado_comercial);			

				$filas_contrato += $contrato['filas'];	
			}
			$filas += $cliente['filas'];
		}

		$wb->send("planilla_alertas.xls");
		$wb->close();
		exit;
	}
	//FIN if(xls)
	
	//Pagina
	$pagina->titulo = __('Reporte de Alertas');
	$pagina->PrintTop();
?>
<form method=post name=formulario action="planilla_alertas_graficas.php?xls=1">
<table class="border_plomo tb_base" width="450px" align="center">
	<tr valign=top>
		<td align=left colspan=2>
			<b><?=__('Clientes')?>:</b>
		</td>
	</tr>
	<tr valign=top>
		<td align=center colspan=2>
			<?=Html::SelectQuery($sesion, "SELECT codigo_cliente, glosa_cliente AS nombre FROM cliente WHERE activo=1 ORDER BY nombre ASC", "clientes[]", $clientes, "class=\"selectMultiple\" multiple size=5 ", "", "200"); ?>
		</td>
	</tr>
	<tr valign=top>
		<td align=left colspan=2>
			<b><?=__('Grupos Clientes')?>:</b>
		</td>
	</tr>
	<tr valign=top>
		<td align=center colspan=2>
			<?=Html::SelectQuery($sesion, "SELECT id_grupo_cliente, glosa_grupo_cliente FROM grupo_cliente", "grupos[]", $grupos, "class=\"selectMultiple\" multiple size=4 ", "", "200"); ?>
		</td>
	</tr>
	<tr valign=top>
		<td align=left colspan=2>
			<b><?=__('Encargados Comerciales')?>:</b>
		</td>
	</tr>
	<tr>
		<td align=center colspan=2>
			<?=Html::SelectQuery($sesion, "SELECT usuario.id_usuario, CONCAT_WS(' ', apellido1, apellido2, ', ', nombre)
				FROM usuario JOIN usuario_permiso USING(id_usuario)
				WHERE codigo_permiso='SOC' ORDER BY apellido1", "socios[]", $socios, "class=\"selectMultiple\" multiple size=5 ", "", "200"); ?>
		</td>
	</tr>
	<tr valign=top>
		<td align=left colspan=2>
			<b><?=__('Buscar')?>:</b>
		</td>
	</tr>
	<tr>
		<td align=right>
			<input type="radio" name="filtrar_contrato" id="con_alerta" value="con_alerta" checked="checked" />
		</td>
		<td align=left>
			<label for="con_alerta"><?=__("Ver todas las Alertas de Acuerdos comerciales")?></label>
		</td>
	</tr>
	<tr>
		<td align=right>
			<input type="radio" name="filtrar_contrato" id="en_alerta" value="en_alerta"/>
		</td>
		<td align=left>
			<label for="en_alerta"><?=__("Ver Alertas sobrepasadas de Acuerdos comerciales")?></label>
		</td>
	</tr>
	<tr>
		<td align=right>
			&nbsp;
		</td>
	</tr>
	<tr>
		<td align=right>
			<input type=submit class=btn value="<?=__('Generar planilla')?>" />
		</td>
	</tr>
</table>
</form>
<?
	echo(InputId::Javascript($sesion));
	$pagina->PrintBottom();
?>