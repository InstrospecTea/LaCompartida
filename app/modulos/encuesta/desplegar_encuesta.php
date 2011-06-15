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

   $pagina->titulo ="Preguntas de la Encuesta";

    $enc = new Encuesta($sesion);
    if(!$enc->Load($id_encuesta))
        $pagina->FatalError("Encuesta Inválida");


    $pagina->PrintHeaders();

    $pagina->PrintTop();
?>

<table width="96%" align="left">
    <tr>
        <td width="20">&nbsp;</td>
        <td valign="top">
    <tr>
    <td></td>
    <td>
<?
	$lista_preguntas = new ListaPreguntas($sesion,'',"SELECT * FROM encuesta_pregunta WHERE id_encuesta = $id_encuesta");

?>
<table width="100%" align="left">
    <tr>
        <td valign="top" class="subtitulo" align="left" colspan="3">
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
    </tr>
    <tr>
        <td>
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


