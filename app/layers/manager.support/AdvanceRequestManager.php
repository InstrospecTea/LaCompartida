<?php

class AdvanceRequestManager extends AbstractManager implements BaseManager {

	/**
	 * Cambia el cliente y contrato de las solicitudes adelantos, luego de haber cambiado el asunto de cliente
	 * @param type $new_matter_code
	 * @param type $client_code
	 * @param type $new_client_code
	 * @param type $agreement_id
	 * @throws Exception
	 */
  public function fixClientAndAgreement($new_matter_code, $client_code, $new_client_code, $agreement_id) {
		$Criteria = new Criteria($this->Sesion);
		$Criteria->add_from('solicitud_adelanto')
			->add_select('id_solicitud_adelanto')
			->add_restriction(CriteriaRestriction::equals('codigo_asunto', "'$new_matter_code'"))
			->add_restriction(CriteriaRestriction::equals('codigo_cliente', "'$client_code'"));
		$advance_requests = $Criteria->run();
		$total_advance_requests = count($advance_requests);
		for ($i = 0; $i < $total_advance_requests; ++$i) {
			$advance_request_id = $advance_requests[$i]['id_solicitud_adelanto'];
			$SolicitudAdelanto = $this->loadModel('SolicitudAdelanto', null, true);
			$SolicitudAdelanto->Load($advance_request_id);
			$SolicitudAdelanto->Edit('codigo_cliente', $new_client_code);
			$SolicitudAdelanto->Edit('id_contrato', $agreement_id);
			if (!$SolicitudAdelanto->Write()) {
				throw new Exception("No se pudo cambiar el cliente de la solicitud de adelanto {$advance_request_id}");
			}
		}
	}
}
