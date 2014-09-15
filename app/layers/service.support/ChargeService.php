<?php
class ChargeService extends AbstractService implements IChargeService {
	public function getDaoLayer() {
		return 'ChargeDAO';
	}

	public function getClass() {
		return 'Charge';
	}

	public function saveOrUpdate($charge) {
		$estado = $charge->get('estado');
		$modalidad_calculo = $charge->get('modalidad_calculo');

		if (empty($estado)) {
			$charge->set('estado', 'CREADO');
		}
		
		if (empty($modalidad_calculo)) {
			$charge->set('modalidad_calculo', '0');
		} 

		return parent::saveOrUpdate($charge);
	}

}
