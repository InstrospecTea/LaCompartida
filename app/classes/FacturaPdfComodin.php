<?php

class FacturaPdfComodin {
	function __construct($Sesion, $Factura) {
		$this->Sesion = $Sesion;
		$this->Factura = $Factura;
	}

	public function getComodines(){
		$result = [];

		$Criteria = new Criteria($this->Sesion);
		$comodines = $Criteria
			->add_select('codigo')
			->add_select('glosa')
			->add_from('prm_codigo')
			->add_restriction(
				CriteriaRestriction::equals('grupo', "'PRM_FACTURA_PDF'")
			)
			->run();

		foreach ($comodines as $comodin) {
			$codigo = $comodin['codigo'];
			$glosa = $comodin['glosa'];

			if (method_exists($this, $glosa)) {
				$result[$codigo] = $this->$glosa();
			} else {
				$result[$codigo] = $glosa;
			}
		}

		return $result;
	}

	private function getCtaCteBanco() {
		$id_moneda = $this->Factura->fields['id_moneda'];
		$result = '';

		$Criteria = new Criteria($this->Sesion);
		$cuentas_banco = $Criteria
			->add_select('prm_banco.nombre')
			->add_select('cuenta_banco.id_moneda')
			->add_select('cuenta_banco.numero')
			->add_from('cuenta_banco')
			->add_left_join_with(
				'prm_banco',
				CriteriaRestriction::equals(
					'cuenta_banco.id_banco',
					'prm_banco.id_banco'
				)
			)
			->add_restriction(
				CriteriaRestriction::equals('id_moneda', $id_moneda)
			)
			->add_restriction(
				CriteriaRestriction::equals('imprimible', '1')
			)
			->run();

		$Moneda = new Moneda($this->Sesion);
		foreach ($cuentas_banco as $cuenta) {
			$moneda = $Moneda->GetSimboloMoneda(
				$this->Sesion,
				$cuenta['id_moneda']
			);
			$result .= "{$cuenta['nombre']}: Cta. Cte. {$moneda} {$cuenta['numero']}\r\n";
		}
		return $result;
	}
}
