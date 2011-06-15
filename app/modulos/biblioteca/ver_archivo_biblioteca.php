<?
	require_once dirname(__FILE__).'/../../conf.php';
	require_once Conf::ServerDir().'/fw/classes/sesion.php';
	require_once Conf::ServerDir().'/fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/fw/classes/html.php';
	require_once dirname(__FILE__).'/classes/archivo_biblioteca.php';

    $sesion = new Sesion('' );
    $pagina = new Pagina($sesion);
    is_numeric($id_proyecto);
//  Proyecto::PermisoVer($id_proyecto,$sesion) or Utiles::errorFatal("Usted no tiene permiso para ver este archivo",__FILE__,__LINE__);


	$params['tabla']='archivos_biblioteca';
	$params['id_archivo']=$id_archivo;
	$archivo = & new ArchivoBiblio($sesion,'',$params);
	$archivo->GetData();

	header ("Content-type: ".$archivo->fields['tipo']);
	header('Content-Length: '.strlen($archivo->fields['data']));
	header ("Content-Disposition: attachment; filename=\"".$archivo->fields['nombre']."\"");
	echo($archivo->fields['data']);
	exit;

