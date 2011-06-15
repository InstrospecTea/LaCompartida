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
	require_once Conf::ServerDir().'/classes/Observacion.php';
	require_once Conf::ServerDir().'/classes/Cobro.php';
	require_once Conf::ServerDir().'/classes/Moneda.php';

	$sesion = new Sesion(array('OFI'));
	$pagina = new Pagina($sesion);
	$id_usuario = $sesion->usuario->fields['id_usuario'];

	$documento = new Documento($sesion);
	$cobro = new Cobro($sesion);
	$cobro->Load($id_cobro);


	/*TOTALES en moneda opc_moneda_total*/
	$moneda_total = new Objeto($sesion,'','','prm_moneda','id_moneda');
	$moneda_total->Load($cobro->fields['opc_moneda_total']);
			
	$cobro_moneda = new CobroMoneda($sesion);
	$cobro_moneda->Load($cobro->fields['id_cobro']);
		
	$tipo_cambio_moneda_total = $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio'];
	if($tipo_cambio_moneda_total == 0)
		$tipo_cambio_moneda_total = 1;

		/* TOTAL para Gastos */
		$total_gastos = 0;
		$query = "SELECT SQL_CALC_FOUND_ROWS *
							FROM cta_corriente
							WHERE id_cobro='".$cobro->fields['id_cobro']."' AND (egreso > 0 OR ingreso > 0)
							ORDER BY fecha ASC";
		$lista_gastos = new ListaGastos($sesion,'',$query);
		for($i=0;$i<$lista_gastos->num;$i++)
		{
			$gasto = $lista_gastos->Get($i);
			$moneda_gasto = new Objeto($sesion,'','','prm_moneda','id_moneda');
			$moneda_gasto->Load($gasto->fields['id_moneda']);

			/* GASTO EN MONEDA TOTAL IMPRESION */
			if((double)$gasto->fields['egreso'] > 0)
				$total_gastos += ((double)$gasto->fields['egreso'] * (double)$moneda_gasto->fields['tipo_cambio'])/($tipo_cambio_moneda_total > 0 ? $tipo_cambio_moneda_total : $moneda_total->fields['tipo_cambio']);
			elseif((double)$gasto->fields['ingreso'] > 0)
				$total_gastos -= ((double)$gasto->fields['ingreso'] * (double)$moneda_gasto->fields['tipo_cambio'])/($tipo_cambio_moneda_total > 0 ? $tipo_cambio_moneda_total : $moneda_total->fields['tipo_cambio']);
		}

		/* Total para Honorarios */
		$aproximacion_monto = number_format($cobro->fields['monto'],$cobro_moneda->moneda[$cobro->fields['id_moneda']]['cifras_decimales'],'.','');
				
		$total_honorarios = ((double)$aproximacion_monto*(double)$cobro->fields['tipo_cambio_moneda'])/($tipo_cambio_moneda_total > 0 ? $tipo_cambio_moneda_total : $moneda_total->fields['tipo_cambio']);

		$total_honorarios = number_format($total_honorarios,$moneda_total->fields['cifras_decimales'],'.','');
		$total_gastos = number_format($total_gastos,$moneda_total->fields['cifras_decimales'],'.','');

		$total = 0;
		if($cobro->fields['honorarios_pagados']=='NO' || $total_honorarios < 0)
		{
			$total += $total_honorarios;
		}
		if($cobro->fields['honorarios_pagados']=='NO' || $total_gastos < 0)
		{
			$total += $total_gastos;
		}
		if($total < 0)
		$total = 0;
	/* */

	if($id_documento != "")
	{
		$documento->Load($id_documento);
	}

	if($opcion == "guardar")
	{
		$monto=str_replace(',','.',$monto);

		$multiplicador = -1.0;
		$documento->Edit("id_tipo_documento",2);

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

		//PAGO DE COBRO
		if( $cobro->SetPagos(isset($_POST['pago_honorarios']),isset($_POST['pago_gastos']),$id_documento) )
				$pagina->AddInfo(__('Historial de Pago de Cobro ingresado'));	

	}


	 	$txt_pagina = $id_documento ? __('Edición de Pago') : __('Ingreso');
	 	$txt_tipo = __('Pago');
	
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

function Cerrar()
{
	window.opener.Refrescar(true);
	window.close();
}

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
	
	//window.opener.Refrescar(true);
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


<?  if($opcion=='guardar'): ?>
window.opener.Refrescar(true);
<? endif; ?>

</script>
<form method=post action="<?= $SERVER[PHP_SELF] ?>" onsubmit="return Validar(this);" id="form_documentos" autocomplete='off'>
<input type=hidden name=opcion value="guardar" />
<input type=hidden name=id_documento value="<?= $documento->fields['id_documento'] ?>" />
<input type=hidden name=id_cobro value="<?= $id_cobro ?>" />
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
			<input maxlength=10 readonly=readonly name="codigo_cliente" id="campo_codigo_cliente" size=10 value=<? echo $codigo_cliente; ?> />
				<input name="nombre_cliente" id='nombre' style='width: 280px;' value = "<?
																								$cliente = new Cliente($sesion);
																								$cliente->LoadByCodigo($codigo_cliente);
																								echo $cliente->fields['glosa_cliente']; 
																							   ?>" readonly=readonly />	
			<span style="color:#FF0000; font-size:10px">*</span>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Monto')?>
		</td>
		<td align=left>
			<input name=monto size=10 value="<? if($id_documento)
													echo str_replace("-","",$documento->fields['monto']);
												else
													echo $total;
												?>" />
			<span style="color:#FF0000; font-size:10px">*</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			<?=__('Moneda')?>&nbsp;
			<?= Html::SelectQuery($sesion, "SELECT id_moneda,glosa_moneda FROM prm_moneda ORDER BY id_moneda","id_moneda", $documento->fields['id_moneda'] ? $documento->fields['id_moneda'] : $moneda_total->fields['id_moneda'], '','',"80"); ?>
			<span style="color:#FF0000; font-size:10px">*</span>
		</td>
	</tr>
		<td align=right colspan="2">&nbsp;</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Tipo Documento:')?>
		</td>
		<td align=left>
			<select name='tipo_doc' id='tipo_doc'  style='width: 80px;' selected='<?=$documento->fields['tipo_doc']?$documento->fields['tipo_doc']:'E' ?>'>
				<option value='E'>Efectivo</option>
				<option value='C'>Cheque</option>
				<option value='T'>Transferencia</option>
				<option value='O'>Otro</option>
				</select>&nbsp;&nbsp;
				
			<?=__('Número:')?>&nbsp;
			<input name=numero_doc size=20 value="<? echo str_replace("-","",$documento->fields['numero_doc']);  ?>" />	
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Descripción')?>
		</td>
		<td align=left>
			<textarea name=glosa_documento cols="45" rows="3"><?
																if($opc=='guardar')
																	echo $documento->fields['glosa_documento'];
																else
																	echo "Pago de Cobro N° ".$id_cobro;
																?></textarea>
		</td>
	</tr>
	<tr>
		<td align=right colspan="2">&nbsp;</td>
	</tr>
	<tr>
		<td align=right>
			<?
			$checked = '';
			$honorarios = 'Este documento completa el Pago de Honorarios ( ';
			if($cobro->fields['honorarios_pagados']=='SI')
			{
				$honorarios = 'Pago de Honorarios completo ( ';
			}
			$checked = 'checked=checked';
			
			?>
			<input type=checkbox name="pago_honorarios" <?=$checked?> >
		</td>
		<td align=left>
			<?
			$moneda_cobro = new Moneda($sesion);
			$moneda_cobro->Load($cobro->fields['id_moneda']);
			$simbolo_cobro = $moneda_total->fields['simbolo'];
			?>
			<?=__($honorarios.$simbolo_cobro.' '.$total_honorarios.' )')?>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?
			$checked = '';
			$gastos = 'Este documento completa el Pago de Gastos ( ';
			if($cobro->fields['gastos_pagados']=='SI')
			{
				$gastos = 'Pago de Gastos completo ( ';
			}
			$checked = 'checked=checked';
			?>
			<input type=checkbox name="pago_gastos" <?=$checked?> >
		</td>
		<td align=left>
			<?=__($gastos.$simbolo_cobro.' '.$total_gastos.' )')?>
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