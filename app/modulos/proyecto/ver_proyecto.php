<?
	require_once dirname(__FILE__).'/../../../conf.php';

	require_once Conf::ServerDir().'/fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/app/classes/PaginaProyecto.php';
	require_once Conf::ServerDir().'/fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/fw/classes/Html.php';
    require_once Conf::ServerDir().'/fw/modulos/proyecto/classes/ArchivoProyecto.php';


	$Sesion = new Sesion('');
	$pagina = new PaginaProyecto($Sesion);
    $proyecto = new Proyecto($Sesion);
	is_numeric($id_proyecto) or $pagina->FatalError("No se ha indicado un id_proyecto válido",__FILE__,__LINE__);

	Proyecto::PermisoVer($id_proyecto, $Sesion) or $pagina->FatalError("No tiene permiso para ver este proyecto",__FILE__,__LINE__);
	if(!$proyecto->Load($id_proyecto))
		$pagina->FatalError("Grupo inválido");
	$proyecto->LoadFiles();

	$pagina->titulo = "Ver Proyecto";

	$pagina->PrintHeaders();

	$pagina->PrintTop($id_proyecto);

?>
<table width="96%" align="left">
	<tr>
		<td width="20">&nbsp;</td>
		<td valign="top">

<table  width="100%" align="left">
 <form id="form" name="formArchivos" method="post">
 <input type="hidden" name="opc" value="">
 <input type="hidden" name="x_pag" value="<?=$x_pag?>">
 <input type="hidden" name="desde" value="">
	<tr>
		<td valign="top" class="subtitulo" align="left" colspan="4">
		    Proyecto: <?=$proyecto->fields['titulo']?>
			<hr class="subtitulo"/>
		</td>
	</tr>
	<tr>
		<td valign="top" class="text" align="left">
			<strong>Descripción</strong>
		</td>
        <td valign="top" class="texto" align="left">
            <?=$proyecto->fields['resumen']?><br><br>&nbsp;
        </td>
	</tr>
  <tr>
        <td valign="top" class="texto" align="center" colspan=4>
            <strong>Lista de archivos de este Proyecto</strong>
        </td>
  </tr>
  <tr>
       <td valign="top" class="texto_suave" align="center" width="20%" >
            <strong>Nombre</strong>
        </td>
       <td valign="top" class="texto_suave" align="rigth" >
            <strong>&nbsp;Descripción</strong>
        </td>
       <td valign="top" class="texto_suave" align="center" >
            <strong>&nbsp;Modificado</strong>
        </td>
       <td valign="top" class="texto_suave" align="center" >
            <strong>Opciones</strong>
        </td>
  </tr>
<?
	if(!is_numeric($desde))
		$desde=0;
    if(!is_numeric($x_pag))
        $x_pag=30;

	$params['tabla']="archivos_proyectos";
	$proyecto->archivos = new ListaArchivos ( $Sesion,$params, "SELECT SQL_CALC_FOUND_ROWS * from archivos_proyectos where id_proyecto='$id_proyecto' limit $desde, $x_pag");
	echo Html::PrintListRows($Sesion, $proyecto->archivos, 'PrintRow');
	echo Html::PrintListPages($proyecto->archivos, $desde, $x_pag, 'PrintLinkPage');
?>
 </form>
</table>
<?

    function PrintRow (& $archivo)
    {
		global $id_proyecto;
		$fields=&$archivo->fields;
        $fecha = Utiles::sql2date($fields['fecha_mod'],'%d/%m/%y');
		$img = Conf::ImgDir();
        $html.=<<<HTML
  <tr>
        <td valign="top" align="left">
            <a href="ver_archivo_proyecto.php?id_archivo=${fields['id_archivo']}&id_proyecto=$id_proyecto">${fields['nombre']}</a>
        </td>
        <td valign="top" align="left">
            ${fields['descripcion']}
        </td>
        <td valign="top" align="center">
            $fecha
        </td>

		    <td valign="top" align="center">
            <a href="ver_archivo_proyecto.php?id_archivo=${fields['id_archivo']}&id_proyecto=$id_proyecto" ><img border=0 src="$img/iconos/16/ver_16.gif" title='Ver'></a>        
        </td>

  </tr>

  <tr>
        <td valign="top" class="texto" align="left" colspan=4>
            <hr size=1>
        </td>
  </tr>

HTML;
		return $html;
    }

?>

<script language="javascript">	
<!-- //

function PrintLinkPage( page )
{
	document.formArchivos.desde.value= (page-1)*document.formArchivos.x_pag.value;
	document.formArchivos.submit();
}

// ->
</script>

