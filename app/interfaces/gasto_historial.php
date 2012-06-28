<? 
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
	require_once Conf::ServerDir().'/classes/GastoHistorial.php';
	require_once Conf::ServerDir().'/classes/Cliente.php';
	require_once Conf::ServerDir().'/classes/Asunto.php';
	require_once Conf::ServerDir().'/classes/Autocompletador.php';
	
	$sesion = new Sesion(array('REV','ADM','COB'));
	$pagina = new Pagina($sesion);
	
	$pagina->titulo = __('Historial de gastos');
	$pagina->PrintTop();
	
	if( $buscar==1 )
		{
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) )
				{
					$cliente = new Cliente($sesion);
					$asunto = new Asunto($sesion);
					if( $codigo_cliente_secundario ) 
						$codigo_cliente = $cliente->CodigoSecundarioACodigo($codigo_cliente_secundario);
					if( $codigo_asunto_secundario )
						$codigo_asunto = $asunto->CodigoSecundarioACodigo($codigo_asunto_secundario);
				}
				
			$where = " 1 ";
			if( $codigo_cliente != '' )
				$where .= " AND cl.codigo_cliente = '$codigo_cliente'";
			if( $codigo_asunto != '' ) 
				$where .= " AND a.codigo_asunto = '$codigo_asunto'";
			if( $id_movimiento != '' )
				$where .= " AND gh.id_movimiento = ".$id_movimiento." ";
			if( $fecha_desde != '' ) 
				$where .= " AND gh.fecha > '".Utiles::fecha2sql($fecha_desde)."' ";
			if( $fecha_hasta != '' ) 
				$where .= " AND gh.fecha < '".Utiles::fecha2sql($fecha_hasta)." 23:59:59' ";
			if( $accion != '' ) 
				$where .= " AND gh.accion = '$accion' ";
			if( $descripcion != '' )
				$where .= " AND ( gh.descripcion LIKE '%".$descripcion."%' OR gh.descripcion_modificado LIKE '%".$descripcion."%' ) ";
				
				$query = "SELECT DISTINCT SQL_CALC_FOUND_ROWS *, 
												gh.id_movimiento as id_movimiento,
												gh.fecha as fecha_modificacion, 
												gh.id_usuario as id_modificador, 
												gh.accion as accion, 
												gh.fecha_movimiento as fecha_movimiento_anterior, 
												gh.fecha_movimiento_modificado as fecha_movimiento, 
												gh.codigo_cliente as codigo_cliente_anterior, 
												gh.codigo_cliente_modificado as codigo_cliente, 
												gh.codigo_asunto as codigo_asunto_anterior, 
												gh.codigo_asunto_modificado as codigo_asuntoo,
												gh.egreso as egreso_anterior, 
												gh.egreso_modificado as egreso, 
												gh.ingreso as ingreso_anterior, 
												gh.ingreso_modificado as ingreso, 
												gh.monto_cobrable as monto_cobrable_anterior, 
												gh.monto_cobrable_modificado as monto_cobrable, 
												gh.descripcion as descripcion_anterior, 
												gh.descripcion_modificado as descripcion, 
												gh.id_moneda as id_moneda_anterior, 
												gh.id_moneda_modificado as id_moneda, 
												cla.glosa_cliente as glosa_cliente_anterior,  
												cl.glosa_cliente as glosa_cliente, 
												aa.glosa_asunto as glosa_asunto_anterior, 
												a.glosa_asunto as glosa_asunto, 
												CONCAT_WS(' ',u.nombre,u.apellido1,u.apellido2) as nombre_usuario, 
												ma.simbolo as simbolo_anterior, 
												m.simbolo as simbolo 
											FROM gasto_historial AS gh  
								 LEFT JOIN cta_corriente AS cta ON gh.id_movimiento = cta.id_movimiento  
								 LEFT JOIN usuario		AS u   ON gh.id_usuario = u.id_usuario 
								 LEFT JOIN cliente		AS cl  ON cl.codigo_cliente = gh.codigo_cliente_modificado 
						 		 LEFT JOIN cliente		AS cla ON cla.codigo_cliente = gh.codigo_cliente 
								 LEFT JOIN asunto  		AS a   ON a.codigo_asunto = gh.codigo_asunto_modificado 
								 LEFT JOIN asunto  		AS aa  ON aa.codigo_asunto = gh.codigo_asunto 
								 LEFT JOIN prm_moneda AS ma  ON ma.id_moneda = gh.id_moneda 
								 LEFT JOIN prm_moneda AS m   ON m.id_moneda = gh.id_moneda_modificado 
											WHERE $where "; 
											
				if( $orden == "" )
					$orden = " gh.fecha DESC ";
					
				
				$x_pag = 15;
				$b = new Buscador($sesion, $query, "GastoHistorial", $desde, $x_pag, $orden);
				$b->mensaje_error_fecha = "N/A";
				$b->nombre = "busc_gasto_historial";
				$b->titulo = __('Listado de').' '.__('gastos modificados');
				$b->AgregarEncabezado("gh.id_movimiento","ID","align=center");
				$b->AgregarEncabezado("nombre_usuario",__('Modificado por'),"align=center");
				$b->AgregarEncabezado("gh.fecha",__('Fecha modificacion'),"align=center");
				$b->AgregarEncabezado("gh.accion",__('Acción'),"align=center");
				$b->AgregarEncabezado("a.glosa_asunto",__('Asunto'),"align=center");
				$b->AgregarEncabezado("gh.descripcion_modificado",__('Descripción'),"align=center");
				$b->AgregarEncabezado("IF( gh.fecha_movimiento_modificado!='' OR gh.fecha_movimiento_modificado IS NOT NULL,gh.fecha_movimiento_modificado,gh.fecha_movimiento)",__('Fecha movimiento'),"align=center");
				$b->AgregarEncabezado("IF(gh.egreso_modificado>0,gh.egreso_modificado,gh.egreso)",__('Egreso'),"align=center");
				$b->AgregarEncabezado("IF(gh.ingreso_modificado>0,gh.ingreso_modificado,gh.ingreso)",__('Ingreso'),"align=center");
				$b->color_mouse_over = "#bcff5c";
				$b->funcionTR = "funcionTR";
				
		}

echo(Autocompletador::CSS());
	?>
	<form action="#">
	<input type="hidden" name="buscar" id="buscar" value="1" />
	<table width="90%" style="border: 1px solid #BDBDBD;">
	<tr>
		<td width="20%" align="right">
			<?=__('Id gasto/provision')?>
		</td>
		<td width="80%" colspan="3" align="left">
			<input type="text" size="3" id="id_movimiento" name="id_movimiento" value="<?=$id_movimiento?>" /> 
		</td>
	</tr> 
	<tr>
        <td align="right">
            <?=__('Cliente')?>
        </td>
        <td nowrap align="left" colspan="3">
<?
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
	{
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
			echo Autocompletador::ImprimirSelector($sesion,'',$codigo_cliente_secundario);
		else
			echo Autocompletador::ImprimirSelector($sesion,$codigo_cliente);
	}
	else
	{
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
			echo InputId::Imprimir($sesion,"cliente","codigo_cliente_secundario","glosa_cliente", "codigo_cliente_secundario", $codigo_cliente_secundario,"","CargarSelect('codigo_cliente_secundario','codigo_asunto_secundario','cargar_asuntos',1);", 320, $codigo_asunto);
		else
			echo InputId::Imprimir($sesion,"cliente","codigo_cliente","glosa_cliente", "codigo_cliente", $codigo_cliente,"","CargarSelect('codigo_cliente','codigo_asunto','cargar_asuntos',1);", 320, $codigo_asunto);
	}
?>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Asunto')?>
		</td>
		<td nowrap align='left' colspan=3>
			<?
					if (( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ))
					{
						echo InputId::Imprimir($sesion,"asunto","codigo_asunto_secundario","glosa_asunto", "codigo_asunto_secundario", $codigo_asunto_secundario,"","CargarSelectCliente(this.value);", 320,$codigo_cliente_secundario);
					}
					else
					{
						echo InputId::Imprimir($sesion,"asunto","codigo_asunto","glosa_asunto", "codigo_asunto", $codigo_asunto,"", "CargarSelectCliente(this.value);", 320,$codigo_cliente);
					}
?>
				</td>
			</tr>
			<tr>
				<td align="right">
					<?=__('Acción')?>
				</td>
				<td align="left" colspan="3">
					<select name="accion" id="accion" width="150">
						<option value=''></option>
						<option value='CREAR' <?=$accion=='CREAR'?'selected':''?>>CREAR</option>
						<option value='MODIFICAR' <?=$accion=='MODIFICAR'?'selected':''?>>MODIFICAR</option>
						<option value='ELIMINAR' <?=$accion=='ELIMINAR'?'selected':''?>>ELIMINAR</option>
					</select>
				</td>
			</tr>
			<tr> 
				<td align="right" width="20%">
					<?=__('Fecha desde')?>
				</td>
				<td align="left" width="20%">
					<input type="text" size="10" id="fecha_desde" name="fecha_desde" value="<?=$fecha_desde?>" />
					<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha_desde" style="cursor:pointer" />
				</td>
				<td align="right" width="20%">
					<?=__('Fecha hasta')?>
				</td>
				<td align="left" width="40%"> 
					<input type="text" size="10" id="fecha_hasta" name="fecha_hasta" value="<?=$fecha_hasta?>" /> 
					<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha_hasta" style="cursor:pointer" />
				</td>
			</tr>
			<tr>
				<td align="right">
					<?=__('Descripción')?>
				</td>
				<td align="left" colspan="3">
					<input type="text" size="50" id="descripcion" name="descripcion" value="<?=$descripcion?>" />
				</td>
			</tr>
			<tr>
				<td colspan="4" align="center">
					<input type="submit" value="<?=__('Buscar')?>" />
				</td>
			</tr>
		</table>
	</form>
	
	<?
	if( $buscar == 1 )
		{
			echo "<center>";
			$b->Imprimir();
			echo "</center>";
		}
	
	function funcionTR(& $gasto_historial)
		{
			static $i = 0;
			
			if($i % 2 == 0)
				$color = "#dddddd";
			else
				$color = "#ffffff";
			
			$formato_fecha = "%d/%m/%y";
			$fecha_modificacion = Utiles::sql2fecha($gasto_historial->fields['fecha_modificacion'],$formato_fecha);
			$fecha_movimiento = Utiles::sql2fecha($gasto_historial->fields['fecha_movimiento'],$formato_fecha);
			$fecha_movimiento_anterior = Utiles::sql2fecha($gasto_historial->fields['fecha_movimiento_anterior'],$formato_fecha);
			$html .= "<tr id=\"t".$gasto_historial->fields['id_trabajo']."\" bgcolor=$color>";
			$html .= "<td width=\"5%\">".$gasto_historial->fields['id_movimiento']."</td>";
			$html .= "<td width=\"10%\">".$gasto_historial->fields['nombre_usuario']."</td>";
			$html .= "<td width=\"9%\">".$fecha_modificacion."</td>";
			$html .= "<td width=\"9%\">".$gasto_historial->fields['accion']."</td>";
			if($gasto_historial->fields['accion'] == 'CREAR')
				{
					if( strlen($gasto_historial->fields['glosa_asunto']) > 25)
						$html .= "<td width=\"18%\" nowrap><div onmouseover=\"ddrivetip('".$gasto_historial->fields[glosa_asunto]."');\" onmouseout=\"hideddrivetip();\">".substr($gasto_historial->fields['glosa_asunto'],0,23)."..</div></td>";
					else	
						$html .= "<td width=\"18%\" nowrap>".$gasto_historial->fields['glosa_asunto']."</td>";
					if( strlen($gasto_historial->fields['descripcion']) > 25 )
						$html .= "<td width=\"18%\" nowrap><div onmouseover=\"ddrivetip('".$gasto_historial->fields[descripcion]."');\" onmouseout=\"hideddrivetip();\">".substr($gasto_historial->fields['descripcion'],0,23)."..</div></td>";
					else
						$html .= "<td width=\"18%\" nowrap>".$gasto_historial->fields['descripcion']."</td>";
					$html .= "<td width=\"9%\">".$fecha_movimiento."</td>";
					if( $gasto_historial->fields['egreso'] > 0 )
						$html .= "<td width=\"11%\">".$gasto_historial->fields['simbolo'].$gasto_historial->fields['monto_cobrable']."</td>";
					else
						$html .= "<td width=\"11%\"></td>";
					if( $gasto_historial->fields['ingreso'] > 0 )
						$html .= "<td width=\"11%\">".$gasto_historial->fields['simbolo'].$gasto_historial->fields['monto_cobrable']."</td>";
					else
						$html .= "<td width=\"11%\"></td>";
				}
			else if( $gasto_historial->fields['accion'] == 'MODIFICAR' )
				{
					if(strlen($gasto_historial->fields['glosa_asunto']) > 25 && strlen($gasto_historial->fields['glosa_asunto_anterior'])>25 )
						$html .= "<td width=\"18%\"><div onmouseover=\"ddrivetip('".$gasto_historial->fields[glosa_asunto]."<br><font color=red>".$gasto_historial->fields[glosa_asunto_anterior]."</font>');\" onmouseout=\"hideddrivetip();\">".substr($gasto_historial->fields['glosa_asunto'],0,23)."..<br><font color=red>".substr($gasto_historial->fields['glosa_asunto_anterior'],0,23)."..</font></div></td>";
					else if( strlen($gasto_historial->fields['glosa_asunto']) > 25)
						$html .= "<td width=\"18%\"><div onmouseover=\"ddrivetip('".$gasto_historial->fields[glosa_asunto]."<br><font color=red>".$gasto_historial->fields[glosa_asunto_anterior]."</font>');\" onmouseout=\"hideddrivetip();\">".substr($gasto_historial->fields['glosa_asunto'],0,23)."..<br><font color=red>".$gasto_historial->fields['glosa_asunto_anterior']."</font></div></td>";
					else if( strlen($gasto_historial->fields['glosa_asunto_anterior']) > 25 )
						$html .= "<td width=\"18%\"><div onmouseover=\"ddrivetip('".$gasto_historial->fields[glosa_asunto]."<br><font color=red>".$gasto_historial->fields[glosa_asunto_anterior]."</font>');\" onmouseout=\"hideddrivetip();\">".$gasto_historial->fields['glosa_asunto']."<br><font color=red>".substr($gasto_historial->fields['glosa_asunto_anterior'],0,23)."..</font></div></td>";
					else
						$html .= "<td width=\"18%\">".$gasto_historial->fields['glosa_asunto']."<br><font color=red>".$gasto_historial->fields['glosa_asunto_anterior']."</font></td>";
					if(strlen($gasto_historial->fields['descripcion']) > 25 && strlen($gasto_historial->fields['descripcion_anterior']) > 25 )
						$html .= "<td width=\"18%\"><div onmouseover=\"ddrivetip('".$gasto_historial->fields[descripcion]."<br><font color=red>".$gasto_historial->fields[descripcion_anterior]."</font>');\" onmouseout=\"hideddrivetip();\">".substr($gasto_historial->fields['descripcion'],0,23)."..<br><font color=red>".substr($gasto_historial->fields['descripcion_anterior'],0,23)."..</font></div></td>";
					else if( strlen($gasto_historial->fields['descripcion']) > 25 )
						$html .= "<td width=\"18%\"><div onmouseover=\"ddrivetip('".$gasto_historial->fields[descripcion]."<br><font color=red>".$gasto_historial->fields[descripcion_anterior]."</font>');\" onmouseout=\"hideddrivetip();\">".substr($gasto_historial->fields['descripcion'],0,23)."..<br><font color=red>".$gasto_historial->fields['descripcion_anterior']."</font></div></td>";
					else if( strlen($gasto_historial->fields['descripcion_anterior']) > 25 )
						$html .= "<td width=\"18%\"><div onmouseover=\"ddrivetip('".$gasto_historial->fields[descripcion]."<br><font color=red>".$gasto_historial->fields[descripcion_anterior]."</font>');\" onmouseout=\"hideddrivetip();\">".$gasto_historial->fields['descripcion']."<br><font color=red>".substr($gasto_historial->fields['descripcion_anterior'],0,23)."..</font></div></td>";
					else	
						$html .= "<td width=\"18%\">".$gasto_historial->fields['descripcion']."<br><font color=red>".$gasto_historial->fields['descripcion_anterior']."</font></td>";
					$html .= "<td width=\"9%\">".$fecha_movimiento."<br><font color=red>".$fecha_movimiento_anterior."</font></td>";
					if($gasto_historial->fields['egreso'] > 0 && $gasto_historial->fields['egreso_anterior'])
						$html .= "<td width=\"11%\">".$gasto_historial->fields['simbolo'].$gasto_historial->fields['monto_cobrable']."<br><font color=red>".$gasto_historial->fields['simbolo_anterior'].$gasto_historial->fields['monto_cobrable_anterior']."</font></td>";
					else if( $gasto_historial->fields['egreso'] > 0 )
						$html .= "<td width=\"11%\">".$gasto_historial->fields['simbolo'].$gasto_historial->fields['monto_cobrable']."<br><font color=red>".$gasto_historial->fields['simbolo_anterior'].'0'."</font></td>";
					else if( $gasto_historial->fields['egreso_anterior'] > 0 )
						$html .= "<td width=\"11%\">".$gasto_historial->fields['simbolo'].'0'."<br><font color=red>".$gasto_historial->fields['simbolo_anterior'].$gasto_historial->fields['monto_cobrable_anterior']."</font></td>";
					else 
						$html .= "<td width=\"11%\"></td>";
					if($gasto_historial->fields['ingreso'] > 0 && $gasto_historial->fields['ingreso_anterior'] > 0)
						$html .= "<td width=\"11%\">".$gasto_historial->fields['simbolo'].$gasto_historial->fields['monto_cobrable']."<br><font color=red>".$gasto_historial->fields['simbolo_anterior'].$gasto_historial->fields['monto_cobrable_anterior']."</font></td>";
					else if( $gasto_historial->fields['ingreso'] > 0 )
						$html .= "<td width=\"11%\">".$gasto_historial->fields['simbolo'].$gasto_historial->fields['monto_cobrable']."<br><font color=red>".$gasto_historial->fields['simbolo_anterior'].'0'."</font></td>";
					else if( $gasto_historial->fields['ingreso_anterior'] )
						$html .= "<td width=\"11%\">".$gasto_historial->fields['simbolo'].'0'."<br><font color=red>".$gasto_historial->fields['simbolo'].$gasto_historial->fields['monto_cobrable_anterior']."</font></td>";
					else 
						$html .= "<td width=\"11%\"></td>";
				}
			else	
				{
					if( strlen($gasto_historial->fields['glosa_asunto_anterior']) > 25)
						$html .= "<td width=\"18%\" nowrap><div onmouseover=\"ddrivetip('".$gasto_historial->fields[glosa_asunto_anterior]."');\" onmouseout=\"hideddrivetip();\">".substr($gasto_historial->fields['glosa_asunto_anterior'],0,23)."..</div></td>";
					else	
						$html .= "<td width=\"18%\" nowrap>".$gasto_historial->fields['glosa_asunto_anterior']."</td>";
					if( strlen($gasto_historial->fields['descripcion_anterior']) > 25)
						$html .= "<td width=\"18%\" nowrap><div onmouseover=\"ddrivetip('".$gasto_historial->fields[descripcion_anterior]."');\" onmouseout=\"hideddrivetip();\">".substr($gasto_historial->fields['descripcion_anterior'],0,23)."..</div></td>";
					else	
						$html .= "<td width=\"18%\" nowrap>".$gasto_historial->fields['descripcion_anterior']."</td>";
					$html .= "<td width=\"9%\">".$fecha_movimiento_anterior."</td>";
					if( $gasto_historial->fields['egreso_anterior'] > 0 )
						$html .= "<td width=\"11%\">".$gasto_historial->fields['simbolo'].$gasto_historial->fields['monto_cobrable_anterior']."</td>";
					else
						$html .= "<td width=\"11%\"></td>";
					if( $gasto_historial->fields['ingreso_anterior'] > 0 )
						$html .= "<td width=\"11%\">".$gasto_historial->fields['simbolo'].$gasto_historial->fields['monto_cobrable_anterior']."</td>";
					else
						$html .= "<td width=\"11%\"></td>";
				}
			
	    $i++;
	    return $html;
		}

	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
	{
		echo(Autocompletador::Javascript($sesion));
	}
	echo(InputId::Javascript($sesion));
	$pagina->PrintBottom();
	?>
	
	<script language="javascript" type="text/javascript">
	//datepicker Fecha
	Calendar.setup(
		{
			inputField	: "fecha_desde",				// ID of the input field
			ifFormat	: "%d-%m-%Y",			// the date format
			button			: "img_fecha_desde"		// ID of the button
		}
	);
	Calendar.setup(
		{
			inputField : "fecha_hasta",    // input of the input field
			ifFormat   : "%d-%m-%Y", // the date format
			button     : "img_fecha_hasta"  // ID of the button
		}
	);
	</script>