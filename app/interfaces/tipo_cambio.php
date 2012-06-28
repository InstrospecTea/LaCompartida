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
			//echo "valor " . $moneda->fields['glosa_moneda'] . ": " . ${"valor_".$moneda->fields['id_moneda']} . "<br />";
 			//$moneda->Edit('moneda_base','0'); //ya no se permite cambiar moneda base
			if( $moneda->fields['tipo_cambio_referencia'] == 1 )
			{
				$moneda->Edit('tipo_cambio', '1');
			}
			else
			{
				$moneda->Edit('tipo_cambio', ${"valor_".$moneda->fields['id_moneda']});
			}
			if(!$moneda->Write())
			{
				$error = true;			
			}
			else
			{
				$moneda->GuardaHistorial($sesion, $fecha_hist);
			}
		}

		if(!$moneda->Load($moneda_base))
			$pagina->AddError(__('Debe selecciona una moneda base'));

		/*$moneda->Edit('moneda_base','1');
        $moneda->Edit('tipo_cambio','1');
        $moneda->Write();*/  #ya no es necesario este paso
		
		
		if(!$error)
		{
			$pagina->AddInfo(__('Datos actualizados correctamente'));
		}
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
        <input style='display:none' type=radio name="moneda_base" <?=$mon->fields['moneda_base'] ? "checked":""?> value="<?=$mon->fields['id_moneda']?>" onchange="Input('valor_<?=$mon->fields['glosa_moneda']?>')" readonly>
	</td>
	<td>
		<input type=hidden id="moneda_<?=$mon->fields['id_moneda']?>" name="moneda_<?=$mon->fields['id_moneda']?>" value="<?=$mon->fields['tipo_cambio']?>"/>
		<input type="text" class="txt_input" size="10" value="<?=number_format($mon->fields['tipo_cambio'],$mon->fields['cifras_decimales'],'.','')?>" <?=$mon->fields['tipo_cambio_referencia'] ? "disabled":""?> name="valor_<?=$mon->fields['id_moneda']?>" id="valor_<?=$mon->fields['id_moneda']?>" />
		<!-- onchange="GrabarCampo('moneda','<?=$mon->fields['id_moneda']?>',this.value,'<?=$mon->fields['glosa_moneda']?>');" -->
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
		<input type="submit" class="btn" value="<?=__('Guardar')?>" onclick="return Validar(this.form);">
	</td>
</tr>
</table>
</form>
<script>
	var id_moneda_base = <?=$id_moneda_base?>;

function Validar(form)
{
	/*var m_base;
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
		if(!confirm("<?=__('Ha seleccionado otra moneda base, ¿ Está seguro que desea continuar?')?>"))
			return false;
	}
	return true;*/
	//form.<?="valor_".$mon->fields['id_moneda']?>;
	var f = document.getElementById('formulario');
	var errores = "";
<?	
	for($x=0;$x<$lista->num;$x++)
    {
        $mon = $lista->Get($x);
?>
		if( f.<?="valor_".$mon->fields['id_moneda']?>.value.length == 0 || f.<?="valor_".$mon->fields['id_moneda']?>.value == 0 )
		{
			errores += "- <?php echo $mon->fields['glosa_moneda']; ?> \n";
		}
<?
    }	
?>
	if( errores.length > 0 )
	{
		alert("<?php echo __('Se encontraron errores al guardar los tipos de cambio, por favor revise los siguientes valores'); ?>: \n\n" + errores + "<?php echo __('\ne intentelo nuevamente'); ?>");
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
        form.<?="valor_".$mon->fields['id_moneda']?>.disabled = false;
		if(form.<?="valor_".$mon->fields['id_moneda']?>.name == name)
			form.<?="valor_".$mon->fields['id_moneda']?>.disabled = true;
<?
    }
?>
}
<?php
for($x=0;$x<$lista->num;$x++)
	{
		$mon = $lista->Get($x);
		$cf = $mon->fields['cifras_decimales'];
		if( $cf > 0 ) { $dec = "."; while( $cf-- > 0 ){ $dec .= "0"; } }
?>
	jQuery("#valor_<?=$mon->fields['id_moneda']?>").blur(function(){
	   var str = jQuery(this).val();
	   jQuery(this).val( str.replace(',','.') );
	   jQuery(this).parseNumber({format:"#<?=$dec?>", locale:"us"});
	   jQuery(this).formatNumber({format:"#<?=$dec?>", locale:"us"});
	});
<?php
	}
?>
</script>
<?
	$pagina->PrintBottom();
?>
