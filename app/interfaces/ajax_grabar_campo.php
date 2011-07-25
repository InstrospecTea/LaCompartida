<?
	require_once dirname(__FILE__).'/../conf.php';	
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';

	$sesion = new Sesion();
	$pagina = new Pagina($sesion);
	
	if($accion == "cobrable")
	{
		$query = "UPDATE cta_corriente SET cobrable_actual='$valor' WHERE id_movimiento='$id_gasto'";
		$result = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		echo(utf8_encode("OK")); 
		if($valor)
        {
            $query = "UPDATE cta_corriente SET id_cobro='$id_cobro' WHERE id_movimiento='$id_gasto'";
            $result = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
            echo(utf8_encode("OK"));
        }
        else
        {
            $query = "UPDATE cta_corriente SET id_cobro=NULL WHERE id_movimiento='$id_gasto'";
            $result = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
            echo(utf8_encode("OK"));
        }
	}
	else if($accion == "trabajo_cobrable")
	{
		if($valor)
		{
			$query = "UPDATE trabajo SET id_cobro='$id_cobro' WHERE id_trabajo='$id_trabajo'";
            $result = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
            echo(utf8_encode("OK"));
		}
		else
		{
			$query = "UPDATE trabajo SET id_cobro=NULL WHERE id_trabajo='$id_trabajo'";
            $result = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
            echo(utf8_encode("OK"));
		}	
	}
	else if($accion == "tramite_cobrable")
	{
		if($valor)
		{
			$query = "UPDATE tramite SET id_cobro='$id_cobro' WHERE id_tramite='$id_tramite'";
					$result = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
					echo(utf8_encode("OK"));
		}
		else
		{
			$query = "UPDATE tramite SET id_cobro=NULL WHERE id_tramite='$id_tramite'";
					$result = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
					echo(utf8_encode("OK"));
		}
	}
	else if($accion == "agregar_asunto")
	{
		if($valor == "eliminar")
		{
				$query = "UPDATE trabajo SET id_cobro=NULL WHERE id_cobro = '$id_cobro' AND codigo_asunto = '$codigo_asunto'";
        $result = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);        

				if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'SepararGastosPorAsunto') ) || ( method_exists('Conf','SepararGastosPorAsunto') && Conf::SepararGastosPorAsunto() ) )
					{
						$query = "UPDATE cta_corriente SET id_cobro=NULL WHERE id_cobro = '$id_cobro' AND codigo_asunto = '$codigo_asunto'";
						$result = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
					}
					
        $query = "DELETE FROM cobro_asunto WHERE id_cobro='$id_cobro' AND codigo_asunto = '$codigo_asunto'";
        $result = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
   	    echo(utf8_encode("OK"));
		}
		else if($valor == "agregar")
		{
		    $query = "INSERT INTO cobro_asunto SET id_cobro='$id_cobro', codigo_asunto = '$codigo_asunto' 
							ON DUPLICATE KEY UPDATE id_cobro='$id_cobro', codigo_asunto = '$codigo_asunto'";
            $result = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
            echo(utf8_encode("OK"));
		}
		else
			echo 'ERROR EN AJAX';
	}
	else if($accion == "moneda")
	{
		$valor=str_replace(',','.',$valor);
        $query = "UPDATE prm_moneda SET tipo_cambio='$valor' WHERE id_moneda='$id_moneda'";
        $result = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		
        echo(utf8_encode("OK"));
	}
	else if ($accion == "asunto_actividad")
	{
        $query = "SELECT actividades_obligatorias FROM asunto WHERE codigo_asunto='$codigo_asunto'";
        $result = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		list($obligatorio) = mysql_fetch_array($result);
		echo(utf8_encode("OK~".$obligatorio));
	}
  else if($accion == "cobrar_trabajo")
  {
        if($id_cobro == "")
           $id_cobro = "NULL";
        else
           $id_cobro = "'$id_cobro'";
       $query = "UPDATE trabajo SET id_cobro = if(id_cobro is not null,NULL,$id_cobro)
                   WHERE id_trabajo = '$id_trabajo'
                    ";

       $resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
       echo(utf8_encode("OK"));
#       echo(utf8_encode($query));
  }
  else if($accion == "cobrar_tramite")
  {
  				if($id_cobro =="")
  						$id_cobro = "NULL";
  				else
  						$id_cobro = "'$id_cobro'";
  				$query = "UPDATE tramite SET id_cobro = if(id_cobro is not null,NULL,$id_cobro) 
  										WHERE id_tramite = '$id_tramite'
  											";
  											
  			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
        echo(utf8_encode("OK"));
  }
  else if($accion == 'guardar_tipo_cambio')
  {
	  	$tipo_cambio=str_replace(',','.',$tipo_cambio);
	  	$sql = "UPDATE cobro_moneda SET tipo_cambio = ".$tipo_cambio." WHERE id_cobro = ".$id_cobro." AND id_moneda = ".$id_moneda;
			$resp = mysql_query($sql, $sesion->dbh) or Utiles::errorSQL($sql,__FILE__,__LINE__,$sesion->dbh);
			echo(utf8_encode("OK"));
  }
	else
		echo 'ERROR EN AJAX';
?>
