<?php
   require_once dirname(__FILE__).'/../../../conf.php';
    require_once Conf::ServerDir().'/app/modulos/encuesta/classes/Encuesta.php';
    require_once Conf::ServerDir().'/app/modulos/encuesta/classes/Pregunta.php';
    require_once Conf::ServerDir().'/app/modulos/encuesta/classes/Alternativa.php';
    require_once Conf::ServerDir().'/app/modulos/encuesta/classes/Reportes.php';


    require_once Conf::ServerDir().'/app/modulos/encuesta/classes/Lista.php';
    require_once Conf::ServerDir().'/fw/classes/Pagina.php';
    require_once Conf::ServerDir().'/fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/fw/classes/Usuario.php';
    require_once Conf::ServerDir().'/fw/classes/Utiles.php';
    require_once Conf::ServerDir().'/fw/classes/Html.php';

    $sesion = new Sesion( array('ADM') );

    $pagina = new Pagina($sesion);

   $pagina->titulo ="Preguntas de la Encuesta";

    $enc = new Encuesta($sesion);
    if(!$enc->Load($id_encuesta))
		$pagina->FatalError("Encuesta Inv�lida");

	if($enc->fields['estado']=='CREADA')
		$pagina->FatalError("No puede ver los reportes de esta encuesta por a�n no es EMITIDA");

    $pagina->PrintHeaders();

    $pagina->PrintTop();
?>
<table width="96%" align="left">
    <tr>
        <td width="20">&nbsp;</td>
        <td valign="top">
	</tr>
	<tr>
		<td></td>
		<td align=center>
		 <img src=grafico_encuesta.php?id_encuesta=<?=$id_encuesta?>>
		</td>
	</tr>
	<tr>
		<td></td>
		<td align=right>
		<img src=<?=Conf::ImgDir()?>/excel.gif> <a href=bajar_excel_encuesta.php?id_encuesta=<?=$id_encuesta?>>bajar detalle</a>
		</td>
	</tr>
    <tr>
    <td></td>
    <td>
<?php
	$lista_preguntas = new ListaPreguntas($sesion,'',"SELECT * FROM encuesta_pregunta WHERE id_encuesta = $id_encuesta");

?>
<table width="100%" align="left">
    <tr>
        <td valign="top" class="subtitulo" align="left" colspan="3">
              <img border=0 src="<?=Conf::ImgDir()?>/ver_encuesta16.gif"> Lista de preguntas: <?=$enc->fields['titulo']?>
            <hr class="subtitulo">
        </td>
    </tr>
<?php
   for($x=0;$x<$lista_preguntas->num;$x++)// Busca las preguntas de la encuesta
    {
        $pregunta = $lista_preguntas->Get($x);
        $id_encuesta_pregunta = $pregunta->fields['id_encuesta_pregunta'];

?>
        <tr>
            <td>
        <br><strong><?=$x+1?> - <?=$pregunta->fields['glosa_pregunta']?></strong>&nbsp;&nbsp;&nbsp;<br><br>

<?php
       if($pregunta->fields['tipo'] == 'ALTERNATIVA') // si es alternativa busca las alternativas
        {
            $lista_alternativas = new ListaPreguntasAlternativas($sesion,'',"SELECT * FROM encuesta_pregunta_alternativa
                                                                    WHERE id_encuesta_pregunta = $id_encuesta_pregunta");

                for($y=0;$y<$lista_alternativas->num;$y++)
                {
                    $alternativas = $lista_alternativas->Get($y);
                    $id_alternativa = $alternativas->fields['id_encuesta_pregunta_alternativa'];
					$cant = Reportes::Alternativa($sesion, $id_encuesta_pregunta,$id_alternativa);

?>


                <?=$y+1?>) <input type="radio" name="respuesta[<?=$id_encuesta_pregunta?>]" value="<?=$id_alternativa?>" disabled>
                <?=$alternativas->fields['glosa_alternativa']?> (<?=$cant['num']?>)<br>
<?php

                }
?>
			</td>
		<tr>
			<td align=center>
			   <br><img src=grafico_pregunta.php?id_encuesta_pregunta=<?=$id_encuesta_pregunta?>>
<?php
       }
        else // si es abierta, despliega un text area
        {
?>
Pregunta Abierta
<?php
       }
?>
        </td>
    </tr>
    <tr>
        <td>
            <hr size=1>
        </td>
    </tr>
<?php
   }
?>
</table>
</td>
</tr>
</table>
<?php
   $pagina->PrintBottom();
?>


