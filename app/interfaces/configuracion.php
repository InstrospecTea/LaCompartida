<?php
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/../app/classes/ContratoDocumentoLegal.php';

	$sesion = new Sesion(array('ADM'));
	$pagina = new Pagina($sesion);

	$pagina->titulo = __('Configuración');
	$pagina->PrintTop();
	if($opc=='guardar')
	{
		foreach($opcion as $id => $valor)
		{
			if(isset($opcion_hidden[$id]))
				$opcion_hidden[$id] = 1;
			$query = "UPDATE configuracion SET valor_opcion='$valor' WHERE id='$id'";
			mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		}
		foreach($opcion_hidden as $id => $valor)
			if($valor==0)
			{
				$query = "UPDATE configuracion SET valor_opcion='$valor' WHERE id='$id'";
				mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			}

		ContratoDocumentoLegal::EliminarDocumentosLegales($sesion, null, true);
		if( is_array($docs_legales) )
		{
			foreach ($docs_legales as $doc_legal) {
				if (empty($doc_legal['documento_legal']) or ( empty($doc_legal['honorario']) and empty($doc_legal['gastos_con_iva']) and empty($doc_legal['gastos_sin_iva']) )) {
					continue;
				}
				$contrato_doc_legal = new ContratoDocumentoLegal($sesion);
				$contrato_doc_legal->Edit('id_tipo_documento_legal', $doc_legal['documento_legal']);
				if (!empty($doc_legal['honorario'])) {
					$contrato_doc_legal->Edit('honorarios', 1);
				}
				if (!empty($doc_legal['gastos_con_iva'])) {
					$contrato_doc_legal->Edit('gastos_con_impuestos', 1);
				}
				if (!empty($doc_legal['gastos_sin_iva'])) {
					$contrato_doc_legal->Edit('gastos_sin_impuestos', 1);
				}
				$contrato_doc_legal->Edit('id_tipo_documento_legal', $doc_legal['documento_legal']);
				$contrato_doc_legal->Write();
			}
		}
	}
?>

<script type="text/javascript">
  
function MostrarTablaAsuntos( check, tabla, valor_hidden )
{
	//var check_elemento = document.getElementById( check );
	//var tabla_elemento = document.getElementById( tabla );
	
        //var valorhidden = document.getElementById( valor_hidden );
       // var valorhidden = jQuery('#'+valor_hidden).val();
      
	var string_asuntos = jQuery('#'+valor_hidden).val();
        var array_asuntos = string_asuntos.split(';');
	//if( check_elemento.checked ) {
	//	tabla_elemento.style.display = 'table';
        if (jQuery('#'+check).is(':checked')) {
        jQuery('#'+tabla).show();
        string_asuntos='true';
	} else {
	//	tabla_elemento.style.display = 'none';
        jQuery('#'+tabla).hide();
            string_asuntos='false';  
        }    
   for( var i = 1; i < array_asuntos.length; i++)
  	{
  	string_asuntos += ';'+array_asuntos[i];
        }
	         
        jQuery('#'+valor_hidden).val(string_asuntos)  ;  
}

function Ocultar( version, trID, valor_hidden, inputID )
{
	var trArea = document.getElementById(trID);
	var valorhidden = document.getElementById(valor_hidden);
	var string_asuntos = valorhidden.value;

	trArea.style['display'] = "none";
	
	if( version == 'nuevo' )
		var str = ';'+document.getElementById( inputID ).value;
	else
		var str = ';'+trID;
	string_asuntos = string_asuntos.replace( str , ""); 
	valorhidden.value = string_asuntos;
}

function MuestraOculta(divID)
{
	var divArea = $(divID);
	var divAreaImg = $(divID+"_img");
	var divAreaVisible = divArea.style['display'] != "none";
	
	if(divAreaVisible)
	{
		divArea.style['display'] = "none";
		divAreaImg.innerHTML = "<img src='../templates/default/img/mas.gif' border='0' title='Desplegar'>";
	}
	else
	{
		divArea.style['display'] = "inline";
		divAreaImg.innerHTML = "<img src='../templates/default/img/menos.gif' border='0' title='Ocultar'>";
	}
}

function AsuntosPorSeparado( valor, valor_hidden )
{
	var asuntos_por_separado = document.getElementById("asuntos_por_separado");
	var valorhidden = document.getElementById( valor_hidden );
	var string_asuntos = valorhidden.value;
	
	if( asuntos_por_separado.checked )
		string_asuntos = string_asuntos.replace("false","true");
	else
		string_asuntos = string_asuntos.replace("true","false");
	valorhidden.value = string_asuntos;
}

function AgregarAsunto( numero , valor_hidden )
{
	var tr_elemento = document.getElementById( 'hidden_'+numero );
	var input_elemento = document.getElementById( 'text_'+numero );
	var div_elemento = document.getElementById( 'texto_'+numero );
	var img_elemento = document.getElementById( 'img_'+numero );
	var agregar_elemento = document.getElementById( 'agregar_'+numero );
	var valorhidden = document.getElementById( valor_hidden );
	var string_asuntos = valorhidden.value;
	
  var array_asuntos = string_asuntos.split(';');
 if (jQuery('#usa_asuntos_por_defecto').is(':checked')) {
  string_asuntos='true';
  } else {
  string_asuntos='false';    
  }
  for( var i = 1; i < array_asuntos.length; i++)
  	{
  		if( input_elemento.value == array_asuntos[i] )
  			{
  				alert( 'EL asunto indicado ya existe.' );
  				input_elemento.focus();
  				return false;
  			}
  	string_asuntos += ';'+array_asuntos[i];
        }
        string_asuntos += ';'+input_elemento.value;
//	alert(string_asuntos);
	valorhidden.value = string_asuntos; 
	div_elemento.innerHTML = input_elemento.value;
	div_elemento.style.display = 'inline';
	input_elemento.style.display = 'none';
	/*input_elemento.style.background = 'white';
	input_elemento.style.color = 'black';
	input_elemento.style.border = '0px';
	input_elemento.style.margin = '0px';
	input_elemento.style.padding = '0px';*/
	
	agregar_elemento.style.display = 'none';
	img_elemento.style.display = 'block';
	
	numero_nuevo = (numero-0)+1;
	var tr_elemento_nuevo = document.getElementById( 'hidden_'+numero_nuevo );
	tr_elemento_nuevo.style.display = 'table-row';
}
</script>


<div id="calendar-container" style="width:221px; position:absolute; display:none;">
	<div class="floating" id="calendar"></div>
</div>
	
<form name="formulario" id="formulario" method="post" action='' autocomplete="off" onsubmit="return validar_doc_legales(true)">
	<input type=hidden name='opc' value='guardar'>
<?
	if( $sesion->usuario->fields['rut'] != '99511620' ) 
		$where_orden = " WHERE orden > -1 ";
	else 
		$where_orden = "";
	$query = "SELECT id, glosa_opcion, valor_opcion, comentario, valores_posibles, configuracion.id_configuracion_categoria, glosa_configuracion_categoria, orden 
					FROM configuracion  
					JOIN configuracion_categoria ON configuracion.id_configuracion_categoria=configuracion_categoria.id_configuracion_categoria 
					$where_orden 
					ORDER BY configuracion.id_configuracion_categoria, orden, glosa_opcion ASC";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	
	while(list($id, $glosa_opcion, $valor_opcion, $comentario, $valores_posibles, $id_categoria, $glosa_categoria, $orden) = mysql_fetch_array($resp))
	{
		if( $id_categoria != $id_categoria_anterior )
			{
			if( !$id_categoria_anterior )
				echo "<fieldset class=\"tb_base\" style=\"width:85%; border: 1px solid #BDBDBD;\"><legend onClick=\"MuestraOculta('".$glosa_categoria."')\" style=\"cursor:pointer\"><span id='".$glosa_categoria."_img'><img src='".Conf::ImgDir()."/mas.gif' border=0 id='".$glosa_categoria."_img'></span>".$glosa_categoria."</legend><table width=80% id='".$glosa_categoria."' style='display:none'>";
			else
				echo "</table></fieldset><fieldset class=\"tb_base\" style=\"width:85%; border: 1px solid #BDBDBD;\"><legend onClick=\"MuestraOculta('".$glosa_categoria."')\" style=\"cursor:pointer\"><span id='".$glosa_categoria."_img'><img src='".Conf::ImgDir()."/mas.gif' border=0 id='".$glosa_categoria."_img'></span> ".$glosa_categoria."</legend><table width=80% id='".$glosa_categoria."' style='display:none'>";
			}
		$tooltip = $comentario?Html::Tooltip($comentario):'';
		echo "<tr><td align=left width=50%>" . __($glosa_opcion) . "</td><td align=left $tooltip width=50%>";
		$valores = explode(';', $valores_posibles);
		switch($valores[0])
		{
			case 'boolean':
				// generar checkbox, se pone un input hidden para poder saber si se desmarca un checkbox
				echo "<input type='hidden' value='0' name='opcion_hidden[$id]' />";
				echo "<input type='checkbox' value='1' name='opcion[$id]' ".($valor_opcion?"checked='checked'":"")." />";
				break;
			case 'radio':
				// generar radiobutton para eligir entre opciones
				echo "<input type='radio' ".($valor_opcion?"checked='checked'":"")." name='".$valores[1]."' />";
				break;
			case 'array':
				echo "<input type='hidden' id='opcion_$id' name='opcion[$id]' value='".$valor_opcion."' />";
				$valores_array = explode(';', $valor_opcion);
				echo "<input type='checkbox'  id='usa_asuntos_por_defecto' ".(($valores_array[0]== 'true')?"checked='checked'":"")." onChange=\"MostrarTablaAsuntos( 'usa_asuntos_por_defecto', 'tabla_asuntos', 'opcion_$id' );\" />";
				echo "<table id='tabla_asuntos' ".(($valores_array[0]!='true')?"style='display: none;'":"").">";
				for($i=1; $i<count($valores_array); ++$i )
					echo "<tr id='$valores_array[$i]'><td>".$valores_array[$i]."</td><td><img style=\"filter:alpha(opacity=100);\" src='".Conf::ImgDir()."/cruz_roja_13.gif' border='0' class='mano_on' alt='Ocultar' onclick=\"Ocultar('viejo','".$valores_array[$i]."', 'opcion[".$id."]');\"/></td></tr>";
				echo "<tr id='hidden_1'><td><input type='text' id='text_1' size='12' value='' /><div id='texto_1' style=\"display:none;\"/></td><td><img id='img_1' style=\"display:none; filter:alpha(opacity=100);\" src='".Conf::ImgDir()."/cruz_roja_13.gif' border='0' class='mano_on' alt='Ocultar' onclick=\"Ocultar('nuevo','hidden_1', 'opcion[".$id."]', 'text_1');\"/><input type='button' id='agregar_1' name='agregar_asunto' value=\"Agregar Asunto\" onclick=\"AgregarAsunto('1','opcion[".$id."]');\" /></td></tr>";
				for($j=2; $j<21; ++$j)
					echo "<tr id='hidden_".$j."' style=\"display: none;\"><td><input type='text' id='text_".$j."' size='12' value='' /><div id='texto_".$j."' style=\"display:none;\"/></td><td><img id='img_".$j."' style=\"display:none; filter:alpha(opacity=100);\" src='".Conf::ImgDir()."/cruz_roja_13.gif' border='0' class='mano_on' alt='Ocultar' onclick=\"Ocultar('nuevo','hidden_".$j."', 'opcion[".$id."]','text_".$j."');\"/><input type='button' id='agregar_".$j."' name='agregar_asunto' value=\"Agregar Asunto\" onclick=\"AgregarAsunto('".$j."','opcion[".$id."]');\"/></td></tr>";
				echo "<tr><td colspan=2><input id=\"asuntos_por_separado\" type='checkbox' ".($valores_array[0]=='true'?"checked='checked'":"")." onChange=\"AsuntosPorSeparado(this.value, 'opcion[".$id."]');\"/> &nbsp;&nbsp; Cobrar los asuntos de forma independiente.</td></tr>";
				echo "</table>";
				break;
			case 'numero':
			case 'string':
				// generar input de texto
				echo "<input type='text' size='64' class='text_box' name='opcion[$id]' value='$valor_opcion' />";
				break;
			case 'text':
				// generar input de texto largo
				echo "<textarea name='opcion[$id]' cols=45 rows=2>".$valor_opcion."</textarea>";
				break;
			case 'select':
				// generar select usando los siguientes valores
				echo "<select name='opcion[$id]'>";
				for($i=1; $i<count($valores); ++$i)
					echo "<option value='$valores[$i]'".($valores[$i]==$valor_opcion?" selected='selected'":"").">".($valores[$i]?__($valores[$i]):'0')."</option>";
				echo '</select>';
				break;
			case 'fecha':
				// generar DatePicker
				echo "<input type=text name=fecha value='$valor_opcion' id=fecha size=11 maxlength=10 />
		        <img src=".Conf::ImgDir()."/calendar.gif id=img_fecha style=\"cursor:pointer\" />";
		    break;
		}
		echo "</td></tr>\n";
		$id_categoria_anterior = $id_categoria;
	}
?>
	</table>
</fieldset>

<?
if( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NuevoModuloFactura') )
{
?>
<!-- ASOCIAR DOC LEGALES -->
<fieldset class="tb_base" style="width:85%; border: 1px solid #BDBDBD;">
	<legend onclick="MuestraOculta('div_doc_legales_asociados')" style="cursor:pointer">
		<span id="doc_legales_img"><img src="<?=Conf::ImgDir()?>/mas.gif" border="0" id="doc_legales_img"></span>
		&nbsp;<?=__('Documentos legales por defecto')?>
	</legend>
	<div id="div_doc_legales_asociados" style='display:none'>
		<p><center>Ingrese los documentos legales que desea generar en el proceso de facturación</center></p>
		<?php include dirname(__FILE__) . '/agregar_doc_legales.php'; ?>
	</div>
</fieldset>
<!-- ASOCIAR DOC LEGALES -->
<? 
} 
?>

	<table>
	<tr><td>&nbsp;</td><td><input type="submit" value="<?=__('Guardar') ?>" class="btn" /></td></tr>
	</table>
</form>

<script language="javascript" type="text/javascript">
Calendar.setup(
	{
		inputField	: "fecha",				// ID of the input field
		ifFormat	: "%d-%m-%Y",			// the date format
		button			: "img_fecha"		// ID of the button
	}

);
</script>
<?
	$pagina->PrintBottom($popup);
?>