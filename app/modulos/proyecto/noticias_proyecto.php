<?
	require_once dirname(__FILE__).'/../../../conf.php';
    require_once Conf::ServerDir().'/fw/modulos/noticia/classes/Noticia.php';
    require_once Conf::ServerDir().'/fw/modulos/proyecto/classes/Proyecto.php';

	require_once Conf::ServerDir().'/fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/fw/classes/Lista.php';
	require_once Conf::ServerDir().'/fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/fw/classes/Usuario.php';
	require_once Conf::ServerDir().'/fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/fw/classes/Html.php';

	$sesion = new Sesion( array('CON') );
	$pagina = new Pagina($sesion);
	$proyecto = new Proyecto($sesion);

    Proyecto::PermisoEditar($id_proyecto,$sesion) or $pagina->FatalError("Usted no tiene permiso para editar este proyecto",__FILE__,__LINE__);	
    	    
	if($opc == 'agregar_not')
    {
        if($titulo== '' or $resumen== '' or $detalle== '')
            $pagina->AddError("Debe ingresar todos los datos de la noticia");
        else
        
        {
            $noticia=new Noticia($sesion);
            $noticia->Edit('titulo',$titulo);
            $noticia->Edit('resumen',$resumen);

            $noticia->Edit( 'detalle',$detalle);
            $noticia->Edit('id_noticia_agrupador',$id_noticia_agrupador);
            
		if( $noticia->Write() )
            {
                 $pagina->AddInfo( 'Noticia ingresada con exito...' );
            }
        }
	}

	
	$proyecto->Load($id_proyecto);
    $titulo_proyecto= $proyecto->fields['titulo'];


	$pagina->titulo = "Noticias - $titulo_proyecto";

	$pagina->PrintHeaders();

	$pagina->PrintTop();
?>
<form name="form1" method="post" action="">
    <input name="opc" type="hidden" value="agregar_not">
<table width="100%" height="44" border="0">
  <tr>
    <td height="40" class= "subtitulo"><img src="<?=Conf::ImgDir()?>/noticia16.png"> Agregar Noticia
      <hr class ="subtitulo" width="100%"></td>
  </tr>
</table>
<table width="100%" border="0">
  <tr>
    <td><strong>Titulo</strong></td>
    <td><input name="titulo" class="texto"  type="text" id="titulo" size="65"></td>
  </tr>
  <tr>
    <td><strong>Resumen</strong></td>
    <td><textarea name="resumen" id="resumen" cols="40" rows="4"></textarea></td>
  </tr>
  <tr>
    <td><strong>Detalle</strong></td>
    <td><textarea name="detalle" cols="40" rows="8" id="detalle"></textarea>
    </td>
  </tr>
</table>
<table width="308" border="0">
  <tr>
    <td width="341">
        <div align="right">
          <input type="submit" name="Submit" value="Agregar">
      </div></td>
  </tr>
</table>
</form>

<?
    $pagina->PrintBottom();
?>

