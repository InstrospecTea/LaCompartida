<?
    require_once dirname(__FILE__).'/../conf.php';
    require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
    require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
    require_once Conf::ServerDir().'/../fw/classes/Html.php';
    require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
    require_once Conf::ServerDir().'/../app/classes/Debug.php';
    require_once Conf::ServerDir().'/classes/InputId.php';
    require_once Conf::ServerDir().'/classes/Trabajo.php';
    require_once Conf::ServerDir().'/classes/Cobro.php';

    # revisores y administradores
    $sesion = new Sesion(array('COB','DAT'));
    $pagina = new Pagina($sesion);
    $id_usuario = $sesion->usuario->fields['id_usuario'];

		$params_array['codigo_permiso'] = 'COB';
		$permisos = $sesion->usuario->permisos->Find('FindPermiso',$params_array); #tiene permiso de Cobranza

		if( !$permisos->fields['permitido'] )
		die(__('No tienes privilegios suficientes para ver esta sección.'));

    $pagina->titulo = __('Revisar cobros');
    $pagina->PrintTop($popup);

    if($opc == "eliminar")
    {
			$cobro = new Cobro($sesion);
			if($cobro->Load($id_cobro))
			{
		  	if($cobro->Eliminar())
		  		$pagina->AddInfo( __('Cobro eliminado con éxito') );
				else
		    	$pagina->AddInfo( __('No es posible Eliminar el Cobro. Es posible que ya se encuentre eliminado') );
			}
    }

    #solo se muestra la cuenta corriente y otros si es cobranza
    if( ! $permisos->fields['permitido'] )
        $where = "AND cobro.id_usuario = $id_usuario";


	#if($opc == "buscar")
    #{

		$where = 1;
		if($id_cobro_sql)
			$where .= " AND cobro.id_cobro = '$id_cobro_sql' ";
		else
		{
			if($codigo_cliente != "")
				$where .= " AND cliente.codigo_cliente = '$codigo_cliente'";
			if($factura)
				$where .= " AND cobro.documento = '$factura' ";
			if($fecha1 || $fecha2)
				$where .= " AND fecha_cobro BETWEEN '".Utiles::fecha2sql($fecha1)."' AND '".Utiles::fecha2sql($fecha2).' 23:59:59'."' ";
			if($estado_cobro)
				$where.= " AND (cobro.estado = '$estado_cobro') ";
			if ($fecha_creacion1 || $fecha_creacion2)
				$where .= " AND cobro.fecha_creacion BETWEEN '".Utiles::fecha2sql($fecha_creacion1)."' AND '".Utiles::fecha2sql($fecha_creacion2).' 23:59:59'."' ";
		}

	    $query = "SELECT SQL_CALC_FOUND_ROWS cobro.*, cliente.*, CONCAT_WS(' ',prm_moneda.simbolo,cobro.monto) AS cobro_monto
                FROM cobro
                LEFT JOIN cliente ON cliente.codigo_cliente=cobro.codigo_cliente
				JOIN prm_moneda on cobro.id_moneda = prm_moneda.id_moneda
                WHERE 1 AND $where";
	    if($orden == "")
	    	$orden = "fecha_emision, cobro.fecha_creacion DESC";
			if(!$desde)
    		$desde = 0;

    	$x_pag = 10;
    	$b = new Buscador($sesion, $query, "Trabajo", $desde, $x_pag, $orden);
    	#$b->no_pages = true;
    	$b->color_mouse_over = "#62A9DF";
    	$b->titulo = __('Listado de cobros');
    	$b->AgregarEncabezado("id_cobro",__('N° Cobro'));
    	$b->AgregarEncabezado("fecha_emision",__('Fecha Emisión'));
			$b->AgregarEncabezado("fecha_creacion",__('Fecha creación'),"align=left");
    	$b->AgregarEncabezado("glosa_cliente",__('Cliente'), "align=left");
    	$b->AgregarEncabezado("estado",__('Estado'), "align=left");
    	$b->AgregarEncabezado("cobro_monto",__('Monto'),"align=right");
    	$b->AgregarFuncion("","Opciones","align=right","nowrap");
    	#$b->Imprimir();
	#}
?>
<script type="text/javascript">

//refrescar el listado si se hizo un cambio en un popup
function Refrescar()
{
//todo if $motivo=="cobros",$motivo=="horas"
<?
	if($desde)
		echo "var pagina_desde = '&desde=".$desde."';";
	else
		echo "var pagina_desde = '';";
?>
	var url = "lista_cobros.php?codigo_cliente=<?=$codigo_cliente?>&popup=1&opc=buscar&no_mostrar_filtros=1"+pagina_desde;
	self.location.href= url;
}
//fin refrescar

//Extend the scal library to add draggable calendar support.
//This script block can be added to the scal.js file.
Object.extend(scal.prototype,
{
    toggleCalendar: function()
    {
        var element = $(this.options.wrapper) || this.element;
        this.options[element.visible() ? 'onclose' : 'onopen'](element);
        this.options[element.visible() ? 'closeeffect' : 'openeffect'](element, {duration: 0.5});
    },

    isOpen: function()
    {
        return ( $(this.options.wrapper) || this.element).visible();
    }
});

//this is a global variable to have only one instance of the calendar
var calendar = null;

//@element   => is the <div> where the calender will be rendered by Scal.
//@input     => is the <input> where the date will be updated.
//@container => is the <div> for dragging.
//@source    => is the img/button which raises up the calender, the script will locate the calenar over this control.
function showCalendar(element, input, container, source)
{
    if (!calendar)
    {
        container = $(container);
        //the Draggable handle is hard coded to "rtop" to avoid other parameter.
        new Draggable(container, {handle: "rtop", starteffect: Prototype.emptyFunction, endeffect: Prototype.emptyFunction});

        //The singleton calendar is created.
        calendar = new scal(element, $(input),
        {
            updateformat: 'dd-mm-yyyy',
            closebutton: '&nbsp;',
            wrapper: container
        });
    }
    else
    {
        calendar.updateelement = $(input);
    }

    var date = new Date($F(input));
    calendar.setCurrentDate(isNaN(date) ? new Date() : date);

    //Locates the calendar over the calling control  (in this example the "img").
    if (source = $(source))
    {
        Position.clone($(source), container, {setWidth: false, setHeight: false, offsetLeft: source.getWidth() + 2});
    }

    //finally show the calendar =)
    calendar.openCalendar();
};

//<![CDATA[
document.observe('dom:loaded', function() {
	//new Tip('c_edit', 'Continuar con el cobro', {offset: {x:-2, y:5}});
});
//]]>

</script>
<?
	if(!$no_mostrar_filtros)
	{
?>
<form method=post name="form_cobros" id="form_cobros">
<input type=hidden name=opc value=buscar>
<!-- Calendario DIV -->
<div id="calendar-container" style="width:221px; position:absolute; display:none;">
	<div class="floating" id="calendar"></div>
</div>
<!-- Fin calendario DIV -->

<fieldset>
<legend><?=__('Filtros')?></legend>
<table style="border: 0px solid black" width='100%'>
	<tr>
	    <td align=right width='30%'>
	        <?=__('Nombre Cliente')?>
	    </td>
	    <td nowrap colspan=3 align=left>
	    	<?= InputId::Imprimir($sesion,"cliente","codigo_cliente","glosa_cliente", "codigo_cliente", $codigo_cliente,"","", 300) ?>
	    </td>
	</tr>
	<tr>
			<td align=right>
	     	<?=__('Cobro')?>
	    </td>
	    <td align=left>
				<input type='text' name='id_cobro_sql' id='id_cobro_sql' value='<?=$id_cobro_sql ?>' size='8'>
	    </td>
	    <td align=right>
	     	<?=__('Factura')?>
	    </td>
	    <td align=left>
				<input type='text' name='factura' id='factura' value='<?=$factura?>' size=8>
	    </td>
	</tr>
	<tr>
	    <td align=right>
	        <?=__('Fecha Emisión')?>
	    </td>
	    <td nowrap align=left>
	    	<input type="text" name="fecha1" value="<?=$fecha1 ?>" id="fecha1" size="11" maxlength="10" />
				<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha1" style="cursor:pointer" />
				<br>
				<input type="text" name="fecha2" value="<?=$fecha2 ?>" id="fecha2" size="11" maxlength="10" />
				<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha2" style="cursor:pointer" />
	    </td>
	    <td align=right>
	        <?=__('Fecha creación')?>
	    </td>
	    <td nowrap align=left><input type="text" name="fecha_creacion1" value="<?=$fecha_creacion1 ?>" id="fecha_creacion1" size="11" maxlength="10" />
				<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha_creacion1" style="cursor:pointer" />
				<br>
				<input type="text" name="fecha_creacion2" value="<?=$fecha_creacion2 ?>" id="fecha_creacion2" size="11" maxlength="10" />
				<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha_creacion2" style="cursor:pointer" />
	    </td>
	</tr>
	<tr>
	 	<td align=right>
	     	<?=__('Estado')?>
	    </td>
	    <td align=left colspan=3>
	        <?=Html::SelectQuery($sesion,"SELECT codigo_estado_cobro FROM prm_estado_cobro","estado_cobro",$estado_cobro,'','Todos','120')?>
	    </td>
	</tr>
	<tr>
		<td></td>
		<td colspan=4 align=left>
			<input name=boton_buscar type=submit value="<?=__('Buscar')?>">
		</td>
	</tr>
</table>
</fieldset>
</form>
<?
	}
	if($opc == "buscar" || $opc == "eliminar")
		$b->Imprimir();

  function Opciones(& $fila)
  {
		global $desde, $x_pagina;
		print_r($flia->fields);
		$id_cobro = $fila->fields['id_cobro'];
		$tmp = $fila->fields['etapa_cobro']*1 +1;
		return "<a href=\"javascript:void(0)\" onclick=\"nuevaVentana('Editar_Cobro',1024,690,'cobros$tmp.php?id_cobro=$id_cobro&popup=1');\" title='Detalle'><img src='".Conf::ImgDir()."/ver_16.gif' border=0 alt='Ver detalle'/></a>"
     . "&nbsp;&nbsp;&nbsp;<a onclick=\"return confirm('".__('¿Está seguro de eliminar el cobro?')."')\" href=?id_cobro=$id_cobro&opc=eliminar&desde=$desde title='" . __("Eliminar cobro") . "'><img src='".Conf::ImgDir()."/cruz_roja.gif' border=0 alt='Eliminar' /></a>";
  }
?>
<script type="text/javascript">
Calendar.setup(
	{
		inputField	: "fecha1",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_fecha1",		// ID of the button
		id					: 1								// id del calendario
	}
);
Calendar.setup(
	{
		inputField	: "fecha2",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_fecha2",		// ID of the button
		id					: 2								// id del calendario
	}
);
Calendar.setup(
	{
		inputField	: "fecha_creacion1",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_fecha_creacion1",		// ID of the button
		id					: 3								// id del calendario
	}
);
Calendar.setup(
	{
		inputField	: "fecha_creacion2",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_fecha_creacion2",		// ID of the button
		id					: 4								// id del calendario
	}
);
</script>
<?
    echo(InputId::Javascript($sesion));
    $pagina->PrintBottom($popup);
?>
