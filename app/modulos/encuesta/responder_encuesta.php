<?
	require_once dirname(__FILE__).'/../../../conf.php';
    require_once Conf::ServerDir().'/app/modulos/encuesta/classes/Encuesta.php';
    require_once Conf::ServerDir().'/app/modulos/encuesta/classes/Pregunta.php';
    require_once Conf::ServerDir().'/app/modulos/encuesta/classes/RespuestaAlternativa.php';
    require_once Conf::ServerDir().'/app/modulos/encuesta/classes/RespuestaAbierta.php';
    require_once Conf::ServerDir().'/app/modulos/encuesta/classes/Alternativa.php';

    require_once Conf::ServerDir().'/app/modulos/encuesta/classes/Lista.php';
	require_once Conf::ServerDir().'/fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/fw/classes/Usuario.php';
	require_once Conf::ServerDir().'/fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/fw/classes/Html.php';

	$sesion = new Sesion( '' );
	$pagina = new Pagina($sesion);
    	    
	$pagina->titulo = "Responder Encuesta";

	$encuesta = new Encuesta($sesion);
    if(!$encuesta->Load($id_encuesta))
        $pagina->FatalError("Encuesta Inválida");

	$sesion->usuario->LoadEmpresa();

	$id_empresa_enc = $encuesta->fields['id_empresa'];
	$id_empresa_user = $sesion->usuario->id_empresa;


	if($id_empresa_enc != $id_empresa_user)  // Valida que la emcuesta sea de la empresa a la cual pertenece el usuario
		$pagina->FatalError("No puede contestar encuestas de otra empresa"); 


	$id_encuesta = $encuesta->fields['id_encuesta'];

	$lista_preguntas = new ListaPreguntas($sesion,'',"SELECT * FROM encuesta_pregunta WHERE id_encuesta = $id_encuesta");

	$rut = $sesion->usuario->fields['rut'];

	if($opc=='guardar')
	{
		$detec=true;
        for($x=0;$x<$lista_preguntas->num;$x++)// Busca las preguntas de la encuesta
        {
             $pregunta = $lista_preguntas->Get($x);
             $id_pregunta = $pregunta->fields['id_encuesta_pregunta'];
             if($pregunta->fields['tipo'] == 'ALTERNATIVA')
             {
                 if($respuesta[$id_pregunta] == '')
                     $detec=false;
             }
             else
             {
                 if($respuesta[$id_pregunta] == '')
                     $detec=false;
             }
	 	}

		if($detec==true)
		{
		    for($x=0;$x<$lista_preguntas->num;$x++)// Busca las preguntas de la encuesta
    		{	
	        	$pregunta = $lista_preguntas->Get($x);
		        $id_pregunta = $pregunta->fields['id_encuesta_pregunta'];

				if($pregunta->fields['tipo'] == 'ALTERNATIVA')
				{
					$resp = new RespuestaAlternativa($sesion,'','');
					$resp->Edit('id_encuesta_pregunta',$id_pregunta);
					$resp->Edit('id_encuesta_pregunta_alternativa',$respuesta[$id_pregunta]);
					$resp->Edit('rut_usuario', $rut);
					if(!$resp->Write())
						$detec=false;
				}
				else
				{
					$resp = new RespuestaAbierta($sesion,'','');
	  				$resp->Edit('id_encuesta_pregunta',$id_pregunta);
					$resp->Edit('respuesta',$respuesta[$id_pregunta]);
			        $resp->Edit('rut_usuario', $rut);
					if(!$resp->Write())
						$detec=false;
				}
			}
		}
		if($detec==true)
		{
			$pagina->AddInfo("Encuesta guardada con éxito");
		    $pagina->Redirect( 'ver_encuestas.php?guardada=yes');	
		}
		else
			$pagina->AddError("Debe responder todas las preguntas para continuar");
	}

    $pagina->PrintHeaders();

    $pagina->PrintTop();
?>
<table width="96%" align="left">
    <tr>
        <td width="20">&nbsp;</td>
        <td valign="top">
<table width="100%" align="left">
    <tr>
        <td valign="top" class="subtitulo" align="left" colspan="3">
              <img border=0 src="<?=Conf::ImgDir()?>/encuesta_xresp16.gif"> Responder encuesta: <?=$encuesta->fields['titulo']?>
            <hr class="subtitulo">
        </td>
    </tr>
	<tr>
		<td>
			Asegurese de responder todas las preguntas antes de terminar la encuesta
		</td>
	</tr>
 <form id="form" name="Encuesta" method="post">
 <input type="hidden" name="opc" value="guardar">
<?
	for($x=0;$x<$lista_preguntas->num;$x++)// Busca las preguntas de la encuesta
	{
		$pregunta = $lista_preguntas->Get($x);
		$id_encuesta_pregunta = $pregunta->fields['id_encuesta_pregunta'];

?>
		<tr>
			<td>
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
				<?=$y+1?>) <input type="radio" name="respuesta[<?=$id_encuesta_pregunta?>]" value="<?=$id_alternativa?>" 
							<?=$respuesta[$id_encuesta_pregunta] == $id_alternativa ? 'checked':''?>>
				<?=$alternativas->fields['glosa_alternativa']?><br>	
<?

				}
		}
		else // si es abierta, despliega un text area
		{
?>
		<textarea name="respuesta[<?=$id_encuesta_pregunta?>]" rows=3 cols=60><?=$respuesta[$id_encuesta_pregunta]?></textarea><br>
<?
		}
?>
		</td>
	</tr>
<?
	}
?>
	<tr>
		<td align=right>
<br><br><input type="submit" value="Terminar Encuesta">	
	</form>
		</td>
	</tr>
	</table>
	</td>
	</tr>
	</table>
<?
    $pagina->PrintBottom();
?>

