<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/Asunto.php';
	require_once Conf::ServerDir().'/classes/Cliente.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
	require_once Conf::ServerDir().'/classes/CobroAsunto.php';
	require_once Conf::ServerDir().'/classes/UtilesApp.php';
	
	$sesion = new Sesion(array('DAT','COB'));
	$pagina = new Pagina($sesion);
	$formato_fecha = UtilesApp::ObtenerFormatoFecha($sesion);
	
	if( method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'SelectClienteAsuntoEspecial') == 1 )
	{
		require_once Conf::ServerDir().'/classes/AutocompletadorAsunto.php';
	}
	else
	{
		require_once Conf::ServerDir().'/classes/Autocompletador.php';
	}

	$params_array['codigo_permiso'] = 'DAT';
	$permisos = $sesion->usuario->permisos->Find('FindPermiso',$params_array); #tiene permiso de admin de datos
	if($permisos->fields['permitido'] && $accion == "eliminar")
	{
		$asunto = new Asunto($sesion);
		$asunto->Load($id_asunto);
		if(!$asunto->Eliminar()){
			$pagina->AddError($asunto->error);
		}
		else
		{
			$pagina->AddInfo(__('Asunto').' '.__('eliminado con éxito'));
			$buscar=1;
		}
	}
	
	$pagina->titulo = __('Listado de').' '.__('Asuntos');
$pagina->PrintTop($popup);

?>

<script type="text/javascript">
function GrabarCampo(accion,asunto,cobro,valor)
{
    var http = getXMLHTTP();
    if(valor)
        valor = 'agregar';
    else
        valor = 'eliminar';

    loading("Actualizando opciones");
    http.open('get', 'ajax_grabar_campo.php?accion=' + accion + '&codigo_asunto=' + asunto + '&id_cobro=' + cobro + '&valor=' + valor );
    http.onreadystatechange = function()
    {
        if(http.readyState == 4)
        {
            var response = http.responseText;
            var update = new Array();
            if(response.indexOf('OK') == -1)
            {
                alert(response);
                
                
            }
            offLoading();
        }
  };
    http.send(null);
}

function Listar( form, from )
{
	if(from == 'buscar')
		form.action = 'asuntos.php?buscar=1';
	else if(from == 'xls')
		form.action = 'asuntos_xls.php';
	else if(from == 'facturacion_xls')
		form.action = 'asuntos_facturacion_xls.php';
	else
		return false;
		
	form.submit();
	return true;
}

function EliminaAsunto(from,id_asunto)
{
<?
	if ($codigo_cliente)
		echo "var codigo_cliente = '&codigo_cliente=" . $codigo_cliente ."';";
	else
		echo "var codigo_cliente = '';";
?>
	var form = document.getElementById('form');
	if( from == 'agregar_cliente' )
		form.action = 'asuntos.php?buscar=1&accion=eliminar&id_asunto='+id_asunto+codigo_cliente+'&from=agregar_cliente&popup=1';
	else
		form.action = 'asuntos.php?buscar=1&accion=eliminar&id_asunto='+id_asunto+codigo_cliente+'&from='+from;
	form.submit();
	return true;
}
	//top.frames.iframe_asuntos.location.reload();
</script>
<?php
	if( method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'SelectClienteAsuntoEspecial') == 1 )
	{
		echo AutocompletadorAsunto::CSS();
	}
	else
	{
		echo Autocompletador::CSS();
	}
?>
<form method=post name='form' id='form'>
<input type="hidden" name="busqueda" value="TRUE">
<!-- Calendario DIV -->	
<div id="calendar-container" style="width:221px; position:absolute; display:none;">
	<div class="floating" id="calendar"></div>
</div>
<!-- Fin calendario DIV -->


<?  
if($id_cobro == "")
{
?>
		<table style="border: 0px solid black" width='100%'>
		<tr>
			<td></td>
			<td colspan=3 align=right>
				<a href=# onclick="nuevaVentana('Agregar_Asunto',730,600,'agregar_asunto.php?codigo_cliente=<?=$codigo_cliente?>&popup=1&motivo=agregar_proyecto');" title="<?=__('Agregar Asunto')?>"><img src="<?=Conf::ImgDir()?>/agregar.gif" border=0><?=__('Agregar').' '.__('Asunto')?></a>
			</td>
		</tr>
	</table>
<?
}
?>
<?
if($opc != "entregar_asunto" && $from != "agregar_cliente")
{
?>
<table width="90%"><tr><td>
<fieldset class="tb_base" width="100%">
<legend><?=__('Filtros')?></legend>	
<table style="border: 0px solid black" width='100%'>
		<tr>
			<td colspan=4>&nbsp;</td>
		</tr>
    <tr>
        <td align=right style="font-weight:bold;">
           <?=__('Activo')?> 
        </td>
        <td align=left colspan=3>
        	<?=Html::SelectQuery($sesion,"SELECT codigo_si_no, codigo_si_no FROM prm_si_no","activo",$activo,'','Todos','60')?>
				</td>
    </tr>
    <tr>
    	<td align=right style="font-weight:bold;">
          <?=__('Cliente')?>
      </td>
      <td nowrap align=left colspan=3>
<?php
	if( method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'SelectClienteAsuntoEspecial') == 1 )
	{
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
			echo InputId::Imprimir($sesion,"cliente","codigo_cliente_secundario","glosa_cliente", "codigo_cliente_secundario", $codigo_cliente_secundario,"","", 320);
		else
			echo InputId::Imprimir($sesion,"cliente","codigo_cliente","glosa_cliente", "codigo_cliente", $codigo_cliente,"","", 320);
	}
	else
	{
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
		{
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
				echo Autocompletador::ImprimirSelector($sesion, '', $codigo_cliente_secundario);
			else	
				echo Autocompletador::ImprimirSelector($sesion, $codigo_cliente);
		}
		else
		{
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
				echo InputId::Imprimir($sesion,"cliente","codigo_cliente_secundario","glosa_cliente", "codigo_cliente_secundario", $codigo_cliente_secundario,"","", 320);
			else
				echo InputId::Imprimir($sesion,"cliente","codigo_cliente","glosa_cliente", "codigo_cliente", $codigo_cliente,"","", 320);
		}
	}
	
?>
      </td>
    </tr>
    <tr>
        <td width=25% align=right style="font-weight:bold;">
            <?=__('Código asunto')?>
        </td>
        <td nowrap align=left colspan=4>
<?php
	if( method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'SelectClienteAsuntoEspecial') == 1 )
	{
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
			echo AutocompletadorAsunto::ImprimirSelector($sesion, '', $codigo_asunto_secundario, $codigo_cliente);
		else
			echo AutocompletadorAsunto::ImprimirSelector($sesion, $codigo_asunto, '', $codigo_cliente);
	}
	else
	{
?>
			<input onkeydown="if(event.keyCode==13) Listar(this.form, 'buscar');" type="text" name="codigo_asunto" size="15" value="<?=$codigo_asunto?>" onchange="this.value=this.value.toUpperCase();">
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			<b><?=__('Título asunto')?></b>
			<input onkeydown="if(event.keyCode==13)Listar(this.form, 'buscar');" type="text" name="glosa_asunto" size="30" value="<?=$glosa_asunto?>">
<?php
	}
?>
        </td>
    </tr>
    <tr>
        <td align=right style="font-weight:bold;">
            <?=__('Fecha creación')?> 
        </td>
        <td nowrap align=left colspan= 3>
					<input onkeydown="if(event.keyCode==13)Listar( this.form, 'buscar' );" type="text" name="fecha1" value="<?=$fecha1 ?>" id="fecha1" size="11" maxlength="10" />
					<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha1" style="cursor:pointer" />
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					<?=__('Hasta')?>
					<input onkeydown="if(event.keyCode==13)Listar( this.form, 'buscar' );" type="text" name="fecha2" value="<?=$fecha2 ?>" id="fecha2" size="11" maxlength="10" />
					<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha2" style="cursor:pointer" />
        </td>
    </tr>
    <tr>
        <td align=right style="font-weight:bold;">
					<?=__('Usuario')?>
        </td>
        <td align=left colspan=3>
					<?=Html::SelectQuery($sesion,"SELECT id_usuario, CONCAT_WS(' ',apellido1,apellido2,',',nombre) FROM usuario","id_usuario",$id_usuario,'','Todos','200')?>
        </td>
    <tr>
    <tr>
    	<td align=right style="font-weight:bold;">
					<?=__('Área')?>
        </td>
        <td align=left colspan=3>
					<?=Html::SelectQuery($sesion,"SELECT id_area_proyecto, glosa FROM prm_area_proyecto ORDER BY orden ASC","id_area_proyecto",$id_area_proyecto,'','Todos','200')?>
        </td>
    </tr>
    <tr>
    		<td>&nbsp;</td>
        <td align=left colspan=3>
					<input type="button" class=btn name="buscar" value="<?=__('Buscar')?>" onclick="Listar( this.form, 'buscar')">
					<input type="button" class=btn value="<?=__('Descargar listado a Excel')?>" onclick="Listar(this.form, 'xls')" >
					<input type="button" class=btn value="<?=__('Descargar Información Comercial a Excel')?>" onclick="Listar(this.form,'facturacion_xls')" >
        </td>
    </tr>
</table>
</fieldset>
</td></tr></table>

</form>
<script type="text/javascript">
Calendar.setup(
	{
		inputField	: "fecha1",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_fecha1"		// ID of the button
	}
);
Calendar.setup(
	{
		inputField	: "fecha2",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_fecha2"		// ID of the button
	}
);
</script>
<?
}
?>
<?
  	if ($busqueda)
			$link ="Opciones";
  	else	
				$link = __('Cobrar')." <br /><a href='asuntos.php?codigo_cliente=".$codigo_cliente."&opc=entregar_asunto&id_cobro=".$id_cobro."&popup=1&motivo=cobros&checkall=1'>".__('Todos')."</a>";
	
	
	if($checkall == '1')
	{
 		CheckAll($id_cobro,$codigo_cliente);
	}
	
	$where = 1;
	if($buscar || $opc == "entregar_asunto")
	{
      if($activo)
			{
				if($activo== 'SI') 
					$activo = 1;
				else $activo = 0;
        	$where .= " AND a1.activo = $activo ";
			}

			if($codigo_asunto != "")
			{
				if (( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ))
				{
					$where .= " AND a1.codigo_asunto_secundario Like '$codigo_asunto%'";
				}
				else
				{
					$where .= " AND a1.codigo_asunto Like '$codigo_asunto%'";
				}
			}
	
			if($glosa_asunto != "")
			{
				$nombre = strtr($glosa_asunto, ' ', '%' );
				$where .= " AND a1.glosa_asunto Like '%$glosa_asunto%'";
			}
	
			if($codigo_cliente || $codigo_cliente_secundario)
			{
				if ( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) && !$codigo_cliente )
				{
					$cliente = new Cliente($sesion);
					if($cliente->LoadByCodigoSecundario($codigo_cliente_secundario))
						$codigo_cliente=$cliente->fields['codigo_cliente'];
				}
				$where .= " AND cliente.codigo_cliente = '$codigo_cliente'";
			}
	
			if($opc == "entregar_asunto")
				$where .= " AND a1.codigo_cliente = '$codigo_cliente' ";
	
			if($fecha1 || $fecha2)
				$where .= " AND a1.fecha_creacion BETWEEN '".Utiles::fecha2sql($fecha1)."' AND '".Utiles::fecha2sql($fecha2)." 23:59:59'";
	    
			if($motivo == "cobros")
			{
				#$where .= " AND a1.activo='1' AND a1.cobrable = '1'";
				# Se cambia para que se pueda desmarcar un asunto no cobrable si es que venÃ­a premarcado en el contrato al generar el cobro
				$where .= " AND a1.activo='1'";
			}
			if($id_usuario)
				$where .= " AND a1.id_encargado = '$id_usuario' ";
			if($id_area_proyecto)
				$where .= " AND a1.id_area_proyecto = '$id_area_proyecto' ";
//Este query es mejorable, se podría sacar horas_no_cobradas y horas_trabajadas, pero ya no se podría ordenar por estos campos.
$query = "SELECT SQL_CALC_FOUND_ROWS *, a1.codigo_asunto, a1.codigo_asunto_secundario,a1.id_moneda, a1.activo,
					a1.fecha_creacion, (SELECT SUM(TIME_TO_SEC(duracion_cobrada))/3600
					FROM trabajo AS t2
					LEFT JOIN cobro on t2.id_cobro=cobro.id_cobro
					WHERE (cobro.estado IS NULL OR cobro.estado = 'CREADO' OR cobro.estado = 'EN REVISION')
					AND t2.codigo_asunto=a1.codigo_asunto
					AND t2.cobrable = 1
					) AS horas_no_cobradas,

					(SELECT SUM(TIME_TO_SEC(duracion))/3600
					FROM trabajo AS t3
					WHERE 
					t3.codigo_asunto=a1.codigo_asunto
					AND t3.cobrable = 1
					) AS horas_trabajadas,

					ca.id_cobro AS id_cobro_asunto,
					DATE_FORMAT( (SELECT MAX(fecha_fin) FROM cobro AS c1 WHERE c1.id_contrato = a1.id_contrato), '$formato_fecha') as fecha_ultimo_cobro
					FROM asunto AS a1
					LEFT JOIN cliente ON cliente.codigo_cliente=a1.codigo_cliente
					LEFT JOIN cobro_asunto AS ca ON (ca.codigo_asunto=a1.codigo_asunto AND ca.id_cobro='$id_cobro')
					WHERE $where
					GROUP BY a1.codigo_asunto";		
			if($orden == "")
				$orden = "a1.activo DESC, horas_no_cobradas DESC, glosa_asunto";
			if(stristr($orden,".") === FALSE)
				$orden = str_replace("codigo_asunto","a1.codigo_asunto",$orden);
			
			if( $motivo == "cobros" )
				$x_pag = 15;
			else
				$x_pag = 7;
			$b = new Buscador($sesion, $query, "Asunto", $desde, $x_pag, $orden);
			$b->formato_fecha = "$formato_fecha";
			$b->mensaje_error_fecha = "N/A";
			$b->nombre = "busc_gastos";
			$b->titulo = __('Listado de').' '.__('Asuntos');
			if (( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ))
			{
				$b->AgregarEncabezado("codigo_asunto_secundario",__('Código'),"align=left");
			}
			else
			{
				$b->AgregarEncabezado("codigo_asunto",__('Código'),"align=left");
			}
			$b->AgregarEncabezado("cliente.glosa_cliente",__('Cliente'),"align=left");
			$b->AgregarEncabezado("glosa_asunto", __('Asunto'), "align=left");
	#		$b->AgregarEncabezado("descripcion_asunto","Descripción","align=left");
	#		$b->AgregarEncabezado("horas_trabajadas","Horas trabajadas","","","SplitDuracion");
			$b->AgregarEncabezado("horas_trabajadas",__('Horas Trabajadas'),"align=left");
			$b->AgregarEncabezado("horas_no_cobradas",__('Horas a cobrar'),"align=left");
	#		$b->AgregarEncabezado("horas_no_cobradas","Horas no cobradas","","","SplitDuracion");
			$b->AgregarEncabezado("fecha_ultimo_cobro",__('Fecha último cobro'));
			$b->AgregarEncabezado("a1.fecha_creacion",__('Fecha de creación"'));
			if($permisos->fields['permitido'])
				$b->AgregarFuncion("$link",'Opciones',"align=center nowrap");
			$b->color_mouse_over = "#bcff5c";
	#		if($motivo == "cobros")
	#			$b->funcionTR = "funcionTR";
			$b->Imprimir();
	
	#		if($permisos->fields['permitido'])
	#		{
	#			echo("<br/><input type=button value=\"Agregar ".__('asunto')."\" onclick=\"document.location='agregar_asunto.php'\"/>");
	#		}
	}

	function Cobrable(& $fila)
    {
    /*    global $sesion;
        global $id_cobro;
        //echo $fila->fields['horas_no_cobradas']; 
		$checked = '';
        if($fila->fields['id_cobro_asunto'] == $id_cobro and $id_cobro != '')
            $checked = "checked";
			
		$id_moneda = $fila->fields['id_moneda'];
        $Check = "<input type='checkbox' $checked onchange=GrabarCampo('agregar_asunto','".$fila->fields['codigo_asunto']."','$id_cobro',this.checked,'$id_moneda')>";
        return $Check;
	*/
	    global $sesion;
        global $id_cobro;
        $checked = '';

        if($fila->fields['id_cobro_asunto'] == $id_cobro and $id_cobro != '')
            $checked = "checked";
            $id_moneda = $fila->fields['id_moneda'];
        $codigo_asunto = $fila->fields['codigo_asunto'];
        $Check = "<input type='checkbox' $checked onchange=\"GrabarCampo('agregar_asunto','$codigo_asunto',$id_cobro,this.checked)\">";
        return $Check;

    }

    function Opciones(& $fila)
    {
    	global $sesion;
			global $checkall;
			global $motivo, $from;	
			
			
			if($motivo == 'cobros')
			{
				return Cobrable($fila,$checkall);
			}
			$id_asunto = $fila->fields['id_asunto'];
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ) ) ) {
			return "<a target='_parent' href=agregar_asunto.php?id_asunto=$id_asunto><img src='".Conf::ImgDir()."/editar_on.gif' border=0 title=Editar actividad></a>"
        . "<a href='javascript:void(0);' onclick=\"if  (confirm('¿".__('Está seguro de eliminar el')." ".__('asunto')."?'))EliminaAsunto('".$from."',".$id_asunto.");\" ><img src='".Conf::ImgDir()."/cruz_roja_nuevo.gif' border=0 alt='Eliminar' /></a>";
    	}
    	else	{
    	return "<a target='_parent' href=agregar_asunto.php?id_asunto=$id_asunto><img src='".Conf::ImgDir()."/editar_on.gif' border=0 title=Editar actividad></a>"
        . "<a href='javascript:void(0);' onclick=\"if  (confirm('¿".__('Está seguro de eliminar el')." ".__('asunto')."?'))EliminaAsunto('".$from."',".$id_asunto.");\" ><img src='".Conf::ImgDir()."/cruz_roja.gif' border=0 alt='Eliminar' /></a>";
      }
    }

	function SplitDuracion($time)
	{
		list($h,$m,$s) = split(":",$time);
		if($h > 0 || $s > 0 || $m > 0)
			return $h.":".$m;
	}

	function funcionTR(& $asunto)
    {
        global $sesion;
		global $formato_fecha;
        static $i = 0;

        if($i % 2 == 0)
            $color = "#dddddd";
        else
            $color = "#ffffff";
        
        $fecha = Utiles::sql2fecha($asunto->fields['fecha_ultimo_cobro'],$formato_fecha, "N/A");
        $html .= "<tr bgcolor=$color style=\"border-right: 1px solid #409C0B; border-left: 1px solid #409C0B; border-bottom: 1px solid #409C0B; \">";
        $html .= "<td align=center>".$asunto->fields['codigo_asunto']."</td>";
        $html .= "<td align=center>".$asunto->fields['glosa_asunto']."</td>";
        $html .= "<td align=center>".$fecha."</td>";
        $html .= "<td align=center>".Cobrable($asunto)."</td>";
        $html .= "</tr>";
        $i++;
        return $html;
    }
	
	if( method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'SelectClienteAsuntoEspecial') == 1 )
	{
		if( empty($_REQUEST["id_cobro"]) && $from != 'agregar_cliente' )
		{
			echo(AutocompletadorAsunto::Javascript($sesion,false));
		}
	}
	else
	{
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
		{ 
			if( empty($_REQUEST["id_cobro"]) && $from != 'agregar_cliente' )
			{
				echo(Autocompletador::Javascript($sesion,false));
			}		
		}
	}
    echo(InputId::Javascript($sesion));
	$pagina->PrintBottom($popup);
?>
