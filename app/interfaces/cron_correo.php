<?php 
	require_once dirname(__FILE__).'/../conf.php';
	
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	include_once dirname(__FILE__).'/../classes/AlertaCron.php';
	require_once Conf::ServerDir().'/classes/UtilesApp.php';

	
	set_time_limit(180);
	$sesion = new Sesion (null, true);
	 

	$sesion->debug('abri sesión');
	
$sesion->debug('incluyo alertacron');
	$alerta = new Alerta ($sesion);
$sesion->debug('instancio $alerta');
$encolados=0;
$enviados=0;


$query = "SELECT id_log_correo, subject, mensaje, mail, nombre, id_archivo_anexo FROM log_correo WHERE enviado=0";
$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

	while(list($id, $subject, $mensaje, $mail, $nombre, $id_archivo_anexo )=mysql_fetch_array($resp))
	{
		$correos=array();
		$adresses=explode(',',$mail);
		foreach($adresses as $adress) {
			$correo=array( 'nombre' => $nombre, 'mail' => trim($adress) );
		
			if( validEmail($adress)) {
			$sesion->debug('correo encolado: '.$adress);
			array_push($correos,$correo);
			} else {
			$sesion->debug('no válido '.$adress);
			}
		}
		
		$encolados++;
		
		
		 if(Utiles::EnviarMail($sesion,$correos,$subject,$mensaje,false,$id_archivo_anexo))
		{
                    
			$query2 = "UPDATE log_correo SET enviado=1 WHERE id_log_correo=".$id;
			$resp2 = mysql_query($query2,$sesion->dbh) or Utiles::errorSQL($query2,__FILE__,__LINE__,$sesion->dbh);
				$enviados++;
		} 
	}
$sesion->debug('recorri log correo where enviado = 0');	

echo '<br>Se ha detectado '.$encolados.' correos pendientes';
echo '<br>Se ha  enviado '.$enviados.' correos pendientes';

	/**
Validate an email address.
Provide email address (raw input)
Returns true if the email address has the email 
address format and the domain exists.
*/
function validEmail($email)
{
	$email=trim($email);
   $isValid = true;
   $atIndex = strrpos($email, "@");
   if (is_bool($atIndex) && !$atIndex)
   {
      $isValid = false;
   }
   else
   {
      $domain = substr($email, $atIndex+1);
      $local = substr($email, 0, $atIndex);
      $localLen = strlen($local);
      $domainLen = strlen($domain);
      if ($localLen < 1 || $localLen > 64)
      {
         // local part length exceeded
         $isValid = false;
      }
      else if ($domainLen < 1 || $domainLen > 255)
      {
         // domain part length exceeded
         $isValid = false;
      }
      else if ($local[0] == '.' || $local[$localLen-1] == '.')
      {
         // local part starts or ends with '.'
         $isValid = false;
      }
      else if (preg_match('/\\.\\./', $local))
      {
         // local part has two consecutive dots
         $isValid = false;
      }
      else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
      {
         // character not valid in domain part
         $isValid = false;
      }
      else if (preg_match('/\\.\\./', $domain))
      {
         // domain part has two consecutive dots
         $isValid = false;
      }
      else if(!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',                 str_replace("\\\\","",$local)))
      {
         // character not valid in local part unless 
         // local part is quoted
         if (!preg_match('/^"(\\\\"|[^"])+"$/',
             str_replace("\\\\","",$local)))
         {
            $isValid = false;
         }
      }
      if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A")))
      {
         // domain not found in DNS
         $isValid = false;
      }
   }
   return $isValid;
}

?>
