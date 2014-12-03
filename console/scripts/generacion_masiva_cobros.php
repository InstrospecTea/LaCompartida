<?php

/**
 * Clase GeneracionMasivaCobros
 * Realiza las llamadas de generacion masiva de cobros en backgruound
 */
class GeneracionMasivaCobros extends AppShell {

	private $status = array();

	public function main() {
		$this->Session->usuario = new Usuario($this->Session);
		$this->Session->usuario->LoadId($this->data['user_id']);
		$this->loadModel('BloqueoProceso');
		$this->BloqueoProceso->lock(Cobro::PROCESS_NAME);
		if (isset($this->data['arrayClientes'])) {
			$this->clients();
		} else {
			$this->data['form'] = $this->qs2array($this->data['form']);
			if (!empty($this->data['arrayGG'])) {
				$this->generaGG();
			}
			if (!empty($this->data['arrayHH'])) {
				$this->generaHH();
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
			foreach ($this->data['arrayClientes'] as $k => $cliente) {
				++$processing;
				$this->status('client', "Procesando $processing de $total_clientes clientes. ({$errores} con errores)");
				$post_data = preg_replace('/(codigo_cliente=)[^&]*/', "codigo_cliente={$cliente}", $this->data['form']);
				$ok = $this->post($url, $post_data);
				if (!$ok) {
					++$$errores;
				}
			}
		} catch (Exeption $e) {
			++$errores;
		}
		$this->status('client', "Se han procesado $processing de $total_clientes clientes. ({$errores} con errores)");
	}

	private function generaGG() {
		try {
			$errores = 0;
			$processing = 0;
			$total_gg = count($this->data['arrayGG']);
			$url = Conf::Server() . Conf::RootDir() . '/app/interfaces/genera_cobros_guarda.php';
			foreach ($this->data['arrayGG'] as $contrato) {
				++$processing;
				$this->status('gg', "Procesando $processing de $total_gg liquidaciones gastos. ($errores con errores)");

				$post_data = array_merge($this->data['form'], $contrato);
				$ok = $this->post($url, $post_data);
				if (!$ok) {
					++$errores;
				}
			}
		} catch (Exeption $e) {
			++$errores;
		}
		$this->status('gg', "Se han procesado $processing de $total_gg liquidaciones  de gastos. ({$errores} con errores)");
	}

	private function generaHH() {
		try {
			$errores = 0;
			$processing = 0;
			$total_hh = count($this->data['arrayHH']);
			$get_data = 'generar_silenciosamente=1';
			$url = Conf::Server() . Conf::RootDir() . '/app/interfaces/genera_cobros_guarda.php';
			foreach ($this->data['arrayHH'] as $contrato) {
				++$processing;
				$this->status('hh', "Procesando $processing de $total_hh liquidaciones de honorarios. ($errores con errores)");

				$post_data = array_merge($this->data['form'], $contrato);
				$ok = $this->post($url, $post_data, $get_data);
				if (!$ok) {
					++$errores;
				}
			}
		} catch (Exeption $e) {
			++$errores;
		}
		$this->status('hh', "Se han procesado $processing de $total_hh liquidaciones  de honorarios. ({$errores} con errores)");
	}

	private function generaMIXTAS() {
		try {
			$errores = 0;
			$processing = 0;
			$total_mixtas = count($this->data['arrayMIXTAS']);
			$get_data = 'generar_silenciosamente=1';
			$url = Conf::Server() . Conf::RootDir() . '/app/interfaces/genera_cobros_guarda.php';
			foreach ($this->data['arrayMIXTAS'] as $contrato) {
				++$processing;
				$this->status('mixtas', "Procesando $processing de $total_mixtas liquidaciones mixtas. ($errores con errores)");
				if ($this->data['solo'] == 'honorarios') {
					$contrato = array_merge($contrato, array('incluye_honorarios' => 1, 'incluye_gastos' => 0));
				} else if ($this->data['solo'] == 'gastos') {
					$contrato = array_merge($contrato, array('incluye_honorarios' => 0, 'incluye_gastos' => 1));
				}

				$post_data = array_merge($this->data['form'], $contrato);
				$ok = $this->post($url, $post_data, $get_data);
				if (!$ok) {
					++$errores;
				}
			}
		} catch (Exeption $e) {
			++$errores;
		}
		$this->status('mixtas', "Se han procesado $processing de $total_mixtas liquidaciones mixtas. ({$errores} con errores)");
	}

	private function status($type, $status) {
		$this->status[$type] = $status;
		$st = implode('<br/>', $this->status);
		$this->BloqueoProceso->updateStatus(Cobro::PROCESS_NAME, $st);
	}

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

		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);

		curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);
		return $http_code == 200;
	}

	private function log($value) {
		Log::write($value, Cobro::PROCESS_NAME);
	}

	private function qs2array($qs) {
		$array = array();
		$a = explode('&', $qs);
		foreach ($a as $v) {
			$b = explode('=', $v);
			$array[$b[0]] = $b[1];
		}
		return $array;
	}


}
