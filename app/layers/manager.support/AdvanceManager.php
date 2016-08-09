<?php

class AdvanceManager extends AbstractManager implements BaseManager {

	/**
	 * Cambia el cliente y contrato de los adelantos, luego de haber cambiado el asunto de cliente
	 * @param type $new_matter_code
	 * @param type $client_code
	 * @param type $new_client_code
	 * @param type $agreement_id
	 * @throws Exception
	 */
  public function fixClientAndAgreement($new_matter_code, $client_code, $new_client_code, $agreement_id) {
		$Criteria = new Criteria($this->Sesion);
		$Criteria->add_from('documento')
			->add_select('id_documento')
			->add_restriction(CriteriaRestriction::equals('codigo_asunto', "'$new_matter_code'"))
			->add_restriction(CriteriaRestriction::equals('codigo_cliente', "'$client_code'"))
			->add_restriction(CriteriaRestriction::equals('es_adelanto', '1'));
		$advances = $Criteria->run();
		$total_advances = count($advances);
		for ($i = 0; $i < $total_advances; ++$i) {
			$advance_id = $advances[$i]['id_documento'];
			$Documento = $this->loadModel('Documento', null, true);
			$Documento->Load($advance_id);
			$Documento->Edit('codigo_cliente', $new_client_code);
			$Documento->Edit('id_contrato', $agreement_id);
			if (!$Documento->Write()) {
				throw new Exception("No se pudo cambiar el cliente del adelanto {$advance_id}");
			}
		}
	}
}
