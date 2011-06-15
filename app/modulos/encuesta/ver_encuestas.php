<?
	require_once dirname(__FILE__).'/../../../conf.php';
    require_once Conf::ServerDir().'/app/modulos/encuesta/classes/Encuesta.php';

    require_once Conf::ServerDir().'/app/modulos/encuesta/classes/Lista.php';
	require_once Conf::ServerDir().'/fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/fw/classes/Usuario.php';
	require_once Conf::ServerDir().'/fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/fw/classes/Html.php';
    require_once Conf::ServerDir().'/fw/classes/Empresa.php';


	$sesion = new Sesion( '' );
	$pagina = new Pagina($sesion);
    	    
	$pagina->titulo = "Ver Encuestas";

	$pagina->PrintHeaders();

	if ($id_empresa == '')
	{
		$sesion->usuario->LoadEmpresa();
		$id_empresa = $sesion->usuario->id_empresa;
		$from = 'USER';
	}
	else
		$from = 'ADMIN';

	if($opc=='borrar')
	{
        $params_array['codigo_permiso'] = 'ADM';
        $p = $sesion->usuario->permisos->Find('FindPermiso',$params_array) or $pagina->FatalError("No se encontro el permiso ADM",__FILE__,__LINE__);

        if( $p->fields['permitido'] )
		{
			$enc = new Encuesta($sesion);
			if($enc->Load($id_encuesta))
				if($enc->Delete())
					$pagina->AddInfo("Encuesta eliminada");
				else
					$pagina->AddError($enc->error);
		
		}
		else
			$pagina->FatalError(" No tiene permisos de Administrador");
	}

	if($from == 'USER')
	{
		$lista_encuestas = new ListaEncuestas($sesion,'',"SELECT * FROM encuesta WHERE id_empresa = $id_empresa AND estado = 'EMITIDA' 
																			 ORDER BY fecha_creacion DESC");
	}
	else
	{
        $lista_encuestas = new ListaEncuestas($sesion,'',"SELECT * FROM encuesta WHERE id_empresa = $id_empresa 
                                                                             ORDER BY fecha_creacion DESC");
	}
	if($id_empresa == '')
	{
		$pagina->FatalError("Empresa inválida");
	}
    if($guardada == 'yes')
    {
	    $pagina->AddInfo("Encuesta guardada con éxito");
    }

		$empresa = new Empresa($sesion);
		if(!$empresa->Load($id_empresa))
			 $pagina->FatalError("Empresa inválida");

    $pagina->PrintTop();

?>
<table width="96%" align="left">
    <tr>
        <td width="20">&nbsp;</td>
        <td valign="top">
<table width="100%" align="left">
    <tr>
        <td valign="top" class="subtitulo" align="left" colspan="3">
              <img border=0 src="<?=Conf::ImgDir()?>/ver_encuesta16.gif"> Lista de encuestas empresa: <?=$empresa->fields['glosa_empresa']?> 
   </td>
	<td width=10% align=center>
<?
       $params_array['codigo_permiso'] = 'ADM';
        $p = $sesion->usuario->permisos->Find('FindPermiso',$params_array) or $pagina->FatalError("No se encontro el permiso ADM",__FILE__,__LINE__);

        if( $p->fields['permitido'] )
        {
?>
        <a href="encuesta_paso1.php?id_empresa=<?=$id_empresa?>"><img src=<?=Conf::ImgDir()?>/agregar_encuesta16.gif title="Agregar Encuesta" border=0></a>
<?
		}
?>
	</td>
    </tr>
	<tr>
		<td colspan=4>
	<table width="100%" align="left">
	<tr>
		<td class="texto_suave">Nombre</td>
		<td class="texto_suave" align ="center" width=20%><?=$from == 'USER' ? 'Estado':'Opciones'?></td>
	</tr>
<?
	    $rut = $sesion->usuario->fields['rut'];

		for($x=0; $x<$lista_encuestas->num; $x++)
		{
			$encuesta = $lista_encuestas->Get($x);
?>
	<tr>
		<td>
			<?=$encuesta->fields['titulo']?><br>
		</td>
		<td align="center">
<?
        if($from == 'ADMIN')
		{
            echo "<a href='desplegar_encuesta.php?id_encuesta=".$encuesta->fields['id_encuesta']."' ><img border=0 src='".Conf::ImgDir()."/ver_16.gif' title='Ver Encuesta'></a>&nbsp;<a href=ver_encuestas.php?id_empresa=".$id_empresa."&id_encuesta=".$encuesta->fields['id_encuesta']."&opc=borrar><img border=0 src='".Conf::ImgDir()."/encuesta_borrar.gif' title='Borrar Encuesta'></a>";


			if($encuesta->fields['estado'] == 'CREADA')
				echo "&nbsp;<img border=0 src='".Conf::ImgDir()."/graficos16_off.gif' title='Encuesta no Emitida'>&nbsp;<a href='encuesta_paso2.php?id_encuesta=".$encuesta->fields['id_encuesta']."'><img border=0 src='".Conf::ImgDir()."/editar_on.gif' title='Editar Encuesta'></a>";
			else
               echo "&nbsp<a href='ver_reporte.php?id_encuesta=".$encuesta->fields['id_encuesta']."'><img border=0 src='".Conf::ImgDir()."/graficos16.gif' title='Ver Estadísticas'></a>&nbsp;<img border=0 src='".Conf::ImgDir()."/editar_off.gif' title='Encuesta Emitida'></a>";
		}

		else
		{
			if( $encuesta->IsRespondida($encuesta->fields['id_encuesta'], $rut) )
			{
				echo "<img border=0 src='".Conf::ImgDir()."/encuesta_16_2.gif' title='Encuesta Respondida'>&nbsp;<img border=0 src='".Conf::ImgDir()."/encuesta_resp16.gif' title=' Encuesta Respondida'>";
			}
			else
			{
	            echo "<img border=0 src='".Conf::ImgDir()."/encuesta_resp16_1.gif' title='Encuesta No Respondida'>&nbsp;<a href=responder_encuesta.php?id_encuesta=".$encuesta->fields['id_encuesta']."><img border=0 src='".Conf::ImgDir()."/encuesta_xresp16.gif' title='Responder Encuesta'></a>";
			}
		}
?>
		</td>
	</tr>
	<tr>
		<td colspan = 2>
		<hr size=1>
		</td>
	</tr>
<?
		}
?>
	</table>
		</td>
	</tr>
</table>
        </td>
    </tr>
</table>
<?
    $pagina->PrintBottom();
?>

