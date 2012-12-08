<?php

ini_set('display_errors', 'Off');

if (file_exists('/var/www/html/addbd.php')) {
	require_once '/var/www/html/addbd.php';
} else if (file_exists(dirname(__FILE__) . '/miconf.php')) {
	require_once dirname(__FILE__) . '/miconf.php';
}

if (!class_exists('Conf')) {
	class Conf
	{
		
		public static function AppName() { return APPNAME; }
		public static function ServerDir() { return dirname(__FILE__); }
		public static function ImgDir() { return  '//static.thetimebilling.com/templates/default/img'; }
		public static function MaxLoggedTime() { return 48800; }
		public static function dbHost() { return DBHOST; }
		public static function dbName() { return DBNAME; }
		public static function dbUser() { return DBUSER; }
		public static function dbPass() { return DBPASS; }	
		public static function Server() { return "https://" . SUBDOMAIN . ".thetimebilling.com"; }
		public static function RootDir() { return '/' . ROOTDIR; }
		public static function ServerIP() { return SUBDOMAIN . '.thetimebilling.com'; }
		public static function logoutRedirect() { return Conf::RootDir() . '/index.php?logout'; }
		public static function Hash() { return 'c85ef9997e6a30032a765a20ee69630b'; }
		public static function Logo($fullPath = false) { return ($fullPath ? Conf::Server() : '') . Conf::ImgDir() . '/logo_tt.jpg'; }
		public static function LogoDoc($fullPath = false) { return ($fullPath ? Conf::Server() : '') . Conf::ImgDir() . '/logo_tt.jpg'; } 
		public static function FicheroLogoDoc() { return "logo_lemon.png"; }  
		public static function EsAmbientePrueba() { if(defined('BACKUP')&& (BACKUP == 2 || BACKUP == '2')) return true; }
		public static function RutaGraficos() { return "/usr/lib64/php/modules/ChartDirector/lib/phpchartdir.php"; }
		public static function RutaPdf() { return "/usr/share/php/fpdf/fpdf.php"; }
		public static function DireccionPdf() { return "Estudio Lemontech\nTorremolinos 70, Oficina 4\nCP 7550159 Las Condes\nSantiago, Chile\nTf. +56-2 2299243\ninfo@lemontech.cl";}
		public static function LogoPdf($fullPath = true) { return ($fullPath ? Conf::Server() : '') . Conf::ImgDir() . '/logo_pdf.jpg'; }
		public static function Templates() { return "default"; }
		public static function Host() { return Conf::Server() . "/" . ROOTDIR . "/"; }
		public static function MantencionTablas() { return array('grupo_cliente','prm_comuna','prm_area_proyecto','prm_area_usuario','prm_tipo_proyecto','prm_moneda','j_prm_materia','j_prm_estado_causa','prm_categoria_usuario'); }
		public static function GlosaTablas() {
			$glosa['prm_comuna'] = "glosa_comuna";
			$glosa['j_prm_materia'] = "glosa_materia";
			$glosa['cliente'] = "glosa_cliente";
			$glosa['j_prm_estado_causa'] = "glosa_estado_causa";
			$glosa['prm_categoria_usuario'] = "CategorÃƒÂ­de Usuario";
			$glosa['prm_area_proyecto'] = "prm_area_proyecto";
			$glosa['prm_tipo_proyecto'] = "prm_tipo_proyecto";
			$glosa['prm_area_usuario'] = "prm_area_usuario";

			$glosa['asunto'] = "glosa_asunto";
			$glosa['prm_banco']="Bancos";
			$glosa['cuenta_banco']="Cuentas Bancarias";

			return $glosa;
		}

		public static function Locale() { return array('es_CL','es_ES'); }
		public static function NombreIdentificador() {return 'DNI'; }
		public static function BorrarDatosAdministracion() { return false; }

		public static function TimestampDeployJS(){ return '1234370043'; }
		public static function TimestampDeployCSS(){ return '1226330411'; }
		public static function UsernameMail() { return 'ptimetracking@lemontech.cl'; }
		public static function PasswordMail() { return 'tt.asdwsx'; }
		public static function TieneTablaVisitante() { return true; }

	/**
	 *
	 * @param object $sesion
	 * @param string $conf
	 * @return string
	 *  Ahora comprueba si existe el array $sesion->arrayconf para llenarlo una sola vez y consultar de él de ahí en adelante.
	 * Si no, intenta usar memcache
	 * Tiene fallback al código antiguo por si
	 */
	public static function GetConf(Sesion $Sesion, $conf) {
		global $memcache;
		$existememcache =isset($memcache) && is_object($memcache); // nunca se sabe si correrán este código en una máquina sin MC. Primero se comprueba con isset para evitar un warning de undefined variable.

		// Prioridad sobre los conf?
		//1) Primera Prioridad: Siempre es más barato leer un método static de la clase conf que obtenerlo de memcache o de la base de datos.
		if (method_exists(__CLASS__, $conf)) {
			$config = self::$conf();
			error_log("CONF ESTATICA $conf = $config");
			return $config;

		 // 2) Segunda prioridad: leer de la memoria. Existe variable caching?
		} else if (count($Sesion->arrayconf ) > 0) {
			// 2.1) Usar variable desde caching
			$arrayconf = $Sesion->arrayconf;
			error_log("CACHE HIT! $conf = {$arrayconf[$conf]}");
			return $arrayconf[$conf];

		// 3) Tercera prioridad: existe memcache y la llave de configuración está vigente.
		} else if ($existememcache && $arrayconf= json_decode($memcache->get(DBNAME . '_config'), true)) {
				$Sesion->arrayconf = $arrayconf;
				error_log("CACHE HIT FROM MEMCACHED! $conf ={$arrayconf[$conf]}");
				return $arrayconf[$conf];

		// 4) Cuarta prioridad: tengo que obtener el dato de la BD, aprovecho de llenar el dato en memoria y en memcache.
		} else {
				 //4.1 compruebo conexión a la BBDD para consultar array de configuraciones
				if(isset($Sesion->pdodbh)) {
					$query = "SELECT glosa_opcion, valor_opcion FROM configuracion";
					$bd_configs = $Sesion->pdodbh->query($query)->fetchAll(PDO::FETCH_NUM | PDO::FETCH_GROUP);
					foreach ($bd_configs as $glosa => $valor) {
						$Sesion->arrayconf[$glosa] = $valor[0][0];
					}

					// 4.2) Si existe memcache, fijo la llave usando lo obtenido en 4.1
					if ($existememcache) {
						$memcache->set(DBNAME . '_config', json_encode($Sesion->arrayconf), false, 120);
						error_log("MEMCACHE CACHE SET $conf = {$Sesion->arrayconf[$conf]} (" . count($Sesion->arrayconf) . " registros)");
					}

					return $Sesion->arrayconf[$conf];

				} else {
					error_log('No hay conexion a BD');
					return false;
				}


		}


	}

		public static function PasswordWS() { return defined('PASSWS') ? PASSWS : base64_encode(rand(10000,90000)); }
		public static function UsuarioWS() { return defined('USERWS') ? USERWS : base64_encode(rand(10000,90000)); }

		public static function AmazonKey() {
			return array(
				'key' => 'AKIAIQYFL5PYVQKORTBA',
				'secret' => 'q5dgekDyR9DgGVX7/Zp0OhgrMjiI0KgQMAWRNZwn',
				'default_cache_config' => CACHEDIR
			);
		}
	}
}
defined('ROOTDIR') || define('ROOTDIR', str_replace('//','/','/' . Conf::RootDir()));
defined('DBUSER') || define('DBUSER', Conf::dbUser());
defined('DBHOST') || define('DBHOST', Conf::dbHost());
defined('DBNAME') || define('DBNAME', Conf::dbName());
defined('DBPASS') || define('DBPASS', Conf::dbPass());
defined('BACKUPDIR') || define('BACKUPDIR', '/tmp');
defined('USERWS') || define('USERWS', Conf::PasswordWS());
defined('PASSWS') || define('PASSWS', Conf::UsuarioWS());
defined('CACHEDIR') || define('CACHEDIR', '/var/www/virtual/cache/');


require_once APPPATH . '/fw/funciones/funciones.php';
require_once APPPATH . '/app/lang/es.php';		//Para que cargue el idioma por defecto
require_once APPPATH . '/app/lang/abogado.php';	//Por si hay palabras especificas relacionadas con el rubro