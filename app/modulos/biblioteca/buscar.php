<?
	require_once dirname(__FILE__).'/../../conf.php';
	require_once dirname(__FILE__).'/classes/archivo_biblioteca.php';
	require_once dirname(__FILE__).'/classes/categoria_biblioteca.php';
    require_once dirname(__FILE__).'/classes/lista.php';

	require_once Conf::ServerDir().'/fw/classes/sesion.php';
	require_once Conf::ServerDir().'/fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/fw/classes/usuario.php';
	require_once Conf::ServerDir().'/fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/fw/classes/html.php';


#	$sesion = new Sesion( array('ADM','EMP','INV') ); Como todos los perfiles pueden mejor pregunto por perfil vacío ya que es más barato
	$sesion = new Sesion('');
	
	$pagina = new Pagina($sesion);
	
	$pagina->titulo = "Biblioteca de Documentos";

	$pagina->PrintHeaders();

	if($opc == 'addCat')
	{
		if($cat_name != '')
		{
    		$new_cat = new CategoriaBiblio($sesion,'',$params);
		    $new_cat->Edit('glosa_categoria',$cat_name);

			if($new_cat->Write())
				$pagina->AddInfo("Categoría ingresada");
			else
				$pagina->AddError($new_cat->error);
		}	
		else
			$pagina->AddError("Debe ingresar un nombre para la categoria");
	}

    if($opc == "uploadFile")
    {
        if ($_FILES['file']['tmp_name'] ==  '')
            $pagina->AddError("Debe especificar un archivo");
		else if($categoria == 0)
			$pagina->AddError("Debe seleccionar una categoria");	
		else
		{
	        $archivo = new ArchivoBiblio($sesion,'','');
	        $archivo->Edit('nombre',$_FILES['file']['name']);
	        $archivo->Edit('tipo',$_FILES['file']['type']);
	        $archivo->Edit('descripcion',$file_desc);
	        $archivo->Edit('id_categoria',$categoria);
    	    $archivo->Edit('visible_inversionista',$inv);
	        $archivo->Edit('visible_emprendedor',$emp);
	        $archivo->GetDataFromFile($_FILES['file']['tmp_name']);
	        $archivo->tamano = $_FILES['file']['size'];
	        $archivo->Write();
		}

    }
    if($opc =="delFile" and $id_archivo != '')
    {
		$params['id_archivo'] = $id_archivo;
        $archivo = new ArchivoBiblio($sesion,'',$params);
		if($archivo->Load())
		{
			if($archivo->DbRemove())
				$pagina->AddInfo("Archivo Eliminado");
		 	else
				$pagina->AddError($archivo->error);
		}
    }

	if($desde=="")
		$desde=0;
	if($x_pag=="")
		$x_pag=30;
	if($orden == '')
		$orden= 'nombre';

    $pagina->PrintTop();

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
function Confirmar()
{
    if(confirm("¿Esta seguro que desea eliminar este archivo? "))
        return true;
    return false;
}
function Check( form )
{
	if(form.emp.checked==true)
		form.emp.value = '1'
	else
		form.emp.value = '0';

    if(form.inv.checked==true)
        form.inv.value = '1'
    else
        form.inv.value = '0';

	if(form.categoria.value == '0')
	{
		alert("Debe seleccionar una categoria");
		return false;
	}
	form.opc.value = 'uploadFile';

    return true;
}
function MostrarNewCat()
{
    var form = document.getElementById( 'new_categoria' );
    form.style['display'] = 'inline';
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
 <input type="hidden" name="orden" value="">
	<tr>
		<td valign="top" class="subtitulo" align="left" colspan="5">
		     <img border=0 src="<?=Conf::ImgDir()?>/iconos/16/ver_16.gif"> Ingrese los filtros para la búsqueda de documentos	
			<hr class="subtitulo">
		</td>
	</tr>
	<tr>
		<td colspan=5>
		<table border="0" cellspacing="0" cellpadding="3" width="90%">
            Ingresa el Nombre, o si no lo sabes ingresa su descripción  o parte de ella.
        </td>
    </tr>
    <tr>
        <td valign="top" align="left" colspan="5"><img src="<?=Conf::ImgDir()?>/pix.gif" border="0" width="1" height="10"></td>
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
            <strong>Categoría</strong>
        </td>

        <td valign="top" align="left">
		  <?=Html::SelectQuery($sesion, 'SELECT id_categoria, glosa_categoria FROM categoria', 'categoria2', $categoria2,'','Todas')?>
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
        <td valign="top" class="subtitulo" align="left" colspan="5">
             <br><img border=0 src="<?=Conf::ImgDir()?>/archivo_16.gif"> Lista de Documentos
            <hr class="subtitulo">
        </td>
    </tr>
	<tr>
		<td valign="top" class="texto_suave" align="center" width="15%">
			<a href="#" class="texto_suave" onclick="OrdenarLista('nombre');">Nombre</a>
		</td>
		<td valign="top" class="texto_suave" align="left" width="35%">
			Descripción
		</td>
        <td valign="top" class="texto_suave" align="center" width="20%">
            <a class="texto_suave" href="#" onclick="OrdenarLista('glosa_categoria');">Categoría</a>
        </td>
		<td valign="top" class="texto_suave" align="center" width="15%">
			<a class="texto_suave" href="#" onclick="OrdenarLista('fecha_mod');">Creación</a>
		</td>
        <td valign="top" class="texto_suave" align="center" width="15%">
            Opciones
        </td>
	</tr>
<?

        $where2= '';

        if( $nombre != '' )
        {
            $nombre = strtr( $nombre, ' ', '%' );
            $where2 = "(nombre Like '%$nombre%')";
        }
        if( $descripcion != '' )
        {
            if( $nombre != '')
                $where2 .= " OR ";

            $descripcion = strtr( $descripcion, ' ', '%' );
            $where2 .= "(descripcion Like '%$descripcion%')";
        }
        if( $categoria2 != '0' and $categoria2 !='')
        {
            if( $nombre != '' or $descripcion != '')
                $where2 .= " OR ";

            $where2 .= "(archivos_biblioteca.id_categoria = '$categoria2')";
        }

        if( $fecha1 != '' and $fecha2 != '')
        {
            if( $nombre != '' or $descripcion != '' or $categoria2 !='')
                $where2 .= " OR ";
			$fech1 = Utiles::fecha2sql($fecha1);
			$fech2 = Utiles::fecha2sql($fecha2);
            $where2 .= "(fecha_mod BETWEEN '$fech1 00:00:00' AND '$fech2 23:59:59')";
        }

        if( $where2 == '' )
            $where2 = '1';

		$where="0 ";
		$params_array['codigo_permiso'] = 'ADM';
        $p = $sesion->usuario->permisos->Find('FindPermiso',$params_array); //tiene permiso de administrador
        if( $p->fields['permitido'] )
			$where.=" OR 1";

		$params_array['codigo_permiso'] = 'INV';
        $p = $sesion->usuario->permisos->Find('FindPermiso',$params_array); //tiene permiso de Emprendedor
        if( $p->fields['permitido'] )
            $where.=" OR visible_inversionista=1";

		$params_array['codigo_permiso'] = 'EMP';
        $p = $sesion->usuario->permisos->Find('FindPermiso',$params_array); //tiene permiso de Emprendedor
        if( $p->fields['permitido'] )
            $where.=" OR visible_emprendedor=1";
	
	  $archivos = new ListaArchivosBiblio ( $sesion,'', "SELECT id_archivo, nombre, descripcion, archivos_biblioteca.fecha_mod, glosa_categoria FROM archivos_biblioteca 
																LEFT JOIN categoria ON categoria.id_categoria = archivos_biblioteca.id_categoria 
																WHERE ($where) AND ($where2) 
																ORDER BY $orden ASC 
																LIMIT $desde, $x_pag");

	echo Html::PrintListRows($sesion, $archivos, 'PrintRow');
	echo Html::PrintListPages($archivos, $desde, $x_pag, 'PrintLinkPage');

?>
 </form>
<?
        $params_array['codigo_permiso'] = 'ADM';
        $p = $sesion->usuario->permisos->Find('FindPermiso',$params_array); //tiene permiso de administrador
        if( $p->fields['permitido'] )
        {
?>

    <tr>
        <td valign="top" class="subtitulo" align="left" colspan="5">
<br>
<br>
<img src="<?=Conf::ImgDir()?>/agregar2.gif"> Agregar Documento
            <hr class="subtitulo">
        </td>
    </tr>
<tr>
<td colspan=4 align="center">
<table width="100%">
 <form id="addFile" name="addFile" enctype="multipart/form-data" method="post">
 <input type="hidden" name="opc" value="">
  <tr>
        <td width="30%" align=right ><strong>Archivo:</strong></td><td><input type="file" name="file"></td>
  </tr>
  <tr>
        <td align=right><strong>Descripción:</strong></td><td><textarea name="file_desc" size=50></textarea></td>
  </tr>
  <tr>
		<td align=right><strong>Categoría:</strong></td><td> <?=Html::SelectQuery($sesion, 'SELECT id_categoria, glosa_categoria FROM categoria', 'categoria', $categoria2,'','Seleccione')?> <img src="<?=Conf::ImgDir()?>/agregar.gif" alt="Nueva Categoria"><a href="#" onclick="MostrarNewCat();"><span style="font-size: 10px">Nueva categoría</span></a></td>
  </tr>
	<tr>
		<td></td><td>
			<table id="new_categoria" style="display : none" width="100%">
			<tr>	
				<td bgcolor="#f0f0f0" style="border: 1px dashed #bbbbbb;">
				<br>
				<strong>&nbsp;Nombre:&nbsp;</strong><input type="cat_name" name="cat_name">&nbsp;<input type=submit value="Agregar" onclick="this.form.opc.value = 'addCat';">
				<br>&nbsp;
				</td>
			</tr>
			</table>
		</td>
	</tr>
  <tr>
		<td align=right><input type="checkbox" id="emp" name="emp" value=""></td>
		<td>Emprendedor</td>
  </tr>
  <tr>
        <td align=right><input type="checkbox" id="inv" name="inv" value=""></td>
        <td>Inversionista</td>
  </tr>
  <tr>	<td></td>
        <td align=left><input type=submit value="Subir Archivo" onclick="return Check(this.form);"></td><td></td>
  </tr>
</form>
</table>
</td></tr>
<?
    }
?>

</table>

<?
    	function PrintRow (& $fila)
   	 	{
		$fields=&$fila->fields;
		global $sesion;
        $params_array['codigo_permiso'] = 'ADM';
        $p = $sesion->usuario->permisos->Find('FindPermiso',$params_array); //tiene permiso de administrador
        if( $p->fields['permitido'] )
		{

			$opciones="<a href='?opc=delFile&id_archivo=${fields['id_archivo']}' onclick='return Confirmar();'><img border=0 src='".Conf::ImgDir()."/iconos/16/eliminar.gif' title='Eliminar'></a> &nbsp;"."<a href='editar_archivo.php?id_archivo=".$fields['id_archivo']."' ><img border=0 src='".Conf::ImgDir()."/iconos/16/editar_n.png' title='Editar'></a>&nbsp;"
						."<a href='ver_archivo_biblioteca.php?id_archivo=${fields['id_archivo']}'><img border=0 src='".Conf::ImgDir()."/iconos/16/ver_16.gif' title='Ver'></a>";
		}
		else
		{
			            $opciones="<a href='ver_archivo_biblioteca.php?id_archivo=${fields['id_archivo']}'><img border=0 src='".Conf::ImgDir()."/iconos/16/ver_16.gif' title='Ver'></a>";
		}


        $fecha = Utiles::sql2date($fields['fecha_mod'],'%d/%m/%y');
		$titulo = substr($fields['nombre'],0,14);
        $html.=<<<HTML
  <tr>
        <td valign="top" align="left">
            <a href='ver_archivo_biblioteca.php?id_archivo=${fields['id_archivo']}'>$titulo</a>
        </td>
        <td valign="top" align="left">
            ${fields['descripcion']}
        </td>
        <td valign="top" align="center">
            ${fields['glosa_categoria']}
        </td>
        <td valign="top" align="center">
            $fecha
        </td>
        <td valign="top" align="center">
            $opciones
            
        </td>
  </tr>
  <tr>
	<td  colspan="5">
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
	document.formProyectos.desde.value= (page-1)*document.formProyectos.x_pag.value;
	document.formProyectos.submit();
}

// ->
</script>

