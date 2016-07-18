<?php

class ExpenseManager extends AbstractManager implements BaseManager {

	/**
	 * Cambia el cliente de los gastos, luego de haber cambiado el asunto de cliente
	 * @param type $new_matter_code
	 * @param type $client_code
	 * @param type $new_client_code
	 * @throws Exception
	 */
  public function fixClient($new_matter_code, $client_code, $new_client_code) {
		$Criteria = $this->loadModel('Criteria', null, true);
		$expences = $Criteria->add_from('cta_corriente')
			->add_select('id_movimiento')
			->add_restriction(CriteriaRestriction::equals('codigo_asunto', "'$new_matter_code'"))
			->add_restriction(CriteriaRestriction::equals('codigo_cliente', "'$client_code'"))
			->add_restriction(CriteriaRestriction::is_not_null('egreso'));
		$expences = $Criteria->run();
		$total_expences = count($expences);
		for ($i = 0; $i < $total_expences; ++$i) {
			$expence_id = $expences[$i]['id_movimiento'];
			$Gasto = $this->loadModel('Gasto', null, true);
			$Gasto->Load($expence_id);
			$Gasto->Edit('codigo_cliente', $new_client_code);
			if (!$Gasto->Write()) {
				throw new Exception("No se pudo cambiar el cliente del adelanto {$expence_id}");
			}
		}
	}
}
