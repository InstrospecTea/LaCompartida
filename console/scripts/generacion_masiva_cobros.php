<?php

/**
 * Clase GeneracionMasivaCobros
 * Realiza las llamadas de generación masiva de cobros en background.
 */
class GeneracionMasivaCobros extends AppShell {

	private $status = array();

	public function main() {
		$this->Session->usuario = new Usuario($this->Session);
		$this->Session->usuario->LoadId($this->data['user_id']);
		$this->loadModel('BloqueoProceso');
		$this->data['form'] = $this->data['form'];
		$this->BloqueoProceso->lock(Cobro::PROCESS_NAME, '', json_encode($this->data['form']));
		if (isset($this->data['arrayClientes'])) {
			$this->clients();
		} else {
			if (!empty($this->data['arrayHH'])) {
				$this->generaHH();
				$this->generaGG();
			}
			if (!empty($this->data['arrayMIXTAS'])) {
				$this->generaMIXTAS();
			}
		}
		$this->BloqueoProceso->unlock(Cobro::PROCESS_NAME);
	}

	private function clients() {
		try {
			$errores = 0;
			$processing = 0;
			$total_clientes = count($this->data['arrayClientes']);
			$url = Conf::Server() . Conf::RootDir() . '/app/interfaces/genera_cobros_guarda.php';
			$generated = 0;
			foreach ($this->data['arrayClientes'] as $k => $cliente) {
				++$processing;
				$this->status('client', "Procesando $processing de $total_clientes clientes. ({$errores} con errores)");
				$post_data = array_merge($this->data['form'], array('codigo_cliente' => $cliente));
				$result = $this->post($url, $post_data);
				if (count($result['cobros'])) {
					$generated += count($result['cobros']);
				}
			}
		} catch (Exeption $e) {
			++$errores;
		}
		if ($generated) {
			$this->status('client', "Se han generado $generated liquidaciones para $total_clientes clientes. ({$errores} con errores)");
		} else {
			$this->status('client', "No se han generado liquidaciones. ({$errores} errores)");
		}
	}

	private function generaGG() {
		try {
			$errores = 0;
			$processing = 0;
			$total_gg = count($this->data['arrayHH']);
			$url = Conf::Server() . Conf::RootDir() . '/app/interfaces/genera_cobros_guarda.php';
			$generated = 0;
			foreach ($this->data['arrayHH'] as $contrato) {
				++$processing;
				$this->status('gg', "Procesando $processing liquidaciones de gastos. ($errores con errores)");
				$datos_cobro = array(
					'id_contrato' => $contrato[0],
					'fecha_ultimo_cobro' => $contrato[1],
					'incluye_honorarios' => 0,
					'incluye_gastos' => 1
				);
				$post_data = array_merge($this->data['form'], $datos_cobro);
				$result = $this->post($url, $post_data);
				if (count($result['cobros'])) {
					$generated += count($result['cobros']);
				}
			}
		} catch (Exeption $e) {
			++$errores;
		}
		if ($generated) {
			$this->status('gg', "Se han generado $generated liquidaciones de gastos. ({$errores} con errores)");
		} else {
			$this->status('gg', "No se han generado liquidaciones de gastos. ({$errores} errores)");
		}
	}

	private function generaHH() {
		try {
			$errores = 0;
			$processing = 0;
			$total_hh = count($this->data['arrayHH']);
			$url = Conf::Server() . Conf::RootDir() . '/app/interfaces/genera_cobros_guarda.php';
			$generated = 0;
			foreach ($this->data['arrayHH'] as $contrato) {
				++$processing;
				$this->status('hh', "Procesando $processing liquidaciones de honorarios. ($errores con errores)");

				$datos_cobro = array(
					'id_contrato' => $contrato[0],
					'fecha_ultimo_cobro' => empty($contrato[1]) ? '' : $contrato[1],
					'monto' => empty($contrato[2]) ? '' : $contrato[2],
					'incluye_honorarios' => 1,
					'incluye_gastos' => 0
				);
				$post_data = array_merge($this->data['form'], $datos_cobro);
				$result = $this->post($url, $post_data);
				if (count($result['cobros'])) {
					$generated += count($result['cobros']);
				}
			}
		} catch (Exeption $e) {
			++$errores;
		}
		if ($generated) {
			$this->status('hh', "Se han generado $generated liquidaciones de honorarios. ({$errores} con errores)");
		} else {
			$this->status('hh', "No se han generado liquidaciones de honorarios. ({$errores} errores)");
		}
	}

	private function generaMIXTAS() {
		try {
			$errores = 0;
			$processing = 0;
			$total_mixtas = count($this->data['arrayMIXTAS']);
			$url = Conf::Server() . Conf::RootDir() . '/app/interfaces/genera_cobros_guarda.php';
			$generated = 0;
			foreach ($this->data['arrayMIXTAS'] as $contrato) {
				++$processing;
				$this->status('mixtas', "Procesando $processing liquidaciones mixtas. ($errores con errores)");
				$datos_cobro = array(
					'id_contrato' => $contrato[0],
					'fecha_ultimo_cobro' => empty($contrato[1]) ? '' : $contrato[1],
					'monto' => $contrato[2],
					'incluye_honorarios' => 1,
					'incluye_gastos' => 1,
				);
				if ($this->data['solo'] == 'honorarios') {
					$datos_cobro = array_merge($datos_cobro, array('incluye_honorarios' => 1, 'incluye_gastos' => 0));
				} else if ($this->data['solo'] == 'gastos') {
					$datos_cobro = array_merge($datos_cobro, array('incluye_honorarios' => 0, 'incluye_gastos' => 1));
				}

				$post_data = array_merge($this->data['form'], $datos_cobro);
				$result = $this->post($url, $post_data);
				if (count($result['cobros'])) {
					$generated += count($result['cobros']);
				}
			}
		} catch (Exeption $e) {
			++$errores;
		}
		if ($generated) {
			$this->status('mixtas', "Se han generado $generated liquidaciones mixtas. ({$errores} con errores)");
		} else {
			$this->status('mixtas', "No se han generado liquidaciones de mixtas. ({$errores} errores)");
		}
	}

	/**
	 * Escribe el estado del proceso actual.
	 * @param type $type
	 * @param type $status
	 */
	private function status($type, $status) {
		$this->status[$type] = $status;
		$st = implode('<br/>', $this->status);
		$this->BloqueoProceso->updateStatus(Cobro::PROCESS_NAME, $st);
	}

	/**
	 * Envía una llamada POST a una URL.
	 * @param type $url
	 * @param type $post_data
	 * @return boolean
	 */
	private function post($url, $post_data) {
		if (is_array($post_data)) {
			$post_data = implode('&', UtilesApp::mergeKeyValue($post_data, '%s=%s'));
		}
		$url = preg_replace('/^https:\/\//', 'http://', $url);
		$get_data = 'generar_silenciosamente=1';
		$post_data .= "&autologin=1&id_usuario_login={$this->data['user_id']}&hash=" . Conf::hash();
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
		throw new exception('Ocurrio un error en la llamada post.');
	}

	/**
	 * Escribe en el archivo log.
	 * @param type $value
	 */
	private function log($value) {
		Log::write($value, Cobro::PROCESS_NAME);
	}

}
