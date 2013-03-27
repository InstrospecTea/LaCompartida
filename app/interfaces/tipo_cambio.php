<?php 

require_once dirname(__FILE__) . '/../conf.php'; 

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
			$moneda->Edit('cifras_decimales', ${"decimales_".$moneda->fields['id_moneda']});
 
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
 
<table width="600" align=center class="border_plomo tb_base"  cellspacing=5>
<tr>
	<td align=right width="40%">
		<strong><?php echo __('Moneda')?></strong>	
	</td>
	<td width="10px">
		<!--<strong><?php echo __('Base')?></strong>-->
	</td>
	<td width="20%">
		<strong><?php echo __('Tasa')?></strong>
	</td>
	<td width="20%">
		<strong><?php echo __('Decimales')?></strong>
	</td>
</td>
<?php 
 
	for($x=0;$x<$lista->num;$x++)
	{
		$mon = $lista->Get($x);
?>
<tr>
	<td align=right>
 
	 <?php echo $mon->fields['glosa_moneda']?>: 
	  <?php echo $mon->fields['moneda_base'] ? "<br><small style='color:#999;'>(moneda base)</small>":""?>
	</td>
	<td align=center>
       <input style='display:none' type=radio name="moneda_base" <?php echo $mon->fields['moneda_base'] ? "checked":""?> value="<?php echo $mon->fields['id_moneda']?>" onchange="Input('valor_<?php echo $mon->fields['glosa_moneda']?>')" readonly>
	</td>
	<td>
		<input type=hidden id="moneda_<?php echo $mon->fields['id_moneda']?>" name="moneda_<?php echo $mon->fields['id_moneda']?>" value="<?php echo $mon->fields['tipo_cambio']?>"/>
		<input type="text" class="txt_input" size="10" value="<?php echo $mon->fields['tipo_cambio'];?>" alt="<?php echo $mon->fields['tipo_cambio'];?>" <?php echo $mon->fields['tipo_cambio_referencia'] ? "disabled":""?> name="valor_<?php echo $mon->fields['id_moneda']?>" id="valor_<?php echo $mon->fields['id_moneda']?>" />
		<!-- onchange="GrabarCampo('moneda','<?php echo $mon->fields['id_moneda']?>',this.value,'<?php echo $mon->fields['glosa_moneda']?>');" -->
	</td>
	<td>
		<input type="text" class="txt_input decimales" size="4" value="<?php echo $mon->fields['cifras_decimales']; ?>" readonly="readonly" name="decimales_<?php echo $mon->fields['id_moneda']?>" id="decimales_<?php echo $mon->fields['id_moneda']?>" />
	</td>
</tr>
<?php 
 
		if($mon->fields['moneda_base'])
			$id_moneda_base = $mon->fields['id_moneda'];
	}
?>
<tr>
 
	<td colspan=4>
 
		&nbsp;
	</td>
</tr>
<tr>
 
	<td colspan=4 align=center>
		<input type="submit" class="btn" value="<?php echo __('Guardar')?>" onclick="return Validar(this.form);">
 
	</td>
</tr>
</table>
</form>
<script>
 
	jQuery('document').ready(function() {
		
		jQuery('.decimales').dblclick(function() {
			jQuery(this).removeAttr('readonly');
		});
		
		jQuery('.decimales').each(function() {
			var decimales=jQuery(this).val();
			var str_decimales="";
			for(var n=decimales;n>0;n--) {
				str_decimales+="0";
			}
			var targetcell=jQuery(this).attr('id').replace('decimales_','valor_');
			jQuery('#'+targetcell).formatNumber({format:"0."+str_decimales, locale:"us"});
			
		});
		
		jQuery('.decimales').on('keyup',function() {
			var decimales=jQuery(this).val();
			var str_decimales="";
			for(var n=decimales;n>0;n--) {
				str_decimales+="0";
			}
			var targetcell=jQuery(this).attr('id').replace('decimales_','valor_');
			jQuery('#'+targetcell).val(jQuery('#'+targetcell).attr('alt')).formatNumber({format:"0."+str_decimales, locale:"us"});
			
		});
	});
	var id_moneda_base = <?php echo $id_moneda_base?>;
 

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

		if(!confirm("<?php echo __('Ha seleccionado otra moneda base, ¿ Está seguro que desea continuar?')?>"))
			return false;
	}
	return true;*/
	//form.<?php echo "valor_".$mon->fields['id_moneda']?>;
	var f = document.getElementById('formulario');
	var errores = "";
<?php 	
 
	for($x=0;$x<$lista->num;$x++)
    {
        $mon = $lista->Get($x);
?>
 
		if( f.<?php echo "valor_".$mon->fields['id_moneda']?>.value.length == 0 || f.<?php echo "valor_".$mon->fields['id_moneda']?>.value == 0 )
		{
			errores += "- <?php echo $mon->fields['glosa_moneda']; ?> \n";
		}
<?php 
 
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
 
<?php 
 
    for($x=0;$x<$lista->num;$x++)
    {
        $mon = $lista->Get($x);
?>
 
        form.<?php echo "valor_".$mon->fields['id_moneda']?>.disabled = false;
		if(form.<?php echo "valor_".$mon->fields['id_moneda']?>.name == name)
			form.<?php echo "valor_".$mon->fields['id_moneda']?>.disabled = true;
<?php 
 
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
 
	jQuery("#valor_<?php echo $mon->fields['id_moneda']?>").blur(function(){
	   var str = jQuery(this).val();
	   jQuery(this).val( str.replace(',','.') );
	   jQuery(this).parseNumber({format:"#<?php echo $dec?>", locale:"us"});
	   jQuery(this).formatNumber({format:"#<?php echo $dec?>", locale:"us"});
 
	});
<?php
	}
?>
</script>
 
<?php 
 
	$pagina->PrintBottom();
?>
