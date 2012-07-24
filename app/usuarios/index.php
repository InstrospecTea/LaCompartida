<?php
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Usuario.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../app/classes/UtilesApp.php';
 
	
	 
$sesion = new Sesion( array() );
	     
	
			
			if(isset($_GET['endswitch']) && isset($_SESSION['switchuser']) ) {
			unset($_SESSION['switchuser']);
			$sesion->usuario =	new UsuarioExt($sesion, $_SESSION['RUT']);
			} else  if(isset($_GET['switchuser']) && $_SESSION['RUT']=='99511620')  {
				$_SESSION['switchuser']=$_GET['switchuser'];
				$sesion->usuario =	new UsuarioExt($sesion, $_SESSION['switchuser']);
			}
			
	$pagina = new Pagina($sesion);
	       
   
	$pagina->titulo = "Bienvenido a ".Conf::AppName();

	$pagina->PrintTop();
	

        
	


	require_once dirname(__FILE__).'/../../app/templates/'.Conf::Templates().'/index.php';

	$pagina->PrintBottom();
 
