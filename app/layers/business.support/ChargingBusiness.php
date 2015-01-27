<?php

class ChargingBusiness extends AbstractBusiness implements IChargingBusiness {

	public function __construct(Sesion $Session) {
		parent::__construct($Session);
		$this->loadService('Charge');
	}
	public function doesChargeExists($id_cobro) {
		if (empty($id_cobro)) {
			return false; //el id no puede ser vacío o cero
		}
		$restrictions = CriteriaRestriction::equals('id_cobro', "$id_cobro");
		$entity = $this->ChargeService->findFirst($restrictions, array('id_cobro'));
		return $entity !== false;
	}

}
