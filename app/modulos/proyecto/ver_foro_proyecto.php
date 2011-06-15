<?
    require_once dirname(__FILE__).'/../../../conf.php';
    require_once Conf::ServerDir().'/fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/app/classes/PaginaProyecto.php';
    require_once Conf::ServerDir().'/fw/classes/Utiles.php';
    require_once Conf::ServerDir().'/fw/modulos/foro/classes/Foro.php';

    $Sesion = new Sesion('');

    $pagina = new PaginaProyecto($Sesion);
    $pagina->titulo = "Foros";

    Proyecto::PermisoVer($id_proyecto, $Sesion) or $pagina->FatalError("No tiene permiso para ver este foro",__FILE__,__LINE__);

    $proyecto = new Proyecto($Sesion);
    $proyecto->Load($id_proyecto);

    is_numeric($desde) or $desde="0";
    is_numeric($x_pag) or $x_pag="30";

    $foro = new Foro($Sesion);
    $foro->Load($proyecto->fields['id_foro'],$desde,$x_pag);

	 if($opc=="nvo_tema")
    {
        if($titulo_tema == "")
            $pagina->AddError("Debe ingresar el titulo");
        else
           $id_foro_tema= $foro->AgregarTema($titulo_tema,$mensaje);
           $pagina->Redirect( '../foro/ver_tema_foro.php?id_foro_tema='.$id_foro_tema );


    }

	if(!$foro->Loaded())
		 $pagina->FatalError("Foro inválido");

    $pagina->PrintTop($id_proyecto);
	 $pagina->PrintHeaders();

?>
<table width="96%" align="left">
    <tr>
        <td width="20">&nbsp;</td>
        <td valign="top">
<table width="100%" align="left">
    <tr>
        <td valign="top" class="subtitulo" align="left" colspan="3">
              <img border=0 src="<?=Conf::ImgDir()?>/foro_16.gif"> Foro Grupo: <?=$foro->fields['titulo']?>
            <hr class="subtitulo"><br><br>
        </td>
    </tr>
    <tr>
        <td valign="top" align="right" colspan="3">
			<img border=0 src="<?=Conf::ImgDir()?>/agregar2.gif" alt="Nuevo Tema" border="0"> <a href="#Nuevo_Tema"><strong>nuevo tema</strong></a>
		</td>
	</tr>
  <tr>
    <td width="62%" class="texto_suave">&nbsp;Tema</td>
    <td width="28%" class="texto_suave" align="center">Ultimo mensaje</td>
    <td width="10%" class="texto_suave">Mensajes</td>
  </tr>
        <td valign="top" align="center" colspan="3">
<?

	$foro->Imprimir($desde,$x_pag);
?>
</td>
</tr>
<tr>
   <td valign="top" class="subtitulo" align="left" colspan="3">
<br>
<form method=post>
<input type=hidden name=id_foro value="<?=$foro->fields['id_foro']?>">
<input type=hidden name=opc value="nvo_tema">
<table width="100%" align="left" id="Nuevo_Tema">
	<tr>
		<td colspan=2 class="subtitulo">
			 <img src="<?=Conf::ImgDir()?>/agregar2.gif"> Nuevo Tema
			<hr size=1 color=black>
		</td>
	</tr>
	<tr>
		<td align=right>
		<strong>Título: </strong>
		</td>
		<td>
			<input name=titulo_tema size=41>
		</td>
	</tr>
	<tr>
		<td align=right>
			<strong>Mensaje: </strong>
		</td>
		<td>
			<textarea name=mensaje cols=35 rows=6></textarea>
		</td>
	</tr>
	<tr>
		<td></td>
		<td align=left>
			<input type=submit value="Agregar tema">
		</td>
	</tr>
</table>
</td>
</tr>
</table>
</form>
</td>
</tr>
</table>

<?
    $pagina->PrintBottom();
?>


