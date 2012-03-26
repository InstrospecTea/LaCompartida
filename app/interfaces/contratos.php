<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
	require_once Conf::ServerDir().'/../app/classes/Contrato.php';

	$sesion = new Sesion(array('COB','DAT'));
	$pagina = new Pagina($sesion);

	$params_array['codigo_permiso'] = 'COB';
	$permisos = $sesion->usuario->permisos->Find('FindPermiso',$params_array); #tiene permiso de Cobranza 

	if( !$permisos->fields['permitido'] )
		die(__('No tienes privilegios suficientes para ver esta sección.'));

	if($permisos->fields['permitido'] && $accion == "eliminar")
	{
		$contrato = new Contrato($sesion);
		$contrato->LoadById($id_contrato);
		if(!$contrato->Eliminar())
			$pagina->AddError($contrato->error);
		else
			$pagina->AddInfo( __('Contrato Eliminado con éxito') );
	}

	$pagina->titulo = __('Listado de Contratos');
	$pagina->PrintTop($popup);
?>
<script language='javascript'>
function Refrescar()
{
//todo if $motivo=="cobros",$motivo=="horas"
<?
	if($desde)
		echo "var pagina_desde = '&desde=".$desde."';";
	else
		echo "var pagina_desde = '';";
	if($codigo_cliente)
		echo "var codigo_cliente = '". $codigo_cliente . "';";
	else
		echo "var codigo_cliente = 0;"
?>
	
	var url = "contratos.php?codigo_cliente="+codigo_cliente+"&popup=1&buscar=1&activo=SI";
	self.location.href= url;
}
</script>
<form method=post>
<input type="hidden" name="busqueda" value="TRUE">
<?
if($mostrar_filtros)
{
?>

<fieldset>
<legend><?=__('Filtros')?></legend>	
<table style="border: 0px solid black" width=100%>
    <tr>
        <td width=50% align=right style="font-weight:bold;">
           <?=__('Activo')?>
        </td>
        <td align=left>
        	<?=Html::SelectQuery($sesion,"SELECT codigo_si_no, codigo_si_no FROM prm_si_no","activo",$activo,'','Todos','60')?>
		</td>
        <td width=50% align=right style="font-weight:bold;">
            <?=__('Cliente')?>
        </td>
        <td nowrap align=left>
            <input type="text" name="glosa_cliente" size="17" value="<?=$glosa_cliente?>">
        </td>
    </tr>
    <tr>
        <td width=50% align=right style="font-weight:bold;">
            <?=__('Correlativo').' '.__('contrato')?>
        </td>
        <td nowrap align=left>
        	<input type="text" name="id_contrato" size="7" maxlength="5" value="<?=$id_contrato?>" onchange="this.value=this.value.toUpperCase();">
		</td>
        <td width=50% align=right style="font-weight:bold;">
            <?=__('Titulo').' '.__('contrato')?>
        </td>
        <td nowrap align=left>
            <input type="text" name="glosa_contrato" size="35" value="<?=$glosa_contrato?>">
        </td>
    </tr>
    <tr>
        <td width=50% align=right style="font-weight:bold;">
            <?=__('Fecha creacion').' '.__('entre')?> 
        </td>
        <td nowrap align=left colspan = 3>
           <?= Html::PrintCalendar("fecha1", $fecha1,"false") ?><?= Html::PrintCalendar("fecha2", $fecha2,"false") ?>
        </td>
    </tr>
        <td width=50% align=right style="font-weight:bold;">
           <?=__('Usuario').' '.__('Responsable')?>
        </td>
        <td align=left colspan =3>
            <?=Html::SelectQuery($sesion,"SELECT id_usuario, CONCAT_WS(', ',apellido1,nombre) as nombre FROM usuario ORDER BY nombre","id_usuario",$id_usuario,'','Todos','200')?>
        </td>
    <tr>
    		<td></td>
        <td align=left>
            <input type=submit class=btn name=buscar value=<?=__('Buscar')?>>
        </td>
    </tr>
	<tr>
        <td align=right colspan=4>
            <img src="<?=Conf::ImgDir()?>/agregar.gif" border=0> <a href='agregar_contrato.php' title="Agregar <?=__('contrato');?>">Agregar <?=__('contrato')?></a>

        </td>
    </tr>
	<tr>
        <td colspan=2 align=right>
        </td>
    </tr>
</table>
<?
}
?>
</form>
</fieldset>
<?
if($codigo_cliente)
{
?>

<table width=100%>
<tr>
    <td align=right>
        <img src="<?=Conf::ImgDir()?>/agregar.gif" border=0> <a href=# onclick="nuovaFinestra('Agregar_Contrato',730,600,'agregar_contrato.php?popup=1&codigo_cliente=<?=$codigo_cliente?>');" title="<?=__('Agregar contrato')?>"><?=__('Agregar contrato')?></a>
    </td>
</tr>
</table>
<?
}
  	if ($busqueda)
		$link ="Opciones";
	
		$where = 1;
    if($buscar)
    {
			if($activo)
				$where .= " AND contrato.activo = '$activo' ";

		if($id_contrato != "")
		{		
			$where .= " AND contrato.id_contrato Like '$id_contrato%'";
		}

		if($glosa_contrato != "")
		{
			$nombre = strtr($glosa_contrato, ' ', '%' );
			$where .= " AND glosa_contrato Like '%$glosa_contrato%'";
		}

		if($glosa_cliente != "")
		{
			$nombre = strtr($glosa_cliente, ' ', '%' );
			$where .= " AND cliente.glosa_cliente Like '%$nombre%'";
		}

		if($codigo_cliente != "")
			$where .= " AND contrato.codigo_cliente = '$codigo_cliente' ";

		if($fecha1 || $fecha2)
			$where .= " AND contrato.fecha_creacion BETWEEN '$fecha1' AND '$fecha2'";
    
		if($id_usuario)
			$where .= " AND contrato.id_usuario_responsable = '$id_usuario' ";
			
			#a1.id_encargado

		$query = "SELECT SQL_CALC_FOUND_ROWS contrato.id_contrato, contrato.codigo_cliente, cliente.glosa_cliente, 
							GROUP_CONCAT('<li>', glosa_asunto SEPARATOR '</li><br>')  as asuntos, contrato.forma_cobro, CONCAT(simbolo, ' ', contrato.monto) AS monto_total,  contrato.activo,
							(SELECT MAX(fecha_fin) FROM cobro WHERE cobro.id_contrato = contrato.id_contrato AND cobro.estado <> 'CREADO' AND cobro.estado <> 'EN REVISION') as fecha_ultimo_cobro
                    FROM contrato
                    LEFT JOIN asunto ON asunto.id_contrato=contrato.id_contrato
                    JOIN cliente ON cliente.codigo_cliente=contrato.codigo_cliente
                    JOIN prm_moneda  ON (prm_moneda.id_moneda=contrato.id_moneda)
                    WHERE $where
                    GROUP BY contrato.id_contrato";
	
		#, IF(contrato.es_periodico = 'NO', periodo_fecha_inicio, DATE_ADD(fecha_ultimo_cobro, INTERVAL contrato.periodo_intervalo contrato.periodo_unidad)) as fecha_prox_cobro
		if($orden == "")
			$orden = "contrato.codigo_cliente";

		$x_pag = 7;
		$b = new Buscador($sesion, $query, "Objeto", $desde, $x_pag, $orden);
		$b->mensaje_error_fecha = "N/A";
		$b->nombre = "";
		$b->titulo = __('Listado de').' '.__('Contratos');
		$b->AgregarEncabezado("glosa_cliente",__('Cliente'),"align=left");
		$b->AgregarEncabezado("asuntos",__('Asunto'), "align=left");
		$b->AgregarEncabezado("forma_cobro",__('Modalidad'),"align=left");
		$b->AgregarEncabezado("monto_total",__('Monto total'),"align=left");
		$b->AgregarEncabezado("fecha_ultimo_cobro",__('Fecha último cobro'));
		$b->AgregarEncabezado("fecha_prox_cobro",__('Fecha Prox. Cobro'),"align=left");

		if($permisos->fields['permitido'])
			$b->AgregarFuncion("$link",'Opciones',"align=center nowrap");

		$b->color_mouse_over = "#DF9862";
		
		$b->Imprimir();
	}
	function Cobrable(& $fila)
	{
        global $id_cobro;
        $checked = '';

        if($fila->fields['id_cobro_asunto'] == $id_cobro and $id_cobro != '')
            $checked = "checked";
            $id_moneda = $fila->fields['id_moneda'];
        $Check = "<input type='checkbox' $checked onchange=GrabarCampo('agregar_asunto','".$fila->fields['codigo_asunto']."','$id_cobro',this.checked,'$id_moneda')>";
        return $Check;

	}

    function Opciones(& $fila)
    {
		global $checkall;
		global $motivo;
		
		
		
		
		if($motivo == 'cobros')
		{
			return Cobrable($fila,$checkall);
		}
		$id_contrato = $fila->fields['id_contrato'];
        return 
		"<a href=# onclick=\"nuovaFinestra('Editar_Contrato',800,600,'agregar_contrato.php?id_contrato=$id_contrato&popup=1');\" title='Editar Contrato'><img src='".Conf::ImgDir()."/editar_on.gif' border=0 title=Editar Contrato></a>"
		. "<a href='javascript:void
		(0);' onclick=\"if(confirm('¿Desea generar este cobro individualmente?'))nuevaVentana('Cobrar',750,660,'genera_cobros_guarda.php?id_contrato=$id_contrato&individual=true');\"><img src='".Conf::ImgDir()."/coins_16.png' border=0 title=Cobrar></a>";
			#<a target='_top' onclick=\"return confirm('¿Está seguro de eliminar este contrato?');\" href=contratos.php?id_contrato=$id_contrato&accion=eliminar&buscar=1&mostrar_filtros=1><img src='".Conf::ImgDir()."/cruz_roja.gif' border=0 alt='Eliminar' /></a>
    }
    echo(InputId::Javascript($sesion));
	$pagina->PrintBottom($popup);
?>
