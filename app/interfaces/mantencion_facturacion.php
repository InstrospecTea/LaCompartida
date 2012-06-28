<?php
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
require_once Conf::ServerDir().'/../fw/funciones/funciones.php';
require_once Conf::ServerDir().'/../fw/tablas/funciones_mantencion_tablas.php';
$sesion = new Sesion('');
$pagina = new Pagina($sesion);
$pagina->titulo = __('Mantención de facturación');
$pagina->PrintTop();

$tablas = array('prm_documento_legal','prm_banco');

//OBS: ingresar columnas no editables separados po coma
$columnas_no_editables['prm_documento_legal'] = 'codigo';

if($tabla == "")
	$tabla = $tablas[0];

?>
<script type="text/javascript">
//variable para almacenar el contenido original del campo a editar

var cols_no_edit='<?php echo $columnas_no_editables[$tabla];?>';

var valor_original = '';
function editCell(id, cellSpan, tabla) 
{
	var inputWidth = (document.getElementById(id).offsetWidth / 40);
	var oldCellSpan = cellSpan.innerHTML;

	//guardamos el valor original del campo a editar
	valor_original = oldCellSpan;

	if(tabla == "" || tabla == undefined)
		document.getElementById(id).innerHTML = "<form name=\"activeForm\" onsubmit=\"parseForm('"+id+"', '"+id+"input');return false;\" style=\"margin:0;\" action=\"\"><input type=\"text\" class=\"dynaInput\" id=\""+id+"input\" size=\""+ inputWidth + "\" onblur=\"parseForm('"+id+"', '"+id+"input');return false;\"><br /><noscript><input value=\"OK\" type=\"submit\"></noscript></form>";
	else
	{
		document.getElementById(id).innerHTML = "<form name=\"activeForm\" onsubmit=\"parseForm('"+id+"', '"+id+"input','"+tabla+"');return false;\" style=\"margin:0;\" action=\"\"><select class=\"dynaInput\" id=\""+id+"input\"  onblur=\"parseForm('"+id+"', '"+id+"input','" + tabla + "');return false;\"></select><br /><noscript><input value=\"OK\" type=\"submit\"></noscript></form>";
		AgregarOpciones(id + "input", tabla, oldCellSpan);
	}

	document.getElementById(id).style.background = '#ffc';
	document.getElementById(id).style.border = '1px solid #fc0';
	document.getElementById(id+"input").value = oldCellSpan;
	document.getElementById(id+"input").focus();
}
function parseForm(cellID, inputID, tabla) 
{
	var temp = document.getElementById(inputID).value;
	var obj = /^(\s*)([\W\w]*)(\b\s*$)/;
	if (obj.test(temp)) 
	{ 
		temp = temp.replace(obj, '$2'); 
	}
	var obj = /  /g;
	while (temp.match(obj)) { temp = temp.replace(obj, " "); }
	if (temp == " ") 
	{ 
		temp = ""; 
	}
	if (! temp) 
	{
		alert("Este campo debe contener al menos un carácter visible.");
		return;
	}
	var st = document.getElementById(inputID).value + '~~|~~' + cellID;
	//document.getElementById(cellID).innerHTML = "<span class=\"update\">Updating...</span>";
	//document.getElementById(cellID).innerHTML = "<span class=\"update\">" + temp + "</span>";
	if(tabla != undefined)
		document.getElementById(cellID).innerHTML = "<div class='dynaDiv' onclick=\"editCell('" + cellID + "',this, '" + tabla + "');\">" + temp + "</div>";
	else
		document.getElementById(cellID).innerHTML = "<div class='dynaDiv' onclick=\"editCell('" + cellID + "',this);\">" + temp + "</div>";
	document.getElementById(cellID).style.border = 'none';
	GuardarCampo(cellID,temp,tabla);
}
function GuardarCampo(id_celda, valor_actual, tabla)
{
	//si no ha modificado nada, no realiza ningun cambio
	if(valor_actual == valor_original)
		return false;
	
	
	aux = id_celda.split("-|-");
	campo = aux[0];
	pkey = aux[1];
	
	// verificar si la columna no es editable
	col_no_edit = cols_no_edit.split(",");
	for(var x = 0; x < col_no_edit.length; x++) {	
		if(campo == col_no_edit[x]){
			alert('Este dato no es editable');
			document.getElementById(id_celda).innerHTML = "<div class='dynaDiv'>" + valor_original  + "</div>";
			return false;
		}
	}
	//si cancela reasigna el valor original
	if(!confirm("¿Desea modificar el valor del campo '" + campo +  "'?"))
	{
		if(tabla != undefined)
			document.getElementById(id_celda).innerHTML = "<div class='dynaDiv' onclick=\"editCell('" + id_celda + "',this, '" + tabla + "');\">" + valor_original  + "</div>";
		else
			document.getElementById(id_celda).innerHTML = "<div class='dynaDiv' onclick=\"editCell('" + id_celda + "',this);\">" + valor_original + "</div>";
		return false;
	}
	
	var http = getXMLHTTP();
	url = '<?=Conf::Host()?>/fw/tablas/ajax_tablas.php?accion=guardar_campo&campo=' + campo + '&pkey=' + pkey + '&valor=' + valor_actual + '&tabla=<?= $tabla ?>';
	http.open('get', url);

	loading( 'Guardando información en la base de datos.' );

	http.onreadystatechange = function()
{
	if(http.readyState == 4)
	{
		var response = http.responseText;
		var update = new Array();

		offLoading();

		if(response.indexOf('OK') == -1) 
		{
			alert(sacarHTML(response));
			location.reload();
		}
	}
};
http.send(null);
return;
}
function sacarHTML( strSrc ) 
{
	return ( strSrc.replace( /<[^<|>]+?>/gi,'' ) );
}
function AgregarOpciones(id_select , tabla_punto_id, selected)
{
	var http = getXMLHTTP();

	var tabla = tabla_punto_id.split(".");
	nombre_tabla = tabla[0];

	http.open('get', '<?=Conf::Host()?>/fw/tablas/ajax_tablas.php?accion=cargar_tabla&tabla=' + tabla_punto_id);

	loading( 'Obteniendo información del servidor.' );

	http.onreadystatechange = function()
{
	if(http.readyState == 4)
	{
		offLoading();

		var response = http.responseText;
		var update = new Array();

		response = response.split('\n');
		resp = response[0];

		if(resp.indexOf('|') != -1) 
		{
			opciones = resp.split('|');
			select = document.getElementById(id_select);

			select.options[select.options.length] = new Option('NULL', 'NULL');
			for(i = 0; i < opciones.length; i += 2)
			{
				select.options[select.options.length] = new Option(opciones[i+1], opciones[i]);
			}
			select.value = selected;
		}
		else
			alert(response);
	}
};
http.send(null);
return;
}
</script>
<style type="text/css">
.dynaInput 
{
	border: none;
	background: #ffc;
	font: 12px/24px Tahoma, Arial, Geneva, sans-serif;
	width: 100%;
	height: 100%;
}

.dynaDiv 
{
	display: block;
	height: 100%;
	width: 100%;
	font: 12px/24px Tahoma, Arial, Geneva, sans-serif;
}


</style>
<?
	$tooltip_agregar = __('Haga clic sobre esta imagen para ingresar una nueva fila a esta tabla.');
	 if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ) ) 
			{
				$width = '90%';
				$width_adentro = '20%';
				$width_adentro_2 = '100%';
			}
		else
		 	{
		 		$width = '90%';
				$width_adentro = '80%';
				$width_adentro_2 = '100%';
		 	} 
?>

<table width=<?=$width ?> align="center">
	<tr>
		<td align=right width=<?=$width_adentro ?>>
			<?=__('Tabla')?>
		</td>
		<td width=<?=$width_adentro_2 ?>>
			<form name=formulario>
			<?= Html::SelectArray($tablas, "tabla", $tabla, $opciones='onchange=this.form.submit()') ?>
			<a href="#" onclick="window.location='<?=Conf::Host()?>/fw/tablas/agregar_campo.php?tabla=' + formulario.tabla.value;" onmouseover="ddrivetip('<?= $tooltip_agregar ?>')" onmouseout="hideddrivetip();" ><img border="0" src="<?= Conf::ImgDir() ?>/agregar.gif"></a>
			</form>
		</td>
	</tr>
	<tr>
		<td align=center colspan=2>
			<br>
			<? if($tabla != "") echo( Tabla($sesion, $tabla) ); ?>
			<br>
		</td>
	</tr>
	<tr>
		<td align=center colspan=2><input type=submit value=Guardar /></td>
	</tr>
</table>
<?
	$pagina->PrintBottom();
?>
