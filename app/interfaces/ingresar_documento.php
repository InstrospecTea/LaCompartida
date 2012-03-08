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
	require_once Conf::ServerDir().'/classes/Funciones.php';
	require_once Conf::ServerDir().'/classes/Documento.php';
	require_once Conf::ServerDir().'/classes/Moneda.php';
	require_once Conf::ServerDir().'/classes/Observacion.php';

	$sesion = new Sesion(array('OFI'));
	$pagina = new Pagina($sesion);
	$id_usuario = $sesion->usuario->fields['id_usuario'];

	$documento = new Documento($sesion);


	if($id_documento != "")
	{
		$documento->Load($id_documento);
		$codigo_cliente = $documento->fields['codigo_cliente'];
	}

	if($opcion == "guardar")
	{
		$monto=str_replace(',','.',$monto);

		foreach($_POST as $key => $post)
		{
					if(strpos($key,'documento_') !== false && $post > 0)
					{
						$documento_cobro = new Documento($sesion);
						$documento_cobro->Load($post);
						$cobro = new Cobro($sesion);
						$cobro->Load($documento_cobro->fields['id_cobro']);

						if( $cobro->SetPagos(isset($_POST['pago_honorarios_'.$post]),isset($_POST['pago_gastos_'.$post]),$id_documento) )
								$pagina->AddInfo(__('Historial de Pago de Cobro ingresado'));
					}
		}

		if($pago == 'true')
		{
			$multiplicador = -1.0;
			$documento->Edit("id_tipo_documento",2);
		}
		else
		{
			$multiplicador = 1.0;
			$documento->Edit("id_tipo_documento",3);
		}

		$moneda = new Moneda($sesion);
		$moneda->Load($id_moneda);
		$moneda_base = Utiles::MonedaBase($sesion);

		$monto_base = $monto * $moneda->fields['tipo_cambio'] / $moneda_base['tipo_cambio'];

		$documento->Edit("monto",number_format($monto*$multiplicador,$moneda->fields['cifras_decimales'],".",""));
		$documento->Edit("monto_base",number_format($monto_base*$multiplicador,$moneda_base['cifras_decimales'],".",""));
		$documento->Edit("tipo_doc",$tipo_doc);
		$documento->Edit("numero_doc",$numero_doc);
		$documento->Edit("id_moneda",$id_moneda);
		$documento->Edit("fecha",Utiles::fecha2sql($fecha));
		$documento->Edit("glosa_documento",$glosa_documento);
		$documento->Edit("codigo_cliente",$codigo_cliente);


		if($documento->Write())
		{
			$id_documento = $documento->fields['id_documento'];
			$pagina->addInfo(__('Pago ingresado con éxito'));
		}
		else
			$pagina->AddError($documento->error);
	}

	if($pago == 'true')
	{
		$query = "SELECT SQL_CALC_FOUND_ROWS *, doc.id_documento, doc.id_cobro, doc.honorarios, doc.gastos,
		                                       cobro.honorarios_pagados, cobro.honorarios_pagados, cobro.fecha_ini, cobro.fecha_fin, prm_moneda.simbolo
					FROM documento doc
					JOIN cobro ON doc.id_cobro = cobro.id_cobro
					JOIN prm_moneda on cobro.id_moneda = prm_moneda.id_moneda
					WHERE (cobro.honorarios_pagados = 'NO' OR cobro.gastos_pagados = 'NO') AND doc.codigo_cliente = '".$codigo_cliente."' ";
		$x_pag = 0;
		$b = new Buscador($sesion, $query, "Objeto", $desde, $x_pag, $orden);
		$b->nombre = "busc_cobros";
		$b->titulo = "Si el documento corresponde al Pago de " . __('un Cobro') . " pendiente, marque los montos cuyo pago estará completo:";
		$b->AgregarEncabezado("id_cobro",__('N°'), "align=left");
		$b->AgregarEncabezado("glosa_documento",__('Descripción'), "align=left");
		$b->AgregarEncabezado("fecha_ini",__('desde'),"align=left");
		$b->AgregarEncabezado("fecha_fin",__('hasta'),"align=right nowrap");
		$b->AgregarFuncion(__('Honorarios Pagados'),"Honorarios","align=right nowrap");
		$b->AgregarFuncion(__('Gastos Pagados'),"Gastos","align=right nowrap");
		$b->color_mouse_over = "#DF9862";

		function Honorarios(& $fila)
		{
			
			$checked = '';
			if($fila->fields['honorarios_pagados']=='SI')
				$checked = 'checked="checked"';
			$html_cobro .= $fila->fields['simbolo']." ".$fila->fields['honorarios']." <input type=checkbox name=\"pago_honorarios_".$fila->fields['id_documento']."\" ".$checked." >";
			$html_cobro .= "<input type=hidden name=\"documento_".$fila->fields['id_documento']."\" value = \"".$fila->fields['id_documento']."\">";
			return $html_cobro;
		}

		function Gastos(& $fila)
		{
			$checked = '';
			if($fila->fields['gastos_pagados']=='SI')
				$checked = 'checked="checked"';

			$html_cobro .= $fila->fields['simbolo']." ".$fila->fields['gastos']." <input type=checkbox name=\"pago_gastos_".$fila->fields['id_documento']."\" ".$checked." >";
			return $html_cobro;
		}
	}


	if($pago == 'true')
	{
	 	$txt_pagina = $id_documento ? __('Edición de Pago') : __('Documento de Pago');
	 	$txt_tipo = __('Documento de Pago');
	}
	else
	{
		$txt_pagina = $id_documento ? __('Edición de Documento de') . ' ' . __('Cobro') : __('Documento de') . ' ' . __('Cobro');
		$txt_tipo = __('Documento de') . ' ' . __('Cobro');
	}
	
	$pagina->titulo = $txt_pagina;
	$pagina->PrintTop($popup);
?>

<script type="text/javascript">
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


document.observe('dom:loaded', function() {
});



function Validar(form)
{

	monto = parseFloat(form.monto.value);
	
	
	if(monto <= 0 || isNaN(monto))
	{
		alert('<?=__('Debe ingresar un monto para el pago')?>');
		form.monto.focus();
		return false;
	}
	if(form.glosa_documento.value == "")
	{
		alert('<?=__('Debe ingresar una descripción')?>');
		form.glosa_documento.focus();
		return false;
	}
}

function CheckEliminaIngreso(chk)
{
	var form = $('form_documentos');
	if(chk)
		form.elimina_ingreso.value = 1;
	else
		form.elimina_ingreso.value = '';

	return true;
}


window.opener.Refrescar();

</script>
<form method=post action="<?= $SERVER[PHP_SELF] ?>" onsubmit="return Validar(this);" id="form_documentos" autocomplete='off'>
<input type=hidden name=opcion value="guardar" />
<input type=hidden name=id_documento value="<?= $documento->fields['id_documento'] ?>" />
<input type=hidden name='pago' value='<?=$pago?>'>
<input type=hidden name=elimina_ingreso id=elimina_ingreso value=''>
<!-- Calendario DIV -->	
<div id="calendar-container" style="width:221px; position:absolute; display:none;">
	<div class="floating" id="calendar"></div>
</div>
<!-- Fin calendario DIV -->
<br>
<table width='90%'>
	<tr>
		<td align=left><b><?=$txt_pagina ?></b></td>
	</tr>
</table>
<br>

<table style="border: 0px solid black;" width='90%'>
	<tr>
		<td align=left>
			<b><?=__('Información de Documento') ?> </b>
		</td>
	</tr>
</table>
<table style="border: 1px solid black;" width='90%'>
	<tr>
		<td align=right>
			<?=__('Fecha')?>
		</td>
		<td align=left>
			<input type="text" name="fecha" value="<?=$documento->fields[fecha] ? Utiles::sql2date($documento->fields[fecha]) : date('d-m-Y') ?>" id="fecha" size="11" maxlength="10" />
			<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha" style="cursor:pointer" />
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Cliente')?>
		</td>
		<td align=left>
			<input maxlength=10 readonly=readonly name="codigo_cliente" id="campo_codigo_cliente" size=10 value= <? echo $codigo_cliente; ?> />
				<input name="nombre_cliente" id='nombre' style='width: 280px;' value = "<?
																								$cliente = new Cliente($sesion);
																								$cliente->LoadByCodigo($codigo_cliente);
																								echo $cliente->fields['glosa_cliente']; 
																							   ?>" readonly=readonly />	
			<span style="color:#FF0000; font-size:10px">*</span>
		</td>
	</tr>
	<?	if($pago == 'true')
		{
	?>
		<tr>
			<td align=right>
				<?=__('Monto')?>
			</td>
			<td align=left>
				<input name=monto size=10 value="<? echo str_replace("-","",$documento->fields['monto']);  ?>" />
				<span style="color:#FF0000; font-size:10px">*</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<?=__('Moneda')?>&nbsp;
				<?= Html::SelectQuery($sesion, "SELECT id_moneda,glosa_moneda FROM prm_moneda ORDER BY id_moneda","id_moneda", $documento->fields['id_moneda'] ? $documento->fields['id_moneda'] : '', '','',"80"); ?>
				<span style="color:#FF0000; font-size:10px">*</span>
			</td>
		</tr>
		<tr>
			<td align=right>
				<?=__('Número Documento:')?>
			</td>
			<td align=left>
				<input name=numero_doc size=20 value="<? echo str_replace("-","",$documento->fields['numero_doc']);  ?>" />
						<?=__('Tipo:')?>&nbsp;
				<select name='tipo_doc' id='tipo_doc'  style='width: 80px;' selected='<?=$documento->fields['tipo_doc']?$documento->fields['tipo_doc']:'E' ?>'>
					<option value='E'>Efectivo</option>
					<option value='C'>Cheque</option>
					<option value='T'>Transferencia</option>
					<option value='O'>Otro</option>
					</select>	
			</td>
		</tr>
	<?} else {?>
		<tr>
			<td align=right>
				<?=__('Monto Honorarios')?>
			</td>
			<td align=left>
				<input name=monto_honorarios size=10 value="<? echo str_replace("-","",$documento->fields['monto']);  ?>" />
				<span style="color:#FF0000; font-size:10px">*</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

				<?=__('Moneda')?>&nbsp;
				<?= Html::SelectQuery($sesion, "SELECT id_moneda,glosa_moneda FROM prm_moneda ORDER BY id_moneda","id_moneda", $documento->fields['id_moneda'] ? $documento->fields['id_moneda'] : '', '','',"80"); ?>
				<span style="color:#FF0000; font-size:10px">*</span>
			</td>
		</tr>
		<tr>
			<td align=right>
				<?=__('Monto Gastos')?>
			</td>
			<td align=left>
				<input name=monto_gastos size=10 value="<? echo str_replace("-","",$documento->fields['monto']);  ?>" />
				<span style="color:#FF0000; font-size:10px">*</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			</td>
		</tr>
	<?}?>
	<tr>
		<td align=right>
			<?=__('Descripción')?>
		</td>
		<td align=left>
			<textarea name=glosa_documento cols="45" rows="3"><?=$documento->fields['glosa_documento'];?></textarea>
		</td>
	</tr>
	<tr>
		<td align=right colspan="2">&nbsp;</td>
	</tr>
</table>

<br>
<table style="border: 0px solid black;" width='90%'>
	<tr>
		<td align=left>
			<input type=submit class=btn value="<?=__('Guardar')?>" onclick='return Validar(this.form);' /> <input type=button class=btn value="<?=__('Cerrar')?>" onclick="Cerrar();" />
		</td>
	</tr>
</table>

<?
	if(isset($b))
	{
		$b->Imprimir("",array(''),false);
	}

?>

</form>
<script type="text/javascript">

Calendar.setup(
	{
		inputField	: "fecha",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_fecha"		// ID of the button
	}
);
Calendar.setup(
	{
		inputField	: "fecha_pago",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_fecha_pago"		// ID of the button
	}
);
</script>
<?
	echo InputId::Javascript($sesion);
	$pagina->PrintBottom($popup);
?>