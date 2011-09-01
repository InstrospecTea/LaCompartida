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

	$sesion = new Sesion(array('COB'));
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

		$monto_honorarios=str_replace(',','.',$monto_honorarios);
		$monto_gastos=str_replace(',','.',$monto_gastos);

		$monto= $monto_honorarios+$monto_gastos;

		/*Documento de Cobro: tipo de documento = 3*/
		$documento->Edit("id_tipo_documento",3);

		$moneda = new Moneda($sesion);
		$moneda->Load($id_moneda);
		$moneda_base = Utiles::MonedaBase($sesion);

		$monto_base = $monto * $moneda->fields['tipo_cambio'] / $moneda_base['tipo_cambio'];


		$documento->Edit("honorarios",number_format($monto_honorarios,$moneda->fields['cifras_decimales'],".",""));
		$documento->Edit("gastos",number_format($monto_gastos,$moneda->fields['cifras_decimales'],".",""));
		$documento->Edit("saldo_honorarios",number_format($monto_honorarios,$moneda->fields['cifras_decimales'],".",""));
		$documento->Edit("saldo_gastos",number_format($monto_gastos,$moneda->fields['cifras_decimales'],".",""));

		if($monto_gastos == 0)
			$documento->Edit("gastos_pagados",'SI');
		else if($monto_honorarios == 0)
			$documento->Edit("honorarios_pagados",'SI');



		$documento->Edit("monto",number_format($monto,$moneda->fields['cifras_decimales'],".",""));
		$documento->Edit("monto_base",number_format($monto_base,$moneda_base['cifras_decimales'],".",""));

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

	$txt_pagina = $id_documento ? __('Edición de Documento de') . ' ' . __('Cobro') : __('Documento de') . ' ' . __('Cobro');
	$txt_tipo = __('Documento de') . ' ' . __('Cobro');

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
	window.opener.Refrescar();
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

	if(form.campo_codigo_cliente.value == -1 || form.campo_codigo_cliente == '' )
	{
		alert('<?=__('Debe seleccionar un cliente')?>');
		form.campo_codigo_cliente.focus();
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
<?/*		<td align=right>
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
*/?>
		<td align=right width='30%'><?=__('Cliente ')?></td>
		<td colspan=3 align=left><?= InputId::ImprimirSinCualquiera($sesion,"cliente","codigo_cliente","glosa_cliente", "codigo_cliente", $codigo_cliente," ","", 280) ?></td>
		</td>
	</tr>

	<tr>
		<td align=right>
			<?=__('Monto Honorarios')?>
		</td>
		<td align=left>
			<input name=monto_honorarios size=10 value="<? echo str_replace("-","",$documento->fields['honorarios']);  ?>" />
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
			<input name=monto_gastos size=10 value="<? echo str_replace("-","",$documento->fields['gastos']);  ?>" />
			<span style="color:#FF0000; font-size:10px">*</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		</td>
	</tr>

	<tr>
		<td align=right>
			<?=__('Descripción')?>
		</td>
		<td align=left>
			<textarea name=glosa_documento cols="45" rows="3"><?= $documento->fields['glosa_documento']? $documento->fields['glosa_documento']: "Cobro externo al sistema de Time Tracking."?></textarea>
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