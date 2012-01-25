<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/classes/PaginaCobro.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/Asunto.php';
	require_once Conf::ServerDir().'/classes/Cobro.php';
	require_once Conf::ServerDir().'/classes/Cliente.php';

	$sesion = new Sesion(array('COB'));
	$pagina = new PaginaCobro($sesion);
	$pagina->titulo = __('Emitir') . ' ' . __('Cobro') . __(' :: Seleccion de Gastos');

	$cobro = new Cobro($sesion);
	if(!$cobro->Load($id_cobro))
		$pagina->FatalError(__('Cobro inv�lido'));
	$cliente = new Cliente($sesion);
	$cliente->LoadByCodigo($cobro->fields['codigo_cliente']);
	$nombre_cliente = $cliente->fields['glosa_cliente'];
	$pagina->titulo = __('Emitir') . ' ' . __('Cobro') . __(' :: Selecci�n de Gastos #').$id_cobro.__(' ').$nombre_cliente;
	if($cobro->fields['estado'] <> 'CREADO' && $cobro->fields['estado'] <> 'EN REVISION')
		$pagina->Redirect("cobros6.php?id_cobro=".$id_cobro."&popup=1&contitulo=true");	

    if($opc=="siguiente")
        $pagina->Redirect("cobros5.php?id_cobro=".$id_cobro."&popup=1&contitulo=true");
    else if($opc=="anterior"){
		if(!empty($cobro->fields['incluye_honorarios']))
			$pagina->Redirect("cobros_tramites.php?id_cobro=".$id_cobro."&popup=1&contitulo=true");
		else
			$pagina->Redirect("cobros2.php?id_cobro=".$id_cobro."&popup=1&contitulo=true");
	}
        
        $solo_gastos = $cobro->fields['incluye_gastos'] && !$cobro->fields['incluye_honorarios'];
        
        if ($solo_gastos && $opc == "boton_buscar") {
            $cobro->Edit('fecha_ini', empty($fecha_ini) ? "0000-00-00" : date("Y-m-d", strtotime($fecha_ini)));
            $cobro->Edit('fecha_fin', empty($fecha_fin) ? "0000-00-00" : date("Y-m-d", strtotime($fecha_fin)));
        } else {
            $fecha_ini = $cobro->fields['fecha_ini'];
            $fecha_fin = $cobro->fields['fecha_fin'];
        }
        
	$cobro->Edit('etapa_cobro','3');
	$cobro->Write();

	if($orden == "")
		$orden = "fecha";
	
	$where = "1";

	if($id_cobro)
	{            
		$cobro->LoadAsuntos();
                $query_asuntos = implode("','", $cobro->asuntos);
                
		$codigo_cliente = $cobro->fields['codigo_cliente'];
		$where .= " AND (cta_corriente.codigo_cliente= '$codigo_cliente' OR cta_corriente.codigo_asunto IN ('$query_asuntos')) ";
		$where .= " AND cta_corriente.cobrable = 1 ";
		$where .= " AND (cobro.estado is NULL OR cobro.estado = 'CREADO' OR cobro.estado = 'EN REVISION')";
		$where .= " AND (cta_corriente.incluir_en_cobro = 'SI')";
		
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'SepararGastosPorAsunto') ) || ( method_exists('Conf', 'SepararGastosPorAsunto') && Conf::SepararGastosPorAsunto() ) )
			{
				$join_cobro_asunto = " JOIN cobro_asunto ON asunto.codigo_asunto = cobro_asunto.codigo_asunto ";
				$where .= " AND cobro_asunto.id_cobro = '$id_cobro' ";
			}
		else
			$join_cobro_asunto = " ";
                
                if (!$solo_gastos) {
                    $where = "( $where OR cta_corriente.id_cobro = '$id_cobro' )";
                }
	}

	$query = "SELECT DISTINCT SQL_CALC_FOUND_ROWS 
                          cta_corriente.*, 
                          usuario.*, 
                          prm_moneda.*, 
                          asunto.glosa_asunto, 
                          egreso, 
                          cta_corriente.monto_cobrable, 
                          ingreso 
                    FROM cta_corriente  
                    LEFT JOIN asunto USING( codigo_asunto )
                    LEFT JOIN usuario ON usuario.id_usuario=cta_corriente.id_usuario 
                    LEFT JOIN prm_moneda ON cta_corriente.id_moneda=prm_moneda.id_moneda 
                    LEFT JOIN cobro ON cobro.id_cobro=cta_corriente.id_cobro
                    $join_cobro_asunto 
                    WHERE $where AND (egreso > 0 OR ingreso > 0)";
        
                if($check_gasto == 1 && isset($cobro))	//Check_trabajo vale 1 cuando aprietan boton buscar
		{
			$query2 = "UPDATE cta_corriente SET id_cobro = NULL WHERE id_cobro='$id_cobro'";
			$resp = mysql_query($query2, $sesion->dbh) or Utiles::errorSQL($query2,__FILE__,__LINE__,$sesion->dbh);
			$lista_gastos = new ListaGastos($sesion,'',$query);
			for($x=0;$x<$lista_gastos->num;$x++)
			{
				$gasto = $lista_gastos->Get($x);
				$emitir_gasto = new Gasto($sesion);
				$emitir_gasto->Load($gasto->fields['id_movimiento']);
				$emitir_gasto->Edit('id_cobro',$id_cobro);
				$emitir_gasto->Write();
			}
                        $cobro->GuardarCobro();
		}
        
	#echo $query;

	$pagina->PrintTop($popup);
	
	if($popup)
	{
?>
		<table width="100%" border="0" cellspacing="0" cellpadding="2">
			<tr>
				<td valign="top" align="left" class="titulo" bgcolor="<?=(method_exists('Conf','GetConf')?Conf::GetConf($sesion,'ColorTituloPagina'):Conf::ColorTituloPagina())?>">
					<?=__('Emitir') . ' ' . __('Cobro') . __(' :: Selecci�n de Gastos #').$id_cobro.__(' ').$nombre_cliente;?>
				</td>
			</tr>
		</table>
		<br>
<?
	} 
	$pagina->PrintPasos($sesion,3,'',$id_cobro, $cobro->fields['incluye_gastos'], $cobro->fields['incluye_honorarios']);
?>

<script language="javascript" type="text/javascript">
<!-- //

function GrabarCampo(accion,id_gasto,id_cobro, valor)
{
	var http = getXMLHTTP();
	if(valor)
		valor = '1';
	else
		valor = '0';

	loading("Actualizando opciones");
	http.open('get', 'ajax_grabar_campo.php?accion=' + accion +'&id_gasto=' + id_gasto + '&id_cobro=' + id_cobro + '&valor=' + valor);
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

function Refrescar()
{
<?
	if($desde)
		echo "var pagina_desde = '&desde=".$desde."';";
	else
		echo "var pagina_desde = '';";
	if($orden)
		echo "var orden = '&orden=".$orden."';";
	else
		echo "var orden = '';";
?>

	var id_cobro = document.forms["cobro"].elements["id_cobro"].value;
	var url = "cobros4.php?id_cobro="+id_cobro+"&popup=1&contitulo=true";
	self.location.href= url; 
}

// Basado en http://snipplr.com/view/1696/get-elements-by-class-name/
function getElementsByClassName(classname)
{
	node = document.getElementsByTagName("body")[0];
	var a = [];
	var re = new RegExp('\\b' + classname + '\\b');
	var els = node.getElementsByTagName("*");
	for(var i=0,j=els.length; i<j; i++)
		if(re.test(els[i].className))a.push(els[i]);
	return a;
}
// Funci�n para seleccionar todos las filas para editar, basada en la de phpMyAdmin
function seleccionarTodo(valor, sinc)
{
	var rows = getElementsByClassName('buscador')[0].getElementsByTagName('tr');
	valores = "";
	var checkbox;
	for (var i=0; i<rows.length; i++)
	{
		checkbox = rows[i].getElementsByTagName( 'input' )[0];
		if ( checkbox && checkbox.type == 'checkbox' && checkbox.disabled == false) {
			checkbox.checked = valor
			valores += ( valores.length > 0 ? "||" : "" ) + checkbox.id  ;
		}
	}
	GrabarTodosCampos(valores, <?php echo $id_cobro; ?>, valor, sinc );
	return true;
}

function GrabarTodosCampos(id_gastos,id_cobro, valor, sinc)
{
        sinc = (sinc == undefined || sinc == null) ? true : false;
	var http = getXMLHTTP();
	if(valor)
		valor = '1';
	else
		valor = '0';

	loading("Actualizando opciones");
	http.open('get', 'ajax_grabar_campo.php?accion=varios_cobrables&id_gastos=' + id_gastos + '&id_cobro=' + id_cobro + '&valor=' + valor, sinc);
	http.onreadystatechange = function()
	{
		if(http.readyState == 4)
		{
			var response = http.responseText;
			if(response.indexOf('OK') == -1)
			{
				alert(response);
			}
			offLoading();
		}
	};
	http.send(null);
}
// -->
</script>

    <form method=post name=cobro>
    <input type=hidden name=opc>
    <input type=hidden name=id_cobro value=<?=$id_cobro?>>
    <table width=100%>
    <tr>
        <td align=left><input type=button class=btn value="<?=__('<< Anterior')?>" onclick="this.form.opc.value = 'anterior'; this.form.submit();">
        <td align=right><input type=button class=btn value="<?=__('Siguiente >>')?>" onclick="this.form.opc.value = 'siguiente'; this.form.submit();"></td>
    </tr>
    </table>
    </form>

<?php if ($solo_gastos) { ?>
<div style="width:90%">
    <fieldset class="tb_base" width="100%" style="border: 1px solid #BDBDBD;">
        <legend> <?php echo __('Filtros') ?></legend>
        <form method="post" onsubmit="seleccionarTodo(false, 1);">
            <table>
                <tr>
                    <td align='right' colspan='1'>
                        <?php echo __('Fecha desde') ?>:
                    </td>
                    <td align='left' colspan='3'>
                        <input type="hidden" name="check_gasto" id="check_gasto" value='' />
                        <input type="text" name="fecha_ini" value="<?php echo (empty($fecha_ini) or $fecha_ini == "0000-00-00") ? "" : date("d-m-Y", strtotime($fecha_ini)) ?>" id="fecha_ini" size="11" maxlength="10" />
                        <img src="<?php echo Conf::ImgDir() ?>/calendar.gif" id="img_fecha_ini" style="cursor:pointer" />&nbsp;&nbsp;&nbsp;&nbsp;
                        <?php echo __('Fecha hasta') ?>:&nbsp;
                        <input type="text" name="fecha_fin" value="<?php echo (empty($fecha_fin) or $fecha_fin == "0000-00-00") ? "" : date("d-m-Y", strtotime($fecha_fin)) ?>" id="fecha_fin" size="11" maxlength="10" />
                        <img src="<?php echo Conf::ImgDir() ?>/calendar.gif" id="img_fecha_fin" style="cursor:pointer" />
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td colspan='3' align='left'>
                        <input type='submit' class=btn' value='<?php echo __('Buscar') ?>' onclick="this.form.check_gasto.value=1" />
                    </td>
                </tr>
            </table>
            <input type="hidden" name="opc" value="boton_buscar" />
        </form>
    </fieldset>
</div>
<?php } ?>

<?php
	$b = new Buscador($sesion, $query, "Objeto", $desde, $x_pag, $orden);
	$b->mensaje_sin_resultados = str_replace('%s', __('asuntos'), __('No existen gastos por cobrar en los %s seleccionados'));
	$b->nombre = "busc_gastos";
	//	$b->titulo = "Gastos por Asuntos";
	//    	$b->AgregarFuncion("Nombre","Nombre");
	$b->AgregarEncabezado("nombre",__('Nombre'), "align=center");
	$b->AgregarEncabezado("fecha",__('Fecha'));
	$b->AgregarEncabezado("glosa_asunto", __('Asunto'), "align=center");
	$b->AgregarEncabezado("descripcion",__('Descripci�n'));
	$b->AgregarEncabezado("egreso",__('Egreso'), "align=center");
	$b->AgregarEncabezado("ingreso",__('Ingreso'), "align=center");
	$b->AgregarEncabezado("cobrable",__('Cobrable'), "align=center");
	$b->funcionTR = "funcionTR";
	//    $b->AgregarFuncion("Monto","Monto","align=center");
	//    $b->AgregarFuncion("Cobrable","Cobrable","align=center");
	$b->Imprimir();

	if(isset($cobro) || $opc == 'buscar')
	{
?>
	<center>
		<a href="#" onclick="seleccionarTodo(true); return false;">Seleccionar todo</a>
		&nbsp;&nbsp;&nbsp;&nbsp;
		<a href="#" onclick="seleccionarTodo(false); return false;">Desmarcar todo</a>
	</center>
<?php
	}

	function Nombre(& $fila)
	{
		return $fila->fields[apellido1].", ".$fila->fields[nombre];
	}
	function Monto(& $fila)
	{
		return $fila->fields[egreso] > 0 ? $fila->fields[simbolo] . " " .number_format($fila->fields[monto_cobrable],2,",",".") : '-';
	}
	function Monto_ingreso(& $fila)
	{
		return $fila->fields[ingreso] > 0 ? $fila->fields[simbolo] . " " .number_format($fila->fields[monto_cobrable],2,",",".") : '-';
	}
	function Cobrable(& $fila)
	{
			global $id_cobro;
			$checked = '';
			#if($fila->fields['cobrable_actual'])
			#	$checked = "checked";
			
			if($fila->fields['id_cobro'] == $id_cobro)
		$checked = "checked";
	
			$Check = "<input type='checkbox' id='".$fila->fields['id_movimiento']."' $checked onchange=GrabarCampo('cobrable','".$fila->fields['id_movimiento']."','".$id_cobro."',this.checked)>";
		return $Check;
	}
function Opciones(& $fila)
		{
			global $where;
			$id_gasto = $fila->fields['id_movimiento'];
			$prov = $fila->fields[egreso] != '' ? 'false' : 'true';
			$html_opcion = "<a href='javascript:void(0)' onclick=\"nuevaVentana('Editar_Gasto',730,580,'agregar_gasto.php?id_gasto=$id_gasto&popup=1&prov=$prov', 'top=100, left=155');\" ><img src='".Conf::ImgDir()."/editar_on.gif' border=0 title=Editar></a>&nbsp;";
			return $html_opcion;
		}
	function funcionTR(& $gasto)
	{
		static $i = 0;
	
		if($i % 2 == 0)
		$color = "#dddddd";
		else
		$color = "#ffffff";
		$formato_fecha = "%d/%m/%y";
		$fecha = Utiles::sql2fecha($gasto->fields[fecha],$formato_fecha);
		$html .= "<tr bgcolor=$color style=\"border-right: 1px solid #409C0B; border-left: 1px solid #409C0B; border-bottom: 1px solid #409C0B; \">";
		$html .= "<td align=center>".Nombre($gasto)."</td>";
		$html .= "<td align=center>".$fecha."</td>";
		$html .= "<td align=center>".$gasto->fields['glosa_asunto']."</td>";
		$html .= "<td align=center>".$gasto->fields['descripcion']."</td>";
		$html .= "<td align=center>".Monto($gasto)."</td>";
		$html .= "<td align=center>".Monto_ingreso($gasto)."</td>";
		$html .= "<td align=center>".Cobrable($gasto)."&nbsp".Opciones($gasto)."</td>";
		$html .= "</tr>";
		$i++;
		return $html;
	}
	
	$pagina->PrintBottom($popup);
?>
<script type="text/javascript">
<?php if ($solo_gastos && $opc == "boton_buscar") { ?>
seleccionarTodo(true);
<?php } ?>

Calendar.setup({
    inputField : "fecha_ini",
    ifFormat : "%d-%m-%Y",
    button : "img_fecha_ini"
});
Calendar.setup({
    inputField : "fecha_fin",
    ifFormat : "%d-%m-%Y",
    button : "img_fecha_fin"
});
</script>
