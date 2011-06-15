<?
	require_once dirname(__FILE__).'/../../../conf.php';
    require_once Conf::ServerDir().'/app/modulos/encuesta/classes/Encuesta.php';
    require_once Conf::ServerDir().'/app/modulos/encuesta/classes/Pregunta.php';
    require_once Conf::ServerDir().'/app/modulos/encuesta/classes/Alternativa.php';

    require_once Conf::ServerDir().'/app/modulos/encuesta/classes/Lista.php';
	require_once Conf::ServerDir().'/fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/fw/classes/Usuario.php';
	require_once Conf::ServerDir().'/fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/fw/classes/Html.php';

	$sesion = new Sesion( array('ADM') );

	$pagina = new Pagina($sesion);
	
    $enc = new Encuesta($sesion);
    if(!$enc->Load($id_encuesta))
		$pagina->FatalError("Encuesta Inválida");

	if($enc->fields['estado']=='EMITIDA')
		$pagina->FatalError("la encuesta ya se encuesta emitida");

    $rut = $sesion->usuario->fields['rut'];

	$pagina->titulo ="Preguntas de la Encuesta";

	if($opc=='terminar')
	{
		$encuesta = new Encuesta($sesion);
		$encuesta->Load($id_encuesta);
		$encuesta->Edit('estado', 'EMITIDA');
		if($encuesta->Write())
			 $pagina->Redirect( 'ver_encuestas.php?id_empresa='.$encuesta->fields['id_empresa']);
		else
			$pagina->AddError($encuesta->error);
	}
    if($opc=='no_terminar')
    {
        $encuesta = new Encuesta($sesion);
        if($encuesta->Load($id_encuesta))
             $pagina->Redirect( 'ver_encuestas.php?id_empresa='.$encuesta->fields['id_empresa']);
    }

	if($opc=='borrarPregunta')
	{
		$pre = new Pregunta($sesion);
		if($pre->Load($id_encuesta_pregunta))
		{
			if($pre->Delete())	
				$pagina->AddInfo("Pregunta Eliminada");
			else
				$pagina->AddError($pre->error);
		}
		else
			 $pagina->AddError("No existe la pregunta");
	}

	if($opc=='guardar')
	{
		if($glosa_pregunta == '')
		$pagina->AddError("Debe ingresar una pregunta");
		else
		{
			$pregunta= new Pregunta($sesion);
			$pregunta->Edit('id_encuesta',$id_encuesta);
            $pregunta->Edit('glosa_pregunta',$glosa_pregunta);

			if($tipo == 'abierta')
			{
            	$pregunta->Edit('tipo','ABIERTA');
				if ($pregunta->Write())
					$pagina->AddInfo("Pregunta guardada con éxito");
			}
			else
			{
				$pregunta->Edit('tipo','ALTERNATIVA');
	            if ($pregunta->Write())
		            $pagina->AddInfo("Pregunta guardada con éxito");
       			foreach($alternativa as $key => $value)
        		{
		            $alter = new Alternativa($sesion);
                    $alter->Edit('id_encuesta_pregunta',$pregunta->fields['id_encuesta_pregunta']);
                    $alter->Edit('glosa_alternativa',$value);
                    $alter->Write();
        		}
			}
		}
	}

	$pagina->PrintHeaders();

	$pagina->PrintTop();
?>
<script>
function MostrarAlt()
{
	var alternativas= document.getElementById( 'alternativas' );
	alternativas.style['display']='inline';
}
function OcultarAlt()
{
    var alternativas= document.getElementById( 'alternativas' );
    alternativas.style['display']='none';
}

var textNumber = 1;
function addTextBox(form, afterElement) 
{
	var div = document.getElementById("alternativas");
	var label = document.createElement("label");
	label.setAttribute("id", "label" + (++textNumber));
	var textField = document.createElement("input");
	textField.setAttribute("type","text");
	textField.setAttribute("name","alternativa[" + textNumber + "]");
	label.appendChild(document.createElement("br"));
	label.appendChild(document.createTextNode("Alternativa #" + textNumber+": "));
	label.appendChild(textField);
	var button = document.createElement("input");
	button.setAttribute("type", "button");
	button.setAttribute("onclick","EliminarAlternativa(" + textNumber + ", this.form)");
	button.setAttribute("value", "Eliminar alternativa");
	label.appendChild(button);

	form.appendChild(label);

	//var par = document.getElementById("parrafo");
	//document.body.insertBefore(label,par);
	return false;
}
function EliminarAlternativa( id, form )
{
	var id = "label" + id;
	var nodo = document.getElementById( id );
	var div = document.getElementById("alternativas");
	form.removeChild(nodo);
}


function Terminar(form)
{
	if(confirm("Después de emitir la encuesta, esta no podrá ser modificada ¿Está seguro que desea continuar?"))
	{
    	form.opc.value = 'terminar';
  		return true;
  	}
  	return false;
}



function Guardar_P(form)
{

	if(form.glosa_pregunta.value=='')
	{
	alert('Debe Ingresar Una Pregunta');
	form.glosa_pregunta.focus();
	return false;
	}
	else
	{
	form.opc.value='guardar';

	}




}



</script>




<table width="96%" align="left">
    <tr>
        <td width="20">&nbsp;</td>
        <td valign="top">
		 <form id="form" name="Pregunta" method="post">
		 <input type="hidden" name="opc" value="<?=$opc?>">
		 <input type="hidden" name="id_encuesta" value="<?=$id_encuesta?>">
			<strong>Ingrese el texto de la pregunta:</strong><br>	
			<textarea name=glosa_pregunta cols=60 rows=2></textarea><br><br>
            <strong>Seleccione el tipo de pregunta:</strong> <br>
			<input type="radio" value="abierta" checked name="tipo" onclick="OcultarAlt();">Abierta 
			<input type="radio" name="tipo"  value="alternativa" onclick="MostrarAlt();"> Alternativas<br>
			<div id=alternativas style="display:none;" align=left>
			<p id="parrafo"><input type="button" value="Agregar Alternativa" onclick="addTextBox(this.form,this.parentNode)" /></p>
			Alternativa #1: <input type="text" id=alternativa name="alternativa[1]" />
			</div>
		</td>
	</tr>
	<tr>
		<td></td>
		<td align=right>
           <input type="submit" value="Guardar Pregunta" onclick="return Guardar_P(this.form)">&nbsp;&nbsp;


            <input type="submit" value="Continuar mas tarde" onclick="this.form.opc.value='no_terminar';" >&nbsp;&nbsp;
            <input type="submit" value="Emitir Encuesta" onclick="return Terminar(this.form)">
   			</form>
        </td>
    </tr>
	<tr>
	<td></td>
	<td>
<?
    $lista_preguntas = new ListaPreguntas($sesion,'',"SELECT * FROM encuesta_pregunta WHERE id_encuesta = $id_encuesta");

?>
<table width="100%" align="left">
    <tr>
        <td valign="top" class="subtitulo" align="left" colspan="2">
              <img border=0 src="<?=Conf::ImgDir()?>/ver_encuesta16.gif"> Lista de preguntas: <?=$enc->fields['titulo']?>
            <hr class="subtitulo">
        </td>
    </tr>
<?
    for($x=0;$x<$lista_preguntas->num;$x++)// Busca las preguntas de la encuesta
    {
        $pregunta = $lista_preguntas->Get($x);
        $id_encuesta_pregunta = $pregunta->fields['id_encuesta_pregunta'];

?>
        <tr>
            <td width=80%>
        <br><strong><?=$x+1?> - <?=$pregunta->fields['glosa_pregunta']?></strong>&nbsp;&nbsp;&nbsp;<br><br>

<?
        if($pregunta->fields['tipo'] == 'ALTERNATIVA') // si es alternativa busca las alternativas
        {
            $lista_alternativas = new ListaPreguntasAlternativas($sesion,'',"SELECT * FROM encuesta_pregunta_alternativa
                                                                    WHERE id_encuesta_pregunta = $id_encuesta_pregunta");
                for($y=0;$y<$lista_alternativas->num;$y++)
                {
                    $alternativas = $lista_alternativas->Get($y);
                    $id_alternativa = $alternativas->fields['id_encuesta_pregunta_alternativa'];
?>
                <?=$y+1?>) <input type="radio" name="respuesta[<?=$id_encuesta_pregunta?>]" value="<?=$id_alternativa?>" disabled>
                <?=$alternativas->fields['glosa_alternativa']?><br>
<?

                }
        }
        else // si es abierta, despliega un text area
        {
?>
Pregunta Abierta
<?
        }
?>
        </td>
		<td align=center>
<br><br><a href="encuesta_paso2.php?opc=borrarPregunta&id_encuesta_pregunta=<?=$id_encuesta_pregunta?>&id_encuesta=<?=$id_encuesta?>"><img border=0 src="<?=Conf::ImgDir()?>/eliminar.gif" title="Eliminar Pregunta"></a>
		</td>
    </tr>
	<tr>
		<td colspan=2>
			<hr size=1>
        </td>
    </tr>

<?
    }
?>
</table>
</td>
</tr>
</table>

<?
    $pagina->PrintBottom();
?>

