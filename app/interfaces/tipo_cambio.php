<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
    require_once Conf::ServerDir().'/../fw/classes/Lista.php';
    require_once Conf::ServerDir().'/classes/Moneda.php';

	$sesion = new Sesion(array('COB'));
	$pagina = new Pagina($sesion);

	$pagina->titulo = __('Tipo de Cambio');
 
	$lista = new ListaMonedas($sesion,"","SELECT * FROM prm_moneda");

	if($opc == "guardar")
	{
		$moneda = new Moneda($sesion);
		$error = false;
		$fecha_hist = date("Y-m-d H:i:s");
	    for($x=0;$x<$lista->num;$x++)
	    {
    	    $mon = $lista->Get($x);
			$moneda->Load($mon->fields['id_moneda']);
			$moneda->GuardaHistorial($sesion, $fecha_hist);
 			$moneda->Edit('moneda_base','0');
			if(!$moneda->Write())
			{
				$error = true;			
			}
		}

		if(!$moneda->Load($moneda_base))
			$pagina->AddError(__('Debe selecciona una moneda base'));

		$moneda->Edit('moneda_base','1');
        $moneda->Edit('tipo_cambio','1');
        $moneda->Write();

		if(!$error)
			$pagina->AddInfo(__('Datos actualizados correctamente'));
	}

	$pagina->PrintTop();

    $lista = new ListaMonedas($sesion,"","SELECT * FROM prm_moneda");
?>
<style>
.txt_input
{
	text-align:right;
}
</style>
<form name=formulario id=formulario method=post autocomplete='off'>
<input type=hidden name=opc value=guardar>
<table width=90% align=center class="border_plomo tb_base" cellspacing=5>
<tr>
	<td align=right width=40%>
		<strong><?=__('Moneda')?></strong>	
	</td>
	<td width=10px>
		<!--<strong><?=__('Base')?></strong>-->
	</td>
	<td width=60%>
		<strong><?=__('Tasa')?></strong>
	</td>
</td>
<?
	for($x=0;$x<$lista->num;$x++)
	{
		$mon = $lista->Get($x);
?>
<tr>
	<td align=right>
	 <?=$mon->fields['glosa_moneda']?>: 
	</td>
	<td align=center>
        <input style='display:none' type=radio name="moneda_base" <?=$mon->fields['moneda_base'] ? "checked":""?> value="<?=$mon->fields['id_moneda']?>" onchange="Input('<?=$mon->fields['glosa_moneda']?>')" readonly>
	</td>
	<td>
		<input type=hidden id="moneda_<?=$mon->fields['id_moneda']?>" name="moneda_<?=$mon->fields['id_moneda']?>" value="<?=$mon->fields['tipo_cambio']?>"/>
		<input type=text class=txt_input size=10 value="<?=$mon->fields['tipo_cambio']?>" <?=$mon->fields['moneda_base'] ? "disabled":""?> name="<?=$mon->fields['glosa_moneda']?>" id="<?=$mon->fields['glosa_moneda']?>" onchange="GrabarCampo('moneda','<?=$mon->fields['id_moneda']?>',this.value,'<?=$mon->fields['glosa_moneda']?>');"/>
	</td>
</tr>
<?
		if($mon->fields['moneda_base'])
			$id_moneda_base = $mon->fields['id_moneda'];
	}
?>
<tr>
	<td colspan=3>
		&nbsp;
	</td>
</tr>
<tr>
	<td colspan=3 align=center>
		<input type=submit class=btn value=<?=__('Guardar')?> onclick="return Validar(this.form);">
	</td>
</tr>
</table>
</form>
<script>
	var id_moneda_base = <?=$id_moneda_base?>;

function Validar(form)
{
	var m_base;
	for( var i=0; actual = form.elements.moneda_base[i]; i++ )
    {
    	if(form.elements.moneda_base[i].checked)
        {
        	m_base = form.elements.moneda_base[i].value
        	break;
        }
    }

	if(id_moneda_base != m_base)
	{
		if(!confirm("<?=__('Ha seleccionado otra moneda base, � Est� seguro que desea continuar?')?>"))
			return false;
	}
	return true;
}
	
function GrabarCampo(accion,id_moneda,valor,glosa)
{
		if( valor == 0 )
		{
			$(glosa).value = $('moneda_'+id_moneda).value;
			alert('No se permite definir el tipo cambio con valor 0.');
		}
		else
		{
	    var http = getXMLHTTP();
	
	    loading("Actualizando opciones");
	    http.open('get', 'ajax_grabar_campo.php?accion=' + accion +'&id_moneda=' + id_moneda + '&valor=' + valor );
	    http.onreadystatechange = function()
	    {
	        if(http.readyState == 4)
	        {
	            var response = http.responseText;
	            var update = new Array();
	            if(response.indexOf('OK') == -1)
	            {
					return false;
	                alert(response);
	            }
	            offLoading();
	        }
	    };
	    http.send(null);
  	}
}
function Input(name)
{
    var form = document.getElementById('formulario');
<?
    for($x=0;$x<$lista->num;$x++)
    {
        $mon = $lista->Get($x);
?>
        form.<?=$mon->fields['glosa_moneda']?>.disabled = false;
		if(form.<?=$mon->fields['glosa_moneda']?>.name == name)
			form.<?=$mon->fields['glosa_moneda']?>.disabled = true;
<?
    }
?>
}
</script>
<?
	$pagina->PrintBottom();
?>
