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
		$id_doc_pago_honorarios = $charge->get('id_doc_pago_honorarios');

		if (empty($estado)) {
			$charge->set('estado', 'CREADO');
		}
		
		if (empty($id_doc_pago_honorarios)) {
			$charge->set('id_doc_pago_honorarios', 'NULL');
		}

		if (empty($modalidad_calculo)) {
			$charge->set('modalidad_calculo', '0');
		} 

		return parent::saveOrUpdate($charge);
	}

}
