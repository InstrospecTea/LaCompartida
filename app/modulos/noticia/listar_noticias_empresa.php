<?
	require_once dirname(__FILE__).'/../../../conf.php';
	require_once Conf::ServerDir().'/fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/fw/classes/Pagina.php';
    require_once Conf::ServerDir().'/fw/classes/Utiles.php';
    require_once Conf::ServerDir().'/fw/classes/Lista.php';
	require_once Conf::ServerDir().'/fw/classes/Empresa.php';
	require_once Conf::ServerDir().'/fw/modulos/archivo/classes/Archivo.php';
	
	$sesion = new Sesion('');
    $pagina = new Pagina($sesion);

	$emp = new Empresa($sesion);
	$titulo = "Noticias";
# Logica de permisos

# Obtener id
	if($id_empresa == '')
	{
	    $pro= new Proyecto($sesion);
	
		$sesion->usuario->LoadEmpresa();
		$id_empresa = $sesion->usuario->id_empresa;
		$rut_usuario= $sesion->usuario->fields['rut'];
		
		$emp->load("$id_empresa");
    	$id_noticia_agrupador = $emp->fields['id_noticia_agrupador'];
	
		$pro->LoadProyectoUsuario($rut_usuario);
	    $id_proyecto=$pro->fields['id_proyecto'];
		$pro->LoadProyecto($id_proyecto);
		$id_noticia_agrupador2 = $pro->fields['id_noticia_agrupador'];
	}
	else
	{
		
	    $params_array['codigo_permiso'] = 'ADM';
	    $p = $sesion->usuario->permisos->Find('FindPermiso',$params_array) or Utiles::FatalError("No se encontro el permiso ADM",__FILE__,__LINE__);
	    if($p->fields['permitido'])
		{
            if(!$emp->load($id_empresa))
                   $pagina->FatalError("Empresa Inválida");

			$id_noticia_agrupador = $emp->fields['id_noticia_agrupador'];
		}
		else
		{
        	$sesion->usuario->LoadEmpresa();

            if($id_empresa != $sesion->usuario->id_empresa)
                $pagina->FatalError("No puede ver las noticias de otra empresa");
	        if(!$emp->load($id_empresa))
			       $pagina->FatalError("Empresa Inválida");
            $id_noticia_agrupador = $emp->fields['id_noticia_agrupador'];
		}
	}

    require_once Conf::ServerDir().'/fw/modulos/noticia/listar_noticias.php';

    $pagina->PrintBottom();
?>

