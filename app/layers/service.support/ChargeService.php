<?php

class ChargeService extends AbstractService implements IChargeService {

    public function getDaoLayer() {
        return 'ChargeDAO';
    }


    public function getClass() {
        return 'Charge';
    }

	public function saveOrUpdate($charge) {
		$estado = $charge->fields['estado'];
		if (empty($estado)) {
			$charge->set('estado', 'CREADO');
		}
		return parent::saveOrUpdate($charge);
	}

}