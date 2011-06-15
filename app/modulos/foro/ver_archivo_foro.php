<?
	require_once dirname(__FILE__).'/../../../conf.php';
	require_once Conf::ServerDir().'/fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/fw/classes/Utiles.php';
    require_once Conf::ServerDir().'/app/modulos/foro/classes/ArchivoForo.php';


    $sesion = new Sesion('' );
    $pagina = new Pagina($sesion);
    is_numeric($id_proyecto);
//  Proyecto::PermisoVer($id_proyecto,$sesion) or Utiles::errorFatal("Usted no tiene permiso para ver este archivo",__FILE__,__LINE__);


	$params['tabla']='foro_mensaje_archivo';
	$params['id_archivo']=$id_foro_mensaje_archivo;
	$archivo = & new ArchivoForo($sesion,'',$params);
	$archivo->GetData();

	header ("Content-type: ".$archivo->fields['tipo']);
	header('Content-Length: '.strlen($archivo->fields['data']));
	header ("Content-Disposition: attachment; filename=\"".$archivo->fields['nombre']."\"");
	echo($archivo->fields['data']);
	exit;

