<?php

class EliminarLiquidaciones extends AppShell {
	public function main() {
		$this->Session->usuario = new UsuarioExt($this->Session, '99511620');
		$Criteria = new Criteria($this->Session);

		// todos los cobros asignados en la tabla tmp_cobro
		// CREATE TABLE tmp_cobro (id_cobro INT(11));
		$tmp_cobros = $Criteria
			->add_select('id_cobro')
			->add_from('tmp_cobro')
			->add_ordering('id_cobro')
			->run();

		$total_tmp_cobros = count($tmp_cobros);

		for ($x = 0; $x < $total_tmp_cobros; $x++) {
			$id_cobro = $tmp_cobros[$x]['id_cobro'];
			$this->out("----------------------------------------------------");
			$this->out("Cobro #{$id_cobro}");
			$Cobro = new Cobro($this->Session);
			$Cobro->Load($id_cobro);

			if (Conf::GetConf($this->Session, 'NuevoModuloFactura')) {
				$DocumentoCobro = new Documento($this->Session);
				$DocumentoCobro->LoadByCobro($id_cobro);

				$Criteria = new Criteria($this->Session);
				$facturas = $Criteria
					->add_select('id_factura')
					->add_from('factura')
					->add_restriction(CriteriaRestriction::equals('id_cobro', $id_cobro))
					->run();

				if (!empty($facturas)) {
					foreach ($facturas as $_factura) {
						$id_factura = $_factura['id_factura'];
						$Factura = new Factura($this->Session);
						$Factura->Load($id_factura);
						$this->out("Factura #{$id_cobro}");

						$Pagos = $Factura->GetPagosSoyFactura(null, $DocumentoCobro->fields['id_documento']);

						// eliminar los pagos de las facturas
						for ($i = 0; $i < $Pagos->num; $i++) {
							$Pago = $Pagos->Get($i);
							$Pago->Eliminar();
							$this->out("Pago #{$Pago->fields['id_factura_pago']} eliminado");
						}

						// eliminar relación entre la factura y el cobro
						$query = "DELETE FROM factura_cobro WHERE id_factura = '{$id_factura}' AND id_cobro = '{$id_cobro}'";
						$this->Session->pdodbh->query($query);
						$this->out("factura_cobro de Cobro #{$id_cobro} y Factura #{$id_factura} eliminado");

						// se elimina la factura
						$Factura->Eliminar();
						$this->out("Factura #{$id_factura} eliminada");
					}
				}
			} else {
				// eliminar pagos
				$DocumentoCobro = new Documento($this->Session);
				$DocumentoCobro->LoadByCobro($id_cobro);
				$query = "SELECT id_documento_pago FROM neteo_documento WHERE id_documento_cobro = '{$DocumentoCobro->fields['id_documento']}'";
				$_NeteoDocumentoPago = $this->Session->pdodbh->query($query);

				$neteo_documento_pagos = $_NeteoDocumentoPago->fetchAll(PDO::FETCH_ASSOC);
				foreach ($neteo_documento_pagos as $neteo_documento_pago) {
					$Pago = new Documento($this->Session);
					$Pago->Load($neteo_documento_pago['id_documento_pago']);

					if (!$Pago->fields['es_adelanto']) {
						$Pago->EliminarNeteos();
						$this->out("Neteos del pago #{$neteo_documento_pago['id_documento_pago']} eliminados");

						$query = "DELETE from cta_corriente WHERE cta_corriente.documento_pago = '{$neteo_documento_pago['id_documento_pago']}'";
						$this->Session->pdodbh->query($query);
						$this->out("cta_corriente del pago #{$neteo_documento_pago['id_documento_pago']} eliminados");

						$Pago->Delete();
						$this->out("Pago #{$neteo_documento_pago['id_documento_pago']} eliminado");
					} else {
						$Pago->EliminarNeteo($id_cobro);
						$this->out("Neteos del pago #{$neteo_documento_pago['id_documento_pago']} eliminados cuando es adelanto");
					}
				}
			}

			// eliminar el historial del cobro
			$query = "DELETE FROM cobro_historial WHERE id_cobro = '{$id_cobro}'";
			$this->Session->pdodbh->query($query);
			$this->out("cobro_historial de Cobro #{$id_cobro} eliminado");

			// eliminar el cobro
			$Cobro->Edit('estado', 'CREADO');
			$Cobro->Write();
			$Cobro->Eliminar();
			$this->out("Cobro #{$id_cobro} eliminado");
		}
	}
}
