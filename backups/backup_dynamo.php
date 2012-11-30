#!/usr/bin/php
<?php
$correo='';
$correofinal='';
$arreglo=array();
$DDBarray=array();

 $errores = array();
 function loguear($msg,$alfinal=false){
    global $correo,$correofinal;
	$entry .= "[".date('Y-m-d H:i:s')."] ".$msg;
	echo $entry."\n";
	if($alfinal) {
		$correofinal.=$entry."<br/>\n";
	} else {
		$correo.=$entry."<br/>\n";
	}
	return true;
}

if (file_exists(dirname(__FILE__).'/AWSSDKforPHP/sdk.class.php')) {

    require_once dirname(__FILE__).'/AWSSDKforPHP/sdk.class.php';
    } else {
	$errores[] = loguear("No se pudo comprobar si existe la libreria PEAR de AWS: AWSSDKforPHP");
	 require_once 'AWSSDKforPHP/sdk.class.php';
	}

require_once dirname(__FILE__).'/DatabaseUpdater.php';

set_time_limit(7200);

 if(!is_dir('/var/www/error_logs')){
		loguear("creando directorio '/var/www/error_logs'");
		if(!mkdir('/var/www/error_logs', 0777, true)){
			$errores[] = loguear("error al crear directorio '/var/www/error_logs'");
			continue;
		}
	}
	
 class CONF {
        var $dir_temp = '/tmp';
        var $alerta_disco_temp = 5; //(GB) si el espacio libre es menos q eso, tira un mensaje (y manda mail)
        var $alerta_disco_base = 5; //(GB) si el espacio libre es menos q eso, tira un mensaje (y manda mail)

        var $mailer = array(
                'host' => 'smtp.gmail.com',
                'port' => 465,
                'user' => 'bogorandom@gmail.com',
                'pass' => '111284',
                'from' => 'bogorandom@gmail.com',
                'to' => 'servidores@lemontech.cl');
 }

 function decrypt( $msg, $k ) {
     
             $msg = base64_decode($msg);          # base64 decode?
     
            # open cipher module (do not change cipher/mode)
            if ( ! $td = mcrypt_module_open('rijndael-256', '', 'ctr', '') )
                return false;
     
            $iv = substr($msg, 0, 32);                        
            $mo = strlen($msg) - 32;                            
            $em = substr($msg, $mo);                           
            $msg = substr($msg, 32, strlen($msg)-64);          
           
            if ( mcrypt_generic_init($td, $k, $iv) !== 0 )      
                return false;
     
            $msg = mdecrypt_generic($td, $msg);                 
            $msg = unserialize($msg);                          
     
            mcrypt_generic_deinit($td);                        
            mcrypt_module_close($td);                          
     
            return $msg;                                       
        }
 
 


function EnviarMail($conf, $subject, $body)
{
	require_once dirname(__FILE__).'/PHPMailer/class.phpmailer.php';
	try{
		$mail = new PHPMailer();

		$mail->IsSMTP(); // telling the class to use SMTP
		$mail->SMTPAuth   = true;// enable SMTP authentication
		$mail->SMTPSecure = "ssl";// sets the prefix to the servier
		$mail->Host       = $conf['host'];// sets GMAIL as the SMTP server
		$mail->Port       = $conf['port'];// set the SMTP port for the GMAIL server

		$mail->Username = $conf['user']; //recordar poner en el conf el correo completo: algo@lemontech.cl
		$mail->Password = $conf['pass'];

		$mail->SetFrom($conf['from'], 'Mailer Backups TT');

		$correos = explode(',', $conf['to']);
		foreach($correos as $correo){
			$mail->AddAddress($correo);
		}

		$mail->Subject    = $subject;
		$mail->AltBody    = "Debe utilizar un lector de correos que acepte HTML"; // optional, comment out and test
		$mail->MsgHTML($body);

		if(!$mail->Send()){
			return 'Error: ' . $mail->ErrorInfo;
		}
		return null;
	}
	catch(Exception $e){
		return 'Excepcion: ' . $e->getMessage();
	}
}



//calcular las fechas de los backups q no se borran
function fechaBorrable($fechaviejo, $duracion){
	if(empty($duracion)) $duracion = array(7, 4, 'friday');
	$diaSemana = isset($duracion[2]) ? $duracion[2] : 'friday';

	$timeDias = strtotime(-($duracion[0]-1).' days');
	$fechaDias = date('Y-m-d', $timeDias);
	$vierneses = array();
	for($i = $duracion[1]; $i; $i--){
		$vierneses[] = date('Y-m-d', strtotime("-$i $diaSemana", $timeDias));
	}

	return $fechaviejo < $fechaDias && !in_array($fechaviejo, $vierneses);
}




loguear('leyendo conf');


//leer conf de mysql y lista de dbs
$conf = new CONF();
$fecha = date('Y-m-d');

loguear("limpiando temporales");
$temps = glob($conf->dir_temp . "/*_*.sql*");
if(!empty($temps)){
	loguear("borrando ".count($temps)." archivos temporales");
}
foreach($temps as $temp){
	if(!unlink($temp)){
		$errores[] = loguear("error al borrar temporal antiguo $temp");
	}
}
if(!is_dir('/var/www/cache/S3')) mkdir('/var/www/cache/S3',0755);

$S3sdk = new AmazonS3(array('key' => 'AKIAJDGKILFBFXH3Y2UA',
			'secret' => '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'
			, 'default_cache_config' => '/var/www/cache/S3'));
 
 

if(!is_dir('/var/www/cache/dynamoDBbackups')) mkdir('/var/www/cache/dynamoDBbackups',0755);
loguear('leyendo DynamoDB');
$connection_params = array(
		    'key' => 'AKIAJDGKILFBFXH3Y2UA',
		    'secret' => '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',
		    'default_cache_config' => '/var/www/cache/dynamoDBbackups'
		  );

$dynamodb = new AmazonDynamoDB($connection_params);

$db_updater = new DatabaseUpdater(
  'c85ef9997e6a30032a765a20ee69630b',
  $connection_params
);

$scan_response = $dynamodb->scan(array(
'TableName' => 'thetimebilling'
));
$i=0;


foreach($scan_response->body->Items as $registro):
         $i++;
         foreach($registro as $etiqueta=>$objeto) {
                foreach(get_object_vars($objeto) as $tipo=>$valor)      $arreglo[$i][$etiqueta]= $valor;
         }
 endforeach;
$jsonscan=json_encode($arreglo);
file_put_contents('/var/www/backup_svn/dynamo.json',$jsonscan);
 loguear('termina la lectura de DynamoDB');
 
foreach($arreglo as $sitio):
    if(isset($argv[1]) && $argv[1]!=$sitio['dbname']) {
	//	loguear("saltandose respaldo de {$sitio['dbname']} por que se pasó únicamente el subdominio {$argv[1]} por linea de comando",true);
		continue;
	}
    if($sitio['backup']!=1) {
		loguear('Respaldo apagado para '.$sitio['dominio'],true) ;
	} else {
			loguear('respaldando '.$sitio['dominio']) ;
			list($db, $dominio, $dbclon,) = array($sitio['dbname'],$sitio['vhost'],$sitio['dbclon']);
			$slavehost=($sitio['dbslave']!='_' && $sitio['dbslave']!='')? $sitio['dbslave']:$sitio['dbhost'];
			$subdominiosubdir=explode('.',$sitio['subdominiosubdir']);
			$bucketname='ttbackup'.$subdominiosubdir[0];
			loguear('comprobando bucket S3 '.$bucketname) ;
			try {
			$comprobarbucket=$S3sdk->create_bucket($bucketname,AmazonS3::REGION_US_E1);
			} catch(Exception $e) {
			print_r($e);
			
			}
			//print_r($comprobarbucket);
			$duracion=array(intval($sitio['days']),intval($sitio['weeks']),$sitio['dia']);

			if(fechaBorrable($fecha, $duracion)){
				loguear("saltandose respaldo de $db por configuracion de duracion de backups (".print_r($duracion, true).")",true);
				continue;
			}

			loguear("respaldando $db en $dominio");

			

			$out = array();
			$ret = 0;

			//genero el dump sql
			$filebkp=$db . "_" . $fecha . ".sql.gz";
			$path = $conf->dir_temp . "/" . $filebkp;
			
				
				if($sitio['mistery']==1) $sitio['dbpass']=decrypt($sitio['dbpass'],$sitio['backupdir']);
			
			try{
				$file_exists=$S3sdk->get_object_metadata($bucketname,$filebkp);
				} catch(Exception $e) {
				print_r($e);
				}
					 
				 
				if($file_exists[Key]==$filebkp) {
					loguear("respaldo $filebkp ya existe: omitiendo...");
					
				} else   {
				
					if(!file_exists($conf->dir_temp)){
						loguear("creando directorio temporal ".$conf->dir_temp);
						if(!mkdir($conf->dir_temp, 0755, true)){
							$errores[] = loguear("error al crear directorio ".$conf->dir_temp);
							continue;
						}
					}

				
		
		/*********** DUMPEANDO A GZIP ******************/
		
		loguear("dumpeando a $path");
		$sentencia = "mysqldump --disable-keys --skip-add-locks  --lock-tables=false --net_buffer_length=50000  --extended-insert  --delayed-insert  --insert-ignore --quick --single-transaction --add-drop-table  --host=" .$slavehost . " --user=" .$sitio['dbuser'] . "  --password=" . $sitio['dbpass'] . " $db | gzip  > $path";
 		//echo $sentencia;
		exec(" $sentencia ", $out, $ret);	
			if($ret){
			$errores[] = loguear("error generando dump para $db. retornado: $ret\noutput: ".implode("\n", $out));
			if(file_exists($path)){
				loguear("borrando dump fallado");
				if(!unlink($path)){
					$errores[] = loguear("error al borrar dump fallado");
				}
			}
		continue;
		}
		

		loguear("copiando a S3:  $path");
		try {
			$crearobject=$S3sdk->create_object($bucketname,$filebkp,array('fileUpload'=>$path));
			$db_updater->update('update_db', $sitio['subdominiosubdir'], $sitio['update_db']);
		} catch(Exception $e) {
		print_r($e);
		
		}
	 
		if($ret) 	$errores[] = loguear("error copiando a S3, retornado: $ret\noutput: ".implode("\n", $out));
		
		if(!unlink($path)){
			$errores[] = loguear("error al borrar el comprimido temporal $path");
		}
	}  
		/*********** CLONANDO ******************/
		
	if($dbclon && $dbclon!='' && $dbclon!='_')  {
			loguear("clonando a ".$dbclon);
				$dbclonarray=explode(':',$dbclon);
				if(count($dbclonarray)==2) {
					$dbclonarray['dbhost']=$dbclonarray[0];
					$dbclonarray['dbname']=$dbclonarray[1];
				} else {
					$dbclonarray['dbhost']=$sitio['dbhost'];
					$dbclonarray['dbname']=$dbclonarray[0];
				}
				if ($dbclonarray['dbname']==$db && $dbclonarray['dbhost']==$sitio['dbhost']) {
					$errores[] = loguear("no se puede clonar ".$dbclonarray['dbhost'].".$db sobre si misma");
				} else {
					$sentencia = "mysqldump --disable-keys --skip-add-locks  --lock-tables=false --net_buffer_length=50000  --extended-insert  --delayed-insert  --insert-ignore --quick --single-transaction --add-drop-table  --host=" . $slavehost . " --user=" .$sitio['dbuser'] . "  --password=" . $sitio['dbpass'] . " $db |  mysql --host=" . $dbclonarray['dbhost'] . "   --user=" .$sitio['dbuser'] . "   --password=" . $sitio['dbpass'] . "  ". $dbclonarray['dbname'];
					//echo $sentencia;
					exec(" $sentencia ", $out, $ret);
				
					if($ret){
					$errores[] = loguear("error clonando $db en {$dbclonarray['dbhost']} {$dbclonarray['dbname']}: \n $sentencia \n $ret\noutput: ".implode("\n", $out));
					}
				}
		} 
		
	
	loguear("Listando contenidos del bucket $bucketname");
	$respaldos=array();
	$respaldosborrar=array();
	if (($contents = $S3sdk->list_objects($bucketname)) !== false) {
		foreach ($contents->body as $object) {
			$dropname = $object->Key;
			if($dropname!='') {
			
			 if(preg_match("/\d{4}-\d{2}-\d{2}/", $dropname, $match)){
				$fechaviejo = $match[0];
					if(fechaBorrable($fechaviejo, $duracion) )  {
						$S3sdk->delete_object($bucketname,$dropname);
						$respaldosborrar[]=$dropname;
						} else 	if($object->Size<500) {
						$S3sdk->delete_object($bucketname,$dropname);
						$respaldosborrar[]=$dropname;
						} else {
						 $respaldos[]=$dropname;
						}
					}
				}
			}
	}			
	
		if(count($respaldosborrar)>0) {
			loguear("Eliminando ".count($respaldosborrar)."respaldos viejos o fallados del bucket $bucketname");
		}
		loguear(implode("\n ",$respaldos));		
	$espacio = disk_free_space($conf->dir_temp)/(1024*1024*1024);
		if($espacio < $conf->alerta_disco_base){
			$errores['espacio_base'] = loguear("quedan solo $espacio GB libres en ".$conf->dir_temp);
		}	else if(isset($errores['espacio_base'])){
		unset($errores['espacio_base']);
		}
		
		//borro los backups antiguos q no sean de esta semana o de los ultimos 5 viernes
	loguear("borrando backups antiguos...");
		$dir = rtrim($sitio['backupdir'],'/');
			if(file_exists($dir)){
			
				$viejos = glob($dir . "/" . $db . "_*.sql.tar.gz");
				foreach($viejos as $viejo){
					if(preg_match("/\d{4}-\d{2}-\d{2}/", $viejo, $match)){
						$fechaviejo = $match[0];
						if(fechaBorrable($fechaviejo, $duracion)){
							loguear("borrando backup antiguo $viejo");
							if(!unlink($viejo)){
								$errores[] = loguear("error al borrar $viejo");
							}
						}
					}
				}
				$viejos2= glob($dir . "/" . $db . "_*.sql.gz");
				foreach($viejos2 as $viejo){
					if(preg_match("/\d{4}-\d{2}-\d{2}/", $viejo, $match)){
						$fechaviejo = $match[0];
						if(fechaBorrable($fechaviejo, $duracion)){
							loguear("borrando backup antiguo $viejo");
							if(!unlink($viejo)){
								$errores[] = loguear("error al borrar $viejo");
							}
						}
					}
				}
		}
    }  
	
	
    
endforeach;



$espacio_disco_local = disk_free_space($conf->dir_temp)/(1024*1024*1024);
	if($espacio_disco_local < $conf->alerta_disco_temp){
		$errores['espacio_base'] = loguear("quedan solo ".$espacio_disco_local." GB libres en ".$conf->dir_temp);
	}	else if(isset($errores['espacio_base'])){
		unset($errores['espacio_base']);
	}


if(!empty($errores)){
	loguear(count($errores) . " errores, mandando mail...");
	
	$errorMail = EnviarMail($conf->mailer, count($errores) . ' problemas en proceso de backups', $correo.'<br><hr><br>'.$correofinal);
	if($errorMail){
		loguear('error mandando mail: '.$errorMail);
	}
	else{
		loguear('mail ok');
	}
}

loguear("fin");

?>
