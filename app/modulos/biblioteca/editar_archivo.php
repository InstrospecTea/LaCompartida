<?
	require_once dirname(__FILE__).'/../../conf.php';
	require_once dirname(__FILE__).'/classes/archivo_biblioteca.php';
	require_once dirname(__FILE__).'/classes/categoria_biblioteca.php';

	require_once Conf::ServerDir().'/fw/classes/sesion.php';
	require_once Conf::ServerDir().'/fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/fw/classes/usuario.php';
	require_once Conf::ServerDir().'/fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/fw/classes/html.php';

#	$sesion = new Sesion( array('ADM','EMP','INV') ); Como todos los perfiles pueden mejor pregunto por perfil vacío ya que es más barato
	$sesion = new Sesion('');
	
	$pagina = new Pagina($sesion);
	
	$pagina->titulo = "Editar Documento";

	$pagina->PrintHeaders();
	
	$id['id_archivo'] = $id_archivo;
    $arch = new ArchivoBiblio($sesion,'',$id);


    if($opc == "upload")
    {
		 if($categoria == 0)
			$pagina->AddError("Debe seleccionar una categoria");	
		else
		{
	        $arch->Edit('descripcion',$file_desc);
	        $arch->Edit('id_categoria',$categoria);
    	    $arch->Edit('visible_inversionista',$inv);
	        $arch->Edit('visible_emprendedor',$emp);
	        if( $arch->Write() )
    	    {
        	    $pagina->AddInfo( 'Documento Editado.' );
            	$id_archivo=$arch->fields['id_archivo'];
	        }	
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
	form.opc.value = 'upload';

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
        <td valign="top" class="subtitulo" align="left" colspan="5">
<img src="<?=Conf::ImgDir()?>/iconos/16/editar_n.png"> Editar Documento
            <hr class="subtitulo">
        </td>
    </tr>
<tr>
<td colspan=4 align="center">
<table width="100%">
 <form id="addFile" name="addFile" method="post">
 <input type="hidden" name="opc" value="">
  <tr>
        <td width="30%" align=right ><strong>Archivo:</strong></td><td><?=$arch->fields['nombre']?></td>
  </tr>
  <tr>
        <td align=right><strong>Descripción:</strong></td><td><textarea name="file_desc" size=50><?=$arch->fields['descripcion']?></textarea></td>
  </tr>
  <tr>
		<td align=right><strong>Categoría:</strong></td><td><?=Html::SelectQuery($sesion, 'SELECT id_categoria, glosa_categoria FROM categoria', 'categoria', $categoria2,'','Seleccione')?> <img src="<?=Conf::ImgDir()?>/agregar.gif" alt="Nueva Categoria"><a href="#" onclick="MostrarNewCat();"><span style="font-size: 10px">Nueva categoría</span></a></td>
  </tr>
	<tr>
		<td></td><td>
			<table id="new_categoria" width="100%" style="display:none;">
			<tr>	
				<td bgcolor="#f0f0f0" style="border: 1px dashed #bbbbbb;">
				<br>
				<strong>&nbsp;Nombre:&nbsp;</strong><input type="cat_name" name="cat_name">&nbsp;<input type=submit value="Agregar" onclick="this.form.opc.value = 'AddCat';">
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
        <td align=left><input type=submit value="Guardar" onclick="return Check(this.form);"></td><td></td>
  </tr>
</form>
</table>
</td></tr>
<tr>
<td align="right">
<a href="#" onclick="irIntranet('/app/biblioteca/buscar.php')">Volver</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
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

    var form = document.getElementById( 'addFile' );

    for( var i=0; i<form.categoria.options.length; i++)
    {
        if( form.categoria.options[i].value == '<?=$arch->fields['id_categoria']?>' )
            form.categoria.options[i].selected = true;
    }
<?
	if($arch->fields['visible_emprendedor']==1)
	{
?>	
		form.emp.checked=true;
<?
	}
?>
<?
    if($arch->fields['visible_inversionista']==1)
    {
?>
        form.inv.checked=true;
<?
    }
?>
// ->
</script>
