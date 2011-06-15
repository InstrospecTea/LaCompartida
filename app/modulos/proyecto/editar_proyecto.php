<?
	require_once dirname(__FILE__).'/../../../conf.php';

	require_once Conf::ServerDir().'/fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/fw/classes/Usuario.php';
	require_once Conf::ServerDir().'/fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/fw/classes/Html.php';
    require_once Conf::ServerDir().'/fw/modulos/proyecto/classes/Proyecto.php';
    require_once Conf::ServerDir().'/fw/modulos/proyecto/classes/ListaProyecto.php';
    require_once Conf::ServerDir().'/fw/modulos/proyecto/classes/ArchivoProyecto.php';



	$Sesion = new Sesion( array('ADM','CON') );
	$pagina = new Pagina($Sesion);
    $proyecto = new Proyecto($Sesion);

    Proyecto::PermisoEditar($id_proyecto,$Sesion) or $pagina->FatalError("Usted no tiene permiso para editar este proyecto",__FILE__,__LINE__);

	if($msg == "ok")
		$pagina->AddInfo( 'Consultores asignados con éxito.');

    if($accion == 'editar')
    {
        is_numeric($id_proyecto) or Utiles::errorFatal("id_proyecto incorrecto",__FILE__,__LINE__);

        if(!$proyecto->Load($id_proyecto))
 	       $pagina->FatalError("Grupo Inválido");


        $proyecto->LoadFiles();
		$proyecto->LoadEmpresa();
    }

    if($opc == 'addPro')
    {
        if($titulo == '')
            $pagina->AddError("Debe ingresar el titulo");
		else if($empresa == 0)
			$pagina->AddError("Debe seleccionar una empresa");
        else
        {
	    	$proyecto->Edit('titulo', $titulo);
    		$proyecto->Edit('resumen', $resumen);
        	if( $proyecto->Write() )
           	{
				$proyecto->GuardarEmpresa($empresa);
	            $pagina->AddInfo( 'Grupo agregado con éxito.');
				$pagina->Redirect("editar_proyecto.php?id_proyecto=".$proyecto->fields['id_proyecto']."&accion=editar");
	            $id_proyecto=$proyecto->fields['id_proyecto'];
	        }
        }
    }

    if($opc == 'editPro')
    {
        if($titulo == '')
            $pagina->AddError("Debe ingresar el titulo");
        else if($empresa == '0')
            $pagina->AddError("Debe seleccionar una empresa");
        else
        {
	       $proyecto->Edit('titulo', $titulo);
	       $proyecto->Edit('resumen', $resumen);
	       if( $proyecto->Write() )
	       {
				$proyecto->GuardarEmpresa($empresa);
	            $pagina->AddInfo( 'Grupo editado con éxito.' );
	            $id_proyecto=$proyecto->fields['id_proyecto'];
	        }
		}
	    $proyecto->LoadEmpresa();
    }

    if($opc =="delFile" and $id_archivo != '')
    {
    	$params['id_archivo'] = $id_archivo;
        $archivo = new ArchivoProyecto($Sesion,'',$params);
        if($archivo->Load())
        {
        	if($archivo->DbRemove())
            {
            	$pagina->AddInfo("Archivo Eliminado");
            }   
            else
            	$pagina->AddError($archivo->error);
	     }
     }

	if($opc == "uploadFile")
	{
        if ($_FILES['file']['tmp_name'] ==  '')
           $pagina->AddError("Debe especificar un archivo");
		else
		{
			$archivo = new ArchivoProyecto($Sesion,'','');
			$archivo->Edit('nombre',$_FILES['file']['name']);
			$archivo->Edit('tipo',$_FILES['file']['type']);
			$archivo->Edit('descripcion',$file_desc);
			$archivo->Edit('id_proyecto', $id_proyecto);
			$archivo->GetDataFromFile($_FILES['file']['tmp_name']);
			$archivo->tamano = $_FILES['file']['size'];
			$archivo->Write();
		}
	}

	$pagina->titulo = "Editar Grupo";

	$pagina->PrintHeaders();

	$pagina->PrintTop();

?>
<script>
function Confirmar()
{
	if(confirm("¿Esta seguro que desea eliminar este archivo? "))
		return true;
	return false;
}
</script>
<table width="96%" align="left">
	<tr>
		<td width="20">&nbsp;</td>
		<td valign="top">

<table width="100%" align="left">
 <form id="form" name="editProyect" method="post">
 <input type="hidden" name="opc" value="">
 <input type="hidden" name="accion" value="<?=$accion?>">
 <input type="hidden" name="x_pag" value="<?=$x_pag?>">
 <input type="hidden" name="desde" value="">
	<tr>
		<td valign="top" class="subtitulo" align="left" colspan="4">
		     <img border=0 src="<?=Conf::ImgDir()?>/proyectos_16.gif"> <?=$accion == 'agregar'? 'Agregar Grupo':'Editar Grupo'?>	
			<hr class="subtitulo"/>
		</td>
	</tr>
	<tr>
		<td valign="top" class="texto" align="left">
			<strong>Nombre</strong>
		</td>
		<td valign="top" class="texto" align="left">
			<input type=text name='titulo' value="<?=$proyecto->fields['titulo']?>">
		</td>
	</tr>
	<tr>
		<td valign="top" class="texto" align="left">
			<strong>Descripción</strong>
		</td>
        <td valign="top" class="texto" align="left">
            <textarea  name='resumen' rows=5><?=$proyecto->fields['resumen']?></textarea>
        </td>
	</tr>
    <tr>
        <td valign="top" class="texto" align="right">
            Empresa
        </td>
        <td valign="top" class="texto" align="left">
      <?=Html::SelectQuery($Sesion, 'SELECT id_empresa, glosa_empresa FROM empresa', 'empresa', $proyecto->id_empresa ,'','Seleccione')?>

        </td>
    </tr>

<?
	if($accion == 'agregar')
	{
?>

  <tr>
        <td valign="top" class="texto" align="right" colspan=2>
            <input type="submit" value="Grabar" onclick="this.form.opc.value='addPro';">
        </td>
  </tr>
  <tr>
        <td valign="top" class="texto" align="left" colspan=3>
            <hr size=1>
        </td>
  </tr>
</form>
<?
	}
	else
	{
?>
  <tr>
        <td valign="top" class="texto" align="right" colspan=2>
            <input type="submit" value="Grabar" onclick="this.form.opc.value='editPro';">
        </td>
  </tr>
<?
        $params_array['codigo_permiso'] = 'ADM';
        $p = $Sesion->usuario->permisos->Find('FindPermiso',$params_array); //tiene permiso de administrador
        if( $p->fields['permitido'] )
        {
?>
<tr>
		<td colspan=3 align="right"><br>
			<img border=0 src="<?=Conf::ImgDir()?>/usuarios2_16.gif"> <a href="agregar_consultor.php?id_proyecto=<?=$id_proyecto?>"><strong>Manejar Consultores</strong></a>
		<td>
</tr>
<?
		}
?>
  <tr>
        <td valign="top" class="texto" align="left" colspan=3>
            <hr size=1>
        </td>
  </tr>
</form>
  <tr>
        <td valign="top" class="texto" align="center" colspan=3>
            <strong>Lista de archivos de este proyecto</strong>
        </td>
  </tr>
  <tr>
       <td valign="top" class="texto_suave" align="center" width="20%" >
            <strong>Nombre</strong>
        </td>
       <td valign="top" class="texto_suave" align="left" >
            <strong>Descripción</strong>
        </td>
       <td valign="top" class="texto_suave" align="center" >
            <strong>Opciones</strong>
        </td>
  </tr>

<?
	}
		if(!is_numeric($desde))
			$desde=0;
	    if(!is_numeric($x_pag))
	        $x_pag=30;

		$proyecto->archivos = new ListaArchivosProyectos ( $Sesion,'', "SELECT SQL_CALC_FOUND_ROWS * FROM archivos_proyectos WHERE id_proyecto='$id_proyecto' LIMIT $desde, $x_pag");
		echo Html::PrintListRows($Sesion, $proyecto->archivos, 'PrintRow');
		echo Html::PrintListPages($proyecto->archivos, $desde, $x_pag, 'PrintLinkPage');
	
		if($id_proyecto && $accion=='editar')
		{
?>
<tr>
<td colspan=3>
<br>&nbsp;
<table style="border: 1px solid #000000;" width="100%">
 <form id="addFile" name="addFile" enctype="multipart/form-data" method="post">
 <input type="hidden" name="accion" value="<?=$accion?>">
 <input type="hidden" name="opc" value="">
  <tr>
		<td colspan=2 align=center><b>Subir Archivo</b></td>
  </tr>
  <tr>
		<td>Archivo a subir:</td><td><input type="file" name="file"></td>
  </tr>
  <tr>
		<td>Descripción:</td><td><input type="text" name="file_desc" size=50></td>
  </tr>
  <tr>
        <td><input type=submit value="Subir Archivo" onclick="this.form.opc.value='uploadFile'"></td><td></td>
  </tr>
</form>
</table>
<?
		}
    	function PrintRow (& $archivo)
	    {
			$fields=&$archivo->fields;
			$img_dir=Conf::ImgDir();
			global $id_proyecto;
			$file_name=__FILE__;
	        $html.=<<<HTML
  <tr>
        <td valign="top" align="left">
            <a href="ver_archivo_proyecto.php?id_proyecto=$id_proyecto&id_archivo=${fields['id_archivo']}">${fields['nombre']}</a>
        </td>
        <td valign="top" align="left">
            ${fields['descripcion']}
        </td>
        <td valign="top" align="center">
			<a href="ver_archivo_proyecto.php?id_proyecto=$id_proyecto&id_archivo=${fields['id_archivo']}" ><img border=0 src="$img_dir/iconos/16/ver_16.gif" title='Ver'></a>            
            <a href="?opc=delFile&id_proyecto=$id_proyecto&id_archivo=${fields['id_archivo']}&accion=editar" onclick="return Confirmar();"><img border=0 src="$img_dir/iconos/16/eliminar.gif" title='Eliminar'></a>
        </td>
  </tr>
  <tr>
        <td valign="top" class="texto" align="left" colspan=3>
            <hr size=1>
        </td>
  </tr>

HTML;
		return $html;
    	}
?>
</table>
</tr>
</td>
</table>
<script language="javascript">	
<!-- //

function PrintLinkPage( page )
{
	document.formArchivos.desde.value= (page-1)*document.formArchivos.x_pag.value;
	document.formArchivos.submit();
}

// ->
</script>

<?
	$pagina->PrintBottom();
?>
