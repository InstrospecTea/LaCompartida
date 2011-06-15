<?
	require_once 'Spreadsheet/Excel/Writer.php';
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
	require_once Conf::ServerDir().'/classes/Trabajo.php';

	$sesion = new Sesion(array('REP'));
	//Revisa el Conf si esta permitido
	
	$pagina = new Pagina($sesion);
	$fecha_desde = $fecha_anio_desde.'-'.sprintf('%02d',$fecha_mes_desde).'-01';
	$fecha_hasta = $fecha_anio_hasta.'-'.sprintf('%02d',$fecha_mes_hasta).'-31';

	/*if($opcion == "xls")
	{
		$total_tiempo = 0;
		$where = 1;
		$join = "";
		//segun el tipo de duracion se ven los parametros de la duracion
		if($tipo_duracion == 'trabajada')
		{
			$duracion_query = "trabajo.duracion";
		}
		if($tipo_duracion == 'cobrable')
		{
			$duracion_query = "trabajo.duracion_cobrada";
			$where .= " AND trabajo.cobrable=1";
		}
		if($tipo_duracion == 'cobrada')
		{
			$duracion_query = "trabajo.duracion_cobrada";
			$join = "INNER JOIN cobro ON cobro.id_cobro=trabajo.id_cobro";
			$where .= " AND trabajo.cobrable=1 AND cobro.estado <> 'CREADO' AND cobro.estado <> 'EN REVISION'";
		}
		//segun el tipo de reporte se ven los parametros del query
		if($tipo_reporte != 'trabajos_por_estudio')
		{
			//si no es por estudio se agrupa y se acota por cliente o por empleado
			if($tipo_reporte == 'trabajos_por_cliente')
			{
				if(!empty($codigo_cliente))
					$where .= " AND cliente.codigo_cliente=$codigo_cliente";
				if(is_array($usuarios))
				{
					$lista_usuarios = join("','",$usuarios);
					$where_usuario = " AND trabajo.id_usuario IN ('".$lista_usuarios."')";
				}
				else
					$where_usuario = '';
			}

			if($tipo_reporte == 'trabajos_por_empleado')
			{
				if(!empty($id_usuario))
					$where .= " AND usuario.id_usuario=$id_usuario";
				if(is_array($clientes))
				{
					$lista_clientes = join("','",$clientes);
					$where_cliente = "	AND asunto.codigo_cliente IN ('".$lista_clientes."')";
				}
				else
					$where_cliente = '';
			}
			$nombre_general = "meses_duracion.nombre,";
		}
		else
		{
			if(is_array($usuarios))
			{
				$lista_usuarios = join("','",$usuarios);
				$where_usuario = " AND trabajo.id_usuario IN ('".$lista_usuarios."')";
			}
			else
				$where_usuario = '';

			if(is_array($clientes))
			{
				$lista_clientes = join("','",$clientes);
				$where_cliente = "	AND asunto.codigo_cliente IN ('".$lista_clientes."')";
			}
			else
				$where_cliente = '';
		}
		#ARMANDO XLS
		$wb = new Spreadsheet_Excel_Writer();

		$wb->send("planilla horas.xls");

		$wb->setCustomColor ( 35, 220, 255, 220 );

		$wb->setCustomColor ( 36, 255, 255, 220 );

		$encabezado =& $wb->addFormat(array('Size' => 12,
									'VAlign' => 'top',
									'Align' => 'left',
									'Bold' => '1',
									'underline'=>1,
									'Color' => 'black'));

		$txt_opcion =& $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'left',
									'Border' => 1,
									'Color' => 'black'));
		$txt_opcion->setTextWrap();

		$txt_valor =& $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Color' => 'black'));
		$txt_valor->setTextWrap();

		$txt_centro =& $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'center',
									'Border' => 1,
									'Color' => 'black'));
		$txt_centro->setTextWrap();

		$fecha =& $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'center',
									'Border' => 1,
									'Color' => 'black'));
		$fecha->setTextWrap();

		$numeros =& $wb->addFormat(array('Size' => 11,
									'VAlign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Color' => 'black'));
		$numeros->setNumFormat("0");

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
										'Color' => 'black'));
		$formato_moneda->setNumFormat("#,##0.00");

		$ws1 =& $wb->addWorksheet(__('Horas'));
		$ws1->setInputEncoding('utf-8');
		$ws1->fitToPages(1,5);
		$ws1->setZoom(100);
		$ws1->hideGridlines();
		$ws1->setLandscape();
		$ws1->setColumn( 1, 1, 17.00);
		$ws1->setColumn( 1, 2, 35.00);
		$ws1->setColumn( 1, 3, 35.00);
		$ws1->setColumn( 1, 4, 35.00);

		$filas += 1;
		$ws1->mergeCells( $filas, 1, $filas, 13 );
		$ws1->write($filas, 1, __('REPORTE HORAS ').strtoupper($tipo_duracion), $encabezado);
		for($x=2;$x<14;$x++)
			$ws1->write($filas, $x, '', $encabezado);
		$filas +=2;
		$ws1->write($filas,1,__('GENERADO EL:'),$txt_opcion);
		$ws1->write($filas,2,date("d-m-Y H:i:s"),$txt_opcion);

		$filas +=4;
		$query  ="SELECT DATE_FORMAT(fechas.mes,'%m-%Y') as mes_anio,IFNULL(meses_duracion.duracion,0) as duracion_mes
							FROM (SELECT DATE_SUB(DATE_FORMAT(NOW(),'%Y-%m-01'),INTERVAL n2.num+n1.num MONTH) AS 'mes'
								FROM (SELECT 0 AS num UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3
											UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL
											SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) n1,
										 (SELECT 0 AS num UNION ALL SELECT 10 UNION ALL SELECT 20 UNION ALL SELECT 30
											UNION ALL SELECT 40 UNION ALL SELECT 50 UNION ALL SELECT 60 UNION ALL
											SELECT 70 UNION ALL SELECT 80 UNION ALL SELECT 90) n2
								WHERE DATE_SUB(DATE_FORMAT(NOW(),'%Y-%m-01'),INTERVAL n2.num+n1.num MONTH) >='$fecha_desde'
									AND DATE_SUB(DATE_FORMAT(NOW(),'%Y-%m-01'),INTERVAL n2.num+n1.num MONTH) <='$fecha_hasta'
								ORDER BY 'mes') as fechas
							LEFT JOIN (SELECT SUM( TIME_TO_SEC( $duracion_query ) ) /3600 AS duracion,
								DATE_FORMAT( trabajo.fecha,  '%Y-%m-01' ) AS periodo
								FROM trabajo
								JOIN usuario ON usuario.id_usuario=trabajo.id_usuario
								JOIN asunto ON asunto.codigo_asunto = trabajo.codigo_asunto
								JOIN cliente ON cliente.codigo_cliente = asunto.codigo_cliente
								$join
								WHERE $where $where_usuario $where_cliente AND trabajo.fecha
								BETWEEN '$fecha_desde'
								AND '$fecha_hasta'
								GROUP BY YEAR( trabajo.fecha ) , MONTH( trabajo.fecha ) $groupby
								ORDER BY YEAR( trabajo.fecha ) , MONTH( trabajo.fecha )) as meses_duracion ON meses_duracion.periodo=fechas.mes";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		while($cobro = mysql_fetch_array($resp))
		{
			//Se llena la información del excel
		}
		$wb->close();
		exit;
	}*/
	$pagina->titulo = __('Reporte Gráfico por Período');
	$pagina->PrintTop();
?>
<script type="text/javascript">
function Validar(form,opc)
{
	form.opcion.value=opc;
	if(form.tipo_reporte.selectedIndex == 2)
	{
		var selectedArray = new Array();
		var selObj = $('clientes[]');
		var count = 0;
		for (i=0; i<selObj.options.length; i++)
		{
			if (selObj.options[i].selected)
			{
				count++;
			}
		}
		if(count == 0)
		{
			alert('Debe seleccionar un cliente.');
			return false;
		}
	}
	else if(form.tipo_reporte.selectedIndex == 1)
	{
		var selectedArray = new Array();
		var selObj = $('usuarios[]');
		var count = 0;
		for (i=0; i<selObj.options.length; i++)
		{
			if (selObj.options[i].selected)
			{
				count++;
			}
		}
		if(count == 0)
		{
			alert('Debe seleccionar un profesional.');
			return false;
		}
	}
	return form.submit();
}
</script>
<form method='post' name='formulario'>
<input type=hidden name=opcion value="desplegar" >

<table class="border_plomo tb_base">
	<tr>
		<td align=right>
			<?=__('Periodo')?>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Desde')?>
		</td>
		<td align=left>
			<?
				$fecha_mes_desde = $fecha_mes_desde != '' ? $fecha_mes_desde : date('m')-1;
			?>
			<select name="fecha_mes_desde" style='width:60px' id="fecha_mes_desde">
				<option value='1' <?=$fecha_mes_desde==1 ? 'selected':'' ?>><?=__('Enero') ?></option>
				<option value='2' <?=$fecha_mes_desde==2 ? 'selected':'' ?>><?=__('Febrero') ?></option>
				<option value='3' <?=$fecha_mes_desde==3 ? 'selected':'' ?>><?=__('Marzo') ?></option>
				<option value='4' <?=$fecha_mes_desde==4 ? 'selected':'' ?>><?=__('Abril') ?></option>
				<option value='5' <?=$fecha_mes_desde==5 ? 'selected':'' ?>><?=__('Mayo') ?></option>
				<option value='6' <?=$fecha_mes_desde==6 ? 'selected':'' ?>><?=__('Junio') ?></option>
				<option value='7' <?=$fecha_mes_desde==7 ? 'selected':'' ?>><?=__('Julio') ?></option>
				<option value='8' <?=$fecha_mes_desde==8 ? 'selected':'' ?>><?=__('Agosto') ?></option>
				<option value='9' <?=$fecha_mes_desde==9 ? 'selected':'' ?>><?=__('Septiembre') ?></option>
				<option value='10' <?=$fecha_mes_desde==10 ? 'selected':'' ?>><?=__('Octubre') ?></option>
				<option value='11' <?=$fecha_mes_desde==11 ? 'selected':'' ?>><?=__('Noviembre') ?></option>
				<option value='12' <?=$fecha_mes_desde==12 ? 'selected':'' ?>><?=__('Diciembre') ?></option>
			</select>
			<?
				if(!$fecha_anio_desde)
					$fecha_anio_desde = date('Y');
			?>
			<select name="fecha_anio_desde" style='width:55px' id="fecha_anio_desde">
				<? for($i=(date('Y')-5);$i <= date('Y');$i++){ ?>
				<option value='<?=$i?>' <?=$fecha_anio_desde == $i ? 'selected' : '' ?>><?=$i ?></option>
				<? } ?>
			</select>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Hasta')?>
		</td>
		<td align=left>
			<?
				$fecha_mes_hasta = $fecha_mes_hasta != '' ? $fecha_mes_hasta : date('m');
			?>
			<select name="fecha_mes_hasta" style='width:60px' id="fecha_mes_hasta">
				<option value='1' <?=$fecha_mes_hasta==1 ? 'selected':'' ?>><?=__('Enero') ?></option>
				<option value='2' <?=$fecha_mes_hasta==2 ? 'selected':'' ?>><?=__('Febrero') ?></option>
				<option value='3' <?=$fecha_mes_hasta==3 ? 'selected':'' ?>><?=__('Marzo') ?></option>
				<option value='4' <?=$fecha_mes_hasta==4 ? 'selected':'' ?>><?=__('Abril') ?></option>
				<option value='5' <?=$fecha_mes_hasta==5 ? 'selected':'' ?>><?=__('Mayo') ?></option>
				<option value='6' <?=$fecha_mes_hasta==6 ? 'selected':'' ?>><?=__('Junio') ?></option>
				<option value='7' <?=$fecha_mes_hasta==7 ? 'selected':'' ?>><?=__('Julio') ?></option>
				<option value='8' <?=$fecha_mes_hasta==8 ? 'selected':'' ?>><?=__('Agosto') ?></option>
				<option value='9' <?=$fecha_mes_hasta==9 ? 'selected':'' ?>><?=__('Septiembre') ?></option>
				<option value='10' <?=$fecha_mes_hasta==10 ? 'selected':'' ?>><?=__('Octubre') ?></option>
				<option value='11' <?=$fecha_mes_hasta==11 ? 'selected':'' ?>><?=__('Noviembre') ?></option>
				<option value='12' <?=$fecha_mes_hasta==12 ? 'selected':'' ?>><?=__('Diciembre') ?></option>
			</select>
			<?
				if(!$fecha_anio_hasta)
					$fecha_anio_hasta = date('Y');
			?>
			<select name="fecha_anio_hasta" style='width:55px' id="fecha_anio_hasta">
				<? for($i=(date('Y')-5);$i <= date('Y');$i++){ ?>
				<option value='<?=$i?>' <?=$fecha_anio_hasta == $i ? 'selected' : '' ?>><?=$i ?></option>
				<? } ?>
			</select>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Cliente')?>
		</td>
		<td align=left>
			<?=Html::SelectQuery($sesion,"SELECT codigo_cliente, glosa_cliente AS nombre FROM cliente WHERE activo=1 ORDER BY nombre ASC", "clientes[]",$clientes,"class=\"selectMultiple\" multiple size=6 ","","200"); ?>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Profesionales')?>
		</td>
		<td align=left>
			<?=Html::SelectQuery($sesion,"SELECT usuario.id_usuario, CONCAT_WS(' ',usuario.apellido1,usuario.apellido2,',',usuario.nombre) AS nombre FROM usuario JOIN usuario_permiso USING(id_usuario) WHERE usuario.visible = 1 AND usuario_permiso.codigo_permiso='PRO' ORDER BY nombre ASC", "usuarios[]",$usuarios,"class=\"selectMultiple\" multiple size=6 ","","200"); ?>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Tipo de reporte')?>
		</td>
		<td align=left>
			<select name="tipo_reporte" style="width:200px">
				<option <?= $tipo_reporte == "trabajos_por_estudio" ? "selected" : "" ?> value="trabajos_por_estudio"><?=__('Simple')?></option>
				<option <?= $tipo_reporte == "trabajos_por_empleado" ? "selected" : "" ?> value="trabajos_por_empleado"><?=__('Desglose profesional')?></option>
				<option <?= $tipo_reporte == "trabajos_por_cliente" ? "selected" : "" ?> value="trabajos_por_cliente"><?=__('Desglose cliente')?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Dato')?><br/>
		</td>
		<td align=left>
			<select name="tipo_duracion" style="width:200px">
				<option <?= $tipo_duracion == "trabajada" ? "selected" : "" ?> value="trabajada"><?=__('Trabajadas')?></option>
				<option <?= $tipo_duracion == "cobrable" ? "selected" : "" ?> value="cobrable"><?=__('Cobrables')?></option>
				<option <?= $tipo_duracion == "cobrada" ? "selected" : "" ?> value="cobrada"><?=__('Cobradas')?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td colspan=4 align=right>
			<input type=button class=btn value="<?=__('Generar Gráfico')?>" onclick="Validar(this.form,'desplegar');" >
			<!--<input type=button class=btn value="<?=__('Generar Excel')?>" onclick="Validar(this.form,'xls');" >-->
		</td>
	</tr>

</table>

</form>
<?
	if($opcion == "desplegar")
	{
		$datos = 'tipo_reporte='.$tipo_reporte;
		$datos .= '&fecha_desde='.$fecha_desde.'&fecha_hasta='.$fecha_hasta.'&tipo_duracion='.$tipo_duracion;
		//en caso de que el tipo de reporte sea por el estudio
		//se imprime un grafico resumen de los clientes y/o usuarios seleccionados
		if($tipo_reporte=="trabajos_por_estudio")
		{
			if($usuarios)
			{
				foreach($usuarios as $id_usuario)
				{
					$datos .= '&usuarios[]='.$id_usuario;
				}
			}
			if($clientes)
			{
				foreach($clientes as $codigo_cliente)
				{
					$datos .= '&clientes[]='.$codigo_cliente;
				}
			}
			//echo "graficos/grafico_trabajos.php?".$datos;
?>
		<br />
		<img src="graficos/grafico_trabajos.php?<?=$datos ?>" alt='' />
<?
		}
		//en el reporte por cliente se hace un grafico por cada cliente seleccionado
		if($tipo_reporte=="trabajos_por_cliente")
		{
			if($usuarios)
			{
				foreach($usuarios as $id_usuario)
				{
					$datos .= '&usuarios[]='.$id_usuario;
				}
			}
			if($clientes)
			{
				foreach($clientes as $codigo_cliente)
				{
					$datos .= '&codigo_cliente='.$codigo_cliente;
					$query = "SELECT glosa_cliente AS nombre FROM cliente WHERE codigo_cliente=$codigo_cliente LIMIT 1";
					$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
					$temp = mysql_fetch_array($resp);
					$nombre = str_replace(' ','-',$temp['nombre']);
					$datos .= '&nombre='.$nombre;
?>
		<br />
		<img src="graficos/grafico_trabajos.php?<?=$datos ?>" alt='' />
<?
				}
			}
		}
		//en el reporte por empleado se hace un grafico por cada empleado seleccionado
		if($tipo_reporte=="trabajos_por_empleado")
		{
			if($clientes)
			{
				foreach($clientes as $codigo_cliente)
				{
					$datos .= '&clientes[]='.$codigo_cliente;
				}
			}
			if($usuarios)
			{
				foreach($usuarios as $id_usuario)
				{
					$datos .= '&id_usuario='.$id_usuario;
					$query = "SELECT id_usuario, CONCAT_WS(' ',nombre,apellido1,apellido2) AS nombre FROM usuario WHERE id_usuario=$id_usuario LIMIT 1";
					$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
					$temp = mysql_fetch_array($resp);
					$nombre = str_replace(' ','-',$temp['nombre']);
					$datos .= '&nombre='.$nombre;
?>
		<br />
		<img src="graficos/grafico_trabajos.php?<?=$datos ?>" alt='' />
<?
				}
			}
		}
	}
	$pagina->PrintBottom();
?>