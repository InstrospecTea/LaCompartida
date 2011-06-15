<?
	require_once dirname(__FILE__).'/../../../conf.php';
	require_once Conf::ServerDir().'/fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/fw/classes/Pagina.php';
    require_once Conf::ServerDir().'/fw/classes/Utiles.php';
    require_once Conf::ServerDir().'/fw/classes/Lista.php';
	require_once Conf::ServerDir().'/fw/modulos/proyecto/classes/Proyecto.php';

	$sesion = new Sesion('');
	$pro = new Proyecto($sesion);
    $pagina = new Pagina($sesion);


# Logica de permisos

	if($id_proyecto == '')
		$pagina->FatalError("Grupo Invalido");	

    Proyecto::PermisoVer($id_proyecto, $sesion) or $pagina->FatalError("No tiene permiso para ver este grupo",__FILE__,__LINE__);


	if(!$pro->LoadProyecto($id_proyecto))
       $pagina->FatalError("Grupo Inválido");
	
	$titulo = "Noticias Proyecto";

	$id_noticia_agrupador= $pro->fields['id_noticia_agrupador'];

	require_once Conf::ServerDir().'/fw/modulos/noticia/listar_noticias.php';


    $pagina->PrintBottom();
?>

