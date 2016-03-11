<?php

/**
 * Clase GeneracionMasivaCobros
 * Realiza las llamadas de generación masiva de cobros en background.
 * @property BloqueoProceso $BloqueoProceso
 * @property CobroQuery $CobroQuery
 */
class GeneracionMasivaCobros extends AppShell {

	private $status = array(
			'proceso' => '',
			'hh' => '',
			'gg' => '',
			'mixtas' => '',
			'error' => '',
			'mensajes' => ''
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
	private $messages = array(
			'hh' => array(),
			'gg' => array(),
			'mixtas' => array()
	);

	private $with_error = array(
		'hh' => array(),
		'gg' => array(),
		'mixtas' => array()
	);

	private $NewRelic;

	public function __construct()
	{
		parent::__construct();
		$this->NewRelic = new NewRelic(Cobro::PROCESS_NAME);
	}

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
			$this->status('error', __('Ocurrio un error inesperado'));
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
	}

	private function reconectDb() {
		$this->log('Intentando reconectar a la BD.');
		while (!$this->Session->dbconnect(false)) {
			sleep(1);
			$this->reconectDb();
		}
	}

	/**
	 * Procesa las liquidaciones por contrato
	 * @param $contratos
	 */
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
				$msg_procesando = $this->sp($processing, __('1 contrato'), "{$processing} " . __('contratos'));
				$this->status('proceso', __('Procesando') . " {$msg_procesando} " . __('de') . " {$total_contratos}.");
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
		$msg_procesando = $this->sp($processing, __('Se ha procesado 1 contrato de') . " {$total_contratos}", __('Se han procesado') . " {$processing} " . __('contratos de') . " {$total_contratos}");
		$this->status('proceso', "{$msg_procesando}.");
	}

	/**
	 * Procesa las liquidaciones por cliente
	 * @param $contratos
	 */
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
				$msg_procesando = $this->sp($processing, __('1 cliente'), "{$processing} ". __('clientes'));
				$this->status('proceso', __('Procesando') . " {$msg_procesando} " . __('de') . " {$total_clientes}.");
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
		$msg_procesando = $this->sp($processing, __('Se ha procesado 1 cliente'), __('Se han procesado') . " {$processing}");
		$this->status('proceso', "{$msg_procesando} " . __('de') . " {$total_clientes}.");
	}

	/**
	 * Genera liquidaciónes de gastos
	 * @param $id_contrato
	 */
	private function generaGG($id_contrato) {
		try {
			$datos_cobro = array(
					'id_contrato' => $id_contrato,
					'incluye_honorarios' => 0,
					'incluye_gastos' => 1
			);
			$detalle_asunto = $this->getMatterFromAgreement($id_contrato);
			$post_data = array_merge($this->data['form'], $datos_cobro);
			$result = $this->post($post_data);
			if (!empty($result['cobro'])) {
				$this->generated['gg'] += 1;
			}
			if (!empty($result['mensajes'])) {
				array_push($this->messages['gg'] ,$result['mensajes']);
				++$this->errors['gg'];
				$this->with_error['gg'][$this->getClientFromAgreement($id_contrato)] ++;
			}
		} catch (Exception $e) {
			$this->log('Error generaGG: ' . $e->getMessage());
			$this->NewRelic->addMessage("[Generando Liquidaciones Gastos] Error Procesando Contrato : $id_contrato");
			$this->NewRelic->addMessage("Asunto : {$detalle_asunto}");
			$this->NewRelic->addMessage($e->getMessage());
			$this->NewRelic->notice();
			++$this->errors['gg'];
			array_push($this->messages['gg'], "{$detalle_asunto}");
			$this->with_error['gg'][$this->getClientFromAgreement($id_contrato)] ++;
			$mensajes = $this->getMessage();
			if ($this->errors['gg'] > 10) {
				$this->status('mensajes', '<span style="color:red">Ocurrió un error, favor procesar nuevamente</span>');
			} else {
				$this->status('mensajes', "<span style=\"color:red\">Ocurrió un error al procesar algunos asuntos : {$mensajes}</span>");
			}
		}$messages = $this->generateMessage('gg', 'gastos');
		$this->status('gg', "<strong>{$messages['message']}. ({$messages['error']})</strong>");
	}

	/**
	 * Genera liquidaciones de honorarios
	 * @param $id_contrato
	 */
	private function generaHH($id_contrato) {
		try {
			$datos_cobro = array(
					'id_contrato' => $id_contrato,
					'incluye_honorarios' => 1,
					'incluye_gastos' => 0
			);
			$detalle_asunto = $this->getMatterFromAgreement($id_contrato);
			$post_data = array_merge($this->data['form'], $datos_cobro);
			$result = $this->post($post_data);
			if (!empty($result['cobro'])) {
				$this->generated['hh'] += 1;
			}
			if (!empty($result['mensajes'])) {
				array_push($this->messages['hh'] ,$result['mensajes']);
				array_push($this->with_error['hh'], $this->getClientFromAgreement($id_contrato));
				++$this->errors['hh'];
			}
		} catch (Exception $e) {
			$this->log('Error generaHH: ' . $e->getMessage());
			$this->NewRelic->addMessage("[Generando Liquidaciones Honorarios] Error Procesando Contrato : $id_contrato");
			$this->NewRelic->addMessage("Asunto : {$detalle_asunto}");
			$this->NewRelic->addMessage($e->getMessage());
			$this->NewRelic->notice();
			++$this->errors['hh'];
			array_push($this->messages['hh'], "{$detalle_asunto}");
			$this->with_error['hh'][$this->getClientFromAgreement($id_contrato)] ++;
			$mensajes = $this->getMessage();
			if ($this->errors['hh'] > 10) {
				$this->status('mensajes', '<span style="color:red">Ocurrió un error, favor procesar nuevamente</span>');
			} else {
				$this->status('mensajes', "<span style=\"color:red\">Ocurrió un error al procesar algunos asuntos : {$mensajes}</span>");
			}
		}
		$messages = $this->generateMessage('hh', 'honorarios');
		$this->status('hh', "<strong>{$messages['message']}. ({$messages['error']})</strong>");
	}

	/**
	 * Genera liquidaciones mixtas
	 * @param $id_contrato
	 */
	private function generaMIXTAS($id_contrato) {
		try {
			$datos_cobro = array(
					'id_contrato' => $id_contrato,
					'incluye_honorarios' => 1,
					'incluye_gastos' => 1,
			);
			$detalle_asunto = $this->getMatterFromAgreement($id_contrato);
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
			if (!empty($result['mensajes'])) {
				array_push($this->messages['mixtas'] ,$result['mensajes']);
				array_push($this->with_error['mixtas'], $this->getClientFromAgreement($id_contrato));
				++$this->errors['mixtas'];
			}
		} catch (Exception $e) {
			$this->log('Error generaMIXTAS: ' . $e->getMessage());
			$this->NewRelic->addMessage("[Generando Liquidaciones Mixtas] Error Procesando Contrato : $id_contrato");
			$this->NewRelic->addMessage("Asunto : {$detalle_asunto}");
			$this->NewRelic->addMessage($e->getMessage());
			$this->NewRelic->notice();
			++$this->errors['mixtas'];
			array_push($this->messages['mixtas'], "{$detalle_asunto}");
			$this->with_error['mixtas'][$this->getClientFromAgreement($id_contrato)] ++;
			$mensajes = $this->getMessage();
			if ($this->errors['mixtas'] > 10) {
				$this->status('mensajes', '<span style="color:red">Ocurrió un error, favor procesar nuevamente</span>');
			} else {
				$this->status('mensajes', "<span style=\"color:red\">Ocurrió un error al procesar algunos asuntos : {$mensajes}</span>");
			}
		}
		$messages = $this->generateMessage('mixtas', 'mixtas');
		$this->status('mixtas', "<strong>{$messages['message']}. ({$messages['error']})</strong>");
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
			$this->status['error'] = __('Ocurrio un error inesperado.');
			++$this->errors[$type];
		} catch (Exception $e) {
			$this->log('ERROR: ' . $e->getMessage() . ' ' . $e->getFile() . ' (' . $e->getLine() . ').');
			$this->status['error'] = __('Ocurrio un error inesperado.');
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
	 * Envía una llamada POST a una URL.
	 * @param mixed $post_data
	 * @return bool
	 * @throws Exception
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

			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
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
			$this->log($body);
			if ($http_code == 200) {
				return json_decode(trim($body), true);
			}
		} catch (Exception $e) {
			$this->log('Ocurrio un error en el POST REQUEST');
			$this->log($e->getMessage());
			$this->log($response);
			$this->NewRelic->addMessage($e->getMessage());
			$this->NewRelic->addMessage($post_data);
			$this->NewRelic->notice();
		}
		throw new Exception("RESPONSE: {$response} \n POST DATA:{$post_data}");
		$this->log(Cobro::PROCESS_NAME);
		$this->log('El POST REQUEST no responde 200');
		$this->log("POST DATA:{$post_data} \n RESPONSE: {$response}");
	}

	/**
	 * Escribe en el archivo log.
	 * @param type $value
	 */
	private function log($value) {
		Log::write($value, Cobro::PROCESS_NAME);
	}

	/**
	 * Devuelve mensaje singular o plural según el valor.
	 * @param numeric $valor
	 * @param string $singular mensaje que devuelve si el valor es igual a 1
	 * @param string $plural mensaje que devuelve si el valor es distinto a 1
	 * @param string $cero @opcional mensaje que devuelve si el valor es cero, si no viene utiliza el mensaje plural.
	 * @return string
	 */
	private function sp($valor, $singular, $plural, $cero = '') {
		if (empty($cero) && $valor == 0) {
			$valor = 2;
		}
		return $valor == 0 ? $cero : ($valor == 1 ? $singular : $plural);
	}

	/**
	 * Obtiene los mensajes
	 * @return string mensaje
	 */
	private function getMessage() {
		$output = '';
		foreach ($this->messages as $message) {
			$output .= implode('<br/>', $message);
		}
		return $output;
	}

	private function getClientFromAgreement($id_contrato) {
		$contrato = new Contrato($this->Session);
		$contrato->Load($id_contrato);
		return $contrato->fields['codigo_cliente'];
	}

	private function getMatterFromAgreement($id_contrato) {
		$criteria = new Criteria($this->Session);
		$matter = $criteria->add_select('asunto.codigo_asunto')
			->add_select('cliente.glosa_cliente')
			->add_select('asunto.glosa_asunto')
			->add_from('asunto')
			->add_left_join_with('cliente', 'cliente.codigo_cliente = asunto.codigo_cliente')
			->add_restriction(CriteriaRestriction::equals('asunto.id_contrato', $id_contrato))
			->add_limit(1)
			->run();
		$detail = "{$matter[0]['codigo_asunto']}:{$matter[0]['glosa_cliente']} - {$matter[0]['glosa_asunto']}";
		return $detail;
	}

	private function generateMessage($key, $name) {
		$msg_generado = $this->sp(
			$this->generated[$key],
			__("Se ha generado 1 liquidación {$name}"),
			__('Se han generado') . " {$this->generated[$key]} " . __("liquidaciones {$name}"),
			__("No se han generado liquidaciones {$name}"));

		$msg_error = $this->sp(
			$this->errors[$key],
			__('1 con error'),
			"{$this->errors[$key]} " . __('con errores'),
			__('sin errores'));

		return array('message' => $msg_generado, 'error' => $msg_error);
	}

}
