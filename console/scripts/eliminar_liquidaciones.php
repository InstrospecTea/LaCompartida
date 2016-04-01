<?php

class EliminarLiquidaciones extends AppShell {
	public function main() {
		$Criteria = new Criteria($this->Session);

		// todos los cobros asignados en la tabla tmp_cobro
		$tmp_cobros = $Criteria
			->add_select('id_cobro')
			->add_from('tmp_cobro')
			->run();

		$total_tmp_cobros = count($tmp_cobros);
		for ($x = 0; $x < $total_tmp_cobros; $x++) {
			$id_cobro = $tmp_cobros[$x]['id_cobro'];
			$Cobro = new Cobro($this->Session);
			$Cobro->Load($id_cobro);

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

					$Pagos = $Factura->GetPagosSoyFactura(null, $DocumentoCobro->fields['id_documento']);

					// eliminar los pagos de las facturas
					for ($i = 0; $i < $Pagos->num; $i++) {
						$Pago = $Pagos->Get($i);
						$Pago->Eliminar();
					}

					// eliminar relación entre la factura y el cobro
					$query = "DELETE FROM factura_cobro WHERE id_factura = '{$id_factura}' AND id_cobro = '{$id_cobro}'";
					$this->Session->pdodbh->query($query);

					// se elimina la factura
					$Factura->Eliminar();
				}
			}

			// eliminar el cobro
			$Cobro->Edit('estado', 'CREADO');
			$Cobro->Write();
			$Cobro->Eliminar();

			// eliminar relación entre la factura y el cobro
			$query = "DELETE FROM cobro_historial WHERE id_cobro = '{$id_cobro}'";
			$this->Session->pdodbh->query($query);
		}
	}
}
