<?
	require_once dirname(__FILE__).'/../../../conf.php';
	require_once Conf::ServerDir().'/fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/fw/classes/Utiles.php';
    require_once Conf::ServerDir().'/fw/modulos/proyecto/classes/ArchivoProyecto.php';


    $Sesion = new Sesion('' );
    $pagina = new Pagina($Sesion);
    is_numeric($id_proyecto) or Utiles::errorFatal("id_proyecto incorrecto",__FILE__,__LINE__);
    Proyecto::PermisoVer($id_proyecto,$Sesion) or $pagina->FatalError("Usted no tiene permiso para ver este archivo",__FILE__,__LINE__);


	$params['id_archivo']=$id_archivo;
	$archivo = & new ArchivoProyecto($Sesion,'',$params);
	$archivo->GetData();

	header ("Content-type: ".$archivo->fields['tipo']);
	header('Content-Length: '.strlen($archivo->fields['data']));
	header ("Content-Disposition: attachment; filename=\"".$archivo->fields['nombre']."\"");
	echo($archivo->fields['data']);
	exit;

