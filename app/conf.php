<?php
require_once '/var/www/html/addbd.php';
require_once APPPATH.'/vendor/autoload.php'; 

if (!class_exists('Conf')) {
	class Conf
	{
		public static function AppName() { return APPNAME; }

		public static function ServerDir() { return dirname(__FILE__); }

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
					 $glosa['prm_categoria_usuario'] = "CategorÃ­de Usuario";
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
defined('APPPATH') || define('APPPATH',dirname(dirname(__FILE__)));




require_once APPPATH.'/fw/funciones/funciones.php';
require_once APPPATH.'/app/lang/es.php';		//Para que cargue el idioma por defecto
require_once APPPATH.'/app/lang/abogado.php';	//Por si hay palabras especificas relacionadas con el rubro