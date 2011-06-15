<?
	require_once dirname(__FILE__).'/../../../conf.php';

	require_once Conf::ServerDir().'/fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/fw/classes/Usuario.php';
	require_once Conf::ServerDir().'/fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/fw/classes/Html.php';

	$Sesion = new Sesion('');
	
	$pagina = new Pagina($Sesion);
	
	$pagina->titulo = "Listado de mis grupos";

	$pagina->PrintHeaders();

	$pagina->PrintTop();

	if($desde=="")
		$desde=0;
	if($x_pag=="")
		$x_pag=30;
	if($orden == '')
		$orden= 'titulo';

?>
<script language="javascript" type="text/javascript">
<!-- //
function OrdenarLista( tipo )
{
	 var form = document.getElementById( 'formProyectos' );
	 form.orden.value = tipo;
     form.submit();
     return true;
}
// -->
</script>
<table width="96%" align="left">
    <tr>
        <td width="20">&nbsp;</td>
        <td valign="top">
 <form id="formProyectos" name="formProyectos" method="post">
 <input type="hidden" name="x_pag" value="<?=$x_pag?>">
 <input type="hidden" name="desde" value="<?=$desde?>">
 <input type="hidden" name="opc" value="buscar">
 <input type="hidden" name="orden" value="">
<table width="100%" align="left">
    <tr>
        <td valign="top" class="subtitulo" align="left" colspan="4">
             <img border=0 src="<?=Conf::ImgDir()?>/proyectos_16.gif"> Mis Grupos
            <hr class="subtitulo">
        </td>
    </tr>

	<tr>
		<td valign="top" class="texto_suave" align="center">
			<a href="#" class="texto_suave" onclick="OrdenarLista('titulo');">Nombre</a>
		</td>
		<td valign="top" class="texto_suave" align="left">
			Descripción
		</td>
		<td valign="top" class="texto_suave" align="center">
			<a class="texto_suave" href="#" onclick="OrdenarLista('fecha_creacion');">Creación</a>
		</td>
        <td valign="top" class="texto_suave" align="center">
            Opciones
        </td>
	</tr>
<?

	$query = "SELECT SQL_CALC_FOUND_ROWS proyecto.* FROM proyecto, proyecto_usuario WHERE 
							proyecto.id_proyecto = proyecto_usuario.id_proyecto AND
							proyecto_usuario.rut_usuario = '".$Sesion->usuario->fields['rut']."'
						ORDER BY $orden ASC 
						LIMIT $desde, $x_pag";
	$proyectos = new ListaProyectos ( $Sesion,'', $query );
			
	echo Html::PrintListRows($Sesion, $proyectos, 'PrintRow');
	echo Html::PrintListPages($proyectos, $desde, $x_pag, 'PrintLinkPage');

?>
 </form>
</table>
<?

    	function PrintRow (& $fila)
   	 	{
			$fields = &$fila->fields;
			global $Sesion;
			$id_proyecto = $fields['id_proyecto'];
			$opciones="<a href='ver_proyecto.php?id_proyecto=".$fields['id_proyecto']."' ><img border=0 src='".Conf::ImgDir()."/iconos/16/ver_16.gif' title='Ver'></a>&nbsp;<a href=ver_foro_proyecto.php?id_proyecto=".$fields['id_proyecto']."><img border=0 src='".Conf::ImgDir()."/foro_16.gif' alt='Ver Foro' title='Ver Foro' border=0></a>
<a href='../../../app/modulos/noticia/listar_noticias_proyectos.php?id_proyecto=".$fields['id_proyecto']."'><img border=0 src='".Conf::ImgDir()."/noticia16.png' title='Ver Noticias'></a> ";

        $fecha = Utiles::sql2date($fields['fecha_creacion'],'%d/%m/%y');
		$titulo = $fields['titulo'];
        $html.=<<<HTML
  <tr>
        <td valign="top" align="left">
            <a href="ver_proyecto.php?id_proyecto=${fields['id_proyecto']}">$titulo</a>
        </td>
        <td valign="top" align="left">
            ${fields['resumen']}
        </td>
        <td valign="top" align="center">
            $fecha
        </td>
        <td valign="top" align="center">
            $opciones
        </td>
  </tr>
  <tr>
	<td  colspan="4">
		<hr size=1>
	</td>
 </tr>
HTML;
			return $html;
		}

?>
</td>
</tr>
</table>


<script language="javascript">	
<!-- //

function PrintLinkPage( page )
{
	document.formProyectos.desde.value= (page-1)*document.formProyectos.x_pag.value;
	document.formProyectos.submit();
}

// ->
</script>

<?
	$pagina->PrintBottom();
?>

