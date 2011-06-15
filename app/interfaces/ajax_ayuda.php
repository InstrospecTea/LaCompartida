<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/Ayuda.php';
	
	
    $sesion = new Sesion('');
	$pagina = new Pagina ($sesion);

	$html = 'EXITO';
	if($accion == 'cargar_ayuda')
	{
		$ayuda = new Ayuda($sesion);
		if($ayuda->Load($id_ayuda))
		{
			echo $ayuda->fields['descripcion'];
		}
		else
			echo 'FAIL';
		
	}
?>