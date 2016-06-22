<?php

class AsuntoCargaMasiva extends AsuntoConfig {

	public function PreCrearDato($data) {
		if (empty($data['codigo_cliente']) || $data['codigo_cliente'] == 'NULL') {
			return $this->IngresarAsuntoGenerico($data);
		}

		if (isset($data['codigo_asunto'])) {
			$this->LoadByCodigo($data['codigo_asunto']);
		} else {
			$asunto = null;
			if (!isset($data['id_contrato_cliente'])) {
				$query_contrato = "SELECT id_contrato AS id_contrato_cliente FROM cliente WHERE codigo_cliente = '{$data['codigo_cliente']}'";
				$resp_contrato = $this->sesion->pdodbh->query($query_contrato);
				$contrato = $resp_contrato->fetchAll(PDO::FETCH_ASSOC);
				$data += $contrato[0];

				$query_asunto = "SELECT id_asunto, codigo_asunto, id_contrato FROM asunto WHERE codigo_cliente = '{$data['codigo_cliente']}' AND glosa_asunto = '{$data['glosa_asunto']}'";
				$resp_asunto = $this->sesion->pdodbh->query($query_asunto);
				$asunto = $resp_asunto->fetchAll(PDO::FETCH_ASSOC);
			}

			if (!empty($asunto)) {
				$data += $asunto[0];
			} else {
				$data['codigo_asunto'] = $this->AsignarCodigoAsunto($data['codigo_cliente']);
				$data['id_contrato'] = $data['id_contrato_cliente'];
			}
		}

		if (!empty($data['forma_cobro']) && $data['forma_cobro'] != 'NULL') {
			if (!empty($data['monto_tarifa_flat']) && $data['monto_tarifa_flat'] > 0) {
				$Tarifa = new Tarifa($this->sesion);
				$data['id_tarifa'] = $Tarifa->GuardaTarifaFlat($data['monto_tarifa_flat'], $data['id_moneda']);
			}

			//copio algunos datos a su equivalente en contrato
			if (empty($data['id_moneda']) || $data['id_moneda'] == 'NULL') {
				$data['id_moneda'] = 1;
				$monedas = array('opc_moneda_total', 'id_moneda_monto', 'opc_moneda_gastos', 'id_moneda_tramite');
				foreach ($monedas as $moneda) {
					if (!empty($data[$moneda]) && $data[$moneda] != 'NULL') {
						$data['id_moneda'] = $data[$moneda];
						break;
					}
				}
			}
			$datos_clon = array(
				'id_moneda_tramite' => 'id_moneda',
				'id_moneda_monto' => 'id_moneda',
				'opc_moneda_total' => 'id_moneda',
				'opc_moneda_gastos' => 'id_moneda',
				'id_usuario_responsable' => 'id_encargado'
			);
			foreach ($datos_clon as $nombre_contrato => $nombre_asunto) {
				if (isset($data[$nombre_asunto]) &&
						(!isset($data[$nombre_contrato]) || empty($data[$nombre_contrato]) || $data[$nombre_contrato] == 'NULL')) {
					$data[$nombre_contrato] = $data[$nombre_asunto];
				}
			}

			$this->extra_fields['activo'] = empty($data['activo']) ? 'NO' : 'SI';
		}
		unset($data['monto_tarifa_flat']);

		$campos_contrato = array('id_contrato_cliente', 'id_moneda_monto', 'id_tarifa', 'forma_cobro', 'id_moneda_tramite',
			'id_usuario_responsable', 'id_moneda', 'monto', 'retainer_horas', 'opc_moneda_gastos', 'opc_moneda_total', 'id_cuenta',
			'direccion_contacto');
		$this->editable_fields = array_diff(array_keys($data), $campos_contrato);
		if (!empty($data['contacto'])) {
			$this->extra_fields['contacto'] = $data['contacto'];
		}
		if (!empty($data['email_contacto'])) {
			$this->email_contacto['email_contacto'] = $data['email_contacto'];
		}
		if (!empty($data['fono_contacto'])) {
			$this->extra_fields['fono_contacto'] = $data['fono_contacto'];
		}
		$this->extra_fields['activo'] = empty($data['activo']) ? 'NO' : 'SI';

		return $data;
	}

	public function PostCrearDato() {
		if (empty($this->extra_fields['forma_cobro']) || $this->extra_fields['forma_cobro'] == 'NULL') {
			return;
		}

		//copiar contrato del cliente y agregar datos especificados aca
		$Contrato = new Contrato($this->sesion);
		$Contrato->Load($this->fields['id_contrato']);
		if ($this->fields['id_contrato'] == $this->extra_fields['id_contrato_cliente']) {
			unset($Contrato->fields['id_contrato']);
		}
		unset($this->extra_fields['id_contrato_cliente']);

		$Contrato->editable_fields = array_keys($this->extra_fields);
		$Contrato->Fill($this->extra_fields, true);
		$Contrato->Edit('codigo_cliente', $this->fields['codigo_cliente']);
		if ($Contrato->Write()) {
			$this->Edit('id_contrato', $Contrato->fields['id_contrato']);
			$this->Edit('id_contrato_indep', $Contrato->fields['id_contrato']);
			if (!$this->Write()) {
				throw new Exception('No se pudo asociar el contrato al asunto');
			}
		} else {
			throw new Exception('No se pudo guardar el contrato asociado al asunto');
		}
	}

	private function IngresarAsuntoGenerico($data) {
		unset($data['codigo_cliente']);

		$query_clientes = "SELECT codigo_cliente, id_contrato AS id_contrato_cliente FROM cliente";
		$resp_clientes = $this->sesion->pdodbh->query($query_clientes);
		$clientes = $resp_clientes->fetchAll(PDO::FETCH_ASSOC);

		foreach ($clientes as $cliente) {
			$this->fields = array();
			$this->changes = array();
			$asunto = $this->PreCrearDato($data + $cliente);

			$this->Fill($asunto, true);
			if ($this->Write()) {
				$this->PostCrearDato();
			} else {
				throw new Exception("Error al guardar asunto genérico {$data['glosa_asunto']} en cliente {$cliente['codigo_cliente']}" .
				(empty($this->error) ? '' : ": {$this->error}"));
			}
		}
	}

}
