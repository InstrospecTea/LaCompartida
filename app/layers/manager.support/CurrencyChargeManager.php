<?php

class CurrencyChargeManager extends AbstractManager implements BaseManager {

	public function __construct(Sesion $Sesion) {
		$this->Sesion = $Sesion;
		$this->loadService('CurrencyCharge');
	}

	public function findAll($restrictions = null, $fields = null, $order = null, $limit = null) {
		return $this->CurrencyChargeService->findAll($restrictions, $fields, $order, $limit);
	}

	public function update(Entity $entity) {
		$InsertCriteria = new InsertCriteria($this->Sesion);
		return $InsertCriteria
			->update()
			->add_pivot_with_value('tipo_cambio', $entity->fields['tipo_cambio'])
			->set_into($entity->getPersistenceTarget())
			->addRestriction(CriteriaRestriction::equals('id_cobro', $entity->fields['id_cobro']))
			->addRestriction(CriteriaRestriction::equals('id_moneda', $entity->fields['id_moneda']))
			->run();
	}

}
