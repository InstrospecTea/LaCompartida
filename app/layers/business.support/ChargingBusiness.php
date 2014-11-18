<?php

class ChargingBusiness extends AbstractBusiness implements IChargingBusiness {

	/**
	 * Elimina un cobro
	 * @param type $id_cobro
	 * @throw Exceptions
	 */
	public function delete($id_cobro) {
		$this->loadService('Charge');
		$charge = $this->ChargeService->get($id_cobro);
		if (!$charge->isLoaded()) {
			throw new Exception(__('El cobro Nº') . $id_cobro . __(' no existe.'));
		}
		if ($charge->get('estado') != 'CREADO') {
			throw new Exception(__('El cobro Nº') . $id_cobro . __(' no se puede borrar porque tiene un estado distinto a CREADO.'));
		}

		$this->loadModel('Documento');
		$this->Documento->LoadByCobro($id_cobro);
		$lista_pagos = $this->Documento->ListaPagos();
		if (!empty($lista_pagos)) {
			throw new Exception(__('El cobro Nº') . $id_cobro . __(' no se puede borrar porque tiene pago(s) asociados(s).'));
		}

		$query = "SELECT count(*) total FROM factura WHERE id_cobro = '{$id_cobro}'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		$facturas = mysql_fetch_assoc($resp);
		if ($facturas['total'] > 0) {
			throw new Exception(__('El cobro Nº') . $id_cobro . __(' no se puede borrar porque tiene un documento tributario asociado.'));
		}

		mysql_query('BEGIN', $this->sesion->dbh);
		try {
			//Elimina el gasto generado y la provision generada, SOLO si la provision no ha sido incluida en otro cobro:
			if ($this->fields['id_provision_generada']) {
				$provision_generada = new Gasto($this->sesion);
				$gasto_generado = new Gasto($this->sesion);
				$provision_generada->Load($this->fields['id_provision_generada']);

				if ($provision_generada->Loaded()) {
					if (!$provision_generada->fields['id_cobro']) {
						$provision_generada->Eliminar();
						$gasto_generado->Load($this->fields['id_gasto_generado']);
						if ($gasto_generado->Loaded()) {
							$gasto_generado->Eliminar();
						}
					}
				}
			}

			$this->overrideDocument();

			$query = "UPDATE trabajo SET id_cobro = NULL, fecha_cobro= 'NULL', monto_cobrado='NULL' WHERE id_cobro = $id_cobro";
			mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

			$query = "UPDATE tramite SET id_cobro = NULL WHERE id_cobro = $id_cobro";
			mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

			$query = "UPDATE cobro_pendiente SET id_cobro = NULL WHERE id_cobro = $id_cobro";
			mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

			$query = "UPDATE cta_corriente SET id_cobro = NULL WHERE id_cobro = $id_cobro";
			mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

			$CobroAsunto = new CobroAsunto($this->sesion);
			$CobroAsunto->eliminarAsuntos($id_cobro);
			$this->ChargeService->delete($charge);
			mysql_query('COMMIT', $this->sesion->dbh);
		} catch (Exception $e) {
			mysql_query('ROLLBACK', $this->sesion->dbh);
			throw new Exception(__('El cobro Nº') . $id_cobro . __(' no se puede borrar por un error inesperado.'));
		}
	}


	public function overrideDocument($id_cobro = null, $estado = 'CREADO', $hay_pagos = false) {
		if (!$this->Documento->Loaded()) {
			if (empty($id_cobro)) {
				return;
			}
			$this->Documento->LoadByCobro($id_cobro);
		}

		if ($estado == 'INCOBRABLE') {
			$this->Documento->EliminarNeteos();
			$this->Documento->AnularMontos();
		} else if (!$hay_pagos) {
			$this->Documento->EliminarNeteos();
			$query_factura = "UPDATE factura_cobro SET id_documento = NULL WHERE id_documento = '{$this->Documento->fields['id_documento']}'";
			mysql_query($query_factura, $this->sesion->dbh) or Utiles::errorSQL($query_factura, __FILE__, __LINE__, $this->sesion->dbh);
			$this->Documento->Delete();
		}

	}

}
