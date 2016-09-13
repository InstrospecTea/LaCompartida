<?php

require_once dirname(__FILE__) . '/../../app/conf.php';
require_once dirname(__FILE__) . '/PhpConsole.php';
require_once dirname(__FILE__) . '/Slim/Slim.php';

require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/../app/classes/UsuarioExt.php'; //Siempre debe de existir esta clase que extiende usuario



class Sesion {

	// Data Base Handler
	var $dbh = null;
	// Usuario activo
	var $usuario = null;

	/**
	 * @var PDO
	 */
	var $pdodbh = null;
	var $pdoslave = null;
	// Fecha ultimo ingreso
	var $ultimo_ingreso = null;
	// El usuario esta logueado?
	var $logged = false;
	// El usuario debe ser reenviado al index?
	var $goto_index = false;
	// String con �ltimo error
	var $error_msg;
	var $switch_user;
	// tablas
	var $tbl_usuario = 'usuario';
	var $Slim=null;
	protected $plugin=Array();

	private static $pluginsLoaded = false;
	private static $langLoaded = false;

	function Sesion($permisos = null, $login = false) {

		if (isset($_SESSION['MaxLoggedTime'])) {
			$MaxLoggedTime = max(3600, intval($_COOKIE['MaxLoggedTime']));
			ini_set("session.gc_maxlifetime", $MaxLoggedTime);
			ini_set("session.cookie_lifetime", $MaxLoggedTime);
		} else {
			ini_set("session.gc_maxlifetime", "18000");
			ini_set("session.cookie_lifetime", "18000");
		}


		static $i1 = 0;
		if (!isset($_SESSION)) {
			session_cache_expire(480);
			session_start();

			if ($i1++ == 0) {
				header("Cache-control: private");
			}
		}
		extract($_REQUEST);

		$this->error_msg = isset($_SESSION['ERROR']) ? $_SESSION['ERROR'] : '';
		$this->ultimo_ingreso = isset($_SESSION['ULTIMO_INGRESO']) ? $_SESSION['ULTIMO_INGRESO'] : '';

		$this->dbConnect();

		$this->loadConfig();

		static $i2=0;
		if(!$i2++) {
			$MaxLoggedTime= intval(Conf::read('MaxLoggedTime')) ;
			setcookie("MaxLoggedTime",'14400', time()+$MaxLoggedTime);
		}
		$this->LoadLang();
		$this->Slim = new Slim(
			array(
				'log.enabled' => false,
				'debug' => false,
				'cookies.lifetime' => 0,
				'cookies.path' => null,
				'cookies.domain' => null,
				'log.writer' => "Slim_LogFileWriter",
				'session.handler' => null
			)
		);

		$this->LoadPlugin();

		$p = 1;

		if (!$login) {
			$this->Validate($permisos);
		}

		setlocale(LC_ALL, Conf::Locale());
	}

	function LoadLang() {
		if (self::$langLoaded) {
			return;
		}
		global $_LANG;
		$query = 'select archivo_nombre from prm_lang where activo=1 order by orden ASC';
		if ($result = mysql_query($query, $this->dbh)) {
			while ($archivo = mysql_fetch_row($result)) {
				include_once Conf::ServerDir() . '/lang/' . $archivo[0];
			}
		}
		self::$langLoaded = true;
	}

	function LoadPlugin() {
		if (self::$pluginsLoaded) {
			return;
		}

		$query = 'select archivo_nombre from prm_plugin where activo=1 order by orden ASC';
		if ($archivos = $this->pdodbh->query($query)) {
			foreach ($archivos as $archivo)  {
				include_once Conf::ServerDir() . '/plugins/' . $archivo[0];
			}
		}
		self::$pluginsLoaded = true;
	}

	/**
	 * Conecta a la base de datos
	 * @param boolean $show_errors indica si muestra los mensajes de error.
	 * @return boolean
	 */
	public function dbConnect($show_errors = true) {
		$this->dbh = @mysql_connect(Conf::dbHost(), Conf::dbUser(), Conf::dbPass()) or $show_errors ? die(mysql_error()) : false;

		if ($this->dbh === false) {
			return false;
		}
		mysql_select_db(Conf::dbName(), $this->dbh) or mysql_error($this->dbh);

		$cadenadb = 'mysql:dbname=' . Conf::dbName() . ';host=' . Conf::dbHost();

		try {
			$this->pdodbh = new PDO($cadenadb, Conf::dbUser(), Conf::dbPass());
			$this->pdodbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e) {
			echo "Error Connection: " . $e->getMessage();
			file_put_contents("resources/logs/Connection-log.txt", DATE . PHP_EOL . $e->getMessage() . PHP_EOL . PHP_EOL, FILE_APPEND);
			return false;
		}
		$this->setTimezone($show_errors);
		return true;
	}

	/**
	 * Selecciona el timezone para la base de datos.
	 * @param type $show_errors indica si muestra los mensajes de error.
	 */
	function setTimezone($show_errors = true) {
		$zona = Conf::GetConf($this, 'ZonaHoraria');

		if ($zona != '') {
			$query = "SET time_zone = '$zona'";
			$result = mysql_query($query, $this->dbh) or $show_errors ? mysql_error($this->dbh) : false;

			if (!$result && $show_errors) {
				$mensaje = "No se pudo seleccionar la ZonaHoraria $zona,"
					. " revisar que exista en la tabla 'time_zone_name' de la base de datos 'mysql'"
					. " Esta fue la query: $query";
				Utiles::errorSQL($mensaje, __FILE__, __LINE__, $this->dbh);
			}
			$this->pdodbh->query($query);
		}
	}

	function Validate($permisos) {
		$this->logged = false;



		if (method_exists('Conf', 'GetConf')) {
			$MaxLoggedTime = Conf::GetConf($this, 'MaxLoggedTime');
		} else if (method_exists('Conf', 'MaxLoggedTime')) {
			$MaxLoggedTime = Conf::MaxLoggedTime();
		} else {
			$MaxLoggedTime = '14400';
		}

		if (time() - $_SESSION['LAST_ACTION'] > $MaxLoggedTime)
		{
			$ultima_accion = date('H:i:s', $_SESSION['LAST_ACTION']);
			$minutos_max_logged = number_format((time() - $_SESSION['LAST_ACTION']) / 60);
			if(!isset($_SESSION['LAST_ACTION']))
			{
				if(!isset($this->error_msg))
					$this->error_msg = 'Error al iniciar sesi�n';
			}
			if ((time() - $_SESSION['LAST_ACTION']) / 60 > 480)
			{
				if(!isset($this->error_msg))
					$this->error_msg = 'La sesi�n ha expirado';
			}
				else
			{
				$this->error_msg = 'La sesi�n ha expirado (' . $minutos_max_logged . ' min. sin actividad). La �ltima acci�n fue a las ' . $ultima_accion . '.';
			}
			return false;
		}

		/* if($_SESSION['APP'] != Conf::AppName())
		  {
		  $this->error_msg = 'Usted se ha logueado en otra Aplicaci�n';
		  return false;
		  } */

		if (( method_exists('Conf', 'GetConf') && Conf::GetConf($this, 'TablaJuicios') ) || method_exists('Conf', 'TablaJuicios')) {
			if ($_SESSION['ACTIVO_JUICIO'] != 1) {
				$this->error_msg = 'Usuario desactivado, pongase en contacto con el administrador';
				return false;
			}
		} else {
			if ($_SESSION['ACTIVO'] != 1) {
				$this->error_msg = 'Usuario desactivado, pongase en contacto con el administrador.';
				return false;
			}
		}

		if ($_SESSION['RUT'] == '')
		{
			if(!$this->error_msg)
				$this->error_msg = '';
			return false;
		}

		$rut = $_SESSION['RUT'];
		if ($rut == '99511620' && isset($_SESSION['switchuser'])) {
			$this->usuario = new UsuarioExt($this, $_SESSION['switchuser']);
		} else {
			$this->usuario = new UsuarioExt($this, $rut);
		}



		if (!$this->usuario->loaded)
		{
			$this->error_msg = "No se encuentra el usuario.";
			return false;
		}

		if ($permisos != null)
		{
			$permitido = false;
			$default_permitido = true;
				//$default_permitido existe para el caso en que se entreguen solo permisos negativos Ejemplo: Sesion('~RELOJ');
				//Revisaremos RELOJ, y si existe default_permitido ser� falso.
			if(!is_array($permisos))
				$permisos = array($permisos);

			foreach ($permisos as $val)
			{
				if(strpos($val,'~')===0)
				{
					//Se indic� un permiso negativo. '~RELOJ' indica que requiere cualquier permiso que no sea RELOJ.
					$params_array['codigo_permiso'] = substr($val,1);
					$p = $this->usuario->permisos->Find('FindPermiso', $params_array);

					if ($p->fields['permitido'])
					{
						$default_permitido = false; //Ya que el usuario era RELOJ, no puede ver la p�gina.
						//Sin embargo, todavia puede pasar por ($permitido = true) si tiene un permiso positivo que haya sido requerido.
						break;
					}
				}
				else
				{
					//Permisos positivos. Tener cualquier permiso de estos le dar� acceso al usuario ($permitido)
					$params_array['codigo_permiso'] = $val;
					$p = $this->usuario->permisos->Find('FindPermiso', $params_array);

					if ($p->fields['permitido'])
					{
						$permitido = true;
						break;
					}
					else
						$default_permitido = false; //Se requiri� al menos un permiso que el usuario no tenia.
				}
			}

			//Pudo haber pasado por tener un permiso positivo ($permitido), o no haber fallado ningun permiso -positivo o negativo- ($default_permitido.)
			if($permitido || $default_permitido)
			{
				//ACCESO PERMITIDO
			}
			else
			{
				//Antes haciamos log off del usuario. Ahora solo lo redirigimos al index. (goto_index es revisado en Pagina.)
				//$this->error_msg = "No tienes permisos para acceder a este recurso.";
				$this->goto_index = true;
				//return false;
			}
		}

		$_SESSION['LAST_ACTION'] = time();
		$this->logged = true;
		$this->error_msg = '';

		return true;
	}

	function VerificarPassword($rut, $password) {
		$query = "SELECT password FROM " . $this->tbl_usuario . " WHERE rut='$rut'";
		$resp = mysql_query($query, $this->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->dbh);
		$pass_db = mysql_fetch_array($resp);
		if ($pass_db['password'] == md5($password)) {
			return true;
		}
		return false;
	}

	function Login($rut, $dvrut, $password, $recordar = false, $desde = "", $use_ad = false, $auth_token = '') {
		$this->Logout();

		if (!Conf::GetConf($this, 'LoginDesdeSitio')) {

			if (strtolower(Conf::GetConf($this, 'NombreIdentificador')) == 'rut') {
				$rut = Utiles::LimpiarRut($rut);
				if (!Utiles::ValidarRut($rut, $dvrut)) {
					$this->error_msg = "RUT $rut-$dvrut no v�lido";
					$_SESSION['ERROR'] = $this->error_msg;
					return false;
				}
			}
		}

		$this->usuario = new UsuarioExt($this, $rut);

		// S� se usa el Windows-Login en Active Directory con ldap
		// el password comparar� en el file login_AD.php
		$ldap_connected = false;
		if (method_exists('Conf', 'Use_WindowsLogin') && $use_ad == true) {
			if (Conf::Use_WindowsLogin()) {
				$ldap_connected = $this->connect2ldap($this->usuario->fields['ldap_user'], $password);
				if (!$ldap_connected) {
					return false;
				}
			}
		}

		if ($this->usuario->fields['password'] == md5($password) || $ldap_connected == true) {
			$this->usuario->Login();

			$_SESSION['APP'] = Conf::AppName();
			$_SESSION['IP'] = $_SERVER['REMOTE_ADDR'];
			$_SESSION['RUT'] = $rut;
			$_SESSION['LOGIN_TIME'] = time();
			$_SESSION['LAST_ACTION'] = time();
			$_SESSION['ULTIMO_INGRESO'] = $this->usuario->fields['ultimo_ingreso'];
			$_SESSION['ACTIVO'] = $this->usuario->fields['activo'];

			$_SESSION['ACTIVO_JUICIO'] = $this->usuario->fields['activo_juicio'];

			if (empty($auth_token)) {
				$UserToken = new \UserToken($this);
				$auth_token = $UserToken->makeAuthToken($this->usuario->fields['rut']);

				$user_token_data = array(
					'user_id' => $this->usuario->fields['id_usuario'],
					'auth_token' => $auth_token,
					'app_key' => 'ttb'
				);

				$UserToken->save($user_token_data);
			}

			$_SESSION['AUTHTOKEN'] = $auth_token;

			/*
			  Seteando cookie
			 */
			if (isset($recordar)) {
				setcookie("ttbrut", $rut, time() + 60 * 60 * 24 * 100, "/");
				setcookie("ttbdv", $dvrut, time() + 60 * 60 * 24 * 100, "/");
				setcookie("ttbpass", $this->encrypt($password), time() + 60 * 60 * 24 * 100, "/");
				setcookie("lmjuiciosauthtoken", $auth_token, time() + 60 * 60 * 24 * 100, "/");
			}

			$this->logged = true;
			return true;
		}

		$this->error_msg = "RUT o password inv�lidos. $rut";
		$_SESSION['ERROR'] = $this->error_msg;

		return false;
	}

	/* Se envia $salir para saber si se ha hecho clic en el enlace "salir" de la aplicaci�n */

	function Logout($salir = false) {
		if ($salir) {
			setcookie("ttbrut", '', time() + 60 * 60 * 24 * 100, "/");
			setcookie("ttbdv", '', time() + 60 * 60 * 24 * 100, "/");
			setcookie("ttbpass", '', time() + 60 * 60 * 24 * 100, "/");
			setcookie("lmjuiciosauthtoken", '', time() + 60 * 60 * 24 * 100, "/");
		}

		$_SESSION = array();
		if ($this->error_msg) {
			$_SESSION['ERROR'] = $this->error_msg;
		}

		$this->logged = false;
	}

	/*
	  Chequea si est� logeado o creadas las cookies para setearlas.
	 */

	/*
	  Chequea si est� logeado o creadas las cookies para setearlas.
	 */

	function CheckLogin() {
		if ($this->logged || isset($_COOKIE['ttbrut']) && isset($_COOKIE['ttbpass'])) {
			$rut = $_COOKIE['ttbrut'];
			$dvrut = $_COOKIE['ttbdv'];
			$password = $this->decrypt($_COOKIE['ttbpass']);
			$auth_token = $_COOKIE['lmjuiciosauthtoken'];
			if ($this->logged || $this->Login($rut, $dvrut, $password, true, '', false, $auth_token)) {
				if (file_exists(Conf::ServerDir() . '/usuarios/index.php')) {
					$pagina_inicio = Conf::RootDir() . "/app/usuarios/index.php";
				} else {
					$pagina_inicio = Conf::RootDir() . '/fw/usuarios/index.php';
				}
				header("Location: $pagina_inicio");
				return true;
			}
		}
		return false;
	}

	function connect2ldap($username, $password) {

		/**
		 * Manage Support for Active Directory Login with LDAP
		 *
		 * Need to be sure that LDAP-Support is enabled in php
		 * using XAMPP, comment following out: extension=php_ldap.dll
		 *
		 */
		if (!method_exists('Conf', 'Use_WindowsLogin')) {
			return false;
		}

		if (!Conf::Use_WindowsLogin()) {
			return false;
		}

		if ($username == "") {
			$this->error_msg = "Usario inv�lido.";
			$_SESSION['ERROR'] = $this->error_msg;
			return false;
		}

		if ($username != "" && $password != "") {
			$ldaprdn = $username; // username
			$ldappass = $password; // password
			// Mapping from conf.php file
			if (method_exists('Conf', 'Ldapport')) {
				$ldapport = Conf::Ldapport();
			} else {
				$ldapport = 389;  // Standard port for ldap
			}

			if (method_exists('Conf', 'AdServer')) {
				$adServer = Conf::AdServer();
			}

			if (method_exists('Conf', 'Domain')) {
				$domain = Conf::Domain();
			}

			if ($domain != "") {
				$ldaprdn = $username . "@" . $domain;
			}

			// Connect, if using OpenLDAP connection will be established at bind
			$ldapconn = ldap_connect($adServer, $ldapport)
				or die("Could not connect to $adServer");

			// Diese Parameter sind n�tig f�r den Zugriff auf ein Active Directory:
			ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);

			if ($ldapconn) {
				// bind
				$ldapbind = ldap_bind($ldapconn, $ldaprdn, $ldappass);
				if ($ldapbind) {
					return true;  // if connected & binded
				} else {
					$this->error_msg = ldap_error($ldapconn) . ", IP: " . $adServer;
					$_SESSION['ERROR'] = $this->error_msg;
					return false;
				}
			}
		} else {
			$this->error_msg = "Password inv�lido.";
			$_SESSION['ERROR'] = $this->error_msg;
			return false;
		}
	}
	  function PhpConsole($errorlevel=7) {
		if ($_SESSION['RUT']=='99511620') PhpConsole::start(true, true, null, $errorlevel);
	}

	function debug($mensaje, $tags = 'debug') {
		if ($_SESSION['RUT'] == '99511620')
			PhpConsole::debug($mensaje, $tags);
	}

	/**
	 * Encripta un string
	 * @param type $data
	 * @return string
	 */
	public function encrypt($text) {
		$crypt = $this->getCryptData();
		mcrypt_generic_init($crypt['cipher'], $crypt['key'], $crypt['iv']);
		$encrypted = mcrypt_generic($crypt['cipher'], $text);
		mcrypt_generic_deinit($crypt['cipher']);
		return base64_encode($encrypted);
	}

	/**
	 * Desencripta un string
	 * @param type $data
	 * @return string
	 */
	public function decrypt($text) {
		$crypt = $this->getCryptData();
		mcrypt_generic_init($crypt['cipher'], $crypt['key'], $crypt['iv']);
		$decrypted = mdecrypt_generic($crypt['cipher'], base64_decode($text));
		mcrypt_generic_deinit($crypt['cipher']);
		return trim($decrypted);
	}

	/**
	 * Obtiene los par�matros de encrttaci�n y desencriptaci�n
	 */
	protected function getCryptData() {
		$cipher = mcrypt_module_open(MCRYPT_BLOWFISH, '', MCRYPT_MODE_CBC, '');
		$key = md5('lemontech-99511620');
		$iv_size = mcrypt_get_block_size($cipher, MCRYPT_MODE_CBC);
		$iv = substr($key, 0, $iv_size);
		return compact('cipher', 'key', 'iv');
	}

	protected function loadConfig() {
		if ($this->has('array_conf')) {
			return Conf::loadFromArray($this->read('array_conf'));
		}
		if (!isset($this->pdodbh)) {
			return false;
		}
		$array_conf = [];
		$query = "SELECT glosa_opcion AS glosa, valor_opcion AS valor FROM configuracion";
		$bd_configs = $this->pdodbh->query($query)->fetchAll(PDO::FETCH_ASSOC);
		foreach ($bd_configs as $conf) {
			$array_conf[$conf['glosa']] = $conf['valor'];
		}
		$this->write('array_conf', $array_conf);
		Conf::loadFromArray($array_conf);
		/**
		 * @TODO: Implementar memcache sin usar una variable global
		// 4.2) Si existe memcache, fijo la llave usando lo obtenido en 4.1
		if (self::$has_memcache) {
			$memcache->set(self::dbName() . '_config', json_encode(UtilesApp::utf8izar(self::$array_conf[$conf])), false, 120);
		}
		 */
	}

	/**
	 * Lee un valor  desde $_SESSION
	 * @param $key
	 * @return null
	 */
	public function read($key) {
		return $this->has($key) ? $_SESSION[$key] : null;
	}

	/**
	 * Escribe un valor en $_SESSION
	 * @param type $key
	 * @param type $value
	 */
	public function write($key, $value) {
		$_SESSION[$key] = $value;
	}


	/**
	 * Verifica existencia de un valor en $_SESSION
	 * @param $key
	 * @return bool
	 */
	public function has($key) {
		return isset($_SESSION[$key]) && !is_null($_SESSION[$key]);
	}

	/**
	 * Elimina un valor desde $_SESSION
	 * @param type $key
	 */
	public function drop($key) {
		unset($_SESSION[$key]);
	}

}
