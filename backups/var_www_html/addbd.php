<?php

list($subdominio)=explode('.',$_SERVER['HTTP_HOST']);
ini_set('error_log','/var/www/error_logs/'.$subdominio.'_error_log.log');

if (extension_loaded('newrelic')) {
		newrelic_set_appname ($subdominio);
	   }
   
	if($_SERVER['DOCUMENT_ROOT'].$_SERVER['SCRIPT_URL']==__FILE__) {
		header('HTTP/1.0 403 Forbidden');
		echo '<div style="margin:50px auto;text-align:center;font-family:Arial;">';
		echo '<h2>Error 403</h2>';
		echo '<img  src="//static.thetimebilling.com/cartas/img/lemontech_logo400.png"  style="margin:auto;width:400px;height:126px;display:block;"  alt="Lemontech"/> ';
		echo '<h4>No se puede acceder directamente a este script</h4></div>';
		die();
	}	   

	list($nada,$subdir)=explode('/',$_SERVER['REQUEST_URI']);
	$llave= $subdominio.'.'.$subdir;
	
if (!defined('USERWS') || !defined('PASSWS') || !defined('MISTERY') || (!defined('SUBDOMAIN') &&
	!defined('ROOTDIR') &&
	!defined('DBUSER') &&
	!defined('DBHOST') &&
	!defined('DBNAME') &&
	!defined('DBPORT') &&
	!defined('APPDOMAIN') &&
	!defined('BACKUPDIR') &&
	!defined('APPNAME')&&

	!defined('DBPASS') ) ) {

	define('SUBDOMAIN',$subdominio);
	define('ROOTDIR',$subdir);


include 'AWSSDKforPHP/sdk.class.php';


$dynamodb = new AmazonDynamoDB(array(
        'key' => 'AKIAIQYFL5PYVQKORTBA',
        'secret' => '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'
        ,'default_cache_config' => '/var/www/cache/'));		


$response = $dynamodb->GetItem(array(
    'TableName' => 'thetimebilling',
    'Key' => array( 'HashKeyElement' => array('S'=>$llave)
                
                )));
function _mdecrypt($input,$key){  
    $input = str_replace("\n","",$input);  
    $input = str_replace("\t","",$input);  
    $input = str_replace("\r","",$input);  
    $input = trim(chop(base64_decode($input)));  
    $td = mcrypt_module_open ('tripledes', '', 'ecb', '');  
    $key = substr(md5($key),0,24);  
    $iv = mcrypt_create_iv (mcrypt_enc_get_iv_size ($td), MCRYPT_RAND);  
    mcrypt_generic_init ($td, $key, $iv);  
    $decrypted_data = mdecrypt_generic ($td, $input);  
    mcrypt_generic_deinit ($td);  
    mcrypt_module_close ($td);  
    return trim(chop($decrypted_data));  
    }  
	
 function decrypt( $msg, $k ) {
     
             $msg = base64_decode($msg);          # base64 decode?
     $k=substr($k,0,32);
            # open cipher module (do not change cipher/mode)
            if ( ! $td = mcrypt_module_open('rijndael-256', '', 'ctr', '') )
                return false;
     
            $iv = substr($msg, 0, 32);                          # extract iv
            $mo = strlen($msg) - 32;                            # mac offset
            $em = substr($msg, $mo);                            # extract mac
            $msg = substr($msg, 32, strlen($msg)-64);           # extract ciphertext
           
            if ( @mcrypt_generic_init($td, $k, $iv) !== 0 )  return false;    # initialize buffers
                
     
            $msg = mdecrypt_generic($td, $msg);                 # decrypt
            $msg = unserialize($msg);                           # unserialize
     
            mcrypt_generic_deinit($td);                         # clear buffers
            mcrypt_module_close($td);                           # close cipher module
     
            return $msg;                                        # return original msg
        }
		
	if(is_object($response->body->Item)) {
	if(is_object($response->body->Item->dbuser)) define('DBUSER',  $response->body->Item->dbuser->S);
	if(is_object($response->body->Item->dbhost)) define('DBHOST',$response->body->Item->dbhost->S);
	if(is_object($response->body->Item->dbname)) define('DBNAME',$response->body->Item->dbname->S);
	if(is_object($response->body->Item->dbport)) define('DBPORT',$response->body->Item->dbport->S);
	if(is_object($response->body->Item->dominio)) define('APPDOMAIN',$response->body->Item->dominio->S);
	if(is_object($response->body->Item->backupdir)) define('BACKUPDIR',$response->body->Item->backupdir->S);
	if(is_object($response->body->Item->userws)) define('USERWS',$response->body->Item->userws->S);
	if(is_object($response->body->Item->passws)) define('PASSWS',$response->body->Item->passws->S);
	if(is_object($response->body->Item->backup)) define('BACKUP',$response->body->Item->backup->N);
	if(is_object($response->body->Item->appname)) define('APPNAME', $response->body->Item->appname->S);
	if(is_object($response->body->Item->filepath)) define('FILEPATH', $response->body->Item->filepath->S);
	if(is_object($response->body->Item->slavedb)) define('SLAVEDB', $response->body->Item->slavedb->S);
	$dbpass=is_object($response->body->Item->dbpass)? $response->body->Item->dbpass->S:'';
	$mistery=is_object($response->body->Item->mistery)? $response->body->Item->mistery->N:'0';
	define('MISTERY',$mistery);
		if ($mistery=='1' || $mistery==1) {
			$pass=@decrypt($dbpass,BACKUPDIR);
			define('DBPASS',$pass);
		} else {
			define('DBPASS',$dbpass);
		}
	}
}

if(defined('BACKUP')&& (BACKUP==3 ||BACKUP=='3')) {
	include('offline.php');
die();
}


if(is_readable('/var/www/html/instanceid')) $instanceid=file_get_contents('/var/www/html/instanceid');
header("Instance-ID:" . $instanceid);
if (defined('FILEPATH')) {
	header("X-vhost:" . FILEPATH);
	setcookie('vhost', FILEPATH);
	
	if (isset($_SERVER['REDIRECT_URL'])) {
		$_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_URL'];
	}
}
 defined('DOCROOT') || define('DOCROOT',dirname(__FILE__));
	if(defined('FILEPATH')) {
		defined('APPPATH') || define('APPPATH',DOCROOT.'/'.FILEPATH);
	} else {
		defined('APPPATH') || define('APPPATH',DOCROOT.'/'.ROOTDIR);
	}
	
require_once APPPATH . '/fw/funciones/funciones.php';
require_once APPPATH . '/app/lang/es.php';		//Para que cargue el idioma por defecto
require_once APPPATH . '/app/lang/abogado.php';	//Por si hay palabras especificas relacionadas con el rubro


/*
if (!class_exists('Conf')) {
	class Conf
	{
		public static function AppName() { return APPNAME; }

		public static function ServerDir() { 
			if (defined('FILEPATH')) {
			return dirname(__FILE__).'/'.FILEPATH.'/app'; 
			} else {
			return dirname(__FILE__).'/'.ROOTDIR.'/app'; 
			}
		}

		public static function ImgDir() {
			return  '//static.thetimebilling.com/templates/default/img';
		}
		function MaxLoggedTime() { return 48800; }
		public static function dbHost() { return DBHOST; }
		public static function dbName() { return DBNAME; }
		public static function dbUser() { return DBUSER; }
		public static function dbPass() { return DBPASS; }	
		public static function Server() { return "https://".SUBDOMAIN.".thetimebilling.com"; }
		public static function RootDir() { return '/'.ROOTDIR; }
		function ServerIP() { return SUBDOMAIN.'.thetimebilling.com'; }


	public static function logoutRedirect() { return Conf::RootDir().'/index.php?logout'; }
		public static function Hash() { return 'c85ef9997e6a30032a765a20ee69630b'; }

		public static function Logo($fullPath=false) { return ($fullPath?Conf::Server():'').Conf::ImgDir().'/logo_tt.jpg'; }
		public static function LogoDoc($fullPath=false) { return ($fullPath?Conf::Server():'').Conf::ImgDir().'/logo_tt.jpg'; } 
		public static function FicheroLogoDoc() { return "logo_lemon.png"; }  
		public static function EsAmbientePrueba() { if(defined('BACKUP')&& (BACKUP==2 ||BACKUP=='2')) return true; }

		public static function RutaGraficos() { return "/usr/lib64/php/modules/ChartDirector/lib/phpchartdir.php"; }
		public static function RutaPdf() { return "/usr/share/php/fpdf/fpdf.php"; }
		public static function DireccionPdf() { return "Estudio Lemontech\nTorremolinos 70, Oficina 4\nCP 7550159 Las Condes\nSantiago, Chile\nTf. +56-2 2299243\ninfo@lemontech.cl";}
		public static function LogoPdf($fullPath=true) { return ($fullPath?Conf::Server():'').Conf::ImgDir().'/logo_pdf.jpg'; }
		public static function Templates() { return "default"; }

	public static function Host() { return Conf::Server()."/".ROOTDIR."/"; }

				public static function MantencionTablas() { return array('grupo_cliente','prm_comuna','prm_area_proyecto','prm_area_usuario','prm_tipo_proyecto','prm_moneda','j_prm_materia','j_prm_estado_causa','prm_categoria_usuario'); }


		public static function GlosaTablas()
		{
			$glosa['prm_comuna'] = "glosa_comuna";
					$glosa['j_prm_materia'] = "glosa_materia";
					$glosa['cliente'] = "glosa_cliente";
					$glosa['j_prm_estado_causa'] = "glosa_estado_causa";
					 $glosa['prm_categoria_usuario'] = "Categoríde Usuario";
			$glosa['prm_area_proyecto'] = "prm_area_proyecto";
			$glosa['prm_tipo_proyecto'] = "prm_tipo_proyecto";
			$glosa['prm_area_usuario'] = "prm_area_usuario";

			$glosa['asunto'] = "glosa_asunto";
					 $glosa['prm_banco']="Bancos";
					$glosa['cuenta_banco']="Cuentas Bancarias";

			return $glosa;
		}


	public static    function Locale() { return array('es_CL','es_ES'); }
		public static function NombreIdentificador() {return 'DNI'; }
	public static    	function BorrarDatosAdministracion() { return false; }

	public static    	function TimestampDeployJS(){ return '1234370043'; }
	public static    	function TimestampDeployCSS(){ return '1226330411'; }
	public static    	function UsernameMail() { return 'ptimetracking@lemontech.cl'; }
	public static    	function PasswordMail() { return 'tt.asdwsx'; }
	public static    	function TieneTablaVisitante() { return true; }

		public static function GetConf($sesion, $glosa_opcion)
		{
					$query_conf = "SELECT valor_opcion FROM configuracion WHERE glosa_opcion='$glosa_opcion'";
					$resp_conf = mysql_query($query_conf, $sesion->dbh) or Utiles::errorSQL($query_conf, __FILE__, __LINE__, $sesion->dbh);
					list($valor_opcion) = mysql_fetch_array($resp_conf);
					return $valor_opcion;
		}

	public static      function PasswordWS(){             return defined('PASSWS')? PASSWS:  base64_encode(rand(10000,90000));       }
	public static       function UsuarioWS()  {           return defined('USERWS')? USERWS:  base64_encode(rand(10000,90000));          }


	}
}

defined('ROOTDIR') || define('ROOTDIR',str_replace('//','/','/'.Conf::RootDir()));
defined('DBUSER') || define('DBUSER',Conf::dbUser());
defined('DBHOST') || define('DBHOST',Conf::dbHost());
defined('DBNAME') || define('DBNAME',Conf::dbName());
defined('DBPASS') || define('DBPASS',Conf::dbPass());
defined('BACKUPDIR') || define('BACKUPDIR','/tmp');
defined('USERWS') || define('USERWS',Conf::PasswordWS());
defined('PASSWS') || define('PASSWS',Conf::UsuarioWS());
*/


