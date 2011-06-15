<? 
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
require_once Conf::ServerDir().'/../fw/classes/Html.php';
require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';
require_once Conf::ServerDir().'/classes/InputId.php';
require_once Conf::ServerDir().'/classes/TrabajoHistorial.php';
require_once Conf::ServerDir().'/classes/Cliente.php';
require_once Conf::ServerDir().'/classes/Asunto.php';
require_once Conf::ServerDir().'/classes/Autocompletador.php';

$sesion = new Sesion(array('REV','ADM','COB'));
$pagina = new Pagina($sesion);

$pagina->titulo = __('Historial de trabajos');
$pagina->PrintTop();

if( $buscar==1 )
{
	if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) )
		{
			$cliente = new Cliente($sesion);
			$asunto = new Asunto($sesion);
			if( $codigo_cliente_secundario ) 
				$cliente->CodigoSecundarioACodigo($codigo_cliente_secundario);
			if( $codigo_asunto_secundario )
				$asunto->CodigoSecundarioACodigo($codigo_asunto_secundario);
		}
		
	$where = " 1 ";
	if( $codigo_cliente != '' )
		$where .= " AND cl.codigo_cliente = '$codigo_cliente'";
	if( $codigo_asunto != '' ) 
		$where .= " AND a.codigo_asunto = '$codigo_asunto'";
	if( $id_trabajo != '' )
		$where .= " AND th.id_trabajo = ".$id_trabajo." ";
	if( $fecha_desde != '' ) 
		$where .= " AND th.fecha > '".Utiles::fecha2sql($fecha_desde)."' ";
	if( $fecha_hasta != '' ) 
		$where .= " AND th.fecha < '".Utiles::fecha2sql($fecha_hasta)." 23:59:59' ";
	if( $accion != '' ) 
		$where .= " AND th.accion = '$accion' ";
	if( $id_usuario != '' )
		$where .= " AND th.id_usuario_trabajador = ".$id_usuario." "; 
	if( $descripcion != '' )
		$where .= " AND ( th.descripcion LIKE '%".$descripcion."%' OR th.descripcion_modificado LIKE '%".$descripcion."%' )";
		
		$query = "SELECT DISTINCT SQL_CALC_FOUND_ROWS *, 
										th.id_trabajo as id_trabajo, 
										th.id_usuario as modificador, 
										th.fecha as fecha_modificacion, 
										th.fecha_trabajo_modificado as fecha_trabajo, 
										th.fecha_trabajo as fecha_trabajo_anterior, 
										th.descripcion as descripcion_anterior, 
										th.accion as accion, 
										th.codigo_asunto as codigo_asunto_anterior, 
										th.cobrable as cobrable_anterior, 
										th.duracion as duracion_anterior, 
										th.duracion_cobrada as duracion_cobrable_anterior, 
										th.duracion_modificado as duracion, 
										th.fecha_trabajo_modificado as fecha_trabajo, 
										th.descripcion_modificado as descripcion, 
										th.duracion_cobrada_modificado as duracion_cobrable, 
										th.duracion_modificado as duracion, 
										cl.glosa_cliente as cliente_anterior,  
										a.glosa_asunto as asunto_anterior, 
										cla.glosa_cliente as cliente, 
										aa.glosa_asunto as asunto,  
										CONCAT_WS(' ',u.nombre,u.apellido1,u.apellido2) as nombre_usuario, 
										CONCAT_WS(' ',uta.nombre,uta.apellido1,uta.apellido2) as trabajador_anterior, 
										CONCAT_WS(' ',ut.nombre,ut.apellido1,ut.apellido2) as trabajador  
									FROM trabajo_historial AS th 
						 LEFT JOIN trabajo AS t ON th.id_trabajo = t.id_trabajo 
						 LEFT JOIN usuario AS u ON th.id_usuario = u.id_usuario 
						 LEFT JOIN usuario AS uta ON th.id_usuario_trabajador = uta.id_usuario 
						 LEFT JOIN usuario AS ut ON th.id_usuario_trabajador_modificado = ut.id_usuario 
						 LEFT JOIN asunto AS a ON a.codigo_asunto=th.codigo_asunto 
						 LEFT JOIN cliente AS cl ON cl.codigo_cliente=a.codigo_cliente 
						 LEFT JOIN asunto AS aa ON aa.codigo_asunto=th.codigo_asunto_modificado
						 LEFT JOIN cliente AS cla ON cla.codigo_cliente=aa.codigo_cliente 
									WHERE $where ";
		 
		if( $orden == "" )
			$orden = " th.fecha DESC ";
			
		
		$x_pag = 15;
		$b = new Buscador($sesion, $query, "TrabajoHistorial", $desde, $x_pag, $orden);
		$b->mensaje_error_fecha = "N/A";
		$b->nombre = "busc_trabajo_historial";
		$b->titulo = __('Listado de').' '.__('trabajos modificados');
		$b->AgregarEncabezado("th.id_trabajo","ID","align=center");
		$b->AgregarEncabezado("nombre_usuario",__('Modificado por'),"align=center");
		$b->AgregarEncabezado("th.fecha",__('Fecha modificacion'),"align=center");
		$b->AgregarEncabezado("th.accion",__('Acción'),"align=center");
		$b->AgregarEncabezado("a.glosa_asunto",__('Asunto'),"align=center");
		$b->AgregarEncabezado("descripcion",__('Descripción'),"align=center");
		$b->AgregarEncabezado("th.fecha_trabajo_modificado",__('Fecha trabajo'),"align=center");
		$b->AgregarEncabezado("trabajador",__('Abogado'),"align=center");
		$b->AgregarEncabezado("duracion",__('Duración'),"align=center");
		$b->AgregarEncabezado("duracion_cobrable",_('Duración<br>cobrable'),"align=center");
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
			<?=__('Id trabajo')?>
		</td>
		<td width="80%" colspan="3" align="left">
			<input type="text" size="3" id="id_trabajo" name="id_trabajo" value="<?=$id_trabajo?>" /> 
		</td>
	</tr> 
	 <tr>
        <td align=right>
            <?=__('Cliente')?>
        </td>
        <td nowrap align='left' colspan=3>
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
		if ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
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
						echo InputId::Imprimir($sesion,"asunto","codigo_asunto_secundario","glosa_asunto", "codigo_asunto_secundario", $codigo_asunto_secundario,"","CargarSelectCliente(this.value);", 320,$codigo_cliente_secundario);
					else
						echo InputId::Imprimir($sesion,"asunto","codigo_asunto","glosa_asunto", "codigo_asunto", $codigo_asunto,"", "CargarSelectCliente(this.value);", 320,$codigo_cliente);
			?>
		</td>
	</tr>
	<tr>
		<td align="right">
			<?=__('Abogado')?>
		</td>
		<td align="left">
			<?=Html::SelectQuery($sesion,"SELECT usuario.id_usuario, CONCAT_WS(' ',usuario.apellido1,usuario.apellido2,',',usuario.nombre) AS nombre FROM usuario JOIN usuario_permiso USING(id_usuario) WHERE usuario.visible = 1 AND usuario_permiso.codigo_permiso='PRO' ORDER BY nombre ASC","id_usuario",$id_usuario,'','Todos','200') ?>
		</td>
		<td align="right">
			<?=__('Acción')?>
		</td>
		<td align="left">
			<select name="accion" id="accion" width="150">
				<option value=''></option>
				<option value='CREAR' <?=$accion=='CREAR'?'selected':''?>>CREAR</option>
				<option value='MODIFICAR' <?=$accion=='MODIFICAR'?'selected':''?>>MODIFICAR</option>
				<option value='SUBIR_XLS' <?=$accion=='SUBIR_XLS'?'selected':''?>>SUBIR EXCEL</option>
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

function funcionTR(& $trabajo_historial)
	{
		global $sesion;
		static $i = 0;
		
		if($i % 2 == 0)
			$color = "#dddddd";
		else
			$color = "#ffffff";
	
		list($ha,$ma,$sa)=split(":",$trabajo_historial->fields['duracion_anterior']);
		list($h,$m,$s)=split(":",$trabajo_historial->fields['duracion']);
		list($hca,$mca,$sca)=split(":",$trabajo_historial->fields['duracion_cobrable_anterior']);
		list($hc,$mc,$sc)=split(":",$trabajo_historial->fields['duracion_cobrable']);
		
		$dur_ant = "$ha:$ma";
		$dur = "$h:$m";
		$dur_cob_ant = "$hca:$mca";
		$dur_cob = "$hc:$mc";
		
		$formato_fecha = "%d/%m/%y";
		$fecha_modificacion = Utiles::sql2fecha($trabajo_historial->fields['fecha_modificacion'],$formato_fecha);
		if( $trabajo_historial->fields['fecha_trabajo'] == '' ) 
			$fecha_trabajo = '';
		else
			$fecha_trabajo = Utiles::sql2fecha($trabajo_historial->fields['fecha_trabajo'],$formato_fecha);
		if( $trabajo_historial->fields['fecha_trabajo_anterior'] == '' )
			$fecha_trabajo_anterior = '';
		else
			$fecha_trabajo_anterior = Utiles::sql2fecha($trabajo_historial->fields['fecha_trabajo_anterior'],$formato_fecha);
		$html .= "<tr id=\"t".$trabajo_historial->fields['id_trabajo']."\" bgcolor=$color>";
		$html .= "<td width=\"5%\">".$trabajo_historial->fields['id_trabajo']."</td>";
		$html .= "<td width=\"10%\">".$trabajo_historial->fields['nombre_usuario']."</td>";
		$html .= "<td width=\"9%\">".$fecha_modificacion."</td>";
		$html .= "<td width=\"9%\">".$trabajo_historial->fields['accion']."</td>";
		if( $trabajo_historial->fields['accion'] == 'CREAR' )
			{
				if( strlen($trabajo_historial->fields['asunto']) > 25 )
					$html .= "<td width=\"18%\"><div onmouseover=\"ddrivetip('".$trabajo_historial->fields[asunto]."');\" onmouseout=\"hideddrivetip();\">".substr($trabajo_historial->fields['asunto'],0,23)."..</div></td>";
				else 
					$html .= "<td width=\"18%\">".$trabajo_historial->fields['asunto']."</td>";
				if( strlen($trabajo_historial->fields['descripcion']) > 25 )
					$html .= "<td width=\"18%\"><div onmouseover=\"ddrivetip('".$trabajo_historial->fields[asunto]."');\" onmouseout=\"hideddrivetip();\">".substr($trabajo_historial->fields['asunto'],0,23)."..</div></td>";
				else
					$html .= "<td width=\"18%\">".$trabajo_historial->fields['descripcion']."</td>";
				$html .= "<td width=\"9%\">".$fecha_trabajo."</td>";
				if( strlen($trabajo_historial->fields['trabajador']) > 25 ) 
					$html .= "<td width=\"18%\"><div onmouseover=\"ddrivetip('".$trabajo_historial->fields[trabajador]."');\" onmouseout=\"hideddrivetip();\">".substr($trabajo_historial->fields['trabajador'],0,23)."..</div></td>";
				else	
					$html .= "<td width=\"18%\">".$trabajo_historial->fields['trabajador']."</td>";
				$html .= "<td width=\"6%\">".$dur."</td>";
				$html .= "<td width=\"6%\">".$dur_cob."</td>";
			}
		else if( $trabajo_historial->fields['accion'] == 'MODIFICAR' || $trabajo_historial->fields['accion'] == 'SUBIR_XLS' )
			{
				if(strlen($trabajo_historial->fields['asunto']) > 25 && strlen($trabajo_historial->fields['asunto_anterior'])>25 )
					$html .= "<td width=\"18%\"><div onmouseover=\"ddrivetip('".$trabajo_historial->fields[asunto]."<br><font color=red>".$trabajo_historial->fields[asunto_anterior]."</font>');\" onmouseout=\"hideddrivetip();\">".substr($trabajo_historial->fields['asunto'],0,23)."..<br><font color=red>".substr($trabajo_historial->fields['asunto_anterior'],0,16)."..</font></div></td>";
				else if( strlen($trabajo_historial->fields['asunto']) > 25)
					$html .= "<td width=\"18%\"><div onmouseover=\"ddrivetip('".$trabajo_historial->fields[asunto]."<br><font color=red>".$trabajo_historial->fields[asunto_anterior]."</font>');\" onmouseout=\"hideddrivetip();\">".substr($trabajo_historial->fields['asunto'],0,23)."..<br><font color=red>".$trabajo_historial->fields['asunto_anterior']."</font></div></td>";
				else if( strlen($trabajo_historial->fields['asunto_anterior']) > 25 )
					$html .= "<td width=\"18%\"><div onmouseover=\"ddrivetip('".$trabajo_historial->fields[asunto]."<br><font color=red>".$trabajo_historial->fields[asunto_anterior]."</font>');\" onmouseout=\"hideddrivetip();\">".$trabajo_historial->fields['asunto']."<br><font color=red>".substr($trabajo_historial->fields['asunto_anterior'],0,23)."..</font></div></td>";
				else
					$html .= "<td width=\"18%\">".$trabajo_historial->fields['asunto']."<br><font color=red>".$trabajo_historial->fields['asunto_anterior']."</font></td>";
				if(strlen($trabajo_historial->fields['descripcion']) > 25 && strlen($trabajo_historial->fields['descripcion_anterior']) > 25 )
					$html .= "<td width=\"18%\"><div onmouseover=\"ddrivetip('".$trabajo_historial->fields[descripcion]."<br><font color=red>".$trabajo_historial->fields[descripcion_anterior]."</font>');\" onmouseout=\"hideddrivetip();\">".substr($trabajo_historial->fields['descripcion'],0,23)."..<br><font color=red>".substr($trabajo_historial->fields['descripcion_anterior'],0,23)."..</font></div></td>";
				else if( strlen($trabajo_historial->fields['desripcion']) > 25 )
					$html .= "<td width=\"18%\"><div onmouseover=\"ddrivetip('".$trabajo_historial->fields[descripcion]."<br><font color=red>".$trabajo_historial->fields[descripcion_anterior]."</font>');\" onmouseout=\"hideddrivetip();\">".substr($trabajo_historial->fields['descripcion'],0,23)."..<br><font color=red>".$trabajo_historial->fields['descripcion_anterior']."</font></div></td>";
				else if( strlen($trabajo_historial->fields['descripcion_anterior']) > 25 )
					$html .= "<td width=\"18%\"><div onmouseover=\"ddrivetip('".$trabajo_historial->fields[descripcion]."<br><font color=red>".$trabajo_historial->fields[descripcion_anterior]."</font>');\" onmouseout=\"hideddrivetip();\">".$trabajo_historial->fields['descripcion']."<br><font color=red>".substr($trabajo_historial->fields['descripcion_anterior'],0,23)."..</font></div></td>";
				else	
					$html .= "<td width=\"18%\">".$trabajo_historial->fields['descripcion']."<br><font color=red>".$trabajo_historial->fields['descripcion_anterior']."</font></td>";
				$html .= "<td width=\"9%\">".$fecha_trabajo."<br><font color=red>".$fecha_trabajo_anterior."</font></td>";
				if( strlen($trabajo_historial->fields['trabajador']) > 14 && strlen($trabajo_historial->fields['trabajador_anterior']) > 14 )
					$html .= "<td width=\"10%\"><div onmouseover=\"ddrivetip('".$trabajo_historial->fields['trabajador']."<br><font color=red>".$trabajo_historial->fields['trabajador_anterior']."</font>');\" onmouseout=\"hideddrivetip();\">".substr($trabajo_historial->fields['trabajador'],0,12)."..<br><font color=red>".substr($trabajo_historial->fields['trabajador_anterior'],0,12)."..</font></td>";
				else if( strlen($trabajo_historial->fields['trabajador']) > 14 )
					$html .= "<td width=\"10%\"><div onmouseover=\"ddrivetip('".$trabajo_historial->fields['trabajador']."<br><font color=red>".$trabajo_historial->fields['trabajador_anterior']."</font>');\" onmouseout=\"hideddrivetip();\">".substr($trabajo_historial->fields['trabajador'],0,12)."..<br><font color=red>".$trabajador_historial->fields['trabajador_anterior']."</font></td>";
				else if( strlen($trabajo_historial->fields['trabajador_anterior']) > 14 )
					$html .= "<td width=\"10%\"><div onmouseover=\"ddrivetip('".$trabajo_historial->fields['trabajador']."<br><font color=red>".$trabajo_historial->fields['trabajador_anterior']."</font>');\" onmouseout=\"hideddrivetip();\">".$trabajo_historial->fields['trabajador']."<br><font color=red>".substr($trabajo_historial->fields['trabajo_anterior'],0,12)."</font></td>";
				else
					$html .= "<td width=\"10%\">".$trabajo_historial->fields['trabajador']."<br><font color=red>".$trabajo_historial->fields['trabajador_anterior']."</font></td>";
				$html .= "<td width=\"6%\">".$dur."<br><font color=red>".$dur_ant."</font></td>";
				$html .= "<td width=\"6%\">".$dur_cob."<br><font color=red>".$dur_cob_ant."</font></td>";
			}
		else
			{
				if( strlen($trabajo_historial->fields['asunto_anterior']) > 25 )
					$html .= "<td width=\"18%\"><div onmouseover=\"ddrivetip('".$trabajo_historial->fields[asunto_anterior]."');\" onmouseout=\"hideddrivetip();\">".$trabajo_historial->fields[asunto_anterior]."..</div></td>";
				else
					$html .= "<td width=\"18%\">".$trabajo_historial->fields['asunto_anterior']."</td>";
				if( strlen($trabajo_historial->fields['descripcion_anterior']) > 25 )
					$html .= "<td width=\"18%\"><div onmouseover=\"ddrivetip('".$trabajo_historial->fields[descripcion_anterior]."');\" onmouseout=\"hideddrivetip();\">".$trabajo_historial->fields[asunto_anterior]."..</div></td>";
				else 
					$html .= "<td width=\"18%\">".$trabajo_historial->fields['descripcion_anterior']."</td>";
				$html .= "<td width=\"9%\">".$fecha_trabajo_anterior."</td>";
				if( strlen($trabajo_historial->fields['trabajador_anterior']) > 25 ) 
					$html .= "<td width=\"10%\"><div onmouseover=\"ddrivetip('".$trabajo_historial->fields[trabajador_anterior]."');\" onmouseout=\"hideddrivetip();\">".$trabajo_historial->fields['trabajador_anterior']."..</div></td>";
				else 
					$html .= "<td width=\"10%\">".$trabajo_historial->fields['trabajador_anterior']."</td>";
				$html .= "<td width=\"6%\">".$dur_cob."</td>";
				$html .= "<td width=\"6%\">".$dur_cob_ant."</td>";
			}
		$html .= "</tr>\n";
		
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
