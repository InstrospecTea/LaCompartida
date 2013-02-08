<?php
        session_start();
	require_once dirname(__FILE__).'/../../app/conf.php';
       	
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';


	$sesion = new Sesion(null, true);

	// usar Windows login en Active Directory con ldap-Autentificacion
	if (isset($ldap_user)){
		$query = "SELECT DISTINCT rut, dv_rut FROM usuario WHERE ldap_user='".$ldap_user."'";
		$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		$usario_array = mysql_fetch_assoc($resp);
		$dvrut = $usario_array['dv_rut'];
		$rut = $usario_array['rut'];
	}
        
	if( $desde == 'sitio' && Conf::GetConf($sesion,'LoginDesdeSitio') )		{
			$query = "SELECT id_visitante, email, rut, nombre, apellido1, apellido2, empresa, telefono, cont_visitas 
											FROM visitante WHERE email='".$email."'";
			$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			$row = mysql_fetch_assoc($resp);

			if(empty($row['id_visitante'])) 	{
						$query = "SELECT id_visitante FROM visitante ORDER BY id_visitante DESC LIMIT 1";
						$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
						list( $id_visitante ) = mysql_fetch_array( $resp );
						$id_visitante++;
						$rut = sprintf('%18d',$cont);
						$apellidos = explode(' ',$apellido);
						$apellido1 = $apellidos[0];
						$apellido2 = $apellidos[1];
					$query = "INSERT INTO visitante( id_visitante, email, rut, nombre, apellido1, apellido2, empresa, telefono, pais ) 
									VALUES( ".$id_visitante.",'".$email."',".$rut.",'".$nombre."','".$apellido1."','".$apellido2."','".$empresa."','".$telefono."','".$pais."')";
					mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);

					$userdata=array('rut'=>$rut
											,'email'=>$email
											,'nombre'=>$nombre
											,'apellido1'=>$apellido1
											,'apellido2'=>$apellido2
											,'empresa'=>$empresa
											,'telefono'=>$telefono
											,'pais'=>$pais
											,'fecha'=>date('c'));

					UtilesApp::CorreoAreaComercial($row,0);
					UtilesApp::CrearUsuario($sesion,$userdata,$id_visitante) ;
				} 	else		{
					$query = "UPDATE visitante SET cont_visitas=cont_visitas+1 WHERE email='".$email."'";
					mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
					$id_visitante = $row['id_visitante'];
		
					UtilesApp::CorreoAreaComercial($row,$row['cont_visitas']);

					$query = "SELECT count(*) FROM usuario WHERE id_visitante = ".$id_visitante;
					$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
					list($usuario_existe) = mysql_fetch_array($resp);

					if( $usuario_existe == 0 ) 			{
							$userdata=array('rut'=>$row['rut']
											,'email'=>$row['email']
											,'nombre'=>$row['nombre']
											,'apellido1'=>$row['apellido1']
											,'apellido2'=>$row['apellido2']);
							UtilesApp::CrearUsuario($sesion,$row,false) ;
						}
		}
	}
	  



  if($log != "")
  {
    # esto es cuando se trata de loguear en la onda rut=XXXX&password=YYYY encoded en base 64
    $decoded = base64_decode($log);
    list($rut,$dv_rut,$password,$url) = split("&", $decoded);
    #saco los valores
    list($caca,$rut) = split("=",$rut);
    list($caca,$dvrut) = split("=",$dv_rut);
    list($caca,$password) = split("=",$password);
    $_SESSION['lockerbie']=$rut.';'.$password;
		list($caca,$url) = split("=",$url);
		$pagina_inicio = Conf::RootDir()."/app/interfaces/".$url;
  }
	if(!$pagina_inicio)
		$pagina_inicio = "index.php";

	/*
	Se genera una url enlace directo.
	*/
	if($infix)
	{
		$lista = urldecode($infix);
		list($rut,$dvrut,$password) = explode('|',$lista);
                 
		$rut = base64_decode($rut);
		$dvrut = base64_decode($dvrut);
		$password = base64_decode($password);
	}


if($desde=='sitio' && ( UtilesApp::GetConf($sesion,'LoginDesdeSitio') )) {
	$sesion->Login($rut, '', '12345', $recordar, $desde, $use_ad);
} else {
if(isset($_POST['urlto']) && $_POST['urlto']!='') $url=$_POST['urlto'] ;
	$_SESSION['lockerbie']=($_SERVER['HTTPS']?'https':'http').';'.$rut.';'.$password;
	setcookie('lockerbie',$_SESSION['lockerbie'],0,ROOTDIR);

			global $memcache;
			$existememcache =isset($memcache) && is_object($memcache); 
				if ($existememcache) {
					$token=sha1(LLAVE.'_'.$rut.'_'.time()) ;

					setcookie('wstoken',$token,(time()+900),ROOTDIR);
					$memcache->set($token, base64_encode(serialize(array($rut,$password))), false, 900);

					} else {
						setcookie('wstoken','No habilitado',0,ROOTDIR);

					}

    $sesion->Login($rut, $dvrut, $password, $recordar, "", $use_ad);
}
	$pagina = new Pagina($sesion);

	//Validamos que url no sea vacio ni sea trabajo.php
	//porque cliente envia url como dato para acceso.
	if($url != '' && $url != 'trabajo.php') {
		$pagina->Redirect("$url");
	} else {
		$pagina->Redirect("$pagina_inicio");
	}