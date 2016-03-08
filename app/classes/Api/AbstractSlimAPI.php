<?php

class AbstractSlimAPI  {

	private $session;
	private $slim;

	const MIN_TIMESTAMP = 315532800;
	const MAX_TIMESTAMP = 4182191999;

	public function __construct($session, $slim) {
		$this->session = $session;
		$this->slim = $slim;
	}

	/**
	 * Corta la ejecución de la aplicación y retorna código http
	 * @param  [type]  $error_message [description]
	 * @param  [type]  $error_code    [description]
	 * @param  integer $halt_code     [description]
	 * @param  string  $data          [description]
	 * @return [type]                 [description]
	 */
	public function halt($error_message = null, $error_code = null, $halt_code = 400, $data = '') {
		$errors = array();
		$Slim = $this->slim;
		switch ($halt_code) {
			case 200:
				$Slim->halt(200);
				break;

			default:
				array_push($errors, array('message' => $error_message, 'code' => $error_code));
				$data = UtilesApp::utf8izar(array('errors' => $errors, 'extra_data' => $data));
				$Slim->halt($halt_code, json_encode($data));
				break;
		}
	}

	/**
	 * Entrega la respuesta en formato JSON con los headers adecuados
	 * @param  [type] $response [description]
	 * @return [type]           [description]
	 */
	public function outputJson($response) {
		header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
		header('Cache-Control: no-cache, must-revalidate');
		header('Pragma: no-cache');
		header('Content-type: application/json; charset=utf-8');
		$response = UtilesApp::utf8izar($response);
		array_walk_recursive($response, function(&$x) { if (is_string($x)) $x = trim($x); });
		echo json_encode($response);
		exit;
	}

	/**
	 * Cambia tiempo string en segundos
	 * @param  string $time [description]
	 * @return [type]       [description]
	 */
	public function time2seconds($time = '00:00:00') {
		list($hours, $mins, $secs) = explode(':', $time);
		return ($hours * 3600 ) + ($mins * 60 ) + $secs;
	}

	/**
	 * Valida si un timestamp está dentro de un rango aceptable
	 * @param  [type]  $timestamp [description]
	 * @return boolean            [description]
	 */
	public function isValidTimeStamp($timestamp) {
		return ($timestamp >= self::MIN_TIMESTAMP)
		&& ($timestamp <= self::MAX_TIMESTAMP);
	}

	/**
	 * Descarga un contenido como attachment
	 * @param  [type] $name    [description]
	 * @param  [type] $type    [description]
	 * @param  [type] $content [description]
	 * @return [type]          [description]
	 */
	public function downloadFile($name, $type, $content) {
		header('Content-Transfer-Encoding: binary');
		header("Content-Type: $type");
		header('Content-Description: File Transfer');
		header("Content-Disposition: attachment; filename=$name");
		echo $content;
	}

	/**
	 * [getAppIdByAppKey description]
	 * @param  [type] $app_key [description]
	 * @return [type]          [description]
	 */
	public function getAppIdByAppKey($app_key) {
		$Session = $this->session;
		$UserToken = new UserToken($Session);
		return $UserToken->getAppIdByAppKey($app_key);
	}

	/**
	 * Valida auth token medianto los headers
	 * @param  [type] $permission [description]
	 * @return [type]             [description]
	 */
	public function validateAuthTokenSendByHeaders($permission = null) {
		$Slim = $this->slim;
		$Session = $this->session;

		$UserToken = new UserToken($Session);
		$Request = $Slim->request();
		$auth_token = $Request->headers('AUTHTOKEN');
		$user_token = $UserToken->findByAuthToken($auth_token);

		// if not exist the auth_token then return error
		if (!is_object($user_token)) {
			$this->halt(__('Invalid AUTH TOKEN'), 'SecurityError', 401);
		} else {
			$app_id = $UserToken->getAppIdByAppKey($user_token->app_key);
			$_SESSION['app_id'] = is_null($app_id) ? 1 : $app_id;

			// verify if the token is expired
			// date_default_timezone_set("UTC");
			$now = time();
			$expiry_date = strtotime($user_token->expiry_date);
			if ($expiry_date < $now) {
				if ($UserToken->delete($user_token->id)) {
					$this->halt(__('Expired AUTH TOKEN'), 'SecurityError', 401);
				} else {
					$this->halt(__('Unexpected error deleting data'), 'UnexpectedDelete');
				}
			} else {
				$user_token_data = array(
					'id' => $user_token->id,
					'user_id' => $user_token->user_id,
					'auth_token' => $user_token->auth_token,
					'app_key' => $user_token->app_key
				);
				$UserToken->save($user_token_data);
			}

			$Session->usuario = new UsuarioExt($Session);
			$Session->usuario->LoadId($user_token->user_id);
			if (!is_null($permission)) {
				if (!$Session->usuario->Loaded()) {
					$this->halt(__("The user doesn't exist"), 'UserDoesntExist');
				} else {
					$Session->usuario->LoadPermisos($user_token->user_id);
					$params_array['codigo_permiso'] = $permission;
					$p = $Session->usuario->permisos->Find('FindPermiso', $params_array);
					if (!$p->fields['permitido']) {
						$this->halt(__('Not allowed'), 'SecurityError');
					}
				}
			}

			return $user_token;
		}
	}
}
