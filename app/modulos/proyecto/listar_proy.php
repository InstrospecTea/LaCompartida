<?
	require_once dirname(__FILE__).'/../../../conf.php';
    require_once Conf::ServerDir().'/fw/modulos/proyecto/classes/Proyecto.php';
    require_once Conf::ServerDir().'/fw/modulos/proyecto/classes/ListaProyecto.php';


	require_once Conf::ServerDir().'/fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/fw/classes/Usuario.php';
	require_once Conf::ServerDir().'/fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/fw/classes/Html.php';

    $Sesion = new Sesion( array('ADM') );
	
	$pagina = new Pagina($Sesion);
	
	$pagina->titulo = "Listado de Grupos";

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
<table width="100%" align="left">
 <form id="formProyectos" name="formProyectos" method="post">
 <input type="hidden" name="x_pag" value="<?=$x_pag?>">
 <input type="hidden" name="desde" value="<?=$desde?>">
 <input type="hidden" name="opc" value="buscar">
 <input type="hidden" name="orden" value="">
	<tr>
		<td valign="top" class="subtitulo" align="left" colspan="4">
		     <img border=0 src="<?=Conf::ImgDir()?>/buscar_16.gif"> Ingrese los filtros para la búsqueda del Grupo
			<hr class="subtitulo">
		</td>
	</tr>
	<tr>
		<td colspan=4>
		<table border="0" cellspacing="0" cellpadding="3" width="90%">
            Ingresa el Nombre, o si no lo sabes ingresa su descripción  o parte de ella.
        </td>
    </tr>
    <tr>
        <td valign="top" align="left" colspan="2"><img src="<?=Conf::ImgDir()?>/pix.gif" border="0" width="1" height="10"></td>
    </tr>
    <tr>
        <td valign="top" align="right">
            <strong>Nombre</strong>
        </td>
        <td valign="top" align="left">
            <input type="text" name="nombre" value="<?=$nombre?>" size="10">
        </td>
    </tr>
    <tr>
        <td valign="top" align="right">
            <strong>Descripción</strong>
        </td>
        <td valign="top" align="left">
            <input type="text" name="descripcion" value="<?=$descripcion?>" size="24">
        </td>
        </td>
    </tr>
	<tr>
        <td valign="top" align="right">
            <strong>Fecha Creación</strong>
        </td>
        <td valign="top" align="left">
<?=Html::PrintCalendar('fecha1',$fecha1, 'formProyectos');?>
&nbsp;&nbsp;
<?=Html::PrintCalendar('fecha2',$fecha2, 'formProyectos');?>
		</td>
    <tr>
        <td valign="top" align="right">
            &nbsp;
        </td>
        <td valign="top" align="left">
            <input type="submit" value="Buscar" onClick=" this.form.desde.value='';">
        </td>
    </tr>
</table>
		</td>

    <tr>
        <td valign="top" class="subtitulo" align="left" colspan="3">
            <img border=0 src="<?=Conf::ImgDir()?>/proyectos_16.gif"> Todos los Grupos

<td align="right">
   <img border=0 src="<?=Conf::ImgDir()?>/agregar2.gif"> <a href="editar_proyecto.php?accion=agregar"><strong>Agregar</strong></a></td>
        </td>
    </tr>

<?
//	if($opc == 'buscar')
//	{
?>
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
        <td valign="top" class="texto_suave" align="center" width=20%>
            Opciones
        </td>
	</tr>
<?

        $where2= '';

        if( $nombre != '' )
        {
            $nombre = strtr( $nombre, ' ', '%' );
            $where2 = "(titulo Like '%$nombre%')";
        }
        if( $descripcion != '' )
        {
            if( $nombre != '')
                $where2 .= " OR ";

            $descripcion = strtr( $descripcion, ' ', '%' );
            $where2 .= "(resumen Like '%$descripcion%')";
        }
        if( $fecha1 != '' and $fecha2 != '')
        {
            if( $nombre != '' or $descripcion != '')
                $where2 .= " OR ";
			$fech1 = Utiles::fecha2sql($fecha1);
			$fech2 = Utiles::fecha2sql($fecha2);
            $where2 .= "(fecha_creacion BETWEEN '$fech1 00:00:00' AND '$fech2 23:59:59')";
        }

        if( $where2 == '' )
            $where2 = '1';

		$where="0 ";
		$params_array['codigo_permiso'] = 'ADM';
        $p = $Sesion->usuario->permisos->Find('FindPermiso',$params_array); //tiene permiso de administrador
        if( $p->fields['permitido'] )
			$where.=" OR 1";

		$params_array['codigo_permiso'] = 'INV';
        $p = $Sesion->usuario->permisos->Find('FindPermiso',$params_array); //tiene permiso de Emprendedor
        if( $p->fields['permitido'] )
            $where.=" OR visible_inversionista=1";

		$params_array['codigo_permiso'] = 'EMP';
        $p = $Sesion->usuario->permisos->Find('FindPermiso',$params_array); //tiene permiso de Emprendedor
        if( $p->fields['permitido'] )
		{
			$rut=$Sesion->usuario->fields['rut'];	
            $where.=" OR id_proyecto in (SELECT id_proyecto FROM usuario_proyecto WHERE rut='$rut')";
		}
#echo "desde $desde--x_pag=$x_pag--where $where2";
    $query = "SELECT SQL_CALC_FOUND_ROWS proyecto.*,id_noticia_agrupador
                                FROM proyecto, proyecto_noticia_agrupador
                                WHERE proyecto_noticia_agrupador.id_proyecto = proyecto.id_proyecto
								AND ($where) AND ($where2)
                                ORDER BY $orden ASC
                                LIMIT $desde, $x_pag";

	
	$proyectos = new ListaProyectos ( $Sesion,'', $query); 
			
	echo Html::PrintListRows($Sesion, $proyectos, 'PrintRow');
	echo Html::PrintListPages($proyectos, $desde, $x_pag, 'PrintLinkPage');

?>
 </form>
<?

    	function PrintRow (& $fila)
   	 	{
		$fields=&$fila->fields;
		$id_proyecto=$fields['id_proyecto'];
		global $Sesion;
		$opciones="<a href='editar_proyecto.php?id_proyecto=".$fields['id_proyecto']."&accion=editar' ><img border=0 src='".Conf::ImgDir()."/iconos/16/editar_n.png' title='Editar'></a>&nbsp;<a href='ver_proyecto.php?id_proyecto=".$fields['id_proyecto']."' ><img border=0 src='".Conf::ImgDir()."/iconos/16/ver_16.gif' title='Ver'></a>&nbsp;<a href=ver_foro_proyecto.php?id_proyecto=".$fields['id_proyecto']."><img border=0 src='".Conf::ImgDir()."/foro_16.gif' alt='Ver Foro' title='Ver Foro' border=0></a>&nbsp;<a href=agregar_consultor.php?id_proyecto=".$fields['id_proyecto']."><img border=0 src='".Conf::ImgDir()."/usuarios2_16.gif' alt='Manejar Consultores' title='Manejar Consultores' border=0></a>&nbsp;<a href=noticias_proyecto.php?id_proyecto=".$fields['id_proyecto']."&id_noticia_agrupador=".$fields['id_noticia_agrupador']."><img border=0 src='".Conf::ImgDir()."/add_noticia16.gif' alt='Agregar noticia' title='Agregar Noticia' border=0></a>&nbsp;<a href='../../../app/modulos/noticia/listar_noticias_proyectos.php?id_proyecto=".$fields['id_proyecto']."'><img border=0 src='".Conf::ImgDir()."/noticia16.png' title='Ver Noticias'></a>";

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
//	}

?>

</table>
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
