<?php

/**
 * Clase GeneracionMasivaCobros
 * Realiza las llamadas de generaci�n masiva de cobros en background.
 */
class GeneracionMasivaCobros extends AppShell {

	private $status = array(
		'proceso' => '',
		'hh' => '',
		'gg' => '',
		'mixtas' => '',
		'error' => ''
	);
	private $generated = array(
		'hh' => 0,
		'gg' => 0,
		'mixtas' => 0
	);
	private $errors = array(
		'hh' => 0,
		'gg' => 0,
		'mixtas' => 0
	);

	public function main() {
		$this->Session->usuario = new Usuario($this->Session);
		$this->Session->usuario->LoadId($this->data['user_id']);
		$this->loadModel('BloqueoProceso');
		$this->BloqueoProceso->lock(Cobro::PROCESS_NAME, '', json_encode($this->data['form']));
		try {
			$this->loadModel('CobroQuery');
			$query = $this->CobroQuery->genera_cobros($this->data['form']) . ' ORDER BY cliente.id_cliente';
			$contratos = $this->Session->pdodbh->query($query)->fetchAll(PDO::FETCH_ASSOC);
			if (Configure::read('TipoGeneracionMasiva') == 'contrato') {
				$this->contracts($contratos);
			} else {
				$this->clients($contratos);
			}
		} catch (Exception $e) {
			$this->status('error', 'Ocurrio un error inesperado');
		}
		$this->unlookProcess();
	}

	private function unlookProcess() {
		try {
			$this->BloqueoProceso->unlock(Cobro::PROCESS_NAME);
			$this->log('Proceso desbloqueado.');
		} catch (Exception $e) {
			$this->log('Tratando de desbloquear el proceso');
			if (preg_match('/SQLSTATE\[HY000\]/', $e->getMessage())) {
				$this->reconectDb();
			}
			$this->status('error', '<strong>Ocurrio un error inesperado.</strong>');
			$this->unlookProcess();
		}
//		try {
//			$Criteria = $this->loadModel('Criteria', null, true);
//			$result = $Criteria
//				->add_from('usuario')
//				->add_select('nombre')
//				->add_select('email')
//				->add_restriction(CriteriaRestriction::equals('id_usuario', $this->data['user_id']))
//				->run();
//			if (empty($result)) {
//				throw new Exception("No se pudo cargar el usuario {$this->data['user_id']}");
//			}
//			$usuario = $result[0];
//			$subject = __('Generaci�n de') . ' ' . __('Cobros') . ' ' . __('finalizada');
//			$messaje = __('Estimado') .
//						" {$usuario['nombre']}:\n\n" .
//						__('El proceso a finalizado con el siguiente resultado') .
//						":\n\n{$this->statusText()}\n\n--\nThe Time Billing";
//			\TTB\Utiles::InsertarPlus($this->Session, $subject, $messaje, $usuario['email'], $usuario['nombre'], false, $this->data['user_id'], 'proceso');
//		} catch (Exception $e) {
//			$this->log('ERROR al generar correo: ' . $e->getMessage() . ' ' . $e->getFile() . ' (' . $e->getLine() . ').');
//		}
	}

	private function reconectDb() {
		$this->log('Intentando reconectar a la BD.');
		while (!$this->Session->dbconnect(false)) {
			sleep(1);
			$this->reconectDb();
		}
	}

	private function contracts($contratos) {
		$processing = 0;
		// quitar contratos HITOS
		foreach ($contratos as $key => $contrato) {
			if ($contrato['forma_cobro'] == 'HITOS') {
				$contratos[$key] = null;
				continue;
			}
		}
		$contratos = array_filter($contratos);
		$total_contratos = count($contratos);
		foreach ($contratos as $contrato) {
			try {
				++$processing;
				$msg_procesando = $this->sp($processing, "1 contrato", "{$processing} contratos");
				$this->status('proceso', "Procesando $msg_procesando de {$total_contratos}.");
				if ($contrato['separar_liquidaciones']) {
					$this->generaHH($contrato['id_contrato']);
					$this->generaGG($contrato['id_contrato']);
				} else {
					$this->generaMIXTAS($contrato['id_contrato']);
				}
			} catch (Exception $e) {
				$this->log('Error contracts: ' . $e->getMessage());
			}
			$this->log(' |- Uso de memoria ' . \TTB\Utiles::_h(memory_get_usage()) . ', sistema ' . \TTB\Utiles::_h(memory_get_usage(1)));
		}
		$msg_procesando = $this->sp($processing, "Se ha procesado 1 contrato de {$total_contratos}", "Se han procesado {$processing} contratos de {$total_contratos}");
		$this->status('proceso', "$msg_procesando.");
	}

	private function clients($contratos) {
		$processing = 0;
		$clientes = array();
		// contar clientes
		foreach ($contratos as $contrato) {
			if ($contrato['forma_cobro'] == 'HITOS') {
				continue;
			}
			$codigo_cliente = $contrato['codigo_cliente'];
			if (!isset($clientes[$codigo_cliente])) {
				$clientes[$codigo_cliente] = array();
			}
			$clientes[$codigo_cliente][] = $contrato;
		}
		unset($contratos);
		$total_clientes = count($clientes);
		foreach ($clientes as $codigo_cliente => $contratos) {
			try {
				++$processing;
				$msg_procesando = $this->sp($processing, '1 cliente', "{$processing} clientes");
				$this->status('proceso', "Procesando $msg_procesando de {$total_clientes}.");
				foreach ($contratos as $contrato) {
					if ($contrato['separar_liquidaciones']) {
						$this->generaHH($contrato['id_contrato']);
						$this->generaGG($contrato['id_contrato']);
					} else {
						$this->generaMIXTAS($contrato['id_contrato']);
					}
					$this->log(' |- Uso de memoria ' . \TTB\Utiles::_h(memory_get_usage()) . ', sistema ' . \TTB\Utiles::_h(memory_get_usage(1)));
				}
			} catch (Exception $e) {
				$this->log('Error clients: ' . $e->getMessage());
			}
		}
		$msg_procesando = $this->sp($processing, 'ha procesado 1 cliente', "han procesado {$processing}");
		$this->status('proceso', "Se $msg_procesando de {$total_clientes}.");
	}

	private function generaGG($id_contrato) {
		try {
			$datos_cobro = array(
				'id_contrato' => $id_contrato,
				'incluye_honorarios' => 0,
				'incluye_gastos' => 1
			);
			$post_data = array_merge($this->data['form'], $datos_cobro);
			$result = $this->post($post_data);
			if (!empty($result['cobro'])) {
				$this->generated['gg'] += empty($result['cobro']) ? 0 : 1;
			}
		} catch (Exception $e) {
			$this->log('Error generaGG: ' . $e->getMessage());
			++$this->errors['gg'];
		}
		$msg_generado = $this->sp(
			$this->generated['gg'], 'Se ha generado 1 liquidaci�n de gastos', "Se han generado {$this->generated['gg']} liquidaciones de gastos", 'No se han generado liquidaciones de gastos'
		);
		$msg_error = $this->sp($this->errors['gg'], '1 con error', "{$this->errors['gg']} con errores", 'sin errores');
		$this->status('gg', "$msg_generado. ($msg_error)");
	}

	private function generaHH($id_contrato) {
		try {
			$datos_cobro = array(
				'id_contrato' => $id_contrato,
				'incluye_honorarios' => 1,
				'incluye_gastos' => 0
			);
			$post_data = array_merge($this->data['form'], $datos_cobro);
			$result = $this->post($post_data);
			if (!empty($result['cobro'])) {
				$this->generated['hh'] += empty($result['cobro']) ? 0 : 1;
			}
		} catch (Exception $e) {
			$this->log('Error generaHH: ' . $e->getMessage());
			++$this->errors['hh'];
		}
		$msg_generado = $this->sp(
			$this->generated['hh'], 'Se ha generado 1 liquidaci�n de honorarios', "Se han generado {$this->generated['hh']} liquidaciones de honorarios", 'No se han generado liquidaciones de honorarios'
		);
		$msg_error = $this->sp($this->errors['hh'], '1 con error', "{$this->errors['hh']} con errores", 'sin errores');
		$this->status('hh', "$msg_generado. ($msg_error)");
	}

	private function generaMIXTAS($id_contrato) {
		try {
			$datos_cobro = array(
				'id_contrato' => $id_contrato,
				'incluye_honorarios' => 1,
				'incluye_gastos' => 1,
			);
			if ($this->data['solo'] == 'honorarios') {
				$datos_cobro = array_merge($datos_cobro, array('incluye_honorarios' => 1, 'incluye_gastos' => 0));
			} else if ($this->data['solo'] == 'gastos') {
				$datos_cobro = array_merge($datos_cobro, array('incluye_honorarios' => 0, 'incluye_gastos' => 1));
			}

			$post_data = array_merge($this->data['form'], $datos_cobro);
			$result = $this->post($post_data);
			if (!empty($result['cobro'])) {
				$this->generated['mixtas'] += empty($result['cobro']) ? 0 : 1;
			}
		} catch (Exception $e) {
			$this->log('Error generaMIXTAS: ' . $e->getMessage());
			++$this->errors['mixtas'];
		}
		$msg_generado = $this->sp(
			$this->generated['mixtas'], 'Se ha generado 1 liquidaci�n mixta', "Se han generado {$this->generated['mixtas']} liquidaciones mixtas", 'No se han generado liquidaciones mixtas'
		);
		$msg_error = $this->sp($this->errors['mixtas'], '1 con error', "{$this->errors['mixtas']} con errores", 'sin errores');
		$this->status('mixtas', "$msg_generado. ($msg_error)");
	}

	/**
	 * Escribe el estado del proceso actual.
	 * @param type $type
	 * @param type $status
	 */
	private function status($type, $status) {
		$this->status[$type] = $status;
		try {
			$this->BloqueoProceso->updateStatus(Cobro::PROCESS_NAME, $this->statusText());
		} catch (PDOException $e) {
			$this->log('ERROR: ' . $e->getMessage() . ' ' . $e->getFile() . ' (' . $e->getLine() . ').');
			$this->status['error'] = 'Ocurrio un error inesperado.';
			++$this->errors[$type];
		} catch (Exception $e) {
			$this->log('ERROR: ' . $e->getMessage() . ' ' . $e->getFile() . ' (' . $e->getLine() . ').');
			$this->status['error'] = 'Ocurrio un error inesperado.';
			++$this->errors[$type];
		}
	}

	/**
	 * Genera un texto del array de status.
	 * @return string
	 */
	private function statusText() {
		return implode('<br/>', array_filter($this->status));
	}

	/**
	 * Env�a una llamada POST a una URL.
	 * @param type $post_data
	 * @return boolean
	 */
	private function post($post_data) {
		try {
			$url = Conf::Server() . Conf::RootDir() . '/app/interfaces/genera_cobros_guarda.php';
			if (is_array($post_data)) {
				$post_data = implode('&', UtilesApp::mergeKeyValue($post_data, '%s=%s'));
			}
			$url = preg_replace('/^https:\/\//', 'http://', $url);
			$get_data = 'generar_silenciosamente=1';
			$post_data .= "&individual=1&autologin=1&id_usuario_login={$this->data['user_id']}&hash=" . Conf::hash();
			$this->log("{$url}?{$get_data} --post {$post_data}");
			$ch = curl_init();

			curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, 'GET');
			curl_setopt($ch, CURLOPT_URL, "{$url}?{$get_data}");
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			$response = curl_exec($ch);

			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$header = substr($response, 0, $header_size);
			$body = substr($response, $header_size);
			curl_close($ch);

			if ($http_code == 200) {
				return json_decode(trim($body), true);
			}
		} catch (Exception $e) {
			$response .= $e->getMessage();
		}
		$this->log('Ocurrio un error en la llamada post.');
		$this->log($response);
		throw new Exception('Ocurrio un error en la llamada post.');
	}

	/**
	 * Escribe en el archivo log.
	 * @param type $value
	 */
	private function log($value) {
		Log::write($value, Cobro::PROCESS_NAME);
	}

	/**
	 * Devuelve mensaje singular o plural seg�n el valor.
	 * @param numeric $valor
	 * @param string $singular mensaje que devuelve si el valor es igual a 1
	 * @param string $plural mensaje que devuelve si el valor es distinto a 1
	 * @param string $cero @opcional mensaje que devuelve si el valor es cero, si no viene utiliza el mensaje plural.
	 */
	private function sp($valor, $singular, $plural, $cero = '') {
		if (empty($cero) && $valor == 0) {
			$valor = 2;
		}
		return $valor == 0 ? $cero : ($valor == 1 ? $singular : $plural);
	}

}