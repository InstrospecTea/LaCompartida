<?
	require_once dirname(__FILE__).'/../../../conf.php';
	require_once Conf::ServerDir().'/fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/fw/classes/Utiles.php';
    require_once Conf::ServerDir().'/fw/classes/Empresa.php';
    require_once Conf::ServerDir().'/fw/modulos/proyecto/classes/Proyecto.php';
    require_once Conf::ServerDir().'/fw/modulos/foro/classes/Foro.php';
    require_once Conf::ServerDir().'/app/modulos/foro/classes/ArchivoForo.php';

	$sesion = new Sesion('');
	$pagina = new Pagina($sesion);
	$pagina->titulo = "Foros";
	$pagina->PrintHeaders();

	$tema_foro = new TemaForo($sesion);
	is_numeric($desde) or $desde="0";
	is_numeric($x_pag) or $x_pag="30";
	if(!$tema_foro->Load($id_foro_tema,$desde,$x_pag))
		$pagina->FatalError("Tema inválido");



//	PERMISOS PARA EL FORO

    $id_foro = $tema_foro->fields['id_foro'];

    $params_array['codigo_permiso'] = 'ADM';
    $p = $sesion->usuario->permisos->Find('FindPermiso',$params_array) or Utiles::FatalError("No se encontro el permiso ADM",__FILE__,__LINE__);
    if(!$p->fields['permitido']) // No es admin
	{
    	$params_array['codigo_permiso'] = 'CON';
	    $p = $sesion->usuario->permisos->Find('FindPermiso',$params_array) or Utiles::FatalError("No se encontro el permiso ADM",__FILE__,__LINE__);
	    if($p->fields['permitido']) // Es consultor
		{
			if($id_foro != '1') // no es el foro de los consultores
			{
				$pro = new Proyecto($sesion);
				if($pro->LoadByForo($id_foro))
				{
					$id_proyecto = $pro->fields['id_proyecto'];
				    Proyecto::PermisoVer($id_proyecto, $sesion) or $pagina->FatalError("No tiene permiso para ver temas de este grupo");
				}
				else // chequeamos la empresa
				{
                    $sesion->usuario->LoadEmpresa();
                    $id_empresa = $sesion->usuario->id_empresa;
	                $emp = new Empresa($sesion);
                    $emp->load($id_empresa);
					$id_foro_empresa = $emp->fields['id_foro'];
                    if($id_foro != $id_foro_empresa)
                         $pagina->FatalError("No tiene permiso para ver temas de esta empresa");
				}
			}
		}
		else // No es admin ni consultor
		{
            if($id_foro == '1') // es el foro de los consultores
            {
				$pagina->FatalError("No tiene permiso para ver temas del foro consultores");
			}
			else
			{
                $pro = new Proyecto($sesion);
                if($pro->LoadByForo($id_foro))
                {
                    $id_proyecto = $pro->fields['id_proyecto'];
                    Proyecto::PermisoVer($id_proyecto, $sesion) or $pagina->FatalError("No tiene permiso para ver temas de este grupo");
                }
				else // chequeamos la empresa
				{
			        $sesion->usuario->LoadEmpresa();
			        $id_empresa = $sesion->usuario->id_empresa;
					$emp = new Empresa($sesion);
			        $emp->load($id_empresa);
			        $id_foro_empresa = $emp->fields['id_foro'];
					if($id_foro != $id_foro_empresa)
						 $pagina->FatalError("No tiene permiso para ver temas de esta empresa");
				}
			}
		}
	}

// FIN PERMISOS FORO

	if($opc=="addMsg")
	{
		if($mensaje != "" )
		{
			$id_foro_mensaje = $tema_foro->AgregarMensaje($mensaje);
        if( $file['name'] != '' )
			{
				$archivo = new ArchivoForo($sesion,'','');
                $archivo->Edit('nombre',$_FILES['file']['name']);
				$archivo->Edit('tipo',$_FILES['file']['type']);
				$archivo->Edit('id_foro_mensaje', $id_foro_mensaje);
	            $archivo->GetDataFromFile($_FILES['file']['tmp_name']);
	            $archivo->tamano = $_FILES['file']['size'];
				$archivo->Write();
			}
		}
		else
			$pagina->AddError("Debe ingresar un mensaje");
	}

    $pagina->PrintTop();
?>
<table width="96%" align="left">
    <tr>
        <td width="20">&nbsp;</td>
        <td valign="top">
<table width="100%" align="left">
    <tr>
        <td valign="top" class="subtitulo" align="left" colspan="3">
              <img border=0 src="<?=Conf::ImgDir()?>/msg.gif"> Tema: <em><?=$tema_foro->fields['tema']?></em>
            <hr class="subtitulo"><br><br>
        </td>
    </tr>
    <tr>
        <td valign="top" align="right" colspan="3">
            <img border=0 src="<?=Conf::ImgDir()?>/agregar2.gif" alt="Nuevo Mensaje" border="0"> <a href="#Nuevo_Msg"><strong>nuevo mensaje</strong></a>
        </td>
    </tr>
  <tr>
    <td width="30%" class="texto_suave">&nbsp;Autor</td>
    <td width="70%" class="texto_suave">Mensaje</td>
  </tr>
        <td valign="top" align="center" colspan="2">
<?
	$tema_foro->Imprimir($desde,$x_pag);
?>
</td>
</tr>
<tr>
   <td valign="top" class="subtitulo" align="left" colspan="3">
<br>
<form method=post enctype="multipart/form-data">
<input type=hidden name=id_tema_foro value="<?= $proyecto->fields['id_tema_foro'] ?>">
<input type=hidden name="opc" value="addMsg">
<table width="100%" align="left" id="Nuevo_Msg">
    <tr>
        <td colspan=2 class="subtitulo">
             <img src="<?=Conf::ImgDir()?>/agregar2.gif"> Nuevo Mensaje
            <hr size=1 color=black>
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
      <td align=right>
            <strong>Adjuntar Archivo: </strong>
        </td>
		<td>
	            <input type="file" name="file" size="20">
		</td>

    <tr>
        <td></td>
        <td align=left>
            <input type=submit value="Agregar Mensaje">
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


