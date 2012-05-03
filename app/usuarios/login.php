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
        
	if( $desde == 'sitio' && ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'LoginDesdeSitio') ) ||( method_exists('Conf','LoginDesdeSitio') && Conf::LoginDesdeSitio() ) ) ) 
		{
			$query = "SELECT id_visitante, email, rut, nombre, apellido1, apellido2, empresa, telefono, cont_visitas 
											FROM visitante WHERE email='".$email."'";
			$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			$row = mysql_fetch_assoc($resp);
			
			if(!$row['id_visitante'])
				{
						$query = "SELECT id_visitante FROM visitante ORDER BY id_visitante DESC LIMIT 1";
						$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
						list( $cont ) = mysql_fetch_array( $resp );
						$cont++;
						$recordar = 0;
						$rut = sprintf('%18d',$cont);
						
						$query = "SELECT id_notificacion_tt FROM usuario ORDER BY id_notificacion_tt DESC LIMIT 1";
						$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
						list($id_notificacion) = mysql_fetch_array( $resp ); 
					
					$apellidos = split(' ',$apellido);
					$apellido1 = $apellidos[0];
					$apellido2 = $apellidos[1];
					$query = "INSERT INTO visitante( id_visitante, email, rut, nombre, apellido1, apellido2, empresa, telefono, pais ) 
											VALUES( ".$cont.",'".$email."',".$rut.",'".$nombre."','".$apellido1."','".$apellido2."','".$empresa."','".$telefono."','".$pais."')";
					mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
					
					$correos = array();
					$correo = array( 'mail' => 'areacomercial@lemontech.cl, ffigueroa@lemontech.cl', 'nombre' => 'Equipo Time Tracking' );
					array_push($correos,$correo);
					$subject = 'Demo1: Nuevo visitante.';
					$body = 'Datos del visitante: <br><br>Nombre:       '.$nombre.'
																						<br>Apellido1:    '.$apellido1.'
																						<br>Apellido2:    '.$apellido2.' 
																						<br>Empresa:      '.$empresa.'
																						<br>Telefono:     '.$telefono.'
																						<br>Mail:         '.$email.' 
																						<br>País:         '.$pais.'
																						<br>Fecha:         '. date('c');
					Utiles::EnviarMail($sesion,$correos,$subject,$body,false);
					
					$query = "SELECT id_usuario FROM usuario ORDER BY id_usuario DESC LIMIT 1";
					$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
					list( $id_usuario ) = mysql_fetch_array($resp);
					$id_usuario++;
					
					$usuario = new Usuario($sesion);
					$usuario->Edit('id_usuario',$id_usuario);
					$usuario->Edit('rut',$rut);
					$usuario->Edit('email',$email);
					$usuario->Edit('nombre',$nombre);
					$usuario->Edit('apellido1',$apellido1);
					$usuario->Edit('apellido2',$apellido2);
                                        $usuario->Edit('username',$nombre.' '.$apellido1);
					$usuario->Edit('password',md5('12345'));
					$usuario->Edit('id_visitante',$cont);
					$usuario->Edit('id_notificacion_tt',$id_notificacion);
					$usuario->Write();
					
					$query = "INSERT INTO usuario_permiso( id_usuario, codigo_permiso ) 
												VALUES ( ".$id_usuario.", 'ADM' ),
															 ( ".$id_usuario.", 'ALL' ),
															 ( ".$id_usuario.", 'COB' ),
															 ( ".$id_usuario.", 'DAT' ),
															 ( ".$id_usuario.", 'OFI' ),
															 ( ".$id_usuario.", 'PRO' ),
															 ( ".$id_usuario.", 'REP' ),
															 ( ".$id_usuario.", 'REV' )";
					mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
				}
			else
				{
					$query = "UPDATE visitante SET cont_visitas=cont_visitas+1 WHERE email='".$email."'";
					mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
					$id_visitante = $row['id_visitante'];
					$rut = $row['rut'];
					
					
					$correos = array();
					$correo = array( 'mail' => 'areacomercial@lemontech.cl', 'nombre' => 'Equipo Time Tracking' );
					array_push($correos,$correo);
					$subject = 'Demo1: Visitante repetetivo.';
					$body = 'El visitante, <br><br>Nombre:       '.$row['nombre'].'
																						<br>Apellido1:    '.$row['apellido1'].'
																						<br>Apellido2:    '.$row['apellido2'].' 
																						<br>Empresa:      '.$row['empresa'].'
																						<br>Telefono:     '.$row['telefono'].'
																						<br>Mail:         '.$row['email'].' 
																						<br>País:         '.$row['pais'].' 
										<br><br>ya ha ingresado '.$row['cont_visitas'].' veces al sistema demo. ';
					Utiles::EnviarMail($sesion,$correos,$subject,$body,false);
					
					$query = "SELECT count(*) FROM usuario WHERE id_visitante = ".$id_visitante;
					$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
					list($usuario_existe) = mysql_fetch_array($resp);
					
					if( $usuario_existe == 0 )
						{
							$query = "SELECT id_usuario FROM usuario ORDER BY id_usuario DESC LIMIT 1";
							$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
							list( $id_usuario ) = mysql_fetch_array($resp);
							$id_usuario++;
							
							$query = "SELECT id_notificacion_tt FROM usuario ORDER BY id_notificacion_tt DESC LIMIT 1";
							$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
							list($id_notificacion) = mysql_fetch_array( $resp ); 
							
							
							$usuario = new Usuario($sesion);
							$usuario->Edit('id_usuario',$id_usuario);
							$usuario->Edit('rut',$rut);
							$usuario->Edit('email',$email);
							$usuario->Edit('nombre',$row['nombre']);
							$usuario->Edit('apellido1',$row['apellido1']);
							$usuario->Edit('apellido2',$row['apellido2']);
							$usuario->Edit('password',md5('12345'));
							$usuario->Edit('id_visitante',$row['id_visitante']);
							$usuario->Edit('id_notificacion_tt',$id_notificacion);
							$usuario->Write();
							
							$query = "INSERT INTO usuario_permiso( id_usuario, codigo_permiso ) 
														VALUES ( ".$id_usuario.", 'ADM' ),
																	 ( ".$id_usuario.", 'ALL' ),
																	 ( ".$id_usuario.", 'COB' ),
																	 ( ".$id_usuario.", 'DAT' ),
																	 ( ".$id_usuario.", 'OFI' ),
																	 ( ".$id_usuario.", 'PRO' ),
																	 ( ".$id_usuario.", 'REP' ),
																	 ( ".$id_usuario.", 'REV' )";
							mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
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

if($desde=='sitio' && ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'LoginDesdeSitio') ) ||( method_exists('Conf','LoginDesdeSitio') && Conf::LoginDesdeSitio() ) ) )
	$sesion->Login($rut, '', '12345', $recordar, $desde, $use_ad);
else
$_SESSION['lockerbie']=($_SERVER['HTTPS']?'https':'http').';'.$rut.';'.$password;
setcookie('lockerbie',$_SESSION['lockerbie'],0,ROOTDIR);
    $sesion->Login($rut, $dvrut, $password, $recordar, "", $use_ad);

	$pagina = new Pagina($sesion);

	//Validamos que url no sea vacio ni sea trabajo.php
	//porque cliente envia url como dato para acceso.
	if($url != '' && $url != 'trabajo.php')
		$pagina->Redirect("$url");
	else
		$pagina->Redirect("$pagina_inicio");
?>
