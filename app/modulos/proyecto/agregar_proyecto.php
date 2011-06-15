<?
	require_once dirname(__FILE__).'/../../conf.php';
	require_once dirname(__FILE__).'/classes/lista.php';

	require_once Conf::ServerDir().'/fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/fw/classes/usuario.php';
	require_once Conf::ServerDir().'/fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/fw/classes/html.php';
	require_once dirname(__FILE__).'/classes/archivo_proyecto.php';

	$Sesion = new Sesion( array('ADM') );
	$pagina = new Pagina($Sesion);
    $proyecto = new Proyecto($Sesion);

	Proyecto::PermisoEditar($id_proyecto,$Sesion) or Utiles::errorFatal("Usted no tiene permiso para este archivo",__FILE__,__LINE__);


   if($desde=="")
        $desde=0;
    if($x_pag=="")
        $x_pag=30;
    if($orden == '')
        $orden= 'titulo';


    if($opc == 'agregar')
    {
		if($titulo == '')
			$pagina->AddError("Debe ingresar el titulo");
		else
		{
			
	        $proyecto->Edit('titulo', $titulo);
	        $proyecto->Edit('resumen', $resumen);
			$proyecto->Edit('visible_inversionista', $inv);
			if( $proyecto->Write() )
	        {
	            $pagina->AddInfo( 'Proyecto agregado con éxito.' );
				$id_proyecto=$proyecto->fields['id_proyecto'];
    	    }
		}
	}      

	$pagina->titulo = "Editar Proyecto";

	$pagina->PrintHeaders();

	$pagina->PrintTop();

?>
<script>
function Set(form)
{
	if(form.inv.checked==true)
		form.inv.value = '1';
	else
		form.inv.value = '2';
	return true;

}
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
 <input type="hidden" name="opc" value="agregar">
 <input type="hidden" name="x_pag" value="<?=$x_pag?>">
 <input type="hidden" name="desde" value="">
	<tr>
		<td valign="top" class="subtitulo" align="left" colspan="4">
		    Proyecto	
			<hr class="subtitulo"/>
		</td>
	</tr>
	<tr>
		<td valign="top" class="texto" align="rigth">
			<strong>Nombre</strong>
		</td>
		<td valign="top" class="texto" align="left">
			<input type=text name='titulo' value="<?=$titulo?>">
		</td>
	</tr>
	<tr>
		<td valign="top" class="texto" align="rigth">
			<strong>Descripción</strong>
		</td>
        <td valign="top" class="texto" align="left">
            <textarea  name='resumen' rows=5><?=$resumen?></textarea>
        </td>
	</tr>
  <tr>

    <tr>
        <td valign="top" class="texto" align="rigth">
            <strong>Inversionista</strong>
        </td>
        <td valign="top" class="texto" align="left">
            <input type=checkbox name=inv id=inv value="" onclick="return Set(this.form)">
        </td>
    </tr>

        <td valign="top" class="texto" align="right" colspan=2>
            <input type="submit" value="Agregar">
        </td>
  </tr>
  <tr>
        <td valign="top" class="texto" align="left" colspan=4>
            <hr size=1>
        </td>
  </tr>
</form>
<form id="formArchivos" name="formArchivos" method="post">
 <input type="hidden" name="opc" value="edit">
 <input type="hidden" name="x_pag" value="<?=$x_pag?>">
 <input type="hidden" name="desde" value="">
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
            <strong>Creación</strong>
        </td>

       <td valign="top" class="texto_suave" align="center" >
            <strong>Opciones</strong>
        </td>

  </tr>
<?
        $where="0 ";
        $params_array['codigo_permiso'] = 'ADM';
        $p = $Sesion->usuario->permisos->Find('FindPermiso',$params_array); //tiene permiso de administrador
        if( $p->fields['permitido'] )
            $where.=" OR 1";

    $proyectos = new ListaProyectos ( $Sesion,'', "SELECT SQL_CALC_FOUND_ROWS proyecto . * FROM proyecto WHERE ($where)
                                                                ORDER BY titulo ASC
                                                                LIMIT $desde, $x_pag");

    echo Html::PrintListRows($Sesion, $proyectos, 'PrintRow');
    echo Html::PrintListPages($proyectos, $desde, $x_pag, 'PrintLinkPage');

?>
 </form>
</table>
<?

        function PrintRow (& $fila)
        {
        $fields=&$fila->fields;
        global $Sesion;

        if( Proyecto::PermisoEditar($id_proyecto,$Sesion))
            $opciones="<a href='editar_proyecto.php?id_proyecto=".$fields['id_proyecto']."' ><img border=0 src='".Conf::ImgDir()."/iconos/16/editar_n.png' title='Editar'></a>&nbsp;"
                        ."<a href='ver_proyecto.php?id_proyecto=".$fields['id_proyecto']."' ><img border=0 src='".Conf::ImgDir()."/iconos/16/ver_16.gif' title='Ver'></a>";
        else
            $opciones="<a href='ver_proyecto.php?id_proyecto=".$fields['id_proyecto']."' ><img border=0 src='".Conf::ImgDir()."/iconos/16/ver_16.gif' title='Ver'></a>&nbsp;";
        $fecha = Utiles::sql2date($fields['fecha_creacion'],'%d/%m/%y');
        $titulo = substr($fields['titulo'],0,10);
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
//  }

?>


 </form>

<script language="javascript">	
<!-- //

function PrintLinkPage( page )
{
	document.formArchivos.desde.value= (page-1)*document.formArchivos.x_pag.value;
	document.formArchivos.submit();
}

// ->
</script>

