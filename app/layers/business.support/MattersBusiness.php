<?php

class MattersBusiness extends AbstractBusiness implements BaseBusiness {

	protected function changeClientOfMatterValidation(Asunto $Asunto) {
		if ($Asunto->fields['id_contrato_indep'] !== $Asunto->fields['id_contrato']) {
			throw new Exception(__('El asunto debe cobrarse de forma independiente'));
		}
		$this->loadManager('Matter');
		$matter_code = $Asunto->fields['codigo_asunto'];
		$charges = $this->MatterManager->getChargesOfWorks($matter_code);
		if ($charges === false) {
			throw new Exception(__('El asunto tiene Trabajos en los siguientes cobros:') . " {$charges}");
		}
		$charges = $this->MatterManager->getChargesOfErrands();
		if ($charges === false) {
			throw new Exception(__('El asunto tiene Trámites en los siguientes cobros:') . " {$charges}");
		}
		$charges = $this->MatterManager->getChargesOfExpenses();
		if ($charges === false) {
			throw new Exception(__('El asunto tiene Gastos en los siguientes cobros:') . " {$charges}");
		}
		$charges = $this->MatterManager->getChargesOfAdvances();
		if ($charges === false) {
			throw new Exception(__('El asunto tiene Adelantos en los siguientes cobros:') . " {$charges}");
		}
	}

	/**
	 * Cambia el cliente de un asunto y todas sus cositas
	 * @param Asunto $Asunto Instancia del asunto
	 * @param string $client_code cÃ³digo del nuevo cliente
	 * @return array ['Client' => Cliente, 'Matter' => Asunto]
	 * @throws Exception
	 */
	public function changeClientOfMatter(Asunto $Asunto, $client_code) {
		try {
			$this->changeClientOfMatterValidation($Asunto);
		} catch (Exception $e) {
			throw new Exception(__('No se puede cambiar el cliente') . ':<br/>' . $e->getMessage());
		}
		$NuevoCliente = new Cliente($this->Sesion);
		if (Configure::read('CodigoSecundario')) {
			$NuevoCliente->LoadByCodigoSecundario($client_code);
		} else {
			$NuevoCliente->LoadByCodigo($client_code);
		}

		$new_client_code = $NuevoCliente->fields['codigo_cliente'];
		$new_matter_code = $Asunto->AsignarCodigoAsunto($new_client_code);
		$old_client_code = $Asunto->fields['codigo_cliente'];

		$this->begin();
		try {
			$matter_code = $Asunto->fields['codigo_asunto'];
			$agreement_id = $Asunto->fields['id_contrato'];
			$this->changeClientOfAgreement($agreement_id, $new_client_code);
			$Asunto->Edit('codigo_asunto', $new_matter_code, true);
			$Asunto->Edit('codigo_cliente', $new_client_code, true);
			$Asunto->Write();

			$this->fixClientOfAdvanceRequests($new_matter_code, $old_client_code, $new_client_code, $agreement_id);
			$this->fixClientOfAdvances($new_matter_code, $old_client_code, $new_client_code, $agreement_id);
			$this->fixClientOfTasks($new_matter_code, $old_client_code, $new_client_code);
			$this->fixClientOfExpenses($new_matter_code, $old_client_code, $new_client_code);
		} catch (Exception $e) {
			$this->rollback();
			Utiles::errorSQL($e->getMessage(), __FILE__, __LINE__, $this->sesion->dbh, '', $e);
		}
		$this->commit();

		return array('Client' => $NuevoCliente, 'Matter' => $Asunto);
	}

	public function changeClientOfAgreement($agreement_id, $new_client_code) {
		$Contrato = $this->loadModel('Contrato', null, true);
		$Contrato->load($agreement_id);
		$Contrato->Edit('codigo_cliente', $new_client_code);
		if (!$Contrato->Write()) {
			throw new Exception("No se pudo cambiar el cliente del contrato {$agreement_id}");
		}
	}

	public function fixClientOfAdvanceRequests($new_matter_code, $client_code, $new_client_code, $agreement_id) {
		$Criteria = $this->loadModel('Criteria', null, true);
		$advance_requests = $Criteria->add_from('solicitud_adelanto')
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

	public function fixClientOfAdvances($new_matter_code, $client_code, $new_client_code, $agreement_id) {
		$Criteria = $this->loadModel('Criteria', null, true);
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

	public function fixClientOfTasks($new_matter_code, $client_code, $new_client_code) {
		$Criteria = $this->loadModel('Criteria', null, true);
		$tasks = $Criteria->add_from('tarea')
			->add_select('id_tarea')
			->add_restriction(CriteriaRestriction::equals('codigo_asunto', "'$new_matter_code'"))
			->add_restriction(CriteriaRestriction::equals('codigo_cliente', "'$client_code'"));
		$tasks = $Criteria->run();
		$total_tasks = count($tasks);
		for ($i = 0; $i < $total_tasks; ++$i) {
			$task_id = $tasks[$i]['id_tarea'];
			$Tarea = $this->loadModel('Tarea', null, true);
			$Tarea->Load($task_id);
			$Tarea->Edit('codigo_cliente', $new_client_code);
			if (!$Tarea->Write()) {
				throw new Exception("No se pudo cambiar el cliente del adelanto {$task_id}");
			}
		}
	}

	public function fixClientOfExpenses($new_matter_code, $client_code, $new_client_code) {
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
