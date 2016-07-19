<?php

interface IChargeManager extends BaseManager {
	/**
	 * Obtiene los adelantos utilizados en un cobro
	 * @param 	string $charge_id
	 * @return
	 */
	public function getAdvances($charge_id);
}
